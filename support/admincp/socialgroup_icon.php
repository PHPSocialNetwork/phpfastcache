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
define('CVS_REVISION', '$RCSfile$ - $Revision: 24444 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('attachment_image', 'socialgroups');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminimages'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['social_group_icons_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'storage';
}

// ###################### Start checkpath #######################
function verify_upload_folder($imagepath)
{
	global $vbphrase;
	if ($imagepath == '')
	{
		print_stop_message('please_complete_required_fields');
	}
	if ($fp = @fopen($imagepath . '/test.image', 'wb'))
	{
		fclose($fp);
		if (!@unlink($imagepath . '/test.image'))
		{
			print_stop_message('test_file_write_failed', $imagepath);
		}
		return true;
	}
	else
	{
		print_stop_message('test_file_write_failed', $imagepath);
	}
}

$vbulletin->input->clean_array_gpc('r', array(
	'groupiconpath'     => TYPE_STR,
	'groupiconurl'      => TYPE_STR,
	'dowhat'         => TYPE_STR
));

// ###################### Swap from database to file system and vice versa ##########
if ($_REQUEST['do'] == 'storage')
{
	if ($vbulletin->options['usefilegroupicon'])
	{
		print_form_header('socialgroup_icon', 'switchtype');
		print_table_header("$vbphrase[storage_type]: <span class=\"normal\">$vbphrase[social_group_icons]</span>");
		print_description_row(construct_phrase($vbphrase['group_icons_are_currently_being_served_from_the_filesystem_at_x'], '<b>' . $vbulletin->options['groupiconpath'] . '</b>'));
		print_table_break();
		print_table_header('&nbsp;');
		print_radio_row($vbphrase['move_group_icons_from_filesystem_into_database'], 'dowhat', array('FS_to_DB' => ''), 'FS_to_DB');

		print_table_break();
		print_table_header('&nbsp;');
		print_radio_row($vbphrase['move_group_icons_to_a_different_directory'], 'dowhat', array('FS_to_FS' => ''));

		print_submit_row($vbphrase['go'], 0);
	}
	else
	{
		$vbulletin->GPC['dowhat'] = 'DB_to_FS';
		$_REQUEST['do'] = 'switchtype';
	}


}

// ###################### Swap from database to file system and vice versa ##########
if ($_REQUEST['do'] == 'switchtype')
{
	if ($vbulletin->GPC['dowhat'] == 'FS_to_DB')
	{
		// redirect straight through to image mover
		$vbulletin->GPC['groupiconpath'] = $vbulletin->options['groupiconpath'];
		$vbulletin->GPC['groupiconurl'] = $vbulletin->options['groupiconurl'];
		$_POST['do'] = 'doswitchtype';
	}
	else
	{
		// show a form to allow user to specify file path
		print_form_header('socialgroup_icon', 'doswitchtype');
		construct_hidden_code('dowhat', $vbulletin->GPC['dowhat']);

		if ('FS_to_FS' == $dowhat)
		{
			print_table_header($vbphrase['move_group_icons_to_a_different_directory']);
			print_description_row(construct_phrase($vbphrase['group_icons_are_currently_being_served_from_the_filesystem_at_x'], '<b>' . $vbulletin->options['groupiconpath'] . '</b>'));
		}
		else
		{
			print_table_header($vbphrase['move_group_icons_from_database_into_filesystem']);
			print_description_row($vbphrase['group_icons_are_currently_being_served_from_the_database'], false, 2, '', 'center');
		}

		print_input_row($vbphrase['group_icon_file_path_dfn'], 'groupiconpath', $vbulletin->options['groupiconpath']);
		print_input_row($vbphrase['url_to_group_icons_relative_to_your_forums_home_page'], 'groupiconurl', $vbulletin->options['groupiconurl']);
				
		print_submit_row($vbphrase['go']);
	}
}

// ############### Move files from database to file system and vice versa ###########
if ($_POST['do'] == 'doswitchtype')
{
	$vbulletin->GPC['groupiconpath'] = preg_replace('/(\/|\\\)$/s', '', $vbulletin->GPC['groupiconpath']);
	$vbulletin->GPC['groupiconurl'] = preg_replace('/(\/|\\\)$/s', '', $vbulletin->GPC['groupiconurl']);
	
	if ($vbulletin->GPC['dowhat'] == 'FS_to_FS')
	{
		$imagepath =& $vbulletin->GPC['groupiconpath'];
		$imageurl =& $vbulletin->GPC['groupiconurl'];
	}

	switch($vbulletin->GPC['dowhat'])
	{
		// #############################################################################
		// update image file path
		case 'FS_to_FS':

			if ($imagepath === $vbulletin->options['groupiconpath'] AND $imageurl === $vbulletin->options['groupiconurl'])
			{
				// new and old path are the same - show error
				print_stop_message('invalid_file_path_specified');
			}
			else
			{
				// new and old paths are different - check the directory is valid
				verify_upload_folder($imagepath);
				$oldpath = $vbulletin->options['groupiconpath'];

				// update $vboptions
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "setting SET value = 
					CASE varname
						WHEN 'groupiconpath' THEN '" . $db->escape_string($imagepath) . "'
						WHEN 'groupiconurl' THEN '" . $db->escape_string($imageurl) . "'
					ELSE value END
					WHERE varname IN('groupiconpath', 'groupiconurl')
				");
				build_options();

				// show message
				print_stop_message('your_vb_settings_have_been_updated_to_store_group_icons_in_x', $imagepath, $oldpath);
			}

			break;

		// #############################################################################
		// move userpics from database to filesystem
		case 'DB_to_FS':

			// check path is valid
			verify_upload_folder($vbulletin->GPC['groupiconpath']);

			// update $vboptions
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "setting SET value =
				CASE varname
					WHEN 'groupiconpath' THEN '" . $db->escape_string($vbulletin->GPC['groupiconpath']) . "'
					WHEN 'groupiconurl' THEN '" . $db->escape_string($vbulletin->GPC['groupiconurl']) . "'
				ELSE value END
				WHERE varname IN('groupiconpath', 'groupiconurl')
			");
			build_options();

			break;
	}

	// #############################################################################

	print_form_header('socialgroup_icon', 'domoveicon');
	print_table_header(construct_phrase($vbphrase['edit_storage_type'], "<span class=\"normal\">" . $vbphrase['social_group_icons'] . "</span>"));
	construct_hidden_code('dowhat', $vbulletin->GPC['dowhat']);

	if ($vbulletin->GPC['dowhat'] == 'DB_to_FS')
	{
		print_description_row($vbphrase['we_are_ready_to_attempt_to_move_your_group_icons_from_database_to_filesystem']);
	}
	else
	{
		print_description_row($vbphrase['we_are_ready_to_attempt_to_move_your_group_icons_from_filesystem_to_database']);
	}

	print_input_row($vbphrase['number_of_groups_to_process_per_cycle'], 'perpage', 300, 1, 5);
	print_submit_row($vbphrase['go'], '_default_', 2, $vbphrase['go_back']);

}

// ################### Move icons ######################################
if ($_REQUEST['do'] == 'domoveicon')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage' => TYPE_INT,
		'startat' => TYPE_INT,
	));

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 10;
	}

	if ($vbulletin->GPC['startat'] < 0)
	{
		$vbulletin->GPC['startat'] = 0;
	}

	$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

	$groups = $db->query_read("
		SELECT icon.groupid, icon.userid, icon.filedata, icon.dateline, icon.extension, icon.width, icon.height, icon.thumbnail_filedata, 
			icon.thumbnail_width, icon.thumbnail_height 
		FROM " . TABLE_PREFIX . "socialgroup AS socialgroup
		LEFT JOIN " . TABLE_PREFIX . "socialgroupicon AS icon ON (icon.groupid = socialgroup.groupid)
		WHERE NOT ISNULL(icon.dateline) 
		ORDER BY icon.groupid ASC 
		LIMIT " . $vbulletin->GPC['startat'] . ", " . $vbulletin->GPC['perpage'] . "
	");

	if ($vbulletin->debug OR $vbulletin->options['usefilegroupicon'])
	{
		require_once(DIR . '/includes/functions_socialgroup.php');
	}
	
	while ($group = $db->fetch_array($groups))
	{
		$group['icondateline'] = $group['dateline'];
		
		if ($vbulletin->debug)
		{
			echo "<strong>$vbphrase[social_group] : $group[groupid]</strong><br />";
			if ($group['dateline'])
			{
				echo "&nbsp;&nbsp;$vbphrase[social_group_icon] : " . fetch_socialgroupicon_url($group) . "<br />";
			}
		}

		if (!$vbulletin->options['usefilegroupicon'])
		{
			$vbulletin->options['usefilegroupicon'] = true;

			// Converting FROM mysql TO fs
			if (!empty($group['filedata']))
			{
				$icon =& datamanager_init('SocialGroupIcon', $vbulletin, ERRTYPE_CP);
				$icon->set_existing($group);

				if (!$icon->save())
				{
					require_once(DIR . '/includes/functions_socialgroup.php');
					print_stop_message('error_writing_x', basename(fetch_socialgroupicon_url($group)));
				}
			}
			unset($icon);

			$vbulletin->options['usefilegroupicon'] = false;
		}
		else
		{
			$vbulletin->options['usefilegroupicon'] = false;

			// Converting FROM fs TO mysql
			if (!empty($group['dateline']))
			{
				$path = fetch_socialgroupicon_url($group, false, true, true);
				$thumbpath = fetch_socialgroupicon_url($group, true, true, true);

				if ($filedata = @file_get_contents($path))
				{
					$icon =& datamanager_init('SocialGroupIcon', $vbulletin, ERRTYPE_CP);
					$icon->set_existing($group);
					$icon->setr('filedata', $filedata);
					
					if ($thumbdata = @file_get_contents($thumbpath))
					{
						$icon->setr('thumbnail_filedata', $thumbdata);
					}
					
					$icon->save();
					unset($icon);
				}
				//else 
				//{
					//die('could not get image filedata for: ' . $path);
				//}
				
				//@unlink($path);
				//@unlink($thumbpath);
			}

			$vbulletin->options['usefilegroupicon'] = true;
		}
		$lastgroup = $group['groupid'];
	}

	if ($lastgroup AND $db->query_first("
		SELECT icon.groupid
		FROM " . TABLE_PREFIX . "socialgroup AS socialgroup 
		LEFT JOIN " . TABLE_PREFIX . "socialgroupicon AS icon ON (icon.groupid = socialgroup.groupid) 
		WHERE NOT ISNULL(icon.dateline) 
		AND icon.groupid > $lastgroup 
		LIMIT 1
	"))
	{
		print_cp_redirect("socialgroup_icon.php?" . $vbulletin->session->vars['sessionurl'] . "do=domoveicon&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
		//TODO: pointless?
		echo "<p><a href=\"socialgroup_icon.php?" . $vbulletin->session->vars['sessionurl'] . "do=domoveicon&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'socialgroup_icon.php?do=storage');

		if (!$vbulletin->options['usefilegroupicon'])
		{
			// Update $vboptions[]
			$db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value = 1 WHERE varname = 'usefilegroupicon'");
			build_options();

			$db->query_write("UPDATE " . TABLE_PREFIX . "socialgroupicon SET filedata = '', thumbnail_filedata = ''");

			$db->hide_errors();
			$db->query_write("OPTIMIZE TABLE " . TABLE_PREFIX . "socialgroupicon");
			$db->show_errors();

			print_stop_message('images_moved_to_the_filesystem');
		}
		else
		{
			$db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value = 0 WHERE varname = 'usefilegroupicon'");
			build_options();
			print_stop_message('images_moved_to_the_database');
		}
	}
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 24444 $
|| ####################################################################
\*======================================================================*/
?>
