<?php
/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */

class phpfastcache_files extends  phpfastcache_method {

    function checkMethod() {
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

        if(!$this->checkMethod()) {
            return false;
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
                    die("PLEASE CHMOD ".$this->getPath()." - 0777 OR ANY WRITABLE PERMISSION!");
                }

            } elseif(!is_writeable($path)) {
                @chmod($path,0777);
            }
        }

        $file_path = $path."/".$code.".txt";
        return $file_path;
    }


    function set($keyword, $value = "", $time = 300, $option = array() ) {
        $file_path = $this->getFilePath($keyword);
      //  echo "<br>DEBUG SET: ".$keyword." - ".$value." - ".$time."<br>";
        $data = $this->encode($value, $time, $option);

        $toWrite = true;
        /*
         * Skip if Existing Caching in Options
         */
        if(isset($option['skipExisting']) && $option['skipExisting'] == true && file_exists($file_path)) {
            $content = file_get_contents($file_path);
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

    function get($keyword, $option = array()) {

        $file_path = $this->getFilePath($keyword);
        if(!file_exists($file_path)) {
            return null;
        }

        $object = $this->decode(file_get_contents($file_path));
        if($this->isExpired($object)) {
            @unlink($file_path);
            return null;
        }

        return $object['data'];
    }

    function delete($keyword, $option = array()) {
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
    function stats($option = array()) {
        $res = array(
            "info"  =>  "",
            "size"  =>  "",
            "data"  =>  "",
        );

        $path = $this->getPath();
        $dir = opendir($path);
        $total = 0;
        $removed = 0;
        while($file=readdir($dir)) {
            if($file!="." && $file!=".." && is_dir($path."/".$file)) {
                // read sub dir
                $subdir = opendir($path."/".$file);
                while($f = readdir($subdir)) {
                    if($f!="." && $f!="..") {
                        $file_path = $path."/".$file."/".$f;
                        $size = filesize($file_path);
                        $object = $this->decode(file_get_contents($file_path));
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

    function clean($option = array()) {
        return $this->stats($option);
    }

    function isExisting($keyword) {
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
        if(@date("U") >= $object['exp']) {
            return true;
        } else {
            return false;
        }
    }

    function increment($keyword,$step =1 , $option = array()) {
        $file_path = $this->getFilePath($keyword);
        if(!file_exists($file_path)) {
            $this->set($keyword,$step,3600);
            return $step;
        }

        $object = $this->decode(file_get_contents($file_path));
        if($this->isExpired($object)) {
            $this->set($keyword,$step,3600);
            return $step;
        }

        $next = (Int)$object['data'] + $step;
        $object['data'] = $next;

        $string = serialize($object);

        $f = fopen($file_path,"w+");
        fwrite($f,$string);
        fclose($f);
        return $next;
    }

    function decrement($keyword,$step =1 , $option = array()) {
        $file_path = $this->getFilePath($keyword);
        if(!file_exists($file_path)) {
            $this->set($keyword,$step,3600);
            return $step;
        }

        $object = $this->decode(file_get_contents($file_path));
        if($this->isExpired($object)) {
            $this->set($keyword,$step,3600);
            return $step;
        }

        $next = (Int)$object['data'] - $step;
        $object['data'] = $next;

        $string = serialize($object);

        $f = fopen($file_path,"w+");
        fwrite($f,$string);
        fclose($f);
        return $next;
    }


}