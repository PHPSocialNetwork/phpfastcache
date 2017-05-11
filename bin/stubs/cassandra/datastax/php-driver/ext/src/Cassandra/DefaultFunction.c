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

#include "util/result.h"
#include "util/ref.h"
#include "util/types.h"

zend_class_entry *cassandra_default_function_ce = NULL;

php5to7_zval
php_cassandra_create_function(cassandra_ref* schema,
                              const CassFunctionMeta *meta TSRMLS_DC)
{
  php5to7_zval result;
  cassandra_function *function;
  const char *full_name;
  size_t full_name_length;

  PHP5TO7_ZVAL_UNDEF(result);

  PHP5TO7_ZVAL_MAYBE_MAKE(result);
  object_init_ex(PHP5TO7_ZVAL_MAYBE_P(result), cassandra_default_function_ce);

  function = PHP_CASSANDRA_GET_FUNCTION(PHP5TO7_ZVAL_MAYBE_P(result));
  function->schema = php_cassandra_add_ref(schema);
  function->meta   = meta;

  cass_function_meta_full_name(function->meta, &full_name, &full_name_length);
  PHP5TO7_ZVAL_MAYBE_MAKE(function->signature);
  PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_P(function->signature), full_name, full_name_length);

  return result;
}

PHP_METHOD(DefaultFunction, name)
{
  cassandra_function *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_FUNCTION(getThis());

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->signature), 1, 0);
}

PHP_METHOD(DefaultFunction, simpleName)
{
  cassandra_function *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_FUNCTION(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->simple_name)) {
    const char *name;
    size_t name_length;
    cass_function_meta_name(self->meta, &name, &name_length);
    PHP5TO7_ZVAL_MAYBE_MAKE(self->simple_name);
    PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_P(self->simple_name), name, name_length);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->simple_name), 1, 0);
}

PHP_METHOD(DefaultFunction, arguments)
{
  cassandra_function *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_FUNCTION(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->arguments)) {
    size_t i, count = cass_function_meta_argument_count(self->meta);
    PHP5TO7_ZVAL_MAYBE_MAKE(self->arguments);
    array_init(PHP5TO7_ZVAL_MAYBE_P(self->arguments));
    for (i = 0; i < count; ++i) {
      const char *name;
      size_t name_length;
      const CassDataType* data_type;
      if (cass_function_meta_argument(self->meta, i, &name, &name_length, &data_type) == CASS_OK) {
        php5to7_zval type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);
        if (!PHP5TO7_ZVAL_IS_UNDEF(type)) {
          PHP5TO7_ADD_ASSOC_ZVAL_EX(PHP5TO7_ZVAL_MAYBE_P(self->arguments),
                                    name, name_length + 1,
                                    PHP5TO7_ZVAL_MAYBE_P(type));
        }
      }
    }
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->arguments), 1, 0);
}

PHP_METHOD(DefaultFunction, returnType)
{
  cassandra_function *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_FUNCTION(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->return_type)) {
    const CassDataType* data_type = cass_function_meta_return_type(self->meta);
    if (!data_type) {
      return;
    }
    self->return_type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->return_type), 1, 0);
}

PHP_METHOD(DefaultFunction, signature)
{
  cassandra_function *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_FUNCTION(getThis());
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->signature), 1, 0);
}

PHP_METHOD(DefaultFunction, language)
{
  cassandra_function *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_FUNCTION(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->language)) {
    const char *language;
    size_t language_length;
    cass_function_meta_language(self->meta, &language, &language_length);
    PHP5TO7_ZVAL_MAYBE_MAKE(self->language);
    PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_P(self->language), language, language_length);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->language), 1, 0);
}

PHP_METHOD(DefaultFunction, body)
{
  cassandra_function *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_FUNCTION(getThis());
  if (PHP5TO7_ZVAL_IS_UNDEF(self->body)) {
    const char *body;
    size_t body_length;
    cass_function_meta_body(self->meta, &body, &body_length);
    PHP5TO7_ZVAL_MAYBE_MAKE(self->body);
    PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_P(self->body), body, body_length);
  }

  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->body), 1, 0);
}

PHP_METHOD(DefaultFunction, isCalledOnNullInput)
{
  cassandra_function *self;

  if (zend_parse_parameters_none() == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_FUNCTION(getThis());
  RETURN_BOOL((int)cass_function_meta_called_on_null_input(self->meta));
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_default_function_methods[] = {
  PHP_ME(DefaultFunction, name, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultFunction, simpleName, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultFunction, arguments, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultFunction, returnType, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultFunction, signature, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultFunction, language, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultFunction, body, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultFunction, isCalledOnNullInput, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_default_function_handlers;

static HashTable *
php_cassandra_type_default_function_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_default_function_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_default_function_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_default_function_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_function *self = PHP5TO7_ZEND_OBJECT_GET(function, object);

  PHP5TO7_ZVAL_MAYBE_DESTROY(self->simple_name);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->arguments);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->return_type);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->signature);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->language);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->body);

  if (self->schema) {
    php_cassandra_del_ref(&self->schema);
    self->schema = NULL;
  }
  self->meta = NULL;

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_default_function_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_function *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(function, ce);

  PHP5TO7_ZVAL_UNDEF(self->simple_name);
  PHP5TO7_ZVAL_UNDEF(self->arguments);
  PHP5TO7_ZVAL_UNDEF(self->return_type);
  PHP5TO7_ZVAL_UNDEF(self->signature);
  PHP5TO7_ZVAL_UNDEF(self->language);
  PHP5TO7_ZVAL_UNDEF(self->body);

  self->schema = NULL;
  self->meta = NULL;

  PHP5TO7_ZEND_OBJECT_INIT_EX(function, default_function, self, ce);
}

void cassandra_define_DefaultFunction(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\DefaultFunction", cassandra_default_function_methods);
  cassandra_default_function_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_default_function_ce TSRMLS_CC, 1, cassandra_function_ce);
  cassandra_default_function_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_default_function_ce->create_object = php_cassandra_default_function_new;

  memcpy(&cassandra_default_function_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_default_function_handlers.get_properties  = php_cassandra_default_function_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_default_function_handlers.get_gc          = php_cassandra_type_default_function_gc;
#endif
  cassandra_default_function_handlers.compare_objects = php_cassandra_default_function_compare;
  cassandra_default_function_handlers.clone_obj = NULL;
}
