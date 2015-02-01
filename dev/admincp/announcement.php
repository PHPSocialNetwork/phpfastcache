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
define('CVS_REVISION', '$RCSfile$ - $Revision: 34547 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('posting');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_announcement.php');

// ############################# LOG ACTION ###############################

if (!($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['ismoderator']))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'announcementid' => TYPE_INT
));
log_admin_action(iif($vbulletin->GPC['announcementid'] != 0, "announcement id = " . $vbulletin->GPC['announcementid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['announcement_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start add / edit #######################
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'forumid'        => TYPE_INT,
		'newforumid'     => TYPE_ARRAY,
		'announcementid' => TYPE_INT
	));

	print_form_header('announcement', 'update');

	if ($_REQUEST['do'] == 'add')
	{
		// set default values
		if (is_array($vbulletin->GPC['newforumid']))
		{
			foreach($vbulletin->GPC['newforumid'] AS $key => $val)
			{
				$vbulletin->GPC['forumid'] = intval($key);
			}
		}
		$announcement = array(
			'startdate'           => TIMENOW,
			'enddate'             => (TIMENOW + 86400 * 31),
			'forumid'             => $vbulletin->GPC['forumid'],
			'announcementoptions' => 29
		);
		print_table_header($vbphrase['post_new_announcement']);
	}
	else
	{
		// query announcement
		$announcement = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "announcement WHERE announcementid = " . $vbulletin->GPC['announcementid']);

		if (!($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
		{
			if ($announcement['forumid'] == -1 AND !($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['ismoderator']))
			{
				print_table_header($vbphrase['no_permission_global_announcement']);
				print_table_break();
			}
			else if ($announcement['forumid'] != -1 AND !can_moderate($announcement['forumid'], 'canannounce'))
			{
				print_table_header($vbphrase['no_permission_announcement']);
				print_table_break();
			}
		}

		construct_hidden_code('announcementid', $vbulletin->GPC['announcementid']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['announcement'], htmlspecialchars_uni($announcement['title']), $announcement['announcementid']));
	}

	print_forum_chooser($vbphrase['forum_and_children'], 'forumid', $announcement['forumid'], $vbphrase['all_forums']);
	print_input_row($vbphrase['title'], 'title', $announcement['title']);

	print_time_row($vbphrase['start_date'], 'startdate', $announcement['startdate'], 0);
	print_time_row($vbphrase['end_date'], 'enddate', $announcement['enddate'], 0);

	print_textarea_row($vbphrase['text'], 'pagetext', $announcement['pagetext'], 20, '75" style="width:100%');

	if ($vbulletin->GPC['announcementid'])
	{
		print_yes_no_row($vbphrase['reset_views_counter'], 'reset_views', 0);
	}

	print_yes_no_row($vbphrase['allow_bbcode'], 'announcementoptions[allowbbcode]', ($announcement['announcementoptions'] & $vbulletin->bf_misc_announcementoptions['allowbbcode'] ? 1 : 0));
	print_yes_no_row($vbphrase['allow_smilies'], 'announcementoptions[allowsmilies]', ($announcement['announcementoptions'] & $vbulletin->bf_misc_announcementoptions['allowsmilies'] ? 1 : 0));
	print_yes_no_row($vbphrase['allow_html'], 'announcementoptions[allowhtml]', ($announcement['announcementoptions'] & $vbulletin->bf_misc_announcementoptions['allowhtml'] ? 1 : 0));
	print_yes_no_row($vbphrase['automatically_parse_links_in_text'], 'announcementoptions[parseurl]', ($announcement['announcementoptions'] & $vbulletin->bf_misc_announcementoptions['parseurl'] ? 1 : 0));
	print_yes_no_row($vbphrase['show_your_signature'], 'announcementoptions[signature]', ($announcement['announcementoptions'] & $vbulletin->bf_misc_announcementoptions['signature'] ? 1 : 0));

	print_submit_row($vbphrase['save']);
}

// ###################### Start insert #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'announcementid'      => TYPE_UINT,
		'title'               => TYPE_STR,
		'startdate'           => TYPE_UNIXTIME,
		'enddate'             => TYPE_UNIXTIME,
		'pagetext'            => TYPE_STR,
		'forumid'             => TYPE_INT,
		'announcementoptions' => TYPE_ARRAY_BOOL,
		'reset_views'         => TYPE_BOOL
	));

	if (!($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
	{
		if ($vbulletin->GPC['forumid'] == -1 AND !($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['ismoderator']))
		{
			print_stop_message('no_permission_global_announcement');
		}
		else if ($vbulletin->GPC['forumid'] != -1 AND !can_moderate($vbulletin->GPC['forumid'], 'canannounce'))
		{
			print_stop_message('no_permission_announcement');
		}
	}

	// query original data
	if ($vbulletin->GPC['announcementid'] AND (!$original_data = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "announcement WHERE announcementid = " . $vbulletin->GPC['announcementid'])))
	{
		if (!preg_match('#^(mailto:|http)#siU', $vbulletin->options['contactuslink']))
		{
			$vbulletin->options['contactuslink'] = '../' . $vbulletin->options['contactuslink'];
		}
		print_stop_message('invalidid', $vbphrase['announcement'], $vbulletin->options['contactuslink']);
	}

	if (!trim($vbulletin->GPC['title']))
	{
		$vbulletin->GPC['title'] = $vbphrase['announcement'];
	}

	$anncdata =& datamanager_init('Announcement', $vbulletin, ERRTYPE_CP);

	if ($vbulletin->GPC['announcementid'])
	{
		$anncdata->set_existing($original_data);

		if ($vbulletin->GPC['reset_views'])
		{
			define('RESET_VIEWS', true);
			$anncdata->set('views', 0);
		}
	}
	else
	{
		$anncdata->set('userid', $vbulletin->userinfo['userid']);
	}

	$anncdata->set('title', $vbulletin->GPC['title']);
	$anncdata->set('pagetext', $vbulletin->GPC['pagetext']);
	$anncdata->set('forumid', $vbulletin->GPC['forumid']);
	$anncdata->set('startdate', $vbulletin->GPC['startdate']);
	$anncdata->set('enddate', $vbulletin->GPC['enddate'] + 86399);

	foreach ($vbulletin->GPC['announcementoptions'] AS $key => $val)
	{
		$anncdata->set_bitfield('announcementoptions', $key, $val);
	}

	$announcementid = $anncdata->save();

	if ($original_data)
	{
		if ($vbulletin->GPC['reset_views'])
		{
			$db->query_write("DELETE FROM " . TABLE_PREFIX . "announcementread WHERE announcementid = " . $vbulletin->GPC['announcementid']);
		}
		$announcementid = $announcementinfo['announcementid'];
	}

	define('CP_REDIRECT', 'announcement.php');
	print_stop_message('saved_announcement_x_successfully', htmlspecialchars_uni($vbulletin->GPC['title']));
}

// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'announcementid' 	=> TYPE_UINT
	));

	print_delete_confirmation('announcement', $vbulletin->GPC['announcementid'], 'announcement', 'kill', 'announcement');
}

// ###################### Start Kill #######################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'announcementid' 	=> TYPE_UINT
	));

	if ($announcement = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "announcement WHERE announcementid = " . $vbulletin->GPC['announcementid']))
	{
		$anncdata =& datamanager_init('Announcement', $vbulletin, ERRTYPE_CP);
		$anncdata->set_existing($announcement);
		$anncdata->delete();

		define('CP_REDIRECT', 'announcement.php?do=modify');
		print_stop_message('deleted_announcement_successfully');
	}
	else
	{
		print_stop_message('invalidid', $vbphrase['announcement'], $vbulletin->options['contactuslink']);
	}
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$ans = $db->query_read("
		SELECT announcementid,title,startdate,enddate,forumid,username
		FROM " . TABLE_PREFIX . "announcement AS announcement
		LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid)
		ORDER BY startdate
	");
	while ($an = $db->fetch_array($ans))
	{
		if (!$an['username'])
		{
			$an['username'] = $vbphrase['guest'];
		}
		if ($an['forumid'] == -1)
		{
			$globalannounce[] = $an;
		}
		else
		{
			$ancache[$an['forumid']][$an['announcementid']] = $an;
		}
	}

	//require_once(DIR . '/includes/functions_databuild.php');
	//cache_forums();
	print_form_header('announcement', 'add');
	print_table_header($vbphrase['announcement_manager'], 3);

	// display global announcments
	if (is_array($globalannounce))
	{
		$cell = array();
		$cell[] = '<b>' . $vbphrase['global_announcements'] . '</b>';
		$announcements = '';
		foreach($globalannounce AS $announcementid => $announcement)
		{
			$announcements .=
			"\t\t<li><b>" . htmlspecialchars_uni($announcement['title']) . "</b> ($announcement[username]) ".
			construct_link_code($vbphrase['edit'], "announcement.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&a=$announcement[announcementid]").
			construct_link_code($vbphrase['delete'], "announcement.php?" . $vbulletin->session->vars['sessionurl'] . "do=remove&a=$announcement[announcementid]").
			'<span class="smallfont">(' . ' ' .
				construct_phrase($vbphrase['x_to_y'], vbdate($vbulletin->options['dateformat'], $announcement['startdate']), vbdate($vbulletin->options['dateformat'], $announcement['enddate'])) .
			")</span></li>\n";
		}
		$cell[] = $announcements;
		$cell[] = '<input type="submit" class="button" value="' . $vbphrase['new'] . '" title="' . $vbphrase['post_new_announcement'] . '" />';
		print_cells_row($cell, 0, '', -1);
		print_table_break();
	}

	// display forum-specific announcements
	foreach($vbulletin->forumcache AS $key => $forum)
	{
		if ($forum['parentid'] == -1)
		{
			print_cells_row(array($vbphrase['forum'], $vbphrase['announcements'], ''), 1, 'tcat', 1);
		}
		$cell = array();
		$cell[] = "<b>" . construct_depth_mark($forum['depth'], '- - ', '- - ') . "<a href=\"../announcement.php?" . $vbulletin->session->vars['sessionurl'] . "f=$forum[forumid]\" target=\"_blank\">$forum[title]</a></b>";
		$announcements = '';
		if (is_array($ancache[$forum['forumid']]))
		{
			foreach($ancache[$forum['forumid']] AS $announcementid => $announcement)
			{
				$announcements .=
				"\t\t<li><b>" . htmlspecialchars_uni($announcement['title']) . "</b> ($announcement[username]) ".
				construct_link_code($vbphrase['edit'], "announcement.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&a=$announcement[announcementid]").
				construct_link_code($vbphrase['delete'], "announcement.php?" . $vbulletin->session->vars['sessionurl'] . "do=remove&a=$announcement[announcementid]").
				'<span class="smallfont">('.
					construct_phrase($vbphrase['x_to_y'], vbdate($vbulletin->options['dateformat'], $announcement['startdate']), vbdate($vbulletin->options['dateformat'], $announcement['enddate'])) .
				")</span></li>\n";
			}
		}
		$cell[] = $announcements;
		$cell[] = '<input type="submit" class="button" value="' . $vbphrase['new'] . '" name="newforumid[' . $forum['forumid'] . ']" title="' . $vbphrase['post_new_announcement'] . '" />';
		print_cells_row($cell, 0, '', -1);
	}

	print_table_footer();
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 34547 $
|| ####################################################################
\*======================================================================*/
?>
