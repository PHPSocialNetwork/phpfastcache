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
#include "result.h"
#include "math.h"
#include "collections.h"
#include "types.h"
#include "src/Cassandra/Collection.h"
#include "src/Cassandra/Map.h"
#include "src/Cassandra/Set.h"
#include "src/Cassandra/Tuple.h"
#include "src/Cassandra/UserTypeValue.h"

int
php_cassandra_value(const CassValue* value, const CassDataType* data_type, php5to7_zval *out TSRMLS_DC)
{
  const char *v_string;
  size_t v_string_len;
  const cass_byte_t *v_bytes;
  size_t v_bytes_len;
  const cass_byte_t *v_decimal;
  size_t v_decimal_len;
  cass_int32_t v_decimal_scale;
  cass_int32_t v_int_32;
  cass_bool_t v_boolean;
  cass_double_t v_double;
  cassandra_uuid *uuid;
  CassIterator *iterator;
  cassandra_numeric *numeric = NULL;
  cassandra_timestamp *timestamp = NULL;
  cassandra_date *date = NULL;
  cassandra_time *time = NULL;
  cassandra_blob *blob = NULL;
  cassandra_inet *inet = NULL;
  cassandra_collection *collection = NULL;
  cassandra_map *map = NULL;
  cassandra_set *set = NULL;
  cassandra_tuple *tuple = NULL;
  cassandra_user_type_value *user_type_value = NULL;
  ulong index;

  CassValueType type = cass_data_type_type(data_type);
  const CassDataType* primary_type;
  const CassDataType* secondary_type;

  PHP5TO7_ZVAL_MAYBE_MAKE(*out);

  if (cass_value_is_null(value)) {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    return SUCCESS;
  }

  switch (type) {
  case CASS_VALUE_TYPE_ASCII:
  case CASS_VALUE_TYPE_TEXT:
  case CASS_VALUE_TYPE_VARCHAR:
    ASSERT_SUCCESS_BLOCK(cass_value_get_string(value, &v_string, &v_string_len),
      zval_ptr_dtor(out);
      return FAILURE;
    );
    PHP5TO7_ZVAL_STRINGL(PHP5TO7_ZVAL_MAYBE_DEREF(out), v_string, v_string_len);
    break;
  case CASS_VALUE_TYPE_INT:
    ASSERT_SUCCESS_BLOCK(cass_value_get_int32(value, &v_int_32),
      zval_ptr_dtor(out);
      return FAILURE;
    );
    ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_DEREF(out), v_int_32);
    break;
  case CASS_VALUE_TYPE_COUNTER:
  case CASS_VALUE_TYPE_BIGINT:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_bigint_ce);
    numeric = PHP_CASSANDRA_GET_NUMERIC(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_int64(value, &numeric->bigint_value),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    break;
  case CASS_VALUE_TYPE_SMALL_INT:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_smallint_ce);
    numeric = PHP_CASSANDRA_GET_NUMERIC(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_int16(value, &numeric->smallint_value),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    break;
  case CASS_VALUE_TYPE_TINY_INT:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_tinyint_ce);
    numeric = PHP_CASSANDRA_GET_NUMERIC(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_int8(value, &numeric->tinyint_value),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    break;
  case CASS_VALUE_TYPE_TIMESTAMP:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_timestamp_ce);
    timestamp = PHP_CASSANDRA_GET_TIMESTAMP(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_int64(value, &timestamp->timestamp),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    break;
  case CASS_VALUE_TYPE_DATE:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_date_ce);
    date = PHP_CASSANDRA_GET_DATE(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_uint32(value, &date->date),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    break;
  case CASS_VALUE_TYPE_TIME:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_time_ce);
    time = PHP_CASSANDRA_GET_TIME(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_int64(value, &time->time),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    break;
  case CASS_VALUE_TYPE_BLOB:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_blob_ce);
    blob = PHP_CASSANDRA_GET_BLOB(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_bytes(value, &v_bytes, &v_bytes_len),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    blob->data = emalloc(v_bytes_len * sizeof(cass_byte_t));
    blob->size = v_bytes_len;
    memcpy(blob->data, v_bytes, v_bytes_len);
    break;
  case CASS_VALUE_TYPE_VARINT:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_varint_ce);
    numeric = PHP_CASSANDRA_GET_NUMERIC(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_bytes(value, &v_bytes, &v_bytes_len),
      zval_ptr_dtor(out);
      return FAILURE;
    );
    import_twos_complement((cass_byte_t*) v_bytes, v_bytes_len, &numeric->varint_value);
    break;
  case CASS_VALUE_TYPE_UUID:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_uuid_ce);
    uuid = PHP_CASSANDRA_GET_UUID(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_uuid(value, &uuid->uuid),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    break;
  case CASS_VALUE_TYPE_TIMEUUID:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_timeuuid_ce);
    uuid = PHP_CASSANDRA_GET_UUID(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_uuid(value, &uuid->uuid),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    break;
  case CASS_VALUE_TYPE_BOOLEAN:
    ASSERT_SUCCESS_BLOCK(cass_value_get_bool(value, &v_boolean),
      zval_ptr_dtor(out);
      return FAILURE;
    );
    if (v_boolean) {
      ZVAL_TRUE(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    } else {
      ZVAL_FALSE(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    }
    break;
  case CASS_VALUE_TYPE_INET:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_inet_ce);
    inet = PHP_CASSANDRA_GET_INET(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_inet(value, &inet->inet),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    break;
  case CASS_VALUE_TYPE_DECIMAL:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_decimal_ce);
    numeric = PHP_CASSANDRA_GET_NUMERIC(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_decimal(value, &v_decimal, &v_decimal_len, &v_decimal_scale),
      zval_ptr_dtor(out);
      return FAILURE;
    );
    import_twos_complement((cass_byte_t*) v_decimal, v_decimal_len, &numeric->decimal_value);
    numeric->decimal_scale = v_decimal_scale;
    break;
  case CASS_VALUE_TYPE_DOUBLE:
    ASSERT_SUCCESS_BLOCK(cass_value_get_double(value, &v_double),
      zval_ptr_dtor(out);
      return FAILURE;
    );
    ZVAL_DOUBLE(PHP5TO7_ZVAL_MAYBE_DEREF(out), v_double);
    break;
  case CASS_VALUE_TYPE_FLOAT:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_float_ce);
    numeric = PHP_CASSANDRA_GET_NUMERIC(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    ASSERT_SUCCESS_BLOCK(cass_value_get_float(value, &numeric->float_value),
      zval_ptr_dtor(out);
      return FAILURE;
    )
    break;
  case CASS_VALUE_TYPE_LIST:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_collection_ce);
    collection = PHP_CASSANDRA_GET_COLLECTION(PHP5TO7_ZVAL_MAYBE_DEREF(out));

    primary_type = cass_data_type_sub_data_type(data_type, 0);
    collection->type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);

    iterator = cass_iterator_from_collection(value);

    while (cass_iterator_next(iterator)) {
      php5to7_zval v;

      if (php_cassandra_value(cass_iterator_get_value(iterator), primary_type, &v TSRMLS_CC) == FAILURE) {
        cass_iterator_free(iterator);
        zval_ptr_dtor(out);
        return FAILURE;
      }

      php_cassandra_collection_add(collection, PHP5TO7_ZVAL_MAYBE_P(v) TSRMLS_CC);
      zval_ptr_dtor(&v);
    }

    cass_iterator_free(iterator);
    break;
  case CASS_VALUE_TYPE_MAP:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_map_ce);
    map = PHP_CASSANDRA_GET_MAP(PHP5TO7_ZVAL_MAYBE_DEREF(out));

    primary_type = cass_data_type_sub_data_type(data_type, 0);
    secondary_type = cass_data_type_sub_data_type(data_type, 1);
    map->type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);

    iterator = cass_iterator_from_map(value);

    while (cass_iterator_next(iterator)) {
      php5to7_zval k;
      php5to7_zval v;

      if (php_cassandra_value(cass_iterator_get_map_key(iterator), primary_type, &k TSRMLS_CC) == FAILURE ||
          php_cassandra_value(cass_iterator_get_map_value(iterator), secondary_type, &v TSRMLS_CC) == FAILURE) {
        cass_iterator_free(iterator);
        zval_ptr_dtor(out);
        return FAILURE;
      }

      php_cassandra_map_set(map, PHP5TO7_ZVAL_MAYBE_P(k), PHP5TO7_ZVAL_MAYBE_P(v) TSRMLS_CC);
      zval_ptr_dtor(&k);
      zval_ptr_dtor(&v);
    }

    cass_iterator_free(iterator);
    break;
  case CASS_VALUE_TYPE_SET:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_set_ce);
    set = PHP_CASSANDRA_GET_SET(PHP5TO7_ZVAL_MAYBE_DEREF(out));

    primary_type = cass_data_type_sub_data_type(data_type, 0);
    set->type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);

    iterator = cass_iterator_from_collection(value);

    while (cass_iterator_next(iterator)) {
      php5to7_zval v;

      if (php_cassandra_value(cass_iterator_get_value(iterator), primary_type, &v TSRMLS_CC) == FAILURE) {
        cass_iterator_free(iterator);
        zval_ptr_dtor(out);
        return FAILURE;
      }

      php_cassandra_set_add(set, PHP5TO7_ZVAL_MAYBE_P(v) TSRMLS_CC);
      zval_ptr_dtor(&v);
    }

    cass_iterator_free(iterator);
    break;
  case CASS_VALUE_TYPE_TUPLE:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_tuple_ce);
    tuple = PHP_CASSANDRA_GET_TUPLE(PHP5TO7_ZVAL_MAYBE_DEREF(out));

    tuple->type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);

    iterator = cass_iterator_from_tuple(value);

    index = 0;
    while (cass_iterator_next(iterator)) {
      const CassValue* value = cass_iterator_get_value(iterator);

      if (!cass_value_is_null(value)) {
        php5to7_zval v;

        primary_type = cass_data_type_sub_data_type(data_type, index);
        if (php_cassandra_value(value, primary_type, &v TSRMLS_CC) == FAILURE) {
          cass_iterator_free(iterator);
          zval_ptr_dtor(out);
          return FAILURE;
        }

        php_cassandra_tuple_set(tuple, index, PHP5TO7_ZVAL_MAYBE_P(v) TSRMLS_CC);
        zval_ptr_dtor(&v);
      }

      index++;
    }

    cass_iterator_free(iterator);
    break;
  case CASS_VALUE_TYPE_UDT:
    object_init_ex(PHP5TO7_ZVAL_MAYBE_DEREF(out), cassandra_user_type_value_ce);
    user_type_value = PHP_CASSANDRA_GET_USER_TYPE_VALUE(PHP5TO7_ZVAL_MAYBE_DEREF(out));

    user_type_value->type = php_cassandra_type_from_data_type(data_type TSRMLS_CC);

    iterator = cass_iterator_fields_from_user_type(value);

    index = 0;
    while (cass_iterator_next(iterator)) {
      const CassValue* value = cass_iterator_get_user_type_field_value(iterator);

      if (!cass_value_is_null(value)) {
        const char *name;
        size_t name_length;
        php5to7_zval v;

        primary_type = cass_data_type_sub_data_type(data_type, index);
        if (php_cassandra_value(value, primary_type, &v TSRMLS_CC) == FAILURE) {
          cass_iterator_free(iterator);
          zval_ptr_dtor(out);
          return FAILURE;
        }

        cass_iterator_get_user_type_field_name(iterator, &name, &name_length);
        php_cassandra_user_type_value_set(user_type_value,
                                          name, name_length,
                                          PHP5TO7_ZVAL_MAYBE_P(v) TSRMLS_CC);
        zval_ptr_dtor(&v);
      }

      index++;
    }

    cass_iterator_free(iterator);
    break;
  default:
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    break;
  }

  return SUCCESS;
}

int
php_cassandra_get_keyspace_field(const CassKeyspaceMeta *metadata, const char *field_name, php5to7_zval *out TSRMLS_DC)
{
  const CassValue *value;

  value = cass_keyspace_meta_field_by_name(metadata, field_name);

  if (value == NULL || cass_value_is_null(value)) {
    PHP5TO7_ZVAL_MAYBE_MAKE(*out);
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    return SUCCESS;
  }

  return php_cassandra_value(value, cass_value_data_type(value), out TSRMLS_CC);
}

int
php_cassandra_get_table_field(const CassTableMeta *metadata, const char *field_name, php5to7_zval *out TSRMLS_DC)
{
  const CassValue *value;

  value = cass_table_meta_field_by_name(metadata, field_name);

  if (value == NULL || cass_value_is_null(value)) {
    PHP5TO7_ZVAL_MAYBE_MAKE(*out);
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    return SUCCESS;
  }

  return php_cassandra_value(value, cass_value_data_type(value), out TSRMLS_CC);
}

int
php_cassandra_get_column_field(const CassColumnMeta *metadata, const char *field_name, php5to7_zval *out TSRMLS_DC)
{
  const CassValue *value;

  value = cass_column_meta_field_by_name(metadata, field_name);

  if (value == NULL || cass_value_is_null(value)) {
    PHP5TO7_ZVAL_MAYBE_MAKE(*out);
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_DEREF(out));
    return SUCCESS;
  }

  return php_cassandra_value(value, cass_value_data_type(value), out TSRMLS_CC);
}

int
php_cassandra_get_result(const CassResult *result, php5to7_zval *out TSRMLS_DC)
{
  php5to7_zval     rows;
  php5to7_zval     row;
  const CassRow   *cass_row;
  const char      *column_name;
  size_t           column_name_len;
  const CassDataType* column_type;
  const CassValue *column_value;
  CassIterator    *iterator = NULL;
  size_t           columns = -1;
  char           **column_names;
  unsigned         i;

  PHP5TO7_ZVAL_MAYBE_MAKE(rows);
  array_init(PHP5TO7_ZVAL_MAYBE_P(rows));

  iterator = cass_iterator_from_result(result);
  columns  = cass_result_column_count(result);

  column_names = (char**) ecalloc(columns, sizeof(char*));

  while (cass_iterator_next(iterator)) {
    PHP5TO7_ZVAL_MAYBE_MAKE(row);
    array_init(PHP5TO7_ZVAL_MAYBE_P(row));
    cass_row = cass_iterator_get_row(iterator);

    for (i = 0; i < columns; i++) {
      php5to7_zval value;

      if (column_names[i] == NULL) {
        cass_result_column_name(result, i, &column_name, &column_name_len);
        column_names[i] = estrndup(column_name, column_name_len);
      }

      column_type  = cass_result_column_data_type(result, i);
      column_value = cass_row_get_column(cass_row, i);

      if (php_cassandra_value(column_value, column_type, &value TSRMLS_CC) == FAILURE) {
        zval_ptr_dtor(&row);
        zval_ptr_dtor(&rows);

        for (i = 0; i < columns; i++) {
          if (column_names[i]) {
            efree(column_names[i]);
          }
        }

        efree(column_names);
        cass_iterator_free(iterator);

        return FAILURE;
      }

      PHP5TO7_ADD_ASSOC_ZVAL_EX(PHP5TO7_ZVAL_MAYBE_P(row),
                                column_names[i], strlen(column_names[i]) + 1,
                                PHP5TO7_ZVAL_MAYBE_P(value));
    }

    add_next_index_zval(PHP5TO7_ZVAL_MAYBE_P(rows),
                        PHP5TO7_ZVAL_MAYBE_P(row));
  }

  for (i = 0; i < columns; i++) {
    if (column_names[i] != NULL) {
      efree(column_names[i]);
    }
  }

  efree(column_names);
  cass_iterator_free(iterator);

  *out = rows;

  return SUCCESS;
}
