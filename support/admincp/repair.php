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
define('CVS_REVISION', '$RCSfile$ - $Revision: 32878 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('sql');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

@set_time_limit(0);

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminmaintain'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['repair_optimize_tables']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'list';
}

// ###################### Start checktable #######################
function exec_sql_table_check($table)
{
	global $vbulletin, $vbphrase;

	$nooptimize = 0;
	$error = 0;

	if ($vbulletin->GPC['repairtables'])
	{
		$checkmsgs = $vbulletin->db->query_write("CHECK TABLE `$table`");
		while ($msg = $vbulletin->db->fetch_array($checkmsgs, DBARRAY_NUM))
		{
			if ($msg[2] == 'error')
			{
				if ($msg[3] == 'The handler for the table doesn\'t support check/repair') // nb: this is the MySQL error message, it does not need phrasing
				{
					$msg[2] = 'status';
					$msg[3] = $vbphrase['this_table_does_not_support_repair_optimize'];
					$nooptimize = 1;
				}
				else
				{
					$error = 1;
				}
			}

			$cells = array(
				$table,
				ucfirst($msg[1]),
				iif($error, '<b>' . ucfirst($msg[2]) . '</b>', ucfirst($msg[2])) . ': ' . $msg[3]
			);
			print_cells_row($cells, 0, '', -4);
		}

		if ($error)
		{
			$repairmsg = $vbulletin->db->query_first("REPAIR TABLE `$table`");
			if ($repairmsg[3]!='OK')
			{
				$error2 = 1;
			}
			else
			{
				$error2 = 0;
				$error = 0;
			}

			$cells = array(
				$table,
				ucfirst($msg[1]),
				iif($error2, '<b>' . ucfirst($msg[2]) . '</b>', ucfirst($msg[2])),
			);
			print_cells_row($cells);
		}
	} // end repairing

	if ($vbulletin->GPC['optimizetables'] AND !$error AND !$error2 AND !$nooptimize)
	{
		$opimizemsgs = $vbulletin->db->query_write("OPTIMIZE TABLE `$table`");
		while ($msg = $vbulletin->db->fetch_array($opimizemsgs, DBARRAY_NUM))
		{
			if ($msg[2] == 'error')
			{
				$error = 1;
			}

			$cells = array(
				$table,
				ucfirst($msg[1]),
				iif($error, '<b>' . ucfirst($msg[2]) . '</b>', ucfirst($msg[2])) . ': ' . $msg[3],
			);
			print_cells_row($cells, 0, '', -4);
		}
	} // end optimizing
}

// ######################### Start do repair #####################
if ($_POST['do'] == 'dorepair')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'tableserial'    => TYPE_STR,
		'tablelist'      => TYPE_ARRAY_STR,
		'optimizetables' => TYPE_BOOL,
		'repairtables'   => TYPE_BOOL,
		'converttables'  => TYPE_BOOL,
		'isamtablelist'  => TYPE_ARRAY_STR,
	));

	// This will work on some servers, for what it's worth.
	echo '<p align="center">' . $vbphrase['please_wait'] . '</p>';
	vbflush();

	if (!empty($vbulletin->GPC['tableserial']))
	{
		$vbulletin->GPC['tablelist'] = @unserialize(verify_client_string($vbulletin->GPC['tableserial']));
	}

	print_form_header('repair', 'dorepair');

	if ($vbulletin->GPC['converttables'] AND !empty($vbulletin->GPC['isamtablelist']))
	{
		$vbulletin->db->hide_errors();
		print_table_header(construct_phrase($vbphrase['convert_tables_from_x_to_y'], '<b>ISAM</b>', '<b>MyISAM</b>'));
		print_cells_row(array($vbphrase['table'], $vbphrase['status']), 1);
		foreach ($vbulletin->GPC['isamtablelist'] AS $index => $value)
		{
			$cells = array();
			$cells[] = construct_phrase($vbphrase['convert_x_from_y_to_z'], "<i>$value</i>", 'ISAM', 'MyISAM');
			$vbulletin->db->query_write("ALTER TABLE `$value` TYPE=MyISAM");
			if ($vbulletin->db->errno() == 0)
			{
				$cells[] = $vbphrase['okay'];
			}
			else
			{
				$cells[] = $vbulletin->db->errno() . ': ' . $vbulletin->db->error();
			}
			print_cells_row($cells);
		}
		$vbulletin->db->show_errors();
		print_table_break();
	}

	print_table_header($vbphrase['results'], 3);
	print_cells_row(array($vbphrase['table'], $vbphrase['action'], $vbphrase['message']), 1);

	if (!empty($vbulletin->GPC['tablelist']) AND ($vbulletin->GPC['optimizetables'] OR $vbulletin->GPC['repairtables']))
	{
		foreach ($vbulletin->GPC['tablelist'] AS $tablename)
		{
			exec_sql_table_check($tablename);
		}
	}
	else
	{
		print_description_row($vbphrase['nothing_to_do'], 0, 3);
	}

	construct_hidden_code('optimizetables', $vbulletin->GPC['optimizetables']);
	construct_hidden_code('repairtables', $vbulletin->GPC['repairtables']);
	construct_hidden_code('tableserial', sign_client_string(serialize($vbulletin->GPC['tablelist'])));

	print_submit_row($vbphrase['repeat_process'], '', 3);
}

// ######################### Start table list ####################
if ($_REQUEST['do'] == 'list')
{
	print_form_header('repair', 'dorepair', 0, 1, 'cpform');
	print_table_header($vbphrase['repair_optimize_tables'], 5);
	$headings = array();
	$headings[] = $vbphrase['table'];
	$headings[] = $vbphrase['data_length'];
	$headings[] = $vbphrase['index_length'];
	$headings[] = $vbphrase['overhead'];
	$headings[] = "<input type=\"checkbox\" name=\"allbox\" id=\"allbox\" title=\"$vbphrase[check_all]\" onclick=\"js_check_all(this.form);\" /><label for=\"allbox\">$vbphrase[check_all]</label>";
	print_cells_row($headings, 1);

	$mysqlversion = $db->query_first("SELECT VERSION() AS version");

	$tables = $db->query_write("SHOW TABLE STATUS");

	$isamtables = array();

	$nullcount = 0;

	while ($table = $db->fetch_array($tables))
	{
		$cells = array();
		$table['Engine'] = (!empty($table['Type']) ? $table['Type'] : $table['Engine']);
		if (!in_array(strtolower($table['Engine']), array('heap', 'memory')))
		{
			$cells[] = $table['Name'];
			$cells[] = vb_number_format($table['Data_length'], 0, true);
			$cells[] = vb_number_format($table['Index_length'], 0, true);
			$cells[] = vb_number_format($table['Data_free'], 0, true);
			$cells[] = "<input type=\"checkbox\" name=\"tablelist[$nullcount]\" id=\"tablelist_$nullcount\" title=\"$table[Name]\" value=\"$table[Name]\" /><label for=\"tablelist_$nullcount\">$vbphrase[yes]</label>";
			print_cells_row($cells);
			$nullcount++;
			if ($table['Engine'] == 'ISAM')
			{
				$isamtables[] = $table['Name'];
			}
		}
	}

	if (!empty($isamtables))
	{
		$nullcount = 0;
		print_table_break('');
		print_table_header($vbphrase['isam_tables'], 0, 5);
		print_description_row('<span class="smallfont">' . construct_phrase($vbphrase['you_are_running_mysql_version_x_convert_to_myisam'], $mysqlversion['version']) . '</span>');
		foreach ($isamtables AS $index => $value)
		{
			print_checkbox_row($value, "isamtablelist[$nullcount]", false, $value);
			$nullcount++;
		}
	}

	print_table_break('');

	// can use REPAIR TABLE xxxx
	print_table_header($vbphrase['options']);
	if (isset($isamtables[0]))
	{
		print_yes_no_row(construct_phrase($vbphrase['convert_tables_from_x_to_y'], 'ISAM', 'MyISAM'), 'converttables', 1);
	}
	print_yes_no_row($vbphrase['optimize_tables'], 'optimizetables', 1);
	print_yes_no_row($vbphrase['repair_tables'], 'repairtables', 1);
	print_submit_row($vbphrase['continue']);

	echo '<a name="fixunique">&nbsp;</a>';

	print_form_header('repair', 'fixunique', 0, 1, 'bla');
	print_table_header($vbphrase['fix_unique_indexes']);
	print_description_row($vbphrase['fix_unique_indexes_intro']);
	print_submit_row($vbphrase['fix_unique_indexes'], false);
}

// ######################### Start fix unique indexes #####################
if ($_REQUEST['do'] == 'fixunique')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'tableid' => TYPE_UINT
	));

	if (!file_exists(DIR . '/install/mysql-schema.php'))
	{
		print_stop_message('restore_mysql_schema');
	}

	require_once(DIR . '/install/mysql-schema.php');


	function fix_unique_index($tableid)
	{
		global $vbphrase, $db, $uniquetables;

		if (isset($uniquetables["$tableid"]))
		{
			$table =& $uniquetables["$tableid"];
		}
		else
		{
			return -1;
		}

		$unique_keys = array();
		$keys = array();
		$checkindexes = $db->query_write("SHOW KEYS FROM " . TABLE_PREFIX . "$table[name]");
		while ($checkindex = $db->fetch_array($checkindexes))
		{
			if ($checkindex['Non_unique'] == 0)
			{
				$unique_keys["$checkindex[Key_name]"][] = $checkindex['Column_name'];
			}
			$keys["$checkindex[Key_name]"][] = $checkindex['Column_name'];
		}
		$db->free_result($checkindexes);

		foreach ($unique_keys AS $keyname => $keyfields)
		{
			$unique_keys["$keyname"] = implode(', ', $keyfields);
		}

		$fields = implode(', ', $table['fields']);

		$gotunique = in_array($fields, $unique_keys);

		if ($gotunique)
		{
			echo "<div>" . construct_phrase($vbphrase['table_x_has_unique_index'], "<strong>$table[name]</strong>") . "</div>";
			$nexttableid = fix_unique_index($tableid + 1);
		}
		else
		{
			echo "<p>" . construct_phrase($vbphrase['replacing_unique_index_on_table_x'], "<strong>$table[name]</strong>") . "</p><ul>";

			$fields = implode(', ', $table['fields']);

			$findquery = "SELECT $fields, COUNT(*) AS occurences
				FROM " . TABLE_PREFIX . "$table[name]
				GROUP BY $fields
				HAVING occurences > 1";
			$dupes = $db->query_write($findquery);

			if ($numdupes = $db->num_rows($dupes))
			{
				echo "<li>" . construct_phrase($vbphrase['found_x_duplicate_record_occurences'], "<strong>$numdupes</strong>") . "<ol>";

				while ($dupe = $db->fetch_array($dupes))
				{
					$cond = array();

					foreach ($dupe AS $fieldname => $field)
					{
						if ($fieldname != 'occurences')
						{
							$cond[] = "$fieldname = " . iif(is_numeric($field), $field, "'" . $db->escape_string($field) . "'");
						}
					}

					$dupesquery = "DELETE FROM " . TABLE_PREFIX . "$table[name] WHERE " . implode(" AND ", $cond) . " ";

					if ($table['autoinc'])
					{
						$max = $db->query_first("
							SELECT MAX($table[autoinc]) AS maxid
							FROM " . TABLE_PREFIX . "$table[name]
							WHERE " . implode("\nAND ", $cond) . "
						");
						$dupesquery .= "AND $table[autoinc] <> $max[maxid]";
					}
					else
					{
						$dupesquery .= "LIMIT " . ($dupe['occurences'] - 1);
					}

					$db->query_write($dupesquery);
					echo "<li>$vbphrase[deleted_duplicate_occurence]</li>";
				}
				$db->free_result($dupes);

				echo "</ol></li>";
			}

			if (isset($keys["$table[keyname]"]))
			{
				$killindexquery = "ALTER TABLE " . TABLE_PREFIX . "$table[name] DROP INDEX $table[keyname]";
				echo "<li>$vbphrase[dropping_non_unique_index] <!--<pre>$killindexquery</pre>-->";
				$db->query_write($killindexquery);
				echo "$vbphrase[done]</li>";
			}

			$createindexquery = "ALTER TABLE " . TABLE_PREFIX . "$table[name] ADD " . iif($table['name'] == 'access', 'PRIMARY', 'UNIQUE') . " KEY $table[keyname] ($fields)";
			echo "<li>$vbphrase[creating_unique_index] <!--<pre>$createindexquery</pre>-->";
			$db->query_write($createindexquery);
			echo "$vbphrase[done]</li>";

			echo "</ul>";

			$nexttableid = $tableid + 1;
		}

		return $nexttableid;
	}

	$uniquetables = array();

	foreach ($schema['CREATE']['query'] AS $tablename => $query)
	{
		if (preg_match('#unique key (\w+)\s*\(([\w, ]+)\)#siU', $query, $regs) OR ($tablename == 'access' AND preg_match('#primary key (\w+)\s*\(([\w, ]+)\)#siU', $query, $regs)))
		{
			if (preg_match('#\t+(\w+id)\s+[\w- ]+AUTO_INCREMENT#iU', $query, $regs2))
			{
				$autoinc = $regs2[1];
			}
			else
			{
				$autoinc = false;
			}

			$uniquetables[] = array(
				'name'    => $tablename,
				'keyname' => $regs[1],
				'fields'  => preg_split('#\s*,\s*#si', $regs[2], -1, PREG_SPLIT_NO_EMPTY),
				'autoinc' => $autoinc
			);
		}
	}

	echo "<p><strong>$vbphrase[fix_unique_indexes]</strong></p>";

	//while ($nexttableid >= 0)
	//{
		$nexttableid = fix_unique_index($vbulletin->GPC['tableid']);
	//}

	if ($nexttableid >= 0)
	{
		print_form_header('repair', 'fixunique', 0, 1, 'cpform', '25%');
		construct_hidden_code('tableid', $nexttableid);
		print_table_header("<div style=\"white-space:nowrap\">$vbphrase[fix_unique_indexes]</div>");
		print_submit_row($vbphrase['continue'], false);
	}
	else
	{
		print_form_header('repair', '', 0, 1, 'cpform', '65%');
		print_table_header($vbphrase['fix_unique_indexes']);
		print_description_row($vbphrase['all_unique_indexes_checked']);
		print_submit_row($vbphrase['proceed'], false);
	}

}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>