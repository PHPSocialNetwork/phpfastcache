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
if ($_REQUEST['do'] == 'inlinemerge' OR $_POST['do'] == 'doinlinemerge')
{
	define('GET_EDIT_TEMPLATES', true);
}
define('THIS_SCRIPT', 'inlinemod');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('threadmanage', 'posting', 'inlinemod');

// get special data templates from the datastore
$specialtemplates = array();

$globaltemplates = array(
	'threadadmin_authenticate'
);

$actiontemplates = array(
	'inlinedelete' => array('socialgroups_deletemessages')
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_socialgroup.php');
require_once(DIR . '/includes/modfunctions.php');
require_once(DIR . '/includes/functions_log_error.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

//not sure why "THIS_SCRIPT" is inlinemod instead of group_inlinemod.  Override
//so that verify works.
verify_forum_url('group_inlinemod');

if (($current_memory_limit = ini_size_to_bytes(@ini_get('memory_limit'))) < 128 * 1024 * 1024 AND $current_memory_limit > 0)
{
	@ini_set('memory_limit', 128 * 1024 * 1024);
}
@set_time_limit(0);

if (!$vbulletin->userinfo['userid'] OR !$vbulletin->options['socnet_groups_msg_enabled'])
{
	print_no_permission();
}

$itemlimit = 200;

// This is a list of ids that were checked on the page we submitted from
$vbulletin->input->clean_array_gpc('r', array(
	'userid'                    => TYPE_UINT,
	'inline_discussion'         => TYPE_BOOL
));

// Whether we are inlining from the discussion view.  Used for aesthetics.
$inline_discussion = $vbulletin->GPC['inline_discussion'];
$inline_cookie = ($inline_discussion ? 'vbulletin_inlinegdiscussion' : 'vbulletin_inlinegmessage');
$messagelist = ($inline_discussion ? 'gdiscussionlist' : 'gmessagelist');

$vbulletin->input->clean_array_gpc('p', array(
	$messagelist               => TYPE_ARRAY_KEYS_INT
));

$vbulletin->input->clean_array_gpc('c', array(
	$inline_cookie => TYPE_STR,
));

if (!empty($vbulletin->GPC["$inline_cookie"]))
{
	$gmessagelist = explode('-', $vbulletin->GPC["$inline_cookie"]);
	$gmessagelist = $vbulletin->input->clean($gmessagelist, TYPE_ARRAY_UINT);

	$vbulletin->GPC['gmessagelist'] = array_unique(array_merge($gmessagelist, $vbulletin->GPC["$messagelist"]));
}

if (!$vbulletin->userinfo['userid'])
{
	print_no_permission();
}

switch ($_POST['do'])
{
	case 'doinlinedelete':
	{
		$inline_mod_authenticate = true;
		break;
	}
	default:
	{
		$inline_mod_authenticate = false;
		($hook = vBulletinHook::fetch_hook('group_inlinemod_authenticate_switch')) ? eval($hook) : false;
	}
}

if ($inline_mod_authenticate AND !inlinemod_authenticated())
{
	show_inline_mod_login(false, true);
}

switch ($_POST['do'])
{
	case 'inlinedelete':
	case 'inlineapprove':
	case 'inlineunapprove':
	case 'inlineundelete':

		if (empty($vbulletin->GPC['gmessagelist']))
		{
			standard_error(fetch_error(($inline_discussion ? 'you_did_not_select_any_valid_discussions' : 'you_did_not_select_any_valid_messages')));
		}

		if (count($vbulletin->GPC['gmessagelist']) > $itemlimit)
		{
			standard_error(fetch_error(($inline_discussion ? 'you_are_limited_to_working_with_x_discussions' : 'you_are_limited_to_working_with_x_messages'), $itemlimit));
		}

		if ($vbulletin->GPC['userid'])
		{
			$userinfo = fetch_userinfo($vbulletin->GPC['userid'], 1);
		}

		$messageids = $vbulletin->GPC['gmessagelist'];
		break;

	case 'doinlinedelete':

		$vbulletin->input->clean_array_gpc('p', array(
			'messageids' => TYPE_STR,
		));
		$messageids = explode(',', $vbulletin->GPC['messageids']);
		$messageids = $vbulletin->input->clean($messageids, TYPE_ARRAY_UINT);

		if (count($messageids) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_messages', $itemlimit));
		}
		break;
}

// set forceredirect for IIS
$forceredirect = (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);

$messagelist = $messagearray = $discussionarray = $userlist = $discussionlist = $grouplist = $ownerlist = array();

($hook = vBulletinHook::fetch_hook('group_inlinemod_start')) ? eval($hook) : false;

// #######################################################################
if ($_POST['do'] == 'clearmessage')
{
	setcookie($inline_cookie, '', TIMENOW - 3600, '/');

	print_standard_redirect('redirect_inline_messagelist_cleared', true, $forceredirect);
}

// #######################################################################
if ($_POST['do'] == 'inlineapprove' OR $_POST['do'] == 'inlineunapprove')
{
	$insertrecords = array();

	$approve = $_POST['do'] == 'inlineapprove' ? true : false;

	// Validate records
	$messages = ($inline_discussion ? ($approve ? verify_discussions($messageids, false, true, false)
												: verify_discussions($messageids, true, false, true))
									: ($approve ? verify_messages($messageids, false, true, false)
												: verify_messages($messageids, true, false, true)));

	if ($messages)
	{
		while ($messages AND ($message = $db->fetch_array($messages)))
		{
			$discussion = fetch_socialdiscussioninfo($message['discussionid']);
			$group = fetch_socialgroupinfo($discussion['groupid']);
			$discussion['is_group_owner'] = $message['is_group_owner'] = ($group['creatoruserid'] == $vbulletin->userinfo['userid']);

			// whether the message is a discussion
			$is_discussion = ($message['gmid'] == $discussion['firstpostid']);

			// if moderating from discussions there should not be any non discussion messages
			if ($inline_discussion AND !$is_discussion)
			{
				continue;
			}

			// check permissions
			if ($is_discussion)
			{
				if (!fetch_socialgroup_modperm('canmoderatediscussions', $group))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_moderate_discussions'));
				}
				else if ($message['state'] == 'deleted' AND !fetch_socialgroup_modperm('canundeletediscussions', $group))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_discussions'));
				}
			}
			else
			{
				if (!fetch_socialgroup_modperm('canmoderategroupmessages', $group))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_moderate_messages'));
				}
				else if ($message['state'] == 'deleted' AND !fetch_socialgroup_modperm('canundeletegroupmessages', $group))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_messages'));
				}
			}

			if ($is_discussion)
			{
				$discussion['group_name'] = $group['name'];
				$discussion['is_group_owner'] = $group['is_owner'];
				$discussionarray["$message[gmid]"] = $discussion;
			}
			else
			{
				$message['group_name'] = $group['name'];
				$message['discussion_name'] = $discussion['title'];
				$message['is_group_owner'] = $group['is_owner'];
				$messagearray["$message[gmid]"] = $message;
			}

			$discussionlist["$message[discussionid]"] = true;
			$grouplist["$discussion[groupid]"] = true;
			$ownerlist["$group[creatoruserid]"] = true;

			if (!$approve)
			{
				$type = ($is_discussion) ? 'groupdiscussion' : 'groupmessage';
				$insertrecords[] = "($message[gmid], '$type', " . TIMENOW . ")";
			}
		}
	}

	if (empty($messagearray) AND empty($discussionarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	$itemkeys = array_merge(array_keys($messagearray), array_keys($discussionarray));

	// Set message state
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "groupmessage
		SET state = '" . ($approve ? 'visible' : 'moderation') . "'
		WHERE gmid IN (" . implode(',', $itemkeys) . ")
	");

	if ($approve)
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "moderation
			WHERE primaryid IN(" . implode(',', $itemkeys) . ")
				AND type = 'groupmessage'
		");
	}
	else	// Unapprove
	{
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "moderation
				(primaryid, type, dateline)
			VALUES
				" . implode(',', $insertrecords) . "
		");

		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "deletionlog
			WHERE type = 'groupmessage' AND
				primaryid IN(" . implode(',', $itemkeys) . ")
		");
	}

	// build discussion counters seperately from groups so we don't rebuild groups for every discussion
	foreach (array_keys($discussionlist) AS $discussionid)
	{
		build_discussion_counters($discussionid);
	}

	// group counters are only built from current discussion counters
	foreach (array_keys($grouplist) AS $groupid)
	{
		build_group_counters($groupid);
	}

	// update owner moderation count
	foreach (array_keys($ownerlist) AS $owner)
	{
		update_owner_pending_gm_count($owner);
	}

	foreach ($messagearray AS $message)
	{
		if (!$message['is_group_owner'])
		{
			log_moderator_action($message,
				($approve ? 'gm_by_x_in_y_for_z_approved' : 'gm_by_x_in_y_for_z_unapproved'),
				array($message['postusername'], $message['discussion_name'], $message['group_name'])
			);
		}
	}

	foreach ($discussionarray AS $discussion)
	{
		if (!$discussion['is_group_owner'])
		{
			log_moderator_action($message,
				($approve ? 'discussion_by_x_for_y_approved' : 'discussion_by_x_for_y_unapproved'),
				array($discussion['postusername'], $discussion['group_name'])
			);
		}
	}

	setcookie($inline_cookie, '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('group_inlinemod_approveunapprove')) ? eval($hook) : false;

	if ($inline_discussion)
	{
		if ($approve)
		{
			print_standard_redirect('redirect_inline_approveddiscussions', true, $forceredirect);
		}
		else
		{
			print_standard_redirect('redirect_inline_unapproveddiscussions', true, $forceredirect);
		}
	}
	else
	{
		if ($approve)
		{
			print_standard_redirect('redirect_inline_approvedmessages', true, $forceredirect);
		}
		else
		{
			print_standard_redirect('redirect_inline_unapprovedmessages', true, $forceredirect);
		}
	}
}

// #######################################################################
if ($_POST['do'] == 'inlinedelete')
{
	$checked = array('delete' => 'checked="checked"');

	// Validate Messages
	$messages = ($inline_discussion ? verify_discussions($messageids, true, true, true)
									: verify_messages($messageids, true, true, true));

	$canremovemessages = $candeletemessages = false;

	if ($messages)
	{
		// Find which delete options are available to the user
		while ($messages AND ($message = $db->fetch_array($messages)))
		{
			$discussion = fetch_socialdiscussioninfo($message['discussionid']);
			$group = fetch_socialgroupinfo($discussion['groupid']);

			if ($inline_discussion AND ($message['gmid'] != $discussion['firstpostid']))
			{
				// if inlining discussions, don't need to validate messages that are not first posts
				continue;
			}

			// check if message is first post of a discussion
			if (($message['gmid'] == $discussion['firstpostid']))
			{
				if ($message['state'] == 'moderation' AND !fetch_socialgroup_modperm('canmoderatediscussions', $group))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_discussions'));
				}
				else if ($message['state'] == 'deleted' AND !fetch_socialgroup_modperm('canundeletediscussions', $group))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_discussions'));
				}

				$canremovemessage = fetch_socialgroup_modperm('canremovediscussions', $group);
				$canremovemessages = ($canremovemessages OR $canremovemessage);
				$candeletemessage = (fetch_socialgroup_modperm('candeletediscussions', $group) OR ($message['state'] == 'visible' AND $message['postuserid'] == $vbulletin->userinfo['userid'] AND (fetch_socialgroup_perm('canmanagediscussions') OR (fetch_socialgroup_perm('canmanagemessages') AND $discussion['visible'] <= 1))));
				$candeletemessages = ($candeletemessages OR $candeletemessage);

				if (!$candeletemessage AND !$canremovemessage)
				{
					standard_error(fetch_error('you_do_not_have_permission_to_delete_selected_discussions'));
				}

				$discussion_selected = true;
				$discussionarray["$message[discussionid]"] = true;
			}
			else
			{
				if ($message['state'] == 'moderation' AND !fetch_socialgroup_modperm('canmoderategroupmessages', $group))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_messages'));
				}
				else if ($message['state'] == 'deleted' AND !fetch_socialgroup_modperm('canundeletegroupmessages', $group))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_messages'));
				}

				// accumulate applicable delete options for all messages
				$canremovemessage = fetch_socialgroup_modperm('canremovegroupmessages', $group);
				$canremovemessages = ($canremovemessages OR $canremovemessage);
				$candeletemessage = (fetch_socialgroup_modperm('candeletegroupmessages', $group) OR ($message['state'] == 'visible' AND $message['postuserid'] == $vbulletin->userinfo['userid'] AND fetch_socialgroup_perm('canmanagemessages')));
				$candeletemessages = ($candeletemessages OR $candeletemessage);

				if (!$candeletemessage AND !$canremovemessage)
				{
					standard_error(fetch_error('you_do_not_have_permission_to_delete_selected_messages'));
				}
			}

			$messagearray["$message[gmid]"] = $message;
			$discussionlist["$message[discussionid]"] = true;
			$grouplist["$discussion[groupid]"] = true;
		}
	}

	// Check appropriate default
	if (!$candeletemessages)
	{
		$checked['remove'] = 'checked="checked"';
	}
	else
	{
		$checked['delete'] = 'checked="checked"';
	}

	if (empty($messagearray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	$messagecount = count($messagearray);
	$discussioncount = count($discussionlist);
	$groupcount = count($grouplist);

	// implode messageids to pass in form
	$messageids = implode(',', $messageids);

	// after delete, redirect to group if current discussion will be deleted
	if (!$inline_discussion AND $discussion_selected)
	{
		$vbulletin->input->clean_array_gpc('r', array(
			'discussionid'              => TYPE_UINT,
			'groupid'                     => TYPE_UINT
		));

		if (isset($discussionarray[$vbulletin->GPC['discussionid']]))
		{
			$vbulletin->url = fetch_seo_url('group', $group);
		}
	}

	$url = $vbulletin->url;

	$navbits = array('' => ($inline_discussion ? $vbphrase['delete_discussions'] : $vbphrase['delete_messages']));
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('group_inlinemod_delete')) ? eval($hook) : false;

	$templater = vB_Template::create('socialgroups_deletemessages');
		$templater->register_page_templates();
		$templater->register('candeletemessages', $candeletemessages);
		$templater->register('canremovemessages', $canremovemessages);
		$templater->register('checked', $checked);
		$templater->register('discussioncount', $discussioncount);
		$templater->register('discussion_selected', $discussion_selected);
		$templater->register('gmids', $gmids);
		$templater->register('group', $group);
		$templater->register('groupcount', $groupcount);
		$templater->register('inline_discussion', $inline_discussion);
		$templater->register('messagecount', $messagecount);
		$templater->register('messageids', $messageids);
		$templater->register('navbar', $navbar);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('url', $url);
	print_output($templater->render());
}

// #######################################################################
if ($_POST['do'] == 'doinlinedelete')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deletetype'   => TYPE_UINT, // 1 - Soft Deletion, 2 - Physically Remove
		'deletereason' => TYPE_NOHTMLCOND,
	));

	$physicaldel = ($vbulletin->GPC['deletetype'] == 2) ? true : false;

	// Validate Messages
	$messages = ($inline_discussion ? verify_discussions($messageids, true, true, true)
									: verify_messages($messageids, true, true, true));

	if ($messages)
	{
		while ($message = $db->fetch_array($messages))
		{
			$discussion = fetch_socialdiscussioninfo($message['discussionid']);
			$group = fetch_socialgroupinfo($discussion['groupid']);
			$discussion['is_group_owner'] = $message['is_group_owner'] = $group['is_owner'];

			// don't delete message if it's part of a discussion being hard deleted
			if (isset($discussionarray["$discussion[discussionid]"]))
			{
				continue;
			}

			// if moderating discussions don't delete messages that are not first posts
			if ($inline_discussion AND $message['gmid'] != $discussion['firstpostid'])
			{
				continue;
			}

			if (($message['gmid'] == $discussion['firstpostid']))
			{
				if ($message['state'] == 'moderation')
				{
					if (!fetch_socialgroup_modperm('canmoderatediscussions', $group))
					{
						standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_discussions'));
					}
				}

				if ($physicaldel)
				{
					if (!can_moderate(0, 'canremovediscussions'))
					{
						standard_error(fetch_error('you_do_not_have_permission_to_hard_delete_discussions'));
					}

					$discussion['group_name'] = $group['name'];
					$discussionarray["$discussion[discussionid]"] = $discussion;
					$grouplist["$discussion[groupid]"] = true;
					continue;
				}
				else
				{
					$candeletemessage = (
						fetch_socialgroup_modperm('candeletediscussions', $group)
						OR (
							$message['state'] == 'visible'
							AND $message['postuserid'] == $vbulletin->userinfo['userid']
							AND fetch_socialgroup_perm('canmanagemessages')
						)
					);

					if (!$candeletemessage)
					{
						standard_error(fetch_error('you_do_not_have_permission_to_soft_delete_discussions'));
					}
				}
			}
			else
			{
				if ($message['state'] == 'moderation' AND !fetch_socialgroup_modperm('canmoderategroupmessages', $group))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_messages'));
				}

				if ($physicaldel AND !can_moderate(0, 'canremovegroupmessages'))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_hard_delete_messages'));
				}

				// check user has permission to delete the message
				$candeletemessage = (fetch_socialgroup_modperm('candeletegroupmessages', $group) OR ($message['state'] == 'visible' AND $message['postuserid'] == $vbulletin->userinfo['userid'] AND fetch_socialgroup_perm('canmanagemessages')));

				if (!$candeletemessage)
				{
					standard_error(fetch_error('you_do_not_have_permission_to_soft_delete_messages'));
				}
			}

			$message['group_name'] = $group['name'];
			$message['discussion_name'] = $discussion['title'];
			$message['groupid'] = $discussion['groupid'];

			$messagearray["$message[gmid]"] = $message;
			$discussionlist["$message[discussionid]"] = true;
			$grouplist["$discussion[groupid]"] = true;
			$ownerlist["$group[creatoruserid]"] = true;
		}
	}

	// Skip messages that are in discussions that will be hard deleted
	if (sizeof($discussionarray))
	{
		foreach($messagearray as $gmid => $message)
		{
			if (isset($discussionarray["$message[discussion]"]))
			{
				unset($messagearray["$gmid"]);
			}
		}
	}

	if (empty($messagearray) AND empty($discussionarray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}
	require_once(DIR . '/vb/search/indexcontroller/queue.php');

	// Delete messages
	foreach($messagearray AS $gmid => $message)
	{
		$dataman =& datamanager_init('GroupMessage', $vbulletin, ERRTYPE_SILENT);
		$dataman->set_existing($message);
		$dataman->set_info('hard_delete', $physicaldel);
		$dataman->set_info('reason', $vbulletin->GPC['deletereason']);
		$dataman->set_info('skip_build_counters', true);
		$dataman->delete();
		unset($dataman);
		vB_Search_Indexcontroller_Queue::indexQueue('vBForum', 'SocialGroupMessage', 'delete',
			$gmid);
	}

	// Delete discussions
	foreach($discussionarray AS $discussionid => $discussion)
	{
		$dataman =& datamanager_init('Discussion', $vbulletin, ERRTYPE_SILENT);
		$dataman->set_existing($discussion);
		$dataman->set_info('hard_delete', $physicaldel);
		$dataman->set_info('reason', $vbulletin->GPC['deletereason']);
		$dataman->set_info('skip_build_counters', true);
		$dataman->delete();
		unset($dataman);
	}

	// build discussion counters seperately from groups so we don't rebuild groups for every discussion
	foreach(array_keys($discussionlist) AS $discussionid)
	{
		if (!isset($discussionarray["$discussionid"]))
		{
			build_discussion_counters($discussionid);
		}
	}

	// group counters are only built from current discussion counters
	foreach(array_keys($grouplist) AS $groupid)
	{
		build_group_counters($groupid);
	}

	// update owner moderation count
	foreach (array_keys($ownerlist) AS $owner)
	{
		update_owner_pending_gm_count($owner);
	}

	foreach($discussionarray AS $discussion)
	{
		if (!$discussion['is_group_owner'])
		{
			log_moderator_action($discussion,
				($physicaldel ? 'discussion_by_x_for_y_removed' : 'discussion_by_x_for_y_soft_deleted'),
				array($discussion['postusername'], $discussion['group_name'])
			);
		}
	}

	foreach ($messagearray AS $message)
	{
		if (!$message['is_group_owner'])
		{
			log_moderator_action($message,
				($physicaldel ? 'gm_by_x_in_y_for_z_removed' : 'gm_by_x_in_y_for_z_soft_deleted'),
				array($message['postusername'], $message['discussion_name'], $message['group_name'])
			);
		}
	}

	// empty cookie
	setcookie($inline_cookie, '', TIMENOW - 3600, '/');

	if ($physicaldel AND !$inline_discussion)
	{
		parse_str($vbulletin->input->parse_url($vbulletin->url, PHP_URL_QUERY), $args);

		if ($args['gmid'] AND isset($messagearray[$args['gmid']]))
		{
			// check if the discussion does still exist --- this read query must go to MASTER is we might have just deleted that discussion
			if ($discussion = $db->query_first("SELECT discussionid FROM " . TABLE_PREFIX . "discussion WHERE discussionid = " . intval($messagearray[$args['gmid']]['discussionid'])))
			{
				// discussion does exist, redirect to discussion
				$vbulletin->url = fetch_seo_url('groupdiscussion', $messagearray[$args['gmid']]);
			}
			else
			{
				// discussion does not exist any longer, redirect to group
				$vbulletin->url = fetch_seo_url('group', $messagearray[$args['gmid']], 'groupid', 'group_name');
			}
		}
	}

	($hook = vBulletinHook::fetch_hook('group_inlinemod_dodelete')) ? eval($hook) : false;

	$redirect_message = ($inline_discussion ? 'redirect_inline_deleteddiscussions' : 'redirect_inline_deletedmessages');
	print_standard_redirect($redirect_message, true, $forceredirect);
}

// #######################################################################
if ($_POST['do'] == 'inlineundelete')
{
	// Validate Messages
	$messages = ($inline_discussion ? verify_discussions($messageids, false, false, true)
									: verify_messages($messageids, false, false, true));
	require_once(DIR . '/vb/search/indexcontroller/queue.php');

	if ($messages)
	{
		while ($message = $db->fetch_array($messages))
		{
			$discussion = fetch_socialdiscussioninfo($message['discussionid']);
			$group = fetch_socialgroupinfo($discussion['groupid']);
			$message['is_group_owner'] = ($group['creatoruserid'] == $vbulletin->userinfo['userid']);

			if ($message['gmid'] == $discussion['firstpostid'])
			{
				if (!fetch_socialgroup_modperm('canundeletediscussions'))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_discussions'));
				}

				$message['firstpost'] = true;
			}
			else
			{
				if (!fetch_socialgroup_modperm('canundeletegroupmessages', $group))
				{
					standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_messages'));
				}

				$message['firstpost'] = false;
			}

			$message['group_name'] = $group['name'];
			$message['discussion_name'] = $discussion['title'];

			$messagearray["$message[gmid]"] = $message;
			$discussionlist["$discussion[discussionid]"] = true;
			$grouplist["$group[groupid]"] = true;
		}
	}

	if (empty($messagearray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	require_once(DIR . '/vb/search/indexcontroller/queue.php');

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "deletionlog
		WHERE type = 'groupmessage' AND
			primaryid IN(" . implode(',', array_keys($messagearray)) . ")
	");

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "groupmessage
		SET state = 'visible'
		WHERE gmid IN(" . implode(',', array_keys($messagearray)) . ")
	");
   foreach (array_keys($messagearray) as $gmid){
		vB_Search_Indexcontroller_Queue::indexQueue('vBForum', 'SocialGroupMessage', 'index',
			$gmid, null, null);
   }
	foreach(array_keys($discussionlist) AS $discussionid)
	{
		build_discussion_counters($discussionid);
	}

	foreach(array_keys($grouplist) AS $groupid)
	{
		build_group_counters($groupid);
	}

	foreach ($messagearray AS $message)
	{
		if (!$message['is_group_owner'])
		{
			if ($message['firstpost'])
			{
				log_moderator_action($message, 'discussion_by_x_for_y_undeleted',
					array($message['postusername'], $message['group_name'])
				);
			}
			else
			{
				log_moderator_action($message, 'gm_by_x_in_y_for_z_undeleted',
					array($message['postusername'], $message['discussion_name'], $message['group_name'])
				);
			}
		}
	}

	// empty cookie
	setcookie($inline_cookie, '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('group_inlinemod_undelete')) ? eval($hook) : false;

	if ($inline_discussion)
	{
		print_standard_redirect('redirect_inline_undeleteddiscussions', true, $forceredirect);
	}
	else
	{
		print_standard_redirect('redirect_inline_undeletedmessages', true, $forceredirect);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # SVN: $Revision: 63865 $
|| ####################################################################
\*======================================================================*/
