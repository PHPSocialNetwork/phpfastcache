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
define('CVS_REVISION', '$RCSfile$ - $Revision: 58368 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('logging', 'cron');

$specialtemplates = array(
	'crondata',
);

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (is_demo_mode() OR !can_administer('canadmincron'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'cronid' => TYPE_INT
));
log_admin_action(iif($vbulletin->GPC['cronid'] != 0, 'cron id = ' . $vbulletin->GPC['cronid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['scheduled_task_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ############## quick enabled/disabled status ################
if ($_POST['do'] == 'updateenabled')
{
	$vbulletin->input->clean_gpc('p', 'enabled', TYPE_ARRAY_BOOL);

	$updates = array();

	$crons_result = $db->query_read("SELECT varname, active FROM " . TABLE_PREFIX . "cron");
	while ($cron = $db->fetch_array($crons_result))
	{
		$old = $cron['active'] ? 1 : 0;
		$new = $vbulletin->GPC['enabled']["$cron[varname]"] ? 1 : 0;

		if ($old != $new)
		{
			$updates["$cron[varname]"] = $new;
		}
	}
	$db->free_result($crons_result);

	if (!empty($updates))
	{
		$cases = '';
		foreach ($updates AS $varname => $status)
		{
			$cases .= "WHEN '" . $db->escape_string($varname) . "' THEN $status ";
		}

		$db->query_write("UPDATE " . TABLE_PREFIX . "cron SET active = CASE varname $cases ELSE active END");
	}

	print_cp_redirect('cronadmin.php?do=modify' . $vbulletin->session->vars['sessionurl_js']);
}

// ###################### Start run cron #######################
if ($_REQUEST['do'] == 'runcron')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'cronid' => TYPE_INT,
		'varname' => TYPE_STR
	));

	$nextitem = null;

	if ($vbulletin->GPC['cronid'])
	{
		$nextitem = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "cron WHERE cronid = " . $vbulletin->GPC['cronid']);
	}
	else if ($vbulletin->GPC['varname'])
	{
		$nextitem = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "cron WHERE varname = '" . $db->escape_string($vbulletin->GPC['varname']) . "'");
	}

	if ($nextitem)
	{
		ignore_user_abort(1);
		@set_time_limit(0);

		// Force custom scripts to use $vbulletin->db to follow function standards of only globaling $vbulletin
		// This will cause an error to be thrown when a script is run manually since it will silently fail when cron.php runs if $db-> is accessed
		unset($db);

		echo "<p><b>" . (isset($vbphrase['task_' . $nextitem['varname'] . '_title']) ? htmlspecialchars_uni($vbphrase['task_' . $nextitem['varname'] . '_title']) : $nextitem['varname']) . " </b></p>";
		require_once(DIR . '/includes/functions_cron.php');
		include_once(DIR . '/' . $nextitem['filename']);
		echo "<p>$vbphrase[done]</p>";

		$db =& $vbulletin->db;
	}
	else
	{
		print_stop_message('invalid_action_specified');
	}
}

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'cronid' => TYPE_INT
	));

	print_form_header('cronadmin', 'update');
	if (!empty($vbulletin->GPC['cronid']))
	{
		$cron = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "cron
			WHERE cronid = " . intval($vbulletin->GPC['cronid'])
		);

		$title = 'task_' . $cron['varname'] . '_title';
		$desc = 'task_' . $cron['varname'] . '_desc';
		$logphrase = 'task_' . $cron['varname'] . '_log';

		if (is_numeric($cron['minute']))
		{
			$cron['minute'] = array(0 => $cron['minute']);
		}
		else
		{
			$cron['minute'] = unserialize($cron['minute']);
		}

		$phrases = $db->query_read("
			SELECT varname, text
			FROM " . TABLE_PREFIX . "phrase
			WHERE languageid = " . ($cron['volatile'] ? -1 : 0) . " AND
				fieldname = 'cron' AND
				varname IN ('$title', '$desc', '$logphrase')
		");
		while ($phrase = $db->fetch_array($phrases))
		{
			if ($phrase['varname'] == $title)
			{
				$cron['title'] = $phrase['text'];
				$cron['titlevarname'] = $title;
			}
			else if ($phrase['varname'] == $desc)
			{
				$cron['description'] = $phrase['text'];
				$cron['descvarname'] = $desc;
			}
			else if ($phrase['varname'] == $logphrase)
			{
				$cron['logphrase'] = $phrase['text'];
				$cron['logvarname'] = $logphrase;
			}
		}

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['scheduled_task'], htmlspecialchars_uni($cron['title']), $cron['cronid']));
		construct_hidden_code('cronid' , $cron['cronid']);
		print_label_row($vbphrase['varname'], $cron['varname']);
	}
	else
	{
		$cron = array(
			'cronid'   => 0,
			'weekday'  => -1,
			'day'      => -1,
			'hour'     => -1,
			'minute'   => array (0 => -1),
			'filename' => './includes/cron/.php',
			'loglevel' => 0,
			'active'   => 1,
			'volatile' => ($vbulletin->debug ? 1 : 0),
			'product'  => 'vbulletin'
		);
		print_table_header($vbphrase['add_new_scheduled_task']);
		print_input_row($vbphrase['varname'], 'varname');
	}

	$weekdays = array(-1 => '*', 0 => $vbphrase['sunday'], $vbphrase['monday'], $vbphrase['tuesday'], $vbphrase['wednesday'], $vbphrase['thursday'], $vbphrase['friday'], $vbphrase['saturday']);
	$hours = array(-1 => '*', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23);
	$days = array(-1 => '*', 1 => 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31);
	$minutes = array(-1 => '*');
	for ($x = 0; $x < 60; $x++)
	{
		$minutes[] = $x;
	}

	if ($cron['cronid'])
	{
		$trans_link = "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=cron&t=1&varname="; // has varname appended

		if (!$cron['volatile'] OR $vbulletin->debug)
		{
			// non volatile or in debug mode -- always editable (custom created)
			print_input_row($vbphrase['title'] . '<dfn>' . construct_link_code($vbphrase['translations'], $trans_link . $cron['titlevarname'], 1)  . '</dfn>', 'title', $cron['title']);
			print_textarea_row($vbphrase['description'] . '<dfn>' . construct_link_code($vbphrase['translations'], $trans_link . $cron['descvarname'], 1)  . '</dfn>', 'description', $cron['description']);
			print_textarea_row($vbphrase['log_phrase'] . '<dfn>' . construct_link_code($vbphrase['translations'], $trans_link . $cron['logvarname'], 1)  . '</dfn>', 'logphrase', $cron['logphrase']);
		}
		else
		{
			print_label_row($vbphrase['title'] . '<dfn>' . construct_link_code($vbphrase['translations'], $trans_link . $cron['titlevarname'], 1)  . '</dfn>', htmlspecialchars_uni($cron['title']));
			print_label_row($vbphrase['description'] . '<dfn>' . construct_link_code($vbphrase['translations'], $trans_link . $cron['descvarname'], 1)  . '</dfn>', htmlspecialchars_uni($cron['description']));
			print_label_row($vbphrase['log_phrase'] . '<dfn>' . construct_link_code($vbphrase['translations'], $trans_link . $cron['logvarname'], 1)  . '</dfn>', htmlspecialchars_uni($cron['logphrase']));
		}
	}
	else
	{
		print_input_row($vbphrase['title'], 'title');
		print_textarea_row($vbphrase['description'], 'description');
		print_textarea_row($vbphrase['log_phrase'], 'logphrase');
	}

	print_select_row($vbphrase['day_of_week'], 'weekday', $weekdays, $cron['weekday']);
	print_select_row($vbphrase['day_of_month'], 'day', $days, $cron['day']);
	print_select_row($vbphrase['hour'], 'hour', $hours, $cron['hour']);

	$selects = '';
	for ($x = 0; $x < 6; $x++)
	{
		if ($x == 1)
		{
			$minutes = array(-2 => '-') + $minutes;
			unset($minutes[-1]);
		}
		if (!isset($cron['minute'][$x]))
		{
			$cron['minute'][$x] = -2;
		}
		$selects .= "<select name=\"minute[$x]\" tabindex=\"1\" class=\"bginput\">\n";
		$selects .= construct_select_options($minutes, $cron['minute'][$x]);
		$selects .= "</select>\n";
	}
	print_label_row($vbphrase['minute'], $selects, '', 'top', 'minute');
	print_yes_no_row($vbphrase['active'], 'active', $cron['active']);
	print_yes_no_row($vbphrase['log_entries'], 'loglevel', $cron['loglevel']);
	print_input_row($vbphrase['filename'], 'filename', $cron['filename'], true, 35, 0, 'ltr');
	print_select_row($vbphrase['product'], 'product', fetch_product_list(), $cron['product']);
	if ($vbulletin->debug)
	{
		print_yes_no_row($vbphrase['vbulletin_default'], 'volatile', $cron['volatile']);
	}
	else
	{
		construct_hidden_code('volatile', $cron['volatile']);
	}
	print_submit_row($vbphrase['save']);
}

// ###################### Start do update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'cronid'      => TYPE_INT,
		'varname'     => TYPE_STR,
		'filename'    => TYPE_STR,
		'title'       => TYPE_STR,
		'description' => TYPE_STR,
		'logphrase'   => TYPE_STR,
		'weekday'     => TYPE_STR,
		'day'         => TYPE_STR,
		'hour'        => TYPE_STR,
		'minute'      => TYPE_ARRAY,
		'active'      => TYPE_INT,
		'loglevel'    => TYPE_INT,
		'filename'    => TYPE_STR,
		'product'     => TYPE_STR,
		'volatile'    => TYPE_INT
	));

	if (empty($vbulletin->GPC['cronid']))
	{
		if (empty($vbulletin->GPC['varname']))
		{
			print_stop_message('please_complete_required_fields');
		}

		if (!preg_match('#^[a-z0-9_]+$#i', $vbulletin->GPC['varname'])) // match a-z, A-Z, 0-9, _ only
		{
			print_stop_message('invalid_phrase_varname');
		}

		if ($db->query_first("
			SELECT varname
			FROM " . TABLE_PREFIX . "cron
			WHERE varname = '" . $db->escape_string($vbulletin->GPC['varname']) . "'
		"))
		{
			print_stop_message('there_is_already_option_named_x', $vbulletin->GPC['varname']);
		}

		if (empty($vbulletin->GPC['title']))
		{
			print_stop_message('please_complete_required_fields');
		}
	}
	else
	{
		$cron = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "cron
			WHERE cronid = " . intval($vbulletin->GPC['cronid'])
		);
		if (!$cron)
		{
			print_stop_message('invalid_option_specified');
		}

		if ((!$cron['volatile'] OR $vbulletin->debug) AND empty($vbulletin->GPC['title']))
		{
			// custom entry or in debug mode means the title is editable
			print_stop_message('please_complete_required_fields');
		}

		$vbulletin->GPC['varname'] = $cron['varname'];
	}

	if ($vbulletin->GPC['filename'] == '' OR $vbulletin->GPC['filename'] == './includes/cron/.php')
	{
		print_stop_message('invalid_filename_specified');
	}

	$vbulletin->GPC['weekday']	= str_replace('*', '-1', $vbulletin->GPC['weekday']);
	$vbulletin->GPC['day']		= str_replace('*', '-1', $vbulletin->GPC['day']);
	$vbulletin->GPC['hour']		= str_replace('*', '-1', $vbulletin->GPC['hour']);

	// need to deal with minute properly :)
	sort($vbulletin->GPC['minute'], SORT_NUMERIC);
	$newminute = array();
	foreach ($vbulletin->GPC['minute'] AS $time)
	{
		$newminute["$time"] = true;
	}

	unset($newminute["-2"]); // this is the "-" (don't run) entry

	if ($newminute["-1"])
	{ // its run every minute so lets just ignore every other entry
		$newminute = array(0 => -1);
	}
	else
	{
		// $newminute's keys are the values of the GPC variable, so get the values back
		$newminute = array_keys($newminute);
	}

	if (empty($vbulletin->GPC['cronid']))
	{
		/*insert query*/
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "cron
				(varname)
			VALUES
				('" . $vbulletin->GPC['varname'] . "')
		");
		$vbulletin->GPC['cronid'] = $db->insert_id();
	}
	else
	{
		// updating an entry. If we're changing the volatile status, we
		// need to remove the entries in the opposite language id.
		// Only possible in debug mode.
		if ($vbulletin->GPC['volatile'] != $cron['volatile'])
		{
			$old_languageid = ($cron['volatile'] ? -1 : 0);
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "phrase
				WHERE languageid = $old_languageid
					AND fieldname = 'cron'
					AND varname IN ('task_$cron[varname]_title', 'task_$cron[varname]_desc', 'task_$cron[varname]_log')
			");
		}
	}

	$escaped_product = $db->escape_string($vbulletin->GPC['product']);

	// update
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "cron SET
			loglevel = " . intval($vbulletin->GPC['loglevel']) . ",
			weekday = " . intval($vbulletin->GPC['weekday']) . ",
			day = " . intval($vbulletin->GPC['day']) . ",
			hour = " . intval($vbulletin->GPC['hour']) . ",
			minute = '" . $db->escape_string(serialize($newminute)) . "',
			filename = '" . $db->escape_string($vbulletin->GPC['filename']) . "',
			active = " . $vbulletin->GPC['active'] . ",
			volatile = " . $vbulletin->GPC['volatile'] . ",
			product = '$escaped_product'
		WHERE cronid = " . intval($vbulletin->GPC['cronid'])
	);

	$new_languageid = ($vbulletin->GPC['volatile'] ? -1 : 0);

	$full_product_info = fetch_product_list(true);
	$product_version = $full_product_info["$escaped_product"]['version'];

	if (!$vbulletin->GPC['volatile'] OR $vbulletin->debug)
	{
		/*insert_query*/
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "phrase
				(languageid, fieldname, varname, text, product, username, dateline, version)
			VALUES
				(
					$new_languageid,
					'cron',
					'task_" . $vbulletin->GPC['varname'] . "_title',
					'" . $db->escape_string($vbulletin->GPC['title']) . "',
					'$escaped_product',
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($product_version) . "'
				),
				(
					$new_languageid,
					'cron',
					'task_" . $vbulletin->GPC['varname'] . "_desc',
					'" . $db->escape_string($vbulletin->GPC['description']) . "',
					'$escaped_product',
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($product_version) . "'
				),
				(
					$new_languageid,
					'cron',
					'task_" . $vbulletin->GPC['varname'] . "_log',
					'" . $db->escape_string($vbulletin->GPC['logphrase']) . "',
					'$escaped_product',
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($product_version) . "'
				)
		");

		require_once(DIR . '/includes/adminfunctions_language.php');
		build_language();
	}

	require_once(DIR . '/includes/functions_cron.php');
	build_cron_item($vbulletin->GPC['cronid']);
	build_cron_next_run();

	define('CP_REDIRECT', 'cronadmin.php?do=modify');
	print_stop_message('saved_scheduled_task_x_successfully', $vbulletin->GPC['title']);
}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'cronid' 	=> TYPE_INT
	));

	print_form_header('cronadmin', 'kill');
	construct_hidden_code('cronid', $vbulletin->GPC['cronid']);
	print_table_header($vbphrase['confirm_deletion']);
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_scheduled_task']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// ###################### Start Kill #######################
if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'cronid' 	=> TYPE_INT
	));

	$cron = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "cron
		WHERE cronid = " . $vbulletin->GPC['cronid']
	);

	$escaped_varname = $db->escape_string($cron['varname']);

	// delete phrases
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "phrase
		WHERE fieldname = 'cron' AND
			varname IN ('task_{$escaped_varname}_title', 'task_{$escaped_varname}_desc', 'task_{$escaped_varname}_log')
	");

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "cron WHERE cronid = " . $vbulletin->GPC['cronid']);

 	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	define('CP_REDIRECT', 'cronadmin.php?do=modify');
	print_stop_message('deleted_scheduled_task_successfully');
}

// ###################### Start switchactive #######################
if ($_REQUEST['do'] == 'switchactive')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'cronid' 	=> TYPE_INT
	));

	verify_cp_sessionhash();

	$cron = $db->query_first("
		SELECT cron.*,
			IF(product.productid IS NULL OR product.active = 1, 1, 0) AS product_active,
			product.title AS product_title
		FROM " . TABLE_PREFIX . "cron AS cron
		LEFT JOIN " . TABLE_PREFIX . "product AS product ON (cron.product = product.productid)
		WHERE cronid = " . $vbulletin->GPC['cronid']
	);

	if (!$cron)
	{
		define('CP_REDIRECT', 'cronadmin.php?do=modify');
		print_stop_message('enabled_disabled_scheduled_task_successfully');
	}
	else if (!$cron['product_active'])
	{
		print_stop_message('task_not_enabled_product_x_disabled', htmlspecialchars_uni($cron['product_title']));
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "cron SET
			active = IF(active = 1, 0, 1)
		WHERE cronid = " . $vbulletin->GPC['cronid'] . "
	");

	require_once(DIR . '/includes/functions_cron.php');
	build_cron_item($vbulletin->GPC['cronid']);
	build_cron_next_run();

	define('CP_REDIRECT', 'cronadmin.php?do=modify');
	print_stop_message('enabled_disabled_scheduled_task_successfully');
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	function fetch_cron_timerule($cron)
	{
		global $vbphrase;

		$t = array(
			'hour'		=> $cron['hour'],
			'day'		=> $cron['day'],
			'month'		=> -1,
			'weekday'	=> $cron['weekday']
		);

		// set '-1' fields as
		foreach ($t AS $field => $value)
		{
			$t["$field"] = iif($value == -1, '*', $value);
		}

		if (is_numeric($cron['minute']))
		{
			$cron['minute'] = array(0 => $cron['minute']);
		}
		else
		{
			$cron['minute'] = unserialize($cron['minute']);
			if (!is_array($cron['minute']))
			{
				$cron['minute'] = array(-1);
			}
		}

		if ($cron['minute'][0] == -1)
		{
			$t['minute'] = '*';
		}
		else
		{
			$minutes = array();
			foreach ($cron['minute'] AS $nextminute)
			{
				$minutes[] = str_pad(intval($nextminute), 2, 0, STR_PAD_LEFT);
			}
			$t['minute'] = implode(', ', $minutes);
		}

		// set weekday to override day of month if necessary
		$days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
		if ($t['weekday'] != '*')
		{
			$day = $days[intval($t['weekday'])];
			$t['weekday'] = $vbphrase[$day . "_abbr"];
			$t['day'] = '*';
		}

		return $t;
	}

	$crons = $db->query_read("
		SELECT cron.*, IF(product.productid IS NULL OR product.active = 1, cron.active, 0) AS effective_active
		FROM " . TABLE_PREFIX . "cron AS cron
		LEFT JOIN " . TABLE_PREFIX . "product AS product ON (cron.product = product.productid)
		ORDER BY effective_active DESC, nextrun
	");

	?>
	<script type="text/javascript">
	<!--
	function js_cron_jump(cronid)
	{
		task = eval("document.cpform.c" + cronid + ".options[document.cpform.c" + cronid + ".selectedIndex].value");
		switch (task)
		{
			case 'edit': window.location = "cronadmin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=edit&cronid=" + cronid; break;
			case 'kill': window.location = "cronadmin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=remove&cronid=" + cronid; break;
			case 'switchactive': window.location = "cronadmin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=switchactive&cronid=" + cronid + "&hash=<?php echo CP_SESSIONHASH; ?>"; break;
			default: return false; break;
		}
	}
	function js_run_cron(cronid)
	{
		window.location = "<?php echo "cronadmin.php?" . $vbulletin->session->vars['sessionurl_js'] . "do=runcron&cronid="; ?>" + cronid;
	}
	//-->
	</script>
	<?php

	print_form_header('cronadmin', 'updateenabled');
	print_table_header($vbphrase['scheduled_task_manager'], 9);
	print_cells_row(array(
		'',
		$vbphrase['min_abbr'],
		$vbphrase['hour_abbr'],
		$vbphrase['day_abbr'],
		$vbphrase['month_abbr'],
		$vbphrase['dow_acronym'],
		$vbphrase['title'],
		$vbphrase['next_time'],
		$vbphrase['controls']
	), 1, '', 1);

	while ($cron = $db->fetch_array($crons))
	{
		$options = array(
			'edit' => $vbphrase['edit'],
			'switchactive' => ($cron['effective_active'] ? $vbphrase['disable'] : $vbphrase['enable'])
		);
		if (!$cron['volatile'] OR $vbulletin->debug)
		{
			$options['kill'] = $vbphrase['delete'];
		}

		$item_title = htmlspecialchars_uni($vbphrase['task_' . $cron['varname'] . '_title']);
		if (isset($vbphrase['task_' . $cron['varname'] . '_title']))
		{
			$item_title = htmlspecialchars_uni($vbphrase['task_' . $cron['varname'] . '_title']);
		}
		else
		{
			$item_title = $cron['varname'];
		}
		if (!$cron['effective_active'])
		{
			$item_title = "<strike>$item_title</strike>";
		}
		$item_desc = htmlspecialchars_uni($vbphrase['task_' . $cron['varname'] . '_desc']);

		$timerule = fetch_cron_timerule($cron);

		// this will happen in the future which the yestoday setting doesn't handle when its in the detailed mode
		$future = ($cron['nextrun'] > TIMENOW AND $vbulletin->options['yestoday'] == 2);

		$cell = array(
			"<input type=\"checkbox\" name=\"enabled[$cron[varname]]\" value=\"1\" title=\"$vbphrase[enabled]\" id=\"cb_enabled_$cron[varname]\" tabindex=\"1\"" . ($cron['active'] ? ' checked="checked"' : '') . " />",
			$timerule['minute'],
			$timerule['hour'],
			$timerule['day'],
			$timerule['month'],
			$timerule['weekday'],
			"<label for=\"cb_enabled_$cron[varname]\"><strong>$item_title</strong><br /><span class=\"smallfont\">$item_desc</span></label>",
			'<div style="white-space:nowrap">' . ($cron['effective_active'] ? vbdate($vbulletin->options['dateformat'], $cron['nextrun'], (true AND !$future)) . (($vbulletin->options['yestoday'] != 2 OR $future) ? '<br />' . vbdate($vbulletin->options['timeformat'], $cron['nextrun']) : '') : $vbphrase['n_a']) . '</div>',
			"\n\t<select name=\"c$cron[cronid]\" onchange=\"js_cron_jump($cron[cronid]);\" class=\"bginput\">\n" . construct_select_options($options) . "\t</select><input type=\"button\" class=\"button\" value=\"$vbphrase[go]\" onclick=\"js_cron_jump($cron[cronid]);\" />\n\t" .
			"\n\t<input type=\"button\" class=\"button\" value=\"$vbphrase[run_now]\" onclick=\"js_run_cron($cron[cronid]);\" />"
		);
		print_cells_row($cell, 0, '', -6);
	}

	print_description_row("<div class=\"smallfont\" align=\"center\">$vbphrase[all_times_are_gmt_x_time_now_is_y]</div>", 0, 9, 'thead');
	print_submit_row($vbphrase['save_enabled_status'], 0, 9, '', "<input type=\"button\" class=\"button\" value=\"$vbphrase[add_new_scheduled_task]\" tabindex=\"1\" onclick=\"window.location='cronadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit'\" />");

}
print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 58368 $
|| ####################################################################
\*======================================================================*/
?>
