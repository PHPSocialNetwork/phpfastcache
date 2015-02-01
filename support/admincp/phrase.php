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
define('CVS_REVISION', '$RCSfile$ - $Revision: 63231 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('language');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_language.php');
require_once(DIR . '/includes/functions_misc.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminlanguages'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'phraseid' => TYPE_INT,
));

// ############################# LOG ACTION ###############################
log_admin_action(iif($vbulletin->GPC['phraseid'], "phrase id = " . $vbulletin->GPC['phraseid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

$full_product_info = fetch_product_list(true);

// #############################################################################

if ($_REQUEST['do'] == 'quickref')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'languageid' => TYPE_INT,
		'fieldname'  => TYPE_NOHTML,
	));

	if ($vbulletin->GPC['languageid'] == 0)
	{
		$vbulletin->GPC['languageid'] = $vbulletin->options['languageid'];
	}
	if ($vbulletin->GPC['fieldname'] == '')
	{
		$vbulletin->GPC['fieldname'] = 'global';
	}

	$languages = fetch_languages_array();
	if ($vbulletin->debug)
	{
		$langoptions['-1'] = $vbphrase['master_language'];
	}
	foreach($languages AS $id => $lang)
	{
		$langoptions["$id"] = $lang['title'];
	}
	$phrasetypes = fetch_phrasetypes_array();
	foreach($phrasetypes AS $fieldname => $type)
	{
		$typeoptions["$fieldname"] = $type['title'] . ' ' . $vbphrase['phrases'];
	}

	define('NO_PAGE_TITLE', true);
	print_cp_header("$vbphrase[quickref] {$langoptions["{$vbulletin->GPC['languageid']}"]} {$typeoptions["{$vbulletin->GPC['fieldname']}"]}", '', '', 0);

	$phrasearray = array();

	if ($vbulletin->GPC['languageid'] != -1)
	{
		$custom = fetch_custom_phrases($vbulletin->GPC['languageid'], $vbulletin->GPC['fieldname']);
		if (!empty($custom))
		{
			foreach($custom AS $phrase)
			{
				$phrasearray[htmlspecialchars_uni($phrase['text'])] = $phrase['varname'];
			}
		}
	}

	$standard = fetch_standard_phrases($vbulletin->GPC['languageid'], $vbulletin->GPC['fieldname']);

	if (is_array($standard))
	{
		foreach($standard AS $phrase)
		{
			$phrasearray[htmlspecialchars_uni($phrase['text'])] = $phrase['varname'];
		}
		$tval = $langoptions["{$vbulletin->GPC['languageid']}"] . ' ' . $typeoptions["{$vbulletin->GPC['fieldname']}"];
	}
	else
	{
		$tval = construct_phrase($vbphrase['no_x_phrases_defined'], '<i>' . $typeoptions["{$vbulletin->GPC['fieldname']}"] . '</i>');
	}

	$directionHtml = 'dir="' . $languages["{$vbulletin->GPC['languageid']}"]['direction'] . '"';

	print_form_header('phrase', 'quickref', 0, 1, 'cpform', '100%', '', 0);
	print_table_header($vbphrase['quickref'] . ' </b>' . $langoptions["{$vbulletin->GPC['languageid']}"] . ' ' . $typeoptions["{$vbulletin->GPC['fieldname']}"] . '<b>');
	print_label_row("<select size=\"10\" class=\"bginput\" onchange=\"
		if (this.options[this.selectedIndex].value != '')
		{
			this.form.tvar.value = '\$" . "vbphrase[' + this.options[this.selectedIndex].text + ']';
			this.form.tbox.value = this.options[this.selectedIndex].value;
		}
		\">" . construct_select_options($phrasearray) . '</select>','
		<input type="text" class="bginput" name="tvar" size="35" class="button" /><br />
		<textarea name="tbox" class="darkbg" style="font: 11px verdana" rows="8" cols="35" ' . $directionHtml . '>' . $tval . '</textarea>
		');
	print_description_row('
		<center>
		<select name="languageid" accesskey="l" class="bginput">' . construct_select_options($langoptions, $vbulletin->GPC['languageid']) . '</select>
		<select name="fieldname" accesskey="t" class="bginput">' . construct_select_options($typeoptions, $vbulletin->GPC['fieldname']) . '</select>
		<input type="submit" class="button" value="' . $vbphrase['view'] . '" accesskey="s" />
		<input type="button" class="button" value="' . $vbphrase['close'] . '" accesskey="c" onclick="self.close()" />
		</center>
	', 0, 2, 'thead');
	print_table_footer();

	unset($DEVDEBUG);
	print_cp_footer();

}

// #############################################################################

if ($_POST['do'] == 'completeorphans')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'del'  => TYPE_ARRAY_STR,  // phrases to delete
		'keep' => TYPE_ARRAY_UINT, // phrases to keep
	));

	if (!empty($vbulletin->GPC['del']))
	{
		$delcondition = array();

		foreach ($vbulletin->GPC['del'] AS $key)
		{
			fetch_varname_fieldname($key, $varname, $fieldname);
			$delcondition[] = "(varname = '" . $db->escape_string($varname) . "' AND fieldname = '" . $db->escape_string($fieldname) . "')";
		}

		$q = "
			DELETE FROM " . TABLE_PREFIX . "phrase
			WHERE " . implode("\nOR ", $delcondition);

		$db->query_write($q);
	}

	if (!empty($vbulletin->GPC['keep']))
	{
		$insertsql = array();

		$phrases = $db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "phrase
			WHERE phraseid IN(" . implode(', ', $vbulletin->GPC['keep']) . ")
		");
		while ($phrase = $db->fetch_array($phrases))
		{
			$insertsql[] = "
				(0,
				'" . $db->escape_string($phrase['fieldname']) . "',
				'" . $db->escape_string($phrase['varname']) . "',
				'" . $db->escape_string($phrase['text']) . "',
				'" . $db->escape_string($phrase['product']) . "',
				'" . $db->escape_string($phrase['username']) . "',
				$phrase[dateline],
				'" . $db->escape_string($phrase['version']) . "')
			";
		}
		$db->free_result($phrases);

		/*insert query*/
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "phrase
				(languageid, fieldname, varname, text, product, username, dateline, version)
			VALUES
				" . implode(', ', $insertsql)
		);

	}

	exec_header_redirect("language.php?" . $vbulletin->session->vars['sessionurl'] . "do=rebuild&goto=" . urlencode("phrase.php?" . $vbulletin->session->vars['sessionurl']));
}

// #############################################################################

if ($_POST['do'] != 'doreplace')
{
	print_cp_header($vbphrase['phrase_manager']);
}

// #############################################################################

if ($_POST['do'] == 'manageorphans')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'phr' => TYPE_ARRAY_BOOL,
	));

	print_form_header('phrase', 'completeorphans');

	$hidden_code_num = 0;
	$keepnames = array();

	foreach ($vbulletin->GPC['phr'] AS $key => $keep)
	{
		if ($keep)
		{
			fetch_varname_fieldname($key, $varname, $fieldname);
			$keepnames[] = "(varname = '" . $db->escape_string($varname) . "' AND fieldname = '" . $db->escape_string($fieldname) . "')";
		}
		else
		{
			construct_hidden_code("del[$hidden_code_num]", $key);
			$hidden_code_num ++;
		}
	}

	print_table_header($vbphrase['find_orphan_phrases']);

	if (empty($keepnames))
	{
		// there are no phrases to keep, just show a message telling admin to click to proceed
		print_description_row('<blockquote><p><br />' . $vbphrase['delete_all_orphans_notes'] . '</p></blockquote>');
	}
	else
	{
		// there are some phrases to keep, show a message explaining the page
		print_description_row($vbphrase['keep_orphans_notes']);

		$orphans = array();

		$phrases = $db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "phrase
			WHERE " . implode("\nOR ", $keepnames)
		);
		while ($phrase = $db->fetch_array($phrases))
		{
			$orphans["{$phrase['varname']}@{$phrase['fieldname']}"]["{$phrase['languageid']}"] = array('phraseid' => $phrase['phraseid'], 'text' => $phrase['text']);
		}
		$db->free_result($phrases);

		$languages = fetch_languages_array();
		$phrasetypes = fetch_phrasetypes_array();

		foreach ($orphans AS $key => $languageids)
		{
			fetch_varname_fieldname($key, $varname, $fieldname);

			if (isset($languageids["{$vbulletin->options['languageid']}"]))
			{
				$checked = $vbulletin->options['languageid'];
			}
			else
			{
				$checked = 0;
			}

			$bgclass = fetch_row_bgclass();

			echo "<tr valign=\"top\">\n";
			echo "\t<td class=\"$bgclass\">" . construct_wrappable_varname($varname, 'font-weight:bold;') . " <dfn>" . construct_phrase($vbphrase['x_phrases'], $phrasetypes["$fieldname"]['title']) . "</dfn></td>\n";
			echo "\t<td style=\"padding:0px\">\n\t\t<table cellpadding=\"2\" cellspacing=\"1\" border=\"0\" width=\"100%\">\n\t\t<col width=\"65%\"><col width=\"35%\" align=\"" . vB_Template_Runtime::fetchStyleVar('right') . "\">\n";

			$i = 0;
			$tr_bgclass = iif(($bgcounter % 2) == 0, 'alt2', 'alt1');

			foreach ($languages AS $_languageid => $language)
			{
				if (isset($languageids["$_languageid"]))
				{
					if ($checked)
					{
						if ($_languageid == $checked)
						{
							$checkedhtml = ' checked="checked"';
						}
						else
						{
							$checkedhtml = '';
						}
					}
					else if ($i == 0)
					{
						$checkedhtml = ' checked="checked"';
					}
					else
					{
						$checkedhtml = '';
					}
					$i++;
					$phrase =& $orphans["$key"]["$_languageid"];

					echo "\t\t<tr class=\"$tr_bgclass\">\n";
					echo "\t\t\t<td class=\"smallfont\"><label for=\"p$phrase[phraseid]\"><i>$phrase[text]</i></label></td>\n";
					echo "\t\t\t<td class=\"smallfont\"><label for=\"p$phrase[phraseid]\"><b>$language[title]</b><input type=\"radio\" name=\"keep[" . urlencode($key) . "]\" value=\"$phrase[phraseid]\" id=\"p$phrase[phraseid]\" tabindex=\"1\"$checkedhtml /></label></td>\n";
					echo "\t\t</tr>\n";
				}
			}

			echo "\n\t\t</table>\n";
			echo "\t\t<div class=\"$bgclass\">&nbsp;</div>\n";
			echo "\t</td>\n</tr>\n";
		}
	}

	print_submit_row($vbphrase['continue'], iif(empty($keepnames), false, " $vbphrase[reset] "));
}

// #############################################################################

if ($_REQUEST['do'] == 'findorphans')
{
	// get info for the languages and phrase types
	$languages = fetch_languages_array();
	$phrasetypes = fetch_phrasetypes_array();

	// query phrases that do not have a parent phrase in language -1 or 0
	$phrases = $db->query_read("
		SELECT orphan.varname, orphan.languageid, orphan.fieldname
		FROM " . TABLE_PREFIX . "phrase AS orphan
		LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase ON (phrase.languageid IN(-1, 0) AND phrase.varname = orphan.varname AND phrase.fieldname = orphan.fieldname)
		WHERE orphan.languageid NOT IN (-1, 0)
			AND phrase.phraseid IS NULL
		ORDER BY orphan.varname
	");

	if ($db->num_rows($phrases) == 0)
	{
		$db->free_result($phrases);
		print_stop_message('no_phrases_matched_your_query');
	}

	$orphans = array();
	while ($phrase = $db->fetch_array($phrases))
	{
		$phrase['varname'] = urlencode($phrase['varname']);
		$orphans["{$phrase['varname']}@{$phrase['fieldname']}"]["{$phrase['languageid']}"] = true;
	}
	$db->free_result($phrases);

	// get the number of columns for the table
	$colspan = sizeof($languages) + 2;

	print_form_header('phrase', 'manageorphans');
	print_table_header($vbphrase['find_orphan_phrases'], $colspan);

	// make the column headings
	$headings = array($vbphrase['varname']);
	foreach ($languages AS $language)
	{
		$headings[] = $language['title'];
	}
	$headings[] = '<input type="button" class="button" value="' . $vbphrase['keep_all'] . '" onclick="js_check_all_option(this.form, 1)" /> <input type="button" class="button" value="' . $vbphrase['delete_all'] . '" onclick="js_check_all_option(this.form, 0)" />';
	print_cells_row($headings, 1);

	// init the counter for our id attributes in label tags
	$i = 0;

	foreach ($orphans AS $key => $languageids)
	{
		// split the array key
		fetch_varname_fieldname($key, $varname, $fieldname);

		// make the first cell
		$cell = array(construct_wrappable_varname($varname, 'font-weight:bold;') . " <dfn>" . construct_phrase($vbphrase['x_phrases'], $phrasetypes["$fieldname"]['title']) . "</dfn>");

		// either display a tick or not depending on whether a translation exists
		foreach ($languages AS $_languageid => $language)
		{
			if (isset($languageids["$_languageid"]))
			{
				$yesno = 'yes';
			}
			else
			{
				$yesno = 'no';
			}

			$cell[] = "<img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/cp_tick_$yesno.gif\" alt=\"\" />";
		}

		$i++;
		$varname = urlencode($varname);
		$cell[] = "
		<label for=\"k_$i\"><input type=\"radio\" id=\"k_$i\" name=\"phr[{$varname}@$fieldname]\" value=\"1\" tabindex=\"1\" />$vbphrase[keep]</label>
		<label for=\"d_$i\"><input type=\"radio\" id=\"d_$i\" name=\"phr[{$varname}@$fieldname]\" value=\"0\" tabindex=\"1\" checked=\"checked\" />$vbphrase[delete]</label>
		";

		print_cells_row($cell);
	}

	print_submit_row($vbphrase['continue'], " $vbphrase[reset] ", $colspan);
}

// #############################################################################
// find custom phrases that need updating
if ($_REQUEST['do'] == 'findupdates')
{

	// query custom phrases
	$customcache = array();
	$phrases = $db->query_read("
		SELECT pGlobal.phraseid, pCustom.varname, pCustom.languageid,
			pCustom.username AS customuser, pCustom.dateline AS customdate, pCustom.version AS customversion,
			pGlobal.username AS globaluser, pGlobal.dateline AS globaldate, pGlobal.version AS globalversion,
			pGlobal.product, phrasetype.title AS phrasetype_title
		FROM " . TABLE_PREFIX . "phrase AS pCustom
		INNER JOIN " . TABLE_PREFIX . "phrase AS pGlobal ON (pGlobal.languageid = -1 AND pGlobal.varname = pCustom.varname AND pGlobal.fieldname = pCustom.fieldname)
		LEFT JOIN " . TABLE_PREFIX . "phrasetype AS phrasetype ON (phrasetype.fieldname = pGlobal.fieldname)
		WHERE pCustom.languageid <> -1
		ORDER BY pCustom.varname
	");
	while($phrase = $db->fetch_array($phrases))
	{
		if ($phrase['globalversion'] == '')
		{
			// No version on the global phrase. Wasn't edited in 3.6,
			// can't tell when it was last edited. Skip it.
			continue;
		}

		if ($phrase['customversion'] == '' AND $phrase['globalversion'] < '3.6')
		{
			// don't know when the custom version was last edited,
			// and the global was edited before 3.6, so we don't know what's newer
			continue;
		}

		if (!$phrase['product'])
		{
			$phrase['product'] = 'vbulletin';
		}

		if (is_newer_version($phrase['globalversion'], $phrase['customversion']))
		{
			$customcache["$phrase[languageid]"]["$phrase[phraseid]"] = $phrase;
		}
	}

	if (empty($customcache))
	{
		print_stop_message('all_phrases_are_up_to_date');
	}

	$languages = fetch_languages_array();

	print_form_header('', '');
	print_table_header($vbphrase['find_updated_phrases']);
	print_description_row('<span class="smallfont">' . construct_phrase($vbphrase['updated_default_phrases_desc'], $vbulletin->options['templateversion']) . '</span>');
	print_table_break(' ');

	foreach($languages AS $languageid => $language)
	{
		if (is_array($customcache["$languageid"]))
		{
			print_description_row($language['title'], 0, 2, 'thead');
			foreach($customcache["$languageid"] AS $phraseid => $phrase)
			{
				if (!$phrase['customuser'])
				{
					$phrase['customuser'] = $vbphrase['n_a'];
				}
				if (!$phrase['customversion'])
				{
					$phrase['customversion'] = $vbphrase['n_a'];
				}

				$product_name = $full_product_info["$phrase[product]"]['title'];

				print_label_row("
					<b>$phrase[varname]</b> ($phrase[phrasetype_title])<br />
					<span class=\"smallfont\">" .
						construct_phrase($vbphrase['default_phrase_updated_desc'],
							"$product_name $phrase[globalversion]",
							$phrase['globaluser'],
							"$product_name $phrase[customversion]",
							$phrase['customuser'])
					. '</span>',
				'<span class="smallfont">' .
					construct_link_code($vbphrase['edit'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;phraseid=$phraseid", 1) . '<br />' .
				'</span>'
				);
			}
		}
	}

	print_table_footer();
}

// #############################################################################

if ($_POST['do'] == 'dosearch')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'searchstring'  => TYPE_STR,
		'searchwhere'   => TYPE_UINT,
		'casesensitive' => TYPE_BOOL,
		'exactmatch'    => TYPE_BOOL,
		'languageid'    => TYPE_INT,
		'phrasetype'    => TYPE_ARRAY_NOHTML,
		'transonly'     => TYPE_BOOL,
		'product'       => TYPE_STR,
	));

	if ($vbulletin->GPC['searchstring'] == '')
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($vbulletin->GPC['exactmatch'])
	{
		$sql = ($vbulletin->GPC['casesensitive'] ? 'BINARY ' : '');

		switch($vbulletin->GPC['searchwhere'])
		{
			case 0:  $sql .= "text = '" . $db->escape_string($vbulletin->GPC['searchstring']) . "'"; break;
			case 1:  $sql .= "varname = '" . $db->escape_string($vbulletin->GPC['searchstring']) . "'"; break;
			case 10: $sql .= "(text = '" . $db->escape_string($vbulletin->GPC['searchstring']) . "' OR $sql varname = '" . $db->escape_string($vbulletin->GPC['searchstring']) . "')"; break;
			default: $sql .= '';
		}
	}
	else
	{
		switch($vbulletin->GPC['searchwhere'])
		{
			case 0:  $sql = fetch_field_like_sql($vbulletin->GPC['searchstring'], 'text', false, $vbulletin->GPC['casesensitive']); break;
			case 1:  $sql = fetch_field_like_sql($vbulletin->GPC['searchstring'], 'varname', true, $vbulletin->GPC['casesensitive']); break;
			case 10: $sql = '(' . fetch_field_like_sql($vbulletin->GPC['searchstring'], 'text', false, $vbulletin->GPC['casesensitive']) . ' OR ' . fetch_field_like_sql($vbulletin->GPC['searchstring'], 'varname', true, $vbulletin->GPC['casesensitive']) . ')'; break;
			default: $sql = '';
		}
	}

	if (!empty($vbulletin->GPC['phrasetype']) AND trim(implode($vbulletin->GPC['phrasetype'])) != '')
	{
		$phrasetype_sql = "'" . implode("', '", array_map(array(&$db, 'escape_string'), $vbulletin->GPC['phrasetype'])) . "'";
	}
	else
	{
		$phrasetype_sql = '';
	}

	if ($vbulletin->GPC['languageid'] == -10)
	{
		// query ALL languages
		if ($vbulletin->debug)
		{
			// searches all phrases
			$phrases = $db->query_read("
				SELECT phrase.*, language.title
				FROM " . TABLE_PREFIX . "phrase AS phrase
				LEFT JOIN " . TABLE_PREFIX . "language AS language USING(languageid)
				WHERE $sql
				" . ($phrasetype_sql ? "AND phrase.fieldname IN($phrasetype_sql)" : "") . "
				" . ($vbulletin->GPC['product'] ? "AND phrase.product = '" . $db->escape_string($vbulletin->GPC['product']) . "'" : "") . "
				ORDER BY languageid DESC, fieldname DESC
			");
		}
		else
		{
			// searches all phrases that are in use. Translated master phrases will not be searched
			$phrases = $db->query_read("
				SELECT IF (pcustom.fieldname IS NOT NULL, pcustom.fieldname, pmaster.fieldname) AS fieldname,
					IF (pcustom.varname IS NOT NULL, pcustom.varname, pmaster.varname) AS varname,
					IF (pcustom.languageid IS NOT NULL, pcustom.languageid, pmaster.languageid) AS languageid,
					IF (pcustom.text IS NOT NULL, pcustom.text, pmaster.text) AS text,
					language.title
				FROM " . TABLE_PREFIX . "language AS language
				INNER JOIN " . TABLE_PREFIX . "phrase AS pmaster ON
					(pmaster.languageid IN (-1, 0))
				LEFT JOIN " . TABLE_PREFIX . "phrase AS pcustom ON
					(pcustom.languageid = language.languageid AND pcustom.varname = pmaster.varname AND pcustom.fieldname = pmaster.fieldname)
				WHERE 1=1
					" . ($phrasetype_sql ? "AND pmaster.fieldname IN($phrasetype_sql)" : '') . "
					" . ($vbulletin->GPC['product'] ? "AND pmaster.product = '" . $db->escape_string($vbulletin->GPC['product']) . "'" : "") . "
				" . ($sql ? "HAVING $sql" : '') . "
				ORDER BY languageid DESC, fieldname DESC
			");
		}

	}
	else if ($vbulletin->GPC['languageid'] > 0 AND !$vbulletin->GPC['transonly'])
	{
		// query specific translation AND master/custom master languages
		$phrases = $db->query_read("
			SELECT IF (pcustom.fieldname IS NOT NULL, pcustom.fieldname, pmaster.fieldname) AS fieldname,
				IF (pcustom.varname IS NOT NULL, pcustom.varname, pmaster.varname) AS varname,
				IF (pcustom.languageid IS NOT NULL, pcustom.languageid, pmaster.languageid) AS languageid,
				IF (pcustom.text IS NOT NULL, pcustom.text, pmaster.text) AS text,
				language.title
			FROM " . TABLE_PREFIX . "phrase AS pmaster
			LEFT JOIN " . TABLE_PREFIX . "phrase AS pcustom ON (pcustom.languageid = " . $vbulletin->GPC['languageid'] . " AND pcustom.varname = pmaster.varname)
			LEFT JOIN " . TABLE_PREFIX . "language AS language ON (pcustom.languageid = language.languageid)
			WHERE pmaster.languageid IN (-1, 0)
			" . ($phrasetype_sql ? "AND pmaster.fieldname IN($phrasetype_sql)" : '') . "
			" . ($vbulletin->GPC['product'] ? "AND pmaster.product = '" . $db->escape_string($vbulletin->GPC['product']) . "'" : "") . "
			" . ($sql ? "HAVING $sql" : '') . "
			ORDER BY languageid DESC, fieldname DESC
		");
	}
	else
	{
		// query ONLY specific language
		$phrases = $db->query_read("
			SELECT phrase.*, language.title
			FROM " . TABLE_PREFIX . "phrase AS phrase
			LEFT JOIN " . TABLE_PREFIX . "language AS language USING(languageid)
			WHERE $sql
			" . ($phrasetype_sql ? "AND phrase.fieldname IN($phrasetype_sql)" : '') . "
			" . ($vbulletin->GPC['product'] ? "AND phrase.product = '" . $db->escape_string($vbulletin->GPC['product']) . "'" : "") . "
			AND phrase.languageid = " . $vbulletin->GPC['languageid'] . "
			ORDER BY fieldname DESC
		");
	}

	if ($db->num_rows($phrases) == 0)
	{
		print_stop_message('no_phrases_matched_your_query');
	}

	$phrasearray = array();
	while ($phrase = $db->fetch_array($phrases))
	{
		// check to see if the languageid is already set
		if ($vbulletin->GPC['languageid'] > 0 AND isset($phrasearray["$phrase[fieldname]"]["$phrase[varname]"]["{$vbulletin->GPC['languageid']}"]))
		{
			continue;
		}
		$phrasearray["{$phrase['fieldname']}"]["{$phrase['varname']}"]["{$phrase['languageid']}"] = $phrase;
	}

	unset($phrase);
	$db->free_result($phrases);

	$phrasetypes = fetch_phrasetypes_array();

	print_form_header('phrase', 'edit');
	print_table_header($vbphrase['search_results'], 5);

	$ignorecase = ($vbulletin->GPC['casesensitive'] ? false : true);

	foreach($phrasearray AS $fieldname => $x)
	{
		// display the header for the phrasetype
		print_description_row(construct_phrase($vbphrase['x_phrases_containing_y'], $phrasetypes["$fieldname"]['title'], htmlspecialchars_uni($vbulletin->GPC['searchstring'])), 0, 5, 'thead" align="center');

		// sort the phrases alphabetically by $varname
		ksort($x);
		foreach($x AS $varname => $y)
		{
			foreach($y AS $phrase)
			{
				$cell = array();
				$cell[] = '<b>' . ($vbulletin->GPC['searchwhere'] > 0 ? fetch_highlighted_search_results($vbulletin->GPC['searchstring'], $varname, $ignorecase) : $varname) . '</b>';
				$cell[] = '<span class="smallfont">' . fetch_language_type_string($phrase['languageid'], $phrase['title']) . '</span>';
				$cell[] = '<span class="smallfont">' . nl2br(($vbulletin->GPC['searchwhere'] % 10 == 0) ? fetch_highlighted_search_results($vbulletin->GPC['searchstring'], $phrase['text'], $ignorecase) : htmlspecialchars_uni($phrase['text'])) . '</span>';
				$cell[] = "<input type=\"submit\" class=\"button\" value=\" $vbphrase[edit] \" name=\"e[$fieldname][" . urlencode($varname) . "]\" />";
				if (($vbulletin->debug AND $phrase['languageid'] == -1) OR $phrase['languageid'] == 0)
				{
					$cell[] = "<input type=\"submit\" class=\"button\" value=\" $vbphrase[delete] \" name=\"delete[$fieldname][" . urlencode($varname) . "]\" />";
				}
				else
				{
					$cell[] = '';
				}
				print_cells_row($cell, 0, 0, -2);
			} // end foreach($y)
		} // end foreach($x)
	} // end foreach($phrasearray)

	print_table_footer();

	$_REQUEST['do'] = 'search';

}

// #############################################################################

if ($_REQUEST['do'] == 'search')
{
	if (!isset($_REQUEST['languageid']))
	{
		$_REQUEST['languageid'] = -10;
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'searchstring'  => TYPE_STR,
		'searchwhere'   => TYPE_UINT,
		'casesensitive' => TYPE_BOOL,
		'exactmatch'    => TYPE_BOOL,
		'languageid'    => TYPE_INT,
		'phrasetype'    => TYPE_ARRAY_NOHTML,
		'transonly'     => TYPE_BOOL,
		'product'       => TYPE_STR,
	));

	// get all languages
	$languageselect = array(-10 => $vbphrase['all_languages']);

	if ($vbulletin->debug)
	{
		$languageselect["$vbphrase[developer_options]"] = array(
			-1 => $vbphrase['master_language'] . ' (-1)',
			0  => $vbphrase['custom_language'] . ' (0)'
		);
	}

	$languageselect["$vbphrase[translations]"] = array();

	$languages = $db->query_read("SELECT title, languageid FROM " . TABLE_PREFIX . "language");
	while ($language = $db->fetch_array($languages))
	{
		$languageselect["$vbphrase[translations]"]["$language[languageid]"] = $language['title'];
	}
	$db->free_result($languages);

	// get all phrase types
	$phrasetypes = array('' => '');
	$phrasetypes_result = $db->query_read("SELECT fieldname, title FROM " . TABLE_PREFIX . "phrasetype ORDER BY title");
	while ($phrasetype = $db->fetch_array($phrasetypes_result))
	{
		$phrasetypes["$phrasetype[fieldname]"] = $phrasetype['title'];
	}

	print_form_header('phrase', 'dosearch');
	print_table_header($vbphrase['search_in_phrases']);
	print_input_row($vbphrase['search_for_text'], 'searchstring', $vbulletin->GPC['searchstring'], 1, 50);
	print_select_row($vbphrase['search_in_language'], 'languageid', $languageselect, $vbulletin->GPC['languageid']);
	print_select_row($vbphrase['product'], 'product', array('' => $vbphrase['all_products']) + fetch_product_list(), $vbulletin->GPC['product']);
	print_yes_no_row($vbphrase['search_translated_phrases_only'], 'transonly', $vbulletin->GPC['transonly']);
	print_select_row($vbphrase['phrase_type'], 'phrasetype[]', $phrasetypes, $vbulletin->GPC['phrasetype'], false, 10, true);

	$where = array("{$vbulletin->GPC['searchwhere']}" => ' checked="checked"');
	print_label_row(construct_phrase($vbphrase['search_in_x'], '...'),'
		<label for="rb_sw_0"><input type="radio" name="searchwhere" id="rb_sw_0" value="0" tabindex="1"' . $where[0] . ' />' . $vbphrase['phrase_text_only'] . '</label><br />
		<label for="rb_sw_1"><input type="radio" name="searchwhere" id="rb_sw_1" value="1" tabindex="1"' . $where[1] . ' />' . $vbphrase['phrase_name_only'] . '</label><br />
		<label for="rb_sw_10"><input type="radio" name="searchwhere" id="rb_sw_10" value="10" tabindex="1"' . $where[10] . ' />' . $vbphrase['phrase_text_and_phrase_name'] . '</label>', '', 'top', 'searchwhere');
	print_yes_no_row($vbphrase['case_sensitive'], 'casesensitive', $vbulletin->GPC['casesensitive']);
	print_yes_no_row($vbphrase['exact_match'], 'exactmatch', $vbulletin->GPC['exactmatch']);
	print_submit_row($vbphrase['find']);

	unset($languageselect[-10], $languageselect[-1], $languageselect[0]);
	// search & replace
	print_form_header('phrase', 'replace', 0, 1, 'srform');
	print_table_header($vbphrase['find_and_replace_in_languages']);
	print_select_row($vbphrase['search_in_language'], 'languageid', $languageselect);
	print_textarea_row($vbphrase['search_for_text'], 'searchstring', '', 5, 60, 1, 0);
	print_textarea_row($vbphrase['replace_with_text'], 'replacestring', '', 5, 60, 1, 0);
	print_submit_row($vbphrase['replace']);

}

// #############################################################################

if ($_POST['do'] == 'doreplace')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'replace'       => TYPE_ARRAY_UINT,
		'searchstring'  => TYPE_STR,
		'replacestring' => TYPE_STR,
		'languageid'    => TYPE_INT
	));

	if (empty($vbulletin->GPC['replace']))
	{
		print_stop_message('please_complete_required_fields');
	}

	$phraseids = array_keys($vbulletin->GPC['replace']);

	$phrases = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "phrase
		WHERE phraseid IN (" . implode(',', $phraseids) . ")
	");

	while ($phrase = $db->fetch_array($phrases))
	{
		$phrase['product'] = (empty($phrase['product']) ? 'vbulletin' : $phrase['product']);
		$phrase['text'] = str_replace($vbulletin->GPC['searchstring'], $vbulletin->GPC['replacestring'], $phrase['text']);

		if ($phrase['languageid'] == $vbulletin->GPC['languageid'])
		{ // update
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "phrase SET
					text = '" . $db->escape_string($phrase['text']) . "',
					username = '" . $db->escape_string($vbulletin->userinfo['username']) . "',
					dateline = " . TIMENOW . ",
					version = '" . $db->escape_string($full_product_info["$phrase[product]"]['version']) . "'
				WHERE phraseid = $phrase[phraseid]
			");
		}
		else
		{ // insert
			/*insert query*/
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "phrase
					(languageid, varname, text, fieldname, product, username, dateline, version)
				VALUES
					(" . $vbulletin->GPC['languageid'] . ",
					'" . $db->escape_string($phrase['varname']) . "',
					'" . $db->escape_string($phrase['text']) . "',
					'" . $db->escape_string($phrase['fieldname']) . "',
					'" . $db->escape_string($phrase['product']) . "',
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($full_product_info["$phrase[product]"]['version']) . "')
			");
		}

		if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			$products_to_export[$phrase['product']] = 1;
		}
	}

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		foreach(array_keys($products_to_export) as $product)
		{
			autoexport_write_language($vbulletin->GPC['languageid'], $product);
		}
	}

	exec_header_redirect("language.php?" . $vbulletin->session->vars['sessionurl'] .
		"do=rebuild&goto=" . urlencode("phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=search"));
}

// #############################################################################

if ($_POST['do'] == 'replace')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'searchstring'  => TYPE_STR,
		'replacestring' => TYPE_STR,
		'languageid'    => TYPE_INT
	));

	if (empty($vbulletin->GPC['searchstring']) OR empty($vbulletin->GPC['replacestring']))
	{
		print_stop_message('please_complete_required_fields');
	}

	// do a rather clever query to find what phrases to display
	$phraseids = '0';
	$phrases = $db->query_read("
		SELECT
			IF(pcust.phraseid IS NULL, pmast.phraseid, pcust.phraseid) AS phraseid,
			IF(pcust.phraseid IS NULL, pmast.text, pcust.text) AS xtext
		FROM " . TABLE_PREFIX . "phrase AS pmast
		LEFT JOIN " . TABLE_PREFIX . "phrase AS pcust ON (
			pcust.varname = pmast.varname AND
			pcust.fieldname = pmast.fieldname AND
			pcust.languageid = " . $vbulletin->GPC['languageid'] . "
		)
		WHERE pmast.languageid = -1
		HAVING " . fetch_field_like_sql($vbulletin->GPC['searchstring'], 'xtext', false, true) . "
	");
	while ($phrase = $db->fetch_array($phrases))
	{
		$phraseids .= ",$phrase[phraseid]";
	}
	$db->free_result($phrases);

	// now do a simple query to actually fetch the data
	$phrasearray = array();
	$phrases = $db->query_read("
		SELECT phrase.*, language.title
		FROM " .TABLE_PREFIX . "phrase AS phrase
		LEFT JOIN " . TABLE_PREFIX . "language AS language USING(languageid)
		WHERE phrase.phraseid IN($phraseids)
	");

	if ($db->num_rows($phrases) == 0)
	{
		print_stop_message('no_phrases_matched_your_query');
	}

	while ($phrase = $db->fetch_array($phrases))
	{
		$phrasearray["$phrase[fieldname]"]["$phrase[varname]"]["$phrase[languageid]"] = $phrase;
	}
	unset($phrase);
	$db->free_result($phrases);

	$phrasetypes = fetch_phrasetypes_array();

	print_form_header('phrase', 'doreplace');
	print_table_header($vbphrase['search_results'], 4);

	construct_hidden_code('searchstring', $vbulletin->GPC['searchstring']);
	construct_hidden_code('replacestring', $vbulletin->GPC['replacestring']);
	construct_hidden_code('languageid', $vbulletin->GPC['languageid']);

	foreach($phrasearray AS $fieldname => $x)
	{
		// display the header for the phrasetype
		print_description_row(construct_phrase($vbphrase['x_phrases_containing_y'], $phrasetypes["$fieldname"]['title'], htmlspecialchars_uni($vbulletin->GPC['searchstring'])), 0, 4, 'thead" align="center');

		// sort the phrases alphabetically by $varname
		ksort($x);
		foreach($x AS $varname => $y)
		{
			foreach($y AS $phrase)
			{
				$cell = array();
				$cell[] = '<b>' . $varname . '</b>';
				$cell[] = '<span class="smallfont">' . fetch_language_type_string($phrase['languageid'], $phrase['title']) . '</span>';
				$cell[] = '<span class="smallfont">' . fetch_highlighted_search_results($vbulletin->GPC['searchstring'], $phrase['text'], false) . '</span>';
				$cell[] = "<input type=\"checkbox\" value=\"1\" name=\"replace[{$phrase['phraseid']}]\" />";
				print_cells_row($cell, 0, 0, -2);
			} // end foreach($y)
		} // end foreach($x)
	} // end foreach($phrasearray)
	//print_submit_row($vbphrase['replace'], '', 4);
	print_submit_row($vbphrase['replace'], '', 4, '', '<label for="cb_checkall"><input type="checkbox" name="allbox" id="cb_checkall" onclick="js_check_all(this.form)" />' . $vbphrase['check_uncheck_all'] . '</label>');
	//print_table_footer();
}

// #############################################################################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'fieldname'       => TYPE_NOHTML,
		'pagenumber'      => TYPE_UINT,
		'perpage'         => TYPE_UINT,
		'sourcefieldname' => TYPE_NOHTML,
	));

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		$extra_fields = ", languageid, product";
	}

	$getvarname = $db->query_first("
		SELECT varname, fieldname $extra_fields
		FROM " . TABLE_PREFIX . "phrase
		WHERE phraseid = " . $vbulletin->GPC['phraseid']
	);

	if ($getvarname)
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "phrase
			WHERE varname = '" . $db->escape_string($getvarname['varname']) . "'
				AND fieldname = '" . $db->escape_string($getvarname['fieldname']) . "'
		");

		build_language();

		if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			require_once(DIR . '/includes/functions_filesystemxml.php');
			autoexport_write_language($getvarname['languageid'], $getvarname['product']);
		}

		define('CP_REDIRECT', "phrase.php?fieldname=" . $vbulletin->GPC['sourcefieldname'] .
			"&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;pp=" . $vbulletin->GPC['perpage']);
		print_stop_message('deleted_phrase_successfully');
	}
	else
	{
		print_stop_message('invalid_phrase_specified');
	}
}

// #############################################################################

if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'fieldname'       => TYPE_NOHTML,
		'oldfieldname'    => TYPE_NOHTML,
		'languageid'      => TYPE_INT,
		'oldvarname'      => TYPE_STR,
		'varname'         => TYPE_STR,
		'text'            => TYPE_ARRAY_NOTRIM,
		'ismaster'        => TYPE_INT,
		'sourcefieldname' => TYPE_NOHTML,
		't'               => TYPE_BOOL,
	));

	if (empty($vbulletin->GPC['varname']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if (!preg_match('#^[a-z0-9_\[\].]+$#i', $vbulletin->GPC['varname'])) // match a-z, A-Z, 0-9, ',', _ only .. allow [] for help items
	{
		print_stop_message('invalid_phrase_varname');
	}

	foreach ($vbulletin->GPC['text'] AS $text)
	{
		if (!validate_string_for_interpolation($text))
		{
			print_stop_message('phrase_text_not_safe', $vbulletin->GPC['varname']);
		}
	}

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		//only used after fall through to "insert" action.
		$old_product = $db->query_first("
			SELECT product FROM " . TABLE_PREFIX . "phrase
			WHERE languageid = -1 AND
				varname = '" . $db->escape_string($vbulletin->GPC['oldvarname']) . "' AND
				fieldname = '" . $db->escape_string($vbulletin->GPC['oldfieldname']) . "'");
	}

	if ($db->query_first("
		SELECT phraseid FROM " . TABLE_PREFIX . "phrase
		WHERE varname = '" . $db->escape_string($vbulletin->GPC['varname']) . "' AND
			languageid IN(0,-1) AND fieldname = '" . $db->escape_string($vbulletin->GPC['fieldname']) . "'")
	)
	{
		if ($vbulletin->GPC['varname'] != $vbulletin->GPC['oldvarname'])
		{
			print_stop_message('variable_name_exists', $vbulletin->GPC['oldvarname'], $vbulletin->GPC['varname']);
		}

		if ($vbulletin->GPC['oldfieldname'] != $vbulletin->GPC['fieldname'])
		{
			print_stop_message('there_is_already_phrase_named_x', $vbulletin->GPC['varname']);
		}
	}

	// delete old phrases
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "phrase
		WHERE varname = '" . $db->escape_string($vbulletin->GPC['oldvarname']) . "' AND
				fieldname = '" . $db->escape_string($vbulletin->GPC['oldfieldname']) . "'
		" . ($vbulletin->GPC['t'] ? " AND languageid NOT IN(-1,0)" : "") . "
		" . (!$vbulletin->debug ? ' AND languageid <> -1' : '') . "
	");

	// now set some variables and go ahead to the insert action
	$update = 1;
	$vbulletin->GPC['ismaster'] = ($vbulletin->GPC['languageid'] == -1) ? true : false;
	$_POST['do'] = 'insert';

}

// #############################################################################

if ($_POST['do'] == 'insert')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'sourcefieldname' => TYPE_NOHTML,
		'fieldname'       => TYPE_NOHTML,
		'varname'         => TYPE_STR,
		'text'            => TYPE_ARRAY_NOTRIM,
		'ismaster'        => TYPE_INT,
		'pagenumber'      => TYPE_UINT,
		'perpage'         => TYPE_UINT,
		'product'         => TYPE_STR,
	));

	if (empty($update))
	{
		if ((empty($vbulletin->GPC['text'][0]) AND $vbulletin->GPC['text'][0] != '0' AND !$vbulletin->GPC['t']) OR empty($vbulletin->GPC['varname']))
		{
			print_stop_message('please_complete_required_fields');
		}

		if (!preg_match('#^[a-z0-9_\[\].]+$#i', $vbulletin->GPC['varname'])) // match a-z, A-Z, 0-9, ',', _ only .. allow [] for help items
		{
			print_stop_message('invalid_phrase_varname');
		}

		foreach ($vbulletin->GPC['text'] AS $text)
		{
			if (!validate_string_for_interpolation($text))
			{
				print_stop_message('phrase_text_not_safe', $vbulletin->GPC['varname']);
			}
		}

		if ($db->query_first("SELECT phraseid FROM " . TABLE_PREFIX . "phrase WHERE varname = '" . $db->escape_string($vbulletin->GPC['varname']) . "' AND languageid IN(0,-1) AND fieldname = '" . $db->escape_string($vbulletin->GPC['fieldname']) . "'"))
		{
			print_stop_message('there_is_already_phrase_named_x', $vbulletin->GPC['varname']);
		}
	}

	if ($vbulletin->GPC['ismaster'])
	{
		if ($vbulletin->debug AND !$vbulletin->GPC['t'])
		{
			/*insert query*/
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "phrase
					(languageid, varname, text, fieldname, product, username, dateline, version)
				VALUES
					(-1,
					'" . $db->escape_string($vbulletin->GPC['varname']) . "',
					'" . $db->escape_string($vbulletin->GPC['text'][0]) . "',
					'" . $db->escape_string($vbulletin->GPC['fieldname']) . "',
					'" . $db->escape_string($vbulletin->GPC['product']) . "',
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($full_product_info[$vbulletin->GPC['product']]['version']) . "')
			");
		}

		unset($vbulletin->GPC['text'][0]);
		if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			require_once(DIR . '/includes/functions_filesystemxml.php');

			$products_to_export  = array( $vbulletin->GPC['product']);
			if (isset($old_product['product']))
			{
				$products_to_export[] = $old_product['product'];
			}
			autoexport_write_language(-1, $products_to_export);
		}

	}

	foreach($vbulletin->GPC['text'] AS $_languageid => $txt)
	{
		$_languageid = intval($_languageid);
		if (!empty($txt) OR $txt == '0')
		{
			/*insert query*/
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "phrase
					(languageid, varname, text, fieldname, product, username, dateline, version)
				VALUES
					($_languageid,
					'" . $db->escape_string($vbulletin->GPC['varname']) . "',
					'" . $db->escape_string($txt) . "',
					'" . $db->escape_string($vbulletin->GPC['fieldname']) . "',
					'" . $db->escape_string($vbulletin->GPC['product']) . "',
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($full_product_info[$vbulletin->GPC['product']]['version']) . "')
			");
		}
	}

	build_language();

	define('CP_REDIRECT', "phrase.php?fieldname=" . $vbulletin->GPC['sourcefieldname'] . "&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;pp=" . $vbulletin->GPC['perpage']);
	print_stop_message('saved_phrase_x_successfully', $vbulletin->GPC['varname']);
}

// #############################################################################

if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
?>
<script type="text/javascript">
function copy_default_text(targetlanguage)
{
	var deftext = fetch_object("default_phrase").value
	if (deftext == "")
	{
		alert("<?php echo $vbphrase['default_text_is_empty']; ?>");
	}
	else
	{
		fetch_object("text_" + targetlanguage).value = deftext;
	}
}
</script>
<?php
}

// #############################################################################

if ($_REQUEST['do'] == 'add')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'fieldname'       => TYPE_NOHTML,
		'pagenumber'      => TYPE_UINT,
		'perpage'         => TYPE_UINT
	));

	// make phrasetype options
	$phrasetypes = fetch_phrasetypes_array();
	$typeoptions = array();
	$type_product_options = array();
	foreach($phrasetypes AS $fieldname => $phrasetype)
	{
		$typeoptions["$fieldname"] = $phrasetype['title'];
		$type_product_options["$fieldname"] = $phrasetype['product'];
	}

	print_form_header('phrase', 'insert');
	print_table_header($vbphrase['add_new_phrase']);

	if ($vbulletin->debug)
	{
		print_yes_no_row(construct_phrase($vbphrase['insert_into_master_language_developer_option'], "<b></b>"), 'ismaster', iif($vbulletin->debug, 1, 0));
	}

	print_select_row($vbphrase['phrase_type'], 'fieldname', $typeoptions, $vbulletin->GPC['fieldname']);

	print_select_row($vbphrase['product'], 'product', fetch_product_list(), $type_product_options[$vbulletin->GPC['fieldname']]);

	// main input fields
	$resizer = "<div class=\"smallfont\"><a href=\"#\" onclick=\"return resize_textarea(1, 'default_phrase')\">$vbphrase[increase_size]</a> <a href=\"#\" onclick=\"return resize_textarea(-1, 'default_phrase')\">$vbphrase[decrease_size]</a></div>";

	print_input_row($vbphrase['varname'], 'varname', '', 1, 60);
	print_label_row(
		$vbphrase['text']  . $resizer,
		"<textarea name=\"text[0]\" id=\"default_phrase\" rows=\"5\" cols=\"60\" wrap=\"virtual\" tabindex=\"1\" dir=\"ltr\"" . iif($vbulletin->debug, ' title="name=&quot;text[0]&quot;"') . "></textarea>",
		'', 'top', 'text[0]'
	);

	// do translation boxes
	print_table_header($vbphrase['translations']);
	print_description_row("
			<ul><li>$vbphrase[phrase_translation_desc_1]</li>
			<li>$vbphrase[phrase_translation_desc_2]</li>
			<li>$vbphrase[phrase_translation_desc_3]</li></ul>
		",
		0, 2, 'tfoot'
	);
	$languages = fetch_languages_array();
	foreach($languages AS $_languageid => $lang)
	{
		$resizer = "<div class=\"smallfont\"><a href=\"#\" onclick=\"return resize_textarea(1, 'text_$_languageid')\">$vbphrase[increase_size]</a> <a href=\"#\" onclick=\"return resize_textarea(-1, 'text_$_languageid')\">$vbphrase[decrease_size]</a></div>";

		print_label_row(
			construct_phrase($vbphrase['x_translation'], "<b>$lang[title]</b>") . " <dfn>($vbphrase[optional])</dfn><br /><input type=\"button\" class=\"button\" value=\"$vbphrase[copy_default_text]\" tabindex=\"1\" onclick=\"copy_default_text($_languageid);\" />" . $resizer,
			"<textarea name=\"text[$_languageid]\" id=\"text_$_languageid\" rows=\"5\" cols=\"60\" tabindex=\"1\" wrap=\"virtual\" dir=\"$lang[direction]\"></textarea>"
		);
		print_description_row('<img src="../' . $vbulletin->options['cleargifurl'] . '" width="1" height="1" alt="" />', 0, 2, 'thead');
	}

	construct_hidden_code('page', $vbulletin->GPC['pagenumber']);
	construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
	construct_hidden_code('sourcefieldname', $vbulletin->GPC['fieldname']);
	print_submit_row($vbphrase['save']);

}

// #############################################################################

if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'e'          => TYPE_ARRAY_ARRAY,
		'delete'     => TYPE_ARRAY_ARRAY,
		'pagenumber' => TYPE_UINT,
		'perpage'    => TYPE_UINT,
		'fieldname'  => TYPE_NOHTML,
		'varname'    => TYPE_STR,
		't'          => TYPE_BOOL,		// Display only the translations and no delete button
	));
	if (!empty($vbulletin->GPC['delete']))
	{
		$editvarname =& $vbulletin->GPC['delete'];
		$_REQUEST['do'] = 'delete';
	}
	else
	{
		$editvarname =& $vbulletin->GPC['e'];
	}

	// make phrasetype options
	$phrasetypes = fetch_phrasetypes_array();
	$typeoptions = array();
	foreach($phrasetypes AS $fieldname => $phrasetype)
	{
		$typeoptions["$fieldname"] = $phrasetype['title'];
	}

	if (!empty($editvarname))
	{
		foreach($editvarname AS $fieldname => $varnames)
		{
			foreach($varnames AS $varname => $type)
			{
				$varname = urldecode($varname);
				$phrase['fieldname'] = $fieldname;
				$phrase = $db->query_first("
					SELECT * FROM " . TABLE_PREFIX . "phrase
					WHERE varname = '" . $db->escape_string($varname) . "' AND
							fieldname = '" . $db->escape_string($phrase['fieldname']) . "'
					ORDER BY languageid
					LIMIT 1
				");
				break;
			}
		}
	}
	else if ($vbulletin->GPC['phraseid'])
	{
		$phrase = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "phrase WHERE phraseid = " . $vbulletin->GPC['phraseid']);
	}
	else if ($vbulletin->GPC['fieldname'] AND $vbulletin->GPC['varname'])
	{
		$varname = urldecode($vbulletin->GPC['varname']);
		$phrase = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "phrase
			WHERE varname = '" . $db->escape_string($varname) . "' AND
					fieldname = '" . $db->escape_string($vbulletin->GPC['fieldname']) . "'
			ORDER BY languageid
			LIMIT 1
		");
	}

	if (!$phrase['phraseid'] OR !$phrase['varname'])
	{
		print_stop_message('no_phrases_matched_your_query');
	}

	if ($_REQUEST['do'] == 'delete')
	{
		$vbulletin->GPC['phraseid'] = $phrase['phraseid'];
	}
	else
	{
		// delete link
		if (($vbulletin->debug OR $phrase['languageid'] != '-1') AND !$vbulletin->GPC['t'])
		{
			print_form_header('phrase', 'delete');
			construct_hidden_code('phraseid', $phrase['phraseid']);
			print_table_header($vbphrase['if_you_would_like_to_remove_this_phrase'] . ' &nbsp; &nbsp; <input type="submit" class="button" tabindex="1" value="' . $vbphrase['delete'] . '" />');
			print_table_footer();
		}

		//. '<input type="hidden" id="default_phrase" value="' . htmlspecialchars_uni($phrase['text']) . '" />'

		print_form_header('phrase', 'update', false, true, 'phraseform');

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], iif(
			$phrase['languageid'] == 0,
			$vbphrase['custom_phrase'],
			$vbphrase['standard_phrase']
		), $phrase['varname'], $phrase['phraseid']));
		construct_hidden_code('mode', $mode);
		construct_hidden_code('oldvarname', $phrase['varname']);
		construct_hidden_code('t', $vbulletin->GPC['t']);

		if ($vbulletin->debug)
		{
			print_select_row($vbphrase['language'], 'languageid', array('-1' => $vbphrase['master_language'], '0' => $vbphrase['custom_language']), $phrase['languageid']);
			construct_hidden_code('oldfieldname', $phrase['fieldname']);
			print_select_row($vbphrase['phrase_type'], 'fieldname', $typeoptions, $phrase['fieldname']);
		}
		else
		{
			construct_hidden_code('languageid', $phrase['languageid']);
			construct_hidden_code('oldfieldname', $phrase['fieldname']);
			construct_hidden_code('fieldname', $phrase['fieldname']);
		}

		print_select_row($vbphrase['product'], 'product', fetch_product_list(), $phrase['product']);

		if (($phrase['languageid'] == 0 OR $vbulletin->debug) AND !$vbulletin->GPC['t'])
		{
			$resizer = "<div class=\"smallfont\"><a href=\"#\" onclick=\"return resize_textarea(1, 'default_phrase')\">$vbphrase[increase_size]</a> <a href=\"#\" onclick=\"return resize_textarea(-1, 'default_phrase')\">$vbphrase[decrease_size]</a></div>";

			print_input_row($vbphrase['varname'], 'varname', $phrase['varname'], 1, 50);
			print_label_row(
				$vbphrase['text'] . $resizer,
				"<textarea name=\"text[0]\" id=\"default_phrase\" rows=\"4\" cols=\"50\" wrap=\"virtual\" tabindex=\"1\" dir=\"ltr\"" . iif($vbulletin->debug, ' title="name=&quot;text[0]&quot;"') . ">" . htmlspecialchars_uni($phrase['text']) . "</textarea>",
				'', 'top', 'text[0]'
			);
		}
		else
		{
			print_label_row($vbphrase['varname'], '$vbphrase[<b>' . $phrase['varname'] . '</b>]');
			construct_hidden_code('varname', $phrase['varname']);

			print_label_row($vbphrase['text'], nl2br(htmlspecialchars_uni($phrase['text'])) . '<input type="hidden" id="default_phrase" value="' . htmlspecialchars_uni($phrase['text']) . '" />');
			if (!$vbulletin->GPC['t'])
			{
				construct_hidden_code('text[0]', $phrase['text']);
			}
		}

		// do translation boxes
		print_table_header($vbphrase['translations']);
		print_description_row("
				<ul><li>$vbphrase[phrase_translation_desc_1]</li>
				<li>$vbphrase[phrase_translation_desc_2]</li>
				<li>$vbphrase[phrase_translation_desc_3]</li></ul>
			",
			0, 2, 'tfoot'
		);

		$translations = $db->query_read("
			SELECT languageid, text
			FROM " . TABLE_PREFIX . "phrase
			WHERE varname = '" . $db->escape_string($phrase['varname']) . "' AND
				languageid <> $phrase[languageid] AND
				fieldname = '" . $db->escape_string($phrase[fieldname]) . "'
		");
		while ($translation = $db->fetch_array($translations))
		{
			$text["{$translation['languageid']}"] = $translation['text'];
		}

		// remove escape junk from javascript phrases for nice editable look
		fetch_js_unsafe_string($text);

		$languages = fetch_languages_array();
		foreach($languages AS $_languageid => $lang)
		{
			$resizer = "<div class=\"smallfont\"><a href=\"#\" onclick=\"return resize_textarea(1, 'text_$_languageid')\">$vbphrase[increase_size]</a> <a href=\"#\" onclick=\"return resize_textarea(-1, 'text_$_languageid')\">$vbphrase[decrease_size]</a></div>";

			print_label_row(
				construct_phrase($vbphrase['x_translation'], "<b>$lang[title]</b>") . " <dfn>($vbphrase[optional])</dfn><br /><input type=\"button\" class=\"button\" value=\"$vbphrase[copy_default_text]\" tabindex=\"1\" onclick=\"copy_default_text($_languageid);\" />" . $resizer,
				"<textarea name=\"text[$_languageid]\" id=\"text_$_languageid\" rows=\"5\" cols=\"60\" tabindex=\"1\" wrap=\"virtual\" dir=\"$lang[direction]\">" . htmlspecialchars_uni($text["$_languageid"]) . "</textarea>"
			);
			print_description_row('<img src="../' . $vbulletin->options['cleargifurl'] . '" width="1" height="1" alt="" />', 0, 2, 'thead');
		}

		construct_hidden_code('page', $vbulletin->GPC['pagenumber']);
		construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
		construct_hidden_code('sourcefieldname', $vbulletin->GPC['fieldname']);
		print_submit_row($vbphrase['save']);
	}
}

// #############################################################################

if ($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT,
		'perpage'    => TYPE_UINT,
		'fieldname'  => TYPE_NOHTML,
	));

	//Check if Phrase belongs to Master Language -> only able to delete if $vbulletin->debug=1
	$getvarname = $db->query_first("
		SELECT varname, fieldname
		FROM " . TABLE_PREFIX . "phrase
		WHERE phraseid=" . $vbulletin->GPC['phraseid']
	);

	$ismasterphrase = $db->query_first("
		SELECT languageid FROM " . TABLE_PREFIX . "phrase
		WHERE varname = '" . $getvarname['varname'] . "' AND
			languageid = '-1'" . iif($getvarname['fieldname'], " AND
			fieldname = '" . $db->escape_string($getvarname['fieldname']) . "'")
	);
	if (!$vbulletin->debug AND $ismasterphrase)
	{
		print_stop_message('cant_delete_master_phrase');
	}

	print_delete_confirmation('phrase', $vbulletin->GPC['phraseid'], 'phrase', 'kill', 'phrase', array(
		'sourcefieldname' => $vbulletin->GPC['fieldname'],
		'fieldname'       => $getvarname['fieldname'],
		'pagenumber'      => $vbulletin->GPC['pagenumber'],
		'perpage'         => $vbulletin->GPC['perpage']
	), $vbphrase['if_you_delete_this_phrase_translations_will_be_deleted']);

}

// #############################################################################

if ($_REQUEST['do'] == 'modify')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'fieldname'  => TYPE_NOHTML,
		'perpage'    => TYPE_INT,
		'pagenumber' => TYPE_INT,
		'showpt'     => TYPE_ARRAY_UINT,
	));

	/*if (empty($vbulletin->GPC['showpt']))
	{
		$vbulletin->GPC['showpt'] = array('master' => 1, 'custom' => 1);
	}
	$checked = array();
	foreach ($vbulletin->GPC['showpt'] AS $type => $yesno)
	{
		$checked["$type$yesno"] = ' checked="checked"';
	}*/

	$phrasetypes = fetch_phrasetypes_array();

	// make sure $fieldname is valid
	if ($vbulletin->GPC['fieldname'] != '' AND !isset($phrasetypes["{$vbulletin->GPC['fieldname']}"]))
	{
		$vbulletin->GPC['fieldname'] = 'global';
	}

	// check display values are valid
	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}

	// count phrases
	$countphrases = $db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "phrase AS phrase
		WHERE languageid IN(-1, 0)
			" . iif($vbulletin->GPC['fieldname'] != '', "AND fieldname = '" . $db->escape_string($vbulletin->GPC['fieldname']) . "'")
	);

	$numphrases =& $countphrases['total'];
	$numpages = ceil($numphrases / $vbulletin->GPC['perpage']);

	if ($numpages < 1)
	{
		$numpages = 1;
	}
	if ($vbulletin->GPC['pagenumber'] > $numpages)
	{
		$vbulletin->GPC['pagenumber'] = $numpages;
	}

	$showprev = false;
	$shownext = false;

	if ($vbulletin->GPC['pagenumber'] > 1)
	{
		$showprev = true;
	}
	if ($vbulletin->GPC['pagenumber'] < $numpages)
	{
		$shownext = true;
	}

	$pageoptions = array();
	for ($i = 1; $i <= $numpages; $i++)
	{
		$pageoptions["$i"] = "$vbphrase[page] $i / $numpages";
	}

	$phraseoptions = array('' => $vbphrase['all_phrase_groups']);
	foreach($phrasetypes AS $fieldname => $type)
	{
		$phraseoptions["$fieldname"] = $type['title'];
	}

	print_form_header('phrase', 'modify', false, true, 'navform', '90%', '', true, 'get');
	echo '
	<colgroup span="5">
		<col style="white-space:nowrap"></col>
		<col></col>
		<col width="100%" align="center"></col>
		<col style="white-space:nowrap"></col>
		<col></col>
	</colgroup>
	<tr>
		<td class="thead">' . $vbphrase['phrase_type'] . ':</td>
		<td class="thead"><select name="fieldname" class="bginput" tabindex="1" onchange="this.form.page.selectedIndex = 0; this.form.submit()">' . construct_select_options($phraseoptions, $vbulletin->GPC['fieldname']) . '</select></td>
		<td class="thead">' .
			'<input type="button"' . iif(!$showprev, ' disabled="disabled"') . ' class="button" value="&laquo; ' . $vbphrase['prev'] . '" tabindex="1" onclick="this.form.page.selectedIndex -= 1; this.form.submit()" />' .
			'<select name="page" tabindex="1" onchange="this.form.submit()" class="bginput">' . construct_select_options($pageoptions, $vbulletin->GPC['pagenumber']) . '</select>' .
			'<input type="button"' . iif(!$shownext, ' disabled="disabled"') . ' class="button" value="' . $vbphrase['next'] . ' &raquo;" tabindex="1" onclick="this.form.page.selectedIndex += 1; this.form.submit()" />
		</td>
		<td class="thead">' . $vbphrase['phrases_to_show_per_page'] . ':</td>
		<td class="thead"><input type="text" class="bginput" name="perpage" value="' . $vbulletin->GPC['perpage'] . '" tabindex="1" size="5" /></td>
		<td class="thead"><input type="submit" class="button" value=" ' . $vbphrase['go'] . ' " tabindex="1" accesskey="s" /></td>
	</tr>';
	print_table_footer();

	/*print_form_header('phrase', 'modify');
	print_table_header($vbphrase['controls'], 3);
	echo '
	<tr>
		<td class="tfoot">
			<select name="fieldname" class="bginput" tabindex="1" onchange="this.form.page.selectedIndex = 0; this.form.submit()">' . construct_select_options($phraseoptions, $vbulletin->GPC['fieldname']) . '</select><br />
			<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td><b>Show Master Phrases?</b> &nbsp; &nbsp; &nbsp;</td>
				<td><label for="rb_smy"><input type="radio" name="showpt[master]" id="rb_smy" value="1"' . $checked['master1'] . ' />' . $vbphrase['yes'] . '</label></td>
				<td><label for="rb_smn"><input type="radio" name="showpt[master]" id="rb_smn" value="0"' . $checked['master0'] . ' />' . $vbphrase['no'] . '</label></td>
			</tr>
			<tr>
				<td><b>Show Custom Phrases?</b> &nbsp; &nbsp; &nbsp;</td>
				<td><label for="rb_scy"><input type="radio" name="showpt[custom]" id="rb_scy" value="1"' . $checked['custom1'] . ' />' . $vbphrase['yes'] . '</label></td>
				<td><label for="rb_scn"><input type="radio" name="showpt[custom]" id="rb_scn" value="0"' . $checked['custom0'] . ' />' . $vbphrase['no'] . '</label></td>
			</tr>
			</table>
		</td>
		<td class="tfoot" align="center">
			<div style="margin-bottom:4px"><b>' . $vbphrase['phrases_to_show_per_page'] . ':</b> <input type="text" class="bginput" name="perpage" value="' . $vbulletin->GPC['perpage'] . '" tabindex="1" size="5" /></div>
			<input type="button"' . iif(!$showprev, ' disabled="disabled"') . ' class="button" value="&laquo; ' . $vbphrase['prev'] . '" tabindex="1" onclick="this.form.page.selectedIndex -= 1; this.form.submit()" />' .
			'<select name="page" tabindex="1" onchange="this.form.submit()" class="bginput">' . construct_select_options($pageoptions, $vbulletin->GPC['pagenumber']) . '</select>' .
			'<input type="button"' . iif(!$shownext, ' disabled="disabled"') . ' class="button" value="' . $vbphrase['next'] . ' &raquo;" tabindex="1" onclick="this.form.page.selectedIndex += 1; this.form.submit()" />
		</td>
		<td class="tfoot" align="center"><input type="submit" class="button" value=" ' . $vbphrase['go'] . ' " tabindex="1" accesskey="s" /></td>
	</tr>
	';
	print_table_footer();*/

	print_phrase_ref_popup_javascript();

	?>
	<script type="text/javascript">
	<!--
	function js_edit_phrase(id)
	{
		window.location = "phrase.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=edit&phraseid=" + id;
	}
	// -->
	</script>
	<?php

	$masterphrases = $db->query_read("
		SELECT varname, fieldname
		FROM " . TABLE_PREFIX . "phrase AS phrase
		WHERE languageid IN(-1, 0)
		" . iif($vbulletin->GPC['fieldname'] != '', "AND fieldname = '" . $db->escape_string($vbulletin->GPC['fieldname']) . "'") . "
		ORDER BY fieldname, varname
		LIMIT " . (($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage']) . ", " . $vbulletin->GPC['perpage'] . "
	");
	$phrasenames = array();
	while ($masterphrase = $db->fetch_array($masterphrases))
	{
		$phrasenames[] = "(varname = '" . $db->escape_string($masterphrase['varname']) . "' AND fieldname = '" . $db->escape_string($masterphrase['fieldname']) . "')";
	}
	unset($masterphrase);
	$db->free_result($masterphrases);

	$cphrases = array();
	if (!empty($phrasenames))
	{
		$phrases = $db->query_read("
			SELECT phraseid, languageid, varname, fieldname
			FROM " . TABLE_PREFIX . "phrase AS phrase
			WHERE " . implode("
			OR ", $phrasenames) . "
			ORDER BY fieldname, varname
		");
		unset($phrasenames);
		while ($phrase = $db->fetch_array($phrases))
		{
			$cphrases["{$phrase['fieldname']}"]["{$phrase['varname']}"]["{$phrase['languageid']}"] = $phrase['phraseid'];
		}
		unset($phrase);
		$db->free_result($phrases);
	}

	$languages = fetch_languages_array();
	$numlangs = sizeof($languages);
	$colspan = $numlangs + 2;

	print_form_header('phrase', 'add', false, true, 'phraseform', '90%', '', true, 'post', 1);
	construct_hidden_code('fieldname', $vbulletin->GPC['fieldname']);

	echo "\t<colgroup span=\"" . (sizeof($languages) + 1) . "\"></colgroup>\n";
	echo "\t<col style=\"white-space:nowrap\"></col>\n";

	// show phrases
	foreach($cphrases AS $_fieldname => $varnames)
	{
		print_table_header(construct_phrase($vbphrase['x_phrases'], $phrasetypes["$_fieldname"]['title']) . " <span class=\"normal\">(fieldname = $_fieldname)</span>", $colspan);

		$headings = array($vbphrase['varname']);
		foreach($languages AS $_languageid => $language)
		{
			$headings[] = "<a href=\"javascript:js_open_phrase_ref($language[languageid],'$_fieldname');\" title=\"" . $vbphrase['view_quickref'] . ": $language[title]\">$language[title]</a>";
		}
		$headings[] = '';
		print_cells_row($headings, 0, 'thead');

		ksort($varnames);
		foreach($varnames AS $varname => $phrase)
		{
			$cell = array(construct_wrappable_varname($varname, 'font-weight:bold;', 'smallfont', 'span'));
			if (isset($phrase['-1']))
			{
				$phraseid = $phrase['-1'];
				$custom = 0;
			}
			else

			{
				$phraseid = $phrase['0'];
				$custom = 1;
			}
			foreach(array_keys($languages) AS $_languageid)
			{
				$cell[] = "<img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/cp_tick_" . iif(isset($phrase["$_languageid"]), 'yes', 'no') . ".gif\" alt=\"\" />";
			}
			$cell[] = '<span class="smallfont">' . construct_link_code(fetch_tag_wrap($vbphrase['edit'], 'span class="col-i"', $custom==1), "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;phraseid=$phraseid&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;pp=" . $vbulletin->GPC['perpage'] . "&amp;fieldname=" . $vbulletin->GPC['fieldname']) . iif($custom OR $vbulletin->debug, construct_link_code(fetch_tag_wrap($vbphrase['delete'], 'span class="col-i"', $custom==1), "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=delete&amp;phraseid=$phraseid&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;pp=" . $vbulletin->GPC['perpage'] . "&amp;fieldname=" . $vbulletin->GPC['fieldname']), '') . '</span>';
			print_cells_row($cell, 0, 0, 0, 'top', 0);
		}
	}

	print_table_footer($colspan, "
		<input type=\"button\" class=\"button\" value=\"" . $vbphrase['search_in_phrases'] . "\" tabindex=\"1\" onclick=\"window.location='phrase.php?" . $vbulletin->session->vars['sessionurl'] . "&amp;do=search';\" />
		&nbsp; &nbsp;
		<input type=\"button\" class=\"button\" value=\"" . $vbphrase['add_new_phrase'] . "\" tabindex=\"1\" onclick=\"window.location='phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=add&amp;fieldname=" . $vbulletin->GPC['fieldname'] . "&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;pp=" . $vbulletin->GPC['perpage'] . "';\" />
		&nbsp; &nbsp;
		<input type=\"button\" class=\"button\" value=\"" . $vbphrase['find_orphan_phrases'] . "\" tabindex=\"1\" onclick=\"window.location='phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=findorphans';\" />
	");


}

// #############################################################################

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63231 $
|| ####################################################################
\*======================================================================*/
?>
