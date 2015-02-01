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

class vB_ActivityStream_View_Perm_Album_Photo extends vB_ActivityStream_View_Perm_Album_Base
{
	public function __construct(&$content, &$vbphrase)
	{
		$this->requireExist['vB_ActivityStream_View_Perm_Album_Album'] = 1;
		$this->requireFirst['vB_ActivityStream_View_Perm_Socialgroup_Photocomment'] = 1;
		$this->requireFirst['vB_ActivityStream_View_Perm_Album_Comment'] = 1;
		return parent::__construct($content, $vbphrase);
	}

	public function group($activity)
	{
		if (!$this->fetchCanViewAlbums())
		{
			return;
		}

		$this->content['album_photo_only'][$activity['contentid']] = 1;
		if (!$this->content['album_attachment'][$activity['contentid']])
		{
			$this->content['album_attachmentid'][$activity['contentid']] = 1;
		}
	}

	public function process()
	{
		if (!$this->content['album_attachmentid'])
		{
			return true;
		}

		$attachments = vB::$db->query_read_slave("
			SELECT
				a.attachmentid AS a_attachmentid, a.dateline AS a_dateline, a.contentid AS a_albumid, a.userid AS a_userid, fd.thumbnail_width AS a_thumbnail_width, fd.thumbnail_height AS a_thumbnail_height,
				a.counter AS a_counter, a.state AS a_state,
				al.albumid AS al_albumid, al.userid AS al_userid, al.createdate AS al_createdate, al.title AS al_title, al.state AS al_state, al.coverattachmentid AS al_coverattachmentid
				" . (vB::$vbulletin->userinfo['userid'] ? ", u.type AS al_buddy" : "") . "
			FROM " . TABLE_PREFIX . "attachment AS a
			INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
			INNER JOIN " . TABLE_PREFIX . "album AS al ON (a.contentid = al.albumid)
			" . (vB::$vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "userlist AS u ON (al.userid = u.userid AND u.relationid = " . vB::$vbulletin->userinfo['userid'] . " AND u.type = 'buddy')" : "") . "
			WHERE
				a.attachmentid IN (" . implode(",", array_keys($this->content['album_attachmentid'])) . ")
					AND
				a.state <> 'deleted'
		");
		while ($attachment = vB::$db->fetch_array($attachments))
		{
			// Unset these values so we don't query for the albums when the process() function for it gets called .. we already have it
			unset($this->content['albumid'][$attachment['a_albumid']]);
			$this->content['album_attachment'][$attachment['a_attachmentid']] = $this->parse_array($attachment, 'a_');
			$this->content['album_attachment_matrix'][$attachment['a_albumid']][] = $attachment['a_attachmentid'];
			$this->content['userid'][$attachment['a_userid']] = 1;
			if (!$this->content['album'][$attachment['al_albumid']])
			{
				$this->content['album'][$attachment['al_albumid']] = $this->parse_array($attachment, 'al_');
				$this->content['userid'][$attachment['al_userid']] = 1;
			}
		}

		$this->content['album_attachmentid'] = array();
	}

	public function fetchCanView($record)
	{
		$this->processUsers();
		return $this->fetchCanViewAlbumPhoto($record['contentid']);
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
		$attachmentinfo =& $this->content['album_attachment'][$activity['contentid']];
		$albuminfo =& $this->content['album'][$attachmentinfo['albumid']];
		$userinfo =& $this->content['user'][$activity['userid']];

		if (!$skipgroup)
		{
			if (!$this->content['album_photo_only'][$attachmentinfo['attachmentid']])
			{
				return '';
			}

			$attach = array();
			foreach($this->content['album_attachment_matrix'][$albuminfo['albumid']] AS $attachmentid)
			{
				if ($this->content['album_photo_only'][$attachmentid])
				{
					$attach[] = $this->content['album_attachment'][$attachmentid];
					unset($this->content['album_photo_only'][$attachmentid]);
				}
			}
			$attach = array_reverse($attach);
			$photocount = count($attach);
		}
		else
		{
			$attach = array($attachmentinfo);
			$photocount = 1;
		}

		$activity['postdate'] = vbdate(vB::$vbulletin->options['dateformat'], $activity['dateline'], true);
		$activity['posttime'] = vbdate(vB::$vbulletin->options['timeformat'], $activity['dateline']);

		if ($fetchphrase)
		{
			return array(
				'phrase'   => construct_phrase($this->vbphrase['x_added_y_photos_to_album_z'], fetch_seo_url('member', $userinfo), $userinfo['username'], $photocount, vB::$vbulletin->session->vars['sessionurl'], $albuminfo['albumid'], $albuminfo['title']),
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
				$templater->register('albuminfo', $albuminfo);
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