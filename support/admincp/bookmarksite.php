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
define('CVS_REVISION', '$RCSfile$ - $Revision: 35520 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array();
$specialtemplates = array('bookmarksitecache');

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_bookmarksite.php');

// ############################# LOG ACTION ###############################

$vbulletin->input->clean_array_gpc('r', array('bookmarksiteid' => TYPE_INT));

log_admin_action($vbulletin->GPC['bookmarksiteid'] != 0 ? "bookmark site id = " . $vbulletin->GPC['bookmarksiteid'] : '');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminsettings'))
{
	print_cp_no_permission();
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['social_bookmarking_manager']);

// default action
if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ########################################################################
// when we want to add a new site from the site list page we need change the action before the main 'socialbookmarks_setpost' handler
// we came here if somebody press the add button in the sitelist edit/save form
if (($_POST['do'] == 'socialbookmarks_setpost') AND $vbulletin->GPC['add'])
{
	$_POST['do'] = 'add';
}

// ########################################################################
// confirmed delete, we shall do the delete from the database
if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array('bookmarksiteid' => TYPE_UINT));

	if ($vbulletin->GPC['bookmarksiteid'] AND $bookmarksite = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "bookmarksite WHERE bookmarksiteid = " . $vbulletin->GPC['bookmarksiteid']))
	{
		$vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "bookmarksite WHERE bookmarksiteid = " . $vbulletin->GPC['bookmarksiteid']);

		// rebuild the cache
		build_bookmarksite_datastore();

		define('CP_REDIRECT', 'bookmarksite.php' . $vbulletin->session->vars['sessionurl_q']);
		print_stop_message('bookmark_site_deleted_successfully');
	}

	$_REQUEST['do'] = 'modify';
}

// ########################################################################
// delete handler - we want to delete one of the sites
if ($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('r', array('bookmarksiteid' => TYPE_UINT));

	if ($vbulletin->GPC['bookmarksiteid'] AND $bookmarksite = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "bookmarksite WHERE bookmarksiteid = " . $vbulletin->GPC['bookmarksiteid']))
	{
		// display the delete confirmation page
		print_delete_confirmation('bookmarksite', $vbulletin->GPC['bookmarksiteid'], 'bookmarksite', 'kill');
	}
	else
	{
		$_REQUEST['do'] = 'modify';
	}
}

// ########################################################################
// update handler - we sent the site details form (add new or edit old one)
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'bookmarksiteid' => TYPE_UINT,
		'title' => TYPE_NOHTML,
		'iconpath' => TYPE_STR,
		'active' => TYPE_BOOL,
		'displayorder' => TYPE_UINT,
		'url' => TYPE_STR,
		'utf8encode' => TYPE_BOOL
	));

	$vbulletin->GPC['url'] = preg_replace('/&(?!(#[0-9]+|[a-z]+);)/U', '&amp;', $vbulletin->GPC['url']);

	if (!$vbulletin->GPC['title'] OR !$vbulletin->GPC['url'])
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($vbulletin->GPC['bookmarksiteid'] AND $bookmarksite = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "bookmarksite WHERE bookmarksiteid = " . $vbulletin->GPC['bookmarksiteid']))
	{
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "bookmarksite SET
				title = '" . $db->escape_string($vbulletin->GPC['title']) . "',
				iconpath = '" . $db->escape_string($vbulletin->GPC['iconpath']) . "',
				active = " . $vbulletin->GPC['active'] . ",
				displayorder = " . $vbulletin->GPC['displayorder'] . ",
				url = '" . $db->escape_string($vbulletin->GPC['url']) . "',
				utf8encode = '" . $db->escape_string($vbulletin->GPC['utf8encode']) . "'
			WHERE bookmarksiteid = " . $vbulletin->GPC['bookmarksiteid']
		);
	}
	else
	{
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "bookmarksite
				(title, iconpath, active, displayorder, url, utf8encode)
			VALUES (
				'" . $db->escape_string($vbulletin->GPC['title']) . "',
				'" . $db->escape_string($vbulletin->GPC['iconpath']) . "',
				" . $vbulletin->GPC['active'] . ",
				" . $vbulletin->GPC['displayorder'] . ",
				'" . $db->escape_string($vbulletin->GPC['url']) . "',
				'" . $db->escape_string($vbulletin->GPC['utf8encode']) . "'
			)
		");
	}

	// rebuild the cache
	build_bookmarksite_datastore();

	define('CP_REDIRECT', 'bookmarksite.php' . $vbulletin->session->vars['sessionurl_q']);
	print_stop_message('bookmark_site_saved_successfully');

	$_REQUEST['do'] = 'modify';
}

// ########################################################################
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array('bookmarksiteid' => TYPE_UINT));

	print_form_header('bookmarksite', 'update');
	print_column_style_code(array('width:35%', 'width:65%'));

	if ($_REQUEST['do'] == 'edit' AND $bookmarksite = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "bookmarksite WHERE bookmarksiteid = " . $vbulletin->GPC['bookmarksiteid']))
	{
		// edit existing
		print_table_header($vbphrase['edit_social_bookmarking_site'] . " <span class=\"normal\">$bookmarksite[title]</span>");
		construct_hidden_code('bookmarksiteid', $bookmarksite['bookmarksiteid']);
	}
	else
	{
		// add new
		$bookmarksite = $vbulletin->db->query_first("SELECT MAX(displayorder) AS displayorder FROM " . TABLE_PREFIX . "bookmarksite");
		$bookmarksite['displayorder'] += 10;
		$bookmarksite['url'] = 'http://';
		$bookmarksite['active'] = true;
		$bookmarksite['utf8encode'] = false;

		print_table_header($vbphrase['add_new_social_bookmarking_site']);
	}

	print_input_row($vbphrase['title'], 'title', $bookmarksite['title'], false, 50);
	print_input_row($vbphrase['icon'] . '<dfn>' . $vbphrase['icon_bookmarksite_help'] . '</dfn>', 'iconpath', $bookmarksite['iconpath'], true, 50);
	print_input_row($vbphrase['link'] . '<dfn>' . $vbphrase['link_replacement_variables_help'] . '</dfn>', 'url', $bookmarksite['url'], true, 50);
	print_input_row($vbphrase['display_order'], 'displayorder', $bookmarksite['displayorder'], true, 2);
	print_yes_no_row($vbphrase['active'], 'active', $bookmarksite['active']);
	print_yes_no_row($vbphrase['utf8encode_title'] . '<dfn>' . $vbphrase['utf8encode_title_help'] . '</dfn>', 'utf8encode', $bookmarksite['utf8encode']);
	print_submit_row();
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

	$bookmarksites_result = $db->query_read("SELECT bookmarksiteid, displayorder, active FROM " . TABLE_PREFIX . "bookmarksite");
	while ($bookmarksite = $db->fetch_array($bookmarksites_result))
	{
		if (intval($bookmarksite['active']) != $vbulletin->GPC['active']["$bookmarksite[bookmarksiteid]"] OR $bookmarksite['displayorder'] != $vbulletin->GPC['displayorder']["$bookmarksite[bookmarksiteid]"])
		{
			$update_ids .= ",$bookmarksite[bookmarksiteid]";
			$update_active .= " WHEN $bookmarksite[bookmarksiteid] THEN " . intval($vbulletin->GPC['active']["$bookmarksite[bookmarksiteid]"]);
			$update_displayorder .= " WHEN $bookmarksite[bookmarksiteid] THEN " . $vbulletin->GPC['displayorder']["$bookmarksite[bookmarksiteid]"];
		}
	}
	$db->free_result($bookmarksites_result);

	if (strlen($update_ids) > 1)
	{
		$db->query_write("UPDATE " . TABLE_PREFIX . "bookmarksite SET
			active = CASE bookmarksiteid
			$update_active ELSE active END,
			displayorder = CASE bookmarksiteid
			$update_displayorder ELSE displayorder END
			WHERE bookmarksiteid IN($update_ids)
		");

		// tell the datastore to update
		$changes = true;
	}

	// handle swapping
	if (!empty($vbulletin->GPC['displayorderswap']))
	{
		list($orig_bookmarksiteid, $swap_direction) = explode(',', $vbulletin->GPC['displayorderswap'][0]);

		if (isset($vbulletin->GPC['displayorder']["$orig_bookmarksiteid"]))
		{
			$bookmarksite_orig = array(
				'bookmarksiteid'     => $orig_bookmarksiteid,
				'displayorder' => $vbulletin->GPC['displayorder']["$orig_bookmarksiteid"]
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

			if ($comp AND $sort AND $bookmarksite_swap = $db->query_first("SELECT bookmarksiteid, displayorder FROM " . TABLE_PREFIX . "bookmarksite WHERE displayorder $comp $bookmarksite_orig[displayorder] ORDER BY displayorder $sort, title ASC LIMIT 1"))
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "bookmarksite
					SET displayorder = CASE bookmarksiteid
						WHEN $bookmarksite_orig[bookmarksiteid] THEN $bookmarksite_swap[displayorder]
						WHEN $bookmarksite_swap[bookmarksiteid] THEN $bookmarksite_orig[displayorder]
						ELSE displayorder END
					WHERE bookmarksiteid IN($bookmarksite_orig[bookmarksiteid], $bookmarksite_swap[bookmarksiteid])
				");

				// tell the datastore to update
				$changes = true;
			}
		}
	}

	//update the datastore bookmarksite cache
	if ($changes)
	{
		build_bookmarksite_datastore();
	}

	$_REQUEST['do'] = 'modify';
}

// ########################################################################
// we want to display the bookmark list - this is the default action
if ($_REQUEST['do'] == 'modify')
{
	if (!$vbulletin->options['socialbookmarks'])
	{
		print_table_start();
		print_description_row(fetch_error('social_bookmarks_disabled'));
		print_table_footer(2, '', '', false);
	}

	// display the form and table header
	print_form_header('bookmarksite', 'quickupdate');
	print_table_header($vbphrase['social_bookmarking_manager'], 3);

	$bookmarksites_result = $db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "bookmarksite AS bookmarksite
		ORDER BY displayorder, title
	");
	$bookmarksite_count = $db->num_rows($bookmarksites_result);

	if ($bookmarksite_count)
	{
		print_description_row('<label><input type="checkbox" id="allbox" checked="checked" />' . $vbphrase['toggle_active_status_for_all'] . '</label><input type="image" src="../' . $vbulletin->options['cleargifurl'] . '" name="normalsubmit" />', false, 3, 'thead" style="font-weight:normal; padding:0px 4px 0px 4px');
		print_column_style_code(array('width:20%; white-space:nowrap', 'width:60%', "width:20%; white-space:nowrap; text-align:" . vB_Template_Runtime::fetchStyleVar('right')));
		while ($bookmarksite = $db->fetch_array($bookmarksites_result))
		{
			print_cells_row(array(
				'<label class="smallfont"><input type="checkbox" name="active[' . $bookmarksite['bookmarksiteid'] . ']" value="1"' . ($bookmarksite['active'] ? ' checked="checked"' : '') . ' />' . $vbphrase['active'] . '</label> &nbsp; ' .
				'<input type="image" src="../cpstyles/' . $vbulletin->options['cpstylefolder'] . '/move_down.gif" name="displayorderswap[' . $bookmarksite['bookmarksiteid'] . ',higher]" />' .
				'<input type="text" name="displayorder[' . $bookmarksite['bookmarksiteid'] . ']" value="' . $bookmarksite['displayorder'] . '" class="bginput" size="4" title="' . $vbphrase['display_order'] . '" style="text-align:' . vB_Template_Runtime::fetchStyleVar('right') . '" />' .
				'<input type="image" src="../cpstyles/' . $vbulletin->options['cpstylefolder'] . '/move_up.gif" name="displayorderswap[' . $bookmarksite['bookmarksiteid'] . ',lower]" />',

				'<a href="bookmarksite.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit&amp;bookmarksiteid=' . $bookmarksite['bookmarksiteid'] . '" title="' . $vbphrase['edit'] . '">' . $bookmarksite['title'] . '</a>',

				construct_link_code($vbphrase['edit'], 'bookmarksite.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit&amp;bookmarksiteid=' . $bookmarksite['bookmarksiteid']) .
				construct_link_code($vbphrase['delete'], 'bookmarksite.php?' . $vbulletin->session->vars['sessionurl'] . 'do=delete&amp;bookmarksiteid=' . $bookmarksite['bookmarksiteid'])
			), false, '', -1);
		}
		$db->free_result($bookmarksites_result);
	}

	echo '<tr>
		<td class="tfoot">' .
		($bookmarksite_count ? '<input type="submit" class="button" accesskey="s" value="' . $vbphrase['save'] . '" /> <input type="reset" class="button" accesskey="r" value="' . $vbphrase['reset'] . '" />' : '&nbsp;') .
		'</td>
		<td class="tfoot" align="' . vB_Template_Runtime::fetchStyleVar('right') . '" colspan="2"><input type="button" class="button" value="' . $vbphrase['add_new_social_bookmarking_site'] . '" onclick="window.location=\'bookmarksite.php?' . $vbulletin->session->vars['sessionurl'] . 'do=add\';" /></td>
	</tr>';
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

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 35520 $
|| ####################################################################
\*======================================================================*/
?>