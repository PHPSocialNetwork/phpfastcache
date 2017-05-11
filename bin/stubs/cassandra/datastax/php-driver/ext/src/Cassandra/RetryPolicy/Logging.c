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
#include "util/types.h"

zend_class_entry *cassandra_retry_policy_logging_ce = NULL;

PHP_METHOD(Logging, __construct)
{
  zval *child_policy = NULL;
  cassandra_retry_policy *self, *retry_policy;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "O",
                            &child_policy, cassandra_retry_policy_ce) == FAILURE) {
    return;
  }

  if (instanceof_function(Z_OBJCE_P(child_policy),
                           cassandra_retry_policy_logging_ce TSRMLS_CC)) {
    zend_throw_exception_ex(cassandra_invalid_argument_exception_ce, 0 TSRMLS_CC,
                            "Cannot add a Cassandra\\Logging as child policy of Cassandra\\Logging");
    return;
  }

  self = PHP_CASSANDRA_GET_RETRY_POLICY(getThis());
  retry_policy = PHP_CASSANDRA_GET_RETRY_POLICY(child_policy);
  self->policy = cass_retry_policy_logging_new(retry_policy->policy);
}

ZEND_BEGIN_ARG_INFO_EX(arginfo__construct, 0, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_OBJ_INFO(0, childPolicy, Cassandra\\RetryPolicy, 0)
ZEND_END_ARG_INFO()

static zend_function_entry cassandra_retry_policy_logging_methods[] = {
  PHP_ME(Logging, __construct, arginfo__construct, ZEND_ACC_CTOR|ZEND_ACC_PUBLIC)
  PHP_FE_END
};

static zend_object_handlers cassandra_retry_policy_logging_handlers;

static void
php_cassandra_retry_policy_logging_free(php5to7_zend_object_free *object TSRMLS_DC)
{
  cassandra_retry_policy *self = PHP5TO7_ZEND_OBJECT_GET(retry_policy, object);

  if (self->policy) {
    cass_retry_policy_free(self->policy);
  }

  zend_object_std_dtor(&self->zval TSRMLS_CC);
  PHP5TO7_MAYBE_EFREE(self);
}

static php5to7_zend_object
php_cassandra_retry_policy_logging_new(zend_class_entry *ce TSRMLS_DC)
{
  cassandra_retry_policy *self = PHP5TO7_ZEND_OBJECT_ECALLOC(retry_policy, ce);

  self->policy = NULL;

  PHP5TO7_ZEND_OBJECT_INIT_EX(retry_policy, retry_policy_logging, self, ce);
}

void cassandra_define_RetryPolicyLogging(TSRMLS_D)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "Cassandra\\RetryPolicy\\Logging", cassandra_retry_policy_logging_methods);
  cassandra_retry_policy_logging_ce = zend_register_internal_class(&ce TSRMLS_CC);
  zend_class_implements(cassandra_retry_policy_logging_ce TSRMLS_CC, 1, cassandra_retry_policy_ce);
  cassandra_retry_policy_logging_ce->ce_flags     |= PHP5TO7_ZEND_ACC_FINAL;
  cassandra_retry_policy_logging_ce->create_object = php_cassandra_retry_policy_logging_new;

  memcpy(&cassandra_retry_policy_logging_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
}
