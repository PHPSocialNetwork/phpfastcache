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

class vB_ActivityStream_View_Perm_Blog_Entry extends vB_ActivityStream_View_Perm_Blog_Base
{
	public function __construct(&$content, &$vbphrase)
	{
		$this->requireFirst['vB_ActivityStream_View_Perm_Blog_Comment'] = 1;
		return parent::__construct($content, $vbphrase);
	}

	public function group($activity)
	{
		if (!vB::$vbulletin->products['vbblog'])
		{
			return;
		}

		if (!$this->content['blog'][$activity['contentid']])
		{
			$this->content['blogid'][$activity['contentid']] = 1;
		}
	}

	public function process()
	{
		if (!$this->content['blogid'] OR !vB::$vbulletin->products['vbblog'])
		{
			return true;
		}

		if (vB::$vbulletin->userinfo['userid'])
		{
			$fields = ", ignored.relationid AS ignoreid, buddy.relationid AS buddyid";
			$joins = "
				LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = user.userid AND ignored.relationid = " . vB::$vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')
				LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = user.userid AND buddy.relationid = " . vB::$vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')
			";
		}

		$catsql = $this->fetchCategoryPermissions();

		$blogs = vB::$db->query_read_slave("
			SELECT
				IF (bu.title <> '', bu.title, user.username) AS blog_title,
				blog.blogid, blog.title AS title, blog.userid, blog.state, blog.options, blog.views, blog.comments_visible, blog.postedby_userid,
				bt.pagetext,
				bu.options_member, bu.options_guest, bu.options_buddy, options_ignore, bu.memberids, bu.memberblogids,
				user.username, IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, user.infractiongroupid, user.usergroupid, user.membergroupids
				$fields
			FROM " . TABLE_PREFIX . "blog AS blog
			INNER JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog.userid)
			INNER JOIN " . TABLE_PREFIX . "blog_text AS bt ON (bt.blogtextid = blog.firstblogtextid)
			$joins
			{$catsql['joinsql']}
			WHERE
				blog.blogid IN (" . implode(",", array_keys($this->content['blogid'])) . ")
					AND
				blog.pending = 0
				{$catsql['wheresql']}
		");
		while ($blog = vB::$db->fetch_array($blogs))
		{
			cache_permissions($blog, false);
			$this->content['blog'][$blog['blogid']] = $blog;
			$this->content['userid'][$blog['userid']] = 1;
			$this->content['userid'][$blog['postedby_userid']] = 1;
		}

		$this->content['blogid'] = array();
	}

	public function fetchCanView($record)
	{
		$this->processUsers();
		return $this->fetchCanViewBlogEntry($record['contentid']);
	}

	/*
	 * Register Template
	 *
	 * @param	string	Template Name
	 * @param	array	Activity Record
	 *
	 * @return	string	Template
	 */
	public function fetchTemplate($templatename, $activity, $skipgroup = false, $fetchphrase = false)
	{
		$userinfo =& $this->content['user'][$activity['userid']];
		$bloginfo =& $this->content['blog'][$activity['contentid']];
		$activity['postdate'] = vbdate(vB::$vbulletin->options['dateformat'], $activity['dateline'], true);
		$activity['posttime'] = vbdate(vB::$vbulletin->options['timeformat'], $activity['dateline']);

		$preview = strip_quotes($bloginfo['pagetext']);
		$bloginfo['preview'] = htmlspecialchars_uni(fetch_censored_text(
			fetch_trimmed_title(strip_bbcode($preview, false, true, true, true),
				vb::$vbulletin->options['as_snippet'])
		));

		if ($fetchphrase)
		{
			return array(
				'phrase' => construct_phrase($this->vbphrase['x_created_a_blog_entry_y_in_z'], fetch_seo_url('member', $userinfo), $userinfo['username'], fetch_seo_url('entry', $bloginfo), $bloginfo['title'], fetch_seo_url('blog', $bloginfo), $bloginfo['blog_title']),
				'userinfo' => $userinfo,
				'activity' => $activity,
			);
		}
		else
		{
			$templater = vB_Template::create($templatename);
				$templater->register('userinfo', $userinfo);
				$templater->register('activity', $activity);
				$templater->register('bloginfo', $bloginfo);
			return $templater->render();
		}
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 57655 $
|| ####################################################################
\*======================================================================*/