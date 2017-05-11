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
#include "util/types.h"
#if PHP_MAJOR_VERSION >= 7
#include <zend_smart_str.h>
#else
#include <ext/standard/php_smart_str.h>
#endif
#include "src/Cassandra/Bigint.h"
#include "src/Cassandra/Smallint.h"
#include "src/Cassandra/Tinyint.h"
#include "src/Cassandra/Blob.h"
#include "src/Cassandra/Decimal.h"
#include "src/Cassandra/Float.h"
#include "src/Cassandra/Inet.h"
#include "src/Cassandra/Timestamp.h"
#include "src/Cassandra/Date.h"
#include "src/Cassandra/Time.h"
#include "src/Cassandra/Timeuuid.h"
#include "src/Cassandra/Uuid.h"
#include "src/Cassandra/Varint.h"
#include "src/Cassandra/Type/Tuple.h"
#include "src/Cassandra/Type/UserType.h"

ZEND_EXTERN_MODULE_GLOBALS(cassandra)

struct node_s {
  struct node_s *parent;
  const char    *name;
  size_t         name_length;
  struct node_s *first_child;
  struct node_s *last_child;
  struct node_s *next_sibling;
  struct node_s *prev_sibling;
};
static int
hex_value(int c)
{
  if (c >= '0' && c <= '9') {
    return c - '0';
  } else if (c >= 'A' && c <= 'F') {
    return c - 'A' + 10;
  } else if (c >= 'a' && c <= 'f') {
    return c - 'a' + 10;
  }
  return -1;
}

static char*
php_cassandra_from_hex(const char* hex, size_t hex_length)
{
  size_t i, c = 0;
  size_t size = hex_length / 2;
  char *result;
  if ((hex_length & 1) == 1) { /* Invalid if not divisible by 2 */
    return NULL;
  }
  result = emalloc(size + 1);
  for (i = 0; i < size; ++i) {
    int half0 = hex_value(hex[i * 2]);
    int half1 = hex_value(hex[i * 2 + 1]);
    if (half0 < 0 || half1 < 0) {
      efree(result);
      return NULL;
    }
    result[c++] = (char)(((uint8_t)half0 << 4) | (uint8_t)half1);
  }
  result[size] = '\0';
  return result;
}

static php5to7_zval
php_cassandra_create_type(struct node_s *node TSRMLS_DC);

static php5to7_zval
php_cassandra_tuple_from_data_type(const CassDataType *data_type TSRMLS_DC) {
  php5to7_zval ztype;
  cassandra_type *type;
  size_t i, count;

  count = cass_data_sub_type_count(data_type);
  ztype = php_cassandra_type_tuple(TSRMLS_C);
  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));
  for (i = 0; i < count; ++i) {
    php5to7_zval sub_type =
        php_cassandra_type_from_data_type(
          cass_data_type_sub_data_type(data_type, i) TSRMLS_CC);
    php_cassandra_type_tuple_add(type,
                                 PHP5TO7_ZVAL_MAYBE_P(sub_type)
                                 TSRMLS_CC);
  }

  return ztype;
}

static php5to7_zval
php_cassandra_tuple_from_node(struct node_s *node TSRMLS_DC) {
  php5to7_zval ztype;
  cassandra_type *type;
  struct node_s *current;

  ztype = php_cassandra_type_tuple(TSRMLS_C);
  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));

  for (current = node->first_child;
       current != NULL;
       current = current->next_sibling) {
    php5to7_zval sub_type = php_cassandra_create_type(current TSRMLS_CC);
    php_cassandra_type_tuple_add(type,
                                 PHP5TO7_ZVAL_MAYBE_P(sub_type)
                                 TSRMLS_CC);
  }

  return ztype;
}

static php5to7_zval
php_cassandra_user_type_from_data_type(const CassDataType *data_type TSRMLS_DC)
{
  php5to7_zval ztype;
  cassandra_type *type;
  const char *type_name, *keyspace;
  size_t  type_name_len, keyspace_len;
  size_t i, count;

  count = cass_data_sub_type_count(data_type);
  ztype = php_cassandra_type_user_type(TSRMLS_C);
  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));

  cass_data_type_type_name(data_type, &type_name, &type_name_len);
  type->type_name = estrndup(type_name, type_name_len);
  cass_data_type_keyspace(data_type, &keyspace, &keyspace_len);
  type->keyspace = estrndup(keyspace, keyspace_len);

  for (i = 0; i < count; ++i) {
    const char *name;
    size_t name_length;
    php5to7_zval sub_type =
        php_cassandra_type_from_data_type(
          cass_data_type_sub_data_type(data_type, i) TSRMLS_CC);
    cass_data_type_sub_type_name(data_type, i, &name, &name_length);
    php_cassandra_type_user_type_add(type,
                                     name, name_length,
                                     PHP5TO7_ZVAL_MAYBE_P(sub_type) TSRMLS_CC);
  }

  return ztype;
}


static php5to7_zval
php_cassandra_user_type_from_node(struct node_s *node TSRMLS_DC)
{
  php5to7_zval ztype;
  cassandra_type *type;
  struct node_s *current = node->first_child;

  ztype = php_cassandra_type_user_type(TSRMLS_C);
  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));

  if (current) {
    type->keyspace = estrndup(current->name,
                              current->name_length);
    current = current->next_sibling;
  }

  if (current) {
    type->type_name = php_cassandra_from_hex(current->name,
                                             current->name_length);
    current = current->next_sibling;
  }

  for (; current; current = current->next_sibling) {
    php5to7_zval sub_type;
    char *name = php_cassandra_from_hex(current->name,
                                        current->name_length);
    current = current->next_sibling;
    if (!current) {
      efree(name);
      break;
    }
    sub_type = php_cassandra_create_type(current TSRMLS_CC);
    php_cassandra_type_user_type_add(type,
                                     name, strlen(name),
                                     PHP5TO7_ZVAL_MAYBE_P(sub_type) TSRMLS_CC);
    efree(name);
  }

  return ztype;
}

php5to7_zval
php_cassandra_type_from_data_type(const CassDataType *data_type TSRMLS_DC)
{
  php5to7_zval ztype;
  php5to7_zval key_type;
  php5to7_zval value_type;
  CassValueType type = cass_data_type_type(data_type);

  PHP5TO7_ZVAL_UNDEF(ztype);

  switch (type) {
#define XX_SCALAR(name, value) \
  case value: \
      ztype = php_cassandra_type_scalar(value TSRMLS_CC); \
    break;
   PHP_CASSANDRA_SCALAR_TYPES_MAP(XX_SCALAR)
#undef XX_SCALAR

  case CASS_VALUE_TYPE_LIST:
    value_type = php_cassandra_type_from_data_type(
      cass_data_type_sub_data_type(data_type, 0) TSRMLS_CC);
    ztype = php_cassandra_type_collection(PHP5TO7_ZVAL_MAYBE_P(value_type) TSRMLS_CC);
    break;

  case CASS_VALUE_TYPE_MAP:
    key_type = php_cassandra_type_from_data_type(
                 cass_data_type_sub_data_type(data_type, 0) TSRMLS_CC);
    value_type = php_cassandra_type_from_data_type(
                   cass_data_type_sub_data_type(data_type, 1) TSRMLS_CC);
    ztype = php_cassandra_type_map(PHP5TO7_ZVAL_MAYBE_P(key_type),
                                   PHP5TO7_ZVAL_MAYBE_P(value_type) TSRMLS_CC);
    break;

  case CASS_VALUE_TYPE_SET:
    value_type = php_cassandra_type_from_data_type(
      cass_data_type_sub_data_type(data_type, 0) TSRMLS_CC);
    ztype = php_cassandra_type_set(PHP5TO7_ZVAL_MAYBE_P(value_type) TSRMLS_CC);
    break;

  case CASS_VALUE_TYPE_TUPLE:
      ztype = php_cassandra_tuple_from_data_type(data_type TSRMLS_CC);
      break;

  case CASS_VALUE_TYPE_UDT:
      ztype = php_cassandra_user_type_from_data_type(data_type TSRMLS_CC);
      break;

  default:
    break;
  }

  return ztype;
}

int php_cassandra_type_validate(zval *object, const char *object_name TSRMLS_DC)
{
  if (!instanceof_function(Z_OBJCE_P(object), cassandra_type_scalar_ce TSRMLS_CC) &&
      !instanceof_function(Z_OBJCE_P(object), cassandra_type_collection_ce TSRMLS_CC) &&
      !instanceof_function(Z_OBJCE_P(object), cassandra_type_map_ce TSRMLS_CC) &&
      !instanceof_function(Z_OBJCE_P(object), cassandra_type_set_ce TSRMLS_CC) &&
      !instanceof_function(Z_OBJCE_P(object), cassandra_type_tuple_ce TSRMLS_CC) &&
      !instanceof_function(Z_OBJCE_P(object), cassandra_type_user_type_ce TSRMLS_CC)) {
    throw_invalid_argument(object, object_name, "a valid Cassandra\\Type" TSRMLS_CC);
    return 0;
  }
  return 1;
}

static inline int
collection_compare(cassandra_type *type1, cassandra_type *type2 TSRMLS_DC)
{
  return php_cassandra_type_compare(PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type1->value_type)),
                                    PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type2->value_type)) TSRMLS_CC);
}

static inline int
map_compare(cassandra_type *type1, cassandra_type *type2 TSRMLS_DC)
{
  int result;
  result = php_cassandra_type_compare(PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type1->key_type)),
                                       PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type2->key_type)) TSRMLS_CC);
  if (result != 0) return result;
  result =  php_cassandra_type_compare(PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type1->value_type)),
                                       PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type2->value_type)) TSRMLS_CC);
  if (result != 0) return result;
  return 0;
}

static inline int
set_compare(cassandra_type *type1, cassandra_type *type2 TSRMLS_DC)
{
  return php_cassandra_type_compare(PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type1->value_type)),
                                    PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type2->value_type)) TSRMLS_CC);
}

static inline int
tuple_compare(cassandra_type *type1, cassandra_type *type2 TSRMLS_DC) {
  HashPosition pos1;
  HashPosition pos2;
  php5to7_zval *current1;
  php5to7_zval *current2;

  if (zend_hash_num_elements(&type1->types) != zend_hash_num_elements(&type2->types)) {
    return zend_hash_num_elements(&type1->types) < zend_hash_num_elements(&type2->types) ? -1 : 1;
  }

  zend_hash_internal_pointer_reset_ex(&type1->types, &pos1);
  zend_hash_internal_pointer_reset_ex(&type2->types, &pos2);

  while (PHP5TO7_ZEND_HASH_GET_CURRENT_DATA_EX(&type1->types, current1, &pos1) &&
         PHP5TO7_ZEND_HASH_GET_CURRENT_DATA_EX(&type2->types, current2, &pos2)) {
    cassandra_type *sub_type1 =
        PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_DEREF(current1));
    cassandra_type *sub_type2 =
        PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_DEREF(current2));
    int result = php_cassandra_type_compare(sub_type1, sub_type2 TSRMLS_CC);
    if (result != 0) return result;
    zend_hash_move_forward_ex(&type1->types, &pos1);
    zend_hash_move_forward_ex(&type2->types, &pos2);
  }

  return 0;
}

static inline int
user_type_compare(cassandra_type *type1, cassandra_type *type2 TSRMLS_DC)
{
  HashPosition pos1;
  HashPosition pos2;
  php5to7_string key1;
  php5to7_string key2;
  php5to7_zval *current1;
  php5to7_zval *current2;

  if (zend_hash_num_elements(&type1->types) != zend_hash_num_elements(&type2->types)) {
    return zend_hash_num_elements(&type1->types) < zend_hash_num_elements(&type2->types) ? -1 : 1;
  }

  zend_hash_internal_pointer_reset_ex(&type1->types, &pos1);
  zend_hash_internal_pointer_reset_ex(&type2->types, &pos2);

  while (PHP5TO7_ZEND_HASH_GET_CURRENT_KEY_EX(&type1->types, &key1, NULL, &pos1) == HASH_KEY_IS_STRING &&
         PHP5TO7_ZEND_HASH_GET_CURRENT_KEY_EX(&type2->types, &key2, NULL, &pos2) == HASH_KEY_IS_STRING &&
         PHP5TO7_ZEND_HASH_GET_CURRENT_DATA_EX(&type1->types, current1, &pos1) &&
         PHP5TO7_ZEND_HASH_GET_CURRENT_DATA_EX(&type2->types, current2, &pos2)) {
    int result;
    cassandra_type *sub_type1 =
        PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_DEREF(current1));
    cassandra_type *sub_type2 =
        PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_DEREF(current2));
    result = php5to7_string_compare(key1, key2);
    if (result != 0) return result;
    result = php_cassandra_type_compare(sub_type1, sub_type2 TSRMLS_CC);
    if (result != 0) return result;
    zend_hash_move_forward_ex(&type1->types, &pos1);
    zend_hash_move_forward_ex(&type2->types, &pos2);
  }

  return 0;
}

static inline int
is_string_type(CassValueType type)
{
  return type == CASS_VALUE_TYPE_VARCHAR || type == CASS_VALUE_TYPE_TEXT;
}

int
php_cassandra_type_compare(cassandra_type *type1, cassandra_type *type2 TSRMLS_DC)
{
  if (type1->type != type2->type) {
    if (is_string_type(type1->type) &&
        is_string_type(type2->type)) { /* varchar and text are aliases */
      return 0;
    }
    return type1->type < type2->type ? -1 : 1;
  } else {
    switch (type1->type) {
    case CASS_VALUE_TYPE_LIST:
      return collection_compare(type1, type2 TSRMLS_CC);

    case CASS_VALUE_TYPE_MAP:
      return map_compare(type1, type2 TSRMLS_CC);

    case CASS_VALUE_TYPE_SET:
      return set_compare(type1, type2 TSRMLS_CC);

    case CASS_VALUE_TYPE_TUPLE:
      return tuple_compare(type1, type2 TSRMLS_CC);

    case CASS_VALUE_TYPE_UDT:
      return user_type_compare(type1, type2 TSRMLS_CC);

    default:
      break;
    }
    return 0;
  }
}

static inline void
collection_string(cassandra_type *type, smart_str *string TSRMLS_DC)
{
  smart_str_appendl(string, "list<", 5);
  php_cassandra_type_string(PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type->value_type)), string TSRMLS_CC);
  smart_str_appendl(string, ">", 1);
}

static inline void
map_string(cassandra_type *type, smart_str *string TSRMLS_DC)
{
  smart_str_appendl(string, "map<", 4);
  php_cassandra_type_string(PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type->key_type)), string TSRMLS_CC);
  smart_str_appendl(string, ", ", 2);
  php_cassandra_type_string(PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type->value_type)), string TSRMLS_CC);
  smart_str_appendl(string, ">", 1);
}

static inline void
set_string(cassandra_type *type, smart_str *string TSRMLS_DC)
{
  smart_str_appendl(string, "set<", 4);
  php_cassandra_type_string(PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(type->value_type)), string TSRMLS_CC);
  smart_str_appendl(string, ">", 1);
}

static inline void
tuple_string(cassandra_type *type, smart_str *string TSRMLS_DC) {
  php5to7_zval *current;
  int first = 1;

  smart_str_appendl(string, "tuple<", 6);
  PHP5TO7_ZEND_HASH_FOREACH_VAL(&type->types, current) {
    cassandra_type *sub_type =
        PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_DEREF(current));
    if (!first) smart_str_appendl(string, ", ", 2);
    first = 0;
    php_cassandra_type_string(sub_type, string TSRMLS_CC);
  } PHP5TO7_ZEND_HASH_FOREACH_END(&type->types);
  smart_str_appendl(string, ">", 1);
}

static inline void
user_type_string(cassandra_type *type, smart_str *string TSRMLS_DC)
{
  char *name;
  php5to7_zval *current;
  int first = 1;

  if (type->type_name) {
    if (type->keyspace) {
      smart_str_appendl(string, type->keyspace, strlen(type->keyspace));
      smart_str_appendl(string, ".", 1);
    }
    smart_str_appendl(string, type->type_name, strlen(type->type_name));
  } else {
    smart_str_appendl(string, "userType<", 9);
    PHP5TO7_ZEND_HASH_FOREACH_STR_KEY_VAL(&type->types, name, current) {
      cassandra_type *sub_type =
          PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_DEREF(current));
      if (!first) smart_str_appendl(string, ", ", 2);
      first = 0;
      smart_str_appendl(string, name, strlen(name));
      smart_str_appendl(string, ":", 1);
      php_cassandra_type_string(sub_type, string TSRMLS_CC);
    } PHP5TO7_ZEND_HASH_FOREACH_END(&type->types);
    smart_str_appendl(string, ">", 1);
  }
}

void
php_cassandra_type_string(cassandra_type *type, smart_str *string TSRMLS_DC)
{
  switch (type->type) {
#define XX_SCALAR(name, value) \
  case value: \
    smart_str_appendl(string, #name, strlen(#name)); \
    break;
  PHP_CASSANDRA_SCALAR_TYPES_MAP(XX_SCALAR)
#undef XX_SCALAR

  case CASS_VALUE_TYPE_LIST:
    collection_string(type, string TSRMLS_CC);
    break;

  case CASS_VALUE_TYPE_MAP:
    map_string(type, string TSRMLS_CC);
    break;

  case CASS_VALUE_TYPE_SET:
    set_string(type, string TSRMLS_CC);
    break;

  case CASS_VALUE_TYPE_TUPLE:
    tuple_string(type, string TSRMLS_CC);
    break;

  case CASS_VALUE_TYPE_UDT:
    user_type_string(type, string TSRMLS_CC);
    break;

  default:
    smart_str_appendl(string, "invalid", 7);
    break;
  }
}

static php5to7_zval
php_cassandra_type_scalar_new(CassValueType type TSRMLS_DC)
{
  php5to7_zval ztype;
  cassandra_type *scalar;

  PHP5TO7_ZVAL_MAYBE_MAKE(ztype);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(ztype), cassandra_type_scalar_ce);
  scalar = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));
  scalar->type = type;
  scalar->data_type = cass_data_type_new(type);

  return ztype;
}


const char *
php_cassandra_scalar_type_name(CassValueType type TSRMLS_DC)
{
  switch (type) {
#define XX_SCALAR(name, value) \
  case value: \
    return #name;
  PHP_CASSANDRA_SCALAR_TYPES_MAP(XX_SCALAR)
#undef XX_SCALAR
  default:
    return "invalid";
  }
}

static void
php_cassandra_varchar_init(INTERNAL_FUNCTION_PARAMETERS)
{
  char *string;
  php5to7_size string_len;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &string, &string_len) == FAILURE) {
    return;
  }

  PHP5TO7_RETVAL_STRINGL(string, string_len);
}

static void
php_cassandra_ascii_init(INTERNAL_FUNCTION_PARAMETERS)
{
  php_cassandra_varchar_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}

static void
php_cassandra_boolean_init(INTERNAL_FUNCTION_PARAMETERS)
{
  zend_bool value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "b", &value) == FAILURE) {
    return;
  }

  RETURN_BOOL(value);
}

static void
php_cassandra_counter_init(INTERNAL_FUNCTION_PARAMETERS)
{
  php_cassandra_bigint_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}

static void
php_cassandra_double_init(INTERNAL_FUNCTION_PARAMETERS)
{
  double value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "d", &value) == FAILURE) {
    return;
  }

  RETURN_DOUBLE(value);
}

static void
php_cassandra_int_init(INTERNAL_FUNCTION_PARAMETERS)
{
  long value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &value) == FAILURE) {
    return;
  }

  RETURN_LONG(value);
}

static void
php_cassandra_text_init(INTERNAL_FUNCTION_PARAMETERS)
{
  php_cassandra_varchar_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}

#define TYPE_INIT_METHOD(t) php_cassandra_ ## t ## _init

void
php_cassandra_scalar_init(INTERNAL_FUNCTION_PARAMETERS)
{
  cassandra_type *self = PHP_CASSANDRA_GET_TYPE(getThis());

#define XX_SCALAR(name, value) \
  if (self->type == value) { \
    TYPE_INIT_METHOD(name)(INTERNAL_FUNCTION_PARAM_PASSTHRU); \
  }
  PHP_CASSANDRA_SCALAR_TYPES_MAP(XX_SCALAR)
#undef XX_SCALAR
}
#undef TYPE_INIT_METHOD

#define TYPE_CODE(m) type_ ## m

php5to7_zval
php_cassandra_type_scalar(CassValueType type TSRMLS_DC)
{
  php5to7_zval result;
  PHP5TO7_ZVAL_UNDEF(result);

#define XX_SCALAR(name, value) \
  if (value == type) { \
    if (PHP5TO7_ZVAL_IS_UNDEF(CASSANDRA_G(TYPE_CODE(name)))) { \
      CASSANDRA_G(TYPE_CODE(name)) = php_cassandra_type_scalar_new(type TSRMLS_CC); \
    } \
    Z_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(CASSANDRA_G(TYPE_CODE(name)))); \
    return CASSANDRA_G(TYPE_CODE(name)); \
  }
  PHP_CASSANDRA_SCALAR_TYPES_MAP(XX_SCALAR)
#undef XX_SCALAR

  zend_throw_exception_ex(cassandra_invalid_argument_exception_ce,
                          0 TSRMLS_CC, "Invalid type");
  return result;
}
#undef TYPE_CODE

php5to7_zval
php_cassandra_type_map(zval *key_type,
                       zval *value_type TSRMLS_DC)
{
  php5to7_zval ztype;
  cassandra_type *map;
  cassandra_type *sub_type;

  PHP5TO7_ZVAL_MAYBE_MAKE(ztype);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(ztype), cassandra_type_map_ce);
  map = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));

  if (!PHP5TO7_ZVAL_IS_UNDEF_P(key_type)) {
    sub_type = PHP_CASSANDRA_GET_TYPE(key_type);
    cass_data_type_add_sub_type(map->data_type, sub_type->data_type);
  }

  if (!PHP5TO7_ZVAL_IS_UNDEF_P(value_type)) {
    sub_type = PHP_CASSANDRA_GET_TYPE(value_type);
    cass_data_type_add_sub_type(map->data_type, sub_type->data_type);
  }

#if PHP_MAJOR_VERSION >= 7
  map->key_type = *key_type;
  map->value_type = *value_type;
#else
  map->key_type = key_type;
  map->value_type = value_type;
#endif

  return ztype;
}

php5to7_zval
php_cassandra_type_map_from_value_types(CassValueType key_type,
                                        CassValueType value_type TSRMLS_DC)
{
  php5to7_zval ztype;
  cassandra_type *map;
  cassandra_type *sub_type;

  PHP5TO7_ZVAL_MAYBE_MAKE(ztype);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(ztype), cassandra_type_map_ce);
  map = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));
  map->key_type   = php_cassandra_type_scalar(key_type TSRMLS_CC);
  map->value_type = php_cassandra_type_scalar(value_type TSRMLS_CC);

  sub_type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(map->key_type));
  cass_data_type_add_sub_type(map->data_type, sub_type->data_type);
  sub_type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(map->value_type));
  cass_data_type_add_sub_type(map->data_type, sub_type->data_type);

  return ztype;
}

php5to7_zval
php_cassandra_type_set(zval *value_type TSRMLS_DC)
{
  php5to7_zval ztype;
  cassandra_type *set;
  cassandra_type *sub_type;

  PHP5TO7_ZVAL_MAYBE_MAKE(ztype);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(ztype), cassandra_type_set_ce);
  set = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));

  if (!PHP5TO7_ZVAL_IS_UNDEF_P(value_type)) {
    sub_type = PHP_CASSANDRA_GET_TYPE(value_type);
    cass_data_type_add_sub_type(set->data_type, sub_type->data_type);
  }

#if PHP_MAJOR_VERSION >= 7
  set->value_type = *value_type;
#else
  set->value_type = value_type;
#endif

  return ztype;
}

php5to7_zval
php_cassandra_type_set_from_value_type(CassValueType type TSRMLS_DC)
{
  php5to7_zval ztype;
  cassandra_type *set;
  cassandra_type *sub_type;

  PHP5TO7_ZVAL_MAYBE_MAKE(ztype);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(ztype), cassandra_type_set_ce);
  set = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));
  set->value_type = php_cassandra_type_scalar(type TSRMLS_CC);

  sub_type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(set->value_type));
  cass_data_type_add_sub_type(set->data_type, sub_type->data_type);

  return ztype;
}

php5to7_zval
php_cassandra_type_collection(zval *value_type TSRMLS_DC)
{
  php5to7_zval ztype;
  cassandra_type *collection;
  cassandra_type *sub_type;

  PHP5TO7_ZVAL_MAYBE_MAKE(ztype);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(ztype), cassandra_type_collection_ce);
  collection = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));

  if (!PHP5TO7_ZVAL_IS_UNDEF_P(value_type)) {
    sub_type = PHP_CASSANDRA_GET_TYPE(value_type);
    cass_data_type_add_sub_type(collection->data_type, sub_type->data_type);
  }

#if PHP_MAJOR_VERSION >= 7
  collection->value_type = *value_type;
#else
  collection->value_type = value_type;
#endif

  return ztype;
}

php5to7_zval
php_cassandra_type_collection_from_value_type(CassValueType type TSRMLS_DC)
{
  php5to7_zval ztype;
  cassandra_type *collection;
  cassandra_type *sub_type;

  PHP5TO7_ZVAL_MAYBE_MAKE(ztype);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(ztype), cassandra_type_collection_ce);
  collection = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));
  collection->value_type = php_cassandra_type_scalar(type TSRMLS_CC);

  sub_type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(collection->value_type));
  cass_data_type_add_sub_type(collection->data_type, sub_type->data_type);

  return ztype;
}

php5to7_zval
php_cassandra_type_tuple(TSRMLS_D)
{
  php5to7_zval ztype;

  PHP5TO7_ZVAL_MAYBE_MAKE(ztype);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(ztype), cassandra_type_tuple_ce);

  return ztype;
}

php5to7_zval
php_cassandra_type_user_type(TSRMLS_D)
{
  php5to7_zval ztype;
  cassandra_type *user_type;

  PHP5TO7_ZVAL_MAYBE_MAKE(ztype);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(ztype), cassandra_type_user_type_ce);
  user_type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));
  user_type->data_type = cass_data_type_new(CASS_VALUE_TYPE_UDT);

  return ztype;
}

php5to7_zval
php_cassandra_type_custom(char *name TSRMLS_DC)
{
  php5to7_zval ztype;
  cassandra_type *custom;

  PHP5TO7_ZVAL_MAYBE_MAKE(ztype);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(ztype), cassandra_type_custom_ce);
  custom = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));
  custom->name = name;

  return ztype;
}

#define EXPECTING_TOKEN(expected) \
  zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC, \
    "Unexpected %s at position %d in string \"%s\", expected " expected, \
    describe_token(token), ((int) (str - validator) - 1), validator \
  ); \
  return FAILURE;

enum token_type {
  TOKEN_ILLEGAL = 0,
  TOKEN_PAREN_OPEN,
  TOKEN_PAREN_CLOSE,
  TOKEN_COMMA,
  TOKEN_COLON,
  TOKEN_NAME,
  TOKEN_END
};

enum parser_state {
  STATE_CLASS = 0,
  STATE_AFTER_CLASS,
  STATE_AFTER_PARENS,
  STATE_END
};

static const char *
describe_token(enum token_type token)
{
  switch (token) {
  case TOKEN_ILLEGAL:     return "illegal character";      break;
  case TOKEN_PAREN_OPEN:  return "opening parenthesis";    break;
  case TOKEN_PAREN_CLOSE: return "closing parenthesis";    break;
  case TOKEN_COMMA:       return "comma";                  break;
  case TOKEN_COLON:       return "colon";                  break;
  case TOKEN_NAME:        return "alphanumeric character"; break;
  case TOKEN_END:         return "end of string";          break;
  default:                return "unknown token";
  }
}

static int
isletter(char ch)
{
  return isalnum(ch) || ch == '.';
}

static enum token_type
next_token(const char  *str,       size_t  len,
           const char **token_str, size_t *token_len,
           const char **str_out,   size_t *len_out)
{
  enum token_type type;
  unsigned int i = 0;
  char         c = str[i];

  if (len == 0) {
    return TOKEN_END;
  }

  if (isalnum(c)) {
    type = TOKEN_NAME;
    while (i < len) {
      if (!isletter(str[i])) {
        break;
      }
      i++;
    }
  } else {
    switch (c) {
      case '\0':
        type = TOKEN_END;
        break;
      case '(':
        type = TOKEN_PAREN_OPEN;
        i++;
        break;
      case ')':
        type = TOKEN_PAREN_CLOSE;
        i++;
        break;
      case ',':
        type = TOKEN_COMMA;
        i++;
        break;
      case ':':
        type = TOKEN_COLON;
        i++;
        break;
      default:
        type = TOKEN_ILLEGAL;
    }
  }

  *token_str = &(str[0]);
  *token_len = i;
  *str_out   = &(str[i]);
  *len_out   = len - i;

  return type;
}

static struct node_s *
php_cassandra_parse_node_new()
{
  struct node_s *node;
  node = emalloc(sizeof(struct node_s));
  node->parent        = NULL;
  node->name          = NULL;
  node->name_length   = 0;
  node->first_child   = NULL;
  node->last_child    = NULL;
  node->next_sibling  = NULL;
  node->prev_sibling  = NULL;

  return node;
}

static void
php_cassandra_parse_node_free(struct node_s *node)
{
  if (node->first_child) {
    php_cassandra_parse_node_free(node->first_child);
    node->first_child = NULL;
  }
  node->last_child = NULL;

  if (node->next_sibling) {
    php_cassandra_parse_node_free(node->next_sibling);
    node->next_sibling = NULL;
  }

  efree(node);
}

static int
php_cassandra_parse_class_name(const char     *validator,
                               size_t          validator_len,
                               struct node_s **result TSRMLS_DC)
{
  const char       *str;
  size_t            len;
  const char       *token_str;
  size_t            token_len;
  enum parser_state state;
  enum token_type   token;
  struct node_s    *root;
  struct node_s    *node;
  struct node_s    *child;

  token_str = NULL;
  token_len = 0;
  state     = STATE_CLASS;
  str       = validator;
  len       = validator_len;
  root      = php_cassandra_parse_node_new();
  node      = root;

  while (1) {
    token = next_token(str, len, &token_str, &token_len, &str, &len);

    if (token == TOKEN_ILLEGAL) {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
        "Illegal character \"%c\" at position %d in \"%s\"",
        *token_str, ((int) (str - validator) - 1), validator);
      php_cassandra_parse_node_free(root);
      return FAILURE;
    }

    if (state == STATE_AFTER_PARENS) {
      if (token == TOKEN_COMMA) {
        if (node->parent == NULL) {
          EXPECTING_TOKEN("end of string");
        }
        state = STATE_CLASS;

        child = php_cassandra_parse_node_new();
        child->parent        = node->parent;
        child->prev_sibling  = node;
        node->next_sibling   = child;
        node->parent->last_child = child;

        node = child;
        continue;
      } else if (token == TOKEN_PAREN_CLOSE) {
        if (node->parent == NULL) {
          EXPECTING_TOKEN("end of string");
        }

        node = node->parent;
        continue;
      } else if (token == TOKEN_END) {
        break;
      } else {
        EXPECTING_TOKEN("a comma, a closing parenthesis or an end of string");
      }
    }

    if (state == STATE_AFTER_CLASS) {
      if (token == TOKEN_PAREN_OPEN) {
        state = STATE_CLASS;

        child = php_cassandra_parse_node_new();
        child->parent = node;

        if (node->first_child == NULL) {
          node->first_child = child;
        }

        if (node->last_child) {
          node->last_child->next_sibling = child;
        }

        child->prev_sibling = node->last_child;
        node->last_child = child;

        node = child;
        continue;
      } else if (token == TOKEN_COMMA || token == TOKEN_COLON) {
        state = STATE_CLASS;

        child = php_cassandra_parse_node_new();
        child->parent        = node->parent;
        child->prev_sibling  = node;
        node->next_sibling   = child;
        node->parent->last_child = child;

        node = child;
        continue;
      } else if (token == TOKEN_PAREN_CLOSE) {
        state = STATE_AFTER_PARENS;

        node = node->parent;
        continue;
      } else if (token == TOKEN_END) {
        break;
      } else {
        php_cassandra_parse_node_free(root);
        EXPECTING_TOKEN("opening/closing parenthesis or comma");
      }
    }

    if (state == STATE_CLASS) {
      if (token != TOKEN_NAME) {
        php_cassandra_parse_node_free(root);
        EXPECTING_TOKEN("fully qualified class name");
      }
      state = STATE_AFTER_CLASS;

      node->name        = token_str;
      node->name_length = token_len;
    }
  }

  *result = root;
  return SUCCESS;
}

static CassValueType
php_cassandra_lookup_type(struct node_s *node TSRMLS_DC)
{
  if (strncmp("org.apache.cassandra.db.marshal.AsciiType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_ASCII;
  }

  if (strncmp("org.apache.cassandra.db.marshal.LongType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_BIGINT;
  }

  if (strncmp("org.apache.cassandra.db.marshal.ShortType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_SMALL_INT;
  }

  if (strncmp("org.apache.cassandra.db.marshal.ByteType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_TINY_INT;
  }

  if (strncmp("org.apache.cassandra.db.marshal.BytesType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_BLOB;
  }

  if (strncmp("org.apache.cassandra.db.marshal.BooleanType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_BOOLEAN;
  }

  if (strncmp("org.apache.cassandra.db.marshal.CounterColumnType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_COUNTER;
  }

  if (strncmp("org.apache.cassandra.db.marshal.DecimalType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_DECIMAL;
  }

  if (strncmp("org.apache.cassandra.db.marshal.DoubleType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_DOUBLE;
  }

  if (strncmp("org.apache.cassandra.db.marshal.FloatType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_FLOAT;
  }

  if (strncmp("org.apache.cassandra.db.marshal.InetAddressType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_INET;
  }

  if (strncmp("org.apache.cassandra.db.marshal.Int32Type", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_INT;
  }

  if (strncmp("org.apache.cassandra.db.marshal.UTF8Type", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_VARCHAR;
  }

  if (strncmp("org.apache.cassandra.db.marshal.TimestampType", node->name, node->name_length) == 0 ||
      strncmp("org.apache.cassandra.db.marshal.DateType",      node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_TIMESTAMP;
  }

  if (strncmp("org.apache.cassandra.db.marshal.SimpleDateType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_DATE;
  }

  if (strncmp("org.apache.cassandra.db.marshal.TimeType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_TIME;
  }

  if (strncmp("org.apache.cassandra.db.marshal.UUIDType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_UUID;
  }

  if (strncmp("org.apache.cassandra.db.marshal.IntegerType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_VARINT;
  }

  if (strncmp("org.apache.cassandra.db.marshal.TimeUUIDType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_TIMEUUID;
  }

  if (strncmp("org.apache.cassandra.db.marshal.MapType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_MAP;
  }

  if (strncmp("org.apache.cassandra.db.marshal.SetType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_SET;
  }

  if (strncmp("org.apache.cassandra.db.marshal.ListType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_LIST;
  }

  if (strncmp("org.apache.cassandra.db.marshal.TupleType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_TUPLE;
  }

  if (strncmp("org.apache.cassandra.db.marshal.UserType", node->name, node->name_length) == 0) {
    return CASS_VALUE_TYPE_UDT;
  }

  return CASS_VALUE_TYPE_CUSTOM;
}

static void
php_cassandra_node_dump_to(struct node_s *node, smart_str *text)
{
  smart_str_appendl(text, node->name, node->name_length);

  if (node->first_child) {
    smart_str_appendl(text, "(", 1);
    php_cassandra_node_dump_to(node->first_child, text);
    smart_str_appendl(text, ")", 1);
  }

  if (node->next_sibling) {
    smart_str_appendl(text, ", ", 2);
    php_cassandra_node_dump_to(node->next_sibling, text);
  }
}

static char *
php_cassandra_node_dump(struct node_s *node)
{
  smart_str text = PHP5TO7_SMART_STR_INIT;

  php_cassandra_node_dump_to(node, &text);
  smart_str_0(&text);

  return PHP5TO7_SMART_STR_VAL(text);
}

static php5to7_zval
php_cassandra_create_type(struct node_s *node TSRMLS_DC)
{
  CassValueType type = CASS_VALUE_TYPE_UNKNOWN;

  /* Skip wrapper types */
  while (node &&
         (strncmp("org.apache.cassandra.db.marshal.FrozenType", node->name, node->name_length)    == 0 ||
          strncmp("org.apache.cassandra.db.marshal.ReversedType", node->name, node->name_length)  == 0 ||
          strncmp("org.apache.cassandra.db.marshal.CompositeType", node->name, node->name_length) == 0)) {
    node = node->first_child;
  }

  if (node) {
    type = php_cassandra_lookup_type(node TSRMLS_CC);
  }

  if (type == CASS_VALUE_TYPE_UNKNOWN) {
    php5to7_zval undef;
    PHP5TO7_ZVAL_UNDEF(undef);
    return undef;
  }

  if (type == CASS_VALUE_TYPE_CUSTOM) {
    return php_cassandra_type_custom(
          php_cassandra_node_dump(node) TSRMLS_CC);
  } else if (type == CASS_VALUE_TYPE_MAP) {
    php5to7_zval key_type;
    php5to7_zval value_type;

    if (node->first_child) {
      key_type = php_cassandra_create_type(node->first_child TSRMLS_CC);
      value_type = php_cassandra_create_type(node->first_child->next_sibling TSRMLS_CC);
    } else {
      PHP5TO7_ZVAL_UNDEF(key_type);
      PHP5TO7_ZVAL_UNDEF(value_type);
    }
    return php_cassandra_type_map(PHP5TO7_ZVAL_MAYBE_P(key_type),
                                  PHP5TO7_ZVAL_MAYBE_P(value_type) TSRMLS_CC);
  } else if (type == CASS_VALUE_TYPE_LIST) {
    php5to7_zval value_type;
    if (node->first_child) {
      value_type = php_cassandra_create_type(node->first_child TSRMLS_CC);
    } else {
      PHP5TO7_ZVAL_UNDEF(value_type);
    }
    return php_cassandra_type_collection(PHP5TO7_ZVAL_MAYBE_P(value_type) TSRMLS_CC);
  } else if (type == CASS_VALUE_TYPE_SET) {
    php5to7_zval value_type;
    if (node->first_child) {
      value_type = php_cassandra_create_type(node->first_child TSRMLS_CC);
    } else {
      PHP5TO7_ZVAL_UNDEF(value_type);
    }
    return php_cassandra_type_set(PHP5TO7_ZVAL_MAYBE_P(value_type) TSRMLS_CC);
  } else if (type == CASS_VALUE_TYPE_TUPLE) {
    return php_cassandra_tuple_from_node(node TSRMLS_CC);
  } else if (type == CASS_VALUE_TYPE_UDT) {
    return php_cassandra_user_type_from_node(node TSRMLS_CC);
  }

  return php_cassandra_type_scalar(type TSRMLS_CC);
}

int
php_cassandra_parse_column_type(const char   *validator,
                                size_t        validator_len,
                                int          *reversed_out,
                                int          *frozen_out,
                                php5to7_zval *type_out TSRMLS_DC)
{
  struct node_s *root;
  struct node_s *node  = NULL;
  cass_bool_t reversed = 0;
  cass_bool_t frozen   = 0;

  if (php_cassandra_parse_class_name(validator, validator_len, &root TSRMLS_CC) == FAILURE) {
    return FAILURE;
  }

  node = root;

  while (node) {
    if (strncmp("org.apache.cassandra.db.marshal.ReversedType", node->name, node->name_length) == 0) {
      reversed = 1;
      node     = node->first_child;
      continue;
    }

    if (strncmp("org.apache.cassandra.db.marshal.FrozenType", node->name, node->name_length) == 0) {
      frozen = 1;
      node   = node->first_child;
      continue;
    }

    if (strncmp("org.apache.cassandra.db.marshal.CompositeType", node->name, node->name_length) == 0) {
      node = node->first_child;
      continue;
    }

    break;
  }

  if (node == NULL) {
    php_cassandra_parse_node_free(root);
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
      "Invalid type");
    return FAILURE;
  }

  *reversed_out = reversed;
  *frozen_out   = frozen;
  *type_out     = php_cassandra_create_type(node TSRMLS_CC);

  php_cassandra_parse_node_free(root);

  return SUCCESS;
}
