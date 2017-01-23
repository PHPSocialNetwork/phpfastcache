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
#include "util/math.h"
#include "util/types.h"
#include <float.h>

zend_class_entry *cassandra_float_ce = NULL;

static int
to_string(zval *result, cassandra_numeric *flt TSRMLS_DC)
{
  char *string;
  spprintf(&string, 0, "%.*F", (int) EG(precision), flt->float_value);
  PHP5TO7_ZVAL_STRING(result, string);
  efree(string);
  return SUCCESS;
}

void
php_cassandra_float_init(INTERNAL_FUNCTION_PARAMETERS)
{
  cassandra_numeric *self;
  zval *value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &value) == FAILURE) {
    return;
  }

  if (getThis() && instanceof_function(Z_OBJCE_P(getThis()), cassandra_float_ce TSRMLS_CC)) {
    self = PHP_CASSANDRA_GET_NUMERIC(getThis());
  } else {
    object_init_ex(return_value, cassandra_float_ce);
    self = PHP_CASSANDRA_GET_NUMERIC(return_value);
  }

  if (Z_TYPE_P(value) == IS_LONG) {
    self->float_value = (cass_float_t) Z_LVAL_P(value);
  } else if (Z_TYPE_P(value) == IS_DOUBLE) {
    self->float_value = (cass_float_t) Z_DVAL_P(value);
  } else if (Z_TYPE_P(value) == IS_STRING) {
    if (!php_cassandra_parse_float(Z_STRVAL_P(value), Z_STRLEN_P(value),
                                   &self->float_value TSRMLS_CC)) {
      return;
    }
  } else if (Z_TYPE_P(value) == IS_OBJECT &&
             instanceof_function(Z_OBJCE_P(value), cassandra_float_ce TSRMLS_CC)) {
    cassandra_numeric *flt = PHP_CASSANDRA_GET_NUMERIC(return_value);
    self->float_value = flt->float_value;
  } else {
    INVALID_ARGUMENT(value, "a long, double, numeric string or a " \
                            "Cassandra\\Float instance");
  }
}

/* {{{ Cassandra\Float::__construct(string) */
PHP_METHOD(Float, __construct)
{
  php_cassandra_float_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}
/* }}} */

/* {{{ Cassandra\Float::__toString() */
PHP_METHOD(Float, __toString)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_string(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Float::type() */
PHP_METHOD(Float, type)
{
  php5to7_zval type = php_cassandra_type_scalar(CASS_VALUE_TYPE_FLOAT TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(type), 1, 1);
}
/* }}} */

/* {{{ Cassandra\Float::value() */
PHP_METHOD(Float, value)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
  RETURN_DOUBLE((double) self->float_value);
}
/* }}} */

/* {{{ Cassandra\Float::isInfinite() */
PHP_METHOD(Float, isInfinite)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
  RETURN_BOOL(zend_isinf(self->float_value));
}
/* }}} */

/* {{{ Cassandra\Float::isFinite() */
PHP_METHOD(Float, isFinite)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
  RETURN_BOOL(zend_finite(self->float_value));
}
/* }}} */

/* {{{ Cassandra\Float::isNaN() */
PHP_METHOD(Float, isNaN)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
  RETURN_BOOL(zend_isnan(self->float_value));
}
/* }}} */

/* {{{ Cassandra\Float::add() */
PHP_METHOD(Float, add)
{
  zval *num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_float_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *flt = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_float_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    result->float_value = self->float_value + flt->float_value;
  } else {
    INVALID_ARGUMENT(num, "an instance of Cassandra\\Float");
  }
}
/* }}} */

/* {{{ Cassandra\Float::sub() */
PHP_METHOD(Float, sub)
{
  zval *num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_float_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *flt = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_float_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    result->float_value = self->float_value - flt->float_value;
  } else {
    INVALID_ARGUMENT(num, "an instance of Cassandra\\Float");
  }
}
/* }}} */

/* {{{ Cassandra\Float::mul() */
PHP_METHOD(Float, mul)
{
  zval *num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_float_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *flt = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_float_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    result->float_value = self->float_value * flt->float_value;
  } else {
    INVALID_ARGUMENT(num, "an instance of Cassandra\\Float");
  }
}
/* }}} */

/* {{{ Cassandra\Float::div() */
PHP_METHOD(Float, div)
{
  zval *num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_float_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *flt = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_float_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    if (flt->float_value == 0) {
      zend_throw_exception_ex(cassandra_divide_by_zero_exception_ce, 0 TSRMLS_CC, "Cannot divide by zero");
      return;
    }

    result->float_value = self->float_value / flt->float_value;
  } else {
    INVALID_ARGUMENT(num, "an instance of Cassandra\\Float");
  }
}
/* }}} */

/* {{{ Cassandra\Float::mod() */
PHP_METHOD(Float, mod)
{
  zval *num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_float_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *flt = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_float_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    if (flt->float_value == 0) {
      zend_throw_exception_ex(cassandra_divide_by_zero_exception_ce, 0 TSRMLS_CC, "Cannot divide by zero");
      return;
    }

    result->float_value = fmod(self->float_value, flt->float_value);
  } else {
    INVALID_ARGUMENT(num, "an instance of Cassandra\\Float");
  }
}

/* {{{ Cassandra\Float::abs() */
PHP_METHOD(Float, abs)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
  object_init_ex(return_value, cassandra_float_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);
  result->float_value = fabsf(self->float_value);
}
/* }}} */

/* {{{ Cassandra\Float::neg() */
PHP_METHOD(Float, neg)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
  object_init_ex(return_value, cassandra_float_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);
  result->float_value = -self->float_value;
}
/* }}} */

/* {{{ Cassandra\Float::sqrt() */
PHP_METHOD(Float, sqrt)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  if (self->float_value < 0) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC,
                            "Cannot take a square root of a negative number");
  }

  object_init_ex(return_value, cassandra_float_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);
  result->float_value = sqrtf(self->float_value);
}
/* }}} */

/* {{{ Cassandra\Float::toInt() */
PHP_METHOD(Float, toInt)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  RETURN_LONG((long) self->float_value);
}
/* }}} */

/* {{{ Cassandra\Float::toDouble() */
PHP_METHOD(Float, toDouble)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  RETURN_DOUBLE((double) self->float_value);
}
/* }}} */

/* {{{ Cassandra\Float::min() */
PHP_METHOD(Float, min)
{
  cassandra_numeric *flt = NULL;
  object_init_ex(return_value, cassandra_float_ce);
  flt = PHP_CASSANDRA_GET_NUMERIC(return_value);
  flt->float_value = FLT_MIN;
}
/* }}} */

/* {{{ Cassandra\Float::max() */
PHP_METHOD(Float, max)
{
  cassandra_numeric *flt = NULL;
  object_init_ex(return_value, cassandra_float_ce);
  flt = PHP_CASSANDRA_GET_NUMERIC(return_value);
  flt->float_value = FLT_MAX;
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

static zend_function_entry cassandra_float_methods[] = {
  PHP_ME(Float, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Float, __toString, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, value, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, isInfinite, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, isFinite, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, isNaN, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, add, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Float, sub, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Float, mul, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Float, div, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Float, mod, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Float, abs, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, neg, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, sqrt, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, toInt, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, toDouble, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Float, min, arginfo_none, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
  PHP_ME(Float, max, arginfo_none, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_float_handlers;

static HashTable *
php_cassandra_float_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_float_properties(zval *object TSRMLS_DC)
{
  php5to7_zval type;
  php5to7_zval value;

  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(object);
  HashTable         *props = zend_std_get_properties(object TSRMLS_CC);

  type = php_cassandra_type_scalar(CASS_VALUE_TYPE_FLOAT TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "type", sizeof("type"), PHP5TO7_ZVAL_MAYBE_P(type), sizeof(zval));

  PHP5TO7_ZVAL_MAYBE_MAKE(value);
  to_string(PHP5TO7_ZVAL_MAYBE_P(value), self TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "value", sizeof("value"), PHP5TO7_ZVAL_MAYBE_P(value), sizeof(zval));

  return props;
}

static inline cass_int32_t
float_to_bits(cass_float_t value) {
  cass_int32_t bits;
  if (zend_isnan(value)) return 0x7fc00000; /* A canonical NaN value */
  memcpy(&bits, &value, sizeof(cass_int32_t));
  return bits;
}

static int
php_cassandra_float_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cass_int32_t bits1, bits2;
  cassandra_numeric *flt1 = NULL;
  cassandra_numeric *flt2 = NULL;

  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  flt1 = PHP_CASSANDRA_GET_NUMERIC(obj1);
  flt2 = PHP_CASSANDRA_GET_NUMERIC(obj2);

  if (flt1->float_value < flt2->float_value) return -1;
  if (flt1->float_value > flt2->float_value) return  1;

  bits1 = float_to_bits(flt1->float_value);
  bits2 = float_to_bits(flt2->float_value);

  /* Handle NaNs and negative and positive 0.0 */
  return bits1 < bits2 ? -1 : bits1 > bits2;
}

static unsigned
php_cassandra_float_hash_value(zval *obj TSRMLS_DC)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(obj);
  return float_to_bits(self->float_value);
}

static int
php_cassandra_float_cast(zval *object, zval *retval, int type TSRMLS_DC)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(object);

  switch (type) {
  case IS_LONG:
      ZVAL_LONG(retval, (long) self->float_value);
      return SUCCESS;
  case IS_DOUBLE:
      ZVAL_DOUBLE(retval, (double) self->float_value);
      return SUCCESS;
  case IS_STRING:
      return to_string(retval, self TSRMLS_CC);
  default:
     return FAILURE;
  }

  return SUCCESS;
}

static void
php_cassandra_float_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_numeric *self = PHP5TO7_ZEND_OBJECT_GET(numeric, object);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_float_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_numeric *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(numeric, ce);

  PHP5TO7_ZEND_OBJECT_INIT_EX(numeric, float, self, ce);
}

void cassandra_define_Float(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Float", cassandra_float_methods);
  cassandra_float_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_float_ce TSRMLS_CC, 2, cassandra_value_ce, cassandra_numeric_ce);
  cassandra_float_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_float_ce->create_object = php_cassandra_float_new;

  memcpy(&cassandra_float_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_float_handlers.std.get_properties  = php_cassandra_float_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_float_handlers.std.get_gc          = php_cassandra_float_gc;
#endif
  cassandra_float_handlers.std.compare_objects = php_cassandra_float_compare;
  cassandra_float_handlers.std.cast_object     = php_cassandra_float_cast;

  cassandra_float_handlers.hash_value = php_cassandra_float_hash_value;
  cassandra_float_handlers.std.clone_obj = NULL;
}
