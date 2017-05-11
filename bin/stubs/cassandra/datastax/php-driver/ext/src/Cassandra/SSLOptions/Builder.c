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
#include <ext/standard/php_filestat.h>

zend_class_entry *cassandra_ssl_builder_ce = NULL;

static int
file_get_contents(char *path, char **output, int *len TSRMLS_DC)
{
#if PHP_MAJOR_VERSION >= 7
  zend_string *str;
  php_stream *stream = php_stream_open_wrapper(path, "rb",
                         USE_PATH|REPORT_ERRORS, NULL);
#else
  php_stream *stream = php_stream_open_wrapper(path, "rb",
                         USE_PATH|REPORT_ERRORS|ENFORCE_SAFE_MODE, NULL);
#endif

  if (!stream) {
    zend_throw_exception_ex(cassandra_runtime_exception_ce, 0 TSRMLS_CC,
      "The path '%s' doesn't exist or is not readable", path);
    return 0;
  }

#if PHP_MAJOR_VERSION >= 7
  str = php_stream_copy_to_mem(stream, PHP_STREAM_COPY_ALL, 0);
  if (str) {
    *output = estrndup(str->val, str->len);
    *len = str->len;
    zend_string_release(str);
  } else {
    php_stream_close(stream);
    return 0;
  }
#else
  *len = php_stream_copy_to_mem(stream, output, PHP_STREAM_COPY_ALL, 0);
#endif

  php_stream_close(stream);
  return 1;
}

PHP_METHOD(SSLOptionsBuilder, build)
{
  cassandra_ssl *ssl = NULL;
  int   len;
  char *contents;
  CassError rc;

  cassandra_ssl_builder *builder = PHP_CASSANDRA_GET_SSL_BUILDER(getThis());

  object_init_ex(return_value, cassandra_ssl_ce);
  ssl = PHP_CASSANDRA_GET_SSL(return_value);

  cass_ssl_set_verify_flags(ssl->ssl, builder->flags);

  if (builder->trusted_certs) {
    int   i;
    char *path;

    for (i = 0; i < builder->trusted_certs_cnt; i++) {
      path = builder->trusted_certs[i];

      if (!file_get_contents(path, &contents, &len TSRMLS_CC))
        return;

      rc = cass_ssl_add_trusted_cert_n(ssl->ssl, contents, len);
      efree(contents);
      ASSERT_SUCCESS(rc);
    }
  }

  if (builder->client_cert) {
    if (!file_get_contents(builder->client_cert, &contents, &len TSRMLS_CC))
      return;

    rc = cass_ssl_set_cert_n(ssl->ssl, contents, len);
    efree(contents);
    ASSERT_SUCCESS(rc);
  }

  if (builder->private_key) {
    if (!file_get_contents(builder->private_key, &contents, &len TSRMLS_CC))
      return;

    rc = cass_ssl_set_private_key(ssl->ssl, contents, builder->passphrase);
    efree(contents);
    ASSERT_SUCCESS(rc);
  }
}

PHP_METHOD(SSLOptionsBuilder, withTrustedCerts)
{
  zval readable;
  php5to7_zval_args args = NULL;
  int argc = 0, i;
  cassandra_ssl_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "+", &args, &argc) == FAILURE) {
    return;
  }

  for (i = 0; i < argc; i++) {
    zval *path = PHP5TO7_ZVAL_ARG(args[i]);

    if (Z_TYPE_P(path) != IS_STRING) {
      throw_invalid_argument(path, "path", "a path to a trusted cert file" TSRMLS_CC);
      PHP5TO7_MAYBE_EFREE(args);
    }

    php_stat(Z_STRVAL_P(path), Z_STRLEN_P(path), FS_IS_R, &readable TSRMLS_CC);

    if (PHP5TO7_ZVAL_IS_FALSE_P(&readable)) {
      zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
        "The path '%s' doesn't exist or is not readable", Z_STRVAL_P(path));
      PHP5TO7_MAYBE_EFREE(args);
      return;
    }
  }

  builder = PHP_CASSANDRA_GET_SSL_BUILDER(getThis());

  if (builder->trusted_certs) {
    for (i = 0; i < builder->trusted_certs_cnt; i++) {
      efree(builder->trusted_certs[i]);
    }

    efree(builder->trusted_certs);
  }

  builder->trusted_certs_cnt = argc;
  builder->trusted_certs     = ecalloc(argc, sizeof(char *));

  for (i = 0; i < argc; i++) {
    zval *path = PHP5TO7_ZVAL_ARG(args[i]);

    builder->trusted_certs[i] = estrndup(Z_STRVAL_P(path), Z_STRLEN_P(path));
  }

  PHP5TO7_MAYBE_EFREE(args);
  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(SSLOptionsBuilder, withVerifyFlags)
{
  long flags;
  cassandra_ssl_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &flags) == FAILURE) {
    return;
  }

  builder = PHP_CASSANDRA_GET_SSL_BUILDER(getThis());

  builder->flags = (int) flags;

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(SSLOptionsBuilder, withClientCert)
{
  char *client_cert;
  php5to7_size client_cert_len;
  zval readable;
  cassandra_ssl_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &client_cert, &client_cert_len) == FAILURE) {
    return;
  }

  php_stat(client_cert, client_cert_len, FS_IS_R, &readable TSRMLS_CC);

  if (PHP5TO7_ZVAL_IS_FALSE_P(&readable)) {
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
      "The path '%s' doesn't exist or is not readable", client_cert);
    return;
  }

  builder = PHP_CASSANDRA_GET_SSL_BUILDER(getThis());

  if (builder->client_cert)
    efree(builder->client_cert);

  builder->client_cert = estrndup(client_cert, client_cert_len);

  RETURN_ZVAL(getThis(), 1, 0);
}

PHP_METHOD(SSLOptionsBuilder, withPrivateKey)
{
  char *private_key;
  char *passphrase = NULL;
  php5to7_size private_key_len, passphrase_len;
  zval readable;
  cassandra_ssl_builder *builder = NULL;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|s", &private_key, &private_key_len, &passphrase, &passphrase_len) == FAILURE) {
    return;
  }

  php_stat(private_key, private_key_len, FS_IS_R, &readable TSRMLS_CC);

  if (PHP5TO7_ZVAL_IS_FALSE_P(&readable)) {
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
      "The path '%s' doesn't exist or is not readable", private_key);
    return;
  }

  builder = PHP_CASSANDRA_GET_SSL_BUILDER(getThis());

  if (builder->private_key)
    efree(builder->private_key);

  builder->private_key = estrndup(private_key, private_key_len);

  if (builder->passphrase) {
    efree(builder->passphrase);
    builder->passphrase = NULL;
  }

  if (passphrase)
    builder->passphrase = estrndup(passphrase, passphrase_len);

  RETURN_ZVAL(getThis(), 1, 0);
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_none, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_path, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, path)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_flags, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, flags)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_key, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, path)
  ZEND_ARG_INFO(0, passphrase)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_ssl_builder_methods[] = {
  PHP_ME(SSLOptionsBuilder, build, arginfo_none, ZEND_ACC_PUBLIC)
  PHP_ME(SSLOptionsBuilder, withTrustedCerts, arginfo_path,
    ZEND_ACC_PUBLIC)
  PHP_ME(SSLOptionsBuilder, withVerifyFlags, arginfo_flags,
    ZEND_ACC_PUBLIC)
  PHP_ME(SSLOptionsBuilder, withClientCert, arginfo_path,
    ZEND_ACC_PUBLIC)
  PHP_ME(SSLOptionsBuilder, withPrivateKey, arginfo_key,
    ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_ssl_builder_handlers;

static HashTable *
php_cassandra_ssl_builder_properties(zval *object TSRMLS_DC)
{
  HashTable *props = zend_std_get_properties(object TSRMLS_CC);

  return props;
}

static int
php_cassandra_ssl_builder_compare(zval *obj1, zval *obj2 TSRMLS_DC)
{
  if (Z_OBJCE_P(obj1) != Z_OBJCE_P(obj2))
    return 1; /* different classes */

  return Z_OBJ_HANDLE_P(obj1) != Z_OBJ_HANDLE_P(obj1);
}

static void
php_cassandra_ssl_builder_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_ssl_builder *self = PHP5TO7_ZEND_OBJECT_GET(ssl_builder, object);

  if (self->trusted_certs) {
    int i;

    for (i = 0; i < self->trusted_certs_cnt; i++)
      efree(self->trusted_certs[i]);

    efree(self->trusted_certs);
  }

  if (self->client_cert)
    efree(self->client_cert);

  if (self->private_key)
    efree(self->private_key);

  if (self->passphrase)
    efree(self->passphrase);

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_ssl_builder_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_ssl_builder *self =
      PHP5TO7_ZEND_OBJECT_ECALLOC(ssl_builder, ce);

  self->flags             = 0;
  self->trusted_certs     = NULL;
  self->trusted_certs_cnt = 0;
  self->client_cert       = NULL;
  self->private_key       = NULL;
  self->passphrase        = NULL;

  PHP5TO7_ZEND_OBJECT_INIT(ssl_builder, self, ce);
}

void cassandra_define_SSLOptionsBuilder(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\SSLOptions\\Builder", cassandra_ssl_builder_methods);
  cassandra_ssl_builder_ce = zend_register_internal_class(&ce TSRMLS_CC);
  cassandra_ssl_builder_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_ssl_builder_ce->create_object = php_cassandra_ssl_builder_new;

  memcpy(&cassandra_ssl_builder_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
  cassandra_ssl_builder_handlers.get_properties  = php_cassandra_ssl_builder_properties;
  cassandra_ssl_builder_handlers.compare_objects = php_cassandra_ssl_builder_compare;
}
