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

zend_class_entry *cassandra_future_session_ce = NULL;

ZEND_EXTERN_MODULE_GLOBALS(cassandra)

PHP_METHOD(FutureSession, get)
{
  zval *timeout = NULL;
  cassandra_session *session = NULL;
  CassError rc = CASS_OK;

  cassandra_future_session *future = PHP_CASSANDRA_GET_FUTURE_SESSION(getThis());

  if (!PHP5TO7_ZVAL_IS_UNDEF(future->default_session)) {
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(future->default_session), 1, 0);
  }

  if (future->exception_message) {
    zend_throw_exception_ex(exception_class(future->exception_code),
      future->exception_code TSRMLS_CC, future->exception_message);
    return;
  }

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|z", &timeout) == FAILURE) {
    return;
  }

  if (php_cassandra_future_wait_timed(future->future, timeout TSRMLS_CC) == FAILURE) {
    return;
  }

  rc = cass_future_error_code(future->future);

  if (rc != CASS_OK) {
    const char *message;
    size_t message_len;
    cass_future_error_message(future->future, &message, &message_len);

    if (future->persist) {
      future->exception_message = estrndup(message, message_len);
      future->exception_code    = rc;

      if (PHP5TO7_ZEND_HASH_DEL(&EG(persistent_list), future->hash_key, future->hash_key_len + 1)) {
        future->session = NULL;
        future->future  = NULL;
      }

      zend_throw_exception_ex(exception_class(future->exception_code),
        future->exception_code TSRMLS_CC, future->exception_message);
      return;
    }

    zend_throw_exception_ex(exception_class(rc), rc TSRMLS_CC,
      "%.*s", (int) message_len, message);
    return;
  }

  object_init_ex(return_value, cassandra_default_session_ce);

  PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(future->default_session), return_value);
  session = PHP_CASSANDRA_GET_SESSION(return_value);
  session->session = future->session;
  session->persist = future->persist;
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_timeout, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, timeout)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_future_session_methods[] = {
  PHP_ME(FutureSession, get, arginfo_timeout, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_future_session_handlers;

static HashTable *
php_cassandra_future_session_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_future_session_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_future_session_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_future_session *self =
      PHP5TO7_ZEND_OBJECT_GET(future_session, object);

  if (self->persist) {
    efree(self->hash_key);
  } else {
    if (self->future) {
      cass_future_free(self->future);
    }
    if (self->session) {
      cass_session_free(self->session);
    }
  }

  if (self->exception_message)
    efree(self->exception_message);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_future_session_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_future_session *self
      = PHP5TO7_ZEND_OBJECT_ECALLOC(future_session, ce);

  self->session           = NULL;
  self->future            = NULL;
  self->exception_message = NULL;
  self->hash_key          = NULL;
  self->persist           = 0;

  PHP5TO7_ZEND_OBJECT_INIT(future_session, self, ce);
}

void cassandra_define_FutureSession(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\FutureSession", cassandra_future_session_methods);
  cassandra_future_session_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_future_session_ce TSRMLS_CC, 1, cassandra_future_ce);
  cassandra_future_session_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_future_session_ce->create_object = php_cassandra_future_session_new;

  memcpy(&cassandra_future_session_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_future_session_handlers.get_properties  = php_cassandra_future_session_properties;
  cassandra_future_session_handlers.compare_objects = php_cassandra_future_session_compare;
  cassandra_future_session_handlers.clone_obj = NULL;
}
