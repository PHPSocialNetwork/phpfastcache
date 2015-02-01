<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Blog 4.2.1 - Licence Number VBF02D260D
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

require_once(DIR . '/includes/blog_functions_shared.php');

/**
* Pre-processes the location for blog scripts in Who's Online
*
* @param	array	userinfo array
* @param	string	the filename that the page view is on
* @param	array	attributes passed in the URI
*
* @return	void
*/
function blog_online_location_preprocess(&$userinfo, $filename, $values)
{
	global $vbulletin;

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		if (!$vbulletin->userinfo['userid'] OR !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
		{
			return;
		}
	}

	if (strpos($filename, 'blog') === 0 || strpos($filename, 'entry') === 0)
	{
		global $bloguserids, $blogids, $blogtextids, $blogtrackbackids;

		if (!empty($values['blogid']))
		{
			$userinfo['blogid'] = intval($values['blogid']);
			$blogids .= ',' . $userinfo['blogid'];
		}

		if (!empty($values['blogtextid']))
		{
			$userinfo['blogtextid'] = intval($values['blogtextid']);
			$blogtextids .= ',' . $userinfo['blogtextid'];
		}

		if (!empty($values['blogtrackbackid']))
		{
			$userinfo['blogtrackbackid'] = intval($values['blogtrackbackid']);
			$blogtrackbackids .= ',' . $userinfo['blogtrackbackid'];
		}

		if (!empty($values['userid']))
		{
			$userinfo['bloguserid'] = intval($values['userid']);
			$bloguserids .= ',' . $userinfo['bloguserid'];
		}

		if (!empty($values['b']))
		{
			$userinfo['blogid'] = intval($values['b']);
			$blogids .= ',' . $userinfo['blogid'];
		}
	}
}

/**
* Converts are blog ids to titles for Who's Online
*
* @return	void
*/
function blog_online_ids_titles()
{
	global $blogids, $blogtextids, $blogtrackbackids, $bloguserids, $vbulletin;
	global $wol_blog, $wol_blogtext, $wol_blogtrackback, $wol_bloguser, $wol_user;

	if ($blogtrackbackids)
	{
		$blogtrackbackidquery = $vbulletin->db->query_read_slave("
			SELECT blogid, blogtrackbackid
			FROM " . TABLE_PREFIX . "blog_trackback
			WHERE blogtrackbackid IN (0$blogtrackbackids)
		");
		while ($blogtrackbackidqueryr = $vbulletin->db->fetch_array($blogtrackbackidquery))
		{
			$blogids .= ',' . $blogtrackbackidqueryr['blogid'];
			$wol_blogtrackback["$blogtrackbackidqueryr[blogtrackbackid]"] = $blogtrackbackidqueryr['blogid'];
		}
	}

	if ($blogtextids)
	{
		$blogtextidquery = $vbulletin->db->query_read_slave("
			SELECT blogid, blogtextid, title
			FROM " . TABLE_PREFIX . "blog_text
			WHERE blogtextid IN (0$blogtextids)
		");
		while ($blogtextidqueryr = $vbulletin->db->fetch_array($blogtextidquery))
		{
			$blogids .= ',' . $blogtextidqueryr['blogid'];
			$wol_blogtext["$blogtextidqueryr[blogtextid]"]['blogid'] = $blogtextidqueryr['blogid'];
			$wol_blogtext["$blogtextidqueryr[blogtextid]"]['title'] = $blogtextidqueryr['title'];
		}
	}

	if ($blogids)
	{
		$blogresults = $vbulletin->db->query_read_slave("
			SELECT blog.title, blogid, blog.userid, state, blog.options
			FROM " . TABLE_PREFIX . "blog AS blog
			LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog.userid)
			WHERE blogid IN (0$blogids)
		");
		while ($blogresult = $vbulletin->db->fetch_array($blogresults))
		{
			$wol_blog["$blogresult[blogid]"]['title'] = $blogresult['title'];
			$wol_blog["$blogresult[blogid]"]['userid'] = $blogresult['userid'];
			$wol_blog["$blogresult[blogid]"]['state'] = $blogresult['state'];
			$wol_blog["$blogresult[blogid]"]['private'] = ($blogresult['options'] & $vbulletin->bf_misc_vbblogoptions['private']);
			$bloguserids .= ",$blogresult[userid]";
		}
	}

	if ($bloguserids)
	{
		$fields = $joins = '';
		if ($vbulletin->userinfo['userid'])
		{
			$fields = ", ignored.relationid AS ignoreid, buddy.relationid AS buddyid";
			$joins = "
				LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = bu.bloguserid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')
				LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = bu.bloguserid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')
			";
		}

		$userresults = $vbulletin->db->query_read_slave("
			SELECT bu.title, bu.bloguserid, bu.options_member, bu.options_guest, bu.options_buddy, bu.options_ignore, bu.memberids, bu.memberblogids,
				user.userid, user.username, IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, user.infractiongroupid, user.usergroupid, user.membergroupids
				$fields
			FROM " . TABLE_PREFIX . "blog_user AS bu
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (bu.bloguserid = user.userid)
			$joins
			WHERE bu.bloguserid IN (0$bloguserids)
		");
		while ($userresult = $vbulletin->db->fetch_array($userresults))
		{
			cache_permissions($userresult, false);
			fetch_musername($userresult);
			$wol_user["$userresult[userid]"] = $userresult['musername'];

			$member = ($userresult['options_member'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] ? 1 : 0);
			$guest = ($userresult['options_guest'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] ? 1 : 0);
			$buddy = ($userresult['options_buddy'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] ? 1 : 0);
			$ignore = ($userresult['options_ignore'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] ? 1 : 0);

			$wol_bloguser["$userresult[bloguserid]"]['title'] = $userresult['title'];
			$wol_bloguser["$userresult[bloguserid]"]['canviewmyblog'] =
			(
				(
					!$userresult['buddyid']
						OR
					$buddy
				)
				AND
				(
					!$userresult['ignoreid']
						OR
					$ignore
				)
				AND
				(
					(
						$member
							AND
						$vbulletin->userinfo['userid']
					)
					OR
					(
						$guest
							AND
						!$vbulletin->userinfo['userid']
					)
				)
				OR
				(
					$ignore
						AND
					$userresult['ignoreid']
				)
				OR
				(
					$buddy
						AND
					$userresult['buddyid']
				)
				OR
					$userresult['userid'] == $vbulletin->userinfo['userid']
				OR
					can_moderate_blog()
				OR
					is_member_of_blog($vbulletin->userinfo, $userresult)
			) ? true : false;

		}
	}
}

/**
* Processes the location for blog scripts in Who's Online
*
* @param	array	userinfo array
* @param	string	the filename that the page view is on
* @param	array	attributes passed in the URI
*
* @return	void
*/
function blog_online_location_process(&$userinfo, &$values, $filename)
{
	global $vbulletin;

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		if (!$vbulletin->userinfo['userid'] OR !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
		{
			return;
		}
	}

	switch($filename)
	{
		case 'entry.php':
			if ($values['do'] == 'sendtofriend')
			{
				$userinfo['activity'] = 'blog_send_friend';
			}
			else if ($values['do'] == 'viewip')
			{
				$userinfo['activity'] = 'blog_view_ip';
			}
			else if ($values['do'] == 'comments')
			{
				$userinfo['activity'] = 'blog_view_comments';
			}
			else
			{
				$userinfo['activity'] = 'blog_view_entry';
			}
			break;

		case 'blog.php':
			if ((!isset($values['do']) AND isset($values['blogid'])) OR $values['do'] == 'blog')
			{
				$userinfo['activity'] = 'blog_view_entry';
			}
			else if ((!isset($values['do']) AND isset($values['userid'])) OR $values['do'] == 'list')
			{
				$userinfo['activity'] = 'blog_view_user';
			}
			else if ($values['do'] == 'bloglist')
			{
				$userinfo['activity'] = 'blog_view_list';
			}
			else if ($values['do'] == 'members')
			{
				$userinfo['activity'] = 'blog_view_members';
			}
			else
			{
				$userinfo['activity'] = 'blog_view_home';
			}
			break;

		case 'blog_inlinemod.php':
			$userinfo['activity'] = 'blog_inlinemod';
			break;

		case 'blog_tag.php':
			$userinfo['activity'] = 'blog_tag';
			break;

		case 'blog_post.php':
			// could change this behaviour depending on if the edited post is visible
			if ($values['do'] == 'newblog')
			{
				$userinfo['activity'] = 'blog_new_entry';
			}
			else if ($values['do'] == 'editblog')
			{
				$userinfo['activity'] = 'blog_edit_entry';
			}
			else if ($values['do'] == 'updateblog')
			{
				$userinfo['activity'] = isset($values['blogid']) ? 'blog_edit_entry' :'blog_new_entry';
			}
			else if ($values['do'] == 'comment')
			{
				$userinfo['activity'] = 'blog_new_comment';
			}
			else if ($values['do'] == 'editcomment')
			{
				$userinfo['activity'] = 'blog_edit_comment';
			}
			else if ($values['do'] == 'edittrackback' OR $values['do'] == 'updatetrackback')
			{
				$userinfo['activity'] = 'blog_edit_trackback';
			}
			break;

		case 'blog_report.php':
			if ($values['blogtextid'])
			{
				$userinfo['activity'] = 'blog_report_comment';
			}
			else
			{
				$userinfo['activity'] = 'blog_report_entry';
			}
			break;

		case 'blog_subscription.php':
			$userinfo['activity'] = 'blog_subscription';
			break;

		case 'blog_usercp.php':
			$userinfo['activity'] = 'blog_usercp';
			break;
	}
}

/**
* Called when an unknown Who's Online location is found within vBulletin
*
* @param	array		userinfo array
* @param	boolean		reference to a boolean variable to indicate if the function handled the location
*
* @return	void
*/
function blog_online_location_unknown(&$userinfo, &$handled)
{
	if (strpos($userinfo['activity'], 'blog_') === 0)
	{
		global $wol_blog, $wol_blogtext, $wol_blogtrackback, $wol_bloguser, $wol_user, $vbulletin, $vbphrase;

		$handled = true;
		if ($userinfo['blogtextid'])
		{
			$blogid = $wol_blogtext["$userinfo[blogtextid]"]['blogid'];
		}
		else
		{
			$blogid = $userinfo['blogid'];
		}

		if ($wol_blog["$blogid"]['userid'])
		{
			$userid = $wol_blog["$blogid"]['userid'];
		}
		else if (!empty($userinfo['targetuserid']))
		{
			$userid = $userinfo['targetuserid'];
		}
		else if ($userinfo['bloguserid'])
		{
			$userid = $userinfo['bloguserid'];
		}

		$can_see_blog = false;
		$can_see_blog_title = false;
		if (
				(($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND $wol_blog["$blogid"]['userid'] == $vbulletin->userinfo['userid'])
				OR
				(($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']) AND $wol_blog["$blogid"]['userid'] != $vbulletin->userinfo['userid'])
			)
		{
			if ($can_see_blog = $wol_bloguser["$userid"]['canviewmyblog'])
			{	/* draft isn't here because it doesn't really exist yet, ooOoooh (best ghost impression) */
				if (((
					$wol_blog["$blogid"]['state'] == 'visible'
						OR
					(
						$wol_blog["$blogid"]['state'] == 'deleted'
							AND
						can_moderate_blog()
					)
						OR
					(
						$bloginfo['state'] == 'moderation'
							AND
						can_moderate_blog('canmoderateentries')
					))
						AND
					!$wol_blog["$blogid"]['private'])
						OR
					$wol_blog["$blogid"]['userid'] == $vbulletin->userinfo['userid']
				)
				{
					$can_see_blog_title = true;
				}
			}
		}

		$blogtitle = $wol_bloguser["$userid"]['title'] ? $wol_bloguser["$userid"]['title'] : $wol_user["$userid"];

		$blog = '<a href="' . fetch_seo_url('blog', array('userid' => $userid, 'blog_title' => $blogtitle))	. "\">$blogtitle</a>";
		$entry = '<a href="' . fetch_seo_url('entry', array('blogid' => $blogid, 'title' => $wol_blog["$blogid"]['title']))	.
			"\">" . $wol_blog["$blogid"]['title'] . '</a>';
		$showentry = $showblog = false;

		switch ($userinfo['activity'])
		{
			case 'blog_view_user':
				$userinfo['action'] = $vbphrase['viewing_blog'];
				$showblog = true;
				break;

			case 'blog_view_entry':
				$userinfo['action'] = $vbphrase['viewing_blog_entry'];
				$showentry = true;
				break;

			case 'blog_view_home':
				$userinfo['action'] = $vbphrase['viewing_blog_home'];
				break;

			case 'blog_view_comments':
				$userinfo['action'] = $vbphrase['viewing_blog_comments'];
				break;

			case 'blog_view_list':
				$userinfo['action'] = $vbphrase['viewing_blog_list'];
				break;

			case 'blog_send_friend':
				$userinfo['action'] = $vbphrase['sending_blog_entry_to_friend'];
				$showentry = true;
				break;

			case 'blog_view_ip':
				$userinfo['action'] = $vbphrase['viewing_ip_address'];
				break;

			case 'blog_inlinemod':
				$userinfo['action'] = '<b><i>' . $vbphrase['moderating'] . '</b></i>';
				break;

			case 'blog_new_entry':
				$userinfo['action'] = $vbphrase['posting_blog_entry'];
				break;

			case 'blog_edit_entry':
				$userinfo['action'] = $vbphrase['editing_blog_entry'];
				$showentry = true;
				break;

			case 'blog_new_comment':
				$userinfo['action'] = $vbphrase['posting_blog_comment'];
				$showentry = true;
				break;

			case 'blog_edit_comment':
				$userinfo['action'] = $vbphrase['editing_blog_comment'];
				$showentry = true;
				break;

			case 'blog_edit_trackback':
				$userinfo['action'] = $vbphrase['editing_blog_trackback'];
				$showentry = true;
				break;

			case 'blog_report_entry':
				$userinfo['action'] = $vbphrase['reporting_blog_entry'];
				if ($vbulletin->userinfo['permissions']['wolpermissions'] & $vbulletin->bf_ugp_wolpermissions['canwhosonlinefull'])
				{
					$showentry = true;
				}
				break;

			case 'blog_report_comment':
				$userinfo['action'] = $vbphrase['reporting_blog_comment'];
				if ($vbulletin->userinfo['permissions']['wolpermissions'] & $vbulletin->bf_ugp_wolpermissions['canwhosonlinefull'])
				{
					$showentry = true;
				}
				break;

			case 'blog_subscription':
				$userinfo['action'] = $vbphrase['viewing_blog_subscriptions'];
				$showblog = true;
				break;

			case 'blog_usercp':
				$userinfo['action'] = $vbphrase['viewing_blog_control_panel'];
				break;

			case 'blog_tag':
				$userinfo['action'] = $vbphrase['viewing_blog_tags'];
				$showblog = true;
				break;

			case 'blog_view_members':
				$userinfo['action'] = $vbphrase['viewing_blog_members'];
				$showblog = true;
				break;

			default:
				$handled = false;
		}

		if ($showentry AND $blog AND $entry AND $can_see_blog_title)
		{
			$userinfo['where'] = construct_phrase($vbphrase['blog_x_entry_y'], $blog, $entry);
		}
		else if ($showblog AND $blog AND $can_see_blog)
		{
			$userinfo['where'] = $blog;
		}
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 63620 $
|| ####################################################################
\*======================================================================*/
?>
