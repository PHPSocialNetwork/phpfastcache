<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 4.2.1 - Licence Number VBF02D260D
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

error_reporting(E_ALL & ~E_NOTICE);

define('VB_API', true);
define('VB_API_VERSION', 7);
define('VB_API_VERSION_MIN', 1);
define('CWD_API', (($getcwd = getcwd()) ? $getcwd : '.') . '/includes/api');
define('NOCOOKIES', true);

require_once(CWD_API . '/functions_api.php');
require_once(CWD_API . '/class_api.php');

$api_m = trim($_REQUEST['api_m']);
if (strpos($api_m, 'cms.') !== 0)
{
	// Method name should be in the format "scriptname_action" or "scriptname"
	list($api_script, $action) = explode("_", $api_m);
	$api_script = str_replace('.', '_', trim($api_script));
	$_REQUEST['do'] = $_GET['do'] = $_POST['do'] = trim($action);
	define('VB_API_CMS', false);
}
else
{
	// CMS methods.
	// cms.routename_pathsegment1_pathsegment2_...
	$methodsegments = explode("_", $api_m);
	$api_script = str_replace('cms.', '', array_shift($methodsegments));
	$_REQUEST['r'] = implode('/', $methodsegments);
	define('VB_API_CMS', true);
}
// API Version
$api_version = intval($_REQUEST['api_v']);
if (!$api_version)
{
	$api_version = VB_API_VERSION;
}
if ($api_version < VB_API_VERSION_MIN)
{
	print_apierror('api_version_too_low', 'This server accepts API version ' . VB_API_VERSION_MIN . ' at least. The requested API version is too low.');
}
elseif ($api_version > VB_API_VERSION)
{
	print_apierror('api_version_too_high', 'This server accepts API version ' . VB_API_VERSION . ' at most. The requested API version is too high.');
}

// Client ID
$api_c = intval($_REQUEST['api_c']);

// Access token
$api_s = trim($_REQUEST['api_s']);

// Request Signature Verification Prepare (Verified in init.php)
$api_sig = trim($_REQUEST['api_sig']);
unset($_GET['']); // See VBM-835
$VB_API_PARAMS_TO_VERIFY = $_GET;
unset($VB_API_PARAMS_TO_VERIFY['api_c'], $VB_API_PARAMS_TO_VERIFY['api_v'], $VB_API_PARAMS_TO_VERIFY['api_s'], $VB_API_PARAMS_TO_VERIFY['api_sig'],
$VB_API_PARAMS_TO_VERIFY['debug'], $VB_API_PARAMS_TO_VERIFY['showall'], $VB_API_PARAMS_TO_VERIFY['do'], $VB_API_PARAMS_TO_VERIFY['r'], $VB_API_PARAMS_TO_VERIFY['vbseourl']);
ksort($VB_API_PARAMS_TO_VERIFY);
$VB_API_REQUESTS = array(
	'api_m' => $api_m,
	'api_version' => $api_version,
	'api_c' => $api_c,
	'api_s' => $api_s,
	'api_sig' => $api_sig
);

if (!$api_script)
{
	header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
	die();
}

// Check if the api method has been defined in versions
$api_script = loadAPI($api_script, $_REQUEST['do'], $api_version);

//find the latest version of the class defined.  This could be written more
//cleanly but I'm trying not to touch the logic below that runs the class.
for($i = $api_version; $i > 0; $i--)
{
	$api_classname = 'vB_APIMethod_' . $api_m . '_' . $i;
	if (class_exists($api_classname))
	{
		break;
	}
}

if (!class_exists($api_classname))
{
	$api_classname = 'vB_APIMethod_' . $api_m;
}

if (class_exists($api_classname))
{
	$apimethod = new $api_classname();
	if ($apimethod instanceof vBI_APIMethod)
	{
		require_once('./global.php');

		$output = json_encode($apimethod->processed_output());

		$sign = md5($output . $vbulletin->apiclient['apiaccesstoken'] . $vbulletin->apiclient['apiclientid'] . $vbulletin->apiclient['secret'] . $vbulletin->options['apikey']);
		@header('Authorization: ' . $sign);

		if (!$_REQUEST['debug'])
		{
			@header('Content-Type: application/json');
		}

		// Trigger shutdown event
		$vbulletin->shutdown->shutdown();

		if (defined('NOSHUTDOWNFUNC'))
		{
			exec_shut_down();
		}

		echo $output;
		die();
	}
	else
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
		die();
	}
}

include($api_script . '.php');

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 35584 $
|| ####################################################################
\*======================================================================*/
