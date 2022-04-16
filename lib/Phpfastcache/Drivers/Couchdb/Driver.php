<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

declare(strict_types=1);

namespace Phpfastcache\Drivers\Couchdb;

use Doctrine\CouchDB\CouchDBClient;
use Doctrine\CouchDB\CouchDBException;
use Doctrine\CouchDB\HTTP\HTTPException;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * Class Driver
 * @property CouchdbClient $instance Instance of driver service
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    public const COUCHDB_DEFAULT_DB_NAME = 'phpfastcache'; // Public because used in config

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
     * @throws HTTPException
     */
    public function getStats(): DriverStatistic
    {
        $info = $this->instance->getDatabaseInfo();

        return (new DriverStatistic())
            ->setSize($info['sizes']['active'] ?? 0)
            ->setRawData($info)
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo('Couchdb version ' . $this->instance->getVersion() . "\n For more information see RawData.");
    }

    /**
     * @return bool
     * @throws HTTPException
     */
    protected function driverConnect(): bool
    {
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
        $url .= '/' . \urlencode($this->getDatabaseName());

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
        return $this->getConfig()->getDatabase() ?: static::COUCHDB_DEFAULT_DB_NAME;
    }

    /**
     * @return void
     * @throws HTTPException
     */
    protected function createDatabase(): void
    {
        try {
            $this->instance->getDatabaseInfo($this->getDatabaseName());
        } catch (HTTPException) {
            $this->instance->createDatabase($this->getDatabaseName());
        }
    }

    protected function getCouchDbItemKey(ExtendedCacheItemInterface $item): string
    {
        return 'pfc_' . $item->getEncodedKey();
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     * @throws PhpfastcacheDriverException
     * @throws \Exception
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        try {
            $response = $this->instance->findDocument($this->getCouchDbItemKey($item));
        } catch (CouchDBException $e) {
            throw new PhpfastcacheDriverException('Got error while trying to get a document: ' . $e->getMessage(), 0, $e);
        }

        if ($response->status === 404 || empty($response->body[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX])) {
            return null;
        }

        if ($response->status === 200) {
            return $this->decodeDocument($response->body);
        }

        throw new PhpfastcacheDriverException('Got unexpected HTTP status: ' . $response->status);
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        try {
            $this->instance->putDocument(
                $this->encodeDocument($this->driverPreWrap($item)),
                $this->getCouchDbItemKey($item),
                $this->getLatestDocumentRevision($this->getCouchDbItemKey($item))
            );
        } catch (CouchDBException $e) {
            throw new PhpfastcacheDriverException('Got error while trying to upsert a document: ' . $e->getMessage(), 0, $e);
        }
        return true;
    }

    /**
     * @param string $docId
     * @return string|null
     */
    protected function getLatestDocumentRevision(string $docId): ?string
    {
        $path = '/' . \urlencode($this->getDatabaseName()) . '/' . urlencode($docId);

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

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        try {
            $this->instance->deleteDocument($this->getCouchDbItemKey($item), $this->getLatestDocumentRevision($this->getCouchDbItemKey($item)));
        } catch (CouchDBException $e) {
            throw new PhpfastcacheDriverException('Got error while trying to delete a document: ' . $e->getMessage(), 0, $e);
        }
        return true;
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function encodeDocument(array $data): array
    {
        $data[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX] = $this->encode($data[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX]);

        return $data;
    }

    /**
     * Specific document decoder for Couchdb
     * since we don't store encoded version
     * for performance purposes
     *
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     * @throws \Exception
     */
    protected function decodeDocument(array $value): array
    {
        $value[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX] = \unserialize(
            $value[ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX],
            ['allowed_classes' => true]
        );

        $value[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX] = new \DateTime(
            $value[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX]['date'],
            new \DateTimeZone($value[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX]['timezone'])
        );

        if (isset($value[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX])) {
            $value[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] = new \DateTime(
                $value[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX]['date'],
                new \DateTimeZone($value[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX]['timezone'])
            );
        }

        if (isset($value[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX])) {
            $value[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] = new \DateTime(
                $value[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX]['date'],
                new \DateTimeZone($value[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX]['timezone'])
            );
        }

        return $value;
    }
}
