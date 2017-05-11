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
#include <gmp.h>
#include <float.h>
#include <math.h>
#include <ext/spl/spl_exceptions.h>

zend_class_entry *cassandra_decimal_ce = NULL;

static void
to_mpf(mpf_t result, cassandra_numeric *decimal)
{
  mpf_t scale_factor;
  long scale;
  /* result = unscaled * pow(10, -scale) */
  mpf_set_z(result, decimal->decimal_value);

  scale = decimal->decimal_scale;
  mpf_init_set_si(scale_factor, 10);
  mpf_pow_ui(scale_factor, scale_factor, scale < 0 ? -scale : scale);

  if (scale > 0) {
    mpf_ui_div(scale_factor, 1, scale_factor);
  }

  mpf_mul(result, result, scale_factor);

  mpf_clear(scale_factor);
}

/*
 * IEEE 754 double precision floating point representation:
 *
 *  S   EEEEEEEEEEE  MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
 * [63][  62 - 52  ][                    51 - 0                          ]
 *
 * S = sign bit
 * E = exponent
 * M = mantissa
 */
#define DOUBLE_MANTISSA_BITS 52
#define DOUBLE_MANITSSA_MASK (cass_int64_t) ((1LL << DOUBLE_MANTISSA_BITS) - 1)
#define DOUBLE_EXPONENT_BITS 11
#define DOUBLE_EXPONENT_MASK (cass_int64_t) ((1LL << DOUBLE_EXPONENT_BITS) - 1)

static void
from_double(cassandra_numeric *result, double value)
{
  cass_int64_t raw = (cass_int64_t) value;
  cass_int64_t mantissa = raw & DOUBLE_MANITSSA_MASK;
  cass_int64_t exponent = (raw >> DOUBLE_MANTISSA_BITS) & DOUBLE_EXPONENT_MASK;
  char mantissa_str[32];

  /* This exponent is offset using 1023 unless it's a denormal value then its value
   * is the minimum value -1022
   */
  int denormal;
  if (exponent == 0) {
    /* If the exponent is a zero then we have a denormal (subnormal) number. These are numbers
     * that represent small values around 0.0. The mantissa has the form of 0.xxxxxxxx...
     *
     * http://en.wikipedia.org/wiki/Denormal_number
     */
    denormal = 1;
    exponent = -1022;
  } else {
    /* Normal number The mantissa has the form of 1.xxxxxxx... */
    denormal = 0;
    exponent -= 1023;
  }

  /* Move the factional parts in the mantissa to the exponent. The significand
   * represents fractional parts:
   *
   * S = 1 + B51 * 2^-51 + B50 * 2^-52 ... + B0
   *
   */
  exponent -= DOUBLE_MANTISSA_BITS;

  if (!denormal) {
    /* Normal numbers have an implied one i.e. 1.xxxxxx... */
    mantissa |= (1LL << DOUBLE_MANTISSA_BITS);
  }

  /* Remove trailing zeros and move them to the exponent */
  while (exponent < 0 && (mantissa & 1) == 0) {
    ++exponent;
    mantissa >>= 1;
  }

  /* There isn't any "long long" setter method  */
#ifdef _WIN32
  sprintf(mantissa_str, "%I64d", mantissa);
#else
  sprintf(mantissa_str, "%lld", mantissa);
#endif
  mpz_set_str(result->decimal_value, mantissa_str, 10);

  /* Change the sign if negative */
  if (raw < 0) {
    mpz_neg(result->decimal_value, result->decimal_value);
  }

  if (exponent < 0) {
    /* Convert from pow(2, exponent) to pow(10, exponent):
     *
     * mantissa * pow(2, exponent) equals
     * mantissa * (pow(10, exponent) / pow(5, exponent))
     */
    mpz_t pow_5;
    mpz_init(pow_5);
    mpz_ui_pow_ui(pow_5, 5, -exponent);
    mpz_mul(result->decimal_value, result->decimal_value, pow_5);
    mpz_clear(pow_5);
    result->decimal_scale = -exponent;
  } else {
    mpz_mul_2exp(result->decimal_value, result->decimal_value, exponent);
    result->decimal_scale = 0;
  }
}

static int
to_double(zval* result, cassandra_numeric *decimal TSRMLS_DC)
{
  mpf_t value;
  mpf_init(value);
  to_mpf(value, decimal);

  if (mpf_cmp_d(value, -DBL_MAX) < 0) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Value is too small");
    mpf_clear(value);
    return FAILURE;
  }

  if (mpf_cmp_d(value, DBL_MAX) > 0) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Value is too big");
    mpf_clear(value);
    return FAILURE;
  }

  ZVAL_DOUBLE(result, mpf_get_d(value));
  mpf_clear(value);
  return SUCCESS;
}

static int
to_long(zval* result, cassandra_numeric *decimal TSRMLS_DC)
{
  mpf_t value;
  mpf_init(value);
  to_mpf(value, decimal);

  if (mpf_cmp_si(value, LONG_MIN) < 0) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Value is too small");
    mpf_clear(value);
    return FAILURE;
  }

  if (mpf_cmp_si(value, LONG_MAX) > 0) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Value is too big");
    mpf_clear(value);
    return FAILURE;
  }

  ZVAL_LONG(result, mpf_get_si(value));
  mpf_clear(value);
  return SUCCESS;
}

static int
to_string(zval* result, cassandra_numeric *decimal TSRMLS_DC)
{
  char* string;
  int string_len;
  php_cassandra_format_decimal(decimal->decimal_value, decimal->decimal_scale, &string, &string_len);

  PHP5TO7_ZVAL_STRINGL(result, string, string_len);
  efree(string);

  return SUCCESS;
}

static void
align_decimals(cassandra_numeric *lhs, cassandra_numeric *rhs)
{
  mpz_t pow_10;
  mpz_init(pow_10);
  if (lhs->decimal_scale < rhs->decimal_scale) {
    mpz_ui_pow_ui(pow_10, 10, rhs->decimal_scale - lhs->decimal_scale);
    mpz_mul(lhs->decimal_value, lhs->decimal_value, pow_10);
  } else if (lhs->decimal_scale > rhs->decimal_scale) {
    mpz_ui_pow_ui(pow_10, 10, lhs->decimal_scale - rhs->decimal_scale);
    mpz_mul(rhs->decimal_value, rhs->decimal_value, pow_10);
  }
  mpz_clear(pow_10);
}

void
php_cassandra_decimal_init(INTERNAL_FUNCTION_PARAMETERS)
{
  cassandra_numeric *self;
  zval* value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &value) == FAILURE) {
    return;
  }

  if (getThis() && instanceof_function(Z_OBJCE_P(getThis()), cassandra_decimal_ce TSRMLS_CC)) {
    self = PHP_CASSANDRA_GET_NUMERIC(getThis());
  } else {
    object_init_ex(return_value, cassandra_decimal_ce);
    self = PHP_CASSANDRA_GET_NUMERIC(return_value);
  }

  if (Z_TYPE_P(value) == IS_LONG) {
    mpz_set_si(self->decimal_value, Z_LVAL_P(value));
    self->decimal_scale = 0;
  } else if (Z_TYPE_P(value) == IS_DOUBLE) {
    double val = Z_DVAL_P(value);
    if (zend_isnan(val) || zend_isinf(val)) {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                              "Value of NaN or +/- infinity is not supported");
      return;
    }
    from_double(self, val);
  } else if (Z_TYPE_P(value) == IS_STRING) {
    if (!php_cassandra_parse_decimal(Z_STRVAL_P(value), Z_STRLEN_P(value),
                                     &self->decimal_value, &self->decimal_scale TSRMLS_CC)) {
      return;
    }
  } else if (Z_TYPE_P(value) == IS_OBJECT &&
             instanceof_function(Z_OBJCE_P(value), cassandra_decimal_ce TSRMLS_CC)) {
    cassandra_numeric *decimal = PHP_CASSANDRA_GET_NUMERIC(value);
    mpz_set(self->decimal_value, decimal->decimal_value);
    self->decimal_scale = decimal->decimal_scale;
  } else {
    INVALID_ARGUMENT(value, "a long, a double, a numeric string or a " \
                            "Cassandra\\Decimal");
  }
}

/* {{{ Cassandra\Decimal::__construct(string) */
PHP_METHOD(Decimal, __construct)
{
  php_cassandra_decimal_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}
/* }}} */

/* {{{ Cassandra\Decimal::__toString() */
PHP_METHOD(Decimal, __toString)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_string(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Decimal::type() */
PHP_METHOD(Decimal, type)
{
  php5to7_zval type = php_cassandra_type_scalar(CASS_VALUE_TYPE_DECIMAL TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(type), 1, 1);
}
/* }}} */

/* {{{ Cassandra\Decimal::value() */
PHP_METHOD(Decimal, value)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  char* string;
  int string_len;
  php_cassandra_format_integer(self->decimal_value, &string, &string_len);

  PHP5TO7_RETVAL_STRINGL(string, string_len);
  efree(string);
}
/* }}} */

PHP_METHOD(Decimal, scale)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  RETURN_LONG(self->decimal_scale);
}

/* {{{ Cassandra\Decimal::add() */
PHP_METHOD(Decimal, add)
{
  zval* num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_decimal_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *decimal = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_decimal_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    align_decimals(self, decimal);
    mpz_add(result->decimal_value, self->decimal_value, decimal->decimal_value);
    result->decimal_scale = MAX(self->decimal_scale, decimal->decimal_scale);
  } else {
    INVALID_ARGUMENT(num, "a Cassandra\\Decimal");
  }
}
/* }}} */

/* {{{ Cassandra\Decimal::sub() */
PHP_METHOD(Decimal, sub)
{
  zval* num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_decimal_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *decimal = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_decimal_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    align_decimals(self, decimal);
    mpz_sub(result->decimal_value, self->decimal_value, decimal->decimal_value);
    result->decimal_scale = MAX(self->decimal_scale, decimal->decimal_scale);
  } else {
    INVALID_ARGUMENT(num, "a Cassandra\\Decimal");
  }
}
/* }}} */

/* {{{ Cassandra\Decimal::mul() */
PHP_METHOD(Decimal, mul)
{
  zval* num;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &num) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(num) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(num), cassandra_decimal_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *decimal = PHP_CASSANDRA_GET_NUMERIC(num);

    object_init_ex(return_value, cassandra_decimal_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    mpz_mul(result->decimal_value, self->decimal_value, decimal->decimal_value);
    result->decimal_scale = self->decimal_scale + decimal->decimal_scale;
  } else {
    INVALID_ARGUMENT(num, "a Cassandra\\Decimal");
  }
}
/* }}} */

/* {{{ Cassandra\Decimal::div() */
PHP_METHOD(Decimal, div)
{
  /* TODO: Implementation of this a bit more difficult than anticipated. */
  zend_throw_exception_ex(cassandra_runtime_exception_ce, 0 TSRMLS_CC, "Not implemented");
}
/* }}} */

/* {{{ Cassandra\Decimal::mod() */
PHP_METHOD(Decimal, mod)
{
  /* TODO: We could implement a remainder method */
  zend_throw_exception_ex(cassandra_runtime_exception_ce, 0 TSRMLS_CC, "Not implemented");
}

/* {{{ Cassandra\Decimal::abs() */
PHP_METHOD(Decimal, abs)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  object_init_ex(return_value, cassandra_decimal_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);

  mpz_abs(result->decimal_value, self->decimal_value);
  result->decimal_scale = self->decimal_scale;
}
/* }}} */

/* {{{ Cassandra\Decimal::neg() */
PHP_METHOD(Decimal, neg)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  object_init_ex(return_value, cassandra_decimal_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);

  mpz_neg(result->decimal_value, self->decimal_value);
  result->decimal_scale = self->decimal_scale;
}
/* }}} */

/* {{{ Cassandra\Decimal::sqrt() */
PHP_METHOD(Decimal, sqrt)
{
  zend_throw_exception_ex(cassandra_runtime_exception_ce, 0 TSRMLS_CC, "Not implemented");
#if 0
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  mpf_t value;
  mpf_init(value);
  to_mpf(value, self);

  mpf_sqrt(value, value);

  mp_exp_t exponent;
  char* mantissa = mpf_get_str(NULL, &exponent, 10, 0, value);

  object_init_ex(return_value, cassandra_decimal_ce);
  cassandra_numeric *result = PHP_CASSANDRA_GET_NUMERIC(return_value);

  mpz_set_str(result->decimal_value, mantissa, 10);
  mp_bitcnt_t prec = mpf_get_prec(value);
  exponent -= prec;
  result->decimal_scale = -exponent;

  free(mantissa);
  mpf_clear(value);
#endif
}
/* }}} */

/* {{{ Cassandra\Decimal::toInt() */
PHP_METHOD(Decimal, toInt)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_long(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Decimal::toDouble() */
PHP_METHOD(Decimal, toDouble)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_double(return_value, self TSRMLS_CC);
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

static zend_function_entry cassandra_decimal_methods[] = {
  PHP_ME(Decimal, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, __toString, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, value, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, scale, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, add, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, sub, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, mul, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, div, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, mod, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, abs, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, neg, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, sqrt, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, toInt, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Decimal, toDouble, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_decimal_handlers;

static HashTable*
php_cassandra_decimal_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable*
php_cassandra_decimal_properties(zval *object TSRMLS_DC)
{
  char* string;
  int string_len;
  php5to7_zval type;
  php5to7_zval value;
  php5to7_zval scale;

  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(object);
  HashTable         *props = zend_std_get_properties(object TSRMLS_CC);

  type = php_cassandra_type_scalar(CASS_VALUE_TYPE_DECIMAL TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "type", sizeof("type"), PHP5TO7_ZVAL_MAYBE_P(type), sizeof(zval));

  php_cassandra_format_integer(self->decimal_value, &string, &string_len);
  PHP5TO7_ZVAL_MAYBE_MAKE(PHP5TO7_ZVAL_MAYBE_P(value));
  PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_P(value), string, string_len);
  efree(string);
  PHP5TO7_ZEND_HASH_UPDATE(props, "value", sizeof("value"), PHP5TO7_ZVAL_MAYBE_P(value), sizeof(zval));

  PHP5TO7_ZVAL_MAYBE_MAKE(scale);
  ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(scale), self->decimal_scale);
  PHP5TO7_ZEND_HASH_UPDATE(props, "scale", sizeof("scale"), PHP5TO7_ZVAL_MAYBE_P(scale), sizeof(zval));

  return props;
}

static int
php_cassandra_decimal_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_numeric *decimal1 = NULL;
  cassandra_numeric *decimal2 = NULL;

  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  decimal1 = PHP_CASSANDRA_GET_NUMERIC(obj1);
  decimal2 = PHP_CASSANDRA_GET_NUMERIC(obj2);

  if (decimal1->decimal_scale == decimal2->decimal_scale) {
    return mpz_cmp(decimal1->decimal_value, decimal2->decimal_value);
  } else if (decimal1->decimal_scale < decimal2->decimal_scale) {
    return -1;
  } else {
    return 1;
  }
}

static unsigned
php_cassandra_decimal_hash_value(zval *obj TSRMLS_DC)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(obj);
  return php_cassandra_mpz_hash((unsigned)self->decimal_scale, self->decimal_value);
}

static int
php_cassandra_decimal_cast(zval *object, zval *retval, int type TSRMLS_DC)
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
php_cassandra_decimal_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_numeric *self = PHP5TO7_ZEND_OBJECT_GET(numeric, object);

  mpz_clear(self->decimal_value);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_decimal_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_numeric *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(numeric, ce);

  self->type = CASSANDRA_DECIMAL;
  self->decimal_scale = 0;
  mpz_init(self->decimal_value);

  PHP5TO7_ZEND_OBJECT_INIT_EX(numeric, decimal, self, ce);
}

void cassandra_define_Decimal(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Decimal", cassandra_decimal_methods);
  cassandra_decimal_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_decimal_ce TSRMLS_CC, 2, cassandra_value_ce, cassandra_numeric_ce);
  cassandra_decimal_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_decimal_ce->create_object = php_cassandra_decimal_new;

  memcpy(&cassandra_decimal_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_decimal_handlers.std.get_properties  = php_cassandra_decimal_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_decimal_handlers.std.get_gc          = php_cassandra_decimal_gc;
#endif
  cassandra_decimal_handlers.std.compare_objects = php_cassandra_decimal_compare;
  cassandra_decimal_handlers.std.cast_object     = php_cassandra_decimal_cast;

  cassandra_decimal_handlers.hash_value = php_cassandra_decimal_hash_value;
  cassandra_decimal_handlers.std.clone_obj = NULL;
}
