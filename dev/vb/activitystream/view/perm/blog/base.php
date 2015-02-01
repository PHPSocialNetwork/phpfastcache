<?php

/* ======================================================================*\
  || #################################################################### ||
  || # vBulletin 4.2.1 - Licence Number VBF02D260D
  || # ---------------------------------------------------------------- # ||
  || # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
  || # This file may not be redistributed in whole or significant part. # ||
  || # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
  || # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
  || #################################################################### ||
  \*====================================================================== */

abstract class vB_ActivityStream_View_Perm_Blog_Base extends vB_ActivityStream_View_Perm_Base
{
	public function __construct(&$content, &$vbphrase)
	{
		require_once(DIR . '/includes/blog_functions_shared.php');
		return parent::__construct($content, $vbphrase);
	}

	protected function fetchCanViewBlogEntry($blogid)
	{
		if (!($blogrecord = $this->content['blog'][$blogid]))
		{
			return false;
		}

		if (vB::$vbulletin->userinfo['userid'] == $blogrecord['userid'] AND !(vB::$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & vB::$vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
		{
			return false;
		}

		if (vB::$vbulletin->userinfo['userid'] != $blogrecord['userid'] AND !(vB::$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & vB::$vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
		{
			return false;
		}

		if ($blogrecord['options'] & vB::$vbulletin->bf_misc_vbblogoptions['private'] AND !can_moderate_blog() AND !is_member_of_blog(vB::$vbulletin->userinfo, $blogrecord) AND !$blogrecord['buddyid'])
		{
			return false;
		}

		if ($blogrecord['state'] != 'visible' AND ($blogrecord['state'] != 'moderation' OR (!can_moderate_blog('canmoderateentries') AND !is_member_of_blog(vB::$vbulletin->userinfo, $blogrecord))))
		{
			return false;
		}

		$member = ($blogrecord['options_member'] & vB::$vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] ? 1 : 0);
		$guest = ($blogrecord['options_guest'] & vB::$vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] ? 1 : 0);
		$buddy = ($blogrecord['options_buddy'] & vB::$vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] ? 1 : 0);
		$ignore = ($blogrecord['options_ignore'] & vB::$vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] ? 1 : 0);

		if (
			($blogrecord['buddyid'] AND !$buddy)
				OR
			($blogrecord['ignoreid'] AND !$ignore)
				OR
			(
			(!$member OR !vB::$vbulletin->userinfo['userid'])
				AND
			(!$guest OR vB::$vbulletin->userinfo['userid'])
			)
				AND
			(!$ignore OR !$blogrecord['ignoreid'])
				AND
			(!$buddy OR !$blogrecord['buddyid'])
				AND
			$blogrecord['userid'] != vB::$vbulletin->userinfo['userid']
				AND
			!can_moderate_blog()
				AND
			!is_member_of_blog(vB::$vbulletin->userinfo, $blogrecord)
		)
		{
			return false;
		}

		return true;
	}

	protected function fetchCanViewBlogComment($blogtextid)
	{
		if (!($blogtextrecord = $this->content['blogtext'][$blogtextid]))
		{
			return false;
		}
		$blogrecord = $this->content['blog'][$blogtextrecord['blogid']];

		$state = array('visible');
		if (can_moderate_blog('canmoderatecomments') OR is_member_of_blog(vB::$vbulletin->userinfo, $blogrecord))
		{
			$state[] = 'moderation';
		}

		if (!in_array($blogtextrecord['state'], $state))
		{
			return false;
		}

		return $this->fetchCanViewBlogEntry($blogtextrecord['blogid']);
	}

	/* Fetch blog admin cat permissions
	 *
	 * @return	array	SQL to use in blog query
	 */
	protected function fetchCategoryPermissions()
	{
		if (!vB::$vbulletin->userinfo['blogcategorypermissions'])
		{
			require_once(DIR . '/includes/blog_functions_shared.php');
			prepare_blog_category_permissions(vB::$vbulletin->userinfo, true);
		}

		$return = array();
		if (!empty(vB::$vbulletin->userinfo['blogcategorypermissions']['cantview']))
		{
			$return['joinsql'] = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", vB::$vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
			if (vB::$vbulletin->userinfo['userid'])
			{
				$return['wheresql'] = "AND (cu.blogcategoryid IS NULL OR blog.userid = " . vB::$vbulletin->userinfo['userid'] . ")";
			}
			else
			{
				$return['wheresql'] = "AND cu.blogcategoryid IS NULL";
			}
		}
		return $return;
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 57655 $
|| ####################################################################
\*======================================================================*/