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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 64477 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cpuser', 'forum', 'timezone', 'user', 'cprofilefield', 'subscription', 'banning', 'profilefield');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_profilefield.php');
require_once(DIR . '/includes/adminfunctions_user.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'userid' => TYPE_INT
));
log_admin_action(iif($vbulletin->GPC['userid'] != 0, 'user id = ' . $vbulletin->GPC['userid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// #############################################################################
// put this before print_cp_header() so we can use an HTTP header
if ($_REQUEST['do'] == 'find')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'user'              => TYPE_ARRAY,
		'profile'           => TYPE_ARRAY,
		'display'           => TYPE_ARRAY_BOOL,
		'orderby'           => TYPE_STR,
		'limitstart'        => TYPE_UINT,
		'limitnumber'       => TYPE_UINT,
		'direction'         => TYPE_STR,
		'serializedprofile' => TYPE_STR,
		'serializeduser'    => TYPE_STR,
		'serializeddisplay' => TYPE_STR
	));

	if (!empty($vbulletin->GPC['serializeduser']))
	{
		$vbulletin->GPC['user']    = @unserialize(verify_client_string($vbulletin->GPC['serializeduser']));
		$vbulletin->GPC['profile'] = @unserialize(verify_client_string($vbulletin->GPC['serializedprofile']));
	}

	if (!empty($vbulletin->GPC['serializeddisplay']))
	{
		$vbulletin->GPC['display'] = @unserialize(verify_client_string($vbulletin->GPC['serializeddisplay']));
	}

	if (@array_sum($vbulletin->GPC['display']) == 0)
	{
		$vbulletin->GPC['display'] = array('username' => 1, 'options' => 1, 'email' => 1, 'joindate' => 1, 'lastactivity' => 1, 'posts' => 1);
	}

	$condition = fetch_user_search_sql($vbulletin->GPC['user'], $vbulletin->GPC['profile']);

	switch($vbulletin->GPC['orderby'])
	{
		case 'username':
		case 'email':
		case 'joindate':
		case 'lastactivity':
		case 'lastpost':
		case 'posts':
		case 'birthday_search':
		case 'reputation':
		case 'warnings':
		case 'infractions':
		case 'ipoints':
			break;
		default:
			$vbulletin->GPC['orderby'] = 'username';
	}

	if ($vbulletin->GPC['direction'] != 'DESC')
	{
		$vbulletin->GPC['direction'] = 'ASC';
	}

	if (empty($vbulletin->GPC['limitstart']))
	{
		$vbulletin->GPC['limitstart'] = 0;
	}
	else
	{
		$vbulletin->GPC['limitstart']--;
	}

	if (empty($vbulletin->GPC['limitnumber']) OR $vbulletin->GPC['limitnumber'] == 0)
	{
		$vbulletin->GPC['limitnumber'] = 25;
	}

	$searchquery = "
		SELECT
		user.userid, reputation, username, usergroupid, birthday_search, email,
		parentemail,(options & " . $vbulletin->bf_misc_useroptions['coppauser'] . ") AS coppauser, homepage, icq, aim, yahoo, msn, skype, signature,
		usertitle, joindate, lastpost, posts, ipaddress, lastactivity, userfield.*, infractions, ipoints, warnings
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
		WHERE $condition
		ORDER BY " . $db->escape_string($vbulletin->GPC['orderby']) . " " . $db->escape_string($vbulletin->GPC['direction']) . "
		LIMIT " . $vbulletin->GPC['limitstart'] . ", " . $vbulletin->GPC['limitnumber']
	;

	$countusers = $db->query_first("
		SELECT COUNT(*) AS users
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
		WHERE $condition
	");

	$users = $db->query_read($searchquery);

	if ($countusers['users'] == 1)
	{
		// show a user if there is just one found
		$user = $db->fetch_array($users);
		// instant redirect
		exec_header_redirect("user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&u=$user[userid]");
	}
	else if ($countusers['users'] == 0)
	{
		// no users found!
		print_stop_message('no_users_matched_your_query');
	}

	define('DONEFIND', true);
	$_REQUEST['do'] = 'find2';
}

// #############################################################################

print_cp_header($vbphrase['user_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start email password #######################
if ($_REQUEST['do'] == 'emailpassword')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'email'  => TYPE_STR,
		'userid' => TYPE_UINT,
	));

	print_form_header('../login', 'emailpassword');
	construct_hidden_code('email', $vbulletin->GPC['email']);
	construct_hidden_code('url', $vbulletin->config['Misc']['admincpdir'] . "/user.php?do=find&user[email]=" . urlencode($vbulletin->GPC['email']));
	construct_hidden_code('u', $vbulletin->GPC['userid']);
	print_table_header($vbphrase['email_password_reminder_to_user']);
	print_description_row(construct_phrase($vbphrase['click_the_button_to_send_password_reminder_to_x'], "<i>" . htmlspecialchars_uni($vbulletin->GPC['email']) . "</i>"));
	print_submit_row($vbphrase['send'], 0);
}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_INT
	));

	$extratext = $vbphrase['all_posts_will_be_set_to_guest'];

	// find out if the user has social groups
	$hasgroups = $vbulletin->db->query_first("
		SELECT COUNT('groupid') AS total
		FROM " . TABLE_PREFIX . "socialgroup
		WHERE creatoruserid = " . $vbulletin->GPC['userid']
	);

	if ($hasgroups['total'])
	{
		$extratext .= "<br /><br />" . construct_phrase($vbphrase[delete_user_transfer_social_groups], $hasgroups['total']) . " <input type=\"checkbox\" name=\"transfer_groups\" value=\"1\" />";
	}

	print_delete_confirmation('user', $vbulletin->GPC['userid'], 'user', 'kill', 'user', '', $extratext);
	echo '<p align="center">' . construct_phrase($vbphrase['if_you_want_to_prune_user_posts_first'], "thread.php?" . $vbulletin->session->vars['sessionurl'] . "do=pruneuser&amp;f=-1&amp;u=" . $vbulletin->GPC['userid'] . "&amp;confirm=1") . '</p>';
}

// ###################### Start Kill #######################
if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'userid' => TYPE_INT,
		'transfer_groups' => TYPE_BOOL
	));

	// check user is not set in the $undeletable users string
	if (is_unalterable_user($vbulletin->GPC['userid']))
	{
		print_stop_message('user_is_protected_from_alteration_by_undeletableusers_var');
	}
	else
	{
		$info = fetch_userinfo($vbulletin->GPC['userid']);
		if (!$info)
		{
			print_stop_message('invalid_user_specified');
		}

		if ($vbulletin->GPC['transfer_groups'])
		{
			// fetch groupmember info for groups that the deleted user has ownership of
			$sgmember_query = $vbulletin->db->query("
				SELECT socialgroup.groupid AS sgroupid, sgmember.*
				FROM " . TABLE_PREFIX . "socialgroup AS socialgroup
				LEFT JOIN " . TABLE_PREFIX . "socialgroupmember AS sgmember
				ON sgmember.groupid = socialgroup.groupid
				AND sgmember.userid = " . $vbulletin->userinfo['userid'] . "
				WHERE creatoruserid = " . $vbulletin->GPC['userid'] . "
			");

			if ($vbulletin->db->num_rows($sgmember_query))
			{
				while ($sgmember = $vbulletin->db->fetch_array($sgmember_query))
				{
					// ensure the current user is a full member of the group
					if ($sgmember['type'] != 'member')
					{
						$socialgroupmemberdm = datamanager_init('SocialGroupMember', $vbulletin);

						if ($sgmember['userid'])
						{
							$socialgroupmemberdm->set_existing($sgmember);
						}

						$socialgroupmemberdm->set('userid', $vbulletin->userinfo['userid']);
						$socialgroupmemberdm->set('groupid', $sgmember['sgroupid']);
						$socialgroupmemberdm->set('dateline', TIMENOW);
						$socialgroupmemberdm->set('type', 'member');

						$socialgroupmemberdm->save();
					}
				}
				$vbulletin->db->free_result($sgmember_query);

				// transfer all of the groups to the current user
				$vbulletin->db->query_write("
					UPDATE " . TABLE_PREFIX . "socialgroup
					SET creatoruserid = " . $vbulletin->userinfo['userid'] . ",
						transferowner = 0
					WHERE creatoruserid = " . $vbulletin->GPC['userid'] . "
				");
			}
		}

		$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_CP);
		$userdm->set_existing($info);
		$userdm->delete();
		unset($userdm);

		define('CP_REDIRECT', 'user.php?do=modify');
		print_stop_message('deleted_user_successfully');
	}
}

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit' OR $_REQUEST['do'] == 'add')
{
	$OUTERTABLEWIDTH = '95%';
	$INNERTABLEWIDTH = '100%';

	require_once(DIR . '/includes/functions_misc.php');

	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT
	));

	if ($vbulletin->GPC['userid'])
	{
		$user = $db->query_first("
			SELECT user.*, avatar.avatarpath, customavatar.dateline AS avatardateline, customavatar.width AS avatarwidth, customavatar.height AS avatarheight,
			NOT ISNULL(customavatar.userid) AS hascustomavatar, usertextfield.signature,
			customprofilepic.width AS profilepicwidth, customprofilepic.height AS profilepicheight,
			customprofilepic.dateline AS profilepicdateline, usergroup.adminpermissions,
			NOT ISNULL(customprofilepic.userid) AS hasprofilepic,
			NOT ISNULL(sigpic.userid) AS hassigpic,
			sigpic.width AS sigpicwidth, sigpic.height AS sigpicheight,
			sigpic.userid AS profilepic, sigpic.dateline AS sigpicdateline,
			usercsscache.cachedcss
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid)
			LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "customprofilepic AS customprofilepic ON(customprofilepic.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "sigpic AS sigpic ON(sigpic.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON(usergroup.usergroupid = user.usergroupid)
			LEFT JOIN " . TABLE_PREFIX . "usercsscache AS usercsscache ON (user.userid = usercsscache.userid)
			WHERE user.userid = " . $vbulletin->GPC['userid']
		);

		if (!$user)
		{
			print_stop_message('invalid_user_specified');
		}

		$user = array_merge($user, convert_bits_to_array($user['options'], $vbulletin->bf_misc_useroptions));
		$user = array_merge($user, convert_bits_to_array($user['adminoptions'], $vbulletin->bf_misc_adminoptions));

		if ($user['coppauser'] == 1)
		{
			echo "<p align=\"center\"><b>$vbphrase[this_is_a_coppa_user_do_not_change_to_registered]</b></p>\n";
		}

		if ($user['usergroupid'] == 3)
		{
			print_form_header('../register', 'emailcode', 0, 0);
			construct_hidden_code('email', $user['email']);
			print_submit_row($vbphrase['email_activation_codes'], 0);
		}

		// make array for quick links menu
		$quicklinks = array(
			"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=editaccess&u=" . $vbulletin->GPC['userid']
				=> $vbphrase['edit_forum_permissions_access_masks'],
			"resources.php?" . $vbulletin->session->vars['sessionurl'] . "do=viewuser&u=" . $vbulletin->GPC['userid']
				=> $vbphrase['view_forum_permissions'],
			"mailto:$user[email]"
				=> $vbphrase['send_email_to_user']
		);

		if ($user['usergroupid'] == 3)
		{
			$quicklinks[
				"../register.php?" . $vbulletin->session->vars['sessionurl'] . "do=requestemail&email=" . urlencode(unhtmlspecialchars($user['email'])) . '&amp;url=' . urlencode($vbulletin->options['bburl'] . '/' . $vbulletin->config['Misc']['admincpdir'] . '/user.php?do=edit&u=' . $vbulletin->GPC['userid'])
			] = $vbphrase['email_activation_codes'];
		}

		require_once(DIR . '/includes/class_paid_subscription.php');
		$subobj = new vB_PaidSubscription($vbulletin);
		$subobj->cache_user_subscriptions();
		if (!empty($subobj->subscriptioncache))
		{
			$quicklinks[
				"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=adjust&amp;userid=" . $vbulletin->GPC['userid']
			] = $vbphrase['add_paid_subscription'];
		}


		$quicklinks = array_merge(
			$quicklinks,
			array(
			"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=emailpassword&amp;u=" . $vbulletin->GPC['userid'] . "&amp;email=" . urlencode(unhtmlspecialchars($user['email']))
				=> $vbphrase['email_password_reminder_to_user'],
			"../private.php?" . $vbulletin->session->vars['sessionurl'] . "do=newpm&amp;u=" . $vbulletin->GPC['userid']
				=> $vbphrase['send_private_message_to_user'],
			"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=pmfolderstats&amp;u=" . $vbulletin->GPC['userid']
				=> $vbphrase['private_message_statistics'],
			"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=removepms&amp;u=" . $vbulletin->GPC['userid']
				=> $vbphrase['delete_all_users_private_messages'],
			"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=removesentpms&amp;u=" . $vbulletin->GPC['userid']
				=> $vbphrase['delete_private_messages_sent_by_user'],
			"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=removesentvms&amp;u=" . $vbulletin->GPC['userid']
				=> $vbphrase['delete_visitor_messages_sent_by_user'],
			"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=removesubs&amp;u=" . $vbulletin->GPC['userid']
				=> $vbphrase['delete_subscriptions'],
			"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=doips&amp;u=" . $vbulletin->GPC['userid'] . "&amp;hash=" . CP_SESSIONHASH
				=> $vbphrase['view_ip_addresses'],
			"../member.php?" . $vbulletin->session->vars['sessionurl'] . "u=" .$user['userid']
				=> $vbphrase['view_profile'],
			"../search.php?" . $vbulletin->session->vars['sessionurl'] . "do=finduser&amp;u=" . $vbulletin->GPC['userid']. "&amp;contenttype=vBForum_Post&amp;showposts=1"
				=> $vbphrase['find_posts_by_user'],
			"admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist&amp;startstamp=1&amp;endstamp= " . TIMENOW . "&amp;infractionlevelid=-1&amp;u=" . $vbulletin->GPC['userid']
				=> $vbphrase['view_infractions'],
			'../' . $vbulletin->config['Misc']['modcpdir'] . '/banning.php?' . $vbulletin->session->vars['sessionurl'] . "do=banuser&amp;u=" . $vbulletin->GPC['userid']
				=> $vbphrase['ban_user'],
			"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=remove&u=" . $vbulletin->GPC['userid']
				=> $vbphrase['delete_user'],
			"socialgroups.php?" . $vbulletin->session->vars['sessionurl'] . "do=groupsby&u=" . $vbulletin->GPC['userid']
				=> $vbphrase['view_social_groups_created_by_user'],
			)
		);

		if (intval($user['adminpermissions']) & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'] AND in_array($vbulletin->userinfo['userid'], preg_split('#\s*,\s*#s', $vbulletin->config['SpecialUsers']['superadministrators'], -1, PREG_SPLIT_NO_EMPTY)))
		{
			$quicklinks["adminpermissions.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&u=" . $vbulletin->GPC['userid']] = $vbphrase['edit_administrator_permissions'];
		}

		$userfield = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "userfield WHERE userid =" .  $vbulletin->GPC['userid']);

	}
	else
	{
		$regoption = array();
		if ($vbulletin->bf_misc_regoptions['subscribe_none'] & $vbulletin->options['defaultregoptions'])
		{
			$regoption['autosubscribe'] = -1;
		}
		else if ($vbulletin->bf_misc_regoptions['subscribe_nonotify'] & $vbulletin->options['defaultregoptions'])
		{
			$regoption['autosubscribe'] = 0;
		}
		else if ($vbulletin->bf_misc_regoptions['subscribe_instant'] & $vbulletin->options['defaultregoptions'])
		{
			$regoption['autosubscribe'] = 1;
		}
		else if ($vbulletin->bf_misc_regoptions['subscribe_daily'] & $vbulletin->options['defaultregoptions'])
		{
			$regoption['autosubscribe'] = 2;
		}
		else
		{
			$regoption['autosubscribe'] = 3;
		}

		if ($vbulletin->bf_misc_regoptions['vbcode_none'] & $vbulletin->options['defaultregoptions'])
		{
			$regoption['showvbcode'] = 0;
		}
		else if ($vbulletin->bf_misc_regoptions['vbcode_standard'] & $vbulletin->options['defaultregoptions'])
		{
			$regoption['showvbcode'] = 1;
		}
		else
		{
			$regoption['showvbcode'] = 2;
		}

		if ($vbulletin->bf_misc_regoptions['thread_linear_oldest'] & $vbulletin->options['defaultregoptions'])
		{
			$regoption['threadedmode'] = 0;
			$regoption['postorder'] = 0;
		}
		else if ($vbulletin->bf_misc_regoptions['thread_linear_newest'] & $vbulletin->options['defaultregoptions'])
		{
			$regoption['threadedmode'] = 0;
			$regoption['postorder'] = 1;
		}
		else if ($vbulletin->bf_misc_regoptions['thread_threaded'] & $vbulletin->options['defaultregoptions'])
		{
			$regoption['threadedmode'] = 1;
			$regoption['postorder'] = 0;
		}
		else if ($vbulletin->bf_misc_regoptions['thread_hybrid'] & $vbulletin->options['defaultregoptions'])
		{
			$regoption['threadedmode'] = 2;
			$regoption['postorder'] = 0;
		}
		else
		{
			$regoption['threadedmode'] = 0;
			$regoption['postorder'] = 0;
		}

		$userfield = '';
		$user = array(
			'invisible'                 => $vbulletin->bf_misc_regoptions['invisiblemode'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'daysprune'                 => -1,
			'joindate'                  => TIMENOW,
			'lastactivity'              => TIMENOW,
			'lastpost'                  => 0,
			'adminemail'                => $vbulletin->bf_misc_regoptions['adminemail'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'showemail'                 => $vbulletin->bf_misc_regoptions['receiveemail'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'receivepm'                 => $vbulletin->bf_misc_regoptions['enablepm'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'receivepmbuddies'          => 0,
			'emailonpm'                 => $vbulletin->bf_misc_regoptions['emailonpm'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'pmpopup'                   => $vbulletin->bf_misc_regoptions['pmpopup'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'pmdefaultsavecopy'			=> $vbulletin->bf_misc_regoptions['pmdefaultsavecopy'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'vm_enable'                 => $vbulletin->bf_misc_regoptions['vm_enable'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'vm_contactonly'            => $vbulletin->bf_misc_regoptions['vm_contactonly'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'showvcard'                 => $vbulletin->bf_misc_regoptions['vcard'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'autosubscribe'             => $regoption['autosubscribe'],
			'showreputation'            => $vbulletin->bf_misc_regoptions['showreputation'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'reputation'                => $vbulletin->options['reputationdefault'],
			'showsignatures'            => $vbulletin->bf_misc_regoptions['signature'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'showavatars'               => $vbulletin->bf_misc_regoptions['avatar'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'showimages'                => $vbulletin->bf_misc_regoptions['image'] & $vbulletin->options['defaultregoptions'] ? 1 : 0,
			'postorder'                 => $regoption['postorder'],
			'threadedmode'              => $regoption['threadedmode'],
			'showvbcode'                => $regoption['showvbcode'],
			'usergroupid'               => 2,
			'timezoneoffset'            => $vbulletin->options['timeoffset'],
			'dstauto'                   => 1,
			'showusercss'               => 1,
			'receivefriendemailrequest' => 1
		);
	}

	// get threaded mode options
	if ($user['threadedmode'] == 1 OR $user['threadedmode'] == 2)
	{
		$threaddisplaymode = $user['threadedmode'];
	}
	else
	{
		if ($user['postorder'] == 0)
		{
			$threaddisplaymode = 0;
		}
		else
		{
			$threaddisplaymode = 3;
		}
	}
	$user['threadedmode'] = $threaddisplaymode;

	// make array for daysprune menu
	$pruneoptions = array(
		'0'   => '- ' . $vbphrase['use_forum_default'] . ' -',
		'1'   => $vbphrase['show_threads_from_last_day'],
		'2'   => construct_phrase($vbphrase['show_threads_from_last_x_days'], 2),
		'7'   => $vbphrase['show_threads_from_last_week'],
		'10'  => construct_phrase($vbphrase['show_threads_from_last_x_days'], 10),
		'14'  => construct_phrase($vbphrase['show_threads_from_last_x_weeks'], 2),
		'30'  => $vbphrase['show_threads_from_last_month'],
		'45'  => construct_phrase($vbphrase['show_threads_from_last_x_days'], 45),
		'60'  => construct_phrase($vbphrase['show_threads_from_last_x_months'], 2),
		'75'  => construct_phrase($vbphrase['show_threads_from_last_x_days'], 75),
		'100' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 100),
		'365' => $vbphrase['show_threads_from_last_year'],
		'-1'  => $vbphrase['show_all_threads']
	);
	if ($pruneoptions["$user[daysprune]"] == '')
	{
		$pruneoptions["$user[daysprune]"] = construct_phrase($vbphrase['show_threads_from_last_x_days'], $user['daysprune']);
	}

	($hook = vBulletinHook::fetch_hook('useradmin_edit_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['userid'])
	{
		// a little javascript for the options menus
		?>
		<script type="text/javascript">
		function pick_a_window(url)
		{
			var modcpurl = "<?php echo(fetch_js_safe_string($vbulletin->config['Misc']['modcpdir'])); ?>";

			if (url != '')
			{
				if (url.substr(0, 3) == '../' && url.substr(3, modcpurl.length) != modcpurl)
				{
					window.open(url);
				}
				else
				{
					window.location = url;
				}
			}
			return false;
		}
		</script>
		<?php
	}

	?>
	<script type="text/javascript" src="../clientscript/vbulletin_md5.js"></script>
	<script type="text/javascript">
	// Encode password in j/s to be consistent with frontend.
	function hash_password(currentpassword, currentpassword_md5)
	{
		var junk_output;
		if (currentpassword.value != '')
		{
			md5hash(currentpassword, currentpassword_md5, junk_output, 0);
		}
	}
	</script>
	<?php

	// start main table, fudge in the onsubmit command (a cheat, but it works).
	print_form_header('user', 'update', false, false, 'cpform', '90%', '" onsubmit="hash_password(password, password_md5)');
	?>
	<table cellpadding="0" cellspacing="0" border="0" width="<?php echo $OUTERTABLEWIDTH; ?>" align="center"><tr valign="top"><td>
	<table cellpadding="4" cellspacing="0" border="0" align="center" width="100%" class="tborder">
	<?php

	construct_hidden_code('password_md5', '');
	construct_hidden_code('userid', $vbulletin->GPC['userid']);
	construct_hidden_code('ousergroupid', $user['usergroupid']);
	construct_hidden_code('odisplaygroupid', $user['displaygroupid']);

	$haschangehistory = false;

	if ($vbulletin->GPC['userid'])
	{
		// QUICK LINKS SECTION
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['user'], $user['username'], $vbulletin->GPC['userid']));
		print_label_row($vbphrase['quick_user_links'], '<select name="quicklinks" onchange="javascript:pick_a_window(this.options[this.selectedIndex].value);" tabindex="1" class="bginput">' . construct_select_options($quicklinks) . '</select><input type="button" class="button" value="' . $vbphrase['go'] . '" onclick="javascript:pick_a_window(this.form.quicklinks.options[this.form.quicklinks.selectedIndex].value);" tabindex="2" />');
		print_table_break('', $INNERTABLEWIDTH);

		require_once(DIR . '/includes/class_userchangelog.php');

		$userchangelog = new vb_UserChangeLog($vbulletin);
		$userchangelog->set_execute(true);

		// get the user change list
		$userchange_list = $userchangelog->sql_select_by_userid($vbulletin->GPC['userid']);

		$haschangehistory = $db->num_rows($userchange_list) ? true : false;
	}

	// PROFILE SECTION
	unset($user['salt']);
	construct_hidden_code('olduser', sign_client_string(serialize($user))); //For consistent Edits

	print_table_header($vbphrase['profile'] . ($haschangehistory ? '<span class="smallfont">' . construct_link_code($vbphrase['view_change_history'], 'user.php?' . $vbulletin->session->vars['sessionurl'] . 'do=changehistory&amp;userid=' . $vbulletin->GPC['userid'])  . '</span>' : ''));
	print_input_row($vbphrase['username'], 'user[username]', $user['username'], 0);
	print_input_row($vbphrase['password'], 'password');
	print_input_row($vbphrase['email'], 'user[email]', $user['email']);
	print_select_row($vbphrase['language'] , 'user[languageid]', array('0' => $vbphrase['use_forum_default']) + fetch_language_titles_array('', 0), $user['languageid']);
	print_input_row($vbphrase['user_title'], 'user[usertitle]', $user['usertitle']);
	print_select_row($vbphrase['custom_user_title'], 'user[customtitle]', array(0 => $vbphrase['no'], 2 => $vbphrase['user_set'], 1 => $vbphrase['admin_set_html_allowed']), $user['customtitle']);
	print_input_row($vbphrase['personal_home_page'], 'user[homepage]', $user['homepage'], 0);

	print_time_row($vbphrase['birthday'], 'user[birthday]', $user['birthday'], 0, 1);
	print_select_row($vbphrase['privacy'], 'user[showbirthday]', array(
		0 => $vbphrase['hide_age_and_dob'],
		1 => $vbphrase['display_age'],
		3 => $vbphrase['display_day_and_month'],
		2 => $vbphrase['display_age_and_dob']
	), $user['showbirthday']);
	print_textarea_row($vbphrase['signature'], 'user[signature]', $user['signature'], 8, 45);
	print_input_row($vbphrase['icq_uin'], 'user[icq]', $user['icq'], 0);
	print_input_row($vbphrase['aim_screen_name'], 'user[aim]', $user['aim'], 0);
	print_input_row($vbphrase['yahoo_id'], 'user[yahoo]', $user['yahoo'], 0);
	print_input_row($vbphrase['msn_id'], 'user[msn]', $user['msn'], 0);
	print_input_row($vbphrase['skype_name'], 'user[skype]', $user['skype'], 0);
	print_yes_no_row($vbphrase['coppa_user'], 'options[coppauser]', $user['coppauser']);
	print_input_row($vbphrase['parent_email_address'], 'user[parentemail]', $user['parentemail'], 0);
	if ($user['referrerid'])
	{
		$referrername = $db->query_first("SELECT username FROM " . TABLE_PREFIX . "user WHERE userid = $user[referrerid]");
		$user['referrer'] = $referrername['username'];
	}
	print_input_row($vbphrase['referrer'], 'user[referrerid]', $user['referrer'], 0);
	print_input_row($vbphrase['ip_address'], 'user[ipaddress]', $user['ipaddress']);
	print_input_row($vbphrase['post_count'], 'user[posts]', $user['posts'], 0, 7);
	print_table_break('', $INNERTABLEWIDTH);

	// USER IMAGE SECTION
	print_table_header($vbphrase['image_options']);
	if ($user['avatarid'])
	{
		$avatarurl = resolve_cp_image_url($user['avatarpath']);
	}
	else
	{
		if ($user['hascustomavatar'])
		{
			if ($vbulletin->options['usefileavatar'])
			{
				$avatarurl = resolve_cp_image_url($vbulletin->options['avatarurl'] . "/avatar$user[userid]_$user[avatarrevision].gif");
			}
			else
			{
				$avatarurl = "../image.php?" . $vbulletin->session->vars['sessionurl'] . "u=$user[userid]&amp;dateline=$user[avatardateline]";
			}
			if ($user['avatarwidth'] AND $user['avatarheight'])
			{
				$avatarurl .= "\" width=\"$user[avatarwidth]\" height=\"$user[avatarheight]";
			}
		}
		else
		{
			$avatarurl = '../' . $vbulletin->options['cleargifurl'];
		}
	}
	if ($user['hasprofilepic'])
	{
		if ($vbulletin->options['usefileavatar'])
		{
			$profilepicurl = resolve_cp_image_url($vbulletin->options['profilepicurl'] . "/profilepic$user[userid]_$user[profilepicrevision].gif");
		}
		else
		{
			$profilepicurl = "../image.php?" . $vbulletin->session->vars['sessionurl'] . "u=$user[userid]&amp;type=profile&amp;dateline=$user[profilepicdateline]";
		}

		if ($user['profilepicwidth'] AND $user['profilepicheight'])
		{
			$profilepicurl .= "\" width=\"$user[profilepicwidth]\" height=\"$user[profilepicheight]";
		}
	}
	else
	{
		$profilepicurl = '../' . $vbulletin->options['cleargifurl'];
	}
	if ($user['hassigpic'])
	{
		if ($vbulletin->options['usefileavatar'])
		{
			$sigpicurl = resolve_cp_image_url($vbulletin->options['sigpicurl'] . "/sigpic$user[userid]_$user[sigpicrevision].gif");
		}
		else
		{
			$sigpicurl = "../image.php?" . $vbulletin->session->vars['sessionurl'] . "u=$user[userid]&amp;type=sigpic&amp;dateline=$user[sigpicdateline]";
		}

		if ($user['sigpicwidth'] AND $user['sigpicheight'])
		{
			$sigpicurl .= "\" width=\"$user[sigpicwidth]\" height=\"$user[sigpicheight]";
		}
	}
	else
	{
		$sigpicurl = '../' . $vbulletin->options['cleargifurl'];
	}

	print_label_row($vbphrase['avatar'] . '<input type="image" src="../' . $vbulletin->options['cleargifurl'] . '" alt="" />', '<img src="' . $avatarurl . '" alt="" align="top" /> &nbsp; <input type="submit" class="button" tabindex="1" name="modifyavatar" value="' . $vbphrase['change_avatar'] . '" />');
	print_label_row($vbphrase['profile_picture'] . '<input type="image" src="../' . $vbulletin->options['cleargifurl'] . '" alt="" />', '<img src="' . $profilepicurl . '" alt="" align="top" /> &nbsp; <input type="submit" class="button" tabindex="1" name="modifyprofilepic" value="' . $vbphrase['change_profile_picture'] . '" />');
	print_label_row($vbphrase['signature_picture'] . '<input type="image" src="../' . $vbulletin->options['cleargifurl'] . '" alt="" />', '<img src="' . $sigpicurl . '" alt="" align="top" /> &nbsp; <input type="submit" class="button" tabindex="1" name="modifysigpic" value="' . $vbphrase['change_signature_picture'] . '" />');
	print_table_break('', $INNERTABLEWIDTH);

	// USER CSS SECTION
	if ($user['cachedcss'])
	{
		print_table_header($vbphrase['profile_style_customizations']);
		print_description_row(
			'<input type="submit" class="button" tabindex="1" name="modifycss" value="' . $vbphrase['edit_profile_customizations'] . '" />',
			false, 2, '', 'center'
		);
		print_table_break('', $INNERTABLEWIDTH);
	}

	// PROFILE FIELDS SECTION
	$forms = array(
		0 => $vbphrase['edit_your_details'],
		1 => "$vbphrase[options]: $vbphrase[log_in] / $vbphrase[privacy]",
		2 => "$vbphrase[options]: $vbphrase[messaging] / $vbphrase[notification]",
		3 => "$vbphrase[options]: $vbphrase[thread_viewing]",
		4 => "$vbphrase[options]: $vbphrase[date] / $vbphrase[time]",
		5 => "$vbphrase[options]: $vbphrase[other]",
	);
	$currentform = -1;

	print_table_header($vbphrase['user_profile_fields']);

	$profilefields = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "profilefield AS profilefield
		LEFT JOIN " . TABLE_PREFIX . "profilefieldcategory AS profilefieldcategory ON
			(profilefield.profilefieldcategoryid = profilefieldcategory.profilefieldcategoryid)
		ORDER BY profilefield.form, profilefieldcategory.displayorder, profilefield.displayorder
	");
	while ($profilefield = $db->fetch_array($profilefields))
	{
		if ($profilefield['form'] != $currentform)
		{
			print_description_row(construct_phrase($vbphrase['fields_from_form_x'], $forms["$profilefield[form]"]), false, 2, 'optiontitle');
			$currentform = $profilefield['form'];
		}
		print_profilefield_row('userfield', $profilefield, $userfield, false);
		construct_hidden_code('userfield[field' . $profilefield['profilefieldid'] . '_set]', 1);
	}

	($hook = vBulletinHook::fetch_hook('useradmin_edit_column1')) ? eval($hook) : false;

	if ($vbulletin->options['cp_usereditcolumns'] == 2)
	{
		?>
		</table>
		</td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
		<table cellpadding="4" cellspacing="0" border="0" align="center" width="100%" class="tborder">
		<?php
	}
	else
	{
		print_table_break('', $INNERTABLEWIDTH);
	}

	// USERGROUP SECTION
	print_table_header($vbphrase['usergroup_options']);
	print_chooser_row($vbphrase['primary_usergroup'], 'user[usergroupid]', 'usergroup', $user['usergroupid']);
	if (!empty($user['membergroupids']))
	{
		$usergroupids = $user['usergroupid'] . (!empty($user['membergroupids']) ? ',' . $user['membergroupids'] : '');
		print_chooser_row($vbphrase['display_usergroup'], 'user[displaygroupid]', 'usergroup', iif($user['displaygroupid'] == 0, -1, $user['displaygroupid']), $vbphrase['default'], 0, "WHERE usergroupid IN ($usergroupids)");
	}
	$tempgroup = $user['usergroupid'];
	$user['usergroupid'] = 0;
	print_membergroup_row($vbphrase['additional_usergroups'], 'user[membergroupids]', 0, $user);
	print_table_break('', $INNERTABLEWIDTH);
	$user['usergroupid'] = $tempgroup;

	if ($banreason = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "userban WHERE userid = " . intval($user['userid'])))
	{
		print_table_header($vbphrase['banning'], 3);

		$row = array($vbphrase['ban_reason'], (!empty($banreason['reason']) ? $banreason['reason'] : $vbphrase['n_a']), construct_link_code($vbphrase['lift_ban'], "../" . $vbulletin->config['Misc']['modcpdir'] . "/banning.php?" . $vbulletin->session->vars['sessionurl'] . "do=liftban&amp;userid=" . $user['userid']));
		print_cells_row($row);

		print_table_break('', $INNERTABLEWIDTH);
	}

	if (!empty($subobj->subscriptioncache))
	{
		$subscribed = array();
		// fetch all active subscriptions the user is subscribed too
		$subs = $db->query_read("
			SELECT status, regdate, expirydate, subscriptionlogid, subscription.subscriptionid
			FROM " . TABLE_PREFIX . "subscriptionlog AS subscriptionlog
			INNER JOIN " . TABLE_PREFIX . "subscription AS subscription USING (subscriptionid)
			WHERE userid = " . intval($user['userid']) . "
			ORDER BY status DESC, regdate
			"
		);
		if ($db->num_rows($subs))
		{
			print_table_header($vbphrase['paid_subscriptions']);
			while ($sub = $db->fetch_array($subs))
			{
				$desc = "<div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\"><input type=\"submit\" class=\"button\" tabindex=\"1\" name=\"subscriptionlogid[$sub[subscriptionlogid]]\" value=\"" . $vbphrase['edit'] . "\" />&nbsp;</div>";

				$joindate = vbdate($vbulletin->options['dateformat'], $sub['regdate'], false);
				$enddate = vbdate($vbulletin->options['dateformat'], $sub['expirydate'], false);
				if ($sub['status'])
				{
					$title = '<strong>' . $vbphrase['sub' . $sub['subscriptionid'] . '_title'] . '</strong>';
					$desc .= '<strong>' . construct_phrase($vbphrase['x_to_y'], $joindate, $enddate) . '</strong>';
				}
				else
				{
					$title = $vbphrase['sub' . $sub['subscriptionid'] . '_title'];
					$desc .= construct_phrase($vbphrase['x_to_y'], $joindate, $enddate);
				}

				print_label_row($title, $desc);
			}
			print_table_break('',$INNERTABLEWIDTH);
		}
	}

	// REPUTATION SECTION
	require_once(DIR . '/includes/functions_reputation.php');

	if ($user['userid'])
	{
		$perms = fetch_permissions(0, $user['userid'], $user);
	}
	else
	{
		$perms = array();
	}
	$score = fetch_reppower($user, $perms);

	print_table_header($vbphrase['reputation']);
	print_yes_no_row($vbphrase['display_reputation'], 'options[showreputation]', $user['showreputation']);
	print_input_row($vbphrase['reputation_level'], 'user[reputation]', $user['reputation']);
	print_label_row($vbphrase['current_reputation_power'], $score, '', 'top', 'reputationpower');
	print_table_break('',$INNERTABLEWIDTH);

	// INFRACTIONS section
	print_table_header($vbphrase['infractions'] . '<span class="smallfont">' . construct_link_code($vbphrase['view'], "admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist&amp;startstamp=1&amp;endstamp= " . TIMENOW . "&amp;infractionlevelid=-1&amp;u= " . $vbulletin->GPC['userid']) . '</span>');
	print_input_row($vbphrase['warnings'], 'user[warnings]', $user['warnings'], true, 5);
	print_input_row($vbphrase['infractions'], 'user[infractions]', $user['infractions'], true, 5);
	print_input_row($vbphrase['infraction_points'], 'user[ipoints]', $user['ipoints'], true, 5);
	if (!empty($user['infractiongroupids']))
	{
		$infractiongroups = explode(',', $user['infractiongroupids']);
		$groups = array();
		foreach($infractiongroups AS $groupid)
		{
			if (!empty($vbulletin->usergroupcache["$groupid"]['title']))
			{
				$groups[] = $vbulletin->usergroupcache["$groupid"]['title'];
			}
		}
		if (!empty($groups))
		{
			print_label_row($vbphrase['infraction_groups'], implode('<br />', $groups));
		}
		if (!empty($user['infractiongroupid']) AND $usertitle = $vbulletin->usergroupcache["$user[infractiongroupid]"]['usertitle'])
		{
			print_label_row($vbphrase['display_group'], 	$usertitle);
		}
	}
	print_table_break('',$INNERTABLEWIDTH);

	// BROWSING OPTIONS SECTION
	print_table_header($vbphrase['browsing_options']);
	print_yes_no_row($vbphrase['receive_admin_emails'], 'options[adminemail]', $user['adminemail']);
	print_yes_no_row($vbphrase['receive_user_emails'], 'options[showemail]', $user['showemail']);
	print_yes_no_row($vbphrase['invisible_mode'], 'options[invisible]', $user['invisible']);
	print_yes_no_row($vbphrase['allow_vcard_download'], 'options[showvcard]', $user['showvcard']);
	print_yes_no_row($vbphrase['receive_private_messages'], 'options[receivepm]', $user['receivepm']);
	print_yes_no_row($vbphrase['pm_from_contacts_only'], 'options[receivepmbuddies]', $user['receivepmbuddies']);
	print_yes_no_row($vbphrase['send_notification_email_when_a_private_message_is_received'], 'options[emailonpm]', $user['emailonpm']);
	print_yes_no_row($vbphrase['pop_up_notification_box_when_a_private_message_is_received'], 'user[pmpopup]', $user['pmpopup']);
	print_yes_no_row($vbphrase['acp_save_pm_copy_default'], 'options[pmdefaultsavecopy]', $user['pmdefaultsavecopy']);
	print_yes_no_row($vbphrase['enable_visitor_messaging'], 'options[vm_enable]', $user['vm_enable']);
	print_yes_no_row($vbphrase['limit_vm_to_contacts_only'], 'options[vm_contactonly]', $user['vm_contactonly']);
	print_yes_no_row($vbphrase['display_signatures'], 'options[showsignatures]', $user['showsignatures']);
	print_yes_no_row($vbphrase['display_avatars'], 'options[showavatars]', $user['showavatars']);
	print_yes_no_row($vbphrase['display_images'], 'options[showimages]', $user['showimages']);
	print_yes_no_row($vbphrase['show_others_custom_profile_styles'], 'options[showusercss]', $user['showusercss']);
	print_yes_no_row($vbphrase['receieve_friend_request_notification'], 'options[receivefriendemailrequest]', $user['receivefriendemailrequest']);

	print_radio_row($vbphrase['auto_subscription_mode'], 'user[autosubscribe]', array(
		-1 => $vbphrase['subscribe_choice_none'],
		0  => $vbphrase['subscribe_choice_0'],
		1  => $vbphrase['subscribe_choice_1'],
		2  => $vbphrase['subscribe_choice_2'],
		3  => $vbphrase['subscribe_choice_3'],
	), $user['autosubscribe'], 'smallfont');


	print_radio_row($vbphrase['thread_display_mode'], 'user[threadedmode]', array(
		0 => "$vbphrase[linear] - $vbphrase[oldest_first]",
		3 => "$vbphrase[linear] - $vbphrase[newest_first]",
		2 => $vbphrase['hybrid'],
		1 => $vbphrase['threaded']
	), $user['threadedmode'], 'smallfont');

	print_radio_row($vbphrase['message_editor_interface'], 'user[showvbcode]', array(
		0 => $vbphrase['do_not_show_editor_toolbar'],
		1 => $vbphrase['show_standard_editor_toolbar'],
		2 => $vbphrase['show_enhanced_editor_toolbar']
	), $user['showvbcode'], 'smallfont');

	construct_style_chooser($vbphrase['style'], 'user[styleid]', $user['styleid']);
	print_table_break('', $INNERTABLEWIDTH);

	// ADMIN OVERRIDE OPTIONS SECTION
	print_table_header($vbphrase['admin_override_options']);
	foreach ($vbulletin->bf_misc_adminoptions AS $field => $value)
	{
		print_yes_no_row($vbphrase['keep_' . $field], 'adminoptions[' . $field . ']', $user["$field"]);
	}
	print_table_break('', $INNERTABLEWIDTH);

	// TIME FIELDS SECTION
	print_table_header($vbphrase['time_options']);
	print_select_row($vbphrase['timezone'], 'user[timezoneoffset]', fetch_timezones_array(), $user['timezoneoffset']);
	print_yes_no_row($vbphrase['automatically_detect_dst_settings'], 'options[dstauto]', $user['dstauto']);
	print_yes_no_row($vbphrase['dst_currently_in_effect'], 'options[dstonoff]', $user['dstonoff']);
	print_select_row($vbphrase['default_view_age'], 'user[daysprune]', $pruneoptions, $user['daysprune']);
	print_time_row($vbphrase['join_date'], 'user[joindate]', $user['joindate']);
	print_time_row($vbphrase['last_activity'], 'user[lastactivity]', $user['lastactivity']);
	print_time_row($vbphrase['last_post'], 'user[lastpost]', $user['lastpost']);
	print_table_break('', $INNERTABLEWIDTH);

	// EXTERNAL CONNECTIONS SECTION
	print_table_header($vbphrase['external_connections']);
	print_label_row($vbphrase['facebook_connected'], (!empty($user['fbuserid']) ? $vbphrase['yes'] : $vbphrase['no']), '', 'top', 'facebookconnect');
//	print_table_break('', $INNERTABLEWIDTH);

	($hook = vBulletinHook::fetch_hook('useradmin_edit_column2')) ? eval($hook) : false;

	?>
	</table>
	</td>
	</tr>
	<?php

	print_table_break('', $OUTERTABLEWIDTH);
	$tableadded = 1;
	print_submit_row($vbphrase['save']);

}

// ###################### Start do update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'userid'            => TYPE_UINT,
		'password'          => TYPE_STR,
		'password_md5'      => TYPE_STR,
		'user'              => TYPE_ARRAY,
		'options'           => TYPE_ARRAY_BOOL,
		'adminoptions'      => TYPE_ARRAY_BOOL,
		'userfield'         => TYPE_ARRAY,
		'modifyavatar'      => TYPE_NOCLEAN,
		'modifyprofilepic'  => TYPE_NOCLEAN,
		'modifysigpic'      => TYPE_NOCLEAN,
		'modifycss'         => TYPE_NOCLEAN,
		'subscriptionlogid' => TYPE_ARRAY_KEYS_INT,
		'olduser'           => TYPE_BINARY
	));

	// check for 'undeletable' users
	if (is_unalterable_user($vbulletin->GPC['userid']))
	{
		print_stop_message('user_is_protected_from_alteration_by_undeletableusers_var');
	}

	// init data manager
	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_CP);
	$userdata->adminoverride = true;

	// set existing info if this is an update
	if ($vbulletin->GPC['userid'])
	{
		$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
		if (!$userinfo)
		{
			print_stop_message('invalid_user_specified');
		}
		$userinfo['posts'] = intval($vbulletin->GPC['user']['posts']);
		$userdata->set_existing($userinfo);
	}

	$olduser = @unserialize(verify_client_string($vbulletin->GPC['olduser']));

	// user options
	foreach ($vbulletin->GPC['options'] AS $key => $val)
	{
		if (!$vbulletin->GPC['userid'] OR $olduser["$key"] != $val)
		{
			$userdata->set_bitfield('options', $key, $val);
		}
	}

	foreach($vbulletin->GPC['adminoptions'] AS $key => $val)
	{
		$userdata->set_bitfield('adminoptions', $key, $val);
	}

	$displaygroupid = ($vbulletin->GPC['user']['displaygroupid'] <= 0) ? $vbulletin->GPC['user']['usergroupid'] : $vbulletin->GPC['user']['displaygroupid'];
	// custom user title
	$userdata->set_usertitle(
		$vbulletin->GPC['user']['usertitle'],
		$vbulletin->GPC['user']['customtitle'] ? false : true,
		$vbulletin->usergroupcache["$displaygroupid"],
		true,
		$vbulletin->GPC['user']['customtitle'] == 1 ? true : false
	);
	unset($vbulletin->GPC['user']['usertitle'], $vbulletin->GPC['user']['customtitle']);

	// user fields
	foreach ($vbulletin->GPC['user'] AS $key => $val)
	{
		if (!$vbulletin->GPC['userid'] OR $olduser["$key"] != $val)
		{
			$userdata->set($key, $val);
		}
	}

	// password
	$vbulletin->GPC['password'] = ($vbulletin->GPC['password_md5'] ? $vbulletin->GPC['password_md5'] : $vbulletin->GPC['password']);

	if (!empty($vbulletin->GPC['password']))
	{
		$userdata->set('password', $vbulletin->GPC['password']);
	}
	else if (!$vbulletin->GPC['userid'])
	{
		print_stop_message('invalid_password_specified');
	}

	if (empty($vbulletin->GPC['user']['membergroupids']))
	{
		$userdata->set('membergroupids', '');
	}

	// custom profile fields
	$userdata->set_userfields($vbulletin->GPC['userfield'], false, 'admin');

	($hook = vBulletinHook::fetch_hook('useradmin_update_save')) ? eval($hook) : false;

	// save data
	$userid = $userdata->save();
	if ($vbulletin->GPC['userid'])
	{
		$userid = $vbulletin->GPC['userid'];
	}

	// #############################################################################
	// now do the redirect

	if ($vbulletin->GPC['modifyavatar'])
	{
		define('CP_REDIRECT', "usertools.php?do=avatar&amp;u=$userid");
	}
	else if ($vbulletin->GPC['modifyprofilepic'])
	{
		define('CP_REDIRECT', "usertools.php?do=profilepic&amp;u=$userid");
	}
	else if ($vbulletin->GPC['modifysigpic'])
	{
		define('CP_REDIRECT', "usertools.php?do=sigpic&amp;u=$userid");
	}
	else if ($vbulletin->GPC['subscriptionlogid'])
	{
		define('CP_REDIRECT', "subscriptions.php?do=adjust&amp;subscriptionlogid=" . array_pop($vbulletin->GPC['subscriptionlogid']));
	}
	else if ($vbulletin->GPC['modifycss'])
	{
		define('CP_REDIRECT', "usertools.php?do=usercss&amp;u=$userid");
	}
	else
	{
		$handled = false;
		($hook = vBulletinHook::fetch_hook('useradmin_update_choose')) ? eval($hook) : false;

		if (!$handled)
		{
			define('CP_REDIRECT', "user.php?do=modify&amp;u=$userid" . ($userdata->insertedadmin ? '&insertedadmin=1' : ''));
		}
	}

	print_stop_message('saved_user_x_successfully', $user['username']);
}

// ###################### Start Edit Access #######################
if ($_REQUEST['do'] == 'editaccess')
{
	if (!can_administer('canadminpermissions'))
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_INT
	));

	$user = $db->query_first("SELECT username, options FROM " . TABLE_PREFIX . "user WHERE userid = " . $vbulletin->GPC['userid']);

	$accesslist = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "access WHERE userid = " . $vbulletin->GPC['userid']);

	//echo '<h1>$db->numrows($accesslist) = ' . $db->num_rows($accesslist) . '<br />user.hasaccessmask = ' . ($user['options'] & $vbulletin->bf_misc_useroptions['hasaccessmask'] ? 'yes' : 'no') . '</h1>';

	while ($access = $db->fetch_array($accesslist))
	{
		$accessarray[$access['forumid']] = $access;
	}

	print_form_header('user', 'updateaccess');
	construct_hidden_code('userid', $vbulletin->GPC['userid']);

	print_table_header($vbphrase['edit_access_masks'] . ": <span class=\"normal\">$user[username]</span>", 2, 0);
	print_description_row($vbphrase['here_you_may_edit_forum_access_on_a_user_by_user_basis']);
	print_cells_row(array($vbphrase['forum'], $vbphrase['allow_access_to_forum']), 0, 'thead', -2);
	print_label_row('&nbsp;', '
		<input type="button" value="' . $vbphrase['all_yes'] . '" onclick="js_check_all_option(this.form, 1);" class="button" />
		<input type="button" value=" ' . $vbphrase['all_no'] . ' " onclick="js_check_all_option(this.form, 0);" class="button" />
		<input type="button" value="' . $vbphrase['all_default'] .'" onclick="js_check_all_option(this.form, -1);" class="button" />
	');

	//require_once(DIR . '/includes/functions_databuild.php');
	//cache_forums();
	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		if (is_array($accessarray["$forum[forumid]"]))
		{
			if ($accessarray["$forum[forumid]"]['accessmask'] == 0)
			{
				$sel = 0;
			}
			else if ($accessarray["$forum[forumid]"]['accessmask'] == 1)
			{
				$sel = 1;
			}
			else
			{
				$sel = -1;
			}
		}
		else
		{
			$sel = -1;
		}
		print_yes_no_other_row(construct_depth_mark($forum['depth'], '- - ') . " $forum[title]", "accessupdate[$forum[forumid]]", $vbphrase['default'], $sel);
	}
	print_submit_row();
}

// ###################### Start Update Access #######################
if ($_POST['do'] == 'updateaccess')
{
	if (!can_administer('canadminpermissions'))
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'userid'	      => TYPE_INT,
		'accessupdate' => TYPE_ARRAY_INT,
	));

	$user = fetch_userinfo($vbulletin->GPC['userid']);

	if (!$user)
	{
		print_stop_message('invalid_user_specified');
	}

	// delete all old access masks
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "access WHERE userid = " . $vbulletin->GPC['userid']);

	// build SQL for new access masks
	$insert_mask_sql = array();

	foreach ($vbulletin->GPC['accessupdate'] AS $forumid => $val)
	{
		$forumid = intval($forumid);
		if ($val >= 0)
		{
			$insert_mask_sql[] = '(' . $vbulletin->GPC['userid'] . ", $forumid, $val)";
		}
	}
	if (!empty($insert_mask_sql))
	{
		/*insert query*/
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "access
				(userid, forumid, accessmask)
			VALUES
				" . implode(",\n\t", $insert_mask_sql)
		);
	}

	$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
	$userdm->set_existing($user);
	$userdm->set_bitfield('options', 'hasaccessmask', (sizeof($insert_mask_sql) ? true : false));
	$userdm->save();
	unset($userdm);

	cache_permissions($user);

	$noforums = array();
	foreach ($user['forumpermissions'] AS $forumid => $perm)
	{
		if ($perm == 0)
		{
			$noforums[] = $forumid;
		}
	}

	if (!empty($noforums))
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "subscribeforum
			WHERE userid = " . $vbulletin->GPC['userid'] . " AND
				forumid IN(" . implode(',', $noforums) . ")
		");
	}

	require_once(DIR . '/includes/functions_databuild.php');
	update_subscriptions(array('userids' => array($user['userid'])));

	define('CP_REDIRECT', "user.php?do=edit&amp;u=" . $vbulletin->GPC['userid']);
	print_stop_message('saved_access_masks_successfully');

}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'        => TYPE_INT,
		'insertedadmin' => TYPE_INT
	));

	if ($vbulletin->GPC['userid'])
	{
		$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
		if (!$userinfo)
		{
			print_stop_message('invalid_user_specified');
		}
		print_form_header('user', 'edit', 0, 1, 'reviewform');
		print_table_header($userinfo['username'], 2, 0, '', 'center', 0);
		construct_hidden_code('userid', $vbulletin->GPC['userid']);
		print_description_row(
			construct_link_code($vbphrase['view_profile'], "user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=" . $vbulletin->GPC['userid']) .
			iif($vbulletin->GPC['insertedadmin'], '<br />' . construct_link_code('<span style="color:red;"><strong>' . $vbphrase['update_or_add_administration_permissions'] . '</strong></span>', "adminpermissions.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=" . $vbulletin->GPC['userid']))
		);
		print_table_footer();
	}

	print_form_header('', '');
	print_table_header($vbphrase['quick_search']);
	print_description_row("
		<ul>
			<li><a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=find\">" . $vbphrase['show_all_users'] . "</a></li>
			<li><a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=find&amp;orderby=posts&amp;direction=DESC&amp;limitnumber=30\">" . $vbphrase['list_top_posters'] . "</a></li>
			<li><a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=find&amp;user[lastactivityafter]=" . (TIMENOW - 86400) . "&amp;orderby=lastactivity&amp;direction=DESC\">" . $vbphrase['list_visitors_in_the_last_24_hours'] . "</a></li>
			<li><a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=find&amp;orderby=joindate&direction=DESC&amp;limitnumber=30\">" . $vbphrase['list_new_registrations'] . "</a></li>
			<li><a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=moderate\">" . $vbphrase['list_users_awaiting_moderation'] . "</a></li>
			<li><a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=find&amp;user[coppauser]=1\">" . $vbphrase['show_all_coppa_users'] . "</a></li>
		</ul>
	");
	print_table_footer();

	print_form_header('user', 'find');
	print_table_header($vbphrase['advanced_search']);
	print_description_row($vbphrase['if_you_leave_a_field_blank_it_will_be_ignored']);
	print_description_row('<img src="../' . $vbulletin->options['cleargifurl'] . '" alt="" width="1" height="2" />', 0, 2, 'thead');
	print_user_search_rows();
	print_table_break();

	print_table_header($vbphrase['display_options']);
	print_yes_no_row($vbphrase['display_username'], 'display[username]', 1);
	print_yes_no_row($vbphrase['display_options'], 'display[options]', 1);
	print_yes_no_row($vbphrase['display_usergroup'], 'display[usergroup]', 0);
	print_yes_no_row($vbphrase['display_email'], 'display[email]', 1);
	print_yes_no_row($vbphrase['display_parent_email_address'], 'display[parentemail]', 0);
	print_yes_no_row($vbphrase['display_coppa_user'],'display[coppauser]', 0);
	print_yes_no_row($vbphrase['display_home_page'], 'display[homepage]', 0);
	print_yes_no_row($vbphrase['display_icq_uin'], 'display[icq]', 0);
	print_yes_no_row($vbphrase['display_aim_screen_name'], 'display[aim]', 0);
	print_yes_no_row($vbphrase['display_yahoo_id'], 'display[yahoo]', 0);
	print_yes_no_row($vbphrase['display_msn_id'], 'display[msn]', 0);
	print_yes_no_row($vbphrase['display_skype_name'], 'display[skype]', 0);
	print_yes_no_row($vbphrase['display_signature'], 'display[signature]', 0);
	print_yes_no_row($vbphrase['display_user_title'], 'display[usertitle]', 0);
	print_yes_no_row($vbphrase['display_join_date'], 'display[joindate]', 1);
	print_yes_no_row($vbphrase['display_last_activity'], 'display[lastactivity]', 1);
	print_yes_no_row($vbphrase['display_last_post'], 'display[lastpost]', 0);
	print_yes_no_row($vbphrase['display_post_count'], 'display[posts]', 1);
	print_yes_no_row($vbphrase['display_reputation'], 'display[reputation]', 0);
	print_yes_no_row($vbphrase['display_warnings'], 'display[warnings]', 0);
	print_yes_no_row($vbphrase['display_infractions'], 'display[infractions]', 0);
	print_yes_no_row($vbphrase['display_infraction_points'], 'display[ipoints]', 0);
	print_yes_no_row($vbphrase['display_ip_address'], 'display[ipaddress]', 0);
	print_yes_no_row($vbphrase['display_birthday'], 'display[birthday]', 0);
	print_description_row('<div align="' . vB_Template_Runtime::fetchStyleVar('right') .'"><input type="submit" class="button" value=" ' . $vbphrase['find'] . ' " tabindex="1" /></div>');

	print_table_header($vbphrase['user_profile_field_options']);
	$profilefields = $db->query_read("
		SELECT profilefieldid
		FROM " . TABLE_PREFIX . "profilefield AS profilefield
		LEFT JOIN " . TABLE_PREFIX . "profilefieldcategory AS profilefieldcategory ON
			(profilefield.profilefieldcategoryid = profilefieldcategory.profilefieldcategoryid)
		ORDER BY profilefield.form, profilefieldcategory.displayorder, profilefield.displayorder
	");
	while ($profilefield = $db->fetch_array($profilefields))
	{
		print_yes_no_row(construct_phrase($vbphrase['display_x'], htmlspecialchars_uni($vbphrase['field' . $profilefield['profilefieldid'] . '_title'])), "display[field$profilefield[profilefieldid]]", 0);
	}
	print_description_row('<div align="' . vB_Template_Runtime::fetchStyleVar('right') .'"><input type="submit" class="button" value=" ' . $vbphrase['find'] . ' " tabindex="1" /></div>');
	print_table_break();

	print_table_header($vbphrase['sorting_options']);
	print_label_row($vbphrase['order_by'], '
		<select name="orderby" tabindex="1" class="bginput">
		<option value="username" selected="selected">' . 	$vbphrase['username'] . '</option>
		<option value="email">' . $vbphrase['email'] . '</option>
		<option value="joindate">' . $vbphrase['join_date'] . '</option>
		<option value="lastactivity">' . $vbphrase['last_activity'] . '</option>
		<option value="lastpost">' . $vbphrase['last_post'] . '</option>
		<option value="posts">' . $vbphrase['post_count'] . '</option>
		<option value="birthday_search">' . $vbphrase['birthday'] . '</option>
		 <option value="reputation">' . $vbphrase['reputation'] . '</option>
		<option value="warnings">' . $vbphrase['warnings'] . '</option>
		<option value="infractions">' . $vbphrase['infractions'] . '</option>
		<option value="ipoints">' . $vbphrase['infraction_points'] . '</option>
		</select>
		<select name="direction" tabindex="1" class="bginput">
		<option value="">' . $vbphrase['ascending'] . '</option>
		<option value="DESC">' . $vbphrase['descending'] . '</option>
		</select>
	', '', 'top', 'orderby');
	print_input_row($vbphrase['starting_at_result'], 'limitstart', 1);
	print_input_row($vbphrase['maximum_results'], 'limitnumber', 50);

	print_submit_row($vbphrase['find'], $vbphrase['reset'], 2, '', '<input type="submit" class="button" value="' . $vbphrase['exact_match'] . '" tabindex="1" name="user[exact]" />');

}

// ###################### Start find #######################
if ($_REQUEST['do'] == 'find2' AND defined('DONEFIND'))
{
	// carries on from do == find at top of script

	$limitfinish = $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'];

	// display the column headings
	$header = array();
	if ($vbulletin->GPC['display']['username'])
	{
		$header[] = $vbphrase['username'];
	}
	if ($vbulletin->GPC['display']['usergroup'])
	{
		$header[] = $vbphrase['usergroup'];
	}
	if ($vbulletin->GPC['display']['email'])
	{
		$header[] = $vbphrase['email'];
	}
	if ($vbulletin->GPC['display']['parentemail'])
	{
		$header[] = $vbphrase['parent_email_address'];
	}
	if ($vbulletin->GPC['display']['coppauser'])
	{
		$header[] = $vbphrase['coppa_user'];
	}
	if ($vbulletin->GPC['display']['homepage'])
	{
		$header[] = $vbphrase['personal_home_page'];
	}
	if ($vbulletin->GPC['display']['icq'])
	{
		$header[] = $vbphrase['icq_uin'];
	}
	if ($vbulletin->GPC['display']['aim'])
	{
		$header[] = $vbphrase['aim_screen_name'];
	}
	if ($vbulletin->GPC['display']['yahoo'])
	{
		$header[] = $vbphrase['yahoo_id'];
	}
	if ($vbulletin->GPC['display']['msn'])
	{
		$header[] = $vbphrase['msn_id'];
	}
	if ($vbulletin->GPC['display']['skype'])
	{
		$header[] = $vbphrase['skype_name'];
	}
	if ($vbulletin->GPC['display']['signature'])
	{
		$header[] = $vbphrase['signature'];
	}
	if ($vbulletin->GPC['display']['usertitle'])
	{
		$header[] = $vbphrase['user_title'];
	}
	if ($vbulletin->GPC['display']['joindate'])
	{
		$header[] = $vbphrase['join_date'];
	}
	if ($vbulletin->GPC['display']['lastactivity'])
	{
		$header[] = $vbphrase['last_activity'];
	}
	if ($vbulletin->GPC['display']['lastpost'])
	{
		$header[] = $vbphrase['last_post'];
	}
	if ($vbulletin->GPC['display']['posts'])
	{
		$header[] = $vbphrase['post_count'];
	}
	if ($vbulletin->GPC['display']['reputation'])
	{
		$header[] = $vbphrase['reputation'];
	}
	if ($vbulletin->GPC['display']['warnings'])
	{
		$header[] = $vbphrase['warnings'];
	}
	if ($vbulletin->GPC['display']['infractions'])
	{
		$header[] = $vbphrase['infractions'];
	}
	if ($vbulletin->GPC['display']['ipoints'])
	{
		$header[] = $vbphrase['infraction_points'];
	}
	if ($vbulletin->GPC['display']['ipaddress'])
	{
		$header[] = $vbphrase['ip_address'];
	}
	if ($vbulletin->GPC['display']['birthday'])
	{
		$header[] = $vbphrase['birthday'];
	}

	$profilefields = $db->query_read("
		SELECT profilefieldid, type, data
		FROM " . TABLE_PREFIX . "profilefield AS profilefield
		LEFT JOIN " . TABLE_PREFIX . "profilefieldcategory AS profilefieldcategory ON
			(profilefield.profilefieldcategoryid = profilefieldcategory.profilefieldcategoryid)
		ORDER BY profilefield.form, profilefieldcategory.displayorder, profilefield.displayorder
	");
	while ($profilefield = $db->fetch_array($profilefields))
	{
		if ($vbulletin->GPC['display']["field$profilefield[profilefieldid]"])
		{
			$header[] = htmlspecialchars_uni($vbphrase['field' . $profilefield['profilefieldid'] . '_title']);
		}
	}

	if ($vbulletin->GPC['display']['options'])
	{
		$header[] = $vbphrase['options'];
	}

	// get number of cells for use in 'colspan=' attributes
	$colspan = sizeof($header);
	// a little javascript for the options menus
	?>
	<script type="text/javascript">
	function js_usergroup_jump(userinfo)
	{
		var value = eval("document.cpform.u" + userinfo + ".options[document.cpform.u" + userinfo + ".selectedIndex].value");
		if (value != "")
		{
			switch (value)
			{
				case 'edit': page = "edit&u=" + userinfo; break;
				case 'kill': page = "remove&u=" + userinfo; break;
				case 'access': page = "editaccess&u=" + userinfo; break;
				default: page = "emailpassword&u=" + userinfo + "&email=" + value; break;
			}
			window.location = "user.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=" + page;
		}
	}
	</script>
	<?php

	print_form_header('user', 'find');
	print_table_header(
		construct_phrase(
			$vbphrase['showing_users_x_to_y_of_z'],
			($vbulletin->GPC['limitstart'] + 1),
			iif($limitfinish > $countusers['users'], $countusers['users'], $limitfinish),
			$countusers['users']
		), $colspan);
	print_cells_row($header, 1);

	// cache usergroups if required to save querying every single one...
	if ($vbulletin->GPC['display']['usergroup'] AND !is_array($groupcache))
	{
		$groupcache = array();
		$groups = $db->query_read("SELECT usergroupid, title FROM " . TABLE_PREFIX . "usergroup");
		while ($group = $db->fetch_array($groups))
		{
			$groupcache["$group[usergroupid]"] = $group['title'];
		}
		$db->free_result($groups);
	}

	// now display the results
	while ($user = $db->fetch_array($users))
	{

		$cell = array();
		if ($vbulletin->GPC['display']['username'])
		{
			$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&u=$user[userid]\"><b>$user[username]</b></a>&nbsp;";
		}
		if ($vbulletin->GPC['display']['usergroup'])
		{
			$cell[] = $groupcache[$user['usergroupid']];
		}
		if ($vbulletin->GPC['display']['email'])
		{
			$cell[] = "<a href=\"mailto:$user[email]\">$user[email]</a>";
		}
		if ($vbulletin->GPC['display']['parentemail'])
		{
			$cell[] = "<a href=\"mailto:$user[parentemail]\">$user[parentemail]</a>";
		}
		if ($vbulletin->GPC['display']['coppauser'])
		{
			$cell[] = iif($user['coppauser'] == 1, $vbphrase['yes'], $vbphrase['no']);
		}
		if ($vbulletin->GPC['display']['homepage'])
		{
			$cell[] = iif($user['homepage'], "<a href=\"$user[homepage]\" target=\"_blank\">$user[homepage]</a>");
		}
		if ($vbulletin->GPC['display']['icq'])
		{
			$cell[] = $user['icq'];
		}
		if ($vbulletin->GPC['display']['aim'])
		{
			$cell[] = $user['aim'];
		}
		if ($vbulletin->GPC['display']['yahoo'])
		{
			$cell[] = $user['yahoo'];
		}
		if ($vbulletin->GPC['display']['msn'])
		{
			$cell[] = $user['msn'];
		}
		if ($vbulletin->GPC['display']['skype'])
		{
			$cell[] = $user['skype'];
		}
		if ($vbulletin->GPC['display']['signature'])
		{
			$cell[] = nl2br(htmlspecialchars_uni($user['signature']));
		}
		if ($vbulletin->GPC['display']['usertitle'])
		{
			$cell[] = $user['usertitle'];
		}
		if ($vbulletin->GPC['display']['joindate'])
		{
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['dateformat'], $user['joindate']) . '</span>';
		}
		if ($vbulletin->GPC['display']['lastactivity'])
		{
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['dateformat'], $user['lastactivity']) . '</span>';
		}
		if ($vbulletin->GPC['display']['lastpost'])
		{
			$cell[] = '<span class="smallfont">' . iif($user['lastpost'], vbdate($vbulletin->options['dateformat'], $user['lastpost']), '<i>' . $vbphrase['never'] . '</i>') . '</span>';
		}
		if ($vbulletin->GPC['display']['posts'])
		{
			$cell[] = vb_number_format($user['posts']);
		}
		if ($vbulletin->GPC['display']['reputation'])
		{
			$cell[] = vb_number_format($user['reputation']);
		}
		if ($vbulletin->GPC['display']['warnings'])
		{
			$cell[] = vb_number_format($user['warnings']);
		}
		if ($vbulletin->GPC['display']['infractions'])
		{
			$cell[] = vb_number_format($user['infractions']);
		}
		if ($vbulletin->GPC['display']['ipoints'])
		{
			$cell[] = vb_number_format($user['ipoints']);
		}
		if ($vbulletin->GPC['display']['ipaddress'])
		{
			$cell[] = iif(!empty($user['ipaddress']), "$user[ipaddress] (" . @gethostbyaddr($user['ipaddress']) . ')', '&nbsp;');
		}
		if ($vbulletin->GPC['display']['birthday'])
		{
			$cell[] = $user['birthday_search'];
		}
		$db->data_seek($profilefields, 0);
		while ($profilefield = $db->fetch_array($profilefields))
		{
			$profilefieldname = 'field' . $profilefield['profilefieldid'];
			if ($vbulletin->GPC['display']["field$profilefield[profilefieldid]"])
			{
				$varname = 'field' . $profilefield['profilefieldid'];
				if ($profilefield['type'] == 'checkbox' OR $profilefield['type'] == 'select_multiple')
				{
					$output = '';
					$data = unserialize($profilefield['data']);
					foreach ($data AS $index => $value)
					{
						if ($user["$profilefieldname"] & pow(2, $index))
						{
							if (!empty($output))
							{
								$output .= '<b>,</b> ';
							}
							$output .= $value;
						}
					}
					$cell[] = $output;
				}
				else
				{
					$cell[] = $user["$varname"];
				}
			}
		}
		if ($vbulletin->GPC['display']['options'])
		{
			$cell[] = "\n\t<select name=\"u$user[userid]\" onchange=\"js_usergroup_jump($user[userid]);\" class=\"bginput\">
			<option value=\"edit\">$vbphrase[view] / " . $vbphrase['edit_user'] . "</option>"
			. iif(!empty($user['email']), "<option value=\"" . unhtmlspecialchars($user[email]) . "\">" . $vbphrase['send_password_to_user'] . "</option>") . "
			<option value=\"access\">" . $vbphrase['edit_access_masks'] . "</option>
			<option value=\"kill\">" . $vbphrase['delete_user'] . "</option>\n\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_usergroup_jump($user[userid]);\" />\n\t";
		}
		print_cells_row($cell);
	}

	construct_hidden_code('serializeduser', sign_client_string(serialize($vbulletin->GPC['user'])));
	construct_hidden_code('serializedprofile', sign_client_string(serialize($vbulletin->GPC['profile'])));
	construct_hidden_code('serializeddisplay', sign_client_string(serialize($vbulletin->GPC['display'])));
	construct_hidden_code('limitnumber', $vbulletin->GPC['limitnumber']);
	construct_hidden_code('orderby', $vbulletin->GPC['orderby']);
	construct_hidden_code('direction', $vbulletin->GPC['direction']);

	if ($vbulletin->GPC['limitstart'] == 0 AND $countusers['users'] > $vbulletin->GPC['limitnumber'])
	{
		construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
		print_submit_row($vbphrase['next_page'], 0, $colspan);
	}
	else if ($limitfinish < $countusers['users'])
	{
		construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
		print_submit_row($vbphrase['next_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
	}
	else if ($vbulletin->GPC['limitstart'] > 0 AND $limitfinish >= $countusers['users'])
	{
		print_submit_row($vbphrase['first_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
	}
	else
	{
		print_table_footer();
	}
}

// ###################### Start moderate + coppa #######################
if ($_REQUEST['do'] == 'moderate')
{

	$users = $db->query_read("
		SELECT userid, username, email, ipaddress
		FROM " . TABLE_PREFIX . "user
		WHERE usergroupid = 4
		ORDER BY username
	");
	if ($db->num_rows($users) == 0)
	{
		print_stop_message('no_matches_found');
	}
	else
	{
		?>
		<script type="text/javascript">
		function js_check_radio(value)
		{
			for (var i = 0; i < document.cpform.elements.length; i++)
			{
				var e = document.cpform.elements[i];
				if (e.type == 'radio' && e.name.substring(0, 8) == 'validate')
				{
					if (e.value == value)
					{
						e.checked = true;
					}
					else
					{
						e.checked = false;
					}
				}
			}
		}
		</script>
		<?php
		print_form_header('user', 'domoderate');
		print_table_header($vbphrase['users_awaiting_moderation'], 4);
		print_cells_row(array(
			$vbphrase['username'],
			$vbphrase['email'],
			$vbphrase['ip_address'],
			"<input type=\"button\" class=\"button\" value=\"" . $vbphrase['accept_all'] . "\" onclick=\"js_check_radio(1)\" />
			<input type=\"button\" class=\"button\" value=\"" . $vbphrase['delete_all'] . "\" onclick=\"js_check_radio(-1)\" />
			<input type=\"button\" class=\"button\" value=\"" . $vbphrase['ignore_all'] . "\" onclick=\"js_check_radio(0)\" />"
		), 0, 'thead', -3);
		while ($user = $db->fetch_array($users))
		{
			$cell = array();
			$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$user[userid]\" target=\"_blank\"><b>$user[username]</b></a>";
			$cell[] = "<a href=\"mailto:$user[email]\">$user[email]</a>";
			$cell[] = "<a href=\"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=doips&amp;depth=2&amp;ipaddress=$user[ipaddress]&amp;hash=" . CP_SESSIONHASH . "\" target=\"_blank\">$user[ipaddress]</a>";
			$cell[] = "
				<label for=\"v_$user[userid]\"><input type=\"radio\" name=\"validate[$user[userid]]\" value=\"1\" id=\"v_$user[userid]\" tabindex=\"1\" />$vbphrase[accept]</label>
				<label for=\"d_$user[userid]\"><input type=\"radio\" name=\"validate[$user[userid]]\" value=\"-1\" id=\"d_$user[userid]\" tabindex=\"1\" />$vbphrase[delete]</label>
				<label for=\"i_$user[userid]\"><input type=\"radio\" name=\"validate[$user[userid]]\" value=\"0\" id=\"i_$user[userid]\" tabindex=\"1\" checked=\"checked\" />$vbphrase[ignore]</label>
			";
			print_cells_row($cell, 0, '', -4);
		}

		require_once(DIR . '/includes/functions_misc.php');
		$template = fetch_phrase('validated', 'emailbody', 'email_');

		print_table_break();
		print_table_header($vbphrase['email_options']);
		print_yes_no_row($vbphrase['send_email_to_accepted_users'], 'send_validated', 1);
		print_yes_no_row($vbphrase['send_email_to_deleted_users'], 'send_deleted', 1);
		print_description_row($vbphrase['email_will_be_sent_in_user_specified_language']);

		print_table_break();
		print_submit_row($vbphrase['continue']);
	}
}

// ###################### Start do moderate and coppa #######################
if ($_POST['do'] == 'domoderate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'send_validated' => TYPE_INT,
		'send_deleted'	  => TYPE_INT,
		'validate'       => TYPE_ARRAY_INT,
	));

	if (empty($vbulletin->GPC['validate']))
	{
		print_stop_message('please_complete_required_fields');
	}
	else
	{
		$evalemail_validated = array();
		$evalemail_deleted = array();

		require_once(DIR . '/includes/functions_misc.php');

		if ($vbulletin->options['welcomepm'])
		{
			if ($fromuser = fetch_userinfo($vbulletin->options['welcomepm']))
			{
				cache_permissions($fromuser, false);
			}
		}
		foreach($vbulletin->GPC['validate'] AS $userid => $status)
		{
			$userid = intval($userid);
			$user = $db->query_first("
				SELECT *
				FROM " . TABLE_PREFIX . "user
				WHERE userid = $userid
			");
			if (!$user)
			{
				// use was likely deleted
				continue;
			}
			$username = unhtmlspecialchars($user['username']);

			$chosenlanguage = iif($user['languageid'] < 1, intval($vbulletin->options['languageid']), intval($user['languageid']));

			if ($status == 1)
			{ // validated
				// init user data manager
				$displaygroupid = ($user['displaygroupid'] > 0 AND $user['displaygroupid'] != $user['usergroupid']) ? $user['displaygroupid'] : 2;

				$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_CP);
				$userdata->set_existing($user);
				$userdata->set('usergroupid', 2);
				$userdata->set_usertitle(
					$user['customtitle'] ? $user['usertitle'] : '',
					false,
					$vbulletin->usergroupcache["$displaygroupid"],
					($vbulletin->usergroupcache['2']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusecustomtitle']) ? true : false,
					false
				);
				$userdata->save();

				if ($vbulletin->GPC['send_validated'])
				{
					if (!isset($evalemail_validated["$user[languageid]"]))
					{
						//note that we pass the "all languages" flag as true all the time because if the function does
						//caching internally and is not smart enough to check if the language requested the second time
						//was cached on the first pass -- so we make sure that we load and cache all language version
						//in case the second user has a different language from the first
						$text_message = fetch_phrase('moderation_validated', 'emailbody', '', true, true, $chosenlanguage, true);
						$text_subject = fetch_phrase('moderation_validated', 'emailsubject', '', true, true, $chosenlanguage);

						$text_message = construct_phrase($text_message,
							create_full_url(fetch_seo_url('forumhome|nosession', array()), true));

						$evalemail_validated["$user[languageid]"] = '
							$message = "' . $text_message . '";
							$subject = "' . $text_subject . '";
						';
					}
					eval($evalemail_validated["$user[languageid]"]);
					vbmail($user['email'], $subject, $message, true);
				}

				if ($vbulletin->options['welcomepm'] AND $fromuser AND !$user['posts'])
				{
					if (!isset($evalpm_validated["$user[languageid]"]))
					{
						//note that we pass the "all languages" flag as true all the time because if the function does
						//caching internally and is not smart enough to check if the language requested the second time
						//was cached on the first pass -- so we make sure that we load and cache all language version
						//in case the second user has a different language from the first
						$text_message = fetch_phrase('welcomepm', 'emailbody', '', true, true, $chosenlanguage);
						$text_subject = fetch_phrase('welcomepm', 'emailsubject', '', true, true, $chosenlanguage);


						$evalpm_validated["$user[languageid]"] = '
							$message = "' . $text_message . '";
							$subject = "' . $text_subject . '";
						';
					}
					eval($evalpm_validated["$user[languageid]"]);

					// create the DM to do error checking and insert the new PM
					$pmdm =& datamanager_init('PM', $vbulletin, ERRTYPE_SILENT);
					$pmdm->set('fromuserid', $fromuser['userid']);
					$pmdm->set('fromusername', $fromuser['username']);
					$pmdm->set_info('receipt', false);
					$pmdm->set_info('savecopy', false);
					$pmdm->set('title', $subject);
					$pmdm->set('message', $message);
					$pmdm->set_recipients($username, $fromuser['permissions']);
					$pmdm->set('dateline', TIMENOW);
					$pmdm->set('allowsmilie', true);

					($hook = vBulletinHook::fetch_hook('private_insertpm_process')) ? eval($hook) : false;
					$pmdm->pre_save();
					if (empty($pmdm->errors))
					{
						$pmdm->save();
						($hook = vBulletinHook::fetch_hook('private_insertpm_complete')) ? eval($hook) : false;
					}
					unset($pmdm);
				}
			}
			else if ($status == -1)
			{ // deleted
				if ($vbulletin->GPC['send_deleted'])
				{
					if (!isset($evalemail_deleted["$user[languageid]"]))
					{
						//note that we pass the "all languages" flag as true all the time because if the function does
						//caching internally and is not smart enough to check if the language requested the second time
						//was cached on the first pass -- so we make sure that we load and cache all language version
						//in case the second user has a different language from the first
						$text_message = fetch_phrase('moderation_deleted', 'emailbody', '', true, true, $chosenlanguage);
						$text_subject = fetch_phrase('moderation_deleted', 'emailsubject', '', true, true, $chosenlanguage);

						$evalemail_deleted["$user[languageid]"] = '
							$message = "' . $text_message . '";
							$subject = "' . $text_subject . '";
						';
					}
					eval($evalemail_deleted["$user[languageid]"]);
					vbmail($user['email'], $subject, $message, true);
				}

				$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
				$userdm->set_existing($user);
				$userdm->delete();
				unset($userdm);
			} // else, do nothing
		}

		// rebuild stats so new user displays on forum home
		require_once(DIR . '/includes/functions_databuild.php');
		build_user_statistics();

		define('CP_REDIRECT', 'index.php?do=home');
		print_stop_message('user_accounts_validated');
	}
}

// ############################# do prune/move users (step 1) #########################
if ($_POST['do'] == 'dopruneusers')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'users'     => TYPE_ARRAY_INT,
		'dowhat'    => TYPE_STR,
		'movegroup' => TYPE_INT,
	));

	if (!empty($vbulletin->GPC['users']))
	{
		$userids = array();
		foreach ($vbulletin->GPC['users'] AS $key => $val)
		{
			$key = intval($key);
			if ($val == 1 AND $key != $vbulletin->userinfo['userid'])
			{
				$userids[] = $key;
			}
		}

		$userids = implode(',', $userids);

		if ($vbulletin->GPC['dowhat'] == 'delete')
		{
			$_REQUEST['do'] = 'dodeleteusers';
			build_adminutil_text('ids', $userids);
		}
		else if ($vbulletin->GPC['dowhat'] == 'move')
		{
			$group = $db->query_first("
				SELECT title
				FROM " . TABLE_PREFIX . "usergroup
				WHERE usergroupid = " . $vbulletin->GPC['movegroup']
			);
			echo '<p>' . $vbphrase['updating_users'] . "\n";
			vbflush();
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "user
				SET displaygroupid = IF(displaygroupid = usergroupid, 0, displaygroupid),
					usergroupid = " . $vbulletin->GPC['movegroup'] . "
				WHERE userid IN($userids)
			");
			echo $vbphrase['okay'] . '</p><p><b>' . $vbphrase['moved_users_successfully'] . '</b></p>';
			print_cp_redirect("user.php?" . $vbulletin->session->vars['sessionurl'] . "do=prune", 1);
		}
		else
		{
			$vbulletin->input->clean_array_gpc('r', array(
				'usergroupid' => TYPE_INT,
				'daysprune'   => TYPE_INT,
				'minposts'    => TYPE_INT,
				'joindate'    => TYPE_STR,
				'order'       => TYPE_STR
			));


			define('CP_REDIRECT', "user.php?do=pruneusers" .
				"&usergroupid=" . $vbulletin->GPC['usergroupid'] .
				"&daysprune=" .	$vbulletin->GPC['daysprune'] .
				"&minposts=" . $vbulletin->GPC['minposts'] .
				"&joindate=" . $vbulletin->GPC['joindate'] .
				"&order=" . $vbulletin->GPC['order']
			);

			print_stop_message('invalid_action_specified');
		}

		if (is_array($query))
		{
			foreach ($query AS $val)
			{
				echo "<pre>$val</pre>\n";
			}
		}
	}
	else
	{
		print_stop_message('please_complete_required_fields');
	}
}

// ############################# do prune users #########################
if ($_REQUEST['do'] == 'dodeleteusers')
{
	$userids = fetch_adminutil_text('ids');
	if (!$userids)
	{
		$userids = '0';
	}

	$users = $db->query_read("
		SELECT userid, username
		FROM " . TABLE_PREFIX . "user
		WHERE userid IN ($userids)
		LIMIT 0, 50
	");
	if ($db->num_rows($users))
	{
		while ($user = $db->fetch_array($users))
		{
			echo '<p>' . construct_phrase($vbphrase['deleting_user_x'], $user['username']) . "\n";
			vbflush();

			$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_CP);
			$userdm->set_existing($user);
			$userdm->delete();
			unset($userdm);

			echo '<b>' . $vbphrase['done'] . "</b></p>\n";
			vbflush();
		}

		print_cp_redirect("user.php?" . $vbulletin->session->vars['sessionurl'] . "do=dodeleteusers", 0);
		exit;
	}
	else
	{
		build_adminutil_text('ids');
		($hook = vBulletinHook::fetch_hook('useradmin_prune')) ? eval($hook) : false;

		define('CP_REDIRECT', "user.php?do=prune");
		print_stop_message('pruned_users_successfully');
	}
}

// ############################# start list users for pruning #########################
if ($_REQUEST['do'] == 'pruneusers')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'usergroupid' => TYPE_INT,
		'daysprune'   => TYPE_INT,
		'minposts'    => TYPE_INT,
		'joindate'    => TYPE_ARRAY_UINT,
		'order'       => TYPE_STR
	));

	unset($sqlconds);

	if ($vbulletin->GPC['usergroupid'] != -1)
	{
		$sqlconds = "WHERE user.usergroupid = " . $vbulletin->GPC['usergroupid'] . ' ';
	}
	if ($vbulletin->GPC['daysprune'])
	{
		$daysprune = intval(TIMENOW - $vbulletin->GPC['daysprune'] * 86400);
		if ($daysprune < 0)
		{ // if you have a negative number you're never going to find a value
			print_stop_message('no_users_matched_your_query');
		}
		$sqlconds .= iif(empty($sqlconds), 'WHERE', 'AND') . " lastactivity < $daysprune ";
	}
	if ($vbulletin->GPC['joindate']['month'] AND $vbulletin->GPC['joindate']['year'])
	{
		$joindateunix = mktime(0, 0, 0, $vbulletin->GPC['joindate']['month'], $vbulletin->GPC['joindate']['day'], $vbulletin->GPC['joindate']['year']);
		if ($joindateunix)
		{
			$sqlconds .= iif(empty($sqlconds), 'WHERE', 'AND') . " joindate < $joindateunix ";
		}
	}
	if ($vbulletin->GPC['minposts'])
	{
		$sqlconds .= iif(empty($sqlconds), 'WHERE', 'AND') . " posts < " . $vbulletin->GPC['minposts'] . ' ';
	}

	switch($vbulletin->GPC['order'])
	{
		case 'username':
			$orderby = 'ORDER BY username ASC';
			break;
		case 'email':
			$orderby = 'ORDER BY email ASC';
			break;
		case 'usergroup':
			$orderby = 'ORDER BY usergroup.title ASC';
			break;
		case 'posts':
			$orderby = 'ORDER BY posts DESC';
			break;
		case 'lastactivity':
			$orderby = 'ORDER BY lastactivity DESC';
			break;
		case 'joindate':
			$orderby = 'ORDER BY joindate DESC';
			break;
		default:
			$orderby = 'ORDER BY username ASC';
	}

	if (!empty($sqlconds))
	{
		$users = $db->query_read("
			SELECT DISTINCT user.userid, username, email, posts, lastactivity, joindate,
			user.usergroupid, moderator.moderatorid, usergroup.title
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "moderator AS moderator ON(moderator.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON(usergroup.usergroupid = user.usergroupid)
			$sqlconds
			GROUP BY user.userid $orderby
		");

		if ($numusers = $db->num_rows($users))
		{
			?>
			<script type="text/javascript">
			function js_alert_no_permission()
			{
				alert("<?php echo $vbphrase['you_may_not_delete_move_this_user']; ?>");
			}
			</script>
			<?php

			$groups = $db->query_read("
				SELECT usergroupid, title
				FROM " . TABLE_PREFIX . "usergroup
				WHERE usergroupid NOT IN(1,3,4,5,6)
				ORDER BY title
			");
			$groupslist = '';
			while ($group = $db->fetch_array($groups))
			{
				$groupslist .= "\t<option value=\"$group[usergroupid]\">$group[title]</option>\n";
			}

			print_form_header('user', 'dopruneusers');
			construct_hidden_code('usergroupid', $vbulletin->GPC['usergroupid']);
			construct_hidden_code('daysprune', $vbulletin->GPC['daysprune']);
			construct_hidden_code('minposts', $vbulletin->GPC['minposts']);
			construct_hidden_code('joindate[day]', $vbulletin->GPC['joindate']['day']);
			construct_hidden_code('joindate[month]', $vbulletin->GPC['joindate']['month']);
			construct_hidden_code('joindate[year]', $vbulletin->GPC['joindate']['year']);
			construct_hidden_code('order', $order);
			print_table_header(construct_phrase($vbphrase['showing_users_x_to_y_of_z'], 1, $numusers, $numusers), 7);
			print_cells_row(array(
				'Userid',
				$vbphrase['username'],
				$vbphrase['email'],
				$vbphrase['post_count'],
				$vbphrase['last_activity'],
				$vbphrase['join_date'],
				'<input type="checkbox" name="allbox" onclick="js_check_all(this.form)" title="' . $vbphrase['check_all'] . '" checked="checked" />'
			), 1);

			while ($user = $db->fetch_array($users))
			{
				$cell = array();
				$cell[] = $user['userid'];
				$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&u=$user[userid]\" target=\"_blank\">$user[username]</a><br /><span class=\"smallfont\">$user[title]" . ($user['moderatorid'] ? ", " . $vbphrase['moderator'] : "" ) . "</span>";
				$cell[] = "<a href=\"mailto:$user[email]\">$user[email]</a>";
				$cell[] = vb_number_format($user['posts']);
				$cell[] = vbdate($vbulletin->options['dateformat'], $user['lastactivity']);
				$cell[] = vbdate($vbulletin->options['dateformat'], $user['joindate']);
				if ($user['userid'] == $vbulletin->userinfo['userid'] OR $user['usergroupid'] == 6 OR $user['usergroupid'] == 5 OR $user['moderatorid'] OR is_unalterable_user($user['userid']))
				{
					$cell[] = '<input type="button" class="button" value=" ! " onclick="js_alert_no_permission()" />';
				}
				else
				{
					$cell[] = "<input type=\"checkbox\" name=\"users[$user[userid]]\" value=\"1\" checked=\"checked\" tabindex=\"1\" />";
				}
				print_cells_row($cell);
			}
			print_description_row('<center><span class="smallfont">
				<b>' . $vbphrase['action'] . ':
				<label for="dw_delete"><input type="radio" name="dowhat" value="delete" id="dw_delete" tabindex="1" />' . $vbphrase['delete'] . '</label>
				<label for="dw_move"><input type="radio" name="dowhat" value="move" id="dw_move" tabindex="1" />' . $vbphrase['move'] . '</label>
				<select name="movegroup" tabindex="1" class="bginput">' . $groupslist . '</select></b>
				</span></center>', 0, 7);
			print_submit_row($vbphrase['go'], $vbphrase['check_all'], 7);

			echo '<p>' . $vbphrase['this_action_is_not_reversible'] . '</p>';
		}
		else
		{
			define('CP_REDIRECT', "user.php?do=prune" .
				"&usergroupid=" . $vbulletin->GPC['usergroupid'] .
				"&daysprune=" . $vbulletin->GPC['daysprune'] .
				"&joindateunix=$joindateunix" .
				"&minposts=" . $vbulletin->GPC['minposts']
			);
			print_stop_message('no_users_matched_your_query');
		}
	}
	else
	{
		print_stop_message('please_complete_required_fields');
	}
}


// ############################# start prune users #########################
if ($_REQUEST['do'] == 'prune')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'usergroupid'  => TYPE_UINT,
		'daysprune'    => TYPE_INT,
		'joindateunix'	=> TYPE_INT,
		'minposts'     => TYPE_INT
	));

	print_form_header('user', 'pruneusers');
	print_table_header($vbphrase['user_moving_pruning_system']);
	print_description_row('<blockquote>' . $vbphrase['this_system_allows_you_to_mass_move_delete_users'] . '</blockquote>');
	print_chooser_row($vbphrase['usergroup'], 'usergroupid', 'usergroup', iif($vbulletin->GPC['usergroupid'], $vbulletin->GPC['usergroupid'], -1), $vbphrase['all_usergroups']);
	print_input_row($vbphrase['has_not_logged_on_for_xx_days'], 'daysprune', iif($vbulletin->GPC['daysprune'], $vbulletin->GPC['daysprune'], 365));
	print_time_row($vbphrase['join_date_is_before'], 'joindate', $vbulletin->GPC['joindateunix'], false, false, 'middle');
	print_input_row($vbphrase['posts_is_less_than'], 'minposts', iif($vbulletin->GPC['minposts'], $vbulletin->GPC['minposts'], '0'));
	print_label_row($vbphrase['order_by'], '<select name="order" tabindex="1" class="bginput">
		<option value="username">' . $vbphrase['username'] . '</option>
		<option value="email">' . $vbphrase['email'] . '</option>
		<option value="usergroup">' . $vbphrase['usergroup'] . '</option>
		<option value="posts">' . $vbphrase['post_count'] . '</option>
		<option value="lastactivity">' . $vbphrase['last_activity'] . '</option>
		<option value="joindate">' . $vbphrase['join_date'] . '</option>
	</select>', '', 'top', 'order');
	print_submit_row($vbphrase['find']);

}

// ############################# user change history #########################
if ($_REQUEST['do'] == 'changehistory')
{
	require_once(DIR . '/includes/class_userchangelog.php');
	require_once(DIR . '/includes/functions_misc.php');

	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT
	));

	if ($vbulletin->GPC['userid'])
	{
		// initalize the $user storage
		$users = false;

		// create the vb_UserChangeLog instance and set the execute flag (we want to do the query, not just to build)
		$userchangelog = new vb_UserChangeLog($vbulletin);
		$userchangelog->set_execute(true);

		// get the user change list
		$userchange_list = $userchangelog->sql_select_by_userid($vbulletin->GPC['userid']);

		if (!$userchange_list)
		{
			print_stop_message('invalid_user_specified');
		}

		if ($db->num_rows($userchange_list))
		{
			//start the printing
			$printed = array();
			print_table_start();
			print_column_style_code(array('width: 30%;', 'width: 35%;', 'width: 35%;'));

			// fetch the rows
			while ($userchange = $db->fetch_array($userchange_list))
			{
				if (!$printed['header'])
				{
					// print the table header
					print_table_header($vbphrase['view_change_history'] . ' <span class="normal"><a href="user.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit&amp;userid=' . $userchange['userid'] . '">' . $userchange['username'] . '</a>', 3);
					//print_cells_row(array('&nbsp;', $vbphrase['oldvalue'], $vbphrase['newvalue']), 1, false, -10);
					$printed['header'] = true;
				}

				// new change block, print a block header (empty line + header line)
				if ($printed['change_uniq'] != $userchange['change_uniq'])
				{
					//print_cells_row(array('&nbsp;', '&nbsp', '&nbsp'), 0, false, -10);
					$text = array();
					$ipaddress = $userchange['ipaddress'] ? htmlspecialchars_uni(long2ip($userchange['ipaddress'])) : '';
					$text[] = '<span class="normal" title="' . vbdate($vbulletin->options['timeformat'], $userchange['change_time']) . '">' . vbdate($vbulletin->options['dateformat'], $userchange['change_time']) . ';</span> ' . $userchange['admin_username'] . ($ipaddress ? " <span class=\"normal\" title=\"$vbphrase[ip_address]: $ipaddress\">($ipaddress)</span>" : '');
					$text[] = $vbphrase['old_value'];
					$text[] = $vbphrase['new_value'];
					print_cells_row($text, 1, false, -10);

					// actualize the block id
					$printed['change_uniq'] = $userchange['change_uniq'];
				}

				// get/find some names, depend on the field and the content
				switch ($userchange['fieldname'])
				{
					// get usergroup names from the cache
					case 'usergroupid':
					case 'membergroupids':
					{
						foreach (array('oldvalue', 'newvalue') as $fname)
						{
							$str = '';
							if ($ids = explode(',', $userchange[$fname]))
							{
								foreach ($ids as $id)
								{
									if ($vbulletin->usergroupcache["$id"]['title'])
									{
										$str .= ($vbulletin->usergroupcache["$id"]['title']).'<br/>';
									}
								}
							}
							$userchange["$fname"] = ($str ? $str : '-');
						}
						break;
					}
				}

				// sometimes we need translate the fieldname to show the phrases (database field and phrase have different name)
				$fieldnametrans = array('usergroupid' => 'primary_usergroup', 'membergroupids' => 'additional_usergroups');
				if ($fieldnametrans["$userchange[fieldname]"])
				{
					$userchange['fieldname'] = $fieldnametrans["$userchange[fieldname]"];
				}

				// print the change
				$text = array();
				$text[] = $vbphrase["$userchange[fieldname]"];
				$text[] = $userchange['oldvalue'];
				$text[] = $userchange['newvalue'];
				print_cells_row($text, 0, false, -10);
			}
			print_table_footer();
		}
		else
		{
			print_stop_message('no_userchange_history');
		}
	}
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 64477 $
|| ####################################################################
\*======================================================================*/
?>
