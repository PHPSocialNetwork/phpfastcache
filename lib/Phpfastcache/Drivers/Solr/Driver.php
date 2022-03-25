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
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Solarium\Client as SolariumClient;
use Solarium\Core\Client\Adapter\Curl as SolariumCurlAdapter;
use Solarium\Exception\ExceptionInterface as SolariumExceptionInterface;
use Solarium\QueryType\Select\Result\Document as SolariumDocument;

/**
 * Class Driver
 * @property Config $config
 * @property SolariumClient $instance
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    /**
     * Copy of configuration entry for performance optimization
     * @var string[]
     */
    protected array $mappingSchema = [];

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \class_exists(SolariumClient::class);
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverConnectException
     */
    protected function driverConnect(): bool
    {
        $this->mappingSchema = $this->getConfig()->getMappingSchema();
        $this->instance = new SolariumClient(new SolariumCurlAdapter(), $this->getConfig()->getEventDispatcher(), [
            'endpoint' => [
                $this->getConfig()->getEndpointName() => [
                    'scheme' => $this->getConfig()->getScheme(),
                    'host' => $this->getConfig()->getHost(),
                    'port' => $this->getConfig()->getPort(),
                    'path' => $this->getConfig()->getPath(),
                    'core' => $this->getConfig()->getCoreName(),
                ]
            ]
        ]);

        try {
            $this->instance->ping($this->instance->createPing());
        } catch (SolariumExceptionInterface $e) {
            throw new PhpfastcacheDriverConnectException($e->getMessage(), 0, $e);
        }

        return false;
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
        /** @SuppressWarnings(PHPMD.UndefinedVariable) */
        $doc->id = $item->getEncodedKey();
        $doc->{$this->getSolrField(self::DRIVER_KEY_WRAPPER_INDEX)} = $item->getKey();
        $doc->{$this->getSolrField(self::DRIVER_DATA_WRAPPER_INDEX)} = $this->encode($item->getRawValue());
        $doc->{$this->getSolrField(self::DRIVER_TAGS_WRAPPER_INDEX)} = $item->getTags();
        $doc->{$this->getSolrField(self::DRIVER_EDATE_WRAPPER_INDEX)} = [
            $item->getExpirationDate()->format(\DateTimeInterface::ATOM),
            $item->getExpirationDate()->getTimezone()->getName()
        ];

        if ($this->getConfig()->isItemDetailedDate()) {
            $doc->{$this->getSolrField(self::DRIVER_MDATE_WRAPPER_INDEX)} = [
                $item->getModificationDate()->format(\DateTimeInterface::ATOM),
                $item->getModificationDate()->getTimezone()->getName()
            ];
            $doc->{$this->getSolrField(self::DRIVER_CDATE_WRAPPER_INDEX)} = [
                $item->getCreationDate()->format(\DateTimeInterface::ATOM),
                $item->getCreationDate()->getTimezone()->getName()
            ];
        }

        $update->addDocument($doc);
        $update->addCommit();

        return $this->instance->update($update)->getStatus() === 0;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return null|array
     * @throws \Exception
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $query = $this->instance->createSelect()
            ->setQuery('id:' . $item->getEncodedKey())
            ->setRows(1);

        $results =  $this->instance->execute($query);

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
     * @return array
     * @throws \Exception
     */
    protected function decodeDocument(SolariumDocument $document): array
    {
        $fields = $document->getFields();

        $value = [
            self::DRIVER_KEY_WRAPPER_INDEX => $fields[$this->getSolrField(self::DRIVER_KEY_WRAPPER_INDEX)],
            self::DRIVER_TAGS_WRAPPER_INDEX => $fields[$this->getSolrField(self::DRIVER_TAGS_WRAPPER_INDEX)] ?? [],
            self::DRIVER_DATA_WRAPPER_INDEX => $this->decode(
                $fields[$this->getSolrField(self::DRIVER_DATA_WRAPPER_INDEX)],
            ),
        ];

        $eDate = $fields[$this->getSolrField(self::DRIVER_EDATE_WRAPPER_INDEX)];

        $value[ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX] = new \DateTime(
            $eDate[0],
            new \DateTimeZone($eDate[1])
        );

        if ($this->getConfig()->isItemDetailedDate()) {
            $cDate = $fields[$this->getSolrField(self::DRIVER_CDATE_WRAPPER_INDEX)];
            if (isset($cDate[0], $cDate[1])) {
                $value[ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX] = new \DateTime(
                    $cDate[0],
                    new \DateTimeZone($cDate[1])
                );
            }

            $mDate = $fields[$this->getSolrField(self::DRIVER_MDATE_WRAPPER_INDEX)];
            if (isset($mDate[0], $cDate[1])) {
                $value[ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX] = new \DateTime(
                    $mDate[0],
                    new \DateTimeZone($mDate[1])
                );
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

        $update->addDeleteById($item->getEncodedKey());
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
        $update->addDeleteQuery('*:*');
        $update->addCommit();

        return  $this->instance->update($update)->getStatus() === 0;
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
        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo('')
            ->setRawData(null)
            ->setSize(0);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
