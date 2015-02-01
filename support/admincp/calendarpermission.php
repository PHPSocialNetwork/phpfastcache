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
define('CVS_REVISION', '$RCSfile$ - $Revision: 47535 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('calendar', 'cppermission');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadmincalendars'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'calendarpermissionid' 	=> TYPE_INT,
	'calendarid' 			=> TYPE_INT,
	'usergroupid' 			=> TYPE_INT
));
log_admin_action(iif($vbulletin->GPC['calendarpermissionid'] != 0, "calendarpermission id = " . $vbulletin->GPC['calendarpermissionid'],
					iif($vbulletin->GPC['calendarid'] != 0, "calendar id = ". $vbulletin->GPC['calendarid'] .
						iif($vbulletin->GPC['usergroupid'] != 0, " / usergroup id = " . $vbulletin->GPC['usergroupid']))));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['calendar_permissions_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'calendarpermissionid' 	=> TYPE_INT,
		'calendarid' 			=> TYPE_INT,
		'usergroupid' 			=> TYPE_INT
	));

	?>
	<script type="text/javascript">
	<!--
	function js_set_custom()
	{
		if (document.cpform.useusergroup[1].checked == false)
		{
			if (confirm('<?php echo addslashes_js($vbphrase['must_enable_custom_permissions']);?>'))
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

	print_form_header('calendarpermission', 'doupdate');

	if ($vbulletin->GPC['calendarpermissionid'])
	{
		$getperms = $db->query_first("
			SELECT calendarpermission.*, usergroup.title AS grouptitle, calendar.title AS calendartitle
			FROM " . TABLE_PREFIX . "calendarpermission AS calendarpermission
			INNER JOIN " . TABLE_PREFIX . "calendar AS calendar ON (calendar.calendarid = calendarpermission.calendarid)
			INNER JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroup.usergroupid = calendarpermission.usergroupid)
			WHERE calendarpermissionid = " . $vbulletin->GPC['calendarpermissionid']
		);
		$usergroup['title'] = $getperms['grouptitle'];
		$calendar['title'] = $getperms['calendartitle'];
		construct_hidden_code('calendarpermissionid', $vbulletin->GPC['calendarpermissionid']);
		construct_hidden_code('calendarid', $getperms['calendarid']);
	}
	else
	{
		$calendar = $db->query_first("SELECT title FROM " . TABLE_PREFIX . "calendar WHERE calendarid = " . $vbulletin->GPC['calendarid']);
		$usergroup = $db->query_first("SELECT title FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);

		$getperms = $db->query_first("
			SELECT usergroup.title as grouptitle, calendarpermissions
			FROM " . TABLE_PREFIX . "usergroup AS usergroup
			WHERE usergroupid = " . $vbulletin->GPC['usergroupid']
		);

		construct_hidden_code('calendarpermission[usergroupid]', $vbulletin->GPC['usergroupid']);
		construct_hidden_code('calendarid', $vbulletin->GPC['calendarid']);
	}
	$calendarpermission = convert_bits_to_array($getperms['calendarpermissions'], $vbulletin->bf_ugp_calendarpermissions);

	print_table_header(construct_phrase($vbphrase['edit_calendar_permissions_for_usergroup_x_in_calendar_y'], $usergroup['title'], $calendar['title']));
	print_description_row('
		<label for="uug_1"><input type="radio" name="useusergroup" value="1" id="uug_1" tabindex="1" onclick="this.form.reset(); this.checked=true;"' . iif(!$vbulletin->GPC['calendarpermissionid'], ' checked="checked"', '') . ' />' . $vbphrase['use_default_permissions'] . '</label>
		<br />
		<label for="uug_0"><input type="radio" name="useusergroup" value="0" id="uug_0" tabindex="1"' . iif($vbulletin->GPC['calendarpermissionid'], ' checked="checked"', '') . ' />' . $vbphrase['use_custom_permissions'] . '</label>
	', 0, 2, 'tfoot', '', 'mode');
	print_table_break();
	print_label_row(
		'<b>' . $vbphrase['custom_calendar_permissions'] . '</b>','
		<input type="button" value="' . $vbphrase['all_yes'] . '" onclick="if (js_set_custom()) { js_check_all_option(this.form, 1); }" class="button" />
		<input type="button" value=" ' . $vbphrase['all_no'] . ' " onclick="if (js_set_custom()) { js_check_all_option(this.form, 0); }" class="button" />
	', 'tcat', 'middle');

	// Load permissions
	require_once(DIR . '/includes/class_bitfield_builder.php');
	$groupinfo = vB_Bitfield_Builder::fetch_permission_group('calendarpermissions');

	foreach($groupinfo AS $grouptitle => $group)
	{
		print_table_header($vbphrase["$grouptitle"]);

		foreach ($group AS $permtitle => $permvalue)
		{
			print_yes_no_row($vbphrase["{$permvalue['phrase']}"], "calendarpermission[$permtitle]", $calendarpermission["$permtitle"], 'js_set_custom();');
		}
	}

	print_submit_row($vbphrase['save']);
}

// ###################### Start do update #######################
if ($_POST['do'] == 'doupdate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'calendarpermissionid' => TYPE_INT,
		'calendarid'           => TYPE_INT,
		'useusergroup'         => TYPE_INT,
		'calendarpermission'   => TYPE_ARRAY
	));

	define('CP_REDIRECT', "calendarpermission.php?do=modify#calendar" . $vbulletin->GPC['calendarid']);

	if ($vbulletin->GPC['useusergroup'])
	{
		// use usergroup defaults. delete calendarpermission if it exists
		if ($vbulletin->GPC['calendarpermissionid'])
		{
			$db->query_write("DELETE FROM " . TABLE_PREFIX . "calendarpermission WHERE calendarpermissionid = " . $vbulletin->GPC['calendarpermissionid']);
			print_stop_message('deleted_calendar_permissions_successfully');
		}
		else
		{
			print_stop_message('saved_calendar_permissions_successfully');
		}
	}
	else
	{
		require_once(DIR . '/includes/functions_misc.php');
		$vbulletin->GPC['calendarpermission']['calendarpermissions'] = convert_array_to_bits($vbulletin->GPC['calendarpermission'], $vbulletin->bf_ugp_calendarpermissions, 1);

		if ($vbulletin->GPC['calendarid'] AND !$vbulletin->GPC['calendarpermissionid'])
		{
			$vbulletin->GPC['calendarpermission']['calendarid'] = $vbulletin->GPC['calendarid'];
			$query = fetch_query_sql($vbulletin->GPC['calendarpermission'], 'calendarpermission');
			$db->query_write($query);
			$calendarinfo = $db->query_first("SELECT title AS calendartitle FROM " . TABLE_PREFIX . "calendar WHERE calendarid=" . $vbulletin->GPC['calendarid']);

			print_stop_message('saved_calendar_permissions_successfully');
		}
		else
		{
			$query = fetch_query_sql($vbulletin->GPC['calendarpermission'], 'calendarpermission' , "WHERE calendarpermissionid = " . $vbulletin->GPC['calendarpermissionid']);
			$db->query_write($query);

			print_stop_message('saved_calendar_permissions_successfully');
		}
	}
}

// ###################### Start fpgetstyle #######################
function fetch_forumpermission_style($color = '', $canview)
{
	if ($canview == 0)
	{
		if ($canview == 0)
		{
			$canview = 'list-style-type:circle;';
		}
		else
		{
			$canview = '';
		}
		return " style=\"$color$canview\"";
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
	print_table_header($vbphrase['calendar_permissions']);
	print_description_row('
		<div class="darkbg" style="border: 2px inset">	<ul class="darkbg">
		<li><b>' . $vbphrase['color_key'] . '</b></li>
		<li class="col-g">' . $vbphrase['standard_using_default_usergroup_permissions'] . '</li>
		<li class="col-c">' . $vbphrase['customized_using_custom_permissions_for_this_usergroup'] . '</li>
		</ul></div>
	');

	print_table_footer();

	// Calendar cache, will move to function...
	$calendars = $db->query_read("SELECT calendarid, title FROM " . TABLE_PREFIX . "calendar ORDER BY displayorder");
	$calendarcache = array();
	while ($calendar = $db->fetch_array($calendars))
	{
		$calendarcache["$calendar[calendarid]"] = $calendar['title'];
	}
	unset($calendar);
	$db->free_result($calendars);

	// query forum permissions
	$calendarpermissions = $db->query_read("
		SELECT usergroupid, calendar.calendarid, calendarpermissions,
		NOT (ISNULL(calendarpermission.calendarid)) AS hasdata, calendarpermissionid
		FROM " . TABLE_PREFIX . "calendar AS calendar
		LEFT JOIN " . TABLE_PREFIX . "calendarpermission AS calendarpermission ON (calendarpermission.calendarid = calendar.calendarid)
	");

	$permscache = array();
	while ($cperm = $db->fetch_array($calendarpermissions))
	{
		if ($cperm['hasdata'])
		{
			$temp = array();
			$temp['calendarpermissionid'] = $cperm['calendarpermissionid'];
			$temp['calendarpermissions'] = $cperm['calendarpermissions'];
			$permscache["{$cperm['calendarid']}"]["{$cperm['usergroupid']}"] = $temp;
		}
	}

	echo '<center><div class="tborder" style="width: 89%">';
	echo '<div class="alt1" style="padding: 8px">';
	echo '<div class="darkbg" style="padding: 4px; border: 2px inset; text-align: ' . vB_Template_Runtime::fetchStyleVar('left') . '">';

	$ident = '   ';
	echo "$indent<ul class=\"lsq\">\n";
	foreach ($calendarcache AS $calendarid => $title)
	{
		// forum title and links
		echo "$indent<li><b><a name=\"calendar$calendarid\" href=\"admincalendar.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;c=$calendarid\">$title</a></b>";
		echo "$indent\t<ul class=\"usergroups\">\n";
		foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
		{
			$fp = $permscache["$calendarid"]["$usergroupid"];
			if (is_array($fp))
			{
				$fp['class'] = ' class="col-c"';
				$fp['link'] = "calendarpermissionid=$fp[calendarpermissionid]";
			}
			else

			{
				$fp['class'] = '';
				$fp['link'] = "c=$calendarid&amp;usergroupid=$usergroupid";
			}
			echo "$indent\t<li$fp[class]" . iif($fp['calendarpermissions'] & $vbulletin->bf_ugp_calendarpermissions['canview'], '', ' style="list-style-type:circle;"') . '>' . construct_link_code($vbphrase['edit'], "calendarpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;$fp[link]") . $usergroup['title'] . "</li>\n";

			unset($permscache["$calendarid"]["$usergroupid"]);
		}
		echo "$indent\t</ul><br />\n";
		echo "$indent</li>\n";
	}
	echo "$indent</ul>\n";

	echo "</div></div></div></center>";
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 47535 $
|| ####################################################################
\*======================================================================*/
?>
