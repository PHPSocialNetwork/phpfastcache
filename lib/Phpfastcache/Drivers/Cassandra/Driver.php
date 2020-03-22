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

namespace Phpfastcache\Drivers\Cassandra;

use Cassandra;
use Cassandra\Exception;
use Cassandra\Exception\InvalidArgumentException;
use Cassandra\Session as CassandraSession;
use DateTime;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{PhpfastcacheInvalidArgumentException, PhpfastcacheLogicException};
use Psr\Cache\CacheItemInterface;


/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property CassandraSession $instance Instance of driver service
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface, AggregatablePoolInterface
{
    protected const CASSANDRA_KEY_SPACE = 'phpfastcache';
    protected const CASSANDRA_TABLE = 'cacheItems';

    use DriverBaseTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('Cassandra') && class_exists(Cassandra::class);
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
Please not that this repository only provide php stubs and C/C++ sources, it does NOT provide php client.
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
            )
        );

        return (new DriverStatistic())
            ->setSize($result->first()['cache_size'])
            ->setRawData([])
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setInfo('The cache size represents only the cache data itself without counting data structures associated to the cache entries.');
    }

    /**
     * @return bool
     * @throws PhpfastcacheLogicException
     * @throws Exception
     */
    protected function driverConnect(): bool
    {
        if ($this->instance instanceof CassandraSession) {
            throw new PhpfastcacheLogicException('Already connected to Couchbase server');
        }

        $clientConfig = $this->getConfig();

        $clusterBuilder = Cassandra::cluster()
            ->withContactPoints($clientConfig->getHost())
            ->withPort($clientConfig->getPort());

        if (!empty($clientConfig->isSslEnabled())) {
            if (!empty($clientConfig->isSslVerify())) {
                $sslBuilder = Cassandra::ssl()->withVerifyFlags(Cassandra::VERIFY_PEER_CERT);
            } else {
                $sslBuilder = Cassandra::ssl()->withVerifyFlags(Cassandra::VERIFY_NONE);
            }

            $clusterBuilder->withSSL($sslBuilder->build());
        }

        $clusterBuilder->withConnectTimeout($clientConfig->getTimeout());

        if ($clientConfig->getUsername()) {
            $clusterBuilder->withCredentials($clientConfig->getUsername(), $clientConfig->getPassword());
        }

        $this->instance = $clusterBuilder->build()->connect();

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
            )
        );
        $this->instance->execute(new Cassandra\SimpleStatement(sprintf('USE %s;', self::CASSANDRA_KEY_SPACE)));
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
            )
        );

        return true;
    }

    /**
     * @param CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        try {
            $options = new Cassandra\ExecutionOptions(
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
                return $this->decode($results->first()['cache_data']);
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $cacheData = $this->encode($this->driverPreWrap($item));
                $options = new Cassandra\ExecutionOptions(
                    [
                        'arguments' => [
                            'cache_uuid' => new Cassandra\Uuid(),
                            'cache_id' => $item->getKey(),
                            'cache_data' => $cacheData,
                            'cache_creation_date' => new Cassandra\Timestamp((new DateTime())->getTimestamp()),
                            'cache_expiration_date' => new Cassandra\Timestamp($item->getExpirationDate()->getTimestamp()),
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
                throw new PhpfastcacheInvalidArgumentException($e, 0, $e);
            }
        } else {
            throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @param CacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $options = new Cassandra\ExecutionOptions(
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
        } else {
            throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
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
                )
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
