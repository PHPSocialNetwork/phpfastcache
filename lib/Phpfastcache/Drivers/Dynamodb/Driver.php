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

namespace Phpfastcache\Drivers\Dynamodb;

use Aws\Sdk as AwsSdk;
use Aws\DynamoDb\DynamoDbClient as AwsDynamoDbClient;
use Aws\DynamoDb\Marshaler as AwsMarshaler;

use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * Class Driver
 * @property Config $config
 * @property AwsDynamoDbClient $instance
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    protected AwsSdk $awsSdk;

    protected AwsMarshaler $marshaler;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \class_exists(AwsSdk::class) && \class_exists(AwsDynamoDbClient::class);
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        $this->awsSdk = new AwsSdk([
            'endpoint'   => $this->getConfig()->getEndpoint(),
            'region'   => $this->getConfig()->getRegion(),
            'version'  => $this->getConfig()->getVersion(),
            'debug'  => $this->getConfig()->isDebugEnabled(),
        ]);
        $this->instance = $this->awsSdk->createDynamoDb();
        $this->marshaler = new AwsMarshaler();

        if (!\count($this->instance->listTables(['TableNames' => [$this->getConfig()->getTable()]])->get('TableNames'))) {
            $this->createTable();
        }

        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $awsItem = $this->marshaler->marshalItem(
            $this->encodeDocument($this->driverPreWrap($item, true))
        );

        $result = $this->instance->putItem([
            'TableName' => $this->getConfig()->getTable(),
            'Item' => $awsItem
        ]);

        return ($result->get('@metadata')['statusCode'] ?? null) === 200;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return null|array
     * @throws \Exception
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $key = $this->marshaler->marshalItem([
            $this->getConfig()->getPartitionKey() => $item->getKey()
        ]);

        $result = $this->instance->getItem([
            'TableName' => $this->getConfig()->getTable(),
            'Key' => $key
        ]);

        $awsItem = $result->get('Item');

        if ($awsItem !== null) {
            return $this->decodeDocument(
                $this->marshaler->unmarshalItem($awsItem)
            );
        }

        return null;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $key = $this->marshaler->marshalItem([
            $this->getConfig()->getPartitionKey() => $item->getKey()
        ]);

        $result = $this->instance->deleteItem([
            'TableName' => $this->getConfig()->getTable(),
            'Key' => $key
        ]);

        return ($result->get('@metadata')['statusCode'] ?? null) === 200;
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        $params = [
            'TableName' => $this->getConfig()->getTable(),
        ];

        $result = $this->instance->deleteTable($params);

        $this->instance->waitUntil('TableNotExists', $params);

        $this->createTable();

        return ($result->get('@metadata')['statusCode'] ?? null) === 200;
    }

    protected function createTable() :void
    {
        $params = [
            'TableName' => $this->getConfig()->getTable(),
            'KeySchema' => [
                [
                    'AttributeName' => $this->getConfig()->getPartitionKey(),
                    'KeyType' => 'HASH'
                ]
            ],
            'AttributeDefinitions' => [
                [
                    'AttributeName' => $this->getConfig()->getPartitionKey(),
                    'AttributeType' => 'S'
                ],
            ],
            'ProvisionedThroughput' => [
                'ReadCapacityUnits' => 10,
                'WriteCapacityUnits' => 10
            ]
        ];

        $this->instance->createTable($params);
        $this->instance->waitUntil('TableExists', $params);
    }

    public function getStats(): DriverStatistic
    {
        /**
         * @todo :D
         */
        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo('')
            ->setRawData([])
            ->setSize(0);
    }

    protected function encodeDocument(array $data): array
    {
        $data[self::DRIVER_DATA_WRAPPER_INDEX] = $this->encode($data[self::DRIVER_DATA_WRAPPER_INDEX]);

        return $data;
    }

    protected function decodeDocument(array $data): array
    {
        $data[self::DRIVER_DATA_WRAPPER_INDEX] = $this->decode($data[self::DRIVER_DATA_WRAPPER_INDEX]);

        return $data;
    }

    public function getConfig() : Config|ConfigurationOption
    {
        return $this->config;
    }
}
