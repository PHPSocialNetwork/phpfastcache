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

namespace Phpfastcache\Drivers\Sqlite;

use PDO;
use PDOException;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\DriverBaseTrait;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\IO\IOHelperTrait;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Psr\Cache\CacheItemInterface;

/**
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use IOHelperTrait;

    protected const INDEXING_FILE = 'indexing';

    protected int $maxSize = 10;

    protected int $currentDB = 1;

    protected string $sqliteDir = '';

    protected ?PDO $indexing;

    /**
     * @return bool
     * @throws PhpfastcacheCoreException
     */
    public function driverCheck(): bool
    {
        return extension_loaded('pdo_sqlite') && (is_writable($this->getSqliteDir()) || @mkdir($this->getSqliteDir(), $this->getDefaultChmod(), true));
    }

    /**
     * @return string
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function getSqliteDir(): string
    {
        return $this->sqliteDir ?: $this->getPath();
    }

    /**
     * @return array
     */
    public function __sleep(): array
    {
        return array_diff(array_keys(get_object_vars($this)), ['indexing', 'instance']);
    }

    /**
     * @return bool
     * @throws PhpfastcacheIOException
     */
    protected function driverConnect(): bool
    {
        if (!file_exists($this->getSqliteDir()) && !@mkdir($this->getSqliteDir(), $this->getDefaultChmod(), true)) {
            throw new PhpfastcacheIOException(sprintf('Sqlite cannot write in "%s", aborting...', $this->getPath()));
        }

        $this->sqliteDir = $this->getPath();

        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        try {
            $stm = $this->getDb($item->getEncodedKey())
                ->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
            $stm->execute(
                [
                    ':keyword' => $item->getEncodedKey(),
                ]
            );
            $row = $stm->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            try {
                $stm = $this->getDb($item->getEncodedKey(), true)
                    ->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
                $stm->execute(
                    [
                        ':keyword' => $item->getEncodedKey(),
                    ]
                );
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
     * @param string $keyword
     * @param bool $reset
     * @return PDO
     */
    public function getDb(string $keyword, bool $reset = false): PDO
    {
        /**
         * Default is phpfastcache
         */
        $instant = $this->getDbIndex($keyword);

        /**
         * init instant
         */
        if (!isset($this->instance[$instant])) {
            // check DB Files ready or not
            $tableCreated = false;
            if ($reset || !file_exists($this->sqliteDir . '/db' . $instant)) {
                $tableCreated = true;
            }
            $pdo = new PDO('sqlite:' . $this->sqliteDir . '/db' . $instant);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($tableCreated) {
                $this->initDB($pdo);
            }

            $this->instance[$instant] = $pdo;
            unset($pdo);
        }

        return $this->instance[$instant];
    }

    /**
     * Return Database of Keyword
     * @param string $keyword
     * @return int
     */
    public function getDbIndex(string $keyword)
    {
        if (!isset($this->indexing)) {
            $tableCreated = false;
            if (!file_exists($this->sqliteDir . '/indexing')) {
                $tableCreated = true;
            }

            $pdo = new PDO("sqlite:" . $this->sqliteDir . '/' . self::INDEXING_FILE);
            $pdo->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            if ($tableCreated) {
                $this->initIndexing($pdo);
            }
            $this->indexing = $pdo;
            unset($pdo);

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

            $size = file_exists($this->sqliteDir . '/db' . $db) ? filesize($this->sqliteDir . '/db' . $db) : 1;
            $size = round($size / 1024 / 1024, 1);


            if ($size > $this->maxSize) {
                $db++;
            }
            $this->currentDB = $db;
        }

        // look for keyword
        $stm = $this->indexing->prepare("SELECT * FROM `balancing` WHERE `keyword`=:keyword LIMIT 1");
        $stm->execute(
            [
                ':keyword' => $keyword,
            ]
        );
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if (isset($row['db']) && $row['db'] != '') {
            $db = $row['db'];
        } else {
            /*
             * Insert new to Indexing
             */
            $db = $this->currentDB;
            $stm = $this->indexing->prepare("INSERT INTO `balancing` (`keyword`,`db`) VALUES(:keyword, :db)");
            $stm->execute(
                [
                    ':keyword' => $keyword,
                    ':db' => $db,
                ]
            );
        }

        return $db;
    }

    /**
     * INIT Indexing DB
     * @param PDO $db
     */
    public function initIndexing(PDO $db): void
    {
        // delete everything before reset indexing
        $dir = opendir($this->sqliteDir);
        while ($file = readdir($dir)) {
            if ($file !== '.' && $file !== '..' && $file !== 'indexing' && $file !== 'dbfastcache') {
                unlink($this->sqliteDir . '/' . $file);
            }
        }

        $db->exec('DROP TABLE if exists "balancing"');
        $db->exec('CREATE TABLE "balancing" ("keyword" VARCHAR PRIMARY KEY NOT NULL UNIQUE, "db" INTEGER)');
        $db->exec('CREATE INDEX "db" ON "balancing" ("db")');
        $db->exec('CREATE UNIQUE INDEX "lookup" ON "balancing" ("keyword")');
    }

    /**
     * INIT NEW DB
     * @param PDO $db
     */
    protected function initDB(PDO $db): void
    {
        $db->exec('drop table if exists "caching"');
        $db->exec('CREATE TABLE "caching" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "keyword" VARCHAR UNIQUE, "object" BLOB, "exp" INTEGER)');
        $db->exec('CREATE UNIQUE INDEX "cleanup" ON "caching" ("keyword","exp")');
        $db->exec('CREATE INDEX "exp" ON "caching" ("exp")');
        $db->exec('CREATE UNIQUE INDEX "keyword" ON "caching" ("keyword")');
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        try {
            $stm = $this->getDb($item->getEncodedKey())
                ->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
            $stm->execute(
                [
                    ':keyword' => $item->getEncodedKey(),
                    ':object' => $this->encode($this->driverPreWrap($item)),
                    ':exp' => $item->getExpirationDate()->getTimestamp(),
                ]
            );

            return true;
        } catch (PDOException $e) {
            return false;
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
            $stm = $this->getDb($item->getEncodedKey())
                ->prepare("DELETE FROM `caching` WHERE (`exp` <= :exp) OR (`keyword`=:keyword) ");

            return $stm->execute(
                [
                    ':keyword' => $item->getEncodedKey(),
                    ':exp' => time(),
                ]
            );
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @return bool
     * @throws PhpfastcacheCoreException
     */
    protected function driverClear(): bool
    {
        $this->instance = [];
        $this->indexing = null;

        // delete everything before reset indexing
        $dir = opendir($this->getSqliteDir());
        while ($file = readdir($dir)) {
            if ($file !== '.' && $file !== '..') {
                unlink($this->getSqliteDir() . '/' . $file);
            }
        }

        return true;
    }
}
