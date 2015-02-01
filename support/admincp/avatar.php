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
$phrasegroups = array('attachment_image');
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

print_cp_header($vbphrase['userpic_manager']);

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
	'avatarpath'     => TYPE_STR,
	'avatarurl'      => TYPE_STR,
	'profilepicpath' => TYPE_STR,
	'profilepicurl'  => TYPE_STR,
	'sigpicpath'     => TYPE_STR,
	'sigpicurl'      => TYPE_STR,
	'dowhat'         => TYPE_STR
));

// ###################### Swap from database to file system and vice versa ##########
if ($_REQUEST['do'] == 'storage')
{
	if ($vbulletin->options['usefileavatar'])
	{
		print_form_header('avatar', 'switchtype');
		print_table_header("$vbphrase[storage_type]: <span class=\"normal\">$vbphrase[user_pictures]</span>");
		print_description_row(construct_phrase($vbphrase['avatars_are_currently_being_served_from_the_filesystem_at_x'], '<b>' . $vbulletin->options['avatarpath'] . '</b>'));
		print_description_row(construct_phrase($vbphrase['profilepics_are_currently_being_served_from_the_filesystem_at_x'], '<b>' . $vbulletin->options['profilepicpath'] . '</b>'));
		print_description_row(construct_phrase($vbphrase['sigpics_are_currently_being_served_from_the_filesystem_at_x'], '<b>' . $vbulletin->options['sigpicpath'] . '</b>'));
		print_table_break();
		print_table_header('&nbsp;');
		print_radio_row($vbphrase['move_items_from_filesystem_into_database'], 'dowhat', array('FS_to_DB' => ''), 'FS_to_DB');

		print_table_break();
		print_table_header('&nbsp;');
		print_radio_row($vbphrase['move_avatars_to_a_different_directory'], 'dowhat', array('FS_to_FS1' => ''));
		print_radio_row($vbphrase['move_profilepics_to_a_different_directory'], 'dowhat', array('FS_to_FS2' => ''));
		print_radio_row($vbphrase['move_sigpics_to_a_different_directory'], 'dowhat', array('FS_to_FS3' => ''));

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
		$vbulletin->GPC['avatarpath'] = $vbulletin->options['avatarpath'];
		$vbulletin->GPC['avatarurl'] = $vbulletin->options['avatarurl'];
		$vbulletin->GPC['profilepicpath'] = $vbulletin->options['profilepicpath'];
		$vbulletin->GPC['profilepicurl'] = $vbulletin->options['profilepicurl'];
		$vbulletin->GPC['sigpicpath'] = $vbulletin->options['sigpicpath'];
		$vbulletin->GPC['sigpicurl'] = $vbulletin->options['sigpicurl'];
		$_POST['do'] = 'doswitchtype';
	}
	else
	{
		// show a form to allow user to specify file path
		print_form_header('avatar', 'doswitchtype');
		construct_hidden_code('dowhat', $vbulletin->GPC['dowhat']);

		switch($vbulletin->GPC['dowhat'])
		{
			case 'FS_to_FS1':
				print_table_header($vbphrase['move_avatars_to_a_different_directory']);
				print_description_row(construct_phrase($vbphrase['avatars_are_currently_being_served_from_the_filesystem_at_x'], '<b>' . $vbulletin->options['avatarpath'] . '</b>'));
				print_input_row($vbphrase['avatar_file_path_dfn'], 'avatarpath', $vbulletin->options['avatarpath']);
				print_input_row($vbphrase['url_to_avatars_relative_to_your_forums_home_page'], 'avatarurl', $vbulletin->options['avatarurl']);
				break;

			case 'FS_to_FS2':
				print_table_header($vbphrase['move_profilepics_to_a_different_directory']);
				print_description_row(construct_phrase($vbphrase['profilepics_are_currently_being_served_from_the_filesystem_at_x'], '<b>' . $vbulletin->options['profilepicpath'] . '</b>'));
				print_input_row($vbphrase['profilepic_file_path_dfn'], 'profilepicpath', $vbulletin->options['profilepicpath']);
				print_input_row($vbphrase['url_to_profilepics_relative_to_your_forums_home_page'], 'profilepicurl', $vbulletin->options['profilepicurl']);
				break;

			case 'FS_to_FS3':
				print_table_header($vbphrase['move_sigpics_to_a_different_directory']);
				print_description_row(construct_phrase($vbphrase['sigpics_are_currently_being_served_from_the_filesystem_at_x'], '<b>' . $vbulletin->options['sigpicpath'] . '</b>'));
				print_input_row($vbphrase['sigpic_file_path_dfn'], 'sigpicpath', $vbulletin->options['sigpicpath']);
				print_input_row($vbphrase['url_to_sigpics_relative_to_your_forums_home_page'], 'sigpicurl', $vbulletin->options['sigpicurl']);
				break;

			default:
				print_table_header($vbphrase['move_items_from_database_into_filesystem']);
				print_description_row($vbphrase['images_are_currently_being_served_from_the_database'], false, 2, '', 'center');
				print_input_row($vbphrase['avatar_file_path_dfn'], 'avatarpath', $vbulletin->options['avatarpath']);
				print_input_row($vbphrase['url_to_avatars_relative_to_your_forums_home_page'], 'avatarurl', $vbulletin->options['avatarurl']);
				print_input_row($vbphrase['profilepic_file_path_dfn'], 'profilepicpath', $vbulletin->options['profilepicpath']);
				print_input_row($vbphrase['url_to_profilepics_relative_to_your_forums_home_page'], 'profilepicurl', $vbulletin->options['profilepicurl']);
				print_input_row($vbphrase['sigpic_file_path_dfn'], 'sigpicpath', $vbulletin->options['sigpicpath']);
				print_input_row($vbphrase['url_to_sigpics_relative_to_your_forums_home_page'], 'sigpicurl', $vbulletin->options['sigpicurl']);
		}

		print_submit_row($vbphrase['go']);
	}
}

// ############### Move files from database to file system and vice versa ###########
if ($_POST['do'] == 'doswitchtype')
{
	$vbulletin->GPC['avatarpath'] = preg_replace('/(\/|\\\)$/s', '', $vbulletin->GPC['avatarpath']);
	$vbulletin->GPC['avatarurl'] = preg_replace('/(\/|\\\)$/s', '', $vbulletin->GPC['avatarurl']);
	$vbulletin->GPC['profilepicpath'] = preg_replace('/(\/|\\\)$/s', '', $vbulletin->GPC['profilepicpath']);
	$vbulletin->GPC['profilepicurl'] = preg_replace('/(\/|\\\)$/s', '', $vbulletin->GPC['profilepicurl']);
	$vbulletin->GPC['sigpicpath'] = preg_replace('/(\/|\\\)$/s', '', $vbulletin->GPC['sigpicpath']);
	$vbulletin->GPC['sigpicurl'] = preg_replace('/(\/|\\\)$/s', '', $vbulletin->GPC['sigpicurl']);

	if ($vbulletin->GPC['dowhat'] == 'FS_to_FS1')
	{
		$imagepath =& $vbulletin->GPC['avatarpath'];
		$imageurl =& $vbulletin->GPC['avatarurl'];
		$path = 'avatarpath';
		$url = 'avatarurl';
	}
	else if ($vbulletin->GPC['dowhat'] == 'FS_to_FS2')
	{
		$imagepath =& $vbulletin->GPC['profilepicpath'];
		$imageurl =& $vbulletin->GPC['profilepicurl'];
		$path = 'profilepicpath';
		$url = 'profilepicurl';
	}
	else
	{
		$imagepath =& $vbulletin->GPC['sigpicpath'];
		$imageurl =& $vbulletin->GPC['sigpicurl'];
		$path = 'sigpicpath';
		$url = 'sigpicurl';
	}

	switch($vbulletin->GPC['dowhat'])
	{
		// #############################################################################
		// update image file path
		case 'FS_to_FS1':
		case 'FS_to_FS2':
		case 'FS_to_FS3':

			if ($imagepath === $vbulletin->options["$path"] AND $imageurl === $vbulletin->options["$url"])
			{
				// new and old path are the same - show error
				print_stop_message('invalid_file_path_specified');
			}
			else
			{
				// new and old paths are different - check the directory is valid
				verify_upload_folder($imagepath);
				$oldpath = $vbulletin->options["$path"];

				// update $vboptions
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "setting SET value =
					CASE varname
						WHEN '$path' THEN '" . $db->escape_string($imagepath) . "'
						WHEN '$url' THEN '" . $db->escape_string($imageurl) . "'
					ELSE value END
					WHERE varname IN('$path', '$url')
				");
				build_options();

				// show message
				print_stop_message('your_vb_settings_have_been_updated_to_store_images_in_x', $imagepath, $oldpath);
			}

			break;

		// #############################################################################
		// move userpics from database to filesystem
		case 'DB_to_FS':

			// check path is valid
			verify_upload_folder($vbulletin->GPC['avatarpath']);
			verify_upload_folder($vbulletin->GPC['profilepicpath']);
			verify_upload_folder($vbulletin->GPC['sigpicpath']);

			// update $vboptions
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "setting SET value =
				CASE varname
					WHEN 'avatarpath' THEN '" . $db->escape_string($vbulletin->GPC['avatarpath']) . "'
					WHEN 'avatarurl' THEN '" . $db->escape_string($vbulletin->GPC['avatarurl']) . "'
					WHEN 'profilepicpath' THEN '" . $db->escape_string($vbulletin->GPC['profilepicpath']) . "'
					WHEN 'profilepicurl' THEN '" . $db->escape_string($vbulletin->GPC['profilepicurl']) . "'
					WHEN 'sigpicpath' THEN '" . $db->escape_string($vbulletin->GPC['sigpicpath']) . "'
					WHEN 'sigpicurl' THEN '" . $db->escape_string($vbulletin->GPC['sigpicurl']) . "'
				ELSE value END
				WHERE varname IN('avatarpath', 'avatarurl', 'profilepicurl', 'profilepicpath', 'sigpicurl', 'sigpicpath')
			");
			build_options();

			break;
	}

	// #############################################################################

	print_form_header('avatar', 'domoveavatar');
	print_table_header(construct_phrase($vbphrase['edit_storage_type'], "<span class=\"normal\">" . $vbphrase['user_pictures'] . "</span>"));
	construct_hidden_code('dowhat', $vbulletin->GPC['dowhat']);

	if ($vbulletin->GPC['dowhat'] == 'DB_to_FS')
	{
		print_description_row($vbphrase['we_are_ready_to_attempt_to_move_your_images_from_database_to_filesystem']);
	}
	else
	{
		print_description_row($vbphrase['we_are_ready_to_attempt_to_move_your_images_from_filesystem_to_database']);
	}

	print_input_row($vbphrase['number_of_users_to_process_per_cycle'], 'perpage', 300, 1, 5);
	print_submit_row($vbphrase['go']);

}

// ################### Move avatars ######################################
if ($_REQUEST['do'] == 'domoveavatar')
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

	if ($vbulletin->debug)
	{
		echo '<p>' . $imagetype . '</p>';
	}

	$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

	$images = $db->query_read("
		SELECT user.userid, user.avatarrevision, user.profilepicrevision, user.sigpicrevision,
			customavatar.filename AS afilename, customavatar.filedata AS afiledata, customavatar.filedata_thumb AS afiledata_thumb,
			customprofilepic.filename AS pfilename, customprofilepic.filedata AS pfiledata,
			sigpic.filename AS sfilename, sigpic.filedata AS sfiledata
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (user.userid = customavatar.userid)
		LEFT JOIN " . TABLE_PREFIX . "customprofilepic AS customprofilepic ON (user.userid = customprofilepic.userid)
		LEFT JOIN " . TABLE_PREFIX . "sigpic AS sigpic ON (user.userid = sigpic.userid)
		WHERE NOT ISNULL(customavatar.userid) OR NOT ISNULL(customprofilepic.userid) OR NOT ISNULL(sigpic.userid)
		ORDER BY user.userid ASC
		LIMIT " . $vbulletin->GPC['startat'] . ", " . $vbulletin->GPC['perpage'] . "
	");

	while ($image = $db->fetch_array($images))
	{
		if ($vbulletin->debug)
		{
			echo "<strong>$vbphrase[user] : $image[userid]</strong><br />";
			if ($image['afilename'])
			{
				echo "&nbsp;&nbsp;$vbphrase[avatar] : $image[afilename]<br />";
			}
			if ($image['pfilename'])
			{
				echo "&nbsp;&nbsp;$vbphrase[profile_picture] : $image[pfilename]<br />";
			}
			if ($image['sfilename'])
			{
				echo "&nbsp;&nbsp;$vbphrase[sinature_pic] : $image[pfilename]<br />";
			}
		}
		if (!$vbulletin->options['usefileavatar'])
		{
			$vbulletin->options['usefileavatar'] = true;

			// Converting FROM mysql TO fs
			if (!empty($image['afiledata']))
			{
				$userpic =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_CP, 'userpic');
				$userpic->set_existing($image);
				$userpic->setr('filedata', $image['afiledata']);
				#if ($image['afiledata_thumb'])
				#{
				#	$userpic->setr('filedata_thumb', $image['afiledata_thumb']);
				#}
				if (!$userpic->save())
				{
					print_stop_message('error_writing_x', $image['afilename']);
				}
			}

			if (!empty($image['pfiledata']))
			{
				$userpic =& datamanager_init('Userpic_Profilepic', $vbulletin, ERRTYPE_CP, 'userpic');
				$userpic->set_existing($image);
				$userpic->setr('filedata', $image['pfiledata']);
				if (!$userpic->save())
				{
					print_stop_message('error_writing_x', $image['pfilename']);
				}
			}

			if (!empty($image['sfiledata']))
			{
				$userpic =& datamanager_init('Userpic_Sigpic', $vbulletin, ERRTYPE_CP, 'userpic');
				$userpic->set_existing($image);
				$userpic->setr('filedata', $image['sfiledata']);
				if (!$userpic->save())
				{
					print_stop_message('error_writing_x', $image['sfilename']);
				}
			}
			unset($userpic);

			$vbulletin->options['usefileavatar'] = false;
		}
		else
		{
			$vbulletin->options['usefileavatar'] = false;

			// Converting FROM fs TO mysql
			if (!empty($image['afilename']))
			{
				$path = $vbulletin->options['avatarpath'] . "/avatar$image[userid]_$image[avatarrevision].gif";
				$thumbpath = $vbulletin->options['avatarpath'] . "/thumbs/avatar$image[userid]_$image[avatarrevision].gif";
				if ($filedata = @file_get_contents($path))
				{
					$userpic =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_CP, 'userpic');
					$userpic->set_existing($image);
					$userpic->setr('filedata', $filedata);
					#if ($thumbdata = @file_get_contents($thumbpath))
					#{
					#	$userpic->setr('filedata_thumb', $thumbdata);
					#}
					$userpic->save();
					unset($userpic);
				}
				#@unlink($path);
				#@unlink($thumbpath);
			}

			if (!empty($image['pfilename']))
			{
				$path = $vbulletin->options['profilepicpath'] . "/profilepic$image[userid]_$image[profilepicrevision].gif";
				if ($filedata = @file_get_contents($path))
				{
					$userpic =& datamanager_init('Userpic_Profilepic', $vbulletin, ERRTYPE_CP, 'userpic');
					$userpic->set_existing($image);
					$userpic->setr('filedata', $filedata);
					$userpic->save();
					unset($userpic);
				}
				#@unlink($path);
			}

			if (!empty($image['sfilename']))
			{
				$path = $vbulletin->options['sigpicpath'] . "/sigpic$image[userid]_$image[sigpicrevision].gif";
				if ($filedata = @file_get_contents($path))
				{
					$userpic =& datamanager_init('Userpic_Sigpic', $vbulletin, ERRTYPE_CP, 'userpic');
					$userpic->set_existing($image);
					$userpic->setr('filedata', $filedata);
					$userpic->save();
					unset($userpic);
				}
				#@unlink($path);
			}

			$vbulletin->options['usefileavatar'] = true;
		}
		$lastuser = $image['userid'];
	}

	if ($lastuser AND $db->query_first("
		SELECT user.userid
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (user.userid = customavatar.userid)
		LEFT JOIN " . TABLE_PREFIX . "customprofilepic AS customprofilepic ON (user.userid = customprofilepic.userid)
		LEFT JOIN " . TABLE_PREFIX . "sigpic AS sigpic ON (user.userid = sigpic.userid)
		WHERE user.userid > $lastuser
			AND (NOT ISNULL(customavatar.userid) OR NOT ISNULL(customprofilepic.userid) OR NOT ISNULL(sigpic.userid))
		LIMIT 1
	"))
	{
		print_cp_redirect("avatar.php?" . $vbulletin->session->vars['sessionurl'] . "do=domoveavatar&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
		echo "<p><a href=\"avatar.php?" . $vbulletin->session->vars['sessionurl'] . "do=domoveavatar&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'avatar.php?do=storage');

		if (!$vbulletin->options['usefileavatar'])
		{
			// Update $vboptions[]
			$db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value = 1 WHERE varname = 'usefileavatar'");
			build_options();

			$db->query_write("UPDATE " . TABLE_PREFIX . "customavatar SET filedata = '', filedata_thumb = ''");
			$db->query_write("UPDATE " . TABLE_PREFIX . "customprofilepic SET filedata = ''");
			$db->query_write("UPDATE " . TABLE_PREFIX . "sigpic SET filedata = ''");

			$db->hide_errors();
			$db->query_write("OPTIMIZE TABLE " . TABLE_PREFIX . "customavatar");
			$db->query_write("OPTIMIZE TABLE " . TABLE_PREFIX . "customprofilepic");
			$db->query_write("OPTIMIZE TABLE " . TABLE_PREFIX . "sigpic");
			$db->show_errors();

			print_stop_message('images_moved_to_the_filesystem');
		}
		else
		{
			$db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value = 0 WHERE varname = 'usefileavatar'");
			build_options();
			print_stop_message('images_moved_to_the_database');
		}
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
