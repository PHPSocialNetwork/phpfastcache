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
#include "util/collections.h"
#include "util/hash.h"
#include "util/types.h"
#include "Map.h"

zend_class_entry *cassandra_map_ce = NULL;

int
php_cassandra_map_set(cassandra_map *map, zval *zkey, zval *zvalue TSRMLS_DC)
{
  cassandra_map_entry *entry;
  cassandra_type *type;

  if (Z_TYPE_P(zkey) == IS_NULL) {
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                            "Invalid key: null is not supported inside maps");
    return 0;
  }

  if (Z_TYPE_P(zvalue) == IS_NULL) {
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                            "Invalid value: null is not supported inside maps");
    return 0;
  }

  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(map->type));

  if (!php_cassandra_validate_object(zkey, PHP5TO7_ZVAL_MAYBE_P(type->key_type) TSRMLS_CC)) {
    return 0;
  }

  if (!php_cassandra_validate_object(zvalue, PHP5TO7_ZVAL_MAYBE_P(type->value_type) TSRMLS_CC)) {
    return 0;
  }

  map->dirty = 1;
  HASH_FIND_ZVAL(map->entries, zkey, entry);
  if (entry == NULL) {
    entry = (cassandra_map_entry *) emalloc(sizeof(cassandra_map_entry));
    PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(entry->key), zkey);
    PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(entry->value), zvalue);
    HASH_ADD_ZVAL(map->entries, key, entry);
  } else {
    php5to7_zval prev_value = entry->value;
    PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(entry->value), zvalue);
    zval_ptr_dtor(&prev_value);
  }

  return 1;
}

static int
php_cassandra_map_get(cassandra_map *map, zval *zkey, php5to7_zval *zvalue TSRMLS_DC)
{
  cassandra_map_entry *entry;
  cassandra_type *type;
  int result = 0;

  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(map->type));

  if (!php_cassandra_validate_object(zkey, PHP5TO7_ZVAL_MAYBE_P(type->key_type) TSRMLS_CC)) {
    return 0;
  }

  HASH_FIND_ZVAL(map->entries, zkey, entry);
  if (entry != NULL) {
    *zvalue = entry->value;
    result = 1;
  }

  return result;
}

static int
php_cassandra_map_del(cassandra_map *map, zval *zkey TSRMLS_DC)
{
  cassandra_map_entry *entry;
  cassandra_type *type;
  int result = 0;

  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(map->type));

  if (!php_cassandra_validate_object(zkey, PHP5TO7_ZVAL_MAYBE_P(type->key_type) TSRMLS_CC)) {
    return 0;
  }

  HASH_FIND_ZVAL(map->entries, zkey, entry);
  if (entry != NULL) {
    map->dirty = 1;
    if (entry == map->iter_temp) {
      map->iter_temp = (cassandra_map_entry *)map->iter_temp->hh.next;
    }
    HASH_DEL(map->entries, entry);
    zval_ptr_dtor(&entry->key);
    zval_ptr_dtor(&entry->value);
    efree(entry);
    result = 1;
  }

  return result;
}

static int
php_cassandra_map_has(cassandra_map *map, zval *zkey TSRMLS_DC)
{
  cassandra_map_entry *entry;
  cassandra_type *type;
  int result = 0;

  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(map->type));

  if (!php_cassandra_validate_object(zkey, PHP5TO7_ZVAL_MAYBE_P(type->key_type) TSRMLS_CC)) {
    return 0;
  }

  HASH_FIND_ZVAL(map->entries, zkey, entry);
  if (entry != NULL) {
    result = 1;
  }

  return result;
}

static void
php_cassandra_map_populate_keys(const cassandra_map *map, zval *array TSRMLS_DC)
{
  cassandra_map_entry *curr,  *temp;
  HASH_ITER(hh, map->entries, curr, temp) {
    if (add_next_index_zval(array, PHP5TO7_ZVAL_MAYBE_P(curr->key)) != SUCCESS) {
      break;
    }
    Z_TRY_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(curr->key));
  }
}

static void
php_cassandra_map_populate_values(const cassandra_map *map, zval *array TSRMLS_DC)
{
  cassandra_map_entry *curr, *temp;
  HASH_ITER(hh, map->entries, curr, temp) {
    if (add_next_index_zval(array, PHP5TO7_ZVAL_MAYBE_P(curr->value)) != SUCCESS) {
      break;
    }
    Z_TRY_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(curr->value));
  }
}

/* {{{ Cassandra\Map::__construct(type, type) */
PHP_METHOD(Map, __construct)
{
  cassandra_map *self;
  zval *key_type;
  zval *value_type;
  php5to7_zval scalar_key_type;
  php5to7_zval scalar_value_type;

  PHP5TO7_ZVAL_UNDEF(scalar_key_type);
  PHP5TO7_ZVAL_UNDEF(scalar_value_type);

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "zz", &key_type, &value_type) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_MAP(getThis());

  if (Z_TYPE_P(key_type) == IS_STRING) {
    CassValueType type;
    if (!php_cassandra_value_type(Z_STRVAL_P(key_type), &type TSRMLS_CC))
      return;
    scalar_key_type = php_cassandra_type_scalar(type TSRMLS_CC);
    key_type = PHP5TO7_ZVAL_MAYBE_P(scalar_key_type);
  } else if (Z_TYPE_P(key_type) == IS_OBJECT &&
             instanceof_function(Z_OBJCE_P(key_type), cassandra_type_ce TSRMLS_CC)) {
    if (!php_cassandra_type_validate(key_type, "keyType" TSRMLS_CC)) {
      return;
    }
    Z_ADDREF_P(key_type);
  } else {
    throw_invalid_argument(key_type,
                           "keyType",
                           "a string or an instance of Cassandra\\Type" TSRMLS_CC);
    return;
  }

  if (Z_TYPE_P(value_type) == IS_STRING) {
    CassValueType type;
    if (!php_cassandra_value_type(Z_STRVAL_P(value_type), &type TSRMLS_CC))
      return;
    scalar_value_type = php_cassandra_type_scalar(type TSRMLS_CC);
    value_type = PHP5TO7_ZVAL_MAYBE_P(scalar_value_type);
  } else if (Z_TYPE_P(value_type) == IS_OBJECT &&
             instanceof_function(Z_OBJCE_P(value_type), cassandra_type_ce TSRMLS_CC)) {
    if (!php_cassandra_type_validate(value_type, "valueType" TSRMLS_CC)) {
      return;
    }
    Z_ADDREF_P(value_type);
  } else {
    zval_ptr_dtor(PHP5TO7_ZVAL_MAYBE_ADDR_OF(key_type));
    throw_invalid_argument(value_type,
                           "valueType",
                           "a string or an instance of Cassandra\\Type" TSRMLS_CC);
    return;
  }

  self->type = php_cassandra_type_map(key_type, value_type TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Map::type() */
PHP_METHOD(Map, type)
{
  cassandra_map *self = PHP_CASSANDRA_GET_MAP(getThis());
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->type), 1, 0);
}
/* }}} */

PHP_METHOD(Map, keys)
{
  cassandra_map *self = NULL;
  array_init(return_value);
  self = PHP_CASSANDRA_GET_MAP(getThis());
  php_cassandra_map_populate_keys(self, return_value TSRMLS_CC);
}

PHP_METHOD(Map, values)
{
  cassandra_map *self = NULL;
  array_init(return_value);
  self = PHP_CASSANDRA_GET_MAP(getThis());
  php_cassandra_map_populate_values(self, return_value TSRMLS_CC);
}

PHP_METHOD(Map, set)
{
  zval *key;
  cassandra_map *self = NULL;
  zval *value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "zz", &key, &value) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_MAP(getThis());

  if (php_cassandra_map_set(self, key, value TSRMLS_CC))
    RETURN_TRUE;

  RETURN_FALSE;
}

PHP_METHOD(Map, get)
{
  zval *key;
  cassandra_map *self = NULL;
  php5to7_zval value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &key) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_MAP(getThis());

  if (php_cassandra_map_get(self, key, &value TSRMLS_CC))
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(value), 1, 0);
}

PHP_METHOD(Map, remove)
{
  zval *key;
  cassandra_map *self = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &key) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_MAP(getThis());

  if (php_cassandra_map_del(self, key TSRMLS_CC))
    RETURN_TRUE;

  RETURN_FALSE;
}

PHP_METHOD(Map, has)
{
  zval *key;
  cassandra_map *self = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &key) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_MAP(getThis());

  if (php_cassandra_map_has(self, key TSRMLS_CC))
    RETURN_TRUE;

  RETURN_FALSE;
}

PHP_METHOD(Map, count)
{
  cassandra_map *self = PHP_CASSANDRA_GET_MAP(getThis());
  RETURN_LONG((long)HASH_COUNT(self->entries));
}

PHP_METHOD(Map, current)
{
  cassandra_map *self = PHP_CASSANDRA_GET_MAP(getThis());
  if (self->iter_curr != NULL)
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->iter_curr->value), 1, 0);
}

PHP_METHOD(Map, key)
{
  cassandra_map *self = PHP_CASSANDRA_GET_MAP(getThis());
  if (self->iter_curr != NULL)
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->iter_curr->key), 1, 0);
}

PHP_METHOD(Map, next)
{
  cassandra_map *self = PHP_CASSANDRA_GET_MAP(getThis());
  self->iter_curr = self->iter_temp;
  self->iter_temp = self->iter_temp != NULL ? (cassandra_map_entry *)self->iter_temp->hh.next : NULL;
}

PHP_METHOD(Map, valid)
{
  cassandra_map *self = PHP_CASSANDRA_GET_MAP(getThis());
  RETURN_BOOL(self->iter_curr != NULL);
}

PHP_METHOD(Map, rewind)
{
  cassandra_map *self = PHP_CASSANDRA_GET_MAP(getThis());
  self->iter_curr = self->entries;
  self->iter_temp = self->entries != NULL ? (cassandra_map_entry *)self->entries->hh.next : NULL;
}

PHP_METHOD(Map, offsetSet)
{
  zval *key;
  cassandra_map *self = NULL;
  zval *value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "zz", &key, &value) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_MAP(getThis());

  php_cassandra_map_set(self, key, value TSRMLS_CC);
}

PHP_METHOD(Map, offsetGet)
{
  zval *key;
  cassandra_map *self = NULL;
  php5to7_zval value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &key) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_MAP(getThis());

  if (php_cassandra_map_get(self, key, &value TSRMLS_CC))
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(value), 1, 0);
}

PHP_METHOD(Map, offsetUnset)
{
  zval *key;
  cassandra_map *self = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &key) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_MAP(getThis());

  php_cassandra_map_del(self, key TSRMLS_CC);
}

PHP_METHOD(Map, offsetExists)
{
  zval *key;
  cassandra_map *self = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &key) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_MAP(getThis());

  if (php_cassandra_map_has(self, key TSRMLS_CC))
    RETURN_TRUE;

  RETURN_FALSE;
}

ZEND_BEGIN_ARG_INFO_EX(arginfo__construct, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, type)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_two, 0, ZEND_RETURN_VALUE, 2)
  ZEND_ARG_INFO(0, key)
  ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_one, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, key)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_map_methods[] = {
  PHP_ME(Map, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Map, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Map, keys, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Map, values, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Map, set, arginfo_two, ZEND_ACC_PUBLIC)
  PHP_ME(Map, get, arginfo_one, ZEND_ACC_PUBLIC)
  PHP_ME(Map, remove, arginfo_one, ZEND_ACC_PUBLIC)
  PHP_ME(Map, has, arginfo_one, ZEND_ACC_PUBLIC)
  /* Countable */
  PHP_ME(Map, count, arginfo_none, ZEND_ACC_PUBLIC)
  /* Iterator */
  PHP_ME(Map, current, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Map, key, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Map, next, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Map, valid, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Map, rewind, arginfo_none, ZEND_ACC_PUBLIC)
  /* ArrayAccess */
  PHP_ME(Map, offsetSet, arginfo_two, ZEND_ACC_PUBLIC)
  PHP_ME(Map, offsetGet, arginfo_one, ZEND_ACC_PUBLIC)
  PHP_ME(Map, offsetUnset, arginfo_one, ZEND_ACC_PUBLIC)
  PHP_ME(Map, offsetExists, arginfo_one, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_map_handlers;

static HashTable *
php_cassandra_map_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_map_properties(zval *object TSRMLS_DC)
{
  php5to7_zval keys;
  php5to7_zval values;

  cassandra_map *self = PHP_CASSANDRA_GET_MAP(object);
  HashTable     *props = zend_std_get_properties(object TSRMLS_CC);


  if (PHP5TO7_ZEND_HASH_UPDATE(props,
                               "type", sizeof("type"),
                               PHP5TO7_ZVAL_MAYBE_P(self->type), sizeof(zval))) {
    Z_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(self->type));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(keys);
  array_init(PHP5TO7_ZVAL_MAYBE_P(keys));
  php_cassandra_map_populate_keys(self, PHP5TO7_ZVAL_MAYBE_P(keys) TSRMLS_CC);
  PHP5TO7_ZEND_HASH_SORT(Z_ARRVAL_P(PHP5TO7_ZVAL_MAYBE_P(keys)), php_cassandra_data_compare, 1);
  PHP5TO7_ZEND_HASH_UPDATE(props, "keys", sizeof("keys"), PHP5TO7_ZVAL_MAYBE_P(keys), sizeof(zval *));

  PHP5TO7_ZVAL_MAYBE_MAKE(values);
  array_init(PHP5TO7_ZVAL_MAYBE_P(values));
  php_cassandra_map_populate_values(self, PHP5TO7_ZVAL_MAYBE_P(values) TSRMLS_CC);
  PHP5TO7_ZEND_HASH_SORT(Z_ARRVAL_P(PHP5TO7_ZVAL_MAYBE_P(values)), php_cassandra_data_compare, 1);
  PHP5TO7_ZEND_HASH_UPDATE(props, "values", sizeof("values"), PHP5TO7_ZVAL_MAYBE_P(values), sizeof(zval *));

  return props;
}

static int
php_cassandra_map_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_map_entry *curr, *temp;
  cassandra_map *map1;
  cassandra_map *map2;
  cassandra_type *type1;
  cassandra_type *type2;
  int result;

  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  map1 = PHP_CASSANDRA_GET_MAP(obj1);
  map2 = PHP_CASSANDRA_GET_MAP(obj2);

  type1 = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(map1->type));
  type2 = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(map2->type));

  result = php_cassandra_type_compare(type1, type2 TSRMLS_CC);
  if (result != 0) return result;

  if (HASH_COUNT(map1->entries) != HASH_COUNT(map1->entries)) {
   return HASH_COUNT(map1->entries) < HASH_COUNT(map1->entries) ? -1 : 1;
  }

  HASH_ITER(hh, map1->entries, curr, temp) {
    cassandra_map_entry *entry;
    HASH_FIND_ZVAL(map2->entries, PHP5TO7_ZVAL_MAYBE_P(curr->key), entry);
    if (entry == NULL) {
      return 1;
    }
  }

  return 0;
}

static unsigned
php_cassandra_map_hash_value(zval *obj TSRMLS_DC)
{
  cassandra_map *self = PHP_CASSANDRA_GET_MAP(obj);
  cassandra_map_entry *curr, *temp;
  unsigned hashv = 0;

  if (!self->dirty) return self->hashv;

  HASH_ITER(hh, self->entries, curr, temp) {
    hashv = php_cassandra_combine_hash(hashv,
                                       php_cassandra_value_hash(PHP5TO7_ZVAL_MAYBE_P(curr->key) TSRMLS_CC));
    hashv = php_cassandra_combine_hash(hashv,
                                       php_cassandra_value_hash(PHP5TO7_ZVAL_MAYBE_P(curr->value) TSRMLS_CC));
  }

  self->hashv = hashv;
  self->dirty = 0;

  return hashv;
}

static void
php_cassandra_map_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_map *self = PHP5TO7_ZEND_OBJECT_GET(map, object);
  cassandra_map_entry *curr, *temp;

  HASH_ITER(hh, self->entries, curr, temp) {
    zval_ptr_dtor(&curr->key);
    zval_ptr_dtor(&curr->value);
    HASH_DEL(self->entries, curr);
    efree(curr);
  }

  PHP5TO7_ZVAL_MAYBE_DESTROY(self->type);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_map_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_map *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(map, ce);

  self->entries = self->iter_curr = self->iter_temp = NULL;
  self->dirty = 1;
  PHP5TO7_ZVAL_UNDEF(self->type);

  PHP5TO7_ZEND_OBJECT_INIT(map, self, ce);
}

void cassandra_define_Map(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Map", cassandra_map_methods);
  cassandra_map_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_map_ce TSRMLS_CC, 1, cassandra_value_ce);
  memcpy(&cassandra_map_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_map_handlers.std.get_properties  = php_cassandra_map_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_map_handlers.std.get_gc          = php_cassandra_map_gc;
#endif
  cassandra_map_handlers.std.compare_objects = php_cassandra_map_compare;
  cassandra_map_ce->ce_flags |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_map_ce->create_object = php_cassandra_map_new;
  zend_class_implements(cassandra_map_ce TSRMLS_CC, 3, spl_ce_Countable, zend_ce_iterator, zend_ce_arrayaccess);

  cassandra_map_handlers.hash_value = php_cassandra_map_hash_value;
  cassandra_map_handlers.std.clone_obj = NULL;
}
