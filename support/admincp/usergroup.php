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
define('CVS_REVISION', '$RCSfile$ - $Revision: 56530 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cppermission', 'cpuser', 'promotion', 'pm', 'cpusergroup');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_ranks.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminpermissions'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'usergroupid'       => TYPE_INT,
	'usergroupleaderid' => TYPE_INT,
));

log_admin_action(!empty($vbulletin->GPC['usergroupid']) ? "usergroup id = " . $vbulletin->GPC['usergroupid'] : (!empty($vbulletin->GPC['usergroupleaderid']) ? "leader id = " . $vbulletin->GPC['usergroupleaderid'] : ''));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['usergroup_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start getuserid #######################
function fetch_userid_from_username($username)
{
	global $vbulletin;
	if ($user = $vbulletin->db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = '" . $vbulletin->db->escape_string(trim($username)) . "'"))
	{
		return $user['userid'];
	}
	else
	{
		return false;
	}
}

// ###################### Start add / update #######################
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{

	$vbulletin->input->clean_array_gpc('r', array(
		'defaultgroupid' => TYPE_INT
	));

	require_once(DIR . '/includes/class_bitfield_builder.php');
	if (vB_Bitfield_Builder::build(false) !== false)
	{
		$myobj =& vB_Bitfield_Builder::init();
		if (sizeof($myobj->datastore_total['ugp']) != sizeof($vbulletin->bf_ugp))
		{
			$myobj->save($db);
			build_forum_permissions();
			define('CP_REDIRECT', $vbulletin->scriptpath);
			print_stop_message('rebuilt_bitfields_successfully');
		}
	}
	else
	{
		echo "<strong>error</strong>\n";
		print_r(vB_Bitfield_Builder::fetch_errors());
	}

	if ($_REQUEST['do'] == 'add')
	{
		// get a list of other usergroups to base this one off of
		print_form_header('usergroup', 'add');
		$groups = $db->query_read("SELECT usergroupid, title FROM " . TABLE_PREFIX . "usergroup ORDER BY title");
		$selectgroups = '';
		while ($group = $db->fetch_array($groups))
		{
			$selectgroups .= "<option value=\"$group[usergroupid]\" " . iif($group['usergroupid'] == $vbulletin->GPC['defaultgroupid'], 'selected="selected"') . ">$group[title]</option>\n";
		}
		print_description_row(construct_table_help_button('defaultgroupid') . '<b>' . $vbphrase['create_usergroup_based_off_of_usergroup'] . '</b> <select name="defaultgroupid" tabindex="1" class="bginput">' . $selectgroups . '</select> <input type="submit" class="button" value="' . $vbphrase['go'] . '" tabindex="1" />', 0, 2, 'tfoot', 'center');
		print_table_footer();
	}

	print_form_header('usergroup', 'update');
	print_column_style_code(array('width: 70%', 'width: 30%'));

	if ($_REQUEST['do'] == 'add')
	{
		if (!empty($vbulletin->GPC['defaultgroupid']))
		{
			// set defaults to this group's info
			$usergroup = $db->query_first("
				SELECT * FROM " . TABLE_PREFIX . "usergroup
				WHERE usergroupid = " . $vbulletin->GPC['defaultgroupid'] . "
			");

			$ug_bitfield = array();
			foreach($vbulletin->bf_ugp AS $permissiongroup => $fields)
			{
				$ug_bitfield["$permissiongroup"] = convert_bits_to_array($usergroup["$permissiongroup"], $fields);
			}
		}
		else
		{
			$ug_bitfield = array(
				'genericoptions' => array('showgroup' => 1, 'showeditedby' => 1, 'isnotbannedgroup' => 1),
				'forumpermissions' => array('canview' => 1, 'canviewothers' => 1, 'cangetattachment' => 1,
				'cansearch' => 1, 'canthreadrate' => 1, 'canpostattachment' => 1, 'canpostpoll' => 1, 'canvote' => 1, 'canviewthreads' => 1),
				'wolpermissions' => array('canwhosonline' => 1),
				'genericpermissions' => array('canviewmembers' => 1, 'canmodifyprofile' => 1, 'canseeprofilepic' => 1, 'canusesignature' => 1, 'cannegativerep' => 1, 'canuserep' => 1, 'cansearchft_nl' => 1)
			);
			// set default numeric permissions
			$usergroup = array(
				'pmquota' => 0, 'pmsendmax' => 5, 'attachlimit' => 1000000,
				'avatarmaxwidth' => 50, 'avatarmaxheight' => 50, 'avatarmaxsize' => 20000,
				'profilepicmaxwidth' => 100, 'profilepicmaxheight' => 100, 'profilepicmaxsize' => 25000, 'sigmaxsizebbcode' => 7
			);
		}

		$permgroups = $db->query_read("
			SELECT usergroup.usergroupid, title,
				(COUNT(forumpermission.forumpermissionid) + COUNT(calendarpermission.calendarpermissionid)) AS permcount
			FROM " . TABLE_PREFIX . "usergroup AS usergroup
			LEFT JOIN " . TABLE_PREFIX . "forumpermission AS forumpermission ON (usergroup.usergroupid = forumpermission.usergroupid)
			LEFT JOIN " . TABLE_PREFIX . "calendarpermission AS calendarpermission ON (usergroup.usergroupid = calendarpermission.usergroupid)
			GROUP BY usergroup.usergroupid
			HAVING permcount > 0
			ORDER BY title
		");
		$ugarr = array('-1' => '--- ' . $vbphrase['none'] . ' ---');
		while ($group = $db->fetch_array($permgroups))
		{
			$ugarr["$group[usergroupid]"] = $group['title'];
		}
		print_table_header($vbphrase['default_forum_permissions']);
		print_select_row($vbphrase['create_permissions_based_off_of_forum'], 'ugid_base', $ugarr, $vbulletin->GPC['defaultgroupid']);
		print_table_break();

		print_table_header($vbphrase['add_new_usergroup']);
	}
	else
	{
		$usergroup = $db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "usergroup
			WHERE usergroupid = " . $vbulletin->GPC['usergroupid'] . "
		");

		$ug_bitfield = array();
		foreach($vbulletin->bf_ugp AS $permissiongroup => $fields)
		{
			$ug_bitfield["$permissiongroup"] = convert_bits_to_array($usergroup["$permissiongroup"], $fields);
		}
		construct_hidden_code('usergroupid', $vbulletin->GPC['usergroupid']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['usergroup'],$usergroup[title], $usergroup[usergroupid]), 2, 0);
	}

	print_input_row($vbphrase['title'], 'usergroup[title]', $usergroup['title']);
	print_input_row($vbphrase['description'], 'usergroup[description]', $usergroup['description']);
	print_input_row($vbphrase['usergroup_user_title'], 'usergroup[usertitle]', $usergroup['usertitle'], true, 35, 100);
	print_label_row($vbphrase['username_markup'],
		'<span style="white-space:nowrap">
		<input size="15" type="text" class="bginput" name="usergroup[opentag]" value="' . htmlspecialchars_uni($usergroup['opentag']) . '" tabindex="1" />
		<input size="15" type="text" class="bginput" name="usergroup[closetag]" value="' . htmlspecialchars_uni($usergroup['closetag']) . '" tabindex="1" />
		</span>', '', 'top', 'htmltags');
	print_input_row($vbphrase['password_expiry'], 'usergroup[passwordexpires]', $usergroup['passwordexpires']);
	print_input_row($vbphrase['password_history'], 'usergroup[passwordhistory]', $usergroup['passwordhistory']);
	print_table_break();
	print_column_style_code(array('width: 70%', 'width: 30%'));

	if ($vbulletin->GPC['usergroupid'] > 7 OR $_REQUEST['do'] == 'add')
	{
		print_table_header($vbphrase['public_group_settings']);
		print_yes_no_row($vbphrase['public_joinable_custom_usergroup'], 'usergroup[ispublicgroup]', $usergroup['ispublicgroup']);
		print_yes_no_row($vbphrase['can_override_primary_group_title'], 'usergroup[canoverride]', $usergroup['canoverride']);
		print_table_break();
		print_column_style_code(array('width: 70%', 'width: 30%'));
	}

	($hook = vBulletinHook::fetch_hook('admin_usergroup_edit')) ? eval($hook) : false;

	foreach ($myobj->data['ugp'] AS $grouptitle => $perms)
	{
		foreach ($perms AS $permtitle => $permvalue)
		{
			if (empty($permvalue['group']))
			{
				continue;
			}
			$groupinfo["$permvalue[group]"]["$permtitle"] = array('phrase' => $permvalue['phrase'], 'value' => $permvalue['value'], 'parentgroup' => $grouptitle);
			if ($permvalue['intperm'])
			{
				$groupinfo["$permvalue[group]"]["$permtitle"]['intperm'] = true;
			}
			if (!empty($myobj->data['layout']["$permvalue[group]"]['ignoregroups']))
			{
				$groupinfo["$permvalue[group]"]['ignoregroups'] = $myobj->data['layout']["$permvalue[group]"]['ignoregroups'];
			}
			if (!empty($permvalue['ignoregroups']))
			{
				$groupinfo["$permvalue[group]"]["$permtitle"]['ignoregroups'] = $permvalue['ignoregroups'];
			}
			if (!empty($permvalue['options']))
			{
				$groupinfo["$permvalue[group]"]["$permtitle"]['options'] = $permvalue['options'];
			}
		}
	}

	foreach ($groupinfo AS $grouptitle => $group)
	{

		// This set of permissions is hidden from a specific group
		if (isset($group['ignoregroups']))
		{
			$ignoreids = explode(',', $group['ignoregroups']);
			if (in_array($vbulletin->GPC['usergroupid'], $ignoreids))
			{
				continue;
			}
			else
			{
				unset($group['ignoregroups']);
			}
		}

		print_table_header($vbphrase["$grouptitle"]);

		foreach ($group AS $permtitle => $permvalue)
		{
			// Permission is shown only if a particular option is enabled.
			if (isset($permvalue['options']) AND !$vbulletin->options["$permvalue[options]"])
			{
				continue;
			}

			// Permission is hidden from specific groups
			if (isset($permvalue['ignoregroups']))
			{
				$ignoreids = explode(',', $permvalue['ignoregroups']);
				if (in_array($vbulletin->GPC['usergroupid'], $ignoreids))
				{
					continue;
				}
			}

			if (isset($permvalue['intperm']))
			{
				$getval = $usergroup["$permtitle"];

				if (isset($permvalue['readonly']))
				{
					// This permission is readonly for certain usergroups
					$readonlyids = explode(',', $permvalue['readonly']);
					if (in_array($vbulletin->GPC['usergroupid'], $readonlyids))
					{
						$getval = ($permvalue['readonlyvalue']) ? $permvalue['readonlyvalue'] : $getval;

						print_label_row($vbphrase["$permvalue[phrase]"], $getval);
						construct_hidden_code($vbphrase["$permvalue[phrase]"], $getval);
						continue;
					}
				}

				print_input_row($vbphrase["$permvalue[phrase]"], "usergroup[$permtitle]", $getval, 1, 20);
			}
			else
			{
				$getval = $ug_bitfield["$permvalue[parentgroup]"]["$permtitle"];

				if (isset($permvalue['readonly']))
				{
					// This permission is readonly for certain usergroups
					$readonlyids = explode(',', $permvalue['readonly']);
					if (in_array($vbulletin->GPC['usergroupid'], $readonlyids))
					{
						if ($permvalue['readonlyvalue'] == 'true')
						{
							print_yes_row($vbphrase["$permvalue[phrase]"], "usergroup[$permvalue[parentgroup]][$permtitle]", $vbphrase['yes'], true);
						}
						else
						{
							print_yes_row($vbphrase["$permvalue[phrase]"], "usergroup[$permvalue[parentgroup]][$permtitle]", $vbphrase['no'], false);
						}
						continue;
					}
				}

				print_yes_no_row($vbphrase["$permvalue[phrase]"], "usergroup[$permvalue[parentgroup]][$permtitle]", $getval);
			}
		}
		print_table_break();
		print_column_style_code(array('width: 70%', 'width: 30%'));
	}

	print_submit_row(iif($_REQUEST['do'] == 'add', $vbphrase['save'], $vbphrase['update']));
}

// ###################### Start insert / update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usergroup' => TYPE_ARRAY,
		'ugid_base' => TYPE_INT,
	));

	// create bitfield values
	require_once(DIR . '/includes/functions_misc.php');
	foreach($vbulletin->bf_ugp AS $permissiongroup => $fields)
	{
		$vbulletin->GPC['usergroup']["$permissiongroup"] = convert_array_to_bits($vbulletin->GPC['usergroup']["$permissiongroup"], $fields, 1);
	}

	($hook = vBulletinHook::fetch_hook('admin_usergroup_save')) ? eval($hook) : false;

	if (!empty($vbulletin->GPC['usergroupid']))
	{
	// update
		if (!($vbulletin->GPC['usergroup']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
		{ // check that not removing last admin group
			$checkadmin = $db->query_first("
				SELECT COUNT(*) AS usergroups
				FROM " . TABLE_PREFIX . "usergroup
				WHERE (adminpermissions & " . $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'] . ") AND
					usergroupid <> " . $vbulletin->GPC['usergroupid'] . "
			");
			if ($vbulletin->GPC['usergroupid'] == 6)
			{ // stop them turning no control panel for usergroup 6, seems the most sensible thing
				print_stop_message('invalid_usergroup_specified');
			}
			if (!$checkadmin['usergroups'])
			{
				print_stop_message('cant_delete_last_admin_group');
			}
		}

		$db->query_write(fetch_query_sql($vbulletin->GPC['usergroup'], 'usergroup', "WHERE usergroupid=" . $vbulletin->GPC['usergroupid']));

		if (!($vbulletin->GPC['usergroup']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['caninvisible']))
		{
			if (!($vbulletin->GPC['usergroup']['genericoptions'] & $vbulletin->bf_ugp_genericoptions['allowmembergroups']))
			{
				// make the users in this group visible
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET options = (options & ~" . $vbulletin->bf_misc_useroptions['invisible'] . ")
					WHERE usergroupid = " . $vbulletin->GPC['usergroupid'] . "
				");
			}
			else
			{
				// find all groups allowed to be invisible - don't change people with those as secondary groups
				$invisible_groups = '';
				$invisible_sql = $db->query_read("
					SELECT usergroupid
					FROM " . TABLE_PREFIX . "usergroup
					WHERE genericpermissions & " . $vbulletin->bf_ugp_genericpermissions['caninvisible']
				);
				while ($invisible_group = $db->fetch_array($invisible_sql))
				{
					$invisible_groups .= "\nAND NOT FIND_IN_SET($invisible_group[usergroupid], membergroupids)";
				}

				$db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET options = (options & ~" . $vbulletin->bf_misc_useroptions['invisible'] . ")
					WHERE usergroupid = " . $vbulletin->GPC['usergroupid'] . "
						$invisible_groups
				");
			}
		}
		if ($vbulletin->GPC['usergroup']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
		{
			$ausers = $db->query_write("
				SELECT user.userid
				FROM " . TABLE_PREFIX . "user AS user
				LEFT JOIN " . TABLE_PREFIX . "administrator as administrator ON (user.userid = administrator.userid)
				WHERE administrator.userid IS NULL AND
					user.usergroupid = " . $vbulletin->GPC['usergroupid'] . "
			");
			while ($auser = $db->fetch_array($ausers))
			{
				$userids[] = $auser['userid'];
			}

			if (!empty($userids))
			{
				foreach ($userids AS $userid)
				{
					$admindm =& datamanager_init('Admin', $vbulletin, ERRTYPE_SILENT);
					$admindm->set('userid', $userid);
					$admindm->save();
					unset($admindm);
				}
			}
		}
		else if ($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
		{
			// lets find admin usergroupids
			$ausergroupids = array();
			$vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]['adminpermissions'] = $vbulletin->GPC['usergroup']['adminpermissions'];
			foreach ($vbulletin->usergroupcache AS $ausergroupid => $ausergroup)
			{
				if ($ausergroup['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
				{
					$ausergroupids[] = $ausergroupid;
				}
			}
			$ausergroupids = implode(',', $ausergroupids);
			$ausers = $db->query_read("
				SELECT userid FROM " . TABLE_PREFIX . "user
				WHERE usergroupid NOT IN ($ausergroupids)
					AND NOT FIND_IN_SET('$ausergroupids', membergroupids)
					AND (usergroupid = " . $vbulletin->GPC['usergroupid'] . "
					OR FIND_IN_SET('" . $vbulletin->GPC['usergroupid'] . "', membergroupids))
			");

			while ($auser = $db->fetch_array($ausers))
			{
				$userids[] = $auser['userid'];
			}

			if (!empty($userids))
			{
				foreach ($userids AS $userid)
				{
					$info = array('userid' => $userid);

					$admindm =& datamanager_init('Admin', $vbulletin, ERRTYPE_ARRAY);
					$admindm->set_existing($info);
					$admindm->delete();
					unset($admindm);
				}
			}
		}
	}
	else
	{
	// insert
		/*insert query*/
		$db->query_write(fetch_query_sql($vbulletin->GPC['usergroup'], 'usergroup'));
		$newugid = $db->insert_id();

		if ($vbulletin->GPC['ugid_base'] > 0)
		{
			$fperms = $db->query_read("
				SELECT * FROM " . TABLE_PREFIX . "forumpermission
				WHERE usergroupid = " . $vbulletin->GPC['ugid_base'] . "
			");
			while ($fperm = $db->fetch_array($fperms))
			{
				unset($fperm['forumpermissionid']);
				$fperm['usergroupid'] = $newugid;
				/*insert query*/
				$db->query_write(fetch_query_sql($fperm, 'forumpermission'));
			}

			$cperms = $db->query_read("
				SELECT * FROM " . TABLE_PREFIX . "calendarpermission
				WHERE usergroupid = " . $vbulletin->GPC['ugid_base'] . "
			");
			while ($cperm = $db->fetch_array($cperms))
			{
				unset($cperm['calendarpermissionid']);
				$cperm['usergroupid'] = $newugid;
				/*insert query*/
				$db->query_write(fetch_query_sql($cperm, 'calendarpermission'));
			}
		}

		$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "prefixpermission (usergroupid, prefixid)
			SELECT " . $newugid . ", prefixid FROM " . TABLE_PREFIX . "prefix
			WHERE options & " . $vbulletin->bf_misc_prefixoptions['deny_by_default']
		);
	}

	$markups = $db->query_read("
		SELECT usergroupid, opentag, closetag
		FROM " . TABLE_PREFIX . "usergroup
		WHERE opentag <> '' OR
		closetag <> ''
	");
	$usergroupmarkup = array();
	while ($markup = $db->fetch_array($markups))
	{
		$usergroupmarkup["{$markup['usergroupid']}"]['opentag'] = $markup['opentag'];
		$usergroupmarkup["{$markup['usergroupid']}"]['closetag'] = $markup['closetag'];
	}

	require_once(DIR . '/includes/functions_databuild.php');
	build_forum_permissions();
	build_birthdays();

	// could be changing sig perms -- this is unscientific, but empty the sig cache
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "sigparsed
	");

	($hook = vBulletinHook::fetch_hook('admin_usergroup_save_complete')) ? eval($hook) : false;

	define('CP_REDIRECT', 'usergroup.php?do=modify');
	print_stop_message('saved_usergroup_x_successfully', htmlspecialchars_uni($vbulletin->GPC['usergroup']['title']));

}

// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'remove')
{

	if ($vbulletin->GPC['usergroupid'] < 8)
	{
		print_stop_message('cant_delete_usergroup');
	}
	else
	{
		print_delete_confirmation('usergroup', $vbulletin->GPC['usergroupid'], 'usergroup', 'kill', 'usergroup', 0,
			construct_phrase($vbphrase['all_members_of_this_usergroup_will_revert'], $vbulletin->usergroupcache['2']['title'])
		);
	}

}

// ###################### Start Kill #######################
if ($_POST['do'] == 'kill')
{

	// update users who are in this usergroup to be in the registered usergroup
	$db->query_write("UPDATE " . TABLE_PREFIX . "user SET usergroupid = 2 WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("UPDATE " . TABLE_PREFIX . "user SET displaygroupid = 0 WHERE displaygroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("UPDATE " . TABLE_PREFIX . "user SET infractiongroupid = 0 WHERE infractiongroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("UPDATE " . TABLE_PREFIX . "useractivation SET usergroupid = 2 WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("UPDATE " . TABLE_PREFIX . "subscription SET nusergroupid = -1 WHERE nusergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("UPDATE " . TABLE_PREFIX . "subscriptionlog SET pusergroupid = 2 WHERE pusergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("UPDATE " . TABLE_PREFIX . "userban SET usergroupid = 2 WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("UPDATE " . TABLE_PREFIX . "userban SET displaygroupid = 0 WHERE displaygroupid = " . $vbulletin->GPC['usergroupid']);

	// now get on with deleting stuff...
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "forumpermission WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "ranks WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "usergrouprequest WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "userpromotion WHERE usergroupid = " . $vbulletin->GPC['usergroupid'] . " OR joinusergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "imagecategorypermission WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "attachmentpermission WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "prefixpermission WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
    $db->query_write("DELETE FROM " . TABLE_PREFIX . "usergroupleader WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
    $db->query_write("DELETE FROM " . TABLE_PREFIX . "infractiongroup WHERE usergroupid = " . $vbulletin->GPC['usergroupid'] . " OR orusergroupid = " . $vbulletin->GPC['usergroupid']);
    $db->query_write("DELETE FROM " . TABLE_PREFIX . "infractionban WHERE usergroupid = " . $vbulletin->GPC['usergroupid'] . " OR banusergroupid = " . $vbulletin->GPC['usergroupid']);
    
	build_ranks();
	build_forum_permissions();

	require_once(DIR . '/includes/adminfunctions_attachment.php');
	build_attachment_permissions();

	// remove this group from users who have this group as a membergroup or infractiongroupid
	$casesqlm = $casesqli = '';
	$updateusersm = $updateusersi = array();

	$users = $db->query_read("
		SELECT userid, username, membergroupids, infractiongroupids
		FROM " . TABLE_PREFIX . "user
		WHERE FIND_IN_SET('" . $vbulletin->GPC['usergroupid'] . "', membergroupids)
		OR FIND_IN_SET('" . $vbulletin->GPC['usergroupid'] . "', infractiongroupids)
	");
	
	if ($db->num_rows($users))
	{
		while($user = $db->fetch_array($users))
		{
			if (!empty($user['membergroupids']))
			{
				$membergroups = fetch_membergroupids_array($user, false);
				foreach($membergroups AS $key => $val)
				{
					if ($val == $vbulletin->GPC['usergroupid'])
					{
						unset($membergroups["$key"]);
					}
				}
				$user['membergroupids'] = implode(',', $membergroups);
				$casesqlm .= "WHEN $user[userid] THEN '$user[membergroupids]' ";
				$updateusersm[] = $user['userid'];
			}
			if (!empty($user['infractiongroupids']))
			{
				$infractiongroups = explode(',', str_replace(' ', '', $user['infractiongroupids']));
				foreach($infractiongroups AS $key => $val)
				{
					if ($val == $vbulletin->GPC['usergroupid'])
					{
						unset($infractiongroups["$key"]);
					}
				}
				$user['infractiongroupids'] = implode(',', $infractiongroups);
				$casesqli .= "WHEN $user[userid] THEN '$user[infractiongroupids]' ";
				$updateusersi[] = $user['userid'];
			}
		}

		// do big update to get rid of this usergroup from matched members' membergroupids
		if (!empty($casesqlm))
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "user SET
				membergroupids = CASE userid
				$casesqlm
				ELSE '' END
				WHERE userid IN(" . implode(',', $updateusersm) . ")
			");
		}

		// do big update to get rid of this usergroup from matched members' infractiongroupids
		if (!empty($casesqli))
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "user SET
				infractiongroupids = CASE userid
				$casesqli
				ELSE '' END
				WHERE userid IN(" . implode(',', $updateusersi) . ")
			");
		}
	}

	($hook = vBulletinHook::fetch_hook('admin_usergroup_kill')) ? eval($hook) : false;

	define('CP_REDIRECT', 'usergroup.php?do=modify');
	print_stop_message('deleted_usergroup_successfully');
}

// ###################### Start kill group leader #######################
if ($_POST['do'] == 'killleader')
{

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "usergroupleader WHERE usergroupleaderid = " . $vbulletin->GPC['usergroupleaderid']);

	define('CP_REDIRECT', 'usergroup.php?do=modify');
	print_stop_message('deleted_usergroup_leader_successfully');
}

// ###################### Start delete group leader #######################
if ($_REQUEST['do'] == 'removeleader')
{

	print_delete_confirmation('usergroupleader', $vbulletin->GPC['usergroupleaderid'], 'usergroup', 'killleader', 'usergroup_leader');

}

// ###################### Start insert group leader #######################
if ($_POST['do'] == 'insertleader')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'username' => TYPE_NOHTML
	));

	if ($usergroup = $db->query_first("SELECT title FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid = " . $vbulletin->GPC['usergroupid'] . " AND ispublicgroup = 1 AND usergroupid > 7"))
	{
		if ($user = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE username = '" . $db->escape_string($vbulletin->GPC['username']) . "'"))
		{
			if (is_unalterable_user($user['userid']))
			{
				print_stop_message('user_is_protected_from_alteration_by_undeletableusers_var');
			}

			if ($preexists = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "usergroupleader WHERE usergroupid = " . $vbulletin->GPC['usergroupid'] . " AND userid = $user[userid]"))
			{
				print_stop_message('invalid_usergroup_leader_specified');
			}

			// update leader's member groups if necessary
			if (strpos(",$user[membergroupids],", "," . $vbulletin->GPC['usergroupid'] . ",") === false AND $user['usergroupid'] != $vbulletin->GPC['usergroupid'])
			{
				if (empty($user['membergroupids']))
				{
					$membergroups = $vbulletin->GPC['usergroupid'];
				}
				else
				{
					$membergroups = "$user[membergroupids]," . $vbulletin->GPC['usergroupid'];
				}

				$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_CP);
				$userdm->set_existing($user);
				$userdm->set('membergroupids', $membergroups);
				$userdm->save();
				unset($userdm);
			}

			// insert into usergroupleader table
			/*insert query*/
			$result = $db->query_write("
				INSERT INTO " . TABLE_PREFIX . "usergroupleader
				(userid, usergroupid)
				VALUES
				($user[userid], " . $vbulletin->GPC['usergroupid'] . ")
			");

			define('CP_REDIRECT', 'usergroup.php?do=modify');
			print_stop_message('saved_usergroup_leader_x_successfully', $vbulletin->GPC['username']);
		}
		else
		{
			print_stop_message('invalid_user_specified');
		}
	}
	else
	{
		print_stop_message('cant_add_usergroup_leader');
	}

}

// ###################### Start add group leader #######################
if ($_REQUEST['do'] == 'addleader')
{

	$groups = array();
	$usergroups = $db->query_read("
		SELECT usergroupid, title
		FROM " . TABLE_PREFIX . "usergroup
		WHERE usergroupid > 7 AND
			ispublicgroup = 1
		ORDER BY title
	");
	while($usergroup = $db->fetch_array($usergroups))
	{
		$groups["$usergroup[usergroupid]"] = $usergroup['title'];
	}

	if (!isset($groups["{$vbulletin->GPC['usergroupid']}"]))
	{
		print_stop_message('usergroup_not_public_or_invalid');
	}

	print_form_header('usergroup', 'insertleader');
	construct_hidden_code('usergroupid', $vbulletin->GPC['usergroupid']);
	print_table_header($vbphrase['add_new_usergroup_leader']);
	print_select_row($vbphrase['usergroup'], 'usergroupid', $groups, $vbulletin->GPC['usergroupid']);
	print_input_row($vbphrase['username'], 'username');
	print_submit_row($vbphrase['add'], 0);

}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	// get usergroups (don't use the cache at this point...
	// this is the only place where you could rebuild the forumcache and vbulletin->usergroupcache
	// without them being present already...

	unset($vbulletin->usergroupcache);

	$usergroups = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "usergroup ORDER BY title");
	while ($usergroup = $db->fetch_array($usergroups))
	{
		$vbulletin->usergroupcache["{$usergroup['usergroupid']}"] = $usergroup;
	}
	unset($usergroup);
	$db->free_result($usergroups);

	// count primary users
	$groupcounts = $db->query_read("
		SELECT user.usergroupid, COUNT(user.userid) AS total
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup USING (usergroupid)
		WHERE usergroup.usergroupid IS NOT NULL
		GROUP BY usergroupid
	");
	while($groupcount = $db->fetch_array($groupcounts))
	{
		$vbulletin->usergroupcache["{$groupcount['usergroupid']}"]['count'] = $groupcount['total'];
	}
	unset($groupcount);
	$db->free_result($groupcounts);

	// count secondary users
	$groupcounts = $db->query_read("
		SELECT membergroupids, usergroupid
		FROM " . TABLE_PREFIX . "user
		WHERE membergroupids <> ''
	");
	while ($groupcount = $db->fetch_array($groupcounts))
	{
		$ids = fetch_membergroupids_array($groupcount, false);
		foreach ($ids AS $index => $value)
		{
			if ($groupcount['usergroupid'] != $value AND !empty($vbulletin->usergroupcache["$value"]))
			{
				$vbulletin->usergroupcache["$value"]['secondarycount']++;
			}
		}
	}
	unset($groupcount);
	$db->free_result($groupcounts);

	// count requests
	$groupcounts = $db->query_read("
		SELECT usergroupid, COUNT(userid) AS total
		FROM " . TABLE_PREFIX . "usergrouprequest AS usergrouprequest
		GROUP BY usergroupid
	");
	while($groupcount = $db->fetch_array($groupcounts))
	{
		$vbulletin->usergroupcache["{$groupcount['usergroupid']}"]['requests'] = $groupcount['total'];
	}
	unset($groupcount);
	$db->free_result($groupcounts);

	$usergroups = array();
	foreach($vbulletin->usergroupcache AS $group)
	{
		if ($group['usergroupid'] > 7)
		{
			if ($group['ispublicgroup'])
			{
				$usergroups['public']["{$group['usergroupid']}"] = $group;
			}
			else
			{
				$usergroups['custom']["{$group['usergroupid']}"] = $group;
			}
		}
		else
		{
			$usergroups['default']["{$group['usergroupid']}"] = $group;
		}
	}

	$usergroupleaders = array();
	$leaders = $db->query_read("
		SELECT usergroupleader.*, username
		FROM " . TABLE_PREFIX . "usergroupleader AS usergroupleader
		INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
	");
	while ($leader = $db->fetch_array($leaders))
	{
		$usergroupleaders["{$leader['usergroupid']}"][] = $leader;
	}
	unset($leader);
	$db->free_result($leaders);

	$promotions = array();
	$proms = $db->query_read("
		SELECT COUNT(*) AS count, usergroupid
		FROM " . TABLE_PREFIX . "userpromotion
		GROUP BY usergroupid
	");
	while ($prom = $db->fetch_array($proms))
	{
		$promotions["{$prom['usergroupid']}"] = $prom['count'];
	}

	?>
	<script type="text/javascript">
	function js_usergroup_jump(usergroupid)
	{
		task = eval("document.cpform.u" + usergroupid + ".options[document.cpform.u" + usergroupid + ".selectedIndex].value");
		switch (task)
		{
			case 'edit': window.location = "usergroup.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=edit&usergroupid=" + usergroupid; break;
			case 'kill': window.location = "usergroup.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=remove&usergroupid=" + usergroupid; break;
			case 'list': window.location = "user.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=find&user[usergroupid]=" + usergroupid; break;
			case 'list2': window.location = "user.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=find&user[membergroup][]=" + usergroupid; break;
			case 'reputation': window.location = "user.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=find&display[username]=1&display[options]=1&display[posts]=1&display[usergroup]=1&display[lastvisit]=1&display[reputation]=1&orderby=reputation&direction=desc&limitnumber=25&user[usergroupid]=" + usergroupid; break;
			case 'promote': window.location = "usergroup.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=modifypromotion&returnug=1&usergroupid=" + usergroupid; break;
			case 'leader': window.location = "usergroup.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=addleader&usergroupid=" + usergroupid; break;
			case 'requests': window.location = "usergroup.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=viewjoinrequests&usergroupid=" + usergroupid; break;
			default: return false; break;
		}
	}
	</script>
	<?php

	// ###################### Start makeusergroupcode #######################
	function print_usergroup_row($usergroup, $options)
	{
		global $usergroupleaders, $vbphrase, $promotions, $vbulletin;

		if ($promotions["$usergroup[usergroupid]"])
		{
			$options['promote'] .= " (${promotions[$usergroup[usergroupid]]})";
		}

		$cell = array();
		$cell[] = "<b>$usergroup[title]" . iif($usergroup['canoverride'], '*') . "</b>" . iif($usergroup['ispublicgroup'], '<br /><span class="smallfont">' . $usergroup['description'] . '</span>');
		$cell[] = iif($usergroup['count'], vb_number_format($usergroup['count']), '-');
		$cell[] = iif($usergroup['secondarycount'], vb_number_format($usergroup['secondarycount']), '-');

		if ($usergroup['ispublicgroup'])
		{
			$cell[] = iif($usergroup['requests'], vb_number_format($usergroup['requests']), '0');
		}
		if ($usergroup['ispublicgroup'])
		{
			$cell_out = '<span class="smallfont">';
			if (is_array($usergroupleaders["$usergroup[usergroupid]"]))
			{
				foreach($usergroupleaders["$usergroup[usergroupid]"] AS $usergroupleader)
				{
					$cell_out .= "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$usergroupleader[userid]\"><b>$usergroupleader[username]</b></a>" . construct_link_code($vbphrase['delete'], "usergroup.php?" . $vbulletin->session->vars['sessionurl'] . "do=removeleader&amp;usergroupleaderid=$usergroupleader[usergroupleaderid]") . '<br />';
				}
			}
			$cell[] = $cell_out . '</span>';
		}
		$options['edit'] .= " (id: $usergroup[usergroupid])";
		$cell[] = "\n\t<select name=\"u$usergroup[usergroupid]\" onchange=\"js_usergroup_jump($usergroup[usergroupid]);\" class=\"bginput\">\n" . construct_select_options($options) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_usergroup_jump($usergroup[usergroupid]);\" />\n\t";
		print_cells_row($cell);
	}

	print_form_header('usergroup', 'add');

	$options_default = array(
		'edit'       => $vbphrase['edit_usergroup'],
		'promote'    => $vbphrase['edit_promotions'],
		'list'       => $vbphrase['show_all_primary_users'],
		'list2'      => $vbphrase['show_all_additional_users'],
		'reputation' => $vbphrase['view_reputation']
	);
	$options_custom = array(
		'edit'       => $vbphrase['edit_usergroup'],
		'promote'    => $vbphrase['edit_promotions'],
		'kill'       => $vbphrase['delete_usergroup'],
		'list'       => $vbphrase['show_all_primary_users'],
		'list2'      => $vbphrase['show_all_additional_users'],
		'reputation' => $vbphrase['view_reputation']
	);
	$options_public = array(
		'edit'       => $vbphrase['edit_usergroup'],
		'promote'    => $vbphrase['edit_promotions'],
		'kill'       => $vbphrase['delete_usergroup'],
		'list'       => $vbphrase['show_all_primary_users'],
		'list2'      => $vbphrase['show_all_additional_users'],
		'reputation' => $vbphrase['view_reputation'],
		'leader'     => $vbphrase['add_usergroup_leader'],
		'requests'   => $vbphrase['view_join_requests']
	);

	print_table_header($vbphrase['default_usergroups'], 5);
	print_cells_row(array($vbphrase['title'], $vbphrase['primary_users'], $vbphrase['additional_users'], $vbphrase['controls']), 1);
	foreach($usergroups['default'] AS $usergroup)
	{
		print_usergroup_row($usergroup, $options_default);
	}
	if (is_array($usergroups['custom']))
	{
		print_table_break();
		print_table_header($vbphrase['custom_usergroups'], 5);
		print_cells_row(array($vbphrase['title'], $vbphrase['primary_users'], $vbphrase['additional_users'], $vbphrase['controls']), 1);
		foreach($usergroups['custom'] AS $usergroup)
		{
			print_usergroup_row($usergroup, $options_custom);
		}
		print_description_row('<span class="smallfont">' . $vbphrase['note_groups_marked_with_a_asterisk'] . '</span>', 0, 6);
	}
	if (is_array($usergroups['public']))
	{
		print_table_break();
		print_table_header($vbphrase['public_joinable_custom_usergroup'], 9);
		print_cells_row(array($vbphrase['title'], $vbphrase['primary_users'], $vbphrase['additional_users'], $vbphrase['join_requests'], $vbphrase['usergroup_leader'], $vbphrase['controls']), 1);
		foreach($usergroups['public'] AS $usergroup)
		{
			print_usergroup_row($usergroup, $options_public);
		}
		print_description_row('<span class="smallfont">' . $vbphrase['note_groups_marked_with_a_asterisk'] . '</span>', 0, 6);
	}

	print_table_break();
	print_submit_row($vbphrase['add_new_usergroup'], 0);

}

// ###################### Start modify promotions #######################
if ($_REQUEST['do'] == 'modifypromotion')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'returnug' => TYPE_BOOL
	));

	$title = $db->query_first("SELECT title FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);

	$promotions = array();
	$getpromos = $db->query_read("
		SELECT userpromotion.*, joinusergroup.title
		FROM " . TABLE_PREFIX . "userpromotion AS userpromotion
		LEFT JOIN " . TABLE_PREFIX . "usergroup AS joinusergroup ON (userpromotion.joinusergroupid = joinusergroup.usergroupid)
		" . iif($vbulletin->GPC['usergroupid'], "WHERE userpromotion.usergroupid = " . $vbulletin->GPC['usergroupid']) . "
	");
	while ($promotion = $db->fetch_array($getpromos))
	{
		$promotions["$promotion[usergroupid]"][] = $promotion;
	}
	unset($promotion);
	$db->free_result($getpromos);

	print_form_header('usergroup', 'updatepromotion');
	if (isset($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]))
	{
		construct_hidden_code('usergroupid', $vbulletin->GPC['usergroupid']);
	}
	if ($vbulletin->GPC['returnug'])
	{
		construct_hidden_code('returnug', 1);
	}

	foreach($promotions AS $groupid => $promos)
	{
		print_table_header("$vbphrase[promotions]: <span style=\"font-weight:normal\">" . $vbulletin->usergroupcache["$groupid"]['title'] . ' ' . construct_link_code($vbphrase['add_new_promotion'], "usergroup.php?" . $vbulletin->session->vars['sessionurl'] . "do=updatepromotion&amp;usergroupid=$groupid" . ($vbulletin->GPC['returnug'] ? '&amp;returnug=1' : '')) . "</span>", 7);
		print_cells_row(array(
			$vbphrase['usergroup'],
			$vbphrase['promotion_type'],
			$vbphrase['promotion_strategy'],
			$vbphrase['reputation_level'],
			$vbphrase['days_registered'],
			$vbphrase['posts'],
			$vbphrase['controls']
		), 1);

		foreach($promos AS $promotion)
		{
			$promotion['strategy'] = iif(($promotion['strategy'] > 7 AND $promotion['strategy'] < 16) OR $promotion['strategy'] == 24, $promotion['strategy'] - 8, $promotion['strategy']);
			if ($promotion['strategy'] == 16)
			{
				$type = $vbphrase['reputation'];
			}
			else if ($promotion['strategy'] == 17)
			{
				$type = $vbphrase['posts'];
			}
			else if ($promotion['strategy'] == 18)
			{
				$type = $vbphrase['join_date'];
			}
			else
			{
				$type = $vbphrase['promotion_strategy' . ($promotion['strategy'] + 1)];
			}
			print_cells_row(array(
				"<b>$promotion[title]</b>",
				iif($promotion['type']==1, $vbphrase['primary_usergroup'], $vbphrase['additional_usergroups']),
				$type,
				$promotion['reputation'],
				$promotion['date'],
				$promotion['posts'],
				construct_link_code($vbphrase['edit'], "usergroup.php?" . $vbulletin->session->vars['sessionurl'] . "userpromotionid=$promotion[userpromotionid]&do=updatepromotion" . ($vbulletin->GPC['returnug'] ? '&returnug=1' : '')) . construct_link_code($vbphrase['delete'], "usergroup.php?" . $vbulletin->session->vars['sessionurl'] . "userpromotionid=$promotion[userpromotionid]&do=removepromotion" . ($vbulletin->GPC['returnug'] ? '&returnug=1' : '')),
			));
		}
	}

	print_submit_row($vbphrase['add_new_promotion'], 0, 7);

}

// ###################### Start edit/insert promotions #######################
if ($_REQUEST['do'] == 'updatepromotion')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userpromotionid' => TYPE_INT,
		'returnug'        => TYPE_BOOL,
	));

	$usergroups = array();
	foreach($vbulletin->usergroupcache AS $usergroup)
	{
		$usergroups["{$usergroup['usergroupid']}"] = $usergroup['title'];
	}

	print_form_header('usergroup', 'doupdatepromotion');

	if (!$vbulletin->GPC['userpromotionid'])
	{
		$promotion = array(
			'reputation' => 1000,
			'date' => 30,
			'posts' => 100,
			'type' => 1,
			'reputationtype' => 0,
			'strategy' => 16
		);

		if ($vbulletin->GPC['usergroupid'])
		{
			$promotion['usergroupid'] = $vbulletin->GPC['usergroupid'];
		}

		if ($vbulletin->GPC['returnug'])
		{
			construct_hidden_code('returnug', 1);
		}
		print_table_header($vbphrase['add_new_promotion']);
		print_select_row($vbphrase['usergroup'], 'promotion[usergroupid]', $usergroups, $promotion['usergroupid']);

	}
	else
	{
		$promotion = $db->query_first("
			SELECT userpromotion.*, usergroup.title
			FROM " . TABLE_PREFIX . "userpromotion AS userpromotion,
			" . TABLE_PREFIX . "usergroup AS usergroup
			WHERE userpromotionid = " . $vbulletin->GPC['userpromotionid'] . " AND
				userpromotion.usergroupid = usergroup.usergroupid
		");

		if (($promotion['strategy'] > 7 AND $promotion['strategy'] < 16) OR $promotion['strategy'] == 24)
		{
			$promotion['reputationtype'] = 1;
			$promotion['strategy'] -= 8;
		}
		else
		{
			$promotion['reputationtype'] = 0;
		}
		if ($vbulletin->GPC['returnug'])
		{
			construct_hidden_code('returnug', 1);
		}
		construct_hidden_code('userpromotionid', $vbulletin->GPC['userpromotionid']);
		construct_hidden_code('usergroupid', $promotion['usergroupid']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['promotion'], $promotion['title'], $promotion['userpromotionid']));
	}

	$promotionarray = array(
		17=> $vbphrase['posts'],
		18=> $vbphrase['join_date'],
		16=> $vbphrase['reputation'],
		0 => $vbphrase['promotion_strategy1'],
		1 => $vbphrase['promotion_strategy2'],
		2 => $vbphrase['promotion_strategy3'],
		3 => $vbphrase['promotion_strategy4'],
		4 => $vbphrase['promotion_strategy5'],
		5 => $vbphrase['promotion_strategy6'],
		6 => $vbphrase['promotion_strategy7'],
		7 => $vbphrase['promotion_strategy8'],
	);

	print_input_row($vbphrase['reputation_level'], 'promotion[reputation]', $promotion['reputation']);
	print_input_row($vbphrase['days_registered'], 'promotion[date]', $promotion['date']);
	print_input_row($vbphrase['posts'], 'promotion[posts]', $promotion['posts']);
	print_select_row($vbphrase['promotion_strategy'] . " <dfn> $vbphrase[promotion_strategy_description]</dfn>", 'promotion[strategy]', $promotionarray, $promotion['strategy']);
	print_select_row($vbphrase['promotion_type'] . ' <dfn>' . $vbphrase['promotion_type_description_primary_additional'] . '</dfn>', 'promotion[type]', array(1 => $vbphrase['primary_usergroup'], 2 => $vbphrase['additional_usergroups']), $promotion['type']);
	print_select_row($vbphrase['reputation_comparison_type'] . '<dfn>' . $vbphrase['reputation_comparison_type_desc'] . '</dfn>', 'promotion[reputationtype]', array($vbphrase['greater_or_equal_to'], $vbphrase['less_than']), $promotion['reputationtype']);
	print_chooser_row($vbphrase['move_user_to_usergroup'] . " <dfn>$vbphrase[move_user_to_usergroup_description]</dfn>", 'promotion[joinusergroupid]', 'usergroup', $promotion['joinusergroupid'], '&nbsp;');

	print_submit_row(iif(empty($vbulletin->GPC['userpromotionid']), $vbphrase['save'], '_default_'));
}

// ###################### Start do edit/insert promotions #######################
if ($_POST['do'] == 'doupdatepromotion')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'promotion'       => TYPE_ARRAY,
		'userpromotionid' => TYPE_INT,
		'returnug'        => TYPE_BOOL,
	));

	if ($vbulletin->GPC['promotion']['joinusergroupid'] == -1)
	{
		print_stop_message('invalid_usergroup_specified');
	}

	if ($vbulletin->GPC['promotion']['reputationtype'] AND $vbulletin->GPC['promotion']['strategy'] <= 16)
	{
		$vbulletin->GPC['promotion']['strategy'] += 8;
	}
	unset($vbulletin->GPC['promotion']['reputationtype']);

	if (!empty($vbulletin->GPC['userpromotionid']))
	{ // update
		if ($vbulletin->GPC['usergroupid'] == $vbulletin->GPC['promotion']['joinusergroupid'])
		{
			print_stop_message('promotion_join_same_group');
		}
		$db->query_write(fetch_query_sql($vbulletin->GPC['promotion'], 'userpromotion', "WHERE userpromotionid=" . $vbulletin->GPC['userpromotionid']));
	}
	else
	{ // insert
		$vbulletin->GPC['usergroupid'] = $vbulletin->GPC['promotion']['usergroupid'];
		if ($vbulletin->GPC['usergroupid'] == $vbulletin->GPC['promotion']['joinusergroupid'])
		{
			print_stop_message('promotion_join_same_group');
		}
		/*insert query*/
		$db->query_write(fetch_query_sql($vbulletin->GPC['promotion'], 'userpromotion'));
	}

	// $title = $db->query_first("SELECT title FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);
	// $message = str_replace('{title}', $title['title'], $message);

	define('CP_REDIRECT', "usergroup.php?do=modifypromotion" . ($vbulletin->GPC['returnug'] ? "&returnug=1&usergroupid=" . $vbulletin->GPC['usergroupid'] : ''));
	print_stop_message('saved_promotion_successfully');
}

// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'removepromotion')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userpromotionid' => TYPE_INT,
		'returnug'        => TYPE_BOOL,
	));
	print_delete_confirmation('userpromotion', $vbulletin->GPC['userpromotionid'], 'usergroup', 'killpromotion', 'promotion_usergroup', array('returnug' => $vbulletin->GPC['returnug']));

}

// ###################### Start Kill #######################
if ($_POST['do'] == 'killpromotion')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'userpromotionid' => TYPE_INT,
		'returnug'        => TYPE_BOOL,
	));
	$promotion = $db->query_first_slave("SELECT usergroupid FROM " . TABLE_PREFIX . "userpromotion WHERE userpromotionid = " . $vbulletin->GPC['userpromotionid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "userpromotion WHERE userpromotionid = " . $vbulletin->GPC['userpromotionid']);

	define('CP_REDIRECT', 'usergroup.php?do=modifypromotion' . ($vbulletin->GPC['returnug'] ? '&returnug=1&usergroupid=' . $promotion['usergroupid'] : ""));
	print_stop_message('deleted_promotion_successfully');
}

// #############################################################################
// process usergroup join requests
if ($_POST['do'] == 'processjoinrequests')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'request' => TYPE_ARRAY_INT
	));

	($hook = vBulletinHook::fetch_hook('admin_joinrequest_process_start')) ? eval($hook) : false;

	// check we have some results to process
	if (empty($vbulletin->GPC['request']))
	{
		print_stop_message('no_matches_found');
	}

	// check that we are working with a valid usergroup
	if (!is_array($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]))
	{
		print_stop_message('invalid_usergroup_specified');
	}
	else
	{
		$usergroupname = htmlspecialchars_uni($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]['title']);
	}

	$auth = array();

	// sort the requests according to the action specified
	foreach($vbulletin->GPC['request'] AS $requestid => $action)
	{
		switch($action)
		{
			case -1:	// this request will be ignored
				unset($vbulletin->GPC['request']["$requestid"]);
				break;

			case  1:	// this request will be authorized
				$auth[] = intval($requestid);
				break;

			case  0:	// this request will be denied
				// do nothing - this request will be zapped at the end of this segment
				break;
		}
	}

	// if we have any accepted requests, make sure they are valid
	if (!empty($auth))
	{
		$users = $db->query_read("
			SELECT req.userid, user.username, user.usergroupid, user.membergroupids, req.usergrouprequestid
			FROM " . TABLE_PREFIX . "usergrouprequest AS req
			INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
			WHERE usergrouprequestid IN (" . implode(', ', $auth) . ")
			ORDER BY user.username
		");
		$auth = array();
		echo "<p><b>" . $vbphrase['processing_join_requests'] . "</b></p><ul>\n";
		while ($user = $db->fetch_array($users))
		{
			if (in_array($vbulletin->GPC['usergroupid'], fetch_membergroupids_array($user)))
			{
				echo "\t<li>" . construct_phrase($vbphrase['x_is_already_a_member_of_the_usergroup_y'], "<b>$user[username]</b>", "<i>$usergroupname</i>") . "</li>\n";
			}
			else
			{
				echo "\t<li>" . construct_phrase($vbphrase['making_x_a_member_of_the_usergroup_y'], "<b>$user[username]</b>", "<i>$usergroupname</i>") . "</li>\n";
				$auth[] = $user['userid'];
			}
		}
		echo "</ul><p><b>$vbphrase[done]</b></p>\n";

		// check that we STILL have some valid requests
		if (!empty($auth))
		{
			$updateQuery = "
				UPDATE " . TABLE_PREFIX . "user SET
				membergroupids = IF(membergroupids = '', " . $vbulletin->GPC['usergroupid'] . ", CONCAT(membergroupids, '," . $vbulletin->GPC['usergroupid'] . "'))
				WHERE userid IN (" . implode(', ', $auth) . ")
			";
			$db->query_write($updateQuery);
		}
	}

	($hook = vBulletinHook::fetch_hook('admin_joinrequest_process_complete')) ? eval($hook) : false;

	// delete processed join requests
	if (!empty($vbulletin->GPC['request']))
	{
		$request = array_map('intval', array_keys($vbulletin->GPC['request']));
		$deleteQuery = "DELETE FROM " . TABLE_PREFIX . "usergrouprequest WHERE usergrouprequestid IN (" . implode(', ', $request) . ")";
		$db->query_write($deleteQuery);
	}

	// and finally jump back to the join requests screen
	$_REQUEST['do'] = 'viewjoinrequests';
}

// #############################################################################
// show usergroup join requests
if ($_REQUEST['do'] == 'viewjoinrequests')
{

	($hook = vBulletinHook::fetch_hook('admin_joinrequest_view_start')) ? eval($hook) : false;

	// first query groups that have join requests
	$getusergroups = $db->query_read("
		SELECT req.usergroupid, COUNT(req.usergrouprequestid) AS requests,
		IF(usergroup.usergroupid IS NULL, 0, 1) AS validgroup
		FROM " . TABLE_PREFIX . "usergrouprequest AS req
		LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroup.usergroupid = req.usergroupid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = req.userid)
		WHERE user.userid IS NOT NULL
		GROUP BY req.usergroupid
	");
	if ($db->num_rows($getusergroups) == 0)
	{
		// there are no join requests
		print_stop_message('nothing_to_do');
	}

	// if we got this far we know that we have at least one group with some requests in it
	$usergroups = array();
	$badgroups = array();

	while($getusergroup = $db->fetch_array($getusergroups))
	{
		$ugid =& $getusergroup['usergroupid'];

		if (isset($vbulletin->usergroupcache["$ugid"]))
		{
			$vbulletin->usergroupcache["$ugid"]['joinrequests'] = $getusergroup['requests'];
		}
		else
		{
			$badgroups[] = $getusergroup['usergroupid'];
		}
	}
	unset($getusergroup);
	$db->free_result($getusergroups);

	// if there are any invalid requests, zap them now
	if (!empty($badgroups))
	{
		$badgroups = implode(', ', $badgroups);
		DEVDEBUG("Deleting requests from the following invalid usergroups: $badgroups");
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "usergrouprequest WHERE usergroupid IN ($badgroups)");
	}

	// create array to hold options for the menu
	$groupsmenu = array();

	foreach ($vbulletin->usergroupcache AS $id => $usergroup)
	{
		if ($usergroup['ispublicgroup'])
		{
			$groupsmenu["$id"] = htmlspecialchars_uni($usergroup['title']) . " ($vbphrase[join_requests]: " . vb_number_format($usergroup['joinrequests']) . ")";
		}
	}

	print_form_header('usergroup', 'viewjoinrequests', 0, 1, 'chooser');
	print_label_row(
		$vbphrase['usergroup'],
		'<select name="usergroupid" onchange="this.form.submit();" class="bginput">' . construct_select_options($groupsmenu, $vbulletin->GPC['usergroupid'])  . '</select><input type="submit" class="button" value="' . $vbphrase['go'] . '" />',
		'thead'
	);
	print_table_footer();
	unset($groupsmenu);

	// now if we are being asked to display a particular usergroup, do so.
	if ($vbulletin->GPC['usergroupid'])
	{
		// check this is a valid usergroup
		if (!is_array($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]))
		{
			print_stop_message('invalid_usergroup_specified');
		}

		// check that this usergroup has some join requests
		if ($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]['joinrequests'])
		{

			// everything seems okay, so make a total record for this usergroup
			$usergroup =& $vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"];

			// query the usergroup leaders of this usergroup
			$leaders = array();
			$getleaders = $db->query_read("
				SELECT usergroupleader.userid, user.username
				FROM " . TABLE_PREFIX . "usergroupleader AS usergroupleader
				INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
				WHERE usergroupleader.usergroupid = " . $vbulletin->GPC['usergroupid'] . "
			");
			while ($getleader = $db->fetch_array($getleaders))
			{
				$leaders[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$getleader[userid]\">$getleader[username]</a>";
			}
			unset($getleader);
			$db->free_result($getleaders);

			// query the requests for this usergroup
			$requests = $db->query_read("
				SELECT req.*, user.username
				FROM " . TABLE_PREFIX . "usergrouprequest AS req
				INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
				WHERE req.usergroupid = " . $vbulletin->GPC['usergroupid'] . "
				ORDER BY user.username
			");

			print_form_header('usergroup', 'processjoinrequests');
			construct_hidden_code('usergroupid', $vbulletin->GPC['usergroupid']);
			print_table_header("$usergroup[title] - ($vbphrase[join_requests]: $usergroup[joinrequests])", 6);
			if (!empty($leaders))
			{
				print_description_row("<span style=\"font-weight:normal\">(" . $vbphrase['usergroup_leader'] . ': ' . implode(', ', $leaders) . ')</span>', 0, 6, 'thead');
			}
			print_cells_row(array
			(
				$vbphrase['username'],
				$vbphrase['reason'],
				'<span style="white-space:nowrap">' . $vbphrase['date'] . '</span>',
				'<input type="button" value="' . $vbphrase['accept'] . '" onclick="js_check_all_option(this.form, 1);" class="button" title="' . $vbphrase['check_all'] . '" />',
				'<input type="button" value=" ' . $vbphrase['deny'] . ' " onclick="js_check_all_option(this.form, 0);" class="button" title="' . $vbphrase['check_all'] . '" />',
				'<input type="button" value="' . $vbphrase['ignore'] . '" onclick="js_check_all_option(this.form, -1);" class="button" title="' . $vbphrase['check_all'] . '" />'
			), 1);

			$i = 0;

			while ($request = $db->fetch_array($requests))
			{
				if ($i > 0 AND $i % 10 == 0)
				{
					print_description_row('<div align="center"><input type="submit" class="button" value="' . $vbphrase['process'] . '" accesskey="s" tabindex="1" /></div>', 0, 6, 'thead');
				}
				$i++;
				$cell = array
				(
					"<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$request[userid]\"><b>$request[username]</b></a>",
					$request['reason'],
					'<span class="smallfont">' . vbdate($vbulletin->options['dateformat'], $request['dateline']) . '<br />' . vbdate($vbulletin->options['timeformat'], $request['dateline']) . '</span>',
					'<label for="a' . $request['usergrouprequestid'] . '" class="smallfont">' . $vbphrase['accept'] . '<input type="radio" name="request[' . $request['usergrouprequestid'] . ']" value="1" id="a' . $request['usergrouprequestid'] . '" tabindex="1" /></label>',
					'<label for="d' . $request['usergrouprequestid'] . '" class="smallfont">' . $vbphrase['deny'] . '<input type="radio" name="request[' . $request['usergrouprequestid'] . ']" value="0" id="d' . $request['usergrouprequestid'] . '" tabindex="1" /></label>',
					'<label for="i' . $request['usergrouprequestid'] . '" class="smallfont">' . $vbphrase['ignore'] . '<input type="radio" name="request[' . $request['usergrouprequestid'] . ']" value="-1" id="i' . $request['usergrouprequestid'] . '" tabindex="1" checked="checked" /></label>'
				);
				
				$printcells = true;
				($hook = vBulletinHook::fetch_hook('admin_joinrequest_view_bit')) ? eval($hook) : false;

				if ($printcells)
				{
					print_cells_row($cell, 0, '', -5);
				}
			}
			unset($request);
			$db->free_result($requests);

			print_submit_row($vbphrase['process'], $vbphrase['reset'], 6);

		}
		else
		{
			print_stop_message('no_join_requests_matched_your_query');
		}

	}

	($hook = vBulletinHook::fetch_hook('admin_joinrequest_view_complete')) ? eval($hook) : false;
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 56530 $
|| ####################################################################
\*======================================================================*/
?>
