<?php

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

namespace Cassandra\Cluster;

/**
 * Cluster builder allows fluent configuration of the cluster instance.
 *
 * @see \Cassandra::cluster()
 */
final class Builder
{
    /**
     * Returns a Cluster Instance.
     *
     * @return \Cassandra\Cluster Cluster instance
     */
    public function build() {}

    /**
     * Configures default consistency for all requests.
     *
     * @param int $consistency A consistency level, must be one of Cassandra::CONSISTENCY_* values
     *
     * @return Builder self
     */
    public function withDefaultConsistency($consistency) {}

    /**
     * Configures default page size for all results.
     * Set to `null` to disable paging altogether.
     *
     * @param int|null $pageSize default page size
     *
     * @return Builder self
     */
    public function withDefaultPageSize($pageSize) {}

    /**
     * Configures default timeout for future resolution in blocking operations
     * Set to null to disable (default).
     *
     * @param float|null $timeout Timeout value in seconds, can be fractional
     *
     * @return Builder self
     */
    public function withDefaultTimeout($timeout) {}

    /**
     * Configures the initial endpoints. Note that the driver will
     * automatically discover and connect to the rest of the cluster.
     *
     * @param string $host,... one or more ip addresses or hostnames
     *
     * @return Builder self
     */
    public function withContactPoints($host) {}

    /**
     * Specify a different port to be used when connecting to the cluster.
     *
     * @throws \Cassandra\Exception\InvalidArgumentException
     *
     * @param int $port a number between 1 and 65535
     *
     * @return Builder self
     */
    public function withPort($port) {}

    /**
     * Configures this cluster to use a round robin load balancing policy.
     *
     * @return Builder self
     */
    public function withRoundRobinLoadBalancingPolicy() {}

    /**
     * Configures this cluster to use a datacenter aware round robin load balancing policy.
     *
     * @param string $localDatacenter                          Name of the local datacenter
     * @param int    $hostPerRemoteDatacenter                  Maximum number of hosts to try in remote datacenters
     * @param bool   $useRemoteDatacenterForLocalConsistencies Allow using hosts from remote datacenters to execute statements with local consistencies
     *
     * @return Builder self
     */
    public function withDatacenterAwareRoundRobinLoadBalancingPolicy($localDatacenter, $hostPerRemoteDatacenter, $useRemoteDatacenterForLocalConsistencies) {}

    /**
     * Enable token aware routing.
     *
     * @param bool $enabled Whether to enable token aware routing (optional)
     *
     * @return Builder self
     */
    public function withTokenAwareRouting($enabled = true) {}

    /**
     * Configures cassandra authentication.
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return Builder self
     */
    public function withCredentials($username, $password) {}

    /**
     * Timeout used for establishing TCP connections.
     *
     * @param float $timeout Timeout value in seconds, can be fractional
     *
     * @return Builder self
     */
    public function withConnectTimeout($timeout) {}

    /**
     * Timeout used for waiting for a response from a node.
     *
     * @param float $timeout Timeout value in seconds, can be fractional
     *
     * @return Builder self
     */
    public function withRequestTimeout($timeout) {}

    /**
     * Set up ssl context.
     *
     * @param \Cassandra\SSLOptions $options a preconfigured ssl context
     *
     * @return Builder self
     */
    public function withSSL(\Cassandra\SSLOptions $options) {}

    /**
     * Enable persistent sessions and clusters.
     *
     * @param bool $enabled whether to enable persistent sessions and clusters
     *                      (optional)
     *
     * @return Builder self
     */
    public function withPersistentSessions($enabled = true) {}

    /**
     * Force the driver to use a specific binary protocol version.
     *
     * Apache Cassandra 1.2+ supports protocol version 1
     * Apache Cassandra 2.0+ supports protocol version 2
     * Apache Cassandra 2.1+ supports protocol version 3
     * Apache Cassandra 2.2+ supports protocol version 4
     *
     * NOTE: Apache Cassandra 3.x supports protocol version 3 and 4 only
     *
     * @param int $version the actual protocol version, only `1`, `2`, `3`, or
     *                     `4` are supported
     *
     * @return Builder self
     */
    public function withProtocolVersion($version) {}

    /**
     * Total number of IO threads to use for handling the requests.
     *
     * Note: number of io threads * core connections per host <= total number
     *       of connections <= number of io threads * max connections per host
     *
     * @param int $count total number of threads.
     *
     * @return Builder self
     */
    public function withIOThreads($count) {}

    /**
     * Set the size of connection pools used by the driver. Pools are fixed
     * when only `$core` is given, when a `$max` is specified as well,
     * additional connections will be created automatically based on current
     * load until the maximum number of connection has been reached. When
     * request load goes down, extra connections are automatically cleaned up
     * until only the core number of connections is left.
     *
     * @param int $core minimum connections to keep open to any given host
     * @param int $max  maximum connections to keep open to any given host
     *
     * @return Builder self
     */
    public function withConnectionsPerHost($core = 1, $max = 2) {}

    /**
     * Specify interval in seconds that the driver should wait before attempting
     * to re-establish a closed connection.
     *
     * @param float $interval interval in seconds
     *
     * @return Builder self
     */
    public function withReconnectInterval($interval) {}

    /**
     * Enables/disables latency-aware routing.
     *
     * @param bool $enabled whether to actually enable or disable the routing.
     *
     * @return Builder self
     */
    public function withLatencyAwareRouting($enabled = true) {}

    /**
     * Disables nagle algorithm for lower latency.
     *
     * @param bool $enabled whether to actually enable or disable nodelay.
     *
     * @return Builder self
     */
    public function withTCPNodelay($enabled = true) {}

    /**
     * Enables/disables TCP keepalive.
     *
     * @param float|null $delay the period of inactivity in seconds, after
     *                          which the keepalive probe should be sent over
     *                          the connection. If set to `null`, disables
     *                          keepalive probing.
     *
     * @return Builder self
     */
    public function withTCPKeepalive($delay) {}

    /**
     * Configures the retry policy.
     *
     * @param RetryPolicy $policy the retry policy to use.
     *
     * @return Builder self
     */
    public function withRetryPolicy(RetryPolicy $policy) {}

    /**
     * Sets the timestamp generator.
     *
     * @param Cassandra\TimestampGenerator $generator A timestamp generator that will be used
     *                                                to generate timestamps for statements.
     *
     * @return Builder self
     */
    public function withTimestampGenerator($generator) {}

    /**
     * Enables/disables Schema Metadata.
     *
     * If disabled this allows the driver to skip over retrieving and
     * updating schema metadata, but it also disables the usage of token-aware
     * routing and $session->schema() will always return an empty object. This
     * can be useful for reducing the startup overhead of short-lived sessions.
     *
     * @param bool $enable whether the driver fetches and maintains schema metadata.
     *
     * @return Builder self
     */
    public function withSchemaMetadata($enable = true) {}

}
