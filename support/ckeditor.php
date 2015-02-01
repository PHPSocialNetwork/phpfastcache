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

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ckeditor');
define('CSRF_PROTECTION', true);
define('NOPMPOPUP', 1);
define('NOCOOKIES', 1);
define('NONOTICES', 1);
define('NOHEADER', 1);
define('NOSHUTDOWNFUNC', 1);
define('LOCATION_BYPASS', 1);
define('NOGLOBALPHRASE', 1);

// Immediately send back the 304 Not Modified header if this is cached, don't load global.php
if ((!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) OR !empty($_SERVER['HTTP_IF_NONE_MATCH'])))
{
	$sapi_name = php_sapi_name();
	if ($sapi_name == 'cgi' OR $sapi_name == 'cgi-fcgi')
	{
		header('Status: 304 Not Modified');
	}
	else
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
	}
	// remove the content-type and X-Powered headers to emulate a 304 Not Modified response as close as possible
	header('Content-Type:');
	header('X-Powered-By:');
	exit;
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('ckeditor');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions  - build
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_xml.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

header('Pragma:'); // VBIV-8269 
header('Cache-control: max-age=31536000, private');
header('Expires: ' . gmdate("D, d M Y H:i:s", TIMENOW + 31536000) . ' GMT');

// When were the CKEditor phrases last modified? Good Question.
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', 0) . ' GMT');

$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
$xml->add_group('phrases');

foreach ($vbphrase AS $key => $phrase)
{
	$xml->add_tag('phrase', $phrase, array(
		'name'  => $key,
	));
}

$xml->close_group('group');
$xml->print_xml();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 30573 $
|| ####################################################################
\*======================================================================*/
