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
define('THIS_SCRIPT', 'converse');
define('CSRF_PROTECTION', true);
define('BYPASS_STYLE_OVERRIDE', 1);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'user',
	'posting',
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'editor_ckeditor',
	'editor_clientscript',
	'editor_jsoptions_font',
	'editor_jsoptions_size',
	'editor_smilie_category',
	'editor_smilie_row',
	'newpost_disablesmiliesoption',
	'memberinfo_block_visitormessaging',
	'memberinfo_usercss',
	'memberinfo_visitormessage',
	'memberinfo_visitormessage_deleted',
	'memberinfo_visitormessage_ignored',
	'converse',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_bbcode.php');
require_once(DIR . '/includes/class_visitormessage.php');
require_once(DIR . '/includes/functions_visitormessage.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (!($vbulletin->userinfo['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers']))
{
	print_no_permission();
}
if (!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_visitor_messaging']))
{
	print_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'u'         => TYPE_UINT,
	'u2'         => TYPE_UINT,
	'perpage'    => TYPE_UINT,
	'pagenumber' => TYPE_UINT,
	'showignored' => TYPE_BOOL,
	'vmid'        => TYPE_UINT
));

($hook = vBulletinHook::fetch_hook('converse_start')) ? eval($hook) : false;

if ($vbulletin->GPC['vmid'])
{
	$vminfo = verify_visitormessage($vbulletin->GPC['vmid']);

	if (
		(
			$vminfo['postuserid'] != $vbulletin->GPC['u']
			OR $vminfo['userid'] != $vbulletin->GPC['u2']
		)
		AND
		(
			$vminfo['userid'] != $vbulletin->GPC['u']
			OR $vminfo['postuserid'] != $vbulletin->GPC['u2']
		)
	)
	{
		standard_error(fetch_error('invalidid', $vbphrase['visitor_message'], $vbulletin->options['contactuslink']));
	}
}

$userinfo = verify_id('user', $vbulletin->GPC['u'], true, true, FETCH_USERINFO_USERCSS | FETCH_USERINFO_ISFRIEND);
$userinfo2 = verify_id('user', $vbulletin->GPC['u2'], true, true, FETCH_USERINFO_USERCSS | FETCH_USERINFO_ISFRIEND);

// $userinfo will never be vbulletin->userinfo
// $userinfo2 may be vbulletin->userinfo
if ($userinfo2['userid'] == $vbulletin->userinfo['userid'])
{
	$viewself = true;
}
cache_permissions($userinfo, false);

if (
	(
		!$userinfo['vm_enable']
			AND
		!can_moderate(0,'canmoderatevisitormessages')
	)
		OR
	(
		$userinfo['vm_contactonly']
			AND
		!$userinfo['bbuser_iscontact_of_user']
			AND
		!can_moderate(0,'canmoderatevisitormessages')
	)
)
{
	print_no_permission();
}

if (
	(
		!$userinfo2['vm_enable']
			AND
		(
			!can_moderate(0,'canmoderatevisitormessages')
				OR
			$viewself
		)
	)
		OR
	(
		$userinfo2['vm_contactonly']
			AND
		!$userinfo2['bbuser_iscontact_of_user']
			AND
		!can_moderate(0,'canmoderatevisitormessages')
		 AND
		!$viewself
	)
)
{
	print_no_permission();
}

require_once(DIR . '/includes/functions_user.php');
if (!can_view_profile_section($userinfo['userid'], 'visitor_messaging') OR !can_view_profile_section($userinfo2['userid'], 'visitor_messaging'))
{
	print_no_permission();
}

// state1/sql1 refers to messages left to u's profile by u2 (which may be bbuserinfo)
// state2/sql2 refers to messages left to u2's profile (which may be bbuserinfo) by u

$sql1 = $sql2 = array();

$state2 = array('visible');
if (fetch_visitor_message_perm('canmoderatevisitormessages', $userinfo2))
{
	$state2[] = 'moderation';
}
if (can_moderate(0,'canmoderatevisitormessages') OR ($viewself AND $vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['canmanageownprofile']))
{
	$state2[] = 'deleted';
	$deljoinsql2 = "LEFT JOIN " . TABLE_PREFIX . "deletionlog AS deletionlog ON (visitormessage.vmid = deletionlog.primaryid AND deletionlog.type = 'visitormessage')";
}
else
{
	$deljoinsql2 = '';
}

$sql2[] = "visitormessage.userid = $userinfo2[userid]";
$sql2[] = "visitormessage.postuserid = $userinfo[userid]";
$sql2[] = "visitormessage.state IN ('" . implode("','", $state2) . "')";

$state1 = array('visible');
if ($viewself OR fetch_visitor_message_perm('canmoderatevisitormessages', $userinfo))
{
	$state1[] = 'moderation';
}
if (can_moderate(0,'canmoderatevisitormessages'))
{
	$state1[] = 'deleted';
	$delsql1 = ",deletionlog.userid AS del_userid, deletionlog.username AS del_username, deletionlog.reason AS del_reason";
	$deljoinsql1 = "LEFT JOIN " . TABLE_PREFIX . "deletionlog AS deletionlog ON (visitormessage.vmid = deletionlog.primaryid AND deletionlog.type = 'visitormessage')";
}
else if ($deljoinsql2)
{
	$delsql1 = ",0 AS del_userid, '' AS del_username, '' AS del_reason";
}

$sql1[] = "visitormessage.userid = $userinfo[userid]";
$sql1[] = "visitormessage.postuserid = $userinfo2[userid]";
$sql1[] = "visitormessage.state IN ('" . implode("','", $state1) . "')";

if (!$vbulletin->GPC['perpage'])
{
	$perpage = $vbulletin->options['vm_perpage'];
}
else if ($vbulletin->GPC['perpage'] > $vbulletin->options['vm_maxperpage'])
{
	$perpage = $vbulletin->options['vm_maxperpage'];
}
else
{
	$perpage = $vbulletin->GPC['perpage'];
}

$hook_query_fields1 = $hook_query_fields2 = $hook_query_joins1 = $hook_query_joins2 = $hook_query_where1 = $hook_query_where2 = '';
($hook = vBulletinHook::fetch_hook('converse_query')) ? eval($hook) : false;



if ($vminfo['vmid'])
{
	$getpagenum = $vbulletin->db->query_first("
		SELECT COUNT(*) As comments
		FROM " . TABLE_PREFIX . "visitormessage AS visitormessage
		WHERE (
			(" . implode(" AND ", $sql1) . ")
			OR (" . implode(" AND ", $sql2) . ")
		) AND dateline >= " . $vminfo[dateline]
	);
	$vbulletin->GPC['pagenumber'] = ceil($getpagenum['comments'] / $perpage);
}

$pagenumber = $vbulletin->GPC['pagenumber'];

do
{
	if (!$pagenumber)
	{
		$pagenumber = 1;
	}

	$start = ($pagenumber - 1) * $perpage;

	$messages = $db->query_read_slave("
	(
		SELECT
			visitormessage.*, visitormessage.dateline AS pmdateline, user.*, visitormessage.ipaddress AS messageipaddress, visitormessage.userid AS profileuserid
			$delsql1
			" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight, customavatar.width_thumb AS avwidth_thumb, customavatar.height_thumb AS avheight_thumb, filedata_thumb, NOT ISNULL(customavatar.userid) AS hascustom" : "") . "
			$hook_query_fields1
		FROM " . TABLE_PREFIX . "visitormessage AS visitormessage
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (visitormessage.postuserid = user.userid)
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
		$deljoinsql1
		$hook_query_joins1
		WHERE " . implode(" AND ", $sql1) . "
		$hook_query_where1
	)
	UNION
	(
		SELECT
			visitormessage.*, visitormessage.dateline AS pmdateline, user.*, visitormessage.ipaddress AS messageipaddress, visitormessage.userid AS profileuserid
			" . ($deljoinsql2 ? ",deletionlog.userid AS del_userid, deletionlog.username AS del_username, deletionlog.reason AS del_reason" : "") . "
			" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight, customavatar.width_thumb AS avwidth_thumb, customavatar.height_thumb AS avheight_thumb, filedata_thumb, NOT ISNULL(customavatar.userid) AS hascustom" : "") . "
			$hook_query_fields2
		FROM " . TABLE_PREFIX . "visitormessage AS visitormessage
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (visitormessage.postuserid = user.userid)
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
		$deljoinsql2
		$hook_query_joins2
		WHERE " . implode(" AND ", $sql2) . "
		$hook_query_where2
	)
	ORDER BY pmdateline DESC
	LIMIT $start, $perpage
	");

	$messagetotal = $db->found_rows();
	if ($start >= $messagetotal)
	{
		$pagenumber = ceil($messagetotal / $perpage);
	}
}
while ($start >= $messagetotal AND $messagetotal);

$block_data = array(
	'messagestart' => $start + 1,
	'messageend'   => min($start + $perpage, $messagetotal),
	'fromconverse' => 1,
);
$prepared = array('vm_total' => $messagetotal);

$bbcode = new vB_BbCodeParser($vbulletin, fetch_tag_list());
$factory = new vB_Visitor_MessageFactory($vbulletin, $bbcode, $userinfo2);

$show['conversepage'] = true;

$block_data['messagebits'] = '';
$have_inlinemod = false;
while ($message = $db->fetch_array($messages))
{
	if (in_coventry($message['postuserid']) AND !$vbulletin->GPC['showignored'])
	{
		$message['ignored'] = true;
	}
	if ($message['profileuserid'] == $vbulletin->userinfo['userid'] AND $message['state'] == 'visible' AND !$message['messageread'])
	{
		$read_ids[] = $message['vmid'];
	}
	$response_handler =& $factory->create($message);
	$response_handler->converse = false;
	$response_handler->cachable = false;
	$block_data['messagebits'] .= $response_handler->construct();

	if ($show['inlinemod'])
	{
		$have_inlinemod = true;
	}

	$block_data['lastcomment'] = !$block_data['lastcomment'] ? $message['dateline'] : $block_data['lastcomment'];
}

// our profile and ids that need read
if (!empty($read_ids))
{
	$db->query_write("UPDATE " . TABLE_PREFIX . "visitormessage SET messageread = 1 WHERE vmid IN (" . implode(',', $read_ids) . ")");

	build_visitor_message_counters($vbulletin->userinfo['userid']);
}

$dummydata = array();
$show['delete'] = ($have_inlinemod AND fetch_visitor_message_perm('candeletevisitormessages', $userinfo2));
$show['undelete'] = ($have_inlinemod AND fetch_visitor_message_perm('canundeletevisitormessages', $userinfo2));
$show['approve'] = ($have_inlinemod AND fetch_visitor_message_perm('canmoderatevisitormessages', $userinfo2));
$show['inlinemod'] = ($show['delete'] OR $show['undelete'] OR $show['approve']);

// Only allow AJAX QC on the first page
$show['quickcomment'] = (
	$vbulletin->userinfo['userid']
	AND $viewself
	AND $vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_visitor_messaging']
	AND $userinfo['vm_enable']
	AND $userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers']
	AND (
		!$userinfo['vm_contactonly']
		OR $userinfo['userid'] == $vbulletin->userinfo['userid']
		OR $userinfo['bbuser_iscontact_of_user']
		OR can_moderate(0,'canmoderatevisitormessages')
	)
	AND ((
			$userinfo['userid'] == $vbulletin->userinfo['userid']
			AND $vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['canmessageownprofile']
		)
		OR (
			$userinfo['userid'] != $vbulletin->userinfo['userid']
			AND $vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['canmessageothersprofile']
		)
	)
);
$show['post_visitor_message'] = $show['quickcomment'];

$show['allow_ajax_qc'] = ($pagenumber == 1 AND $messagetotal) ? 1 : 0;
$pagenavbits = array(
	"u=$userinfo[userid]",
	"u2=$userinfo2[userid]",
);
if ($perpage != $vbulletin->options['vm_perpage'])
{
	$pagenavbits[] = "pp=$perpage";
}

if ($vbulletin->GPC['showignored'])
{
	$pagenavbits[] = 'showignored=1';
}

$pagenavurl = 'converse.php?' . $vbulletin->session->vars['sessionurl'] . implode('&amp;', $pagenavbits);
$block_data['pagenav'] = construct_page_nav($pagenumber, $perpage, $messagetotal, $pagenavurl, '');

if ($show['quickcomment'])
{
	require_once(DIR . '/includes/functions_editor.php');

	$block_data['editorid'] = construct_edit_toolbar(
		'',
		false,
		'visitormessage',
		$vbulletin->options['allowsmilies'],
		true,
		false,
		'qr_small',
		'',
		array(),
		'content',
		'vBForum_VisitorMessage',
		0,
		$userinfo['userid']
	);
	$block_data['messagearea'] =& $messagearea;
	$block_data['clientscript'] = $vBeditTemplate['clientscript'];
}

$navbits = construct_navbits(array(
	fetch_seo_url('member', $userinfo) => $userinfo['username'],
	'' => construct_phrase($vbphrase['conversation_between_x_and_y'], $userinfo['username'], $userinfo2['username']),
));
$navbar = render_navbar_template($navbits);

$usercss = construct_usercss($userinfo, $show['usercss_switch']);
$show['usercss_switch'] = ($show['usercss_switch'] AND $vbulletin->userinfo['userid'] != $userinfo['userid']);
construct_usercss_switch($show['usercss_switch'], $usercss_switch_phrase);

($hook = vBulletinHook::fetch_hook('converse_complete')) ? eval($hook) : false;

$templater = vB_Template::create('memberinfo_block_visitormessaging');
	$templater->register('block_data', $block_data);
	$templater->register('prepared', $prepared);
	$templater->register('userinfo', $userinfo);
	$templater->register('userinfo2', $userinfo2);
$html = $templater->render();
$templater = vB_Template::create('converse');
	$templater->register_page_templates();
	$templater->register('html', $html);
	$templater->register('navbar', $navbar);
	$templater->register('pagetitle', $pagetitle);
	$templater->register('usercss', $usercss);
	$templater->register('usercss_switch_phrase', $usercss_switch_phrase);
	$templater->register('userinfo', $userinfo);
print_output($templater->render());

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 16016 $
|| ####################################################################
\*======================================================================*/
?>
