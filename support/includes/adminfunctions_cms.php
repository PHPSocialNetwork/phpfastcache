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

error_reporting(E_ALL & ~E_NOTICE);

/**
* Prints a grid row for use in cms_admin.php?do=grid
*
* @param	array	Grid array containing gridid, title
*/
function print_grid_row($grid)
{
	global $vbulletin, $typeoptions, $vbphrase;
	$gridid = $grid['gridid'];

	if ($grid['flattened'])
	{
		$options = array(
			'grid_doflatten' => $vbphrase['edit'],
			'grid_unflatten' => $vbphrase['unflatten_grid'],
			'modifylayout'   => $vbphrase['create_layout'],
			'grid_delete'    => $vbphrase['delete'],
		);
	}
	else
	{
		$options = array(
			'grid_modify'  => $vbphrase['edit'],
			'grid_flatten' => $vbphrase['flatten_grid'],
			'modifylayout' => $vbphrase['create_layout'],
			'grid_delete'  => $vbphrase['delete'],
		);
	}

	$cell = array();
	$cell[] = $grid['title'];
	$cell[] = "<span style=\"white-space:nowrap\">
				<select id=\"grid_options_$grid[gridid]\" name=\"g$grid[gridid]\" onchange=\"js_jump($grid[gridid], this);\" class=\"bginput\">" . construct_select_options($options) . "</select>
				<input id=\"grid_go_button_$grid[gridid]\" type=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_jump($grid[gridid], this.form.g$grid[gridid]);\" class=\"button\" />
			</span>";
	print_cells_row($cell);
}

/**
* Reads XML grids file and imports data from it into the database
*
* @param	string	XML data
* @param	boolean	Allow overwriting of existing grids with same name
*/
function xml_import_grid($xml = false, $allowoverwrite = false)
{
	// $GLOBALS['path'] needs to be passed into this function or reference $vbulletin->GPC['path']

	global $vbulletin, $vbphrase;

	print_dots_start('<b>' . $vbphrase['importing_grid'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');

	require_once(DIR . '/includes/class_xml.php');

	$xmlobj = new vB_XML_Parser($xml, $vbulletin->GPC['path']);
	if ($xmlobj->error_no == 1)
	{
			print_dots_stop();
			print_stop_message('no_xml_and_no_path');
	}
	else if ($xmlobj->error_no == 2)
	{
			print_dots_stop();
			print_stop_message('please_ensure_x_file_is_located_at_y', 'vbulletin-grid.xml', $vbulletin->GPC['path']);
	}

	if(!$arr = $xmlobj->parse())
	{
		print_dots_stop();
		print_stop_message('xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line());
	}

	if (!$arr['grid'])
	{
		print_dots_stop();
		print_stop_message('invalid_file_specified');
	}

	$grids = array();
	$gridq = $vbulletin->db->query_read("
		SELECT gridid
		FROM " . TABLE_PREFIX . "cms_grid
	");
	while ($grid = $vbulletin->db->fetch_array($gridq))
	{
		$grids[] = $grid['gridid'];
	}

	if (!is_array($arr['grid'][0]))
	{
		$arr['grid'] = array($arr['grid']);
	}

	require_once(DIR . '/includes/adminfunctions_template.php');

	$newgrids = array();
	foreach($arr['grid'] AS $grid)
	{
		$vbulletin->db->query_write("
			" . ($allowoverwrite ? "REPLACE" : "INSERT IGNORE") . " INTO " . TABLE_PREFIX . "cms_grid
				(title, auxheader, auxfooter, addcolumn, addcolumnsnap, addcolumnsize, gridcolumns, gridhtml)
			VALUES
				(
					'" . $vbulletin->db->escape_string($grid['name']) . "',
					" . intval($grid['auxheader']) . ",
					" . intval($grid['auxfooter']) . ",
					" . intval($grid['addcolumn']) . ",
					" . intval($grid['addcolumnsnap']) . ",
					" . intval($grid['addcolumnsize']) . ",
					" . intval($grid['gridcolumns']) . ",
					'" . $vbulletin->db->escape_string($grid['value']) . "'
				)
		");

		if ($gridid = $vbulletin->db->insert_id())
		{
			$title = "vbcms_grid_$gridid";
			$vbulletin->db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "template
					(styleid, title, template, template_un, dateline, username, product, version)
				VALUES
					(
						0,
						'" . $vbulletin->db->escape_string($title) . "',
						'" . $vbulletin->db->escape_string(compile_template($grid["value"])) . "',
						'" . $vbulletin->db->escape_string($grid["value"]) . "',
						" . TIMENOW . ",
						'" . $vbulletin->vbulletin->userinfo['username'] . "',
						'vbcms',
						'" . $vbulletin->db->escape_string($vbulletin->options['templateversion']) . "'
					)
			");
		}
	}

	$newgrids = array();
	$gridq = $vbulletin->db->query_read("
		SELECT gridid
		FROM " . TABLE_PREFIX . "cms_grid
	");
	while ($grid = $vbulletin->db->fetch_array($gridq))
	{
		$newgrids[] = $grid['gridid'];
	}

	$removetemplates = array_diff($grids, $newgrids);
	$templates = array();
	foreach ($removetemplates AS $gridid)
	{
		$templates[] = "vbcms_grid_$gridid";
	}
	if (!empty($templates))
	{
		$vbulletin->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "template
			WHERE
				title IN ('" . implode("', '", $templates) . "')
					AND
				templatetype = 'template'
					AND
				styleid = 0
		");
	}

	print_rebuild_style(-1, '', 0, 0, 0, 0);
	print_rebuild_style(-2, '', 0, 0, 0, 0);

	print_dots_stop();
}

/**
* Evals a widget's configuration, set default values, this is surely to change
*
* This function appears to be unused.
*
* @param	object	Reference to $vbulletin->db
* @param	string	HTML containing widget options
* @param	integer	idfield
*/
function fetch_widgethtml(&$db, $widgethtml, $widgetinstanceid)
{
	if ($widgetinstanceid)
	{
		$attributes = $db->query_read_slave("
			SELECT attribute, value
			FROM " . TABLE_PREFIX . "cms_widgetinstanceoption
			WHERE widgetinstanceid = $widgetinstanceid
		");
		while ($attribute = $db->fetch_array($attributes))
		{
			$attr = htmlspecialchars_uni($attribute['attribute']);
			$value = htmlspecialchars_uni($attribute['value']);

			$$attr = array(
				'value' => $value,
				'selected_' . $value => 'selected="selected"',
				'checked_' . $value  => 'checked="checked"',
			);
		}
	}

	$widgethtml = addslashes($widgethtml);
	$widgethtml = str_replace('\\\\$', '\\$', $widgethtml);

	//this eval looks suspicious.  If the widgethtml contains expressions like {$db->query(...)}
	//in it, the could be run here.  Since this function doesn't appear to be called, its
	//hard to say if widgethtml has a trusted source or not.
	eval('$evalhtml = "' . $widgethtml . '";');
	return $evalhtml;
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision$
|| ####################################################################
\*======================================================================*/
?>