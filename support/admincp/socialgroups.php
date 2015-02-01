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
define('CVS_REVISION', '$RCSfile$ - $Revision: 62098 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('socialgroups', 'search');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_socialgroup_search.php');
require_once(DIR . '/includes/functions_socialgroup.php');

if (!can_administer('canadminthreads'))
{
	print_cp_no_permission();
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'search';
}

// Print the Header
print_cp_header($vbphrase['social_groups']);

$vbulletin->input->clean_array_gpc('r', array(
	'userid'    => TYPE_UINT
));

// #######################################################################
if ($_REQUEST['do'] == 'search')
{
	print_form_header('socialgroups', 'dosearch');

	print_table_header($vbphrase['search_social_groups']);

	print_input_row($vbphrase['key_words'], 'filtertext');

	// get category options
	$category_options = array();
	$categories = fetch_socialgroup_category_options(false, true);

	foreach ($categories AS $category)
	{
		$category_options[$category['socialgroupcategoryid']] = $category['title'];
	}
	unset($categories);

	print_select_row($vbphrase['category_is'], 'category', $category_options, 0);

	print_input_row($vbphrase['members_greater_than'], 'members_gteq', '', true, 5);
	print_input_row($vbphrase['members_less_than'], 'members_lteq', '', true, 5);
	print_time_row($vbphrase['creation_date_is_before'], 'date_lteq', '', false);
	print_time_row($vbphrase['creation_date_is_after'], 'date_gteq', '', false);
	print_input_row($vbphrase['group_created_by'], 'creator');

	print_select_row($vbphrase['group_type'], 'type', array(
		''           => '',
		'public'     => $vbphrase['group_type_public'],
		'moderated'  => $vbphrase['group_type_moderated'],
		'inviteonly' => $vbphrase['group_type_inviteonly']
	));

	print_submit_row($vbphrase['search']);
	print_cp_footer();
}

// #######################################################################
if ($_REQUEST['do'] == 'groupsby' AND !empty($vbulletin->GPC['userid']))
{
	if (verify_id('user', $vbulletin->GPC['userid'], false))
	{
		$vbulletin->GPC['creatoruserid'] = $vbulletin->GPC['userid'];
		$_REQUEST['do'] = 'dosearch';
	}
	else
	{
		print_cp_message($vbphrase['invalid_username']);
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'dosearch')
{
	$socialgroupsearch = new vB_SGSearch($vbulletin);

	$vbulletin->input->clean_array_gpc('r', array(
		'filtertext'    => TYPE_NOHTML,
		'category' => TYPE_UINT,
		'members_lteq'  => TYPE_UINT,
		'members_gteq'  => TYPE_UINT,
		'date_gteq'     => TYPE_UNIXTIME,
		'date_lteq'     => TYPE_UNIXTIME,
		'creator'       => TYPE_NOHTML,
		'type'          => TYPE_NOHTML
	));

	if ($vbulletin->GPC['creator'] != '')
	{
		$user = $vbulletin->db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = '" . $vbulletin->db->escape_string($vbulletin->GPC['creator']) . "'");
		if (!empty($user['userid']))
		{
			$vbulletin->GPC['creatoruserid'] = $user['userid'];
		}
		else
		{
			print_cp_message($vbphrase['invalid_username']);
		}
	}

	$filters = array();

	if (!empty($vbulletin->GPC['filtertext']))
	{
		$filters['text'] = $vbulletin->GPC['filtertext'];
	}

	if ($vbulletin->GPC['category'])
	{
		$filters['category'] = $vbulletin->GPC['category'];
	}

	if (!empty($vbulletin->GPC['date_lteq']))
	{
		$filters['date_lteq'] = $vbulletin->GPC['date_lteq'];
	}

	if (!empty($vbulletin->GPC['date_gteq']))
	{
		$filters['date_gteq'] = $vbulletin->GPC['date_gteq'];
	}

	if (!empty($vbulletin->GPC['members_lteq']))
	{
		$filters['members_lteq'] = $vbulletin->GPC['members_lteq'];
	}

	if (!empty($vbulletin->GPC['members_gteq']))
	{
		$filters['members_gteq'] = $vbulletin->GPC['members_gteq'];
	}

	if (!empty($vbulletin->GPC['creatoruserid']))
	{
		$filters['creator'] = $vbulletin->GPC['creatoruserid'];
	}

	if (!empty($vbulletin->GPC['type']))
	{
		$filters['type'] = $vbulletin->GPC['type'];
	}

	foreach ($filters AS $key => $value)
	{
		$socialgroupsearch->add($key, $value);
	}

	$groups = $socialgroupsearch->fetch_results();

	if (!empty($groups))
	{
		print_form_header('socialgroups','delete');
		print_table_header($vbphrase['search_results']);

		echo '
			<tr>
			<td class="thead"><input type="checkbox" name="allbox" id="cb_checkall" onclick="js_check_all(this.form)" /></td>
			<td width="100%" class="thead"><label for="cb_checkall">' . $vbphrase['check_uncheck_all'] . '</label></td>
			</tr>';

		foreach ($groups AS $group)
		{
			$group = prepare_socialgroup($group);

			$cell = '<span class="shade smallfont" style="float: ' . vB_Template_Runtime::fetchStyleVar('right') . '; text-align: ' . vB_Template_Runtime::fetchStyleVar('right') . ';">' . $vbphrase['group_desc_' . $group['type']] . '<br />' . construct_phrase($vbphrase['x_members'], $group['members']);

			if ($group['moderatedmembers'])
			{
				$cell .= '<br />' . construct_phrase($vbphrase['x_awaiting_moderation'], $group['moderatedmembers']);
			}

			$ownerlink = fetch_seo_url('member|bburl', $group, null, 'creatoruserid', 'creatorusername');
			$grouplink = fetch_seo_url('group|bburl', $group);
			$cell .= '</span>
				<div style="text-align: ' . vB_Template_Runtime::fetchStyleVar('left') . '"><a href="' . $grouplink . '" target="group">' . $group['name'] . '</a></div>
				<div class="smallfont" style="text-align: ' . vB_Template_Runtime::fetchStyleVar('left') . '">' . construct_phrase($vbphrase['group_created_by_x'], $ownerlink, $group['creatorusername']) . '</div>';

			if (!empty($group['description']))
			{
				$cell .= '<div style="text-align: ' . vB_Template_Runtime::fetchStyleVar('left') . '">' . $group['description'] . '</div>';
			}

			print_cells_row(array(
				'<input type="checkbox" name="ids[' . $group['groupid'] . ']" />',
				$cell
			));

		}

		print_submit_row($vbphrase['delete_selected_groups']);
	}
	else
	{
		print_cp_message($vbphrase['no_groups_found']);
	}
}


// #######################################################################
if ($_POST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'ids' => TYPE_ARRAY_KEYS_INT
	));

	if (empty($vbulletin->GPC['ids']))
	{
		print_cp_message($vbphrase['you_did_not_select_any_groups']);
	}

	print_form_header('socialgroups','kill');
	print_table_header($vbphrase['confirm_deletion']);

	print_description_row(construct_phrase($vbphrase['are_you_sure_you_want_to_delete_x_groups'], sizeof($vbulletin->GPC['ids'])), false, 2, '', 'center');

	construct_hidden_code('ids', sign_client_string(serialize($vbulletin->GPC['ids'])));

	print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);
}


// #######################################################################
if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'ids' => TYPE_NOCLEAN
	));

	$ids = @unserialize(verify_client_string($vbulletin->GPC['ids']));

	if (is_array($ids) AND !empty($ids))
	{
		print_form_header('socialgroups', '');
		print_table_header($vbphrase['deleting_groups']);

		$groups = $vbulletin->db->query_read("
			SELECT * FROM " . TABLE_PREFIX . "socialgroup
			WHERE groupid IN (" . implode(',', $ids) . ")
		");

		if ($vbulletin->db->num_rows($groups) == 0)
		{
			print_description_row($vbphrase['no_groups_found']);
		}

		while ($group = $vbulletin->db->fetch_array($groups))
		{
			$socialgroupdm = datamanager_init('SocialGroup', $vbulletin);

			print_description_row(construct_phrase($vbphrase['deleting_x'], $group['name']));

			$socialgroupdm->set_existing($group);
			$socialgroupdm->delete();

			unset($socialgroupdm);
		}
	}
	else
	{
		// This should never happen without playing with the URLs
		print_cp_message($vbphrase['no_groups_selected_or_invalid_input']);
	}

	print_table_footer();

	print_cp_redirect('socialgroups.php', 5);
}


// #######################################################################
if ($_POST['do'] == 'updatecategory')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'socialgroupcategoryid' => TYPE_UINT,
		'title' => TYPE_STR,
		'description' => TYPE_STR,
		'displayorder' => TYPE_UINT
	));

	$sgcatdata = datamanager_init('SocialGroupCategory', $vbulletin);

	if ($vbulletin->GPC['socialgroupcategoryid'] AND $category = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "socialgroupcategory WHERE socialgroupcategoryid = " . $vbulletin->GPC['socialgroupcategoryid']))
	{
		// update
		$sgcatdata->set_existing($category);
	}
	else if ($vbulletin->GPC['socialgroupcategoryid'])
	{
		// error
		print_stop_message('invalid_social_group_category_specified');
	}
	else
	{
		// add
		$sgcatdata->set('creatoruserid', $vbulletin->userinfo['userid']);
	}

	if ('' == $vbulletin->GPC['title'])
	{
		print_stop_message('please_complete_required_fields');
	}

	$sgcatdata->set('title', $vbulletin->GPC['title']);
	$sgcatdata->set('description', $vbulletin->GPC['description']);
	$sgcatdata->set('displayorder', $vbulletin->GPC['displayorder']);

	$sgcatdata->save();

	unset($sgcatdata);

	print_cp_redirect('socialgroups.php?do=categories', 0);
}

// #######################################################################
if ($_REQUEST['do'] == 'editcategory')
{
	$vbulletin->input->clean_gpc('r', 'socialgroupcategoryid', TYPE_UINT);

	if ($vbulletin->GPC['socialgroupcategoryid'] AND $category = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "socialgroupcategory WHERE socialgroupcategoryid = " . $vbulletin->GPC['socialgroupcategoryid']))
	{
		// edit
		print_form_header('socialgroups', 'updatecategory');
		construct_hidden_code('socialgroupcategoryid', $category['socialgroupcategoryid']);
		print_table_header($vbphrase['edit_social_group_category'] . " <span class=\"normal\">" . htmlspecialchars_uni($category['title']) . "</span>");
	}
	else if ($vbulletin->GPC['socialgroupcategoryid'])
	{
		print_stop_message('invalid_social_group_category_specified');
	}
	else
	{
		// add
		print_form_header('socialgroups', 'updatecategory');
		print_table_header($vbphrase['add_new_socialgroup_category']);
	}

	print_input_row($vbphrase['title'], 'title', $category['title']);
	print_textarea_row($vbphrase['description'], 'description', $category['description']);
	print_input_row($vbphrase['display_order'], 'displayorder', $category['displayorder']);
	print_submit_row();
}


// #############################################################################
// perform deletion of category
if ($_POST['do'] == 'killcategory')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'socialgroupcategoryid' => TYPE_UINT,
		'destsocialgroupcategoryid' => TYPE_UINT
	));

	if (!empty($vbulletin->GPC['socialgroupcategoryid']) AND !empty($vbulletin->GPC['destsocialgroupcategoryid']))
	{
		$categories = array();
		$categoriesresult = $db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "socialgroupcategory
			WHERE socialgroupcategoryid IN({$vbulletin->GPC[socialgroupcategoryid]}, {$vbulletin->GPC[destsocialgroupcategoryid]})
		");
		if ($db->num_rows($categoriesresult) == 2)
		{
			while ($category = $db->fetch_array($categoriesresult))
			{
				$categories["$category[socialgroupcategoryid]"] = $category;
			}
			$db->free_result($categoriesresult);

			// move all groups that belong to this category into the destination category
			$groupsresult = $db->query_read("
				SELECT *
				FROM " . TABLE_PREFIX . "socialgroup
				WHERE socialgroupcategoryid = {$vbulletin->GPC[socialgroupcategoryid]}
			");
			while ($group = $db->fetch_array($groupsresult))
			{
				$sgdata = datamanager_init('SocialGroup', $vbulletin);
				$sgdata->set_existing($group);
				$sgdata->set('socialgroupcategoryid', $vbulletin->GPC['destsocialgroupcategoryid']);
				$sgdata->save();
			}
			$db->free_result($groupsresult);

			// delete the source category
			$sgcatdata = datamanager_init('SocialGroupCategory', $vbulletin);
			$sgcatdata->set_existing($categories[$vbulletin->GPC['socialgroupcategoryid']]);
			$sgcatdata->delete();

			define('CP_REDIRECT', 'socialgroups.php?do=categories');
			print_stop_message('social_group_category_deleted');
		}
	}
	else
	{
		print_stop_message('invalid_social_group_category_specified');
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'categoriesquickupdate')
{

	$vbulletin->input->clean_array_gpc('p', array('order' => TYPE_ARRAY_INT));

	if (is_array($vbulletin->GPC['order']))
	{
		$groupcategories = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "socialgroupcategory");
		while ($groupcategory = $db->fetch_array($groupcategories))
		{
			if (!isset($vbulletin->GPC['order']["$groupcategory[socialgroupcategoryid]"]))
			{
				continue;
			}

			$displayorder = $vbulletin->GPC['order']["$groupcategory[socialgroupcategoryid]"];
			if ($groupcategory['displayorder'] != $displayorder)
			{
				$groupcategorydm =& datamanager_init('Socialgroupcategory', $vbulletin, ERRTYPE_SILENT);
				$groupcategorydm->set_existing($groupcategory);
				$groupcategorydm->setr('displayorder', $displayorder);
				$groupcategorydm->save();
				unset($groupcategorydm);
			}
		}
	}

	build_forum_permissions();

	define('CP_REDIRECT', 'socialgroups.php?do=categories');
	print_stop_message('saved_display_order_successfully');
}

// #############################################################################
// confirm deletion of category
if ($_REQUEST['do'] == 'deletecategory')
{
	$vbulletin->input->clean_gpc('r', 'socialgroupcategoryid', TYPE_UINT);

	if (!empty($vbulletin->GPC['socialgroupcategoryid']))
	{
		$category_for_deletion = array();
		$category_options = array();

		$categories = fetch_socialgroup_category_options();

		if (sizeof($categories) < 2)
		{
			print_stop_message('cannot_delete_last_social_group_category');
		}

		$category_options = array();

		foreach ($categories AS $category)
		{
			if ($category['socialgroupcategoryid'] == $vbulletin->GPC['socialgroupcategoryid'])
			{
				$category_for_deletion = $category;
			}
			else
			{
				$category_options["$category[socialgroupcategoryid]"] = $category['title'] . " (" . construct_phrase($vbphrase['x_groups'], $category[groupcount]) . ")";
			}
		}
		unset($categories);

		print_form_header('socialgroups', 'killcategory');
		construct_hidden_code('socialgroupcategoryid', $category_for_deletion['socialgroupcategoryid']);
		print_table_header($vbphrase['confirm_deletion']);
		print_description_row(construct_phrase($vbphrase['are_you_sure_you_want_to_delete_category_x_y_groups'],
												$category_for_deletion['title'],
												$category_for_deletion['groupcount'])
		);
		print_select_row($vbphrase['select_destination_category'], 'destsocialgroupcategoryid', $category_options);
		print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
	}
	else
	{
		print_stop_message('invalid_social_group_category_specified');
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'categories')
{
	print_form_header('socialgroups', 'categoriesquickupdate');
	print_table_header($vbphrase['social_group_categories'], 5);
	print_cells_row(array(
		"$vbphrase[title] / $vbphrase[description]",
		$vbphrase['social_groups'],
		$vbphrase['creator'],
		$vbphrase['display_order'],
		$vbphrase['controls']
	), true);

	$categories = fetch_socialgroup_category_options();
	$groupcounts = array();

	foreach ($categories as $category)
	{
		$groupcounts["$category[socialgroupcategoryid]"] = $category['groupcount'];
	}
	unset($categories);

	$categoriesresult = $db->query_read("
		SELECT socialgroupcategory.*, user.username
		FROM " . TABLE_PREFIX . "socialgroupcategory AS socialgroupcategory
		INNER JOIN " .TABLE_PREFIX . "user AS user ON(user.userid = socialgroupcategory.creatoruserid)
		ORDER BY socialgroupcategory.displayorder, socialgroupcategory.title
	");

	$category_count = $db->num_rows($categoriesresult);

	if ($category_count)
	{
		while ($category = $db->fetch_array($categoriesresult))
		{
			$category['title'] = htmlspecialchars_uni($category['title']);
			$category['description'] = htmlspecialchars_uni($category['description']);

			print_cells_row(array(
				"<a href=\"socialgroups.php?" . $vbulletin->session->vars['sessionurl'] . "do=editcategory&amp;socialgroupcategoryid=$category[socialgroupcategoryid]\">$category[title]</a> <small>$category[description]</small>",
				"<a href=\"socialgroups.php?" . $vbulletin->session->vars['sessionurl'] . "do=dosearch&amp;category=$category[socialgroupcategoryid]\">" . vb_number_format($groupcounts["$category[socialgroupcategoryid]"]) . "</a>",
				"<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;userid=$category[creatoruserid]\">$category[username]</a>",
				"<input type=\"text\" class=\"bginput\" name=\"order[$category[socialgroupcategoryid]]\" value=\"$category[displayorder]\" tabindex=\"1\" size=\"3\" title=\"" . $vbphrase['edit_display_order'] . "\" />",
				'<div class="smallfont">' .
				construct_link_code($vbphrase['edit'], "socialgroups.php?" . $vbulletin->session->vars['sessionurl'] . "do=editcategory&amp;socialgroupcategoryid=" . $category['socialgroupcategoryid']) .
				construct_link_code($vbphrase['delete'], "socialgroups.php?" . $vbulletin->session->vars['sessionurl'] . "do=deletecategory&amp;socialgroupcategoryid=" . $category['socialgroupcategoryid']) .
				'</div>'
			));
		}
	}

	print_table_footer(5, "<input type=\"submit\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['save_display_order'] . "\" accesskey=\"s\" />" . construct_button_code($vbphrase['add_new_category'], "socialgroups.php?" . $vbulletin->session->vars['sessionurl'] . "do=editcategory"));
}

// Print Footer
print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 62098 $
|| ####################################################################
\*======================================================================*/
?>
