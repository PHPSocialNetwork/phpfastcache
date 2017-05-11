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

zend_class_entry *cassandra_keyspace_ce = NULL;

ZEND_BEGIN_ARG_INFO_EX(arginfo_name, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_signature, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, name)
  ZEND_ARG_INFO(0, ...)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_keyspace_methods[] = {
  PHP_ABSTRACT_ME(Keyspace, name, arginfo_none)
  PHP_ABSTRACT_ME(Keyspace, replicationClassName, arginfo_none)
  PHP_ABSTRACT_ME(Keyspace, replicationOptions, arginfo_none)
  PHP_ABSTRACT_ME(Keyspace, hasDurableWrites, arginfo_none)
  PHP_ABSTRACT_ME(Keyspace, table, arginfo_name)
  PHP_ABSTRACT_ME(Keyspace, tables, arginfo_none)
  PHP_ABSTRACT_ME(Keyspace, userType, arginfo_name)
  PHP_ABSTRACT_ME(Keyspace, userTypes, arginfo_none)
  PHP_ABSTRACT_ME(Keyspace, materializedView, arginfo_name)
  PHP_ABSTRACT_ME(Keyspace, materializedViews, arginfo_none)
  PHP_ABSTRACT_ME(Keyspace, function, arginfo_signature)
  PHP_ABSTRACT_ME(Keyspace, functions, arginfo_none)
  PHP_ABSTRACT_ME(Keyspace, aggregate, arginfo_signature)
  PHP_ABSTRACT_ME(Keyspace, aggregates, arginfo_none)
  PHP_FE_END
};

void cassandra_define_Keyspace(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Keyspace", cassandra_keyspace_methods);
  cassandra_keyspace_ce = zend_register_internal_class(&ce TSRMLS_CC);
  cassandra_keyspace_ce->ce_flags |= ZEND_ACC_INTERFACE;
}
