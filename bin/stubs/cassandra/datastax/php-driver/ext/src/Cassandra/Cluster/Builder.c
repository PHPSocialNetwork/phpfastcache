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
#include "util/consistency.h"

#if PHP_MAJOR_VERSION >= 7
#include <zend_smart_str.h>
#else
#include <ext/standard/php_smart_str.h>
#endif

zend_class_entry *cassandra_cluster_builder_ce = NULL;

ZEND_EXTERN_MODULE_GLOBALS(cassandra)

PHP_METHOD(ClusterBuilder, build)
{
  char *hash_key;
  int   hash_key_len = 0;
  cassandra_cluster *cluster = NULL;
  php5to7_zend_resource_le resource;

  cassandra_cluster_builder *builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  object_init_ex(return_value, cassandra_default_cluster_ce);
  cluster = PHP_CASSANDRA_GET_CLUSTER(return_value);

  cluster->persist             = builder->persist;
  cluster->default_consistency = builder->default_consistency;
  cluster->default_page_size   = builder->default_page_size;

  PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(cluster->default_timeout),
                    PHP5TO7_ZVAL_MAYBE_P(builder->default_timeout));

  if (builder->persist) {
    php5to7_zend_resource_le *le;

    hash_key_len = spprintf(&hash_key, 0,
      "cassandra:%s:%d:%d:%s:%d:%d:%d:%s:%s:%d:%d:%d:%d:%d:%d:%d:%d:%d:%d:%d:%d:%s:%s:%s:%s",
      builder->contact_points, builder->port, builder->load_balancing_policy,
      SAFE_STR(builder->local_dc), builder->used_hosts_per_remote_dc,
      builder->allow_remote_dcs_for_local_cl, builder->use_token_aware_routing,
      SAFE_STR(builder->username), SAFE_STR(builder->password),
      builder->connect_timeout, builder->request_timeout,
      builder->protocol_version, builder->io_threads,
      builder->core_connections_per_host, builder->max_connections_per_host,
      builder->reconnect_interval, builder->enable_latency_aware_routing,
      builder->enable_tcp_nodelay, builder->enable_tcp_keepalive,
      builder->tcp_keepalive_delay, builder->enable_schema,
      SAFE_STR(builder->whitelist_hosts), SAFE_STR(builder->whitelist_dcs),
      SAFE_STR(builder->blacklist_hosts), SAFE_STR(builder->blacklist_dcs));

    cluster->hash_key     = hash_key;
    cluster->hash_key_len = hash_key_len;

    if (PHP5TO7_ZEND_HASH_FIND(&EG(persistent_list), hash_key, hash_key_len + 1, le)) {
      if (Z_TYPE_P(le) == php_le_cassandra_cluster()) {
        cluster->cluster = (CassCluster*) Z_RES_P(le)->ptr;
        return;
      }
    }
  }

  cluster->cluster = cass_cluster_new();

  if (builder->load_balancing_policy == LOAD_BALANCING_ROUND_ROBIN) {
    cass_cluster_set_load_balance_round_robin(cluster->cluster);
  }

  if (builder->load_balancing_policy == LOAD_BALANCING_DC_AWARE_ROUND_ROBIN) {
    ASSERT_SUCCESS(cass_cluster_set_load_balance_dc_aware(cluster->cluster, builder->local_dc,
      builder->used_hosts_per_remote_dc, builder->allow_remote_dcs_for_local_cl));
  }

  if (builder->blacklist_hosts != NULL) {
      cass_cluster_set_blacklist_filtering(cluster->cluster, builder->blacklist_hosts);
  }

  if (builder->whitelist_hosts != NULL) {
      cass_cluster_set_whitelist_filtering(cluster->cluster, builder->whitelist_hosts);
  }

  if (builder->blacklist_dcs != NULL) {
      cass_cluster_set_blacklist_dc_filtering(cluster->cluster, builder->blacklist_dcs);
  }

  if (builder->whitelist_dcs != NULL) {
      cass_cluster_set_whitelist_dc_filtering(cluster->cluster, builder->whitelist_dcs);
  }

  cass_cluster_set_token_aware_routing(cluster->cluster, builder->use_token_aware_routing);

  if (builder->username) {
    cass_cluster_set_credentials(cluster->cluster, builder->username, builder->password);
  }

  cass_cluster_set_connect_timeout(cluster->cluster, builder->connect_timeout);
  cass_cluster_set_request_timeout(cluster->cluster, builder->request_timeout);

  if (!PHP5TO7_ZVAL_IS_UNDEF(builder->ssl_options)) {
    cassandra_ssl *options = PHP_CASSANDRA_GET_SSL(PHP5TO7_ZVAL_MAYBE_P(builder->ssl_options));
    cass_cluster_set_ssl(cluster->cluster, options->ssl);
  }

  ASSERT_SUCCESS(cass_cluster_set_contact_points(cluster->cluster, builder->contact_points));
  ASSERT_SUCCESS(cass_cluster_set_port(cluster->cluster, builder->port));

  ASSERT_SUCCESS(cass_cluster_set_protocol_version(cluster->cluster, builder->protocol_version));
  ASSERT_SUCCESS(cass_cluster_set_num_threads_io(cluster->cluster, builder->io_threads));
  ASSERT_SUCCESS(cass_cluster_set_core_connections_per_host(cluster->cluster, builder->core_connections_per_host));
  ASSERT_SUCCESS(cass_cluster_set_max_connections_per_host(cluster->cluster, builder->max_connections_per_host));
  cass_cluster_set_reconnect_wait_time(cluster->cluster, builder->reconnect_interval);
  cass_cluster_set_latency_aware_routing(cluster->cluster, builder->enable_latency_aware_routing);
  cass_cluster_set_tcp_nodelay(cluster->cluster, builder->enable_tcp_nodelay);
  cass_cluster_set_tcp_keepalive(cluster->cluster, builder->enable_tcp_keepalive, builder->tcp_keepalive_delay);
  cass_cluster_set_use_schema(cluster->cluster, builder->enable_schema);

  if (!PHP5TO7_ZVAL_IS_UNDEF(builder->timestamp_gen)) {
    cassandra_timestamp_gen *timestamp_gen =
        PHP_CASSANDRA_GET_TIMESTAMP_GEN(PHP5TO7_ZVAL_MAYBE_P(builder->timestamp_gen));
    cass_cluster_set_timestamp_gen(cluster->cluster, timestamp_gen->gen);
  }

  if (builder->persist) {
#if PHP_MAJOR_VERSION >= 7
    ZVAL_NEW_PERSISTENT_RES(&resource, 0, cluster->cluster, php_le_cassandra_cluster());

    if (PHP5TO7_ZEND_HASH_UPDATE(&EG(persistent_list), hash_key, hash_key_len + 1, &resource, sizeof(php5to7_zend_resource_le))) {
      CASSANDRA_G(persistent_clusters)++;
    }
#else
    resource.type = php_le_cassandra_cluster();
    resource.ptr = cluster->cluster;

    if (PHP5TO7_ZEND_HASH_UPDATE(&EG(persistent_list), hash_key, hash_key_len + 1, resource, sizeof(php5to7_zend_resource_le))) {
      CASSANDRA_G(persistent_clusters)++;
    }
#endif
  }

  if (!PHP5TO7_ZVAL_IS_UNDEF(builder->retry_policy)) {
    cassandra_retry_policy *retry_policy =
        PHP_CASSANDRA_GET_RETRY_POLICY(PHP5TO7_ZVAL_MAYBE_P(builder->retry_policy));
    cass_cluster_set_retry_policy(cluster->cluster, retry_policy->policy);
  }
}

PHP_METHOD(ClusterBuilder, withDefaultConsistency)
{
  zval *consistency = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &consistency) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (php_cassandra_get_consistency(consistency, &builder->default_consistency TSRMLS_CC) == FAILURE) {
    return;
  }

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withDefaultPageSize)
{
  zval *pageSize = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &pageSize) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (Z_TYPE_P(pageSize) == IS_NULL) {
    builder->default_page_size = -1;
  } else if (Z_TYPE_P(pageSize) == IS_LONG &&
             Z_LVAL_P(pageSize) > 0) {
    builder->default_page_size = Z_LVAL_P(pageSize);
  } else {
    INVALID_ARGUMENT(pageSize, "a positive integer or null");
  }

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withDefaultTimeout)
{
  zval *timeout = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &timeout) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (Z_TYPE_P(timeout) == IS_NULL) {
    PHP5TO7_ZVAL_MAYBE_DESTROY(builder->default_timeout);
    PHP5TO7_ZVAL_UNDEF(builder->default_timeout);
  } else if ((Z_TYPE_P(timeout) == IS_LONG && Z_LVAL_P(timeout) > 0) ||
             (Z_TYPE_P(timeout) == IS_DOUBLE && Z_LVAL_P(timeout) > 0)) {
    PHP5TO7_ZVAL_MAYBE_DESTROY(builder->default_timeout);
    PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(builder->default_timeout), timeout);
  } else {
    INVALID_ARGUMENT(timeout, "a number of seconds greater than zero or null");
  }

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withContactPoints)
{
  zval *host = NULL;
  php5to7_zval_args args = NULL;
  int argc = 0, i;
  smart_str contactPoints = PHP5TO7_SMART_STR_INIT;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "+", &args, &argc) == FAILURE) {
    return;
  }

  for (i = 0; i < argc; i++) {
    host = PHP5TO7_ZVAL_ARG(args[i]);

    if (Z_TYPE_P(host) != IS_STRING) {
      smart_str_free(&contactPoints);
      throw_invalid_argument(host, "host", "a string ip address or hostname" TSRMLS_CC);
      PHP5TO7_MAYBE_EFREE(args);
      return;
    }

    if (i > 0) {
      smart_str_appendl(&contactPoints, ",", 1);
    }

    smart_str_appendl(&contactPoints, Z_STRVAL_P(host), Z_STRLEN_P(host));
  }

  PHP5TO7_MAYBE_EFREE(args);
  smart_str_0(&contactPoints);

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  efree(builder->contact_points);
#if PHP_MAJOR_VERSION >= 7
  builder->contact_points = estrndup(contactPoints.s->val, contactPoints.s->len);
  smart_str_free(&contactPoints);
#else
  builder->contact_points = contactPoints.c;
#endif

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withPort)
{
  zval *port = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &port) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (Z_TYPE_P(port) == IS_LONG &&
      Z_LVAL_P(port) > 0 &&
      Z_LVAL_P(port) < 65536) {
    builder->port = Z_LVAL_P(port);
  } else {
    INVALID_ARGUMENT(port, "an integer between 1 and 65535");
  }

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withRoundRobinLoadBalancingPolicy)
{
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters_none() == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (builder->local_dc) {
    efree(builder->local_dc);
    builder->local_dc = NULL;
  }

  builder->load_balancing_policy = LOAD_BALANCING_ROUND_ROBIN;

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withDatacenterAwareRoundRobinLoadBalancingPolicy)
{
  char *local_dc;
  php5to7_size local_dc_len;
  zval *hostPerRemoteDatacenter = NULL;
  zend_bool allow_remote_dcs_for_local_cl;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "szb", &local_dc, &local_dc_len, &hostPerRemoteDatacenter, &allow_remote_dcs_for_local_cl) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(hostPerRemoteDatacenter) != IS_LONG ||
      Z_LVAL_P(hostPerRemoteDatacenter) < 0) {
    INVALID_ARGUMENT(hostPerRemoteDatacenter, "a positive integer");
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (builder->local_dc) {
    efree(builder->local_dc);
    builder->local_dc = NULL;
  }

  builder->load_balancing_policy         = LOAD_BALANCING_DC_AWARE_ROUND_ROBIN;
  builder->local_dc                      = estrndup(local_dc, local_dc_len);
  builder->used_hosts_per_remote_dc      = Z_LVAL_P(hostPerRemoteDatacenter);
  builder->allow_remote_dcs_for_local_cl = allow_remote_dcs_for_local_cl;

  RETURN_ZVAL(getThis(), 1, 0);
}


PHP_METHOD(ClusterBuilder, withBlackListHosts)
{
    zval *hosts = NULL;
    php5to7_zval_args args = NULL;
    int argc = 0, i;
    smart_str blacklist_hosts = PHP5TO7_SMART_STR_INIT;
    cassandra_cluster_builder *builder = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "+", &args, &argc) == FAILURE) {
      return;
    }

    for (i = 0; i < argc; i++) {
      hosts = PHP5TO7_ZVAL_ARG(args[i]);

      if (Z_TYPE_P(hosts) != IS_STRING) {
        smart_str_free(&blacklist_hosts);
        throw_invalid_argument(hosts, "hosts", "a string ip address or hostname" TSRMLS_CC);
        PHP5TO7_MAYBE_EFREE(args);
        return;
      }

      if (i > 0) {
        smart_str_appendl(&blacklist_hosts, ",", 1);
      }

      smart_str_appendl(&blacklist_hosts, Z_STRVAL_P(hosts), Z_STRLEN_P(hosts));
    }

    PHP5TO7_MAYBE_EFREE(args);
    smart_str_0(&blacklist_hosts);

    builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

    efree(builder->blacklist_hosts);
  #if PHP_MAJOR_VERSION >= 7
    builder->blacklist_hosts = estrndup(blacklist_hosts.s->val, blacklist_hosts.s->len);
    smart_str_free(&blacklist_hosts);
  #else
    builder->blacklist_hosts = blacklist_hosts.c;
  #endif

    RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withWhiteListHosts)
{
    zval *hosts = NULL;
    php5to7_zval_args args = NULL;
    int argc = 0, i;
    smart_str whitelist_hosts = PHP5TO7_SMART_STR_INIT;
    cassandra_cluster_builder *builder = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "+", &args, &argc) == FAILURE) {
      return;
    }

    for (i = 0; i < argc; i++) {
      hosts = PHP5TO7_ZVAL_ARG(args[i]);

      if (Z_TYPE_P(hosts) != IS_STRING) {
        smart_str_free(&whitelist_hosts);
        throw_invalid_argument(hosts, "hosts", "a string ip address or hostname" TSRMLS_CC);
        PHP5TO7_MAYBE_EFREE(args);
        return;
      }

      if (i > 0) {
        smart_str_appendl(&whitelist_hosts, ",", 1);
      }

      smart_str_appendl(&whitelist_hosts, Z_STRVAL_P(hosts), Z_STRLEN_P(hosts));
    }

    PHP5TO7_MAYBE_EFREE(args);
    smart_str_0(&whitelist_hosts);

    builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

    efree(builder->whitelist_hosts);
  #if PHP_MAJOR_VERSION >= 7
    builder->whitelist_hosts = estrndup(whitelist_hosts.s->val, whitelist_hosts.s->len);
    smart_str_free(&whitelist_hosts);
  #else
    builder->whitelist_hosts = whitelist_hosts.c;
  #endif

    RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withBlackListDCs)
{
    zval *dcs = NULL;
    php5to7_zval_args args = NULL;
    int argc = 0, i;
    smart_str blacklist_dcs = PHP5TO7_SMART_STR_INIT;
    cassandra_cluster_builder *builder = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "+", &args, &argc) == FAILURE) {
      return;
    }

    for (i = 0; i < argc; i++) {
      dcs = PHP5TO7_ZVAL_ARG(args[i]);

      if (Z_TYPE_P(dcs) != IS_STRING) {
        smart_str_free(&blacklist_dcs);
        throw_invalid_argument(dcs, "dcs", "a string" TSRMLS_CC);
        PHP5TO7_MAYBE_EFREE(args);
        return;
      }

      if (i > 0) {
        smart_str_appendl(&blacklist_dcs, ",", 1);
      }

      smart_str_appendl(&blacklist_dcs, Z_STRVAL_P(dcs), Z_STRLEN_P(dcs));
    }

    PHP5TO7_MAYBE_EFREE(args);
    smart_str_0(&blacklist_dcs);

    builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

    efree(builder->blacklist_dcs);
  #if PHP_MAJOR_VERSION >= 7
    builder->blacklist_dcs = estrndup(blacklist_dcs.s->val, blacklist_dcs.s->len);
    smart_str_free(&blacklist_dcs);
  #else
    builder->blacklist_dcs = blacklist_dcs.c;
  #endif

    RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withWhiteListDCs)
{
    zval *dcs = NULL;
    php5to7_zval_args args = NULL;
    int argc = 0, i;
    smart_str whitelist_dcs = PHP5TO7_SMART_STR_INIT;
    cassandra_cluster_builder *builder = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "+", &args, &argc) == FAILURE) {
      return;
    }

    for (i = 0; i < argc; i++) {
      dcs = PHP5TO7_ZVAL_ARG(args[i]);

      if (Z_TYPE_P(dcs) != IS_STRING) {
        smart_str_free(&whitelist_dcs);
        throw_invalid_argument(dcs, "dcs", "a string" TSRMLS_CC);
        PHP5TO7_MAYBE_EFREE(args);
        return;
      }

      if (i > 0) {
        smart_str_appendl(&whitelist_dcs, ",", 1);
      }

      smart_str_appendl(&whitelist_dcs, Z_STRVAL_P(dcs), Z_STRLEN_P(dcs));
    }

    PHP5TO7_MAYBE_EFREE(args);
    smart_str_0(&whitelist_dcs);

    builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

    efree(builder->whitelist_dcs);
  #if PHP_MAJOR_VERSION >= 7
    builder->whitelist_dcs = estrndup(whitelist_dcs.s->val, whitelist_dcs.s->len);
    smart_str_free(&whitelist_dcs);
  #else
    builder->whitelist_dcs = whitelist_dcs.c;
  #endif

    RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withTokenAwareRouting)
{
  zend_bool enabled = 1;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|b", &enabled) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  builder->use_token_aware_routing = enabled;

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withCredentials)
{
  zval *username = NULL;
  zval *password = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "zz", &username, &password) == FAILURE) {
    return;
  }

  if (Z_TYPE_P(username) != IS_STRING) {
    INVALID_ARGUMENT(username, "a string");
  }

  if (Z_TYPE_P(password) != IS_STRING) {
    INVALID_ARGUMENT(password, "a string");
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (builder->username) {
    efree(builder->username);
    efree(builder->password);
  }

  builder->username = estrndup(Z_STRVAL_P(username), Z_STRLEN_P(username));
  builder->password = estrndup(Z_STRVAL_P(password), Z_STRLEN_P(password));

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withConnectTimeout)
{
  zval *timeout = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &timeout) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (Z_TYPE_P(timeout) == IS_LONG &&
      Z_LVAL_P(timeout) > 0) {
    builder->connect_timeout = Z_LVAL_P(timeout) * 1000;
  } else if (Z_TYPE_P(timeout) == IS_DOUBLE &&
             Z_DVAL_P(timeout) > 0) {
    builder->connect_timeout = ceil(Z_DVAL_P(timeout) * 1000);
  } else {
    INVALID_ARGUMENT(timeout, "a positive number");
  }

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withRequestTimeout)
{
  double timeout;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "d", &timeout) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  builder->request_timeout = ceil(timeout * 1000);

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withSSL)
{
  zval *ssl_options = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "O", &ssl_options, cassandra_ssl_ce) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (!PHP5TO7_ZVAL_IS_UNDEF(builder->ssl_options))
    zval_ptr_dtor(&builder->ssl_options);

  PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(builder->ssl_options), ssl_options);

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withPersistentSessions)
{
  zend_bool enabled = 1;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|b", &enabled) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  builder->persist = enabled;

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withProtocolVersion)
{
  zval *version = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &version) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (Z_TYPE_P(version) == IS_LONG &&
      Z_LVAL_P(version) >= 1) {
    builder->protocol_version = Z_LVAL_P(version);
  } else {
    INVALID_ARGUMENT(version, "must be >= 1");
  }

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withIOThreads)
{
  zval *count = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &count) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (Z_TYPE_P(count) == IS_LONG &&
      Z_LVAL_P(count) < 129 &&
      Z_LVAL_P(count) > 0) {
    builder->io_threads = Z_LVAL_P(count);
  } else {
    INVALID_ARGUMENT(count, "a number between 1 and 128");
  }

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withConnectionsPerHost)
{
  zval *core = NULL;
  zval *max = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z|z", &core, &max) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (Z_TYPE_P(core) == IS_LONG &&
      Z_LVAL_P(core) < 129 &&
      Z_LVAL_P(core) > 0) {
    builder->core_connections_per_host = Z_LVAL_P(core);
  } else {
    INVALID_ARGUMENT(core, "a number between 1 and 128");
  }

  if (max == NULL || Z_TYPE_P(max) == IS_NULL) {
    builder->max_connections_per_host = Z_LVAL_P(core);
  } else if (Z_TYPE_P(max) == IS_LONG) {
    if (Z_LVAL_P(max) < Z_LVAL_P(core)) {
      INVALID_ARGUMENT(max, "greater than core");
    } else if (Z_LVAL_P(max) > 128) {
      INVALID_ARGUMENT(max, "less than 128");
    } else {
      builder->max_connections_per_host = Z_LVAL_P(max);
    }
  } else {
    INVALID_ARGUMENT(max, "a number between 1 and 128");
  }

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withReconnectInterval)
{
  zval *interval = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &interval) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (Z_TYPE_P(interval) == IS_LONG &&
      Z_LVAL_P(interval) > 0) {
    builder->reconnect_interval = Z_LVAL_P(interval) * 1000;
  } else if (Z_TYPE_P(interval) == IS_DOUBLE &&
             Z_DVAL_P(interval) > 0) {
    builder->reconnect_interval = ceil(Z_DVAL_P(interval) * 1000);
  } else {
    INVALID_ARGUMENT(interval, "a positive number");
  }

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withLatencyAwareRouting)
{
  zend_bool enabled = 1;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|b", &enabled) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  builder->enable_latency_aware_routing = enabled;

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withTCPNodelay)
{
  zend_bool enabled = 1;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|b", &enabled) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  builder->enable_tcp_nodelay = enabled;

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withTCPKeepalive)
{
  zval *delay = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &delay) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (Z_TYPE_P(delay) == IS_NULL) {
    builder->enable_tcp_keepalive = 0;
    builder->tcp_keepalive_delay  = 0;
  } else if (Z_TYPE_P(delay) == IS_LONG &&
             Z_LVAL_P(delay) > 0) {
    builder->enable_tcp_keepalive = 1;
    builder->tcp_keepalive_delay  = Z_LVAL_P(delay) * 1000;
  } else if (Z_TYPE_P(delay) == IS_DOUBLE &&
             Z_DVAL_P(delay) > 0) {
    builder->enable_tcp_keepalive = 1;
    builder->tcp_keepalive_delay  = ceil(Z_DVAL_P(delay) * 1000);
  } else {
    INVALID_ARGUMENT(delay, "a positive number or null");
  }

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withRetryPolicy)
{
  zval *retry_policy = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "O",
                            &retry_policy, cassandra_retry_policy_ce) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (!PHP5TO7_ZVAL_IS_UNDEF(builder->retry_policy))
    zval_ptr_dtor(&builder->retry_policy);

  PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(builder->retry_policy), retry_policy);

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withTimestampGenerator)
{
  zval *timestamp_gen = NULL;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "O",
                            &timestamp_gen, cassandra_timestamp_gen_ce) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  if (!PHP5TO7_ZVAL_IS_UNDEF(builder->timestamp_gen))
    zval_ptr_dtor(&builder->timestamp_gen);

  PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(builder->timestamp_gen), timestamp_gen);

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(ClusterBuilder, withSchemaMetadata)
{
  zend_bool enabled = 1;
  cassandra_cluster_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|b", &enabled) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_CLUSTER_BUILDER(getThis());

  builder->enable_schema = enabled;

  RETURN_ZVAL(getThis(), 1, 0);
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_consistency, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, consistency)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_page_size, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, pageSize)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_contact_points, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, host)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_port, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, port)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_dc_aware, 0, ZEND_RETURN_VALUE, 3)
  ZEND_ARG_INFO(0, localDatacenter)
  ZEND_ARG_INFO(0, hostPerRemoteDatacenter)
  ZEND_ARG_INFO(0, useRemoteDatacenterForLocalConsistencies)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_blacklist_nodes, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, hosts)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_whitelist_nodes, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, hosts)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_blacklist_dcs, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, dcs)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_whitelist_dcs, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, dcs)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_enabled, 0, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, enabled)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_credentials, 0, ZEND_RETURN_VALUE, 2)
  ZEND_ARG_INFO(0, username)
  ZEND_ARG_INFO(0, password)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_timeout, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, timeout)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_ssl, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_OBJ_INFO(0, options, Cassandra\\SSLOptions, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_version, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, version)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_count, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, count)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_connections, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, core)
  ZEND_ARG_INFO(0, max)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_interval, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, interval)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_delay, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, delay)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_retry_policy, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_OBJ_INFO(0, policy, Cassandra\\RetryPolicy, 0)
ZEND_END_ARG_INFO()

  ZEND_BEGIN_ARG_INFO_EX(arginfo_timestamp_gen, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_OBJ_INFO(0, generator, Cassandra\\TimestampGenerator, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_cluster_builder_methods[] = {
  PHP_ME(ClusterBuilder, build, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withDefaultConsistency, arginfo_consistency,
         ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withDefaultPageSize, arginfo_page_size,
         ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withDefaultTimeout, arginfo_timeout, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withContactPoints, arginfo_contact_points,
         ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withPort, arginfo_port, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withRoundRobinLoadBalancingPolicy, arginfo_none,
         ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withDatacenterAwareRoundRobinLoadBalancingPolicy,
         arginfo_dc_aware, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withBlackListHosts,
         arginfo_blacklist_nodes, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withWhiteListHosts,
          arginfo_whitelist_nodes, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withBlackListDCs,
          arginfo_blacklist_dcs, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withWhiteListDCs,
          arginfo_whitelist_dcs, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withTokenAwareRouting, arginfo_enabled,
         ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withCredentials, arginfo_credentials, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withConnectTimeout, arginfo_timeout, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withRequestTimeout, arginfo_timeout, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withSSL, arginfo_ssl, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withPersistentSessions, arginfo_enabled,
         ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withProtocolVersion, arginfo_version, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withIOThreads, arginfo_count, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withConnectionsPerHost, arginfo_connections,
         ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withReconnectInterval, arginfo_interval,
         ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withLatencyAwareRouting, arginfo_enabled,
        ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withTCPNodelay, arginfo_enabled,
        ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withTCPKeepalive, arginfo_delay,
        ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withRetryPolicy, arginfo_retry_policy, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withTimestampGenerator, arginfo_timestamp_gen, ZEND_ACC_PUBLIC)
  PHP_ME(ClusterBuilder, withSchemaMetadata, arginfo_enabled,
        ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_cluster_builder_handlers;

static HashTable*
php_cassandra_cluster_builder_gc(zval *object, php5to7_zval_gc table, int *n TSRMLS_DC)
{
  *table = NULL;
  *n = 0;
  return zend_std_get_properties(object TSRMLS_CC);
}

static HashTable*
php_cassandra_cluster_builder_properties(zval *object TSRMLS_DC)
{
  cassandra_cluster_builder *self = PHP_CASSANDRA_GET_CLUSTER_BUILDER(object);
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  php5to7_zval contactPoints;
  php5to7_zval loadBalancingPolicy;
  php5to7_zval localDatacenter;
  php5to7_zval hostPerRemoteDatacenter;
  php5to7_zval useRemoteDatacenterForLocalConsistencies;
  php5to7_zval useTokenAwareRouting;
  php5to7_zval username;
  php5to7_zval password;
  php5to7_zval connectTimeout;
  php5to7_zval requestTimeout;
  php5to7_zval sslOptions;
  php5to7_zval defaultConsistency;
  php5to7_zval defaultPageSize;
  php5to7_zval defaultTimeout;
  php5to7_zval usePersistentSessions;
  php5to7_zval protocolVersion;
  php5to7_zval ioThreads;
  php5to7_zval coreConnectionPerHost;
  php5to7_zval maxConnectionsPerHost;
  php5to7_zval reconnectInterval;
  php5to7_zval latencyAwareRouting;
  php5to7_zval tcpNodelay;
  php5to7_zval tcpKeepalive;
  php5to7_zval retryPolicy;
  php5to7_zval blacklistHosts;
  php5to7_zval whitelistHosts;
  php5to7_zval blacklistDCs;
  php5to7_zval whitelistDCs;
  php5to7_zval timestampGen;
  php5to7_zval schemaMetadata;

  PHP5TO7_ZVAL_MAYBE_MAKE(contactPoints);
  PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(contactPoints), self->contact_points);

  PHP5TO7_ZVAL_MAYBE_MAKE(loadBalancingPolicy);
  ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(loadBalancingPolicy), self->load_balancing_policy);

  PHP5TO7_ZVAL_MAYBE_MAKE(localDatacenter);
  PHP5TO7_ZVAL_MAYBE_MAKE(hostPerRemoteDatacenter);
  PHP5TO7_ZVAL_MAYBE_MAKE(useRemoteDatacenterForLocalConsistencies);
  if (self->load_balancing_policy == LOAD_BALANCING_DC_AWARE_ROUND_ROBIN) {
    PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(localDatacenter), self->local_dc);
    ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(hostPerRemoteDatacenter), self->used_hosts_per_remote_dc);
    ZVAL_BOOL(PHP5TO7_ZVAL_MAYBE_P(useRemoteDatacenterForLocalConsistencies), self->allow_remote_dcs_for_local_cl);
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(localDatacenter));
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(hostPerRemoteDatacenter));
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(useRemoteDatacenterForLocalConsistencies));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(useTokenAwareRouting);
  ZVAL_BOOL(PHP5TO7_ZVAL_MAYBE_P(useTokenAwareRouting), self->use_token_aware_routing);

  PHP5TO7_ZVAL_MAYBE_MAKE(username);
  PHP5TO7_ZVAL_MAYBE_MAKE(password);
  if (self->username) {
    PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(username), self->username);
    PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(password), self->password);
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(username));
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(password));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(connectTimeout);
  ZVAL_DOUBLE(PHP5TO7_ZVAL_MAYBE_P(connectTimeout), (double) self->connect_timeout / 1000);
  PHP5TO7_ZVAL_MAYBE_MAKE(requestTimeout);
  ZVAL_DOUBLE(PHP5TO7_ZVAL_MAYBE_P(requestTimeout), (double) self->request_timeout / 1000);

  PHP5TO7_ZVAL_MAYBE_MAKE(sslOptions);
  if (!PHP5TO7_ZVAL_IS_UNDEF(self->ssl_options)) {
    PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(sslOptions), PHP5TO7_ZVAL_MAYBE_P(self->ssl_options));
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(sslOptions));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(defaultConsistency);
  ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(defaultConsistency), self->default_consistency);
  PHP5TO7_ZVAL_MAYBE_MAKE(defaultPageSize);
  ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(defaultPageSize), self->default_page_size);
  PHP5TO7_ZVAL_MAYBE_MAKE(defaultTimeout);
  if (!PHP5TO7_ZVAL_IS_UNDEF(self->default_timeout)) {
    ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(defaultTimeout), PHP5TO7_Z_LVAL_MAYBE_P(self->default_timeout));
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(defaultTimeout));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(usePersistentSessions);
  ZVAL_BOOL(PHP5TO7_ZVAL_MAYBE_P(usePersistentSessions), self->persist);

  PHP5TO7_ZVAL_MAYBE_MAKE(protocolVersion);
  ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(protocolVersion), self->protocol_version);

  PHP5TO7_ZVAL_MAYBE_MAKE(ioThreads);
  ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(ioThreads), self->io_threads);

  PHP5TO7_ZVAL_MAYBE_MAKE(coreConnectionPerHost);
  ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(coreConnectionPerHost), self->core_connections_per_host);

  PHP5TO7_ZVAL_MAYBE_MAKE(maxConnectionsPerHost);
  ZVAL_LONG(PHP5TO7_ZVAL_MAYBE_P(maxConnectionsPerHost), self->max_connections_per_host);

  PHP5TO7_ZVAL_MAYBE_MAKE(reconnectInterval);
  ZVAL_DOUBLE(PHP5TO7_ZVAL_MAYBE_P(reconnectInterval), (double) self->reconnect_interval / 1000);

  PHP5TO7_ZVAL_MAYBE_MAKE(latencyAwareRouting);
  ZVAL_BOOL(PHP5TO7_ZVAL_MAYBE_P(latencyAwareRouting), self->enable_latency_aware_routing);

  PHP5TO7_ZVAL_MAYBE_MAKE(tcpNodelay);
  ZVAL_BOOL(PHP5TO7_ZVAL_MAYBE_P(tcpNodelay), self->enable_tcp_nodelay);

  PHP5TO7_ZVAL_MAYBE_MAKE(tcpKeepalive);
  if (self->enable_tcp_keepalive) {
    ZVAL_DOUBLE(PHP5TO7_ZVAL_MAYBE_P(tcpKeepalive), (double) self->tcp_keepalive_delay / 1000);
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(tcpKeepalive));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(retryPolicy);
  if (!PHP5TO7_ZVAL_IS_UNDEF(self->retry_policy)) {
    PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(retryPolicy), PHP5TO7_ZVAL_MAYBE_P(self->retry_policy));
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(retryPolicy));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(blacklistHosts);
  if (self->blacklist_hosts) {
    PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(blacklistHosts), self->blacklist_hosts);
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(blacklistHosts));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(whitelistHosts);
  if (self->whitelist_hosts) {
    PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(whitelistHosts), self->whitelist_hosts);
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(whitelistHosts));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(blacklistDCs);
  if (self->blacklist_dcs) {
    PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(blacklistDCs), self->blacklist_dcs);
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(blacklistDCs));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(whitelistDCs);
  if (self->whitelist_dcs) {
    PHP5TO7_ZVAL_STRING(PHP5TO7_ZVAL_MAYBE_P(whitelistDCs), self->whitelist_dcs);
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(whitelistDCs));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(timestampGen);
  if (!PHP5TO7_ZVAL_IS_UNDEF(self->timestamp_gen)) {
    PHP5TO7_ZVAL_COPY(PHP5TO7_ZVAL_MAYBE_P(timestampGen), PHP5TO7_ZVAL_MAYBE_P(self->timestamp_gen));
  } else {
    ZVAL_NULL(PHP5TO7_ZVAL_MAYBE_P(timestampGen));
  }

  PHP5TO7_ZVAL_MAYBE_MAKE(schemaMetadata);
  ZVAL_BOOL(PHP5TO7_ZVAL_MAYBE_P(schemaMetadata), self->enable_schema);

  PHP5TO7_ZEND_HASH_UPDATE(props, "contactPoints", sizeof("contactPoints"),
                           PHP5TO7_ZVAL_MAYBE_P(contactPoints), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "loadBalancingPolicy", sizeof("loadBalancingPolicy"),
                           PHP5TO7_ZVAL_MAYBE_P(loadBalancingPolicy), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "localDatacenter", sizeof("localDatacenter"),
                           PHP5TO7_ZVAL_MAYBE_P(localDatacenter), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "hostPerRemoteDatacenter", sizeof("hostPerRemoteDatacenter"),
                           PHP5TO7_ZVAL_MAYBE_P(hostPerRemoteDatacenter), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "useRemoteDatacenterForLocalConsistencies", sizeof("useRemoteDatacenterForLocalConsistencies"),
                           PHP5TO7_ZVAL_MAYBE_P(useRemoteDatacenterForLocalConsistencies), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "useTokenAwareRouting", sizeof("useTokenAwareRouting"),
                           PHP5TO7_ZVAL_MAYBE_P(useTokenAwareRouting), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "username", sizeof("username"),
                           PHP5TO7_ZVAL_MAYBE_P(username), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "password", sizeof("password"),
                           PHP5TO7_ZVAL_MAYBE_P(password), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "connectTimeout", sizeof("connectTimeout"),
                           PHP5TO7_ZVAL_MAYBE_P(connectTimeout), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "requestTimeout", sizeof("requestTimeout"),
                           PHP5TO7_ZVAL_MAYBE_P(requestTimeout), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "sslOptions", sizeof("sslOptions"),
                           PHP5TO7_ZVAL_MAYBE_P(sslOptions), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "defaultConsistency", sizeof("defaultConsistency"),
                           PHP5TO7_ZVAL_MAYBE_P(defaultConsistency), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "defaultPageSize", sizeof("defaultPageSize"),
                           PHP5TO7_ZVAL_MAYBE_P(defaultPageSize), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "defaultTimeout", sizeof("defaultTimeout"),
                           PHP5TO7_ZVAL_MAYBE_P(defaultTimeout), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "usePersistentSessions", sizeof("usePersistentSessions"),
                           PHP5TO7_ZVAL_MAYBE_P(usePersistentSessions), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "protocolVersion", sizeof("protocolVersion"),
                           PHP5TO7_ZVAL_MAYBE_P(protocolVersion), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "ioThreads", sizeof("ioThreads"),
                           PHP5TO7_ZVAL_MAYBE_P(ioThreads), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "coreConnectionPerHost", sizeof("coreConnectionPerHost"),
                           PHP5TO7_ZVAL_MAYBE_P(coreConnectionPerHost), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "maxConnectionsPerHost", sizeof("maxConnectionsPerHost"),
                           PHP5TO7_ZVAL_MAYBE_P(maxConnectionsPerHost), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "reconnectInterval", sizeof("reconnectInterval"),
                           PHP5TO7_ZVAL_MAYBE_P(reconnectInterval), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "latencyAwareRouting", sizeof("latencyAwareRouting"),
                           PHP5TO7_ZVAL_MAYBE_P(latencyAwareRouting), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "tcpNodelay", sizeof("tcpNodelay"),
                           PHP5TO7_ZVAL_MAYBE_P(tcpNodelay), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "tcpKeepalive", sizeof("tcpKeepalive"),
                           PHP5TO7_ZVAL_MAYBE_P(tcpKeepalive), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "retryPolicy", sizeof("retryPolicy"),
                           PHP5TO7_ZVAL_MAYBE_P(retryPolicy), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "timestampGenerator", sizeof("timestampGenerator"),
                           PHP5TO7_ZVAL_MAYBE_P(timestampGen), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "schemaMetadata", sizeof("schemaMetadata"),
                           PHP5TO7_ZVAL_MAYBE_P(schemaMetadata), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "blacklist_hosts", sizeof("blacklist_hosts"),
                           PHP5TO7_ZVAL_MAYBE_P(blacklistHosts), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "whitelist_hosts", sizeof("whitelist_hosts"),
                           PHP5TO7_ZVAL_MAYBE_P(whitelistHosts), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "blacklist_dcs", sizeof("blacklist_dcs"),
                           PHP5TO7_ZVAL_MAYBE_P(blacklistDCs), sizeof(zval));
  PHP5TO7_ZEND_HASH_UPDATE(props, "whitelist_dcs", sizeof("whitelist_dcs"),
                           PHP5TO7_ZVAL_MAYBE_P(whitelistDCs), sizeof(zval));

  return props;
}

static int
php_cassandra_cluster_builder_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_cluster_builder_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_cluster_builder *self =
      PHP5TO7_ZEND_OBJECT_GET(cluster_builder, object);

  efree(self->contact_points);
  self->contact_points = NULL;

  if (self->local_dc) {
    efree(self->local_dc);
    self->local_dc = NULL;
  }

  if (self->username) {
    efree(self->username);
    self->username = NULL;
  }

  if (self->password) {
    efree(self->password);
    self->password = NULL;
  }

  if (self->whitelist_hosts) {
    efree(self->whitelist_hosts);
    self->whitelist_hosts = NULL;
  }

  if (self->blacklist_hosts) {
    efree(self->blacklist_hosts);
    self->blacklist_hosts = NULL;
  }

  if (self->whitelist_dcs) {
    efree(self->whitelist_dcs);
    self->whitelist_dcs = NULL;
  }

  if (self->blacklist_dcs) {
    efree(self->blacklist_dcs);
    self->whitelist_dcs = NULL;
  }

  PHP5TO7_ZVAL_MAYBE_DESTROY(self->ssl_options);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->default_timeout);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->retry_policy);
  PHP5TO7_ZVAL_MAYBE_DESTROY(self->timestamp_gen);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_cluster_builder_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_cluster_builder *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(cluster_builder, ce);

  self->contact_points = estrdup("127.0.0.1");
  self->port = 9042;
  self->load_balancing_policy = LOAD_BALANCING_DEFAULT;
  self->local_dc = NULL;
  self->used_hosts_per_remote_dc = 0;
  self->allow_remote_dcs_for_local_cl = 0;
  self->use_token_aware_routing = 1;
  self->username = NULL;
  self->password = NULL;
  self->connect_timeout = 5000;
  self->request_timeout = 12000;
  self->default_consistency = PHP_CASSANDRA_DEFAULT_CONSISTENCY;
  self->default_page_size = 5000;
  self->persist = 1;
  self->protocol_version = 4;
  self->io_threads = 1;
  self->core_connections_per_host = 1;
  self->max_connections_per_host = 2;
  self->reconnect_interval = 2000;
  self->enable_latency_aware_routing = 1;
  self->enable_tcp_nodelay = 1;
  self->enable_tcp_keepalive = 0;
  self->tcp_keepalive_delay = 0;
  self->enable_schema = 1;
  self->blacklist_hosts = NULL;
  self->whitelist_hosts = NULL;
  self->blacklist_dcs = NULL;
  self->whitelist_dcs = NULL;

  PHP5TO7_ZVAL_UNDEF(self->ssl_options);
  PHP5TO7_ZVAL_UNDEF(self->default_timeout);
  PHP5TO7_ZVAL_UNDEF(self->retry_policy);
  PHP5TO7_ZVAL_UNDEF(self->timestamp_gen);

  PHP5TO7_ZEND_OBJECT_INIT(cluster_builder, self, ce);
}

void cassandra_define_ClusterBuilder(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\Cluster\\Builder", cassandra_cluster_builder_methods);
  cassandra_cluster_builder_ce = zend_register_internal_class(&ce TSRMLS_CC);
  cassandra_cluster_builder_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_cluster_builder_ce->create_object = php_cassandra_cluster_builder_new;

  memcpy(&cassandra_cluster_builder_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_cluster_builder_handlers.get_properties  = php_cassandra_cluster_builder_properties;
#if PHP_VERSION_ID >= 50400
  cassandra_cluster_builder_handlers.get_gc          = php_cassandra_cluster_builder_gc;
#endif
  cassandra_cluster_builder_handlers.compare_objects = php_cassandra_cluster_builder_compare;
}
