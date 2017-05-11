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

#include "DefaultFunction.h"

#include "php_cassandra.h"
#include "util/result.h"
#include "util/ref.h"
#include "util/types.h"

zend_class_entry *cassandra_default_aggregate_ce = NULL;

PHP_METHOD(DefaultAggregate, name)
{
  cassandra_aggregate *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_AGGREGATE(getThis());

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->signature), 1, 0);
}

PHP_METHOD(DefaultAggregate, simpleName)
{
  cassandra_aggregate *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_AGGREGATE(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->simple_name)) {
    const char *name;
    size_t name_length;
    cass_aggregate_meta_name(self->meta, &name, &name_length);
    PHP5TO7_ZVAL_MAYBE_MAKE(self->simple_name);
    PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_P(self->simple_name), name, name_length);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->simple_name), 1, 0);
}

PHP_METHOD(DefaultAggregate, argumentTypes)
{
  cassandra_aggregate *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_AGGREGATE(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->argument_types)) {
    size_t i, count = cass_aggregate_meta_argument_count(self->meta);
    PHP5TO7_ZVAL_MAYBE_MAKE(self->argument_types);
    array_init(PHP5TO7_ZVAL_MAYBE_P(self->argument_types));
    for (i = 0; i < count; ++i) {
      const CassDataType* data_type = cass_aggregate_meta_argument_type(self->meta, i);
      if (data_type) {
        php5to7_zval type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);
        if (!PHP5TO7_ZVAL_IS_UNDEF(type)) {
          add_next_index_zval(PHP5TO7_ZVAL_MAYBE_P(self->argument_types),
                              PHP5TO7_ZVAL_MAYBE_P(type));
        }
      }
    }
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->argument_types), 1, 0);
}

PHP_METHOD(DefaultAggregate, stateFunction)
{
  cassandra_aggregate *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_AGGREGATE(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->state_function)) {
    const CassFunctionMeta* function = cass_aggregate_meta_state_func(self->meta);
    if (!function) {
      return;
    }
    self->state_function =
        php_cassandra_create_function(self->schema, function TSRMLS_CC);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->state_function), 1, 0);
}

PHP_METHOD(DefaultAggregate, finalFunction)
{
  cassandra_aggregate *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_AGGREGATE(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->final_function)) {
    const CassFunctionMeta* function = cass_aggregate_meta_final_func(self->meta);
    if (!function) {
      return;
    }
    self->final_function =
        php_cassandra_create_function(self->schema, function TSRMLS_CC);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->final_function), 1, 0);
}

PHP_METHOD(DefaultAggregate, initialCondition)
{
  cassandra_aggregate *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_AGGREGATE(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->initial_condition)) {
    const CassValue *value = cass_aggregate_meta_init_cond(self->meta);
    const CassDataType *data_type = NULL;
    if (!value) {
      return;
    }
    data_type = cass_value_data_type(value);
    if (!data_type) {
      return;
    }
    php_cassandra_value(value, data_type, &self->initial_condition TSRMLS_CC);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->initial_condition), 1, 0);
}

PHP_METHOD(DefaultAggregate, stateType)
{
  cassandra_aggregate *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_AGGREGATE(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->state_type)) {
    const CassDataType* data_type = cass_aggregate_meta_state_type(self->meta);
    if (!data_type) {
      return;
    }
    self->state_type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->state_type), 1, 0);
}

PHP_METHOD(DefaultAggregate, returnType)
{
  cassandra_aggregate *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_AGGREGATE(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->return_type)) {
    const CassDataType* data_type = cass_aggregate_meta_return_type(self->meta);
    if (!data_type) {
      return;
    }
    self->return_type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->return_type), 1, 0);
}

PHP_METHOD(DefaultAggregate, signature)
{
  cassandra_aggregate *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_AGGREGATE(getThis());
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->signature), 1, 0);
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_default_aggregate_methods[] = {
  PHP_ME(DefaultAggregate, name, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultAggregate, simpleName, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultAggregate, argumentTypes, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultAggregate, stateFunction, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultAggregate, finalFunction, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultAggregate, initialCondition, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultAggregate, stateType, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultAggregate, returnType, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultAggregate, signature, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_default_aggregate_handlers;

static HashTable *
php_cassandra_type_default_aggregate_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_default_aggregate_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_default_aggregate_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_default_aggregate_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_aggregate *self = PHP5TO7_ZEND_OBJECT_GET(aggregate, object);

  PHP5TO7_ZVAL_MAYBE_DESTROY(self->simple_name);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->argument_types);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->state_function);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->final_function);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->initial_condition);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->state_type);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->return_type);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->signature);

  if (self->schema) {
    php_cassandra_del_ref(&self->schema);
    self->schema = NULL;
  }
  self->meta = NULL;

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_default_aggregate_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_aggregate *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(aggregate, ce);

  PHP5TO7_ZVAL_UNDEF(self->simple_name);
  PHP5TO7_ZVAL_UNDEF(self->argument_types);
  PHP5TO7_ZVAL_UNDEF(self->state_function);
  PHP5TO7_ZVAL_UNDEF(self->final_function);
  PHP5TO7_ZVAL_UNDEF(self->initial_condition);
  PHP5TO7_ZVAL_UNDEF(self->state_type);
  PHP5TO7_ZVAL_UNDEF(self->return_type);
  PHP5TO7_ZVAL_UNDEF(self->signature);

  self->schema = NULL;
  self->meta = NULL;

  PHP5TO7_ZEND_OBJECT_INIT_EX(aggregate, default_aggregate, self, ce);
}

void cassandra_define_DefaultAggregate(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\DefaultAggregate", cassandra_default_aggregate_methods);
  cassandra_default_aggregate_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_default_aggregate_ce TSRMLS_CC, 1, cassandra_aggregate_ce);
  cassandra_default_aggregate_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_default_aggregate_ce->create_object = php_cassandra_default_aggregate_new;

  memcpy(&cassandra_default_aggregate_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_default_aggregate_handlers.get_properties  = php_cassandra_default_aggregate_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_default_aggregate_handlers.get_gc          = php_cassandra_type_default_aggregate_gc;
#endif
  cassandra_default_aggregate_handlers.compare_objects = php_cassandra_default_aggregate_compare;
  cassandra_default_aggregate_handlers.clone_obj = NULL;
}
