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

zend_class_entry *cassandra_future_value_ce = NULL;

ZEND_EXTERN_MODULE_GLOBALS(cassandra)

PHP_METHOD(FutureValue, get)
{
  zval *timeout = NULL;
  cassandra_future_value *self = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|z", &timeout) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_FUTURE_VALUE(getThis());

  if (!PHP5TO7_ZVAL_IS_UNDEF(self->value)) {
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->value), 1, 0);
  }
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_timeout, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, timeout)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_future_value_methods[] = {
  PHP_ME(FutureValue, get, arginfo_timeout, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_future_value_handlers;

static HashTable *
php_cassandra_future_value_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_future_value_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_future_value_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_future_value *self =
      PHP5TO7_ZEND_OBJECT_GET(future_value, object);

  PHP5TO7_ZVAL_MAYBE_DESTROY(self->value);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_future_value_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_future_value *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(future_value, ce);

  PHP5TO7_ZVAL_UNDEF(self->value);

  PHP5TO7_ZEND_OBJECT_INIT(future_value, self, ce);
}

void cassandra_define_FutureValue(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\FutureValue", cassandra_future_value_methods);
  cassandra_future_value_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_future_value_ce TSRMLS_CC, 1, cassandra_future_ce);
  cassandra_future_value_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_future_value_ce->create_object = php_cassandra_future_value_new;

  memcpy(&cassandra_future_value_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_future_value_handlers.get_properties  = php_cassandra_future_value_properties;
  cassandra_future_value_handlers.compare_objects = php_cassandra_future_value_compare;
  cassandra_future_value_handlers.clone_obj = NULL;
}
