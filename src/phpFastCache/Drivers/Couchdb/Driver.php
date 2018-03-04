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
declare(strict_types=1);

namespace phpFastCache\Drivers\Couchdb;

use Doctrine\CouchDB\{
  CouchDBClient as CouchdbClient, CouchDBException
};
use phpFastCache\Config\ConfigurationOption;
use phpFastCache\Core\Pool\{
  DriverBaseTrait, ExtendedCacheItemPoolInterface
};
use phpFastCache\Exceptions\{
  phpFastCacheDriverException, phpFastCacheInvalidArgumentException, phpFastCacheLogicException
};
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Util\ArrayObject;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property CouchdbClient $instance Instance of driver service
 * @property Config $config Config object
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    const COUCHDB_DEFAULT_DB_NAME = 'phpfastcache';

    use DriverBaseTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \class_exists('Doctrine\CouchDB\CouchDBClient');
    }

    /**
     * @return bool
     * @throws phpFastCacheLogicException
     */
    protected function driverConnect(): bool
    {
        if ($this->instance instanceof CouchdbClient) {
            throw new phpFastCacheLogicException('Already connected to Couchdb server');
        } else {
            $clientConfig = $this->getConfig();

            $url = ($clientConfig[ 'ssl' ] ? 'https://' : 'http://');
            if ($clientConfig[ 'username' ]) {
                $url .= "{$clientConfig['username']}";
                if ($clientConfig[ 'password' ]) {
                    $url .= ":{$clientConfig['password']}";
                }
                $url .= '@';
            }
            $url .= $clientConfig[ 'host' ];
            $url .= ":{$clientConfig['port']}";
            $url .= $clientConfig[ 'path' ];

            $this->instance = CouchDBClient::create([
              'dbname' => $this->getDatabaseName(),
              'url' => $url,
              'timeout' => $clientConfig[ 'timeout' ],
            ]);

            $this->createDatabase();
        }

        return true;
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
    protected function driverWrite(CacheItemInterface $item): bool
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
     * @return bool
     * @throws phpFastCacheDriverException
     * @throws phpFastCacheInvalidArgumentException
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
    protected function driverClear(): bool
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
            return \trim($response->headers[ 'etag' ], " '\"\t\n\r\0\x0B");
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getDatabaseName(): string
    {
        return $this->getConfigOption('database') ?: self::COUCHDB_DEFAULT_DB_NAME;
    }

    /**
     * @return void
     */
    protected function createDatabase()
    {
        if (!\in_array($this->instance->getDatabase(), $this->instance->getAllDatabases(), true)) {
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
          ->setSize($info[ 'sizes' ][ 'active' ])
          ->setRawData($info)
          ->setData(\implode(', ', \array_keys($this->itemInstances)))
          ->setInfo('Couchdb version ' . $this->instance->getVersion() . "\n For more information see RawData.");
    }
}