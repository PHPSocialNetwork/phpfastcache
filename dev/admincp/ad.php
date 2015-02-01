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
define('CVS_REVISION', '$RCSfile$ - $Revision: 58539 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('advertising', 'notice');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ############################# LOG ACTION ###############################
if (!can_administer('canadminads'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array('adid' => TYPE_UINT));

log_admin_action($vbulletin->GPC['adid'] != 0 ? "ad id = " . $vbulletin->GPC['adid'] : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['advertising']);

if (!in_array($_REQUEST['do'], array('add', 'edit', 'update', 'delete', 'remove', 'locate', 'flipcoin')))
{
	if (!empty($_REQUEST['adid']))
	{
		$_REQUEST['do'] = 'edit';
	}
	else
	{
		$_REQUEST['do'] = 'modify';
	}
}

// initialize some data storage
$ad_locations = array();	//not used, repurposing to hold map of location_key => location data
$ad_cache      = array();
$ad_name_cache = array();

// we don't want this if we're in modify (the listing view), and maybe other views to come
if (in_array($_REQUEST['do'], array('add', 'edit', 'update', 'remove', 'locate')))
{
	require_once(DIR . '/includes/class_xml.php');

	$locfiles = array();
	if ($handle = @opendir(DIR . '/includes/xml/'))
	{
		while (($file = readdir($handle)) !== false)
		{
			if (!preg_match('#^ad_locations_(.*).xml$#i', $file, $matches))
			{
				continue;
			}
			$loc_key = preg_replace('#[^a-z0-9]#i', '', $matches[1]);
			$locfiles["$loc_key"] = $file;
		}
		closedir($handle);
	}

	if (empty($locfiles['vbulletin']))	// opendir failed or ad_locations_vbulletin.xml is missing
	{
		if (is_readable(DIR . '/includes/xml/ad_locations_vbulletin.xml'))
		{
			$locfiles['vbulletin'] = 'ad_locations_vbulletin.xml';
		}
		else
		{
			echo construct_phrase($vbphrase['could_not_open_x'], DIR . '/includes/xml/ad_locations_vbulletin.xml');
			exit;
		}
	}

	// sort location groups and locations
	$locgroups = array();
	foreach ($locfiles AS $loc_file => $file)
	{
		$xmlobj = new vB_XML_Parser(false, DIR . "/includes/xml/$file");
		$xml = $xmlobj->parse();

		if ($xml['product'] AND empty($vbulletin->products[$xml['product']]))
		{
			// attached to a specific product and that product isn't enabled
			continue;
		}

		if (!is_array($xml['locationgroup'][0]))
		{
			$xml['locationgroup'] = array($xml['locationgroup']);
		}
		$xmlgroups = $xml['locationgroup'];

		foreach ($xmlgroups AS $xmlgroup)
		{
			$locations = array();

			if(!is_array($xmlgroup['location'][0]))
			{
				$xmlgroup['location'] = array($xmlgroup['location']);
			}
			$xmllocations = $xmlgroup['location'];

			foreach ($xmllocations AS $xmllocation)
			{
				$xmllocation['displayorder'] = intval($xmllocation['displayorder']);
				$xmllocation['cp_width'] = intval($xmllocation['cp_width']);
				$xmllocation['cp_height'] = intval($xmllocation['cp_height']);
				$xmllocation['cp_xpos'] = intval($xmllocation['cp_xpos']);
				$xmllocation['cp_ypos'] = intval($xmllocation['cp_ypos']);
				$xmllocation['product'] = $xml['product'];

				// add in order
				while (isset($locations[$xmllocation['displayorder']]))
				{
					$xmllocation['displayorder']++;
				}

				$locations[$xmllocation['displayorder']] = $xmllocation;
				$ad_locations[$xmlgroup['key'] . '_' . $xmllocation['key']] = $xmllocation;
			}
			ksort($locations);

			$xmlgroup['location'] = $locations;

			// add in order
			$xmlgroup['displayorder'] = intval($xmlgroup['displayorder']);
			while (isset($locgroups[$xmlgroup['displayorder']]))
			{
				$xmlgroup['displayorder']++;
			}

			$locgroups[$xmlgroup['displayorder']] = $xmlgroup;
		}
		ksort($locgroups);
	}
	unset($locfiles, $file, $xmlobj, $xml, $xmlgroups, $locations);

	// create options
	if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
	{
		foreach ($locgroups AS $locgroup)
		{
			foreach ($locgroup['location'] AS $location)
			{
				$location_options[$vbphrase['locationgroup_' . $locgroup['key']]]["$locgroup[key]_$location[key]"] = $vbphrase["adlocation_$locgroup[key]_$location[key]"];
			}
		}
	}
	else if ($_REQUEST['do'] == 'locate')
	{
		foreach($locgroups AS $locgroup)
		{
			$location_options[$locgroup['key']] = $vbphrase['locationgroup_' . $locgroup['key']];
		}
	}

	// cache all ads
	$ad_result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "ad ORDER BY displayorder");
	$max_displayorder = 0;
	while ($ad = $db->fetch_array($ad_result))
	{
		$ad_cache["$ad[adid]"] = $ad;
		if ($ad['adid'] != $vbulletin->GPC['adid'])
		{
			$ad_name_cache["$ad[adid]"] = $ad['title'];
		}
		if ($ad['displayorder'] > $max_displayorder)
		{
			$max_displayorder = $ad['displayorder'];
		}
	}
	$db->free_result($ad_result);
}

// #############################################################################
// edit an ad
if ($_REQUEST['do'] == 'edit' OR $_REQUEST['do'] == 'add')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'ad_location' => TYPE_NOHTML,
		'ad_location_orig' => TYPE_NOHTML
	));

	// set some default values
	$ad = array(
		'displayorder' => $max_displayorder + 10,
		'active'       => true,
	);

	$table_title = $vbphrase['add_new_ad'];

	$criteria_cache    = array();

	// are we editing or adding?
	if ($vbulletin->GPC['adid'] AND !empty($ad_cache[$vbulletin->GPC['adid']]))
	{
		// edit existing ad
		$ad = $ad_cache[$vbulletin->GPC['adid']];

		$criteria_result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "adcriteria WHERE adid = " . $vbulletin->GPC['adid']);
		while ($criteria = $db->fetch_array($criteria_result))
		{
			$criteria_cache["$criteria[criteriaid]"] = $criteria;
		}
		$db->free_result($criteria);

		$table_title = $vbphrase['edit_ad'] . " <span class=\"normal\">$ad[title]</span>";
	}

	// build list of usergroup titles
	$usergroup_options = array();
	foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
	{
		$usergroup_options["$usergroupid"] = $usergroup['title'];
	}

	// build list of style names
	require_once(DIR . '/includes/adminfunctions_template.php');
	cache_styles();
	$style_options = array();
	foreach($stylecache AS $style)
	{
		$masterset = $vbphrase[$style['type'] . '_styles'];
		$style_options[$masterset]["$style[styleid]"] = /*construct_depth_mark($style['depth'], '&nbsp; &nbsp; ') . ' ' .*/ $style['title'];
		$style_options[$masterset]["$style[styleid]"] = construct_depth_mark($style['depth'], '--') . ' ' . $style['title'];
	}

	// build the list of criteria options
	$criteria_options = array(
		'in_usergroup_x' => array(
			'<select name="criteria[in_usergroup_x][condition1]" tabindex="1">' .
				construct_select_options($usergroup_options, (empty($criteria_cache['in_usergroup_x']) ? 2 : $criteria_cache['in_usergroup_x']['condition1'])) .
			'</select>'
		),
		'not_in_usergroup_x' => array(
			'<select name="criteria[not_in_usergroup_x][condition1]" tabindex="1">' .
				construct_select_options($usergroup_options, (empty($criteria_cache['not_in_usergroup_x']) ? 6 : $criteria_cache['not_in_usergroup_x']['condition1'])) .
			'</select>'
		),
		'browsing_content_page' => array(
			'<select name="criteria[browsing_content_page][condition1]" tabindex="1">
				<option value="1"' . (empty($criteria_cache['browsing_content_page']['condition1']) ? ' selected="selected"' : '') . '>' . $vbphrase['content'] . '</option>
				<option value="0"' . ($criteria_cache['browsing_content_page']['condition1'] == 0 ? ' selected="selected"' : '') . '>' . $vbphrase['non_content'] . '</option>
			</select>'
		),
		'browsing_forum_x' => array(
			'<select name="criteria[browsing_forum_x][condition1]" tabindex="1">' .
				construct_select_options(construct_forum_chooser_options(), $criteria_cache['browsing_forum_x']['condition1']) .
			'</select>'
		),
		'browsing_forum_x_and_children' => array(
			'<select name="criteria[browsing_forum_x_and_children][condition1]" tabindex="1">' .
				construct_select_options(construct_forum_chooser_options(), $criteria_cache['browsing_forum_x_and_children']['condition1']) .
			'</select>'
		),
		'style_is_x' => array(
			'<select name="criteria[style_is_x][condition1]" tabindex="1">' .
				construct_select_options($style_options, $criteria_cache['style_is_x']['condition1']) .
			'</select>'
		),
		'no_visit_in_x_days' => array(
			'<input type="text" name="criteria[no_visit_in_x_days][condition1]" size="5" class="bginput" tabindex="1" value="' .
				(empty($criteria_cache['no_visit_in_x_days']) ? 30 : intval($criteria_cache['no_visit_in_x_days']['condition1'])) .
			'" />'
		),
		'no_posts_in_x_days' => array(
			'<input type="text" name="criteria[no_posts_in_x_days][condition1]" size="5" class="bginput" tabindex="1" value="' .
				(empty($criteria_cache['no_posts_in_x_days']) ? 30 : intval($criteria_cache['no_posts_in_x_days']['condition1'])) .
			'" />'
		),
		'has_x_postcount' => array(
			'<input type="text" name="criteria[has_x_postcount][condition1]" size="5" class="bginput" tabindex="1" value="' .
				$criteria_cache['has_x_postcount']['condition1'] .
			'" />',
			'<input type="text" name="criteria[has_x_postcount][condition2]" size="5" class="bginput" tabindex="1" value="' .
				$criteria_cache['has_x_postcount']['condition2'] .
			'" />'
		),
		'has_never_posted' => array(
		),
		'has_x_reputation' => array(
			'<input type="text" name="criteria[has_x_reputation][condition1]" size="5" class="bginput" tabindex="1" value="' .
				(empty($criteria_cache['has_x_reputation']) ? 100 : $criteria_cache['has_x_reputation']['condition1']) .
			'" />',
			'<input type="text" name="criteria[has_x_reputation][condition2]" size="5" class="bginput" tabindex="1" value="' .
				(empty($criteria_cache['has_x_reputation']) ? 200 : $criteria_cache['has_x_reputation']['condition2']) .
			'" />'
		),
		'pm_storage_x_percent_full' => array(
			'<input type="text" name="criteria[pm_storage_x_percent_full][condition1]" size="5" class="bginput" tabindex="1" value="' .
				(empty($criteria_cache['pm_storage_x_percent_full']) ? 90 : $criteria_cache['pm_storage_x_percent_full']['condition1']) .
			'" />',
			'<input type="text" name="criteria[pm_storage_x_percent_full][condition2]" size="5" class="bginput" tabindex="1" value="' .
				(empty($criteria_cache['pm_storage_x_percent_full']) ? 100 : $criteria_cache['pm_storage_x_percent_full']['condition2']) .
			'" />'
		),
		'came_from_search_engine' => array(
		),
		'is_date' => array(
			'<input type="text" name="criteria[is_date][condition1]" size="10" class="bginput" tabindex="1" value="' .
				(empty($criteria_cache['is_date']['condition1']) ? vbdate('d-m-Y', TIMENOW, false, false) : $criteria_cache['is_date']['condition1']) .
			'" />',
			'<select name="criteria[is_date][condition2]" tabindex="1">
				<option value="0"' . (empty($criteria_cache['is_date']['condition2']) ? ' selected="selected"' : '') . '>' . $vbphrase['user_timezone'] . '</option>
				<option value="1"' . ($criteria_cache['is_date']['condition2'] == 1 ? ' selected="selected"' : '') . '>' . $vbphrase['utc_universal_time'] . '</option>
			</select>'
		),
		'is_time' => array(
			'<input type="text" name="criteria[is_time][condition1]" size="5" class="bginput" tabindex="1" value="' .
				(empty($criteria_cache['is_time']['condition1']) ? vbdate('H:i', TIMENOW, false, false) : $criteria_cache['is_time']['condition1']) .
			'" />',
			'<input type="text" name="criteria[is_time][condition2]" size="5" class="bginput" tabindex="1" value="' .
				(empty($criteria_cache['is_time']['condition2']) ? (intval(vbdate('H', TIMENOW, false, false)) + 1) . vbdate(':i', TIMENOW, false, false) : $criteria_cache['is_time']['condition2']) .
			'" />',
			'<select name="criteria[is_time][condition3]" tabindex="1">
				<option value="0"' . (empty($criteria_cache['is_time']['condition3']) ? ' selected="selected"' : '') . '>' . $vbphrase['user_timezone'] . '</option>
				<option value="1"' . ($criteria_cache['is_time']['condition3'] == 1 ? ' selected="selected"' : '') . '>' . $vbphrase['utc_universal_time'] . '</option>
			</select>'
		),
		/*
		* These are flagged for a future version
		'userfield_x_equals_y' => array(
		),
		'userfield_x_contains_y' => array(
		),
		*/
	);
	if (sizeof($ad_name_cache))
	{
		$criteria_options['ad_x_not_displayed'] = array(
				'<select name="criteria[ad_x_not_displayed][condition1]" tabindex="1">' .
					construct_select_options($ad_name_cache, $criteria_cache['ad_x_not_displayed']['condition1']) .
				'</select>'
			);
	}

	// hook to allow third-party additions of criteria
	($hook = vBulletinHook::fetch_hook('ads_list_criteria')) ? eval($hook) : false;

	// build the editor form
	print_form_header('ad', 'update');
	construct_hidden_code('adid', $vbulletin->GPC['adid']);
	if($vbulletin->GPC['ad_location_orig']){
		construct_hidden_code('ad_location_orig', $vbulletin->GPC['ad_location_orig']);
	}
	print_table_header($table_title);

	print_input_row($vbphrase['title'] . '<dfn>' . $vbphrase['ad_title_description'] . '</dfn>', 'title', $ad['title'], 0, 60);
	print_select_row($vbphrase['ad_location'] . '<dfn>' . $vbphrase['ad_location_description'] . '</dfn>', 'ad_location', $location_options, $vbulletin->GPC['ad_location'] ? $vbulletin->GPC['ad_location'] : $ad['adlocation'] );
	print_textarea_row($vbphrase['ad_html'] . '<dfn>' . $vbphrase['ad_html_description'] . '</dfn>', 'ad_html', $ad['html'] ? $ad['html'] : $ad['snippet'], 8, 60, true, false);

	print_input_row($vbphrase['display_order'], 'displayorder', $ad['displayorder'], 0, 10);
	print_yes_no_row($vbphrase['active'] . '<dfn>' . $vbphrase['ad_active_description'] . '</dfn>', 'active', $ad['active']);
	print_description_row('<strong>' . $vbphrase['display_ad_if_elipsis'] . '</strong>', false, 2, 'tcat', '', 'criteria');

	if ($display_active_criteria_first)
	{
		function print_ad_criterion($criteria_option_id, &$criteria_options, $criteria_cache)
		{
			global $vbphrase;

			$criteria_option = $criteria_options["$criteria_option_id"];

			print_description_row(
				"<label><input type=\"checkbox\" id=\"cb_$criteria_option_id\" tabindex=\"1\" name=\"criteria[$criteria_option_id][active]\" title=\"$vbphrase[criterion_is_active]\" value=\"1\"" . (empty($criteria_cache["$criteria_option_id"]) ? '' : ' checked="checked"') . " />" .
				"<span id=\"span_$criteria_option_id\">" . construct_phrase($vbphrase[$criteria_option_id . '_criteria'], $criteria_option[0], $criteria_option[1], $criteria_option[2]) . '</span></label>'
			);

			unset($criteria_options["$criteria_option_id"]);
		}

		foreach (array_keys($criteria_cache) AS $id)
		{
			print_ad_criterion($id, $criteria_options, $criteria_cache);
		}
		foreach ($criteria_options AS $id => $criteria_option)
		{
			print_ad_criterion($id, $criteria_options, $criteria_cache);
		}
	}
	else
	{
		foreach ($criteria_options AS $criteria_option_id => $criteria_option)
		{
			// the criteria options can't trigger the checkbox to change, we need to break out of the label
			$criteria_text = '<label>' . construct_phrase($vbphrase[$criteria_option_id . '_criteria'],
				"</label>$criteria_option[0]<label>",
				"</label>$criteria_option[1]<label>",
				"</label>$criteria_option[2]<label>"
			) . '</label>';

			$criteria_text = str_replace('<label>', "<label for=\"cb_$criteria_option_id\">", $criteria_text);

			print_description_row(
				"<input type=\"checkbox\" id=\"cb_$criteria_option_id\" tabindex=\"1\" name=\"criteria[$criteria_option_id][active]\" title=\"$vbphrase[criterion_is_active]\" value=\"1\"" . (empty($criteria_cache["$criteria_option_id"]) ? '' : ' checked="checked"') . " />" .
				"<span id=\"span_$criteria_option_id\">$criteria_text</span>"
			);
		}
	}

	print_submit_row();
}

// #############################################################################
// update or insert an ad
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'title'               => TYPE_NOHTML,
		'ad_location'         => TYPE_NOHTML,
		'ad_location_orig'    => TYPE_NOHTML,
		'ad_html'             => TYPE_STR,
		'displayorder'        => TYPE_UINT,
		'active'              => TYPE_BOOL,
		'criteria'            => TYPE_ARRAY,
		'criteria_serialized' => TYPE_STR,
		'confirmerrors'       => TYPE_BOOL,
	));
	$adid = $vbulletin->GPC['adid'];

	$criterion = $vbulletin->GPC['criteria'];
	if ($vbulletin->GPC['criteria_serialized'])
	{
		$criterion = unserialize($vbulletin->GPC['criteria_serialized']);
	}

	/*
	foreach ($vbulletin->GPC['criteria'] AS $criteria)
	{
		if ($criteria['active'])
		{
			$have_criteria = true;
			break;
		}
	}


	if (!$have_criteria)
	{
		print_stop_message('no_ad_criteria_active');
	}
	*/

	if ($vbulletin->GPC['title'] === '')
	{
		print_stop_message('invalid_title_specified');
	}

	// we are editing
	if ($vbulletin->GPC['adid'])
	{
		// update ad record
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "ad SET
				title = '" . $db->escape_string($vbulletin->GPC['title']) . "',
				adlocation = '" . $db->escape_string($vbulletin->GPC['ad_location']) . "',
				displayorder = " . $vbulletin->GPC['displayorder'] . ",
				active = " . $vbulletin->GPC['active'] . ",
				snippet = '" . $db->escape_string($vbulletin->GPC['ad_html']) . "'
			WHERE adid = " . $adid
		);

		// delete criteria
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "adcriteria
			WHERE adid = " . $adid
		);
	}
	// we are adding a new ad
	else
	{
		// insert ad record
		$db->query_write($sql = "
			INSERT INTO " . TABLE_PREFIX . "ad
				(title, adlocation, displayorder, active, snippet)
			VALUES (" .
				"'" . $db->escape_string($vbulletin->GPC['title']) . "', " .
				"'" . $db->escape_string($vbulletin->GPC['ad_location']) . "', " .
				$vbulletin->GPC['displayorder'] . ", " .
				$vbulletin->GPC['active'] . ", " .
				"'" . $db->escape_string($vbulletin->GPC['ad_html']) . "'
			)
		");

		$adid = $db->insert_id();
	}

	// update the ad_cache
	$ad = array();
	$ad['adid'] = $adid;
	$ad['adlocation'] = $vbulletin->GPC['ad_location'];
	$ad['displayorder'] = $vbulletin->GPC['displayorder'];
	$ad['active'] = $vbulletin->GPC['active'];
	$ad['snippet'] = $vbulletin->GPC['ad_html'];
	$ad_cache[$adid] = $ad;

	$criteria_sql = array();

	foreach ($criterion AS $criteriaid => $criteria)
	{
		if ($criteria['active'])
		{
			$criteria_sql[] = "(
				$adid,
				'" . $db->escape_string($criteriaid) . "',
				'" . $db->escape_string(trim($criteria['condition1'])) . "',
				'" . $db->escape_string(trim($criteria['condition2'])) . "',
				'" . $db->escape_string(trim($criteria['condition3'])) . "'
			)";
		}
	}

	if (sizeof($criteria_sql))
	{
		// insert criteria
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "adcriteria
				(adid, criteriaid, condition1, condition2, condition3)
			VALUES " . implode(', ', $criteria_sql)
		);
	}

	require_once(DIR . '/includes/functions_ad.php');
	$template = wrap_ad_template(build_ad_template($vbulletin->GPC['ad_location']), $ad['adlocation']);
	$template_un = $template;

	require_once(DIR . '/includes/adminfunctions_template.php');
	$template = compile_template($template);

	// rebuild previous template if ad has moved locations
	$ad_location_orig = $vbulletin->GPC['ad_location_orig'];
	if (!empty($ad_location_orig) AND $ad['adlocation'] != $ad_location_orig)
	{
		$template_orig = wrap_ad_template(build_ad_template($ad_location_orig), $ad_location_orig);
		$template_orig_un = $template_orig;

		$template_orig = compile_template($template_orig);

		replace_ad_template(0, $ad_location_orig, $template_orig, $template_orig_un, 
			$vbulletin->userinfo['username'], $vbulletin->options['templateversion'],
			$ad_locations[$ad_location_orig]['product']);
		replace_ad_template(-1, $ad_location_orig, $template_orig, $template_orig_un, 
			$vbulletin->userinfo['username'], $vbulletin->options['templateversion'],
			$ad_locations[$ad_location_orig]['product']);
		replace_ad_template(-2, $ad_location_orig, $template_orig, $template_orig_un, 
			$vbulletin->userinfo['username'], $vbulletin->options['templateversion'],
			$ad_locations[$ad_location_orig]['product']);		
	}

	$ad_location = $vbulletin->GPC['ad_location'];
	// note: this error check will ALWAYS be triggered if another ad on the same location have an error.
	// would be a good idea to add a new description row to detail this problem for end users.
	if (empty($vbulletin->GPC['confirmerrors']))
	{
		$errors = check_template_errors($template);

		if (!empty($errors))
		{
			print_form_header('ad', 'update', 0, 1, '', '75%');
			construct_hidden_code('confirmerrors', 1);
			construct_hidden_code('adid', intval($vbulletin->GPC['adid']));
			construct_hidden_code('title', $vbulletin->GPC['title']);
			construct_hidden_code('ad_location', $ad_location);
			construct_hidden_code('ad_html', $vbulletin->GPC['ad_html']);
			construct_hidden_code('displayorder', intval($vbulletin->GPC['displayorder']));
			construct_hidden_code('active', $vbulletin->GPC['active']);
			construct_hidden_code('criteria_serialized', $criterion);
			print_table_header($vbphrase['vbulletin_message']);
			print_description_row(construct_phrase($vbphrase['template_eval_error'], $errors));
			print_description_row(construct_phrase($template_un, $errors));
			print_submit_row($vbphrase['continue'], 0, 2, $vbphrase['go_back']);
			print_cp_footer();
			exit;
		}
	}

	// The insert of the template.
	replace_ad_template(0, $ad_location, $template, $template_un,
		$vbulletin->userinfo['username'], $vbulletin->options['templateversion'],
		$ad_locations[$ad_location]['product']);
	replace_ad_template(-1, $ad_location, $template, $template_un,
		$vbulletin->userinfo['username'], $vbulletin->options['templateversion'],
		$ad_locations[$ad_location]['product']);
	replace_ad_template(-2, $ad_location, $template, $template_un,
		$vbulletin->userinfo['username'], $vbulletin->options['templateversion'],
		$ad_locations[$ad_location]['product']);
	
	build_all_styles(0, 0, '', false, 'standard');
	build_all_styles(0, 0, '', false, 'mobile');

	define('CP_REDIRECT', 'ad.php');
	print_stop_message('saved_ad_x_successfully', $vbulletin->GPC['title']);
}

// #############################################################################
// confirm deletion of a ad
if ($_REQUEST['do'] == 'delete')
{
	print_delete_confirmation('ad', $vbulletin->GPC['adid'], 'ad', 'remove');
}

// #############################################################################
// remove an ad
if ($_POST['do'] == 'remove')
{
	// get ad location
	$adlocation = $ad_cache[$vbulletin->GPC['adid']]['adlocation'];

	// delete criteria
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "adcriteria
		WHERE adid = " . $vbulletin->GPC['adid']
	);

	// delete ad
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "ad
		WHERE adid = " . $vbulletin->GPC['adid']
	);

	// remove record from ad_cache
	unset($ad_cache[$vbulletin->GPC['adid']]);
	$ad_cache = array_values($ad_cache);

	// rebuild affected template
	require_once(DIR . '/includes/functions_ad.php');
	$template = build_ad_template($adlocation);

	$template_un = $template;

	require_once(DIR . '/includes/adminfunctions_template.php');
	$template = compile_template($template);

	// note: we are skipping the error check this time around because it would not make sense to ask user to check the
	// template if they've already confirmed at other locations that their if conditions are wrong or whatever, and they
	// cannot fix it here.
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "template SET
			template = '" . $db->escape_string($template) . "',
			template_un = '" . $db->escape_string($template_un) . "',
			dateline = " . TIMENOW . ",
			username = '" . $db->escape_string($vbulletin->userinfo['username']) . "'
		WHERE
			title = 'ad_" . $db->escape_string($adlocation) . "'
		AND
			styleid IN (-2,-1,0)
	");

	build_all_styles(0, 0, '', false, 'standard');
	build_all_styles(0, 0, '', false, 'mobile');

	define('CP_REDIRECT', 'ad.php?do=modify');
	print_stop_message('deleted_ad_successfully');
}

// #############################################################################
// quick update of active and display order fields
if ($_POST['do'] == 'quickupdate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'active'            => TYPE_ARRAY_BOOL,
		'displayorder'      => TYPE_ARRAY_UINT,
		'displayorderswap'  => TYPE_CONVERT_KEYS
	));

	$changes = false;
	$update_ids = '0';
	$update_active = '';
	$update_displayorder = '';
	$ads_dispord = array();
	$changed_locations = array();
	$ads_result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "ad");
	while ($ad = $db->fetch_array($ads_result))
	{
		$ads_dispord["$ad[adid]"] = $ad['displayorder'];

		if (intval($ad['active']) != $vbulletin->GPC['active']["$ad[adid]"] OR $ad['displayorder'] != $vbulletin->GPC['displayorder']["$ad[$adid]"])
		{
			// prepare the queries
			$update_ids .= ",$ad[adid]";
			$update_active .= " WHEN $ad[adid] THEN " . intval($vbulletin->GPC['active']["$ad[adid]"]);
			$update_displayorder .= " WHEN $ad[adid] THEN " . $vbulletin->GPC['displayorder']["$ad[adid]"];

			// update the ad_cache
			$ad['active'] = $vbulletin->GPC['active']["$ad[adid]"];
			$ad['displayorder'] = $vbulletin->GPC['displayorder']["$ad[$adid]"];
			$ad_cache[$ad['adid']] = $ad;

			// flag location for rebuild later
			if (!in_array($ad['adlocation'], $changed_locations))
			{
				$changed_locations[] = $ad['adlocation'];
			}
		}
	}
	$db->free_result($ads_result);

	if (strlen($update_ids) > 1)
	{
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "ad
			SET
				active = CASE adid
				$update_active ELSE active END,
				displayorder = CASE adid
				$update_displayorder ELSE displayorder END
			WHERE
			 adid IN($update_ids)
		");

		// tell the templates to rebuild
		$changes = true;
	}

	// handle swapping
	if (!empty($vbulletin->GPC['displayorderswap']))
	{
		list($orig_adid, $swap_direction) = explode(',', $vbulletin->GPC['displayorderswap'][0]);

		if (isset($vbulletin->GPC['displayorder']["$orig_adid"]))
		{
			$ad_orig = array(
				'adid'     => $orig_adid,
				'displayorder' => $vbulletin->GPC['displayorder']["$orig_adid"]
			);

			switch ($swap_direction)
			{
				case 'lower':
				{
					$comp = '<';
					$sort = 'DESC';
					break;
				}
				case 'higher':
				{
					$comp = '>';
					$sort = 'ASC';
					break;
				}
				default:
				{
					$comp = false;
					$sort = false;
				}
			}

			if ($comp AND $sort AND $ad_swap = $db->query_first("SELECT adid, displayorder FROM " . TABLE_PREFIX . "ad WHERE displayorder $comp $ad_orig[displayorder] ORDER BY displayorder $sort, title ASC LIMIT 1"))
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "ad
					SET displayorder = CASE adid
						WHEN $ad_orig[adid] THEN $ad_swap[displayorder]
						WHEN $ad_swap[adid] THEN $ad_orig[displayorder]
						ELSE displayorder END
					WHERE adid IN($ad_orig[adid], $ad_swap[adid])
				");

				// tell the datastore to update
				$changes = true;
			}
		}
	}

	//update the ad templates
	if ($changes)
	{
		require_once(DIR . '/includes/functions_ad.php');
		require_once(DIR . '/includes/adminfunctions_template.php');

		foreach($changed_locations AS $location)
		{
			// same as above, we're not telling user errors here, because they already confirmed the error else where
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
	}

	$_REQUEST['do'] = 'modify';
}

// #############################################################################
// list existing ads
if ($_REQUEST['do'] == 'modify')
{
	print_form_header('ad', 'quickupdate');
	print_column_style_code(array('width:100%', 'white-space:nowrap'));
	print_table_header($vbphrase['ad_manager']);

	$ad_result = $db->query("SELECT * FROM " . TABLE_PREFIX . "ad ORDER BY displayorder, title");
	$ad_count = $db->num_rows($ad_result);

	if ($ad_count)
	{
		print_description_row('<label><input type="checkbox" id="allbox" checked="checked" />' . $vbphrase['toggle_active_status_for_all'] . '</label><input type="image" src="../' . $vbulletin->options['cleargifurl'] . '" name="normalsubmit" />', false, 2, 'thead" style="font-weight:normal; padding:0px 4px 0px 4px');
		while ($ad = $db->fetch_array($ad_result))
		{
			print_label_row(
				'<a href="ad.php?' . $vbulletin->session->vars['sessionurl'] . 'do=locate&amp;editloc=1&amp;ad_location=' . $ad['adlocation'] . '&amp;adid=' . $ad['adid'] . '" title="' . $vbphrase['edit_ad'] . '">' . $ad['title'] . '</a>',
				'<div style="white-space:nowrap">' .
				'<label class="smallfont"><input type="checkbox" name="active[' . $ad['adid'] . ']" value="1"' . ($ad['active'] ? ' checked="checked"' : '') . ' />' . $vbphrase['active'] . '</label> ' .
				'<input type="image" src="../cpstyles/' . $vbulletin->options['cpstylefolder'] . '/move_down.gif" name="displayorderswap[' . $ad['adid'] . ',higher]" />' .
				'<input type="text" name="displayorder[' . $ad['adid'] . ']" value="' . $ad['displayorder'] . '" class="bginput" size="4" title="' . $vbphrase['display_order'] . '" style="text-align:' . vB_Template_Runtime::fetchStyleVar('right') . '" />' .
				'<input type="image" src="../cpstyles/' . $vbulletin->options['cpstylefolder'] . '/move_up.gif" name="displayorderswap[' . $ad['adid'] . ',lower]" />' .
				construct_link_code($vbphrase['edit'], 'ad.php?' . $vbulletin->session->vars['sessionurl'] . 'do=locate&amp;editloc=1&amp;ad_location=' . $ad['adlocation'] . '&amp;adid=' . $ad['adid']) .
				construct_link_code($vbphrase['delete'], 'ad.php?' . $vbulletin->session->vars['sessionurl'] . 'do=delete&amp;adid=' . $ad['adid']) .
				'</div>'
			);
		}
	}

	print_label_row(
		'<input type="button" class="button" value="' . $vbphrase['add_new_ad'] . '" onclick="window.location=\'ad.php?' . $vbulletin->session->vars['sessionurl'] . 'do=locate\';" />',
		($ad_count ? '<div align="' . vB_Template_Runtime::fetchStyleVar('right') . '"><input type="submit" class="button" accesskey="s" value="' . $vbphrase['save'] . '" /> <input type="reset" class="button" accesskey="r" value="' . $vbphrase['reset'] . '" /></div>' : '&nbsp;'),
		'tfoot'
	);
	print_table_footer();

	?>
	<script type="text/javascript">
	<!--
	function toggle_all_active(e)
	{
		for (var i = 0; i < this.form.elements.length; i++)
		{
			if (this.form.elements[i].type == "checkbox" && this.form.elements[i].name.substr(0, 6) == "active")
			{
				this.form.elements[i].checked = this.checked;
			}
		}
	}

	YAHOO.util.Event.on("allbox", "click", toggle_all_active);
	//-->
	</script>
	<?php
}

// ###################### Select Ad Location #######################
if ($_REQUEST['do'] == 'locate')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'ad_location'		=> TYPE_NOHTML,
		'editloc'			=> TYPE_BOOL
	));
	$orig_location = $vbulletin->GPC['ad_location'];
	if ($vbulletin->GPC['ad_location'] AND sizeof($location_info = explode('_', $vbulletin->GPC['ad_location'], 2)) == 2)
	{
		list($selected_group, $ad_location) = $location_info;
	}
	else
	{
		$selected_group = 'global';
		$ad_location = false;
	}
?>
<style type="text/css">
a.ad
{
	display: table-cell;
	position: absolute;
	background-color: gold;
	color: gold;
	font: bold 12px verdana, geneva, lucida, 'lucida grande', arial, helvetica, sans-serif;
	padding: 0px;
	vertical-align: middle;
	text-decoration: none;
	overflow: hidden;
	/* border: dashed 1px navy; */
}

div .ad:hover, div .ad_selected
{
	color: black;
	/* background-color: #E1E4F2; */
	background-color: gold;
	border: 2px dashed navy;
	margin: -2px 0 0 -2px;
}
</style>
<?php
	echo '<script type="text/javascript" src="../clientscript/vbulletin_cpadlocator.js?v=' . SIMPLE_VERSION . '"></script>';

	print_table_start();
	print_table_header($vbphrase['ad_selector']);

	echo '<tr><td colspan="2" class="alt1" align="center">
			<label for="languagegroup"><strong>' . $vbphrase['page'] . ':</strong></label> <select name="languagegroup" id="languagegroup" tabindex="1" class="bginput" title="name=&quot;languagegroup&quot;">';

	foreach ($location_options as $key => $name)
	{
		echo '<option value="' . $key . '"' . ($selected_group == $key ? 'selected="selected"' : '') . '>' . $name . "</option>\r\n";
	}

	echo '</select>
		<tr><td colspan="2" class="alt1" align="center">';

	foreach ($locgroups AS $locgroup)
	{
		$display = (($selected_group == $locgroup['key']) ? '' : 'none');
		$do = $vbulletin->GPC['editloc'] ? 'edit' : 'add';
?>
		<div id="group_<?php echo $locgroup['key']; ?>" style="display:<?php echo $display; ?>;position:relative;width:500px">
			<img src="../images/ads/locator/ad_<?php echo($locgroup['key']); ?>.jpg?v=<?php echo SIMPLE_VERSION; ?>" style="border: none" alt="<?php echo($locgroup['key']) ?>" />

<?php
		foreach ($locgroup['location'] AS $location)
		{
			$selected = false;
			$lineheight = ($location['cp_height']);

			if ($vbulletin->GPC['editloc'] AND $vbulletin->GPC['adid'])
			{
				if ($locgroup['key'] == $selected_group AND $location['key'] == $ad_location)
				{
					$selected = true;
				}
				if ($orig_location != $locgroup['key']."_".$location['key']) {
					$orig_loc = "&amp;ad_location_orig=".$orig_location;
				}

				echo "\n\t<a id=\"location_$location[key]\" href=\"ad.php?do=edit&amp;adid={$vbulletin->GPC['adid']}&amp;ad_location=$locgroup[key]_$location[key]$orig_loc\" class=\"ad" . ($selected ? ' ad_selected' : '') . "\" style=\"top:$location[cp_ypos]px;left:$location[cp_xpos]px;width:$location[cp_width]px;height:$location[cp_height]px;line-height:$location[cp_height]px\">" . $vbphrase["adlocation_$locgroup[key]_$location[key]"] . "</a>";
			}
			else
			{
				echo "\n\t<a id=\"location_$location[key]\" href=\"ad.php?do=add&amp;ad_location=$locgroup[key]_$location[key]\" class=\"ad\" style=\"top:$location[cp_ypos]px;left:$location[cp_xpos]px;width:$location[cp_width]px;height:$location[cp_height]px;line-height:$location[cp_height]px\">" . $vbphrase["adlocation_$locgroup[key]_$location[key]"] . "</a>";
			}
		}

		echo '</div>';
	}

	echo "</td></tr>";

	print_table_footer();
?>
<script type="text/javascript">
<!--
	vBulletin_init();
//-->
</script>
<?php
}

// #### Dev 101: Deciding the best approach for a given problem ####
if ($_REQUEST['do'] == 'flipcoin')
{
	echo rand(0,1) ? 'Heads' : 'Tails';
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 58539 $
|| ####################################################################
\*======================================================================*/
