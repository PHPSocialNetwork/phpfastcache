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

abstract class vB_ActivityStream_View_Perm_Cms_Base extends vB_ActivityStream_View_Perm_Base
{
	protected function fetchCanViewCmsArticle($nodeid)
	{
		// The process query handles the infernal CMS
		if (!($node = $this->content['cms_node'][$nodeid]))
		{
			return false;
		}

		if ($node['publishdate'] > TIMENOW OR !$node['published'])
		{
			return false;
		}

		$canview = vBCMS_Permissions::canView($node['nodeid']);

		return $canview;
	}

	protected function fetchCanViewCmsComment($postid)
	{
		if (
			!($post = $this->content['cms_post'][$postid])
				OR
			!($node = $this->content['cms_node'][$post['nodeid']])
				OR
			!($this->fetchCanViewCmsArticle($post['nodeid']))
				OR
			!($node['comments_enabled'])
		)
		{
			return false;
		}

		$forumid = $post['forumid'];
		$canviewothers = vB::$vbulletin->userinfo['forumpermissions']["$forumid"] & vB::$vbulletin->bf_ugp_forumpermissions['canviewothers'];
		$canviewthreads = vB::$vbulletin->userinfo['forumpermissions']["$forumid"] & vB::$vbulletin->bf_ugp_forumpermissions['canviewthreads'];
		$threadviewable = (($post['visible'] == 1 OR ($post['visible'] == 0 AND can_moderate($forumid))) AND $canviewthreads);
		if (!$threadviewable OR !$this->fetchCanViewForum($forumid) OR (!$canviewothers AND $post['postuserid'] != vB::$vbulletin->userinfo['userid']))
		{
			return false;
		}

		return true;
	}

	protected function fetchParentUrl($nodeid)
	{
		if (!$this->content['cms_section'])
		{
			$sections = vBCms_ContentManager::getSections();
			foreach ($sections AS $key => $section)
			{
				$this->content['cms_section'][$section['nodeid']] = $section;
			}
		}
		return vB_Route::create('vBCms_Route_Content', $nodeid . ($this->content['cms_section'][$nodeid]['url'] == '' ? '' : '-' . $this->content['cms_section'][$nodeid]['url'] ))->getCurrentURL();
	}

	protected function fetchParentTitle($nodeid)
	{
		if (!$this->content['cms_section'])
		{
			$sections = vBCms_ContentManager::getSections();
			foreach ($sections AS $key => $section)
			{
				$this->content['cms_section'][$section['nodeid']] = $section;
			}
		}
		return $this->content['cms_section'][$nodeid]['title'];
	}

	protected function fetchCanViewForum($forumid)
	{
		return (vB::$vbulletin->userinfo['forumpermissions']["$forumid"] & vB::$vbulletin->bf_ugp_forumpermissions['canview'] AND verify_forum_password($forumid, vB::$vbulletin->forumcache["$forumid"]['password'], false));
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 57655 $
|| ####################################################################
\*======================================================================*/
