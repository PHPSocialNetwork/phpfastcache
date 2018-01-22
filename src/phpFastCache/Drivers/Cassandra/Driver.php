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

namespace phpFastCache\Drivers\Cassandra;

use Cassandra;
use Cassandra\Session as CassandraSession;
use phpFastCache\Core\Pool\{DriverBaseTrait, ExtendedCacheItemPoolInterface};
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\{
  phpFastCacheInvalidArgumentException, phpFastCacheLogicException
};
use phpFastCache\Util\ArrayObject;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property CassandraSession $instance Instance of driver service
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    const CASSANDRA_KEY_SPACE = 'phpfastcache';
    const CASSANDRA_TABLE     = 'cacheItems';

    use DriverBaseTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('Cassandra') && class_exists(\Cassandra::class);
    }

    /**
     * @return bool
     * @throws phpFastCacheLogicException
     * @throws \Cassandra\Exception
     */
    protected function driverConnect(): bool
    {
        if ($this->instance instanceof CassandraSession) {
            throw new phpFastCacheLogicException('Already connected to Couchbase server');
        } else {
            $clientConfig = $this->getConfig();

            $clusterBuilder = Cassandra::cluster()
              ->withContactPoints($clientConfig[ 'host' ])
              ->withPort($clientConfig[ 'port' ]);

            if (!empty($clientConfig[ 'sslEnabled' ])) {
                if (!empty($clientConfig[ 'sslVerify' ])) {
                    $sslBuilder = Cassandra::ssl()->withVerifyFlags(Cassandra::VERIFY_PEER_CERT);
                } else {
                    $sslBuilder = Cassandra::ssl()->withVerifyFlags(Cassandra::VERIFY_NONE);
                }

                $clusterBuilder->withSSL($sslBuilder->build());
            }

            $clusterBuilder->withConnectTimeout($clientConfig[ 'timeout' ]);

            if ($clientConfig[ 'username' ]) {
                $clusterBuilder->withCredentials($clientConfig[ 'username' ], $clientConfig[ 'password' ]);
            }

            $this->instance = $clusterBuilder->build()->connect();

            /**
             * In case of emergency:
             * $this->instance->execute(
             *      new Cassandra\SimpleStatement(sprintf("DROP KEYSPACE %s;", self::CASSANDRA_KEY_SPACE))
             * );
             */

            $this->instance->execute(new Cassandra\SimpleStatement(sprintf(
              "CREATE KEYSPACE IF NOT EXISTS %s WITH REPLICATION = { 'class' : 'SimpleStrategy', 'replication_factor' : 1 };",
              self::CASSANDRA_KEY_SPACE
            )));
            $this->instance->execute(new Cassandra\SimpleStatement(sprintf('USE %s;', self::CASSANDRA_KEY_SPACE)));
            $this->instance->execute(new Cassandra\SimpleStatement(sprintf('
                CREATE TABLE IF NOT EXISTS %s (
                    cache_uuid uuid,
                    cache_id varchar,
                    cache_data text,
                    cache_creation_date timestamp,
                    cache_expiration_date timestamp,
                    cache_length int,
                    PRIMARY KEY (cache_id)
                );', self::CASSANDRA_TABLE
            )));
        }

        return true;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        try {
            $options = new Cassandra\ExecutionOptions([
              'arguments' => ['cache_id' => $item->getKey()],
              'page_size' => 1,
            ]);
            $query = sprintf(
              'SELECT cache_data FROM %s.%s WHERE cache_id = :cache_id;',
              self::CASSANDRA_KEY_SPACE,
              self::CASSANDRA_TABLE
            );
            $results = $this->instance->execute(new Cassandra\SimpleStatement($query), $options);

            if ($results instanceof Cassandra\Rows && $results->count() === 1) {
                return $this->decode($results->first()[ 'cache_data' ]);
            } else {
                return null;
            }
        } catch (Cassandra\Exception $e) {
            return null;
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $cacheData = $this->encode($this->driverPreWrap($item));
                $options = new Cassandra\ExecutionOptions([
                  'arguments' => [
                    'cache_uuid' => new Cassandra\Uuid(),
                    'cache_id' => $item->getKey(),
                    'cache_data' => $cacheData,
                    'cache_creation_date' => new Cassandra\Timestamp((new \DateTime())->getTimestamp()),
                    'cache_expiration_date' => new Cassandra\Timestamp($item->getExpirationDate()->getTimestamp()),
                    'cache_length' => strlen($cacheData),
                  ],
                  'consistency' => Cassandra::CONSISTENCY_ALL,
                  'serial_consistency' => Cassandra::CONSISTENCY_SERIAL,
                ]);

                $query = sprintf('INSERT INTO %s.%s
                    (
                      cache_uuid, 
                      cache_id, 
                      cache_data, 
                      cache_creation_date, 
                      cache_expiration_date,
                      cache_length
                    )
                  VALUES (:cache_uuid, :cache_id, :cache_data, :cache_creation_date, :cache_expiration_date, :cache_length);
            ', self::CASSANDRA_KEY_SPACE, self::CASSANDRA_TABLE);

                $result = $this->instance->execute(new Cassandra\SimpleStatement($query), $options);
                /**
                 * There's no real way atm
                 * to know if the item has
                 * been really upserted
                 */
                return $result instanceof Cassandra\Rows;
            } catch (\Cassandra\Exception\InvalidArgumentException $e) {
                throw new phpFastCacheInvalidArgumentException($e, 0, $e);
            }
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $options = new Cassandra\ExecutionOptions([
                  'arguments' => [
                    'cache_id' => $item->getKey(),
                  ],
                ]);
                $result = $this->instance->execute(new Cassandra\SimpleStatement(sprintf(
                  'DELETE FROM %s.%s WHERE cache_id = :cache_id;',
                  self::CASSANDRA_KEY_SPACE,
                  self::CASSANDRA_TABLE
                )), $options);

                /**
                 * There's no real way atm
                 * to know if the item has
                 * been really deleted
                 */
                return $result instanceof Cassandra\Rows;
            } catch (Cassandra\Exception $e) {
                return false;
            }
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        try {
            $this->instance->execute(new Cassandra\SimpleStatement(sprintf(
              'TRUNCATE %s.%s;',
              self::CASSANDRA_KEY_SPACE, self::CASSANDRA_TABLE
            )));

            return true;
        } catch (Cassandra\Exception $e) {
            return false;
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
To install the php Cassandra extension via Pecl:
<code>sudo pecl install cassandra</code>
More information on: https://github.com/datastax/php-driver
Please not that this repository only provide php stubs and C/C++ sources, it does NOT provide php client.
</p>
HELP;
    }

    /**
     * @return DriverStatistic
     * @throws \Cassandra\Exception
     */
    public function getStats(): DriverStatistic
    {
        $result = $this->instance->execute(new Cassandra\SimpleStatement(sprintf(
          'SELECT SUM(cache_length) as cache_size FROM %s.%s',
          self::CASSANDRA_KEY_SPACE,
          self::CASSANDRA_TABLE
        )));

        return (new DriverStatistic())
          ->setSize($result->first()[ 'cache_size' ])
          ->setRawData([])
          ->setData(\implode(', ', \array_keys($this->itemInstances)))
          ->setInfo('The cache size represents only the cache data itself without counting data structures associated to the cache entries.');
    }

    /**
     * @return ArrayObject
     */
    public function getDefaultConfig(): ArrayObject
    {
        $defaultConfig = new ArrayObject();

        $defaultConfig[ 'host' ] = '127.0.0.1';
        $defaultConfig[ 'port' ] = 9042;
        $defaultConfig[ 'timeout' ] = 2;
        $defaultConfig[ 'username' ] = '';
        $defaultConfig[ 'password' ] = '';
        $defaultConfig[ 'sslEnabled' ] = false;
        $defaultConfig[ 'sslVerify' ] = false;

        return $defaultConfig;
    }
}