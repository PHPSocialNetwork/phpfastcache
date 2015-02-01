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

class vB_ActivityStream_View_Perm_Album_Album extends vB_ActivityStream_View_Perm_Album_Base
{
	public function __construct(&$content, &$vbphrase)
	{
		$this->requireFirst['vB_ActivityStream_View_Perm_Album_Photo'] = 1;
		$this->requireFirst['vB_ActivityStream_View_Perm_Album_Comment'] = 1;
		return parent::__construct($content, $vbphrase);
	}

	public function group($activity)
	{
		if (!$this->fetchCanViewAlbums())
		{
			return;
		}

		if (!$this->content['album'][$activity['contentid']])
		{
			$this->content['albumid'][$activity['contentid']] = 1;
		}
	}

	public function process()
	{
		if (!$this->content['albumid'])
		{
			return true;
		}

		$albums = vB::$db->query_read_slave("
			SELECT
				a.albumid, a.userid, a.createdate, a.title, a.state, a.coverattachmentid
				" . (vB::$vbulletin->userinfo['userid'] ? ", u.type AS buddy" : "") . "
			FROM " . TABLE_PREFIX . "album AS a
			" . (vB::$vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "userlist AS u ON (a.userid = u.userid AND u.relationid = " . vB::$vbulletin->userinfo['userid'] . " AND u.type = 'buddy')" : "") . "
			WHERE a.albumid IN (" . implode(",", array_keys($this->content['albumid'])) . ")
		");
		while ($album = vB::$db->fetch_array($albums))
		{
			$this->content['album'][$album['albumid']] = $album;
			$this->content['userid'][$album['userid']] = 1;
		}

		$this->content['albumid'] = array();
	}

	public function fetchCanView($record)
	{
		$this->processUsers();
		return $this->fetchCanViewAlbum($record['contentid']);
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
		$albuminfo =& $this->content['album'][$activity['contentid']];

		$activity['postdate'] = vbdate(vB::$vbulletin->options['dateformat'], $activity['dateline'], true);
		$activity['posttime'] = vbdate(vB::$vbulletin->options['timeformat'], $activity['dateline']);

		if ($fetchphrase)
		{
			return array(
				'phrase' => construct_phrase($this->vbphrase['x_created_an_album_y'], fetch_seo_url('member', $userinfo), $userinfo['username'], vB::$vbulletin->session->vars['sessionurl'],  $albuminfo['albumid'], $albuminfo['title']),
				'userinfo' => $userinfo,
				'activity' => $activity,
			);
		}
		else
		{
			$templater = vB_Template::create($templatename);
				$templater->register('userinfo', $userinfo);
				$templater->register('activity', $activity);
				$templater->register('albuminfo', $albuminfo);
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