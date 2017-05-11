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

#ifndef PHP_CASSANDRA_UTIL_TYPES_H
#define PHP_CASSANDRA_UTIL_TYPES_H

#if PHP_MAJOR_VERSION >= 7
#include <zend_smart_str.h>
#else
#include <ext/standard/php_smart_str.h>
#endif

#define PHP_CASSANDRA_SCALAR_TYPES_MAP(XX) \
  XX(ascii, CASS_VALUE_TYPE_ASCII) \
  XX(bigint, CASS_VALUE_TYPE_BIGINT) \
  XX(smallint, CASS_VALUE_TYPE_SMALL_INT) \
  XX(tinyint, CASS_VALUE_TYPE_TINY_INT) \
  XX(blob, CASS_VALUE_TYPE_BLOB) \
  XX(boolean, CASS_VALUE_TYPE_BOOLEAN) \
  XX(counter, CASS_VALUE_TYPE_COUNTER) \
  XX(decimal, CASS_VALUE_TYPE_DECIMAL) \
  XX(double, CASS_VALUE_TYPE_DOUBLE) \
  XX(float, CASS_VALUE_TYPE_FLOAT) \
  XX(int, CASS_VALUE_TYPE_INT) \
  XX(text, CASS_VALUE_TYPE_TEXT) \
  XX(timestamp, CASS_VALUE_TYPE_TIMESTAMP) \
  XX(date, CASS_VALUE_TYPE_DATE) \
  XX(time, CASS_VALUE_TYPE_TIME) \
  XX(uuid, CASS_VALUE_TYPE_UUID) \
  XX(varchar, CASS_VALUE_TYPE_VARCHAR) \
  XX(varint, CASS_VALUE_TYPE_VARINT) \
  XX(timeuuid, CASS_VALUE_TYPE_TIMEUUID) \
  XX(inet, CASS_VALUE_TYPE_INET)

php5to7_zval php_cassandra_type_from_data_type(const CassDataType *data_type TSRMLS_DC);

int php_cassandra_type_validate(zval *object, const char *object_name TSRMLS_DC);
int php_cassandra_type_compare(cassandra_type *type1, cassandra_type *type2 TSRMLS_DC);
void php_cassandra_type_string(cassandra_type *type, smart_str *smart TSRMLS_DC);

php5to7_zval php_cassandra_type_scalar(CassValueType type TSRMLS_DC);
const char* php_cassandra_scalar_type_name(CassValueType type TSRMLS_DC);

php5to7_zval php_cassandra_type_set(zval *value_type TSRMLS_DC);
php5to7_zval php_cassandra_type_set_from_value_type(CassValueType type TSRMLS_DC);

php5to7_zval php_cassandra_type_collection(zval *value_type TSRMLS_DC);
php5to7_zval php_cassandra_type_collection_from_value_type(CassValueType type TSRMLS_DC);

php5to7_zval php_cassandra_type_map(zval *key_type,
                                    zval *value_type TSRMLS_DC);
php5to7_zval php_cassandra_type_map_from_value_types(CassValueType key_type,
                                                     CassValueType value_type TSRMLS_DC);

php5to7_zval php_cassandra_type_tuple(TSRMLS_D);

php5to7_zval php_cassandra_type_user_type(TSRMLS_D);

php5to7_zval php_cassandra_type_custom(char *name TSRMLS_DC);

int php_cassandra_parse_column_type(const char   *validator,
                                    size_t        validator_len,
                                    int          *reversed_out,
                                    int          *frozen_out,
                                    php5to7_zval *type_out TSRMLS_DC);

void php_cassandra_scalar_init(INTERNAL_FUNCTION_PARAMETERS);

#endif /* PHP_CASSANDRA_UTIL_TYPES_H */
