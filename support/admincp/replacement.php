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
define('CVS_REVISION', '$RCSfile$ - $Revision: 58539 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('style');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_template.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminstyles'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'templateid' => TYPE_INT,
	'dostyleid'  => TYPE_INT,
));

// ############################# LOG ACTION ###############################
log_admin_action(iif($vbulletin->GPC['templateid'] != 0, "template id = " . $vbulletin->GPC['templateid'], iif($vbulletin->GPC['dostyleid'] != 0, "style id = " . $vbulletin->GPC['dostyleid'])));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['replacement_variable_manager'], '', '<style type="text/css">.ldi li, .lsq a { font: 11px tahoma; list-style-type:disc; }</style>');

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// *********************** kill *********************
if ($_POST['do'] == 'kill')
{
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "template WHERE templateid = " . $vbulletin->GPC['templateid']);
	print_rebuild_style($vbulletin->GPC['dostyleid'], '', 0, 0, 1, 0);

	print_cp_redirect("replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify", 1);

}

// *********************** remove *********************
if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'group' => TYPE_STR
	));

	$hidden = array();
	$hidden['dostyleid'] =& $vbulletin->GPC['dostyleid'];
	$hidden['group'] = $vbulletin->GPC['group'];
	print_delete_confirmation('template', $vbulletin->GPC['templateid'], 'replacement', 'kill', 'replacement_variable', $hidden, $vbphrase['please_be_aware_replacement_variable_is_inherited']);

}

// *********************** update *********************
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'findtext'    => TYPE_STR,
		'replacetext' => TYPE_STR
	));

	$vbulletin->GPC['findtext'] = strtolower($vbulletin->GPC['findtext']);

	if ($vbulletin->GPC['findtext'] === '')
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($vbulletin->GPC['dostyleid'] != -1 AND $vbulletin->GPC['dostyleid'] != -2)
	{
		$style = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "style WHERE styleid = " . $vbulletin->GPC['dostyleid']);
		$templateids = implode(',' , unserialize($style['templatelist']));
		$templates = $db->query_read("
			SELECT templateid, title, template, styleid
			FROM " . TABLE_PREFIX . "template
			WHERE
				templateid IN ($templateids)
					AND
				templatetype = 'replacement'
		");
	}
	else
	{
		$templates = $db->query_read("
			SELECT templateid, title, template, styleid
			FROM " . TABLE_PREFIX . "template
			WHERE
				styleid = {$vbulletin->GPC['dostyleid']}
					AND
				templatetype = 'replacement'
		");
	}
	while ($template = $db->fetch_array($templates))
	{
		$template_cache[strtolower($template['title'])] = $template;
	}

	$changed = false;
	if (isset($template_cache["{$vbulletin->GPC['findtext']}"]))
	{
		$existing =& $template_cache["{$vbulletin->GPC['findtext']}"];

		// prevent duplicates
		if ($vbulletin->GPC['templateid'] == 0 AND $existing['styleid'] == $vbulletin->GPC['dostyleid'])
		{
			print_stop_message('replacement_already_exists',
				htmlspecialchars($existing['title']),
				htmlspecialchars($existing['template']),
				"replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;dostyleid=$existing[styleid]&amp;templateid=$existing[templateid]"
			);
		}

		if ($existing['template'] != $vbulletin->GPC['replacetext'])
		{
			$changed = true;
			if ($existing['styleid'] != $vbulletin->GPC['dostyleid'])
			{
				/*insert query*/
				$db->query_write("
					INSERT INTO " . TABLE_PREFIX . "template
					(styleid, templatetype, title, template)
					VALUES
					(" . $vbulletin->GPC['dostyleid'] . ", 'replacement', '" . $db->escape_string($vbulletin->GPC['findtext']) . "', '" . $db->escape_string($vbulletin->GPC['replacetext']) . "')
				");
			}
			else
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "template
					SET template = '" . $db->escape_string($vbulletin->GPC['replacetext']) . "'
					WHERE templateid = " . $vbulletin->GPC['templateid'] . "
				");
			}
		}
	}
	else
	{
		$changed = true;
		/*insert query*/
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "template
			(styleid, templatetype, title, template)
			VALUES
			(" . $vbulletin->GPC['dostyleid'] . ", 'replacement', '" . $db->escape_string($vbulletin->GPC['findtext']) . "', '" . $db->escape_string($vbulletin->GPC['replacetext']) . "')
		");
	}

	$goto = "replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify";
	if ($changed)
	{
		if ($vbulletin->GPC['dostyleid'] == -1)
		{
			$title = $vbphrase['master_style'];
		}
		else if ($vbulletin->GPC['dostyleid'] == -2)
		{
			$title = $vbphrase['mobile_master_style'];
		}
		else
		{
			$title = $style['title'];
		}
		print_rebuild_style($vbulletin->GPC['dostyleid'], $title, 0, 0, 1, 0);
		print_cp_redirect($goto, 1);
	}
	else
	{
		define('CP_REDIRECT', $goto);
		print_stop_message('nothing_to_do');
	}
}

// *********************** edit *********************
if ($_REQUEST['do'] == 'edit')
{

	$style = $db->query_first("SELECT styleid, title FROM " . TABLE_PREFIX . "style WHERE styleid = " . $vbulletin->GPC['dostyleid']);
	$replacement = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "template WHERE templateid = " . $vbulletin->GPC['templateid']);

	print_form_header('replacement', 'update');
	construct_hidden_code('templateid', $vbulletin->GPC['templateid']);
	construct_hidden_code('dostyleid', $vbulletin->GPC['dostyleid']);
	construct_hidden_code('findtext', $replacement['title']);
	if ($replacement['styleid'] == $vbulletin->GPC['dostyleid'])
	{
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['replacement_variable'], htmlspecialchars_uni($replacement['title']), $replacement['templateid']));
	}
	else
	{
		print_table_header(construct_phrase($vbphrase['customize_replacement_variable_x'], htmlspecialchars_uni($replacement['title'])));
	}
		if ($vbulletin->GPC['dostyleid'] == -1)
		{
			$title = $vbphrase['master_style'];
		}
		else if ($vbulletin->GPC['dostyleid'] == -2)
		{
			$title = $vbphrase['mobile_master_style'];
		}
		else
		{
			$title = $style['title'];
		}	
	print_label_row($vbphrase['style'], $title);
	print_label_row("$vbphrase[search_for_text] <dfn>($vbphrase[case_insensitive])</dfn>", htmlspecialchars_uni($replacement['title']));
	print_textarea_row($vbphrase['replace_with_text'], 'replacetext', $replacement['template'], 5, 50);
	print_submit_row($vbphrase['save']);

}

// *********************** insert *********************
if ($_POST['do'] == 'insert')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'findtext'    => TYPE_STR,
		'replacetext' => TYPE_STR,
	));

	$vbulletin->GPC['findtext'] = strtolower($vbulletin->GPC['findtext']);

	if ($vbulletin->GPC['findtext'] === '')
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($existing = $db->query_first("
		SELECT templateid, styleid, title, template
		FROM " . TABLE_PREFIX . "template
		WHERE styleid = " . $vbulletin->GPC['dostyleid'] . "
			AND templatetype = 'replacement'
			AND title = '" . $db->escape_string($vbulletin->GPC['findtext']) . "'
	"))
	{
		print_stop_message('replacement_already_exists',
			htmlspecialchars($existing['title']),
			htmlspecialchars($existing['template']),
			"replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;dostyleid=$existing[styleid]&amp;templateid=$existing[templateid]"
		);
	}
	else
	{
		/*insert query*/
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "template
				(styleid, templatetype, title, template)
			VALUES
				(" . $vbulletin->GPC['dostyleid'] . ", 'replacement', '" . $db->escape_string($vbulletin->GPC['findtext']) . "', '" . $db->escape_string($vbulletin->GPC['replacetext']) . "')
		");

		$style = $db->query_first("SELECT styleid, title FROM " . TABLE_PREFIX . "style WHERE styleid = " . $vbulletin->GPC['dostyleid']);

		if ($vbulletin->GPC['dostyleid'] == -1)
		{
			$title = $vbphrase['master_style'];
		}
		else if ($vbulletin->GPC['dostyleid'] == -2)
		{
			$title = $vbphrase['mobile_master_style'];
		}
		else
		{
			$title = $style['title'];
		}		
		print_rebuild_style($vbulletin->GPC['dostyleid'], $title, 0, 0, 1, 0);
		print_cp_redirect("replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=modify", 1);
	}
}

// *********************** add *********************
if ($_REQUEST['do'] == 'add')
{
	print_form_header('replacement', 'insert');
	print_table_header($vbphrase['add_new_replacement_variable']);
	print_style_chooser_row('dostyleid', $vbulletin->GPC['dostyleid'], $vbphrase['master_style'], $vbphrase['style'], ($vbulletin->debug == 1 ? 1 : 0));
	print_input_row("$vbphrase[search_for_text] <dfn>($vbphrase[case_insensitive])</dfn>", 'findtext', '');
	print_textarea_row($vbphrase['replace_with_text'], 'replacetext', '', 5, 50);
	print_submit_row($vbphrase['save']);

}

// *********************** modify *********************
if ($_REQUEST['do'] == 'modify')
{
	// ###################### Start displayreplacements #######################
	function print_replacements($parentid = -1, $indent = "\t", $skipmaster = false)
	{
		global $vbulletin, $vbphrase;
		static $stylecache, $donecache;

		if (($parentid == -1 OR $parentid == -2) AND !$skipmaster)
		{
			if ($vbulletin->debug)
			{
				$title = ($parentid == -1) ? $vbphrase['master_style'] : $vbphrase['mobile_master_style'];
			}
			else
			{
				$title = ($parentid == -1) ? $vbphrase['standard_styles'] : $vbphrase['mobile_styles'];
			}
			echo "<ul class=\"lsq\">\n";
			echo "\t<li><b>{$title}</b>";;
			if ($vbulletin->debug)
			{
				echo construct_link_code($vbphrase['add_new_replacement_variable'], "replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=add&amp;dostyleid={$parentid}") . "\n";
			}
			echo "\t\t<ul class=\"ldi\">\n";
			if ($vbulletin->debug)
			{
				$templates = $vbulletin->db->query_read("
					SELECT templateid, title
					FROM " . TABLE_PREFIX . "template
					WHERE templatetype = 'replacement'
						AND styleid = {$parentid}
				");
				if ($vbulletin->db->num_rows($templates))
				{
					while ($template = $vbulletin->db->fetch_array($templates))
					{
						echo "\t\t<li>" . htmlspecialchars_uni($template['title']) . construct_link_code($vbphrase['edit'], "replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;dostyleid={$parentid}&amp;templateid=$template[templateid]").construct_link_code($vbphrase['delete'], "replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=remove&amp;dostyleid={$parentid}&amp;templateid=$template[templateid]") . "\n";
					}
				}
				else
				{
					echo "\t\t\t<li>" . $vbphrase['no_replacements_defined'] . "</li>\n";
				}
			}
			else
			{
				
			}
			echo "\t\t</ul><br />\n";
			print_replacements($parentid, "$indent\t", true);
			echo "\t</li>\n</ul>\n<hr size=\"1\" />\n";
			return;
		}

		// initialise the style cache if not already created
		if (empty($stylecache))
		{
			$styles = $vbulletin->db->query_read("
				SELECT *
				FROM " . TABLE_PREFIX . "style
				ORDER BY parentid, displayorder
			");
			while ($style = $vbulletin->db->fetch_array($styles))
			{
				$stylecache["$style[parentid]"]["$style[displayorder]"]["$style[styleid]"] = $style;
			}
		}

		// initialise the 'donecache' if not already created
		if (empty($donecache))
		{
			$donecache = array();
		}

		// check to see if this style actually exists / has children
		if (!isset($stylecache["$parentid"]))
		{
			return;
		}

		foreach ($stylecache["$parentid"] AS $holder)
		{
			echo "$indent<ul class=\"lsq\">\n";
			foreach ($holder AS $styleid => $style)
			{
				echo "$indent<li><b>$style[title]</b>" . construct_link_code($vbphrase['add_new_replacement_variable'], "replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=add&amp;dostyleid=$styleid") . "\n";
				echo "\t$indent<ul class=\"ldi\">\n";
				$templateids = implode(',', unserialize($style['templatelist']));
				$templates = $vbulletin->db->query_read("SELECT templateid, title, styleid, template FROM " . TABLE_PREFIX . "template WHERE templatetype = 'replacement' AND templateid IN($templateids) ORDER BY title");
				if ($vbulletin->db->num_rows($templates))
				{
					while ($template = $vbulletin->db->fetch_array($templates))
					{
						if (in_array($template['templateid'], $donecache))
						{
							echo "\t\t$indent<li class=\"col-i\">" . htmlspecialchars_uni($template['title']) . construct_link_code($vbphrase['customize'], "replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;dostyleid=$styleid&amp;templateid=$template[templateid]") . "</li>\n";
						}
						else if ($template['styleid'] != -1 AND $template['styleid'] != -2)
						{
							echo "\t\t$indent<li class=\"col-c\">" . htmlspecialchars_uni($template['title']) . construct_link_code($vbphrase['edit'], "replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;dostyleid=$styleid&amp;templateid=$template[templateid]") . construct_link_code($vbphrase['delete'], "replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=remove&amp;dostyleid=$styleid&amp;templateid=$template[templateid]") . "</li>\n";
							$donecache[] = $template['templateid'];
						}
						else
						{
							echo "\t\t$indent<li class=\"col-g\">" . htmlspecialchars_uni($template['title']) . construct_link_code($vbphrase['customize'], "replacement.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;dostyleid=$styleid&amp;templateid=$template[templateid]") . "</li>\n";
						}
					}
				}
				else
				{
					echo "\t\t$indent<li>" . $vbphrase['no_replacements_defined'] . "</li>\n";
				}

				echo "$indent\t</ul><br />\n";
				print_replacements($styleid, "$indent\t", true);
				echo "$indent</li>\n";
			}
			echo "$indent</ul>\n";
		}
	}

	print_form_header('', '');
	print_table_header($vbphrase['color_key']);
	print_description_row('
	<div class="darkbg" style="border: 2px inset;"><ul class="darkbg">
		<li class="col-g">' . $vbphrase['replacement_variable_is_unchanged_from_the_default_style'] . '</li>
		<li class="col-i">' . $vbphrase['replacement_variable_is_inherited_from_a_parent_style'] . '</li>
		<li class="col-c">' . $vbphrase['replacement_variable_is_customized_in_this_style'] . '</li>
	</ul></div>
	');
	print_table_footer();

	echo "<center>\n";
	echo "<div class=\"tborder\" style=\"width: 89%\">";
	echo "<div class=\"tcat\" style=\"padding:4px\" align=\"center\"><b>$vbphrase[replacement_variables]</b></div>\n";
	echo "<div class=\"alt1\" style=\"padding: 8px\">";
	echo "<div class=\"darkbg\" style=\"padding: 4px; border: 2px inset; text-align: " . vB_Template_Runtime::fetchStyleVar('left') . "\">\n";

	print_replacements(-1);
	print_replacements(-2);

	echo "</div></div></div>\n</center>\n";

}

unset($DEVDEBUG);
print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 58539 $
|| ####################################################################
\*======================================================================*/
?>