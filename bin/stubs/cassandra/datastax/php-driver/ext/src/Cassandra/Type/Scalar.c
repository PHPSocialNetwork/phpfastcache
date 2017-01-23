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

zend_class_entry *cassandra_type_scalar_ce = NULL;

PHP_METHOD(TypeScalar, __construct)
{
  zend_throw_exception_ex(cassandra_logic_exception_ce, 0 TSRMLS_CC,
    "Instantiation of a Cassandra\\Type\\Scalar objects directly is not " \
    "supported, call varchar(), text(), blob(), ascii(), bigint(), " \
    "smallint(), tinyint(), counter(), int(), varint(), boolean(), " \
    "decimal(), double(), float(), inet(), timestamp(), uuid(), timeuuid(), " \
    "map(), collection() or set() on Cassandra\\Type statically instead."
  );
  return;
}

PHP_METHOD(TypeScalar, name)
{
  cassandra_type *self;
  const char *name;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());
  name = php_cassandra_scalar_type_name(self->type TSRMLS_CC);
  PHP5TO7_RETVAL_STRING(name);
}

PHP_METHOD(TypeScalar, __toString)
{
  cassandra_type *self;
  const char *name;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());
  name = php_cassandra_scalar_type_name(self->type TSRMLS_CC);
  PHP5TO7_RETVAL_STRING(name);
}

PHP_METHOD(TypeScalar, create)
{
  php_cassandra_scalar_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_value, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_type_scalar_methods[] = {
  PHP_ME(TypeScalar, __construct, arginfo_none,  ZEND_ACC_PUBLIC)
  PHP_ME(TypeScalar, name,        arginfo_none,  ZEND_ACC_PUBLIC)
  PHP_ME(TypeScalar, __toString,  arginfo_none,  ZEND_ACC_PUBLIC)
  PHP_ME(TypeScalar, create,      arginfo_value, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_type_scalar_handlers;

static HashTable *
php_cassandra_type_scalar_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_type_scalar_properties(zval *object TSRMLS_DC)
{
  php5to7_zval name;

  cassandra_type *self  = PHP_CASSANDRA_GET_TYPE(object);
  HashTable      *props = zend_std_get_properties(object TSRMLS_CC);

  /* Used for comparison and 'text' is just an alias for 'varchar' */
  CassValueType type = self->type == CASS_VALUE_TYPE_TEXT
                     ? CASS_VALUE_TYPE_VARCHAR
                     : self->type;

  PHP5TO7_ZVAL_MAYBE_MAKE(name);
  PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(name),
                      php_cassandra_scalar_type_name(type TSRMLS_CC));
  PHP5TO7_ZEND_HASH_UPDATE(props,
                           "name", sizeof("name"),
                           PHP5TO7_ZVAL_MAYBE_P(name), sizeof(zval));
  return props;
}

static int
php_cassandra_type_scalar_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_type* type1 = PHP_CASSANDRA_GET_TYPE(obj1);
  cassandra_type* type2 = PHP_CASSANDRA_GET_TYPE(obj2);

  return php_cassandra_type_compare(type1, type2 TSRMLS_CC);
}

static void
php_cassandra_type_scalar_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_type *self = PHP5TO7_ZEND_OBJECT_GET(type, object);

  if (self->data_type) cass_data_type_free(self->data_type);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_type_scalar_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_type *self = PHP5TO7_ZEND_OBJECT_ECALLOC(type, ce);

  self->type = CASS_VALUE_TYPE_UNKNOWN;
  self->data_type = NULL;

  PHP5TO7_ZEND_OBJECT_INIT_EX(type, type_scalar, self, ce);
}

void cassandra_define_TypeScalar(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Type\\Scalar", cassandra_type_scalar_methods);
  cassandra_type_scalar_ce = php5to7_zend_register_internal_class_ex(&ce, cassandra_type_ce);
  memcpy(&cassandra_type_scalar_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_type_scalar_handlers.get_properties  = php_cassandra_type_scalar_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_type_scalar_handlers.get_gc          = php_cassandra_type_scalar_gc;
#endif
  cassandra_type_scalar_handlers.compare_objects = php_cassandra_type_scalar_compare;
  cassandra_type_scalar_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_type_scalar_ce->create_object = php_cassandra_type_scalar_new;
}
