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

class vB_ActivityStream_View_Perm_Blog_Comment extends vB_ActivityStream_View_Perm_Blog_Base
{
	public function __construct(&$content, &$vbphrase)
	{
		$this->requireExist['vB_ActivityStream_View_Perm_Blog_Entry'] = 1;
		return parent::__construct($content, $vbphrase);
	}

	public function group($activity)
	{
		if (!vB::$vbulletin->products['vbblog'])
		{
			return false;
		}

		if (!$this->content['blogtext'][$activity['contentid']])
		{
			$this->content['blogtextid'][$activity['contentid']] = 1;
		}
	}

	public function process()
	{
		if (!$this->content['blogtextid'] OR !vB::$vbulletin->products['vbblog'])
		{
			return true;
		}

		if (vB::$vbulletin->userinfo['userid'])
		{
			$fields = ", ignored.relationid AS b_ignoreid, buddy.relationid AS b_buddyid";
			$joins = "
				LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = user.userid AND ignored.relationid = " . vB::$vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')
				LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = user.userid AND buddy.relationid = " . vB::$vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')
			";
		}

		$catsql = $this->fetchCategoryPermissions();

		$comments = vB::$db->query_read_slave("
			SELECT
				IF (bu.title <> '', bu.title, user.username) AS b_blog_title, bt.pagetext AS bt_pagetext, blog.postedby_userid, bt.username AS bt_username,
				bt.blogid AS bt_blogid, bt.blogtextid AS bt_blogtextid, bt.title AS bt_title, bt.state AS bt_state, bt.userid AS bt_userid, fp.pagetext AS b_pagetext,
				blog.blogid AS b_blogid, blog.title AS b_title, blog.userid AS b_userid, blog.state AS b_state, blog.options AS b_options, blog.views AS b_views, blog.comments_visible AS b_comments_visible,
				bu.options_member AS b_options_member, bu.options_guest AS b_options_guest, bu.options_buddy AS b_options_buddy, options_ignore AS b_options_ignore, bu.memberids AS b_memberids, bu.memberblogids AS b_memberblogids,
				user.username AS b_username, IF(displaygroupid=0, user.usergroupid, displaygroupid) AS b_displaygroupid, user.infractiongroupid AS b_infractiongroupid, user.usergroupid AS b_usergroupid, user.membergroupids AS b_membergroupids
				$fields
			FROM " . TABLE_PREFIX . "blog_text AS bt
			INNER JOIN " . TABLE_PREFIX . "blog AS blog ON (blog.blogid = bt.blogid)
			INNER JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog.userid)
			INNER JOIN " . TABLE_PREFIX . "blog_text AS fp ON (fp.blogtextid = blog.firstblogtextid)
			$joins
			{$catsql['joinsql']}
			WHERE
				bt.blogtextid IN (" . implode(",", array_keys($this->content['blogtextid'])) . ")
					AND
				blog.pending = 0
				{$catsql['wheresql']}
		");
		while ($comment = vB::$db->fetch_array($comments))
		{
			$this->content['blogtext'][$comment['blogtextid']] = $comment;

			unset($this->content['blogid'][$comment['bt_blogid']]);
			$this->content['blogtext'][$comment['bt_blogtextid']] = $this->parse_array($comment, 'bt_');
			$this->content['userid'][$comment['bt_userid']] = 1;
			if (!$this->content['blog'][$comment['b_blogid']])
			{
				$this->content['blog'][$comment['b_blogid']] = $this->parse_array($comment, 'b_');
				cache_permissions($this->content['blog'][$comment['b_blogid']], false);
				$this->content['userid'][$comment['b_userid']] = 1;
				$this->content['userid'][$comment['postedby_userid']] = 1;
			}
		}

		$this->content['blogtextid'] = array();
	}

	public function fetchCanView($record)
	{
		$this->processUsers();
		return $this->fetchCanViewBlogComment($record['contentid']);
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
		$blogtextinfo =& $this->content['blogtext'][$activity['contentid']];
		$bloginfo =& $this->content['blog'][$blogtextinfo['blogid']];

		$activity['postdate'] = vbdate(vB::$vbulletin->options['dateformat'], $activity['dateline'], true);
		$activity['posttime'] = vbdate(vB::$vbulletin->options['timeformat'], $activity['dateline']);

		$preview = strip_quotes($blogtextinfo['pagetext']);
		$blogtextinfo['preview'] = htmlspecialchars_uni(fetch_censored_text(
			fetch_trimmed_title(strip_bbcode($preview, false, true, true, true),
				vb::$vbulletin->options['as_snippet'])
		));
		$userinfo = $this->fetchUser($activity['userid'], $blogtextinfo['username']);

		if ($fetchphrase)
		{
			if ($userinfo['userid'])
			{
				$phrase = construct_phrase($this->vbphrase['x_commented_on_blog_entry_y_in_z'], fetch_seo_url('member', $userinfo), $userinfo['username'], fetch_seo_url('entry', $bloginfo), $bloginfo['title'], fetch_seo_url('blog', $bloginfo), $bloginfo['blog_title']);
			}
			else
			{
				$phrase = construct_phrase($this->vbphrase['guest_x_commented_on_blog_entry_y_in_z'], $userinfo['username'], fetch_seo_url('entry', $bloginfo), $bloginfo['title'], fetch_seo_url('blog', $bloginfo), $bloginfo['blog_title']);
			}
			return array(
				'phrase' => $phrase,
				'userinfo' => $userinfo,
				'activity' => $activity,
			);
		}
		else
		{
			$templater = vB_Template::create($templatename);
				$templater->register('userinfo', $userinfo);
				$templater->register('activity', $activity);
				$templater->register('blogtextinfo', $blogtextinfo);
				$templater->register('bloginfo', $bloginfo);
				$templater->register('pageinfo', array('bt' => $blogtextinfo['blogtextid']));
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