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

zend_class_entry *cassandra_column_ce = NULL;

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_column_methods[] = {
  PHP_ABSTRACT_ME(Column, name, arginfo_none)
  PHP_ABSTRACT_ME(Column, type, arginfo_none)
  PHP_ABSTRACT_ME(Column, isReversed, arginfo_none)
  PHP_ABSTRACT_ME(Column, isStatic, arginfo_none)
  PHP_ABSTRACT_ME(Column, isFrozen, arginfo_none)
  PHP_ABSTRACT_ME(Column, indexName, arginfo_none)
  PHP_ABSTRACT_ME(Column, indexOptions, arginfo_none)
  PHP_FE_END
};

void cassandra_define_Column(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Column", cassandra_column_methods);
  cassandra_column_ce = zend_register_internal_class(&ce TSRMLS_CC);
  cassandra_column_ce->ce_flags |= ZEND_ACC_INTERFACE;
}
