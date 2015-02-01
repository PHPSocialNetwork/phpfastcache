<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Blog 4.2.1 - Licence Number VBF02D260D
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
define('CVS_REVISION', '$RCSfile$ - $Revision: 63620 $');
define('NOZIP', 1);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// TODO: break these groups down into being called only when needed :: see the stats check following this array assignment
$phrasegroups = array(
	'vbblogcat',
	'vbblogadmin',
	'vbblogglobal',
	'cppermission',
	'moderator',
	'maintenance',
	'user',
	'cpuser',
);
if ($_GET['do'] == 'stats')
{
	$phrasegroups[] = 'stats';
}
$specialtemplates = array('blogcategorycache');

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/blog_functions.php');
require_once(DIR . '/includes/blog_adminfunctions.php');
require_once(DIR . '/includes/blog_functions_category.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canblog'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################

$vbulletin->input->clean_array_gpc('r', array(
));

/*
log_admin_action(iif($vbulletin->GPC['moderatorid'] != 0, " moderator id = " . $vbulletin->GPC['moderatorid'],
					iif($vbulletin->GPC['calendarid'] != 0, "calendar id = " . $vbulletin->GPC['calendarid'], '')));

*/
// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'counters';
}

switch ($_REQUEST['do'])
{
	case 'updateattachments':
		print_cp_header($vbphrase['convert_blog_attachments']);
		break;

	case 'rebuildthumbs':
	case 'emptycache':
	case 'counters':
		print_cp_header($vbphrase['maintenance']);
		break;

	case 'listcp':
	case 'editcp':
		print_cp_header($vbphrase['blog_category_permissions']);
		break;

	case 'list':
	case 'dolist':
	case 'deleteentry':
		print_cp_header($vbphrase['view_blog_entries']);
		break;

	case 'listfe':
	case 'updatefe':
	case 'modifyfe':
		print_cp_header($vbphrase['blog_featured_entries']);
		break;

	case 'listcat':
	case 'updatecat':
	case 'modifycat':
		print_cp_header($vbphrase['blog_categories']);
		break;

	case 'stats':
	case 'dostats':
		print_cp_header($vbphrase['blog_stats']);
		break;

	case 'usercss':
		print_cp_header($vbphrase['user_customizations']);
		break;

	default:
		print_cp_header($vbphrase['blog_moderators']);
}

$vbulletin->input->clean_array_gpc('r', array(
	'perpage' => TYPE_UINT,
	'startat' => TYPE_UINT
));

// ##################### Start Index ###################################
if ($_REQUEST['do'] == 'counters')
{
	// Check if we have the "old" attachment system in place still
	require_once(DIR . '/includes/class_dbalter.php');
	$db_alter = new vB_Database_Alter_MySQL($db);
	if ($db_alter->fetch_table_info('blog_attachment'))
	{
		print_form_header('blog_admin', 'updateattachments');
		print_table_header($vbphrase['convert_blog_attachments'], 2, 0);
		print_description_row($vbphrase['convert_blog_attachments_desc']);
		print_input_row($vbphrase['number_of_attachments_to_process_per_cycle'], 'perpage', 25);
		print_submit_row($vbphrase['convert_blog_attachments']);
	}

	print_form_header('blog_admin', 'updateentry');
	print_table_header($vbphrase['rebuild_blog_entry_information'], 2, 0);
	print_yes_no_row($vbphrase['remove_cantview_categories'], 'cantview', 1);
	print_yes_no_row($vbphrase['remove_cantpost_categories'], 'cantpost', 1);
	print_input_row($vbphrase['number_of_posts_to_process_per_cycle'], 'perpage', 2000);
	print_submit_row($vbphrase['rebuild_blog_entry_information']);

	print_form_header('blog_admin', 'updateuser');
	print_table_header($vbphrase['rebuild_blog_user_information'], 2, 0);
	print_input_row($vbphrase['number_of_users_to_process_per_cycle'], 'perpage', 2000);
	print_submit_row($vbphrase['rebuild_blog_user_information']);

	print_form_header('blog_admin', 'rebuildcounters');
	print_table_header($vbphrase['rebuild_blog_counters']);
	print_description_row($vbphrase['rebuild_blog_metadata']);
	print_submit_row($vbphrase['rebuild_blog_counters']);

	print_form_header('blog_admin', 'emptycache');
	print_table_header($vbphrase['clear_parsed_text_cache']);
	print_description_row($vbphrase['clear_cached_text_entries']);
	print_submit_row($vbphrase['clear_parsed_text_cache']);

	print_form_header('blog_admin', 'rebuildprofilepic');
	print_table_header($vbphrase['rebuild_profile_picture_dimensions']);
	print_input_row($vbphrase['number_of_pictures_to_process_per_cycle'], 'perpage', 25);
	print_submit_row($vbphrase['rebuild_profile_picture_dimensions']);
}

// ##################### Start Update Attachments ###################################
if ($_REQUEST['do'] == 'updateattachments')
{
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 25;
	}

	// Check if we have the "old" attachment system in place still
	require_once(DIR . '/includes/functions_file.php');
	require_once(DIR . '/includes/class_dbalter.php');
	$db_alter = new vB_Database_Alter_MySQL($db);
	$continue = $db_alter->fetch_table_info('blog_attachment');
	if (!$continue)
	{
		define('CP_REDIRECT', 'blog_admin.php');
		print_stop_message('updated_blog_attachments_successfully');
	}

	$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];
	echo '<p>' . $vbphrase['updating_blog_attachments'] . '</p>';

	$attachments = $db->query_read("
		SELECT ba.*, bt.pagetext, bt.blogtextid
		FROM " . TABLE_PREFIX . "blog_attachment AS ba
		LEFT JOIN " . TABLE_PREFIX . "blog AS blog ON (ba.blogid = blog.blogid)
		LEFT JOIN " . TABLE_PREFIX . "blog_text AS bt ON (blog.firstblogtextid = bt.blogtextid)
		WHERE
			ba.attachmentid >= " . $vbulletin->GPC['startat'] . "
		ORDER BY ba.attachmentid
		LIMIT " . $vbulletin->GPC['perpage']
	);
	$blog_text_cache = array();
	while ($attachment = $db->fetch_array($attachments))
	{
		echo construct_phrase($vbphrase['processing_x'], $attachment['attachmentid']) . "<br />\n";
		vbflush();

		if ($vbulletin->options['blogattachfile'])
		{
			$attachthumbpath = fetch_attachment_path($attachment['userid'], $attachment['attachmentid'], true, $vbulletin->options['blogattachpath']);
			$attachpath = fetch_attachment_path($attachment['userid'], $attachment['attachmentid'], false, $vbulletin->options['blogattachpath']);

			$thumbnail = @file_get_contents($attachthumbpath);
			$filedata = @file_get_contents($attachpath);
		}
		else
		{
			$thumbnail =& $attachment['thumbnail'];
			$filedata =& $attachment['filedata'];
		}

		$dataman =& datamanager_init('AttachmentFiledata', $vbulletin, ERRTYPE_STANDARD, 'attachment');
		$dataman->set('contenttypeid', vB_Types::instance()->getContentTypeID('vBBlog_BlogEntry'));
		$dataman->set('contentid', $attachment['blogid']);
		$dataman->set('userid', $attachment['userid']);
		$dataman->set('filename', $attachment['filename']);
		$dataman->set('dateline', $attachment['dateline']);
		$dataman->set('thumbnail_dateline', $attachment['thumbnail_dateline']);
		$dataman->set('counter', $attachment['counter']);
		$dataman->set('state', $attachment['visible']);
		$dataman->setr('filedata', $filedata);
		$dataman->setr('thumbnail', $thumbnail);
		if ($attachmentid = $dataman->save())
		{
			$db->query_write("DELETE FROM " . TABLE_PREFIX . "blog_attachment WHERE attachmentid = $attachment[attachmentid]");
			if ($vbulletin->options['blogattachfile'])
			{
				@unlink($attachthumbpath);
				@unlink($attachpath);
			}

			// if text was changed by previous iteration, then update text from cache
			if (array_key_exists($attachment['blogid'], $blog_text_cache))
			{
				$attachment['pagetext'] = $blog_text_cache[$attachment['blogid']];
			}

			if (($newpagetext = preg_replace('#\[attach(.*)\]' . $attachment['attachmentid'] . '\[/attach\]#siU', '[attach\\1]' . $attachmentid . '[/attach]', $attachment['pagetext'])) != $attachment['pagetext'])
			{
				// update text cache
				$blog_text_cache[$attachment['blogid']] = $newpagetext;
				// direct query, skip all checking nonsense
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "blog_text
					SET pagetext = '" . $db->escape_string($newpagetext) . "'
					WHERE blogtextid = $attachment[blogtextid]
				");
			}

			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "blog_attachmentlegacy
					(oldattachmentid, newattachmentid)
				VALUES
					($attachment[attachmentid], $attachmentid)
			");
		}

		$finishat = ($attachment['attachmentid'] > $finishat ? $attachment['attachmentid'] : $finishat);
	}

	$finishat++;

	if ($checkmore = $db->query_first("SELECT attachmentid FROM " . TABLE_PREFIX . "blog_attachment WHERE attachmentid >= $finishat LIMIT 1"))
	{
		print_cp_redirect("blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=updateattachments&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
		echo "<p><a href=\"blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=updateattachments&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		// Drop table if it truly is empty!
		if (!$db->query_first("SELECT attachmentid FROM " . TABLE_PREFIX . "blog_attachment"))
		{
			$db->query_write("DROP TABLE IF EXISTS " . TABLE_PREFIX . "blog_attachment");
			$db->query_write("DROP TABLE IF EXISTS " . TABLE_PREFIX . "blog_attachmentviews");
		}
		define('CP_REDIRECT', 'blog_admin.php');
		print_stop_message('updated_blog_attachments_successfully');
	}
}

// ##################### Start Update Entry ###################################
if ($_REQUEST['do'] == 'updateentry')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'cantview' => TYPE_BOOL,
		'cantpost' => TYPE_BOOL
	));

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 2000;
	}

	$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

	echo '<p>' . $vbphrase['updating_blog_entries'] . '</p>';

	$blogs = $db->query_read("
		SELECT blogid, user.userid
		FROM " . TABLE_PREFIX . "blog AS blog
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (blog.userid = user.userid)
		WHERE
			blogid >= " . $vbulletin->GPC['startat'] . "
				AND
			blogid < $finishat
		ORDER BY blogid
	");
	while ($blog = $db->fetch_array($blogs))
	{
		if ($blog['userid'])
		{
			build_blog_entry_counters($blog['blogid'], $vbulletin->GPC['cantview'], $vbulletin->GPC['cantpost']);
		}
		else
		{
			$bloginfo = array('blogid' => $blog['blogid']);
			$blogman =& datamanager_init('Blog', $vbulletin, ERRTYPE_SILENT, 'blog');
			$blogman->set_existing($bloginfo);
			$blogman->set_info('hard_delete', true);
			$blogman->delete();
			unset($blogman);
		}
		echo construct_phrase($vbphrase['processing_x'], $blog['blogid']) . "<br />\n";
		vbflush();
	}

	if ($checkmore = $db->query_first("SELECT blogid FROM " . TABLE_PREFIX . "blog WHERE blogid >= $finishat LIMIT 1"))
	{
		$args = array(
			"do=updateentry",
			"startat=$finishat",
			"pp=" . $vbulletin->GPC['perpage']
		);
		if ($vbulletin->GPC['cantview'])
		{
			$args[] = "cantview=1";
		}
		if ($vbulletin->GPC['cantpost'])
		{
			$args[] = "cantpost=1";
		}
		print_cp_redirect("blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . implode('&', $args));
		echo "<p><a href=\"blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . implode('&amp;', $args) . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'blog_admin.php');
		print_stop_message('updated_blog_entries_successfully');
	}
}

// ##################### Start Update User ###################################
if ($_REQUEST['do'] == 'updateuser')
{
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 2000;
	}

	$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

	echo '<p>' . $vbphrase['updating_blog_users'] . '</p>';

	$users = $db->query_read("
		SELECT userid
		FROM " . TABLE_PREFIX . "user AS user
		INNER JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (user.userid = blog_user.bloguserid)
		WHERE userid >= " . $vbulletin->GPC['startat'] . "
		ORDER BY userid
		LIMIT " . $vbulletin->GPC['perpage']
	);
	while ($user = $db->fetch_array($users))
	{
		build_category_genealogy($user['userid']);
		build_blog_user_counters($user['userid']);
		echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
		vbflush();

		$finishat = ($user['userid'] > $finishat ? $user['userid'] : $finishat);
	}

	$finishat++;

	if ($checkmore = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid >= $finishat LIMIT 1"))
	{
		print_cp_redirect("blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=updateuser&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
		echo "<p><a href=\"blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=updateuser&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		build_category_genealogy(0);
		define('CP_REDIRECT', 'blog_admin.php');
		print_stop_message('updated_blog_users_successfully');
	}
}

// ##################### Start Empty Cache ###################################
if ($_POST['do'] == 'emptycache')
{
	$db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "blog_textparsed");
	define('CP_REDIRECT', 'blog_admin.php');
	print_stop_message('blog_cache_emptied');
}

// ##################### Start Rebuild Counters ###################################
if ($_POST['do'] == 'rebuildcounters')
{
	$mysqlversion = $db->query_first("SELECT version() AS version");
	define('MYSQL_VERSION', $mysqlversion['version']);
	$enginetype = (version_compare(MYSQL_VERSION, '4.0.18', '<')) ? 'TYPE' : 'ENGINE';
	$tabletype = (version_compare(MYSQL_VERSION, '4.1', '<')) ? 'HEAP' : 'MEMORY';

	// rebuild trackback counters
	$tablename = 'blog_trackback_count' . $vbulletin->userinfo['userid'];

	$vbulletin->db->query_write("DROP TABLE IF EXISTS " . TABLE_PREFIX . "$tablename");
	$db->query_write("
	CREATE TABLE " . TABLE_PREFIX . "$tablename
		(
			bid INT UNSIGNED NOT NULL DEFAULT '0',
			bstate ENUM('moderation','visible') NOT NULL DEFAULT 'visible',
			btotal INT UNSIGNED NOT NULL DEFAULT '0',
			KEY blogid (bid, state)
		) $enginetype = $tabletype
		SELECT blog_trackback.blogid, blog_trackback.state AS state, COUNT(*) AS total
		FROM " . TABLE_PREFIX . "blog_trackback AS blog_trackback
		INNER JOIN " . TABLE_PREFIX . "blog AS blog USING (blogid)
		GROUP BY state, blog_trackback.blogid
	");

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "blog AS blog, " . TABLE_PREFIX . "$tablename AS blog_trackback_count
		SET blog.trackback_visible = blog_trackback_count.btotal
		WHERE blog.blogid = blog_trackback_count.bid AND blog_trackback_count.bstate = 'visible'
	");

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "blog AS blog, " . TABLE_PREFIX . "$tablename AS blog_trackback_count
		SET blog.trackback_moderation = blog_trackback_count.btotal
		WHERE blog.blogid = blog_trackback_count.bid AND blog_trackback_count.bstate = 'moderation'
	");
	$vbulletin->db->query_write("DROP TABLE IF EXISTS " . TABLE_PREFIX . "$tablename");

	build_blog_stats();

	define('CP_REDIRECT', 'blog_admin.php');
	print_stop_message('blog_counters_rebuilt');
}

// ##################### Show Moderators ##################################
if ($_REQUEST['do'] == 'moderators')
{
	if (!can_administer('canblogpermissions'))
	{
		print_cp_no_permission();
	}
	print_form_header('', '');
	print_table_header($vbphrase['last_online'] . ' - ' . $vbphrase['color_key']);
	print_description_row('
		<div class="darkbg" style="border: 2px inset"><ul class="darkbg">
		<li class="modtoday">' . $vbphrase['today'] . '</li>
		<li class="modyesterday">' . $vbphrase['yesterday'] . '</li>
		<li class="modlasttendays">' . construct_phrase($vbphrase['within_the_last_x_days'], '10') . '</li>
		<li class="modsincetendays">' . construct_phrase($vbphrase['more_than_x_days_ago'], '10') . '</li>
		<li class="modsincethirtydays"> ' . construct_phrase($vbphrase['more_than_x_days_ago'], '30') . '</li>
		</ul></div>
	');
	print_table_footer();

	// get the timestamp for the beginning of today, according to bbuserinfo's timezone
	require_once(DIR . '/includes/functions_misc.php');
	$unixtoday = vbmktime(0, 0, 0, vbdate('m', TIMENOW, false, false), vbdate('d', TIMENOW, false, false), vbdate('Y', TIMENOW, false, false));

	print_form_header('', '');
	print_table_header($vbphrase['super_moderators']);
	echo "<tr valign=\"top\">\n\t<td class=\"" . fetch_row_bgclass() . "\" colspan=\"2\">";
	echo "<div class=\"darkbg\" style=\"padding: 4px; border: 2px inset; text-align: " . vB_Template_Runtime::fetchStyleVar('left') . "\"><ul>";

	$countmods = 0;
	$supergroups = $db->query_read("
		SELECT user.*, usergroup.usergroupid
		FROM " . TABLE_PREFIX . "usergroup AS usergroup
		INNER JOIN " . TABLE_PREFIX . "user AS user ON(user.usergroupid = usergroup.usergroupid OR FIND_IN_SET(usergroup.usergroupid, user.membergroupids))
		WHERE (usergroup.adminpermissions & " . $vbulletin->bf_ugp_adminpermissions['ismoderator'] . ")
		GROUP BY user.userid
		ORDER BY user.username
	");
	if ($db->num_rows($supergroups))
	{
		while ($supergroup = $db->fetch_array($supergroups))
		{
			$countmods++;
			if ($supergroup['lastactivity'] >= $unixtoday)
			{
				$onlinecolor = 'modtoday';
			}
			else if ($supergroup['lastactivity'] >= ($unixtoday - 86400))
			{
				$onlinecolor = 'modyesterday';
			}
			else if ($supergroup['lastactivity'] >= ($unixtoday - 864000))
			{
				$onlinecolor = 'modlasttendays';
			}
			else if ($supergroup['lastactivity'] >= ($unixtoday - 2592000))
			{
				$onlinecolor = 'modsincetendays';
			}
			else
			{
				$onlinecolor = 'modsincethirtydays';
			}

			$lastonline = vbdate($vbulletin->options['dateformat'] . ' ' .$vbulletin->options['timeformat'], $supergroup['lastactivity']);
			echo "\n\t<li><b><a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&u=$supergroup[userid]\">$supergroup[username]</a></b><span class=\"smallfont\"> (" . construct_link_code($vbphrase['edit_permissions'], "blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=editglobal&amp;u=$supergroup[userid]") . ") - " . $vbphrase['last_online'] . " <span class=\"$onlinecolor\">" . $lastonline . "</span></span></li>\n";
		}
	}
	else
	{
		echo $vbphrase['there_are_no_moderators'];
	}
	echo "</ul></div>\n";
	echo "</td>\n</tr>\n";

	if ($countmods)
	{
		print_table_footer(1, $vbphrase['total'] . ": <b>$countmods</b>");
	}
	else
	{
		print_table_footer();
	}

	print_form_header('', '');
	print_table_header($vbphrase['moderators']);
	echo "<tr valign=\"top\">\n\t<td class=\"" . fetch_row_bgclass() . "\" colspan=\"2\">";
	echo "<div class=\"darkbg\" style=\"padding: 4px; border: 2px inset; text-align: " . vB_Template_Runtime::fetchStyleVar('left') . "\">";

	$countmods = 0;
	$moderators = $db->query_read("
		SELECT blog_moderator.blogmoderatorid, user.userid, user.username, user.lastactivity
		FROM " . TABLE_PREFIX . "blog_moderator AS blog_moderator
		INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog_moderator.userid)
		WHERE blog_moderator.type = 'normal'
		ORDER BY user.username
	");
	if ($db->num_rows($moderators))
	{
		while ($moderator = $db->fetch_array($moderators))
		{
			if ($countmods++ != 0)
			{
				echo "\t\t</ul>\n\t\t</ul>\n\t</li>\n\t</ul>\n";
			}

			if ($moderator['lastactivity'] >= $unixtoday)
			{
				$onlinecolor = 'modtoday';
			}
			else if ($moderator['lastactivity'] >= ($unixtoday - 86400))
			{
				$onlinecolor = 'modyesterday';
			}
			else if ($moderator['lastactivity'] >= ($unixtoday - 864000))
			{
				$onlinecolor = 'modlasttendays';
			}
			else if ($moderator['lastactivity'] >= ($unixtoday - 2592000))
			{
				$onlinecolor = 'modsincetendays';
			}
			else
			{
				$onlinecolor = 'modsincethirtydays';
			}
			$lastonline = vbdate($vbulletin->options['dateformat'] . ' ' .$vbulletin->options['timeformat'], $moderator['lastactivity']);
			echo "\n\t<ul>\n\t<li><b><a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$moderator[userid]&amp;redir=showlist\">$moderator[username]</a></b><span class=\"smallfont\"> - " . $vbphrase['last_online'] . " <span class=\"$onlinecolor\">" . $lastonline . "</span></span>\n";
			echo " <span class=\"smallfont\">(" .
				construct_link_code($vbphrase['edit'], "blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=editmod&blogmoderatorid=$moderator[blogmoderatorid]") .
				construct_link_code($vbphrase['remove'], "blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=removemod&blogmoderatorid=$moderator[blogmoderatorid]") .
			")</span>";
		}
		echo "\t\t</ul>\n\t\t</ul>\n\t</li>\n\t</ul>\n";
	}
	else
	{
		echo $vbphrase['there_are_no_moderators'];
	}
	echo "</div>\n";
	echo "</td>\n</tr>\n";

	if ($countmods)
	{
		print_table_footer(1, $vbphrase['moderators'] . ": <b>$countmods</b>");
	}
	else
	{
		print_table_footer();
	}

	print_form_header('blog_admin', 'addmod');
	print_table_header('<input type="submit" class="button" value="' . $vbphrase['add_new_moderator'] . '" style="font:bold 11px tahoma" />');
	print_table_footer();

}

// ##################### Start Add/Edit Moderator ##########
if ($_REQUEST['do'] == 'addmod' OR $_REQUEST['do'] == 'editmod' OR $_REQUEST['do'] == 'editglobal')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blogmoderatorid'	=> TYPE_INT,
		'userid'          => TYPE_UINT,
	));

	if (!can_administer('canblogpermissions'))
	{
		print_cp_no_permission();
	}

	require_once(DIR . '/includes/class_bitfield_builder.php');
	if (vB_Bitfield_Builder::build(false) !== false)
	{
		$myobj =& vB_Bitfield_Builder::init();
		if (sizeof($myobj->data['misc']['vbblogmoderatorpermissions']) != sizeof($vbulletin->bf_misc_vbblogmoderatorpermissions))
		{
			$myobj->save($db);
			define('CP_REDIRECT', $vbulletin->scriptpath);
			print_stop_message('rebuilt_bitfields_successfully');
		}
	}
	else
	{
		echo "<strong>error</strong>\n";
		print_r(vB_Bitfield_Builder::fetch_errors());
	}

	if ($_REQUEST['do'] == 'editglobal')
	{
		$moderator = $db->query_first("
			SELECT user.username, user.userid,
			bm.permissions, bm.blogmoderatorid
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "blog_moderator AS bm ON (bm.userid = user.userid AND bm.type = 'super')
			WHERE user.userid = " . $vbulletin->GPC['userid']
		);

		print_form_header('blog_admin', 'updatemod');
		construct_hidden_code('type', 'super');
		construct_hidden_code('modusername', $moderator['username'], false);
		$username = $moderator['username'];

		if (empty($moderator['blogmoderatorid']))
		{
			$moderator = array();
			foreach ($myobj->data['misc']['vbblogmoderatorpermissions'] AS $permission => $option)
			{
				$moderator["$permission"] = true;
			}

			// this user doesn't have a record for super mod permissions, which is equivalent to having them all
			$globalperms = array_sum($vbulletin->bf_misc_vbblogmoderatorpermissions);
			$moderator = convert_bits_to_array($globalperms, $vbulletin->bf_misc_vbblogmoderatorpermissions, 1);
			$moderator['username'] = $username;
		}
		else
		{
			construct_hidden_code('blogmoderatorid', $moderator['blogmoderatorid']);
			$perms = convert_bits_to_array($moderator['permissions'], $vbulletin->bf_misc_vbblogmoderatorpermissions, 1);
			$moderator = array_merge($perms, $moderator);
		}

		print_table_header($vbphrase['super_moderator_permissions'] . ' - <span class="normal">' . $moderator['username'] . '</span>');
	}
	else if (empty($vbulletin->GPC['blogmoderatorid']))
	{
		// add moderator - set default values
		$moderator = array();
		foreach ($myobj->data['misc']['vbblogmoderatorpermissions'] AS $permission => $option)
		{
			$moderator["$permission"] = $option['default'] ? 1 : 0;
		}

		print_form_header('blog_admin', 'updatemod');
		print_table_header($vbphrase['add_new_moderator_to_vbulletin_blog']);
		construct_hidden_code('type', 'normal');
	}
	else
	{
		// edit moderator - query moderator
		$moderator = $db->query_first("
			SELECT blogmoderatorid, bm.userid, permissions, user.username, bm.type
			FROM " . TABLE_PREFIX . "blog_moderator AS bm
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = bm.userid)
			WHERE blogmoderatorid = " . $vbulletin->GPC['blogmoderatorid']
		);

		$perms = convert_bits_to_array($moderator['permissions'], $vbulletin->bf_misc_vbblogmoderatorpermissions, 1);
		$moderator = array_merge($perms, $moderator);

		// delete link
		print_form_header('blog_admin', 'removemod');
		construct_hidden_code('blogmoderatorid', $vbulletin->GPC['blogmoderatorid']);
		construct_hidden_code('type', 'normal');
		print_table_header($vbphrase['if_you_would_like_to_remove_this_moderator'] . ' &nbsp; &nbsp; <input type="submit" class="button" value="' . $vbphrase['delete_moderator'] . '" style="font:bold 11px tahoma" />');
		print_table_footer();

		print_form_header('blog_admin', 'updatemod');
		construct_hidden_code('blogmoderatorid', $vbulletin->GPC['blogmoderatorid']);
		print_table_header($vbphrase['edit_moderator']);
	}

	if ($_REQUEST['do'] != 'editglobal')
	{
		if (empty($vbulletin->GPC['blogmoderatorid']))
		{
			print_input_row($vbphrase['moderator_username'], 'modusername', $moderator['username']);
		}
		else
		{
			print_label_row($vbphrase['moderator_username'], '<b>' . $moderator['username'] . '</b>');
		}
		print_table_header($vbphrase['blog_permissions']);
	}

	foreach ($myobj->data['misc']['vbblogmoderatorpermissions'] AS $permission => $option)
	{
		print_yes_no_row($vbphrase["$option[phrase]"], 'modperms[' . $permission . ']', $moderator["$permission"]);
	}

	print_submit_row(!empty($vbulletin->GPC['blogmoderatorid']) ? $vbphrase['update'] : $vbphrase['save']);
}

// ###################### Start insert / update moderator #######################
if ($_POST['do'] == 'updatemod')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'modusername'     => TYPE_NOHTML,
		'moderator'       => TYPE_ARRAY,
		'modperms'        => TYPE_ARRAY,
		'blogmoderatorid' => TYPE_UINT,
		'type'            => TYPE_NOHTML,
	));

	$vbulletin->GPC['type'] = ($vbulletin->GPC['type'] == 'super') ? 'super' : 'normal';

	if (!can_administer('canblogpermissions'))
	{
		print_cp_no_permission();
	}

	require_once(DIR . '/includes/functions_misc.php');
	$vbulletin->GPC['moderator']['permissions'] = convert_array_to_bits($vbulletin->GPC['modperms'], $vbulletin->bf_misc_vbblogmoderatorpermissions, 1);
	if ($vbulletin->GPC['blogmoderatorid'])
	{ // update
		$db->query_write(fetch_query_sql($vbulletin->GPC['moderator'], 'blog_moderator', "WHERE blogmoderatorid=" . $vbulletin->GPC['blogmoderatorid']));

		define('CP_REDIRECT', 'blog_admin.php?do=moderators');
		print_stop_message('saved_moderator_x_successfully', $vbulletin->GPC['modusername']);
	}
	else
	{ // insert
		if ($userinfo = $db->query_first("
			SELECT user.userid, bloguserid, blog_moderator.userid AS bmuserid
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (user.userid = blog_user.bloguserid)
			LEFT JOIN " . TABLE_PREFIX . "blog_moderator AS blog_moderator ON (user.userid = blog_moderator.userid AND type = '" . $db->escape_string($vbulletin->GPC['type']) . "')
			WHERE username = '" . $db->escape_string($vbulletin->GPC['modusername']) . "'"
		))
		{
			if ($userinfo['bmuserid'])
			{
				print_stop_message('user_already_moderator');
			}

			$vbulletin->GPC['moderator']['userid'] = $userinfo['userid'];
			$vbulletin->GPC['moderator']['type'] = $vbulletin->GPC['type'];
			$db->query_write(fetch_query_sql($vbulletin->GPC['moderator'], 'blog_moderator'));

			if ($vbulletin->GPC['type'] == 'normal')
			{
				$dataman =& datamanager_init('Blog_User', $vbulletin, ERRTYPE_CP);
				if ($userinfo['bloguserid'])
				{
					$dataman->set_existing($userinfo);
				}
				else
				{
					$dataman->set('bloguserid', $userinfo['userid']);
				}

				$dataman->set('isblogmoderator', 1);
				$dataman->save();
			}
		}
		else
		{
			print_stop_message('no_users_matched_your_query');
		}

		define('CP_REDIRECT', 'blog_admin.php?do=moderators');
		print_stop_message('saved_moderator_x_successfully', $vbulletin->GPC['modusername']);
	}
}

// ###################### Start Remove moderator #######################
if ($_REQUEST['do'] == 'removemod')
{
	$vbulletin->input->clean_array_gpc('r', array('blogmoderatorid' => TYPE_UINT));

	if (!can_administer('canblogpermissions'))
	{
		print_cp_no_permission();
	}

	print_delete_confirmation('blog_moderator', $vbulletin->GPC['blogmoderatorid'], 'blog_admin', 'killmod', 'moderator');
}

// ###################### Start Kill moderator #######################
$vbulletin->input->clean_array_gpc('p', array('blogmoderatorid' => TYPE_UINT));

if ($_POST['do'] == 'killmod')
{

	if (!can_administer('canblogpermissions'))
	{
		print_cp_no_permission();
	}

	$getuserid = $db->query_first("
		SELECT user.userid, usergroupid
		FROM " . TABLE_PREFIX . "blog_moderator AS blog_moderator
		LEFT JOIN " . TABLE_PREFIX . "user AS user USING (userid)
		WHERE blogmoderatorid = " . $vbulletin->GPC['blogmoderatorid']
	);
	if (!$getuserid)
	{
		print_stop_message('user_no_longer_moderator');
	}
	else
	{
		$userinfo = array('bloguserid' => $getuserid['userid']);
		$dataman =& datamanager_init('Blog_User', $vbulletin, ERRTYPE_SILENT);
		$dataman->set_existing($userinfo);
		$dataman->set('isblogmoderator', 0);
		$dataman->save();

		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_moderator
			WHERE blogmoderatorid = " . $vbulletin->GPC['blogmoderatorid']
		);

		define('CP_REDIRECT', 'blog_admin.php?do=moderators');
		print_stop_message('deleted_moderator_successfully');
	}
}

// ##################### Start List / Do list ###################################
if ($_REQUEST['do'] == 'list' OR $_REQUEST['do'] == 'dolist')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'user'              => TYPE_NOHTML,
		'userid'            => TYPE_UINT,
		'pagenumber'        => TYPE_UINT,
		'perpage'           => TYPE_UINT,
		'orderby'           => TYPE_NOHTML,
		'start'             => TYPE_ARRAY_UINT,
		'end'               => TYPE_ARRAY_UINT,
		'startstamp'        => TYPE_UINT,
		'endstamp'          => TYPE_UINT,
		'status'            => TYPE_NOHTML,
	));

	$vbulletin->GPC['start'] = iif($vbulletin->GPC['startstamp'], $vbulletin->GPC['startstamp'], $vbulletin->GPC['start']);
	$vbulletin->GPC['end'] = iif($vbulletin->GPC['endstamp'], $vbulletin->GPC['endstamp'], $vbulletin->GPC['end']);

	if ($userinfo = verify_id('user', $vbulletin->GPC['userid'], 0, 1))
	{
		$vbulletin->GPC['user'] = $userinfo['username'];
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

	if (!$vbulletin->GPC['status'])
	{
		$vbulletin->GPC['status'] = 'all';
	}

	$statusoptions = array(
		'all'        => $vbphrase['all_entries'],
		'deleted'    => $vbphrase['deleted_entries'],
		'draft'      => $vbphrase['draft_entries'],
		'moderation' => $vbphrase['moderated_entries'],
		'pending'    => $vbphrase['pending_entries'],
		'visible'    => $vbphrase['visible_entries'],
	);

	print_form_header('blog_admin', 'dolist');
	print_table_header($vbphrase['view_blog_entries']);
	print_input_row($vbphrase['user'], 'user', $vbulletin->GPC['user'], 0);
	print_select_row($vbphrase['status'], 'status', $statusoptions, $vbulletin->GPC['status']);
	print_time_row($vbphrase['start_date'], 'start', $vbulletin->GPC['start'], false);
	print_time_row($vbphrase['end_date'], 'end', $vbulletin->GPC['end'], false);
	print_submit_row($vbphrase['go']);
}

// ###################### Start list #######################
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

	if (!$vbulletin->GPC['userid'] AND $vbulletin->GPC['user'])
	{
		if (!$user = $db->query_first("
			SELECT userid
			FROM " . TABLE_PREFIX . "user
				WHERE username = '" . $db->escape_string($vbulletin->GPC['user']) . "'
		"))
		{
			print_stop_message('could_not_find_user_x', $vbulletin->GPC['user']);
		}
		$vbulletin->GPC['userid'] = $user['userid'];
	}

	$wheresql = array();
	if ($vbulletin->GPC['userid'])
	{
		$wheresql[] = "blog.userid = " . $vbulletin->GPC['userid'];
	}

	if ($vbulletin->GPC['start'])
	{
		$wheresql[] = "dateline >= " . $vbulletin->GPC['start'];
	}
	if ($vbulletin->GPC['end'])
	{
		$wheresql[] = "dateline <= " . $vbulletin->GPC['end'];
	}

	switch($vbulletin->GPC['orderby'])
	{
		case 'title':
			$orderby = 'title ASC';
			break;
		case 'username':
			$orderby = 'username ASC';
			break;
		default:
			$orderby = 'dateline DESC';
			$vbulletin->GPC['orderby'] = '';
	}

	switch ($vbulletin->GPC['status'])
	{
		case 'pending':
			$wheresql[] = "pending = 1";
			break;
		case 'draft':
			$wheresql[] = "state = 'draft'";
			break;
		case 'deleted':
			$wheresql[] = "state = 'deleted'";
			break;
		case 'moderation':
			$wheresql[] = "state = 'moderation'";
			break;
		case 'visible':
			$wheresql[] = "state = 'visible'";
			$wheresql[] = "pending = 0";
			break;
	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 15;
	}

	$totalentries = 0;
	do
	{
		if (!$vbulletin->GPC['pagenumber'])
		{
			$vbulletin->GPC['pagenumber'] = 1;
		}
		$start = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

		$entries = $db->query_read_slave("
			SELECT SQL_CALC_FOUND_ROWS blogid, dateline, title, blog.userid, user.username, state, pending
			FROM " . TABLE_PREFIX . "blog AS blog
			LEFT JOIN " . TABLE_PREFIX . "user AS user USING (userid)
			WHERE " . implode(" AND ", $wheresql) . "
			ORDER BY $orderby
			LIMIT $start, " . $vbulletin->GPC['perpage'] . "
		");
		list($totalentries) = $db->query_first("SELECT FOUND_ROWS()", DBARRAY_NUM);
		if ($start >= $totalentries)
		{
			$vbulletin->GPC['pagenumber'] = ceil($totalentries / $vbulletin->GPC['perpage']);
		}
	}
	while ($start >= $totalentries AND $totalentries);

	$args =
		 '&status=' . $vbulletin->GPC['status'] .
		 '&u=' . $vbulletin->GPC['userid'] .
		 '&startstamp=' . $vbulletin->GPC['start'] .
		 '&endstamp=' . $vbulletin->GPC['end'] .
		 '&pp=' . $vbulletin->GPC['perpage'] .
		 '&page=' . $vbulletin->GPC['pagenumber'] .
		 '&orderby=';


	$totalpages = ceil($totalentries / $vbulletin->GPC['perpage']);

	if ($db->num_rows($entries))
	{
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" tabindex=\"1\" value=\"&laquo; " . $vbphrase['first_page'] . "\" onclick=\"window.location='blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . $vbulletin->GPC['orderby'] . "&page=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" tabindex=\"1\" value=\"&lt; " . $vbphrase['prev_page'] . "\" onclick=\"window.location='blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . $vbulletin->GPC['orderby'] . "&page=$prv'\">";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['next_page'] . " &gt;\" onclick=\"window.location='blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . $vbulletin->GPC['orderby'] . "&page=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['last_page'] . " &raquo;\" onclick=\"window.location='blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . $vbulletin->GPC['orderby'] . "&page=$totalpages'\">";
		}

		print_form_header('blog_admin', 'remove');
		print_table_header(construct_phrase($vbphrase['blog_entry_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($totalentries)), 5);

		$headings = array();
		$headings[] = "<a href=\"blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . "username\" title=\"" . $vbphrase['order_by_username'] . "\">" . $vbphrase['user_name'] . "</a>";
		$headings[] = "<a href=\"blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . "title\" title=\"" . $vbphrase['order_by_title'] . "\">" . $vbphrase['title'] . "</a>";
		$headings[] = "<a href=\"blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=dolist" . $args . "\" title=\"" . $vbphrase['order_by_date'] . "\">" . $vbphrase['date'] . "</a>";
		$headings[] = $vbphrase['type'];
		$headings[] = $vbphrase['controls'];
		print_cells_row($headings, 1);

		while ($entry = $db->fetch_array($entries))
		{
			$cell = array();
			$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$entry[userid]\"><b>$entry[username]</b></a>";
			if ($entry['state'] != 'draft' AND !$entry['pending'])
			{
				$cell[] = "<a href=\"" . fetch_seo_url('entry|bburl', $entry) . "\"><b>$entry[title]</b></a>";
			}
			else
			{
				$cell[] = $entry['title'];
			}
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $entry['dateline']) . '</span>';
			switch($entry['state'])
			{
				case 'visible':
					if ($entry['pending'])
					{
						$cell[] = $vbphrase['pending'];
					}
					else
					{
						$cell[] = $vbphrase['visible'];
					}
					break;
				case 'deleted':
					$cell[] = $vbphrase['deleted'];
					break;
				case 'draft':
					$cell[] = $vbphrase['draft'];
					break;
				case 'moderation':
					$cell[] = $vbphrase['moderated'];
					break;
				default:
					$cell[] = '&nbsp;';
			}

			$cell[] = construct_link_code($vbphrase['delete'], "blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=deleteentry&blogid=$entry[blogid]" . $args . $vbulletin->GPC['orderby'], false, '', true);

			print_cells_row($cell);
		}

		print_table_footer(5, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	}
	else
	{
		print_stop_message('no_matches_found');
	}
}

// ##################### Start Delete Entry ###################################
if ($_REQUEST['do'] == 'deleteentry')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blogid'     => TYPE_UINT,
		'userid'     => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'perpage'    => TYPE_UINT,
		'orderby'    => TYPE_NOHTML,
		'startstamp' => TYPE_UINT,
		'endstamp'   => TYPE_UINT,
		'status'     => TYPE_NOHTML,
	));

	if ($bloginfo = fetch_bloginfo($vbulletin->GPC['blogid'], false, true))
	{
		print_form_header('blog_admin', 'killentry');
		construct_hidden_code('blogid', $vbulletin->GPC['blogid']);
		construct_hidden_code('userid', $vbulletin->GPC['userid']);
		construct_hidden_code('pagenumber', $vbulletin->GPC['pagenumber']);
		construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
		construct_hidden_code('orderby', $vbulletin->GPC['orderby']);
		construct_hidden_code('startstamp', $vbulletin->GPC['startstamp']);
		construct_hidden_code('endstamp', $vbulletin->GPC['endstamp']);
		construct_hidden_code('status', $vbulletin->GPC['status']);
		print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], $bloginfo['title']));
		print_description_row($vbphrase['are_you_sure_that_you_want_to_delete_this_blog_entry']);
		print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
	}
	else
	{
		print_stop_message('no_matches_found');
	}
}

// ##################### Start Kill Entry ###################################
if ($_POST['do'] == 'killentry')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'blogid'     => TYPE_UINT,
		'userid'     => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'perpage'    => TYPE_UINT,
		'orderby'    => TYPE_NOHTML,
		'startstamp' => TYPE_UINT,
		'endstamp'   => TYPE_UINT,
		'status'     => TYPE_NOHTML,
	));

	if ($bloginfo = fetch_bloginfo($vbulletin->GPC['blogid'], false, true))
	{
		$blogman =& datamanager_init('Blog', $vbulletin, ERRTYPE_CP, 'blog');
		$blogman->set_existing($bloginfo);
		$blogman->set_info('hard_delete', true);
		$blogman->delete();
		unset($blogman);
		build_blog_user_counters($bloginfo['userid']);

		$args =
			 '&status=' . $vbulletin->GPC['status'] .
			 '&u=' . $vbulletin->GPC['userid'] .
			 '&startstamp=' . $vbulletin->GPC['startstamp'] .
			 '&endstamp=' . $vbulletin->GPC['endstamp'] .
			 '&pp=' . $vbulletin->GPC['perpage'] .
			 '&page=' . $vbulletin->GPC['pagenumber'] .
			 '&orderby=' . $vbulletin->GPC['orderby'];

		define('CP_REDIRECT', 'blog_admin.php?do=dolist' . $args);
		print_stop_message('deleted_entry_successfully');
	}
	else
	{
		print_stop_message('no_matches_found');
	}
}

// ##################### Start Rebuild Profile Pic ###################################
if ($_REQUEST['do'] == 'rebuildprofilepic')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'      => TYPE_UINT,
		'startat'      => TYPE_UINT,
	));

	@ini_set('memory_limit', -1);

	if ($vbulletin->options['imagetype'] != 'Magick' AND !function_exists('imagetypes'))
	{
		print_stop_message('your_version_no_image_support');
	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 20;
	}

	if (!$vbulletin->GPC['startat'])
	{
		$firstpic = $db->query_first("SELECT MIN(userid) AS min FROM " . TABLE_PREFIX . "customprofilepic WHERE width = 0 OR height = 0");
		$vbulletin->GPC['startat'] = intval($firstpic['min']);
	}

	if ($vbulletin->GPC['startat'])
	{
		$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

		echo '<p>' . construct_phrase($vbphrase['calculating_profile_pic_dimensions'], "blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=rebuildprofilepic&startat=" . $vbulletin->GPC['startat'] . "&pp=" . $vbulletin->GPC['perpage']) . '</p>';

		require_once(DIR . '/includes/class_image.php');
		$image =& vB_Image::fetch_library($vbulletin);

		$pictures = $db->query_read("
			SELECT cpp.userid, cpp.filedata, u.profilepicrevision, u.username
			FROM " . TABLE_PREFIX . "customprofilepic AS cpp
			LEFT JOIN " . TABLE_PREFIX . "user AS u USING (userid)
			WHERE cpp.userid >= " . $vbulletin->GPC['startat'] . "
				AND (cpp.width = 0 OR cpp.height = 0)
			ORDER BY cpp.userid
			LIMIT " . $vbulletin->GPC['perpage'] . "
		");

		while ($picture = $db->fetch_array($pictures))
		{
			if (!$vbulletin->options['usefileavatar'])	// Profilepics are in the database
			{
				if ($vbulletin->options['safeupload'])
				{
					$filename = $vbulletin->options['tmppath'] . '/' . md5(uniqid(microtime()) . $vbulletin->userinfo['userid']);
				}
				else
				{
					$filename = tempnam(ini_get('upload_tmp_dir'), 'vbthumb');
				}
				$filenum = fopen($filename, 'wb');
				fwrite($filenum, $picture['filedata']);
				fclose($filenum);
			}
			else
			{
				$filename = $vbulletin->options['profilepicurl'] . '/profilepic' . $picture['userid'] . '_' . $picture['profilepicrevision'] . '.gif';
			}

			echo construct_phrase($vbphrase['processing_x'], "$vbphrase[profile_picture] : $picture[username] ");

			if (!is_readable($filename) OR !@filesize($filename))
			{
				echo '<b>' . $vbphrase['error_file_missing'] . '</b><br />';
				continue;
			}

			$imageinfo = $image->fetch_image_info($filename);
			if ($imageinfo[0] AND $imageinfo[1])
			{
				$dataman =& datamanager_init('Userpic_Profilepic', $vbulletin, ERRTYPE_SILENT, 'userpic');
				$dataman->set_existing($picture);
				$dataman->set('width', $imageinfo[0]);
				$dataman->set('height', $imageinfo[1]);
				$dataman->save();
				unset($dataman);
			}
			else
			{
				echo $vbphrase['error'];
			}

			// Remove temporary file
			if (!$vbulletin->options['usefileavatar'])
			{
				@unlink($filename);
			}

			echo '<br />';
			vbflush();
			$finishat = ($picture['userid'] > $finishat ? $picture['userid'] : $finishat);
		}

		$finishat++;

		if ($checkmore = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "customprofilepic WHERE userid >= $finishat LIMIT 1"))
		{
			print_cp_redirect("blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=rebuildprofilepic&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
			echo "<p><a href=\"blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=rebuildprofilepic&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
		}
		else
		{
			define('CP_REDIRECT', 'blog_admin.php');
			print_stop_message('updated_profile_pictures_successfully');
		}
	}
	else
	{
		define('CP_REDIRECT', 'blog_admin.php');
		print_stop_message('updated_profile_pictures_successfully');
	}
}

// ##################### Start List Feature Entry ###################################
if ($_REQUEST['do'] == 'listfe')
{
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 20;
	}

	?>
	<script type="text/javascript">
	function js_jump(id, obj)
	{
		task = obj.options[obj.selectedIndex].value;

		switch (task)
		{
			case 'modifyfe': window.location = "blog_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=modifyfe&featureid=" + id; break;
			case 'killfe': window.location = "blog_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=removefe&featureid=" + id; break;
			default: return false; break;
		}
	}
	</script>
	<?php

	$options = array(
		'modifyfe' => $vbphrase['edit'],
		'killfe'   => $vbphrase['delete'],
	);

	$entries = $db->query_read_slave("
		SELECT fe.*, user.username
		FROM " . TABLE_PREFIX . "blog_featured AS fe
		LEFT JOIN " . TABLE_PREFIX . "user AS user USING (userid)
		ORDER BY fe.displayorder
	");

	print_form_header('', '');
	print_table_header($vbphrase['blog_featured_entries']);
	print_label_row($vbphrase['featured_entries_warning']);
	print_table_footer();

	print_form_header('blog_admin', 'doorderfe');
	print_table_header($vbphrase['blog_featured_entries'], 9);

	if ($db->num_rows($entries))
	{
		print_cells_row(array(
			$vbphrase['featured_entry_type'],
			$vbphrase['blogid'],
			$vbphrase['primary_usergroup'],
			$vbphrase['additional_usergroups'],
			$vbphrase['username'],
			$vbphrase['refresh'],
			$vbphrase['entries_from'],
			$vbphrase['display_order'],
			$vbphrase['controls'],
		), 1);

		while ($entry = $db->fetch_array($entries))
		{
			switch($entry['refresh'])
			{
				case 60:    $refresh = $vbphrase['every_1_minute']; break;
				case 600:   $refresh = $vbphrase['every_10_minutes']; break;
				case 1800:  $refresh = $vbphrase['every_30_minutes']; break;
				case 3600:  $refresh = $vbphrase['every_1_hour']; break;
				case 21600: $refresh = $vbphrase['every_6_hours']; break;
				case 43200: $refresh = $vbphrase['every_12_hours']; break;
				case 86400: $refresh = $vbphrase['every_1_day']; break;
			}

			switch($entry['timespan'])
			{
				case 'day':   $timespan = $vbphrase['past_24_hours']; break;
				case 'week':  $timespan = $vbphrase['past_week']; break;
				case 'month': $timespan = $vbphrase['past_month']; break;
				case 'year':  $timespan = $vbphrase['past_year']; break;
				case 'all':   $timespan = $vbphrase['the_beginning']; break;
			}

			if ($entry['type'] == 'specific')
			{
				$refresh = '-';
				$blogid = $entry['blogid'];
				$pusergroup = '-';
				$susergroup = '-';
			}
			else
			{
				$blogid = '-';
				$pusergroup = $entry['pusergroupid'] ? $vbulletin->usergroupcache["$entry[pusergroupid]"]['title'] : '-';
				$susergroup = $entry['susergroupid'] ? $vbulletin->usergroupcache["$entry[susergroupid]"]['title'] : '-';
			}

			if ($entry['type'] != 'random')
			{
				$timespan = '-';
			}

			$username = $entry['username'] ? $entry['username'] : '-';

			print_cells_row(array(
				$vbphrase[$entry['type'] . '_entry'],
				$blogid,
				$pusergroup,
				$susergroup,
				$username,
				$refresh,
				$timespan,
				"<input type=\"text\" class=\"bginput\" tabindex=\"1\" name=\"displayorder[$entry[featureid]]\" value=\"$entry[displayorder]\" size=\"2\" />",
				"<span style=\"white-space:nowrap\"><select name=\"f$entry[featureid]\" onchange=\"js_jump($entry[featureid], this);\" class=\"bginput\">" . construct_select_options($options) . "</select><input type=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_jump($entry[featureid], this.form.f$entry[featureid]);\" class=\"button\" /></span>"
			));
		}
	}

	print_table_footer(9, ($db->num_rows($entries) ? '<input type="submit" class="button" value="' . $vbphrase['save_display_order'] . '" accesskey="s" tabindex="1" />' : '') . construct_button_code($vbphrase['add_new_featured_entry'], "blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=modifyfe"));
}

// ##################### Start Modify Feature Entry ###################################
if ($_REQUEST['do'] == 'modifyfe')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'featureid' => TYPE_UINT,
		'type'      => TYPE_NOHTML,
	));

	if (empty($vbulletin->GPC['featureid']))
	{
		if (empty($vbulletin->GPC['type']))
		{
			$typeoptions = array(
				'random'   => $vbphrase['random_entry'],
				'specific' => $vbphrase['specific_entry'],
				'latest'   => $vbphrase['latest_entry'],
			);

			echo "<p>&nbsp;</p><p>&nbsp;</p>\n";
			print_form_header('blog_admin', 'modifyfe');
			print_table_header($vbphrase['add_new_featured_entry']);
			print_select_row($vbphrase['featured_entry_type'], 'type', $typeoptions);
			print_submit_row($vbphrase['continue'], 0);
			print_cp_footer();
			exit;
		}
		else
		{
			$entry = array(
				'bbcode' => true,
			);
			$type = $vbulletin->GPC['type'];
		}
	}
	else
	{
		$entry = $db->query_first_slave("
			SELECT fe.*, user.username
			FROM " . TABLE_PREFIX . "blog_featured AS fe
			LEFT JOIN " . TABLE_PREFIX . "user AS user USING (userid)
			WHERE featureid = " . $vbulletin->GPC['featureid'] . "
		");

		if (!$entry)
		{
			print_stop_message('invalid_x_specified', 'featureid');
		}

		$type = $entry['type'];
	}

	construct_hidden_code('featureid', $vbulletin->GPC['featureid']);
	construct_hidden_code('type', $type);

	$refreshoptions = array(
		60    => $vbphrase['every_1_minute'],
		600   => $vbphrase['every_10_minutes'],
		1800  => $vbphrase['every_30_minutes'],
		3600  => $vbphrase['every_1_hour'],
		21600 => $vbphrase['every_6_hours'],
		43200 => $vbphrase['every_12_hours'],
		86400 => $vbphrase['every_1_day'],
	);

	$timespanoptions = array(
		'day'   => $vbphrase['past_24_hours'],
		'week'  => $vbphrase['past_week'],
		'month' => $vbphrase['past_month'],
		'year'  => $vbphrase['past_year'],
		'all'   => $vbphrase['the_beginning'],
	);

	print_form_header('blog_admin', 'updatefe');
	print_table_header($vbphrase['blog_featured_entries'] . ' - ' . $vbphrase[$type . '_entry']);

	if ($type == 'specific')
	{
		print_input_row($vbphrase['blogid'], 'blogid', $entry['blogid']);
	}

	if ($type == 'random' OR $type == 'latest')
	{
		$usergroups = array(0 => '');
		foreach($vbulletin->usergroupcache AS $usergroup)
		{
			$usergroups["{$usergroup['usergroupid']}"] = $usergroup['title'];
		}

		print_input_row($vbphrase['username'], 'username', $entry['username']);
		print_select_row($vbphrase['primary_usergroup'], 'pusergroupid', $usergroups, $entry['pusergroupid']);
		print_select_row($vbphrase['additional_usergroups'], 'susergroupid', $usergroups, $entry['susergroupid']);
	}

	if ($type == 'random')
	{
		print_time_row($vbphrase['start_date'], 'start', $entry['start'], false);
		print_time_row($vbphrase['end_date'], 'end', $entry['end'], false);
	}

	if ($type == 'random' OR $type == 'latest')
	{
		print_select_row($vbphrase['refresh'], 'refresh', $refreshoptions, $entry['refresh']);
	}

	if ($type == 'random')
	{
		print_select_row($vbphrase['only_include_entries_from'], 'timespan', $timespanoptions, $entry['timespan']);
	}

	print_yes_no_row($vbphrase['parse_bbcode'], 'bbcode', $entry['bbcode']);
	print_input_row($vbphrase['display_order'], 'displayorder', $entry['displayorder']);

	print_submit_row($vbphrase['save']);
}

// ###################### Start do order #######################
if ($_POST['do'] == 'doorderfe')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'displayorder' => TYPE_ARRAY_UINT
	));

	if (!empty($vbulletin->GPC['displayorder']))
	{
		$entries = $db->query_read_slave("SELECT featureid, displayorder FROM " . TABLE_PREFIX . "blog_featured");
		while ($entry = $db->fetch_array($entries))
		{
			if ($entry['displayorder'] != $vbulletin->GPC['displayorder']["$entry[featureid]"])
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "blog_featured
					SET displayorder = " . $vbulletin->GPC['displayorder']["$entry[featureid]"] . "
					WHERE featureid = $entry[featureid]"
				);
			}
		}
	}

	build_featured_entry_datastore();

	define('CP_REDIRECT', 'blog_admin.php?do=listfe');
	print_stop_message('saved_display_order_successfully');
}

// ##################### Start Update Feature Entry ###################################
if ($_POST['do'] == 'updatefe')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'featureid'    => TYPE_UINT,
		'type'         => TYPE_NOHTML,
		'pusergroupid' => TYPE_UINT,
		'susergroupid' => TYPE_UINT,
		'start'        => TYPE_ARRAY_UINT,
		'end'          => TYPE_ARRAY_UINT,
		'refresh'      => TYPE_UINT,
		'timespan'     => TYPE_STR,
		'blogid'       => TYPE_UINT,
		'username'     => TYPE_NOHTML,
		'displayorder' => TYPE_UINT,
		'bbcode'       => TYPE_BOOL,
	));

	$values = array(
		'blogid'       => 0,
		'userid'       => 0,
		'pusergroupid' => 0,
		'susergroupid' => 0,
		'refresh'      => 3600,
		'start'        => 0,
		'end'          => 0,
		'featureid'    => $vbulletin->GPC['featureid'],
		'displayorder' => $vbulletin->GPC['displayorder'],
		'timespan'     => 'all',
		'bbcode'       => $vbulletin->GPC['bbcode'],
	);

	if ($vbulletin->GPC['featureid'])
	{
		$entry = $db->query_first_slave("
			SELECT type
			FROM " . TABLE_PREFIX . "blog_featured
			WHERE featureid = " . $vbulletin->GPC['featureid'] . "
		");
		if (!$entry)
		{
			print_stop_message('invalid_x_specified', 'featureid');
		}

		$values['type'] = $entry['type'];
	}
	else
	{
		$values['type'] = $vbulletin->GPC['type'];
	}

	if ($values['type'] == 'specific')
	{
		$bloginfo = verify_blog($vbulletin->GPC['blogid'], false);

		if (!$bloginfo)
		{
			print_stop_message('invalid_x_specified', $vbphrase['blogid']);
		}
		else
		{
			$values['blogid'] = $bloginfo['blogid'];
		}
		$values['type'] = 'specific';
	}
	else
	{
		if ($vbulletin->GPC['username'])
		{
			$userinfo = $db->query_first_slave("
				SELECT userid
				FROM " . TABLE_PREFIX . "user
				WHERE username = '" . $db->escape_string($vbulletin->GPC['username']) . "'
			");
			if (!$userinfo)
			{
				print_stop_message('invalid_user_specified');
			}
			else
			{
				$values['userid'] = $userinfo['userid'];
			}
		}
		$values['pusergroupid'] = $vbulletin->GPC['pusergroupid'];
		$values['susergroupid'] = $vbulletin->GPC['susergroupid'];
		$values['refresh'] = $vbulletin->GPC['refresh'];

		if ($values['type'] == 'random')
		{
			if (@checkdate($vbulletin->GPC['start']['month'], $vbulletin->GPC['start']['day'], $vbulletin->GPC['start']['year']))
			{
				$startstamp = mktime(0, 0, 0, $vbulletin->GPC['start']['month'], $vbulletin->GPC['start']['day'], $vbulletin->GPC['start']['year']);
			}
			if (@checkdate($vbulletin->GPC['end']['month'], $vbulletin->GPC['end']['day'], $vbulletin->GPC['end']['year']))
			{
				$endstamp = mktime(23, 59, 59, $vbulletin->GPC['end']['month'], $vbulletin->GPC['end']['day'], $vbulletin->GPC['end']['year']);
			}
			if ($startstamp AND (!$endstamp OR $startstamp < $endstamp))
			{
				$values['start'] = $startstamp;
			}
			if ($endstamp AND (!$startstamp OR $startstamp < $endstamp))
			{
				$values['end'] = $endstamp;
			}
			$values['timespan'] = $vbulletin->GPC['timespan'];
		}
	}

	if ($values['featureid'])
	{
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "blog_featured
			SET
				userid = $values[userid],
				blogid = $values[blogid],
				pusergroupid = $values[pusergroupid],
				susergroupid = $values[susergroupid],
				start = $values[start],
				end = $values[end],
				refresh = '$values[refresh]',
				timespan = '" . $db->escape_string($values['timespan']) . "',
				displayorder = $values[displayorder],
				bbcode = " . intval($values['bbcode']) . "
			WHERE featureid = $values[featureid]
		");
	}
	else
	{
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "blog_featured
				(userid, blogid, pusergroupid, susergroupid, start, end, refresh, timespan, displayorder, type, bbcode)
			VALUES
				(
					$values[userid],
					$values[blogid],
					$values[pusergroupid],
					$values[susergroupid],
					$values[start],
					$values[end],
					'$values[refresh]',
					'" . $db->escape_string($values['timespan']) . "',
					$values[displayorder],
					'" . $db->escape_string($values['type']) . "',
					" . intval($values['bbcode']) . "
				)
		");
	}

	build_featured_entry_datastore();

	define('CP_REDIRECT', 'blog_admin.php?do=listfe');
	print_stop_message('updated_featured_entry_successfully');
}

// ##################### Start Remove Feature Entry ###################################
if ($_REQUEST['do'] == 'removefe')
{
	$vbulletin->input->clean_array_gpc('r', array('featureid' => TYPE_UINT));

	print_delete_confirmation('blog_featured', $vbulletin->GPC['featureid'], 'blog_admin', 'killfe', 'featureid');
}

// ##################### Start Kill Feature Entry ###################################
if ($_POST['do'] == 'killfe')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'featureid' => TYPE_UINT,
	));

	$getfeatureid = $db->query_first_slave("
		SELECT featureid
		FROM " . TABLE_PREFIX . "blog_featured
		WHERE featureid = " . $vbulletin->GPC['featureid']
	);

	if (!$getfeatureid)
	{
		print_stop_message('invalid_x_specified', 'featureid');
	}
	else
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_featured
			WHERE featureid = " . $vbulletin->GPC['featureid']
		);

		build_featured_entry_datastore();

		define('CP_REDIRECT', 'blog_admin.php?do=listfe');
		print_stop_message('deleted_featured_entry_successfully');
	}
}

// ##################### Start List Categories ###################################
if ($_REQUEST['do'] == 'listcat')
{
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 20;
	}

	?>
	<script type="text/javascript">
	function js_jump(id, obj)
	{
		task = obj.options[obj.selectedIndex].value;

		switch (task)
		{
			case 'modifycat': window.location = "blog_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=modifycat&blogcategoryid=" + id; break;
			case 'killcat': window.location = "blog_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=removecat&blogcategoryid=" + id; break;
			default: return false; break;
		}
	}
	</script>
	<?php

	$options = array(
		'modifycat' => $vbphrase['edit'],
		'killcat'   => $vbphrase['delete'],
	);

	fetch_ordered_categories(0);

	print_form_header('blog_admin', 'doordercat');
	print_table_header($vbphrase['blog_categories'], 3);

	if (!empty($vbulletin->vbblog['categorycache']["0"]))
	{
		print_cells_row(array(
			$depthmark . $vbphrase['title'],
			$vbphrase['display_order'],
			$vbphrase['controls'],
		), 1);

		foreach ($vbulletin->vbblog['categorycache']["0"] AS $categoryid => $category)
		{
			$depthmark = str_pad('', 4 * $category['depth'], '- - ', STR_PAD_LEFT);
			print_cells_row(array(
				$depthmark . $vbphrase['category' . $category['blogcategoryid'] . '_title'],
				"<input type=\"text\" class=\"bginput\" tabindex=\"1\" name=\"displayorder[$category[blogcategoryid]]\" value=\"$category[displayorder]\" size=\"2\" />",
				"<span style=\"white-space:nowrap\"><select name=\"c$category[blogcategoryid]\" onchange=\"js_jump($category[blogcategoryid], this);\" class=\"bginput\">" . construct_select_options($options) . "</select><input type=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_jump($category[blogcategoryid], this.form.c$category[blogcategoryid]);\" class=\"button\" /></span>"
			));
		}
	}

	print_table_footer(3, (!empty($vbulletin->vbblog['categorycache']["0"]) ? '<input type="submit" class="button" value="' . $vbphrase['save_display_order'] . '" accesskey="s" tabindex="1" />' : '') . construct_button_code($vbphrase['add_new_blog_category'], "blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=modifycat"));
}

// ###################### Start do order #######################
if ($_POST['do'] == 'doordercat')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'displayorder' => TYPE_ARRAY_UINT
	));

	if (!empty($vbulletin->GPC['displayorder']))
	{
		$categories = $db->query_read_slave("SELECT blogcategoryid, displayorder FROM " . TABLE_PREFIX . "blog_category WHERE userid = 0");
		while ($category = $db->fetch_array($categories))
		{
			if ($category['displayorder'] != $vbulletin->GPC['displayorder']["$category[blogcategoryid]"])
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "blog_category
					SET displayorder = " . $vbulletin->GPC['displayorder']["$category[blogcategoryid]"] . "
					WHERE blogcategoryid = $category[blogcategoryid]
						AND userid = 0
				");
			}
		}
	}

	// build category cache here
	build_category_permissions();

	define('CP_REDIRECT', 'blog_admin.php?do=listcat');
	print_stop_message('saved_display_order_successfully');
}

// ##################### Start Modify Categories ###################################
if ($_REQUEST['do'] == 'modifycat')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blogcategoryid' => TYPE_UINT,
	));

	print_form_header('blog_admin', 'updatecat');

	if ($vbulletin->GPC['blogcategoryid'])
	{
		$title = 'category' . $vbulletin->GPC['blogcategoryid'] . '_title';
		$desc = 'category' . $vbulletin->GPC['blogcategoryid'] . '_desc';

		$category = $db->query_first_slave("
			SELECT blog_category.*, phrase1.text AS title, phrase2.text AS description
			FROM " . TABLE_PREFIX . "blog_category AS blog_category
			LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase1 ON (phrase1.varname = '$title' AND phrase1.fieldname = 'vbblogcat' AND phrase1.languageid = 0)
			LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase2 ON (phrase2.varname = '$desc' AND phrase2.fieldname = 'vbblogcat' AND phrase2.languageid = 0)
			WHERE blog_category.blogcategoryid = " . $vbulletin->GPC['blogcategoryid'] . "
		");

		if (!$category)
		{
			print_stop_message('invalid_x_specified', $vbphrase['blog_category']);
		}

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['blog_category'], htmlspecialchars_uni($category['title']), $category['blogcategoryid']), 2, 0);
		construct_hidden_code('blogcategoryid', $vbulletin->GPC['blogcategoryid']);
	}
	else
	{
		print_table_header($vbphrase['add_new_blog_category']);
		$category = array(
			'displayorder' => 0,
		);
	}

	$categorylist = array(
		0 => $vbphrase['none']
	);

	fetch_ordered_categories(0);
	foreach ($vbulletin->vbblog['categorycache']["0"] AS $blogcategoryid => $categorie)
	{
		$depthmark = str_pad('', 4 * $categorie['depth'], '- - ', STR_PAD_LEFT);
		$categorylist["$blogcategoryid"] = $depthmark . $categorie['title'];
	}

	$trans_link = "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=vbblogcat&t=1&varname="; // has varname appended
	print_input_row($vbphrase['title'] . ($title ? '<dfn>' . construct_link_code($vbphrase['translations'], $trans_link . $title, 1)  . '</dfn>' : ''), 'title', $category['title']);
	print_textarea_row($vbphrase['description'] . ($desc ? '<dfn>' . construct_link_code($vbphrase['translations'], $trans_link . $desc, 1)  . '</dfn>' : ''),  'description', $category['description']);
	print_select_row($vbphrase['parent_category'], 'parentid', $categorylist, $category['parentid']);
	print_input_row($vbphrase['display_order'], 'displayorder', $category['displayorder'], true, 4);
	print_submit_row($vbphrase['save']);
}

// ##################### Start Update Category ###################################
if ($_POST['do'] == 'updatecat')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'title'          => TYPE_STR,
		'description'    => TYPE_STR,
		'parentid'       => TYPE_UINT,
		'blogcategoryid' => TYPE_UINT,
		'displayorder'   => TYPE_UINT,
	));

	if (empty($vbulletin->GPC['title']))
	{
		print_stop_message('please_complete_required_fields');
	}

	fetch_ordered_categories(0);
	$dataman =& datamanager_init('Blog_Category', $vbulletin, ERRTYPE_CP);

	if ($vbulletin->GPC['blogcategoryid'])
	{
		if (!($categoryinfo = $db->query_first("
			SELECT *, 0 AS userid
			FROM " . TABLE_PREFIX . "blog_category
			WHERE blogcategoryid = " . $vbulletin->GPC['blogcategoryid'] . "
				AND userid = 0
		")))
		{
			print_stop_message('invalid_x_specified', 'categoryid');
		}
		$dataman->set_existing($categoryinfo);
	}
	else
	{
		$dataman->set('userid', 0);
	}

	$dataman->set('description', $vbulletin->GPC['description']);
	$dataman->set('title', $vbulletin->GPC['title']);
	$dataman->set('parentid', $vbulletin->GPC['parentid']);
	$dataman->set('displayorder', $vbulletin->GPC['displayorder']);
	$dataman->save();

	build_category_permissions();

	define('CP_REDIRECT', 'blog_admin.php?do=listcat');
	print_stop_message('saved_blog_category_successfully');
}

/**
* Verifies that a given blog parent id is not one of its own children
*
* @param	integer	The ID of the current category
* @param	integer	The ID of the category's proposed parentid
*
* @return	boolean	Returns true if the children of the given parent category does not include the specified category... or something
*/
function is_subcategory_of($categoryid, $parentid)
{
	global $vbulletin;

	if (is_array($vbulletin->vbblog['icategorycache']["0"]["$categoryid"]))
	{
		foreach ($vbulletin->vbblog['icategorycache']["0"]["$categoryid"] AS $curcategoryid => $category)
		{
			if ($curcategoryid == $parentid OR !$is_subcategory_of($curcategoryid, $parentid))
			{
				print_stop_message('cant_parent_category_to_child');
			}
		}
	}

	return true;
}

// ##################### Start Remove Category ###################################
if ($_REQUEST['do'] == 'removecat')
{
	$vbulletin->input->clean_array_gpc('r', array('blogcategoryid' => TYPE_UINT));

	print_delete_confirmation('blog_category', $vbulletin->GPC['blogcategoryid'], 'blog_admin', 'killcat', 'blogcategoryid');
}

// ##################### Start Kill Category ###################################
if ($_POST['do'] == 'killcat')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'blogcategoryid' => TYPE_UINT,
	));

	$categoryinfo = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "blog_category
		WHERE blogcategoryid = " . $vbulletin->GPC['blogcategoryid'] . "
			AND userid = 0
	");

	if (!$categoryinfo)
	{
		print_stop_message('invalid_x_specified', 'blogcategoryid');
	}
	else
	{
		$dataman =& datamanager_init('Blog_Category', $vbulletin, ERRTYPE_CP);
		$dataman->set_existing($categoryinfo);
		$dataman->set_condition("FIND_IN_SET('" . $vbulletin->GPC['blogcategoryid'] . "', parentlist)");
		$dataman->delete();

		build_category_permissions();

		define('CP_REDIRECT', 'blog_admin.php?do=listcat');
		print_stop_message('deleted_blog_category_successfully');
	}
}

// ###################### Start list permissions #######################
if ($_REQUEST['do'] == 'listcp')
{

	print_form_header('', '');
	print_table_header($vbphrase['blog_category_permissions']);
	print_description_row('
		<div class="darkbg" style="border: 2px inset">	<ul class="darkbg">
		<li><b>' . $vbphrase['color_key'] . '</b></li>
		<li class="col-g">' . $vbphrase['standard_using_default_usergroup_permissions'] . '</li>
		<li class="col-c">' . $vbphrase['customized_using_custom_permissions_for_this_usergroup'] . '</li>
		<li class="col-i">' . $vbphrase['inherited_using_custom_permissions_inherited_from_a_parent_category'] . '</li>
		</ul></div>
	');

	print_table_footer();

	fetch_ordered_categories(0);

	// query category permissions
	$categorypermissions = $db->query_read("
		SELECT bcp.usergroupid, bc.blogcategoryid, bcp.categorypermissions, bcp.categorypermissionid,
		NOT (ISNULL(bcp.blogcategoryid)) AS hasdata, bcp.blogcategoryid
		FROM " . TABLE_PREFIX . "blog_category AS bc
		LEFT JOIN " . TABLE_PREFIX . "blog_categorypermission AS bcp ON (bcp.blogcategoryid = bc.blogcategoryid)
	");

	$permscache = array();
	while ($cperm = $db->fetch_array($categorypermissions))
	{
		if ($cperm['hasdata'])
		{
			$temp = array();
			$temp['categorypermissionid'] = $cperm['categorypermissionid'];
			$temp['categorypermissions'] = $cperm['categorypermissions'];
			$permscache["{$cperm['blogcategoryid']}"]["{$cperm['usergroupid']}"] = $temp;
		}
	}

	// get usergroup default permissions
	$permissions = array();
	foreach($vbulletin->usergroupcache AS $usergroupid => $usergroup)
	{
		$permissions["$usergroupid"] = $usergroup['vbblog_general_permissions'];
	}

	echo '<center><div class="tborder" style="width: 89%">';
	echo '<div class="alt1" style="padding: 8px">';
	echo '<div class="darkbg" style="padding: 4px; border: 2px inset; text-align: ' . vB_Template_Runtime::fetchStyleVar('left') . '">';

	print_categories($permscache, $permissions, array(), 0);

	echo "</div></div></div></center>";
}

// ###################### Start edit category permission #######################
if ($_REQUEST['do'] == 'editcp')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'categorypermissionid' => TYPE_UINT,
		'blogcategoryid'       => TYPE_UINT,
		'usergroupid'          => TYPE_UINT
	));

	?>
	<script type="text/javascript">
	<!--
	function js_set_custom()
	{
		if (document.cpform.useusergroup[1].checked == false)
		{
			if (confirm('<?php echo addslashes_js($vbphrase['must_enable_custom_permissions']);?>'))
			{
				document.cpform.useusergroup[1].checked = true;
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
		}
	}
	// -->
	</script>
	<?php

	print_form_header('blog_admin', 'updatecp');

	if ($vbulletin->GPC['categorypermissionid'])
	{
		$getperms = $db->query_first("
			SELECT bcp.*, usergroup.title AS grouptitle, phrase.text AS title
			FROM " . TABLE_PREFIX . "blog_categorypermission AS bcp
			INNER JOIN " . TABLE_PREFIX . "blog_category AS bc ON (bc.blogcategoryid = bcp.blogcategoryid)
			LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase ON (phrase.varname = CONCAT(CONCAT('category', bc.blogcategoryid), '_title') AND phrase.fieldname = 'vbblogcat' AND phrase.languageid = 0)
			INNER JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroup.usergroupid = bcp.usergroupid)
			WHERE bcp.categorypermissionid = " . $vbulletin->GPC['categorypermissionid']
		);
		$usergroup['title'] = $getperms['grouptitle'];
		$category['title'] = $getperms['title'];
		construct_hidden_code('categorypermissionid', $vbulletin->GPC['categorypermissionid']);
		construct_hidden_code('blogcategoryid', $getperms['blogcategoryid']);
	}
	else
	{
		$category = $db->query_first("SELECT text AS title FROM " . TABLE_PREFIX . "phrase WHERE varname = CONCAT(CONCAT('category', '" . $vbulletin->GPC['blogcategoryid'] . "'), '_title') AND fieldname = 'vbblogcat' AND languageid = 0");
		$usergroup = $db->query_first("SELECT title FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid = " . $vbulletin->GPC['usergroupid']);

		$getperms = $db->query_first("
			SELECT usergroup.title as grouptitle, vbblog_general_permissions AS categorypermissions
			FROM " . TABLE_PREFIX . "usergroup AS usergroup
			WHERE usergroupid = " . $vbulletin->GPC['usergroupid']
		);

		construct_hidden_code('categorypermission[usergroupid]', $vbulletin->GPC['usergroupid']);
		construct_hidden_code('blogcategoryid', $vbulletin->GPC['blogcategoryid']);
	}
	$categorypermission = convert_bits_to_array($getperms['categorypermissions'], $vbulletin->bf_ugp_vbblog_general_permissions);

	print_table_header(construct_phrase($vbphrase['edit_category_permissions_for_usergroup_x_in_category_y'], $usergroup['title'], $category['title']));
	print_description_row('
		<label for="uug_1"><input type="radio" name="useusergroup" value="1" id="uug_1" tabindex="1" onclick="this.form.reset(); this.checked=true;"' . (!$vbulletin->GPC['categorypermissionid'] ? ' checked="checked"' : '') . ' />' . $vbphrase['use_default_permissions'] . '</label>
		<br />
		<label for="uug_0"><input type="radio" name="useusergroup" value="0" id="uug_0" tabindex="1"' . ($vbulletin->GPC['categorypermissionid'] ? ' checked="checked"' : '') . ' />' . $vbphrase['use_custom_permissions'] . '</label>
	', 0, 2, 'tfoot', '', 'mode');
	print_table_break();
	print_label_row(
		'<b>' . $vbphrase['custom_blog_category_permissions'] . '</b>','
		<input type="button" value="' . $vbphrase['all_yes'] . '" onclick="if (js_set_custom()) { js_check_all_option(this.form, 1); }" class="button" />
		<input type="button" value=" ' . $vbphrase['all_no'] . ' " onclick="if (js_set_custom()) { js_check_all_option(this.form, 0); }" class="button" />
	', 'tcat', 'middle');

	print_yes_no_row($vbphrase['can_view_admin_category'], 'categorypermission[blog_canviewcategory]', $categorypermission['blog_canviewcategory'], 'js_set_custom();');
	print_yes_no_row($vbphrase['can_post_to_admin_category'], 'categorypermission[blog_canpostcategory]', $categorypermission['blog_canpostcategory'], 'js_set_custom();');


	print_submit_row($vbphrase['save']);
}

// ##################### Start Updatecp ###################################
if ($_POST['do'] == 'updatecp')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'categorypermissionid' => TYPE_UINT,
		'blogcategoryid'       => TYPE_UINT,
		'useusergroup'         => TYPE_BOOL,
		'categorypermission'   => TYPE_ARRAY,
	));

	define('CP_REDIRECT', "blog_admin.php?do=listcp#category" . $vbulletin->GPC['blogcategoryid']);

	if ($vbulletin->GPC['useusergroup'])
	{
		// use usergroup defaults. delete categorypermission if it exists
		if ($vbulletin->GPC['categorypermissionid'])
		{
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "blog_categorypermission
				WHERE categorypermissionid = " . $vbulletin->GPC['categorypermissionid']
			);

			build_category_permissions();
			print_stop_message('deleted_category_permissions_successfully');
		}
		else
		{
			build_category_permissions();
			print_stop_message('saved_category_permissions_successfully');
		}
	}
	else
	{
		require_once(DIR . '/includes/functions_misc.php');
		$vbulletin->GPC['categorypermission']['categorypermissions'] = convert_array_to_bits($vbulletin->GPC['categorypermission'], $vbulletin->bf_ugp_vbblog_general_permissions, 1);

		if ($vbulletin->GPC['blogcategoryid'] AND !$vbulletin->GPC['categorypermissionid'])
		{
			$vbulletin->GPC['categorypermission']['blogcategoryid'] = $vbulletin->GPC['blogcategoryid'];
			$query = fetch_query_sql($vbulletin->GPC['categorypermission'], 'blog_categorypermission');
			$db->query_write($query);
		}
		else
		{
			$query = fetch_query_sql($vbulletin->GPC['categorypermission'], 'blog_categorypermission' , "WHERE categorypermissionid = " . $vbulletin->GPC['categorypermissionid']);
			$db->query_write($query);
		}

		build_category_permissions();
		print_stop_message('saved_category_permissions_successfully');
	}

}

// ##################### Start Stats ###################################
if ($_REQUEST['do'] == 'stats')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'start'     => TYPE_ARRAY_INT,
		'end'       => TYPE_ARRAY_INT,
		'scope'     => TYPE_NOHTML,
		'sort'      => TYPE_NOHTML,
		'nullvalue' => TYPE_BOOL,
		'username'  => TYPE_NOHTML,
		'type'      => TYPE_NOHTML,
	));

	if (!empty($vbulletin->GPC['username']))
	{
		if (!($userexist = $db->query_first("
			SELECT userid
			FROM " . TABLE_PREFIX . "user
			WHERE username = '" . $db->escape_string($vbulletin->GPC['username']) . "'"))
		)
		{
			print_stop_message('invalid_user_specified');
		}
	}

	// Default View Values
	if (empty($vbulletin->GPC['start']))
	{
		$vbulletin->GPC['start'] = TIMENOW - 3600 * 24 * 30;
	}

	if (empty($vbulletin->GPC['end']))
	{
		$vbulletin->GPC['end'] = TIMENOW;
	}

	switch ($vbulletin->GPC['scope'])
	{
		case 'weekly':
		case 'monthly':
			$scope = $vbulletin->GPC['scope'];
			break;
		default:
			$scope = 'daily';
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

	switch ($vbulletin->GPC['type'])
	{
		case 'comments':
		case 'users':
			$type = $vbulletin->GPC['type'];
			break;
		default:
			$type = 'entries';
	}

	print_form_header('blog_admin', 'stats');
	print_table_header($vbphrase['blog_stats']);

	print_input_row($vbphrase['username'], 'username', $vbulletin->GPC['username']);
	print_time_row($vbphrase['start_date'], 'start', $vbulletin->GPC['start'], false);
	print_time_row($vbphrase['end_date'], 'end', $vbulletin->GPC['end'], false);
	print_select_row($vbphrase['type'], 'type', array(
		'entries'  => $vbphrase['entries'],
		'comments' => $vbphrase['comments'],
		'users'    => $vbphrase['visitors'],
	), $type);
	print_select_row($vbphrase['scope'], 'scope', array(
		'daily'   => $vbphrase['daily'],
		'weekly'  => $vbphrase['weekly'],
		'monthly' => $vbphrase['monthly']
	), $scope);
	print_select_row($vbphrase['order_by'], 'sort', array(
		'date_asc'   => $vbphrase['date_ascending'],
		'date_desc'  => $vbphrase['date_descending'],
		'total_asc'  => $vbphrase['total_ascending'],
		'total_desc' => $vbphrase['total_descending'],
	), $vbulletin->GPC['sort']);
	print_yes_no_row($vbphrase['include_empty_results'], 'nullvalue', $vbulletin->GPC['nullvalue']);
	print_submit_row($vbphrase['go']);

	if (!empty($vbulletin->GPC['scope']))
	{
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
			default:	// daily
				$sqlformat = '%w %U %m %Y';
				$phpformat = '! d, Y';
				break;
		}

		$statistics = $db->query_read("
			SELECT SUM($type) AS total,
			DATE_FORMAT(from_unixtime(dateline), '$sqlformat') AS formatted_date,
			MAX(dateline) AS dateline
			FROM " . TABLE_PREFIX . "blog_" . (!empty($userexist['userid']) ? "user" : "summary") . "stats
			WHERE dateline >= $start_time
				AND dateline <= $end_time
			" . (!empty($userexist['userid']) ? "AND userid = $userexist[userid]" : "") . "
			GROUP BY formatted_date
			" . (empty($vbulletin->GPC['nullvalue']) ? " HAVING total > 0 " : "") . "
			ORDER BY $orderby
		");

		$dates = $results = array();
		while ($stats = $db->fetch_array($statistics))
		{ // we will now have each days total of the type picked and we can sort through it
			$month = strtolower(date('F', $stats['dateline']));
			$dates[] = str_replace(' ', '&nbsp;', str_replace('#', $vbphrase['week'] . '&nbsp;' . strftime('%U', $stats['dateline']), str_replace('!', $vbphrase["$month"], date($phpformat, $stats['dateline']))));
			$results[] = $stats['total'];
		}

		if (empty($results))
		{
			print_stop_message('no_matches_found');
		}

		require_once(DIR . '/includes/adminfunctions_stats.php');

		// we'll need a poll image
		$style = $db->query_first("
			SELECT stylevars FROM " . TABLE_PREFIX . "style
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
/*
// ########################################################################

if ($_POST['do'] == 'taginsert')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'tagtext'    => TYPE_NOHTML,
		'pagenumber' => TYPE_UINT,
		'sort'       => TYPE_NOHTML,
		'perpage'    => TYPE_UINT,
	));

	if ($db->query_first("
		SELECT tagid
		FROM " . TABLE_PREFIX . "blog_tag
		WHERE tagtext = '" . $db->escape_string($vbulletin->GPC['tagtext']) . "'
	"))
	{
		print_stop_message('tag_exists');
	}

	require_once(DIR . '/includes/blog_functions_tag.php');
	$valid = fetch_valid_entry_tags(array(), array($vbulletin->GPC['tagtext']), $vbulletin->userinfo, $errors, false);

	if ($errors)
	{
		print_stop_message('generic_error_x', implode('<br /><br />', $errors));
	}

	if ($vbulletin->GPC['tagtext'])
	{
		$db->query_write("
			INSERT IGNORE INTO " . TABLE_PREFIX . "blog_tag
				(tagtext, dateline)
			VALUES
				('" . $db->escape_string($vbulletin->GPC['tagtext']) . "', " . TIMENOW . ")
		");
	}

	define('CP_REDIRECT', 'blog_admin.php?do=tags&pagenumber=' . $vbulletin->GPC['pagenumber'] . '&pp=' . $vbulletin->GPC['perpage'] . '&sort=' . $vbulletin->GPC['sort']);
	print_stop_message('tag_saved');
}

// ########################################################################

if ($_REQUEST['do'] == 'tags')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT,
		'sort'       => TYPE_NOHTML,
		'perpage'    => TYPE_UINT,
	));

	$vbulletin->input->clean_array_gpc('p', array(
		'delete' => TYPE_STR,
	));

	if (empty($vbulletin->GPC['delete']))
	{
		if ($vbulletin->GPC['pagenumber'] < 1)
		{
			$vbulletin->GPC['pagenumber'] = 1;
		}

		$column_count = 3;
		$max_per_column = 15;

		if (!$vbulletin->GPC['perpage'])
		{
			$vbulletin->GPC['perpage'] = $column_count * $max_per_column;
		}

		do
		{
			if (!$vbulletin->GPC['pagenumber'])
			{
				$vbulletin->GPC['pagenumber'] = 1;
			}
			$start = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

			$tags = $db->query_read("
				SELECT SQL_CALC_FOUND_ROWS *
				FROM " . TABLE_PREFIX . "blog_tag
				ORDER BY " . ($vbulletin->GPC['sort'] == 'dateline' ? 'dateline DESC' : 'tagtext') . "
				LIMIT $start, {$vbulletin->GPC['perpage']}
			");

			$tag_count = $db->found_rows();
			if ($start >= $tag_count)
			{
				$vbulletin->GPC['pagenumber'] = ceil($tag_count / $vbulletin->GPC['perpage']);
			}
		}
		while ($start >= $tag_count AND $tag_count);

		print_form_header('blog_admin', 'tags');
		construct_hidden_code('page', $vbulletin->GPC['pagenumber']);
		construct_hidden_code('pp', $vbulletin->GPC['perpage']);
		construct_hidden_code('sort', $vbulletin->GPC['sort']);
		print_table_header($vbphrase['tag_list'], 3);
		if ($db->num_rows($tags))
		{
			$columns = array();
			$counter = 0;

			// build page navigation
			$total_pages = ceil($tag_count / $vbulletin->GPC['perpage']);
			if ($total_pages > 1)
			{
				$pagenav = '<strong>' . $vbphrase['go_to_page'] . '</strong>';
				for ($thispage = 1; $thispage <= $total_pages; $thispage++)
				{
					if ($thispage == $vbulletin->GPC['pagenumber'])
					{
						$pagenav .= " <strong>[$thispage]</strong> ";
					}
					else
					{
						$pagenav .= " <a href=\"blog_admin.php?$session[sessionurl]do=tags&amp;page=$thispage&amp;sort=" . $vbulletin->GPC['sort'] . "&pp=" . $vbulletin->GPC['perpage'] . "\" class=\"normal\">$thispage</a> ";
					}
				}

			}
			else
			{
				$pagenav = '';
			}

			if ($vbulletin->GPC['sort'] == 'dateline')
			{
				$sort_link = '<a href="blog_admin.php?do=tags">' . $vbphrase['display_alphabetically'] . '</a>';
			}
			else
			{
				$sort_link = '<a href="blog_admin.php?do=tags&amp;sort=dateline">' . $vbphrase['display_newest'] . '</a>';
			}

			print_description_row(
				"<div style=\"float: $stylevar[left]\">$sort_link</div>$pagenav
				<input type=\"checkbox\" name=\"allbox\" title=\"" . $vbphrase['check_all'] . "\" onclick=\"js_check_all(this.form);\" />",
				false, 3, 'thead', 'right'
			);

			// build columns
			while ($tag = $db->fetch_array($tags))
			{
				$columnid = floor($counter++ / $max_per_column);
				$columns["$columnid"][] = '<label for="tag' . $tag['tagid'] . '_1"><input type="checkbox" name="tag[' . $tag['tagid'] . ']" id="tag' . $tag['tagid'] . '_1" value="1" tabindex="1" /> ' . $tag['tagtext'] . '</label>';
			}

			// make column values printable
			$cells = array();
			for ($i = 0; $i < $column_count; $i++)
			{
				if ($columns["$i"])
				{
					$cells[] = implode("<br />\n", $columns["$i"]);
				}
				else
				{
					$cells[] = '&nbsp;';
				}
			}

			print_column_style_code(array(
				'width: 33%',
				'width: 33%',
				'width: 34%'
			));
			print_cells_row($cells, false, false, -3);

			print_table_footer(3, "\n\t<input type=\"submit\" class=\"button\" name=\"delete\" value=\"" . $vbphrase['delete_selected'] . "\" tabindex=\"1\" />\n\t&nbsp; &nbsp; &nbsp; &nbsp;
			" . $vbphrase['per_page'] . "
			<input type=\"text\" name=\"perpage\" value=\"" . $vbulletin->GPC['perpage'] . "\" size=\"3\" tabindex=\"1\" />
			<input type=\"submit\" class=\"button\" value=\"" . $vbphrase['go'] . "\" tabindex=\"1\" />\n\t");
		}
		else
		{
			print_description_row($vbphrase['no_tags_defined'], false, 3, '', 'center');
			print_table_footer();
		}

		print_form_header('blog_admin', 'taginsert');
		construct_hidden_code('pagenumber', $vbulletin->GPC['pagenumber']);
		construct_hidden_code('pp', $vbulletin->GPC['perpage']);
		construct_hidden_code('sort', $vbulletin->GPC['sort']);
		print_input_row($vbphrase['add_tag'], 'tagtext');
		print_submit_row();
	}
	else
	{
		$_POST['do'] = 'tagkill';
	}
}

// ########################################################################

if ($_POST['do'] == 'tagkill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'tag'        => TYPE_ARRAY_KEYS_INT,
		'pagenumber' => TYPE_UINT,
		'perpage'    => TYPE_UINT,
		'sort'       => TYPE_NOHTML,
	));

	if ($vbulletin->GPC['tag'])
	{
		$tags_result = $vbulletin->db->query_read("
			SELECT tagtext
			FROM " . TABLE_PREFIX . "blog_tag
			WHERE tagid IN (" . implode(',', $vbulletin->GPC['tag']) . ")
		");

		$tagstodelete = array();
		while ($tag = $vbulletin->db->fetch_array($tags_result))
		{
			$tagstodelete[] = $tag['tagtext'];
		}
		unset($tag);

		if (!empty($tagstodelete))
		{
			$entries_result = $vbulletin->db->query_read("
				SELECT DISTINCT blog.*
				FROM " . TABLE_PREFIX . "blog_tagentry AS tagentry
				INNER JOIN " . TABLE_PREFIX . "blog AS blog ON (blog.blogid = tagentry.blogid)
				WHERE tagentry.blogid IN (" . implode(',', $vbulletin->GPC['tag']) . ")
			");
			while ($blog = $vbulletin->db->fetch_array($entries_result))
			{
				$newtags = array();
				foreach (explode(',', trim($entry['taglist'])) AS $oldtag)
				{
					$oldtag = trim($oldtag);
					if (!in_array($oldtag, $tagstodelete))
					{
						$newtags[] = $oldtag;
					}
				}

				$newtags_string = implode(', ', $newtags);

				if ($newtags_string != $thread['taglist'])
				{
					// if efficiency is needed, this could be changed to a direct query
					$blogdm =& datamanager_init('Blog', $vbulletin, ERRTYPE_SILENT, 'blog');
					$blogdm->set_existing($blog);
					$blogdm->set('taglist', $newtags_string);
					$blogdm->save();

					unset($blogdm);
				}
			}
		}

		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_tag
			WHERE tagid IN (" . implode(',', $vbulletin->GPC['tag']) . ")
		");

		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_tagentry
			WHERE tagid IN (" . implode(',', $vbulletin->GPC['tag']) . ")
		");

		// need to invalidate the search and tag cloud caches
		build_datastore('blogtagcloud', '', 1);
		build_datastore('blogsearchcloud', '', 1);
		$db->query_write("UPDATE " . TABLE_PREFIX . "blog_user SET tagcloud = ''");
	}

	define('CP_REDIRECT', 'blog_admin.php?do=tags&pagenumber=' . $vbulletin->GPC['pagenumber'] . '&pp=' . $vbulletin->GPC['perpage'] . '&sort=' . $vbulletin->GPC['sort']);
	print_stop_message('tags_edited_successfully');
}
*/
// ########################################################################

// ##################### Start User CSS ###################################
if ($_REQUEST['do'] == 'usercss' OR $_POST['do'] == 'updateusercss')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT
	));

	$userinfo = fetch_userinfo($vbulletin->GPC['userid']);

	if (!$userinfo)
	{
		print_stop_message('invalid_user_specified');
	}

	cache_permissions($userinfo, false);

	$usercsspermissions = array(
		'caneditfontfamily' => $userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditfontfamily'] ? true  : false,
		'caneditfontsize'   => $userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditfontsize'] ? true : false,
		'caneditcolors'     => $userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditcolors'] ? true : false,
		'caneditbgimage'    => $userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditbgimage'] ? true : false,
		'caneditborders'    => $userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditborders'] ? true : false
	);

	require_once(DIR . '/includes/class_usercss.php');
	require_once(DIR . '/includes/class_usercss_blog.php');
	$usercss = new vB_UserCSS_Blog($vbulletin, $userinfo['userid']);
}

// ########################################################################

if ($_POST['do'] == 'updateusercss')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usercss' => TYPE_ARRAY
	));

	$allowedfonts = $usercss->build_select_option($vbulletin->options['vbblog_usercss_allowed_fonts']);
	$allowedfontsizes = $usercss->build_select_option($vbulletin->options['vbblog_usercss_allowed_font_sizes']);
	$allowedborderwidths = $usercss->build_select_option($vbulletin->options['vbblog_usercss_allowed_border_widths']);
	$allowedpaddings = $usercss->build_select_option($vbulletin->options['vbblog_usercss_allowed_padding']);

	foreach ($vbulletin->GPC['usercss'] AS $selectorname => $selector)
	{
		if (!isset($usercss->cssedit["$selectorname"]) OR !empty($usercss->cssedit["$selectorname"]['noinputset']))
		{
			$usercss->error[] = fetch_error('invalid_selector_name_x', $selectorname);
			continue;
		}

		if (!is_array($selector))
		{
			continue;
		}

		foreach ($selector AS $property => $value)
		{
			$prop_perms = $usercss->properties["$property"]['permission'];

			if (empty($usercsspermissions["$prop_perms"]) OR !in_array($property, $usercss->cssedit["$selectorname"]['properties']))
			{
				$usercss->error[] = fetch_error('no_permission_edit_selector_x_property_y', $selectorname, $property);
				continue;
			}

			unset($allowedlist);
			switch ($property)
			{
				case 'font_size':    $allowedlist = $allowedfontsizes; break;
				case 'font_family':  $allowedlist = $allowedfonts; break;
				case 'border_width': $allowedlist = $allowedborderwidths; break;
				case 'padding':      $allowedlist = $allowedpaddings; break;
			}

			if (isset($allowedlist))
			{
				if (!in_array($value, $allowedlist) AND $value != '')
				{
					$usercss->invalid["$selectorname"]["$property"] = ' usercsserror ';
					continue;
				}
			}

			$usercss->parse($selectorname, $property, $value);
		}
	}

	if (!empty($usercss->error))
	{
		print_cp_message(implode("<br />", $usercss->error));
	}
	else if (!empty($usercss->invalid))
	{
		print_stop_message('invalid_values_customize_blog');
	}

	$usercss->save();

	define('CP_REDIRECT', "user.php?do=edit&amp;u=$userinfo[userid]");
	print_stop_message('saved_blog_customizations_successfully');
}

// ########################################################################

if ($_REQUEST['do'] == 'usercss')
{
	require_once(DIR . '/includes/adminfunctions_template.php');

	?>
	<script type="text/javascript" src="../clientscript/vbulletin_cpcolorpicker.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
	<?php

	$colorPicker = construct_color_picker(11);

	$allowedfonts = $usercss->build_admin_select_option($vbulletin->options['vbblog_usercss_allowed_fonts'], 'usercss_font_');
	$allowedfontsizes = $usercss->build_admin_select_option($vbulletin->options['vbblog_usercss_allowed_font_sizes'], 'usercss_fontsize_');
	$allowedborderwidths = $usercss->build_admin_select_option($vbulletin->options['vbblog_usercss_allowed_border_widths'], 'usercss_borderwidth_');
	$allowedpaddings = $usercss->build_admin_select_option($vbulletin->options['vbblog_usercss_allowed_padding'], 'usercss_padding_');

	$allowedborderstyles = array(
		''       => '',
		'none'   => $vbphrase['usercss_borderstyle_none'],
		'hidden' => $vbphrase['usercss_borderstyle_hidden'],
		'dotted' => $vbphrase['usercss_borderstyle_dotted'],
		'dashed' => $vbphrase['usercss_borderstyle_dashed'],
		'solid'  => $vbphrase['usercss_borderstyle_solid'],
		'double' => $vbphrase['usercss_borderstyle_double'],
		'groove' => $vbphrase['usercss_borderstyle_groove'],
		'ridge'  => $vbphrase['usercss_borderstyle_ridge'],
		'inset'  => $vbphrase['usercss_borderstyle_inset'],
		'outset' => $vbphrase['usercss_borderstyle_outset']
	);

	$allowedbackgroundrepeats = array(
		'' => '',
		'repeat' => $vbphrase['usercss_repeat_repeat'],
		'repeat-x' => $vbphrase['usercss_repeat_repeat_x'],
		'repeat-y' => $vbphrase['usercss_repeat_repeat_y'],
		'no-repeat' => $vbphrase['usercss_repeat_no_repeat']
	);

	$cssdisplayinfo = $usercss->build_display_array();

	print_form_header('blog_admin', 'updateusercss');
	print_table_header(construct_phrase($vbphrase['edit_blog_style_customizations_for_x'], $userinfo['username']));
	construct_hidden_code('userid', $userinfo['userid']);

	$have_output = false;

	foreach ($cssdisplayinfo AS $selectorname => $selectorinfo)
	{
		if (empty($selectorinfo['properties']))
		{
			$selectorinfo['properties'] = $usercss->cssedit["$selectorname"]['properties'];
		}

		if (!is_array($selectorinfo['properties']))
		{
			continue;
		}

		$field_names = array();
		$selector = array();

		foreach ($selectorinfo['properties'] AS $key => $value)
		{
			if (is_numeric($key))
			{
				$this_property = $value;
				$this_selector = $selectorname;
			}
			else
			{
				$this_property = $key;
				$this_selector = $value;
			}

			if (!$usercsspermissions[$usercss->properties["$this_property"]['permission']])
			{
				continue;
			}

			$field_names["$this_property"] = "usercss[$this_selector][$this_property]";
			$selector["$this_property"] = $usercss->existing["$this_selector"]["$this_property"];
		}

		if (!$field_names)
		{
			continue;
		}

		$have_output = true;

		print_description_row($vbphrase["$selectorinfo[phrasename]"], false, 2, 'thead', 'center');

		if ($field_names['font_family'])
		{
			print_select_row($vbphrase['usercss_font_family'], $field_names['font_family'], $allowedfonts, $selector['font_family']);
		}

		if ($field_names['font_size'])
		{
			print_select_row($vbphrase['usercss_font_size'], $field_names['font_size'], $allowedfontsizes, $selector['font_size']);
		}

		if ($field_names['color'])
		{
			print_color_input_row($vbphrase['usercss_color'], $field_names['color'], $selector['color'], true, 10);
		}

		if ($field_names['shadecolor'])
		{
			print_color_input_row($vbphrase['usercss_shadecolor'], $field_names['shadecolor'], $selector['shadecolor'], true, 10);
		}

		if ($field_names['linkcolor'])
		{
			print_color_input_row($vbphrase['usercss_linkcolor'], $field_names['linkcolor'], $selector['linkcolor'], true, 10);
		}

		if ($field_names['border_color'])
		{
			print_color_input_row($vbphrase['usercss_border_color'], $field_names['border_color'], $selector['border_color'], true, 10);
		}

		if ($field_names['border_style'])
		{
			print_select_row($vbphrase['usercss_border_style'], $field_names['border_style'], $allowedborderstyles, $selector['border_style']);
		}

		if ($field_names['border_width'])
		{
			print_select_row($vbphrase['usercss_border_width'], $field_names['border_width'], $allowedborderwidths, $selector['border_width']);
		}

		if ($field_names['padding'])
		{
			print_select_row($vbphrase['usercss_padding'], $field_names['padding'], $allowedpaddings, $selector['padding']);
		}

		if ($field_names['background_color'])
		{
			print_color_input_row($vbphrase['usercss_background_color'], $field_names['background_color'], $selector['background_color'], true, 10);
		}

		if ($field_names['background_image'])
		{
			if (preg_match("/^([0-9]+),([0-9]+)$/", $selector['background_image'], $picture))
			{
				$selector['background_image'] = "picture.php?albumid=" . $picture[1] . "&pictureid=" . $picture[2];
			}
			else
			{
				$selector['background_image'] = '';
			}
			print_input_row($vbphrase['usercss_background_image'], $field_names['background_image'], $selector['background_image']);
		}

		if ($field_names['background_repeat'])
		{
			print_select_row($vbphrase['usercss_background_repeat'], $field_names['background_repeat'], $allowedbackgroundrepeats, $selector['background_repeat']);
		}
	}

	if ($have_output == false)
	{
		print_description_row($vbphrase['user_no_permission_customize_blog']);
		print_table_footer();
	}
	else
	{
		print_submit_row();

		echo $colorPicker;
	?>
	<script type="text/javascript">
	<!--

	var bburl = "<?php echo $vbulletin->options['bburl']; ?>/";
	var cpstylefolder = "<?php echo $vbulletin->options['cpstylefolder']; ?>";
	var numColors = <?php echo intval($numcolors); ?>;
	var colorPickerWidth = <?php echo intval($colorPickerWidth); ?>;
	var colorPickerType = <?php echo intval($colorPickerType); ?>;

	init_color_preview();

	//-->
	</script>
	<?php
	}
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # SVN: $Revision: 63620 $
|| ####################################################################
\*======================================================================*/
?>
