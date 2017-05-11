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

zend_class_entry *cassandra_numeric_ce = NULL;

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_num, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, num)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_numeric_methods[] = {
  PHP_ABSTRACT_ME(Numeric, add, arginfo_num)
  PHP_ABSTRACT_ME(Numeric, sub, arginfo_num)
  PHP_ABSTRACT_ME(Numeric, mul, arginfo_num)
  PHP_ABSTRACT_ME(Numeric, div, arginfo_num)
  PHP_ABSTRACT_ME(Numeric, mod, arginfo_num)
  PHP_ABSTRACT_ME(Numeric, abs, arginfo_none)
  PHP_ABSTRACT_ME(Numeric, neg, arginfo_none)
  PHP_ABSTRACT_ME(Numeric, sqrt, arginfo_none)
  PHP_ABSTRACT_ME(Numeric, toInt, arginfo_none)
  PHP_ABSTRACT_ME(Numeric, toDouble, arginfo_none)
  PHP_FE_END
};

void
cassandra_define_Numeric(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Numeric", cassandra_numeric_methods);
  cassandra_numeric_ce = zend_register_internal_class(&ce TSRMLS_CC);
  cassandra_numeric_ce->ce_flags |= ZEND_ACC_INTERFACE;
}
