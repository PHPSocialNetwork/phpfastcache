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

abstract class vB_ActivityStream_View_Perm_Album_Base extends vB_ActivityStream_View_Perm_Base
{
	protected function fetchCanViewAlbums()
	{
		return
		(
			vB::$vbulletin->options['socnet'] & vB::$vbulletin->bf_misc_socnet['enable_albums']
				AND
			vB::$vbulletin->userinfo['permissions']['genericpermissions'] & vB::$vbulletin->bf_ugp_genericpermissions['canviewmembers']
				AND
			vB::$vbulletin->userinfo['permissions']['albumpermissions'] & vB::$vbulletin->bf_ugp_albumpermissions['canviewalbum']
		);
	}

	protected function fetchCanViewAlbum($albumid)
	{
		if (!$this->fetchCanViewAlbums() OR !($album = $this->content['album'][$albumid]))
		{
			return false;
		}

		if (!($userinfo = $this->content['user'][$album['userid']]))
		{
			return false;
		}

		cache_permissions($userinfo, false);
		if (!can_moderate(0, 'caneditalbumpicture') AND !($userinfo['permissions']['albumpermissions'] & vB::$vbulletin->bf_ugp_albumpermissions['canalbum']))
		{
			return false;
		}

		if (!can_view_profile_section($album['userid'], 'albums'))
		{
			// private album that we can not see
			return false;
		}

		require_once(DIR . '/includes/functions_album.php');
		if ($album['state'] == 'private' AND !can_view_private_albums($album['userid'], $album['buddy']))
		{
			// private album that we can not see
			return false;
		}
		else if ($album['state'] == 'profile' AND !can_view_profile_albums($album['userid']))
		{
			// profile album that we can not see
			return false;
		}

		return true;
	}

	protected function fetchCanViewAlbumPhoto($attachmentid)
	{
		if (
			!$this->fetchCanViewAlbums()
				OR
			!($attachment = $this->content['album_attachment'][$attachmentid])
				OR
			!($album = $this->content['album'][$attachment['albumid']])
				OR
			! $this->fetchCanViewAlbum($album['albumid'])
		)
		{
			return false;
		}

		if(
			$attachment['state'] == 'moderation'
				AND
			!can_moderate(0, 'canmoderatepictures')
				AND
			$attachment['userid'] != vB::$vbulletin->userinfo['userid']
				AND
			!can_moderate(0, 'caneditalbumpicture')
		)
		{
			return false;
		}

		return true;
	}

	protected function fetchCanViewAlbumComment($commentid)
	{
		$comment = $this->content['album_picturecomment'][$commentid];
		$attachment = $this->content['album_attachment'][$comment['attachmentid']];

		require_once(DIR . '/includes/functions_picturecomment.php');
		if ($comment['state'] == 'moderation')
		{
			if (
				(!vB::$vbulletin->userinfo['userid'] OR vB::$vbulletin->userinfo['userid'] != $comment['postuserid'])
					AND
				!fetch_user_picture_message_perm('canmoderatemessages', $attachment)
			)
			{
				return false;
			}
		}

		if (!vB::$vbulletin->options['pc_enabled'])
		{
			return false;
		}

		return $this->fetchCanViewAlbumPhoto($comment['attachmentid']);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 57655 $
|| ####################################################################
\*======================================================================*/