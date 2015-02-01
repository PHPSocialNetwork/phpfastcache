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

class vB_ActivityStream_View_Perm_Socialgroup_Discussion extends vB_ActivityStream_View_Perm_Socialgroup_Base
{
	public function __construct(&$content, &$vbphrase)
	{
		$this->requireFirst['vB_ActivityStream_View_Perm_Socialgroup_Groupmessage'] = 1;
		$this->requireExist['vB_ActivityStream_View_Perm_Socialgroup_Group'] = 1;
		return parent::__construct($content, $vbphrase);
	}

	public function group($activity)
	{
		if (!$this->fetchCanUseGroups())
		{
			return;
		}

		if (!$this->content['socialgroup_discussion'][$activity['contentid']])
		{
			$this->content['discussionid'][$activity['contentid']] = 1;
		}
	}

	public function process()
	{
		if (!$this->content['discussionid'])
		{
			return true;
		}

		$discussions = vB::$db->query_read_slave("
			SELECT
				d.discussionid AS d_discussionid, d.groupid AS d_groupid, d.visible AS d_visible,
				firstpost.state AS d_state, firstpost.postuserid AS d_postuserid, firstpost.postuserid AS d_userid, firstpost.title AS d_title,
				firstpost.postusername AS d_postusername, firstpost.pagetext AS d_pagetext,
				sg.options AS g_options, sg.groupid AS g_groupid, sg.name AS g_name, sg.creatoruserid AS g_creatoruserid, sg.creatoruserid AS g_userid,
				sg.dateline AS g_dateline, sg.type AS g_type
				" . (vB::$vbulletin->userinfo['userid'] ? ", sgm.type AS g_membertype" : "") . "
			FROM " . TABLE_PREFIX . "discussion AS d
			LEFT JOIN " . TABLE_PREFIX . "groupmessage AS firstpost ON (firstpost.gmid = d.firstpostid)
			INNER JOIN " . TABLE_PREFIX . "socialgroup AS sg ON (d.groupid = sg.groupid)
			" . (vB::$vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "socialgroupmember AS sgm ON (sgm.userid = " . vB::$vbulletin->userinfo['userid'] . " AND sgm.groupid = sg.groupid)" : "") . "
			WHERE
				d.discussionid IN (" . implode(",", array_keys($this->content['discussionid'])) . ")
					AND
				firstpost.state <> 'deleted'
		");
		while ($discussion = vB::$db->fetch_array($discussions))
		{
			unset($this->content['groupid'][$discussion['d_groupid']]);
			$this->content['socialgroup_discussion'][$discussion['d_discussionid']] = $this->parse_array($discussion, 'd_');
			$this->content['userid'][$discussion['d_postuserid']] = 1;
			if (!$this->content['socialgroup'][$discussion['d_groupid']])
			{
				$this->content['socialgroup'][$discussion['d_groupid']] = $this->parse_array($discussion, 'g_');
				$this->content['socialgroup'][$discussion['d_groupid']]['is_owner'] = ($this->content['socialgroup'][$discussion['d_groupid']]['creatoruserid'] == vB::$vbulletin->userinfo['userid']);
				$this->content['userid'][$discussion['g_creatoruserid']] = 1;
			}
		}

		$this->content['discussionid'] = array();
	}

	public function fetchCanView($record)
	{
		$this->processUsers();
		return $this->fetchCanViewSocialgroupDiscussion($record['contentid']);
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
		$discussioninfo =& $this->content['socialgroup_discussion'][$activity['contentid']];
		$groupinfo =& $this->content['socialgroup'][$discussioninfo['groupid']];

		$activity['postdate'] = vbdate(vB::$vbulletin->options['dateformat'], $activity['dateline'], true);
		$activity['posttime'] = vbdate(vB::$vbulletin->options['timeformat'], $activity['dateline']);

		$preview = strip_quotes($discussioninfo['pagetext']);
		$discussioninfo['preview'] = htmlspecialchars_uni(fetch_censored_text(
			fetch_trimmed_title(strip_bbcode($preview, false, true, true, true),
				vb::$vbulletin->options['as_snippet'])
		));
		$userinfo = $this->fetchUser($activity['userid'], $discussion['postusername']);

		if ($fetchphrase)
		{
			if ($userinfo['userid'])
			{
				$phrase = construct_phrase($this->vbphrase['x_started_a_discussion_y_in_z'], fetch_seo_url('member', $userinfo), $userinfo['username'], vB::$vbulletin->session->vars['sessionurl'], $discussioninfo['discussionid'], $discussioninfo['title'], $groupinfo['groupid'], $groupinfo['name']);
			}
			else
			{
				$phrase = construct_phrase($this->vbphrase['guest_x_started_a_discussion_y_in_z'], $userinfo['username'], vB::$vbulletin->session->vars['sessionurl'], $discussioninfo['discussionid'], $discussioninfo['title'], $groupinfo['groupid'], $groupinfo['name']);
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
				$templater->register('discussioninfo', $discussioninfo);
				$templater->register('groupinfo', $groupinfo);
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