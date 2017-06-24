<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace phpFastCache\Drivers\Couchdb;

use Doctrine\CouchDB\CouchDBClient as CouchdbClient;
use Doctrine\CouchDB\CouchDBException;
use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheLogicException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property CouchdbClient $instance Instance of driver service
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait;

    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct(array $config = [])
    {
        $this->setup($config);

        if (!$this->driverCheck()) {
            throw new phpFastCacheDriverCheckException(sprintf(self::DRIVER_CHECK_FAILURE, $this->getDriverName()));
        } else {
            $this->driverConnect();
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        return class_exists('Doctrine\CouchDB\CouchDBClient');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheDriverException
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $this->instance->putDocument(['data' => $this->encode($this->driverPreWrap($item))], $item->getEncodedKey(),
                  $this->getLatestDocumentRevision($item->getEncodedKey()));
            } catch (CouchDBException $e) {
                throw new phpFastCacheDriverException('Got error while trying to upsert a document: ' . $e->getMessage(), null, $e);
            }
            return true;
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     * @throws phpFastCacheDriverException
     */
    protected function driverRead(CacheItemInterface $item)
    {
        try {
            $response = $this->instance->findDocument($item->getEncodedKey());
        } catch (CouchDBException $e) {
            throw new phpFastCacheDriverException('Got error while trying to get a document: ' . $e->getMessage(), null, $e);
        }

        if ($response->status === 404 || empty($response->body[ 'data' ])) {
            return null;
        } else if ($response->status === 200) {
            return $this->decode($response->body[ 'data' ]);
        } else {
            throw new phpFastCacheDriverException('Got unexpected HTTP status: ' . $response->status);
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheDriverException
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $this->instance->deleteDocument($item->getEncodedKey(), $this->getLatestDocumentRevision($item->getEncodedKey()));
            } catch (CouchDBException $e) {
                throw new phpFastCacheDriverException('Got error while trying to delete a document: ' . $e->getMessage(), null, $e);
            }
            return true;
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     * @throws phpFastCacheDriverException
     */
    protected function driverClear()
    {
        try {
            $this->instance->deleteDatabase($this->getDatabaseName());
            $this->createDatabase();
        } catch (CouchDBException $e) {
            throw new phpFastCacheDriverException('Got error while trying to delete and recreate the database: ' . $e->getMessage(), null, $e);
        }


        return true;
    }

    /**
     * @return bool
     * @throws phpFastCacheLogicException
     */
    protected function driverConnect()
    {
        if ($this->instance instanceof CouchdbClient) {
            throw new phpFastCacheLogicException('Already connected to Couchdb server');
        } else {
            $host = isset($this->config[ 'host' ]) ? $this->config[ 'host' ] : '127.0.0.1';
            $ssl = isset($this->config[ 'ssl' ]) ? $this->config[ 'ssl' ] : false;
            $port = isset($this->config[ 'port' ]) ? $this->config[ 'port' ] : 5984;
            $path = isset($this->config[ 'path' ]) ? $this->config[ 'path' ] : '/';
            $username = isset($this->config[ 'username' ]) ? $this->config[ 'username' ] : '';
            $password = isset($this->config[ 'password' ]) ? $this->config[ 'password' ] : '';
            $timeout = isset($this->config[ 'timeout' ]) ? $this->config[ 'timeout' ] : 10;

            $url = ($ssl ? 'https://' : 'http://');
            if ($username) {
                $url .= "{$username}";
                if ($password) {
                    $url .= ":{$password}";
                }
                $url .= '@';
            }
            $url .= $host;
            $url .= ":{$port}";
            $url .= $path;

            $this->instance = CouchDBClient::create([
              'dbname' => $this->getDatabaseName(),
              'url' => $url,
              'timeout' => $timeout,
            ]);

            $this->createDatabase();
        }

        return true;
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
        if (!empty($response->headers[ 'etag' ])) {
            return trim($response->headers[ 'etag' ], " '\"\t\n\r\0\x0B");
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getDatabaseName()
    {
        return isset($this->config[ 'database' ]) ? $this->config[ 'database' ] : 'phpfastcache';
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

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return string
     */
    public function getHelp()
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
    public function getStats()
    {
        $info = $this->instance->getDatabaseInfo();

        return (new DriverStatistic())
          ->setSize($info[ 'sizes' ][ 'active' ])
          ->setRawData($info)
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setInfo('Couchdb version ' . $this->instance->getVersion() . "\n For more information see RawData.");
    }
}