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
define('CVS_REVISION', '$RCSfile$ - $Revision: 62098 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cppermission');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['administrator_permissions_manager']);

if (!in_array($vbulletin->userinfo['userid'], preg_split('#\s*,\s*#s', $vbulletin->config['SpecialUsers']['superadministrators'], -1, PREG_SPLIT_NO_EMPTY)))
{
	print_stop_message('sorry_you_are_not_allowed_to_edit_admin_permissions');
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'userid' => TYPE_INT
));

if ($vbulletin->GPC['userid'])
{
	$user = $db->query_first("
		SELECT administrator.*, IF(administrator.userid IS NULL, 0, 1) AS isadministrator,
			user.userid, user.username
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "administrator AS administrator ON(administrator.userid = user.userid)
		WHERE user.userid = " . $vbulletin->GPC['userid']
	);

	if (!$user)
	{
		print_stop_message('no_matches_found');
	}
	else if (!$user['isadministrator'])
	{
		// should this user have an administrator record??
		$userinfo = fetch_userinfo($user['userid']);
		cache_permissions($userinfo);
		if ($userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
		{
			$admindm =& datamanager_init('Admin', $vbulletin, ERRTYPE_SILENT);
			$admindm->set('userid', $userinfo['userid']);
			$admindm->save();
			unset($admindm);
		}
		else
		{
			print_stop_message('invalid_user_specified');
		}
	}

	$admindm =& datamanager_init('Admin', $vbulletin, ERRTYPE_CP);
	$admindm->set_existing($user);
}
else
{
	$user = array();
}

require_once(DIR . '/includes/class_bitfield_builder.php');
if (vB_Bitfield_Builder::build(false) !== false)
{
	$myobj =& vB_Bitfield_Builder::init();
}
else
{
	echo "<strong>error</strong>\n";
	print_r(vB_Bitfield_Builder::fetch_errors());
}
foreach ($myobj->data['ugp']['adminpermissions'] AS $title => $values)
{
	// don't show settings that have a group for the usergroup page
	if (empty($values['group']))
	{
		$ADMINPERMISSIONS["$title"] = $values['value'];
		$permsphrase["$title"] = $vbphrase["$values[phrase]"];
	}
}

$vbulletin->input->clean_array_gpc('p', array(
	'oldpermissions' 	 => TYPE_INT,
	'adminpermissions' => TYPE_ARRAY_INT
));

require_once(DIR . '/includes/functions_misc.php');
log_admin_action(iif($user, "user id = $user[userid] ($user[username])" . iif($_POST['do'] == 'update', " (" . $vbulletin->GPC['oldpermissions'] ." &raquo; " . convert_array_to_bits($vbulletin->GPC['adminpermissions'], $ADMINPERMISSIONS) . ")")));

// #############################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// #############################################################################

if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'cssprefs'      => TYPE_STR,
		'dismissednews' => TYPE_STR
	));

	foreach ($vbulletin->GPC['adminpermissions'] AS $key => $value)
	{
		$admindm->set_bitfield('adminpermissions', $key, $value);
	}

	($hook = vBulletinHook::fetch_hook('admin_permissions_process')) ? eval($hook) : false;

	$admindm->set('cssprefs', $vbulletin->GPC['cssprefs']);
	$admindm->set('dismissednews', $vbulletin->GPC['dismissednews']);
	$admindm->save();

	define('CP_REDIRECT', "adminpermissions.php?" . $vbulletin->session->vars['sessionurl'] . "#user$user[userid]");
	vB_Cache::instance()->event('permissions_' . $vbulletin->GPC['userid']);
	print_stop_message('saved_administrator_permissions_successfully');
}

// #############################################################################

if ($_REQUEST['do'] == 'edit')
{
	echo "<p align=\"center\">{$vbphrase['give_admin_access_arbitrary_html']}</p>";
	print_form_header('adminpermissions', 'update');
	construct_hidden_code('userid', $vbulletin->GPC['userid']);
	construct_hidden_code('oldpermissions', $user['adminpermissions']);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['administrator_permissions'], $user['username'], $user['userid']));
	print_label_row("$vbphrase[administrator]: <a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=" . $vbulletin->GPC['userid'] . "\">$user[username]</a>", '<div align="' . vB_Template_Runtime::fetchStyleVar('right') .'"><input type="button" class="button" value=" ' . $vbphrase['all_yes'] . ' " onclick="js_check_all_option(this.form, 1);" /> <input type="button" class="button" value=" ' . $vbphrase['all_no'] . ' " onclick="js_check_all_option(this.form, 0);" /></div>', 'thead');

	foreach (convert_bits_to_array($user['adminpermissions'], $ADMINPERMISSIONS) AS $field => $value)
	{
		print_yes_no_row(($permsphrase["$field"] == '' ? $vbphrase['n_a'] : $permsphrase["$field"]), "adminpermissions[$field]", $value);
	}

	($hook = vBulletinHook::fetch_hook('admin_permissions_form')) ? eval($hook) : false;

	print_select_row($vbphrase['control_panel_style_choice'], 'cssprefs', array_merge(array('' => "($vbphrase[default])"), fetch_cpcss_options()), $user['cssprefs']);
	print_input_row($vbphrase['dismissed_news_item_ids'], 'dismissednews', $user['dismissednews']);

	print_submit_row();
}

// #############################################################################

if ($_REQUEST['do'] == 'modify')
{
	print_form_header('adminpermissions', 'edit');
	print_table_header($vbphrase['administrator_permissions'], 3);

	$users = $db->query_read("
		SELECT user.username, usergroupid, membergroupids, infractiongroupids, administrator.*
		FROM " . TABLE_PREFIX . "administrator AS administrator
		INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
		ORDER BY user.username
	");
	while ($user = $db->fetch_array($users))
	{
		$perms = fetch_permissions(0, $user['userid'], $user);
		if ($perms['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
		{
			print_cells_row(array(
				"<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$user[userid]\" name=\"user$user[userid]\"><b>$user[username]</b></a>",
				'-',
				construct_link_code($vbphrase['view_control_panel_log'], "adminlog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&script=&u=$user[userid]") .
				construct_link_code($vbphrase['edit_permissions'], "adminpermissions.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$user[userid]")
			), 0, '', 0);
		}
	}

	print_table_footer();
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 62098 $
|| ####################################################################
\*======================================================================*/
?>
