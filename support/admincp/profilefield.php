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
define('CVS_REVISION', '$RCSfile$ - $Revision: 34257 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('profilefield', 'cprofilefield');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_profilefield.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'profilefieldid' => TYPE_UINT,
));

// ############################# LOG ACTION ###############################
log_admin_action(iif($vbulletin->GPC['profilefieldid'] != 0, "profilefield id = " . $vbulletin->GPC['profilefieldid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['user_profile_field_manager']);

$types = array(
	'input'           => $vbphrase['single_line_text_box'],
	'textarea'        => $vbphrase['multiple_line_text_box'],
	'radio'           => $vbphrase['single_selection_radio_buttons'],
	'select'          => $vbphrase['single_selection_menu'],
	'select_multiple' => $vbphrase['multiple_selection_menu'],
	'checkbox'        => $vbphrase['multiple_selection_checkbox']
);

$category_locations = array(
	''                        => $vbphrase['only_in_about_me_tab'],
	'profile_tabs_first'      => $vbphrase['main_column_first_tab'],
	'profile_tabs_last'       => $vbphrase['main_column_last_tab'],
	'profile_sidebar_first'   => $vbphrase['blocks_column_first'],
	'profile_sidebar_stats'   => $vbphrase['blocks_column_after_mini_stats'],
	'profile_sidebar_friends' => $vbphrase['blocks_column_after_friends'],
	'profile_sidebar_albums'  => $vbphrase['blocks_column_after_albums'],
	'profile_sidebar_groups'  => $vbphrase['blocks_column_after_groups'],
	'profile_sidebar_last'    => $vbphrase['blocks_column_last']
);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

($hook = vBulletinHook::fetch_hook('admin_profilefield_start')) ? eval($hook) : false;

// #############################################################################
if ($_REQUEST['do'] == 'deletecat')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'profilefieldcategoryid' => TYPE_UINT
	));

	if ($pfc = $db->query_first("
		SELECT pfc.*,
			COUNT(profilefieldid) AS profilefieldscount
		FROM " . TABLE_PREFIX . "profilefieldcategory AS pfc
		LEFT JOIN " . TABLE_PREFIX . "profilefield AS pf ON(pf.profilefieldcategoryid = pfc.profilefieldcategoryid)
		WHERE pfc.profilefieldcategoryid = " . $vbulletin->GPC['profilefieldcategoryid'] . "
		GROUP BY pfc.profilefieldcategoryid
	"))
	{
		print_form_header('profilefield', 'removecat');
		construct_hidden_code('profilefieldcategoryid', $pfc['profilefieldcategoryid']);
		print_table_header($vbphrase['confirm_deletion']);
		print_description_row(construct_phrase(
			$vbphrase['are_you_sure_you_want_to_delete_user_profile_field_category_x'],
			$vbphrase['category' . $pfc['profilefieldcategoryid'] . '_title'],
			$pfc['profilefieldscount'],
			$vbphrase['uncategorized']
		));
		print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
	}
	else
	{
		$_REQUEST['do'] = 'modifycats';
	}
}

// #############################################################################
if ($_POST['do'] == 'removecat')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'profilefieldcategoryid' => TYPE_UINT
	));

	if ($pfc = $db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "profilefieldcategory
		WHERE profilefieldcategoryid = " . $vbulletin->GPC['profilefieldcategoryid'] . "
	"))
	{
		// update profile fields to be uncategorized
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "profilefield
			SET profilefieldcategoryid = 0
			WHERE profilefieldcategoryid = " . $pfc['profilefieldcategoryid'] . "
		");

		// delete category
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "profilefieldcategory
			WHERE profilefieldcategoryid = " . $pfc['profilefieldcategoryid'] . "
		");

		// delete phrases
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "phrase
			WHERE fieldname = 'cprofilefield'
			AND varname IN('category$pfc[profilefieldcategoryid]_title', 'category$pfc[profilefieldcategoryid]_desc')
		");

		// redirect to category list page
		define('CP_REDIRECT', 'profilefield.php?do=modifycats');
		print_stop_message('deleted_profile_field_category_successfully');
	}
	else
	{
		$_REQUEST['do'] = 'modifycats';
	}
}

// #############################################################################
if ($_POST['do'] == 'updatecat')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'profilefieldcategoryid' => TYPE_UINT,
		'displayorder' => TYPE_UINT,
		'title' => TYPE_NOHTML,
		'location' => TYPE_STR,
		'desc' => TYPE_STR,
		'allowprivacy' => TYPE_BOOL
	));

	if (empty($vbulletin->GPC['title']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if (!$_POST['profilefieldcategoryid'])
	{
		// we are adding a new item
		$db->query_write("
			INSERT INTO " .TABLE_PREFIX . "profilefieldcategory
				(profilefieldcategoryid, displayorder, location, allowprivacy)
			VALUES
				(NULL, " . $vbulletin->GPC['displayorder'] . ", '" . $db->escape_string($vbulletin->GPC['location']) . "', " . intval($vbulletin->GPC['allowprivacy']) . ")
		");

		$vbulletin->GPC['profilefieldcategoryid'] = intval($db->insert_id());
	}
	else
	{
		// we are updating an existing item
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "profilefieldcategory SET
				displayorder = " . $vbulletin->GPC['displayorder'] . ",
				location = '" . $db->escape_string($vbulletin->GPC['location']) . "',
				allowprivacy = " . intval($vbulletin->GPC['allowprivacy']) . "
			WHERE profilefieldcategoryid = " . $vbulletin->GPC['profilefieldcategoryid'] . "
		");
	}

	// and now update the phrases
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "phrase
			(languageid, fieldname, varname, text, product, username, dateline, version)
		VALUES
			(
				0,
				'cprofilefield',
				'category{$vbulletin->GPC[profilefieldcategoryid]}_title',
				'" . $db->escape_string($vbulletin->GPC['title']) .  "',
				'vbulletin',
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($vbulletin->options['templateversion']) . "'
			),
			(
				0,
				'cprofilefield',
				'category{$vbulletin->GPC[profilefieldcategoryid]}_desc',
				'" . $db->escape_string($vbulletin->GPC['desc']) . "',
				'vbulletin',
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($vbulletin->options['templateversion']) . "'
			)
	");

	// rebuild the language cache
	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	// redirect to category list page
	define('CP_REDIRECT', 'profilefield.php?do=modifycats');
	print_stop_message('saved_x_successfully', $vbulletin->GPC['title']);
}

// #############################################################################
if ($_REQUEST['do'] == 'addcat' OR $_REQUEST['do'] == 'editcat')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'profilefieldcategoryid' => TYPE_UINT
	));

	print_form_header('profilefield', 'updatecat');

	if ($_REQUEST['do'] == 'editcat' AND $pfc = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "profilefieldcategory WHERE profilefieldcategoryid = " . $vbulletin->GPC['profilefieldcategoryid']))
	{
		print_table_header($vbphrase['edit_user_profile_field_category'] .
			' <span class="normal">' . $vbphrase['category' . $pfc['profilefieldcategoryid'] . '_title'] .
			" (id $pfc[profilefieldcategoryid])</span>"
		);
		construct_hidden_code('profilefieldcategoryid', $pfc['profilefieldcategoryid']);

		$title = 'category' . $pfc['profilefieldcategoryid'] . '_title';
		$desc = 'category' . $pfc['profilefieldcategoryid'] . '_desc';

		$phrases = $db->query_read("
			SELECT varname, text
			FROM " . TABLE_PREFIX . "phrase
			WHERE languageid = 0 AND
					fieldname = 'cprofilefield' AND
					varname IN ('$title', '$desc')
		");
		while ($phrase = $db->fetch_array($phrases))
		{
			if ($phrase['varname'] == $title)
			{
				$pfc['title'] = $phrase['text'];
			}
			else if ($phrase['varname'] == $desc)
			{
				$pfc['desc'] = $phrase['text'];
			}
		}
	}
	else
	{
		print_table_header($vbphrase['add_new_profile_field_category']);

		$pfc = array(
			'profilefieldcategoryid' => 0,
			'location' => '',
			'displayorder' => 1,
			'title' => '',
			'descr' => ''
		);
	}

	$trans_link = "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=cprofilefield&t=1&varname=";

	print_input_row(
		$vbphrase['title'] .
			($pfc['profilefieldcategoryid'] ? '<dfn>' . construct_link_code($vbphrase['translations'], $trans_link . "category$pfc[profilefieldcategoryid]_title", 1)  . '</dfn>' : ''),
		'title', $pfc['title'], false
	);
	print_textarea_row(
		$vbphrase['description'] .
			($pfc['profilefieldcategoryid'] ? '<dfn>' . construct_link_code($vbphrase['translations'], $trans_link . "category$pfc[profilefieldcategoryid]_desc", 1)  . '</dfn>' : ''),
		'desc', $pfc['desc']
	);
	print_select_row($vbphrase['location_on_profile_page_dfn'], 'location', $category_locations, $pfc['location']);
	print_input_row($vbphrase['display_order'], 'displayorder', $pfc['displayorder']);
	print_checkbox_row($vbphrase['allow_privacy_options'], 'allowprivacy', $pfc['allowprivacy']);
	print_submit_row();
}

// #############################################################################
if ($_POST['do'] == 'displayordercats')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'order' => TYPE_ARRAY_UINT,
	));

	if (!empty($vbulletin->GPC['order']))
	{
		$sql = '';
		foreach ($vbulletin->GPC['order'] AS $profilefieldcategoryid => $displayorder)
		{
			$sql .= "WHEN " . intval($profilefieldcategoryid) . " THEN " . intval($displayorder) . "\n";
		}
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "profilefieldcategory
			SET displayorder = CASE profilefieldcategoryid
			$sql ELSE displayorder END
		");

		define('CP_REDIRECT', 'profilefield.php?do=modifycats');
		print_stop_message('saved_display_order_successfully');
	}
	else
	{
		$_REQUEST['do'] = 'modifycats';
	}
}

// #############################################################################
if ($_REQUEST['do'] == 'modifycats')
{
	$pfcs_result = $db->query_read("
		SELECT pfc.*,
			COUNT(profilefieldid) AS profilefieldscount
		FROM " . TABLE_PREFIX . "profilefieldcategory AS pfc
		LEFT JOIN " . TABLE_PREFIX . "profilefield AS pf ON(pf.profilefieldcategoryid = pfc.profilefieldcategoryid)
		GROUP BY pfc.profilefieldcategoryid
		ORDER BY pfc.displayorder
	");

	print_form_header('profilefield', 'displayordercats');
	print_table_header($vbphrase['user_profile_field_categories'], 4);

	if ($db->num_rows($pfcs_result))
	{
		print_cells_row(array(
			'ID',
			$vbphrase['title'],
			$vbphrase['display_order'],
			$vbphrase['controls']
		), true, false, -1);

		while ($pfc = $db->fetch_array($pfcs_result))
		{
			print_cells_row(array(
				$pfc['profilefieldcategoryid'],
				"<div class=\"smallfont\" style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\"><em>" . construct_phrase($vbphrase['contains_x_fields'], $pfc['profilefieldscount']) . "</em></div>
					<strong>" . $vbphrase['category' . $pfc['profilefieldcategoryid'] . '_title'] . '</strong>
					<dfn>' . $vbphrase['category' . $pfc['profilefieldcategoryid'] . '_desc'] . "</dfn>",
				"<input type=\"text\" name=\"order[$pfc[profilefieldcategoryid]]\" size=\"5\" value=\"$pfc[displayorder]\" class=\"bginput\" tabindex=\"1\" style=\"text-align:" . vB_Template_Runtime::fetchStyleVar('right') . "\" />",
				construct_link_code($vbphrase['edit'], "profilefield.php?" . $vbulletin->session->vars['sessionurl'] . "do=editcat&amp;profilefieldcategoryid=$pfc[profilefieldcategoryid]") .
					construct_link_code($vbphrase['delete'], "profilefield.php?" . $vbulletin->session->vars['sessionurl'] . "do=deletecat&amp;profilefieldcategoryid=$pfc[profilefieldcategoryid]")
			), false, false, -1);

		}

		print_submit_row($vbphrase['save_display_order'], '', 4);
	}
	else
	{
		print_description_row($vbphrase['no_user_profile_field_categories_have_been_created'], false, 4);
		print_table_footer();
	}

	echo '<div align="center">' . construct_link_code($vbphrase['add_new_profile_field_category'], 'profilefield.php?' . $vbulletin->session->vars['sessionurl'] . 'do=addcat') . '</div>';
}

// ###################### Start Update Display Order #######################
if ($_POST['do'] == 'displayorder')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'order' => TYPE_ARRAY_UINT,
	));

	if (!empty($vbulletin->GPC['order']))
	{
		$sql = '';
		foreach ($vbulletin->GPC['order'] AS $_profilefieldid => $displayorder)
		{
			$sql .= "WHEN " . intval($_profilefieldid) . " THEN " . intval($displayorder) . "\n";
		}
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "profilefield
			SET displayorder = CASE profilefieldid
			$sql ELSE displayorder END
		");

		define('CP_REDIRECT', 'profilefield.php?do=modify');
		print_stop_message('saved_display_order_successfully');
	}
	else
	{
		$_REQUEST['do'] = 'modify';
	}
}

// ###################### Start Insert / Update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'type'         => TYPE_STR,
		'profilefield' => TYPE_ARRAY_STR,
		'modifyfields' => TYPE_STR,
		'newtype'      => TYPE_STR,
		'title'        => TYPE_STR,
		'description'  => TYPE_STR
	));

	if ((($vbulletin->GPC['type'] == 'select' OR $vbulletin->GPC['type'] == 'radio') AND empty($vbulletin->GPC['profilefield']['data'])) OR empty($vbulletin->GPC['title']))
	{
		print_stop_message('please_complete_required_fields');
	}
	else if (($vbulletin->GPC['type'] == 'checkbox' OR $vbulletin->GPC['type'] == 'select_multiple') AND empty($vbulletin->GPC['profilefield']['data']) AND empty($vbulletin->GPC['profilefieldid']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if (!empty($vbulletin->GPC['profilefield']['regex']))
	{
		if (preg_match('#' . str_replace('#', '\#', $vbulletin->GPC['profilefield']['regex']) . '#siU', '') === false)
		{
			print_stop_message('regular_expression_is_invalid');
		}
	}

	// maxlength required for text boxes or optional inputs - conditions split for readability
	if ($vbulletin->GPC['profilefield']['maxlength'] < 1)
	{
		if ($vbulletin->GPC['type'] == 'textarea' OR $vbulletin->GPC['type'] == 'input')
		{
			print_stop_message('must_have_positive_maxlength');
		}
		else if (($vbulletin->GPC['type'] == 'select' OR $vbulletin->GPC['type'] == 'radio') AND $vbulletin->GPC['profilefield']['optional'])
		{
			print_stop_message('must_have_positive_maxlength');
		}
	}

	if ($vbulletin->GPC['type'] == 'select' OR $vbulletin->GPC['type'] == 'radio' OR (($vbulletin->GPC['type'] == 'checkbox' OR $vbulletin->GPC['type'] == 'select_multiple') AND empty($vbulletin->GPC['profilefieldid'])))
	{

		$data = explode("\n", htmlspecialchars_uni($vbulletin->GPC['profilefield']['data']));
		$data = array_map('trim', $data);

		$testdata = array_unique(array_map('strtolower', $data));
		if (($vbulletin->GPC['type'] == 'checkbox' OR $vbulletin->GPC['type'] == 'select_multiple') AND count($testdata) != count($data))
		{
			print_stop_message('can_not_duplicate_options');
		}

		if (sizeof($data) > 31 AND ($vbulletin->GPC['type'] == 'checkbox' OR $vbulletin->GPC['type'] == 'select_multiple'))
		{
			print_stop_message('too_many_profile_field_options', sizeof($data));
		}

		$vbulletin->GPC['profilefield']['data'] = serialize($data);
	}

	if ($vbulletin->GPC['type'] == 'input' OR $vbulletin->GPC['type'] == 'textarea')
	{
		$profilefield['data'] = htmlspecialchars_uni($vbulletin->GPC['profilefield']['data']);
	}
	if (!empty($vbulletin->GPC['newtype']) AND $vbulletin->GPC['newtype'] != $vbulletin->GPC['type'])
	{
		$vbulletin->GPC['profilefield']['type'] = $vbulletin->GPC['newtype'];
		if ($vbulletin->GPC['newtype'] == 'textarea')
		{
			$vbulletin->GPC['profilefield']['height'] = 4;
			$vbulletin->GPC['profilefield']['memberlist'] = 0;
		}
		else if ($vbulletin->GPC['newtype'] == 'select_multiple')
		{
			$vbulletin->GPC['profilefield']['height'] = $vbulletin->GPC['profilefield']['perline'];
		}
	}
	else
	{
		$vbulletin->GPC['profilefield']['type'] = $vbulletin->GPC['type'];
	}

	// check that specified category exists
	$vbulletin->GPC['profilefield']['profilefieldcategoryid'] = $vbulletin->input->clean($vbulletin->GPC['profilefield']['profilefieldcategoryid'], TYPE_UINT);

	$pfcs = array();
	$pfcs_result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "profilefieldcategory");
	while ($pfc = $db->fetch_array($pfcs_result))
	{
		$pfcs[] = $pfc['profilefieldcategoryid'];
	}

	if (!in_array($vbulletin->GPC['profilefield']['profilefieldcategoryid'], $pfcs))
	{
		$vbulletin->GPC['profilefield']['profilefieldcategoryid'] = 0;
	}

	if (empty($vbulletin->GPC['profilefieldid']))
	{ // insert
		/*insert query*/
		$db->query_write(fetch_query_sql($vbulletin->GPC['profilefield'], 'profilefield'));
		$vbulletin->GPC['profilefieldid'] = $db->insert_id();
		$db->query_write("ALTER TABLE " . TABLE_PREFIX . "userfield ADD field{$vbulletin->GPC['profilefieldid']} MEDIUMTEXT NOT NULL");
		$db->query_write("OPTIMIZE TABLE " . TABLE_PREFIX . "userfield");
	}
	else
	{
		$db->query_write(fetch_query_sql($vbulletin->GPC['profilefield'], 'profilefield', "WHERE profilefieldid=" . $vbulletin->GPC['profilefieldid']));
	}

	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "phrase
			(languageid, fieldname, varname, text, product, username, dateline, version)
		VALUES
			(
				0,
				'cprofilefield',
				'field" . $db->escape_string($vbulletin->GPC['profilefieldid']) . "_title',
				'" . $db->escape_string($vbulletin->GPC['title']) .  "',
				'vbulletin',
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($vbulletin->options['templateversion']) . "'
			),
			(
				0,
				'cprofilefield',
				'field" . $db->escape_string($vbulletin->GPC['profilefieldid']) . "_desc',
				'" . $db->escape_string($vbulletin->GPC['description']) . "',
				'vbulletin',
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($vbulletin->options['templateversion']) . "'
			)
	");

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	build_profilefield_cache();

	if ($vbulletin->GPC['modifyfields'])
	{
		define('CP_REDIRECT', "profilefield.php?do=modifycheckbox&profilefieldid=" . $vbulletin->GPC['profilefieldid']);
	}
	else
	{
		define('CP_REDIRECT', 'profilefield.php?do=modify');
	}
	print_stop_message('saved_x_successfully', htmlspecialchars_uni($vbulletin->GPC['title']));
}

// ###################### Start add #######################
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{

	$vbulletin->input->clean_array_gpc('r', array(
		'type' => TYPE_STR,
	));

	if ($_REQUEST['do'] == 'add')
	{

		if (empty($vbulletin->GPC['type']))
		{
			echo "<p>&nbsp;</p><p>&nbsp;</p>\n";
			print_form_header('profilefield', 'add');
			print_table_header($vbphrase['add_new_user_profile_field']);
			print_label_row($vbphrase['profile_field_type'], '<select name="type" tabindex="1" class="bginput">' . construct_select_options($types) . '</select>', '', 'top', 'profilefieldtype');
			print_submit_row($vbphrase['continue'], 0);
			print_cp_footer();
			exit;
		}

		$maxprofile = $db->query_first("SELECT COUNT(*) AS count FROM " . TABLE_PREFIX . "profilefield");

		$profilefield = array(
			'maxlength'    => 100,
			'size'         => 25,
			'height'       => 4,
			'def'          => 1,
			'memberlist'   => 1,
			'searchable'   => 1,
			'limit'        => 0,
			'perline'      => 0,
			'displayorder' => $maxprofile['count'] + 1,
			'boxheight'    => 0,
			'editable'     => 1,
		);

		print_form_header('profilefield', 'update');
		construct_hidden_code('type', $vbulletin->GPC['type']);
		print_table_header($vbphrase['add_new_user_profile_field'] . " <span class=\"normal\">" . $types["{$vbulletin->GPC['type']}"] . "</span>", 2, 0);

	}
	else
	{
		$profilefield = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "profilefield
			WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
		");

		$vbulletin->GPC['type'] =& $profilefield['type'];

		if ($vbulletin->GPC['type'] == 'select' OR $vbulletin->GPC['type'] == 'radio')
		{
			$profilefield['data'] = implode("\n", unserialize($profilefield['data']));
		}
		$profilefield['limit'] = $profilefield['size'];
		$profilefield['boxheight'] = $profilefield['height'];

		if ($vbulletin->GPC['type'] == 'checkbox')
		{
			echo '<p><b>' . $vbphrase['you_close_before_modifying_checkboxes'] . '</b></p>';
		}

		$title = 'field' . $profilefield['profilefieldid'] . '_title';
		$desc = 'field' . $profilefield['profilefieldid'] . '_desc';

		$phrases = $db->query_read("
			SELECT varname, text
			FROM " . TABLE_PREFIX . "phrase
			WHERE languageid = 0 AND
					fieldname = 'cprofilefield' AND
					varname IN ('$title', '$desc')
		");
		while ($phrase = $db->fetch_array($phrases))
		{
			if ($phrase['varname'] == $title)
			{
				$profilefield['title'] = $phrase['text'];
				$profilefield['titlevarname'] = 'field' . $profilefield['profilefieldid'] . '_title';
			}
			else if ($phrase['varname'] == $desc)
			{
				$profilefield['description'] = $phrase['text'];
				$profilefield['descvarname'] = 'field' . $profilefield['profilefieldid'] . '_desc';
			}
		}

		print_form_header('profilefield', 'update');
		construct_hidden_code('type', $vbulletin->GPC['type']);
		construct_hidden_code('profilefieldid', $vbulletin->GPC['profilefieldid']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['user_profile_field'], $profilefield['title'], $vbulletin->GPC['profilefieldid'] . " - $profilefield[type]"), 2, 0);
	}

	if ($profilefield['title'])
	{
		print_input_row($vbphrase['title'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=cprofilefield&varname=$profilefield[titlevarname]&t=1", 1)  . '</dfn>', 'title', $profilefield['title']);
	}
	else
	{
		print_input_row($vbphrase['title'], 'title');
	}
	if ($vbulletin->GPC['type'] == 'checkbox')
	{
		$extra = '<dfn>' . $vbphrase['choose_limit_choices_add_info'] . '<dfn>';
	}
	if ($profilefield['description'])
	{
		print_textarea_row($vbphrase['description'] . $extra . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=cprofilefield&varname=$profilefield[descvarname]&t=1", 1)  . '</dfn>', 'description', $profilefield['description']);
	}
	else
	{
		print_textarea_row($vbphrase['description'] . $extra, 'description');
	}

	$pfcs = array(0 => '(' . $vbphrase['uncategorized'] . ')');
	$pfcs_result = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "profilefieldcategory ORDER BY displayorder");
	while ($pfc = $db->fetch_array($pfcs_result))
	{
		$pfcs["$pfc[profilefieldcategoryid]"] = $vbphrase['category' . $pfc['profilefieldcategoryid'] . '_title'];
	}
	$db->free_result($pfcs_result);

	if (!$pfcs[$profilefield['profilefieldcategoryid']])
	{
		$profilefield['profilefieldcategoryid'] = 0;
	}
	print_radio_row($vbphrase['profile_field_category'], 'profilefield[profilefieldcategoryid]', $pfcs, $profilefield['profilefieldcategoryid']);

	if ($vbulletin->GPC['type'] == 'input')
	{
		print_input_row($vbphrase['default_value_you_may_specify_a_default_registration_value'], 'profilefield[data]', $profilefield['data'], 0);
	}
	if ($vbulletin->GPC['type'] == 'textarea')
	{
		print_textarea_row($vbphrase['default_value_you_may_specify_a_default_registration_value'], 'profilefield[data]', $profilefield['data'], 10, 40, 0);
	}
	if ($vbulletin->GPC['type'] == 'textarea' OR $vbulletin->GPC['type'] == 'input')
	{
		print_input_row($vbphrase['max_length_of_allowed_user_input'], 'profilefield[maxlength]', $profilefield['maxlength']);
		print_input_row($vbphrase['field_length'], 'profilefield[size]', $profilefield['size']);
	}
	if ($vbulletin->GPC['type'] == 'textarea')
	{
		print_input_row($vbphrase['text_area_height'], 'profilefield[height]', $profilefield['height']);
	}
	if ($vbulletin->GPC['type'] == 'select')
	{
		print_textarea_row(construct_phrase($vbphrase['x_enter_the_options_that_the_user_can_choose_from'], $vbphrase['options']), 'profilefield[data]', $profilefield['data'], 10, 40, 0);
		print_select_row($vbphrase['set_default_if_yes_first'], 'profilefield[def]', array(0 => $vbphrase['none'], 1 => $vbphrase['yes_including_a_blank'], 2 => $vbphrase['yes_but_no_blank_option']),  $profilefield['def']);
	}
	if ($vbulletin->GPC['type'] == 'radio')
	{
		print_textarea_row(construct_phrase($vbphrase['x_enter_the_options_that_the_user_can_choose_from'], $vbphrase['options']), 'profilefield[data]', $profilefield['data'], 10, 40, 0);
		print_yes_no_row($vbphrase['set_default_if_yes_first'], 'profilefield[def]', $profilefield['def']);
	}
	if ($vbulletin->GPC['type'] == 'checkbox')
	{
		print_input_row($vbphrase['limit_selection'], 'profilefield[size]', $profilefield['limit']);
		if ($_REQUEST['do'] == 'add')
		{
			print_textarea_row(construct_phrase($vbphrase['x_enter_the_options_that_the_user_can_choose_from'], $vbphrase['options']) . "<br /><dfn>$vbphrase[note_max_31_options]</dfn>", 'profilefield[data]', '', 10, 40, 0);
		}
		else
		{
			print_label_row($vbphrase['fields'], '<input type="image" src="../' . $vbulletin->options['cleargifurl'] . '"><input type="submit" class="button" value="' . $vbphrase['modify'] . '" tabindex="1" name="modifyfields">');
		}
	}
	if ($vbulletin->GPC['type'] == 'select_multiple')
	{
		print_input_row($vbphrase['limit_selection'], 'profilefield[size]', $profilefield['limit']);
		print_input_row($vbphrase['box_height'], 'profilefield[height]', $profilefield['boxheight']);
		if ($_REQUEST['do'] == 'add')
		{
			print_textarea_row(construct_phrase($vbphrase['x_enter_the_options_that_the_user_can_choose_from'], $vbphrase['options']) . "<br /><dfn>$vbphrase[note_max_31_options]</dfn>", 'profilefield[data]', '', 10);
		}
		else
		{
			print_label_row($vbphrase['fields'], '<input type="image" src="../' . $vbulletin->options['cleargifurl'] . '"><input type="submit" class="button" value="' . $vbphrase['modify'] . '" tabindex="1" name="modifyfields">');
		}
	}
	if ($_REQUEST['do'] == 'edit')
	{
		if ($vbulletin->GPC['type'] == 'input' OR $vbulletin->GPC['type'] == 'textarea')
		{
			if ($vbulletin->GPC['type'] == 'input')
			{
				$inputchecked = 'checked="checked"';
			}
			else
			{
				$textareachecked = 'checked="checked"';
			}
			print_label_row($vbphrase['profile_field_type'], "
				<label for=\"newtype_input\"><input type=\"radio\" name=\"newtype\" value=\"input\" id=\"newtype_input\" tabindex=\"1\" $inputchecked>" . $vbphrase['single_line_text_box'] . "</label><br />
				<label for=\"newtype_textarea\"><input type=\"radio\" name=\"newtype\" value=\"textarea\" id=\"newtype_textarea\" $textareachecked>" . $vbphrase['multiple_line_text_box'] . "</label>
			", '', 'top', 'newtype');
		}
		else if ($vbulletin->GPC['type'] == 'checkbox' OR $vbulletin->GPC['type'] == 'select_multiple')
		{
			if ($vbulletin->GPC['type'] == 'checkbox')
			{
				$checkboxchecked = 'checked="checked"';
			}
			else
			{
				$multiplechecked = 'checked="checked"';
			}
			print_label_row($vbphrase['profile_field_type'], "
				<label for=\"newtype_checkbox\"><input type=\"radio\" name=\"newtype\" value=\"checkbox\" id=\"newtype_checkbox\" tabindex=\"1\" $checkboxchecked>" . $vbphrase['multiple_selection_checkbox'] . "</label><br />
				<label for=\"newtype_multiple\"><input type=\"radio\" name=\"newtype\" value=\"select_multiple\" id=\"newtype_multiple\" tabindex=\"1\" $multiplechecked>" . $vbphrase['multiple_selection_menu'] . "</label>
			");
		}

	}
	print_input_row($vbphrase['display_order'], 'profilefield[displayorder]', $profilefield['displayorder']);
	//print_yes_no_row($vbphrase['field_required'], 'profilefield[required]', $profilefield['required']);
	print_select_row($vbphrase['field_required'], 'profilefield[required]', array(
		1 => $vbphrase['yes_at_registration'],
		3 => $vbphrase['yes_always'],
		0 => $vbphrase['no'],
		2 => $vbphrase['no_but_on_register']
	), $profilefield['required']);
	print_select_row($vbphrase['field_editable_by_user'], 'profilefield[editable]', array(
		1 => $vbphrase['yes'],
		0 => $vbphrase['no'],
		2 => $vbphrase['only_at_registration']
	), $profilefield['editable']);
	print_yes_no_row($vbphrase['field_hidden_on_profile'], 'profilefield[hidden]', $profilefield['hidden']);
	print_yes_no_row($vbphrase['field_searchable_on_members_list'], 'profilefield[searchable]', $profilefield['searchable']);
	if ($vbulletin->GPC['type'] != 'textarea')
	{
		print_yes_no_row($vbphrase['show_on_members_list'], 'profilefield[memberlist]', $profilefield['memberlist']);
	}

	if ($vbulletin->GPC['type'] == 'select' OR $vbulletin->GPC['type'] == 'radio')
	{
		print_table_break();
		print_table_header($vbphrase['optional_input']);
		print_yes_no_row($vbphrase['allow_user_to_input_their_own_value_for_this_option'], 'profilefield[optional]', $profilefield['optional']);
		print_input_row($vbphrase['max_length_of_allowed_user_input'], 'profilefield[maxlength]', $profilefield['maxlength']);
		print_input_row($vbphrase['field_length'], 'profilefield[size]', $profilefield['size']);
	}
	if ($vbulletin->GPC['type'] != 'select_multiple' AND $vbulletin->GPC['type'] != 'checkbox')
	{
		print_input_row($vbphrase['regular_expression_require_match'], 'profilefield[regex]', $profilefield['regex']);
	}

	print_table_break();
	print_table_header($vbphrase['display_page']);
	print_select_row($vbphrase['which_page_displays_option'], 'profilefield[form]', array(
		$vbphrase['edit_your_details'],
		"$vbphrase[options]: $vbphrase[log_in] / $vbphrase[privacy]",
		"$vbphrase[options]: $vbphrase[messaging] / $vbphrase[notification]",
		"$vbphrase[options]: $vbphrase[thread_viewing]",
		"$vbphrase[options]: $vbphrase[date] / $vbphrase[time]",
		"$vbphrase[options]: $vbphrase[other]"
	), $profilefield['form']);

	print_submit_row($vbphrase['save']);
}

// ###################### Start Rename Checkbox Data #######################
if ($_REQUEST['do'] == 'renamecheckbox')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'id' => TYPE_UINT,
	));

	$boxdata = $db->query_first("
		SELECT data,type
		FROM " . TABLE_PREFIX . "profilefield
		WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
	");
	$data = unserialize($boxdata['data']);
	foreach ($data AS $index => $value)
	{
		if ($index + 1 == $vbulletin->GPC['id'])
		{
			$oldfield = $value;
			break;
		}
	}

	print_form_header('profilefield', 'dorenamecheckbox');
	construct_hidden_code('profilefieldid', $vbulletin->GPC['profilefieldid']);
	construct_hidden_code('id', $vbulletin->GPC['id']);
	print_table_header($vbphrase['rename']);
	print_input_row($vbphrase['name'], 'newfield', $oldfield);
	print_submit_row($vbphrase['save']);

}

// ###################### Start Rename Checkbox Data #######################
if ($_POST['do'] == 'dorenamecheckbox')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'newfield' => TYPE_NOHTML,
		'id'       => TYPE_UINT
	));

	if (!empty($vbulletin->GPC['newfield']))
	{
		$boxdata = $db->query_first("
			SELECT data
			FROM " . TABLE_PREFIX . "profilefield
			WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
		");
		$data = unserialize($boxdata['data']);
		foreach ($data AS $index => $value)
		{
			if (strtolower($value) == strtolower($vbulletin->GPC['newfield']))
			{
				print_stop_message('this_is_already_option_named_x', $value);
			}
		}

		$index = $vbulletin->GPC['id'] - 1;
		$data["$index"] = $vbulletin->GPC['newfield'];

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "profilefield
			SET data = '" . $db->escape_string(serialize($data)) . "'
			WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
		");
	}
	else
	{
		print_stop_message('please_complete_required_fields');
	}

	define('CP_REDIRECT', "profilefield.php?do=modifycheckbox&profilefieldid=" . $vbulletin->GPC['profilefieldid']);
	print_stop_message('saved_option_x_successfully', $vbulletin->GPC['newfield']);
}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'deletecheckbox')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'id' => TYPE_UINT
	));

	print_form_header('profilefield', 'dodeletecheckbox');
	construct_hidden_code('profilefieldid', $vbulletin->GPC['profilefieldid']);
	construct_hidden_code('id', $vbulletin->GPC['id']);
	print_table_header($vbphrase['confirm_deletion']);
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_user_profile_field']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);

}

// ###################### Process Remove Checkbox Option #######################
if ($_POST['do'] == 'dodeletecheckbox')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'id' => TYPE_UINT
	));

	$boxdata = $db->query_first("
		SELECT data
		FROM " . TABLE_PREFIX . "profilefield
		WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
	");
	$data = unserialize($boxdata['data']);

	$db->query_write("UPDATE " . TABLE_PREFIX . "userfield SET temp = field" . $vbulletin->GPC['profilefieldid']);

	foreach ($data AS $index => $value)
	{
		$index;
		$index2 = $index + 1;
		if ($index2 >= $vbulletin->GPC['id'])
		{
			if ($vbulletin->GPC['id'] == $index2)
			{
				build_profilefield_bitfields($vbulletin->GPC['profilefieldid'], $index2); // Delete this value
			}
			else
			{
				build_profilefield_bitfields($vbulletin->GPC['profilefieldid'], $index2, $index);
			}
			if ($index2 == sizeof($data))
			{
				unset($data["$index"]);
			}
			else
			{
				$data["$index"] = $data["$index2"];
			}
		}
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "userfield
		SET field" . $vbulletin->GPC['profilefieldid'] . " = temp,
		temp = ''
	");

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "profilefield
		SET data = '" . $db->escape_string(serialize($data)) . "'
		WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
	");

	define('CP_REDIRECT', "profilefield.php?do=modifycheckbox&profilefieldid=" . $vbulletin->GPC['profilefieldid']);
	print_stop_message('deleted_option_successfully');
}

// ###################### Start Add Checkbox #######################
if ($_POST['do'] == 'addcheckbox')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'newfield'    => TYPE_NOHTML,
		'newfieldpos' => TYPE_UINT,
	));

	if (!empty($vbulletin->GPC['newfield']))
	{
		$boxdata = $db->query_first("
			SELECT data
			FROM " . TABLE_PREFIX . "profilefield
			WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
		");
		$data = unserialize($boxdata['data']);

		if (sizeof($data) >= 31)
		{
 			print_stop_message('too_many_profile_field_options', sizeof($data));
 		}

		foreach ($data AS $index => $value)
		{
			if (strtolower($value) == strtolower($vbulletin->GPC['newfield']))
			{
				print_stop_message('this_is_already_option_named_x', $value);
			}
		}

		$db->query_write("UPDATE " . TABLE_PREFIX . "userfield SET temp = field" . $vbulletin->GPC['profilefieldid']);

		for ($x = sizeof($data); $x >= 0; $x--)
		{
			if ($x > $vbulletin->GPC['newfieldpos'])
			{
				$data["$x"] = $data[$x - 1];
				build_profilefield_bitfields($vbulletin->GPC['profilefieldid'], $x, $x + 1);
			}
			else if ($x == $vbulletin->GPC['newfieldpos'])
			{
				$data["$x"] = $vbulletin->GPC['newfield'];
			}
		}

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "userfield
			SET field" . $vbulletin->GPC['profilefieldid'] . " = temp,
			temp = ''
		");

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "profilefield SET
			data = '" . $db->escape_string(serialize($data)) . "'
			WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
		");

		define('CP_REDIRECT', "profilefield.php?do=modifycheckbox&profilefieldid=" . $vbulletin->GPC['profilefieldid']);
		print_stop_message('saved_option_successfully');
	}
	else
	{
		print_stop_message('invalid_option_specified');
	}

}

// ###################### Start Move Checkbox #######################

if ($_REQUEST['do'] == 'movecheckbox')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'direction' => TYPE_STR,
		'id'        => TYPE_UINT
	));

	$boxdata = $db->query_first("
		SELECT data
		FROM " . TABLE_PREFIX . "profilefield
		WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
	");
	$data = unserialize($boxdata['data']);

	$db->query_write("UPDATE " . TABLE_PREFIX . "userfield SET temp = field" . $vbulletin->GPC['profilefieldid']);

	if ($vbulletin->GPC['direction'] == 'up')
	{
		build_bitwise_swap($vbulletin->GPC['profilefieldid'], $vbulletin->GPC['id'], $vbulletin->GPC['id'] - 1);
	}
	else
	{ // Down
		build_bitwise_swap($vbulletin->GPC['profilefieldid'], $vbulletin->GPC['id'], $vbulletin->GPC['id'] + 1);
	}

	foreach ($data AS $index => $value)
	{
		if ($index + 1 == $vbulletin->GPC['id'])
		{
			$temp = $data["$index"];
			if ($vbulletin->GPC['direction'] == 'up')
			{
				$data["$index"] = $data[strval($index - 1)];
				$data[strval($index - 1)] = $temp;
			}
			else

			{ // Down
				$data["$index"] = $data[strval($index + 1)];
				$data[strval($index + 1)] = $temp;
			}
			break;
		}
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "userfield
		SET field" . $vbulletin->GPC['profilefieldid'] . " = temp,
		temp = ''
	");

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "profilefield
		SET data = '" . $db->escape_string(serialize($data)) . "'
		WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
	");

	$_REQUEST['do'] = 'modifycheckbox';

}

// ###################### Start Modify Checkbox Data #######################
if ($_REQUEST['do'] == 'modifycheckbox')
{

	$boxdata = $db->query_first("
		SELECT data, type
		FROM " . TABLE_PREFIX . "profilefield
		WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
	");

	if ($boxdata['data'] != '')
	{
		$index = 0;
		$output = '<table cellspacing="0" cellpadding="4"><tr><td>&nbsp;</td><td><b>' . $vbphrase['move'] . '</b></td><td colspan=2><b>' . $vbphrase['option'] . '</b></td></tr>';
		$data = unserialize($boxdata['data']);
		foreach ($data AS $index => $value)
		{
			$index++;
			if ($index != 1)
			{
				$moveup = "<a href=\"profilefield.php?" . $vbulletin->session->vars['sessionurl'] . "profilefieldid=" . $vbulletin->GPC['profilefieldid'] . "&do=movecheckbox&direction=up&id=$index\"><img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/move_up.gif\" border=\"0\" /></a> ";
			}
			else
			{
				$moveup = '<img src="../' . $vbulletin->options['cleargifurl'] . '" width="11" border="0" alt="" /> ';
			}
			if ($index != sizeof($data))
			{
				$movedown = "<a href=\"profilefield.php?" . $vbulletin->session->vars['sessionurl'] . "profilefieldid=" . $vbulletin->GPC['profilefieldid'] . "&do=movecheckbox&direction=down&id=$index\"><img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/move_down.gif\" border=\"0\" /></a> ";
			}
			else
			{
				unset($movedown);
			}
			$output .= "<tr><td align=\"right\">$index.</td><td>$moveup$movedown</td><td>$value</td><td>".
			construct_link_code($vbphrase['rename'], "profilefield.php?do=renamecheckbox&profilefieldid=" . $vbulletin->GPC['profilefieldid'] . "&id=$index")
			."</td><td>".
			iif(sizeof($xxxdata) > 1, construct_link_code($vbphrase['move'], "profilefield.php?do=movecheckbox&profilefieldid=" . $vbulletin->GPC['profilefieldid'] . "&id=$index"), '')
			. "</td><td>".
			iif(sizeof($data) > 1, construct_link_code($vbphrase['delete'], "profilefield.php?do=deletecheckbox&profilefieldid=" . $vbulletin->GPC['profilefieldid'] . "&id=$index"), '')
			. "</td></tr>";
		}
		$output .= '</table>';
	}
	else
	{
		$output = "<p>" . construct_phrase($vbphrase['this_profile_fields_no_options'], $boxdata['type']) . "</p>";
	}

	print_form_header('', '');
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['user_profile_field'], construct_link_code($vbphrase['field' . $vbulletin->GPC['profilefieldid'] . '_title'], "profilefield.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;profilefieldid=" . $vbulletin->GPC['profilefieldid']), $vbulletin->GPC['profilefieldid']));
	print_table_break();
	print_table_header($vbphrase['modify']);
	print_description_row($output);
	print_table_footer();


	if (sizeof($data) < 31)
	{
		print_form_header('profilefield', 'addcheckbox');
		construct_hidden_code('profilefieldid', $vbulletin->GPC['profilefieldid']);
		print_table_header($vbphrase['add']);
		print_description_row($vbphrase['note_max_31_options']);
		print_input_row($vbphrase['name'], 'newfield');
		$output = "<select name=\"newfieldpos\" tabindex=\"1\" class=\"bginput\"><option value=\"0\">" . $vbphrase['first']."</option>\n";
		if ($boxdata['data'] != '')
		{
			foreach ($data AS $index => $value)
			{
				$index++;
				$output .= "<option value=\"$index\"" . iif(sizeof($data) == $index, " selected=\"selected\"") . ">" . construct_phrase($vbphrase['after_x'], $value) . "</option>\n";
			}
		}
		print_label_row($vbphrase['postition'], $output);
		print_submit_row($vbphrase['add_new_option']);
	}

}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'remove')
{

	print_form_header('profilefield', 'kill');
	construct_hidden_code('profilefieldid', $vbulletin->GPC['profilefieldid']);
	print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], htmlspecialchars_uni($vbphrase['field' . $vbulletin->GPC['profilefieldid'] . '_title'])));
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_user_profile_field']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// ###################### Start Kill #######################

if ($_POST['do'] == 'kill')
{
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "phrase
		WHERE fieldname = 'cprofilefield' AND
				varname IN ('field" . $vbulletin->GPC['profilefieldid'] . "_title', 'field" . $vbulletin->GPC['profilefieldid'] . "_desc')
	");

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	require_once(DIR . '/includes/class_dbalter.php');
	$db_alter = new vB_Database_Alter_MySQL($db);

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "profilefield WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid']);
	if ($db_alter->fetch_table_info('userfield'))
	{
		$db_alter->drop_field("field" . $vbulletin->GPC['profilefieldid']);
	}
	$db->query_write("OPTIMIZE TABLE " . TABLE_PREFIX . "userfield");

	build_profilefield_cache();

	define('CP_REDIRECT', 'profilefield.php?do=modify');
	print_stop_message('deleted_user_profile_field_successfully');
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	// cache profile field categories
	$pfcs = array(0);
	$pfcs_result = $db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "profilefieldcategory
		ORDER BY displayorder
	");
	while ($pfc = $db->fetch_array($pfcs_result))
	{
		$pfcs["$pfc[profilefieldcategoryid]"] = $pfc['profilefieldcategoryid'];
	}
	$db->free_result($pfcs_result);

	// query profile fields
	$profilefields = $db->query_read("
		SELECT profilefieldid, profilefieldcategoryid, type, form, displayorder,
			IF(required=2, 0, required) AS required,
			editable, hidden, searchable, memberlist
		FROM " . TABLE_PREFIX . "profilefield
	");

	if ($db->num_rows($profilefields))
	{
		$forms = array(
			0 => $vbphrase['edit_your_details'],
			1 => "$vbphrase[options]: $vbphrase[log_in] / $vbphrase[privacy]",
			2 => "$vbphrase[options]: $vbphrase[messaging] / $vbphrase[notification]",
			3 => "$vbphrase[options]: $vbphrase[thread_viewing]",
			4 => "$vbphrase[options]: $vbphrase[date] / $vbphrase[time]",
			5 => "$vbphrase[options]: $vbphrase[other]",
		);

		$optionfields = array(
			'required'   => $vbphrase['required'],
			'editable'   => $vbphrase['editable'],
			'hidden'     => $vbphrase['hidden'],
			'searchable' => $vbphrase['searchable'],
			'memberlist' => $vbphrase['members_list'],
		);

		$fields = array();

		while ($profilefield = $db->fetch_array($profilefields))
		{
			$profilefield['title'] = htmlspecialchars_uni($vbphrase['field' . $profilefield['profilefieldid'] . '_title']);
			$fields["{$profilefield['form']}"]["$profilefield[profilefieldcategoryid]"]["{$profilefield['displayorder']}"]["{$profilefield['profilefieldid']}"] = $profilefield;
		}
		$db->free_result($profilefields);

		// sort by form and displayorder
		foreach ($fields AS $profilefieldcategoryid => $profilefieldcategory)
		{
			ksort($fields["$profilefieldcategoryid"]);
			foreach (array_keys($fields["$profilefieldcategoryid"]) AS $key)
			{
				ksort($fields["$profilefieldcategoryid"]["$key"]);
			}
		}

		$numareas = sizeof($fields);
		$areacount = 0;

		print_form_header('profilefield', 'displayorder');

		foreach ($forms AS $formid => $formname)
		{
			if (is_array($fields["$formid"]))
			{
				print_table_header(construct_phrase($vbphrase['user_profile_fields_in_area_x'], $formname), 5);

				echo "
				<col width=\"50%\" align=\"" . vB_Template_Runtime::fetchStyleVar('left') . "\"></col>
				<col width=\"50%\" align=\"" . vB_Template_Runtime::fetchStyleVar('left') . "\"></col>
				<col align=\"" . vB_Template_Runtime::fetchStyleVar('left') . "\" style=\"white-space:nowrap\"></col>
				<col align=\"center\" style=\"white-space:nowrap\"></col>
				<col align=\"center\" style=\"white-space:nowrap\"></col>
				";

				print_cells_row(array(
					"$vbphrase[title] / $vbphrase[profile_field_type]",
					$vbphrase['options'],
					$vbphrase['name'],
					'<nobr>' . $vbphrase['display_order'] . '</nobr>',
					$vbphrase['controls']
				), 1, '', -1);

				foreach ($pfcs AS $pfcid)
				{
					if (is_array($fields["$formid"]["$pfcid"]))
					{
						if ($pfcid > 0)
						{
							print_description_row($vbphrase['category' . $pfcid . '_title'] . '<div class="normal">' . $vbphrase['category' . $pfcid . '_desc'] . '</div>', false, 5, 'optiontitle');
						}
						else
						{
							print_description_row('(' . $vbphrase['uncategorized'] . ')', false, 5, 'optiontitle');
						}

						foreach ($fields["$formid"]["$pfcid"] AS $displayorder => $profilefields)
						{
							foreach ($profilefields AS $_profilefieldid => $profilefield)
							{
								$bgclass = fetch_row_bgclass();

								$options = array();
								foreach ($optionfields AS $fieldname => $optionname)
								{
									if ($profilefield["$fieldname"])
									{
										$options[] = $optionname;
									}
								}
								$options = implode(', ', $options) . '&nbsp;';


								echo "
								<tr>
									<td class=\"$bgclass\"><strong>$profilefield[title] <dfn>{$types["{$profilefield['type']}"]}</dfn></strong></td>
									<td class=\"$bgclass\">$options</td>
									<td class=\"$bgclass\">field$_profilefieldid</td>
									<td class=\"$bgclass\"><input type=\"text\" class=\"bginput\" name=\"order[$_profilefieldid]\" value=\"$profilefield[displayorder]\" size=\"5\" /></td>
									<td class=\"$bgclass\">" .
									construct_link_code($vbphrase['edit'], "profilefield.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;profilefieldid=$_profilefieldid") .
									construct_link_code($vbphrase['delete'], "profilefield.php?" . $vbulletin->session->vars['sessionurl'] . "do=remove&profilefieldid=$_profilefieldid") .
									"</td>
								</tr>";
							}
						}
					}
				}

				print_description_row("<input type=\"submit\" class=\"button\" value=\"$vbphrase[save_display_order]\" accesskey=\"s\" />", 0, 5, 'tfoot', vB_Template_Runtime::fetchStyleVar('right'));

				if (++$areacount < $numareas)
				{
					print_table_break('');
				}
			}
		}

		print_table_footer();
	}
	else
	{
		print_stop_message('no_profile_fields_defined');
	}

}
// #############################################################################

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 34257 $
|| ####################################################################
\*======================================================================*/
?>
