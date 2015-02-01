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
define('CVS_REVISION', '$RCSfile$ - $Revision: 46627 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cppermission', 'forum');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_forums.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminpermissions'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################

$vbulletin->input->clean_array_gpc('r', array(
	'fp'	=> TYPE_INT,
	'f'		=> TYPE_INT,
	'u'		=> TYPE_INT
));

log_admin_action(
	iif($vbulletin->GPC['fp'] != 0, "forumpermission id = " . $vbulletin->GPC['fp'],
	iif($vbulletin->GPC['f'] != 0, "forum id = " . $vbulletin->GPC['f'] .
	iif($vbulletin->GPC['u'] != 0, " / usergroup id = " . $vbulletin->GPC['u']
))));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['forum_permissions_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit')
{
	$forumid =& $vbulletin->GPC['f'];
	$usergroupid =& $vbulletin->GPC['u'];
	$forumpermissionid =& $vbulletin->GPC['fp'];

	?>
	<script type="text/javascript">
	<!--
	function js_set_custom()
	{
		if (document.cpform.useusergroup[1].checked == false)
		{
			if (confirm("<?php echo $vbphrase['must_enable_custom_permissions']; ?>"))
			{
				document.cpform.useusergroup[1].checked = true;
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
		}
	}
	// -->
	</script>
	<?php

	print_form_header('forumpermission', 'doupdate');

	if (empty($forumpermissionid))
	{
		$forum = $vbulletin->forumcache["$forumid"];
		$usergroup = $vbulletin->usergroupcache["$usergroupid"];
		if (!$forum)
		{
			print_table_footer();
			print_stop_message('invalid_forum_specified');
		}
		else if (!$usergroup)
		{
			print_table_footer();
			print_stop_message('invalid_usergroup_specified');
		}
		$getperms = fetch_forum_permissions($usergroupid, $forumid);
		construct_hidden_code('forumpermission[usergroupid]', $usergroupid);
		construct_hidden_code('forumid', $forumid);
	}
	else
	{
		$getperms = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "forumpermission
			WHERE forumpermissionid = $forumpermissionid
		");
		if (!$getperms)
		{
			print_table_footer();
			print_stop_message('invalid_forum_permissions_specified');
		}
		$usergroup['title'] = $vbulletin->usergroupcache["$getperms[usergroupid]"]['title'];
		$forum['title'] = $vbulletin->forumcache["$getperms[forumid]"]['title'];
		construct_hidden_code('forumpermissionid', $forumpermissionid);
	}
	$forumpermission = convert_bits_to_array($getperms['forumpermissions'], $vbulletin->bf_ugp_forumpermissions);

	print_table_header(construct_phrase($vbphrase['edit_forum_permissions_for_usergroup_x_in_forum_y'], $usergroup['title'], $forum['title']));
	print_description_row('
		<label for="uug_1"><input type="radio" name="useusergroup" value="1" id="uug_1" onclick="this.form.reset(); this.checked=true;"' . iif(empty($forumpermissionid), ' checked="checked"') . ' />' . $vbphrase['use_default_permissions'] . '</label>
		<br />
		<label for="uug_0"><input type="radio" name="useusergroup" value="0" id="uug_0"' . iif(!empty($forumpermissionid), ' checked="checked"') . ' />' . $vbphrase['use_custom_permissions'] . '</label>
	', 0, 2, 'tfoot', '' , 'mode');
	print_table_break();
	print_forum_permission_rows($vbphrase['edit_forum_permissions'], $forumpermission, 'js_set_custom();');

	print_submit_row($vbphrase['save']);

}

// ###################### Start do update #######################
if ($_POST['do'] == 'doupdate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'forumpermissionid'	=> TYPE_INT,
		'forumpermission'	=> TYPE_ARRAY_INT,	// Its only ever refrenced as an array would be
		'useusergroup' 		=> TYPE_INT,
		'forumid' 			=> TYPE_INT,
	));

	if (!$vbulletin->GPC['forumpermissionid'])
	{
		$forum_perms = $db->query_first("
			SELECT forumpermissionid
			FROM " . TABLE_PREFIX . "forumpermission
			WHERE usergroupid = " . $vbulletin->GPC['forumpermission']['usergroupid'] . "
				AND forumid = " . $vbulletin->GPC['forumid']
		);
		$vbulletin->GPC['forumpermissionid'] = intval($forum_perms['forumpermissionid']);
		if ($vbulletin->GPC['forumpermissionid'])
		{
			$vbulletin->GPC['forumid'] = 0;
		}
	}

	// NOTE: $getforum is called to get a forumid to jump to on the target page...
	$infoquery = "
		SELECT forum.forumid, forum.title AS forumtitle,usergroup.title AS grouptitle
		FROM " . TABLE_PREFIX . "forumpermission AS forumpermission
		INNER JOIN " . TABLE_PREFIX . "forum AS forum ON (forum.forumid = forumpermission.forumid)
		INNER JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroup.usergroupid = forumpermission.usergroupid)
		WHERE forumpermissionid = " . $vbulletin->GPC['forumpermissionid']
	;

	if ($vbulletin->GPC['useusergroup'])
	{
		// use usergroup defaults. delete forumpermission if it exists
		if (!empty($vbulletin->GPC['forumpermissionid']))
		{
			$info = $db->query_first($infoquery);
			$db->query_write("DELETE FROM " . TABLE_PREFIX . "forumpermission WHERE forumpermissionid = " . $vbulletin->GPC['forumpermissionid']);
			build_forum_permissions();
			define('CP_REDIRECT', "forumpermission.php?do=modify&f=$info[forumid]#forum$info[forumid]");
			print_stop_message('deleted_forum_permissions_successfully');
		}
		else
		{
			build_forum_permissions();
			define('CP_REDIRECT', "forumpermission.php?do=modify&f=" . $vbulletin->GPC['forumid']);
			print_stop_message('saved_forum_permissions_successfully');
		}
	}
	else
	{

		require_once(DIR . '/includes/functions_misc.php');
		$querydata = array(
			'usergroupid' => $vbulletin->GPC['forumpermission']['usergroupid'],
			'forumpermissions' => convert_array_to_bits($vbulletin->GPC['forumpermission'], $vbulletin->bf_ugp_forumpermissions, 1)
		);

		($hook = vBulletinHook::fetch_hook('admin_fperms_save')) ? eval($hook) : false;

		if ($vbulletin->GPC['forumid'])
		{
			$querydata['forumid'] = $vbulletin->GPC['forumid'];
			$query = fetch_query_sql($querydata, 'forumpermission');
			/*insert query*/
			$db->query_write($query);

			$info['forumid'] = $vbulletin->GPC['forumid'];
			$foruminfo = $db->query_first("
				SELECT title
				FROM " . TABLE_PREFIX . "forum
				WHERE forumid = " . $vbulletin->GPC['forumid']
			);
			$groupinfo = $db->query_first("
				SELECT title
				FROM " . TABLE_PREFIX . "usergroup
				WHERE usergroupid = " . $vbulletin->GPC['forumpermission']['usergroupid']
			);

			build_forum_permissions();
			define('CP_REDIRECT', "forumpermission.php?do=modify&f=" . $vbulletin->GPC['forumid']);
			print_stop_message('saved_forum_permissions_successfully');
		}
		else
		{
			unset($querydata['usergroupid']);
			$query = fetch_query_sql($querydata, 'forumpermission', "WHERE forumpermissionid = " . $vbulletin->GPC['forumpermissionid']);
			$db->query_write($query);

			build_forum_permissions();

			$info = $db->query_first($infoquery);
			define('CP_REDIRECT', "forumpermission.php?do=modify&f=$info[forumid]#forum$info[forumid]");
			print_stop_message('saved_forum_permissions_successfully');
		}
	}

}

// ###################### Start duplicator #######################
if ($_REQUEST['do'] == 'duplicate')
{
	$permgroups = $db->query_read("
		SELECT usergroup.usergroupid, title, COUNT(forumpermission.forumpermissionid) AS permcount
		FROM " . TABLE_PREFIX . "usergroup AS usergroup
		LEFT JOIN " . TABLE_PREFIX . "forumpermission AS forumpermission ON (usergroup.usergroupid = forumpermission.usergroupid)
		GROUP BY usergroup.usergroupid
		HAVING permcount > 0
		ORDER BY title
	");
	$ugarr = array();
	while ($group = $db->fetch_array($permgroups))
	{
		$ugarr["$group[usergroupid]"] = $group['title'];
	}
	if (!empty($ugarr))
	{
		$usergrouplist = array();
		foreach($vbulletin->usergroupcache AS $usergroup)
		{
			$usergrouplist[] = "<input type=\"checkbox\" name=\"usergrouplist[$usergroup[usergroupid]]\" value=\"1\" /> $usergroup[title]";
		}
		$usergrouplist = implode("<br />\n", $usergrouplist);

		print_form_header('forumpermission', 'doduplicate_group');
		print_table_header($vbphrase['usergroup_based_permission_duplicator']);
		print_select_row($vbphrase['copy_permissions_from_group'], 'ugid_from', $ugarr);
		print_label_row($vbphrase['copy_permissions_to_groups'], "<span class=\"smallfont\">$usergrouplist</span>", '', 'top', 'usergrouplist');
		print_forum_chooser($vbphrase['only_copy_permissions_from_forum'], 'limitforumid', -1);
		print_yes_no_row($vbphrase['overwrite_duplicate_entries'], 'overwritedupes_group', 0);
		print_yes_no_row($vbphrase['overwrite_inherited_entries'], 'overwriteinherited_group', 0);
		print_submit_row($vbphrase['go']);
	}

	// generate forum check boxes
	$forumlist = array();
	foreach($vbulletin->forumcache AS $forum)
	{
		$depth = construct_depth_mark($forum['depth'], '--');
		$forumlist[] = "<input type=\"checkbox\" name=\"forumlist[$forum[forumid]]\" value=\"1\" tabindex=\"1\" />$depth $forum[title] ";
	}
	$forumlist = implode("<br />\n", $forumlist);

	print_form_header('forumpermission', 'doduplicate_forum');
	print_table_header($vbphrase['forum_based_permission_duplicator']);
	print_forum_chooser($vbphrase['copy_permissions_from_forum'], 'forumid_from', -1);
	print_label_row($vbphrase['copy_permissions_to_forums'], "<span class=\"smallfont\">$forumlist</span>", '', 'top', 'forumlist');
	//print_chooser_row($vbphrase['only_copy_permissions_from_group'], 'limitugid', 'usergroup', -1, $vbphrase['all_usergroups']);
	print_yes_no_row($vbphrase['overwrite_duplicate_entries'], 'overwritedupes_forum', 0);
	print_yes_no_row($vbphrase['overwrite_inherited_entries'], 'overwriteinherited_forum', 0);
	print_submit_row($vbphrase['go']);

}

// ###################### Start do duplicate (group-based) #######################
if ($_POST['do'] == 'doduplicate_group')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'ugid_from' 				=> TYPE_INT,
		'limitforumid' 				=> TYPE_INT,
		'overwritedupes_group' 		=> TYPE_INT,
		'overwriteinherited_group' 	=> TYPE_INT,
		'usergrouplist' 			=> TYPE_ARRAY
	));

	if (sizeof($vbulletin->GPC['usergrouplist']) == 0)
	{
		print_stop_message('invalid_usergroup_specified');
	}

	if ($vbulletin->GPC['limitforumid'] > 0)
	{
		$foruminfo = fetch_foruminfo($vbulletin->GPC['limitforumid']);
		$forumsql = "AND forumpermission.forumid IN ($foruminfo[parentlist])";
		$childforum = "AND forumpermission.forumid IN ($foruminfo[childlist])";
	}
	else
	{
		$childforum = '';
		$forumsql = '';
	}

	foreach ($vbulletin->GPC['usergrouplist'] AS $ugid_to => $confirm)
	{
		$ugid_to = intval($ugid_to);
		if ($vbulletin->GPC['ugid_from'] == $ugid_to OR $confirm != 1)
		{
			continue;
		}

		$forumsql_local = '';

		$existing = $db->query_read("
			SELECT forumpermission.forumid, forum.parentlist
			FROM " . TABLE_PREFIX . "forumpermission AS forumpermission, " . TABLE_PREFIX . "forum AS forum
			WHERE forumpermission.forumid = forum.forumid
				AND usergroupid = $ugid_to
				$forumsql
				$forumsql_local
		");
		$perm_set = array();
		while ($thisperm = $db->fetch_array($existing))
		{
			$perm_set[] = $thisperm['forumid'];
		}

		$perm_inherited = array();
		if (sizeof($perm_set) > 0)
		{
			$inherits = $db->query_read("
				SELECT forumid
				FROM " . TABLE_PREFIX . "forum
				WHERE CONCAT(',', parentlist, ',') LIKE '%," . implode(",%' OR CONCAT(',', parentlist, ',') LIKE '%,", $perm_set) . ",%'
			");
			while ($thisperm = $db->fetch_array($inherits))
			{
				$perm_inherited[] = $thisperm['forumid'];
			}
		}

		if (!$vbulletin->GPC['overwritedupes_group'] OR !$vbulletin->GPC['overwriteinherited_group'])
		{
			$exclude = array('0');
			if (!$vbulletin->GPC['overwritedupes_group'])
			{
				$exclude = array_merge($exclude, $perm_set);
			}
			if (!$vbulletin->GPC['overwriteinherited_group'])
			{
				$exclude = array_merge($exclude, $perm_inherited);
			}
			$exclude = array_unique($exclude);
			$forumsql_local .= ' AND forumpermission.forumid NOT IN (' . implode(',', $exclude) . ')';
		}

		$perms = $db->query_read("
			SELECT forumid, forumpermissions
			FROM " . TABLE_PREFIX . "forumpermission AS forumpermission
			WHERE usergroupid = " . $vbulletin->GPC['ugid_from'] . "
				$childforum
				$forumsql_local
		");

		while ($thisperm = $db->fetch_array($perms))
		{
			/*insert query*/
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "forumpermission
					(forumid, usergroupid, forumpermissions)
				VALUES
					($thisperm[forumid], $ugid_to, $thisperm[forumpermissions])
			");
		}
	}

	build_forum_permissions();

	define('CP_REDIRECT', 'forumpermission.php?do=modify');
	print_stop_message('duplicated_permissions_successfully');
}

// ###################### Start do duplicate (forum-based) #######################
if ($_POST['do'] == 'doduplicate_forum')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'forumid_from' 					=> TYPE_INT,
		'overwritedupes_forum'			=> TYPE_INT,
		'overwriteinherited_forum' 		=> TYPE_INT,
		'forumlist' 					=> TYPE_ARRAY
	));

	if (sizeof($vbulletin->GPC['forumlist']) == 0)
	{
		print_stop_message('invalid_forum_specified');
	}

	$forumperms = $db->query_read("
		SELECT usergroupid, forumpermissions
		FROM " . TABLE_PREFIX . "forumpermission
		WHERE forumid = " . $vbulletin->GPC['forumid_from']
	);

	if ($db->num_rows($forumperms) == 0)
	{
		print_stop_message('no_permissions_set');
	}

	$copyperms = array();
	while ($perm = $db->fetch_array($forumperms))
	{
		$copyperms["$perm[usergroupid]"] = $perm['forumpermissions'];
	}

	$permscache = array();
	if (!$vbulletin->GPC['overwritedupes_forum'] OR !$vbulletin->GPC['overwriteinherited_forum'])
	{
		// query forum permissions
		$forumpermissions = $db->query_read("
			SELECT usergroupid, forum.forumid, IF(forumpermission.forumid = forum.forumid, 0, 1) AS inherited
			FROM " . TABLE_PREFIX . "forum AS forum, " . TABLE_PREFIX . "forumpermission AS forumpermission
			WHERE FIND_IN_SET(forumpermission.forumid, forum.parentlist)
		");
		// make permission cache
		while ($fperm = $db->fetch_array($forumpermissions))
		{
			$permscache["$fperm[forumid]"]["$fperm[usergroupid]"] = $fperm['inherited'];
		}
	}

	foreach ($vbulletin->GPC['forumlist'] AS $forumid_to => $confirm)
	{
		$forumid_to = intval($forumid_to);
		if ($forumid_to == $vbulletin->GPC['forumid_from'] OR !$confirm)
		{
			continue;
		}
		foreach ($copyperms AS $usergroupid => $permissions)
		{
			if (!$vbulletin->GPC['overwritedupes_forum'] AND isset($permscache["$forumid_to"]["$usergroupid"]) AND $permscache["$forumid_to"]["$usergroupid"] == 0)
			{
				continue;
			}
			if (!$vbulletin->GPC['overwriteinherited_forum'] AND $permscache["$forumid_to"]["$usergroupid"] == 1)
			{
				continue;
			}
			/*insert query*/
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "forumpermission
				(forumid, usergroupid, forumpermissions)
				VALUES ($forumid_to, $usergroupid, $permissions)
			");
		}
	}

	build_forum_permissions();

	define('CP_REDIRECT', 'forumpermission.php?do=modify');
	print_stop_message('duplicated_permissions_successfully');
}

// ###################### Start quick edit #######################
if ($_REQUEST['do'] == 'quickedit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'orderby' => TYPE_STR
	));

	print_form_header('forumpermission', 'doquickedit');
	print_table_header($vbphrase['permissions_quick_editor'], 4);
	print_cells_row(array(
		'<input type="checkbox" name="allbox" title="' . $vbphrase['check_all'] . '" onclick="js_check_all(this.form);" />',
		"<a href=\"forumpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=quickedit&amp;orderby=forum\" title=\"" . $vbphrase['order_by_forum'] . "\">" . $vbphrase['forum'] . "</a>",
		"<a href=\"forumpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=quickedit&amp;orderby=usergroup\" title=\"" . $vbphrase['order_by_usergroup'] . "\">" . $vbphrase['usergroup'] . "</a>",
		$vbphrase['controls']
	), 1);
	$forumperms = $db->query_read("
		SELECT forumpermissionid, usergroup.title AS ug_title, forum.title AS forum_title
		FROM " . TABLE_PREFIX . "forumpermission AS forumpermission,
		" . TABLE_PREFIX . "usergroup AS usergroup,
		" . TABLE_PREFIX . "forum AS forum
		WHERE forumpermission.usergroupid = usergroup.usergroupid AND
			forumpermission.forumid = forum.forumid
		" . iif($vbulletin->GPC['orderby'] == 'usergroup', 'ORDER BY ug_title, forum_title', 'ORDER BY forum_title, ug_title')
	);
	if ($db->num_rows($forumperms) > 0)
	{
		while ($perm = $db->fetch_array($forumperms))
		{
			print_cells_row(array("<input type=\"checkbox\" name=\"permission[$perm[forumpermissionid]]\" value=\"1\" tabindex=\"1\" />", $perm['forum_title'], $perm['ug_title'], construct_link_code($vbphrase['edit'], "forumpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;fp=$perm[forumpermissionid]")));
		}
		print_submit_row($vbphrase['delete_selected_permissions'], $vbphrase['reset'], 4);
	}
	else
	{
		print_description_row($vbphrase['nothing_to_do'], 0, 4, '', 'center');
		print_table_footer();
	}
}

// ###################### Start do quick edit #######################
if ($_POST['do'] == 'doquickedit')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'permission' => TYPE_ARRAY
	));

	if (sizeof($vbulletin->GPC['permission'])  == 0)
	{
		print_stop_message('nothing_to_do');
	}

	$removeids = '0';
	foreach ($vbulletin->GPC['permission'] AS $permissionid => $confirm)
	{
		if ($confirm == 1)
		{
			$removeids .= ", " . intval($permissionid);
		}
	}

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "forumpermission WHERE forumpermissionid IN ($removeids)");

	build_forum_permissions();

	define('CP_REDIRECT', 'forumpermission.php?do=modify');
	print_stop_message('deleted_forum_permissions_successfully');
}

// ###################### Start quick forum setup #######################
if ($_REQUEST['do'] == 'quickforum')
{
	$usergrouplist = array();
	$usergroups = $db->query_read("SELECT usergroupid, title FROM " . TABLE_PREFIX . "usergroup ORDER BY title");
	while ($usergroup = $db->fetch_array($usergroups))
	{
		$usergrouplist[] = "<input type=\"checkbox\" name=\"usergrouplist[$usergroup[usergroupid]]\" id=\"usergrouplist_$usergroup[usergroupid]\" value=\"1\" tabindex=\"1\" /><label for=\"usergrouplist_$usergroup[usergroupid]\">$usergroup[title]</label>";
	}
	$usergrouplist = implode('<br />', $usergrouplist);

	print_form_header('forumpermission', 'doquickforum');
	print_table_header($vbphrase['quick_forum_permission_setup']);
	print_forum_chooser($vbphrase['apply_permissions_to_forum'], 'forumid', -1);
	print_label_row($vbphrase['apply_permissions_to_usergroup'], "<span class=\"smallfont\">$usergrouplist</span>", '', 'top', 'usergrouplist');
	print_description_row($vbphrase['permission_overwrite_notice']);

	print_table_break();
	print_forum_permission_rows($vbphrase['permissions']);
	print_submit_row();
}

// ###################### Start do quick forum #######################
if ($_POST['do'] == 'doquickforum')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usergrouplist'		=> TYPE_ARRAY,
		'forumid'			=> TYPE_INT,
		'forumpermission'	=> TYPE_ARRAY_INT
	));

	if (sizeof($vbulletin->GPC['usergrouplist']) == 0)
	{
		print_stop_message('invalid_usergroup_specified');
	}

	require_once(DIR . '/includes/functions_misc.php');
	$permbits = convert_array_to_bits($vbulletin->GPC['forumpermission'], $vbulletin->bf_ugp_forumpermissions, 1);

	foreach ($vbulletin->GPC['usergrouplist'] AS $usergroupid => $confirm)
	{
		if ($confirm == 1)
		{
			$usergroupid = intval($usergroupid);
			/*insert query*/
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "forumpermission
					(forumid, usergroupid, forumpermissions)
				VALUES
					(" . $vbulletin->GPC['forumid'] . ", $usergroupid, $permbits)
			");

			($hook = vBulletinHook::fetch_hook('admin_fperms_doquickforum')) ? eval($hook) : false;
		}
	}

	build_forum_permissions();

	define('CP_REDIRECT', 'forumpermission.php?do=modify&f=' . $vbulletin->GPC['forumid']);
	print_stop_message('saved_forum_permissions_successfully');
}

// ###################### Start quick set #######################
if ($_REQUEST['do'] == 'quickset')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'type'		=> TYPE_STR,
		'forumid'	=> TYPE_INT
	));

	verify_cp_sessionhash();

	if (!$vbulletin->GPC['forumid'])
	{
		print_stop_message('invalid_forum_specified');
	}

	switch ($vbulletin->GPC['type'])
	{
		case 'reset':
			$db->query_write("DELETE FROM " . TABLE_PREFIX . "forumpermission WHERE forumid = " . $vbulletin->GPC['forumid']);
			break;

		case 'deny':
			$groups = $db->query_read("SELECT usergroupid FROM " . TABLE_PREFIX . "usergroup");
			while ($group = $db->fetch_array($groups))
			{
				/*insert query*/
				$db->query_write("
					REPLACE INTO " . TABLE_PREFIX . "forumpermission
					(
						forumid,
						usergroupid,
						forumpermissions
					)
					VALUES
					(
						" . $vbulletin->GPC['forumid'] . ",
						$group[usergroupid],
						0
					)
				");
			}
			break;

		default:
			print_stop_message('invalid_quick_set_action');
	}

	build_forum_permissions();

	define('CP_REDIRECT', 'forumpermission.php?do=modify&f=' . $vbulletin->GPC['forumid']);
	print_stop_message('saved_forum_permissions_successfully');
}

// ###################### Start fpgetstyle #######################
function fetch_forumpermission_style($permissions)
{
	global $vbulletin;

	if (!($permissions & $vbulletin->bf_ugp_forumpermissions['canview']))
	{
		return " style=\"list-style-type:circle;\"";
	}
	else
	{
		return '';
	}
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	print_form_header('', '');
	print_table_header($vbphrase['additional_functions']);
	print_description_row("<b><a href=\"forumpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=duplicate\">" . $vbphrase['permission_duplication_tools'] . "</a> | <a href=\"forumpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=quickedit\">" . $vbphrase['permissions_quick_editor'] . "</a> | <a href=\"forumpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=quickforum\">" . $vbphrase['quick_forum_permission_setup'] . "</a></b>", 0, 2, '', 'center');
	print_table_footer();

	print_form_header('', '');
	print_table_header($vbphrase['forum_permissions']);
	print_description_row('
		<div class="darkbg" style="border: 2px inset"><ul class="darkbg">
		<li><b>' . $vbphrase['color_key'] . '</b></li>
		<li class="col-g">' . $vbphrase['standard_using_default_usergroup_permissions'] . '</li>
		<li class="col-c">' . $vbphrase['customized_using_custom_permissions_for_this_usergroup'] . '</li>
		<li class="col-i">' . $vbphrase['inherited_using_custom_permissions_inherited_from_a_parent_forum'] . '</li>
		</ul></div>
	');
	print_table_footer();

	require_once(DIR . '/includes/functions_forumlist.php');

	// get forum orders
	cache_ordered_forums(0, 1);

	// get moderators
	cache_moderators();

	// query forum permissions
	$fpermscache = array();
	$forumpermissions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "forumpermission");
	while ($fp = $db->fetch_array($forumpermissions))
	{
		$fpermscache["$fp[forumid]"]["$fp[usergroupid]"] = $fp;
	}

	// get usergroup default permissions
	$permissions = array();
	foreach($vbulletin->usergroupcache AS $usergroupid => $usergroup)
	{
		$permissions["$usergroupid"] = $usergroup['forumpermissions'];
	}

?>
<center>
<div class="tborder" style="width: 89%">
<div class="alt1" style="padding: 8px">
<div class="darkbg" style="padding: 4px; border: 2px inset; text-align: <?php echo vB_Template_Runtime::fetchStyleVar('left'); ?>">
<?php

	// run the display function
	if ($vbulletin->options['cp_collapse_forums'])
	{
?>
	<script type="text/javascript">
	<!--
	function js_forum_jump(forumid)
	{
		if (forumid > 0)
		{
			window.location = 'forumpermission.php?do=modify&f=' + forumid;
		}
	}
	-->
	</script>
		<?php
		$vbulletin->input->clean_array_gpc('g', array('forumid' => TYPE_INT));
		define('ONLYID', (!empty($vbulletin->GPC['forumid']) ? $vbulletin->GPC['forumid'] : $vbulletin->GPC['f']));

		$select = '<div align="center"><select name="forumid" id="sel_foruid" tabindex="1" class="bginput" onchange="js_forum_jump(this.options[selectedIndex].value);">';
		$select .= construct_forum_chooser(ONLYID, true);
		$select .= "</select></div>\n";
		echo $select;

		print_forums($permissions, array(), -1);
	}
	else
	{
		print_forums($permissions, array(), -1);
	}

?>
</div>
</div>
</div>
</center>
<?php

}

// ###################### Start displayforums #######################
function print_forums($permissions, $inheritance = array(), $parentid = -1, $indent = '	')
{
	global $vbulletin, $permscache;
	global $imodcache, $fpermscache, $vbphrase;

	// check to see if this forum actually exists / has children
	if (empty($vbulletin->iforumcache["$parentid"]))
	{
		return;
	}

	foreach ($vbulletin->iforumcache["$parentid"] AS $forumid)
	{
		if (!defined('ONLYID'))
		{
			echo "$indent<ul class=\"lsq\">\n";
		}
			// get current forum info
			$forum =& $vbulletin->forumcache["$forumid"];

			// make a copy of the current permissions set up
			$perms = $permissions;

			// make a copy of the inheritance set up
			$inherit = $inheritance;

			if ($forumid == ONLYID)
			{
				echo "$indent<ul class=\"lsq\">\n";
			}

			// echo forum title and links
			if ($forumid == ONLYID OR !defined('ONLYID'))
			{
				echo "$indent<li><b><a name=\"forum$forumid\" href=\"forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;f=$forumid\">$forum[title]</a> <span class=\"smallfont\">(" . construct_link_code($vbphrase['reset'], "forumpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=quickset&amp;type=reset&amp;f=$forumid&amp;hash=" . CP_SESSIONHASH) . construct_link_code($vbphrase['deny_all'], "forumpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=quickset&amp;type=deny&amp;f=$forumid&amp;hash=" . CP_SESSIONHASH) . ")</span></b>";

				// get moderators
				if (is_array($imodcache["$forumid"]))
				{
					echo "<span class=\"smallfont\"><br /> - <i>" . $vbphrase['moderators'] . ":";
					foreach($imodcache["$forumid"] AS $moderator)
					{
						// moderator username and links
						echo " <a href=\"moderator.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;moderatorid=$moderator[moderatorid]\">$moderator[username]</a>";
					}
					echo "</i></span>";
				}

				echo "$indent\t<ul class=\"usergroups\">\n";
			}
			foreach($vbulletin->usergroupcache AS $usergroupid => $usergroup)
			{
				if ($inherit["$usergroupid"] == 'col-c')
				{
					$inherit["$usergroupid"] = 'col-i';
				}

				// if there is a custom permission for the current usergroup, use it
				if (isset($fpermscache["$forumid"]["$usergroupid"]))
				{
					$inherit["$usergroupid"] = 'col-c';
					$perms["$usergroupid"] = $fpermscache["$forumid"]["$usergroupid"]['forumpermissions'];
					$fplink = 'fp=' . $fpermscache["$forumid"]["$usergroupid"]['forumpermissionid'];
				}
				else
				{
					$fplink = "f=$forumid&amp;u=$usergroupid";
				}

				// work out display style
				$liStyle = '';
				if (isset($inherit["$usergroupid"]))
				{
					$liStyle = " class=\"$inherit[$usergroupid]\"";
				}
				if (!($perms["$usergroupid"] & $vbulletin->bf_ugp_forumpermissions['canview']))
				{
					$liStyle .= " style=\"list-style:circle\"";
				}
				if ($forumid == ONLYID OR !defined('ONLYID'))
				{
					echo "$indent\t<li$liStyle>" . construct_link_code($vbphrase['edit'], "forumpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;$fplink") . $usergroup['title'] . "</li>\n";
				}
			}
			if ($forumid == ONLYID OR !defined('ONLYID'))
			{
				echo "$indent\t</ul><br />\n";
			}

			if ($forumid == ONLYID AND defined('ONLYID'))
			{
				echo "$indent</li>\n";
				echo "$indent</ul>\n";
				return;
			}
			print_forums($perms, $inherit, $forumid, "$indent	");
			if ($forumid == ONLYID OR !defined('ONLYID'))
			{
				echo "$indent</li>\n";
			}
		unset($inherit);
		if ($forumid == ONLYID OR !defined('ONLYID'))
		{
			echo "$indent</ul>\n";
		}

		if ($forum['parentid'] == -1 AND !defined('ONLYID'))
		{
			echo "<hr size=\"1\" />\n";
		}
	}
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 46627 $
|| ####################################################################
\*======================================================================*/
?>
