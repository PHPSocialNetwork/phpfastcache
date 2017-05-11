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
#include "src/Cassandra/Collection.h"

zend_class_entry *cassandra_collection_ce = NULL;

int
php_cassandra_collection_add(cassandra_collection *collection, zval *object TSRMLS_DC)
{
  if (PHP5TO7_ZEND_HASH_NEXT_INDEX_INSERT(&collection->values, object, sizeof(zval *))) {
    Z_TRY_ADDREF_P(object);
    collection->dirty = 1;
    return 1;
  }
  return 0;
}

static int
php_cassandra_collection_del(cassandra_collection *collection, ulong index)
{
  if (zend_hash_index_del(&collection->values, index) == SUCCESS) {
    collection->dirty = 1;
    return 1;
  }

  return 0;
}

static int
php_cassandra_collection_get(cassandra_collection *collection, ulong index, php5to7_zval *zvalue)
{
  php5to7_zval *value;
  if (PHP5TO7_ZEND_HASH_INDEX_FIND(&collection->values, index, value)) {
    *zvalue = *value;
    return 1;
  }
  return 0;
}

static int
php_cassandra_collection_find(cassandra_collection *collection, zval *object, long *index TSRMLS_DC)
{
  php5to7_ulong num_key;
  php5to7_zval *current;
  PHP5TO7_ZEND_HASH_FOREACH_NUM_KEY_VAL(&collection->values, num_key, current) {
    zval compare;
    is_equal_function(&compare, object, PHP5TO7_ZVAL_MAYBE_DEREF(current) TSRMLS_CC);
    if (PHP5TO7_ZVAL_IS_TRUE_P(&compare)) {
      *index = (long) num_key;
      return 1;
    }
  } PHP5TO7_ZEND_HASH_FOREACH_END(&collection->values);

  return 0;
}

static void
php_cassandra_collection_populate(cassandra_collection *collection, zval *array)
{
  php5to7_zval *current;
  PHP5TO7_ZEND_HASH_FOREACH_VAL(&collection->values, current) {
    if (add_next_index_zval(array, PHP5TO7_ZVAL_MAYBE_DEREF(current)) == SUCCESS)
      Z_TRY_ADDREF_P(PHP5TO7_ZVAL_MAYBE_DEREF(current));
    else
      break;
  } PHP5TO7_ZEND_HASH_FOREACH_END(&collection->values);
}

/* {{{ Cassandra\Collection::__construct(type) */
PHP_METHOD(Collection, __construct)
{
  cassandra_collection *self;
  zval *type;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &type) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_COLLECTION(getThis());

  if (Z_TYPE_P(type) == IS_STRING) {
    CassValueType value_type;
    if (!php_cassandra_value_type(Z_STRVAL_P(type), &value_type TSRMLS_CC))
      return;
    self->type = php_cassandra_type_set_from_value_type(value_type TSRMLS_CC);
  } else if (Z_TYPE_P(type) == IS_OBJECT &&
             instanceof_function(Z_OBJCE_P(type), cassandra_type_ce TSRMLS_CC)) {
    if (!php_cassandra_type_validate(type, "type" TSRMLS_CC)) {
      return;
    }
    self->type = php_cassandra_type_collection(type TSRMLS_CC);
    Z_ADDREF_P(type);
  } else {
    INVALID_ARGUMENT(type, "a string or an instance of Cassandra\\Type");
  }
}
/* }}} */

/* {{{ Cassandra\Collection::type() */
PHP_METHOD(Collection, type)
{
  cassandra_collection *self = PHP_CASSANDRA_GET_COLLECTION(getThis());
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->type), 1, 0);
}

/* {{{ Cassandra\Collection::values() */
PHP_METHOD(Collection, values)
{
  cassandra_collection *collection = NULL;
  array_init(return_value);
  collection = PHP_CASSANDRA_GET_COLLECTION(getThis());
  php_cassandra_collection_populate(collection, return_value);
}
/* }}} */

/* {{{ Cassandra\Collection::add(mixed) */
PHP_METHOD(Collection, add)
{
  cassandra_collection *self = NULL;
  php5to7_zval_args args = NULL;
  int argc = 0, i;
  cassandra_type *type;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "+", &args, &argc) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_COLLECTION(getThis());
  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(self->type));

  for (i = 0; i < argc; i++) {
    if (Z_TYPE_P(PHP5TO7_ZVAL_ARG(args[i])) == IS_NULL) {
      PHP5TO7_MAYBE_EFREE(args);
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                              "Invalid value: null is not supported inside collections");
      RETURN_FALSE;
    }

    if (!php_cassandra_validate_object(PHP5TO7_ZVAL_ARG(args[i]),
                                       PHP5TO7_ZVAL_MAYBE_P(type->value_type) TSRMLS_CC)) {
      PHP5TO7_MAYBE_EFREE(args);
      RETURN_FALSE;
    }
  }

  for (i = 0; i < argc; i++) {
    php_cassandra_collection_add(self, PHP5TO7_ZVAL_ARG(args[i]) TSRMLS_CC);
  }

  PHP5TO7_MAYBE_EFREE(args);
  RETVAL_LONG(zend_hash_num_elements(&self->values));
}
/* }}} */

/* {{{ Cassandra\Collection::get(int) */
PHP_METHOD(Collection, get)
{
  long key;
  cassandra_collection *self = NULL;
  php5to7_zval value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &key) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_COLLECTION(getThis());

  if (php_cassandra_collection_get(self, (ulong) key, &value))
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(value), 1, 0);
}
/* }}} */

/* {{{ Cassandra\Collection::find(mixed) */
PHP_METHOD(Collection, find)
{
  zval *object;
  cassandra_collection *collection = NULL;
  long index;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &object) == FAILURE)
    return;

  collection = PHP_CASSANDRA_GET_COLLECTION(getThis());

  if (php_cassandra_collection_find(collection, object, &index TSRMLS_CC))
    RETURN_LONG(index);
}
/* }}} */

/* {{{ Cassandra\Collection::count() */
PHP_METHOD(Collection, count)
{
  cassandra_collection *collection = PHP_CASSANDRA_GET_COLLECTION(getThis());
  RETURN_LONG(zend_hash_num_elements(&collection->values));
}
/* }}} */

/* {{{ Cassandra\Collection::current() */
PHP_METHOD(Collection, current)
{
  php5to7_zval *current;
  cassandra_collection *collection = PHP_CASSANDRA_GET_COLLECTION(getThis());

  if (PHP5TO7_ZEND_HASH_GET_CURRENT_DATA(&collection->values, current)) {
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_DEREF(current), 1, 0);
  }
}
/* }}} */

/* {{{ Cassandra\Collection::key() */
PHP_METHOD(Collection, key)
{
  php5to7_ulong num_key;
  cassandra_collection *collection = PHP_CASSANDRA_GET_COLLECTION(getThis());
  if (PHP5TO7_ZEND_HASH_GET_CURRENT_KEY(&collection->values, NULL, &num_key) == HASH_KEY_IS_LONG) {
    RETURN_LONG(num_key);
  }
}
/* }}} */

/* {{{ Cassandra\Collection::next() */
PHP_METHOD(Collection, next)
{
  cassandra_collection *collection = PHP_CASSANDRA_GET_COLLECTION(getThis());
  zend_hash_move_forward(&collection->values);
}
/* }}} */

/* {{{ Cassandra\Collection::valid() */
PHP_METHOD(Collection, valid)
{
  cassandra_collection *collection = PHP_CASSANDRA_GET_COLLECTION(getThis());
  RETURN_BOOL(zend_hash_has_more_elements(&collection->values) == SUCCESS);
}
/* }}} */

/* {{{ Cassandra\Collection::rewind() */
PHP_METHOD(Collection, rewind)
{
  cassandra_collection *collection = PHP_CASSANDRA_GET_COLLECTION(getThis());
  zend_hash_internal_pointer_reset(&collection->values);
}
/* }}} */

/* {{{ Cassandra\Collection::remove(key) */
PHP_METHOD(Collection, remove)
{
  long index;
  cassandra_collection *collection = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &index) == FAILURE) {
    return;
  }

  collection = PHP_CASSANDRA_GET_COLLECTION(getThis());

  if (php_cassandra_collection_del(collection, (ulong) index)) {
    RETURN_TRUE;
  }

  RETURN_FALSE;
}
/* }}} */

ZEND_BEGIN_ARG_INFO_EX(arginfo__construct, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, type)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_value, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_index, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, index)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_collection_methods[] = {
  PHP_ME(Collection, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Collection, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Collection, values, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Collection, add, arginfo_value, ZEND_ACC_PUBLIC)
  PHP_ME(Collection, get, arginfo_index, ZEND_ACC_PUBLIC)
  PHP_ME(Collection, find, arginfo_value, ZEND_ACC_PUBLIC)
  /* Countable */
  PHP_ME(Collection, count, arginfo_none, ZEND_ACC_PUBLIC)
  /* Iterator */
  PHP_ME(Collection, current, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Collection, key, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Collection, next, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Collection, valid, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Collection, rewind, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Collection, remove, arginfo_index, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_collection_handlers;

static HashTable *
php_cassandra_collection_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_collection_properties(zval *object TSRMLS_DC)
{
  php5to7_zval values;

  cassandra_collection  *self = PHP_CASSANDRA_GET_COLLECTION(object);
  HashTable             *props = zend_std_get_properties(object TSRMLS_CC);

  if (PHP5TO7_ZEND_HASH_UPDATE(props,
                               "type", sizeof("type"),
                               PHP5TO7_ZVAL_MAYBE_P(self->type), sizeof(zval))) {
    Z_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(self->type));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(values);
  array_init(PHP5TO7_ZVAL_MAYBE_P(values));
  php_cassandra_collection_populate(self, PHP5TO7_ZVAL_MAYBE_P(values));
  PHP5TO7_ZEND_HASH_UPDATE(props, "values", sizeof("values"), PHP5TO7_ZVAL_MAYBE_P(values), sizeof(zval));

  return props;
}

static int
php_cassandra_collection_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  HashPosition pos1;
  HashPosition pos2;
  php5to7_zval *current1;
  php5to7_zval *current2;
  cassandra_collection *collection1;
  cassandra_collection *collection2;
  cassandra_type *type1;
  cassandra_type *type2;
  int result;

  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  collection1 = PHP_CASSANDRA_GET_COLLECTION(obj1);
  collection2 = PHP_CASSANDRA_GET_COLLECTION(obj2);

  type1 = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(collection1->type));
  type2 = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(collection2->type));

  result = php_cassandra_type_compare(type1, type2 TSRMLS_CC);
  if (result != 0) return result;

  if (zend_hash_num_elements(&collection1->values) != zend_hash_num_elements(&collection2->values)) {
    return zend_hash_num_elements(&collection1->values) < zend_hash_num_elements(&collection2->values) ? -1 : 1;
  }

  zend_hash_internal_pointer_reset_ex(&collection1->values, &pos1);
  zend_hash_internal_pointer_reset_ex(&collection2->values, &pos2);

  while (PHP5TO7_ZEND_HASH_GET_CURRENT_DATA_EX(&collection1->values, current1, &pos1) &&
         PHP5TO7_ZEND_HASH_GET_CURRENT_DATA_EX(&collection2->values, current2, &pos2)) {
    result = php_cassandra_value_compare(PHP5TO7_ZVAL_MAYBE_DEREF(current1),
                                         PHP5TO7_ZVAL_MAYBE_DEREF(current2) TSRMLS_CC);
    if (result != 0) return result;
    zend_hash_move_forward_ex(&collection1->values, &pos1);
    zend_hash_move_forward_ex(&collection2->values, &pos2);
  }

  return 0;
}

static unsigned
php_cassandra_collection_hash_value(zval *obj TSRMLS_DC)
{
  php5to7_zval *current;
  unsigned hashv = 0;
  cassandra_collection *self = PHP_CASSANDRA_GET_COLLECTION(obj);

  if (!self->dirty) return self->hashv;

  PHP5TO7_ZEND_HASH_FOREACH_VAL(&self->values, current) {
    hashv = php_cassandra_combine_hash(hashv,
                                       php_cassandra_value_hash(PHP5TO7_ZVAL_MAYBE_DEREF(current) TSRMLS_CC));
  } PHP5TO7_ZEND_HASH_FOREACH_END(&self->values);

  self->hashv = hashv;
  self->dirty = 0;

  return hashv;
}

static void
php_cassandra_collection_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_collection *self =
      PHP5TO7_ZEND_OBJECT_GET(collection, object);

  zend_hash_destroy(&self->values);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->type);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_collection_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_collection *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(collection, ce);

  zend_hash_init(&self->values, 0, NULL, ZVAL_PTR_DTOR, 0);
  self->dirty = 1;
  PHP5TO7_ZVAL_UNDEF(self->type);

  PHP5TO7_ZEND_OBJECT_INIT(collection, self, ce);
}

void cassandra_define_Collection(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Collection", cassandra_collection_methods);
  cassandra_collection_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_collection_ce TSRMLS_CC, 1, cassandra_value_ce);
  memcpy(&cassandra_collection_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_collection_handlers.std.get_properties  = php_cassandra_collection_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_collection_handlers.std.get_gc          = php_cassandra_collection_gc;
#endif
  cassandra_collection_handlers.std.compare_objects = php_cassandra_collection_compare;
  cassandra_collection_ce->ce_flags |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_collection_ce->create_object = php_cassandra_collection_new;
  zend_class_implements(cassandra_collection_ce TSRMLS_CC, 2, spl_ce_Countable, zend_ce_iterator);

  cassandra_collection_handlers.hash_value = php_cassandra_collection_hash_value;
  cassandra_collection_handlers.std.clone_obj = NULL;
}
