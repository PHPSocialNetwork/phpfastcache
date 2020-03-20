<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Drivers\Couchdb;

use Doctrine\CouchDB\{CouchDBClient, CouchDBException};
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{PhpfastcacheDriverException, PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException};
use Psr\Cache\CacheItemInterface;


/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property CouchdbClient $instance Instance of driver service
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
{
    public const COUCHDB_DEFAULT_DB_NAME = 'phpfastcache'; // Public because used in config

    use DriverBaseTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return class_exists(CouchDBClient::class);
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
<p>
To install the Couchdb HTTP client library via Composer:
<code>composer require "doctrine/couchdb" "@dev"</code>
</p>
HELP;
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $info = $this->instance->getDatabaseInfo();

        return (new DriverStatistic())
            ->setSize($info['sizes']['active'])
            ->setRawData($info)
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo('Couchdb version ' . $this->instance->getVersion() . "\n For more information see RawData.");
    }

    /**
     * @return bool
     * @throws PhpfastcacheLogicException
     */
    protected function driverConnect(): bool
    {
        if ($this->instance instanceof CouchDBClient) {
            throw new PhpfastcacheLogicException('Already connected to Couchdb server');
        }

        $clientConfig = $this->getConfig();

        $url = ($clientConfig->isSsl() ? 'https://' : 'http://');
        if ($clientConfig->getUsername()) {
            $url .= $clientConfig->getUsername();
            if ($clientConfig->getPassword()) {
                $url .= ":{$clientConfig->getPassword()}";
            }
            $url .= '@';
        }
        $url .= $clientConfig->getHost();
        $url .= ":{$clientConfig->getPort()}";
        $url .= $clientConfig->getPath();

        $this->instance = CouchDBClient::create(
            [
                'dbname' => $this->getDatabaseName(),
                'url' => $url,
                'timeout' => $clientConfig->getTimeout(),
            ]
        );

        $this->createDatabase();

        return true;
    }

    /**
     * @return string
     */
    protected function getDatabaseName(): string
    {
        return $this->getConfig()->getDatabase() ?: self::COUCHDB_DEFAULT_DB_NAME;
    }

    /**
     * @return void
     */
    protected function createDatabase()
    {
        if (!in_array($this->instance->getDatabase(), $this->instance->getAllDatabases(), true)) {
            $this->instance->createDatabase($this->instance->getDatabase());
        }
    }

    /**
     * @param CacheItemInterface $item
     * @return null|array
     * @throws PhpfastcacheDriverException
     */
    protected function driverRead(CacheItemInterface $item)
    {
        try {
            $response = $this->instance->findDocument($item->getEncodedKey());
        } catch (CouchDBException $e) {
            throw new PhpfastcacheDriverException('Got error while trying to get a document: ' . $e->getMessage(), 0, $e);
        }

        if ($response->status === 404 || empty($response->body['data'])) {
            return null;
        }

        if ($response->status === 200) {
            return $this->decode($response->body['data']);
        }

        throw new PhpfastcacheDriverException('Got unexpected HTTP status: ' . $response->status);
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $this->instance->putDocument(
                    ['data' => $this->encode($this->driverPreWrap($item))],
                    $item->getEncodedKey(),
                    $this->getLatestDocumentRevision($item->getEncodedKey())
                );
            } catch (CouchDBException $e) {
                throw new PhpfastcacheDriverException('Got error while trying to upsert a document: ' . $e->getMessage(), 0, $e);
            }
            return true;
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @return string|null
     */
    protected function getLatestDocumentRevision($docId)
    {
        $path = '/' . $this->getDatabaseName() . '/' . urlencode($docId);

        $response = $this->instance->getHttpClient()->request(
            'HEAD',
            $path,
            null,
            false
        );
        if (!empty($response->headers['etag'])) {
            return trim($response->headers['etag'], " '\"\t\n\r\0\x0B");
        }

        return null;
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $this->instance->deleteDocument($item->getEncodedKey(), $this->getLatestDocumentRevision($item->getEncodedKey()));
            } catch (CouchDBException $e) {
                throw new PhpfastcacheDriverException('Got error while trying to delete a document: ' . $e->getMessage(), 0, $e);
            }
            return true;
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverException
     */
    protected function driverClear(): bool
    {
        try {
            $this->instance->deleteDatabase($this->getDatabaseName());
            $this->createDatabase();
        } catch (CouchDBException $e) {
            throw new PhpfastcacheDriverException('Got error while trying to delete and recreate the database: ' . $e->getMessage(), 0, $e);
        }

        return true;
    }
}
