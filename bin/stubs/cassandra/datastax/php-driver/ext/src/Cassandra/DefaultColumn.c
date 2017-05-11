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

#include "DefaultColumn.h"

#include "php_cassandra.h"
#include "util/result.h"
#include "util/types.h"
#include "util/ref.h"

zend_class_entry *cassandra_default_column_ce = NULL;

php5to7_zval
php_cassandra_create_column(cassandra_ref *schema,
                            const CassColumnMeta *meta TSRMLS_DC)
{
  php5to7_zval result;
  cassandra_column *column;
  const char *name;
  size_t name_length;
  const CassValue *value;

  PHP5TO7_ZVAL_UNDEF(result);

  PHP5TO7_ZVAL_MAYBE_MAKE(result);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(result), cassandra_default_column_ce);

  column = PHP_CASSANDRA_GET_COLUMN(PHP5TO7_ZVAL_MAYBE_P(result));
  column->schema = php_cassandra_add_ref(schema);
  column->meta   = meta;

  cass_column_meta_name(meta, &name, &name_length);
  PHP5TO7_ZVAL_MAYBE_MAKE(column->name);
  PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_P(column->name), name, name_length);

  value = cass_column_meta_field_by_name(meta, "validator");
  if (value) {
    const char *validator;
    size_t validator_length;

    ASSERT_SUCCESS_BLOCK(cass_value_get_string(value,
                                               &validator,
                                               &validator_length),
     zval_ptr_dtor(&result);
     PHP5TO7_ZVAL_UNDEF(result);
     return result;
    );

    if (php_cassandra_parse_column_type(validator, validator_length,
                                        &column->reversed, &column->frozen,
                                        &column->type TSRMLS_CC) == FAILURE) {
      zval_ptr_dtor(&result);
      PHP5TO7_ZVAL_UNDEF(result);
      return result;
    }
  } else {
    const CassDataType *data_type = cass_column_meta_data_type(meta);
    if (data_type) {
      const char *clustering_order;
      size_t clustering_order_length;
      column->type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);

#if CURRENT_CPP_DRIVER_VERSION > CPP_DRIVER_VERSION(2, 2, 0)
      column->frozen = cass_data_type_is_frozen(data_type);
#else
      column->frozen = 0;
#endif

      value = cass_column_meta_field_by_name(meta, "clustering_order");
      if (!value) {
        zend_throw_exception_ex(cassandra_runtime_exception_ce, 0 TSRMLS_CC,
                                "Unable to get column field \"clustering_order\"");
        zval_ptr_dtor(&result);
        PHP5TO7_ZVAL_UNDEF(result);
        return result;
      }

     ASSERT_SUCCESS_BLOCK(cass_value_get_string(value,
                                                &clustering_order,
                                                &clustering_order_length),
        zval_ptr_dtor(&result);
        PHP5TO7_ZVAL_UNDEF(result);
        return result;
      );
      column->reversed =
          strncmp(clustering_order, "desc", clustering_order_length) == 0 ? 1 : 0;
    }
  }

  return result;
}


PHP_METHOD(DefaultColumn, name)
{
  cassandra_column *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_COLUMN(getThis());

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->name), 1, 0);
}

PHP_METHOD(DefaultColumn, type)
{
  cassandra_column *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_COLUMN(getThis());

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->type), 1, 0);
}

PHP_METHOD(DefaultColumn, isReversed)
{
  cassandra_column *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_COLUMN(getThis());

  RETURN_BOOL(self->reversed);
}

PHP_METHOD(DefaultColumn, isStatic)
{
  cassandra_column *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self  = PHP_CASSANDRA_GET_COLUMN(getThis());

  RETURN_BOOL(cass_column_meta_type(self->meta) == CASS_COLUMN_TYPE_STATIC);
}

PHP_METHOD(DefaultColumn, isFrozen)
{
  cassandra_column *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_COLUMN(getThis());

  RETURN_BOOL(self->frozen);
}

PHP_METHOD(DefaultColumn, indexName)
{
  cassandra_column *self;
  php5to7_zval value;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_COLUMN(getThis());

  php_cassandra_get_column_field(self->meta, "index_name", &value TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(value), 0, 1);
}

PHP_METHOD(DefaultColumn, indexOptions)
{
  cassandra_column *self;
  php5to7_zval value;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_COLUMN(getThis());

  php_cassandra_get_column_field(self->meta, "index_options", &value TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(value), 0, 1);
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_default_column_methods[] = {
  PHP_ME(DefaultColumn, name,         arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultColumn, type,         arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultColumn, isReversed,   arginfo_none, ZEND_ACC_PUBLIC|ZEND_ACC_DEPRECATED)
  PHP_ME(DefaultColumn, isStatic,     arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultColumn, isFrozen,     arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultColumn, indexName,    arginfo_none, ZEND_ACC_PUBLIC|ZEND_ACC_DEPRECATED)
  PHP_ME(DefaultColumn, indexOptions, arginfo_none, ZEND_ACC_PUBLIC|ZEND_ACC_DEPRECATED)
  PHP_FE_END
};

static zend_object_handlers cassandra_default_column_handlers;

static HashTable *
php_cassandra_type_default_column_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_default_column_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_default_column_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_default_column_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_column *self = PHP5TO7_ZEND_OBJECT_GET(column, object);

  PHP5TO7_ZVAL_MAYBE_DESTROY(self->name);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->type);

  if (self->schema) {
    php_cassandra_del_ref(&self->schema);
    self->schema = NULL;
  }
  self->meta = NULL;

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_default_column_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_column *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(column, ce);

  self->reversed = 0;
  self->frozen   = 0;
  self->schema   = NULL;
  self->meta     = NULL;
  PHP5TO7_ZVAL_UNDEF(self->name);
  PHP5TO7_ZVAL_UNDEF(self->type);

  PHP5TO7_ZEND_OBJECT_INIT_EX(column, default_column, self, ce);
}

void cassandra_define_DefaultColumn(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\DefaultColumn", cassandra_default_column_methods);
  cassandra_default_column_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_default_column_ce TSRMLS_CC, 1, cassandra_column_ce);
  cassandra_default_column_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_default_column_ce->create_object = php_cassandra_default_column_new;

  memcpy(&cassandra_default_column_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_default_column_handlers.get_properties  = php_cassandra_default_column_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_default_column_handlers.get_gc          = php_cassandra_type_default_column_gc;
#endif
  cassandra_default_column_handlers.compare_objects = php_cassandra_default_column_compare;
  cassandra_default_column_handlers.clone_obj = NULL;
}
