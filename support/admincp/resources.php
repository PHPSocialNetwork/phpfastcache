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
define('CVS_REVISION', '$RCSfile$ - $Revision: 73770 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cppermission');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_misc.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminforums'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'userid'      => TYPE_INT,
	'usergroupid' => TYPE_INT,
	'forumid'     => TYPE_INT,
));

// ############################# LOG ACTION ###############################
log_admin_action(iif($vbulletin->GPC['userid'], "user id = " . $vbulletin->GPC['userid'], iif($vbulletin->GPC['usergroupid'], "usergroup id = " . $vbulletin->GPC['usergroupid'], iif($vbulletin->GPC['forumid'], "forum id = " . $vbulletin->GPC['forumid']))));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['view_permissions']);

$perm_phrase = array(
	'canview'               => $vbphrase['can_view_forum'],
	'canviewthreads'        => $vbphrase['can_view_threads'],
	'canviewothers'         => $vbphrase['can_view_others_threads'],
	'cansearch'             => $vbphrase['can_search_forum'],
	'canemail'              => $vbphrase['can_use_email_to_friend'],
	'canpostnew'            => $vbphrase['can_post_threads'],
	'canreplyown'           => $vbphrase['can_reply_to_own_threads'],
	'canreplyothers'        => $vbphrase['can_reply_to_others_threads'],
	'caneditpost'           => $vbphrase['can_edit_own_posts'],
	'candeletepost'         => $vbphrase['can_delete_own_posts'],
	'candeletethread'       => $vbphrase['can_delete_own_threads'],
	'canopenclose'          => $vbphrase['can_open_close_own_threads'],
	'canmove'               => $vbphrase['can_move_own_threads'],
	'cangetattachment'      => $vbphrase['can_view_attachments'],
	'canseethumbnails'      => $vbphrase['can_see_thumbnails'],
	'canpostattachment'     => $vbphrase['can_post_attachments'],
	'canpostpoll'           => $vbphrase['can_post_polls'],
	'canvote'               => $vbphrase['can_vote_on_polls'],
	'canthreadrate'	        => $vbphrase['can_rate_threads'],
	'canseedelnotice'       => $vbphrase['can_see_deletion_notices'],
	'followforummoderation'	=> $vbphrase['follow_forum_moderation_rules'],
	'cantagown'             => $vbphrase['can_tag_own_threads'],
	'cantagothers'          => $vbphrase['can_tag_others_threads'],
	'candeletetagown'       => $vbphrase['can_delete_tags_own_threads'],
	'canattachmentcss'		=> $vbphrase['can_css_attachments'],
	'bypassdoublepost'		=> $vbphrase['bypass_double_post'],
	'canwrtmembers'			=> $vbphrase['can_wrt_members'],
);

//build a nice array with permission names
foreach ($vbulletin->bf_ugp_forumpermissions AS $key => $val)
{
	$bitfieldnames["$val"] = $perm_phrase["$key"];
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'index';
}

// ###################### Start index ########################
if ($_REQUEST['do'] == 'index')
{
	print_form_header('resources', 'view');
	print_table_header($vbphrase['view_forum_permissions']);
	print_forum_chooser($vbphrase['forum'], 'forumid', -1, "($vbphrase[forum])");
	print_chooser_row($vbphrase['usergroup'], 'usergroupid', 'usergroup', '', "($vbphrase[usergroup])");
	print_label_row(
		$vbphrase['forum_permissions'],
		'<label for="cb_checkall"><input type="checkbox" id="cb_checkall" name="allbox" onclick="js_check_all(this.form)" />' . $vbphrase['check_all'] . '</label>',
		'thead'
	);
	foreach ($vbulletin->bf_ugp_forumpermissions AS $field => $value)
	{
		print_checkbox_row($perm_phrase["$field"], "checkperm[$value]", false, $value);
	}
	print_submit_row($vbphrase['find']);

}

// ###################### Start viewing resources for forums or usergroups ########################
if ($_REQUEST['do'] == 'view')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'checkperm' => TYPE_ARRAY_INT,
	));

	if ($vbulletin->GPC['forumid'] == -1 AND $vbulletin->GPC['usergroupid'] == -1)
	{
		print_stop_message('you_must_pick_a_usergroup_or_forum_to_check_permissions');
	}
	if (empty($vbulletin->GPC['checkperm']))
	{
		$vbulletin->GPC['checkperm'][] = 1;
	}
	$fpermscache = array();
	$_PERMQUERY = "
	SELECT forumpermission.usergroupid, forumpermission.forumpermissions, forum.forumid, forum.title, FIND_IN_SET(forumpermission.forumid, forum.parentlist) AS ordercontrol
	FROM " . TABLE_PREFIX . "forum AS forum
	LEFT JOIN " . TABLE_PREFIX . "forumpermission AS forumpermission ON
	(FIND_IN_SET(forumpermission.forumid, forum.parentlist))
	ORDER BY ordercontrol DESC
	";
	$forumpermissions = $db->query_read($_PERMQUERY);
	while ($forumpermission = $db->fetch_array($forumpermissions))
	{
		$fpermscache["$forumpermission[forumid]"]["$forumpermission[usergroupid]"] = intval($forumpermission['forumpermissions']);
	}
	unset($forumpermission);
	$db->free_result($forumpermissions);

	$usergroups = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "usergroup" . iif($vbulletin->GPC['usergroupid'] > 0, " WHERE usergroupid = " . $vbulletin->GPC['usergroupid']));
	while ($usergroup = $db->fetch_array($usergroups))
	{
		$usergrouptitlecache["$usergroup[usergroupid]"] = $usergroup['title'];
		$vbulletin->usergroupcache["$usergroup[usergroupid]"] = $usergroup;
	}

	foreach($fpermscache AS $sforumid => $fpermissions)
	{
		if ($vbulletin->GPC['usergroupid'] == -1)
		{
			foreach ($vbulletin->usergroupcache AS $pusergroupid => $usergroup)
			{
				$perms["$sforumid"]["$pusergroupid"] = 0;
				if (isset($fpermissions["$pusergroupid"]))
				{
					$perms["$sforumid"]["$pusergroupid"] |= $fpermissions["$pusergroupid"];
				}
				else
				{
					$perms["$sforumid"]["$pusergroupid"] |= $vbulletin->usergroupcache["$pusergroupid"]['forumpermissions'];
				}
			}
		}
		else
		{
			$perms["$sforumid"]["{$vbulletin->GPC['usergroupid']}"] = 0;
			if (isset($fpermissions["{$vbulletin->GPC['usergroupid']}"]))
			{
				$perms["$sforumid"]["{$vbulletin->GPC['usergroupid']}"] |= $fpermissions["{$vbulletin->GPC['usergroupid']}"];
			}
			else
			{
				$perms["$sforumid"]["{$vbulletin->GPC['usergroupid']}"] |= $vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]['forumpermissions'];
			}
		}
	}
	//we now have a nice $perms array with the forumid as the index, lets look at the users original request
	//did they want all forums for a usergroup or all perms for a forum or just a specific one

	print_form_header('', '');
	if ($vbulletin->GPC['forumid'] == -1)
	{
		print_table_header($usergrouptitlecache["{$vbulletin->GPC['usergroupid']}"] . " <span class=\"normal\">(usergroupid: " . $vbulletin->GPC['usergroupid'] . ")</span>");
		foreach ($perms AS $sforumid => $usergroup)
		{
			print_table_header($vbulletin->forumcache["$sforumid"]['title'] . " <span class=\"normal\">(forumid: $sforumid)</span>");
			foreach ($vbulletin->GPC['checkperm'] AS $key => $val)
			{

				if (bitwise($usergroup["{$vbulletin->GPC['usergroupid']}"], $val))
				{
					print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['yes'] . '</b>');
				}
				else
				{
					print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['no'] . '</b>');
				}
			}
		}
	}
	else if ($vbulletin->GPC['usergroupid'] == -1)
	{
		ksort($perms["{$vbulletin->GPC['forumid']}"], SORT_NUMERIC);
		print_table_header($vbulletin->forumcache["{$vbulletin->GPC['forumid']}"]['title'] . " <span class=\"normal\">(forumid: " . $vbulletin->GPC['forumid'] . ")</span>");
		//forumid was set so show permissions for all usergroups on that forum
		foreach ($perms["{$vbulletin->GPC['forumid']}"] AS $_usergroupid => $usergroup)
		{
			print_table_header($usergrouptitlecache["$_usergroupid"] . " <span class=\"normal\">(usergroupid: $_usergroupid)</span>");
			foreach ($vbulletin->GPC['checkperm'] AS $key => $val)
			{
				if (bitwise($usergroup, $val))
				{
					print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['yes'] . '</b>');
				}
				else
				{
					print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['no'] . '</b>');
				}
			}
		}
	}
	else
	{
		print_table_header($usergrouptitlecache["{$vbulletin->GPC['usergroupid']}"] . ' / ' . $vbulletin->forumcache["{$vbulletin->GPC['forumid']}"]['title']);
		foreach ($vbulletin->GPC['checkperm'] AS $key => $val)
		{
			if (bitwise($perms["{$vbulletin->GPC['forumid']}"]["{$vbulletin->GPC['usergroupid']}"], $val))
			{
				print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['yes'] . '</b>');
			}
			else
			{
				print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['no'] . '</b>');
			}
		}
	}
	print_table_footer();
}

// ###################### Start viewing resources for specific user ########################
if ($_REQUEST['do'] == 'viewuser')
{
	$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
	if (!$userinfo)
	{
		print_stop_message('invalid_user_specified');
	}
	$perms = cache_permissions($userinfo);

	print_form_header('', '');
	print_table_header($userinfo['username'] . " <span class=\"normal\">(userid: $userinfo[userid])</span>");

	foreach ($userinfo['forumpermissions'] AS $forumid => $forumperms)
	{
		print_table_header($vbulletin->forumcache["$forumid"]['title'] . " <span class=\"normal\">(forumid: $forumid)</span>");
		foreach ($vbulletin->bf_ugp_forumpermissions AS $key => $val)
		{

			if (bitwise($userinfo['forumpermissions']["$forumid"], $val))
			{
				print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['yes'] . '</b>');
			}
			else
			{
				print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['no'] . '</b>');
			}
		}
	}
	print_table_footer();
}
print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 73770 $
|| ####################################################################
\*======================================================================*/
?>