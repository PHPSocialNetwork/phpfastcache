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
#include <time.h>
#include <ext/date/php_date.h>

zend_class_entry *cassandra_date_ce = NULL;

void
php_cassandra_date_init(INTERNAL_FUNCTION_PARAMETERS)
{
  zval *seconds = NULL;
  cassandra_date *self;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|z", &seconds) == FAILURE) {
    return;
  }

  if (getThis() && instanceof_function(Z_OBJCE_P(getThis()), cassandra_date_ce TSRMLS_CC)) {
    self = PHP_CASSANDRA_GET_DATE(getThis());
  } else {
    object_init_ex(return_value, cassandra_date_ce);
    self = PHP_CASSANDRA_GET_DATE(return_value);
  }

  if (seconds == NULL) {
    self->date = cass_date_from_epoch(time(NULL));
  } else {
    if (Z_TYPE_P(seconds) != IS_LONG) {
      INVALID_ARGUMENT(seconds, "a number of seconds since the Unix Epoch");
    }
    self->date = cass_date_from_epoch(Z_LVAL_P(seconds));
  }
}

/* {{{ Cassandra\Date::__construct(string) */
PHP_METHOD(Date, __construct)
{
  php_cassandra_date_init(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}
/* }}} */

/* {{{ Cassandra\Date::type() */
PHP_METHOD(Date, type)
{
  php5to7_zval type = php_cassandra_type_scalar(CASS_VALUE_TYPE_DATE TSRMLS_CC);
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(type), 1, 1);
}
/* }}} */

/* {{{ Cassandra\Date::seconds() */
PHP_METHOD(Date, seconds)
{
  cassandra_date *self = PHP_CASSANDRA_GET_DATE(getThis());

  RETURN_LONG(cass_date_time_to_epoch(self->date, 0));
}
/* }}} */

/* {{{ Cassandra\Date::toDateTime() */
PHP_METHOD(Date, toDateTime)
{
  cassandra_date *self;
  zval *ztime = NULL;
  cassandra_time* time_obj = NULL;
  php5to7_zval datetime;
  php_date_obj *datetime_obj = NULL;
  char *str;
  int str_len;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|z", &ztime) == FAILURE) {
    return;
  }

  if (ztime != NULL) {
    time_obj = PHP_CASSANDRA_GET_TIME(ztime);
  }
  self = PHP_CASSANDRA_GET_DATE(getThis());

  PHP5TO7_ZVAL_MAYBE_MAKE(datetime);
  php_date_instantiate(php_date_get_date_ce(), PHP5TO7_ZVAL_MAYBE_P(datetime) TSRMLS_CC);

#if PHP_MAJOR_VERSION >= 7
  datetime_obj = php_date_obj_from_obj(Z_OBJ(datetime));
#else
  datetime_obj = zend_object_store_get_object(datetime TSRMLS_CC);
#endif

  str_len = spprintf(&str, 0, "%lld",
                     cass_date_time_to_epoch(self->date,
                                             time_obj != NULL ? time_obj->time : 0));
  php_date_initialize(datetime_obj, str, str_len, "U", NULL, 0 TSRMLS_CC);
  efree(str);

  RETVAL_ZVAL(PHP5TO7_ZVAL_MAYBE_P(datetime), 0, 1);
}
/* }}} */

/* {{{ Cassandra\Date::fromDateTime() */
PHP_METHOD(Date, fromDateTime)
{
  cassandra_date *self;
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
    object_init_ex(return_value, cassandra_date_ce);
    self = PHP_CASSANDRA_GET_DATE(return_value);
    self->date = cass_date_from_epoch(PHP5TO7_Z_LVAL_MAYBE_P(retval));
    zval_ptr_dtor(&retval);
    return;
  }
}
/* }}} */


/* {{{ Cassandra\Date::__toString() */
PHP_METHOD(Date, __toString)
{
  cassandra_date *self;
  char *ret = NULL;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_DATE(getThis());

  spprintf(&ret, 0, "Cassandra\\Date(seconds=%lld)", cass_date_time_to_epoch(self->date, 0));
  PHP5TO7_RETVAL_STRING(ret);
  efree(ret);
}
/* }}} */

ZEND_BEGIN_ARG_INFO_EX(arginfo__construct, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, seconds)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_time, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_OBJ_INFO(0, time, Cassandra\\Time, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_datetime, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_OBJ_INFO(0, datetime, DateTime, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_date_methods[] = {
  PHP_ME(Date, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Date, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Date, seconds, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Date, toDateTime, arginfo_time, ZEND_ACC_PUBLIC)
  PHP_ME(Date, fromDateTime, arginfo_datetime, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
  PHP_ME(Date, __toString, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_date_handlers;

static HashTable *
php_cassandra_date_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_date_properties(zval *object TSRMLS_DC)
{
  php5to7_zval type;
  php5to7_zval seconds;

  cassandra_date *self = PHP_CASSANDRA_GET_DATE(object);
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  type = php_cassandra_type_scalar(CASS_VALUE_TYPE_DATE TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "type", sizeof("type"), PHP5TO7_ZVAL_MAYBE_P(type), sizeof(zval));

  PHP5TO7_ZVAL_MAYBE_MAKE(seconds);
  ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(seconds), cass_date_time_to_epoch(self->date, 0));
  PHP5TO7_ZEND_HASH_UPDATE(props, "seconds", sizeof("seconds"), PHP5TO7_ZVAL_MAYBE_P(seconds), sizeof(zval));

  return props;
}

static int
php_cassandra_date_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_date *date1 = NULL;
  cassandra_date *date2 = NULL;
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  date1 = PHP_CASSANDRA_GET_DATE(obj1);
  date2 = PHP_CASSANDRA_GET_DATE(obj2);

  return PHP_CASSANDRA_COMPARE(date1->date, date2->date);
}

static unsigned
php_cassandra_date_hash_value(zval *obj TSRMLS_DC)
{
  cassandra_date *self = PHP_CASSANDRA_GET_DATE(obj);
  return 31 * 17 + self->date;
}

static void
php_cassandra_date_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_date *self = PHP5TO7_ZEND_OBJECT_GET(date, object);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_date_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_date *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(date, ce);

  self->date = 0;

  PHP5TO7_ZEND_OBJECT_INIT(date, self, ce);
}

void cassandra_define_Date(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Date", cassandra_date_methods);
  cassandra_date_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_date_ce TSRMLS_CC, 1, cassandra_value_ce);
  memcpy(&cassandra_date_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_date_handlers.std.get_properties  = php_cassandra_date_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_date_handlers.std.get_gc          = php_cassandra_date_gc;
#endif
  cassandra_date_handlers.std.compare_objects = php_cassandra_date_compare;
  cassandra_date_ce->ce_flags |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_date_ce->create_object = php_cassandra_date_new;

  cassandra_date_handlers.hash_value = php_cassandra_date_hash_value;
}
