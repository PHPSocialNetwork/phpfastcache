<?php
    // phpFastCache
    // Author: Khoa Bui
    // E-mail: khoaofgod@yahoo.com
    // Website: http://www.phpfastcache.com
    // PHP Fast Cache is simple caching build on SQLite & PDO.

    class phpFastCache {
        private static $pdo = "";
        public static  $path = "";
        private static $filename = "caching.0777";

        private static $table = "objects";

        public static function delete($name) {
            $name = self::safename($name);
            self::db()->exec("DELETE FROM ".self::$table." WHERE `name`='".$name."'");
        }

        public static function resetAll() {
            self::db(array("skip_clean" => true))->exec("drop table if exists ".self::$table);
            self::createDatabase();
            self::createIndex();

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
                $res['size'] = filesize(self::$path."/".self::$filename);
            }

            return $res;
        }

        public static function increment($name ,$step = 1) {
            $name = self::safename($name);
            $int = self::get($name);
            try {
                $stm = self::db()->prepare("UPDATE ".self::$table." SET `value`=:int + :step WHERE `name`=:name ");
                $stm->execute(array(
                    ":int"  => $int,
                    ":step" => $step,
                    ":name" =>  $name,
                ));

            } catch (PDOException $e) {
                die("Sorry! phpFastCache don't allow this type of value - Name: ".$name." -> Increment: ".$step);
            }

        }

        private static function safename($name) {
            return strtolower(preg_replace("/[^a-zA-Z0-9_\s\.]+/","",$name));

        }

        public static function decrement($name, $step = 1) {
            $name = self::safename($name);
            $int = self::get($name);
            try {
                $stm = self::db()->prepare("UPDATE ".self::$table." SET `value`=:int - :step WHERE `name`=:name ");
                $stm->execute(array(
                    ":int"  => $int,
                    ":step" => $step,
                    ":name" =>  $name,
                ));

            } catch (PDOException $e) {
                die("Sorry! phpFastCache don't allow this type of value - Name: ".$name." -> Decrement: ".$step);
            }

        }

        public static function get($name) {
            $name = self::safename($name);
            $stm = self::db()->prepare("SELECT * FROM ".self::$table." WHERE `name`='".$name."'");
            $stm->execute();
            $res = $stm->fetch();
            if(!isset($res['value'])) {
                return null;
            } else {
                return unserialize($res['value']);
            }
        }

        public static function set($name,$value,$time_in_second = 600, $skip_if_existing = false) {
            $name = self::safename($name);
            $insert = false;
            if($skip_if_existing == true) {
                try {
                    $insert = self::db()->prepare("INSERT OR IGNORE INTO ".self::$table." (name,value,added,endin) VALUES(:name,:value,:added,:endin)

                                                    ");
                    try {
                        $value = serialize($value);
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
                    $insert = self::db()->prepare("INSERT OR REPLACE INTO ".self::$table." (name,value,added,endin) VALUES(:name,:value,:added,:endin)

                    ");
                    try {
                        $value = serialize($value);
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

        private static function db($option = array()) {
            if(self::$pdo=="") {
              //  self::$pdo == new PDO("sqlite:".self::$path."/cachedb.sqlite");
                if(self::$path!="memory") {
                    if(self::$path == "") {
                        self::$path = dirname(__FILE__);
                    }
                    try {
                        self::$pdo = new PDO("sqlite:".self::$path."/".self::$filename);
                        self::$pdo->setAttribute(PDO::ATTR_ERRMODE,
                            PDO::ERRMODE_EXCEPTION);
                    } catch (PDOException $e) {
                        die("Can't connect to caching file ".self::$path."/".self::$filename);
                    }


                } else {
                    self::$pdo = new PDO("sqlite::memory:");
                    self::$pdo->setAttribute(PDO::ATTR_ERRMODE,
                        PDO::ERRMODE_EXCEPTION);

                    self::createDatabase();
                }

                // remove old cache
                if(!isset($option['skip_clean'])) {
                    try {
                        self::$pdo->exec("DELETE FROM ".self::$table." WHERE (`added` + `endin`) < ".@date("U"));
                    } catch(PDOException $e) {
                        die("Please re-upload the caching file ".self::$filename." and chmod it 0777 or Writable permission!");
                    }
                }


                return self::$pdo;

            } else {
                return self::$pdo;
            }




        }

        private static function createDatabase() {
            self::db(array("skip_clean" => true))->exec('CREATE TABLE IF NOT EXISTS "'.self::$table.'" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL  UNIQUE , "name" VARCHAR UNIQUE NOT NULL  , "value" BLOB, "added" INTEGER NOT NULL  DEFAULT 0, "endin" INTEGER NOT NULL  DEFAULT 0)');
        }

        private static function createIndex() {
            self::db(array("skip_clean" => true))->exec('CREATE INDEX "lookup" ON "'.self::$table.'" ("added" ASC, "endin" ASC)');
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
            if (file_exists($cookie_file)) {
                $this->cookie_file = $cookie_file;
            } else {
                @fopen($cookie_file, 'w+') or $this->error('The cookie file could not be opened. Make sure this directory has the correct permissions');
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