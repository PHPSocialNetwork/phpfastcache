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
define('CVS_REVISION', '$RCSfile$ - $Revision: 58799 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('style');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_template.php');

$vbulletin->input->clean_array_gpc('r', array(
	'group'     => TYPE_INT,
	'dostyleid' => TYPE_INT,
	'dowhat'    => TYPE_NOCLEAN // Sometimes this is an array and other times it is a string .. bad, bad.
));

// redirect back to template editor if required
if ($_REQUEST['do'] == 'edit' AND $vbulletin->GPC['dowhat'] == 'templateeditor')
{
	exec_header_redirect("template.php?" . $vbulletin->session->vars['sessionurl_js'] . "&do=modify" . "&group=" . $vbulletin->GPC['group'] . "&expandset=" . $vbulletin->GPC['dostyleid']);
}

if ($_REQUEST['do'] == 'edit' AND $vbulletin->GPC['dowhat'] == 'stylevar')
{
	exec_header_redirect("stylevar.php?" . $vbulletin->session->vars['sessionurl_js'] . "&dostyleid=" . $vbulletin->GPC['dostyleid']);
}

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminstyles'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'dostyleid'	=> TYPE_INT
));
log_admin_action(iif($vbulletin->GPC['dostyleid'] != 0, "style id = " . $vbulletin->GPC['dostyleid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['style_manager'], iif($_REQUEST['do'] == 'edit' OR $_REQUEST['do'] == 'doedit', 'init_color_preview()'));

?>
<script type="text/javascript" src="../clientscript/vbulletin_cpcolorpicker.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
<?php

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}
else if ($_REQUEST['do'] == 'update')
{
	$vbulletin->nozip = true;
}

if (
	$vbulletin->GPC['dostyleid'] == -2
		OR
	$db->query_first("SELECT styleid FROM " . TABLE_PREFIX . "style WHERE type='mobile' AND styleid = " . $vbulletin->GPC['dostyleid'])
)
{
	print_stop_message('css_obsolete_mobile_styles');
}

if ($dostyleid < 1)
{
	$dostyleid = -1;
}

// ###################### Start Update Special Templates #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'dostyleid'       => TYPE_INT,
		'group'           => TYPE_STR,
		'css'             => TYPE_ARRAY,
		'stylevar'        => TYPE_ARRAY,
		'replacement'     => TYPE_ARRAY,
		'commontemplate'  => TYPE_ARRAY,
		'delete'          => TYPE_ARRAY,
		'dowhat'          => TYPE_ARRAY,
		'colorPickerType' => TYPE_INT,
		'passthru_dowhat' => TYPE_STR
	));

	if (empty($vbulletin->GPC['dostyleid']))
	{
		// probably lost due to Suhosin wiping out the variable
		print_stop_message('variables_missing_suhosin');
	}
	else if ($vbulletin->GPC['dostyleid'] == -1)
	{
		$templates = $db->query_read("
			SELECT templateid, title, template, template_un, styleid, templatetype
			FROM " . TABLE_PREFIX . "template
			WHERE styleid = -1
			AND (templatetype <> 'template' OR title IN ('" . implode("', '", $_query_common_templates) . "', '" . implode("', '", $_query_special_templates) . "'))
		");
	}
	else
	{
		$style = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "style WHERE styleid = " . $vbulletin->GPC['dostyleid']);

		if (empty($style['templatelist']))
		{
			print_stop_message('invalid_style_specified');
		}

		$templateids = implode(',', unserialize($style['templatelist']));
		if (!$templateids)
		{
			// this used to cause an SQL error, this should work as an alternative
			$templateids_query = "styleid = " . $vbulletin->GPC['dostyleid'];
		}
		else
		{
			$templateids_query = "templateid IN($templateids)";
		}
		$templates = $db->query_read("
			SELECT templateid, title, template, template_un, styleid, templatetype
			FROM " . TABLE_PREFIX . "template
			WHERE $templateids_query
			AND (templatetype <> 'template' OR title IN ('" . implode("', '", $_query_common_templates) . "', '" . implode("', '", $_query_special_templates) . "'))
		");
	}
	$template_cache = array(
		'template'    => array(),
		'css'         => array(),
		'stylevar'    => array(),
		'replacement' => array()
	);
	while ($template = $db->fetch_array($templates))
	{
		$template_cache["$template[templatetype]"]["$template[title]"] = $template;
	}
	$db->free_result($templates);

	// update templates
	if ($vbulletin->GPC['dowhat']['templates'] OR $vbulletin->GPC['dowhat']['posteditor'])
	{
		$templatequery = array();
		// Attempt to enable display_errors so that this eval actually returns something in the event of an error
		@ini_set('display_errors', true);
		foreach($vbulletin->GPC['commontemplate'] AS $templatetitle => $templatehtml)
		{
			if ($tquery = fetch_template_update_sql($templatetitle, $templatehtml, $vbulletin->GPC['dostyleid'], $vbulletin->GPC['delete']))
			{
				$templatequery[] = $tquery;
			}
		}

		if (!empty($templatequery))
		{
			foreach($templatequery AS $query)
			{
				$db->query_write($query);
			}
		}
	}

	// update stylevars
	if ($vbulletin->GPC['dowhat']['stylevars'])
	{
		build_special_templates($vbulletin->GPC['stylevar'], 'stylevar', 'stylevar');
	}

	// update css
	if ($vbulletin->GPC['dowhat']['css'])
	{
		build_special_templates($vbulletin->GPC['css'], 'css', 'css');
	}

	// update replacements
	if ($vbulletin->GPC['dowhat']['replacements'] AND is_array($vbulletin->GPC['replacement']) AND !empty($vbulletin->GPC['replacement']))
	{
		$temp = $vbulletin->GPC['replacement'];
		$vbulletin->GPC['replacement'] = array();
		foreach ($temp AS $key => $replacebits)
		{
			$vbulletin->GPC['replacement']["$replacebits[find]"] = $replacebits['replace'];
			$vbulletin->GPC['delete']['replacement']["$replacebits[find]"] = $vbulletin->GPC['delete']['replacement']["$key"];
		}
		build_special_templates($vbulletin->GPC['replacement'], 'replacement', 'replacement');
	}

	print_rebuild_style(
		$vbulletin->GPC['dostyleid'],
		iif($vbulletin->GPC['dostyleid'] == -1, $vbphrase['master_style'], $style['title']),
		$vbulletin->GPC['dowhat']['css'],
		$vbulletin->GPC['dowhat']['stylevars'],
		$vbulletin->GPC['dowhat']['replacements'],
		$vbulletin->GPC['dowhat']['posteditor']
	);

	print_cp_redirect(
		"css.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit" .
		"&dostyleid=" . $vbulletin->GPC['dostyleid'] .
		"&amp;group=" . $vbulletin->GPC['group'] .
		"&amp;dowhat=" . $vbulletin->GPC['passthru_dowhat'] .
		"&amp;colorPickerType=" . $vbulletin->GPC['colorPickerType'],
		1
	);
}

// ###################### Start Choose What to Edit #######################
if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'dostyleid' => TYPE_INT,
		'group'     => TYPE_STR,
		'dowhat'    => TYPE_STR
	));

	if ($vbulletin->GPC['dostyleid'] == 0 OR $vbulletin->GPC['dostyleid'] < -1)
	{
		$vbulletin->GPC['dostyleid'] = 1;
	}

	if (!empty($vbulletin->GPC['dowhat']))
	{
		$_REQUEST['do'] = 'doedit';
	}
	else
	{
		if ($vbulletin->GPC['dostyleid'] == -1)
		{
			$style = array('styleid' => -1, 'title' => $vbphrase['master_style']);
		}
		else
		{
			$style = $db->query_first("
				SELECT styleid, title
				FROM " . TABLE_PREFIX . "style
				WHERE styleid = " . $vbulletin->GPC['dostyleid']
			);
		}

		print_form_header('css', 'doedit', false, true, 'cpform', '90%', '', true, 'get');
		construct_hidden_code('dostyleid', $style['styleid']);
		construct_hidden_code('group', $vbulletin->GPC['group']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['fonts_colors_etc'], $style['title'], $style['styleid']));
		print_yes_row($vbphrase['all_style_options'], 'dowhat', $vbphrase['yes'], true, 'all');
		print_yes_row($vbphrase['common_templates'], 'dowhat', $vbphrase['yes'], false, 'templates');
		print_yes_row($vbphrase['main_css'], 'dowhat', $vbphrase['yes'], false, 'maincss');
		print_yes_row($vbphrase['replacement_variables'], 'dowhat', $vbphrase['yes'], false, 'replacements');
		print_yes_row($vbphrase['toolbar_menu_options'], 'dowhat', $vbphrase['yes'], false, 'posteditor');
		print_submit_row($vbphrase['go'], 0);
	}

}

// ###################### Start Edit CSS #######################
if ($_REQUEST['do'] == 'doedit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'dostyleid'       => TYPE_INT,
		'group'           => TYPE_STR,
		'dowhat'          => TYPE_STR,
		'colorPickerType' => TYPE_INT
	));

	if ($vbulletin->GPC['dostyleid'] == 0 OR $vbulletin->GPC['dostyleid'] < -1)
	{
		print_stop_message('invalid_style_specified');
	}

	// get data from styles table
	cache_styles();

	if (!isset($stylecache[$vbulletin->GPC['dostyleid']]) AND !$vbulletin->debug)
	{
		print_stop_message('invalid_style_specified');
	}

	?>
	<form action="css.php" method="get">
	<input type="hidden" name="s" value="<?php echo $vbulletin->session->vars['sessionhash']; ?>" />
	<input type="hidden" name="do" value="edit" />
	<input type="hidden" name="group" value="<?php echo htmlspecialchars_uni($vbulletin->GPC['group']); ?>" />
	<table cellpadding="0" cellspacing="0" border="0" width="90%" align="center">
	<tr valign="top">
		<td>

		<table cellpadding="4" cellspacing="1" border="0" class="tborder" width="300">
		<tr align="center">
			<td class="tcat"><b><?php echo construct_phrase($vbphrase['x_y_id_z'], $vbphrase['fonts_colors_etc'], iif($vbulletin->GPC['dostyleid'] == -1, $vbphrase['master_style'], $stylecache[$vbulletin->GPC['dostyleid']]['title']), $vbulletin->GPC['dostyleid']); ?></b></td>
		</tr>
		<tr>
			<td class="alt2" align="center">

			<select name="dostyleid" class="bginput" style="width:275px">
			<?php

			if ($vbulletin->debug)
			{
				echo "<option value=\"-1\"" . iif($vbulletin->GPC['dostyleid'] == -1, ' selected="selected"', '') . ">" . $vbphrase['master_style'] . "</option>\n";
			}
			foreach ($stylecache AS $style)
			{
				if ($style['type'] == 'mobile')
				{
					continue;
				}
				echo "<option value=\"$style[styleid]\"" . iif($style['styleid'] == $vbulletin->GPC['dostyleid'], ' selected="selected"', '') . ">" . construct_depth_mark($style['depth'], '--', '--') . " $style[title]</option>\n";
				$jsarray[] = "style[$style[styleid]] = \"" . addslashes_js($style['title'], '"') . "\";\n";
			}

			$optionselected[$vbulletin->GPC['dowhat']] = ' selected="selected"';

			?>
			</select>
			<br />
			<select name="dowhat" class="bginput" style="width:275px" onchange="this.form.submit()">
				<optgroup label="<?php echo $vbphrase['edit_fonts_colors_etc']; ?>">
					<option value="all"<?php echo $optionselected['all']; ?>><?php echo $vbphrase['all_style_options']; ?></option>
					<option value="templates"<?php echo $optionselected['templates']; ?>><?php echo $vbphrase['common_templates']; ?></option>
					<option value="stylevar"><?php echo $vbphrase['stylevars']; ?></option>
					<option value="maincss"<?php echo $optionselected['maincss']; ?>><?php echo $vbphrase['main_css']; ?></option>
					<option value="replacements"<?php echo $optionselected['replacements']; ?>><?php echo $vbphrase['replacement_variables']; ?></option>
					<option value="posteditor"<?php echo $optionselected['posteditor']; ?>><?php echo $vbphrase['toolbar_menu_options']; ?></option>
				</optgroup>
				<optgroup label="<?php echo $vbphrase['template_options']; ?>">
					<option value="templateeditor"><?php echo $vbphrase['edit_templates']; ?></option>
				</optgroup>
				<!-- <option value="<?php echo $vbulletin->GPC['dowhat']; ?>">&nbsp;</option> -->
			</select>

			</td>
		</tr>
		<tr>
			<td class="tfoot" align="center"><input type="submit" class="button" value="  <?php echo $vbphrase['go']; ?>  " /></td>
		</tr>
		</table>

		</td>
		<td align="<?php echo vB_Template_Runtime::fetchStyleVar('right'); ?>">

		<table cellpadding="4" cellspacing="1" border="0" class="tborder" width="300">
		<tr align="center">
			<td class="tcat"><b><?php echo $vbphrase['color_key']; ?></b></td>
		</tr>
		<tr>
			<td class="alt2">
			<div class="darkbg" style="margin: 4px; padding: 4px; border: 2px inset; text-align: ' . vB_Template_Runtime::fetchStyleVar('left') . '">
			<span class="col-g"><?php echo $vbphrase['template_is_unchanged_from_the_default_style']; ?></span><br />
			<span class="col-i"><?php echo $vbphrase['template_is_inherited_from_a_parent_style']; ?></span><br />
			<span class="col-c"><?php echo $vbphrase['template_is_customized_in_this_style']; ?></span>
			</div>
			</td>
		</tr>
		</table>

		</td>
	</tr>
	</table>
	</form>
	<script type="text/javascript">
	<!--
	function js_show_default_item(url, dolinks)
	{
		gotourl = "css.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=showdefault&dolinks=" + dolinks + "&" + url;
		if (dolinks==1)
		{
			wheight = 350;
		}
		else
		{
			wheight = 250;
		}
		window.open(gotourl, 'showdefault', 'resizable=yes,width=670,height=' + wheight);
	}
	var style = new Array();
	<?php echo implode('', $jsarray); ?>
	function js_show_style_info(styleid)
	{
		alert(construct_phrase("<?php echo $vbphrase['this_item_is_customized_in_the_parent_style_called_x']; ?>", style[styleid]));
	}

	<?php
	foreach (array(
		'css_value_invalid',
		'color_picker_not_ready',
	) AS $phrasename)
	{
			$JS_PHRASES[] = "\"$phrasename\" : \"" . fetch_js_safe_string($vbphrase["$phrasename"]) . "\"";
	}
	?>

	var vbphrase = {
		<?php echo implode(",\r\n\t", $JS_PHRASES) . "\r\n"; ?>
	};
	//-->
	</script>
	<?php

	if ($vbulletin->GPC['dostyleid'] == -1)
	{
		$templates = $db->query_read("
			SELECT title, template, template_un, styleid, templatetype
			FROM " . TABLE_PREFIX . "template
			WHERE styleid = -1
			AND (templatetype <> 'template' OR title IN('" . implode("', '", $_query_common_templates) . "', '" . implode("', '", $_query_special_templates) . "'))
		");
	}
	else
	{
		$templateids = implode(',', unserialize($stylecache[$vbulletin->GPC['dostyleid']]['templatelist']));
		if (!$templateids)
		{
			// this used to cause an SQL error, this should work as an alternative
			$templateids_query = "styleid = " . $vbulletin->GPC['dostyleid'];
		}
		else
		{
			$templateids_query = "templateid IN($templateids)";
		}
		$templates = $db->query_read("
			SELECT title, template, template_un, styleid, templatetype
			FROM " . TABLE_PREFIX . "template
			WHERE $templateids_query
			AND (templatetype <> 'template' OR title IN('" . implode("', '", $_query_common_templates) . "', '" . implode("', '", $_query_special_templates) . "'))
		");
	}
	$template_cache = array();
	while ($template = $db->fetch_array($templates))
	{
		$template_cache["$template[templatetype]"]["$template[title]"] = $template;
	}
	// get style options
	$stylevars = array();
	$stylevar_info = array();
	foreach($template_cache['stylevar'] AS $title => $template)
	{
		$stylevars["$title"] = $template['template'];
		$stylevar_info["$title"] = $template['styleid'];
	}
	// get css
	$css = array();
	foreach($template_cache['css'] AS $title => $template)
	{
		$css["$title"] = unserialize($template['template']);
		$css_info["$title"] = $template['styleid'];
	}
	// get replacements
	$replacement = array();
	if (is_array($template_cache['replacement']))
	{
		ksort($template_cache['replacement']);
		foreach($template_cache['replacement'] AS $title => $template)
		{
			$replacement["$title"] = $template['template'];
			$replacement_info["$title"] = $template['styleid'];
		}
	}

	$readonly = 0;

	// #############################################################################
	// start main form
	print_form_header('css', 'update', 0, 1, 'styleform');
	construct_hidden_code('dostyleid', $vbulletin->GPC['dostyleid']);
	construct_hidden_code('passthru_dowhat', $vbulletin->GPC['dowhat']);
	construct_hidden_code('group', $vbulletin->GPC['group']);

	// #############################################################################
	// build color picker if necessary
	if ($vbulletin->GPC['dowhat'] == 'all' OR $vbulletin->GPC['dowhat'] == 'css' OR $vbulletin->GPC['dowhat'] == 'maincss' OR $vbulletin->GPC['dowhat'] == 'posteditor')
	{
		$colorPicker = construct_color_picker(11);
	}
	else
	{
		$colorPicker = '';
	}

	// #############################################################################
	// COMMON TEMPLATES
	if ($vbulletin->GPC['dowhat'] == 'templates' OR $vbulletin->GPC['dowhat'] == 'all')
	{
		construct_hidden_code('dowhat[templates]', 1);
		print_table_header($vbphrase['common_templates']);
		print_common_template_row('header');
		print_common_template_row('headinclude');
		print_common_template_row('footer');
		print_table_break(' ');
	}

	// #############################################################################
	// STYLEVARS
	if ($vbulletin->GPC['dowhat'] == 'stylevars' OR $vbulletin->GPC['dowhat'] == 'all')
	{
		construct_hidden_code('dowhat[stylevars]', 1);

		print_table_header($vbphrase['sizes_and_dimensions'], 3);
			print_description_row($vbphrase['style_options_obsolete'], false, 3, fetch_row_bgclass() . ' modlasttendays', 'center');
			print_stylevar_row($vbphrase['main_table_width'],            'outertablewidth');
			print_stylevar_row($vbphrase['spacer_size'],                 'spacersize',       30, '#^\d+$#siU', 0);
			print_stylevar_row($vbphrase['outer_border_width'],          'outerborderwidth', 30, '#^\d+$#siU', 0);
			print_stylevar_row($vbphrase['inner_border_width'],          'cellspacing',      30, '#^\d+$#siU', 0);
			print_stylevar_row($vbphrase['table_cell_padding'],          'cellpadding',      30, '#^\d+$#siU', 0);

			print_stylevar_row($vbphrase['form_spacer_size'],            'formspacer');
			print_stylevar_row($vbphrase['form_width'],                  'formwidth');
			print_stylevar_row($vbphrase['form_width_usercp'],           'formwidth_usercp');
			print_stylevar_row($vbphrase['message_width'],               'messagewidth');
			print_stylevar_row($vbphrase['message_width_usercp'],        'messagewidth_usercp');
			print_stylevar_row($vbphrase['code_block_width'],            'codeblockwidth');

			($hook = vBulletinHook::fetch_hook('stylevar_edit_sizes')) ? eval($hook) : false;

		print_table_header($vbphrase['image_paths'], 3);
			print_description_row($vbphrase['style_options_obsolete'], false, 3, fetch_row_bgclass() . ' modlasttendays', 'center');
			print_stylevar_row($vbphrase['title_image'],                 'titleimage');
			print_stylevar_row($vbphrase['buttons_folder'],              'imgdir_button');
			print_stylevar_row($vbphrase['statusicon_folder'],           'imgdir_statusicon');
			print_stylevar_row($vbphrase['attachment_icons_folder'],     'imgdir_attach');
			print_stylevar_row($vbphrase['misc_images_folder'],          'imgdir_misc');
			print_stylevar_row($vbphrase['text_editor_controls_folder'], 'imgdir_editor');
			print_stylevar_row($vbphrase['poll_images_folder'],          'imgdir_poll');
			print_stylevar_row($vbphrase['rating_images_folder'],        'imgdir_rating');
			print_stylevar_row($vbphrase['reputation_images_folder'],    'imgdir_reputation');

			($hook = vBulletinHook::fetch_hook('stylevar_edit_imagepaths')) ? eval($hook) : false;

		print_table_header($vbphrase['miscellaneous'], 3);
			print_description_row($vbphrase['style_options_obsolete'], false, 3, fetch_row_bgclass() . ' modlasttendays', 'center');
			print_stylevar_row($vbphrase['html_doctype'],                'htmldoctype');

			($hook = vBulletinHook::fetch_hook('stylevar_edit_misc')) ? eval($hook) : false;

		print_table_break(' ');
	}

	// #############################################################################
	// MAIN CSS
	if ($vbulletin->GPC['dowhat'] == 'maincss' OR $vbulletin->GPC['dowhat'] == 'css')
	{
		construct_hidden_code('dowhat[css]', 1);

		if (is_browser('mozilla'))
		{
			?>
			<script type="text/javascript">
			window.onresize = redo_fieldset;
			var target_fieldsets = new Array();
			var z = 0;
			function redo_fieldset()
			{
				for (m = 0; m < z; m++)
				{
					if (typeof(target_fieldsets[m]) != "undefined")
					{
						reflow_fieldset(target_fieldsets[m], false);
					}
				}
			}
			function reflow_fieldset(set, add)
			{
				if (add)
				{
					target_fieldsets[z++] = set;
				}
				document.getElementById('desc_' + set).style.width = (document.getElementById('extra_' + set).scrollWidth -20)+ 'px';
			}
			</script>
			<?php
		}
		print_table_header($vbphrase['obsolete'], 1);
		print_description_row($vbphrase['css_file_obsolete']);
		print_table_break(' ');

		print_css_row($vbphrase['body'], $vbphrase['body_desc'], 'body', 1);
		print_css_row($vbphrase['page_background'], $vbphrase['page_background_desc'], '.page', 1);
		print_css_row('<td>, <th>, <p>, <li>', $vbphrase['text_desc'], 'td, th, p, li', 0);
		print_css_row($vbphrase['table_border'], $vbphrase['table_border_desc'], '.tborder', 0);
		print_css_row($vbphrase['category_strips'], $vbphrase['category_strips_desc'], '.tcat', 1);
		print_css_row($vbphrase['table_header'], $vbphrase['table_header_desc'], '.thead', 1);
		print_css_row($vbphrase['table_footer'], $vbphrase['table_footer_desc'], '.tfoot', 1);
		print_css_row($vbphrase['first_alternating_color'], $vbphrase['first_alternating_color_desc'], '.alt1, .alt1Active', 1);
		print_css_row($vbphrase['second_alternating_color'], $vbphrase['second_alternating_color_desc'], '.alt2, .alt2Active', 1);
		print_css_row(construct_phrase($vbphrase['wysiwyg_editor_style'], $vbphrase['first_alternating_color'], $vbphrase['input_fields']), $vbphrase['wysiwyg_editor_style_desc'], '.wysiwyg', 1);
		print_css_row($vbphrase['input_fields'], $vbphrase['input_fields_desc'], 'textarea, .bginput', 0);
		print_css_row($vbphrase['buttons'], $vbphrase['buttons_desc'], '.button', 0);
		print_css_row($vbphrase['menus'], $vbphrase['menus_desc'], 'select', 0);
		print_css_row($vbphrase['small_font'], $vbphrase['small_font_desc'], '.smallfont', 0);
		print_css_row($vbphrase['time_color'], $vbphrase['time_color_desc'], '.time', 0);
		print_css_row($vbphrase['navbar_text'], $vbphrase['navbar_text_desc'], '.navbar', 1);
		print_css_row($vbphrase['highlighted_font'], $vbphrase['highlighted_font_desc'], '.highlight', 0);
		print_css_row($vbphrase['inline_mod_highlight'], $vbphrase['inline_mod_highlight_desc'], '.inlinemod', 1);

		// new ones
		print_css_row($vbphrase['panel_surround'], $vbphrase['panel_surround_desc'], '.panelsurround', 0);
		print_css_row($vbphrase['panel'], $vbphrase['panel_desc'], '.panel', 1);
		print_css_row('<legend>', $vbphrase['legend_desc'], 'legend', 0);

		print_css_row($vbphrase['popup_menu_control'], $vbphrase['popup_menu_control_desc'], '.vbmenu_control', 1);
		print_css_row($vbphrase['popup_menu_body'], $vbphrase['popup_menu_body_desc'], '.vbmenu_popup', 0);
		print_css_row($vbphrase['popup_menu_option'], $vbphrase['popup_menu_option_desc'], '.vbmenu_option', 1);
		print_css_row($vbphrase['popup_menu_hilite'], $vbphrase['popup_menu_hilite_desc'], '.vbmenu_hilite', 1);

		($hook = vBulletinHook::fetch_hook('css_edit')) ? eval($hook) : false;

		// forum jump css
		print_column_style_code(array('width: 50%', 'width: 50%'));
		print_table_header($vbphrase['forum_jump_menu'], 2);

		$jumpbits = array(construct_forumjump_css_row($vbphrase['selected_item'], '.fjsel'));
		for ($depth = 0; $depth < 5; $depth++)
		{
			$jumpbits[] = construct_forumjump_css_row(construct_phrase($vbphrase['depth_x_items'], $depth), ".fjdpth$depth");
		}

		$i = 0;
		while ($i < sizeof($jumpbits))
		{
			print_label_row($jumpbits[$i++], $jumpbits[$i++], 'alt2');
		}
		print_table_break(' ');

		// additional css
		print_table_header($vbphrase['additional_css']);
		print_textarea_row($vbphrase['additional_css_description'], 'css[EXTRA][all]', $css['EXTRA']['all'], 10, 80, true, false, 'ltr', fetch_inherited_color($css_info['EXTRA'], $vbulletin->GPC['dostyleid']) . '" style="font:9pt \'courier new\', monospace');
		$revertcode = construct_revert_code($css_info['EXTRA'], 'css', 'EXTRA');
		if ($revertcode['info'])
		{
			print_description_row("<span style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">$revertcode[revertcode]</span>$revertcode[info]", 0, 2, 'tfoot" align="center');
		}
		print_textarea_row('', 'css[EXTRA2][all]', $css['EXTRA2']['all'], 10, 80, true, false, 'ltr', fetch_inherited_color($css_info['EXTRA2'], $vbulletin->GPC['dostyleid']) . '" style="font:9pt \'courier new\', monospace');
		$revertcode = construct_revert_code($css_info['EXTRA2'], 'css', 'EXTRA2');
		if ($revertcode['info'])
		{
			print_description_row("<span style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">$revertcode[revertcode]</span>$revertcode[info]", 0, 2, 'tfoot" align="center');
		}

		print_table_break(' ');
	}

	// #############################################################################
	// POST EDITOR
	if ($vbulletin->GPC['dowhat'] == 'posteditor' OR $vbulletin->GPC['dowhat'] == 'all')
	{
		construct_hidden_code('dowhat[posteditor]', 1);
		/* disabled for now with the new editor until we decide we want to make the new editor customizable
		print_table_header($vbphrase['text_editor_control_styles']);
		print_description_row($vbphrase['text_editor_control_desc']);

		$out = array();
		foreach ($_query_special_templates AS $varname)
		{
			if (substr($varname, 0, 13) == 'editor_styles')
			{
				//$out[] = construct_posteditor_style_code(ucwords(str_replace('_', ' ', substr($varname, 13))), $varname);
				$out[] = construct_posteditor_style_code($vbphrase["$varname"], $varname);
			}
		}
		$i = 0;
		while ($i < sizeof($out))
		{
			print_label_row($out[$i++], $out[$i++], 'alt2');
		}

		print_table_break(' ');
		*/
		print_table_header($vbphrase['toolbar_menu_options']);
		print_description_row($vbphrase['bbcode_pulldown_menu_desc']);
		print_label_row(
			construct_edit_menu_code($vbphrase['available_fonts'], 'editor_jsoptions_font'),
			construct_edit_menu_code($vbphrase['available_sizes'], 'editor_jsoptions_size')
		);

		print_table_break(' ');
	}

	// #############################################################################
	// REPLACEMENT VARS
	if ($vbulletin->GPC['dowhat'] == 'replacements' OR $vbulletin->GPC['dowhat'] == 'all')
	{
		construct_hidden_code('dowhat[replacements]', 1);
		if (sizeof($replacement) > 0)
		{
			print_table_header($vbphrase['replacement_variables'], 3);
			print_cells_row(array($vbphrase['search_for_text'], $vbphrase['replace_with_text'], ''), 1);
			foreach($replacement AS $findword => $replaceword)
			{
				print_replacement_row($findword, $replaceword);
			}
		}
		else

		{
			print_description_row($vbphrase['no_replacements_defined']);
		}
		print_table_break("<center>".
		construct_link_code($vbphrase['add_new_replacement_variable'], "replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=add&amp;dostyleid=" . $vbulletin->GPC['dostyleid']).
		"</center>");
	}

	if ($vbulletin->GPC['dowhat'] == 'maincss' OR $vbulletin->GPC['dowhat'] == 'css')
	{
		$footerhtml = '';
	}
	else
	{
		$footerhtml = '
		<input type="submit" class="button" value="' . $vbphrase['save'] . '" accesskey="s" tabindex="1" />
		<input type="reset" class="button" value="' . $vbphrase['reset'] . '" accesskey="r" tabindex="1" onclick="this.form.reset(); init_color_preview(); return false;" />
		';
	}

	print_table_footer(2, $footerhtml);
	echo $colorPicker;

	?>
	<script type="text/javascript">
	<!--

	var bburl = "<?php echo $vbulletin->options['bburl']; ?>/";
	var cpstylefolder = "<?php echo $vbulletin->options['cpstylefolder']; ?>";
	var numColors = <?php echo intval($numcolors); ?>;
	var colorPickerWidth = <?php echo intval($colorPickerWidth); ?>;
	var colorPickerType = <?php echo intval($colorPickerType); ?>;

	//-->
	</script>
	<?php
}

// ###################### Start Show Default CSS #######################
if ($_REQUEST['do'] == 'showdefault')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'item' => TYPE_STR
	));

	$readonly = 1;

	$template = $db->query_first("
		SELECT title, template
		FROM " . TABLE_PREFIX . "template
		WHERE title = '" . $db->escape_string($vbulletin->GPC['item']) . "'
			AND styleid = -1
			AND templatetype = 'stylevar'
	");
	$css["$template[title]"] = unserialize($template['template']);
	$css["$template[title]"]['styleid'] = -1;

	print_form_header('', '');
	print_css_row($title.' (default)', $vbulletin->GPC['item'], $dolinks, 0);
	print_table_footer(2, '<input type="button" class="button" value="' . $vbphrase['close'] . '" onclick="self.close();" tabindex="1" />');
}

// ###################### Start List StyleVar Colors #######################
if ($_REQUEST['do'] == 'stylevar-colors')
{
	// use 'dostyleid'from GPC

	if ($vbulletin->GPC['dostyleid'] != 0 AND $style = $db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "style
		WHERE styleid = " . $vbulletin->GPC['dostyleid']
	))
	{
		print_form_header('', '');

		foreach (unserialize($style['csscolors']) AS $colorname => $colorvalue)
		{
			if (preg_match('#^[a-z0-9_]+_hex$#siU', $colorname))
			{
				echo "<tr><td class=\"" . fetch_row_bgclass() . "\">$colorname</td><td style=\"background-color:#$colorvalue\" title=\"#$colorvalue\">#$colorvalue</td></tr>";
			}
		}

		print_table_footer();
	}
	else
	{
		// fail.
		$_REQUEST['do'] = 'modify';
	}
}

// ###################### Start List styles #######################
if ($_REQUEST['do'] == 'modify')
{
	print_form_header('css', 'edit');
	print_table_header($vbphrase['edit_styles']);
	if ($vbulletin->debug)
	{
		print_label_row(
			'<b>' . $vbphrase['master_style'] . '</b>',
			construct_link_code($vbphrase['edit'], "css.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;dostyleid=-1") .
			construct_link_code($vbphrase['templates'], "template.php?" . $vbulletin->session->vars['sessionurl'] . "expandset=$style[styleid]")
		);
		$depthmark = '--';
	}
	else
	{
		$dethmark = '';
	}
	cache_styles();
	foreach ($stylecache AS $style)
	{
		if ($style['type'] == 'mobile')
		{
			continue;
		}
		print_label_row(
			construct_depth_mark($style['depth'], '--', $depthmark) . " <b>$style[title]</b>",
			construct_link_code($vbphrase['edit'], "css.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;dostyleid=$style[styleid]") .
			construct_link_code($vbphrase['templates'], "template.php?" . $vbulletin->session->vars['sessionurl'] . "expandset=$style[styleid]") .
			construct_link_code($vbphrase['settings'], "template.php?" . $vbulletin->session->vars['sessionurl'] . "do=editstyle&amp;dostyleid=$style[styleid]")
		);
	}
	print_table_footer();
}

if ($_REQUEST['do'] == 'moo')
{
	unset($vbulletin->debug);
	function print_moo($l, $r)
	{
		print_label_row(htmlspecialchars($l), "<span class=\"smallfont\">$r</span>");
	}

	print_form_header('','');
		print_moo($vbphrase['body'], $vbphrase['body_desc'], 'body', 1);
		print_moo($vbphrase['page_background'], $vbphrase['page_background_desc'], '.page', 1);
		print_moo('<td>, <th>, <p>, <li>', $vbphrase['text_desc'], 'td, th, p, li', 0);
		print_moo($vbphrase['table_border'], $vbphrase['table_border_desc'], '.tborder', 0);
		print_moo($vbphrase['category_strips'], $vbphrase['category_strips_desc'], '.tcat', 1);
		print_moo($vbphrase['table_header'], $vbphrase['table_header_desc'], '.thead', 1);
		print_moo($vbphrase['table_footer'], $vbphrase['table_footer_desc'], '.tfoot', 1);
		print_moo($vbphrase['first_alternating_color'], $vbphrase['first_alternating_color_desc'], '.alt1, .alt1Active', 1);
		print_moo($vbphrase['second_alternating_color'], $vbphrase['second_alternating_color_desc'], '.alt2, .alt2Active', 1);
		print_moo(construct_phrase($vbphrase['wysiwyg_editor_style'], $vbphrase['second_alternating_color'], $vbphrase['input_fields']), $vbphrase['wysiwyg_editor_style_desc'], '.wysiwyg', 1);
		print_moo($vbphrase['input_fields'], $vbphrase['input_fields_desc'], 'textarea, .bginput', 0);
		print_moo($vbphrase['buttons'], $vbphrase['buttons_desc'], '.button', 0);
		print_moo($vbphrase['menus'], $vbphrase['menus_desc'], 'select', 0);
		print_moo($vbphrase['small_font'], $vbphrase['small_font_desc'], '.smallfont', 0);
		print_moo($vbphrase['time_color'], $vbphrase['time_color_desc'], '.time', 0);
		print_moo($vbphrase['navbar_text'], $vbphrase['navbar_text_desc'], '.navbar', 1);
		print_moo($vbphrase['highlighted_font'], $vbphrase['highlighted_font_desc'], '.highlight', 0);

		// new ones
		print_moo($vbphrase['panel_surround'], $vbphrase['panel_surround_desc'], '.panelsurround', 0);
		print_moo($vbphrase['panel'], $vbphrase['panel_desc'], '.panel', 1);
		print_moo('<legend>', $vbphrase['legend_desc'], 'legend', 0);

		print_moo($vbphrase['popup_menu_control'], $vbphrase['popup_menu_control_desc'], '.vbmenu_control', 1);
		print_moo($vbphrase['popup_menu_body'], $vbphrase['popup_menu_body_desc'], '.vbmenu_popup', 0);
		print_moo($vbphrase['popup_menu_option'], $vbphrase['popup_menu_option_desc'], '.vbmenu_option', 1);
		print_moo($vbphrase['popup_menu_hilite'], $vbphrase['popup_menu_hilite_desc'], '.vbmenu_hilite', 1);
	print_table_footer();
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 58799 $
|| ####################################################################
\*======================================================================*/
?>
