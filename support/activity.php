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
define('THIS_SCRIPT', 'activity');
define('CSRF_PROTECTION', true);
if ($_POST['ajax'] == 1)
{
	define('LOCATION_BYPASS', 1);
	define('NOPMPOPUP', 1);
	define('NONOTICES', 1);
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'activitystream',
	'user'
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
	'blogcategorycache',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'activitystream_home',
	'activitystream_album_album',
	'activitystream_album_comment',
	'activitystream_album_photo',
	'activitystream_calendar_event',
	'activitystream_date_group',
	'activitystream_photo_date_bit',
	'activitystream_forum_post',
	'activitystream_forum_thread',
	'activitystream_forum_visitormessage',
	'activitystream_socialgroup_discussion',
	'activitystream_socialgroup_group',
	'activitystream_socialgroup_groupmessage',
	'activitystream_socialgroup_photo',
	'activitystream_socialgroup_photocomment',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (STYLE_TYPE == 'mobile' AND (!defined('VB_API') OR VB_API !== true))
{
	exec_header_redirect('forum.php' . $vbulletin->session->vars['sessionurl_q']);
}

if ($_POST['do'] == 'loadactivitytab')
{
	require_once(DIR . '/includes/class_userprofile.php');
	require_once(DIR . '/includes/class_profileblock.php');
	require_once(DIR . '/includes/functions_user.php');

	$fetch_userinfo_options = (
		FETCH_USERINFO_AVATAR | FETCH_USERINFO_LOCATION |
		FETCH_USERINFO_PROFILEPIC | FETCH_USERINFO_SIGPIC |
		FETCH_USERINFO_USERCSS | FETCH_USERINFO_ISFRIEND
	);

	$vbulletin->input->clean_array_gpc('p', array(
		'userid' => TYPE_UINT,
	));
	$userinfo = verify_id('user', $vbulletin->GPC['userid'], false, true, $fetch_userinfo_options);

	$profileobj = new vB_UserProfile($vbulletin, $userinfo);
	$blockfactory = new vB_ProfileBlockFactory($vbulletin, $profileobj);
	$profileblock =& $blockfactory->fetch('friends');

	$activity = new vB_ActivityStream_View_MembertabAjax($vbphrase, $profileblock->visitor_can_view('friends', $vbulletin->userinfo));
	$activity->process();
}
else
{
	$activity = new vB_ActivityStream_View_Home($vbphrase);
	$activity->process();
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 16016 $
|| ####################################################################
\*======================================================================*/
