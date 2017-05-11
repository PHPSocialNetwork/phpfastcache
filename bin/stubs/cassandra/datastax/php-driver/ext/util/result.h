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

#ifndef PHP_CASSANDRA_RESULT_H
#define PHP_CASSANDRA_RESULT_H

int php_cassandra_value(const CassValue* value, const CassDataType* data_type, php5to7_zval *out TSRMLS_DC);

int php_cassandra_get_keyspace_field(const CassKeyspaceMeta *metadata, const char *field_name, php5to7_zval *out TSRMLS_DC);
int php_cassandra_get_table_field(const CassTableMeta *metadata, const char *field_name, php5to7_zval *out TSRMLS_DC);
int php_cassandra_get_column_field(const CassColumnMeta *metadata, const char *field_name, php5to7_zval *out TSRMLS_DC);

int php_cassandra_get_result(const CassResult *result, php5to7_zval *out TSRMLS_DC);


#endif /* PHP_CASSANDRA_RESULT_H */
