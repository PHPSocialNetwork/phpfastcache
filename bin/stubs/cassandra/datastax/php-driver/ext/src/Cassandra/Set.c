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
#include "src/Cassandra/Set.h"

zend_class_entry *cassandra_set_ce = NULL;

int
php_cassandra_set_add(cassandra_set *set, zval *object TSRMLS_DC)
{
  cassandra_set_entry *entry;
  cassandra_type *type;

  if (Z_TYPE_P(object) == IS_NULL) {
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                            "Invalid value: null is not supported inside sets");
    return 0;
  }

  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(set->type));

  if (!php_cassandra_validate_object(object, PHP5TO7_ZVAL_MAYBE_P(type->value_type) TSRMLS_CC)) {
    return 0;
  }

  HASH_FIND_ZVAL(set->entries, object, entry);
  if (entry == NULL) {
    set->dirty = 1;
    entry = (cassandra_set_entry *) emalloc(sizeof(cassandra_set_entry));
    PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(entry->value), object);
    HASH_ADD_ZVAL(set->entries, value, entry);
  }

  return 1;
}

static int
php_cassandra_set_del(cassandra_set *set, zval *object TSRMLS_DC)
{
  cassandra_set_entry *entry;
  cassandra_type *type;
  int result = 0;

  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(set->type));

  if (!php_cassandra_validate_object(object, PHP5TO7_ZVAL_MAYBE_P(type->value_type) TSRMLS_CC)) {
    return 0;
  }

  HASH_FIND_ZVAL(set->entries, object, entry);
  if (entry != NULL) {
    set->dirty = 1;
    if (entry == set->iter_temp) {
      set->iter_temp = (cassandra_set_entry *)set->iter_temp->hh.next;
    }
    HASH_DEL(set->entries, entry);
    zval_ptr_dtor(&entry->value);
    efree(entry);
    result = 1;
  }

  return result;
}

static int
php_cassandra_set_has(cassandra_set *set, zval *object TSRMLS_DC)
{
  cassandra_set_entry *entry;
  cassandra_type *type;
  int result = 0;

  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(set->type));

  if (!php_cassandra_validate_object(object, PHP5TO7_ZVAL_MAYBE_P(type->value_type) TSRMLS_CC)) {
    return 0;
  }

  HASH_FIND_ZVAL(set->entries, object, entry);
  if (entry != NULL) {
    result = 1;
  }

  return result;
}

static void
php_cassandra_set_populate(cassandra_set *set, zval *array TSRMLS_DC)
{
  cassandra_set_entry *curr, *temp;
  HASH_ITER(hh, set->entries, curr, temp) {
    if (add_next_index_zval(array, PHP5TO7_ZVAL_MAYBE_P(curr->value)) != SUCCESS) {
      break;
    }
    Z_TRY_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(curr->value));
  }
}

/* {{{ Cassandra\Set::__construct(type) */
PHP_METHOD(Set, __construct)
{
  cassandra_set *self;
  zval *type;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &type) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_SET(getThis());

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
    self->type = php_cassandra_type_set(type TSRMLS_CC);
    Z_ADDREF_P(type);
  } else {
    INVALID_ARGUMENT(type, "a string or an instance of Cassandra\\Type");
  }
}
/* }}} */

/* {{{ Cassandra\Set::type() */
PHP_METHOD(Set, type)
{
  cassandra_set *self = PHP_CASSANDRA_GET_SET(getThis());
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->type), 1, 0);
}
/* }}} */

/* {{{ Cassandra\Set::values() */
PHP_METHOD(Set, values)
{
  cassandra_set *set = NULL;
  array_init(return_value);
  set = PHP_CASSANDRA_GET_SET(getThis());
  php_cassandra_set_populate(set, return_value TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Set::add(value) */
PHP_METHOD(Set, add)
{
  cassandra_set *self = NULL;

  zval *object;
  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &object) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_SET(getThis());

  if (php_cassandra_set_add(self, object TSRMLS_CC))
    RETURN_TRUE;

  RETURN_FALSE;
}
/* }}} */

/* {{{ Cassandra\Set::remove(value) */
PHP_METHOD(Set, remove)
{
  cassandra_set *self = NULL;

  zval *object;
  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &object) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_SET(getThis());

  if (php_cassandra_set_del(self, object TSRMLS_CC))
    RETURN_TRUE;

  RETURN_FALSE;
}
/* }}} */

/* {{{ Cassandra\Set::has(value) */
PHP_METHOD(Set, has)
{
  cassandra_set *self = NULL;

  zval *object;
  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &object) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_SET(getThis());

  if (php_cassandra_set_has(self, object TSRMLS_CC))
    RETURN_TRUE;

  RETURN_FALSE;
}
/* }}} */

/* {{{ Cassandra\Set::count() */
PHP_METHOD(Set, count)
{
  cassandra_set *self = PHP_CASSANDRA_GET_SET(getThis());
  RETURN_LONG((long)HASH_COUNT(self->entries));
}
/* }}} */

/* {{{ Cassandra\Set::current() */
PHP_METHOD(Set, current)
{
  cassandra_set *self = PHP_CASSANDRA_GET_SET(getThis());
  if (self->iter_curr != NULL)
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->iter_curr->value), 1, 0);
}
/* }}} */

/* {{{ Cassandra\Set::key() */
PHP_METHOD(Set, key)
{
  cassandra_set *self = PHP_CASSANDRA_GET_SET(getThis());
  RETURN_LONG(self->iter_index);
}
/* }}} */

/* {{{ Cassandra\Set::next() */
PHP_METHOD(Set, next)
{
  cassandra_set *self = PHP_CASSANDRA_GET_SET(getThis());
  self->iter_curr = self->iter_temp;
  self->iter_temp = self->iter_temp != NULL ? (cassandra_set_entry *)self->iter_temp->hh.next : NULL;
  self->iter_index++;
}
/* }}} */

/* {{{ Cassandra\Set::valid() */
PHP_METHOD(Set, valid)
{
  cassandra_set *self = PHP_CASSANDRA_GET_SET(getThis());
  RETURN_BOOL(self->iter_curr != NULL);
}
/* }}} */

/* {{{ Cassandra\Set::rewind() */
PHP_METHOD(Set, rewind)
{
  cassandra_set *self = PHP_CASSANDRA_GET_SET(getThis());
  self->iter_curr = self->entries;
  self->iter_temp = self->entries != NULL ? (cassandra_set_entry *)self->entries->hh.next : NULL;
  self->iter_index = 0;
}
/* }}} */

ZEND_BEGIN_ARG_INFO_EX(arginfo__construct, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, type)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_one, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_set_methods[] = {
  PHP_ME(Set, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Set, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Set, values, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Set, add, arginfo_one, ZEND_ACC_PUBLIC)
  PHP_ME(Set, has, arginfo_one, ZEND_ACC_PUBLIC)
  PHP_ME(Set, remove, arginfo_one, ZEND_ACC_PUBLIC)
  /* Countable */
  PHP_ME(Set, count, arginfo_none, ZEND_ACC_PUBLIC)
  /* Iterator */
  PHP_ME(Set, current, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Set, key, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Set, next, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Set, valid, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Set, rewind, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_set_handlers;

static HashTable *
php_cassandra_set_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_set_properties(zval *object TSRMLS_DC)
{
  php5to7_zval values;

  cassandra_set *self = PHP_CASSANDRA_GET_SET(object);
  HashTable     *props = zend_std_get_properties(object TSRMLS_CC);


  if (PHP5TO7_ZEND_HASH_UPDATE(props,
                               "type", sizeof("type"),
                               PHP5TO7_ZVAL_MAYBE_P(self->type), sizeof(zval))) {
    Z_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(self->type));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(values);
  array_init(PHP5TO7_ZVAL_MAYBE_P(values));
  php_cassandra_set_populate(self , PHP5TO7_ZVAL_MAYBE_P(values) TSRMLS_CC);
  PHP5TO7_ZEND_HASH_SORT(Z_ARRVAL_P(PHP5TO7_ZVAL_MAYBE_P(values)), php_cassandra_data_compare, 1);
  PHP5TO7_ZEND_HASH_UPDATE(props, "values", sizeof("values"), PHP5TO7_ZVAL_MAYBE_P(values), sizeof(zval));

  return props;
}

static int
php_cassandra_set_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  cassandra_set_entry *curr, *temp;
  cassandra_set *set1;
  cassandra_set *set2;
  cassandra_type *type1;
  cassandra_type *type2;
  int result;

  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  set1 = PHP_CASSANDRA_GET_SET(obj1);
  set2 = PHP_CASSANDRA_GET_SET(obj2);

  type1 = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(set1->type));
  type2 = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(set2->type));

  result = php_cassandra_type_compare(type1, type2 TSRMLS_CC);
  if (result != 0) return result;

  if (HASH_COUNT(set1->entries) != HASH_COUNT(set1->entries)) {
   return HASH_COUNT(set1->entries) < HASH_COUNT(set1->entries) ? -1 : 1;
  }

  HASH_ITER(hh, set1->entries, curr, temp) {
    cassandra_set_entry *entry;
    HASH_FIND_ZVAL(set2->entries, PHP5TO7_ZVAL_MAYBE_P(curr->value), entry);
    if (entry == NULL) {
      return 1;
    }
  }

  return 0;
}

static unsigned
php_cassandra_set_hash_value(zval *obj TSRMLS_DC)
{
  unsigned hashv = 0;
  cassandra_set_entry *curr,  *temp;
  cassandra_set *self = PHP_CASSANDRA_GET_SET(obj);

  if (!self->dirty) return self->hashv;

  HASH_ITER(hh, self->entries, curr, temp) {
    hashv = php_cassandra_combine_hash(hashv, php_cassandra_value_hash(PHP5TO7_ZVAL_MAYBE_P(curr->value) TSRMLS_CC));
  }

  self->hashv = hashv;
  self->dirty = 0;

  return hashv;
}

static void
php_cassandra_set_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_set *self = PHP5TO7_ZEND_OBJECT_GET(set, object);
  cassandra_set_entry *curr, *temp;

  HASH_ITER(hh, self->entries, curr, temp) {
    zval_ptr_dtor(&curr->value);
    HASH_DEL(self->entries, curr);
    efree(curr);
  }

  PHP5TO7_ZVAL_MAYBE_DESTROY(self->type);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_set_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_set *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(set, ce);

  self->entries = self->iter_curr = self->iter_temp = NULL;
  self->iter_index = 0;
  self->dirty = 1;
  PHP5TO7_ZVAL_UNDEF(self->type);

  PHP5TO7_ZEND_OBJECT_INIT(set, self, ce);
}

void cassandra_define_Set(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Set", cassandra_set_methods);
  cassandra_set_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_set_ce TSRMLS_CC, 1, cassandra_value_ce);
  memcpy(&cassandra_set_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_set_handlers.std.get_properties  = php_cassandra_set_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_set_handlers.std.get_gc          = php_cassandra_set_gc;
#endif
  cassandra_set_handlers.std.compare_objects = php_cassandra_set_compare;
  cassandra_set_ce->ce_flags |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_set_ce->create_object = php_cassandra_set_new;
  zend_class_implements(cassandra_set_ce TSRMLS_CC, 2, spl_ce_Countable, zend_ce_iterator);

  cassandra_set_handlers.hash_value = php_cassandra_set_hash_value;
  cassandra_set_handlers.std.clone_obj = NULL;
}
