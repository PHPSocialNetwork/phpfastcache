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

#include "DefaultIndex.h"

#include "php_cassandra.h"
#include "util/result.h"
#include "util/ref.h"
#include "util/types.h"

zend_class_entry *cassandra_default_index_ce = NULL;

php5to7_zval
php_cassandra_create_index(cassandra_ref *schema,
                           const CassIndexMeta *meta TSRMLS_DC)
{
  php5to7_zval result;
  cassandra_index *index;
  const char *name;
  size_t name_length;

  PHP5TO7_ZVAL_UNDEF(result);

  PHP5TO7_ZVAL_MAYBE_MAKE(result);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(result), cassandra_default_index_ce);

  index = PHP_CASSANDRA_GET_INDEX(PHP5TO7_ZVAL_MAYBE_P(result));
  index->meta   = meta;
  index->schema = php_cassandra_add_ref(schema);

  cass_index_meta_name(meta, &name, &name_length);
  PHP5TO7_ZVAL_MAYBE_MAKE(index->name);
  PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_P(index->name), name, name_length);

  return result;
}

PHP_METHOD(DefaultIndex, name)
{
  cassandra_index *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_INDEX(getThis());
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->name), 1, 0);
}

PHP_METHOD(DefaultIndex, target)
{
  cassandra_index *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_INDEX(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->target)) {
    const char *target;
    size_t target_length;
    cass_index_meta_target(self->meta, &target, &target_length);
    PHP5TO7_ZVAL_MAYBE_MAKE(self->target);
    PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_P(self->target), target, target_length);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->target), 1, 0);
}

PHP_METHOD(DefaultIndex, kind)
{
  cassandra_index *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_INDEX(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->kind)) {
    PHP5TO7_ZVAL_MAYBE_MAKE(self->kind);
    switch (cass_index_meta_type(self->meta)) {
      case CASS_INDEX_TYPE_KEYS:
        PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(self->kind), "keys");
        break;
      case CASS_INDEX_TYPE_CUSTOM:
        PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(self->kind), "custom");
        break;
      case CASS_INDEX_TYPE_COMPOSITES:
        PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(self->kind), "composites");
        break;
      default:
        PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(self->kind), "unknown");
        break;
    }
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->kind), 1, 0);
}

void php_cassandra_index_build_option(cassandra_index *index)
{
  const CassValue* options;

  PHP5TO7_ZVAL_MAYBE_MAKE(index->options);
  array_init(PHP5TO7_ZVAL_MAYBE_P(index->options));
  options = cass_index_meta_options(index->meta);
  if (options) {
    CassIterator* iterator = cass_iterator_from_map(options);
    while (cass_iterator_next(iterator)) {
      const char* key_str;
      size_t key_str_length;
      const char* value_str;
      size_t value_str_length;
      const CassValue* key = cass_iterator_get_map_key(iterator);
      const CassValue* value = cass_iterator_get_map_value(iterator);

      cass_value_get_string(key, &key_str, &key_str_length);
      cass_value_get_string(value, &value_str, &value_str_length);
      PHP5TO7_ADD_ASSOC_STRINGL_EX(PHP5TO7_ZVAL_MAYBE_P(index->options),
                                   key_str, key_str_length + 1,
                                   value_str, value_str_length);
    }
  }
}

PHP_METHOD(DefaultIndex, option)
{
  char *name;
  php5to7_size name_len;
  cassandra_index *self;
  php5to7_zval* result;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s",
                            &name, &name_len) == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_INDEX(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->options)) {
    php_cassandra_index_build_option(self);
  }

  if (PHP5TO7_ZEND_HASH_FIND(PHP5TO7_Z_ARRVAL_MAYBE_P(self->options),
                         name, name_len + 1,
                         result)) {
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_DEREF(result), 1, 0);
  }
  RETURN_FALSE;
}

PHP_METHOD(DefaultIndex, options)
{
  cassandra_index *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_INDEX(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->options)) {
    php_cassandra_index_build_option(self);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->options), 1, 0);
}

PHP_METHOD(DefaultIndex, className)
{
  cassandra_index *self;
  php5to7_zval* result;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_INDEX(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->options)) {
    php_cassandra_index_build_option(self);
  }

  if (PHP5TO7_ZEND_HASH_FIND(PHP5TO7_Z_ARRVAL_MAYBE_P(self->options),
                         "class_name", sizeof("class_name"),
                         result)) {
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_DEREF(result), 1, 0);
  }
  RETURN_FALSE;
}

PHP_METHOD(DefaultIndex, isCustom)
{
  cassandra_index *self;
  int is_custom;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_INDEX(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->options)) {
    php_cassandra_index_build_option(self);
  }

  is_custom =
      PHP5TO7_ZEND_HASH_EXISTS(PHP5TO7_Z_ARRVAL_MAYBE_P(self->options),
                               "class_name", sizeof("class_name"));
  RETURN_BOOL(is_custom);
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_name, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_default_index_methods[] = {
  PHP_ME(DefaultIndex, name, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultIndex, kind, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultIndex, target, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultIndex, option, arginfo_name, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultIndex, options, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultIndex, className, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultIndex, isCustom, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_default_index_handlers;

static HashTable *
php_cassandra_type_default_index_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_default_index_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_default_index_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_default_index_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_index *self = PHP5TO7_ZEND_OBJECT_GET(index, object);

  PHP5TO7_ZVAL_MAYBE_DESTROY(self->name);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->kind);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->target);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->options);

  if (self->schema) {
    php_cassandra_del_ref(&self->schema);
    self->schema = NULL;
  }
  self->meta = NULL;

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_default_index_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_index *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(index, ce);

  PHP5TO7_ZVAL_UNDEF(self->name);
  PHP5TO7_ZVAL_UNDEF(self->kind);
  PHP5TO7_ZVAL_UNDEF(self->target);
  PHP5TO7_ZVAL_UNDEF(self->options);

  self->schema = NULL;
  self->meta = NULL;

  PHP5TO7_ZEND_OBJECT_INIT_EX(index, default_index, self, ce);
}

void cassandra_define_DefaultIndex(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\DefaultIndex", cassandra_default_index_methods);
  cassandra_default_index_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_default_index_ce TSRMLS_CC, 1, cassandra_index_ce);
  cassandra_default_index_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_default_index_ce->create_object = php_cassandra_default_index_new;

  memcpy(&cassandra_default_index_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_default_index_handlers.get_properties  = php_cassandra_default_index_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_default_index_handlers.get_gc          = php_cassandra_type_default_index_gc;
#endif
  cassandra_default_index_handlers.compare_objects = php_cassandra_default_index_compare;
  cassandra_default_index_handlers.clone_obj = NULL;
}
