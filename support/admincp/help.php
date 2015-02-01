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
define('CVS_REVISION', '$RCSfile$ - $Revision: 37624 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('help_faq', 'fronthelp');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_help.php');

// ############################# LOG ACTION ###############################

$vbulletin->input->clean_array_gpc('r', array('adminhelpid' => TYPE_INT));

log_admin_action(iif($vbulletin->GPC['adminhelpid'] != 0, "help id = " . $vbulletin->GPC['adminhelpid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'answer';
}

// ############################### start download help XML ##############
if ($_REQUEST['do'] == 'download')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'product' => TYPE_STR
	));

	$doc = get_help_export_xml($vbulletin->GPC['product']);
	require_once(DIR . '/includes/functions_file.php');
	file_download($doc, 'vbulletin-adminhelp.xml', 'text/xml');
}

// #########################################################################

print_cp_header($vbphrase['admin_help']);

if ($vbulletin->debug)
{
	print_form_header('', '', 0, 1, 'notaform');
	print_table_header($vbphrase['admin_help_manager']);
	print_description_row(
		construct_link_code($vbphrase['add_new_topic'], "help.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit") .
		construct_link_code($vbphrase['edit_topics'], "help.php?" . $vbulletin->session->vars['sessionurl'] . "do=manage") .
		construct_link_code($vbphrase['download_upload_adminhelp'], "help.php?" . $vbulletin->session->vars['sessionurl'] . "do=files"), 0, 2, '', 'center');
	print_table_footer();
}

// ############################### start do upload help XML ##############
if ($_REQUEST['do'] == 'doimport')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'serverfile'	=> TYPE_STR,
	));

	$vbulletin->input->clean_array_gpc('f', array(
		'helpfile'		=> TYPE_FILE,
	));

	// got an uploaded file?
	// do not use file_exists here, under IIS it will return false in some cases
	if (is_uploaded_file($vbulletin->GPC['helpfile']['tmp_name']))
	{
		$xml = file_read($vbulletin->GPC['helpfile']['tmp_name']);
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

	xml_import_help_topics($xml);

	echo '<p align="center">' . $vbphrase['imported_admin_help_successfully'] . '<br />' . construct_link_code($vbphrase['continue'], "help.php?" . $vbulletin->session->vars['sessionurl'] . "do=manage") . '</p>';
}

// ############################### start upload help XML ##############
if ($_REQUEST['do'] == 'files')
{
	// download form
	print_form_header('help', 'download', 0, 1, 'downloadform" target="download');
	print_table_header($vbphrase['download']);
	print_select_row($vbphrase['product'], 'product', fetch_product_list());
	print_submit_row($vbphrase['download']);
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
	//-->
	</script>
	<?php

	print_form_header('help', 'doimport', 1, 1, 'uploadform" onsubmit="return js_confirm_upload(this, this.helpfile);');
	print_table_header($vbphrase['import_admin_help_xml_file']);
	print_upload_row($vbphrase['upload_xml_file'], 'helpfile', 999999999);
	print_input_row($vbphrase['import_xml_file'], 'serverfile', './install/vbulletin-adminhelp.xml');
	print_submit_row($vbphrase['import'], 0);
}

// ############################### start listing answers ##############
if ($_REQUEST['do'] == 'answer')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'page'			=> TYPE_STR,
		'pageaction'	=> TYPE_STR,
		'option'		=> TYPE_STR
	));

	if (empty($vbulletin->GPC['page']))
	{
		$fullpage = REFERRER;
	}
	else
	{
		$fullpage = $vbulletin->GPC['page'];
	}

	if (!$fullpage)
	{
		print_stop_message('invalid_page_specified');
	}

	if ($strpos = strpos($fullpage, '?'))
	{
		$pagename = basename(substr($fullpage, 0, $strpos));
	}
	else
	{
		$pagename = basename($fullpage);
	}

	if ($strpos = strpos($pagename, '.'))
	{
		$pagename = substr($pagename, 0, $strpos); // remove the .php part as people may have different extensions
	}

	if (!empty($vbulletin->GPC['pageaction']))
	{
		$action = $vbulletin->GPC['pageaction'];
	}
	else if ($strpos AND preg_match('#do=([^&]+)(&|$)#sU', substr($fullpage, $strpos), $matches))
	{
		$action = $matches[1];
	}
	else
	{
		$action = '';
	}

	if (empty($vbulletin->GPC['option']))
	{
		$vbulletin->GPC['option'] = NULL;
	}

	$helptopics = $db->query_read("
		SELECT *, LENGTH(action) AS length
		FROM " . TABLE_PREFIX . "adminhelp
		WHERE script = '".$db->escape_string($pagename)."'
			AND (action = '' OR FIND_IN_SET('" . $db->escape_string($action) . "', action))
			" . iif($vbulletin->GPC['option'] !== NULL,
				"AND optionname = '" . $db->escape_string($vbulletin->GPC['option']) . "'") . "
			AND displayorder <> 0
		ORDER BY displayorder
	");
	if (($resultcount = $db->num_rows($helptopics)) == 0)
	{
		print_stop_message('no_help_topics');
	}
	else
	{
		$general = array();
		$specific = array();
		$phraseSQL = array();
		while ($topic = $db->fetch_array($helptopics))
		{
			$phrasename = $db->escape_string(fetch_help_phrase_short_name($topic));
			$phraseSQL[] = "'$phrasename" . "_title'";
			$phraseSQL[] = "'$phrasename" . "_text'";

			if (!$topic['action'])
			{
				$general[] = $topic;
			}
			else
			{
				$specific[] = $topic;
			}
		}

		// query phrases
		$helpphrase = array();
		$phrases = $db->query_read("
			SELECT varname, text, languageid
			FROM " . TABLE_PREFIX . "phrase
			WHERE fieldname = 'cphelptext'
				AND languageid IN(-1, 0, " . LANGUAGEID . ")
				AND varname IN(\n" . implode(",\n", $phraseSQL) . "\n)
			ORDER BY languageid ASC
		");
		while($phrase = $db->fetch_array($phrases))
		{
			$helpphrase["$phrase[varname]"] = preg_replace('#\{\$([a-z0-9_>-]+([a-z0-9_]+(\[[a-z0-9_]+\])*))\}#ie', '(isset($\\1) AND !is_array($\\1)) ? $\\1 : \'$\\1\'', $phrase['text']);
		}

		if ($resultcount != 1)
		{
			print_form_header('', '');
			print_table_header($vbphrase['quick_help_topic_links'], 1);
			if (sizeof($specific))
			{
				print_description_row($vbphrase['action_specific_topics'], 0, 1, 'thead');
				foreach ($specific AS $topic)
				{
					print_description_row('<a href="#help' . $topic['adminhelpid'] . '">' . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . '</a>', 0, 1);
				}
			}
			if (sizeof($general))
			{
				print_description_row($vbphrase['general_topics'], 0, 1, 'thead');
				foreach ($general AS $topic)
				{
					print_description_row('<a href="#help' . $topic['adminhelpid'] . '">' . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . '</a>', 0, 1);
				}
			}
			print_table_footer();
		}

		if (sizeof($specific))
		{
			reset($specific);
			print_form_header('', '');
			if ($resultcount != 1)
			{
				print_table_header($vbphrase['action_specific_topics'], 1);
			}
			foreach ($specific AS $topic)
			{
				print_description_row("<a name=\"help$topic[adminhelpid]\">" . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . "</a>", 0, 1, 'thead');
				print_description_row($helpphrase[fetch_help_phrase_short_name($topic, '_text')], 0, 1, 'alt1');
				if ($vbulletin->debug)
				{
					print_description_row("<div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">" . construct_button_code($vbphrase['edit'], "help.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;adminhelpid=$topic[adminhelpid]") . "</div><div>action = $topic[action] | optionname = $topic[optionname] | displayorder = $topic[displayorder]</div>", 0, 1, 'alt2 smallfont');
				}
			}
			print_table_footer();
		}

		if (sizeof($general))
		{
			reset($general);
			print_form_header('', '');
			if ($resultcount != 1)
			{
				print_table_header($vbphrase['general_topics'], 1);
			}
			foreach ($general AS $topic)
			{
				print_description_row("<a name=\"help$topic[adminhelpid]\">" . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . "</a>", 0, 1, 'thead');
				print_description_row($helpphrase[fetch_help_phrase_short_name($topic, '_text')]);
			}
			print_table_footer();
		}
	}
}

// ############################### start form for adding/editing help topics ##############
if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'adminhelpid' => TYPE_INT,
		'script'      => TYPE_NOHTML,
		'scriptaction'=> TYPE_NOHTML,
		'option'      => TYPE_NOHTML,
	));

	$helpphrase = array();

	print_form_header('help', 'doedit');
	if (empty($vbulletin->GPC['adminhelpid']))
	{
		$adminhelpid = 0;
		$helpdata = array(
			'adminhelpid'  => 0,
			'script'       => $vbulletin->GPC['script'],
			'action'       => $vbulletin->GPC['scriptaction'],
			'optionname'   => $vbulletin->GPC['option'],
			'displayorder' => 1,
			'volatile'     => iif($vbulletin->debug, 1, 0)
		);

		print_table_header($vbphrase['add_new_topic']);
	}
	else
	{
		$helpdata = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "adminhelp
			WHERE adminhelpid = " . $vbulletin->GPC['adminhelpid']
		);

		$titlephrase = fetch_help_phrase_short_name($helpdata, '_title');
		$textphrase = fetch_help_phrase_short_name($helpdata, '_text');

		// query phrases
		$phrases = $db->query_read("
			SELECT varname, text FROM " . TABLE_PREFIX . "phrase
			WHERE fieldname = 'cphelptext' AND
			languageid = " . iif($helpdata['volatile'], -1, 0) . " AND
			varname IN ('" . $db->escape_string($titlephrase) . "', '" . $db->escape_string($textphrase) . "')
		");
		while ($phrase = $db->fetch_array($phrases))
		{
			$helpphrase["$phrase[varname]"] = $phrase['text'];
		}
		unset($phrase);
		$db->free_result($phrases);

		construct_hidden_code('orig[script]', $helpdata['script']);
		construct_hidden_code('orig[action]', $helpdata['action']);
		construct_hidden_code('orig[optionname]', $helpdata['optionname']);
		construct_hidden_code('orig[product]', $helpdata['product']);
		construct_hidden_code('orig[title]', $helpphrase["$titlephrase"]);
		construct_hidden_code('orig[text]', $helpphrase["$textphrase"]);

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['topic'], $helpdata['title'], $helpdata['adminhelpid']));
	}

	print_input_row($vbphrase['script'], 'help[script]', $helpdata['script']);
	print_input_row($vbphrase['action_leave_blank'], 'help[action]', $helpdata['action']);

	print_select_row($vbphrase['product'], 'help[product]', fetch_product_list(), $helpdata['product']);

	print_input_row($vbphrase['option'], 'help[optionname]', $helpdata['optionname']);
	print_input_row($vbphrase['display_order'], 'help[displayorder]', $helpdata['displayorder']);

	print_input_row($vbphrase['title'], 'title', $helpphrase["$titlephrase"]);
	print_textarea_row($vbphrase['text'], 'text', $helpphrase["$textphrase"], 10, '50" style="width:100%');

	if ($vbulletin->debug)
	{
		print_yes_no_row($vbphrase['vbulletin_default'], 'help[volatile]', $helpdata['volatile']);
	}
	else
	{
		construct_hidden_code('help[volatile]', $helpdata['volatile']);
	}

	construct_hidden_code('adminhelpid', $vbulletin->GPC['adminhelpid']);
	print_submit_row($vbphrase['save']);
}

// ############################### start actually adding/editing help topics ##############
if ($_POST['do'] == 'doedit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'adminhelpid'	=> TYPE_INT,
		'help'			=> TYPE_ARRAY_STR,
		'orig'			=> TYPE_ARRAY_STR,
		'title' 		=> TYPE_STR,
		'text' 			=> TYPE_STR
	));

	if (!$vbulletin->GPC['help']['script'])
	{
		print_stop_message('please_complete_required_fields');
	}

	$newphrasename = $db->escape_string(fetch_help_phrase_short_name($vbulletin->GPC['help']));

	$languageid = iif($vbulletin->GPC['help']['volatile'], -1, 0);

	$full_product_info = fetch_product_list(true);
	$product_version = $full_product_info[$vbulletin->GPC['help']['product']]['version'];

	if (!empty($vbulletin->GPC['orig'])) // update
	{
		$oldphrasename = $db->escape_string(fetch_help_phrase_short_name($vbulletin->GPC['orig']));

		// update help item
		$q[] = fetch_query_sql($vbulletin->GPC['help'], 'adminhelp', 'WHERE adminhelpid = ' . $vbulletin->GPC['adminhelpid']);

		// update phrase titles for all languages
		if ($newphrasename != $oldphrasename)
		{
			$q[] = "
				### UPDATE HELP TITLE PHRASES FOR ALL LANGUAGES ###
				UPDATE " . TABLE_PREFIX . "phrase
				SET varname = '$newphrasename" . "_title'
				WHERE fieldname = 'cphelptext'
					AND varname = '$oldphrasename" . "_title'
			";
			$q[] = "
				### UPDATE HELP TEXT PHRASES FOR ALL LANGUAGES ###
				UPDATE " . TABLE_PREFIX . "phrase
				SET varname = '$newphrasename" . "_text'
				WHERE fieldname = 'cphelptext'
					AND varname = '$oldphrasename" . "_text'
			";
		}

		// update phrase title contents for master language
		if ($vbulletin->GPC['orig']['title'] != $vbulletin->GPC['title'])
		{
			$q[] = "
			### UPDATE HELP TITLE CONTENTS PHRASES FOR MASTER LANGUAGE ###
			REPLACE INTO " . TABLE_PREFIX . "phrase
				(text, fieldname, languageid, varname, product, username, dateline, version)
			VALUES
				('" . $db->escape_string($vbulletin->GPC['title']) . "',
				'cphelptext',
				$languageid,
				'{$newphrasename}_title',
				'" . $db->escape_string($vbulletin->GPC['help']['product']) . "',
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($product_version) . "')
			";
		}
		else if ($vbulletin->GPC['orig']['product'] != $vbulletin->GPC['help']['product'])
		{
			// haven't changed the title, but we changed the product,
			// so we need to reflect that
			$q[] = "
				UPDATE " . TABLE_PREFIX . "phrase SET
					product = '" . $db->escape_string($vbulletin->GPC['help']['product']) . "',
					username = '" . $db->escape_string($vbulletin->userinfo['username']) . "',
					dateline = " . TIMENOW . ",
					version = '" . $db->escape_string($product_version) . "'
				WHERE fieldname = 'cphelptext'
					AND varname = '{$newphrasename}_title'
			";
		}

		// update phrase text contents for master language
		if ($vbulletin->GPC['orig']['text'] != $vbulletin->GPC['text'])
		{
			$q[] = "
			### UPDATE HELP TEXT CONTENTS PHRASES FOR MASTER LANGUAGE ###
			REPLACE INTO " . TABLE_PREFIX . "phrase
				(text, fieldname, languageid, varname, product, username, dateline, version)
			VALUES
				('" . $db->escape_string($vbulletin->GPC['text']) . "',
				'cphelptext',
				$languageid,
				'{$newphrasename}_text',
				'" . $db->escape_string($vbulletin->GPC['help']['product']) . "',
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($product_version) . "')
			";
		}
		else if ($vbulletin->GPC['orig']['product'] != $vbulletin->GPC['help']['product'])
		{
			// haven't changed the text, but we changed the product,
			// so we need to reflect that
			$q[] = "
				UPDATE " . TABLE_PREFIX . "phrase SET
					product = '" . $db->escape_string($vbulletin->GPC['help']['product']) . "',
					username = '" . $db->escape_string($vbulletin->userinfo['username']) . "',
					dateline = " . TIMENOW . ",
					version = '" . $db->escape_string($product_version) . "'
				WHERE fieldname = 'cphelptext'
					AND varname = '{$newphrasename}_text'
			";
		}
	}
	else // insert
	{
		$sql = "
		SELECT * FROM " . TABLE_PREFIX . "adminhelp
		WHERE script = '" . $db->escape_string($vbulletin->GPC['help']['script']) . "'
			AND action = '" . $db->escape_string($vbulletin->GPC['help']['action']) . "'
			AND optionname = '" . $db->escape_string($vbulletin->GPC['help']['optionname']) . "'";

		if ($check = $db->query_first($sql))
		{ // error message, this already exists
			// why phrase when its only available in debug mode and its meant for us?
			print_cp_message('This help item already exists.');
		}
		unset($sql);

		// insert help item
		$q[] = fetch_query_sql($vbulletin->GPC['help'], 'adminhelp');

		// insert new phrases
		$q[] = "
			### INSERT NEW HELP PHRASES ###
			INSERT INTO " . TABLE_PREFIX . "phrase
				(languageid, fieldname, varname, text, product, username, dateline, version)
			VALUES
				(
					$languageid,
					'cphelptext',
					'$newphrasename" . "_title',
					'" . $db->escape_string($vbulletin->GPC['title']) . "',
					'" . $db->escape_string($vbulletin->GPC['help']['product']) . "',
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($product_version) . "'
				),
				(
					$languageid,
					'cphelptext',
					'$newphrasename" . "_text',
					'" . $db->escape_string($vbulletin->GPC['text']) . "',
					'" . $db->escape_string($vbulletin->GPC['help']['product']) . "',
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($product_version) . "'
				)
		";
	}


	foreach($q AS $sql)
	{
		/*insert query*/
		$db->query_write($sql);
	}


	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		autoexport_write_help(array($vbulletin->GPC['orig']['product'], 
			$vbulletin->GPC['help']['product']));
	}

	define('CP_REDIRECT', 'help.php?do=manage&amp;script=' . $vbulletin->GPC['help']['script']);
	print_stop_message('saved_topic_x_successfully', $title);

}

// ############################### start confirmation for deleting a help topic ##############
if ($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('r', array('adminhelpid'	=> TYPE_INT));

	print_delete_confirmation('adminhelp', $vbulletin->GPC['adminhelpid'], 'help', 'dodelete', 'topic');
}

// ############################### start actually deleting the help topic ##############
if ($_POST['do'] == 'dodelete')
{
	$vbulletin->input->clean_array_gpc('r', array('adminhelpid'	=> TYPE_INT));

	$help = $db->query_first("
		SELECT script, action, optionname, product 
		FROM " . TABLE_PREFIX . "adminhelp 
		WHERE adminhelpid = " . $vbulletin->GPC['adminhelpid']
	);

	if ($help)
	{
		// delete adminhelp entry
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "adminhelp 
			WHERE adminhelpid = " . $vbulletin->GPC['adminhelpid']
		);

		// delete associated phrases
		$phrasename = $db->escape_string(fetch_help_phrase_short_name($help));
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "phrase
			WHERE fieldname = 'cphelptext'
				AND varname IN ('$phrasename" . "_title', '$phrasename" . "_text')
		");

		// update language records
		require_once(DIR . '/includes/adminfunctions_language.php');
		build_language();

		if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			require_once(DIR . '/includes/functions_filesystemxml.php');
			autoexport_write_help($help['product']);
		}
	}

	define('CP_REDIRECT', 'help.php?do=manage');
	print_stop_message('deleted_topic_successfully');
}

// ############################### start list of existing help topics ##############
if ($_REQUEST['do'] == 'manage')
{
	$vbulletin->input->clean_array_gpc('r', array('script'	=> TYPE_STR));

	// query phrases
	$helpphrase = array();
	$phrases = $db->query_read("SELECT varname, text FROM " . TABLE_PREFIX . "phrase WHERE fieldname = 'cphelptext'");
	while ($phrase = $db->fetch_array($phrases))
	{
		$helpphrase["$phrase[varname]"] = $phrase['text'];
	}
	unset($phrase);
	$db->free_result($phrases);

	// query scripts
	$scripts = array();
	$getscripts = $db->query_read("SELECT DISTINCT script FROM " . TABLE_PREFIX . "adminhelp");
	while ($getscript = $db->fetch_array($getscripts))
	{
		$scripts["$getscript[script]"] = "$getscript[script].php";
	}
	unset($getscript);
	$db->free_result($getscripts);

	// query topics
	$topics = array();
	$gettopics = $db->query_read("
		SELECT adminhelpid, script, action, optionname, displayorder
		FROM " . TABLE_PREFIX . "adminhelp
		" . iif($vbulletin->GPC['script'], "WHERE script = '" . $db->escape_string($vbulletin->GPC['script']) . "'") . "
		ORDER BY script, action, displayorder
	");
	while ($gettopic = $db->fetch_array($gettopics))
	{
		$topics["$gettopic[script]"][] = $gettopic;
	}
	unset($gettopic);
	$db->free_result($gettopics);

	// build the form
	print_form_header('help', 'manage', false, true, 'helpform' ,'90%', '', true, 'get');
	print_table_header($vbphrase['topic_manager'], 5);
	print_description_row('<div align="center">' . $vbphrase['script'] . ': <select name="script" tabindex="1" onchange="this.form.submit()" class="bginput"><option value="">' . $vbphrase['all_scripts'] . '</option>' . construct_select_options($scripts, $script) . '</select> <input type="submit" class="button" value="' . $vbphrase['go'] . '" tabindex="1" /></div>', 0, 5, 'thead');

	foreach($topics AS $script => $scripttopics)
	{
		print_table_header($script . '.php', 5);
		print_cells_row(
			array(
				$vbphrase['action'],
				$vbphrase['option'],
				$vbphrase['title'],
				$vbphrase['order_by'],
				''
			), 1, 0, -5
		);
		foreach($scripttopics AS $topic)
		{
			print_cells_row(
				array(
					'<span class="smallfont">' . $topic['action'] . '</span>',
					'<span class="smallfont">' . $topic['optionname'] . '</span>',
					'<span class="smallfont"><b>' . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . '</b></span>',
					'<span class="smallfont">' . $topic['displayorder'] . '</span>',
					'<span class="smallfont">' . construct_link_code($vbphrase['edit'], "help.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;adminhelpid=$topic[adminhelpid]") . construct_link_code($vbphrase['delete'], "help.php?" . $vbulletin->session->vars['sessionurl'] . "do=delete&amp;adminhelpid=$topic[adminhelpid]") . '</span>'
				), 0, 0, -5
			);
		}
	}

	print_table_footer();
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 37624 $
|| ####################################################################
\*======================================================================*/
?>
