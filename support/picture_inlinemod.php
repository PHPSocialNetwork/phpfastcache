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
define('THIS_SCRIPT', 'picture_inlinemod');
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
	'inlinedelete'  => array('picturecomment_deletemessages'),
	'picturedelete' => array('moderation_deletepictures'),
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_picturecomment.php');
require_once(DIR . '/includes/modfunctions.php');
require_once(DIR . '/includes/functions_log_error.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$types = vB_Types::instance();
$contenttypeid = $types->getContentTypeID('vBForum_Album');

if (($current_memory_limit = ini_size_to_bytes(@ini_get('memory_limit'))) < 128 * 1024 * 1024 AND $current_memory_limit > 0)
{
	@ini_set('memory_limit', 128 * 1024 * 1024);
}
@set_time_limit(0);

$itemlimit = 200;

// This is a list of ids that were checked on the page we submitted from
$vbulletin->input->clean_array_gpc('p', array(
	'picturecommentlist' => TYPE_ARRAY_KEYS_INT,
	'picturelist'        => TYPE_ARRAY_KEYS_INT,
	'attachmentid'       => TYPE_UINT,
));

$vbulletin->input->clean_array_gpc('c', array(
	'vbulletin_inlinepicturecomment' => TYPE_STR,
));

if (!empty($vbulletin->GPC['vbulletin_inlinepicturecomment']))
{
	$commentlist = explode('-', $vbulletin->GPC['vbulletin_inlinepicturecomment']);
	$commentlist = $vbulletin->input->clean($commentlist, TYPE_ARRAY_UINT);

	$vbulletin->GPC['picturecommentlist'] = array_unique(array_merge($commentlist, $vbulletin->GPC['picturecommentlist']));
}

if (!$vbulletin->userinfo['userid'])
{
	print_no_permission();
}

switch ($_POST['do'])
{
	case 'doinlinedelete':
	case 'dopicturedelete':
	{
		$inline_mod_authenticate = true;
		break;
	}
	default:
	{
		$inline_mod_authenticate = false;
		($hook = vBulletinHook::fetch_hook('picturecomment_inlinemod_authenticate_switch')) ? eval($hook) : false;
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

		if (!$vbulletin->options['pc_enabled'])
		{
			print_no_permission();
		}

		if (empty($vbulletin->GPC['picturecommentlist']))
		{
			standard_error(fetch_error('you_did_not_select_any_valid_messages'));
		}

		if (count($vbulletin->GPC['picturecommentlist']) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_messages', $itemlimit));
		}

		$messageids = implode(', ', $vbulletin->GPC['picturecommentlist']);
		break;

	case 'doinlinedelete':

		if (!$vbulletin->options['pc_enabled'])
		{
			print_no_permission();
		}

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

($hook = vBulletinHook::fetch_hook('picturecomment_inlinemod_start')) ? eval($hook) : false;

if ($_POST['do'] == 'clearpicture')
{
	setcookie('vbulletin_inlinepicture', '', TIMENOW - 3600, '/');

	print_standard_redirect('redirect_inline_messagelist_cleared', true, $forceredirect);  
}

if ($_POST['do'] == 'clearmessage')
{
	setcookie('vbulletin_inlinepicturecomment', '', TIMENOW - 3600, '/');

	print_standard_redirect('redirect_inline_messagelist_cleared', true, $forceredirect);  
}

if ($_POST['do'] == 'inlineapprove' OR $_POST['do'] == 'inlineunapprove')
{
	$insertrecords = array();

	$approve = ($_POST['do'] == 'inlineapprove');

	// Validate Messages
	$messages = $db->query_read_slave("
		SELECT
			picturecomment.*, a.userid AS picture_userid, a.caption AS picture_caption, a.attachmentid
		FROM " . TABLE_PREFIX . "picturecomment AS picturecomment
		INNER JOIN " . TABLE_PREFIX . "attachment AS a ON (a.filedataid = picturecomment.filedataid AND a.userid = picturecomment.userid)
		WHERE
			a.contenttypeid = $contenttypeid
				AND
			picturecomment.commentid IN ($messageids)
				AND
			picturecomment.state IN (" . ($approve ? "'moderation'" : "'visible', 'deleted'") . ")
	");
	while ($message = $db->fetch_array($messages))
	{
		$pictureinfo = array(
			'attachmentid' => $message['attachmentid'],
			'userid'       => $message['picture_userid']
		);

		if ($message['state'] == 'deleted' AND !can_moderate(0, 'candeletepicturecomments'))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_messages'));
		}
		else if (!fetch_user_picture_message_perm('canmoderatemessages', $pictureinfo, $message))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_moderate_messages'));
		}

		$messagearray["$message[commentid]"] = $message;
		$userlist["$pictureinfo[userid]"] = true;

		if (!$approve)
		{
			$insertrecords[] = "($message[commentid], 'picturecomment', " . TIMENOW . ")";
		}
	}

	if (empty($messagearray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	// Set message state
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "picturecomment
		SET state = '" . ($approve ? 'visible' : 'moderation') . "'
		WHERE commentid IN (" . implode(',', array_keys($messagearray)) . ")
	");

	if ($approve)
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "moderation
			WHERE primaryid IN(" . implode(',', array_keys($messagearray)) . ")
				AND type = 'picturecomment'
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
			WHERE type = 'picturecomment' AND
				primaryid IN(" . implode(',', array_keys($messagearray)) . ")
		");
	}

	foreach (array_keys($userlist) AS $userid)
	{
		build_picture_comment_counters($userid);
	}

	foreach ($messagearray AS $commentinfo)
	{
		log_moderator_action($commentinfo,
			($approve ? 'pc_by_x_on_y_approved' : 'pc_by_x_on_y_unapproved'),
			array($commentinfo['postusername'], fetch_trimmed_title($commentinfo['picture_caption'], 50))
		);
	}

	setcookie('vbulletin_inlinepicturecomment', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('picturecomment_inlinemod_approveunapprove')) ? eval($hook) : false;

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
	$show['removemessages'] = false;
	$show['deletemessages'] = false;
	$show['deleteoption'] = false;
	$checked = array('delete' => 'checked="checked"');
	$picturelist = array();

	// Validate Messages
	$messages = $db->query_read_slave("
		SELECT
			picturecomment.*, a.userid AS picture_userid
		FROM " . TABLE_PREFIX . "picturecomment AS picturecomment
		INNER JOIN " . TABLE_PREFIX . "attachment AS a ON (a.filedataid = picturecomment.filedataid AND a.userid = picturecomment.userid AND a.contenttypeid = " . intval($contenttypeid) . ")
		WHERE picturecomment.commentid IN ($messageids)
	");
	while ($message = $db->fetch_array($messages))
	{
		$pictureinfo = array(
			'attachmentid' => $message['attachmentid'],
			'userid'       => $message['picture_userid']
		);

		$canmoderatemessages = fetch_user_picture_message_perm('canmoderatemessages', $pictureinfo, $message);
		$candeletemessages = fetch_user_picture_message_perm('candeletemessages', $pictureinfo, $message);
		$canremovemessages = can_moderate(0, 'canremovepicturecomments');

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

		$messagearray["$message[commentid]"] = $message;
		$picturelist["$message[attachmentid]"] = true;
		$userlist["$pictureinfo[userid]"] = true;
	}

	if (empty($messagearray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	$messagecount = count($messagearray);
	$picturecount = count($picturelist);

	$url =& $vbulletin->url;

	$navbits = array('' => $vbphrase['delete_messages']);
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('picturecomment_inlinemod_delete')) ? eval($hook) : false;

	$templater = vB_Template::create('picturecomment_deletemessages');
		$templater->register_page_templates();
		$templater->register('checked', $checked);
		$templater->register('messagecount', $messagecount);
		$templater->register('messageids', $messageids);
		$templater->register('navbar', $navbar);
		$templater->register('picturecount', $picturecount);
		$templater->register('url', $url);
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
		SELECT
			picturecomment.*, a.userid AS picture_userid, a.caption AS picture_caption
		FROM " . TABLE_PREFIX . "picturecomment AS picturecomment
		INNER JOIN " . TABLE_PREFIX . "attachment AS a ON (a.filedataid = picturecomment.filedataid AND a.userid = picturecomment.userid AND a.contenttypeid = " . intval($contenttypeid) . ")
		WHERE picturecomment.commentid IN (" . implode(',', $messageids) . ")
	");
	while ($message = $db->fetch_array($messages))
	{
		$pictureinfo = array(
			'attachmentid' => $message['attachmentid'],
			'userid'       => $message['picture_userid']
		);

		$canmoderatemessages = fetch_user_picture_message_perm('canmoderatemessages', $pictureinfo, $message);
		$candeletemessages = fetch_user_picture_message_perm('candeletemessages', $pictureinfo, $message);
		$canremovemessages = can_moderate(0, 'canremovepicturecomments');

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

		$messagearray["$message[commentid]"] = $message;
		$userlist["$pictureinfo[userid]"] = true;
	}

	if (empty($messagearray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	foreach($messagearray AS $commentid => $message)
	{
		$dataman =& datamanager_init('PictureComment', $vbulletin, ERRTYPE_SILENT);
		$dataman->set_existing($message);
		$dataman->set_info('hard_delete', $physicaldel);
		$dataman->set_info('reason', $vbulletin->GPC['deletereason']);
		$dataman->delete();
		unset($dataman);

		if (can_moderate(0, 'candeletepicturecomments'))
		{
			log_moderator_action($message,
				($physicaldel ? 'pc_by_x_on_y_removed' : 'pc_by_x_on_y_soft_deleted'),
				array($message['postusername'], fetch_trimmed_title($message['picture_caption'], 50))
			);
		}
	}

	foreach(array_keys($userlist) AS $userid)
	{
		build_picture_comment_counters($userid);
	}

	// empty cookie
	setcookie('vbulletin_inlinepicturecomment', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('picturecomment_inlinemod_dodelete')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_deletedmessages', true, $forceredirect);  
}

if ($_POST['do'] == 'inlineundelete')
{
	// Validate Messages
	$messages = $db->query_read_slave("
		SELECT picturecomment.*, a.userid AS picture_userid, a.caption AS picture_caption
		FROM " . TABLE_PREFIX . "picturecomment AS picturecomment
		INNER JOIN " . TABLE_PREFIX . "attachment AS a ON (a.filedataid = picturecomment.filedataid AND a.userid = picturecomment.userid AND a.contenttypeid = " . intval($contenttypeid) . ")
		WHERE picturecomment.commentid IN ($messageids)
			AND picturecomment.state = 'deleted'
	");
	while ($message = $db->fetch_array($messages))
	{
		$pictureinfo = array(
			'attachmentid' => $message['attachmentid'],
			'userid'       => $message['picture_userid']
		);
		if (!can_moderate(0, 'candeletepicturecomments'))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_messages'));
		}

		$messagearray["$message[commentid]"] = $message;
		$userlist["$pictureinfo[userid]"] = true;
	}

	if (empty($messagearray))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_messages'));
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "deletionlog
		WHERE type = 'picturecomment' AND
			primaryid IN(" . implode(',', array_keys($messagearray)) . ")
	");
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "picturecomment
		SET state = 'visible'
		WHERE commentid IN(" . implode(',', array_keys($messagearray)) . ")
	");

	foreach(array_keys($userlist) AS $userid)
	{
		build_picture_comment_counters($userid);
	}

	if (can_moderate(0, 'candeletepicturecomments'))
	{
		foreach ($messagearray AS $message)
		{
			log_moderator_action($message, 'pc_by_x_on_y_undeleted',
				array($message['postusername'], fetch_trimmed_title($message['picture_caption'], 50))
			);
		}
	}

	// empty cookie
	setcookie('vbulletin_inlinepicturecomment', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('picturecomment_inlinemod_undelete')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_undeletedmessages', true, $forceredirect);  
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 63231 $
|| ####################################################################
\*======================================================================*/
