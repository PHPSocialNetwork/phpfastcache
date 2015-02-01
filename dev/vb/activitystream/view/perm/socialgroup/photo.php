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

class vB_ActivityStream_View_Perm_Socialgroup_Photo extends vB_ActivityStream_View_Perm_Socialgroup_Base
{
	public function __construct(&$content, &$vbphrase)
	{
		$this->requireExist['vB_ActivityStream_View_Perm_Socialgroup_Group'] = 1;
		$this->requireFirst['vB_ActivityStream_View_Perm_Socialgroup_Photocomment'] = 1;
		$this->requireFirst['vB_ActivityStream_View_Perm_Album_Comment'] = 1;
		return parent::__construct($content, $vbphrase);
	}

	public function group($activity)
	{
		if (!$this->fetchCanUseGroups() OR !vB::$vbulletin->userinfo['userid'])
		{
			return;
		}

		$this->content['group_photo_only'][$activity['contentid']] = 1;
		if (!$this->content['socialgroup_attachment'][$activity['contentid']])
		{
			$this->content['socialgroup_attachmentid'][$activity['contentid']] = 1;
		}
	}

	public function process()
	{
		if (!$this->content['socialgroup_attachmentid'] OR !vB::$vbulletin->userinfo['userid'])
		{
			return true;
		}

		$attachments = vB::$db->query_read_slave("
			SELECT
				a.attachmentid AS a_attachmentid, a.dateline AS a_dateline, a.contentid AS a_groupid, a.userid AS a_userid, a.counter AS a_counter, a.state AS a_state,
				fd.thumbnail_width AS a_thumbnail_width, fd.thumbnail_height AS a_thumbnail_height,
				sg.options AS g_options, sg.groupid AS g_groupid, sg.name AS g_name, sg.creatoruserid AS g_creatoruserid, sg.creatoruserid AS g_userid,
				sg.dateline AS g_dateline, sg.type AS g_type
				" . (vB::$vbulletin->userinfo['userid'] ? ", sgm.type AS g_membertype" : "") . "
			FROM " . TABLE_PREFIX . "attachment AS a
			INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
			INNER JOIN " . TABLE_PREFIX . "socialgroup AS sg ON (a.contentid = sg.groupid)
			" . (vB::$vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "socialgroupmember AS sgm ON (sgm.userid = " . vB::$vbulletin->userinfo['userid'] . " AND sgm.groupid = sg.groupid)" : "") . "
			WHERE
				a.attachmentid IN (" . implode(",", array_keys($this->content['socialgroup_attachmentid'])) . ")
					AND
				a.state <> 'deleted'
		");
		while ($attachment = vB::$db->fetch_array($attachments))
		{
			// Unset these values so we don't query for the discussions and groups when the process() functions for those get called .. we already have them
			unset($this->content['groupid'][$attachment['g_groupid']]);
			$this->content['socialgroup_attachment'][$attachment['a_attachmentid']] = $this->parse_array($attachment, 'a_');
			$this->content['socialgroup_attachment_matrix'][$attachment['g_groupid']][] = $attachment['a_attachmentid'];
			$this->content['userid'][$attachment['a_userid']] = 1;
			if (!$this->content['socialgroup'][$attachment['g_groupid']])
			{
				$this->content['socialgroup'][$attachment['g_groupid']] = $this->parse_array($attachment, 'g_');
				$this->content['socialgroup'][$attachment['g_groupid']]['is_owner'] = ($this->content['socialgroup'][$attachment['g_groupid']]['creatoruserid'] == vB::$vbulletin->userinfo['userid']);
				$this->content['userid'][$attachment['g_creatoruserid']] = 1;
			}
		}

		$this->content['socialgroup_attachmentid'] = array();
	}

	public function fetchCanView($record)
	{
		$this->processUsers();
		return $this->fetchCanViewSocialgroupPhoto($record['contentid']);
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
		$attachmentinfo =& $this->content['socialgroup_attachment'][$activity['contentid']];
		$groupinfo =& $this->content['socialgroup'][$attachmentinfo['groupid']];
		$userinfo =& $this->content['user'][$activity['userid']];

		if (!$skipgroup)
		{
			if (!$this->content['group_photo_only'][$attachmentinfo['attachmentid']])
			{
				return '';
			}

			foreach($this->content['socialgroup_attachment_matrix'][$attachmentinfo['groupid']] AS $attachmentid)
			{
				$attachment = $this->content['socialgroup_attachment'][$attachmentid];
				if ($this->content['group_photo_only'][$attachmentid] AND $activity['userid'] == $attachment['userid'])
				{
					$attach[] = $attachment;
					unset($this->content['group_photo_only'][$attachmentid]);
				}
			}
			$attach = array_reverse($attach);
			$photocount = count($attach);
		}
		else
		{
			$attach = array($attachmentinfo);
			$photocount = count($attach);
		}

		$activity['postdate'] = vbdate(vB::$vbulletin->options['dateformat'], $activity['dateline'], true);
		$activity['posttime'] = vbdate(vB::$vbulletin->options['timeformat'], $activity['dateline']);

		if ($fetchphrase)
		{
			return array(
				'phrase'   => construct_phrase($this->vbphrase['x_added_y_photos_to_group_z'], fetch_seo_url('member', $userinfo), $userinfo['username'], $photocount, vB::$vbulletin->session->vars['sessionurl'], $groupinfo['groupid'], $groupinfo['name']),
				'userinfo' => $userinfo,
				'activity' => $activity,
			);
		}
		else
		{
			$templater = vB_Template::create($templatename);
				$templater->register('userinfo', $userinfo);
				$templater->register('activity', $activity);
				$templater->register('attachmentinfo', $attachmentinfo);
				$templater->register('attach', $attach);
				$templater->register('groupinfo', $groupinfo);
				$templater->register('photocount', $photocount);
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