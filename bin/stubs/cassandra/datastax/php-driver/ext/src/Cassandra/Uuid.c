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
#include "util/hash.h"
#include "util/types.h"
#include "util/uuid_gen.h"

zend_class_entry *cassandra_uuid_ce = NULL;

void
php_cassandra_uuid_init(INTERNAL_FUNCTION_PARAMETERS)
{
  char *value;
  php5to7_size value_len;
  cassandra_uuid *self;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|s", &value, &value_len) == FAILURE) {
    return;
  }

  if (getThis() && instanceof_function(Z_OBJCE_P(getThis()), cassandra_uuid_ce TSRMLS_CC)) {
    self = PHP_CASSANDRA_GET_UUID(getThis());
  } else {
    object_init_ex(return_value, cassandra_uuid_ce);
    self = PHP_CASSANDRA_GET_UUID(return_value);
  }

  if (ZEND_NUM_ARGS() == 0) {
    php_cassandra_uuid_generate_random(&self->uuid TSRMLS_CC);
  } else {
    if (cass_uuid_from_string(value, &self->uuid) != CASS_OK) {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                              "Invalid UUID: '%.*s'", value_len, value);
      return;
    }
  }
}

/* {{{ Cassandra\Uuid::__construct(string) */
PHP_METHOD(Uuid, __construct)
{
  php_cassandra_uuid_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}
/* }}} */

/* {{{ Cassandra\Uuid::__toString() */
PHP_METHOD(Uuid, __toString)
{
  char string[CASS_UUID_STRING_LENGTH];
  cassandra_uuid *self = PHP_CASSANDRA_GET_UUID(getThis());

  cass_uuid_string(self->uuid, string);

  PHP5TO7_RETVAL_STRING(string);
}
/* }}} */

/* {{{ Cassandra\Uuid::type() */
PHP_METHOD(Uuid, type)
{
  php5to7_zval type = php_cassandra_type_scalar(CASS_VALUE_TYPE_UUID TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(type), 1, 1);
}
/* }}} */

/* {{{ Cassandra\Uuid::value() */
PHP_METHOD(Uuid, uuid)
{
  char string[CASS_UUID_STRING_LENGTH];
  cassandra_uuid *self = PHP_CASSANDRA_GET_UUID(getThis());

  cass_uuid_string(self->uuid, string);

  PHP5TO7_RETVAL_STRING(string);
}
/* }}} */

/* {{{ Cassandra\Uuid::version() */
PHP_METHOD(Uuid, version)
{
  cassandra_uuid *self = PHP_CASSANDRA_GET_UUID(getThis());

  RETURN_LONG((long) cass_uuid_version(self->uuid));
}
/* }}} */

ZEND_BEGIN_ARG_INFO_EX(arginfo__construct, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, uuid)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_uuid_methods[] = {
  PHP_ME(Uuid, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Uuid, __toString, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Uuid, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Uuid, uuid, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Uuid, version, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_uuid_handlers;

static HashTable *
php_cassandra_uuid_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_uuid_properties(zval *object TSRMLS_DC)
{
  char string[CASS_UUID_STRING_LENGTH];
  php5to7_zval type;
  php5to7_zval uuid;
  php5to7_zval version;

  cassandra_uuid *self = PHP_CASSANDRA_GET_UUID(object);
  HashTable      *props = zend_std_get_properties(object TSRMLS_CC);

  cass_uuid_string(self->uuid, string);

  type = php_cassandra_type_scalar(CASS_VALUE_TYPE_UUID TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "type", sizeof("type"), PHP5TO7_ZVAL_MAYBE_P(type), sizeof(zval));

  PHP5TO7_ZVAL_MAYBE_MAKE(uuid);
  PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(uuid), string);
  PHP5TO7_ZEND_HASH_UPDATE(props, "uuid", sizeof("uuid"), PHP5TO7_ZVAL_MAYBE_P(uuid), sizeof(zval));

  PHP5TO7_ZVAL_MAYBE_MAKE(version);
  ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(version), (long) cass_uuid_version(self->uuid));
  PHP5TO7_ZEND_HASH_UPDATE(props, "version", sizeof("version"), PHP5TO7_ZVAL_MAYBE_P(version), sizeof(zval));

  return props;
}

static int
php_cassandra_uuid_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_uuid *uuid1 = NULL;
  cassandra_uuid *uuid2 = NULL;

  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  uuid1 = PHP_CASSANDRA_GET_UUID(obj1);
  uuid2 = PHP_CASSANDRA_GET_UUID(obj2);

  if (uuid1->uuid.time_and_version != uuid2->uuid.time_and_version)
    return uuid1->uuid.time_and_version < uuid2->uuid.time_and_version ? -1 : 1;
  if (uuid1->uuid.clock_seq_and_node != uuid2->uuid.clock_seq_and_node)
    return uuid1->uuid.clock_seq_and_node < uuid2->uuid.clock_seq_and_node ? -1 : 1;

  return 0;
}

static unsigned
php_cassandra_uuid_hash_value(zval *obj TSRMLS_DC)
{
  cassandra_uuid *self = PHP_CASSANDRA_GET_UUID(obj);
  return php_cassandra_combine_hash(php_cassandra_bigint_hash(self->uuid.time_and_version),
                                    php_cassandra_bigint_hash(self->uuid.clock_seq_and_node));

}

static void
php_cassandra_uuid_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_uuid *self = PHP5TO7_ZEND_OBJECT_GET(uuid, object);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_uuid_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_uuid *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(uuid, ce);

  PHP5TO7_ZEND_OBJECT_INIT(uuid, self, ce);
}

void
cassandra_define_Uuid(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Uuid", cassandra_uuid_methods);
  cassandra_uuid_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_uuid_ce TSRMLS_CC, 2, cassandra_value_ce, cassandra_uuid_interface_ce);
  memcpy(&cassandra_uuid_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_uuid_handlers.std.get_properties  = php_cassandra_uuid_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_uuid_handlers.std.get_gc          = php_cassandra_uuid_gc;
#endif
  cassandra_uuid_handlers.std.compare_objects = php_cassandra_uuid_compare;
  cassandra_uuid_ce->ce_flags |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_uuid_ce->create_object = php_cassandra_uuid_new;

  cassandra_uuid_handlers.hash_value = php_cassandra_uuid_hash_value;
  cassandra_uuid_handlers.std.clone_obj = NULL;
}
