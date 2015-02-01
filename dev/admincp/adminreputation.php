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
$phrasegroups = array('reputation', 'user', 'reputationlevel');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_reputation.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'reputationlevelid' => TYPE_INT,
	'minimumreputation' => TYPE_INT,
));

// ############################# LOG ACTION ###############################
log_admin_action(iif($vbulletin->GPC['reputationlevelid'] != 0, " reputationlevel id = " . $vbulletin->GPC['reputationlevelid'], iif($vbulletin->GPC['minimumreputation'] != 0, "minimum reputation = " . $vbulletin->GPC['minimumreputation'], '')));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['user_reputation_manager']);

// *************************************************************************************************

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// *************************************************************************************************

if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'reputationlevelid' => TYPE_INT
	));

	print_form_header('adminreputation', 'update');
	if ($vbulletin->GPC['reputationlevelid'])
	{
		$reputationlevel = $db->query_first("
				SELECT *
				FROM " . TABLE_PREFIX . "reputationlevel
				WHERE reputationlevelid = " . $vbulletin->GPC['reputationlevelid']
		);

		$level = 'reputation' . $reputationlevel['reputationlevelid'];

		if ($phrase = $db->query_first("
			SELECT varname, text
			FROM " . TABLE_PREFIX . "phrase
			WHERE languageid = 0 AND
					fieldname = 'reputationlevel' AND
					varname IN ('$level')
		"))
		{
			$reputationlevel['level'] = $phrase['text'];
			$reputationlevel['levelvarname'] = 'reputation' . $reputationlevel['reputationlevelid'];
		}

		construct_hidden_code('reputationlevelid', $vbulletin->GPC['reputationlevelid']);
		construct_hidden_code('oldminimum', $reputation['minimumreputation']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['reputation_level'], '<i>' . htmlspecialchars_uni($reputationlevel['level']) . '</i>', $reputationlevel['minimumreputation']));
	}
	else
	{
		print_table_header($vbphrase['add_new_reputation_level']);
	}

	if ($reputationlevel['level'])
	{
		print_input_row($vbphrase['description'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=reputationlevel&varname=$reputationlevel[levelvarname]&t=1", 1)  . '</dfn>', 'level', $reputationlevel['level']);
	}
	else
	{
		print_input_row($vbphrase['description'], 'level');
	}
	print_input_row($vbphrase['minimum_reputation_level'], 'reputationlevel[minimumreputation]', $reputationlevel['minimumreputation']);
	print_submit_row(iif($vbulletin->GPC['reputationlevelid'], $vbphrase['update'], $vbphrase['save']));
}

// *************************************************************************************************

if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'reputationlevelid' => TYPE_INT,
		'oldminimum'        => TYPE_INT,
		'reputationlevel'   => TYPE_ARRAY,
		'level'             => TYPE_STR,
	));

	if ($vbulletin->GPC['reputationlevelid'])
	{ // edit
		$sql = " AND reputationlevelid <> " . $vbulletin->GPC['reputationlevelid'];
	}

	$vbulletin->GPC['reputationlevel']['minimumreputation'] = intval($vbulletin->GPC['reputationlevel']['minimumreputation']);
	if (!$db->query_first("SELECT reputationlevelid FROM " . TABLE_PREFIX . "reputationlevel WHERE minimumreputation = " . $vbulletin->GPC['reputationlevel']['minimumreputation'] . $sql))
	{
		define('CP_REDIRECT', 'adminreputation.php?do=modify');
		if ($vbulletin->GPC['reputationlevelid'])
		{ // edit
			$db->query_write(fetch_query_sql($vbulletin->GPC['reputationlevel'], 'reputationlevel', "WHERE reputationlevelid=" . $vbulletin->GPC['reputationlevelid']));

			if ($vbulletin->GPC['oldminimum'] != $vbulletin->GPC['reputationlevel']['minimumreputation'])
			{ // need to update user table
				build_reputationids();
			}
		}
		else
		{
			$db->query_write(fetch_query_sql($vbulletin->GPC['reputationlevel'], 'reputationlevel'));
			$vbulletin->GPC['reputationlevelid'] = $db->insert_id();
			build_reputationids();
		}

		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "phrase
				(languageid, fieldname, varname, text, product, username, dateline, version)
			VALUES
				(0,
				'reputationlevel',
				'reputation" . $vbulletin->GPC['reputationlevelid'] . "',
				'" . $db->escape_string($vbulletin->GPC['level']) .  "',
				'vbulletin',
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($vbulletin->options['templateversion']) . "')
		");

		require_once(DIR . '/includes/adminfunctions_language.php');
		build_language();

		print_stop_message('saved_reputation_level_x_successfully', htmlspecialchars_uni($vbulletin->GPC['level']));
	}
	else
	{
		print_stop_message('no_permission_duplicate_reputation');
	}
}

// *************************************************************************************************

if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'minimumreputation'	=> TYPE_INT
	));

	print_form_header('adminreputation', 'kill');
	construct_hidden_code('minimumreputation', $vbulletin->GPC['minimumreputation']);
	print_table_header($vbphrase['confirm_deletion']);
	print_description_row(construct_phrase($vbphrase['are_you_sure_you_want_to_delete_the_reputation_level_x'], '<i>' . $vbulletin->GPC['minimumreputation'] . '</i>'));
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// *************************************************************************************************

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'minimumreputation'	=> TYPE_INT
	));

	$reputationlevel = $db->query_first("
		SELECT reputationlevelid
		FROM " . TABLE_PREFIX . "reputationlevel
		WHERE minimumreputation = " . $vbulletin->GPC['minimumreputation']
	);

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "phrase
		WHERE fieldname = 'reputationlevel' AND
				varname IN ('reputation$reputationlevel[reputationlevelid]')
	");

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "reputationlevel
		WHERE minimumreputation = " . $vbulletin->GPC['minimumreputation']
	);

	build_reputationids();

	define('CP_REDIRECT', 'adminreputation.php?do=modify');
	print_stop_message('deleted_reputation_level_successfully');
}

// *************************************************************************************************

if ($_POST['do'] == 'updateminimums')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'reputation' 	=> TYPE_ARRAY
	));

	if (is_array($vbulletin->GPC['reputation']))
	{
		foreach($vbulletin->GPC['reputation'] AS $index => $value)
		{
			if ($found["$value"])
			{
				print_stop_message('no_permission_duplicate_reputation');
			}
			else
			{
				$found["$value"] = 1;
			}
		}

		foreach ($vbulletin->GPC['reputation'] AS $index => $value)
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "reputationlevel
				SET minimumreputation = " . intval($value) . "
				WHERE reputationlevelid = " . intval($index) . "
			");
		}

		build_reputationids();
	}

	define('CP_REDIRECT', 'adminreputation.php?do=modify');
	print_stop_message('saved_reputation_level_x_successfully', '');
}

// *************************************************************************************************

if ($_REQUEST['do'] == 'list' OR $_REQUEST['do'] == 'dolist')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'leftby'     => TYPE_NOHTML,
		'leftfor'    => TYPE_NOHTML,
		'userid'     => TYPE_UINT,
		'whoadded'   => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'perpage'    => TYPE_UINT,
		'orderby'    => TYPE_STR,
		'start'      => TYPE_ARRAY_UINT,
		'end'        => TYPE_ARRAY_UINT,
		'startstamp' => TYPE_UINT,
		'endstamp'   => TYPE_UINT
	));

	$vbulletin->GPC['start'] 	= iif($vbulletin->GPC['startstamp'], $vbulletin->GPC['startstamp'], $vbulletin->GPC['start']);
	$vbulletin->GPC['end'] 		= iif($vbulletin->GPC['endstamp'], $vbulletin->GPC['endstamp'], $vbulletin->GPC['end']);

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


	print_form_header('adminreputation', 'dolist');
	print_table_header($vbphrase['view_reputation_comments']);
	print_input_row($vbphrase['leftfor'], 'leftfor', $vbulletin->GPC['leftfor'], 0);
	print_input_row($vbphrase['leftby'], 'leftby', $vbulletin->GPC['leftby'], 0);
	print_time_row($vbphrase['start_date'], 'start', $vbulletin->GPC['start'], false);
	print_time_row($vbphrase['end_date'], 'end', $vbulletin->GPC['end'], false);
	print_submit_row($vbphrase['go']);
}

// *************************************************************************************************

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
			print_stop_message('could_not_find_user_x', $vbulletin->GPC['leftfor']);
		}
		$vbulletin->GPC['userid'] = $leftfor_user['userid'];
	}

	if ($vbulletin->GPC['whoadded'])
	{
		$condition = "WHERE rep.whoadded = " . $vbulletin->GPC['whoadded'];
	}
	if ($vbulletin->GPC['userid'])
	{
		$condition .= iif (!$condition, "WHERE", " AND") . " rep.userid = " . $vbulletin->GPC['userid'];
	}
	if ($vbulletin->GPC['start'])
	{
		$condition .= iif (!$condition, "WHERE", " AND") . " rep.dateline >= " . $vbulletin->GPC['start'];
	}
	if ($vbulletin->GPC['end'])
	{
		$condition .= iif (!$condition, "WHERE", " AND") . " rep.dateline <= " . $vbulletin->GPC['end'];
	}

	$count = $db->query_first("
		SELECT count(*) AS count
		FROM " . TABLE_PREFIX . "reputation AS rep
		$condition
	");

	$totalrep = $count['count'];

	if (!$totalrep)
	{
		print_stop_message('no_matches_found');
	}

	switch($vbulletin->GPC['orderby'])
	{
		case 'leftbyuser':
			$orderbysql = 'leftby_user.username';
			break;
		case 'leftforuser':
			$orderbysql = 'leftfor_user.username';
			break;
		default:
			$orderbysql = 'rep.dateline';
			$orderby = 'dateline';
	}

	sanitize_pageresults($totalrep, $vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage']);
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	$totalpages = ceil($totalrep / $vbulletin->GPC['perpage']);

	$comments = $db->query_read("
		SELECT post.postid, rep.userid AS userid, whoadded, rep.reason, rep.dateline, rep.reputationid, rep.reputation,
			leftfor_user.username AS leftfor_username,
			leftby_user.username AS leftby_username,
			post.title, post.threadid
		FROM " . TABLE_PREFIX . "reputation AS rep
		LEFT JOIN " . TABLE_PREFIX . "post AS post ON (rep.postid = post.postid)
		LEFT JOIN " . TABLE_PREFIX . "user AS leftby_user ON (rep.whoadded = leftby_user.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS leftfor_user ON (rep.userid = leftfor_user.userid)
		$condition
		ORDER BY $orderbysql
		LIMIT $startat, " . $vbulletin->GPC['perpage']
	);

	if ($vbulletin->GPC['pagenumber'] != 1)
	{
		$prv = $vbulletin->GPC['pagenumber'] - 1;
		$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='adminreputation.php?" . $vbulletin->session->vars['sessionurl'] .	"do=dolist" .
			"&u=" 			. $vbulletin->GPC['userid'] .
			"&whoadded="	. $vbulletin->GPC['whoadded'] .
			"&pp="			. $vbulletin->GPC['perpage'] .
			"&page=1" .
			"&startstamp=" 	. $vbulletin->GPC['start'] .
			"&endstamp=" 	. $vbulletin->GPC['end'] .
			"&orderby=" . $vbulletin->GPC['orderby'] . "'\">";

		$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='adminreputation.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" .
			"&u="			. $vbulletin->GPC['userid'] .
			"&whoadded="	. $vbulletin->GPC['whoadded'] .
			"&pp="		 	. $vbulletin->GPC['perpage'] .
			"&page="		. $prv .
			"&startstamp="	. $vbulletin->GPC['start'] .
			"&endstamp="	. $vbulletin->GPC['end'] .
			"&orderby=" . $vbulletin->GPC['orderby'] . "'\">";
	}

	if ($vbulletin->GPC['pagenumber'] != $totalpages)
	{
		$nxt = $vbulletin->GPC['pagenumber'] + 1;
		$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='adminreputation.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" .
			"&u="			. $vbulletin->GPC['userid'] .
			"&whoadded="	. $vbulletin->GPC['whoadded'] .
			"&pp="			. $vbulletin->GPC['perpage'] .
			"&page="		. $nxt .
			"&startstamp="	. $vbulletin->GPC['start'] .
			"&endstamp="	. $vbulletin->GPC['end'] .
			"&orderby=" . $vbulletin->GPC['orderby'] . "'\">";

		$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='adminreputation.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" .
			"&u="			. $vbulletin->GPC['userid'] .
			"&whoadded="	. $vbulletin->GPC['whoadded'] .
			"&pp="			. $vbulletin->GPC['perpage'] .
			"&page="		. $totalpages .
			"&startstamp="	. $vbulletin->GPC['start'] .
			"&endstamp=" 	. $vbulletin->GPC['end'] .
			"&orderby=" . $vbulletin->GPC['orderby'] . "'\">";
	}

	print_form_header('adminreputation', 'dolist');
	print_table_header(construct_phrase($vbphrase['x_reputation_comments_page_y_z'], vb_number_format($totalrep), $vbulletin->GPC['pagenumber'], vb_number_format($totalpages)), 7);

	$headings = array();
	$headings[] = "<a href='adminreputation.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" .
		"&amp;u=" 			. $vbulletin->GPC['userid'] .
		"&amp;whoadded="	. $vbulletin->GPC['whoadded'] .
		"&amp;pp="			. $vbulletin->GPC['perpage'] .
		"&amp;orderby=leftbyuser" .
		"&amp;page=" 		. $vbulletin->GPC['pagenumber'] .
		"&amp;startstamp="	. $vbulletin->GPC['start'] .
		"&amp;endstamp="	. $vbulletin->GPC['end'] . "' title='" . $vbphrase['order_by_username'] . "'>" . $vbphrase['leftby'] . "</a>";

	$headings[] = "<a href='adminreputation.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" .
		"&amp;u="			. $vbulletin->GPC['userid'] .
		"&amp;whoadded="	. $vbulletin->GPC['whoadded'] .
		"&amp;pp="			. $vbulletin->GPC['perpage'] .
		"&amp;orderby=leftforuser" .
		"&amp;page=" 		. $vbulletin->GPC['pagenumber'] .
		"&amp;startstamp="	. $vbulletin->GPC['start'] .
		"&amp;endstamp="	. $vbulletin->GPC['end'] . "' title='" . $vbphrase['order_by_username'] . "'>" . $vbphrase['leftfor'] . "</a>";

	$headings[] = "<a href='adminreputation.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" .
		"&amp;u="			. $vbulletin->GPC['userid'] .
		"&amp;whoadded="	. $vbulletin->GPC['whoadded'] .
		"&amp;pp="			. $vbulletin->GPC['perpage'] .
		"&amp;orderby=date" .
		"&amp;page="		. $vbulletin->GPC['pagenumber'] .
		"&amp;startstamp="	. $vbulletin->GPC['start'] .
		"&amp;endstamp="	. $vbulletin->GPC['end'] . "' title='" . $vbphrase['order_by_date'] . "'>" . $vbphrase['date'] . "</a>";

	$headings[] = $vbphrase['reputation'];
	$headings[] = $vbphrase['reason'];
	$headings[] = $vbphrase['post'];
	$headings[] = $vbphrase['controls'];
	print_cells_row($headings, 1);

	while ($comment = $db->fetch_array($comments))
	{

		$postlink = '';
		if (!empty($comment['postid']))
		{
			//deliberately don't use the title.  We don't have it in our result set (or
			//in any of the tables in our result set) and we'll catch it on redirect.  
			//Plus the admincp isn't a big SEO issue -- we just want to get the links
			//on the classes so that they work and centralize logic for future changes.
			$postlink = fetch_seo_url('thread|bburl', $comment, array('p' => $comment['postid'])) . "#post$comment[postid]";
		}

		$cell = array();
		$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$comment[whoadded]\"><b>$comment[leftby_username]</b></a>";
		$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$comment[userid]\"><b>$comment[leftfor_username]</b></a>";
		$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $comment['dateline']) . '</span>';
		$cell[] = $comment['reputation'];
		$cell[] = !empty($comment['reason']) ? '<span class="smallfont">' . htmlspecialchars_uni($comment['reason']) . '</span>' : '';
		$cell[] = $postlink ? construct_link_code(htmlspecialchars_uni($vbphrase['post']), $postlink, true, '', true) : '&nbsp;';
		$cell[] = construct_link_code($vbphrase['edit'], "adminreputation.php?" . $vbulletin->session->vars['sessionurl'] . "do=editreputation&reputationid=$comment[reputationid]", false, '', true) .
			' ' . construct_link_code($vbphrase['delete'], "adminreputation.php?" . $vbulletin->session->vars['sessionurl'] . "do=deletereputation&reputationid=$comment[reputationid]", false, '', true);
		print_cells_row($cell);
	}

	print_table_footer(7, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
}

// *************************************************************************************************

if ($_REQUEST['do'] == 'editreputation')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'reputationid' => TYPE_INT
	));
	if ($repinfo = $db->query_first("
		SELECT rep.*, whoadded.username as whoadded_username, user.username, thread.title, thread.threadid
		FROM " . TABLE_PREFIX . "reputation AS rep
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (rep.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS whoadded ON (rep.whoadded = whoadded.userid)
		LEFT JOIN " . TABLE_PREFIX . "post AS post ON (rep.postid = post.postid)
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = post.threadid)
		WHERE reputationid = " . $vbulletin->GPC['reputationid']
	))
	{
		print_form_header('adminreputation', 'doeditreputation');
		print_table_header($vbphrase['edit_reputation']);
		print_label_row($vbphrase['thread'], 
			$repinfo['title'] ? "<a href=\"" . fetch_seo_url('thread|bburl', $repinfo, 
				array('p' => $repinfo['postid'])) . "#post$repinfo[postid]" . 
				"\">$repinfo[title]</a>" : '');
		print_label_row($vbphrase['leftby'], $repinfo['whoadded_username']);
		print_label_row($vbphrase['leftfor'], $repinfo['username']);
		print_input_row($vbphrase['comment'], 'reputation[reason]', $repinfo['reason']);
		print_input_row($vbphrase['reputation'], 'reputation[reputation]', $repinfo['reputation'], 0, 5);
		construct_hidden_code('reputationid', $vbulletin->GPC['reputationid']);
		construct_hidden_code('oldreputation', $repinfo[reputation]);
		construct_hidden_code('userid', $repinfo['userid']);
		print_submit_row();
	}
	else
	{
		print_stop_message('no_matches_found');
	}
}

// *************************************************************************************************

if ($_POST['do'] == 'doeditreputation')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'reputation'	=> TYPE_ARRAY,
		'reputationid'	=> TYPE_INT,
		'oldreputation'	=> TYPE_INT,
		'userid'		=> TYPE_INT
	));

	$db->query_write(fetch_query_sql($vbulletin->GPC['reputation'], 'reputation', "WHERE reputationid=" . $vbulletin->GPC['reputationid']));

	if ($vbulletin->GPC['oldreputation'] != $vbulletin->GPC['reputation']['reputation'])
	{
		$diff = $vbulletin->GPC['oldreputation'] - $vbulletin->GPC['reputation']['reputation'];

		$user = fetch_userinfo($vbulletin->GPC['userid']);
		if ($user)
		{
			$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
			$userdm->set_existing($user);
			$userdm->set('reputation', "reputation - $diff", false);
			$userdm->save();
			unset($userdm);
		}
	}

	define('CP_REDIRECT', "adminreputation.php?do=list&amp;u=" . $vbulletin->GPC['userid']);

	print_stop_message('saved_reputation_successfully');
}

// *************************************************************************************************

if ($_POST['do'] == 'killreputation')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'reputationid'	=> TYPE_INT
	));

	$repinfo = verify_id('reputation', $vbulletin->GPC['reputationid'], 0, 1);

	$user = fetch_userinfo($repinfo['userid']);
	if ($user)
	{
		$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_CP);
		$userdm->set_existing($user);
		$userdm->set('reputation', $user['reputation'] - $repinfo['reputation']);
		$userdm->save();
		unset($userdm);
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "reputation
		WHERE reputationid = " . $vbulletin->GPC['reputationid']
	);

	define('CP_REDIRECT', "adminreputation.php?do=list&amp;u=$repinfo[userid]");

	print_stop_message('deleted_reputation_successfully');
}

// *************************************************************************************************

if ($_REQUEST['do'] == 'deletereputation')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'reputationid'	=> TYPE_INT
	));

	print_delete_confirmation('reputation', $vbulletin->GPC['reputationid'], 'adminreputation', 'killreputation');
}

if ($_REQUEST['do'] == 'modify')
{
	$reputationlevels = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "reputationlevel
		ORDER BY minimumreputation
	");

	print_form_header('adminreputation', 'updateminimums');
	print_table_header($vbphrase['user_reputation_manager'], 3);
	print_cells_row(array($vbphrase['reputation_level'], $vbphrase['minimum_reputation_level'], $vbphrase['controls']), 1);

	while ($reputationlevel = $db->fetch_array($reputationlevels))
	{
		$reputationlevel['level'] = htmlspecialchars_uni($vbphrase['reputation' . $reputationlevel['reputationlevelid']]);
		$cell = array();
		$cell[] = "$vbphrase[user] <b>$reputationlevel[level]</b>";
		$cell[] = "<input type=\"text\" class=\"bginput\" tabindex=\"1\" name=\"reputation[$reputationlevel[reputationlevelid]]\" value=\"$reputationlevel[minimumreputation]\" size=\"5\" />";
		$cell[] = construct_link_code($vbphrase['edit'], "adminreputation.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&reputationlevelid=$reputationlevel[reputationlevelid]") . construct_link_code($vbphrase['delete'], "adminreputation.php?" . $vbulletin->session->vars['sessionurl'] . "do=remove&minimumreputation=$reputationlevel[minimumreputation]");
		print_cells_row($cell);
	}

	print_submit_row($vbphrase['update'], $vbphrase['reset'], 3);
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
?>
