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

namespace phpFastCache\Drivers;

use phpFastCache\Core\DriverAbstract;
use PDO;
use PDOException;
use phpFastCache\Exceptions\phpFastCacheDriverException;

/**
 * Class sqlite
 * @package phpFastCache\Drivers
 */
class sqlite extends DriverAbstract
{
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
    public $max_size = 10; // 10 mb

    /**
     * @var array
     */
    public $instant = array();
    /**
     * @var null
     */
    public $indexing = null;
    /**
     * @var string
     */
    public $path = '';

    /**
     * @var int
     */
    public $currentDB = 1;

    /**
     * Init Main Database & Sub Database
     * phpFastCache_sqlite constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct($config = array())
    {
        /**
         * init the path
         */
        $this->setup($config);
        if (!$this->checkdriver()) {
            throw new phpFastCacheDriverException('SQLITE is not installed, cannot continue.');
        }

        if (!file_exists($this->getPath() . '/' . self::SQLITE_DIR)) {
            if (!mkdir($this->getPath() . '/' . self::SQLITE_DIR,
              $this->__setChmodAuto())
            ) {
                $this->fallback = true;
            }
        }
        $this->path = $this->getPath() . '/' . self::SQLITE_DIR;
    }

    /**
     * INIT NEW DB
     * @param \PDO $db
     */
    public function initDB(PDO $db)
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
    public function initIndexing(PDO $db)
    {

        // delete everything before reset indexing
        $dir = opendir($this->path);
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && $file != 'indexing' && $file != 'dbfastcache') {
                unlink($this->path . '/' . $file);
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
            if (!file_exists($this->path . '/indexing')) {
                $createTable = true;
            }

            $PDO = new PDO("sqlite:" . $this->path . '/' . self::INDEXING_FILE);
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

            $size = file_exists($this->path . '/db' . $db) ? filesize($this->path . '/db' . $db) : 1;
            $size = round($size / 1024 / 1024, 1);


            if ($size > $this->max_size) {
                $db = $db + 1;
            }
            $this->currentDB = $db;

        }

        // look for keyword
        $stm = $this->indexing->prepare("SELECT * FROM `balancing` WHERE `keyword`=:keyword LIMIT 1");
        $stm->execute(array(
          ':keyword' => $keyword,
        ));
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if (isset($row[ 'db' ]) && $row[ 'db' ] != '') {
            $db = $row[ 'db' ];
        } else {
            /*
             * Insert new to Indexing
             */
            $db = $this->currentDB;
            $stm = $this->indexing->prepare("INSERT INTO `balancing` (`keyword`,`db`) VALUES(:keyword, :db)");
            $stm->execute(array(
              ':keyword' => $keyword,
              ':db' => $db,
            ));
        }

        return $db;
    }

    /**
     * @param $keyword
     * @param bool $reset
     * @return mixed
     */
    public function db($keyword, $reset = false)
    {
        /**
         * Default is fastcache
         */
        $instant = $this->indexing($keyword);

        /**
         * init instant
         */
        if (!isset($this->instant[ $instant ])) {
            // check DB Files ready or not
            $createTable = false;
            if (!file_exists($this->path . '/db' . $instant) || $reset == true) {
                $createTable = true;
            }
            $PDO = new PDO('sqlite:' . $this->path . '/db' . $instant);
            $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($createTable == true) {
                $this->initDB($PDO);
            }

            $this->instant[ $instant ] = $PDO;
            unset($PDO);

        }

        return $this->instant[ $instant ];
    }

    /**
     * @return bool
     */
    public function checkdriver()
    {
        if (extension_loaded('pdo_sqlite') && is_writable($this->getPath())) {
            return true;
        }
        $this->fallback = true;
        return false;
    }


    /**
     * @param $keyword
     * @param string $value
     * @param int $time
     * @param array $option
     * @return bool
     */
    public function driver_set(
      $keyword,
      $value = '',
      $time = 300,
      $option = array()
    ) {
        $skipExisting = isset($option[ 'skipExisting' ]) ? $option[ 'skipExisting' ] : false;
        $toWrite = true;

        // check in cache first
        $in_cache = $this->get($keyword, $option);

        if ($skipExisting == true) {
            if ($in_cache == null) {
                $toWrite = true;
            } else {
                $toWrite = false;
            }
        }

        if ($toWrite == true) {
            try {
                $stm = $this->db($keyword)
                  ->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
                $stm->execute(array(
                  ':keyword' => $keyword,
                  ':object' => $this->encode($value),
                  ':exp' => time() + (int)$time,
                ));

                return true;
            } catch (\PDOException $e) {

                try {
                    $stm = $this->db($keyword, true)
                      ->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
                    $stm->execute(array(
                      ':keyword' => $keyword,
                      ':object' => $this->encode($value),
                      ':exp' => time() + (int)$time,
                    ));
                } catch (PDOException $e) {
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * @param $keyword
     * @param array $option
     * @return mixed|null
     */
    public function driver_get($keyword, $option = array())
    {
        // return null if no caching
        // return value if in caching
        try {
            $stm = $this->db($keyword)
              ->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
            $stm->execute(array(
              ':keyword' => $keyword,
            ));
            $row = $stm->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            try {
                $stm = $this->db($keyword, true)
                  ->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
                $stm->execute(array(
                  ':keyword' => $keyword,
                ));
                $row = $stm->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return null;
            }

        }

        if ($this->isExpired($row)) {
            $this->deleteRow($row);
            return null;
        }

        if (isset($row[ 'id' ])) {
            $data = $this->decode($row[ 'object' ]);
            return $data;
        }

        return null;
    }

    /**
     * @param $row
     * @return bool
     */
    public function isExpired($row)
    {
        if (isset($row[ 'exp' ]) && time() >= $row[ 'exp' ]) {
            return true;
        }

        return false;
    }

    /**
     * @param $row
     * @return bool
     */
    public function deleteRow($row)
    {
        try {
            $stm = $this->db($row[ 'keyword' ])
              ->prepare("DELETE FROM `caching` WHERE (`id`=:id) OR (`exp` <= :U) ");
            $stm->execute(array(
              ':id' => $row[ 'id' ],
              ':U' => time(),
            ));
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @param $keyword
     * @param array $option
     * @return bool
     */
    public function driver_delete($keyword, $option = array())
    {
        try {
            $stm = $this->db($keyword)
              ->prepare("DELETE FROM `caching` WHERE (`keyword`=:keyword) OR (`exp` <= :U)");
            $stm->execute(array(
              ':keyword' => $keyword,
              ':U' => time(),
            ));
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Return total cache size + auto removed expired entries
     * @param array $option
     * @return array
     */
    public function driver_stats($option = array())
    {
        $res = array(
          'info' => '',
          'size' => '',
          'data' => '',
        );
        $total = 0;
        $optimized = 0;

        $dir = opendir($this->path);
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                $file_path = $this->path . "/" . $file;
                $size = filesize($file_path);
                $total = $total + $size;

                try {
                    $PDO = new PDO("sqlite:" . $file_path);
                    $PDO->setAttribute(PDO::ATTR_ERRMODE,
                      PDO::ERRMODE_EXCEPTION);

                    $stm = $PDO->prepare("DELETE FROM `caching` WHERE `exp` <= :U");
                    $stm->execute(array(
                      ':U' => date('U'),
                    ));

                    $PDO->exec('VACUUM;');
                    $size = filesize($file_path);
                    $optimized = $optimized + $size;
                } catch (PDOException $e) {
                    $size = 0;
                    $optimized = 0;
                }


            }
        }
        $res[ 'size' ] = $optimized;
        $res[ 'info' ] = array(
          'total before removing expired entries [bytes]' => $total,
          'optimized after removing expired entries [bytes]' => $optimized,
        );

        return $res;
    }

    /**
     * @param array $option
     * @return void
     */
    public function driver_clean($option = array())
    {
        // close connection
        $this->instant = array();
        $this->indexing = null;

        // delete everything before reset indexing
        $dir = opendir($this->path);
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                unlink($this->path . '/' . $file);
            }
        }
    }

    /**
     * @param $keyword
     * @return bool
     */
    public function driver_isExisting($keyword)
    {
        try {
            $stm = $this->db($keyword)
              ->prepare("SELECT COUNT(`id`) as `total` FROM `caching` WHERE `keyword`=:keyword");
            $stm->execute(array(
              ':keyword' => $keyword,
            ));
            $data = $stm->fetch(PDO::FETCH_ASSOC);
            if ($data[ 'total' ] >= 1) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }
}
