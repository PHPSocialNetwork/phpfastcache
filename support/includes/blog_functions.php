<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Blog 4.2.1 - Licence Number VBF02D260D
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # -----------------VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}
require_once(DIR . '/includes/blog_functions_shared.php');

/**
* Fetches information about the selected blog.
*
* @param	integer	The blog entry we want info about
* @param	boolean	If we want to use a cached copy
* @param	boolean	Allow viewing of orphan posts
*
* @return	array|false	Array of information about the blog or false if it doesn't exist
*/
function fetch_bloginfo($blogid, $usecache = true, $vieworphan = false)
{
	global $vbulletin, $show;
	static $blogcache;

	if ($vbulletin->userinfo['userid'] AND in_coventry($vbulletin->userinfo['userid'], true))
	{
		$lastpost_info = ",IF(blog_tachyentry.userid IS NULL, blog.lastcomment, blog_tachyentry.lastcomment) AS lastcomment, " .
			"IF(blog_tachyentry.userid IS NULL, blog.lastcommenter, blog_tachyentry.lastcommenter) AS lastcommenter, " .
			"IF(blog_tachyentry.userid IS NULL, blog.lastblogtextid, blog_tachyentry.lastblogtextid) AS lastblogtextid";

		$tachyjoin = "LEFT JOIN " . TABLE_PREFIX . "blog_tachyentry AS blog_tachyentry ON " .
			"(blog_tachyentry.blogid = blog.blogid AND blog_tachyentry.userid = " . $vbulletin->userinfo['userid'] . ')';
	}
	else
	{
		$lastpost_info = "";
		$tachyjoin = "";
	}

	$blogid = intval($blogid);
	if (!isset($blogcache["$blogid"]) OR !$usecache)
	{
		$deljoinsql = '';
		if (can_moderate_blog() OR $vbulletin->userinfo['userid'])
		{
			$deljoinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_deletionlog AS blog_deletionlog ON (blog.blogid = blog_deletionlog.primaryid AND blog_deletionlog.type = 'blogid')";
		}

		if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']))
		{
			$catjoin = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
			if ($vbulletin->userinfo['userid'])
			{
				$catwhere = "AND (cu.blogcategoryid IS NULL OR blog.userid = " . $vbulletin->userinfo['userid'] . ")";
			}
			else
			{
				$catwhere = "AND cu.blogcategoryid IS NULL";
			}
		}

		$hook_query_fields = $hook_query_joins = $hook_query_where = '';
		($hook = vBulletinHook::fetch_hook('blog_fetch_bloginfo_query')) ? eval($hook) : false;

		$blogcache["$blogid"] = $vbulletin->db->query_first("
			SELECT
				blog.*, blog.options AS blogoptions,
				blog_text.pagetext, blog_text.allowsmilie, blog_text.ipaddress, blog_text.reportthreadid, blog_text.ipaddress AS blogipaddress, blog_text.htmlstate,
				blog_editlog.userid AS edit_userid, blog_editlog.dateline AS edit_dateline, blog_editlog.reason AS edit_reason, blog_editlog.username AS edit_username,
				blog_textparsed.pagetexthtml, blog_textparsed.hasimages,
				user.*, userfield.*, usertextfield.*,
				blog.userid AS userid, bu.categorycache, bu.sidebar, bu.memberids, bu.memberblogids, bu.custompages,
				bu.title AS blog_title, bu.description AS blog_description, bu.allowsmilie AS blog_allowsmilie, bu.akismet_key AS akismet_key, bu.tagcloud,
				bu.options_member, bu.options_guest, bu.options_buddy, bu.options_ignore, bu.entries, bu.isblogmoderator, bu.comments_moderation AS blog_comments_moderation,
				bu.draft AS blog_draft, bu.pending AS blog_pending, bu.uncatentries, bu.moderation AS blog_moderation, bu.deleted AS blog_deleted, bu.customblocks,
				customprofilepic.userid AS profilepic, customprofilepic.dateline AS profilepicdateline, customprofilepic.width AS ppwidth, customprofilepic.height AS ppheight,
				IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, infractiongroupid,
				blog_usercsscache.csscolors AS blog_csscolors, blog_usercsscache.cachedcss AS blog_cachedcss, IF(blog_usercsscache.cachedcss IS NULL, 0, 1) AS blog_hascachedcss, blog_usercsscache.buildpermissions AS blog_cssbuildpermissions
				" . (($vbulletin->userinfo['permissions']['vbblog_customblocks'] > 0) ? ", blockparsed.blocktext" : "") . "
				" . ($vbulletin->userinfo['userid'] ? ", gm.permissions as grouppermissions, blog_rate.vote,ignored.relationid AS ignoreid, buddy.relationid AS buddyid, IF(blog_subscribeuser.blogsubscribeuserid, 1, 0) AS blogsubscribed, IF(blog_subscribeentry.blogsubscribeentryid, 1, 0) AS entrysubscribed, blog_subscribeentry.type AS emailupdate" : "") . "
				" . ($vbulletin->options['avatarenabled'] ? ", avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight" : "") . "
				" . (!($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehiddencustomfields']) ? $vbulletin->profilefield['hidden'] : "") . "
				" . (($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid']) ? ", blog_read.readtime AS blogread, blog_userread.readtime  AS bloguserread" : "") . "
				" . ($deljoinsql ? ",blog_deletionlog.moddelete AS del_moddelete, blog_deletionlog.userid AS del_userid, blog_deletionlog.username AS del_username, blog_deletionlog.reason AS del_reason" : "") . "
				$lastpost_info
				$hook_query_fields
			FROM " . TABLE_PREFIX . "blog AS blog
			INNER JOIN " . TABLE_PREFIX . "blog_text AS blog_text ON (blog_text.blogtextid = blog.firstblogtextid)
			" . ($vieworphan ? "LEFT" : "INNER") . " JOIN " . TABLE_PREFIX . "user AS user ON (blog.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "blog_editlog AS blog_editlog ON (blog_editlog.blogtextid = blog.firstblogtextid)
			LEFT JOIN " . TABLE_PREFIX . "blog_textparsed AS blog_textparsed ON (blog_textparsed.blogtextid = blog.firstblogtextid AND blog_textparsed.styleid = " . intval(STYLEID) . " AND blog_textparsed.languageid = " . intval(LANGUAGEID) . ")
			LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "customprofilepic AS customprofilepic ON (user.userid = customprofilepic.userid)
			LEFT JOIN " . TABLE_PREFIX . "blog_usercsscache AS blog_usercsscache ON (user.userid = blog_usercsscache.userid)
			" . (($vbulletin->userinfo['permissions']['vbblog_customblocks'] > 0) ? "LEFT JOIN " . TABLE_PREFIX . "blog_custom_block_parsed AS blockparsed ON (blockparsed.userid = user.userid AND blockparsed.styleid = " . intval(STYLEID) . " AND blockparsed.languageid = " . intval(LANGUAGEID) . ")" : "") . "
			" . (($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid']) ? "
			LEFT JOIN " . TABLE_PREFIX . "blog_read AS blog_read ON (blog_read.blogid = blog.blogid AND blog_read.userid = " . $vbulletin->userinfo['userid'] . ")
			LEFT JOIN " . TABLE_PREFIX . "blog_userread AS blog_userread ON (blog_userread.bloguserid = blog.userid AND blog_userread.userid = " . $vbulletin->userinfo['userid'] . ")
			" : "") . "
			" . ($vbulletin->userinfo['userid'] ? "
			LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = blog.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')
			LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = blog.userid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')
			" : "") . "
			" . ($vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "blog_subscribeentry AS blog_subscribeentry ON (blog.blogid = blog_subscribeentry.blogid AND blog_subscribeentry.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
			" . ($vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "blog_subscribeuser AS blog_subscribeuser ON (blog.userid = blog_subscribeuser.bloguserid AND blog_subscribeuser.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
			" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid) " : "") . "
			" . ($vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "blog_rate AS blog_rate ON (blog_rate.blogid = blog.blogid AND blog_rate.userid = " . $vbulletin->userinfo['userid'] . ")" : '') . "
			" . ($vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")" : '') . "
			$deljoinsql
			$tachyjoin
			$catjoin
			$hook_query_joins
			WHERE blog.blogid = " . intval($blogid) . "
				$catwhere
				$hook_query_where
		");

		if (!$blogcache["$blogid"])
		{
			return false;
		}

		if (!$blogcache["$blogid"]['blog_title'])
		{
			$blogcache["$blogid"]['blog_title'] = $blogcache["$blogid"]['username'];
		}

		$blogcache["$blogid"] = array_merge($blogcache["$blogid"], convert_bits_to_array($blogcache["$blogid"]['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		$blogcache["$blogid"] = array_merge($blogcache["$blogid"], convert_bits_to_array($blogcache["$blogid"]['options'], $vbulletin->bf_misc_useroptions));
		$blogcache["$blogid"] = array_merge($blogcache["$blogid"], convert_bits_to_array($blogcache["$blogid"]['adminoptions'], $vbulletin->bf_misc_adminoptions));

		cache_permissions($blogcache["$blogid"], false);

		foreach ($vbulletin->bf_misc_vbblogsocnetoptions AS $optionname => $optionval)
		{
			if ($blogcache["$blogid"]['private'])
			{
				$blogcache["$blogid"]["guest_$optionname"] = false;
				$blogcache["$blogid"]["ignore_$optionname"] = false;
				$blogcache["$blogid"]["member_$optionname"] = false;
			}
			else
			{
				$blogcache["$blogid"]["member_$optionname"] = ($blogcache["$blogid"]['options_member'] & $optionval ? 1 : 0);
				$blogcache["$blogid"]["guest_$optionname"] = ($blogcache["$blogid"]['options_guest'] & $optionval ? 1 : 0);
				$blogcache["$blogid"]["ignore_$optionname"] = ($blogcache["$blogid"]['options_ignore'] & $optionval ? 1 : 0);
			}
			$blogcache["$blogid"]["buddy_$optionname"] = ($blogcache["$blogid"]['options_buddy'] & $optionval ? 1 : 0);

			$blogcache["$blogid"]["$optionname"] = (
				(
					(
						!$blogcache["$blogid"]['buddyid']
							OR
						$blogcache["$blogid"]["buddy_$optionname"]
					)
					AND
					(
						!$blogcache["$blogid"]['ignoreid']
							OR
						$blogcache["$blogid"]["ignore_$optionname"]
					)
					AND
					(
						(
							$blogcache["$blogid"]["member_$optionname"]
								AND
							$vbulletin->userinfo['userid']
						)
						OR
						(
							$blogcache["$blogid"]["guest_$optionname"]
								AND
							!$vbulletin->userinfo['userid']
						)
					)
				)
				OR
				(
					$blogcache["$blogid"]["ignore_$optionname"]
						AND
					$blogcache["$blogid"]['ignoreid']
				)
				OR
				(
					$blogcache["$blogid"]["buddy_$optionname"]
						AND
					$blogcache["$blogid"]['buddyid']
				)
				OR
					is_member_of_blog($vbulletin->userinfo, $blogcache["$blogid"])
				OR
					can_moderate_blog()
			) ? true : false;
		}

		fetch_musername($blogcache["$blogid"]);
		prepare_blog_category_permissions($blogcache["$blogid"]);
		$blogcache["$blogid"]['categorycache'] = !empty($blogcache["$blogid"]['categorycache']) ? @unserialize($blogcache["$blogid"]['categorycache']) : array();
		$blogcache["$blogid"]['sidebar'] = !empty($blogcache["$blogid"]['sidebar']) ? @unserialize($blogcache["$blogid"]['sidebar']) : array();
		$blogcache["$blogid"]['sidebar_customblocks'] = !empty($blogcache["$blogid"]['blocktext']) ? @unserialize($blogcache["$blogid"]['blocktext']) : array();
		$blogcache["$blogid"]['custompages'] = !empty($blogcache["$blogid"]['custompages']) ? @unserialize($blogcache["$blogid"]['custompages']) : array();
	}

	// Check category permissions again
	if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']) AND $blogcache["$blogid"]['userid'] != $vbulletin->userinfo['userid'])
	{
		$cats = explode(',', $blogcache["$blogid"]['categories']);
		if (array_intersect($cats, $vbulletin->userinfo['blogcategorypermissions']['cantview']))
		{
			return false;
		}
	}

	($hook = vBulletinHook::fetch_hook('blog_fetch_bloginfo')) ? eval($hook) : false;

	return $blogcache["$blogid"];
}

/**
* Fetches information about the selected blog with permission checks, almost identical to fetch_bloginfo
*
* @param	integer	The blog post we want info about
* @param	mixed		Should a permission check be performed as well
*
* @return	array	Array of information about the blog or prints an error if it doesn't exist / permission problems
*/
function verify_blog($blogid, $alert = true, $perm_check = true)
{
	global $vbulletin, $vbphrase;

	$bloginfo = fetch_bloginfo($blogid);
	if (!$bloginfo)
	{
		if ($alert)
		{
			standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
		}
		else
		{
			return 0;
		}
	}

	if ($perm_check)
	{
		if (
			(
				//belongs to the user and the user can't view own (why?)
				!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] &
					$vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND
				$bloginfo['userid'] == $vbulletin->userinfo['userid']
			) OR
			(
				//does not belong to the user and the user can't view others.
				!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] &
					$vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']) AND
				$bloginfo['userid'] != $vbulletin->userinfo['userid']
			)
		)
		{
			print_no_permission();
		}

		if ($bloginfo['state'] == 'deleted' AND !can_moderate_blog())
		{
			if (!is_member_of_blog($vbulletin->userinfo, $bloginfo) OR $perm_check === 'modifychild')
			{
				// the blog entry is deleted
				standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
			}
		}
		else if (($bloginfo['pending'] OR $bloginfo['state'] == 'draft') AND !is_member_of_blog($vbulletin->userinfo, $bloginfo))
		{
			// can't view a pending/draft if you aren't the author
			standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
		}
		else if ($bloginfo['state'] == 'moderation' AND !can_moderate_blog('canmoderateentries'))
		{
			// the blog entry is awaiting moderation
			if (!is_member_of_blog($vbulletin->userinfo, $bloginfo) OR $perm_check === 'modifychild')
			{
				standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
			}
		}
		else if (in_coventry($bloginfo['userid']) AND !can_moderate_blog())
		{
			standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
		}
		else if (!$bloginfo['canviewmyblog'])	// Check Socnet permissions
		{
			print_no_permission();
		}
	}

	return $bloginfo;
}

/**
* Fetches information about the selected blog text entry
*
* @param	integer	Blogtextid of requested
*
* @return	array|false	Array of information about the blog text or false if it doesn't exist
*/
function fetch_blog_textinfo($blogtextid)
{
	global $vbulletin;
	static $blogtextcache;

	$blogtextid = intval($blogtextid);
	if (!isset($blogtextcache["$blogtextid"]))
	{
		$blogtextcache["$blogtextid"] = $vbulletin->db->query_first("
			SELECT blog_text.*,
				blog_editlog.userid AS edit_userid, blog_editlog.dateline AS edit_dateline, blog_editlog.reason AS edit_reason, blog_editlog.username AS edit_username
			FROM " . TABLE_PREFIX . "blog_text AS blog_text
			LEFT JOIN " . TABLE_PREFIX . "blog_editlog AS blog_editlog ON (blog_editlog.blogtextid = blog_text.blogtextid)
			WHERE blog_text.blogtextid = $blogtextid
		");
	}

	if (!$blogtextcache["$blogtextid"])
	{
		return false;
	}
	else
	{
		return $blogtextcache["$blogtextid"];
	}
}

/**
* Build the metadata for a blog entry
*
* @param	integer	ID of the blog entry
* @param	bool		Remove categories that the owner can't view
* @param	bool		Remove categories that the owner can't post to
*
* @return	void
*/
function build_blog_entry_counters($blogid, $cantview = false, $cantpost = false)
{
	global $vbulletin;

	if (!($blogid = intval($blogid)))
	{
		return;
	}

	$comments = $vbulletin->db->query_first("
		SELECT
			SUM(IF(blog_text.state = 'visible', 1, 0)) AS visible,
			SUM(IF(blog_text.state = 'moderation', 1, 0)) AS moderation,
			SUM(IF(blog_text.state = 'deleted', 1, 0)) AS deleted
		FROM " . TABLE_PREFIX . "blog_text AS blog_text
		INNER JOIN " . TABLE_PREFIX . "blog AS blog USING (blogid)
		WHERE blog_text.blogid = $blogid
			AND blog_text.blogtextid <> blog.firstblogtextid
	");

	$trackback = $vbulletin->db->query_first("
		SELECT
			SUM(IF(state = 'visible', 1, 0)) AS visible,
			SUM(IF(state = 'moderation', 1, 0)) AS moderation
		FROM " . TABLE_PREFIX . "blog_trackback
		WHERE blogid = $blogid
	");

	$vbulletin->db->query_write("
		DELETE FROM " . TABLE_PREFIX . "blog_tachyentry
		WHERE blogid = $blogid
	");

	// read the last posts out of the blog, looking for tachy'd users.
	// if we find one, give them that as the last post but continue looking
	// for the displayed last post.
	$offset = 0;
	$users_processed = array();
	do
	{
		$lastposts = $vbulletin->db->query_first("
			SELECT user.username, blog_text.userid, blog_text.username AS bloguser, blog_text.dateline, blog_text.blogtextid
			FROM " . TABLE_PREFIX . "blog_text AS blog_text
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog_text.userid)
			WHERE blog_text.blogid = $blogid AND
				blog_text.state = 'visible'
			ORDER BY dateline DESC
			LIMIT $offset, 1
		");

		if (in_coventry($lastposts['userid'], true))
		{
			$offset++;

			if (!isset($users_processed["$lastposts[userid]"]))
			{
				$vbulletin->db->query_write("
					REPLACE INTO " . TABLE_PREFIX . "blog_tachyentry
						(userid, lastblogtextid, blogid, lastcomment, lastcommenter)
					VALUES
						($lastposts[userid],
						$lastposts[blogtextid],
						$blogid,
						" . intval($lastposts['dateline']) . ",
						'" . $vbulletin->db->escape_string(empty($lastposts['username']) ? $lastposts['bloguser'] : $lastposts['username']) . "')
				");
				$users_processed["$lastposts[userid]"] = true;
			}
		}
		else
		{
			break;
		}
	}
	while ($lastposts);

	$firstpost = $vbulletin->db->query_first("
		SELECT blog_text.blogtextid, blog_text.userid, user.username, blog_text.username AS bloguser, blog_text.dateline
		FROM " . TABLE_PREFIX . "blog_text AS blog_text
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON user.userid = blog_text.userid
		WHERE blog_text.blogid = $blogid AND
			blog_text.state = 'visible'
		ORDER BY dateline, blogid
		LIMIT 1
	");

	if ($lastposts)
	{
		$lastcommenter = (empty($lastposts['username']) ? $lastposts['bloguser'] : $lastposts['username']);
		$lastcomment = intval($lastposts['dateline']);
		$lastblogtextid = intval($lastposts['blogtextid']);
	}
	else
	{
		// this will occur on a blog posted by a tachy user.
		// since only they will see the blog, the lastpost info can say their name
		$lastcommenter = (empty($firstpost['username']) ? $firstpost['bloguser'] : $firstpost['username']);
		$lastcomment = intval($firstpost['dateline']);
		$lastblogtextid = intval($firstpost['blogtextid']);
	}

	$ratings = $vbulletin->db->query_first("
		SELECT
			COUNT(*) AS ratingnum,
			SUM(vote) AS ratingtotal
		FROM " . TABLE_PREFIX . "blog_rate
		WHERE blogid = $blogid
	");

	$removecats = array();
	// Categories
	$cats = array();
	$categories = $vbulletin->db->query_read("
		SELECT
			cu.blogcategoryid
		" . (($cantview OR $cantpost) ? ", cu.userid, user.usergroupid, user.membergroupids, user.infractiongroupids" : "") . "
		FROM " . TABLE_PREFIX . "blog_categoryuser AS cu
		" . (($cantview OR $cantpost) ? "LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = cu.userid)" : "") . "
		WHERE blogid = $blogid
	");
	while ($category = $vbulletin->db->fetch_array($categories))
	{
		if ($cantview OR $cantpost)
		{
			require_once(DIR . "/includes/blog_functions_shared.php");
			prepare_blog_category_permissions($category);
			if (
				(
					$cantview
						AND
					!empty($category['blogcategorypermissions']['cantview'])
						AND
					in_array($category['blogcategoryid'], $category['blogcategorypermissions']['cantview'])
				)
				OR
				(
					$cantpost
						AND
					!empty($category['blogcategorypermissions']['cantpost'])
						AND
					in_array($category['blogcategoryid'], $category['blogcategorypermissions']['cantpost'])
				)
			)
			{
				$removecats[] = $category['blogcategoryid'];
			}
			else
			{
				$cats[] = $category['blogcategoryid'];
			}
		}
	}

	if (!empty($removecats))
	{
		$vbulletin->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_categoryuser
			WHERE blogid = $blogid AND
				blogcategoryid IN (" . implode(",", $removecats) . ")
		");
	}

	require_once(DIR . '/includes/functions_bigthree.php');
	$coventry = fetch_coventry('string');
	$uniques = $vbulletin->db->query_first("
		SELECT COUNT(DISTINCT(userid)) AS total
		FROM " . TABLE_PREFIX . "blog_text
		WHERE
			blogid = $blogid
				AND
			state = 'visible'
			" . ($coventry ? "AND userid NOT IN ($coventry)" : "") . "
	");
	if (!$uniques['total'])
	{
		$uniques['total'] = 1;
	}

	$bloginfo = array('blogid' => $blogid);
	$blogman =& datamanager_init('Blog', $vbulletin, ERRTYPE_SILENT);
	$blogman->set_existing($bloginfo);

	$blogman->set('lastcomment', $lastcomment);
	$blogman->set('lastcommenter', $lastcommenter);
	$blogman->set('lastblogtextid', $lastblogtextid);
	$blogman->set('comments_visible', $comments['visible'], true, false);
	$blogman->set('comments_moderation', $comments['moderation'], true, false);
	$blogman->set('comments_deleted', $comments['deleted'], true, false);
	$blogman->set('trackback_visible', $trackback['visible'], true, false);
	$blogman->set('trackback_moderation', $trackback['moderation'], true, false);
	$blogman->set('ratingnum', $ratings['ratingnum'], true, false);
	$blogman->set('ratingtotal', $ratings['ratingtotal'], true, false);
	$blogman->set('rating', $ratings['ratingnum'] ? $ratings['ratingtotal'] / $ratings['ratingnum'] : 0, true, false);
	$blogman->set('categories', implode(',', $cats));
	$blogman->set('postercount', $uniques['total']);
	$blogman->save();
}

/**
* Build the metadata for a user's blog
*
* @param	integer	ID of the user
*
* @return	void
*/
function build_blog_user_counters($userid)
{
	global $vbulletin;

	if (!($userid = intval($userid)))
	{
		return;
	}

	$posts = $vbulletin->db->query_first("
		SELECT
			SUM(IF(blog.state = 'visible' AND dateline <= " . TIMENOW . " AND pending = 0, comments_visible, 0)) AS commentcount,
			SUM(IF(blog.state = 'visible' AND dateline <= " . TIMENOW . ", 1, 0) AND pending = 0) AS visible,
			SUM(IF(blog.state = 'moderation', 1, 0)) AS moderation,
			SUM(IF(blog.state = 'deleted', 1, 0)) AS deleted,
			SUM(IF(blog.state = 'draft', 1, 0)) AS draft,
			SUM(IF(blog.state = 'visible' AND dateline <= " . TIMENOW . " AND pending = 0, ratingnum, 0)) AS ratingnum,
			SUM(IF(blog.state = 'visible' AND dateline <= " . TIMENOW . " AND pending = 0, ratingtotal, 0)) AS ratingtotal,
			SUM(IF(blog.dateline > " . TIMENOW . " OR pending = 1, 1, 0)) AS pending,
			SUM(IF(blog.state = 'visible' AND dateline <= " . TIMENOW . " AND pending = 0, comments_moderation, 0)) AS comments_moderation,
			SUM(IF(blog.state = 'visible' AND dateline <= " . TIMENOW . " AND pending = 0, comments_deleted, 0)) AS comments_deleted
		FROM " . TABLE_PREFIX . "blog AS blog
		WHERE blog.userid = $userid
	");

	$lastpost = $vbulletin->db->query_first("
		SELECT title, blogid, dateline, lastcomment, lastcommenter, lastblogtextid
		FROM " . TABLE_PREFIX . "blog AS blog
		WHERE userid = $userid AND
			state = 'visible' AND
			dateline <= " . TIMENOW . " AND
			~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . " AND
			pending = 0
		ORDER BY dateline DESC
		LIMIT 1
	");

	$uncats = $vbulletin->db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "blog AS blog
		LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS blog_categoryuser USING (blogid)
		WHERE blog.userid = $userid AND
			state = 'visible' AND
			dateline <= " . TIMENOW . " AND
			pending = 0 AND
			blogcategoryid IS NULL
	");

	$cache = $cats = $categorydata = array();
	$totals = $vbulletin->db->query_read("
		SELECT
			blog_category.userid, blog_category.blogcategoryid, blog_category.title, blog_category.parentid, blog_category.childlist,
			blog_category.parentlist, COUNT(*) AS entries
		FROM " . TABLE_PREFIX . "blog_categoryuser AS blog_categoryuser
		INNER JOIN " . TABLE_PREFIX . "blog AS blog ON (blog.blogid = blog_categoryuser.blogid)
		INNER JOIN " . TABLE_PREFIX . "blog_category AS blog_category ON (blog_category.blogcategoryid = blog_categoryuser.blogcategoryid)
		WHERE
			blog_categoryuser.userid = $userid AND
			blog.dateline <= " . TIMENOW . " AND
			blog.pending = 0 AND
			blog.state = 'visible'
		GROUP BY blog_categoryuser.blogcategoryid
		HAVING entries > 0
		ORDER BY blog_category.userid, blog_category.displayorder
	");
	while ($total = $vbulletin->db->fetch_array($totals))
	{
		$cache["$total[parentid]"]["$total[blogcategoryid]"] = $total['blogcategoryid'];
		$categorydata["$total[blogcategoryid]"] = $total;
	}

	fetch_user_cat_order($cache, $cats, $categorydata);

	if ($vbulletin->userinfo['userid'] != $userid)
	{
		$userinfo = $vbulletin->db->query_first("
			SELECT bloguserid
			FROM " . TABLE_PREFIX . "blog_user
			WHERE bloguserid = $userid
		");
	}
	else
	{
		$userinfo = array('bloguserid' => $userid);
	}

	$blogman =& datamanager_init('Blog_User', $vbulletin, ERRTYPE_SILENT);
	if ($userinfo['bloguserid'])
	{
		$blogman->set_existing($userinfo);
	}
	else
	{
		$blogman->set('bloguserid', $userid);
	}

	$blogman->set('lastcomment', $lastpost['lastcomment']);
	$blogman->set('lastcommenter', $lastpost['lastcommenter']);
	$blogman->set('lastblogtextid', $lastpost['lastblogtextid']);

	$blogman->set('lastblog', $lastpost['dateline']);
	$blogman->set('lastblogid', $lastpost['blogid']);
	$blogman->set('lastblogtitle', $lastpost['title']);

	$blogman->set('entries', $posts['visible'], true, false);
	$blogman->set('moderation', $posts['moderation'], true, false);
	$blogman->set('deleted', $posts['deleted'], true, false);
	$blogman->set('draft', $posts['draft'], true, false);
	$blogman->set('comments', $posts['commentcount'], true, false);
	$blogman->set('pending', $posts['pending'], true, false);

	$blogman->set('ratingnum', $posts['ratingnum'], true, false);
	$blogman->set('ratingtotal', $posts['ratingtotal'], true, false);
	$blogman->set('rating', $posts['ratingnum'] ? $posts['ratingtotal'] / $posts['ratingnum'] : 0, true, false);

	$blogman->set('comments_moderation', $posts['comments_moderation'], true, false);
	$blogman->set('comments_deleted', $posts['comments_deleted'], true, false);

	$blogman->set('uncatentries', $uncats['total'], true, false);
	$blogman->set('categorycache', @serialize($cats), true, false);
	$blogman->save();
}

/**
* Order categories for build_blog_user_counters
*
* @param	array		Category data, grouped by parentid
* @param	array		Final category data
* @param	array		Category data, grouped by blogcatgeories
* @param	integer	Initial parent forum ID to use
* @param	integer	Initial depth of categories
*
* @return	void
*/
function fetch_user_cat_order(&$cache, &$cats, &$categorydata, $parentid = 0, $depth = 0)
{
	if (is_array($cache["$parentid"]))
	{
		foreach($cache["$parentid"] AS $blogcategoryid)
		{
			$cats["$blogcategoryid"] = $categorydata["$blogcategoryid"];
			$cats["$blogcategoryid"]['depth'] = $depth;
			fetch_user_cat_order($cache, $cats, $categorydata, $blogcategoryid, $depth + 1);
		}
	}
}

/**
* Construct a calendar table for the sidebar
*
* @param	integer		Month
* @param	integer		Year
* @param	integer		Userinfo
*
* @return	string		HTML output
*/
function construct_calendar($month, $year, $userinfo = null)
{
	global $vbulletin, $vbphrase, $vbcollapse, $show;

	require_once(DIR . '/includes/functions_misc.php');

	$months = array(
		1  => 'january',
		2  => 'february',
		3  => 'march',
		4  => 'april',
		5  => 'may',
		6  => 'june',
		7  => 'july',
		8  => 'august',
		9  => 'september',
		10 => 'october',
		11 => 'november',
		12 => 'december'
	);

	$days = array(
		1 => 'sunday',
		2 => 'monday',
		3 => 'tuesday',
		4 => 'wednesday',
		5 => 'thursday',
		6 => 'friday',
		7 => 'saturday',
	);

	if ($userinfo)
	{
		$userid = $userinfo['userid'];
	}

	$monthname = $vbphrase["$months[$month]"];
	//$nextmonth = ($month == 12) ? 1 : $month + 1;
	//$prevmonth = ($month == 1) ? 12 : $month - 1;
	//$nextyear = ($month == 12) ? ($year == 2037 ? 1970 : $year + 1) : $year;
	//$prevyear = ($month == 1) ? ($year == 1970 ? 2037 : $year - 1) : $year;

	$startdate = getdate(gmmktime(12, 0, 0, $month, 1, $year));

	$calendarrows = '';
	// set up which days will be shown
	$vbulletin->userinfo['startofweek'] = ($vbulletin->userinfo['startofweek'] < 1 OR $vbulletin->userinfo['startofweek'] > 7) ? 1 : $vbulletin->userinfo['startofweek'];
	$weekstart = $vbulletin->userinfo['startofweek'];
	for ($i = 0; $i < 7; $i++)
	{
		$dayvarname = 'day' . ($i + 1);
		$$dayvarname = $vbphrase[ $days[$weekstart] . '_short'];
		$weekstart++;
		if ($weekstart == 8)
		{
			$weekstart = 1;
		}
	}

	$curday = 1;
	while (gmdate('w', gmmktime(0, 0, 0, $month, $curday, $year)) + 1 != $vbulletin->userinfo['startofweek'])
	{
		$curday--;
	}
	$totaldays = gmdate('t', gmmktime(0, 0, 0, $month, 1, $year));

	if (
			($totaldays != 30 OR (gmdate('w', gmmktime(0, 0, 0, $month, 30, $year)) + 1) != $vbulletin->userinfo['startofweek'])
		AND
			(
				($totaldays != 31 OR
					(
						gmdate('w', gmmktime(0, 0, 0, $month, 31, $year)) != $vbulletin->userinfo['startofweek']
						 AND
						(gmdate('w', gmmktime(0, 0, 0, $month, 31, $year)) + 1) != $vbulletin->userinfo['startofweek']
					)
				)
			)
		)
	{
		$curday = $curday - 7;
		if ($totaldays == 28 AND gmdate('w', gmmktime(0, 0, 0, $month, 1, $year)) == ($vbulletin->userinfo['startofweek'] - 1))
		{
			$curday = $curday - 7;
		}
	}

	$sql1 = array();
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		$sql[] = "userid = " . $vbulletin->userinfo['userid'];
	}
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND $vbulletin->userinfo['userid'])
	{
		if (!empty($sql))
		{	// can't view own blog or others' blog
			// This condition should not be reachable
			$sql1[] = "1 <> 1";
		}
		else
		{
			$sql1[] = "blog.userid <> " . $vbulletin->userinfo['userid'];
		}
	}

	$state = array('visible');
	if (can_moderate_blog('canmoderateentries'))
	{
		$state[] = 'moderation';
	}
	if (can_moderate_blog())
	{
		$state[] = 'deleted';
	}

	$sql1join = array();
	if (!can_moderate_blog())
	{
		$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog.userid)";

		if ($vbulletin->userinfo['userid'])
		{
			$userlist_sql = array();
			$userlist_sql[] = "(options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND ignored.relationid IS NOT NULL)";
			$userlist_sql[] = "(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)";
			$userlist_sql[] = "(options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR buddy.relationid IS NULL) AND (options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL))";
			$sql1[] = "(" . implode(" OR ", $userlist_sql) . ")";

			$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = blog.userid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')";
			$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = blog.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')";

			$sql1[] = "(~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . "
					OR
				(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL))";
		}
		else
		{
			$sql1[] = "options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			$sql1[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
		}
	}

	$prevdays = 1;
	while (gmdate('w', gmmktime(0, 0, 0, $month + 1, $prevdays, $year)) + 1 != $vbulletin->userinfo['startofweek'])
	{
		$prevdays--;
	}

	$adddays = 0;
	if ($prevdays <= 0)
	{
		$adddays = $prevdays + 6;
	}

	require_once(DIR . '/includes/functions_misc.php');
	$starttime = vbmktime(0, 0, 0, $month, $curday, $year);
	$endtime = vbmktime(0, 0, 0, $month + 1, 1 + $adddays, $year);
	$endtime = ($endtime > TIMENOW) ? TIMENOW : $endtime;

	$sql1[] = "state IN('" . implode("', '", $state) . "')";
	$sql1['date1'] = "dateline >= $starttime";
	$sql1['date2'] = "dateline < $endtime";
	if ($userinfo['userid'])
	{
		$sql1[] = "blog.userid = $userinfo[userid]";
	}

	$sql2 = array();
	if ($userinfo AND is_member_of_blog($vbulletin->userinfo, $userinfo))
	{
		$sql2[] = "blog.userid = $userinfo[userid]";
		$sql2['date1'] = "dateline >= $starttime";
		$sql2['date2'] = "dateline < " . vbmktime(0, 0, 0, $month + 1, 1 + $adddays, $year);
	}
	else if (!$userinfo AND $vbulletin->userinfo['userid'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
	{
		// blogs that I am a member of here ....
		$sql2[] = "blog.userid IN (" . $vbulletin->userinfo['memberblogids'] . ")";
		$sql2['date1'] = "dateline >= $starttime";
		$sql2['date2'] = "dateline < " . vbmktime(0, 0, 0, $month + 1, 1 + $adddays, $year);
	}

	if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']))
	{
		$sql1join[] = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
		$sql1[] = "cu.blogcategoryid IS NULL";
	}

	$blogcache = array();
	$blogs = $vbulletin->db->query_read_slave("
		" . (!empty($sql2) ? "(" : "") . "
			SELECT COUNT(*) AS total,
			FROM_UNIXTIME(dateline - " . $vbulletin->options['hourdiff'] . ", '%c-%e-%Y') AS period
			FROM " . TABLE_PREFIX . "blog AS blog
			" . (!empty($sql1join) ? implode("\r\n", $sql1join) : "") . "
			WHERE " . implode(" AND ", $sql1) . "
			GROUP BY period
		" . (!empty($sql2) ? ") UNION (
			SELECT COUNT(*) AS total,
			FROM_UNIXTIME(dateline - " . $vbulletin->options['hourdiff'] . ", '%c-%e-%Y') AS period
			FROM " . TABLE_PREFIX . "blog AS blog
			WHERE " . implode(" AND ", $sql2) . "
			GROUP BY period
		)" : "") . "
	");
	while ($blog = $vbulletin->db->fetch_array($blogs))
	{
		$blogcache["$blog[period]"] += $blog['total'];
	}
	$today = getdate(TIMENOW - $vbulletin->options['hourdiff']);
	while (!$monthcomplete)
	{
		$calendarrows .= '<tr>';
		for ($i = 0; $i < 7; $i++)
		{
			if ($curday <= 0)
			{
				$currentmonth = ($month - 1 == 0) ? 12 : $month - 1;
				$currentyear = ($currentmonth == 12) ? $year - 1 : $year;
			}
			else if ($curday > $totaldays)
			{
				$currentmonth = ($month + 1 > 12) ? 1 : $month + 1;
				$currentyear = ($currentmonth == 1) ? $year + 1 : $year;
			}
			else
			{
				$currentmonth = $month;
				$currentyear = $year;
			}

			$day = gmdate('j', gmmktime(0, 0, 0, $month, $curday, $year));
			$show['thismonth'] = ($curday > 0 AND $curday <= $totaldays) ? true : false;
			$show['highlighttoday'] = ($currentmonth == $today['mon'] AND $currentyear == $today['year'] AND $day == $today['mday'] AND $show['thismonth']) ? true : false;

			$show['daylink'] = false;
			if (!empty($blogcache["$currentmonth-$day-$currentyear"]))
			{
				$total = $blogcache["$currentmonth-$day-$currentyear"];
				$show['daylink'] = true;
			}

			$curday++;
			$templater = vB_Template::create('blog_sidebar_calendar_day');
				$templater->register('pageinfo_current', array('m' => $currentmonth, 'y' => $currentyear, 'd' => $day));
				$templater->register('userinfo', $userinfo);
				$templater->register('currentmonth', $currentmonth);
				$templater->register('currentyear', $currentyear);
				$templater->register('day', $day);
				$templater->register('userid', $userid);
				$templater->register('total', $total);
			$calendarrows .= $templater->render();
		}
		$calendarrows .= '</tr>';

		if ($curday > $totaldays)
		{
			$monthcomplete = true;
		}
	}

	unset($sql1['date1'], $sql1['date2'], $sql2['date1'], $sql2['date2']);
	$starttime = vbmktime(23, 59, 59, $month, 0, $year);
	$endtime = vbmktime(0, 0, 0, $month + 1, 1, $year);
	$show['nextmonth'] = $show['prevmonth'] = false;

	// Get first event before this month
	$sql1['date1'] = "dateline <= $starttime";
	if ($sql2)
	{
		$sql2['date1'] = "dateline <= $starttime";
	}
	$preventries = $vbulletin->db->query_read_slave("
		" . (!empty($sql2) ? "(" : "") . "
			SELECT MAX(dateline) AS dateline
			FROM " . TABLE_PREFIX . "blog AS blog
			" . (!empty($sql1join) ? implode("\r\n", $sql1join) : "") . "
			WHERE " . implode(" AND ", $sql1) . "
		" . (!empty($sql2) ? ") UNION (
			SELECT MAX(dateline) AS dateline
			FROM " . TABLE_PREFIX . "blog AS blog
			WHERE " . implode(" AND ", $sql2) . "
		)" : "") . "
	");

	$preventry = 0;
	while ($entry = $vbulletin->db->fetch_array($preventries))
	{
		if ($entry['dateline'] AND ($entry['dateline'] > $preventry OR !$preventry))
		{
			$preventry = $entry['dateline'];
		}
	}
	if ($preventry)
	{
		$prevmonth = vbdate('n', $preventry);
		$prevyear = vbdate('Y', $preventry);
		$show['prevmonth'] = true;
	}

	if (
		// Member of blog, viewing blog
		($userinfo AND is_member_of_blog($vbulletin->userinfo, $userinfo))
			OR
		// Registered user viewing front page
		(!$userinfo AND $vbulletin->userinfo['userid'])
			OR
		($year < $today['year'])
			OR
		($month < $today['mon'])
	)
	{
		// Get first event after this month
		unset($sql1['date1'], $sql2['date1']);
		$sql1['date1'] = "dateline >= $endtime";
		if ($sql2)
		{
			$sql2['date1'] = "dateline >= $endtime";
		}
		$postentries = $vbulletin->db->query_read_slave("
			" . (!empty($sql2) ? "(" : "") . "
				SELECT MIN(dateline) AS dateline
				FROM " . TABLE_PREFIX . "blog AS blog
				" . (!empty($sql1join) ? implode("\r\n", $sql1join) : "") . "
				WHERE " . implode(" AND ", $sql1) . "
			" . (!empty($sql2) ? ") UNION (
				SELECT MIN(dateline) AS dateline
				FROM " . TABLE_PREFIX . "blog AS blog
				WHERE " . implode(" AND ", $sql2) . "
			)" : "") . "
		");

		$postentry = 0;
		while ($entry = $vbulletin->db->fetch_array($postentries))
		{
			if ($entry['dateline'] AND ($entry['dateline'] < $postentry OR !$postentry))
			{
				$postentry = $entry['dateline'];
			}
		}
		if ($postentry)
		{
			$nextmonth = vbdate('n', $postentry);
			$nextyear = vbdate('Y', $postentry);
			$show['nextmonth'] = true;
		}
	}

	$templater = vB_Template::create('blog_sidebar_calendar');
		$templater->register('pageinfo_prev', array('m' => $prevmonth, 'y' => $prevyear));
		$templater->register('pageinfo_next', array('m' => $nextmonth, 'y' => $nextyear));
		$templater->register('pageinfo_current', array('m' => $month, 'y' => $year));
		$templater->register('userinfo', $userinfo);
		$templater->register('userid', $userid);
		$templater->register('prevmonth', $prevmonth);
		$templater->register('prevyear', $prevyear);
		$templater->register('nextmonth', $nextmonth);
		$templater->register('nextyear', $nextyear);
		$templater->register('month', $month);
		$templater->register('year', $year);
		$templater->register('monthname', $monthname);
		$templater->register('calendarrows', $calendarrows);
		for ($x = 1; $x <= 7; $x++)
		{
			$templater->register("day$x", ${day . $x});
		}
	return $templater->render();
}

/**
* Build the blog statistics for sidebar
*
* @return	void
*/
function build_blog_stats()
{
	global $vbulletin;

	$blogstats = array();

	$total_blog_users = $vbulletin->db->query_first_slave("
		SELECT COUNT(DISTINCT userid) AS total
		FROM " . TABLE_PREFIX . "blog WHERE state = 'visible'
	");

	$total_blog_entries = $vbulletin->db->query_first_slave("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "blog
		WHERE state = 'visible'
			AND dateline <= " . TIMENOW . "
			AND pending = 0
	");

	$entries_in_24hours = $vbulletin->db->query_first_slave("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "blog
		WHERE state = 'visible'
			AND (dateline > " . (TIMENOW - (24 * 3600)) . "
			AND dateline <= " . TIMENOW . ")
			AND pending = 0
	");

	if ($lastentry = $vbulletin->db->query_first_slave("
		SELECT
			user.username,
			blog.userid, blog.title, blog.blogid, blog.categories, blog.postedby_username, blog.postedby_userid,
			bu.title AS blogtitle, bu.options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AS guestcanview
		FROM " . TABLE_PREFIX . "blog AS blog
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (blog.userid = bu.bloguserid)
		INNER JOIN " . TABLE_PREFIX . "user AS user ON (blog.userid = user.userid)
		WHERE
			state = 'visible' AND
			dateline <= " . TIMENOW . " AND
			blog.pending = 0 AND
			~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . " AND
			bu.options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND
			bu.options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . "
		ORDER BY dateline DESC
		LIMIT 1
	"))
	{
		$blogstats['lastentry'] = $lastentry;

		$guestuser = array(
			'userid'      => 0,
			'usergroupid' => 0,
		);
		cache_permissions($guestuser, false);
		prepare_blog_category_permissions($guestuser);
		$entrycats = explode(',', $lastentry['categories']);

		if (
				(
					array_intersect($guestuser['blogcategorypermissions']['cantview'], $entrycats)
						OR
					!$lastentry['guestcanview']
				)
					AND
				$guestuser['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']
					AND
				$guestuser['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']
		)
		{
			$blogstats['lastentry']['guestcanview'] = false;

			if (!empty($guestuser['blogcategorypermissions']['cantview']))
			{
				$joinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $guestuser['blogcategorypermissions']['cantview']) . "))";
				$wheresql = "AND cu.blogcategoryid IS NULL";
			}

			if ($lastentry_guest = $vbulletin->db->query_first_slave("
				SELECT user.username, blog.userid, blog.title, blog.blogid, blog.categories, blog.postedby_userid, blog.postedby_username, bu.title AS blogtitle
				FROM " . TABLE_PREFIX . "blog AS blog
				LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (blog.userid = bu.bloguserid)
				INNER JOIN " . TABLE_PREFIX . "user AS user ON (blog.userid = user.userid)
				$joinsql
				WHERE
					state = 'visible' AND
					dateline <= " . TIMENOW . " AND
					blog.pending = 0 AND
					~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . " AND
					bu.options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND
					bu.options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND
					bu.options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . "
					$wheresql
				ORDER BY dateline DESC
				LIMIT 1
			"))
			{
				$blogstats['lastentry_guest'] = $lastentry_guest;
			}
		}
	}

	$blogstats['total_blog_users'] = $total_blog_users['total'];
	$blogstats['total_blog_entries'] = $total_blog_entries['total'];
	$blogstats['entries_in_24hours'] = $entries_in_24hours['total'];

	build_datastore('blogstats', serialize($blogstats), 1);

	return $blogstats;
}

/**
* Constructs the avatar code for display on the blog page
*
* @param	array	vBulletin userinfo array
*
* @return	void
*/
function fetch_avatar_html(&$userinfo, $thumb = false)
{
	global $vbulletin, $show;

	// get avatar
	if ($userinfo['avatarid'])
	{
		$userinfo['avatarurl'] = $userinfo['avatarpath'];
	}
	else
	{
		if ($userinfo['hascustomavatar'] AND $vbulletin->options['avatarenabled'])
		{
			if ($vbulletin->options['usefileavatar'])
			{
				$userinfo['avatarurl'] = $vbulletin->options['avatarurl'] . ($thumb ? '/thumbs' : '') . '/avatar' . $userinfo['userid'] . '_' . $userinfo['avatarrevision'] . '.gif';
			}
			else
			{
				$userinfo['avatarurl'] = 'image.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userinfo['userid'] . '&amp;dateline=' . $userinfo['avatardateline']. ($thumb ? '&amp;type=thumb' : '');
			}

			$userinfo['avwidthpx'] = intval($userinfo['avwidth']);
			$userinfo['avheightpx'] = intval($userinfo['avheight']);

			if ($userinfo['avwidth'] AND $userinfo['avheight'])
			{
				$userinfo['avwidth'] = 'width="' . $userinfo['avwidth'] . '"';
				$userinfo['avheight'] = 'height="' . $userinfo['avheight'] . '"';
			}
			else
			{
				$userinfo['avwidth'] = '';
				$userinfo['avheight'] = '';
			}
		}
		else
		{
			$userinfo['avatarurl'] = '';
		}
	}

	if (empty($userinfo['permissions']))
	{
		cache_permissions($userinfo, false);
	}

	if ( // no avatar defined for this user
		empty($userinfo['avatarurl'])
		OR // visitor doesn't want to see avatars
		($vbulletin->userinfo['userid'] > 0 AND !$vbulletin->userinfo['showavatars'])
		OR // user has a custom avatar but no permission to display it
		(!$userinfo['avatarid'] AND !($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar']) AND !$userinfo['adminavatar']) //
	)
	{
		$show['avatar'] = false;
	}
	else
	{
		$show['avatar'] = true;
	}
}

/**
* Constructs the profile pic code for display on the blog page
*
* @param	array	vBulletin userinfo array
*
* @return	void
*/
function fetch_profilepic_html(&$userinfo)
{
	global $vbulletin, $show;

	if (empty($userinfo['permissions']))
	{
		cache_permissions($userinfo, false);
	}

	if ($vbulletin->options['profilepicenabled'] AND $userinfo['profilepic'] AND ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseeprofilepic'] OR $vbulletin->userinfo['userid'] == $userinfo['userid']) AND ($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canprofilepic'] OR $userinfo['adminprofilepic']))
	{
		// Kill the comparison code in the blog for vB 4.0
		if (version_compare($vbulletin->options['templateversion'], '3.8.0', '>='))
		{
			require_once(DIR . '/includes/functions_user.php');
			if (!can_view_profile_section($userinfo['userid'], 'profile_picture'))
			{
				$show['profilepic'] = false;
				return;
			}
		}

		if ($vbulletin->options['usefileavatar'])
		{
			$userinfo['profilepicurl'] = $vbulletin->options['profilepicurl'] . '/profilepic' . $userinfo['userid'] . '_' . $userinfo['profilepicrevision'] . '.gif';
		}
		else
		{
			$userinfo['profilepicurl'] = 'image.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userinfo['userid'] . "&amp;dateline=$userinfo[profilepicdateline]&amp;type=profile";
		}

		$userinfo['ppwidthpx'] = intval($userinfo['ppwidth']);
		$userinfo['ppheightpx'] = intval($userinfo['ppheight']);

		if ($userinfo['ppwidthpx'] AND $userinfo['ppheightpx'])
		{
			$userinfo['ppwidth'] = 'width="' . $userinfo['ppwidthpx'] . '"';
			$userinfo['ppheight'] = 'height="' . $userinfo['ppheightpx'] . '"';
		}
		else
		{
			$userinfo['ppwidth'] = '';
			$userinfo['ppheight'] = '';
		}
		$show['profilepic'] = true;
	}
	else
	{
		$userinfo['profilepicurl'] = '';
		$show['profilepic'] = false;
	}
}

/**
* Constructs the blog overview sidebar
*
* @param	integer	The month to show the calendar for
* @param	integer	The year to show the calendar for
*
* @return	string	HTML for sidebar
*/
function &build_overview_sidebar($month = 0, $year = 0)
{
	global $vbulletin, $show, $vbphrase, $vbcollapse, $ad_location;

	($hook = vBulletinHook::fetch_hook('blog_sidebar_generic_start')) ? eval($hook) : false;

	$month = ($month < 1 OR $month > 12) ? vbdate('n', TIMENOW, false, false) : $month;
	$year = ($year > 2037 OR $year < 1970) ? vbdate('Y', TIMENOW, false, false) : $year;

	if ($vbulletin->blogstats === NULL)
	{
		$vbulletin->blogstats = build_blog_stats();
	}

	$blogstats = $vbulletin->blogstats;
	foreach ($blogstats AS $key => $value)
	{
		if (is_numeric($value))
		{
			$blogstats["$key"] = vb_number_format($value);
		}
	}

	//########################### Get Category Bits #####################################
	$categorybits = '';
	if (!empty($vbulletin->blogcategorycache))
	{
		$beenhere = $prevdepth = 0;
		foreach ($vbulletin->blogcategorycache AS $category)
		{
			$show['ul'] = false;

			$category['title'] = $vbphrase['category' . $category['blogcategoryid'] . '_title'];
			if (!($vbulletin->userinfo['blogcategorypermissions']["$category[blogcategoryid]"] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewcategory']))
			{
				continue;
			}

			$indentbits = '';
			if ($category['depth'] == $prevdepth AND $beenhere)
			{
				$indentbits = '</li>';
			}
			else if ($category['depth'] > $prevdepth)
			{
				// Need an UL
				$show['ul'] = true;
			}
			else if ($category['depth'] < $prevdepth)
			{
				for ($x = ($prevdepth - $category['depth']); $x > 0; $x--)
				{
					$indentbits .= '</li></ul>';
				}
				$indentbits .= '</li>';
			}

			$show['catlink'] = ($vbulletin->GPC['blogcategoryid'] != $category['blogcategoryid']) ? true : false;

			$templater = vB_Template::create('blog_sidebar_category_link');
				$templater->register('category', $category);
				$templater->register('blog', $blog);
				$templater->register('pageinfo', array('blogcategoryid' => $category['blogcategoryid']));
			$sidebar['categorybits'] .= $indentbits . $templater->render();
			$prevdepth = $category['depth'];
			$beenhere = true;
		}

		if ($sidebar['categorybits'])
		{
			for ($x = $prevdepth; $x > 0; $x--)
			{
				$sidebar['categorybits'] .= '</li></ul>';
			}
			$sidebar['categorybits'] .= '</li>';
		}
	}

	$calendar = construct_calendar($month, $year);

	$tag_cloud = fetch_blog_tagcloud('usage', true);

	$show['postblog'] = ($vbulletin->userinfo['userid'] AND $vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpost'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']);
	$show['gotoblog'] = ($vbulletin->userinfo['userid'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']);
	$show['rssfeed'] = ($vbulletin->usergroupcache['1']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']) ? true : false;
	$show['hidepostblogbutton'] = (THIS_SCRIPT == 'blog_post' AND in_array($_REQUEST['do'], array('editblog', 'newblog', 'comment')));

	set_sidebar_ads($ad_location, $show);

	($hook = vBulletinHook::fetch_hook('blog_sidebar_generic_end')) ? eval($hook) : false;

	$templater = vB_Template::create('blog_sidebar_generic');
		$templater->register('ad_location', $ad_location);
		$templater->register('blogstats', $blogstats);
		$templater->register('sidebar', $sidebar);
		$templater->register('calendar', $calendar);
		$templater->register('month', $month);
		$templater->register('year', $year);
		$templater->register('tag_cloud', $tag_cloude);
	return $templater->render();
}

/**
* Constructs the blog sidebar specific for a user's blog
*
* @param	array	userinfo array
* @param	integer	The month to show the calendar for
* @param	integer	The year to show the calendar for
* @param	boolean	Should posting rules be shown in the sidebar
*
* @return	string	HTML for sidebar
*/
function &build_user_sidebar(&$userinfo, $month = 0, $year = 0, $rules = false)
{
	global $vbulletin, $show, $vbphrase, $vbcollapse, $headinclude, $ad_location, $blogrssinfo;

	($hook = vBulletinHook::fetch_hook('blog_sidebar_user_start')) ? eval($hook) : false;

	$sidebar = array();

	$blockorder = $userinfo['sidebar'];
	$freeblocks = array();

	if ($userinfo['customblocks'] AND $userinfo['permissions']['vbblog_customblocks'] > 0)
	{
		if (count($userinfo['sidebar_customblocks']) != $userinfo['customblocks'])
		{
			$customblock = array();
			$customblocks = $vbulletin->db->query_read_slave("
				SELECT customblockid, pagetext, allowsmilie, title
				FROM " . TABLE_PREFIX . "blog_custom_block
				WHERE
					userid = " . $userinfo['userid'] . "
						AND
					type = 'block'
			");
			while ($blockholder = $vbulletin->db->fetch_array($customblocks))
			{
				$userinfo['sidebar_customblocks']["$blockholder[customblockid]"] = array(
					'pagetext'    => $blockholder['pagetext'],
					'title'       => $blockholder['title'],
					'allowsmilie' => $blockholder['allowsmilie'],
				);
			}
		}

		$blocktext = array();
		require_once(DIR . '/includes/class_bbcode_blog.php');
		$bbcode = new vB_BbCodeParser_Blog($vbulletin, fetch_tag_list());
		$bbcode->set_parse_userinfo($userinfo, $userinfo['permissions']);
		foreach ($userinfo['sidebar_customblocks'] AS $customblockid => $blockinfo)
		{
			$customblock["custom$customblockid"] = array(
				'message'   => $bbcode->parse(
					$blockinfo['pagetext'],
					'blog_entry',
					$blockinfo['allowsmilie'],
					false,
					$blockinfo['parsedtext'],
					$blockinfo['hasimages'],
					true
				),
				'title'     => $blockinfo['title'],
				'customblockid' => $customblockid,
			);

			if ($bbcode->cached['text'])
			{
				$blocktext["$customblockid"] = array(
					'parsedtext'  => $bbcode->cached['text'],
					'title'       => $blockinfo['title'],
					'allowsmilie' => $blockinfo['allowsmilie'],
					'hasimages'   => $bbcode->cached['has_images'],
				);
			}
		}
		unset($bbcode);

		if (!empty($blocktext))
		{
			$vbulletin->db->shutdown_query("
				REPLACE INTO " . TABLE_PREFIX . "blog_custom_block_parsed (userid, styleid, languageid, blocktext)
				VALUES ($userinfo[userid], " . (STYLEID) . ", " . (LANGUAGEID) . ", '" . $vbulletin->db->escape_string(serialize($blocktext)) . "')
			");
		}
	}

	if ($vbulletin->userinfo['permissions']['vbblog_customblocks'])
	{
		$show['editsidebar'] = true;
	}
	if ($vbulletin->userinfo['permissions']['vbblog_custompages'])
	{
		$show['editcustompage'] = true;
	}
	$useblock = array();
	foreach ($vbulletin->bf_misc_vbblogblockoptions AS $key => $value)
	{
		if ($vbulletin->options['vbblog_blocks'] & $value)
		{
			switch ($key)
			{
				case 'block_archive':
				case 'block_category':
				case 'block_comments':
				case 'block_entries':
				case 'block_visitors':
					$show['editsidebar'] = true;
					break;
				case 'block_search':
					if ($show['blog_search'])
					{
						$show['editsidebar'] = true;
					}
					break;
				case 'block_tagcloud':
					if ($vbulletin->options['vbblog_tagging'])
					{
						$show['editsidebar'] = true;
					}
					break;
				default:
					($hook = vBulletinHook::fetch_hook('blog_sidebar_user_block')) ? eval($hook) : false;
			}
			if (!isset($blockorder["$key"]))
			{
				$freeblocks["$key"] = 1;
				$useblock["$key"] = true;
			}
			else
			{
				$useblock["$key"] = $blockorder["$key"];
			}
		}
		else
		{
			if (preg_match('#^block_#', $key))
			{
				$useblock["$key"] = true;
				$blockorder["$key"] = 0;
			}
		}
	}
	if (!empty($freeblocks))
	{
		$blockorder = array_merge($blockorder, $freeblocks);
	}

	if ($useblock['block_archive'])
	{
		$month = ($month < 1 OR $month > 12) ? vbdate('n', TIMENOW, false, false) : $month;
		$year = ($year > 2037 OR $year < 1970) ? vbdate('Y', TIMENOW, false, false) : $year;
		$show['moveable'] = ($blockorder['block_archive']);
		$sidebar['calendar'] = construct_calendar($month, $year, $userinfo);
	}

	fetch_avatar_html($userinfo);
	fetch_profilepic_html($userinfo);

	$userinfo['joindate'] = vbdate($vbulletin->options['registereddateformat'], $userinfo['joindate']);
	$userinfo['posts'] = vb_number_format($userinfo['posts']);
	$userinfo['entries'] = vb_number_format($userinfo['entries']);

	// ########################## Get Recent Visitors #########################################
	if ($useblock['block_visitors'])
	{
		if ($vbulletin->options['profilemaxvisitors'] < 2)
		{
			$vbulletin->options['profilemaxvisitors'] = 2;
		}

		$hook_query_fields = $hook_query_joins = $hook_query_where = '';
		($hook = vBulletinHook::fetch_hook('blog_sidebar_user_visitors_query')) ? eval($hook) : false;

		// DISTINCT is nasty so add 5 to the limit as a fudge factor against pulling the same user twice (users can appear twice
		// due to stat tracking)
		$visitors_db = $vbulletin->db->query_read_slave("
			SELECT user.userid, user.username, user.usergroupid, user.displaygroupid, blog_visitor.visible
				$hook_query_fields
			FROM " . TABLE_PREFIX . "blog_visitor AS blog_visitor
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog_visitor.visitorid)
			$hook_query_joins
			WHERE blog_visitor.userid = $userinfo[userid]
				" . (!($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehidden']) ? " AND (visible = 1 OR blog_visitor.visitorid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
				$hook_query_where
			ORDER BY blog_visitor.dateline DESC
			LIMIT " . ($vbulletin->options['profilemaxvisitors'] + 5) . "
		");

		$visitors = array();
		while ($user = $vbulletin->db->fetch_array($visitors_db))
		{
			if (count($visitors) == $vbulletin->options['profilemaxvisitors'])
			{
				break;
			}
			$visitors["$user[username]"] = $user;
		}

		uksort($visitors, 'strnatcasecmp');

		if ($vbulletin->userinfo['buddylist'] = trim($vbulletin->userinfo['buddylist']))
		{
			$buddylist = preg_split('/\s+/', $vbulletin->userinfo['buddylist'], -1, PREG_SPLIT_NO_EMPTY);
		}
		else
		{
			$buddylist = array();
		}

		$visitorcount = 0;
		$visitorbits = array();
		foreach ($visitors AS $user)
		{
			fetch_musername($user);
			$user['invisiblemark'] = !$user['visible'] ? '*' : '';
			$user['buddymark'] = in_array($user['userid'], $buddylist) ? '+' : '';

			($hook = vBulletinHook::fetch_hook('blog_sidebar_user_visitors_loop')) ? eval($hook) : false;

			$visitorcount++;
			$user['comma'] = $vbphrase['comma_space'];
			$visitorbits[$visitorcount] = $user;
		}

		// Last element
		if ($visitorcount)
		{
			$visitorbits[$visitorcount]['comma'] = '';
		}

		$sidebar['visitorbits'] = $visitorbits;
		$sidebar['visitorcount'] = $visitorcount;
	}

	//########################### Get Recent Comments #####################################
	if ($useblock['block_comments'])
	{
		$commentbits = '';
		$wheresql = array();
		$blogtextstate = array('visible');
		if (can_moderate_blog('canmoderatecomments') OR is_member_of_blog($vbulletin->userinfo, $userinfo))
		{
			$blogtextstate[] = 'moderation';
		}

		$blogstate = array('visible');
		if (can_moderate_blog('canmoderateentries') OR is_member_of_blog($vbulletin->userinfo, $userinfo))
		{
			$blogstate[] = 'moderation';
		}

		$wheresql = array(
			"blog.userid = $userinfo[userid]",
			"blog_text.blogtextid <> blog.firstblogtextid",
			"blog_text.state IN ('" . implode("','", $blogtextstate) . "')",
			"blog.state IN ('" . implode("','", $blogstate) . "')",
			"blog.dateline <= " . TIMENOW,
			"blog.pending = 0",
		);

		if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']) AND $userinfo['userid'] != $vbulletin->userinfo['userid'])
		{
			$joinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
			$wheresql[] = "cu.blogcategoryid IS NULL";
		}
		if (!can_moderate_blog() AND !is_member_of_blog($vbulletin->userinfo,  $userinfo) AND !$userinfo['buddyid'])
		{
			$wheresql[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
		}

		$hook_query_fields = $hook_query_joins = $hook_query_where = '';
		($hook = vBulletinHook::fetch_hook('blog_sidebar_user_comments_query')) ? eval($hook) : false;

		$comments = $vbulletin->db->query_read("
			SELECT blog.blogid, lastblogtextid AS blogtextid, blog_text.userid, blog_text.state, IF(blog_text.userid = 0, blog_text.username, user.username) AS username, blog.blogid, blog.title
			" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, user.avatarrevision, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
			$hook_query_fields
			FROM " . TABLE_PREFIX . "blog AS blog
			LEFT JOIN " . TABLE_PREFIX . "blog_text AS blog_text ON (blog.lastblogtextid = blog_text.blogtextid)
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON (blog_text.userid = user.userid)
			INNER JOIN " . TABLE_PREFIX . "user AS user2 ON (blog.userid = user2.userid)
			" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
			$joinsql
			$hook_query_joins
			WHERE " . implode(" AND ", $wheresql) . "
			$hook_query_where
			ORDER BY blog.lastcomment DESC
			LIMIT 5
		");

		while ($comment = $vbulletin->db->fetch_array($comments))
		{
			$show['deleted'] = ($comment['state'] == 'deleted') ? true : false;
			$show['moderation'] = ($comment['state'] == 'moderation') ? true : false;

			fetch_avatar_html($comment, true);

			($hook = vBulletinHook::fetch_hook('blog_sidebar_user_comments_loop')) ? eval($hook) : false;

			$templater = vB_Template::create('blog_sidebar_comment_link');
				$templater->register('comment', $comment);
				$templater->register('pageinfo', array('bt' => $comment['blogtextid']));
			$sidebar['commentbits'] .= $templater->render();
		}
	}

	//########################### Get Recent Entries #####################################
	if ($useblock['block_entries'])
	{
		$wheresql = array();
		$state = array('visible');

		if (can_moderate_blog('canmoderateentries') OR is_member_of_blog($vbulletin->userinfo, $userinfo))
		{
			$state[] = 'moderation';
		}

		if (is_member_of_blog($vbulletin->userinfo, $userinfo))
		{
			$state[] = 'draft';
		}
		else
		{
			$wheresql[] = "blog.dateline <= " . TIMENOW;
			$wheresql[] = "blog.pending = 0";
		}

		$wheresql[] = "blog.userid = $userinfo[userid]";
		$wheresql[] = "blog.state IN ('" . implode("','", $state) . "')";

		if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']) AND $userinfo['userid'] != $vbulletin->userinfo['userid'])
		{
			$joinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
			$wheresql[] = "cu.blogcategoryid IS NULL";
		}

		if (!can_moderate_blog() AND !is_member_of_blog($vbulletin->userinfo, $userinfo) AND !$userinfo['buddyid'])
		{
			$wheresql[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
		}

		$hook_query_fields = $hook_query_joins = $hook_query_where = '';
		($hook = vBulletinHook::fetch_hook('blog_sidebar_user_entries_query')) ? eval($hook) : false;

		// Recent Entries
		$entries = $vbulletin->db->query_read_slave("
			SELECT blog.blogid, blog.title, blog.dateline, blog.state, blog.pending
			" . ($deljoinsql ? ",blog_deletionlog.primaryid" : "") . "
			$hook_query_fields
			FROM " . TABLE_PREFIX . "blog AS blog
			$joinsql
			$hook_query_joins
			WHERE " . implode(" AND ", $wheresql) . "
			$hook_query_where
			ORDER BY blog.dateline DESC
			LIMIT 5
		");

		fetch_avatar_html($userinfo, true); // Set to thumbnail for sideblock

		while ($entry = $vbulletin->db->fetch_array($entries))
		{
			if ($entry['dateline'] > TIMENOW OR $entry['pending'])
			{
				$status['phrase'] = $vbphrase['pending_blog_entry'];
				$status['image'] = vB_Template_Runtime::fetchStyleVar('imgdir_misc') . "/blog/pending_small.gif";
				$show['sidebarstatus'] = true;
			}
			else if ($entry['state'] == 'deleted')
			{
				$status['image'] = vB_Template_Runtime::fetchStyleVar('imgdir_misc') . "/blog/trashcan.gif";
				$status['phrase'] = $vbphrase['deleted_blog_entry'];
				$show['sidebarstatus'] = true;
			}
			else if ($entry['state'] == 'moderation')
			{
				$status['phrase'] = $vbphrase['moderated_blog_entry'];
				$status['image'] = vB_Template_Runtime::fetchStyleVar('imgdir_misc') . "/blog/moderated.gif";
				$show['sidebarstatus'] = true;
			}
			else if ($entry['state'] == 'draft')
			{
				$status['phrase'] = $vbphrase['draft_blog_entry'];
				$status['image'] = vB_Template_Runtime::fetchStyleVar('imgdir_misc') . "/blog/draft_small.gif";
				$show['sidebarstatus'] = true;
			}
			else
			{
				$show['sidebarstatus'] = false;
			}

			$entry['date'] = vbdate($vbulletin->options['dateformat'], $entry['dateline']);
			$entry['time'] = vbdate($vbulletin->options['timeformat'], $entry['dateline']);

			($hook = vBulletinHook::fetch_hook('blog_sidebar_user_entries_loop')) ? eval($hook) : false;

			$templater = vB_Template::create('blog_sidebar_entry_link');
				$templater->register('status', $status);
				$templater->register('entry', $entry);
				$templater->register('userinfo', $userinfo);
			$sidebar['entrybits'] .= $templater->render();
		}

		fetch_avatar_html($userinfo);  // Unset from thumbnail
	}

	if ($useblock['block_category'])
	{
		//########################### Get Category Bits #####################################
		$blog = array('userid' => $userinfo['userid'], 'title' => $userinfo['blog_title']);
		$categorybits = '';

		if (!empty($userinfo['categorycache']))
		{
			if (empty($userinfo['permissions']))
			{
				cache_permissions($userinfo, false);
			}

			$beenhere = $prevdepth = 0;
			foreach ($userinfo['categorycache'] AS $category)
			{
				$show['ul'] = $admincat = false;
				if (!$category['userid'])
				{
					if (!$vbulletin->blogcategorycache["{$category['blogcategoryid']}"])
					{
						continue;
					}
					$category['title'] = $vbphrase['category' . $category['blogcategoryid'] . '_title'];
					if (!($vbulletin->userinfo['blogcategorypermissions']["$category[blogcategoryid]"] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewcategory']) AND $userinfo['userid'] != $vbulletin->userinfo['userid'] )
					{
						continue;
					}
					$admincat = true;
				}
				else if (!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancreatecategory']))
				{
					continue;
				}

				if (!$admincat AND $sidebar['globalcategorybits'] AND !$sidebar['localcategorybits'])
				{
					for ($x = $prevdepth; $x > 0; $x--)
					{
						$sidebar['globalcategorybits'] .= '</li></ul>';
					}
					$sidebar['globalcategorybits'] .= '</li>';
					$beenhere = $prevdepth = 0;
				}

				$indentbits = '';
				if ($category['depth'] == $prevdepth AND $beenhere)
				{
					$indentbits = '</li>';
				}
				else if ($category['depth'] > $prevdepth)
				{
					// Need an UL
					$show['ul'] = true;
				}
				else if ($category['depth'] < $prevdepth)
				{
					for ($x = ($prevdepth - $category['depth']); $x > 0; $x--)
					{
						$indentbits .= '</li></ul>';
					}
					$indentbits .= '</li>';
				}

				$show['catlink'] = ($vbulletin->GPC['blogcategoryid'] != $category['blogcategoryid']) ? true : false;
				if ($admincat)
				{
					$show['globalcats'] = true;

					$templater = vB_Template::create('blog_sidebar_category_link');
						$templater->register('category', $category);
						$templater->register('blog', $blog);
						$templater->register('pageinfo', array('blogcategoryid' => $category['blogcategoryid']));
					$sidebar['globalcategorybits'] .= $templater->render();
				}
				else
				{
					$show['localcats'] = true;
					$templater = vB_Template::create('blog_sidebar_category_link');
						$templater->register('category', $category);
						$templater->register('blog', $blog);
						$templater->register('pageinfo', array('blogcategoryid' => $category['blogcategoryid']));
					$sidebar['localcategorybits'] .= $templater->render();
				}

				$prevdepth = $category['depth'];
				$beenhere = true;
			}

			if ($sidebar['localcategorybits'])
			{
				for ($x = $prevdepth; $x > 0; $x--)
				{
					$sidebar['localcategorybits'] .= '</li></ul>';
				}
				$sidebar['localcategorybits'] .= '</li>';
			}
			else if ($sidebar['globalcategorybits'])
			{
				for ($x = $prevdepth; $x > 0; $x--)
				{
					$sidebar['globalcategorybits'] .= '</li></ul>';
				}
				$sidebar['globalcategorybits'] .= '</li>';
			}
		}

		if ($userinfo['uncatentries'])
		{
			$show['ul'] = false;
			$show['localcats'] = true;
			$blogcategoryid = -1;
			$category = array(
				'title'          => $vbphrase['uncategorized'],
				'entrycount'     => $userinfo['uncatentries'],
				'blogcategoryid' => $blogcategoryid,
			);
			$show['catlink'] = ($vbulletin->GPC['blogcategoryid'] != $blogcategoryid) ? true : false;

			$templater = vB_Template::create('blog_sidebar_category_link');
				$templater->register('category', $category);
				$templater->register('blog', $blog);
				$templater->register('pageinfo', array('blogcategoryid' => $category['blogcategoryid']));
			$sidebar['localcategorybits'] .= $templater->render();
			$sidebar['localcategorybits'] .= '</li>';
		}

		$show['editcat'] = ($userinfo['userid'] == $vbulletin->userinfo['userid'] OR can_moderate_blog('caneditcategories'));
		$show['editcat_userid'] = ($userinfo['userid'] != $vbulletin->userinfo['userid']);
	}

	$show['subscribelink'] = ($vbulletin->userinfo['userid']);
	$show['blogsubscribed'] = $userinfo['blogsubscribed'];
	$show['pending'] = (is_member_of_blog($vbulletin->userinfo, $userinfo) AND $userinfo['blog_pending']);
	$show['draft'] = (is_member_of_blog($vbulletin->userinfo, $userinfo) AND $userinfo['blog_draft']);
	$show['approvecomments'] = (is_member_of_blog($vbulletin->userinfo, $userinfo) AND $userinfo['blog_comments_moderation']);

	if ($userinfo['blogid'])
	{
		$show['editentry'] = fetch_entry_perm('edit', $userinfo);

		$perform_floodcheck = (
			!($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
			AND $vbulletin->options['emailfloodtime']
			AND $vbulletin->userinfo['userid']
		);

		$show['emailentry'] = (
			$userinfo['state'] != 'visible'
				OR
			$userinfo['pending']
				OR
			!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canemail'])
				OR
			!$vbulletin->options['enableemail']
				OR
			(
				$perform_floodcheck
					AND
				($timepassed = TIMENOW - $vbulletin->userinfo['emailstamp']) < $vbulletin->options['emailfloodtime'])
			) ? false : true;
	}

	$show['emaillink'] = (
		$userinfo['showemail'] AND $vbulletin->options['displayemails'] AND (
			!$vbulletin->options['secureemail'] OR (
				$vbulletin->options['secureemail'] AND $vbulletin->options['enableemail']
			)
		) AND $vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canemailmember']
	);
	$show['homepage'] = ($userinfo['homepage'] != '' AND $userinfo['homepage'] != 'http://');
	$show['pmlink'] = ($vbulletin->options['enablepms'] AND $vbulletin->userinfo['permissions']['pmquota'] AND ($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']
 					OR ($userinfo['receivepm'] AND $vbulletin->perm_cache["{$userinfo['userid']}"]['pmquota'])
 				)) ? true : false;
 	$show['gotoblog'] = ($vbulletin->userinfo['userid'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']);
	$show['rssfeed'] = ($vbulletin->usergroupcache['1']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']) ? true : false;
	$show['categorylink'] = ($show['canpostitems'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancreatecategory']);

	$usercsspermissions = array(
		'caneditfontfamily' => $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditfontfamily'] ? true  : false,
		'caneditfontsize'   => $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditfontsize'] ? true : false,
		'caneditcolors'     => $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditcolors'] ? true : false,
		'caneditbgimage'    => ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_albums'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditbgimage']) ? true : false,
		'caneditborders'    => $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditborders'] ? true : false
	);
	$show['customizeblog'] = (in_array(true, $usercsspermissions) AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancustomizeblog']);

	if (
		$userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canhavegroupblog']
			OR
		$userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canjoingroupblog']
	)
	{
		$show['managegroupblog'] = true;
		$blogmembers = explode(',', $userinfo['memberids']);
		$show['groupblog'] = (count($blogmembers) > 1);
		$show['memberblog'] = (is_member_of_blog($vbulletin->userinfo, $userinfo) AND $userinfo['userid'] != $vbulletin->userinfo['userid']);
		$show['postgroupblog'] = (
			is_member_of_blog($vbulletin->userinfo, $userinfo)
				AND
			$vbulletin->userinfo['userid'] != $userinfo['userid']
				AND
			$userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpost']
				AND
			$userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']
		);
	}
	else
	{
		$show['groupblog'] = $show['managegroupblog'] = $show['postgroupblog'] = $show['memberblog'] = false;
	}

	$show['postblog'] = (
		$vbulletin->userinfo['userid']
			AND
		$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpost']
			AND
		$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']
	);

	$show['hidepostblogbutton'] = (THIS_SCRIPT == 'blog_post' AND in_array($_REQUEST['do'], array('editblog', 'newblog', 'comment')));

	if ($vbulletin->userinfo['userid'] AND !$userinfo['member_canviewmyblog'])
	{
		if (is_member_of_blog($vbulletin->userinfo, $userinfo))
		{
			$show['privateblog'] = true;
		}
		else if ($userinfo['buddyid'] AND $userinfo['buddy_canviewmyblog'])
		{
			$show['privateblog'] = $show['privateblog_contact'] = true;
		}
		else if (can_moderate_blog())
		{
			$show['privateblog'] = $show['privateblog_moderator'] = true;
		}
	}

	$userinfo['onlinestatus'] = 0;
	// now decide if we can see the user or not
	if ($userinfo['lastactivity'] > (TIMENOW - $vbulletin->options['cookietimeout']) AND $userinfo['lastvisit'] != $userinfo['lastactivity'])
	{
		if ($userinfo['invisible'])
		{
			if (($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehidden']) OR $vbulletin->userinfo['userid'] == $userinfo['userid'])
			{
				// user is online and invisible BUT bbuser can see them
				$userinfo['onlinestatus'] = 2;
			}
		}
		else
		{
			// user is online and visible
			$userinfo['onlinestatus'] = 1;
		}
	}

	if ($useblock['block_tagcloud'])
	{
		$sidebar['tagcloud'] = fetch_blog_tagcloud('usage', true, $userinfo['userid']);
	}
	if ($useblock['block_search'])
	{
		$sidebar['search'] = $show['blog_search'];
	}

	$blogrules = $rules ? construct_blog_rules($rules, $userinfo) : '';

	$customblockcount = 0;
	$moveableblocks = 0;
	foreach ($blockorder AS $blockname => $status)
	{
		switch($blockname)
		{
			case 'block_comments':
				$pageinfo = array('do' => 'comments');
				break;
			default:
				$pageinfo = array();
		}
		if ($status)
		{
			$show['moveable'] = true;
			if (preg_match('#^block_#', $blockname))
			{
				 $templater = vB_Template::create('blog_sidebar_user_' . $blockname);
					$templater->register('userinfo', $userinfo);
					$templater->register('sidebar', $sidebar);
					$templater->register('month', $month);
					$templater->register('year', $year);
					$templater->register('pageinfo', $pageinfo);
				$sidebar['user_customized_blocks'] .= $templater->render();
			}
			else if (!empty($customblock["$blockname"]) AND $customblockcount < $userinfo['permissions']['vbblog_customblocks']) // custom block
			{
				$collapseimg = $vbcollapse["collapseimg_blog_block_$blockname"];
				$collapseobj = $vbcollapse["collapseobj_blog_block_$blockname"];
				$block =& $customblock["$blockname"];
				$customblockcount++;
				$show['editblock'] = ($userinfo['userid'] == $vbulletin->userinfo['userid'] OR can_moderate_blog('caneditcustomblocks'));
				$templater = vB_Template::create('blog_sidebar_user_block_custom');
					$templater->register('block', $block);
					$templater->register('blockname', $blockname);
				$sidebar['user_customized_blocks'] .= $templater->render();
			}
			$moveableblocks++;
		}
		else if ($useblock["$blockname"])
		{
			$show['moveable'] = false;
				$templater = vB_Template::create('blog_sidebar_user_' . $blockname);
				$templater->register('userinfo', $userinfo);
				$templater->register('sidebar', $sidebar);
				$templater->register('month', $month);
				$templater->register('year', $year);
				$templater->register('pageinfo', $pageinfo);
			$sidebar["$blockname"] = $templater->render();
		}
	}

	if ($userinfo['permissions']['vbblog_custompages'] AND !empty($userinfo['custompages']['side']))
	{
		foreach ($userinfo['custompages']['side'] AS $page)
		{
			$templater = vB_Template::create('blog_sidebar_custompage_link');
				$templater->register('page', $page);
			$sidebar['custompages'] .= $templater->render();
		}
	}

	if ($userinfo['showbirthday'] == 1 OR $userinfo['showbirthday'] == 2)
	{
		$year = vbdate('Y', TIMENOW, false, false);
		$month = vbdate('n', TIMENOW, false, false);
		$day = vbdate('j', TIMENOW, false, false);

		$date = explode('-', $userinfo['birthday']);
		if ($year > $date[2] AND $date[2] != '0000')
		{
			$userinfo['age'] = $year - $date[2];
			if ($month < $date[0] OR ($month == $date[0] AND $day < $date[1]))
			{
				$userinfo['age']--;
			}

			if ($userinfo['age'] >= 101)
			{
				unset($userinfo['age']);
			}
		}
	}

	if ($moveableblocks > 1 AND $vbulletin->userinfo['userid'] == $userinfo['userid'])
	{
		$show['moveable_blocks'] = true;
	}

	$show['bloguserinfo'] = true;
	$blogrssinfo = array(
		'bloguserid' => ($userinfo['bloguserid']) ? $userinfo['bloguserid'] : $userinfo['postedby_userid'],
		'blog_title' => $userinfo['blog_title'],
	);

	set_sidebar_ads($ad_location, $show);

	$headinclude .= construct_usercss_blog($userinfo, $show['blog_usercss_switch']);
	construct_usercss_switch_blog($show['blog_usercss_switch'], $blog_usercss_switch_phrase);

	($hook = vBulletinHook::fetch_hook('blog_sidebar_user_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('blog_sidebar_user');
		$templater->register('ad_location', $ad_location);
		$templater->register('userinfo', $userinfo);
		$templater->register('sidebar', $sidebar);
		$templater->register('blogrules', $blogrules);
		$templater->register('pageinfo_markread', array('do' => 'markread', 'readhash' => $vbulletin->userinfo['logouthash']));
	return $templater->render();
}

/**
 *	Set the sidebar ads to the ad_location array and the is_set flags for them to
 *	the $show array
 *
 *	We use the $show array becasue that is consistant with usage elsewhere for the
 *	set/not set flag for the ad.  We can't just use the presence or absense of text
 *	in the add template because if template comments are turned on then there will
 *	be a template comment in the rendered text which we need to treat as ad no set.
 */
function set_sidebar_ads(&$ad_location, &$show)
{
	// advertising location setup
	$ad_location['blogsidebar_start'] = vB_Template::create('ad_blogsidebar_start')->render();
	$ad_location['blogsidebar_middle'] = vB_Template::create('ad_blogsidebar_middle')->render();
	$ad_location['blogsidebar_end'] = vB_Template::create('ad_blogsidebar_end')->render();

	//need to change the ad template here when it changes in the xml
	$show['blogsidebar_start'] = (strpos($ad_location['blogsidebar_start'],
		'<div id="ad_blogsidebar_start">') !== false);
	$show['blogsidebar_middle'] = (strpos($ad_location['blogsidebar_middle'],
		'<div id="ad_blogsidebar_middle">') !== false);
	$show['blogsidebar_end'] = (strpos($ad_location['blogsidebar_end'],
		'<div id="ad_blogsidebar_end">') !== false);
}

/**
* Fetches the permission value for entries
*
* @param	string	The permission to check
* @param	array	An array of information about the blog entry
*
* @return	boolean	Returns true if they have the permission else false
*/
function fetch_entry_perm($perm, $entryinfo)
{
	global $vbulletin;

	// userid/bloguserid always needs to be the owner of the blog!
	if (!$entryinfo['bloguserid'])
	{
		$entryinfo['bloguserid'] = $entryinfo['userid'];
	}

	if (
			// Deleted Entry
			(
				$entryinfo['state'] == 'deleted'
					AND
				!can_moderate_blog('candeleteentries')
					AND
				(
					!is_member_of_blog($vbulletin->userinfo, $entryinfo)
						OR
					(
						$entryinfo['del_userid'] != $vbulletin->userinfo['userid']
							AND
						(
							$entryinfo['bloguserid'] != $vbulletin->userinfo['userid']
								OR
							$entryinfo['del_moddelete']
						)
					)
				)
			)
				OR
			// Moderated Entry
			(
				$entryinfo['state'] == 'moderation'
					AND
				!can_moderate_blog('canmoderateentries')
					AND
				(
					!is_member_of_blog($vbulletin->userinfo, $entryinfo)
						OR
					(
						$entryinfo['bloguserid'] != $vbulletin->userinfo['userid']
							AND
						$entryinfo['postedby_userid'] != $vbulletin->userinfo['userid']
					)
				)
			)
				OR
			(($entryinfo['state'] == 'draft' OR $entryinfo['state'] == 'pending') AND !is_member_of_blog($vbulletin->userinfo, $entryinfo))
		)
	{
		return false;
	}

	switch ($perm)
	{
		case 'moderate':
			return
			(
				can_moderate_blog('canmoderateentries')
					OR
				(
					$vbulletin->userinfo['userid']
						AND
					$entryinfo['bloguserid'] == $vbulletin->userinfo['userid']
						AND
					$entryinfo['postedby_userid'] != $vbulletin->userinfo['userid']
						AND
					$entryinfo['membermoderate']
				)
			);

		case 'undelete':
			return
			(
				$entryinfo['state'] == 'deleted'
					AND
				(
					can_moderate_blog('candeleteentries')
						OR
					(
						is_member_of_blog($vbulletin->userinfo, $entryinfo)
							AND
						$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_candeleteentry']
							AND
						$entryinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_candeleteentry']
					)
				)
			);

		case 'delete':
			return
			(
				can_moderate_blog('candeleteentries')
					OR
				(
					is_member_of_blog($vbulletin->userinfo, $entryinfo)
						AND
					$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_candeleteentry']
						AND
					$entryinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_candeleteentry']
						AND
					(
						$vbulletin->userinfo['userid'] == $entryinfo['bloguserid']
							OR
						(
							(
								$vbulletin->userinfo['userid'] == $entryinfo['postedby_userid']
									AND
								$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['candeleteentry']
							)
								OR
							(
								$vbulletin->userinfo['userid'] != $entryinfo['postedby_userid']
									AND
								$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanageotherentry']
							)
						)
					)
				)
			);

		case 'remove':
			return
			(
				can_moderate_blog('canremoveentries')
					OR
				(
					is_member_of_blog($vbulletin->userinfo, $entryinfo)
						AND
					$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canremoveentry']
						AND
					$entryinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canremoveentry']
						AND
					(
						$vbulletin->userinfo['userid'] == $entryinfo['bloguserid']
							OR
						(
							(
								$vbulletin->userinfo['userid'] == $entryinfo['postedby_userid']
									AND
								$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canremoveentry']
							)
						)
					)
				)
			);

		case 'edit':
			return
			(
				can_moderate_blog('caneditentries')
					OR
				(
					is_member_of_blog($vbulletin->userinfo, $entryinfo)
						AND
					$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_caneditentry']
						AND
					$entryinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_caneditentry']
						AND
					(
						$vbulletin->userinfo['userid'] == $entryinfo['bloguserid']
							OR
						(
							$vbulletin->userinfo['userid'] == $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['caneditentry']
						)
							OR
						(
							$vbulletin->userinfo['userid'] != $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanageotherentry']
						)
					)
				)
			);

		default:
			$handled = false;
			($hook = vBulletinHook::fetch_hook('blog_fetch_entry_perm')) ? eval($hook) : false;

			if (!$handled)
			{
				trigger_error('fetch_entry_perm(): Argument #1; Invalid permission specified');
			}
	}
}

/**
* Fetches the permission value for a specific blog comment
*
* @param	string	The permission to check
* @param	array	An array of information about the blog entry
* @param	array	An array of information about the blog comment
*
* @return	boolean	Returns true if they have the permission else false
*/
function fetch_comment_perm($perm, $entryinfo = null, $blogtextinfo = null)
{
	global $vbulletin;

	// Only moderator can manage a comment that is in a moderated/deleted post, not even the owner of the post can manage in this situation.
	if (
		// Deleted Post
			($entryinfo['state'] == 'deleted' AND !can_moderate_blog('candeleteentries') AND ($perm != 'canviewcomments' OR !is_member_of_blog($vbulletin->userinfo, $entryinfo['userid'])))
			 OR
		// Moderated Post
			($entryinfo['state'] == 'moderation' AND !can_moderate_blog('canmoderateentries') AND ($perm != 'canviewcomments' OR !is_member_of_blog($vbulletin->userinfo, $entryinfo)))
		)
	{
		return false;
	}

	switch ($perm)
	{
		case 'canviewcomments':
			return
			(
				(
					($blogtextinfo['state'] != 'deleted' OR can_moderate_blog('candeletecomments') OR is_member_of_blog($vbulletin->userinfo, $entryinfo))
				 	 AND
				 	($blogtextinfo['state'] != 'moderation' OR is_member_of_blog($vbulletin->userinfo, $entryinfo) OR $vbulletin->userinfo['userid'] == $blogtextinfo['userid'] OR fetch_comment_perm('canmoderatecomments', $entryinfo, $blogtextinfo))
				)
			);

		case 'caneditcomments':
			return
			(
				(
					$entryinfo['userid'] == $vbulletin->userinfo['userid']
					 AND
					$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments']
				)
					OR
				(
					is_member_of_blog($vbulletin->userinfo, $entryinfo)
						AND
					$entryinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments']
						AND
					(
						(
							$vbulletin->userinfo['userid'] == $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanagecomments']
						)
							OR
						(
							$vbulletin->userinfo['userid'] != $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanageotherentry']
						)
					)
				)
				 OR
				(
					($blogtextinfo['state'] == 'visible' OR $blogtextinfo['state'] == 'moderation')
					 AND
					$blogtextinfo['userid'] == $vbulletin->userinfo['userid']
					 AND
					$vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_caneditowncomment']
				)
				 OR
				(
					can_moderate_blog('caneditcomments')
					 AND
					(
						$blogtextinfo['state'] != 'moderation' OR fetch_comment_perm('canmoderatecomments', $entryinfo, $blogtextinfo)
					)
					 AND
					(
						$blogtextinfo['state'] != 'deleted' OR fetch_comment_perm('candeletecomments', $entryinfo, $blogtextinfo)
					)
				)
			);

		case 'canmoderatecomments':
			return
			(
				(
					$entryinfo['userid'] == $vbulletin->userinfo['userid']
					 AND
					$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments']
				)
					OR
				(
					is_member_of_blog($vbulletin->userinfo, $entryinfo)
						AND
					$entryinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments']
						AND
					(
						(
							$vbulletin->userinfo['userid'] == $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanagecomments']
						)
							OR
						(
							$vbulletin->userinfo['userid'] != $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanageotherentry']
						)
					)
				)
					OR
				(
					($blogtextinfo['state'] != 'deleted' OR can_moderate('candeletecomments'))
						AND
					can_moderate_blog('canmoderatecomments')
				)
			);

		case 'candeletecomments':
			return
			(
				(
					$entryinfo['userid'] == $vbulletin->userinfo['userid']
					 AND
					$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments']
				)
					OR
				(
					is_member_of_blog($vbulletin->userinfo, $entryinfo)
						AND
					$entryinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments']
						AND
					(
						(
							$vbulletin->userinfo['userid'] == $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanagecomments']
						)
							OR
						(
							$vbulletin->userinfo['userid'] != $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanageotherentry']
						)
					)
				)
					OR
				(
					can_moderate_blog('candeletecomments')
				)
					OR
				(
					$blogtextinfo['state'] == 'visible'
						AND
					$blogtextinfo['userid'] == $vbulletin->userinfo['userid']
						AND
					$vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_candeleteowncomment']
				)
			);

		case 'canremovecomments':
			return
			(
				(
					$entryinfo['userid'] == $vbulletin->userinfo['userid']
					 AND
					$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments']
				)
					OR
				(
					is_member_of_blog($vbulletin->userinfo, $entryinfo)
						AND
					$entryinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments']
						AND
					(
						(
							$vbulletin->userinfo['userid'] == $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanagecomments']
						)
					)
				)
					OR
				(
					can_moderate_blog('canremovecomments')
				)
			);

		case 'canundeletecomments':
			return
			(
				(
					$entryinfo['userid'] == $vbulletin->userinfo['userid']
						AND
					$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments']
				)
					OR
				(
					is_member_of_blog($vbulletin->userinfo, $entryinfo)
						AND
					$entryinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments']
						AND
					(
						(
							$vbulletin->userinfo['userid'] == $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanagecomments']
						)
							OR
						(
							$vbulletin->userinfo['userid'] != $entryinfo['postedby_userid']
								AND
							$entryinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['canmanageotherentry']
						)
					)
				)
					OR
				(
					can_moderate_blog('candeletecomments')
				)
			);

		default:
			$handled = false;
			($hook = vBulletinHook::fetch_hook('blog_fetch_comment_perm')) ? eval($hook) : false;

			if (!$handled)
			{
				trigger_error('fetch_comment_perm(): Argument #1; Invalid permission specified', E_USER_ERROR);
			}
	}
}

/**
* Verifies that an akismet key is valid
*
* @param	string	The akismet key to check for validity
* @param	string	The URL that the key is going to be used on
* @param	fields	Extra information that should be submitted to akismet
*
* @return	boolean	Returns true if the key is valid else false
*/
function verify_akismet_status($key, $url, $fields = array())
{
	global $vbulletin;

	require_once(DIR . '/includes/class_akismet.php');
	$akismet = new vB_Akismet($vbulletin);

	$akismet->akismet_key = $key;
	$akismet->akismet_board = $url;

	return $akismet->verify_text($fields);
}

/**
* Construct the blog rules table
*
* @param	string	The area the table will be shown, 'comment', 'usercomment', 'entry' or 'user' are valid values
*
* @return	string	HTML for the blog rules tbale
*/
function construct_blog_rules($area = 'entry', $userinfo = null)
{
	global $vbulletin, $vbphrase, $vbcollapse, $show;

	if (!$userinfo)
	{
		$userinfo =& $vbulletin->userinfo;
	}

	switch ($area)
	{
		case 'comment':
			$bbcodeon = $userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowbbcode'] ? $vbphrase['on'] : $vbphrase['off'];
			$imgcodeon = $userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowimages'] ? $vbphrase['on'] : $vbphrase['off'];
			$videocodeon = $userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowvideos'] ? $vbphrase['on'] : $vbphrase['off'];
			$htmlcodeon = $userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowhtml'] ? $vbphrase['on'] : $vbphrase['off'];
			$smilieson = $userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowsmilies'] ? $vbphrase['on'] : $vbphrase['off'];
			break;
		case 'usercomment':
			$bbcodeon = $userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_allowbbcode'] ? $vbphrase['on'] : $vbphrase['off'];
			$imgcodeon = $userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_allowimages'] ? $vbphrase['on'] : $vbphrase['off'];
			$videocodeon = $userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_allowvideos'] ? $vbphrase['on'] : $vbphrase['off'];
			$htmlcodeon = $userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_allowhtml'] ? $vbphrase['on'] : $vbphrase['off'];
			$smilieson = $userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowsmilies'] ? $vbphrase['on'] : $vbphrase['off'];
			break;
		case 'entry':
		case 'user':
		case 'customblock':
		case 'custompage':
			$bbcodeon = $userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode'] ? $vbphrase['on'] : $vbphrase['off'];
			$imgcodeon = $userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowimages'] ? $vbphrase['on'] : $vbphrase['off'];
			$videocodeon = $userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowvideos'] ? $vbphrase['on'] : $vbphrase['off'];
			$htmlcodeon = $userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowhtml'] ? $vbphrase['on'] : $vbphrase['off'];
			$smilieson = $userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowsmilies'] ? $vbphrase['on'] : $vbphrase['off'];
			break;
	}

	($hook = vBulletinHook::fetch_hook('blog_rules')) ? eval($hook) : false;

	$templater = vB_Template::create('blog_rules');
		$templater->register('bbcodeon', $bbcodeon);
		$templater->register('imgcodeon', $imgcodeon);
		$templater->register('videocodeon', $videocodeon);
		$templater->register('htmlcodeon', $htmlcodeon);
		$templater->register('smilieson', $smilieson);
	return $templater->render();
}

/**
* Fetches the HTML for the tag cloud.
*
* @param	string	Type of cloud. Supports search, usage
* @param	bool		Return full cloud box or just the links
* @param	int			Limit cloud to blog entries owned by a specific user
*
* @return	string	Tag cloud HTML (nothing if no cloud)
*/
function fetch_blog_tagcloud($type = 'usage', $links = false, $userid = 0)
{
	global $vbulletin, $vbphrase, $show, $template_hook;

	if (!$vbulletin->options['vbblog_tagging'])
	{
		return false;
	}

	$wheresql = array(
		"blog.dateline <= " . TIMENOW,
		"blog.pending = 0",
		"blog.state = 'visible'",
		"~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'],
	);

	if ($userid AND $type == 'usage')
	{
		$userinfo = fetch_userinfo($userid);
		$wheresql[] = "blog.userid = $userid";
		$cloud = @unserialize($userinfo['tagcloud']);
	}
	else
	{
		if ($vbulletin->options['vbblog_tagcloud_cachetype'] == 1)
		{
			$cloud = null;
		}
		else
		{
			switch ($type)
			{
				case 'search':
					$cloud = $vbulletin->blogsearchcloud;
					break;

				case 'usage':
				default:
					if ($userid)
					{
						$userinfo = fetch_userinfo($userid);
						$wheresql[] = "blog.userid = $userid";
					}
					else
					{
						$cloud = $vbulletin->blogtagcloud;
					}
					break;
			}
		}
	}

	if (!is_array($cloud) OR $cloud['dateline'] < (TIMENOW - (60 * $vbulletin->options['vbblog_tagcloud_cachetime'])))
	{
		if ($type == 'search')
		{
			$tags_result = $vbulletin->db->query_read_slave("
				SELECT tagsearch.tagid, tag.tagtext, COUNT(*) AS searchcount
				FROM " . TABLE_PREFIX . "blog_tagsearch AS tagsearch
				INNER JOIN " . TABLE_PREFIX . "tag AS tag ON (tagsearch.tagid = tag.tagid)
				" . ($vbulletin->options['tagcloud_searchhistory'] ?
					"WHERE tagsearch.dateline > " . (TIMENOW - (60 * 60 * 24 * $vbulletin->options['vbblog_tagcloud_searchhistory'])) :
					'') . "
				GROUP BY tagsearch.tagid, tag.tagtext
				ORDER BY searchcount DESC
				LIMIT " . $vbulletin->options['vbblog_tagcloud_tags']
			);
		}
		else
		{
			$joinsql = array();

			if ($vbulletin->options['vbblog_tagcloud_cachetype'] == 1)
			{
				if ($vbulletin->userinfo['userid'])
				{
					$userlist_sql = array();
					$userlist_sql[] = "(options_ignore & " .
						$vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] .
						" AND ignored.relationid IS NOT NULL)";
					$userlist_sql[] = "(options_buddy & " .
						$vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] .
						" AND buddy.relationid IS NOT NULL)";
					$userlist_sql[] = "(options_member & " .
						$vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] .
						" AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] .
						" OR buddy.relationid IS NULL) AND (options_ignore & " .
						$vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] .
						" OR ignored.relationid IS NULL))";
					$wheresql[] = "(" . implode(" OR ", $userlist_sql) . ")";

					$joinsql[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON
						(buddy.userid = blog.userid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . "
							AND buddy.type = 'buddy')";
					$joinsql[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON
						(ignored.userid = blog.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . "
						AND ignored.type = 'ignore')";
				}
				else
				{
					$wheresql[] = "options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
					$wheresql[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
				}

				if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']) AND $userinfo['userid'] != $vbulletin->userinfo['userid'])
				{
					$joinsql[] = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
					$wheresql[] = "cu.blogcategoryid IS NULL";
				}

				// remove blog entries that don't interest us
				require_once(DIR . '/includes/functions_bigthree.php');
				if ($coventry = fetch_coventry('string'))
				{
					$wheresql[] = "blog.userid NOT IN ($coventry)";
				}
			}
			else
			{
				if (trim($vbulletin->options['globalignore']) != '')
				{
					if ($coventry = preg_split('#\s+#s', $vbulletin->options['globalignore'], -1, PREG_SPLIT_NO_EMPTY))
					{
						$wheresql[] = "blog.userid NOT IN (" . implode(',', $coventry) . ")";
					}
				}
			}

			$contenttypeid = vB_Types::instance()->getContentTypeID('vBBlog_BlogEntry');

			$tags_result = $vbulletin->db->query_read_slave("
				SELECT tagcontent.tagid, tag.tagtext, COUNT(*) AS searchcount
				FROM " . TABLE_PREFIX . "tagcontent AS tagcontent
				INNER JOIN " . TABLE_PREFIX . "tag AS tag ON (tagcontent.tagid = tag.tagid)
				INNER JOIN " . TABLE_PREFIX . "blog AS blog ON (tagcontent.contenttypeid = $contenttypeid AND tagcontent.contentid = blog.blogid)
				INNER JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog.userid = blog_user.bloguserid)
				" . (!empty($joinsql) ? implode("\r\n", $joinsql) : "") . "
				WHERE " . implode(" AND ", $wheresql) . "
				" . ($vbulletin->options['vbblog_tagcloud_usagehistory'] ? "AND tagcontent.dateline > " . (TIMENOW - (60 * 60 * 24 * $vbulletin->options['vbblog_tagcloud_usagehistory'])) : "") . "
				GROUP BY tagcontent.tagid, tag.tagtext
				ORDER BY searchcount DESC
				LIMIT " . $vbulletin->options['vbblog_tagcloud_tags']
			);
		}

		$tags = array();
		$counts = array();
		if (!empty($tags_result))
		{
			while ($currenttag = $vbulletin->db->fetch_array($tags_result))
			{
				$tags["$currenttag[tagtext]"] = $currenttag;
				$counts[$currenttag['tagid']] = $currenttag['searchcount'];
			}
			$vbulletin->db->free_result($tags_result);

			// fetch the stddev levels
			$levels = fetch_standard_deviated_levels($counts, $vbulletin->options['vbblog_tagcloud_levels']);

			// assign the levels back to the tags
			$final_tags = array();
			foreach ($tags AS $tagtext => $thistag)
			{
				$thistag['level'] = $levels[$thistag['tagid']];
				$thistag['tagtext_url'] = urlencode(unhtmlspecialchars($thistag['tagtext']));
				$final_tags[] = $thistag;
			}

			uksort($tags, 'strnatcasecmp');
		}

		$cloud = array(
			'tags'     => $final_tags,
			'count'    => sizeof($final_tags),
			'dateline' => TIMENOW
		);

		if ($userid)
		{
			$dataman =& datamanager_init('Blog_User', $vbulletin, ERRTYPE_STANDARD);
			$info = array('bloguserid' => $userinfo['userid']);
			$dataman->set_existing($info);
			$dataman->set('tagcloud', $cloud);
			$dataman->save();
			unset($dataman);
		}
		else
		{
			if ($vbulletin->options['vbblog_tagcloud_cachetype'] == 2)
			{
				if ($type == 'search')
				{
					$vbulletin->blogsearchcloud = $cloud;
					build_datastore('blogsearchcloud', serialize($cloud), 1);
				}
				else
				{
					$vbulletin->blogtagcloud = $cloud;
					build_datastore('blogtagcloud', serialize($cloud), 1);
				}
			}
		}
	}

	if (empty($cloud['tags']))
	{
		return '';
	}

	$cloud['links'] = '';

	foreach ($cloud['tags'] AS $thistag)
	{
		($hook = vBulletinHook::fetch_hook('blog_tag_cloud_bit')) ? eval($hook) : false;
		$show['userlink'] = ($userid);
		$templater = vB_Template::create('blog_tag_cloud_link');
			$templater->register('thistag', $thistag);
			$templater->register('userinfo', $userinfo);
			$templater->register('pageinfo', array('tag' => $thistag['tagtext_url']));
		$cloud['links'] .= $templater->render();
	}

	if ($links)
	{
		$cloud_html = $cloud['links'];
	}
	else
	{
		$cloud['count'] = vb_number_format($cloud['count']);
		$templater = vB_Template::create('blog_tag_cloud_box');
			$templater->register('cloud', $cloud);
			$templater->register('userinfo', $userinfo);
		$cloud_html .= $templater->render();
	}

	if ($cloud_html)
	{
		$show['tagcloud_css'] = true;
	}
	return $cloud_html;
}

/**
* Build the CSV lists of members of a blog
*
* @param	int			Owner of blog
*
*/
function build_blog_memberids($bloguserid)
{
	global $vbulletin;

	$members_of_this_blog = array($bloguserid);
	$members = $vbulletin->db->query_read("
		SELECT userid
		FROM " . TABLE_PREFIX . "blog_groupmembership
		WHERE bloguserid = $bloguserid AND state = 'active'
	");
	while ($member = $vbulletin->db->fetch_array($members))
	{
		$members_of_this_blog[] = $member['userid'];
	}

	$dataman =& datamanager_init('Blog_User', $vbulletin, ERRTYPE_SILENT);
	if ($userinfo = $vbulletin->db->query_first_slave("
		SELECT bloguserid FROM " . TABLE_PREFIX . "blog_user WHERE bloguserid = $bloguserid
	"))
	{
		$dataman->set_existing($userinfo);
	}
	else
	{
		$dataman->set('bloguserid', $bloguserid);
	}
	$dataman->set('memberids', implode(',', $members_of_this_blog));
	$dataman->save();

	#memberids = members of this blog
	#memberblogids = blogs that I am a member of
}

/**
* Build the CSV lists of blogs that a member belongs to
*
* @param	int			Userid
*
*/
function build_blog_memberblogids($userid)
{
	global $vbulletin;

	$member_of = array($userid);
	$blogs = $vbulletin->db->query_read("
		SELECT bloguserid
		FROM " . TABLE_PREFIX . "blog_groupmembership
		WHERE userid = $userid AND state = 'active'
	");
	while ($blog = $vbulletin->db->fetch_array($blogs))
	{
		$member_of[] = $blog['bloguserid'];
	}

	$dataman =& datamanager_init('Blog_User', $vbulletin, ERRTYPE_SILENT);
	if ($userinfo = $vbulletin->db->query_first_slave("
		SELECT bloguserid FROM " . TABLE_PREFIX . "blog_user WHERE bloguserid = $userid
	"))
	{
		$dataman->set_existing($userinfo);
	}
	else
	{
		$dataman->set('bloguserid', $userid);
	}
	$dataman->set('memberblogids', implode(',', $member_of));
	$dataman->save();
}

/**
* Constructs the User's Custom CSS
*
* @param	array	An array of userinfo
* @param	bool	(Return) Whether to show the user css on/off switch to the user
*
* @return	string	HTML for the User's CSS
*/
function construct_usercss_blog(&$userinfo, &$show_usercss_switch)
{
	global $vbulletin;

	if (defined('VBBLOG_NOUSERCSS') OR !($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancustomizeblog']))
	{
		$show_usercss_switch = false;
		return '';
	}

	// check if permissions have changed and we need to rebuild this user's css
	if ($userinfo['blog_hascachedcss'] AND $userinfo['blog_cssbuildpermissions'] != $userinfo['permissions']['vbblog_general_permissions'])
	{
		require_once(DIR . '/includes/class_usercss.php');
		require_once(DIR . '/includes/class_usercss_blog.php');
		$usercss = new vB_UserCSS_Blog($vbulletin, $userinfo['userid'], false);
		$userinfo['blog_cachedcss'] = $usercss->update_css_cache();
	}

	if (!$vbulletin->userinfo['userid'])
	{
		$vbulletin->userinfo['showblogcss'] = 1;
	}

	if (!$vbulletin->userinfo['showblogcss'] AND $vbulletin->userinfo['userid'] != $userinfo['userid'])
	{
		// user has disabled viewing css; they can reenable
		$show_usercss_switch = (trim($userinfo['blog_cachedcss']) != '');
		$usercss = '';
	}
	else if (trim($userinfo['blog_cachedcss']))
	{
		if ($csscolors = @unserialize($userinfo['blog_csscolors']))
		{
			// todo - this is broken, $stylevar doesn't exist...
			//$stylevar = array_merge($stylevar, $csscolors);
		}

		$show_usercss_switch = ($vbulletin->userinfo['userid'] != $userinfo['userid']);
		$userinfo['blog_cachedcss'] = str_replace('/*sessionurl*/', $vbulletin->session->vars['sessionurl_js'], $userinfo['blog_cachedcss']);
		$templater = vB_Template::create('blog_usercss');
			$templater->register('userinfo', $userinfo);
		$usercss = $templater->render();
	}
	else
	{
		$show_usercss_switch = false;
		$usercss = '';
	}

	return $usercss;
}

/**
* Constructs the User's Custom CSS Switch Phrase
*
* @param	bool	If the switch is going to be shown or not
* @param	string	The phrase to use (Reference)
*
* @return	void
*/
function construct_usercss_switch_blog(&$show_usercss_switch, &$usercss_switch_phrase)
{
	global $vbphrase, $vbulletin;

	if ($show_usercss_switch AND $vbulletin->userinfo['userid'])
	{
		if ($vbulletin->userinfo['showblogcss'])
		{
			$show_usercss_switch = 'off';
			$usercss_switch_phrase = $vbphrase['hide_user_customizations'];
		}
		else
		{
			$show_usercss_switch = 'on';
			$usercss_switch_phrase = $vbphrase['show_user_customizations'];
		}
	}
}

/**
* Parses the user's blog description
*
* @param	array	$userinfo array from fetch_userinfo()
*
* @return	array
*/
function parse_blog_description(&$userinfo, $blockinfo = null)
{
	global $vbphrase, $vbulletin, $show;
	$blogheader = array();

	require_once(DIR . '/includes/class_bbcode_blog.php');
	$bbcode = new vB_BbCodeParser_Blog($vbulletin, fetch_tag_list());
	$bbcode->set_parse_userinfo($userinfo, $userinfo['permissions']);

	$blogheader['title'] = $userinfo['blog_title'];
	$blogheader['userid'] = $userinfo['bloguserid'];
	if (!empty($userinfo['blog_description']))
	{
		require_once(DIR . '/includes/class_blog_response.php');
		$blogheader['description'] = $bbcode->parse($userinfo['blog_description'], 'blog_user', $userinfo['blog_allowsmilie'] ? 1 : 0);
	}

	if (!empty($userinfo['custompages']['top']) AND $userinfo['permissions']['vbblog_custompages'])
	{
		$count = 1;
		foreach ($userinfo['custompages']['top'] AS $page)
		{
			$show['cplink'] = (!$blockinfo OR $blockinfo['customblockid'] != $page['i']);
			if (!$show['cplink'])
			{
				$show['cpbloglink'] = true;
			}
			$show['divider'] = ($count != count($userinfo['custompages']['top']));
			$templater = vB_Template::create('blog_header_custompage_link');
				$templater->register('page', $page);
			$blogheader['custompages'] .= $templater->render();
			$count++;
		}
	}

	return $blogheader;
}


function verify_blog_url()
{
	global $vbulletin;
	return verify_subdirectory_url($vbulletin->options['vbblog_url']);
}

if (!function_exists('fetch_require_hvcheck'))
{
	function fetch_require_hvcheck($action)
	{
		global $vbulletin;

		return (!$vbulletin->userinfo['userid'] AND $vbulletin->options["hvcheck_{$action}"]);
	}
}

if (version_compare($vbulletin->options['templateversion'], '3.8.0', '='))
{
	/**
	* Determines if the browsing user can view a specific section of a user's profile.
	*
	* @param	integer	User ID to check against
	* @param	string	Name of the section to check
	* @param	string	Optional override for privacy requirement (prevents query)
	* @param	array	Optional array of userinfo (to save on querying)
	*
	* @return	boolean
	*/
	function can_view_profile_section($userid, $section, $privacy_requirement = null, $userinfo = null)
	{
		global $vbulletin;

		if (!$vbulletin->options['profileprivacy'])
		{
			// not enabled - always viewable
			return true;
		}

		if (!is_array($userinfo))
		{
			if ($userid == $vbulletin->userinfo['userid'])
			{
				return true;
			}

			$userinfo = fetch_userinfo($userid);
			if (!$userinfo)
			{
				return true;
			}
		}
		else if ($userinfo['userid'] == $vbulletin->userinfo['userid'])
		{
			return true;
		}

		if (!isset($userinfo['permissions']))
		{
			cache_permissions($userinfo, false);
		}

		if (!($userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditprivacy']))
		{
			// user doesn't have permission - always viewable
			return true;
		}

		if ($privacy_requirement === null)
		{
			$privacy_requirement = $vbulletin->db->query_first_slave("
				SELECT requirement
				FROM " . TABLE_PREFIX . "profileblockprivacy
				WHERE userid = " . intval($userinfo['userid']) . "
					AND blockid = '" . $vbulletin->db->escape_string($section) . "'
			");
			$privacy_requirement = ($privacy_requirement['requirement'] ? $privacy_requirement['requirement'] : 0);
		}

		require_once(DIR . '/includes/functions_user.php');
		return (!$privacy_requirement OR fetch_user_relationship($userinfo['userid'], $vbulletin->userinfo['userid']) >= $privacy_requirement);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 63620 $
|| ####################################################################
\*======================================================================*/
