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

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
ignore_user_abort(1);
@set_time_limit(0);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('SKIP_SESSIONCREATE', 1);
define('NOCOOKIES', 1);
define('THIS_SCRIPT', 'cron');
define('CSRF_PROTECTION', true);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array();

// get special data templates from the datastore
$specialtemplates = array(
	'crondata',
	'mailqueue',
);

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_cron.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$filedata = vb_base64_decode('R0lGODlhAQABAIAAAMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
$filesize = strlen($filedata);

// browser will think there is no more data if content-length is what is returned
// regardless of how long the script continues to execute, apart from IIS + CGI

header('Content-type: image/gif');

if (!(strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false AND strpos(SAPI_NAME, 'cgi') !== false))
{
	header('Content-Length: ' . $filesize);
	header('Connection: Close');
}

if (!$vbulletin->options['crontab'])
{
	echo $filedata;
	flush();
}

($hook = vBulletinHook::fetch_hook('cron_start')) ? eval($hook) : false;

if (!defined('NOSHUTDOWNFUNC') AND !$vbulletin->options['crontab'])
{
	vB_Shutdown::add('exec_cron');
}
else
{
	$cronid = NULL;
	if ($vbulletin->options['crontab'] AND SAPI_NAME == 'cli')
	{
		$cronid = intval($_SERVER['argv'][1]);
		// if its a negative number or 0 set it to NULL so it just grabs the next task
		if ($cronid < 1)
		{
			$cronid = NULL;
		}
	}

	exec_cron($cronid);
	if (defined('NOSHUTDOWNFUNC'))
	{
		$db->close();
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 62096 $
|| ####################################################################
\*======================================================================*/
?>
