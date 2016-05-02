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

namespace phpFastCache\Drivers\Sqlite;

use PDO;
use PDOException;
use phpFastCache\Cache\ExtendedCacheItemInterface;
use phpFastCache\Core\DriverAbstract;
use phpFastCache\Core\PathSeekerTrait;
use phpFastCache\Core\StandardPsr6StructureTrait;
use phpFastCache\Entities\driverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 */
class Driver extends DriverAbstract
{
    use PathSeekerTrait, StandardPsr6StructureTrait;

    /**
     *
     */
    const SQLITE_DIR = 'sqlite';
    /**
     *
     */
    const INDEXING_FILE = 'indexing';

    /**
     * @var int
     */
    protected $maxSize = 10; // 10 mb

    /**
     * @var array
     */
    protected $deferredList = [];

    /**
     * @var int
     */
    protected $currentDB = 1;

    /**
     * @var string
     */
    protected $SqliteDir = '';

    /**
     * @var null
     */
    protected $indexing;

    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct(array $config = [])
    {
        $this->setup($config);

        if (!$this->driverCheck()) {
            throw new phpFastCacheDriverCheckException(sprintf(self::DRIVER_CHECK_FAILURE, 'Sqlite'));
        } else {
            if (!file_exists($this->getSqliteDir()) && !@mkdir($this->getSqliteDir(), $this->setChmodAuto())) {
                throw new phpFastCacheDriverException(sprintf('Sqlite cannot write in "%s", aborting...', $this->getPath()));
            } else {
                $this->driverConnect();
            }
        }
    }

    /**
     * @return string
     * @throws \phpFastCache\Exceptions\phpFastCacheCoreException
     */
    public function getSqliteDir()
    {
        return $this->SqliteDir ?: $this->getPath() . '/' . self::SQLITE_DIR;
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        return extension_loaded('pdo_sqlite');
    }

    /**
     * INIT NEW DB
     * @param \PDO $db
     */
    public function initDB(\PDO $db)
    {
        $db->exec('drop table if exists "caching"');
        $db->exec('CREATE TABLE "caching" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "keyword" VARCHAR UNIQUE, "object" BLOB, "exp" INTEGER)');
        $db->exec('CREATE UNIQUE INDEX "cleanup" ON "caching" ("keyword","exp")');
        $db->exec('CREATE INDEX "exp" ON "caching" ("exp")');
        $db->exec('CREATE UNIQUE INDEX "keyword" ON "caching" ("keyword")');
    }

    /**
     * INIT Indexing DB
     * @param \PDO $db
     */
    public function initIndexing(\PDO $db)
    {

        // delete everything before reset indexing
        $dir = opendir($this->SqliteDir);
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && $file != 'indexing' && $file != 'dbfastcache') {
                unlink($this->SqliteDir . '/' . $file);
            }
        }

        $db->exec('drop table if exists "balancing"');
        $db->exec('CREATE TABLE "balancing" ("keyword" VARCHAR PRIMARY KEY NOT NULL UNIQUE, "db" INTEGER)');
        $db->exec('CREATE INDEX "db" ON "balancing" ("db")');
        $db->exec('CREATE UNIQUE INDEX "lookup" ON "balancing" ("keyword")');

    }

    /**
     * INIT Instant DB
     * Return Database of Keyword
     * @param $keyword
     * @return int
     */
    public function indexing($keyword)
    {
        if ($this->indexing == null) {
            $createTable = false;
            if (!file_exists($this->SqliteDir . '/indexing')) {
                $createTable = true;
            }

            $PDO = new PDO("sqlite:" . $this->SqliteDir . '/' . self::INDEXING_FILE);
            $PDO->setAttribute(PDO::ATTR_ERRMODE,
              PDO::ERRMODE_EXCEPTION);

            if ($createTable == true) {
                $this->initIndexing($PDO);
            }
            $this->indexing = $PDO;
            unset($PDO);

            $stm = $this->indexing->prepare("SELECT MAX(`db`) as `db` FROM `balancing`");
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            if (!isset($row[ 'db' ])) {
                $db = 1;
            } elseif ($row[ 'db' ] <= 1) {
                $db = 1;
            } else {
                $db = $row[ 'db' ];
            }

            // check file size

            $size = file_exists($this->SqliteDir . '/db' . $db) ? filesize($this->SqliteDir . '/db' . $db) : 1;
            $size = round($size / 1024 / 1024, 1);


            if ($size > $this->maxSize) {
                $db++;
            }
            $this->currentDB = $db;

        }

        // look for keyword
        $stm = $this->indexing->prepare("SELECT * FROM `balancing` WHERE `keyword`=:keyword LIMIT 1");
        $stm->execute([
          ':keyword' => $keyword,
        ]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if (isset($row[ 'db' ]) && $row[ 'db' ] != '') {
            $db = $row[ 'db' ];
        } else {
            /*
             * Insert new to Indexing
             */
            $db = $this->currentDB;
            $stm = $this->indexing->prepare("INSERT INTO `balancing` (`keyword`,`db`) VALUES(:keyword, :db)");
            $stm->execute([
              ':keyword' => $keyword,
              ':db' => $db,
            ]);
        }

        return $db;
    }

    /**
     * @param $keyword
     * @param bool $reset
     * @return PDO
     */
    public function getDb($keyword, $reset = false)
    {
        /**
         * Default is fastcache
         */
        $instant = $this->indexing($keyword);

        /**
         * init instant
         */
        if (!isset($this->instance[ $instant ])) {
            // check DB Files ready or not
            $createTable = false;
            if (!file_exists($this->SqliteDir . '/db' . $instant) || $reset == true) {
                $createTable = true;
            }
            $PDO = new PDO('sqlite:' . $this->SqliteDir . '/db' . $instant);
            $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($createTable == true) {
                $this->initDB($PDO);
            }

            $this->instance[ $instant ] = $PDO;
            unset($PDO);

        }

        return $this->instance[ $instant ];
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            $skipExisting = isset($this->config[ 'skipExisting' ]) ? $this->config[ 'skipExisting' ] : false;
            $toWrite = true;

            // check in cache first
            $in_cache = $this->driverRead($item->getKey(), $this->config);

            if ($skipExisting == true) {
                if ($in_cache == null) {
                    $toWrite = true;
                } else {
                    $toWrite = false;
                }
            }

            if ($toWrite == true) {
                try {
                    $stm = $this->getDb($item->getKey())
                      ->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
                    $stm->execute([
                      ':keyword' => $item->getKey(),
                      ':object' => $this->encode($this->driverPreWrap($item)),
                      ':exp' => time() + $item->getTtl(),
                    ]);

                    return true;
                } catch (\PDOException $e) {

                    try {
                        $stm = $this->getDb($item->getKey(), true)
                          ->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
                        $stm->execute([
                          ':keyword' => $item->getKey(),
                          ':object' => $this->encode($this->driverPreWrap($item)),
                          ':exp' => time() + $item->getTtl(),
                        ]);
                    } catch (PDOException $e) {
                        return false;
                    }
                }
            }

            return false;
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public function driverRead($key)
    {
        try {
            $stm = $this->getDb($key)
              ->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword AND (`exp` >= :U)  LIMIT 1");
            $stm->execute([
              ':keyword' => $key,
              ':U' => time(),
            ]);
            $row = $stm->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            try {
                $stm = $this->getDb($key, true)
                  ->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword AND (`exp` >= :U)  LIMIT 1");
                $stm->execute([
                  ':keyword' => $key,
                  ':U' => time(),
                ]);
                $row = $stm->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return null;
            }
        }

        if (isset($row[ 'id' ])) {
            /**
             * @var $item ExtendedCacheItemInterface
             */
            $item = $this->decode($row[ 'object' ]);
            if ($item instanceof ExtendedCacheItemInterface && $item->isExpired()) {
                $this->driverDelete($item);

                return null;
            }

            return $this->decode($row[ 'object' ]);
        }

        return null;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function driverDelete(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $stm = $this->getDb($item->getKey())
                  //->prepare("DELETE FROM `caching` WHERE (`id`=:id) OR (`exp` <= :U) ");
                  ->prepare("DELETE FROM `caching` WHERE (`exp` <= :U) OR (`keyword`=:keyword) ");

                return $stm->execute([
                    // ':id' => $row[ 'id' ],
                  ':keyword' => $item->getKey(),
                  ':U' => time(),
                ]);
            } catch (PDOException $e) {
                return false;
            }
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    public function driverClear()
    {
        $this->instance = [];
        $this->instance = null;

        // delete everything before reset indexing
        $dir = opendir($this->getSqliteDir());
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                unlink($this->getSqliteDir() . '/' . $file);
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function driverConnect()
    {
        if (!file_exists($this->getPath() . '/' . self::SQLITE_DIR)) {
            if (!mkdir($this->getPath() . '/' . self::SQLITE_DIR,
              $this->setChmodAuto())
            ) {
                $this->fallback = true;
            }
        }
        $this->SqliteDir = $this->getPath() . '/' . self::SQLITE_DIR;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function driverIsHit(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            /**
             * @todo: Check expiration time here
             */
            $stm = $this->getDb($item->getKey())
              ->prepare("SELECT COUNT(`id`) as `total` FROM `caching` WHERE (`keyword`=:keyword) AND (`exp` <= :U) ");
            $stm->execute([
              ':keyword' => $item->getKey(),
              ':U' => time(),
            ]);
            $data = $stm->fetch(PDO::FETCH_ASSOC);
            if ($data[ 'total' ] >= 1) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new \InvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return driverStatistic
     * @throws PDOException
     */
    public function getStats()
    {
        $stat = new driverStatistic();

        $res = [
          'info' => '',
          'size' => '',
          'data' => '',
        ];
        $total = 0;
        $optimized = 0;

        $dir = opendir($this->getSqliteDir());
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                $file_path = $this->getSqliteDir() . "/" . $file;
                $size = filesize($file_path);
                $total = $total + $size;

                try {
                    $PDO = new PDO("sqlite:" . $file_path);
                    $PDO->setAttribute(PDO::ATTR_ERRMODE,
                      PDO::ERRMODE_EXCEPTION);

                    $stm = $PDO->prepare("DELETE FROM `caching` WHERE `exp` <= :U");
                    $stm->execute([
                      ':U' => date('U'),
                    ]);

                    $PDO->exec('VACUUM;');
                    $size = filesize($file_path);
                    $optimized = $optimized + $size;
                } catch (PDOException $e) {
                    $size = 0;
                    $optimized = 0;
                }
            }
        }

        $stat->setSize($optimized)
            ->setInfo('Total before removing expired entries [bytes]: ' . $total . ', '
            . 'Optimized after removing expired entries [bytes]: ' . $optimized
          );

        return $stat;
    }
}