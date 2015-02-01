<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Blog 4.2.1 - Licence Number VBF02D260D
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
define('VB_PRODUCT', 'vbblog');
define('THIS_SCRIPT', 'blog_callback');
define('VBBLOG_SCRIPT', true);
define('SKIP_SESSIONCREATE', 1);
define('VB_AREA', 'BlogCallback');
define('CWD', (($getcwd = getcwd()) ? $getcwd : '.'));
define('NOZIP', 1);
define('NOHEADER', 1);
define('NOCOOKIES', 1);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array();

// get special data templates from the datastore
$specialtemplates = array('blogcategorycache');

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once(CWD . '/includes/init.php');
require_once(DIR . '/includes/blog_functions.php');
require_once(DIR . '/includes/class_trackback.php');
require_once(DIR . '/includes/class_bootstrap.php');

define('VB_AREA', 'Forum');

$bootstrap = new vB_Bootstrap_Forum();
$bootstrap->datastore_entries = $specialtemplates;
$bootstrap->cache_templates = vB_Bootstrap::fetch_required_template_list(array(), array());
$bootstrap->bootstrap();

$vbulletin->input->clean_array_gpc('p', array(
	'url'    => TYPE_STR,
));

$vbulletin->input->clean_array_gpc('r', array(
	'blogid' => TYPE_UINT,
));

// Came to the url directly
if ($vbulletin->GPC['blogid'])
{
	if ($vbulletin->options['vbblog_pingback'])
	{
		$pingbackurl = fetch_seo_url('blogcallback|bburl', array());
		header("X-Pingback: $pingbackurl");
	}
	//we don't have the title available here, but it isn't that critical for a buried redirect
	exec_header_redirect(fetch_seo_url('entry|js|nosession', array('blogid' => $vbulletin->GPC['blogid'])));
}

($hook = vBulletinHook::fetch_hook('blog_callback_start')) ? eval($hook) : false;

$trackback = new vB_Trackback_Server($vbulletin);

if ($trackback->parse_blogid(SCRIPTPATH, $vbulletin->GPC['url']) AND $vbulletin->options['vbblog_trackback'])
{
	$trackback->send_xml_response();
}
else if (stristr($_SERVER['CONTENT_TYPE'], 'text/xml') === false OR $_SERVER['REQUEST_METHOD'] != 'POST' OR empty($GLOBALS['HTTP_RAW_POST_DATA']) OR !$vbulletin->options['vbblog_pingback'])
{	// Not an XML doc or was sent via GET so do nothing..
	exec_header_redirect(fetch_seo_url('forumhome', array()));
}

require_once(DIR . '/includes/class_xmlrpc_pingback.php');

// Pingback Server Instance
$xmlrpc_server = new vB_XMLRPC_Server_Pingback($vbulletin);
$xmlrpc_server->parse_xml($GLOBALS['HTTP_RAW_POST_DATA']);
$xmlrpc_server->parse_xmlrpc();
$xmlrpc_server->send_xml_response();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
?>
