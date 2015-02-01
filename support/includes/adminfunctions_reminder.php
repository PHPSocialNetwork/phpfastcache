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

error_reporting(E_ALL & ~E_NOTICE);
if (!is_object($db))
{
	exit;
}

// constant definitions and redirect systems
define('MAXTITLELENGTH', 50);
define('OVERDUETIME', TIMENOW - 86400);

if (!empty($_REQUEST['add_day']))
{
	$_REQUEST['do'] = 'add';
}

if (!empty($_REQUEST['viewday']))
{
	$_REQUEST['do'] = 'viewday';
	$_REQUEST['day'] = $_REQUEST['viewday'];
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

if ($_POST['do'] == 'redirecter')
{

	if (isset($_POST['dodelete']))
	{
		$_REQUEST['do'] = 'delete';
	}
	elseif (isset($_POST['docomplete']))
	{
		$_POST['do'] = 'complete';
	}
	elseif (isset($_POST['doadd']))
	{
		$_REQUEST['do'] = 'add';
	}
	else
	{
		$_REQUEST['do'] = 'edit';
	}

}

// array of months & month names
$months = array(
	1  => $vbphrase['january'],
	2  => $vbphrase['february'],
	3  => $vbphrase['march'],
	4  => $vbphrase['april'],
	5  => $vbphrase['may'],
	6  => $vbphrase['june'],
	7  => $vbphrase['july'],
	8  => $vbphrase['august'],
	9  => $vbphrase['september'],
	10 => $vbphrase['october'],
	11 => $vbphrase['november'],
	12 => $vbphrase['december']
);

// array of number of days in each month
$monthdays = array(0,
	31,
	iif(date('L', $thismonth) == 1, 29, 28),
	31,
	30,
	31,
	30,
	31,
	31,
	30,
	31,
	30,
	31
);

// array of day names
$daynames = array(
	$vbphrase['sunday'],
	$vbphrase['monday'],
	$vbphrase['tuesday'],
	$vbphrase['wednesday'],
	$vbphrase['thursday'],
	$vbphrase['friday'],
	$vbphrase['saturday']
);

// ###################### Start reminder_sanitizedate #######################
function sanitize_reminder_date(&$month, &$year)
{
	if ($month == 13)
	{
		$month = 1;
		$year++;
	}
	elseif ($month == 0)
	{
		$month = 12;
		$year--;
	}
}

// ###################### Start reminder_getevents #######################
function construct_reminders_summary_string($date, $showevents = 1)
{
	global $event, $vbulletin;

	$out = '';
	if (is_array($event["$date"]))
	{
		if ($showevents)
		{
			$out .= "<br /><span class=\"smallfont\">\n";
			foreach($event["$date"] AS $reminder)
			{
				$out .= "<li" . iif(!$reminder['completedby'], ' style="font-weight:bold"', '') . "><a href=\"reminder.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&amp;reminderid=$reminder[reminderid]\">$reminder[title]</a></li>\n";
			}
			$out .= "</span>\n";
		}
		else

		{
			$out = 1;
		}
	}
	return $out;
}

// ###################### Start reminder_showmonth #######################
function print_reminder_month($month, $year, $showevents = 1)
{

	global $months, $monthdays, $daynames, $vbulletin, $vbphrase;

	sanitize_reminder_date($month, $year);

	print_table_header($months["$month"] . ' ' . $year, 7);

	// write out the column headers
	$d = BEGINDAY;
	echo "<tr class=\"thead\" align=\"center\">\n";
	for ($i=0; $i<7; $i++)
	{
		if ($d > 6)
		{
			$d = 0;
		}
		if ($showevents)
		{
			echo "\t<td width=\"14%\"><b>" . $daynames["$d"] . "</b></td>\n";
		}
		else
		{
			echo "\t<td>" . substr($daynames["$d"], 0, 2) . "</td>\n";
		}
		$d++;
	}
	echo "</tr>\n";

	// prepare variables
	$thismonthstart = mktime(0, 0, 0, $month, 1, $year);
	$thismonthend = mktime(0, 0, 0, $month+1, 1, $year) - 1;
	$startday = getdate($thismonthstart);
	$count = 0;
	$safety = 0;
	$curday = 1;
	$monthstarted = 0;
	$monthcomplete = 0;

	// loop through the days
	while ($monthcomplete == 0 AND $safety < 50)
	{
		echo "<tr " . iif($showevents, 'valign="top" height="50"', 'align="center"') . ">\n";
		for ($i=0; $i<7; $i++)
		{
			if ($d > 6)
			{
				$d = 0;
			}
			if ($d == $startday['wday'])
			{
				$monthstarted = 1;
			}
			if ($monthstarted AND !$monthcomplete)
			{
				$printday = str_pad($curday, 2, 0, STR_PAD_LEFT);
				if ($showevents)
				{
					if ($curday == TODAY)
					{
						echo "\t<td class=\"alt1\" style=\"border: 1px outset\"><input type=\"submit\" class=\"button\" name=\"viewday[$year-$month-$curday]\" value=\"$printday\" tabindex=\"1\" /> <span class=\"smallfont\">(<b>" . $vbphrase['today'] . "</b>)</span>" . construct_reminders_summary_string("$month-$curday") . "</td>\n";
					}
					else
					{
						echo "\t<td class=\"alt2\"><input type=\"submit\" class=\"button\" name=\"viewday[$year-$month-$curday]\" value=\"$printday\" tabindex=\"1\" title=\"" . construct_phrase($vbphrase['add_x'], $vbphrase['event']) . "\" />" . construct_reminders_summary_string("$month-$curday") . "</td>\n";
					}
				}
				else
				{
					echo "\t<td class=\"alt2\"><span class=\"smallfont\">" . iif(construct_reminders_summary_string("$month-$curday", 0), "<a href=\"reminder.php?" . $vbulletin->session->vars['sessionurl'] . "do=viewday&day[$year-$month-$curday]=1\"><b class=\"col-i\">$printday</b></a>", "$printday") . "</span></td>\n";
				}
				$curday++;
				if ($curday > $monthdays["$month"])
				{
					$monthcomplete = 1;
				}
			}
			else
			{
				echo "\t<td>&nbsp;</td>\n";
			}
			$d++;
		}
		echo "</tr>\n";
		$safety++;
	}
}

// ###################### Start reminder_showevent #######################
function print_reminder($event)
{
	global $vbulletin, $vbphrase;

	print_form_header('reminder', 'redirecter');
	construct_hidden_code('reminderid', $event['reminderid']);
	print_table_header(construct_phrase($vbphrase['adminfunctions_reminder_showevent_adminreminderdue'], vbdate($vbphrase['adminfunctions_reminder_showevent_date'], $event['duedate']), $event[reminderid]));
	print_label_row($vbphrase['adminfunctions_reminder_showevent_postedby'], "<a href=\"../member.php?" . $vbulletin->session->vars['sessionurl'] . "u=$event[userid]\" target=\"_blank\">$event[username]</a>");
	print_label_row($vbphrase['title'], $event['title']);
	if (!empty($event['text']))
	{
		print_label_row($vbphrase['adminfunctions_reminder_showevent_extrainfo'], nl2br(htmlspecialchars_uni($event['text'])));
	}
	print_label_row($vbphrase['status'], fetch_reminder_status($event));
	print_table_footer(2, '
		<input type="button" class="button" value="' . $vbphrase['completed'] . '" onclick="js_confirm_completion(' . $event['reminderid'] . ')" tabindex="1" />
		<input type="submit" class="button" name="dodelete" value="' . $vbphrase['delete'] . '" tabindex="1" />
		<input type="submit" class="button" name="doedit" value="' . construct_phrase($vbphrase['edit_x'], $vbphrase['event']) . '" tabindex="1" />
		<input type="button" class="button" value="' . $vbphrase['adminfunctions_reminder_showevent_newevent'] . '" tabindex="1" onclick="'."window.location='reminder.php?" . $vbulletin->session->vars['sessionurl'] . "do=add&amp;year=" . vbdate('Y', $event['duedate']) . "&amp;month=" . vbdate('n', $event['duedate']) . "&amp;add_day=" . vbdate('j', $event['duedate']) . "';" . '" />
	');

}

// ###################### Start eminder_complete #######################
function exec_complete_reminder($reminderid)
{

	global $vbulletin, $permissions, $vbphrase;

	$reminderid = intval($reminderid);

	$event = $vbulletin->db->query_first("
		SELECT reminder.*, u1.username AS username, u2.username AS completedname
		FROM " . TABLE_PREFIX . "reminder AS reminder
		INNER JOIN " . TABLE_PREFIX . "user AS u1 ON(u1.userid = reminder.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS u2 ON(u2.userid = reminder.completedby)
		WHERE reminderid = $reminderid
	");

	// check to see if the event is for administrators only,
	// and if it is deny permission to non admins
	if ($event['adminonly'] AND !($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
	{

		print_stop_message('no_permission');

	}
	elseif ($event['completedby'])
	{

		print_stop_message('event_already_complete', $event['completedname'], vbdate($vbphrase['adminfunctions_reminder_complete_date'], $event['completedtime'], 1), vbdate($vbphrase['adminfunctions_reminder_complete_time'], $event['completedtime']));

	}
	else
	{

		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "reminder
				SET	completedby = " . $vbulletin->userinfo['userid'] . ",
					completedtime = " . TIMENOW . "
			WHERE reminderid = $reminderid
		");
		print_stop_message('completed_x_successfully', $vbphrase['reminder']);

	}

}

// ###################### Start reminder_completeconfirm #######################
function print_confirm_reminder_completed()
{
	global $vbulletin, $vbphrase;
	?>
	<script type="text/javascript">
	<!--
	function js_confirm_completion(id)
	{
		if (confirm('<?php echo addslashes_js($vbphrase['adminfunctions_reminder_completeconfirm_areyousure']); ?>'))
		{
			window.location = 'reminder.php?<?php echo $vbulletin->session->vars['sessionurl']; ?>do=complete&reminderid=' + id;
		}
		else
		{
			return false;
		}
	}
	//-->
	</script>
	<?php

}

// ###################### Start reminder_getstatus #######################
function fetch_reminder_status($event)
{
	global $vbphrase;

	if ($event['completedby'] != 0)
	{
		return construct_phrase($vbphrase['adminfunctions_reminder_getstatus_completedby'], $event['completedname'], vbdate($vbphrase['adminfunctions_reminder_getstatus_date'], $event['completedtime']), vbdate($vbphrase['adminfunctions_reminder_getstatus_time'], $event['completedtime']));
	}
	elseif ($event['duedate'] > OVERDUETIME)
	{
		return $vbphrase['adminfunctions_reminder_getstatus_pendingaction'];
	}
	else
	{
		$diff = TIMENOW - $event['duedate'];
		$days = ceil($diff / 86400);
		return construct_phrase($vbphrase['adminfunctions_reminder_getstatus_overdue'], $days);
	}
}

// ###################### Start reminder_checktitlelength #######################
function verify_reminder_title_length($title)
{
	global $vbphrase;
	$length = strlen($title);
	if ($length > MAXTITLELENGTH)
	{
		$diff = $length - MAXTITLELENGTH;
		print_stop_message('reminder_title_too_long', MAXTITLELENGTH, $diff);
	}
	else
	{
		return $title;
	}
}

// ############################# Start getreminders2 #########################
function fetch_reminders_array2()
{
// prints out all reminders for the appropriate control panel
	global $vbulletin, $permissions, $vbphrase;

	if ($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
	{
		$condition = '';
	}
	else
	{
		$condition = 'AND allowmodcp = 1';
	}

	$reminders = $vbulletin->db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "adminreminder
		WHERE duedate < " . (TIMENOW + 7 * 86400) . "
		$condition ORDER BY duedate
	");
	if ($vbulletin->db->num_rows($reminders))
	{

		print_form_header(iif(VB_AREA == 'AdminCP', '../modcp/reminder', 'reminder'), 'docompleted');
		print_table_header($vbphrase['adminfunctions_getreminders2_header'], 4);
		print_cells_row(array($vbphrase['adminfunctions_getreminders2_duedate'], $vbphrase['event'], $vbphrase['edit'], $vbphrase['status']), 1, 0, -1);

		while ($reminder = $vbulletin->db->fetch_array($reminders))
		{

			if ($reminder['completed'] == 0)
			{
				if ($reminder['duedate'] < TIMENOW)
				{
					$date = '<b class="col-i">%s</b>';
					$status = '<b>'.$vbphrase['adminfunctions_getreminders2_overdue'].'</b>';
					$hint = $vbphrase['adminfunctions_getreminders2_completed'];
					$checkbox = '';
				}
				else
				{
					$date = '%s';
					$status = 'Pending';
					$hint = $vbphrase['adminfunctions_getreminders2_completed'];
					$checkbox = '';
				}
			}
			else
			{
				$date = '%s';
				$status = $vbphrase['adminfunctions_getreminders2_complete'];
				$hint = $vbphrase['adminfunctions_getreminders2_delete'];
				$checkbox = ' checked="checked" disabled="disabled"';
			}

			$cell = array();
			$cell[] = '<p class="smallfont" style="white-space:nowrap">' . sprintf($date, vbdate("M jS 'y", $reminder['duedate'])) . '</p>';
			$cell[] = '<p class="smallfont">'.$reminder['title'].'</p>';
			//$cell[] = '<span class="smallfont">'.construct_link_code($status, $link, 0, $hint) . '</span>';
			$cell[] = '<p class="smallfont">' . construct_link_code($vbphrase['edit'], "reminder.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;id[$reminder[adminreminderid]]=1") . '</p>';
			$cell[] = '<p class="smallfont" style="text-align:' . vB_Template_Runtime::fetchStyleVar('right') . '">'.$status.'<input type="checkbox" name="id['.$reminder['adminreminderid'].']" value="1" tabindex="1"'.$checkbox.' /></p>';
			print_cells_row($cell, 0, '', -2);

		}

		print_submit_row($vbphrase['adminfunctions_getreminders2_delcomplete'], 0, 4);

	}
	unset($reminder);
	$vbulletin->db->free_result($reminders);
}

// ############################# Start getreminders #########################
function fetch_reminders_array()
{
	global $vbulletin, $vbphrase;

	$reminders = $vbulletin->db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "reminder
		WHERE duedate < " . TIMENOW." AND
		completedby = 0
	");

	if ($reminders['total'] == 1)
	{
		$reminders['s'] = '';
		$reminders['them'] = $vbphrase['adminfunctions_getreminders_it'];
	}
	else
	{
		$reminders['s'] = $vbphrase['adminfunctions_getreminders_s'];
		$reminders['them'] = $vbphrase['adminfunctions_getreminders_them'];
	}

	if ($reminders['total'] != 0)
	{
		$reminders['script'] = "
		<script type=\"text/javascript\">
		<!--
		if (confirm('" . construct_phrase($vbphrase['adminfunctions_getreminders_confirm'], unhtmlspecialchars($vbulletin->userinfo['username']), $reminders[total], $reminders[s], $reminders[them]) . "'))
		{
			window.location = 'reminder.php?' . $vbulletin->session->vars['sessionurl_js'];
		}
		// -->
		</script>\n";
	}
	else
	{
		$reminders['script'] = '';
	}

	return $reminders;

}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>