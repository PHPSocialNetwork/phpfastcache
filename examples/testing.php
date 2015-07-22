<?php
echo $_SERVER['HOME'];
print_r($_SERVER);

exit;
$string = "ABC_KAJSHDKAS_AKDJASKDJSA_SAKDJHSKADJHS_19827381276318253_ASKDHKAJSHDKAJDHA_18973628137621";
$max = 999999;

$start = microtime(true);
for($i=1;$i<=$max;$i++) {
	$list = md5($string);
	$end = microtime(true);
}
$end = $end - $start;


echo "MD5: ".$end."<br><br>";

$start = microtime(true);
for($i=1;$i<=$max;$i++) {
	$list = rtrim(base64_encode($string), '=');
	$end = microtime(true);
}
$end = $end - $start;

echo "Base64: ".$end."<br><br>";


$start = microtime(true);
for($i=1;$i<=$max;$i++) {
	$list = preg_replace("/#([^\W_]+)/","",$string);
	$end = microtime(true);
}
$end = $end - $start;

echo "Preg Replace: ".$end."<br><br>";