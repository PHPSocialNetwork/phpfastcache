<?php
    // phpFastCache
    // Author: Khoa Bui
    // E-mail: khoaofgod@yahoo.com
    // Website: http://www.phpfastcache.com
    // PHP Fast Cache is simple caching build on PDO
/*
 * Example:
 * phpFastCache::$storage = "auto"; // use multi files for caching.
 * phpFastCache::$autosize = 40; megabytes size for each cache file.
 * phpFastCache::$path  = "/PATH/TO/CACHE/FOLDER";
 *
 * phpFastCache::set("keyword", $value, $time_in_second);
 * $value = phpFastCache::get("keyword");
 *
 */

    class phpFastCache {
        private static $pdo = "";
        public static $options = array();
        public static $storage = "single"; // single | auto
        public static $autosize = 40; // Megabytes
        public static $path = "";
        private static $filename = "caching.0777";

        private static $table = "objects";
        private static $autodb = "";
        private static $multiPDO = array();

        private static function selectDB($object) {
            $res = array(
                'db'    => "",
                'item'  => "",
            );
            if(is_array($object)) {
                $key = array_keys($object);
                $key = $key[0];
                $res['db'] = $key;
                $res['item'] = self::safename($object[$key]);
            } else {
                $res['item'] = self::safename($object);
            }

            // for auto database
            if($res['db'] == "" && self::$storage!= "single") {
                $create_table = false;
                if(!file_exists('sqlite:'.self::getPath().'/phpfastcache.c')) {
                    $create_table = true;
                }
                if(self::$autodb == "") {
                    try {
                        self::$autodb = new PDO('sqlite:'.self::getPath().'/phpfastcache.c');
                        self::$autodb->setAttribute(PDO::ATTR_ERRMODE,
                            PDO::ERRMODE_EXCEPTION);

                    } catch (PDOException $e) {
                        die("Please CHMOD 0777 or Writable Permission for ".self::getPath());
                    }

                }

                if($create_table == true) {
                    self::$autodb->exec('CREATE TABLE IF NOT EXISTS "main"."db" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL  UNIQUE , "item" VARCHAR NOT NULL  UNIQUE , "dbname" INTEGER NOT NULL )');
                }

                $db = self::$autodb->prepare("SELECT * FROM `db` WHERE `item`=:item");
                $db->execute(array(
                    ":item" => $res['item'],
                ));
                $row = $db->fetch(PDO::FETCH_ASSOC);
                if(isset($row['dbname'])) {
                    // found key
                    $res['db'] = $row['dbname'];
                } else {
                    // not key // check filesize
                    if((Int)self::$autosize < 10) {
                        self::$autosize = 10;
                    }
                    // get last key
                    $db = self::$autodb->prepare("SELECT * FROM `db` ORDER BY `id` DESC");
                    $db->execute();
                    $row = $db->fetch(PDO::FETCH_ASSOC);
                    $dbname = isset($row['dbname']) ? $row['dbname'] : 1;
                    $fsize = file_exists(self::getPath()."/".$dbname.".cache") ? filesize(self::getPath()."/".$dbname.".cache") : 0;
                    if($fsize > (1024*1024*(Int)self::$autosize)) {
                        $dbname = (Int)$dbname + 1;
                    }
                    try {
                        $insert = self::$autodb->prepare("INSERT INTO `db` (`item`,`dbname`) VALUES(:item,:dbname)");
                        $insert->execute(array(
                            ":item" => $res['item'],
                            ":dbname"   => $dbname
                        ));
                    } catch (PDOException $e) {
                        die('Database Error - Check A look at self::$autodb->prepare("INSERT INTO ');
                    }

                    $res['db'] = $dbname;

                }
            }

            return $res;

        }

        public static function delete($name = "string|array(db->item)") {
            $db = self::selectDB($name);
            $name = $db['item'];

            self::db(array('db'=>$db['db']))->exec("DELETE FROM ".self::$table." WHERE `name`='".$name."'");
        }

        public static function resetAll() {
            if(self::$storage == "single") {
                self::db(array("skip_clean" => true))->exec("drop table if exists ".self::$table);
                self::initDatabase();
            } else {
                $dir = opendir(self::getPath());
                while($file = readdir($dir)) {
                    if(strpos($file,".cache") !== false) {
                        @unlink(self::getPath()."/".$file);
                    }
                }
            }


        }

        public static function showStats($full = false) {
            $res = array();
            if($full == true) {
                $stm = self::db()->prepare("SELECT * FROM ".self::$table."");
                $stm->execute();
                $result = $stm->fetchAll();
                $res['data'] = $result;
            }
            $stm = self::db()->prepare("SELECT COUNT(*) as `total` FROM ".self::$table."");
            $stm->execute();
            $result = $stm->fetch();
            $res['record'] = $result['total'];
            if(self::$path!="memory") {
                $res['size'] = filesize(self::getPath()."/".self::$filename);
            }

            return $res;
        }

        public static function increment($name ,$step = 1) {
            $db = self::selectDB($name);
            $name = $db['item'];
            // array('db'=>$db['db'])

            $int = self::get($name);
           // echo $int."xxx";
            try {
                $stm = self::db(array('db'=>$db['db']))->prepare("UPDATE ".self::$table." SET `value`=:new WHERE `name`=:name ");
                $stm->execute(array(
                    ":new" => $int + $step,
                    ":name" =>  $name,
                ));

            } catch (PDOException $e) {
                die("Sorry! phpFastCache don't allow this type of value - Name: ".$name." -> Increment: ".$step);
            }
            return $int + $step;

        }

        private static function safename($name) {
            return strtolower(preg_replace("/[^a-zA-Z0-9_\s\.]+/","",$name));

        }

        public static function decrement($name, $step = 1) {
            $db = self::selectDB($name);
            $name = $db['item'];
            // array('db'=>$db['db'])

            $int = self::get($name);
            try {
                $stm = self::db(array('db'=>$db['db']))->prepare("UPDATE ".self::$table." SET `value`=:new WHERE `name`=:name ");
                $stm->execute(array(
                    ":new"  => $int - $step,
                    ":name" =>  $name,
                ));

            } catch (PDOException $e) {
                die("Sorry! phpFastCache don't allow this type of value - Name: ".$name." -> Decrement: ".$step);
            }
            return $int - $step;

        }

        public static function get($name) {
            $db = self::selectDB($name);
            $name = $db['item'];
            // array('db'=>$db['db'])
            $stm = self::db(array('db'=>$db['db']))->prepare("SELECT * FROM ".self::$table." WHERE `name`='".$name."'");
            $stm->execute();
            $res = $stm->fetch(PDO::FETCH_ASSOC);

            if(!isset($res['value'])) {
                return null;
            } else {
                return self::decode($res['value']);
            }
        }

        private static function encode($value) {
            $value = serialize($value);
            return $value;
        }

        private static function decode($value) {
            $x = @unserialize($value);
            if($x == false) {
                return $value;
            } else {
                return $x;
            }
        }

        public static function set($name,$value,$time_in_second = 600, $skip_if_existing = false) {
            $db = self::selectDB($name);
            $name = $db['item'];
            // array('db'=>$db['db'])

            if($skip_if_existing == true) {
                try {
                    $insert = self::db(array('db'=>$db['db']))->prepare("INSERT OR IGNORE INTO ".self::$table." (name,value,added,endin) VALUES(:name,:value,:added,:endin)");
                    try {
                        $value = self::encode($value);
                    } catch(Exception $e) {
                        die("Sorry! phpFastCache don't allow this type of value - Name: ".$name);
                    }

                    $insert->execute(array(
                        ":name"  => $name,
                        ":value"    => $value,
                        ":added"    => @date("U"),
                        ":endin"  =>  (Int)$time_in_second
                    ));

                    return true;
                } catch (PDOException $e) {
                    return false;
                }

            } else {
                try {
                    $insert = self::db(array('db'=>$db['db']))->prepare("INSERT OR REPLACE INTO ".self::$table." (name,value,added,endin) VALUES(:name,:value,:added,:endin)");
                    try {
                        $value = self::encode($value);
                    } catch(Exception $e) {
                        die("Sorry! phpFastCache don't allow this type of value - Name: ".$name);
                    }

                    $insert->execute(array(
                        ":name"  => $name,
                        ":value"    => $value,
                        ":added"    => @date("U"),
                        ":endin"  =>  (Int)$time_in_second
                    ));

                    return true;
                } catch (PDOException $e) {
                    return false;
                }
            }

        }

        private static function getPath() {
            if(self::$path == "") {
                self::$path = dirname(__FILE__);
            }

            if(self::$path == "memory") {
                self::$path = dirname(__FILE__);
            }

            if(self::$storage != "single") {

                if(!file_exists(self::$path."/cache.storage/") || !is_writable(self::$path."/cache.storage/")) {
                    if(!file_exists(self::$path."/cache.storage/")) {
                        @mkdir(self::$path."/cache.storage/",0777);
                    }
                    if(!is_writable(self::$path."/cache.storage/")) {
                        @chmod(self::$path."/cache.storage/",0777);
                    }
                    if(!file_exists(self::$path."/cache.storage/") || !is_writable(self::$path."/cache.storage/")) {
                        die("Sorry, Please create ".self::$path."/cache.storage/ and SET Mode 0777 or any Writable Permission!" );
                    }

                }
                return self::$path."/cache.storage/";
            } else {
                return self::$path;
            }



        }

        private static function db($option = array()) {
            $vacuum = false;
            $dbname = isset($option['db']) ? $option['db'] : "";
            $dbname = $dbname != "" ? $dbname : self::$filename;
            if($dbname!=self::$filename) {
                $dbname = $dbname.".cache";
            }



            $initDB = false;

            if(self::$storage == "single") {
                // start self PDO
                if(self::$pdo=="") {
                  //  self::$pdo == new PDO("sqlite:".self::$path."/cachedb.sqlite");
                    if(self::$path!="memory") {
                        if(!file_exists(self::getPath()."/".$dbname)) {
                            $initDB = true;
                        } else {
                            if(!is_writable(self::getPath()."/".$dbname)) {
                                    @chmod(self::getPath()."/".$dbname,0777);
                                    if(!is_writable(self::getPath()."/".$dbname)) {
                                        die("Please CHMOD 0777 or any Writable Permission for ".self::getPath()."/".$dbname);
                                    }
                            }
                        }



                        try {
                            self::$pdo = new PDO("sqlite:".self::getPath()."/".$dbname);
                            self::$pdo->setAttribute(PDO::ATTR_ERRMODE,
                                PDO::ERRMODE_EXCEPTION);

                            if($initDB == true) {
                                self::initDatabase();
                            }

                            $time = filemtime(self::getPath()."/".$dbname);
                            if($time + (3600*48) < @date("U")) {
                                $vacuum = true;
                            }



                        } catch (PDOException $e) {
                            die("Can't connect to caching file ".self::getPath()."/".$dbname);
                        }


                    } else {
                        self::$pdo = new PDO("sqlite::memory:");
                        self::$pdo->setAttribute(PDO::ATTR_ERRMODE,
                            PDO::ERRMODE_EXCEPTION);

                        self::initDatabase();
                    }

                    // remove old cache
                    if(!isset($option['skip_clean'])) {

                        try {
                            self::$pdo->exec("DELETE FROM ".self::$table." WHERE (`added` + `endin`) < ".@date("U"));
                        } catch(PDOException $e) {
                            die("Please re-upload the caching file ".$dbname." and chmod it 0777 or Writable permission!");
                        }
                    }

                    // auto Vaccuum() every 48 hours
                    if($vacuum == true) {
                        self::$pdo->exec('VACUUM');
                    }


                    return self::$pdo;

                } else {
                    return self::$pdo;
                }
                // end self pdo

            } else {

                // start self PDO
                if(!isset(self::$multiPDO[$dbname])) {
                    //  self::$pdo == new PDO("sqlite:".self::$path."/cachedb.sqlite");
                    if(self::$path!="memory") {
                        if(!file_exists(self::getPath()."/".$dbname)) {
                            $initDB = true;
                        } else {
                            if(!is_writable(self::getPath()."/".$dbname)) {
                                    @chmod(self::getPath()."/".$dbname,0777);
                                    if(!is_writable(self::getPath()."/".$dbname)) {
                                        die("Please CHMOD 0777 or any Writable Permission for PATH ".self::getPath());
                                    }
                            }
                        }



                        try {
                            self::$multiPDO[$dbname] = new PDO("sqlite:".self::getPath()."/".$dbname);
                            self::$multiPDO[$dbname]->setAttribute(PDO::ATTR_ERRMODE,
                                PDO::ERRMODE_EXCEPTION);

                            if($initDB == true) {
                                self::initDatabase(self::$multiPDO[$dbname]);
                            }

                            $time = filemtime(self::getPath()."/".$dbname);
                            if($time + (3600*48) < @date("U")) {
                                $vacuum = true;
                            }



                        } catch (PDOException $e) {
                            die("Can't connect to caching file ".self::getPath()."/".$dbname);
                        }


                    }

                    // remove old cache
                    if(!isset($option['skip_clean'])) {
                        try {
                            self::$multiPDO[$dbname]->exec("DELETE FROM ".self::$table." WHERE (`added` + `endin`) < ".@date("U"));
                        } catch(PDOException $e) {
                            die("Please re-upload the caching file ".$dbname." and chmod it 0777 or Writable permission!");
                        }
                    }

                    // auto Vaccuum() every 48 hours
                    if($vacuum == true) {
                        self::$multiPDO[$dbname]->exec('VACUUM');
                    }


                    return self::$multiPDO[$dbname];

                } else {
                    return self::$multiPDO[$dbname];
                }
                // end self pdo

            }





        }

        private static function initDatabase($object = null) {
            if($object == null) {
                self::db(array("skip_clean" => true))->exec('CREATE TABLE IF NOT EXISTS "'.self::$table.'" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL  UNIQUE , "name" VARCHAR UNIQUE NOT NULL  , "value" BLOB, "added" INTEGER NOT NULL  DEFAULT 0, "endin" INTEGER NOT NULL  DEFAULT 0)');
                self::db(array("skip_clean" => true))->exec('CREATE INDEX "lookup" ON "'.self::$table.'" ("added" ASC, "endin" ASC)');
                self::db(array("skip_clean" => true))->exec('VACUUM');
            } else {
                $object->exec('CREATE TABLE IF NOT EXISTS "'.self::$table.'" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL  UNIQUE , "name" VARCHAR UNIQUE NOT NULL  , "value" BLOB, "added" INTEGER NOT NULL  DEFAULT 0, "endin" INTEGER NOT NULL  DEFAULT 0)');
                $object->exec('CREATE INDEX "lookup" ON "'.self::$table.'" ("added" ASC, "endin" ASC)');
                $object->exec('VACUUM');
            }


        }






        var $headers;
        var $user_agent = "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31";
        var $compression;
        var $cookie_file;
        var $cookies;
        var $proxy;

        function cURL($cookies = TRUE, $cookie = 'cookie.txt', $compression = 'gzip', $proxy = '')
        {
            //    $this->headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg';
            $this->headers[] = 'Connection: Keep-Alive';
            $this->headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
            $this->user_agent = 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31';
            $this->compression = $compression;
            $this->proxy = $proxy;
            $this->cookies = $cookies;
            if ($this->cookies == TRUE) $this->cookie($cookie);

            //   self::$_headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg';

        }

        private function cookie($cookie_file)
        {
            $cookie_file = self::getPath()."/".$cookie_file;
            if (file_exists($cookie_file)) {
                $this->cookie_file = $cookie_file;
            } else {
                @fopen($cookie_file, 'w+') or $this->error('The cookie.txt file could not be opened. Please create cookie.txt and chmod 0777 for it.');
                $this->cookie_file = $cookie_file;
                @fclose($this->cookie_file);
            }
        }

        private function _getcURL($url, $ref = "", $user = "", $pass = "", $hash = "", $ssl = false, $cache = false)
        {
            $process = curl_init($url);
            curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
            if ($ssl == true) {
                @curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 0);
                # Allow certs that do not match the domain
                @curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
            }
            if (($user != "") && ($pass != "")) {
                $header[0] = "Authorization: Basic " . base64_encode($user . ":" . $pass) . "\n\r";
                @curl_setopt($process, CURLOPT_HTTPHEADER, $header);
            } elseif (($user != "") && ($hash != "")) {
                $header[0] = "Authorization: WHM " . $user . ":" . preg_replace("'(\r|\n)'", "", $hash);
                # Remove newlines from the hash
                curl_setopt($process, CURLOPT_HTTPHEADER, $header);
            } else {
                curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
                curl_setopt($process, CURLOPT_HEADER, 0);
            }
            if ($cache == true) {
                curl_setopt($process, CURLOPT_FRESH_CONNECT, true);

            }

            curl_setopt($process, CURLOPT_REFERER, $ref);
            if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
            if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
            curl_setopt($process, CURLOPT_ENCODING, $this->compression);
            curl_setopt($process, CURLOPT_TIMEOUT, 30);
            if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
            curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
            try {
                curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
            } catch(Exception $e) {

            }

            $return = curl_exec($process);
            curl_close($process);
            if(trim($return) == "") {
                $return = file_get_contents($url);
            }
            return $return;
        }

        private  function _postcURL($url, $data, $ref = "", $user = "", $pass = "", $hash = "", $ssl = false)
        {
            if ($ref == "") {
                $ref = $url;
            }
            $process = curl_init($url);
            if ($ssl == true) {
                @curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 0);
                # Allow certs that do not match the domain
                @curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
            }
            if (($user != "") && ($pass != "")) {
                $header[0] = "Authorization: Basic " . base64_encode($user . ":" . $pass) . "\n\r";
                @curl_setopt($process, CURLOPT_HTTPHEADER, $header);
            } elseif (($user != "") && ($hash != "")) {
                $header[0] = "Authorization: WHM " . $user . ":" . preg_replace("'(\r|\n)'", "", $hash);
                # Remove newlines from the hash
                curl_setopt($process, CURLOPT_HTTPHEADER, $header);
            } else {
                curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
                curl_setopt($process, CURLOPT_HEADER, 0);
            }

            curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
            curl_setopt($process, CURLOPT_REFERER, $ref);
            if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
            if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
            curl_setopt($process, CURLOPT_ENCODING, $this->compression);
            curl_setopt($process, CURLOPT_TIMEOUT, 30);
            if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
            curl_setopt($process, CURLOPT_POSTFIELDS, $data);
            curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
            try {
                curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
            } catch(Exception $e) {

            }
            curl_setopt($process, CURLOPT_POST, 1);
            $return = curl_exec($process);
            curl_close($process);
            return $return;
        }

        private function error($error)
        {
            echo $error;
            die("");
        }

        public static function getBycURL($name, $url = "http://", $time_in_second = 600, $skip_if_exist = false,
                                        $option = array(
                                                "ref" => "",
                                                "user" => "",
                                                "pass"  => "",
                                                "hash"  => "",
                                                "ssl"   => false,
                                                "user_agent" => "",
                                                )
        ) {
            $data = self::get($name);
            if($data == null) {
                $curl = new phpFastCache();
                $curl->cURL();
                if($option['user_agent']!="") {
                    $curl->user_agent = $option['user_agent'];
                }

                $html = $curl->_getcURL($url, $option['ref'],$option['user'],$option['pass'],$option['hash'],$option['ssl']);
                $res = self::set($name,$html,$time_in_second,$skip_if_exist);
                return $html;
            } else {
                return $data;
            }
        }

        public static function postBycURL($name, $url = "http://", $data = array(), $time_in_second = 600, $skip_if_exist = false,
                                         $option = array(
                                             "ref" => "",
                                             "user" => "",
                                             "pass"  => "",
                                             "hash"  => "",
                                             "user_agent" => "",
                                             "ssl"   => false,
                                         )
        ) {
            $data = self::get($name);
            if($data == null) {
                $curl = new phpFastCache();

                $curl->cURL();
                if($option['user_agent']!="") {
                    $curl->user_agent = $option['user_agent'];
                }
                $string="";
                foreach($data as $var=>$value) {
                    $string.="&".$var."=".urlencode($value);
                }
                $string = substr($string,1);

                $html = $curl->_postcURL($url, $string, $option['ref'],$option['user'],$option['pass'],$option['hash'],$option['ssl']);
                $res = self::set($name,$html,$time_in_second,$skip_if_exist);
                return $html;
            } else {
                return $data;
            }
        }

    }

?>