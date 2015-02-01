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

/**
* Construct HTML for a list of prefix sets in checkboxes.
*
* @param	string	Name of the checkbox input
* @param	integer	Forum ID to use to determine if the boxes are selected
*
* @return	string	HTML (probably for use in print_label_row)
*/
function construct_prefixset_checkboxes($input_name, $forumid = 0)
{
	global $vbulletin, $vbphrase;

	$prefixsets = '';
	$prefixsets_sql = $vbulletin->db->query_read("
		SELECT prefixset.prefixsetid, IF(forumprefixset.prefixsetid IS NULL, 0, 1) AS selected
		FROM " . TABLE_PREFIX . "prefixset AS prefixset
		LEFT JOIN " . TABLE_PREFIX . "forumprefixset AS forumprefixset ON
			(forumprefixset.prefixsetid = prefixset.prefixsetid AND forumprefixset.forumid = " . intval($forumid) . ")
		ORDER BY prefixset.displayorder
	");
	while ($prefixset = $vbulletin->db->fetch_array($prefixsets_sql))
	{
		$prefixsets .= "<div class=\"smallfont\"><label>"
			. "<input type=\"checkbox\" name=\"{$input_name}[]\" value=\"$prefixset[prefixsetid]\" tabindex=\"1\"" . ($prefixset['selected'] ? ' checked="checked"' : '') . " />"
			. htmlspecialchars_uni($vbphrase["prefixset_$prefixset[prefixsetid]_title"]) . "</label></div>";
	}

	return $prefixsets;
}

/**
* Construct a set of option tags for a <select> box consisting of prefixes.
* Note: if you only use one of the no- and any-prefix options, they will each have
* a value of ''; if you use both, any will be '' and none will be '-1'.
*
* @param	integer	if specified, only include prefixes available in a specific forum
* @param	string	The selected value
* @param	boolean	Whether to show a "no prefix" option
* @param	boolean	Whether to show an "any prefix" option
* @param	boolean	Whether to return the no/any options if there are no prefixes available
*
* @return	string	HTML for options
*/
function construct_prefix_options($forumid = 0, $selectedid = '', $show_no_prefix = true, $show_any_prefix = false, $show_if_empty = false)
{
	global $vbulletin, $vbphrase;
	static $prefix_option_cache = array();

	$forumid = intval($forumid);

	if (!isset($prefix_option_cache["$forumid"]))
	{
		$prefixsets = array();
		$prefixsets_sql = $vbulletin->db->query_read("
			SELECT prefixset.*
			FROM " . TABLE_PREFIX . "prefixset AS prefixset
			" . ($forumid ?
				"INNER JOIN " . TABLE_PREFIX . "forumprefixset AS forumprefixset ON
					(forumprefixset.prefixsetid = prefixset.prefixsetid AND forumprefixset.forumid = $forumid)
				" : '') . "
			ORDER BY prefixset.displayorder
		");
		while ($prefixset = $vbulletin->db->fetch_array($prefixsets_sql))
		{
			$phrased_set = htmlspecialchars_uni($vbphrase["prefixset_$prefixset[prefixsetid]_title"]);
			if ($phrased_set)
			{
				$prefixsets["$phrased_set"] = array();
			}
		}

		$prefixes_sql = $vbulletin->db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "prefix
			ORDER BY displayorder
		");
		while ($prefix = $vbulletin->db->fetch_array($prefixes_sql))
		{
			$phrased_set = htmlspecialchars_uni($vbphrase["prefixset_$prefix[prefixsetid]_title"]);
			if (isset($prefixsets["$phrased_set"]))
			{
				$prefixsets["$phrased_set"]["$prefix[prefixid]"] = htmlspecialchars_uni($vbphrase["prefix_$prefix[prefixid]_title_plain"]);
			}
		}

		$prefix_option_cache["$forumid"] = $prefixsets;
	}

	$construct = $prefix_option_cache["$forumid"];
	if (!$show_if_empty AND !$construct)
	{
		return '';
	}

	$beginning = array();
	if ($show_no_prefix AND $show_any_prefix)
	{
		$beginning[''] = $vbphrase['any_prefix_meta'];
		$beginning['-1'] = $vbphrase['no_prefix_meta'];
	}
	else if ($show_no_prefix OR $show_any_prefix)
	{
		$beginning[''] = ($show_no_prefix ? $vbphrase['no_prefix_meta'] : $vbphrase['any_prefix_meta']);
	}

	if (sizeof($beginning) > 0)
	{
		// don't use array merge -- it will renumber
		$construct = $beginning + $construct;
	}

	return construct_select_options($construct, $selectedid);
}

/**
* Builds the prefix cache datastore entry
*/
function build_prefix_datastore()
{
	global $vbulletin;

	$prefixes = array();
	$prefix_sql = $vbulletin->db->query_read("
		SELECT prefix.*, prefixpermission.usergroupid AS restriction
		FROM " . TABLE_PREFIX . "prefix AS prefix
		LEFT JOIN " . TABLE_PREFIX . "prefixpermission AS prefixpermission ON (prefix.prefixid = prefixpermission.prefixid)
		ORDER BY prefix.displayorder
	");
	while ($prefix = $vbulletin->db->fetch_array($prefix_sql))
	{
		if (empty($prefixes["$prefix[prefixsetid]"]["$prefix[prefixid]"]))
		{
			$prefixes["$prefix[prefixsetid]"]["$prefix[prefixid]"] = array(
				'prefixid' => $prefix['prefixid'],
				'restrictions' => array()
			);
		}

		if ($prefix['restriction'])
		{
			$prefixes["$prefix[prefixsetid]"]["$prefix[prefixid]"]['restrictions'][] = $prefix['restriction'];
		}
	}

	$forum_prefixes = array();
	$forum_prefix_sql = $vbulletin->db->query_read("
		SELECT forumprefixset.*
		FROM " . TABLE_PREFIX . "forumprefixset AS forumprefixset
		INNER JOIN " . TABLE_PREFIX . "prefixset AS prefixset ON (prefixset.prefixsetid = forumprefixset.prefixsetid)
		ORDER BY prefixset.displayorder
	");
	while ($forum_prefix = $vbulletin->db->fetch_array($forum_prefix_sql))
	{
		if (isset($prefixes["$forum_prefix[prefixsetid]"]))
		{
			// ensure that there are prefixes in this set before making available
			$forum_prefixes["$forum_prefix[forumid]"]["$forum_prefix[prefixsetid]"] = $prefixes["$forum_prefix[prefixsetid]"];
		}
	}

	build_datastore('prefixcache', serialize($forum_prefixes), 1);
}

/**
* Removes prefixes from threads in certain forums. Useful when a prefix or prefix set
* is no longer available in a forum.
*
* @param	array|string	Array of prefixes (or single one)
* @param	array|integer	Array of forumids (or a single one)
*/
function remove_prefixes_forum($prefixes, $forumids)
{
	global $vbulletin;

	if (!is_array($prefixes))
	{
		$prefixes = array($prefixes);
	}
	$prefixes = array_map(array(&$vbulletin->db, 'escape_string'), $prefixes);

	if (!is_array($forumids))
	{
		$forumids = array($forumids);
	}
	$forumids = array_map('intval', $forumids);

	if (empty($prefixes) OR empty($forumids))
	{
		return;
	}

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "thread SET
			prefixid = ''
		WHERE prefixid IN ('" . implode("', '", $prefixes) . "')
			AND forumid IN (" . implode(',', $forumids) . ")
	");

	// rebuild last post info in these forums
	require_once(DIR . '/includes/functions_databuild.php');
	foreach ($forumids AS $forumid)
	{
		build_forum_counters($forumid);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>