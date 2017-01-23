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

zend_class_entry *cassandra_cluster_ce = NULL;

ZEND_BEGIN_ARG_INFO_EX(arginfo_keyspace, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, keyspace)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_cluster_methods[] = {
  PHP_ABSTRACT_ME(Cluster, connect, arginfo_keyspace)
  PHP_ABSTRACT_ME(Cluster, connectAsync, arginfo_keyspace)
  PHP_FE_END
};

void cassandra_define_Cluster(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Cluster", cassandra_cluster_methods);
  cassandra_cluster_ce = zend_register_internal_class(&ce TSRMLS_CC);
  cassandra_cluster_ce->ce_flags |= ZEND_ACC_INTERFACE;
}
