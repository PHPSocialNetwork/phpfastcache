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
define('THIS_SCRIPT', 'postings');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('threadmanage', 'prefix');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
	'THREADADMIN',
	'threadadmin_postbit',
	'bbcode_quote',
	'bbcode_code',
	'bbcode_html',
	'bbcode_php',
	'bbcode_video',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'editthread' => array(
		'threadadmin_editthread',
		'threadadmin_logbit',
		'optgroup',
		'posticonbit',
		'posticons'
	),
	'deletethread' => array('threadadmin_deletethread'),
	'managepost'   => array('threadadmin_managepost'),
	'mergethread'  => array('threadadmin_mergethread'),
	'movethread'   => array('threadadmin_movethread','optgroup'),
	'copythread'   => array('threadadmin_movethread'),
);

// ####################### PRE-BACK-END ACTIONS ##########################
require_once('./global.php');
require_once(DIR . '/includes/functions_threadmanage.php');
require_once(DIR . '/includes/functions_databuild.php');
require_once(DIR . '/includes/functions_log_error.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_forum_url();

// ###################### Start makepostingsnav #######################
// shortcut function to make $navbits for navbar
function construct_postings_nav($foruminfo, $threadinfo)
{
	global $vbulletin, $vbphrase;

	$navbits = array();
	$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];

	$parentlist = array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3)));
	foreach ($parentlist AS $forumID)
	{
		$forumTitle = $vbulletin->forumcache["$forumID"]['title'];
		$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
	}
	$navbits[fetch_seo_url('thread', $threadinfo)] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];

	switch ($_REQUEST['do'])
	{
		case 'movethread':   $navbits[''] = $vbphrase['move_thread']; break;
		case 'copythread':	$navbits[''] = $vbphrase['copy_thread']; break;
		case 'editthread':   $navbits[''] = $vbphrase['edit_thread']; break;
		case 'deletethread': $navbits[''] = $vbphrase['delete_thread']; break;
		case 'mergethread':  $navbits[''] = $vbphrase['merge_threads']; break;
	}

	return construct_navbits($navbits);
}

$idname = $vbphrase['thread'];

switch ($_REQUEST['do'])
{
	case 'openclosethread':
	case 'dodeletethread':
	case 'docopythread':
	case 'domovethread':
	case 'updatethread':
	case 'domergethread':
	case 'stick':
	case 'removeredirect':
	case 'deletethread':
	case 'movethread':
	case 'copythread':
	case 'editthread':
	case 'mergethread':
	case 'moderatethread':

		if (!$threadinfo['threadid'])
		{
			eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
		}
		break;

	case 'getip':
		break;
	case 'domanagepost':
	case 'managepost':

		if (!$postinfo['postid'])
		{
			eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
		}
		else if (!$threadinfo['threadid'])
		{
			eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
		}
		break;

	default: // throw and error about invalid $_REQUEST['do']
		$handled_do = false;
		($hook = vBulletinHook::fetch_hook('threadmanage_action_switch')) ? eval($hook) : false;
		if (!$handled_do)
		{
			eval(standard_error(fetch_error('invalid_action')));
		}

}

if ($threadinfo['forumid'])
{
	$forumperms = fetch_permissions($threadinfo['forumid']);
	if (
			(($threadinfo['postuserid'] != $vbulletin->userinfo['userid']) AND !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']))
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR
			!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
		)
	{
		print_no_permission();
	}
}

$threadinfo['notes'] = htmlspecialchars_uni($threadinfo['notes']);


$show['softdelete'] = iif(can_moderate($threadinfo['forumid'], 'candeleteposts'), true, false);
$show['harddelete'] = iif(can_moderate($threadinfo['forumid'], 'canremoveposts'), true, false);

// set $threadedmode (continued from global.php)
if ($vbulletin->options['allowthreadedmode'])
{
	if (!empty($vbulletin->GPC[COOKIE_PREFIX . 'threadedmode']))
	{
		switch ($vbulletin->GPC[COOKIE_PREFIX . 'threadedmode'])
		{
			case 'threaded': $threadedmode = 1; break;
			case 'hybrid':   $threadedmode = 2; break;
			default:         $threadedmode = 0;
		}
	}
	else
	{
		$threadedmode = ($vbulletin->userinfo['threadedmode'] == 3 ? 0 : $vbulletin->userinfo['threadedmode']);
	}

	switch ($threadedmode)
	{
		case 1:
			$show['threadedmode'] = true;
			$show['hybridmode'] = false;
			$show['linearmode'] = false;
			break;
		case 2:
			$show['threadedmode'] = false;
			$show['hybridmode'] = true;
			$show['linearmode'] = false;
			break;
		default:
			$show['threadedmode'] = false;
			$show['hybridmode'] = false;
			$show['linearmode'] = true;
		break;
	}
}
else
{
	DEVDEBUG('Threadedmode disabled by admin');
	$threadedmode = 0;
}

($hook = vBulletinHook::fetch_hook('threadmanage_start')) ? eval($hook) : false;

// ############################### start do open / close thread ###############################
if ($_POST['do'] == 'openclosethread')
{
	if (($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'], 'candeleteposts')) OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		if (can_moderate($threadinfo['forumid']))
		{
			print_no_permission();
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}

	// permission check
	if (!can_moderate($threadinfo['forumid'], 'canopenclose'))
	{
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canopenclose']))
		{
			print_no_permission();
		}
		else
		{
			if (!is_first_poster($threadid))
			{
				print_no_permission();
			}
		}
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	// handles mod log
	$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
	$threadman->set_existing($threadinfo);
	$threadman->set('open', ($threadman->fetch_field('open') == 1 ? 0 : 1));

	($hook = vBulletinHook::fetch_hook('threadmanage_openclose')) ? eval($hook) : false;

	$threadman->save();

	if ($threadinfo['open'])
	{
		$action = $vbphrase['closed'];
	}
	else
	{
		$action = $vbphrase['opened'];
	}

	$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	print_standard_redirect(array('redirect_openclose',$action), true, true);

}

// ############################### start delete thread ###############################
if ($_REQUEST['do'] == 'deletethread')
{
	if (($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'], 'canremoveposts')) OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		if (can_moderate($threadinfo['forumid']))
		{
			print_no_permission();
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}

	// permission check
	if (!can_moderate($threadinfo['forumid'], 'candeleteposts') AND !can_moderate($threadinfo['forumid'], 'canremoveposts'))
	{
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['candeletepost']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['candeletethread']))
		{
			print_no_permission();
		}
		else if ($threadinfo['dateline'] < (TIMENOW - ($vbulletin->options['edittimelimit'] * 60)) AND $vbulletin->options['edittimelimit'] != 0)
		{
			print_no_permission();
		}
		else
		{
			if (!$threadinfo['open'])
			{
				$vbulletin->url = fetch_seo_url('thread', $threadinfo);
				print_standard_redirect('redirect_threadclosed');
			}
			// make sure this thread is owned by the user trying to delete it
			if (!is_first_poster($threadid))
			{
				print_no_permission();
			}
		}
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	// draw nav bar
	$navbits = construct_postings_nav($foruminfo, $threadinfo);

	($hook = vBulletinHook::fetch_hook('threadmanage_deletethread')) ? eval($hook) : false;

	$page_templater = vB_Template::create('threadadmin_deletethread');
		$page_templater->register('threadid', $threadid);
		$page_templater->register('threadinfo', $threadinfo);
	$remove_temp_render = $page_templater->render();
}

// ############################### start do delete thread ###############################
if ($_POST['do'] == 'dodeletethread')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'deletetype'      => TYPE_UINT, 	// 1=leave message; 2=removal
		'deletereason'    => TYPE_STR,
		'keepattachments' => TYPE_BOOL,
	));

	if (($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'], 'canremoveposts')) OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		if (can_moderate($threadinfo['forumid']))
		{
			print_no_permission();
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}

	$physicaldel = false;
	if (!can_moderate($threadinfo['forumid'], 'candeleteposts') AND !can_moderate($threadinfo['forumid'], 'canremoveposts'))
	{
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['candeletepost']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['candeletethread']))
		{
			print_no_permission();
		}
		else if ($threadinfo['dateline'] < (TIMENOW - ($vbulletin->options['edittimelimit'] * 60)) AND $vbulletin->options['edittimelimit'] != 0)
		{
			print_no_permission();
		}
		else
		{
			if (!$threadinfo['open'])
			{
				$vbulletin->url = fetch_seo_url('thread', $threadinfo);
				print_standard_redirect('redirect_threadclosed');
			}
			if (!is_first_poster($threadinfo['threadid']))
			{
				print_no_permission();
			}
		}
	}
	else
	{
		if (!can_moderate($threadinfo['forumid'], 'canremoveposts'))
		{
			$physicaldel = false;
		}
		else if (!can_moderate($threadinfo['forumid'], 'candeleteposts'))
		{
			$physicaldel = true;
		}
		else
		{
			$physicaldel = iif($vbulletin->GPC['deletetype'] == 1, false, true);
		}
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	$delinfo = array(
		'userid'          => $vbulletin->userinfo['userid'],
		'username'        => $vbulletin->userinfo['username'],
		'reason'          => $vbulletin->GPC['deletereason'],
		'keepattachments' => $vbulletin->GPC['keepattachments']
	);

	$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
	$threadman->set_existing($threadinfo);

	($hook = vBulletinHook::fetch_hook('threadmanage_dodeletethread')) ? eval($hook) : false;

	$threadman->delete($foruminfo['countposts'], $physicaldel, $delinfo);
	unset($threadman);

	build_forum_counters($threadinfo['forumid']);

	$vbulletin->url = fetch_seo_url('forum', $foruminfo);
	print_standard_redirect('redirect_deletethread');

}

// ############################### start retrieve ip ###############################
if ($_REQUEST['do'] == 'getip')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'ip' => TYPE_NOHTML
	));

	// check moderator permissions for getting ip
	if (!can_moderate($threadinfo['forumid'], 'canviewips'))
	{
		print_no_permission();
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	if (!empty($vbulletin->GPC['ip']) AND preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $vbulletin->GPC['ip']))
	{
		$postinfo['ipaddress'] =& $vbulletin->GPC['ip'];
	}
	else if (!$postinfo['postid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
	}

	$postinfo['hostaddress'] = @gethostbyaddr($postinfo['ipaddress']);

	($hook = vBulletinHook::fetch_hook('threadmanage_getip')) ? eval($hook) : false;

	eval(standard_error(fetch_error('thread_displayip', $postinfo['ipaddress'], $postinfo['hostaddress']), '', 0));
}

// ############################### start move thread ###############################
if ($_REQUEST['do'] == 'movethread' OR $_REQUEST['do'] == 'copythread')
{
	if (($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'], 'candeleteposts')) OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		if (can_moderate($threadinfo['forumid']))
		{
			print_no_permission();
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}

	// check forum permissions for this forum
	if (!can_moderate($threadinfo['forumid'], 'canmanagethreads'))
	{
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canmove']))
		{
			print_no_permission();
		}
		else
		{
			if (!$threadinfo['open'] AND !($forumperms & $vbulletin->bf_ugp_forumpermissions['canopenclose']))
			{
				$vbulletin->url = fetch_seo_url('thread', $threadinfo);
				print_standard_redirect('redirect_threadclosed', true, true);
			}
			if (!is_first_poster($threadinfo['threadid']))
			{
				print_no_permission();
			}
		}
	}

	$show['move'] = ($_REQUEST['do'] == 'movethread');
	if ($show['move'])
	{
		require_once(DIR . '/includes/functions_prefix.php');
		$prefix_options = fetch_prefix_html($threadinfo['forumid'], $threadinfo['prefixid'], true);
	}
	else
	{
		$prefix_options = '';
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	$title =& $threadinfo['title'];

	$curforumid = $threadinfo['forumid'];
	$moveoptions = construct_move_forums_options();

	$option_templater = vB_Template::create('option');
	$option_templater->register('options', $moveoptions);
	$moveforumbits = $option_templater->render();

	// draw nav bar
	$navbits = construct_postings_nav($foruminfo, $threadinfo);

	($hook = vBulletinHook::fetch_hook('threadmanage_move_copy_thread')) ? eval($hook) : false;

	$page_templater = vB_Template::create('threadadmin_movethread');
		$page_templater->register('moveforumbits', $moveforumbits);
		$page_templater->register('prefix_options', $prefix_options);
		$page_templater->register('threadid', $threadid);
		$page_templater->register('threadinfo', $threadinfo);
		$page_templater->register('title', $title);
	$remove_temp_render = $page_templater->render();
}

// ############################### start do move thread ###############################
//we should split move and copy actions completely and combine the logic with the
//inline mod versions of the same.
if ($_POST['do'] == 'domovethread' OR $_POST['do'] == 'docopythread')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'destforumid'      => TYPE_UINT,
		'redirect'         => TYPE_STR,
		'title'            => TYPE_NOHTML,
		'redirectprefixid' => TYPE_NOHTML,
		'redirecttitle'    => TYPE_NOHTML,
		'period'           => TYPE_UINT,
		'frame'            => TYPE_STR,
	));

	if (($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'], 'candeleteposts')) OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		if (can_moderate($threadinfo['forumid']))
		{
			print_no_permission();
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}

	// check whether dest can contain posts
	$destforumid = verify_id('forum', $vbulletin->GPC['destforumid']);
	$destforuminfo = fetch_foruminfo($destforumid);
	if (!$destforuminfo['cancontainthreads'] OR $destforuminfo['link'])
	{
		eval(standard_error(fetch_error('moveillegalforum')));
	}

	if (($threadinfo['isdeleted'] AND !can_moderate($destforuminfo['forumid'], 'candeleteposts')) OR (!$threadinfo['visible'] AND !can_moderate($destforuminfo['forumid'], 'canmoderateposts')))
	{
		## Insert proper phrase about not being able to move a hidden thread to a forum you can't moderateposts in or a deleted thread to a forum you can't deletethreads in
		eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
	}

	// check source forum permissions
	if (!can_moderate($threadinfo['forumid'], 'canmanagethreads'))
	{
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canmove']))
		{
			print_no_permission();
		}
		else
		{
			if (!$threadinfo['open'] AND !($forumperms & $vbulletin->bf_ugp_forumpermissions['canopenclose']))
			{
				$vbulletin->url = fetch_seo_url('thread', $threadinfo);
				print_standard_redirect('redirect_threadclosed', true, true);
			}
			if (!is_first_poster($threadid))
			{
				print_no_permission();
			}
		}
	}

	// check destination forum permissions
	$destforumperms = fetch_permissions($destforuminfo['forumid']);
	if (!($destforumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
	{
		print_no_permission();
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);
	verify_forum_password($destforuminfo['forumid'], $destforuminfo['password']);

	// check to see if this thread is being returned to a forum it's already been in
	// if a redirect exists already in the destination forum, remove it
	if ($checkprevious = $db->query_first_slave("SELECT threadid FROM " . TABLE_PREFIX . "thread WHERE forumid = $destforuminfo[forumid] AND open = 10 AND pollid = $threadid"))
	{
		$old_redirect =& datamanager_init('Thread', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
		$old_redirect->set_existing($checkprevious);
		$old_redirect->delete(false, true, NULL, false);
		unset($old_redirect);
	}

	// check to see if this thread is being moved to the same forum it's already in but allow copying to the same forum
	if ($destforuminfo['forumid'] == $threadinfo['forumid'] AND $vbulletin->GPC['redirect'])
	{
		eval(standard_error(fetch_error('movesameforum')));
	}

	($hook = vBulletinHook::fetch_hook('threadmanage_move_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['title'] != '' AND $vbulletin->GPC['title'] != $threadinfo['title'])
	{
		$oldtitle = $threadinfo['title'];
		$threadinfo['title'] = unhtmlspecialchars($vbulletin->GPC['title']);
		$updatetitle = true;
	}
	else
	{
		$oldtitle = $threadinfo['title'];
		$updatetitle = false;
	}

	if ($_POST['do'] == 'docopythread')
	{
		$method = 'copy';
	}
	else if ($_POST['do'] == 'domovethread')
	{
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
	}

	switch($method)
	{
		// ***************************************************************
		// move the thread wholesale into the destination forum
		case 'move':
			// update forumid/notes and unstick to prevent abuse
			$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
			$threadman->set_info('skip_moderator_log', true);
			$threadman->set_existing($threadinfo);
			if ($updatetitle)
			{
				$threadman->set('title', $threadinfo['title']);
				if ($vbulletin->options['similarthreadsearch'])
				{
					require_once(DIR . '/vb/search/core.php');
					$searchcontroller = vB_Search_Core::get_instance()->get_search_controller();
					$similarthreads = $searchcontroller->get_similar_threads(
						fetch_censored_text($vbulletin->GPC['title']), $threadinfo['threadid']);
					$threadman->set('similar', implode(',', $similarthreads));
				}
			}
			else
			{	// Bypass check since title wasn't modified
				$threadman->set('title', $threadinfo['title'], true, false);
			}
			$threadman->set('forumid', $destforuminfo['forumid']);

			// If mod can not manage threads in destination forum then unstick thread
			if (!can_moderate($destforuminfo['forumid'], 'canmanagethreads'))
			{
				$threadman->set('sticky', 0);
			}

			($hook = vBulletinHook::fetch_hook('threadmanage_move_simple')) ? eval($hook) : false;

			$threadman->save();

			log_moderator_action($threadinfo, 'thread_moved_to_x', $destforuminfo['title']);

			break;
		// ***************************************************************


		// ***************************************************************
		// move the thread into the destination forum and leave a redirect
		case 'movered':

			$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
			$threadman->set_info('skip_moderator_log', true);
			$threadman->set_existing($threadinfo);
			if ($updatetitle)
			{
				$threadman->set('title', $threadinfo['title']);
				if ($vbulletin->options['similarthreadsearch'])
				{
					require_once(DIR . '/vb/search/core.php');
					$searchcontroller = vB_Search_Core::get_instance()->get_search_controller();
					$similarthreads = $searchcontroller->get_similar_threads(
						fetch_censored_text($vbulletin->GPC['title']), $threadinfo['threadid']);
					$threadman->set('similar', implode(',', $similarthreads));
				}
			}
			else
			{	// Bypass check since title wasn't modified
				$threadman->set('title', $threadinfo['title'], true, false);
			}
			$threadman->set('forumid', $destforuminfo['forumid']);

			// If mod can not manage threads in destination forum then unstick thread
			if (!can_moderate($destforuminfo['forumid'], 'canmanagethreads'))
			{
				$threadman->set('sticky', 0);
			}

			($hook = vBulletinHook::fetch_hook('threadmanage_move_redirect_orig')) ? eval($hook) : false;

			$threadman->save();
			unset($threadman);

			if ($threadinfo['visible'] == 1)
			{	// Insert redirect for visible thread
				log_moderator_action($threadinfo, 'thread_moved_with_redirect_to_a', $destforuminfo['title']);

				$redirdata = array(
					'lastpost'     => intval($threadinfo['lastpost']),
					'forumid'      => intval($threadinfo['forumid']),
					'pollid'       => intval($threadinfo['threadid']),
					'open'         => 10,
					'replycount'   => intval($threadinfo['replycount']),
					'postusername' => $threadinfo['postusername'],
					'postuserid'   => intval($threadinfo['postuserid']),
					'lastposter'   => $threadinfo['lastposter'],
					'lastposterid' => $threadinfo['lastposterid'],
					'dateline'     => intval($threadinfo['dateline']),
					'views'        => intval($threadinfo['views']),
					'iconid'       => intval($threadinfo['iconid']),
					'visible'      => 1
				);

				$redir =& datamanager_init('Thread', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
				foreach (array_keys($redirdata) AS $field)
				{
					// bypassing the verify_* calls; this data should be valid as is
					$redir->setr($field, $redirdata["$field"], true, false);
				}

				if ($updatetitle)
				{
					if (empty($vbulletin->GPC['redirecttitle']))
					{
						$redir->set('title', $threadinfo['title']);
					}
					else
					{
						$redir->set('title', unhtmlspecialchars($vbulletin->GPC['redirecttitle']));
					}
				}
				else
				{	// Bypass check since title wasn't modified
					if (empty($vbulletin->GPC['redirecttitle']))
					{
						$redir->set('title', $threadinfo['title'], true, false);
					}
					else
					{
						$redir->set('title', unhtmlspecialchars($vbulletin->GPC['redirecttitle']));
					}
				}

				require_once(DIR . '/includes/functions_prefix.php');
				if (can_use_prefix($vbulletin->GPC['redirectprefixid']))
				{
					$redir->set('prefixid', $vbulletin->GPC['redirectprefixid']);
				}
				($hook = vBulletinHook::fetch_hook('threadmanage_move_redirect_notice')) ? eval($hook) : false;

				if ($redirthreadid = $redir->save() AND $vbulletin->GPC['redirect'] == 'expires')
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
					$db->query_write("
						REPLACE INTO " . TABLE_PREFIX . "threadredirect
							(threadid, expires)
						VALUES
							($redirthreadid, $expires)
					");
				}
				unset($redir);
			}
			else
			{	// leave no redirect for hidden or deleted threads
				log_moderator_action($threadinfo, 'thread_moved_to_x', $destforuminfo['title']);
			}

			break;
		// ***************************************************************


		// ***************************************************************
		// make a copy of the thread in the redirect forum
		case 'copy':

			log_moderator_action($threadinfo, 'thread_copied_to_x', $destforuminfo['title']);

			if ($threadinfo['pollid'] AND $threadinfo['open'] != 10)
			{
				// We have a poll, need to duplicate it!
				if ($pollinfo = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "poll WHERE pollid = $threadinfo[pollid]"))
				{
					$poll =& datamanager_init('Poll', $vbulletin, ERRTYPE_STANDARD);
					$poll->set('question',	$pollinfo['question']);
					$poll->set('dateline',	$pollinfo['dateline']);
					foreach (explode('|||', $pollinfo['options']) AS $option)
					{
						$option = rtrim($option);
						$poll->set_option($option);
					}
					$poll->set('active',	$pollinfo['active']);
					$poll->set('timeout',	$pollinfo['timeout']);
					$poll->set('multiple',	$pollinfo['multiple']);
					$poll->set('public',	$pollinfo['public']);

					if ($pollinfo['multiple'])
					{
						$poll->set('voters', $pollinfo['voters']);
					}

					$oldpollid = $threadinfo['pollid'];
					$threadinfo['pollid'] = $poll->save();

					$pollvotes = $db->query_read_slave("
						SELECT *
						FROM " . TABLE_PREFIX . "pollvote
						WHERE pollid = $oldpollid
					");

					while ($pollvote = $db->fetch_array($pollvotes))
					{
						$new_pollvote =& datamanager_init('PollVote', $vbulletin, ERRTYPE_STANDARD);
						if ($pollinfo['multiple'])
						{
							$new_pollvote->set_info('skip_voters', true);
						}
						$new_pollvote->set('pollid',     $threadinfo['pollid']);
						$new_pollvote->set('votedate',   $pollvote['votedate']);
						$new_pollvote->set('voteoption', $pollvote['voteoption']);
						$new_pollvote->set('votetype',   $pollvote['votetype']);
						if (!$pollvote['userid'])
						{
							$new_pollvote->set('userid', NULL, false);
						}
						else
						{
							$new_pollvote->set('userid', $pollvote['userid']);
						}
						$new_pollvote->save();
					}
				}
			}

			// duplicate thread, save a few columns
			$newthreadinfo = $threadinfo;
			$delinfo = array(
				'userid'   => $threadinfo['del_userid'],
				'username' => $threadinfo['del_username'],
				'reason'   => $threadinfo['del_reason'],
				'dateline' => $threadinfo['del_dateline'],
			);

			unset($newthreadinfo['vote'], $newthreadinfo['threadid'], $newthreadinfo['votenum'], $newthreadinfo['votetotal'], $newthreadinfo['isdeleted'], $newthreadinfo['del_userid'], $newthreadinfo['del_username'], $newthreadinfo['del_reason'], $newthreadinfo['issubscribed'], $newthreadinfo['emailupdate'], $newthreadinfo['folderid']);
			$newthreadinfo['forumid'] = $destforuminfo['forumid'];

			// If mod can not manage threads in destination forum then unstick thread
			if (!can_moderate($destforuminfo['forumid'], 'canmanagethreads'))
			{
				unset($newthreadinfo['sticky']);
			}

			$threadcopy =& datamanager_init('Thread', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
			foreach (array_keys($threadcopy->validfields) AS $field)
			{
				if (isset($newthreadinfo["$field"]))
				{
					// bypassing the verify_* calls; this data should be valid as is
					$threadcopy->setr($field, $newthreadinfo["$field"], true, false);
				}
			}

			if ($updatetitle AND $vbulletin->options['similarthreadsearch'])
			{
				require_once(DIR . '/vb/search/core.php');
				$searchcontroller = vB_Search_Core::get_instance()->get_search_controller();
				$similarthreads = $searchcontroller->get_similar_threads(
					fetch_censored_text($vbulletin->GPC['title']), $threadinfo['threadid']);
				$threadcopy->set('similar', implode(',', $similarthreads));
			}

			($hook = vBulletinHook::fetch_hook('threadmanage_move_copy_threadcopy')) ? eval($hook) : false;
			$newthreadid = $threadcopy->save();
			$newthreadinfo['threadid'] = $newthreadid;

			require_once(DIR . '/includes/class_taggablecontent.php');
			require_once(DIR . '/vb/search/core.php');
			$content = vB_Taggable_Content_Item::create($vbulletin, vB_Search_Core::get_instance()->get_contenttypeid("vBForum", "Thread"), $newthreadid);
			$content->copy_tag_attachments(vB_Search_Core::get_instance()->get_contenttypeid("vBForum", "Thread"), $threadid);
			unset($threadcopy);

			require_once(DIR . '/includes/functions_file.php');

			// duplicate posts
			$posts = $db->query_read_slave("
				SELECT post.*,
					deletionlog.userid AS deleteduserid, deletionlog.username AS deletedusername, deletionlog.reason AS deletedreason,
					NOT ISNULL(deletionlog.primaryid) AS isdeleted, deletionlog.dateline AS deleteddateline,
					moderation.dateline AS moderateddateline
				FROM " . TABLE_PREFIX . "post AS post
				LEFT JOIN " . TABLE_PREFIX . "deletionlog AS deletionlog ON (deletionlog.primaryid = post.postid AND deletionlog.type = 'post')
				LEFT JOIN " . TABLE_PREFIX . "moderation AS moderation ON (moderation.primaryid = post.postid AND moderation.type = 'reply')
				WHERE post.threadid = $threadid
				ORDER BY dateline
			");

			$done_firstpost = false;
			$userbyuserid = array();
			$postarray = array();
			$postassoc = array();
			$deleteinfo = array();

			while ($post = $db->fetch_array($posts))
			{
				if ($post['title'] == $oldtitle AND $updatetitle)
				{
					$post['title'] = $threadinfo['title'];
					$update_post_title = true;
				}
				else
				{
					$update_post_title = false;
				}

				$oldpostid = $post['postid'];
				unset($post['postid']);
				unset($post['infraction']);

				$post['threadid'] = $newthreadid;

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
				($hook = vBulletinHook::fetch_hook('threadmanage_move_copy_postcopy')) ? eval($hook) : false;
				$newpostid = $postcopy->save();
				$errors = $postcopy->errors;
				unset($postcopy);

				if (!$done_firstpost)
				{
					if (!$threadinfo['visible'])
					{	// Insert Moderation Record
						$db->query_write("
							INSERT INTO " . TABLE_PREFIX . "moderation
							(primaryid, type, dateline)
							VALUES
							($newthreadid, 'thread', " . (!empty($post['moderateddateline']) ? $post['moderateddateline'] : TIMENOW) . ")
						");
					}
					else if ($threadinfo['visible'] == 2)
					{
						$deletionman =& datamanager_init('Deletionlog_ThreadPost', $vbulletin, ERRTYPE_SILENT, 'deletionlog');
						$deletionman->set('primaryid', $newthreadinfo['threadid']);
						$deletionman->set('type', 'thread');
						$deletionman->set('userid', $delinfo['userid']);
						$deletionman->set('username', $delinfo['username']);
						$deletionman->set('dateline', $delinfo['dateline']);
						// bypassing the verify_* calls; this data should be valid as is
						$deletionman->setr('reason', $delinfo['reason'], true, false);
						$deletionman->save();
						unset($deletionman);
					}

					$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
					$threadman->set_existing($newthreadinfo);
					$threadman->set('firstpostid', $newpostid);
					$threadman->save();
					unset($threadman);

					$done_firstpost = true;
				}

				if (!$newpostid)
				{ // the saving of the post failed, skip it
					continue;
				}

				if ($post['visible'] == 2)
				{
					$deleteinfo[] = "($newpostid, 'post', " . intval($post['deleteduserid']) . ", '" . $db->escape_string($post['deletedusername']) . "', '". $db->escape_string($post['deletedreason']) . "', " . intval($post['deleteddateline']) . ")";
				}

				$parentcasesql .= " WHEN parentid = $oldpostid THEN $newpostid";
				$parentids .= ",$oldpostid"; // doubles as a list of original post IDs
				$postassoc["$oldpostid"] = $newpostid; // same as $postarray, but set in all cases; for attachments

				if ($foruminfo['indexposts'] AND $destforuminfo['indexposts'])
				{
					if (!$update_post_title)
					{
						$postarray["$oldpostid"] = $newpostid;
					}
				}

				build_thread_counters($newthreadinfo['threadid']);

				if ($destforuminfo['countposts'] AND $post['userid'] AND $threadinfo['visible'] == 1 AND $post['visible'] == 1)
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
			}

			$types = vB_Types::instance();
			$contenttypeid = $types->getContentTypeID('vBForum_Post');

			$find_attach = array();
			$replace_attach = array();
			$attachments = $db->query_read("
				SELECT *
				FROM " . TABLE_PREFIX . "attachment
				WHERE
					contentid IN (-1$parentids)
						AND
					contenttypeid = $contenttypeid
				ORDER BY attachmentid
			");
			while ($attachment = $db->fetch_array($attachments))
			{
				$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_SILENT, 'attachment');
				$attachdata->set('userid', $attachment['userid']);
				$attachdata->set('dateline', $attachment['dateline']);
				$attachdata->set('contentid', $postassoc["$attachment[contentid]"]);
				$attachdata->set('state', $attachment['state']);
				$attachdata->set('contenttypeid', $contenttypeid);
				$attachdata->set('filename', $attachment['filename']);
				$attachdata->set('filedataid', $attachment['filedataid']);
				$attachdata->set('displayorder', $attachment['displayorder']);
				$newattachmentid = $attachdata->save();
				unset($attachdata);

				$find_attach[$postassoc["$attachment[contentid]"]][] = '#\[attach\]' . $attachment['attachmentid']. '\[/attach\]#si';
				$replace_attach[$postassoc["$attachment[contentid]"]][] = '[attach]' . $newattachmentid . '[/attach]';
			}

			// update [attach]ABC[/attach] Entries
			if (!empty($find_attach))
			{
				$posts = $db->query_read_slave("
					SELECT post.pagetext, post.postid
					FROM " . TABLE_PREFIX . "post AS post
					WHERE post.postid IN (" . implode(', ', array_keys($find_attach)) . ")
				");
				while ($post = $db->fetch_array($posts))
				{
					$pagetext = preg_replace($find_attach["$post[postid]"], $replace_attach["$post[postid]"], $post['pagetext']);
					$postcopy =& datamanager_init('Post', $vbulletin, ERRTYPE_SILENT, 'threadpost');
					$postcopy->set_existing($post);
					$postcopy->set_info('is_automated', true);
					// bypassing the verify_* calls; this data should be valid as is
					$postcopy->setr('pagetext', $pagetext, true, false);
					$postcopy->save();
				}
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

			// reconnect parent/child posts in the new thread
			if ($parentcasesql)
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "post SET
						parentid = CASE $parentcasesql ELSE parentid END
					WHERE threadid = $newthreadid AND parentid IN (0$parentids)
				");
			}

			// Update User Post Counts
			if (!empty($userbyuserid))
			{
				$userbypostcount = array();
				foreach ($userbyuserid AS $postuserid => $postcount)
				{
					$alluserids .= ",$postuserid";
					$userbypostcount["$postcount"] .= ",$postuserid";
				}
				foreach ($userbypostcount AS $postcount => $userids)
				{
					$postcasesql .= " WHEN userid IN (0$userids) THEN $postcount";
				}

				$db->query_write("
					UPDATE " . TABLE_PREFIX . "user SET
						posts = posts + CASE $postcasesql ELSE 0 END
					WHERE userid IN (0$alluserids)
				");
			}

			break;
		// ***************************************************************

	} // end switch($method)

	// kill the cache for the old thread
	delete_post_cache_threads(array($threadinfo['threadid']));

	// Update Post Count if we move from a counting forum to a non counting or vice-versa..
	// Source Dest  Visible Thread    Hidden Thread
	// Yes    Yes   ~           	  ~
	// Yes    No    -visible          ~
	// No     Yes   +visible          ~
	// No     No    ~                 ~
	if ($threadinfo['visible'] AND ($method == 'move' OR $method == 'movered') AND (($foruminfo['countposts'] AND !$destforuminfo['countposts']) OR (!$foruminfo['countposts'] AND $destforuminfo['countposts'])))
	{
		$posts = $db->query_read_slave("
			SELECT userid
			FROM " . TABLE_PREFIX . "post
			WHERE threadid = $threadinfo[threadid]
				AND	userid > 0
				AND visible = 1
		");
		$userbyuserid = array();
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
			foreach ($userbyuserid AS $postuserid => $postcount)
			{
				$alluserids .= ",$postuserid";
				$userbypostcount["$postcount"] .= ",$postuserid";
			}
			foreach ($userbypostcount AS $postcount => $userids)
			{
				$casesql .= " WHEN userid IN (0$userids) THEN $postcount";
			}

			$operator = ($destforuminfo['countposts'] ? '+' : '-');

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "user
				SET posts = CAST(posts AS SIGNED) $operator
					CASE
						$casesql
						ELSE 0
					END
				WHERE userid IN (0$alluserids)
			");
		}
	}

	build_forum_counters($threadinfo['forumid']);
	if ($threadinfo['forumid'] != $destforuminfo['forumid'])
	{
		build_forum_counters($destforuminfo['forumid']);
	}

	// Update canview status of thread subscriptions
	update_subscriptions(array('threadids' => array($threadid)));

	if ($method == 'copy' AND $newthreadid AND $newthreadinfo)
	{
		$threadid = $newthreadid;
		$threadinfo = $newthreadinfo;
	}

	($hook = vBulletinHook::fetch_hook('threadmanage_move_complete')) ? eval($hook) : false;

	$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	print_standard_redirect('redirect_movethread');
}

// ############################### start manage post ###############################
if ($_REQUEST['do'] == 'managepost')
{
	if ($postinfo['postid'] == $threadinfo['firstpostid'])
	{	// first post
		// redirect to edit thread
		$_REQUEST['do'] = 'editthread';
	}
	else
	{
		if (!can_moderate($threadinfo['forumid'], 'candeleteposts'))
		{
			print_no_permission();
		}

		verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

		$show['undeleteoption'] = iif($postinfo['isdeleted'] AND (can_moderate($threadinfo['forumid'], 'canremoveposts') OR can_moderate($threadinfo['forumid'], 'candeleteposts')), true, false);

		if (!$show['undeleteoption'])
		{
  			standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink']));
		}

		require_once(DIR . '/includes/class_bbcode.php');
		$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
		$postinfo['pagetext'] = $bbcode_parser->parse($postinfo['pagetext'], $forumid);

		$postinfo['postdate'] = vbdate($vbulletin->options['dateformat'], $postinfo['dateline'], 1);
		$postinfo['posttime'] = vbdate($vbulletin->options['timeformat'], $postinfo['dateline']);

		$visiblechecked = iif($postinfo['visible'], 'checked="checked"');

		// draw nav bar
		$navbits = construct_postings_nav($foruminfo, $threadinfo);
	}

	($hook = vBulletinHook::fetch_hook('threadmanage_managepost')) ? eval($hook) : false;

	$page_templater = vB_Template::create('threadadmin_managepost');
		$page_templater->register('postid', $postid);
		$page_templater->register('postinfo', $postinfo);
		$page_templater->register('threadid', $threadid);
	$remove_temp_render = $page_templater->render();
}

// ############################### start edit thread ###############################
if ($_REQUEST['do'] == 'editthread')
{
	// only mods with the correct permissions should be able to access this
	if (!can_moderate($threadinfo['forumid'], 'caneditthreads') OR ($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'], 'candeleteposts') AND !can_moderate($threadinfo['forumid'], 'canremoveposts')) OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		print_no_permission();
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	$show['undeleteoption'] = ($threadinfo['visible'] == 2 AND can_moderate($threadinfo['forumid'], 'candeleteposts')) ? true : false;
	$show['removeoption'] = ($threadinfo['visible'] == 2 AND can_moderate($threadinfo['forumid'], 'canremoveposts')) ? true : false;
	$show['moderateoption'] = (can_moderate($threadinfo['forumid'], 'canmoderateposts') AND $threadinfo['visible'] != 2) ? true : false;

	// draw nav bar
	$navbits = construct_postings_nav($foruminfo, $threadinfo);

	$visiblechecked = iif($threadinfo['visible'], 'checked="checked"');
	$visiblehidden = iif($threadinfo['visible'], 'yes');
	$openchecked = iif($threadinfo['open'], 'checked="checked"');
	$stickychecked = iif($threadinfo['sticky'], 'checked="checked"');

	require_once(DIR . '/includes/functions_newpost.php');
	$posticons = construct_icons($threadinfo['iconid'], $foruminfo['allowicons']);

	require_once(DIR . '/includes/functions_prefix.php');
	$prefix_options = fetch_prefix_html($foruminfo['forumid'], $threadinfo['prefixid'], true);

	$show['ipaddress'] = can_moderate($threadinfo['forumid'], 'canviewips') ? true : false;

	$logs = $db->query_read_slave("
		SELECT moderatorlog.dateline, moderatorlog.userid, moderatorlog.action, moderatorlog.type, moderatorlog.postid, moderatorlog.ipaddress,
			user.username,
			post.title
		FROM " . TABLE_PREFIX . "moderatorlog AS moderatorlog
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = moderatorlog.userid)
		LEFT JOIN " . TABLE_PREFIX . "post AS post ON (moderatorlog.postid = post.postid)
		WHERE moderatorlog.threadid = $threadid
		ORDER BY dateline
	");

	while ($log = $db->fetch_array($logs))
	{
		exec_switch_bg();

		if ($log['type'])
		{
			$phrase = fetch_modlogactions($log['type']);

			if ($unserialized = unserialize($log['action']))
			{
				array_unshift($unserialized, $vbphrase["$phrase"]);
				$log['action'] = call_user_func_array('construct_phrase', $unserialized);
			}
			else
			{
				$log['action'] = construct_phrase($vbphrase["$phrase"], $log['action']);
			}
		}

		if ($log['title'] == '')
		{
			$log['title'] = $vbphrase['n_a'];
		}

		$pageinfo = array('p' => $log['postid']);

		$log['dateline'] = vbdate($vbulletin->options['logdateformat'], $log['dateline']);
		$log['ipaddress'] = htmlspecialchars_uni($log['ipaddress']); // Sanity ;0
		$templater = vB_Template::create('threadadmin_logbit');
			$templater->register('bgclass', $bgclass);
			$templater->register('log', $log);
			$templater->register('pageinfo', $pageinfo);
			$templater->register('threadinfo', $threadinfo);
		$logbits .= $templater->render();
	}
	$show['modlog'] = iif($logbits, true, false);

	if ($threadinfo['open'] == 10) // Thread redirect
	{
		$posticons = '';
		$show['undeleteoptions'] = $show['options'] = false;
		if ($redirect = $db->query_first_slave("SELECT expires FROM " . TABLE_PREFIX . "threadredirect WHERE threadid = $threadinfo[threadid]"))
		{
			$show['expires'] = true;
			$show['cpermanent'] = true;
			$threadinfo['expiredate'] = vbdate($vbulletin->options['dateformat'], $redirect['expires']);
			$threadinfo['expiretime'] = vbdate($vbulletin->options['timeformat'], $redirect['expires']);
		}
		else
		{
			$show['cexpires'] = true;
		}
		$show['redirect']  = true;
	}
	else
	{
		$show['options'] = true;
	}

	($hook = vBulletinHook::fetch_hook('threadmanage_editthread')) ? eval($hook) : false;

	$page_templater = vB_Template::create('threadadmin_editthread');
		$page_templater->register('logbits', $logbits);
		$page_templater->register('openchecked', $openchecked);
		$page_templater->register('posticons', $posticons);
		$page_templater->register('prefix_options', $prefix_options);
		$page_templater->register('selectedicon', $selectedicon);
		$page_templater->register('stickychecked', $stickychecked);
		$page_templater->register('threadid', $threadid);
		$page_templater->register('threadinfo', $threadinfo);
		$page_templater->register('visiblechecked', $visiblechecked);
	$remove_temp_render = $page_templater->render();
}

// ############################### start update thread ###############################
if ($_POST['do'] == 'updatethread')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'visible'         => TYPE_BOOL,
		'open'            => TYPE_BOOL,
		'sticky'          => TYPE_BOOL,
		'iconid'          => TYPE_UINT,
		'notes'           => TYPE_NOHTML,
		'threadstatus'    => TYPE_UINT,
		'reason'          => TYPE_NOHTML,
		'title'           => TYPE_STR,
		'prefixid'        => TYPE_NOHTML,
		'redirect'        => TYPE_STR,
		'frame'           => TYPE_STR,
		'period'          => TYPE_UINT,
		'keepattachments' => TYPE_BOOL
	));

	// only mods with the correct permissions should be able to access this
	if (!can_moderate($threadinfo['forumid'], 'caneditthreads'))
	{
		print_no_permission();
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	if ($vbulletin->GPC['title'] == '')
	{
		eval(standard_error(fetch_error('notitle')));
	}

	if (!can_moderate($threadinfo['forumid'], 'canopenclose') AND !$forumperms['canopenclose'])
	{
		$vbulletin->GPC['open'] = $threadinfo['open'];
	}

	if (!can_moderate($threadinfo['forumid'], 'canmanagethreads'))
	{
		$vbulletin->GPC['sticky'] = $threadinfo['sticky'];
	}

	if ($threadinfo['visible'] == 2)
	{	// Editing a deleted thread
		if ($vbulletin->GPC['threadstatus'] == 1 AND can_moderate($threadinfo['forumid'], 'candeleteposts'))
		{ // undelete
			undelete_thread($threadinfo['threadid'], $foruminfo['countposts']);
			$threaddeleted = -1;
		}
		else if ($vbulletin->GPC['threadstatus'] == 2 AND can_moderate($threadinfo['forumid'], 'canremoveposts'))
		{ // remove
			$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
			$threadman->set_existing($threadinfo);
			$threadman->delete($foruminfo['countposts'], true);
			unset($threadman);

			$threaddeleted = 1;
		}
		else
		{
			if ($vbulletin->GPC['reason'] != '')
			{
				$deletionman =& datamanager_init('Deletionlog_Threadpost', $vbulletin, ERRTYPE_STANDARD, 'deletionlog');
				$deletioninfo = array('type' => 'thread', 'primaryid' => $threadinfo['threadid']);
				$deletionman->set_existing($deletioninfo);
				$deletionman->set('reason', $vbulletin->GPC['reason']);
				$deletionman->save();
				unset($deletionman, $deletioninfo);
			}
			$threaddeleted = 0;

			if (!$vbulletin->GPC['keepattachments'])
			{
				// want to remove attachments
				$postids = array();
				$posts = $db->query_read("
					SELECT post.postid
					FROM " . TABLE_PREFIX . "post AS post
					WHERE post.threadid = $threadinfo[threadid]
				");
				while ($post = $db->fetch_array($posts))
				{
					$postids[] = $post['postid'];
				}

				if (!empty($postids))
				{
					$types = vB_Types::instance();
					$contenttypeid = $types->getContentTypeID('vBForum_Post');

					$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_SILENT, 'attachment');
					$attachdata->condition = "a.contentid IN (" . implode(",", $postids) . ") AND a.contenttypeid = $contenttypeid";
					$attachdata->delete(true, false);
				}
			}
		}
	}
	else
	{	// Editing a non deleted thread
		if (can_moderate($threadinfo['forumid'], 'canmoderateposts') AND $threadinfo['open'] != 10)
		{
			if ($threadinfo['visible'] == 1 AND !$vbulletin->GPC['visible'])
			{
				unapprove_thread($threadid, $foruminfo['countposts'], true, $threadinfo);
			}
			else if (!$threadinfo['visible'] AND $vbulletin->GPC['visible'])
			{
				approve_thread($threadid, $foruminfo['countposts'], true, $threadinfo);
			}
		}
		$threaddeleted = 0;
	}

	if ($threaddeleted != 1)
	{
		$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
		$threadman->set_existing($threadinfo);

		if ($threadinfo['open'] != 10)
		{
			// Reindex first post to set up title properly.
			$getfirstpost = $db->query_first_slave("
				SELECT *
				FROM " . TABLE_PREFIX . "post
				WHERE threadid = $threadinfo[threadid]
				ORDER BY dateline
				LIMIT 1
			");
			$getfirstpost['threadtitle'] =& $vbulletin->GPC['title'];

			$threadman->set_info('skip_moderator_log', true);
			$threadman->set('open', $vbulletin->GPC['open']);
			$threadman->set('sticky', $vbulletin->GPC['sticky']);
			$threadman->set('iconid', $vbulletin->GPC['iconid'], true, false);
			if ($vbulletin->options['similarthreadsearch'])
			{
				require_once(DIR . '/vb/search/core.php');
				$searchcontroller = vB_Search_Core::get_instance()->get_search_controller();
				$similarthreads = $searchcontroller->get_similar_threads(
					$vbulletin->GPC['title'], $threadinfo['threadid']);
				$threadman->set('similar', implode(',', $similarthreads));
			}
		}

		$threadman->set('notes', $vbulletin->GPC['notes']);
		// re-enable mod logging for the title since we don't include it in the other log info
		$threadman->set_info('skip_moderator_log', false);
		$threadman->set('title', $vbulletin->GPC['title']);

		require_once(DIR . '/includes/functions_prefix.php');
		if (can_use_prefix($vbulletin->GPC['prefixid']))
		{
			$threadman->set('prefixid', $vbulletin->GPC['prefixid']);
		}

		($hook = vBulletinHook::fetch_hook('threadmanage_update')) ? eval($hook) : false;
		$threadman->save();

	}

	build_forum_counters($threadinfo['forumid']);

	if ($threadinfo['open'] == 10)
	{
		if ($vbulletin->GPC['redirect'] == 'perm')
		{
			$db->query_write("DELETE FROM " . TABLE_PREFIX . "threadredirect WHERE threadid = $threadinfo[threadid]");
		}
		else if ($vbulletin->GPC['redirect'] == 'expires')
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
					$expires = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('y') + $vbulletin->GPC['period']);
					break;
				case 'm':
				default:
					$expires =  mktime(date('H'), date('i'), date('s'), date('m') + $vbulletin->GPC['period'], date('d'), date('y'));
			}
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "threadredirect
					(threadid, expires)
				VALUES
					($threadinfo[threadid], $expires)
			");
		}
		log_moderator_action($threadinfo, 'thread_edited_visible_x_open_y_sticky_z', array($threadinfo['visible'], intval((bool)$threadinfo['open']), $threadinfo['sticky']));
	}
	else
	{
		log_moderator_action($threadinfo, 'thread_edited_visible_x_open_y_sticky_z', array($vbulletin->GPC['visible'], $vbulletin->GPC['open'], $vbulletin->GPC['sticky']));
	}

	if ($threadinfo['open'] == 10 OR (!$vbulletin->GPC['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')) OR $threaddeleted == 1 OR ($threadinfo['isdeleted'] AND $threaddeleted != -1))
	{
		$vbulletin->url = fetch_seo_url('forum', $foruminfo);
	}
	else
	{
		$threadinfo['title'] = htmlspecialchars_uni($vbulletin->GPC['title']);
		$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	}
	print_standard_redirect('redirect_editthread');
}

// ############################### start merge threads ###############################
if ($_REQUEST['do'] == 'mergethread')
{
	if (($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'], 'candeleteposts')) OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		if (can_moderate($threadinfo['forumid']))
		{
			print_no_permission();
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}

	// check forum permissions for this forum
	if (!can_moderate($threadinfo['forumid'], 'canmanagethreads'))
	{
		print_no_permission();
	}

	// draw nav bar
	$navbits = construct_postings_nav($foruminfo, $threadinfo);

	($hook = vBulletinHook::fetch_hook('threadmanage_mergethread')) ? eval($hook) : false;

	$page_templater = vB_Template::create('threadadmin_mergethread');
		$page_templater->register('threadid', $threadid);
		$page_templater->register('threadinfo', $threadinfo);
	$remove_temp_render = $page_templater->render();
}

// ############################### start do merge threads ###############################
if ($_POST['do'] == 'domergethread')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'mergethreadurl' => TYPE_STR,
		'title'          => TYPE_STR,
		'redirect'       => TYPE_STR,
		'period'         => TYPE_UINT,
		'frame'          => TYPE_STR,
	));

	if (($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'], 'candeleteposts')) OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		if (can_moderate($threadinfo['forumid']))
		{
			print_no_permission();
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}

	// check forum permissions for this forum
	if (!can_moderate($threadinfo['forumid'], 'canmanagethreads'))
	{
		print_no_permission();
	}

	// relative URLs will do bad things here, so don't let them through; thanks Paul! :)
	if (stristr($vbulletin->GPC['mergethreadurl'], 'goto=next'))
	{
		eval(standard_error(fetch_error('mergebadurl')));
	}

	// eliminate everything but the query string
	if (($strpos = strpos($vbulletin->GPC['mergethreadurl'], '?')) !== false)
	{
		$vbulletin->GPC['mergethreadurl'] = substr($vbulletin->GPC['mergethreadurl'], $strpos);
	}
	else
	{
		eval(standard_error(fetch_error('mergebadurl')));
	}

	$search = array(
		'#[\?&](?:threadid|t)=([0-9]+)#',
		'#showthread.php[\?/]([0-9]+)#',
		'#/threads/([0-9]+)#'
	);

	foreach ($search AS $regex)
	{
		if (preg_match($regex, $vbulletin->GPC['mergethreadurl'], $matches))
		{
			$mergethreadid = intval($matches[1]);
			break;
		}
	}

	if (!$mergethreadid)
	{
		if (preg_match('#[\?&](postid|p)=([0-9]+)#', $vbulletin->GPC['mergethreadurl'], $matches))
		{
			$mergepostid = verify_id('post', $matches[2], 0);
			if ($mergepostid == 0)
			{
				// do invalid url
				eval(standard_error(fetch_error('mergebadurl')));
			}

			$postinfo = fetch_postinfo($mergepostid);
			$mergethreadid = $postinfo['threadid'];
		}
		else
		{
			eval(standard_error(fetch_error('mergebadurl')));
		}
	}

	$mergethreadid = verify_id('thread', $mergethreadid);
	$mergethreadinfo = fetch_threadinfo($mergethreadid);
	$mergeforuminfo = fetch_foruminfo($mergethreadinfo['forumid']);

	if ($mergethreadinfo['open'] == 10 OR $mergethreadid == $threadid)
	{
		if (can_moderate($mergethreadinfo['forumid']))
		{
			eval(standard_error(fetch_error('mergebadurl')));
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}

	if (($mergethreadinfo['isdeleted'] AND !can_moderate($mergethreadinfo['forumid'], 'candeleteposts')) OR (!$mergethreadinfo['visible'] AND !can_moderate($mergethreadinfo['forumid'], 'canmoderateposts')))
	{
		if (can_moderate($mergethreadinfo['forumid']))
		{
			print_no_permission();
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}

	// check forum permissions for the merge forum
	$mergeforumperms = fetch_permissions($mergethreadinfo['forumid']);
	if (
		(($mergethreadinfo['postuserid'] != $vbulletin->userinfo['userid']) AND !($mergeforumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']))
			OR
		!($mergeforumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
			OR
		!($mergeforumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
	)
	{
		print_no_permission();
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($mergeforuminfo['forumid'], $mergeforuminfo['password']);

	// get the first post from each thread -- we only need to reindex those
	$thrd_firstpost = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "post
		WHERE threadid = $threadinfo[threadid]
		ORDER BY dateline
		LIMIT 1
	");
	$mrgthrd_firstpost = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "post
		WHERE threadid = $mergethreadinfo[threadid]
		ORDER BY dateline
		LIMIT 1
	");

	($hook = vBulletinHook::fetch_hook('threadmanage_merge_start')) ? eval($hook) : false;

	$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
	$threadman->set_existing($threadinfo);
	$threadman->set('title', $vbulletin->GPC['title']);
	$threadman->set('views', $threadinfo['views'] + $mergethreadinfo['views']);

	// sort out polls
	if ($mergethreadinfo['pollid'] != 0)
	{ // merge thread has poll ...
		if ($threadinfo['pollid'] == 0)
		{ // ... and original thread doesn't
			$threadman->set('pollid', $mergethreadinfo['pollid']);
			$threadcache["$mergethreadinfo[threadid]"]['pollid'] = 0;
		}
		else
		{ // ... and original does
			// if the poll isn't found anywhere else, delete the merge thread's poll
			if (!$poll = $db->query_first_slave("
				SELECT threadid
				FROM " . TABLE_PREFIX . "thread
				WHERE pollid = $mergethreadinfo[pollid] AND
					threadid <> $mergethreadinfo[threadid] AND
					open <> 10
				"))
			{
				$pollman =& datamanager_init('Poll', $vbulletin, ERRTYPE_STANDARD);
				$pollman->set_existing($mergethreadinfo);
				$pollman->delete();
			}
		}
	}

	$threadman->save();

	// Update Post Count if we merge from a counting forum to a non counting or vice-versa.. hidden thread to a visible thread, moderated to visible (and so on)
	// Source Dest  Visible Thread    Hidden Thread
	// Yes    Yes   +hidden           -visible
	// Yes    No    -visible          -visible
	// No     Yes   +visible,+hidden  ~
	// No     No    ~                 ~

	if (($threadinfo['visible'] AND $foruminfo['countposts'] AND ($mergethreadinfo['visible'] != 1 OR ($mergethreadinfo['visible'] == 1 AND !$mergeforuminfo['countposts'])))
			OR
		($mergethreadinfo['visible'] == 1 AND $mergeforuminfo['countposts'] AND ($threadinfo['visible'] != 1 OR ($threadinfo['visible'] == 1 AND !$foruminfo['countposts']))))
	{
		$posts = $db->query_read("
			SELECT userid, threadid
			FROM " . TABLE_PREFIX . "post
			WHERE threadid = $mergethreadinfo[threadid]
				AND visible = 1
				AND userid > 0
		");
		while ($post = $db->fetch_array($posts))
		{
			$set = 0;

			// Visible thread that merges a visible thread from a non counting forum into a counting forum - Increment post counts belonging to visible threads
			// visible thread that merges a moderated or deleted thread into a counting forum - increment post counts belonging to a hidden/deleted source thread
			if ($threadinfo['visible'] AND $foruminfo['countposts'] AND ($mergethreadinfo['visible'] != 1 OR ($mergethreadinfo['visible'] == 1 AND !$mergeforuminfo['countposts'])))
			{
				$set = 1;
			}

			// hidden thread that merges a visible thread from a counting forum
			// OR visible thread that merges a visible thread from a counting forum into a non counting forum
			// decrement post counts belonging to a visible source thread
			else if ($mergethreadinfo['visible'] == 1 AND $mergeforuminfo['countposts'] AND ($threadinfo['visible'] != 1 OR ($threadinfo['visible'] == 1 AND !$foruminfo['countposts'])))
			{
				$set = -1;
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
	}

	// move posts
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "post
		SET threadid = $threadinfo[threadid]
		WHERE threadid = $mergethreadinfo[threadid]
	");

	// kill the cache for the dest thread
	delete_post_cache_threads(array($threadinfo['threadid']));

	// update first post relationships
	if ($thrd_firstpost['dateline'] > $mrgthrd_firstpost['dateline'])
	{
		if (!$threadinfo['visible'])
		{
			// Update original first post to now be moderated, insert moderation record
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "moderation
				(primaryid, type, dateline)
				VALUES
				($thrd_firstpost[postid], 'reply', " . TIMENOW . ")
			");
		}

		// thread being merged into is newer, so the merged thread's first post should become this thread's first post
		$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
		$postman->set_existing($thrd_firstpost);
		$postman->set('parentid', $mrgthrd_firstpost['postid']);

		// Update original first post to now be moderated
		if (!$threadinfo['visible'])
		{
			$postman->set('visible', 0);
		}
		$postman->save();
	}
	else
	{
		if (!$mergethreadinfo['visible'])
		{
			// Change moderation entry for a hidden thread to point to a hidden post
			$db->query_write("
				UPDATE IGNORE " . TABLE_PREFIX . "moderation
				SET primaryid = $threadinfo[firstpostid],
					type = 'reply'
				WHERE primaryid = $mergethreadinfo[threadid]
					AND type = 'thread'
			");
		}

		$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
		$postman->set_existing($mrgthrd_firstpost);
		$postman->set('parentid', $thrd_firstpost['postid']);

		// Update merged thread's first post to be hidden since the thread was
		if (!$mergethreadinfo['visible'])
		{
			$postman->set('visible', 0);
		}
		$postman->save();
	}
	unset($postman);

	$users = array();
	$ratings = $db->query_read("
		SELECT threadrateid, threadid, userid, vote, ipaddress
		FROM " . TABLE_PREFIX . "threadrate
		WHERE threadid IN($mergethreadinfo[threadid], $threadinfo[threadid])
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
			WHERE threadid IN($mergethreadinfo[threadid], $threadinfo[threadid])
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
			$sql[] = "($threadinfo[threadid], $userid, $vote, '" . $db->escape_string($ipaddress) . "')";
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

	// Update redirects
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "thread
		SET pollid = $threadinfo[threadid]
		WHERE open = 10
			AND pollid = $mergethreadinfo[threadid]
	");

	// Update subscribed threads
	$db->query_write("
		UPDATE IGNORE " . TABLE_PREFIX . "subscribethread
		SET threadid = $threadinfo[threadid]
		WHERE threadid = $mergethreadinfo[threadid]
	");

	// We had multiple subscriptions so remove all but the main one now
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "subscribethread
		WHERE threadid = $mergethreadinfo[threadid]
	");

	// Update Moderation Log entries
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "moderatorlog
		SET threadid = $threadinfo[threadid]
		WHERE threadid = $mergethreadinfo[threadid]
	");

	if ($mergethreadinfo['forumid'] != $threadinfo['forumid'])
	{
		// update canview status of thread subscriptions
		update_subscriptions(array('threadids' => array($threadinfo['threadid'])));
	}

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

	$merge_thread =& datamanager_init('Thread', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
	$merge_thread->set_existing($mergethreadinfo);
	if ($vbulletin->GPC['redirect'] AND $vbulletin->GPC['redirect'] != 'none')
	{
		$merge_thread->set('open', 10);
		$merge_thread->set('pollid', $threadinfo['threadid']);
		$merge_thread->set('visible', 1);
		$merge_thread->set('dateline', TIMENOW);
		$merge_thread->save();
		if ($vbulletin->GPC['redirect'] == 'expires')
		{
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "threadredirect
					(threadid, expires)
				VALUES
					($mergethreadinfo[threadid], $expires)
			");
		}
	}
	else
	{
		// remove remnants of merge thread
		$merge_thread->delete(false, true, NULL, false);
	}
	unset($merge_thread);

	build_thread_counters($threadinfo['threadid']);
	build_forum_counters($threadinfo['forumid']);
	if ($mergethreadinfo['forumid'] != $threadinfo['forumid'])
	{
		build_forum_counters($mergethreadinfo['forumid']);
	}

	vB_ActivityStream_Populate_Forum_Thread::rebuild_thread(array($threadinfo['threadid'], $mergethreadinfo['threadid']));

	log_moderator_action($threadinfo, 'thread_merged_with_x', $mergethreadinfo['title']);

	($hook = vBulletinHook::fetch_hook('threadmanage_merge_complete')) ? eval($hook) : false;

	$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	print_standard_redirect('redirect_mergethread');

}

// ############################### start stick / unstick thread ###############################
if ($_POST['do'] == 'stick')
{
	if (($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'], 'candeleteposts')) OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		if (can_moderate($threadinfo['forumid']))
		{
			print_no_permission();
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}

	if (!can_moderate($threadinfo['forumid'], 'canmanagethreads'))
	{
		print_no_permission();
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	// handles mod log
	$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
	$threadman->set_existing($threadinfo);
	$threadman->set('sticky', ($threadman->fetch_field('sticky') == 1 ? 0 : 1));

	($hook = vBulletinHook::fetch_hook('threadmanage_stickunstick')) ? eval($hook) : false;
	$threadman->save();

	if ($threadinfo['sticky'])
	{
		$action = $vbphrase['unstuck'];
	}
	else
	{
		$action = $vbphrase['stuck'];
	}

	$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	print_standard_redirect(array('redirect_sticky',$action), true, true);
}

// ############################### start remove redirects ###############################
if ($_POST['do'] == 'removeredirect')
{
	if (!can_moderate($threadinfo['forumid'], 'canmanagethreads'))
	{
		print_no_permission();
	}

	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	// Really need thread.* for set_existing -> delete_thread()
	$redirects = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "thread
		WHERE open = 10 AND pollid = $threadid
	");
	while ($redirect = $db->fetch_array($redirects))
	{
		verify_forum_password($redirect['forumid'], $vbulletin->forumcache["$redirect[forumid]"]['password']);

		$old_redirect =& datamanager_init('Thread', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
		$old_redirect->set_existing($redirect);
		$old_redirect->delete(false, true);
		unset($old_redirect);
	}

	($hook = vBulletinHook::fetch_hook('threadmanage_removeredirect')) ? eval($hook) : false;

	$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	print_standard_redirect('redirects_removed', true, true);
}

// ############################### start manage post ###############################
if ($_POST['do'] == 'domanagepost')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'poststatus' => TYPE_UINT,
		'reason'		 => TYPE_NOHTML,
	));


	if (!can_moderate($threadinfo['forumid'], 'candeleteposts'))
	{
		print_no_permission();
	}

	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	if ($vbulletin->GPC['poststatus'] == 1)
	{
		// undelete
		$postdeleted = -1;
	}
	else if ($vbulletin->GPC['poststatus'] == 2 AND can_moderate($threadinfo['forumid'], 'canremoveposts'))
	{
		// remove
		$postdeleted = 1;
	}
	else
	{
		// leave as is
		$postdeleted = 0;
	}

	if ($postdeleted != 1)
	{
		$deletionman =& datamanager_init('Deletionlog_ThreadPost', $vbulletin, ERRTYPE_STANDARD, 'deletionlog');
		$deletioninfo = array('type' => 'post', 'primaryid' => $postid);
		$deletionman->set_existing($deletioninfo);
		$deletionman->set('reason', $vbulletin->GPC['reason']);
		$deletionman->save();
		unset($deletionman, $deletioninfo);
	}

	if ($postdeleted == -1)
	{
		undelete_post($postid, $foruminfo['countposts'], $postinfo, $threadinfo);
	}
	else if ($postdeleted == 1)
	{
		$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
		$postman->set_existing($postinfo);
		$postman->delete($foruminfo['countposts'], $threadinfo['threadid'], 1);
		unset($postman);
		build_thread_counters($threadinfo['threadid']);
		build_forum_counters($threadinfo['forumid']);
	}

	($hook = vBulletinHook::fetch_hook('threadmanage_domanagepost')) ? eval($hook) : false;

	if ($postdeleted != 1)
	{
		$vbulletin->url = fetch_seo_url('thread', $threadinfo, array('p' => $postid)) . "#post$postid";
	}
	else
	{
		$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	}

	print_standard_redirect('redirect_post_manage');
}

// ############################### start moderate thread ###############################
if ($_POST['do'] == 'moderatethread')
{
	if (!can_moderate($threadinfo['forumid'], 'canmoderateposts'))
	{
		print_no_permission();
	}

	if ($threadinfo['open'] != 10)
	{
		if ($threadinfo['visible'] == 0)
		{
			approve_thread($threadid, $foruminfo['countposts'], true, $threadinfo);
			build_forum_counters($threadinfo['forumid']);

			print_standard_redirect('thread_approved');
		}
		else
		{
			unapprove_thread($threadid, $foruminfo['countposts'], true, $threadinfo);
			build_forum_counters($threadinfo['forumid']);

			print_standard_redirect('thread_unapproved');
		}
	}
}

// ############################### all done, do shell template ###############################

if (!empty($page_templater))
{
	// draw navbar
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('threadmanage_complete')) ? eval($hook) : false;

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
