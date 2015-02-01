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
define('GET_EDIT_TEMPLATES', 'editsignature,updatesignature');
define('THIS_SCRIPT', 'profile');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('user', 'timezone', 'posting', 'cprofilefield', 'cppermission');

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
	'banemail',
	'ranks',
	'noavatarperms',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'USERCP_SHELL',
	'usercp_nav_folderbit'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'editprofile' => array(
		'modifyprofile',
		'modifyprofile_birthday',
		'userfield_checkbox_option',
		'userfield_optional_input',
		'userfield_radio',
		'userfield_radio_option',
		'userfield_select',
		'userfield_select_option',
		'userfield_select_multiple',
		'userfield_textarea',
		'userfield_textbox',
		'userfield_wrapper',
	),
	'editoptions' => array(
		'modifyoptions',
		'modifyoptions_timezone',
		'userfield_checkbox_option',
		'userfield_optional_input',
		'userfield_radio',
		'userfield_radio_option',
		'userfield_select',
		'userfield_select_option',
		'userfield_select_multiple',
		'userfield_textarea',
		'userfield_textbox',
		'userfield_wrapper',
	),
	'editconnections' =>array(
		'modifyconnections'
	),
	'editavatar' => array(
		'modifyavatar',
		'modifyavatar_category',
		'modifyavatarbit',
		'modifyavatarbit_custom',
		'modifyavatarbit_noavatar',
	),
	'editusergroups' => array(
		'modifyusergroups',
		'modifyusergroups_joinrequestbit',
		'modifyusergroups_memberbit',
		'modifyusergroups_nonmemberbit',
		'modifyusergroups_displaybit'
	),
	'editsignature' => array(
		'modifysignature',
		'forumrules'
	),
	'updatesignature' => array(
		'modifysignature',
		'forumrules'
	),
	'editpassword' => array(
		'modifypassword'
	),
	'editprofilepic' => array(
		'modifyprofilepic'
	),
	'joingroup' => array(
		'modifyusergroups_requesttojoin'
	),
	'editattachments' => array(
		'GENERIC_SHELL',
		'modifyattachmentsbit',
		'modifyattachmentsbit_post',
		'modifyattachmentsbit_album',
		'modifyattachmentsbit_group',
		'modifyattachments'
	),
	'addlist' => array(
		'modifyuserlist_confirm',
	),
	'removelist' => array(
		'modifyuserlist_confirm',
	),
	'buddylist' => array(
		'modifybuddylist',
		'modifybuddylist_user',
	),
	'ignorelist' => array(
		'modifyignorelist',
		'modifyignorelist_user',
	),
	'customize' => array(
		'memberinfo_usercss',
		'modifyusercss',
		'modifyusercss_backgroundbit',
		'modifyusercss_backgroundrow',
		'modifyusercss_bit',
		'modifyusercss_error',
		'modifyusercss_error_link',
		'modifyusercss_headinclude',
		'modifyprivacy_bit',
	),
	'privacy' => array(
		'modifyprofileprivacy',
		'modifyprivacy_bit'
	),
	'doprivacy' => array(
		'modifyprofileprivacy',
		'modifyprivacy_bit'
	)
);
$actiontemplates['docustomize'] = $actiontemplates['customize'];

$actiontemplates['none'] =& $actiontemplates['editprofile'];

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_user.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'editprofile';
}

if (!($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
{
	print_no_permission();
}

if (empty($vbulletin->userinfo['userid']))
{
	print_no_permission();
}

// set shell template name
$shelltemplatename = 'USERCP_SHELL';
$includecss = array();

// initialise onload event
$onload = '';

// start the navbar
$navbits = array('usercp.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['user_control_panel']);

($hook = vBulletinHook::fetch_hook('profile_start')) ? eval($hook) : false;

// ############################### start dst autodetect switch ###############################
if ($_POST['do'] == 'dst')
{
	if ($vbulletin->userinfo['dstauto'])
	{
		$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
		$userdata->set_existing($vbulletin->userinfo);

		switch ($vbulletin->userinfo['dstonoff'])
		{
			case 1:
			{
				if ($vbulletin->userinfo['options'] & $vbulletin->bf_misc_useroptions['dstonoff'])
				{
					$userdata->set_bitfield('options', 'dstonoff', 0);
				}
			}
			break;

			case 0:
			{
				if (!($vbulletin->userinfo['options'] & $vbulletin->bf_misc_useroptions['dstonoff']))
				{
					$userdata->set_bitfield('options', 'dstonoff', 1);
				}
			}
			break;
		}

		($hook = vBulletinHook::fetch_hook('profile_dst')) ? eval($hook) : false;

		$userdata->save();
	}

	print_standard_redirect('redirect_dst');  
}

// ############################### toggle user css ###############################
if ($_REQUEST['do'] == 'switchusercss')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'hash'     => TYPE_STR,
		'userid'   => TYPE_UINT,
	));

	if (!verify_security_token($vbulletin->GPC['hash'], $vbulletin->userinfo['securitytoken_raw']))
	{
		print_no_permission();
	}

	$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true);

	if ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_profile_styling'])
	{
		$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
		$userdata->set_existing($vbulletin->userinfo);

		$userdata->set_bitfield('options', 'showusercss', ($vbulletin->userinfo['options'] & $vbulletin->bf_misc_useroptions['showusercss'] ? 0 : 1));

		$userdata->save();
	}

	if ($vbulletin->GPC['userid'] AND $vbulletin->url == fetch_seo_url('forumhome|nosession', array()))
	{
		$vbulletin->url = fetch_seo_url('member', $userinfo);
	}
	print_standard_redirect('redirect_usercss_toggled');  
}

// ############################################################################
// ############################### EDIT PASSWORD ##############################
// ############################################################################

if ($_REQUEST['do'] == 'editpassword')
{
	($hook = vBulletinHook::fetch_hook('profile_editpassword_start')) ? eval($hook) : false;

	// draw cp nav bar
	construct_usercp_nav('password');

	// check for password history retention
	$passwordhistory = $permissions['passwordhistory'];

	// don't let banned people edit their email (see bug 2142)
	if (!($permissions['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
	{
		$show['edit_email_field'] = false;
		$navbits[''] = $vbphrase['edit_password'];
	}
	else
	{
		$show['edit_email_field'] = true;
		$navbits[''] = $vbphrase['edit_email_and_password'];
	}

	// only show old password input if user is vb user,
	// and not facebook only user (which means they have no password)
	$show['oldpasswordinput'] = ($vbulletin->userinfo['logintype'] == 'vb');

	// don't show optional because password expired
	$show['password_optional'] = !$show['passwordexpired'];

	$page_templater = vB_Template::create('modifypassword');
}

// ############################### start update password ###############################
if ($_POST['do'] == 'updatepassword')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'currentpassword'        => TYPE_STR,
		'currentpassword_md5'    => TYPE_STR,
		'newpassword'            => TYPE_STR,
		'newpasswordconfirm'     => TYPE_STR,
		'newpassword_md5'        => TYPE_STR,
		'newpasswordconfirm_md5' => TYPE_STR,
		'email'                  => TYPE_STR,
		'emailconfirm'           => TYPE_STR
	));

	// instanciate the data manager class
	$userdata =& datamanager_init('user', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($vbulletin->userinfo);

	($hook = vBulletinHook::fetch_hook('profile_updatepassword_start')) ? eval($hook) : false;

	// if this is a Facebook only user, we will only use this form to add a password
	// so we will ignore old password, email, and set the user logintype to be a vB user
	if (is_facebookenabled() AND $vbulletin->userinfo['logintype'] == 'fb')
	{
		$userdata->set('logintype', 'vb');
		// if a new email was not submitted, use whats already in the DB
		if (!$vbulletin->GPC_exists['email'])
		{
			$vbulletin->GPC['email'] = $vbulletin->GPC['emailconfirm'] = $vbulletin->userinfo['email'];
		}
	}

	// if not Facebook user, validate old password
	else if ($userdata->hash_password($userdata->verify_md5($vbulletin->GPC['currentpassword_md5']) ? $vbulletin->GPC['currentpassword_md5'] : $vbulletin->GPC['currentpassword'], $vbulletin->userinfo['salt']) != $vbulletin->userinfo['password'])
	{
		eval(standard_error(fetch_error('badpassword', $vbulletin->options['bburl'], $vbulletin->session->vars['sessionurl'])));
	}

	// update password
	if (!empty($vbulletin->GPC['newpassword']) OR !empty($vbulletin->GPC['newpassword_md5']))
	{
		// are we using javascript-hashed password strings?
		if ($userdata->verify_md5($vbulletin->GPC['newpassword_md5']))
		{
			$vbulletin->GPC['newpassword'] =& $vbulletin->GPC['newpassword_md5'];
			$vbulletin->GPC['newpasswordconfirm'] =& $vbulletin->GPC['newpasswordconfirm_md5'];
		}
		else
		{
			$vbulletin->GPC['newpassword'] =& md5($vbulletin->GPC['newpassword']);
			$vbulletin->GPC['newpasswordconfirm'] =& md5($vbulletin->GPC['newpasswordconfirm']);
		}

		// check that new passwords match
		if ($vbulletin->GPC['newpassword'] != $vbulletin->GPC['newpasswordconfirm'])
		{
			eval(standard_error(fetch_error('passwordmismatch')));
		}

		// check to see if the new password is invalid due to previous use
		if ($userdata->check_password_history($userdata->hash_password($vbulletin->GPC['newpassword'], $vbulletin->userinfo['salt']), $permissions['passwordhistory']))
		{
			eval(standard_error(fetch_error('passwordhistory', $permissions['passwordhistory'])));
		}

		// everything is good - send the singly-hashed MD5 to the password update routine
		$userdata->set('password', $vbulletin->GPC['newpassword']);

		// Update cookie if we have one
		$vbulletin->input->clean_array_gpc('c', array(
			COOKIE_PREFIX . 'password' => TYPE_STR,
			COOKIE_PREFIX . 'userid'   => TYPE_UINT)
		);

		if (md5($vbulletin->userinfo['password'] . COOKIE_SALT) == $vbulletin->GPC[COOKIE_PREFIX . 'password'] AND
			$vbulletin->GPC[COOKIE_PREFIX . 'userid'] == $vbulletin->userinfo['userid']
		)
		{
			vbsetcookie('password', md5(md5($vbulletin->GPC['newpassword'] . $vbulletin->userinfo['salt']) . COOKIE_SALT), true, true, true);
		}
		$activate = false;
	}

	// update email only if user is not banned (see bug 2142) and email is changed
	// also, do not update
	if ($permissions['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup'] AND ($vbulletin->GPC['email'] != $vbulletin->userinfo['email'] OR $vbulletin->GPC['emailconfirm'] != $vbulletin->userinfo['email']))
	{
		// check that new email addresses match
		if ($vbulletin->GPC['email'] != $vbulletin->GPC['emailconfirm'])
		{
			eval(standard_error(fetch_error('emailmismatch')));
		}

		// set the email field to be updated
		$userdata->set('email', $vbulletin->GPC['email']);

		// generate an activation ID if required
		if ($vbulletin->options['verifyemail'] AND !can_moderate())
		{
			$userdata->set('usergroupid', 3);
			$userdata->set_info('override_usergroupid', true);

			$activate = true;

			// wait lets check if we have an entry first!
			$activation_exists = $db->query_first("
				SELECT * FROM " . TABLE_PREFIX . "useractivation
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
				AND type = 0
			");

			if (!empty($activation_exists['usergroupid']) AND $vbulletin->userinfo['usergroupid'] == 3)
			{
				$usergroupid = $activation_exists['usergroupid'];
			}
			else
			{
				$usergroupid = $vbulletin->userinfo['usergroupid'];
			}
			$activateid = build_user_activation_id($vbulletin->userinfo['userid'], $usergroupid, 0, 1);

			$username = unhtmlspecialchars($vbulletin->userinfo['username']);
			$userid = $vbulletin->userinfo['userid'];

			eval(fetch_email_phrases('activateaccount_change'));
			vbmail($vbulletin->GPC['email'], $subject, $message, true);
		}
		else
		{
			$activate = false;
		}
	}
	else
	{
		$userdata->verify_useremail($vbulletin->userinfo['email']);
	}

	($hook = vBulletinHook::fetch_hook('profile_updatepassword_complete')) ? eval($hook) : false;

	// save the data
	$userdata->save();

	if ($activate)
	{
		$vbulletin->url = 'usercp.php' . $vbulletin->session->vars['sessionurl_q'];
		print_standard_redirect(array('redirect_updatethanks_newemail',$vbulletin->userinfo['username']), true, true);  
	}
	else
	{
		$vbulletin->url = 'usercp.php' . $vbulletin->session->vars['sessionurl_q'];
		print_standard_redirect(array('redirect_updatethanks',$vbulletin->userinfo['username']), true, true);  
	}
}
else if ($_GET['do'] == 'updatepassword')
{
	// add consistency with previous behavior
	exec_header_redirect('profile.php?do=editpassword');
}

// ############################################################################
// ######################### EDIT BUDDY/IGNORE LISTS ##########################
// ############################################################################
if ($_REQUEST['do'] == 'addlist')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'   => TYPE_UINT,
		'userlist' => TYPE_NOHTML,
	));

	if ($vbulletin->GPC['userlist'] == 'friend' AND (!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends']) OR !($vbulletin->userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends'])))
	{
		$vbulletin->GPC['userlist'] = 'buddy';
	}

	$show['friend_checkbox'] = false;
	$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true, FETCH_USERINFO_ISFRIEND);
	cache_permissions($userinfo);

	if ($vbulletin->GPC['userlist'] == 'buddy' OR $vbulletin->GPC['userlist'] == 'friend')
	{
		// No slave here
		$ouruser = $db->query_first("
			SELECT friend
			FROM " . TABLE_PREFIX . "userlist
			WHERE relationid = $userinfo[userid]
				AND userid = " . $vbulletin->userinfo['userid'] . "
				AND type = 'buddy'
		");
		if ($vbulletin->GPC['userlist'] == 'friend')
		{
			if ($ouruser['friend'] == 'pending' OR $ouruser['friend'] == 'denied')
			{	// We are pending friends
				print_standard_redirect(array('redirect_friendspending',$userinfo['username']), true, true);  
			}
			else if ($ouruser['friend'] == 'yes')
			{	// We are already friends
				print_standard_redirect(array('redirect_friendsalready',$userinfo['username']), true, true);  
			}
			else if ($vbulletin->GPC['userid'] == $vbulletin->userinfo['userid'])
			{ // You can't be friends with yourself
				print_standard_redirect('redirect_friendswithself', true, true);  
			}
		}
		else if ($ouruser)
		{
			if ($ouruser['friend'] == 'yes')
			{
				print_standard_redirect(array('redirect_friendsalready',$userinfo['username']), true, true);  
			}
			else
			{
				print_standard_redirect(array('redirect_contactsalready',$userinfo['username']), true, true);  
			}
		}
	}

	switch ($vbulletin->GPC['userlist'])
	{
		case 'friend':
			$friend_checked = ' checked="checked"';
		case 'buddy':
			if ($userinfo['requestedfriend'])
			{
				$confirm_phrase = 'confirm_friendship_request_from_x';
				$show['friend_checkbox'] = false;
				$show['hiddenfriend'] = true;
			}
			else
			{
				$confirm_phrase = 'add_x_to_contacts_confirm';
				$supplemental_phrase = 'also_send_friend_request_to_x';
				$show['friend_checkbox'] = ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends'] AND $userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends']);
			}

			construct_usercp_nav('buddylist');
		break;
		case 'ignore':
			$uglist = $userinfo['usergroupid'] . (trim($userinfo['membergroupids']) ? ",$userinfo[membergroupids]" : '');
			if (!$vbulletin->options['ignoremods'] AND can_moderate(0, '', $userinfo['userid'], $uglist) AND !($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
			{
				standard_error(fetch_error('listignoreuser', $userinfo['username']));
			}
			else if ($vbulletin->userinfo['userid'] == $userinfo['userid'])
			{
				standard_error(fetch_error('cantlistself_ignore'));
			}

			$confirm_phrase = 'add_x_to_ignorelist_confirm';

			construct_usercp_nav('ignorelist');
		break;
		default:
			standard_error(fetch_error('invalidid', 'list', $vbulletin->options['contactuslink']));
	}
	$navbits[''] = $vbphrase['confirm_user_list_modification'];

	// draw cp nav bar
	$action = 'doaddlist';
	$userid = $userinfo['userid'];
	$userlist = $vbulletin->GPC['userlist'];
	$url =& $vbulletin->url;

	$page_templater = vB_Template::create('modifyuserlist_confirm');
	$page_templater->register('action', $action);
	$page_templater->register('confirm_phrase', $confirm_phrase);
	$page_templater->register('friend_checked', $friend_checked);
	$page_templater->register('list', $list);
	$page_templater->register('supplemental_phrase', $supplemental_phrase);
	$page_templater->register('url', $url);
	$page_templater->register('userid', $userid);
	$page_templater->register('userinfo', $userinfo);
	$page_templater->register('userlist', $userlist);
}

if ($_REQUEST['do'] == 'removelist')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'   => TYPE_UINT,
		'userlist' => TYPE_NOHTML,
	));

	$show['friend_checkbox'] = false;
	$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true);
	cache_permissions($userinfo);

	switch ($vbulletin->GPC['userlist'])
	{
		case 'friend':
			$confirm_phrase = 'remove_x_from_friendlist_confirm';
			$supplemental_phrase = 'also_remove_x_from_contacts';
			$show['friend_checkbox'] = true;

			construct_usercp_nav('buddylist');
		break;
		case 'buddy':
			$confirm_phrase = 'remove_x_from_contacts_confirm';
			construct_usercp_nav('buddylist');
		break;
		case 'ignore':
			$confirm_phrase = 'remove_x_from_ignorelist_confirm';
			construct_usercp_nav('ignorelist');
		break;
		default:
			standard_error(fetch_error('invalidid', 'list', $vbulletin->options['contactuslink']));
	}

	$navbits[''] = $vbphrase['confirm_user_list_modification'];

	// draw cp nav bar
	$action = 'doremovelist';
	$userid = $userinfo['userid'];
	$userlist = $vbulletin->GPC['userlist'];
	$url =& $vbulletin->url;

	$page_templater = vB_Template::create('modifyuserlist_confirm');
	$page_templater->register('action', $action);
	$page_templater->register('confirm_phrase', $confirm_phrase);
	$page_templater->register('friend_checked', $friend_checked);
	$page_templater->register('list', $list);
	$page_templater->register('supplemental_phrase', $supplemental_phrase);
	$page_templater->register('url', $url);
	$page_templater->register('userid', $userid);
	$page_templater->register('userinfo', $userinfo);
	$page_templater->register('userlist', $userlist);
}

// ############################### start add to list ###############################
if ($_POST['do'] == 'doaddlist')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'userid'   => TYPE_UINT,
		'userlist' => TYPE_NOHTML,
		'friend'   => TYPE_BOOL,
		'deny'     => TYPE_NOHTML,
	));

	$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true);
	cache_permissions($userinfo);

	($hook = vBulletinHook::fetch_hook('profile_doaddlist_start')) ? eval($hook) : false;

	// no referring URL, send them back to the profile page
	if ($vbulletin->url == fetch_seo_url('forumhome|nosession', array()))
	{
		$vbulletin->url = fetch_seo_url('member', $userinfo);
	}

	// No was clicked
	if ($vbulletin->GPC['deny'])
	{
		print_standard_redirect('action_cancelled');  
	}

	if ($vbulletin->GPC['userlist'] != 'ignore')
	{
		$vbulletin->GPC['userlist'] = $vbulletin->GPC['friend'] ? 'friend' : 'buddy';
	}

	if ($vbulletin->GPC['userlist'] == 'friend' AND (!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends']) OR !($userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends']) OR !($vbulletin->userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends'])))
	{
		$vbulletin->GPC['userlist'] = 'buddy';
	}

	$users = array();
	switch ($vbulletin->GPC['userlist'])
	{
		case 'friend':
		case 'buddy':

			// No slave here
			$ouruser = $db->query_first("
				SELECT friend
				FROM " . TABLE_PREFIX . "userlist
				WHERE relationid = $userinfo[userid]
					AND userid = " . $vbulletin->userinfo['userid'] . "
					AND type = 'buddy'
			");
		break;
		case 'ignore':
			$uglist = $userinfo['usergroupid'] . (trim($userinfo['membergroupids']) ? ",$userinfo[membergroupids]" : '');
			if (!$vbulletin->options['ignoremods'] AND can_moderate(0, '', $userinfo['userid'], $uglist) AND !($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
			{
				standard_error(fetch_error('listignoreuser', $userinfo['username']));
			}
			else if ($vbulletin->userinfo['userid'] == $userinfo['userid'])
			{
				standard_error(fetch_error('cantlistself_ignore'));
			}

			$db->query_write("
				INSERT IGNORE INTO " . TABLE_PREFIX . "userlist
					(userid, relationid, type, friend)
				VALUES
					(" . $vbulletin->userinfo['userid'] . ", " . intval($userinfo['userid']) . ", 'ignore', 'no')
			");
			$users[] = $vbulletin->userinfo['userid'];
			$redirect_phrase = array('redirect_addlist_ignore',$userinfo['username']);
		break;
		default:
			standard_error(fetch_error('invalidid', 'list', $vbulletin->options['contactuslink']));
	}

	if ($vbulletin->GPC['userlist'] == 'buddy')
	{ // if an entry exists already then we're fine
		if (empty($ouruser))
		{
			$db->query_write("
				INSERT IGNORE INTO " . TABLE_PREFIX . "userlist
					(userid, relationid, type, friend)
				VALUES
					(" . $vbulletin->userinfo['userid'] . ", " . intval($userinfo['userid']) . ", 'buddy', 'no')
			");
			$users[] = $vbulletin->userinfo['userid'];
		}
		$redirect_phrase = array('redirect_addlist_contact',$userinfo['username']);
	}
	else if ($vbulletin->GPC['userlist'] == 'friend')
	{
		if ($ouruser['friend'] == 'pending' OR $ouruser['friend'] == 'denied')
		{	// We are pending friends
			print_standard_redirect(array('redirect_friendspending',$userinfo['username']), true, true);  
		}
		else if ($ouruser['friend'] == 'yes')
		{	// We are already friends
			print_standard_redirect(array('redirect_friendsalready',$userinfo['username']), true, true);  
		}
		else if ($vbulletin->GPC['userid'] == $vbulletin->userinfo['userid'])
		{ // You can't be friends with yourself
			print_standard_redirect('redirect_friendswithself', true, true);  
		}

		// No slave here
		if ($db->query_first("
			SELECT friend
			FROM " . TABLE_PREFIX . "userlist
			WHERE userid = $userinfo[userid]
				AND relationid = " . $vbulletin->userinfo['userid'] . "
				AND type = 'buddy'
				AND (friend = 'pending' OR friend = 'denied')
		"))
		{
			// Make us friends
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "userlist
					(userid, relationid, type, friend)
				VALUES
					({$vbulletin->userinfo['userid']}, $userinfo[userid], 'buddy', 'yes'),
					($userinfo[userid], {$vbulletin->userinfo['userid']}, 'buddy', 'yes')
			");

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "user
				SET friendcount = friendcount + 1
				WHERE userid IN ($userinfo[userid], " . $vbulletin->userinfo['userid'] . ")
			");

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "user
				SET friendreqcount = IF(friendreqcount > 0, friendreqcount - 1, 0)
				WHERE userid = " . $vbulletin->userinfo['userid']
			);

			$users[] = $vbulletin->userinfo['userid'];
			$users[] = $userinfo['userid'];
			$redirect_phrase = array('redirect_friendadded',$userinfo['username']);
		}
		else
		{
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "userlist
					(userid, relationid, type, friend)
				VALUES
					({$vbulletin->userinfo['userid']}, $userinfo[userid], 'buddy', 'pending')
			");

			$cansendemail = (($userinfo['adminemail'] OR $userinfo['showemail']) AND $vbulletin->options['enableemail'] AND $vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canemailmember']);
			if ($cansendemail AND $userinfo['options'] & $vbulletin->bf_misc_useroptions['receivefriendemailrequest'])
			{
				$touserinfo =& $userinfo;
				$fromuserinfo =& $vbulletin->userinfo;


				eval(fetch_email_phrases('friendship_request_email', $touserinfo['languageid']));
				require_once(DIR . '/includes/class_bbcode_alt.php');
				$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());
				$plaintext_parser->set_parsing_language($touserinfo['languageid']);
				$message = $plaintext_parser->parse($message, 'privatemessage');
				vbmail($touserinfo['email'], $subject, $message);
			}

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "user
				SET friendreqcount = friendreqcount + 1
				WHERE userid = " . $userinfo['userid']
			);

			$users[] = $vbulletin->userinfo['userid'];
			$redirect_phrase = array('redirect_friendrequested',$userinfo['username']);
		}
	}

	require_once(DIR . '/includes/functions_databuild.php');
	foreach($users AS $userid)
	{
		build_userlist($userid);
	}

	($hook = vBulletinHook::fetch_hook('profile_doaddlist_complete')) ? eval($hook) : false;

	print_standard_redirect($redirect_phrase, true, true);  
}

if ($_POST['do'] == 'doremovelist')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'userid'   => TYPE_UINT,
		'userlist' => TYPE_NOHTML,
		'friend'   => TYPE_BOOL,
		'deny'     => TYPE_NOHTML,
	));

	$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true);
	cache_permissions($userinfo);

	($hook = vBulletinHook::fetch_hook('profile_doremovelist_start')) ? eval($hook) : false;

	// no referring URL, send them back to the profile page
	if ($vbulletin->url == fetch_seo_url('forumhome|nosession', array()))
	{
		$vbulletin->url = fetch_seo_url('member', $userinfo);
	}

	// No was clicked
	if ($vbulletin->GPC['deny'])
	{
		print_standard_redirect('action_cancelled');  
	}

	$users = array();
	switch ($vbulletin->GPC['userlist'])
	{
		case 'friend':
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "userlist
				SET friend = 'no'
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND relationid = $userinfo[userid]
					AND type = 'buddy'
					AND friend = 'yes'
			");
			if ($db->affected_rows())
			{
				$users[] = $vbulletin->userinfo['userid'];
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "userlist
					SET friend = 'no'
					WHERE relationid = " . $vbulletin->userinfo['userid'] . "
						AND userid = $userinfo[userid]
						AND type = 'buddy'
						AND friend = 'yes'
				");
				if ($db->affected_rows())
				{
					$users[] = $userinfo['userid'];
				}
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET friendcount = IF(friendcount >= 1, friendcount - 1, 0)
					WHERE userid IN(" . implode(", ", $users) . ")
						AND friendcount <> 0
				");
			}
			// this option actually means remove buddy in this case, do don't break so we fall through.
			if (!$vbulletin->GPC['friend'])
			{
				break;
			}
		case 'buddy':
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "userlist
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND relationid = $userinfo[userid]
					AND type = 'buddy'
			");
			if ($db->affected_rows())
			{
				$users[] = $vbulletin->userinfo['userid'];

				// The user could have been a friend too
				list($pendingcount) = $db->query_first("
					SELECT COUNT(*)
					FROM " . TABLE_PREFIX . "userlist AS userlist
					LEFT JOIN " . TABLE_PREFIX . "userlist AS userlist_ignore ON(userlist_ignore.userid = " . $userinfo['userid'] . " AND userlist_ignore.relationid = userlist.userid AND userlist_ignore.type = 'ignore')
					WHERE userlist.relationid = " . $userinfo['userid'] . "
						AND userlist.type = 'buddy'
						AND userlist.friend = 'pending'
						AND userlist_ignore.type IS NULL", DBARRAY_NUM
				);

				$db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET friendreqcount = $pendingcount
					WHERE userid = " . $userinfo['userid']
				);
			}
		break;
		case 'ignore':
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "userlist
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND relationid = $userinfo[userid]
					AND type = 'ignore'
			");
			if ($db->affected_rows())
			{
				$users[] = $vbulletin->userinfo['userid'];
			}
		break;
		default:
			standard_error(fetch_error('invalidid', 'list', $vbulletin->options['contactuslink']));
	}

	require_once(DIR . '/includes/functions_databuild.php');
	foreach($users AS $userid)
	{
		build_userlist($userid);
	}

	($hook = vBulletinHook::fetch_hook('profile_doremovelist_complete')) ? eval($hook) : false;

	print_standard_redirect(array('redirect_removelist_' . $vbulletin->GPC['userlist'],$userinfo['username']), true, true);  
}

// ############################### start update list ###############################
if ($_POST['do'] == 'updatelist')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'userlist'       => TYPE_NOHTML,
		'listbits'       => TYPE_ARRAY_ARRAY,
		'username'       => TYPE_NOHTML,
		'ajax'           => TYPE_BOOL,
		'makefriends'    => TYPE_BOOL, // value doesn't matter since we're using GPC_exists
		'incomingaction' => TYPE_NOHTML,
	));

	$list_types = array('buddy', 'ignore');

	$clean_lists = array();

	foreach ($vbulletin->GPC['listbits'] AS $type => $val)
	{
		$clean_lists["$type"] = array_map('intval', array_keys($vbulletin->GPC['listbits']["$type"]));
	}

	$remove = $add = array();
	$remove['friend'] = $remove['buddy'] = $remove['ignore'] = $remove['approvals'] = array();

	($hook = vBulletinHook::fetch_hook('profile_updatelist_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['userlist'] == 'buddy')
	{ // FRIENDS LIST, BUDDY LIST or PENDING FRIENDS
		foreach ($clean_lists AS $type => $val)
		{
			switch ($type)
			{
				case 'friend_original':
				{ // someone who is currently my friend, if they are missing then I dont want to be their friend
					if (sizeof($clean_lists['friend_original']) != sizeof($clean_lists['friend']))
					{
						$remove['friend'] = array_merge($remove['friend'], array_diff($clean_lists['friend_original'], (is_array($clean_lists['friend']) ? $clean_lists['friend'] : array())));
					}
				}
				break;

				case 'buddy_original':
				{ // someone who is simply just a buddy or has denied me friend access, if they are missing from the buddy then they were deleted
					if (sizeof($clean_lists['buddy_original']) != sizeof($clean_lists['buddy']))
					{
						$remove['buddy'] = array_merge($remove['buddy'], array_diff($clean_lists['buddy_original'], (is_array($clean_lists['buddy']) ? $clean_lists['buddy'] : array())));
					}
				}
				break;

				default:
					($hook = vBulletinHook::fetch_hook('profile_updatelist_listtype')) ? eval($hook) : false;
				break;
			}
		}

		if (!empty($vbulletin->GPC['username']))
		{ // friend request
			if ($vbulletin->GPC['ajax'])
			{
				$vbulletin->GPC['username'] = convert_urlencoded_unicode($vbulletin->GPC['username']);
			}

			if ($userinfo = $db->query_first("
				SELECT user.userid, userlist.friend, user.options, user.username, user.membergroupids, user.usergroupid, user.email, user.languageid
				FROM " . TABLE_PREFIX . "user AS user
				LEFT JOIN " . TABLE_PREFIX . "userlist AS userlist ON (userlist.relationid = user.userid AND userlist.userid = " . $vbulletin->userinfo['userid'] . " AND type = 'buddy')
				WHERE username = '" . $db->escape_string(vbstrtolower($vbulletin->GPC['username'])) . "'
			") AND (!$vbulletin->GPC_exists['makefriends'] OR $userinfo['userid'] != $vbulletin->userinfo['userid']))
			{ // user exists and its either not making friends or the user id is different

				cache_permissions($userinfo);

				if
				(
					$vbulletin->GPC_exists['makefriends']
					AND $vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends']
					AND $vbulletin->userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends']
					AND $userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends']
				)
				{ // Only add the request if its not there
					if (empty($userinfo['friend']) OR $userinfo['friend'] == 'no')
					{
						$add['friend']["$userinfo[userid]"] = $userinfo;
						$show['pending'] = true;
					}
				}
				else
				{ // regular buddy
					if (empty($userinfo['friend']))
					{ // we're not already a buddy so re-add it
						$add['buddy']["$userinfo[userid]"] = $userinfo;
					}
				}
			}
			else if ($userinfo['userid'] == $vbulletin->userinfo['userid'])
			{
				eval(standard_error(fetch_error('friendswithself')));
			}
			else
			{
				eval(standard_error(fetch_error('listbaduser', $vbulletin->GPC['username'], $vbulletin->session->vars['sessionurl_q'])));
			}
		}

		// Friends we've checked through this method will already be on the buddy list, since you can't have a friend without a buddy.
		if (is_array($clean_lists['friend']))
		{
			$newuser = array();
			foreach ($clean_lists['friend'] AS $userid)
			{
				if (!isset($clean_lists['friend_original']["$userid"]))
				{
					$newuser[] = $userid;
				}
			}

			if (!empty($newuser))
			{
				$userdata = $db->query_read("
					SELECT user.userid, userlist.friend, user.options, user.username, user.membergroupids, user.usergroupid, user.email, user.languageid
					FROM " . TABLE_PREFIX . "user AS user
					LEFT JOIN " . TABLE_PREFIX . "userlist AS userlist ON (userlist.relationid = user.userid AND userlist.userid = " . $vbulletin->userinfo['userid'] . " AND type = 'buddy')
					WHERE user.userid IN (" . implode(',', $newuser) . ")
				");
				while ($userinfo = $db->fetch_array($userdata))
				{
					cache_permissions($userinfo);
					if
					(
					!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends'])
					OR !($vbulletin->userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends'])
					OR !($userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends'])
					OR $vbulletin->userinfo['userid'] == $userinfo['userid']
					)
					{
						continue;
					}

					if (empty($userinfo['friend']) OR $userinfo['friend'] == 'no')
					{
						$add['friend']["$userinfo[userid]"] = $userinfo;
						$show['pending'] = true;
					}
				}
			}
		}
	}
	else if ($vbulletin->GPC['userlist'] == 'incoming')
	{ // APPROVAL OF NEW FRIENDS
		if (is_array($clean_lists['incoming']))
		{
			foreach ($clean_lists['incoming'] AS $userid)
			{
				if ($vbulletin->GPC['incomingaction'] == 'accept')
				{
					$add['approvals']["$userid"] = $userid;
				}
				else
				{
					$remove['approvals']["$userid"] = $userid;
				}
			}
		}
	}
	else
	{ // IGNORE LIST
		$vbulletin->GPC['userlist'] = 'ignore';
		if (!empty($clean_lists['ignore_original']))
		{
			$remove['ignore'] = array_merge($remove['ignore'], array_diff($clean_lists['ignore_original'], (is_array($clean_lists['ignore']) ? $clean_lists['ignore'] : array())));
		}

		if (!empty($vbulletin->GPC['username']))
		{
			if ($vbulletin->GPC['ajax'])
			{
				$vbulletin->GPC['username'] = convert_urlencoded_unicode($vbulletin->GPC['username']);
			}

			if ($userinfo = $db->query_first("
				SELECT userid, username, usergroupid, membergroupids
				FROM " . TABLE_PREFIX . "user AS user
				WHERE username = '" . $db->escape_string(vbstrtolower($vbulletin->GPC['username'])) . "'
			"))
			{
				$uglist = $userinfo['usergroupid'] . iif(trim($userinfo['membergroupids']), ",$userinfo[membergroupids]");
				if (!$vbulletin->options['ignoremods'] AND can_moderate(0, '', $userinfo['userid'], $uglist) AND !($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
				{
					eval(standard_error(fetch_error('listignoreuser', $userinfo['username'])));
				}
				else if ($vbulletin->userinfo['userid'] == $userinfo['userid'])
				{
					eval(standard_error(fetch_error('cantlistself_ignore')));
				}
				$add['ignore']["$userinfo[userid]"] = $userinfo;
			}
			else
			{
				eval(standard_error(fetch_error('listbaduser', $vbulletin->GPC['username'], $vbulletin->session->vars['sessionurl_q'])));
			}
		}
	}

	/*
		$remove['buddy'] contains records of people to delete entries on our side for
		$remove['ignore'] contains people we want to take off of our list
	*/
	$rebuild_friendreqcount = array();

	($hook = vBulletinHook::fetch_hook('profile_updatelist_process')) ? eval($hook) : false;

	if (!empty($remove['approvals']))
	{
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "userlist
			SET friend = 'denied'
			WHERE type = 'buddy'
				AND friend = 'pending'
				AND relationid = " . $vbulletin->userinfo['userid']  . "
				AND userid IN (" . implode(',', $remove['approvals']) . ")
		");
		$rebuild_friendreqcount[$vbulletin->userinfo['userid']] = true;
	}

	if (!empty($remove['buddy']) OR !empty($remove['ignore']) OR !empty($remove['friend']))
	{
		if (!empty($remove['buddy']))
		{
			/* Deal with friend request count */
			$decrement_friends = array();
			$friends = $db->query_read("
				SELECT *
				FROM " . TABLE_PREFIX . "userlist
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND type = 'buddy'
					AND friend = 'yes'
					AND relationid IN (" . implode($remove['buddy'], ', ') . ")
			");
			while ($friend = $db->fetch_array($friends))
			{
				$decrement_friends[] = $friend['relationid'];
			}

			if (!empty($decrement_friends))
			{
				$rebuild_my_friendcount = true;
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET friendcount = IF(friendcount >= 1, friendcount - 1, 0)
					WHERE userid IN (" . implode($decrement_friends, ', ') . ")
				");
			}

			/* Deal with pending friend request count */
			$decrement_pending = array();
			$pendingsreqs = $db->query_read("
				SELECT *
				FROM " . TABLE_PREFIX . "userlist
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND type = 'buddy'
					AND friend = 'pending'
					AND relationid IN (" . implode($remove['buddy'], ', ') . ")
			");
			while ($pendingreq = $db->fetch_array($pendingsreqs))
			{
				$decrement_pending[] = $pendingreq['relationid'];
			}

			if (!empty($decrement_pending))
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET friendreqcount = IF(friendreqcount >= 1, friendreqcount - 1, 0)
					WHERE userid IN (" . implode($decrement_pending, ', ') . ")
				");
			}

			/* Perform the actual delete */
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "userlist
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND type = 'buddy'
					AND relationid IN (" . implode($remove['buddy'], ', ') . ")
			");
			# remove friendships that already exist
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "userlist
				SET friend = 'no'
				WHERE type='buddy'
					AND friend <> 'no'
					AND relationid = " . $vbulletin->userinfo['userid'] . "
					AND userid IN (" . implode($remove['buddy'], ', ') . ")
			");
		}

		if (!empty($remove['friend']))
		{
			// Remove my friends
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "userlist
				SET friend = 'no'
				WHERE type = 'buddy'
					AND friend <> 'no'
					AND userid = " . $vbulletin->userinfo['userid'] . "
					AND relationid IN (" . implode($remove['friend'], ', ') . ")
			");

			$updatecount_sql = $db->query_read("
				SELECT userid
				FROM " . TABLE_PREFIX . "userlist
				WHERE type = 'buddy'
					AND friend <> 'no'
					AND relationid = " . $vbulletin->userinfo['userid'] . "
					AND userid IN (" . implode($remove['friend'], ', ') . ")
			");

			$updatecount_userids = array();
			while ($updatecount = $db->fetch_array($updatecount_sql))
			{
				$updatecount_userids[] = $updatecount['userid'];
			}

			if ($updatecount_userids)
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET friendcount = IF(friendcount >= 1, friendcount - 1, 0)
					WHERE userid IN (" . implode($updatecount_userids, ', ') . ")
				");
			}

			// Remove their reference too
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "userlist
				SET friend = 'no'
				WHERE type = 'buddy'
					AND friend <> 'no'
					AND relationid = " . $vbulletin->userinfo['userid'] . "
					AND userid IN (" . implode($remove['friend'], ', ') . ")
			");

			$rebuild_my_friendcount = true;
		}

		if (!empty($remove['ignore']))
		{
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "userlist
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND type = 'ignore'
					AND relationid IN (" . implode($remove['ignore'], ', ') . ")
			");
			$rebuild_friendreqcount[$vbulletin->userinfo['userid']] = true;
		}
	}

	if (!empty($add))
	{ // It is possible to have multiple ADD calls when you're approving people. Just in case you think it should be only one value.
		$addvalues = array();
		foreach ($add AS $value)
		{
			if (is_array($value))
			{
				foreach ($value AS $userinfo)
				{
					if (!empty($userinfo['userid']))
					{
						$addvalues[] = $userinfo['userid'];
					}
				}
			}
			else
			{
				$addvalues[] = !empty($value['userid']) ? $value['userid'] : intval($value);
			}
		}

		if (empty($add['approvals']))
		{ // We need to know a previous state.
			$current_statuses = $db->query_read("
				SELECT *
				FROM " . TABLE_PREFIX . "userlist
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND relationid IN (" . implode($addvalues, ', ') . ")
			");
			$usercache = array();
			while ($current_status = $db->fetch_array($current_statuses))
			{
				$usercache["$current_status[type]"]["$current_status[relationid]"] = $current_status;
			}

			if (!empty($add['friend']))
			{
				// Another query to fill the cache, this is looking on the other site of the arrangement to see if they're waiting on pending too. We should instantly just upgrade this to friend.
				$pending_checks = $db->query_read("
					SELECT *
					FROM " . TABLE_PREFIX . "userlist
					WHERE relationid = " . $vbulletin->userinfo['userid'] . "
						AND userid IN (" . implode(array_keys($add['friend']), ', ') . ")
				");
				$pendingcache = array();
				while ($pending_check = $db->fetch_array($pending_checks))
				{
					$pendingcache["$pending_check[type]"]["$pending_check[userid]"] = $pending_check;
				}

				$browsing_user_in_coventry = in_coventry($vbulletin->userinfo['userid'], true);

				foreach ($add['friend'] AS $userid => $userinfo)
				{
					if (isset($usercache['buddy']["$userid"]) AND $usercache['buddy']["$userid"]['friend'] == 'yes')
					{
						continue;
					}

					if (isset($pendingcache['buddy']["$userid"]) AND $pendingcache['buddy']["$userid"]['friend'] == 'pending')
					{
						$add['approvals'][] = $userid;
						continue;
					}

					if (isset($pendingcache['buddy']["$userid"]) AND $pendingcache['buddy']["$userid"]['friend'] == 'denied')
					{ // If they were denied last time you must have changed your mind, remove the block so its just a buddy
						$db->query_write("UPDATE " . TABLE_PREFIX . "userlist set friend = 'no' WHERE userid = $userid AND relationid = " . $vbulletin->userinfo['userid']);
					}

					$db->query_write("
						REPLACE INTO " . TABLE_PREFIX . "userlist
						(userid, relationid, type, friend)
							VALUES
						(" . $vbulletin->userinfo['userid'] . ", " . intval($userinfo['userid']) . ", 'buddy', 'pending')
					");

					($hook = vBulletinHook::fetch_hook('profile_updatelist_addfriend')) ? eval($hook) : false;

					// Send notification to user that a friend request has been made for them
					$userinfo = array_merge($userinfo , convert_bits_to_array($userinfo['options'] , $vbulletin->bf_misc_useroptions));
					$cansendemail = (($userinfo['adminemail'] OR $userinfo['showemail']) AND $vbulletin->options['enableemail'] AND $vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canemailmember']);

					if ($cansendemail AND $userinfo['options'] & $vbulletin->bf_misc_useroptions['receivefriendemailrequest']
						AND !isset($usercache['ignore']["$userid"]) // I'm not ignoring them
						AND !isset($pendingcache['ignore']["$userid"]) // they're not ignoring me
						AND !$browsing_user_in_coventry
					)
					{
						$fromuserinfo =& $vbulletin->userinfo;
						$touserinfo =& $userinfo;

						eval(fetch_email_phrases('friendship_request_email', $touserinfo['languageid']));
						require_once(DIR . '/includes/class_bbcode_alt.php');
						$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());
						$plaintext_parser->set_parsing_language($touserinfo['languageid']);
						$message = $plaintext_parser->parse($message, 'privatemessage');
						vbmail($touserinfo['email'], $subject, $message);
					}

					$rebuild_friendreqcount[$userid] = true;

				}
			}
			else if (!empty($add['buddy']))
			{ // We only want a record if one doesn't exist
				foreach($add['buddy'] AS $userid => $touserinfo)
				{
					if (isset($usercache['buddy']["$userid"]))
					{
						continue;
					}
					$db->query_write("
						INSERT IGNORE INTO " . TABLE_PREFIX . "userlist
							(userid, relationid, type, friend)
						VALUES
							(" . $vbulletin->userinfo['userid'] . ", " . intval($userid) . ", 'buddy', 'no')
					");
				}
			}
			else if (!empty($add['ignore']))
			{ // Adding someone to the ignore again is fine
				foreach($add['ignore'] AS $userid => $touserinfo)
				{
					$db->query_write("
						INSERT IGNORE INTO " . TABLE_PREFIX . "userlist
						(userid, relationid, type, friend)
							VALUES
						(" . $vbulletin->userinfo['userid'] . ", " . intval($userid) . ", 'ignore', 'no')
					");
				}
				$rebuild_friendreqcount[$vbulletin->userinfo['userid']] = true;
			}
		}

		// This may look "special" compared to above, but it shouldn't be an else. The condition block above can add an entry to $add[approvals]
		if (!empty($add['approvals']))
		{ // Approving a bunch of users, make sure we get an entry too
			$updatecount_sql = $db->query_read("
				SELECT userid
				FROM " . TABLE_PREFIX . "userlist
				WHERE type = 'buddy'
					AND friend = 'pending'
					AND relationid = " . $vbulletin->userinfo['userid']  . "
					AND userid IN (" . implode(',', $add['approvals']) . ")
			");

			$updatecount_userids = array();
			while ($updatecount = $db->fetch_array($updatecount_sql))
			{
				$updatecount_userids[] = $updatecount['userid'];
			}

			if ($updatecount_userids)
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET friendcount = friendcount + 1
					WHERE userid IN (" . implode(',', $updatecount_userids) . ")
				");
			}

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "userlist
				SET friend = 'yes'
				WHERE type = 'buddy'
					AND friend = 'pending'
					AND relationid = " . $vbulletin->userinfo['userid']  . "
					AND userid IN (" . implode(',', $add['approvals']) . ")
			");

			$replacesql = array();
			foreach ($add['approvals'] AS $userid)
			{
				$replacesql[] = "(" . $vbulletin->userinfo['userid'] . ", $userid, 'buddy', 'yes')";
			}
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "userlist
					(userid, relationid, type, friend)
				VALUES
					" . implode(", ", $replacesql) . "
			");

			$rebuild_my_friendcount = true;
			$rebuild_friendreqcount[$vbulletin->userinfo['userid']] = true;
		}
	}

	if (!empty($rebuild_friendreqcount))
	{
		if (trim($vbulletin->options['globalignore']) != '')
		{
			$coventry = preg_split('#\s+#s', $vbulletin->options['globalignore'], -1, PREG_SPLIT_NO_EMPTY);
			$coventry_query = 'AND userlist.userid NOT IN (' . implode(',', $coventry) . ')';
		}
		else
		{
			$coventry_query = '';
		}

		foreach (array_keys($rebuild_friendreqcount) AS $userid)
		{
			// The user could have been a friend too
			list($pendingcount) = $db->query_first("
				SELECT COUNT(*)
				FROM " . TABLE_PREFIX . "userlist AS userlist
				LEFT JOIN " . TABLE_PREFIX . "userlist AS userlist_ignore ON
					(userlist_ignore.userid = " . $userid . " AND userlist_ignore.relationid = userlist.userid AND userlist_ignore.type = 'ignore')
				WHERE userlist.relationid = " . $userid . "
					$coventry_query
					AND userlist.type = 'buddy'
					AND userlist.friend = 'pending'
					AND userlist_ignore.type IS NULL", DBARRAY_NUM
			);

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "user
				SET friendreqcount = $pendingcount
				WHERE userid = " . $userid
			);
		}
	}

	if ($rebuild_my_friendcount)
	{
		list($myfriendcount) = $db->query_first("
			SELECT COUNT(*) FROM " . TABLE_PREFIX . "userlist
			WHERE userid = " . $vbulletin->userinfo['userid'] . "
				AND type = 'buddy'
				AND friend = 'yes'", DBARRAY_NUM
		);

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET friendcount = $myfriendcount
			WHERE userid = " . $vbulletin->userinfo['userid']
		);
	}

	/* Todo, force the cache variable (if we can) */
	require_once(DIR . '/includes/functions_databuild.php');
	build_userlist($vbulletin->userinfo['userid']);
	$show["{$vbulletin->GPC['userlist']}"] = true;

	($hook = vBulletinHook::fetch_hook('profile_updatelist_complete')) ? eval($hook) : false;

	if ($vbulletin->GPC['ajax'])
	{
		$ajax = true;
		$_REQUEST['do'] = ($vbulletin->GPC['userlist'] == 'ignore' ? 'ignorelist' : 'buddylist');
	}
	else
	{
		print_standard_redirect('updatelist_' . $vbulletin->GPC['userlist']);  
	}
}

// ################# start edit buddy list ###############
if ($_REQUEST['do'] == 'buddylist')
{
	$perpage = $vbulletin->input->clean_gpc('r', 'perpage', TYPE_UINT);
	$pagenumber = $vbulletin->input->clean_gpc('r', 'pagenumber', TYPE_UINT);
	$vbulletin->input->clean_array_gpc('r', array(
		'filter' => TYPE_NOHTML
	));

	$vbulletin->input->clean_array_gpc('p', array(
		'ajax' => TYPE_BOOL,
	));

	if ($vbulletin->GPC['ajax'])
	{
		$ajax = true;
	}

	$buddylist = '';
	$incominglist = '';
	$friend_list = array();

	$js_userlist = array();

	$show['friend_controls'] = ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends'] AND $vbulletin->userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends']);

	$perpage = (!$perpage OR $perpage > 100) ? 20 : $perpage;
	$pagenumber = !$vbulletin->GPC['pagenumber'] ? 1 : $vbulletin->GPC['pagenumber'];
	$totalfriends = 0;

	$condition1 = $condition2 = array(
		"userlist.userid = " . $vbulletin->userinfo['userid'],
		"userlist.type = 'buddy'"
	);
	if ($vbulletin->GPC['filter'])
	{
		$condition1[] = "user.username LIKE '" . $vbulletin->db->escape_string($vbulletin->GPC['filter']) . "%'";
	}

	$redo = false;
	do
	{
		$start = ($pagenumber - 1) * $perpage;

		$users_result = $db->query_read_slave("
			SELECT SQL_CALC_FOUND_ROWS
				user.*, userlist.type, userlist.friend
				" . ($vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width_thumb AS avwidth_thumb, customavatar.height_thumb AS avheight_thumb, customavatar.width as avwidth, customavatar.height as avheight, customavatar.filedata_thumb' : '') . "
			FROM " . TABLE_PREFIX . "userlist AS userlist
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = userlist.relationid)
			" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid) " : '') . "
			WHERE
				" . implode($condition1, " AND ") . "
			ORDER BY user.username
			LIMIT $start, $perpage
		");

		$totalfriends = $db->found_rows();

		// Switch to condition with no filter
		if (!$totalfriends AND $vbulletin->GPC['filter'] AND !$redo)
		{
			$condition1 = $condition2;
			$redo = true;
		}
		else
		{
			if ($start >= $totalfriends)
			{
				$pagenumber = ceil($totalfriends / $perpage);
			}
			$redo = false;
		}
	}
	while (($start >= $totalfriends AND $totalfriends) OR $redo);

	while ($user = $db->fetch_array($users_result))
	{

		$user['extended_type'] = $user['type'];
		if ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends'])
		{
			switch ($user['friend'])
			{
				case 'yes':
					$user['extended_type'] = 'friend';
				break;
				case 'pending':
				case 'denied':
					$user['extended_type'] = 'outgoing';
				break;
				default:
					($hook = vBulletinHook::fetch_hook('profile_contactlist_listtype')) ? eval($hook) : false;
			}
		}
		fetch_avatar_from_userinfo($user, true);
		cache_permissions($user);

		$container = 'buddylist';
		$show['incomingrequest'] = false;
		$show['outgoingrequest'] = ($user['extended_type'] == 'outgoing');
		$friendcheck_checked = ($user['extended_type'] == 'friend' ? ' checked="checked"' : '');
		$user['checked'] = ' checked="checked"';
		$friend_list["$user[userid]"] = $user['friend'];

		$js_userlist["$user[username]"] = $user['userid'];

		$show['friend_checkbox'] = (($show['friend_controls'] AND ($user['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends']) AND $vbulletin->userinfo['userid'] != $user['userid']) OR (!empty($friendcheck_checked) AND $vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends']));
		$templater = vB_Template::create('modifybuddylist_user');
			$templater->register('container', $container);
			$templater->register('friendcheck_checked', $friendcheck_checked);
			$templater->register('user', $user);
		$buddylist .= $templater->render();
	}

	$buddycount = $totalfriends;

	$sorturl = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=buddylist';
	if ($perpage != 20)
	{
		$sorturl .= "&amp;pp=$perpage";
	}
	if ($vbulletin->GPC['filter'])
	{
		$sorturl .= "&amp;filter=" . $vbulletin->GPC['filter'];
	}
	$pagenav = construct_page_nav($pagenumber, $perpage, $totalfriends, $sorturl);

	if (trim($vbulletin->options['globalignore']) != '')
	{
		$coventry = preg_split('#\s+#s', $vbulletin->options['globalignore'], -1, PREG_SPLIT_NO_EMPTY);
		$coventry_query = 'AND userlist.userid NOT IN (' . implode(',', $coventry) . ')';
	}
	else
	{
		$coventry_query = '';
	}

	$incomingcount = 0;
	$users_result = $db->query_read_slave("
		SELECT
			user.*, userlist.type, userlist.friend
		" . ($vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width_thumb AS avwidth_thumb, customavatar.height_thumb AS avheight_thumb, customavatar.width as avwidth, customavatar.height as avheight, customavatar.filedata_thumb' : '') . "
		FROM " . TABLE_PREFIX . "userlist AS userlist
		LEFT JOIN " . TABLE_PREFIX . "userlist AS userlist_ignore ON
			(userlist_ignore.userid = " . $vbulletin->userinfo['userid'] . " AND userlist_ignore.relationid = userlist.userid AND userlist_ignore.type = 'ignore')
		INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = userlist.userid)
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid) " : '') . "
		WHERE userlist.relationid = " . $vbulletin->userinfo['userid'] . "
			$coventry_query
			AND userlist.type = 'buddy'
			AND userlist.friend = 'pending'
			AND userlist_ignore.type IS NULL
		ORDER BY user.username
	");
	while ($user = $db->fetch_array($users_result))
	{
		// User is a friend already, the other side must have a broken relationship. update theirs
		if ($friend_list["$user[userid]"] == 'yes')
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "userlist
				SET friend = 'yes'
				WHERE relationid = " . $vbulletin->userinfo['userid'] . "
					AND userid = " . $user['userid'] . "
					AND type = 'buddy'
			");
			continue;
		}

		$user['extended_type'] = $user['type'] = 'incoming';
		fetch_avatar_from_userinfo($user, true);

		$container = 'incomingreqs';
		$show['incomingrequest'] = true;
		$show['outgoingrequest'] = false;
		$friendcheck_checked = '';
		$show['friend_checkbox'] = false;
		$incomingcount++;

		$js_userlist["$user[username]"] = $user['userid'];

		$templater = vB_Template::create('modifybuddylist_user');
			$templater->register('container', $container);
			$templater->register('friendcheck_checked', $friendcheck_checked);
			$templater->register('user', $user);
		$incominglist .= $templater->render();
	}

	$show['incominglist'] = !empty($incominglist);
	$show['buddylist'] = !empty($buddylist);

	// Adjust the friend req count if it doesn't match what we really have
	if ($_GET['do'] == 'buddylist' AND $vbulletin->userinfo['friendreqcount'] != $incomingcount)
	{
		$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
		$userdata->set_condition("userid = " . $vbulletin->userinfo['userid'] . " AND friendreqcount = " . $vbulletin->userinfo['friendreqcount']);
		$userdata->set('friendreqcount', $incomingcount);
		$userdata->save();
	}

	if ($ajax)
	{
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

		$xml->add_group('userlists');
			$xml->add_tag('userlist', process_replacement_vars($buddylist), array('type' => 'buddylist'));
			$xml->add_tag('userlist', process_replacement_vars($incominglist), array('type' => 'incomingreqs'));
			$xml->add_tag('pagenav', process_replacement_vars($pagenav));
			$xml->add_tag('pagenumber', $pagenumber);

			$xml->add_group('counts');
				$xml->add_tag('buddycount', $totalfriends);
			$xml->close_group();

			$xml->add_group('rollcall');

				foreach ($js_userlist AS $username => $id)
				{
					$xml->add_tag('user', false, array('username' => $username, 'userid' => $id));
				}

			$xml->close_group();

		$xml->close_group();

		$xml->print_xml();
		exit;
	}
	else
	{
		// build JS username array
		$js_userlist_array = array();
		foreach ($js_userlist AS $username => $userid)
		{
			$js_userlist_array[] = "\"$username\" : $userid";
		}
		$js_userlist_array = implode(",\n\t", $js_userlist_array);

		// draw cp nav bar
		construct_usercp_nav('buddylist');

		if ($show['friend_controls'])
		{
			$navbits[''] = $vbphrase['contacts_and_friends'];
		}
		else
		{
			$navbits[''] = $vbphrase['contacts'];
		}

		$showavatarchecked = ($vbulletin->userinfo['showavatars'] ? ' checked="checked"' : '');
		$show['avatars'] = $vbulletin->userinfo['showavatars'];
		$includecss['buddylist'] = 'buddylist.css';

		$page_templater = vB_Template::create('modifybuddylist');
		$page_templater->register('buddycount', $totalfriends);
		$page_templater->register('buddylist', $buddylist);
		$page_templater->register('buddy_username', $buddy_username);
		$page_templater->register('incominglist', $incominglist);
		$page_templater->register('js_userlist_array', $js_userlist_array);
		$page_templater->register('showavatarchecked', $showavatarchecked);
		$page_templater->register('perpage', $perpage);
		$page_templater->register('pagenumber', $pagenumber);
		$page_templater->register('pagenav', $pagenav);
		$page_templater->register('filtertext', $vbulletin->GPC['filter']);
	}
}

// ################# start edit ignore list ###############
if ($_REQUEST['do'] == 'ignorelist')
{
	$ignorelist = '';
	$users_result = $db->query_read_slave("
		SELECT user.*, userlist.type
		FROM " . TABLE_PREFIX . "userlist AS userlist
		INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = userlist.relationid)
		WHERE userlist.userid = " . $vbulletin->userinfo['userid'] . " AND userlist.type = 'ignore'
		ORDER BY user.username
	");
	while ($user = $db->fetch_array($users_result))
	{
		$templater = vB_Template::create('modifyignorelist_user');
			$templater->register('user', $user);
		$ignorelist .= $templater->render();
	}

	$show['ignorelist'] = !empty($ignorelist);

	if ($ajax)
	{
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('userlists');
		$xml->add_tag('userlist', process_replacement_vars($ignorelist), array('type' => 'ignorelist'));
		$xml->close_group();
		$xml->print_xml();
		exit;
	}
	else
	{
		// draw cp nav bar
		construct_usercp_nav('ignorelist');
		$includecss['buddylist'] = 'buddylist.css';

		$navbits[''] = $vbphrase['edit_ignore_list'];

		$page_templater = vB_Template::create('modifyignorelist');
		$page_templater->register('ignorelist', $ignorelist);
		$page_templater->register('ignore_username', $ignore_username);
	}
}

// ############################################################################
// ALL FUNCTIONS BELOW HERE REQUIRE 'canmodifyprofile' PERMISSION, SO CHECK IT

if (!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canmodifyprofile']) AND empty($page_templater))
{
	print_no_permission();
}

// ############################################################################
// ############################### EDIT PROFILE ###############################
// ############################################################################
if ($_REQUEST['do'] == 'editprofile')
{
	unset($tempcustom); // from functions_user.php?

	($hook = vBulletinHook::fetch_hook('profile_editprofile_start')) ? eval($hook) : false;

	exec_switch_bg();
	// Set birthday fields right here!
	if (empty($vbulletin->userinfo['birthday']))
	{
		$dayselected['default'] = 'selected="selected"';
		$monthselected['default'] = 'selected="selected"';
	}
	else
	{
		$birthday = explode('-', $vbulletin->userinfo['birthday']);

		$dayselected["$birthday[1]"] = 'selected="selected"';
		$monthselected["$birthday[0]"] = 'selected="selected"';

		if (date('Y') >= $birthday[2] AND $birthday[2] != '0000')
		{
			$year = $birthday[2];
		}
	}
	$sbselected = array($vbulletin->userinfo['showbirthday'] => 'selected="selected"');

	// custom user title
	if ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusecustomtitle'])
	{
		// fetch_musername modifies this value. How evil!
		if ($vbulletin->userinfo['customtitle'] == 2 AND !isset($vbulletin->userinfo['musername']))
		{
			$vbulletin->userinfo['usertitle'] = htmlspecialchars_uni($vbulletin->userinfo['usertitle']);
		}
		$show['customtitleoption'] = true;
	}
	else
	{
		$show['customtitleoption'] = false;
	}

	require_once(DIR . '/includes/functions_misc.php');
	// Set birthday required or optional
	$show['birthday_readonly'] = false;
	if ($vbulletin->options['reqbirthday'])
	{
		$show['birthday_required'] = true;
		if ($birthday[2] > 1901 AND $birthday[2] <= date('Y') AND @checkdate($birthday[0], $birthday[1], $birthday[2]))
		{
			$vbulletin->options['calformat1'] = mktimefix($vbulletin->options['calformat1'], $birthday[2]);
			if ($birthday[2] >= 1970)
			{
				$yearpass = $birthday[2];
			}
			else
			{
				// day of the week patterns repeat every 28 years, so
				// find the first year >= 1970 that has this pattern
				$yearpass = $birthday[2] + 28 * ceil((1970 - $birthday[2]) / 28);
			}
			$birthdate = vbdate($vbulletin->options['calformat1'], mktime(0, 0, 0, $birthday[0], $birthday[1], $yearpass), false, true, false);
			$show['birthday_readonly'] = true;
		}
	}
	else
	{
		$show['birthday_optional'] = true;
	}

	// Get Custom profile fields
	$customfields = array();
	fetch_profilefields(0);

	// draw cp nav bar
	construct_usercp_nav('profile');

	$templater = vB_Template::create('modifyprofile_birthday');
		$templater->register('birthdate', $birthdate);
		$templater->register('dayselected', $dayselected);
		$templater->register('monthselected', $monthselected);
		$templater->register('sbselected', $sbselected);
		$templater->register('year', $year);
	$birthdaybit = $templater->render();

	$navbits[''] = $vbphrase['edit_your_details'];

	$page_templater = vB_Template::create('modifyprofile');
	$page_templater->register('birthdaybit', $birthdaybit);
	$page_templater->register('customfields', $customfields);
}

// ############################### start update profile ###############################
if ($_POST['do'] == 'updateprofile')
{
	$vbulletin->input->clean_array_gpc('p', array(
		// coppa stuff
		'coppauser'    => TYPE_BOOL,
		'parentemail'  => TYPE_STR,
		// IM handles / homepage
		'aim'          => TYPE_STR,
		'yahoo'        => TYPE_STR,
		'icq'          => TYPE_STR,
		'msn'          => TYPE_STR,
		'skype'        => TYPE_STR,
		'homepage'     => TYPE_STR,
		// user title
		'resettitle'   => TYPE_STR,
		'customtext'   => TYPE_STR,
		// birthday fields
		'day'          => TYPE_INT,
		'month'        => TYPE_INT,
		'year'         => TYPE_STR,
		'oldbirthday'  => TYPE_STR,
		'showbirthday' => TYPE_UINT,
		// redirect button
		'gotopassword' => TYPE_NOCLEAN,
		// custom profile fields
		'userfield'    => TYPE_ARRAY,
	));

	// don't make the password button submit all the details; this is confusing to users
	if (!empty($vbulletin->GPC['gotopassword']))
	{
		exec_header_redirect('profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editpassword');
		exit;
	}

	// init user data manager
	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($vbulletin->userinfo);

	// coppa stuff
	$userdata->set_info('coppauser', $vbulletin->GPC['coppauser']);
	$userdata->set('parentemail', $vbulletin->GPC['parentemail']);

	// easy stuff
	$userdata->set('icq', $vbulletin->GPC['icq']);
	$userdata->set('msn', $vbulletin->GPC['msn']);
	$userdata->set('aim', $vbulletin->GPC['aim']);
	$userdata->set('yahoo', $vbulletin->GPC['yahoo']);
	$userdata->set('skype', $vbulletin->GPC['skype']);
	$userdata->set('homepage', $vbulletin->GPC['homepage']);
	$userdata->set('birthday', $vbulletin->GPC);
	$userdata->set('showbirthday', $vbulletin->GPC['showbirthday']);

	// custom profile fields
	$userdata->set_userfields($vbulletin->GPC['userfield']);

	if ($vbulletin->userinfo['usertitle'] != $vbulletin->GPC['customtext'] AND
		!($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) AND
		$vbulletin->options['ctMaxChars'] > 0
	)
	{
		// only trim title if changing custom title and not an admin
		$vbulletin->GPC['customtext'] = vbchop($vbulletin->GPC['customtext'], $vbulletin->options['ctMaxChars']);
	}

	// custom user title
	$userdata->set_usertitle(
		$vbulletin->GPC['customtext'],
		$vbulletin->GPC['resettitle'],
		$vbulletin->usergroupcache[$vbulletin->userinfo['displaygroupid']],
		($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusecustomtitle']) ? true : false,
		($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) ? true : false
	);

	($hook = vBulletinHook::fetch_hook('profile_updateprofile')) ? eval($hook) : false;

	// save the data
	$userdata->save();

	if ($vbulletin->session->vars['profileupdate'])
	{
		$vbulletin->session->set('profileupdate', 0);
	}

	$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editprofile';
	print_standard_redirect(array('redirect_updatethanks',$vbulletin->userinfo['username']), true, true);  
}

// ############################### start edit connections ###############################
if ($_REQUEST['do'] == 'editconnections')
{
	// if facebook connect is not enabled, go to the general settings page
	if (!is_facebookenabled())
	{
		$_REQUEST['do'] = 'editoptions';
	}
	else
	{
		($hook = vBulletinHook::fetch_hook('profile_editconnections_start')) ? eval($hook) : false;

		// draw cp nav bar
		construct_usercp_nav('connections');

		// set up navbits for shell template
		$navbits[''] = $vbphrase['edit_connections'];

		$show['fbaccount'] = !empty($vbulletin->userinfo['fbuserid']);

		// if user is Facebook only login, allow them to add a vbpassword
		$show['fbaddpasswordform'] = ($vbulletin->userinfo['logintype'] == 'fb');

		$page_templater = vB_Template::create('modifyconnections');
		$page_templater->register('fbuserid', $vbulletin->userinfo['fbuserid']);
		$page_templater->register('fbname', $vbulletin->userinfo['fbname']);
		$page_templater->register('fbjoindate', vbdate($vbulletin->options['dateformat'], $vbulletin->userinfo['fbjoindate'], true));
		$page_templater->register('fbjoindatetime', vbdate($vbulletin->options['timeformat'], $vbulletin->userinfo['fbjoindate']));
		$page_templater->register('fbprofileurl', get_fbprofileurl());
		$page_templater->register('fbprofilepicurl', get_fbprofilepicurl());
	}
}

// ############################################################################
// ############################### EDIT OPTIONS ###############################
// ############################################################################
if ($_REQUEST['do'] == 'editoptions')
{
	require_once(DIR . '/includes/functions_misc.php');

	($hook = vBulletinHook::fetch_hook('profile_editoptions_start')) ? eval($hook) : false;

	// check the appropriate checkboxes
	$checked = array();
	foreach ($vbulletin->userinfo AS $key => $val)
	{
		if ($val != 0)
		{
			$checked["$key"] = 'checked="checked"';
		}
		else

		{
			$checked["$key"] = '';
		}
	}

	// invisible option
	$show['invisibleoption'] = iif(bitwise($permissions['genericpermissions'], $vbulletin->bf_ugp_genericpermissions['caninvisible']), true, false);

	// Email members option
	$show['receiveemail'] = ($vbulletin->options['enableemail'] AND $vbulletin->options['displayemails']) ? true : false;

	// reputation options
	if ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canhiderep'] AND $vbulletin->options['reputationenable'])
	{
		if ($vbulletin->userinfo['showreputation'])
		{
			$checked['showreputation'] = 'checked="checked"';
		}
		$show['reputationoption'] = true;
	}
	else
	{
		$show['reputationoption'] = false;
	}

	// PM options
	$show['pmoptions'] = ($vbulletin->options['enablepms'] AND $permissions['pmquota'] > 0) ? true : false;
	$show['friend_email_request'] = (($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends']) AND
								($vbulletin->userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2) ? true : false);

	// VM Options
	$show['vmoptions'] = (
		$vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_visitor_messaging']
			AND
		$vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers']
	) ? true : false;

	// autosubscribe selected option
	$vbulletin->userinfo['autosubscribe'] = verify_subscription_choice($vbulletin->userinfo['autosubscribe'], $vbulletin->userinfo, 9999);
	$emailchecked = array($vbulletin->userinfo['autosubscribe'] => 'selected="selected"');

	// threaded mode options
	if ($vbulletin->userinfo['threadedmode'] == 1 OR $vbulletin->userinfo['threadedmode'] == 2)
	{
		$threaddisplaymode["{$vbulletin->userinfo['threadedmode']}"] = 'selected="selected"';
	}
	else
	{
		if ($vbulletin->userinfo['postorder'] == 0)
		{
			$threaddisplaymode[0] = 'selected="selected"';
		}
		else
		{
			$threaddisplaymode[3] = 'selected="selected"';
		}
	}

	// default days prune
	if ($vbulletin->userinfo['daysprune'] == 0)
	{
		$daysdefaultselected = 'selected="selected"';
	}
	else
	{
		if ($vbulletin->userinfo['daysprune'] == '-1')
		{
			$vbulletin->userinfo['daysprune'] = 'all';
		}
		$dname = 'days' . $vbulletin->userinfo['daysprune'] . 'selected';
		$$dname = 'selected="selected"';
	}

	// daylight savings time
	$selectdst = array();
	if ($vbulletin->userinfo['dstauto'])
	{
		$selectdst[2] = 'selected="selected"';
	}
	else if ($vbulletin->userinfo['dstonoff'])
	{
		$selectdst[1] = 'selected="selected"';
	}
	else
	{
		$selectdst[0] = 'selected="selected"';
	}

	require_once(DIR . '/includes/functions_misc.php');
	$timezoneoptions = '';
	foreach (fetch_timezone() AS $optionvalue => $timezonephrase)
	{
		$optiontitle = $vbphrase["$timezonephrase"];
		$optionselected = iif($optionvalue == $vbulletin->userinfo['timezoneoffset'], 'selected="selected"', '');
		$timezoneoptions .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
	}
	$templater = vB_Template::create('modifyoptions_timezone');
		$templater->register('selectdst', $selectdst);
		$templater->register('timezoneoptions', $timezoneoptions);
	$timezoneoptions = $templater->render();

	// start of the week
	if ($vbulletin->userinfo['startofweek'] > 0)
	{
		$dname = 'day' . $vbulletin->userinfo['startofweek'] . 'selected';
		$$dname = 'selected="selected"';
	}
	else
	{
		$day1selected = 'selected="selected"';
	}

	// bb code editor options
	if (!is_array($vbulletin->options['editormodes_array']))
	{
		$vbulletin->options['editormodes_array'] = unserialize($vbulletin->options['editormodes']);
	}
	$max_editormode = max($vbulletin->options['editormodes_array']);
	if ($vbulletin->userinfo['showvbcode'] > $max_editormode)
	{
		$vbulletin->userinfo['showvbcode'] = $max_editormode;
	}
	$show['editormode_picker'] = $max_editormode ? true : false;
	$show['editormode_wysiwyg'] = $max_editormode > 1 ? true : false;
	$checkvbcode = array($vbulletin->userinfo['showvbcode'] => ' checked="checked"');
	$selectvbcode = array($vbulletin->userinfo['showvbcode'] => ' selected="selected"');

	//MaxPosts by User
	$foundmatch = 0;
	if ($vbulletin->options['usermaxposts'])
	{
		$optionArray = explode(',', $vbulletin->options['usermaxposts']);
		foreach ($optionArray AS $optionvalue)
		{
			if ($optionvalue == $vbulletin->userinfo['maxposts'])
			{
				$optionselected = 'selected="selected"';
				$foundmatch = 1;
			}
			else
			{
				$optionselected = '';
			}
			$optiontitle = construct_phrase($vbphrase['show_x_posts_per_page'], $optionvalue);
			$maxpostsoptions .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
		}
	}
	if ($foundmatch == 0)
	{
		$postsdefaultselected = 'selected="selected"';
	}
	$show['maxpostsoptions'] = ($vbulletin->options['usermaxposts'] ? true : false);

	if (
		$vbulletin->options['allowchangestyles']
			OR
		$vbulletin->options['mobilestyleid_advanced']
			OR
		$vbulletin->options['mobilestyleid_basic']		
	)
	{
		$stylecount = 0;
		if ($vbulletin->stylecache !== null)
		{
			$stylesetlist1 = construct_style_options(-1, '', true, false, $stylecount);
			$stylesetlist2 = construct_style_options(-2, '', $stylesetlist1 ? false : true, false, $stylecount);
		}
		$show['styleoption'] = $stylecount > 1 ? true : false;
	}
	else
	{
		$show['styleoption'] = false;
	}

	// get language options
	$languagelist = '';
	$languages = fetch_language_titles_array('', 0);
	if (sizeof($languages) > 1)
	{
		foreach ($languages AS $optionvalue => $optiontitle)
		{
			$optionselected = iif($vbulletin->userinfo['saved_languageid'] == $optionvalue, 'selected="selected"', '');
			$languagelist .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
		}
		$show['languageoption'] = true;
	}
	else
	{
		$show['languageoption'] = false;
	}

	$bgclass1 = 'alt1'; // Login Section
	$bgclass3 = 'alt1'; // Messaging Section
	$bgclass3 = 'alt1'; // Thread View Section
	$bgclass4 = 'alt1'; // Date/Time Section
	$bgclass5 = 'alt1'; // Other Section

	// View other users' profile styling
	$show['usercssoption'] = $vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_profile_styling'];

	// Get custom otions
	$customfields = array();
	fetch_profilefields(1);

	// draw cp nav bar
	construct_usercp_nav('options');

	$navbits[''] = $vbphrase['edit_options'];

	$page_templater = vB_Template::create('modifyoptions');
	$page_templater->register('block_data', $block_data);
	$page_templater->register('checked', $checked);
	$page_templater->register('customfields', $customfields);
	$page_templater->register('day1selected', $day1selected);
	$page_templater->register('day2selected', $day2selected);
	$page_templater->register('day3selected', $day3selected);
	$page_templater->register('day4selected', $day4selected);
	$page_templater->register('day5selected', $day5selected);
	$page_templater->register('day6selected', $day6selected);
	$page_templater->register('day7selected', $day7selected);
	$page_templater->register('days1selected', $days1selected);
	$page_templater->register('days2selected', $days2selected);
	$page_templater->register('days7selected', $days7selected);
	$page_templater->register('days10selected', $days10selected);
	$page_templater->register('days14selected', $days14selected);
	$page_templater->register('days30selected', $days30selected);
	$page_templater->register('days45selected', $days45selected);
	$page_templater->register('days60selected', $days60selected);
	$page_templater->register('days75selected', $days75selected);
	$page_templater->register('days100selected', $days100selected);
	$page_templater->register('days365selected', $days365selected);
	$page_templater->register('daysallselected', $daysallselected);
	$page_templater->register('daysdefaultselected', $daysdefaultselected);
	$page_templater->register('emailchecked', $emailchecked);
	$page_templater->register('languagelist', $languagelist);
	$page_templater->register('maxpostsoptions', $maxpostsoptions);
	$page_templater->register('postsdefaultselected', $postsdefaultselected);
	$page_templater->register('selectvbcode', $selectvbcode);
	$page_templater->register('checkvbcode', $checkvbcode);
	$page_templater->register('stylesetlist1', $stylesetlist1);
	$page_templater->register('stylesetlist2', $stylesetlist2);
	$page_templater->register('template_hook', $template_hook);
	$page_templater->register('threaddisplaymode', $threaddisplaymode);
	$page_templater->register('timezoneoptions', $timezoneoptions);
}

// ############################### start update options ###############################
if ($_POST['do'] == 'updateoptions')
{
	require_once(DIR . '/includes/functions_misc.php');
	$vbulletin->input->clean_array_gpc('p', array(
		'newstyleset'    => TYPE_INT,
		'dst'            => TYPE_INT,
		'showvbcode'     => TYPE_INT,
		'pmpopup'        => TYPE_INT,
		'umaxposts'      => TYPE_INT,
		'prunedays'      => TYPE_INT,
		'timezoneoffset' => TYPE_NUM,
		'startofweek'    => TYPE_INT,
		'languageid'     => TYPE_INT,
		'threadedmode'   => TYPE_INT,
		'invisible'      => TYPE_INT,
		'autosubscribe'  => TYPE_INT,
		'options'        => TYPE_ARRAY_BOOL,
		'set_options'    => TYPE_ARRAY_BOOL,
		'modifyavatar'   => TYPE_NOCLEAN,
		'userfield'      => TYPE_ARRAY
	));

	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($vbulletin->userinfo);

	// reputation
	if (!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canhiderep']))
	{
		$vbulletin->GPC['options']['showreputation'] = 1;
	}

	// options bitfield
	foreach ($vbulletin->bf_misc_useroptions AS $key => $val)
	{
		if (isset($vbulletin->GPC['options']["$key"]) OR isset($vbulletin->GPC['set_options']["$key"]))
		{
			$value = $vbulletin->GPC['options']["$key"];
			$userdata->set_bitfield('options', $key, $value);
		}
	}

	// style set
	if ($vbulletin->options['allowchangestyles'] AND $vbulletin->userinfo['realstyleid'] != $vbulletin->GPC['newstyleset'])
	{
		$userdata->set('styleid', $vbulletin->GPC['newstyleset']);
	}

	// language
	$userdata->set('languageid', $vbulletin->GPC['languageid']);

	// autosubscribe
	$userdata->set('autosubscribe', $vbulletin->GPC['autosubscribe']);

	// threaded mode
	$userdata->set('threadedmode', $vbulletin->GPC['threadedmode']);

	// time zone offset
	$userdata->set('timezoneoffset', $vbulletin->GPC['timezoneoffset']);

	$userdata->set('showvbcode', $vbulletin->GPC['showvbcode']);
	$userdata->set('pmpopup', $vbulletin->GPC['pmpopup']);
	$userdata->set('maxposts', $vbulletin->GPC['umaxposts']);
	$userdata->set('daysprune', $vbulletin->GPC['prunedays']);
	$userdata->set('startofweek', $vbulletin->GPC['startofweek']);

	// custom profile fields
	$userdata->set_userfields($vbulletin->GPC['userfield']);

	// daylight savings
	$userdata->set_dst($vbulletin->GPC['dst']);

	($hook = vBulletinHook::fetch_hook('profile_updateoptions')) ? eval($hook) : false;

	$userdata->save();

	if (!empty($vbulletin->GPC['modifyavatar']))
	{
		$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editavatar';
	}
	else
	{
		$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editoptions';
	}

	// recache the global group to get the stuff from the new language
	$globalgroup = $db->query_first_slave("
		SELECT phrasegroup_global, languagecode, charset
		FROM " . TABLE_PREFIX . "language
		WHERE languageid = " . intval($userdata->fetch_field('languageid') ? $userdata->fetch_field('languageid') : $vbulletin->options['languageid'])
	);
	if ($globalgroup)
	{
		$vbphrase = array_merge($vbphrase, unserialize($globalgroup['phrasegroup_global']));

		if (vB_Template_Runtime::fetchStyleVar('charset') != $globalgroup['charset'])
		{
			// change the character set in a bunch of places - a total hack
			global $headinclude;

			$headinclude = str_replace(
				"content=\"text/html; charset=" . vB_Template_Runtime::fetchStyleVar('charset') . "\"",
				"content=\"text/html; charset=$globalgroup[charset]\"",
				$headinclude
			);

			vB_Template_Runtime::addStyleVar('charset', $globalgroup['charset']);
			$vbulletin->userinfo['lang_charset'] = $globalgroup['charset'];

			exec_headers();
		}

		vB_Template_Runtime::addStyleVar('languagecode', $globalgroup['languagecode']);
	}

	print_standard_redirect(array('redirect_updatethanks',$vbulletin->userinfo['username']), true, true, $userdata->fetch_field('languageid'));  
}

// ############################################################################
// ############################## EDIT SIGNATURE ##############################
// ############################################################################



// ########################### start update signature #########################
if ($_POST['do'] == 'updatesignature')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'wysiwyg'      => TYPE_BOOL,
		'message'      => TYPE_STR,
		'preview'      => TYPE_STR,
		'deletesigpic' => TYPE_BOOL,
		'sigpicurl'    => TYPE_STR,
	));

	if (!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
	{
		eval(standard_error(fetch_error('nosignaturepermission')));
	}

	if ($permissions['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic'])
	{
		$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);
	}

	require_once(DIR . '/includes/class_bbcode.php');
	require_once(DIR . '/includes/class_sigparser.php');
	require_once(DIR . '/includes/functions_misc.php');

	$errors = array();

	// DO WYSIWYG processing to get to BB code.
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$signature = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $permissions['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['allowhtml']);
	}
	else
	{
		$signature = $vbulletin->GPC['message'];
	}

	($hook = vBulletinHook::fetch_hook('profile_updatesignature_start')) ? eval($hook) : false;

	// handle image uploads
	if ($vbulletin->GPC['deletesigpic'])
	{
		if (preg_match('#\[sigpic\](.*)\[/sigpic\]#siU', $signature))
		{
			$errors[] = fetch_error('sigpic_in_use');
		}
		else
		{
			$userpic =& datamanager_init('Userpic_Sigpic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
			$userpic->condition = "userid = " . $vbulletin->userinfo['userid'];
			$userpic->delete();
		}
		$redirectsig = true;
	}
	else if (($vbulletin->GPC['sigpicurl'] != '' AND $vbulletin->GPC['sigpicurl'] != 'http://www.') OR $vbulletin->GPC['upload']['size'] > 0)
	{
		require_once(DIR . '/includes/class_upload.php');
		require_once(DIR . '/includes/class_image.php');

		$upload = new vB_Upload_Userpic($vbulletin);

		$upload->data =& datamanager_init('Userpic_Sigpic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$upload->image =& vB_Image::fetch_library($vbulletin);
		$upload->maxwidth = $vbulletin->userinfo['permissions']['sigpicmaxwidth'];
		$upload->maxheight = $vbulletin->userinfo['permissions']['sigpicmaxheight'];
		$upload->maxuploadsize = $vbulletin->userinfo['permissions']['sigpicmaxsize'];
		$upload->allowanimation = ($vbulletin->userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cananimatesigpic']) ? true : false;

		if (!$upload->process_upload($vbulletin->GPC['sigpicurl']))
		{
			eval(standard_error($upload->fetch_error()));
		}
		$redirectsig = true;
		$vbulletin->userinfo['sigpicrevision']++;
	}

	$userinfo_sigpic = fetch_userinfo($vbulletin->userinfo['userid'], FETCH_USERINFO_SIGPIC);

	// Censored Words
	$censor_signature = fetch_censored_text($signature);

	if ($signature != $censor_signature)
	{
		$signature = $censor_signature;
		$errors[] = fetch_error('censoredword');
		unset($censor_signature);
	}

	// Max number of images in the sig if imgs are allowed.
	if ($vbulletin->userinfo['permissions']['sigmaximages'] OR $vbulletin->userinfo['permissions']['sigmaxvideos'])
	{
		// Parsing the signature into BB code.
		require_once(DIR . '/includes/class_bbcode_alt.php');
		require_once(DIR . '/includes/functions_video.php');

		$bbcode_parser = new vB_BbCodeParser_ImgCheck($vbulletin, fetch_tag_list());
		$bbcode_parser->set_parse_userinfo($userinfo_sigpic, $vbulletin->userinfo['permissions']);
		$signature = parse_video_bbcode($signature);
		$parsedsig = $bbcode_parser->parse($signature, 'signature');

		// Count the images
		if ($vbulletin->userinfo['permissions']['sigmaximages'])
		{
			$imagecount = fetch_character_count($parsedsig, '<img');
			if ($imagecount > $vbulletin->userinfo['permissions']['sigmaximages'])
			{
				$vbulletin->GPC['preview'] = true;
				$errors[] = fetch_error('toomanyimages', $imagecount, $vbulletin->userinfo['permissions']['sigmaximages']);
			}
		}
		if ($vbulletin->userinfo['permissions']['sigmaxvideos'])
		{
			$videocount = fetch_character_count($parsedsig, '<video />');
			if ($videocount > $vbulletin->userinfo['permissions']['sigmaxvideos'])
			{
				$vbulletin->GPC['preview'] = true;
				$errors[] = fetch_error('toomanyvideos', $videocount, $vbulletin->userinfo['permissions']['sigmaxvideos']);
			}
		}
	}

	// Count the raw characters in the signature
	if ($vbulletin->userinfo['permissions']['sigmaxrawchars'] AND vbstrlen($signature) > $vbulletin->userinfo['permissions']['sigmaxrawchars'])
	{
		$vbulletin->GPC['preview'] = true;
		$errors[] = fetch_error('sigtoolong_includingbbcode', $vbulletin->userinfo['permissions']['sigmaxrawchars']);
	}
	// Count the characters after stripping in the signature
	else if ($vbulletin->userinfo['permissions']['sigmaxchars'] AND (vbstrlen(strip_bbcode($signature, false, false, false)) > $vbulletin->userinfo['permissions']['sigmaxchars']))
	{
		$vbulletin->GPC['preview'] = true;
		$errors[] = fetch_error('sigtoolong_excludingbbcode', $vbulletin->userinfo['permissions']['sigmaxchars']);
	}

	if ($vbulletin->userinfo['permissions']['sigmaxlines'] > 0)
	{
		require_once(DIR . '/includes/class_sigparser_char.php');
		$char_counter = new vB_SignatureParser_CharCount($vbulletin, fetch_tag_list(), $vbulletin->userinfo['permissions'], $vbulletin->userinfo['userid']);
		$line_count_text = $char_counter->parse(trim($signature));

		if ($vbulletin->options['softlinebreakchars'] > 0)
		{
			// implicitly wrap after X characters without a break
			//trim it to get rid of the trailing whitechars that are inserted by the replace
			$line_count_text = trim(preg_replace('#([^\r\n]{' . $vbulletin->options['softlinebreakchars'] . '})#', "\\1\n", $line_count_text));
		}

		// + 1, since 0 linebreaks still means 1 line
		$line_count = substr_count($line_count_text, "\n") + 1;

		if ($line_count > $vbulletin->userinfo['permissions']['sigmaxlines'])
		{
			$vbulletin->GPC['preview'] = true;
			$errors[] = fetch_error('sigtoomanylines', $vbulletin->userinfo['permissions']['sigmaxlines']);
		}

	}

	if ($vbulletin->userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['canbbcode'])
	{
		// Get the files we need
		require_once(DIR . '/includes/functions_newpost.php');

		// add # to color tags using hex if it's not there
		$signature = preg_replace('#\[color=(&quot;|"|\'|)([a-f0-9]{6})\\1]#i', '[color=\1#\2\1]', $signature);

		// Turn the text into bb code.
		if ($vbulletin->userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['canbbcodelink'])
		{
			$signature = convert_url_to_bbcode($signature);
		}

		// Create the parser with the users sig permissions
		$sig_parser = new vB_SignatureParser($vbulletin, fetch_tag_list(), $vbulletin->userinfo['permissions'], $vbulletin->userinfo['userid']);

		// Parse the signature
		$previewmessage = $sig_parser->parse($signature);

		if ($error_num = count($sig_parser->errors))
		{
			foreach ($sig_parser->errors AS $tag => $error_phrase)
			{
				$errors[] = fetch_error($error_phrase, $tag);
			}
		}

		unset($sig_parser, $tag_list, $sig_tag_token_array, $results);
	}

	// If they are previewing the signature or there were usergroup rules broken and there are $errors[]
	if (!empty($errors) OR $vbulletin->GPC['preview'] != '')
	{
		$errorlist = '';
		if (!empty($errors))
		{
			$show['errors'] = true;
			$templater = vB_Template::create('newpost_errormessage');
			$templater->register('errors', $errors);
			$errorlist .= $templater->render();
		}

		$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
		$bbcode_parser->set_parse_userinfo($userinfo_sigpic, $vbulletin->userinfo['permissions']);
		$previewmessage = $bbcode_parser->parse($signature, 'signature');

		// save a conditional by just overwriting the phrase
		$vbphrase['submit_message'] =& $vbphrase['save_signature'];
		$templater = vB_Template::create('newpost_preview');
			$templater->register('errorlist', $errorlist);
			$templater->register('newpost', $newpost);
			$templater->register('post', $post);
			$templater->register('previewmessage', $previewmessage);
		$preview = $templater->render();
		$_REQUEST['do'] = 'editsignature';

		$preview_error_signature = $signature;
	}
	else
	{
		// init user data manager
		$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
		$userdata->set_existing($vbulletin->userinfo);

		$userdata->set('signature', $signature);

		($hook = vBulletinHook::fetch_hook('profile_updatesignature_complete')) ? eval($hook) : false;

		$userdata->save();

		clear_autosave_text('vBForum_Signature', 0, 0, $vbulletin->userinfo['userid']);

		if ($redirectsig)
		{
			$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editsignature&amp;url=' . $vbulletin->url . '#sigpic';
		}
		else
		{
			$vbulletin->url = 'usercp.php' . $vbulletin->session->vars['sessionurl_q'];
		}
		print_standard_redirect(array('redirect_updatethanks',$vbulletin->userinfo['username']));  
	}
}

// ############################### start update profile pic###########################
if ($_POST['do'] == 'updatesigpic')
{
	if (!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
	{
		eval(standard_error(fetch_error('nosignaturepermission')));
	}

	if (!($permissions['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic']))
	{
		print_no_permission();
	}

	#if (!$vbulletin->options['profilepicenabled']) // add sigpicenabled?
	#{
	#	print_no_permission();
	#}

	$vbulletin->input->clean_array_gpc('p', array(
		'deletesigpic' => TYPE_BOOL,
		'sigpicurl'    => TYPE_STR,
	));

	($hook = vBulletinHook::fetch_hook('profile_updatesigpic_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['deletesigpic'])
	{
		$userpic =& datamanager_init('Userpic_Sigpic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$userpic->condition = "userid = " . $vbulletin->userinfo['userid'];
		$userpic->delete();
	}
	else
	{
		$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);

		require_once(DIR . '/includes/class_upload.php');
		require_once(DIR . '/includes/class_image.php');

		$upload = new vB_Upload_Userpic($vbulletin);

		$upload->data =& datamanager_init('Userpic_Sigpic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$upload->image =& vB_Image::fetch_library($vbulletin);
		$upload->maxwidth = $vbulletin->userinfo['permissions']['sigpicmaxwidth'];
		$upload->maxheight = $vbulletin->userinfo['permissions']['sigpicmaxheight'];
		$upload->maxuploadsize = $vbulletin->userinfo['permissions']['sigpicmaxsize'];
		$upload->allowanimation = ($vbulletin->userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cananimatesigpic']) ? true : false;

		if (!$upload->process_upload($vbulletin->GPC['sigpicurl']))
		{
			eval(standard_error($upload->fetch_error()));
		}
	}

	($hook = vBulletinHook::fetch_hook('profile_updatesigpic_complete')) ? eval($hook) : false;

	$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editsignature#sigpic';
	print_standard_redirect(array('redirect_updatethanks',$vbulletin->userinfo['username']));  
}

// ############################ start edit signature ##########################
if ($_REQUEST['do'] == 'editsignature')
{
	require_once(DIR . '/includes/functions_newpost.php');

	if (!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
	{
		eval(standard_error(fetch_error('nosignaturepermission')));
	}

	($hook = vBulletinHook::fetch_hook('profile_editsignature_start')) ? eval($hook) : false;

	// Build the permissions to display
	require_once(DIR . '/includes/class_bbcode.php');
	require_once(DIR . '/includes/class_sigparser.php');

	// Create the parser with the users sig permissions
	$sig_parser = new vB_SignatureParser($vbulletin, fetch_tag_list(), $vbulletin->userinfo['permissions'], $vbulletin->userinfo['userid']);

	// Build $show variables for each signature bitfield permission
	foreach ($vbulletin->bf_ugp_signaturepermissions AS $bit_name => $bit_value)
	{
		if ($bbcode = preg_match('#canbbcode(\w+)#i', $bit_name, $matches) AND $matches[1] AND $matches[1] != 'quote')
		{
			$term = $matches[1] == 'link' ? 'URL' : strtoupper($matches[1]);
			$show["$bit_name"] = ($permissions['signaturepermissions'] & $bit_value AND $vbulletin->options['allowedbbcodes'] & @constant('ALLOW_BBCODE_' . $term))  ? true : false;
		}
		else
		{
			$show["$bit_name"] = ($permissions['signaturepermissions'] & $bit_value ? true : false);
		}
	}

	// Build variables for the remaining signature permissions
	$sigperms_display = array(
		'sigmaxchars'     => vb_number_format($permissions['maxchars']),
		'sigmaxlines'     => vb_number_format($permissions['maxlines']),
		'sigpicmaxwidth'  => vb_number_format($permissions['sigpicmaxwidth']),
		'sigpicmaxheight' => vb_number_format($permissions['sigpicmaxheight']),
		'sigpicmaxsize'   => vb_number_format($permissions['sigpicmaxsize'], 1, true)
	);

	if ($preview_error_signature)
	{
		$signature = $preview_error_signature;
	}
	else
	{
		$signature = $vbulletin->userinfo['signature'];
	}

	// Free the memory, unless we need it below.
	if (!$signature)
	{
		unset($sig_parser);
	}

	if ($signature)
	{
		if (!$previewmessage)
		{
			require_once(DIR . '/includes/class_bbcode.php');
			$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
			$bbcode_parser->set_parse_userinfo(fetch_userinfo($vbulletin->userinfo['userid'], FETCH_USERINFO_SIGPIC), $vbulletin->userinfo['permissions']);
			$previewmessage = $bbcode_parser->parse($signature, 'signature');
		}

		// save a conditional by just overwriting the phrase
		$vbphrase['submit_message'] =& $vbphrase['save_signature'];
		$templater = vB_Template::create('newpost_preview');
			$templater->register('errorlist', $errorlist);
			$templater->register('newpost', $newpost);
			$templater->register('post', $post);
			$templater->register('previewmessage', $previewmessage);
		$preview = $templater->render();
	}

	require_once(DIR . '/includes/functions_editor.php');

	$editorid = construct_edit_toolbar(
		htmlspecialchars_uni($signature),
		0,
		'signature',
		$vbulletin->userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['allowsmilies'],
		true,
		false,
		'fe',
		'',
		array(),
		'content',
		'vBForum_Signature'
	);

	$show['canbbcode'] = ($vbulletin->userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['canbbcode']) ? true : false;

	// ############### DISPLAY SIG IMAGE CONTROLS ###############
	require_once(DIR . '/includes/functions_file.php');
	$inimaxattach = fetch_max_upload_size();

	if ($permissions['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic'])
	{
		$show['cansigpic'] = true;
		$show['sigpic_url'] = (ini_get('allow_url_fopen') != 0 OR function_exists('curl_init'));

		$maxnote = '';
		if ($permissions['sigpicmaxsize'] AND ($permissions['sigpicmaxwidth'] OR $permissions['sigpicmaxheight']))
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x_y_or_z'], $sigperms_display['sigpicmaxwidth'], $sigperms_display['sigpicmaxheight'], $sigperms_display['sigpicmaxsize']);
		}
		else if ($permissions['sigpicmaxsize'])
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x'], $sigperms_display['sigpicmaxsize']);
		}
		else if ($permissions['sigpicmaxwidth'] OR $permissions['sigpicmaxheight'])
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x_y_pixels'], $sigperms_display['sigpicmaxwidth'], $sigperms_display['sigpicmaxheight']);
		}
		$show['maxnote'] = (!empty($maxnote)) ? true : false;

		// Get the current sig image info.
		if ($sig_image = $db->query_first("SELECT dateline, filename, filedata FROM " . TABLE_PREFIX . "sigpic WHERE userid = " . $vbulletin->userinfo['userid']))
		{
			if ($sig_image['filedata'] != '')
			{
				// sigpic stored in the DB
				$sigpicurl = 'image.php?' . $vbulletin->session->vars['sessionurl'] . 'type=sigpic&amp;userid=' . $vbulletin->userinfo['userid'] . "&amp;dateline=$sig_image[dateline]";
			}
			else
			{
				// sigpic stored in the FS
				$sigpicurl = $vbulletin->options['sigpicurl'] . '/sigpic' . $vbulletin->userinfo['userid'] . '_' . $vbulletin->userinfo['sigpicrevision'] . '.gif';
			}
		}
		else // No sigpic yet
		{
			$sigpicurl = false;
		}
	}
	else
	{
		$show['cansigpic'] = false;
	}

	construct_usercp_nav('signature');

	$navbits[''] = $vbphrase['edit_signature'];
	$url =& $vbulletin->url;

	$page_templater = vB_Template::create('modifysignature');
	$page_templater->register('editorid', $editorid);
	$page_templater->register('inimaxattach', $inimaxattach);
	$page_templater->register('maxnote', $maxnote);
	$page_templater->register('messagearea', $messagearea);
	$page_templater->register('preview', $preview);
	$page_templater->register('sigperms', $sigperms);
	$page_templater->register('sigpicurl', $sigpicurl);
	$page_templater->register('url', $url);
}

// ############################################################################
// ############################### EDIT AVATAR ################################
// ############################################################################
if ($_REQUEST['do'] == 'editavatar')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT,
		'categoryid' => TYPE_UINT
	));

	if (!$vbulletin->options['avatarenabled'])
	{
		eval(standard_error(fetch_error('avatardisabled')));
	}

	($hook = vBulletinHook::fetch_hook('profile_editavatar_start')) ? eval($hook) : false;

	$categorycache = array();
	$bbavatar = array();
	$donefirstcategory = 0;

	// variables that will become templates
	$avatarlist = '';
	$nouseavatarchecked = '';
	$categorybits = '';
	$predefined_section = '';
	$custom_section = '';

	// initialise the bg class
	$bgclass = 'alt1';

	// ############### DISPLAY USER'S AVATAR ###############
	if ($vbulletin->userinfo['avatarid'])
	{
	// using a predefined avatar

		$avatar = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "avatar WHERE avatarid = " . $vbulletin->userinfo['avatarid']);
		$avatarid =& $avatar['avatarid'];
		$avatarchecked = ($avatarid == $vbulletin->userinfo['avatarid']) ? 'checked="checked"' : '';
		$templater = vB_Template::create('modifyavatarbit');
			$templater->register('avatar', $avatar);
			$templater->register('avatarchecked', $avatarchecked);
			$templater->register('avatarid', $avatarid);
		$currentavatar = $templater->render();
		// store avatar info in $bbavatar for later use
		$bbavatar = $avatar;
		$avatarchecked = '';
	}
	else
	{
	// not using a predefined avatar, check for custom

		if ($avatar = $db->query_first("SELECT dateline, width, height FROM " . TABLE_PREFIX . "customavatar WHERE userid=" . $vbulletin->userinfo['userid']))
		{
		// using a custom avatar
			if ($vbulletin->options['usefileavatar'])
			{
				$vbulletin->userinfo['avatarurl'] = $vbulletin->options['avatarurl'] . '/avatar' . $vbulletin->userinfo['userid'] . '_' . $vbulletin->userinfo['avatarrevision'] . '.gif';
			}
			else
			{
				$vbulletin->userinfo['avatarurl'] = 'image.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $vbulletin->userinfo['userid'] . "&amp;dateline=$avatar[dateline]";
			}
			if ($avatar['width'] AND $avatar['height'])
			{
				$vbulletin->userinfo['avatarurl'] .= "\" width=\"$avatar[width]\" height=\"$avatar[height]";
			}
			$currentavatar = vB_Template::create('modifyavatarbit_custom')->render();
			$avatarchecked[0] = 'checked="checked"';
		}
		else
		{
		// no avatar specified
			$nouseavatarchecked = 'checked="checked"';
			$avatarchecked[0] = '';
			$currentavatar = vB_Template::create('modifyavatarbit_noavatar')->render();
		}
	}
	// get rid of any lingering $avatar variables
	unset($avatar);

	$categorycache =& fetch_avatar_categories($vbulletin->userinfo);
	foreach ($categorycache AS $category)
	{
		if (!$donefirstcategory OR $category['imagecategoryid'] == $vbulletin->GPC['categoryid'])
		{
			$displaycategory = $category;
			$donefirstcategory = 1;
		}
	}

	// get the id of the avatar category we want to display
	if ($vbulletin->GPC['categoryid'] == 0)
	{
		if ($vbulletin->userinfo['avatarid'] != 0 AND !empty($categorycache["{$bbavatar['imagecategoryid']}"]))
		{
			$displaycategory = $bbavatar;
		}
		$vbulletin->GPC['categoryid'] = $displaycategory['imagecategoryid'];
	}

	// make the category <select> list
	$optionselected["{$vbulletin->GPC['categoryid']}"] = 'selected="selected"';
	if (count($categorycache) > 1)
	{
		$show['categories'] = true;
		foreach ($categorycache AS $category)
		{
			$thiscategoryid = $category['imagecategoryid'];
			$selected = iif($thiscategoryid == $vbulletin->GPC['categoryid'], ' selected="selected"', '');
			$templater = vB_Template::create('modifyavatar_category');
				$templater->register('category', $category);
				$templater->register('selected', $selected);
				$templater->register('thiscategoryid', $thiscategoryid);
			$categorybits .= $templater->render();
		}
	}
	else
	{
		$show['categories'] = false;
		$categorybits = '';
	}

	// ############### GET TOTAL NUMBER OF AVATARS IN THIS CATEGORY ###############
	// get the total number of avatars in this category
	$totalavatars = $categorycache["{$vbulletin->GPC['categoryid']}"]['avatars'];

	// get perpage parameters for table display
	$perpage = $vbulletin->options['numavatarsperpage'];
	sanitize_pageresults($totalavatars, $vbulletin->GPC['pagenumber'], $perpage, 100, 25);
	// get parameters for query limits
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;

	// make variables for 'displaying avatars x to y of z' text
	$first = $startat + 1;
	$last = $startat + $perpage;
	if ($last > $totalavatars)
	{
		$last = $totalavatars;
	}

	// ############### DISPLAY PREDEFINED AVATARS ###############
	if ($totalavatars)
	{
		$pagenav = construct_page_nav($vbulletin->GPC['pagenumber'], $perpage, $totalavatars, 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editavatar&amp;categoryid=' . $vbulletin->GPC['categoryid']);

		$avatars = $db->query_read_slave("
			SELECT avatar.*, imagecategory.title AS category
			FROM " . TABLE_PREFIX . "avatar AS avatar LEFT JOIN " . TABLE_PREFIX . "imagecategory AS imagecategory USING(imagecategoryid)
			WHERE minimumposts <= " . $vbulletin->userinfo['posts'] . "
			AND avatar.imagecategoryid=" . $vbulletin->GPC['categoryid'] . "
			AND avatarid <> " . $vbulletin->userinfo['avatarid'] . "
			ORDER BY avatar.displayorder
			LIMIT $startat,$perpage
		");
		$avatarsonthispage = $db->num_rows($avatars);

		while ($avatar = $db->fetch_array($avatars))
		{
			$categoryname = $avatar['category'];
			$avatarid =& $avatar['avatarid'];

			($hook = vBulletinHook::fetch_hook('profile_editavatar_bit')) ? eval($hook) : false;

			$templater = vB_Template::create('modifyavatarbit');
				$templater->register('avatar', $avatar);
				//$templater->register('avatarchecked', $avatarchecked);
				$templater->register('avatarid', $avatarid);
			$avatarlist .= $templater->render();
		}

		$show['forumavatars'] = true;
	}
	else
	{
		$show['forumavatars'] = false;
	}
	// end code for predefined avatars

	// ############### DISPLAY CUSTOM AVATAR CONTROLS ###############
	require_once(DIR . '/includes/functions_file.php');
	$inimaxattach = fetch_max_upload_size();

	if ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar'])
	{
		$show['customavatar'] = true;
		$show['customavatar_url'] = (ini_get('allow_url_fopen') != 0 OR function_exists('curl_init'));

		$permissions['avatarmaxsize'] = vb_number_format($permissions['avatarmaxsize'], 1, true);

		$maxnote = '';
		if ($permissions['avatarmaxsize'] AND ($permissions['avatarmaxwidth'] OR $permissions['avatarmaxheight']))
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x_y_or_z'], $permissions['avatarmaxwidth'], $permissions['avatarmaxheight'], $permissions['avatarmaxsize']);
		}
		else if ($permissions['avatarmaxsize'])
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x'], $permissions['avatarmaxsize']);
		}
		else if ($permissions['avatarmaxwidth'] OR $permissions['avatarmaxheight'])
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x_y_pixels'], $permissions['avatarmaxwidth'], $permissions['avatarmaxheight']);
		}
		$show['maxnote'] = (!empty($maxnote)) ? true : false;
	}
	else
	{
		$show['customavatar'] = false;
	}

	// draw cp nav bar
	construct_usercp_nav('avatar');

	$navbits[''] = $vbphrase['edit_avatar'];
	$includecss['editavatar'] = 'editavatar.css';

	$page_templater = vB_Template::create('modifyavatar');
	$page_templater->register('avatarchecked', $avatarchecked);
	$page_templater->register('avatarlist', $avatarlist);
	$page_templater->register('categorybits', $categorybits);
	$page_templater->register('categoryname', $categoryname);
	$page_templater->register('cols', $cols);
	$page_templater->register('currentavatar', $currentavatar);
	$page_templater->register('inimaxattach', $inimaxattach);
	$page_templater->register('maxnote', $maxnote);
	$page_templater->register('nouseavatarchecked', $nouseavatarchecked);
	$page_templater->register('pagenav', $pagenav);
}

// ############################################################################
// ########################## EDIT PROFILE PICTURE ############################
// ############################################################################
if ($_REQUEST['do'] == 'editprofilepic')
{
	($hook = vBulletinHook::fetch_hook('profile_editprofilepic')) ? eval($hook) : false;

	if ($vbulletin->options['profilepicenabled'] AND ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canprofilepic']))
	{
		if ($profilepic = $db->query_first("
			SELECT userid, dateline, height, width
			FROM " . TABLE_PREFIX . "customprofilepic
			WHERE userid = " . $vbulletin->userinfo['userid']
		))
		{
			if ($vbulletin->options['usefileavatar'])
			{
				$vbulletin->userinfo['profileurl'] = $vbulletin->options['profilepicurl'] . '/profilepic' . $vbulletin->userinfo['userid'] . '_' . $vbulletin->userinfo['profilepicrevision'] . '.gif';
			}
			else
			{
				$vbulletin->userinfo['profileurl'] = 'image.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $vbulletin->userinfo['userid'] . "&amp;dateline=$profilepic[dateline]&amp;type=profile";
			}

			if ($profilepic['width'] AND $profilepic['height'])
			{
				$vbulletin->userinfo['profileurl'] .= "\" width=\"$profilepic[width]\" height=\"$profilepic[height]";
			}
			$show['profilepic'] = true;
		}

		$permissions['profilepicmaxsize'] = vb_number_format($permissions['profilepicmaxsize'], 1, true);

		$maxnote = '';
		if ($permissions['profilepicmaxsize'] AND ($permissions['profilepicmaxwidth'] OR $permissions['profilepicmaxheight']))
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x_y_or_z'], $permissions['profilepicmaxwidth'], $permissions['profilepicmaxheight'], $permissions['profilepicmaxsize']);
		}
		else if ($permissions['profilepicmaxsize'])
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x'], $permissions['profilepicmaxsize']);
		}
		else if ($permissions['profilepicmaxwidth'] OR $permissions['profilepicmaxheight'])
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x_y_pixels'], $permissions['profilepicmaxwidth'], $permissions['profilepicmaxheight']);
		}
		$show['maxnote'] = (!empty($maxnote)) ? true : false;
		$show['profilepic_url'] = (ini_get('allow_url_fopen') != 0 OR function_exists('curl_init'));

		// draw cp nav bar
		construct_usercp_nav('profilepic');

		$navbits[''] = $vbphrase['edit_profile_picture'];

		$page_templater = vB_Template::create('modifyprofilepic');
		$page_templater->register('inimaxattach', $inimaxattach);
		$page_templater->register('maxnote', $maxnote);
	}
	else
	{
		print_no_permission();
	}
}

// ############################### start update avatar ###############################
if ($_POST['do'] == 'updateavatar')
{
	if (!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canmodifyprofile']))
	{
		print_no_permission();
	}

	if (!$vbulletin->options['avatarenabled'])
	{
		eval(standard_error(fetch_error('avatardisabled')));
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'avatarid'  => TYPE_INT,
		'avatarurl' => TYPE_STR,
	));

	($hook = vBulletinHook::fetch_hook('profile_updateavatar_start')) ? eval($hook) : false;

	$useavatar = iif($vbulletin->GPC['avatarid'] == -1, 0, 1);

	if ($useavatar)
	{
		if ($vbulletin->GPC['avatarid'] == 0 AND ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar']))
		{
			$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);

			// begin custom avatar code
			require_once(DIR . '/includes/class_upload.php');
			require_once(DIR . '/includes/class_image.php');

			$upload = new vB_Upload_Userpic($vbulletin);

			$upload->data =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_STANDARD, 'userpic');
			$upload->image =& vB_Image::fetch_library($vbulletin);
			$upload->maxwidth = $vbulletin->userinfo['permissions']['avatarmaxwidth'];
			$upload->maxheight = $vbulletin->userinfo['permissions']['avatarmaxheight'];
			$upload->maxuploadsize = $vbulletin->userinfo['permissions']['avatarmaxsize'];
			$upload->allowanimation = ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cananimateavatar']) ? true : false;

			if (!$upload->process_upload($vbulletin->GPC['avatarurl']))
			{
				eval(standard_error($upload->fetch_error()));
			}
		}
		else
		{
			// start predefined avatar code
			$vbulletin->GPC['avatarid'] = verify_id('avatar', $vbulletin->GPC['avatarid']);
			$avatarinfo = $db->query_first_slave("
				SELECT avatarid, minimumposts, imagecategoryid
				FROM " . TABLE_PREFIX . "avatar
				WHERE avatarid = " . $vbulletin->GPC['avatarid']
			);

			if ($avatarinfo['minimumposts'] > $vbulletin->userinfo['posts'])
			{
				// not enough posts error
				eval(standard_error(fetch_error('avatarmoreposts')));
			}

			$membergroups = fetch_membergroupids_array($vbulletin->userinfo);

			$avperms = $db->query_read_slave("
				SELECT usergroupid
				FROM " . TABLE_PREFIX . "imagecategorypermission
				WHERE imagecategoryid = $avatarinfo[imagecategoryid]
			");

			$noperms = array();
			while ($avperm = $db->fetch_array($avperms))
			{
				$noperms[] = $avperm['usergroupid'];
			}
			if (!count(array_diff($membergroups, $noperms)))
			{
				eval(standard_error(fetch_error('invalid_avatar_specified')));
			}

			$userpic =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_STANDARD, 'userpic');
			$userpic->condition = 'userid = ' . $vbulletin->userinfo['userid'];
			$userpic->delete();

			// end predefined avatar code
		}
	}
	else
	{
		// not using an avatar

		$vbulletin->GPC['avatarid'] = 0;
		$userpic =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$userpic->condition = 'userid = ' . $vbulletin->userinfo['userid'];
		$userpic->delete();
	}

	// init user data manager
	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($vbulletin->userinfo);

	$userdata->set('avatarid', $vbulletin->GPC['avatarid']);

	($hook = vBulletinHook::fetch_hook('profile_updateavatar_complete')) ? eval($hook) : false;

	$userdata->save();

	$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editavatar';
	print_standard_redirect(array('redirect_updatethanks',$vbulletin->userinfo['username']));  

}

// ############################### start update profile pic###########################
if ($_POST['do'] == 'updateprofilepic')
{

	if (!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canprofilepic']))
	{
		print_no_permission();
	}

	if (!$vbulletin->options['profilepicenabled'])
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'deleteprofilepic' => TYPE_BOOL,
		'avatarurl'        => TYPE_STR,
	));

	($hook = vBulletinHook::fetch_hook('profile_updateprofilepic_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['deleteprofilepic'])
	{
		$userpic =& datamanager_init('Userpic_Profilepic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$userpic->condition = "userid = " . $vbulletin->userinfo['userid'];
		$userpic->delete();
	}
	else
	{
		$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);

		require_once(DIR . '/includes/class_upload.php');
		require_once(DIR . '/includes/class_image.php');

		$upload = new vB_Upload_Userpic($vbulletin);

		$upload->data =& datamanager_init('Userpic_Profilepic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
		$upload->image =& vB_Image::fetch_library($vbulletin);
		$upload->maxwidth = $vbulletin->userinfo['permissions']['profilepicmaxwidth'];
		$upload->maxheight = $vbulletin->userinfo['permissions']['profilepicmaxheight'];
		$upload->maxuploadsize = $vbulletin->userinfo['permissions']['profilepicmaxsize'];
		$upload->allowanimation = ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cananimateprofilepic']) ? true : false;

		if (!$upload->process_upload($vbulletin->GPC['avatarurl']))
		{
			eval(standard_error($upload->fetch_error()));
		}
	}

	($hook = vBulletinHook::fetch_hook('profile_updateprofilepic_complete')) ? eval($hook) : false;

	$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editprofilepic';
	print_standard_redirect(array('redirect_updatethanks',$vbulletin->userinfo['username']));  
}

// ############################### start choose displayed usergroup ###############################

if ($_POST['do'] == 'updatedisplaygroup')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usergroupid' => TYPE_UINT
	));

	$membergroups = fetch_membergroupids_array($vbulletin->userinfo);

	if ($vbulletin->GPC['usergroupid'] == 0)
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['usergroup'], $vbulletin->options['contactuslink'])));
	}

	if (!in_array($vbulletin->GPC['usergroupid'], $membergroups))
	{
		eval(standard_error(fetch_error('notmemberofdisplaygroup')));
	}
	else
	{
		$display_usergroup = $vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"];

		if ($vbulletin->GPC['usergroupid'] == $vbulletin->userinfo['usergroupid'] OR $display_usergroup['canoverride'])
		{
			$vbulletin->userinfo['displaygroupid'] = $vbulletin->GPC['usergroupid'];

			// init user data manager
			$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
			$userdata->set_existing($vbulletin->userinfo);

			$userdata->set('displaygroupid', $vbulletin->GPC['usergroupid']);

			if (!$vbulletin->userinfo['customtitle'])
			{
				$userdata->set_usertitle(
					$vbulletin->userinfo['customtitle'] ? $vbulletin->userinfo['usertitle'] : '',
					false,
					$display_usergroup,
					($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusecustomtitle']) ? true : false,
					($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cancontrolpanel']) ? true : false
				);
			}

			($hook = vBulletinHook::fetch_hook('profile_updatedisplaygroup')) ? eval($hook) : false;

			$userdata->save();

			print_standard_redirect(array('usergroup_displaygroupupdated',$display_usergroup['title']));  
		}
		else
		{
			eval(standard_error(fetch_error('usergroup_invaliddisplaygroup')));
		}
	}
}

// *************************************************************************

if ($_POST['do'] == 'leavegroup')
{
	$vbulletin->input->clean_gpc('p', 'usergroupid', TYPE_UINT);

	$membergroups = fetch_membergroupids_array($vbulletin->userinfo);

	if (empty($membergroups))
	{ // check they have membergroups
		eval(standard_error(fetch_error('usergroup_cantleave_notmember')));
	}
	else if (!in_array($vbulletin->GPC['usergroupid'], $membergroups))
	{ // check they are a member before leaving
		eval(standard_error(fetch_error('usergroup_cantleave_notmember')));
	}
	else
	{
		if ($vbulletin->GPC['usergroupid'] == $vbulletin->userinfo['usergroupid'])
		{
			// trying to leave primary usergroup
			eval(standard_error(fetch_error('usergroup_cantleave_primary')));
		}
		else if ($check = $db->query_first_slave("SELECT usergroupleaderid FROM " . TABLE_PREFIX . "usergroupleader WHERE usergroupid = " . $vbulletin->GPC['usergroupid'] . " AND userid=" . $vbulletin->userinfo['userid']))
		{
			// trying to leave a group of which user is a leader
			eval(standard_error(fetch_error('usergroup_cantleave_groupleader')));
		}
		else
		{
			$newmembergroups = array();
			foreach ($membergroups AS $groupid)
			{
				if ($groupid != $vbulletin->userinfo['usergroupid'] AND $groupid != $vbulletin->GPC['usergroupid'])
				{
					$newmembergroups[] = $groupid;
				}
			}

			// init user data manager
			$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
			$userdata->set_existing($vbulletin->userinfo);
			$userdata->set('membergroupids', $newmembergroups);
			if ($vbulletin->userinfo['displaygroupid'] == $vbulletin->GPC['usergroupid'])
			{
				$userdata->set('displaygroupid', 0);
				$userdata->set_usertitle(
					$vbulletin->userinfo['customtitle'] ? $vbulletin->userinfo['usertitle'] : '',
					false,
					$vbulletin->usergroupcache["{$vbulletin->userinfo['usergroupid']}"],
					($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusecustomtitle']) ? true : false,
					($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cancontrolpanel']) ? true : false
				);
			}

			($hook = vBulletinHook::fetch_hook('profile_leavegroup')) ? eval($hook) : false;

			$userdata->save();

			print_standard_redirect('usergroup_nolongermember');  
		}
	}

}

// *************************************************************************

if ($_POST['do'] == 'insertjoinrequest')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usergroupid' => TYPE_UINT,
		'reason'      => TYPE_NOHTML,
	));

	($hook = vBulletinHook::fetch_hook('profile_insertjoinrequest')) ? eval($hook) : false;

	$vbulletin->url = "profile.php?do=editusergroups";

	if ($request = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "usergrouprequest WHERE userid=" . $vbulletin->userinfo['userid'] . " AND usergroupid=" . $vbulletin->GPC['usergroupid']))
	{
		// request already exists, just say okay...
		print_standard_redirect('usergroup_requested');  
	}
	else

	{
		// insert the request
		/*insert query*/
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "usergrouprequest
				(userid,usergroupid,reason,dateline)
			VALUES
				(" . $vbulletin->userinfo['userid'] . ", " . $vbulletin->GPC['usergroupid'] . ", '" . $db->escape_string($vbulletin->GPC['reason']) . "', " . TIMENOW . ")
		");
		print_standard_redirect('usergroup_requested');  
	}

}

// *************************************************************************

if ($_POST['do'] == 'joingroup')
{
	$usergroupid = $vbulletin->input->clean_gpc('p', 'usergroupid', TYPE_UINT);

	$membergroups = fetch_membergroupids_array($vbulletin->userinfo);

	if (in_array($usergroupid, $membergroups))
	{
		eval(standard_error(fetch_error('usergroup_already_member')));
	}
	else
	{
		// check to see that usergroup exists and is public
		if ($vbulletin->usergroupcache["$usergroupid"]['ispublicgroup'])
		{
			$usergroup = $vbulletin->usergroupcache["$usergroupid"];

			// check to see if group is moderated
			$leaders = $db->query_read_slave("
				SELECT ugl.userid, username
				FROM " . TABLE_PREFIX . "usergroupleader AS ugl
				INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
				WHERE ugl.usergroupid = $usergroupid
			");

			if ($db->num_rows($leaders))
			{ // group is moderated: show join request page
				$clc = 0;
				$groupleaders = array();
				while ($leader = $db->fetch_array($leaders))
				{
					$clc++;
					$leader['comma'] = $vbphrase['comma_space'];
					$groupleaders[$clc] = $leader;
				}

				// Last element
				if ($clc) 
				{
					$groupleaders[$clc]['comma'] = '';
				}

				$navbits['profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editusergroups'] = $vbphrase['group_memberships'];
				$navbits[''] = $vbphrase['join_request'];

				($hook = vBulletinHook::fetch_hook('profile_joingroup_moderated')) ? eval($hook) : false;

				// draw cp nav bar
				construct_usercp_nav('usergroups');
				$includecss['joinrequests'] = 'joinrequests.css';

				$page_templater = vB_Template::create('modifyusergroups_requesttojoin');
				$page_templater->register('groupleaders', $groupleaders);
				$page_templater->register('usergroup', $usergroup);
				$page_templater->register('usergroupid', $usergroupid);
			}
			else
			{

				// group is not moderated: update user & join group
				$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
				$userdata->set_existing($vbulletin->userinfo);
				$userdata->set('membergroupids', (($vbulletin->userinfo['membergroupids'] == '') ? $usergroupid : $vbulletin->userinfo['membergroupids'] . ',' . $usergroupid));

				($hook = vBulletinHook::fetch_hook('profile_joingroup_unmoderated')) ? eval($hook) : false;

				$userdata->save();

				$usergroupname = $usergroup['title'];
				print_standard_redirect(array('usergroup_welcome',$usergroupname));  
			}

		}
		else
		{
			eval(standard_error(fetch_error('usergroup_notpublic')));
		}
	}

}

// *************************************************************************

if ($_REQUEST['do'] == 'editusergroups')
{
	// draw cp nav bar
	construct_usercp_nav('usergroups');

	// check to see if there are usergroups available
	$haspublicgroups = false;
	foreach ($vbulletin->usergroupcache AS $usergroup)
	{
		if ($usergroup['ispublicgroup'] or $usergroup['canoverride'])
		{
			$haspublicgroups = true;
			break;
		}
	}

	($hook = vBulletinHook::fetch_hook('profile_editusergroups_start')) ? eval($hook) : false;

	if (!$haspublicgroups)
	{
		eval(standard_error(fetch_error('no_public_usergroups')));
	}
	else
	{
		$membergroups = fetch_membergroupids_array($vbulletin->userinfo);

		// query user's usertitle based on posts ladder
		$usertitle = $db->query_first_slave("
			SELECT *
			FROM " . TABLE_PREFIX . "usertitle
			WHERE minposts < " . $vbulletin->userinfo['posts'] . "
			ORDER BY minposts DESC
			LIMIT 1
		");

		// get array of all usergroup leaders
		$bbuserleader = array();
		$leaders = array();
		$groupleaders = $db->query_read_slave("
			SELECT ugl.*, user.username
			FROM " . TABLE_PREFIX . "usergroupleader AS ugl
			INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
		");
		while ($groupleader = $db->fetch_array($groupleaders))
		{
			if ($groupleader['userid'] == $vbulletin->userinfo['userid'])
			{
				$bbuserleader[] = $groupleader['usergroupid'];
			}
			$leaders["$groupleader[usergroupid]"]["$groupleader[userid]"] = $groupleader;
		}
		unset($groupleader);
		$db->free_result($groupleaders);

		// notify about new join requests if user is a group leader
		$joinrequestbits = '';
		if (!empty($bbuserleader))
		{
			$joinrequests = $db->query_read_slave("
				SELECT usergroup.title, usergroup.opentag, usergroup.closetag, usergroup.usergroupid, COUNT(usergrouprequestid) AS requests
				FROM " . TABLE_PREFIX . "usergroup AS usergroup
				LEFT JOIN " . TABLE_PREFIX . "usergrouprequest AS req USING(usergroupid)
				WHERE usergroup.usergroupid IN(" . implode(',', $bbuserleader) . ")
				GROUP BY usergroup.usergroupid
				ORDER BY usergroup.title
			");
			while ($joinrequest = $db->fetch_array($joinrequests))
			{
				exec_switch_bg();
				$joinrequest['requests'] = vb_number_format($joinrequest['requests']);
				$templater = vB_Template::create('modifyusergroups_joinrequestbit');
					$templater->register('bgclass', $bgclass);
					$templater->register('joinrequest', $joinrequest);
				$joinrequestbits .= $templater->render();
			}
			unset($joinrequest);
			$db->free_result($joinrequests);
		}

		$show['joinrequests'] = iif($joinrequestbits != '', true, false);

		// get usergroups
		$groups = array();
		foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
		{
			if ($usergroup['usertitle'] == '')
			{
				$usergroup['usertitle'] = $usertitle['title'];
			}
			if (in_array($usergroupid, $membergroups))
			{
				$groups['member']["$usergroupid"] = $usergroup;
			}
			else if ($usergroup['ispublicgroup'])
			{
				$groups['notmember']["$usergroupid"] = $usergroup;
				$couldrequest[] = $usergroupid;
			}
		}

		// do groups user is NOT a member of
		$nonmembergroupbits = '';
		if (is_array($groups['notmember']))
		{
			// get array of join requests for this user
			$requests = array();
			$joinrequests = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "usergrouprequest WHERE userid=" . $vbulletin->userinfo['userid'] . " AND usergroupid IN (" . implode(',', $couldrequest) . ')');
			while ($joinrequest = $db->fetch_array($joinrequests))
			{
				$requests["$joinrequest[usergroupid]"] = $joinrequest;
			}
			unset($joinrequest);
			$db->free_result($joinrequests);

			foreach ($groups['notmember'] AS $usergroupid => $usergroup)
			{
				$joinrequested = 0;
				$groupleaders = array();
				if (is_array($leaders["$usergroupid"]))
				{
					$clc = 0;
					$ismoderated = 1;
					foreach ($leaders["$usergroupid"] AS $leader)
					{
						$clc++;
						$leader['comma'] = $vbphrase['comma_space'];
						$groupleaders[$clc] = $leader;
					}

					// Last element
					if ($clc) 
					{
						$groupleaders[$clc]['comma'] = '';
					}

					if (isset($requests["$usergroupid"]))
					{
						$joinrequest = $requests["$usergroupid"];
						$joinrequest['date'] = vbdate($vbulletin->options['dateformat'], $joinrequest['dateline'], 1);
						$joinrequest['time'] = vbdate($vbulletin->options['timeformat'], $joinrequest['dateline'], 1);
						$joinrequested = 1;
					}
				}
				else
				{
					$ismoderated = 0;
				}

				($hook = vBulletinHook::fetch_hook('profile_editusergroups_nonmemberbit')) ? eval($hook) : false;

				$templater = vB_Template::create('modifyusergroups_nonmemberbit');
					$templater->register('bgclass', $bgclass);
					$templater->register('groupleaders', $groupleaders);
					$templater->register('ismoderated', $ismoderated);
					$templater->register('joinrequest', $joinrequest);
					$templater->register('joinrequested', $joinrequested);
					$templater->register('usergroup', $usergroup);
				$nonmembergroupbits .= $templater->render();
			}
		}

		$show['nonmembergroups'] = iif($nonmembergroupbits != '', true, false);

		// set primary group info
		$primarygroupid = $vbulletin->userinfo['usergroupid'];
		$primarygroup = $groups['member']["{$vbulletin->userinfo['usergroupid']}"];

		// do groups user IS a member of
		$membergroupbits = '';
		$show['canleave'] = false;
		foreach ($groups['member'] AS $usergroupid => $usergroup)
		{
			if ($usergroupid != $vbulletin->userinfo['usergroupid'] AND $usergroup['ispublicgroup'])
			{
				exec_switch_bg();
				if ($usergroup['usertitle'] == '')
				{
					$usergroup['usertitle'] = $usertitle['title'];
				}
				if (isset($leaders["$usergroupid"]["{$vbulletin->userinfo['userid']}"]))
				{
					$show['isleader'] = true;
				}
				else
				{
					$show['isleader'] = false;
					$show['canleave'] = true;
				}

				($hook = vBulletinHook::fetch_hook('profile_editusergroups_memberbit')) ? eval($hook) : false;

				$templater = vB_Template::create('modifyusergroups_memberbit');
					$templater->register('bgclass', $bgclass);
					$templater->register('usergroup', $usergroup);
				$membergroupbits .= $templater->render();
			}
		}

		$show['membergroups'] = iif($membergroupbits != '', true, false);

		// do groups user could use as display group
		$checked = array();
		if ($vbulletin->userinfo['displaygroupid'])
		{
			$checked["{$vbulletin->userinfo['displaygroupid']}"] = 'checked="checked"';
		}
		else
		{
			$checked["{$vbulletin->userinfo['usergroupid']}"] = 'checked="checked"';
		}
		$displaygroupbits = '';
		foreach ($groups['member'] AS $usergroupid => $usergroup)
		{
			if ($usergroupid != $vbulletin->userinfo['usergroupid'] AND $usergroup['canoverride'])
			{
				exec_switch_bg();

				($hook = vBulletinHook::fetch_hook('profile_editusergroups_displaybit')) ? eval($hook) : false;

				$templater = vB_Template::create('modifyusergroups_displaybit');
					$templater->register('bgclass', $bgclass);
					$templater->register('checked', $checked);
					$templater->register('usergroup', $usergroup);
					$templater->register('usergroupid', $usergroupid);
				$displaygroupbits .= $templater->render();
			}
		}

		$show['displaygroups'] = iif($displaygroupbits != '', true, false);

		if (!$show['joinrequests'] AND !$show['nonmembergroups'] AND !$show['membergroups'] AND !$show['displaygroups'])
		{
			eval(standard_error(fetch_error('no_public_usergroups')));
		}

		$navbits[''] = $vbphrase['group_memberships'];
		$includecss['joinrequests'] = 'joinrequests.css';

		$page_templater = vB_Template::create('modifyusergroups');
		$page_templater->register('checked', $checked);
		$page_templater->register('displaygroupbits', $displaygroupbits);
		$page_templater->register('joinrequestbits', $joinrequestbits);
		$page_templater->register('membergroupbits', $membergroupbits);
		$page_templater->register('nonmembergroupbits', $nonmembergroupbits);
		$page_templater->register('primarygroup', $primarygroup);
		$page_templater->register('primarygroupid', $primarygroupid);
	}
}

if ($_POST['do'] == 'deleteusergroups')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usergroupid' => TYPE_UINT,
		'deletebox'   => TYPE_ARRAY_BOOL
	));

	($hook = vBulletinHook::fetch_hook('profile_deleteusergroups_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['usergroupid'])
	{
		// check permission to do authorizations in this group
		if (!$leadergroup = $db->query_first("
			SELECT usergroupleaderid
			FROM " . TABLE_PREFIX . "usergroupleader AS usergroupleader
			WHERE userid = " . $vbulletin->userinfo['userid'] . "
				AND usergroupid = " . $vbulletin->GPC['usergroupid'] . "
		"))
		{
			print_no_permission();
		}

		if (!empty($vbulletin->GPC['deletebox']))
		{
			foreach (array_keys($vbulletin->GPC['deletebox']) AS $userid)
			{
				$userids .= ',' . intval($userid);
			}

			$users = $db->query_read_slave("
				SELECT u.*
				FROM " . TABLE_PREFIX . "user AS u
				LEFT JOIN " . TABLE_PREFIX . "usergroupleader AS ugl ON (u.userid = ugl.userid AND ugl.usergroupid = " . $vbulletin->GPC['usergroupid'] . ")
				WHERE u.userid IN (0$userids) AND ugl.usergroupleaderid IS NULL
			");
			while ($user = $db->fetch_array($users))
			{
				$membergroups = fetch_membergroupids_array($user, false);
				$newmembergroups = array();
				foreach($membergroups AS $groupid)
				{
					if ($groupid != $user['usergroupid'] AND $groupid != $vbulletin->GPC['usergroupid'])
					{
						$newmembergroups[] = $groupid;
					}
				}

				// init user data manager
				$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
				$userdata->set_existing($user);
				$userdata->set('membergroupids', $newmembergroups);
				if ($user['displaygroupid'] == $vbulletin->GPC['usergroupid'])
				{
					$userdata->set('displaygroupid', 0);
				}
				($hook = vBulletinHook::fetch_hook('profile_deleteusergroups_process')) ? eval($hook) : false;
				$userdata->save();
			}

			$vbulletin->url = 'memberlist.php?' . $vbulletin->session->vars['sessionurl'] . 'usergroupid=' . $vbulletin->GPC['usergroupid'];
			print_standard_redirect('redirect_removedusers');  
		}
		else
		{
			// Print didn't select any users to delete
			eval(standard_error(fetch_error('usergroupleader_deleted')));
		}
	}
	else
	{
		print_no_permission();
	}

}

// ############################### Delete attachments for current user #################
if ($_POST['do'] == 'deleteattachments')
{
	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'attachmentslist' => TYPE_ARRAY_BOOL,
		'perpage'         => TYPE_UINT,
		'pagenumber'      => TYPE_UINT,
		'showthumbs'      => TYPE_BOOL,
		'userid'          => TYPE_UINT
	));

	($hook = vBulletinHook::fetch_hook('profile_deleteattachments_start')) ? eval($hook) : false;

	$idlist = array();
	foreach (array_keys($vbulletin->GPC['attachmentslist']) AS $attachmentid)
	{
		$idlist[] = intval($attachmentid);
	}

	if (empty($vbulletin->GPC['attachmentslist']) OR empty($idlist))
	{
		eval(standard_error(fetch_error('attachdel')));
	}

	require_once(DIR . '/includes/functions_file.php');
	if (!empty($idlist))
	{
		$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_STANDARD);
		$attachdata->condition = "attachmentid IN (" . implode(", ", $idlist) . ")";
		$attachdata->delete();
	}

	($hook = vBulletinHook::fetch_hook('profile_deleteattachments_complete')) ? eval($hook) : false;

	$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editattachments&amp;pp=' . $vbulletin->GPC['perpage'] . '&amp;page=' . $vbulletin->GPC['pagenumber'] . '&amp;showthumbs=' . $vbulletin->GPC['showthumbs'] . '&amp;u=' . $vbulletin->GPC['userid'];
	print_standard_redirect('redirect_attachdel');  

}

// ############################### List of attachments for current user ################
if ($_REQUEST['do'] == 'editattachments')
{
	// Variables reused in templates
	$perpage = $vbulletin->input->clean_gpc('r', 'perpage', TYPE_UINT);
	$pagenumber = $vbulletin->input->clean_gpc('r', 'pagenumber', TYPE_UINT);
	$showthumbs = $vbulletin->input->clean_gpc('r', 'showthumbs', TYPE_BOOL);

	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT
	));

	$show['attachment_list'] = true;

	if (!$vbulletin->GPC['userid'] OR $vbulletin->GPC['userid'] == $vbulletin->userinfo['userid'])
	{
		// show own attachments in user cp
		$userinfo =& $vbulletin->userinfo;
		$userid = $vbulletin->userinfo['userid'];
		$username = $vbulletin->userinfo['username'];
		$show['attachquota'] = true;
	}
	else
	{
		// show someone else's attachments
		$userinfo = verify_id('user', $vbulletin->GPC['userid'], 1, 1);
		$userid = $userinfo['userid'];
		$username = $userinfo['username'];
		$show['otheruserid'] = true;
	}

	($hook = vBulletinHook::fetch_hook('profile_editattachments_start')) ? eval($hook) : false;

	// Get attachment count
	require_once(DIR . '/packages/vbattach/attach.php');
	$attachmultiple = new vB_Attachment_Display_Multiple($vbulletin);
	$attachments = $attachmultiple->fetch_results("a.userid = $userid", true);

	$totalattachments = intval($attachments['count']);
	$attachsum = $attachments['uniquesum'];

	if (!$totalattachments AND $userid != $vbulletin->userinfo['userid'])
	{
		eval(standard_error(fetch_error('noattachments')));
	}
	else if (!$totalattachments)
	{
		$show['attachment_list'] = false;
		$show['attachquota'] = false;
	}
	else
	{
		if ($permissions['attachlimit'])
		{
			if ($attachsum >= $permissions['attachlimit'])
			{
				$totalsize = 0;
				$attachsize = 100;
			}
			else
			{
				$attachsize = ceil($attachsum / $permissions['attachlimit'] * 100);
				$totalsize = 100 - $attachsize;
			}

			$attachlimit = vb_number_format($permissions['attachlimit'], 1, true);
		}

		$attachsum = vb_number_format($attachsum, 1, true);

		if ($showthumbs)
		{
			$maxperpage = 10;
			$defaultperpage = 10;
		}
		else
		{
			$maxperpage = 200;
			$defaultperpage = 20;
		}

		sanitize_pageresults($totalattachments, $pagenumber, $perpage, $maxperpage, $defaultperpage);

		$limitlower = ($pagenumber - 1) * $perpage + 1;
		$limitupper = ($pagenumber) * $perpage;

		if ($limitupper > $totalattachments)
		{
			$limitupper = $totalattachments;
			if ($limitlower > $totalattachments)
			{
				$limitlower = $totalattachments - $perpage;
			}
		}
		if ($limitlower <= 0)
		{
			$limitlower = 1;
		}

		$attachments = $attachmultiple->fetch_results("a.userid = $userid", false, $limitlower - 1, $perpage, 'dateline');

		$sorturl = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=editattachments';
		if ($userid != $vbulletin->userinfo['userid'])
		{
			$sorturl .= "&amp;u=$userid";
		}
		if ($perpage != $defaultperpage)
		{
			$sorturl .= "&amp;pp=$perpage";
		}
		if ($showthumbs)
		{
			$sorturl .= "&amp;showthumbs=1";
		}
		$pagenav = construct_page_nav($pagenumber, $perpage, $totalattachments, $sorturl);

		foreach($attachments AS $attachment)
		{
			$result = $attachmultiple->process_attachment($attachment, $showthumbs);
			if ($show['candelete'])
			{
				$show['deleteoption'] = true;
			}
			$templater = vB_Template::create('modifyattachmentsbit_' . $result['template']);
			#unset($result['template']);
			foreach ($result AS $key => $value)
			{
				$templater->register($key, $value);
			}
			$uniquebit = $templater->render();

			$templater = vB_Template::create('modifyattachmentsbit');
			foreach ($result AS $key => $value)
			{
				if ($key == $result['template'])
				{
					$templater->register('info', $value);
				}
			}
			$templater->register('uniquebit', $uniquebit);
			$attachmentlistbits .= $templater->render();
		}

		$totalattachments = vb_number_format($totalattachments);

		$show['attachlimit'] = $permissions['attachlimit'];
		$show['currentattachsize'] = $attachsize;
		$show['totalattachsize'] = $totalsize;
		$show['thumbnails'] = $showthumbs;
	}

	$show['lightbox'] = ($vbulletin->options['lightboxenabled'] AND $vbulletin->options['usepopups'] AND $showthumbs);

	($hook = vBulletinHook::fetch_hook('profile_editattachments_complete')) ? eval($hook) : false;

	if ($userid == $vbulletin->userinfo['userid'])
	{
		// show $vbulletin->userinfo's attachments in usercp
		construct_usercp_nav('attachments');
		$navbits[''] = construct_phrase($vbphrase['attachments_posted_by_x'], $vbulletin->userinfo['username']);
	}
	else
	{
		// show some other user's attachments
		$pagetitle = construct_phrase($vbphrase['attachments_posted_by_x'], $username);

		$navbits = array(
			fetch_seo_url('member', $userinfo) => $vbphrase['view_profile'],
			'' => $pagetitle
		);

		$shelltemplatename = 'GENERIC_SHELL';
	}

	$includecss['attachments'] = 'attachments.css';
	$includecss['lightbox'] = 'lightbox.css';

	$page_templater = vB_Template::create('modifyattachments');
	$page_templater->register('attachlimit', $attachlimit);
	$page_templater->register('attachsize', $attachsize);
	$page_templater->register('attachsum', $attachsum);
	$page_templater->register('pagenav', $pagenav);
	$page_templater->register('pagenumber', $pagenumber);
	$page_templater->register('perpage', $perpage);
	$page_templater->register('showthumbs', $showthumbs);
	$page_templater->register('template', $template);
	$page_templater->register('totalattachments', $totalattachments);
	$page_templater->register('totalsize', $totalsize);
	$page_templater->register('userid', $userid);
	$page_templater->register('username', $username);
	$page_templater->register('attachmentlistbits', $attachmentlistbits);
}

// #######################################################################
if ($_REQUEST['do'] == 'customize' OR $_POST['do'] == 'docustomize')
{
	if (!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_profile_styling']))
	{
		print_no_permission();
	}
	print_no_permission();

	require_once(DIR . '/includes/class_usercss.php');

	$selector_base = array(
		'font_family'       => '',
		'font_size'         => '',
		'color'             => '',
		'background_color'  => '',
 		'background_image'  => '',
		'border_style'      => '',
		'border_color'      => '',
		'border_width'      => '',
		'linkcolor'         => '',
		'shadecolor'        => '',
		'padding'           => '',
		'background_repeat' => ''
	);

	$usercsspermissions = array(
		'caneditfontfamily' => $vbulletin->userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditfontfamily'] ? true  : false,
		'caneditfontsize'   => $vbulletin->userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditfontsize'] ? true : false,
		'caneditcolors'     => $vbulletin->userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditcolors'] ? true : false,
		'caneditbgimage'    => ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_albums'] AND $vbulletin->userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditbgimage']) ? true : false,
		'caneditborders'    => $vbulletin->userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditborders'] ? true : false,
		'canusetheme'    => $vbulletin->userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['canusetheme'] ? true : false,
		'cancustomize'    => $vbulletin->userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['cancustomize'] ? true : false
	);

	$usercss = new vB_UserCSS($vbulletin, $vbulletin->userinfo['userid']);

	$allowedfonts = $usercss->build_select_option($vbulletin->options['usercss_allowed_fonts']);
	$allowedfontsizes = $usercss->build_select_option($vbulletin->options['usercss_allowed_font_sizes']);
	$allowedborderwidths = $usercss->build_select_option($vbulletin->options['usercss_allowed_border_widths']);
	$allowedpaddings = $usercss->build_select_option($vbulletin->options['usercss_allowed_padding']);
}

// #######################################################################
if ($_POST['do'] == 'docustomize')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usercss' => TYPE_ARRAY,
		'ajax'    => TYPE_BOOL // means preview
	));

	($hook = vBulletinHook::fetch_hook('profile_docustomize_start')) ? eval($hook) : false;

	foreach ($vbulletin->GPC['usercss'] AS $selectorname => $selector)
	{
		if (!isset($usercss->cssedit["$selectorname"]) OR !empty($usercss->cssedit["$selectorname"]['noinputset']))
		{
			$usercss->error[] = fetch_error('invalid_selector_name_x', htmlspecialchars_uni($selectorname));
			continue;
		}

		if (!is_array($selector))
		{
			continue;
		}

		foreach ($selector AS $property => $value)
		{
			$prop_perms = $usercss->properties["$property"]['permission'];

			if (empty($usercsspermissions["$prop_perms"]) OR !in_array($property, $usercss->cssedit["$selectorname"]['properties']))
			{
				$usercss->error[] = fetch_error('no_permission_edit_selector_x_property_y', htmlspecialchars_uni($selectorname), htmlspecialchars_uni($property));
				continue;
			}

			unset($allowedlist);
			switch ($property)
			{
				case 'font_size':    $allowedlist = $allowedfontsizes; break;
				case 'font_family':  $allowedlist = $allowedfonts; break;
				case 'border_width': $allowedlist = $allowedborderwidths; break;
				case 'padding':      $allowedlist = $allowedpaddings; break;
			}

			if (isset($allowedlist))
			{
				if (!in_array($value, $allowedlist) AND $value != '')
				{
					$usercss->invalid["$selectorname"]["$property"] = ' usercsserror ';
					continue;
				}
			}

			$usercss->parse($selectorname, $property, $value);
		}
	}

	($hook = vBulletinHook::fetch_hook('profile_docustomize_process')) ? eval($hook) : false;

	if ($vbulletin->GPC['ajax'])
	{
		// AJAX means get the preview
		$effective_css = $usercss->build_css($usercss->fetch_effective());
		$effective_css = str_replace('/*sessionurl*/', $vbulletin->session->vars['sessionurl_js'], $effective_css);

		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('preview');
			$xml->add_tag('css', process_replacement_vars($effective_css));
		$xml->close_group();
		$xml->print_xml();
	}

	if (empty($usercss->error) AND empty($usercss->invalid))
	{
		$usercss->save();
		$vbulletin->url = "profile.php?"  . $vbulletin->session->vars['sessionurl'] . "do=customize";
		print_standard_redirect('usercss_saved');  
	}
	else if (!empty($usercss->error))
	{
		standard_error(implode("<br />", $usercss->error));
	}
	else
	{
		// have invalid, no errors
		$_REQUEST['do'] = 'customize';
		define('HAVE_ERRORS', true);
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'customize')
{
	$cssdisplayinfo = $usercss->build_display_array();
	$errors = '';

	// if we don't have errors, the displayed values are the existing ones
	// otherwise, use the form submission
	if (!defined('HAVE_ERRORS'))
	{
		$selectors_saved = $usercss->existing;
	}

	($hook = vBulletinHook::fetch_hook('profile_customize_start')) ? eval($hook) : false;

	$usercssbits = '';
	foreach ($cssdisplayinfo AS $selectorname => $selectorinfo)
	{
		$selector = $selector_base;

		$selectorinvalid = array();
		$field_names = array();

		$invalidpropertyphrases = array();

		if (empty($selectorinfo['properties']))
		{
			$selectorinfo['properties'] = $usercss->cssedit["$selectorname"]['properties'];
		}

		if (!is_array($selectorinfo['properties']))
		{
			continue;
		}

		$selector['phrase'] = $vbphrase["$selectorinfo[phrasename]"];

		foreach ($selectorinfo['properties'] AS $key => $value)
		{
			if (is_numeric($key))
			{
				$this_property = $value;
				$this_selector = $selectorname;
			}
			else
			{
				$this_property = $key;
				$this_selector = $value;
			}

			if (!$usercsspermissions[$usercss->properties["$this_property"]['permission']])
			{
				continue;
			}

			$field_names["$this_property"] = "usercss[$this_selector][$this_property]";

			if (defined('HAVE_ERRORS'))
			{
				if (isset($vbulletin->GPC['usercss']["$this_selector"]["$this_property"]))
				{
					$selector["$this_property"] = $vbulletin->GPC['usercss']["$this_selector"]["$this_property"];
				}

				if (isset($usercss->invalid["$this_selector"]["$this_property"]))
				{
					$selectorinvalid["$this_property"] = $usercss->invalid["$this_selector"]["$this_property"];

					$error_link_phrase = $vbphrase["usercss_$this_property"];
					$templater = vB_Template::create('modifyusercss_error_link');
						$templater->register('error_link_phrase', $error_link_phrase);
						$templater->register('selectorname', $selectorname);
						$templater->register('this_property', $this_property);
					$invalidpropertyphrases[] = $templater->render();
				}
			}
			else if (isset($selectors_saved["$this_selector"]["$this_property"]))
			{
				$selector["$this_property"] = $selectors_saved["$this_selector"]["$this_property"];
			}
		}

		if ($invalidpropertyphrases)
		{
			$invalid_properties_string = implode(", ", $invalidpropertyphrases);
			$templater = vB_Template::create('modifyusercss_error');
				$templater->register('invalid_properties_string', $invalid_properties_string);
				$templater->register('selector', $selector);
				$templater->register('selectorname', $selectorname);
			$errors .= $templater->render();
		}

		$show['textcolor'] = ($field_names['color'] OR $field_names['linkcolor'] OR $field_names['shadecolor']);
		$show['background'] = ($field_names['background_color'] OR $field_names['background_image']);
		$show['font'] = ($field_names['font_family'] OR $field_names['font_size']);
		$show['border'] = ($field_names['border_style'] OR $field_names['border_color'] OR $field_names['border_width'] OR $field_names['padding']);

		if ($field_names['font_family'])
		{
			$fontselect = '';
			foreach ($allowedfonts AS $key => $font)
			{
				$optionvalue = htmlspecialchars_uni($font);
				$optionclass = '';
				$optionselected = ($font == $selector['font_family'] ? ' selected="selected"' : '');
				$optiontitle = !empty($vbphrase["usercss_font_$key"]) ? $vbphrase["usercss_font_$key"] : $key;

				$fontselect .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}
		}

		if ($field_names['font_size'])
		{
			$fontsizeselect = '';
			foreach ($allowedfontsizes AS $key => $fontsize)
			{
				$optionvalue = htmlspecialchars_uni($fontsize);
				$optionclass = '';
				$optionselected = ($fontsize == $selector['font_size'] ? ' selected="selected"' : '');
				$optiontitle = !empty($vbphrase["usercss_fontsize_$key"]) ? $vbphrase["usercss_fontsize_$key"] : $key;

				$fontsizeselect .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}
		}

		if ($field_names['border_width'])
		{
			$borderwidthselect = '';
			foreach ($allowedborderwidths AS $key => $borderwidth)
			{
				$optionvalue = htmlspecialchars_uni($borderwidth);
				$optionclass = '';
				$optionselected = ($borderwidth == $selector["border_width"] ? ' selected="selected"' : '');
				$optiontitle = !empty($vbphrase["usercss_borderwidth_$key"]) ? $vbphrase["usercss_borderwidth_$key"] : $key;

				$borderwidthselect .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}
		}

		if ($field_names['background_image'])
		{
			if (!empty($selector['background_image']))
			{
				if (preg_match("/^([0-9]+),([0-9]+)$/", $selector['background_image'], $picture))
				{
					$selector['background_image'] = create_full_url("attachment.php?attachmentid=" . $picture[2] . "&amp;albumid=" . $picture[1]);
				}
			}
		}

		if ($field_names['padding'])
		{
			$paddingselect = '';

			foreach ($allowedpaddings AS $key => $padding)
			{
				$optionvalue = htmlspecialchars_uni($padding);
				$optionclass = '';
				$optionselected = ($padding == $selector['padding'] ? ' selected="selected"' : '');
				$optiontitle = !empty($vbphrase["usercss_padding_$key"]) ? $vbphrase["usercss_padding_$key"] : $key;

				$paddingselect .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}
		}

		if ($field_names)
		{
			$border_style_selected = array(
				$selector['border_style'] => ' selected="selected"'
			);

			$repeat_selected = array(
				str_replace('-', '_', $selector['background_repeat']) => ' selected="selected"'
			);

			// make safe for display
			foreach ($selector AS $property => $selvalue)
			{
				$selector["$property"] = htmlspecialchars_uni($selvalue);
			}

			$selector['phrase'] = $vbphrase["$selectorinfo[phrasename]"];
			$selector['description'] = (isset($vbphrase["$selectorinfo[phrasename]_desc"]) ? $vbphrase["$selectorinfo[phrasename]_desc"] : '');

			($hook = vBulletinHook::fetch_hook('profile_customize_bit')) ? eval($hook) : false;

			$templater = vB_Template::create('modifyusercss_bit');
				$templater->register('borderwidthselect', $borderwidthselect);
				$templater->register('border_style_selected', $border_style_selected);
				$templater->register('field_names', $field_names);
				$templater->register('fontselect', $fontselect);
				$templater->register('fontsizeselect', $fontsizeselect);
				$templater->register('paddingselect', $paddingselect);
				$templater->register('repeat_selected', $repeat_selected);
				$templater->register('selector', $selector);
				$templater->register('selectorinvalid', $selectorinvalid);
				$templater->register('selectorname', $selectorname);
			$usercssbits .= $templater->render();
		}
	}

	if ($usercssbits)
	{
		$types = vB_Types::instance();
		$contenttypeid = $types->getContentTypeID('vBForum_Album');

		$albumbits = '';
		$picturerowbits = '';
		$count = 0;
		$albums = array();
		$profilealbums = $db->query_read("
			SELECT
				album.title, album.albumid,
				a.dateline, a.attachmentid, a.caption,
				fd.filesize, fd.thumbnail_filesize, fd.thumbnail_dateline, fd.thumbnail_width, fd.thumbnail_height, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail
			FROM " . TABLE_PREFIX . "album AS album
			INNER JOIN " . TABLE_PREFIX . "attachment AS a ON (a.contentid = album.albumid)
			INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (fd.filedataid = a.filedataid)
			WHERE
				album.state = 'profile'
					AND
				album.userid = " . $vbulletin->userinfo['userid'] . "
					AND
				a.state = 'visible'
					AND
				a.contenttypeid = $contenttypeid
			ORDER BY
				album.albumid, a.attachmentid
		");
		while ($album = $db->fetch_array($profilealbums))
		{
			$albums[$album['albumid']]['title'] = $album['title'];
			$albums[$album['albumid']]['pictures'][] = $album;
		}

		require_once(DIR . '/includes/functions_album.php');
		foreach ($albums AS $albumid => $info)
		{
			$picturebits = '';
			$show['backgroundpicker'] = true;
			$optionvalue = $albumid;

			// Need to shorten album titles here
			$optiontitle = "{$info['title']} (" . count($info['pictures']) . ")";
			$optionselected = empty($albumbits) ? 'selected="selected"' : '';
			$albumbits .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			$show['hidediv'] = empty($picturerowbits) ? false : true;
			foreach($info['pictures'] AS $picture)
			{
				$picture['caption_preview'] = fetch_censored_text(fetch_trimmed_title(
					$picture['caption'],
					$vbulletin->options['album_captionpreviewlen']
				));
				$picture['dimensions'] = ($picture['thumbnail_width'] ? "width=\"$picture[thumbnail_width]\" height=\"$picture[thumbnail_height]\"" : '');
				$templater = vB_Template::create('modifyusercss_backgroundbit');
					$templater->register('picture', $picture);
				$picturebits .= $templater->render();
			}

			$templater = vB_Template::create('modifyusercss_backgroundrow');
				$templater->register('albumid', $albumid);
				$templater->register('picturebits', $picturebits);
			$picturerowbits .= $templater->render();
		}

		$show['albumselect'] = (count($albums) == 1) ? false : true;

		$vbulletin->userinfo['cachedcss'] = $usercss->build_css($usercss->fetch_effective());
		$vbulletin->userinfo['cachedcss'] = str_replace('/*sessionurl*/', $vbulletin->session->vars['sessionurl_js'], $vbulletin->userinfo['cachedcss']);
		if ($vbulletin->userinfo['cachedcss'])
		{
			$userinfo = $vbulletin->userinfo;
			$templater = vB_Template::create('memberinfo_usercss');
				$templater->register('userinfo', $userinfo);
			$usercss_string = $templater->render();
		}
		else
		{
			$usercss_string = '';
		}

		$show['usercss'] = true;
	}
	else
	{
		$show['usercss'] = false;
	}

	$clientscripts_template = vB_Template::create('modifyusercss_scripts');
		$templater->register('usercss_string', $usercss_string);

	$navbits[''] = $vbphrase['customize_profile'];

	construct_usercp_nav('customize');

	$page_templater = vB_Template::create('modifyusercss');
	$page_templater->register('albumbits', $albumbits);
	$page_templater->register('errors', $errors);
	$page_templater->register('picturerowbits', $picturerowbits);
	$page_templater->register('usercssbits', $usercssbits);
	$page_templater->register('usercsspermissions', $usercsspermissions);
}

// #######################################################################
if ($_REQUEST['do'] == 'privacy' OR $_POST['do'] == 'doprivacy')
{
	if (!$vbulletin->options['profileprivacy'])
	{
		print_no_permission();
	}

	if (!($permissions['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditprivacy']))
	{
		print_no_permission();
	}

	// Create the configurable blocks
	$blocks = array(
		'contactinfo' => array(
			'name' => $vbphrase['contact_info'],
			'requirement' => 0,
			'enabled' => true
		),
		'profile_picture' => array(
			'name' => $vbphrase['profile_picture'],
			'requirement' => 0,
			'enabled' => $vbulletin->options['profilepicenabled']
		),
		'visitor_messaging' => array(
			'name' => $vbphrase['visitor_messages'],
			'requirement' => 0,
			'enabled' => ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_visitor_messaging'])
		),
		'albums' => array(
			'name' => $vbphrase['albums'],
			'requirement' => 0,
			'enabled' => (($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_albums'])
							AND (($vbulletin->userinfo['permissions']['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['canalbum'])
								OR can_moderate(0, 'canmoderatepictures')))
		),
		'aboutme' => array(
			'name' => $vbphrase['about_me'],
			'requirement' => 0,
			'enabled' => true
		),
		'friends' => array(
			'name' => $vbphrase['friends'],
			'requirement' => 0,
			'enabled' => ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends'])
		),
		'visitors' => array(
			'name' => $vbphrase['recent_visitors'],
			'requirement' => 0,
			'enabled' => ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_visitor_tracking'])
		),
		'groups' => array(
			'name' => $vbphrase['group_memberships'],
			'requirement' => 0,
			'enabled' => ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_groups'])
		),
	);

	// Get custom fields
	$custom_blocks = $vbulletin->db->query_read_slave("
		SELECT pfc.profilefieldcategoryid AS id
		FROM " . TABLE_PREFIX . "profilefieldcategory AS pfc
		INNER JOIN " . TABLE_PREFIX . "profilefield pf
		 ON pf.profilefieldcategoryid = pfc.profilefieldcategoryid
		WHERE pfc.allowprivacy = 1
		GROUP BY pfc.profilefieldcategoryid
		ORDER BY pfc.location, pfc.displayorder
	");

	while($block = $vbulletin->db->fetch_array($custom_blocks))
	{
		$blocks["profile_cat$block[id]"] = array (
			'name' => $vbphrase["category$block[id]_title"],
			'requirement' => 0,
			'enabled' => true
		);
	}
	$vbulletin->db->free_result($custom_blocks);
}

// #######################################################################
if ($_POST['do'] == 'doprivacy')
{
	$vbulletin->input->clean_gpc('r',
		'blockprivacy',	TYPE_ARRAY_UINT
	);

	$values = array();
	foreach($vbulletin->GPC['blockprivacy'] AS $blockid => $requirement)
	{
		if (isset($blocks[$blockid]))
		{
			$blocks[$blockid]['requirement'] = $requirement;
			$values[] = "({$vbulletin->userinfo['userid']}, '$blockid', $requirement)";
		}
	}

	if (sizeof($values))
	{
		$vbulletin->db->query_write($sql = "
			REPLACE INTO " . TABLE_PREFIX . "profileblockprivacy
				(userid, blockid, requirement)
			VALUES " . implode(',', $values) . "
		");
	}

	$vbulletin->url = "profile.php?"  . $vbulletin->session->vars['sessionurl'] . "do=privacy";
	print_standard_redirect('profile_privacy_saved');  
}

// #######################################################################
if ($_REQUEST['do'] == 'privacy')
{
	// Get current privacy settings
	if (!isset($_POST['do']))
	{
		$results = $vbulletin->db->query_read_slave("
			SELECT blockid, requirement
			FROM " . TABLE_PREFIX . "profileblockprivacy
			WHERE userid = " . intval($vbulletin->userinfo['userid']) . "
		");

		while ($result = $vbulletin->db->fetch_array($results))
		{
			if (isset($blocks[$result['blockid']]))
			{
				$blocks[$result['blockid']]['requirement'] = $result['requirement'];
			}
		}
		$vbulletin->db->free_result($results);
	}

	foreach($blocks as $blockid => $block)
	{
		if ($block['enabled'])
		{
			$selected = array($block['requirement'] => 'selected="selected"');
			$templater = vB_Template::create('modifyprivacy_bit');
				$templater->register('block', $block);
				$templater->register('blockid', $blockid);
				$templater->register('selected', $selected);
			$profileprivacybits .= $templater->render();
		}
	}

	$navbits[''] = $vbphrase['profile_privacy'];

	construct_usercp_nav('privacy');

	$page_templater = vB_Template::create('modifyprofileprivacy');
	$page_templater->register('errors', $errors);
	$page_templater->register('profileprivacybits', $profileprivacybits);
}

// #############################################################################
// dismiss notice (non-ajax / no js user)
if ($_POST['do'] == 'dismissnotice')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'dismiss_noticeid' => TYPE_UINT
	));

	$dismiss_noticeid = $vbulletin->GPC['dismiss_noticeid'];

	// in IE, clicking the image won't send a value, so pull it out of the element's name
	foreach (array_keys($_POST) AS $input_name)
	{
		if (preg_match('#^dismiss_noticeid_(\d+)_x$#', $input_name, $match))
		{
			$dismiss_noticeid = intval($match[1]);
			break;
		}
	}

	if ($dismiss_noticeid)
	{
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "noticedismissed
				(noticeid, userid)
			VALUES
				(" . intval($dismiss_noticeid) . ", " . $vbulletin->userinfo['userid'] .")
		");
	}

	print_standard_redirect('redirect_notice_dismissed');  
}

// #############################################################################
// spit out final HTML if we have got this far

if (!empty($page_templater))
{
	// make navbar
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('profile_complete')) ? eval($hook) : false;

	// add any extra clientscripts
	$clientscripts = (isset($clientscripts_template) ? $clientscripts_template->render() : '');

	if (!$vbulletin->options['storecssasfile'])
	{
		$includecss = implode(',', $includecss);
	}

	// shell template
	$templater = vB_Template::create($shelltemplatename);
		$templater->register_page_templates();
		$templater->register('includecss', $includecss);
		$templater->register('cpnav', $cpnav);
		$templater->register('HTML', $page_templater->render());
		$templater->register('navbar', $navbar);
		$templater->register('navclass', $navclass);
		$templater->register('onload', $onload);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('template_hook', $template_hook);
		$templater->register('clientscripts', $clientscripts);
	print_output($templater->render());
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63231 $
|| ####################################################################
\*======================================================================*/
