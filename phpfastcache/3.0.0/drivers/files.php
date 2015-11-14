<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class phpfastcache_files extends  BasePhpFastCache implements phpfastcache_driver  {

    function checkdriver() {
        if(is_writable($this->getPath())) {
            return true;
        } else {

        }
        return false;
    }

    /*
     * Init Cache Path
     */
    function __construct($config = array()) {
        $this->setup($config);
        $this->getPath(); // force create path

        if(!$this->checkdriver() && !isset($config['skipError'])) {
            throw new Exception("Can't use this driver for your website!");
        }

    }

    private function encodeFilename($keyword) {
        return trim(trim(preg_replace("/[^a-zA-Z0-9]+/","_",$keyword),"_"));
        // return rtrim(base64_encode($keyword), '=');
    }

    private function decodeFilename($filename) {
        return $filename;
        // return base64_decode($filename);
    }

    /*
     * Return $FILE FULL PATH
     */
    private function getFilePath($keyword, $skip = false) {
        $path = $this->getPath();

        $filename = $this->encodeFilename($keyword);
        $folder = substr($filename,0,2);
        $path = rtrim($path,"/")."/".$folder;
        /*
         * Skip Create Sub Folders;
         */
        if($skip == false) {
            if(!@file_exists($path)) {
                if(!@mkdir($path,$this->__setChmodAuto())) {
                    throw new Exception("PLEASE CHMOD ".$this->getPath()." - 0777 OR ANY WRITABLE PERMISSION!",92);
                }

            } elseif(!is_writeable($path)) {
                if(!chmod($path,$this->__setChmodAuto())) {
                    throw new Exception("PLEASE CHMOD ".$this->getPath()." - 0777 OR ANY WRITABLE PERMISSION!",92);
                }
            }
        }

        $file_path = $path."/".$filename.".txt";
        return $file_path;
    }

    function driver_set($keyword, $value = "", $time = 300, $option = array() ) {
        $file_path = $this->getFilePath($keyword);
      //  echo "<br>DEBUG SET: ".$keyword." - ".$value." - ".$time."<br>";
        $data = $this->encode($value);

        $toWrite = true;
        /*
         * Skip if Existing Caching in Options
         */
        if(isset($option['skipExisting']) && $option['skipExisting'] == true && @file_exists($file_path)) {
            $content = $this->readfile($file_path);
            $old = $this->decode($content);
            $toWrite = false;
            if($this->isExpired($old)) {
                $toWrite = true;
            }
        }

        if($toWrite == true) {
                try {
                    $f = @fopen($file_path, "w+");
                    fwrite($f, $data);
                    fclose($f);
                } catch (Exception $e) {
                    // miss cache
                    return false;
                }
        }
    }

    function driver_get($keyword, $option = array()) {

        $file_path = $this->getFilePath($keyword);
        if(!@file_exists($file_path)) {
            return null;
        }

        $content = $this->readfile($file_path);
        $object = $this->decode($content);
        if($this->isExpired($object)) {
            @unlink($file_path);
            $this->auto_clean_expired();
            return null;
        }

        return $object;
    }

    function driver_delete($keyword, $option = array()) {
        $file_path = $this->getFilePath($keyword,true);
        if(@unlink($file_path)) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Return total cache size + auto removed expired files
     */
    function driver_stats($option = array()) {
        $res = array(
            "info"  =>  "",
            "size"  =>  "",
            "data"  =>  "",
        );

        $path = $this->getPath();
        $dir = @opendir($path);
        if(!$dir) {
            throw new Exception("Can't read PATH:".$path,94);
        }

        $total = 0;
        $removed = 0;
        $content = array();
        while($file=@readdir($dir)) {
            if($file!="." && $file!=".." && is_dir($path."/".$file)) {
                // read sub dir
                $subdir = @opendir($path."/".$file);
                if(!$subdir) {
                    throw new Exception("Can't read path:".$path."/".$file,93);
                }

                while($f = @readdir($subdir)) {
                    if($f!="." && $f!="..") {
                        $file_path = $path."/".$file."/".$f;
                        $size = @filesize($file_path);
                        $object = $this->decode($this->readfile($file_path));

                        if(strpos($f,".") === false) {
                            $key = $f;
                        }
                        else {
                            //Because PHP 5.3, this cannot be written in single line
                            $key = explode(".", $f);
                            $key = $key[0];
                        }
                        $content[$key] = array("size"=>$size,"write_time"=>$object["write_time"]);
                        if($this->isExpired($object)) {
                            @unlink($file_path);
                            $removed += $size;
                        }
                        $total += $size;
                    }
                } // end read subdir
            } // end if
       } // end while

       $res['size'] = $total - $removed;
       $res['info'] = array(
                "Total [bytes]" => $total,
                "Expired and removed [bytes]" => $removed,
                "Current [bytes]" => $res['size'],
       );
        $res["data"] = $content;
       return $res;
    }

    function auto_clean_expired() {
        $autoclean = $this->get("keyword_clean_up_driver_files");
        if($autoclean == null) {
            $this->set("keyword_clean_up_driver_files",3600*24);
            $res = $this->stats();
        }
    }

    function driver_clean($option = array()) {

        $path = $this->getPath();
        $dir = @opendir($path);
        if(!$dir) {
            throw new Exception("Can't read PATH:".$path,94);
        }

        while($file=@readdir($dir)) {
            if($file!="." && $file!=".." && is_dir($path."/".$file)) {
                // read sub dir
                $subdir = @opendir($path."/".$file);
                if(!$subdir) {
                    throw new Exception("Can't read path:".$path."/".$file,93);
                }

                while($f = @readdir($subdir)) {
                    if($f!="." && $f!="..") {
                        $file_path = $path."/".$file."/".$f;
                        @unlink($file_path);
                    }
                } // end read subdir
            } // end if
        } // end while

    }

    function driver_isExisting($keyword) {
        $file_path = $this->getFilePath($keyword,true);
        if(!@file_exists($file_path)) {
            return false;
        } else {
            // check expired or not
            $value = $this->get($keyword);
            if($value == null) {
                return false;
            } else {
                return true;
            }
        }
    }

    function isExpired($object) {
        if(isset($object['expired_time']) && time() >= $object['expired_time']) {
            return true;
        } else {
            return false;
        }
    }

}
