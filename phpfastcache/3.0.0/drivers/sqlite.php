<?php

/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */


class phpfastcache_sqlite extends BasePhpFastCache implements phpfastcache_driver  {
    const SQLITE_DIR = 'sqlite';
    const INDEXING_FILE = 'indexing';

    var $max_size = 10; // 10 mb

    var $instant = array();
    var $indexing = NULL;
    var $path = "";

    var $currentDB = 1;

    /*
     * INIT NEW DB
     */
    function initDB(PDO $db) {
        $db->exec('drop table if exists "caching"');
        $db->exec('CREATE TABLE "caching" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "keyword" VARCHAR UNIQUE, "object" BLOB, "exp" INTEGER)');
        $db->exec('CREATE UNIQUE INDEX "cleanup" ON "caching" ("keyword","exp")');
        $db->exec('CREATE INDEX "exp" ON "caching" ("exp")');
        $db->exec('CREATE UNIQUE INDEX "keyword" ON "caching" ("keyword")');
    }

    /*
     * INIT Indexing DB
     */
    function initIndexing(PDO $db) {

        // delete everything before reset indexing
        $dir = @opendir($this->path);
        while($file = @readdir($dir)) {
            if($file!="." && $file!=".." && $file!=self::INDEXING_FILE && $file!="dbfastcache") {
                @unlink($this->path."/".$file);
            }
        }

        $db->exec('drop table if exists "balancing"');
        $db->exec('CREATE TABLE "balancing" ("keyword" VARCHAR PRIMARY KEY NOT NULL UNIQUE, "db" INTEGER)');
        $db->exec('CREATE INDEX "db" ON "balancing" ("db")');
        $db->exec('CREATE UNIQUE INDEX "lookup" ON "balancing" ("keyword")');

    }

    /*
     * INIT Instant DB
     * Return Database of Keyword
     */
    function indexing($keyword) {
        if($this->indexing == NULL) {
            $createTable = false;
            if(!@file_exists($this->path."/".self::INDEXING_FILE)) {
                $createTable = true;
            }

            $PDO = new PDO("sqlite:".$this->path."/".self::INDEXING_FILE);
            $PDO->setAttribute(PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION);

            if($createTable == true) {
                $this->initIndexing($PDO);
            }
            $this->indexing = $PDO;
            unset($PDO);

            $stm = $this->indexing->prepare("SELECT MAX(`db`) as `db` FROM `balancing`");
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            if(!isset($row['db'])) {
                $db = 1;
            } elseif($row['db'] <=1 ) {
                $db = 1;
            } else {
                $db = $row['db'];
            }

            // check file size

            $size = @file_exists($this->path."/db".$db) ? @filesize($this->path."/db".$db) : 1;
            $size = round($size / 1024 / 1024,1);


            if($size > $this->max_size) {
                $db = $db + 1;
            }
            $this->currentDB = $db;

        }

        // look for keyword
        $stm = $this->indexing->prepare("SELECT * FROM `balancing` WHERE `keyword`=:keyword LIMIT 1");
        $stm->execute(array(
             ":keyword"  => $keyword
        ));
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if(isset($row['db']) && $row['db'] != "") {
            $db = $row['db'];
        } else {
            /*
             * Insert new to Indexing
             */
            $db = $this->currentDB;
            $stm = $this->indexing->prepare("INSERT INTO `balancing` (`keyword`,`db`) VALUES(:keyword, :db)");
            $stm->execute(array(
                ":keyword"  => $keyword,
                ":db"       =>  $db,
            ));
        }

        return $db;
    }

    function db($keyword, $reset = false) {
        /*
         * Default is fastcache
         */
        $instant = $this->indexing($keyword);

        /*
         * init instant
         */
        if(!isset($this->instant[$instant])) {
            // check DB Files ready or not
            $createTable = false;
            if(!@file_exists($this->path."/db".$instant) || $reset == true) {
                $createTable = true;
            }
            $PDO = new PDO("sqlite:".$this->path."/db".$instant);
            $PDO->setAttribute(PDO::ATTR_ERRMODE,
                               PDO::ERRMODE_EXCEPTION);

            if($createTable == true) {
                $this->initDB($PDO);
            }

            $this->instant[$instant] = $PDO;
            unset($PDO);

        }

        return $this->instant[$instant];
    }

    function checkdriver() {
        if(extension_loaded('pdo_sqlite') && is_writeable($this->getPath())) {
           return true;
        }
        $this->fallback = true;
        return false;
    }

    /*
     * Init Main Database & Sub Database
     */
    function __construct($config = array()) {
        /*
         * init the path
         */
        $this->setup($config);
        if(!$this->checkdriver() && !isset($config['skipError'])) {
            $this->fallback = true;
        }

        if(!@file_exists($this->getPath()."/".self::SQLITE_DIR)) {
            if(!@mkdir($this->getPath()."/".self::SQLITE_DIR,$this->__setChmodAuto())) {
                $this->fallback = true;
            }
        }
        $this->path = $this->getPath()."/".self::SQLITE_DIR;
    }


    function driver_set($keyword, $value = "", $time = 300, $option = array() ) {
        $skipExisting = isset($option['skipExisting']) ? $option['skipExisting'] : false;
        $toWrite = true;

        // check in cache first
        $in_cache = $this->get($keyword,$option);

        if($skipExisting == true) {
            if($in_cache == null) {
                $toWrite = true;
            } else {
                $toWrite = false;
            }
        }

        if($toWrite == true) {
            try {
                $stm = $this->db($keyword)->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
                $stm->execute(array(
                    ":keyword"  => $keyword,
                    ":object"   =>  $this->encode($value),
                    ":exp"      => time() + (Int)$time,
                ));

                return true;
            } catch(PDOException $e) {

                try {
                    $stm = $this->db($keyword,true)->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
                    $stm->execute(array(
                        ":keyword"  => $keyword,
                        ":object"   =>  $this->encode($value),
                        ":exp"      => time() + (Int)$time,
                    ));
                } catch (PDOException $e) {
                    return false;
                }

            }


        }

        return false;

    }

    function driver_get($keyword, $option = array()) {
        // return null if no caching
        // return value if in caching
        try {
            $stm = $this->db($keyword)->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
            $stm->execute(array(
                ":keyword"  =>  $keyword
            ));
            $row = $stm->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            try {
                $stm = $this->db($keyword,true)->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
                $stm->execute(array(
                    ":keyword"  =>  $keyword
                ));
                $row = $stm->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                return null;
            }

        }

        if($this->isExpired($row)) {
            $this->deleteRow($row);
            return null;
        }

        if(isset($row['id'])) {
            $data = $this->decode($row['object']);
            return $data;
        }

        return null;
    }

    function isExpired($row) {
        if(isset($row['exp']) && time() >= $row['exp']) {
            return true;
        }

        return false;
    }

    function deleteRow($row) {
        try {
            $stm = $this->db($row['keyword'])->prepare("DELETE FROM `caching` WHERE (`id`=:id) OR (`exp` <= :U) ");
            $stm->execute(array(
                ":id"   => $row['id'],
                ":U"    =>  time(),
            ));
        } catch (PDOException $e) {
            return false;
        }
    }

    function driver_delete($keyword, $option = array()) {
        try {
            $stm = $this->db($keyword)->prepare("DELETE FROM `caching` WHERE (`keyword`=:keyword) OR (`exp` <= :U)");
            $stm->execute(array(
                ":keyword"   => $keyword,
                ":U"    =>  time(),
            ));
        } catch (PDOException $e) {
            return false;
        }


    }

    /*
     * Return total cache size + auto removed expired entries
     */
    function driver_stats($option = array()) {
        $res = array(
            "info"  =>  "",
            "size"  =>  "",
            "data"  =>  "",
        );
        $total = 0;
        $optimized = 0;

        $dir = @opendir($this->path);
        while($file = @readdir($dir)) {
            if($file!="." && $file!="..") {
                $file_path = $this->path."/".$file;
                $size = @filesize($file_path);
                $total += $size;
                if ($file!=self::INDEXING_FILE) {
                    try {
                        $PDO = new PDO("sqlite:".$file_path);
                        $PDO->setAttribute(PDO::ATTR_ERRMODE,
                            PDO::ERRMODE_EXCEPTION);

                        $stm = $PDO->prepare("DELETE FROM `caching` WHERE `exp` <= :U");
                        $stm->execute(array(
                            ":U"    =>  time(),
                        ));

                        $PDO->exec("VACUUM;");
                        $size = @filesize($file_path);
                    } catch (PDOException $e) {
                        $res['data'] .= sprintf("%s: %s\n", $file_path, $e->getMessage());
                    }
                }
                $optimized += $size;
            }
        }
        $res['size'] = $optimized;
        $res['info'] = array(
            "total before removing expired entries [bytes]" => $total,
            "optimized after removing expired entries [bytes]" => $optimized,
        );

        return $res;
    }

    function driver_clean($option = array()) {

        // close connection
        $this->instant = array();
        $this->indexing = NULL;

        // delete everything
        $dir = @opendir($this->path);
        while($file = @readdir($dir)) {
            if($file != "." && $file!="..") {
                @unlink($this->path."/".$file);
            }
        }
    }

    function driver_isExisting($keyword) {
        try {
            $stm = $this->db($keyword)->prepare("SELECT COUNT(`id`) as `total` FROM `caching` WHERE `keyword`=:keyword");
            $stm->execute(array(
                ":keyword"   => $keyword
            ));
            $data = $stm->fetch(PDO::FETCH_ASSOC);
            if($data['total'] >= 1) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }
}
