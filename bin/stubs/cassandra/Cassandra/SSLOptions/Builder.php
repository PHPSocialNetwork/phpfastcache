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

namespace Cassandra\SSLOptions;

/**
 * SSLOptions builder allows fluent configuration of ssl options.
 *
 * @see \Cassandra::ssl()
 * @see \Cassandra\Cluster\Builder::withSSL()
 */
final class Builder
{
    /**
     * Adds a trusted certificate. This is used to verify node's identity.
     *
     * @throws \Cassandra\Exception\InvalidArgumentException
     *
     * @param string $path,... one or more paths to files containing a PEM formatted certificate.
     *
     * @return Builder self
     */
    public function withTrustedCerts($path) {}

    /**
     * Disable certificate verification.
     *
     * @throws \Cassandra\Exception\InvalidArgumentException
     *
     * @param int $flags
     *
     * @return Builder self
     */
    public function withVerifyFlags($flags) {}

    /**
     * Set client-side certificate chain.
     *
     * This is used to authenticate the client on the server-side. This should contain the entire Certificate
     * chain starting with the certificate itself.
     *
     * @throws \Cassandra\Exception\InvalidArgumentException
     *
     * @param string $path path to a file containing a PEM formatted certificate.
     *
     * @return Builder self
     */
    public function withClientCert($path) {}

    /**
     * Set client-side private key. This is used to authenticate the client on
     * the server-side.
     *
     * @throws \Cassandra\Exception\InvalidArgumentException
     *
     * @param string      $path       Path to the private key file
     * @param string|null $passphrase Passphrase for the private key, if any
     *
     * @return Builder self
     */
    public function withPrivateKey($path, $passphrase = null) {}

    /**
     * Builds SSL options.
     *
     * @return \Cassandra\SSLOptions ssl options configured accordingly.
     */
    public function build() {}
}
