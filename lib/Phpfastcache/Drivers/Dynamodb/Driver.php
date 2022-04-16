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
use Aws\DynamoDb\Exception\DynamoDbException as AwsDynamoDbException;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Psr\Http\Message\UriInterface;

/**
 * Class Driver
 * @method Config getConfig()
 * @property AwsDynamoDbClient $instance
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    protected const TTL_FIELD_NAME = 't';

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
     * @throws PhpfastcacheDriverConnectException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverConnect(): bool
    {
        $wsAccessKey = $this->getConfig()->getSuperGlobalAccessor()('SERVER', 'AWS_ACCESS_KEY_ID');
        $awsSecretKey = $this->getConfig()->getSuperGlobalAccessor()('SERVER', 'AWS_SECRET_ACCESS_KEY');

        if (empty($wsAccessKey)) {
            throw new PhpfastcacheDriverConnectException('The environment configuration AWS_ACCESS_KEY_ID must be set');
        }

        if (empty($awsSecretKey)) {
            throw new PhpfastcacheDriverConnectException('The environment configuration AWS_SECRET_ACCESS_KEY must be set');
        }

        $this->awsSdk = new AwsSdk([
            'endpoint'   => $this->getConfig()->getEndpoint(),
            'region'   => $this->getConfig()->getRegion(),
            'version'  => $this->getConfig()->getVersion(),
            'debug'  => $this->getConfig()->isDebugEnabled(),
        ]);
        $this->instance = $this->awsSdk->createDynamoDb();
        $this->marshaler = new AwsMarshaler();

        if (!$this->hasTable()) {
            $this->createTable();
        }

        if (!$this->hasTtlEnabled()) {
            $this->enableTtl();
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
            \array_merge(
                $this->encodeDocument($this->driverPreWrap($item, true)),
                ['t' => $item->getExpirationDate()->getTimestamp()]
            )
        );

        $result = $this->instance->putItem([
            'TableName' => $this->getConfig()->getTable(),
            'Item' => $awsItem
        ]);

        return ($result->get('@metadata')['statusCode'] ?? null) === 200;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
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
     * @throws PhpfastcacheDriverException
     */
    protected function driverClear(): bool
    {
        $params = [
            'TableName' => $this->getConfig()->getTable(),
        ];

        $result = $this->instance->deleteTable($params);

        $this->instance->waitUntil('TableNotExists', $params);

        $this->createTable();
        $this->enableTtl();

        return ($result->get('@metadata')['statusCode'] ?? null) === 200;
    }

    protected function hasTable(): bool
    {
        return \count($this->instance->listTables(['TableNames' => [$this->getConfig()->getTable()]])->get('TableNames')) > 0;
    }

    protected function createTable(): void
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

        $this->eventManager->dispatch(Event::DYNAMODB_CREATE_TABLE, $this, new EventReferenceParameter($params));

        $this->instance->createTable($params);
        $this->instance->waitUntil('TableExists', $params);
    }

    protected function hasTtlEnabled(): bool
    {
        $ttlDesc = $this->instance->describeTimeToLive(['TableName' => $this->getConfig()->getTable()])->get('TimeToLiveDescription');

        if (!isset($ttlDesc['AttributeName'], $ttlDesc['TimeToLiveStatus'])) {
            return false;
        }

        return $ttlDesc['TimeToLiveStatus'] === 'ENABLED' && $ttlDesc['AttributeName'] === self::TTL_FIELD_NAME;
    }

    /**
     * @throws PhpfastcacheDriverException
     */
    protected function enableTtl(): void
    {
        try {
            $this->instance->updateTimeToLive([
                'TableName' => $this->getConfig()->getTable(),
                'TimeToLiveSpecification' => [
                    "AttributeName" => self::TTL_FIELD_NAME,
                    "Enabled" => true
                ],
            ]);
        } catch (AwsDynamoDbException $e) {
            /**
             * Error 400 can be an acceptable error of a
             * Dynamodb restriction: "Time to live has been modified multiple times within a fixed interval"
             * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_UpdateTimeToLive.html
             */
            if ($e->getStatusCode() !== 400) {
                throw new PhpfastcacheDriverException(
                    'Failed to enable TTL with the following error: ' . $e->getMessage()
                );
            }
        }
    }

    public function getStats(): DriverStatistic
    {
        /** @var UriInterface $endpoint */
        $endpoint = $this->instance->getEndpoint();
        $table = $this->instance->describeTable(['TableName' => $this->getConfig()->getTable()])->get('Table');

        $info = \sprintf(
            'Dynamo server "%s" | Table "%s" with %d item(s) stored',
            $endpoint->getHost(),
            $table['TableName'] ?? 'Unknown table name',
            $table['ItemCount'] ?? 'Unknown item count',
        );

        $data = [
            'dynamoEndpoint' => $endpoint,
            'dynamoTable' => $table,
            'dynamoConfig' => $this->instance->getConfig(),
            'dynamoApi' => $this->instance->getApi()->toArray(),
        ];

        return (new DriverStatistic())
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo($info)
            ->setRawData($data)
            ->setSize($data['dynamoTable']['TableSizeBytes'] ?? 0);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function encodeDocument(array $data): array
    {
        $data[self::DRIVER_DATA_WRAPPER_INDEX] = $this->encode($data[self::DRIVER_DATA_WRAPPER_INDEX]);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function decodeDocument(array $data): array
    {
        $data[self::DRIVER_DATA_WRAPPER_INDEX] = $this->decode($data[self::DRIVER_DATA_WRAPPER_INDEX]);

        return $data;
    }
}
