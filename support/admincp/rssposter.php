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
define('CVS_REVISION', '$RCSfile$ - $Revision: 63865 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cron', 'cpuser', 'prefix');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_prefix.php');

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ############################# LOG ACTION ###############################
log_admin_action(!empty($vbulletin->GPC['rssfeedid']) ? 'RSS feed id = ' . $vbulletin->GPC['rssfeedid'] : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadmincron'))
{
	print_cp_no_permission();
}

// #############################################################################
if ($_POST['do'] == 'updatestatus')
{
	$vbulletin->input->clean_gpc('p', 'enabled', TYPE_ARRAY_UINT);

	$feeds_result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "rssfeed ORDER BY title");
	while ($feed = $db->fetch_array($feeds_result))
	{
		$old = ($feed['options'] & $vbulletin->bf_misc_feedoptions['enabled'] ? 1 : 0);
		$new = ($vbulletin->GPC['enabled']["$feed[rssfeedid]"] ? 1 : 0);

		if ($old != $new)
		{
			$feeddata =& datamanager_init('RSSFeed', $vbulletin, ERRTYPE_ARRAY);
			$feeddata->set_existing($feed);
			$feeddata->set_bitfield('options', 'enabled', $new);
			$feeddata->save();
		}
	}

	exec_header_redirect('rssposter.php');
}

print_cp_header($vbphrase['rss_feed_manager']);

// #############################################################################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_gpc('p', 'rssfeedid', TYPE_UINT);

	if ($vbulletin->GPC['rssfeedid'] AND $feed = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "rssfeed WHERE rssfeedid = " . $vbulletin->GPC['rssfeedid']))
	{
		$feeddata =& datamanager_init('RSSFeed', $vbulletin, ERRTYPE_ARRAY);
		$feeddata->set_existing($feed);
		$feeddata->delete();

		define('CP_REDIRECT', 'rssposter.php');
		print_stop_message('deleted_rssfeed_x_successfully', $feeddata->fetch_field('title'));
	}
	else
	{
		echo "Kill oops";
	}
}

// #############################################################################

if ($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'rssfeedid', TYPE_UINT);

	if ($vbulletin->GPC['rssfeedid'] AND $feed = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "rssfeed WHERE rssfeedid = " . $vbulletin->GPC['rssfeedid']))
	{
		print_delete_confirmation('rssfeed', $vbulletin->GPC['rssfeedid'], 'rssposter', 'kill');
	}
	else
	{
		echo "Delete oops";
	}
}

// #############################################################################

// this array is used by do=preview and do=update
$input_vars = array(
	'rssfeedid'         => TYPE_UINT,
	'title'             => TYPE_NOHTML,
	'url'               => TYPE_STR,
	'ttl'               => TYPE_UINT,
	'maxresults'        => TYPE_UINT,
	'titletemplate'     => TYPE_STR,
	'bodytemplate'      => TYPE_STR,
	'username'          => TYPE_NOHTML,
	'forumid'           => TYPE_UINT,
	'prefixid'          => TYPE_NOHTML,
	'iconid'            => TYPE_UINT,
	'searchwords'       => TYPE_STR,
	'itemtype'          => TYPE_STR,
	'threadactiondelay' => TYPE_UINT,
	'endannouncement'   => TYPE_UINT,
	'resetlastrun'      => TYPE_BOOL,
	'options'           => TYPE_ARRAY_BOOL
);

// #############################################################################

if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', $input_vars);
	if (empty($vbulletin->GPC['url']))
	{
		print_stop_message('upload_invalid_url');
	}

	if (empty($_POST['preview']))
	{
		if ($vbulletin->GPC['rssfeedid'])
		{
			// update to follow
			$feed = $db->query_first("
				SELECT rssfeed.*
				FROM " . TABLE_PREFIX . "rssfeed AS rssfeed
				INNER JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = rssfeed.userid)
				WHERE rssfeed.rssfeedid = " . $vbulletin->GPC['rssfeedid'] . "
			");
		}
		else
		{
			$feed = array();
		}

		$feeddata =& datamanager_init('RSSFeed', $vbulletin, ERRTYPE_ARRAY);

		if (!empty($feed))
		{
			// doing an update, provide existing data to datamanager
			$feeddata->set_existing($feed);
		}

		$feeddata->set('title', $vbulletin->GPC['title']);
		$feeddata->set('url', $vbulletin->GPC['url']);
		$feeddata->set('ttl', $vbulletin->GPC['ttl']);
		$feeddata->set('maxresults',$vbulletin->GPC['maxresults']);
		$feeddata->set('titletemplate', $vbulletin->GPC['titletemplate']);
		$feeddata->set('bodytemplate', $vbulletin->GPC['bodytemplate']);
		$feeddata->set('searchwords', $vbulletin->GPC['searchwords']);
		$feeddata->set('forumid', $vbulletin->GPC['forumid']);
		$feeddata->set('prefixid', $vbulletin->GPC['prefixid']);
		$feeddata->set('iconid', $vbulletin->GPC['iconid']);
		$feeddata->set('threadactiondelay', $vbulletin->GPC['threadactiondelay']);
		$feeddata->set('itemtype', $vbulletin->GPC['itemtype']);
		$feeddata->set('endannouncement', $vbulletin->GPC['endannouncement']);
		$feeddata->set_user_by_name($vbulletin->GPC['username']);

		if ($vbulletin->GPC['resetlastrun'])
		{
			$feeddata->set('lastrun', 0);
		}

		foreach ($vbulletin->GPC['options'] AS $bitname => $value)
		{
			$feeddata->set_bitfield('options', $bitname, $value);
		}

		if ($feeddata->has_errors(false))
		{
			$feed = array();
			foreach ($input_vars AS $varname => $foo)
			{
				$feed["$varname"] = $vbulletin->GPC["$varname"];
			}

			foreach ($feeddata->errors AS $error)
			{
				echo "<div>$error</div>";
			}

			define('FEED_SAVE_ERROR', true);
			$_REQUEST['do'] = 'edit';
		}
		else
		{
			$feeddata->save();

			define('CP_REDIRECT', 'rssposter.php');
			print_stop_message('saved_rssfeed_x_successfully', $feeddata->fetch_field('title'));
		}
	}
	else
	{
		$_POST['do'] = 'preview';
	}
}

// #############################################################################

if ($_POST['do'] == 'preview')
{
	require_once(DIR . '/includes/class_rss_poster.php');
	require_once(DIR . '/includes/class_wysiwygparser.php');
	$html_parser = new vB_WysiwygHtmlParser($vbulletin);

	$xml = new vB_RSS_Poster($vbulletin);
	$xml->fetch_xml($vbulletin->GPC['url']);

	if (empty($xml->xml_string))
	{
		print_stop_message('unable_to_open_url');
	}
	else if ($xml->parse_xml() === false)
	{
		print_stop_message('xml_error_x_at_line_y', ($xml->feedtype == 'unknown' ? 'Unknown Feed Type' : $xml->xml_object->error_string()), $xml->xml_object->error_line());
	}

	require_once(DIR . '/includes/class_bbcode.php');
	$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	$output = '';
	$count = 0;
	foreach ($xml->fetch_items() AS $item)
	{
		if ($vbulletin->GPC['maxresults'] AND $count++ >= $vbulletin->GPC['maxresults'])
		{
			break;
		}
		if (!empty($item['content:encoded']))
		{
			$content_encoded = true;
		}

		$title = $bbcode_parser->parse(strip_bbcode($html_parser->parse_wysiwyg_html_to_bbcode($xml->parse_template($vbulletin->GPC['titletemplate'], $item))), 0, false);

		if ($vbulletin->GPC['options']['html2bbcode'])
		{
			$body_template = nl2br($vbulletin->GPC['bodytemplate']);
		}
		else
		{
			$body_template = $vbulletin->GPC['bodytemplate'];
		}

		$body = $xml->parse_template($body_template, $item);
		if ($vbulletin->GPC['options']['html2bbcode'])
		{
			$body = $html_parser->parse_wysiwyg_html_to_bbcode($body, false, true);
		}
		$body = $bbcode_parser->parse($body, 0, false);

		$output .= '<div class="alt2" style="border:inset 1px; padding:5px; width:400px; height: 175px; margin:10px; overflow: auto;"><h3><em>' . $title . '</em></h3>' . $body . '</div>';
	}

	$feed = array();
	foreach ($input_vars AS $varname => $foo)
	{
		$feed["$varname"] = $vbulletin->GPC["$varname"];
	}

	define('FEED_SAVE_ERROR', true);
	$_REQUEST['do'] = 'edit';

	print_form_header('', '');
	print_table_header($vbphrase['preview_feed']);
	if ($content_encoded)
	{
		print_description_row($vbphrase['feed_supports_content_encoded']);
	}
	print_description_row($output);
	print_table_footer();
}

// #############################################################################

if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array('rssfeedid' => TYPE_UINT));

	if (defined('FEED_SAVE_ERROR') AND is_array($feed))
	{
		// save error, show stuff again
		$form_title = ($feed['rssfeedid'] ? $vbphrase['edit_rss_feed'] : $vbphrase['add_new_rss_feed']);
	}
	else if ($vbulletin->GPC['rssfeedid'] AND $feed = $db->query_first("
		SELECT rssfeed.*, user.username
		FROM " . TABLE_PREFIX . "rssfeed AS rssfeed
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = rssfeed.userid)
		WHERE rssfeed.rssfeedid = " . $vbulletin->GPC['rssfeedid'] . "
	"))
	{
		// feed is defined
		$form_title = $vbphrase['edit_rss_feed'];
	}
	else
	{
		// add new feed
		$feed = array(
			'options'         => 1025,
			'ttl'             => 1800,
			'maxresults'      => 0,
			'endannouncement' => 7,
			'titletemplate'   => $vbphrase['rssfeed_title_template'],
			'bodytemplate'    => $vbphrase['rssfeed_body_template'],
			'itemtype'        => 'thread'
		);
		$form_title = $vbphrase['add_new_rss_feed'];
	}

	$checked = array();

	if (!defined('FEED_SAVE_ERROR') AND !is_array($feed['options']))
	{
		$feed['options'] = convert_bits_to_array($feed['options'], $vbulletin->bf_misc_feedoptions);
	}

	foreach ($feed['options'] AS $bitname => $bitvalue)
	{
		$checked["$bitname"] = ($bitvalue ? ' checked="checked"' : '');
	}

	$checked['itemtype']["$feed[itemtype]"] = ' checked="checked"';

	print_form_header('rssposter', 'update');
	print_table_header($form_title);
	if ($feed['rssfeedid'])
	{
		print_checkbox_row($vbphrase['reset_last_checked_time'], 'resetlastrun', 0, 1, "<span class=\"normal\">$vbphrase[reset]</span>");
	}
	print_yes_no_row($vbphrase['feed_is_enabled'], 'options[enabled]', $feed['options']['enabled']);
	print_input_row($vbphrase['title'], 'title', $feed['title'], false, 50);
	print_input_row($vbphrase['url_of_feed'], 'url', $feed['url'], true, 50);
	print_select_row($vbphrase['check_feed_every'], 'ttl', array(
		600  => construct_phrase($vbphrase['x_minutes'], 10),
		1200 => construct_phrase($vbphrase['x_minutes'], 20),
		1800 => construct_phrase($vbphrase['x_minutes'], 30),
		3600 => construct_phrase($vbphrase['x_minutes'], 60),
		7200 => construct_phrase($vbphrase['x_hours'], 2),
	  14400 => construct_phrase($vbphrase['x_hours'], 4),
	  21600 => construct_phrase($vbphrase['x_hours'], 6),
	  28800 => construct_phrase($vbphrase['x_hours'], 8),
	  36000 => construct_phrase($vbphrase['x_hours'], 10),
	  43200 => construct_phrase($vbphrase['x_hours'], 12),
	), $feed['ttl']);
	print_input_row($vbphrase['maximum_items_to_fetch'], 'maxresults', $feed['maxresults'], true, 50);
	print_label_row($vbphrase['search_items_for_words'],'
		<div><textarea name="searchwords" rows="5" cols="50" tabindex="1">' . $feed['searchwords'] . '</textarea></div>
		<input type="hidden" name="options[searchboth]" value="0" />
		<input type="hidden" name="options[matchall]" value="0" />
		<div class="smallfont">
			<label for="cb_searchboth"><input type="checkbox" name="options[searchboth]" id="cb_searchboth" value="1" tabindex="1"' . $checked['searchboth'] . ' />' . $vbphrase['search_item_body'] . '</label>
			<label for="cb_matchall"><input type="checkbox" name="options[matchall]" id="cb_matchall" value="1" tabindex="1"' . $checked['matchall'] . ' />' . $vbphrase['match_all_words'] . '</label>
		</div>
	', '', 'top', 'searchwords');
	print_input_row($vbphrase['username'], 'username', $feed['username'], false, 50);
	print_forum_chooser($vbphrase['forum'], 'forumid', $feed['forumid'], null, true, false, '[%s]');
	print_yes_no_row($vbphrase['allow_smilies'], 'options[allowsmilies]', $feed['options']['allowsmilies']);
	print_yes_no_row($vbphrase['display_signature'], 'options[showsignature]', $feed['options']['showsignature']);
	print_yes_no_row($vbphrase['convert_html_to_bbcode'], 'options[html2bbcode]', $feed['options']['html2bbcode']);

	print_table_header($vbphrase['templates']);
	print_description_row('<div class="smallfont">' . $vbphrase['rss_templates_description'] . '</div>');
	print_input_row($vbphrase['title_template'], 'titletemplate', $feed['titletemplate'], true, 50);
	print_textarea_row($vbphrase['body_template'], 'bodytemplate', $feed['bodytemplate'], 10, 50);

	print_description_row('<label for="rb_itemtype_thread"><input type="radio" name="itemtype" value="thread" id="rb_itemtype_thread"' . $checked['itemtype']['thread'] . "  />$vbphrase[post_items_as_threads]</label>", false, 2, 'thead', 'left', 'itemtype');
	if ($prefix_options = construct_prefix_options(0, $feed['prefixid']))
	{
		print_label_row(
			$vbphrase['prefix'] . '<dfn>' . $vbphrase['note_prefix_must_allowed_forum'] . '</dfn>',
			'<select name="prefixid" class="bginput">' . $prefix_options . '</select>',
			'', 'top', 'prefixid'
		);
	}

	// build thread icon picker
	$icons = array();
	$icons_result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "icon ORDER BY imagecategoryid, displayorder");
	$icons_total = $db->num_rows($icons_result);
	while ($icon = $db->fetch_array($icons_result))
	{
		$icons[] = $icon;
	}
	$db->free_result($icons_result);

	$icon_count = 0;
	$icon_cols = 7;
	$icon_rows = ceil($icons_total / $icon_cols);

	// build icon html
	$icon_html = "<table cellpadding=\"0\" cellspacing=\"2\" border=\"0\" width=\"100%\">";

	for ($i = 0; $i < $icon_rows; $i++)
	{
		$icon_html .= "<tr>";

		for ($j = 0; $j < $icon_cols; $j++)
		{
			if ($icons["$icon_count"])
			{
				$icon =& $icons["$icon_count"];
				if (strtolower(substr($icon['iconpath'], 0, 4)) != 'http' AND substr($icon['iconpath'], 0, 1) != '/')
				{
					$icon['iconpath'] = "../$icon[iconpath]";
				}
				$icon_html .= "<td class=\"smallfont\"><label for=\"rb_icon_$icon[iconid]\" title=\"$icon[title]\"><input type=\"radio\" name=\"iconid\" value=\"$icon[iconid]\" tabindex=\"1\" id=\"rb_icon_$icon[iconid]\"" . ($feed['iconid'] == $icon['iconid'] ? ' checked="checked"' : '') . " /><img src=\"$icon[iconpath]\" alt=\"$icon[title]\" /></label></td>";
				$icon_count++;
			}
			else
			{
				$remaining_cols = $icon_cols - $j;
				$icon_html .= "<td class=\"smallfont\" colspan=\"$remaining_cols\">&nbsp;</td>";
				break;
			}
		}

		$icon_html .= '</tr>';
	}
	$icon_html .= "<tr><td colspan=\"$icon_cols\" class=\"smallfont\"><label for=\"rb_icon_0\" title=\"$icon[title]\"><input type=\"radio\" name=\"iconid\" value=\"0\" tabindex=\"1\" id=\"rb_icon_0\"" . ($feed['iconid'] == 0 ? ' checked="checked"' : '') . " />$vbphrase[no_icon]</label></td></tr></table>";

	print_label_row($vbphrase['post_icons'], $icon_html, '', 'top', 'iconid');
	print_yes_no_row($vbphrase['make_thread_sticky'], 'options[stickthread]', $feed['options']['stickthread']);
	print_yes_no_row($vbphrase['moderate_thread'], 'options[moderatethread]', $feed['options']['moderatethread']);
	print_input_row($vbphrase['thread_action_delay'], 'threadactiondelay', $feed['threadactiondelay']);
	print_yes_no_row($vbphrase['unstick_thread_after_delay'], 'options[unstickthread]', $feed['options']['unstickthread']);
	print_yes_no_row($vbphrase['close_thread_after_delay'], 'options[closethread]', $feed['options']['closethread']);
	print_description_row('<label for="rb_itemtype_announcement"><input type="radio" name="itemtype" value="announcement" id="rb_itemtype_announcement"' . $checked['itemtype']['announcement'] . "  />$vbphrase[post_items_as_announcements]</label>", false, 2, 'thead', 'left', 'itemtype');
	print_yes_no_row($vbphrase['allow_html_in_announcements'], 'options[allowhtml]', $feed['options']['allowhtml']);
	print_input_row($vbphrase['days_for_announcement_to_remain_active'], 'endannouncement', $feed['endannouncement']);
	construct_hidden_code('rssfeedid', $feed['rssfeedid']);
	print_submit_row('', $vbphrase['reset'], 2, '', "<input type=\"submit\" class=\"button\" name=\"preview\" tabindex=\"1\" accesskey=\"p\" value=\"$vbphrase[preview_feed]\" />");
}

if ($_REQUEST['do'] == 'modify')
{
	$feeds = array();

	$feeds_result = $db->query_read("
		SELECT rssfeed.*, user.username, forum.title AS forumtitle
		FROM " . TABLE_PREFIX . "rssfeed AS rssfeed
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = rssfeed.userid)
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum ON(forum.forumid = rssfeed.forumid)
		ORDER BY rssfeed.title
	");
	if ($db->num_rows($feeds_result))
	{
		while ($feed = $db->fetch_array($feeds_result))
		{
			$feeds["$feed[rssfeedid]"] = $feed;
		}
	}
	$db->free_result($feeds_result);

	if (empty($feeds))
	{
		print_stop_message('no_feeds_defined', $vbulletin->session->vars['sessionurl']);
	}
	else
	{
		print_form_header('rssposter', 'updatestatus');
		print_table_header($vbphrase['rss_feed_manager'], 5);
		print_cells_row(array(
			'',
			$vbphrase['rss_feed'],
			$vbphrase['forum'] . ' / ' . $vbphrase['username'],
			$vbphrase['last_checked'],
			$vbphrase['controls']
		), true, '', -4);

		foreach ($feeds AS $rssfeedid => $feed)
		{
			$x = $vbulletin->input->parse_url($feed['url']);

			if ($feed['lastrun'] > 0)
			{
				$date = vbdate($vbulletin->options['dateformat'], $feed['lastrun'], true);
				$time = vbdate($vbulletin->options['timeformat'], $feed['lastrun']);
				$datestring = $date . ($vbulletin->options['yestoday'] == 2 ? '' : ", $time");
			}
			else
			{
				$datestring = '-';
			}

			print_cells_row(array(
				"<input type=\"checkbox\" name=\"enabled[$rssfeedid]\" value=\"$rssfeedid\" title=\"$vbphrase[enabled]\"" . ($feed['options'] & $vbulletin->bf_misc_feedoptions['enabled'] ? ' checked="checked"' : '') . " />",
				"<div><a href=\"rssposter.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;rssfeedid=$feed[rssfeedid]\" title=\"" . htmlspecialchars_uni($feed['url']) . "\"><strong>$feed[title]</strong></a></div>
				<div class=\"smallfont\"><a href=\"" . htmlspecialchars_uni($feed['url']) . "\" target=\"feed\">$x[host]</a></div>",
				"<div><a href=\"forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;forumid=$feed[forumid]\">$feed[forumtitle]</a></div>
				<div class=\"smallfont\"><a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;userid=$feed[userid]\">$feed[username]</a></div>",
				"<span class=\"smallfont\">$datestring</span>",
				construct_link_code($vbphrase['edit'], "rssposter.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;rssfeedid=$feed[rssfeedid]") .
				construct_link_code($vbphrase['delete'], "rssposter.php?" . $vbulletin->session->vars['sessionurl'] . "do=delete&amp;rssfeedid=$feed[rssfeedid]")
			), false, '', -4);
		}

		print_submit_row(
			$vbphrase['save_enabled_status'], false, 5, '',
			"
				<input type=\"button\" class=\"button\" value=\"$vbphrase[run_scheduled_task_now]\" onclick=\"window.location='cronadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=runcron&amp;varname=rssposter'\" />
				<input type=\"button\" class=\"button\" value=\"$vbphrase[add_new_rss_feed]\" onclick=\"window.location='rssposter.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit'\" />
			");
	}
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63865 $
|| ####################################################################
\*======================================================================*/
?>