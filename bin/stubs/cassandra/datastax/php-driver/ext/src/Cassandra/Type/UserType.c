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
#include "src/Cassandra/UserTypeValue.h"
#include "util/collections.h"
#if PHP_MAJOR_VERSION >= 7
#include <zend_smart_str.h>
#else
#include <ext/standard/php_smart_str.h>
#endif

zend_class_entry *cassandra_type_user_type_ce = NULL;

int php_cassandra_type_user_type_add(cassandra_type *type,
                                     const char *name, size_t name_length,
                                     zval *zsub_type TSRMLS_DC)
{
  cassandra_type *sub_type = PHP_CASSANDRA_GET_TYPE(zsub_type);
  if (cass_data_type_add_sub_type_by_name_n(type->data_type,
                                            name, name_length,
                                            sub_type->data_type) != CASS_OK) {
    return 0;
  }
  PHP5TO7_ZEND_HASH_ADD(&type->types,
                        name, name_length + 1,
                        zsub_type, sizeof(zval *));
  return 1;
}

PHP_METHOD(TypeUserType, __construct)
{
  zend_throw_exception_ex(cassandra_logic_exception_ce, 0 TSRMLS_CC,
    "Instantiation of a Cassandra\\Type\\UserType type is not supported."
  );
  return;
}

PHP_METHOD(TypeUserType, withName)
{
  char *name;
  php5to7_size name_len;
  cassandra_type *self;
  cassandra_type *user_type;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &name, &name_len) == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());

  object_init_ex(return_value, cassandra_type_user_type_ce);
  user_type = PHP_CASSANDRA_GET_TYPE(return_value);
  user_type->data_type = cass_data_type_new_from_existing(self->data_type);

  user_type->type_name = estrndup(name, name_len);

  if (self->keyspace) {
    user_type->keyspace = estrdup(self->keyspace);
  }

  PHP5TO7_ZEND_HASH_ZVAL_COPY(&user_type->types, &self->types);
}

PHP_METHOD(TypeUserType, name)
{
  cassandra_type *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());

  if (!self->type_name)
    RETURN_NULL();

  PHP5TO7_RETVAL_STRING(self->type_name);
}

PHP_METHOD(TypeUserType, withKeyspace)
{
  char *keyspace;
  php5to7_size keyspace_len;
  cassandra_type *self;
  cassandra_type *user_type;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &keyspace, &keyspace_len) == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());

  object_init_ex(return_value, cassandra_type_user_type_ce);
  user_type = PHP_CASSANDRA_GET_TYPE(return_value);
  user_type->data_type = cass_data_type_new_from_existing(self->data_type);

  if (self->type_name) {
    user_type->type_name = estrdup(self->type_name);
  }

  user_type->keyspace = estrndup(keyspace, keyspace_len);

  PHP5TO7_ZEND_HASH_ZVAL_COPY(&user_type->types, &self->types);
}

PHP_METHOD(TypeUserType, keyspace)
{
  cassandra_type *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());

  if (!self->keyspace)
    RETURN_NULL();

  PHP5TO7_RETVAL_STRING(self->keyspace);
}

PHP_METHOD(TypeUserType, types)
{
  cassandra_type *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());

  array_init(return_value);
  PHP5TO7_ZEND_HASH_ZVAL_COPY(Z_ARRVAL_P(return_value), &self->types);
}

PHP_METHOD(TypeUserType, __toString)
{
  cassandra_type *self;
  smart_str string = PHP5TO7_SMART_STR_INIT;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());

  php_cassandra_type_string(self, &string TSRMLS_CC);
  smart_str_0(&string);

  PHP5TO7_RETVAL_STRING(PHP5TO7_SMART_STR_VAL(string));
  smart_str_free(&string);
}

PHP_METHOD(TypeUserType, create)
{
  cassandra_type *self;
  cassandra_user_type_value *user_type_value;
  php5to7_zval_args args = NULL;
  int argc = 0, i;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "*",
                            &args, &argc) == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TYPE(getThis());

  object_init_ex(return_value, cassandra_user_type_value_ce);
  user_type_value = PHP_CASSANDRA_GET_USER_TYPE_VALUE(return_value);

  PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(user_type_value->type), getThis());

  if (argc > 0) {
    if (argc % 2 == 1) {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                              "Not enough name/value pairs, user_types can only be created " \
                              "from an even number of name/value pairs, where each odd " \
                              "argument is a name and each even argument is a value, " \
                              "e.g user_type(name, value, name, value, name, value)");
      PHP5TO7_MAYBE_EFREE(args);
      return;
    }

    for (i = 0; i < argc; i += 2) {
      zval *name = PHP5TO7_ZVAL_ARG(args[i]);
      zval *value = PHP5TO7_ZVAL_ARG(args[i + 1]);
      php5to7_zval *sub_type;
      if (Z_TYPE_P(name) != IS_STRING) {
        zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                                "Argument %d is not a string", i + 1);
        PHP5TO7_MAYBE_EFREE(args);
        return;
      }
      if (!PHP5TO7_ZEND_HASH_FIND(&self->types,
                                  Z_STRVAL_P(name), Z_STRLEN_P(name) + 1,
                                  sub_type)) {
        zend_throw_exception_ex(cassandra_invalid_argument_exception_ce,
                                0 TSRMLS_CC,
                                "Invalid name '%s'", Z_STRVAL_P(name));
        PHP5TO7_MAYBE_EFREE(args);
        return;
      }
      if (!php_cassandra_validate_object(value,
                                         PHP5TO7_ZVAL_MAYBE_DEREF(sub_type) TSRMLS_CC)) {
        PHP5TO7_MAYBE_EFREE(args);
        return;
      }
      if (!php_cassandra_user_type_value_set(user_type_value,
                                        Z_STRVAL_P(name), Z_STRLEN_P(name),
                                        value TSRMLS_CC)) {
        PHP5TO7_MAYBE_EFREE(args);
        return;
      }
    }

    PHP5TO7_MAYBE_EFREE(args);
  }
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_value, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_name, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_keyspace, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, keyspace)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_type_user_type_methods[] = {
  PHP_ME(TypeUserType, __construct,  arginfo_none,     ZEND_ACC_PUBLIC)
  PHP_ME(TypeUserType, withName,     arginfo_name,     ZEND_ACC_PUBLIC)
  PHP_ME(TypeUserType, name,         arginfo_none,     ZEND_ACC_PUBLIC)
  PHP_ME(TypeUserType, withKeyspace, arginfo_keyspace, ZEND_ACC_PUBLIC)
  PHP_ME(TypeUserType, keyspace,     arginfo_none,     ZEND_ACC_PUBLIC)
  PHP_ME(TypeUserType, __toString,   arginfo_none,     ZEND_ACC_PUBLIC)
  PHP_ME(TypeUserType, types,        arginfo_none,     ZEND_ACC_PUBLIC)
  PHP_ME(TypeUserType, create,       arginfo_value,    ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_type_user_type_handlers;

static HashTable *
php_cassandra_type_user_type_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_type_user_type_properties(zval *object TSRMLS_DC)
{
  php5to7_zval types;

  cassandra_type *self  = PHP_CASSANDRA_GET_TYPE(object);
  HashTable      *props = zend_std_get_properties(object TSRMLS_CC);

  PHP5TO7_ZVAL_MAYBE_MAKE(types);
  array_init(PHP5TO7_ZVAL_MAYBE_P(types));
  PHP5TO7_ZEND_HASH_ZVAL_COPY(PHP5TO7_Z_ARRVAL_MAYBE_P(types), &self->types);
  PHP5TO7_ZEND_HASH_UPDATE(props,
                           "types", sizeof("types"),
                           PHP5TO7_ZVAL_MAYBE_P(types), sizeof(zval));

  return props;
}

static int
php_cassandra_type_user_type_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_type* type1 = PHP_CASSANDRA_GET_TYPE(obj1);
  cassandra_type* type2 = PHP_CASSANDRA_GET_TYPE(obj2);

  return php_cassandra_type_compare(type1, type2 TSRMLS_CC);
}

static void
php_cassandra_type_user_type_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_type *self = PHP5TO7_ZEND_OBJECT_GET(type, object);

  if (self->data_type) cass_data_type_free(self->data_type);
  if (self->keyspace) efree(self->keyspace);
  if (self->type_name) efree(self->type_name);
  zend_hash_destroy(&self->types);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_type_user_type_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_type *self = PHP5TO7_ZEND_OBJECT_ECALLOC(type, ce);

  self->type = CASS_VALUE_TYPE_UDT;
  self->data_type = NULL;
  self->keyspace = self->type_name = NULL;
  zend_hash_init(&self->types, 0, NULL, ZVAL_PTR_DTOR, 0);

  PHP5TO7_ZEND_OBJECT_INIT_EX(type, type_user_type, self, ce);
}

void cassandra_define_TypeUserType(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Type\\UserType", cassandra_type_user_type_methods);
  cassandra_type_user_type_ce = php5to7_zend_register_internal_class_ex(&ce, cassandra_type_ce);
  memcpy(&cassandra_type_user_type_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_type_user_type_handlers.get_properties  = php_cassandra_type_user_type_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_type_user_type_handlers.get_gc          = php_cassandra_type_user_type_gc;
#endif
  cassandra_type_user_type_handlers.compare_objects = php_cassandra_type_user_type_compare;
  cassandra_type_user_type_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_type_user_type_ce->create_object = php_cassandra_type_user_type_new;
}
