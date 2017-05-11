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

zend_class_entry *cassandra_divide_by_zero_exception_ce = NULL;

static zend_function_entry DivideByZeroException_methods[] = {
  PHP_FE_END
};

void cassandra_define_DivideByZeroException(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Exception\\DivideByZeroException", DivideByZeroException_methods);
  cassandra_divide_by_zero_exception_ce = php5to7_zend_register_internal_class_ex(&ce, cassandra_range_exception_ce);
  zend_class_implements(cassandra_divide_by_zero_exception_ce TSRMLS_CC, 1, cassandra_exception_ce);
}
