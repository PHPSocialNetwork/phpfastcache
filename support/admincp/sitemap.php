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
define('CVS_REVISION', '$RCSfile$ - $Revision: $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('global');
$specialtemplates = array();
DEFINE ('THIS_SCRIPT', 'sitemap');
// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_sitemap.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('cansitemap'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'menu';
}

if (($_REQUEST['do'] == 'blog'))
{
	$headinsert = "
<script type=\"text/javascript\">
function init()
{

     snc1 = new vB_AJAX_NameSuggest('snc', 'userfield1_txt', 'userfield1');
     snc2 = new vB_AJAX_NameSuggest('snc', 'userfield2_txt', 'userfield2');
     snc3 = new vB_AJAX_NameSuggest('snc', 'userfield3_txt', 'userfield3');
     snc4 = new vB_AJAX_NameSuggest('snc', 'userfield4_txt', 'userfield4');
     snc5 = new vB_AJAX_NameSuggest('snc', 'userfield5_txt', 'userfield5');
     snc6 = new vB_AJAX_NameSuggest('snc', 'userfield6_txt', 'userfield6');
     snc7 = new vB_AJAX_NameSuggest('snc', 'userfield7_txt', 'userfield7');
     snc8 = new vB_AJAX_NameSuggest('snc', 'userfield8_txt', 'userfield8');
     snc9 = new vB_AJAX_NameSuggest('snc', 'userfield9_txt', 'userfield9');
     snc10 = new vB_AJAX_NameSuggest('snc', 'userfield10_txt', 'userfield9');
}

</script>

";
	$onload = "init();";

}
else
{
	$onload = '';
	$headinsert = '';
}

print_cp_header($vbphrase['xml_sitemap_manager'], $onload , $headinsert);


// ########################################################################
if ($_REQUEST['do'] == 'menu')
{
	$options = array('forum' => $vbphrase['forum']);

	//check to see if blog and/or cms are installed
	if (isset($vbulletin->products['vbcms']) AND $vbulletin->products['vbcms'])
	{
		$options['cms'] = $vbphrase['vbcms'];
	}
	if (isset($vbulletin->products['vbblog']) AND $vbulletin->products['vbblog'])
	{
		$options['blog'] = $vbphrase['blog'];
	}

	// Check if CMS is installed, and show link if so
	$show['viewarticles'] = $vbulletin->products['vbcms'];
	print_form_header('sitemap');
	print_table_header($vbphrase['sitemap_priority_manager']);
	print_select_row($vbphrase['manage_priority_for_content_type'], 'do', $options);
	print_submit_row($vbphrase['manage'], null);
}

// Default priority settings, with clear
$default_settings = array(
	'default' => $vbphrase['default'],
	'0.0' => vb_number_format('0.0', 1),
	'0.1' => vb_number_format('0.1', 1),
	'0.2' => vb_number_format('0.2', 1),
	'0.3' => vb_number_format('0.3', 1),
	'0.4' => vb_number_format('0.4', 1),
	'0.5' => vb_number_format('0.5', 1),
	'0.6' => vb_number_format('0.6', 1),
	'0.7' => vb_number_format('0.7', 1),
	'0.8' => vb_number_format('0.8', 1),
	'0.9' => vb_number_format('0.9', 1),
	'1.0' => vb_number_format('1.0', 1),
);

($hook = vBulletinHook::fetch_hook('sitemap_admin_start')) ? eval($hook) : false;

// ########################################################################
if ($_POST['do'] == 'saveforum')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'f' => TYPE_ARRAY_STR
	));

	// Custom values to remove
	$update_values = array();

	foreach ($vbulletin->GPC['f'] AS $forumid => $priority)
	{
		if ($priority == 'default')
		{
			$vbulletin->db->query("
				DELETE FROM " . TABLE_PREFIX . "contentpriority
				WHERE contenttypeid = 'forum' AND sourceid = " . intval($forumid)
			);
		}
		else
		{
			$update_values[] = "('forum', " . intval($forumid) . "," . floatval($priority) . ")";
		}
	}

	// If there are any with custom values, set them
	if (count($update_values))
	{
		$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "contentpriority
				(contenttypeid, sourceid, prioritylevel)
			VALUES
				" . implode(',', $update_values)
		);
	}

	define('CP_REDIRECT', 'sitemap.php?do=forum');
	print_stop_message('saved_content_priority_successfully');
}

// ########################################################################
if ($_REQUEST['do'] == 'forum')
{
	// Get the custom forum priorities
	$sitemap = new vB_SiteMap_Forum($vbulletin);

	print_form_header('sitemap', 'saveforum');
	print_table_header($vbphrase['forum_priority_manager']);
	print_description_row($vbphrase['sitemap_forum_priority_desc']);

	if (is_array($vbulletin->forumcache))
	{
		foreach($vbulletin->forumcache AS $key => $forum)
		{
			$priority = $sitemap->get_forum_custom_priority($forum['forumid']);
			if ($priority === false)
			{
				$priority = 'default';
			}

			$cell = array();

			$cell[] = "<b>" . construct_depth_mark($forum['depth'], '- - ')
				. "<a href=\"forum.php?do=edit&amp;f=$forum[forumid]\">$forum[title]</a></b>";

			$cell[] = "\n\t<select name=\"f[$forum[forumid]]\" class=\"bginput\">\n"
				. construct_select_options($default_settings, $priority)
				. " />\n\t";

			if ($forum['parentid'] == -1)
			{
				print_cells_row(array(
					$vbphrase['forum'],
					construct_phrase($vbphrase['priority_default_x'], vb_number_format($vbulletin->options['sitemap_priority'], 1))
				), 1, 'tcat');
			}

			($hook = vBulletinHook::fetch_hook('sitemap_forum_row')) ? eval($hook) : false;

			print_cells_row($cell);
		}
	}

	print_submit_row($vbphrase['save_priority']);
}


// ########################################################################
if ($_REQUEST['do'] == 'blog')
{
	// Get the custom forum priorities
	$sitemap = new vB_SiteMap_Blog($vbulletin);
	$settings = $sitemap->get_priorities('blog');

	print_form_header('sitemap', 'saveblog');
	print_table_header($vbphrase['blog_priority_manager']);
	print_description_row($vbphrase['sitemap_blog_priority_desc']);
	print_cells_row(array(
		$vbphrase['default_sitemap_priority'],
		"<input name=\"default\" value=\"" . $settings['default'] . "\" />")	, 1);
	print_table_header($vbphrase['featured_authors']);

	print_cells_row(array(
		$vbphrase['enter_author_name'], $vbphrase['add_this_many_points']	 )
		, 1, 'tcat');
	$username = "
	<div id=\"userfield$i\" class=\"popupmenu nomouseover noclick nohovermenu\">

	<input type=\"text\" class=\"textbox popupctrl\" name=\"newauthor\" id=\"userfield" .
	$i . "_txt\" tabindex=\"$i\" value=\"\" />
	</div>\n";
	print_cells_row(array(
		$username,
		"<input name=\"newauthor_pts\" style=\"width:35px\"/>"));

	//existing authors
	if ($settings['authors'])
	{
		$remove = $vbphrase['remove'];
		print_table_header($vbphrase['delete_or_modify_authors']);
		foreach ($settings['authors'] as $userid => $userinfo )
		{
			print_cells_row(array(
				"<label for=\"del_author_$userid\">" . $userinfo['name'] . "</label> <input type=\"checkbox\" name=\"del_author_$userid\" id=\"del_author_$userid\">$remove",
				"<input name=\"author_pts_$userid\" value=\"" . $userinfo['weight'] . "\" style=\"width:35px\" />") );

		}
	}
	print_table_header($vbphrase['age_and_popularity_desc']);

	print_cells_row(array(
		$vbphrase['remove_per_mo_since_post'],
		"<input name=\"age_pts\" value=\"" . $settings['age_pts'] . "\" style=\"width:35px\" />"));
	print_cells_row(array(
		$vbphrase['but_not_more_than'],
		"<input name=\"age_max\" value=\"" . $settings['age_max'] . "\" style=\"width:35px\" />"));
	print_cells_row(array(
		$vbphrase['remove_per_mo_since_comment'] ,
		"<input name=\"c_age_pts\" value=\"" . $settings['c_age_pts'] . "\" style=\"width:35px\" />"));
	print_cells_row(array(
		$vbphrase['but_not_more_than'],
		"<input name=\"c_age_max\" value=\"" . $settings['c_age_max'] . "\" style=\"width:35px\" />"));
	print_cells_row(array(
		$vbphrase['add_per_ten_comments'],
		"<input name=\"comm_pts\" value=\"" . $settings['comm_pts'] . "\" style=\"width:35px\" />"));
	print_cells_row(array(
		$vbphrase['but_not_more_than'],
		"<input name=\"comm_max\" value=\"" . $settings['comm_max'] . "\" style=\"width:35px\" />"));

	($hook = vBulletinHook::fetch_hook('sitemap_blog_row')) ? eval($hook) : false;

	print_submit_row($vbphrase['save_priority']);
}

// ########################################################################
if ($_POST['do'] == 'saveblog')
{
	$sitemap = new vB_SiteMap_Blog($vbulletin);

	define('CP_REDIRECT', 'sitemap.php?do=blog');

	if ($sitemap->save_data())
	{
		print_stop_message('saved_content_priority_successfully');
	}
}

// ########################################################################
if ($_REQUEST['do'] == 'cms')
{

	require_once DIR . '/includes/functions.php';
	// Get the custom forum priorities
	$sitemap = new vB_SiteMap_Cms($vbulletin);

	print_form_header('sitemap', 'savecms');
	print_table_header($vbphrase['cms_priority_manager']);
	print_description_row(construct_phrase($vbphrase['sitemap_cms_priority_desc_x'],
		vb_number_format($vbulletin->options['sitemap_priority'], 1)));

	$sections = $sitemap->get_priorities('cms');

		if (! empty($sections))
	{
		$route = vB_Route::create('vBCms_Route_Content');
		foreach($sections AS $sectionid => $section)
		{

			if ($section['priority'] === false)
			{
				$section['priority'] = 'default';
			}

			if (isset($section['nodeid']) AND intval($section['nodeid']))
			{
				$cell = array();
				$route->node = $section['nodeid'] . (empty($section['url']) ? '' : '-' . $section['url']);
				$pageurl = $route->getCurrentURL();

				$cell[] = "<b>" . construct_depth_mark($section['depth'], '- - ')
					. "<a href=\"$pageurl\">$section[title]</a></b>";

				$cell[] = "\n\t<select name=\"f[$section[nodeid]]\" class=\"bginput\">\n"
					. construct_select_options($default_settings, $section['priority'])
					. " />\n\t";

				if ($forum['parentid'] == -1)
				{
					print_cells_row(array(
						$vbphrase['vbcms'],
						construct_phrase($vbphrase['priority_default_x'], vb_number_format($vbulletin->options['sitemap_priority'], 1))
					), 1, '');
				}

				($hook = vBulletinHook::fetch_hook('sitemap_cms_row')) ? eval($hook) : false;

				print_cells_row($cell);
			}
		}
	}

	print_submit_row($vbphrase['save_priority']);
}

// ########################################################################
if ($_POST['do'] == 'savecms')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'f' => TYPE_ARRAY_STR
	));

	// Custom values to remove
	$update_values = array();

	foreach ($vbulletin->GPC['f'] AS $nodeid => $priority)
	{
		if ($priority == 'default')
		{
			$vbulletin->db->query("
				DELETE FROM " . TABLE_PREFIX . "contentpriority
				WHERE contenttypeid = 'cms' AND sourceid = " . intval($nodeid)
			);
		}
		else
		{
			$update_values[] = "('cms', " . intval($nodeid) . "," . floatval($priority) . ")";
		}
	}

	// If there are any with custom values, set them
	if (count($update_values))
	{
		$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "contentpriority
				(contenttypeid, sourceid, prioritylevel)
			VALUES
				" . implode(',', $update_values)
		);
	}

	define('CP_REDIRECT', 'sitemap.php?do=cms');
	print_stop_message('saved_content_priority_successfully');
}


// ########################################################################
if ($_REQUEST['do'] == 'removesession')
{
	print_form_header('sitemap', 'doremovesession');
	print_table_header($vbphrase['remove_sitemap_session']);
	print_description_row($vbphrase['are_you_sure_remove_sitemap_session']);
	print_submit_row($vbphrase['remove_sitemap_session'], null);
}

// ########################################################################
if ($_POST['do'] == 'doremovesession')
{
	// reset the build time to be the next time the cron is supposed to run based on schedule (in case we're in the middle of running it)
	require_once(DIR . '/includes/functions_cron.php');
	$cron = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "cron WHERE filename = './includes/cron/sitemap.php'");
	if ($cron)
	{
		build_cron_item($cron['cronid'], $cron);
	}

	$vbulletin->db->query("DELETE FROM " . TABLE_PREFIX . "adminutil WHERE title = 'sitemapsession'");

	$_REQUEST['do'] = 'buildsitemap';
}

// ########################################################################
if ($_REQUEST['do'] == 'buildsitemap')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'success' => TYPE_BOOL
	));

	if ($vbulletin->GPC['success'])
	{
		print_table_start();
		print_description_row($vbphrase['sitemap_built_successfully_view_here'], false, 2, '', 'center');
		print_table_footer();
	}

	$runner = new vB_SiteMapRunner_Admin($vbulletin);

	$status = $runner->check_environment();
	if ($status['error'])
	{
		$sitemap_session = $runner->fetch_session();
		if ($sitemap_session['state'] != 'start')
		{
			print_table_start();
			print_description_row('<a href="sitemap.php?do=removesession">' . $vbphrase['remove_sitemap_session'] . '</a>', false, 2, '', 'center');
			print_table_footer();
		}

		print_stop_message($status['error']);
	}

	// Manual Sitemap Build
	print_form_header('sitemap', 'dobuildsitemap');
	print_table_header($vbphrase['build_sitemap']);
	print_description_row($vbphrase['use_to_build_sitemap']);
	print_submit_row($vbphrase['build_sitemap'], null);
}

// ########################################################################
if ($_POST['do'] == 'dobuildsitemap')
{
	$runner = new vB_SiteMapRunner_Admin($vbulletin);

	$status = $runner->check_environment();
	if ($status['error'])
	{
		print_stop_message($status['error']);
	}

	echo '<div>' . construct_phrase($vbphrase['processing_x'], '...') . '</div>';
	vbflush();

	$runner->generate();

	if ($runner->is_finished)
	{
		print_cp_redirect('sitemap.php?do=buildsitemap&success=1');
	}
	else
	{
		echo '<div>' . construct_phrase($vbphrase['processing_x'], $runner->written_filename) . '</div>';

		print_form_header('sitemap', 'dobuildsitemap', false, true, 'cpform_dobuildsitemap');
		print_submit_row($vbphrase['next_page'], 0);
		print_form_auto_submit('cpform_dobuildsitemap');
	}
}

($hook = vBulletinHook::fetch_hook('sitemap_admin_complete')) ? eval($hook) : false;

// ########################################################################

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision:  $
|| ####################################################################
\*======================================================================*/
