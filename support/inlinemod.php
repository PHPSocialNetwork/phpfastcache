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
if ($_REQUEST['do'] == 'mergeposts' OR $_POST['do'] == 'domergeposts')
{
	define('GET_EDIT_TEMPLATES', true);
}
define('THIS_SCRIPT', 'inlinemod');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('banning', 'threadmanage', 'posting', 'inlinemod');

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'THREADADMIN',
	'threadadmin_authenticate'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'mergethread'  => array('threadadmin_mergethreads'),
	'deletethread' => array('threadadmin_deletethreads'),
	'movethread'   => array('threadadmin_movethreads'),
	'moveposts'    => array('threadadmin_moveposts'),
	'copyposts'    => array('threadadmin_copyposts'),
	'mergeposts'   => array('threadadmin_mergeposts'),
	'domergeposts' => array('threadadmin_mergeposts'),
	'deleteposts'  => array('threadadmin_deleteposts'),
	// spam management
	'spampost'     => array('threadadmin_easyspam', 'threadadmin_easyspam_headinclude'),
	'spamthread'   => array('threadadmin_easyspam', 'threadadmin_easyspam_headinclude'),
	'spamconfirm'  => array('threadadmin_easyspam_confirm', 'threadadmin_easyspam_ban', 'threadadmin_easyspam_user_option', 'threadadmin_easyspam_headinclude'),
	'dodeletespam' => array('threadadmin_easyspam_headinclude', 'threadadmin_easyspam_skipped_prune'),
);
$actiontemplates['mergethreadcompat'] =& $actiontemplates['mergethread'];

// ####################### PRE-BACK-END ACTIONS ##########################
require_once('./global.php');
require_once(DIR . '/includes/functions_editor.php');
require_once(DIR . '/includes/functions_threadmanage.php');
require_once(DIR . '/includes/functions_databuild.php');
require_once(DIR . '/includes/functions_log_error.php');
require_once(DIR . '/includes/modfunctions.php');
require_once(DIR . '/vb/search/indexcontroller/queue.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_forum_url();

if (($current_memory_limit = ini_size_to_bytes(@ini_get('memory_limit'))) < 128 * 1024 * 1024 AND $current_memory_limit > 0)
{
	@ini_set('memory_limit', 128 * 1024 * 1024);
}
@set_time_limit(0);

// Wouldn't be fun if someone tried to manipulate every post in the database ;)
// Should be made into options I suppose - too many and you exceed what a cookie can hold anyway
$postlimit = 400;
$threadlimit = 200;

if (!can_moderate())
{
	print_no_permission();
}

// This is a list of ids that were checked on the page we submitted from
$vbulletin->input->clean_array_gpc('p', array(
	'tlist' => TYPE_ARRAY_KEYS_INT,
	'plist' => TYPE_ARRAY_KEYS_INT,
));

// If we have javascript, all ids should be in here
$vbulletin->input->clean_array_gpc('c', array(
	'vbulletin_inlinethread' => TYPE_STR,
	'vbulletin_inlinepost'   => TYPE_STR,
));



$tlist = array();
if (!empty($vbulletin->GPC['vbulletin_inlinethread']))
{
	$tlist = explode('-', $vbulletin->GPC['vbulletin_inlinethread']);
	$tlist = $vbulletin->input->clean($tlist, TYPE_ARRAY_UINT);
}
$tlist = array_unique(array_merge($tlist, $vbulletin->GPC['tlist']));

$plist = array();
if (!empty($vbulletin->GPC['vbulletin_inlinepost']))
{
	$plist = explode('-', $vbulletin->GPC['vbulletin_inlinepost']);
	$plist = $vbulletin->input->clean($plist, TYPE_ARRAY_UINT);
}
$plist = array_unique(array_merge($plist, $vbulletin->GPC['plist']));

switch ($_POST['do'])
{
	case 'dodeletethreads':
	case 'domovethreads':
	case 'domergethreads':
	case 'dodeleteposts':
	case 'domergeposts':
	case 'domoveposts':
	case 'docopyposts':
	case 'spamconfirm':
	case 'dodeletespam':
	{
		$inline_mod_authenticate = true;
		break;
	}
	default:
	{
		$inline_mod_authenticate = false;
		($hook = vBulletinHook::fetch_hook('inlinemod_authenticate_switch')) ? eval($hook) : false;
	}
}

if ($inline_mod_authenticate AND !inlinemod_authenticated() AND !VB_API)
{
	show_inline_mod_login(false, true);
}

switch ($_POST['do'])
{
	case 'mergethreadcompat':
		$vbulletin->input->clean_gpc('p', 'mergethreadurl', TYPE_STR);

		$mergethreadid = extract_threadid_from_url($vbulletin->GPC['mergethreadurl']);
		if (!$mergethreadid)
		{
			// Invalid URL
			eval(standard_error(fetch_error('mergebadurl')));
		}

		$threadids = "$threadid,$mergethreadid";
		break;
	case 'open':
	case 'close':
	case 'stick':
	case 'unstick':
	case 'deletethread':
	case 'undeletethread':
	case 'approvethread':
	case 'unapprovethread':
	case 'movethread':
	case 'mergethread':
	case 'viewthread':
	case 'spamthread':
	{
		if (empty($tlist))
		{
			eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
		}

		if (count($tlist) > $threadlimit)
		{
			eval(standard_error(fetch_error('you_are_limited_to_working_with_x_threads', $threadlimit)));
		}

		$threadids = implode(',', $tlist);

		break;
	}
	case 'dodeletethreads':
	case 'domovethreads':
	case 'domergethreads':
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'threadids' => TYPE_STR,
		));

		$threadids = explode(',', $vbulletin->GPC['threadids']);
		foreach ($threadids AS $index => $threadid)
		{
			if (intval($threadid) == 0)
			{
				unset($threadids["$index"]);
			}
			else
			{
				$threadids["$index"] = intval($threadid);
			}

		}

		if (empty($threadids))
		{
			eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
		}

		if (count($threadids) > $threadlimit)
		{
			eval(standard_error(fetch_error('you_are_limited_to_working_with_x_threads', $threadlimit)));
		}

		break;
	}
	case 'deleteposts':
	case 'undeleteposts':
	case 'approveposts':
	case 'unapproveposts':
	case 'mergeposts':
	case 'moveposts':
	case 'copyposts':
	case 'approveattachments':
	case 'unapproveattachments':
	case 'viewpost':
	case 'spampost':
	{
		if (empty($plist))
		{
			eval(standard_error(fetch_error('no_applicable_posts_selected')));
		}

		if (count($plist) > $postlimit)
		{
			eval(standard_error(fetch_error('you_are_limited_to_working_with_x_posts', $postlimit)));
		}

		$postids = implode(',', $plist);

		break;
	}
	case 'dodeleteposts':
	case 'domergeposts':
	case 'domoveposts':
	case 'docopyposts':
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'postids' => TYPE_STR,
		));

		$postids = explode(',', $vbulletin->GPC['postids']);
		foreach ($postids AS $index => $postid)
		{
			if (intval($postid) == 0)
			{
				unset($postids["$index"]);
			}
			else
			{
				$postids["$index"] = intval($postid);
			}
		}

		if (empty($postids))
		{
			eval(standard_error(fetch_error('no_applicable_posts_selected')));
		}

		if (count($postids) > $postlimit)
		{
			eval(standard_error(fetch_error('you_are_limited_to_working_with_x_posts', $postlimit)));
		}
		break;
	}
	case 'spamconfirm':
	case 'dodeletespam':
	{ // thse can be either posts OR threads
		$vbulletin->input->clean_array_gpc('p', array(
			'type' => TYPE_STR,
		));
		if ($vbulletin->GPC['type'] == 'post')
		{
			$vbulletin->input->clean_array_gpc('p', array(
				'postids' => TYPE_STR,
			));

			$postids = explode(',', $vbulletin->GPC['postids']);
			foreach ($postids AS $index => $postid)
			{
				if (intval($postid) == 0)
				{
					unset($postids["$index"]);
				}
				else
				{
					$postids["$index"] = intval($postid);
				}
			}

			if (empty($postids))
			{
				eval(standard_error(fetch_error('no_applicable_posts_selected')));
			}

			if (count($postids) > $postlimit)
			{
				eval(standard_error(fetch_error('you_are_limited_to_working_with_x_posts', $postlimit)));
			}
		}
		else
		{
			$vbulletin->input->clean_array_gpc('p', array(
				'threadids' => TYPE_STR,
			));

			$threadids = explode(',', $vbulletin->GPC['threadids']);
			foreach ($threadids AS $index => $threadid)
			{
				if (intval($threadid) == 0)
				{
					unset($threadids["$index"]);
				}
				else
				{
					$threadids["$index"] = intval($threadid);
				}

			}

			if (empty($threadids))
			{
				eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
			}

			if (count($threadids) > $threadlimit)
			{
				eval(standard_error(fetch_error('you_are_limited_to_working_with_x_threads', $threadlimit)));
			}
		}
		break;
	}
	case 'clearthread':
	case 'clearpost':
	{
		break;
	}
	default: // throw and error about invalid $_REQUEST['do']
	{
		$handled_do = false;
		($hook = vBulletinHook::fetch_hook('inlinemod_action_switch')) ? eval($hook) : false;
		if (!$handled_do)
		{
			eval(standard_error(fetch_error('invalid_action')));
		}
	}
}

// set forceredirect for IIS
$forceredirect = (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);

$threadarray = array();
$postarray = array();
$postinfos = array();
$forumlist = array();
$threadlist = array();

($hook = vBulletinHook::fetch_hook('inlinemod_start')) ? eval($hook) : false;

################################## Feed selected threads to search.php ###############
if ($_POST['do'] == 'viewthread')
{
	require_once(DIR . '/vb/search/core.php');
	require_once(DIR . '/vb/search/results.php');
	require_once(DIR . '/vb/legacy/currentuser.php');

	$typeid = vB_Search_Core::get_instance()->get_contenttypeid('vBForum', 'Thread');
	$result_array = array();
	foreach (explode(",", $threadids) AS $id)
	{
		$result_array[] = array($typeid, $id);
	}

	$results = vB_Search_Results::create_from_array(new vB_Legacy_CurrentUser(), $result_array);
	$vbulletin->url = 'search.php?' . $vbulletin->session->vars['sessionurl'] .
		"searchid=" . $results->get_searchid();
	print_standard_redirect('search');
}

################################## Feed selected posts to search.php #################
if ($_POST['do'] == 'viewpost')
{
	require_once(DIR . '/vb/search/core.php');
	require_once(DIR . '/vb/search/results.php');
	require_once(DIR . '/vb/legacy/currentuser.php');

	$typeid = vB_Search_Core::get_instance()->get_contenttypeid('vBForum', 'Post');
	$result_array = array();
	foreach (explode(",", $postids) AS $id)
	{
		$result_array[] = array($typeid, $id);
	}

	$results = vB_Search_Results::create_from_array(new vB_Legacy_CurrentUser(), $result_array);
	$vbulletin->url = 'search.php?' . $vbulletin->session->vars['sessionurl'] .
		"searchid=" . $results->get_searchid();
	print_standard_redirect('search');
}

// ############################### Empty Thread Cookie ###############################
if ($_POST['do'] == 'clearthread')
{
	setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_clearthread')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_threadlist_cleared', true, $forceredirect);
}

// ############################### Empty Post Cookie ###############################
if ($_POST['do'] == 'clearpost')
{
	setcookie('vbulletin_inlinepost', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_clearpost')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_postlist_cleared', true, $forceredirect);
}

// ############################### start do open / close thread ###############################
if ($_POST['do'] == 'open' OR $_POST['do'] == 'close')
{

	// Validate threads
	$threads = $db->query_read_slave("
		SELECT threadid, visible, forumid, postuserid, title, prefixid
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid IN ($threadids)
			AND open = " . ($_POST['do'] == 'open' ? 0 : 1) . "
	");
	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

		if (!can_moderate($thread['forumid'], 'canopenclose'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_openclose_threads', $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}
		else if (!$thread['visible'] AND !can_moderate($thread['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($thread['visible'] == 2 AND !can_moderate($thread['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}

		$threadarray["$thread[threadid]"] = $thread;
	}

	if (!empty($threadarray))
	{
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "thread
			SET open = " . ($_POST['do'] == 'open' ? 1 : 0) . "
			WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")

		");

		foreach (array_keys($threadarray) AS $threadid)
		{
			$modlog[] = array(
				'userid'   =>& $vbulletin->userinfo['userid'],
				'forumid'  =>& $threadarray["$threadid"]['forumid'],
				'threadid' => $threadid,
			);
		}

		log_moderator_action($modlog, ($_POST['do'] == 'open') ? 'opened_thread' : 'closed_thread');
	}

	// empty cookie
	setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_closeopen')) ? eval($hook) : false;

	if ($_POST['do'] == 'open')
	{
		print_standard_redirect('redirect_inline_opened', true, $forceredirect);
	}
	else
	{
		print_standard_redirect('redirect_inline_closed', true, $forceredirect);
	}
}

// ############################### start do stick / unstick thread ###############################
if ($_POST['do'] == 'stick' OR $_POST['do'] == 'unstick')
{
	$redirect = array();

	// Validate threads
	$threads = $db->query_read_slave("
		SELECT threadid, open, visible, forumid, postuserid, title, prefixid
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid IN ($threadids)
			AND sticky = " . ($_POST['do'] == 'stick' ? 0 : 1) . "
	");
	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

		if (!can_moderate($thread['forumid'], 'canmanagethreads'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_stickunstick_threads', $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}
		else if (!$thread['visible'] AND !can_moderate($thread['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($thread['visible'] == 2 AND !can_moderate($thread['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}

		$threadarray["$thread[threadid]"] = $thread;
		if ($thread['open'] == 10)
		{
			$redirect[] = $thread['threadid'];
		}
	}

	if (!empty($threadarray))
	{
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "thread
			SET sticky = " . ($_POST['do'] == 'stick' ? 1 : 0) . "
			WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
		");

		foreach (array_keys($threadarray) AS $threadid)
		{
			if (!in_array($threadid, $redirect))
			{	// Don't add log entry for (un)sticking a redirect
				$modlog[] = array(
					'userid'   =>& $vbulletin->userinfo['userid'],
					'forumid'  =>& $threadarray["$threadid"]['forumid'],
					'threadid' => $threadid,
				);
			}
		}

		log_moderator_action($modlog, ($_POST['do'] == 'stick') ? 'stuck_thread' : 'unstuck_thread');
	}

	// empty cookie
	setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_stickunstick')) ? eval($hook) : false;

	if ($_POST['do'] == 'stick')
	{
		print_standard_redirect('redirect_inline_stuck', true, $forceredirect);
	}
	else
	{
		print_standard_redirect('redirect_inline_unstuck', true, $forceredirect);
	}
}

// ############################### start do delete thread ###############################
if ($_POST['do'] == 'deletethread' OR $_POST['do'] == 'spamthread')
{
	// Validate threads
	$threads = $db->query_read_slave("
		SELECT threadid, open, visible, forumid, title, prefixid, postuserid
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid IN ($threadids)
	");

	$show['removethreads'] = true;
	$show['deletethreads'] = true;
	$show['deleteoption'] = true;
	$checked = array('delete' => 'checked="checked"');

	$redirectcount = 0;
	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

		if ($thread['open'] == 10)
		{
			if (!can_moderate($thread['forumid'], 'canmanagethreads'))
			{
				// No permission to remove redirects.
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_thread_redirects', $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
			}
			else
			{
				$redirectcount++;
			}
		}
		else if (!$thread['visible'] AND !can_moderate($thread['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($thread['visible'] == 2)
		{
			if (!can_moderate($thread['forumid'], 'candeleteposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
			}
			else if (!can_moderate($thread['forumid'], 'canremoveposts'))
			{
				continue;
			}
		}
		else if (!can_moderate($thread['forumid'], 'canremoveposts'))
		{
			if (!can_moderate($thread['forumid'], 'candeleteposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
			}
			else if (!$show['deletethreads'])
			{
				eval(standard_error(fetch_error('you_do_not_share_delete_permission')));
			}
			else
			{
				$show['removethreads'] = false;
				$show['deleteoption'] = false;
			}
		}
		else if (!can_moderate($thread['forumid'], 'candeleteposts'))
		{
			if (!can_moderate($thread['forumid'], 'canremoveposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
			}
			else if (!$show['removethreads'])
			{
				eval(standard_error(fetch_error('you_do_not_share_delete_permission')));
			}
			else
			{
				$checked = array('remove' => 'checked="checked"');
				$show['deletethreads'] = false;
				$show['deleteoption'] = false;
			}
		}

		$threadarray["$thread[threadid]"] = $thread;
		$forumlist["$thread[forumid]"] = true;

		if ($node = get_nodeFromThreadid($thread['threadid']))
		{
			// Expire any CMS comments cache entries.
			$expire_cache = array('cms_comments_change');
			$expire_cache[] = 'cms_comments_add_' . $node;
			$expire_cache[] = 'cms_comments_change_' . $thread['threadid'];

			vB_Cache::instance()->eventPurge($expire_cache);
			vB_Cache::instance()->cleanNow();
		}
	}

	if (empty($threadarray))
	{
		eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
	}

	($hook = vBulletinHook::fetch_hook('inlinemod_deletethread')) ? eval($hook) : false;

	$threadcount = count($threadarray);
	$forumcount = count($forumlist);

	switch ($_POST['do'])
	{
		case 'spamthread':
		{
			$users_result = $db->query_read("
				SELECT user.userid, user.username, user.joindate, user.posts, post.ipaddress, post.postid, thread.forumid
				FROM " . TABLE_PREFIX . "thread AS thread
				INNER JOIN " . TABLE_PREFIX . "post AS post ON(post.postid = thread.firstpostid)
				INNER JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = post.userid)
				WHERE thread.threadid IN($threadids)
				ORDER BY user.username
			");
			$user_cache = array();
			$ip_cache = array();
			while ($user = $db->fetch_array($users_result))
			{
				$user_cache["$user[userid]"] = $user;
				if ($vbulletin->options['logip'] == 2 OR ($vbulletin->options['logip'] == 1 AND can_moderate($user['forumid'], 'canviewips')))
				{
					$ip_cache["$user[ipaddress]"] = $user['postid'];
				}
			}
			$db->free_result($users_result);

			$usercount = 0;
			$users = array();
			foreach ($user_cache AS $user)
			{
				$usercount++;
				$user['comma'] = $vbphrase['comma_space'];
				$users[$usercount] = $user;
			}

			// Last element
			if ($usercount)
			{
				$users[$usercount]['comma'] = '';
			}

			$clc = 0;
			$ips = array();
			if ($vbulletin->options['logip'])	// already checked forum permission above
			{
				ksort($ip_cache);
				foreach ($ip_cache AS $ip => $postid)
				{
					if (empty($ip))
					{
						continue;
					}

					$clc++;
					$row['ip'] = $ip;
					$row['ip2long'] = ip2long($ip);
					$row['comma'] = $vbphrase['comma_space'];
					$row['forumurl'] = $vboptions['vbforum_url'] ? $vboptions['vbforum_url'].'/' : '' ;
					$ips[$clc] = $row;
				}

				// Last element
				if ($clc)
				{
					$ips[$clc]['comma'] = '';
				}
			}

			$show['ips'] = ($clc > 0);
			$show['users'] = ($usercount > 0);

			// make a list of usergroups into which to move this user
			$havebanned = false;
			foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
			{
				if (!($usergroup['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
				{
					$havebangroup = true;
					break;
				}
			}

			$show['punitive_action'] = ($havebangroup AND (($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) OR can_moderate(0, 'canbanusers'))) ? true : false;
			$show['akismet_option'] = !empty($vbulletin->options['vb_antispam_key']);
			$show['delete_others_option'] = can_moderate(-1, 'canmassprune');

			$show['deleteitems'] = $show['deletethreads'];
			$show['removeitems'] = $show['removethreads'];

			$headinclude .= vB_Template::create('threadadmin_easyspam_headinclude')->render();

			$navbits[''] = $vbphrase['delete_threads_as_spam'];

			$page_templater = vB_Template::create('threadadmin_easyspam');
			$page_templater->register('checked', $checked);
			$page_templater->register('forumcount', $forumcount);
			$page_templater->register('ips', $ips);
			$page_templater->register('postcount', $postcount);
			$page_templater->register('postids', $postids);
			$page_templater->register('postid_hiddenfields', $postid_hiddenfields);
			$page_templater->register('threadcount', $threadcount);
			$page_templater->register('threadids', $threadids);
			$page_templater->register('threadid_hiddenfields', $threadid_hiddenfields);
			$page_templater->register('threadinfo', $threadinfo);
			$page_templater->register('usercount', $usercount);
			$page_templater->register('users', $users);

			($hook = vBulletinHook::fetch_hook('inlinemod_spamthread')) ? eval($hook) : false;
			break;
		}

		default:
		{
			if ($threadcount == $redirectcount)
			{	// selected all redirects so delet-o-matic them

				$delinfo = array(
					'userid'          => $vbulletin->userinfo['userid'],
					'username'        => $vbulletin->userinfo['username'],
					'reason'          => '',
					'keepattachments' => 0
				);

				foreach ($threadarray AS $threadid => $thread)
				{
					$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
					$threadman->set_existing($thread);
					$threadman->delete(false, true, $delinfo);
					unset($threadman);
				}

				// empty cookie
				setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');

				($hook = vBulletinHook::fetch_hook('inlinemod_dodeletethread')) ? eval($hook) : false;

				print_standard_redirect('redirect_inline_deleted', true, $forceredirect);
			}
			else
			{
				$navbits[''] = $vbphrase['delete_threads'];

				$page_templater = vB_Template::create('threadadmin_deletethreads');
				$page_templater->register('checked', $checked);
				$page_templater->register('forumcount', $forumcount);
				$page_templater->register('threadcount', $threadcount);
				$page_templater->register('threadids', $threadids);
				$page_templater->register('threadinfo', $threadinfo);
			}
		}
	}
}

/* permission checks for the punitive action on spam threads / posts */
if ($_POST['do'] == 'spamconfirm' OR $_POST['do'] == 'dodeletespam')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'useraction' => TYPE_STR,
		'userid' => TYPE_ARRAY_UINT,
	));

	$userids = array();

	if ($vbulletin->GPC['type'] == 'thread')
	{ // threads
		$threadarray = array();
		$threads = $db->query_read_slave("
			SELECT threadid, open, visible, forumid, title, prefixid, postuserid
			FROM " . TABLE_PREFIX . "thread
			WHERE threadid IN (" . implode(',', $threadids) . ")
		");
		while ($thread = $db->fetch_array($threads))
		{
			$forumperms = fetch_permissions($thread['forumid']);
			if 	(
				!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
					OR
				!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
					OR
				(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
				)
			{
				print_no_permission();
			}

			$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

			if ($thread['open'] == 10)
			{
				if (!can_moderate($thread['forumid'], 'canmanagethreads'))
				{ // No permission to remove redirects.
					eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_thread_redirects', $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
				}
			}
			else if (!$thread['visible'] AND !can_moderate($thread['forumid'], 'canmoderateposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
			}
			else if ($thread['visible'] == 2 AND !can_moderate($thread['forumid'], 'candeleteposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
			}
			else if (!can_moderate($thread['forumid'], 'canremoveposts'))
			{
				if (!can_moderate($thread['forumid'], 'candeleteposts'))
				{
					eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
				}
			}
			else if (!can_moderate($thread['forumid'], 'candeleteposts'))
			{
				if (!can_moderate($thread['forumid'], 'canremoveposts'))
				{
					eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
				}
			}
			$threadarray["$thread[threadid]"] = $thread;
			$userids["$thread[postuserid]"] = true;
		}

		if (empty($threadarray))
		{
			eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
		}
	}
	else
	{ // posts
		// Validate posts
		$postarray = array();
		$posts = $db->query_read_slave("
			SELECT post.postid, post.threadid, post.visible, post.title, post.userid,
				thread.forumid, thread.title AS thread_title, thread.postuserid, thread.visible AS thread_visible, thread.firstpostid
			FROM " . TABLE_PREFIX . "post AS post
			LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING (threadid)
			WHERE postid IN (" . implode(',', $postids) . ")
		");
		while ($post = $db->fetch_array($posts))
		{
			$forumperms = fetch_permissions($post['forumid']);
			if 	(
				!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
					OR
				!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
					OR
				(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $post['postuserid'] != $vbulletin->userinfo['userid'])
				)
			{
				print_no_permission();
			}

			if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($post['forumid'], 'canmoderateposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
			}
			else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($post['forumid'], 'candeleteposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
			}
			else if (!can_moderate($post['forumid'], 'canremoveposts'))
			{
				if (!can_moderate($post['forumid'], 'candeleteposts'))
				{
					eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
				}
			}
			else if (!can_moderate($post['forumid'], 'candeleteposts'))
			{
				if (!can_moderate($post['forumid'], 'canremoveposts'))
				{
					eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
				}
			}
			$postarray["$post[postid]"] = $post;
			$userids["$post[userid]"] = true;
		}

		if (empty($postarray))
		{
			eval(standard_error(fetch_error('no_applicable_posts_selected')));
		}
	}

	$user_cache = array();
	foreach ($vbulletin->GPC['userid'] AS $userid)
	{
		// check that userid appears somewhere in either posts / threads, if they don't then you're doing something naughty
		if (!isset($userids["$userid"]))
		{
			print_no_permission();
		}
		$user_cache["$userid"] = fetch_userinfo($userid);
		cache_permissions($user_cache["$userid"]);
		$user_cache["$userid"]['joindate_string'] = vbdate($vbulletin->options['dateformat'], $user_cache["$userid"]['joindate']);
	}

	if ($vbulletin->GPC['useraction'] == 'ban')
	{
		require_once(DIR . '/includes/adminfunctions.php');
		require_once(DIR . '/includes/functions_banning.php');
		if (!($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'] OR can_moderate(0, 'canbanusers')))
		{
			print_no_permission();
		}

		// check that user has permission to ban the person they want to ban
		if (!($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
		{
			foreach ($user_cache AS $userid => $userinfo)
			{
				if (can_moderate(0, '', $userinfo['userid'], $userinfo['usergroupid'] . (trim($userinfo['membergroupids']) ? ",$userinfo[membergroupids]" : ''))
					OR $userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']
					OR $userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['ismoderator']
					OR is_unalterable_user($userinfo['userid']))
				{
					eval(standard_error(fetch_error('no_permission_ban_non_registered_users')));
				}
			}
		}
		else
		{
			foreach ($user_cache AS $userid => $userinfo)
			{
				if ($userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']
					OR is_unalterable_user($userinfo['userid']))
				{
					eval(standard_error(fetch_error('no_permission_ban_non_registered_users')));
				}
			}
		}
	}
	($hook = vBulletinHook::fetch_hook('inlinemod_spam_permission')) ? eval($hook) : false;
}

if ($_POST['do'] == 'spamconfirm')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deleteother'     => TYPE_BOOL,
		'report'          => TYPE_BOOL,
		'useraction'      => TYPE_NOHTML,
		'userid'          => TYPE_ARRAY_UINT,
		'type'            => TYPE_NOHTML,
		'deletetype'      => TYPE_UINT, // 1 = soft, 2 = hard
		'deletereason'    => TYPE_STR,
		'keepattachments' => TYPE_BOOL,
	));

	if (!empty($user_cache))
	{
		// Calculate this regardless, real thread + post count is important.
		$additional_threads = $db->query_read_slave("SELECT COUNT(*) AS total, postuserid AS userid FROM " . TABLE_PREFIX . "thread WHERE postuserid IN (". implode(', ', array_keys($user_cache)) . ") GROUP BY postuserid");
		while ($additional_thread = $db->fetch_array($additional_threads))
		{
			$user_cache["$additional_thread[userid]"]['thread_count'] = intval($additional_thread['total']);
		}

		$additional_posts = $db->query_read_slave("SELECT COUNT(*) AS total, userid AS userid FROM " . TABLE_PREFIX . "post WHERE userid IN (". implode(', ', array_keys($user_cache)) . ") GROUP BY userid");
		while ($additional_post = $db->fetch_array($additional_posts))
		{
			$user_cache["$additional_post[userid]"]['post_count'] = intval($additional_post['total']);
		}
	}

	$show['remove_info'] = $vbulletin->GPC['deleteother'];
	$show['userid_checkbox'] = ($vbulletin->GPC['deleteother'] OR $vbulletin->GPC['useraction'] == 'ban');

	$username_bits = '';
	foreach ($user_cache AS $userid => $user)
	{
		$show['prevent_userselection'] = ($user['post_count'] > 50 AND empty($vbulletin->GPC['useraction']));
		$user['post_count'] = vb_number_format($user['post_count']);
		$templater = vB_Template::create('threadadmin_easyspam_user_option');
			$templater->register('user', $user);
		$username_bits .= $templater->render();
	}

	$show['username_bits'] = !empty($username_bits);
	$show['punitive_action'] = !empty($vbulletin->GPC['useraction']);
	$punitive_action = '';

	switch ($vbulletin->GPC['useraction'])
	{
		case 'ban':
			$ban_usergroups = '';
			// make a list of usergroups into which to move this user
			foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
			{
				if (!($usergroup['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
				{
					$optiontitle = $usergroup['title'];
					$optionvalue = $usergroupid;
					$ban_usergroups .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
				}
			}

			$temp_ban_options = array(
				'D_1'  => "1 $vbphrase[day]",
				'D_2'  => "2 $vbphrase[days]",
				'D_3'  => "3 $vbphrase[days]",
				'D_4'  => "4 $vbphrase[days]",
				'D_5'  => "5 $vbphrase[days]",
				'D_6'  => "6 $vbphrase[days]",
				'D_7'  => "7 $vbphrase[days]",
				'D_10' => "10 $vbphrase[days]",
				'D_14' => "2 $vbphrase[weeks]",
				'D_21' => "3 $vbphrase[weeks]",
				'M_1'  => "1 $vbphrase[month]",
				'M_2' => "2 $vbphrase[months]",
				'M_3' => "3 $vbphrase[months]",
				'M_4' => "4 $vbphrase[months]",
				'M_5' => "5 $vbphrase[months]",
				'M_6' => "6 $vbphrase[months]",
				'Y_1' => "1 $vbphrase[year]",
				'Y_2' => "2 $vbphrase[years]",
			);

			$temp_ban_periods = '';
			foreach ($temp_ban_options AS $thisperiod => $text)
			{
				if ($liftdate = convert_date_to_timestamp($thisperiod))
				{
					$optiontitle = $text . ' (' . vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'], $liftdate) . ')';
					$optionvalue = $thisperiod;
					$temp_ban_periods .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
				}
			}

			$templater = vB_Template::create('threadadmin_easyspam_ban');
				$templater->register('ban_usergroups', $ban_usergroups);
				$templater->register('editorid', $editorid);
				$templater->register('temp_ban_periods', $temp_ban_periods);
			$punitive_action = $templater->render();

		break;
		default:
			($hook = vBulletinHook::fetch_hook('inlinemod_spamconfirm_defaultaction')) ? eval($hook) : false;
	}

	if ($show['punitive_action'] OR $vbulletin->GPC['deleteother'])
	{
		$deleteother = $vbulletin->GPC['deleteother'];
		$remove = $vbulletin->GPC['remove'];
		$report = $vbulletin->GPC['report'];
		$useraction = $vbulletin->GPC['useraction'];
		$keepattachments = $vbulletin->GPC['keepattachments'];
		$deletetype = $vbulletin->GPC['deletetype'];
		$deletereason = htmlspecialchars_uni($vbulletin->GPC['deletereason']);
		$type = $vbulletin->GPC['type'];

		$headinclude .= vB_Template::create('threadadmin_easyspam_headinclude')->render();

		// There isn't a punitive action to apply if there are no users.
		if (!$show['username_bits'])
		{
			$useraction = '';
			$deleteother = false;
			$show['punitive_action'] = false;
		}

		if ($vbulletin->GPC['type'] == 'thread')
		{
			$navbits[''] = $vbphrase['delete_threads_as_spam'];
			$threadids = implode(',', $threadids);
		}
		else
		{
			$navbits[''] = $vbphrase['delete_posts_as_spam'];
			$postids = implode(',', $postids);
		}

		$page_templater = vB_Template::create('threadadmin_easyspam_confirm');
		$page_templater->register('deleteother', $deleteother);
		$page_templater->register('deletereason', $deletereason);
		$page_templater->register('deletetype', $deletetype);
		$page_templater->register('keepattachments', $keepattachments);
		$page_templater->register('postid', $postid);
		$page_templater->register('postids', $postids);
		$page_templater->register('punitive_action', $punitive_action);
		$page_templater->register('report', $report);
		$page_templater->register('threadids', $threadids);
		$page_templater->register('threadinfo', $threadinfo);
		$page_templater->register('type', $type);
		$page_templater->register('useraction', $useraction);
		$page_templater->register('username_bits', $username_bits);
	}
	else
	{
		$_POST['do'] = 'dodeletespam';
	}

	($hook = vBulletinHook::fetch_hook('inlinemod_spamconfirm')) ? eval($hook) : false;
}

if ($_POST['do'] == 'dodeletespam')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deleteother'     => TYPE_BOOL,
		'report'          => TYPE_BOOL,
		'useraction'      => TYPE_STR,
		'userid'          => TYPE_ARRAY_UINT,
		'type'            => TYPE_STR,
		'deletetype'      => TYPE_UINT, // 1 = soft, 2 = hard
		'deletereason'    => TYPE_STR,
		'keepattachments' => TYPE_BOOL,
	));

	// Check if we have users to punish
	if (!empty($user_cache))
	{
		switch ($vbulletin->GPC['useraction'])
		{
			case 'ban':
				$vbulletin->input->clean_array_gpc('p', array(
					'usergroupid'       => TYPE_UINT,
					'period'            => TYPE_STR,
					'reason'            => TYPE_STR,
				));

				if (!isset($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]) OR ($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
				{
					eval(standard_error(fetch_error('invalid_usergroup_specified')));
				}

				// check that the number of days is valid
				if ($vbulletin->GPC['period'] != 'PERMANENT' AND !preg_match('#^(D|M|Y)_[1-9][0-9]?$#', $vbulletin->GPC['period']))
				{
					eval(standard_error(fetch_error('invalid_ban_period_specified')));
				}

				if ($vbulletin->GPC['period'] == 'PERMANENT')
				{
					// make this ban permanent
					$liftdate = 0;
				}
				else
				{
					// get the unixtime for when this ban will be lifted
					$liftdate = convert_date_to_timestamp($vbulletin->GPC['period']);
				}

				$user_dms = array();

				$current_bans = $db->query_read("
					SELECT user.userid, userban.liftdate, userban.bandate
					FROM " . TABLE_PREFIX . "user AS user
					LEFT JOIN " . TABLE_PREFIX . "userban AS userban ON(userban.userid = user.userid)
					WHERE user.userid IN (" . implode(',', array_keys($user_cache)) . ")
				");
				while ($current_ban = $db->fetch_array($current_bans))
				{
					$userinfo = $user_cache["$current_ban[userid]"];
					$userid = $userinfo['userid'];

					if ($current_ban['bandate'])
					{ // they already have a ban, check if the current one is being made permanent, continue if its not
						if ($liftdate AND $liftdate < $current_ban['liftdate'])
						{
							continue;
						}

						// there is already a record - just update this record
						$db->query_write("
							UPDATE " . TABLE_PREFIX . "userban SET
							bandate = " . TIMENOW . ",
							liftdate = $liftdate,
							adminid = " . $vbulletin->userinfo['userid'] . ",
							reason = '" . $db->escape_string($vbulletin->GPC['reason']) . "'
							WHERE userid = $userinfo[userid]
						");
					}
					else
					{
						// insert a record into the userban table
						/*insert query*/
						$db->query_write("
							INSERT INTO " . TABLE_PREFIX . "userban
							(userid, usergroupid, displaygroupid, customtitle, usertitle, adminid, bandate, liftdate, reason)
							VALUES
							($userinfo[userid], $userinfo[usergroupid], $userinfo[displaygroupid], $userinfo[customtitle], '" . $db->escape_string($userinfo['usertitle']) . "', " . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ", $liftdate, '" . $db->escape_string($vbulletin->GPC['reason']) . "')
						");
					}

					// update the user record
					$user_dms[$userid] =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
					$user_dms[$userid]->set_existing($userinfo);
					$user_dms[$userid]->set('usergroupid', $vbulletin->GPC['usergroupid']);
					$user_dms[$userid]->set('displaygroupid', 0);

					// update the user's title if they've specified a special user title for the banned group
					if ($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]['usertitle'] != '')
					{
						$user_dms[$userid]->set('usertitle', $vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]['usertitle']);
						$user_dms[$userid]->set('customtitle', 0);
					}
					$user_dms[$userid]->pre_save();
				}

				foreach ($user_dms AS $userdm)
				{
					$userdm->save();
				}
			break;
			default:
				($hook = vBulletinHook::fetch_hook('inlinemod_deletespam_defaultaction')) ? eval($hook) : false;
		}
	}

	// report
	if ($vbulletin->GPC['report'] AND !empty($vbulletin->options['vb_antispam_key']))
	{ // report to Akismet
		require_once(DIR . '/includes/class_akismet.php');
		$akismet = new vB_Akismet($vbulletin);
		$akismet->akismet_board = $vbulletin->options['bburl'];
		$akismet->akismet_key = $vbulletin->options['vb_antispam_key'];
		if ($vbulletin->GPC['type'] == 'thread')
		{
			$posts = $db->query_read("
				SELECT post.*, postlog.*
				FROM " . TABLE_PREFIX . "thread AS thread
				INNER JOIN " . TABLE_PREFIX . "post AS post ON (post.postid = thread.firstpostid)
				INNER JOIN " . TABLE_PREFIX . "postlog AS postlog ON (postlog.postid = post.postid)
				WHERE thread.threadid IN (" . implode(',', $threadids) . ")
			");
		}
		else
		{
			$posts = $db->query_read("
				SELECT post.*, postlog.*
				FROM " . TABLE_PREFIX . "post AS post
				INNER JOIN " . TABLE_PREFIX . "postlog AS postlog ON (postlog.postid = post.postid)
				WHERE post.postid IN (" . implode(',', $postids) . ")
			");
		}

		while ($post = $db->fetch_array($posts))
		{
			$akismet->mark_as_spam(array('user_ip' => long2ip($post['ip']), 'user_agent' => $post['useragent'], 'comment_type' => 'post', 'comment_author' => $post['username'], 'comment_content' => $post['pagetext']));
		}
	}

	// delete threads that are defined explicitly as spam by being ticked
	$physicaldel = ($vbulletin->GPC['deletetype'] == 2) ? true : false;
	$skipped_user_prune = array();

	if ($vbulletin->GPC['deleteother'] AND !empty($user_cache) AND can_moderate(-1, 'canmassprune'))
	{
		$remove_all_posts = array();
		$user_checks = $db->query_read_slave("SELECT COUNT(*) AS total, userid AS userid FROM " . TABLE_PREFIX . "post WHERE userid IN (". implode(', ', array_keys($user_cache)) . ") GROUP BY userid");
		while ($user_check = $db->fetch_array($user_checks))
		{
			if (intval($user_check['total']) <= 50)
			{
				$remove_all_posts[] = $user_check['userid'];
			}
			else
			{
				$skipped_user_prune[] = $user_check['userid'];
			}
		}

		if (!empty($remove_all_posts))
		{
			$threads = $db->query_read_slave("SELECT threadid FROM " . TABLE_PREFIX . "thread WHERE postuserid IN (". implode(', ', $remove_all_posts) . ")");
			while ($thread = $db->fetch_array($threads))
			{
				$threadids[] = $thread['threadid'];
			}

			// Yes this can pick up firstposts of threads but we check later on when fetching info, so it won't matter if its already deleted
			$posts = $db->query_read_slave("SELECT postid FROM " . TABLE_PREFIX . "post WHERE userid IN (". implode(', ', $remove_all_posts) . ")");
			while ($post = $db->fetch_array($posts))
			{
				$postids[] = $post['postid'];
			}
		}
	}

	if (!empty($threadids))
	{
		// Validate threads
		$threads = $db->query_read_slave("
			SELECT threadid, open, visible, forumid, title, postuserid
			FROM " . TABLE_PREFIX . "thread
			WHERE threadid IN (" . implode(',', $threadids) . ")
		");
		while ($thread = $db->fetch_array($threads))
		{
			$forumperms = fetch_permissions($thread['forumid']);
			if 	(
				!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
					OR
				!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
					OR
				(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
				)
			{
				print_no_permission();
			}

			if ($thread['open'] == 10 AND !can_moderate($thread['forumid'], 'canmanagethreads'))
			{
				// No permission to remove redirects.
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_thread_redirects', $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
			}
			else if (!$thread['visible'] AND !can_moderate($thread['forumid'], 'canmoderateposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
			}
			else if ($thread['visible'] == 2 AND !can_moderate($thread['forumid'], 'candeleteposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $vbphrase['n_a'], $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
			}
			else if ($thread['open'] != 10)
			{
				if (!can_moderate($thread['forumid'], 'canremoveposts') AND $physicaldel)
				{
					eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $vbphrase['n_a'], $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
				}
				else if (!can_moderate($thread['forumid'], 'candeleteposts') AND !$physicaldel)
				{
					eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $vbphrase['n_a'], $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
				}
			}

			$threadarray["$thread[threadid]"] = $thread;
			$forumlist["$thread[forumid]"] = true;
		}
	}

	$delinfo = array(
			'userid'          => $vbulletin->userinfo['userid'],
			'username'        => $vbulletin->userinfo['username'],
			'reason'          => $vbulletin->GPC['deletereason'],
			'keepattachments' => $vbulletin->GPC['keepattachments'],
	);
	foreach ($threadarray AS $threadid => $thread)
	{
		$countposts = $vbulletin->forumcache["$thread[forumid]"]['options'] & $vbulletin->bf_misc_forumoptions['countposts'];
		if (!$physicaldel AND $thread['visible'] == 2)
		{
			# Thread is already soft deleted
			continue;
		}

		$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$threadman->set_existing($thread);

		// Redirect
		if ($thread['open'] == 10)
		{
			$threadman->delete(false, true, $delinfo);
		}
		else
		{
			$threadman->delete($countposts, $physicaldel, $delinfo);

			// Search index maintenance
			vb_Search_Indexcontroller_Queue::indexQueue('vBForum', 'Post', 'delete_thread', $thread['threadid']);
		}
		unset($threadman);
	}

	if (!empty($postids))
	{
		// Validate Posts
		$posts = $db->query_read_slave("
			SELECT post.postid, post.threadid, post.parentid, post.visible, post.title,
				thread.forumid, thread.title AS threadtitle, thread.postuserid, thread.firstpostid, thread.visible AS thread_visible
			FROM " . TABLE_PREFIX . "post AS post
			LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING (threadid)
			WHERE postid IN (" . implode(',', $postids) . ")
			ORDER BY postid
		");
		while ($post = $db->fetch_array($posts))
		{
			$postarray["$post[postid]"] = $post;
			$threadlist["$post[threadid]"] = true;
			$forumlist["$post[forumid]"] = true;
			if ($post['firstpostid'] == $post['postid'])
			{	// deleting a thread so do not decremement the counters of any other posts in this thread
				$firstpost["$post[threadid]"] = true;
			}
			else if (!empty($firstpost["$post[threadid]"]))
			{
				$postarray["$post[postid]"]['skippostcount'] = true;
			}
		}
	}

	$gotothread = true;
	foreach ($postarray AS $postid => $post)
	{
		$foruminfo = fetch_foruminfo($post['forumid']);

		$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$postman->set_existing($post);
		$postman->delete(($foruminfo['countposts'] AND !$post['skippostcount']), $post['threadid'], $physicaldel, $delinfo);
		unset($postman);

		if ($vbulletin->GPC['threadid'] == $post['threadid'] AND $post['postid'] == $post['firstpostid'])
		{	// we've deleted the thread that we activated this action from so we can only return to the forum
			$gotothread = false;
		}
		else if ($post['postid'] == $postinfo['postid'] AND $physicaldel)
		{	// we came in via a post, which we have deleted so we have to go back to the thread
			$vbulletin->url = fetch_seo_url('thread', $postinfo, null, 'threadid', 'threadtitle');
		}
	}

	foreach(array_keys($threadlist) AS $threadid)
	{
		build_thread_counters($threadid);
	}
	foreach (array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	// empty cookie
	if ($vbulletin->GPC['type'] == 'thread')
	{
		setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');
	}
	else
	{
		setcookie('vbulletin_inlinepost', '', TIMENOW - 3600, '/');
	}

	($hook = vBulletinHook::fetch_hook('inlinemod_deletespam')) ? eval($hook) : false;

	if ($gotothread)
	{
		// Actually let's do nothing and redirect to where we were
	}
	else if ($vbulletin->GPC['forumid'])
	{	// redirect to the forum that we activated from since we hard deleted the thread
		$vbulletin->url = fetch_seo_url('forum', $foruminfo);
	}
	else
	{
		// this really shouldn't happen...
		$vbulletin->url = fetch_seo_url('forumhome', array());
	}

	// Following users had more than 50 posts, so we couldn't do a mass remove.
	if (!empty($skipped_user_prune))
	{
		$usercount = 0;
		$users = array();
		foreach ($skipped_user_prune AS $userid)
		{
			$usercount++;
			$user = $user_cache[$userid];
			$user['comma'] = $vbphrase['comma_space'];
			$users[$usercount] = $user;
		}

		// Last element
		if ($usercount)
		{
			$list[$usercount]['comma'] = '';
		}

		$headinclude .= vB_Template::create('threadadmin_easyspam_headinclude')->render();
		$navbits[''] = $vbphrase['spam_management'];

		$page_templater = vB_Template::create('threadadmin_easyspam_skipped_prune');
		$page_templater->register('users', $users);
	}
	else
	{
		print_standard_redirect(!empty($postids) ? 'redirect_inline_deletedposts' : 'redirect_inline_deleted', true, $forceredirect);
	}
}

// ############################### start dodelete threads ###############################
if ($_POST['do'] == 'dodeletethreads')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deletetype'      => TYPE_UINT, 	// 1=leave message; 2=removal
		'deletereason'    => TYPE_STR,
		'keepattachments' => TYPE_BOOL,
	));

	$physicaldel = iif($vbulletin->GPC['deletetype'] == 1, false, true);

	$delinfo = array(
		'userid'          => $vbulletin->userinfo['userid'],
		'username'        => $vbulletin->userinfo['username'],
		'reason'          => $vbulletin->GPC['deletereason'],
		'keepattachments' => $vbulletin->GPC['keepattachments']
	);

	// Validate threads
	$threads = $db->query_read_slave("
		SELECT threadid, open, visible, forumid, title, prefixid, postuserid, pollid
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid IN(" . implode(',', $threadids) . ")
	");
	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

		if ($thread['open'] == 10 AND !can_moderate($thread['forumid'], 'canmanagethreads'))
		{
			// No permission to remove redirects.
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_thread_redirects', $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}
		else if (!$thread['visible'] AND !can_moderate($thread['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($thread['visible'] == 2 AND !can_moderate($thread['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}
		else if ($thread['open'] != 10)
		{
			if (!can_moderate($thread['forumid'], 'canremoveposts') AND $physicaldel)
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
			}
			else if (!can_moderate($thread['forumid'], 'candeleteposts') AND !$physicaldel)
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
			}
		}

		$threadarray["$thread[threadid]"] = $thread;
		$forumlist["$thread[forumid]"] = true;
	}

	if (empty($threadarray))
	{
			eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
	}

	foreach ($threadarray AS $threadid => $thread)
	{
		$countposts = $vbulletin->forumcache["$thread[forumid]"]['options'] & $vbulletin->bf_misc_forumoptions['countposts'];
		if (!$physicaldel AND $thread['visible'] == 2)
		{
			# Thread is already soft deleted
			continue;
		}

		$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$threadman->set_existing($thread);

		// Redirect
		if ($thread['open'] == 10)
		{
			$threadman->delete(false, true, $delinfo);
		}
		else
		{
			$threadman->delete($countposts, $physicaldel, $delinfo);

			// Search index maintenance
		}
		unset($threadman);
	}

	foreach (array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	// empty cookie
	setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_dodeletethread')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_deleted', true, $forceredirect);
}

// ############################### start do undelete thread ###############################
if ($_POST['do'] == 'undeletethread')
{

	// Validate threads
	$threads = $db->query_read_slave("
		SELECT threadid, visible, forumid, title, prefixid, postuserid
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid IN ($threadids)
			AND visible = 2
			AND open <> 10
	");
	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

		if (!can_moderate($thread['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}

		$threadarray["$thread[threadid]"] = $thread;
		$forumlist["$thread[forumid]"] = true;
	}

	if (empty($threadarray))
	{
			eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
	}

	foreach ($threadarray AS $threadid => $thread)
	{
		$countposts = $vbulletin->forumcache["$thread[forumid]"]['options'] & $vbulletin->bf_misc_forumoptions['countposts'];
		undelete_thread($thread['threadid'], $countposts, $thread);
	}

	foreach (array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	// empty cookie
	setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_undeletethread')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_undeleted', true, $forceredirect);
}

// ############################### start do approve thread ###############################
if ($_POST['do'] == 'approvethread')
{

	$countingthreads = array();
	$firstposts = array();
	// Validate threads
	$threads = $db->query_read_slave("
		SELECT threadid, visible, forumid, postuserid, firstpostid
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid IN($threadids)
			AND visible = 0
			AND open <> 10
	");
	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}


		if (!can_moderate($thread['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}

		$threadarray["$thread[threadid]"] = $thread;
		$forumlist["$thread[forumid]"] = true;
		$firstposts[] = $thread['firstpostid'];

		$foruminfo = fetch_foruminfo($thread['forumid']);
		if ($foruminfo['countposts'])
		{	// this thread is in a counting forum
			$countingthreads[] = $thread['threadid'];
		}
	}

	if (empty($threadarray))
	{
			eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
	}

	// Set threads visible
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "thread
		SET visible = 1
		WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
	");

	if (!empty($countingthreads))
	{	// Update post count for visible posts
		$userbyuserid = array();
		$posts = $db->query_read_slave("
			SELECT userid
			FROM " . TABLE_PREFIX . "post
			WHERE threadid IN(" . implode(',', $countingthreads) . ")
				AND visible = 1
				AND userid > 0
		");
		while ($post = $db->fetch_array($posts))
		{
			if (!isset($userbyuserid["$post[userid]"]))
			{
				$userbyuserid["$post[userid]"] = 1;
			}
			else
			{
				$userbyuserid["$post[userid]"]++;
			}
		}

		if (!empty($userbyuserid))
		{
			$userbypostcount = array();
			$alluserids = '';

			foreach ($userbyuserid AS $postuserid => $postcount)
			{
				$alluserids .= ",$postuserid";
				$userbypostcount["$postcount"] .= ",$postuserid";
			}
			foreach($userbypostcount AS $postcount => $userids)
			{
				$casesql .= " WHEN userid IN (0$userids) THEN $postcount\n";
			}

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "user
				SET posts = posts +
				CASE
					$casesql
					ELSE 0
				END
				WHERE userid IN (0$alluserids)
			");
		}
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "moderation
		WHERE primaryid IN(" . implode(',', array_keys($threadarray)) . ")
			AND type = 'thread'
	");
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "spamlog
		WHERE postid IN(" . implode(',', $firstposts) . ")
	");

	// Set thread redirects visible
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "thread
		SET visible = 1
		WHERE open = 10 AND pollid IN(" . implode(',', array_keys($threadarray)) . ")
	");

	foreach ($threadarray AS $threadid => $thread)
	{
		$modlog[] = array(
			'userid'   =>& $vbulletin->userinfo['userid'],
			'forumid'  =>& $thread['forumid'],
			'threadid' => $threadid,
		);
	}

	log_moderator_action($modlog, 'approved_thread');

	foreach (array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	// empty cookie
	setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_approvethread')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_approvedthreads', true, $forceredirect);
}

// ############################### start do unapprove thread ###############################
if ($_POST['do'] == 'unapprovethread')
{

	$threadarray = array();
	$countingthreads = array();
	$modrecords = array();

	// Validate threads
	$threads = $db->query_read_slave("
		SELECT threadid, visible, forumid, title, prefixid, postuserid, firstpostid
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid IN($threadids)
			AND visible > 0
			AND open <> 10
	");
	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

		if (!can_moderate($thread['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($thread['visible'] == 2 AND !can_moderate($thread['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}

		$threadarray["$thread[threadid]"] = $thread;
		$forumlist["$thread[forumid]"] = true;

		$foruminfo = fetch_foruminfo($thread['forumid']);
		if ($thread['visible'] AND $foruminfo['countposts'])
		{	// this thread is visible AND in a counting forum
			$countingthreads[] = $thread['threadid'];
		}

		$modrecords[] = "($thread[threadid], 'thread', " . TIMENOW . ")";
	}

	if (empty($threadarray))
	{
			eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
	}

	// Set threads hidden
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "thread
		SET visible = 0
		WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
	");

	// Set thread redirects hidden
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "thread
		SET visible = 0
		WHERE open = 10 AND pollid IN(" . implode(',', array_keys($threadarray)) . ")
	");

	if (!empty($countingthreads))
	{	// Update post count for visible posts
		$userbyuserid = array();
		$posts = $db->query_read_slave("
			SELECT userid
			FROM " . TABLE_PREFIX . "post
			WHERE threadid IN(" . implode(',', $countingthreads) . ")
				AND visible = 1
				AND userid > 0
		");
		while ($post = $db->fetch_array($posts))
		{
			if (!isset($userbyuserid["$post[userid]"]))
			{
				$userbyuserid["$post[userid]"] = -1;
			}
			else
			{
				$userbyuserid["$post[userid]"]--;
			}
		}

		if (!empty($userbyuserid))
		{
			$userbypostcount = array();
			$alluserids = '';

			foreach ($userbyuserid AS $postuserid => $postcount)
			{
				$alluserids .= ",$postuserid";
				$userbypostcount["$postcount"] .= ",$postuserid";
			}
			foreach($userbypostcount AS $postcount => $userids)
			{
				$casesql .= " WHEN userid IN (0$userids) THEN $postcount\n";
			}

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "user
				SET posts = CAST(posts AS SIGNED) +
				CASE
					$casesql
					ELSE 0
				END
				WHERE userid IN (0$alluserids)
			");
		}
	}

	// Insert Moderation Records
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "moderation
		(primaryid, type, dateline)
		VALUES
		" . implode(',', $modrecords) . "
	");

	// Clean out deletionlog
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "deletionlog
		WHERE primaryid IN(" . implode(',', array_keys($threadarray)) . ")
			AND type = 'thread'
	");

	foreach ($threadarray AS $threadid => $thread)
	{
		$modlog[] = array(
			'userid'   =>& $vbulletin->userinfo['userid'],
			'forumid'  =>& $thread['forumid'],
			'threadid' => $threadid,
		);
	}

	log_moderator_action($modlog, 'unapproved_thread');

	foreach (array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	// empty cookie
	setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_unapprovethread')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_unapprovedthreads', true, $forceredirect);
}

// ############################### start do move thread ###############################
if ($_POST['do'] == 'movethread')
{

	// Validate threads
	$threads = $db->query_read_slave("
		SELECT threadid, open, visible, forumid, title, prefixid, postuserid
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid IN ($threadids)
	");

	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

		if ($thread['open'] == 10 AND !can_moderate($thread['forumid'], 'canmanagethreads'))
		{
			// No permission to remove redirects.
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_thread_redirects', $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}
		else if (!$thread['visible'] AND !can_moderate($thread['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($thread['visible'] == 2 AND !can_moderate($thread['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}

		$threadarray["$thread[threadid]"] = $thread;
		$forumlist["$thread[forumid]"]++;
	}

	if (empty($threadarray))
	{
			eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
	}

	$threadcount = count($threadarray);
	$forumcount = count($forumlist);

	asort($forumlist, SORT_NUMERIC);
	$curforumid = array_pop($array = array_keys($forumlist));
	$moveoptions = construct_move_forums_options();

	$option_templater = vB_Template::create('option');
	$option_templater->register('options', $moveoptions);
	$moveforumbits = $option_templater->render();

	($hook = vBulletinHook::fetch_hook('inlinemod_movethread')) ? eval($hook) : false;

	$navbits = array();
	$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];
	$navbits[''] = $vbphrase['move_threads'];

	$page_templater = vB_Template::create('threadadmin_movethreads');
	$page_templater->register('forumcount', $forumcount);
	$page_templater->register('moveforumbits', $moveforumbits);
	$page_templater->register('threadcount', $threadcount);
	$page_templater->register('threadids', $threadids);
}

// ############################### start do domove thread ###############################
if ($_POST['do'] == 'domovethreads')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'destforumid' => TYPE_UINT,
		'redirect'    => TYPE_STR,
		'frame'       => TYPE_STR,
		'period'      => TYPE_UINT,
	));

	// check whether dest can contain posts
	$destforumid = verify_id('forum', $vbulletin->GPC['destforumid']);
	$destforuminfo = fetch_foruminfo($destforumid);
	if (!$destforuminfo['cancontainthreads'] OR $destforuminfo['link'])
	{
		eval(standard_error(fetch_error('moveillegalforum')));
	}

	// check destination forum permissions
	$forumperms = fetch_permissions($destforuminfo['forumid']);
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
	{
		print_no_permission();
	}

	//because of dependant controls its possible that "redirect" doesn't get passed.
	//if not then we want to assume no redirect
	if (!$vbulletin->GPC_exists['redirect'] OR $vbulletin->GPC['redirect'] == 'none')
	{
		$method = 'move';
	}
	else
	{
		$method = 'movered';
	}

	$countingthreads = array();
	$redirectids = array();

	// Validate threads
	$threads = $db->query_read_slave("
		SELECT threadid, visible, open, pollid, title, prefixid, postuserid, forumid
		" . ($method == 'movered' ? ", lastpost, replycount, postusername, lastposter, lastposterid, dateline, views, iconid" : "") . "
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid IN(" . implode(',', $threadids) . ")
	");
	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

		if (!can_moderate($thread['forumid'], 'canmanagethreads'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}
		else if (!$thread['visible'] AND !can_moderate($thread['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($thread['visible'] == 2 AND !can_moderate($thread['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}

		if ($thread['visible'] == 2 AND !can_moderate($destforuminfo['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts_in_destination_forum')));
		}
		else if (!$thread['visible'] AND !can_moderate($destforuminfo['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts_in_destination_forum')));
		}

		// Ignore all threads that are already in the destination forum
		if ($thread['forumid'] == $destforuminfo['forumid'])
		{
			$sameforum = true;
			continue;
		}

		$threadarray["$thread[threadid]"] = $thread;
		$forumlist["$thread[forumid]"] = true;

		if ($thread['open'] == 10)
		{
			$redirectids["$thread[pollid]"][] = $thread['threadid'];
		}
		else if ($thread['visible'])
		{
			$countingthreads[] = $thread['threadid'];
		}
	}

	if (empty($threadarray))
	{
		if ($sameforum)
		{
			eval(standard_error(fetch_error('thread_is_already_in_the_forum')));
		}
		else
		{
			eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
		}
	}

	// check to see if these threads are being returned to a forum they've already been in
	// if redirects exist in the destination forum, remove them
	$checkprevious = $db->query_read_slave("
		SELECT threadid
		FROM " . TABLE_PREFIX . "thread
		WHERE forumid = $destforuminfo[forumid]
			AND open = 10
			AND pollid IN(" . implode(',', array_keys($threadarray)) . ")
	");
	while ($check = $db->fetch_array($checkprevious))
	{
		$old_redirect =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$old_redirect->set_existing($check);
		$old_redirect->delete(false, true, NULL, false);
		unset($old_redirect);
	}

	// check to see if a redirect is being moved to a forum where its destination thread already exists
	// if so delete the redirect
	if (!empty($redirectids))
	{
		$checkprevious = $db->query_read_slave("
			SELECT threadid
			FROM " . TABLE_PREFIX . "thread
			WHERE forumid = $destforuminfo[forumid]
				AND threadid IN(" . implode(',', array_keys($redirectids)) . ")

		");
		while ($check = $db->fetch_array($checkprevious))
		{
			if (!empty($redirectids["$check[threadid]"]))
			{
				foreach($redirectids["$check[threadid]"] AS $threadid)
				{
					$old_redirect =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
					$old_redirect->set_existing($threadarray["$threadid"]);
					$old_redirect->delete(false, true, NULL, false);
					unset($old_redirect);

					# Remove redirect threadids from $threadarray so no log entry is entered below or new redirect is added
					unset($threadarray["$threadid"]);
				}
			}
		}
	}

	if (!empty($threadarray))
	{
		// Move threads
		// If mod can not manage threads in destination forum then unstick all moved threads
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "thread
			SET forumid = $destforuminfo[forumid]
			" . (!can_moderate($destforuminfo['forumid'], 'canmanagethreads') ? ", sticky = 0" : "") . "
			WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
		");

		require_once(DIR . '/includes/functions_prefix.php');
		remove_invalid_prefixes(array_keys($threadarray), $destforuminfo['forumid']);

		// update canview status of thread subscriptions
		update_subscriptions(array('threadids' => array_keys($threadarray)));

		// kill the post cache for these threads
		delete_post_cache_threads(array_keys($threadarray));

		$movelog = array();
		// Insert Redirects FUN FUN FUN
		if ($method == 'movered')
		{
			$redirectsql = array();
			if ($vbulletin->GPC['redirect'] == 'expires')
			{
				switch($vbulletin->GPC['frame'])
				{
					case 'h':
						$expires = mktime(date('H') + $vbulletin->GPC['period'], date('i'), date('s'), date('m'), date('d'), date('y'));
						break;
					case 'd':
						$expires = mktime(date('H'), date('i'), date('s'), date('m'), date('d') + $vbulletin->GPC['period'], date('y'));
						break;
					case 'w':
						$expires = $vbulletin->GPC['period'] * 60 * 60 * 24 * 7 + TIMENOW;
						break;
					case 'y':
						$expires =  mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('y') + $vbulletin->GPC['period']);
						break;
					case 'm':
						default:
						$expires =  mktime(date('H'), date('i'), date('s'), date('m') + $vbulletin->GPC['period'], date('d'), date('y'));
				}
			}
			foreach($threadarray AS $threadid => $thread)
			{
				if ($thread['visible'] == 1)
				{
					$thread['open'] = 10;
					$thread['pollid'] = $threadid;
					unset($thread['threadid']);
					$redir =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
					foreach (array_keys($thread) AS $field)
					{
						// bypassing the verify_* calls; this data should be valid as is
						if (isset($redir->validfields["$field"]))
						{
							$redir->setr($field, $thread["$field"], true, false);
						}
					}
					$redirthreadid = $redir->save();
					if ($vbulletin->GPC['redirect'] == 'expires')
					{
						$redirectsql[] = "$redirthreadid, $expires";
					}
					unset($redir);
				}
				else
				{
					// else this is a moderated or deleted thread so leave no redirect behind
					// insert modlog entry of just "move", not "moved with redirect"
					// unset threadarray[threadid] so thread_moved_with_redirect log entry is not entered below.

					unset($threadarray["$threadid"]);
					$movelog = array(
						'userid'   =>& $vbulletin->userinfo['userid'],
						'forumid'  =>& $thread['forumid'],
						'threadid' => $threadid,
					);
				}
			}

			if (!empty($redirectsql))
			{
				$db->query_write("
					INSERT INTO " . TABLE_PREFIX . "threadredirect
						(threadid, expires)
					VALUES
						(" . implode("), (", $redirectsql) . ")
				");
			}
		}

		if (!empty($movelog))
		{
			log_moderator_action($movelog, 'thread_moved_to_x', $destforuminfo['title']);
		}

		if (!empty($threadarray))
		{
			foreach ($threadarray AS $threadid => $thread)
			{
				$modlog[] = array(
					'userid'   =>& $vbulletin->userinfo['userid'],
					'forumid'  =>& $thread['forumid'],
					'threadid' => $threadid,
				);
			}

			log_moderator_action($modlog, ($method == 'move') ? 'thread_moved_to_x' : 'thread_moved_with_redirect_to_a', $destforuminfo['title']);

			if (!empty($countingthreads))
			{
				$posts = $db->query_read_slave("
					SELECT userid, threadid
					FROM " . TABLE_PREFIX . "post
					WHERE threadid IN(" . implode(',', $countingthreads) . ")
						AND visible = 1
						AND	userid > 0
				");
				$userbyuserid = array();
				while ($post = $db->fetch_array($posts))
				{
					$foruminfo = fetch_foruminfo($threadarray["$post[threadid]"]['forumid']);
					if ($foruminfo['countposts'] AND !$destforuminfo['countposts'])
					{	// Take away a post
						if (!isset($userbyuserid["$post[userid]"]))
						{
							$userbyuserid["$post[userid]"] = -1;
						}
						else
						{
							$userbyuserid["$post[userid]"]--;
						}
					}
					else if (!$foruminfo['countposts'] AND $destforuminfo['countposts'])
					{	// Add a post
						if (!isset($userbyuserid["$post[userid]"]))
						{
							$userbyuserid["$post[userid]"] = 1;
						}
						else
						{
							$userbyuserid["$post[userid]"]++;
						}
					}
				}

				if (!empty($userbyuserid))
				{
					$userbypostcount = array();
					$alluserids = '';

					foreach ($userbyuserid AS $postuserid => $postcount)
					{
						$alluserids .= ",$postuserid";
						$userbypostcount["$postcount"] .= ",$postuserid";
					}
					foreach ($userbypostcount AS $postcount => $userids)
					{
						$casesql .= " WHEN userid IN (0$userids) THEN $postcount";
					}

					$db->query_write("
						UPDATE " . TABLE_PREFIX . "user
						SET posts = CAST(posts AS SIGNED) +
						CASE
							$casesql
							ELSE 0
						END
						WHERE userid IN (0$alluserids)
					");
				}
			}
		}
	}

	// Search index maintenance
	foreach($threadarray AS $threadid => $thread)
	{
		vb_Search_Indexcontroller_Queue::indexQueue('vBForum', 'Post',
			'thread_data_change', $threadid);
	}

	foreach(array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}
	build_forum_counters($destforuminfo['forumid']);

	// empty cookie
	setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_domovethread')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_moved', true, $forceredirect);
}

// ############################### start do merge thread ###############################
if ($_POST['do'] == 'mergethread' OR $_POST['do'] == 'mergethreadcompat')
{
	$pollarray = array();

	// Validate threads
	$threads = $db->query_read_slave("
		SELECT thread.threadid, thread.prefixid, thread.visible, thread.open, thread.pollid, thread.title, thread.postuserid, thread.forumid,
			poll.question
		FROM " . TABLE_PREFIX . "thread AS thread
		LEFT JOIN " . TABLE_PREFIX . "poll AS poll ON (thread.pollid = poll.pollid)
		WHERE threadid IN($threadids)
			AND open <> 10
		ORDER BY thread.dateline, thread.threadid
	");
	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

		if (!can_moderate($thread['forumid'], 'canmanagethreads'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}
		else if (!$thread['visible'] AND !can_moderate($thread['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($thread['visible'] == 2 AND !can_moderate($thread['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}

		if ($thread['pollid'] AND $thread['question'])
		{
			$pollarray["$thread[pollid]"] = $thread['question'];
		}

		if (empty($title))
		{
			$title = $thread['title'];
		}

		$threadarray["$thread[threadid]"] = $thread;
		$forumlist["$thread[forumid]"]++;
	}

	if (empty($threadarray))
	{
			eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
	}

	$threadcount = count($threadarray);
	$forumcount = count($forumlist);

	if ($threadcount == 1)
	{
		eval(standard_error(fetch_error('not_much_would_be_accomplished_by_merging')));
	}

	$max = 0;
	foreach ($forumlist AS $forumid => $count)
	{
		if ($count > $max)
		{
			$curforumid = $forumid;
		}
	}

	if (count($pollarray) > 1)
	{
		foreach ($pollarray AS $optionvalue => $optiontitle)
		{
			$optionclass = '';
			$pollbits .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
		}
	}

	foreach ($threadarray AS $thread)
	{
		$optiontitle = "[{$thread['threadid']}] " . ($thread['prefixid'] ? $vbphrase["prefix_$thread[prefixid]_title_plain"] . ' ' : '') . $thread['title'];
		$optionvalue = $thread['threadid'];
		$optionclass = '';
		$movethreadbits .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
	}

	$moveoptions = construct_move_forums_options();

	$option_templater = vB_Template::create('option');
	$option_templater->register('options', $moveoptions);
	$moveforumbits = $option_templater->render();

	if ($_POST['do'] == 'mergethreadcompat')
	{
		$show['skipclearlist'] = true;
	}

	($hook = vBulletinHook::fetch_hook('inlinemod_mergethread')) ? eval($hook) : false;

	// draw navbar
	$navbits = array();
	$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];

	$navbits[''] = $vbphrase['merge_threads'];

	$page_templater = vB_Template::create('threadadmin_mergethreads');
	$page_templater->register('forumcount', $forumcount);
	$page_templater->register('moveforumbits', $moveforumbits);
	$page_templater->register('movethreadbits', $movethreadbits);
	$page_templater->register('pollbits', $pollbits);
	$page_templater->register('threadcount', $threadcount);
	$page_templater->register('threadids', $threadids);
}

// ############################### start do domerge thread ###############################
if ($_POST['do'] == 'domergethreads')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'destforumid'   => TYPE_UINT,
		'destthreadid'  => TYPE_UINT,
		'redirect'      => TYPE_STR,
		'frame'         => TYPE_STR,
		'period'        => TYPE_UINT,
		'pollid'        => TYPE_UINT,
		'skipclearlist' => TYPE_BOOL,
	));

	// check whether dest can contain posts
	$destforumid = verify_id('forum', $vbulletin->GPC['destforumid']);
	$destforuminfo = fetch_foruminfo($destforumid);
	if (!$destforuminfo['cancontainthreads'] OR $destforuminfo['link'])
	{
		eval(standard_error(fetch_error('moveillegalforum')));
	}

	// check destination forum permissions
	$forumperms = fetch_permissions($destforuminfo['forumid']);
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
	{
		print_no_permission();
	}

	if ($vbulletin->GPC['type'] == 1)
	{	// Mod cannot create merged hidden thread if they can't moderateposts dest forum
		if (!can_moderate($destforuminfo['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts_in_destination_forum')));
		}
	}
	else if ($vbulletin->GPC['type'] == 2)
	{	// Mod can not create merged deleted thread if they can't deletethreads in dest forum
		if (!can_moderate($destforuminfo['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts_in_destination_forum')));
		}
	}

	$counter = array(
		'moderated' => array(),
		'normal'    => array(),
		'deleted'   => array()
	);

	$destthread = 0;
	$pollinfo = array();
	$firstthread = array();
	$views = 0;
	$firstpostids = array();

	$sticky = 1;

	// Validate threads
	$threads = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid IN(" . implode(',', $threadids) . ")
			AND open <> 10
		ORDER BY dateline, threadid
	");
	while ($thread = $db->fetch_array($threads))
	{
		$forumperms = fetch_permissions($thread['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $thread['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) . ' ' : '');

		if (!can_moderate($thread['forumid'], 'canmanagethreads'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}
		else if (!$thread['visible'] AND !can_moderate($thread['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($thread['visible'] == 2 AND !can_moderate($thread['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $vbphrase['n_a'], $thread['prefix_plain_html'] . $thread['title'], $vbulletin->forumcache["$thread[forumid]"]['title'])));
		}

		if ($thread['pollid'] AND (!$vbulletin->GPC['pollid'] OR ($thread['pollid'] == $vbulletin->GPC['pollid'])))
		{
			$pollinfo = array(
				'pollid'    => $thread['pollid'],
				'votenum'   => $thread['votenum'],
				'votetotal' => $thread['votetotal'],
				'threadid'  => $thread['threadid'],
			);
		}

		if (empty($firstthread))
		{
			$firstthread = $thread;
		}

		if ($thread['threadid'] == $vbulletin->GPC['destthreadid'])
		{
			$destthread = $thread;
		}
		else
		{
			switch($thread['visible'])
			{
				case '0':
					$counter['moderated'][] = $thread['threadid'];
					break;
				case '1':
					$counter['normal'][] = $thread['threadid'];
					break;
				case '2':
					$counter['deleted'][] = $thread['threadid'];
					break;
				default: // Invalid State
					continue;
			}
		}

		$threadarray["$thread[threadid]"] = $thread;
		$views += $thread['views'];
		$firstpostids[] = $thread['firstpostid'];
		$forumlist["$thread[forumid]"] = true;
	}
	if (empty($threadarray) OR empty($destthread))
	{
		eval(standard_error(fetch_error('you_did_not_select_any_valid_threads')));
	}

	if (count($threadarray) == 1)
	{
		eval(standard_error(fetch_error('not_much_would_be_accomplished_by_merging')));
	}

	@ignore_user_abort(true);
	$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
	$threadman->set_existing($destthread);
	$threadman->set('forumid', $destforuminfo['forumid']);
	$threadman->set('views', $views);
	vb_Search_Indexcontroller_Queue::indexQueue('vBForum', 'Post', 'thread_data_change',
				$destthread['threadid']);
	// Poll coming from a thread other than the dest's current poll (if it has one)
	if (!empty($pollinfo) AND $destthread['threadid'] != $pollinfo['threadid'])
	{
		// Dest already has a poll so we need to kill it
		if ($destthread['pollid'])
		{
			$pollman =& datamanager_init('Poll', $vbulletin, ERRTYPE_STANDARD);
			$pollman->set_existing($destthread);
			$pollman->delete();
			unset($pollman);
		}

		$threadman->set('pollid', $pollinfo['pollid']);
		$threadman->set('votenum', $pollinfo['votenum']);
		$threadman->set('votetotal', $pollinfo['votetotal']);

		$threadarray["$pollinfo[threadid]"]['pollid'] = 0;
		$threadarray["$pollinfo[threadid]"]['votenum'] = 0;
		$threadarray["$pollinfo[threadid]"]['votetotal'] = 0;
		// Remove poll from source thread so delete_thread doesn't remove it
		$pollthreadinfo = array('threadid' => $pollinfo['threadid']);
		$threadpollman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
		$threadpollman->set_existing($pollthreadinfo);
		$threadpollman->set('pollid', 0);
		$threadpollman->set('votenum', 0);
		$threadpollman->set('votetotal', 0);
		$threadpollman->save();
		unset($threadpollman);
	}

	$threadman->save();
	unset($threadman);

	// Merged thread contains moderated threads
	if (count($counter['moderated']))
	{
		// Delete thread records that need to be converted into replies, simpler than constructing a massive case to alter them.
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "moderation
			WHERE primaryid IN(" . implode(',', $counter['moderated']) . ")
				AND type = 'thread'
		");

		$insertrecords = array();
		// Insert posts back in now
		foreach ($counter['moderated'] AS $threadid)
		{
			$insertrecords[] = "(" . $threadarray["$threadid"]['firstpostid'] . ", 'reply', " . TIMENOW . ")";
		}
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "moderation
				(primaryid, type, dateline)
			VALUES
			" . implode(',', $insertrecords) . "
		");

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "post AS post
			LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (post.threadid = thread.threadid)
			SET post.visible = 0
			WHERE	post.threadid IN(" . implode(',', $counter['moderated']) . ")
				AND post.visible = 1
				AND thread.firstpostid = post.postid
		");
	}

	// Merged thread contains deleted threads
	if (count($counter['deleted']))
	{
		// Remove any deletion records for deleted threads as they are now undeleted
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "deletionlog
			WHERE primaryid IN(" . implode(',', $counter['deleted']) . ")
				AND type = 'thread'
		");
	}

	// Update parentids
	// Not certain about this -  seems that having a parentid of 0 is equal to having a parentid of the first postid so perhaps this is needless
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "post
		SET parentid = $firstthread[firstpostid]
		WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
			AND postid <> $firstthread[firstpostid]
			AND parentid = 0
	");

	// Update Redirects
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "thread
		SET pollid = $destthread[threadid]
		WHERE open = 10
			AND pollid IN(" . implode(',', array_keys($threadarray)) . ")
	");

	$userbyuserid = array();

	$hiddenthreads = array_merge($counter['deleted'], $counter['moderated']);

	// Source Dest  Visible Thread    Hidden Thread
	// Yes    Yes   +hidden           -visible
	// Yes    No    -visible          -visible
	// No     Yes   +visible,+hidden  ~
	// No     No    ~                 ~

	$posts = $db->query_read_slave("
		SELECT userid, threadid
		FROM " . TABLE_PREFIX . "post
		WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
			AND visible = 1
			AND userid > 0
	");
	while ($post = $db->fetch_array($posts))
	{
		$set = 0;

		$foruminfo = fetch_foruminfo($threadarray["$post[threadid]"]['forumid']);

		// visible thread that merges moderated or deleted threads into a counting forum
		// increment post counts belonging to hidden/deleted threads
		if ($destthread['visible'] == 1 AND $destforuminfo['countposts'] AND in_array($post['threadid'], $hiddenthreads))
		{
			$set = 1;
		}

		// hidden thread that merges visible threads from a counting forum
		// OR visible thread that merges visible threads from a counting forum into a non counting forum
		// decrement post counts belonging to visible threads
		else if ($foruminfo['countposts'] AND (($destthread['visible'] != 1) OR ($destthread['visible'] == 1 AND !$destforuminfo['countposts'])) AND in_array($post['threadid'], $counter['normal']))
		{
			$set = -1;
		}

		// Visible thread that merges visible threads from a non counting forum into a counting forum
		// Increment post counts belonging to visible threads
		else if ($destthread['visible'] == 1 AND !$foruminfo['countposts'] AND $destforuminfo['countposts'] AND in_array($post['threadid'], $counter['normal']))
		{
			$set = 1;
		}

		if ($set != 0)
		{
			if (!isset($userbyuserid["$post[userid]"]))
			{
				$userbyuserid["$post[userid]"] = $set;
			}
			else if ($set == -1)
			{
				$userbyuserid["$post[userid]"]--;
			}
			else
			{
				$userbyuserid["$post[userid]"]++;
			}
		}
	}


	if (!empty($userbyuserid))
	{
		$userbypostcount = array();
		$alluserids = '';
		foreach ($userbyuserid AS $postuserid => $postcount)
		{
			$alluserids .= ",$postuserid";
			$userbypostcount["$postcount"] .= ",$postuserid";
		}
		foreach($userbypostcount AS $postcount => $userids)
		{
			$casesql .= " WHEN userid IN (0$userids) THEN $postcount\n";
		}

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET posts = CAST(posts AS SIGNED) +
			CASE
				$casesql
				ELSE 0
			END
			WHERE userid IN (0$alluserids)
		");
	}

	// Update post threadids
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "post
		SET threadid = $destthread[threadid]
		WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
	");

	// kill the post cache for the dest thread
	delete_post_cache_threads(array($destthread['threadid']));

	// Update subscribed threads
	$db->query_write("
		UPDATE IGNORE " . TABLE_PREFIX . "subscribethread
		SET threadid = $destthread[threadid]
		WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
	");

	require_once(DIR . '/includes/class_taggablecontent.php');
	$content = vB_Taggable_Content_Item::create($vbulletin, "vBForum_Thread",
		$destthread['threadid'], $destthread);
	$content->merge_tag_attachments(array_keys($threadarray));

	$users = array();
	$ratings = $db->query_read_slave("
		SELECT threadrateid, threadid, userid, vote, ipaddress
		FROM " . TABLE_PREFIX . "threadrate
		WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
	");
	while ($rating = $db->fetch_array($ratings))
	{
		$id = (!empty($rating['userid'])) ? $rating['userid'] : $rating['ipaddress'];
		$users["$id"]['vote'] += $rating['vote'];
		$users["$id"]['total'] += 1;
	}

	if (!empty($users))
	{
		$sql = array();
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "threadrate
			WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
		");

		foreach ($users AS $id => $rating)
		{
			if (is_int($id))
			{
				$userid = $id;
				$ipaddress = '';
			}
			else
			{
				$userid = 0;
				$ipaddress = $id;
			}

			$vote = round($rating['vote'] / $rating['total']);
			$sql[] = "($destthread[threadid], $userid, $vote, '" . $db->escape_string($ipaddress) . "')";
		}
		unset($users);

		if (!empty($sql))
		{
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "threadrate
					(threadid, userid, vote, ipaddress)
				VALUES
					" . implode(",\n", $sql)
			);
			unset($sql);
		}
	}

	// Remove destthread from the threadarray now so we don't lose it.
	unset($threadarray["{$destthread['threadid']}"]);

	// We had multiple subscriptions so remove all but the main one now
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "subscribethread
		WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
	");

/*
	// remove any duplicated tags
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "tagcontent
		WHERE contentid IN(" . implode(',', array_keys($threadarray)) . ") AND
			contenttype = 'thread'
	");
*/

	// Update Moderator Log entries
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "moderatorlog
		SET threadid = $destthread[threadid]
		WHERE threadid IN(" . implode(',', array_keys($threadarray)) . ")
	");

	if ($vbulletin->GPC['redirect'] == 'expires')
	{
		switch($vbulletin->GPC['frame'])
		{
			case 'h':
				$expires = mktime(date('H') + $vbulletin->GPC['period'], date('i'), date('s'), date('m'), date('d'), date('y'));
				break;
			case 'd':
				$expires = mktime(date('H'), date('i'), date('s'), date('m'), date('d') + $vbulletin->GPC['period'], date('y'));
				break;
			case 'w':
				$expires = $vbulletin->GPC['period'] * 60 * 60 * 24 * 7 + TIMENOW;
				break;
			case 'y':
				$expires =  mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('y') + $vbulletin->GPC['period']);
				break;
			case 'm':
				default:
				$expires =  mktime(date('H'), date('i'), date('s'), date('m') + $vbulletin->GPC['period'], date('d'), date('y'));
		}
	}
	$redirectsql = array();


	// Remove source threads now
	foreach ($threadarray AS $threadid => $thread)
	{
		// Search index maintenance
		//this needs to happen before we nuke the old threads
		vb_Search_Indexcontroller_Queue::indexQueue('vBForum', 'Post', 'merge_group', $threadid , $destthread['threadid']);
		$foruminfo = fetch_foruminfo($thread['forumid']);
		$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
		$threadman->set_existing($thread);
		if ($vbulletin->GPC['redirect'] AND $vbulletin->GPC['redirect'] != 'none')
		{
			$threadman->set('open', 10);
			$threadman->set('pollid', $destthread['threadid']);
			$threadman->set('visible', 1);
			$threadman->set('dateline', TIMENOW);
			$threadman->save();
			if ($vbulletin->GPC['redirect'] == 'expires')
			{
				$redirectsql[] = "$thread[threadid], $expires";
			}
		}
		else
		{
			$threadman->delete($foruminfo['countposts'], true);
		}
		unset($threadman);
	}

	if (!empty($redirectsql))
	{
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "threadredirect
				(threadid, expires)
			VALUES
				(" . implode("), (", $redirectsql) . ")
		");
	}

	build_thread_counters($destthread['threadid']);
	foreach (array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	// Add log entries
	$threadinfo = array(
		'threadid'  => $destthread['threadid'],
		'forumid' => $destforuminfo['forumid'],
	);
	log_moderator_action($threadinfo, 'thread_merged_from_multiple_threads');

	if (empty($forumlist["$destforuminfo[forumid]"]))
	{
		build_forum_counters($destforuminfo['forumid']);
	}

	// Update canview status of thread subscriptions
	update_subscriptions(array('threadids' => array($destthread['threadid'])));

	// empty cookie
	if (!$vbulletin->GPC['skipclearlist'])
	{
		setcookie('vbulletin_inlinethread', '', TIMENOW - 3600, '/');
	}

	vB_ActivityStream_Populate_Forum_Thread::rebuild_thread($threadids);

	($hook = vBulletinHook::fetch_hook('inlinemod_domergethread')) ? eval($hook) : false;

	$vbulletin->url = fetch_seo_url('thread', $destthread);
	print_standard_redirect('redirect_inline_mergedthreads', true, $forceredirect);
}

// ############################### start delete posts ###############################
if ($_REQUEST['do'] == 'deleteposts' OR $_REQUEST['do'] == 'spampost')
{
	$show['removeposts'] = true;
	$show['deleteposts'] = true;
	$show['deleteoption'] = true;
	$checked = array('delete' => 'checked="checked"');

	$iplist = array();

	// Validate posts
	$posts = $db->query_read_slave("
		SELECT post.postid, post.threadid, post.visible, post.title, post.userid,
			thread.forumid, thread.title AS thread_title, thread.postuserid, thread.visible AS thread_visible, thread.firstpostid
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING (threadid)
		WHERE postid IN ($postids)
	");

	while ($post = $db->fetch_array($posts))
	{
		$forumperms = fetch_permissions($post['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $post['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($post['thread_visible'] == 2 AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if ($post['visible'] == 2)
		{
			if (!can_moderate($post['forumid'], 'candeleteposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
			}
			else if (!can_moderate($post['forumid'], 'canremoveposts'))
			{
				continue;
			}
		}
		else if (!can_moderate($post['forumid'], 'canremoveposts'))
		{
			if (!can_moderate($post['forumid'], 'candeleteposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
			}
			else if (!$show['deleteposts'])
			{
				eval(standard_error(fetch_error('you_do_not_share_delete_permission')));
			}
			else
			{
				$show['removeposts'] = false;
				$show['deleteoption'] = false;
			}
		}
		else if (
			!can_moderate($post['forumid'], 'candeleteposts')
			AND (
				$post['userid'] != $vbulletin->userinfo['userid']
				OR !($vbulletin->userinfo['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['candeletepost'])
			)
		)
		{
			if (!can_moderate($post['forumid'], 'canremoveposts'))
			{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
			}
			else if (!$show['removeposts'])
			{
				eval(standard_error(fetch_error('you_do_not_share_delete_permission')));
			}
			else
			{
				$checked = array('remove' => 'checked="checked"');
				$show['deleteposts'] = false;
				$show['deleteoption'] = false;
			}
		}

		$postarray["$post[postid]"] = true;
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;
		$iplist["$post[ipaddress]"] = true;

		if ($post['postid'] == $post['firstpostid'])
		{
			$show['firstpost'] = true;
		}
	}

	if (empty($postarray))
	{
		eval(standard_error(fetch_error('no_applicable_posts_selected')));
	}

	$postcount = count($postarray);
	$threadcount = count($threadlist);
	$forumcount = count($forumlist);

	if ($_REQUEST['do'] == 'spampost')
	{
		$users_result = $db->query_read("
			SELECT user.userid, user.username, user.joindate, user.posts, post.ipaddress, post.postid, thread.forumid
			FROM " . TABLE_PREFIX . "post AS post
			LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = post.threadid)
			INNER JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = post.userid)
			WHERE post.postid IN($postids)
			ORDER BY user.username
		");
		$user_cache = array();
		$ip_cache = array();
		while ($user = $db->fetch_array($users_result))
		{
			$user_cache["$user[userid]"] = $user;
			if ($vbulletin->options['logip'] == 2 OR ($vbulletin->options['logip'] == 1 AND can_moderate($user['forumid'], 'canviewips')))
			{
				$ip_cache["$user[ipaddress]"] = $user['postid'];
			}
		}
		$db->free_result($users_result);

		$usercount = 0;
		$users = array();
		foreach ($user_cache AS $user)
		{
			$usercount++;
			$user['comma'] = $vbphrase['comma_space'];
			$users[$usercount] = $user;
		}

		// Last element
		if ($usercount)
		{
			$users[$usercount]['comma'] = '';
		}

		$clc = 0;
		$ips = array();
		if ($vbulletin->options['logip'])	// already checked forum permission above
		{
			ksort($ip_cache);
			foreach ($ip_cache AS $ip => $postid)
			{
				if (empty($ip))
				{
					continue;
				}

				$clc++;
				$row['ip'] = $ip;
				$row['ip2long'] = ip2long($ip);
				$row['comma'] = $vbphrase['comma_space'];
				$row['forumurl'] = $vboptions['vbforum_url'] ? $vboptions['vbforum_url'].'/' : '' ;
				$ips[$clc] = $row;
			}

			// Last element
			if ($clc)
			{
				$ips[$clc]['comma'] = '';
			}
		}

		// make a list of usergroups into which to move this user
		$havebanned = false;
		foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
		{
			if (!($usergroup['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
			{
				$havebangroup = true;
				break;
			}
		}

		$show['ips'] = ($clc > 0);
		$show['users'] = ($usercount > 0);
		$show['punitive_action'] = ($havebangroup AND (($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) OR can_moderate(0, 'canbanusers'))) ? true : false;
		$show['akismet_option'] = !empty($vbulletin->options['vb_antispam_key']);
		$show['delete_others_option'] = can_moderate(-1, 'canmassprune');
		$show['removeitems'] = $show['removeposts'];
		$show['deleteitems'] = $show['deleteposts'];

		$headinclude .= vB_Template::create('threadadmin_easyspam_headinclude')->render();

		$navbits[''] = $vbphrase['delete_posts_as_spam'];

		$page_templater = vB_Template::create('threadadmin_easyspam');
		$page_templater->register('checked', $checked);
		$page_templater->register('forumcount', $forumcount);
		$page_templater->register('ips', $ips);
		$page_templater->register('postcount', $postcount);
		$page_templater->register('postids', $postids);
		$page_templater->register('postid_hiddenfields', $postid_hiddenfields);
		$page_templater->register('threadcount', $threadcount);
		$page_templater->register('threadids', $threadids);
		$page_templater->register('threadid_hiddenfields', $threadid_hiddenfields);
		$page_templater->register('threadinfo', $threadinfo);
		$page_templater->register('usercount', $usercount);
		$page_templater->register('users', $users);

		($hook = vBulletinHook::fetch_hook('inlinemod_spampost')) ? eval($hook) : false;
	}
	else
	{
		($hook = vBulletinHook::fetch_hook('inlinemod_deleteposts')) ? eval($hook) : false;

		// draw navbar
		$navbits = array();
		$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];
		$parentlist = array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3)));
		foreach ($parentlist AS $forumID)
		{
			$forumTitle = $vbulletin->forumcache["$forumID"]['title'];
			$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
		}

		$navbits[fetch_seo_url('thread', $threadinfo)] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];
		$navbits[''] = $vbphrase['delete_posts'];

		$page_templater = vB_Template::create('threadadmin_deleteposts');
		$page_templater->register('checked', $checked);
		$page_templater->register('forumcount', $forumcount);
		$page_templater->register('postcount', $postcount);
		$page_templater->register('postid', $postid);
		$page_templater->register('postids', $postids);
		$page_templater->register('threadcount', $threadcount);
		$page_templater->register('threadid', $threadid);
		$page_templater->register('threadinfo', $threadinfo);
	}
}

// ############################### start do delete posts ###############################
if ($_POST['do'] == 'dodeleteposts')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deletetype'      => TYPE_UINT,	// 1 = soft delete post, 2 = physically remove.
		'keepattachments' => TYPE_BOOL,
		'deletereason'    => TYPE_STR
	));

	$physicaldel = iif($vbulletin->GPC['deletetype'] == 1, false, true);

	// Validate posts
	$posts = $db->query_read_slave("
		SELECT post.postid, post.threadid, post.parentid, post.visible, post.title, post.userid AS posteruserid,
			thread.forumid, thread.title AS threadtitle, thread.postuserid, thread.firstpostid, thread.visible AS thread_visible
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING (threadid)
		WHERE postid IN (" . implode(',', $postids) . ")
		ORDER BY postid
	");

	$deletethreads = array();
	$firstpost = array();
	while ($post = $db->fetch_array($posts))
	{
		$forumperms = fetch_permissions($post['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $post['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['threadtitle'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if (!can_moderate($post['forumid'], 'canremoveposts') AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $post['title'], $post['threadtitle'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}

		if (!can_moderate($post['forumid'], 'canremoveposts') AND $physicaldel)
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $post['title'], $post['threadtitle'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if (
			!physicaldel
			AND (
				!can_moderate($post['forumid'], 'candeleteposts')
				AND (
					$post['posteruserid'] != $vbulletin->userinfo['userid']
					OR !($vbulletin->userinfo['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['candeletepost'])
				)
			)
		)
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_delete_threads_and_posts', $post['title'], $post['threadtitle'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}

		$postarray["$post[postid]"] = $post;
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;

		if ($post['firstpostid'] == $post['postid'])
		{	// deleting a thread so do not decremement the counters of any other posts in this thread
			$firstpost["$post[threadid]"] = true;
		}
		else if (!empty($firstpost["$post[threadid]"]))
		{
			$postarray["$post[postid]"]['skippostcount'] = true;
		}

		if ($node = get_nodeFromThreadid($post['threadid']))
		{
			// Expire any CMS comments cache entries.
			$expire_cache = array('cms_comments_change');
			$expire_cache[] = 'cms_comments_add_' . $node;
			$expire_cache[] = 'cms_comments_change_' . $post['threadid'];

			vB_Cache::instance()->eventPurge($expire_cache);
			vB_Cache::instance()->cleanNow();
		}
	}

	if (empty($postarray))
	{
		eval(standard_error(fetch_error('no_applicable_posts_selected')));
	}

	$firstpost = false;
	$gotothread = true;
	foreach ($postarray AS $postid => $post)
	{
		$foruminfo = fetch_foruminfo($post['forumid']);

		$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$postman->set_existing($post);
		$postman->delete(($foruminfo['countposts'] AND !$post['skippostcount']), $post['threadid'], $physicaldel, array(
			'userid'          => $vbulletin->userinfo['userid'],
			'username'        => $vbulletin->userinfo['username'],
			'reason'          => $vbulletin->GPC['deletereason'],
			'keepattachments' => $vbulletin->GPC['keepattachments']
		));
		unset($postman);

		// Search index maintenance
		if ($physicaldel)
		{
			vb_Search_Indexcontroller_Queue::indexQueue('vBForum', 'Post', 'delete', $postid);
		}

		if ($vbulletin->GPC['threadid'] == $post['threadid'] AND $post['postid'] == $post['firstpostid'])
		{	// we've deleted the thread that we activated this action from so we can only return to the forum
			$gotothread = false;
		}
		else if ($post['postid'] == $postinfo['postid'] AND $physicaldel)
		{	// we came in via a post, which we have deleted so we have to go back to the thread
			$vbulletin->url = fetch_seo_url('thread', $post, null, 'threadid', 'threadtitle');
		}
	}

	foreach(array_keys($threadlist) AS $threadid)
	{
		build_thread_counters($threadid);
	}

	foreach(array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	// empty cookie
	setcookie('vbulletin_inlinepost', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_dodeleteposts')) ? eval($hook) : false;

	if ($gotothread)
	{
		// Actually let's do nothing and redirect to where we were
	}
	else if ($vbulletin->GPC['forumid'])
	{	// redirect to the forum that we activated from since we hard deleted the thread
		$vbulletin->url = fetch_seo_url('forum', $foruminfo);
	}
	else
	{
		// this really shouldn't happen...
		$vbulletin->url = fetch_seo_url('forumhome', array());
	}
	print_standard_redirect('redirect_inline_deletedposts', true, $forceredirect);
}

// ############################### start do delete posts ###############################
if ($_POST['do'] == 'undeleteposts')
{

	// Validate posts
	$posts = $db->query_read_slave("
		SELECT post.postid, post.threadid, post.parentid, post.visible, post.title, post.userid,
			thread.forumid, thread.title AS thread_title, thread.postuserid, thread.firstpostid, thread.visible AS thread_visible,
			forum.options AS forum_options
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING (threadid)
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum USING (forumid)
		WHERE postid IN ($postids)
			AND (post.visible = 2 OR (post.visible = 1 AND thread.visible = 2 AND post.postid = thread.firstpostid))
		ORDER BY postid
	");

	$deletethreads = array();

	while ($post = $db->fetch_array($posts))
	{
		$forumperms = fetch_permissions($post['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $post['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}

		$postarray["$post[postid]"] = $post;
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;

		if ($post['firstpostid'] == $post['postid'])
		{	// undeleting a thread so need to update the $tinfo for any other posts in this thread
			$firstpost["$post[threadid]"] = true;
		}
		else if (!empty($firstpost["$post[threadid]"]))
		{
			$postarray["$post[postid]"]['thread_visible'] = 1;
		}
	}

	foreach ($postarray AS $postid => $post)
	{
		$tinfo = array(
			'threadid'    => $post['threadid'],
			'forumid'     => $post['forumid'],
			'visible'     => $post['thread_visible'],
			'firstpostid' => $post['firstpostid']
		);
		undelete_post($post['postid'], $post['forum_options'] & $vbulletin->bf_misc_forumoptions['countposts'], $post, $tinfo, false);
	}

	foreach (array_keys($threadlist) AS $threadid)
	{
		build_thread_counters($threadid);
	}

	foreach (array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	// empty cookie
	setcookie('vbulletin_inlinepost', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_undeleteposts')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_undeleteposts', true, $forceredirect);
}

// ############################### start do approve attachments ###############################
if ($_POST['do'] == 'approveattachments' OR $_POST['do'] == 'unapproveattachments')
{
	// validate posts
	$posts = $db->query_read_slave("
		SELECT post.postid, post.threadid, post.visible, post.title, post.userid,
			thread.forumid, thread.title AS thread_title, thread.postuserid, thread.visible AS thread_visible,
			thread.firstpostid
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING (threadid)
		WHERE postid IN ($postids)
	");

	while ($post = $db->fetch_array($posts))
	{
		$forumperms = fetch_permissions($post['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $post['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		if ((!$post['thread_visible'] OR !$post['visible']) AND !can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if (($post['thread_visible'] == 2 OR $post['visible'] == 2) AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if (!can_moderate($post['forumid'], 'canmoderateattachments'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_attachments', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}

		$postarray["$post[postid]"] = $post;
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;
	}

	if (empty($postarray))
	{
		eval(standard_error(fetch_error('no_applicable_posts_selected')));
	}

	$types = vB_Types::instance();
	$contenttypeid = $types->getContentTypeID('vBForum_Post');

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "attachment
		SET state = '" . ($_POST['do'] == 'approveattachments' ? 'visible' : 'moderation') . "'
		WHERE
			contentid IN (" . implode(',', array_keys($postarray)) . ")
				AND
			contenttypeid = $contenttypeid
	");

	// empty cookie
	setcookie('vbulletin_inlinepost', '', TIMENOW - 3600, '/');

	if ($_POST['do'] == 'approveattachments')
	{
		($hook = vBulletinHook::fetch_hook('inlinemod_approveattachments')) ? eval($hook) : false;
		print_standard_redirect('redirect_inline_approvedattachments', true, $forceredirect);
	}
	else
	{
		($hook = vBulletinHook::fetch_hook('inlinemod_unapproveattachments')) ? eval($hook) : false;
		print_standard_redirect('redirect_inline_unapprovedattachments', true, $forceredirect);
	}
}

// ############################### start do approve posts ###############################
if ($_POST['do'] == 'approveposts')
{
	// Validate posts
	$posts = $db->query_read_slave("
		SELECT post.postid, post.threadid, post.visible, post.title, post.userid, post.dateline,
			thread.forumid, thread.title AS thread_title, thread.postuserid, thread.visible AS thread_visible,
			thread.firstpostid,
			user.usergroupid, user.displaygroupid, user.membergroupids, user.posts, usertextfield.rank # for rank updates
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = post.threadid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (post.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON (post.userid = usertextfield.userid)
		WHERE postid IN ($postids)
			AND (post.visible = 0 OR (post.visible = 1 AND thread.visible = 0 AND post.postid = thread.firstpostid))
		ORDER BY postid
	");

	while ($post = $db->fetch_array($posts))
	{
		$forumperms = fetch_permissions($post['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $post['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		if (!can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if ($post['thread_visible'] == 2 AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}

		$postarray["$post[postid]"] = $post;
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;

		if ($post['firstpostid'] == $post['postid'])
		{	// approving a thread so need to update the $tinfo for any other posts in this thread
			$firstpost["$post[threadid]"] = true;
		}
		else if (!empty($firstpost["$post[threadid]"]))
		{
			$postarray["$post[postid]"]['thread_visible'] = 1;
		}
	}

	if (empty($postarray))
	{
		eval(standard_error(fetch_error('no_applicable_posts_selected')));
	}

	foreach ($postarray AS $postid => $post)
	{
		$tinfo = array(
			'threadid'    => $post['threadid'],
			'forumid'     => $post['forumid'],
			'visible'     => $post['thread_visible'],
			'firstpostid' => $post['firstpostid']
		);

		$foruminfo = fetch_foruminfo($post['forumid']);
		approve_post($postid, $foruminfo['countposts'], true, $post, $tinfo, false);
	}

	foreach (array_keys($threadlist) AS $threadid)
	{
		build_thread_counters($threadid);
	}
	foreach (array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	// empty cookie
	setcookie('vbulletin_inlinepost', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_approveposts')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_approvedposts', true, $forceredirect);
}

// ############################### start do unapprove posts ###############################
if ($_POST['do'] == 'unapproveposts')
{

	// Validate posts
	$posts = $db->query_read_slave("
		SELECT post.postid, post.threadid, post.visible, post.title, post.userid,
			thread.forumid, thread.title AS thread_title, thread.postuserid, thread.visible AS thread_visible,
			thread.firstpostid,
			user.usergroupid, user.displaygroupid, user.membergroupids, user.posts, usertextfield.rank # for rank updates
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = post.threadid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (post.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON (post.userid = usertextfield.userid)
		WHERE postid IN ($postids)
			AND (post.visible > 0 OR (post.visible = 1 AND thread.visible > 0 AND post.postid = thread.firstpostid))
	");

	$firstpost = array();
	while ($post = $db->fetch_array($posts))
	{
		$forumperms = fetch_permissions($post['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $post['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		if (!can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}

		$postarray["$post[postid]"] = $post;
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;
		if ($post['firstpostid'] == $post['postid'] AND $post['thread_visible'] == 1)
		{	// unapproving a thread so do not decremement the counters of any other posts in this thread
			$firstpost["$post[threadid]"] = true;
		}
		else if (!empty($firstpost["$post[threadid]"]))
		{
			$postarray["$post[postid]"]['skippostcount'] = true;
		}
	}

	if (empty($postarray))
	{
		eval(standard_error(fetch_error('no_applicable_posts_selected')));
	}

	foreach ($postarray AS $postid => $post)
	{
		$foruminfo = fetch_foruminfo($post['forumid']);
		$tinfo = array(
			'threadid'    => $post['threadid'],
			'forumid'     => $post['forumid'],
			'visible'     => $post['thread_visible'],
			'firstpostid' => $post['firstpostid']
		);
		// Can't send $thread without considering that thread_visible may change if we approve the first post of a thread
		unapprove_post($postid, ($foruminfo['countposts'] AND !$post['skippostcount']), true, $post, $tinfo, false);
	}

	foreach (array_keys($threadlist) AS $threadid)
	{
		build_thread_counters($threadid);
	}

	foreach (array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	// empty cookie
	setcookie('vbulletin_inlinepost', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_unapproveposts')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_unapprovedposts', true, $forceredirect);
}

// ############################### start do merge posts ###############################
if ($_POST['do'] == 'domergeposts')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'username'       => TYPE_NOHTML,
		'postid'         => TYPE_UINT,
		'title'          => TYPE_STR,
		'reason'         => TYPE_NOHTML,
		'wysiwyg'  		   => TYPE_BOOL,
		'message'        => TYPE_STR,
		'parseurl'       => TYPE_BOOL,
		'signature'      => TYPE_BOOL,
		'disablesmilies' => TYPE_BOOL,
	));

	// ### PREP INPUT ###
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$edit['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $foruminfo['allowhtml']);
	}
	else
	{
		$edit['message'] =& $vbulletin->GPC['message'];
	}

	preg_match('#^(\d+)\|(.+)$#', $vbulletin->GPC['username'], $matches);
	$userid = intval($matches[1]);
	$username = $matches[2];

	$attachtotal = 0;
	$destpost = array();

	$validname = false;
	$validdate = false;
	$new_ip = false;

	// Validate posts
	$posts = $db->query_read_slave("
		SELECT post.*,
			thread.threadid, thread.forumid, thread.title AS threadtitle, thread.postuserid, thread.visible AS thread_visible, thread.firstpostid,
			infraction.infractionid
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (post.threadid = thread.threadid)
		LEFT JOIN " . TABLE_PREFIX . "infraction AS infraction ON (post.postid = infraction.postid)
		WHERE post.postid IN (" . implode(',', $postids) . " )
		ORDER BY post.dateline
	");

	while ($post = $db->fetch_array($posts))
	{
		$forumperms = fetch_permissions($post['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $post['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		if (!can_moderate($post['forumid'], 'canmanagethreads'))
		{
				eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_threads_and_posts', $post['title'], $post['threadtitle'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['threadtitle'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}

		if ($post['username'] == $username AND $post['userid'] == $userid)
		{
			$validname = true;
			if ($new_ip === false)
			{
				// update IP to one of the IPs used by the person who will own the new post
				$new_ip = $post['ipaddress'];
			}
		}

		$attachtotal += $post['attach'];

		$postarray["$post[postid]"] = $post;
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;

		if ($post['postid'] == $vbulletin->GPC['postid'])
		{
			$destpost = $post;
		}
	}

	if (empty($postarray))
	{
		eval(standard_error(fetch_error('no_applicable_posts_selected')));
	}
	else if (count($postarray) == 1)
	{
		eval(standard_error(fetch_error('not_much_would_be_accomplished_by_merging')));
	}
	else if (empty($destpost))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
	}

	if (!$validname)
	{
		$userid = $destpost['userid'];
		$username = $destpost['username'];
		$new_ip = $destpost['ipaddress'];
	}

	if (!$userid AND $attachtotal)
	{
		eval(standard_error(fetch_error('guest_posts_may_not_contain_attachments')));
	}

	$edit['parseurl'] = ($vbulletin->GPC['parseurl'] AND $vbulletin->forumcache["$destpost[forumid]"]['allowbbcode']);
	$edit['disablesmilies'] =& $vbulletin->GPC['disablesmilies'];
	$edit['enablesmilies'] = $edit['allowsmilie'] = ($edit['disablesmilies']) ? 0 : 1;
	$edit['reason'] = fetch_censored_text($vbulletin->GPC['reason']);

	if ($destpost['threadid'] != $threadinfo['threadid'])
	{	// retrieve threadinfo for the owner of the first post
		$threadinfo = fetch_threadinfo($destpost['threadid']);
		$foruminfo = fetch_foruminfo($threadinfo['forumid']);
	}

	if ($destpost['postid'] == $threadinfo['firstpostid'] AND !($vbulletin->GPC['title']))
	{
		$edit['title'] = unhtmlspecialchars($threadinfo['title']);
	}
	else
	{
		$edit['title'] = $vbulletin->GPC['title'];
	}

	// Update First Post
	$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
	$postman->set_existing($destpost);
	$postman->set_info('parseurl', $edit['parseurl']);
	$postman->set('pagetext', $edit['message']);
	$postman->set('userid', $userid, true, false); // Bypass verify
	$postman->set('username', $username, true, false); // Bypass verify
	$postman->set('dateline', $destpost['dateline']);
	$postman->set('attach', $attachtotal);
	$postman->set('title', $edit['title']);
	$postman->set('allowsmilie', $edit['enablesmilies']);
	$postman->set('ipaddress', $new_ip);

	($hook = vBulletinHook::fetch_hook('inlinemod_domergeposts_process')) ? eval($hook) : false;

	$postman->pre_save();

	if ($postman->errors)
	{
		$errors = $postman->errors;
	}

	if (sizeof($errors) > 0)
	{
		unset($postman);
		// ### POST HAS ERRORS ###
		$errorreview = construct_errors($errors);
		construct_checkboxes($edit);
		$previewpost = true;
		$postids = implode(',', $postids);
		$_REQUEST['do'] = 'mergeposts';
	}
	else
	{
		$updateinfraction = array();
		if ($userid == $destpost['userid'])
		{
			$founddest = !empty($destpost['infractionid']) ? true : false;
			// Remove destpost from the postarray so as to not move its infraction
			unset($postarray["$destpost[postid]"]);
		}
		else
		{
			$founddest = false;
		}

		$infractions = $db->query_read_slave("
			SELECT infractionid, userid, points
			FROM " . TABLE_PREFIX . "infraction
			WHERE postid IN (" . implode(',', array_keys($postarray)) . ")
			ORDER BY action
		");
		while ($infraction = $db->fetch_array($infractions))
		{
			if ($infraction['userid'] == $userid AND !$founddest)
			{
				$founddest = true;
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "infraction
					SET postid = $destpost[postid]
					WHERE infractionid = $infraction[infractionid]
				");
				$postman->set('infraction', ($infraction['points'] ? 2 : 1), true, false); // Bypass verify
			}
			else
			{
				$updateinfraction[] = $infraction['infractionid'];
			}
		}

		if (!$founddest)
		{
			$postman->set('infraction', 0, true, false); // Bypass verify
		}

		if (!empty($updateinfraction))
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "infraction
				SET postid = 0
				WHERE infractionid IN (" . implode(',', $updateinfraction) . ")
			");
		}

		$postman->save();
		unset($postman);

		// Update Attachments to point to new owner
		if ($attachtotal)
		{
			$types = vB_Types::instance();
			$contenttypeid = $types->getContentTypeID('vBForum_Post');

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "attachment
				SET
					contentid = " . intval($destpost['postid']) . ",
					userid = " . intval($userid) . "
				WHERE
					contentid IN (" . implode(",", array_keys($postarray)) . ",$destpost[postid])
						AND
					contenttypeid = $contenttypeid
			");
		}

		if ($userid != $destpost['userid'] AND $threadinfo['visible'] == 1 AND $destpost['visible'] == 1 AND $foruminfo['countposts'])
		{
			if ($userid)
			{	// need to give this a user a post for now owning the merged post
				$user =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
				$userinfo = array('userid' => $userid);
				$user->set_existing($userinfo);
				$user->set('posts', 'posts + 1', false);
				$user->set_ladder_usertitle_relative(1);
				$user->save();
				unset($user);
			}

			if ($destpost['userid'])
			{	// need to take a post from this user since they no longer own the merged post
				$user =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
				$userinfo = array('userid' => $destpost['userid']);
				$user->set_existing($userinfo);
				$user->set('posts', 'IF(posts > 1, posts - 1, 0)', false);
				$user->set_ladder_usertitle_relative(-1);
				$user->save();
				unset($user);
			}

		}

		// Search index maintenance
		vb_Search_Indexcontroller_Queue::indexQueue('vBForum', 'Post', 'index', $destpost['postid']);
		// Make sure destpost is now gone so as to not delete it!
		unset($postarray["$destpost[postid]"]);
		$deletedthreads = array();

		// Delete Posts that are not the firstpost in a thread
		foreach($postarray AS $postid => $post)
		{
			// Search index maintenance
			vb_Search_Indexcontroller_Queue::indexQueue('vBForum', 'Post', 'delete', $postid);
			if (!empty($deletedthreads["$post[threadid]"]))
			{	// we already deleted the firstpost of this thread and hence all of its posts so no need to do anything else with this post
				continue;
			}

			$foruminfo = fetch_foruminfo($post['forumid']);

			if ($post['postid'] == $post['firstpostid'])
			{	// this is a firstpost so check if we can delete this thread or we need to give the thread a new firstpost before we call delete

				if ($getfirstpost = $db->query_first("
					SELECT postid
					FROM " . TABLE_PREFIX . "post
					WHERE threadid = $post[threadid]
						AND postid NOT IN (" . implode(',', array_keys($postarray)) . ")
					ORDER BY dateline
					LIMIT 1
				"))
				{

					$db->query_write("
						UPDATE " . TABLE_PREFIX . "thread
						SET firstpostid = $getfirstpost[postid]
						WHERE threadid = $post[threadid]
					");

					$post['firstpostid'] = $getfirstpost['postid'];
					// Also update the threadcache
					$threadcache["$post[threadid]"]['firstpostid'] = $getfirstpost['postid'];
				}
				else
				{ // there are no posts left or we plan to delete them all so mark this thread as deleted now
					$deletedthreads["$post[threadid]"] = true;
				}
			}

			$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_SILENT, 'threadpost');
			$postman->set_info('skip_moderator_log', true);
			$postman->set_existing($post);
			$postman->delete($foruminfo['countposts'], $post['threadid'], true, NULL, false);
			unset($postman);
		}

		$reason = fetch_censored_text($vbulletin->GPC['reason']);

		// Delete user's previous edit if we don't save edits for this group and they didn't give a reason
		if (!$edit['reason'] AND !($permissions['genericoptions'] & $vbulletin->bf_ugp_genericoptions['showeditedby']))
		{
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "editlog
				WHERE postid = $destpost[postid]
			");
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "postedithistory
				WHERE postid = $destpost[postid]
			");
		}
		else if ((($permissions['genericoptions'] & $vbulletin->bf_ugp_genericoptions['showeditedby']) AND $destpost['dateline'] < (TIMENOW - ($vbulletin->options['noeditedbytime'] * 60))) OR !empty($edit['reason']))
		{
			if ($vbulletin->options['postedithistory'])
			{
				// insert original post on first edit
				if (!$db->query_first("SELECT postedithistoryid FROM " . TABLE_PREFIX . "postedithistory WHERE original = 1 AND postid = " . $destpost['postid']))
				{
					$db->query_write("
						INSERT INTO " . TABLE_PREFIX . "postedithistory
							(postid, userid, username, title, iconid, dateline, reason, original, pagetext)
						VALUES
							($destpost[postid],
							" . $destpost['userid'] . ",
							'" . $db->escape_string($destpost['username']) . "',
							'" . $db->escape_string($destpost['title']) . "',
							$destpost[iconid],
							" . $destpost['dateline'] . ",
							'',
							1,
							'" . $db->escape_string($destpost['pagetext']) . "')
					");
				}

				// insert the new version
				$db->query_write("
					INSERT INTO " . TABLE_PREFIX . "postedithistory
						(postid, userid, username, title, iconid, dateline, reason, pagetext)
					VALUES
						($destpost[postid],
						" . $vbulletin->userinfo['userid'] . ",
						'" . $db->escape_string($vbulletin->userinfo['username']) . "',
						'" . $db->escape_string($edit['title']) . "',
						$destpost[iconid],
						" . TIMENOW . ",
						'" . $db->escape_string($edit['reason']) . "',
						'" . $db->escape_string($edit['message']) . "')
				");
			}

			/*insert query*/
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "editlog
					(postid, userid, username, dateline, reason, hashistory)
				VALUES
					($destpost[postid],
					" . $vbulletin->userinfo['userid'] . ",
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($edit['reason']) . "',
					" . ($vbulletin->options['postedithistory'] ? 1 : 0) . ")
			");
		}

		// Need to update thread
		if ($destpost['postid'] == $threadinfo['firstpostid'] AND $vbulletin->GPC['title'] != '' AND ($destpost['dateline'] + $vbulletin->options['editthreadtitlelimit'] * 60) > TIMENOW)
		{
			$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
			$threadman->set_existing($threadinfo);
			$threadman->set_info('skip_first_post_update', true);
			$threadman->set('title', $vbulletin->GPC['title']);
			$threadman->save();
		}

		$threadinfo['postid'] = $destpost['postid'];
		log_moderator_action($threadinfo, 'post_merged_from_multiple_posts');

		foreach(array_keys($threadlist) AS $threadid)
		{
			build_thread_counters($threadid);
		}

		foreach(array_keys($forumlist) AS $forumid)
		{
			build_forum_counters($forumid);
		}

		vB_ActivityStream_Populate_Forum_Thread::rebuild_thread(array_keys($threadlist));

		// empty cookie
		setcookie('vbulletin_inlinepost', '', TIMENOW - 3600, '/');

		($hook = vBulletinHook::fetch_hook('inlinemod_domergeposts_complete')) ? eval($hook) : false;

		$vbulletin->url = fetch_seo_url('thread', $destpost, array('p' => $destpost['postid']), null, 'threadid', 'threadtitle') . "#post$destpost[postid]";
		print_standard_redirect('redirect_inline_mergedposts', true, $forceredirect);
	}
}

// ############################### start merge posts ###############################
if ($_REQUEST['do'] == 'mergeposts')
{

	if ($previewpost)
	{
		$checked['parseurl'] = ($edit['parseurl']) ? 'checked="checked"' : '';
		$checked['disablesmilies'] = ($edit['disablesmilies']) ? 'checked="checked"' : '';
	}
	else
	{
		$checked['parseurl'] = 'checked="checked"';
	}

	$userselect = array();
	$postselect = array();
	$pagetext = '';

	// Validate posts
	$posts = $db->query_read_slave("
		SELECT post.postid, post.threadid, post.visible, post.title, post.username, post.dateline, post.pagetext,
			post.userid, thread.forumid, thread.title AS thread_title, thread.postuserid, thread.visible AS thread_visible
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING (threadid)
		WHERE postid IN ($postids)
		ORDER BY post.dateline
	");

	$counter = 1;
	while ($post = $db->fetch_array($posts))
	{
		$forumperms = fetch_permissions($post['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $post['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		if (!can_moderate($post['forumid'], 'canmanagethreads'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}

		$userselect["$post[userid]|$post[username]"] = $post['username']; // Allow guest usernames so key off username
		$postselect["$post[postid]"] = construct_phrase($vbphrase['x_y_by_z'], $counter, vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'], $post['dateline']), $post['username']);

		if (empty($titlebit))
		{
			$titlebit = $post['thread_title'];
		}

		$js_titles .= "threadtitles[$post[postid]] = '" . addslashes_js($post['thread_title']) . "';\n";

		($hook = vBulletinHook::fetch_hook('inlinemod_mergeposts_post')) ? eval($hook) : false;

		$pagetext .= (!empty($pagetext) ? "\n\n" : "") . $post['pagetext'];

		$postarray["$post[postid]"] = true;
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;
		$counter++;
	}

	if (empty($postarray))
	{
		eval(standard_error(fetch_error('no_applicable_posts_selected')));
	}
	else if (count($postarray) == 1)
	{
		eval(standard_error(fetch_error('not_much_would_be_accomplished_by_merging')));
	}

	$postcount = count($postarray);
	$threadcount = count($threadlist);
	$forumcount = count($forumlist);

	if ($previewpost)
	{
		$pagetext = htmlspecialchars_uni($edit['message']);
	}
	else
	{
		$pagetext = htmlspecialchars_uni($pagetext);
	}
	$editorid = construct_edit_toolbar(
		$pagetext,
		0,
		$foruminfo['forumid'],
		$foruminfo['allowsmilies'] ? 1 : 0,
		true,
		false,
		'fe',
		'',
		array(),
		'content',
		'',
		0,
		0,
		false,
		true,
		'title'
	);

	$usernamebit = '';
	if (count($userselect) > 1)
	{
		$guests = array();
		uasort($userselect, 'strnatcasecmp'); // alphabetically sort usernames
		foreach ($userselect AS $optionvalue => $optiontitle)
		{
			preg_match('#^(\d+)\|(.+)$#', $optionvalue, $matches);
			if (!intval($matches[1]))
			{
				$guests[] = $optiontitle;
			}
			else
			{
				$optionselected = ($optionvalue == "$userid|$username") ? "selected='selected'" : "";
				$usernamebit .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}
		}

		if (!empty($guests))
		{
			$usernamebit .= "<optgroup label=\"$vbphrase[guests]\">\n";
			foreach ($guests AS $optiontitle)
			{
				$optionvalue = "0|$username";
				$optionselected = ($optionvalue == "$userid|$username") ? "selected='selected'" : "";
				$usernamebit .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}
			$usernamebit .= "</optgroup>\n";
		}
		$show['userchoice'] = true;
	}

	$postlistbit = '';

	foreach ($postselect AS $optionvalue => $optiontitle)
	{
		$optionselected = ($optionvalue == $vbulletin->GPC['postid']) ? "selected='selected'" : "";
		$postlistbit .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
	}


	($hook = vBulletinHook::fetch_hook('inlinemod_mergeposts_complete')) ? eval($hook) : false;

	// draw navbar
	$navbits = array();
	$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];
	$parentlist = array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3)));
	foreach ($parentlist AS $forumID)
	{
		$forumTitle = $vbulletin->forumcache["$forumID"]['title'];
		$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
	}

	$navbits[fetch_seo_url('thread', $threadinfo)] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];
	$navbits[''] = $vbphrase['merge_posts'];

	$page_templater = vB_Template::create('threadadmin_mergeposts');
	$page_templater->register('checked', $checked);
	$page_templater->register('disablesmiliesoption', $disablesmiliesoption);
	$page_templater->register('edit', $edit);
	$page_templater->register('editorid', $editorid);
	$page_templater->register('errorreview', $errorreview);
	$page_templater->register('forumcount', $forumcount);
	$page_templater->register('js_titles', $js_titles);
	$page_templater->register('messagearea', $messagearea);
	$page_templater->register('postcount', $postcount);
	$page_templater->register('postids', $postids);
	$page_templater->register('postlistbit', $postlistbit);
	$page_templater->register('threadcount', $threadcount);
	$page_templater->register('threadid', $threadid);
	$page_templater->register('titlebit', $titlebit);
	$page_templater->register('usernamebit', $usernamebit);
}

// ############################### start move posts ###############################
if ($_REQUEST['do'] == 'moveposts' OR $_REQUEST['do'] == 'copyposts')
{
	// Validate posts
	$posts = $db->query_read_slave("
		SELECT post.postid, post.threadid, post.visible, post.title,
			thread.forumid, thread.title AS thread_title, thread.postuserid, thread.visible AS thread_visible
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING (threadid)
		WHERE postid IN ($postids)
		ORDER BY post.dateline
	");

	while ($post = $db->fetch_array($posts))
	{
		$forumperms = fetch_permissions($post['forumid']);
		if 	(
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
				OR
			(!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND $post['postuserid'] != $vbulletin->userinfo['userid'])
			)
		{
			print_no_permission();
		}

		if (!can_moderate($post['forumid'], 'canmanagethreads'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}

		$postarray["$post[postid]"] = $post;
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;
	}

	if (empty($postarray))
	{
		eval(standard_error(fetch_error('no_applicable_posts_selected')));
	}

	$postcount = count($postarray);
	$threadcount = count($threadlist);
	$forumcount = count($forumlist);

	if ($postcount == 1)
	{
		$post = array_pop($postarray);
	}
	else
	{
		$post = array('title' => '');
	}

	$curforumid = $foruminfo['forumid'];
	$moveoptions = construct_move_forums_options();

	$option_templater = vB_Template::create('option');
	$option_templater->register('options', $moveoptions);
	$moveforumbits = $option_templater->render();

	if ($_REQUEST['do'] == 'moveposts')
	{
		$navbits_phrase = $vbphrase['move_posts'];

		$page_templater = vB_Template::create('threadadmin_moveposts');
		$page_templater->register('forumcount', $forumcount);
		$page_templater->register('moveforumbits', $moveforumbits);
		$page_templater->register('post', $post);
		$page_templater->register('postcount', $postcount);
		$page_templater->register('postids', $postids);
		$page_templater->register('threadcount', $threadcount);
		$page_templater->register('threadid', $threadid);

		($hook = vBulletinHook::fetch_hook('inlinemod_moveposts')) ? eval($hook) : false;
	}
	else
	{
		$navbits_phrase = $vbphrase['copy_posts'];

		$page_templater = vB_Template::create('threadadmin_copyposts');
		$page_templater->register('forumcount', $forumcount);
		$page_templater->register('moveforumbits', $moveforumbits);
		$page_templater->register('post', $post);
		$page_templater->register('postcount', $postcount);
		$page_templater->register('postids', $postids);
		$page_templater->register('threadcount', $threadcount);
		$page_templater->register('threadid', $threadid);

		($hook = vBulletinHook::fetch_hook('inlinemod_copyposts')) ? eval($hook) : false;
	}

	// draw navbar
	$navbits = array();
	$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];
	$parentlist = array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3)));
	foreach ($parentlist AS $forumID)
	{
		$forumTitle = $vbulletin->forumcache["$forumID"]['title'];
		$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
	}

	$navbits[fetch_seo_url('thread', $threadinfo)] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];
	$navbits[''] = $navbits_phrase;
}

// ############################### start do move posts ###############################
if ($_POST['do'] == 'domoveposts')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'type'           => TYPE_UINT,
		'title'          => TYPE_NOHTML,
		'destforumid'    => TYPE_UINT,
		'mergethreadurl' => TYPE_STR
	));

	if ($vbulletin->GPC['type'] == 0)
	{	// Move to new thread
		if (empty($vbulletin->GPC['title']))
		{
			eval(standard_error(fetch_error('notitle')));
		}

		// check whether dest can contain posts
		$destforumid = verify_id('forum', $vbulletin->GPC['destforumid']);
		$destforuminfo = fetch_foruminfo($destforumid);
		if (!$destforuminfo['cancontainthreads'] OR $destforuminfo['link'])
		{
			eval(standard_error(fetch_error('moveillegalforum')));
		}

		// check destination forum permissions
		$forumperms = fetch_permissions($destforuminfo['forumid']);
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
		{
			print_no_permission();
		}
	}
	else
	{
		// Validate destination thread
		$destthreadid = extract_threadid_from_url($vbulletin->GPC['mergethreadurl']);
		if (!$destthreadid)
		{
			// Invalid URL
			eval(standard_error(fetch_error('mergebadurl')));
		}

		$destthreadid = verify_id('thread', $destthreadid);
		$destthreadinfo = fetch_threadinfo($destthreadid);
		$destforuminfo = fetch_foruminfo($destthreadinfo['forumid']);

		$forumperms = fetch_permissions($destforuminfo['forumid']);
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
		{
			print_no_permission();
		}

		if ($destthreadinfo['open'] == 10)
		{
			if (can_moderate($destthreadinfo['forumid']))
			{
				eval(standard_error(fetch_error('mergebadurl')));
			}
			else
			{
				eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
			}
		}

		if (($destthreadinfo['isdeleted'] AND !can_moderate($destthreadinfo['forumid'], 'candeleteposts')) OR (!$destthreadinfo['visible'] AND !can_moderate($destthreadinfo['forumid'], 'canmoderateposts')))
		{
			if (can_moderate($destthreadinfo['forumid']))
			{
				print_no_permission();
			}
			else
			{
				eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
			}
		}

		// allow merging only in forums this user can moderate - otherwise, they
		// have a good vector for faking posts in other forums, etc
		if (!can_moderate($destthreadinfo['forumid']))
		{
			eval(standard_error(fetch_error('move_posts_moderated_forums_only')));
		}
	}

	$firstpost = array();
	$userbyuserid = array();
	$unique_thread_user = array();

	$posts = $db->query_read_slave("
		SELECT post.postid, post.threadid, post.visible, post.title, post.username, post.dateline, post.parentid, post.userid,
			thread.forumid, thread.title AS thread_title, thread.postuserid, thread.visible AS thread_visible, thread.firstpostid,
			thread.sticky, thread.open, thread.iconid,
			IF(subscribethread.emailupdate IS NULL, 0, 1) AS issubscribed, user.autosubscribe
		FROM " . TABLE_PREFIX . "post AS post
		INNER JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = post.threadid)
		LEFT JOIN " . TABLE_PREFIX . "subscribethread AS subscribethread ON (subscribethread.threadid = thread.threadid AND subscribethread.userid = post.userid AND subscribethread.canview = 1)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (post.userid = user.userid)
		WHERE postid IN (" . implode(',', $postids) . ")
		ORDER BY post.dateline

	");
	while ($post = $db->fetch_array($posts))
	{

		if (!can_moderate($post['forumid'], 'canmanagethreads'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($destforuminfo['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts_in_destination_forum')));
		}
		else if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($destforuminfo['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts_in_destination_forum')));
		}

		// Ignore posts that are already in the destination thread
		if ($post['threadid'] == $destthreadinfo['threadid'])
		{
			continue;
		}

		$postarray["$post[postid]"] = $post;
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;

		if (empty($firstpost))
		{
			$firstpost = $post;
		}

		if ($post['userid'])
		{
			// find all unique thread-user combos
			$unique_thread_user["$post[threadid]"]["$post[userid]"] = array(
				'issubscribed' => $post['issubscribed'],
				'autosubscribe' => $post['autosubscribe'],
			);
		}
	}

	if (empty($postarray))
	{
		eval(standard_error(fetch_error('no_applicable_posts_selected')));
	}

	// we need the full structure of each thread before we move
	// (so we can figure out the parent relationships)
	$parentassoc = array();
	$parent_posts_sql = $db->query_read("
		SELECT postid, parentid, threadid
		FROM " . TABLE_PREFIX . "post
		WHERE threadid IN (" . implode(',', array_keys($threadlist)) . ")
		ORDER BY dateline
	");
	while ($parent_post = $db->fetch_array($parent_posts_sql))
	{
		$parentassoc["$parent_post[threadid]"]["$parent_post[postid]"] = $parent_post['parentid'];
	}

	if ($vbulletin->GPC['type'] == 0)
	{	// Create a new thread
		$destthreadinfo = array(
			'open'         => $firstpost['open'],
			'iconid'       => $firstpost['iconid'],
			'visible'      => $firstpost['thread_visible'],
			'forumid'      => $destforuminfo['forumid'],
			'title'        => $vbulletin->GPC['title'],
			'views'        => 0,
			'dateline'     => TIMENOW,
			'postuserid'   => $firstpost['userid'],
			'postusername' => $firstpost['username'],
			'sticky'       => $firstpost['sticky']
		);

		$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$threadman->setr('forumid', $destthreadinfo['forumid'], true, false);
		$threadman->setr('title', $destthreadinfo['title'], true, false);
		$threadman->setr('iconid', $destthreadinfo['iconid'], true, false);
		$threadman->setr('open', $destthreadinfo['open'], true, false);
		$threadman->setr('views', $destthreadinfo['views']);
		$threadman->setr('visible', $destthreadinfo['visible'], true, false);
		// Rest of thread field will be populated by the build_thread_counters() call
		$destthreadinfo['threadid'] = $threadman->save();
		unset($threadman);
	}

	if ($firstpost['dateline'] <= $destthreadinfo['dateline'])
	{	// destination thread has a new first post (this will always be true for $type == 0)
		if ($firstpost['visible'] != 1)
		{	// Unhide the new first post since all first posts are visible
			$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_SILENT, 'threadpost');
			$postman->set_existing($firstpost);
			$postman->set('visible', 1);
			$postman->save();
			unset($postman);

			// we need to give this user back his post if this is a visible thread in a counting forum
			if ($destthreadinfo['visible'] == 1 AND $destforuminfo['countposts'])
			{
				$userman =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
				$userman->set_existing($firstpost);
				$userman->set('posts', 'posts + 1', false);
				$userman->set_ladder_usertitle_relative(1);
				$userman->save();
				unset($userman);
			}

			if ($firstpost['firstpostid'] != $firstpost['postid'])
			{	// We didn't take the thread's first post so remove some records
				if (!$firstpost['visible'])
				{	// remove new first post's old moderation record
					$db->query_write("
						DELETE FROM " . TABLE_PREFIX . "moderation
						WHERE primaryid = $firstpost[postid]
							AND type = 'reply'
					");
				}
				else
				{	// remove new first post's old deletionlog record
					$deletiondata =& datamanager_init('Deletionlog_ThreadPost', $vbulletin, ERRTYPE_SILENT, 'deletionlog');
					$deletioninfo = array('type' => 'post', 'primaryid' => $firstpost['postid']);
					$deletiondata->set_existing($deletioninfo);
					$deletiondata->delete();
					unset($deletiondata, $deletioninfo);
				}
			}
		}

		if (!$destthreadinfo['visible'])
		{	// Moderated thread so overwrite moderation record
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "moderation
				(primaryid, type, dateline)
				VALUES
				($destthreadinfo[threadid], 'thread', " . TIMENOW . ")
			");
		}
		else if ($destthreadinfo['visible'] == 2)
		{	// Deleted thread so overwrite the deletionlog entry
			$deletionman =& datamanager_init('Deletionlog_ThreadPost', $vbulletin, ERRTYPE_SILENT, 'deletionlog');
			$deletionman->set('primaryid', $destthreadinfo['threadid']);
			$deletionman->set('type', 'thread');
			$deletionman->set('userid', $vbulletin->userinfo['userid']);
			$deletionman->set('username', $vbulletin->userinfo['username']);
			$deletionman->save();
			unset($deletionman);
		}
	}

	// Move posts to their new thread
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "post
		SET threadid = $destthreadinfo[threadid]
		WHERE postid IN (" . implode(',', array_keys($postarray)) . ")
	");

	// kill the parsed post cache
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "postparsed
		WHERE postid IN (" . implode(',', array_keys($postarray)) . ")
	");

	// Search index maintenance
	vb_Search_Indexcontroller_Queue::indexQueue('vBForum', 'Post',
   		'thread_data_change',  $destthreadinfo[threadid]);

	$userbyuserid = array();
	foreach ($postarray AS $postid => $post)
	{
		if ($post['userid'] AND $post['visible'] == 1)
		{
			$foruminfo = fetch_foruminfo($post['forumid']);

			if ($foruminfo['countposts'] AND $post['thread_visible'] == 1 AND (!$destforuminfo['countposts'] OR ($destforuminfo['countposts'] AND $destthreadinfo['visible'] != 1)))
			{	// Take away a post
				if (!isset($userbyuserid["$post[userid]"]))
				{
					$userbyuserid["$post[userid]"] = -1;
				}
				else
				{
					$userbyuserid["$post[userid]"]--;
				}
			}
			else if ($destforuminfo['countposts'] AND $destthreadinfo['visible'] == 1 AND (!$foruminfo['countposts'] OR ($foruminfo['countposts'] AND $post['thread_visible'] != 1)))
			{	// Add a post
				if (!isset($userbyuserid["$post[userid]"]))
				{
					$userbyuserid["$post[userid]"] = 1;
				}
				else
				{
					$userbyuserid["$post[userid]"]++;
				}
			}
		}

		// Let's deal with the residual thread(s) now
		if ($post['postid'] == $post['firstpostid'])
		{	// we moved a first post so thread must be tinkered with

			// Do we have any posts left in this thread?
			if ($firstleftpost = $db->query_first("
				SELECT postid, visible, threadid, title, pagetext
				FROM " . TABLE_PREFIX . "post
				WHERE threadid = $post[threadid]
				ORDER BY dateline
				LIMIT 1
			"))
			{
				if (!$firstleftpost['visible'])
				{	// new first post is moderated so we must remove it's moderation record
					$db->query_write("
						DELETE FROM " . TABLE_PREFIX . "moderation
						WHERE primaryid = $firstleftpost[postid]
							AND type = 'reply'
					");
				}
				else if ($firstleftpost['visible'] == 2)
				{	// new first post is deleted so we must removed it's deletionlog record
					$deletiondata =& datamanager_init('Deletionlog_ThreadPost', $vbulletin, ERRTYPE_SILENT, 'deletionlog');
					$deletioninfo = array('type' => 'post', 'primaryid' => $firstleftpost['postid']);
					$deletiondata->set_existing($deletioninfo);
					$deletiondata->delete();
					unset($deletiondata, $deletioninfo);
				}

				if ($firstleftpost['visible'] != 1)
				{	// post is not visible so we need to set it visible since first posts are always visible
					$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_SILENT, 'threadpost');
					$postman->set_existing($firstleftpost);
					$postman->set('visible', 1);
					$postman->save();
					unset($postman);

					$foruminfo = fetch_foruminfo($post['forumid']);
					// we need to give this user back his post if this is a visible thread in a counting forum
					if ($post['thread_visible'] == 1 AND $foruminfo['countposts'])
					{
						$userman =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
						$userman->set_existing($firstleftpost);
						$userman->set('posts', 'posts + 1', false);
						$userman->set_ladder_usertitle_relative(1);
						$userman->save();
						unset($userman);
					}
				}
			}
			else	// we moved all of the thread :eek: delete the empty thread!
			{
				$threadinfo = fetch_threadinfo($post['threadid']);
				$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
				if ($threadinfo)
				{
					$threadman->set_existing($threadinfo);
				}
				else
				{
					// for legacy support, if some how we get a post that is no longer in a thread (IE: deleted twice?)
					$threadman->set_existing($post);
				}
				$threadman->delete(false, true, NULL, false);
				unset($threadman);
			}
		}
	}

	if (!empty($userbyuserid))
	{
		$userbypostcount = array();
		$alluserids = '';

		foreach ($userbyuserid AS $postuserid => $postcount)
		{
			$alluserids .= ",$postuserid";
			$userbypostcount["$postcount"] .= ",$postuserid";
		}
		foreach ($userbypostcount AS $postcount => $userids)
		{
			$casesql .= " WHEN userid IN (0$userids) THEN $postcount";
		}

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET posts = CAST(posts AS SIGNED) +
			CASE
				$casesql
				ELSE 0
			END
			WHERE userid IN (0$alluserids)
		");
	}

	// update parentids.
	$firstposts = array(
		$destthreadinfo['threadid'] => intval($destthreadinfo['firstpostid'])
	);

	// Remember, this loops through all posts in a thread, even if they aren't moved
	foreach ($parentassoc AS $threadid => $parentposts)
	{
		foreach ($parentposts AS $postid => $parentid)
		{
			if (empty($postarray["$postid"]) AND !empty($postarray["$parentid"]))
			{
				// case 1: post remains, but parent moved
				// we need to find the first post in this thread that wasn't moved
				$new_parentid = $parentid;

				// we continue as long as we find posts that were moved
				while (isset($postarray["$new_parentid"]) AND $new_parentid != 0)
				{
					$new_parentid = $parentposts["$new_parentid"];
				}

				$check_threadid = $threadid;
			}
			else if (!empty($postarray["$postid"]) AND empty($postarray["$parentid"]))
			{
				// case 2: post moved, but parent remains
				// need to find the first post in this thread that was moved
				$new_parentid = $parentid;

				// we continue as long as we find posts that were not moved
				while (!isset($postarray["$new_parentid"]) AND $new_parentid != 0)
				{
					$new_parentid = $parentposts["$new_parentid"];
				}

				$check_threadid = $destthreadinfo['threadid'];
			}
			else
			{
				// if both moved/not moved, then we don't need to do anything
				continue;
			}

			// are we trying to make this the top post in the thread?
			if ($new_parentid == 0)
			{
				if (!empty($firstposts["$check_threadid"]) AND $firstposts["$check_threadid"] != $postid)
				{
					// already have a top post in this thread
					$new_parentid = $firstposts["$check_threadid"];
				}
				else
				{
					$firstposts["$check_threadid"] = $postid;
				}
			}

			$parentcasesql .= " WHEN postid = $postid THEN " . intval($new_parentid);
			$allpostids .= ",$postid";
		}
	}

	if ($parentcasesql)
	{
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "post
			SET parentid =
			CASE
				$parentcasesql
			ELSE
				parentid
			END
			WHERE postid IN (0$allpostids)
		");
	}

	if ($unique_thread_user)
	{
		// Copy thread subscriptions. To do this, we take the "minimum" subscription level.
		// If you aren't subscribed to a thread by default OR aren't subscribed to this thread,
		// you won't be subscribed to the new thread. If you subscribe by default and are subscribed
		// to this thread, you will be subscribed with the default option. (See 3.6 bug 1342.)
		$insert_subscriptions = array();

		foreach ($unique_thread_user AS $threadid => $users)
		{
			foreach ($users AS $userid => $subscriptioninfo)
			{
				if ($subscriptioninfo['issubscribed'] AND $subscriptioninfo['autosubscribe'] != -1)
				{
					$insert_subscriptions[] = "($userid, $destthreadinfo[threadid], $subscriptioninfo[autosubscribe], 0, 1)";
				}
			}
		}

		if ($insert_subscriptions)
		{
			$db->query_write("
				INSERT IGNORE INTO " . TABLE_PREFIX . "subscribethread
					(userid, threadid, emailupdate, folderid, canview)
				VALUES
					" . implode(', ', $insert_subscriptions)
			);
		}

		// need to check permissions on these threads
		update_subscriptions(array('threadids' => array($destthreadinfo['threadid'])));
	}

	$getfirstpost = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "post
		WHERE threadid = $destthreadinfo[threadid]
		ORDER BY dateline
		LIMIT 1
	");

	// make the first post have the title of the new split thread
	$postdata =& datamanager_init('Post', $vbulletin, ERRTYPE_SILENT, 'threadpost');
	$postdata->set_existing($getfirstpost);
	$postdata->set('title', $destthreadinfo['title'], true, false); // don't clean it -- already been cleaned
	$postdata->set('iconid', $destthreadinfo['iconid'], true, false);
	$postdata->save();

	foreach (array_keys($threadlist) AS $threadid)
	{
		build_thread_counters($threadid);
	}

	if (empty($threadlist["$destthreadinfo[threadid]"]))
	{
		build_thread_counters($destthreadinfo['threadid']);
	}

	foreach(array_keys($forumlist) AS $forumid)
	{
		build_forum_counters($forumid);
	}

	if (empty($forumlist["$destforuminfo[forumid]"]))
	{
		build_forum_counters($destforuminfo['forumid']);
	}

	$threadlist[$destthreadinfo['threadid']] = 1;
	vB_ActivityStream_Populate_Forum_Thread::rebuild_thread(array_keys($threadlist));

	log_moderator_action($threadinfo, 'thread_split_to_x', $destthreadinfo['threadid']);

	// empty cookie
	setcookie('vbulletin_inlinepost', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_domoveposts')) ? eval($hook) : false;

	$vbulletin->url = fetch_seo_url('thread', $destthreadinfo);
	print_standard_redirect('redirect_inline_movedposts', true, $forceredirect);
}

// ############################### start do move posts ###############################
if ($_POST['do'] == 'docopyposts')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'type'           => TYPE_UINT,
		'title'          => TYPE_STR,
		'destforumid'    => TYPE_UINT,
		'mergethreadurl' => TYPE_STR
	));

	if ($vbulletin->GPC['type'] == 0)
	{	// Copy to new thread
		if (empty($vbulletin->GPC['title']))
		{
			eval(standard_error(fetch_error('notitle')));
		}

		// check whether dest can contain posts
		$destforumid = verify_id('forum', $vbulletin->GPC['destforumid']);
		$destforuminfo = fetch_foruminfo($destforumid);
		if (!$destforuminfo['cancontainthreads'] OR $destforuminfo['link'])
		{
			eval(standard_error(fetch_error('moveillegalforum')));
		}

		// check destination forum permissions
		$forumperms = fetch_permissions($destforuminfo['forumid']);
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
		{
			print_no_permission();
		}
	}
	else
	{
		// Validate destination thread
		$destthreadid = extract_threadid_from_url($vbulletin->GPC['mergethreadurl']);
		if (!$destthreadid)
		{
			// Invalid URL
			eval(standard_error(fetch_error('mergebadurl')));
		}

		$destthreadid = verify_id('thread', $destthreadid);
		$destthreadinfo = fetch_threadinfo($destthreadid);
		$destforuminfo = fetch_foruminfo($destthreadinfo['forumid']);

		if (($destthreadinfo['isdeleted'] AND !can_moderate($destthreadinfo['forumid'], 'candeleteposts')) OR (!$destthreadinfo['visible'] AND !can_moderate($destthreadinfo['forumid'], 'canmoderateposts')))
		{
			if (can_moderate($destthreadinfo['forumid']))
			{
				print_no_permission();
			}
			else
			{
				eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
			}
		}
	}

	$userbyuserid = array();
	$unique_thread_user = array();

	$posts = $db->query_read_slave("
		SELECT post.postid, post.threadid, post.visible, post.title, post.username, post.dateline, post.parentid, post.userid,
			thread.forumid, thread.title AS thread_title, thread.postuserid, thread.visible AS thread_visible, thread.firstpostid,
			thread.sticky, thread.open, thread.iconid,
			IF(subscribethread.emailupdate IS NULL, 0, 1) AS issubscribed, user.autosubscribe
		FROM " . TABLE_PREFIX . "post AS post
		INNER JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = post.threadid)
		LEFT JOIN " . TABLE_PREFIX . "subscribethread AS subscribethread ON (subscribethread.threadid = thread.threadid AND subscribethread.userid = post.userid AND subscribethread.canview = 1)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (post.userid = user.userid)
		WHERE postid IN (" . implode(',', $postids) . ")
		ORDER BY post.dateline
	");
	while ($post = $db->fetch_array($posts))
	{
		if (!can_moderate($post['forumid'], 'canmanagethreads'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($post['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts')));
		}
		else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($post['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts', $post['title'], $post['thread_title'], $vbulletin->forumcache["$post[forumid]"]['title'])));
		}
		else if (($post['visible'] == 2 OR $post['thread_visible'] == 2) AND !can_moderate($destforuminfo['forumid'], 'candeleteposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_threads_and_posts_in_destination_forum')));
		}
		else if ((!$post['visible'] OR !$post['thread_visible']) AND !can_moderate($destforuminfo['forumid'], 'canmoderateposts'))
		{
			eval(standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_threads_and_posts_in_destination_forum')));
		}

		$postarray["$post[postid]"] = $post['postid'];
		$threadlist["$post[threadid]"] = true;
		$forumlist["$post[forumid]"] = true;

		if (empty($firstpost))
		{
			$firstpost = $post;
		}

		if ($post['userid'])
		{
			// find all unique thread-user combos
			$unique_thread_user["$post[threadid]"]["$post[userid]"] = array(
				'issubscribed' => $post['issubscribed'],
				'autosubscribe' => $post['autosubscribe'],
			);
		}
	}

	if (empty($postarray))
	{
		eval(standard_error(fetch_error('no_applicable_posts_selected')));
	}

	if ($vbulletin->GPC['type'] == 0)
	{	// Create a new thread
		$destthreadinfo = array(
			'open'         => $firstpost['open'],
			'iconid'       => $firstpost['iconid'],
			'visible'      => $firstpost['thread_visible'],
			'forumid'      => $destforuminfo['forumid'],
			'title'        => $vbulletin->GPC['title'],
			'views'        => 0,
			'dateline'     => TIMENOW,
			'postuserid'   => $firstpost['userid'],
			'postusername' => $firstpost['username'],
			'sticky'       => $firstpost['sticky']
		);

		$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$threadman->setr('forumid', $destthreadinfo['forumid'], true, false);
		#$threadman->setr('title', $destthreadinfo['title'], true, false);
		$threadman->set('title', $vbulletin->GPC['title']);
		$threadman->setr('iconid', $destthreadinfo['iconid'], true, false);
		$threadman->setr('open', $destthreadinfo['open'], true, false);
		$threadman->setr('views', $destthreadinfo['views']);
		$threadman->setr('visible', $destthreadinfo['visible'], true, false);
		$threadman->setr('postuserid', $destthreadinfo['postuserid']);
		// Rest of thread field will be populated by the build_thread_counters() call
		$destthreadinfo['threadid'] = $threadman->save();
		$destthreadinfo['title'] = $threadman->fetch_field('title');
		unset($threadman);
	}

	require_once(DIR . '/includes/functions_file.php');

	// duplicate posts
	$posts = $db->query_read_slave("
		SELECT post.*,
			deletionlog.userid AS deleteduserid, deletionlog.username AS deletedusername, deletionlog.reason AS deletedreason,
			NOT ISNULL(deletionlog.primaryid) AS isdeleted, deletionlog.dateline AS deleteddateline,
			moderation.dateline AS moderateddateine, thread.title AS thread_title
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (post.threadid = thread.threadid)
		LEFT JOIN " . TABLE_PREFIX . "deletionlog AS deletionlog ON (deletionlog.primaryid = post.postid AND deletionlog.type = 'post')
		LEFT JOIN " . TABLE_PREFIX . "moderation AS moderation ON (moderation.primaryid = post.postid AND moderation.type = 'post')
		WHERE post.postid IN (" . implode(', ', $postarray) . ")
		ORDER BY dateline
	");


	$foundfirstpost = false;
	$userbyuserid = array();
	$postassoc = array();
	$dupeposts = array();

	while ($post = $db->fetch_array($posts))
	{
		$oldpostid = $post['postid'];
		unset($post['postid'], $post['infraction']);
		$post['threadid'] = $destthreadinfo['threadid'];

		$newfirstpost = false;
		if (!$foundfirstpost)
		{	// First post!
			if ($post['dateline'] < $destthreadinfo['dateline'])
			{	// this copied post is the new first post of the thread
				$post['parentid'] = 0;
				if ($post['visible'] != 1)
				{
					$post['visible'] = 1;
				}
				$newfirstpost = true;
			}
			else
			{
				$post['parentid'] = $destthreadinfo['firstpostid'];
			}
		}
		else if ($postarray["$post[parentid]"] AND $postassoc["$post[parentid]"])
		{	// this post's parent was also copied, update it with the new copy
			$post['parentid'] = $postassoc["$post[parentid]"];
		}
		else
		{
			$post['parentid'] = $firstpostid;
		}

		if ($post['title'] == $post['thread_title'])
		{
			$post['title'] = $destthreadinfo['title'];
			$update_post_title = true;
		}
		else
		{
			$update_post_title = false;
		}

		$postcopy =& datamanager_init('Post', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
		$postcopy->set_info('is_automated', true);
		foreach (array_keys($postcopy->validfields) AS $field)
		{
			if (isset($post["$field"]))
			{
				// bypassing the verify_* calls; this data should be valid as is
				$postcopy->setr($field, $post["$field"], true, false);
			}
		}
		$post['postid'] = $postcopy->save();
		unset($postcopy);

		$postassoc["$oldpostid"] = $post['postid'];

		if (!$foundfirstpost)
		{
			$foundfirstpost = true;
			if ($post['dateline'] < $destthreadinfo['dateline'])
			{
				$firstpostid = $post['postid'];
			}
			else
			{
				$firstpostid = $destthreadinfo['firstpostid'];
			}
		}

		if ($destthreadinfo['visible'] AND $post['visible'] AND $destforuminfo['countposts'] AND $post['userid'])
		{
			if (!isset($userbyuserid["$post[userid]"]))
			{
				$userbyuserid["$post[userid]"] = 1;
			}
			else
			{
				$userbyuserid["$post[userid]"]++;
			}
		}

		if (!$post['visible'])
		{
			$hiddeninfo[] = "($post[postid], 'post', " . (!empty($post['moderateddateline']) ? $post['moderateddateline'] : TIMENOW) . ")";
		}
		else if ($post['visible'] == 2)
		{
			$deleteinfo[] = "($post[postid], 'post', " . intval($post['deleteduserid']) . ", '" . $db->escape_string($post['deletedusername']) . "', '". $db->escape_string($post['deletedreason']) . "', $post[deleteddateline])";
		}

		if ($destforuminfo['indexposts'])
		{
			if (!$update_post_title)
			{
				$dupeposts["$oldpostid"] = $post['postid'];
			}
		}
	}

	// need to read filedata in chunks and update in chunks!
	$types = vB_Types::instance();
	$contenttypeid = $types->getContentTypeID('vBForum_Post');

	$attachments = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "attachment
		WHERE
			contentid IN (" . implode(', ', $postarray) . ")
				AND
			contenttypeid = $contenttypeid
		");
	while ($attachment = $db->fetch_array($attachments))
	{
		$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_ARRAY, 'attachment');
		$attachdata->set('userid', $attachment['userid']);
		$attachdata->set('dateline', $attachment['dateline']);
		$attachdata->set('contentid', $postassoc["$attachment[contentid]"]);
		$attachdata->set('state', $attachment['state']);
		$attachdata->set('contenttypeid', $contenttypeid);
		$attachdata->set('filedataid', $attachment['filedataid']);
		$attachdata->set('filename', $attachment['filename']);
		$attachdata->set('displayorder', $attachment['displayorder']);
		$attachdata->save();
		unset($attachdata);
	}

	foreach($dupeposts AS $oldid => $newid)
	{
		vb_Search_Indexcontroller_Queue::indexQueue('vBForum', 'Post', 'index', $newid);
	}

	// Insert Moderated Posts
	if (!empty($hiddeninfo))
	{
		/*insert query*/
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "moderation
			(primaryid, type, dateline)
			VALUES
			" . implode(', ', $hiddeninfo) . "
		");
	}

	// Insert Deleted Posts
	if (!empty($deleteinfo))
	{
		/*insert query*/
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "deletionlog
			(primaryid, type, userid, username, reason, dateline)
			VALUES
			" . implode(', ', $deleteinfo) . "
		");
	}

	if (!empty($userbyuserid))
	{
		$userbypostcount = array();
		$alluserids = '';

		foreach ($userbyuserid AS $postuserid => $postcount)
		{
			$alluserids .= ",$postuserid";
			$userbypostcount["$postcount"] .= ",$postuserid";
		}
		foreach ($userbypostcount AS $postcount => $userids)
		{
			$casesql .= " WHEN userid IN (0$userids) THEN $postcount";
		}

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET posts = CAST(posts AS SIGNED) +
			CASE
				$casesql
				ELSE 0
			END
			WHERE userid IN (0$alluserids)
		");
	}

	if ($unique_thread_user)
	{
		// Copy thread subscriptions. To do this, we take the "minimum" subscription level.
		// If you aren't subscribed to a thread by default OR aren't subscribed to this thread,
		// you won't be subscribed to the new thread. If you subscribe by default and are subscribed
		// to this thread, you will be subscribed with the default option. (See 3.6 bug 1342.)
		$insert_subscriptions = array();

		foreach ($unique_thread_user AS $threadid => $users)
		{
			foreach ($users AS $userid => $subscriptioninfo)
			{
				if ($subscriptioninfo['issubscribed'] AND $subscriptioninfo['autosubscribe'] != -1)
				{
					$insert_subscriptions[] = "($userid, $destthreadinfo[threadid], $subscriptioninfo[autosubscribe], 0, 1)";
				}
			}
		}

		if ($insert_subscriptions)
		{
			$db->query_write("
				INSERT IGNORE INTO " . TABLE_PREFIX . "subscribethread
					(userid, threadid, emailupdate, folderid, canview)
				VALUES
					" . implode(', ', $insert_subscriptions)
			);
		}

		// need to check permissions on these threads
		update_subscriptions(array('threadids' => array($destthreadinfo['threadid'])));
	}

	build_thread_counters($destthreadinfo['threadid']);
	build_forum_counters($destforuminfo['forumid']);

	vB_ActivityStream_Populate_Forum_Thread::rebuild_thread(array($destthreadinfo['threadid']));
	log_moderator_action($destthreadinfo, 'posts_copied_to_x', $destthreadinfo['threadid']);

	// empty cookie
	setcookie('vbulletin_inlinepost', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('inlinemod_docopyposts')) ? eval($hook) : false;

	$vbulletin->url = fetch_seo_url('thread', $destthreadinfo);
	print_standard_redirect('redirect_inline_copiedposts', true, $forceredirect);
}

($hook = vBulletinHook::fetch_hook('inlinemod_complete')) ? eval($hook) : false;

if (!empty($page_templater))
{
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	$page_templater->register('url', $vbulletin->url);

	$templater = vB_Template::create('THREADADMIN');
		$templater->register_page_templates();
		$templater->register('HTML', $page_templater->render());
		$templater->register('navbar', $navbar);
		$templater->register('onload', $onload);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('parentpostassoc', $parentpostassoc);
		$templater->register('threadinfo', $threadinfo);
	print_output($templater->render());
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63272 $
|| ####################################################################
\*======================================================================*/