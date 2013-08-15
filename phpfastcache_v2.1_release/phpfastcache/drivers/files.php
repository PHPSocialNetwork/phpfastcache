<?php
/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */

class phpfastcache_files extends  phpFastCache implements phpfastcache_driver  {

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
    function __construct($option = array()) {

        $this->setOption($option);
        $this->getPath();

        if(!$this->checkdriver() && !isset($option['skipError'])) {
            throw new Exception("Can't use this driver for your website!");
        }

    }

    /*
     * Return $FILE FULL PATH
     */
    private function getFilePath($keyword, $skip = false) {
        $path = $this->getPath();
        $code = md5($keyword);
        $folder = substr($code,0,2);
        $path = $path."/".$folder;
        /*
         * Skip Create Sub Folders;
         */
        if($skip == false) {
            if(!file_exists($path)) {
                if(!@mkdir($path,0777)) {
                    throw new Exception("PLEASE CHMOD ".$this->getPath()." - 0777 OR ANY WRITABLE PERMISSION!",92);
                }

            } elseif(!is_writeable($path)) {
                @chmod($path,0777);
            }
        }

        $file_path = $path."/".$code.".txt";
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
        if(isset($option['skipExisting']) && $option['skipExisting'] == true && file_exists($file_path)) {
            $content = $this->readfile($file_path);
            $old = $this->decode($content);
            $toWrite = false;
            if($this->isExpired($old)) {
                $toWrite = true;
            }
        }

        if($toWrite == true) {
                $f = fopen($file_path,"w+");
                fwrite($f,$data);
                fclose($f);
        }
    }




    function driver_get($keyword, $option = array()) {

        $file_path = $this->getFilePath($keyword);
        if(!file_exists($file_path)) {
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
        while($file=readdir($dir)) {
            if($file!="." && $file!=".." && is_dir($path."/".$file)) {
                // read sub dir
                $subdir = @opendir($path."/".$file);
                if(!$subdir) {
                    throw new Exception("Can't read path:".$path."/".$file,93);
                }

                while($f = readdir($subdir)) {
                    if($f!="." && $f!="..") {
                        $file_path = $path."/".$file."/".$f;
                        $size = filesize($file_path);
                        $object = $this->decode($this->readfile($file_path));
                        if($this->isExpired($object)) {
                            unlink($file_path);
                            $removed = $removed + $size;
                        }
                        $total = $total + $size;
                    }
                } // end read subdir
            } // end if
       } // end while

       $res['size']  = $total - $removed;
       $res['info'] = array(
                "Total" => $total,
                "Removed"   => $removed,
                "Current"   => $res['size'],
       );
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

        while($file=readdir($dir)) {
            if($file!="." && $file!=".." && is_dir($path."/".$file)) {
                // read sub dir
                $subdir = @opendir($path."/".$file);
                if(!$subdir) {
                    throw new Exception("Can't read path:".$path."/".$file,93);
                }

                while($f = readdir($subdir)) {
                    if($f!="." && $f!="..") {
                        $file_path = $path."/".$file."/".$f;
                        unlink($file_path);
                    }
                } // end read subdir
            } // end if
        } // end while


    }


    function driver_isExisting($keyword) {
        $file_path = $this->getFilePath($keyword,true);
        if(!file_exists($file_path)) {
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

        if(isset($object['expired_time']) && @date("U") >= $object['expired_time']) {
            return true;
        } else {
            return false;
        }
    }




}