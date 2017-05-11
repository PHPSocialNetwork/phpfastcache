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
#include "src/Cassandra/Map.h"

zend_class_entry *cassandra_type_map_ce = NULL;

PHP_METHOD(TypeMap, __construct)
{
  zend_throw_exception_ex(cassandra_logic_exception_ce, 0 TSRMLS_CC,
    "Instantiation of a Cassandra\\Type\\Map type is not supported."
  );
  return;
}

PHP_METHOD(TypeMap, name)
{
  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  PHP5TO7_RETVAL_STRING("map");
}

PHP_METHOD(TypeMap, keyType)
{
  cassandra_type *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->key_type), 1, 0);
}

PHP_METHOD(TypeMap, valueType)
{
  cassandra_type *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->value_type), 1, 0);
}

PHP_METHOD(TypeMap, __toString)
{
  cassandra_type *self;
  smart_str string = PHP5TO7_SMART_STR_INIT;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());

  php_cassandra_type_string(self, &string TSRMLS_CC);
  smart_str_0(&string);

  PHP5TO7_RETVAL_STRING(PHP5TO7_SMART_STR_VAL(string));
  smart_str_free(&string);
}

PHP_METHOD(TypeMap, create)
{
  cassandra_map *map;
  php5to7_zval_args args = NULL;
  int argc = 0, i;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "*",
                            &args, &argc) == FAILURE) {
    return;
  }

  if (argc % 2 == 1) {
    PHP5TO7_MAYBE_EFREE(args);
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                            "Not enough values, maps can only be created " \
                            "from an even number of values, where each odd " \
                            "value is a key and each even value is a value, " \
                            "e.g create(key, value, key, value, key, value)");
    return;
  }

  object_init_ex(return_value, cassandra_map_ce);
  map = PHP_CASSANDRA_GET_MAP(return_value);

  PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(map->type), getThis());

  if (argc > 0) {
    for (i = 0; i < argc; i += 2) {
      if (!php_cassandra_map_set(map,
                                 PHP5TO7_ZVAL_ARG(args[i]),
                                 PHP5TO7_ZVAL_ARG(args[i + 1]) TSRMLS_CC)) {
        PHP5TO7_MAYBE_EFREE(args);
        return;
      }
    }
    PHP5TO7_MAYBE_EFREE(args);
  }
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_value, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_type_map_methods[] = {
  PHP_ME(TypeMap, __construct, arginfo_none,  ZEND_ACC_PUBLIC)
  PHP_ME(TypeMap, name,        arginfo_none,  ZEND_ACC_PUBLIC)
  PHP_ME(TypeMap, keyType,     arginfo_none,  ZEND_ACC_PUBLIC)
  PHP_ME(TypeMap, valueType,   arginfo_none,  ZEND_ACC_PUBLIC)
  PHP_ME(TypeMap, __toString,  arginfo_none,  ZEND_ACC_PUBLIC)
  PHP_ME(TypeMap, create,      arginfo_value, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_type_map_handlers;

static HashTable *
php_cassandra_type_map_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_type_map_properties(zval *object TSRMLS_DC)
{
  cassandra_type *self  = PHP_CASSANDRA_GET_TYPE(object);
  HashTable      *props = zend_std_get_properties(object TSRMLS_CC);

  if (PHP5TO7_ZEND_HASH_UPDATE(props,
                               "keyType", sizeof("keyType"),
                               PHP5TO7_ZVAL_MAYBE_P(self->key_type), sizeof(zval))) {
    Z_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(self->key_type));
  }

  if (PHP5TO7_ZEND_HASH_UPDATE(props,
                               "valueType", sizeof("valueType"),
                               PHP5TO7_ZVAL_MAYBE_P(self->value_type), sizeof(zval))) {
    Z_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(self->value_type));
  }

  return props;
}

static int
php_cassandra_type_map_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_type* type1 = PHP_CASSANDRA_GET_TYPE(obj1);
  cassandra_type* type2 = PHP_CASSANDRA_GET_TYPE(obj2);

  return php_cassandra_type_compare(type1, type2 TSRMLS_CC);
}

static void
php_cassandra_type_map_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_type *self = PHP5TO7_ZEND_OBJECT_GET(type, object);

  if (self->data_type) cass_data_type_free(self->data_type);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->key_type);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->value_type);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_type_map_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_type *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(type, ce);

  self->type = CASS_VALUE_TYPE_MAP;
  self->data_type = cass_data_type_new(self->type);
  PHP5TO7_ZVAL_UNDEF(self->key_type);
  PHP5TO7_ZVAL_UNDEF(self->value_type);

  PHP5TO7_ZEND_OBJECT_INIT_EX(type, type_map, self, ce);
}

void cassandra_define_TypeMap(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Type\\Map", cassandra_type_map_methods);
  cassandra_type_map_ce = php5to7_zend_register_internal_class_ex(&ce, cassandra_type_ce);
  memcpy(&cassandra_type_map_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_type_map_handlers.get_properties  = php_cassandra_type_map_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_type_map_handlers.get_gc          = php_cassandra_type_map_gc;
#endif
  cassandra_type_map_handlers.compare_objects = php_cassandra_type_map_compare;
  cassandra_type_map_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_type_map_ce->create_object = php_cassandra_type_map_new;
}
