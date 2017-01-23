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
#include "util/math.h"
#include "util/types.h"

#if !defined(HAVE_STDINT_H) && !defined(_MSC_STDINT_H_)
#  define INT64_MAX 9223372036854775807LL
#  define INT64_MIN (-INT64_MAX-1)
#endif

zend_class_entry *cassandra_bigint_ce = NULL;

static int
to_double(zval *result, cassandra_numeric *bigint TSRMLS_DC)
{
  ZVAL_DOUBLE(result, (double) bigint->bigint_value);
  return SUCCESS;
}

static int
to_long(zval *result, cassandra_numeric *bigint TSRMLS_DC)
{
  if (bigint->bigint_value < (cass_int64_t) LONG_MIN) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Value is too small");
    return FAILURE;
  }

  if (bigint->bigint_value > (cass_int64_t) LONG_MAX) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Value is too big");
    return FAILURE;
  }

  ZVAL_LONG(result, (long) bigint->bigint_value);
  return SUCCESS;
}

static int
to_string(zval *result, cassandra_numeric *bigint TSRMLS_DC)
{
  char *string;
#ifdef WIN32
  spprintf(&string, 0, "%I64d", (long long int) bigint->bigint_value);
#else
  spprintf(&string, 0, "%lld", (long long int) bigint->bigint_value);
#endif
  PHP5TO7_ZVAL_STRING(result, string);
  efree(string);
  return SUCCESS;
}

void
php_cassandra_bigint_init(INTERNAL_FUNCTION_PARAMETERS)
{
  cassandra_numeric *self;
  zval *value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &value) == FAILURE) {
    return;
  }

  if (getThis() && instanceof_function(Z_OBJCE_P(getThis()), cassandra_bigint_ce TSRMLS_CC)) {
    self = PHP_CASSANDRA_GET_NUMERIC(getThis());
  } else {
    object_init_ex(return_value, cassandra_bigint_ce);
    self = PHP_CASSANDRA_GET_NUMERIC(return_value);
  }

  if (Z_TYPE_P(value) == IS_LONG) {
    self->bigint_value = (cass_int64_t) Z_LVAL_P(value);
  } else if (Z_TYPE_P(value) == IS_DOUBLE) {
    self->bigint_value = (cass_int64_t) Z_DVAL_P(value);
  } else if (Z_TYPE_P(value) == IS_STRING) {
    if (!php_cassandra_parse_bigint(Z_STRVAL_P(value), Z_STRLEN_P(value),
                                    &self->bigint_value TSRMLS_CC)) {
      return;
    }
  } else if (Z_TYPE_P(value) == IS_OBJECT &&
             instanceof_function(Z_OBJCE_P(value), cassandra_bigint_ce TSRMLS_CC)) {
    cassandra_numeric *bigint = PHP_CASSANDRA_GET_NUMERIC(value);
    self->bigint_value = bigint->bigint_value;
  } else {
    INVALID_ARGUMENT(value, "a long, a double, a numeric string or a " \
                            "Cassandra\\Bigint");
  }
}

/* {{{ Cassandra\Bigint::__construct(string) */
PHP_METHOD(Bigint, __construct)
{
  php_cassandra_bigint_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}
/* }}} */

/* {{{ Cassandra\Bigint::__toString() */
PHP_METHOD(Bigint, __toString)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_string(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Bigint::type() */
PHP_METHOD(Bigint, type)
{
  php5to7_zval type = php_cassandra_type_scalar(CASS_VALUE_TYPE_BIGINT TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(type), 1, 1);
}
/* }}} */

/* {{{ Cassandra\Bigint::value() */
PHP_METHOD(Bigint, value)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_string(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Bigint::add() */
PHP_METHOD(Bigint, add)
{
  zval *num;
  cassandra_numeric *self;
  cassandra_numeric *bigint;
  cassandra_numeric *result;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_bigint_ce TSRMLS_CC)) {
    self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    bigint = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_bigint_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    result->bigint_value = self->bigint_value + bigint->bigint_value;
  } else {
    INVALID_ARGUMENT(num, "a Cassandra\\Bigint");
  }
}
/* }}} */

/* {{{ Cassandra\Bigint::sub() */
PHP_METHOD(Bigint, sub)
{
  zval *num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_bigint_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *bigint = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_bigint_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    result->bigint_value = self->bigint_value - bigint->bigint_value;
  } else {
    INVALID_ARGUMENT(num, "a Cassandra\\Bigint");
  }
}
/* }}} */

/* {{{ Cassandra\Bigint::mul() */
PHP_METHOD(Bigint, mul)
{
  zval *num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_bigint_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *bigint = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_bigint_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    result->bigint_value = self->bigint_value * bigint->bigint_value;
  } else {
    INVALID_ARGUMENT(num, "a Cassandra\\Bigint");
  }
}
/* }}} */

/* {{{ Cassandra\Bigint::div() */
PHP_METHOD(Bigint, div)
{
  zval *num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_bigint_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *bigint = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_bigint_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    if (bigint->bigint_value == 0) {
      zend_throw_exception_ex(cassandra_divide_by_zero_exception_ce, 0 TSRMLS_CC, "Cannot divide by zero");
      return;
    }

    result->bigint_value = self->bigint_value / bigint->bigint_value;
  } else {
    INVALID_ARGUMENT(num, "a Cassandra\\Bigint");
  }
}
/* }}} */

/* {{{ Cassandra\Bigint::mod() */
PHP_METHOD(Bigint, mod)
{
  zval *num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_bigint_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *bigint = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_bigint_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    if (bigint->bigint_value == 0) {
      zend_throw_exception_ex(cassandra_divide_by_zero_exception_ce, 0 TSRMLS_CC, "Cannot modulo by zero");
      return;
    }

    result->bigint_value = self->bigint_value % bigint->bigint_value;
  } else {
    INVALID_ARGUMENT(num, "a Cassandra\\Bigint");
  }
}
/* }}} */

/* {{{ Cassandra\Bigint::abs() */
PHP_METHOD(Bigint, abs)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  if (self->bigint_value == INT64_MIN) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Value doesn't exist");
    return;
  }

  object_init_ex(return_value, cassandra_bigint_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);
  result->bigint_value = self->bigint_value < 0 ? -self->bigint_value : self->bigint_value;
}
/* }}} */

/* {{{ Cassandra\Bigint::neg() */
PHP_METHOD(Bigint, neg)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  object_init_ex(return_value, cassandra_bigint_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);
  result->bigint_value = -self->bigint_value;
}
/* }}} */

/* {{{ Cassandra\Bigint::sqrt() */
PHP_METHOD(Bigint, sqrt)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  if (self->bigint_value < 0) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC,
                            "Cannot take a square root of a negative number");
  }

  object_init_ex(return_value, cassandra_bigint_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);
  result->bigint_value = (cass_int64_t) sqrt((long double) self->bigint_value);
}
/* }}} */

/* {{{ Cassandra\Bigint::toInt() */
PHP_METHOD(Bigint, toInt)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_long(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Bigint::toDouble() */
PHP_METHOD(Bigint, toDouble)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_double(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Bigint::min() */
PHP_METHOD(Bigint, min)
{
  cassandra_numeric *bigint = NULL;
  object_init_ex(return_value, cassandra_bigint_ce);
  bigint = PHP_CASSANDRA_GET_NUMERIC(return_value);
  bigint->bigint_value = INT64_MIN;
}
/* }}} */

/* {{{ Cassandra\Bigint::max() */
PHP_METHOD(Bigint, max)
{
  cassandra_numeric *bigint = NULL;
  object_init_ex(return_value, cassandra_bigint_ce);
  bigint = PHP_CASSANDRA_GET_NUMERIC(return_value);
  bigint->bigint_value = INT64_MAX;
}
/* }}} */

ZEND_BEGIN_ARG_INFO_EX(arginfo__construct, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_num, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, num)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_bigint_methods[] = {
  PHP_ME(Bigint, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, __toString, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, value, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, add, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, sub, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, mul, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, div, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, mod, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, abs, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, neg, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, sqrt, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, toInt, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, toDouble, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Bigint, min, arginfo_none, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
  PHP_ME(Bigint, max, arginfo_none, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_bigint_handlers;

static HashTable *
php_cassandra_bigint_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_bigint_properties(zval *object TSRMLS_DC)
{
  php5to7_zval type;
  php5to7_zval value;

  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(object);
  HashTable         *props = zend_std_get_properties(object TSRMLS_CC);

  type = php_cassandra_type_scalar(CASS_VALUE_TYPE_BIGINT TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "type", sizeof("type"), PHP5TO7_ZVAL_MAYBE_P(type), sizeof(zval));

  PHP5TO7_ZVAL_MAYBE_MAKE(value);
  to_string(PHP5TO7_ZVAL_MAYBE_P(value), self TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "value", sizeof("value"), PHP5TO7_ZVAL_MAYBE_P(value), sizeof(zval));

  return props;
}

static int
php_cassandra_bigint_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_numeric *bigint1 = NULL;
  cassandra_numeric *bigint2 = NULL;

  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  bigint1 = PHP_CASSANDRA_GET_NUMERIC(obj1);
  bigint2 = PHP_CASSANDRA_GET_NUMERIC(obj2);

  if (bigint1->bigint_value == bigint2->bigint_value)
    return 0;
  else if (bigint1->bigint_value < bigint2->bigint_value)
    return -1;
  else
    return 1;
}

static unsigned
php_cassandra_bigint_hash_value(zval *obj TSRMLS_DC)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(obj);
  return (unsigned)(self->bigint_value ^ (self->bigint_value >> 32));
}

static int
php_cassandra_bigint_cast(zval *object, zval *retval, int type TSRMLS_DC)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(object);

  switch (type) {
  case IS_LONG:
      return to_long(retval, self TSRMLS_CC);
  case IS_DOUBLE:
      return to_double(retval, self TSRMLS_CC);
  case IS_STRING:
      return to_string(retval, self TSRMLS_CC);
  default:
     return FAILURE;
  }

  return SUCCESS;
}

static void
php_cassandra_bigint_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_numeric *self = PHP5TO7_ZEND_OBJECT_GET(numeric, object);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_bigint_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_numeric *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(numeric, ce);

  self->type = CASSANDRA_BIGINT;

  PHP5TO7_ZEND_OBJECT_INIT_EX(numeric, bigint, self, ce);
}

void cassandra_define_Bigint(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Bigint", cassandra_bigint_methods);
  cassandra_bigint_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_bigint_ce TSRMLS_CC, 2, cassandra_value_ce, cassandra_numeric_ce);
  cassandra_bigint_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_bigint_ce->create_object = php_cassandra_bigint_new;

  memcpy(&cassandra_bigint_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_bigint_handlers.std.get_properties  = php_cassandra_bigint_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_bigint_handlers.std.get_gc          = php_cassandra_bigint_gc;
#endif
  cassandra_bigint_handlers.std.compare_objects = php_cassandra_bigint_compare;
  cassandra_bigint_handlers.std.cast_object     = php_cassandra_bigint_cast;

  cassandra_bigint_handlers.hash_value = php_cassandra_bigint_hash_value;
  cassandra_bigint_handlers.std.clone_obj = NULL;
}
