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
define('THIS_SCRIPT', 'blog_inlinemod');
define('CSRF_PROTECTION', true);
define('VBBLOG_PERMS', true);
define('VBBLOG_STYLE', true);
define('VBBLOG_SCRIPT', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'vbblogglobal',
	'vbblogcat',
	'threadmanage',
	'postbit',
);

// get special data templates from the datastore
$specialtemplates = array('blogcategorycache');

if (!$_REQUEST['userid'])
{
	$specialtemplates[] = 'blogtagcloud';
}

// pre-cache templates used by all actions
$globaltemplates = array(
	'BLOG',
	'blog_css',
	'blog_usercss',
	'blog_header_custompage_link',
	'ad_blogsidebar_start',
	'ad_blogsidebar_middle',
	'ad_blogsidebar_end',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'deletetrackback'	=> array(
		'blog_inlinemod_delete_trackbacks',
		'blog_sidebar_category_link',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_entry_link',
		'blog_sidebar_user',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_sidebar_generic',
		'blog_sidebar_user_block_archive',
		'blog_sidebar_user_block_category',
		'blog_sidebar_user_block_comments',
		'blog_sidebar_user_block_entries',
		'blog_sidebar_user_block_search',
		'blog_sidebar_user_block_tagcloud',
		'blog_sidebar_user_block_visitors',
		'blog_sidebar_user_block_custom',
		'blog_tag_cloud_link',
	),
	'deletecomment'   => array(
		'blog_inlinemod_delete_comments',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_sidebar_generic',
		'blog_sidebar_category_link',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_entry_link',
		'blog_sidebar_user',
		'blog_sidebar_user_block_archive',
		'blog_sidebar_user_block_category',
		'blog_sidebar_user_block_comments',
		'blog_sidebar_user_block_entries',
		'blog_sidebar_user_block_search',
		'blog_sidebar_user_block_tagcloud',
		'blog_sidebar_user_block_visitors',
		'blog_sidebar_user_block_custom',
		'blog_tag_cloud_link',
	),
	'deleteentry'     => array(
		'blog_inlinemod_delete_entries',
		'blog_archive_link_li',
		'blog_sidebar_category_link',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_entry_link',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_sidebar_user',
		'blog_sidebar_generic',
		'blog_sidebar_user_block_archive',
		'blog_sidebar_user_block_category',
		'blog_sidebar_user_block_comments',
		'blog_sidebar_user_block_entries',
		'blog_sidebar_user_block_search',
		'blog_sidebar_user_block_tagcloud',
		'blog_sidebar_user_block_visitors',
		'blog_sidebar_user_block_custom',
		'blog_tag_cloud_link',
	),
	'deletepcomment'   => array(
		'blog_inlinemod_delete_profile_comments',
	),
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/blog_functions.php');

verify_blog_url();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$itemlimit = 200;

// This is a list of ids that were checked on the page we submitted from
$vbulletin->input->clean_array_gpc('p', array(
	'trackbacklist' => TYPE_ARRAY_KEYS_INT,
	'commentlist'   => TYPE_ARRAY_KEYS_INT,
	'pcommentlist'  => TYPE_ARRAY_KEYS_INT,
	'bloglist'      => TYPE_ARRAY_KEYS_INT,
	'userid'        => TYPE_UINT,
));

// If we have javascript, all ids should be in here
$vbulletin->input->clean_array_gpc('c', array(
	'vbulletin_inlinetrackback' => TYPE_STR,
	'vbulletin_inlinecomment'   => TYPE_STR,
	'vbulletin_inlineentry'      => TYPE_STR,
	'vbulletin_inlinepcomment'  => TYPE_STR,
));

// Combine ids sent from the form and what we have in the cookie
if (!empty($vbulletin->GPC['vbulletin_inlinetrackback']))
{
	$trackbacklist = explode('-', $vbulletin->GPC['vbulletin_inlinetrackback']);
	$trackbacklist = $vbulletin->input->clean($trackbacklist, TYPE_ARRAY_UINT);

	$vbulletin->GPC['trackbacklist'] = array_unique(array_merge($trackbacklist, $vbulletin->GPC['trackbacklist']));
}

if (!empty($vbulletin->GPC['vbulletin_inlinecomment']))
{
	$commentlist = explode('-', $vbulletin->GPC['vbulletin_inlinecomment']);
	$commentlist = $vbulletin->input->clean($commentlist, TYPE_ARRAY_UINT);

	$vbulletin->GPC['commentlist'] = array_unique(array_merge($commentlist, $vbulletin->GPC['commentlist']));
}

if (!empty($vbulletin->GPC['vbulletin_inlineentry']))
{
	$bloglist = explode('-', $vbulletin->GPC['vbulletin_inlineentry']);
	$bloglist = $vbulletin->input->clean($bloglist, TYPE_ARRAY_UINT);

	$vbulletin->GPC['bloglist'] = array_unique(array_merge($bloglist, $vbulletin->GPC['bloglist']));
}

if (!empty($vbulletin->GPC['vbulletin_inlinepcomment']))
{
	$pcommentlist = explode('-', $vbulletin->GPC['vbulletin_inlinepcomment']);
	$pcommentlist = $vbulletin->input->clean($pcommentlist, TYPE_ARRAY_UINT);

	$vbulletin->GPC['pcommentlist'] = array_unique(array_merge($pcommentlist, $vbulletin->GPC['pcommentlist']));
}

if (!$vbulletin->userinfo['userid'])
{
	print_no_permission();
}

switch ($_POST['do'])
{
	// ######################### POSTS ############################
	case 'deleteentry':
	case 'approveentry':
	case 'unapproveentry':
	case 'undeleteentry':

		if (empty($vbulletin->GPC['bloglist']))
		{
			standard_error(fetch_error('you_did_not_select_any_valid_entries'));
		}

		if (count($vbulletin->GPC['bloglist']) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_entries', $itemlimit));
		}

		if ($vbulletin->GPC['userid'])
		{
			$userinfo = fetch_userinfo($vbulletin->GPC['userid'], 1);
		}

		$blogids = implode(', ', $vbulletin->GPC['bloglist']);
		break;

	case 'dodeleteentry':

		$vbulletin->input->clean_array_gpc('p', array(
			'blogids' => TYPE_STR,
		));
		$blogids = explode(',', $vbulletin->GPC['blogids']);
		$blogids = $vbulletin->input->clean($blogids, TYPE_ARRAY_UINT);

		if (count($blogids) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_entries', $itemlimit));
		}
		break;

	// ######################### COMMENTS ############################
	case 'deletecomment':
	case 'approvecomment':
	case 'unapprovecomment':
	case 'undeletecomment':

		if (empty($vbulletin->GPC['commentlist']))
		{
			standard_error(fetch_error('you_did_not_select_any_valid_comments'));
		}

		if (count($vbulletin->GPC['commentlist']) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_comments', $itemlimit));
		}

		if ($vbulletin->GPC['userid'])
		{
			$userinfo = fetch_userinfo($vbulletin->GPC['userid'], 1);
		}

		$blogtextids = implode(', ', $vbulletin->GPC['commentlist']);
		break;

	case 'dodeletecomment':

		$vbulletin->input->clean_array_gpc('p', array(
			'blogtextids' => TYPE_STR,
		));
		$blogtextids = explode(',', $vbulletin->GPC['blogtextids']);
		$blogtextids = $vbulletin->input->clean($blogtextids, TYPE_ARRAY_UINT);

		if (count($blogtextids) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_comments', $itemlimit));
		}
		break;

	// ######################### PROFILE COMMENTS ############################
	case 'deletepcomment':
	case 'approvepcomment':
	case 'unapprovepcomment':
	case 'undeletepcomment':

		if (empty($vbulletin->GPC['pcommentlist']))
		{
			standard_error(fetch_error('you_did_not_select_any_valid_comments'));
		}

		if (count($vbulletin->GPC['pcommentlist']) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_comments', $itemlimit));
		}

		if ($vbulletin->GPC['userid'])
		{
			$userinfo = fetch_userinfo($vbulletin->GPC['userid'], 1);
		}

		$commentids = implode(', ', $vbulletin->GPC['pcommentlist']);
		break;

	case 'dodeletepcomment':

		$vbulletin->input->clean_array_gpc('p', array(
			'commentids' => TYPE_STR,
		));
		$commentids = explode(',', $vbulletin->GPC['commentids']);
		$commentids = $vbulletin->input->clean($commentids, TYPE_ARRAY_UINT);

		if (count($commentids) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_comments', $itemlimit));
		}
		break;

	// ######################### TRACKBACKS ############################
	case 'deletetrackback':
	case 'approvetrackback':
	case 'unapprovetrackback':

		if (empty($vbulletin->GPC['trackbacklist']))
		{
			standard_error(fetch_error('you_did_not_select_any_valid_trackbacks'));
		}

		if (count($vbulletin->GPC['trackbacklist']) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_trackbacks', $itemlimit));
		}

		if ($vbulletin->GPC['userid'])
		{
			$userinfo = fetch_userinfo($vbulletin->GPC['userid'], 1);
		}

		$trackbackids = implode(', ', $vbulletin->GPC['trackbacklist']);
		break;

	case 'dodeletetrackback':

		$vbulletin->input->clean_array_gpc('p', array(
			'trackbackids' => TYPE_STR,
		));
		$trackbackids = explode(',', $vbulletin->GPC['trackbackids']);
		$trackbackids = $vbulletin->input->clean($trackbackids, TYPE_ARRAY_UINT);

		if (count($trackbackids) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_trackbacks', $itemlimit));
		}
		break;

	case 'cleartrackback':
	case 'clearpcomment':
	case 'clearcomment':
	case 'clearentry':

		break;

	default:
		$handled_do = false;
		($hook = vBulletinHook::fetch_hook('blog_inlinemod_action_switch')) ? eval($hook) : false;
		if (!$handled_do)
		{
			standard_error(fetch_error('invalid_action'));
		}
}

// set forceredirect for IIS
$forceredirect = (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);

// Init

$userarray = array();
$blogarray = array();
$trackbackarray = array();
$commentarray = array();
$bloglist = array();
$userlist = array();

if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']))
{
	$joinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
	$wheresql = "AND (cu.blogcategoryid IS NULL OR blog.userid = " . $vbulletin->userinfo['userid'] . ")";
}
else
{
	$joinsql = $wheresql = '';
}

if ($_POST['do'] == 'clearcomment')
{
	setcookie('vbulletin_inlinecomment', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('blog_inlinemod_clearcomment')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_commentlist_cleared', true, $forceredirect);  
}

if ($_POST['do'] == 'clearpcomment')
{
	setcookie('vbulletin_inlinepcomment', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('blog_inlinemod_clearpcomment')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_commentlist_cleared', true, $forceredirect);  
}

if ($_POST['do'] == 'cleartrackback')
{
	setcookie('vbulletin_inlinetrackback', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('blog_inlinemod_cleartrackback')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_trackbacklist_cleared', true, $forceredirect);  
}

if ($_POST['do'] == 'clearentry')
{
	setcookie('vbulletin_inlineentry', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('blog_inlinemod_clearentry')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_entrylist_cleared', true, $forceredirect);  
}

if ($_POST['do'] == 'approvetrackback' OR $_POST['do'] == 'unapprovetrackback')
{
	$insertrecords = array();

	$approve = $_POST['do'] == 'approvetrackback' ? true : false;

	// Validate Trackbacks
	$trackbacks = $db->query_read_slave("
		SELECT
			bt.blogtrackbackid, bt.state, bt.blogid, bt.userid,
			blog.state AS blog_state, blog.userid AS blog_userid, blog.categories, blog.pending, blog.postedby_userid, blog.postedby_username, blog.options AS blogoptions,
			bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			user2.membergroupids AS blog_membergroupids, user2.usergroupid AS blog_usergroupid, user2.infractiongroupids AS blog_infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog_trackback AS bt
		LEFT JOIN " . TABLE_PREFIX . "blog AS blog USING (blogid)
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = bt.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		$joinsql
		WHERE
			bt.blogtrackbackid IN ($trackbackids)
		 		AND
		 	bt.state = '" . ($approve ? 'moderation' : 'visible') . "'
		 	$wheresql
	");
	while ($trackback = $db->fetch_array($trackbacks))
	{
		$trackback = array_merge($trackback, convert_bits_to_array($trackback['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		$entryinfo = array(
			'state'              => $trackback['blog_state'],
			'blogid'             => $trackback['blogid'],
			'userid'             => $trackback['blog_userid'],
			'usergroupid'        => $trackback['blog_usergroupid'],
			'infractiongroupids' => $trackback['blog_infractiongroupids'],
			'membergroupids'     => $trackback['blog_membergroupids'],
			'memberids'          => $trackback['memberids'],
			'memberblogids'      => $trackback['memberblogids'],
			'postedby_userid'    => $trackback['postedby_userid'],
			'postedby_username'  => $trackback['postedby_username'],
			'grouppermissions'   => $trackback['grouppermissions'],
			'membermoderate'     => $trackback['membermoderate'],
		);

		cache_permissions($trackback, false);
		cache_permissions($entryinfo, false);

		// Check permissions.....
		if (($entryinfo['userid'] != $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])) OR
			($entryinfo['userid'] == $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])))
		{
			print_no_permission();
		}

		if (!fetch_comment_perm('canmoderatecomments', $entryinfo, $trackback))
		{
			print_no_permission();
		}

		$trackbackarray["$trackback[blogtrackbackid]"] = $trackback;
		$bloglist["$trackback[blogid]"] = true;

		if (!$approve)
		{
			$insertrecords[] = "($trackback[blogtrackbackid], 'blogtrackbackid', " . TIMENOW . ")";
		}
	}

	if (empty($trackbackarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_trackbacks'));
	}

	// Set trackback state
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "blog_trackback
		SET
			state = '" . ($approve ? 'visible' : 'moderation') . "'
		WHERE
			blogtrackbackid IN (" . implode(',', array_keys($trackbackarray)) . ")
	");

	if ($approve)
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_moderation
			WHERE
				primaryid IN(" . implode(',', array_keys($trackbackarray)) . ")
					AND
				type = 'blogtrackback'
		");
	}
	else
	{
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "blog_moderation
				(primaryid, type, dateline)
			VALUES
				" . implode(',', $insertrecords) . "
		");
	}

	$modlog = array();
	foreach(array_keys($trackbackarray) AS $blogtrackbackid)
	{
		$modlog[] = array(
			'userid'   =>& $vbulletin->userinfo['userid'],
			'id1'      =>& $trackbackarray["$blogtrackbackid"]['blog_userid'],
			'id2'      =>& $trackbackarray["$blogtrackbackid"]['blogid'],
			'id5'      =>  $blogtrackbackid,
		);
	}

	require_once(DIR . '/includes/blog_functions_log_error.php');
	blog_moderator_action($modlog, $approve ? 'trackback_approved' : 'trackback_unapproved');

	foreach (array_keys($bloglist) AS $blogid)
	{
		build_blog_entry_counters($blogid);
	}

	setcookie('vbulletin_inlinetrackback', '', TIMENOW - 3600, '/');

	// hook

	if ($approve)
	{
		print_standard_redirect('redirect_inline_approvedtrackbacks', true, $forceredirect);  
	}
	else
	{
		print_standard_redirect('redirect_inline_unapprovedtrackbacks', true, $forceredirect);  
	}
}

if ($_POST['do'] == 'approvecomment' OR $_POST['do'] == 'unapprovecomment')
{
	$insertrecords = array();

	$approve = $_POST['do'] == 'approvecomment' ? true : false;

	// Validate records
	$comments = $db->query_read_slave("
		SELECT
			bt.blogtextid, bt.state, bt.blogid, bt.userid, bt.dateline,
			blog.state AS blog_state, blog.userid AS blog_userid, blog.pending, blog.categories, blog.postedby_userid, blog.postedby_username, blog.options AS blogoptions,
			bu.lastcomment, bu.lastblogtextid, bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			user2.membergroupids AS blog_membergroupids, user2.usergroupid AS blog_usergroupid, user2.infractiongroupids AS blog_infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog_text AS bt
		LEFT JOIN " . TABLE_PREFIX . "blog AS blog ON (blog.blogid = bt.blogid)
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = bt.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		$joinsql
		WHERE blogtextid IN ($blogtextids)
		 AND bt.state IN (" . ($approve ? "'moderation'" : "'visible', 'deleted'") . ")
		 AND blogtextid <> firstblogtextid
		 $wheresql
	");

	while ($comment = $db->fetch_array($comments))
	{
		$comment = array_merge($comment, convert_bits_to_array($comment['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		$entryinfo = array(
			'blogid'             => $comment['blogid'],
			'userid'             => $comment['blog_userid'],
			'usergroupid'        => $comment['blog_usergroupid'],
			'infractiongroupids' => $comment['blog_infractiongroupids'],
			'membergroupids'     => $comment['blog_membergroupids'],
			'memberids'          => $comment['memberids'],
			'memberblogids'      => $comment['memberblogids'],
			'postedby_userid'    => $comment['postedby_userid'],
			'postedby_username'  => $comment['postedby_username'],
			'grouppermissions'   => $comment['grouppermissions'],
			'membermoderate'     => $comment['membermoderate'],
		);

		cache_permissions($comment, false);
		cache_permissions($entryinfo, false);

		// Check permissions.....
		if (($comment['blog_userid'] != $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])) OR
			($comment['blog_userid'] == $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])))
		{
			print_no_permission();
		}

		if (!fetch_comment_perm('canmoderatecomments', $entryinfo, $comment))
		{
			print_no_permission();
		}
		else if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']) AND $entryinfo['userid'] != $vbulletin->userinfo['userid'])
		{
			$categories = explode(',', $comment['categories']);
			if (array_intersect($vbulletin->userinfo['blogcategorypermissions']['cantview'], $categories))
			{
				standard_error(fetch_error('you_do_not_have_permission_to_view_category'));
			}
		}

		$commentarray["$comment[blogtextid]"] = $comment;
		$bloglist["$comment[blogid]"] = true;
		$userlist["$entryinfo[userid]"] = true;

		if (!$approve)
		{
			$insertrecords[] = "($comment[blogtextid], 'blogtextid', " . TIMENOW . ")";
		}
	}

	if (empty($commentarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_comments'));
	}

	// Set comment state
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "blog_text
		SET state = '" . ($approve ? 'visible' : 'moderation') . "'
		WHERE blogtextid IN (" . implode(',', array_keys($commentarray)) . ")
	");

	if ($approve)
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_moderation
			WHERE primaryid IN(" . implode(',', array_keys($commentarray)) . ")
				AND type = 'blogtextid'
		");
	}
	else	// Unapprove
	{
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "blog_moderation
				(primaryid, type, dateline)
			VALUES
				" . implode(',', $insertrecords) . "
		");

		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_deletionlog
			WHERE type = 'blogtextid' AND
				primaryid IN(" . implode(',', array_keys($commentarray)) . ")
		");
	}

	// Logging?

	foreach (array_keys($bloglist) AS $blogid)
	{
		build_blog_entry_counters($blogid);
	}

	foreach (array_keys($userlist) AS $userid)
	{
		build_blog_user_counters($userid);
	}

	setcookie('vbulletin_inlinecomment', '', TIMENOW - 3600, '/');

	// hook

	if ($approve)
	{
		print_standard_redirect('redirect_inline_approvedcomments', true, $forceredirect);  
	}
	else
	{
		print_standard_redirect('redirect_inline_unapprovedcomments', true, $forceredirect);  
	}
}

if ($_POST['do'] == 'approveentry' OR $_POST['do'] == 'unapproveentry')
{
	$insertrecords = array();

	$approve = $_POST['do'] == 'approveentry' ? true : false;
	$visibleposts = array();
	$invisibleposts = array();

	// Validate records
	$posts = $db->query_read_slave("
		SELECT
			blog.blogid, blog.userid, blog.state, blog.pending, blog.options AS blogoptions, blog.postedby_userid,
			bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog AS blog
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		$joinsql
		WHERE
			blog.blogid IN ($blogids)
		 		AND
		 	blog.state IN (" . ($approve ? "'moderation'" : "'visible', 'deleted'") . ")
		 	$wheresql
	");
	while ($post = $db->fetch_array($posts))
	{
		$post = array_merge($post, convert_bits_to_array($post['blogoptions'], $vbulletin->bf_misc_vbblogoptions));

		// Check permissions.....
		if (($post['userid'] != $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])) OR
			($post['userid'] == $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])))
		{
			print_no_permission();
		}

		cache_permissions($post, false);
		if (!fetch_entry_perm('moderate', $post))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_entries'));
		}

		$blogarray["$post[blogid]"] = $post;
		if ($post['userid'])
		{
			if (!empty($userlist["$post[userid]"]))
			{
				$userlist["$post[userid]"]++;
			}
			else
			{
				$userlist["$post[userid]"] = 1;
			}
		}

		if (!$approve)
		{
			$insertrecords[] = "($post[blogid], 'blogid', " . TIMENOW . ")";
		}

		if ($post['state'] == 'visible')
		{
			$visibleposts[] = $post['blogid'];
		}
		else
		{
			$invisibleposts[] = $post['blogid'];
		}
	}

	if (empty($blogarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_entries'));
	}

	if (!empty($userlist))
	{
		$bycount = array();
		foreach ($userlist AS $userid => $total)
		{
			$bycount["$total"][] = $userid;
		}

		$casesql = array();
		foreach ($bycount AS $total => $userids)
		{
			$casesql[] = " WHEN bloguserid IN (" . implode(',', $userids) . ") THEN $total";
		}

		if (!empty($casesql))
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "blog_user
				SET entries = entries " . ($approve ? "+" : "-") . "
				CASE
					" . implode("\n", $casesql) . "
					ELSE 0
				END
				WHERE bloguserid IN (" . implode(',', array_keys($userlist)) . ")
			");
		}
	}

	if ($approve)
	{
		// Set post state
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "blog AS blog
			SET
				blog.state = 'visible',
				options = options & ~" . $vbulletin->bf_misc_vbblogoptions['membermoderate'] . "
			WHERE blog.blogid IN (" . implode(',', array_keys($blogarray)) . ")
		");

		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_moderation
			WHERE primaryid IN(" . implode(',', array_keys($blogarray)) . ")
				AND type = 'blogid'
		");
	}
	else
	{
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "blog AS blog
			SET
				blog.state = 'moderation',
				options = options & ~" . $vbulletin->bf_misc_vbblogoptions['membermoderate'] . "
			WHERE blog.blogid IN (" . implode(',', array_keys($blogarray)) . ")
		");

		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "blog_moderation
				(primaryid, type, dateline)
			VALUES
				" . implode(',', $insertrecords) . "
		");

		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_deletionlog
			WHERE type = 'blogid' AND
				primaryid IN(" . implode(',', array_keys($blogarray)) . ")
		");
	}

	foreach (array_keys($blogarray) AS $blogid)
	{
		build_blog_entry_counters($blogid);
		$modlog[] = array(
			'userid' =>& $vbulletin->userinfo['userid'],
			'id1'    =>& $blogarray["$blogid"]['userid'],
			'id2'    =>  $blogid,
		);
	}

	require_once(DIR . '/includes/blog_functions_log_error.php');
	blog_moderator_action($modlog, $approve ? 'blogentry_approved' : 'blogentry_unapproved');

	foreach(array_keys($userlist) AS $userid)
	{
		build_blog_user_counters($userid);
	}
	build_blog_stats();
	setcookie('vbulletin_inlineentry', '', TIMENOW - 3600, '/');

	// hook

	if ($approve)
	{
		print_standard_redirect('redirect_inline_approvedposts', true, $forceredirect);  
	}
	else
	{
		print_standard_redirect('redirect_inline_unapprovedposts', true, $forceredirect);  
	}
}

if ($_POST['do'] == 'deletetrackback')
{
	// Trackbacks might need a soft deletion option

	// Validate Trackbacks
	$trackbacks = $db->query_read_slave("
		SELECT
			bt.blogtrackbackid, bt.state, bt.blogid, bt.userid, bt.url,
			blog.state AS blog_state, blog.userid AS blog_userid, blog.categories, blog.pending, blog.postedby_userid, blog.postedby_username, blog.options AS blogoptions,
			bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			user2.membergroupids AS blog_membergroupids, user2.usergroupid AS blog_usergroupid, user2.infractiongroupids AS blog_infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog_trackback AS bt
		LEFT JOIN " . TABLE_PREFIX . "blog AS blog USING (blogid)
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = bt.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		$joinsql
		WHERE
			bt.blogtrackbackid IN ($trackbackids)
			$wheresql
	");
	while ($trackback = $db->fetch_array($trackbacks))
	{
		$trackback = array_merge($trackback, convert_bits_to_array($trackback['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		$entryinfo = array(
			'state'              => $trackback['blog_state'],
			'blogid'             => $trackback['blogid'],
			'userid'             => $trackback['blog_userid'],
			'usergroupid'        => $trackback['blog_usergroupid'],
			'infractiongroupids' => $trackback['blog_infractiongroupids'],
			'membergroupids'     => $trackback['blog_membergroupids'],
			'memberids'          => $trackback['memberids'],
			'memberblogids'      => $trackback['memberblogids'],
			'postedby_userid'    => $trackback['postedby_userid'],
			'postedby_username'  => $trackback['postedby_username'],
			'grouppermissions'   => $trackback['grouppermissions'],
			'membermoderate'     => $trackback['membermoderate'],
		);

		cache_permissions($trackback, false);
		cache_permissions($entryinfo, false);

		// Check permissions.....
		if (($entryinfo['userid'] != $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])) OR
			($entryinfo['_userid'] == $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])))
		{
			print_no_permission();
		}
		if (!fetch_comment_perm('canremovecomments', $entryinfo, $comment))
		{
			print_no_permission();
		}

		$trackbackarray["$trackback[blogtrackbackid]"] = $trackback;
		$bloglist["$trackback[blogid]"] = true;
	}

	if (empty($trackbackarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_trackbacks'));
	}

	$trackbackcount = count($trackbackarray);
	$blogcount = count($bloglist);

	// hook
	// draw navbar

	$navbits = array();
	$navbits[fetch_seo_url('entry', $bloginfo)] = $bloginfo['title'];
	$navbits[''] = $vbphrase['delete_trackbacks'];

	$url =& $vbulletin->url;
	$templater = vB_Template::create('blog_inlinemod_delete_trackbacks');
		$templater->register('blogcount', $blogcount);
		$templater->register('bloginfo', $bloginfo);
		$templater->register('trackbackcount', $trackbackcount);
		$templater->register('trackbackids', $trackbackids);
		$templater->register('url', $url);
	$content = $templater->render();
}

if ($_POST['do'] == 'dodeletetrackback')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'pinghistory' => TYPE_BOOL,
	));

	// Validate Trackbacks
	$trackbacks = $db->query_read_slave("
		SELECT
			bt.blogtrackbackid, bt.state, bt.blogid, bt.userid, bt.url,
			blog.state AS blog_state, blog.userid AS blog_userid, blog.categories, blog.pending, blog.postedby_userid, blog.postedby_username, blog.options AS blogoptions,
			bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			user2.membergroupids AS blog_membergroupids, user2.usergroupid AS blog_usergroupid, user2.infractiongroupids AS blog_infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog_trackback AS bt
		LEFT JOIN " . TABLE_PREFIX . "blog AS blog USING (blogid)
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = bt.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		$joinsql
		WHERE
			bt.blogtrackbackid IN (" . implode(',', $trackbackids) . ")
			$wheresql
	");
	while ($trackback = $db->fetch_array($trackbacks))
	{
		$trackback = array_merge($trackback, convert_bits_to_array($trackback['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		$entryinfo = array(
			'blogid'             => $comment['blogid'],
			'userid'             => $comment['blog_userid'],
			'usergroupid'        => $comment['blog_usergroupid'],
			'infractiongroupids' => $comment['blog_infractiongroupids'],
			'membergroupids'     => $comment['blog_membergroupids'],
			'memberids'          => $comment['memberids'],
			'memberblogids'      => $comment['memberblogids'],
			'postedby_userid'    => $comment['postedby_userid'],
			'postedby_username'  => $comment['postedby_username'],
			'grouppermissions'   => $comment['grouppermissions'],
			'membermoderate'     => $comment['membermoderate'],
		);

		cache_permissions($trackback, false);
		cache_permissions($entryinfo, false);

		// Check permissions.....
		if (($entryinfo['userid'] != $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])) OR
			($entryinfo['_userid'] == $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])))
		{
			print_no_permission();
		}
		if (!fetch_comment_perm('canremovecomments', $entryinfo, $comment))
		{
			print_no_permission();
		}

		$trackbackarray["$trackback[blogtrackbackid]"] = $trackback;
		$bloglist["$trackback[blogid]"] = true;
	}

	if (empty($trackbackarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_trackbacks'));
	}

	foreach($trackbackarray AS $trackbackid => $trackback)
	{
		$dataman =& datamanager_init('Blog_Trackback', $vbulletin, ERRTYPE_SILENT);
		$dataman->set_existing($trackback);
		$dataman->set_info('skip_build_blog_counters', true);
		if ($vbulletin->GPC['pinghistory'])
		{
			$dataman->set_info('delete_ping_history', true);
		}
		$dataman->delete();
		unset($dataman);
	}

	foreach(array_keys($bloglist) AS $blogid)
	{
		build_blog_entry_counters($blogid);
	}

	// empty cookie
	setcookie('vbulletin_inlinetrackback', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('blog_inlinemod_dodeletetrackbacks')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_deletedtrackbacks', true, $forceredirect);  
}

if ($_POST['do'] == 'deletecomment')
{
	$show['removecomments'] = false;
	$show['deletecomments'] = false;
	$show['deleteoption'] = false;
	$checked = array('delete' => 'checked="checked"');

	// Validate Comments
	$comments = $db->query_read_slave("
		SELECT
			bt.blogtextid, bt.state, bt.blogid, bt.userid,
			blog.state AS blog_state, blog.userid AS blog_userid, blog.categories, blog.pending, blog.postedby_userid, blog.postedby_username, blog.options AS blogoptions,
			bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			user2.membergroupids AS blog_membergroupids, user2.usergroupid AS blog_usergroupid, user2.infractiongroupids AS blog_infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog_text AS bt
		LEFT JOIN " . TABLE_PREFIX . "blog AS blog USING (blogid)
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = bt.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		$joinsql
		WHERE
			bt.blogtextid IN ($blogtextids)
				AND
			bt.blogtextid <> blog.firstblogtextid
			$wheresql
	");
	while ($comment = $db->fetch_array($comments))
	{
		$comment = array_merge($comment, convert_bits_to_array($comment['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		$entryinfo = array(
			'blogid'             => $comment['blogid'],
			'userid'             => $comment['blog_userid'],
			'usergroupid'        => $comment['blog_usergroupid'],
			'infractiongroupids' => $comment['blog_infractiongroupids'],
			'membergroupids'     => $comment['blog_membergroupids'],
			'memberids'          => $comment['memberids'],
			'memberblogids'      => $comment['memberblogids'],
			'postedby_userid'    => $comment['postedby_userid'],
			'postedby_username'  => $comment['postedby_username'],
			'grouppermissions'   => $comment['grouppermissions'],
			'membermoderate'     => $comment['membermoderate'],
		);

		cache_permissions($comment, false);
		cache_permissions($entryinfo, false);

		// Check permissions.....
		if (($comment['blog_userid'] != $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])) OR
			($comment['blog_userid'] == $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])))
		{
			print_no_permission();
		}

		if (!fetch_comment_perm('candeletecomments', $entryinfo, $comment) AND !fetch_comment_perm('canremovecomments', $entryinfo, $comment))
		{
			print_no_permission();
		}
		else
		{
			if (fetch_comment_perm('candeletecomments', $entryinfo, $comment))
			{
				$show['deletecomments'] = true;
			}
			if (fetch_comment_perm('canremovecomments', $entryinfo, $comment))
			{
				$show['removecomments'] = true;
				if (!fetch_comment_perm('candeletecomments', $entryinfo, $comment))
				{
					$checked = array('remove' => 'checked="checked"');
				}
			}

			if (fetch_comment_perm('candeletecomments', $entryinfo, $comment) AND fetch_comment_perm('canremovecomments', $entryinfo, $comment))
			{
				$show['deleteoption'] = true;
			}
		}

		$commentarray["$comment[blogtextid]"] = $comment;
		$bloglist["$entryinfo[blogid]"] = true;
	}

	if (empty($commentarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_comments'));
	}

	$commentcount = count($commentarray);
	$blogcount = count($bloglist);

	// hook
	// draw navbar

	$navbits = array(
		fetch_seo_url('entry', $bloginfo) => $bloginfo['title'],
		'' => $vbphrase['delete_comments']
	);

	$url =& $vbulletin->url;
	$templater = vB_Template::create('blog_inlinemod_delete_comments');
		$templater->register('blogcount', $blogcount);
		$templater->register('bloginfo', $bloginfo);
		$templater->register('blogtextids', $blogtextids);
		$templater->register('blogtextinfo', $blogtextinfo);
		$templater->register('checked', $checked);
		$templater->register('commentcount', $commentcount);
		$templater->register('url', $url);
	$content = $templater->render();
}

if ($_POST['do'] == 'dodeletecomment')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deletetype'   => TYPE_UINT, // 1 - Soft Deletion, 2 - Physically Remove
		'deletereason' => TYPE_NOHTMLCOND,
	));

	$physicaldel = ($vbulletin->GPC['deletetype'] == 2) ? true : false;

	// Validate Comments
	$comments = $db->query_read_slave("
		SELECT
			bt.blogtextid, bt.state, bt.blogid, bt.userid,
			blog.state AS blog_state, blog.userid AS blog_userid, blog.categories, blog.pending, 
				blog.postedby_userid, blog.postedby_username, blog.options AS blogoptions,
			bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			user2.membergroupids AS blog_membergroupids, user2.usergroupid AS blog_usergroupid, 
				user2.infractiongroupids AS blog_infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog_text AS bt
		LEFT JOIN " . TABLE_PREFIX . "blog AS blog USING (blogid)
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = bt.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		$joinsql
		WHERE
			blogtextid IN (" . implode(',', $blogtextids) . ")
				AND
			bt.blogtextid <> blog.firstblogtextid
			$wheresql
	");
	while ($comment = $db->fetch_array($comments))
	{
		$comment = array_merge($comment, convert_bits_to_array($comment['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		$entryinfo = array(
			'blogid'             => $comment['blogid'],
			'userid'             => $comment['blog_userid'],
			'usergroupid'        => $comment['blog_usergroupid'],
			'infractiongroupids' => $comment['blog_infractiongroupids'],
			'membergroupids'     => $comment['blog_membergroupids'],
			'memberids'          => $comment['memberids'],
			'memberblogids'      => $comment['memberblogids'],
			'postedby_userid'    => $comment['postedby_userid'],
			'postedby_username'  => $comment['postedby_username'],
			'grouppermissions'   => $comment['grouppermissions'],
			'membermoderate'     => $comment['membermoderate'],
		);

		cache_permissions($comment, false);
		cache_permissions($entryinfo, false);

		if (
			($physicaldel AND !fetch_comment_perm('canremovecomments', $entryinfo, $comment)) OR 
			(!$physicaldel AND !fetch_comment_perm('candeletecomments', $entryinfo, $comment))
		)
		{
			standard_error(fetch_error('you_do_not_have_permission_to_delete_comments'));
		}

		$commentarray["$comment[blogtextid]"] = $comment;
		$bloglist["$comment[blogid]"] = true;
		$userlist["$comment[blog_userid]"] = true;
	}

	if (empty($commentarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_comments'));
	}

	$gotoblog = true;
	foreach($commentarray AS $blogtextid => $comment)
	{
		$dataman =& datamanager_init('BlogText', $vbulletin, ERRTYPE_SILENT, 'blog');
		$dataman->set_existing($comment);
		$dataman->set_info('skip_build_blog_counters', true);
		$dataman->set_info('hard_delete', $physicaldel);
		$dataman->set_info('reason', $vbulletin->GPC['deletereason']);
		$dataman->delete();
		unset($dataman);

		if ($vbulletin->GPC['blogid'] == $comment['blogid'] AND $comment['blogtextid'] == $comment['firstblogtextid'])
		{
			$gotoblog = false;
		}
		//I can't find a place where we allow physical deletes of blog posts.  This branch may not be
		//possible to run. 
		else if ($comment['blogtextid'] == $blogtextinfo['blogtextid'] AND $physicaldel)
		{
			//if we have information for a specific blog entry, return there, otherwise return to blog home
			//(the same as the previous behavior, but the latter is possible erroneous -- there is was check
			//to see if $vbulletin->GPC['blogid'] was actually set.
			//$bloginfo (and $vbulletin->GPC['blogid']) are set up in the blog plugin code (if blogid is set)
			if($bloginfo)
			{
				$vbulletin->url = fetch_seo_url('entry', $bloginfo);
			}
			else
			{
				$vbulletin->url = fetch_seo_url('bloghome', array());
			}
		}
	}

	foreach(array_keys($bloglist) AS $blogid)
	{
		build_blog_entry_counters($blogid);
	}

	foreach(array_keys($userlist) AS $userid)
	{
		build_blog_user_counters($userid);
	}

	// empty cookie
	setcookie('vbulletin_inlinecomment', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('blog_inlinemod_dodeletecomments')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_deletedcomments', true, $forceredirect);  
}

if ($_POST['do'] == 'undeletecomment')
{
	// Validate Comments
	$comments = $db->query_read_slave("
		SELECT
			bt.blogtextid, bt.state, bt.blogid, bt.userid,
			blog.state AS blog_state, blog.userid AS blog_userid, blog.categories, blog.pending, blog.postedby_userid, blog.postedby_username, blog.options AS blogoptions,
			bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			user2.membergroupids AS blog_membergroupids, user2.usergroupid AS blog_usergroupid, user2.infractiongroupids AS blog_infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog_text AS bt
		LEFT JOIN " . TABLE_PREFIX . "blog AS blog USING (blogid)
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = bt.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		$joinsql
		WHERE
			blogtextid IN ($blogtextids)
				AND
			blogtextid <> firstblogtextid
				AND
			bt.state = 'deleted'
			$wheresql
	");
	while ($comment = $db->fetch_array($comments))
	{
		$comment = array_merge($comment, convert_bits_to_array($comment['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		$entryinfo = array(
			'blogid'             => $comment['blogid'],
			'userid'             => $comment['blog_userid'],
			'usergroupid'        => $comment['blog_usergroupid'],
			'infractiongroupids' => $comment['blog_infractiongroupids'],
			'membergroupids'     => $comment['blog_membergroupids'],
			'memberids'          => $comment['memberids'],
			'memberblogids'      => $comment['memberblogids'],
			'postedby_userid'    => $comment['postedby_userid'],
			'postedby_username'  => $comment['postedby_username'],
			'grouppermissions'   => $comment['grouppermissions'],
			'membermoderate'     => $comment['membermoderate'],
		);

		cache_permissions($comment, false);
		cache_permissions($entryinfo, false);

		if (!fetch_comment_perm('canundeletecomments', $entryinfo, $comment))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_comments'));
		}

		$commentarray["$comment[blogtextid]"] = $comment;
		$bloglist["$comment[blogid]"] = true;

		if ($comment['dateline'] >= $comment['lastcomment'])
		{
			$userlist["$entryinfo[userid]"] = true;
		}
	}

	if (empty($commentarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_comments'));
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "blog_deletionlog
		WHERE type = 'blogtextid' AND
			primaryid IN(" . implode(',', array_keys($commentarray)) . ")
	");
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "blog_text
		SET state = 'visible'
		WHERE blogtextid IN(" . implode(',', array_keys($commentarray)) . ")
	");

	foreach(array_keys($bloglist) AS $blogid)
	{
		build_blog_entry_counters($blogid);
	}

	foreach(array_keys($userlist) AS $userid)
	{
		build_blog_user_counters($userid);
	}

	$modlog = array();
	foreach(array_keys($commentarray) AS $blogtextid)
	{
		$modlog[] = array(
			'userid'   =>& $vbulletin->userinfo['userid'],
			'id1'      =>& $commentarray["$blogtextid"]['blog_userid'],
			'id2'      =>& $commentarray["$blogtextid"]['blogid'],
			'id3'      =>  $blogtextid,
			'username' =>& $commentarray["$blogtextid"]['username'],
		);
	}

	require_once(DIR . '/includes/blog_functions_log_error.php');
	blog_moderator_action($modlog, 'comment_x_by_y_undeleted');

	// empty cookie
	setcookie('vbulletin_inlinecomment', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('blog_inlinemod_undeletecomments')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_undeletedcomments', true, $forceredirect);  
}

if ($_POST['do'] == 'deleteentry')
{
	$show['removeentries'] = false;
	$show['deleteentries'] = false;
	$show['deleteoption'] = false;
	$checked = array('delete' => 'checked="checked"');

	// Validate Posts
	$posts = $db->query_read_slave("
		SELECT
			blog.blogid, blog.userid, blog.state, blog.pending, blog.options AS blogoptions, blog.postedby_userid,
			bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog AS blog
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		$joinsql
		WHERE
			blog.blogid IN ($blogids)
			$wheresql
	");
	while ($post = $db->fetch_array($posts))
	{
		$post = array_merge($post, convert_bits_to_array($post['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		// Check permissions.....
		if (($post['userid'] != $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])) OR
			($post['userid'] == $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])))
		{
			print_no_permission();
		}

		cache_permissions($post, false);
		if (fetch_entry_perm('delete', $post) OR fetch_entry_perm('remove', $post))
		{
			$show['deleteentries'] = (fetch_entry_perm('delete', $post));
			if (fetch_entry_perm('remove', $post))
			{
				$show['removeentries'] = true;
				if (!fetch_entry_perm('delete', $post))
				{
					$checked = array('remove' => 'checked="checked"');
				}
			}
			$show['deleteoption'] = (fetch_entry_perm('delete', $post) AND fetch_entry_perm('remove', $post));
			$show['delete'] = true;
		}
		else
		{
			print_no_permission();
		}

		$blogarray["$post[blogid]"] = $post;
		$userarray["$post[userid]"] = true;
	}

	if (empty($blogarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_entries'));
	}

	$blogcount = count($blogarray);
	$usercount = count($userarray);

	// hook
	// draw navbar

	$navbits = array(
		//This doesn't actually work.  There is not $bloginfo array defined for this action.  
		//Its not entirely clear what should be here since we are potentially deleting entries across multiple user
		//blogs, so a specific blog would require some dancing to determine if there is a single user blog to 
		//link to.  We could link to blog home, but in the end its not that important.  This produces the same
		//immediate html as we get without it (items with blank labels must get filtered out) and there haven't
		//been any complaints with that behavior. 
		//'blog.php?' . $vbulletin->session->vars['sessionurl'] . "b=$bloginfo[blogid]" => $bloginfo['title'],
		'' => $vbphrase['delete_blog_entries']
	);

	$url =& $vbulletin->url;
	$templater = vB_Template::create('blog_inlinemod_delete_entries');
		$templater->register('blogcount', $blogcount);
		$templater->register('blogids', $blogids);
		$templater->register('bloginfo', $bloginfo);
		$templater->register('checked', $checked);
		$templater->register('url', $url);
		$templater->register('usercount', $usercount);
	$content = $templater->render();
}

if ($_POST['do'] == 'dodeleteentry')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deletetype'   => TYPE_UINT, // 1 - Soft Deletion, 2 - Physically Remove
		'deletereason' => TYPE_NOHTMLCOND,
	));

	$physicaldel = ($vbulletin->GPC['deletetype'] == 2) ? true : false;
	$visibleposts = array();
	$visibleuserlist = array();
	$invisibleuserlist = array();

	// Validate Posts
	$posts = $db->query_read_slave("
		SELECT
			blog.blogid, blog.userid, blog.state, blog.pending, blog.options AS blogoptions, blog.postedby_userid,
			bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog AS blog
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		$joinsql
		WHERE
			blog.blogid IN (" . implode(',', $blogids) . ")
			$wheresql
	");
	while ($post = $db->fetch_array($posts))
	{
		$post = array_merge($post, convert_bits_to_array($post['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		// Check permissions.....
		if (($post['userid'] != $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])) OR
			($post['userid'] == !($vbulletin->userinfo['userid'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])))
		{
			print_no_permission();
		}

		cache_permissions($post, false);
		if (fetch_entry_perm('remove', $post) OR fetch_entry_perm('delete', $post))
		{
			if (($physicaldel AND !fetch_entry_perm('remove', $post)) OR (!$physicaldel AND !fetch_entry_perm('delete', $post)))
			{
				standard_error(fetch_error('you_do_not_have_permission_to_remove_blog_entries'));
			}
		}
		else
		{
			standard_error(fetch_error('you_do_not_have_permission_to_remove_blog_entries'));
		}

		$blogarray["$post[blogid]"] = $post;

		if ($post['state'] == 'visible')
		{
			if (empty($visibleuserlist["$post[userid]"]))
			{
				$visibleuserlist["$post[userid]"] = 1;
			}
			else
			{
				$visibleuserlist["$post[userid]"]++;
			}
		}
		else
		{
			if (empty($invisibleuserlist["$post[userid]"]))
			{
				$invisibleuserlist["$post[userid]"] = 1;
			}
			else
			{
				$invisibleuserlist["$post[userid]"]++;
			}
		}
	}

	if (empty($blogarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_entries'));
	}

	foreach($blogarray AS $blogid => $blog)
	{
		$dataman =& datamanager_init('Blog', $vbulletin, ERRTYPE_SILENT, 'blog');
		$dataman->set_existing($blog);
		$dataman->set_info('skip_build_blog_counters', true);
		$dataman->set_info('skip_build_category_counters', true);
		if ($blog['state'] == 'draft' OR $blog['pending'])
		{	// Always perm delete drafts - only the owner can do this
			$dataman->set_info('hard_delete', true);
		}
		else
		{
			$dataman->set_info('hard_delete', $physicaldel);
		}
		$dataman->set_info('reason', $vbulletin->GPC['deletereason']);
		$dataman->delete();
		unset($dataman);
	}

	if (!empty($visibleuserlist))
	{
		$bycount = array();
		foreach ($visibleuserlist AS $userid => $total)
		{
			$bycount["$total"][] = $userid;
		}

		$casesql = array();
		foreach ($bycount AS $total => $userids)
		{
			$casesql[] = " WHEN bloguserid IN (" . implode(',', $userids) . ") THEN $total";
		}

		if (!empty($casesql))
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "blog_user
				SET entries = entries -
				CASE
					" . implode("\n", $casesql) . "
					ELSE 0
				END
				WHERE bloguserid IN (" . implode(',', array_keys($visibleuserlist)) . ")
			");
		}
	}

	foreach (array_unique(array_merge(array_keys($invisibleuserlist),  array_keys($visibleuserlist))) AS $userid)
	{
		build_blog_user_counters($userid);
	}
	build_blog_stats();

	// empty cookie
	setcookie('vbulletin_inlineentry', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('blog_inlinemod_dodeleteentries')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_deletedentries', true, $forceredirect);  
}

if ($_POST['do'] == 'undeleteentry')
{
	// Validate Entries
	$posts = $db->query_read_slave("
		SELECT
			blog.blogid, blog.userid, blog.state, blog.pending, blog_deletionlog.userid AS del_userid, blog.options AS blogoptions, blog.postedby_userid,
			bu.memberids, bu.memberblogids,
			user.membergroupids, user.usergroupid, user.infractiongroupids,
			gm.permissions AS grouppermissions
		FROM " . TABLE_PREFIX . "blog AS blog
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
		LEFT JOIN " . TABLE_PREFIX . "blog_deletionlog AS blog_deletionlog ON (blog.blogid = blog_deletionlog.primaryid AND blog_deletionlog.type = 'blogid')
		$joinsql
		WHERE
			blog.blogid IN ($blogids)
				AND
			blog.state = 'deleted'
			$wheresql
	");
	while ($post = $db->fetch_array($posts))
	{
		$post = array_merge($post, convert_bits_to_array($post['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		// Check permissions.....
		if (($post['userid'] != $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])) OR
			($post['userid'] == $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])))
		{
			print_no_permission();
		}

		cache_permissions($post, false);
		if (!fetch_entry_perm('undelete', $post))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_entries'));
		}

		$blogarray["$post[blogid]"] = $post;

		if (empty($userlist["$post[userid]"]))
		{
			$userlist["$post[userid]"] = 1;
		}
		else
		{
			$userlist["$post[userid]"]++;
		}
	}

	if (empty($blogarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_entries'));
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "blog_deletionlog
		WHERE type = 'blogid' AND
			primaryid IN(" . implode(',', array_keys($blogarray)) . ")
	");

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "blog
		SET state = 'visible'
		WHERE blogid IN(" . implode(',', array_keys($blogarray)) . ")
	");

	if (!empty($userlist))
	{
		$bycount = array();
		foreach ($userlist AS $userid => $total)
		{
			$bycount["$total"][] = $userid;
		}

		$casesql = array();
		foreach ($bycount AS $total => $userids)
		{
			$casesql[] = " WHEN bloguserid IN (" . implode(',', $userids) . ") THEN $total";
		}

		if (!empty($casesql))
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "blog_user
				SET entries = entries +
				CASE
					" . implode("\n", $casesql) . "
					ELSE 0
				END
				WHERE bloguserid IN (" . implode(',', array_keys($userlist)) . ")
			");
		}
	}

	$modlog = array();

	foreach(array_keys($blogarray) AS $blogid)
	{
		build_blog_entry_counters($blogid);
		$modlog[] = array(
			'userid' =>& $vbulletin->userinfo['userid'],
			'id1'    =>& $blogarray["$blogid"]['userid'],
			'id2'    =>  $blogid,
		);
	}

	require_once(DIR . '/includes/blog_functions_log_error.php');
	blog_moderator_action($modlog, 'blogentry_undeleted');

	foreach (array_keys($userlist) AS $userid)
	{
		build_blog_user_counters($userid);
	}
	build_blog_stats();

	// empty cookie
	setcookie('vbulletin_inlineentry', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('blog_inlinemod_undeleteentries')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_undeletedentries', true, $forceredirect);  
}

if ($userinfo)
{
	$sidebar =& build_user_sidebar($userinfo);
}
else
{
	$sidebar =& build_overview_sidebar();
}

$navbits = construct_navbits($navbits);
$navbar = render_navbar_template($navbits);

($hook = vBulletinHook::fetch_hook('blog_inlinemod_complete')) ? eval($hook) : false;

$headinclude .= vB_Template::create('blog_css')->render();
$templater = vB_Template::create('BLOG');
	$templater->register_page_templates();
	$templater->register('abouturl', $abouturl);
	$templater->register('blogheader', $blogheader);
	$templater->register('bloginfo', $bloginfo);
	$templater->register('blogrssinfo', $blogrssinfo);
	$templater->register('bloguserid', $bloguserid);
	$templater->register('content', $content);
	$templater->register('navbar', $navbar);
	$templater->register('onload', $onload);
	$templater->register('pagetitle', $pagetitle);
	$templater->register('pingbackurl', $pingbackurl);
	$templater->register('sidebar', $sidebar);
	$templater->register('trackbackurl', $trackbackurl);
	$templater->register('usercss_profile_preview', $usercss_profile_preview);
print_output($templater->render());

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 53471 $
|| ####################################################################
\*======================================================================*/
?>
