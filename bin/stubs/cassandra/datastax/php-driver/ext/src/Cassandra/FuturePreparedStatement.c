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

#include "util/future.h"

zend_class_entry *cassandra_future_prepared_statement_ce = NULL;

ZEND_EXTERN_MODULE_GLOBALS(cassandra)

PHP_METHOD(FuturePreparedStatement, get)
{
  zval *timeout = NULL;
  cassandra_statement *prepared_statement = NULL;

  cassandra_future_prepared_statement *self = PHP_CASSANDRA_GET_FUTURE_PREPARED_STATEMENT(getThis());

  if (!PHP5TO7_ZVAL_IS_UNDEF(self->prepared_statement)) {
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->prepared_statement), 1, 0);
  }

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|z", &timeout) == FAILURE) {
    return;
  }

  if (php_cassandra_future_wait_timed(self->future, timeout TSRMLS_CC) == FAILURE) {
    return;
  }

  if (php_cassandra_future_is_error(self->future TSRMLS_CC) == FAILURE) {
    return;
  }

  object_init_ex(return_value, cassandra_statement_ce);
  PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(self->prepared_statement), return_value);

  prepared_statement = PHP_CASSANDRA_GET_STATEMENT(return_value);

  prepared_statement->prepared = cass_future_get_prepared(self->future);
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_timeout, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, timeout)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_future_prepared_statement_methods[] = {
  PHP_ME(FuturePreparedStatement, get, arginfo_timeout, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_future_prepared_statement_handlers;

static HashTable *
php_cassandra_future_prepared_statement_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_future_prepared_statement_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_future_prepared_statement_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_future_prepared_statement *self =
      PHP5TO7_ZEND_OBJECT_GET(future_prepared_statement, object);

  if (self->future) {
    cass_future_free(self->future);
    self->future = NULL;
  }

  PHP5TO7_ZVAL_MAYBE_DESTROY(self->prepared_statement);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_future_prepared_statement_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_future_prepared_statement *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(future_prepared_statement, ce);

  self->future = NULL;
  PHP5TO7_ZVAL_UNDEF(self->prepared_statement);

  PHP5TO7_ZEND_OBJECT_INIT(future_prepared_statement, self, ce);
}

void cassandra_define_FuturePreparedStatement(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\FuturePreparedStatement", cassandra_future_prepared_statement_methods);
  cassandra_future_prepared_statement_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_future_prepared_statement_ce TSRMLS_CC, 1, cassandra_future_ce);
  cassandra_future_prepared_statement_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_future_prepared_statement_ce->create_object = php_cassandra_future_prepared_statement_new;

  memcpy(&cassandra_future_prepared_statement_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_future_prepared_statement_handlers.get_properties  = php_cassandra_future_prepared_statement_properties;
  cassandra_future_prepared_statement_handlers.compare_objects = php_cassandra_future_prepared_statement_compare;
  cassandra_future_prepared_statement_handlers.clone_obj = NULL;
}
