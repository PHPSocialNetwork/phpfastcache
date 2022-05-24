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

namespace Phpfastcache\Drivers\Cassandra;

use Cassandra;
use Cassandra\Exception;
use Cassandra\Exception\InvalidArgumentException;
use Cassandra\Session as CassandraSession;
use DateTime;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * Class Driver
 * @property CassandraSession|null $instance Instance of driver service
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    protected const CASSANDRA_KEY_SPACE = 'phpfastcache';
    protected const CASSANDRA_TABLE = 'cacheItems';

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('Cassandra') && class_exists(Cassandra::class);
    }

    /**
     * @return bool
     * @throws PhpfastcacheLogicException
     * @throws Exception
     */
    protected function driverConnect(): bool
    {
        $clientConfig = $this->getConfig();

        $clusterBuilder = Cassandra::cluster()
            ->withContactPoints($clientConfig->getHost())
            ->withPort($clientConfig->getPort());

        if (!empty($clientConfig->isSslEnabled())) {
            $sslBuilder = Cassandra::ssl();
            if (!empty($clientConfig->isSslVerify())) {
                $sslBuilder->withVerifyFlags(Cassandra::VERIFY_PEER_CERT);
            } else {
                $sslBuilder->withVerifyFlags(Cassandra::VERIFY_NONE);
            }

            $clusterBuilder->withSSL($sslBuilder->build());
        }

        $clusterBuilder->withConnectTimeout($clientConfig->getTimeout());

        if ($clientConfig->getUsername()) {
            $clusterBuilder->withCredentials($clientConfig->getUsername(), $clientConfig->getPassword());
        }

        $this->instance = $clusterBuilder->build()->connect('');

        /**
         * In case of emergency:
         * $this->instance->execute(
         *      new Cassandra\SimpleStatement(\sprintf("DROP KEYSPACE %s;", self::CASSANDRA_KEY_SPACE))
         * );
         */

        $this->instance->execute(
            new Cassandra\SimpleStatement(
                sprintf(
                    "CREATE KEYSPACE IF NOT EXISTS %s WITH REPLICATION = { 'class' : 'SimpleStrategy', 'replication_factor' : 1 };",
                    self::CASSANDRA_KEY_SPACE
                )
            ),
            []
        );
        $this->instance->execute(new Cassandra\SimpleStatement(sprintf('USE %s;', self::CASSANDRA_KEY_SPACE)), []);
        $this->instance->execute(
            new Cassandra\SimpleStatement(
                sprintf(
                    '
                CREATE TABLE IF NOT EXISTS %s (
                    cache_uuid uuid,
                    cache_id varchar,
                    cache_data text,
                    cache_creation_date timestamp,
                    cache_expiration_date timestamp,
                    cache_length int,
                    PRIMARY KEY (cache_id)
                );',
                    self::CASSANDRA_TABLE
                )
            ),
            []
        );

        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        try {
            $options = $this->getCompatibleExecutionOptionsArgument(
                [
                    'arguments' => ['cache_id' => $item->getKey()],
                    'page_size' => 1,
                ]
            );
            $query = sprintf(
                'SELECT cache_data FROM %s.%s WHERE cache_id = :cache_id;',
                self::CASSANDRA_KEY_SPACE,
                self::CASSANDRA_TABLE
            );
            $results = $this->instance->execute(new Cassandra\SimpleStatement($query), $options);

            if ($results instanceof Cassandra\Rows && $results->count() === 1) {
                return $this->decode($results->first()['cache_data']) ?: null;
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        try {
            $cacheData = $this->encode($this->driverPreWrap($item));
            $options = $this->getCompatibleExecutionOptionsArgument(
                [
                    'arguments' => [
                        'cache_uuid' => new Cassandra\Uuid(''),
                        'cache_id' => $item->getKey(),
                        'cache_data' => $cacheData,
                        'cache_creation_date' => new Cassandra\Timestamp((new DateTime())->getTimestamp(), 0),
                        'cache_expiration_date' => new Cassandra\Timestamp($item->getExpirationDate()->getTimestamp(), 0),
                        'cache_length' => strlen($cacheData),
                    ],
                    'consistency' => Cassandra::CONSISTENCY_ALL,
                    'serial_consistency' => Cassandra::CONSISTENCY_SERIAL,
                ]
            );

            $query = sprintf(
                'INSERT INTO %s.%s
                    (
                      cache_uuid, 
                      cache_id, 
                      cache_data, 
                      cache_creation_date, 
                      cache_expiration_date,
                      cache_length
                    )
                  VALUES (:cache_uuid, :cache_id, :cache_data, :cache_creation_date, :cache_expiration_date, :cache_length);
            ',
                self::CASSANDRA_KEY_SPACE,
                self::CASSANDRA_TABLE
            );

            $result = $this->instance->execute(new Cassandra\SimpleStatement($query), $options);
            /**
             * There's no real way atm
             * to know if the item has
             * been really upserted
             */
            return $result instanceof Cassandra\Rows;
        } catch (InvalidArgumentException $e) {
            throw new PhpfastcacheInvalidArgumentException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        try {
            $options = $this->getCompatibleExecutionOptionsArgument(
                [
                    'arguments' => [
                        'cache_id' => $item->getKey(),
                    ],
                ]
            );
            $result = $this->instance->execute(
                new Cassandra\SimpleStatement(
                    sprintf(
                        'DELETE FROM %s.%s WHERE cache_id = :cache_id;',
                        self::CASSANDRA_KEY_SPACE,
                        self::CASSANDRA_TABLE
                    )
                ),
                $options
            );

            /**
             * There's no real way atm
             * to know if the item has
             * been really deleted
             */
            return $result instanceof Cassandra\Rows;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        try {
            $this->instance->execute(
                new Cassandra\SimpleStatement(
                    sprintf(
                        'TRUNCATE %s.%s;',
                        self::CASSANDRA_KEY_SPACE,
                        self::CASSANDRA_TABLE
                    )
                ),
                null
            );

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
<p>
To install the php Cassandra extension via Pecl:
<code>sudo pecl install cassandra</code>
More information on: https://github.com/datastax/php-driver
Please note that this repository only provide php stubs and C/C++ sources, it does NOT provide php client.
</p>
HELP;
    }

    /**
     * @return DriverStatistic
     * @throws Exception
     */
    public function getStats(): DriverStatistic
    {
        $result = $this->instance->execute(
            new Cassandra\SimpleStatement(
                sprintf(
                    'SELECT SUM(cache_length) as cache_size FROM %s.%s',
                    self::CASSANDRA_KEY_SPACE,
                    self::CASSANDRA_TABLE
                )
            ),
            null
        );

        return (new DriverStatistic())
            ->setSize($result->first()['cache_size'])
            ->setRawData([])
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo('The cache size represents only the cache data itself without counting data structures associated to the cache entries.');
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>|Cassandra\ExecutionOptions
     */
    protected function getCompatibleExecutionOptionsArgument(array $options): mixed
    {
        if ($this->getConfig()->isUseLegacyExecutionOptions()) {
            return new Cassandra\ExecutionOptions($options);
        }

        return $options;
    }
}
