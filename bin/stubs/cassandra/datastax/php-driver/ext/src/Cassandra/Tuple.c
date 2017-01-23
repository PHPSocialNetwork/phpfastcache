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
#include "src/Cassandra/Type/Tuple.h"
#include "src/Cassandra/Tuple.h"

#include "zend_hash.h"

zend_class_entry *cassandra_tuple_ce = NULL;

int
php_cassandra_tuple_set(cassandra_tuple *tuple, ulong index, zval *object TSRMLS_DC)
{
  if (PHP5TO7_ZEND_HASH_INDEX_UPDATE(&tuple->values, index, object, sizeof(zval *))) {
    Z_TRY_ADDREF_P(object);
    tuple->dirty = 1;
    return 1;
  }
  return 0;
}

static void
php_cassandra_tuple_populate(cassandra_tuple *tuple, zval *array TSRMLS_DC)
{
  php5to7_ulong index;
  cassandra_type *type;
  php5to7_zval *current;
  php5to7_zval null;

  PHP5TO7_ZVAL_MAYBE_MAKE(null);
  ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(null));

  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(tuple->type));

  PHP5TO7_ZEND_HASH_FOREACH_NUM_KEY_VAL(&type->types, index, current) {
    php5to7_zval *value = NULL;
    if (PHP5TO7_ZEND_HASH_INDEX_FIND(&tuple->values, index, value)) {
      if (add_next_index_zval(array, PHP5TO7_ZVAL_MAYBE_DEREF(value)) == SUCCESS)
        Z_TRY_ADDREF_P(PHP5TO7_ZVAL_MAYBE_DEREF(value));
      else
        break;
    } else {
      if (add_next_index_zval(array, PHP5TO7_ZVAL_MAYBE_P(null)) == SUCCESS)
        Z_TRY_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(null));
      else
        break;
    }
  } PHP5TO7_ZEND_HASH_FOREACH_END(&type->types);

#if PHP_MAJOR_VERSION < 7
  zval_ptr_dtor(&null);
#endif
}

/* {{{ Cassandra\Tuple::__construct(types) */
PHP_METHOD(Tuple, __construct)
{
  cassandra_tuple *self;
  cassandra_type *type;
  HashTable *types;
  php5to7_zval *current;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "h", &types) == FAILURE) {
    return;
  }

  self = PHP_CASSANDRA_GET_TUPLE(getThis());
  self->type = php_cassandra_type_tuple(TSRMLS_C);
  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(self->type));

  PHP5TO7_ZEND_HASH_FOREACH_VAL(types, current) {
    zval *sub_type = PHP5TO7_ZVAL_MAYBE_DEREF(current);
    php5to7_zval scalar_type;

    if (Z_TYPE_P(sub_type) == IS_STRING) {
      CassValueType value_type;
      if (!php_cassandra_value_type(Z_STRVAL_P(sub_type), &value_type TSRMLS_CC)) {
        return;
      }
      scalar_type = php_cassandra_type_scalar(value_type TSRMLS_CC);
      if (!php_cassandra_type_tuple_add(type,
                                        PHP5TO7_ZVAL_MAYBE_P(scalar_type) TSRMLS_CC)) {
        return;
      }
    } else if (Z_TYPE_P(sub_type) == IS_OBJECT &&
               instanceof_function(Z_OBJCE_P(sub_type), cassandra_type_ce TSRMLS_CC)) {
      if (!php_cassandra_type_validate(sub_type, "type" TSRMLS_CC)) {
        return;
      }
      if (php_cassandra_type_tuple_add(type,
                                        sub_type TSRMLS_CC)) {
        Z_ADDREF_P(sub_type);
      } else {
        return;
      }
    } else {
      INVALID_ARGUMENT(sub_type, "a string or an instance of Cassandra\\Type");
    }

  } PHP5TO7_ZEND_HASH_FOREACH_END(types);
}
/* }}} */

/* {{{ Cassandra\Tuple::type() */
PHP_METHOD(Tuple, type)
{
  cassandra_tuple *self = PHP_CASSANDRA_GET_TUPLE(getThis());
  RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_P(self->type), 1, 0);
}

/* {{{ Cassandra\Tuple::values() */
PHP_METHOD(Tuple, values)
{
  cassandra_tuple *self = NULL;
  array_init(return_value);
  self = PHP_CASSANDRA_GET_TUPLE(getThis());
  php_cassandra_tuple_populate(self, return_value TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Tuple::set(int, mixed) */
PHP_METHOD(Tuple, set)
{
  cassandra_tuple *self = NULL;
  long index;
  cassandra_type *type;
  php5to7_zval *sub_type;
  zval *value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "lz", &index, &value) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_TUPLE(getThis());
  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(self->type));

  if (index < 0 || index >= zend_hash_num_elements(&type->types)) {
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                            "Index out of bounds");
    return;
  }

  PHP5TO7_ZEND_HASH_INDEX_FIND(&type->types, index, sub_type);

  if (!php_cassandra_validate_object(value,
                                     PHP5TO7_ZVAL_MAYBE_DEREF(sub_type) TSRMLS_CC)) {
    return;
  }

  php_cassandra_tuple_set(self, index, value TSRMLS_CC);
}
/* }}} */

/* {{{ Cassandra\Tuple::get(int) */
PHP_METHOD(Tuple, get)
{
  cassandra_tuple *self = NULL;
  long index;
  cassandra_type *type;
  php5to7_zval *value;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &index) == FAILURE)
    return;

  self = PHP_CASSANDRA_GET_TUPLE(getThis());
  type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(self->type));

  if (index < 0 || index >= zend_hash_num_elements(&type->types)) {
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                            "Index out of bounds");
    return;
  }

  if (PHP5TO7_ZEND_HASH_INDEX_FIND(&self->values, index, value)) {
    RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_DEREF(value), 1, 0);
  }
}
/* }}} */

/* {{{ Cassandra\Tuple::count() */
PHP_METHOD(Tuple, count)
{
  cassandra_tuple *self = PHP_CASSANDRA_GET_TUPLE(getThis());
  cassandra_type *type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(self->type));
  RETURN_LONG(zend_hash_num_elements(&type->types));
}
/* }}} */

/* {{{ Cassandra\Tuple::current() */
PHP_METHOD(Tuple, current)
{
  php5to7_ulong index;
  cassandra_tuple *self = PHP_CASSANDRA_GET_TUPLE(getThis());
  cassandra_type *type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(self->type));

  if (PHP5TO7_ZEND_HASH_GET_CURRENT_KEY_EX(&type->types, NULL, &index, &self->pos) == HASH_KEY_IS_LONG) {
    php5to7_zval *value;
    if (PHP5TO7_ZEND_HASH_INDEX_FIND(&self->values, index, value)) {
      RETURN_ZVAL(PHP5TO7_ZVAL_MAYBE_DEREF(value), 1, 0);
    }
  }
}
/* }}} */

/* {{{ Cassandra\Tuple::key() */
PHP_METHOD(Tuple, key)
{
  php5to7_ulong index;
  cassandra_tuple *self = PHP_CASSANDRA_GET_TUPLE(getThis());
  cassandra_type *type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(self->type));
  if (PHP5TO7_ZEND_HASH_GET_CURRENT_KEY_EX(&type->types, NULL, &index, &self->pos) == HASH_KEY_IS_LONG) {
    RETURN_LONG(index);
  }
}
/* }}} */

/* {{{ Cassandra\Tuple::next() */
PHP_METHOD(Tuple, next)
{
  cassandra_tuple *self = PHP_CASSANDRA_GET_TUPLE(getThis());
  cassandra_type *type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(self->type));
  zend_hash_move_forward_ex(&type->types, &self->pos);
}
/* }}} */

/* {{{ Cassandra\Tuple::valid() */
PHP_METHOD(Tuple, valid)
{
  cassandra_tuple *self = PHP_CASSANDRA_GET_TUPLE(getThis());
  cassandra_type *type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(self->type));
  RETURN_BOOL(zend_hash_has_more_elements_ex(&type->types, &self->pos) == SUCCESS);
}
/* }}} */

/* {{{ Cassandra\Tuple::rewind() */
PHP_METHOD(Tuple, rewind)
{
  cassandra_tuple *self = PHP_CASSANDRA_GET_TUPLE(getThis());
  cassandra_type *type = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(self->type));
  zend_hash_internal_pointer_reset_ex(&type->types, &self->pos);
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

static zend_function_entry cassandra_tuple_methods[] = {
  PHP_ME(Tuple, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_ME(Tuple, type, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Tuple, values, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Tuple, set, arginfo_value, ZEND_ACC_PUBLIC)
  PHP_ME(Tuple, get, arginfo_index, ZEND_ACC_PUBLIC)
  /* Countable */
  PHP_ME(Tuple, count, arginfo_none, ZEND_ACC_PUBLIC)
  /* Iterator */
  PHP_ME(Tuple, current, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Tuple, key, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Tuple, next, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Tuple, valid, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(Tuple, rewind, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static php_cassandra_value_handlers cassandra_tuple_handlers;

static HashTable *
php_cassandra_tuple_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable *
php_cassandra_tuple_properties(zval *object TSRMLS_DC)
{
  php5to7_zval values;

  cassandra_tuple  *self = PHP_CASSANDRA_GET_TUPLE(object);
  HashTable             *props = zend_std_get_properties(object TSRMLS_CC);

  if (PHP5TO7_ZEND_HASH_UPDATE(props,
                               "type", sizeof("type"),
                               PHP5TO7_ZVAL_MAYBE_P(self->type), sizeof(zval))) {
    Z_ADDREF_P(PHP5TO7_ZVAL_MAYBE_P(self->type));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(values);
  array_init(PHP5TO7_ZVAL_MAYBE_P(values));
  php_cassandra_tuple_populate(self, PHP5TO7_ZVAL_MAYBE_P(values) TSRMLS_CC);
  PHP5TO7_ZEND_HASH_UPDATE(props, "values", sizeof("values"), PHP5TO7_ZVAL_MAYBE_P(values), sizeof(zval));

  return props;
}

static int
php_cassandra_tuple_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  HashPosition pos1;
  HashPosition pos2;
  php5to7_zval *current1;
  php5to7_zval *current2;
  cassandra_tuple *tuple1;
  cassandra_tuple *tuple2;
  cassandra_type *type1;
  cassandra_type *type2;
  int result;

  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  tuple1 = PHP_CASSANDRA_GET_TUPLE(obj1);
  tuple2 = PHP_CASSANDRA_GET_TUPLE(obj2);

  type1 = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(tuple1->type));
  type2 = PHP_CASSANDRA_GET_TYPE(PHP5TO7_ZVAL_MAYBE_P(tuple2->type));

  result = php_cassandra_type_compare(type1, type2 TSRMLS_CC);
  if (result != 0) return result;

  if (zend_hash_num_elements(&tuple1->values) != zend_hash_num_elements(&tuple2->values)) {
    return zend_hash_num_elements(&tuple1->values) < zend_hash_num_elements(&tuple2->values) ? -1 : 1;
  }

  zend_hash_internal_pointer_reset_ex(&tuple1->values, &pos1);
  zend_hash_internal_pointer_reset_ex(&tuple2->values, &pos2);

  while (PHP5TO7_ZEND_HASH_GET_CURRENT_DATA_EX(&tuple1->values, current1, &pos1) &&
         PHP5TO7_ZEND_HASH_GET_CURRENT_DATA_EX(&tuple2->values, current2, &pos2)) {
    result = php_cassandra_value_compare(PHP5TO7_ZVAL_MAYBE_DEREF(current1),
                                         PHP5TO7_ZVAL_MAYBE_DEREF(current2) TSRMLS_CC);
    if (result != 0) return result;
    zend_hash_move_forward_ex(&tuple1->values, &pos1);
    zend_hash_move_forward_ex(&tuple2->values, &pos2);
  }

  return 0;
}

static unsigned
php_cassandra_tuple_hash_value(zval *obj TSRMLS_DC)
{
  php5to7_zval *current;
  unsigned hashv = 0;
  cassandra_tuple *self = PHP_CASSANDRA_GET_TUPLE(obj);

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
php_cassandra_tuple_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_tuple *self =
      PHP5TO7_ZEND_OBJECT_GET(tuple, object);

  zend_hash_destroy(&self->values);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->type);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_tuple_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_tuple *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(tuple, ce);

  zend_hash_init(&self->values, 0, NULL, ZVAL_PTR_DTOR, 0);
#if PHP_MAJOR_VERSION >= 7
  self->pos = HT_INVALID_IDX;
#else
  self->pos = NULL;
#endif
  self->dirty = 1;
  PHP5TO7_ZVAL_UNDEF(self->type);

  PHP5TO7_ZEND_OBJECT_INIT(tuple, self, ce);
}

void cassandra_define_Tuple(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Tuple", cassandra_tuple_methods);
  cassandra_tuple_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_tuple_ce TSRMLS_CC, 1, cassandra_value_ce);
  memcpy(&cassandra_tuple_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_tuple_handlers.std.get_properties  = php_cassandra_tuple_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_tuple_handlers.std.get_gc          = php_cassandra_tuple_gc;
#endif
  cassandra_tuple_handlers.std.compare_objects = php_cassandra_tuple_compare;
  cassandra_tuple_ce->ce_flags |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_tuple_ce->create_object = php_cassandra_tuple_new;
  zend_class_implements(cassandra_tuple_ce TSRMLS_CC, 2, spl_ce_Countable, zend_ce_iterator);

  cassandra_tuple_handlers.hash_value = php_cassandra_tuple_hash_value;
  cassandra_tuple_handlers.std.clone_obj = NULL;
}
