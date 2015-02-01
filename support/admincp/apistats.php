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
define('CVS_REVISION', '$RCSfile$ - $Revision: 33931 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('stats', 'logging');
$specialtemplates = array('userstats', 'maxloggedin');

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_stats.php');

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['api_stats']);

if (empty($_REQUEST['do']) OR $_REQUEST['do'] == 'index' OR $_REQUEST['do'] == 'top' OR $_REQUEST['do'] == 'method' OR $_REQUEST['do'] == 'client')
{
	if (!$vbulletin->options['enableapilog'])
	{
		print_table_start();
		print_description_row($vbphrase['apilog_disabled_options']);
		print_table_footer(2, '', '', false);
	}

	print_form_header('stats', 'index');
	print_table_header($vbphrase['api_stats']);
	print_label_row(construct_link_code($vbphrase['top_statistics'], 'apistats.php?do=top'), '');
	print_label_row(construct_link_code($vbphrase['top_apimethods'], 'apistats.php?do=method'), '');
	print_label_row(construct_link_code($vbphrase['top_apiclients'], 'apistats.php?do=client'), '');
	print_label_row(construct_link_code($vbphrase['apiclient_activity_statistics'], 'apistats.php?do=activity'), '');
	print_table_footer();
}

// Find most popular things below
if ($_REQUEST['do'] == 'top')
{
	// Top Client
	$maxclient = $db->query_first("
		SELECT apilog.apiclientid, apiclient.clientname, COUNT(*) as c
		FROM " . TABLE_PREFIX . "apilog AS apilog
		LEFT JOIN " . TABLE_PREFIX . "apiclient AS apiclient using(apiclientid)
		GROUP BY apilog.apiclientid
		ORDER BY c DESC
	");

	// Top API Method
	$maxmethod = $db->query_first("
		SELECT apilog.method, COUNT(*) as c
		FROM " . TABLE_PREFIX . "apilog AS apilog
		GROUP BY apilog.method
		ORDER BY c DESC
	");

	print_form_header('');
	print_table_header($vbphrase['top']);

	print_label_row($vbphrase['top_apiclient'], construct_link_code($maxclient['clientname'] . " ($vbphrase[id]: " . $maxclient['apiclientid'] . ')', "apilog.php?do=viewclient&apiclientid=" . $maxclient['apiclientid']) . " (" . construct_phrase($vbphrase['api_x_calls'], $maxclient['c']) . ")");
	print_label_row($vbphrase['top_apimethod'], $maxmethod['method'] . " (" . construct_phrase($vbphrase['api_x_calls'], $maxclient['c']) . ")");
	print_table_footer();

}

if ($_REQUEST['do'] == 'method')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber'	=> TYPE_INT,
	));

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

	$counter = $db->query_first("SELECT COUNT(*) AS total FROM (
		SELECT method, COUNT(*) FROM " . TABLE_PREFIX . "apilog AS apilog
		GROUP BY method
	) AS t");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	$logs = $db->query_read("
		SELECT method, COUNT(*) AS c
		FROM " . TABLE_PREFIX . "apilog AS apilog
		GROUP BY method
		ORDER BY c DESC
		LIMIT $startat, " .  $vbulletin->GPC['perpage']
	);

	if ($db->num_rows($logs))
	{

		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] .
							"\" tabindex=\"1\" onclick=\"window.location='apistats.php?" . $vbulletin->session->vars['sessionurl'] .
							"do=method" .
							"&pp=" . $vbulletin->GPC['perpage'] .
							"&page=1" .
							"'\"/>";

			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] .
						"\" tabindex=\"1\" onclick=\"window.location='apistats.php?" . $vbulletin->session->vars['sessionurl'] .
						"do=method" .
						"&pp=" . $vbulletin->GPC['perpage'] .
						"&page=$prv" .
						"'\"/>";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] .
						" &gt;\" tabindex=\"1\" onclick=\"window.location='apistats.php?" .
						$vbulletin->session->vars['sessionurl'] .
						"do=method" .
						"&amp;pp=" . $vbulletin->GPC['perpage'] .
						"&amp;page=$nxt" .
						"'\"/>";

			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] .
						" &raquo;\" tabindex=\"1\" onclick=\"window.location='apistats.php?" . $vbulletin->session->vars['sessionurl'] .
						"do=method" .
						"&amp;pp=" . $vbulletin->GPC['perpage'] .
						"&amp;page=$totalpages" .
						"'\"/>";
		}

		print_form_header('apilog', 'remove');
		print_table_header(construct_phrase($vbphrase['top_api_methods_viewer_page_x_y_there_are_z_total_methods'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 8);

		$headings = array();
		$headings[] = $vbphrase['apimethod'];
		$headings[] = $vbphrase['apicalls'];
		print_cells_row($headings, 1);

		while ($log = $db->fetch_array($logs))
		{
			$cell = array();
			$cell[] = htmlspecialchars_uni($log['method']);
			$cell[] = $log['c'];
			print_cells_row($cell);
		}

		print_table_footer(2, "$firstpage $prevpage &nbsp; $nextpage $lastpage");

	}
	else
	{
		print_stop_message('no_log_entries_matched_your_query');
	}
}

if ($_REQUEST['do'] == 'client')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber'	=> TYPE_INT,
	));

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

	$counter = $db->query_first("SELECT COUNT(*) AS total FROM (
		SELECT apiclientid, COUNT(*)
		FROM " . TABLE_PREFIX . "apilog AS apilog
		GROUP BY apiclientid
	) AS t");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	$logs = $db->query_read("
		SELECT apilog.apiclientid, apiclient.userid, apiclient.clientname, user.username, COUNT(*) as c
		FROM " . TABLE_PREFIX . "apilog AS apilog
		LEFT JOIN " . TABLE_PREFIX . "apiclient AS apiclient ON (apiclient.apiclientid = apilog.apiclientid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (apiclient.userid = user.userid)
		GROUP BY apilog.apiclientid
		ORDER BY c DESC
		LIMIT $startat, " .  $vbulletin->GPC['perpage']
	);

	if ($db->num_rows($logs))
	{

		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] .
							"\" tabindex=\"1\" onclick=\"window.location='apistats.php?" . $vbulletin->session->vars['sessionurl'] .
							"do=client" .
							"&pp=" . $vbulletin->GPC['perpage'] .
							"&page=1" .
							"'\"/>";

			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] .
						"\" tabindex=\"1\" onclick=\"window.location='apistats.php?" . $vbulletin->session->vars['sessionurl'] .
						"do=client" .
						"&pp=" . $vbulletin->GPC['perpage'] .
						"&page=$prv" .
						"'\"/>";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] .
						" &gt;\" tabindex=\"1\" onclick=\"window.location='apistats.php?" .
						$vbulletin->session->vars['sessionurl'] .
						"do=client" .
						"&amp;pp=" . $vbulletin->GPC['perpage'] .
						"&amp;page=$nxt" .
						"'\"/>";

			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] .
						" &raquo;\" tabindex=\"1\" onclick=\"window.location='apistats.php?" . $vbulletin->session->vars['sessionurl'] .
						"do=client" .
						"&amp;pp=" . $vbulletin->GPC['perpage'] .
						"&amp;page=$totalpages" .
						"'\"/>";
		}

		print_form_header('apilog', 'remove');
		print_table_header(construct_phrase($vbphrase['top_api_clients_viewer_page_x_y_there_are_z_total_clients'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 8);

		$headings = array();
		$headings[] = $vbphrase['apiclientid'];
		$headings[] = $vbphrase['apiclientname'];
		$headings[] = $vbphrase['username'];
		$headings[] = $vbphrase['apicalls'];
		$headings[] = $vbphrase['controls'];
		print_cells_row($headings, 1);

		while ($log = $db->fetch_array($logs))
		{
			$cell = array();
			$cell[] = "<a href=\"apilog.php?" . $vbulletin->session->vars['sessionurl'] . "do=viewclient&amp;apiclientid=$log[apiclientid]\"><b>$log[apiclientid]</b></a>";
			$cell[] = "<a href=\"apilog.php?" . $vbulletin->session->vars['sessionurl'] . "do=viewclient&amp;apiclientid=$log[apiclientid]\"><b>" . htmlspecialchars_uni($log['clientname']) . "</b></a>";
			$cell[] = iif(!empty($log['username']), "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$log[userid]\"><b>$log[username]</b></a>", $vbphrase['guest']);
			$cell[] = $log['c'];
			$cell[] = "<input type=\"button\" class=\"button\" value=\"$vbphrase[view_logs]\" onclick=\"window.location = 'apilog.php?{$vbulletin->session->vars[sessionurl_js]}do=view&apiclientid=$log[apiclientid]';\" />";
			print_cells_row($cell);
		}

		print_table_footer(5, "$firstpage $prevpage &nbsp; $nextpage $lastpage");

	}
	else
	{
		print_stop_message('no_log_entries_matched_your_query');
	}
}

if ($_REQUEST['do'] == 'activity')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'start'     => TYPE_ARRAY_INT,
		'end'       => TYPE_ARRAY_INT,
		'scope'     => TYPE_STR,
		'sort'      => TYPE_STR,
		'nullvalue' => TYPE_BOOL,
	));

	// Default View Values
	if (empty($vbulletin->GPC['start']))
	{
		$vbulletin->GPC['start'] = TIMENOW - 3600 * 24 * 30;
	}

	if (empty($vbulletin->GPC['end']))
	{
		$vbulletin->GPC['end'] = TIMENOW;
	}

	switch ($vbulletin->GPC['sort'])
	{
		case 'date_asc':
			$orderby = 'dateline ASC';
			break;
		case 'date_desc':
			$orderby = 'dateline DESC';
			break;
		case 'total_asc':
			$orderby = 'total ASC';
			break;
		case 'total_desc':
			$orderby = 'total DESC';
			break;
		default:
			$orderby = 'dateline DESC';
	}

	print_statistic_code($vbphrase['apiclient_activity_statistics'], 'activity', $vbulletin->GPC['start'], $vbulletin->GPC['end'], $vbulletin->GPC['nullvalue'], $vbulletin->GPC['scope'], $vbulletin->GPC['sort'], 'apistats');

	if (!empty($vbulletin->GPC['scope']))
	{ // we have a submitted form
		$start_time = intval(mktime(0, 0, 0, $vbulletin->GPC['start']['month'], $vbulletin->GPC['start']['day'], $vbulletin->GPC['start']['year']));
		$end_time = intval(mktime(0, 0, 0, $vbulletin->GPC['end']['month'], $vbulletin->GPC['end']['day'], $vbulletin->GPC['end']['year']));
		if ($start_time >= $end_time)
		{
			print_stop_message('start_date_after_end');
		}

		switch ($vbulletin->GPC['scope'])
		{
			case 'weekly':
				$sqlformat = '%U %Y';
				$phpformat = '# (! Y)';
				break;
			case 'monthly':
				$sqlformat = '%m %Y';
				$phpformat = '! Y';
				break;
			default:
				$sqlformat = '%w %U %m %Y';
				$phpformat = '! d, Y';
				break;
		}

		$statistics = $db->query_read("
			SELECT COUNT(*) AS total,
			DATE_FORMAT(from_unixtime(dateline), '$sqlformat') AS formatted_date,
			AVG(dateline) AS dateline
			FROM " . TABLE_PREFIX . "apilog
			WHERE dateline >= $start_time
				AND dateline <= $end_time
			GROUP BY formatted_date
			" . (empty($vbulletin->GPC['nullvalue']) ? " HAVING total > 0 " : "") . "
			ORDER BY $orderby
		");

		while ($stats = $db->fetch_array($statistics))
		{ // we will now have each days total of the type picked and we can sort through it
			$month = strtolower(date('F', $stats['dateline']));
			$dates[] = str_replace(' ', '&nbsp;', str_replace('#', $vbphrase['week'] . '&nbsp;' . strftime('%U', $stats['dateline']), str_replace('!', $vbphrase["$month"], date($phpformat, $stats['dateline']))));
			$results[] = $stats['total'];
		}

		if (!sizeof($results))
		{
			//print_array($results);
			print_stop_message('no_matches_found');
		}

		// we'll need a poll image
		$style = $db->query_first("
			SELECT styleid, newstylevars FROM " . TABLE_PREFIX . "style
			WHERE styleid = " . $vbulletin->options['styleid'] . "
			LIMIT 1
		");
		$vbulletin->stylevars = unserialize($style['newstylevars']);
		fetch_stylevars($style, $vbulletin->userinfo);

		print_form_header('');
		print_table_header($vbphrase['results'], 3);
		print_cells_row(array($vbphrase['date'], '&nbsp;', $vbphrase['total']), 1);
		$maxvalue = max($results);
		foreach ($results as $key => $value)
		{
			$i++;
			$bar = ($i % 6) + 1;
			if ($maxvalue == 0)
			{
				$percentage = 100;
			}
			else
			{
				$percentage = ceil(($value/$maxvalue) * 100);
			}
			print_statistic_result($dates["$key"], $bar, $value, $percentage);
		}
		print_table_footer(3);
	}
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 33931 $
|| ####################################################################
\*======================================================================*/
?>