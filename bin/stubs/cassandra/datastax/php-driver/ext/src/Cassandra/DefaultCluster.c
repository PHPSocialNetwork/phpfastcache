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
#include "util/future.h"

zend_class_entry *cassandra_default_cluster_ce = NULL;

ZEND_EXTERN_MODULE_GLOBALS(cassandra)

PHP_METHOD(DefaultCluster, connect)
{
  CassFuture *future = NULL;
  char *hash_key;
  php5to7_size hash_key_len = 0;
  char *keyspace = NULL;
  php5to7_size keyspace_len;
  zval *timeout = NULL;
  cassandra_psession *psession;
  cassandra_cluster *cluster = NULL;
  cassandra_session *session = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|sz", &keyspace, &keyspace_len, &timeout) == FAILURE) {
    return;
  }

  cluster = PHP_CASSANDRA_GET_CLUSTER(getThis());

  object_init_ex(return_value, cassandra_default_session_ce);
  session = PHP_CASSANDRA_GET_SESSION(return_value);

  session->default_consistency = cluster->default_consistency;
  session->default_page_size   = cluster->default_page_size;
  session->persist             = cluster->persist;

  if (!PHP5TO7_ZVAL_IS_UNDEF(session->default_timeout)) {
    PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(session->default_timeout),
                      PHP5TO7_ZVAL_MAYBE_P(cluster->default_timeout));
  }

  if (session->persist) {
    php5to7_zend_resource_le *le;

    hash_key_len = spprintf(&hash_key, 0, "%s:session:%s",
                            cluster->hash_key, SAFE_STR(keyspace));

    if (PHP5TO7_ZEND_HASH_FIND(&EG(persistent_list), hash_key, hash_key_len + 1, le) &&
        Z_RES_P(le)->type == php_le_cassandra_session()) {
      psession = (cassandra_psession *) Z_RES_P(le)->ptr;
      session->session = psession->session;
      future = psession->future;
    }
  }

  if (future == NULL) {
    php5to7_zend_resource_le resource;

    session->session = cass_session_new();

    if (keyspace) {
      future = cass_session_connect_keyspace(session->session, cluster->cluster, keyspace);
    } else {
      future = cass_session_connect(session->session, cluster->cluster);
    }

    if (session->persist) {
      psession = (cassandra_psession *) pecalloc(1, sizeof(cassandra_psession), 1);
      psession->session = session->session;
      psession->future  = future;

#if PHP_MAJOR_VERSION >= 7
      ZVAL_NEW_PERSISTENT_RES(&resource, 0, psession, php_le_cassandra_session());
      PHP5TO7_ZEND_HASH_UPDATE(&EG(persistent_list), hash_key, hash_key_len + 1, &resource, sizeof(php5to7_zend_resource_le));
      CASSANDRA_G(persistent_sessions)++;
#else
      resource.type = php_le_cassandra_session();
      resource.ptr = psession;
      PHP5TO7_ZEND_HASH_UPDATE(&EG(persistent_list), hash_key, hash_key_len + 1, resource, sizeof(php5to7_zend_resource_le));
      CASSANDRA_G(persistent_sessions)++;
#endif
    }
  }

  if (php_cassandra_future_wait_timed(future, timeout TSRMLS_CC) == FAILURE) {
    if (session->persist) {
      efree(hash_key);
    } else {
      cass_future_free(future);
    }

    return;
  }

  if (php_cassandra_future_is_error(future TSRMLS_CC) == FAILURE) {
    if (session->persist) {
      if (PHP5TO7_ZEND_HASH_DEL(&EG(persistent_list), hash_key, hash_key_len + 1)) {
        session->session = NULL;
      }

      efree(hash_key);
    } else {
      cass_future_free(future);
    }

    return;
  }

  if (session->persist)
    efree(hash_key);
}

PHP_METHOD(DefaultCluster, connectAsync)
{
  char *hash_key;
  php5to7_size hash_key_len = 0;
  char *keyspace = NULL;
  php5to7_size keyspace_len;
  cassandra_cluster *cluster = NULL;
  cassandra_future_session *future = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|s", &keyspace, &keyspace_len) == FAILURE) {
    return;
  }

  cluster = PHP_CASSANDRA_GET_CLUSTER(getThis());

  object_init_ex(return_value, cassandra_future_session_ce);
  future = PHP_CASSANDRA_GET_FUTURE_SESSION(return_value);

  future->persist = cluster->persist;

  if (cluster->persist) {
    php5to7_zend_resource_le *le;

    hash_key_len = spprintf(&hash_key, 0,
      "%s:session:%s", cluster->hash_key, SAFE_STR(keyspace));

    future->hash_key     = hash_key;
    future->hash_key_len = hash_key_len;

    if (PHP5TO7_ZEND_HASH_FIND(&EG(persistent_list), hash_key, hash_key_len + 1, le)) {
      if (Z_TYPE_P(le) == php_le_cassandra_session()) {
        cassandra_psession *psession = (cassandra_psession *) Z_RES_P(le)->ptr;
        future->session = psession->session;
        future->future  = psession->future;
        return;
      }
    }
  }

  future->session = cass_session_new();

  if (keyspace) {
    future->future = cass_session_connect_keyspace(future->session, cluster->cluster, keyspace);
  } else {
    future->future = cass_session_connect(future->session, cluster->cluster);
  }

  if (cluster->persist) {
    php5to7_zend_resource_le resource;
    cassandra_psession *psession =
      (cassandra_psession *) pecalloc(1, sizeof(cassandra_psession), 1);
    psession->session = future->session;
    psession->future  = future->future;

#if PHP_MAJOR_VERSION >= 7
    ZVAL_NEW_PERSISTENT_RES(&resource, 0, psession, php_le_cassandra_session());
    PHP5TO7_ZEND_HASH_UPDATE(&EG(persistent_list), hash_key, hash_key_len + 1, &resource, sizeof(php5to7_zend_resource_le));
    CASSANDRA_G(persistent_sessions)++;
#else
      resource.type = php_le_cassandra_session();
      resource.ptr = psession;
      PHP5TO7_ZEND_HASH_UPDATE(&EG(persistent_list), hash_key, hash_key_len + 1, resource, sizeof(php5to7_zend_resource_le));
      CASSANDRA_G(persistent_sessions)++;
#endif

  }
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_connect, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, keyspace)
  ZEND_ARG_INFO(0, timeout)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_connectAsync, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, keyspace)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_default_cluster_methods[] = {
  PHP_ME(DefaultCluster, connect, arginfo_connect, ZEND_ACC_PUBLIC)
  PHP_ME(DefaultCluster, connectAsync, arginfo_connectAsync, ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_default_cluster_handlers;

static HashTable *
php_cassandra_default_cluster_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_default_cluster_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_default_cluster_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_cluster *self = PHP5TO7_ZEND_OBJECT_GET(cluster, object);

  if (self->persist) {
    efree(self->hash_key);
  } else {
    if (self->cluster) {
      cass_cluster_free(self->cluster);
    }
  }

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_default_cluster_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_cluster *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(cluster, ce);

  self->cluster             = NULL;
  self->default_consistency = PHP_CASSANDRA_DEFAULT_CONSISTENCY;
  self->default_page_size   = 5000;
  self->persist             = 0;
  self->hash_key            = NULL;

  PHP5TO7_ZVAL_UNDEF(self->default_timeout);

  PHP5TO7_ZEND_OBJECT_INIT_EX(cluster, default_cluster, self, ce);
}

void cassandra_define_DefaultCluster(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\DefaultCluster", cassandra_default_cluster_methods);
  cassandra_default_cluster_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_default_cluster_ce TSRMLS_CC, 1, cassandra_cluster_ce);
  cassandra_default_cluster_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_default_cluster_ce->create_object = php_cassandra_default_cluster_new;

  memcpy(&cassandra_default_cluster_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_default_cluster_handlers.get_properties  = php_cassandra_default_cluster_properties;
  cassandra_default_cluster_handlers.compare_objects = php_cassandra_default_cluster_compare;
}
