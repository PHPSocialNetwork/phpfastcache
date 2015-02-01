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
define('CVS_REVISION', '$RCSfile$ - $Revision: 42666 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('logging', 'threadmanage');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_log_error.php');

// ############################# LOG ACTION ###############################
if (!can_administer('canadminmodlog'))
{
	print_cp_no_permission();
}

log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['moderator_log']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'choose';
}

// ###################### Start view #######################
if ($_REQUEST['do'] == 'view')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'userid'     => TYPE_UINT,
		'modaction'  => TYPE_STR,
		'orderby'    => TYPE_NOHTML,
		'product'    => TYPE_STR,
		'startdate'  => TYPE_UNIXTIME,
		'enddate'    => TYPE_UNIXTIME,
	));

	$princids = array(
		'poll_question'    => $vbphrase['question'],
		'post_title'       => $vbphrase['post'],
		'thread_title'     => $vbphrase['thread'],
		'forum_title'      => $vbphrase['forum'],
		'attachment_title' => $vbphrase['attachment'],
	);

	$sqlconds = array();
	$hook_query_fields = $hook_query_joins = '';

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}

	if ($vbulletin->GPC['userid'] OR $vbulletin->GPC['modaction'])
	{
		if ($vbulletin->GPC['userid'])
		{
			$sqlconds[] = "moderatorlog.userid = " . $vbulletin->GPC['userid'];
		}
		if ($vbulletin->GPC['modaction'])
		{
			$sqlconds[] = "moderatorlog.action LIKE '%" . $db->escape_string_like($vbulletin->GPC['modaction']) . "%'";
		}
	}

	if ($vbulletin->GPC['startdate'])
	{
		$sqlconds[] = "moderatorlog.dateline >= " . $vbulletin->GPC['startdate'];
	}

	if ($vbulletin->GPC['enddate'])
	{
 		$sqlconds[] = "moderatorlog.dateline <= " . $vbulletin->GPC['enddate'];
	}

	if ($vbulletin->GPC['product'])
	{
		if ($vbulletin->GPC['product'] == 'vbulletin')
		{
			$sqlconds[] = "moderatorlog.product IN ('', 'vbulletin')";
		}
		else
		{
			$sqlconds[] = "moderatorlog.product = '" . $db->escape_string($vbulletin->GPC['product']) . "'";
		}
	}

	($hook = vBulletinHook::fetch_hook('admin_modlogviewer_query')) ? eval($hook) : false;

	$counter = $db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "moderatorlog AS moderatorlog
		" . (!empty($sqlconds) ? "WHERE " . implode("\r\n\tAND ", $sqlconds) : "") . "
	");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

	switch($vbulletin->GPC['orderby'])
	{
		case 'user':
			$order = 'username ASC, dateline DESC';
			break;
		case 'modaction':
			$order = 'action ASC, dateline DESC';
			break;
		case 'date':
		default:
			$order = 'dateline DESC';
	}

	$logs = $db->query_read("
		SELECT moderatorlog.*, user.username,
			post.title AS post_title, forum.title AS forum_title, thread.title AS thread_title, poll.question AS poll_question, attachment.filename AS attachment_title
			$hook_query_fields
		FROM " . TABLE_PREFIX . "moderatorlog AS moderatorlog
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = moderatorlog.userid)
		LEFT JOIN " . TABLE_PREFIX . "post AS post ON (post.postid = moderatorlog.postid)
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum ON (forum.forumid = moderatorlog.forumid)
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = moderatorlog.threadid)
		LEFT JOIN " . TABLE_PREFIX . "poll AS poll ON (poll.pollid = moderatorlog.pollid)
		LEFT JOIN " . TABLE_PREFIX . "attachment AS attachment ON (attachment.attachmentid = moderatorlog.attachmentid)
		$hook_join_fields
		" . (!empty($sqlconds) ? "WHERE " . implode("\r\n\tAND ", $sqlconds) : "") . "
		ORDER BY $order
		LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
	");

	if ($db->num_rows($logs))
	{
		$vbulletin->GPC['modaction'] = htmlspecialchars_uni($vbulletin->GPC['modaction']);

		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='modlog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&modaction=" . $vbulletin->GPC['modaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='modlog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&modaction=" . $vbulletin->GPC['modaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$prv'\">";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='modlog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&modaction=" . $vbulletin->GPC['modaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='modlog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&modaction=" . $vbulletin->GPC['modaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$totalpages'\">";
		}

		print_form_header('modlog', 'remove');
		print_description_row(construct_link_code($vbphrase['restart'], "modlog.php?" . $vbulletin->session->vars['sessionurl'] . ""), 0, 6, 'thead', vB_Template_Runtime::fetchStyleVar('right'));
		print_table_header(construct_phrase($vbphrase['moderator_log_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 6);

		$headings = array();
		$headings[] = $vbphrase['id'];
		$headings[] = "<a href=\"modlog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&modaction=" . $vbulletin->GPC['modaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=user&page=" . $vbulletin->GPC['pagenumber'] . "\">" . str_replace(' ', '&nbsp;', $vbphrase['username']) . "</a>";
		$headings[] = "<a href=\"modlog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&modaction=" . $vbulletin->GPC['modaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=date&page=" . $vbulletin->GPC['pagenumber'] . "\">" . $vbphrase['date'] . "</a>";
		//$headings[] = "<a href=\"modlog.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&modaction=" . $vbulletin->GPC['modaction'] . "&u=" . $vbulletin->GPC['userid'] . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=modaction&page=" . $vbulletin->GPC['pagenumber'] . "\">" . $vbphrase['action'] . "</a>";
		$headings[] = $vbphrase['action'];
		$headings[] = $vbphrase['info'];
		$headings[] = str_replace(' ', '&nbsp;', $vbphrase['ip_address']);
		print_cells_row($headings, 1);

		while ($log = $db->fetch_array($logs))
		{
			$cell = array();
			$cell[] = $log['moderatorlogid'];
			$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&u=$log[userid]\"><b>$log[username]</b></a>";
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $log['dateline']) . '</span>';

			if ($log['type'])
			{
				$phrase = fetch_modlogactions($log['type']);

				if ($unserialized = unserialize($log['action']))
				{
					array_unshift($unserialized, $vbphrase["$phrase"]);
					$log['action'] = call_user_func_array('construct_phrase', $unserialized);
				}
				else
				{
					$log['action'] = construct_phrase($vbphrase["$phrase"], $log['action']);
				}
			}

			if ($log['thread_title'] == '' AND $log['threadtitle'] != '')
			{
				$log['thread_title'] =& $log['threadtitle'];
			}

			$cell[] = $log['action'];

			($hook = vBulletinHook::fetch_hook('admin_modlogviewer_query_loop')) ? eval($hook) : false;

			$celldata = '';
			reset($princids);
			foreach ($princids AS $sqlfield => $output)
			{
				if ($sqlfield == 'post_title' AND $log['post_title'] == '' AND !empty($log['postid']))
				{
					$log['post_title'] = $vbphrase['untitled'];
				}

				if ($log["$sqlfield"])
				{
					if ($celldata)
					{
						$celldata .= "<br />\n";
					}
					$celldata .= "<b>$output:</b> ";
					switch($sqlfield)
					{
						case 'post_title':
							$celldata .= construct_link_code($log["$sqlfield"], 
								fetch_seo_url('thread|bburl', $log, array('p' => $log['postid']), 'threadid', 'thread_title') . "#post$log[postid]",
								true);
							break;
						case 'thread_title':
							$celldata .= construct_link_code($log["$sqlfield"], 
								fetch_seo_url('thread|bburl', $log, null, 'threadid', 'thread_title'), true);
							break;
						case 'forum_title':
							$celldata .= construct_link_code($log["$sqlfield"], 
								fetch_seo_url('forum|bburl', $log, null, 'forumid', 'forum_title'), true);
							break;
						case 'attachment_title':
							$celldata .= construct_link_code(htmlspecialchars_uni($log["$sqlfield"]), "../attachment.php?" . $vbulletin->session->vars['sessionurl'] . "attachmentid=$log[attachmentid]&amp;nocache=" . TIMENOW, true);
							break;
						default:
							$handled = false;
							($hook = vBulletinHook::fetch_hook('admin_modlogviewer_query_linkfield')) ? eval($hook) : false;
							if (!$handled)
							{
								$celldata .= $log["$sqlfield"];
							}
					}
				}
			}

			$cell[] = $celldata;

			$cell[] = '<span class="smallfont">' . iif($log['ipaddress'], "<a href=\"usertools.php?" . $vbulletin->session->vars['sessionurl'] . "do=gethost&ip=$log[ipaddress]\">$log[ipaddress]</a>", '&nbsp;') . '</span>';

			print_cells_row($cell, 0, 0, -4);
		}

		print_table_footer(6, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	}
	else
	{
		print_stop_message('no_results_matched_your_query');
	}
}

// ###################### Start prune log #######################
if ($_REQUEST['do'] == 'prunelog' AND can_access_logs($vbulletin->config['SpecialUsers']['canpruneadminlog'], 0, '<p>' . $vbphrase['control_panel_log_pruning_permission_restricted'] . '</p>'))
{
	$vbulletin->input->clean_array_gpc('r', array(
		'daysprune' => TYPE_UINT,
		'userid'    => TYPE_UINT,
		'modaction' => TYPE_STR,
		'product'   => TYPE_STR,
	));

	$datecut = TIMENOW - (86400 * $vbulletin->GPC['daysprune']);

	$sqlconds = array("dateline < $datecut");
	if ($vbulletin->GPC['userid'])
	{
		$sqlconds[] = "userid = " . $vbulletin->GPC['userid'];

	}
	if ($vbulletin->GPC['modaction'])
	{
		$sqlconds[] = "action LIKE '%" . $db->escape_string_like($vbulletin->GPC['modaction']) . "%'";
	}
	if ($vbulletin->GPC['product'])
	{
		if ($vbulletin->GPC['product'] == 'vbulletin')
		{
			$sqlconds[] = "product IN ('', 'vbulletin')";
		}
		else
		{
			$sqlconds[] = "product = '" . $db->escape_string($vbulletin->GPC['product']) . "'";
		}
	}

	$logs = $db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "moderatorlog
		WHERE " . (!empty($sqlconds) ? implode("\r\n\tAND ", $sqlconds) : "") . "
	");
	if ($logs['total'])
	{
		print_form_header('modlog', 'doprunelog');
		construct_hidden_code('datecut', $datecut);
		construct_hidden_code('modaction', $vbulletin->GPC['modaction']);
		construct_hidden_code('userid', $vbulletin->GPC['userid']);
		construct_hidden_code('product', $vbulletin->GPC['product']);
		print_table_header($vbphrase['prune_moderator_log']);
		print_description_row(construct_phrase($vbphrase['are_you_sure_you_want_to_prune_x_log_entries_from_moderator_log'], vb_number_format($logs['total'])));
		print_submit_row($vbphrase['yes'], 0, 0, $vbphrase['no']);
	}
	else
	{
		print_stop_message('no_logs_matched_your_query');
	}

}

// ###################### Start do prune log #######################
if ($_POST['do'] == 'doprunelog' AND can_access_logs($vbulletin->config['SpecialUsers']['canpruneadminlog'], 0, '<p>' . $vbphrase['control_panel_log_pruning_permission_restricted'] . '</p>'))
{
	$vbulletin->input->clean_array_gpc('p', array(
		'datecut'   => TYPE_UINT,
		'modaction' => TYPE_STR,
		'userid'    => TYPE_UINT,
		'product'   => TYPE_STR,
	));

	$sqlconds = array("dateline < " . $vbulletin->GPC['datecut']);
	if (!empty($vbulletin->GPC['modaction']))
	{
		$sqlconds[] = "action LIKE '%" . $db->escape_string_like($vbulletin->GPC['modaction']) . "%'";
	}
	if (!empty($vbulletin->GPC['userid']))
	{
		$sqlconds[] = "userid = " . $vbulletin->GPC['userid'];
	}
	if ($vbulletin->GPC['product'])
	{
		if ($vbulletin->GPC['product'] == 'vbulletin')
		{
			$sqlconds[] = "product IN ('', 'vbulletin')";
		}
		else
		{
			$sqlconds[] = "product = '" . $db->escape_string($vbulletin->GPC['product']) . "'";
		}
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "moderatorlog
		WHERE " . (!empty($sqlconds) ? implode("\r\n\tAND ", $sqlconds) : "") . "
	");

	define('CP_REDIRECT', 'modlog.php?do=choose');
	print_stop_message('pruned_moderator_log_successfully');
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'choose')
{
	$users = $db->query_read("
		SELECT DISTINCT moderatorlog.userid, user.username
		FROM " . TABLE_PREFIX . "moderatorlog AS moderatorlog
		INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
		ORDER BY username
	");
	$userlist = array('no_value' => $vbphrase['all_log_entries']);
	while ($user = $db->fetch_array($users))
	{
		$userlist["$user[userid]"] = $user['username'];
	}

	print_form_header('modlog', 'view');
	print_table_header($vbphrase['moderator_log_viewer']);
	print_input_row($vbphrase['log_entries_to_show_per_page'], 'perpage', 15);
	print_select_row($vbphrase['show_only_entries_generated_by'], 'userid', $userlist);
	print_time_row($vbphrase['start_date'], 'startdate', 0, 0);
	print_time_row($vbphrase['end_date'], 'enddate', 0, 0);
	if (count($products = fetch_product_list()) > 1)
	{
		print_select_row($vbphrase['product'], 'product', array('' => $vbphrase['all_products']) + $products);
	}
	print_select_row($vbphrase['order_by'], 'orderby', array('date' => $vbphrase['date'], 'user' => $vbphrase['username']), 'date');
	print_submit_row($vbphrase['view'], 0);

	if (can_access_logs($vbulletin->config['SpecialUsers']['canpruneadminlog'], 0, ''))
	{
		print_form_header('modlog', 'prunelog');
		print_table_header($vbphrase['prune_moderator_log']);
		print_select_row($vbphrase['remove_entries_logged_by_user'], 'userid', $userlist);
		if (count($products) > 1)
		{
			print_select_row($vbphrase['product'], 'product', array('' => $vbphrase['all_products']) + $products);
		}
		print_input_row($vbphrase['remove_entries_older_than_days'], 'daysprune', 30);
		print_submit_row($vbphrase['prune_log_entries'], 0);
	}

}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 42666 $
|| ####################################################################
\*======================================================================*/
?>
