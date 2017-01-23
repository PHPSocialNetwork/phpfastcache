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

use Symfony\Component\Process\Process;
use Cassandra\SimpleStatement;

class CCM
{
    const DEFAULT_CLUSTER_PREFIX = "php-driver";
    const DEFAULT_CASSANDRA_VERSION = "3.0.7";
    const PROCESS_TIMEOUT_IN_SECONDS = 480;
    private $clusterPrefix;
    private $isSilent;
    private $version;
    private $process;
    private $cluster;
    private $session;
    private $ssl;
    private $clientAuth;
    private $dataCenterOneNodes;
    private $dataCenterTwoNodes;

    public function __construct($version = self::DEFAULT_CASSANDRA_VERSION, $isSilent = false, $clusterPrefix = self::DEFAULT_CLUSTER_PREFIX)
    {
        $this->version            = $version;
        $this->isSilent           = $isSilent;
        $this->clusterPrefix      = $clusterPrefix;
        $this->process            = new Process(null);
        $this->cluster            = null;
        $this->session            = null;
        $this->ssl                = false;
        $this->clientAuth         = false;
        $this->dataCenterOneNodes = 0;
        $this->dataCenterTwoNodes = 0;

        // Increase the timeout to handle TravisCI timeouts
        $this->process->setTimeout(self::PROCESS_TIMEOUT_IN_SECONDS);
    }

    public function setupSchema($schema, $dropExistingKeyspaces = true)
    {
        if ($dropExistingKeyspaces) {
            if (version_compare($this->version, "3.0.0", ">=")) {
                $system_keyspaces = "system_schema.keyspaces";
            } else {
                $system_keyspaces = "system.schema_keyspaces";
            }

            $keyspaces = $this->session->execute(new SimpleStatement("SELECT keyspace_name FROM $system_keyspaces"));

            foreach ($keyspaces as $row) {
                $keyspace = $row['keyspace_name'];

                if ($this->startsWith("system", $keyspace)) {
                    continue;
                }

                if (!$this->isSilent) {
                    echo "DROP KEYSPACE " . $keyspace . "\n";
                }
                $this->session->execute(new SimpleStatement("DROP KEYSPACE $keyspace"));
            }
        }

        foreach (explode(";\n", $schema) as $cql) {
            $cql = trim($cql);

            if (empty($cql)) {
                continue;
            }

            if (!$this->isSilent) {
                echo $cql . "\n";
            }
            $this->session->execute(new SimpleStatement($cql));
        }
    }

    public function start()
    {
        $this->run('start', '--wait-other-notice', '--wait-for-binary-proto');
        $builder = Cassandra::cluster()
                       ->withPersistentSessions(false)
                       ->withContactPoints('127.0.0.1');

        if ($this->ssl || $this->clientAuth) {
            $sslOptions = Cassandra::ssl()
                              ->withTrustedCerts(realpath(__DIR__ . '/ssl/cassandra.pem'))
                              ->withVerifyFlags(Cassandra::VERIFY_PEER_CERT)
                              ->withClientCert(realpath(__DIR__ . '/ssl/driver.pem'))
                              ->withPrivateKey(realpath(__DIR__ . '/ssl/driver.key'), 'php-driver')
                              ->build();
            $builder->withSSL($sslOptions);
        }

        for ($retries = 1; $retries <= 10; $retries++) {
            try {
                $this->cluster = $builder->build();
                $this->session = $this->cluster->connect();
                break;
            } catch (Cassandra\Exception\RuntimeException $e) {
                unset($this->session);
                unset($this->cluster);
                sleep($retries * 0.4);
            }
        }

        if (!isset($this->session)) {
            throw new RuntimeException("Unable to initialize a Session, check cassandra logs");
        }
    }

    public function stop()
    {
        unset($this->session);
        unset($this->cluster);
        $this->run('stop');
    }

    private function getClusters()
    {
        $active = '';
        $clusters = array();
        foreach (explode(PHP_EOL, $this->run('list')) as $cluster) {
            $clusterName = trim(substr($cluster, 2, strlen($cluster) - 2));

            // Determine if this cluster is the active cluster
            if ($this->isActive($cluster)) {
                $active = $clusterName;
            }

            // Add the cluster to the list
            if (!empty($clusterName)) {
                $clusters[] = $clusterName;
            }
        }

        return array('active' => $active, 'list' => $clusters);
    }

    private function internalSetup($dataCenterOneNodes, $dataCenterTwoNodes)
    {
        $this->dataCenterOneNodes = $dataCenterOneNodes;
        $this->dataCenterTwoNodes = $dataCenterTwoNodes;

        $clusters = $this->getClusters();
        $clusterName = $this->clusterPrefix.'_'.$this->version.'_'.$dataCenterOneNodes.'-'.$dataCenterTwoNodes;

        if ($this->ssl) {
            $clusterName .= "_ssl";
        }

        if ($this->clientAuth) {
            $clusterName .= "_client_auth";
        }

        if ($clusters['active'] != $clusterName) {
            // Ensure any active cluster is stopped
            if (!empty($clusters['active'])) {
                $this->stop();
            }

            // Determine if a cluster should be created or re-used
            if (in_array($clusterName, $clusters['list'])) {
                $this->run('switch', $clusterName);
            } else {
                $this->run('create', '-v', 'binary:' . $this->version, '-b', $clusterName);

                $params = array(
                  'updateconf', '--rt', '1000', 'read_request_timeout_in_ms: 1000',
                  'write_request_timeout_in_ms: 1000', 'request_timeout_in_ms: 1000',
                  'phi_convict_threshold: 16', 'hinted_handoff_enabled: false',
                  'dynamic_snitch_update_interval_in_ms: 1000',
                );

                if (substr($this->version, 0, 4) == '1.2.') {
                    $params[] = 'reduce_cache_sizes_at: 0';
                    $params[] = 'reduce_cache_capacity_to: 0';
                    $params[] = 'flush_largest_memtables_at: 0';
                    $params[] = 'index_interval: 512';
                } else {
                    $params[] = 'cas_contention_timeout_in_ms: 10000';
                    $params[] = 'file_cache_size_in_mb: 0';
                }

                $params[] = 'native_transport_max_threads: 1';
                $params[] = 'rpc_min_threads: 1';
                $params[] = 'rpc_max_threads: 1';
                $params[] = 'concurrent_reads: 2';
                $params[] = 'concurrent_writes: 2';
                $params[] = 'concurrent_compactors: 1';
                $params[] = 'compaction_throughput_mb_per_sec: 0';

                if (strcmp($this->version, '2.1') < 0) {
                    $params[] = 'in_memory_compaction_limit_in_mb: 1';
                }

                if (version_compare($this->version, "2.2.0", ">=")) {
                    $this->run('updateconf', 'enable_user_defined_functions: true');
                }

                if (version_compare($this->version, "3.0.0", ">=")) {
                    $this->run('updateconf', 'enable_scripted_user_defined_functions: true');
                }


                $params[] = 'key_cache_size_in_mb: 0';
                $params[] = 'key_cache_save_period: 0';
                $params[] = 'memtable_flush_writers: 1';
                $params[] = 'max_hints_delivery_threads: 1';

                call_user_func_array(array($this, 'run'), $params);
                $this->run('populate', '-n', $dataCenterOneNodes.':'.$dataCenterTwoNodes, '-i', '127.0.0.');
            }
        }
    }

    public function setup($dataCenterOneNodes, $dataCenterTwoNodes) {
        $this->ssl = false;
        $this->clientAuth = false;
        $this->internalSetup($dataCenterOneNodes, $dataCenterTwoNodes);
    }

    public function setupSSL()
    {
        if (!$this->ssl) {
            $this->ssl = true;
            $this->internalSetup(1, 0);
            $this->stop();
            $this->run('updateconf',
                'client_encryption_options.enabled: true',
                'client_encryption_options.keystore: ' . realpath(__DIR__ . '/ssl/.keystore'),
                'client_encryption_options.keystore_password: php-driver'
            );
        }
    }

    public function setupClientVerification()
    {
        if (!$this->clientAuth) {
            $this->clientAuth = true;
            $this->internalSetup(1, 0);
            $this->stop();
            $this->run('updateconf',
                'client_encryption_options.enabled: true',
                'client_encryption_options.keystore: ' . realpath(__DIR__ . '/ssl/.keystore'),
                'client_encryption_options.keystore_password: php-driver',
                'client_encryption_options.require_client_auth: true',
                'client_encryption_options.truststore: ' . realpath(__DIR__ . '/ssl/.truststore'),
                'client_encryption_options.truststore_password: php-driver'
            );
        }
    }

    public function setupUserDefinedFunctions()
    {
        $this->ssl = false;
        $this->clientAuth = false;
        $this->internalSetup(1, 0);
        if (version_compare($this->version, "2.2.0", ">=")) {
            $this->run('updateconf', 'enable_user_defined_functions: true');
        }
        if (version_compare($this->version, "3.0.0", ">=")) {
            $this->run('updateconf', 'enable_scripted_user_defined_functions: true');
        }
    }

    public function enableTracing($isEnabled)
    {
        $nodes = $this->dataCenterOneNodes + $this->dataCenterTwoNodes;
        for ($node = 1; $node <= $nodes; ++$node) {
            $this->run('node'.$node, 'nodetool', 'settraceprobability', ((bool) $isEnabled) ? 1 : 0);
        }
    }

    public function pauseNode($nodes)
    {
        foreach (array($nodes) as $node) {
            $this->run('node'.$node, 'pause');
        }
    }

    public function resumeNode($nodes)
    {
        foreach (array($nodes) as $node) {
            $this->run('node'.$node, 'resume');
        }
    }

    public function startNode($nodes)
    {
        foreach (array($nodes) as $node) {
            $this->run('node'.$node, 'start');
        }
        sleep(5); //TODO: Mechanism required to ensure node is up
    }

    public function stopNode($nodes)
    {
        foreach (array($nodes) as $node) {
            $this->run('node'.$node, 'stop');
        }
        sleep(5); //TODO: Mechanism required to ensure node is down
    }

    private function isActive($clusterName)
    {
        return $this->startsWith(' *', $clusterName);
    }

    private function startsWith($prefix, $string)
    {
        return substr($string, 0, strlen($prefix)) === $prefix;
    }

    private function run()
    {
        $args = func_get_args();
        foreach ($args as $i => $arg) {
            $args[$i] = escapeshellarg($arg);
        }

        $command = sprintf('ccm %s', implode(' ', $args));
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || strtoupper(substr(PHP_OS, 0, 6)) === 'CYGWIN') {
            $keepWindowsContext = '';
            if ($args[0] != "\"start\"") {
                $keepWindowsContext = '/B ';
            }
            $command = 'START "PHP Driver - CCM" ' . $keepWindowsContext . '/MIN /WAIT ' . $command;
        }
        $this->process->setCommandLine($command);

        if (!$this->isSilent) {
            echo 'ccm > ' . $command . "\n";
        }
        $this->process->mustRun(function ($type, $buffer) {
            if (!$this->isSilent) {
                echo 'ccm > ' . $buffer;
            }
        });

        return $this->process->getOutput();
    }

    public function removeCluster($cluster)
    {
      return $this->run('remove', $cluster);
    }

    public function removeAllClusters($is_all = false)
    {
        $clusters = $this->getClusters();
        foreach ($clusters['list'] as $cluster) {
            // Determine if the cluster should be deleted
            if (!$is_all && substr(strtolower($cluster), 0, strlen($this->clusterPrefix)) != $this->clusterPrefix) {
                continue;
            }
            $this->removeCluster($cluster);
        }
    }
}
