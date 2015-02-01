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
define('THIS_SCRIPT', 'blog_subscription');
define('CSRF_PROTECTION', true);
define('VBBLOG_PERMS', true);
define('VBBLOG_STYLE', true);
define('VBBLOG_SCRIPT', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'user',
	'vbblogglobal',
	'postbit',
);

// get special data templates from the datastore
$specialtemplates = array(
	'noavatarperms',
	'blogcategorycache',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'BLOG',
	'blog_css',
	'blog_usercss',
	'blog_sidebar_category_link',
	'blog_sidebar_comment_link',
	'blog_sidebar_custompage_link',
	'blog_sidebar_entry_link',
	'blog_sidebar_user',
	'blog_sidebar_calendar',
	'blog_sidebar_calendar_day',
	'blog_sidebar_user_block_archive',
	'blog_sidebar_user_block_category',
	'blog_sidebar_user_block_comments',
	'blog_sidebar_user_block_entries',
	'blog_sidebar_user_block_search',
	'blog_sidebar_user_block_tagcloud',
	'blog_sidebar_user_block_visitors',
	'blog_sidebar_user_block_custom',
	'ad_blogsidebar_start',
	'ad_blogsidebar_middle',
	'ad_blogsidebar_end',
	'blog_tag_cloud_link',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'userlist' => array(
		'forumdisplay_sortarrow',
		'blog_blog_row',
		'blog_cp_manage_subscriptions'
	),
	'entrylist' => array(
		'forumdisplay_sortarrow',
		'blog_cp_manage_subscriptions_entry',
		'blog_cp_manage_subscriptions'
	),
	'subscribe' => array(
		'blog_subscribe_to_item'
	),
);

$actiontemplates['none'] =& $actiontemplates['subscription'];

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/blog_functions_post.php');

verify_blog_url();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$show['pingback'] = ($vbulletin->options['vbblog_pingback'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canreceivepingback'] ? true : false);
$show['trackback'] = ($vbulletin->options['vbblog_trackback'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canreceivepingback'] ? true : false);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'userlist';
}

if ((!$vbulletin->userinfo['userid'] AND $_REQUEST['do'] != 'unsubscribe') OR $userinfo['usergroupid'] == 3 OR $vbulletin->userinfo['usergroupid'] == 4 OR !($permissions['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
{
	print_no_permission();
}

($hook = vBulletinHook::fetch_hook('blog_usersub_start')) ? eval($hook) : false;

// ########################## Start Move / Delete / Update Email ##############################
if ($_POST['do'] == 'dostuff')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deletebox' => TYPE_ARRAY_KEYS_INT,
		'what'      => TYPE_STR,
		'type'      => TYPE_STR,
	));

	if (empty($vbulletin->GPC['deletebox']))
	{
		standard_error(fetch_error('blog_subsnoselected'));
	}

	($hook = vBulletinHook::fetch_hook('blog_usersub_manage_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['type'] == 'user')
	{
		$id = 'bloguserid';
		$table = 'blog_subscribeuser';
		$list = 'userlist';
	}
	else
	{
		$id = 'blogid';
		$table = 'blog_subscribeentry';
		$list = 'entrylist';
	}

	switch($vbulletin->GPC['what'])
	{
		// *************************
		// Delete Subscribed Threads
		case 'delete':
			$sql = '';

			($hook = vBulletinHook::fetch_hook('blog_usersub_manage_delete')) ? eval($hook) : false;

			if (!empty($vbulletin->GPC['deletebox']))
			{
				$db->query_write("
					DELETE FROM " . TABLE_PREFIX . "$table
					WHERE $id IN (" . implode(',', $vbulletin->GPC['deletebox']) . ") AND
					userid = " . $vbulletin->userinfo['userid']
				);
			}
			$vbulletin->url = fetch_seo_url('blogsub', array(), array('do' => $list));
			print_standard_redirect('redirect_subupdate');  
			break;

		// *************************
		// Change Notification Type
		case 'email':
		case 'usercp':

			($hook = vBulletinHook::fetch_hook('blog_usersub_manage_update')) ? eval($hook) : false;

			if (!empty($vbulletin->GPC['deletebox']))
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "$table
					SET type = '" . $vbulletin->GPC['what'] . "'
					WHERE $id IN (" . implode(',', $vbulletin->GPC['deletebox']) . ") AND
						userid = " . $vbulletin->userinfo['userid']
				);
			}

			$vbulletin->url = fetch_seo_url('blogsub', array(), array('do' => $list));
			print_standard_redirect('redirect_subupdate');  
			break;

		// *****************************
		// unknown action specified
		default:
			standard_error(fetch_error('invalidid', $vbphrase['action'], $vbulletin->options['contactuslink']));
	}
}

// ############################### start add subscription ###############################
if ($_POST['do'] == 'dosubscribe')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'emailupdate' => TYPE_STR,
		'userid'      => TYPE_UINT,
	));

	if ($bloginfo['blogid'])
	{
		verify_blog($bloginfo['blogid']);
	}
	else if ($vbulletin->GPC['userid'])
	{
		if (!($userinfo = fetch_userinfo($vbulletin->GPC['userid'], 1)))
		{
			standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink']));
		}
		if ((!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND $userinfo['userid'] == $vbulletin->userinfo['userid']) OR (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']) AND $userinfo['userid'] != $vbulletin->userinfo['userid']))
		{
			print_no_permission();
		}
		if (!$userinfo['canviewmyblog'])	// Check Socnet permissions
		{
			print_no_permission();
		}
	}
	else
	{
		standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
	}

	if ($vbulletin->GPC['emailupdate'] != 'usercp' AND $vbulletin->GPC['emailupdate'] != 'email')
	{
		$vbulletin->GPC['emailupdate'] = 'usercp';
	}

	($hook = vBulletinHook::fetch_hook('blog_postsub_doadd')) ? eval($hook) : false;

	/*insert query*/
	if ($bloginfo)
	{
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "blog_subscribeentry (userid, blogid, dateline, type)
			VALUES (" . $vbulletin->userinfo['userid'] . ", $bloginfo[blogid], " . TIMENOW . ", '" . $vbulletin->GPC['emailupdate'] . "')
		");
		if ($vbulletin->url == fetch_seo_url('forumhome|nosession', array()))
		{
			$vbulletin->url = fetch_seo_url('entry', $bloginfo);
		}
		print_standard_redirect('redirect_subsadd_entry', true, true);  
	}
	else
	{
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "blog_subscribeuser (userid, bloguserid, dateline, type)
			VALUES (" . $vbulletin->userinfo['userid'] . ", $userinfo[userid], " . TIMENOW . ", '" . $vbulletin->GPC['emailupdate'] . "')
		");

		if ($vbulletin->url == fetch_seo_url('forumhome|nosession', array()))
		{
			$vbulletin->url = fetch_seo_url('blog', $userinfo);
		}
		print_standard_redirect('redirect_subsadd_blog', true, true);  
	}
}

// ############################### start add subscription ###############################
if ($_REQUEST['do'] == 'subscribe')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT,
	));

	if ($bloginfo['blogid'])
	{
		verify_blog($bloginfo['blogid']);

		$bloginfo['title_trimmed'] = fetch_trimmed_title($bloginfo['title']);

		// Sidebar
		$sidebar =& build_user_sidebar($bloginfo);
	}
	else if ($vbulletin->GPC['userid'])
	{
		if (!($userinfo = fetch_userinfo($vbulletin->GPC['userid'], 1)))
		{
			standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink']));
		}
		if ((!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND $userinfo['userid'] == $vbulletin->userinfo['userid']) OR (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']) AND $userinfo['userid'] != $vbulletin->userinfo['userid']))
		{
			print_no_permission();
		}
		if (!$userinfo['canviewmyblog'])	// Check Socnet permissions
		{
			print_no_permission();
		}

		// Sidebar
		$sidebar =& build_user_sidebar($userinfo);
	}
	else
	{
		standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
	}

	($hook = vBulletinHook::fetch_hook('blog_postsub_add_start')) ? eval($hook) : false;

	$navbits = array(
		'' => $vbphrase['subscribe_to_blog_entry'],
	);

	($hook = vBulletinHook::fetch_hook('blog_postsub_add_complete')) ? eval($hook) : false;

	$url =& $vbulletin->url;
	$templater = vB_Template::create('blog_subscribe_to_item');
		$templater->register('bloginfo', $bloginfo);
		$templater->register('url', $url);
		$templater->register('userinfo', $userinfo);
	$content = $templater->render();
}

// ############################### start remove subscription ###############################
if ($_REQUEST['do'] == 'unsubscribe')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'return' 	             => TYPE_STR,
		'auth'                 => TYPE_STR,
		'blogsubscribeentryid' => TYPE_UINT,
		'blogsubscribeuserid'  => TYPE_UINT,
		'userid'               => TYPE_UINT,
	));

	($hook = vBulletinHook::fetch_hook('blog_postsub_remove')) ? eval($hook) : false;

	if ($bloginfo)
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_subscribeentry
			WHERE userid = " . $vbulletin->userinfo['userid'] . " AND
				blogid = $bloginfo[blogid]
		");
		if ($db->affected_rows())
		{
			print_standard_redirect('redirect_blogsubremove_blogsubscribeentryid');  
		}
		else
		{
			standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
		}
	}
	else if ($vbulletin->GPC['userid'])
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_subscribeuser
			WHERE userid = " . $vbulletin->userinfo['userid'] . " AND
				bloguserid = " . $vbulletin->GPC['userid'] . "
		");
		#if ($db->affected_rows())
		#{
			print_standard_redirect('redirect_blogsubremove_blogsubscribeuserid');  
		#}
		#else
		#{
		#	standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink']));
		#}
	}
	else if ($vbulletin->GPC['blogsubscribeentryid'] AND $vbulletin->GPC['auth'])
	{
		$idfield = 'blogsubscribeentryid';
		$table = 'blog_subscribeentry';
	}
	else if ($vbulletin->GPC['blogsubscribeuserid'] AND $vbulletin->GPC['auth'])
	{
		$idfield = 'blogsubscribeuserid';
		$table = 'blog_subscribeuser';
	}
	else
	{
		standard_error(fetch_error('nosubtype')); // this says thread or forum but shouldn't happen. Not going to phrase just now.
	}

	$db->query_write("
		DELETE $table
		FROM " . TABLE_PREFIX . "$table AS $table, " . TABLE_PREFIX . "user AS user
		WHERE $idfield = " . $vbulletin->GPC["$idfield"] . " AND
		MD5(CONCAT(user.userid, $idfield, user.salt, '" . COOKIE_SALT . "')) = '" . $db->escape_string($vbulletin->GPC['auth']) . "'
	");
	if ($db->affected_rows())
	{
		print_standard_redirect('redirect_blogsubremove_' . $idfield);  
	}
	else
	{
		standard_error(fetch_error('invalidid', $idfield, $vbulletin->options['contactuslink']));
	}
}

// ############################### start view subscriptions ###############################
if ($_REQUEST['do'] == 'userlist')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'sortfield'  => TYPE_NOHTML,
		'sortorder'  => TYPE_NOHTML,
	));

	($hook = vBulletinHook::fetch_hook('blog_usersub_view_start')) ? eval($hook) : false;

	// Values that are reused in templates
	$sortfield  =& $vbulletin->GPC['sortfield'];
	$perpage    =& $vbulletin->GPC['perpage'];
	$pagenumber =& $vbulletin->GPC['pagenumber'];
	if ($perpage > $vbulletin->options['maxthreads'] OR $perpage == 0)
	{
		$perpage = $vbulletin->options['maxthreads'];
	}

	// look at sorting options:
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
		case 'lastblog':
		case 'rating':
			$sqlsortfield = 'blog_user.' . $sortfield;
			break;
		default:
			$handled = false;
			($hook = vBulletinHook::fetch_hook('blog_usersub_view_sort')) ? eval($hook) : false;
			if (!$handled)
			{
				$sqlsortfield = 'blog_user.lastblog';
				$sortfield = 'lastblog';
			}
	}
	$sort = array($sortfield => 'selected="selected"');

	$wheresql = array(
		"blog_subscribeuser.userid = " . $vbulletin->userinfo['userid']
	);

	if (!can_moderate_blog())
	{
		$userlist_sql = array();
		$userlist_sql[] = "blog_subscribeuser.bloguserid IN (" . $vbulletin->userinfo['memberblogids'] . ")";
		$userlist_sql[] = "(options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND ignored.relationid IS NOT NULL)";
		$userlist_sql[] = "(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)";
		$userlist_sql[] = "(options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR buddy.relationid IS NULL) AND (options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL))";
		$wheresql[] = "(" . implode(" OR ", $userlist_sql) . ")";
	}

	do
	{
		if (!$pagenumber)
		{
			$pagenumber = 1;
		}
		$start = ($pagenumber - 1) * $perpage;

		$blogbits = '';
		$getusers = $db->query_read_slave("
			SELECT
			SQL_CALC_FOUND_ROWS blog_user.entries, blog_user.comments, lastblog, lastblogid, lastblogtitle, 
				blog_user.title, blog_subscribeuser.type, blog_user.ratingnum, blog_user.ratingtotal,
				user.username, user.userid, options_ignore, options_buddy, options_member, options_guest, 
				ignored.relationid AS ignoreid, buddy.relationid AS buddyid,
				IF(blog_user.title <> '', blog_user.title, user.username) AS blogtitle
			FROM " . TABLE_PREFIX . "blog_subscribeuser AS blog_subscribeuser
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog_subscribeuser.bloguserid)
			LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog_subscribeuser.bloguserid)
			LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = user.userid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')
			LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = user.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')
			WHERE " . implode(" AND ", $wheresql) . "
			ORDER BY $sqlsortfield $sqlsortorder
			LIMIT $start, $perpage
		");
		list($sub_count) = $db->query_first("SELECT FOUND_ROWS()", DBARRAY_NUM);

		if ($start >= $sub_count)
		{
			$pagenumber = ceil($sub_count / $perpage);
		}
	}
	while ($start >= $sub_count AND $sub_count);

	//sort url depends on old style format, so let's force it.
	$pagevars = array('do' => 'userlist', 'pp' => $perpage);
	require_once(DIR . '/includes/class_friendly_url.php');
	$sorturl = vB_Friendly_Url::fetchLibrary($vbulletin, 'blogsub', array(), $pagevars)->get_url(FRIENDLY_URL_OFF);

	$pagevars['sort'] =  $sortfield;
	if (!empty($vbulletin->GPC['sortorder']))
	{
		$pagevars['order'] =  $vbulletin->GPC['sortorder'];
	}

	$pagenav = construct_page_nav(
		$pagenumber,
		$perpage,
		$sub_count,
		'',
		'',
		'',
		'blogsub',
		array(),
		$pagevars
	);

	if ($db->num_rows($getusers))
	{
		$show['havesubs'] = true;
		$show['notificationtype'] = true;

		while($blog = $db->fetch_array($getusers))
		{
			$blog['entries'] = vb_number_format($blog['entries']);
			$blog['comments'] = vb_number_format($blog['comments']);

			$blog['lastentrydate'] = vbdate($vbulletin->options['dateformat'], $blog['lastblog'], true);
			$blog['lastentrytime'] = vbdate($vbulletin->options['timeformat'], $blog['lastblog']);


			if ($blog['type'] == 'usercp')
			{
					$blog['notification'] = $vbphrase['none'];
			}
			else
			{
				$blog['notification'] = $vbphrase['instant'];
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

			$blog['entrytitle'] = fetch_trimmed_title($blog['lastblogtitle']);

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

			$templater = vB_Template::create('blog_blog_row');
				$templater->register('blog', $blog);
				$templater->register('thread', $thread);
			$blogbits .= $templater->render();
		}

		$db->free_result($getusers);
		$oppositesort = ($vbulletin->GPC['sortorder'] == 'asc' ? 'desc' : 'asc');

		$templater = vB_Template::create('forumdisplay_sortarrow');
			$templater->register('oppositesort', $oppositesort);
		$sortarrow["$sortfield"] = $templater->render();
	}
	else
	{
		$show['havesubs'] = false;
	}

	$type = 'user';
	$colspan = 6;

	($hook = vBulletinHook::fetch_hook('blog_usersub_view_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('blog_cp_manage_subscriptions');
		$templater->register('blogbits', $blogbits);
		$templater->register('colspan', $colspan);
		$templater->register('gobutton', $gobutton);
		$templater->register('pagenav', $pagenav);
		$templater->register('sortarrow', $sortarrow);
		$templater->register('sorturl', $sorturl);
		$templater->register('sub_count', $sub_count);
		$templater->register('type', $type);
	$content = $templater->render();

	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo);
}

// ############################### start view subscriptions ###############################
if ($_REQUEST['do'] == 'entrylist')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'sortfield'  => TYPE_NOHTML,
		'sortorder'  => TYPE_NOHTML,
	));

	($hook = vBulletinHook::fetch_hook('blog_postsub_view_start')) ? eval($hook) : false;

	// Values that are reused in templates
	$sortfield  = $vbulletin->GPC['sortfield'];
	$perpage    = $vbulletin->GPC['perpage'];
	$pagenumber = $vbulletin->GPC['pagenumber'];
	if ($perpage > $vbulletin->options['maxthreads'] OR $perpage == 0)
	{
		$perpage = $vbulletin->options['maxthreads'];
	}

	// look at sorting options:
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
		case 'rating':
		case 'title':
			$sqlsortfield = 'blog.' . $sortfield;
			break;
		case 'blog':
			$sqlsortfield = 'blogtitle';
			break;
		default:
			$handled = false;
			($hook = vBulletinHook::fetch_hook('blog_postsub_view_sort')) ? eval($hook) : false;
			if (!$handled)
			{
				$sqlsortfield = 'blog.lastcomment';
				$sortfield = 'lastblog';
			}
	}
	$sort = array($sortfield => 'selected="selected"');

	$sqljoin = array(
		"INNER JOIN " . TABLE_PREFIX . "blog AS blog ON(blog.blogid = blog_subscribeentry.blogid)",
		"INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog.userid)",
		"LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog.userid)",
		"LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = user.userid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')",
		"LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = user.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')",
	);

	$wheresql = array(
		"blog_subscribeentry.userid = " . $vbulletin->userinfo['userid'],
	);

	if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']))
	{
		$sqljoin[] = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
		$wheresql[] = "cu.blogcategoryid IS NULL";
	}

	if (!can_moderate_blog())
	{
		$userlist_sql = array();
		$userlist_sql[] = "(options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND ignored.relationid IS NOT NULL)";
		$userlist_sql[] = "(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)";
		$userlist_sql[] = "(options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR buddy.relationid IS NULL) AND (options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL))";
		$wheresql[] = "((" . implode(" OR ", $userlist_sql) . ")
			AND blog.state = 'visible'
			AND blog.pending = 0
			AND blog.dateline <= " . TIMENOW . "
			AND (~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . "
				OR
			(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)))
		OR blog.userid IN (" . $vbulletin->userinfo['memberblogids'] . ")";

		if ($coventry = fetch_coventry('string'))
		{
			$wheresql[] = "blog.userid NOT IN ($coventry)";
		}
	}
	else
	{
		$state = array('visible');
		if (can_moderate_blog('canmoderateentries'))
		{
			$state[] = 'moderation';
		}
		if (can_moderate_blog())
		{
			$state[] = 'deleted';
		}

		$wheresql[] = "(blog.state IN ('" . implode("','", $state) . "') OR blog.userid = " . $vbulletin->userinfo['userid'] . ")";
	}

	do
	{
		if (!$pagenumber)
		{
			$pagenumber = 1;
		}
		$start = ($pagenumber - 1) * $perpage;

		$blogbits = '';
		$getposts = $db->query_read_slave("
			SELECT SQL_CALC_FOUND_ROWS blog_user.bloguserid, blog.userid, blog.username, blog.lastcomment, blog.lastcommenter, blog.lastblogtextid, options_ignore, options_buddy, options_member, options_guest,
				IF(blog_user.title <> '', blog_user.title, user.username) AS blogtitle, blog_subscribeentry.type, blog.title, blog.blogid, blog.dateline, blog.ratingtotal, blog.ratingnum, blog.postedby_userid, blog.postedby_username,
				ignored.relationid AS ignoreid, buddy.relationid AS buddyid, blog.options AS blogoptions
			FROM " . TABLE_PREFIX . "blog_subscribeentry AS blog_subscribeentry
			" . (implode("\r\n", $sqljoin)) . "
			WHERE " . implode(" AND ", $wheresql) . "
			ORDER BY $sqlsortfield $sqlsortorder
				LIMIT $start, $perpage
		");
		list($sub_count) = $db->query_first("SELECT FOUND_ROWS()", DBARRAY_NUM);

		if ($start >= $sub_count)
		{
			$pagenumber = ceil($sub_count / $perpage);
		}
	}
	while ($start >= $sub_count AND $sub_count);

	//sort url depends on old style format, so let's force it.
	$pagevars = array('do' => 'entrylist', 'pp' => $perpage);
	require_once(DIR . '/includes/class_friendly_url.php');
	$sorturl = vB_Friendly_Url::fetchLibrary($vbulletin, 'blogsub', array(), $pagevars)->get_url(FRIENDLY_URL_OFF);

	$pagevars['sort'] =  $sortfield;
	if (!empty($vbulletin->GPC['sortorder']))
	{
		$pagevars['order'] =  $vbulletin->GPC['sortorder'];
	}

	$pagenav = construct_page_nav(
		$pagenumber,
		$perpage,
		$sub_count,
		'',
		'',
		'',
		'blogsub',
		array(),
		$pagevars
	);

	if ($db->num_rows($getposts))
	{
		$show['havesubs'] = true;
		$show['notificationtype'] = true;

		while($post = $db->fetch_array($getposts))
		{
			$post = array_merge($post, convert_bits_to_array($post['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
			$post['lastcommenter_encoded'] = urlencode($post['lastcommenter']);
			if ($post['lastcomment'] != $post['dateline'])
			{
				$post['lastpostdate'] = vbdate($vbulletin->options['dateformat'], $post['lastcomment'], true);
				$post['lastposttime'] = vbdate($vbulletin->options['timeformat'], $post['lastcomment'], true);
				$show['datetime'] = true;
			}
			else
			{
				$show['datetime'] = false;
			}

			if ($post['type'] == 'usercp')
			{
					$post['notification'] = $vbphrase['none'];
			}
			else
			{
				$post['notification'] = $vbphrase['instant'];
			}

			if ($post['ratingnum'] > 0 AND $post['ratingnum'] >= $vbulletin->options['vbblog_ratingpost'])
			{
				$post['ratingavg'] = vb_number_format($post['ratingtotal'] / $post['ratingnum'], 2);
				$post['rating'] = intval(round($post['ratingtotal'] / $post['ratingnum']));
				$show['rating'] = true;
			}
			else
			{
				$post['ratingavg'] = 0;
				$post['rating'] = 0;
				$show['rating'] = false;
			}

			$show['private'] = false;
			if ($post['private'])
			{
				$show['private'] = true;
			}
			else if (can_moderate() AND $post['userid'] != $vbulletin->userinfo['userid'])
			{
				$membercanview = $post['options_member'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
				$buddiescanview = $post['options_buddy'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
				if (!$membercanview AND (!$post['buddyid'] OR !$buddiescanview))
				{
					$show['private'] = true;
				}
			}

			$lastcommentmemberlink = $vbulletin->options['vbforum_url'] . 
				($vbulletin->options['vbforum_url'] ? '/' : '') . 'member.php?' . 
				$vbulletin->session->vars['sessionurl'] . 'username=' . $post['lastcommenter_encoded'];

			$templater = vB_Template::create('blog_cp_manage_subscriptions_entry');
				$templater->register('post', $post);
				$templater->register('thread', $thread);
				$templater->register('lastcommentmemberlink', $lastcommentmemberlink);
			$blogbits .= $templater->render();
		}

		$db->free_result($getusers);
		$oppositesort = ($vbulletin->GPC['sortorder'] == 'asc' ? 'desc' : 'asc');

		$templater = vB_Template::create('forumdisplay_sortarrow');
			$templater->register('foruminfo', $foruminfo);
			$templater->register('oppositesort', $oppositesort);
			$templater->register('pageinfo_sort', $pageinfo_sort);
			$templater->register('pagenumber', $pagenumber);
			$templater->register('perpage', $perpage);
			$templater->register('sortfield', $sortfield);
			$templater->register('sorturl', $sorturl);
		$sortarrow["$sortfield"] = $templater->render();
	}
	else
	{
		$show['havesubs'] = false;
	}

	$show['blog'] = true;
	$type = 'post';
	$colspan = 4;

	($hook = vBulletinHook::fetch_hook('blog_postsub_view_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('blog_cp_manage_subscriptions');
		$templater->register('blogbits', $blogbits);
		$templater->register('colspan', $colspan);
		$templater->register('gobutton', $gobutton);
		$templater->register('pagenav', $pagenav);
		$templater->register('sortarrow', $sortarrow);
		$templater->register('sorturl', $sorturl);
		$templater->register('sub_count', $sub_count);
		$templater->register('type', $type);
	$content = $templater->render();

	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo);
}

// build navbar
if (empty($navbits))
{
	$navbits = array(
		fetch_seo_url('bloghome', array()) => $vbphrase['blogs'],
	);
	if ($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
	{
		$navbits[fetch_seo_url('blog', $vbulletin->userinfo)] = $vbulletin->userinfo['blog_title'];
	}

	$navbits[fetch_seo_url('blogusercp', array())] = $vbphrase['blog_control_panel'];

	if ($_REQUEST['do'] == 'userlist')
	{
		$navbits[''] = $vbphrase['blog_subscriptions'];
	}
	else
	{
		$navbits[''] = $vbphrase['blog_entry_subscriptions'];
	}
}
else
{
	$prenavbits = array(
		fetch_seo_url('bloghome', array()) => $vbphrase['blogs'],
	);

	if ($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
	{
		$prenavbits[fetch_seo_url('blog', $vbulletin->userinfo)] = $vbulletin->userinfo['blog_title'];
	}

	$prenavbits[fetch_seo_url('blogusercp', array())] = $vbphrase['blog_control_panel'];

	if (!$bloginfo['blogid'])
	{
		$prenavbits[fetch_seo_url('blogsub', array(), array('do' => 'userlist'))] = $vbphrase['blog_subscriptions'];
	}
	else
	{
		$prenavbits[fetch_seo_url('blogsub', array(), array('do' => 'entrylist'))] = $vbphrase['blog_entry_subscriptions'];
	}

	$navbits = array_merge($prenavbits, $navbits);
}
$navbits = construct_navbits($navbits);

$navbar = render_navbar_template($navbits);
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
|| # Downloaded: 10:01, Mon May 13th 2013
|| # SVN: $Revision: 53471 $
|| ####################################################################
\*======================================================================*/
?>
