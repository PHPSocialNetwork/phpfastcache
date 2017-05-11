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
#include "src/Cassandra/Type/Tuple.h"
#include "src/Cassandra/Type/UserType.h"
#include "util/types.h"

zend_class_entry *cassandra_type_ce = NULL;

#define XX_SCALAR_METHOD(name, value) PHP_METHOD(Type, name) \
{ \
  php5to7_zval ztype; \
  if (zend_parse_parameters_none() == FAILURE) { \
    return; \
  } \
  ztype = php_cassandra_type_scalar(value TSRMLS_CC); \
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(ztype), 1, 1); \
}

PHP_CASSANDRA_SCALAR_TYPES_MAP(XX_SCALAR_METHOD)
#undef XX_SCALAR_METHOD

PHP_METHOD(Type, collection)
{
  php5to7_zval ztype;
  zval *value_type;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "O",
                            &value_type, cassandra_type_ce) == FAILURE) {
    return;
  }

  if (!php_cassandra_type_validate(value_type, "type" TSRMLS_CC)) {
    return;
  }

  ztype  = php_cassandra_type_collection(value_type TSRMLS_CC);
  Z_ADDREF_P(value_type);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(ztype), 0, 1);
}

PHP_METHOD(Type, tuple)
{
  php5to7_zval ztype;
  cassandra_type *type;
  php5to7_zval_args args = NULL;
  int argc = 0, i;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "+",
                            &args, &argc) == FAILURE) {
    return;
  }

  for (i = 0; i < argc; ++i) {
    zval *sub_type = PHP5TO7_ZVAL_ARG(args[i]);
    if (!php_cassandra_type_validate(sub_type, "type" TSRMLS_CC)) {
      PHP5TO7_MAYBE_EFREE(args);
      return;
    }
  }

  ztype = php_cassandra_type_tuple(TSRMLS_C);
  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));

  for (i = 0; i < argc; ++i) {
    zval *sub_type = PHP5TO7_ZVAL_ARG(args[i]);
    if (php_cassandra_type_tuple_add(type, sub_type TSRMLS_CC)) {
      Z_ADDREF_P(sub_type);
    } else {
      break;
    }
  }

  PHP5TO7_MAYBE_EFREE(args);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(ztype), 0, 1);
}

PHP_METHOD(Type, userType)
{
  php5to7_zval ztype;
  cassandra_type *type;
  php5to7_zval_args args = NULL;
  int argc = 0, i;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "+",
                            &args, &argc) == FAILURE) {
    return;
  }

  if (argc % 2 == 1) {
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                            "Not enough name/type pairs, user types can only be created " \
                            "from an even number of name/type pairs, where each odd " \
                            "argument is a name and each even argument is a type, " \
                            "e.g userType(name, type, name, type, name, type)");
    PHP5TO7_MAYBE_EFREE(args);
    return;
  }

  for (i = 0; i < argc; i += 2) {
    zval *name = PHP5TO7_ZVAL_ARG(args[i]);
    zval *sub_type = PHP5TO7_ZVAL_ARG(args[i + 1]);
    if (Z_TYPE_P(name) != IS_STRING) {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                              "Argument %d is not a string", i + 1);
      PHP5TO7_MAYBE_EFREE(args);
      return;
    }
    if (!php_cassandra_type_validate(sub_type, "type" TSRMLS_CC)) {
      PHP5TO7_MAYBE_EFREE(args);
      return;
    }
  }

  ztype = php_cassandra_type_user_type(TSRMLS_C);
  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(ztype));

  for (i = 0; i < argc; i += 2) {
    zval *name = PHP5TO7_ZVAL_ARG(args[i]);
    zval *sub_type = PHP5TO7_ZVAL_ARG(args[i + 1]);
    if (php_cassandra_type_user_type_add(type,
                                         Z_STRVAL_P(name), Z_STRLEN_P(name),
                                         sub_type TSRMLS_CC)) {
      Z_ADDREF_P(sub_type);
    } else {
      break;
    }
  }


  PHP5TO7_MAYBE_EFREE(args);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(ztype), 0, 1);
}

PHP_METHOD(Type, set)
{
  php5to7_zval ztype;
  zval *value_type;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "O",
                            &value_type, cassandra_type_ce) == FAILURE) {
    return;
  }

  if (!php_cassandra_type_validate(value_type, "type" TSRMLS_CC)) {
    return;
  }

  ztype = php_cassandra_type_set(value_type TSRMLS_CC);
  Z_ADDREF_P(value_type);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(ztype), 0, 1);
}

PHP_METHOD(Type, map)
{
  php5to7_zval ztype;
  zval *key_type;
  zval *value_type;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "OO",
                            &key_type, cassandra_type_ce,
                            &value_type, cassandra_type_ce) == FAILURE) {
    return;
  }

  if (!php_cassandra_type_validate(key_type, "keyType" TSRMLS_CC)) {
    return;
  }

  if (!php_cassandra_type_validate(value_type, "valueType" TSRMLS_CC)) {
    return;
  }

  ztype = php_cassandra_type_map(key_type, value_type TSRMLS_CC);
  Z_ADDREF_P(key_type);
  Z_ADDREF_P(value_type);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(ztype), 0, 1);
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_types, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, types)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_values, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, values)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_type, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_OBJ_INFO(0, type, Cassandra\\Type, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_map, 0, ZEND_RETURN_VALUE, 2)
  ZEND_ARG_OBJ_INFO(0, keyType,   Cassandra\\Type, 0)
  ZEND_ARG_OBJ_INFO(0, valueType, Cassandra\\Type, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_type_methods[] = {
  PHP_ABSTRACT_ME(Type, name,       arginfo_none)
  PHP_ABSTRACT_ME(Type, __toString, arginfo_none)
  PHP_ABSTRACT_ME(Type, create,     arginfo_values)

#define XX_SCALAR_METHOD(name, _) PHP_ME(Type, name, arginfo_none, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC|ZEND_ACC_FINAL)
  PHP_CASSANDRA_SCALAR_TYPES_MAP(XX_SCALAR_METHOD)
#undef XX_SCALAR_METHOD
  PHP_ME(Type, collection, arginfo_type,  ZEND_ACC_PUBLIC|ZEND_ACC_STATIC|ZEND_ACC_FINAL)
  PHP_ME(Type, set,        arginfo_type,  ZEND_ACC_PUBLIC|ZEND_ACC_STATIC|ZEND_ACC_FINAL)
  PHP_ME(Type, map,        arginfo_map,   ZEND_ACC_PUBLIC|ZEND_ACC_STATIC|ZEND_ACC_FINAL)
  PHP_ME(Type, tuple,      arginfo_types, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC|ZEND_ACC_FINAL)
  PHP_ME(Type, userType,   arginfo_types, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC|ZEND_ACC_FINAL)
  PHP_FE_END
};

void cassandra_define_Type(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Type", cassandra_type_methods);
  cassandra_type_ce = zend_register_internal_class(&ce TSRMLS_CC);
  cassandra_type_ce->ce_flags |= ZEND_ACC_ABSTRACT;
}
