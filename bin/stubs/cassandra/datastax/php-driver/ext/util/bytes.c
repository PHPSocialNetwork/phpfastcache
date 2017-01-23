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
#include "util/bytes.h"

void
php_cassandra_bytes_to_hex(const char *bin, int len, char **out, int *out_len)
{
  char hex_str[] = "0123456789abcdef";
  int  i;

  *out_len = len * 2 + 2;
  *out = (char *) emalloc(sizeof(char) * (len * 2 + 3));
  (*out)[0] = '0';
  (*out)[1] = 'x';
  (*out)[len * 2 + 2] = '\0';

  for (i = 0; i < len; i++) {
    (*out)[i * 2 + 2] = hex_str[(bin[i] >> 4) & 0x0F];
    (*out)[i * 2 + 3] = hex_str[(bin[i]     ) & 0x0F];
  }
}
