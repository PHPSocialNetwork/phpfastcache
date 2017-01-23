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
#include "DefaultMaterializedView.h"
#include "DefaultTable.h"

#include "php_cassandra.h"
#include "util/result.h"
#include "util/ref.h"
#include "util/types.h"

#if PHP_MAJOR_VERSION >= 7
#include <zend_smart_str.h>
#else
#include <ext/standard/php_smart_str.h>
#endif

zend_class_entry *cassandra_default_keyspace_ce = NULL;

PHP_METHOD(DefaultKeyspace, name)
{
  cassandra_keyspace *self;
  php5to7_zval value;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_KEYSPACE(getThis());

  php_cassandra_get_keyspace_field(self->meta, "keyspace_name", &value TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(value), 0, 1);
}

PHP_METHOD(DefaultKeyspace, replicationClassName)
{
  cassandra_keyspace *self;
  php5to7_zval value;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_KEYSPACE(getThis());

  php_cassandra_get_keyspace_field(self->meta, "strategy_class", &value TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(value), 0, 1);
}

PHP_METHOD(DefaultKeyspace, replicationOptions)
{
  cassandra_keyspace *self;
  php5to7_zval value;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_KEYSPACE(getThis());

  php_cassandra_get_keyspace_field(self->meta, "strategy_options", &value TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(value), 0, 1);
}

PHP_METHOD(DefaultKeyspace, hasDurableWrites)
{
  cassandra_keyspace *self;
  php5to7_zval value;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_KEYSPACE(getThis());

  php_cassandra_get_keyspace_field(self->meta, "durable_writes", &value TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(value), 0, 1);
}

PHP_METHOD(DefaultKeyspace, table)
{
  char *name;
  php5to7_size name_len;
  cassandra_keyspace *self;
  php5to7_zval ztable;
  const CassTableMeta *meta;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &name, &name_len) == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_KEYSPACE(getThis());
  meta = cass_keyspace_meta_table_by_name_n(self->meta,
                                            name, name_len);
  if (meta == NULL) {
    RETURN_FALSE;
  }

  ztable = php_cassandra_create_table(self->schema, meta TSRMLS_CC);
  if (PHP5TO7_ZVAL_IS_UNDEF(ztable)) {
    return;
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(ztable), 0, 1);
}

PHP_METHOD(DefaultKeyspace, tables)
{
  cassandra_keyspace *self;
  CassIterator *iterator;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self     = PHP_CASSANDRA_GET_KEYSPACE(getThis());
  iterator = cass_iterator_tables_from_keyspace_meta(self->meta);

  array_init(return_value);
  while (cass_iterator_next(iterator)) {
    const CassTableMeta *meta;
    php5to7_zval ztable;
    cassandra_table *table;

    meta  = cass_iterator_get_table_meta(iterator);
    ztable = php_cassandra_create_table(self->schema, meta TSRMLS_CC);

    if (PHP5TO7_ZVAL_IS_UNDEF(ztable)) {
      zval_ptr_dtor(PHP5TO7_ZVAL_MAYBE_ADDR_OF(return_value));
      cass_iterator_free(iterator);
      return;
    } else {
      table = PHP_CASSANDRA_GET_TABLE(PHP5TO7_ZVAL_MAYBE_P(ztable));

      if (PHP5TO7_Z_TYPE_MAYBE_P(table->name) == IS_STRING) {
        PHP5TO7_ADD_ASSOC_ZVAL_EX(return_value,
                                  PHP5TO7_Z_STRVAL_MAYBE_P(table->name),
                                  PHP5TO7_Z_STRLEN_MAYBE_P(table->name) + 1,
                                  PHP5TO7_ZVAL_MAYBE_P(ztable));
      } else {
        add_next_index_zval(return_value, PHP5TO7_ZVAL_MAYBE_P(ztable));
      }
    }
  }

  cass_iterator_free(iterator);
}

PHP_METHOD(DefaultKeyspace, userType)
{
  char *name;
  php5to7_size name_len;
  cassandra_keyspace *self;
  php5to7_zval ztype;
  const CassDataType *user_type;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &name, &name_len) == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_KEYSPACE(getThis());
  user_type = cass_keyspace_meta_user_type_by_name_n(self->meta, name, name_len);

  if (user_type == NULL) {
    return;
  }

  ztype = php_cassandra_type_from_data_type(user_type TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(ztype), 0, 1);
}

PHP_METHOD(DefaultKeyspace, userTypes)
{
  cassandra_keyspace *self;
  CassIterator       *iterator;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self     = PHP_CASSANDRA_GET_KEYSPACE(getThis());
  iterator = cass_iterator_user_types_from_keyspace_meta(self->meta);

  array_init(return_value);
  while (cass_iterator_next(iterator)) {
    const CassDataType *user_type;
    php5to7_zval ztype;
    const char *type_name;
    size_t type_name_len;

    user_type = cass_iterator_get_user_type(iterator);
    ztype = php_cassandra_type_from_data_type(user_type TSRMLS_CC);

    cass_data_type_type_name(user_type, &type_name, &type_name_len);
    PHP5TO7_ADD_ASSOC_ZVAL_EX(return_value,
                              type_name, type_name_len + 1,
                              PHP5TO7_ZVAL_MAYBE_P(ztype));
  }

  cass_iterator_free(iterator);
}

PHP_METHOD(DefaultKeyspace, materializedView)
{
  cassandra_keyspace *self;
  char *name;
  php5to7_size name_len;
  php5to7_zval zview;
  const CassMaterializedViewMeta *meta;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &name, &name_len) == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_KEYSPACE(getThis());
  meta = cass_keyspace_meta_materialized_view_by_name_n(self->meta,
                                                        name, name_len);
  if (meta == NULL) {
    RETURN_FALSE;
  }

  zview = php_cassandra_create_materialized_view(self->schema, meta TSRMLS_CC);
  if (PHP5TO7_ZVAL_IS_UNDEF(zview)) {
    return;
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(zview), 0, 1);
}

PHP_METHOD(DefaultKeyspace, materializedViews)
{
  cassandra_keyspace *self;
  CassIterator *iterator;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self     = PHP_CASSANDRA_GET_KEYSPACE(getThis());
  iterator = cass_iterator_materialized_views_from_keyspace_meta(self->meta);

  array_init(return_value);
  while (cass_iterator_next(iterator)) {
    const CassMaterializedViewMeta *meta;
    php5to7_zval zview;
    cassandra_materialized_view *view;

    meta  = cass_iterator_get_materialized_view_meta(iterator);
    zview = php_cassandra_create_materialized_view(self->schema, meta TSRMLS_CC);

    if (PHP5TO7_ZVAL_IS_UNDEF(zview)) {
      zval_ptr_dtor(PHP5TO7_ZVAL_MAYBE_ADDR_OF(return_value));
      cass_iterator_free(iterator);
      return;
    } else {
      view = PHP_CASSANDRA_GET_MATERIALIZED_VIEW(PHP5TO7_ZVAL_MAYBE_P(zview));

      if (PHP5TO7_Z_TYPE_MAYBE_P(view->name) == IS_STRING) {
        PHP5TO7_ADD_ASSOC_ZVAL_EX(return_value,
                                  PHP5TO7_Z_STRVAL_MAYBE_P(view->name),
                                  PHP5TO7_Z_STRLEN_MAYBE_P(view->name) + 1,
                                  PHP5TO7_ZVAL_MAYBE_P(zview));
      } else {
        add_next_index_zval(return_value, PHP5TO7_ZVAL_MAYBE_P(zview));
      }
    }
  }

  cass_iterator_free(iterator);
}

int php_cassandra_arguments_string(php5to7_zval_args args,
                                   int argc,
                                   smart_str *arguments TSRMLS_DC) {
  int i;

  for (i = 0; i < argc; ++i) {
    zval *argument_type = PHP5TO7_ZVAL_ARG(args[i]);

    if (i > 0) {
      smart_str_appendc_ex(arguments, ',', 0);
    }

    if (Z_TYPE_P(argument_type) == IS_STRING) {
      smart_str_appendl_ex(arguments,
                           Z_STRVAL_P(argument_type), Z_STRLEN_P(argument_type),
                           0);
    } else if (Z_TYPE_P(argument_type) == IS_OBJECT &&
               instanceof_function(Z_OBJCE_P(argument_type), cassandra_type_ce TSRMLS_CC)) {
      cassandra_type *type = PHP_CASSANDRA_GET_TYPE(argument_type);
      php_cassandra_type_string(type, arguments TSRMLS_CC);
    } else {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                              "Argument types must be either a string or an instance of Cassandra\\Type");
      return FAILURE;
    }
  }

  smart_str_0(arguments);

  return SUCCESS;
}

PHP_METHOD(DefaultKeyspace, function)
{
  cassandra_keyspace *self;
  char *name;
  php5to7_size name_len;
  php5to7_zval_args args = NULL;
  smart_str arguments = PHP5TO7_SMART_STR_INIT;
  int argc = 0;
  const CassFunctionMeta *meta = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|*",
                            &name, &name_len,
                            &args, &argc) == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_KEYSPACE(getThis());

  if (argc > 0) {
    if (php_cassandra_arguments_string(args, argc, &arguments TSRMLS_CC) == FAILURE) {
      PHP5TO7_MAYBE_EFREE(args);
      return;
    }
  }

  meta =
      cass_keyspace_meta_function_by_name_n(self->meta,
                                            name, name_len,
                                            PHP5TO7_SMART_STR_VAL(arguments),
                                            PHP5TO7_SMART_STR_LEN(arguments));
  if (meta) {
    php5to7_zval zfunction = php_cassandra_create_function(self->schema, meta TSRMLS_CC);
    RETVAL_ZVAL(PHP5TO7_ZVAL_MAYBE_P(zfunction), 1, 1);
  } else {
    RETVAL_FALSE;
  }

  smart_str_free(&arguments);
  PHP5TO7_MAYBE_EFREE(args);
}

PHP_METHOD(DefaultKeyspace, functions)
{
  cassandra_keyspace *self;
  CassIterator *iterator;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self     = PHP_CASSANDRA_GET_KEYSPACE(getThis());
  iterator = cass_iterator_functions_from_keyspace_meta(self->meta);

  array_init(return_value);
  while (cass_iterator_next(iterator)) {
    const CassFunctionMeta *meta = cass_iterator_get_function_meta(iterator);
    php5to7_zval zfunction = php_cassandra_create_function(self->schema, meta TSRMLS_CC);

    if (!PHP5TO7_ZVAL_IS_UNDEF(zfunction)) {
      cassandra_function *function = PHP_CASSANDRA_GET_FUNCTION(PHP5TO7_ZVAL_MAYBE_P(zfunction));

      if (PHP5TO7_Z_TYPE_MAYBE_P(function->signature) == IS_STRING) {
        PHP5TO7_ADD_ASSOC_ZVAL_EX(return_value,
                                  PHP5TO7_Z_STRVAL_MAYBE_P(function->signature),
                                  PHP5TO7_Z_STRLEN_MAYBE_P(function->signature) + 1,
                                  PHP5TO7_ZVAL_MAYBE_P(zfunction));
      } else {
        add_next_index_zval(return_value, PHP5TO7_ZVAL_MAYBE_P(zfunction));
      }
    }
  }

  cass_iterator_free(iterator);
}

static php5to7_zval
php_cassandra_create_aggregate(cassandra_ref* schema,
                               const CassAggregateMeta *meta TSRMLS_DC)
{
  php5to7_zval result;
  cassandra_aggregate *aggregate;
  const char *full_name;
  size_t full_name_length;

  PHP5TO7_ZVAL_UNDEF(result);

  PHP5TO7_ZVAL_MAYBE_MAKE(result);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(result), cassandra_default_aggregate_ce);

  aggregate = PHP_CASSANDRA_GET_AGGREGATE(PHP5TO7_ZVAL_MAYBE_P(result));
  aggregate->schema = php_cassandra_add_ref(schema);
  aggregate->meta   = meta;

  cass_aggregate_meta_full_name(aggregate->meta, &full_name, &full_name_length);
  PHP5TO7_ZVAL_MAYBE_MAKE(aggregate->signature);
  PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_P(aggregate->signature), full_name, full_name_length);

  return result;
}

PHP_METHOD(DefaultKeyspace, aggregate)
{
  cassandra_keyspace *self;
  char *name;
  php5to7_size name_len;
  php5to7_zval_args args = NULL;
  smart_str arguments = PHP5TO7_SMART_STR_INIT;
  int argc = 0;
  const CassAggregateMeta *meta = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|*",
                            &name, &name_len,
                            &args, &argc) == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_KEYSPACE(getThis());

  if (argc > 0) {
    if (php_cassandra_arguments_string(args, argc, &arguments TSRMLS_CC) == FAILURE) {
      PHP5TO7_MAYBE_EFREE(args);
      return;
    }
  }

  meta =
      cass_keyspace_meta_aggregate_by_name_n(self->meta,
                                            name, name_len,
                                            PHP5TO7_SMART_STR_VAL(arguments),
                                            PHP5TO7_SMART_STR_LEN(arguments));
  if (meta) {
    php5to7_zval zaggregate = php_cassandra_create_aggregate(self->schema, meta TSRMLS_CC);
    RETVAL_ZVAL(PHP5TO7_ZVAL_MAYBE_P(zaggregate), 1, 1);
  } else {
    RETVAL_FALSE;
  }

  smart_str_free(&arguments);
  PHP5TO7_MAYBE_EFREE(args);
}

PHP_METHOD(DefaultKeyspace, aggregates)
{
  cassandra_keyspace *self;
  CassIterator *iterator;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self     = PHP_CASSANDRA_GET_KEYSPACE(getThis());
  iterator = cass_iterator_aggregates_from_keyspace_meta(self->meta);

  array_init(return_value);
  while (cass_iterator_next(iterator)) {
    const CassAggregateMeta *meta = cass_iterator_get_aggregate_meta(iterator);
    php5to7_zval zaggregate = php_cassandra_create_aggregate(self->schema, meta TSRMLS_CC);

    if (!PHP5TO7_ZVAL_IS_UNDEF(zaggregate)) {
      cassandra_aggregate *aggregate = PHP_CASSANDRA_GET_AGGREGATE(PHP5TO7_ZVAL_MAYBE_P(zaggregate));

      if (PHP5TO7_Z_TYPE_MAYBE_P(aggregate->signature) == IS_STRING) {
        PHP5TO7_ADD_ASSOC_ZVAL_EX(return_value,
                                  PHP5TO7_Z_STRVAL_MAYBE_P(aggregate->signature),
                                  PHP5TO7_Z_STRLEN_MAYBE_P(aggregate->signature) + 1,
                                  PHP5TO7_ZVAL_MAYBE_P(zaggregate));
      } else {
        add_next_index_zval(return_value, PHP5TO7_ZVAL_MAYBE_P(zaggregate));
      }
    }
  }

  cass_iterator_free(iterator);
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_name, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_signature, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, name)
  ZEND_ARG_INFO(0, ...)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_default_keyspace_methods[] = {
  PHP_ME(DefaultKeyspace, name, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, replicationClassName, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, replicationOptions, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, hasDurableWrites, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, table, arginfo_name, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, tables, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, userType, arginfo_name, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, userTypes, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, materializedView, arginfo_name, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, materializedViews, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, function, arginfo_signature, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, functions, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, aggregate, arginfo_signature, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultKeyspace, aggregates, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_default_keyspace_handlers;

static HashTable *
php_cassandra_type_default_keyspace_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_default_keyspace_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_default_keyspace_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_default_keyspace_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_keyspace *self = PHP5TO7_ZEND_OBJECT_GET(keyspace, object);

  if (self->schema) {
    php_cassandra_del_ref(&self->schema);
    self->schema = NULL;
  }
  self->meta = NULL;

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_default_keyspace_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_keyspace *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(keyspace, ce);

  self->meta   = NULL;
  self->schema = NULL;

  PHP5TO7_ZEND_OBJECT_INIT_EX(keyspace, default_keyspace, self, ce);
}

void cassandra_define_DefaultKeyspace(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\DefaultKeyspace", cassandra_default_keyspace_methods);
  cassandra_default_keyspace_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_default_keyspace_ce TSRMLS_CC, 1, cassandra_keyspace_ce);
  cassandra_default_keyspace_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_default_keyspace_ce->create_object = php_cassandra_default_keyspace_new;

  memcpy(&cassandra_default_keyspace_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_default_keyspace_handlers.get_properties  = php_cassandra_default_keyspace_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_default_keyspace_handlers.get_gc          = php_cassandra_type_default_keyspace_gc;
#endif
  cassandra_default_keyspace_handlers.compare_objects = php_cassandra_default_keyspace_compare;
  cassandra_default_keyspace_handlers.clone_obj = NULL;
}
