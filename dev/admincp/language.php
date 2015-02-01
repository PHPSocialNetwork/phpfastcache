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
define('DEFAULT_FILENAME', 'vbulletin-language.xml');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('language');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_language.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminlanguages'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'dolanguageid' => TYPE_INT
));

// ############################# LOG ACTION ###############################
log_admin_action(iif(!empty($vbulletin->GPC['dolanguageid']), "Language ID = " . $vbulletin->GPC['dolanguageid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
if (($current_memory_limit = ini_size_to_bytes(@ini_get('memory_limit'))) < 128 * 1024 * 1024 AND $current_memory_limit > 0)
{
	@ini_set('memory_limit', 128 * 1024 * 1024);
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

$langglobals = array(
	'title'                  => TYPE_NOHTML,
	'userselect'             => TYPE_INT,
	'options'                => TYPE_ARRAY_BOOL,
	'languagecode'           => TYPE_STR,
	'charset'                => TYPE_STR,
	'locale'                 => TYPE_STR,
	'imagesoverride'         => TYPE_STR,
	'dateoverride'           => TYPE_STR,
	'timeoverride'           => TYPE_STR,
	'registereddateoverride' => TYPE_STR,
	'calformat1override'     => TYPE_STR,
	'calformat2override'     => TYPE_STR,
	'logdateoverride'        => TYPE_STR,
	'decimalsep'             => TYPE_STR,
	'thousandsep'            => TYPE_STR
);

/*
//moved to download function -- download is the only place we use this so we 
//should keep that together so that we don't have to define this everywhere
//we might want to call the download function.  If we have need of it 
//elsewhere we may need to change that.
$default_skipped_groups = array(
	'cphelptext'
);
 */

// #############################################################################

if ($_POST['do'] == 'download')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'filename'     => TYPE_STR,
		'just_phrases' => TYPE_BOOL,
		'product'      => TYPE_STR,
		'custom'       => TYPE_BOOL,
		'charset'      => TYPE_NOHTML,
	));

	if (empty($vbulletin->GPC['filename']))
	{
		$vbulletin->GPC['filename'] = DEFAULT_FILENAME;
	}

	if (function_exists('set_time_limit') AND !SAFEMODE)
	{
		@set_time_limit(1200);
	}

	try
	{
		$doc = get_language_export_xml($vbulletin->GPC['dolanguageid'], $vbulletin->GPC['product'], $vbulletin->GPC['custom'], $vbulletin->GPC['just_phrases'], $vbulletin->GPC['charset'] ? $vbulletin->GPC['charset'] : 'ISO-8859-1');	
	}
	catch (vB_Exception_AdminStopMessage $e)
	{
		//move print_stop_message calls from install_product so we
		//can use it places where said calls aren't appropriate.
		call_user_func_array('print_stop_message', $e->getParams());
	}

	require_once(DIR . '/includes/functions_file.php');
	file_download($doc, $vbulletin->GPC['filename'], 'text/xml');
}

// ##########################################################################

print_cp_header($vbphrase['language_manager']);

// #############################################################################
// #############################################################################

if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'fieldname'  => TYPE_NOHTML,
		'pagenumber' => TYPE_UINT,
		'def'        => TYPE_ARRAY_STR,  // default text values array (hidden fields)
		'phr'        => TYPE_ARRAY_STR,  // changed text values array (textarea fields)
		'rvt'        => TYPE_ARRAY_UINT, // revert phrases array (checkbox fields)
		'prod'       => TYPE_ARRAY_STR,  // products the phrases as associated with
	));

	if (empty($vbulletin->GPC['product']))
	{
		$vbulletin->GPC['product'] = 'vbulletin';
	}

	$updatelanguage = false;

	if (!empty($vbulletin->GPC['rvt']))
	{
		$updatelanguage = true;

		$query = "
			DELETE FROM " . TABLE_PREFIX . "phrase
			WHERE phraseid IN(" . implode(', ', $vbulletin->GPC['rvt']) . ")
		";

		$db->query_write($query);

		// unset reverted phrases
		foreach (array_keys($vbulletin->GPC['rvt']) AS $varname)
		{
			unset($vbulletin->GPC['def']["$varname"]);
		}
	}

	$sql = array();

	$full_product_info = fetch_product_list(true);

	foreach (array_keys($vbulletin->GPC['def']) AS $varname)
	{
		$defphrase =& $vbulletin->GPC['def']["$varname"];
		$newphrase =& $vbulletin->GPC['phr']["$varname"];
		$product   =& $vbulletin->GPC['prod']["$varname"];
		$product_version = $full_product_info["$product"]['version'];

		if ($newphrase != $defphrase)
		{
			$sql[] = "
				(" . $vbulletin->GPC['dolanguageid'] . ",
				'" . $db->escape_string($vbulletin->GPC['fieldname']) . "',
				'" . $db->escape_string($varname) . "',
				'" . $db->escape_string($newphrase) . "',
				'" . $db->escape_string($product) . "',
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($product_version) . "')
			";
		}
	}

	if (!empty($sql))
	{
		$updatelanguage = true;

		$query = "
			### UPDATE CHANGED PHRASES FROM LANGUAGE:" . $vbulletin->GPC['dolanguageid'] . ", PHRASETYPE:" . $vbulletin->GPC['fieldname'] . " ###
			REPLACE INTO " . TABLE_PREFIX . "phrase
				(languageid, fieldname, varname, text, product, username, dateline, version)
			VALUES
				" . implode(",\n\t\t\t\t", $sql) . "
		";

		$db->query_write($query);
	}

	if ($updatelanguage)
	{
		build_language($vbulletin->GPC['dolanguageid']);
	}

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		//figure out the products of the phrases processed.
		$products = array();
		foreach (array_keys($vbulletin->GPC['rvt']) AS $varname)
		{
			$products[$vbulletin->GPC['prod']["$varname"]] = 1;
		}

		foreach (array_keys($vbulletin->GPC['def']) AS $varname)
		{
			if ($vbulletin->GPC['def']["$varname"] != $vbulletin->GPC['phr']["$varname"])
			{
				$products[$vbulletin->GPC['prod']["$varname"]] = 1;
			}
		}
		$products = array_keys($products);

		//export those products;	
		require_once(DIR . '/includes/functions_filesystemxml.php');
		foreach($products as $product)
		{
			autoexport_write_language($vbulletin->GPC['dolanguageid'], $product);
		}
	}


	define('CP_REDIRECT', "language.php?do=edit&amp;dolanguageid=" . $vbulletin->GPC['dolanguageid'] . 
		"&amp;fieldname=" . $vbulletin->GPC['fieldname'] . "&amp;page=" . $vbulletin->GPC['pagenumber']);
	print_stop_message('saved_language_successfully');
}

// #############################################################################
// #############################################################################

// ##########################################################################

if ($_POST['do'] == 'upload')
{
	ignore_user_abort(true);

	$vbulletin->input->clean_array_gpc('p', array(
		'title'        => TYPE_STR,
		'serverfile'   => TYPE_STR,
		'anyversion'   => TYPE_BOOL,
		'readcharset'  => TYPE_BOOL,
	));

	$vbulletin->input->clean_array_gpc('f', array(
		'languagefile' => TYPE_FILE
	));

	($hook = vBulletinHook::fetch_hook('admin_language_import')) ? eval($hook) : false;

	// got an uploaded file?
	// do not use file_exists here, under IIS it will return false in some cases
	if (is_uploaded_file($vbulletin->GPC['languagefile']['tmp_name']))
	{
		$xml = file_read($vbulletin->GPC['languagefile']['tmp_name']);
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

	xml_import_language($xml, $vbulletin->GPC['dolanguageid'], $vbulletin->GPC['title'], $vbulletin->GPC['anyversion'], true, true, $vbulletin->GPC['readcharset']);

	build_language_datastore();

	print_cp_redirect("language.php?" . $vbulletin->session->vars['sessionurl'] . "do=rebuild&amp;goto=language.php?" . $vbulletin->session->vars['sessionurl'], 0);

}

// ##########################################################################

if ($_REQUEST['do'] == 'files')
{
	require_once(DIR . '/includes/functions_misc.php');
	$alllanguages = fetch_languages_array();
	$languages = array();
	$charsets = array(
		'ISO-8859-1' => 'ISO-8859-1'
	);
	$jscharsets = array(
		'-1' => 'ISO-8859-1'
	);
	foreach ($alllanguages AS $languageid => $language)
	{
		$jscharsets[$languageid] = strtoupper($language['charset']);
		$languages[$languageid] = $language['title'];
		if ($languageid == $vbulletin->GPC['dolanguageid'])
		{
			$charset = strtoupper($language['charset']);
			if ($charset != 'ISO-8859-1')
			{
				$charsets[$charset] = $charset;
			}
		}
	}
	?>
	<script type="text/javascript">
	<!--
	function js_set_charset(formobj, languageid)
	{
		var charsets = {
		<?php
		$output = '';
		foreach ($jscharsets AS $languageid => $charset)
		{
			$output .= "'$languageid' : '$charset',\r\n";
		}
		echo rtrim($output, "\r\n,") . "\r\n";
		?>
		};
		var charsetobj = formobj.charset;
		var charset = charsets[languageid];
		if (charset == charsetobj.options[0].value) // 'ISO-8859-1' which is always in options[0]
		{	// Remove second charset item from list since this language is 'ISO-8859-1'
			if (charsetobj.options.length == 2)
			{
				charsetobj.remove(1);
			}
		}
		else
		{
			if (charsetobj.options.length == 1)
			{	// Add an option!
				var option = document.createElement("option");
				charsetobj.add(option, null);
			}
			// Change the option, maybe to the same thing but that doesn't matter
			charsetobj.options[1].value = charset;
			charsetobj.options[1].text = charset;
		}
	}
	// -->
	</script>
	<?php	
	
	// download form
	print_form_header('language', 'download', 0, 1, 'downloadform" target="download');
	print_table_header($vbphrase['download']);
	print_label_row($vbphrase['language'], '<select name="dolanguageid" tabindex="1" class="bginput" onchange="js_set_charset(this.form, this.value)">' . ($vbulletin->debug ? '<option value="-1">' . MASTER_LANGUAGE . '</option>' : '') . construct_select_options($languages, $vbulletin->GPC['dolanguageid']) . '</select>', '', 'top', 'languageid');
	print_select_row($vbphrase['product'], 'product', fetch_product_list());
	print_input_row($vbphrase['filename'], 'filename', DEFAULT_FILENAME);
	print_select_row($vbphrase['charset'], 'charset', $charsets);
	print_yes_no_row($vbphrase['include_custom_phrases'], 'custom', 0);
	print_yes_no_row($vbphrase['just_fetch_phrases'], 'just_phrases', 0);
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

	// upload form
	print_form_header('language', 'upload', 1, 1, 'uploadform" onsubmit="return js_confirm_upload(this, this.languagefile);');
	print_table_header($vbphrase['import_language_xml_file']);
	print_upload_row($vbphrase['upload_xml_file'], 'languagefile', 999999999);
	print_input_row($vbphrase['import_xml_file'], 'serverfile', './install/vbulletin-language.xml');
	print_label_row($vbphrase['overwrite_language_dfn'], '<select name="dolanguageid" tabindex="1" class="bginput"><option value="0">(' . $vbphrase['create_new_language'] . ')</option>' . construct_select_options($languages) . '</select>', '', 'top', 'olanguageid');
	print_input_row($vbphrase['title_for_uploaded_language'], 'title');
	print_yes_no_row($vbphrase['ignore_language_version'], 'anyversion', 0);
	print_yes_no_row($vbphrase['read_charset_from_file'], 'readcharset', 1);
	print_submit_row($vbphrase['import']);

}

// ##########################################################################

if ($_REQUEST['do'] == 'rebuild')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'goto' => TYPE_STR
	));

	$help = construct_help_button('', NULL, '', 1);

	echo "<p>&nbsp;</p>
	<blockquote><form><div class=\"tborder\">
	<div class=\"tcat\" style=\"padding:4px\" align=\"center\"><div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">$help</div><b>" . $vbphrase['rebuild_language_information'] . "</b></div>
	<div class=\"alt1\" style=\"padding:4px\">\n<blockquote>
	";
	vbflush();

	$languages = fetch_languages_array();
	foreach($languages AS $_languageid => $language)
	{
		echo "<p>" . construct_phrase($vbphrase['rebuilding_language_x'], "<b>$language[title]</b>") . iif($_languageid == $vbulletin->options['languageid'], " ($vbphrase[default])") . ' ...';
		vbflush();
		build_language($_languageid);
		echo "<b>" . $vbphrase['done'] . "</b></p>\n";
		vbflush();
	}

	build_language_datastore();

	echo "</blockquote></div>
	<div class=\"tfoot\" style=\"padding:4px\" align=\"center\">
		<input type=\"button\" class=\"button\" value=\" $vbphrase[done] \" onclick=\"window.location='" . str_replace("'", "\\'", htmlspecialchars_uni($vbulletin->GPC['goto'])) . "';\" />
	</div>
	</div></form></blockquote>
	";
	vbflush();

}

// ##########################################################################

if ($_REQUEST['do'] == 'setdefault')
{
	if ($vbulletin->GPC['dolanguageid'] == 0)
	{
		print_stop_message('invalid_language_specified');
	}

	$db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value = " . $vbulletin->GPC['dolanguageid'] . " WHERE varname = 'languageid'");
	build_options();
	$vbulletin->options['languageid'] = $vbulletin->GPC['dolanguageid'];

	build_language($vbulletin->GPC['dolanguageid']);

	$_REQUEST['do'] = 'modify';

}

// ##########################################################################

if ($_REQUEST['do'] == 'view')
{
	if ($vbulletin->GPC['dolanguageid'] != -1)
	{
		$language = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "language WHERE languageid = " . $vbulletin->GPC['dolanguageid']);
		$phrase = unserialize($language['language']);
	}
	else
	{
		$phrase = array();
		$language['title'] = $vbphrase['master_language'];

		$getphrases = $db->query_read("
			SELECT phrase.varname, phrase.text, phrase.fieldname
			FROM " . TABLE_PREFIX . "phrase AS phrase
			LEFT JOIN " . TABLE_PREFIX . "phrasetype AS phrasetype USING (fieldname)
			WHERE languageid = -1 AND phrasetype.special = 0
			ORDER BY varname
		");
		while ($getphrase = $db->fetch_array($getphrases))
		{
			$phrase["$getphrase[varname]"] = $getphrase['text'];
		}
	}

	if (!empty($phrase))
	{
		print_form_header('', '');
		print_table_header($vbphrase['view_language'] . " <span class=\"normal\">$language[title]<span>");
		print_cells_row(array($vbphrase['varname'], $vbphrase['replace_with_text']), 1, 0, 1);
		foreach($phrase AS $varname => $text)
		{
			print_cells_row(array("<span style=\"white-space: nowrap\">\$vbphrase[<b>$varname</b>]</span>", "<span class=\"smallfont\">" . htmlspecialchars_uni($text) . "</span>"), 0, 0, -1);
		}
		print_table_footer();
	}
	else
	{
		print_stop_message('no_phrases_defined');
	}

}

// ##########################################################################

if ($_POST['do'] == 'kill')
{
	if ($vbulletin->GPC['dolanguageid'] == $vbulletin->options['languageid'])
	{
		// show the 'can't delete default' error message
		$_REQUEST['do'] = 'delete';
	}
	else
	{
		$languages = $db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "language");
		if ($languages['total'] == 1)
		{
			print_stop_message('cant_delete_last_language');
		}
		else
		{
			$db->query_write("UPDATE " . TABLE_PREFIX . "user SET languageid = 0 WHERE languageid = " . $vbulletin->GPC['dolanguageid']);
			$db->query_write("UPDATE " . TABLE_PREFIX . "session SET languageid = 0 WHERE languageid = " . $vbulletin->GPC['dolanguageid']);
			$db->query_write("DELETE FROM " . TABLE_PREFIX . "phrase WHERE languageid = " . $vbulletin->GPC['dolanguageid']);
			$db->query_write("DELETE FROM " . TABLE_PREFIX . "language WHERE languageid = " . $vbulletin->GPC['dolanguageid']);

			build_language_datastore();

			define('CP_REDIRECT', 'language.php');
			print_stop_message('deleted_language_successfully');
		}
	}

}

// ##########################################################################

if ($_REQUEST['do'] == 'delete')
{

	if ($vbulletin->GPC['dolanguageid'] == $vbulletin->options['languageid'])
	{
		print_stop_message('cant_delete_default_language');
	}

	print_delete_confirmation('language', $vbulletin->GPC['dolanguageid'], 'language', 'kill', 'language', 0, $vbphrase['deleting_this_language_will_delete_custom_phrases']);

}

// ##########################################################################

if ($_POST['do'] == 'insert')
{
	$vbulletin->input->clean_array_gpc('p', $langglobals);

	require_once(DIR . '/includes/functions_misc.php');
	$vbulletin->GPC['options'] = convert_array_to_bits($vbulletin->GPC['options'], $vbulletin->bf_misc_languageoptions);

	$newlang = array();
	foreach($langglobals AS $key => $val)
	{
		$newlang["$key"] =& $vbulletin->GPC["$key"];
	}

	if (empty($newlang['title']) OR empty($newlang['charset']))
	{
		print_stop_message('please_complete_required_fields');
	}

	// User has defined a locale.
	if ($newlang['locale'] != '')
	{
		if (!setlocale(LC_TIME, $newlang['locale']) OR !setlocale(LC_CTYPE, $newlang['locale']))
		{
			print_stop_message('invalid_locale', $newlang['locale']);
		}

		if ($newlang['dateoverride'] == '' OR $newlang['timeoverride'] == '' OR $newlang['registereddateoverride'] == '' OR $newlang['calformat1override'] == '' OR $newlang['calformat2override'] == '' OR $newlang['logdateoverride'] == '')
		{
			print_stop_message('locale_define_fill_in_all_overrides');
		}
	}

	/*insert query*/
	$db->query_write(fetch_query_sql($newlang, 'language'));
	$_languageid = $db->insert_id($result);

	build_language($_languageid);
	build_language_datastore();

	define('CP_REDIRECT', 'language.php?dolanguageid=' . $_languageid);
	print_stop_message('saved_language_x_successfully', $newlang['title']);
}

// ##########################################################################

if ($_REQUEST['do'] == 'add')
{
	print_form_header('language', 'insert');
	print_table_header($vbphrase['add_new_language']);

	print_description_row($vbphrase['general_settings'], 0, 2, 'thead');
	print_input_row($vbphrase['title'], 'title');
	print_yes_no_row($vbphrase['allow_user_selection'], 'userselect');
	print_yes_no_row($vbphrase['enable_directional_markup_fix'], 'options[dirmark]');
	print_label_row($vbphrase['text_direction'],
		"<label for=\"rb_l2r\"><input type=\"radio\" name=\"options[direction]\" id=\"rb_l2r\" value=\"1\" tabindex=\"1\" checked=\"checked\" />$vbphrase[left_to_right]</label><br />
		 <label for=\"rb_r2l\"><input type=\"radio\" name=\"options[direction]\" id=\"rb_r2l\" value=\"0\" tabindex=\"1\" />$vbphrase[right_to_left]</label>",
		'', 'top', 'direction'
	);
	print_input_row($vbphrase['language_code'], 'languagecode', 'en');
	print_input_row($vbphrase['html_charset'] . "<code>&lt;meta http-equiv=&quot;Content-Type&quot; content=&quot;text/html; charset=<b>ISO-8859-1</b>&quot; /&gt;</code>", 'charset', 'ISO-8859-1');
	print_input_row($vbphrase['image_folder_override'], 'imagesoverride', '');

	print_description_row($vbphrase['date_time_formatting'], 0, 2, 'thead');
	print_input_row($vbphrase['locale'], 'locale', '');
	print_input_row($vbphrase['date_format_override'], 'dateoverride', '');
	print_input_row($vbphrase['time_format_override'], 'timeoverride', '');
	print_input_row($vbphrase['registereddate_format_override'], 'registereddateoverride', '');
	print_input_row($vbphrase['calformat1_format_override'], 'calformat1override', '');
	print_input_row($vbphrase['calformat2_format_override'], 'calformat2override', '');
	print_input_row($vbphrase['logdate_format_override'], 'logdateoverride', '');

	print_description_row($vbphrase['number_formatting'], 0, 2, 'thead');
	print_input_row($vbphrase['decimal_separator'], 'decimalsep', '.', 1, 3, 1);
	print_input_row($vbphrase['thousands_separator'], 'thousandsep', ',', 1, 3, 1);

	print_submit_row($vbphrase['save']);
}

// ##########################################################################

if ($_POST['do'] == 'update_settings')
{
	$vbulletin->input->clean_array_gpc('p', array_merge($langglobals, array('isdefault' => TYPE_BOOL)));


	require_once(DIR . '/includes/functions_misc.php');

	$vbulletin->GPC['options'] = convert_array_to_bits($vbulletin->GPC['options'], $vbulletin->bf_misc_languageoptions);
	foreach($langglobals AS $key => $val)
	{
		$langupdate["$key"] = $vbulletin->GPC["$key"];
	}

	if (empty($langupdate['title']) OR empty($langupdate['charset']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($isdefault AND $langupdate['userselect'] == 0)
	{
		print_stop_message('cant_delete_default_language');
	}

	// User has defined a locale.
	if ($langupdate['locale'] != '')
	{
		if (!setlocale(LC_TIME, $langupdate['locale']) OR !setlocale(LC_CTYPE, $langupdate['locale']))
		{
			print_stop_message('invalid_locale', $langupdate['locale']);
		}

		if ($langupdate['dateoverride'] == '' OR $langupdate['timeoverride'] == '' OR $langupdate['registereddateoverride'] == '' OR $langupdate['calformat1override'] == '' OR $langupdate['calformat2override'] == '' OR $langupdate['logdateoverride'] == '')
		{
			print_stop_message('locale_define_fill_in_all_overrides');
		}
	}

	$query = fetch_query_sql($langupdate, 'language', "WHERE languageid = " . $vbulletin->GPC['dolanguageid']);
	$db->query_write($query);

	if ($vbulletin->GPC['isdefault'] AND $vbulletin->GPC['dolanguageid'] != $vbulletin->options['languageid'])
	{
		$do = 'setdefault';
	}
	else
	{
		$do = 'modify';
	}

	build_language_datastore();

	define('CP_REDIRECT', 'language.php?dolanguageid=' . $vbulletin->GPC['dolanguageid'] . '&amp;do=' . $do);
	print_stop_message('saved_language_x_successfully', $newlang['title']);
}

// ##########################################################################
if ($_REQUEST['do'] == 'edit_settings')
{
	$language = fetch_languages_array($vbulletin->GPC['dolanguageid']);

	$getoptions = convert_bits_to_array($language['options'], $vbulletin->bf_misc_languageoptions);
	$language = array_merge($language, $getoptions);

	print_form_header('language', 'update_settings');
	construct_hidden_code('dolanguageid', $vbulletin->GPC['dolanguageid']);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['language'], $language['title'], $language['languageid']));

	print_description_row($vbphrase['general_settings'], 0, 2, 'thead');
	print_input_row($vbphrase['title'], 'title', $language['title'], 0);
	print_yes_no_row($vbphrase['allow_user_selection'], 'userselect', $language['userselect']);
	print_yes_no_row($vbphrase['is_default_language'], 'isdefault', iif($vbulletin->GPC['dolanguageid'] == $vbulletin->options['languageid'], 1, 0));
	print_yes_no_row($vbphrase['enable_directional_markup_fix'], 'options[dirmark]', $language['dirmark']);
	print_label_row($vbphrase['text_direction'],
		'<label for="rb_l2r"><input type="radio" name="options[direction]" id="rb_l2r" value="1" tabindex="1"' . iif($language['direction'], ' checked="checked"') . " />$vbphrase[left_to_right]</label><br />" . '
		 <label for="rb_r2l"><input type="radio" name="options[direction]" id="rb_r2l" value="0" tabindex="1"' . iif(!($language['direction']), ' checked="checked"') . " />$vbphrase[right_to_left]</label>",
		'', 'top', 'direction'
	);
	print_input_row($vbphrase['language_code'], 'languagecode', $language['languagecode']);
	print_input_row($vbphrase['html_charset'] . "<code>&lt;meta http-equiv=&quot;Content-Type&quot; content=&quot;text/html; charset=<b>$language[charset]</b>&quot; /&gt;</code>", 'charset', $language['charset']);
	print_input_row($vbphrase['image_folder_override'], 'imagesoverride', $language['imagesoverride']);

	print_description_row($vbphrase['date_time_formatting'], 0, 2, 'thead');
	print_input_row($vbphrase['locale'], 'locale', $language['locale']);
	print_input_row($vbphrase['date_format_override'], 'dateoverride', $language['dateoverride']);
	print_input_row($vbphrase['time_format_override'], 'timeoverride', $language['timeoverride']);
	print_input_row($vbphrase['registereddate_format_override'], 'registereddateoverride', $language['registereddateoverride']);
	print_input_row($vbphrase['calformat1_format_override'], 'calformat1override', $language['calformat1override']);
	print_input_row($vbphrase['calformat2_format_override'], 'calformat2override', $language['calformat2override']);
	print_input_row($vbphrase['logdate_format_override'], 'logdateoverride', $language['logdateoverride']);

	print_description_row($vbphrase['number_formatting'], 0, 2, 'thead');
	print_input_row($vbphrase['decimal_separator'], 'decimalsep', $language['decimalsep'], 1, 3, 1);
	print_input_row($vbphrase['thousands_separator'], 'thousandsep', $language['thousandsep'], 1, 3, 1);

	print_submit_row($vbphrase['save']);

}

// ##########################################################################

if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'fieldname'  => TYPE_NOHTML,
		'pagenumber' => TYPE_UINT,
		'prev'       => TYPE_STR,
		'next'       => TYPE_STR
	));

	if ($vbulletin->GPC['prev'] != '' OR $vbulletin->GPC['next'] != '')
	{
		if ($vbulletin->GPC['prev'] != '')
		{
			$vbulletin->GPC['pagenumber'] -= 1;
		}
		else
		{
			$vbulletin->GPC['pagenumber'] += 1;
		}
	}

	if ($vbulletin->GPC['fieldname'] == '')
	{
		$vbulletin->GPC['fieldname'] = 'global';
	}

	// ***********************
	if ($vbulletin->GPC['dolanguageid'] == -2)
	{
		print_cp_redirect("phrase.php?" . $vbulletin->session->vars['sessionurl'] . "fieldname=" . $vbulletin->GPC['fieldname'], 0);
	}
	else if ($vbulletin->GPC['dolanguageid'] == 0)
	{
		$_REQUEST['do'] = 'modify';
	}
	else
	{
	// ***********************

	$perpage = 10;

	print_phrase_ref_popup_javascript();

	?>
	<script type="text/javascript">
	<!--
	function js_fetch_default(varname)
	{
		var P = eval('document.forms.cpform.P_' + varname);
		var D = eval('document.forms.cpform.D_' + varname);
		P.value = D.value;
	}

	function js_change_direction(direction, varname)
	{
		var P = eval('document.forms.cpform.P_' + varname);
		P.dir = direction;
	}
	// -->
	</script>
	<?php

	// build top options and get language info
	$languages = fetch_languages_array();
	if ($vbulletin->debug)
	{
		$mlanguages = array('-1' => array('languageid' => -1, 'title' => $vbphrase['master_language']));
		$languages = $mlanguages + $languages;
	}
	$langoptions = array();

	foreach($languages AS $langid => $lang)
	{
		$langoptions["$lang[languageid]"] = $lang['title'];
		if ($lang['languageid'] == $vbulletin->GPC['dolanguageid'])
		{
			$language = $lang;
		}
	}

	$phrasetypeoptions = array();
	$phrasetypes = fetch_phrasetypes_array();
	foreach ($phrasetypes AS $fieldname => $type)
	{
		$phrasetypeoptions["$fieldname"] = $type['title'];
	}

	print_phrase_ref_popup_javascript();

	// get custom phrases
	$numcustom = 0;
	if ($vbulletin->GPC['dolanguageid'] != -1)
	{
		$custom_phrases = fetch_custom_phrases($vbulletin->GPC['dolanguageid'], $vbulletin->GPC['fieldname']);
		$numcustom = sizeof($custom_phrases);
	}
	// get inherited and customized phrases
	$standard_phrases = fetch_standard_phrases($vbulletin->GPC['dolanguageid'], $vbulletin->GPC['fieldname'], $numcustom);

	$numstandard = sizeof($standard_phrases);
	$totalphrases = $numcustom + $numstandard;

	$numpages = ceil($totalphrases / $perpage);

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	if ($vbulletin->GPC['pagenumber'] > $numpages)
	{
		$vbulletin->GPC['pagenumber'] = $numpages;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;
	$endat = $startat + $perpage;
	if ($endat >= $totalphrases)
	{
		$endat = $totalphrases;
	}

	$i = 15;

	$p = 0;
	$pageoptions = array();
	for ($i = 0; $i < $totalphrases; $i += $perpage)
	{
		$p++;
		$firstphrase = $i;
		$lastphrase = $firstphrase + $perpage - 1;
		if ($lastphrase >= $totalphrases)
		{
			$lastphrase = $totalphrases - 1;
		}
		$pageoptions["$p"] = "$vbphrase[page] $p ";//<!--(" . ($firstphrase + 1) . " to " . ($lastphrase + 1) . ")-->";
	}

	$showprev = true;
	$shownext = true;
	if ($vbulletin->GPC['pagenumber'] == 1)
	{
		$showprev = false;
	}
	if ($vbulletin->GPC['pagenumber'] >= $numpages)
	{
		$shownext = false;
	}

	// #############################################################################

	print_form_header('language', 'edit', 0, 1, 'qform', '90%', '', 1, 'get');
	echo '
		<colgroup span="5">
			<col style="white-space:nowrap"></col>
			<col></col>
			<col></col>
			<col align="center" width="50%" style="white-space:nowrap"></col>
			<col align="center" width="50%"></col>
		</colgroup>
		<tr>
			<td class="thead">' . $vbphrase['language'] . ':</td>
			<td class="thead"><select name="dolanguageid" onchange="this.form.submit()" class="bginput">' . construct_select_options($langoptions, $vbulletin->GPC['dolanguageid']) . '</select></td>
			<td class="thead" rowspan="2"><input type="submit" class="button" style="height:40px" value="  ' . $vbphrase['go'] . '  " /></td>
			<td class="thead" rowspan="2"><!--' . $vbphrase['page'] . ':-->
				<select name="page" onchange="this.form.submit()" class="bginput">' . construct_select_options($pageoptions, $vbulletin->GPC['pagenumber']) . '</select><br />
				' . iif($showprev, ' <input type="submit" class="button" name="prev" value="&laquo; ' . $vbphrase['prev'] . '" />') . '
				' . iif($shownext, ' <input type="submit" class="button" name="next" value="' . $vbphrase['next'] . ' &raquo;" />') . '
			</td>
			<td class="thead" rowspan="2">' . "
				<input type=\"button\" class=\"button\" value=\"" . $vbphrase['view_quickref'] . "\" onclick=\"js_open_phrase_ref({$vbulletin->GPC['dolanguageid']}, '{$vbulletin->GPC['fieldname']}');\" />
				<!--<input type=\"button\" class=\"button\" value=\"" . $vbphrase['view_summary'] . "\" onclick=\"window.location='language.php?" . $vbulletin->session->vars['sessionurl'] . "do=view&amp;dolanguageid=" . $vbulletin->GPC['dolanguageid'] . "';\" />-->
				<input type=\"button\" class=\"button\" value=\"$vbphrase[set_default]\" " . iif($vbulletin->GPC['dolanguageid'] == -1 OR $vbulletin->GPC['dolanguageid'] == $vbulletin->options['languageid'], 'disabled="disabled"', "title=\"" . construct_phrase($vbphrase['set_language_as_default_x'], $language['title']) . "\" onclick=\"window.location='language.php?" . $vbulletin->session->vars['sessionurl'] . "do=setdefault&amp;dolanguageid=" . $vbulletin->GPC['dolanguageid'] . "';\"") . " />
			" . '</td>
		</tr>
		<tr>
			<td class="thead">' . $vbphrase['phrase_type'] . ':</td>
			<td class="thead"><select name="fieldname" onchange="this.form.page.selectedIndex = 0; this.form.submit()" class="bginput">' . construct_select_options($phrasetypeoptions, $vbulletin->GPC['fieldname']) . '</select></td>
		</tr>
	';
	print_table_footer();

	$printers = array();

	$i = 0;
	if ($startat < $numcustom)
	{
		for ($i = $startat; $i < $endat AND $i < $numcustom; $i++)
		{
			$printers["$i"] =& $custom_phrases["$i"];
		}
	}
	if ($i < $endat)
	{
		if ($i == 0)
		{
			$i = $startat;
		}
		for ($i; $i < $endat AND $i < $totalphrases; $i++)
		{
			$printers["$i"] =& $standard_phrases["$i"];
		}
	}

	// ******************

	print_form_header('language', 'update');
	construct_hidden_code('dolanguageid', $vbulletin->GPC['dolanguageid']);
	construct_hidden_code('fieldname', $vbulletin->GPC['fieldname']);
	construct_hidden_code('page', $vbulletin->GPC['pagenumber']);
	print_table_header(construct_phrase($vbphrase['edit_translate_x_y_phrases'], $languages["{$vbulletin->GPC['dolanguageid']}"]['title'], "<span class=\"normal\">" . $phrasetypes["{$vbulletin->GPC['fieldname']}"][title] . "</span>") . ' <span class="normal">' . construct_phrase($vbphrase['page_x_of_y'], $vbulletin->GPC['pagenumber'], $numpages) . '</span>');
	print_column_style_code(array('', '" width="20'));
	$lasttype = '';
	foreach ($printers AS $key => $blarg)
	{
		if ($lasttype != $blarg['type'])
		{
			print_label_row($vbphrase['varname'], $vbphrase['text'], 'thead');
		}
		print_phrase_row($blarg, $phrasetypes["{$vbulletin->GPC['fieldname']}"]['editrows'], $key, $language['direction']);

		$lasttype = $blarg['type'];
	}
	print_submit_row();

	// ******************

	if ($numpages > 1)
	{
		print_form_header('language', 'edit', 0, 1, 'qform', '90%', '', 1, 'get');
		construct_hidden_code('dolanguageid', $vbulletin->GPC['dolanguageid']);
		construct_hidden_code('fieldname', $vbulletin->GPC['fieldname']);
		$pagebuttons = '';
		for ($p = 1; $p <= $numpages; $p++)
		{
			$pagebuttons .= "\n\t\t\t\t<input type=\"submit\" class=\"button\" style=\"font:10px verdana\" name=\"page\" value=\"$p\" tabindex=\"1\" title=\"$vbphrase[page] $p\"" . iif($p == $vbulletin->GPC['pagenumber'], ' disabled="disabled"') . ' />';
		}
		echo '
		<tr>' . iif($showprev, '
			<td class="thead"><input type="submit" class="button" name="prev" value="&laquo; ' . $vbphrase['prev'] . '" tabindex="1" /></td>') . '
			<td class="thead" width="100%" align="center"><input type="hidden" name="page" value="' . $vbulletin->GPC['pagenumber'] . '" />' . $pagebuttons . '
			</td>' . iif($shownext, '
			<td class="thead"><input type="submit" class="button" name="next" value="' . $vbphrase['next'] . ' &raquo;" tabindex="1" /></td>') . '
		</tr>
		';
		print_table_footer();
	}

	// ***********************
	} // end if ($languageid != 0)
	// ***********************

}

// ##########################################################################

if ($_REQUEST['do'] == 'modify')
{
	/*
	$typeoptions = array();
	$phrasetypes = fetch_phrasetypes_array();
	foreach($phrasetypes AS $fieldname => $type)
	{
		$typeoptions["$fieldname"] = construct_phrase($vbphrase['x_phrases'], $type['title']);
	}
	*/

	print_form_header('language', 'add');
	construct_hidden_code('goto', "language.php?" . $vbulletin->session->vars['sessionurl']);
	print_table_header($vbphrase['language_manager'], 4);
	print_cells_row(array($vbphrase['language'], '', '', $vbphrase['default']), 1);

	if ($vbulletin->debug)
	{
		print_language_row(array('languageid' => -1, 'title' => "<i>$vbphrase[master_language]</i>"));
	}

	$languages = fetch_languages_array();

	foreach($languages AS $_languageid => $language)
	{
		print_language_row($language);
	}

	print_description_row(
		construct_link_code($vbphrase['search_phrases'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=search") .
		construct_link_code($vbphrase['view_quickref'], "javascript:js_open_phrase_ref(0,0);") .
		construct_link_code($vbphrase['rebuild_all_languages'], "language.php?" . $vbulletin->session->vars['sessionurl'] . "do=rebuild&amp;goto=language.php?" . $vbulletin->session->vars['sessionurl'])
	, 0, 4, 'thead" style="text-align:center; font-weight:normal');

	print_table_footer(4, '
		<input type="submit" class="button" value="' . $vbphrase['add_new_language'] . '" tabindex="1" />
		<input type="button" class="button" value="' . $vbphrase['download_upload_language'] . '" tabindex="1" onclick="window.location=\'language.php?do=files\';" />
	');

	print_phrase_ref_popup_javascript();

}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 62098 $
|| ####################################################################
\*======================================================================*/
?>
