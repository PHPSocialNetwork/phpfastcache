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

zend_class_entry *cassandra_ssl_ce = NULL;

static zend_function_entry cassandra_ssl_methods[] = {
  PHP_FE_END
};

static zend_object_handlers cassandra_ssl_handlers;

static HashTable *
php_cassandra_ssl_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_ssl_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_ssl_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_ssl *self = PHP5TO7_ZEND_OBJECT_GET(ssl, object);

  cass_ssl_free(self->ssl);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_ssl_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_ssl *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(ssl, ce);

  self->ssl = cass_ssl_new();

  PHP5TO7_ZEND_OBJECT_INIT(ssl, self, ce);
}

void cassandra_define_SSLOptions(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\SSLOptions", cassandra_ssl_methods);
  cassandra_ssl_ce = zend_register_internal_class(&ce TSRMLS_CC);
  cassandra_ssl_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_ssl_ce->create_object = php_cassandra_ssl_new;

  memcpy(&cassandra_ssl_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_ssl_handlers.get_properties  = php_cassandra_ssl_properties;
  cassandra_ssl_handlers.compare_objects = php_cassandra_ssl_compare;
  cassandra_ssl_handlers.clone_obj = NULL;
}
