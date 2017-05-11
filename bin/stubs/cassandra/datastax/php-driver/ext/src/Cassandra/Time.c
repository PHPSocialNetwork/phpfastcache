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
#include "util/types.h"
#include "util/math.h"
#include <time.h>
#include <ext/date/php_date.h>
#include <ext/date/lib/timelib.h>

zend_class_entry *cassandra_time_ce = NULL;

#if defined(_WIN32)
#ifndef _WINSOCKAPI_
#define _WINSOCKAPI_
#endif
#include <Windows.h>
#elif defined(__APPLE__) && defined(__MACH__)
#include <sys/time.h>
#else
#include <time.h>
#endif

#define NUM_NANOSECONDS_PER_DAY 86399999999999LL
#define NANOSECONDS_PER_SECOND 1000000000LL


#if defined(_WIN32)

cass_int64_t php_cassandra_time_now_ns() {
  FILETIME ft;
  cass_int64_t ns100;
  GetSystemTimeAsFileTime(&ft);
  ns100 = (((cass_int64_t) ft.dwHighDateTime) << 32 |
           (cass_int64_t) ft.dwLowDateTime) -
           116444736000000000LL; /* 100 nanosecond increments between */
  /* Jan. 1, 1601 - Jan. 1, 1970 */
  return (ns100 * 100) % NUM_NANOSECONDS_PER_DAY;
}

#elif defined(__APPLE__) && defined(__MACH__)

cass_int64_t php_cassandra_time_now_ns() {
  struct timeval tv;
  gettimeofday(&tv, NULL);
  return cass_time_from_epoch((cass_int64_t) tv.tv_sec) +
      (cass_int64_t) tv.tv_usec * 1000;
}

#else

cass_int64_t php_cassandra_time_now_ns() {
  struct timespec ts;
  clock_gettime(CLOCK_REALTIME, &ts);
  return cass_time_from_epoch((cass_int64_t) ts.tv_sec) +
      (cass_int64_t) ts.tv_nsec;
}

#endif

static int
to_string(zval *result, cassandra_time *time TSRMLS_DC)
{
  char *string;
#ifdef WIN32
  spprintf(&string, 0, "%I64d", (long long int) time->time);
#else
  spprintf(&string, 0, "%lld", (long long int) time->time);
#endif
  PHP5TO7_ZVAL_STRING(result, string);
  efree(string);
  return SUCCESS;
}

void
php_cassandra_time_init(INTERNAL_FUNCTION_PARAMETERS)
{
  zval *nanoseconds = NULL;
  cassandra_time *self;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|z", &nanoseconds) == FAILURE) {
    return;
  }

  if (getThis() && instanceof_function(Z_OBJCE_P(getThis()), cassandra_time_ce TSRMLS_CC)) {
    self = PHP_CASSANDRA_GET_TIME(getThis());
  } else {
    object_init_ex(return_value, cassandra_time_ce);
    self = PHP_CASSANDRA_GET_TIME(return_value);
  }

  if (nanoseconds == NULL) {
    self->time = php_cassandra_time_now_ns();
  } else {
    if (Z_TYPE_P(nanoseconds) == IS_LONG) {
      self->time = Z_LVAL_P(nanoseconds);
    } else if (Z_TYPE_P(nanoseconds) == IS_STRING) {
      if (!php_cassandra_parse_bigint(Z_STRVAL_P(nanoseconds), Z_STRLEN_P(nanoseconds),
                                      &self->time TSRMLS_CC)) {
        return;
      }
    } else {
      INVALID_ARGUMENT(nanoseconds, "a string or int representing a number of nanoseconds since midnight");
    }

    if (self->time < 0 || self->time > NUM_NANOSECONDS_PER_DAY) {
      INVALID_ARGUMENT(nanoseconds, "nanoseconds since midnight");
    }
  }
}

/* {{{ Cassandra\Time::__construct(string) */
PHP_METHOD(Time, __construct)
{
  php_cassandra_time_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}
/* }}} */

/* {{{ Cassandra\Time::type() */
PHP_METHOD(Time, type)
{
  php5to7_zval type = php_cassandra_type_scalar(CASS_VALUE_TYPE_TIME TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(type), 1, 1);
}
/* }}} */

/* {{{ Cassandra\Time::seconds() */
PHP_METHOD(Time, seconds)
{
  cassandra_time *self = PHP_CASSANDRA_GET_TIME(getThis());
  RETURN_LONG(self->time / NANOSECONDS_PER_SECOND);
}
/* }}} */

/* {{{ Cassandra\Time::fromDateTime() */
PHP_METHOD(Time, fromDateTime)
{
  cassandra_time *self;
  zval *zdatetime;
  php5to7_zval retval;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &zdatetime) == FAILURE) {
    return;
  }

  zend_call_method_with_0_params(PHP5TO7_ZVAL_MAYBE_ADDR_OF(zdatetime),
                                 php_date_get_date_ce(),
                                 NULL,
                                 "gettimestamp",
                                 &retval);

  if (!PHP5TO7_ZVAL_IS_UNDEF(retval) &&
      Z_TYPE_P(PHP5TO7_ZVAL_MAYBE_P(retval)) == IS_LONG) {
    object_init_ex(return_value, cassandra_time_ce);
    self = PHP_CASSANDRA_GET_TIME(return_value);
    self->time = cass_time_from_epoch(PHP5TO7_Z_LVAL_MAYBE_P(retval));
    zval_ptr_dtor(&retval);
    return;
  }
}
/* }}} */

/* {{{ Cassandra\Time::__toString() */
PHP_METHOD(Time, __toString)
{
  cassandra_time *self;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TIME(getThis());
  to_string(return_value, self TSRMLS_CC);
}
/* }}} */

ZEND_BEGIN_ARG_INFO_EX(arginfo__construct, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, seconds)
  ZEND_ARG_INFO(0, microseconds)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_datetime, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_OBJ_INFO(0, datetime, DateTime, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_time_methods[] = {
  PHP_ME(Time, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Time, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Time, seconds, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Time, fromDateTime, arginfo_datetime, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
  PHP_ME(Time, __toString, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_time_handlers;

static HashTable *
php_cassandra_time_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_time_properties(zval *object TSRMLS_DC)
{
  php5to7_zval type;
  php5to7_zval nanoseconds;

  cassandra_time *self = PHP_CASSANDRA_GET_TIME(object);
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  type = php_cassandra_type_scalar(CASS_VALUE_TYPE_TIME TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "type", sizeof("type"), PHP5TO7_ZVAL_MAYBE_P(type), sizeof(zval));

  PHP5TO7_ZVAL_MAYBE_MAKE(nanoseconds);
  to_string(PHP5TO7_ZVAL_MAYBE_P(nanoseconds), self TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "nanoseconds", sizeof("nanoseconds"), PHP5TO7_ZVAL_MAYBE_P(nanoseconds), sizeof(zval));

  return props;
}

static int
php_cassandra_time_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_time *time1 = NULL;
  cassandra_time *time2 = NULL;
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  time1 = PHP_CASSANDRA_GET_TIME(obj1);
  time2 = PHP_CASSANDRA_GET_TIME(obj2);

  return PHP_CASSANDRA_COMPARE(time1->time, time2->time);
}

static unsigned
php_cassandra_time_hash_value(zval *obj TSRMLS_DC)
{
  cassandra_time *self = PHP_CASSANDRA_GET_TIME(obj);
  return php_cassandra_bigint_hash(self->time);
}

static void
php_cassandra_time_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_time *self = PHP5TO7_ZEND_OBJECT_GET(time, object);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_time_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_time *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(time, ce);

  self->time = 0;

  PHP5TO7_ZEND_OBJECT_INIT(time, self, ce);
}

void cassandra_define_Time(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Time", cassandra_time_methods);
  cassandra_time_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_time_ce TSRMLS_CC, 1, cassandra_value_ce);
  memcpy(&cassandra_time_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_time_handlers.std.get_properties  = php_cassandra_time_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_time_handlers.std.get_gc          = php_cassandra_time_gc;
#endif
  cassandra_time_handlers.std.compare_objects = php_cassandra_time_compare;
  cassandra_time_ce->ce_flags |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_time_ce->create_object = php_cassandra_time_new;

  cassandra_time_handlers.hash_value = php_cassandra_time_hash_value;
}
