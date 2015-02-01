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
$phrasegroups = array('vbblock', 'vbblocksettings');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_block.php');
require_once(DIR . '/includes/functions_misc.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminblocks'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'blockid' => TYPE_INT,
));

log_admin_action(!empty($vbulletin->GPC['blockid']) ? 'block id = ' . $vbulletin->GPC['blockid']:'');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// #############################################################################
// put this before print_cp_header() so we can use an HTTP header
if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

$db = &$vbulletin->db;
$blockmanager = vB_BlockManager::create($vbulletin);

print_cp_header($vbphrase['forum_blocks']);

// Add a notice when Forum Blocks are disabled in options
if (!$vbulletin->options['enablesidebar'] AND in_array($_REQUEST['do'], array('modify', 'addblock', 'addblock2', 'editblock')))
{
	print_table_start();
	print_description_row($vbphrase['forum_sidebar_disabled_in_options']);
	print_table_footer(2, '', '', false);
}

// #############################################################################
if ($_REQUEST['do'] == 'modify')
{
	print_form_header('block', 'doorder');
	print_column_style_code(array('width:25%', 'width:50%', 'width:10%', 'width:15%'));
	print_table_header($vbphrase['forum_blocks'], 4);
	print_cells_row(array($vbphrase['block'], $vbphrase['description'], $vbphrase['display_order'], $vbphrase['controls']), 1, false, -1);
	$blocks = $blockmanager->getBlocks(false, false);

	foreach ($blocks as $blockid => $block)
	{
		$cell = array();
		$cell[] = $block['active']?htmlspecialchars($block['title']):'<strike>' . htmlspecialchars($block['title']) .'</strike>';
		$cell[] = $block['active']?htmlspecialchars($block['description']):'<strike>' . htmlspecialchars($block['description']) .'</strike>';
		$cell[] = '<input type="text" name="order[' . $blockid .']" value="' . $block['displayorder'] .'" size="2" title="' . $vbphrase['display_order'] . '" />';
		$cell[] = construct_link_code($vbphrase['edit'], "block.php?" . $vbulletin->session->vars['sessionurl'] . "do=editblock&amp;blockid=" . $block['blockid'], false)
			. construct_link_code($vbphrase['delete'], "block.php?" . $vbulletin->session->vars['sessionurl'] . "do=deleteblock&amp;blockid=" . $block['blockid'], false);
		print_cells_row($cell, false, false, -1);
	}
	print_table_footer(4, '
		<input type="submit" class="button" value="' . $vbphrase['save_display_order'] . '" tabindex="1" />
		<input type="button" class="button" value="' . $vbphrase['add_block'] . '" tabindex="1" onclick="window.location=\'block.php?' . $vbulletin->session->vars['sessionurl'] . 'do=addblock\';" />
	');

	echo '<p align="center" class="smallfont">' .
		construct_link_code($vbphrase['reload_block_types'], "block.php?" . $vbulletin->session->vars['sessionurl'] . "do=reload", false, $vbphrase['do_this_when_upload_block']) .
		construct_link_code($vbphrase['purge_cache'], "block.php?" . $vbulletin->session->vars['sessionurl'] . "do=purgecache", false);
	echo "</p>\n";
}


// #############################################################################
// Reload block types from disk and insert into db
if ($_REQUEST['do'] == 'reload')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'raction' => TYPE_STR,
	));

	if ($vbulletin->GPC['raction'])
	{
		$raction = $vbulletin->GPC['raction'];
	}
	else
	{
		$raction = 'modify';
	}

	$blockmanager->reloadBlockTypes(true);

	print_cp_message($vbphrase['block_type_reloaded'], "block.php?" . $vbulletin->session->vars['sessionurl'] . "do=" . rawurlencode($raction), 1, null, true);

}

// #############################################################################
// Reload block types from disk and insert into db
if ($_REQUEST['do'] == 'purgecache')
{
	// purge block cache
	$blockmanager->purgeBlockCache();

	// purge activeblocks datastore
	$vbulletin->db->query("DELETE FROM " . TABLE_PREFIX . "datastore WHERE title = 'activeblocks'");

	print_cp_message($vbphrase['cache_purged'], "block.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify", 1, null, true);

}

// #############################################################################
if ($_REQUEST['do'] == 'addblock')
{
	$blocktypes = $blockmanager->getBlockTypes();

	foreach ($blocktypes as $w)
	{
		$options[$w['blocktypeid']] = $vbphrase[$w['title']];
	}

	print_form_header('block', 'addblock2');
	print_table_header($vbphrase['add_new_block']);
	print_select_row($vbphrase['select_block_type'], 'blocktypeid', $options, '', true);

	print_submit_row($vbphrase['continue']);

	echo '<p align="center" class="smallfont">' .
		construct_link_code($vbphrase['reload_block_types'], "block.php?" . $vbulletin->session->vars['sessionurl'] . "do=reload&amp;raction=addblock", false, $vbphrase['do_this_when_upload_block']);
	echo "</p>\n";
}

// #############################################################################
if ($_REQUEST['do'] == 'addblock2')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blocktypeid' => TYPE_INT,
	));

	$blocktype = $blockmanager->getBlockTypeById($vbulletin->GPC['blocktypeid']);
	$blocktypeclass = $blockmanager->loadBlockTypeClassByName($blocktype['name']);
	$blocktypeobj = new $blocktypeclass($vbulletin);

	print_form_header('block', 'insertblock');

	print_column_style_code(array('width:45%', 'width:55%'));

	echo "<thead>\r\n";
	print_table_header($vbphrase['add_new_block'] . ': ' . $vbphrase[$blocktype['title']]);
	echo "</thead>\r\n";

	print_input_row($vbphrase['title'], 'title', $vbphrase[$blocktype['title']]);
	print_textarea_row($vbphrase['description'], 'description');

	if ($blocktype['allowcache'])
	{
		print_input_row($vbphrase['cache_time'], 'cachettl', 60);
	}

	print_input_row($vbphrase['display_order'], 'displayorder');

	echo "<thead>\r\n";
	print_table_header($vbphrase['block_config']);
	echo "</thead>\r\n";

	$blocktypeobj->getConfigHTML();

	construct_hidden_code('blocktypeid', $vbulletin->GPC['blocktypeid']);
	print_submit_row($vbphrase['save']);

}

// #############################################################################
if ($_REQUEST['do'] == 'insertblock')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'setting'      => TYPE_ARRAY,
		'blocktypeid'  => TYPE_INT,
		'title'        => TYPE_NOHTML,
		'description'  => TYPE_NOHTML,
		'cachettl'	   => TYPE_UINT,
		'displayorder' => TYPE_UINT
	));

	try
	{
		$blockmanager->saveNewBlock($vbulletin->GPC['blocktypeid'], $vbulletin->GPC['title'], $vbulletin->GPC['description'], $vbulletin->GPC['cachettl'], $vbulletin->GPC['displayorder'], $vbulletin->GPC['setting']);
	}
	catch (Exception $e)
	{
		print_cp_message('Something wrong when saving block: ' . $e->getMessage());
		exit;
	}

	print_cp_message($vbphrase['block_saved'], "block.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify");
}

// #############################################################################
if ($_REQUEST['do'] == 'editblock')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blockid' => TYPE_INT,
	));

	$block = $blockmanager->createBlock($vbulletin->GPC['blockid']);
	$blockinfo = $block->getBlockInfo();

	print_form_header('block', 'updateblock');

	print_column_style_code(array('width:45%', 'width:55%'));

	echo "<thead>\r\n";
	print_table_header($vbphrase['edit_block'] . ': ' . $blockinfo['title']);
	echo "</thead>\r\n";

	print_input_row($vbphrase['title'], 'title', $blockinfo['title']);
	print_textarea_row($vbphrase['description'], 'description', $blockinfo['description']);

	if ($blockinfo['allowcache'])
	{
		print_input_row($vbphrase['cache_time'], 'cachettl', $blockinfo['cachettl']);
	}

	print_input_row($vbphrase['display_order'], 'displayorder', $blockinfo['displayorder']);

	print_yes_no_row($vbphrase['active'], 'active', $blockinfo['active']);

	echo "<thead>\r\n";
	print_table_header($vbphrase['block_config']);
	echo "</thead>\r\n";

	$block->getBlockType()->getConfigHTML($block->getBlockConfig());

	construct_hidden_code('blockid', $vbulletin->GPC['blockid']);
	print_submit_row($vbphrase['save']);

}
// #############################################################################
if ($_REQUEST['do'] == 'updateblock')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'setting'      => TYPE_ARRAY,
		'blockid'      => TYPE_INT,
		'title'        => TYPE_NOHTML,
		'description'  => TYPE_NOHTML,
		'cachettl'     => TYPE_UINT,
		'active'       => TYPE_BOOL,
		'displayorder' => TYPE_UINT
	));

	try
	{
		$blockmanager->updateBlock($vbulletin->GPC['blockid'], $vbulletin->GPC['title'], $vbulletin->GPC['description'], $vbulletin->GPC['cachettl'], $vbulletin->GPC['displayorder'], $vbulletin->GPC['active'], $vbulletin->GPC['setting']);
	}
	catch (Exception $e)
	{
		print_cp_message('Something wrong when saving block: ' . $e->getMessage());
		exit;
	}

	print_cp_message($vbphrase['block_saved'], "block.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify");
}
// #############################################################################
if ($_REQUEST['do'] == 'deleteblock')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blockid' => TYPE_INT,
	));

	print_delete_confirmation('block', $vbulletin->GPC['blockid'], 'block', 'killblock', 'block', array('blockid' => $vbulletin->GPC['blockid']));
}
// #############################################################################
if ($_REQUEST['do'] == 'killblock')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blockid' => TYPE_INT,
	));

	$blockmanager->deleteBlock($vbulletin->GPC['blockid']);

	print_cp_message($vbphrase['block_deleted'], "block.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify");

}
// ###################### Start do order #######################
if ($_POST['do'] == 'doorder')
{
	$vbulletin->input->clean_array_gpc('p', array('order' => TYPE_ARRAY));

	if (is_array($vbulletin->GPC['order']))
	{
		$blocks = $blockmanager->getBlocks(false);
		foreach ($blocks as $block)
		{
			if (!isset($vbulletin->GPC['order']["$block[blockid]"]))
			{
				continue;
			}

			$displayorder = intval($vbulletin->GPC['order']["$block[blockid]"]);
			if ($block['displayorder'] != $displayorder)
			{
				$blockmanager->updateBlockOrder($block['blockid'], $displayorder);
			}
		}
	}

	// Rebuild activeblocks datastore
	$blockmanager->getBlocks(true, true);

	define('CP_REDIRECT', 'block.php?do=modify');
	print_stop_message('saved_display_order_successfully');
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 35105 $
|| ####################################################################
\*======================================================================*/