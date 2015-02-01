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
define('CVS_REVISION', '$RCSfile$ - $Revision: 57655 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('bbcode');
$specialtemplates = array('bbcodecache');

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_misc.php');
require_once(DIR . '/includes/class_bbcode.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminbbcodes'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'bbcodeid' 	=> TYPE_INT
));
log_admin_action(iif($vbulletin->GPC['bbcodeid'] != 0, "bbcode id = " . $vbulletin->GPC['bbcodeid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if ($_REQUEST['do'] != 'previewbbcode')
{
	print_cp_header($vbphrase['bb_code_manager']);
}
else
{
	print_cp_header();
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ########################################### ADD #####################################################

if ($_REQUEST['do'] == 'add')
{
	print_form_header('bbcode', 'insert');
	print_table_header($vbphrase['add_new_bb_code']);
	print_input_row($vbphrase['title'], 'title');
	print_input_row($vbphrase['bb_code_tag_name'], 'bbcodetag');
	print_textarea_row($vbphrase['bb_code_replacement_desc'], 'bbcodereplacement', '', 5, 60);
	print_input_row($vbphrase['example'], 'bbcodeexample');
	print_textarea_row($vbphrase['description'], 'bbcodeexplanation', '', 10, 60);
	print_yes_no_row($vbphrase['use_option'], 'twoparams', 0);
	print_input_row($vbphrase['button_image_desc'], 'buttonimage', '');
	print_yes_no_row($vbphrase['remove_tag_if_empty'], 'options[strip_empty]', 1);
	print_yes_no_row($vbphrase['disable_bbcode_in_bbcode'], 'options[stop_parse]', 0);
	print_yes_no_row($vbphrase['disable_smilies_in_bbcode'], 'options[disable_smilies]', 0);
	print_yes_no_row($vbphrase['disable_wordwrap_in_bbcode'], 'options[disable_wordwrap]', 0);
	print_yes_no_row($vbphrase['disable_urlconversion_in_bbcode'], 'options[disable_urlconversion]', 0);
	print_submit_row($vbphrase['save']);

	print_form_header('', '');
	print_description_row('<span class="smallfont">' .$vbphrase['bb_code_explanations']. '</span>');
	print_table_footer();
}

if ($_POST['do'] == 'doupdate' OR $_POST['do'] == 'insert')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'bbcodeid'          => TYPE_INT,
		'title'             => TYPE_STR,
		'bbcodetag'         => TYPE_STR,
		'bbcodereplacement' => TYPE_STR,
		'bbcodeexample'     => TYPE_STR,
		'bbcodeexplanation' => TYPE_STR,
		'twoparams'         => TYPE_BOOL,
		'buttonimage'       => TYPE_STR,
		'options'           => TYPE_ARRAY_BOOL,
		'continue'          => TYPE_BOOL,
	));

	if (!$vbulletin->GPC['bbcodetag'] OR !$vbulletin->GPC['bbcodereplacement'] OR !$vbulletin->GPC['bbcodeexample'])
	{
		print_stop_message('please_complete_required_fields');
	}

	if (!$vbulletin->GPC['continue'])
	{
		$warnings = array();
		if (preg_match('#=(\'|){(option|param)}\\1#si', $vbulletin->GPC['bbcodereplacement'], $matches))
		{
			$match = htmlspecialchars_uni($matches[0]);
			$warnings[] = str_replace($match, '<strong>' . $match . '</strong>', htmlspecialchars_uni($vbulletin->GPC['bbcodereplacement']));
		}

		if (!empty($warnings))
		{
			print_form_header('bbcode', $_POST['do'], 0, 1, '', '75%');
			construct_hidden_code('bbcodeid', $vbulletin->GPC['bbcodeid']);
			construct_hidden_code('title', $vbulletin->GPC['title']);
			construct_hidden_code('bbcodetag', $vbulletin->GPC['bbcodetag']);
			construct_hidden_code('bbcodereplacement', $vbulletin->GPC['bbcodereplacement']);
			construct_hidden_code('bbcodeexample', $vbulletin->GPC['bbcodeexample']);
			construct_hidden_code('bbcodeexplanation', $vbulletin->GPC['bbcodeexplanation']);
			construct_hidden_code('twoparams', $vbulletin->GPC['twoparams']);
			construct_hidden_code('buttonimage', $vbulletin->GPC['buttonimage']);
			construct_hidden_code('continue', 1);
			foreach($vbulletin->GPC['options'] AS $option => $value)
			{
				construct_hidden_code('options[' . htmlspecialchars_uni($option) . ']', intval($value));
			}
			print_table_header($vbphrase['vbulletin_message']);
			print_description_row(construct_phrase($vbphrase['bbcode_param_warning'], '<ul><li>' . implode("</li><li>", $warnings) . '</li></ul>'));
			print_submit_row($vbphrase['continue'], 0, 2, $vbphrase['go_back']);
			print_cp_footer();
			exit;
		}
	}
}

// ############################################## INSERT #########################################

if ($_POST['do'] == 'insert')
{
	if ($db->query_first("SELECT * FROM " . TABLE_PREFIX . "bbcode WHERE bbcodetag = '" . $db->escape_string($vbulletin->GPC['bbcodetag']) . "' AND twoparams = " . $vbulletin->GPC['twoparams']))
	{
		print_stop_message('there_is_already_bb_code_named_x', htmlspecialchars_uni($vbulletin->GPC['bbcodetag']));
	}
	else
	{
		// fetch all tags, and make sure we can't redefine an existing, built-in code
		$tags = fetch_tag_list('', true);
		if (
			($vbulletin->GPC['twoparams'] AND isset($tags['option'][$vbulletin->GPC['bbcodetag']]))
				OR
			(!$vbulletin->GPC['twoparams'] AND isset($tags['no_option'][$vbulletin->GPC['bbcodetag']]))
				OR
			strtolower($vbulletin->GPC['bbcodetag']) == 'relpath'
		)
		{
			print_stop_message('there_is_already_bb_code_named_x', htmlspecialchars_uni($vbulletin->GPC['bbcodetag']));
		}
	}

	$vbulletin->GPC['bbcodereplacement'] = str_replace('%', '%%', $vbulletin->GPC['bbcodereplacement']);
	$vbulletin->GPC['bbcodereplacement'] = str_replace('{param}', '%1$s', $vbulletin->GPC['bbcodereplacement']);
	if ($vbulletin->GPC['twoparams'])
	{
		$vbulletin->GPC['bbcodereplacement'] = str_replace('{option}', '%2$s', $vbulletin->GPC['bbcodereplacement']);
	}
	$vbulletin->GPC['bbcodereplacement'] = str_replace('{relpath}', '[relpath][/relpath]', $vbulletin->GPC['bbcodereplacement']);

	/*insert query*/
	$db->query_write("
		INSERT INTO " . TABLE_PREFIX . "bbcode
			(bbcodetag, bbcodereplacement, bbcodeexample, bbcodeexplanation, twoparams, title, buttonimage, options)
		VALUES
			('" . $db->escape_string($vbulletin->GPC['bbcodetag']) . "',
			'" . $db->escape_string($vbulletin->GPC['bbcodereplacement']) . "',
			'" . $db->escape_string($vbulletin->GPC['bbcodeexample']) . "',
			'" . $db->escape_string($vbulletin->GPC['bbcodeexplanation']) . "',
			'" . intval($vbulletin->GPC['twoparams']) . "',
			'" . $db->escape_string($vbulletin->GPC['title']) . "',
			'" . $db->escape_string($vbulletin->GPC['buttonimage']) . "',
			" . convert_array_to_bits($vbulletin->GPC['options'], $vbulletin->bf_misc['bbcodeoptions']) . " )
	");

	build_bbcode_cache();

	define('CP_REDIRECT', 'bbcode.php?do=modify');
	print_stop_message('saved_bb_code_x_successfully', "[" . $vbulletin->GPC['bbcodetag'] . "]");
}

// ##################################### EDIT ####################################

if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'bbcodeid' 	=> TYPE_INT
	));

	$_bbcode = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "bbcode WHERE bbcodeid = " . $vbulletin->GPC['bbcodeid']);

	$_bbcode['bbcodereplacement'] = str_replace('%1$s', '{param}', $_bbcode['bbcodereplacement']);
	if($_bbcode['twoparams'])
	{
		$_bbcode['bbcodereplacement'] = str_replace('%2$s', '{option}', $_bbcode['bbcodereplacement']);
	}
	$_bbcode['bbcodereplacement'] = str_replace('[relpath][/relpath]', '{relpath}', $_bbcode['bbcodereplacement']);
	$_bbcode['bbcodereplacement'] = str_replace('%%', '%', $_bbcode['bbcodereplacement']);

	print_form_header('bbcode', 'doupdate');
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['bb_code'], $_bbcode['bbcodetag'], $_bbcode['bbcodeid']), 2, 0);
	construct_hidden_code('bbcodeid', $vbulletin->GPC['bbcodeid']);
	print_input_row($vbphrase['title'], 'title', $_bbcode['title']);
	print_input_row($vbphrase['bb_code_tag_name'], 'bbcodetag', $_bbcode['bbcodetag']);
	print_textarea_row($vbphrase['bb_code_replacement_desc'], 'bbcodereplacement', $_bbcode['bbcodereplacement'], 5, 60);
	print_input_row($vbphrase['example'], 'bbcodeexample', $_bbcode['bbcodeexample']);
	print_textarea_row($vbphrase['description'], 'bbcodeexplanation', $_bbcode['bbcodeexplanation'], 10, 60);
	print_yes_no_row($vbphrase['use_option'], 'twoparams', $_bbcode['twoparams']);
	print_input_row($vbphrase['button_image_desc'], 'buttonimage', $_bbcode['buttonimage']);
	print_yes_no_row($vbphrase['remove_tag_if_empty'], 'options[strip_empty]', (intval($_bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['strip_empty']) ? 1 : 0 );
	print_yes_no_row($vbphrase['disable_bbcode_in_bbcode'], 'options[stop_parse]', (intval($_bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['stop_parse']) ? 1 : 0 );
	print_yes_no_row($vbphrase['disable_smilies_in_bbcode'], 'options[disable_smilies]', (intval($_bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['disable_smilies']) ? 1 : 0);
	print_yes_no_row($vbphrase['disable_wordwrap_in_bbcode'], 'options[disable_wordwrap]', (intval($_bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['disable_wordwrap']) ? 1 : 0);
	print_yes_no_row($vbphrase['disable_urlconversion_in_bbcode'], 'options[disable_urlconversion]', (intval($_bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['disable_urlconversion']) ? 1 : 0);
	print_submit_row($vbphrase['save']);

	print_form_header('', '');
	print_description_row('<span class="smallfont">' .$vbphrase['bb_code_explanations']. '</span>');
	print_table_footer();
}

// ##################################### UPDATE ####################################

if ($_POST['do'] == 'doupdate')
{
	if ($db->query_first("SELECT * FROM " . TABLE_PREFIX . "bbcode WHERE (bbcodetag = '" . $db->escape_string($vbulletin->GPC['bbcodetag']) . "' AND twoparams = " . $vbulletin->GPC['twoparams'] . ") AND bbcodeid <>  " . $vbulletin->GPC['bbcodeid']))
	{
		print_stop_message('there_is_already_bb_code_named_x', htmlspecialchars_uni($vbulletin->GPC['bbcodetag']));
	}
	else
	{
		// fetch all tags, and make sure we can't redefine an existing, built-in code
		$tags = fetch_tag_list('', true);
		if (($vbulletin->GPC['twoparams'] AND isset($tags['option'][$vbulletin->GPC['bbcodetag']])) OR
			(!$vbulletin->GPC['twoparams'] AND isset($tags['no_option'][$vbulletin->GPC['bbcodetag']])))
		{
			print_stop_message('there_is_already_bb_code_named_x', htmlspecialchars_uni($vbulletin->GPC['bbcodetag']));
		}
	}

	$vbulletin->GPC['bbcodereplacement'] = str_replace('%', '%%', $vbulletin->GPC['bbcodereplacement']);
	$vbulletin->GPC['bbcodereplacement'] = str_replace('{param}', '%1$s', $vbulletin->GPC['bbcodereplacement']);
	if ($vbulletin->GPC['twoparams'])
	{
		$vbulletin->GPC['bbcodereplacement'] = str_replace('{option}', '%2$s', $vbulletin->GPC['bbcodereplacement']);
	}
	$vbulletin->GPC['bbcodereplacement'] = str_replace('{relpath}', '[relpath][/relpath]', $vbulletin->GPC['bbcodereplacement']);

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "bbcode SET
			title = '" . $db->escape_string($vbulletin->GPC['title']) . "',
			bbcodetag = '" . $db->escape_string($vbulletin->GPC['bbcodetag']) . "',
			bbcodereplacement = '" . $db->escape_string($vbulletin->GPC['bbcodereplacement']) . "',
			bbcodeexample = '" . $db->escape_string($vbulletin->GPC['bbcodeexample']) . "',
			bbcodeexplanation = '" . $db->escape_string($vbulletin->GPC['bbcodeexplanation']) . "',
			twoparams = '" . $db->escape_string($vbulletin->GPC['twoparams']) . "',
			buttonimage = '" . $db->escape_string($vbulletin->GPC['buttonimage']) . "',
			options = " . convert_array_to_bits($vbulletin->GPC['options'], $vbulletin->bf_misc['bbcodeoptions']) . "
		WHERE bbcodeid = " . $vbulletin->GPC['bbcodeid']
	);

	build_bbcode_cache();

	define('CP_REDIRECT', 'bbcode.php?do=modify');
	print_stop_message('saved_bb_code_x_successfully', "[" . $vbulletin->GPC['bbcodetag'] . "]");
}

// ####################################### REMOVE #####################################

if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'bbcodeid' => TYPE_INT
	));

	print_delete_confirmation('bbcode', $vbulletin->GPC['bbcodeid'], 'bbcode', 'kill', 'bb_code');
}

// ######################################## KILL #####################################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'bbcodeid' => TYPE_INT
	));

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "bbcode WHERE bbcodeid = " . $vbulletin->GPC['bbcodeid']);
	build_bbcode_cache();

	$_REQUEST['do'] = 'modify';
}

// ######################################### TEST ######################################

if ($_POST['do'] == 'test')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'text' => TYPE_STR
	));

	$parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
	$parsed_code = $parser->do_parse($vbulletin->GPC['text'], false, false, true, false, true, false, null, false, false);

	print_form_header('bbcode', 'test');
	print_table_header($vbphrase['test_your_bb_code']);
	print_label_row($vbphrase['this_is_how_your_test_appeard_after_bb_code_formatting'], '<table border="0" cellspacing="0" cellpadding="4" width="100%" class="tborder"><tr class="alt2"><td>' . iif(!empty($parsed_code), $parsed_code, '<i>' . $vbphrase['n_a'] . '</i>') . '</td></tr></table>');
	print_textarea_row($vbphrase['enter_text_with_bb_code'], 'text', $vbulletin->GPC['text'], 15, 60);
	print_submit_row($vbphrase['go']);

	$donetest = 1;
	$_REQUEST['do'] = 'modify';
}

// ########################################################################
if ($_REQUEST['do'] == 'previewbbcode')
{
	define('NO_CP_COPYRIGHT', true);

	$vbulletin->input->clean_array_gpc('r', array(
		'bbcodeid' => TYPE_UINT
	));

	if ($bbcode = $vbulletin->db->query_first("SELECT * FROM " . TABLE_PREFIX . "bbcode WHERE bbcodeid = " . $vbulletin->GPC['bbcodeid']))
	{
		$parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
		$parsed_code = $parser->do_parse($bbcode['bbcodeexample'], false, false, true, false, true, false, null, false, false);

		echo $parsed_code;
	}
}

// ####################################### MODIFY #####################################
if ($_REQUEST['do'] == 'modify')
{
	$parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	$bbcodes = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "bbcode");

	print_form_header('bbcode', 'add');
	print_table_header($vbphrase['bb_code_manager'], 6);
	print_cells_row(array($vbphrase['title'], $vbphrase['bb_code'], $vbphrase['html'], $vbphrase['replacement'], $vbphrase['button_image'], $vbphrase['controls']), 1, '', -5);

	while ($bbcode = $db->fetch_array($bbcodes))
	{
		$class = fetch_row_bgclass();
		$altclass = iif($class == 'alt1', 'alt2', 'alt1');

		$parsed_code = $parser->do_parse($bbcode['bbcodeexample'], false, false, true, false, true, false, null, false, false);

		$cell = array(
			"<b>$bbcode[title]</b>",
			"<div class=\"$altclass\" style=\"padding:2px; border:solid 1px; width:200px; height:75px; overflow:auto\"><span class=\"smallfont\">" . htmlspecialchars_uni($bbcode['bbcodeexample']) . '</span></div>',
			"<div class=\"$altclass\" style=\"padding:2px; border:solid 1px; width:200px; height:75px; overflow:auto\"><span class=\"smallfont\">" . htmlspecialchars_uni($parsed_code) . '</span></div>',
			'<iframe src="bbcode.php?do=previewbbcode&amp;bbcodeid=' . $bbcode['bbcodeid'] . '" style="width:200px; height:75px;"></iframe>'
		);

		if ($bbcode['buttonimage'])
		{
			$src = $bbcode['buttonimage'];
			if (!preg_match('#^[a-z]+://#i', $src) AND $src{0} != '/')
			{
				$src = "../$src";
			}

			$cell[] = "<img style=\"background:buttonface; border:solid 1px highlight\" src=\"$src\" alt=\"\" />";
		}
		else
		{
			$cell[] = $vbphrase['n_a'];
		}
		$cell[] = construct_link_code($vbphrase['edit'], "bbcode.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;bbcodeid=$bbcode[bbcodeid]") . construct_link_code($vbphrase['delete'],"bbcode.php?" . $vbulletin->session->vars['sessionurl'] . "do=remove&amp;bbcodeid=$bbcode[bbcodeid]");
		print_cells_row($cell, 0, $class, -4);
	}

	print_submit_row($vbphrase['add_new_bb_code'], false, 6);

	if (empty($donetest))
	{
		print_form_header('bbcode', 'test');
		print_table_header($vbphrase['test_your_bb_code']);
		print_textarea_row($vbphrase['enter_text_with_bb_code'], 'text', '', 15, 60);
		print_submit_row($vbphrase['go']);
	}
}

// ########################################################################

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 57655 $
|| ####################################################################
\*======================================================================*/
?>
