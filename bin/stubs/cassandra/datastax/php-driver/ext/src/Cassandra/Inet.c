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
#include "util/inet.h"
#include "util/types.h"

zend_class_entry *cassandra_inet_ce = NULL;

void
php_cassandra_inet_init(INTERNAL_FUNCTION_PARAMETERS)
{
  cassandra_inet *self;
  char *string;
  php5to7_size string_len;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &string, &string_len) == FAILURE) {
    return;
  }

  if (getThis() && instanceof_function(Z_OBJCE_P(getThis()), cassandra_inet_ce TSRMLS_CC)) {
    self = PHP_CASSANDRA_GET_INET(getThis());
  } else {
    object_init_ex(return_value, cassandra_inet_ce);
    self = PHP_CASSANDRA_GET_INET(return_value);
  }

  if (!php_cassandra_parse_ip_address(string, &self->inet TSRMLS_CC)) {
    return;
  }
}

/* {{{ Cassandra\Inet::__construct(string) */
PHP_METHOD(Inet, __construct)
{
  php_cassandra_inet_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}
/* }}} */

/* {{{ Cassandra\Inet::__toString() */
PHP_METHOD(Inet, __toString)
{
  cassandra_inet *inet = PHP_CASSANDRA_GET_INET(getThis());
  char *string;
  php_cassandra_format_address(inet->inet, &string);

  PHP5TO7_RETVAL_STRING(string);
  efree(string);
}
/* }}} */

/* {{{ Cassandra\Inet::type() */
PHP_METHOD(Inet, type)
{
  php5to7_zval type = php_cassandra_type_scalar(CASS_VALUE_TYPE_INET TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(type), 1, 1);
}
/* }}} */

/* {{{ Cassandra\Inet::address() */
PHP_METHOD(Inet, address)
{
  cassandra_inet *inet = PHP_CASSANDRA_GET_INET(getThis());
  char *string;
  php_cassandra_format_address(inet->inet, &string);

  PHP5TO7_RETVAL_STRING(string);
  efree(string);
}
/* }}} */

ZEND_BEGIN_ARG_INFO_EX(arginfo__construct, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, address)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_inet_methods[] = {
  PHP_ME(Inet, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Inet, __toString, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Inet, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Inet, address, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_inet_handlers;

static HashTable *
php_cassandra_inet_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_inet_properties(zval *object TSRMLS_DC)
{
  char *string;
  php5to7_zval type;
  php5to7_zval address;

  cassandra_inet *self = PHP_CASSANDRA_GET_INET(object);
  HashTable      *props = zend_std_get_properties(object TSRMLS_CC);

  type = php_cassandra_type_scalar(CASS_VALUE_TYPE_INET TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "type", sizeof("type"), PHP5TO7_ZVAL_MAYBE_P(type), sizeof(zval));

  php_cassandra_format_address(self->inet, &string);
  PHP5TO7_ZVAL_MAYBE_MAKE(address);
  PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(address), string);
  efree(string);
  PHP5TO7_ZEND_HASH_UPDATE(props, "address", sizeof("address"), PHP5TO7_ZVAL_MAYBE_P(address), sizeof(zval));

  return props;
}

static int
php_cassandra_inet_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_inet *inet1 = NULL;
  cassandra_inet *inet2 = NULL;

  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  inet1 = PHP_CASSANDRA_GET_INET(obj1);
  inet2 = PHP_CASSANDRA_GET_INET(obj2);

  if (inet1->inet.address_length != inet2->inet.address_length) {
   return inet1->inet.address_length < inet2->inet.address_length ? -1 : 1;
  }
  return memcmp(inet1->inet.address, inet2->inet.address, inet1->inet.address_length);
}

static unsigned
php_cassandra_inet_hash_value(zval *obj TSRMLS_DC)
{
  cassandra_inet *self = PHP_CASSANDRA_GET_INET(obj);
  return zend_inline_hash_func((const char *) self->inet.address,
                               self->inet.address_length);
}

static void
php_cassandra_inet_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_inet *self = PHP5TO7_ZEND_OBJECT_GET(inet, object);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_inet_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_inet *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(inet, ce);

  PHP5TO7_ZEND_OBJECT_INIT(inet, self, ce);
}

void cassandra_define_Inet(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Inet", cassandra_inet_methods);
  cassandra_inet_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_inet_ce TSRMLS_CC, 1, cassandra_value_ce);
  memcpy(&cassandra_inet_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_inet_handlers.std.get_properties  = php_cassandra_inet_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_inet_handlers.std.get_gc          = php_cassandra_inet_gc;
#endif
  cassandra_inet_handlers.std.compare_objects = php_cassandra_inet_compare;
  cassandra_inet_ce->ce_flags |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_inet_ce->create_object = php_cassandra_inet_new;

  cassandra_inet_handlers.hash_value = php_cassandra_inet_hash_value;
  cassandra_inet_handlers.std.clone_obj = NULL;
}
