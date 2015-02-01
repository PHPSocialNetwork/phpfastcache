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
define('CVS_REVISION', '$RCSfile$ - $Revision: 63163 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array(
	'timezone',
	'user',
	'cpuser',
	'holiday',
	'cppermission',
	'cpoption',
	'cprofilefield', // used for the profilefield option type
);

$specialtemplates = array(
	'banemail',
);

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

require_once(DIR . '/includes/adminfunctions_misc.php');

$vbulletin->input->clean_array_gpc('r', array(
	'varname' => TYPE_STR,
	'dogroup' => TYPE_STR,
));

// intercept direct call to do=options with $varname specified instead of $dogroup
if ($_REQUEST['do'] == 'options' AND !empty($vbulletin->GPC['varname']))
{
	if ($vbulletin->GPC['varname'] == '[all]')
	{
		// go ahead and show all settings
		$vbulletin->GPC['dogroup'] = '[all]';
	}
	else if ($group = $db->query_first("SELECT varname, grouptitle FROM " . TABLE_PREFIX . "setting WHERE varname = '" . $db->escape_string($vbulletin->GPC['varname']) . "'"))
	{
		// redirect to show the correct group and use and anchor to jump to the correct variable
		exec_header_redirect('options.php?' . $vbulletin->session->vars['sessionurl_js'] . "do=options&dogroup=$group[grouptitle]#$group[varname]");
	}
	else
	{
		// could not find a matching group - just carry on as if nothing happened
		$_REQUEST['do'] = 'options';
	}
}

require_once(DIR . '/includes/adminfunctions_options.php');
require_once(DIR . '/includes/functions_misc.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminsettings'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// query settings phrases
$settingphrase = array();
$phrases = $db->query_read("
	SELECT varname, text
	FROM " . TABLE_PREFIX . "phrase
	WHERE fieldname = 'vbsettings' AND
		languageid IN(-1, 0, " . LANGUAGEID . ")
	ORDER BY languageid ASC
");
while($phrase = $db->fetch_array($phrases))
{
	$settingphrase["$phrase[varname]"] = $phrase['text'];
}

// #############################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'options';
}

// ###################### Start download XML settings #######################

if ($_REQUEST['do'] == 'download')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'product' => TYPE_STR
	));

	$doc = get_settings_export_xml($vbulletin->GPC['product']);
/*
	$setting = array();
	$settinggroup = array();

	$groups = $db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "settinggroup
		WHERE volatile = 1
		ORDER BY displayorder, grouptitle
	");
	while ($group = $db->fetch_array($groups))
	{
		$settinggroup["$group[grouptitle]"] = $group;
	}

	$sets = $db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "setting
		WHERE volatile = 1
			AND (product = '" . $db->escape_string($vbulletin->GPC['product']) . "'" . iif($vbulletin->GPC['product'] == 'vbulletin', " OR product = ''") . ")
		ORDER BY displayorder, varname
	");
	while ($set = $db->fetch_array($sets))
	{
		$setting["$set[grouptitle]"][] = $set;
	}
	unset($set);
	$db->free_result($sets);

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_XML_Builder($vbulletin);
	$xml->add_group('settinggroups', array('product' => $vbulletin->GPC['product']));

	foreach($settinggroup AS $grouptitle => $group)
	{
		if (!empty($setting["$grouptitle"]))
		{
			$group = $settinggroup["$grouptitle"];
			$xml->add_group('settinggroup', array('name' => htmlspecialchars($group['grouptitle']), 'displayorder' => $group['displayorder'], 'product' => $group['product']));
			foreach($setting["$grouptitle"] AS $set)
			{
				$arr = array('varname' => $set['varname'], 'displayorder' => $set['displayorder']);
				if ($set['advanced'])
				{
					$arr['advanced'] = 1;
				}

				$xml->add_group('setting', $arr);
				if ($set['datatype'])
				{
					$xml->add_tag('datatype', $set['datatype']);
				}
				if ($set['optioncode'] != '')
				{
					$xml->add_tag('optioncode', $set['optioncode']);
				}
				if ($set['validationcode'])
				{
					$xml->add_tag('validationcode', $set['validationcode']);
				}
				if ($set['defaultvalue'] != '')
				{
					$xml->add_tag('defaultvalue', iif($set['varname'] == 'templateversion', $vbulletin->options['templateversion'], $set['defaultvalue']));
				}
				if ($set['blacklist'])
				{
					$xml->add_tag('blacklist', 1);
				}
				$xml->close_group();
			}
			$xml->close_group();
		}
	}

	$xml->close_group();

	$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n";

	$doc .= $xml->output();
	$xml = null;
 */
	require_once(DIR . '/includes/functions_file.php');
	file_download($doc, 'vbulletin-settings.xml', 'text/xml');
}


// ###################### Start product XML backup #######################

if ($_REQUEST['do'] == 'backup')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'product'   => TYPE_STR,
		'blacklist' => TYPE_BOOL,
	));

	$setting = array();
	$product = $vbulletin->GPC['product'];
	if (empty($product))
	{
		$product = 'vbulletin';
	}

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_XML_Builder($vbulletin);
	$xml->add_group('settings', array('product' => $product));

	$sets = $db->query_read("
		SELECT varname, value
		FROM " . TABLE_PREFIX . "setting
		WHERE (product = '" . $db->escape_string($product) . "'" . iif($product == 'vbulletin', " OR product = ''") . ")
		" . ($vbulletin->GPC['blacklist'] ? "AND blacklist = 0" : "" ). "
		ORDER BY displayorder
	");

	while ($set = $db->fetch_array($sets))
	{
		$arr = array('varname' => $set['varname']);
		$xml->add_group('setting', $arr);

		if ($set['value'] != '')
		{
			$xml->add_tag('value', $set['value']);
		}

		$xml->close_group();
	}

	$xml->close_group();

	$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n";

	$doc .= $xml->output();
	$xml = null;

	require_once(DIR . '/includes/functions_file.php');
	file_download($doc, 'vbulletin-settings.xml', 'text/xml');

}

// #############################################################################
// ajax setting value validation
if ($_POST['do'] == 'validate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'varname' => TYPE_STR,
		'setting' => TYPE_ARRAY
	));

	$varname = convert_urlencoded_unicode($vbulletin->GPC['varname']);
	$value = convert_urlencoded_unicode($vbulletin->GPC['setting']["$varname"]);

	require_once(DIR . '/includes/class_xml.php');

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('setting');
	$xml->add_tag('varname', $varname);

	if ($setting = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "setting WHERE varname = '" . $db->escape_string($varname) . "'"))
	{
		$raw_value = $value;

		$value = validate_setting_value($value, $setting['datatype']);

		$valid = exec_setting_validation_code($setting['varname'], $value, $setting['validationcode'], $raw_value);
	}
	else
	{
		$valid = 1;
	}

	$xml->add_tag('valid', $valid);
	$xml->close_group();
	$xml->print_xml();
}

// ***********************************************************************

print_cp_header($vbphrase['vbulletin_options']);

// ###################### Start do import settings XML #######################
if ($_POST['do'] == 'doimport')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'serverfile' => TYPE_STR,
		'restore'    => TYPE_BOOL,
		'blacklist'  => TYPE_BOOL,
	));

	$vbulletin->input->clean_array_gpc('f', array(
		'settingsfile' => TYPE_FILE
	));

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	// got an uploaded file?
	// do not use file_exists here, under IIS it will return false in some cases
	if (is_uploaded_file($vbulletin->GPC['settingsfile']['tmp_name']))
	{
		$xml = file_read($vbulletin->GPC['settingsfile']['tmp_name']);
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

	if ($vbulletin->GPC['restore'])
	{
		xml_restore_settings($xml, $vbulletin->GPC['blacklist']);
	}
	else
	{
		xml_import_settings($xml);
	}

	print_cp_redirect("options.php?" . $vbulletin->session->vars['sessionurl'], 0);
}

// ###################### Start import settings XML #######################
if ($_REQUEST['do'] == 'files')
{
	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'type' => TYPE_NOHTML
	));

	// download form
	print_form_header('options', 'download', 0, 1, 'downloadform', '90%', '', true, 'post" target="download');
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

	print_form_header('options', 'doimport', 1, 1, 'uploadform', '90%', '', true, 'post" onsubmit="return js_confirm_upload(this, this.settingsfile);');
	print_table_header($vbphrase['import_settings_xml_file']);
	print_upload_row($vbphrase['upload_xml_file'], 'settingsfile', 999999999);
	print_input_row($vbphrase['import_xml_file'], 'serverfile', './install/vbulletin-settings.xml');
	print_submit_row($vbphrase['import'], 0);
}

// ###################### Start kill setting group #######################
if ($_POST['do'] == 'killgroup')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'title' => TYPE_STR
	));

	// get some info
	$group = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "settinggroup
		WHERE grouptitle = '" . $db->escape_string($vbulletin->GPC['title']) . "'"
	);


	//check if the settings have different products from the group.
	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		$products_to_export = array();
		$products_to_export[$group['product']] = 1;

		// query settings from this group
		$settings = array();
		$sets = $db->query_read("
			SELECT product
			FROM " . TABLE_PREFIX . "setting
			WHERE grouptitle = '$group[grouptitle]'
		");
		while ($set = $db->fetch_array($sets))
		{
			$products_to_export[$set['product']] = 1;
		}
	}

	// query settings from this group
	$settings = array();
	$sets = $db->query_read("
		SELECT varname
		FROM " . TABLE_PREFIX . "setting
		WHERE grouptitle = '$group[grouptitle]'
	");
	while ($set = $db->fetch_array($sets))
	{
		$settings[] = $db->escape_string($set['varname']);
	}

	// build list of phrases to be deleted
	$phrases = array("settinggroup_$group[grouptitle]");
	foreach($settings AS $varname)
	{
		$phrases[] = 'setting_' . $varname . '_title';
		$phrases[] = 'setting_' . $varname . '_desc';
	}

	// delete phrases
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "phrase
		WHERE languageid IN (-1,0) AND
			fieldname = 'vbsettings' AND
			varname IN ('" . implode("', '", $phrases) . "')
	");

	// delete settings
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "setting
		WHERE varname IN ('" . implode("', '", $settings) . "')
	");

	// delete group
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "settinggroup
		WHERE grouptitle = '" . $db->escape_string($group['grouptitle']) . "'
	");

	build_options();

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		foreach (array_keys($products_to_export) as $product)
		{
			autoexport_write_settings_and_language(-1, $product);
		}
	}

	define('CP_REDIRECT', 'options.php');
	print_stop_message('deleted_setting_group_successfully');

}

// ###################### Start remove setting group #######################
if ($_REQUEST['do'] == 'removegroup')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'grouptitle' => TYPE_STR
	));

	print_delete_confirmation('settinggroup', $vbulletin->GPC['grouptitle'], 'options', 'killgroup');
}

// ###################### Start insert setting group #######################
if ($_POST['do'] == 'insertgroup')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'group' => TYPE_ARRAY
	));

	if ($s = $db->query_first("
		SELECT grouptitle
		FROM " . TABLE_PREFIX . "settinggroup
		WHERE grouptitle = '" . $db->escape_string($vbulletin->GPC['group']['grouptitle']) . "'
	"))
	{
		print_stop_message('there_is_already_group_setting_named_x', $vbulletin->GPC['group']['grouptitle']);
	}

	// insert setting place-holder
	/*insert query*/
	$db->query_write("
		INSERT INTO " . TABLE_PREFIX . "settinggroup
			(grouptitle, product)
		VALUES
			('" . $db->escape_string($vbulletin->GPC['group']['grouptitle']) . "',
			'" . $db->escape_string($vbulletin->GPC['group']['product']) . "')
	");

	$full_product_info = fetch_product_list(true);
	$product_version = $full_product_info[$vbulletin->GPC['group']['product']]['version'];

	// insert associated phrases
	$languageid = iif($vbulletin->GPC['group']['volatile'], -1, 0);
	/*insert query*/
	$db->query_write("
		INSERT INTO " . TABLE_PREFIX . "phrase
			(languageid, fieldname, varname, text, product, username, dateline, version)
		VALUES
			($languageid,
			'vbsettings',
			'settinggroup_" . $db->escape_string($vbulletin->GPC['group']['grouptitle']) . "',
			'" . $db->escape_string($vbulletin->GPC['group']['title']) . "',
			'" . $db->escape_string($vbulletin->GPC['group']['product']) . "',
			'" . $db->escape_string($vbulletin->userinfo['username']) . "',
			" . TIMENOW . ",
			'" . $db->escape_string($product_version) . "')
	");

	// fall through to 'updategroup' for the real work...
	$_POST['do'] = 'updategroup';
}

// ###################### Start update setting group #######################
if ($_POST['do'] == 'updategroup')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'group' => TYPE_ARRAY,
		'oldproduct' => TYPE_STR
	));

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "settinggroup SET
			displayorder = " . intval($vbulletin->GPC['group']['displayorder']) . ",
			volatile = " . intval($vbulletin->GPC['group']['volatile']) . ",
			product = '" . $db->escape_string($vbulletin->GPC['group']['product']) . "'
		WHERE grouptitle = '" . $db->escape_string($vbulletin->GPC['group']['grouptitle']) . "'
	");

	$full_product_info = fetch_product_list(true);
	$product_version = $full_product_info[$vbulletin->GPC['group']['product']]['version'];

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "phrase SET
			text = '" . $db->escape_string($vbulletin->GPC['group']['title']) . "',
			product = '" . $db->escape_string($vbulletin->GPC['group']['product']) . "',
			username = '" . $db->escape_string($vbulletin->userinfo['username']) . "',
			dateline = " . TIMENOW . ",
			version = '" . $db->escape_string($product_version) . "'
		WHERE languageid IN(-1, 0)
			AND varname = 'settinggroup_" . $db->escape_string($vbulletin->GPC['group']['grouptitle']) . "'
	");

	$settingnames = array();
	$phrasenames = array();

	$settings = $db->query_read("
		SELECT varname, product
		FROM " . TABLE_PREFIX . "setting
		WHERE grouptitle = '" . $db->escape_string($vbulletin->GPC['group']['grouptitle']) . "'
		AND product = '" . $db->escape_string($vbulletin->GPC['oldproduct']) . "'
	");
	while ($setting = $db->fetch_array($settings))
	{
		$settingnames[] = "'" . $db->escape_string($setting['varname']) . "'";
		$phrasenames[] = "'" . $db->escape_string('setting_' . $setting['varname'] . '_desc') . "'";
		$phrasenames[] = "'" . $db->escape_string('setting_' . $setting['varname'] . '_title') . "'";
	}
	if ($db->num_rows($settings))
	{
		$full_product_info = fetch_product_list(true);
		$product_version = $full_product_info[$vbulletin->GPC['group']['product']]['version'];

		$q1 = "
			UPDATE " . TABLE_PREFIX . "setting SET
				product = '" . $db->escape_string($vbulletin->GPC['group']['product']) . "'
			WHERE varname IN(
				" . implode(",\n				", $settingnames) . ")
		";
		$db->query_write($q1);

		$q2 = "
			UPDATE " . TABLE_PREFIX . "phrase SET
				product = '" . $db->escape_string($vbulletin->GPC['group']['product']) . "',
				username = '" . $db->escape_string($vbulletin->userinfo['username']) . "',
				dateline = " . TIMENOW . ",
				version = '" . $db->escape_string($product_version) . "'
			WHERE varname IN(
				" . implode(",\n				", $phrasenames) . "
			) AND fieldname = 'vbsettings'
		";
		$db->query_write($q2);
	}

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		autoexport_write_settings_and_language(-1,
			array($vbulletin->GPC['oldproduct'], $vbulletin->GPC['group']['product']));
	}

	define('CP_REDIRECT', 'options.php?do=options&amp;dogroup=' . $vbulletin->GPC['group']['grouptitle']);
	print_stop_message('saved_setting_group_x_successfully', $vbulletin->GPC['group']['title']);
}

// ###################### Start edit setting group #######################
if ($_REQUEST['do'] == 'editgroup' OR $_REQUEST['do'] == 'addgroup')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'grouptitle' => TYPE_STR,
	));

	if ($_REQUEST['do'] == 'editgroup')
	{
		$group = $db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "settinggroup
			WHERE grouptitle = '" . $db->escape_string($vbulletin->GPC['grouptitle']) . "'
		");
		$phrase = $db->query_first("
			SELECT text FROM " . TABLE_PREFIX . "phrase
			WHERE languageid IN (-1,0) AND
				fieldname = 'vbsettings' AND
				varname = 'settinggroup_" . $db->escape_string($group['grouptitle']) . "'
		");
		$group['title'] = $phrase['text'];
		$pagetitle = construct_phrase($vbphrase['x_y_id_z'], $vbphrase['setting_group'], $group['title'], $group['grouptitle']);
		$formdo = 'updategroup';
	}
	else
	{
		$ordercheck = $db->query_first("
			SELECT displayorder
			FROM " . TABLE_PREFIX . "settinggroup
			ORDER BY displayorder DESC
		");
		$group = array(
			'displayorder' => $ordercheck['displayorder'] + 10,
			'volatile' => iif($vbulletin->debug, 1, 0)
		);
		$pagetitle = $vbphrase['add_new_setting_group'];
		$formdo = 'insertgroup';
	}

	print_form_header('options', $formdo);
	print_table_header($pagetitle);
	if ($_REQUEST['do'] == 'editgroup')
	{
		print_label_row($vbphrase['varname'], "<b>$group[grouptitle]</b>");
		construct_hidden_code('group[grouptitle]', $group['grouptitle']);
	}
	else
	{
		print_input_row($vbphrase['varname'], 'group[grouptitle]', $group['grouptitle']);
	}
	print_input_row($vbphrase['title'], 'group[title]', $group['title']);
	construct_hidden_code('oldproduct', $group['product']);
	print_select_row($vbphrase['product'], 'group[product]', fetch_product_list(), $group['product']);
	print_input_row($vbphrase['display_order'], 'group[displayorder]', $group['displayorder']);
	if ($vbulletin->debug)
	{
		print_yes_no_row($vbphrase['vbulletin_default'], 'group[volatile]', $group['volatile']);
	}
	else
	{
		construct_hidden_code('group[volatile]', $group['volatile']);
	}
	print_submit_row($vbphrase['save']);

}

// ###################### Start kill setting #######################
if ($_POST['do'] == 'killsetting')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'title' => TYPE_STR
	));

	// get some info
	$setting = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "setting
		WHERE varname = '" . $db->escape_string($vbulletin->GPC['title']) . "'"
	);

	// delete phrases
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "phrase
		WHERE languageid IN (-1, 0) AND
			fieldname = 'vbsettings' AND
			varname IN ('setting_" . $db->escape_string($setting['varname']) . "_title',
				'setting_" . $db->escape_string($setting['varname']) . "_desc')
	");

	// delete setting
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "setting
		WHERE varname = '" . $db->escape_string($setting['varname']) . "'"
	);
	build_options();

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		autoexport_write_settings_and_language(-1, $setting['product']);
	}

	define('CP_REDIRECT', 'options.php?do=options&amp;dogroup=' . $setting['grouptitle']);
	print_stop_message('deleted_setting_successfully');
}

// ###################### Start remove setting #######################
if ($_REQUEST['do'] == 'removesetting')
{
	print_delete_confirmation('setting', $vbulletin->GPC['varname'], 'options', 'killsetting');
}

// ###################### Start insert setting #######################
if ($_POST['do'] == 'insertsetting')
{
	$vbulletin->input->clean_array_gpc('p', array(
		// setting stuff
		'varname'        => TYPE_STR,
		'grouptitle'     => TYPE_STR,
		'optioncode'     => TYPE_STR,
		'defaultvalue'   => TYPE_STR,
		'displayorder'   => TYPE_UINT,
		'volatile'       => TYPE_INT,
		'datatype'       => TYPE_STR,
		'validationcode' => TYPE_STR,
		'product'        => TYPE_STR,
		'blacklist'      => TYPE_BOOL,
		// phrase stuff
		'title'          => TYPE_STR,
		'description'    => TYPE_STR,
		// old product -- this doesn't actually appear to be set on the form
		//    or used anywhere
		'oldproduct'     => TYPE_STR
	));

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	if ($s = $db->query_first("
		SELECT varname
		FROM " . TABLE_PREFIX . "setting
		WHERE varname = '" . $db->escape_string($vbulletin->GPC['varname']) . "'
	"))
	{
		print_stop_message('there_is_already_setting_named_x', $vbulletin->GPC['varname']);
	}

	if (!preg_match('#^[a-z0-9_]+$#i', $vbulletin->GPC['varname'])) // match a-z, A-Z, 0-9, _ only
	{
		print_stop_message('invalid_phrase_varname');
	}

	// insert setting place-holder
	/*insert query*/
	$db->query_write("
		INSERT INTO " . TABLE_PREFIX . "setting
			(varname, value, product)
		VALUES
			('" . $db->escape_string($vbulletin->GPC['varname']) . "',
			'" . $db->escape_string($vbulletin->GPC['defaultvalue']) . "',
			'" . $db->escape_string($vbulletin->GPC['product']) . "')
	");

	$full_product_info = fetch_product_list(true);
	$product_version = $full_product_info[$vbulletin->GPC['product']]['version'];

	// insert associated phrases
	$languageid = iif($vbulletin->GPC['volatile'], -1, 0);

	/*insert query*/
	$db->query_write("
		INSERT INTO " . TABLE_PREFIX . "phrase
			(languageid, fieldname, varname, text, product, username, dateline, version)
		VALUES
			($languageid,
			'vbsettings',
			'setting_" . $db->escape_string($vbulletin->GPC['varname']) . "_title',
			'" . $db->escape_string($vbulletin->GPC['title']) . "',
			'" . $db->escape_string($vbulletin->GPC['product']) . "',
			'" . $db->escape_string($vbulletin->userinfo['username']) . "',
			" . TIMENOW . ",
			'" . $db->escape_string($product_version) . "')
	");
	/*insert query*/
	$db->query_write("
		INSERT INTO " . TABLE_PREFIX . "phrase
			(languageid, fieldname, varname, text, product, username, dateline, version)
		VALUES
			($languageid,
			'vbsettings',
			'setting_" . $db->escape_string($vbulletin->GPC['varname']) . "_desc',
			'" . $db->escape_string($vbulletin->GPC['description']) . "',
			'" . $db->escape_string($vbulletin->GPC['product']) . "',
			'" . $db->escape_string($vbulletin->userinfo['username']) . "',
			" . TIMENOW . ",
			'" . $db->escape_string($product_version) . "')
	");

	// fall through to 'updatesetting' for the real work...
	$_POST['do'] = 'updatesetting';
}

// ###################### Start update setting #######################
if ($_POST['do'] == 'updatesetting')
{
	$vbulletin->input->clean_array_gpc('p', array(
		// setting stuff
		'varname'        => TYPE_STR,
		'grouptitle'     => TYPE_STR,
		'optioncode'     => TYPE_STR,
		'defaultvalue'   => TYPE_STR,
		'displayorder'   => TYPE_UINT,
		'volatile'       => TYPE_INT,
		'datatype'       => TYPE_STR,
		'validationcode' => TYPE_STR,
		'product'        => TYPE_STR,
		'blacklist'      => TYPE_BOOL,
		// phrase stuff
		'title'          => TYPE_STR,
		'description'    => TYPE_STR,
		// old product -- this doesn't actually appear to be set on the form
		//    or used anywhere
		'oldproduct'     => TYPE_STR
	));

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		$old_setting = $db->query_first("
			SELECT product
			FROM " . TABLE_PREFIX . "setting
			WHERE varname = '" . $db->escape_string($vbulletin->GPC['varname']) . "'
		");
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "setting SET
			grouptitle = '" . $db->escape_string($vbulletin->GPC['grouptitle']) . "',
			optioncode = '" . $db->escape_string($vbulletin->GPC['optioncode']) . "',
			defaultvalue = '" . $db->escape_string($vbulletin->GPC['defaultvalue']) . "',
			displayorder = " . $vbulletin->GPC['displayorder'] . ",
			volatile = " . $vbulletin->GPC['volatile'] . ",
			datatype = '" . $db->escape_string($vbulletin->GPC['datatype']) . "',
			validationcode = '" . $db->escape_string($vbulletin->GPC['validationcode']) . "',
			product = '" . $db->escape_string($vbulletin->GPC['product']) . "',
			blacklist = " . intval($vbulletin->GPC['blacklist']) . "
		WHERE varname = '" . $db->escape_string($vbulletin->GPC['varname']) . "'
	");

	$newlang = iif($vbulletin->GPC['volatile'], -1, 0);

	$phrases = $db->query_read("
		SELECT varname, text, languageid, product
		FROM " . TABLE_PREFIX . "phrase
		WHERE languageid IN (-1,0)
			AND fieldname = 'vbsettings'
			AND varname IN ('setting_" . $db->escape_string($vbulletin->GPC['varname']) . "_title', 'setting_" . $db->escape_string($vbulletin->GPC['varname']) . "_desc')
	");

	$full_product_info = fetch_product_list(true);
	$product_version = $full_product_info[$vbulletin->GPC['product']]['version'];

	while ($phrase = $db->fetch_array($phrases))
	{
		if ($phrase['varname'] == "setting_" . $vbulletin->GPC['varname'] . "_title")
		{
			$q = "
				UPDATE " . TABLE_PREFIX . "phrase SET
					languageid = " . iif($vbulletin->GPC['volatile'], -1, 0) . ",
					text = '" . $db->escape_string($vbulletin->GPC['title']) . "',
					product = '" . $db->escape_string($vbulletin->GPC['product']) . "',
					username = '" . $db->escape_string($vbulletin->userinfo['username']) . "',
					dateline = " . TIMENOW . ",
					version = '" . $db->escape_string($product_version) . "'
				WHERE languageid = $phrase[languageid]
					AND varname = 'setting_" . $db->escape_string($vbulletin->GPC['varname']) . "_title'
			";
			$db->query_write($q);
		}
		else if ($phrase['varname'] == "setting_" . $vbulletin->GPC['varname'] . "_desc")
		{
			$q = "
				UPDATE " . TABLE_PREFIX . "phrase SET
					languageid = " . iif($vbulletin->GPC['volatile'], -1, 0) . ",
					text = '" . $db->escape_string($vbulletin->GPC['description']) . "',
					product = '" . $db->escape_string($vbulletin->GPC['product']) . "',
					username = '" . $db->escape_string($vbulletin->userinfo['username']) . "',
					dateline = " . TIMENOW . ",
					version = '" . $db->escape_string($product_version) . "'
				WHERE languageid = $phrase[languageid]
					AND varname = 'setting_" . $db->escape_string($vbulletin->GPC['varname']) . "_desc'
			";
			$db->query_write($q);
		}
	}

	build_options();

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		autoexport_write_settings_and_language(($vbulletin->GPC['volatile'] ? -1 : 0),
			array($old_setting['product'], $vbulletin->GPC['product']));
	}

	define('CP_REDIRECT', 'options.php?do=options&amp;dogroup=' . $vbulletin->GPC['grouptitle']);
	print_stop_message('saved_setting_x_successfully', $vbulletin->GPC['title']);
}

// ###################### Start edit / add setting #######################
if ($_REQUEST['do'] == 'editsetting' OR $_REQUEST['do'] == 'addsetting')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'grouptitle' => TYPE_STR
	));

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	$product = '';
	$settinggroups = array();
	$groups = $db->query_read("SELECT grouptitle, product FROM " . TABLE_PREFIX . "settinggroup ORDER BY displayorder");
	while ($group = $db->fetch_array($groups))
	{
		$settinggroups["$group[grouptitle]"] = $settingphrase["settinggroup_$group[grouptitle]"];
		if ($group['grouptitle'] == $vbulletin->GPC['grouptitle'])
		{
			$product = $group['product'];
		}
	}

	if ($_REQUEST['do'] == 'editsetting')
	{
		$setting = $db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "setting
			WHERE varname = '" . $db->escape_string($vbulletin->GPC['varname']) . "'
		");
		$phrases = $db->query_read("
			SELECT varname, text
			FROM " . TABLE_PREFIX . "phrase
			WHERE languageid = " . iif($setting['volatile'], -1, 0) . " AND
				fieldname = 'vbsettings' AND
			varname IN ('setting_" . $db->escape_string($setting['varname']) . "_title', 'setting_" . $db->escape_string($setting['varname']) . "_desc')
		");
		while ($phrase = $db->fetch_array($phrases))
		{
			if ($phrase['varname'] == "setting_$setting[varname]_title")
			{
				$setting['title'] = $phrase['text'];
			}
			else if ($phrase['varname'] == "setting_$setting[varname]_desc")
			{
				$setting['description'] = $phrase['text'];
			}
		}
		$pagetitle = construct_phrase($vbphrase['x_y_id_z'], $vbphrase['setting'], $setting['title'], $setting['varname']);
		$formdo = 'updatesetting';
	}
	else
	{
		$ordercheck = $db->query_first("
			SELECT displayorder FROM " . TABLE_PREFIX . "setting
			WHERE grouptitle='" . $db->escape_string($vbulletin->GPC['grouptitle']) . "'
			ORDER BY displayorder DESC
		");

		$setting = array(
			'grouptitle'   => $vbulletin->GPC['grouptitle'],
			'displayorder' => $ordercheck['displayorder'] + 10,
			'volatile'     => $vbulletin->debug ? 1 : 0,
			'product'      => $product,
		);
		$pagetitle = $vbphrase['add_new_setting'];
		$formdo = 'insertsetting';
	}

	print_form_header('options', $formdo);
	print_table_header($pagetitle);
	if ($_REQUEST['do'] == 'editsetting')
	{
		construct_hidden_code('varname', $setting['varname']);
		print_label_row($vbphrase['varname'], "<b>$setting[varname]</b>");
	}
	else
	{
		print_input_row($vbphrase['varname'], 'varname', $setting['varname']);
	}
	print_select_row($vbphrase['setting_group'], 'grouptitle', $settinggroups, $setting['grouptitle']);
	print_select_row($vbphrase['product'], 'product', fetch_product_list(), $setting['product']);
	print_input_row($vbphrase['title'], 'title', $setting['title']);
	print_textarea_row($vbphrase['description'], 'description', $setting['description'], 4, '50" style="width:100%');
	print_textarea_row($vbphrase['option_code'], 'optioncode', $setting['optioncode'], 4, '50" style="width:100%');
	print_textarea_row($vbphrase['default'], 'defaultvalue', $setting['defaultvalue'], 4, '50" style="width:100%');

	switch ($setting['datatype'])
	{
		case 'number':
			$checked = array('number' => ' checked="checked"');
			break;
		case 'integer':
			$checked = array('integer' => ' checked="checked"');
			break;
		case 'posint':
			$checked = array('posint' => ' checked="checked"');
			break;
		case 'boolean':
			$checked = array('boolean' => ' checked="checked"');
			break;
		case 'bitfield':
			$checked= array('bitfield' => ' checked="checked"');
			break;
		case 'username':
			$checked= array('username' => ' checked="checked"');
			break;
		default:
			$checked = array('free' => ' checked="checked"');
	}
	print_label_row($vbphrase['data_validation_type'], '
		<div class="smallfont">
		<label for="rb_dt_free"><input type="radio" name="datatype" id="rb_dt_free" tabindex="1" value="free"' . $checked['free'] . ' />' . $vbphrase['datatype_free'] . '</label>
		<label for="rb_dt_number"><input type="radio" name="datatype" id="rb_dt_number" tabindex="1" value="number"' . $checked['number'] . ' />' . $vbphrase['datatype_numeric'] . '</label>
		<label for="rb_dt_integer"><input type="radio" name="datatype" id="rb_dt_integer" tabindex="1" value="integer"' . $checked['integer'] . ' />' . $vbphrase['datatype_integer'] . '</label>
		<label for="rb_dt_posint"><input type="radio" name="datatype" id="rb_dt_posint" tabindex="1" value="posint"' . $checked['posint'] . ' />' . $vbphrase['datatype_posint'] . '</label>
		<label for="rb_dt_boolean"><input type="radio" name="datatype" id="rb_dt_boolean" tabindex="1" value="boolean"' . $checked['boolean'] . ' />' . $vbphrase['datatype_boolean'] . '</label>
		<label for="rb_dt_bitfield"><input type="radio" name="datatype" id="rb_dt_bitfield" tabindex="1" value="bitfield"' . $checked['bitfield'] . ' />' . $vbphrase['datatype_bitfield'] . '</label>
		<label for="rb_dt_username"><input type="radio" name="datatype" id="rb_dt_username" tabindex="1" value="username"' . $checked['username'] . ' />' . $vbphrase['datatype_username'] . '</label>
		</div>
	');
	print_textarea_row($vbphrase['validation_php_code'], 'validationcode', $setting['validationcode'], 4, '50" style="width:100%');

	print_input_row($vbphrase['display_order'], 'displayorder', $setting['displayorder']);
	print_yes_no_row($vbphrase['blacklist'], 'blacklist', $setting['blacklist']);
	if ($vbulletin->debug)
	{
		print_yes_no_row($vbphrase['vbulletin_default'], 'volatile', $setting['volatile']);
	}
	else
	{
		construct_hidden_code('volatile', $setting['volatile']);
	}
	print_submit_row($vbphrase['save']);
}

// ###################### Start do options #######################
if ($_POST['do'] == 'dooptions')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'setting'  => TYPE_ARRAY,
		'advanced' => TYPE_BOOL
	));

	if (!empty($vbulletin->GPC['setting']))
	{
		save_settings($vbulletin->GPC['setting']);

		define('CP_REDIRECT', 'options.php?do=options&amp;dogroup=' . $vbulletin->GPC['dogroup'] . '&amp;advanced=' . $vbulletin->GPC['advanced']);
		print_stop_message('saved_settings_successfully');
	}
	else
	{
		print_stop_message('nothing_to_do');
	}

}

// ###################### Start modify options #######################
if ($_REQUEST['do'] == 'options')
{
	require_once(DIR . '/includes/adminfunctions_language.php');

	$vbulletin->input->clean_array_gpc('r', array(
		'advanced' => TYPE_BOOL,
		'expand'   => TYPE_BOOL,
	));

	echo '<script type="text/javascript" src="../clientscript/vbulletin_cpoptions_scripts.js?v=' . SIMPLE_VERSION . '"></script>';

	// display links to settinggroups and create settingscache
	$settingscache = array();
	$options = array('[all]' => '-- ' . $vbphrase['show_all_settings'] . ' --');
	$lastgroup = '';

	$settings = $db->query_read("
		SELECT setting.*, settinggroup.grouptitle
		FROM " . TABLE_PREFIX . "settinggroup AS settinggroup
		LEFT JOIN " . TABLE_PREFIX . "setting AS setting USING(grouptitle)
		" . iif($vbulletin->debug, '', 'WHERE settinggroup.displayorder <> 0') . "
		ORDER BY settinggroup.displayorder, setting.displayorder
	");

	if (empty($vbulletin->GPC['dogroup']) AND $vbulletin->GPC['expand'])
	{
		while ($setting = $db->fetch_array($settings))
		{

			$settingscache["$setting[grouptitle]"]["$setting[varname]"] = $setting;
			if ($setting['grouptitle'] != $lastgroup)
			{
				$grouptitlecache["$setting[grouptitle]"] = $setting['grouptitle'];
				$grouptitle = $settingphrase["settinggroup_$setting[grouptitle]"];
			}
			$options["$grouptitle"]["$setting[varname]"] = $settingphrase["setting_$setting[varname]_title"];
			$lastgroup = $setting['grouptitle'];
		}

		$altmode = 0;
		$linktext =& $vbphrase['collapse_setting_groups'];
	}
	else
	{
		while ($setting = $db->fetch_array($settings))
		{

			$settingscache["$setting[grouptitle]"]["$setting[varname]"] = $setting;
			if ($setting['grouptitle'] != $lastgroup)
			{
				$grouptitlecache["$setting[grouptitle]"] = $setting['grouptitle'];
				$options["$setting[grouptitle]"] = $settingphrase["settinggroup_$setting[grouptitle]"];
			}
			$lastgroup = $setting['grouptitle'];
		}

		$altmode = 1;
		$linktext =& $vbphrase['expand_setting_groups'];
	}
	$db->free_result($settings);

	$optionsmenu = "\n\t<select name=\"" . iif($vbulletin->GPC['expand'], 'varname', 'dogroup') . "\" class=\"bginput\" tabindex=\"1\" " . iif(empty($vbulletin->GPC['dogroup']), 'ondblclick="this.form.submit();" size="20"', 'onchange="this.form.submit();"') . " style=\"width:350px\">\n" . construct_select_options($options, iif($vbulletin->GPC['dogroup'], $vbulletin->GPC['dogroup'], '[all]')) . "\t</select>\n\t";

	print_form_header('options', 'options', 0, 1, 'groupForm', '90%', '', 1, 'get');

	if (empty($vbulletin->GPC['dogroup'])) // show the big <select> with no options
	{
		print_table_header($vbphrase['vbulletin_options']);
		print_label_row($vbphrase['settings_to_edit'] .
			iif($vbulletin->debug,
				'<br /><table><tr><td><fieldset><legend>Developer Options</legend>
				<div style="padding: 2px"><a href="options.php?' . $vbulletin->session->vars['sessionurl'] . 'do=addgroup">' . $vbphrase['add_new_setting_group'] . '</a></div>
				<div style="padding: 2px"><a href="options.php?' . $vbulletin->session->vars['sessionurl'] . 'do=files">' . $vbphrase['download_upload_settings'] . '</a></div>' .
				'</fieldset></td></tr></table>'
			) .
			"<p><a href=\"options.php?" . $vbulletin->session->vars['sessionurl'] . "expand=$altmode\">$linktext</a></p>
			<p><a href=\"options.php?" . $vbulletin->session->vars['sessionurl'] . "do=backuprestore\">" . $vbphrase['backup_restore_settings'] . "</a>", $optionsmenu);
		print_submit_row($vbphrase['edit_settings'], 0);
	}
	else // show the small list with selected setting group(s) options
	{
		print_table_header("$vbphrase[setting_group] $optionsmenu <input type=\"submit\" value=\"$vbphrase[go]\" class=\"button\" tabindex=\"1\" />");
		print_table_footer();

		// show selected settings
		print_form_header('options', 'dooptions', false, true, 'optionsform', '90%', '', true, 'post" onsubmit="return count_errors()');
		construct_hidden_code('dogroup', $vbulletin->GPC['dogroup']);
		construct_hidden_code('advanced', $vbulletin->GPC['advanced']);

		if ($vbulletin->GPC['dogroup'] == '[all]') // show all settings groups
		{
			foreach ($grouptitlecache AS $curgroup => $group)
			{
				print_setting_group($curgroup, $vbulletin->GPC['advanced']);
				echo '<tbody>';
				print_description_row("<input type=\"submit\" class=\"button\" value=\" $vbphrase[save] \" tabindex=\"1\" title=\"" . $vbphrase['save_settings'] . "\" />", 0, 2, 'tfoot" style="padding:1px" align="right');
				echo '</tbody>';
				print_table_break(' ');
			}
		}
		else
		{
			print_setting_group($vbulletin->GPC['dogroup'], $vbulletin->GPC['advanced']);
		}

		print_submit_row($vbphrase['save']);

		?>
		<div id="error_output" style="font: 10pt courier new"></div>
		<script type="text/javascript">
		<!--
		var error_confirmation_phrase = "<?php echo $vbphrase['error_confirmation_phrase']; ?>";
		//-->
		</script>
		<script type="text/javascript" src="../clientscript/vbulletin_settings_validate.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
		<?php
	}
}

// ###################### Start modify options #######################
if ($_REQUEST['do'] == 'backuprestore')
{
	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	// download form
	print_form_header('options', 'backup', 0, 1, 'downloadform', '90%', 'backup');
	print_table_header($vbphrase['backup']);
	print_select_row($vbphrase['product'], 'product', fetch_product_list());
	print_yes_no_row($vbphrase['ignore_blacklisted_settings'], 'blacklist', 1);
	print_submit_row($vbphrase['backup']);

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

	print_form_header('options', 'doimport', 1, 1, 'uploadform', '90%', '', true, 'post" onsubmit="return js_confirm_upload(this, this.settingsfile);');
	construct_hidden_code('restore', 1);
	print_table_header($vbphrase['restore_settings_xml_file']);
	print_yes_no_row($vbphrase['ignore_blacklisted_settings'], 'blacklist', 1);
	print_upload_row($vbphrase['upload_xml_file'], 'settingsfile', 999999999);
	print_input_row($vbphrase['restore_xml_file'], 'serverfile', './install/vbulletin-settings.xml');
	print_submit_row($vbphrase['restore'], 0);
}

// #################### Start Change Search Type #####################
if ($_REQUEST['do'] == 'searchtype')
{
	require_once(DIR . '/includes/class_dbalter.php');

	$db_alter = new vB_Database_Alter_MySQL($db);
	print_form_header('options', 'dosearchtype');
	print_table_header("$vbphrase[search_type]");

	print_select_row($vbphrase["select_search_implementation"], 'implementation',
		fetch_search_implementation_list(), $vbulletin->options['searchimplementation']);

	print_description_row($vbphrase['search_reindex_required']);
	print_submit_row($vbphrase['go'], 0);
}

// #################### Start Change Search Type #####################
if ($_POST['do'] == 'dosearchtype')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'implementation' => TYPE_NOHTML
	));

	$options = fetch_search_implementation_list();
	if (!array_key_exists($vbulletin->GPC['implementation'], $options))
	{
		print_stop_message('invalid_search_implementation');
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "setting
		SET value = '" . $db->escape_string($vbulletin->GPC['implementation']) . "'
		WHERE varname = 'searchimplementation'
	");
	build_options();
	define('CP_REDIRECT', 'index.php');
	print_stop_message('saved_settings_successfully');
}

function fetch_search_implementation_list()
{
	global $vbphrase;
	$options['vBDBSearch_Core'] = $vbphrase['db_search_implementation'];
	//sets any additional options
  ($hook = vBulletinHook::fetch_hook('admin_search_options')) ? eval($hook) : false;
	return $options;
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63163 $
|| ####################################################################
\*======================================================================*/
?>