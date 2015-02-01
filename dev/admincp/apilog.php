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
$phrasegroups = array('logging');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// #############################################################################
print_cp_header($vbphrase['api_log']);
// #############################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'choose';
}

// ###################### Start view #######################
if ($_REQUEST['do'] == 'view' AND can_access_logs($vbulletin->config['SpecialUsers']['canviewadminlog'], 1, '<p>' . $vbphrase['control_panel_log_viewing_restricted'] . '</p>'))
{
	$vbulletin->input->clean_array_gpc('r', array(
		'apiclientname'	    => TYPE_NOHTML,
		'userid'		    => TYPE_INT,
		'apiclientid'       => TYPE_INT,
		'apiclientuniqueid' => TYPE_STR,
		'pagenumber'        => TYPE_INT,
		'orderby'           => TYPE_STR,
		'startdate'         => TYPE_UNIXTIME,
		'enddate'           => TYPE_UNIXTIME
	));

	if ($vbulletin->GPC['userid'] >= 0 OR $vbulletin->GPC['apiclientid'] OR $vbulletin->GPC['apiclientuniqueid'] OR $vbulletin->GPC['apiclientname'] OR $vbulletin->GPC['startdate'] OR $vbulletin->GPC['enddate'])
	{
		$sqlconds = 'WHERE 1=1 ';
		if ($vbulletin->GPC['apiclientid'])
		{
			$sqlconds .= " AND apilog.apiclientid = " . $vbulletin->GPC['apiclientid'];
		}
		elseif ($vbulletin->GPC['apiclientuniqueid'])
		{
			$sqlconds .= " AND apiclient.uniqueid = '" . $vbulletin->db->escape_string($vbulletin->GPC['apiclientuniqueid']) . "'";
		}
		else
		{
			if ($vbulletin->GPC['userid'] >= 0)
			{
				$sqlconds .= " AND apiclient.userid = " . intval($vbulletin->GPC['userid']);
			}
			if ($vbulletin->GPC['apiclientname'])
			{
				$sqlconds .= " AND apiclient.clientname = '" . $vbulletin->db->escape_string($vbulletin->GPC['apiclientname']) . "'";
			}
		}
		if ($vbulletin->GPC['startdate'])
		{
			$sqlconds .= " AND apilog.dateline >= " . $vbulletin->GPC['startdate'];
		}
		if ($vbulletin->GPC['enddate'])
		{
			$sqlconds .= " AND apilog.dateline <= " . $vbulletin->GPC['enddate'];
		}
	}
	else
	{
		$sqlconds = '';
	}

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

	$counter = $db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "apilog AS apilog
		LEFT JOIN " . TABLE_PREFIX . "apiclient AS apiclient ON (apiclient.apiclientid = apilog.apiclientid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (apiclient.userid = user.userid)
	$sqlconds");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	switch ($vbulletin->GPC['orderby'])
	{
		case 'user':
			$order = 'user.username ASC, apilog.apilogid DESC';
			break;
		case 'clientname':
			$order = 'apiclient.clientname ASC, apilog.apiclientid ASC, apilog.apilogid DESC';
			break;
		default:	// Date
			$vbulletin->GPC['orderby'] = 'date';
			$order = 'apilogid DESC';
	}

	$logs = $db->query_read("
		SELECT apilog.*, user.username, apiclient.clientname, apiclient.userid
		FROM " . TABLE_PREFIX . "apilog AS apilog
		LEFT JOIN " . TABLE_PREFIX . "apiclient AS apiclient ON (apiclient.apiclientid = apilog.apiclientid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (apiclient.userid = user.userid)
		$sqlconds
		ORDER BY $order
		LIMIT $startat, " .  $vbulletin->GPC['perpage']
	);

	if ($db->num_rows($logs))
	{

		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] .
							"\" tabindex=\"1\" onclick=\"window.location='apilog.php?" . $vbulletin->session->vars['sessionurl'] .
							"do=view&amp;apiclientname=" . $vbulletin->GPC['apiclientname'] .
							"&amp;apiclientid=" . $vbulletin->GPC['apiclientid'] .
							"&amp;u=" . $vbulletin->GPC['userid'] .
							"&amp;pp=" . $vbulletin->GPC['perpage'] .
							"&amp;orderby=" . $vbulletin->GPC['orderby'] .
							"&amp;page=1" .
							"&amp;startdate=" . $vbulletin->GPC['startdate'] .
							"&amp;enddate=" . $vbulletin->GPC['enddate'] .
							"'\"/>";

			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] .
						"\" tabindex=\"1\" onclick=\"window.location='apilog.php?" . $vbulletin->session->vars['sessionurl'] .
						"do=view&amp;apiclientname=" . $vbulletin->GPC['apiclientname'] .
						"&amp;apiclientid=" . $vbulletin->GPC['apiclientid'] .
						"&amp;u=" . $vbulletin->GPC['userid'] .
						"&amp;pp=" . $vbulletin->GPC['perpage'] .
						"&amp;orderby=" . $vbulletin->GPC['orderby'] .
						"&amp;page=$prv" .
						"&amp;startdate=" . $vbulletin->GPC['startdate'] .
						"&amp;enddate=" . $vbulletin->GPC['enddate'] .
						"'\"/>";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] .
						" &gt;\" tabindex=\"1\" onclick=\"window.location='apilog.php?" .
						$vbulletin->session->vars['sessionurl'] .
						"do=view&amp;apiclientname=" . $vbulletin->GPC['apiclientname'] .
						"&amp;apiclientid=" . $vbulletin->GPC['apiclientid'] .
						"&amp;u=" . $vbulletin->GPC['userid'] .
						"&amp;pp=" . $vbulletin->GPC['perpage'] .
						"&amp;orderby=" . $vbulletin->GPC['orderby'] .
						"&amp;page=$nxt" .
						"&amp;startdate=" . $vbulletin->GPC['startdate'] .
						"&amp;enddate=" . $vbulletin->GPC['enddate'] .
						"'\"/>";

			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] .
						" &raquo;\" tabindex=\"1\" onclick=\"window.location='apilog.php?" . $vbulletin->session->vars['sessionurl'] .
						"do=view&amp;apiclientname=" . $vbulletin->GPC['apiclientname'] .
						"&amp;apiclientid=" . $vbulletin->GPC['apiclientid'] .
						"&amp;u=" . $vbulletin->GPC['userid'] .
						"&amp;pp=" . $vbulletin->GPC['perpage'] .
						"&amp;orderby=" . $vbulletin->GPC['orderby'] .
						"&amp;page=$totalpages" .
						"&amp;startdate=" . $vbulletin->GPC['startdate'] .
						"&amp;enddate=" . $vbulletin->GPC['enddate'] .
						"'\"/>";
		}

		print_form_header('apilog', 'remove');
		print_description_row(construct_link_code($vbphrase['restart'], "apilog.php?" . $vbulletin->session->vars['sessionurl']), 0, 8, 'thead', vB_Template_Runtime::fetchStyleVar('right'));
		print_table_header(construct_phrase($vbphrase['api_log_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 8);

		$headings = array();
		$headings[] = $vbphrase['id'];
		$headings[] = $vbphrase['apiclientid'];
		$headings[] = "<a href='apilog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&amp;apiclientname=" . $vbulletin->GPC['apiclientname'] . "&amp;apiclientid=" . $vbulletin->GPC['apiclientid'] . "&amp;u=" . $vbulletin->GPC['userid'] . "&amp;pp=" . $vbulletin->GPC['perpage'] . "&amp;orderby=clientname&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;startdate=" . $vbulletin->GPC['startdate'] . "&amp;enddate=" . $vbulletin->GPC['enddate'] . "' title='" . $vbphrase['order_by_clientname'] . "'>" . $vbphrase['apiclientname'] . "</a>";
		$headings[] = "<a href='apilog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&amp;apiclientname=" . $vbulletin->GPC['apiclientname'] . "&amp;apiclientid=" . $vbulletin->GPC['apiclientid'] . "&amp;u=" . $vbulletin->GPC['userid'] . "&amp;pp=" . $vbulletin->GPC['perpage'] . "&amp;orderby=user&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;startdate=" . $vbulletin->GPC['startdate'] . "&amp;enddate=" . $vbulletin->GPC['enddate'] . "' title='" . $vbphrase['order_by_username'] . "'>" . $vbphrase['username'] . "</a>";
		$headings[] = "<a href='apilog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&amp;apiclientname=" . $vbulletin->GPC['apiclientname'] . "&amp;apiclientid=" . $vbulletin->GPC['apiclientid'] . "&amp;u=" . $vbulletin->GPC['userid'] . "&amp;pp=" . $vbulletin->GPC['perpage'] . "&amp;orderby=date&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;startdate=" . $vbulletin->GPC['startdate'] . "&amp;enddate=" . $vbulletin->GPC['enddate'] . "' title='" . $vbphrase['order_by_date'] . "'>" . $vbphrase['date'] . "</a>";
		$headings[] = $vbphrase['apimethod'];
		$headings[] = $vbphrase['paramget'];
		$headings[] = $vbphrase['ip_address'];
		print_cells_row($headings, 1);

		while ($log = $db->fetch_array($logs))
		{
			$cell = array();
			$cell[] = $log['apilogid'];
			$cell[] = "<a href=\"apilog.php?" . $vbulletin->session->vars['sessionurl'] . "do=viewclient&amp;apiclientid=$log[apiclientid]\"><b>$log[apiclientid]</b></a>";
			$cell[] = "<a href=\"apilog.php?" . $vbulletin->session->vars['sessionurl'] . "do=viewclient&amp;apiclientid=$log[apiclientid]\"><b>" . htmlspecialchars_uni($log['clientname']) . "</b></a>";
			$cell[] = iif(!empty($log['username']), "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$log[userid]\"><b>$log[username]</b></a>", $vbphrase['guest']);
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $log['dateline']) . '</span>';
			$cell[] = htmlspecialchars_uni($log['method']);
			$cell[] = htmlspecialchars_uni(print_r(@unserialize($log['paramget']), true));
			$cell[] = '<span class="smallfont">' . iif($log['ipaddress'], "<a href=\"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=doips&amp;depth=2&amp;ipaddress=$log[ipaddress]&amp;hash=" . CP_SESSIONHASH . "\">$log[ipaddress]</a>", '&nbsp;') . '</span>';
			print_cells_row($cell);
		}

		print_table_footer(8, "$firstpage $prevpage &nbsp; $nextpage $lastpage");

	}
	else
	{
		print_stop_message('no_log_entries_matched_your_query');
	}
}

// ###################### Start prune log #######################
if ($_REQUEST['do'] == 'prunelog' AND can_access_logs($vbulletin->config['SpecialUsers']['canpruneadminlog'], 0, '<p>' . $vbphrase['control_panel_log_pruning_permission_restricted'] . '</p>'))
{
	$vbulletin->input->clean_array_gpc('r', array(
		'apiclientid'	=> TYPE_INT,
		'daysprune'		=> TYPE_INT
	));

	$datecut = TIMENOW - (86400 * $vbulletin->GPC['daysprune']);
	$query = "SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "apilog AS apilog WHERE dateline < $datecut";

	if ($vbulletin->GPC['apiclientid'])
	{
		$query .= "\nAND apiclientid = " . $vbulletin->GPC['apiclientid'];
	}

	$logs = $db->query_first($query);
	if ($logs['total'])
	{
		print_form_header('apilog', 'doprunelog');
		construct_hidden_code('datecut', $datecut);
		construct_hidden_code('apiclientid', $vbulletin->GPC['apiclientid']);
		print_table_header($vbphrase['prune_api_log']);
		print_description_row(construct_phrase($vbphrase['are_you_sure_you_want_to_prune_x_log_entries_from_api_log'], vb_number_format($logs['total'])));
		print_submit_row($vbphrase['yes'], 0, 0, $vbphrase['no']);
	}
	else
	{
		print_stop_message('no_log_entries_matched_your_query');
	}
}

// ###################### Start do prune log #######################
if ($_POST['do'] == 'doprunelog' AND can_access_logs($vbulletin->config['SpecialUsers']['canpruneadminlog'], 0, '<p>' . $vbphrase['control_panel_log_pruning_permission_restricted'] . '</p>'))
{
	$vbulletin->input->clean_array_gpc('p', array(
		'apiclientid'	=> TYPE_INT,
		'datecut'		=> TYPE_INT
	));


	$query = "DELETE FROM " . TABLE_PREFIX . "apilog WHERE dateline < " . $vbulletin->GPC['datecut'];
	if ($vbulletin->GPC['apiclientid'])
	{
		$query .= "\nAND apiclientid = " . $vbulletin->GPC['apiclientid'];
	}

	$db->query_write($query);

	define('CP_REDIRECT', 'apilog.php?do=choose');
	print_stop_message('pruned_control_panel_log_successfully');
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'choose')
{

	if (can_access_logs($vbulletin->config['SpecialUsers']['canviewadminlog'], 1))
	{
		$show_admin_log = true;
	}
	else
	{
		echo '<p>' . $vbphrase['control_panel_log_viewing_restricted'] . '</p>';
	}

	if ($show_admin_log)
	{
		log_admin_action();

		$clientnames = $db->query_read("
			SELECT DISTINCT clientname
			FROM " . TABLE_PREFIX . "apiclient AS apiclient
			ORDER BY clientname
		");
		$clientnamelist = array('no_value' => $vbphrase['all_api_clients']);
		while ($clientname = $db->fetch_array($clientnames))
		{
			$clientnamelist["$clientname[clientname]"] = $clientname['clientname'];
		}
		$users = $db->query_read("
			SELECT DISTINCT apiclient.userid, user.username
			FROM " . TABLE_PREFIX . "apiclient AS apiclient
			LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid)
			ORDER BY username
		");
		$userlist = array('-1' => $vbphrase['all_users']);
		while ($user = $db->fetch_array($users))
		{
			if ($user['userid'] === '0')
			{
				$user['username'] = $vbphrase['guest'];
			}
			$userlist["$user[userid]"] = $user['username'];
		}

		$perpage_options = array(
			5 => 5,
			10 => 10,
			15 => 15,
			20 => 20,
			25 => 25,
			30 => 30,
			40 => 40,
			50 => 50,
			100 => 100,
		);

		if (!$vbulletin->options['enableapilog'])
		{
			print_table_start();
			print_description_row($vbphrase['apilog_disabled_options']);
			print_table_footer(2, '', '', false);
		}

		print_form_header('apilog', 'view');
		print_table_header($vbphrase['api_log_viewer']);
		print_select_row($vbphrase['log_entries_to_show_per_page'], 'perpage', $perpage_options, 15);
		print_select_row($vbphrase['show_only_entries_generated_by_apiclientname'], 'apiclientname', $clientnamelist, '-1');
		print_select_row($vbphrase['show_only_entries_related_to_remembered_user'], 'userid', $userlist, '-1');
		print_input_row($vbphrase['api_client_id'], 'apiclientid', '', true, 10);
		print_input_row($vbphrase['api_client_uniqueid'], 'apiclientuniqueid', '', true, 30);

		print_time_row($vbphrase['start_date'], 'startdate', 0, 0);
		print_time_row($vbphrase['end_date'], 'enddate', 0, 0);

		print_select_row($vbphrase['order_by'], 'orderby', array('date' => $vbphrase['date'], 'user' => $vbphrase['user'], 'clientname' => $vbphrase['apiclientname']), 'date');
		print_submit_row($vbphrase['view'], 0);

		if (can_access_logs($vbulletin->config['SpecialUsers']['canpruneadminlog'], 1))
		{
			print_form_header('apilog', 'prunelog');
			print_table_header($vbphrase['prune_api_log']);
			print_input_row($vbphrase['remove_entries_logged_by_apiclientid'], 'apiclientid');
			print_input_row($vbphrase['remove_entries_older_than_days'], 'daysprune', 30);
			print_submit_row($vbphrase['prune_api_log'], 0);
		}
		else
		{
			echo '<p>' . $vbphrase['control_panel_log_pruning_permission_restricted'] . '</p>';
		}
	}
}

// ###################### Start view client #######################
if ($_REQUEST['do'] == 'viewclient')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'apiclientid'	=> TYPE_UINT
	));

	if (!$vbulletin->GPC['apiclientid']
			OR
		!($client = $db->query_first("
			SELECT apiclient.*, user.username FROM " . TABLE_PREFIX . "apiclient AS apiclient
			LEFT JOIN " . TABLE_PREFIX . "user AS user using(userid)
			WHERE apiclientid = " . $vbulletin->GPC['apiclientid'] . "
		")))
	{
		print_stop_message('invalidid', 'apiclientid');
	}

	print_form_header('api', 'viewclient');
	print_table_header($vbphrase['apiclient']);
	print_label_row($vbphrase['apiclientid'], $client['apiclientid']);
	print_label_row($vbphrase['apiclientname'], $client['clientname']);
	print_label_row($vbphrase['apiclientversion'], $client['clientversion']);
	print_label_row($vbphrase['apiclient_platformname'], $client['platformname']);
	print_label_row($vbphrase['apiclient_platformversion'], $client['platformversion']);
	print_label_row($vbphrase['apiclient_uniqueid'], $client['uniqueid']);
	print_label_row($vbphrase['apiclient_initialipaddress'], iif(!empty($client['initialipaddress']), "<a href=\"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=doips&amp;depth=2&amp;ipaddress=$client[initialipaddress]&amp;hash=" . CP_SESSIONHASH . "\">$client[initialipaddress]</a>", "&nbsp;"));
	print_label_row($vbphrase['apiclient_initialtime'], vbdate($vbulletin->options['dateformat'] . ' ' .$vbulletin->options['timeformat'], $client['dateline']));
	print_label_row($vbphrase['apiclient_lastactivity'], vbdate($vbulletin->options['dateformat'] . ' ' .$vbulletin->options['timeformat'], $client['lastactivity']));
	print_label_row($vbphrase['apiclient_clienthash'], $client['clienthash']);
	print_label_row($vbphrase['apiclient_secret'], $client['secret']);
	print_label_row($vbphrase['apiclient_apiaccesstoken'], $client['apiaccesstoken']);
	print_label_row($vbphrase['apiclient_remembereduser'], iif(!empty($client['username']), "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$client[userid]\"><b>$client[username]</b></a>", $vbphrase['guest']));
	print_table_footer();
}

echo '<p class="smallfont" align="center"><a href="#" onclick="js_open_help(\'adminlog\', \'restrict\', \'\');">' . $vbphrase['want_to_access_grant_access_to_this_script'] . '</a></p>';

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>
