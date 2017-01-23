/**
 * Copyright 2015-2016 DataStax, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

#include "php_cassandra.h"
#include <stdlib.h>
#include "util/inet.h"

#define IPV4             1
#define IPV6             2
#define TOKEN_MAX_LEN    4
#define IP_MAX_ADDRLEN   50
#define EXPECTING_TOKEN(expected) \
  zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC, \
    "Unexpected %s at position %d in address \"%s\", expected " expected, \
    ip_address_describe_token(type), ((int) (in_ptr - in) - 1), in \
  ); \
  return 0;

enum token_type {
  TOKEN_END = 0,
  TOKEN_COLON,
  TOKEN_DOT,
  TOKEN_HEX,
  TOKEN_DEC,
  TOKEN_ILLEGAL
};

enum parser_state {
  STATE_START = 0,
  STATE_FIELD = 1,
  STATE_COMPRESSED = 2,
  STATE_AFTERHEX = 3,
  STATE_AFTERDEC = 4,
  STATE_IPV4BYTE = 5,
  STATE_IPV4DOT = 6,
  STATE_AFTERCOLON = 7,
  STATE_END = 8
};

static const char *
ip_address_describe_token(enum token_type type)
{
  switch (type) {
  case TOKEN_END    : return "end of address";    break;
  case TOKEN_COLON  : return "colon";             break;
  case TOKEN_DOT    : return "dot";               break;
  case TOKEN_HEX    : return "hex address field"; break;
  case TOKEN_DEC    : return "address field";     break;
  case TOKEN_ILLEGAL: return "illegal character"; break;
  default           : return NULL;
  }
}

static enum token_type
ip_address_tokenize(char *address, char *token, int *token_len, char **next_token)
{
  enum token_type type;

  char ch;
  int len = 0;

  memset(token, 0, TOKEN_MAX_LEN + 1);
  ch = address[len];

  if (isxdigit(ch)) {
    int is_hex = 0;

    while (len < TOKEN_MAX_LEN) {
      ch = address[len];

      if (!isxdigit(ch))
        break;

      /* To be able to differentiate between numbers and hex. */
      if (!isdigit(ch))
        is_hex = 1;

      /* Lower case, since IPv6 addresses are case insensitive. */
      token[len++] = tolower(ch);
    }

    if (is_hex)
      type = TOKEN_HEX;
    else
      type = TOKEN_DEC;
  } else {
    switch (ch) {
    case '\0': type = TOKEN_END;     break;
    case ':' : type = TOKEN_COLON;   break;
    case '.' : type = TOKEN_DOT;     break;
    default  : type = TOKEN_ILLEGAL;
    }

    token[len++] = ch;
  }

  token[len] = '\0';

  *next_token = &(address[len]);
  *token_len = len;

  return type;
}

int
php_cassandra_parse_ip_address(char *in, CassInet *inet TSRMLS_DC)
{
  char              token[TOKEN_MAX_LEN + 1];
  int               token_len                    = -1;
  int               prev_token_len               = 0;
  char             *in_ptr                       = in;
  enum token_type   type;
  enum parser_state state                        = STATE_START;
  int               pos                          = -1;
  int               compress_pos                 = -1;
  int               ipv4_pos                     = -1;
  int               ipv4_byte;
  cass_uint8_t      address[CASS_INET_V6_LENGTH] = {0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0};
  int               domain                       = 0;

  if (strlen(in) > (IP_MAX_ADDRLEN - 1)) {
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
      "The IP address \"%s\" is too long (at most %d characters are expected)",
      in, IP_MAX_ADDRLEN - 1);
    return 0;
  }

  for (;;) {
    if (token_len > -1)
      prev_token_len = token_len;

    type = ip_address_tokenize(in_ptr, token, &token_len, &in_ptr);

    if (type == TOKEN_ILLEGAL) {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
        "Illegal character \"%c\" at position %d in address \"%s\"",
        *token, ((int) (in_ptr - in) - 1), in);
      return 0;
    }

    if (state == STATE_START) {
      /* A colon is found. This must be the start of a compressed
       * group of zeroes.
       */
      if (type == TOKEN_COLON) {
        state = STATE_COMPRESSED;
        continue;
      }

      /* At this point, we expect an IP field. */
      if (type != TOKEN_HEX && type != TOKEN_DEC) {
        EXPECTING_TOKEN("an address field or a colon");
      }

      /* The IP field will be handled by the FIELD state */
      state = STATE_FIELD;
    }

    /* AFTERCOLON: expect either another colon for indicating a
     * compressed group of zeroes or an IPv6 field.
     */
    if (state == STATE_AFTERCOLON) {
      if (type == TOKEN_COLON)
        state = STATE_COMPRESSED;
      else
        state = STATE_FIELD;
    }

    /* COMPRESSED: expect second colon of a compressed group of zeroes */
    if (state == STATE_COMPRESSED) {
      if (type == TOKEN_COLON) {
        /* Only one compressed zero block can exist. */
        if (compress_pos != -1) {
          zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
            "Duplicate \"::\" block at position %d in address \"%s\"",
            ((int) (in_ptr - in) - 1), in);
          return 0;
        }

        compress_pos = pos == -1 ? 0 : pos + 1;
        state = STATE_FIELD;
        continue;
      } else {
        EXPECTING_TOKEN("a colon");
      }
    }

    /* FIELD: expect an IP address field. */
    if (state == STATE_FIELD) {
      unsigned int field;

      /* End of string is only valid after a zero compression
       * block "::".
       */
      if (type == TOKEN_END && pos + 1 == compress_pos)
        break;

      switch (type) {
      case TOKEN_HEX  : state = STATE_AFTERHEX;      break;
      case TOKEN_DEC  : state = STATE_AFTERDEC;      break;
      default         : EXPECTING_TOKEN("an address field");
      }

      /* Check for too many address bytes. */
      if (pos + 3 > CASS_INET_V6_LENGTH) {
        pos += 2;
        break;
      }

      /* Add the IPv6 field bytes. */
      sscanf(token, "%x", &field);
      address[++pos] = (field & 0xff00) >> 8;;
      address[++pos] = (field & 0x00ff);
      continue;
    }

    /* AFTERHEX: the previous token was a hexadecimal number. Expect a
     * colon, which starts the next IPv6 field.
     */
    if (state == STATE_AFTERHEX) {
      if (type == TOKEN_END) {
        break;
      } else if (type == TOKEN_COLON) {
        state = STATE_AFTERCOLON;
        continue;
      } else {
        EXPECTING_TOKEN("a colon");
      }
    }

    /* AFTERDEC: the previous token was a decimal number. Expect a
     * colon, which starts the next IPv6 field, or a dot, which
     * indicates the start of an IPv4 style address.
     */
    if (state == STATE_AFTERDEC) {
      if (type == TOKEN_END) {
        break;
      } else if (type == TOKEN_COLON) {
        state = STATE_AFTERCOLON;
        continue;
      } else if (type == TOKEN_DOT) {
        /* Rollback bytes that we assumed to be an IPv6 hex field */
        address[pos--] = 0;
        address[pos--] = 0;
        in_ptr -= (prev_token_len + 1);

        /* Continue with IPv4 address parsing. */
        state = STATE_IPV4BYTE;
        ipv4_pos = 0;
        continue;
      } else {
        EXPECTING_TOKEN("a colon or a dot");
      }
    }

    /* IPV4BYTE: we're parsing an IPv4 style address representation
     * and are expecting the next byte for it (0-255). */
    if (state == STATE_IPV4BYTE) {
      if (type == TOKEN_DEC) {
        if (token_len > 1 && token[0] == '0') {
          zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
            "Illegal IPv4 character \"%s\" at position %d " \
            "in address \"%s\" (no leading zeroes are allowed)",
            token, ((int) (in_ptr - in) - 1), in);
          return 0;
        }

        ipv4_byte = atoi(token);

        if (ipv4_byte < 0 || ipv4_byte > 255) {
          zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
            "Illegal IPv4 segment value '%d' at position %d " \
            "in address \"%s\" (expected: 0 - 255)",
            ipv4_byte, ((int) (in_ptr - in) - 1), in);
          return 0;
        }

        /* Check for too many address bytes. */
        if (pos + 2 > CASS_INET_V6_LENGTH) {
          pos++;
          break;
        }

        /* Add the IPv4 byte. */
        ipv4_pos++;
        address[++pos] = ipv4_byte;

        /* After 4 bytes, our IPv4 address is complete. */
        if (ipv4_pos == CASS_INET_V4_LENGTH) {
          /* When we have seen 4 bytes and if we only have four
           * bytes in the parsed address so far, then this must be
           * an IPv4 address.
           */
          if (compress_pos == -1 && pos + 1 == CASS_INET_V4_LENGTH)
            domain = IPV4;

          state = STATE_END;
        } else {
          state = STATE_IPV4DOT;
        }
        continue;
      } else {
        EXPECTING_TOKEN("an IPv4 address byte (0 - 255)");
      }
    }

    /* IPV4DOT: we're parsing an IPv4 style address representation
     * and are expecting the next dot for it. */
    if (state == STATE_IPV4DOT) {
      if (type == TOKEN_DOT) {
        state = STATE_IPV4BYTE;
        continue;
      } else {
        EXPECTING_TOKEN("a dot");
      }
    }

    /* END: we've seen the end of the IP address, no more tokens
     * are expected.
     */
    if (state == STATE_END) {
      if (type == TOKEN_END)
        break;
      else
        EXPECTING_TOKEN("the end of address");
    }
  }

  /* All tokenizing and analyzing is done.
   * The parser might already have decided that the parsed address
   * was an IPv4 address. Since the parser does check the byte values
   * and the length of the address already, we don't have to do
   * extensive checking on the address anymore at this point.
   * If we have encountered compressed IPv6 zeroes, then we inflate
   * the IPv6 address up to its full byte size here.
   */
  if (!domain && compress_pos != -1) {
    /* Compression is in use, but there is no space for decompression
     * in the parsed byte array.
     */
    if (pos + 1 >= CASS_INET_V6_LENGTH) {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
        "Address \"%s\" contains a compressed zeroes block '::', "
        "but the address already contains %d bytes or more",
        address, CASS_INET_V6_LENGTH);
      return 0;
    }

    /* If compression is at the end of the address. We already have
     * zeroes setup for those fields, so no extra work is needed.
     */
    pos++;
    if (pos != compress_pos) {
      int i;
      int move_len     = pos - compress_pos;

      /* Move bytes after the compression position to the end and
       * fill the old byte positions with zeroes. */
      for (i = 0; i < move_len; i++) {
        int src_pos = compress_pos + move_len - i - 1;
        int dst_pos = CASS_INET_V6_LENGTH - i - 1;

        address[dst_pos] = address[src_pos];
        address[src_pos] = 0;
      }
    }

    domain = IPV6;
  }
  /* When there are no compressed zeroes, then the address should be
   * at the required length already.
   */
  else if (!domain) {
    /* Check if there are enough bytes. */
    if (pos + 1 < CASS_INET_V6_LENGTH) {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
        "Address \"%s\" contains only %d bytes  (%d bytes are expected)",
        in, pos + 1, CASS_INET_V6_LENGTH);
      return 0;
    }

    /* Check if the number of bytes does not exceed the maximum. */
    if (pos + 1 > CASS_INET_V6_LENGTH) {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
        "Address \"%s\" exceeds the maximum IPv6 byte length " \
        "(%d bytes are expected)\n", in, CASS_INET_V6_LENGTH);
      return 0;
    }

    domain = IPV6;
  }

  if (domain == IPV6)
    *inet = cass_inet_init_v6(address);
  else
    *inet = cass_inet_init_v4(address);

  return 1;
}

void
php_cassandra_format_address(CassInet inet, char **out)
{
  if (inet.address_length > 4)
    spprintf(out, 0, "%x:%x:%x:%x:%x:%x:%x:%x",
      (inet.address[0]  * 256 + inet.address[1]),
      (inet.address[2]  * 256 + inet.address[3]),
      (inet.address[4]  * 256 + inet.address[5]),
      (inet.address[6]  * 256 + inet.address[7]),
      (inet.address[8]  * 256 + inet.address[9]),
      (inet.address[10] * 256 + inet.address[11]),
      (inet.address[12] * 256 + inet.address[13]),
      (inet.address[14] * 256 + inet.address[15])
    );
  else
    spprintf(out, 0, "%d.%d.%d.%d",
      inet.address[0],
      inet.address[1],
      inet.address[2],
      inet.address[3]
    );
}
