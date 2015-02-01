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
define('CVS_REVISION', '$RCSfile$ - $Revision: 40911 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('user', 'cpuser', 'infraction', 'infractionlevel', 'banning');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'infractionlevelid' => TYPE_INT,
	'infractiongroupid' => TYPE_UINT,
	'infractionbanid'   => TYPE_UINT,
));
log_admin_action(!empty($vbulletin->GPC['infractionlevelid']) ? 'infractionlevel id = ' . $vbulletin->GPC['infractionlevelid'] : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['user_infraction_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start add #######################
if ($_REQUEST['do'] == 'editlevel')
{
	print_form_header('admininfraction', 'updatelevel');
	if (!empty($vbulletin->GPC['infractionlevelid']))
	{
		$infraction = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "infractionlevel WHERE infractionlevelid = " . $vbulletin->GPC['infractionlevelid']);

		$title = 'infractionlevel' . $infraction['infractionlevelid'] . '_title';

		if ($phrase = $db->query_first("
			SELECT text
			FROM " . TABLE_PREFIX . "phrase
			WHERE languageid = 0 AND
				fieldname = 'infractionlevel' AND
				varname = '$title'
		"))
		{
			$infraction['title'] = $phrase['text'];
			$infraction['titlevarname'] = 'infractionlevel' . $infraction['infractionlevelid'] . '_title';
		}
		if ($infraction['period'] == 'N')
		{
			$infraction['expires'] = '';
		}

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['user_infraction'], htmlspecialchars_uni($infraction['title']), $vbulletin->GPC['infractionlevelid']), 2, 0);
		construct_hidden_code('infractionlevelid', $vbulletin->GPC['infractionlevelid']);
	}
	else
	{
		$infraction = array(
			'warning' => 1,
			'expires' => 10,
			'period'  => 'D',
			'points'  => 1,
			'extend'  => 0,
		);
		print_table_header($vbphrase['add_new_user_infraction_level']);
	}

	if ($infraction['title'])
	{
		print_input_row($vbphrase['title'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=infractionlevel&varname=$title&t=1", 1)  . '</dfn>', 'title', $infraction['title']);
	}
	else
	{
		print_input_row($vbphrase['title'], 'title');
	}

	$periods = array(
		'H' => $vbphrase['hours'],
		'D' => $vbphrase['days'],
		'M' => $vbphrase['months'],
		'N' => $vbphrase['never'],
	);
	$input = '<input type="text" class="bginput" name="expires" size="5" dir="ltr" tabindex="1" value="' . $infraction['expires'] . '"' . ($vbulletin->debug ? ' title="name=&quot;expires&quot;"' : '') . " />\r\n";
	$input .= '<select name="period" class="bginput" tabindex="1"' . ($vbulletin->debug ? ' title="name=&quot;period&quot;"' : '') . '>' . construct_select_options($periods, $infraction['period']) . '</select>';

	print_label_row($vbphrase['expires'], $input, '', 'top', 'expires');
	print_input_row($vbphrase['points'], 'points', $infraction['points'], true, 5);
	print_yes_no_row($vbphrase['warning'], 'warning', $infraction['warning']);
	print_yes_no_row($vbphrase['extend'], 'extend', $infraction['extend']);

	print_submit_row($vbphrase['save']);

}

// ###################### Start do update #######################
if ($_POST['do'] == 'updatelevel')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'title'   => TYPE_STR,
		'points'  => TYPE_UINT,
		'expires' => TYPE_UINT,
		'period'  => TYPE_NOHTML,
		'warning' => TYPE_BOOL,
		'extend'  => TYPE_BOOL,
	));

	if (empty($vbulletin->GPC['title']) OR (empty($vbulletin->GPC['expires']) AND $vbulletin->GPC['period'] != 'N'))
	{
		print_stop_message('please_complete_required_fields');
	}

	if (empty($vbulletin->GPC['infractionlevelid']))
	{
		$db->query_write("INSERT INTO " . TABLE_PREFIX . "infractionlevel (points) VALUES (0)");
		$vbulletin->GPC['infractionlevelid'] = $db->insert_id();
	}

	if ($vbulletin->GPC['period'] == 'N')
	{
		$vbulletin->GPC['expires'] = 0;
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "infractionlevel
		SET points = " . $vbulletin->GPC['points'] . ",
			expires = " . $vbulletin->GPC['expires'] . ",
			period = '" . $db->escape_string($vbulletin->GPC['period']) . "',
			warning = " . intval($vbulletin->GPC['warning']) . ",
			extend = " . intval($vbulletin->GPC['extend']) . "
		WHERE infractionlevelid = " . $vbulletin->GPC['infractionlevelid'] . "
	");

	/*insert_query*/
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "phrase
			(languageid, fieldname, varname, text, product, username, dateline, version)
		VALUES
			(0,
			'infractionlevel',
			'infractionlevel" . $vbulletin->GPC['infractionlevelid'] . "_title',
			'" . $db->escape_string($vbulletin->GPC['title']) . "',
			'vbulletin',
			'" . $db->escape_string($vbulletin->userinfo['username']) . "',
			" . TIMENOW . ",
			'" . $db->escape_string($vbulletin->options['templateversion']) . "')
	");

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	define('CP_REDIRECT', 'admininfraction.php?do=modify');
	print_stop_message('saved_infraction_level_successfully');

}
// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'removelevel')
{

	print_form_header('admininfraction', 'killlevel');
	construct_hidden_code('infractionlevelid', $vbulletin->GPC['infractionlevelid']);
	print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], htmlspecialchars_uni($vbphrase['infractionlevel' . $vbulletin->GPC['infractionlevelid'] . '_title'])));
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_infraction_level']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);

}

// ###################### Start Kill #######################

if ($_POST['do'] == 'killlevel')
{

	if ($phrase = $db->query_first("SELECT text FROM " . TABLE_PREFIX . "phrase WHERE text <> '' AND fieldname = 'infractionlevel' AND varname = 'infractionlevel" . $vbulletin->GPC['infractionlevelid'] . "_title' AND languageid IN (0," . intval($vbulletin->options['languageid']) . ") ORDER BY languageid DESC"))
	{
		$db->query_write("UPDATE " . TABLE_PREFIX . "infraction SET customreason = '" . $db->escape_string($phrase['text']) . "' WHERE infractionlevelid =" . $vbulletin->GPC['infractionlevelid']);
	}

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "infractionlevel WHERE infractionlevelid = " . $vbulletin->GPC['infractionlevelid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "phrase WHERE fieldname = 'infractionlevel' AND varname = 'infractionlevel" . $vbulletin->GPC['infractionlevelid'] . "_title'");

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	define('CP_REDIRECT', 'admininfraction.php?do=modify');
	print_stop_message('deleted_infraction_level_successfully');
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$infractions = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "infractionlevel
		ORDER BY points
	");

	?>
	<script type="text/javascript">
	function js_usergroup_jump(id, obj)
	{
		task = obj.options[obj.selectedIndex].value;
		switch (task)
		{
			case 'editlevel': window.location = "admininfraction.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=editlevel&infractionlevelid=" + id; break;
			case 'killlevel': window.location = "admininfraction.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=removelevel&infractionlevelid=" + id; break;
			case 'editgroup': window.location = "admininfraction.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=editgroup&infractiongroupid=" + id; break;
			case 'killgroup': window.location = "admininfraction.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=removegroup&infractiongroupid=" + id; break;
			case 'editban': window.location = "admininfraction.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=editbangroup&infractionbanid=" + id; break;
			case 'killban': window.location = "admininfraction.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=removebangroup&infractionbanid=" + id; break;
			default: return false; break;
		}
	}
	</script>
	<?php

	$options = array(
		'editlevel' => $vbphrase['edit'],
		'killlevel' => $vbphrase['delete'],
	);

	print_form_header('admininfraction', 'editlevel');
	print_table_header($vbphrase['user_infraction_levels'], 6);

	print_cells_row(array($vbphrase['title'], $vbphrase['points'], $vbphrase['expires'], $vbphrase['warning'], $vbphrase['extend'], $vbphrase['controls']), 1);

	while ($infraction = $db->fetch_array($infractions))
	{
		switch($infraction['period'])
		{
			case 'H':
				$period = 'x_hours';
				break;
			case 'D':
				$period = 'x_days';
				break;
			case 'M':
				$period = 'x_months';
				break;
			case 'N':
				$period = 'never';
				break;
		}
		$expires = construct_phrase($vbphrase["$period"], $infraction['expires']);
		print_cells_row(array(
			'<b>' . htmlspecialchars_uni($vbphrase['infractionlevel' . $infraction['infractionlevelid'] . '_title']) . '</b>',
			$infraction['points'],
			$expires,
			(empty($infraction['warning']) ? $vbphrase['no'] : $vbphrase['yes']),
			(empty($infraction['extend']) ? $vbphrase['no'] : $vbphrase['yes']),
			"\n\t<select name=\"i$infraction[infractionlevelid]\" onchange=\"js_usergroup_jump($infraction[infractionlevelid], this);\" class=\"bginput\">\n" . construct_select_options($options) . "\t</select>\n\t<input type=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_usergroup_jump($infraction[infractionlevelid], this.form.i$infraction[infractionlevelid]);\" />\n\t"
		));
	}

	print_submit_row($vbphrase['add_new_user_infraction_level'], 0, 6);

	$infractions = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "infractiongroup
		ORDER BY pointlevel
	");

	$options = array(
		'editgroup' => $vbphrase['edit'],
		'killgroup' => $vbphrase['delete'],
	);

	print_form_header('admininfraction', 'editgroup');
	print_table_header($vbphrase['user_infraction_groups'], 5);

	print_cells_row(array($vbphrase['primary_usergroup'], $vbphrase['override_usergroup'], $vbphrase['override_display'], $vbphrase['points'], $vbphrase['controls']), 1);

	while ($infraction = $db->fetch_array($infractions))
	{
		print_cells_row(array(
			($infraction['usergroupid'] == -1) ? $vbphrase['all_usergroups'] : "<a href=\"usergroup.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;usergroupid=$infraction[usergroupid]\" />" . $vbulletin->usergroupcache["$infraction[usergroupid]"]['title'] . "</a>",
			"<a href=\"usergroup.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;usergroupid=$infraction[orusergroupid]\" />" . $vbulletin->usergroupcache["$infraction[orusergroupid]"]['title'] . "</a>",
			($infraction['override'] ? $vbphrase['yes'] : $vbphrase['no']),
			$infraction['pointlevel'],
			"\n\t<select name=\"i$infraction[infractiongroupid]\" onchange=\"js_usergroup_jump($infraction[infractiongroupid], this);\" class=\"bginput\">\n" . construct_select_options($options) . "\t</select>\n\t<input type=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_usergroup_jump($infraction[infractiongroupid], this.form.i$infraction[infractiongroupid]);\" />\n\t"
		));
	}

	print_submit_row($vbphrase['add_new_user_infraction_group'], 0, 6);

	$options = array(
		'editban' => $vbphrase['edit'],
		'killban' => $vbphrase['delete'],
	);

	print_form_header('admininfraction', 'editbangroup');
	print_table_header($vbphrase['automatic_ban'], 6);
	print_cells_row(array($vbphrase['primary_usergroup'], $vbphrase['ban_usergroup'], $vbphrase['amount'], $vbphrase['method'], $vbphrase['ban_period'], $vbphrase['controls']), 1);

	$infractions = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "infractionban
		ORDER BY method, amount ASC
	");
	while($infraction = $db->fetch_array($infractions))
	{
		switch($infraction['period'])
		{
			case 'D_1':   $period = construct_phrase($vbphrase['x_days'], 1); break;
			case 'D_2':   $period = construct_phrase($vbphrase['x_days'], 2); break;
			case 'D_3':   $period = construct_phrase($vbphrase['x_days'], 3); break;
			case 'D_4':   $period = construct_phrase($vbphrase['x_days'], 4); break;
			case 'D_5':   $period = construct_phrase($vbphrase['x_days'], 5); break;
			case 'D_6':   $period = construct_phrase($vbphrase['x_days'], 6); break;
			case 'D_7':   $period = construct_phrase($vbphrase['x_days'], 7); break;
			case 'D_10':  $period = construct_phrase($vbphrase['x_days'], 10); break;
			case 'D_14':  $period = construct_phrase($vbphrase['x_weeks'], 2); break;
			case 'D_21':  $period = construct_phrase($vbphrase['x_weeks'], 3); break;
			case 'M_1':   $period = construct_phrase($vbphrase['x_months'], 1); break;
			case 'M_2':   $period = construct_phrase($vbphrase['x_months'], 2); break;
			case 'M_3':   $period = construct_phrase($vbphrase['x_months'], 3); break;
			case 'M_4':   $period = construct_phrase($vbphrase['x_months'], 4); break;
			case 'M_5':   $period = construct_phrase($vbphrase['x_months'], 5); break;
			case 'M_6':   $period = construct_phrase($vbphrase['x_months'], 6); break;
			case 'Y_1':   $period = construct_phrase($vbphrase['x_years'], 1); break;
			case 'Y_2':   $period = construct_phrase($vbphrase['x_years'], 2); break;
			case 'PERMA': $period = $vbphrase['permanent']; break;
			default: $period = '';
		}

		print_cells_row(array(
			($infraction['usergroupid'] == -1) ? $vbphrase['all_usergroups'] : "<a href=\"usergroup.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;usergroupid=$infraction[usergroupid]\" />" . $vbulletin->usergroupcache["$infraction[usergroupid]"]['title'] . "</a>",
			"<a href=\"usergroup.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;usergroupid=$infraction[banusergroupid]\" />" . $vbulletin->usergroupcache["$infraction[banusergroupid]"]['title'] . "</a>",
			$infraction['amount'],
			$vbphrase["$infraction[method]"],
			$period,
			"\n\t<select name=\"i$infraction[infractionbanid]\" onchange=\"js_usergroup_jump($infraction[infractionbanid], this);\" class=\"bginput\">\n" . construct_select_options($options) . "\t</select>\n\t<input type=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_usergroup_jump($infraction[infractionbanid], this.form.i$infraction[infractionbanid]);\" />\n\t"
		));
	}
	print_submit_row($vbphrase['add_new_automatic_ban'], 0, 6);

}

// ###################### Start Delete #######################
if ($_POST['do'] == 'killinfraction')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'infractionid' => TYPE_UINT,
		'pagenumber'   => TYPE_UINT,
		'orderby'      => TYPE_NOHTML,
		'perpage'      => TYPE_UINT,
		'status'       => TYPE_NOHTML,
		'userid'       => TYPE_UINT,
		'whoadded'     => TYPE_UINT,
		'startstamp'   => TYPE_UINT,
		'endstamp'     => TYPE_UINT,
	));

	if ($infractioninfo = verify_id('infraction', $vbulletin->GPC['infractionid'], 0, 1))
	{
		$infdata =& datamanager_init('Infraction', $vbulletin, ERRTYPE_STANDARD);
		$infdata->set_existing($infractioninfo);
		$infdata->setr_info('postinfo', $postinfo);
		$infdata->setr_info('userinfo', $userinfo);
		$infdata->delete();
		unset($infdata);
	}

	$args =
		 '&status=' . $vbulletin->GPC['status'] .
		 '&u=' . $vbulletin->GPC['userid'] .
		 '&whoadded=' . $vbulletin->GPC['whoadded'] .
		 '&startstamp=' . $vbulletin->GPC['startstamp'] .
		 '&endstamp=' . $vbulletin->GPC['endstamp'] .
		 '&pp=' . $vbulletin->GPC['perpage'] .
		 '&page=' . $vbulletin->GPC['pagenumber'] .
		 '&orderby=' . $vbulletin->GPC['orderby'] .
		 '&infractionlevelid=' . $vbulletin->GPC['infractionlevelid'];

	define('CP_REDIRECT', 'admininfraction.php?do=dolist' . $args);
	print_stop_message('deleted_infraction_successfully');
}

// ###################### Start Delete #######################
if ($_REQUEST['do'] == 'deleteinfraction')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'infractionid' => TYPE_UINT,
		'pagenumber'   => TYPE_UINT,
		'orderby'      => TYPE_NOHTML,
		'perpage'      => TYPE_UINT,
		'status'       => TYPE_NOHTML,
		'userid'       => TYPE_UINT,
		'whoadded'     => TYPE_UINT,
		'startstamp'   => TYPE_UINT,
		'endstamp'     => TYPE_UINT,
	));

	print_delete_confirmation('infraction', $vbulletin->GPC['infractionid'], 'admininfraction', 'killinfraction', '', array(
		'page'              => $vbulletin->GPC['pagenumber'],
		'orderby'           => $vbulletin->GPC['orderby'],
		'pp'                => $vbulletin->GPC['perpage'],
		'status'            => $vbulletin->GPC['status'],
		'u'                 => $vbulletin->GPC['userid'],
		'whoadded'          => $vbulletin->GPC['whoadded'],
		'startstamp'        => $vbulletin->GPC['startstamp'],
		'endstamp'          => $vbulletin->GPC['endstamp'],
		'infractionlevelid' => $vbulletin->GPC['infractionlevelid'],
	));
}

// ###################### Start Reversal #######################
if ($_POST['do'] == 'doreverse')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'infractionid' => TYPE_UINT,
		'reason'       => TYPE_STR,
		'pagenumber'   => TYPE_UINT,
		'orderby'      => TYPE_NOHTML,
		'perpage'      => TYPE_UINT,
		'status'       => TYPE_NOHTML,
		'userid'       => TYPE_UINT,
		'whoadded'     => TYPE_UINT,
		'startstamp'   => TYPE_UINT,
		'endstamp'     => TYPE_UINT,
	));

	if ($infractioninfo = $db->query_first("
		SELECT infraction.*
		FROM " . TABLE_PREFIX . "infraction AS infraction
		WHERE infractionid = " . $vbulletin->GPC['infractionid']
	))
	{
		if ($infractioninfo['action'] == 2)
		{
			print_stop_message('infraction_already_reversed');
		}

		$infdata =& datamanager_init('Infraction', $vbulletin, ERRTYPE_STANDARD);
		$infdata->set_existing($infractioninfo);
		$infdata->setr_info('postinfo', $postinfo);
		$infdata->setr_info('userinfo', $userinfo);
		$infdata->set('action', 2);
		$infdata->set('actionuserid', $vbulletin->userinfo['userid']);
		$infdata->set('actiondateline', TIMENOW);
		$infdata->set('actionreason', $vbulletin->GPC['reason']);
		$infdata->save();

		$args =
			 '&status=' . $vbulletin->GPC['status'] .
			 '&u=' . $vbulletin->GPC['userid'] .
			 '&whoadded=' . $vbulletin->GPC['whoadded'] .
			 '&startstamp=' . $vbulletin->GPC['startstamp'] .
			 '&endstamp=' . $vbulletin->GPC['endstamp'] .
			 '&pp=' . $vbulletin->GPC['perpage'] .
			 '&page=' . $vbulletin->GPC['pagenumber'] .
			 '&orderby=' . $vbulletin->GPC['orderby'] .
			 '&infractionlevelid=' . $vbulletin->GPC['infractionlevelid'];

		define('CP_REDIRECT', 'admininfraction.php?do=dolist' . $args);
		print_stop_message('reversed_infraction_successfully');
	}
	else
	{
		print_stop_message('no_matches_found');
	}
}

// ###################### Start Reversal #######################
if ($_REQUEST['do'] == 'reverse')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'infractionid' => TYPE_UINT,
		'pagenumber'   => TYPE_UINT,
		'orderby'      => TYPE_NOHTML,
		'perpage'      => TYPE_UINT,
		'status'       => TYPE_NOHTML,
		'userid'       => TYPE_UINT,
		'whoadded'     => TYPE_UINT,
		'startstamp'   => TYPE_UINT,
		'endstamp'     => TYPE_UINT
	));

	if ($infraction = $db->query_first("
		SELECT infraction.*,
			user.username AS whoadded_username,
			user2.username
		FROM " . TABLE_PREFIX . "infraction AS infraction
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (infraction.whoadded = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (infraction.userid = user2.userid)
		WHERE infractionid = " . $vbulletin->GPC['infractionid']
	))
	{
		if ($infraction['action'] == 2)
		{
			print_stop_message('infraction_already_reversed');
		}

		print_form_header('admininfraction', 'doreverse');
		print_table_header($vbphrase['reverse_infraction']);
		print_label_row($vbphrase['user_name'], $infraction['username']);
		print_label_row($vbphrase['left_by'],   $infraction['whoadded_username']);

		$title = !empty($vbphrase['infractionlevel' . $infraction['infractionlevelid'] . '_title']) ? $vbphrase['infractionlevel' . $infraction['infractionlevelid'] . '_title'] : (!empty($infraction['customreason']) ? unhtmlspecialchars($infraction['customreason']) : $vbphrase['n_a']);

		if ($infraction['points'])
		{
			print_label_row(($infraction['action'] == 0) ? construct_phrase($vbphrase['active_infraction_x_points'], $infraction['points']) : construct_phrase($vbphrase['expired_infraction_x_points'], $infraction['points']), htmlspecialchars_uni($title));
		}
		else
		{
			print_label_row(($infraction['action'] == 0) ? $vbphrase['active_warning'] : $vbphrase['expired_warning'], htmlspecialchars_uni($title));
		}
		if (!empty($infraction['reason']))
		{
			print_label_row($vbphrase['infraction_reason'], $infraction['reason']);
		}

		print_textarea_row($vbphrase['reversal_reason'] . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255) . '</dfn>', 'reason', '', 0, 40, 2);
		construct_hidden_code('infractionid', $vbulletin->GPC['infractionid']);
		construct_hidden_code('pp', $vbulletin->GPC['perpage']);
		construct_hidden_code('page', $vbulletin->GPC['pagenumber']);
		construct_hidden_code('orderby', $vbulletin->GPC['orderby']);
		construct_hidden_code('status', $vbulletin->GPC['status']);
		construct_hidden_code('userid', $vbulletin->GPC['userid']);
		construct_hidden_code('whoadded', $vbulletin->GPC['whoadded']);
		construct_hidden_code('startstamp', $vbulletin->GPC['startstamp']);
		construct_hidden_code('endstamp', $vbulletin->GPC['endstamp']);
		construct_hidden_code('infractionlevelid', $vbulletin->GPC['infractionlevelid']);
		print_submit_row();
	}
	else
	{
		print_stop_message('no_matches_found');
	}
}

// ###################### Start Details #######################
if ($_REQUEST['do'] == 'details')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'infractionid' => TYPE_UINT
	));

	if ($infraction = $db->query_first("
		SELECT infraction.*,
			user.username AS whoadded_username,
			user2.username,
			user3.username AS action_username
		FROM " . TABLE_PREFIX . "infraction AS infraction
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (infraction.whoadded = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (infraction.userid = user2.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user3 ON (infraction.actionuserid = user3.userid)
		WHERE infractionid = " . $vbulletin->GPC['infractionid']
	))
	{
		print_form_header('', '');
		print_table_header($vbphrase['view_infraction']);
		print_label_row($vbphrase['user_name'], $infraction['username']);
		print_label_row($vbphrase['left_by'],   $infraction['whoadded_username']);
		print_label_row($vbphrase['date'],  vbdate($vbulletin->options['logdateformat'], $infraction['dateline']));

		$title = !empty($vbphrase['infractionlevel' . $infraction['infractionlevelid'] . '_title']) ? $vbphrase['infractionlevel' . $infraction['infractionlevelid'] . '_title'] : (!empty($infraction['customreason']) ? unhtmlspecialchars($infraction['customreason']) : $vbphrase['n_a']);

		if ($infraction['points'])
		{
			print_label_row(($infraction['action'] == 0) ? construct_phrase($vbphrase['active_infraction_x_points'], $infraction['points']) : construct_phrase($vbphrase['expired_infraction_x_points'], $infraction['points']), htmlspecialchars_uni($title));
		}
		else
		{
			print_label_row(($infraction['action'] == 0) ? $vbphrase['active_warning'] : $vbphrase['expired_warning'], $title);
		}
		if ($infraction['action'] == 0)
		{
			print_label_row($vbphrase['expires'],  ($infraction['expires'] ? vbdate($vbulletin->options['logdateformat'], $infraction['expires']) : $vbphrase['never']));
		}
		else if ($infraction['action'] == 1)
		{
			print_label_row($vbphrase['expired'],  vbdate($vbulletin->options['logdateformat'], $infraction['actiondateline']));
		}
		if (!empty($infraction['note']))
		{
			print_label_row($vbphrase['administrative_note'], $infraction['note']);
		}

		if ($infraction['action'] == 2)
		{
			print_table_break();
			print_table_header($vbphrase['reversed']);
			print_label_row($vbphrase['username'], $infraction['action_username']);
			print_label_row($vbphrase['date'],  vbdate($vbulletin->options['logdateformat'], $infraction['actiondateline']));
			if ($infraction['actionreason'])
			{
				print_label_row($vbphrase['reversal_reason'], $infraction['actionreason']);
			}
		}

		print_table_footer();
	}
	else
	{
		print_stop_message('no_matches_found');
	}
}

if ($_REQUEST['do'] == 'list')
{
	$infractions = $db->query_read("
		SELECT COUNT(*) AS count, infractionlevelid
		FROM " . TABLE_PREFIX . "infraction
		GROUP BY infractionlevelid
		ORDER BY count DESC
	");

	if ($db->num_rows($infractions))
	{
		print_form_header('', '');
		print_table_header($vbphrase['infraction_statistics']);
		print_cells_row(array($vbphrase['title'], $vbphrase['infractions']), 1);
	}

	while ($infraction = $db->fetch_array($infractions))
	{
		$title = $infraction['infractionlevelid'] ? (!empty($vbphrase['infractionlevel' . $infraction['infractionlevelid'] . '_title']) ? $vbphrase['infractionlevel' . $infraction['infractionlevelid'] . '_title'] : $vbphrase['n_a']) : '<em>' . $vbphrase['custom_infraction'] . '</em>';
		print_label_row($title, construct_link_code($infraction['count'], "admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist&infractionlevelid=$infraction[infractionlevelid]&startstamp=1&endstamp=" . TIMENOW, false, '', true));
	}

	if ($db->num_rows($infractions))
	{
		print_table_footer();
	}
}

if ($_REQUEST['do'] == 'list' OR $_REQUEST['do'] == 'dolist')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'leftby'            => TYPE_NOHTML,
		'leftfor'           => TYPE_NOHTML,
		'userid'            => TYPE_UINT,
		'whoadded'          => TYPE_UINT,
		'pagenumber'        => TYPE_UINT,
		'perpage'           => TYPE_UINT,
		'orderby'           => TYPE_NOHTML,
		'start'             => TYPE_ARRAY_UINT,
		'end'               => TYPE_ARRAY_UINT,
		'startstamp'        => TYPE_UINT,
		'endstamp'          => TYPE_UINT,
		'status'            => TYPE_NOHTML,
		'infractionlevelid' => TYPE_INT,
	));

	$vbulletin->GPC['start'] = iif($vbulletin->GPC['startstamp'], $vbulletin->GPC['startstamp'], $vbulletin->GPC['start']);
	$vbulletin->GPC['end'] = iif($vbulletin->GPC['endstamp'], $vbulletin->GPC['endstamp'], $vbulletin->GPC['end']);

	if ($whoaddedinfo = verify_id('user', $vbulletin->GPC['whoadded'], 0, 1))
	{
		$vbulletin->GPC['leftby'] = $whoaddedinfo['username'];
	}
	else
	{
		$vbulletin->GPC['whoadded'] = 0;
	}

	if ($userinfo = verify_id('user', $vbulletin->GPC['userid'], 0, 1))
	{
		$vbulletin->GPC['leftfor'] = $userinfo['username'];
	}
	else
	{
		$vbulletin->GPC['userid'] = 0;
	}

	// Default View Values

	if (!$vbulletin->GPC['start'])
	{
		$vbulletin->GPC['start'] = TIMENOW - 3600 * 24 * 30;
	}

	if (!$vbulletin->GPC['end'])
	{
		$vbulletin->GPC['end'] = TIMENOW;
	}

	if (!$vbulletin->GPC['status'])
	{
		$vbulletin->GPC['status'] = 'all';
	}

	$statusoptions = array(
		'all'      => $vbphrase['all'],
		'active'   => $vbphrase['active'],
		'expired'  => $vbphrase['expired'],
		'reversed' => $vbphrase['reversed'],
	);

	$infractionlevels = array (-1 => $vbphrase['all']);
	$infractions = $db->query_read("
		SELECT infractionlevelid
		FROM " . TABLE_PREFIX . "infractionlevel
		ORDER BY points
	");
	while ($infraction = $db->fetch_array($infractions))
	{
		$infractionlevels["$infraction[infractionlevelid]"] = htmlspecialchars_uni($vbphrase['infractionlevel' . $infraction['infractionlevelid'] . '_title']);
	}
	$infractionlevels[0] = $vbphrase['custom_infraction'];

	print_form_header('admininfraction', 'dolist');
	print_table_header($vbphrase['view_infractions']);
	print_input_row($vbphrase['leftfor_infraction'], 'leftfor', $vbulletin->GPC['leftfor'], 0);
	print_input_row($vbphrase['leftby_infraction'], 'leftby', $vbulletin->GPC['leftby'], 0);
	print_select_row($vbphrase['status'], 'status', $statusoptions, $vbulletin->GPC['status']);
	print_select_row($vbphrase['infraction_level'], 'infractionlevelid', $infractionlevels, $_REQUEST['do'] == 'list' ? -1 : $vbulletin->GPC['infractionlevelid']);
	print_time_row($vbphrase['start_date'], 'start', $vbulletin->GPC['start'], false);
	print_time_row($vbphrase['end_date'], 'end', $vbulletin->GPC['end'], false);
	print_submit_row($vbphrase['go']);
}

// ###################### Start list #######################
if ($_REQUEST['do'] == 'dolist')
{
	require_once(DIR . '/includes/functions_misc.php');
	if ($vbulletin->GPC['startstamp'])
	{
		$vbulletin->GPC['start'] = $vbulletin->GPC['startstamp'];
	}
	else
	{
		$vbulletin->GPC['start'] = vbmktime(0, 0, 0, $vbulletin->GPC['start']['month'], $vbulletin->GPC['start']['day'], $vbulletin->GPC['start']['year']);
	}

	if ($vbulletin->GPC['endstamp'])
	{
		$vbulletin->GPC['end'] = $vbulletin->GPC['endstamp'];
	}
	else
	{
		$vbulletin->GPC['end'] = vbmktime(23, 59, 59, $vbulletin->GPC['end']['month'], $vbulletin->GPC['end']['day'], $vbulletin->GPC['end']['year']);
	}

	if ($vbulletin->GPC['start'] >= $vbulletin->GPC['end'])
	{
		define('CP_BACKURL', '');
		print_stop_message('start_date_after_end');
	}

	if ($vbulletin->GPC['leftby'])
	{
		if (!$leftby_user = $db->query_first("
			SELECT userid
			FROM " . TABLE_PREFIX . "user
			WHERE username = '" . $db->escape_string($vbulletin->GPC['leftby']) . "'
		"))
		{
			define('CP_BACKURL', '');
			print_stop_message('could_not_find_user_x', $vbulletin->GPC['leftby']);
		}
		$vbulletin->GPC['whoadded'] = $leftby_user['userid'];
	}

	if ($vbulletin->GPC['leftfor'])
	{
		if (!$leftfor_user = $db->query_first("
			SELECT userid
			FROM " . TABLE_PREFIX . "user
			WHERE username = '" . $db->escape_string($vbulletin->GPC['leftfor']) . "'
		"))
		{
			define('CP_BACKURL', '');
			print_stop_message('could_not_find_user_x', $vbulletin->GPC['leftfor']);
		}
		$vbulletin->GPC['userid'] = $leftfor_user['userid'];
	}

	$condition = "1 = 1";
	if ($vbulletin->GPC['whoadded'])
	{
		$condition .= " AND infraction.whoadded = " . $vbulletin->GPC['whoadded'];
	}
	if ($vbulletin->GPC['userid'])
	{
		$condition .= " AND infraction.userid = " . $vbulletin->GPC['userid'];
	}
	if ($vbulletin->GPC['start'])
	{
		$condition .= " AND infraction.dateline >= " . $vbulletin->GPC['start'];
	}
	if ($vbulletin->GPC['end'])
	{
		$condition .= " AND infraction.dateline <= " . $vbulletin->GPC['end'];
	}
	if ($vbulletin->GPC['infractionlevelid'] != -1)
	{
		$condition .= " AND infraction.infractionlevelid = " . intval($vbulletin->GPC['infractionlevelid']);
	}

	switch ($vbulletin->GPC['status'])
	{
		case 'active': $condition .= " AND action = 0"; break;
		case 'expired': $condition .= " AND action = 1"; break;
		case 'reversed': $condition .= " AND action = 2"; break;
	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 15;
	}

	$counter = $db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "infraction AS infraction
		WHERE $condition
	");
	if (!($totalinf = $counter['total']))
	{
		define('CP_BACKURL', '');
		print_stop_message('no_matches_found');
	}

	switch($vbulletin->GPC['orderby'])
	{
		case 'points':          $orderby = 'points DESC'; break;
		case 'expires':         $orderby = 'action, expires'; break;
		case 'username':        $orderby = 'post.username'; break;
		case 'leftby_username': $orderby = 'leftby_username'; break;
		default:
			$orderby = 'infraction.dateline DESC';
			$vbulletin->GPC['orderby'] = '';
	}

	sanitize_pageresults($totalinf, $vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage']);
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	$totalpages = ceil($totalinf / $vbulletin->GPC['perpage']);

	$args =
		 '&status=' . $vbulletin->GPC['status'] .
		 '&u=' . $vbulletin->GPC['userid'] .
		 '&whoadded=' . $vbulletin->GPC['whoadded'] .
		 '&startstamp=' . $vbulletin->GPC['start'] .
		 '&endstamp=' . $vbulletin->GPC['end'] .
		 '&pp=' . $vbulletin->GPC['perpage'] .
		 '&page=' . $vbulletin->GPC['pagenumber'] .
		 '&infractionlevelid=' . $vbulletin->GPC['infractionlevelid'] .
		 '&orderby=';

	$infractions = $db->query_read("
		SELECT infraction.*,
			user2.username,
			user.username AS leftby_username,
			IF(ISNULL(post.postid) AND infraction.postid != 0, 1, 0) AS postdeleted,
			post.threadid AS postthreadid
		FROM " . TABLE_PREFIX . "infraction AS infraction
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (infraction.whoadded = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (infraction.userid = user2.userid)
		LEFT JOIN " . TABLE_PREFIX . "post AS post ON (infraction.postid = post.postid)
		WHERE $condition
		ORDER BY $orderby
		LIMIT $startat, " . $vbulletin->GPC['perpage']
	);

	if ($db->num_rows($infractions))
	{
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" tabindex=\"1\" value=\"&laquo; " . $vbphrase['first_page'] . "\" onclick=\"window.location='admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . $vbulletin->GPC['orderby'] . "&page=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" tabindex=\"1\" value=\"&lt; " . $vbphrase['prev_page'] . "\" onclick=\"window.location='admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . $vbulletin->GPC['orderby'] . "&page=$prv'\">";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['next_page'] . " &gt;\" onclick=\"window.location='admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . $vbulletin->GPC['orderby'] . "&page=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['last_page'] . " &raquo;\" onclick=\"window.location='admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . $vbulletin->GPC['orderby'] . "&page=$totalpages'\">";
		}

		print_form_header('admininfraction', 'remove');
		print_table_header(construct_phrase($vbphrase['infraction_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 8);

		$headings = array();
		$headings[] = "<a href=\"admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . "\" title=\"" . $vbphrase['order_by_username'] . "\">" . $vbphrase['user_name'] . "</a>";
		$headings[] = "<a href=\"admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . "leftby_username\" title=\"" . $vbphrase['order_by_username'] . "\">" . $vbphrase['left_by'] . "</a>";
		$headings[] = "<a href=\"admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . "date\" title=\"" . $vbphrase['order_by_date'] . "\">" . $vbphrase['date'] . "</a>";
		$headings[] = $vbphrase['infraction_type'];
		$headings[] = "<a href=\"admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . "points\" title=\"" . $vbphrase['order_by_points'] . "\">" . $vbphrase['points'] . "</a>";
		$headings[] = "<a href=\"admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . "expires\" title=\"" . $vbphrase['order_by_expiration'] . "\">" . $vbphrase['expires'] . "</a>";
		$headings[] = $vbphrase['post'];
		$headings[] = $vbphrase['controls'];
		print_cells_row($headings, 1);

		while ($infraction = $db->fetch_array($infractions))
		{
			$cell = array();
			$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$infraction[userid]\"><b>$infraction[username]</b></a>";
			$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$infraction[whoadded]\"><b>$infraction[leftby_username]</b></a>";
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $infraction['dateline']) . '</span>';
			if ($infraction['points'])
			{
				$cell[] = $vbphrase['infraction'];
				$cell[] = $infraction['points'];
				switch($infraction['action'])
				{
					case 0:
						if ($infraction['expires'] == 0)
						{
							$cell[] = $vbphrase['never'];
						}
						else
						{
							$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $infraction['expires']) . '</span>';
						}
						break;
					case 1:
						$cell[] = $vbphrase['expired'];
						break;
					case 2:
						$cell[] = $vbphrase['reversed'];
						break;
				}
			}
			else
			{
				$cell[] = $vbphrase['warning'];
				$cell[] = $infraction['points'];
				switch($infraction['action'])
				{
					case 0:
						if ($infraction['expires'] == 0)
						{
							$cell[] = $vbphrase['never'];
						}
						else
						{
							$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $infraction['expires']) . '</span>';
						}
						break;
					case 1:
						$cell[] = $vbphrase['expired'];
						break;
					case 2:
						$cell[] = $vbphrase['reversed'];
						break;
				}
			}

			$postlink = '';
			if (!empty($infraction['postid']) AND !$infraction['postdeleted'])
			{
				//deliberately don't use the title.  We don't have it in our result set (or
				//in any of the tables in our result set) and we'll catch it on redirect.  
				//Plus the admincp isn't a big SEO issue -- we just want to get the links
				//on the classes so that they work and centralize logic for future changes.`
				$postlink = fetch_seo_url('thread|bburl', $infraction, 
					array('p' => $infraction['postid']), 'postthreadid') . "#post$infraction[postid]";
			}

			$cell[] = ($postlink) ?	construct_link_code(htmlspecialchars_uni($vbphrase['post']), 
				$postlink, true, '', true) : '&nbsp;';
			$cell[] = (($infraction['action'] != 2) ? 
				construct_link_code($vbphrase['reverse'], "admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . 
					"do=reverse&infractionid=$infraction[infractionid]" . $args . $vbulletin->GPC['orderby'], false, '', true) : '') .
				' ' . construct_link_code($vbphrase['infraction_view'], "admininfraction.php?" . 
					$vbulletin->session->vars['sessionurl'] . "do=details&infractionid=$infraction[infractionid]", false, '', true) .
				' ' . construct_link_code($vbphrase['delete'], "admininfraction.php?" . $vbulletin->session->vars['sessionurl'] . 
					"do=deleteinfraction&infractionid=$infraction[infractionid]" . $args . $vbulletin->GPC['orderby'], 
					false, '', true);

			print_cells_row($cell);
		}

		print_table_footer(8, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	}
	else
	{
		define('CP_BACKURL', '');
		print_stop_message('no_matches_found');
	}
}

// ###################### Start add #######################
if ($_REQUEST['do'] == 'editgroup')
{
	print_form_header('admininfraction', 'updategroup');
	if (!empty($vbulletin->GPC['infractiongroupid']))
	{
		$infraction = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "infractiongroup WHERE infractiongroupid = " . $vbulletin->GPC['infractiongroupid']);

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['infraction_group'], '', $vbulletin->GPC['infractiongroupid']), 2, 0);
		construct_hidden_code('infractiongroupid', $vbulletin->GPC['infractiongroupid']);
	}
	else
	{
		$infraction = array(
			'override' => 1
		);
		print_table_header($vbphrase['add_new_user_infraction_group']);
	}

	print_input_row($vbphrase['points'], 'pointlevel', $infraction['pointlevel'], true, 5);
	print_chooser_row($vbphrase['primary_usergroup'], 'usergroupid', 'usergroup', $infraction['usergroupid'], '-- ' . $vbphrase['all_usergroups'] . ' --');

	print_chooser_row($vbphrase['override_with_permissions'], 'orusergroupid', 'usergroup', $infraction['orusergroupid']);
	print_yes_no_row($vbphrase['override_display'], 'override', $infraction['override']);
	print_submit_row($vbphrase['save']);

}


// ###################### Start do update #######################
if ($_POST['do'] == 'updategroup')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'pointlevel'    => TYPE_UINT,
		'usergroupid'   => TYPE_INT,
		'orusergroupid' => TYPE_UINT,
		'override'      => TYPE_BOOL,
	));

	if (empty($vbulletin->GPC['pointlevel']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($db->query_first("
		SELECT infractiongroupid
		FROM " . TABLE_PREFIX . "infractiongroup
		WHERE
			" . ($vbulletin->GPC['usergroupid'] == -1 ? "" : "usergroupid IN (-1," . $vbulletin->GPC['usergroupid'] . ") AND") . "
			pointlevel = " . $vbulletin->GPC['pointlevel'] . "
			" . (($vbulletin->GPC['infractiongroupid']) ? "AND infractiongroupid <> " . $vbulletin->GPC['infractiongroupid'] : "")
	))
	{
		print_stop_message('invalid_infraction_usergroup');
	}

	if (empty($vbulletin->GPC['infractiongroupid']))
	{
		$db->query_write("INSERT INTO " . TABLE_PREFIX . "infractiongroup (pointlevel) VALUES (0)");
		$vbulletin->GPC['infractiongroupid'] = $db->insert_id();
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "infractiongroup
		SET pointlevel = " . $vbulletin->GPC['pointlevel'] . ",
			usergroupid = " . $vbulletin->GPC['usergroupid'] . ",
			orusergroupid = " . $vbulletin->GPC['orusergroupid'] . ",
			override = " . intval($vbulletin->GPC['override']) . "
		WHERE infractiongroupid = " . $vbulletin->GPC['infractiongroupid'] . "
	");

	require_once(DIR . '/includes/functions_infractions.php');
	check_infraction_group_change(
		$vbulletin->GPC['orusergroupid'],
		$vbulletin->GPC['pointlevel'],
		$vbulletin->GPC['usergroupid']
	);

	define('CP_REDIRECT', 'admininfraction.php?do=modify');
	print_stop_message('saved_infraction_group_successfully');

}

// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'removegroup')
{

	print_form_header('admininfraction', 'killgroup');
	construct_hidden_code('infractiongroupid', $vbulletin->GPC['infractiongroupid']);
	print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], $vbphrase['infraction_group']));
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_infraction_group']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);

}

// ###################### Start Kill #######################

if ($_POST['do'] == 'killgroup')
{
	$group = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "infractiongroup
		WHERE infractiongroupid = " . $vbulletin->GPC['infractiongroupid']
	);
	if ($group)
	{
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "infractiongroup WHERE infractiongroupid = " . $vbulletin->GPC['infractiongroupid']);

		require_once(DIR . '/includes/functions_infractions.php');
		check_infraction_group_change(
			$group['orusergroupid'],
			$group['pointlevel'],
			$group['usergroupid']
		);
	}

	define('CP_REDIRECT', 'admininfraction.php?do=modify');
	print_stop_message('deleted_infraction_group_successfully');
}


// ###################### Start add #######################
if ($_REQUEST['do'] == 'editbangroup')
{
	print_form_header('admininfraction', 'updatebangroup');
	if (!empty($vbulletin->GPC['infractionbanid']))
	{
		$infraction = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "infractionban
			WHERE infractionbanid = " . $vbulletin->GPC['infractionbanid']
		);

		$selectedid = $infraction['banusergroupid'];

		print_table_header(
			construct_phrase($vbphrase['x_y_id_z'], $vbphrase['automatic_ban'], '', $vbulletin->GPC['infractionbanid']),
			2,
			0
		);
		construct_hidden_code('infractionbanid', $vbulletin->GPC['infractionbanid']);
	}
	else
	{
		$infraction = array(
			'warnings'    => 0,
			'infractions' => 0,
			'pointlevel'  => 0,
		);
		$selectedid = 0;

		print_table_header($vbphrase['add_new_automatic_ban']);
	}

	// make a list of usergroups into which to move this user
	$usergroups = array();
	foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
	{
		if (!($usergroup['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
		{
			$usergroups["$usergroupid"] = $usergroup['title'];
			if ($selectedid == 0)
			{
				$selectedid = $usergroupid;
			}
		}
	}

	$temporary_phrase = $vbphrase['temporary_ban_options'];
	$permanent_phrase = $vbphrase['permanent_ban_options'];

	// make a list of banning period options
	$periodoptions = array(
		$temporary_phrase => array(
			'D_1'  => construct_phrase($vbphrase['x_days'], 1),
			'D_2'  => construct_phrase($vbphrase['x_days'], 2),
			'D_3'  => construct_phrase($vbphrase['x_days'], 3),
			'D_4'  => construct_phrase($vbphrase['x_days'], 4),
			'D_5'  => construct_phrase($vbphrase['x_days'], 5),
			'D_6'  => construct_phrase($vbphrase['x_days'], 6),
			'D_7'  => construct_phrase($vbphrase['x_days'], 7),
			'D_10' => construct_phrase($vbphrase['x_days'], 10),
			'D_14' => construct_phrase($vbphrase['x_weeks'], 2),
			'D_21' => construct_phrase($vbphrase['x_weeks'], 3),
			'M_1' => construct_phrase($vbphrase['x_months'], 1),
			'M_2' => construct_phrase($vbphrase['x_months'], 2),
			'M_3' => construct_phrase($vbphrase['x_months'], 3),
			'M_4' => construct_phrase($vbphrase['x_months'], 4),
			'M_5' => construct_phrase($vbphrase['x_months'], 5),
			'M_6' => construct_phrase($vbphrase['x_months'], 6),
			'Y_1' => construct_phrase($vbphrase['x_years'], 1),
			'Y_2' => construct_phrase($vbphrase['x_years'], 2),
		),
		$permanent_phrase => array(
			'PERMA' => "$vbphrase[permanent] - $vbphrase[never_lift_ban]"
		)
	);

	$methods = array(
		'points'      => $vbphrase['points'],
		'infractions' => $vbphrase['infractions'],
	);

	print_input_row($vbphrase['amount'], 'amount', $infraction['amount'], true, 5);
	print_select_row($vbphrase['method'], 'method', $methods, $infraction['method']);
	print_chooser_row($vbphrase['primary_usergroup'], 'usergroupid', 'usergroup', $infraction['usergroupid'], '-- ' . $vbphrase['all_usergroups'] . ' --');
	print_select_row($vbphrase['move_user_to_usergroup'], 'banusergroupid', $usergroups, $selectedid);
	print_select_row($vbphrase['lift_ban_after'], 'period', $periodoptions, $infraction['period']);
	print_submit_row($vbphrase['save']);

}


// ###################### Start do update #######################
if ($_POST['do'] == 'updatebangroup')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'method'         => TYPE_NOHTML,
		'amount'         => TYPE_UINT,
		'usergroupid'    => TYPE_INT,
		'banusergroupid' => TYPE_UINT,
		'period'         => TYPE_NOHTML
	));

	if (empty($vbulletin->GPC['amount']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if (empty($vbulletin->GPC['infractionbanid']))
	{
		$db->query_write("INSERT INTO " . TABLE_PREFIX . "infractionban (amount) VALUES (0)");
		$vbulletin->GPC['infractionbanid'] = $db->insert_id();
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "infractionban
		SET amount = " . $vbulletin->GPC['amount'] . ",
			method = '" . $db->escape_string($vbulletin->GPC['method']) . "',
			usergroupid = " . $vbulletin->GPC['usergroupid'] . ",
			banusergroupid = " . $vbulletin->GPC['banusergroupid'] . ",
			period = '" . $db->escape_string($vbulletin->GPC['period']) . "'
		WHERE infractionbanid = " . $vbulletin->GPC['infractionbanid'] . "
	");

	define('CP_REDIRECT', 'admininfraction.php?do=modify');
	print_stop_message('saved_automatic_ban_successfully');

}

// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'removebangroup')
{

	print_form_header('admininfraction', 'killbangroup');
	construct_hidden_code('infractionbanid', $vbulletin->GPC['infractionbanid']);
	print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], $vbphrase['automatic_ban']));
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_automatic_ban']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);

}

// ###################### Start Kill #######################

if ($_POST['do'] == 'killbangroup')
{

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "infractionban WHERE infractionbanid = " . $vbulletin->GPC['infractionbanid']);

	define('CP_REDIRECT', 'admininfraction.php?do=modify');
	print_stop_message('deleted_automatic_ban_successfully');
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
?>
