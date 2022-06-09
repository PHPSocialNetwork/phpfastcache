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

namespace Phpfastcache\Drivers\Solr;

use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Solarium\Client as SolariumClient;
use Solarium\Core\Client\Adapter\Curl as SolariumCurlAdapter;
use Solarium\Exception\ExceptionInterface as SolariumExceptionInterface;
use Solarium\QueryType\Select\Result\Document as SolariumDocument;

/**
 * Class Driver
 * @property SolariumClient $instance
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    public const MINIMUM_SOLARIUM_VERSION = '6.1.0';

    public const SOLR_DEFAULT_ID_FIELD = 'id';

    public const SOLR_DISCRIMINATOR_FIELD = 'type';

    public const SOLR_DISCRIMINATOR_VALUE = '_pfc_';

    /**
     * Copy of configuration entry for performance optimization
     * @var string[]
     */
    protected array $mappingSchema = [];

    /**
     * @return bool
     * @throws PhpfastcacheDriverCheckException
     */
    public function driverCheck(): bool
    {
        if (!\class_exists(SolariumClient::class)) {
            throw new PhpfastcacheDriverCheckException(
                \sprintf(
                    'Phpfastcache needs Solarium %s or greater to be installed',
                    self::MINIMUM_SOLARIUM_VERSION
                )
            );
        }

        return true;
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverConnectException
     */
    protected function driverConnect(): bool
    {
        $this->mappingSchema = $this->getConfig()->getMappingSchema();

        $endpoint = [
            'endpoint' => [
                $this->getConfig()->getEndpointName() => [
                    'scheme' => $this->getConfig()->getScheme(),
                    'host' => $this->getConfig()->getHost(),
                    'port' => $this->getConfig()->getPort(),
                    'path' => $this->getConfig()->getPath(),
                    'core' => $this->getConfig()->getCoreName(),
                ]
            ]
        ];

        $this->eventManager->dispatch(Event::SOLR_BUILD_ENDPOINT, $this, new EventReferenceParameter($endpoint));

        $this->instance = new SolariumClient(new SolariumCurlAdapter(), $this->getConfig()->getEventDispatcher(), $endpoint);

        try {
            return $this->instance->ping($this->instance->createPing())->getStatus() === 0;
        } catch (SolariumExceptionInterface $e) {
            throw new PhpfastcacheDriverConnectException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $update = $this->instance->createUpdate();

        $doc = $update->createDocument();
        $doc->{$this->getSolrField(self::SOLR_DEFAULT_ID_FIELD)} = $item->getEncodedKey();
        $doc->{$this->getSolrField(self::SOLR_DISCRIMINATOR_FIELD)} = self::SOLR_DISCRIMINATOR_VALUE;
        $doc->{$this->getSolrField(self::DRIVER_KEY_WRAPPER_INDEX)} = $item->getKey();
        $doc->{$this->getSolrField(self::DRIVER_DATA_WRAPPER_INDEX)} = $this->encode($item->getRawValue());
        $doc->{$this->getSolrField(self::DRIVER_TAGS_WRAPPER_INDEX)} = $item->getTags();
        $doc->{$this->getSolrField(self::DRIVER_EDATE_WRAPPER_INDEX)} = $item->getExpirationDate()->format(\DateTimeInterface::ATOM);

        if ($this->getConfig()->isItemDetailedDate()) {
            $doc->{$this->getSolrField(self::DRIVER_MDATE_WRAPPER_INDEX)} = $item->getModificationDate()->format(\DateTimeInterface::ATOM);
            $doc->{$this->getSolrField(self::DRIVER_CDATE_WRAPPER_INDEX)} = $item->getCreationDate()->format(\DateTimeInterface::ATOM);
        }

        $update->addDocument($doc);
        $update->addCommit();

        return $this->instance->update($update)->getStatus() === 0;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     * @throws \Exception
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $query = $this->instance->createSelect()
            ->setQuery($this->getSolrField(self::SOLR_DEFAULT_ID_FIELD) . ':' . $item->getEncodedKey())
            ->setRows(1);

        $results = $this->instance->execute($query);

        if ($results instanceof \IteratorAggregate) {
            $document = $results->getIterator()[0] ?? null;

            if ($document instanceof SolariumDocument) {
                return $this->decodeDocument($document);
            }
        }

        return null;
    }

    /**
     * @param SolariumDocument $document
     * @return array<mixed>
     * @throws \Exception
     */
    protected function decodeDocument(SolariumDocument $document): array
    {
        $fields = $document->getFields();
        $key = $fields[$this->getSolrField(self::DRIVER_KEY_WRAPPER_INDEX)];

        if (\is_array($key)) {
            throw new PhpfastcacheInvalidTypeException(
                'Your Solr core seems to be misconfigured, please check the Phpfastcache wiki to setup the expected schema: 
                https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV9.1%CB%96%5D-Configuring-a-Solr-driver'
            );
        }

        $value = [
            self::DRIVER_KEY_WRAPPER_INDEX => $key,
            self::DRIVER_TAGS_WRAPPER_INDEX => $fields[$this->getSolrField(self::DRIVER_TAGS_WRAPPER_INDEX)] ?? [],
            self::DRIVER_DATA_WRAPPER_INDEX => $this->decode(
                $fields[$this->getSolrField(self::DRIVER_DATA_WRAPPER_INDEX)],
            ),
        ];

        $eDate = $fields[$this->getSolrField(self::DRIVER_EDATE_WRAPPER_INDEX)];

        $value[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX] = new \DateTime($eDate);

        if ($this->getConfig()->isItemDetailedDate()) {
            $cDate = $fields[$this->getSolrField(self::DRIVER_CDATE_WRAPPER_INDEX)];
            if (!empty($cDate)) {
                $value[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] = new \DateTime($cDate);
            }

            $mDate = $fields[$this->getSolrField(self::DRIVER_MDATE_WRAPPER_INDEX)];
            if (!empty($mDate)) {
                $value[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] = new \DateTime($mDate);
            }
        }

        return $value;
    }


    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $update = $this->instance->createUpdate();

        $update->addDeleteQuery($this->getSolrField(self::SOLR_DEFAULT_ID_FIELD) . ':' . $item->getEncodedKey());
        $update->addDeleteQuery($this->getSolrField(self::SOLR_DISCRIMINATOR_FIELD) . ':' . self::SOLR_DISCRIMINATOR_VALUE);
        $update->addCommit();

        return $this->instance->update($update)->getStatus() === 0;
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverException
     */
    protected function driverClear(): bool
    {
        // get an update query instance
        $update = $this->instance->createUpdate();
        $update->addDeleteQuery($this->getSolrField(self::SOLR_DISCRIMINATOR_FIELD) . ':' . self::SOLR_DISCRIMINATOR_VALUE);
        $update->addCommit();

        return $this->instance->update($update)->getStatus() === 0;
    }

    /**
     * @param string $pfcField
     * @return string
     */
    protected function getSolrField(string $pfcField): string
    {
        return $this->mappingSchema[$pfcField];
    }

    public function getStats(): DriverStatistic
    {
        /**
         * Solr "phpfastcache" core info
         */
        $coreAdminQuery = $this->instance->createCoreAdmin();
        $statusAction = $coreAdminQuery->createStatus();
        $coreAdminQuery->setAction($statusAction);
        $response = $this->instance->coreAdmin($coreAdminQuery);
        $coreServerInfo = $response->getData()['status'][$this->getConfig()->getCoreName()];

        /**
         * Unfortunately Solarium does not offer
         * an API to query the admin info system :(
         */
        $adminSystemInfoUrl = $this->getConfig()->getScheme()
            . '://'
            . $this->getConfig()->getHost()
            . ':'
            . $this->getConfig()->getPort()
            . rtrim($this->getConfig()->getPath(), '/')
            . '/solr/admin/info/system';

        if (($content = @\file_get_contents($adminSystemInfoUrl)) !== false) {
            try {
                $serverSystemInfo = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $serverSystemInfo = [];
            }
        }

        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo(sprintf(
                'Solarium %s and Solr %s for %s %s. %d document(s) stored in the "%s" core',
                $this->instance::VERSION,
                $serverSystemInfo['lucene']['solr-spec-version'] ?? '[unknown SOLR version]',
                $serverSystemInfo['system']['name'] ?? '[unknown OS]',
                $serverSystemInfo['system']['version'] ?? '[unknown OS version]',
                $coreServerInfo['index']['numDocs'] ?? 0,
                $this->getConfig()->getCoreName()
            ))
            ->setRawData($coreServerInfo)
            ->setSize($coreServerInfo['index']['sizeInBytes'] ?? 0);
    }
}
