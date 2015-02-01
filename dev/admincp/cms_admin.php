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
define('CVS_REVISION', '$RCSfile$ - $Revision: 27874 $');
define('NOZIP', 1);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cpcms', 'widgettypes');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_cms.php');
require_once(DIR . '/includes/functions_cms_layout.php');

vB_Router::setRelativePath('../'); // Needed ?

if (! isset($vbulletin->userinfo['permissions']['cms']))
{
	vBCMS_Permissions::getUserPerms();
}

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!($vbulletin->userinfo['permissions']['cms']['admin']))
{
	print_cp_no_permission();
}


// ############################# LOG ACTION ###############################
/*
$vbulletin->input->clean_array_gpc('r', array(
));
*/

/*
log_admin_action(iif($vbulletin->GPC['moderatorid'] != 0, " moderator id = " . $vbulletin->GPC['moderatorid'],
					iif($vbulletin->GPC['calendarid'] != 0, "calendar id = " . $vbulletin->GPC['calendarid'], '')));

*/
// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'layout';
}

// Actions that need the yui grid css
$grid_actions = array(
	'removelayout',
	'layout',
	'updatelayout',
	'modifylayout',
	'grid',
	'grid_modify',
	'grid_modify',
	'grid_flatten',
	'grid_doflatten',
	'grid_unflatten',
	'grid_dounflatten',
	'grid_delete',
	'grid_update',
	'grid_doedit',
	'grid_dodelete',
	'grid_files',
	'grid_upload'
);

$widget_header = '';

if (in_array($_REQUEST['do'], $grid_actions))
{
	$ddjs = '
		<link rel="stylesheet" type="text/css" href="../clientscript/yui/grids.css" />
		<link rel="stylesheet" type="text/css" href="../clientscript/vbulletin_yui_grid_addon.css" />
		<style type="text/css">
			body
			{
				text-align:' . vB_Template_Runtime::fetchStyleVar('left') . '
			}
			#doc3
			{
				margin:0;
			}
		</style>
	';
}
else if ($_REQUEST['do'] == 'widget')
{
$options = array(
		'editwidget' => $vbphrase['edit'],
		'deletewidget' => $vbphrase['delete'],
	);
$widget_header = "
	<script type=\"text/javascript\" src=\"../clientscript/yui/yuiloader-dom-event/yuiloader-dom-event.js?v=" . vB::$vbulletin->options['simpleversion'] . "\"></script>
	<script type=\"text/javascript\" src=\"../clientscript/vbulletin-core.js?v=" . vB::$vbulletin->options['simpleversion'] . "\"></script>
	<script type=\"text/javascript\" src=\"../clientscript/vbulletin_overlay.js?v=" . vB::$vbulletin->options['simpleversion'] . "\"></script>
	<script type=\"text/javascript\" src=\"../clientscript/vbulletin_cms.js?v=" . vB::$vbulletin->options['simpleversion']  . "\"></script>
	<script type=\"text/javascript\" src=\"../clientscript/vbulletin_ajax_suggest.js?v=" . vB::$vbulletin->options['simpleversion']  . "\"></script>
	<script type=\"text/javascript\" src=\"../clientscript/vbulletin_menu.js?v=" . vB::$vbulletin->options['simpleversion'] . "\"></script>
	<script type=\"text/javascript\" src=\"../clientscript/vbulletin_cms_management.js?v=" . vB::$vbulletin->options['simpleversion'] . "\"></script>
	<script type=\"text/javascript\" src=\"../clientscript/vbulletin_textedit.js?v=" . vB::$vbulletin->options['simpleversion'] . "\" ></script>

<script type=\"text/javascript\">

function initSuggest()
{
     triesleft--;
     try
     {
        if (document.getElementById('tag_search_menu') != undefined)
           {
				  triesleft = 0
              tag_add_comp = new vB_AJAX_TagSuggest('tag_add_comp', 'srch_tag_text', 'tag_search');
              tag_add_comp.allow_multiple = false;

              user_add_comp = new vB_AJAX_NameSuggest('user_add_comp', 'username', 'user_search');
              user_add_comp.allow_multiple = false;

              group_add_comp = new vB_AJAX_SocialGroupSuggest('group_add_comp', 'group_text', 'group_search');
              group_add_comp.allow_multiple = false;


              return;
            }
        if (triesleft > 0)
        {
           setTimeout( 'initSuggest()', 500)
        }
     }
     catch (e)
     {
        if (triesleft > 0)
        {
           setTimeout( 'initSuggest()', 500)
        }

     }
}

</script>
<link rel=\"stylesheet\" type=\"text/css\" href=\"../css.php?sheet=popupmenu.css,editor.css,components.css,vbulletin-formcontrols.css" .
  '&amp;langid=' . LANGUAGEID . '&amp;d=' . $style['dateline'] . '&amp;td=' . $vbulletin->stylevars['textdirection']['string'] . "\" />
	<script type=\"text/javascript\">
	function js_jump(id, obj)
	{
		task = obj.options[obj.selectedIndex].value;
		switch (task)
		{
			case 'editwidget':
				window.location = \"cms_admin.php?". $vbulletin->session->vars['sessionurl_js'] . "do=editwidget&widgetid=\" + id; break;
			case 'deletewidget':
				window.location = \"cms_admin.php?". $vbulletin->session->vars['sessionurl_js'] . "do=deletewidget&widgetid=\" + id; break;
			default:
				return false;
		}
	}
	</script>
";
}
else
{
	// Check widget id
	$widgetid = $vbulletin->input->clean_gpc('r', 'widgetid', TYPE_UINT);

	if ($widgetid)
	{
		$widgets = new vBCms_Collection_Widget($widgetid);

		if (isset($widgets[$widgetid]))
		{
			$widget = $widgets[$widgetid];
		}
		else
		{
			print_stop_message('invalid_x_specified', 'widgetid');
		}
	}
}

switch($_REQUEST['do'])
{
	case 'removelayout':
	case 'layout':
	case 'updatelayout':
		print_cp_header($vbphrase['layout_manager']);
		break;

	case 'modifylayout':
		print_cp_header($vbphrase['layout_manager'], '', $ddjs);
		break;

	case 'grid':
	case 'grid_modify':
	case 'grid_flatten':
	case 'grid_doflatten':
	case 'grid_unflatten':
	case 'grid_dounflatten':
	case 'grid_delete':
	case 'grid_update':
	case 'grid_doedit':
	case 'grid_dodelete':
	case 'grid_files':
	case 'grid_upload':
		print_cp_header($vbphrase['grid_manager']);
		break;

	case 'widget':
	case 'deletewidget':
	case 'removewidget':
	case 'editwidget':
	case 'updatewidget':
	case 'newwidget':
	case 'addwidget':
		print_cp_header($vbphrase['widget_manager'], '', $widget_header . "\n" . '<link rel="stylesheet" type="text/css" href="../css.php?sheet=overlay.css' .
  		'&amp;langid=' . LANGUAGEID . '&amp;d=' . $style['dateline'] . '&amp;td=' . $vbulletin->stylevars['textdirection']['string'] . "\" />");
		break;

	default:
		break;
}

$vbulletin->input->clean_array_gpc('r', array(
	'perpage' => TYPE_UINT,
	'startat' => TYPE_UINT
));

// #############################################################################
// main grid list display
if ($_REQUEST['do'] == 'grid')
{
	?>
	<script type="text/javascript">
	function js_jump(id, obj)
	{
		task = obj.options[obj.selectedIndex].value;
		switch (task)
		{
			case 'grid_modify':
				window.location = "cms_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=grid_modify&gridid=" + id; break;
			case 'grid_delete':
				window.location = "cms_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=grid_delete&gridid=" + id; break;
			case 'grid_doflatten':
				window.location = "cms_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=grid_doflatten&gridid=" + id; break;
			case 'grid_flatten':
				window.location = "cms_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=grid_flatten&gridid=" + id; break;
			case 'grid_unflatten':
				window.location = "cms_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=grid_unflatten&gridid=" + id; break;
			case 'modifylayout':
				window.location = "cms_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=modifylayout&gridid=" + id; break;
			default:
				return false;
		}
	}
	</script>
	<?php

	print_form_header('cms_admin', 'grid_modify');
	construct_hidden_code('goto', "cms_admin.php?do=grid" . $vbulletin->session->vars['sessionurl']);
	print_table_header($vbphrase['grid_manager'], 2);
	print_cells_row(array($vbphrase['grid'], $vbphrase['controls']), 1);

	$grids_result = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "cms_grid
		ORDER BY title
	");
	$grids_cache = array();	// in case if we need to re-use it later?
	$have_grid = false;
	while ($grid = $db->fetch_array($grids_result))
	{
		$have_grid = true;
		$grids_cache[] = $grid;
		print_grid_row($grid);
	}

	if (!$have_grid)
	{
		// print no grid in db message?
	}

	print_table_footer(2, '
		<input type="submit" id="button_new_grid" class="button" value="' . $vbphrase['add_new_grid'] . '" tabindex="1" />
		<input type="button" id="button_upload_grids" class="button" value="' . $vbphrase['download_upload_grids'] . '" tabindex="1" onclick="window.location=\'cms_admin.php?do=grid_files\';" />
	');
}

// ###################################################################
if ($_POST['do'] == 'grid_upload')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'allowoverwrite'   => TYPE_BOOL,
		'serverfile'       => TYPE_STR,
	));

	$vbulletin->input->clean_array_gpc('f', array(
		'gridfile'        => TYPE_FILE,
	));

	// got an uploaded file?
	// do not use file_exists here, under IIS it will return false in some cases
	if (is_uploaded_file($vbulletin->GPC['gridfile']['tmp_name']))
	{
		$xml = file_read($vbulletin->GPC['gridfile']['tmp_name']);
	}
	// no uploaded file - got a local file?
	else if (file_exists($vbulletin->GPC['serverfile']))
	{
		$xml = file_read($vbulletin->GPC['serverfile']);
	}
	// no uploaded file and no local file - ERROR
	else
	{
		print_stop_message('no_file_uploaded_and_no_local_file_found');
	}

	xml_import_grid($xml,
		$vbulletin->GPC['allowoverwrite']
	);

	print_cp_redirect("cms_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=grid");
}

// ###################################################################
if ($_REQUEST['do'] == 'grid_files')
{
	// download / upload grids  (xml, like styles)
	?>
	<script type="text/javascript">
	<!--
	function js_confirm_upload(tform, filefield)
	{
		if (filefield.value == "")
		{
			return confirm("<?php echo construct_phrase($vbphrase['you_did_not_specify_a_file_to_upload'], '" + tform.serverfile.value + "'); ?>");
		}
		return true;
	}
	// -->
	</script>
	<?php
	print_form_header('cms_admin', 'grid_download', 0, 1, 'downloadform" target="download');
	print_table_header($vbphrase['download']);
	print_input_row($vbphrase['filename'], 'filename', 'vbulletin-grid.xml');
	print_submit_row($vbphrase['download']);

	print_form_header('cms_admin', 'grid_upload', 1, 1, 'uploadform" onsubmit="return js_confirm_upload(this, this.gridfile);');
	print_table_header($vbphrase['import_grid_xml_file']);
	print_upload_row($vbphrase['upload_xml_file'], 'gridfile', 999999999);
	print_input_row($vbphrase['import_xml_file'], 'serverfile', './install/vbulletin-grid.xml');
	print_yes_no_row($vbphrase['allow_overwrite_grid'], 'allowoverwrite', 0);

	print_submit_row($vbphrase['import']);
}

// ###################################################################
if ($_REQUEST['do'] == 'grid_download')
{

	if (function_exists('set_time_limit') AND !SAFEMODE)
	{
		@set_time_limit(1200);
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'filename' => TYPE_STR,
	));

	// --------------------------------------------
	// work out what we are supposed to do

	// set a default filename
	if (empty($vbulletin->GPC['filename']))
	{
		$vbulletin->GPC['filename'] = 'vbulletin-style.xml';
	}

	// --------------------------------------------
	// query the grids and put them in an array

	$grids = array();

	$getgrids = $db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "cms_grid
		ORDER BY title
	");
	while ($grid = $db->fetch_array($getgrids))
	{
		$grids[] = $grid;
	}
	$db->free_result($getgrids);

	if (empty($grids))
	{
		print_stop_message('no_grids_to_download');
	}

	// --------------------------------------------
	// now output the XML

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_XML_Builder($vbulletin);
	$xml->add_group('grids');

	foreach($grids AS $grid)
	{
		$attributes = array(
			'name'          => htmlspecialchars($grid['title']),
			'auxheader'     => $grid['auxheader'],
			'auxfooter'     => $grid['auxfooter'],
			'addcolumn'     => $grid['addcolumn'],
			'addcolumnsnap' => $grid['addcolumnsnap'],
			'addcolumnsize' => $grid['addcolumnsize'],
			'columns'       => $grid['gridcolumns']
		);
		$xml->add_tag('grid', $grid['gridhtml'], $attributes, true);
	}

	$xml->close_group();

	$doc = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n\r\n";

	$doc .= $xml->output();
	$xml = null;

	require_once(DIR . '/includes/functions_file.php');
	file_download($doc, $vbulletin->GPC['filename'], 'text/xml');
}

// ###################################################################
if ($_POST['do'] == 'grid_update')
{
	// actually adding the grid into the system
	$vbulletin->input->clean_array_gpc('r', array(
		'auxheader'     => TYPE_BOOL,
		'auxfooter'     => TYPE_BOOL,
		'addcolumn'     => TYPE_BOOL,
		'addcolumnsnap' => TYPE_UINT,
		'addcolumnsize' => TYPE_UINT,
		'columns'       => TYPE_UINT,
		'gridid'        => TYPE_UINT,
		'title'         => TYPE_NOHTML,
	));

	if (!$vbulletin->GPC['title'])
	{
		print_stop_message('please_complete_required_fields');
	}

	$gridinfo = array();
	$wheresql = '';
	if ($vbulletin->GPC['gridid'])
	{
		$gridinfo = $db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "cms_grid
			WHERE
				gridid = " . $vbulletin->GPC['gridid'] . "
		");
		if (!$gridinfo)
		{
			print_stop_message('invalid_x_specified', 'gridid');
		}
		$wheresql = "AND gridid <> $gridinfo[gridid]";
	}
	else
	{
		$wheresql = '';
	}

	if ($db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "cms_grid
		WHERE
			title = '" . $db->escape_string($vbulletin->GPC['title']) . "'
			$wheresql
	"))
	{
		print_stop_message('grid_title_already_in_use');
	}


	$columnid = 1;
	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_XML_Builder($vbulletin);
	if ($vbulletin->GPC['addcolumn'])
	{
		$side = "l";
		if ($vbulletin->GPC['addcolumnsnap'] == 1)
		{
			$side = "r";
		}
		$docclass = "yui-tvb-" . $side . $vbulletin->GPC['addcolumnsize'];	// yui-tvb-(l|r)(1-4)
	}
	$xml->add_group('div', $docclass ? array('id' => 'doc3', 'class' => $docclass) : array('id' => 'doc3'));  // #doc
	if ($vbulletin->GPC['auxheader'])
	{
		$xml->add_group('div', array('id' => 'hd'));
		$xml->add_group('div', array('class' => 'yui-u yui-header'));
		$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
		$columnid++;
		$xml->close_group();
		$xml->close_group();
	}
	$xml->add_group('div', array('id' => 'bd')); // #bd

	if ($vbulletin->GPC['addcolumn'])
	{
		$xml->add_group('div', array('id' => 'yui-main')); // #yui-main
		$xml->add_group('div', array('class' => 'yui-b'));
	}
	switch($vbulletin->GPC['columns'])
	{
		case 1:
			// 1 column, 100
			$xml->add_group('div', array('class' => 'yui-u yui-panel'));
				$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
				$columnid++;
			$xml->close_group();
			break;
		case 2:
			// 2 columns, 50/50
			$xml->add_group('div', array('class' => 'yui-g'));
				$xml->add_group('div', array('class' => 'yui-u first yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
				$xml->add_group('div', array('class' => 'yui-u yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
			$xml->close_group();
			break;
		case 3:
			// 2 columns, 66/33
			$xml->add_group('div', array('class' => 'yui-gc'));
				$xml->add_group('div', array('class' => 'yui-u first yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
				$xml->add_group('div', array('class' => 'yui-u yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
			$xml->close_group();
			break;
		case 4:
			// 2 columns, 33/66
			$xml->add_group('div', array('class' => 'yui-gd'));
				$xml->add_group('div', array('class' => 'yui-u first yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
				$xml->add_group('div', array('class' => 'yui-u yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
			$xml->close_group();
			break;
		case 5:
			// 2 columns, 60/40
			$xml->add_group('div', array('class' => 'yui-g'));
			$xml->add_group('div', array('class' => 'yui-tvb-l60 first yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$columnid++;
			$xml->close_group();
			$xml->add_group('div', array('class' => 'yui-tvb-r40 yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$columnid++;
			$xml->close_group();
			$xml->close_group();
			break;
		case 6:
			// 2 columns, 40/60
			$xml->add_group('div', array('class' => 'yui-g'));
			$xml->add_group('div', array('class' => 'yui-tvb-l40 first yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$columnid++;
			$xml->close_group();
			$xml->add_group('div', array('class' => 'yui-tvb-r60 yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$columnid++;
			$xml->close_group();
			$xml->close_group();
			break;
		case 7:
			// 2 columns, 75/25
			$xml->add_group('div', array('class' => 'yui-ge'));
				$xml->add_group('div', array('class' => 'yui-u first yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
				$xml->add_group('div', array('class' => 'yui-u yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
			$xml->close_group();
			break;
		case 8:
			// 2 columns, 25/75
			$xml->add_group('div', array('class' => 'yui-gf'));
				$xml->add_group('div', array('class' => 'yui-u first yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
				$xml->add_group('div', array('class' => 'yui-u yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
			$xml->close_group();
			break;
		case 9:
			// 3 columns, 33/33/33
			$xml->add_group('div', array('class' => 'yui-gb'));
				$xml->add_group('div', array('class' => 'yui-u first yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
				$xml->add_group('div', array('class' => 'yui-u yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
				$xml->add_group('div', array('class' => 'yui-u yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
			$xml->close_group();
			break;
		case 10:
			// 3 columns, 50/25/25
			$xml->add_group('div', array('class' => 'yui-g'));
				$xml->add_group('div', array('class' => 'yui-g'));
					$xml->add_group('div', array('class' => 'yui-u first yui-panel'));
						$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
						$columnid++;
					$xml->close_group();
					$xml->add_group('div', array('class' => 'yui-u yui-panel'));
						$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
						$columnid++;
					$xml->close_group();
				$xml->close_group();
				$xml->add_group('div', array('class' => 'yui-u yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
			$xml->close_group();
			break;
		case 11:
			// 3 columns, 25/25/50
			$xml->add_group('div', array('class' => 'yui-g'));
				$xml->add_group('div', array('class' => 'yui-u yui-panel'));
					$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
					$columnid++;
				$xml->close_group();
				$xml->add_group('div', array('class' => 'yui-g'));
					$xml->add_group('div', array('class' => 'yui-u first yui-panel'));
						$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
						$columnid++;
					$xml->close_group();
					$xml->add_group('div', array('class' => 'yui-u yui-panel'));
						$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
						$columnid++;
					$xml->close_group();
				$xml->close_group();
			$xml->close_group();
			break;
		case 12:
			// 3 columns, 25/50/25
			$xml->add_group('div', array('class' => 'yui-g'));
			$xml->add_group('div', array('class' => 'yui-tvb-l25 first yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$xml->close_group();
			$columnid++;
			$xml->add_group('div', array('class' => 'yui-tvb-l50 first yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$xml->close_group();
			$columnid++;
			$xml->add_group('div', array('class' => 'yui-tvb-l25 first yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$xml->close_group();
			$columnid++;
			$xml->close_group();
			$columnid += 3;
			break;
		case 13:
			// 3 columns, 30/40/30
			$xml->add_group('div', array('class' => 'yui-g'));
			$xml->add_group('div', array('class' => 'yui-tvb-l30 first yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$xml->close_group();
			$columnid++;
			$xml->add_group('div', array('class' => 'yui-tvb-l40 yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$xml->close_group();
			$columnid++;
			$xml->add_group('div', array('class' => 'yui-tvb-l30 yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$xml->close_group();
			$columnid++;
			$xml->close_group();
			$columnid += 3;
			break;
		case 14:
			// 3 columns, 30/30/40
			$xml->add_group('div', array('class' => 'yui-g'));
			$xml->add_group('div', array('class' => 'yui-tvb-l30 first yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$xml->close_group();
			$columnid++;
			$xml->add_group('div', array('class' => 'yui-tvb-l30 yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$xml->close_group();
			$columnid++;
			$xml->add_group('div', array('class' => 'yui-tvb-l40 yui-panel'));
			$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
			$xml->close_group();
			$columnid++;
			$xml->close_group();
			$columnid += 3;
			break;
		case 15:
			// 4 columns, 25/25/25/25
			$xml->add_group('div', array('class' => 'yui-g'));
				$xml->add_group('div', array('class' => 'yui-g first'));
					$xml->add_group('div', array('class' => 'yui-u first yui-panel'));
						$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
						$columnid++;
					$xml->close_group();
					$xml->add_group('div', array('class' => 'yui-u yui-panel'));
						$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
						$columnid++;
					$xml->close_group();
				$xml->close_group();
				$xml->add_group('div', array('class' => 'yui-g'));
					$xml->add_group('div', array('class' => 'yui-u first yui-panel'));
						$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
						$columnid++;
					$xml->close_group();
					$xml->add_group('div', array('class' => 'yui-u yui-panel'));
						$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
						$columnid++;
					$xml->close_group();
				$xml->close_group();
			$xml->close_group();
			break;
	}
	if ($vbulletin->GPC['addcolumn'])
	{
		$xml->close_group();
		$xml->close_group();	// close #yui-main
		$xml->add_group('div', array('class' => 'yui-b yui-sidebar'));	// side bar
		$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
		$columnid++;
		$xml->close_group(); // close side bar
	}
	$xml->close_group(); // close #bd
	if ($vbulletin->GPC['auxfooter'])
	{
		$xml->add_group('div', array('id' => 'ft'));
		$xml->add_group('div', array('class' => 'yui-u yui-footer'));
		$xml->add_tag('ul', '$' . 'column[' . $columnid . ']', array('class' => 'list_no_decoration widget_list', 'id' => 'widgetlist_column' . $columnid));
		$xml->close_group();
		$xml->close_group();
		$columnid++;
	}
	$xml->close_group(); // close #doc

	// get the xml, well, html segment, and store into db
	// replace "<![CDATA[" and "]]>" with "", as we don't need them in HTML.
	$replace = array("<![CDATA[", "]]>");
	$gridhtml = str_replace($replace, '', $xml->output());

	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "cms_grid
			(gridid, title, gridhtml, auxheader, auxfooter, addcolumn, addcolumnsnap, addcolumnsize, gridcolumns)
		VALUES
			(
			" . intval($gridinfo['gridid']) . ",
			'" . $db->escape_string($vbulletin->GPC['title']) . "',
			'" . $db->escape_string($gridhtml) . "',
			" . intval($vbulletin->GPC['auxheader']) . ",
			" . intval($vbulletin->GPC['auxfooter']) . ",
			" . intval($vbulletin->GPC['addcolumn']) . ",
			" . intval($vbulletin->GPC['addcolumnsnap']) . ",
			" . intval($vbulletin->GPC['addcolumnsize']) . ",
			" . intval($vbulletin->GPC['columns']) . "
			)
	");
	$gridid = $db->insert_id();

	if ($gridid)
	{
		$title = "vbcms_grid_$gridid";
		require_once(DIR . '/includes/adminfunctions_template.php');
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "template
				(styleid, title, template, template_un, dateline, username, product, version)
			VALUES
				(
					0,
					'" . $db->escape_string($title) . "',
					'" . $db->escape_string(compile_template($gridhtml)) . "',
					'" . $db->escape_string($gridhtml) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					'vbcms',
					'" . $db->escape_string($vbulletin->options['templateversion']) . "'
				)
		");
	}

	// Editing grid, make sure widgets still have a place in the layout
	if ($gridinfo['gridid'])
	{
		$columnid--;
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "cms_layout
			SET contentcolumn = $columnid
			WHERE
				gridid = " . intval($gridinfo['gridid']) . "
					AND
				contentcolumn > $columnid
		");

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "cms_layoutwidget AS lw
			INNER JOIN " . TABLE_PREFIX . "cms_layout AS layout ON (lw.layoutid = layout.layoutid)
			SET lw.layoutcolumn = $columnid
			WHERE
				layout.gridid = " . intval($gridinfo['gridid']) . "
					AND
				lw.layoutcolumn > $columnid
		");
	}

	require_once(DIR . '/includes/adminfunctions_template.php');
	print_rebuild_style(-1, '', 0, 0, 0, 0);
	print_rebuild_style(-2, '', 0, 0, 0, 0);

	define('CP_REDIRECT', 'cms_admin.php?do=grid');
	print_stop_message('saved_grid_successfully');
}

// ###################################################################
if ($_POST['do'] == 'grid_doedit')
{
	// actually save the edited grid into the system
	$vbulletin->input->clean_array_gpc('r', array(
		'gridid'   => TYPE_UINT,
		'gridhtml' => TYPE_NOTRIM,
		'template' => TYPE_NOTRIM,
		'title'    => TYPE_NOHTML,
	));

	$gridinfo = $db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "cms_grid
		WHERE
			gridid = " . $vbulletin->GPC['gridid'] . "
	");
	if (!$gridinfo)
	{
		print_stop_message('invalid_x_specified', 'gridid');
	}

	if ($db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "cms_grid
		WHERE
			title = '" . $db->escape_string($vbulletin->GPC['title']) . "'
				AND
			gridid <> $gridinfo[gridid]
	"))
	{
		print_stop_message('grid_title_already_in_use');
	}

	require_once(DIR . '/includes/adminfunctions_template.php');
	if ($errors = check_template_errors(compile_template($vbulletin->GPC['template'])))
	{
		print_cp_message(construct_phrase($vbphrase['grid_eval_error'], $errors));
	}

	preg_match_all('#<ul[^>]+id="widgetlist_column(\d+)"[^>]*>\$column\[\\1\]</ul>#si', $vbulletin->GPC['gridhtml'], $matches1);
	if ($matches1[1])
	{
		$prev = 0;
		sort($matches1[1], SORT_NUMERIC);
		foreach ($matches1[1] AS $index)
		{
			if ($index - 1 != $prev)
			{
				print_stop_message('grid_layout_ui_html_incorrect');
			}
			$prev = $index;
		}
	}
	else
	{
		print_stop_message('grid_layout_ui_html_incorrect');
	}

	preg_match_all('#\$column\[(\d+)\]#si', $vbulletin->GPC['template'], $matches2);
	if ($matches2[1])
	{
		$prev = 0;
		sort($matches2[1], SORT_NUMERIC);
		foreach ($matches2[1] AS $index)
		{
			if ($index - 1 != $prev)
			{
				print_stop_message('grid_template_html_incorrect');
			}
			$prev = $index;
		}
	}
	else
	{
		print_stop_message('grid_template_html_incorrect');
	}

	if (count($matches1[1]) != count($matches2[1]))
	{
		print_stop_message('layout_ui_no_equal_template_ui', count($matches1[1]), count($matches2[1]));
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "cms_layout
		SET contentcolumn = " . intval(count($matches1[1])) . "
		WHERE
			gridid = " . intval($gridinfo['gridid']) . "
				AND
			contentcolumn > " . intval(count($matches1[1])) . "
	");

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "cms_layoutwidget AS lw
		INNER JOIN " . TABLE_PREFIX . "cms_layout AS layout ON (lw.layoutid = layout.layoutid)
		SET lw.layoutcolumn = " . intval(count($matches1[1])) . "
		WHERE
			layout.gridid = " . intval($gridinfo['gridid']) . "
				AND
			lw.layoutcolumn > " . intval(count($matches1[1])) . "
	");

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "cms_grid
		SET
			title = '" . $db->escape_string($vbulletin->GPC['title']) . "',
			gridhtml_backup = IF(flattened = 0, gridhtml, gridhtml_backup),
			gridhtml = '" . $db->escape_string($vbulletin->GPC['gridhtml']) . "',
			flattened = 1
		WHERE gridid = " . $vbulletin->GPC['gridid'] . "
	");

	$title = "vbcms_grid_$gridinfo[gridid]";
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "template
			(styleid, title, template, template_un, dateline, username, product, version)
		VALUES
			(
				0,
				'" . $db->escape_string($title) . "',
				'" . $db->escape_string(compile_template($vbulletin->GPC['template'])) . "',
				'" . $db->escape_string($vbulletin->GPC['template']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				'vbcms',
				'" . $db->escape_string($vbulletin->options['templateversion']) . "'
			)
	");
	print_rebuild_style(-1, '', 0, 0, 0, 0);
	print_rebuild_style(-2, '', 0, 0, 0, 0);

	define('CP_REDIRECT', 'cms_admin.php?do=grid');
	print_stop_message('saved_grid_successfully');
}

// ###################################################################
if ($_POST['do'] == 'grid_dodelete')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'gridid' => TYPE_UINT,
	));

	$gridinfo = $db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "cms_grid
		WHERE
			gridid = " . $vbulletin->GPC['gridid'] . "
	");
	if (!$gridinfo)
	{
		print_stop_message('invalid_x_specified', 'gridid');
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "cms_grid
		WHERE gridid = $gridinfo[gridid]
	");

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "template
		WHERE
			title = 'vbcms_grid_$gridinfo[gridid]'
				AND
			templatetype = 'template'
				AND
			styleid = 0
	");

	require_once(DIR . '/includes/adminfunctions_template.php');
	print_rebuild_style(-1, '', 0, 0, 0, 0);
	print_rebuild_style(-2, '', 0, 0, 0, 0);

	define('CP_REDIRECT', 'cms_admin.php?do=grid');
	print_stop_message('deleted_grid_successfully');
}

// ###################################################################
if ($_REQUEST['do'] == 'grid_modify')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'gridid' => TYPE_UINT,
	));

	if ($vbulletin->GPC['gridid'])
	{
		$gridinfo = $db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "cms_grid
			WHERE
				gridid = " . $vbulletin->GPC['gridid'] . "
		");
		if (!$gridinfo)
		{
			print_stop_message('invalid_x_specified', 'gridid');
		}
	}
	else
	{
		$gridinfo = array();
	}

	print_form_header('cms_admin', 'grid_update', 0, 1);
	if ($gridinfo['gridid'])
	{
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['grid'], $gridinfo['title'], $gridinfo['gridid']));
	}
	else
	{
		print_table_header($vbphrase['define_grid']);
	}
	construct_hidden_code('gridid', $gridinfo['gridid']);
	print_input_row($vbphrase['title'], 'title', $gridinfo['title']);
	print_yes_no_row($vbphrase['secondary_header'], 'auxheader', $gridinfo['auxheader']);
	print_yes_no_row($vbphrase['secondary_footer'], 'auxfooter', $gridinfo['auxfooter']);
	print_yes_no_row($vbphrase['sidebar'], 'addcolumn', $gridinfo['addcolumn']);
	print_select_row($vbphrase['sidebar_location'], 'addcolumnsnap', array(
		0 => $vbphrase['sidebar_left'],
		1 => $vbphrase['sidebar_right']
	), $gridinfo['addcolumnsnap']);
	print_select_row($vbphrase['sidebar_width'], 'addcolumnsize', array(
		1 => $vbphrase['sidebar_120px'],
		2 => $vbphrase['sidebar_160px'],
		3 => $vbphrase['sidebar_240px'],
		4 => $vbphrase['sidebar_300px']
	), $gridinfo['addcolumnsize']);
	$columns = array(
		1  => $vbphrase['columns_1_100'],
		2  => $vbphrase['columns_2_50_50'],
		3  => $vbphrase['columns_2_66_33'],
		4  => $vbphrase['columns_2_33_66'],
		5  => $vbphrase['columns_2_60_40'],
		6  => $vbphrase['columns_2_40_60'],
		7  => $vbphrase['columns_2_75_25'],
		8  => $vbphrase['columns_2_25_75'],
		9  => $vbphrase['columns_3_33_33_33'],
		10  => $vbphrase['columns_3_50_25_25'],
		11  => $vbphrase['columns_3_25_25_50'],
		12  => $vbphrase['columns_3_25_50_25'],
		13  => $vbphrase['columns_3_30_40_30'],
		14  => $vbphrase['columns_3_30_30_40'],
		15 => $vbphrase['columns_4_25_25_25_25'],
	);
	print_select_row($vbphrase['columns'], 'columns', $columns, $gridinfo['gridcolumns']);
	print_submit_row($vbphrase['save']);
}

// ###################################################################
if ($_REQUEST['do'] == 'grid_delete')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'gridid' => TYPE_UINT,
	));

	$gridinfo = $db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "cms_grid
		WHERE
			gridid = " . $vbulletin->GPC['gridid'] . "
	");
	if (!$gridinfo)
	{
		print_stop_message('invalid_x_specified', 'gridid');
	}

	if ($db->query_first("
		SELECT gridid
		FROM " . TABLE_PREFIX . "cms_layout
		WHERE gridid = $gridinfo[gridid]
	"))
	{
		print_stop_message('grid_can_not_be_deleted');
	}

	print_delete_confirmation('cms_grid', $vbulletin->GPC['gridid'], 'cms_admin', 'grid_dodelete', 'grid', 0, '', 'title', 'gridid');
}

// ###################################################################
if ($_REQUEST['do'] == 'grid_flatten')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'gridid' => TYPE_UINT,
	));

	$gridinfo = $db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "cms_grid
		WHERE
			gridid = " . $vbulletin->GPC['gridid'] . "
	");
	if (!$gridinfo)
	{
		print_stop_message('invalid_x_specified', 'gridid');
	}

	print_confirmation($vbphrase['confirm_flatten_grid'], 'cms_admin', 'grid_doflatten', array('gridid' => $gridinfo['gridid']));
}

// ###################################################################
if ($_REQUEST['do'] == 'grid_doflatten')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'gridid' => TYPE_UINT,
	));
	$gridinfo = $db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "cms_grid
		WHERE
			gridid = " . $vbulletin->GPC['gridid'] . "
	");
	if (!$gridinfo)
	{
		print_stop_message('invalid_x_specified', 'gridid');
	}

	$template = $db->query_first_slave("
		SELECT template_un
		FROM " . TABLE_PREFIX . "template
		WHERE
			styleid = 0
				AND
			templatetype = 'template'
				AND
			title = 'vbcms_grid_" . $gridinfo['gridid'] . "'
	");

	print_form_header('cms_admin', 'grid_doedit', 0, 1);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['grid'], $gridinfo['title'], $gridinfo['gridid']));
	construct_hidden_code('gridid', $gridinfo['gridid']);
	print_input_row($vbphrase['title'], 'title', $gridinfo['title']);
	print_textarea_row($vbphrase['layout_manager_ui_html'], 'gridhtml', $gridinfo['gridhtml'], 15, 80);
	print_textarea_row($vbphrase['default_template_html'], 'template', $template['template_un'], 15, 80);
	print_submit_row($vbphrase['save']);
}

// ###################################################################
if ($_REQUEST['do'] == 'grid_unflatten')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'gridid' => TYPE_UINT,
	));
	$gridinfo = $db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "cms_grid
		WHERE
			gridid = " . $vbulletin->GPC['gridid'] . "
				AND
			flattened = 1
	");
	if (!$gridinfo)
	{
		print_stop_message('invalid_x_specified', 'gridid');
	}

	print_confirmation($vbphrase['confirm_unflatten_grid'], 'cms_admin', 'grid_dounflatten', array('gridid' => $gridinfo['gridid']));
}

// ###################################################################
if ($_REQUEST['do'] == 'grid_dounflatten')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'gridid' => TYPE_UINT,
	));
	$gridinfo = $db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "cms_grid
		WHERE
			gridid = " . $vbulletin->GPC['gridid'] . "
				AND
			flattened = 1
	");
	if (!$gridinfo)
	{
		print_stop_message('invalid_x_specified', 'gridid');
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "cms_grid
		SET
			gridhtml = gridhtml_backup,
			gridhtml_backup = '',
			flattened = 0
		WHERE gridid = " . intval($gridinfo['gridid']) . "
	");

	preg_match_all('#\$column\[(\d+)\]#si', $gridinfo['gridhtml_backup'], $matches);
	$count = count($matches[1]);

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "cms_layout
		SET contentcolumn = $count
		WHERE
			gridid = " . intval($gridinfo['gridid']) . "
				AND
			contentcolumn > $count
	");

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "cms_layoutwidget AS lw
		INNER JOIN " . TABLE_PREFIX . "cms_layout AS layout ON (lw.layoutid = layout.layoutid)
		SET lw.layoutcolumn = $count
		WHERE
			layout.gridid = " . intval($gridinfo['gridid']) . "
				AND
			lw.layoutcolumn > $count
	");

	require_once(DIR . '/includes/adminfunctions_template.php');
	$title = "vbcms_grid_$gridinfo[gridid]";
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "template
			(styleid, title, template, template_un, dateline, username, product, version)
		VALUES
			(
				0,
				'" . $db->escape_string($title) . "',
				'" . $db->escape_string(compile_template($gridinfo['gridhtml_backup'])) . "',
				'" . $db->escape_string($gridinfo['gridhtml_backup']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				'vbcms',
				'" . $db->escape_string($vbulletin->options['templateversion']) . "'
			)
	");
	print_rebuild_style(-1, '', 0, 0, 0, 0);
	print_rebuild_style(-2, '', 0, 0, 0, 0);

	define('CP_REDIRECT', 'cms_admin.php?do=grid');
	print_stop_message('saved_grid_successfully');
}

// ###################################################################
if ($_REQUEST['do'] == 'layout')
{
	?>
	<script type="text/javascript">
	function js_jump(id, obj)
	{
		task = obj.options[obj.selectedIndex].value;
		switch (task)
		{
			case 'edit':
				window.location = "cms_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=modifylayout&layoutid=" + id; break;
			case 'kill':
				window.location = "cms_admin.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=removelayout&layoutid=" + id; break;
			default:
				return false;
		}
	}
	</script>
	<?php

	$options = array(
		'edit' => $vbphrase['edit'],
		'kill' => $vbphrase['delete'],
	);

	print_form_header('cms_admin', 'modifylayout');
	print_table_header($vbphrase['layouts'], 3);
	print_cells_row(array($vbphrase['layout'], $vbphrase['grid'], $vbphrase['controls']), 1);

	$layouts = $db->query_read_slave("
		SELECT layout.layoutid, layout.title, grid.title AS gridtitle
		FROM " . TABLE_PREFIX . "cms_layout AS layout
		LEFT JOIN " . TABLE_PREFIX . "cms_grid AS grid ON (layout.gridid = grid.gridid)
		ORDER BY layout.title
	");
	while ($layout = $db->fetch_array($layouts))
	{
		print_cells_row(array(
			$layout['title'],
			$layout['gridtitle'],
			"<span style=\"white-space:nowrap\">
				<select id=\"layout_actions_$layout[layoutid]\" name=\"l$layout[layoutid]\" onchange=\"js_jump($layout[layoutid], this);\" class=\"bginput\">" . construct_select_options($options) . "</select>
				<input id=\"layout_go_button_$layout[layoutid]\" type=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_jump($layout[layoutid], this.form.l$layout[layoutid]);\" class=\"button\" />
			</span>"
		));
	}
	print_submit_row($vbphrase['add_new_layout'], 0, 3);
}

// ###################################################################
if ($_REQUEST['do'] == 'modifylayout')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'layoutid' => TYPE_UINT,
		'gridid'   => TYPE_UINT,
	));

	if ($vbulletin->GPC['layoutid'])
	{
		if (!$layoutinfo = $db->query_first_slave("
			SELECT *
			FROM " . TABLE_PREFIX . "cms_layout
			WHERE layoutid = " . $vbulletin->GPC['layoutid'])
		)
		{
			print_stop_message('invalid_x_specified', 'layoutid');
		}

		$vbulletin->GPC['gridid'] = $layoutinfo['gridid'];
	}
	else
	{
		$layoutinfo = array();
	}

	$gridoptions = array();
	$grids = $db->query_read_slave("
		SELECT gridid, title, gridhtml
		FROM " . TABLE_PREFIX . "cms_grid
		ORDER BY title
	");
	while ($grid = $db->fetch_array($grids))
	{
		$gridoptions["$grid[gridid]"] = htmlspecialchars_uni($grid['title']);
		if (!$gridinfo)
		{
			$gridinfo = array(
				'gridid'   => $grid['gridid'],
				'gridhtml' => $grid['gridhtml'],
			);
			$selectedgridid = $gridinfo['gridid'];
		}
		else if ($vbulletin->GPC['gridid'] == $grid['gridid'])
		{
			$gridinfo = array(
				'gridid'   => $grid['gridid'],
				'gridhtml' => $grid['gridhtml'],
			);
		}
	}

	$selectedgridid = $gridinfo['gridid'];
	print_form_header('cms_admin', 'updatelayout');
	construct_hidden_code('layoutid', $layoutinfo['layoutid']);
	print_table_header($vbphrase['define_layout']);
	print_input_row($vbphrase['title'], 'title', $layoutinfo['title'], false);
	print_select_row($vbphrase['grid'], 'gridid', $gridoptions, $gridinfo['gridid']);

	$widgetbits = '';
	$widgets = $db->query_read_slave("
		SELECT widgetid, title
		FROM " . TABLE_PREFIX . "cms_widget
		ORDER BY title
	");
	while ($widget = $db->fetch_array($widgets))
	{
		$widgetbits .= "<option value=\"$widget[widgetid]\">" . $widget['title'] . "</option>";
	}
	$widgetboxheight = $db->num_rows($widgets) > 10 ? 10 : $db->num_rows($widgets);

	$widgetarray = array();
	$blocks = array();
	$blockarray = array();
	$contentblock = array(
		'layoutcolumn'     => $layoutinfo['contentcolumn'],
		'layoutindex'      => $layoutinfo['contentindex'],
		'widgettitle'      => $vbphrase['primary_content'],
		'content'          => true,
		'widgetid'         => 0,
	);
	$addcontent = true;

	if ($layoutinfo)
	{
		$_blocks = $db->query_read_slave("
			SELECT lw.*, widget.title AS widgettitle
			FROM " . TABLE_PREFIX . "cms_layoutwidget AS lw
			INNER JOIN " . TABLE_PREFIX . "cms_widget AS widget ON (widget.widgetid = lw.widgetid)
			WHERE lw.layoutid = $layoutinfo[layoutid]
			ORDER BY lw.layoutcolumn, lw.layoutindex
		");
		while ($_block = $db->fetch_array($_blocks))
		{
			if ($addcontent AND $layoutinfo['contentcolumn'] == $_block['layoutcolumn'] AND $layoutinfo['contentindex'] <= $_block['layoutindex'])
			{
				$blockarray[] = $contentblock;
				$addcontent = false;
			}
			$blockarray[] = $_block;
		}
	}

	if ($addcontent)
	{
		$blockarray[] = $contentblock;
	}

	foreach ($blockarray AS $id => $block)
	{
		$widgetarray[] = '[' . $block['layoutcolumn'] . ', ' . $block['widgetid'] . ', "' . addslashes_js($block['widgettitle'], '"') . '"]';
	}

	// remove $column[] references in html
	$gridhtml = preg_replace('#\$column\[[0-9]+\]#s', '', $gridinfo['gridhtml']);
	// &gt; turns into &lt; in rtl mode so there is no need to switch it on the button
	print_label_row('
			<table cellspacing="0" cellpadding="0" border="0">
			<tr>
				<td nowrap="nowrap">
					' . $vbphrase['widgets'] . '<br />
					<select size="' . $widgetboxheight . '" id="widgetbox">
						' . $widgetbits . '
					</select>&nbsp;
				</td>
				<td>
					<button type="button" id="addwidget">&gt;</button>&nbsp;
				</td>
			</tr></table>
		',
		'
		<script type="text/javascript" src="../clientscript/yui/dragdrop/dragdrop-min.js?v=' . SIMPLE_VERSION . '"></script>
		<script type="text/javascript" src="../clientscript/yui/animation/animation-min.js?v=' . SIMPLE_VERSION . '"></script>
		<script type="text/javascript" src="../clientscript/vbulletin_overlay.js?v=' . SIMPLE_VERSION . '"></script>
		<script type="text/javascript" src="../clientscript/vbulletin_cpcms_layout.js?v=' . SIMPLE_VERSION . '"></script>
		<script type="text/javascript">
		<!--
			var vbphrase = {
				"remove_widget"  : "' . $vbphrase['remove_widget'] . '",
				"primary_widget" : "' . $vbphrase['primary_content'] . '",
				"please_enter_layout_title" : "' . $vbphrase['please_enter_layout_title'] . '"
			};

			var widgetarray = new Array(
				' . implode(",\r\n\t\t", $widgetarray) . '
			);
		// -->
		</script>

		<div style="min-width:770px" id="layout">' . $gridhtml . '</div>
		<script type="text/javascript">
		<!--
			var LayoutManager = new vB_CMS_Layout_Config("doc3", "cms_layout", widgetarray);
		//-->
		</script>
		<div style="width:770px"></div>
	', '', top, NULL, 1);
	print_submit_row($vbphrase['save'], '');
}

// ###################################################################
if ($_POST['do'] == 'updatelayout')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'layoutid' => TYPE_UINT,
		'title'    => TYPE_NOHTML,
		'gridid'   => TYPE_UINT,
		'widgets'  => TYPE_ARRAY_ARRAY,
	));

	if ($vbulletin->GPC['layoutid'])
	{
		if (!$layoutinfo = $db->query_first_slave("
			SELECT *
			FROM " . TABLE_PREFIX . "cms_layout
			WHERE layoutid = " . $vbulletin->GPC['layoutid'])
		)
		{
			print_stop_message('invalid_x_specified', 'layoutid');
		}
	}
	else
	{
		$layoutinfo = array();
	}

	// verify valid gridid
	if (!($gridinfo = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "cms_grid
		WHERE gridid = " . $vbulletin->GPC['gridid'] . "
	")))
	{
		print_stop_message('invalid_x_specified', 'gridid');
	}

	// Verify title
	if (!$vbulletin->GPC['title'])
	{
		print_stop_message('please_complete_required_fields');
	}


	$widgets = array();
	$widgetids = array();
	foreach ($vbulletin->GPC['widgets'] AS $key => $widgetinfo)
	{
		preg_match('#^widgetlist_column(\d+)$#', $widgetinfo['xyz_column'], $matches);
		$column = $matches[1];
		if ($widgetinfo['xyz_widgetid'] == 0)
		{
			// Primary Content Block
			$contentcolumn = $column;
			$contentindex = count($widgets["$column"]) + 1;
			unset($widgetinfo["$key"]);
		}
		else
		{
			$widgetids["{$widgetinfo['xyz_widgetid']}"] = 1;
			unset($widgetinfo['xyz_column']);
			$widgets["$column"][] = $widgetinfo;
		}
	}

	//Delete any removed widgetids.
	$where = count($widgetids) ? " AND widgetid NOT IN ( "
		. implode(", ", array_keys($widgetids)) . ") "  : '';

	$db->query_write("DELETE FROM ". TABLE_PREFIX . "cms_layoutwidget WHERE
		layoutid = " . $vbulletin->GPC['layoutid'] . $where);

	/*$wcount = 0;
	if (!empty($widgetids))
	{
		list($wcount) = $db->query_first("
			SELECT COUNT(*)
			FROM " . TABLE_PREFIX . "cms_widget
			WHERE widgetid IN ( " . implode(", ", array_keys($widgetids)) . ")
		", DBARRAY_NUM);
		if (count($widgetids) != $wcount)
		{
			$wcount = 0;
		}
	}
	if (!$wcount)
	{
		print_stop_message('no_widgets_specified_for_layout');
	}*/

	$layoutdm =& datamanager_init('cms_layout', $vbulletin, ERRTYPE_CP);
	if ($layoutinfo)
	{
		$layoutdm->set_existing($layoutinfo);
	}
	$layoutdm->set('gridid', $gridinfo['gridid']);
	$layoutdm->set('title', $vbulletin->GPC['title']);
	$layoutdm->set('contentcolumn', $contentcolumn);
	$layoutdm->set('contentindex', $contentindex);
	$layoutdm->set_info('widgetdata', $widgets);

	if ($layoutdm->pre_save())
	{
		$layoutdm->save();
	}
	else
	{
		print_cp_message($layoutdm->error);
	}

	define('CP_REDIRECT', 'cms_admin.php?do=layout');
	print_stop_message('saved_layout_successfully');

}

// ###################################################################
if ($_REQUEST['do'] == 'removelayout')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'layoutid' => TYPE_UINT
	));

	$layoutinfo = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "cms_layout
		WHERE layoutid = " . $vbulletin->GPC['layoutid'] . "
	");
	if (!$layoutinfo)
	{
		print_stop_message('invalid_x_specified', 'layoutid');
	}

	if ($db->query_first("
		SELECT layoutid
		FROM " . TABLE_PREFIX . "cms_node
		WHERE layoutid = $layoutinfo[layoutid]
	"))
	{
		print_stop_message('layout_can_not_be_deleted');
	}

	print_delete_confirmation('cms_layout', $vbulletin->GPC['layoutid'], 'cms_admin', 'killlayout', 'layoutid', 0, '', 'title', 'layoutid');
}

// ###################################################################
if ($_POST['do'] == 'killlayout')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'layoutid' => TYPE_UINT,
	));

	$layoutinfo = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "cms_layout
		WHERE layoutid = " . $vbulletin->GPC['layoutid'] . "
	");

	if (!$layoutinfo)
	{
		print_stop_message('invalid_x_specified', 'layoutid');
	}

	$dataman =& datamanager_init('cms_layout', $vbulletin, ERRTYPE_CP);
	$dataman->set_existing($layoutinfo);
	$dataman->delete();

	define('CP_REDIRECT', 'cms_admin.php?do=layout');
	print_stop_message('deleted_cms_layout_successfully');
}

// ###################################################################
if ($_POST['do'] == 'gridhtml')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'gridid' => TYPE_UINT
	));

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	if (!($gridinfo = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "cms_grid
		WHERE gridid = " . $vbulletin->GPC['gridid'] . "
	")))
	{
		print_stop_message('invalid_x_specified', 'gridid');
	}

	// remove $column[] references in html
	$gridhtml = preg_replace('#\$column\[[0-9]+\]#s', '', $gridinfo['gridhtml']);

	$xml->add_tag('html', $gridhtml);
	$xml->print_xml();
}




/*Widgets=======================================================================*/


// #############################################################################

// Validate widgetid
if (in_array($_REQUEST['do'], array('editwidget', 'updatewidge', 'deletewidget', 'removewidget')) AND !$widget->isValid())
{
	print_stop_message('invalid_x_specified', 'widgetid');
}

// Get input and check for duplicate varname
if (('newwidget' == $_REQUEST['do']) OR ('updatewidget' == $_REQUEST['do']))
{
	/*
	$vbulletin->input->clean_array_gpc('p', array(
		'title' => TYPE_NOHTML,
		'description' => TYPE_NOHTML,
		'varname' => TYPE_NOHTML
	));

	// Check for duplicate varnames
	// TODO: This should be handled by the dm
	if ($vbulletin->GPC['varname'])
	{
		$duplicate_varname = $vbulletin->db->query_first("
			SELECT widgetid
			FROM " . TABLE_PREFIX . "cms_widget AS widget
			WHERE widget.varname = '" . $vbulletin->db->escape_string($vbulletin->GPC['varname']) . "'
		");

		if ($duplicate_varname)
		{
			print_stop_message('widget_varname_x_already_in_use', htmlspecialchars($vbulletin->GPC['varname']));
		}
	}
	*/
}

// Add #########################################################################
if ($_POST['do'] == 'addwidget')
{
	$widgettypes = vBCms_Types::instance()->enumerateWidgetTypes();

	print_form_header('cms_admin', 'newwidget');
	print_table_header($vbphrase['create_widget']);
	print_select_row($vbphrase['widget_type'], 'widgettype', $widgettypes);
	print_input_row($vbphrase['title'], 'title');
	//print_input_row($vbphrase['varname'], 'varname', $widgetinfo['varname'], false);
	print_textarea_row($vbphrase['description'], 'description');
	print_submit_row($vbphrase['save'], '');
}

// New #########################################################################
if ($_REQUEST['do'] == 'newwidget')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'widgettype'  => TYPE_UINT,
		'title'       => TYPE_NOHTML,
		'description' => TYPE_NOHTML
	));

	$widgetdm = new vBCms_DM_Widget();
	$widgetdm->set('widgettypeid', $vbulletin->GPC['widgettype']);
	$widgetdm->set('title', $vbulletin->GPC['title']);
	$widgetdm->set('description', $vbulletin->GPC['description']);
	//$widgetdm->set('varname', $vbulletin->GPC['varname']);

	if (!$widgetdm->save())
	{
		$errmsg = implode("\n<br /><br />", $widgetdm->getErrors());

		print_cp_message($errmsg);
	}

	define('CP_REDIRECT', 'cms_admin.php?do=widget');
	print_stop_message('saved_widget_successfully');
}

// Remove ######################################################################
if ($_POST['do'] == 'removewidget')
{
	$widget->getDM()->delete();

	define('CP_REDIRECT', 'cms_admin.php?do=widget');
	print_stop_message('deleted_widget_successfully');
}

// Delete ######################################################################
if ($_REQUEST['do'] == 'deletewidget')
{
	print_delete_confirmation('cms_widget', $vbulletin->GPC['widgetid'], 'cms_admin', 'removewidget', 'widget', 0, '', 'title', 'widgetid');
}

// Update ######################################################################
if ($_POST['do'] == 'updatewidget')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'title'       => TYPE_NOHTML,
		'description' => TYPE_NOHTML
	));

	$widgetdm = $widget->getDM();

	try
	{
		$widgetdm->set('title', $vbulletin->GPC['title']);
		$widgetdm->set('description', $vbulletin->GPC['description']);

		if (!$widgetdm->save())
		{
			print_cp_message($widgetdm->error);
		}
	}
	catch (vB_Exception $e)
	{
		print_cp_message($e->getMessage());
	}

	define('CP_REDIRECT', 'cms_admin.php?do=widget');
	print_stop_message('saved_widget_successfully');
}

// Edit  #######################################################################
if ($_REQUEST['do'] == 'editwidget')
{
	print_form_header('cms_admin', 'updatewidget');
	construct_hidden_code('widgetid', $widget->getId());
	print_table_header($vbphrase['edit_widget']);
	print_label_row($vbphrase['widget_type'], "<b>{$widget->getTypeTitle()}</b>");
	print_input_row($vbphrase['title'], 'title', $widget->getTitle(), false);
	print_textarea_row($vbphrase['description'], 'description', $widget->getDescription(), 15, 80, false);
	print_submit_row($vbphrase['save'], '');
}

// Manage ######################################################################
if ($_REQUEST['do'] == 'widget')
{
	$options = array(
		'editwidget'   => $vbphrase['edit'],
		'deletewidget' => $vbphrase['delete'],
	);

	print_form_header('cms_admin', 'addwidget');
	print_table_header($vbphrase['widgets'], 4);
	print_cells_row(array($vbphrase['widget_name'], $vbphrase['widget_type'], $vbphrase['controls'], $vbphrase['configure']), 1);

	$widgets = new vBCms_Collection_Widget();
	foreach($widgets AS $widget)
	{
		$config_url = vBCms_Route_Widget::getUrl(array('action' =>'config' ,'widget' => $widget->getID()), null, true);
		$callback = method_exists($widget, 'getConfigCallback') ? $widget->getConfigCallback() : false;
		$config_col = "<a href=\"\" id=\"config_widget_" . $widget->getID() . "\" onclick=\"return cms_show_overlay('$config_url'" .
			($callback ? ", $callback" : '' ) . " )\">$vbphrase[configure]</a>";

		// widgetid, title, description, widgettype, package
		print_cells_row(array(
			$widget->getTitle(),
			'<span class="widgettype_' . strtolower($widget->getPackage() . '_' . $widget->getClass()) . '">' . $vbphrase['widgettype_' . strtolower($widget->getPackage() . '_' . $widget->getClass())] . '</span>',
			"<span style=\"white-space:nowrap\">
				<select id=\"actions_widget_" . $widget->getId() . "\" name=\"widget" . $widget->getId() . "\" onchange=\"js_jump(" . $widget->getId() . ", this);\" class=\"bginput\">" . construct_select_options($options) . "</select>
				<input id=\"go_button_widget_" . $widget->getId() . "\" type=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_jump(" . $widget->getId() . ", this.form.widget" . $widget->getId() . ");\" class=\"button\" />
			</span>",
			$config_col
		));
	}

	print_submit_row($vbphrase['create_new_widget'], 0, 4);

	?>
	<?php
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # SVN: $Revision: 27874 $
|| ####################################################################
\*======================================================================*/
