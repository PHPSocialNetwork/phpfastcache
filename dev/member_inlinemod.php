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
define('THIS_SCRIPT', 'member_inlinemod');
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
	'inlinedelete' => array('memberinfo_deletemessages')
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_visitormessage.php');
require_once(DIR . '/includes/modfunctions.php');
require_once(DIR . '/includes/functions_log_error.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (($current_memory_limit = ini_size_to_bytes(@ini_get('memory_limit'))) < 128 * 1024 * 1024 AND $current_memory_limit > 0)
{
	@ini_set('memory_limit', 128 * 1024 * 1024);
}
@set_time_limit(0);

$itemlimit = 200;

// This is a list of ids that were checked on the page we submitted from
$vbulletin->input->clean_array_gpc('p', array(
	'vmessagelist' => TYPE_ARRAY_KEYS_INT,
	'userid'       => TYPE_UINT,
));

$vbulletin->input->clean_array_gpc('c', array(
	'vbulletin_inlinevmessage' => TYPE_STR,
));

if (!empty($vbulletin->GPC['vbulletin_inlinevmessage']))
{
	$vmessagelist = explode('-', $vbulletin->GPC['vbulletin_inlinevmessage']);
	$vmessagelist = $vbulletin->input->clean($vmessagelist, TYPE_ARRAY_UINT);

	$vbulletin->GPC['vmessagelist'] = array_unique(array_merge($vmessagelist, $vbulletin->GPC['vmessagelist']));
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
		($hook = vBulletinHook::fetch_hook('member_inlinemod_authenticate_switch')) ? eval($hook) : false;
	}
}

if ($inline_mod_authenticate AND !inlinemod_authenticated())
{
	show_inline_mod_login();
}

switch ($_POST['do'])
{
	case 'inlinedelete':
	case 'inlineapprove':
	case 'inlineunapprove':
	case 'inlineundelete':

		if (empty($vbulletin->GPC['vmessagelist']))
		{
			standard_error(fetch_error('you_did_not_select_any_valid_messages'));
		}

		if (count($vbulletin->GPC['vmessagelist']) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_messages', $itemlimit));
		}

		if ($vbulletin->GPC['userid'])
		{
			$userinfo = fetch_userinfo($vbulletin->GPC['userid'], 1);
		}

		$messageids = implode(', ', $vbulletin->GPC['vmessagelist']);
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

$messagelist = $messagearray = $userlist = array();

($hook = vBulletinHook::fetch_hook('member_inlinemod_start')) ? eval($hook) : false;

if ($_POST['do'] == 'clearmessage')
{
	setcookie('vbulletin_inlinevmessage', '', TIMENOW - 3600, '/');

	print_standard_redirect('redirect_inline_messagelist_cleared', true, $forceredirect);
}

if ($_POST['do'] == 'inlineapprove' OR $_POST['do'] == 'inlineunapprove')
{
	$insertrecords = array();

	$approve = $_POST['do'] == 'inlineapprove' ? true : false;

	// Validate records
	$messages = $db->query_read_slave("
		SELECT visitormessage.vmid, visitormessage.state, visitormessage.userid, visitormessage.dateline,
			visitormessage.postuserid, visitormessage.postusername,
			user.username AS profile_username
		FROM " . TABLE_PREFIX . "visitormessage AS visitormessage
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (visitormessage.userid = user.userid)
		WHERE vmid IN ($messageids)
		 AND visitormessage.state IN (" . ($approve ? "'moderation'" : "'visible', 'deleted'") . ")
	");
	while ($message = $db->fetch_array($messages))
	{
		// Check permissions.....
		$userinfo =& $message;
		if ($message['state'] == 'deleted' AND !fetch_visitor_message_perm('canundeletevisitormessages', $userinfo, $message))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_messages'));
		}
		else if (!fetch_visitor_message_perm('canmoderatevisitormessages', $userinfo, $message))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_moderate_messages'));
		}

		$messagearray["$message[vmid]"] = $message;
		$userlist["$message[userid]"] = true;

		if (!$approve)
		{
			$insertrecords[] = "($message[vmid], 'visitormessage', " . TIMENOW . ")";
		}
	}

	if (empty($messagearray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	// Set message state
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "visitormessage
		SET state = '" . ($approve ? 'visible' : 'moderation') . "'
		WHERE vmid IN (" . implode(',', array_keys($messagearray)) . ")
	");

	if ($approve)
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "moderation
			WHERE primaryid IN(" . implode(',', array_keys($messagearray)) . ")
				AND type = 'visitormessage'
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
			WHERE type = 'visitormessage' AND
				primaryid IN(" . implode(',', array_keys($messagearray)) . ")
		");
	}

	if (can_moderate(0, 'canmoderatevisitormessages'))
	{
		foreach ($messagearray AS $message)
		{
			log_moderator_action($message,
				($approve ? 'vm_by_x_for_y_approved' : 'vm_by_x_for_y_unapproved'),
				array($message['postusername'], $message['profile_username'])
			);
		}
	}

	foreach (array_keys($userlist) AS $userid)
	{
		build_visitor_message_counters($userid);
	}

	setcookie('vbulletin_inlinevmessage', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('member_inlinemod_approveunapprove')) ? eval($hook) : false;

	if ($approve)
	{
		print_standard_redirect('redirect_inline_approvedmessages', true, $forceredirect);
	}
	else
	{
		print_standard_redirect('redirect_inline_unapprovedmessages', true, $forceredirect);
	}
}

if ($_POST['do'] == 'inlinedelete')
{
	$show['removemessagets'] = false;
	$show['deletemessages'] = false;
	$show['deleteoption'] = false;
	$checked = array('delete' => 'checked="checked"');

	// Validate Messages
	$messages = $db->query_read_slave("
		SELECT visitormessage.vmid, visitormessage.state, visitormessage.userid, visitormessage.dateline, visitormessage.postuserid
		FROM " . TABLE_PREFIX . "visitormessage AS visitormessage
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (visitormessage.userid = user.userid)
		WHERE vmid IN ($messageids)
	");
	while ($message = $db->fetch_array($messages))
	{
		$userinfo =& $message;

		$canmanage = ($message['userid'] == $vbulletin->userinfo['userid'] AND $vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['canmanageownprofile']);
		$canmoderatemessages = (can_moderate(0, 'canmoderatevisitormessages') OR $canmanage);
		$candeletemessages = (can_moderate(0, 'candeletevisitormessages') OR $canmanage OR ($message['state'] == 'visible' AND $message['postuserid'] == $vbulletin->userinfo['userid'] AND $vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['candeleteownmessages']));
		$canremovemessages = can_moderate(0, 'canremovevisitormessages');

		if ($message['state'] == 'moderation' AND !$canmoderatemessages)
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_messages'));
		}
		else if ($message['state'] == 'deleted' AND !$candeletemessages)
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_messages'));
		}
		else
		{
			$show['deletemessages'] = $candeletemessages;
			if ($canremovemessages)
			{
				$show['removemessages'] = true;
				if (!$candeletemessages)
				{
					$checked = array('remove' => 'checked="checked"');
				}
			}

			if (!$candeletemessages AND !$canremovemessages)
			{
				standard_error(fetch_error('you_do_not_have_permission_to_delete_messages'));
			}
			else if ($candeletemessages AND $canremovemessages)
			{
				$show['deleteoption'] = true;
			}
		}

		$messagearray["$message[vmid]"] = $message;
		$userlist["$message[userid]"] = true;
	}

	if (empty($messagearray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	$messagecount = count($messagearray);
	$usercount = count($userlist);

	$url =& $vbulletin->url;

	$navbits = array('' => $vbphrase['delete_messages']);
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('member_inlinemod_delete')) ? eval($hook) : false;

	$templater = vB_Template::create('memberinfo_deletemessages');
		$templater->register_page_templates();
		$templater->register('checked', $checked);
		$templater->register('messagecount', $messagecount);
		$templater->register('messageids', $messageids);
		$templater->register('navbar', $navbar);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('url', $url);
		$templater->register('usercount', $usercount);
		$templater->register('userinfo', $userinfo);
		$templater->register('vmids', $vmids);
	print_output($templater->render());

}

if ($_POST['do'] == 'doinlinedelete')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'deletetype'   => TYPE_UINT, // 1 - Soft Deletion, 2 - Physically Remove
		'deletereason' => TYPE_NOHTMLCOND,
	));

	$physicaldel = ($vbulletin->GPC['deletetype'] == 2) ? true : false;

	// Validate Messages
	$messages = $db->query_read_slave("
		SELECT visitormessage.vmid, visitormessage.state, visitormessage.userid, visitormessage.dateline,
			visitormessage.postuserid, visitormessage.postusername, visitormessage.messageread,
			user.username AS profile_username
		FROM " . TABLE_PREFIX . "visitormessage AS visitormessage
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (visitormessage.userid = user.userid)
		WHERE vmid IN (" . implode(',', $messageids) . ")
	");
	while ($message = $db->fetch_array($messages))
	{
		$userinfo =& $message;

		$canmanage = ($message['userid'] == $vbulletin->userinfo['userid'] AND $vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['canmanageownprofile']);
		$canmoderatemessages = (can_moderate(0, 'canmoderatevisitormessages') OR $canmanage);
		$candeletemessages = (can_moderate(0, 'candeletevisitormessages') OR $canmanage OR ($message['state'] == 'visible' AND $message['postuserid'] == $vbulletin->userinfo['userid'] AND $vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['candeleteownmessages']));
		$canremovemessages = can_moderate(0, 'canremovevisitormessages');

		if ($message['state'] == 'moderation' AND !$canmoderatemessages)
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_messages'));
		}
		else if ($message['state'] == 'deleted' AND !$candeletemessages)
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_messages'));
		}
		else
		{
			if (($physicaldel AND !$canremovemessages) OR (!$physicaldel AND !$candeletemessages))
			{
				standard_error(fetch_error('you_do_not_have_permission_to_delete_messages'));
			}
		}

		$messagearray["$message[vmid]"] = $message;
		$userlist["$message[userid]"] = true;
	}

	if (empty($messagearray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	foreach($messagearray AS $vmid => $message)
	{
		$dataman =& datamanager_init('VisitorMessage', $vbulletin, ERRTYPE_SILENT);
		$dataman->set_existing($message);
		$dataman->set_info('hard_delete', $physicaldel);
		$dataman->set_info('reason', $vbulletin->GPC['deletereason']);
		$dataman->delete();
		unset($dataman);
	}

	foreach(array_keys($userlist) AS $userid)
	{
		build_visitor_message_counters($userid);
	}

	if (can_moderate(0, 'candeletevisitormessages'))
	{
		foreach ($messagearray AS $message)
		{
			log_moderator_action($message,
				($physicaldel ? 'vm_by_x_for_y_removed' : 'vm_by_x_for_y_soft_deleted'),
				array($message['postusername'], $message['profile_username'])
			);
		}
	}

	// empty cookie
	setcookie('vbulletin_inlinevmessage', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('member_inlinemod_dodelete')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_deletedmessages', true, $forceredirect);
}

if ($_POST['do'] == 'inlineundelete')
{
	// Validate Messages
	$messages = $db->query_read_slave("
		SELECT visitormessage.vmid, visitormessage.state, visitormessage.userid, visitormessage.dateline,
			visitormessage.postuserid, visitormessage.postusername,
			user.username AS profile_username
		FROM " . TABLE_PREFIX . "visitormessage AS visitormessage
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (visitormessage.userid = user.userid)
		WHERE vmid IN ($messageids)
			AND visitormessage.state = 'deleted'
	");
	while ($message = $db->fetch_array($messages))
	{
		if (!fetch_visitor_message_perm('canundeletevisitormessages', $userinfo, $message))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_messages'));
		}

		$messagearray["$message[vmid]"] = $message;
		$userlist["$message[userid]"] = true;
	}

	if (empty($messagearray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "deletionlog
		WHERE type = 'visitormessage' AND
			primaryid IN(" . implode(',', array_keys($messagearray)) . ")
	");
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "visitormessage
		SET state = 'visible'
		WHERE vmid IN(" . implode(',', array_keys($messagearray)) . ")
	");

	foreach(array_keys($userlist) AS $userid)
	{
		build_visitor_message_counters($userid);
	}

	if (can_moderate(0, 'candeletevisitormessages'))
	{
		foreach ($messagearray AS $message)
		{
			log_moderator_action($message, 'vm_by_x_for_y_undeleted',
				array($message['postusername'], $message['profile_username'])
			);
		}
	}

	// empty cookie
	setcookie('vbulletin_inlinevmessage', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('member_inlinemod_undelete')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_undeletedmessages', true, $forceredirect);
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # SVN: $Revision: 62690 $
|| ####################################################################
\*======================================================================*/
