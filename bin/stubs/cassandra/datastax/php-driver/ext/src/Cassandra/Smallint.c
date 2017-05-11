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
#  define INT16_MAX 32767
#  define INT16_MIN (-INT16_MAX-1)
#endif

zend_class_entry *cassandra_smallint_ce = NULL;

static int
to_double(zval *result, cassandra_numeric *smallint TSRMLS_DC)
{
  ZVAL_DOUBLE(result, (double) smallint->smallint_value);
  return SUCCESS;
}

static int
to_long(zval *result, cassandra_numeric *smallint TSRMLS_DC)
{
  ZVAL_LONG(result, (long) smallint->smallint_value);
  return SUCCESS;
}

static int
to_string(zval *result, cassandra_numeric *smallint TSRMLS_DC)
{
  char *string;
  spprintf(&string, 0, "%d", smallint->smallint_value);
  PHP5TO7_ZVAL_STRING(result, string);
  efree(string);
  return SUCCESS;
}

void
php_cassandra_smallint_init(INTERNAL_FUNCTION_PARAMETERS)
{
  cassandra_numeric *self;
  zval *value;
  cass_int32_t number;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &value) == FAILURE) {
    return;
  }

  if (getThis() && instanceof_function(Z_OBJCE_P(getThis()), cassandra_smallint_ce TSRMLS_CC)) {
    self = PHP_CASSANDRA_GET_NUMERIC(getThis());
  } else {
    object_init_ex(return_value, cassandra_smallint_ce);
    self = PHP_CASSANDRA_GET_NUMERIC(return_value);
  }

  if (Z_TYPE_P(value) == IS_OBJECT &&
           instanceof_function(Z_OBJCE_P(value), cassandra_smallint_ce TSRMLS_CC)) {
    cassandra_numeric *other = PHP_CASSANDRA_GET_NUMERIC(value);
    self->smallint_value = other->smallint_value;
  } else {
    if (Z_TYPE_P(value) == IS_LONG) {
      number = (cass_int32_t) Z_LVAL_P(value);
    } else if (Z_TYPE_P(value) == IS_DOUBLE) {
      number = (cass_int32_t) Z_DVAL_P(value);
    } else if (Z_TYPE_P(value) == IS_STRING) {
      if (!php_cassandra_parse_int(Z_STRVAL_P(value), Z_STRLEN_P(value),
                                        &number TSRMLS_CC)) {
        return;
      }
    } else {
      INVALID_ARGUMENT(value, "a long, a double, a numeric string or a " \
                              "Cassandra\\Smallint");
    }
    if (number < INT16_MIN || number > INT16_MAX) {
      INVALID_ARGUMENT(value, ("between -32768 and 32767"));
    }
    self->smallint_value = (cass_int16_t) number;
  }
}


/* {{{ Cassandra\Smallint::__construct(string) */
PHP_METHOD(Smallint, __construct)
{
  php_cassandra_smallint_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}
/* }}} */

/* {{{ Cassandra\Smallint::__toString() */
PHP_METHOD(Smallint, __toString)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_string(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Smallint::type() */
PHP_METHOD(Smallint, type)
{
  php5to7_zval type = php_cassandra_type_scalar(CASS_VALUE_TYPE_SMALL_INT TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(type), 1, 1);
}
/* }}} */

/* {{{ Cassandra\Smallint::value() */
PHP_METHOD(Smallint, value)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_long(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Smallint::add() */
PHP_METHOD(Smallint, add)
{
  zval *addend;
  cassandra_numeric *self;
  cassandra_numeric *smallint;
  cassandra_numeric *result;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &addend) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(addend) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(addend), cassandra_smallint_ce TSRMLS_CC)) {
    self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    smallint = PHP_CASSANDRA_GET_NUMERIC(addend);

    object_init_ex(return_value, cassandra_smallint_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    result->smallint_value = self->smallint_value + smallint->smallint_value;
    if (result->smallint_value - smallint->smallint_value != self->smallint_value) {
      zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Sum is out of range");
      return;
    }
  } else {
    INVALID_ARGUMENT(addend, "a Cassandra\\Smallint");
  }
}
/* }}} */

/* {{{ Cassandra\Smallint::sub() */
PHP_METHOD(Smallint, sub)
{
  zval *difference;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &difference) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(difference) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(difference), cassandra_smallint_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *smallint = PHP_CASSANDRA_GET_NUMERIC(difference);

    object_init_ex(return_value, cassandra_smallint_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    result->smallint_value = self->smallint_value - smallint->smallint_value;
    if (result->smallint_value + smallint->smallint_value != self->smallint_value) {
      zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Difference is out of range");
      return;
    }
  } else {
    INVALID_ARGUMENT(difference, "a Cassandra\\Smallint");
  }
}
/* }}} */

/* {{{ Cassandra\Smallint::mul() */
PHP_METHOD(Smallint, mul)
{
  zval *multiplier;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &multiplier) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(multiplier) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(multiplier), cassandra_smallint_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *smallint = PHP_CASSANDRA_GET_NUMERIC(multiplier);

    object_init_ex(return_value, cassandra_smallint_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    result->smallint_value = self->smallint_value * smallint->smallint_value;
    if (smallint->smallint_value != 0 &&
        result->smallint_value / smallint->smallint_value != self->smallint_value) {
      zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Product is out of range");
      return;
    }
  } else {
    INVALID_ARGUMENT(multiplier, "a Cassandra\\Smallint");
  }
}
/* }}} */

/* {{{ Cassandra\Smallint::div() */
PHP_METHOD(Smallint, div)
{
  zval *divisor;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &divisor) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(divisor) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(divisor), cassandra_smallint_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *smallint = PHP_CASSANDRA_GET_NUMERIC(divisor);

    object_init_ex(return_value, cassandra_smallint_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    if (smallint->smallint_value == 0) {
      zend_throw_exception_ex(cassandra_divide_by_zero_exception_ce, 0 TSRMLS_CC, "Cannot divide by zero");
      return;
    }

    result->smallint_value = self->smallint_value / smallint->smallint_value;
    if (result->smallint_value * smallint->smallint_value != self->smallint_value) {
      zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Quotient is out of range");
      return;
    }
  } else {
    INVALID_ARGUMENT(divisor, "a Cassandra\\Smallint");
  }
}
/* }}} */

/* {{{ Cassandra\Smallint::mod() */
PHP_METHOD(Smallint, mod)
{
  zval *divisor;
  cassandra_numeric *result = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &divisor) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(divisor) == IS_OBJECT &&
      instanceof_function(Z_OBJCE_P(divisor), cassandra_smallint_ce TSRMLS_CC)) {
    cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());
    cassandra_numeric *smallint = PHP_CASSANDRA_GET_NUMERIC(divisor);

    object_init_ex(return_value, cassandra_smallint_ce);
    result = PHP_CASSANDRA_GET_NUMERIC(return_value);

    if (smallint->smallint_value == 0) {
      zend_throw_exception_ex(cassandra_divide_by_zero_exception_ce, 0 TSRMLS_CC, "Cannot modulo by zero");
      return;
    }

    result->smallint_value = self->smallint_value % smallint->smallint_value;
  } else {
    INVALID_ARGUMENT(divisor, "a Cassandra\\Smallint");
  }
}
/* }}} */

/* {{{ Cassandra\Smallint::abs() */
PHP_METHOD(Smallint, abs)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  if (self->smallint_value == INT16_MIN) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Value doesn't exist");
    return;
  }

  object_init_ex(return_value, cassandra_smallint_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);
  result->smallint_value = self->smallint_value < 0 ? -self->smallint_value : self->smallint_value;
}
/* }}} */

/* {{{ Cassandra\Smallint::neg() */
PHP_METHOD(Smallint, neg)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  if (self->smallint_value == INT16_MIN) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC, "Value doesn't exist");
    return;
  }

  object_init_ex(return_value, cassandra_smallint_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);
  result->smallint_value = -self->smallint_value;
}
/* }}} */

/* {{{ Cassandra\Smallint::sqrt() */
PHP_METHOD(Smallint, sqrt)
{
  cassandra_numeric *result = NULL;
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  if (self->smallint_value < 0) {
    zend_throw_exception_ex(cassandra_range_exception_ce, 0 TSRMLS_CC,
                            "Cannot take a square root of a negative number");
  }

  object_init_ex(return_value, cassandra_smallint_ce);
  result = PHP_CASSANDRA_GET_NUMERIC(return_value);
  result->smallint_value = (cass_int16_t) sqrt((long double) self->smallint_value);
}
/* }}} */

/* {{{ Cassandra\Smallint::toInt() */
PHP_METHOD(Smallint, toInt)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_long(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Smallint::toDouble() */
PHP_METHOD(Smallint, toDouble)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(getThis());

  to_double(return_value, self TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Smallint::min() */
PHP_METHOD(Smallint, min)
{
  cassandra_numeric *smallint = NULL;
  object_init_ex(return_value, cassandra_smallint_ce);
  smallint = PHP_CASSANDRA_GET_NUMERIC(return_value);
  smallint->smallint_value = INT16_MIN;
}
/* }}} */

/* {{{ Cassandra\Smallint::max() */
PHP_METHOD(Smallint, max)
{
  cassandra_numeric *smallint = NULL;
  object_init_ex(return_value, cassandra_smallint_ce);
  smallint = PHP_CASSANDRA_GET_NUMERIC(return_value);
  smallint->smallint_value = INT16_MAX;
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

static zend_function_entry cassandra_smallint_methods[] = {
  PHP_ME(Smallint, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, __toString, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, value, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, add, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, sub, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, mul, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, div, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, mod, arginfo_num, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, abs, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, neg, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, sqrt, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, toInt, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, toDouble, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Smallint, min, arginfo_none, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
  PHP_ME(Smallint, max, arginfo_none, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_smallint_handlers;

static HashTable *
php_cassandra_smallint_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_smallint_properties(zval *object TSRMLS_DC)
{
  php5to7_zval type;
  php5to7_zval value;

  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(object);
  HashTable         *props = zend_std_get_properties(object TSRMLS_CC);

  type = php_cassandra_type_scalar(CASS_VALUE_TYPE_SMALL_INT TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "type", sizeof("type"), PHP5TO7_ZVAL_MAYBE_P(type), sizeof(zval));

  PHP5TO7_ZVAL_MAYBE_MAKE(value);
  to_string(PHP5TO7_ZVAL_MAYBE_P(value), self TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "value", sizeof("value"), PHP5TO7_ZVAL_MAYBE_P(value), sizeof(zval));

  return props;
}

static int
php_cassandra_smallint_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_numeric *smallint1 = NULL;
  cassandra_numeric *smallint2 = NULL;

  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  smallint1 = PHP_CASSANDRA_GET_NUMERIC(obj1);
  smallint2 = PHP_CASSANDRA_GET_NUMERIC(obj2);

  if (smallint1->smallint_value == smallint2->smallint_value)
    return 0;
  else if (smallint1->smallint_value < smallint2->smallint_value)
    return -1;
  else
    return 1;
}

static unsigned
php_cassandra_smallint_hash_value(zval *obj TSRMLS_DC)
{
  cassandra_numeric *self = PHP_CASSANDRA_GET_NUMERIC(obj);
  return 31 * 17 + self->smallint_value;
}

static int
php_cassandra_smallint_cast(zval *object, zval *retval, int type TSRMLS_DC)
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
php_cassandra_smallint_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_numeric *self = PHP5TO7_ZEND_OBJECT_GET(numeric, object);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_smallint_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_numeric *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(numeric, ce);

  self->type = CASSANDRA_SMALLINT;

  PHP5TO7_ZEND_OBJECT_INIT_EX(numeric, smallint, self, ce);
}

void cassandra_define_Smallint(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Smallint", cassandra_smallint_methods);
  cassandra_smallint_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_smallint_ce TSRMLS_CC, 2, cassandra_value_ce, cassandra_numeric_ce);
  cassandra_smallint_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_smallint_ce->create_object = php_cassandra_smallint_new;

  memcpy(&cassandra_smallint_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_smallint_handlers.std.get_properties  = php_cassandra_smallint_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_smallint_handlers.std.get_gc          = php_cassandra_smallint_gc;
#endif
  cassandra_smallint_handlers.std.compare_objects = php_cassandra_smallint_compare;
  cassandra_smallint_handlers.std.cast_object     = php_cassandra_smallint_cast;

  cassandra_smallint_handlers.hash_value = php_cassandra_smallint_hash_value;
}
