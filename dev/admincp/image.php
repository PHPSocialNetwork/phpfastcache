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
$phrasegroups = array('attachment_image', 'cppermission');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminimages'))
{
	print_cp_no_permission();
}

// make sure we are dealing with avatars,smilies or icons
$vbulletin->input->clean_array_gpc('r', array(
	'table' => TYPE_STR,
	'id'    => TYPE_UINT,
));

/*
NOTE:
	for use in imagecategory table:
	imagetype = 1 => avatar
	imagetype = 2 => icon
	imagetype = 3 => smilie
*/

switch($vbulletin->GPC['table'])
{
	case 'avatar':
		$itemtype = 'avatar';
		$itemtypeplural = 'avatars';
		$catid = 1;
		break;
	case 'icon':
		$itemtype = 'post_icon';
		$itemtypeplural = 'post_icons';
		$catid = 2;
		break;
	case 'smilie':
		$itemtype = 'smilie';
		$itemtypeplural = 'smilies';
		$catid = 3;
		break;
	default:
		print_cp_header($vbphrase['error']);
		print_stop_message('invalid_table_specified');
		break;
}

// ############################# LOG ACTION ###############################
log_admin_action($vbphrase["$itemtypeplural"] . iif($vbulletin->GPC['id'] != 0, " id = " . $vbulletin->GPC['id']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase["$itemtypeplural"]);

$tables = array('avatar' => $vbphrase['avatar'], 'icon' => $vbphrase['post_icon'], 'smilie' => $vbphrase['smilie']);
$itemid = $vbulletin->GPC['table'] . 'id';
$itempath = $vbulletin->GPC['table'] . 'path';

// ************************************************************
// start functions

$img_per_row = 5;

// ###################### Start showimage #######################
function construct_img_html($imagepath)
{
	// returns an image based on imagepath
	return '<img src="' . resolve_cp_image_url($imagepath) . "\" alt=\"$imagepath\" align=\"middle\" />";
}

// ###################### Start makeitemrow #######################
function print_image_item_row(&$cell)
{
// returns a row of five cells for use in $do==viewimages
	global $img_per_row;
	$cells = $img_per_row - sizeof($cell);
	for ($i=0; $i < $cells; $i++)
	{
		$cell[] = '';
	}
	print_cells_row($cell, 0, 0, 1, 'bottom');
	$cell = array();
}

// ###################### Start displayitem #######################
function print_image_item($item, $itemid, $itempath, $page, $perpage, $catid, $massmove = false, $imagecategoryid = 0)
{
	// displays an item together with links to edit/remove
	global $vbulletin,  $vbphrase;
	static $categories;

	if (!$massmove)
	{
		$out = "<b>$item[title]</b><br /><br />"
			. construct_img_html($item["$itempath"])
			. '<br />'
			. iif($vbulletin->GPC['table'] == 'smilie', " <span class=\"smallfont\">$item[smilietext]</span>")
			. '<br />'
		;

		$out .= construct_link_code(
			$vbphrase['edit'], "image.php?"
			. $vbulletin->session->vars['sessionurl']
			. "do=edit"
			. "&table=" . $vbulletin->GPC['table']
			. "&id="    . $item[$itemid]
			. "&pp="    . $perpage
			. "&page="  . $page
			. ($imagecategoryid ? "&imagecategoryid=" . $imagecategoryid : '')
		);

		$out .= construct_link_code(
			$vbphrase['delete'], "image.php?" .
			$vbulletin->session->vars['sessionurl']
			. "do=remove"
			. "&table="           . $vbulletin->GPC['table']
			. "&id="              . $item[$itemid]
			. "&pp="              . $perpage
			. "&page="            . $page
			. ($imagecategoryid ? "&imagecategoryid=" . $imagecategoryid : '')
		);

		$out .= " <input type=\"text\" class=\"bginput\" name=\"order[" . $item["$itemid"]
				. "]\" tabindex=\"1\" value=\"$item[displayorder]\" size=\"2\" title=\""
				. $vbphrase['display_order'] . "\" class=\"smallfont\" /> ";
	}
	else
	{

		if (!$categories)
		{
			$categories = '<option value="0"></option>';
			$categories .= construct_select_options(fetch_image_categories_array($catid));
		}
		$title = iif($item['title'], "<a href=\"image.php?" .
			$vbulletin->session->vars['sessionurl']
			. "do=edit"
			. "&amp;table=" 	. $vbulletin->GPC['table']
			. "&amp;id="		. $item[$itemid]
			. "&amp;pp=" 	. $perpage
			. "&amp;page="		. $page
			. "&amp;massmove="	. $massmove
			. "\">$item[title]</a>",
			construct_link_code($vbphrase['edit'],
				"image.php?"
				. $vbulletin->session->vars['sessionurl']
				. "do=edit"
				. "&amp;table="		. $vbulletin->GPC['table']
				. "&amp;id="		. $item[$itemid]
				. "&amp;pp="	. $perpage
				. "&amp;page="		. $page
				. "&amp;massmove="	. $massmove
			)
		);

		$out = "<b>"
			. $title
			. "</b><br /><br />"
			. construct_img_html($item["$itempath"])
			. '<br />'
			. iif($vbulletin->GPC['table'] == 'smilie', " <span class=\"smallfont\">$item[smilietext]</span>")
			. '<br />';

		$out .= '<select name="category[' . $item["$itemid"] . ']" class="bginput">' . $categories . '</select>';
	}

	return $out;
}

// ###################### Start getimagecategories #######################
function fetch_image_categories_array($catid)
{
// returns an array of imagecategoryid => title for use in <select> lists
	global $db, $cats;
	if (!is_array($cats))
	{
		$categories = $db->query_read("
			SELECT imagecategoryid,title
			FROM " . TABLE_PREFIX . "imagecategory
			WHERE imagetype = $catid ORDER BY displayorder
		");
		$cats = array();
		while ($category = $db->fetch_array($categories))
		{
			$cats[$category['imagecategoryid']] = $category['title'];
		}
		$db->free_result($categories);
	}
	return $cats;
}

// end functions
// ************************************************************

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start Update Permissions #######################
if ($_POST['do'] == 'updatepermissions')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'iperm'           => TYPE_ARRAY,
		'imagecategoryid' => TYPE_INT
	));

	$categoryinfo = verify_id('imagecategory', $vbulletin->GPC['imagecategoryid'], 0, 1);

	if ($categoryinfo['imagetype'] == 3)
	{
		print_stop_message('smilie_categories_dont_support_permissions');
	}

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "imagecategorypermission WHERE imagecategoryid=" . $vbulletin->GPC['imagecategoryid']);

	foreach($vbulletin->GPC['iperm'] AS $usergroupid => $canuse)
	{
		$usergroupid = intval($usergroupid);
		if ($canuse == 0)
		{
			/*insert query*/
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "imagecategorypermission
				(
					imagecategoryid,
					usergroupid
				)
				VALUES
				(
					" . $vbulletin->GPC['imagecategoryid'] . ",
					$usergroupid
				)
			");
		}
	}

	build_image_permissions($categoryinfo['imagetype']);

	define('CP_REDIRECT', 'image.php?do=modify&amp;table=' . $vbulletin->GPC['table']); // table is cleaned line 48
	print_stop_message('saved_permissions_successfully');
}

// ###################### Start Edit Permissions #######################
if ($_REQUEST['do'] == 'editpermissions')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagecategoryid'	=> TYPE_INT
	));

	$categoryinfo = verify_id('imagecategory', $vbulletin->GPC['imagecategoryid'], 0, 1);
	if ($categoryinfo['imagetype'] == 3)
	{
		print_stop_message('smilie_categories_dont_support_permissions');
	}

	$usergroups = $db->query_read("
		SELECT usergroup.*, imagecategoryid AS nopermission FROM " . TABLE_PREFIX . "usergroup AS usergroup
		LEFT JOIN " . TABLE_PREFIX . "imagecategorypermission AS imgperm ON
		(imgperm.usergroupid = usergroup.usergroupid AND imgperm.imagecategoryid = " . $vbulletin->GPC['imagecategoryid'] . ")
		ORDER BY title
	");

	print_form_header('image', 'updatepermissions');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	construct_hidden_code('imagecategoryid', $vbulletin->GPC['imagecategoryid']);
	print_table_header(construct_phrase($vbphrase["permissions_for_{$itemtype}_category_x"], $categoryinfo['title']));
	print_label_row('<span class="smallfont"><b>' . $vbphrase['usergroup'] . '</b></span>', '<span class="smallfont"><b>' . $vbphrase["can_use_this_{$itemtype}_category"] . '</b></span>');
	while ($usergroup = $db->fetch_array($usergroups))
	{
		$usergroupid = $usergroup['usergroupid'];
		$canuse = iif($usergroup['nopermission'], 0, 1);
		print_yes_no_row($usergroup['title'], "iperm[$usergroupid]", $canuse);
	}
	print_submit_row($vbphrase['save']);

}

// ###################### Start Kill Category #######################
if ($_POST['do'] == 'killcategory')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'imagecategoryid' => TYPE_INT,
		'destinationid' 	=> TYPE_INT,
		'deleteitems' 		=> TYPE_NOCLEAN
	));

	if ($vbulletin->GPC['deleteitems'] == 1)
	{
		$db->query_write("DELETE FROM " . TABLE_PREFIX . $vbulletin->GPC['table'] . " WHERE imagecategoryid = " . $vbulletin->GPC['imagecategoryid']);
		$extra = $vbphrase["{$itemtypeplural}_deleted"];
	}
	else
	{
		$dest = $db->query_first("
			SELECT title
			FROM " . TABLE_PREFIX . "imagecategory
			WHERE imagecategoryid = " . $vbulletin->GPC['imagecategoryid']
		);
		$db->query_write("
			UPDATE " . TABLE_PREFIX . $vbulletin->GPC['table'] . "
			SET imagecategoryid = " . $vbulletin->GPC['destinationid'] . "
			WHERE imagecategoryid = " . $vbulletin->GPC['imagecategoryid']
		);
		$extra = $vbphrase["{$itemtypeplural}_deleted"];
	}

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "imagecategory WHERE imagecategoryid = " . $vbulletin->GPC['imagecategoryid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "imagecategorypermission WHERE imagecategoryid = " . $vbulletin->GPC['imagecategoryid']);

	build_image_cache($vbulletin->GPC['table']);
	build_image_permissions($vbulletin->GPC['table']);

	### KIER LOOK HERE ###
	define('CP_REDIRECT', "image.php?do=modify&table=" . $vbulletin->GPC['table']);
	print_stop_message('deleted_category_successfully');
	### END LOOK HERE ###
}

// ###################### Start Remove Category #######################
if ($_REQUEST['do'] == 'removecategory')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagecategoryid' => TYPE_INT
	));

	$categories = $db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "imagecategory
		WHERE imagetype = $catid ORDER BY displayorder
	");

	if ($db->num_rows($categories) < 2)
	{
		print_stop_message("cant_remove_last_{$itemtype}_category");
	}
	else
	{
		$category = array();
		$destcats = array();
		while ($tmp = $db->fetch_array($categories))
		{
			if ($tmp['imagecategoryid'] == $vbulletin->GPC['imagecategoryid'])
			{
				$category = $tmp;
			}
			else
			{
				$destcats[$tmp['imagecategoryid']] = $tmp['title'];
			}
		}
		unset($tmp);
		$db->free_result($categories);

		echo "<p>&nbsp;</p><p>&nbsp;</p>\n";

		print_form_header('image', 'killcategory');
		construct_hidden_code('imagecategoryid', $category['imagecategoryid']);
		construct_hidden_code('table', $vbulletin->GPC['table']);
		print_table_header(construct_phrase($vbphrase["confirm_deletion_of_{$itemtype}_category_x"], $category['title']));
		print_description_row('<blockquote>' . construct_phrase($vbphrase["are_you_sure_you_want_to_delete_the_{$itemtype}_category_called_x"], $category['title'], construct_select_options($destcats)) . '</blockquote>');
		print_submit_row($vbphrase['delete'], '', 2, $vbphrase['go_back']);
	}

}

// ###################### Start Update Category #######################
if ($_POST['do'] == 'insertcategory')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'title'        => TYPE_NOHTML,
		'displayorder' => TYPE_INT
	));

	if (empty($vbulletin->GPC['title']))
	{
		print_stop_message('please_complete_required_fields');
	}

	/*insert query*/
	$db->query_write("INSERT INTO " . TABLE_PREFIX . "imagecategory (
		title,imagetype,displayorder
	) VALUES (
		'" . $db->escape_string($vbulletin->GPC['title']) . "', $catid, " . $vbulletin->GPC['displayorder'] . "
	)");

	build_image_cache($vbulletin->GPC['table']);
	#build_image_permissions($vbulletin->GPC['table']);

	define('CP_REDIRECT', "image.php?do=modify&table=" . $vbulletin->GPC['table']);
	print_stop_message('saved_category_x_successfully', $vbulletin->GPC['title']);
}

// ###################### Start Add Category #######################
if ($_REQUEST['do'] == 'addcategory')
{
	print_form_header('image', 'insertcategory');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	print_table_header($vbphrase["add_new_{$itemtype}_category"]);
	print_input_row($vbphrase['title'], 'title');
	print_input_row($vbphrase['display_order'], 'displayorder', 1);
	print_submit_row($vbphrase['save']);
}

// ###################### Start Update Category #######################
if ($_POST['do'] == 'updatecategory')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagecategoryid'	=> TYPE_INT,
		'title'				=> TYPE_NOHTML,
		'displayorder'		=> TYPE_INT
	));

	if (empty($vbulletin->GPC['title']))
	{
		print_stop_message('please_complete_required_fields');
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "imagecategory SET
		title = '" . $db->escape_string($vbulletin->GPC['title']) . "',
		displayorder = " . $vbulletin->GPC['displayorder'] . "
		WHERE imagecategoryid = " . $vbulletin->GPC['imagecategoryid']
	);

	build_image_cache($vbulletin->GPC['table']);

	define('CP_REDIRECT', "image.php?do=modify&table=" . $vbulletin->GPC['table']);
	print_stop_message('saved_category_x_successfully', $vbulletin->GPC['title']);
}

// ###################### Start Edit Category #######################
if ($_REQUEST['do'] == 'editcategory')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagecategoryid'	=> TYPE_INT
	));

	$category = $db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "imagecategory
		WHERE imagecategoryid = " . $vbulletin->GPC['imagecategoryid']
	);

	print_form_header('image', 'updatecategory');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	construct_hidden_code('imagecategoryid', $category['imagecategoryid']);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase["{$itemtype}_category"], $category['title'], $category['imagecategoryid']));
	print_input_row($vbphrase['title'], 'title', $category['title'], 0);
	print_input_row($vbphrase['display_order'], 'displayorder', $category['displayorder']);
	print_submit_row();

}

// ###################### Start Update Smiley Category Display Order #######################
if ($_REQUEST['do'] == 'docategorydisplayorder')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'order'	=> TYPE_NOCLEAN
	));

	if (is_array($vbulletin->GPC['order']))
	{
		$categories = $db->query_read("
			SELECT imagecategoryid,displayorder
			FROM " . TABLE_PREFIX . "imagecategory
			WHERE imagetype = $catid
		");
		while ($category = $db->fetch_array($categories))
		{
			$displayorder = intval($vbulletin->GPC['order']["$category[imagecategoryid]"]);
			if ($category['displayorder'] != $displayorder)
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "imagecategory
					SET displayorder = $displayorder
					WHERE imagecategoryid = $category[imagecategoryid] AND
					imagetype = $catid
				");
			}
		}
	}

	define('CP_REDIRECT', "image.php?do=modify&amp;table=" . $vbulletin->GPC['table']);
	print_stop_message('saved_display_order_successfully');
}

// ###################### Start Do Upload #######################
if ($_POST['do'] == 'doupload')
{
	$vbulletin->input->clean_array_gpc('f', array(
		'upload'  => TYPE_FILE,
	));

	$vbulletin->input->clean_array_gpc('p', array(
		'imagespath' => TYPE_STR,
		'title'      => TYPE_STR,
		'smilietext' => TYPE_STR
	));


	if (empty($vbulletin->GPC['title']) OR empty($vbulletin->GPC['imagespath']) OR ($vbulletin->GPC['table'] == 'smilie' AND empty($vbulletin->GPC['smilietext'])))
	{
		print_stop_message('please_complete_required_fields');
	}
	if (file_exists('./' . $vbulletin->GPC['imagespath'] . '/' . $vbulletin->GPC['upload']['name']))
	{
		print_stop_message('file_x_already_exists', htmlspecialchars_uni($vbulletin->GPC['upload']['name']));
	}

	require_once(DIR . '/includes/class_upload.php');
	require_once(DIR . '/includes/class_image.php');

	$upload = new vB_Upload_Image($vbulletin);
	$upload->image =& vB_Image::fetch_library($vbulletin);
	$upload->path = $vbulletin->GPC['imagespath'];

	if (!($imagepath = $upload->process_upload($vbulletin->GPC['upload'])))
	{
		print_stop_message('there_were_errors_encountered_with_your_upload_x', $upload->fetch_error());
	}

	define('IMAGE_UPLOADED', true);
	$_POST['do'] = 'insert';
}

// ###################### Start Upload #######################
if ($_REQUEST['do'] == 'upload')
{
	print_form_header('image', 'doupload', 1);
	construct_hidden_code('table', $vbulletin->GPC['table']);
	print_table_header($vbphrase["upload_{$itemtype}"]);
	print_upload_row($vbphrase['filename'], 'upload');
	print_input_row($vbphrase['title'], 'title');
	switch($vbulletin->GPC['table'])
	{
		case 'avatar':
			print_input_row($vbphrase['minimum_posts'], 'minimumposts', 0);
			break;
		case 'smilie':
			print_input_row($vbphrase['text_to_replace'], 'smilietext', '', true, 35, 20);
			break;
	}
	print_input_row($vbphrase["{$itemtype}_file_path_dfn"], 'imagespath', 'images/' . $vbulletin->GPC['table'] . 's');
	print_label_row($vbphrase["{$itemtype}_category"], "<select name=\"imagecategoryid\" tabindex=\"1\" class=\"bginput\">" . construct_select_options(fetch_image_categories_array($catid), $item['imagecategoryid']) . '</select>', '', 'top', 'imagecategoryid');
	print_input_row($vbphrase['display_order'], 'displayorder', 1);
	print_submit_row($vbphrase['upload']);
}

// ###################### Start Kill #######################
if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'avatarid'        => TYPE_INT,
		'iconid'          => TYPE_INT,
		'smilieid'        => TYPE_INT,
		'page'            => TYPE_INT,
		'perpage'         => TYPE_INT,
		'imagecategoryid' => TYPE_UINT
	));

	if ($vbulletin->GPC['avatarid'])
	{
		$id = $vbulletin->GPC['avatarid'];
	}
	else if ($vbulletin->GPC['iconid'])
	{
		$id = $vbulletin->GPC['iconid'];
	}
	else if ($vbulletin->GPC['smilieid'])
	{
		$id = $vbulletin->GPC['smilieid'];
	}

	$image = $db->query_first("SELECT imagecategoryid FROM " . TABLE_PREFIX . $vbulletin->GPC['table'] ." WHERE $itemid = $id");
	$imagecategoryid = $image['imagecategoryid'];

	$db->query_write("DELETE FROM " . TABLE_PREFIX . $vbulletin->GPC['table'] ." WHERE $itemid = $id");

	if ($vbulletin->GPC['avatarid'])
	{
		@unlink(DIR . "/images/avatars/thumbs/{$vbulletin->GPC['avatarid']}.gif");
	}

	build_image_cache($vbulletin->GPC['table']);
	build_image_permissions($vbulletin->GPC['table']);

	define('CP_REDIRECT',
		"image.php?do=viewimages" .
		"&table=" . $vbulletin->GPC['table'] .
		"&amp;page=" . $vbulletin->GPC['page'] .
		"&amp;pp=" . $vbulletin->GPC['perpage'] .
		($vbulletin->GPC['imagecategoryid'] ? "&amp;imagecategoryid=" . $vbulletin->GPC['imagecategoryid'] : '')
	);
	print_stop_message("deleted_{$itemtype}_successfully");
}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'id'              => TYPE_INT,
		'page'            => TYPE_INT,
		'perpage'         => TYPE_INT,
		'imagecategoryid' => TYPE_UINT
	));

	$hidden = array(
		'table'           => $vbulletin->GPC['table'],
		'page'            => $vbulletin->GPC['page'],
		'perpage'         => $vbulletin->GPC['perpage'],
		'imagecategoryid' => $vbulletin->GPC['imagecategoryid']
	);

	print_delete_confirmation(
		$vbulletin->GPC['table'],
		$vbulletin->GPC['id'],
		'image',
		'kill',
		$itemtype,
		$hidden
	);
}

// ###################### Start Do Insert Multiple #######################
if ($_POST['do'] == 'doinsertmultiple')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'page'             => TYPE_INT,
		'perpage'          => TYPE_INT,
		'imagespath'       => TYPE_STR,
		'doinsertmultiple' => TYPE_STR,
		'ititle'           => TYPE_ARRAY_STR,
		'icat'             => TYPE_ARRAY_STR,
		'ismilietext'      => TYPE_ARRAY_STR,
		'iminimumposts'    => TYPE_ARRAY_STR,
		'doimage'          => TYPE_NOCLEAN
	));

	if (empty($vbulletin->GPC['doinsertmultiple']))
	{
		// just go back to the interface if a page button was pressed, rather than the actual submit button
		$_REQUEST['do'] = 'insertmultiple';
	}
	else if (!is_array($vbulletin->GPC['doimage']))
	{
		// return error if no images checked for insertion
		print_stop_message("no_{$itemtypeplural}_selected");
	}
	else
	{
		echo "<ul>\n";

		$duplicates = array();
		if ($vbulletin->GPC['table'] == 'smilie')
		{
			// Make sure we don't generate duplicates
			foreach($vbulletin->GPC['doimage'] AS $path => $yes)
			{
				$smiliestext.= ",'" . $db->escape_string($vbulletin->GPC['ismilietext']["$path"]) . "'";
			}
			$duplicatesq = $db->query_read("SELECT smilietext FROM " . TABLE_PREFIX . "smilie WHERE smilietext IN (''$smiliestext)");
			while ($smilie = $db->fetch_array($duplicatesq))
			{
				$duplicates["$smilie[smilietext]"] = 1;
			}
			$db->free_result($duplicatesq);
			unset($smiliestext);
		}

		foreach ($vbulletin->GPC['doimage'] AS $path => $yes)
		{
			if ($yes)
			{
				$title 			= $vbulletin->GPC['ititle']["$path"];
				$minimumposts 	= $vbulletin->GPC['iminimumposts']["$path"];
				$smilietext 	= $vbulletin->GPC['ismilietext']["$path"];
				$category 		= $vbulletin->GPC['icat']["$path"];
				$path 			= $vbulletin->GPC['imagespath'] . '/' . urldecode($path);

				echo "\t<li>" . $vbphrase["processing_{$itemtype}"] . " ";

				if (!isset($duplicates["$smilietext"]))
				{
					/*insert query*/
					$db->query_write("
						INSERT INTO " . TABLE_PREFIX . $vbulletin->GPC['table'] ." (
							$itemid,
							title, "
							. iif($vbulletin->GPC['table'] == 'avatar','minimumposts,','')
							. iif($vbulletin->GPC['table'] == 'smilie','smilietext,','')
							."$itempath,
							imagecategoryid,
							displayorder
						)
						VALUES
						(
							NULL,
							'" . $db->escape_string($title) . "', "
							. iif($vbulletin->GPC['table'] == 'avatar', intval($minimumposts).',','')
							. iif($vbulletin->GPC['table'] == 'smilie',"'" . $db->escape_string($smilietext) . "',",'')
							. "'" . $db->escape_string($path) . "',"
							. intval($category) . ",
							1
						)
					");
					echo $vbphrase['okay'] . ".</li>\n";
				}
				else
				{
					echo construct_phrase($vbphrase['smilietext_x_taken'], $smilietext) . ".</li>\n";
				}
			}
		}
		echo "</ul>\n";

	}
	build_image_cache($vbulletin->GPC['table']);
	build_image_permissions($vbulletin->GPC['table']);

	$doneinsert = 1;
	$_REQUEST['do'] = 'insertmultiple';
}

// ###################### Start Insert Multiple #######################
if ($_REQUEST['do'] == 'insertmultiple')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagespath'      => TYPE_STR,
		'perpage'         => TYPE_INT,
		'page'            => TYPE_STR, // this must be str for the trim()!
		'imagecategoryid' => TYPE_INT
	));

	$vbulletin->GPC['imagespath'] = preg_replace('/\/$/s', '', $vbulletin->GPC['imagespath']);

	// try to open the specified file path to images
	if (!$handle = @opendir("./" . $vbulletin->GPC['imagespath']))
	{
		print_stop_message('invalid_file_path_specified');
	}
	else
	{
		// make a $pathcache array containing the filepaths of the existing images in the db
		$pathcache = array();
		$items = $db->query_read("SELECT $itempath AS path FROM " . TABLE_PREFIX . $vbulletin->GPC['table']);
		while ($item = $db->fetch_array($items))
		{
			$pathcache["$item[path]"] = 1;
		}
		unset($item);
		$db->free_result($items);

		// populate the $filearray with paths of images that are not contained in the $pathcache
		$path = $vbulletin->GPC['imagespath'];
		$filearray = array();

		$imagelist = array('.jpg', '.gif', '.jpeg', '.jpe', '.png', '.bmp');

		while($file = readdir($handle))
		{
			if ($file == '.' OR $file == '..')
			{
				continue;
			}
			$ext = strtolower(strrchr($file, '.'));
			if (in_array($ext, $imagelist)
				AND !$pathcache["$path/$file"]
				AND !$pathcache["{$vbulletin->options['bburl']}/$path/$file"])
			{
				$filearray[] = $file;
			}
		}
		// free the $pathcache
		unset($pathcache);
		// close the directory handler
		closedir($handle);

		// sort naturally, but redo the keys
		natcasesort($filearray);
		$filearray = array_values($filearray);

		// now display the returned items

		// get some variables defining what parts of the $filearray to show
		$page = intval($vbulletin->GPC['page']);
		if ($page < 1)
		{
			$page = 1;
		}

		if ($vbulletin->GPC['perpage'] < 1)
		{
			$vbulletin->GPC['perpage'] = 10;
		}

		$startat = ($page - 1) * $vbulletin->GPC['perpage'];
		$endat = $startat + $vbulletin->GPC['perpage'];
		$totalitems = sizeof($filearray);
		$totalpages = ceil($totalitems / $vbulletin->GPC['perpage']);

		// if $endat is greater than $totalitems truncate it so we don't get empty rows in the table
		if ($endat > $totalitems)
		{
			$endat = $totalitems;
		}

		// check to see that the file array actually has some contents
		if ($totalitems == 0)
		{
			// check to see if we are coming from an insert operation...
			if ($doneinsert == 1)
			{
				if ($itemtype == 'avatar')
				{
					print_stop_message('need_to_rebuild_avatars');
				}
				else
				{
					define('CP_REDIRECT', "image.php?table=" . $vbulletin->GPC['table']);
					print_stop_message("all_{$itemtypeplural}_added");
				}
			}
			else
			{
				print_stop_message("no_new_{$itemtypeplural}");
			}
		}
		else
		{
			print_form_header('image', 'doinsertmultiple');
			construct_hidden_code('table', $vbulletin->GPC['table']);
			construct_hidden_code('imagespath', $vbulletin->GPC['imagespath']);
			construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
			construct_hidden_code('imagecategoryid', $vbulletin->GPC['imagecategoryid']);

			// make the headings for the table
			$header = array();
			$header[] = $vbphrase['image'];
			$header[] = $vbphrase['title'];
			switch ($vbulletin->GPC['table'])
			{
				case 'avatar':
					$header[] = $vbphrase['minimum_posts'];
					break;
				case 'smilie':
					$header[] = $vbphrase['text_to_replace'];
					break;
			}
			$header[] = '<input type="checkbox" name="allbox" title="' . $vbphrase['check_all'] . '" onclick="js_check_all(this.form);" /><input type="hidden" name="page" value="' . $page . '" />';

			// get $colspan based on the number of headings and use it for the print_table_header() call
			print_table_header(construct_phrase($vbphrase["adding_multiple_{$itemtypeplural}_reading_from_x"], $vbulletin->options['bburl'] . '/' . $path), sizeof($header));
			// display the column headings
			print_cells_row($header, 1, 0, 1);

			// now run through the appropriate bits of $filearray and display
			for ($i = $startat; $i < $endat; $i++)
			{
				// make a nice title from the filename
				$titlefield = substr($filearray[$i], 0, strrpos($filearray[$i], '.'));

				$cell = array();
				$cell[] = construct_img_html("$path/". $filearray[$i]) . "<br /><span class=\"smallfont\">" . $filearray[$i] . '</span>';
				$cell[] = "<input type=\"text\" class=\"bginput\" name=\"ititle[" . urlencode($filearray["$i"]) . "]\" tabindex=\"1\" value=\"" . ucwords(preg_replace('/(_|-)/siU', ' ', $titlefield)) . "\" size=\"25\" />\n\t<select name=\"icat[" . urlencode($filearray["$i"]) . "]\" tabindex=\"1\" class=\"bginput\">\n" . construct_select_options(fetch_image_categories_array($catid), $vbulletin->GPC['imagecategoryid']) . "\t</select>\n\t";

				// add extra cells if needed
				switch ($vbulletin->GPC['table'])
				{
					case 'avatar':
						$cell[] = "<input type=\"text\" class=\"bginput\" name=\"iminimumposts[" . urlencode($filearray["$i"]) . "]\" tabindex=\"1\" value=\"0\" size=\"5\" />";
						break;
					case 'smilie':
						$cell[] = "<input type=\"text\" class=\"bginput\" name=\"ismilietext[" . urlencode($filearray["$i"]) . "]\" tabindex=\"1\" value=\":$titlefield:\" size=\"15\" maxlength=\"20\" />";
						break;
				}

				$cell[] = "<input type=\"checkbox\" name=\"doimage[" . urlencode($filearray["$i"]) . "]\" value=\"1\" tabindex=\"1\" />";

				print_cells_row($cell, 0, 0, 1);
			}

			// make a page navigator if $totalitems is greater than $perpage
			if ($vbulletin->GPC['perpage'] < $totalitems)
			{
				$pagenav = "<span class=\"smallfont\">" . $vbphrase['pages'] . " ($totalpages)</span> &nbsp; &nbsp; ";
				for ($i = 1; $i <= $totalpages; $i++)
				{
					$pagenav .= " <input type=\"submit\" class=\"button\" name=\"page\" tabindex=\"1\" value=\" $i \"" . iif($i == $page, ' disabled="disabled"') . ' /> ';
				}
				print_description_row('<center><input type="submit" class="button" name="doinsertmultiple" value="' . $vbphrase["add_{$itemtypeplural}"] . '" style="font-weight:bold" tabindex="1" /> <input type="reset" class="button" tabindex="2" value="' . $vbphrase['reset'] . '" style="font-weight:bold" /></center>', 0, $colspan);
				print_table_footer($colspan, $pagenav);
			}
			else
			{
				print_table_footer($colspan, '<input type="submit" class="button" name="doinsertmultiple" value="' . $vbphrase["add_{$itemtypeplural}"] . '" tabindex="1" /> <input type="reset" class="button" value="' . $vbphrase['reset'] . '" tabindex="1" />');
			}
		} // end if($totalitems)
	} // end if(opendir())
}

// ###################### Start Insert #######################
if ($_POST['do'] == 'insert')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'title'           => TYPE_STR,
		'minimumposts'    => TYPE_INT,
		'smilietext'      => TYPE_STR,
		'imagespath'      => TYPE_STR,
		'imagecategoryid' => TYPE_INT,
		'displayorder'    => TYPE_INT
	));

	if (!$vbulletin->GPC['imagespath'] OR ($vbulletin->GPC['table'] == 'smilie' AND !$vbulletin->GPC['smilietext']) OR !$vbulletin->GPC['title'])
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($vbulletin->GPC['table'] == 'smilie' AND $db->query_first("SELECT smilieid FROM " . TABLE_PREFIX . $vbulletin->GPC['table'] . " WHERE BINARY smilietext = '" . $db->escape_string($vbulletin->GPC['smilietext']) . "'"))
	{
		if (IMAGE_UPLOADED AND file_exists($imagepath))
		{ // if the image is being uploaded zap it
			unlink($imagepath);
		}
		// this smilie already exists
		print_stop_message('smilie_replace_text_x_exists', $vbulletin->GPC['smilietext']);
	}

	if (IMAGE_UPLOADED !== true)
	{
		// we are adding a single item via the form, use user input for path
		$imagepath =& $vbulletin->GPC['imagespath'];
	}

	/*insert query*/
	$db->query_write("
		INSERT INTO " . TABLE_PREFIX . $vbulletin->GPC['table'] . "
		(
			$itemid,
			title, "
			. iif($vbulletin->GPC['table'] == 'avatar', 'minimumposts,', '')
			. iif($vbulletin->GPC['table'] == 'smilie', 'smilietext,', '') . "
			$itempath,
			imagecategoryid,
			displayorder
		)
		VALUES
		(
			NULL,
			'" . $db->escape_string($vbulletin->GPC['title']) . "',	"
			. iif($vbulletin->GPC['table'] == 'avatar', $vbulletin->GPC['minimumposts'] . ',', '')
			. iif($vbulletin->GPC['table'] == 'smilie', "'" . $db->escape_string($vbulletin->GPC['smilietext']) . "',", '')
			. "'" . $db->escape_string($imagepath) . "',"
			. $vbulletin->GPC['imagecategoryid'] . ","
			. $vbulletin->GPC['displayorder'] . "
		)
	");

	build_image_cache($vbulletin->GPC['table']);
	build_image_permissions($vbulletin->GPC['table']);

	if ($itemtype == 'avatar')
	{
		print_stop_message('need_to_rebuild_avatars');
	}
	else
	{
		define('CP_REDIRECT', "image.php?do=viewimages" .
			"&table=" . $vbulletin->GPC['table'] .
			"&amp;imagecategoryid=" . $vbulletin->GPC['imagecategoryid']
		);
		print_stop_message("saved_{$itemtype}_successfully");
	}
}

// ###################### Start Add #######################
if ($_REQUEST['do'] == 'add')
{
	print_form_header('image', 'insert');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	print_table_header($vbphrase["add_a_single_{$itemtype}"]);
	print_input_row($vbphrase['title'], 'title');
	switch($vbulletin->GPC['table'])
	{
		case 'avatar':
			print_input_row($vbphrase['minimum_posts'], 'minimumposts', 0);
			break;
		case 'smilie':
			print_input_row($vbphrase['text_to_replace'], 'smilietext', '', true, 35, 20);
			break;
	}
	print_input_row($vbphrase["{$itemtype}_file_path"], 'imagespath');
	print_select_row($vbphrase["{$itemtype}_category"], 'imagecategoryid', fetch_image_categories_array($catid),  $item['imagecategoryid']);
	print_input_row($vbphrase['display_order'],'displayorder',1);
	print_submit_row($vbphrase["add_{$itemtype}"]);

	print_form_header('image', 'insertmultiple');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	print_table_header($vbphrase["add_multiple_{$itemtypeplural}"]);
	print_select_row($vbphrase["{$itemtype}_category"], 'imagecategoryid', fetch_image_categories_array($catid),  $item['imagecategoryid']);
	print_input_row($vbphrase["{$itemtypeplural}_file_path"], 'imagespath', "images/" . $vbulletin->GPC['table'] . 's');
	print_input_row($vbphrase["{$itemtypeplural}_to_show_per_page"], 'perpage', 10);
	print_submit_row($vbphrase["add_{$itemtypeplural}"]);

}

// ###################### Start Update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'id'                    => TYPE_INT,
		'title'                 => TYPE_STR,
		'minimumposts'          => TYPE_INT,
		'imagespath'            => TYPE_STR,
		'imagecategoryid'       => TYPE_INT,
		'displayorder'          => TYPE_INT,
		'smilietext'            => TYPE_STR,
		'page'                  => TYPE_INT,
		'perpage'               => TYPE_INT,
		'massmove'              => TYPE_INT,
		'returnimagecategoryid' => TYPE_UINT,
	));

	if (!$vbulletin->GPC['imagespath'] OR ($vbulletin->GPC['table'] == 'smilie' AND !$vbulletin->GPC['smilietext']) OR !$vbulletin->GPC['title'])
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($vbulletin->GPC['table'] == 'smilie')
	{
		$oldtext = $db->query_first("SELECT smilietext FROM " . TABLE_PREFIX . $vbulletin->GPC['table']." WHERE $itemid = " . $vbulletin->GPC['id']);

		if ($oldtext['smilietext'] != $vbulletin->GPC['smilietext']
			AND $db->query_first("SELECT smilieid FROM " . TABLE_PREFIX . $vbulletin->GPC['table'] . " WHERE BINARY smilietext = '" . $db->escape_string($vbulletin->GPC['smilietext']) . "'"))
		{
			// this smilie already exists
			print_stop_message('smilie_replace_text_x_exists', $vbulletin->GPC['smilietext']);
		}
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . $vbulletin->GPC['table'] . " SET
		title='" . $db->escape_string($vbulletin->GPC['title']) . "',
		" . iif($vbulletin->GPC['table'] == 'avatar', "minimumposts = " . $vbulletin->GPC['minimumposts'] .",", '').
		iif($vbulletin->GPC['table'] == 'smilie', "smilietext = '" . $db->escape_string($vbulletin->GPC['smilietext']) . "',", '')."
		$itempath = '" . $db->escape_string($vbulletin->GPC['imagespath']) . "',
		imagecategoryid = " . $vbulletin->GPC['imagecategoryid'] . ",
		displayorder = " . $vbulletin->GPC['displayorder'] . "
		WHERE $itemid = " . $vbulletin->GPC['id']
	);

	build_image_cache($vbulletin->GPC['table']);
	build_image_permissions($vbulletin->GPC['table']);

	if ($itemtype == 'avatar')
	{
			define('CP_BACKURL', "image.php?do=viewimages" .
				"&amp;table=" . $vbulletin->GPC['table'] .
				"&amp;pp=" . $vbulletin->GPC['perpage'] .
				"&amp;page=" . $vbulletin->GPC['page'] .
				"&amp;massmove=" . $vbulletin->GPC['massmove'] .
				"&amp;imagecategoryid=" . $vbulletin->GPC['returnimagecategoryid']
			);
			print_stop_message('need_to_rebuild_avatars');
	}
	else
	{
		define('CP_REDIRECT', "image.php?do=viewimages" .
			"&amp;table=" . $vbulletin->GPC['table'] .
			"&amp;pp=" . $vbulletin->GPC['perpage'] .
			"&amp;page=" . $vbulletin->GPC['page'] .
			"&amp;massmove=" . $vbulletin->GPC['massmove'] .
			"&amp;imagecategoryid=" . $vbulletin->GPC['returnimagecategoryid']
		);
		print_stop_message("saved_{$itemtype}_successfully");
	}
}

// ###################### Start Edit #######################
if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'id'              => TYPE_INT,
		'page'            => TYPE_INT,
		'perpage'         => TYPE_INT,
		'massmove'        => TYPE_INT,
		'imagecategoryid' => TYPE_UINT
	));

	$item = $db->query_first("SELECT * FROM " . TABLE_PREFIX . $vbulletin->GPC['table'] ." WHERE $itemid = " . $vbulletin->GPC['id']);

	print_form_header('image', 'update');
	construct_hidden_code('id', $vbulletin->GPC['id']);
	construct_hidden_code('table', $vbulletin->GPC['table']);
	construct_hidden_code('page', $vbulletin->GPC['page']);
	construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
	construct_hidden_code('massmove', $vbulletin->GPC['massmove']);
	construct_hidden_code('returnimagecategoryid', $vbulletin->GPC['imagecategoryid']);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase["$itemtype"], $item['title'], $item["$itemid"]));
	print_label_row($vbphrase['image'], construct_img_html($item["$itempath"]));
	print_input_row($vbphrase['title'], 'title', $item['title']);
	switch($vbulletin->GPC['table'])
	{
		case 'avatar':
			print_input_row($vbphrase['minimum_posts'], 'minimumposts', $item['minimumposts']);
			break;
		case 'smilie':
			print_input_row($vbphrase['text_to_replace'], 'smilietext', $item['smilietext'], true, 35, 20);
			break;
	}
	print_input_row($vbphrase["{$itemtype}_file_path"], 'imagespath', $item["$itempath"]);
	print_select_row($vbphrase["{$itemtype}_category"], 'imagecategoryid', fetch_image_categories_array($catid), $item['imagecategoryid']);
	print_input_row($vbphrase['display_order'], 'displayorder', $item['displayorder']);
	print_submit_row();
}

// ###################### Start Update Display Order #######################
if ($_POST['do'] == 'displayorder')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'order'				=> TYPE_NOCLEAN,
		'category'			=> TYPE_ARRAY_INT,
		'doorder' 			=> TYPE_STR,
		'massmove' 			=> TYPE_INT
	));

	// check that the correct submit button was pressed...
	if ($vbulletin->GPC['doorder'])
	{
		if (!$vbulletin->GPC['massmove'] AND !is_array($vbulletin->GPC['order']))
		{
			print_stop_message('please_complete_required_fields');
		}
		else if ($vbulletin->GPC['massmove'])
		{
			foreach($vbulletin->GPC['category'] AS $id => $imagecategoryid)
			{
				if ($imagecategoryid)
				{
					$id = intval($id);
					$db->query_write("
						UPDATE " . TABLE_PREFIX . $vbulletin->GPC['table'] . "
						SET imagecategoryid = $imagecategoryid
						WHERE $itemid = $id
					");
				}
			}
		}
		else
		{
			$items = $db->query_read("SELECT $itemid,displayorder FROM " . TABLE_PREFIX . $vbulletin->GPC['table']);
			$ordercache = array();
			while ($item = $db->fetch_array($items))
			{
				$ordercache["$item[$itemid]"] = $item['displayorder'];
			}
			unset($item);
			$db->free_result($items);

			foreach($vbulletin->GPC['order'] AS $id => $displayorder)
			{
				$id = intval($id);
				$displayorder = intval($displayorder);
				if ($displayorder != $ordercache["$id"])
				{
					$db->query_write("UPDATE " . TABLE_PREFIX . $vbulletin->GPC['table'] ." SET displayorder = $displayorder WHERE $itemid = $id");
				}
			}
		}
	}
	build_image_cache($vbulletin->GPC['table']);
	build_image_permissions($vbulletin->GPC['table']);

	$_REQUEST['do'] = 'viewimages';
}

// ###################### Start View Images #######################
if ($_REQUEST['do'] == 'viewimages')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagesub'         => TYPE_INT,
		'page'            => TYPE_INT,
		'perpage'         => TYPE_INT,
		'imagecategoryid' => TYPE_INT,
		'massmove'        => TYPE_INT
	));

	if (!empty($vbulletin->GPC['pagesub']))
	{
		$vbulletin->GPC['page'] = $vbulletin->GPC['pagesub'];
	}

	if ($vbulletin->GPC['page'] < 1)
	{
		$vbulletin->GPC['page'] = 1;
	}

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 20;
	}

	$startat = ($vbulletin->GPC['page'] - 1) * $vbulletin->GPC['perpage'];

	// check to see if we should be displaying a single image category
	if ($vbulletin->GPC['imagecategoryid'])
	{
		$categoryinfo = verify_id('imagecategory', $vbulletin->GPC['imagecategoryid'], 0, 1);
		// check to ensure that the returned category is of the appropriate type
		if ($categoryinfo['imagetype'] != $catid)
		{
			unset($categoryinfo);
			$vbulletin->GPC['imagecategoryid'] = 0;
		}
	}

	$count = $db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . $vbulletin->GPC['table']
								. iif($vbulletin->GPC['imagecategoryid'], " WHERE imagecategoryid=" . $vbulletin->GPC['imagecategoryid'], ''));

	$totalitems = $count['total'];
	$totalpages = max(1, ceil($totalitems / $vbulletin->GPC['perpage']));

	if ($startat > $totalitems)
	{
		$vbulletin->GPC['page'] = 1;
		$startat = 0;
	}

	if ($categoryinfo)
	{
	// we already have the category details - don't include it in the query
		$items = $db->query_read("
			SELECT * FROM " . TABLE_PREFIX . $vbulletin->GPC['table'] . "
			WHERE imagecategoryid = $categoryinfo[imagecategoryid]
			ORDER BY " . iif($vbulletin->GPC['table'] == 'avatar', 'minimumposts,', '') . "displayorder
			LIMIT $startat, " . $vbulletin->GPC['perpage']
		);
	}
	else
	{
	// getting all items regardless of category... better get the category titles too
		$items = $db->query_read("
			SELECT " . $vbulletin->GPC['table'] . ".*, imagecategory.title AS category
			FROM " . TABLE_PREFIX . $vbulletin->GPC['table'] . " AS " . $vbulletin->GPC['table'] . "
			LEFT JOIN " . TABLE_PREFIX . "imagecategory AS imagecategory USING(imagecategoryid)
			" . iif($vbulletin->GPC['imagecategoryid'], "WHERE " . $vbulletin->GPC['table'] . ".imagecategoryid = " . $vbulletin->GPC['imagecategoryid'], '') . "
			ORDER BY " . iif($vbulletin->GPC['table'] == 'avatar','minimumposts,') . "imagecategory.displayorder," . $vbulletin->GPC['table'] . ".displayorder
			LIMIT $startat, " . $vbulletin->GPC['perpage']
		);
	}
	$itemcache = array();
	while ($item = $db->fetch_array($items))
	{
		if ($vbulletin->GPC['table'] != 'avatar')
		{
			$item['minimumposts'] = 0;
		}
		$itemcache["$item[minimumposts]"][] = $item;
	}
	$j = 0;

	print_form_header('image', 'displayorder');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	construct_hidden_code('imagecategoryid', $vbulletin->GPC['imagecategoryid']);
	construct_hidden_code('massmove', $vbulletin->GPC['massmove']);
	print_table_header(
		$vbphrase["{$itemtype}_manager"]
		. ' <span class="normal">'
		. iif($categoryinfo, "$categoryinfo[title] - ")
		. construct_phrase($vbphrase['page_x_of_y'], $vbulletin->GPC['page'], $totalpages)
		. '</span>',
		$img_per_row
	);

	foreach ($itemcache AS $minimumposts => $val)
	{
		if ($vbulletin->GPC['table'] == 'avatar')
		{
			print_description_row($vbphrase['minimum_posts'] . ': ' . $minimumposts, 0, $img_per_row, 'thead', 'center');
			$lastcategory = 0;
		}
		$cell = array();
		$i = 0;
		foreach($val AS $item)
		{
			if ($item['imagecategoryid'] != $lastcategory AND !$categoryinfo)
			{
				$i = 0;
				print_image_item_row($cell);
				print_description_row('- - ' . iif(empty($item['category']), '(' . $vbphrase['uncategorized'] . ')', $item['category']) . ' - -', 0, $img_per_row, 'thead', 'center');

			}
			if ($i < $img_per_row)
			{
				$cell[] = print_image_item(
					$item,
					$itemid,
					$itempath,
					$vbulletin->GPC['page'],
					$vbulletin->GPC['perpage'],
					$catid,
					$vbulletin->GPC['massmove'],
					$vbulletin->GPC['imagecategoryid']
				);
			}
			else
			{
				$i = 0;
				print_image_item_row($cell);
				$cell[] = print_image_item(
					$item,
					$itemid,
					$itempath,
					$vbulletin->GPC['page'],
					$vbulletin->GPC['perpage'],
					$catid,
					$vbulletin->GPC['massmove'],
					$vbulletin->GPC['imagecategoryid']
				);
			}
			$lastcategory = $item['imagecategoryid'];
			$j++;
			$i++;
		}
		print_image_item_row($cell);
	}

	construct_hidden_code('page', $vbulletin->GPC['page']);
	if ($totalitems > $vbulletin->GPC['perpage'])
	{
		$pagebuttons = "\n\t" . $vbphrase['pages'] . ": ($totalpages)\n";
		for ($i = 1; $i <= $totalpages; $i++)
		{
			$pagebuttons .= "\t<input type=\"submit\" class=\"button\" name=\"pagesub\" value=\" $i \"" . iif($i == $page, ' disabled="disabled"') . " tabindex=\"1\" />\n";
		}
		$pagebuttons .= "\t&nbsp; &nbsp; &nbsp; &nbsp;";
	}
	else
	{
		$pagebuttons = '';
	}
	if ($vbulletin->GPC['massmove'])
	{
		$categories = '<option value="0"></option>';
		$categories .= construct_select_options(fetch_image_categories_array($catid));
		$categories = '<select name="selectall" class="bginput" onchange="js_select_all(this.form);">' . $categories . '</select>';

		$buttontext = $vbphrase['mass_move'];
	}
	else
	{
		$buttontext = $vbphrase['save_display_order'];
	}
	print_table_footer($img_per_row, "\n\t$categories <input type=\"submit\" class=\"button\" name=\"doorder\" value=\"" . $buttontext . "\" tabindex=\"1\" />\n\t&nbsp; &nbsp; &nbsp; &nbsp;$pagebuttons
	" . $vbphrase['per_page'] . "
	<input type=\"text\" name=\"perpage\" value=\"" . $vbulletin->GPC['perpage'] . "\" size=\"3\" tabindex=\"1\" />
	<input type=\"submit\" class=\"button\" value=\"" . $vbphrase['go'] . "\" tabindex=\"1\" />\n\t");

	echo "<p align=\"center\">" .
		construct_link_code($vbphrase["add_{$itemtype}"], "image.php?" . $vbulletin->session->vars['sessionurl'] . "do=add&table=" . $vbulletin->GPC['table']) .
		construct_link_code($vbphrase["edit_{$itemtype}_categories"], "image.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify&table=" . $vbulletin->GPC['table']) .
	"</p>";

}

// ###################### Start Modify Categories #######################
if ($_REQUEST['do'] == 'modify')
{
	$categories = $db->query_read("
		SELECT imagecategory.*, COUNT(" . $vbulletin->GPC['table'] . ".$itemid) AS items
		FROM " . TABLE_PREFIX . "imagecategory AS imagecategory
		LEFT JOIN " . TABLE_PREFIX . $vbulletin->GPC['table'] . " AS " . $vbulletin->GPC['table'] . " USING(imagecategoryid)
		WHERE imagetype = $catid
		GROUP BY imagecategoryid
		ORDER BY displayorder
	");

	if ($db->num_rows($categories))
	{
		print_form_header('image', 'docategorydisplayorder');
		construct_hidden_code('table', $vbulletin->GPC['table']);
		print_table_header($vbphrase["edit_{$itemtype}_categories"], 4);
		print_cells_row(array($vbphrase['title'], $vbphrase['contains'], $vbphrase['display_order'], $vbphrase['controls']), 1);
		while ($category = $db->fetch_array($categories))
		{
			$cell = array();
			$cell[] = "<a href=\"image.php?" . $vbulletin->session->vars['sessionurl'] . "do=viewimages&table=" . $vbulletin->GPC['table'] . "&imagecategoryid=$category[imagecategoryid]\">$category[title]</a>";
			$cell[] = vb_number_format($category['items']) . ' ' . $vbphrase["$itemtypeplural"];
			$cell[] = "<input type=\"text\" class=\"bginput\" name=\"order[$category[imagecategoryid]]\" value=\"$category[displayorder]\" tabindex=\"1\" size=\"3\" />";
			$cell[] =
				construct_link_code(
					$vbphrase['mass_move'], "image.php?"
					. $vbulletin->session->vars['sessionurl']
					. "do=viewimages"
					. "&amp;massmove=1"
					. "&amp;table=" . $vbulletin->GPC['table']
					. "&amp;imagecategoryid=" . $category['imagecategoryid']
				) .
				construct_link_code(
					$vbphrase['view'], "image.php?"
					. $vbulletin->session->vars['sessionurl']
					. "do=viewimages"
					. "&amp;table=" . $vbulletin->GPC['table']
					. "&amp;imagecategoryid=" . $category['imagecategoryid']
				) .
				construct_link_code(
					$vbphrase['edit'], "image.php?"
					. $vbulletin->session->vars['sessionurl']
					. "do=editcategory"
					. "&amp;table=" . $vbulletin->GPC['table']
					. "&amp;imagecategoryid=" . $category['imagecategoryid']
				).
				construct_link_code(
					$vbphrase['delete'], "image.php?"
					. $vbulletin->session->vars['sessionurl']
					. "do=removecategory"
					. "&amp;table=" . $vbulletin->GPC['table']
					. "&amp;imagecategoryid=" . $category['imagecategoryid']
				).

				iif($category['imagetype'] != 3,
					construct_link_code(
						$vbphrase["{$itemtype}_permissions"], "image.php?"
						. $vbulletin->session->vars['sessionurl']
						. "do=editpermissions"
						. "&amp;table=" . $vbulletin->GPC['table']
						. "&amp;imagecategoryid=" . $category['imagecategoryid']
					)
					, ''
				);
			print_cells_row($cell);
		}
		print_submit_row($vbphrase['save_display_order'], NULL, 4);
		echo "<p align=\"center\">" .
			construct_link_code(
				$vbphrase["add_new_{$itemtype}_category"], "image.php?"
				. $vbulletin->session->vars['sessionurl']
				. "do=addcategory"
				. "&table=" . $vbulletin->GPC['table']
			) .
			construct_link_code(
				$vbphrase["show_all_{$itemtypeplural}"], "image.php?"
				. $vbulletin->session->vars['sessionurl']
				. "do=viewimages"
				. "&amp;table=" . $vbulletin->GPC['table']
			).
		"</p>";

	}
	else
	{
		print_stop_message("no_{$itemtype}_categories_found", "image.php?do=addcategory&amp;table=" . $vbulletin->GPC['table']);
	}
}

/**
* Stores a serialized list of usergroups who do not have permission to use any avatars into the datastore
* @param		mixed  String 'avatar' or integer 1
*
* @return	array
*/

function build_image_permissions($table)
{
	global $vbulletin;
	$output = array();

	if ($table != 'avatar' AND $table != 1)
	{
		return $output;
	}

	$categories = $vbulletin->db->query_read("
		SELECT imagecategory.imagecategoryid, COUNT(avatarid) AS avatars
		FROM " . TABLE_PREFIX . "imagecategory AS imagecategory
		LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.imagecategoryid=imagecategory.imagecategoryid)
		WHERE imagetype = 1
		GROUP BY imagecategory.imagecategoryid
		HAVING avatars > 0
	");

	$cats = array();
	while ($cat = $vbulletin->db->fetch_array($categories))
	{
			$cats[] = $cat['imagecategoryid'];
	}

	if (!empty($cats))
	{
		$noperms = $vbulletin->db->query_read("
			SELECT usergroupid, COUNT(*) AS count
			FROM " . TABLE_PREFIX . "imagecategorypermission
			WHERE imagecategoryid IN (" . implode(',', $cats) . ")
			GROUP BY usergroupid
			HAVING count = " . count($cats) . "
		");
		while ($noperm = $vbulletin->db->fetch_array($noperms))
		{
			$output[] = $noperm['usergroupid'];
		}
	}
	else	// No Avatars?
	{
		$output['all'] = true;
	}

	build_datastore('noavatarperms', serialize($output), 1);
	return $output;
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>
