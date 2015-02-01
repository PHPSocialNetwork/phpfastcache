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

class vB_ActivityStream_View_Perm_Cms_Comment extends vB_ActivityStream_View_Perm_Cms_Base
{
	public function __construct(&$content, &$vbphrase)
	{
		$this->requireExist['vB_ActivityStream_View_Perm_Cms_Article'] = 1;
		return parent::__construct($content, $vbphrase);
	}

	public function group($activity)
	{
		if (!vB::$vbulletin->products['vbcms'])
		{
			return;
		}

		if (!$this->content['cms_post'][$activity['contentid']])
		{
			$this->content['cms_postid'][$activity['contentid']] = 1;
		}
	}

	public function process()
	{
		if (!vB::$vbulletin->products['vbcms'])
		{
			return true;
		}

		if (!$this->content['cms_postid'])
		{
			return true;
		}

		$posts = vB::$db->query_read_slave("
			SELECT
				p.pagetext AS p_pagetext, p.postid AS p_postid, p.threadid AS p_threadid, p.title AS p_title, p.visible AS p_visible, p.userid AS p_userid, p.username AS p_username,
				ni.nodeid AS p_nodeid, ni.viewcount AS ni_viewcount, node.nodeid AS ni_nodeid, ni.title AS ni_title, ni.html_title AS ni_html_title,
				node.url AS ni_url, node.comments_enabled AS ni_comments_enabled, node.userid AS ni_userid, node.parentnode AS ni_parentnode,
				a.pagetext AS a_pagetext, a.contentid AS a_contentid, node.nodeid AS a_nodeid, a.contentid AS ni_contentid, thread.replycount AS ni_replycount,
				node.publishdate AS ni_publishdate, node.setpublish AS ni_published, thread.forumid AS p_forumid
			FROM " . TABLE_PREFIX . "post AS p
			INNER JOIN " . TABLE_PREFIX . "cms_nodeinfo AS ni ON (p.threadid = ni.associatedthreadid)
			INNER JOIN " . TABLE_PREFIX . "cms_node AS node ON (ni.nodeid = node.nodeid)
			INNER JOIN " . TABLE_PREFIX . "cms_article AS a ON (node.contentid = a.contentid)
			LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = p.threadid)
			WHERE
				p.postid IN (" . implode(",", array_keys($this->content['cms_postid'])) . ")
					AND
				" . vBCMS_Permissions::getPermissionString() . "
		");
		while ($post = vB::$db->fetch_array($posts))
		{
			unset($this->content['cms_nodeid'][$post['ni_nodeid']]);
			$this->content['cms_post'][$post['p_postid']] = $this->parse_array($post, 'p_');
			$this->content['userid'][$post['p_userid']] = 1;
			if (!$this->content['cms_node'][$post['ni_nodeid']])
			{
				$this->content['cms_node'][$post['ni_nodeid']] = $this->parse_array($post, 'ni_');
				$this->content['cms_article'][$post['a_contentid']] = $this->parse_array($post, 'a_');
				$this->content['userid'][$post['ni_userid']] = 1;
			}
		}

		$this->content['cms_postid'] = array();
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
		$postinfo =& $this->content['cms_post'][$activity['contentid']];
		$nodeinfo =& $this->content['cms_node'][$postinfo['nodeid']];
		$articleinfo =& $this->content['cms_article'][$nodeinfo['contentid']];
		$activity['postdate'] = vbdate(vB::$vbulletin->options['dateformat'], $activity['dateline'], true);
		$activity['posttime'] = vbdate(vB::$vbulletin->options['timeformat'], $activity['dateline']);

		$preview = strip_quotes($postinfo['pagetext']);
		$articleinfo['preview'] = htmlspecialchars_uni(fetch_censored_text(
			fetch_trimmed_title(strip_bbcode($preview, false, true, true, true),
				vb::$vbulletin->options['as_snippet'])
		));

		$articleinfo['fullurl'] = vB_Route::create('vBCms_Route_Content', $nodeinfo['nodeid'] . ($nodeinfo['url'] == '' ? '' : '-' . $nodeinfo['url'] ))->getCurrentURL();
		$nodeinfo['parenturl'] = $this->fetchParentUrl($nodeinfo['parentnode']);
		$nodeinfo['parenttitle'] = $this->fetchParentTitle($nodeinfo['parentnode']);

		$userinfo = $this->fetchUser($activity['userid'], $postinfo['username']);

		if ($fetchphrase)
		{
			if ($userinfo['userid'])
			{
				$phrase =  construct_phrase($this->vbphrase['x_commented_on_an_article_y_in_z'], fetch_seo_url('member', $userinfo), $userinfo['username'], $articleinfo['fullurl'], $nodeinfo['title'], $nodeinfo['parenturl'], $nodeinfo['parenttitle']);
			}
			else
			{
				$phrase =  construct_phrase($this->vbphrase['guest_x_commented_on_an_article_y_in_z'], $userinfo['username'], $articleinfo['fullurl'], $nodeinfo['title'], $nodeinfo['parenturl'], $nodeinfo['parenttitle']);
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
				$templater->register('postinfo', $postinfo);
				$templater->register('activity', $activity);
				$templater->register('nodeinfo', $nodeinfo);
				$templater->register('articleinfo', $articleinfo);
			return $templater->render();
		}
	}

	public function fetchCanView($record)
	{
		$this->processUsers();
		return $this->fetchCanViewCmsComment($record['contentid']);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 57655 $
|| ####################################################################
\*======================================================================*/
