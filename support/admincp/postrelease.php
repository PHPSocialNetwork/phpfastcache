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

$pr_base_url = 'http://www.postrelease.com/';
$dashboard_path = $pr_base_url . 'vbplugin/dashboard'
	.'?Referral=vBulletin&AdminCP='. urlencode($vbulletin->config['Misc']['admincpdir']) .'&PublicationUrl=' . urlencode($vbulletin->input->fetch_basepath());
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>PostRelease Sponsored Content</title>
<style>
body {
	margin-left: 0px;
	margin-top: 0px;
	background: #CCC;
}
</style>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
</head>
<body>
<center>
<?php
	echo '<iframe src="'. $dashboard_path .'" width="700px" height="900px" frameborder="0" scrolling="no"></iframe>';
?>
</center>
</body>
</html>