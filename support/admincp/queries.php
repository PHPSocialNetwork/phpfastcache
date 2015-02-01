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
define('CVS_REVISION', '$RCSfile$ - $Revision: 37230 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('sql', 'user', 'cpuser');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

$vbulletin->input->clean_array_gpc('r', array('query' => TYPE_STR));

// ############################# LOG ACTION ###############################
log_admin_action(!empty($vbulletin->GPC['query']) ? "query = '" . htmlspecialchars_uni($vbulletin->GPC['query']) . "'" : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['execute_sql_query']);

if (!$vbulletin->debug)
{
	$userids = explode(',', str_replace(' ', '', $vbulletin->config['SpecialUsers']['canrunqueries']));
	if (!in_array($vbulletin->userinfo['userid'], $userids))
	{
		print_stop_message('no_permission_queries');
	}
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// define auto queries
$queryoptions = array(
	'-1'  => '',
	$vbphrase['all_users'] => array(
		'10'  => $vbphrase['yes'] . ' - ' . $vbphrase['invisible_mode'],
		'80'  => $vbphrase['no'] . ' - ' . $vbphrase['invisible_mode'],

		'20'  => $vbphrase['yes'] . ' - ' . $vbphrase['allow_vcard_download'],
		'90'  => $vbphrase['no'] . ' - ' . $vbphrase['allow_vcard_download'],

		'30'  => $vbphrase['yes'] . ' - ' . $vbphrase['receive_admin_emails'],
		'100' => $vbphrase['no'] . ' - ' . $vbphrase['receive_admin_emails'],

		'40'  => $vbphrase['yes'] . ' - ' . $vbphrase['display_email'],
		'110' => $vbphrase['no'] . ' - ' . $vbphrase['display_email'],

		'50'  => $vbphrase['yes'] . ' - ' . $vbphrase['receive_private_messages'],
		'120' => $vbphrase['no'] . ' - ' . $vbphrase['receive_private_messages'],

		'60'  => $vbphrase['yes'] . ' - ' . $vbphrase['send_notification_email_when_a_private_message_is_received'],
		'130' => $vbphrase['no'] . ' - ' . $vbphrase['send_notification_email_when_a_private_message_is_received'],

		'70'  => $vbphrase['yes'] . ' - ' . $vbphrase['pop_up_notification_box_when_a_private_message_is_received'],
		'140' => $vbphrase['no'] . ' - ' . $vbphrase['pop_up_notification_box_when_a_private_message_is_received'],

		'150' => $vbphrase['on'] . ' - ' . $vbphrase['display_signatures'],
		'180' => $vbphrase['off'] . ' - ' . $vbphrase['display_signatures'],

		'160' => $vbphrase['on'] . ' - ' . $vbphrase['display_avatars'],
		'190' => $vbphrase['off'] . ' - ' . $vbphrase['display_avatars'],

		'170' => $vbphrase['on'] . ' - ' . $vbphrase['display_images'],
		'200' => $vbphrase['off'] . ' - ' . $vbphrase['display_images'],

		'175' => $vbphrase['on'] . ' - ' . $vbphrase['display_reputation'],
		'205' => $vbphrase['off'] . ' - ' . $vbphrase['display_reputation'],

		'176' => $vbphrase['on'] . ' - ' . $vbphrase['enahnced_attachment_uploading'],
		'206' => $vbphrase['off'] . ' - ' . $vbphrase['enahnced_attachment_uploading'],

		'blank1' => '',

		'210' => $vbphrase['subscribe_choice_none'],
		'220' => $vbphrase['subscribe_choice_0'],
		'230' => $vbphrase['subscribe_choice_1'],
		'240' => $vbphrase['subscribe_choice_2'],
		'250' => $vbphrase['subscribe_choice_3'],

		'blank2' => '',

		'270' => $vbphrase['thread_display_mode'] . ' - ' . $vbphrase['linear'],
		'280' => $vbphrase['thread_display_mode'] . ' - ' . $vbphrase['threaded'],
		'290' => $vbphrase['thread_display_mode'] . ' - ' . $vbphrase['hybrid'],

		'blank3' => '',

		'260' => $vbphrase['posts'] . ' - ' . $vbphrase['oldest_first'],
		'265' => $vbphrase['posts'] . ' - ' . $vbphrase['newest_first'],

		'blank4' => '',

		'300' => $vbphrase['do_not_show_editor_toolbar'],
		'310' => $vbphrase['show_standard_editor_toolbar'],
		'320' => $vbphrase['show_enhanced_editor_toolbar'],
	),
	$vbphrase['all_forums'] => array(
		'400' => $vbphrase['show_threads_from_last_day'],
		'405' => $vbphrase['show_threads_from_last_week'],
		'410' => $vbphrase['show_threads_from_last_month'],
		'415' => $vbphrase['show_threads_from_last_year'],
		'420' => $vbphrase['show_all_threads'],
	),
);

($hook = vBulletinHook::fetch_hook('admin_queries_auto_options')) ? eval($hook) : false;

// ##################### START DO QUERY #####################

if ($_POST['do'] == 'doquery')
{
	require_once(DIR . '/includes/functions_misc.php');

	$vbulletin->input->clean_array_gpc('p', array(
		'autoquery'    => TYPE_UINT,
		'perpage'      => TYPE_UINT,
		'pagenumber'   => TYPE_UINT,
		'confirmquery' => TYPE_BOOL
	));

	$query =& $vbulletin->GPC['query'];

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}

	if (!$vbulletin->GPC['perpage'])
	{
		$vbulletin->GPC['perpage'] = 20;
	}

	if (!$vbulletin->GPC['confirmquery'])
	{
		if (!$vbulletin->GPC['autoquery'] AND !$query)
		{
			print_stop_message('please_complete_required_fields');
		}

		if ($vbulletin->GPC['autoquery'])
		{
			switch($vbulletin->GPC['autoquery'])
			{
				case 10:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['invisible'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['invisible'] . ")";
					break;
				case 20:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['showvcard'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['showvcard'] . ")";
					break;
				case 30:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['adminemail'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['adminemail'] . ")";
					break;
				case 40:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['showemail'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['showemail'] . ")";
					break;
				case 50:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['receivepm'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['receivepm'] . ")";
					break;
				case 60:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['emailonpm'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['emailonpm'] . ")";
					break;
				case 70:
					$query = "UPDATE " . TABLE_PREFIX . "user SET pmpopup = 1";
					break;
				case 80:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['invisible'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['invisible'];
					break;
				case 90:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['showvcard'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['showvcard'];
					break;
				case 100:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['adminemail'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['adminemail'];
					break;
				case 110:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['showemail'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['showemail'];
					break;
				case 120:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['receivepm'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['receivepm'];
					break;
				case 130:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['emailonpm'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['emailonpm'];
					break;
				case 140:
					$query = "UPDATE " . TABLE_PREFIX . "user SET pmpopup = 0";
					break;
				case 150:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['showsignatures'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['showsignatures'] . ")";
					break;
				case 160:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['showavatars'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['showavatars'] . ")";
					break;
				case 170:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['showimages'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['showimages'] . ")";
					break;
				case 175:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['showreputation'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['showreputation'] . ")";
					break;
				case 176:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['vbasset_enable'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['vbasset_enable'] . ")";
					break;
				case 180:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['showsignatures'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['showsignatures'];
					break;
				case 190:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['showavatars'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['showavatars'];
					break;
				case 200:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['showimages'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['showimages'];
					break;
				case 205:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['showreputation'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['showreputation'];
					break;
				case 206:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['vbasset_enable'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['vbasset_enable'];
					break;
				case 210:
					$query = "UPDATE " . TABLE_PREFIX . "user SET autosubscribe = -1";
					break;
				case 220:
					$query = "UPDATE " . TABLE_PREFIX . "user SET autosubscribe = 0";
					break;
				case 230:
					$query = "UPDATE " . TABLE_PREFIX . "user SET autosubscribe = 1";
					break;
				case 240:
					$query = "UPDATE " . TABLE_PREFIX . "user SET autosubscribe = 2";
					break;
				case 250:
					$query = "UPDATE " . TABLE_PREFIX . "user SET autosubscribe = 3";
					break;
				case 260:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options - " . $vbulletin->bf_misc_useroptions['postorder'] . " WHERE options & " . $vbulletin->bf_misc_useroptions['postorder'];
					break;
				case 265:
					$query = "UPDATE " . TABLE_PREFIX . "user SET options = options + " . $vbulletin->bf_misc_useroptions['postorder'] . " WHERE NOT (options & " . $vbulletin->bf_misc_useroptions['postorder'] . ")";
					break;
				case 270:
					$query = "UPDATE " . TABLE_PREFIX . "user SET threadedmode = 0";
					break;
				case 280:
					$query = "UPDATE " . TABLE_PREFIX . "user SET threadedmode = 1";
					break;
				case 290:
					$query = "UPDATE " . TABLE_PREFIX . "user SET threadedmode = 2";
					break;
				case 300:
					$query = "UPDATE " . TABLE_PREFIX . "user SET showvbcode = 0";
					break;
				case 310:
					$query = "UPDATE " . TABLE_PREFIX . "user SET showvbcode = 1";
					break;
				case 320:
					$query = "UPDATE " . TABLE_PREFIX . "user SET showvbcode = 2";
					break;
				case 400:
					$query = "UPDATE " . TABLE_PREFIX . "forum SET daysprune = 1";
					break;
				case 405:
					$query = "UPDATE " . TABLE_PREFIX . "forum SET daysprune = 7";
					break;
				case 410:
					$query = "UPDATE " . TABLE_PREFIX . "forum SET daysprune = 30";
					break;
				case 415:
					$query = "UPDATE " . TABLE_PREFIX . "forum SET daysprune = 365";
					break;
				case 420:
					$query = "UPDATE " . TABLE_PREFIX . "forum SET daysprune = -1";
					break;
				default:
					($hook = vBulletinHook::fetch_hook('admin_queries_auto_query')) ? eval($hook) : false;
			}
		}
	}

	if (substr($query, -1) == ';')
	{
		$query = substr($query, 0, -1);
	}
	$db->hide_errors();

	$auto_query_text = '';
	if ($vbulletin->GPC['autoquery'])
	{
		foreach ($queryoptions AS $query_group => $queries)
		{
			if (!is_array($queries))
			{
				continue;
			}

			foreach ($queries AS $query_id => $query_title)
			{
				if ($query_id == $vbulletin->GPC['autoquery'])
				{
					$auto_query_text = " ($query_title)";
					break 2;
				}
			}
		}
	}

	print_form_header('', '');
	print_table_header($vbphrase['query'] . $auto_query_text);
	print_description_row('<code>' . nl2br(htmlspecialchars_uni($query)) . '</code>', 0, 2, '');
	print_description_row(construct_button_code($vbphrase['restart'], 'queries.php?' . $vbulletin->session->vars['sessionurl']), 0, 2, 'tfoot', 'center');
	print_table_footer();

	$query_stripped = preg_replace('@/\*.*?\*/@s', '', $query);
	$query_stripped = preg_replace('@(#|--).*?$@m', '', $query_stripped);

	preg_match("#^([A-Z]+)\s#si", trim($query_stripped), $regs);
	$querytype = strtoupper($regs[1]);

	switch ($querytype)
	{
		// EXPLAIN, SELECT, DESCRIBE & SHOW **********************************************************
		case 'EXPLAIN':
		case 'SELECT':
		case 'DESCRIBE':
		case 'SHOW':
			$query_mod = preg_replace('#\sLIMIT\s+(\d+(\s*,\s*\d+)?)#i', '', $query);

			$counter = $db->query_write($query_mod);
			print_form_header('queries', 'doquery', 0, 1, 'queryform');
			construct_hidden_code('do', 'doquery');
			construct_hidden_code('query', $query);
			construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
			if ($errornum = $db->errno())
			{
				print_table_header($vbphrase['vbulletin_message']);
				print_description_row(construct_phrase($vbphrase['an_error_occured_while_attempting_to_run_your_query'], $errornum, nl2br(htmlspecialchars_uni($db->error()))));
				$extras = '';
			}
			else
			{
				$numrows = $db->num_rows($counter);
				if ($vbulletin->GPC['pagenumber'] == -1)
				{
					$vbulletin->GPC['pagenumber'] = $numpages;
				}
				$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
				if ($querytype == 'SELECT')
				{
					$query_mod = "$query_mod LIMIT $startat, " . $vbulletin->GPC['perpage'];
					$numpages = ceil($numrows / $vbulletin->GPC['perpage']);
				}
				else
				{
					$query_mod = $query;
					$numpages = 1;
				}

				$time_before = microtime();
				$result = $db->query_write($query_mod);
				$time_taken = fetch_microtime_difference($time_before);

				$colcount = $db->num_fields($result);
				print_table_header(construct_phrase($vbphrase['results_x_y'], vb_number_format($numrows), vb_number_format($time_taken, 4)) . ', ' . construct_phrase($vbphrase['page_x_of_y'], $vbulletin->GPC['pagenumber'], $numpages), $colcount);
				if ($numrows)
				{
					$collist = array();
					for ($i = 0; $i < $colcount; $i++)
					{
						$collist[] = $db->field_name($result, $i);
					}
					print_cells_row($collist, 1);

					while ($record = $db->fetch_array($result))
					{
						foreach ($record AS $colname => $value)
						{
							$record["$colname"] = htmlspecialchars_uni($value);
						}
						print_cells_row($record, 0, '', -$colcount);
					}

					if ($numpages > 1)
					{
						$extras = '<b>' . $vbphrase['page'] . '</b> <select name="page" tabindex="1" onchange="document.queryform.submit();" class="bginput">';
						for ($i = 1; $i <= $numpages; $i++)
						{
							$selected = iif($i == $vbulletin->GPC['pagenumber'], 'selected="selected"');
							$extras .= "<option value=\"$i\" $selected>$i</option>";
						}
						$extras .= '</select> <input type="submit" class="button" tabindex="1" value="' . $vbphrase['go'] . '" accesskey="s" />';
					}
					else
					{
						$extras = '';
					}
				}
				else
				{
					$extras = '';
				}
			}
			print_table_footer($colcount, $extras);
			break;

		// queries that perform data changes **********************************************************
		case 'UPDATE':
		case 'INSERT':
		case 'REPLACE':
		case 'DELETE':
		case 'ALTER':
		case 'CREATE':
		case 'DROP':
		case 'RENAME':
		case 'TRUNCATE':
		case 'LOAD':
		default:
			if (!$vbulletin->GPC['confirmquery'])
			{
				print_form_header('queries', 'doquery');
				construct_hidden_code('do', 'doquery');
				construct_hidden_code('query', $query);
				construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
				construct_hidden_code('confirmquery', 1);
				print_table_header($vbphrase['confirm_query_execution']);
				print_description_row($vbphrase['query_may_modify_database']);
				print_submit_row($vbphrase['continue'], false, 2, $vbphrase['go_back']);
			}
			else
			{
				$time_before = microtime();
				$db->query_write($query);
				$time_taken = fetch_microtime_difference($time_before);

				print_form_header('queries', 'doquery');
				print_table_header($vbphrase['vbulletin_message']);
				if ($errornum = $db->errno())
				{
					print_description_row(construct_phrase($vbphrase['an_error_occured_while_attempting_to_run_your_query'], $errornum, nl2br(htmlspecialchars_uni($db->error()))));
				}
				else
				{
					print_description_row(construct_phrase($vbphrase['affected_rows'], vb_number_format($db->affected_rows()), vb_number_format($time_taken, 4)));
				}
				print_table_footer();
			}
			break;
	}
}

// ##################### START MODIFY #####################
if ($_REQUEST['do'] == 'modify')
{
	print_form_header('queries', 'doquery');
	print_table_header($vbphrase['execute_sql_query']);
	print_select_row($vbphrase['auto_query'], 'autoquery', $queryoptions, -1);
	print_textarea_row($vbphrase['manual_query'], 'query', '', 10, 55);
	print_input_row($vbphrase['results_to_show_per_page'], 'perpage', 20);
	print_submit_row($vbphrase['continue']);
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 37230 $
|| ####################################################################
\*======================================================================*/
?>