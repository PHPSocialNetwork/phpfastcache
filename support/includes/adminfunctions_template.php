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

// note #1: arrays used by functions in this code are declared at the bottom of the page
// note #2: REMEMBER to update the $template_table_query if the table changes!!!

/**
* Expand and collapse button labels
*/
define('EXPANDCODE', '&laquo; &raquo;');
define('COLLAPSECODE', '&raquo; &laquo;');

/**
* Size in rows of template editor <select>
*/
define('TEMPLATE_EDITOR_ROWS', 25);

/**
* List of special purpose templates used by css.php and build_style()
*/
$_query_common_templates = array(
	'header',
	'footer',
	'headinclude'
);

global $_query_special_templates;
 $_query_special_templates = array(
	// message editor menu contents
	'editor_jsoptions_font',
	'editor_jsoptions_size',
);

/**
* Initialize the IDs for colour preview boxes
*/
$numcolors = 0;

/**
* Query used for creating the temporary template table
*/
$template_table_query = "
CREATE TABLE " . TABLE_PREFIX . "template_temp (
	templateid INT UNSIGNED NOT NULL AUTO_INCREMENT,
	styleid SMALLINT NOT NULL DEFAULT '0',
	title VARCHAR(100) NOT NULL DEFAULT '',
	template MEDIUMTEXT,
	template_un MEDIUMTEXT,
	templatetype ENUM('template','stylevar','css','replacement') NOT NULL DEFAULT 'template',
	dateline INT UNSIGNED NOT NULL DEFAULT '0',
	username VARCHAR(100) NOT NULL DEFAULT '',
	version VARCHAR(30) NOT NULL DEFAULT '',
	product VARCHAR(25) NOT NULL DEFAULT '',
	mergestatus ENUM('none', 'merged', 'conflicted') NOT NULL DEFAULT 'none',
	PRIMARY KEY (templateid),
	UNIQUE KEY title (title, styleid, templatetype),
	KEY styleid (styleid)
)
";

/**
* Fields selected when copying the template table to template_temp
*/
$template_table_fields = 'styleid, title, template, template_un, templatetype, dateline, username, version, product, mergestatus';

// #############################################################################
/**
* Trims the string passed to it
*
* @param	string	(ref) String to be trimmed
*/
function array_trim(&$val)
{
	$val = trim($val);
}

// #############################################################################
/**
* Returns an SQL query string to update a single template
*
* @param	string	Title of template
* @param	string	Un-parsed template HTML
* @param	integer	Style ID for template
* @param	array	(ref) array('template' => array($title => true))
* @param	string	The name of the product this template is associated with
*
* @return	string
*/
function fetch_template_update_sql($title, $template, $dostyleid, &$delete, $product = 'vbulletin')
{
	global $vbulletin, $_query_special_templates, $template_cache;

	$oldtemplate = $template_cache['template']["$title"];

	if (is_array($template))
	{
		array_walk($template, 'array_trim');
		$template = "background: $template[background]; color: $template[color]; padding: $template[padding]; border: $template[border];";
	}

	// check if template should be deleted
	if ($delete['template']["$title"])
	{
		return "### DELETE TEMPLATE $title ###
			DELETE FROM " . TABLE_PREFIX . "template
			WHERE templateid = $oldtemplate[templateid]
		";
	}

	if ($template == $oldtemplate['template_un'])
	{
		return false;
	}
	else
	{
		// check for copyright removal
		if ($title == 'footer' // only check footer template
			AND strpos($template, '$vbphrase[powered_by_vbulletin]') === false // template to be saved has no copyright
			AND strpos($oldtemplate['template_un'], '$vbphrase[powered_by_vbulletin]') !== false // pre-saved template includes copyright - therefore a removal attempt is being made
		)
		{
			print_stop_message('you_can_not_remove_vbulletin_copyright');
		}

		// parse template conditionals
		if (!in_array($title, $_query_special_templates))
		{
			$parsedtemplate = compile_template($template);

			$errors = check_template_errors($parsedtemplate);

			// halt if errors in conditionals
			if (!empty($errors))
			{
				print_stop_message('error_in_template_x_y', $title, "<i>$errors</i>");
			}
		}
		else
		{
			$parsedtemplate =& $template;
		}

		$full_product_info = fetch_product_list(true);

		return "
			### REPLACE TEMPLATE: $title ###
			REPLACE INTO " . TABLE_PREFIX . "template
				(styleid, title, template, template_un, templatetype, dateline, username, version, product)
			VALUES
				(" . intval($dostyleid) . ",
				'" . $vbulletin->db->escape_string($title) . "',
				'" . $vbulletin->db->escape_string($parsedtemplate) . "',
				'" . $vbulletin->db->escape_string($template) . "',
				'template',
				" . intval(TIMENOW) . ",
				'" . $vbulletin->db->escape_string($vbulletin->userinfo['username']) . "',
				'" . $vbulletin->db->escape_string($full_product_info["$product"]['version']) . "',
				'" . $vbulletin->db->escape_string($product) . "')
		";
	}

}

// #############################################################################
/**
* Checks the style id of a template item and works out if it is inherited or not
*
* @param	integer	Style ID from template record
*
* @return	string	CSS class name to use to display item
*/
function fetch_inherited_color($itemstyleid, $styleid)
{
	switch ($itemstyleid)
	{
		case $styleid: // customized in current style, or is master set
			if ($styleid == -1 OR $styleid == -2)
			{
				return 'col-g';
			}
			else
			{
				return 'col-c';
			}
		case -2:
		case -1: // inherited from master set
		case 0:
			return 'col-g';
		default: // inhertited from parent set
			return 'col-i';
	}

}

// #############################################################################
/**
* Returns an array of all styles that are parents to the style specified
*
* @param	integer	Style ID
*
* @return	array
*/
function fetch_template_parentlist($styleid)
{
	global $vbulletin;

	$ts_info = $vbulletin->db->query_first("SELECT parentid, type FROM " . TABLE_PREFIX . "style WHERE styleid = $styleid");

	$ts_array = $styleid;

	if ($ts_info['parentid'] != 0)
	{
		#$ts_array .= ',' . fetch_style_parentlist($ts_info['parentid']);
		$ts_array .= ',' . fetch_template_parentlist($ts_info['parentid']);
	}

	if ($ts_info['type'])
	{
		$masterstyleid = $ts_info['type'] == 'standard' ? '-1' : '-2';
		if (substr($ts_array, -2) != $masterstyleid)
		{
			if (substr($ts_array, -1) != ',')
			{
				$ts_array .= ',';
			}
			$ts_array .= $masterstyleid;
		}
	}

	return $ts_array;
}

// #############################################################################
/**
* Saves the correct style parentlist to each style in the database
*/
function build_template_parentlists($mastertype = 'standard')
{
	global $vbulletin;

	$styles = $vbulletin->db->query_read("
		SELECT styleid, title, parentlist, parentid, userselect
		FROM " . TABLE_PREFIX . "style
		WHERE type = '" . $vbulletin->db->escape_string($mastertype) . "'
		ORDER BY parentid
	");
	while($style = $vbulletin->db->fetch_array($styles))
	{
		$parentlist = fetch_template_parentlist($style['styleid']);
		if ($parentlist != $style['parentlist'])
		{
			$vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "style
				SET parentlist = '" . $vbulletin->db->escape_string($parentlist) . "'
				WHERE styleid = $style[styleid]
			");
		}
	}
}

// #############################################################################
/**
* Returns the style parentlist for the specified style
*
* @param	integer	Style ID
*
* @return	string
*/
function fetch_style_parentlist($styleid)
{
	global $vbulletin, $ts_cache;

	static $ts_arraycache;

	if (isset($ts_arraycache["$styleid"]))
	{
		return $ts_arraycache["$styleid"];
	}
	elseif (isset($ts_cache["$styleid"]))
	{
		return $ts_cache["$styleid"]['parentlist'];
	}
	else
	{
		$ts_info = $vbulletin->db->query_first("
			SELECT parentlist
			FROM " . TABLE_PREFIX . "style
			WHERE styleid = $styleid
		");
		$ts_arraycache["$styleid"] = $ts_info['parentlist'];
		return $ts_info['parentlist'];
	}
}

function fetch_parentids($styleid)
{
	global $vbulletin;
	$style = $vbulletin->db->query_first("
			SELECT styleid, title, parentlist
			FROM " . TABLE_PREFIX . "style
			WHERE styleid = $styleid
	");
	if (empty($style))
	{
		trigger_error('Invalid styleid specified', E_USER_ERROR);
	}

	return $style['parentlist'];
}

// #############################################################################
/**
* Fetches a list of template IDs for the specified style
*
* @param	integer	Style ID
* @param	boolean	If true, returns a list of template ids; if false, goes ahead and runs the update query
* @param	mixed	A comma-separated list of style parent ids (if false, will query to fetch the list)
*
* @return	mixed	Either the list of template ids, or nothing
*/
function build_template_id_cache($styleid, $doreturn = false, $parentids = false)
{
	global $vbulletin;

	if ($styleid == -1 OR $styleid == -2)
	{
		// doesn't have a cache
		return '';
	}

	$type = $vbulletin->db->query_first("
		SELECT type
		FROM " . TABLE_PREFIX . "style
		WHERE styleid = {$styleid}
	");
	$masterstyleid = ($type['type'] == 'mobile' ? -2 : -1);

	//this is done as an array for historical reasons
	if ($parentids == 0)
	{
		$style['parentlist'] = fetch_parentids($styleid);
	}
	else
	{
		$style['parentlist'] = $parentids;
	}

	$parents = explode(',', $style['parentlist']);
	$i = sizeof($parents);
	$totalparents = $i;
	foreach($parents AS $setid)
	{
		if ($setid != -1 AND $setid != -2)
		{
			$querySele = ",\nt$i.templateid AS templateid_$i, t$i.title AS title$i, t$i.styleid AS styleid_$i $querySele";
			$queryJoin = "\nLEFT JOIN " . TABLE_PREFIX . "template AS t$i ON (t1.title=t$i.title AND t$i.styleid=$setid)$queryJoin";
			$i--;
		}
	}

	$bbcodestyles = array();
	$templatelist = array();
	$templates = $vbulletin->db->query_read("
		SELECT t1.templateid AS templateid_1, t1.title $querySele
		FROM " . TABLE_PREFIX . "template AS t1 $queryJoin
		WHERE t1.styleid IN ({$masterstyleid},0)
		ORDER BY t1.title
	");
	while ($template = $vbulletin->db->fetch_array($templates, DBARRAY_BOTH))
	{
		for ($tid = $totalparents; $tid > 0; $tid--)
		{
			if ($template["templateid_$tid"])
			{
				$templatelist["$template[title]"] = $template["templateid_$tid"];
				if (preg_match('#^bbcode_[code|html|php|quote]+$#si', trim($template['title'])))
				{
					$bbcodetemplate = $template['title'] . '_styleid';
					if ($template["styleid_$tid"])
					{
						$templatelist["$bbcodetemplate"] = $template["styleid_$tid"];
					}
					else
					{
						$templatelist["$bbcodetemplate"] = $masterstyleid;
					}
				}
				break;
			}
		}
	}

	$customdone = array();
	$customtemps = $vbulletin->db->query_read("
		SELECT t1.templateid, t1.title, INSTR(',$style[parentlist],', CONCAT(',', t1.styleid, ',') ) AS ordercontrol, t1.styleid
		FROM " . TABLE_PREFIX . "template AS t1
		LEFT JOIN " . TABLE_PREFIX . "template AS t2 ON (t2.title=t1.title AND t2.styleid = {$masterstyleid})
		WHERE t1.styleid IN (" . substr(trim($style['parentlist']), 0, -3) . ") AND
		t2.title IS NULL
		ORDER BY title, ordercontrol
	");
	while ($template = $vbulletin->db->fetch_array($customtemps))
	{
		if ($customdone["$template[title]"])
		{
			continue;
		}
		$customdone["$template[title]"] = 1;
		$templatelist["$template[title]"] = $template['templateid'];

		if (preg_match('#^bbcode_[code|html|php|quote]+$#si', trim($template['title'])))
		{
			$bbcodetemplate = $template['title'] . '_styleid';
			$templatelist["$bbcodetemplate"] = $template['styleid'];
		}
	}

	$templatelist = serialize($templatelist);

	if (!$doreturn)
	{
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "style
			SET templatelist = '" . $vbulletin->db->escape_string($templatelist) . "'
			WHERE styleid = $styleid
		");
	}
	else
	{
		return $templatelist;
	}
}

// #############################################################################
/**
* Builds all data from the template table into the fields in the style table
*
* @param	boolean	If true, will drop the template table and rebuild, so that template ids are renumbered from zero
* @param	boolean	If true, will fix styles with no parent style specified
* @param	string	If set, will redirect to specified URL on completion
* @param	boolean	If true, reset the master cache
* @param	mixed	Which master style to rebuild (standard/-1 or mobile/-2)
* @param	bool	Rebuild Style Datastore at end
*/
function build_all_styles($renumber = 0, $install = 0, $goto = '', $resetcache = false, $mastertype = 'standard', $builddatastore = true)
{
	global $vbulletin, $template_table_query, $template_table_fields, $vbphrase;

	if ($mastertype == -1)
	{
		$mastertype = 'standard';
	}
	else if ($mastertype == -2)
	{
		$mastertype = 'mobile';
	}

	// -----------------------------------------------------------------------------
	// -----------------------------------------------------------------------------
	// this bit of text is used for upgrade scripts where the phrase system
	// is not available it should NOT be converted into phrases!!!
	$phrases = array(
		'master_style' => 'MASTER STYLE',
		'mobile_master_style' => 'MOBILE MASTER STYLE',
		'done' => 'Done',
		'style' => 'Style',
		'styles' => 'Styles',
		'templates' => 'Templates',
		'css' => 'CSS',
		'stylevars' => 'Stylevars',
		'replacement_variables' => 'Replacement Variables',
		'controls' => 'Controls',
		'rebuild_style_information' => 'Rebuild Style Information',
		'updating_style_information_for_each_style' => 'Updating style information for each style',
		'updating_styles_with_no_parents' => 'Updating style sets with no parent information',
		'updated_x_styles' => 'Updated %1$s Styles',
		'no_styles_needed_updating' => 'No Styles Needed Updating',
	);
	foreach ($phrases AS $key => $val)
	{
		if (!isset($vbphrase["$key"]))
		{
			$vbphrase["$key"] = $val;
		}
	}
	// -----------------------------------------------------------------------------
	// -----------------------------------------------------------------------------

	if (!empty($goto))
	{
		$form_tags = true;
	}

	if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
	{
		echo "<!--<p>&nbsp;</p>-->
		<blockquote>" . iif($form_tags, "<form>") . "<div class=\"tborder\">
		<div class=\"tcat\" style=\"padding:4px\" align=\"center\"><b>" . $vbphrase['rebuild_style_information'] . "</b></div>
		<div class=\"alt1\" style=\"padding:4px\">\n<blockquote>
		";
		vbflush();
	}

	// useful for restoring utterly broken (or pre vb3) styles
	if ($install)
	{
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo "<p><b>" . $vbphrase['updating_styles_with_no_parents'] . "</b></p>\n<ul class=\"smallfont\">\n";
			vbflush();
		}
		$total = 0;
		if ($mastertype == 'standard')
		{
			$vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "style
				SET parentid = -1,
				parentlist = CONCAT(styleid,',-1')
				WHERE parentid = 0 AND type = 'standard'
			");
			$total += $vbulletin->db->affected_rows();
		}
		else
		{
			$mastertype = 'mobile';
			$vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "style
				SET parentid = -2,
				parentlist = CONCAT(styleid,',-2')
				WHERE parentid = 0 AND type = 'mobile'
			");
			$total += $vbulletin->db->affected_rows();
		}
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			if ($total)
			{
				echo "<li>" . construct_phrase($vbphrase['updated_x_styles'], $total) . "</li>\n";
				vbflush();
			}
			else
			{
				echo "<li>" . $vbphrase['no_styles_needed_updating'] . "</li>\n";
				vbflush();
			}
			echo "</ul>\n";
			vbflush();
		}
	}

	// creates a temporary table in order to renumber all templates from 1 to n sequentially
	if ($renumber)
	{
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo "<p><b>" . $vbphrase['updating_template_ids'] . "</b></p>\n<ul class=\"smallfont\">\n";
			vbflush();
		}
		$vbulletin->db->query_write("DROP TABLE IF EXISTS " . TABLE_PREFIX . "template_temp");
		$vbulletin->db->query_write($template_table_query);
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo "<li>" . $vbphrase['temporary_template_table_created'] . "</li>\n";
			vbflush();
		}

		/*insert query*/
		$vbulletin->db->query_write("
			INSERT INTO " . TABLE_PREFIX . "template_temp
			($template_table_fields)
			SELECT $template_table_fields FROM " . TABLE_PREFIX . "template ORDER BY styleid, templatetype, title
		");
		$rows = $vbulletin->db->affected_rows();
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo "<li>" . construct_phrase($vbphrase['temporary_template_table_populated_with_x_templates'], $rows) . "</li>\n";
			vbflush();
		}

		$vbulletin->db->query_write("DROP TABLE " . TABLE_PREFIX . "template");
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo "<li>" . $vbphrase['old_template_table_dropped'] . "</li>\n";
			vbflush();
		}

		$vbulletin->db->query_write("ALTER TABLE " . TABLE_PREFIX . "template_temp RENAME " . TABLE_PREFIX . "template");
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo "<li>" . $vbphrase['temporary_template_table_renamed'] . "</li>\n";
			vbflush();
			echo "</ul>\n";
			vbflush();
		}
	}

	if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
	{
		// the main bit.
		echo "<p><b>" . $vbphrase['updating_style_information_for_each_style'] . "</b></p>\n";
		vbflush();
	}

	build_template_parentlists($mastertype);

	$styleactions = array('docss' => 1, 'dostylevars' => 1, 'doreplacements' => 1);
	if ($mastertype == 'standard')
	{
		if ($error = build_style(-1, $vbphrase['master_style'], $styleactions, '', '', $resetcache))
		{
			return $error;
		}
	}
	else
	{
		if ($error = build_style(-2, $vbphrase['master_style'], $styleactions, '', '', $resetcache))
		{
			return $error;
		}
	}

	if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
	{
		echo "</blockquote></div>";
		if ($form_tags)
		{
			echo "
			<div class=\"tfoot\" style=\"padding:4px\" align=\"center\">
			<input type=\"button\" class=\"button\" value=\" " . $vbphrase['done'] . " \" onclick=\"window.location='$goto';\" />
			</div>";
		}
		echo "</div>" . iif($form_tags, "</form>") . "</blockquote>
		";
		vbflush();
	}

	if ($builddatastore)
	{
		build_style_datastore();
	}
}

// #############################################################################
/**
* Displays a style rebuild (build_style) in a nice user-friendly info page
*
* @param	integer	Style ID to rebuild
* @param	string	Title of style
* @param	boolean	Build CSS?
* @param	boolean	Build Stylevars?
* @param	boolean	Build Replacements?
*/
function print_rebuild_style($styleid, $title = '', $docss = 1, $dostylevars = 1, $doreplacements = 1)
{
	global $vbulletin, $vbphrase;

	$styleid = intval($styleid);

	if (empty($title))
	{
		if ($styleid == -1)
		{
			$title = $vbphrase['master_style'];
		}
		else if ($styleid == -2)
		{
			$title = $vbphrase['mobile_master_style'];
		}
		else
		{
			DEVDEBUG('Querying first style name');
			$getstyle = $vbulletin->db->query_first("
				SELECT title
				FROM " . TABLE_PREFIX . "style
				WHERE styleid = $styleid
			");
			if (!$getstyle)
			{
				return;
			}

			$title = htmlspecialchars_uni($getstyle['title']);
		}
	}
	else
	{
		$title = htmlspecialchars($title);
	}

	if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
	{
		echo "<p>&nbsp;</p>
		<blockquote><form><div class=\"tborder\">
		<div class=\"tcat\" style=\"padding:4px\" align=\"center\"><b>" . $vbphrase['rebuild_style_information'] . "</b></div>
		<div class=\"alt1\" style=\"padding:4px\">\n<blockquote>
		<p><b>" . construct_phrase($vbphrase['updating_style_information_for_x'], $title) . "</b></p>
		<ul class=\"lci\">\n";
		vbflush();
	}

	build_style($styleid, $title, array(
		'docss' => $docss,
		'dostylevars' => $dostylevars,
		'doreplacements' => $doreplacements
	));

	if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
	{
		echo "</ul>\n<p><b>" . $vbphrase['done'] . "</b></p>\n</blockquote></div>
		</div></form></blockquote>
		";
		vbflush();
	}
}

// #############################################################################
/**
* Attempts to delete the file specified in the <link rel /> for this style
*
* @param	integer	Style ID
* @param	string	CSS contents
*/
function delete_css_file($styleid, $csscontents)
{
	if (preg_match('#@import url\("(clientscript/vbulletin_css/style-\w{8}-0*' . $styleid . '\.css)"\);#siU', $csscontents, $match))
	{
		// attempt to delete old css file
		@unlink("./$match[1]");
	}
}

function delete_style_css_directory($styleid, $dir = 'ltr')
{
	$styledir = 'clientscript/vbulletin_css/style' . str_pad($styleid, 5, '0', STR_PAD_LEFT) . ($dir == 'ltr' ? 'l' : 'r');
	if (is_dir($styledir))
	{
		if (!is_dir("$styledir/$file"))
		{
			if (!is_dir($file))
			{
				@unlink("$styledir/$file");
			}
		}
	}

	@rmdir($styledir);
}

// #############################################################################
/**
* Attempts to create a new css file for this style
*
* @param	string	CSS filename
* @param	string	CSS contents
*
* @return	boolean	Success
*/
function write_css_file($filename, $contents)
{
	// attempt to write new css file - store in database if unable to write file
	if ($fp = @fopen(DIR . "/$filename", 'wb') AND !is_demo_mode())
	{
		fwrite($fp, minify($contents));
		@fclose($fp);
		return true;
	}
	else
	{
		@fclose($fp);
		return false;
	}
}

/**
*	Switch the style for rendering
*	This really should be part of the bootstrap code except:
*	1) We don't actually load the bootstrap in the admincp
* 2) There is a lot to the style load that isn't easy to redo (header/footer templates for example)
*
* This handles the stylevars and template lists -- including reloading the template cache.
* This is enough to handle the css template rendering, but probably won't work for anything
* more complicated.
*/
function switch_css_style($styleid, $templates)
{
	global $vbulletin;
	$styletemp = $vbulletin->db->query_first ("
		SELECT *
		FROM " . TABLE_PREFIX . "style
		WHERE  styleid = " . intval($styleid)
	);

	if (!$styletemp)
	{
		return false;
	}

	global $style;
	$style = $styletemp;

	$vbulletin->stylevars = unserialize($style['newstylevars']);
	fetch_stylevars($style, $vbulletin->userinfo);

	global $templateassoc;
	//clear the template cache, otherwise we might get old templates
	$vbulletin->templatecache = array();
	$templateassoc = null;
	cache_templates($templates, $style['templatelist']);
}


function write_style_css_directory($styleid, $parentlist, $dir = 'ltr')
{
	global $vbulletin;

	//verify that we have or can create a style directory
	$styledir = 'clientscript/vbulletin_css/style' . str_pad($styleid, 5, '0', STR_PAD_LEFT) . ($dir == 'ltr' ? 'l' : 'r');

	//if we have a file that's not a directory or not writable something is wrong.
	if (file_exists($styledir) AND (!is_dir($styledir) OR !is_writable($styledir)))
	{
		return false;
	}

	//clear any old files.
	if (file_exists($styledir))
	{
		delete_style_css_directory($styleid, $dir);
	}

	//create the directory -- if it still exists try to continue with the existing dir
	if (!file_exists($styledir))
	{
		if (!@mkdir($styledir))
		{
			return false;
		}
	}

	//check for success.
	if (!is_dir($styledir) OR !is_writable($styledir))
	{
		return false;
	}

	//write out the files for this style.
	$set = $vbulletin->db->query_read($sql = "
		SELECT DISTINCT title
		FROM " . TABLE_PREFIX . "template
		WHERE styleid IN (" . $parentlist . ")
		AND templatetype = 'template' AND title LIKE '%.css'
	");

	//collapse the list.
	$css_templates = array();
	while($row = $vbulletin->db->fetch_array($set))
	{
		$css_templates[] = $row['title'];
	}

	switch_css_style($styleid, $css_templates);

	if ($dir == 'ltr')
	{
		vB_Template_Runtime::addStyleVar('left', 'left');
		vB_Template_Runtime::addStyleVar('right', 'right');
		vB_Template_Runtime::addStyleVar('textdirection', 'ltr');
	}
	else
	{
		vB_Template_Runtime::addStyleVar('left', 'right');
		vB_Template_Runtime::addStyleVar('right', 'left');
		vB_Template_Runtime::addStyleVar('textdirection', 'rtl');
	}

	$templates = array();
	foreach ($css_templates AS $title)
	{
		//I'd call this a hack but there probably isn't a cleaner way to do this.
		//The css is published to a different directory than the css.php file
		//which means that relative urls that works for css.php won't work for the
		//published directory.  Unfortunately urls from the webroot don't work
		//because the forum often isn't located at the webroot and we can only
		//specify urls from the forum root.  And css doens't provide any way
		//of setting a base url like html does.  So we are left to "fixing"
		//any relative urls in the published css.
		//
		//We leave alone any urls starting with '/', 'http', and 'https:'
		//there are other valid urls, but nothing that people should be
		//using in our css files.

		$text = vB_Template::create($title)->render(true);
		$re = '#url\(\s*["\']?+(?!/|https?:|data:)#';
		$base = $vbulletin->options['bburl'];
		if ($base[-1] != '/')
		{
			$base .= '/';
		}
		$text = preg_replace ($re, "$0$base", $text);

		$templates[$title] = $text;
		if (!write_css_file("$styledir/$title", $text))
		{
			return false;
		}
	}

	static $vbdefaultcss, $cssfiles, $csstemplates;

	if (empty($vbdefaultcss))
	{
		$vbdefaultcss = array();

		// Now write the rollup templates
		require_once(DIR . '/includes/class_xml.php');
		$cssfiles = array();
		if ($handle = @opendir(DIR . '/includes/xml/'))
		{
			while (($file = readdir($handle)) !== false)
			{
				if (!preg_match('#^cssrollup_(.*).xml$#i', $file, $matches))
				{
					continue;
				}
				$css_key = preg_replace('#[^a-z0-9]#i', '', $matches[1]);
				$cssfiles["$css_key"]['name'] = $file;
			}
			closedir($handle);
		}

		if (empty($cssfiles['vbulletin']))	// opendir failed or cpnav_vbulletin.xml is missing
		{
			if (is_readable(DIR . '/includes/xml/cssrollup_vbulletin.xml'))
			{
				$cssfiles['vbulletin']['name'] = 'cssrollup_vbulletin.xml';
			}
			else
			{
				echo construct_phrase($vbphrase['could_not_open_x'], DIR . '/includes/xml/cssrollup_vbulletin.xml');
				exit;
			}
		}

		unset($cssfiles['vbulletin']);

		$xmlobj = new vB_XML_Parser(false, DIR . "/includes/xml/cssrollup_vbulletin.xml");
		$data = $xmlobj->parse();

		if (!is_array($data['rollup'][0]))
		{
			$data['rollup'] = array($data['rollup']);
		}

		foreach ($data['rollup'] AS $file)
		{
			foreach ($file['template'] AS $name)
			{
				$vbdefaultcss["$file[name]"] = $file['template'];
			}
		}

		foreach ($cssfiles AS $css_file => $file)
		{
			$xmlobj = new vB_XML_Parser(false, DIR . "/includes/xml/$file[name]");
			$data = $xmlobj->parse();

			if ($data['product'] AND empty($vbulletin->products["$data[product]"]))
			{
				// attached to a specific product and that product isn't enabled
				continue;
			}

			if (!is_array($data['rollup'][0]))
			{
				$data['rollup'] = array($data['rollup']);
			}

			$cssfiles["$css_file"]['css'] = $data['rollup'];
		}
	}

	foreach ($cssfiles AS $css_file => $files)
	{
		if (is_array($files['css']))
		{
			foreach ($files['css'] AS $file)
			{
				if (process_css_rollup_file($file['name'], $file['template'], $templates, $styledir, $vbdefaultcss) === false)
				{
					return false;
				}
			}
		}
	}

	foreach ($vbdefaultcss AS $xmlfile => $files)
	{
		if (process_css_rollup_file($xmlfile, $files, $templates, $styledir) === false)
		{
			return false;
		}
	}

	return true;
}

function minify($text)
{
	$search1 = array(
		'#/\*.*?\*/#s',
  	'#(\t|\r|\n)#',
  	'#/[^}{]+{\s?}#',
  	'#\s+#',
  	'#\s*{\s*#',
  	'#\s*}\s*#',
  );
  $replace1 = array('', '', '', ' ', '{', '}');
  $text = preg_replace($search1, $replace1, $text);

	$search2 = array(';}', ', ', '; ', ': ');
	$replace2 = array('}', ',', ';', ':');
	$text = str_replace($search2, $replace2, $text);

  $text = preg_replace('#\s+#', ' ', $text);

	return $text;
}

function process_css_rollup_file($file, $templatelist, $templates, $styledir, &$vbdefaultcss = array())
{
	if (!is_array($templatelist))
	{
		$templatelist = array($templatelist);
	}

	if ($vbdefaultcss AND $vbdefaultcss["$file"])
	{
		// Add these templates to the main file rollup
		$vbdefaultcss["$file"] = array_unique(array_merge($vbdefaultcss["$file"], $templatelist));
		return true;
	}

	foreach ($templatelist AS $name)
	{
		$template = $templates["$name"];
		if ($count > 0)
		{
			$text .= "\r\n\r\n";
			$template = preg_replace("#@charset .*#i", "", $template);
		}
		$text .= $template;
		$count++;
	}

	if (!write_css_file("$styledir/$file", $text))
	{
		return false;
	}

	return true;
}

// #############################################################################
/**
* Converts all data from the template table for a style into the style table
*
* @param	integer	Style ID
* @param	string	Title of style
* @param	array	Array of actions set to true/false: docss/dostylevars/doreplacements
* @param	string	List of parent styles
* @param	string	Indent for HTML printing
* @param	boolean	Reset the master cache
*/
function build_style($styleid, $title = '', $actions = array(), $parentlist = '', $indent = '', $resetcache = false)
{
	global $vbulletin, $_queries, $vbphrase, $_query_special_templates;
	static $phrase, $csscache;

	if (($actions['doreplacements'] OR $actions['docss'] OR $actions['dostylevars']) AND $vbulletin->options['storecssasfile'])
	{
		$actions['docss'] = true;
		$actions['doreplacements'] = true;
	}

	if ($styleid != -1 AND $styleid != -2)
	{
		$style = $vbulletin->db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "style
			WHERE styleid = {$styleid}
		");
		$masterstyleid = ($style['type'] == 'standard' ? -1 : -2);
		$QUERY = array(
			'' => "dateline = " . TIMENOW
		);

		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			// echo the title and start the listings
			echo "$indent<li><b>$title</b> ... <span class=\"smallfont\">";
			vbflush();
		}

		// build the templateid cache
		if (!$parentlist)
		{
			$parentlist = fetch_parentids($styleid);
		}

		$templatelist = build_template_id_cache($styleid, 1, $parentlist);
		$QUERY[] = "templatelist = '" . $vbulletin->db->escape_string($templatelist)  . "'";

		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo "($vbphrase[templates]) ";
			vbflush();
		}

		// cache special templates
		if ($actions['docss'] OR $actions['dostylevars'] OR $actions['doreplacements'])
		{
			// get special templates for this style
			$template_cache = array();
			if ($templateids = implode(',' , unserialize($templatelist)))
			{
				$templates = $vbulletin->db->query_read("
					SELECT title, template, templatetype
					FROM " . TABLE_PREFIX . "template
					WHERE templateid IN ($templateids)
						AND (templatetype <> 'template' OR title IN('" . implode("', '", $_query_special_templates) . "'))
				");
				while ($template = $vbulletin->db->fetch_array($templates))
				{
					$template_cache["$template[templatetype]"]["$template[title]"] = $template;
				}
				$vbulletin->db->free_result($templates);
			}
		}

		// style vars
		if ($actions['dostylevars'])
		{
			// rebuild the stylevars field for this style
			$stylevars = array();
			if ($template_cache['stylevar'])
			{
				foreach($template_cache['stylevar'] AS $template)
				{
					$stylevars["$template[title]"] = $template['template'];
				}

				$QUERY[] = "stylevars = '" . $vbulletin->db->escape_string(serialize($stylevars)) . '\'';
			}

			if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
			{
				echo "($vbphrase[stylevars]) ";
				vbflush();
			}

			static $master_stylevar_cache = array(
				-1 => null,
				-2 => null
			);
			static $resetcachedone = array(
				-1 => false,
				-2 => false
			);
			if ($resetcache AND !$resetcachedone[$masterstyleid])
			{
				$resetcachedone[$masterstyleid] = true;
				$master_stylevar_cache[$masterstyleid] = null;
			}
			if ($master_stylevar_cache[$masterstyleid] === null)
			{
				$master_stylevar_cache[$masterstyleid] = array();
				$master_stylevars = $vbulletin->db->query_read("
				SELECT stylevardfn.stylevarid, stylevardfn.datatype, stylevar.value
				FROM " . TABLE_PREFIX . "stylevardfn AS stylevardfn
				LEFT JOIN " . TABLE_PREFIX . "stylevar AS stylevar ON (stylevardfn.stylevarid = stylevar.stylevarid AND stylevar.styleid = {$masterstyleid})
				");
				while ($master_stylevar = $vbulletin->db->fetch_array($master_stylevars))
				{
					$tmp = unserialize($master_stylevar['value']);
					if (!is_array($tmp))
					{
						$tmp = array('value' => $tmp);
					}

					$master_stylevar_cache[$masterstyleid][$master_stylevar['stylevarid']] = $tmp;
					$master_stylevar_cache[$masterstyleid][$master_stylevar['stylevarid']]['datatype'] = $master_stylevar['datatype'];
				}
			}

			$newstylevars = $master_stylevar_cache[$masterstyleid];

			if (substr(trim($parentlist), 0, -3) != '')
			{
				$new_stylevars = $vbulletin->db->query_read($sql = "
				SELECT
					stylevarid, styleid, value, INSTR(',$parentlist,', CONCAT(',', styleid, ',') ) AS ordercontrol
				FROM " . TABLE_PREFIX . "stylevar
				WHERE
					styleid IN (" . substr(trim($parentlist), 0, -3) . ")
				ORDER BY
					ordercontrol DESC
				");
				while ($new_stylevar = $vbulletin->db->fetch_array($new_stylevars))
				{
					$newstylevars[$new_stylevar['stylevarid']] = unserialize($new_stylevar['value']);
					$newstylevars[$new_stylevar['stylevarid']]['datatype'] = $master_stylevar_cache[$masterstyleid][$new_stylevar['stylevarid']]['datatype'];
				}
			}

			if ($newstylevars)
			{
				$QUERY[] = "newstylevars = '" . $vbulletin->db->escape_string(serialize($newstylevars)) . '\'';
			}
		}

		// replacements
		if ($actions['doreplacements'])
		{
			// rebuild the replacements field for this style
			$replacements = array();
			if (is_array($template_cache['replacement']))
			{
				foreach($template_cache['replacement'] AS $template)
				{
					// set the key to be a case-insentitive preg find string
					$replacementkey = '#' . preg_quote($template['title'], '#') . '#si';

					$replacements["$replacementkey"] = $template['template'];
				}
				$QUERY[] = 'replacements = \'' . $vbulletin->db->escape_string(serialize($replacements)) . '\'';
			}
			else
			{
				$QUERY[] = 'replacements = \'\'';
			}
			if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
			{
				echo "($vbphrase[replacement_variables]) ";
				vbflush();
			}
		}

		// css -- old style css
		if ($actions['docss'] AND $template_cache['css'])
		{
			// build a quick cache with the ~old~ contents of the css fields from the style table
			if (!is_array($csscache))
			{
				$csscache = array();
				$fetchstyles = $vbulletin->db->query_read("SELECT styleid, css FROM " . TABLE_PREFIX . "style");
				while ($fetchstyle = $vbulletin->db->fetch_array($fetchstyles))
				{
					$fetchstyle['css'] .= "\n";
					$csscache["$fetchstyle[styleid]"] = $fetchstyle['css'];
				}
			}

			// rebuild the css field for this style
			$css = array();
			foreach($template_cache['css'] AS $template)
			{
				$css["$template[title]"] = unserialize($template['template']);
			}

			// build the CSS contents
			$csscolors = array();
			$css = construct_css($css, $styleid, $title, $csscolors);

			// attempt to delete the old css file if it exists
			delete_css_file($styleid, $csscache["$styleid"]);

			$adblock_is_evil = str_replace('ad', 'be', substr(md5(microtime()), 8, 8));
			$cssfilename = 'clientscript/vbulletin_css/style-' . $adblock_is_evil . '-' . str_pad($styleid, 5, '0', STR_PAD_LEFT) . '.css';

			// if we are going to store CSS as files, run replacement variable substitution on the file to be saved
			if ($vbulletin->options['storecssasfile'])
			{
				$css = process_replacement_vars($css, array('styleid' => $styleid, 'replacements' => serialize($replacements)));
				$css = preg_replace('#(?<=[^a-z0-9-]|^)url\((\'|"|)(.*)\\1\)#iUe', "rewrite_css_file_url('\\2', '\\1')", $css);
				if (write_css_file($cssfilename, $css))
				{
					$css = "@import url(\"$cssfilename\");";
				}
			}

			$fullcsstext = "<style type=\"text/css\" id=\"vbulletin_css\">\r\n" .
				"/**\r\n* vBulletin " . $vbulletin->options['templateversion'] . " CSS\r\n* Style: '$title'; Style ID: $styleid\r\n*/\r\n" .
				"$css\r\n</style>\r\n" .
				"<link rel=\"stylesheet\" type=\"text/css\" href=\"clientscript/vbulletin_important.css?v=" . $vbulletin->options['simpleversion'] . "\" />"
			;

			$QUERY[] = "css = '" . $vbulletin->db->escape_string($fullcsstext) . "'";
			$QUERY[] = "csscolors = '" . $vbulletin->db->escape_string(serialize($csscolors)) . "'";

			if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
			{
				echo "($vbphrase[css]) ";
				vbflush();
			}
		}

		// do the style update query
		if (sizeof($QUERY))
		{
			$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "style SET\n" . implode(",\n", $QUERY) . "\nWHERE styleid = $styleid");
		}

		//write out the new css -- do this *after* we update the style record
		if ($vbulletin->options['storecssasfile'])
		{
			if (!write_style_css_directory($styleid, $parentlist, 'ltr'))
			{
				if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
				{
					echo fetch_error("rebuild_failed_to_write_css");
				}
				else
				{
					return fetch_error("rebuild_failed_to_write_css");
				}
			}
			else if (!write_style_css_directory($styleid, $parentlist, 'rtl'))
			{
				if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
				{
					echo fetch_error("rebuild_failed_to_write_css");
				}
				else
				{
					return fetch_error("rebuild_failed_to_write_css");
				}
			}
		}
		else
		{
			// race condition here
			//delete_style_css_directory($styleid, 'ltr');
			//delete_style_css_directory($styleid, 'rtl');
		}

		// finish off the listings
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo "</span><b>" . $vbphrase['done'] . "</b>.<br />&nbsp;</li>\n"; vbflush();
		}
	}

	$childsets = $vbulletin->db->query_read("
		SELECT
			styleid, title, parentlist
		FROM " . TABLE_PREFIX . "style
		WHERE
			parentid = $styleid
	");
	if ($vbulletin->db->num_rows($childsets))
	{
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo "$indent<ul class=\"ldi\">\n";
		}
		while ($childset = $vbulletin->db->fetch_array($childsets))
		{
			if ($error = build_style($childset['styleid'], $childset['title'], $actions, $childset['parentlist'], $indent . "\t", $resetcache))
			{
				return $error;
			}
		}
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo "$indent</ul>\n";
		}
	}
}

// #############################################################################
/**
* Extracts a color value from a css string
*
* @param	string	CSS color value
*
* @return	string
*/
function fetch_color_value($csscolor)
{
	if (preg_match('/^(rgb\s*\([0-9,\s]+\)|(#?\w+))(\s|$)/siU', $csscolor, $match))
	{
		return $match[1];
	}
	else
	{
		return $csscolor;
	}
}

/**
 * Attempts to return a six-character hex value for a given color value (hex, rgb or named)
 *
 * @param	string	CSS color value
 * @return	string
 */
function fetch_color_hex_value($csscolor)
{
	static $html_color_names = null,
	       $html_color_names_regex = null,
	       $system_color_names = null,
	       $system_color_names_regex = null;

	if (!is_array($html_color_names))
	{
		require_once(DIR . '/includes/html_color_names.php');

		$html_color_names_regex = implode('|', array_keys($html_color_names));

		$system_color_names = (
			strpos(strtolower(USER_AGENT), 'macintosh') !== false
			? $system_color_names_mac
			: $system_color_names_win
		);

		$system_color_names_regex = implode('|', array_keys($system_color_names));
	}

	$hexcolor = '';

	// match a hex color
	if (preg_match('/\#([0-9a-f]{6}|#[0-9a-f]{3})($|[^0-9a-f])/siU', $csscolor, $match))
	{
		if (strlen($match[1]) == 3)
		{
			$hexcolor .= $match[1]{0} . $match[1]{0} . $match[1]{1} . $match[1]{1} . $match[1]{2} . $match[1]{2};
		}
		else
		{
			$hexcolor .= $match[1];
		}
	}
	// match an RGB color
	else if (preg_match('/rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/siU', $csscolor, $match))
	{
		for ($i = 1; $i <= 3; $i++)
		{
			$hexcolor .= str_pad(dechex($match["$i"]), 2, 0, STR_PAD_LEFT);
		}
	}
	// match a named color
	else if (preg_match("/(^|[^\w])($html_color_names_regex)($|[^\w])/siU", $csscolor, $match))
	{
		$hexcolor = $html_color_names[strtolower($match[2])];
	}
	// match a named system color (CSS2, deprecated)
	else if (preg_match("/(^|[^\w])($system_color_names_regex)($|[^\w])/siU", $csscolor, $match))
	{
		$hexcolor = $system_color_names[strtolower($match[2])];
	}
	else
	{
		// failed to match a color
		return false;
	}

	return strtoupper($hexcolor);
}

// #############################################################################
/**
* Reads the input from the CSS editor and builds it into CSS code
*
* @param	array	Submitted data from css.php?do=edit
* @param	integer	Style ID
* @param	string	Title of style
* @param	array	(ref) Array of extracted CSS colour values
*
* @return	string
*/
function construct_css($css, $styleid, $styletitle, &$csscolors)
{
	global $vbulletin;

	// remove the 'EXTRA' definition and stuff it in at the end :)
	$extra = trim($css['EXTRA']['all']);
	$extra2 = trim($css['EXTRA2']['all']);
	unset($css['EXTRA'], $css['EXTRA2']);

	// initialise the stylearray
	$cssarray = array();

	// order for writing out CSS variables
	$css_write_order = array(
		'body',
		'.page',
		'td, th, p, li',
		'.tborder',
		'.tcat',
		'.thead',
		'.tfoot',
		'.alt1, .alt1Active',
		'.alt2, .alt2Active',
		'.inlinemod',
		'.wysiwyg',
		'textarea, .bginput',
		'.button',
		'select',
		'.smallfont',
		'.time',
		'.navbar',
		'.highlight',
		'.fjsel',
		'.fjdpth0',
		'.fjdpth1',
		'.fjdpth2',
		'.fjdpth3',
		'.fjdpth4',

		'.panel',
		'.panelsurround',
		'legend',

		'.vbmenu_control',
		'.vbmenu_popup',
		'.vbmenu_option',
		'.vbmenu_hilite',
	);

	($hook = vBulletinHook::fetch_hook('css_output_build')) ? eval($hook) : false;

	// loop through the $css_write_order array to make sure we
	// write the css into the template in the correct order

	foreach($css_write_order AS $itemname)
	{
		unset($links, $thisitem);
		if (is_array($css["$itemname"]))
		{
			foreach($css["$itemname"] AS $cssidentifier => $value)
			{
				if (preg_match('#^\.(\w+)#si', $itemname, $match))
				{
					$itemshortname = $match[1];
				}
				else
				{
					$itemshortname = $itemname;
				}

				switch ($cssidentifier)
				{
					// do normal links
					case 'LINK_N':
					{
						if ($getlinks = construct_link_css($itemname, $cssidentifier, $value))
						{
							$links['normal'] = $getlinks;
						}
					}
					break;

					// do visited links
					case 'LINK_V':
					{
						if ($getlinks = construct_link_css($itemname, $cssidentifier, $value))
						{
							$links['visited'] = $getlinks;
						}
					}
					break;

					// do hover links
					case 'LINK_M':
					{
						if ($getlinks = construct_link_css($itemname, $cssidentifier, $value))
						{
							$links['hover'] = $getlinks;
						}
					}
					break;

					// do extra attributes
					case 'EXTRA':
					case 'EXTRA2':
					{
						if (!empty($value))
						{
							$value = "\t" . str_replace("\r\n", "\r\n\t", $value);
							$thisitem[] = "$value\r\n";
						}
					}
					break;

					// do font bits
					case 'font':
					{
						if ($getfont = construct_font_css($value))
						{
							$thisitem[] = $getfont;
						}
					}
					break;

					// normal attributes
					default:
					{
						$value = trim($value);
						if ($value != '')
						{
							switch ($cssidentifier)
							{
								case 'background':
								{
									$csscolors["{$itemshortname}_bgcolor"] = fetch_color_value($value);
								}
								break;

								case 'color':
								{
									$csscolors["{$itemshortname}_fgcolor"] = fetch_color_value($value);
								}
								break;
							}
							$thisitem[] = "\t$cssidentifier: $value;\r\n";
						}
					}

				}
			}
		}
		// add the item to the css if it's not blank
		if (sizeof($thisitem) > 0)
		{
			$cssarray[] = "$itemname\r\n{\r\n" . implode('', $thisitem) . "}\r\n" . $links['normal'] . $links['visited'] . $links['hover'];

			if ($itemname == 'select')
			{
				$optioncss = array();
				if ($optionsize = trim($css["$itemname"]['font']['size']))
				{
					$optioncss[] = "\tfont-size: $optionsize;\r\n";
				}
				if ($optionfamily = trim($css["$itemname"]['font']['family']))
				{
					$optioncss[] = "\tfont-family: $optionfamily;\r\n";
				}
				$cssarray[] = "option, optgroup\r\n{\r\n" . implode('', $optioncss) . "}\r\n";
			}
			else if ($itemname == 'textarea, .bginput')
			{
				$optioncss = array();
				if ($optionsize = trim($css["$itemname"]['font']['size']))
				{
					$optioncss[] = "\tfont-size: $optionsize;\r\n";
				}
				if ($optionfamily = trim($css["$itemname"]['font']['family']))
				{
					$optioncss[] = "\tfont-family: $optionfamily;\r\n";
				}
				$cssarray[] = ".bginput option, .bginput optgroup\r\n{\r\n" . implode('', $optioncss) . "}\r\n";
			}
		}
	}

	// generate hex colors
	foreach ($css_write_order AS $itemname)
	{
		if (is_array($css["$itemname"]))
		{
			$itemshortname = (strpos($itemname, '.') === 0 ? substr($itemname, 1) : $itemname);

			foreach($css["$itemname"] AS $cssidentifier => $value)
			{
				switch ($cssidentifier)
				{
					case 'LINK_N':
					case 'LINK_V':
					case 'LINK_M':
					{
						if ($value['color'] != '')
						{
							$csscolors[$itemshortname . '_' . strtolower($cssidentifier) . '_fgcolor'] = fetch_color_value($value['color']);
						}

						if ($value['background'] != '')
						{
							$csscolors[$itemshortname . '_' . strtolower($cssidentifier) . '_bgcolor'] = fetch_color_value($value['background']);
						}
					}
					break;

					// do extra attributes
					case 'EXTRA':
					case 'EXTRA2':
					{
						if (preg_match('#border(-color)?\s*\:\s*([^;]+);#siU', $value, $match))
						{
							$csscolors[$itemshortname . '_border_color'] = fetch_color_value($match[2]);
						}
					}
					break;
				}
			}
		}
	}

	$csscolors_hex = array();

	foreach ($csscolors AS $colorname => $colorvalue)
	{
		$hexcolor = fetch_color_hex_value($colorvalue);

		if ($hexcolor !== false)
		{
			$csscolors_hex[$colorname . '_hex'] = $hexcolor;
		}
	}

	$csscolors = array_merge($csscolors, $csscolors_hex);

	($hook = vBulletinHook::fetch_hook('css_output_build_end')) ? eval($hook) : false;

	return trim(implode('', $cssarray) . "$extra\r\n$extra2");
}

// #############################################################################
/**
* Returns a URL for use in CSS, dealing with directory nesting etc.
*
* @param	string	URL
* @param	string	Quote type (single quote, double quote)
*
* @return	string	example: url('/path/to/file.ext')
*/
function rewrite_css_file_url($url, $delimiter = '')
{
	static $iswritable = null;
	if ($iswritable === null)
	{
		$iswritable = is_writable(DIR . '/clientscript/vbulletin_css/');
	}

	$url = str_replace('\\"', '"', $url);
	$delimiter = str_replace('\\"', '"', $delimiter);

	if (!$iswritable OR preg_match('#^(https?://|/)#i', $url))
	{
		return "url($delimiter$url$delimiter)";
	}
	else
	{
		return "url($delimiter../../$url$delimiter)";
	}
}

// #############################################################################
/**
* Takes the font style input from css.php?do=edit and returns valid CSS
*
* @param	array	Array of values from form
*
* @return	string
*/
function construct_font_css($font)
{
	// possible values for CSS 'font-weight' attribute
	$css_font_weight = array('normal', 'bold', 'bolder', 'lighter');

	// possible values for CSS 'font-style' attribute
	$css_font_style = array('normal', 'italic', 'oblique');

	// possible values for CSS 'font-variant' attribute
	$css_font_variant = array('normal', 'small-caps');

	foreach($font AS $key => $value)
	{
		$font["$key"] = trim($value);
	}

	$out = '';

	if (!empty($font['size']) AND !empty($font['family']))
	{

		foreach ($font AS $value)
		{
			$out .= "$value ";
		}
		$out = trim($out);
		if (!empty($out))
		{
			$out = "\tfont: $out;\r\n";
		}

	}
	else
	{

		if (!empty($font['size']))
		{
			$out .= "\tfont-size: $font[size];\r\n";
		}
		if (!empty($font['family']))
		{
			$out .= "\tfont-family: $font[family];\r\n";
		}
		if (!empty($font['style']))
		{
			$stylebits = explode(' ', $font['style']);
			foreach($stylebits AS $bit)
			{
				$bit = strtolower($bit);
				if (in_array($bit, $css_font_weight) OR preg_match('/[1-9]{1}00/', $bit))
				{
					$out .= "\tfont-weight: $bit;\r\n";
				}
				if (in_array($bit, $css_font_style))
				{
					$out .= "\tfont-style: $bit;\r\n";
				}
				if (in_array($bit, $css_font_variant))
				{
					$out .= "\tfont-variant: $bit;\r\n";
				}
				if (preg_match('/(pt|\.|%)/siU', $bit))
				{
					$out .= "\tline-height: $bit;\r\n";
				}
			}
		}

	}

	if (trim($out) == '')
	{
		return false;
	}
	else
	{
		return $out;
	}

}

// #############################################################################
/**
* Takes the link style input from css.php?do=edit and returns valid CSS
*
* @param	array	Items from form
* @param	string	Link type (LINK_N, LINK_V etc.)
* @param	array	Attributes array
*
* @return	string
*/
function construct_link_css($item, $what, $array)
{
	$out = '';
	foreach($array AS $attribute => $value)
	{
		$value = trim($value);
		if (!empty($value))
		{
			$out .= "\t$attribute: $value;\r\n";
		}
	}

	if (!empty($out))
	{
		$item_bits = '';
		$items = explode(',', $item);
		foreach ($items AS $one_item)
		{
			$one_item = trim($one_item);
			if (!empty($one_item))
			{
				if ($what == 'LINK_N')
				{
					$item_bits .= ", $one_item a:link, {$one_item}_alink";
				}
				else if ($what == 'LINK_V')
				{
					$item_bits .= ", $one_item a:visited, {$one_item}_avisited";
				}
				else
				{
					$item_bits .= ", $one_item a:hover, $one_item a:active, {$one_item}_ahover";
				}
			}
		}
		$item_bits = str_replace('body a:', 'a:', substr($item_bits, 2));
		switch ($what)
		{
			case 'LINK_N':
				return "$item_bits\r\n{\r\n$out}\r\n";
			case 'LINK_V':
				return "$item_bits\r\n{\r\n$out}\r\n";
			default:
				return "$item_bits\r\n{\r\n$out}\r\n";
		}
	}
	else
	{
		return false;
	}
}

// #############################################################################
/**
* Prints out a style editor block, as seen in template.php?do=modify
*
* @param	integer	Style ID
* @param	array	Style info array
*/
function print_style($styleid, $style = '', $mastertype = -1)
{
	global $vbulletin, $stylecache, $masterset;
	global $only, $_query_special_templates;
	global $SHOWTEMPLATE, $vbphrase;

	$titlesonly =& $vbulletin->GPC['titlesonly'];
	$expandset =& $vbulletin->GPC['expandset'];
	$group =& $vbulletin->GPC['group'];
	$searchstring =& $vbulletin->GPC['searchstring'];

	$style['title'] = htmlspecialchars_uni($style['title']);
	if ($styleid == -1)
	{
		$master = 'standard';
		$style['title'] = $vbphrase['master_style'];
		$style['type'] = 'standard';
		$printstyleid = 'm1';
	}
	else if ($styleid == -2)
	{
		$master = 'mobile';
		$style['title'] = $vbphrase['mobile_master_style'];
		$style['type'] = 'mobile';
		$printstyleid = 'm2';
	}
	else
	{
		if ($style['type'] == 'standard' AND $mastertype != -1)
		{
			return;
		}
		else if ($style['type'] == 'mobile' AND $mastertype != -2)
		{
			return;
		}
		$master = false;
		$printstyleid = $styleid;
	}

	if ($master == 'standard')
	{
		$THISstyleid = 'n1';
		$style['templatelist'] = serialize($masterset[$master]);
	}
	else if ($master == 'mobile')
	{
		$THISstyleid = 'n2';
		$style['templatelist'] = serialize($masterset[$master]);
	}
	else
	{
		$THISstyleid = $styleid;
	}

	if ($expandset == $styleid OR ($expandset == 'all-1' AND $style['type'] == 'standard') OR ($expandset == 'all-2' AND $style['type'] == 'mobile'))
	{
		$showstyle = 1;
	}
	else
	{
		$showstyle = 0;
	}

	// try to figure out if the style was customized in vb3
	$show_generate_vb4_style = is_customized_vb3_style($styleid);

	$forumhome_url = fetch_seo_url('forumhome|bburl', array(), array('styleid' => $styleid));

	// show the header row
	echo "
	<!-- start header row for style '$style[styleid]' -->
	<table cellpadding=\"2\" cellspacing=\"0\" border=\"0\" width=\"100%\" class=\"stylerow\">
	<tr>
		<td><label for=\"userselect_$styleid\" title=\"$vbphrase[allow_user_selection]\">&nbsp; " .
			construct_depth_mark($style['depth'], '- - ', (($vbulletin->debug AND !$master) ? '- - ' : '')) .
			iif(!$master, "<input type=\"checkbox\" name=\"userselect[$styleid]\" value=\"1\" tabindex=\"1\"" .
			iif($style['userselect'], ' checked="checked"') .
			" id=\"userselect_$styleid\" onclick=\"check_children($styleid, this.checked)\" />") .
		"</label><a href=\"$forumhome_url\" target=\"_blank\" title=\"$vbphrase[view_your_forum_using_this_style]\">$style[title]</a></td>
		<td align=\"" . vB_Template_Runtime::fetchStyleVar('right') . "\" nowrap=\"nowrap\">
			" . iif(!$master, "<input type=\"text\" class=\"bginput\" name=\"displayorder[$styleid]\" value=\"$style[displayorder]\" tabindex=\"1\" size=\"2\" title=\"$vbphrase[display_order]\" />") . "
			&nbsp;
			<select name=\"styleEdit_$printstyleid\" id=\"menu_$styleid\" onchange=\"Sdo(this.options[this.selectedIndex].value, $styleid);\" class=\"bginput\">
				<optgroup label=\"" . $vbphrase['template_options'] . "\">
					<option value=\"template_templates\">" . $vbphrase['edit_templates'] . "</option>
					<option value=\"template_addtemplate\">" . $vbphrase['add_new_template'] . "</option>
					" . iif(!$master, "<option value=\"template_revertall\">" . $vbphrase['revert_all_templates'] . "</option>") . "
				</optgroup>
				<optgroup label=\"" . $vbphrase['edit_fonts_colors_etc'] . "\">
					" . ($style['type'] == 'standard' ? "<option value=\"css_all\">$vbphrase[all_style_options]</option>" : "") . "
					" . ($style['type'] == 'standard' ? "<option value=\"css_templates\">$vbphrase[common_templates]</option>" : "") . "
					<option value=\"stylevar\" selected=\"selected\">$vbphrase[stylevareditor]</option>
					" . iif(!$master, "<option value=\"stylevar_revertall\">" . $vbphrase['revert_all_stylevars'] . "</option>") . "
					" . ($style['type'] == 'standard' ? "<option value=\"css_maincss\">$vbphrase[main_css]</option>" : "") . "
					<option value=\"" . ($style['type'] == 'standard' ? "css_replacements" : "replacements") . "\">$vbphrase[replacement_variables]</option>
					" . ($style['type'] == 'standard' ? "<option value=\"css_posteditor\">$vbphrase[toolbar_menu_options]</option>" : "") . "
				</optgroup>
				<optgroup label=\"" . $vbphrase['edit_style_options'] . "\">
					" . iif(!$master, '<option value="template_editstyle">' . $vbphrase['edit_settings'] . '</option>') . "
					<option value=\"template_addstyle\">" . $vbphrase['add_child_style'] . "</option>
					<option value=\"template_download\">" . $vbphrase['download'] . "</option>
					" . iif($show_generate_vb4_style, '<option value="stylevar_convertvb3tovb4">' . $vbphrase['generate_vb4_style'] . '</option>') . "
					" . iif(!$master, '<option value="template_delete" class="col-c">' . $vbphrase['delete_style'] . '</option>') . "
				</optgroup>
			</select><input type=\"button\" class=\"button\" value=\"$vbphrase[go]\" onclick=\"Sdo(this.form.styleEdit_$printstyleid.options[this.form.styleEdit_$printstyleid.selectedIndex].value, $styleid);\" />
			&nbsp;
			<input type=\"button\" class=\"button\" tabindex=\"1\"
			value=\"" . iif($showstyle, COLLAPSECODE, EXPANDCODE) . "\" title=\"" . iif($showstyle, $vbphrase['collapse_templates'], $vbphrase['expand_templates']) . "\"
			onclick=\"window.location='template.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify&amp;group=$group" .iif($showstyle, '', "&amp;expandset=$styleid") . "';\" />
			&nbsp;
		</td>
	</tr>
	</table>
	<!-- end header row for style '.$style[styleid]' -->
	";

	if ($showstyle)
	{
		if (empty($searchstring))
		{
			$searchconds = '';
		}
		elseif ($titlesonly)
		{
			$searchconds = "AND t1.title LIKE('%" . $vbulletin->db->escape_string_like($searchstring) . "%')";
		}
		else
		{
			$searchconds = "AND ( t1.title LIKE('%" . $vbulletin->db->escape_string_like($searchstring) . "%') OR template_un LIKE('%" . $vbulletin->db->escape_string_like($searchstring) . "%') ) ";
		}

		// query templates
		$templateids = implode(',' , unserialize($style['templatelist']));
		$templates = $vbulletin->db->query_read("
			SELECT
				templateid, IF(t1.title LIKE '%.css', CONCAT('css_', t1.title), title) AS title, styleid, templatetype, dateline, username
			FROM " . TABLE_PREFIX . "template AS t1
			WHERE
				templatetype IN('template', 'replacement') $searchconds
					AND
				templateid IN($templateids)
			#AND title NOT IN('" . implode("', '", $_query_special_templates) . "')
			ORDER BY title
			# expandset: '$expandset'
		");

		// just exit if no templates found
		$numtemplates = $vbulletin->db->num_rows($templates);
		if ($numtemplates == 0)
		{
			return;
		}

		echo "\n<!-- start template list for style '$style[styleid]' -->\n";

		if (FORMTYPE)
		{
			echo "<table cellpadding=\"0\" cellspacing=\"10\" border=\"0\" align=\"center\"><tr valign=\"top\">\n";
			echo "<td>\n<select name=\"tl$THISstyleid\" id=\"templatelist$THISstyleid\" class=\"darkbg\" size=\"" . TEMPLATE_EDITOR_ROWS . "\" style=\"font-weight:bold; width:350px\"\n\t";
			echo "onchange=\"Tprep(this.options[this.selectedIndex], '$THISstyleid', 1);";
			echo "\"\n\t";
			echo "ondblclick=\"Tdo(Tprep(this.options[this.selectedIndex], '$THISstyleid', 0), '');\">\n";
			echo "\t<option class=\"templategroup\" value=\"\">- - " . construct_phrase($vbphrase['x_templates'], $style['title']) . " - -</option>\n";
		}
		else
		{
			echo "<center><div class=\"darkbg\" style=\"padding: 4px; border: 2px inset; margin: 8px; text-align: " . vB_Template_Runtime::fetchStyleVar('left') . ";" . (is_browser('opera') ? " padding-" . vB_Template_Runtime::fetchStyleVar('left') . ":20px;" : '') . "\">\n<ul>\n";
			echo '<li class="templategroup"><b>' . $vbphrase['all_template_groups'] . '</b>' .
				construct_link_code("<b>" . EXPANDCODE . "</b>", "template.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify&amp;expandset=$expandset&amp;group=all", 0, $vbphrase['expand_all_template_groups']).
				construct_link_code("<b>" . COLLAPSECODE . "</b>", "template.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify&amp;expandset=$expandset", 0, $vbphrase['collapse_all_template_groups']).
				"<br />&nbsp;</li>\n";
		}

		while ($template = $vbulletin->db->fetch_array($templates))
		{
			if ($template['templatetype'] == 'replacement')
			{
				$replacements["$template[templateid]"] = $template;
			}
			else
			{
				// don't show the special templates used for building the text editor style / options
				if (in_array($template['title'], $_query_special_templates))
				{
					continue;
				}
				else
				{
					$m = substr(strtolower($template['title']), 0, iif($n = strpos($template['title'], '_'), $n, strlen($template['title'])));
					if ($template['styleid'] != -1 AND $template['styleid'] != -2 AND !isset($masterset[$style['type']]["$template[title]"]) AND !isset($only["$m"]))
					{
						$customtemplates["$template[templateid]"] = $template;
					}
					else
					{
						$maintemplates["$template[templateid]"] = $template;
					}
				}
			}
		}

		// custom templates
		if (!empty($customtemplates))
		{
			if (FORMTYPE)
			{
				echo "<optgroup label=\"\">\n";
				echo "\t<option class=\"templategroup\" value=\"\">" . $vbphrase['custom_templates'] . "</option>\n";
			}
			else
			{
				echo "<li class=\"templategroup\"><b>" . $vbphrase['custom_templates'] . "</b>\n<ul class=\"ldi\">\n";
			}

			foreach($customtemplates AS $template)
			{
				echo $SHOWTEMPLATE($template, $styleid, 1); vbflush();
			}

			if (FORMTYPE)
			{
				echo "</optgroup><!--<optgroup label=\"\"></optgroup>-->";
			}
			else
			{
				echo "</li>\n</ul>\n";
			}
		}

		// main templates
		if (!empty($maintemplates))
		{
			$lastgroup = '';
			$echo_ul = 0;

			foreach($maintemplates AS $template)
			{
				$showtemplate = 1;
				if (!empty($lastgroup) AND strpos(strtolower(" $template[title]"), $lastgroup) == 1)
				{
					if ($group == 'all' OR $group == $lastgroup)
					{
						echo $SHOWTEMPLATE($template, $styleid, $echo_ul);
						vbflush();
					}
				}
				else
				{
					foreach($only AS $thisgroup => $display)
					{
						if ($lastgroup != $thisgroup AND $echo_ul == 1)
						{
							if (FORMTYPE)
							{
								// do nothing
								echo "</optgroup><!--<optgroup label=\"\"></optgroup>-->\n";
							}
							else
							{
								echo "\t</ul>\n</li>\n";
							}
							$echo_ul = 0;
						}
						if (strpos(strtolower(" $template[title]"), $thisgroup) == 1)
						{
							$lastgroup = $thisgroup;
							if ($group == 'all' OR $group == $lastgroup)
							{
								if (FORMTYPE)
								{
									echo "<optgroup label=\"\">\n";
									echo "\t<option class=\"templategroup\" value=\"[]\"" . iif($group == $thisgroup AND empty($vbulletin->GPC['templateid']), ' selected="selected"', '') . ">" . construct_phrase($vbphrase['x_templates'], $display) . " &laquo;</option>\n";
								}
								else
								{
									echo "<li class=\"templategroup\"><b>" . construct_phrase($vbphrase['x_templates'], $display) . "</b>" . construct_link_code("<b>" . COLLAPSECODE . "</b>", "template.php?" . $vbulletin->session->vars['sessionurl'] . "expandset=$expandset\" name=\"$thisgroup", 0, $vbphrase['collapse_template_group']) . "\n";
									echo "\t<ul class=\"ldi\">\n";
								}
								$echo_ul = 1;
							}
							else
							{
								if (FORMTYPE)
								{
									echo "\t<option class=\"templategroup\" value=\"[$thisgroup]\">" . construct_phrase($vbphrase['x_templates'], $display) . " &raquo;</option>\n";
								}
								else
								{
									echo "<li class=\"templategroup\"><b>" . construct_phrase($vbphrase['x_templates'], $display) . "</b>" . construct_link_code('<b>' . EXPANDCODE . '</b>', "template.php?" . $vbulletin->session->vars['sessionurl'] . "group=$thisgroup&amp;expandset=$expandset#$thisgroup", 0, $vbphrase['expand_template_group']) . "</li>\n";
								}
								$showtemplate = 0;
							}
							break;
						}
					} // end foreach($only)

					if ($showtemplate)
					{
						echo $SHOWTEMPLATE($template, $styleid, $echo_ul);
						vbflush();
					}
				} // end if template string same AS last
			} // end foreach ($maintemplates)
		}

		if (FORMTYPE)
		{
			echo "</select>\n";
			echo "</td>\n<td width=\"100%\" align=\"center\" valign=\"top\">";
			echo "
			<table cellpadding=\"4\" cellspacing=\"1\" border=\"0\" class=\"tborder\" width=\"300\">
			<tr align=\"center\">
				<td class=\"tcat\"><b>$vbphrase[controls]</b></td>
			</tr>
			<tr>
				<td class=\"alt2\" align=\"center\" style=\"font: 11px tahoma, verdana, arial, helvetica, sans-serif\">
					<input type=\"button\" class=\"button\" style=\"font-weight: normal\" value=\"$vbphrase[customize]\" id=\"cust$THISstyleid\" onclick=\"Tdo(Tprep(this.form.tl{$THISstyleid}[this.form.tl$THISstyleid.selectedIndex], '$THISstyleid', 0), '');\" />
					<input type=\"button\" class=\"button\" style=\"font-weight: normal\" value=\"" . trim(construct_phrase($vbphrase['expand_x'], '')) . '/' . trim(construct_phrase($vbphrase['collapse_x'], '')) . "\" id=\"expa$THISstyleid\" onclick=\"Tdo(Tprep(this.form.tl{$THISstyleid}[this.form.tl$THISstyleid.selectedIndex], '$THISstyleid', 0), '');\" /><br />
					<input type=\"button\" class=\"button\" style=\"font-weight: normal\" value=\" $vbphrase[edit] \" id=\"edit$THISstyleid\" onclick=\"Tdo(Tprep(this.form.tl{$THISstyleid}[this.form.tl$THISstyleid.selectedIndex], '$THISstyleid', 0), '');\" />
					<input type=\"button\" class=\"button\" style=\"font-weight: normal\" value=\"$vbphrase[view_original]\" id=\"orig$THISstyleid\" onclick=\"Tdo(Tprep(this.form.tl{$THISstyleid}[this.form.tl$THISstyleid.selectedIndex], '$THISstyleid', 0), 'vieworiginal');\" />
					<input type=\"button\" class=\"button\" style=\"font-weight: normal\" value=\"$vbphrase[revert]\" id=\"kill$THISstyleid\" onclick=\"Tdo(Tprep(this.form.tl{$THISstyleid}[this.form.tl$THISstyleid.selectedIndex], '$THISstyleid', 0), 'killtemplate');\" />
					<div class=\"darkbg\" style=\"margin: 4px; padding: 4px; border: 2px inset; text-align: " . vB_Template_Runtime::fetchStyleVar('left') . "\" id=\"helparea$THISstyleid\">
						" . construct_phrase($vbphrase['x_templates'], '<b>' . $style['title'] . '</b>') . "
					</div>
					<input type=\"button\" class=\"button\" value=\"" . EXPANDCODE . "\" title=\"" . $vbphrase['expand_all_template_groups'] . "\" onclick=\"Texpand('all', '$expandset');\" />
					<b>" . $vbphrase['all_template_groups'] . "</b>
					<input type=\"button\" class=\"button\" value=\"" . COLLAPSECODE . "\" title=\"" . $vbphrase['collapse_all_template_groups'] . "\" onclick=\"Texpand('', '$expandset');\" />
				</td>
			</tr>
			</table>
			<br />
			<table cellpadding=\"4\" cellspacing=\"1\" border=\"0\" class=\"tborder\" width=\"300\">
			<tr align=\"center\">
				<td class=\"tcat\"><b>$vbphrase[color_key]</b></td>
			</tr>
			<tr>
				<td class=\"alt2\">
				<div class=\"darkbg\" style=\"margin: 4px; padding: 4px; border: 2px inset; text-align: " . vB_Template_Runtime::fetchStyleVar('left') . "\">
				<span class=\"col-g\">" . $vbphrase['template_is_unchanged_from_the_default_style'] . "</span><br />
				<span class=\"col-i\">" . $vbphrase['template_is_inherited_from_a_parent_style'] . "</span><br />
				<span class=\"col-c\">" . $vbphrase['template_is_customized_in_this_style'] . "</span>
				</div>
				</td>
			</tr>
			</table>
			";

			/*
			// might come back to this at some point...
			if (!empty($replacements))
			{
				$numreplacements = sizeof($replacements);
				echo "<br />\n<b>Replacement Variables:</b><br />\n<select name=\"rep$THISstyleid\" size=\"" . iif($numreplacements > ADMIN_MAXREPLACEMENTS, ADMIN_MAXREPLACEMENTS, $numreplacements) . "\" class=\"bginput\" style=\"width:350px\">\n";
				foreach($replacements AS $replacement)
				{
					echo $SHOWTEMPLATE($replacement, $styleid, 0, 1);
					vbflush();
				}
				echo "</select>\n";
			}
			*/

			echo "\n</td>\n</tr>\n</table>\n
			<script type=\"text/javascript\">
			<!--
			if (document.forms.tform.tl$THISstyleid.selectedIndex > 0)
			{
				Tprep(document.forms.tform.tl$THISstyleid.options[document.forms.tform.tl$THISstyleid.selectedIndex], '$THISstyleid', 1);
			}
			//-->
			</script>";

		}
		else
		{
			echo "</ul>\n</div></center>\n";
		}

		echo "<!-- end template list for style '$style[styleid]' -->\n\n";

	} // end if($showstyle)

} // end function

// #############################################################################
/**
* Constructs a single template item for the style editor form
*
* @param	array	Template info array
* @param	integer	Style ID of style being shown
* @param	boolean	No longer used
* @param	boolean	HTMLise template titles?
*
* @return	string	Template <option>
*/
function construct_template_option($template, $styleid, $doindent = false, $htmlise = true)
{
	global $vbulletin;

	static $isdevsite;

	$template['title'] = preg_replace('#^css_(.*)#i', '\\1', $template['title']);

	if ($vbulletin->GPC['templateid'] == $template['templateid'])
	{
		$selected = ' selected="selected"';
	}
	else
	{
		$selected = '';
	}

	if ($htmlise)
	{
		$template['title'] = htmlspecialchars_uni($template['title']);
	}

	if ($styleid == -1 OR $styleid == -2)
	{
		return "\t<option value=\"$template[templateid]\" i=\"$template[username];$template[dateline]\"$selected>$indent$template[title]</option>\n";
	}
	else
	{
		switch ($template['styleid'])
		{
			// template is inherited from the master set
			case 0:
			case -1:
			case -2:
			{
				return "\t<option class=\"col-g\" value=\"~\" i=\"$template[username];$template[dateline]\"$selected>$indent$template[title]</option>\n";
			}

			// template is customized for this specific style
			case $styleid:
			{
				return "\t<option class=\"col-c\" value=\"$template[templateid]\" i=\"$template[username];$template[dateline]\"$selected>$indent$template[title]</option>\n";
			}

			// template is customized in a parent style - (inherited)
			default:
			{
				return "\t<option class=\"col-i\" value=\"[$template[templateid]]\" tsid=\"$template[styleid]\" i=\"$template[username];$template[dateline]\" tsid=\"$template[styleid]\"$selected>$indent$template[title]</option>\n";
			}
		}
	}
}

// #############################################################################
/**
* Processes a template into PHP code for eval()
*
* @param	string	Unprocessed template
* @param	boolean	Halt on error?
*
* @return	string
*/
function process_template_conditionals($template, $haltonerror = true)
{
	global $vbphrase;

	$if_lookfor = '<if condition=';
	$if_location = -1;
	$if_end_lookfor = '</if>';
	$if_end_location = -1;

	$else_lookfor = '<else />';
	$else_location = -1;

	$condition_value = '';
	$true_value = '';
	$false_value = '';

	$template_cond = $template;

	static $safe_functions;
	if (!is_array($safe_functions))
	{
		$safe_functions = array(
			// logical stuff
			0 => 'and',                   // logical and
			1 => 'or',                    // logical or
			2 => 'xor',                   // logical xor

			// built-in variable checking functions
			'in_array',                   // used for checking
			'is_array',                   // used for checking
			'is_numeric',                 // used for checking
			'isset',                      // used for checking
			'empty',                      // used for checking
			'defined',                    // used for checking
			'array',                      // used for checking
			'gmdate',                     // used by ad manager
			'mktime',                     // used by ad manager
			'gmmktime',                   // used by ad manager

			// vBulletin-defined functions
			'can_moderate',               // obvious one
			'can_moderate_calendar',      // another obvious one
			'exec_switch_bg',             // harmless function that we use sometimes
			'is_browser',                 // function to detect browser and versions
			'is_member_of',               // function to check if $user is member of $usergroupid
			'is_came_from_search_engine', // function to check whether or not user came from search engine for ad manager
			'vbdate',                     // function to check date range for ad manager
		);

		($hook = vBulletinHook::fetch_hook('template_safe_functions')) ? eval($hook) : false;
	}

	// #############################################################################

	while (1)
	{

		$condition_end = 0;
		$strlen = strlen($template_cond);

		$if_location = strpos($template_cond, $if_lookfor, $if_end_location + 1); // look for opening <if>
		if ($if_location === false)
		{ // conditional started not found
			break;
		}

		$condition_start = $if_location + strlen($if_lookfor) + 2; // the beginning of the conditional

		$delimiter = $template_cond[$condition_start - 1];
		if ($delimiter != '"' AND $delimiter != '\'')
		{ // ensure the conditional is surrounded by a valid character
			$if_end_location = $if_location + 1;
			continue;
		}

		$if_end_location = strpos($template_cond, $if_end_lookfor, $condition_start + 3); // location of conditional terminator
		if ($if_end_location === false)
		{ // move this code above the rest, if no end condition is found then the code below would get stuck
			return str_replace("\\'", '\'', $template_cond); // no </if> found -- return the original template
		}

		for ($i = $condition_start; $i < $strlen; $i++)
		{ // find the end of the conditional
			if ($template_cond["$i"] == $delimiter AND $template_cond[$i - 2] != '\\' AND $template_cond[$i + 1] == '>')
			{ // this char is delimiter and not preceded by backslash
				$condition_end = $i - 1;
				break;
			}
		}
		if (!$condition_end)
		{ // couldn't find an end to the condition, so don't even parse the template anymore
			return str_replace("\\'", '\'', $template_cond);
		}

		$condition_value = substr($template_cond, $condition_start, $condition_end-$condition_start);
		if (empty($condition_value))
		{
			// something went wrong
			$if_end_location = $if_location + 1;
			continue;
		}
		else if (strpos($condition_value, '`') !== false)
		{
			if (VB_AREA == 'Upgrade' OR VB_AREA == 'Install')
			{
				return false;
			}
			print_stop_message('expression_contains_backticks_x_please_rewrite_without', htmlspecialchars('<if condition="' . stripslashes($condition_value) . '">'));
		}
		else
		{
			if (preg_match_all('#([a-z0-9_\x7f-\xff\\\\{}$>-\\]]+)(\s|/\*.*\*/|(\#|//)[^\r\n]*(\r|\n))*\(#si', $condition_value, $matches))
			{
				$functions = array();
				foreach($matches[1] AS $key => $match)
				{
					if (!in_array(strtolower($match), $safe_functions))
					{
						$funcpos = strpos($condition_value, $matches[0]["$key"]);
						$functions[] = array(
							'func' => stripslashes($match),
							'usage' => stripslashes(substr($condition_value, $funcpos, (strpos($condition_value, ')', $funcpos) - $funcpos + 1))),
						);
					}
				}
				if (!empty($functions))
				{
					if (VB_AREA == 'Upgrade' OR VB_AREA == 'Install')
					{
						return false;
					}
					unset($safe_functions[0], $safe_functions[1], $safe_functions[2]);

					$errormsg = "
					$vbphrase[template_condition_contains_functions]:<br /><br />
					<code>" . htmlspecialchars('<if condition="' . stripslashes($condition_value) . '">') . '</code><br /><br />
					<table cellpadding="4" cellspacing="1" width="100%">
					<tr>
						<td class="thead">' . $vbphrase['function_name'] . '</td>
						<td class="thead">' . $vbphrase['usage_in_expression'] . '</td>
					</tr>';

					foreach($functions AS $error)
					{
						$errormsg .= "<tr><td class=\"alt2\"><code>" . htmlspecialchars($error['func']) . "</code></td><td class=\"alt2\"><code>" . htmlspecialchars($error['usage']) . "</code></td></tr>\n";
					}

					$errormsg .= "
					</table>
					<br />$vbphrase[with_a_few_exceptions_function_calls_are_not_permitted]<br />
					<code>". implode('() ', $safe_functions) . '()</code>';

					echo "<p>&nbsp;</p><p>&nbsp;</p>";
					print_form_header('', '', 0, 1, '', '65%');
					print_table_header($vbphrase['vbulletin_message']);
					print_description_row("<blockquote><br />$errormsg<br /><br /></blockquote>");
					print_table_footer(2, construct_button_code($vbphrase['go_back'], 'javascript:history.back(1)'));
					print_cp_footer();
				}
			}
		}

		if ($template_cond[$condition_end + 2] != '>')
		{ // the > doesn't come right after the condition must be malformed
			$if_end_location = $if_location + 1;
			continue;
		}

		// look for recursive case in the if block -- need to do this so the correct </if> is looked at
		$recursive_if_loc = $if_location;
		while (1)
		{
			$recursive_if_loc = strpos($template_cond, $if_lookfor, $recursive_if_loc + 1); // find an if case
			if ($recursive_if_loc === false OR $recursive_if_loc >= $if_end_location)
			{ //not found or out of bounds
				break;
			}

			// the bump first level's recursion back one </if> at a time
			$recursive_if_end_loc = $if_end_location;
			$if_end_location = strpos($template_cond, $if_end_lookfor, $recursive_if_end_loc + 1);
			if ($if_end_location === false)
			{
				return str_replace("\\'", "'", $template_cond); // no </if> found -- return the original template
			}
		}

		$else_location = strpos($template_cond, $else_lookfor, $condition_end + 3); // location of false portion

		// this is needed to correctly identify the <else /> tag associated with the outermost level
		while (1)
		{
			if ($else_location === false OR $else_location >= $if_end_location)
			{ // else isn't found/in a valid area
				$else_location = -1;
				break;
			}

			$temp = substr($template_cond, $condition_end + 3, $else_location - $condition_end + 3);
			$opened_if = substr_count($temp, $if_lookfor); // <if> tags opened between the outermost <if> and the <else />
			$closed_if = substr_count($temp, $if_end_lookfor); // <if> tags closed under same conditions
			if ($opened_if == $closed_if)
			{ // if this is true, we're back to the outermost level
				// and this is the correct else
				break;
			}
			else
			{
				// keep looking for correct else case
				$else_location = strpos($template_cond, $else_lookfor, $else_location + 1);
			}
		}

		if ($else_location == -1)
		{ // no else clause
			$read_length = $if_end_location - strlen($if_end_lookfor) + 1 - $condition_end + 1; // number of chars to read
			$true_value = substr($template_cond, $condition_end + 3, $read_length); // the true portion
			$false_value = '';
		}
		else
		{
			$read_length = $else_location - $condition_end - 3; // number of chars to read
			$true_value = substr($template_cond, $condition_end + 3, $read_length); // the true portion

			$read_length = $if_end_location - strlen($if_end_lookfor) - $else_location - 3; // number of chars to read
			$false_value = substr($template_cond, $else_location + strlen($else_lookfor), $read_length); // the false portion
		}

		if (strpos($true_value, $if_lookfor) !== false)
		{
			$true_value = process_template_conditionals($true_value);
			if ((VB_AREA == 'Upgrade' OR VB_AREA == 'Install') AND $true_value === false)
			{
				return false;
			}
		}
		if (strpos($false_value, $if_lookfor) !== false)
		{
			$false_value = process_template_conditionals($false_value);
			if ((VB_AREA == 'Upgrade' OR VB_AREA == 'Install') AND $false_value === false)
			{
				return false;
			}
		}

		// clean up the extra slashes
		$str_find = array('\\"', '\\\\');
		$str_replace = array('"', '\\');
		if ($delimiter == "'")
		{
			$str_find[] = "\\'";
			$str_replace[] = "'";
		}

		$str_find[] = '\\$delimiter';
		$str_replace[] =  $delimiter;

		$condition_value = str_replace($str_find, $str_replace, $condition_value);

		if (!function_exists('replace_template_variables'))
		{
			require_once(DIR . '/includes/functions_misc.php');
		}

		$condition_value = replace_template_variables($condition_value, true);

		$conditional = "\".(($condition_value) ? (\"$true_value\") : (\"$false_value\")).\"";
		$template_cond = substr_replace($template_cond, $conditional, $if_location, $if_end_location + strlen($if_end_lookfor) - $if_location);


/*echo "
<pre>-----
if_location:      ".htmlspecialchars_uni($if_location)."
delimiter:        ".htmlspecialchars_uni($delimiter)."
condition_start:  ".htmlspecialchars_uni($condition_start)."
condition_end:    ".htmlspecialchars_uni($condition_end)."
condition_value:  ".htmlspecialchars_uni($condition_value)."
else_location:    ".htmlspecialchars_uni($else_location)."
if_end_location:  ".htmlspecialchars_uni($if_end_location)."
true_value:       ".htmlspecialchars_uni($true_value)."
false_value:      ".htmlspecialchars_uni($false_value)."
conditional:      ".htmlspecialchars_uni($conditional)."
--------------
" . htmlspecialchars_uni($template_cond) . "
-----</pre>
";*/

		$if_end_location = $if_location + strlen($conditional) - 1; // adjust searching position for the replacement above
	}

	return str_replace("\\'", "'", $template_cond);
}

// #############################################################################
/*
* Processes {link thread[,] $threadinfo[[,] $pageinfo]} into fetch_seo_url('thread', $threadinfo[, $pageinfo]);
* @param	string	Text to be processed
*
* @return	string
*/
function process_seo_urls($template)
{
	$search = array(
		'#{link \s*([a-z_\|]+)(?:,|\s)\s*(\$[a-z_\[\]]+)\s*(?:(?:,|\s)\s*(?:(\$[a-z_\[\]]+|null)(?:\s*(?:,|\s)\s*\'([a-z_]+)\'\s*(?:,|\s)\s*\'([a-z_]+)\')?))?\s*}#si',
	);

	$text = preg_replace_callback($search, 'process_seo_urls_callback', $template);

	return $text;
}

// #############################################################################
/*
* Callback for process_seo_urls() to handle variable variables into fetch_seo_url
* @param	array	Matches from preg_replace
*
* @return	string
*/
function process_seo_urls_callback($matches)
{
	$search = array(
		'#\[#',
		'#\]#',
		'#\$bbuserinfo#',
	);
	$replace = array(
		'[\'',
		'\']',
		'$GLOBALS[\'vbulletin\']->userinfo',
	);
	$matches[2] = preg_replace($search, $replace, $matches[2]);

	switch (count($matches))
	{
		case 3:
			return '" . fetch_seo_url(\'' . $matches[1] . '\', ' . $matches[2] . ') . "';
		case 4:
			$matches[3] = preg_replace($search, $replace, $matches[3]);
			return '" . fetch_seo_url(\'' . $matches[1] . '\', ' . $matches[2] . ', ' . $matches[3] . ') . "';
		case 6:
			$matches[3] = preg_replace($search, $replace, $matches[3]);
			return '" . fetch_seo_url(\'' . $matches[1] . '\', ' . $matches[2] . ', ' . $matches[3] . ', \'' . $matches[4] . '\', \'' . $matches[5] . '\') . "';
		default:
			return $matches[0];
	}
}

// #############################################################################
/**
* Processes <phrase> tags into construct_phrase() PHP code for eval
*
* @param	string	Name of tag
* @param	string	Text to be processed
* @param	string	Name of processor function
* @param	string	Extra arguments for processor function
*
* @return	string
*/
function process_template_phrases($tagname, $text, $functionhandle, $extraargs = '')
{
	$tagname = strtolower($tagname);
	$open_tag = "<$tagname";
	$open_tag_len = strlen($open_tag);
	$close_tag = "</$tagname>";
	$close_tag_len = strlen($close_tag);

	$beginsearchpos = 0;
	do {
		$textlower = strtolower($text);
		$tagbegin = @strpos($textlower, $open_tag, $beginsearchpos);
		if ($tagbegin === false)
		{
			break;
		}

		$strlen = strlen($text);

		// we've found the beginning of the tag, now extract the options
		$inquote = '';
		$found = false;
		$tagnameend = false;
		for ($optionend = $tagbegin; $optionend <= $strlen; $optionend++)
		{
			$char = $text{$optionend};
			if (($char == '"' OR $char == "'") AND $inquote == '')
			{
				$inquote = $char; // wasn't in a quote, but now we are
			}
			else if (($char == '"' OR $char == "'") AND $inquote == $char)
			{
				$inquote = ''; // left the type of quote we were in
			}
			else if ($char == '>' AND !$inquote)
			{
				$found = true;
				break; // this is what we want
			}
			else if (($char == '=' OR $char == ' ') AND !$tagnameend)
			{
				$tagnameend = $optionend;
			}
		}
		if (!$found)
		{
			break;
		}
		if (!$tagnameend)
		{
			$tagnameend = $optionend;
		}
		$offset = $optionend - ($tagbegin + $open_tag_len);
		$tagoptions = substr($text, $tagbegin + $open_tag_len, $offset);
		$acttagname = substr($textlower, $tagbegin + 1, $tagnameend - $tagbegin - 1);
		if ($acttagname != $tagname)
		{
			$beginsearchpos = $optionend;
			continue;
		}

		// now find the "end"
		$tagend = strpos($textlower, $close_tag, $optionend);
		if ($tagend === false)
		{
			break;
		}

		// if there are nested tags, this </$tagname> won't match our open tag, so we need to bump it back
		$nestedopenpos = strpos($textlower, $open_tag, $optionend);
		while ($nestedopenpos !== false AND $tagend !== false)
		{
			if ($nestedopenpos > $tagend)
			{ // the tag it found isn't actually nested -- it's past the </$tagname>
				break;
			}
			$tagend = strpos($textlower, $close_tag, $tagend + $close_tag_len);
			$nestedopenpos = strpos($textlower, $open_tag, $nestedopenpos + $open_tag_len);
		}
		if ($tagend === false)
		{
			$beginsearchpos = $optionend;
			continue;
		}

		$localbegin = $optionend + 1;
		$localtext = $functionhandle($tagoptions, substr($text, $localbegin, $tagend - $localbegin), $tagname, $extraargs);

		$text = substr_replace($text, $localtext, $tagbegin, $tagend + $close_tag_len - $tagbegin);

		// this adjusts for $localtext having more/less characters than the amount of text it's replacing
		$beginsearchpos = $tagbegin + strlen($localtext);
	} while ($tagbegin !== false);

	return $text;
}

// #############################################################################
/**
* Processes a <phrase> tag
*
* @param	string	Options
* @param	string	Text of phrase
*
* @return	string
*/
function parse_phrase_tag($options, $phrasetext)
{
	$options = stripslashes($options);

	$i = 1;
	$param = array();
	do
	{
		$attribute = parse_tag_attribute("$i=", $options);
		if ($attribute !== false)
		{
			$param[] = $attribute;
		}
		$i++;
	} while ($attribute !== false);

	if (sizeof($param) > 0)
	{
		$return = '" . construct_phrase("' . $phrasetext . '"';
		foreach ($param AS $argument)
		{
			$argument = str_replace(array('\\', '"'), array('\\\\', '\"'), $argument);
			$return .= ', "' . $argument . '"';
		}
		$return .= ') . "';
	}
	else
	{
		$return = $phrasetext;
	}

	return $return;
}

// #############################################################################
/**
* Parses an attribute within a <phrase>
*
* @param	string	Option
* @param	string	Text
*
* @return	string
*/
function parse_tag_attribute($option, $text)
{
	if (($position = strpos($text, $option)) !== false)
	{
		$delimiter = $position + strlen($option);
		if ($text{$delimiter} == '"')
		{ // read to another "
			$delimchar = '"';
		}
		else if ($text{$delimiter} == '\'')
		{
			$delimchar = '\'';
		}
		else
		{ // read to a space
			$delimchar = ' ';
		}
		$delimloc = strpos($text, $delimchar, $delimiter + 1);
		if ($delimloc === false)
		{
			$delimloc = strlen($text);
		}
		else if ($delimchar == '"' OR $delimchar == '\'')
		{
			// don't include the delimiters
			$delimiter++;
		}
		return trim(substr($text, $delimiter, $delimloc - $delimiter));
	}
	else
	{
		return false;
	}
}

// #############################################################################
/**
* Processes a raw template for conditionals, phrases etc into PHP code for eval()
*
* @param	string	Template
*
* @return	mixed	string on success, false on failure when in upgrade/install
*/
function compile_template($template, &$errors = array())
{
	$orig_template = $template;

	$template = preg_replace('#[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]#', '', $template);
	$new_syntax = (strpos($template, '<vb:') !== false OR strpos($template, '{vb:') !== false);
	$old_syntax = (strpos($template, '<if') !== false OR strpos($template, '<phrase') !== false);
	$maybe_old_syntax = preg_match('/(^|[^{])\$[a-z0-9_]+\[?/si', $template);

	if (!$new_syntax AND ($old_syntax OR $maybe_old_syntax))
	{
		$template = addslashes($template);
		$template = process_template_conditionals($template);
		if (VB_AREA == 'Upgrade' OR VB_AREA == 'Install')
		{
			if ($template === false)
			{
				return false;
			}
		}
		$template = process_template_phrases('phrase', $template, 'parse_phrase_tag');
		$template = process_seo_urls($template);

		if (!function_exists('replace_template_variables') OR !function_exists('validate_string_for_interpolation'))
		{
			require_once(DIR . '/includes/functions_misc.php');
		}

		//only check the old style syntax, the new style doesn't use string interpolation and isn't affected
		//by this exploit.  The new syntax doesn't 100% pass this check.
		if (!validate_string_for_interpolation($template))
		{
			if (VB_AREA == 'Upgrade' OR VB_AREA == 'Install')
			{
				return false;
			}
			global $vbphrase;
			echo "<p>&nbsp;</p><p>&nbsp;</p>";
			print_form_header('', '', 0, 1, '', '65%');
			print_table_header($vbphrase['vbulletin_message']);
			print_description_row($vbphrase['template_text_not_safe']);
			print_table_footer(2, construct_button_code($vbphrase['go_back'], 'javascript:history.back(1)'));
			print_cp_footer();
			exit;
		}

		$template = replace_template_variables($template, false);
		$template = str_replace('\\\\$', '\\$', $template);

		if (function_exists('token_get_all'))
		{
			$tokens = @token_get_all('<?php $var = "' . $template . '"; ?>');

			foreach ($tokens AS $token)
			{
				if (is_array($token))
				{
					switch ($token[0])
					{
						case T_INCLUDE:
						case T_INCLUDE_ONCE:
						case T_REQUIRE:
						case T_REQUIRE_ONCE:
						{
							if (VB_AREA == 'Upgrade' OR VB_AREA == 'Install')
							{
								return false;
							}
							global $vbphrase;
							echo "<p>&nbsp;</p><p>&nbsp;</p>";
							print_form_header('', '', 0, 1, '', '65%');
							print_table_header($vbphrase['vbulletin_message']);
							print_description_row($vbphrase['file_inclusion_not_permitted']);
							print_table_footer(2, construct_button_code($vbphrase['go_back'], 'javascript:history.back(1)'));
							print_cp_footer();
							exit;
						}
					}
				}
			}
		}
	}
	else
	{
		require_once(DIR . '/includes/class_template_parser.php');
		$parser = new vB_TemplateParser($orig_template);

		try
		{
			$parser->validate($errors);
		}
		catch (vB_Exception_TemplateFatalError $e)
		{
			if (VB_AREA == 'Upgrade' OR VB_AREA == 'Install')
			{
				return false;
			}
			global $vbphrase;
			echo "<p>&nbsp;</p><p>&nbsp;</p>";
			print_form_header('', '', 0, 1, '', '65%');
			print_table_header($vbphrase['vbulletin_message']);
			print_description_row($vbphrase[$e->getMessage()]);
			print_table_footer(2, construct_button_code($vbphrase['go_back'], 'javascript:history.back(1)'));
			print_cp_footer();
			exit;
		}

		$template = $parser->compile();

		// TODO: Reimplement these - if done, $session[], $bbuserinfo[], $vboptions will parse in the template without using {vb:raw, which isn't what we
		// necessarily want to happen
		/*
		if (!function_exists('replace_template_variables'))
		{
			require_once(DIR . '/includes/functions_misc.php');
		}
		$template = replace_template_variables($template, false);
		*/
	}

	if (function_exists('verify_demo_template'))
	{
		verify_demo_template($template);
	}

	($hook = vBulletinHook::fetch_hook('template_compile')) ? eval($hook) : false;

	return $template;
}

// #############################################################################
/**
* Builds the $stylecache array used in style editing
*
* This is a recursive function - call it as cache_styles() with no arguments
*
* @param	boolean	Not used
* @param	integer	Style ID to start with
* @param	integer	Current depth
*/
function cache_styles($getids = false, $styleid = -1, $depth = 0)
{
	global $vbulletin, $stylecache, $count;
	static $i, $cache;

	// check to see if we have already got the results from the database
	if (empty($cache))
	{
		$styles = $vbulletin->db->query_read("SELECT * FROM " . TABLE_PREFIX . "style ORDER BY displayorder");
		define('STYLECOUNT', $vbulletin->db->num_rows($styles));
		while ($style = $vbulletin->db->fetch_array($styles))
		{
			if (!$style['parentid'])
			{
				$masterstyleid = $style['parentid'] = ($style['type'] == 'standard') ? -1 : -2;
				$parentlist = $style['parentlist'] = "{$style['styleid']}, {$masterstyleid}";
				$vbulletin->db->query_write("
					UPDATE " . TABLE_PREFIX . "style
					SET parentid = {$masterstyleid},
					parentlist = '{$parentlist}'
					WHERE styleid = " . intval($style['styleid'])  . "
				");
			}
			else if (trim($style['parentlist']) == '')
			{
				$parentlist = fetch_template_parentlist($style['styleid']);
				$vbulletin->db->query_write("
					UPDATE " . TABLE_PREFIX . "style
					SET parentlist = '" . $vbulletin->db->escape_string($parentlist) . "'
					WHERE styleid = " . intval($style['styleid'])  . "
				");
				$style['parentlist'] = $parentlist;
			}

			if (trim($style['templatelist']) == '')
			{
				$style['templatelist'] = build_template_id_cache($style['styleid'], true, $style['parentlist']);

				$vbulletin->db->query_write("
					UPDATE " . TABLE_PREFIX . "style
					SET templatelist = '" . $vbulletin->db->escape_string($style['templatelist']) . "'
					WHERE styleid = " . intval($style['styleid'])
				);
			}

			$cache["$style[parentid]"]["$style[displayorder]"]["$style[styleid]"] = $style;
		}
	}

	// database has already been queried
	if (is_array($cache["$styleid"]))
	{
		foreach ($cache["$styleid"] AS $holder)
		{
			foreach ($holder AS $style)
			{
				$stylecache["$style[styleid]"] = $style;
				$stylecache["$style[styleid]"]['depth'] = $depth;
				cache_styles($getids, $style['styleid'], $depth + 1);

			} // end foreach ($holder AS $style)
		} // end foreach ($tcache["$styleid"] AS $holder)
	} // end if (found $tcache["$styleid"])

	if ($styleid == -1 AND is_array($cache[-2]))
	{
		foreach ($cache[-2] AS $holder)
		{
			foreach ($holder AS $style)
			{
				$stylecache["$style[styleid]"] = $style;
				$stylecache["$style[styleid]"]['depth'] = $depth;
				cache_styles($getids, $style['styleid'], $depth + 1);

			} // end foreach ($holder AS $style)
		} // end foreach ($tcache["$styleid"] AS $holder)
	}
}

// #############################################################################
/**
* Builds the stylecache and saves it into the datastore
*
* @return	array	$stylecache
*/
function build_style_datastore()
{
	global $stylecache, $vbulletin;

	if (!is_array($stylecache))
	{
		cache_styles();
		// this should not ever be needed unless the user has edited the database
		if (STYLECOUNT != sizeof($stylecache))
		{
			trigger_error('Invalid row in the style table', E_USER_ERROR);
		}
	}

	$localstylecache = array();

	foreach ($stylecache AS $styleid => $style)
	{
		$localstyle = array();
		$localstyle['styleid'] = $style['styleid'];
		$localstyle['title'] = $style['title'];
		$localstyle['parentid'] = $style['parentid'];
		$localstyle['displayorder'] = $style['displayorder'];
		$localstyle['userselect'] = $style['userselect'];
		$localstyle['type'] = $style['type'];

		($hook = vBulletinHook::fetch_hook('admin_style_datastore')) ? eval($hook) : false;

		$datastorecache["$localstyle[parentid]"]["$localstyle[displayorder]"][] = $localstyle;
		$datastorecache[$style['type']][$style['styleid']] = $style['userselect'] ? 'selectable' : 'unselectable';
	}

	build_datastore('stylecache', serialize($datastorecache), 1);

	return $datastorecache;
}

// #############################################################################
/**
* Prints a row containing a <select> showing the available styles
*
* @param	string	Name for <select>
* @param	integer	Selected style ID
* @param	mixed	Name of top item in <select>, array [-1]/[-2] to set individual top names
* @param	string	Title of row
* @param	boolean	Display top item?
*/
function print_style_chooser_row($name = 'parentid', $selectedid = -1, $topname = NULL, $title = NULL, $displaytop = true, $type = 'both')
{
	global $stylecache, $vbphrase;

	if ($type == 'mobile' AND $selectedid == -1)
	{
		$selectedid = -2;
	}

	if ($topname === NULL)
	{
	   $topname = $vbphrase['no_parent_style'];
	}
	if ($title === NULL)
	{
	   $title = $vbphrase['parent_style'];
	}

	cache_styles();

	$_styles = array();
	$styles = array();

	if ($displaytop)
	{
		if (is_array($topname) AND $topname[0])
		{
			$styles[] = $topname[0];
		}
		if ($type == 'both' OR $type == 'standard')
		{
			if (is_array($topname))
			{
				if ($topname[-1])
				{
					$_styles['standard'][-1] = $topname[-1];
				}
			}
			else
			{
				$_styles['standard'][-1] = $topname;
			}
		}
		if ($type == 'both' OR $type == 'mobile')
		{
			if (is_array($topname))
			{
				if ($topname[-2])
				{
					$_styles['mobile'][-2] = $topname[-2];
				}
			}
			else
			{
				if ($topname == $vbphrase['master_style'])
				{
					$topname = $vbphrase['mobile_master_style'];
				}
				$_styles['mobile'][-2] = $topname;
			}
		}
	}

	foreach($stylecache AS $style)
	{
		$_styles[$style['type']]["$style[styleid]"] = construct_depth_mark($style['depth'], '--', ($displaytop ? '--' : '')) . " $style[title]";
	}

	if ($type == 'both' OR $type == 'standard')
	{
		$styles[$vbphrase['standard_styles']] = $_styles['standard'];
	}
	if ($type == 'both' OR $type == 'mobile')
	{
		$styles[$vbphrase['mobile_styles']] = $_styles['mobile'];
	}


	//	$styles = $styles[$type];

	print_select_row($title, $name, $styles, $selectedid);

}

// #############################################################################
/**
* If a template item is customized, returns HTML to allow revertion
*
* @param	integer	Style ID of template item
* @param	string	Template type (replacement / stylevar etc.)
* @param	string	Name of template record
*
* @return	array	array('info' => x, 'revertcode' => 'y')
*/
function construct_revert_code($itemstyleid, $templatetype, $varname)
{
	global $vbphrase, $vbulletin;

	if ($templatetype == 'replacement')
	{
		$revertword = 'delete';
	}
	else
	{
		$revertword = 'revert';
	}

	switch ($itemstyleid)
	{
		case -2:
		case -1:
			return array('info' => '', 'revertcode' => '&nbsp;');
		case $vbulletin->GPC['dostyleid']:
			return array('info' => "($vbphrase[customized_in_this_style])", 'revertcode' => "<label for=\"del_{$templatetype}_{$varname}\">" . $vbphrase["$revertword"] . "<input type=\"checkbox\" name=\"delete[$templatetype][$varname]\" id=\"del_{$templatetype}_{$varname}\" value=\"1\" tabindex=\"1\" title=\"" . $vbphrase["$revertword"] . "\" /></label>");
		default:
			return array('info' => '(' . construct_phrase($vbphrase['customized_in_a_parent_style_x'], $itemstyleid) . ')', 'revertcode' => '&nbsp;');
	}
}

// #############################################################################
/**
* Makes an entry for a common template on the style editor page
*
* @param	string	Template title
* @param	string	Template variable name
*
* @return	string
*/
function construct_edit_menu_code($title, $varname)
{
	global $template_cache, $vbulletin;

	$template = $template_cache['template']["$varname"];

	$color = fetch_inherited_color($template['styleid'], $vbulletin->GPC['dostyleid']);
	$revertcode = construct_revert_code($template['styleid'], 'template', $varname);

	$out = "<fieldset title=\"$title\"><legend>$title</legend><div class=\"smallfont\" style=\"padding: 2px; text-align: center\"><textarea class=\"$color\" name=\"commontemplate[$varname]\" tabindex=\"1\" cols=\"20\" rows=\"10\" style=\"width: 90%\" wrap=\"off\">" . htmlspecialchars_uni($template['template_un']) . "</textarea>";
	if ($revertcode['info'])
	{
		$out .= "<div>$revertcode[info]<br />$revertcode[revertcode]</div>";
	}
	$out .= '</div></fieldset>';
	return $out;
}

// #############################################################################
/**
* Prints a row containing a textarea for editing one of the 'common templates'
*
* @param	string	Template variable name
*/
function print_common_template_row($varname)
{
	global $template_cache, $vbphrase, $vbulletin;

	$template = $template_cache['template']["$varname"];
	$description = $vbphrase["{$varname}_desc"];

	$color = fetch_inherited_color($template['styleid'], $vbulletin->GPC['dostyleid']);
	$revertcode = construct_revert_code($template['styleid'], 'template', $varname);

	print_textarea_row(
		"<b>$varname</b> <dfn>$description</dfn><span class=\"smallfont\"><br /><br />$revertcode[info]<br /><br />$revertcode[revertcode]</span>",
		"commontemplate[$varname]",
		$template['template_un'],
		8, 70, 1, 0, 'ltr',
		"$color\" style=\"font: 9pt courier new"
	);
}

// #############################################################################
/**
* Prints a row containing a textarea for editing a replacement variable
*
* @param	string	Find text
* @param	string	Replace text
* @param	integer	Number of rows for textarea
* @param	integer	Number of columns for textarea
*/
function print_replacement_row($find, $replace, $rows = 2, $cols = 50)
{
	global $replacement_info, $vbulletin;
	static $rcount;

	$rcount++;

	$color = fetch_inherited_color($replacement_info["$find"], $vbulletin->GPC['dostyleid']);
	$revertcode = construct_revert_code($replacement_info["$find"], 'replacement', $rcount);

	construct_hidden_code("replacement[$rcount][find]", $find);
	print_cells_row(array(
		'<pre>' . htmlspecialchars_uni($find) . '</pre>',
		"\n\t<span class=\"smallfont\"><textarea name=\"replacement[$rcount][replace]\" class=\"$color\" rows=\"$rows\" cols=\"$cols\" tabindex=\"1\">" . htmlspecialchars_uni($replace) . "</textarea><br />$revertcode[info]</span>\n\t",
		"<span class=\"smallfont\">$revertcode[revertcode]</span>"
	));

}

// #############################################################################
/**
* Prints a row containing an input for editing a stylevar
*
* @param	string	Stylevar title
* @param	string	Stylevar varname
* @param	integer	Size of text box
*/
function print_stylevar_row($title, $varname, $size = 30, $validation_regex = '', $failsafe_value = '')
{
	global $stylevars, $stylevar_info, $vbulletin;

	$color = fetch_inherited_color($stylevar_info["$varname"], $vbulletin->GPC['dostyleid']);
	$revertcode = construct_revert_code($stylevar_info["$varname"], 'stylevar', $varname);

	if ($help = construct_table_help_button("stylevar[$varname]"))
	{
		$helplink = "&nbsp;$help";
	}

	if ($validation_regex != '')
	{
		construct_hidden_code("stylevar[_validation][$varname]", htmlspecialchars_uni($validation_regex));
		construct_hidden_code("stylevar[_failsafe][$varname]", htmlspecialchars_uni($failsafe_value));
	}

	print_cells_row(array(
		"<span title=\"\$stylevar[$varname]\">$title</span>",
		"<span class=\"smallfont\"><input type=\"text\" class=\"$color\" title=\"\$stylevar[$varname]\" name=\"stylevar[$varname]\" tabindex=\"1\" value=\"" . htmlspecialchars_uni($stylevars["$varname"]) . "\" size=\"$size\" dir=\"ltr\" /><br />$revertcode[info]</span>",
		"<span class=\"smallfont\">$revertcode[revertcode]</span>$helplink"
	));
}

// #############################################################################
/**
* Returns a <fieldset> containing inputs for editing a forumjump entry
*
* @param	string	Title of item
* @param	string	CSS class name
* @param	integer	Size of text box
*
* @return	string
*/
function construct_forumjump_css_row($title, $classname, $size = 20)
{
	global $css, $css_info, $vbphrase, $color, $vbulletin;

	$color = fetch_inherited_color($css_info["$classname"], $vbulletin->GPC['dostyleid']);
	$revertcode = construct_revert_code($css_info["$classname"], 'css', $classname);

	$output = "
		<fieldset title=\"$title\">
			<legend>" . iif($revertcode['revertcode'] != '&nbsp;', " <span class=\"normal\" style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">$revertcode[revertcode]</span>") . "$title $revertcode[info]</legend>
			<table cellpadding=\"0\" cellspacing=\"2\" border=\"0\" width=\"100%\">
			<colgroup span=\"2\">
				<col width=\"50%\"></col>
				<col width=\"50%\" align=\"right\"></col>
			</colgroup>
			" . construct_css_input_row($vbphrase['background'], "['$classname']['background']", $color, true, 20) . "
			" . construct_css_input_row($vbphrase['font_color'], "['$classname']['color']", $color, true, 20) . "
			</table>
		</fieldset>
	";

	return $output;
}

// #############################################################################
/**
* Returns a row with an input box for use in the CSS editor
*
* @param	string	Title of item
* @param	array	Item info array
* @param	string	CSS class to display with
* @param	boolean	True if the value is a colour (will show colour picker widget)
* @param	integer	Size of input box
*
* @return	string
*/
function construct_css_input_row($title, $item, $class = 'bginput', $iscolor = false, $size = 30)
{
	global $css, $readonly, $color, $numcolors;

	eval('$value = $css' . $item . ';');
	$name = "css" . str_replace("['", "[", str_replace("']", "]", $item));

	if ($iscolor)
	{
		return construct_color_row($title, $name, $value, $class, $size - 8);
	}

	$value = htmlspecialchars_uni($value);
	$readonly = iif($readonly, ' readonly="readonly"', '');

	return "
		<tr>
			<td>$title</td>
			<td><input type=\"text\" class=\"$class\" name=\"$name\" value=\"$value\" title=\"\$$name\" tabindex=\"1\" size=\"$size\" dir=\"ltr\" /></td>
		</tr>
	";
}

// #############################################################################
/**
* Returns a cell containing inputs for editing the LINK section of a CSS item
*
* @param	string	Item title
* @param	array	Item info array
* @param	string	Link type (N, V etc)
* @param	string	CSS class to display with
*
* @return	string
*/
function construct_link_css_input_row($title, $item, $subitem, $color = 'bginput')
{
	global $vbphrase;

	$title = construct_phrase($vbphrase['x_links_css'], $title);

	return '
		<td>
		<fieldset title="' . $title . '">
		<legend>' . $title . '</legend>
		<table cellpadding="0" cellspacing="2" border="0" width="100%">
		<col width="100%"></col>
		' . construct_css_input_row($vbphrase['background'], "['$item']['LINK_$subitem']['background']", $color, true, 16) . '
		' . construct_css_input_row($vbphrase['font_color'], "['$item']['LINK_$subitem']['color']", $color, true, 16) . '
		' . construct_css_input_row($vbphrase['text_decoration'], "['$item']['LINK_$subitem']['text-decoration']", $color, false, 16) . '
		</table>
		</fieldset>
		</td>
	';
}

// #############################################################################
/**
* Returns styles for post editor interface from template
*
* @param	string	Template contents
*
* @return	array
*/
function fetch_posteditor_styles($template)
{
	$item = array();

	preg_match_all('#([a-z0-9-]+):\s*([^\s].*);#siU', $template, $regs);

	foreach ($regs[1] AS $key => $cssname)
	{
		$item[strtolower($cssname)] = trim($regs[2]["$key"]);
	}

	return $item;
}

// #############################################################################
/**
* Returns a <fieldset> for editing post editor styles
*
* @param	string	Item title
* @param	string	Item varname
*
* @return	string
*/
function construct_posteditor_style_code($title, $varname)
{
	global $template_cache, $vbphrase, $vbulletin;

	$template = $template_cache['template']["$varname"];

	$color = fetch_inherited_color($template['styleid'], $vbulletin->GPC['dostyleid']);
	$revertcode = construct_revert_code($template['styleid'], 'template', $varname);

	$item = fetch_posteditor_styles($template['template_un']);

	$out = "
	<fieldset title=\"$title\">
		<legend>$title</legend>
		<div class=\"smallfont\" style=\"padding: 2px\">
		<table cellpadding=\"0\" cellspacing=\"2\" border=\"0\" width=\"100%\">
		<col width=\"50\"></col>
		<col></col>
		<col align=\"" . vB_Template_Runtime::fetchStyleVar('right') . "\"></col>
		<tr>
			<td rowspan=\"5\"><img src=\"control_examples/" . substr($varname, 14) . ".gif\" alt=\"\" title=\"$title\" /></td>
			" . construct_color_row($vbphrase['background'], "commontemplate[$varname][background]", htmlspecialchars_uni($item['background']), $color, 12, false) . "
		</tr>
		<tr>
			" . construct_color_row($vbphrase['font_color'], "commontemplate[$varname][color]", htmlspecialchars_uni($item['color']), $color, 12, false) . "
		</tr>
		<tr>
			<td>$vbphrase[padding]</td>
			<td><input type=\"text\" class=\"$color\" name=\"commontemplate[$varname][padding]\" size=\"20\" value=\"" . htmlspecialchars_uni($item['padding']) . "\" tabindex=\"1\" dir=\"ltr\" /></td>
		</tr>
		<tr>
			<td>$vbphrase[border]</td>
			<td><input type=\"text\" class=\"$color\" name=\"commontemplate[$varname][border]\" size=\"20\" value=\"" . htmlspecialchars_uni($item['border']) . "\" tabindex=\"1\" dir=\"ltr\" /></td>
		</tr>";
	if ($revertcode['info'])
	{
		$out .= "
		<tr>
			<td>$revertcode[info]</td>
			<td>$revertcode[revertcode]</td>
		</tr>";
	}
	else
	{
		$out .= "
		<tr>
			<td colspan=\"2\">&nbsp;</td>
		</tr>";
	}
	$out .= "
		</table>
		</div>
	</fieldset>";

	return $out;
}

// #############################################################################
/**
* Returns a row containing a <select> for use in selecting text alignment
*
* @param	string	Item title
* @param	array	Item info array
*
* @return	string
*/
function construct_text_align_code($title, $item)
{
	global $css, $color, $vbphrase;

	// this is currently disabled
	return false;

	$alignoptions = array(
		'' => '(' . $vbphrase['inherit'] . ')',
		'left' => $vbphrase['align_left'],
		'center' => $vbphrase['align_center'],
		'right' => $vbphrase['align_right'],
		'justify' => $vbphrase['justified']
	);

	eval("\$value = \$css" . $item . ";");
	return "\t\t<tr><td>$title</td><td>\n\t<select class=\"$color\" name=\"css" . str_replace("['", "[", str_replace("']", "]", $item)) . "\" tabindex=\"1\">\n" . construct_select_options($alignoptions, $value) . "\t</select>\n\t</td></tr>\n";
}

// #############################################################################
/**
* Returns a row containing an input and a color picker widget
*
* @param	string	Item title
* @param	string	Item varname
* @param	string	Item value
* @param	string	CSS class to display with
* @param	integer	Size of input box
* @param	boolean	Surround code with <tr> ... </tr> ?
*
* @return	string
*/
function construct_color_row($title, $name, $value, $class = 'bginput', $size = 22, $printtr = true)
{
	global $numcolors;

	$value = htmlspecialchars_uni($value);

	$html = '';
	if ($printtr)
	{
		$html .= "
		<tr>\n";
	}
	$html .= "
			<td>$title</td>
			<td>
				<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
				<tr>
					<td><input type=\"text\" class=\"$class\" name=\"$name\" id=\"color_$numcolors\" value=\"$value\" title=\"\$$name\" tabindex=\"1\" size=\"$size\" onchange=\"preview_color($numcolors)\" dir=\"ltr\" />&nbsp;</td>
					<td><div id=\"preview_$numcolors\" class=\"colorpreview\" onclick=\"open_color_picker($numcolors, event)\"></div></td>
				</tr>
				</table>
			</td>
	";
	if ($printtr)
	{
		$html .= "	</tr>\n";
	}

	$numcolors ++;

	return $html;
}

// #############################################################################
/**
* Prints a row containing an <input type="text" />
*
* @param	string	Title for row
* @param	string	Name for input field
* @param	string	Value for input field
* @param	boolean	Whether or not to htmlspecialchars the input field value
* @param	integer	Size for input field
* @param	integer	Max length for input field
* @param	string	Text direction for input field
* @param	mixed	If specified, overrides the default CSS class for the input field
*/
function print_color_input_row($title, $name, $value = '', $htmlise = true, $size = 35, $maxlength = 0, $direction = '', $inputclass = false)
{
	global $vbulletin, $numcolors;

	$direction = verify_text_direction($direction);

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\">
			<input style=\"float:" . vB_Template_Runtime::fetchStyleVar('left') . "; margin-" . vB_Template_Runtime::fetchStyleVar('right') . ": 4px\" type=\"text\" class=\"" . iif($inputclass, $inputclass, 'bginput') . "\" name=\"$name\" id=\"color_$numcolors\" value=\"" . iif($htmlise, htmlspecialchars_uni($value), $value) . "\" size=\"$size\"" . iif($maxlength, " maxlength=\"$maxlength\"") . " dir=\"$direction\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . " onchange=\"preview_color($numcolors)\" />
			<div style=\"float:" . vB_Template_Runtime::fetchStyleVar('left') . "\" id=\"preview_$numcolors\" class=\"colorpreview\" onclick=\"open_color_picker($numcolors, event)\"></div>
		</div>",
		'', 'top', $name
	);

	$numcolors++;
}

// #############################################################################
/**
* Builds the color picker popup item for the style editor
*
* @param	integer	Width of each color swatch (pixels)
* @param	string	CSS 'display' parameter (default: 'none')
*
* @return	string
*/
function construct_color_picker($size = 12, $display = 'none')
{
	global $vbulletin, $colorPickerWidth, $colorPickerType;

	$previewsize = 3 * $size;
	$surroundsize = $previewsize * 2;
	$colorPickerWidth = 21 * $size + 22;

	$html = "
	<style type=\"text/css\">
	#colorPicker
	{
		background: black;
		position: absolute;
		left: 0px;
		top: 0px;
		width: {$colorPickerWidth}px;
	}
	#colorFeedback
	{
		border: solid 1px black;
		border-bottom: none;
		width: {$colorPickerWidth}px;
	}
	#colorFeedback input
	{
		font: 11px verdana, arial, helvetica, sans-serif;
	}
	#colorFeedback button
	{
		width: 19px;
		height: 19px;
	}
	#txtColor
	{
		border: inset 1px;
		width: 70px;
	}
	#colorSurround
	{
		border: inset 1px;
		white-space: nowrap;
		width: {$surroundsize}px;
		height: 15px;
	}
	#colorSurround td
	{
		background-color: none;
		border: none;
		width: {$previewsize}px;
		height: 15px;
	}
	#swatches
	{
		background-color: black;
		width: {$colorPickerWidth}px;
	}
	#swatches td
	{
		background: black;
		border: none;
		width: {$size}px;
		height: {$size}px;
	}
	</style>
	<div id=\"colorPicker\" style=\"display:$display\" oncontextmenu=\"switch_color_picker(1); return false\" onmousewheel=\"switch_color_picker(event.wheelDelta * -1); return false;\">
	<table id=\"colorFeedback\" class=\"tcat\" cellpadding=\"0\" cellspacing=\"4\" border=\"0\" width=\"100%\">
	<tr>
		<td><button type=\"button\" onclick=\"col_click('transparent'); return false\"><img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/colorpicker_transparent.gif\" title=\"'transparent'\" alt=\"\" /></button></td>
		<td>
			<table id=\"colorSurround\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
			<tr>
				<td id=\"oldColor\" onclick=\"close_color_picker()\"></td>
				<td id=\"newColor\"></td>
			</tr>
			</table>
		</td>
		<td width=\"100%\"><input id=\"txtColor\" type=\"text\" value=\"\" size=\"8\" /></td>
		<td style=\"white-space:nowrap\">
			<input type=\"hidden\" name=\"colorPickerType\" id=\"colorPickerType\" value=\"$colorPickerType\" />
			<button type=\"button\" onclick=\"switch_color_picker(1); return false\"><img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/colorpicker_toggle.gif\" alt=\"\" /></button>
			<button type=\"button\" onclick=\"close_color_picker(); return false\"><img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/colorpicker_close.gif\" alt=\"\" /></button>
		</td>
	</tr>
	</table>
	<table id=\"swatches\" cellpadding=\"0\" cellspacing=\"1\" border=\"0\">\n";

	$colors = array(
		'00', '33', '66',
		'99', 'CC', 'FF'
	);

	$specials = array(
		'#000000', '#333333', '#666666',
		'#999999', '#CCCCCC', '#FFFFFF',
		'#FF0000', '#00FF00', '#0000FF',
		'#FFFF00', '#00FFFF', '#FF00FF'
	);

	$green = array(5, 4, 3, 2, 1, 0, 0, 1, 2, 3, 4, 5);
	$blue = array(0, 0, 0, 5, 4, 3, 2, 1, 0, 0, 1, 2, 3, 4, 5, 5, 4, 3, 2, 1, 0);

	for ($y = 0; $y < 12; $y++)
	{
		$html .= "\t<tr>\n";

		$html .= construct_color_picker_element(0, $y, '#000000');
		$html .= construct_color_picker_element(1, $y, $specials["$y"]);
		$html .= construct_color_picker_element(2, $y, '#000000');

		for ($x = 3; $x < 21; $x++)
		{
			$r = floor((20 - $x) / 6) * 2 + floor($y / 6);
			$g = $green["$y"];
			$b = $blue["$x"];

			$html .= construct_color_picker_element($x, $y, '#' . $colors["$r"] . $colors["$g"] . $colors["$b"]);
		}

		$html .= "\t</tr>\n";
	}

	$html .= "\t</table>
	</div>
	<script type=\"text/javascript\">
	<!--
	var tds = fetch_tags(fetch_object(\"swatches\"), \"td\");
	for (var i = 0; i < tds.length; i++)
	{
		tds[i].onclick = swatch_click;
		tds[i].onmouseover = swatch_over;
	}
	//-->
	</script>\n";

	return $html;
}

// #############################################################################
/**
* Builds a single color swatch for the color picker gadget
*
* @param	integer	Current X coordinate
* @param	integer	Current Y coordinate
* @param	string	Color
*
* @return	string
*/
function construct_color_picker_element($x, $y, $color)
{
	global $vbulletin;
	return "\t\t<td style=\"background:$color\" id=\"sw$x-$y\"><img src=\"../" . $vbulletin->options['cleargifurl'] . "\" alt=\"\" style=\"width:11px; height:11px\" /></td>\r\n";
}

// #############################################################################
/**
* Prints a block of controls for editing a CSS item on css.php?do=edit
*
* @param	string	Item title
* @param	string	Item description
* @param	array	Item info array
* @param	boolean	Print links edit section
* @param	boolean	Print table break
*/
function print_css_row($title, $description, $item, $dolinks = false, $restarttable = true)
{
	global $bgcounter, $css, $css_info, $color, $vbphrase, $vbulletin;
	static $item_js;

	++$item_js;

	$color = fetch_inherited_color($css_info["$item"], $vbulletin->GPC['dostyleid']);

	$title = htmlspecialchars_uni($title);
	switch ($css_info["$item"])
	{
		case -2:
		case -1:
			$tblhead_title = $title;
			$revertlink = '';
			$revertctrl = '';
			break;
		case $vbulletin->GPC['dostyleid']:
			$tblhead_title = "$title <span class=\"normal\">(" . $vbphrase['customized_in_this_style'] . ")</span>";
			$revertlink = 'title=' . urlencode($title) . '&amp;item=' . urlencode($item);
			$revertctrl = "<label for=\"rvcss_$item\">$vbphrase[revert_this_group_of_settings]<input type=\"checkbox\" id=\"rvcss_$item\" name=\"delete[css][$item]\" value=\"1\" tabindex=\"1\" title=\"$vbphrase[revert]\" /></label>";
			break;
		default:
			$tblhead_title = "$title <span class=\"normal\">(" . construct_phrase($vbphrase['customized_in_a_parent_style_x'], $css_info["$item"]) . ")</span>";
			$revertlink = 'title=' . urlencode($title) . '&amp;item=' . urlencode($item);
			$revertctrl = '';
			break;
	}

	echo "\n\n<!-- START $title CSS -->\n\n";

	print_column_style_code(array('width: 50%', 'width: 50%'));
	print_table_header($tblhead_title, 2);

	print_label_row(
		"\n\t<fieldset title=\"$vbphrase[standard_css]\">
		<legend>$vbphrase[standard_css]</legend>
		<table cellpadding=\"0\" cellspacing=\"2\" border=\"0\" width=\"100%\">
		<col width=\"50%\"></col>\n" .
		construct_css_input_row($vbphrase['background'], "['$item']['background']", $color, true) .
		construct_css_input_row($vbphrase['font_color'], "['$item']['color']", $color, true) .
		construct_css_input_row($vbphrase['font_style'], "['$item']['font']['style']", $color) .
		construct_css_input_row($vbphrase['font_size'], "['$item']['font']['size']", $color) .
		construct_css_input_row($vbphrase['font_family'], "['$item']['font']['family']", $color) .
		construct_text_align_code($vbphrase['alignment'], "['$item']['text-align']", $color) .  "
		</table>
		</fieldset>\n\t",

		"
		<fieldset id=\"extra_a_$item_js\" title=\"$vbphrase[extra_css]\">
		<legend>$vbphrase[extra_css]</legend>
		<div align=\"center\" style=\"padding: 2px\">
		<textarea name=\"css[$item][EXTRA]\" rows=\"4\" cols=\"50\" class=\"$color\" style=\"padding: 2px; width: 90%\" tabindex=\"1\" dir=\"ltr\">" . htmlspecialchars_uni($css["$item"]['EXTRA']) . "</textarea>
		</div>
		</fieldset>
		" . iif($description != '', "<fieldset id=\"desc_a_$item_js\" title=\"$vbphrase[description]\" style=\"margin-bottom:4px;\">
		<legend>$vbphrase[description]</legend>
		<div class=\"smallfont\" style=\"margin:4px 4px 0px 4px\">
			<img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/cp_help.gif\" alt=\"$title\" align=\"" . vB_Template_Runtime::fetchStyleVar('right') . "\" style=\"padding:0px 0px 0px 2px\" />
			$description
		</div>
		</fieldset>") . "\n"
	, 'alt2');
	if (is_browser('mozilla'))
	{
		echo "<script type=\"text/javascript\">reflow_fieldset('a_$item_js', true);</script>\n";
	}

	if ($dolinks)
	{
		print_description_row('
		<table cellpadding="4" cellspacing="0" border="0" width="100%">
		<tr>
		' . construct_link_css_input_row($vbphrase['normal_link'], $item, 'N', $color) . '
		' . construct_link_css_input_row($vbphrase['visited_link'], $item, 'V', $color) . '
		' . construct_link_css_input_row($vbphrase['hover_link'], $item, 'M', $color) . '
		</tr>
		</table>
		', 0, 2, 'alt2" style="padding: 0px');
	}

	if ($revertctrl != '')
	{
		print_description_row('<div class="smallfont" style="text-align: center">' . $revertctrl . '</div>', 0, 2, 'thead');
	}

	print_description_row("
		<div class=\"alt1\" style=\"border:inset 1px; padding:2px 10px 2px 10px; float:" . vB_Template_Runtime::fetchStyleVar('left') . "\">" . construct_phrase($vbphrase['css_selector_x'], "<code>$item</code>") . "</div>
		<!--" . iif($revertlink != '', "<input type=\"button\" class=\"button\" style=\"font-weight:normal\" value=\"$vbphrase[show_default]\" tabindex=\"1\" onclick=\"js_show_default_item('$revertlink', $dolinks);\" />") . "-->
		<!--<input type=\"submit\" class=\"button\" style=\"font-weight:normal\" value=\"  " . $vbphrase['save_css'] . "  \" tabindex=\"1\" />-->
	", 0, 2, 'tfoot" align="right');

	echo "\n\n<!-- END $title CSS -->\n\n";

	if ($restarttable)
	{
		print_table_break(' ');
	}
}

// #############################################################################
/**
* Reads results of form submission and updates special templates accordingly
*
* @param	array	Array of data from form
* @param	string	Variable type
* @param	string	Variable type name
*/
function build_special_templates($newtemplates, $templatetype, $vartype)
{
	global $vbulletin, $template_cache;

	DEVDEBUG('------------------------');

	foreach ($template_cache["$templatetype"] AS $title => $oldtemplate)
	{
		// ignore the '_validation' and '_failsafe' keys
		if ($title == '_validation' OR $title == '_failsafe')
		{
			continue;
		}

		// just carry on if there is no data for the current $newtemplate
		if (!isset($newtemplates["$title"]))
		{
			DEVDEBUG("\$$vartype" . "['$title'] is not set");
			continue;
		}

		// if delete the customized template, delete and continue
		if ($vbulletin->GPC['delete']["$vartype"]["$title"])
		{
			if ($vbulletin->GPC['dostyleid'] != -1 AND $vbulletin->GPC['dostyleid'] != -2)
			{
				$vbulletin->db->query_write("
					DELETE FROM " . TABLE_PREFIX . "template
					WHERE title = '" . $vbulletin->db->escape_string($title) . "' AND
					templatetype = '$templatetype' AND
					styleid = " . $vbulletin->GPC['dostyleid'] . "
				");
				DEVDEBUG("$vartype $title (reverted)");

				if ($templatetype == 'stylevar' AND $title == 'codeblockwidth')
				{
					$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "postparsed");
				}
			}
			continue;
		}

		// check for what to do with the template
		switch($templatetype)
		{
			case 'stylevar':
			{
				$newtemplate = $newtemplates["$title"];

				if (isset($newtemplates['_validation']["$title"]))
				{
					if (!preg_match($newtemplates['_validation']["$title"], $newtemplate))
					{
						$newtemplate = $newtemplates['_failsafe']["$title"];
					}
				}
				break;
			}
			case 'css':
				$newtemplate = serialize($newtemplates["$title"]);
				break;
			case 'replacement':
				$newtemplate = $newtemplates["$title"];
				break;
		}

		if ($newtemplate != $oldtemplate['template'])
		{
			// update existing $vartype template
			if ($oldtemplate['styleid'] == $vbulletin->GPC['dostyleid'])
			{
				$vbulletin->db->query_write("
					UPDATE " . TABLE_PREFIX . "template
					SET template = '" . $vbulletin->db->escape_string($newtemplate) . "',
					dateline = " . TIMENOW . ",
					username = '" . $vbulletin->db->escape_string($vbulletin->userinfo['username']) . "'
					WHERE title = '" . $vbulletin->db->escape_string($title) . "' AND
					templatetype = '$templatetype' AND
					styleid = " . $vbulletin->GPC['dostyleid'] . "
				");
				DEVDEBUG("$vartype $title (updated)");
			// insert new $vartype template
			}
			else
			{
				/*insert query*/
				$vbulletin->db->query_write("
					INSERT INTO " . TABLE_PREFIX . "template
					(styleid, templatetype, title, dateline, username, template)
					VALUES
					(" . intval($vbulletin->GPC['dostyleid']) . ", '$templatetype', '" . $vbulletin->db->escape_string($title) . "', " . TIMENOW . ", '" . $vbulletin->db->escape_string($vbulletin->userinfo['username']) . "', '" . $vbulletin->db->escape_string($newtemplate) . "')
				");
				DEVDEBUG("$vartype $title (inserted)");
			}

			if ($templatetype == 'stylevar' AND $title == 'codeblockwidth')
			{
				$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "postparsed");
			}
		}
		else
		{
			DEVDEBUG("$vartype $title (not changed)");
		}

	} // end foreach($template_cache)

}

// #############################################################################
/**
* Prints a row containing template search javascript controls
*/
function print_template_javascript()
{
	global $vbphrase, $vbulletin;

	print_phrase_ref_popup_javascript();

	echo '<script type="text/javascript" src="../clientscript/vbulletin_templatemgr.js?v=' . SIMPLE_VERSION . '"></script>';
	echo '<script type="text/javascript">
<!--
	var textarea_id = "' . $vbulletin->textarea_id . '";
	var vbphrase = { \'not_found\' : "' . fetch_js_safe_string($vbphrase['not_found']) . '" };
// -->
</script>
';

	print_label_row(iif(is_browser('ie') OR is_browser('mozilla', '20040707'), $vbphrase['search_in_template'], $vbphrase['additional_functions']), iif(is_browser('ie') OR is_browser('mozilla', '1.7'), '
	<input type="text" class="bginput" name="string" accesskey="t" value="' . htmlspecialchars_uni($vbulletin->GPC['searchstring']) . '" size="20" onChange="n=0;" tabindex="1" />
	<input type="button" class="button" style="font-weight:normal" value=" ' . $vbphrase['find'] . ' " accesskey="f" onClick="findInPage(document.cpform.string.value);" tabindex="1" />
	&nbsp;') .
	'<input type="button" class="button" style="font-weight:normal" value=" ' . $vbphrase['copy'] . ' " accesskey="c" onclick="HighlightAll();" tabindex="1" />
	&nbsp;
	<input type="button" class="button" style="font-weight:normal" value="' . $vbphrase['view_quickref'] . '" accesskey="v" onclick="js_open_phrase_ref(0, 0);" tabindex="1" />
	<script type="text/javascript">document.cpform.string.onkeypress = findInPageKeyPress;</script>
	');
}

// ###########################################################################################
// START XML STYLE FILE FUNCTIONS

function get_style_export_xml($styleid, $product, $product_version, $title, $mode)
{
	// $only is the (badly named) list of template groups
	global $vbulletin, $vbphrase, $only;

	/* Load the master 'style' phrases for use in
	the export, and then rebuild the $only array */
	load_phrases(array('style'), -1);
	build_template_groups($only);

	if ($styleid == -1 OR $styleid == -2)
	{
		// set the style title as 'master style'
		$style = array('title' => ($styleid == -1) ? $vbphrase['master_style'] : $vbphrase['mobile_master_style']);
		$sqlcondition = "styleid = {$styleid}";
		$parentlist = $styleid;
		$styletype = ($styleid  == -1) ? 'master' : 'mobilemaster';
	}
	else
	{
		// query everything from the specified style
		$style = $vbulletin->db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "style
			WHERE styleid = " . $styleid
		);

		//export as master -- export a style with all changes as a new master style.
		if ($mode == 2)
		{
			//only allowed in debug mode.
			if (!$vbulletin->debug)
			{
				print_cp_no_permission();
			}

			// get all items from this style and all parent styles
			$sqlcondition = "templateid IN(" . implode(',', unserialize($style['templatelist'])) . ")";
			$sqlcondition .= " AND title NOT LIKE 'vbcms_grid_%'";
			$parentlist = $style['parentlist'];
			$styletype = ($style['type'] == 'standard') ? 'master' : 'mobilemaster';
			$title = $vbphrase['master_style'];
		}

		//export with parent styles
		else if ($mode == 1)
		{
			// get all items from this style and all parent styles (except master)
			$sqlcondition = "styleid <> -1 AND styleid <> -2 AND templateid IN(" . implode(',', unserialize($style['templatelist'])) . ")";
			//remove the master style id off the end of the list
			$parentlist = substr(trim($style['parentlist']), 0, -3);
			$styletype = 'custom';
		}

		//this style only
		else
		{
			// get only items customized in THIS style
			$sqlcondition = "styleid = " . $styleid;
			$parentlist = $styleid;
			$styletype = 'custom';
		}
	}

	if ($product == 'vbulletin')
	{
		$sqlcondition .= " AND (product = '" . $vbulletin->db->escape_string($product) . "' OR product = '')";
	}
	else
	{
		$sqlcondition .= " AND product = '" . $vbulletin->db->escape_string($product) . "'";
	}

	// set a default title
	if ($title == '' OR $styleid == -1 OR $styleid == -2)
	{
		$title = $style['title'];
	}

	// --------------------------------------------
	// query the templates and put them in an array

	$templates = array();

	$gettemplates = $vbulletin->db->query_read("
		SELECT title, templatetype, username, dateline, version,
		IF(templatetype = 'template', template_un, template) AS template
		FROM " . TABLE_PREFIX . "template
		WHERE $sqlcondition
		ORDER BY title
	");

	$ugcount = $ugtemplates = 0;
	while ($gettemplate = $vbulletin->db->fetch_array($gettemplates))
	{
		switch($gettemplate['templatetype'])
		{
			case 'template': // regular template

				// if we have ad template, and we are exporting as master, make sure we do not export the add data
				if (substr($gettemplate['title'], 0, 3) == 'ad_' AND $mode == 2)
				{
					$gettemplate['template'] = '';
				}

				$isgrouped = false;
				foreach(array_keys($only) AS $group)
				{
					if (strpos(strtolower(" $gettemplate[title]"), $group) == 1)
					{
						$templates["$group"][] = $gettemplate;
						$isgrouped = true;
					}
				}
				if (!$isgrouped)
				{
					if ($ugtemplates % 10 == 0)
					{
						$ugcount++;
					}
					$ugtemplates++;
					//sort ungrouped templates last.
					$ugcount_key = 'zzz' . str_pad($ugcount, 5, '0', STR_PAD_LEFT);
					$templates[$ugcount_key][] = $gettemplate;
					$only[$ugcount_key] = construct_phrase($vbphrase['ungrouped_templates_x'], $ugcount);
				}
			break;

			case 'stylevar': // stylevar
				$templates[$vbphrase['stylevar_special_templates']][] = $gettemplate;
			break;

			case 'css': // css
				$templates[$vbphrase['css_special_templates']][] = $gettemplate;
			break;

			case 'replacement': // replacement
				$templates[$vbphrase['replacement_var_special_templates']][] = $gettemplate;
			break;
		}
	}
	unset($template);
	$vbulletin->db->free_result($gettemplates);
	if (!empty($templates))
	{
		ksort($templates);
	}

	// --------------------------------------------
	// fetch stylevar-dfns

	$stylevarinfo = get_stylevars_for_export($product, $parentlist);
	$stylevar_cache = $stylevarinfo['stylevars'];
	$stylevar_dfn_cache = $stylevarinfo['stylevardfns'];

	if (empty($templates) AND empty($stylevar_cache) AND empty($stylevar_dfn_cache))
	{
		throw new vB_Exception_AdminStopMessage('download_contains_no_customizations');
	}

	// --------------------------------------------
	// now output the XML

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_XML_Builder($vbulletin);
	$xml->add_group('style',
		array(
			'name'      => $title,
			'vbversion' => $product_version,
			'product'   => $product,
			'type'      => $styletype,
		)
	);

	foreach($templates AS $group => $grouptemplates)
	{
		$xml->add_group('templategroup', array('name' => iif(isset($only["$group"]), $only["$group"], $group)));
		foreach($grouptemplates AS $template)
		{
			$xml->add_tag('template', $template['template'],
				array(
					'name'         => htmlspecialchars($template['title']),
					'templatetype' => $template['templatetype'],
					'date'         => $template['dateline'],
					'username'     => $template['username'],
					'version'      => htmlspecialchars_uni($template['version'])),
				true
			);
		}
		$xml->close_group();
	}

	$xml->add_group('stylevardfns');
	foreach ($stylevar_dfn_cache AS $stylevargroupname => $stylevargroup)
	{
		$xml->add_group('stylevargroup', array('name' => $stylevargroupname));
		foreach($stylevargroup AS $stylevar)
		{
			$xml->add_tag('stylevar', '',
				array(
					'name'       => htmlspecialchars($stylevar['stylevarid']),
					'datatype'   => $stylevar['datatype'],
					'validation' => vb_base64_encode($stylevar['validation']),
					'failsafe'   => vb_base64_encode($stylevar['failsafe'])
				)
			);
		}
		$xml->close_group();
	}
	$xml->close_group();

	$xml->add_group('stylevars');
	foreach ($stylevar_cache AS $stylevarid => $stylevar)
	{
		$xml->add_tag('stylevar', '',
			array(
				'name'  => htmlspecialchars($stylevar['stylevarid']),
				'value' => vb_base64_encode($stylevar['value'])
			)
		);
	}
	$xml->close_group();

	$xml->close_group();

	$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n";
	$doc .= $xml->output();
	$xml = null;
	return $doc;
}

/// #############################################################################
/**
* Reads XML style file and imports data from it into the database
*
* @param	string	XML data
* @param	integer	Style ID
* @param	integer	Parent style ID
* @param	string	New style title
* @param	boolean	Allow vBulletin version mismatch
* @param	integer	Display order for new style
* @param	boolean	Allow user selection of new style
* @param	int|null Starting template group index for this run of importing templates (0 based). Null means all templates (single run)
* @param	int|null
* @param	int 0 = normal import, 1 = style generator
* @param	string Import Filename
*
* @return	array	Array of information about the imported style
*/
function xml_import_style(
	$xml = false,
	$styleid = -1,
	$parentid = -1,
	$title = '',
	$anyversion = false,
	$displayorder = 1,
	$userselect = true,
	$startat = null,
	$perpage = null,
	$importtype = 0,
	$filename = 'vbulletin-style.xml'
)
{
	// $GLOBALS['path'] needs to be passed into this function or reference $vbulletin->GPC['path']

	global $vbulletin, $vbphrase;

	print_dots_start('<b>' . $vbphrase['importing_style'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');

	require_once(DIR . '/includes/class_xml.php');

	//where is this used?  I hate having this random global value in the middle of this function
	$xmlobj = new vB_XML_Parser($xml, $vbulletin->GPC['path']);
	if ($xmlobj->error_no == 1)
	{
			print_dots_stop();
			print_stop_message('no_xml_and_no_path');
	}
	else if ($xmlobj->error_no == 2)
	{
			print_dots_stop();
			print_stop_message('please_ensure_x_file_is_located_at_y', $filename, $vbulletin->GPC['path']);
	}

	if(!$parsed_xml = $xmlobj->parse())
	{
		print_dots_stop();
		print_stop_message('xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line());
	}

	if (!$styleid)
	{
		if ($parentid == -1 OR $parentid == -2)
		{
			$styleid = $parentid;
		}
		else
		{
			$style = $vbulletin->db->query_first("
				SELECT IF(type = 'standard', -1, -2) AS mastertype
				FROM " . TABLE_PREFIX . "style
				WHERE styleid = $parentid
			");
			$styleid = $style['mastertype'];
		}
	}

	$version = $parsed_xml['vbversion'];
	$master = ($parsed_xml['type'] == 'master' ? true : false);
	$mobilemaster = ($parsed_xml['type'] == 'mobilemaster' ? true : false);
	$title = (empty($title) ? $parsed_xml['name'] : $title);
	$product = (empty($parsed_xml['product']) ? 'vbulletin' : $parsed_xml['product']);

	$one_pass = (is_null($startat) AND is_null($perpage));
	if (!$one_pass AND (!is_numeric($startat) OR !is_numeric($perpage) OR $perpage <= 0 OR $startat < 0))
	{
			print_dots_stop();
			print_stop_message('');
	}

	if ($one_pass OR ($startat == 0))
	{
		// version check
		$full_product_info = fetch_product_list(true);
		$product_info = $full_product_info["$product"];

		if ($version != $product_info['version'] AND !$anyversion AND !$master AND !$mobilemaster)
		{
			print_dots_stop();
			print_stop_message('upload_file_created_with_different_version', $product_info['version'], $version);
		}

		//Initialize the style -- either init the master, create a new style, or verify the style to overwrite.
		if ($master OR $mobilemaster)
		{
			$styleid = $master ? -1 : -2;
			$specialstyleid = $master ? -10 : -20;
			$import_data = @unserialize(fetch_adminutil_text("master_style_import_{$product}_{$specialstyleid}"));
			if (!empty($import_data) AND (TIMENOW - $import_data['last_import']) <= 30)
			{
				print_dots_stop();
				if ($master)
				{
					print_stop_message('must_wait_x_seconds_master_style_import', vb_number_format($import_data['last_import'] + 30 - TIMENOW));
				}
				else
				{
					print_stop_message('must_wait_x_seconds_mobile_master_style_import', vb_number_format($import_data['last_import'] + 30 - TIMENOW));
				}
			}

			$stylename = $master ? $vbphrase['master_style'] : $vbphrase['mobile_master_style'];
			// overwrite master style
			if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
			{
				echo "<h3>$stylename</h3>\n<p>$vbphrase[please_wait]</p>";
				vbflush();
			}

			$vbulletin->db->query_write("
				DELETE FROM " . TABLE_PREFIX . "template
				WHERE styleid = {$specialstyleid} AND (product = '" . $vbulletin->db->escape_string($product) . "'" .
					(($product == 'vbulletin') ? " OR product = ''" : "") . ")"
			);

			$vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "template
				SET styleid = {$specialstyleid} WHERE styleid = {$styleid} AND (product = '" . $vbulletin->db->escape_string($product) . "'" .
					(($product == 'vbulletin') ? " OR product = ''" : "") . ")"
			);
		}
		else
		{
			if ($styleid == -1 OR $styleid == -2)
			{
				$type = ($styleid == -1) ? 'standard' : 'mobile';
				// creating a new style
				$test = $vbulletin->db->query_first("
					SELECT styleid FROM " . TABLE_PREFIX . "style
					WHERE
						title = '" . $vbulletin->db->escape_string($title) . "'
							AND
						type = '{$type}'
				");

				if ($test)
				{
					print_dots_stop();
					print_stop_message('style_already_exists', $title);
				}
				else
				{
					echo "<h3><b>" . construct_phrase($vbphrase['creating_a_new_style_called_x'], $title) . "</b></h3>\n<p>$vbphrase[please_wait]</p>";
					vbflush();

					/*insert query*/
					$styleresult = $vbulletin->db->query_write("
						INSERT INTO " . TABLE_PREFIX . "style
						(title, parentid, displayorder, userselect, type)
						VALUES
						('" . $vbulletin->db->escape_string($title) . "', $parentid, $displayorder, " . ($userselect ? 1 : 0) . ", '{$type}')
					");
					$styleid = $vbulletin->db->insert_id($styleresult);
				}
			}
			else
			{
				// overwriting an existing style
				if ($getstyle = $vbulletin->db->query_first("SELECT title FROM " . TABLE_PREFIX . "style WHERE styleid = $styleid"))
				{
					if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
					{
						echo "<h3><b>" . construct_phrase($vbphrase['overwriting_style_x'], $getstyle['title']) . "</b></h3>\n<p>$vbphrase[please_wait]</p>";
						vbflush();
					}
				}
				else
				{
					print_dots_stop();
					print_stop_message('cant_overwrite_non_existent_style');
				}
			}
		}
	}
	else
	{
		//We should never get styleid = -1 unless $master is true;
		if (($styleid == -1 AND !$master) OR ($styleid == -2 AND !$mobilemaster))
		{
			$type = ($styleid == -1) ? 'standard' : 'mobile';
			$stylerec =	$vbulletin->db->query_first("
				SELECT styleid
				FROM " . TABLE_PREFIX . "style
				WHERE
					title = '" . $vbulletin->db->escape_string($title) . "'
						AND
					type = '{$type}'
			");

			if ($stylerec AND intval($stylerec['styleid']))
			{
				$styleid = $stylerec['styleid'];
			}
			else
			{
				print_dots_stop();
				print_stop_message('incorrect_style_setting', $title);
			}
		}
	}

	$outputtext = '';
	//load the templates
	if ($arr = $parsed_xml['templategroup'])
	{
		if (empty($arr[0]))
		{
			$arr = array($arr);
		}

		$templates_done = (is_numeric($startat) AND (count($arr) <= $startat));
		if ($one_pass OR !$templates_done)
		{
			if (!$one_pass)
			{
				$arr = array_slice($arr, $startat, $perpage);
			}
			$outputtext = xml_import_template_groups($styleid, $product, $arr, !$one_pass);
		}
	}
	else
	{
		$templates_done = true;
	}

	//note that templates may actually be done at this point, but templates_done is
	//only true if templates were completed in a prior step. If we are doing a multi-pass
	//process, we don't want to install stylevars in the same pass.  We aren't really done
	//until we hit a pass where the templates are done before processing.
	$done = ($one_pass OR $templates_done);
	if ($done)
	{
		//load stylevars and definitions
		// re-import any stylevar definitions
		if (($master OR $mobilemaster) AND is_array($parsed_xml['stylevardfns']) AND !empty($parsed_xml['stylevardfns']['stylevargroup']))
		{
			xml_import_stylevar_definitions($parsed_xml['stylevardfns'], 'vbulletin', $master ? -1 : -2);
		}

		//if the tag is present but empty we'll end up with a string with whitespace which
		//is a non "empty" value.
		if (!empty($parsed_xml['stylevars']) AND is_array($parsed_xml['stylevars']))
		{
			xml_import_stylevars($parsed_xml['stylevars'], $styleid, $importtype);
		}

		if ($master OR $mobilemaster)
		{
			xml_import_restore_ad_templates($styleid);
			$specialstyleid = $master ? -10 : -20;
			build_adminutil_text("master_style_import_{$product}_{$specialstyleid}", serialize(array('last_import' => TIMENOW)));
		}

		print_dots_stop();
	}

	return array(
		'version'          => $version,
		'master'           => $master,
		'mobilemaster'     => $mobilemaster,
		'title'            => $title,
		'product'          => $product,
		'done'             => $done,
		'overwritestyleid' => $styleid,
		'output'           => $outputtext,
	);
}

function xml_import_template_groups($styleid, $product, $templategroup_array, $output_group_name)
{
	global $vbulletin, $vbphrase;

	$safe_product =  $vbulletin->db->escape_string($product);

	$querytemplates = 0;
	$outputtext = '';
	if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
	{
		echo '<br />';
		vbflush();
	}
	foreach ($templategroup_array AS $templategroup)
	{
		if (empty($templategroup['template'][0]))
		{
			$tg = array($templategroup['template']);
		}
		else
		{
			$tg = &$templategroup['template'];
		}

		if ($output_group_name)
		{
			$text = construct_phrase($vbphrase['template_group_x'], $templategroup['name']);
			$outputtext .= $text;
			if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
			{
				echo $text;
				vbflush();
			}
		}

		foreach($tg AS $template)
		{
			$title = $vbulletin->db->escape_string($template['name']);
			$template['username'] = $vbulletin->db->escape_string($template['username']);
			$template['version'] = $vbulletin->db->escape_string($template['version']);

			if ($template['templatetype'] != 'template')
			{
				// template is a special template == not compiled.
				$uncompiled =  '';
				$compiled = $vbulletin->db->escape_string($template['value']);
			}
			else
			{
				//template is a regular template, do the compile and save the original.
				$uncompiled =  $vbulletin->db->escape_string($template['value']);
				$compiled = $vbulletin->db->escape_string(compile_template($template['value']));
			}

			$querybits[] = "($styleid, '$template[templatetype]', '$title', '$compiled', '$uncompiled', " .
				"" . intval($template['date']) . ", '$template[username]', '$template[version]', " .
				"'$safe_product')";

			if (++$querytemplates % 20 == 0)
			{
				/*insert query*/
				$vbulletin->db->query_write("
					REPLACE INTO " . TABLE_PREFIX . "template
					(styleid, templatetype, title, template, template_un, dateline, username, version, product)
					VALUES
					" . implode(',', $querybits) . "
				");
				$querybits = array();
			}

			// Send some output to the browser inside this loop so certain hosts
			// don't artificially kill the script. See bug #34585
			if (!defined('SUPPRESS_KEEPALIVE_ECHO'))
			{
				if (VB_AREA == 'Upgrade' OR VB_AREA == 'Install')
				{
					echo ' ';
				}
				else
				{
					echo '-';
				}
				vbflush();
			}
		}

		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo '<br />';
			vbflush();
		}
	}

	// insert any remaining templates
	if (!empty($querybits))
	{
		/*insert query*/
		$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "template
			(styleid, templatetype, title, template, template_un, dateline, username, version, product)
			VALUES
			" . implode(',', $querybits) . "
		");
		$querybits = array();
	}

	return $outputtext;
}

function xml_import_restore_ad_templates($styleid = -1)
{
	global $vbulletin;

	$specialstyleid = ($styleid == -2 ? -20 : -10);

	// Get the template titles
	$save = array();
	$save_tables = $vbulletin->db->query_read("
		SELECT title
		FROM " . TABLE_PREFIX . "template
		WHERE templatetype = 'template'
			AND styleid = {$specialstyleid}
			AND product IN('vbulletin', '')
			AND title LIKE 'ad\_%'
	");

	while ($table = $vbulletin->db->fetch_array($save_tables))
	{
		$save[] =  "'" . $vbulletin->db->escape_string($table['title']) . "'";
	}

	// Are there any
	if (count($save))
	{
		// Delete any style id -1 ad templates that may of just been imported.
		$vbulletin->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "template
			WHERE templatetype = 'template'
				AND styleid = {$styleid}
				AND product IN('vbulletin', '')
				AND title IN (" . implode(',', $save) . ")
		");

		// Replace the -1 templates with the special styleid before they are deleted
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "template
			SET styleid = {$styleid}
			WHERE templatetype = 'template'
				AND styleid = {$specialstyleid}
				AND product IN('vbulletin', '')
				AND title IN (" . implode(',', $save) . ")
		");
	}
}

function xml_import_templates($templates, $masterstyleid, $info, &$rebuild)
{
	global $vbulletin;

	$querybits = array();
	$querytemplates = 0;

	if (!isset($templates[0]))
	{
		$templates = array($templates);
	}

	foreach ($templates AS $template)
	{
		$title = $vbulletin->db->escape_string($template['name']);
		$template['template'] = $vbulletin->db->escape_string($template['value']);
		$template['username'] = $vbulletin->db->escape_string($template['username']);
		$template['templatetype'] = $vbulletin->db->escape_string($template['templatetype']);
		$template['date'] = intval($template['date']);

		if ($template['templatetype'] != 'template')
		{
			// template is a special template
			$querybits[] = "({$masterstyleid}, '$template[templatetype]', '$title', '$template[template]', '', $template[date], '$template[username]', '" . $vbulletin->db->escape_string($template['version']) . "', '" . $vbulletin->db->escape_string($info['productid']) . "')";
		}
		else
		{
			// template is a standard template
			$querybits[] = "({$masterstyleid}, '$template[templatetype]', '$title', '" . $vbulletin->db->escape_string(compile_template($template['value'])) . "', '$template[template]', $template[date], '$template[username]', '" . $vbulletin->db->escape_string($template['version']) . "', '" . $vbulletin->db->escape_string($info['productid']) . "')";
		}

		if (++$querytemplates % 20 == 0)
		{
			/*insert query*/
			$vbulletin->db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "template
					(styleid, templatetype, title, template, template_un, dateline, username, version, product)
				VALUES
					" . implode(',', $querybits) . "
			");
			$querybits = array();
		}

		// Send some output to the browser inside this loop so certain hosts
		// don't artificially kill the script. See bug #34585
		if (VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
		{
			echo ' ';
			vbflush();
		}
	}

	// insert any remaining templates
	if (!empty($querybits))
	{
		/*insert query*/
		$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "template
				(styleid, templatetype, title, template, template_un, dateline, username, version, product)
			VALUES
				" . implode(',', $querybits) . "
		");
	}
	unset($querybits);

	$rebuild['templates'] = true;
}

function xml_import_stylevar_definitions($stylevardfns, $product, $masterstyleid = -1)
{
	global $vbulletin;

	$querybits = array();
	$stylevardfns = get_xml_list($stylevardfns['stylevargroup']);

	/* Mark current definitions as old data.
	   Use -10 as thats what the templates use
	   parentid will = 0 for imported stylevars,
	   but is set to -1 for custom added sytlevars.
	   We only really care about this for default
	   vbulletin as any other products will clear up
	   their own stylevars when they are uninstalled. */

	if ($product == 'vbulletin')
	{
		$where = "product = 'vbulletin' AND parentid = 0 AND styleid = {$masterstyleid}";
	}
	else
	{
		$where = "product = '" . $vbulletin->db->escape_string($product) . "' AND styleid = {$masterstyleid}";
	}

	$specialstyleid = ($masterstyleid == -1) ? -10 : -20;
	$vbulletin->db->query_write("
		UPDATE IGNORE " . TABLE_PREFIX . "stylevardfn
		SET styleid = {$specialstyleid} WHERE $where
	");

	$deletebits = array();
	foreach ($stylevardfns AS $stylevardfn_group)
	{
		$sg = get_xml_list($stylevardfn_group['stylevar']);
		foreach ($sg AS $stylevardfn)
		{
			$querybits[] = "('" . $vbulletin->db->escape_string($stylevardfn['name']) . "', {$masterstyleid}, '" .
				$vbulletin->db->escape_string($stylevardfn_group['name']) . "', '" .
				$vbulletin->db->escape_string($product) . "', '" .
				$vbulletin->db->escape_string($stylevardfn['datatype']) . "', '" .
				$vbulletin->db->escape_string(vb_base64_decode($stylevardfn['validation'])) . "', '" .
				$vbulletin->db->escape_string(vb_base64_decode($stylevardfn['failsafe'])) . "', 0, 0
			)";

			$deletebits[] = $vbulletin->db->escape_string($stylevardfn['name']);
		}

		if (!empty($querybits))
		{
			$vbulletin->db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "stylevardfn
				(stylevarid, styleid, stylevargroup, product, datatype, validation, failsafe, parentid, parentlist)
				VALUES
				" . implode(',', $querybits) . "
			");

			$vbulletin->db->query_write("
				DELETE FROM " . TABLE_PREFIX . "stylevardfn
				WHERE
					styleid = {$specialstyleid}
						AND
					stylevarid IN ('" . implode("','", $deletebits) . "')
			");
		}
		$querybits = array();
	}
}

function xml_import_stylevars($stylevars, $styleid, $importtype = 0)
{
	global $vbulletin;

	$querybits = array();
	$sv = get_xml_list($stylevars['stylevar']);

	$dateline = TIMENOW;
	$importname = ($importtype ? 'Style-Generator' : 'Style-Importer');

	foreach ($sv AS $stylevar)
	{
		//the parser merges attributes and child nodes into a single array.  The unnamed text
		//children get placed into a key called "value" automagically.  Since we don't have any
		//text children we just take the first one.
		$value = vb_base64_decode($stylevar['value'][0]);
		$querybits[] = "('" . $vbulletin->db->escape_string($stylevar['name']) . "', $styleid, '" .
			$vbulletin->db->escape_string($value) . "', $dateline, '$importname')";
	}

	if (!empty($querybits))
	{
		$vbulletin->db->query_write($sql = "
			REPLACE INTO " . TABLE_PREFIX . "stylevar
			(stylevarid, styleid, value, dateline, username)
			VALUES
			" . implode(',', $querybits) . "
		");
	}
	$querybits = array();
}

/**
*	Get a list from the parsed xml array
*
* A common way to format lists in xml is
* <tag>
* 	<subtag />
* 	<subtag />
*   ...
* </tag>
*
* The problem is a single item is ambiguous
* <tag>
* 	<subtag />
* </tag>
*
* It could be a one element list or it could be a scalar child -- we only
* know from the context of the data, which the parser doesn't know.  Our parser
* assumes that it is a scalar value unless there are multiple tags with the same
* name.  Therefor so the first is rendered as:
*
* tag['subtag'] = array (0 => $element, 1 => $element)
*
* While the second is:
*
* tag['subtag'] = $element.
*
* Rather than handle each list element as a special case if there is only one item in the
* xml, this function will examine the element passed and if it isn't a 0 indexed array
* as expect will wrap the single element in an array() call.  The first case is not
* affected and the second is converted to tag['subtag'] = array(0 => $element), which
* is what we'd actually expect.
*
*	@param array The array entry for the list value.
* @return The list properly regularized to a numerically indexed array.
*/
function get_xml_list($xmlarray)
{
	if (is_array($xmlarray) AND array_key_exists(0, $xmlarray))
	{
		return $xmlarray;
	}
	else
	{
		return array($xmlarray);
	}
}

/**
*	Get the stylevar list processed to export
*
*	Seperated into its own function for reuse by products
*
*	@param string product -- The name of the product to
*	@param string stylelist -- The styles to export as a comma seperated string
*		(in descending order of precedence).  THE CALLER IS RESPONSIBLE FOR SANITIZING THE
*		INPUT.
*/
function get_stylevars_for_export($product, $stylelist)
{
	global $vbulletin;

	$product_filter = "product =" . (($product == 'vbulletin') ?
		"'vbulletin' OR product = ''" : "'" . $vbulletin->db->escape_string($product) . "'");

	$stylevar_cache = array();

	$stylevars = $vbulletin->db->query_read("
		SELECT stylevar.*,
			INSTR(',$stylelist,', CONCAT(',', stylevar.styleid, ',') ) AS ordercontrol
		FROM " . TABLE_PREFIX . "stylevar AS stylevar
		INNER JOIN " . TABLE_PREFIX . "stylevardfn AS stylevardfn ON (stylevardfn.stylevarid = stylevar.stylevarid AND $product_filter)
		WHERE stylevar.styleid IN ($stylelist)
		ORDER BY ordercontrol DESC
	");

	while ($stylevar = $vbulletin->db->fetch_array($stylevars))
	{
		$stylevar_cache[$stylevar['stylevarid']] = $stylevar;
		ksort($stylevar_cache);
	}

	$stylevar_dfn_cache = array();
	$stylevar_dfns = $vbulletin->db->query_read("
		SELECT *,
			INSTR(',$stylelist,', CONCAT(',', styleid, ',') ) AS ordercontrol
		FROM " . TABLE_PREFIX . "stylevardfn AS stylevardfn
		WHERE styleid IN ($stylelist)
		AND $product_filter
		ORDER BY stylevargroup, stylevarid, ordercontrol
	");
	while ($stylevar_dfn = $vbulletin->db->fetch_array($stylevar_dfns))
	{
		$stylevar_dfn_cache[$stylevar_dfn['stylevargroup']][] = $stylevar_dfn;
	}

	return array("stylevars" => $stylevar_cache, "stylevardfns" => $stylevar_dfn_cache);
}

// #############################################################################
/**
* Function used for usort'ing a collection of templates.
* This function will return newer versions first.
*
* @param	array	First version
* @param	array	Second version
*
* @return	integer	-1, 0, 1
*/
function history_compare($a, $b)
{
	// if either of them does not have a version, make it look really old to the
	// comparison tool so it doesn't get bumped all the way up when its not supposed to
	if (!$a['version'])
	{
		$a['version'] = "0.0.0";
	}

	if (!$b['version'])
	{
		$b['version'] = "0.0.0";
	}

	// these return values are backwards to sort in descending order
	if (is_newer_version($a['version'], $b['version']))
	{
		return -1;
	}
	else if (is_newer_version($b['version'], $a['version']))
	{
		return 1;
	}
	else
	{
		if($a['type'] == $b['type'])
		{
			return ($a['dateline'] > $b['dateline']) ? -1 : 1;
		}
		else if($a['type'] == "historical")
		{
			return 1;
		}
		else
		{
			return -1;
		}
	}
}

// #############################################################################
/**
*	Checks for problems with conflict resolution
*
*	This was not put into check_template_errors because the reported for that
* assumes a certain kind of error and is confusing with the conflict error
* message.
*
* @param	string Template PHP code
* @return string Error message detected or empty string if no error
*/
function check_template_conflict_error($template)
{
	if (preg_match(get_conflict_text_re(), $template))
	{
		$error = fetch_error('template_conflict_exists');
		if (!$error)
		{
			//if the error lookup fails return *something* so the calling code doesn't think
			//we succeeded.
			return "Conflict Error";
		}
		else
		{
			return $error;
		}
	}

	return '';
}

/**
* Collects errors encountered while parsing a template and returns them
*
* @param	string	Template PHP code
*
* @return	string
*/
function check_template_errors($template)
{
	// Attempt to enable display_errors so that this eval actually returns something in the event of an error
	@ini_set('display_errors', true);

	require_once(DIR . '/includes/functions_calendar.php'); // to make sure can_moderate_calendar exists

	if (preg_match('#^(.*)<if condition=(\\\\"|\')(.*)\\2>#siU', $template, $match))
	{
		// remnants of a conditional -- that means something is malformed, probably missing a </if>
		return fetch_error('template_conditional_end_missing_x', (substr_count($match[1], "\n") + 1));
	}

	if (preg_match('#^(.*)</if>#siU', $template, $match))
	{
		// remnants of a conditional -- missing beginning
		return fetch_error('template_conditional_beginning_missing_x', (substr_count($match[1], "\n") + 1));
	}

	if (strpos(@ini_get('disable_functions'), 'ob_start') !== false)
	{
		// alternate method in case OB is disabled; probably not as fool proof
		@ini_set('track_errors', true);
		$oldlevel = error_reporting(0);
		eval('$devnull = "' . $template . '";');
		error_reporting($oldlevel);

		if (strpos(strtolower($php_errormsg), 'parse') !== false)
		{
			// only return error if we think there's a parse error
			// best workaround to ignore "undefined variable" type errors
			return $php_errormsg;
		}
		else
		{
			return '';
		}
	}
	else
	{
		$olderrors = @ini_set('display_errors', true);
		$oldlevel = error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);

		ob_start();
		if (strpos($template, '$final_rendered') !== false)
		{
			eval($template);
		}
		else
		{
			eval('$devnull = "' . $template . '";');
		}

		$errors = ob_get_contents();
		ob_end_clean();

		error_reporting($oldlevel);
		if ($olderrors !== false)
		{
			@ini_set('display_errors', $olderrors);
		}

		return $errors;
	}
}

/**
* Fetches a current or historical template.
*
* @param	integer	The ID (in the appropriate table) of the record you want to fetch
* @param	string	Type of template you want to fetch; should be "current" or "historical"
*
* @return	array	The data for the matching record
*/
function fetch_template_current_historical(&$id, $type)
{
	global $vbulletin, $db;

	$id = intval($id);

	if ($type == 'current')
	{
		return $db->query_first("
			SELECT *, template_un AS templatetext
			FROM " . TABLE_PREFIX . "template
			WHERE templateid = $id
		");
	}
	else
	{
		return $db->query_first("
			SELECT *, template AS templatetext
			FROM " . TABLE_PREFIX . "templatehistory
			WHERE templatehistoryid = $id
		");
	}
}


/**
* Fetches the list of templates that have a changed status in the database
*
* List is hierarchical by style.
*
* @return array Associative array of styleid => template list with each template
* list being an array of templateid => template record.
*/
function fetch_changed_templates()
{
	global $db;
	$select = "tCustom.templateid, tCustom.title, tCustom.styleid,
			tCustom.username AS customuser, tCustom.dateline AS customdate, tCustom.version AS customversion,
			tCustom.mergestatus AS custommergestatus,
			tGlobal.username AS globaluser, tGlobal.dateline AS globaldate, tGlobal.version AS globalversion,
			tGlobal.product, templatemerge.savedtemplateid";

	$query = fetch_changed_templates_query_internal($select);
	$set = $db->query_read($query);
	while($template = $db->fetch_array($set))
	{
		$templates["$template[styleid]"]["$template[templateid]"] = $template;
	}
	$db->free_result($set);

	$result = fetch_old_templates();
	foreach ($result['cache'] AS $styleid => $temps)
	{
		foreach ($temps AS $templateid => $template)
		{
			$templates["$styleid"]["$templateid"] = $template;
		}
	}

	return $templates;
}

/**
* Fetches the count templates that have a changed status in the database
*
* @return int Number of changed templates
*/
function fetch_changed_templates_count()
{
	global $db;
	$select = "count(*) as count";
	$query = fetch_changed_templates_query_internal($select);
	$result = $db->query_first($query);
	$oldstuff = fetch_old_templates();

	return $result['count'] + $oldstuff['count'];
}

/*
 * Fetches updated templates, vB3 style
 *
 * @return	array
 */
function fetch_old_templates()
{
	global $db;

	$full_product_info = fetch_product_list(true);

	$customcache = array();
	$count = 0;
	$templates = $db->query_read("
		SELECT tCustom.templateid, tCustom.title, tCustom.styleid, tCustom.mergestatus AS custommergestatus,
			tCustom.username AS customuser, tCustom.dateline AS customdate, tCustom.version AS customversion,
			tGlobal.username AS globaluser, tGlobal.dateline AS globaldate, tGlobal.version AS globalversion,
			tGlobal.product
		FROM " . TABLE_PREFIX . "template AS tCustom
		INNER JOIN " . TABLE_PREFIX . "style AS style ON (style.styleid = tCustom.styleid)
		INNER JOIN " . TABLE_PREFIX . "template AS tGlobal ON (tGlobal.styleid = IF(style.type = 'standard', -1, -2) AND tGlobal.title = tCustom.title)
		WHERE
			tCustom.styleid > 0
				AND
			tCustom.templatetype = 'template'
				AND
			tCustom.mergestatus = 'none'
		ORDER BY tCustom.title
	");
	while($template = $db->fetch_array($templates))
	{
		if (!$template['product'])
		{
			$template['product'] = 'vbulletin';
		}

		$product_version = $full_product_info["$template[product]"]['version'];

		// version in the template is newer than the version of the product,
		// which probably means it's using the vB version
		if (is_newer_version($template['globalversion'], $product_version))
		{
			$template['globalversion'] = $product_version;
		}
		if (is_newer_version($template['customversion'], $product_version))
		{
			$template['customversion'] = $product_version;
		}

		if (is_newer_version($template['globalversion'], $template['customversion']))
		{
			$count++;
			$customcache["$template[styleid]"]["$template[templateid]"] = $template;
		}

	}

	return array(
		'count' => $count,
		'cache' => $customcache,
	);
}

/**
* Internal function to generate query for changed templates
*
*	@private
* @param string $select fields to be selected from the result set
* @return query to fetch changed templates
*/
//should only be called by the above cover functions
function fetch_changed_templates_query_internal($select, $styleid = -1)
{
	$query = "
		SELECT $select
		FROM " . TABLE_PREFIX . "template AS tCustom
		INNER JOIN " . TABLE_PREFIX . "style AS style ON (style.styleid = tCustom.styleid)
		INNER JOIN " . TABLE_PREFIX . "template AS tGlobal ON (tGlobal.styleid = IF (style.type = 'standard', -1, -2) AND tGlobal.title = tCustom.title)
		LEFT JOIN " . TABLE_PREFIX . "templatemerge AS templatemerge ON (templatemerge.templateid = tCustom.templateid)
		WHERE
			tCustom.styleid > 0
				AND
			tCustom.templatetype = 'template'
				AND
			tCustom.mergestatus IN ('merged', 'conflicted')
		ORDER BY tCustom.title
	";

	return $query;
}

/**
*	Get the template from the template id
*
*	@param id template id
* @return array template table record
*/
function fetch_template_by_id($id)
{
	$filter = "template.templateid = " . intval($id);
	return fetch_template_internal($filter);
}

/**
*	Get the template from the template using the style and title
*
*	@param int styleid
* @param int title
* @return array template table record
*/
function fetch_template_by_title($styleid, $title)
{
	global $db;
	$qTitle = "'" . $db->escape_string($title) . "'";
	$filter = "template.styleid = " . intval($styleid) . " AND template.title = $qTitle AND template.templatetype='template'";
	return fetch_template_internal($filter);
}


/**
*	Get the template from the templatemerge (saved origin templates in the merge process)
* using the id
*
* The record is returned with the addition of an extra template_un field.
* This is set to the same value as the template field and is intended to match up the
* fields in the merge table with the fields in the main template table.
*
*	@param int id - Note that this is the same value as the main template table id
* @return array template record with extra template_un field
*/
function fetch_origin_template_by_id($id)
{
	global $db;
	$result = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "templatemerge
		WHERE templateid = " . intval($id)
	);

	if ($result)
	{
		$result['template_un'] = $result['template'];
	}
	return $result;
}

/**
*	Get the template from the template using the id
*
* The record is returned with the addition of an extra template_un field.
* This is set to the same value as the template field and is intended to match up the
* fields in the merge table with the fields in the main template table.
*
*	@param int id - Note that this is the not same value as the main template table id,
*		there can be multiple saved history versions for a given template
* @return array template record with extra template_un field
*/
function fetch_historical_template_by_id($id)
{
	global $db;
	$result = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX ."templatehistory
		WHERE templatehistoryid = " . intval($id)
	);

	//adjust to look like the main template result
	if ($result)
	{
		$result['template_un'] = $result['template'];
	}
	return $result;
}

/**
*	Get the template record
*
* This should only be called by cover functions in the file
* caller is responsible for sql security on $filter;
*
*	@filter string where clause filter
* @private
*/
function fetch_template_internal($filter)
{
	global $db;
	return $db->query_first("
		SELECT template.*, style.type
		FROM " . TABLE_PREFIX . "template AS template
		LEFT JOIN " . TABLE_PREFIX . "style AS style ON (template.styleid = style.styleid)
		WHERE $filter
	");
}


/**
* Get the requested templates for a merge operation
*
*	This gets the templates needed to show the merge display for a given custom
* template.  These are the custom template, the current default template, and the
* origin template saved when the template was initially merged.
*
* We can only display merges for templates that were actually merged during upgrade
*	as we only save the necesary information at that point.  If we don't have the
* available inforamtion to support the merge display, then an exception will be thrown
* with an explanatory message. Updating a template after upgrade
*
*	If the custom template was successfully merged we return the historical template
* save at upgrade time instead of the current (automatically updated at merge time)
* template.  Otherwise the differences merged into the current template will not be
* correctly displayed.
*
*	@param int templateid - The id of the custom user template to start this off
*	@throws Exception thrown if state does not support a merge display for
* 	the requested template
*	@return array array('custom' => $custom, 'new' => $new, 'origin' => $origin)
*/
function fetch_templates_for_merge($templateid)
{
	global $vbphrase;
	if (!$templateid)
	{
		throw new Exception($vbphrase['merge_error_invalid_template']);
	}

	$custom = fetch_template_by_id($templateid);
	if (!$custom)
	{
		throw new Exception(construct_phrase($vbphrase['merge_error_notemplate'], $templateid));
	}

	if ($custom['mergestatus'] == 'none')
	{
		throw new Exception($vbphrase['merge_error_nomerge']);
	}

	$new = fetch_template_by_title(($custom['type'] == 'mobile' ? -2 : -1), $custom['title']);
	if (!$new)
	{
		throw new Exception(construct_phrase($vbphrase['merge_error_nodefault'],  $custom['title']));
	}

	$origin = fetch_origin_template_by_id($custom['templateid']);
	if (!$origin)
	{
		throw new Exception(construct_phrase($vbphrase['merge_error_noorigin'],  $custom['title']));
	}

	if ($custom['mergestatus'] == 'merged')
	{
		$custom = fetch_historical_template_by_id($origin['savedtemplateid']);
		if (!$custom)
		{
			throw new Exception(construct_phrase($vbphrase['merge_error_nohistory'],  $custom['title']));
		}
	}

	return array('custom' => $custom, 'new' => $new, 'origin' => $origin);
}


/**
* Format the text for a merge conflict
*
* Take the three conflict text strings and format them into a human readable
* text block for display.
*
* @param string	Text from custom template
* @param string	Text from origin template
* @param string	Text from current VBulletin template
* @param string	Version string for origin template
* @param string	Version string for currnet VBulletin template
* @param bool	Whether to output the wrapping text with html markup for richer display
*
* @return string -- combined text
*/
function format_conflict_text($custom, $origin, $new, $origin_version, $new_version, $html_markup = false, $wrap = true)
{
	global $vbphrase;

	$new_title = $vbphrase['new_default_value'];
	$origin_title = $vbphrase['old_default_value'];
	$custom_title = $vbphrase['your_customized_value'];

	if ($html_markup)
	{
		$text =
			"<div class=\"merge-conflict-row\"><b>$custom_title</b><div>" . format_diff_text($custom, $wrap) . "</div></div>"
			. "<div class=\"merge-conflict-row\"><b>$origin_title</b><div>" . format_diff_text($origin, $wrap) . "</div></div>"
			. "<div class=\"merge-conflict-final-row\"><b>$new_title</b><div>" . format_diff_text($new, $wrap) . "</div></div>";
	}
	else
	{
		$origin_bar = "======== $origin_title ========";

		$text  = "<<<<<<<< $custom_title <<<<<<<<\n";
		$text .= $custom;
		$text .= $origin_bar . "\n";
		$text .= $origin;
		$text .= str_repeat("=", strlen($origin_bar)) . "\n";
		$text .= $new;
		$text .= ">>>>>>>> $new_title >>>>>>>>\n";
	}

	return $text;
}

function format_diff_text($string, $wrap = true)
{
	if (trim($string) === '')
	{
		return '&nbsp;';
	}
	else
	{
		if ($wrap)
		{
			$string = nl2br(htmlspecialchars_uni($string));
			$string = preg_replace('#( ){2}#', '&nbsp; ', $string);
			$string = str_replace("\t", '&nbsp; &nbsp; ', $string);
			return "<code>$string</code>";
		}
		else
		{
			return '<pre style="display:inline">' . "\n" . htmlspecialchars_uni($string) . '</pre>';
		}
	}
}

/**
* Return regular expression to detect the blocks returned by format_conflict_text
*
* @return string -- value suitable for passing to preg_match as an re
*/
function get_conflict_text_re()
{
	//we'll start by grabbing the formatting from format_conflict_text directly
	//this should reduce cases were we change the formatting and forget to change the re
	$re = format_conflict_text(".*\n", ".*\n", ".*\n", ".*", '.*');

	//we don't have a set number of delimeter characters since we try to even up the lines
	//in some cases (which can vary based on the version strings).  Since we don't have the
	//exact version available, we don't know how many got inserted.  We'll match any number
	//(we use two because we should always have at least that many and it dramatically improves
	//performance -- probably because we get an early failure on all of the html tags)
	$re = preg_replace('#<+#', '<<+', $re);
	$re = preg_replace('#=+#', '==+', $re);
	$re = preg_replace('#>+#', '>>+', $re);

	//handle variations on newlines.
	$re = str_replace("\n", "(?:\r|\n|\r\n)", $re);

	//convert the preg format
	$re = "#$re#isU";
	return $re;
}

// ******************************** DECLARE ARRAYS AND GLOBAL VARS ******************************

/**
* Template group names => phrases
*
* @var	array
*/
function build_template_groups(&$only)
{
	global $vbphrase;

	$only = array
	(
		// phrased groups
		'activitystream' => $vbphrase['group_activity_stream'],
		'buddylist'      => $vbphrase['group_buddy_list'],
		'calendar'       => $vbphrase['group_calendar'],
		'faq'            => $vbphrase['group_faq'],
		'reputation'     => $vbphrase['group_user_reputation'],
		'poll'           => $vbphrase['group_poll'],
		'pm'             => $vbphrase['group_private_message'],
		'register'       => $vbphrase['group_registration'],
		'search'         => $vbphrase['group_search'],
		'usercp'         => $vbphrase['group_user_control_panel'],
		'usernote'       => $vbphrase['group_user_note'],
		'whosonline'     => $vbphrase['group_whos_online'],
		'showgroup'      => $vbphrase['group_show_groups'],
		'posticon'       => $vbphrase['group_post_icon'],
		'userfield'      => $vbphrase['group_user_profile_field'],
		'bbcode'         => $vbphrase['group_bb_code_layout'],
		'help'           => $vbphrase['group_help'],
		'editor'         => $vbphrase['group_editor'],
		'forumdisplay'   => $vbphrase['group_forum_display'],
		'forumhome'      => $vbphrase['group_forum_home'],
		'pagenav'        => $vbphrase['group_page_navigation'],
		'postbit'        => $vbphrase['group_postbit'],
		'posthistory'    => $vbphrase['group_posthistory'],
		'threadbit'      => $vbphrase['group_threadbit'],
		'im_'            => $vbphrase['group_instant_messaging'],
		'memberinfo'     => $vbphrase['group_member_info'],
		'memberlist'     => $vbphrase['group_members_list'],
		'moderation'     => $vbphrase['group_moderation'],
		'modify'         => $vbphrase['group_modify_user_option'],
		'new'            => $vbphrase['group_new_posting'],
		'showthread'     => $vbphrase['group_show_thread'],
		'smiliepopup'    => $vbphrase['group_smilie_popup'],
		'subscribe'      => $vbphrase['group_subscribed_thread'],
		'whoposted'      => $vbphrase['group_who_posted'],
		'threadadmin'    => $vbphrase['group_thread_administration'],
		'navbar'         => $vbphrase['group_navigation_breadcrumb'],
		'printthread'    => $vbphrase['group_printable_thread'],
		'attachmentlist' => $vbphrase['group_attachment_list'],
		'userinfraction' => $vbphrase['group_user_infraction'],
		'subscription'   => $vbphrase['group_paid_subscriptions'],
		'announcement'   => $vbphrase['group_announcement'],
		'visitormessage' => $vbphrase['group_visitor_message'],
		'humanverify'    => $vbphrase['group_human_verification'],
		'socialgroups'	 => $vbphrase['group_socialgroups'],
		'picture'        => $vbphrase['group_picture_comment'],
		'ad_'            => $vbphrase['group_ad_location'],
		'album'          => $vbphrase['group_album'],
		'tag'            => $vbphrase['group_tag'],
		'assetmanager'   => $vbphrase['group_asset_manager'],
		'css'            => $vbphrase['group_css'],
		'block'          => $vbphrase['group_block'],
		'facebook'		 => $vbphrase['group_facebook'],
	);
}

$only = array();
build_template_groups($only);

if (class_exists('vBulletinHook', false))
{
	($hook = vBulletinHook::fetch_hook('template_groups')) ? eval($hook) : false;
}

// #############################################################################
/**
* Prints the palette for the style generator
*
* @param	array 	contains all help info
*
* @return	string	Formatted help text
*/
function print_style_palette($palette)
{
	foreach ($palette as $id => $info) {
		echo "<div id=\"$id\" class=\"colorpalette\">
			<div id=\"colordisplay-$id\" class=\"colordisplay $info[0]\">&nbsp;
			</div>
			<div id=\"colorinfo-$id\" class=\"colorinfo\">
				$info[1]
			</div>
		</div>
		";
	}
}

// #############################################################################
/**
* Generates the style for the style generator
*
* @param	array 	contains all color data
* @param	int 	Number for the parent id
* @param	string	Title for the genrated style
* @param	boolean	Override version check
* @param	int		Display order for the style
* @param	boolean	True / False whether it will be user selectable
* @param	int		Version
*
*/

function generate_style($data, $parentid, $title, $anyversion = false, $displayorder, $userselect, $version)
{
	global $vbulletin;
	require_once(DIR . '/includes/class_xml.php');
	// Need to check variable for values - Check to make sure we have a name etc

	$arr = explode('{', stripslashes($data)); // checked below
	$hex = array(0 => ''); // start at one
	$match = $match2 = array(); // initialize
	$type = 'lps'; // checked below

	// Get master stylevar data
	$svdata = $vbulletin->db->query_read("
		SELECT stylevarid
		FROM " . TABLE_PREFIX . "stylevar
		WHERE styleid = -1
	");

	// Generate list
	$masterlist = array();
	while ($svlist = $vbulletin->db->fetch_array($svdata))
	{
		$masterlist[$svlist['stylevarid']] = true;
	}

	foreach ($arr AS $key => $value)
	{
		if (preg_match("/\"hex\":\"([0-9A-F]{6})\"/", $value, $match) == 1)
		{
			$hex[] = '#' . $match[1];
		}
		if (preg_match("/\"type\":\"([a-z0-9]{3})\"/", $value, $match2) == 1)
		{
			$type = $match2[1];
		}
	}

	switch (count($hex))
	{
		case '11':
			break;

		default:
			print_stop_message('incorrect_color_mapping');
	}

	switch ($type)
	{
		case 'lpt': // White : Similar to the current style
			$sample_file = "style_generator_sample_white.xml";
			$from = array('#A60000', '#BF3030', '#FF4040', '#FF7373');
			$to = array($hex[3], $hex[2], $hex[1], $hex[1]);
			break;

		case 'gry': // Grey :: Primary 3 and Primary 4 only
			$sample_file = "style_generator_sample_gray.xml";
			$from = array('#A60000', '#FF4040');
			$to = array($hex[1], $hex[4]);
			break;

		case 'drk': // Dark : Primary 3 and Primary 4 only
			$sample_file = "style_generator_sample_dark.xml";
			$from = array('#A60000', '#FF4040');
			$to = array($hex[1], $hex[4]);
			break;

		case 'lps': // Light : Primary and Secondary
		default: // Default to lps (as previously set at start of function, not dark).
			$sample_file = "style_generator_sample_light.xml";
			$from = array('#FF0000', '#BF3030', '#A60000', '#FF4040', '#FF7373', '#009999', '#1D7373', '#5CCCCC');
			$to = array($hex[1], $hex[2], $hex[3], $hex[4], $hex[5], $hex[6], $hex[7], $hex[10]);
			break;
	}

	$decode = $match = array();

	$xmlobj = new vB_XML_Parser(false, DIR . '/includes/xml/' . $sample_file);
	$styledata = $xmlobj->parse();
	foreach($styledata['stylevars']['stylevar'] AS $stylevars)
	{
		// The XML Parser outputs 2 values for the value field when one is set as an attribute.
		// The work around for now is to specify the first value (the attribute). In reality
		// the parser shouldn't add a blank 'value' if it exists as an attribute.
		$decode[$stylevars['name']] = vb_base64_decode($stylevars['value'][0]);
	}

	// Preg match and then replace. Shutter, a better method is on the way.
	$match = array();
	foreach ($decode AS $name => $value) // replaces the RRGGBB in the sample_*.xml file with chosen colors and re-encode
	{
		if (preg_match("/\"(#[a-zA-Z0-9]{6})\"/", $value, $match) == 1)
		{
			$upper = '"' . strtoupper($match[1]) . '"';
			$stylevarparts[$name] = str_replace($from, $to, preg_replace("/\"(#[a-zA-Z0-9]{6})\"/", $upper, $value));
		}
	}

	if($title===''){$title = 'Style ' . time();}
	$xml = new vB_XML_Builder($vbulletin);
	$xml->add_group('style',
		array(
			'name'      => $title,
			'vbversion' => $version,
			'product'   => 'vbulletin',
			'type'      => 'custom'
		)
	);

	$xml->add_group('stylevars');
	foreach ($stylevarparts AS $stylevarid => $stylevar)
	{
		// Add if exists
		if($masterlist[$stylevarid])
		{
			$xml->add_tag('stylevar', '',
				array(
					'name'  => htmlspecialchars($stylevarid),
					'value' => vb_base64_encode($stylevar)
				)
			);
		}
	}
	// Close stylevar group
	$xml->close_group();
	// Close style group
	$xml->close_group();

	$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n";
	$doc .= $xml->output();
	$xml = null;

	if ($parentid == -1 OR $parentid == -2)
	{
		$masterstyleid = $parentid;
	}
	else
	{
		$style = $vbulletin->db->query_first("
			SELECT IF(type = 'standard', '-1', '-2') AS masterstyleid
			FROM " . TABLE_PREFIX . "style
			WHERE styleid = {$parentid}
		");
		$masterstyleid = $style['masterstyleid'];
	}
	xml_import_style($doc, $masterstyleid, $parentid, $title, $anyversion, $displayorder, $userselect, null, null, 1);

	print_cp_redirect("template.php?" . $vbulletin->session->vars['sessionurl'] . "do=rebuild&amp;goto=template.php?" . $vbulletin->session->vars['sessionurl']);
}

// #############################################################################
/**
* Prints out the save options for the style generator
*/

function import_generated_style()
{
	global $vbphrase, $stylecache;

	cache_styles();
	echo "
	<script type=\"text/javascript\">
	<!--
	function js_confirm_upload(tform, filefield)
	{
		if (filefield.value == \"\")
		{
			return confirm(\"" . construct_phrase($vbphrase['you_did_not_specify_a_file_to_upload'], " + tform.serverfile.value + ") . "\");
		}
		return true;
	}
	function js_fetch_style_title()
	{
		styleid = document.forms.downloadform.dostyleid.options[document.forms.downloadform.dostyleid.selectedIndex].value;
		document.forms.downloadform.title.value = style[styleid];
	}
	var style = new Array();
	style['-2'] = \"" . $vbphrase['mobile_master_style'] . "\"
	style['-1'] = \"" . $vbphrase['master_style'] . "\"";
	foreach($stylecache AS $styleid => $style)
	{
		echo "\n\tstyle['$styleid'] = \"" . addslashes_js($style['title'], '"') . "\";";
		$styleoptions["$styleid"] = construct_depth_mark($style['depth'], '--', iif($vbulletin->debug, '--', '')) . ' ' . $style['title'];
	}
	echo "
	// -->
	</script>";

	echo '<div id="styleform">';
	echo '<form id="form" action="template.php?do=stylegenerator" method="post">';
	construct_hidden_code('adid', $vbulletin->GPC['adid']);
	echo '<input id="form-data" type="hidden" name="data" />';
	echo '<div class="styledetails"><div id="title-generated-style" class="help title-generated-style">';
	echo $vbphrase['title_generated_style'] . '<div id="ctrl_name"><input type="text" class="bginput" name="name" id="form-name" value="" size="" dir="ltr" tabindex="1" /></div>';
	echo '</div><div id="parent-id" class="help parent-id">';
	echo $vbphrase['parent_style'] . '<div><select name="parentid" id="sel_parentid_1" tabindex="1" class="bginput">' . construct_select_options($styleoptions, -1, false) . '</select></div>';
	echo '</div></div><div class="styleoptions"><div id="display-order" class="help display-order">';
	echo $vbphrase['display_order'] . '<div id="ctrl_displayorder"><input type="text" class="bginput" name="displayorder" id="form-displayorder" value="1" size="" dir="ltr" tabindex="1" /></div>';
	echo '</div><div id="allow-user-selection" class="help allow-user-selection">';
	echo $vbphrase['allow_user_selection'] . '<div id="ctrl_userselect" class="smallfont" style="white-space:nowrap">
		<label for="rb_1_userselect_2"><input type="radio" name="userselect" id="rb_1_userselect_2" value="1" tabindex="1" checked="checked" />' . $vbphrase['yes'] . '</label>
 		<label for="rb_0_userselect_2"><input type="radio" name="userselect" id="rb_0_userselect_2" value="0" tabindex="1" />' . $vbphrase['no'] . '</label>
 	</div>';
	echo '</div></div></form></div>';
}

// #############################################################################
/**
 * Determines whether or not the given vB3 style has been customized (in vB3)
 *
 * @param	int	vB3 styleid
 *
 * @return	bool	Result
 */
function is_customized_vb3_style($styleid)
{
	global $vbulletin;

	static $resultcache = null;

	$styleid = intval($styleid);

	// no master style or invalid styleids
	if ($styleid < 1)
	{
		return false;
	}

	// fetch all results at once
	if ($resultcache === null)
	{
		$resultcache = array();

		$style_result = $vbulletin->db->query_read("
			SELECT title, template, styleid
			FROM " . TABLE_PREFIX . "template
			WHERE templatetype IN('stylevar', 'css')
		");
		$style = array();
		while ($row = $vbulletin->db->fetch_array($style_result))
		{
			if (!isset($style[$row['styleid']]))
			{
				$style[$row['styleid']] = array();
			}
			$style[$row['styleid']][$row['title']] = $row['template'];
		}

		$masterstyle = $style[-1];
		unset($style[-1]);

		foreach ($style AS $this_styleid => $this_style)
		{
			if ($this_styleid < 1)
			{
				continue;
			}

			$resultcache[$this_styleid] = false;

			foreach ($masterstyle AS $k => $v)
			{
				if (isset($this_style[$k]) AND $v != $this_style[$k])
				{
					$resultcache[$this_styleid] = true;
					continue 2;
				}
			}
		}
	}

	return (bool)$resultcache[$styleid];
}

/* Load groups of phrases for a specific language */
function load_phrases($groups = false, $language = -1)
{
	global $vbulletin, $vbphrase;

	$where = '';
	$language = intval($language);

	if (is_array($groups))
	{
		$glist = array();
		foreach($groups AS $group)
		{
			$glist[] = "'" . $vbulletin->db->escape_string($group) . "'";
		}
		$where = 'AND fieldname IN (' . implode(',', $glist) . ') ';
	}

	$getphrases = $vbulletin->db->query_read_slave("
		SELECT languageid, varname, text
		FROM " . TABLE_PREFIX . "phrase
		WHERE languageid = $language $where
	");

	while ($phrase = $vbulletin->db->fetch_array($getphrases))
	{
		$vbphrase[$phrase['varname']] = $phrase['text'];
	}

	$vbulletin->db->free_result($getphrases);
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63817 $
|| ####################################################################
\*======================================================================*/
?>