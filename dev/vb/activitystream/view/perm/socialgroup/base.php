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

abstract class vB_ActivityStream_View_Perm_Socialgroup_Base extends vB_ActivityStream_View_Perm_Base
{
	protected function fetchCanViewGroupContent($groupid)
	{
		if (!($group = $this->content['socialgroup'][$groupid]))
		{
			return false;
		}

		return
		(
			!($group['options'] & vB::$vbulletin->bf_misc_socialgroupoptions['join_to_view'])
			OR !vB::$vbulletin->options['sg_allow_join_to_view']
			OR $group['membertype'] == 'member'
			OR can_moderate(0, 'caneditsocialgroups')
			OR vB::$vbulletin->userinfo['permissions']['socialgrouppermissions'] & vB::$vbulletin->bf_ugp_socialgrouppermissions['canalwayspostmessage']
			OR vB::$vbulletin->userinfo['permissions']['socialgrouppermissions'] & vB::$vbulletin->bf_ugp_socialgrouppermissions['canalwascreatediscussion']
		);
	}

	protected function fetchCanUseGroups()
	{
		// No permission to use group so don't process group stuff
		if (
			!(vB::$vbulletin->options['socnet'] & vB::$vbulletin->bf_misc_socnet['enable_groups'])
				OR
			!(vB::$vbulletin->userinfo['permissions']['socialgrouppermissions'] & vB::$vbulletin->bf_ugp_socialgrouppermissions['canviewgroups'])
		)
		{
			return false;
		}
		return true;
	}

	protected function fetchCanViewSocialgroupDiscussion($discussionid)
	{
		if (!$this->fetchCanUseGroups() OR !($discussion = $this->content['socialgroup_discussion'][$discussionid]))
		{
			return false;
		}
		$group = $this->content['socialgroup'][$discussion['groupid']];

		if (!$this->fetchCanViewGroupContent($group['groupid']) OR $discussion['state'] == 'deleted')
		{
			return false;
		}

		if (
			!vB::$vbulletin->options['socnet_groups_msg_enabled']
				OR
			!($group['options'] & vB::$vbulletin->bf_misc_socialgroupoptions['enable_group_messages'])
		)
		{
			return false;
		}

		require_once(DIR . '/includes/functions_socialgroup.php');
		if (
			$discussion['state'] == 'moderation'
				AND
			!fetch_socialgroup_modperm('canmoderategroupmessages', $group)
				AND
			$discussion['postuserid'] != vB::$vbulletin->userinfo['userid']
		)
		{
			return false;
		}

		return true;
	}

	protected function fetchCanViewSocialgroupGroupMessage($gmid)
	{
		if (!$this->fetchCanUseGroups())
		{
			return false;
		}

		$message = $this->content['socialgroup_message'][$gmid];
		$discussion = $this->content['socialgroup_discussion'][$message['discussionid']];
		$group = $this->content['socialgroup'][$discussion['groupid']];

		if (!$this->fetchCanViewSocialgroupDiscussion($message['discussionid']))
		{
			return false;
		}

		if ($message['state'] == 'moderation')
		{
			$can_view_message = (
				can_moderate(0, 'canmoderategroupmessages')
				OR $message['postuserid'] == vB::$vbulletin->userinfo['userid']
				OR (
					$group['creatoruserid'] == vB::$vbulletin->userinfo['userid']
						AND
					vB::$vbulletin->userinfo['permissions']['socialgrouppermissions'] & vB::$vbulletin->bf_ugp_socialgrouppermissions['canmanageowngroups']
				)
			);

			if (!$can_view_message)
			{
				return false;
			}
		}

		return true;
	}

	protected function fetchCanViewSocialgroupPhoto($attachmentid)
	{
		if (!$this->fetchCanUseGroups())
		{
			return false;
		}

		if (!($attachment = $this->content['socialgroup_attachment'][$attachmentid]))
		{
			return false;
		}
		if (!($group = $this->content['socialgroup'][$attachment['groupid']]))
		{
			return false;
		}

		if (
			!(vB::$vbulletin->options['socnet_groups_pictures_enabled'])
				OR
			!($group['options'] & vB::$vbulletin->bf_misc_socialgroupoptions['enable_group_albums'])
				OR
			($group['membertype'] != 'member' AND !can_moderate(0, 'caneditgrouppicture'))
		)
		{
			return false;
		}

		return true;
	}

	protected function fetchCanViewSocialgroupPhotoComment($commentid)
	{
		if (!$this->fetchCanUseGroups())
		{
			return false;
		}

		$comment = $this->content['socialgroup_picturecomment'][$commentid];
		$attachment = $this->content['socialgroup_attachment'][$comment['attachmentid']];

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

		return $this->fetchCanViewSocialgroupPhoto($comment['attachmentid']);
	}

}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 57655 $
|| ####################################################################
\*======================================================================*/