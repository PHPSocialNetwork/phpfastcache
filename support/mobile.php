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
define('THIS_SCRIPT', 'mobile');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('register');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'login' => array(
		'mobile_login'
	),
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

if (!IS_MOBILE_STYLE)
{
   exec_header_redirect($vbulletin->options['bburl']);
}

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// ######################### start cache.manifest ############################
if ($_REQUEST['do'] == 'cachemanifest')
{
	// Debug only
	if ($vbulletin->debug)
	{
//		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
//		header("Cache-Control: no-cache");
//		header("Pragma: no-cache");
	}
	$vbulletin->contenttype = 'text/cache-manifest';

	$templater = vB_Template::create('');
	$cssfilepath = str_replace('&amp;', '&', $templater->fetch_css_path());

	echo <<<EOD
CACHE MANIFEST

# v4
CACHE:
./clientscript/jquery/jquery-1.6.1.min.js?v={$vbulletin->options['simpleversion']}
./clientscript/jquery/jquery.mobile-1.0b1.vb.js?v={$vbulletin->options['simpleversion']}
./clientscript/vbulletin-mobile.js?v={$vbulletin->options['simpleversion']}
./clientscript/vbulletin-mobile-init.js?v={$vbulletin->options['simpleversion']}

./clientscript/jquery/jquery.mobile-1.0b1.min.css?v={$vbulletin->options['simpleversion']}
./{$cssfilepath}bbcode.css,editor.css,popupmenu.css,reset-fonts.css,vbulletin.css,vbulletin-chrome.css,vbulletin-formcontrols.css,

./images/mobile/album.png
./images/mobile/arrow-down.png
./images/mobile/arrow-left.png
./images/mobile/articles.png
./images/mobile/blogs.png
./images/mobile/close.png
./images/mobile/forums.png
./images/mobile/friends.png
./images/mobile/gridmenu.png
./images/mobile/home.png
./images/mobile/login.png
./images/mobile/messages.png
./images/mobile/notifications.png
./images/mobile/profile.png
./images/mobile/search.png
./images/mobile/settings.png
./images/mobile/vbulletin-logo.png
./images/mobile/whatsnew.png

./images/statusicon/forum_old-16.png
./images/statusicon/forum_link-16.png
./images/statusicon/forum_new-16.png
./images/buttons/collapse_40b.png
./clientscript/jquery/images/ajax-loader.png
./clientscript/jquery/images/icons-18-white.png

NETWORK:
*
EOD;

	die;
}

// ######################### start login page ############################
if ($_REQUEST['do'] == 'login')
{
	if ($vbulletin->userinfo['userid'])
	{
		// Already logged in
		exec_header_redirect($vbulletin->options['bburl']);
	}

	$show['forgetpassword'] = true;

	$templater = vB_Template::create('mobile_login');
		$templater->register_page_templates();
		$templater->register('url', $vbulletin->url);
	print_output($templater->render());
}

// ######################### start grid menu ############################
if ($_REQUEST['do'] == 'gridmenu')
{
	if (!$notifications_total) $notifications_total = '0';
	$show['blogs'] = ($vbulletin->products['vbblog'] == '1');
	$show['articles'] = ($vbulletin->products['vbcms'] == '1');

	$templater = vB_Template::create('mobile_gridmenu');
		$templater->register_page_templates();
		$templater->register('notifications_total', $notifications_total);
		$templater->register('pageinfo_friends', array('tab' => 'friends'));
	print_output($templater->render());
}


// ######################### start notifications ############################
if ($_REQUEST['do'] == 'notifications')
{
	if ($notifications_total)
	{
		$show['notifications'] = true;
	}
	else
	{
		$show['notifications'] = false;
	}

	$templater = vB_Template::create('mobile_notifications');
		$templater->register_page_templates();
		$templater->register('notifications_menubits', $notifications_menubits);
		$templater->register('notifications_total', $notifications_total);
	print_output($templater->render());

}

// ######################### start agreement ############################
if ($_REQUEST['do'] == 'agreement')
{
	$templater = vB_Template::create('mobile_agreement');
	print_output($templater->render());

}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 35803 $
|| ####################################################################
\*======================================================================*/