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

/**
* Order Categories
*
* @param	integer	Userid
* @param	bool		Force cache to be rebuilt, ignoring copy that may already exist
* @param	bool		Include admin categories when userid > 0
*
* @return	void
*/
function fetch_ordered_categories($userid = 0, $force = false, $admin = true)
{
	global $vbulletin, $vbphrase;

	if (isset($vbulletin->vbblog['categorycache']["$userid"]) AND !$force)
	{
		return;
	}

	$userids = array();
	if ($userid)
	{
		$userids[] = $userid;
	}
	if ($userid == 0 OR $admin)
	{
		$userids[] = 0;
	}

	$vbulletin->vbblog['categorycache']["$userid"] = array();
	$vbulletin->vbblog['icategorycache']["$userid"] = array();
	$vbulletin->vbblog['categorycount']["$userid"] = 0;

	$categorydata = array();

	$cats = $vbulletin->db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "blog_category
		WHERE userid IN(" . implode(", ", $userids) . ")
		ORDER BY userid, displayorder
	");

	while ($cat = $vbulletin->db->fetch_array($cats))
	{
		if (!$cat['userid'])	// Global category, translations from from $vbphrase
		{
			if ($vbphrase['category' . $cat['blogcategoryid'] . '_title'])
			{
				$cat['title'] = $vbphrase['category' . $cat['blogcategoryid'] . '_title'];
			}
			if ($vbphrase['category' . $cat['blogcategoryid'] . '_desc'])
			{
				$cat['description'] = $vbphrase['category' . $cat['blogcategoryid'] . '_desc'];
			}
		}
		$vbulletin->vbblog['icategorycache']["$userid"]["$cat[parentid]"]["$cat[blogcategoryid]"] = $cat['blogcategoryid'];
		$categorydata["$cat[blogcategoryid]"] = $cat;
	}

	$vbulletin->vbblog['categoryorder']["$userid"] = array();
	fetch_category_order($userid);

	foreach ($vbulletin->vbblog['categoryorder']["$userid"] AS $blogcategoryid => $depth)
	{
		$vbulletin->vbblog['categorycache']["$userid"]["$blogcategoryid"] = $categorydata["$blogcategoryid"];
		$vbulletin->vbblog['categorycache']["$userid"]["$blogcategoryid"]['depth'] = $depth;
		if ($categorydata["$blogcategoryid"]['userid'])
		{
			$vbulletin->vbblog['categorycount']["$userid"]++;
		}
	}
}

/**
* Recursive function to build category order
*
* @param	integer	Userid
* @param	integer	Initial parent forum ID to use
* @param	integer	Initial depth of categories
*
* @return	void
*/
function fetch_category_order($userid, $parentid = 0, $depth = 0)
{
	global $vbulletin;

	if (is_array($vbulletin->vbblog['icategorycache']["$userid"]["$parentid"]))
	{
		foreach ($vbulletin->vbblog['icategorycache']["$userid"]["$parentid"] AS $blogcategoryid)
		{
			$vbulletin->vbblog['categoryorder']["$userid"]["$blogcategoryid"] = $depth;
			fetch_category_order($userid, $blogcategoryid, $depth + 1);
		}
	}
}

/**
* Function to output checkbox bits
*
* @param	array	categories
* @param	integer	User
* @param	string	Global or Local categories
*
* @return	void
*/
function construct_category_checkbox(&$categories, $userinfo, $type = 'global')
{
	global $vbulletin, $vbphrase;

	if (!$userinfo['permissions'])
	{
		cache_permissions($userinfo, false);
	}
	if (!isset($vbulletin->vbblog['categorycache']["$userinfo[userid]"]))
	{
		fetch_ordered_categories($userinfo['userid']);
	}

	if (empty($vbulletin->vbblog['categorycache']["$userinfo[userid]"]))
	{
		return;
	}

	if ($userinfo['userid'] != $vbulletin->userinfo['userid'])
	{
		$cantusecats = array_unique(array_merge($userinfo['blogcategorypermissions']['cantpost'], $vbulletin->userinfo['blogcategorypermissions']['cantpost'], $userinfo['blogcategorypermissions']['cantview'], $vbulletin->userinfo['blogcategorypermissions']['cantview']));
	}
	else
	{
		$cantusecats = array_unique(array_merge($userinfo['blogcategorypermissions']['cantpost'], $userinfo['blogcategorypermissions']['cantview']));
	}

	$prevdepth = $beenhere = 0;
	foreach ($vbulletin->vbblog['categorycache']["$userinfo[userid]"] AS $blogcategoryid => $category)
	{
		if (!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancreatecategory']) AND $category['userid'])
		{
			continue;
		}
		else if (in_array($blogcategoryid, $cantusecats))
		{
			continue;
		}
		else if
		(
			($type == 'global' AND $category['userid'] != 0)
				OR
			($type == 'local' AND $category['userid'] == 0)
		)
		{
			continue;
		}


		$show['ul'] = false;

		if (is_array($categories))
		{
			$checked = (in_array($blogcategoryid, $categories)) ? 'checked="checked"' : '';
		}

		if ($category['depth'] == $prevdepth AND $beenhere)
		{
			$jumpcategorybits .= '</li>';
		}
		else if ($category['depth'] > $prevdepth)
		{
			// Need an UL
			$show['ul'] = true;
		}
		else if ($category['depth'] < $prevdepth)
		{
			for ($x = ($prevdepth - $category['depth']); $x > 0; $x--)
			{
				$jumpcategorybits .= '</li></ul>';
			}
			$jumpcategorybits .= '</li>';
		}

		$templater = vB_Template::create('blog_entry_editor_category');
			$templater->register('blogcategoryid', $blogcategoryid);
			$templater->register('category', $category);
			$templater->register('checked', $checked);
		$jumpcategorybits .= $templater->render();

		$prevdepth = $category['depth'];
		$beenhere = true;
	}

	if ($jumpcategorybits)
	{
		for ($x = $prevdepth; $x > 0; $x--)
		{
			$jumpcategorybits .= '</li></ul>';
		}
		$jumpcategorybits .= '</li>';
	}

	return $jumpcategorybits;
}

/**
* Function to output select bits
*
* @param integer	The category parent id to select by default
* @param integer	Userid
*
* @return	void
*/
function construct_category_select($parentid = 0, $userid = 0)
{
	global $vbulletin;

	if (!intval($userid))
	{
		$userid = $vbulletin->userinfo['userid'];
	}

	if (!isset($vbulletin->vbblog['categorycache']["$userid"]))
	{
		fetch_ordered_categories($userid);
	}

	if (empty($vbulletin->vbblog['categorycache']["$userid"]))
	{
		return;
	}

	foreach ($vbulletin->vbblog['categorycache']["$userid"] AS $blogcategoryid => $category)
	{
		if ($category['userid'] != $userid)
		{
			continue;
		}
		$optionvalue = $blogcategoryid;
		$optiontitle = $category[title];
		$optionclass = 'd' . ($category['depth'] > 4) ? 4 : $category['depth'];
		$optionselected = ($blogcategoryid == $parentid) ? 'selected="selected"' : '';

		$jumpcategorybits .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
	}

	return $jumpcategorybits;
}

/**
* Function to output select bits
*
* @param integer	Userid
*
* @return	void
*/
function build_category_genealogy($userid)
{
	global $vbulletin;

	fetch_ordered_categories($userid, true);

	// build parent/child lists
	foreach ($vbulletin->vbblog['categorycache']["$userid"] AS $blogcategoryid => $category)
	{
		// parent list
		$i = 0;
		$curid = $blogcategoryid;

		$vbulletin->vbblog['categorycache']["$userid"]["$blogcategoryid"]['parentlist'] = '';

		while ($curid != 0 AND $i++ < 1000)
		{
			if ($curid)
			{
				$vbulletin->vbblog['categorycache']["$userid"]["$blogcategoryid"]['parentlist'] .= (!empty($vbulletin->vbblog['categorycache']["$userid"]["$blogcategoryid"]['parentlist']) ? ',' : '') . $curid;
				$curid = $vbulletin->vbblog['categorycache']["$userid"]["$curid"]['parentid'];
			}
			else
			{
				global $vbphrase;
				if (!isset($vbphrase['invalid_category_parenting']))
				{
					$vbphrase['invalid_category_parenting'] = 'Invalid category parenting setup. Contact vBulletin support.';
				}
				trigger_error($vbphrase['invalid_category_parenting'], E_USER_ERROR);
			}
		}

		// child list
		$vbulletin->vbblog['categorycache']["$userid"]["$blogcategoryid"]['childlist'] = $blogcategoryid;
		fetch_category_child_list($blogcategoryid, $blogcategoryid, $userid);
	}

	$parentsql = '';
	$childsql = '';
	foreach ($vbulletin->vbblog['categorycache']["$userid"] AS $blogcategoryid => $category)
	{
		$parentsql .= "	WHEN $blogcategoryid THEN '$category[parentlist]'
		";
		$childsql .= "	WHEN $blogcategoryid THEN '$category[childlist]'
		";
	}

	if (!empty($vbulletin->vbblog['categorycache']["$userid"]))
	{
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "blog_category SET
				parentlist = CASE blogcategoryid
					$parentsql
					ELSE parentlist
				END,
				childlist = CASE blogcategoryid
					$childsql
					ELSE childlist
				END
			WHERE userid = $userid
		");
	}
}

/**
* Recursive function to populate categorycache with correct child list fields
*
* @param	integer		Category ID to be updated
* @param	integer		Parent forum ID
* @param	interger	Userid
*
* @return	void
*/
function fetch_category_child_list($maincategoryid, $parentid, $userid)
{
	global $vbulletin;

	if (is_array($vbulletin->vbblog['icategorycache']["$userid"]["$parentid"]))
	{
		foreach ($vbulletin->vbblog['icategorycache']["$userid"]["$parentid"] AS $blogcategoryid => $categoryparentid)
		{
			$vbulletin->vbblog['categorycache']["$userid"]["$maincategoryid"]['childlist'] .= ',' . $blogcategoryid;
			fetch_category_child_list($maincategoryid, $blogcategoryid, $userid);
		}
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 27303 $
|| ####################################################################
\*======================================================================*/
?>
