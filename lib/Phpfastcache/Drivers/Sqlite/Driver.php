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

namespace Phpfastcache\Drivers\Sqlite;

use PDO;
use PDOException;
use Phpfastcache\Core\Pool\{
    DriverBaseTrait, ExtendedCacheItemPoolInterface, IO\IOHelperTrait
};
use Phpfastcache\Exceptions\{
    PhpfastcacheInvalidArgumentException, PhpfastcacheIOException
};
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property Config $config Config object
 * @method Config getConfig() Return the config object
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use DriverBaseTrait, IOHelperTrait;

    /**
     *
     */
    const FILE_DIR = 'sqlite';
    /**
     *
     */
    const INDEXING_FILE = 'indexing';

    /**
     * @var int
     */
    protected $maxSize = 10; // 10 mb

    /**
     * @var int
     */
    protected $currentDB = 1;

    /**
     * @var string
     */
    protected $SqliteDir = '';

    /**
     * @var \PDO
     */
    protected $indexing;


    /**
     * @return string
     * @throws \Phpfastcache\Exceptions\PhpfastcacheCoreException
     */
    public function getSqliteDir(): string
    {
        return $this->SqliteDir ?: $this->getPath() . \DIRECTORY_SEPARATOR . self::FILE_DIR;
    }

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return \extension_loaded('pdo_sqlite') && (\is_writable($this->getSqliteDir()) || @mkdir($this->getSqliteDir(), $this->getDefaultChmod(), true));
    }

    /**
     * @return bool
     * @throws PhpfastcacheIOException
     */
    protected function driverConnect(): bool
    {
        if (!\file_exists($this->getSqliteDir()) && !@mkdir($this->getSqliteDir(), $this->getDefaultChmod(), true)) {
            throw new PhpfastcacheIOException(\sprintf('Sqlite cannot write in "%s", aborting...', $this->getPath()));
        }
        if (!\file_exists($this->getPath() . '/' . self::FILE_DIR)) {
            if (!mkdir($this->getPath() . '/' . self::FILE_DIR, $this->getDefaultChmod(), true)
            ) {
                $this->fallback = true;
            }
        }
        $this->SqliteDir = $this->getPath() . '/' . self::FILE_DIR;

        return true;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        try {
            $stm = $this->getDb($item->getKey())
                ->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
            $stm->execute([
                ':keyword' => $item->getKey(),
            ]);
            $row = $stm->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            try {
                $stm = $this->getDb($item->getKey(), true)
                    ->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
                $stm->execute([
                    ':keyword' => $item->getKey(),
                ]);
                $row = $stm->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return null;
            }
        }

        if (isset($row['object'])) {
            return $this->decode($row['object']);
        }

        return null;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item): bool
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $stm = $this->getDb($item->getKey())
                    ->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
                $stm->execute([
                    ':keyword' => $item->getKey(),
                    ':object' => $this->encode($this->driverPreWrap($item)),
                    ':exp' => $item->getExpirationDate()->getTimestamp(),
                ]);

                return true;
            } catch (\PDOException $e) {
                return false;
            }
        }

        throw new PhpfastcacheInvalidArgumentException('Cross-Driver type confusion detected');
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
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
                $stm = $this->getDb($item->getKey())
                    ->prepare("DELETE FROM `caching` WHERE (`exp` <= :U) OR (`keyword`=:keyword) ");

                return $stm->execute([
                    ':keyword' => $item->getKey(),
                    ':U' => \time(),
                ]);
            } catch (PDOException $e) {
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
        $this->instance = [];
        $this->indexing = null;

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

        $db->exec('DROP TABLE if exists "balancing"');
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
            $tableCreated = false;
            if (!\file_exists($this->SqliteDir . '/indexing')) {
                $tableCreated = true;
            }

            $PDO = new PDO("sqlite:" . $this->SqliteDir . '/' . self::INDEXING_FILE);
            $PDO->setAttribute(PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION);

            if ($tableCreated) {
                $this->initIndexing($PDO);
            }
            $this->indexing = $PDO;
            unset($PDO);

            $stm = $this->indexing->prepare("SELECT MAX(`db`) as `db` FROM `balancing`");
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            if (!isset($row['db'])) {
                $db = 1;
            } elseif ($row['db'] <= 1) {
                $db = 1;
            } else {
                $db = $row['db'];
            }

            // check file size

            $size = \file_exists($this->SqliteDir . '/db' . $db) ? filesize($this->SqliteDir . '/db' . $db) : 1;
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
        if (isset($row['db']) && $row['db'] != '') {
            $db = $row['db'];
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
     * @param string $keyword
     * @param bool $reset
     * @return PDO
     */
    public function getDb(string $keyword, bool $reset = false): PDO
    {
        /**
         * Default is phpfastcache
         */
        $instant = $this->indexing($keyword);

        /**
         * init instant
         */
        if (!isset($this->instance[$instant])) {
            // check DB Files ready or not
            $tableCreated = false;
            if ($reset || !\file_exists($this->SqliteDir . '/db' . $instant)) {
                $tableCreated = true;
            }
            $PDO = new PDO('sqlite:' . $this->SqliteDir . '/db' . $instant);
            $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($tableCreated) {
                $this->initDB($PDO);
            }

            $this->instance[$instant] = $PDO;
            unset($PDO);

        }

        return $this->instance[$instant];
    }

    /**
     * @return array
     */
    public function __sleep(): array
    {
        return \array_diff(\array_keys(\get_object_vars($this)), ['indexing', 'instance']);
    }
}