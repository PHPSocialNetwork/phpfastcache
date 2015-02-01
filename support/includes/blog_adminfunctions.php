<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Blog 4.2.1 - Licence Number VBF02D260D
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

// ###################### Start display category permissions #######################
function print_categories($permscache, $permissions, $inheritance = array(), $parentid = 0, $indent = '	')
{
	global $vbulletin, $vbphrase;

	if (empty($vbulletin->vbblog['icategorycache']["0"]["$parentid"]))
	{
		return;
	}

	foreach ($vbulletin->vbblog['icategorycache']["0"]["$parentid"] AS $blogcategoryid)
	{
		echo "$indent<ul class=\"lsq\">\n";

		// get current forum info
		$category =& $vbulletin->vbblog['categorycache']["0"]["$blogcategoryid"];

		// make a copy of the current permissions set up
		$perms = $permissions;

		// make a copy of the inheritance set up
		$inherit = $inheritance;

		echo "<li><b><a name=\"category$blogcategoryid\" href=\"blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=modifycat&amp;blogcategoryid=$blogcategoryid\">$category[title]</a></b>";
		echo "$indent\t<ul class=\"usergroups\">\n";

		foreach($vbulletin->usergroupcache AS $usergroupid => $usergroup)
		{
			if ($inherit["$usergroupid"] == 'col-c')
			{
				$inherit["$usergroupid"] = 'col-i';
			}

			// if there is a custom permission for the current usergroup, use it
			if (isset($permscache["$blogcategoryid"]["$usergroupid"]))
			{
				$inherit["$usergroupid"] = 'col-c';
				$perms["$usergroupid"] = $permscache["$blogcategoryid"]["$usergroupid"]['categorypermissions'];
				$cplink = 'categorypermissionid=' . $permscache["$blogcategoryid"]["$usergroupid"]['categorypermissionid'];
			}
			else
			{
				$cplink = "blogcategoryid=$blogcategoryid&amp;usergroupid=$usergroupid";
			}

			// work out display style
			$liStyle = '';
			if (isset($inherit["$usergroupid"]))
			{
				$liStyle = " class=\"$inherit[$usergroupid]\"";
			}
			if (!($perms["$usergroupid"] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewcategory']))
			{
				$liStyle .= " style=\"list-style:circle\"";
			}
			echo "$indent\t<li$liStyle>" . construct_link_code($vbphrase['edit'], "blog_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=editcp&amp;$cplink") . $usergroup['title'] . "</li>\n";
		}

		echo "$indent\t</ul><br />\n";

		print_categories($permscache, $perms, $inherit, $blogcategoryid, "$indent	");

		echo "$indent</li>\n";
		unset($inherit);
		echo "$indent</ul>\n";

		if ($category['parentid'] == 0)
		{
			echo "<hr size=\"1\" />\n";
		}
	}
}

function build_category_permissions()
{
	global $vbulletin;

	require_once(DIR . '/includes/blog_functions_category.php');
	fetch_ordered_categories(0);

	// query category permissions
	$categorypermissions = $vbulletin->db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "blog_categorypermission
	");

	$permcache = array();
	while ($cperm = $vbulletin->db->fetch_array($categorypermissions))
	{
			$permcache["$cperm[blogcategoryid]"]["$cperm[usergroupid]"] = intval($cperm['categorypermissions']);
	}

	$grouppermissions = array();
	$usergroups = $vbulletin->db->query_read("SELECT vbblog_general_permissions, usergroupid FROM " . TABLE_PREFIX . "usergroup ORDER BY usergroupid");
	while ($usergroup = $vbulletin->db->fetch_array($usergroups))
	{
		$grouppermissions["$usergroup[usergroupid]"] = $usergroup['vbblog_general_permissions'];
	}

	$category = $vbulletin->vbblog['categorycache']["0"];
	cache_category_permissions($category, $grouppermissions, $permcache);

	build_datastore('blogcategorycache', serialize($category), 1);

	// Update blog stats since category permission affects the latest entry
	build_blog_stats();
}

function cache_category_permissions(&$category, $permissions, $permcache, $parentid = 0)
{
	global $vbulletin;

	// abort if no child categories found
	if (empty($vbulletin->vbblog['icategorycache']["0"]["$parentid"]))
	{
		return;
	}

	// run through each child forum
	foreach($vbulletin->vbblog['icategorycache']["0"]["$parentid"] AS $blogcategoryid)
	{
		// make a copy of the current permissions set up
		$perms = $permissions;

		// run through each usergroup
		foreach($permissions AS $usergroupid => $null)
		{
			// if there is a custom permission for the current usergroup, use it
			if (isset($permcache["$blogcategoryid"]["$usergroupid"]))
			{
				$perms["$usergroupid"] = $permcache["$blogcategoryid"]["$usergroupid"];
			}

			// populate the current row of the categorycache permissions
			$category["$blogcategoryid"]['permissions']["$usergroupid"] = intval($perms["$usergroupid"]);
		}
		// recurse to child forums
		cache_category_permissions($category, $perms, $permcache, $blogcategoryid);
	}
}

/**
* Build the featured entries settings datastore
*
* @return	void
*/
function build_featured_entry_datastore()
{
	global $vbulletin;

	$entries = $vbulletin->db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "blog_featured
		ORDER BY displayorder
	");

	$data = array();
	while ($entry = $vbulletin->db->fetch_array($entries))
	{
		$temp = array(
			'type'   => $entry['type'],
			'bbcode' => $entry['bbcode'],
		);

		if ($entry['type'] == 'specific')
		{
				$temp['blogid'] = $entry['blogid'];
		}
		else
		{
			// Data shared by 'random' and 'latest' entries
			if ($entry['pusergroupid'])
			{
				$temp['pusergroupid'] = $entry['pusergroupid'];
			}
			if ($entry['susergroupid'])
			{
				$temp['susergroupid'] = $entry['susergroupid'];
			}
			if ($entry['userid'])
			{
				$temp['userid'] = $entry['userid'];
			}
			$temp['refresh'] = $entry['refresh'];

			if ($entry['type'] == 'random')
			{
				$temp['timespan'] = $entry['timespan'];
				if ($entry['start'])
				{
					$temp['start'] = $entry['start'];
				}
				if ($entry['end'])
				{
					$temp['end'] = $entry['end'];
				}
			}
		}

		$data["$entry[featureid]"] = $temp;
	}

	build_datastore('blogfeatured_settings', serialize($data), 1);
	build_datastore('blogfeatured_entries', serialize(array()), 1);
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 26255 $
|| ####################################################################
\*======================================================================*/
?>