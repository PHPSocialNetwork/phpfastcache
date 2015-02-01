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

class vB_ActivityStream_View_Perm_Forum_Post extends vB_ActivityStream_View_Perm_Forum_Base
{
	public function __construct(&$content, &$vbphrase)
	{
		$this->requireExist['vB_ActivityStream_View_Perm_Forum_Thread'] = 1;
		return parent::__construct($content, $vbphrase);
	}

	public function group($activity)
	{
		if (!$this->content['post'][$activity['contentid']])
		{
			$this->content['postid'][$activity['contentid']] = 1;
		}
	}

	public function process()
	{
		if (!$this->content['postid'])
		{
			return;
		}

		$posts = vB::$db->query_read_slave("
			SELECT
				p.postid AS p_postid, p.threadid AS p_threadid, p.title AS p_title, p.visible AS p_visible, p.userid AS p_userid, p.pagetext AS p_pagetext, p.username AS p_username,
				t.threadid AS t_threadid, t.title AS t_title, t.forumid AS t_forumid, t.pollid AS t_pollid, t.open AS t_open, t.postusername AS t_postusername,
				t.views AS t_views, t.visible AS t_visible, t.postuserid AS t_postuserid, t.postuserid AS t_userid, t.replycount AS t_replycount, t.prefixid AS t_prefixid,
				fp.pagetext AS t_pagetext
			FROM " . TABLE_PREFIX . "post AS p
			INNER JOIN " . TABLE_PREFIX . "thread AS t ON (p.threadid = t.threadid)
			INNER JOIN " . TABLE_PREFIX . "post AS fp ON (t.firstpostid = fp.postid)
			WHERE
				p.postid IN (" . implode(",", array_keys($this->content['postid'])) . ")
					AND
				p.visible <> 2
					AND
				t.visible <> 2
		");
		while ($post = vB::$db->fetch_array($posts))
		{
			unset($this->content['threadid'][$post['p_threadid']]);			
			$this->content['post'][$post['p_postid']] = $this->parse_array($post, 'p_');
			$this->content['userid'][$post['p_userid']] = 1;
			if (!$this->content['thread'][$post['t_threadid']])
			{
				$this->content['thread'][$post['t_threadid']] = $this->parse_array($post, 't_');
				$this->content['userid'][$post['t_postuserid']] = 1;
			}
		}

		$this->content['postid'] = array();
	}

	public function fetchCanView($record)
	{
		$this->processUsers();
		return $this->fetchCanViewPost($record['contentid']);
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
		global $show;

		$postinfo =& $this->content['post'][$activity['contentid']];
		$threadinfo =& $this->content['thread'][$postinfo['threadid']];
		$foruminfo =& vB::$vbulletin->forumcache[$threadinfo['forumid']];
		$threadinfo['prefix_plain_html'] = htmlspecialchars_uni($this->vbphrase["prefix_{$threadinfo['prefixid']}_title_plain"]);
		$threadinfo['prefix_rich'] = $this->vbphrase["prefix_{$threadinfo['prefixid']}_title_rich"];

		$activity['postdate'] = vbdate(vB::$vbulletin->options['dateformat'], $activity['dateline'], true);
		$activity['posttime'] = vbdate(vB::$vbulletin->options['timeformat'], $activity['dateline']);

		$preview = strip_quotes($postinfo['pagetext']);
		$postinfo['preview'] = htmlspecialchars_uni(fetch_censored_text(
			fetch_trimmed_title(strip_bbcode($preview, false, true, true, true),
				vb::$vbulletin->options['as_snippet'])
		));

		$forumperms = fetch_permissions($threadinfo['forumid']);
		$show['threadcontent'] = ($forumperms & vB::$vbulletin->bf_ugp_forumpermissions['canviewthreads']) ? true : false;
		$userinfo = $this->fetchUser($activity['userid'], $postinfo['username']);

		if ($fetchphrase)
		{
			if ($threadinfo['pollid'])
			{
				if ($userinfo['userid'])
				{
					$phrase = construct_phrase($this->vbphrase['x_replied_to_a_poll_y_in_z'], fetch_seo_url('member', $userinfo), $userinfo['username'], fetch_seo_url('thread', $threadinfo), $threadinfo['prefix_rich'], $threadinfo['title'], fetch_seo_url('forum', $foruminfo), $foruminfo['title']);
				}
				else
				{
					$phrase = construct_phrase($this->vbphrase['guest_x_replied_to_a_poll_y_in_z'], $userinfo['username'], fetch_seo_url('thread', $threadinfo), $threadinfo['prefix_rich'], $threadinfo['title'], fetch_seo_url('forum', $foruminfo), $foruminfo['title']);
				}
			}
			else
			{
				if ($userinfo['userid'])
				{
					$phrase = construct_phrase($this->vbphrase['x_replied_to_a_thread_y_in_z'], fetch_seo_url('member', $userinfo), $userinfo['username'], fetch_seo_url('thread', $threadinfo), $threadinfo['prefix_rich'], $threadinfo['title'], fetch_seo_url('forum', $foruminfo), $foruminfo['title']);
				}
				else
				{
					$phrase = construct_phrase($this->vbphrase['guest_x_replied_to_a_thread_y_in_z'], $userinfo['username'], fetch_seo_url('thread', $threadinfo), $threadinfo['prefix_rich'], $threadinfo['title'], fetch_seo_url('forum', $foruminfo), $foruminfo['title']);
				}
			}

			return array(
				'phrase'   => $phrase,
				'userinfo' => $userinfo,
				'activity' => $activity,
			);
		}
		else
		{
			$templater = vB_Template::create($templatename);
				$templater->register('userinfo', $userinfo);
				$templater->register('activity', $activity);
				$templater->register('threadinfo', $threadinfo);
				$templater->register('postinfo', $postinfo);
				$templater->register('pageinfo', array('p' => $postinfo['postid']));
				$templater->register('foruminfo', $foruminfo);
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