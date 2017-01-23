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

zend_class_entry *cassandra_function_ce = NULL;

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_function_methods[] = {
  PHP_ABSTRACT_ME(Function, name, arginfo_none)
  PHP_ABSTRACT_ME(Function, simpleName, arginfo_none)
  PHP_ABSTRACT_ME(Function, arguments, arginfo_none)
  PHP_ABSTRACT_ME(Function, returnType, arginfo_none)
  PHP_ABSTRACT_ME(Function, signature, arginfo_none)
  PHP_ABSTRACT_ME(Function, language, arginfo_none)
  PHP_ABSTRACT_ME(Function, body, arginfo_none)
  PHP_ABSTRACT_ME(Function, isCalledOnNullInput, arginfo_none)
  PHP_FE_END
};

void cassandra_define_Function(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Function", cassandra_function_methods);
  cassandra_function_ce = zend_register_internal_class(&ce TSRMLS_CC);
  cassandra_function_ce->ce_flags |= ZEND_ACC_INTERFACE;
}
