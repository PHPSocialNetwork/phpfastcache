<?php
/**
 * PostRelease vBulletin Plugin
 *
 * @author Postrelease
 * @version 4.2.0
 * @copyright © PostRelease, Inc.
 */

error_reporting(E_ALL & ~E_NOTICE);


$phrasegroups = array('cpoption', 'cphome');
$specialtemplates = array();
require_once('./global.php');
log_admin_action();

header('Cache-Control: no-cache, must-revalidate');
header('Content-type: application/json');
$data = "{\"result\":0}";
if ($_GET['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'postrelease_enable' => TYPE_UINT
	));

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "setting
		SET value = '" . $vbulletin->db->escape_string($_GET['postrelease_enable']) ."'
		WHERE varname = 'postrelease_enable'
	");
	build_options();
	$data = "{\"result\":1}";
}
echo $_GET['callback'] . '(' . $data . ');';
exit;

?>
