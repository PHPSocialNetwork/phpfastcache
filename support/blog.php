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
if (!defined('THIS_SCRIPT')) { define('THIS_SCRIPT', 'blog'); }
define('VB_PRODUCT', 'vbblog');
define('CSRF_PROTECTION', true);
define('VBBLOG_PERMS', true);
define('VBBLOG_STYLE', true);
define('VBBLOG_SCRIPT', true);
define('GET_EDIT_TEMPLATES', 'blog,comments,list');

if (!defined('FRIENDLY_URL_LINK'))
{
	define('FRIENDLY_URL_LINK', 'blog');
}

// ################### PICK SOMETHING TO DO ######################
if (empty($_REQUEST['do']))
{
	if (THIS_SCRIPT == 'entry' OR !empty($_REQUEST['blogid']) OR !empty($_REQUEST['blogtextid']) OR !empty($_REQUEST['b']) OR !empty($_REQUEST['bt']))
	{
		$_REQUEST['do'] = 'blog';
	}
	else if (!empty($_REQUEST['tag']) OR !empty($_REQUEST['userid']) OR !empty($_REQUEST['u']) OR !empty($_REQUEST['username']) OR !empty($_REQUEST['blogcategoryid']))
	{
		$_REQUEST['do'] = 'list';
	}
	else if (!empty($_REQUEST['cp']))
	{
		$_REQUEST['do'] = 'custompage';
	}
	else
	{
		$_REQUEST['do'] = 'list';
	}
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'posting',
	'vbblogglobal',
	'postbit',
	'vbblogcat',
);

// $actionphrases is broken in 3.6.7 so simulate
if (in_array($_REQUEST['do'], array('sendtofriend', 'dosendtofriend')))
{
	$phrasegroups[] = 'messaging';
}

if (in_array($_REQUEST['do'], array('list', 'blog', 'comments')))
{
	$phrasegroups[] = 'inlinemod';
}

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
	'blogcategorycache',
	'blogstats',
	'blogfeatured_settings',
	'blogfeatured_entries',
);

if (in_array($_REQUEST['do'], array('list', 'bloglist', 'comments')))
{
	$specialtemplates[] = 'blogtagcloud';
}

if ($_REQUEST['do'] == 'blog')
{
	$specialtemplates[] = 'bookmarksitecache';
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
	'facebook_publishcheckbox',
	'facebook_likebutton',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'blog'				=>	array(
		'blog_bookmark',
		'blog_comment',
		'blog_comment_deleted',
		'blog_comment_ignore',
		'blog_entry_category',
		'blog_entry',
		'blog_entry_deleted',
		'blog_entry_ignore',
		'blog_show_entry',
		'blog_show_entry_recent_entry_link',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
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
		'blog_taglist',
		'blog_trackback',
		'ad_blogshowentry_after',
		'ad_blogshowentry_before',
		'postbit_attachmentimage',
		'postbit_attachmentthumbnail',
	),
	'comments'				=> array(
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_list_comments',
		'blog_comment',
		'blog_comment_deleted',
		'blog_comment_ignore',
		'blog_sidebar_category_link',
		'blog_sidebar_generic',
		'blog_sidebar_user',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_entry_link',
		'blog_sidebar_user_block_archive',
		'blog_sidebar_user_block_category',
		'blog_sidebar_user_block_comments',
		'blog_sidebar_user_block_entries',
		'blog_sidebar_user_block_search',
		'blog_sidebar_user_block_tagcloud',
		'blog_sidebar_user_block_visitors',
		'blog_sidebar_user_block_custom',
		'blog_tag_cloud_link',
		'blog_taglist',
	),
	'none'				=> array(
		'blog_list_entries',
	),
	'list'            => array(
		'blog_list_entries',
		'blog_sidebar_generic',
		'blog_entry',
		'blog_entry_category',
		'blog_entry_deleted',
		'blog_entry_ignore',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_sidebar_user',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_entry_link',
		'blog_sidebar_category_link',
		'blog_sidebar_user_block_archive',
		'blog_sidebar_user_block_category',
		'blog_sidebar_user_block_comments',
		'blog_sidebar_user_block_entries',
		'blog_sidebar_user_block_search',
		'blog_sidebar_user_block_tagcloud',
		'blog_sidebar_user_block_visitors',
		'blog_sidebar_user_block_custom',
		'blog_tag_cloud_link',
		'blog_taglist',
		'ad_bloglist_first_entry',
		'postbit_attachmentimage',
		'postbit_attachmentthumbnail',
		'blog_home_list_entry',
		'blog_entry_featured',
		'blog_home_list_comment',
	),
	'sendtofriend'   => array(
		'blog_send_to_friend',
		'humanverify',
		'newpost_errormessage',
		'newpost_usernamecode',
		'blog_sidebar_user',
		'blog_sidebar_user_block_archive',
		'blog_sidebar_user_block_category',
		'blog_sidebar_user_block_comments',
		'blog_sidebar_user_block_entries',
		'blog_sidebar_user_block_search',
		'blog_sidebar_user_block_tagcloud',
		'blog_sidebar_user_block_visitors',
		'blog_sidebar_user_block_custom',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_entry_link',
		'blog_sidebar_category_link',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_tag_cloud_link',
	),
	'viewip'         => array(
		'blog_entry_ip',
	),
	'bloglist'       => array(
		'blog_blog_row',
		'blog_list_blogs_all',
		'blog_list_blogs_best',
		'blog_list_blogs_blog',
		'blog_list_blogs_blog_ignore',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_sidebar_category_link',
		'blog_sidebar_generic',
		'forumdisplay_sortarrow',
		'blog_tag_cloud_link',
	),
	'members'        => array(
		'blog_cp_css',
		'blog_grouplist',
		'blog_grouplist_userbit',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_sidebar_category_link',
		'blog_sidebar_user_block_archive',
		'blog_sidebar_user_block_category',
		'blog_sidebar_user_block_comments',
		'blog_sidebar_user_block_entries',
		'blog_sidebar_user_block_search',
		'blog_sidebar_user_block_tagcloud',
		'blog_sidebar_user_block_visitors',
		'blog_sidebar_user_block_custom',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_entry_link',
		'blog_sidebar_user',
		'blog_tag_cloud_link',
	),
	'custompage'      => array(
		'blog_custompage',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_sidebar_user',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_entry_link',
		'blog_sidebar_category_link',
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
);

$actiontemplates['dosendtofriend'] =& $actiontemplates['sendtofriend'];

// ####################### PRE-BACK-END ACTIONS ##########################
function exec_postvar_call_back()
{
	global $vbulletin;

	$vbulletin->input->clean_gpc('r', 'goto', TYPE_STR);

	if ($vbulletin->GPC['goto'] == 'newpost')
	{
		$vbulletin->noheader = true;
	}
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/blog_functions_main.php');

verify_blog_url();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// ### STANDARD INITIALIZATIONS ###
$checked = array();
$blog = array();
$postattach = array();
$bloginfo = array();
$show['lightbox'] = ($vbulletin->options['lightboxenabled'] AND $vbulletin->options['usepopups']);
$show['moderatecomments'] = (!$vbulletin->options['blog_commentmoderation'] AND $vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_followcommentmoderation'] ? true : false);
$show['pingback'] = ($vbulletin->options['vbblog_pingback'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canreceivepingback'] ? true : false);
$show['trackback'] = ($vbulletin->options['vbblog_trackback'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canreceivepingback'] ? true : false);
$show['notify'] = ($vbulletin->options['vbblog_notifylinks'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cansendpingback'] ? true : false);
$navbits = array();

/* Check they can view a blog, any blog */
if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
{
	if (!$vbulletin->userinfo['userid'] OR !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}
}

($hook = vBulletinHook::fetch_hook('blog_start')) ? eval($hook) : false;

//We'll need this in a bit. This is the info to mark as escalate to Article
if ($vbulletin->products['vbcms'])
{
	if (!isset(vB::$vbulletin->userinfo['permissions']['cms']))
	{
		require_once DIR . '/packages/vbcms/permissions.php';
		vBCMS_Permissions::getUserPerms();
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'blog')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT,
		'goto'       => TYPE_STR,
		'uh'         => TYPE_BOOL,
	));

	$bloginfo = verify_blog($blogid);
	verify_seo_url('entry', $bloginfo, array('pagenumber' => $_REQUEST['pagenumber']));

	track_blog_visit($bloginfo['userid']);

	$wheresql = array();
	$state = array('visible');

	($hook = vBulletinHook::fetch_hook('blog_entry_start')) ? eval($hook) : false;

	if (can_moderate_blog('canmoderateentries') OR is_member_of_blog($vbulletin->userinfo, $bloginfo))
	{
		$state[] = 'moderation';
	}

	if (can_moderate_blog() OR is_member_of_blog($vbulletin->userinfo, $bloginfo))
	{
		$state[] = 'deleted';
		$deljoinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_deletionlog AS blog_deletionlog ON (blog.blogid = blog_deletionlog.primaryid AND blog_deletionlog.type = 'blogid')";
	}

	if (is_member_of_blog($vbulletin->userinfo, $bloginfo))
	{
		$state[] = 'draft';
	}
	else
	{
		$wheresql[] = "blog.dateline <= " . TIMENOW;
		$wheresql[] = "blog.pending = 0";
	}

	$wheresql[] = "blog.userid = $bloginfo[userid]";
	$wheresql[] = "blog.state IN ('" . implode("','", $state) . "')";

	// remove blog entries that don't interest us
	if ($coventry = fetch_coventry('string') AND !can_moderate_blog())
	{
		$wheresql[] = "blog.userid NOT IN ($coventry)";
	}

	if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']) AND $bloginfo['userid'] != $vbulletin->userinfo['userid'])
	{
		$joinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
		$wheresql[] = "cu.blogcategoryid IS NULL";
	}

	if (!can_moderate_blog() AND !is_member_of_blog($vbulletin->userinfo, $bloginfo) AND !$bloginfo['buddyid'])
	{
		$wheresql[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
	}

	switch($vbulletin->GPC['goto'])
	{
		case 'next':
			$wheresql[] = "blog.dateline > $bloginfo[dateline]";
			if ($next = $db->query_first_slave("
				SELECT blog.blogid
				FROM " . TABLE_PREFIX . "blog AS blog
				$joinsql
				WHERE " . implode(" AND ", $wheresql) . "
				ORDER BY blog.dateline
				LIMIT 1
			"))
			{
				$blogid = $next['blogid'];
			}
			else
			{
				standard_error(fetch_error('nonextnewest_blog'));
			}
			break;
		case 'prev':
			$wheresql[] = "blog.dateline < $bloginfo[dateline]";
			if ($prev = $db->query_first_slave("
				SELECT blog.blogid
				FROM " . TABLE_PREFIX . "blog AS blog
				$joinsql
				WHERE " . implode(" AND ", $wheresql) . "
				ORDER BY blog.dateline DESC
				LIMIT 1
			"))
			{
				$blogid = $prev['blogid'];
			}
			else
			{
				standard_error(fetch_error('nonextoldest_blog'));
			}
			break;
		case 'newpost':
			if ($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid'])
			{
				$vbulletin->userinfo['lastvisit'] = max($bloginfo['blogread'], $bloginfo['bloguserread'], TIMENOW - ($vbulletin->options['markinglimit'] * 86400));
			}
			else if ($blogview = max(fetch_bbarray_cookie('blog_lastview', $bloginfo['blogid']), fetch_bbarray_cookie('blog_userread', $bloginfo['userid']), $vbulletin->userinfo['lastvisit']))
			{
				$vbulletin->userinfo['lastvisit'] = $blogview;
			}

			$comments = $db->query_first("
				SELECT MIN(blogtextid) AS blogtextid
				FROM " . TABLE_PREFIX . "blog_text
				WHERE
					blogid = $bloginfo[blogid]
						AND
					blogtextid <> $bloginfo[firstblogtextid]
						AND
					state = 'visible'
						AND
					dateline > " . intval($vbulletin->userinfo['lastvisit']) . "
					" . (($coventry = fetch_coventry('string') AND !can_moderate_blog()) ? "AND userid NOT IN ($coventry)" : "") . "
			");
			if ($comments['blogtextid'])
			{
				$pageinfo = array('bt' => $comments['blogtextid']);
				exec_header_redirect(fetch_seo_url('entry|js', $bloginfo, $pageinfo) . "#comment$comments[blogtextid]");
			}
			else
			{
				$pageinfo = array('bt' => $bloginfo['lastblogtextid']);
				exec_header_redirect(fetch_seo_url('entry|js', $bloginfo, $pageinfo) . "#comment$bloginfo[lastblogtextid]");
			}
			break;
	}

	$bloginfo = verify_blog($blogid);

	if ($vbulletin->options['vbblog_nextprevlinks'])
	{
		$show['nextprevtitle'] = true;
		if ($next = $db->query_first_slave("
			SELECT blog.blogid, blog.title
			FROM " . TABLE_PREFIX . "blog AS blog
			$joinsql
			WHERE " . implode(" AND ", $wheresql) . "
				AND blog.dateline > $bloginfo[dateline]
			ORDER BY blog.dateline
			LIMIT 1
		"))
		{
			$show['nexttitle'] = true;
		}
		if ($prev = $db->query_first_slave("
			SELECT blog.blogid, blog.title
			FROM " . TABLE_PREFIX . "blog AS blog
			$joinsql
			WHERE " . implode(" AND ", $wheresql) . "
				AND blog.dateline < $bloginfo[dateline]
			ORDER BY blog.dateline DESC
			LIMIT 1
		"))
		{
			$show['prevtitle'] = true;
		}
		$show['blognav'] = ($show['prevtitle'] OR $show['nexttitle']);
	}
	else
	{
		$show['blognav'] = true;
	}

	// this fetches permissions for the user who created the blog
	cache_permissions($bloginfo, false);

	$displayed_dateline = 0;

	$show['quickcomment'] =
	(
		$vbulletin->options['quickreply']
		AND
		$vbulletin->userinfo['userid']
		AND
		$bloginfo['cancommentmyblog']
		AND
		($bloginfo['allowcomments'] OR is_member_of_blog($vbulletin->userinfo, $bloginfo) OR can_moderate_blog())
		AND
		(
			(($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_cancommentown']) AND $bloginfo['userid'] == $vbulletin->userinfo['userid'])
			OR
			(($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_cancommentothers']) AND $bloginfo['userid'] != $vbulletin->userinfo['userid'])
		)
		AND
		(
			(
				$bloginfo['state'] == 'moderation'
					AND
				(
					can_moderate_blog('canmoderateentries')
						OR
					(
						$vbulletin->userinfo['userid']
							AND
						$bloginfo['userid'] == $vbulletin->userinfo['userid']
							AND
						$bloginfo['postedby_userid'] != $vbulletin->userinfo['userid']
							AND
						$bloginfo['membermoderate']
					)
				)
			)
				OR
			$bloginfo['state'] == 'visible'
		)
		AND
		!$bloginfo['pending']
		AND
		!fetch_require_hvcheck('post')
	);

	$show['postcomment'] = fetch_can_comment($bloginfo, $vbulletin->userinfo);

	// *********************************************************************************

	// display ratings
	if ($bloginfo['ratingnum'] >= $vbulletin->options['vbblog_ratingpost'])
	{
		$bloginfo['ratingavg'] = vb_number_format($bloginfo['ratingtotal'] / $bloginfo['ratingnum'], 2);
		$bloginfo['rating'] = intval(round($bloginfo['ratingtotal'] / $bloginfo['ratingnum']));
		$show['rating'] = true;
	}
	else
	{
		$show['rating'] = false;
	}

	// this is for a guest
	$rated = intval(fetch_bbarray_cookie('blog_rate', $bloginfo['blogid']));

	// voted already
	if ($bloginfo['vote'] OR $rated)
	{
		$rate_index = $rated;
		if ($bloginfo['vote'])
		{
			$rate_index = $bloginfo['vote'];
		}
		$voteselected["$rate_index"] = 'selected="selected"';
		$votechecked["$rate_index"] = 'checked="checked"';
	}
	else
	{
		$voteselected[0] = 'selected="selected"';
		$votechecked[0] = 'checked="checked"';
	}

	// *********************************************************************************
	// update views counter
	if ($bloginfo['state'] != 'draft' AND !$bloginfo['pending'])
	{
		if ($vbulletin->options['blogviewslive'])
		{
			// doing it as they happen; for optimization purposes
			$db->shutdown_query("
				UPDATE " . TABLE_PREFIX . "blog
				SET views = views + 1
				WHERE blogid = " . intval($bloginfo['blogid'])
			);
		}
		else
		{
			// or doing it once an hour
			$db->shutdown_query("
				INSERT INTO " . TABLE_PREFIX . "blog_views (blogid)
				VALUES (" . intval($bloginfo['blogid']) . ')'
			);
		}
	}

	require_once(DIR . '/includes/class_bbcode_blog.php');
	require_once(DIR . '/includes/class_blog_response.php');

	$bbcode = new vB_BbCodeParser_Blog($vbulletin, fetch_tag_list());

	$factory = new vB_Blog_ResponseFactory($vbulletin, $bbcode, $bloginfo);

	$responsebits = '';
	$saveparsed = '';
	$trackbackbits = '';
	$pagetext_cachable = true;
	$oldest_comment = TIMENOW;

	// Comments
	$deljoinsql = '';
	$state = array('visible');
	if (can_moderate_blog('canmoderatecomments') OR is_member_of_blog($vbulletin->userinfo, $bloginfo))
	{
		$state[] = 'moderation';
	}
	if (can_moderate_blog() OR is_member_of_blog($vbulletin->userinfo, $bloginfo))
	{
		$state[] = 'deleted';
		$deljoinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_deletionlog AS blog_deletionlog ON (blog_text.blogtextid = blog_deletionlog.primaryid AND blog_deletionlog.type = 'blogtextid')";
	}
	else
	{
		$deljoinsql = '';
	}

	// Get our page
	if ($blogtextid)
	{
		$getpagenum = $db->query_first("
			SELECT COUNT(*) AS comments
			FROM " . TABLE_PREFIX . "blog_text AS blog_text
			WHERE blogid = $blogid
				AND state IN ('" . implode("','", $state) . "')
				AND blogtextid <> $bloginfo[firstblogtextid]
				AND dateline <= $blogtextinfo[dateline]
		");
		$vbulletin->GPC['pagenumber'] = ceil($getpagenum['comments'] / $vbulletin->options['blog_commentsperpage']);
	}
	if ($coventry = fetch_coventry('string') AND !can_moderate_blog())
	{
		$globalignore = "AND blog_text.userid NOT IN ($coventry)";
	}
	else
	{
		$globalignore = '';
	}

	$categories = array();
	// Get categories
	$cats = $db->query_read_slave("
		SELECT blog_categoryuser.blogcategoryid, blog_categoryuser.userid, blog_category.userid AS creatorid
		FROM " . TABLE_PREFIX . "blog_categoryuser AS blog_categoryuser
		LEFT JOIN " . TABLE_PREFIX . "blog_category AS blog_category ON (blog_category.blogcategoryid = blog_categoryuser.blogcategoryid)
		WHERE blog_categoryuser.blogid = $bloginfo[blogid]
	");
	while ($category = $db->fetch_array($cats))
	{
		if (!($bloginfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancreatecategory']) AND $category['creatorid'])
		{
			continue;
		}
		$category['title'] = $category['creatorid'] ? $bloginfo['categorycache']["$category[blogcategoryid]"]['title'] : $vbphrase['category' . $category['blogcategoryid'] . '_title'];
		$entry_categories["$bloginfo[blogid]"][] = $category;
	}

	// load attachments
	if ($bloginfo['attach'])
	{
		require_once(DIR . '/packages/vbattach/attach.php');
		$attach = new vB_Attach_Display_Content($vbulletin, 'vBBlog_BlogEntry');
		$postattach = $attach->fetch_postattach(0, $bloginfo['blogid']);
	}

	if ($vbulletin->options['vbblog_pingback'] AND $bloginfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canreceivepingback'])
	{
		$show['pingbacklink'] = true;
		$pingbackurl = fetch_seo_url('blogcallback|nosession|bburl', array());
		header("X-Pingback: $pingbackurl");
	}

	if ($vbulletin->options['vbblog_trackback'] AND $bloginfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canreceivepingback'])
	{
		$show['trackbackrdf'] = true;
		$trackbackurl = fetch_seo_url('blogcallback|nosession|bburl', array(), array('b' => $bloginfo['blogid']));
		$abouturl = fetch_seo_url('entry|bburl', $bloginfo);
	}

	// Load trackbacks
	if ($show['pingbacklink'] OR $show['trackbackrdf'])
	{
		$canmoderation = (can_moderate_blog('canmoderatecomments') OR is_member_of_blog($vbulletin->userinfo, $bloginfo));
		if ($bloginfo['trackback_visible'] OR ($bloginfo['trackback_moderation'] AND $canmoderation))
		{
			$bgclass = 'alt2';
			$trackbacks = $db->query_read("
				SELECT blog_trackback.*
				FROM " . TABLE_PREFIX . "blog_trackback AS blog_trackback
				WHERE blogid = $bloginfo[blogid]
					" . (!$canmoderation ? "AND state = 'visible'" : "") . "
			");
			while ($trackback = $db->fetch_array($trackbacks))
			{
				$response_handler =& $factory->create($trackback);
				$response_handler->cachable = false;
				// we deliberately ignore the returned value since its been templated, we're not really interested in that :)
				$trackbackbits .= $response_handler->construct();
			}
			$show['inlinemod_trackback'] = (
				fetch_comment_perm('canremovecomments', $bloginfo)
					OR
				fetch_comment_perm('candeletecomments', $bloginfo)
					OR
				fetch_comment_perm('canmoderatecomments', $bloginfo)
			);
			$show['approve_trackback'] = true;
			$show['delete_trackback'] = true;
		}
	}

	if (!($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_cangetattach']))
	{
		$vbulletin->options['viewattachedimages'] = 0;
		$vbulletin->options['attachthumbs'] = 0;
	}

	/* Handle the blog entry now */
	require_once(DIR . '/includes/class_blog_entry.php');
	$entry_factory = new vB_Blog_EntryFactory($vbulletin, $bbcode, $entry_categories);
	$entry_handler =& $entry_factory->create($bloginfo);
	$entry_handler->attachments = $postattach;
	$entry_handler->userinfo = $bloginfo;
	$entry_handler->construct();
	$blog =& $entry_handler->blog;
	$status =& $entry_handler->status;

	// *********************************************************************************
	// save parsed post HTML
	if (!empty($saveparsed))
	{
		$db->shutdown_query("
			REPLACE INTO " . TABLE_PREFIX . "blog_textparsed (blogtextid, dateline, hasimages, pagetexthtml, styleid, languageid)
			VALUES $saveparsed
		");
		unset($saveparsed);
	}

	// quick comment
	if ($show['quickcomment'])
	{
		require_once(DIR . '/includes/functions_editor.php');
		$editorid = construct_edit_toolbar(
			'',
			false,
			'blog_comment',
			$vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowsmilies'],
			true,
			false,
			'qr',
			'',
			array(),
			'content',
			'vBBlog_BlogComment',
			0,
			$bloginfo['blogid'],
			false,
			true,
			'title'
		);
		$show['ajax_js'] = true;
	}
	else if ($vbulletin->options['quickedit'])
	{
		$show['ajax_js'] = true;
	}

	$show['quickedit'] = $vbulletin->options['quickedit'];

	// get ignored users
	$ignore = array();
	if (trim($vbulletin->userinfo['ignorelist']))
	{
		$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
		foreach ($ignorelist AS $ignoreuserid)
		{
			$ignore["$ignoreuserid"] = 1;
		}
	}
	DEVDEBUG('ignored users: ' . implode(', ', array_keys($ignore)));

	// Comments
	do
	{
		if (!$vbulletin->GPC['pagenumber'])
		{
			$vbulletin->GPC['pagenumber'] = 1;
		}
		$start = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->options['blog_commentsperpage'];
		$pagenumber = $vbulletin->GPC['pagenumber'];

		$state_or = array(
			"blog_text.state IN ('" . implode("','", $state) . "')"
		);

		// Get the viewing user's moderated entries
		if ($vbulletin->userinfo['userid'] AND $bloginfo['comments_moderation'] > 0 AND !can_moderate_blog('canmoderatecomments') AND !is_member_of_blog($vbulletin->userinfo, $bloginfo))
		{
			$state_or[] = "(blog_text.userid = " . $vbulletin->userinfo['userid'] . " AND state = 'moderation')";
		}

		$show['approve'] = $show['delete'] = $show['undelete'] = false;

		$hook_query_fields = $hook_query_joins = $hook_query_where = '';
		($hook = vBulletinHook::fetch_hook('blog_entry_comments_query')) ? eval($hook) : false;

		$comments = $db->query_read("
			SELECT SQL_CALC_FOUND_ROWS blog_text.*, blog_text.ipaddress AS blogipaddress,
				blog_textparsed.pagetexthtml, blog_textparsed.hasimages,
				user.*, userfield.*, blog_text.username AS postusername,
				blog_editlog.userid AS edit_userid, blog_editlog.dateline AS edit_dateline, blog_editlog.reason AS edit_reason, blog_editlog.username AS edit_username
				" . ($deljoinsql ? ",blog_deletionlog.moddelete AS del_moddelete ,blog_deletionlog.userid AS del_userid, blog_deletionlog.username AS del_username, blog_deletionlog.reason AS del_reason" : "") . "
				" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
				$hook_query_fields
			FROM " . TABLE_PREFIX . "blog_text AS blog_text
			LEFT JOIN " . TABLE_PREFIX . "blog_textparsed AS blog_textparsed ON(blog_textparsed.blogtextid = blog_text.blogtextid AND blog_textparsed.styleid = " . intval(STYLEID) . " AND blog_textparsed.languageid = " . intval(LANGUAGEID) . ")
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON (blog_text.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (userfield.userid = blog_text.userid)
			LEFT JOIN " . TABLE_PREFIX . "blog_editlog AS blog_editlog ON (blog_editlog.blogtextid = blog_text.blogtextid)
			" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
			$deljoinsql
			$hook_query_joins
			WHERE blogid = $bloginfo[blogid]
				AND blog_text.blogtextid <> " . $bloginfo['firstblogtextid'] . "
				AND (" . implode(" OR ", $state_or) . ")
				$globalignore
				$hook_query_where
			ORDER BY blog_text.dateline ASC
			LIMIT $start, " . $vbulletin->options['blog_commentsperpage']
		);
		list($comment_count) = $db->query_first("SELECT FOUND_ROWS()", DBARRAY_NUM);

		if ($start >= $comment_count)
		{
			$vbulletin->GPC['pagenumber'] = ceil($comment_count / $vbulletin->options['blog_commentsperpage']);
		}
	}
	while ($start >= $comment_count AND $comment_count);

	$pagenav = construct_page_nav(
		$vbulletin->GPC['pagenumber'],
		$vbulletin->options['blog_commentsperpage'],
		$comment_count,
		'',
		'',
		'comments',
		'entry',
		$bloginfo
	);

	$counter = 0;
	while ($comment = $db->fetch_array($comments))
	{
		if ($comment['state'] == 'visible')
		{
			$counter++;
		}
		$comment['firstshown'] = ($counter == 1 AND $comment['state'] == 'visible');
		$response_handler =& $factory->create($comment, 'Comment', $ignore);
		$response_handler->userinfo = $bloginfo;
		$response_handler->cachable = $pagetext_cachable;
		$responsebits .= $response_handler->construct();

		if ($pagetext_cachable AND $comment['pagetexthtml'] == '')
		{
			if (!empty($saveparsed))
			{
				$saveparsed .= ',';
			}
			$saveparsed .= "($comment[blogtextid], " . intval($bloginfo['lastcomment']) . ', ' . intval($response_handler->parsed_cache['has_images']) . ", '" . $db->escape_string($response_handler->parsed_cache['text']) . "', " . intval(STYLEID) . ", " . intval(LANGUAGEID) . ")";
		}

		if ($comment['dateline'] > $displayed_dateline)
		{
			$displayed_dateline = $comment['dateline'];
		}

		$oldest_comment = $comment['dateline'];

		if ($comment['state'] == 'deleted' OR $ignore["$comment[userid]"])
		{	// be aware $factory->create can change $response['state']
			$show['quickload'] = true;
		}
	}
	// This is only used by Quick Comment but init it either way
	$effective_lastcomment = max($displayed_dateline, $bloginfo['lastcomment']);

	$show['delete'] = (fetch_comment_perm('candeletecomments', $bloginfo) OR fetch_comment_perm('canremovecomments', $bloginfo));
	$show['undelete'] = fetch_comment_perm('canundeletecomments', $bloginfo);
	$show['approve'] = fetch_comment_perm('canmoderatecomments', $bloginfo);
	$show['inlinemod'] = ($responsebits AND ($show['delete'] OR $show['approve'] OR $show['undelete']));

	// Only allow AJAX QC on the last page and after one comment
	$allow_ajax_qc = ($comment_count > 0 AND ($vbulletin->GPC['pagenumber'] == ceil($comment_count / $vbulletin->options['blog_commentsperpage']))) ? 1 : 0;

	if ($vbulletin->userinfo['userid'])
	{
		mark_blog_read($bloginfo, $vbulletin->userinfo['userid'], $oldest_comment);
	}

	// Todo: allow ratings option or permission, hardcoded but we may want to add this
	$show['blograting'] = ($bloginfo['state'] == 'visible');
	$show['rateblog'] =
	(
		$show['blograting']
		AND
		(
			(
				(!$bloginfo['vote'] AND $vbulletin->userinfo['userid'])
			OR
				(!$rated AND !$vbulletin->userinfo['userid'])
			)
			OR
				$vbulletin->options['votechange']
		)
	);

	// Build Social Bookmark Links
	$guestuser = array(
		'userid'      => 0,
		'usergroupid' => 0,
	);
	cache_permissions($guestuser, false);

	$bookmarksites = '';
	if (
		$guestuser['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']
			AND
		$guestuser['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']
			AND
		$bloginfo['state'] == 'visible'
			AND
		$bloginfo['guest_canviewmyblog']
			AND
		!$bloginfo['pending']
	)
	{
		if ($vbulletin->options['socialbookmarks'] AND is_array($vbulletin->bookmarksitecache) AND !empty($vbulletin->bookmarksitecache))
		{
			$raw_title = html_entity_decode($bloginfo['title'], ENT_QUOTES);
			foreach($vbulletin->bookmarksitecache AS $bookmarksite)
			{
				$bookmarksite['link'] = str_replace(
					array('{URL}', '{TITLE}'),
					array(urlencode(fetch_seo_url('entry|bburl|nosession', $bloginfo)), urlencode($bookmarksite['utf8encode'] ? utf8_encode($raw_title) : $raw_title)),
					$bookmarksite['url']
				);

				($hook = vBulletinHook::fetch_hook('blog_entry_bookmarkbit')) ? eval($hook) : false;

				$templater = vB_Template::create('blog_bookmark');
					$templater->register('bloginfo', $bloginfo);
					$templater->register('bookmarksite', $bookmarksite);
				$bookmarksites .= $templater->render();
			}
		}
		$show['guestview'] = true;
	}
	else
	{
		$show['guestview'] = false;
	}

	$show['trackbacks'] = ($vbulletin->GPC['pagenumber'] <= 1);
	$show['titlefirst'] = true;
	$show['entryonly'] = ($bloginfo['pending'] OR $bloginfo['state'] == 'draft' OR ((!$bloginfo['allowcomments'] AND !is_member_of_blog($vbulletin->userinfo, $bloginfo) AND !can_moderate_blog() AND empty($responsebits))));
	$show['privateentry'] = ($bloginfo['private']);

	$perform_floodcheck = (
		!($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
		AND $vbulletin->options['emailfloodtime']
		AND $vbulletin->userinfo['userid']
	);

	$show['registeruserid'] = true;
	$bloguserid = $bloginfo['userid'];

	$blogheader = parse_blog_description($bloginfo);
	$sidebar =& build_user_sidebar($bloginfo);

	$ad_location['blogshowentry_before'] = vB_Template::create('ad_blogshowentry_before')->render();
	$ad_location['blogshowentry_after'] = vB_Template::create('ad_blogshowentry_after')->render();

	// navbar and output
	$navbits[fetch_seo_url('blog', $bloginfo, null, 'userid', 'blog_title')] = $bloginfo['blog_title'];
	$navbits[fetch_seo_url('entry', $bloginfo)] = $bloginfo['title'];

	// prepare the member action drop-down menu
	if ($bloginfo['userid'] != $bloginfo['postedby_userid'])
	{
		$memberaction_dropdown = construct_memberaction_dropdown(fetch_userinfo($bloginfo['postedby_userid']));
	}
	else
	{
		$memberaction_dropdown = construct_memberaction_dropdown($bloginfo);
	}

	if ($show['guestview'])
	{
		// facebook options
		if (is_facebookenabled())
		{
			// display publish to Facebook checkbox in quick editor?
			$fbpublishcheckbox = construct_fbpublishcheckbox();
		}
		// display the like button for this thread?
		$fblikebutton = construct_fblikebutton();
	}

	($hook = vBulletinHook::fetch_hook('blog_entry_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('blog_show_entry');
		$templater->register('pageinfo_ip', array('do' => 'viewip'));
		$templater->register('pageinfo_sf', array('do' => 'sendtofriend'));
		$templater->register('blogheader', $blogheader);
		$templater->register('ad_location', $ad_location);
		$templater->register('allow_ajax_qc', $allow_ajax_qc);
		$templater->register('blog', $blog);
		$templater->register('bloginfo', $bloginfo);
		$templater->register('memberaction_dropdown', $memberaction_dropdown);
		$templater->register('blogtextinfo', $blogtextinfo);
		$templater->register('bookmarksites', $bookmarksites);
		$templater->register('editorid', $editorid);
		$templater->register('effective_lastcomment', $effective_lastcomment);
		$templater->register('gobutton', $gobutton);
		$templater->register('messagearea', $messagearea);
		$templater->register('next', $next);
		$templater->register('pagenav', $pagenav);
		$templater->register('prev', $prev);
		$templater->register('responsebits', $responsebits);
		$templater->register('status', $status);
		$templater->register('trackbackbits', $trackbackbits);
		$templater->register('url', $url);
		$templater->register('vbulletin', $vbulletin);
		$templater->register('votechecked', $votechecked);
		$templater->register('voteselected', $voteselected);
		$templater->register('fbpublishcheckbox', $fbpublishcheckbox);
		$templater->register('fblikebutton', $fblikebutton);
		$templater->register('trackbackurl', fetch_seo_url('blogcallback|nosession|bburl', array(), array('b' => $bloginfo['blogid'])));
		if ($show['quickedit'] AND !$show['quickcomment'])
		{
			$templater->register('editor_clientscript', vB_Template::create('editor_clientscript')->render());
			$templater->register('editor_js', vB_Ckeditor::getJsIncludes());
		}

		//See if we want to display the authorization to escalate a blog post to
		// an article
		if (count(vB::$vbulletin->userinfo['permissions']['cms']['cancreate']))
		{
			$templater->register('promote_sectionid', vB::$vbulletin->userinfo['permissions']['cms']['canpublish'][0]);
			$templater->register('articletypeid', vB_Types::instance()->getContentTypeID('vBCms_Article'));
			$query = 'contenttypeid='. vB_Types::instance()->getContentTypeID('vBCms_Article') .
				'&amp;blogid=' . $blog['blogid'] . '&amp;parentid=1';
			$promote_url = vB_Route::create('vBCms_Route_Content', '1/addcontent/')->getCurrentURL(null, null, $query);
			$templater->register('promote_url', $promote_url);
		}
	$templater->register('vbulletin', $vbulletin);

	$content = $templater->render();
}

// #######################################################################
if ($_REQUEST['do'] == 'list')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber'     => TYPE_UINT,
		'perpage'        => TYPE_UINT,
		'month'          => TYPE_UINT,
		'year'           => TYPE_UINT,
		'day'            => TYPE_UINT,
		'blogtype'       => TYPE_NOHTML,
		'commenttype'    => TYPE_NOHTML,
		'type'           => TYPE_STR,
		'blogcategoryid' => TYPE_INT,
		'userid'         => TYPE_UINT,
		'username'       => TYPE_NOHTML,
		'tag'            => TYPE_NOHTML,
		'span'           => TYPE_UINT,
		'featured'       => TYPE_STR,
	));

	require_once(DIR . '/includes/class_bbcode_blog.php');

	if ($vbulletin->GPC['username'])
	{
		$user = $db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = '" . $db->escape_string($vbulletin->GPC['username']) . "'");
		$vbulletin->GPC['userid'] = $user['userid'];
	}

	if ($vbulletin->GPC['userid'])
	{
		$userinfo = verify_id('user', $vbulletin->GPC['userid'], 1, 1, 10);
		$show['entry_userinfo'] = false;

		verify_seo_url('blog', $userinfo, array('pagenumber' => $_REQUEST['pagenumber']), 'userid', 'username');

		if ($vbulletin->userinfo['userid'] != $userinfo['userid'] AND empty($userinfo['bloguserid']))
		{
			standard_error(fetch_error('blog_noblog', $userinfo['username']));
		}

		if (!$userinfo['canviewmyblog'])
		{
			print_no_permission();
		}

		if (in_coventry($userinfo['userid']) AND !can_moderate_blog())
		{
			standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
		}

		if ($vbulletin->userinfo['userid'] == $userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
		{
			print_no_permission();
		}

		if ($vbulletin->userinfo['userid'] != $userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
		{
			// Can't view other's entries so off you go to your own blog.
			$bloginfo = array(
				'userid' => $vbulletin->userinfo['userid'],
				'title'  => $vbulletin->userinfo['blog_title'] ? $vbulletin->userinfo['blog_title'] : $vbulletin->userinfo['username'],
			);
			exec_header_redirect(fetch_seo_url('blog|js', $bloginfo));
		}

		track_blog_visit($userinfo['userid']);
		$show['registeruserid'] = true;
		$bloguserid = $userinfo['userid'];
	}
	else
	{
		$userinfo = array();
		$show['entry_userinfo'] = true;
		$show['hidesidebar'] = true;
	}

	// Begin blog home page
	if (!$userinfo AND !$vbulletin->GPC['pagenumber'] AND (!$vbulletin->GPC['blogtype'] OR $vbulletin->GPC['blogtype'] == 'recent'))
	{
		$show['bloghome'] = true;

		if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
		{
			// Can't view other's entries so off you go to your own blog.
			exec_header_redirect(fetch_seo_url('blog|js', $vbulletin->userinfo));
		}

		$month = vbdate('n', TIMENOW, false, false);
		$year = vbdate('Y', TIMENOW, false, false);

		($hook = vBulletinHook::fetch_hook('blog_intro_start')) ? eval($hook) : false;

		if (!empty($vbulletin->blogfeatured_settings))
		{
			$build_datastore = false;
			$featuredblogs = array();
			$blogs = array();
			$fetch_attach = false;

			foreach($vbulletin->blogfeatured_settings AS $featureid => $entry)
			{
				if ($entry['type'] != 'specific')
				{
					if (!$vbulletin->blogfeatured_entries["$featureid"] OR $vbulletin->blogfeatured_entries["$featureid"]['dateline'] < (TIMENOW - $entry['refresh']))
					{
						$blogid = fetch_featured_entry($entry, $blogs);
						$build_datastore = true;
					}
					else
					{
						$blogid = $vbulletin->blogfeatured_entries["$featureid"]['blogid'];
					}
				}
				else
				{
					$blogid = $entry['blogid'];
				}

				if ($blogid)
				{
					$blogs["$blogid"] = $entry;
				}

				if ($entry['bbcode'])
				{
					$fetch_attach = true;
				}

				$featuredblogs["$featureid"] = array(
					'blogid'   => $blogid,
					'dateline' => TIMENOW,
				);
			}

			if ($build_datastore OR $vbulletin->blogfeatured_entries === NULL OR empty($vbulletin->blogfeatured_entries))
			{
				build_datastore('blogfeatured_entries', serialize($featuredblogs), 1);
			}

			if (!empty($blogs))
			{
				$wheresql = array(
					"blog.dateline <= " . TIMENOW,
					"blog.pending = 0",
					"blog.state = 'visible'",
					"bu.options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'],
					"bu.options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'],
					"~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'],
				);

				// Include check for guest viewing permission only if guests can actually view the blog
				if ($vbulletin->usergroupcache[1]['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])
				{
					$wheresql[] = "bu.options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
				}

				$joinsql = array();
				if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']))
				{
					$joinsql[] = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
					if ($vbulletin->userinfo['userid'])
					{
						$wheresql[] = "(cu.blogcategoryid IS NULL OR blog.userid = " . $vbulletin->userinfo['userid'] . ")";
					}
					else
					{
						$wheresql[] = "cu.blogcategoryid IS NULL";
					}
				}

				// Can't use fetch_coventry as if the current user is in coventry and triggers the cache their blog could be picked
				if (trim($vbulletin->options['globalignore']) != '')
				{
					if ($coventry = preg_split('#\s+#s', $vbulletin->options['globalignore'], -1, PREG_SPLIT_NO_EMPTY))
					{
						$wheresql[] = "blog.userid NOT IN (" . implode(',', $coventry) . ")";
					}
				}

				if ($vbulletin->userinfo['userid'])
				{
					$wheresql[] = "(bu.options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL)";
					$joinsql[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON ignored.userid = blog.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore'";
					$joinsql[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = bu.bloguserid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')";
				}

				$attachcount = true;
				$postattach = array();
				$categories = array();
				$bloglist =& fetch_blog_list(array_keys($blogs), $fetch_attach, $postattach, $categories, $joinsql, $wheresql);

				// get ignored users
				$ignore = array();
				if (trim($vbulletin->userinfo['ignorelist']))
				{
					$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
					foreach ($ignorelist AS $ignoreuserid)
					{
						$ignore["$ignoreuserid"] = 1;
					}
				}
				DEVDEBUG('ignored users: ' . implode(', ', array_keys($ignore)));

				require_once(DIR . '/includes/class_bbcode_blog.php');
				require_once(DIR . '/includes/class_blog_entry.php');

				$featured_blogbits = '';
				while ($blog = $db->fetch_array($bloglist))
				{
					if ($blogs["$blog[blogid]"]['bbcode'])
					{
						$bbcode = new vB_BbCodeParser_Blog_Snippet($vbulletin, fetch_tag_list());
					}
					else
					{
						$bbcode = new vB_BbCodeParser_Blog_Snippet_Featured($vbulletin, fetch_tag_list());
					}
					$factory = new vB_Blog_EntryFactory($vbulletin, $bbcode, $categories);

					$blog['blogtitle'] = $blog['blogtitle'] ? $blog['blogtitle'] : $blog['username'];

					$entry_handler =& $factory->create($blog, '_Featured', $ignore);
					$entry_handler->cachable = false;
					if ($blogs["$blog[blogid]"]['bbcode'])
					{
						$entry_handler->attachments = $postattach["$blog[blogid]"];
					}

					$blogs["$blog[blogid]"]['entry'] = $entry_handler->construct();
					$show['featured'] = true;
					unset($bbcode);
					unset($factory);
				}

				// this obtuse loop puts the featured entries in the proper display order since $blogs is already sorted properly
				$featuredblogids = array();
				foreach($blogs AS $blogid => $entry)
				{
					$featured_blogbits .= $entry['entry'];
					$featuredblogids[] = $blogid;
				}
			}
		}
	}
	// End blog home page

	if (!$userinfo)
	{
		$blogtype = 'latest';

		if ($blogtype == 'latest')
		{
			$display = array(
				'latest'          => '',
				'latest_link'     => 'none',
				'rating'          => 'none',
				'rating_link'     => '',
				'blograting'      => 'none',
				'blograting_link' => '',
			);
		}
		else if ($blogtype == 'rating')
		{
			$display = array(
				'latest'          => 'none',
				'latest_link'     => '',
				'rating'          => '',
				'rating_link'     => 'none',
				'blograting'      => 'none',
				'blograting_link' => '',
			);
		}
		else
		{
			$display = array(
				'latest'          => 'none',
				'latest_link'     => '',
				'rating'          => 'none',
				'rating_link'     => '',
				'blograting'      => '',
				'blograting_link' => 'none',
			);
		}

		$recentblogbits =& fetch_latest_blogs($blogtype);
		$recentcommentbits =& fetch_latest_comments('latest');

		if (!VB_API)
		{
			$show['entryfindmore'] = ($recentblogbits);
			$show['commentfindmore'] = ($recentcommentbits);
		}

		if (!$recentblogbits)
		{
			$recentblogbits = fetch_error('blog_no_entries');
		}
		if (!$recentcommentbits)
		{
			$recentcommentbits = fetch_error('blog_no_comments');
		}

		($hook = vBulletinHook::fetch_hook('blog_intro_complete')) ? eval($hook) : false;
	}

	$blogtype = $type = '';
	$month = $year = $day = 0;

	$sql1 = array();
	$sql2 = array();
	$sql1join = $sql2join = array();

	($hook = vBulletinHook::fetch_hook('blog_list_entries_start')) ? eval($hook) : false;

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		$sql1[] = "blog.userid = " . $vbulletin->userinfo['userid'];
	}
	if ($vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		$sql1[] = "blog.userid <> " . $vbulletin->userinfo['userid'];
	}

	$state = array('visible');
	if (can_moderate_blog('canmoderateentries') OR ($userinfo['userid'] AND $vbulletin->userinfo['userid'] == $userinfo['userid']))
	{
		$state[] = 'moderation';
	}

	$deljoinsql = '';
	if (can_moderate_blog() OR $vbulletin->userinfo['userid'])
	{
		if (can_moderate_blog() OR $vbulletin->userinfo['userid'] == $userinfo['userid'])
		{
			$state[] = 'deleted';
			$deljoinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_deletionlog AS blog_deletionlog ON (blog.blogid = blog_deletionlog.primaryid AND blog_deletionlog.type = 'blogid')";
		}
		else if ($vbulletin->userinfo['userid'] AND $vbulletin->userinfo['blog_deleted'])
		{
			$deljoinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_deletionlog AS blog_deletionlog ON (blog.blogid = blog_deletionlog.primaryid AND blog_deletionlog.type = 'blogid')";
		}
	}

	if ($vbulletin->GPC['month'] AND $vbulletin->GPC['year'])
	{
		$month = ($vbulletin->GPC['month'] < 1 OR $vbulletin->GPC['month'] > 12) ? vbdate('n', TIMENOW, false, false) : $vbulletin->GPC['month'];
		$year = ($vbulletin->GPC['year'] > 2037 OR $vbulletin->GPC['year'] < 1970) ? vbdate('Y', TIMENOW, false, false) : $vbulletin->GPC['year'];
		if ($day = $vbulletin->GPC['day'])
		{
			if ($day > gmdate('t', gmmktime(12, 0, 0, $month, $day, $year)))
			{	// Invalid day, toss it out
				$day = 0;
			}
		}

		$today = getdate(TIMENOW - $vbulletin->options['hourdiff']);
		if (
			(
				$year > $today['year']
					OR
				($month > $today['mon'] AND $year == $today['year'])
			)
				AND
			(
				($userinfo AND !is_member_of_blog($vbulletin->userinfo, $userinfo))
					OR
				(!$userinfo AND !$vbulletin->userinfo['userid'])
			)
		)
		{
			print_no_permission();
		}

		require_once(DIR . '/includes/functions_misc.php');
		if ($day)
		{
			$starttime = vbmktime(0, 0, 0, $month, $day, $year);
			$endtime = vbmktime(0, 0, 0, $month, $day + 1, $year);
		}
		else
		{
			$starttime = vbmktime(0, 0, 0, $month, 1, $year);
			$endtime = vbmktime(0, 0, 0, $month + 1, 1, $year);
		}

		$sql1[] = "blog.dateline >= $starttime";
		$sql1[] = "blog.dateline < $endtime";

		$orderby = "blog.dateline DESC";
		$orderby_union = "dateline_order DESC";
	}
	else
	{
		switch($vbulletin->GPC['blogtype'])
		{
			case 'best':
				$blogtype = 'best';
				$sql1[] = "blog.ratingnum >= " . intval($vbulletin->options['vbblog_ratingpost']);
				if (!$userinfo)
				{
					$sql2[] = "blog.ratingnum >= " . intval($vbulletin->options['vbblog_ratingpost']);
				}

				$orderby = "blog.rating DESC, blog.blogid";
				$orderby_union = "rating_order DESC, blogid_order";
				break;
			default:
				$blogtype = 'recent';

				$orderby = "blog.dateline DESC";
				$orderby_union = "dateline_order DESC";
		}

		if ($vbulletin->GPC['span'])
		{
			$lasttime = TIMENOW - 86400;
			$sql1[] = "blog.dateline >= $lasttime";
			if (!$userinfo)
			{
				$sql2[] = "blog.dateline >= $lasttime";
			}
		}
	}

	if ($vbulletin->GPC['type'])
	{
		$type = $vbulletin->GPC['type'];
		switch ($vbulletin->GPC['type'])
		{
			case 'draft':
				if ($userinfo AND is_member_of_blog($vbulletin->userinfo, $userinfo))
				{
					$sql1[] = 'blog.state = "draft"';
				}
				break;
			case 'pending':
				if ($userinfo AND is_member_of_blog($vbulletin->userinfo, $userinfo))
				{
					$sql1[] = "(blog.dateline > " . TIMENOW . " OR blog.pending = 1)";
				}
				break;
			case 'moderated':
				if (($userinfo AND is_member_of_blog($vbulletin->userinfo, $userinfo)) OR can_moderate_blog('canmoderateentries'))
				{
					$sql1[] = "blog.state = 'moderation'";
					if (!$userinfo)
					{
						$sql2[] = "blog.state = 'moderation'";
					}
				}
				break;
			case 'deleted':
				if (($userinfo AND is_member_of_blog($vbulletin->userinfo, $userinfo)) OR can_moderate_blog())
				{
					$sql1[] = "blog.state = 'deleted'";
					if (!$userinfo)
					{
						$sql2[] = "blog.state = 'deleted'";
					}
				}
				break;
			case 'visible':
				if (($userinfo AND is_member_of_blog($vbulletin->userinfo, $userinfo)) OR can_moderate_blog())
				{
					$sql1[] = "blog.state = 'visible'";
					$sql1[] = "blog.pending = 0";
					$sql1[] = "blog.dateline <= " . TIMENOW;
					if (!$userinfo)
					{
						$sql2[] = "blog.state = 'visible'";
						$sql2[] = "blog.pending = 0";
						$sql2[] = "blog.dateline <= " . TIMENOW;
					}
				}
				break;
			default:
				$type = '';
		}
	}

	$categoryinfo = array();
	if ($userinfo)
	{
		if ($vbulletin->GPC['blogcategoryid'])
		{
			if ($vbulletin->GPC['blogcategoryid'] > 0)
			{
				if (
					(
						(
							$userinfo['userid'] != $vbulletin->userinfo['userid']
								OR
							!$vbulletin->userinfo['categorycache']["{$vbulletin->GPC['blogcategoryid']}"]['entrycount']
						 )
							AND
						isset($vbulletin->userinfo['blogcategorypermissions']["{$vbulletin->GPC['blogcategoryid']}"])
							AND
						!($vbulletin->userinfo['blogcategorypermissions']["{$vbulletin->GPC['blogcategoryid']}"] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewcategory'])
					)
				)
				{
					standard_error(fetch_error('invalidid', $vbphrase['category'], $vbulletin->options['contactuslink']));
				}
				$userids = array(0);
				if ($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancreatecategory'])
				{
					$userids[] = $userinfo['userid'];
				}
				// categories are cached with the user record but the description isn't
				if ($categoryinfo = $db->query_first_slave("
					SELECT title, description, blogcategoryid, userid
					FROM " . TABLE_PREFIX . "blog_category
					WHERE blogcategoryid = " . $vbulletin->GPC['blogcategoryid'] . "
						AND userid IN(" . implode(", ", $userids) . ")
				"))
				{
					$sql1join[] = "INNER JOIN " . TABLE_PREFIX . "blog_categoryuser AS blog_categoryuser ON (blog_categoryuser.blogid = blog.blogid AND blog_categoryuser.userid = $userinfo[userid] AND blog_categoryuser.blogcategoryid = $categoryinfo[blogcategoryid])";
					if ($categoryinfo['userid'] == 0)
					{
						$categoryinfo['title'] = $vbphrase['category' . $categoryinfo['blogcategoryid'] . '_title'];
						$categoryinfo['description'] = $vbphrase['category' . $categoryinfo['blogcategoryid'] . '_desc'];
					}
				}
			}
			else
			{
				$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS blog_categoryuser ON (blog_categoryuser.blogid = blog.blogid)";
				$sql1[] = "blog_categoryuser.userid IS NULL";
				$categoryinfo  = array(
					'title'          => $vbphrase['uncategorized'],
					'blogcategoryid' => -1,
					'description'    => $vbphrase['uncategorized_description'],
				);
			}
		}
		$sql1[] = "blog.userid = $userinfo[userid]";

		if (!can_moderate_blog() AND $userinfo['userid'] != $vbulletin->userinfo['userid'])
		{
			if (!$vbulletin->userinfo['userid'] OR !$userinfo['buddyid'] OR !$userinfo['buddy_canviewmyblog'])
			{
				$sql1[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
			}
		}

		$blogheader = parse_blog_description($userinfo);
		$sidebar =& build_user_sidebar($userinfo, $month, $year);
		$navbits[fetch_seo_url('blog', array('userid' => $userinfo['userid'], 'title' => $blogheader['title']))] = $blogheader['title'];
	}
	else
	{
		if (!can_moderate_blog())
		{
			if ($coventry = fetch_coventry('string'))
			{
				$sql1[] = "blog.userid NOT IN ($coventry)";
			}

			if ($vbulletin->userinfo['userid'])
			{
				$userlist_sql = array();
				$userlist_sql[] = "(options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND ignored.relationid IS NOT NULL)";
				$userlist_sql[] = "(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)";
				$userlist_sql[] = "(options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR buddy.relationid IS NULL) AND (options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL))";
				$sql1[] = "(" . implode(" OR ", $userlist_sql) . ")";

				$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = blog.userid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')";
				$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = blog.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')";
				$sql1[] = "(~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . "
						OR
					(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL))
				";
			}
			else
			{
				$sql1[] = "options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
				$sql1[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
			}
		}

		$sql2[] = "blog.userid IN (" . $vbulletin->userinfo['memberblogids'] . ")";

		if ($vbulletin->GPC['month'] AND $vbulletin->GPC['year'])
		{
			$sql2[] = "blog.dateline >= $starttime";
			$sql2[] = "blog.dateline < $endtime";
		}

		// Limit results when we are viewing "All Entries"
		if ((!$vbulletin->GPC['month'] OR !$vbulletin->GPC['year']) AND !$type AND !$blogtype AND $vbulletin->options['vbblog_recententrycutoff'])
		{
			switch($vbulletin->options['vbblog_recententrycutoff'])
			{
				case '1d':
					$option = '1w';
					break;
				case '1w':
					$option = '1m';
					break;
				case '1m':
					$option = '3m';
					break;
				case '3m':
					$option = '6m';
					break;
				case '6m':
					$option = '1y';
					break;
			}

			switch($option)
			{
				case '1d':
					$cutoff = mktime(date('H'), date('i'), date('s'), date('m'), date('d') - 1, date('y'));
					break;
				case '1m':
				case '3m':
				case '6m':
					$cutoff = mktime(date('H'), date('i'), date('s'), date('m') - intval($vbulletin->options['vbblog_recententrycutoff']), date('d'), date('y'));
					break;
				case '1y':
					$cutoff = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('y') - 1);
					break;
				case '1w':
				default:
					$cutoff = TIMENOW - 604800;
			}
			if ($cutoff)
			{
				$sql1[] = "blog.dateline >= $cutoff";
				$sql2[] = "blog.dateline >= $cutoff";
			}
		}

		if ($vbulletin->GPC['blogcategoryid'])
		{
			if (
				!isset($vbulletin->userinfo['blogcategorypermissions']["{$vbulletin->GPC['blogcategoryid']}"])
					OR
				!($vbulletin->userinfo['blogcategorypermissions']["{$vbulletin->GPC['blogcategoryid']}"] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewcategory'])
				)
				{
					standard_error(fetch_error('invalidid', $vbphrase['category'], $vbulletin->options['contactuslink']));
				}
				$sql1join[] = "INNER JOIN " . TABLE_PREFIX . "blog_categoryuser AS blog_categoryuser ON (blog_categoryuser.blogid = blog.blogid AND blog_categoryuser.blogcategoryid = {$vbulletin->GPC['blogcategoryid']})";
				$sql2join[] = "INNER JOIN " . TABLE_PREFIX . "blog_categoryuser AS blog_categoryuser ON (blog_categoryuser.blogid = blog.blogid AND blog_categoryuser.blogcategoryid = {$vbulletin->GPC['blogcategoryid']})";
				$categoryinfo['title'] = $vbphrase['category' . $vbulletin->GPC['blogcategoryid'] . '_title'];
				$categoryinfo['description'] = $vbphrase['category' . $vbulletin->GPC['blogcategoryid'] . '_desc'];
		}

		$sidebar =& build_overview_sidebar($month, $year);
	}

	if (!$userinfo OR !is_member_of_blog($vbulletin->userinfo, $userinfo))
	{
		$sql1[] = "state IN('" . implode("', '", $state) . "')";
		$sql1[] = "blog.pending = 0";
		$sql1[] = "blog.dateline <= " . TIMENOW;
	}

	if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']))
	{
		$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
		if ($vbulletin->userinfo['userid'])
		{
			$sql1[]= "(cu.blogcategoryid IS NULL OR blog.userid = " . $vbulletin->userinfo['userid'] . ")";
		}
		else
		{
			$sql1[] = "cu.blogcategoryid IS NULL";
		}
	}

	if ($vbulletin->GPC['tag'])
	{
		$tag = $db->query_first("
			SELECT tagid
			FROM " . TABLE_PREFIX . "tag
			WHERE tagtext = '" . $db->escape_string($vbulletin->GPC['tag']) . "'
		");
		if (!$tag)
		{
			standard_error(fetch_error('invalidid', $vbphrase['tag'], $vbulletin->options['contactuslink']));
		}

		$contenttypeid = vB_Types::instance()->getContentTypeID('vBBlog_BlogEntry');
		$tagcontent_join = "INNER JOIN " . TABLE_PREFIX . "tagcontent AS tagcontent ON
			(blog.blogid = tagcontent.contentid AND tagcontent.tagid = $tag[tagid] AND contenttypeid = $contenttypeid)";
		$sql1join[] = $tagcontent_join;
		$sql2join[] = $tagcontent_join;
	}

	// Clear SQL2 since we can't use it.
	if (!$vbulletin->userinfo['userid'] OR !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		$sql2 = array();
	}

	$selectedfilter = array(
		$type => 'selected="selected"'
	);

	if ($vbulletin->options['vbblog_perpage'] > $vbulletin->options['vbblog_maxperpage'])
	{
		$vbulletin->options['vbblog_perpage'] = $vbulletin->options['vbblog_maxperpage'];
	}
	// Set Perpage .. this limits it to 10. Any reason for more?
	if ($vbulletin->GPC['perpage'] == 0)
	{
		$perpage = $vbulletin->options['vbblog_perpage'];
	}
	else if ($vbulletin->GPC['perpage'] > $vbulletin->options['vbblog_maxperpage'])
	{
		$perpage = $vbulletin->options['vbblog_maxperpage'];
	}
	else
	{
		$perpage = $vbulletin->GPC['perpage'];
	}

	$pagenavurl = array();

	// Remove featured blog items at blog home
	if ($featuredblogids OR $vbulletin->GPC['featured'])
	{
		if (!$featuredblogids)
		{
			$temparray = explode(",", $vbulletin->GPC['featured']);
			foreach ($temparray as $v)
			{
				$featuredblogids[] = intval($v);
			}
		}
		$sql1[] = "blog.blogid NOT IN (" . implode(", ", $featuredblogids) . ")";
		if (!empty($sql2))
		{
			$sql2[] = "blog.blogid NOT IN (" . implode(", ", $featuredblogids) . ")";
		}
		$pagenavurl['featured'] = implode(",", $featuredblogids);
	}

	$hook_query_joins1 = $hook_query_where1 = $hook_query_fields1 = '';
	$hook_query_joins2 = $hook_query_where2 = $hook_query_fields2 = '';
	($hook = vBulletinHook::fetch_hook('blog_list_entries_blog_query')) ? eval($hook) : false;

	$totalposts = 0;
	do
	{
		if (!$vbulletin->GPC['pagenumber'])
		{
			$vbulletin->GPC['pagenumber'] = 1;
		}
		$start = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;

		$blogs = $db->query_read_slave("
			" . (!empty($sql2) ? "(" : "") . "
				SELECT SQL_CALC_FOUND_ROWS attach, blog.blogid, blog.dateline, blog.rating
				" . (!empty($sql2) ? ",blog.blogid AS blogid_order, blog.dateline AS dateline_order, blog.rating AS rating_order" : "") . "
				$hook_query_fields1
				FROM " . TABLE_PREFIX . "blog AS blog
				" . (!empty($sql1join) ? implode("\r\n", $sql1join) : "") . "
				LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog.userid)
				$hook_query_joins1
				WHERE " . implode(" AND ", $sql1) . "
				$hook_query_where1
			" . (!empty($sql2) ? ") UNION (
				SELECT attach, blog.blogid, blog.dateline, rating,
					blog.blogid AS blogid_order, blog.dateline AS dateline_order, blog.rating AS rating_order
					$hook_query_fields2
				FROM " . TABLE_PREFIX . "blog AS blog
				" . (!empty($sql2join) ? implode("\r\n", $sql2join) : "") . "
				$hook_query_joins2
				WHERE " . implode(" AND ", $sql2) . "
				$hook_query_where2
			)" : "") . "
			ORDER BY " . (!empty($sql2) ? $orderby_union : $orderby) . "
			LIMIT $start, $perpage
		");
		list($totalposts) = $db->query_first_slave("SELECT FOUND_ROWS()", DBARRAY_NUM);

		if ($start >= $totalposts)
		{
			$vbulletin->GPC['pagenumber'] = ceil($totalposts / $perpage);
		}
	}
	while ($start >= $totalposts AND $totalposts);

	if (!$userinfo)
	{
		$pagenavurl['do'] = 'list';
	}

	if ($blogcategoryid = $vbulletin->GPC['blogcategoryid'])
	{
		$pagenavurl['blogcategoryid'] = $blogcategoryid;
	}

	if ($vbulletin->GPC['month'] AND $vbulletin->GPC['year'])
	{
		$pagenavurl['m'] = $month;
		$pagenavurl['y'] = $year;
		if ($day)
		{
			$pagenavurl['d'] = $day;
		}
	}

	if ($vbulletin->GPC['blogtype'])
	{
		$pagenavurl['blogtype'] = $blogtype;
	}

	if ($type)
	{
		$pagenavurl['type'] = $type;
	}

	if ($perpage != $vbulletin->options['vbblog_perpage'])
	{
		$pagenavurl['pp'] = $perpage;
	}

	if ($vbulletin->GPC['tag'])
	{
		$pagenavurl['tag'] = urlencode(unhtmlspecialchars($vbulletin->GPC['tag']));
	}

	$pagenav = construct_page_nav(
		$vbulletin->GPC['pagenumber'],
		$perpage,
		$totalposts,
		'',
		'',
		'',
		($userinfo ? 'blog' : 'bloghome'),
		($userinfo ? $userinfo : array()),
		$pagenavurl
	);

	$postattach = array();
	$blogids = array();
	$attachcount = 0;

	while ($blog = $db->fetch_array($blogs))
	{
		$blogids[] = $blog['blogid'];
		$attachcount += $blog['attach'];
	}

	$categorytitle = '';
	$categories = array();
	$postattach = array();
	if (!empty($blogids))
	{
		$blogs =& fetch_blog_list($blogids, $attachcount, $postattach, $categories, null, null, $deljoinsql, $orderby);

		// get ignored users
		$ignore = array();
		if (trim($vbulletin->userinfo['ignorelist']))
		{
			$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
			foreach ($ignorelist AS $ignoreuserid)
			{
				$ignore["$ignoreuserid"] = 1;
			}
		}
		DEVDEBUG('ignored users: ' . implode(', ', array_keys($ignore)));

		require_once(DIR . '/includes/class_blog_entry.php');
		$bbcode = new vB_BbCodeParser_Blog_Snippet($vbulletin, fetch_tag_list());
		$factory = new vB_Blog_EntryFactory($vbulletin, $bbcode, $categories);

		$blogbits = '';
		$counter = 0;
		while ($blog = $db->fetch_array($blogs))
		{
			if ($blog['state'] == 'visible')
			{
				$counter++;
			}

			$entry_handler =& $factory->create($blog, $userinfo ? '_User' : '', $ignore);
			$entry_handler->is_first = false;
			$entry_handler->userinfo = $userinfo;
			$entry_handler->attachments = $postattach["$blog[blogid]"];

			if ($counter == 1 AND $blog['state'] == 'visible')
			{
				if ($ad_location['bloglist_first_entry'] = trim(vB_Template::create('ad_bloglist_first_entry')->render(true)))
				{
					$entry_handler->is_first = true;
				}
			}

			$blogbits .= $entry_handler->construct();

			if ($show['tags'])
			{
				$show['tageditor'] = true;
			}
			if ($blog['state'] == 'deleted' OR $ignore["$blog[userid]"])
			{
				$show['quickload'] = true;
			}
		}

		$show['delete'] = (
			(	// # owner can always delete drafts so this needs to be on
				!empty($userinfo) AND is_member_of_blog($vbulletin->userinfo, $userinfo)
			)
				OR
			can_moderate_blog('candeleteentries')
				OR
			can_moderate_blog('canremoveentries')
		);

		$show['undelete'] = (
			(
				can_moderate_blog('candeleteentries')
					OR
				(
					!empty($userinfo)
							AND
						(
							(
								$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_candeleteentry']
									AND
								$userinfo['userid'] == $vbulletin->userinfo['userid']
							)
								OR
							(
								is_member_of_blog($vbulletin->userinfo, $userinfo)
										AND
									$userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_candeleteentry']
										AND
									$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_candeleteentry']
										AND
									(
										$userinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanageotherentry']
											OR
										$userinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['candeleteentry']
									)
							)
						)
				)
			)
		);

		$show['approve'] = (can_moderate_blog('canmoderateentries') OR ($vbulletin->userinfo['userid'] AND $userinfo['userid'] == $vbulletin->userinfo['userid']));
		$show['inlinemod'] = ($show['undelete'] OR $show['delete'] OR $show['unapprove']);
	}

	if ($vbulletin->GPC['month'] AND $vbulletin->GPC['year'])
	{
		if (!empty($categoryinfo))
		{
			$pageinfo = array(
				'blogcategoryid' => $categoryinfo['blogcategoryid'],
			);

			$navbits[fetch_seo_url('blog', $userinfo, $pageinfo, 'userid', 'blog_title')] = $categoryinfo['title'];
		}
		$monthname = $vbphrase[strtolower(gmdate('F', gmmktime(12, 0, 0, $month, 1, $year)))];
		if ($type)
		{
			$navbits[] = $day ? construct_phrase($vbphrase[$type . '_entries_for_x_y_z'], $monthname, $day, $year) : construct_phrase($vbphrase[$type . '_entries_for_x_y'], $monthname, $year);
		}
		else
		{
			$navbits[] = $day ? construct_phrase($vbphrase['entries_for_x_y_z'], $monthname, $day, $year) : construct_phrase($vbphrase['entries_for_x_y'], $monthname, $year);
		}
	}
	else if ($show['bloghome'])
	{
		$navbits[fetch_seo_url('bloghome', array())] = $vbphrase['blogs'];
		$navbits[] = $vbphrase['recent_blogs_posts'];
	}
	else if ($type)
	{
		if (!empty($categoryinfo))
		{
			$navbits[] = $categoryinfo['title'];
		}
		elseif ($blogtype != 'recent')
		{
			$navbits[] = $vbphrase[$blogtype . '_' . $type . '_blog_entries'];
		}
		else
		{
			$navbits[] = $vbphrase[$type . '_blog_entries'];
		}
	}
	else if ($blogtype != 'recent')
	{
		if (!empty($categoryinfo))
		{
			$pageinfo = array(
				'blogcategoryid' => $categoryinfo['blogcategoryid'],
			);

			$navbits[fetch_seo_url('blog', $userinfo, $pageinfo, 'userid', 'blog_title')] = $categoryinfo['title'];
		}
		$navbits[] = $vbphrase[$blogtype . '_blog_entries'];
	}
	else if (!empty($categoryinfo))
	{
		$navbits[] = $categoryinfo['title'];
	}

	$show['filter'] = (can_moderate_blog() OR ($vbulletin->userinfo['userid'] AND $vbulletin->userinfo['userid'] == $userinfo['userid']));
	$show['filter_moderation'] = (can_moderate_blog('canmoderateentries') OR $vbulletin->userinfo['userid'] == $userinfo['userid']);
	$show['filter_owner'] = ($vbulletin->userinfo['userid'] == $userinfo['userid']);
	$show['category_description'] = (!empty($categoryinfo));

	if ($vbulletin->options['quickedit'])
	{
		$show['quickedit'] = true;
	}

	if (empty($navbits))
	{
		$navbits = array('' => $vbphrase['blog_entries']);
	}

	($hook = vBulletinHook::fetch_hook('blog_list_entries_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('blog_list_entries');
		$templater->register('blogheader', $blogheader);
		$templater->register('featured_blogbits', $featured_blogbits);
		$templater->register('display', $display);
		$templater->register('recentblogbits', $recentblogbits);
		$templater->register('recentcommentbits', $recentcommentbits);
		$templater->register('blogbits', $blogbits);
		$templater->register('blogcategoryid', $blogcategoryid);
		$templater->register('blogtype', $blogtype);
		$templater->register('categoryinfo', $categoryinfo);
		$templater->register('day', $day);
		$templater->register('month', $month);
		$templater->register('pagenav', $pagenav);
		$templater->register('selectedfilter', $selectedfilter);
		$templater->register('url', $url);
		$templater->register('userinfo', $userinfo);
		$templater->register('vBeditTemplate', $vBeditTemplate);
		$templater->register('year', $year);
		$templater->register('editor_js', vB_Ckeditor::getJsIncludes());
	$content = $templater->render();
}

// #######################################################################
if ($_REQUEST['do'] == 'bloglist')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber'     => TYPE_UINT,
		'perpage'        => TYPE_UINT,
		'blogtype'       => TYPE_NOHTML,
		'sortorder'      => TYPE_NOHTML,
		'sortfield'      => TYPE_NOHTML,
	));

	$type = '';

	$sql = array();
	$sqljoin = array();
	$sqlfields = array();

	($hook = vBulletinHook::fetch_hook('blog_list_start')) ? eval($hook) : false;

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		$sql[] = "blog.userid = " . $vbulletin->userinfo['userid'];
	}
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		$sql[] = "blog.userid <> " . $vbulletin->userinfo['userid'];
	}
	if ($coventry = fetch_coventry('string') AND !can_moderate_blog())
	{
		$sql[] = "blog.userid NOT IN ($coventry)";
	}

	if (!can_moderate_blog())
	{
		if ($vbulletin->userinfo['userid'])
		{
			$userlist_sql = array();
			$userlist_sql[] = "blog_user.bloguserid = " . $vbulletin->userinfo['userid'];
			$userlist_sql[] = "(options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND ignored.relationid IS NOT NULL)";
			$userlist_sql[] = "(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)";
			$userlist_sql[] = "(options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR buddy.relationid IS NULL) AND (options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL))";
			$sql[] = "(" . implode(" OR ", $userlist_sql) . ")";

			$sqljoin[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = blog_user.bloguserid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')";
			$sqljoin[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = blog_user.bloguserid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')";
			$sqlfields[] = "ignored.relationid AS ignoreid, buddy.relationid AS buddyid";
		}
		else
		{
			$sql[] = "options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
		}
	}

	$sqljoin[] = $vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "";

	if ($vbulletin->userinfo['userid'] AND in_coventry($vbulletin->userinfo['userid'], true))
	{
		$sqlfields[] = "IF(blog_tachyentry.userid IS NULL, blog.lastcomment, blog_tachyentry.lastcomment) AS lastcomment";
		$sqlfields[] = "IF(blog_tachyentry.userid IS NULL, blog.lastcommenter, blog_tachyentry.lastcommenter) AS lastcommenter";
		$sqlfields[] = "IF(blog_tachyentry.userid IS NULL, blog.lastblogtextid, blog_tachyentry.lastblogtextid) AS lastblogtextid";

		$sqljoin[] = "LEFT JOIN " . TABLE_PREFIX . "blog_tachyentry AS blog_tachyentry ON (blog_tachyentry.blogid = blog_user.lastblogid AND blog_tachyentry.userid = " . $vbulletin->userinfo['userid'] . ")";
		$sqljoin[] = "LEFT JOIN " . TABLE_PREFIX . "blog_text AS blog_text ON (blog_text.blogtextid = IF(blog_tachyentry.userid IS NULL, blog.lastblogtextid, blog_tachyentry.lastblogtextid))";
	}
	else
	{
		$sqljoin[] = "LEFT JOIN " . TABLE_PREFIX . "blog_text AS blog_text ON (blog_text.blogtextid = blog_user.lastblogtextid)";
	}

	$sidebar =& build_overview_sidebar($month, $year);

	switch($vbulletin->GPC['blogtype'])
	{
		case 'best':
			$blogtype = 'best';
			break;
		default:
			$blogtype = 'all';
	}

	$pagenavurl = array('do' => 'bloglist');
	// Set Perpage .. this limits it to 10. Any reason for more?
	if ($vbulletin->options['vbblog_perpage'] > $vbulletin->options['vbblog_maxperpage'])
	{
		$vbulletin->options['vbblog_perpage'] = $vbulletin->options['vbblog_maxperpage'];
	}
	if ($vbulletin->GPC['perpage'] == 0)
	{
		if ($blogtype == 'all')
		{
			$perpage = 15;
		}
		else
		{
			$perpage = $vbulletin->options['vbblog_perpage'];
		}
	}
	else if ($vbulletin->GPC['perpage'] > $vbulletin->options['vbblog_maxperpage'] AND $blogtype == 'best')
	{
		$perpage = $vbulletin->options['vbblog_maxperpage'];
		$pagenavurl['pp'] = $perpage;
	}
	else if ($vbulletin->GPC['perpage'] > 20 AND $blogtype == 'all')
	{
		$perpage = 20;
		$pagenavurl['pp'] = $perpage;
	}
	else
	{
		$perpage = $vbulletin->GPC['perpage'];
		$pagenavurl['pp'] = $perpage;
	}

	// This uses the lastblog,entries index which avoids the filesort! filesort + limit = BAD
	$sql[] = "blog_user.lastblog > 0";
	$sql[] = "blog_user.entries > 0";

	switch($blogtype)
	{
		case 'best':
			$sql[] = "blog_user.ratingnum >= 0";// . intval($vbulletin->options['vbblog_ratinguser']);
			$orderby = "blog_user.rating DESC";
			$pagenavurl['blogtype'] = $blogtype;
			break;
		case 'all':
			$sortfield  =& $vbulletin->GPC['sortfield'];
			if ($vbulletin->GPC['sortorder'] != 'asc')
			{
				$vbulletin->GPC['sortorder'] = 'desc';
				$sqlsortorder = 'DESC';
				$order = array('desc' => 'selected="selected"');
			}
			else
			{
				$sqlsortorder = '';
				$order = array('asc' => 'selected="selected"');
			}

			switch ($sortfield)
			{
				case 'username':
					$sqlsortfield = 'user.' . $sortfield;
					break;
				case 'title':
					$sqlsortfield = 'blogtitle';
					break;
				case 'entries':
					$sqlsortfield = 'blog_user.entries';
					break;
				case 'comments':
				case 'lastblog':
					$sqlsortfield = 'blog_user.' . $sortfield;
					break;
				case 'rating':
					$sqlsortfield = 'order_rating';
					break;
				default:
					$sqlsortfield = 'blog_user.lastblog';
					$sortfield = 'lastblog';
			}

			if ($sortfield != 'lastblog')
			{
				$pagenavurl['sort'] = $sortfield;
			}
			if ($vbulletin->GPC['sortorder'] != 'desc')
			{
				$pagenavurl['order'] = 'asc';
			}

			$orderby = $sqlsortfield . ' ' . $sqlsortorder;

			$sort = array($sortfield => 'selected="selected"');

			//bloghome is a "generation only" url class, which currently means that it
			//will be locked to FRIENDLY_URL_OFF anyway.  However the current use very much depends
			//on the classic format since we add params to the query string somewhere in the templates
			//(using sorturl as a base in multiple locations).  No sense borrowing trouble now, but
			//if we change the behavior of the bloghome url generation we'll need to fix that before
			//we change so if we hard code the FRIENDLY_URL_OFF here it won't mysteriously break
			//if changes are made to the class.
			require_once(DIR . '/includes/class_friendly_url.php');
			$pagevars = array('do' => 'bloglist');
			if ($perpage != 15)
			{
				$pagevars['pp'] = $perpage;
			}
			$sorturl = vB_Friendly_Url::fetchLibrary($vbulletin, 'bloghome', array(), $pagevars)->get_url(FRIENDLY_URL_OFF);

			$oppositesort = ($vbulletin->GPC['sortorder'] == 'asc' ? 'desc' : 'asc');

			$sortorder = array(
				'title' => ($sortfield == 'title') ? $oppositesort : 'asc',
				'entries' => ($sortfield == 'entries') ? $oppositesort : 'desc',
				'comments' => ($sortfield == 'comments') ? $oppositesort : 'desc',
				'lastblog' => ($sortfield == 'lastblog') ? $oppositesort : 'desc',
			);

			$templater = vB_Template::create('forumdisplay_sortarrow');
				$templater->register('oppositesort', $oppositesort);
			$sortarrow["$sortfield"] = $templater->render();
	}

	// get ignored users
	$ignore = array();
	if (trim($vbulletin->userinfo['ignorelist']))
	{
		$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
		foreach ($ignorelist AS $ignoreuserid)
		{
			$ignore["$ignoreuserid"] = 1;
		}
	}
	DEVDEBUG('ignored users: ' . implode(', ', array_keys($ignore)));

	$totalblogs = 0;
	($hook = vBulletinHook::fetch_hook('blog_list_blog_query')) ? eval($hook) : false;
	do
	{
		if (!$vbulletin->GPC['pagenumber'])
		{
			$vbulletin->GPC['pagenumber'] = 1;
		}
		$start = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;

		$blogs = $db->query_read_slave("
			SELECT SQL_CALC_FOUND_ROWS
				user.*,
				blog.firstblogtextid,
				blog_text.pagetext,
				IF (blog_user.title <> '', blog_user.title, user.username) AS blogtitle,
				IF (blog_user.ratingnum >= " . intval($vbulletin->options['vbblog_ratinguser']) . ", blog_user.rating, 0) AS order_rating,
				blog_user.lastblog, blog_user.lastblogid AS lastblogid, blog_user.lastblogtitle,
				blog_user.lastcomment, blog_user.lastblogtextid AS lastblogtextid, blog_user.lastcommenter,
				blog_user.ratingnum, blog_user.ratingtotal, blog_user.title, blog_user.entries, blog_user.comments, blog_user.title, blog.categories,
				blog2.categories AS categories_lastcomment,
				IF(user.displaygroupid = 0, user.usergroupid, user.displaygroupid) AS displaygroupid, infractiongroupid, options_ignore, options_buddy, options_member, options_guest
				" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
				" . ($vbulletin->userinfo['userid'] ? ", IF(blog_subscribeuser.blogsubscribeuserid, 1, 0) AS blogsubscribed" : "") . "
				" . (!empty($sqlfields) ? ", " . implode(", ", $sqlfields) : "") . "
			FROM " . TABLE_PREFIX . "blog_user AS blog_user
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (blog_user.bloguserid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "blog AS blog ON (blog.blogid = blog_user.lastblogid)
			" . ($vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "blog_subscribeuser AS blog_subscribeuser ON (blog.userid = blog_subscribeuser.bloguserid AND blog_subscribeuser.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
			" . implode("\r\n", $sqljoin) . "
			LEFT JOIN " . TABLE_PREFIX . "blog AS blog2 ON (blog2.blogid = blog_text.blogid)
			WHERE " . implode("\r\n\tAND ", $sql) . "
			ORDER BY $orderby
			LIMIT $start, $perpage
		");
		$totalblogs = $db->found_rows();

		if ($start >= $totalblogs)
		{
			$vbulletin->GPC['pagenumber'] = ceil($totalblogs / $perpage);
		}
	}
	while ($start >= $totalblogs AND $totalblogs);

	$pagenav = construct_page_nav(
		$vbulletin->GPC['pagenumber'],
		$perpage,
		$totalblogs,
		'',
		'',
		'',
		'bloghome',
		array(),
		$pagenavurl
	);

	while ($blog = $db->fetch_array($blogs))
	{
		$blog = array_merge($blog, convert_bits_to_array($blog['options'], $vbulletin->bf_misc_useroptions));
		$blog = array_merge($blog, convert_bits_to_array($blog['adminoptions'], $vbulletin->bf_misc_adminoptions));

		$show['private'] = false;
		if (can_moderate() AND $blog['userid'] != $vbulletin->userinfo['userid'])
		{
			$membercanview = $blog['options_member'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			$buddiescanview = $blog['options_buddy'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			if (!$membercanview AND (!$blog['buddyid'] OR !$buddiescanview))
			{
				$show['private'] = true;
			}
		}

		if ($blog['ratingnum'] > 0 AND $blog['ratingnum'] >= $vbulletin->options['vbblog_ratinguser'])
		{
			$blog['ratingavg'] = vb_number_format($blog['ratingtotal'] / $blog['ratingnum'], 2);
			$blog['rating'] = intval(round($blog['ratingtotal'] / $blog['ratingnum']));
			$show['rating'] = true;
		}
		else
		{
			$blog['ratingavg'] = 0;
			$blog['rating'] = 0;
			$show['rating'] = false;
		}

		$blog['entries'] = vb_number_format($blog['entries']);
		$blog['comments'] = vb_number_format($blog['comments']);

		$blog['lastentrydate'] = vbdate($vbulletin->options['dateformat'], $blog['lastblog'], true);
		$blog['lastentrytime'] = vbdate($vbulletin->options['timeformat'], $blog['lastblog']);

		$lastentrycats = explode(',', $blog['categories']);
		$lastcommentcats = explode(',', $blog['categories_lastcomment']);

		$show['lastentry'] = array_intersect($vbulletin->userinfo['blogcategorypermissions']['cantview'], $lastentrycats) ? false : true;
		$show['lastcomment'] = array_intersect($vbulletin->userinfo['blogcategorypermissions']['cantview'], $lastcommentcats) ? false : true;

		if ($blogtype == 'all')
		{
			$blog['entrytitle'] = fetch_trimmed_title($blog['lastblogtitle'], 20);
			if ($blog['title'])
			{
				$blog['title'] = fetch_trimmed_title($blog['title'], 50);
			}
			$templater = vB_Template::create('blog_blog_row');
				$templater->register('blog', $blog);
				$templater->register('thread', $thread);
			$blogbits .= $templater->render();
		}
		else
		{
			fetch_musername($blog);
			fetch_avatar_html($blog);
			$blog['onlinestatus'] = 0;
			$blog['commentexcerpt'] = htmlspecialchars_uni(fetch_trimmed_title($blog['pagetext'], 50));

			// now decide if we can see the user or not
			if ($blog['lastactivity'] > (TIMENOW - $vbulletin->options['cookietimeout']) AND $blog['lastvisit'] != $blog['lastactivity'])
			{
				if ($blog['invisible'])
				{
					if (($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehidden']) OR $blog['userid'] == $vbulletin->userinfo['userid'])
					{
						// user is online and invisible BUT bbuser can see them
						$blog['onlinestatus'] = 2;
					}
				}
				else
				{
					// user is online and visible
					$blog['onlinestatus'] = 1;
				}
			}

			$blog['commentdate'] = vbdate($vbulletin->options['dateformat'], $blog['lastcomment'], true);
			$blog['commenttime'] = vbdate($vbulletin->options['timeformat'], $blog['lastcomment']);
			$show['lastcomment'] = ($show['lastcomment'] AND $blog['lastblogtextid'] AND $blog['lastblogtextid'] != $blog['firstblogtextid']);

			if (!$blog['title'])
			{
				$blog['title'] = $blog['username'];
			}

			$pageinfo = array('bt' => $blog['lastblogtextid']);
			if ($ignore["$blog[userid]"])
			{
				$templater = vB_Template::create('blog_list_blogs_blog_ignore');
					$templater->register('blog', $blog);
					$templater->register('status', $status);
					$templater->register('pageinfo', $pageinfo);
				$blogbits .= $templater->render();
			}
			else
			{
				$templater = vB_Template::create('blog_list_blogs_blog');
					$templater->register('blog', $blog);
					$templater->register('pageinfo', $pageinfo);
				$blogbits .= $templater->render();
			}
		}
	}

	($hook = vBulletinHook::fetch_hook('blog_list_complete')) ? eval($hook) : false;

	if ($blogtype == 'all')
	{
		$navbits[] = $vbphrase['blog_list'];
		$templater = vB_Template::create('blog_list_blogs_all');
			$templater->register('blogbits', $blogbits);
			$templater->register('pagenav', $pagenav);
			$templater->register('sortarrow', $sortarrow);
			$templater->register('sorturl', $sorturl);
			$templater->register('sortorder', $sortorder);
		$content = $templater->render();
	}
	else
	{
		$navbits[] = $vbphrase['best_blogs'];
		$templater = vB_Template::create('blog_list_blogs_best');
			$templater->register('blogbits', $blogbits);
			$templater->register('pagenav', $pagenav);
		$content = $templater->render();
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'comments')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'userid'     => TYPE_UINT,
		'type'       => TYPE_STR,
	));

	$sql_and = array();
	$sql_or = array();

	($hook = vBulletinHook::fetch_hook('blog_comments_start')) ? eval($hook) : false;

	// Set Perpage .. this limits it to 10. Any reason for more?
	if ($vbulletin->GPC['perpage'] == 0 OR $vbulletin->GPC['perpage'] > $vbulletin->options['blog_commentsperpage'])
	{
		$perpage = $vbulletin->options['blog_commentsperpage'];
	}
	else
	{
		$perpage = $vbulletin->GPC['perpage'];
	}

	if ($vbulletin->GPC['userid'])
	{
		$userinfo = verify_id('user', $vbulletin->GPC['userid'], 1, 1, 10);
		cache_permissions($userinfo, false);
		$show['entry_userinfo'] = false;
		if (!$userinfo['canviewmyblog'])
		{
			print_no_permission();
		}
		if (in_coventry($userinfo['userid']) AND !can_moderate_blog())
		{
			standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
		}

		if ($vbulletin->userinfo['userid'] == $userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
		{
			print_no_permission();
		}

		if ($vbulletin->userinfo['userid'] != $userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
		{
			if ($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
			{
				// Can't view other's entries so off you go to your own blog.
				$bloginfo = array(
					'userid' => $vbulletin->userinfo['userid'],
					'title'  => $vbulletin->userinfo['blog_title'] ? $vbulletin->userinfo['blog_title'] : $vbulletin->userinfo['username'],
				);
				exec_header_redirect(fetch_seo_url('blog|js', $bloginfo));
			}
			else
			{
				print_no_permission();
			}
		}
		$sql_and[] = "blog_text.bloguserid = $userinfo[userid]";

		track_blog_visit($userinfo['userid']);
	}
	else
	{
		$userinfo = array();
		$show['entry_userinfo'] = true;
	}

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		$sql_and[] = "blog.userid = " . $vbulletin->userinfo['userid'];
	}
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND $vbulletin->userinfo['userid'])
	{
		$sql_and[] = "blog.userid <> " . $vbulletin->userinfo['userid'];
	}

	$state = array('visible');
	$commentstate = array('visible');

	if (can_moderate_blog('canmoderatecomments') OR (!empty($userinfo) AND is_member_of_blog($vbulletin->userinfo, $userinfo)))
	{
		$commentstate[] = 'moderation';
	}
	if (can_moderate_blog('canmoderateentries') OR (!empty($userinfo) AND is_member_of_blog($vbulletin->userinfo, $userinfo)))
	{
		$state[] = 'moderation';
	}
	if (can_moderate_blog() OR (!empty($userinfo) AND is_member_of_blog($vbulletin->userinfo, $userinfo)))
	{
		$state[] = 'deleted';
		$commentstate[] = 'deleted';
		$deljoinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_deletionlog AS blog_deletionlog ON (blog_text.blogtextid = blog_deletionlog.primaryid AND blog_deletionlog.type = 'blogtextid')";
	}

	$sql_and[] = "blog.state IN('" . implode("', '", $state) . "')";
	$sql_and[] = "blog.dateline <= " . TIMENOW;
	$sql_and[] = "blog.pending = 0";
	$sql_and[] = "blog_text.state IN('" . implode("', '", $commentstate) . "')";
	$sql_and[] = "blog.firstblogtextid <> blog_text.blogtextid";

	$type = '';
	if ($vbulletin->GPC['type'])
	{
		$type = $vbulletin->GPC['type'];
		switch ($vbulletin->GPC['type'])
		{
			case 'moderated':
				if ((!empty($userinfo) AND is_member_of_blog($vbulletin->userinfo, $userinfo)) OR can_moderate_blog('canmoderateentries'))
				{
					$sql_and[] = "blog_text.state = 'moderation'";
				}
				break;
			case 'deleted':
				if ((!empty($userinfo) AND is_member_of_blog($vbulletin->userinfo, $userinfo)) OR can_moderate_blog())
				{
					$sql_and[] = "blog_text.state = 'deleted'";
				}
				break;
			default:
				$type = '';
		}
	}

	if (!$userinfo AND !$type)
	{
		// Limit results when we are viewing "All Comments", from the "Find More" link on blog home
		if ($vbulletin->options['vbblog_recentcommentcutoff'])
		{
			switch($vbulletin->options['vbblog_recentcommentcutoff'])
			{
				case '1d':
					$option = '1w';
					break;
				case '1w':
					$option = '1m';
					break;
				case '1m':
					$option = '3m';
					break;
				case '3m':
					$option = '6m';
					break;
				case '6m':
					$option = '1y';
					break;
			}

			switch($option)
			{
				case '1m':
				case '3m':
				case '6m':
					$sql_and[] = "blog_text.dateline >= " .  mktime(date('H'), date('i'), date('s'), date('m') - intval($vbulletin->options['vbblog_recentcommentcutoff']), date('d'), date('y'));
					break;
				case '1y':
					$cutoff = $sql_and[] = "blog_text.dateline >= " . mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('y') - 1);
					break;
				case '1w':
					$sql_and[] = "blog_text.dateline >= " .  (TIMENOW - 604800);
			}
		}
	}

	$selectedfilter = array(
		$type => 'selected="selected"'
	);

	$sql_join = array();
	if (!can_moderate_blog())
	{
		if ($vbulletin->userinfo['userid'])
		{
			if ($userinfo['userid'] != $vbulletin->userinfo['userid'])
			{
				$sql_or[] = "blog.userid IN (" . $vbulletin->userinfo['memberblogids'] . ")";
				$sql_or[] = "(options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND ignored.relationid IS NOT NULL)";
				$sql_or[] = "(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)";
				$sql_or[] = "(options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR buddy.relationid IS NULL) AND (options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL))";
				$sql_and[] = "(" . implode(" OR ", $sql_or) . ")";

				$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = blog.userid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')";
				$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = blog.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')";

				$sql_and[] = "
					(blog.userid IN (" . $vbulletin->userinfo['memberblogids'] . ")
						OR
					~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . "
						OR
					(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL))";
				$sqlfields = ",ignored.relationid AS ignoreid, buddy.relationid AS buddyid";
			}
		}
		else
		{
			$sql_and[] = "options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			$sql_and[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
		}
	}

	($hook = vBulletinHook::fetch_hook('blog_comments_comments_query')) ? eval($hook) : false;

	// get ignored users
	$ignore = array();
	if (trim($vbulletin->userinfo['ignorelist']))
	{
		$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
		foreach ($ignorelist AS $ignoreuserid)
		{
			$ignore["$ignoreuserid"] = 1;
		}
	}
	DEVDEBUG('ignored users: ' . implode(', ', array_keys($ignore)));

	$comment_count = 0;
	$responsebits = '';
	$pagetext_cachable = true;

	require_once(DIR . '/includes/class_bbcode_blog.php');
	require_once(DIR . '/includes/class_blog_response.php');

	$bbcode = new vB_BbCodeParser_Blog($vbulletin, fetch_tag_list());
	$factory = new vB_Blog_ResponseFactory($vbulletin, $bbcode, $bloginfo);

	if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']))
	{
		$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
		$sql_and[] = "cu.blogcategoryid IS NULL";
	}

	// Add union query here so blog owners can see comments attached to deleted entries of their own
	do
	{
		if (!$vbulletin->GPC['pagenumber'])
		{
			$vbulletin->GPC['pagenumber'] = 1;
		}
		$start = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->options['blog_commentsperpage'];
		$pagenumber = $vbulletin->GPC['pagenumber'];
		$comments = $db->query_read("
			SELECT SQL_CALC_FOUND_ROWS
				blog_text.username AS postusername, blog_text.ipaddress AS blogipaddress, blog_text.state, blog_text.dateline, blog_text.pagetext, blog_text.allowsmilie, blog_text.blogtextid, blog_text.title,
				blog.pending, blog.userid AS blog_userid, blog.blogid, blog.title AS entrytitle, blog.state AS blog_state, blog.firstblogtextid, blog.options AS blogoptions, blog_user.memberids, blog_user.memberblogids, blog.postedby_userid, blog.postedby_username,
				user2.usergroupid AS blog_usergroupid, user2.infractiongroupids AS blog_infractiongroupids, user2.membergroupids AS blog_membergroupids,
				user.*,
				blog_user.title AS blogtitle,
				IF(user.displaygroupid = 0, user.usergroupid, user.displaygroupid) AS displaygroupid, user.infractiongroupid, options_ignore, options_buddy, options_member, options_guest,
				blog_editlog.userid AS edit_userid, blog_editlog.dateline AS edit_dateline, blog_editlog.reason AS edit_reason, blog_editlog.username AS edit_username
			" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
			" . ($deljoinsql ? ",blog_deletionlog.moddelete AS del_moddelete, blog_deletionlog.userid AS del_userid, blog_deletionlog.username AS del_username, blog_deletionlog.reason AS del_reason" : "") . "
			" . (($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid']) ? ", blog_read.readtime AS blogread, blog_userread.readtime AS bloguserread" : "") . "
			" . ($vbulletin->userinfo['userid'] ? ", gm.permissions AS grouppermissions" : "") . "
			$sqlfields
			FROM " . TABLE_PREFIX . "blog_text AS blog_text
			LEFT JOIN " . TABLE_PREFIX . "blog AS blog ON (blog.blogid = blog_text.blogid)
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog_text.userid)
			INNER JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
			LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog.userid)
			LEFT JOIN " . TABLE_PREFIX . "blog_editlog AS blog_editlog ON (blog_editlog.blogtextid = blog_text.blogtextid)
			" . (($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid']) ? "
			LEFT JOIN " . TABLE_PREFIX . "blog_read AS blog_read ON (blog_read.blogid = blog.blogid AND blog_read.userid = " . $vbulletin->userinfo['userid'] . ")
			LEFT JOIN " . TABLE_PREFIX . "blog_userread AS blog_userread ON (blog_userread.bloguserid = blog.userid AND blog_userread.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
			" . ($vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")" : '') . "
			$deljoinsql
			" . (!empty($sql_join) ? implode("\r\n", $sql_join) : "") . "
			" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
			WHERE " . implode("\r\n\tAND ", $sql_and) . "
			ORDER BY blog_text.dateline DESC
			LIMIT $start, $perpage
		");
		list($comment_count) = $db->query_first("SELECT FOUND_ROWS()", DBARRAY_NUM);

		if ($start >= $comment_count)
		{
			$vbulletin->GPC['pagenumber'] = ceil($comment_count / $perpage);
		}
	}
	while ($start >= $comment_count AND $comment_count);

	$pagenavurl = array('do' => 'comments');
	if ($type)
	{
		$pagenavurl['type'] = $type;
	}
	if ($perpage != $vbulletin->options['blog_commentsperpage'])
	{
		$pagenavurl['pp'] = $perpage;
	}

	$pagenav = construct_page_nav(
		$vbulletin->GPC['pagenumber'],
		$perpage,
		$comment_count,
		'',
		'',
		'',
		($userinfo ? 'blog' : 'bloghome'),
		($userinfo ? $userinfo : array()),
		$pagenavurl
	);

	$record_count = 0;
	while ($comment = $db->fetch_array($comments))
	{
		$response_handler =& $factory->create($comment, 'Comment', $ignore);
		$record_count++;
		$response_handler->userinfo = $userinfo;
		$response_handler->cachable = $pagetext_cachable;
		$response_handler->linkblog = true;
		$responsebits .= $response_handler->construct();

		if ($pagetext_cachable AND $comment['pagetexthtml'] == '')
		{
			if (!empty($saveparsed))
			{
				$saveparsed .= ',';
			}
			$saveparsed .= "($comment[blogtextid], " . intval($bloginfo['lastcomment']) . ', ' . intval($response_handler->parsed_cache['has_images']) . ", '" . $db->escape_string($response_handler->parsed_cache['text']) . "', " . intval(STYLEID) . ", " . intval(LANGUAGEID) . ")";
		}

		if ($comment['dateline'] > $displayed_dateline)
		{
			$displayed_dateline = $comment['dateline'];
		}

		if ($comment['state'] == 'deleted' OR $ignore["$comment[userid]"])
		{	// be aware $factory->create can change $response['state']
			$show['quickload'] = true;
		}
	}

	$show['delete'] = true;
	$show['undelete'] = true;
	$show['approve'] = true;

	$show['inlinemod'] = (($show['delete'] OR $show['approve'] OR $show['undelete'])
		AND
	(
		can_moderate_blog()
			OR
		(
			!empty($userinfo)
				AND
			is_member_of_blog($vbulletin->userinfo, $userinfo)
		)
	));

	if ($userinfo)
	{
		$blogheader = parse_blog_description($userinfo);
		$sidebar =& build_user_sidebar($userinfo, $month, $year);
		$navbits[fetch_seo_url('blog', array('userid' => $userinfo['userid'], 'title' => $blogheader['title']))] = $blogheader['title'];
	}
	else
	{
		$sidebar =& build_overview_sidebar();
	}

	if ($type)
	{
		$navbits[] = $vbphrase[$type . '_comments'];
	}
	else
	{
		$navbits[] = $vbphrase['comments'];
	}

	if ($vbulletin->options['quickedit'])
	{
		$show['quickedit'] = true;
	}

	$show['filter'] = (can_moderate_blog() OR ($vbulletin->userinfo['userid'] AND $vbulletin->userinfo['userid'] == $userinfo['userid']));
	$show['filter_moderation'] = (can_moderate_blog('canmoderatecomments') OR $vbulletin->userinfo['userid'] == $userinfo['userid']);

	($hook = vBulletinHook::fetch_hook('blog_comments_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('blog_list_comments');
		$templater->register('bloginfo', $bloginfo);
		$templater->register('blogheader', $blogheader);
		$templater->register('pagenav', $pagenav);
		$templater->register('start', $start + 1);
		$templater->register('end', $record_count + $start);
		$templater->register('responsebits', $responsebits);
		$templater->register('selectedfilter', $selectedfilter);
		$templater->register('url', $url);
		$templater->register('userinfo', $userinfo);
		$templater->register('vBeditTemplate', $vBeditTemplate);
		$templater->register('comment_count', $comment_count);
	$content = $templater->render();
}

// #######################################################################
if ($_REQUEST['do'] == 'sendtofriend' OR $_POST['do'] == 'dosendtofriend')
{
	$bloginfo = verify_blog($blogid);

	if ($bloginfo['state'] != 'visible' OR $bloginfo['pending'])
	{
		print_no_permission();
	}

	if (!$vbulletin->options['enableemail'] OR !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canemail']))
	{
		standard_error(fetch_error('emaildisabled'));
	}

	if (!$bloginfo OR
		(!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND $bloginfo['userid'] == $vbulletin->userinfo['userid']) OR
		(!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']) AND $bloginfo['userid'] != $vbulletin->userinfo['userid']))
	{
		print_no_permission();
	}

	$perform_floodcheck = (
		!($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
		AND $vbulletin->options['emailfloodtime']
		AND $vbulletin->userinfo['userid']
	);

	if ($perform_floodcheck AND ($timepassed = TIMENOW - $vbulletin->userinfo['emailstamp']) < $vbulletin->options['emailfloodtime'])
	{
		standard_error(fetch_error('emailfloodcheck', $vbulletin->options['emailfloodtime'], ($vbulletin->options['emailfloodtime'] - $timepassed)));
	}

	track_blog_visit($bloginfo['userid']);
}

// ############################### start do send to friend ###############################
if ($_POST['do'] == 'dosendtofriend')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'sendtoname'   => TYPE_STR,
		'sendtoemail'  => TYPE_STR,
		'emailsubject' => TYPE_STR,
		'emailmessage' => TYPE_STR,
		'username'     => TYPE_STR,
		'imagestamp'   => TYPE_STR,
		'imagehash'    => TYPE_STR,
		'humanverify'  => TYPE_ARRAY,
	));

	// Values that are used in phrases or error messages
	$sendtoname =& $vbulletin->GPC['sendtoname'];
	$emailmessage =& $vbulletin->GPC['emailmessage'];
	$errors = array();

	if ($sendtoname == '' OR !is_valid_email($vbulletin->GPC['sendtoemail']) OR $vbulletin->GPC['emailsubject'] == '' OR $emailmessage == '')
	{
		$errors[] = fetch_error('requiredfields');
	}

	if ($perform_floodcheck)
	{
		require_once(DIR . '/includes/class_floodcheck.php');
		$floodcheck = new vB_FloodCheck($vbulletin, 'user', 'emailstamp');
		$floodcheck->commit_key($vbulletin->userinfo['userid'], TIMENOW, TIMENOW - $vbulletin->options['emailfloodtime']);
		if ($floodcheck->is_flooding())
		{
			$errors[] = fetch_error('emailfloodcheck', $vbulletin->options['emailfloodtime'], $floodcheck->flood_wait());
		}
	}

	if (fetch_require_hvcheck('contactus'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verify =& vB_HumanVerify::fetch_library($vbulletin);
		if (!$verify->verify_token($vbulletin->GPC['humanverify']))
		{
			$errors[] = fetch_error($verify->fetch_error());
		}
	}

	($hook = vBulletinHook::fetch_hook('blog_dosendtofriend_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['username'] != '')
	{
		if ($userinfo = $db->query_first_slave("
			SELECT user.*, userfield.*
			FROM " . TABLE_PREFIX . "user AS user," . TABLE_PREFIX . "userfield AS userfield
			WHERE username='" . $db->escape_string(htmlspecialchars_uni($vbulletin->GPC['username'])) . "'
				AND user.userid = userfield.userid"
		))
		{
			$errors[] = fetch_error('usernametaken', $vbulletin->GPC['username'], $vbulletin->session->vars['sessionurl']);
		}
		else
		{
			$postusername = htmlspecialchars_uni($vbulletin->GPC['username']);
		}
	}
	else
	{
		$postusername = $vbulletin->userinfo['username'];
	}

	if (empty($errors))
	{
		eval(fetch_email_phrases('sendtofriend'));

		vbmail($vbulletin->GPC['sendtoemail'], $vbulletin->GPC['emailsubject'], $message);

		($hook = vBulletinHook::fetch_hook('blog_dosendtofriend_complete')) ? eval($hook) : false;

		$sendtoname = htmlspecialchars_uni($sendtoname);

		$vbulletin->url = fetch_seo_url('entry', $bloginfo);
		print_standard_redirect(array('redirect_blog_sentemail',$sendtoname));
	}
	else
	{
		$_REQUEST['do'] = 'sendtofriend';

		$errormessages = '';
		if (!empty($errors))
		{
			$show['errors'] = true;
			$templater = vB_Template::create('newpost_errormessage');
			$templater->register('errors', $errors);
			$errormessages .= $templater->render();
		}
	}
}

// ############################### start send to friend ###############################
if ($_REQUEST['do'] == 'sendtofriend')
{
	($hook = vBulletinHook::fetch_hook('blog_sendtofriend_start')) ? eval($hook) : false;

	$bloginfo['title'] = fetch_word_wrapped_string($bloginfo['title'], $vbulletin->options['blog_wordwrap']);

	if ($show['errors'])
	{
		$stf = array(
			'name'    => htmlspecialchars_uni($vbulletin->GPC['sendtoname']),
			'email'   => htmlspecialchars_uni($vbulletin->GPC['sendtoemail']),
			'title'   => htmlspecialchars_uni($vbulletin->GPC['emailsubject']),
			'message' => htmlspecialchars_uni($vbulletin->GPC['emailmessage']),
		);
	}
	else
	{
		$stf = array(
			'name'    => '',
			'email'   => '',
			'title'   => $bloginfo['title'],
			'message' => construct_phrase($vbphrase['blog_thought_might_be_interested'],
				fetch_seo_url('entry|nosession|bburl', $bloginfo, array('referrerid' => $vbulletin->userinfo['userid'])),
				$vbulletin->userinfo['username']),
		);
	}

	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	// image verification
	$human_verify = '';
	if (fetch_require_hvcheck('contactus'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verification =& vB_HumanVerify::fetch_library($vbulletin);
		$human_verify = $verification->output_token();
	}

	$sidebar =& build_user_sidebar($bloginfo);
	$navbits[fetch_seo_url('blog', $bloginfo, null, 'userid', 'blog_title')] = $bloginfo['blog_title'];
	$navbits[fetch_seo_url('entry', $bloginfo)] = $bloginfo['title'];

	$navbits[] = $vbphrase['email_to_friend'];

	$bloginfo['title_trimmed'] = fetch_trimmed_title($bloginfo['title']);

	($hook = vBulletinHook::fetch_hook('blog_sendtofriend_complete')) ? eval($hook) : false;

	$url =& $vbulletin->url;

	$templater = vB_Template::create('blog_send_to_friend');
		$templater->register('bloginfo', $bloginfo);
		$templater->register('errormessages', $errormessages);
		$templater->register('human_verify', $human_verify);
		$templater->register('imagereg', $imagereg);
		$templater->register('stf', $stf);
		$templater->register('url', $url);
		$templater->register('usernamecode', $usernamecode);
	$content = $templater->render();
}

// #######################################################################
if ($_POST['do'] == 'rate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'vote'       => TYPE_UINT,
		'ajax'       => TYPE_BOOL,
		'blogid'     => TYPE_UINT,
	));

	$bloginfo = fetch_bloginfo($vbulletin->GPC['blogid']);

	track_blog_visit($bloginfo['userid']);

	if ($vbulletin->GPC['vote'] < 1 OR $vbulletin->GPC['vote'] > 5)
	{
		standard_error(fetch_error('invalidvote'));
	}

	if ($bloginfo['state'] !== 'visible')
	{
		print_no_permission();
	}

	$rated = intval(fetch_bbarray_cookie('blog_rate', $bloginfo['blogid']));

	($hook = vBulletinHook::fetch_hook('blog_rate_start')) ? eval($hook) : false;

	$update = false;
	if ($vbulletin->userinfo['userid'])
	{
		if ($rating = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "blog_rate
			WHERE userid = " . $vbulletin->userinfo['userid'] . "
				AND blogid = $bloginfo[blogid]
		"))
		{
			if ($vbulletin->options['votechange'])
			{
				if ($vbulletin->GPC['vote'] != $rating['vote'])
				{
					$blograte =& datamanager_init('Blog_Rate', $vbulletin, ERRTYPE_STANDARD);
					$blograte->set_info('blog', $bloginfo);
					$blograte->set_existing($rating);
					$blograte->set('vote', $vbulletin->GPC['vote']);

					($hook = vBulletinHook::fetch_hook('blog_rate_update')) ? eval($hook) : false;

					$blograte->save();
				}
				$update = true;
				if (!$vbulletin->GPC['ajax'])
				{
					$vbulletin->url = fetch_seo_url('entry', $bloginfo);
					print_standard_redirect('redirect_blog_rate_add');
				}
			}
			else if (!$vbulletin->GPC['ajax'])
			{
				standard_error(fetch_error('blog_rate_voted'));
			}
		}
		else
		{
			$blograte =& datamanager_init('Blog_Rate', $vbulletin, ERRTYPE_STANDARD);
			$blograte->set_info('blog', $bloginfo);
			$blograte->set('blogid', $bloginfo['blogid']);
			$blograte->set('userid', $vbulletin->userinfo['userid']);
			$blograte->set('vote', $vbulletin->GPC['vote']);

			($hook = vBulletinHook::fetch_hook('blog_rate_add')) ? eval($hook) : false;

			$blograte->save();
			$update = true;

			if (!$vbulletin->GPC['ajax'])
			{
				$vbulletin->url = fetch_seo_url('entry', $bloginfo);
				print_standard_redirect('redirect_blog_rate_add');
			}
		}
	}
	else
	{
		// Check for cookie on user's computer for this blogid
		if ($rated AND !$vbulletin->options['votechange'])
		{
			if (!$vbulletin->GPC['ajax'])
			{
				standard_error(fetch_error('blog_rate_voted'));
			}
		}
		else
		{
			// Check for entry in Database for this Ip Addr/blogid
			if ($rating = $db->query_first("
				SELECT *
				FROM " . TABLE_PREFIX . "blog_rate
				WHERE ipaddress = '" . $db->escape_string(IPADDRESS) . "'
					AND blogid = $bloginfo[blogid]
			"))
			{
				if ($vbulletin->options['votechange'])
				{
					if ($vbulletin->GPC['vote'] != $rating['vote'])
					{
						$blograte =& datamanager_init('Blog_Rate', $vbulletin, ERRTYPE_STANDARD);
						$blograte->set_info('blog', $bloginfo);
						$blograte->set_existing($rating);
						$blograte->set('vote', $vbulletin->GPC['vote']);

						($hook = vBulletinHook::fetch_hook('blog_rate_update')) ? eval($hook) : false;

						$blograte->save();
					}
					$update = true;

					if (!$vbulletin->GPC['ajax'])
					{
						$vbulletin->url = fetch_seo_url('entry', $bloginfo);
						print_standard_redirect('redirect_blog_rate_add');
					}
				}
				else if (!$vbulletin->GPC['ajax'])
				{
					set_bbarray_cookie('blog_rate', $rating['blogid'], $rating['vote'], 1);
					standard_error(fetch_error('blog_rate_voted'));
				}
			}
			else
			{
				$blograte =& datamanager_init('Blog_Rate', $vbulletin, ERRTYPE_STANDARD);
				$blograte->set_info('blog', $bloginfo);
				$blograte->set('blogid', $bloginfo['blogid']);
				$blograte->set('userid', 0);
				$blograte->set('vote', $vbulletin->GPC['vote']);
				$blograte->set('ipaddress', IPADDRESS);

				($hook = vBulletinHook::fetch_hook('blog_rate_add')) ? eval($hook) : false;

				$blograte->save();
				$update = true;

				if (!$vbulletin->GPC['ajax'])
				{
					$vbulletin->url = fetch_seo_url('entry', $bloginfo);
					print_standard_redirect('redirect_blog_rate_add');
				}
			}
		}
	}

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('threadrating');
	if ($update)
	{
		$blog = $db->query_first_slave("
			SELECT ratingtotal, ratingnum
			FROM " . TABLE_PREFIX . "blog
			WHERE blogid = $bloginfo[blogid]
		");

		if ($blog['ratingnum'] > 0 AND $blog['ratingnum'] >= $vbulletin->options['vbblog_ratingpost'])
		{	// Show Voteavg
			$blog['ratingavg'] = vb_number_format($blog['ratingtotal'] / $blog['ratingnum'], 2);
			$blog['rating'] = intval(round($blog['ratingtotal'] / $blog['ratingnum']));
			$xml->add_tag('voteavg', "<img class=\"inlineimg\" src=\"" . vB_Template_Runtime::fetchStyleVar('imgdir_rating') . "/rating-15_$blog[rating].png\" alt=\"" . construct_phrase($vbphrase['rating_x_votes_y_average'], $blog['ratingnum'], $blog['ratingavg']) . "\" border=\"0\" />");
		}
		else
		{
			$xml->add_tag('voteavg', '');
		}

		if (!function_exists('fetch_phrase'))
		{
			require_once(DIR . '/includes/functions_misc.php');
		}
		$xml->add_tag('message', fetch_phrase('redirect_blog_rate_add', 'frontredirect', 'redirect_'));
	}
	else	// Already voted error...
	{
		if (!empty($rating['blogid']))
		{
			set_bbarray_cookie('blog_rate', $rating['blogid'], $rating['vote'], 1);
		}
		$xml->add_tag('error', fetch_error('blog_rate_voted'));
	}
	$xml->close_group();
	$xml->print_xml();
}

// ############################### start random blog ###############################
if ($_REQUEST['do'] == 'random')
{
	$sql = array(
		"state = 'visible'",
		"dateline <= " . TIMENOW,
		"blog.pending = 0",
	);

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		$sql[] = "blog.userid <> " . $vbulletin->userinfo['userid'];
	}
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		$sql[] = "blog.userid = " . $vbulletin->userinfo['userid'];
	}

	if ($coventry = fetch_coventry('string') AND !can_moderate_blog())
	{
		$sql[] = "blog.userid NOT IN ($coventry)";
	}

	$sql1join = array();
	if (!can_moderate_blog())
	{
		$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog.userid)";

		if ($vbulletin->userinfo['userid'])
		{
			$userlist_sql = array();
			$userlist_sql[] = "blog.userid = " . $vbulletin->userinfo['userid'];
			$userlist_sql[] = "(options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND ignored.relationid IS NOT NULL)";
			$userlist_sql[] = "(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)";
			$userlist_sql[] = "(options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR buddy.relationid IS NULL) AND (options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL))";
			$sql[] = "(" . implode(" OR ", $userlist_sql) . ")";

			$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = blog.userid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')";
			$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = blog.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')";

			$wheresql[] = "
				(blog.userid = " . $vbulletin->userinfo['userid'] . "
					OR
				~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . "
					OR
				(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL))";
		}
		else
		{
			$sql[] = "options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			$sql[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
		}
	}

	($hook = vBulletinHook::fetch_hook('blog_random_query')) ? eval($hook) : false;

	$blog = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "blog AS blog
		" . (!empty($sql1join) ? implode("\r\n", $sql1join) : "") . "
		WHERE " . implode("\r\nAND ", $sql) . "
		ORDER BY RAND() LIMIT 1
	");

	if ($blog)
	{
		exec_header_redirect(fetch_seo_url('entry|js', $blog));
	}
	else
	{
		standard_error(fetch_error('blog_no_blogs'));
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'viewip')
{
	if (!$vbulletin->options['logip'] OR (!can_moderate_blog('canviewips') AND $vbulletin->options['logip'] != 2))
	{
		print_no_permission();
	}

	if ($blogtextid)
	{
		$blogtextinfo = fetch_blog_textinfo($vbulletin->GPC['blogtextid']);
		if ($blogtextinfo === false)
		{
			standard_error(fetch_error('invalidid', $vbphrase['comment'], $vbulletin->options['contactuslink']));
		}
		$ipaddress = ($blogtextinfo['ipaddress'] ? htmlspecialchars_uni(long2ip($blogtextinfo['ipaddress'])) : '');
	}
	else
	{
		$bloginfo = verify_blog($blogid);
		$ipaddress = ($bloginfo['blogipaddress'] ? htmlspecialchars_uni(long2ip($bloginfo['blogipaddress'])) : '');
	}

	$hostname = htmlspecialchars_uni(gethostbyaddr($ipaddress));

	($hook = vBulletinHook::fetch_hook('blog_viewip_complete')) ? eval($hook) : false;

	standard_error(fetch_error('thread_displayip', $ipaddress, $hostname, '', 0));
}

// #######################################################################
if ($_REQUEST['do'] == 'markread')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'   => TYPE_UINT,
		'readhash' => TYPE_STR
	));

	// verify the userid exists, don't want useless entries in our table.
	if (!($userinfo = fetch_userinfo($vbulletin->GPC['userid'])))
	{
		standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink']));
	}

	if (!VB_API AND $vbulletin->userinfo['userid'] != 0 AND !verify_security_token($vbulletin->GPC['readhash'], $vbulletin->userinfo['securitytoken_raw']))
	{
		standard_error(fetch_error('blog_markread_error',
			fetch_seo_url('blog', $userinfo, array('do' => 'markread', 'readhash' => $vbulletin->userinfo['logouthash'])) , $userinfo['username']));
	}

	mark_user_blog_read($userinfo['userid'], $vbulletin->userinfo['userid'], TIMENOW);

	require_once(DIR . '/includes/functions_login.php');
	$vbulletin->url = fetch_replaced_session_url($vbulletin->url);
	if (strpos($vbulletin->url, 'do=markread') !== false)
	{
		$vbulletin->url = fetch_seo_url('blog', $userinfo, null, 'userid', 'blog_title');
	}
	print_standard_redirect('blog_markread', true, true);
}

// ############################################################################
// ###############################   GROUP MEMBERS   ##########################
// ############################################################################

if ($_REQUEST['do'] == 'members')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'     => TYPE_UINT,
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
	));

	$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true);
	cache_permissions($userinfo, false);

	if (
		($vbulletin->userinfo['userid'] != $userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
			OR
		($vbulletin->userinfo['userid'] == $userinfo['userid'] AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
			OR
		(!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canhavegroupblog']))
			OR
		(!$userinfo['memberids'])
	)
	{
		print_no_permission();
	}

	require_once(DIR . '/includes/functions_user.php');

	do
	{

		$perpage = (($vbulletin->GPC['perpage'] > 30 OR !$vbulletin->GPC['perpage']) ? 20 : $vbulletin->GPC['perpage']);

		if (!$vbulletin->GPC['pagenumber'])
		{
			$vbulletin->GPC['pagenumber'] = 1;
		}
		$start = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;

		$members = $db->query_read_slave("
			SELECT
				SQL_CALC_FOUND_ROWS
				gm.userid, user.*
				" . ($vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width_thumb AS avwidth_thumb, customavatar.height_thumb AS avheight_thumb, customavatar.width as avwidth, customavatar.height as avheight, customavatar.filedata_thumb' : '') . "
			FROM " . TABLE_PREFIX . "blog_groupmembership AS gm
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = gm.userid)
				" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid) " : '') . "
			WHERE
				gm.bloguserid = " . $vbulletin->GPC['userid'] . "
					AND
				gm.state = 'active'
			ORDER BY user.username
			LIMIT $start, $perpage
		");
		$membercount = $db->found_rows();

		if ($start > $membercount)
		{
			$vbulletin->GPC['pagenumber'] = ceil($membercount / $perpage);
		}
	}
	while($start >= $membercount AND $membercount);

	$pagenav = construct_page_nav(
		$vbulletin->GPC['pagenumber'],
		$perpage,
		$membercount,
		'',
		'',
		'',
		'blog',
		$userinfo,
		array('do' => 'members', 'pp' => $perpage)
	);

	while ($member = $db->fetch_array($members))
	{
		fetch_avatar_from_userinfo($member, true);
		$templater = vB_Template::create('blog_grouplist_userbit');
			$templater->register('member', $member);
		$memberlist .= $templater->render();
		if ($vbulletin->userinfo['userid'] == $member['userid'])
		{
			$show['removeself'] = true;
		}
	}

	$show['avatars'] = true;

	$sidebar =& build_user_sidebar($userinfo);
	$navbits[fetch_seo_url('blog', $userinfo, null, 'userid', 'blog_title')] = $userinfo['blog_title'];
	$navbits[''] = $vbphrase['blog_membership'];

	$templater = vB_Template::create('blog_grouplist');
		$templater->register('membercount', $membercount);
		$templater->register('memberlist', $memberlist);
		$templater->register('pagenav', $pagenav);
		$templater->register('userinfo', $userinfo);
	$content = $templater->render();
	$headinclude .= vB_Template::create('blog_cp_css')->render();
}

// ############################### toggle user css ###############################

//this action does not appear to be called anywhere within the 4.0.x code.
if ($_REQUEST['do'] == 'switchcss')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'hash'     => TYPE_STR,
		'userid'   => TYPE_UINT
	));

	if (!verify_security_token($vbulletin->GPC['hash'], $vbulletin->userinfo['securitytoken_raw']))
	{
		print_no_permission();
	}

	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($vbulletin->userinfo);
	$userdata->set('showblogcss', !$vbulletin->userinfo['showblogcss']);
	$userdata->save();

	if ($vbulletin->GPC['userid'] AND $vbulletin->url == fetch_seo_url('forumhome|nosession', array()))
	{
		$vbulletin->url = fetch_seo_url('blog', array('userid' => $vbulletin->GPC['userid']));
	}
	print_standard_redirect('redirect_usercss_toggled');
}

// ############################### custom page ###############################
if ($_REQUEST['do'] == 'custompage')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'cp' => TYPE_UINT,
	));

	require_once(DIR . '/includes/blog_functions_usercp.php');
	$blockinfo = verify_blog_customblock($vbulletin->GPC['cp'], 'page');
	if (
		(
			$blockinfo['type'] == 'block'
				AND
			!$blockinfo['userinfo']['permissions']['vbblog_customblocks']
		)
			OR
		(
			$blockinfo['type'] == 'page'
				AND
			!$blockinfo['userinfo']['permissions']['vbblog_custompages']
		)
	)
	{
		if (!can_moderate_blog('caneditcustomblocks'))
		{
			print_no_permission();
		}
		$show['reportlink'] = false;
	}
	else
	{
		$show['reportlink'] = true;
	}

	track_blog_visit($blockinfo['userinfo']['userid']);

	$show['reportlink'] = (
		$show['reportlink']
			AND
		$vbulletin->userinfo['userid']
			AND
		(
			$vbulletin->options['rpforumid']
				OR
			(
				$vbulletin->options['enableemail']
					AND
				$vbulletin->options['rpemail']
			)
		)
	);
	$show['edit'] = (can_moderate_blog('caneditcustomblocks') OR $vbulletin->userinfo['userid'] == $blockinfo['userid']);

	// Parse Content here
	require_once(DIR . '/includes/class_bbcode_blog.php');
	$bbcode = new vB_BbCodeParser_Blog($vbulletin, fetch_tag_list());
	$bbcode->set_parse_userinfo($blockinfo['userinfo'], $blockinfo['userinfo']['permissions']);
	$blockinfo['page'] = $bbcode->parse($blockinfo['pagetext'], 'blog_user', $blockinfo['allowsmilie'] ? 1 : 0);

	$blogheader = parse_blog_description($blockinfo['userinfo'], $blockinfo);
	$sidebar =& build_user_sidebar($blockinfo['userinfo']);
	$navbits[] = $blockinfo['title'];

	$templater = vB_Template::create('blog_custompage');
		$templater->register('blogheader', $blogheader);
		$templater->register('blockinfo', $blockinfo);
	$content = $templater->render();
}

// build navbar
if (empty($navbits))
{
	$navbits = array();
}

$navbits = array_merge(array(fetch_seo_url('bloghome', array()) => $vbphrase['blogs']), $navbits);
$navbits = construct_navbits($navbits);
$navbar = render_navbar_template($navbits);

($hook = vBulletinHook::fetch_hook('blog_complete')) ? eval($hook) : false;

if (!empty($content))
{
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
}


/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 63620 $
|| ####################################################################
\*======================================================================*/
?>
