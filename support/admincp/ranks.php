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
$phrasegroups = array('user', 'cpuser', 'cprank');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_ranks.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array('rankid' => TYPE_UINT));

// ############################# LOG ACTION ###############################
log_admin_action(!empty($vbulletin->GPC['rankid']) ? "rank id = " . $vbulletin->GPC['rankid'] : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['user_rank_manager']);


if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start insert #######################
if ($_POST['do'] == 'insert')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'ranklevel'   => TYPE_UINT,
		'minposts'    => TYPE_UINT,
		'rankimg'     => TYPE_STR,
		'usergroupid' => TYPE_INT,
		'doinsert'    => TYPE_STR,
		'rankhtml'    => TYPE_NOTRIM,
		'stack'       => TYPE_UINT,
		'display'     => TYPE_UINT,
	));

	if (!$vbulletin->GPC['ranklevel'] OR (!$vbulletin->GPC['rankimg'] AND !$vbulletin->GPC['rankhtml']))
	{
		if ($vbulletin->GPC['doinsert'])
		{
			echo '<p><b>' . $vbphrase['invalid_file_path_specified'] . '</b></p>';
			$vbulletin->GPC['rankimg'] = $vbulletin->GPC['doinsert'];
		}
		else
		{
			print_stop_message('please_complete_required_fields');
		}

	}

	if ($vbulletin->GPC['usergroupid'] == -1)
	{
		$vbulletin->GPC['usergroupid'] = 0;
	}

	if (!$vbulletin->GPC['rankhtml'])
	{
		$vbulletin->GPC['rankimg'] = preg_replace('/\/$/s', '', $vbulletin->GPC['rankimg']);
		if($dirhandle = @opendir(DIR . '/' . $vbulletin->GPC['rankimg']))
		{ // Valid directory!
			readdir($dirhandle);
			readdir($dirhandle);
			while ($filename = readdir($dirhandle))
			{
				if (is_file(DIR . "/{$vbulletin->GPC['rankimg']}/" . $filename) AND (($filelen = strlen($filename)) >= 5))
				{
					$fileext = strtolower(substr($filename, $filelen - 4, $filelen - 1));
					if ($fileext == '.gif' OR $fileext == '.bmp' OR $fileext == '.jpg' OR $fileext == 'jpeg' OR $fileext == 'png')
					{
						$FileArray[] = htmlspecialchars_uni($filename);
					}
				}
			}
			if (!is_array($FileArray))
			{
				print_stop_message('no_matches_found');
			}

			print_form_header('ranks', 'insert', 0, 1, 'name', '');
			print_table_header($vbphrase['images']);
			construct_hidden_code('usergroupid', $vbulletin->GPC['usergroupid']);
			construct_hidden_code('ranklevel', $vbulletin->GPC['ranklevel']);
			construct_hidden_code('minposts', $vbulletin->GPC['minposts']);
			construct_hidden_code('doinsert', $vbulletin->GPC['rankimg']);
			foreach ($FileArray AS $key => $val)
			{
				print_yes_row("<img src='../" . $vbulletin->GPC['rankimg'] . "/$val' border='0' alt='' align='center' />", 'rankimg', '', '', $vbulletin->GPC['rankimg'] . "/$val");
			}
			print_submit_row($vbphrase['save']);
			closedir($dirhandle);
			exit;
		}
		else
		{ // Not a valid dir so assume it is a filename
			if (!(@is_file(DIR . '/' . $vbulletin->GPC['rankimg'])))
			{
				print_stop_message('invalid_file_path_specified');
			}
		}
		$type = 0;
	}
	else
	{
		$vbulletin->GPC['rankimg'] = $vbulletin->GPC['rankhtml'];
		$type = 1;
	}

	build_ranks();

	/*insert query*/
	$db->query_write("
		INSERT INTO " . TABLE_PREFIX . "ranks
			(ranklevel, minposts, rankimg, usergroupid, type, stack, display)
		VALUES
			(
			" . $vbulletin->GPC['ranklevel'] . ",
			" . $vbulletin->GPC['minposts'] . ",
			'" . $db->escape_string($vbulletin->GPC['rankimg']) . "',
			" . $vbulletin->GPC['usergroupid'] . ",
			$type,
			" . $vbulletin->GPC['stack'] . ",
			" . $vbulletin->GPC['display'] . "
			)
	");

	build_ranks();

	define('CP_REDIRECT', 'ranks.php?do=modify');
	print_stop_message('saved_user_rank_successfully');
}

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit' OR $_REQUEST['do'] == 'add')
{

	if ($_REQUEST['do'] == 'edit')
	{
		$ranks = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "ranks
			WHERE rankid = " . $vbulletin->GPC['rankid'] . "
		");
		print_form_header('ranks', 'doupdate');
	}
	else
	{
		$ranks = array(
			'ranklevel'   => 1,
			'usergroupid' => -1,
			'minposts'    => 10,
			'rankimg'     => 'images/',
		);
		print_form_header('ranks', 'insert');
	}

	if ($ranks['type'])
	{
		$ranktext = $ranks['rankimg'];
	}
	else
	{
		$rankimg = $ranks['rankimg'];
	}

	$displaytype = array(
		$vbphrase['always'],
		$vbphrase['if_displaygroup_equals_this_group'],
	);

	construct_hidden_code('rankid', $vbulletin->GPC['rankid']);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['user_rank'], '', $vbulletin->GPC['rankid']));
	print_input_row($vbphrase['times_to_repeat_rank'], 'ranklevel', $ranks['ranklevel']);
	print_chooser_row($vbphrase['usergroup'], 'usergroupid', 'usergroup', $ranks['usergroupid'], $vbphrase['all_usergroups']);
	print_input_row($vbphrase['minimum_posts'], 'minposts', $ranks['minposts']);
	print_yes_no_row($vbphrase['stack_rank'], 'stack', $ranks['stack']);
	print_select_row($vbphrase['display_type'], 'display', $displaytype, $ranks['display']);
	print_table_header($vbphrase['rank_type']);
	print_input_row($vbphrase['user_rank_file_path'], 'rankimg', $rankimg);
	print_input_row($vbphrase['or_you_may_enter_text'], 'rankhtml', $ranktext);

	print_submit_row();
}

// ###################### Start do update #######################
if ($_POST['do'] == 'doupdate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'ranklevel'   => TYPE_UINT,
		'minposts'    => TYPE_UINT,
		'rankimg'     => TYPE_STR,
		'usergroupid' => TYPE_INT,
		'rankhtml'    => TYPE_NOTRIM,
		'stack'       => TYPE_UINT,
		'display'     => TYPE_UINT,
	));

	if (!$vbulletin->GPC['ranklevel'] OR (!$vbulletin->GPC['rankimg'] AND !$vbulletin->GPC['rankhtml']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($vbulletin->GPC['rankhtml'])
	{
		$type = 1;
		$vbulletin->GPC['rankimg'] = $vbulletin->GPC['rankhtml'];
	}
	else
	{
		$type = 0;
		if (!(@is_file(DIR . '/' . $vbulletin->GPC['rankimg'])))
		{
			print_stop_message('invalid_file_path_specified');
		}
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "ranks
		SET ranklevel = " . $vbulletin->GPC['ranklevel'] . ",
			minposts = " . $vbulletin->GPC['minposts'] . ",
			rankimg = '" . $db->escape_string($vbulletin->GPC['rankimg']) . "',
			usergroupid = " . $vbulletin->GPC['usergroupid'] . ",
			type = $type,
			stack = " . $vbulletin->GPC['stack'] . ",
			display = " . $vbulletin->GPC['display'] . "
		WHERE rankid = " . $vbulletin->GPC['rankid'] . "
	");
	build_ranks();

	define('CP_REDIRECT', 'ranks.php?do=modify');
	print_stop_message('saved_user_rank_successfully');
}
// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'remove')
{

	print_form_header('ranks', 'kill');
	construct_hidden_code('rankid', $vbulletin->GPC['rankid']);
	print_table_header($vbphrase['confirm_deletion']);
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_user_rank']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);

}

// ###################### Start Kill #######################

if ($_POST['do'] == 'kill')
{

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "ranks WHERE rankid = " . $vbulletin->GPC['rankid']);
	build_ranks();

	define('CP_REDIRECT', 'ranks.php?do=modify');
	print_stop_message('deleted_user_rank_successfully');
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$ranks = $db->query_write("
		SELECT rankid, ranklevel, minposts, rankimg, ranks. usergroupid,title, type, display, stack
		FROM " . TABLE_PREFIX . "ranks AS ranks
		LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup USING(usergroupid)
		ORDER BY ranks.usergroupid, minposts
	");

	print_form_header('', '');
	print_table_header($vbphrase['user_rank_manager']);
	print_description_row($vbphrase['user_ranks_desc'] . '<br /><br />' . 
	construct_phrase($vbphrase['it_is_recommended_that_you_update_user_titles'], $vbulletin->session->vars['sessionurl'])
	,'',0);
	print_table_footer();

	if ($db->num_rows($ranks) == 0)
	{
		print_stop_message('no_user_ranks_defined');
	}

	print_form_header('', '');
	while ($rank = $db->fetch_array($ranks))
	{
		if ($tempgroup != $rank['usergroupid'])
		{
			if (isset($tempgroup))
			{
				print_table_break();
			}
			$tempgroup = $rank['usergroupid'];

			print_table_header(iif($rank['usergroupid'] == 0, $vbphrase['all_usergroups'], $rank['title']), 5, 1);
			print_cells_row(array($vbphrase['user_rank'], $vbphrase['minimum_posts'], $vbphrase['display_type'], $vbphrase['stack_rank'], $vbphrase['controls']), 1, '', -1);
		}

		$count = 0;
		$rankhtml = '';
		while ($count++ < $rank['ranklevel'])
		{
			if (!$rank['type'])
			{
				$rankhtml .= "<img src=\"../$rank[rankimg]\" border=\"0\" alt=\"\" />";
			}
			else
			{
				$rankhtml .= $rank['rankimg'];
			}
		}

		$cell = array(
			$rankhtml,
			vb_number_format($rank['minposts']),
			($rank['display'] ? $vbphrase['displaygroup'] : $vbphrase['always']),
			($rank['stack'] ? $vbphrase['yes'] : $vbphrase['no']),
			construct_link_code($vbphrase['edit'], "ranks.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&rankid=$rank[rankid]") . construct_link_code($vbphrase['delete'], "ranks.php?" . $vbulletin->session->vars['sessionurl'] . "do=remove&rankid=$rank[rankid]")
		);
		print_cells_row($cell, 0, '', -1);

	}
	print_table_footer();

}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>
