--TEST--
--SKIPIF--
<?php if (!extension_loaded("cassandra")) die("Skipped: cassandra extension required."); ?>
--FILE--
<?php
$statement = new Cassandra\SimpleStatement("SELECT * FROM system.schema_keyspaces");
echo "success";
?>
--EXPECT--
success
