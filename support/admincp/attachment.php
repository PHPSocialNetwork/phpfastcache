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
@set_time_limit(0);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 62098 $');
@ini_set('display_errors', 'On');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('attachment_image');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_attachment.php');
require_once(DIR . '/includes/functions_file.php');
require_once(DIR . '/packages/vbattach/attach.php');

vB_Router::setRelativePath('../');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminthreads'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'attachmentid' => TYPE_INT,
	'extension'    => TYPE_STR,
	'attachpath'   => TYPE_STR,
	'dowhat'       => TYPE_STR,
));


log_admin_action(iif($vbulletin->GPC['attachmentid'] != 0, 'attachment id = ' . $vbulletin->GPC['attachmentid'],
				 iif(!empty($vbulletin->GPC['extension']), "extension = " . $vbulletin->GPC['extension'], '')));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['attachment_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'intro';
}

// ###################### Swap from database to file system and vice versa ##########
if ($_REQUEST['do'] == 'storage')
{
	if ($vbulletin->options['attachfile'])
	{
		$options = array(
			'FS_to_DB' => $vbphrase['move_items_from_filesystem_into_database'],
			'FS_to_FS' => $vbphrase['move_items_to_a_different_directory']
		);
	}
	else
	{
		$options = array(
			'DB_to_FS' => $vbphrase['move_items_from_database_into_filesystem']
		);
	}

	$i = 0;
	$dowhat = '';
	foreach($options AS $value => $text)
	{
		$dowhat .= "<label for=\"dw$value\"><input type=\"radio\" name=\"dowhat\" id=\"dw$value\" value=\"$value\"" . iif($i++ == 0, ' checked="checked"') . " />$text</label><br />";
	}

	print_form_header('attachment', 'switchtype');
	print_table_header("$vbphrase[storage_type]: <span class=\"normal\">$vbphrase[attachments]</span>");
	if ($vbulletin->options['attachfile'])
	{
		print_description_row(construct_phrase($vbphrase['attachments_are_currently_being_stored_in_the_filesystem_at_x'], '<b>' . $vbulletin->options['attachpath'] . '</b>'));
	}
	else
	{
		print_description_row($vbphrase['attachments_are_currently_being_stored_in_the_database']);
	}
	print_label_row($vbphrase['action'], $dowhat);
	print_submit_row($vbphrase['go'], 0);

}

// ###################### Swap from database to file system and vice versa ##########
if ($_REQUEST['do'] == 'switchtype')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'dowhat' 	=> TYPE_STR
	));

	if ($vbulletin->GPC['dowhat'] == 'FS_to_DB')
	{
		// redirect straight through to attachment mover
		$vbulletin->GPC['attachpath'] = $vbulletin->options['attachpath'];
		$vbulletin->GPC['dowhat'] = 'FS_to_DB';
		$_POST['do'] = 'doswitchtype';
	}
	else
	{
		if ($vbulletin->GPC['dowhat'] == 'FS_to_FS')
		{
			// show a form to allow user to specify file path
			print_form_header('attachment', 'doswitchtype');
			construct_hidden_code('dowhat', $vbulletin->GPC['dowhat']);
			print_table_header($vbphrase['move_items_to_a_different_directory']);
			print_description_row(construct_phrase($vbphrase['attachments_are_currently_being_stored_in_the_filesystem_at_x'], '<b>' . $vbulletin->options['attachpath'] . '</b>'));
		}
		else
		{
			if (SAFEMODE)
			{
				// Attachments as files is not compatible with safe_mode since it creates directories
				// Safe_mode does not allow you to write to directories created by PHP
				print_stop_message('your_server_has_safe_mode_enabled');
			}
			// show a form to allow user to specify file path
			print_form_header('attachment', 'doswitchtype');
			construct_hidden_code('dowhat', $vbulletin->GPC['dowhat']);
			print_table_header($vbphrase['move_items_from_database_into_filesystem']);
			print_description_row($vbphrase['attachments_are_currently_being_stored_in_the_database']);
		}

		print_input_row($vbphrase['attachment_file_path_dfn'], 'attachpath', $vbulletin->options['attachpath']);
		print_submit_row($vbphrase['go']);
	}
}

// ############### Move files from database to file system and vice versa ###########
if ($_POST['do'] == 'doswitchtype')
{
	$vbulletin->GPC['attachpath'] = preg_replace('#[/\\\]+$#', '', $vbulletin->GPC['attachpath']);

	switch($vbulletin->GPC['dowhat'])
	{
		// #############################################################################
		// update attachment file path
		case 'FS_to_FS':

			if ($vbulletin->GPC['attachpath'] === $vbulletin->options['attachpath'])
			{
				// new and old path are the same - show error
				print_stop_message('invalid_file_path_specified');
			}
			else
			{
				// new and old paths are different - check the directory is valid
				verify_upload_folder($vbulletin->GPC['attachpath']);
				$oldpath = $vbulletin->options['attachpath'];

				// update $vboptions
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "setting
					SET value = '" . $db->escape_string($vbulletin->GPC['attachpath']) . "'
					WHERE varname = 'attachpath'
				");
				build_options();

				// show message
				print_stop_message('your_vb_settings_have_been_updated_to_store_attachments_in_x', $vbulletin->GPC['attachpath'], $oldpath);
			}

			break;

		// #############################################################################
		// move attachments from database to filesystem
		case 'DB_to_FS':

			// check path is valid
			verify_upload_folder($vbulletin->GPC['attachpath']);

			// update $vboptions
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "setting
				SET value = '" . $db->escape_string($vbulletin->GPC['attachpath']) . "'
				WHERE varname = 'attachpath'
			");
			build_options();

			break;
	}

	// #############################################################################

	print_form_header('attachment', 'domoveattachment');
	print_table_header($vbphrase['edit_storage_type']);
	construct_hidden_code('dowhat', $vbulletin->GPC['dowhat']);

	if ($vbulletin->GPC['dowhat'] == 'DB_to_FS')
	{
		print_description_row($vbphrase['we_are_ready_to_attempt_to_move_your_attachments_from_database_to_filesystem']);
	}
	else
	{
		print_description_row($vbphrase['we_are_ready_to_attempt_to_move_your_attachments_from_filesystem_to_database']);
	}

	print_input_row($vbphrase['number_of_attachments_to_process_per_cycle'], 'perpage', 300, 1, 5);
	if ($vbulletin->debug)
	{
		print_input_row($vbphrase['attachmentid_start_at'], 'startat', 0, 1, 5);
	}
	print_submit_row($vbphrase['go']);
}

// ################### Move attachments ######################################
if ($_REQUEST['do'] == 'domoveattachment')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'          => TYPE_UINT,
		'startat'          => TYPE_UINT,
		'attacherrorcount' => TYPE_UINT,
		'count'            => TYPE_UINT
	));

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 10;
	}

	if (empty($vbulletin->GPC['startat'])) // Grab the first attachmentid so that we don't process a bunch of nonexistent ids to begin with.
	{
		$start = $db->query_first("SELECT MIN(filedataid) AS min FROM " . TABLE_PREFIX . "filedata");
		$vbulletin->GPC['startat'] = intval($start['min']);
	}
	$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

	// echo '<p>' . $vbphrase['attachments'] . '</p>';

	$attachments = $db->query_read("
		SELECT filedataid, filedata, filesize, userid, thumbnail
		FROM " . TABLE_PREFIX . "filedata
		WHERE filedataid >= " . $vbulletin->GPC['startat'] . " AND filedataid < $finishat
		ORDER BY filedataid ASC
	");

	if ($vbulletin->debug)
	{
		echo '<table width="100%" border="1" cellspacing="0" cellpadding="1">
				<tr>
				<td><b>Filedata ID</b></td><td><b>Size in Database</b></td><td><b>Size in Filesystem</b></td>
				</tr>
			';
	}
	while ($attachment = $db->fetch_array($attachments))
	{
		$vbulletin->GPC['count']++;
		$attacherror = false;
		if ($vbulletin->options['attachfile'] == ATTACH_AS_DB)
		{ // Converting FROM mysql TO fs
			$vbulletin->options['attachfile'] = ATTACH_AS_FILES_NEW;

			$attachdata =& datamanager_init('Filedata', $vbulletin, ERRTYPE_SILENT, 'attachment');
			$attachdata->set_existing($attachment);
			if (!($result = $attachdata->save()))
			{
				if (empty($attachdata->errors[0]))
				{
					$attacherror = fetch_error('upload_file_failed'); // change this error
				}
				else
				{
					$attacherror =& $attachdata->errors[0];
				}
			}
			unset($attachdata);
			$filepath = fetch_attachment_path($attachment['userid'], $attachment['filedataid']);
			if (!is_readable($filepath) OR @filesize($filepath) == 0)
			{
				$vbulletin->GPC['attacherrorcount']++;
			}

			$vbulletin->options['attachfile'] = ATTACH_AS_DB;
		}
		else
		{ // Converting FROM fs TO mysql
			$path = fetch_attachment_path($attachment['userid'], $attachment['filedataid']);
			$thumbnail_path = fetch_attachment_path($attachment['userid'], $attachment['filedataid'], true);

			$temp = $vbulletin->options['attachfile'];
			$vbulletin->options['attachfile'] = ATTACH_AS_DB;

			if ($filedata = @file_get_contents($path))
			{
				$thumbnail_filedata = @file_get_contents($thumbnail_path);

				$attachdata =& datamanager_init('Filedata', $vbulletin, ERRTYPE_SILENT, 'attachment');
				$attachdata->set_existing($attachment);
				$attachdata->setr('filedata', $filedata);
				$attachdata->setr('thumbnail', $thumbnail_filedata);

				if (!($result = $attachdata->save()))
				{
					if (empty($attachdata->errors[0]))
					{
						$attacherror = fetch_error('upload_file_failed'); // change this error
					}
					else
					{
						$attacherror =& $attachdata->errors[0];
					}
				}
				unset($attachdata);
			}
			else
			{
				// Add error about file missing..
				$vbulletin->GPC['attacherrorcount']++;
			}

			$vbulletin->options['attachfile'] = $temp;

		}
		if ($vbulletin->debug)
		{
			echo "	<tr>
					<td>$attachment[filedataid]" . iif($attacherror, "<br />$attacherror") . "</td>
					<td>$attachment[filesize]</td>
					<td>$filesize / $thumbnail_filesize</td>
					</tr>
					";
		}
		else
		{
			echo "$vbphrase[attachment] : <b>$attachment[filedataid]</b><br />";
			if ($attacherror)
			{
				echo "$vbphrase[attachment] : <b>$attachment[filedataid] $vbphrase[error]</b> $attacherror<br />";
			}
			vbflush();
		}
	}

	if ($vbulletin->debug)
	{
		echo '</table>';
		vbflush();
	}
	if ($checkmore = $db->query_first("SELECT filedataid FROM " . TABLE_PREFIX . "filedata WHERE filedataid >= $finishat LIMIT 1"))
	{
		print_cp_redirect("attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=domoveattachment&startat=$finishat" .
												"&pp=" . $vbulletin->GPC['perpage'] .
												"&count=" . $vbulletin->GPC['count'] .
												"&attacherrorcount=" . $vbulletin->GPC['attacherrorcount']);

		echo "<p><a href=\"attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=domoveattachment&amp;startat=$finishat" .
												"&amp;pp=" . $vbulletin->GPC['perpage'] .
												"&amp;count=" . $vbulletin->GPC['count'] .
												"&amp;attacherrorcount=" . $vbulletin->GPC['attacherrorcount'] . "\">" .
												$vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		if ($db->num_rows($attachments) > 0)
		{
			// Bump this to a new page
			print_cp_redirect("attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=domoveattachment&startat=$finishat" .
													"&pp=" . $vbulletin->GPC['perpage'] .
													"&count=" . $vbulletin->GPC['count'] .
													"&attacherrorcount=" . $vbulletin->GPC['attacherrorcount']);
			echo "<p><a href=\"attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=domoveattachment&amp;startat=$finishat" .
													"&amp;pp=" . $vbulletin->GPC['perpage'] .
													"&amp;count=" . $vbulletin->GPC['count'] .
													"&amp;attacherrorcount=" . $vbulletin->GPC['attacherrorcount'] . "\">" .
													$vbphrase['click_here_to_continue_processing'] . "</a></p>";
		}

		$totalattach = $db->query_first("SELECT COUNT(*) AS count FROM " . TABLE_PREFIX . "filedata");
		if ($vbulletin->options['attachfile'] == ATTACH_AS_DB)
		{
			// Here we get a form that the user must continue on to delete the filedata column so that they are really sure to complete this step!
			print_form_header('attachment', 'confirmattachmentremove');
			print_table_header($vbphrase['confirm_attachment_removal']);
			print_description_row(construct_phrase($vbphrase['attachment_removal'], $totalattach['count'], $vbulletin->GPC['count'], $vbulletin->GPC['attacherrorcount']));

			if ($totalattach['count'] != $vbulletin->GPC['count'] OR !$vbulletin->GPC['count'] OR ($vbulletin->GPC['attacherrorcount'] / $vbulletin->GPC['count']) * 10 > 1)
			{
				$finalizeoption = false;
			}
			else
			{
				$finalizeoption = true;
			}

			print_yes_no_row($vbphrase['finalize'], 'removeattachments', $finalizeoption);
			print_submit_row($vbphrase['go']);

		}

		else
		{
			$filetype = $vbulletin->options['attachfile'];
			// update $vboptions // attachments are now being read from and saved to the database
			$db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value = '" . ATTACH_AS_DB . "' WHERE varname = 'attachfile'");
			build_options();

			print_form_header('attachment', 'confirmfileremove');
			print_table_header($vbphrase['confirm_attachment_removal']);
			print_description_row(construct_phrase($vbphrase['file_removal'], $totalattach['count'], $vbulletin->GPC['count'], $vbulletin->GPC['attacherrorcount']));
			construct_hidden_code('attachtype', $filetype);
			print_submit_row($vbphrase['go']);

		}
	}
}

// ###################### Confirm emptying of filedata ##########
if ($_REQUEST['do'] == 'confirmfileremove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'startat'    => TYPE_UINT,
		'perpage'    => TYPE_UINT,
		'attachtype' => TYPE_UINT,
	));

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 200;
	}

	$attachments = $db->query_read("
		SELECT filedataid, userid
		FROM " . TABLE_PREFIX . "filedata
		ORDER BY userid DESC, filedataid ASC
		LIMIT " . $vbulletin->GPC['startat'] . ", " . $vbulletin->GPC['perpage'] . "
	");
	if ($records = $db->num_rows($attachments))
	{
		echo '<p>' . construct_phrase($vbphrase['removing_x_attachments'], $records) . '</p>';
		vbflush();

		while ($attachment = $db->fetch_array($attachments))
		{
			if ($userid === null)
			{
				$userid = $attachment['userid'];
			}
			if ($vbulletin->GPC['attachtype'] == ATTACH_AS_FILES_NEW)
			{
				$path = $vbulletin->options['attachpath'] . '/' . implode('/', preg_split('//', $attachment['userid'],  -1, PREG_SPLIT_NO_EMPTY));
			}
			else
			{
				$path = $vbulletin->options['attachpath'] . '/' . $attachment['userid'];
			}

			@unlink($path . '/' . $attachment['filedataid'] . '.attach');
			@unlink($path . '/' . $attachment['filedataid'] . '.thumb');

			if ($userid != $attachment['userid'])
			{
				// Try to remove directory of previous userid
				if ($vbulletin->GPC['attachtype'] == ATTACH_AS_FILES_NEW)
				{
					$path = $vbulletin->options['attachpath'] . '/' . implode('/', preg_split('//', $userid,  -1, PREG_SPLIT_NO_EMPTY));
					$result = @rmdir($path);
					$temp = $userid;
					while ($result AND $temp > 1)
					{
						$temp = floor($temp / 10);
						$path = $vbulletin->options['attachpath'] . '/' . implode('/', preg_split('//', $temp,  -1, PREG_SPLIT_NO_EMPTY));
						$result = @rmdir($path);
					}
				}
				else
				{
					$path = $vbulletin->options['attachpath'] . '/' . $userid;
					@rmdir($path);
				}

				$userid = $attachment['userid'];
			}
		}

		// Try to remove directory
		if ($vbulletin->GPC['attachtype'] == ATTACH_AS_FILES_NEW)
		{
			$path = $vbulletin->options['attachpath'] . '/' . implode('/', preg_split('//', $userid,  -1, PREG_SPLIT_NO_EMPTY));
			$result = @rmdir($path);
			while ($result AND $temp > 1)
			{
				$userid = floor($userid / 10);
				$path = $vbulletin->options['attachpath'] . '/' . implode('/', preg_split('//', $userid,  -1, PREG_SPLIT_NO_EMPTY));
				$result = @rmdir($path);
			}
		}
		else
		{
			$path = $vbulletin->options['attachpath'] . '/' . $userid;
			@rmdir($path);
		}

		$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];
		print_cp_redirect("attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=confirmfileremove&startat=$finishat&attachtype=" . $vbulletin->GPC['attachtype'] .
											"&pp=" . $vbulletin->GPC['perpage']);

		echo "<p><a href=\"attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=confirmfileremove&amp;startat=$finishat&amp;attachtype=" . $vbulletin->GPC['attachtype'] .
											"&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" .
											$vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_CONTINUE', 'attachment.php?do=stats');
		print_stop_message('attachments_moved_to_the_database');
	}
}

// ###################### Confirm emptying of filedata ##########
if ($_REQUEST['do'] == 'confirmattachmentremove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'removeattachments' => TYPE_BOOL,
		'startat'           => TYPE_UINT,
		'perpage'           => TYPE_UINT,
	));

	if ($vbulletin->GPC['removeattachments'])
	{
		if (empty($vbulletin->GPC['perpage']))
		{
			$vbulletin->GPC['perpage'] = 500;
		}

		if ($vbulletin->GPC['startat'] == 0)
		{
			// update $vboptions to attachments as files...
			// attachfile is only set to 1 to indicate the PRE 3.0.0 RC1 attachment FS behaviour
			$db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value = '" . ATTACH_AS_FILES_NEW . "' WHERE varname = 'attachfile'");
			build_options();
		}

		$attachments = $db->query_read("
			SELECT filedataid
			FROM " . TABLE_PREFIX . "filedata
			ORDER BY filedataid
			LIMIT " . $vbulletin->GPC['startat'] . ", " . $vbulletin->GPC['perpage'] . "
		");
		if ($records = $db->num_rows($attachments))
		{
			echo '<p>' . construct_phrase($vbphrase['removing_x_attachments'], $records) . '</p>';
			vbflush();

			$attachmentids = array();
			while ($attachment = $db->fetch_array($attachments))
			{
				$attachmentids[] = $attachment['filedataid'];
			}

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "filedata
					SET filedata = '',
						thumbnail = ''
				WHERE filedataid IN (" . implode(",", $attachmentids) . ")
			");

			$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];
			print_cp_redirect("attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=confirmattachmentremove&startat=$finishat&removeattachments=1" .
												"&pp=" . $vbulletin->GPC['perpage']);

			echo "<p><a href=\"attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=confirmattachmentremove&amp;startat=$finishat&amp;removeattachments=1" .
												"&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" .
												$vbphrase['click_here_to_continue_processing'] . "</a></p>";

		}
		else
		{
			// Again, make sure we are on attachments as files setting.
			$db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value = '" . ATTACH_AS_FILES_NEW . "' WHERE varname = 'attachfile'");
			build_options();

			define('CP_CONTINUE', 'attachment.php?do=stats');
			print_stop_message('attachments_moved_to_the_filesystem', $vbulletin->session->vars['sessionurl']);
		}
	}
	else
	{
		define('CP_CONTINUE', 'attachment.php?do=stats');
		print_stop_message('attachments_not_moved_to_the_filesystem');
	}
}

// ###################### Search attachments ####################

$vbulletin->input->clean_array_gpc('r', array(
	'massdelete' => TYPE_STR
));

if ($_REQUEST['do'] == 'search' AND $vbulletin->GPC['massdelete'])
{
	$vbulletin->input->clean_array_gpc('r', array(
		'a_delete' => TYPE_ARRAY_UINT
	));

	// they hit the mass delete submit button
	if (!is_array($vbulletin->GPC['a_delete']))
	{
		// nothing in the array
		print_stop_message('invalid_attachments_specified');
	}
	else
	{
		$_REQUEST['do'] = 'massdelete';
	}
}

// ###################### Actually search attachments ####################
if ($_REQUEST['do'] == 'search')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'search'     => TYPE_ARRAY,
		'prevsearch' => TYPE_STR,
		'prunedate'  => TYPE_INT,
		'pagenum'    => TYPE_INT,
		'next_page'  => TYPE_STR,
		'prev_page'  => TYPE_STR,
	));

	// for additional pages of results
	if ($vbulletin->GPC['prevsearch'])
	{
		$vbulletin->GPC['search'] = @unserialize(verify_client_string($vbulletin->GPC['prevsearch']));
	}
	else
	{
		$vbulletin->GPC['prevsearch'] = sign_client_string(serialize($vbulletin->GPC['search']));
	}

	$vbulletin->GPC['search']['downloadsmore'] = intval($vbulletin->GPC['search']['downloadsmore']);
	$vbulletin->GPC['search']['downloadsless'] = intval($vbulletin->GPC['search']['downloadsless']);
	$vbulletin->GPC['search']['sizemore'] = intval($vbulletin->GPC['search']['sizemore']);
	$vbulletin->GPC['search']['sizeless'] = intval($vbulletin->GPC['search']['sizeless']);
	$vbulletin->GPC['search']['visible'] = (isset($vbulletin->GPC['search']['visible']) ? intval($vbulletin->GPC['search']['visible']) : -1);
	$vbulletin->GPC['search']['orderby'] = in_array($vbulletin->GPC['search']['orderby'], array('username', 'counter', 'filename', 'filesize', 'dateline', 'state')) ? $vbulletin->GPC['search']['orderby'] : 'filename';
	$vbulletin->GPC['search']['ordering'] = in_array($vbulletin->GPC['search']['ordering'], array('ASC', 'DESC')) ? $vbulletin->GPC['search']['ordering'] : 'DESC';
	$vbulletin->GPC['search']['results'] = intval($vbulletin->GPC['search']['results']);

	// error prevention
	if (!isset($vbulletin->GPC['search']['visible']) OR $vbulletin->GPC['search']['visible'] < -1 OR $vbulletin->GPC['search']['visible'] > 1)
	{
		$vbulletin->GPC['search']['visible'] = -1;
	}

	if (!$vbulletin->GPC['search']['orderby'])
	{
		$vbulletin->GPC['search']['orderby'] = 'filename';
		$vbulletin->GPC['search']['ordering'] = 'DESC';
	}
	if (!$vbulletin->GPC['search']['results'])
	{
		$vbulletin->GPC['search']['results'] = 10;
	}

	// special case
	if ($vbulletin->GPC['prunedate'] > 0)
	{
		$vbulletin->GPC['search']['datelinebefore'] = date('Y-m-d', TIMENOW - 86400 * $vbulletin->GPC['prunedate']);
	}

	if ($vbulletin->GPC['pagenum'] < 1)
	{
		$vbulletin->GPC['pagenum'] = 1;
	}

	if ($vbulletin->GPC['next_page'])
	{
		++$vbulletin->GPC['pagenum'];
	}
	else if ($vbulletin->GPC['prev_page'])
	{
		--$vbulletin->GPC['pagenum'];
	}

	$query = array(
		"a.contentid <> 0"
	);

	if ($vbulletin->GPC['search']['filename'])
	{
		$query[] = "a.filename LIKE '%" . $db->escape_string_like($vbulletin->GPC['search']['filename']) . "%' ";
	}

	if ($vbulletin->GPC['search']['attachedby'])
	{
		$user = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username='" . $db->escape_string(htmlspecialchars_uni($vbulletin->GPC['search']['attachedby'])) . "'");
		if (!$user)
		{
			$user = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username LIKE '%" . $db->escape_string_like(htmlspecialchars_uni($vbulletin->GPC['search']['attachedby'])) . "%'");
		}
		if (!$user)
		{
			print_stop_message('invalid_user_specified');
		}
		else
		{
			$query[] = "a.userid=$user[userid] ";
		}
	}

	if ($vbulletin->GPC['search']['datelinebefore'] AND $vbulletin->GPC['search']['datelineafter'])
	{
		$query[] = "(a.dateline BETWEEN UNIX_TIMESTAMP('" . $db->escape_string($vbulletin->GPC['search']['datelineafter']) . "') AND UNIX_TIMESTAMP('" . $db->escape_string($vbulletin->GPC['search']['datelinebefore']) . "')) ";
	}
	else if ($vbulletin->GPC['search']['datelinebefore'])
	{
		$query[] = "a.dateline < UNIX_TIMESTAMP('" . $db->escape_string($vbulletin->GPC['search']['datelinebefore']) . "') ";
	}
	else if ($vbulletin->GPC['search']['datelineafter'])
	{
		$query[] = "a.dateline > UNIX_TIMESTAMP('" . $db->escape_string($vbulletin->GPC['search']['datelineafter']) . "') ";
	}

	if ($vbulletin->GPC['search']['downloadsmore'] AND $vbulletin->GPC['search']['downloadsless'])
	{
		$query[] = "(a.counter BETWEEN " . $vbulletin->GPC['search']['downloadsmore'] ." AND " . $vbulletin->GPC['search']['downloadsless'] . ") ";
	}
	else if ($vbulletin->GPC['search']['downloadsless'])
	{
		$query[] = "a.counter < " . $vbulletin->GPC['search']['downloadsless'] . " ";
	}
	else if ($vbulletin->GPC['search']['downloadsmore'])
	{
		$query[] = "a.counter > " . $vbulletin->GPC['search']['downloadsmore']. " ";
	}

	if ($vbulletin->GPC['search']['sizemore'] AND $vbulletin->GPC['search']['sizeless'])
	{
		$query[] = "(fd.filesize BETWEEN " . $vbulletin->GPC['search']['sizemore'] . " AND " . $vbulletin->GPC['search']['sizeless'] . ") ";
	}
	else if ($vbulletin->GPC['search']['sizeless'])
	{
		$query[] = "fd.filesize < " . $vbulletin->GPC['search']['sizeless'] . " ";
	}
	else if ($vbulletin->GPC['search']['sizemore'])
	{
		$query[] = "fd.filesize > " . $vbulletin->GPC['search']['sizemore'] . " ";
	}

	if ($vbulletin->GPC['search']['visible'] != -1)
	{
		$query[] = "a.state = '" . ($vbulletin->GPC['search']['visible'] ? 'visible' : 'moderation') . "' ";
	}

	$attachmultiple = new vB_Attachment_Display_Multiple($vbulletin);
	$attachments = $attachmultiple->fetch_results(implode(" AND ", $query), true);

	$pages = ceil($attachments['count'] / $vbulletin->GPC['search']['results']);
	if (!$pages)
	{
		$pages = 1;
	}

	print_form_header('attachment', 'search', 0, 1);
	construct_hidden_code('prevsearch', $vbulletin->GPC['prevsearch']);
	construct_hidden_code('prunedate', $vbulletin->GPC['prunedate']);
	construct_hidden_code('pagenum', $vbulletin->GPC['pagenum']);
	print_table_header(construct_phrase($vbphrase['showing_attachments_x_to_y_of_z'], ($vbulletin->GPC['pagenum'] - 1) * $vbulletin->GPC['search']['results'] + 1,  iif($vbulletin->GPC['search']['results'] * $vbulletin->GPC['pagenum'] > $attachments['count'], $attachments['count'], $vbulletin->GPC['search']['results'] * $vbulletin->GPC['pagenum']), $attachments['count']), 7);

	print_cells_row(array(
		'<input type="checkbox" name="allbox" title="' . $vbphrase['check_all'] . '" onclick="js_check_all(this.form);" />',
		$vbphrase['filename'],
		$vbphrase['username'],
		$vbphrase['date'],
		$vbphrase['filesize'],
		$vbphrase['downloads'],
		$vbphrase['controls']
	), 1);

	$currentrow = 1;

	$attachmultiple = new vB_Attachment_Display_Multiple($vbulletin);
	$attachments = $attachmultiple->fetch_results(implode(" AND ", $query), false, ($vbulletin->GPC['pagenum'] - 1) * $vbulletin->GPC['search']['results'], $vbulletin->GPC['search']['results'], $vbulletin->GPC['search']['orderby'], $vbulletin->GPC['search']['ordering']);
	foreach ($attachments AS $attachment)
	{
		$cell = array();
		$cell[] = "<input type=\"checkbox\" name=\"a_delete[]\" value=\"$attachment[attachmentid]\" tabindex=\"1\" />";
		$cell[] = "<p align=\"" . vB_Template_Runtime::fetchStyleVar('left') . "\"><a href=\"../attachment.php?" . $vbulletin->session->vars['sessionurl'] . "attachmentid=$attachment[attachmentid]&amp;d=$attachment[dateline]\">" . fetch_censored_text(htmlspecialchars_uni($attachment['filename'], false)) . '</a></p>';
		$cell[] = iif($attachment['userid'], "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$attachment[userid]\">$attachment[username]</a>", $attachment['username']);
		$cell[] = vbdate($vbulletin->options['dateformat'], $attachment['dateline']) . construct_link_code($vbphrase['view_content'], $attachmultiple->fetch_content_url($attachment, '../'), true);
		$cell[] = vb_number_format($attachment['filesize'], 1, true);
		$cell[] = $attachment['counter'];
		$cell[] = '<span class="smallfont">' .
			construct_link_code($vbphrase['edit'], "attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;attachmentid=$attachment[attachmentid]") .
			construct_link_code($vbphrase['delete'], "attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=delete&amp;attachmentid=$attachment[attachmentid]") .
			'</span>';
		print_cells_row($cell);
		$currentrow++;
		if ($currentrow > $vbulletin->GPC['search']['results'])
		{
			break;
		}
	}
	print_description_row('<input type="submit" class="button" name="massdelete" value="' . $vbphrase['delete_selected_attachments'] . '" tabindex="1" />', 0, 7, '', 'center');


	$db->free_result($results);

	if ($pages > 1 AND $vbulletin->GPC['pagenum'] < $pages)
	{
		print_table_footer(7, iif($vbulletin->GPC['pagenum'] > 1, "<input type=\"submit\" name=\"prev_page\" class=\"button\" tabindex=\"1\" value=\"$vbphrase[prev_page]\" accesskey=\"s\" />") . "\n<input type=\"submit\" name=\"next_page\" class=\"button\" tabindex=\"1\" value=\"$vbphrase[next_page]\" accesskey=\"s\" />");
	}
	else if ($vbulletin->GPC['pagenum'] == $pages AND $pages > 1)
	{
		print_table_footer(7, "<input type=\"submit\" name=\"prev_page\" class=\"button\" tabindex=\"1\" value=\"$vbphrase[prev_page]\" accesskey=\"s\" />");
	}
	else
	{
		print_table_footer(7);
	}
}

// ###################### Edit an attachment ####################
if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'attachmentid' => TYPE_UINT
	));

	if (!$attachment = $db->query_first("
		SELECT
			attachmentid, filename, state, counter
		FROM " . TABLE_PREFIX . "attachment AS attachment
		WHERE
			attachment.attachmentid = " . $vbulletin->GPC['attachmentid'] . "
	"))
	{
		print_stop_message('no_matches_found');
	}

	print_form_header('attachment', 'doedit', true);
	construct_hidden_code('attachmentid', $vbulletin->GPC['attachmentid']);
	print_table_header($vbphrase['edit_attachment']);
	print_input_row($vbphrase['filename'], 'a_filename', htmlspecialchars_uni($attachment['filename'], false), false);
	print_input_row($vbphrase['views'], 'a_counter', $attachment['counter']);
	print_yes_no_row($vbphrase['visible'], 'a_visible', $attachment['state'] == 'visible' ? 1 : 0);
	print_submit_row($vbphrase['save']);

/*
	print_table_break();
	print_table_header($vbphrase['replace_attachment']);
	print_upload_row($vbphrase['please_select_a_file_to_attach'], 'upload', 99999999);
	print_input_row($vbphrase['or_enter_a_full_url_to_a_file'], 'url');
	print_yes_no_row($vbphrase['visible'], 'newvisible', true);
	print_submit_row($vbphrase['save']);
*/
}

// ###################### Edit an attachment ####################
if ($_POST['do'] == 'doedit')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'attachmentid' => TYPE_UINT,
		'a_filename'   => TYPE_STR,
		'a_counter'    => TYPE_UINT,
		'a_visible'    => TYPE_BOOL,
		'newvisible'   => TYPE_BOOL,
		'url'          => TYPE_STR,
	));

	if (!$attachment = $db->query_first("
		SELECT
			attachmentid, attachment.userid
		FROM " . TABLE_PREFIX . "attachment AS attachment
		WHERE
			attachment.attachmentid = " . $vbulletin->GPC['attachmentid'] . "
	"))
	{
		print_stop_message('no_matches_found');
	}

	$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_CP);
	$attachdata->set_existing($attachment);

/*
	$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);

	$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_CP);
	$attachdata->set_existing($attachment);

	# Replace attachment
	if (!empty($vbulletin->GPC['upload']['tmp_name']) OR !empty($vbulletin->GPC['url']))
	{
		require_once(DIR . '/includes/class_upload.php');
		require_once(DIR . '/includes/class_image.php');

		$upload = new vB_Upload_Attachment($vbulletin);
		$image =& vB_Image::fetch_library($vbulletin);

		$upload->data =& $attachdata;
		$attachdata->set('visible', intval($vbulletin->GPC['newvisible']));
		$attachdata->set('counter', 0);

		$upload->image =& $image;
		$upload->postinfo = array('postid' => $attachment['postid']);
		$upload->userinfo = array(
			'userid'                => $attachment['userid'],
			'attachmentpermissions' => $vbulletin->userinfo['attachmentpermissions'],
			'forumpermissions'      => $vbulletin->userinfo['forumpermissions']
		);
		$upload->emptyfile = false;

		if (!empty($vbulletin->GPC['url']))
		{
			$attachment_input = $vbulletin->GPC['url'];
		}
		else
		{
			$attachment_input = array(
				'name'     => $vbulletin->GPC['upload']['name'],
				'tmp_name' => $vbulletin->GPC['upload']['tmp_name'],
				'error'    =>	$vbulletin->GPC['upload']['error'],
				'size'     => $vbulletin->GPC['upload']['size'],
			);
		}

		$vbulletin->options['allowduplicates'] = true;
		if (!$upload->process_upload($attachment_input))
		{
			print_stop_message('generic_error_x', $upload->fetch_error());
		}
	}

*/
	# Update Attachment

	$attachdata->set('filename', $vbulletin->GPC['a_filename']);
	$attachdata->set('state', $vbulletin->GPC['a_visible'] ? 'visible' : 'moderation');
	$attachdata->set('counter', $vbulletin->GPC['a_counter']);
	$attachdata->save();

	define('CP_REDIRECT', 'attachment.php?do=stats');
	print_stop_message('updated_attachment_successfully');
}

// ###################### Delete an attachment ####################
if ($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'attachmentid' => TYPE_INT
	));

	$attachment = $db->query_first("
		SELECT filename
		FROM " . TABLE_PREFIX . "attachment AS a
		WHERE attachmentid=" . $vbulletin->GPC['attachmentid']
	);

	print_form_header('attachment', 'dodelete');
	construct_hidden_code('attachmentid', $vbulletin->GPC['attachmentid']);
	print_table_header($vbphrase['confirm_deletion']);
	print_description_row(construct_phrase($vbphrase['are_you_sure_you_want_to_delete_the_attachment_x'], htmlspecialchars_uni($attachment['filename'], false), $vbulletin->GPC['attachmentid']));
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// ###################### Do delete the attachment ####################
if ($_POST['do'] == 'dodelete')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'attachmentid' => TYPE_UINT
	));

	$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_CP, 'attachment');
	$attachdata->condition = "attachmentid = " . $vbulletin->GPC['attachmentid'];
	$attachdata->log = false;
	$attachdata->delete(true, false);

	define('CP_REDIRECT', 'attachment.php?do=intro');
	print_stop_message('deleted_attachment_successfully');

}

// ###################### Mass Delete attachments ####################
if ($_REQUEST['do'] == 'massdelete')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'a_delete' => TYPE_ARRAY_UINT
	));

	print_form_header('attachment','domassdelete');
	construct_hidden_code('a_delete', sign_client_string(serialize($vbulletin->GPC['a_delete'])));
	print_table_header($vbphrase['confirm_deletion']);
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_these_attachments']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// ###################### Mass Delete attachments ####################
if ($_POST['do'] == 'domassdelete')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'a_delete' => TYPE_STR,
	));

	$delete = @unserialize(verify_client_string($vbulletin->GPC['a_delete']));
	if ($delete AND is_array($delete))
	{
		$ids = implode(',', $delete);

		$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_CP, 'attachment');
		$attachdata->condition = "attachmentid IN (-1," . $db->escape_string($ids) . ")";
		$attachdata->log = false;
		$attachdata->delete(true, false);
	}

	define('CP_REDIRECT', 'attachment.php?do=intro');
	print_stop_message('deleted_attachments_successfully');
}

// ###################### Statistics ####################
if ($_REQUEST['do'] == 'stats')
{
	$astats = $db->query_first("
		SELECT COUNT(*) AS count, SUM(filesize) AS totalsize, SUM(counter) AS downloads
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid);
	");

	$fstats = $db->query_first("
		SELECT COUNT(*) AS count, SUM(filesize) AS totalsize
		FROM " . TABLE_PREFIX . "filedata AS fd
	");

	if ($astats['count'])
	{
		$astats['average'] = vb_number_format(($astats['totalsize'] / $astats['count']), 1, true);
	}
	else
	{
		$astats['average'] = '0.00';
	}

	print_form_header('', '');
	print_table_header($vbphrase['statistics']);
	print_label_row($vbphrase['unique_total_attachments'], vb_number_format($astats['count']) . ' / ' . vb_number_format($fstats['count']));
	print_label_row($vbphrase['attachment_filesize_sum'], vb_number_format(iif(!$astats['totalsize'], 0, $astats['totalsize']), 1, true));
	print_label_row($vbphrase['disk_space_used'], vb_number_format(iif(!$fstats['totalsize'], 0, $fstats['totalsize']), 1, true));

	if ($vbulletin->options['attachfile'])
	{
		print_label_row($vbphrase['storage_type'], construct_phrase($vbphrase['attachments_are_currently_being_stored_in_the_filesystem_at_x'], '<b>' . $vbulletin->options['attachpath'] . '</b>'));
	}
	else
	{
		print_label_row($vbphrase['storage_type'], $vbphrase['attachments_are_currently_being_stored_in_the_database']);
	}

	print_label_row($vbphrase['average_attachment_filesize'], $astats['average']);
	print_label_row($vbphrase['total_downloads'], vb_number_format($astats['downloads']));
	print_table_break();

	$position = 0;

	print_table_header($vbphrase['five_most_popular_attachments'], 5);
	print_cells_row(array('', $vbphrase['filename'], $vbphrase['username'], $vbphrase['downloads'], '&nbsp;'), 1);

	$attachmultiple = new vB_Attachment_Display_Multiple($vbulletin);
	$attachments = $attachmultiple->fetch_results("a.contentid <> 0", false, 0, 5, 'counter');
	foreach ($attachments AS $attachment)
	{
		$position++;
		$cell = array();
		$cell[] = $position . '.';
		$cell[] = "<a href=\"../attachment.php?" . $vbulletin->session->vars['sessionurl'] . "attachmentid=$attachment[attachmentid]&amp;d=$attachment[dateline]\">" . htmlspecialchars_uni($attachment['filename'], false) . "</a>";
		$cell[] = iif($attachment['userid'], "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$attachment[userid]\">$attachment[username]</a>", $attachment['username']);
		$cell[] = vb_number_format($attachment['counter']);
		$cell[] = '<span class="smallfont">' .
			construct_link_code($vbphrase['view_content'], $attachmultiple->fetch_content_url($attachment, '../'), true) .
			construct_link_code($vbphrase['edit'], "attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;attachmentid=$attachment[attachmentid]") .
			construct_link_code($vbphrase['delete'], "attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=delete&amp;attachmentid=$attachment[attachmentid]") .
			'</span>';
		print_cells_row($cell);
	}
	print_table_break();

	$largest = $db->query_read("
		SELECT
			a.attachmentid, a.dateline, a.contentid, a.counter, a.userid, a.filename, user.username
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (a.userid = user.userid)
		WHERE a.contentid <> 0
		ORDER BY fd.filesize DESC
		LIMIT 5
	");

	$position = 0;

	print_table_header($vbphrase['five_largest_attachments'], 5);
	print_cells_row(array('&nbsp;', $vbphrase['filename'], $vbphrase['username'], $vbphrase['filesize'], '&nbsp;'), 1);

	$attachmultiple = new vB_Attachment_Display_Multiple($vbulletin);
	$attachments = $attachmultiple->fetch_results("a.contentid <> 0", false, 0, 5, 'filesize');
	foreach ($attachments AS $attachment)
	{
		$position++;
		$cell = array();
		$cell[] = $position . '.';
		$cell[] = "<a href=\"../attachment.php?" . $vbulletin->session->vars['sessionurl'] . "attachmentid=$attachment[attachmentid]&amp;d=$attachment[dateline]\">" . htmlspecialchars_uni($attachment['filename'], false) . "</a>";
		$cell[] = iif($attachment['userid'], "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$attachment[userid]\">$attachment[username]</a>", $attachment['username']);
		$cell[] = vb_number_format($attachment['filesize'], 1, true);
		$cell[] = '<span class="smallfont">' .
			construct_link_code($vbphrase['view_content'], $attachmultiple->fetch_content_url($attachment, '../'), true) .
			construct_link_code($vbphrase['edit'], "attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;attachmentid=$attachment[attachmentid]") .
			construct_link_code($vbphrase['delete'], "attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=delete&amp;attachmentid=$attachment[attachmentid]") .
			'</span>';
		print_cells_row($cell);
	}
	print_table_break();

	$content = array();
	$largestuser = $db->query_read("
		SELECT COUNT(*) AS count, SUM(filesize) AS totalsize, user.userid, username
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (a.userid = user.userid)
		GROUP BY a.userid
		HAVING totalsize > 0
		ORDER BY totalsize DESC
		LIMIT 5
	");
	$position = 0;

	print_table_header($vbphrase['five_users_most_attachment_space'], 5);
	print_cells_row(array('&nbsp;', $vbphrase['username'], $vbphrase['attachments'], $vbphrase['total_size'], '&nbsp;'), 1);
	while($thispop = $db->fetch_array($largestuser))
	{
		$position++;
		$cell = array();
		$cell[] = $position . '.';
		$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$thispop[userid]\">$thispop[username]</a>";
		$cell[] = vb_number_format($thispop['count']);
		$cell[] = vb_number_format($thispop['totalsize'], 1, true);
		$cell[] = '<span class="smallfont">' . construct_link_code($vbphrase['view_attachments'], "attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=search&amp;search[attachedby]=" . urlencode($thispop['username'])) . '</span>';
		print_cells_row($cell);
	}
	print_table_footer();
}

// ###################### Introduction ####################
if ($_REQUEST['do'] == 'intro')
{
	print_form_header('attachment', 'search');
	print_table_header($vbphrase['quick_search']);
	print_description_row("
	<ul style=\"margin:0px; padding:0px; list-style:none\">
		<li><a href=\"attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=search&amp;search[orderby]=filesize&amp;search[ordering]=DESC\">" . $vbphrase['view_largest_attachments'] . "</a></li>
		<li><a href=\"attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=search&amp;search[orderby]=counter&amp;search[ordering]=DESC\">" . $vbphrase['view_most_popular_attachments'] . "</a></li>
		<li><a href=\"attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=search&amp;search[orderby]=dateline&amp;search[ordering]=DESC\">" . $vbphrase['view_newest_attachments'] . "</a></li>
		<li><a href=\"attachment.php?" . $vbulletin->session->vars['sessionurl'] . "do=search&amp;search[orderby]=dateline&amp;search[ordering]=ASC\">" . $vbphrase['view_oldest_attachments'] . "</a></li>
	</ul>
	");
	print_table_break();

	print_table_header($vbphrase['prune_attachments']);
	print_input_row($vbphrase['find_all_attachments_older_than_days'], 'prunedate', 30);
	print_submit_row($vbphrase['search'], 0);

	print_form_header('attachment', 'search');
	print_table_header($vbphrase['advanced_search']);
	print_input_row($vbphrase['filename'], 'search[filename]');
	print_input_row($vbphrase['attached_by'], 'search[attachedby]');
	print_input_row($vbphrase['attached_before'], 'search[datelinebefore]');
	print_input_row($vbphrase['attached_after'], 'search[datelineafter]');
	print_input_row($vbphrase['downloads_greater_than'], 'search[downloadsmore]');
	print_input_row($vbphrase['downloads_less_than'], 'search[downloadsless]');
	print_input_row($vbphrase['filesize_greater_than'], 'search[sizemore]');
	print_input_row($vbphrase['filesize_less_than'], 'search[sizeless]');
	print_yes_no_other_row($vbphrase['attachment_is_visible'], 'search[visible]', $vbphrase['either'], -1);

	print_label_row($vbphrase['order_by'],'
		<select name="search[orderby]" tabindex="1" class="bginput">
			<option value="username">' . $vbphrase['attached_by'] . '</option>
			<option value="counter">' . $vbphrase['downloads'] . '</option>
			<option value="filename" selected="selected">' . $vbphrase['filename'] . '</option>
			<option value="filesize">' . $vbphrase['filesize'] . '</option>
			<option value="dateline">' . $vbphrase['time'] . '</option>
			<option value="state">' . $vbphrase['visible'] . '</option>
		</select>
		<select name="search[ordering]" tabindex="1" class="bginput">
			<option value="DESC">' . $vbphrase['descending'] . '</option>
			<option value="ASC">' . $vbphrase['ascending'] . '</option>
		</select>
	', '', 'top', 'orderby');
	print_input_row($vbphrase['attachments_to_show_per_page'], 'search[results]', 20);

	print_submit_row($vbphrase['search'], 0);
}

// ###################### File Types ####################
if ($_REQUEST['do'] == 'types')
{
	$types = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "attachmenttype ORDER BY extension");

	// a little javascript for the options menus
	?>
	<script type="text/javascript">
	<!--
	function js_attachment_jump(attachinfo)
	{
		if (attachinfo == '')
		{
			alert('<?php echo addslashes_js($vbphrase['please_select_attachment']); ?>');
			return;
		}
		else
		{
			action = eval("document.cpform.a" + attachinfo + ".options[document.cpform.a" + attachinfo + ".selectedIndex].value");
		}
		if (action != '')
		{
			switch (action)
			{
				case 'edit':   page = "attachment.php?do=updatetype&extension="; break;
				case 'remove': page = "attachment.php?do=removetype&extension="; break;
				case 'perms':  page = "attachmentpermission.php?do=modify&extension=";

					break;
			}
			document.cpform.reset();
			jumptopage = page + attachinfo + "&s=<?php echo $vbulletin->session->vars['sessionhash']; ?>";
			if (action == 'perms')
			{
				window.location = jumptopage + '#a_' + attachinfo;
			}
			else
			{
				window.location = jumptopage;
			}
		}
		else
		{
			alert('<?php echo addslashes_js($vbphrase['invalid_action_specified']); ?>');
		}
	}
	//-->
	</script>
	<?php

	print_form_header('attachment', 'updatetype');
	print_table_header($vbphrase['attachment_manager'], 5);
	print_cells_row(array(
		$vbphrase['extension'],
		$vbphrase['maximum_filesize'],
		$vbphrase['maximum_width'],
		$vbphrase['maximum_height'],
		$vbphrase['controls']
	), 1, 'tcat');

	$attachoptions = array(
		'edit'   => $vbphrase['edit'],
		'remove' => $vbphrase['delete'],
		'perms'  => $vbphrase['view_permissions'],
	);

	while ($type = $db->fetch_array($types))
	{
		$contenttype = unserialize($type['contenttypes']);
		$type['size'] = iif($type['size'], $type['size'], $vbphrase['none']);
		switch($type['extension'])
		{
			case 'gif':
			case 'bmp':
			case 'jpg':
			case 'jpeg':
			case 'jpe':
			case 'png':
			case 'psd':
			case 'tiff':
			case 'tif':
				$type['width'] = iif($type['width'], $type['width'], $vbphrase['none']);
				$type['height'] = iif($type['height'], $type['height'], $vbphrase['none']);
				break;
			default:
				$type['width'] = '&nbsp;';
				$type['height'] = '&nbsp;';
		}
		$cell = array();
		$cell[] = "<b>$type[extension]</b>";
		$cell[] = $type['size'];
		$cell[] = $type['width'];
		$cell[] = $type['height'];

		$cell[] = "\n\t<select name=\"a$type[extension]\" onchange=\"js_attachment_jump('$type[extension]');\" class=\"bginput\">\n" . construct_select_options($attachoptions) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_attachment_jump('$type[extension]');\" />\n\t";
		print_cells_row($cell);
	}
	print_submit_row($vbphrase['add_new_extension'], 0, 5);
}

// ###################### File Types ####################
if ($_REQUEST['do'] == 'updatetype')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'extension' => TYPE_STR
	));

	print_form_header('attachment', 'doupdatetype');

	if ($vbulletin->GPC['extension'])
	{ // This is an edit
		$type = $db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "attachmenttype
			WHERE extension = '" . $db->escape_string($vbulletin->GPC['extension']) . "'
		");
		if ($type)
		{
			if ($type['mimetype'])
			{
				$type['mimetype'] = implode("\n", unserialize($type['mimetype']));
			}
			construct_hidden_code('extension', $type['extension']);
			print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['attachment_type'], $type['extension'], $type['extension']));
		}
	}
	else
	{
		$type = null;
	}

	if (!$type)
	{
		$type = array('enabled' => 1);
		print_table_header($vbphrase['add_new_extension']);
	}

	print_input_row($vbphrase['extension'], 'type[extension]', $type['extension']);
	print_input_row(construct_phrase($vbphrase['maximum_filesize_dfn']), 'type[size]', $type['size']);
	print_input_row($vbphrase['max_width_dfn'], 'type[width]', $type['width']);
	print_input_row($vbphrase['max_height_dfn'], 'type[height]', $type['height']);
	print_textarea_row($vbphrase['mime_type_dfn'], 'type[mimetype]', $type['mimetype']);

	($hook = vBulletinHook::fetch_hook('admin_attachmenttype')) ? eval($hook) : false;

	$existing = @unserialize($type['contenttypes']);

	print_table_break();
	print_table_header($vbphrase['content_type'], 4);
	print_cells_row(array(
		$vbphrase['product'],
		$vbphrase['location'],
		$vbphrase['new_window'],
		$vbphrase['enabled'],
	), 1, 'tcat');

	$indexed_types = array();
	$collection = new vB_Collection_ContentType();
	$collection->filterAttachable(true);
	foreach ($collection AS $type)
	{
		$value['package'] = $type->getPackageClass();
		$value['class'] = $type->getClass();
		$indexed_types[$type->getID()] = $value;
	}

	foreach ($indexed_types AS $contenttypeid => $content)
	{
		if (!isset($existing["$contenttypeid"]['e']))
		{
			$existing["$contenttypeid"]['e'] = true;
		}

		print_cells_row(array(
			$content['package'],
			$vbphrase['contenttype_' . strtolower($content['package']) . '_' . strtolower($content['class'])],
			"<input type=\"hidden\" name=\"default[$contenttypeid][n]\" value=\"1\" />" .
			"<input type=\"checkbox\" tabindex=\"1\" name=\"contenttype[$contenttypeid][n]\" value=\"1\"" . ($existing["$contenttypeid"]['n'] ? 'checked="checked"' : '') . ' />',
			"<input type=\"hidden\" name=\"default[$contenttypeid][e]\" value=\"1\" />" .
			"<input type=\"checkbox\" tabindex=\"1\" name=\"contenttype[$contenttypeid][e]\" value=\"1\"" . ($existing["$contenttypeid"]['e'] ? 'checked="checked"' : '') . ' />',
		));

	}

	print_submit_row($vbulletin->GPC['extension'] ? $vbphrase['update'] : $vbphrase['save'], '_default_', 4);
}

// ###################### Update File Type ####################
if ($_POST['do'] == 'doupdatetype')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'extension'	  => TYPE_STR,
		'type'        => TYPE_ARRAY,
		'contenttype' => TYPE_ARRAY,
		'default'     => TYPE_ARRAY,
	));

	$vbulletin->GPC['type']['extension'] = preg_replace('#[^a-z0-9_]#i', '', $vbulletin->GPC['type']['extension']);
	$vbulletin->GPC['type']['extension'] = strtolower($vbulletin->GPC['type']['extension']);

	if (empty($vbulletin->GPC['type']['extension']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($vbulletin->GPC['extension'] != $vbulletin->GPC['type']['extension'] AND $test = $db->query_first("SELECT extension FROM " . TABLE_PREFIX . "attachmenttype WHERE extension = '" . $db->escape_string($vbulletin->GPC['type']['extension']) . "'"))
	{
		print_stop_message('name_exists', $vbphrase['filetype'], htmlspecialchars($vbulletin->GPC['type']['extension']));
	}

	if ($vbulletin->GPC['type']['mimetype'])
	{
		$mimetype = explode("\n", $vbulletin->GPC['type']['mimetype']);
		foreach($mimetype AS $index => $value)
		{
			$mimetype["$index"] = trim($value);
		}
	}
	else
	{
		$mimetype = array('Content-type: unknown/unknown');
	}
	$vbulletin->GPC['type']['mimetype'] = serialize($mimetype);

	$contenttypes = array();

	foreach ($vbulletin->GPC['default'] AS $contenttypeid => $contenttype)
	{
		foreach ($contenttype AS $key => $value)
		{
			$contenttypes["$contenttypeid"]["$key"] = intval($vbulletin->GPC['contenttype']["$contenttypeid"]["$key"]);
		}
	}
	$vbulletin->GPC['type']['contenttypes'] = serialize($contenttypes);

	define('CP_REDIRECT', 'attachment.php?do=types');
	if ($vbulletin->GPC['extension'])
	{
		$db->query_write(fetch_query_sql($vbulletin->GPC['type'], 'attachmenttype', 'WHERE extension = \'' . $db->escape_string($vbulletin->GPC['extension']) . '\''));
		build_attachment_permissions();
	}
	else
	{
		/*insert query*/
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "attachmenttype
			(
				extension,
				size,
				height,
				width,
				mimetype,
				contenttypes
			)
			VALUES
			(
				'" . $db->escape_string($vbulletin->GPC['type']['extension']) . "',
				" . intval($vbulletin->GPC['type']['size']) . ",
				" . intval($vbulletin->GPC['type']['height']) . ",
				" . intval($vbulletin->GPC['type']['width']) . ",
				'" . $db->escape_string($vbulletin->GPC['type']['mimetype']) . "',
				'" . $db->escape_string($vbulletin->GPC['type']['contenttype']) . "'
			)
		");

		build_attachment_permissions();
	}

	print_stop_message('saved_attachment_type_x_successfully', $vbulletin->GPC['type']['extension']);
}

// ###################### Remove File Type ####################
if ($_REQUEST['do'] == 'removetype')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'extension' => TYPE_STR
	));

	print_form_header('attachment', 'killtype', 0, 1, '', '75%');
	construct_hidden_code('extension', $vbulletin->GPC['extension']);
	print_table_header(construct_phrase($vbphrase['confirm_deletion_of_attachment_type_x'], $vbulletin->GPC['extension']));
	print_description_row("
		<blockquote><br />".
		construct_phrase($vbphrase['are_you_sure_you_want_to_delete_the_attachment_type_x'], $vbulletin->GPC['extension'])."
		<br /></blockquote>\n\t");
	print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);
}

// ###################### Kill File Type ####################
if ($_POST['do'] == 'killtype')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'extension' => TYPE_STR
	));

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "attachmenttype
		WHERE extension = '" . $db->escape_string($vbulletin->GPC['extension']) . "'
	");
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "attachmentpermission
		WHERE extension = '" . $db->escape_string($vbulletin->GPC['extension']) . "'
	");

	build_attachment_permissions();

	define('CP_REDIRECT', 'attachment.php?do=types');
	print_stop_message('deleted_attachment_type_successfully');
}


print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 62098 $
|| ####################################################################
\*======================================================================*/
?>
