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

#include "util/result.h"

zend_class_entry *cassandra_table_ce = NULL;

php5to7_zval
php_cassandra_table_build_options(CassIterator* iterator TSRMLS_DC) {
  const char *name;
  size_t name_length;
  php5to7_zval zoptions;

  PHP5TO7_ZVAL_MAYBE_MAKE(zoptions);
  array_init(PHP5TO7_ZVAL_MAYBE_P(zoptions));
  while (cass_iterator_next(iterator)) {
    const CassValue *value = NULL;
    if (cass_iterator_get_meta_field_name(iterator, &name, &name_length) == CASS_OK) {
      if (strncmp(name, "keyspace_name", name_length) == 0 ||
          strncmp(name, "table_name", name_length) == 0 ||
          strncmp(name, "columnfamily_name", name_length) == 0) {
        break;
      }
      value = cass_iterator_get_meta_field_value(iterator);
      if (value) {
        const CassDataType *data_type = cass_value_data_type(value);
        if (data_type) {
          php5to7_zval zvalue;
          PHP5TO7_ZVAL_UNDEF(zvalue);
          if (php_cassandra_value(value,
                                  data_type,
                                  &zvalue TSRMLS_CC) == SUCCESS) {
            PHP5TO7_ADD_ASSOC_ZVAL_EX(PHP5TO7_ZVAL_MAYBE_P(zoptions),
                                      name, name_length + 1,
                                      PHP5TO7_ZVAL_MAYBE_P(zvalue));
          }
        }
      }
    }
  }

  return zoptions;
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_name, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_table_methods[] = {
  PHP_ABSTRACT_ME(Table, name, arginfo_none)
  PHP_ABSTRACT_ME(Table, option, arginfo_name)
  PHP_ABSTRACT_ME(Table, options, arginfo_none)
  PHP_ABSTRACT_ME(Table, comment, arginfo_none)
  PHP_ABSTRACT_ME(Table, readRepairChance, arginfo_none)
  PHP_ABSTRACT_ME(Table, localReadRepairChance, arginfo_none)
  PHP_ABSTRACT_ME(Table, gcGraceSeconds, arginfo_none)
  PHP_ABSTRACT_ME(Table, caching, arginfo_none)
  PHP_ABSTRACT_ME(Table, bloomFilterFPChance, arginfo_none)
  PHP_ABSTRACT_ME(Table, memtableFlushPeriodMs, arginfo_none)
  PHP_ABSTRACT_ME(Table, defaultTTL, arginfo_none)
  PHP_ABSTRACT_ME(Table, speculativeRetry, arginfo_none)
  PHP_ABSTRACT_ME(Table, indexInterval, arginfo_none)
  PHP_ABSTRACT_ME(Table, compactionStrategyClassName, arginfo_none)
  PHP_ABSTRACT_ME(Table, compactionStrategyOptions, arginfo_none)
  PHP_ABSTRACT_ME(Table, compressionParameters, arginfo_none)
  PHP_ABSTRACT_ME(Table, populateIOCacheOnFlush, arginfo_none)
  PHP_ABSTRACT_ME(Table, replicateOnWrite, arginfo_none)
  PHP_ABSTRACT_ME(Table, maxIndexInterval, arginfo_none)
  PHP_ABSTRACT_ME(Table, minIndexInterval, arginfo_none)
  PHP_ABSTRACT_ME(Table, column, arginfo_name)
  PHP_ABSTRACT_ME(Table, columns, arginfo_none)
  PHP_ABSTRACT_ME(Table, partitionKey, arginfo_none)
  PHP_ABSTRACT_ME(Table, primaryKey, arginfo_none)
  PHP_ABSTRACT_ME(Table, clusteringKey, arginfo_none)
  PHP_ABSTRACT_ME(Table, clusteringOrder, arginfo_none)
  PHP_FE_END
};

void cassandra_define_Table(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Table", cassandra_table_methods);
  cassandra_table_ce = zend_register_internal_class(&ce TSRMLS_CC);
  cassandra_table_ce->ce_flags |= ZEND_ACC_INTERFACE;
}
