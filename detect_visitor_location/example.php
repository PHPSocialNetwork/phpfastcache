<?php
/*
 * Website http://www.codehelper.io
 * Author: khoaofgod@yahoo.com
 * Any bugs, question, please visit our forum at http://www.codehelper.io
 */

// Required Libraries
require_once("ip.codehelper.io.php");
require_once("php_fast_cache.php");

// New Class
$_ip = new ip_codehelper();

// Detect Real IP Address & Location
$real_client_ip_address = $_ip->getRealIP();
$visitor_location       = $_ip->getLocation($real_client_ip_address);

// Output result
echo $visitor_location['Country']."<br>";
echo "<pre>";
print_r($visitor_location);