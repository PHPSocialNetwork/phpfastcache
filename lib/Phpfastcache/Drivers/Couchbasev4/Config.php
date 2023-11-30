<?php

declare(strict_types=1);

namespace Phpfastcache\Drivers\Couchbasev4;

use Couchbase\ClusterOptions;
use Couchbase\ThresholdLoggingOptions;
use Couchbase\TransactionsConfiguration;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

class Config extends ConfigurationOption
{
    protected const DEFAULT_VALUE = '_default';
    protected const DEFAULT_HOST = '127.0.0.1';

    protected string $username = '';
    protected string $password = '';
    protected string $bucketName = self::DEFAULT_VALUE;
    protected string $scopeName = self::DEFAULT_VALUE;
    protected string $collectionName = self::DEFAULT_VALUE;

    protected array $servers = [];

    protected bool $secure = false;

    protected ClusterOptions $clusterOptions;

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
        $this->clusterOptions = new ClusterOptions();
    }


    public function getServers(): array
    {
        return $this->servers ?: [self::DEFAULT_HOST];
    }

    /**
     * @param array $servers
     * @return $this
     * @throws PhpfastcacheLogicException
     */
    public function setServers(array $servers): Config
    {
        foreach ($servers as $server) {
            $this->addServer($server);
        }
        return $this;
    }

    /**
     * @param string $host
     * @param int|null $port
     * @return $this
     * @throws PhpfastcacheLogicException
     */
    public function addServer(string $host, ?int $port = null): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->servers[] = $host . ($port ? ':' . $port : '');
        return $this;
    }

    /**
     * @param bool $secure
     * @return $this
     * @throws PhpfastcacheLogicException
     */
    public function setSecure(bool $secure): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->secure = $secure;
        return $this;
    }

    public function getSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setUsername(string $username): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * @param string $bucketName
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setBucketName(string $bucketName): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->bucketName = $bucketName;
        return $this;
    }
    /**
     * @return string
     */
    public function getScopeName(): string
    {
        return $this->scopeName;
    }

    /**
     * @param string $scopeName
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setScopeName(string $scopeName): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->scopeName = $scopeName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setCollectionName(string $collectionName): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->collectionName = $collectionName;
        return $this;
    }

    /*************************************************************************/
    /*************************************************************************/
    /*************************************************************************/

    public function getClusterOptions(): ClusterOptions
    {
        return $this->clusterOptions;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setAnalyticsTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->analyticsTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setBootstrapTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->bootstrapTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setConnectTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->connectTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setDnsSrvTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->dnsSrvTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setKeyValueDurableTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->keyValueDurableTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setKeyValueTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->keyValueTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setManagementTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->managementTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setQueryTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->queryTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setResolveTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->resolveTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setSearchTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->searchTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setViewTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->viewTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $numberOfConnections
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setMaxHttpConnections(int $numberOfConnections): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->maxHttpConnections($numberOfConnections);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setConfigIdleRedialTimeout(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->configIdleRedialTimeout($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setConfigPollFloor(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->configPollFloor($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setConfigPollInterval(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->configPollInterval($milliseconds);
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setTcpKeepAliveInterval(int $milliseconds): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->tcpKeepAliveInterval($milliseconds);
        return $this;
    }

    /**
     * @param bool $enable
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setEnableClustermapNotification(bool $enable): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->enableClustermapNotification($enable);
        return $this;
    }

    /**
     * @param bool $enable
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setEnableCompression(bool $enable): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->enableCompression($enable);
        return $this;
    }

    /**
     * @param bool $enable
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setEnableDnsSrv(bool $enable): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->enableDnsSrv($enable);
        return $this;
    }

    /**
     * @param bool $enable
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setEnableMetrics(bool $enable): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->enableMetrics($enable);
        return $this;
    }

    /**
     * @param bool $enable
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setEnableMutationTokens(bool $enable): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->enableMutationTokens($enable);
        return $this;
    }

    /**
     * @param bool $enable
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setEnableTcpKeepAlive(bool $enable): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->enableTcpKeepAlive($enable);
        return $this;
    }

    /**
     * @param bool $enable
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setEnableTls(bool $enable): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->enableTls($enable);
        return $this;
    }

    /**
     * @param bool $enable
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setEnableTracing(bool $enable): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->enableTracing($enable);
        return $this;
    }

    /**
     * @param bool $enable
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setEnableUnorderedExecution(bool $enable): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->enableUnorderedExecution($enable);
        return $this;
    }

    /**
     * @param string $mode "any", "forceIpv4" or "forceIpv6"
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setUseIpProtocol(string $mode): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->useIpProtocol($mode);
        return $this;
    }

    /**
     * @param bool $enable
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setShowQueries(bool $enable): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->showQueries($enable);
        return $this;
    }

    /**
     * @param string $networkSelector
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setNetwork(string $networkSelector): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->network($networkSelector);
        return $this;
    }

    /**
     * @param string $certificatePath
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setTrustCertificate(string $certificatePath): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->trustCertificate($certificatePath);
        return $this;
    }

    /**
     * @param string $userAgentExtraString
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setUserAgentExtra(string $userAgentExtraString): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->userAgentExtra($userAgentExtraString);
        return $this;
    }

    /**
     * @param string $mode
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setTlsVerify(string $mode): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->tlsVerify($mode);
        return $this;
    }

    /**
     * @param ThresholdLoggingOptions $options
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setThresholdLoggingTracerOptions(ThresholdLoggingOptions $options): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->thresholdLoggingTracerOptions($options);
        return $this;
    }

    /**
     * @param TransactionsConfiguration $options
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setTransactionsConfiguration(TransactionsConfiguration $options): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->clusterOptions->transactionsConfiguration($options);
        return $this;
    }
}
