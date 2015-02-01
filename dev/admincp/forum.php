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
define('CVS_REVISION', '$RCSfile$ - $Revision: 62096 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('forum', 'cpuser', 'forumdisplay', 'prefix');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_template.php');
require_once(DIR . '/includes/adminfunctions_forums.php');
require_once(DIR . '/includes/adminfunctions_prefix.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminforums'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################


$vbulletin->input->clean_array_gpc('r', array(
	'moderatorid' 	=> TYPE_UINT,
	'forumid'		=> TYPE_UINT
));

log_admin_action(iif($vbulletin->GPC['moderatorid'] != 0, " moderator id = " . $vbulletin->GPC['moderatorid'],
						iif($vbulletin->GPC['forumid'] != 0, "forum id = " . $vbulletin->GPC['forumid'])));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['forum_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

($hook = vBulletinHook::fetch_hook('forumadmin_start')) ? eval($hook) : false;

// ###################### Start add #######################
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'forumid'			=> TYPE_UINT,
		'defaultforumid'	=> TYPE_UINT,
		'parentid'			=> TYPE_UINT
	));

	if ($_REQUEST['do'] == 'add')
	{
		// get a list of other usergroups to base this one off of
		print_form_header('forum', 'add');
		print_description_row(construct_table_help_button('defaultforumid') . '<b>' . $vbphrase['create_forum_based_off_of_forum'] . '</b> <select name="defaultforumid" tabindex="1" class="bginput">' . construct_forum_chooser() . '</select> <input type="submit" class="button" value="' . $vbphrase['go'] . '" tabindex="1" />', 0, 2, 'tfoot', 'center');
		print_table_footer();
		// Set Defaults;
		$forum = array(
			'title' => '',
			'description' => '',
			'link' => '',
			'displayorder' => 1,
			'daysprune' => -1,
			'parentid' => $vbulletin->GPC['parentid'],
			'showprivate' => 0,
			'newthreademail' => '',
			'newpostemail' => '',
			'moderatenewpost' => 0,
			'moderatenewthread' => 0,
			'moderateattach' => 0,
			'styleid' => '',
			'styleoverride' => 0,
			'password' => '',
			'canhavepassword' => 1,
			'cancontainthreads' => 1,
			'active' => 1,
			'allowposting' => 1,
			'indexposts' => 1,
			'bypassdp' => 0,
			'displaywrt' => 1,
			'canreputation' => 1,
			'allowhtml' => 0,
			'allowbbcode' => 1,
			'allowimages' => 1,
			'allowvideos'  => 1,
			'allowsmilies' => 1,
			'allowicons' => 1,
			'allowratings' => 1,
			'countposts' => 1,
			'showonforumjump' => 1,
			'defaultsortfield' => 'lastpost',
			'defaultsortorder' => 'desc',
   			'imageprefix' => '',
   			'prefixrequired' => 0
		);

		if (!empty($vbulletin->GPC['defaultforumid']))
		{
			$newforum = fetch_foruminfo($vbulletin->GPC['defaultforumid']);
			foreach (array_keys($forum) AS $title)
			{
				$forum["$title"] = $newforum["$title"];
			}
		}

		($hook = vBulletinHook::fetch_hook('forumadmin_add_default')) ? eval($hook) : false;

		print_form_header('forum', 'update');
		print_table_header($vbphrase['add_new_forum']);
	}
	else
	{
		if (!($forum = fetch_foruminfo($vbulletin->GPC['forumid'], false)))
		{
			print_stop_message('invalid_forum_specified');
		}
		print_form_header('forum', 'update');
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['forum'], $forum['title'], $forum['forumid']));
		construct_hidden_code('forumid', $vbulletin->GPC['forumid']);
	}

	$forum['title'] = str_replace('&amp;', '&', $forum['title']);
	$forum['description'] = str_replace('&amp;', '&', $forum['description']);

	print_input_row($vbphrase['title'], 'forum[title]', $forum['title']);
	print_textarea_row($vbphrase['description'], 'forum[description]', $forum['description']);
	print_input_row($vbphrase['forum_link'], 'forum[link]', $forum['link']);
	print_input_row("$vbphrase[display_order]<dfn>$vbphrase[zero_equals_no_display]</dfn>", 'forum[displayorder]', $forum['displayorder']);
	//print_input_row($vbphrase['default_view_age'], 'forum[daysprune]', $forum['daysprune']);

	if ($vbulletin->GPC['forumid'] != -1)
	{
		print_forum_chooser($vbphrase['parent_forum'], 'forum[parentid]', $forum['parentid'], $vbphrase['no_one']);
	}
	else
	{
		construct_hidden_code('parentid', 0);
	}

	// make array for daysprune menu
	$pruneoptions = array(
		'1' => $vbphrase['show_threads_from_last_day'],
		'2' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 2),
		'7' => $vbphrase['show_threads_from_last_week'],
		'10' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 10),
		'14' => construct_phrase($vbphrase['show_threads_from_last_x_weeks'], 2),
		'30' => $vbphrase['show_threads_from_last_month'],
		'45' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 45),
		'60' => construct_phrase($vbphrase['show_threads_from_last_x_months'], 2),
		'75' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 75),
		'100' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 100),
		'365' => $vbphrase['show_threads_from_last_year'],
		'-1' => $vbphrase['show_all_threads']
	);

	print_select_row($vbphrase['default_view_age'], 'forum[daysprune]', $pruneoptions, $forum['daysprune']);

	$sort_fields = array(
		'title'        => $vbphrase['thread_title'],
		'lastpost'     => $vbphrase['last_post_time'],
		'dateline'     => $vbphrase['thread_start_time'],
		'replycount'   => $vbphrase['number_of_replies'],
		'views'        => $vbphrase['number_of_views'],
		'postusername' => $vbphrase['thread_starter'],
		'voteavg'      => $vbphrase['thread_rating']
	);
	print_select_row($vbphrase['default_sort_field'], 'forum[defaultsortfield]', $sort_fields, $forum['defaultsortfield']);
	print_select_row($vbphrase['default_sort_order'], 'forum[defaultsortorder]', array('asc' => $vbphrase['ascending'], 'desc' => $vbphrase['descending']), $forum['defaultsortorder']);

	print_select_row($vbphrase['show_private_forum'], 'forum[showprivate]', array($vbphrase['use_default'], $vbphrase['no'], $vbphrase['yes_hide_post_counts'], $vbphrase['yes_display_post_counts']), $forum['showprivate']);


	print_table_header($vbphrase['moderation_options']);

	print_input_row($vbphrase['emails_to_notify_when_post'], 'forum[newpostemail]', $forum['newpostemail']);
	print_input_row($vbphrase['emails_to_notify_when_thread'], 'forum[newthreademail]', $forum['newthreademail']);

	print_yes_no_row($vbphrase['moderate_posts'] . ' <dfn>(' . $vbphrase['require_moderator_validation_before_new_posts_are_displayed'] . ')</dfn>', 'forum[options][moderatenewpost]', $forum['moderatenewpost']);
	print_yes_no_row($vbphrase['moderate_threads'] . ' <dfn>(' . $vbphrase['require_moderator_validation_before_new_threads_are_displayed'] . ')</dfn>', 'forum[options][moderatenewthread]', $forum['moderatenewthread']);
	print_yes_no_row($vbphrase['moderate_attachments'] . ' <dfn>(' . $vbphrase['require_moderator_validation_before_new_attachments_are_displayed'] . ')</dfn>', 'forum[options][moderateattach]', $forum['moderateattach']);

	print_table_header($vbphrase['style_options']);

	if ($forum['styleid'] == 0)
	{
		$forum['styleid'] = -1; // to get the "use default style" option selected
	}
	print_style_chooser_row('forum[styleid]', $forum['styleid'], array($vbphrase['use_default_style']), $vbphrase['custom_forum_style'], 1);
	print_yes_no_row($vbphrase['override_style_choice'], 'forum[options][styleoverride]', $forum['styleoverride']);
	print_input_row($vbphrase['prefix_for_forum_status_images'], 'forum[imageprefix]', $forum['imageprefix']);

	print_table_header($vbphrase['access_options']);

	print_input_row($vbphrase['forum_password'], 'forum[password]', $forum['password']);
	if ($_REQUEST['do'] == 'edit')
	{
		print_yes_no_row($vbphrase['apply_password_to_children'], 'applypwdtochild', 0);
	}
	print_yes_no_row($vbphrase['can_have_password'], 'forum[options][canhavepassword]', $forum['canhavepassword']);

	print_table_header($vbphrase['posting_options']);

	print_yes_no_row($vbphrase['act_as_forum'], 'forum[options][cancontainthreads]', $forum['cancontainthreads']);
	print_yes_no_row($vbphrase['forum_is_active'], 'forum[options][active]', $forum['active']);
	print_yes_no_row($vbphrase['forum_open'], 'forum[options][allowposting]', $forum['allowposting']);
	print_yes_no_row($vbphrase['index_new_posts'], 'forum[options][indexposts]' , $forum['indexposts'] );
	print_yes_no_row($vbphrase['bypass_double_posts'], 'forum[options][bypassdp]' , $forum['bypassdp'] );

	print_table_header($vbphrase['enable_disable_features']);

	print_yes_no_row($vbphrase['allow_html'], 'forum[options][allowhtml]', $forum['allowhtml']);
	print_yes_no_row($vbphrase['allow_bbcode'], 'forum[options][allowbbcode]', $forum['allowbbcode']);
	print_yes_no_row($vbphrase['allow_img_code'], 'forum[options][allowimages]', $forum['allowimages']);
	print_yes_no_row($vbphrase['allow_video_code'], 'forum[options][allowvideos]', $forum['allowvideos']);
	print_yes_no_row($vbphrase['allow_smilies'], 'forum[options][allowsmilies]', $forum['allowsmilies']);
	print_yes_no_row($vbphrase['allow_icons'], 'forum[options][allowicons]', $forum['allowicons']);
	print_yes_no_row($vbphrase['allow_thread_ratings_in_this_forum'], 'forum[options][allowratings]', $forum['allowratings']);
	print_yes_no_row($vbphrase['count_posts_in_forum'], 'forum[options][countposts]', $forum['countposts']);
	print_yes_no_row($vbphrase['show_forum_on_forum_jump'], 'forum[options][showonforumjump]', $forum['showonforumjump']);

	$prefixsets = construct_prefixset_checkboxes('prefixset', $vbulletin->GPC['defaultforumid'] ? $vbulletin->GPC['defaultforumid'] : $forum['forumid']);
	if ($prefixsets)
	{
		print_label_row($vbphrase['use_selected_prefix_sets'], $prefixsets, '', 'top', 'prefixset');
	}
	print_yes_no_row($vbphrase['require_threads_have_prefix'], 'forum[options][prefixrequired]', $forum['prefixrequired']);
	print_yes_no_row($vbphrase['display_whoread'], 'forum[options][displaywrt]' , $forum['displaywrt'] );
	print_yes_no_row($vbphrase['allow_reputation'], 'forum[options][canreputation]' , $forum['canreputation'] );

	($hook = vBulletinHook::fetch_hook('forumadmin_edit_form')) ? eval($hook) : false;

	print_submit_row($vbphrase['save']);
}

// ###################### Start update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'forumid'         => TYPE_UINT,
		'applypwdtochild' => TYPE_BOOL,
		'forum'           => TYPE_ARRAY,
		'prefixset'       => TYPE_ARRAY_NOHTML,
	));

	$forumdata =& datamanager_init('Forum', $vbulletin, ERRTYPE_CP);

	$forum_exists = false;
	if ($vbulletin->GPC['forumid'])
	{
		$forumdata->set_existing($vbulletin->forumcache[$vbulletin->GPC['forumid']]);
		$forumdata->set_info('applypwdtochild', $vbulletin->GPC['applypwdtochild']);

		$forum_exists = true;
	}

	foreach ($vbulletin->GPC['forum'] AS $varname => $value)
	{
		if ($varname == 'options')
		{
			foreach ($value AS $key => $val)
			{
				$forumdata->set_bitfield('options', $key, $val);
			}
		}
		else
		{
			$forumdata->set($varname, $value);
		}
	}

	($hook = vBulletinHook::fetch_hook('forumadmin_update_save')) ? eval($hook) : false;

	$forumid = $forumdata->save();
	if (!$vbulletin->GPC['forumid'])
	{
		$vbulletin->GPC['forumid'] = $forumid;
	}

	// find old sets
	$old_prefixsets = array();
	if ($forum_exists)
	{
		$set_list_sql = $db->query_read("
			SELECT prefixsetid
			FROM " . TABLE_PREFIX . "forumprefixset
			WHERE forumid = " . $vbulletin->GPC['forumid']
		);
		while ($set = $db->fetch_array($set_list_sql))
		{
			$old_prefixsets[] = $set['prefixsetid'];
		}
	}

	// setup prefixes
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "forumprefixset
		WHERE forumid = " . $vbulletin->GPC['forumid']
	);

	$add_prefixsets = array();
	foreach ($vbulletin->GPC['prefixset'] AS $prefixsetid)
	{
		$add_prefixsets[] = '(' . $vbulletin->GPC['forumid'] . ", '" . $db->escape_string($prefixsetid) . "')";
	}

	if ($add_prefixsets)
	{
		$db->query_write("
			INSERT IGNORE INTO " . TABLE_PREFIX . "forumprefixset
				(forumid, prefixsetid)
			VALUES
				" . implode(',', $add_prefixsets)
		);
	}

	$removed_sets = array_diff($old_prefixsets, $vbulletin->GPC['prefixset']);
	if ($removed_sets)
	{
		$removed_sets = array_map(array(&$db, 'escape_string'), $removed_sets);

		$prefixes = array();
		$prefix_sql = $db->query_read("
			SELECT prefixid
			FROM " . TABLE_PREFIX . "prefix
			WHERE prefixsetid IN ('" . implode("', '", $removed_sets) . "')
		");
		while ($prefix = $db->fetch_array($prefix_sql))
		{
			$prefixes[] = $prefix['prefixid'];
		}

		remove_prefixes_forum($prefixes, $vbulletin->GPC['forumid']);
	}

	require_once(DIR . '/includes/adminfunctions_prefix.php');
	build_prefix_datastore();


	// rebuild ad templates for ads using the 'browsing a forum' criteria
	$ad_result = $db->query_read("
		SELECT ad.*
		FROM " . TABLE_PREFIX . "ad AS ad
		LEFT JOIN " . TABLE_PREFIX . "adcriteria AS adcriteria ON(adcriteria.adid = ad.adid)
		WHERE (adcriteria.criteriaid = 'browsing_forum_x' OR adcriteria.criteriaid = 'browsing_forum_x_and_children')
	");
	if ($db->num_rows($ad_result) > 0)
	{
		$ad_cache = array();
		$ad_locations = array();

		while ($ad = $db->fetch_array($ad_result))
		{
			$ad_cache["$ad[adid]"] = $ad;
			$ad_locations[] = $ad['adlocation'];
		}

		require_once(DIR . '/includes/functions_ad.php');
		require_once(DIR . '/includes/adminfunctions_template.php');

		foreach($ad_locations AS $location)
		{
			$template = wrap_ad_template(build_ad_template($location), $location);

			$template_un = $template;
			$template = compile_template($template);

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "template SET
					template = '" . $db->escape_string($template) . "',
					template_un = '" . $db->escape_string($template_un) . "',
					dateline = " . TIMENOW . ",
					username = '" . $db->escape_string($vbulletin->userinfo['username']) . "'
				WHERE
					title = 'ad_" . $db->escape_string($location) . "'
					AND styleid IN (-2,-1,0)
			");
		}

		build_all_styles(0, 0, '', false, 'standard');
		build_all_styles(0, 0, '', false, 'mobile');
	}

	$db->free_result($ad_result);


	define('CP_REDIRECT', "forum.php?do=modify&amp;f=" . $vbulletin->GPC['forumid'] . "#forum" . $vbulletin->GPC['forumid']);
	print_stop_message('saved_forum_x_successfully', $vbulletin->GPC['forum']['title']);
}
// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array('forumid' => TYPE_UINT));

	print_delete_confirmation('forum', $vbulletin->GPC['forumid'], 'forum', 'kill', 'forum', 0, $vbphrase['are_you_sure_you_want_to_delete_this_forum'], 'title_clean');
}

// ###################### Start Kill #######################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'forumid' => TYPE_UINT
	));

	$forumdata =& datamanager_init('Forum', $vbulletin, ERRTYPE_CP);
	$forumdata->set_condition("FIND_IN_SET(" . $vbulletin->GPC['forumid'] . ", parentlist)");
	$forumdata->delete();

	define('CP_REDIRECT', 'forum.php');
	print_stop_message('deleted_forum_successfully');
}

// ###################### Start do order #######################
if ($_POST['do'] == 'doorder')
{
	$vbulletin->input->clean_array_gpc('p', array('order' => TYPE_ARRAY));

	if (is_array($vbulletin->GPC['order']))
	{
		$forums = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "forum");
		while ($forum = $db->fetch_array($forums))
		{
			if (!isset($vbulletin->GPC['order']["$forum[forumid]"]))
			{
				continue;
			}

			$displayorder = intval($vbulletin->GPC['order']["$forum[forumid]"]);
			if ($forum['displayorder'] != $displayorder)
			{
				$forumdm =& datamanager_init('Forum', $vbulletin, ERRTYPE_SILENT);
				$forumdm->set_existing($forum);
				$forumdm->setr('displayorder', $displayorder);
				$forumdm->save();
				unset($forumdm);
			}
		}
	}

	build_forum_permissions();

	define('CP_REDIRECT', 'forum.php?do=modify');
	print_stop_message('saved_display_order_successfully');
}

// ###################### Start forum_is_related_to_forum #######################
function forum_is_related_to_forum($partial_list, $forumid, $full_list)
{
	// This function is only used below, only for expand/collapse of forums.
	// If the first forum's parent list is contained within the second,
	// then it is considered related (think of it as an aunt or uncle forum).

	$partial = explode(',', $partial_list);
	if ($partial[0] == $forumid)
	{
		array_shift($partial);
	}
	$full = explode(',', $full_list);

	foreach ($partial AS $fid)
	{
		if (!in_array($fid, $full))
		{
			return false;
		}
	}

	return true;
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'forumid' 	=> TYPE_UINT,
		'expandid'	=> TYPE_INT,
	));

	if (!$vbulletin->GPC['expandid'])
	{
		$vbulletin->GPC['expandid'] = -1;
	}
	else if ($vbulletin->GPC['expandid'] == -2)
	{
		// expand all -- easiest to just turn off collapsing
		$vbulletin->options['cp_collapse_forums'] = false;
	}

	// a little javascript for the options menus
	?>
	<script type="text/javascript">
	<!--
	function js_forum_jump(foruminfo)
	{
		var cp_collapse_forums = <?php echo intval($vbulletin->options['cp_collapse_forums']); ?>;
		if (foruminfo == 0)
		{
			alert('<?php echo addslashes_js($vbphrase['please_select_forum']); ?>');
			return;
		}
		else if (typeof(document.cpform.forumid) != 'undefined')
		{
			action = document.cpform.controls.options[document.cpform.controls.selectedIndex].value;
		}
		else
		{
			action = eval("document.cpform.f" + foruminfo + ".options[document.cpform.f" + foruminfo + ".selectedIndex].value");
		}
		if (action != '')
		{
			switch (action)
			{
				case 'edit': page = "forum.php?do=edit&f="; break;
				case 'remove': page = "forum.php?do=remove&f="; break;
				case 'add': page = "forum.php?do=add&parentid="; break;
				case 'addmod': page = "moderator.php?do=add&f="; break;
				case 'listmod': page = "moderator.php?do=showmods&f=";break;
				case 'annc': page = "announcement.php?do=add&f="; break;
				case 'view': page = "../forumdisplay.php?f="; break;
				case 'perms':
					if (cp_collapse_forums > 0)
					{
						page = "forumpermission.php?do=modify&f=";
					}
					else
					{
						page = "forumpermission.php?do=modify&devnull=";
					}
					break;
				case 'podcast': page = "forum.php?do=podcast&f="; break;
				case 'empty': page = "forum.php?do=empty&f="; break;
			}
			document.cpform.reset();
			jumptopage = page + foruminfo + "&s=<?php echo $vbulletin->session->vars['sessionhash']; ?>";
			if (action == 'perms')
			{
				window.location = jumptopage + '#forum' + foruminfo;
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

	function js_moderator_jump(foruminfo)
	{
		if (foruminfo == 0)
		{
			alert('<?php echo addslashes_js($vbphrase['please_select_forum']); ?>');
			return;
		}
		else if (typeof(document.cpform.forumid) != 'undefined')
		{
			modinfo = document.cpform.moderator[document.cpform.moderator.selectedIndex].value;
		}
		else
		{
			modinfo = eval("document.cpform.m" + foruminfo + ".options[document.cpform.m" + foruminfo + ".selectedIndex].value");
			document.cpform.reset();
		}

		switch (modinfo)
		{
			case 'add': window.location = "moderator.php?s=<?php echo $vbulletin->session->vars['sessionhash']; ?>&do=add&f=" + foruminfo; break;
			case 'show': window.location = "moderator.php?s=<?php echo $vbulletin->session->vars['sessionhash']; ?>&do=showmods&f=" + foruminfo; break;
			case '': return false; break;
			default: window.location = "moderator.php?s=<?php echo $vbulletin->session->vars['sessionhash']; ?>&do=edit&moderatorid=" + modinfo; break;
		}
	}

	function js_returnid()
	{
		return document.cpform.forumid.value;
	}
	//-->
	</script>
	<?php

	$forumoptions1 = array(
		'edit'    => $vbphrase['edit_forum'],
		'view'    => $vbphrase['view_forum'],
		'remove'  => $vbphrase['delete_forum'],
		'add'     => $vbphrase['add_child_forum'],
		'addmod'  => $vbphrase['add_moderator'],
		'listmod' => $vbphrase['list_moderators'],
		'annc'    => $vbphrase['add_announcement'],
		'perms'   => $vbphrase['view_permissions'],
		'podcast' => $vbphrase['podcast_settings'],
	);

	$forumoptions2 = array(
		'edit'    => $vbphrase['edit_forum'],
		'view'    => $vbphrase['view_forum'],
		'remove'  => $vbphrase['delete_forum'],
		'add'     => $vbphrase['add_child_forum'],
		'addmod'  => $vbphrase['add_moderator'],
		'annc'    => $vbphrase['add_announcement'],
		'perms'   => $vbphrase['view_permissions'],
		'podcast' => $vbphrase['podcast_settings'],
	);

	require_once(DIR . '/includes/functions_databuild.php');

	if ($vbulletin->options['cp_collapse_forums'] != 2)
	{
		print_form_header('forum', 'doorder');
		print_table_header($vbphrase['forum_manager'], 4);
		print_description_row($vbphrase['if_you_change_display_order'], 0, 4);

		require_once(DIR . '/includes/functions_forumlist.php');
		cache_moderators();

		$forums = array();
		$expanddata = array('forumid' => -1, 'parentlist' => '');
		if (is_array($vbulletin->forumcache))
		{
			foreach($vbulletin->forumcache AS $forumid => $forum)
			{
				$forums["$forum[forumid]"] = construct_depth_mark($forum['depth'], '--') . ' ' . $forum['title'];
				if ($forum['forumid'] == $vbulletin->GPC['expandid'])
				{
					$expanddata = $forum;
				}
			}
		}
		$expanddata['parentids'] = explode(',', $expanddata['parentlist']);

		if ($vbulletin->options['cp_collapse_forums'])
		{
			$expandtext = '[-] ';
		}
		else
		{
			$expandtext = '';
		}

		if (is_array($vbulletin->forumcache))
		{
			foreach($vbulletin->forumcache AS $key => $forum)
			{
				$modcount = sizeof($imodcache["$forum[forumid]"]);
				if ($modcount)
				{
					$mainoptions =& $forumoptions1;
					$mainoptions['listmod'] = $vbphrase['list_moderators'] . " ($modcount)";
				}
				else
				{
					$mainoptions =& $forumoptions2;
				}

				$cell = array();
				if (!$vbulletin->options['cp_collapse_forums'] OR $forum['forumid'] == $expanddata['forumid'] OR in_array($forum['forumid'], $expanddata['parentids']))
				{
					$cell[] = "<a name=\"forum$forum[forumid]\">&nbsp;</a> $expandtext<b>" . construct_depth_mark($forum['depth'],'- - ') . "<a href=\"forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;f=$forum[forumid]\">$forum[title]</a>" . iif(!empty($forum['password']),'*') . " " . iif($forum['link'], "(<a href=\"" . htmlspecialchars_uni($forum['link']) . "\">" . $vbphrase['link'] . "</a>)") . "</b>";
					$cell[] = "\n\t<select name=\"f$forum[forumid]\" onchange=\"js_forum_jump($forum[forumid]);\" class=\"bginput\">\n" . construct_select_options($mainoptions) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_forum_jump($forum[forumid]);\" />\n\t";
					$cell[] = "<input type=\"text\" class=\"bginput\" name=\"order[$forum[forumid]]\" value=\"$forum[displayorder]\" tabindex=\"1\" size=\"3\" title=\"" . $vbphrase['edit_display_order'] . "\" />";

					$mods = array('no_value' => $vbphrase['moderators'].' (' . sizeof($imodcache["$forum[forumid]"]) . ')');
					if (is_array($imodcache["$forum[forumid]"]))
					{
						foreach ($imodcache["$forum[forumid]"] AS $moderator)
						{
							$mods['']["$moderator[moderatorid]"] = $moderator['username'];
						}
					}
					$mods['add'] = $vbphrase['add_moderator'];
					$cell[] = "\n\t<select name=\"m$forum[forumid]\" onchange=\"js_moderator_jump($forum[forumid]);\" class=\"bginput\">\n" . construct_select_options($mods) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_moderator_jump($forum[forumid]);\" />\n\t";
				}
				else if (
					$vbulletin->options['cp_collapse_forums'] AND
						(
						$forum['parentid'] == $expanddata['forumid'] OR
						$forum['parentid'] == -1 OR
						forum_is_related_to_forum($forum['parentlist'], $forum['forumid'], $expanddata['parentlist'])
						)
					)
				{
					$cell[] = "<a name=\"forum$forum[forumid]\">&nbsp;</a> <a href=\"forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify&amp;expandid=$forum[forumid]\">[+]</a>  <b>" . construct_depth_mark($forum['depth'],'- - ') . "<a href=\"forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;f=$forum[forumid]\">$forum[title]</a>" . iif(!empty($forum['password']),'*') . " " . iif($forum['link'], "(<a href=\"$forum[link]\">" . $vbphrase['link'] . "</a>)") . "</b>";
					$cell[] = construct_link_code($vbphrase['expand'], "forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify&amp;expandid=$forum[forumid]");
					$cell[] = "&nbsp;";
					$cell[] = "&nbsp;";
				}
				else
				{
					continue;
				}

				if ($forum['parentid'] == -1)
				{
					print_cells_row(array($vbphrase['forum'], $vbphrase['controls'], $vbphrase['display_order'], $vbphrase['moderators']), 1, 'tcat');
				}
				print_cells_row($cell);
			}
		}

		print_table_footer(4, "<input type=\"submit\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['save_display_order'] . "\" accesskey=\"s\" />" . construct_button_code($vbphrase['add_new_forum'], "forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=add"));

		if ($vbulletin->options['cp_collapse_forums'])
		{
			echo '<p class="smallfont" align="center">' . construct_link_code($vbphrase['expand_all'], "forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify&amp;expandid=-2") . '</p>';
		}

		echo '<p class="smallfont" align="center">' . $vbphrase['forums_marked_asterisk_are_password_protected'] . '</p>';
	}
	else
	{
		print_form_header('forum', 'doorder');
		print_table_header($vbphrase['forum_manager'], 2);

		print_cells_row(array($vbphrase['forum'], $vbphrase['controls']), 1, 'tcat');
		$cell = array();

		$select = '<select name="forumid" id="sel_foruid" tabindex="1" class="bginput">';
		$select .= construct_forum_chooser($vbulletin->GPC['forumid'], true);
		$select .= "</select>\n";

		$cell[] = $select;
		$cell[] = "\n\t<select name=\"controls\" class=\"bginput\">\n" . construct_select_options($forumoptions1) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_forum_jump(js_returnid());\" />\n\t";
		print_cells_row($cell);
		print_table_footer(2, construct_button_code($vbphrase['add_new_forum'], "forum.php?" . $vbulletin->session->vars['sessionurl'] . "do=add"));
	}
}

// ###################### Start add podcast #######################
if ($_REQUEST['do'] == 'podcast')
{
	if (!($forum = fetch_foruminfo($vbulletin->GPC['forumid'], false)))
	{
		print_stop_message('invalid_forum_specified');
	}
	require_once(DIR . '/includes/adminfunctions_misc.php');

	$forum['title'] = str_replace('&amp;', '&', $forum['title']);

	$podcast = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "podcast
		WHERE forumid = $forum[forumid]"
	);

	print_form_header('forum', 'updatepodcast');
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['podcast_settings'], $forum['title'], $forum['forumid']));
	construct_hidden_code('forumid', $forum['forumid']);

	print_yes_no_row($vbphrase['enabled'], 'enabled', $podcast['enabled']);
	print_podcast_chooser($vbphrase['category'], 'categoryid', $podcast['categoryid']);
	print_input_row($vbphrase['media_author'] . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255) . '</dfn>', 'author', $podcast['author']);
	print_input_row($vbphrase['owner_name']  . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255), 'ownername', $podcast['ownername']);
	print_input_row($vbphrase['owner_email']  . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255), 'owneremail', $podcast['owneremail']);
	print_input_row($vbphrase['image_url'], 'image', $podcast['image']);
	print_input_row($vbphrase['subtitle']  . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255) . '</dfn>', 'subtitle', $podcast['subtitle']);
	print_textarea_row($vbphrase['keywords'] . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 255) . '</dfn>', 'keywords', $podcast['keywords'], 2, 40);
	print_textarea_row($vbphrase['summary'] . '<dfn>' . construct_phrase($vbphrase['maximum_chars_x'], 4000) . '</dfn>', 'summary', $podcast['summary'], 4, 40);
	print_yes_no_row($vbphrase['explicit'], 'explicit', $podcast['explicit']);

	print_submit_row($vbphrase['save']);
}

// ###################### Start add podcast #######################
if ($_POST['do'] == 'updatepodcast')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'categoryid' => TYPE_UINT,
		'explicit'   => TYPE_BOOL,
		'enabled'    => TYPE_BOOL,
		'author'     => TYPE_STR,
		'owneremail' => TYPE_STR,
		'ownername'  => TYPE_STR,
		'image'      => TYPE_STR,
		'subtitle'   => TYPE_STR,
		'keywords'   => TYPE_STR,
		'summary'    => TYPE_STR,
	));

	if (!($forum = fetch_foruminfo($vbulletin->GPC['forumid'], false)))
	{
		print_stop_message('invalid_forum_specified');
	}
	require_once(DIR . '/includes/adminfunctions_misc.php');

	$category = fetch_podcast_categoryarray($vbulletin->GPC['categoryid']);

	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "podcast (forumid, enabled, categoryid, category, author, image, explicit, keywords, owneremail, ownername, subtitle, summary)
		VALUES (
			$forum[forumid],
			" . intval($vbulletin->GPC['enabled']) . ",
			" . $vbulletin->GPC['categoryid'] . ",
			'" . $db->escape_string(serialize($category)) . "',
			'" . $db->escape_string($vbulletin->GPC['author']) . "',
			'" . $db->escape_string($vbulletin->GPC['image']) . "',
			" . intval($vbulletin->GPC['explicit']) . ",
			'" . $db->escape_string($vbulletin->GPC['keywords']) . "',
			'" . $db->escape_string($vbulletin->GPC['owneremail']) . "',
			'" . $db->escape_string($vbulletin->GPC['ownername']) . "',
			'" . $db->escape_string($vbulletin->GPC['subtitle']) . "',
			'" . $db->escape_string($vbulletin->GPC['summary']) . "'
		)
	");

	build_forum_permissions();

	define('CP_REDIRECT', 'forum.php?do=modify');
	print_stop_message('updated_podcast_settings_successfully');
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 62096 $
|| ####################################################################
\*======================================================================*/
?>
