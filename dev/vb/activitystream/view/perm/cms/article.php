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

class vB_ActivityStream_View_Perm_Cms_Article extends vB_ActivityStream_View_Perm_Cms_Base
{
	public function __construct(&$content, &$vbphrase)
	{
		$this->requireFirst['vB_ActivityStream_View_Perm_Cms_Comment'] = 1;
		return parent::__construct($content, $vbphrase);
	}

	public function group($activity)
	{
		if (!vB::$vbulletin->products['vbcms'])
		{
			return;
		}

		if (!$this->content['cms_node'][$activity['contentid']])
		{
			$this->content['cms_nodeid'][$activity['contentid']] = 1;
		}
	}

	public function process()
	{
		if (!vB::$vbulletin->products['vbcms'])
		{
			return true;
		}

		if (!$this->content['cms_nodeid'])
		{
			return true;
		}

		$nodes = vB::$db->query_read_slave("
			SELECT
				node.nodeid AS n_nodeid, node.url AS n_url, node.comments_enabled AS n_comments_enabled, node.userid AS n_userid,
				ni.viewcount AS n_viewcount, ni.title AS n_title, ni.html_title AS n_html_title, a.contentid AS n_contentid,
				a.pagetext AS a_pagetext, a.contentid AS a_contentid, node.nodeid AS a_nodeid, node.parentnode AS n_parentnode,
				thread.replycount AS n_replycount, node.publishdate AS n_publishdate, node.setpublish AS n_published
			FROM " . TABLE_PREFIX . "cms_node AS node
			INNER JOIN " . TABLE_PREFIX . "cms_nodeinfo AS ni ON (node.nodeid = ni.nodeid)
			INNER JOIN " . TABLE_PREFIX . "cms_article AS a ON (node.contentid = a.contentid)
			LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = ni.associatedthreadid)
			WHERE
				node.nodeid IN (" . implode(",", array_keys($this->content['cms_nodeid'])) . ")
					AND
				" . vBCMS_Permissions::getPermissionString() . "
		");
		while ($node = vB::$db->fetch_array($nodes))
		{
			$this->content['cms_node'][$node['n_nodeid']] = $this->parse_array($node, 'n_');
			$this->content['cms_article'][$node['a_contentid']] = $this->parse_array($node, 'a_');
			$this->content['userid'][$node['n_userid']] = 1;
		}

		$this->content['cms_nodeid'] = array();
	}

	public function fetchCanView($record)
	{
		$this->processUsers();
		return $this->fetchCanViewCmsArticle($record['contentid']);
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

		$nodeinfo =& $this->content['cms_node'][$activity['contentid']];
		$articleinfo =& $this->content['cms_article'][$nodeinfo['contentid']];
		$activity['postdate'] = vbdate(vB::$vbulletin->options['dateformat'], $activity['dateline'], true);
		$activity['posttime'] = vbdate(vB::$vbulletin->options['timeformat'], $activity['dateline']);

		$preview = strip_quotes($articleinfo['pagetext']);
		$articleinfo['preview'] = htmlspecialchars_uni(fetch_censored_text(
			fetch_trimmed_title(strip_bbcode($preview, false, true, true, true),
				vb::$vbulletin->options['as_snippet'])
		));

		$articleinfo['fullurl'] = vB_Route::create('vBCms_Route_Content', $nodeinfo['nodeid'] . ($nodeinfo['url'] == '' ? '' : '-' . $nodeinfo['url'] ))->getCurrentURL();
		$nodeinfo['parenturl'] = $this->fetchParentUrl($nodeinfo['parentnode']);
		$nodeinfo['parenttitle'] = $this->fetchParentTitle($nodeinfo['parentnode']);
		$userinfo = $this->fetchUser($activity['userid']);

		if ($fetchphrase)
		{
			if ($userinfo['userid'])
			{
				$phrase =  construct_phrase($this->vbphrase['x_created_an_article_y_in_z'], fetch_seo_url('member', $userinfo), $userinfo['username'], $articleinfo['fullurl'], $nodeinfo['title'], $nodeinfo['parenturl'], $nodeinfo['parenttitle']);
			}
			else
			{
				$phrase =  construct_phrase($this->vbphrase['x_created_an_article_y_in_z'], $userinfo['username'], $articleinfo['fullurl'], $nodeinfo['title'], $nodeinfo['parenturl'], $nodeinfo['parenttitle']);
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
				$templater->register('nodeinfo', $nodeinfo);
				$templater->register('articleinfo', $articleinfo);
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
