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

#ifndef PHP_CASSANDRA_UTIL_FUTURE_H
#define PHP_CASSANDRA_UTIL_FUTURE_H

int  php_cassandra_future_wait_timed(CassFuture *future, zval *timeout TSRMLS_DC);
int  php_cassandra_future_is_error(CassFuture *future TSRMLS_DC);

#endif /* PHP_CASSANDRA_UTIL_FUTURE_H */
