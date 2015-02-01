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

/**
* Construct the blog rules table
*
* @param	array	Array identifying the type of desired featured entry
* @param	array	Array of blogs that are already featured
*
* @return	int		Blogid of featured entry
*/
function fetch_featured_entry($featured, $blogs)
{
	global $vbulletin;

	$wheresql = array(
		"blog.dateline <= " . TIMENOW,
		"blog.pending = 0",
		"blog.state = 'visible'",
		"blog_user.options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'],
		"blog_user.options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'],
		"~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'],
	);

	// Include check for guest viewing permission only if guests can actually view the blog
	if ($vbulletin->usergroupcache[1]['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])
	{
		$wheresql[] = "blog_user.options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
	}

	if (!empty($blogs))
	{
		$wheresql[] = "blog.blogid NOT IN (" . implode(",", array_keys($blogs)) . ")";
	}

	if ($featured['userid'])
	{
		$wheresql[] = "blog.userid = $featured[userid]";
	}

	if ($featured['type'] == 'random')
	{
		if ($featured['start'])
		{
			$wheresql[] = "blog.dateline >= $featured[start]";
		}
		if ($featured['end'])
		{
			$wheresql[] = "blog.dateline < $featured[end]";
		}
		if ($featured['timespan'])
		{
			switch ($featured['timespan'])
			{
				case 'day':
					$wheresql[] = "blog.dateline >= " . (TIMENOW - 86400);
					break;
				case 'week':
					$wheresql[] = "blog.dateline >= " . (TIMENOW - 604800);
					break;
				case 'month':
					$wheresql[] = "blog.dateline >= " . (TIMENOW - 2592000);
					break;
				case 'year':
					$wheresql[] = "blog.dateline >= " . (TIMENOW - 31536000);
					break;
				case 'all':
					break;
			}
		}
	}
	if ($featured['pusergroupid'])
	{
		$wheresql[] = "user.usergroupid = $featured[pusergroupid]";
	}
	if ($featured['susergroupid'])
	{
		$wheresql[] = "FIND_IN_SET($featured[susergroupid], user.membergroupids)";
	}

	// Can't use fetch_coventry as if the current user is in coventry and triggers the cache their blog could be picked
	if (trim($vbulletin->options['globalignore']) != '')
	{
		if ($coventry = preg_split('#\s+#s', $vbulletin->options['globalignore'], -1, PREG_SPLIT_NO_EMPTY))
		{
			$wheresql[] = "blog.userid NOT IN (" . implode(',', $coventry) . ")";
		}
	}

	$result = $vbulletin->db->query_first("
		SELECT blogid
		FROM " . TABLE_PREFIX . "blog AS blog
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog.userid)
		INNER JOIN " . TABLE_PREFIX . "user AS user ON (blog.userid = user.userid)
		" . (!empty($joinsql) ? implode("\r\n", $joinsql) : "") . "
		WHERE " . implode(" AND ", $wheresql) . "
		" . ($featured['type'] == 'random' ? "ORDER BY RAND()" : "ORDER BY blog.dateline DESC") . "
		LIMIT 1
	");

	return intval($result['blogid']);
}

/**
* Fetches a list of blog entries
*
* @param	array		Blogids to be retrieved
* @param	int			Sum of attachments belonging to the set of blogids
* @param	array		Information pertaining to each attachment - set within this function
* @param	array		Category information - set within this function
* @param	array		Additional sql joins
* @param	array		Additional where conditions
* @param	string	Additional deletion join
* @param	string	Order By clause
*
* @return	object	mysql result of blog list
*/
function &fetch_blog_list($blogids, $attachcount, &$postattach, &$categories, $sqljoin = null, $wheresql = null, $deljoinsql = null, $orderby = "blog.dateline DESC")
{
	global $vbulletin, $vbphrase;

	$userperms = array();
	$cats = $vbulletin->db->query_read_slave("
		SELECT blog_categoryuser.blogid, blog_category.title, blog_category.blogcategoryid, blog_categoryuser.userid, blog_category.userid AS creatorid,
			user.usergroupid, user.membergroupids, user.infractiongroupids
		FROM " . TABLE_PREFIX . "blog_categoryuser AS blog_categoryuser
		LEFT JOIN " . TABLE_PREFIX . "blog_category AS blog_category ON (blog_category.blogcategoryid = blog_categoryuser.blogcategoryid)
		INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog_categoryuser.userid)
		WHERE blogid IN (" . implode(',', $blogids) . ")
		ORDER BY blogid, displayorder
	");
	while ($cat = $vbulletin->db->fetch_array($cats))
	{
		if (empty($userperms["$cat[userid]"]))
		{
			$userperms["$cat[userid]"] = cache_permissions($cat, false);
		}

		$perms = $userperms["$cat[userid]"];
		if ($perms['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancreatecategory'] OR !$cat['creatorid'])
		{
			if (!$cat['creatorid'])
			{
				$cat['title'] = $vbphrase['category' . $cat['blogcategoryid'] . '_title'];
			}
			$categories["$cat[blogid]"][] = $cat;
		}
	}

	// Query Attachments
	if ($attachcount)
	{
		require_once(DIR . '/packages/vbattach/attach.php');
		$attach = new vB_Attach_Display_Content($vbulletin, 'vBBlog_BlogEntry');
		$postattach = $attach->fetch_postattach(0, $blogids);
	}

	if (!($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_cangetattach']))
	{
		$vbulletin->options['viewattachedimages'] = 0;
		$vbulletin->options['attachthumbs'] = 0;
	}

	$hook_query_joins = $hook_query_fields = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('blog_fetch_list_query')) ? eval($hook) : false;

	$blogs = $vbulletin->db->query_read("
		SELECT
			blog.*, blog.options AS blogoptions,
			blog_text.pagetext, blog_text.ipaddress AS blogipaddress, blog_text.allowsmilie, blog_text.htmlstate,
			user.*,
			bu.title AS blogtitle, bu.memberids, bu.options_member, bu.options_guest, bu.options_buddy, bu.bloguserid,
			blog_textparsed.pagetexthtml, blog_textparsed.hasimages,
			blog_editlog.userid AS edit_userid, blog_editlog.dateline AS edit_dateline, blog_editlog.reason AS edit_reason, blog_editlog.username AS edit_username,
			customprofilepic.userid AS profilepic, customprofilepic.dateline AS profilepicdateline, customprofilepic.width AS ppwidth, customprofilepic.height AS ppheight
		" . ($deljoinsql ? ",blog_deletionlog.moddelete AS del_moddelete, blog_deletionlog.userid AS del_userid, blog_deletionlog.username AS del_username, blog_deletionlog.reason AS del_reason" : "") . "
		" . ($vbulletin->userinfo['userid'] ? ", gm.permissions AS grouppermissions, IF(blog_subscribeentry.blogsubscribeentryid, 1, 0) AS entrysubscribed" : "") . "
		" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
		" . (($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid']) ? ", blog_read.readtime AS blogread, blog_userread.readtime  AS bloguserread" : "") . "
		" . ($vbulletin->userinfo['userid'] AND $sql1join ? ", ignored.relationid AS ignoreid, buddy.relationid AS buddyid" : "") . "
		$hook_query_fields
		FROM " . TABLE_PREFIX . "blog AS blog
		INNER JOIN " . TABLE_PREFIX . "blog_text AS blog_text ON (blog.firstblogtextid = blog_text.blogtextid)
		LEFT JOIN " . TABLE_PREFIX . "blog_editlog AS blog_editlog ON (blog_editlog.blogtextid = blog.firstblogtextid)
		LEFT JOIN " . TABLE_PREFIX . "blog_textparsed AS blog_textparsed ON(blog_textparsed.blogtextid = blog_text.blogtextid AND blog_textparsed.styleid = " . intval(STYLEID) . " AND blog_textparsed.languageid = " . intval(LANGUAGEID) . ")
		" . (($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid']) ? "
		LEFT JOIN " . TABLE_PREFIX . "blog_read AS blog_read ON (blog_read.blogid = blog.blogid AND blog_read.userid = " . $vbulletin->userinfo['userid'] . ")
		LEFT JOIN " . TABLE_PREFIX . "blog_userread AS blog_userread ON (blog_userread.bloguserid = blog.userid AND blog_userread.userid = " . $vbulletin->userinfo['userid'] . ")
		" : "") . "
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (blog.postedby_userid = user.userid)
		INNER JOIN " . TABLE_PREFIX . "user AS user2 ON (blog.userid = user2.userid)
		LEFT JOIN " . TABLE_PREFIX . "customprofilepic AS customprofilepic ON (user.userid = customprofilepic.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
		" . ($vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")" : '') . "
		" . ($vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "blog_subscribeentry AS blog_subscribeentry ON (blog.blogid = blog_subscribeentry.blogid AND blog_subscribeentry.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
		" . (!empty($sqljoin) ? implode("\r\n", $sqljoin) : "") . "
		$deljoinsql
		$hook_query_jons
		WHERE blog.blogid IN (" . implode(',', $blogids) . ")
		" . (!empty($wheresql) ? "AND " . implode("\r\nAND ", $wheresql) : "") . "
		$hook_query_where
		ORDER BY $orderby
	");

	return $blogs;
}

/**
* Write to the blog visitor log
*
* @param	int			Userid of the owner of the blog
*/
function track_blog_visit($userid)
{
	global $vbulletin;

	if (!$vbulletin->userinfo['userid'] OR $vbulletin->userinfo['userid'] == $userid)
	{
		return;
	}

	$timestamp = TIMENOW - 3600 * 23;
	$month = date('n', $timestamp);
	$day = date('j', $timestamp);
	$year = date('Y', $timestamp);

	$startstamp = mktime(0, 0, 0, $month, $day, $year);

	$vbulletin->db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "blog_visitor
			(userid, visitorid, dateline, day, visible)
		VALUES
			(
				$userid,
				". $vbulletin->userinfo['userid'] . ",
				" . TIMENOW . ",
				$startstamp,
				" . ($vbulletin->userinfo['options'] & $vbulletin->bf_misc_useroptions['invisible'] ? 0 : 1) . "
			)
	");
}

/**
* Marks a blog as read using the appropriate method.
*
* @param	array	Array of data for the blog being marked
* @param	integer	User ID this thread is being marked read for
* @param	integer	Unix timestamp that the thread is being marked read
*
* @return	void
*/
function mark_blog_read(&$bloginfo, $userid, $time)
{
	global $vbulletin, $db;

	$userid = intval($userid);
	$time = intval($time);

	if ($vbulletin->options['threadmarking'] AND $userid)
	{
		// can't be shutdown as we do a read query below on this table
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "blog_read
				(userid, blogid, readtime)
			VALUES
				($userid, $bloginfo[blogid], $time)
		");
	}
	else
	{
		set_bbarray_cookie('blog_lastview', $bloginfo['blogid'], $time);
	}

	// now if applicable search to see if this was the last entry requiring marking in this blog
	if ($vbulletin->options['threadmarking'] == 2 AND $userid)
	{
		$privatecheck = '';
		if (!can_moderate_blog() AND $userid != $bloginfo['userid'] AND !$bloginfo['buddyid'])
		{
			$privatecheck = "AND ~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
		}
		$cutoff = TIMENOW - ($vbulletin->options['markinglimit'] * 86400);
		$unread = $db->query_first("
			SELECT COUNT(*) AS count
 			FROM " . TABLE_PREFIX . "blog AS blog
 			LEFT JOIN " . TABLE_PREFIX . "blog_read AS blog_read ON (blog_read.blogid = blog.blogid AND blog_read.userid = $userid)
			LEFT JOIN " . TABLE_PREFIX . "blog_userread AS blog_userread ON (blog_userread.bloguserid = blog.userid AND blog_userread.userid = $userid)
			WHERE blog.userid = $bloginfo[userid]
	      		AND blog.state = 'visible'
						$privatecheck
				AND blog.lastcomment > IF(blog_read.readtime IS NULL, $cutoff, blog_read.readtime)
				AND blog.lastcomment > IF(blog_userread.readtime IS NULL, $cutoff, blog_userread.readtime)
				AND blog.lastcomment > $cutoff
		");
		if ($unread['count'] == 0)
		{
			mark_user_blog_read($bloginfo['userid'], $userid, TIMENOW);
		}
	}
}

/**
* Marks a forum as read using the appropriate method.
*
* @param	integer	User ID of the blog owner
* @param	integer	User ID that is being marked read for
* @param	integer	Unix timestamp that the thread is being marked read
*
* @return	array	Returns an array of forums that were marked as read
*/
function mark_user_blog_read($bloguserid, $userid, $time)
{
	global $vbulletin, $db;

	$bloguserid = intval($bloguserid);
	$userid = intval($userid);
	$time = intval($time);

	if (empty($bloguserid))
	{
		// sanity check -- wouldn't work anyway
		return false;
	}

	if ($vbulletin->options['threadmarking'] AND $userid)
	{

		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "blog_userread
				(userid, bloguserid, readtime)
			VALUES
				($userid, $bloguserid, $time)
		");
	}
	else
	{
		set_bbarray_cookie('blog_userread', $bloguserid, $time);
	}

	return true;
}

/**
* Fetch the latest blog entries for the intro page
*
* @param	string	Type to sort on, valid entries are 'latest', 'blograting' and 'rating'
*
* @return	string	HTML for the latest blog entries
*/
function &fetch_latest_blogs($type = 'latest')
{
	global $vbulletin, $show, $vbphrase;

	$sql_and = array();
	$recentblogbits = '';

	switch($type)
	{
		case 'rating':
			$sql_and[] = "blog.ratingnum >= " . intval($vbulletin->options['vbblog_ratingpost']);
			// blogid is needed because mysql is ordering this result different than when just 'rating' is used with a union (do=bloglist)
			$orderby = "blog.rating DESC, blog.blogid";
			$limit = intval($vbulletin->options['vbblog_maxratedentry']);
			break;
		case 'blograting':
			return fetch_rated_blogs();
		default:
			$orderby = "blog.dateline DESC";
			$limit = intval($vbulletin->options['vbblog_maxrecententry']);
	}

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		$sql_and[] = "blog.userid = " . $vbulletin->userinfo['userid'];
	}
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND $vbulletin->userinfo['userid'])
	{
		$sql_and[] = "blog.userid <> " . $vbulletin->userinfo['userid'];
	}

	// get ignored users - just hide them on the latest list
	$ignore = array();
	if (trim($vbulletin->userinfo['ignorelist']))
	{
		$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
		if (!empty($ignorelist))
		{
			$sql_and[] = "blog.userid NOT IN (" . implode(", ", $ignorelist) . ")";
		}
	}

	if (trim($vbulletin->options['globalignore']) != '')
	{
		require_once(DIR . '/includes/functions_bigthree.php');
		if ($coventry = fetch_coventry('string') AND !can_moderate_blog())
		{
			$sql_and[] = "blog.userid NOT IN ($coventry)";
		}
	}

	$state = array('visible');
	if (can_moderate_blog('canmoderateentries'))
	{
		$state[] = 'moderation';
	}

	$sql_and[] = "blog.state IN('" . implode("', '", $state) . "')";
	$sql_and[] = "blog.dateline <= " . TIMENOW;
	$sql_and[] = "blog.pending = 0";

	if ($type == 'latest' AND $vbulletin->options['vbblog_recententrycutoff'])
	{
		switch($vbulletin->options['vbblog_recententrycutoff'])
		{
			case '1d':
				$cutoff = mktime(date('H'), date('i'), date('s'), date('m'), date('d') - 1, date('y'));
				break;
			case '1m':
			case '3m':
			case '6m':
				$cutoff = mktime(date('H'), date('i'), date('s'), date('m') - intval($vbulletin->options['vbblog_recententrycutoff']), date('d'), date('y'));
				break;
			case '1y':
				$cutoff = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('y') - 1);
				break;
			case '1w':
			default:
				$cutoff = TIMENOW - 604800;
		}
		$sql_and[] = "blog.dateline >= $cutoff";
	}

	$sql_join = array();
	$sql_or = array();
	if (!can_moderate_blog())
	{
		if ($vbulletin->userinfo['userid'])
		{
			$sql_or[] = "blog.userid = " . $vbulletin->userinfo['userid'];
			$sql_or[] = "(options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND ignored.relationid IS NOT NULL)";
			$sql_or[] = "(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)";
			$sql_or[] = "(options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR buddy.relationid IS NULL) AND (options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL))";
			$sql_and[] = "(" . implode(" OR ", $sql_or) . ")";

			$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = blog.userid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')";
			$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = blog.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')";

			$sql_and[] = "
				(blog.userid = " . $vbulletin->userinfo['userid'] . "
					OR
				~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . "
					OR
				(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL))";
		}
		else
		{
			$sql_and[] = "options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			$sql_and[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];

		}
	}

	if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']))
	{
		$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
		// The "OR blog.userid = $vb->userinfo[userid] is not here since it will cause duplicate rows AND don't wish to add DISTINCT
		// User does not need to see entries posted to categories that they no longer have canview access to anyway
		$sql_and[] = "cu.blogcategoryid IS NULL";
	}

	// Recently Updated

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('blog_latest_blogs_query')) ? eval($hook) : false;

	$recentupdates = $vbulletin->db->query_read_slave("
		SELECT
			blog.blogid, blog.title, blog.dateline, blog.state, blog.options AS blogoptions, user.*, blog.postedby_userid, blog.postedby_username,
			blog_user.title AS blogtitle, blog_user.bloguserid,
			IF(user.displaygroupid = 0, user.usergroupid, user.displaygroupid) AS displaygroupid, user.infractiongroupid, options_ignore, options_buddy, options_member, options_guest, blog_user.bloguserid
		" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
		$hook_query_fields
		FROM " . TABLE_PREFIX . "blog AS blog " . ($index ? "USE INDEX ($index)" : "") . "
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog.postedby_userid)
		INNER JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog.userid)
		" . (!empty($sql_join) ? implode("\r\n", $sql_join) : "") . "
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
		$hook_query_joins
		WHERE " . implode("\r\n\tAND ", $sql_and) . "
		$hook_query_where
		ORDER BY $orderby
		LIMIT $limit
	");

	while ($updated = $vbulletin->db->fetch_array($recentupdates))
	{
		$updated['blogtitle'] = $updated['blogtitle'] ? $updated['blogtitle'] : $updated['username'];
		$updated = array_merge($updated, convert_bits_to_array($updated['options'], $vbulletin->bf_misc_useroptions));
		$updated = array_merge($updated, convert_bits_to_array($updated['adminoptions'], $vbulletin->bf_misc_adminoptions));
		$updated = array_merge($updated, convert_bits_to_array($updated['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
		fetch_musername($updated);
		fetch_avatar_html($updated, true);
		$updated['title'] = fetch_word_wrapped_string($updated['title'], $vbulletin->options['blog_wordwrap']);
		$updated['postdate'] = vbdate($vbulletin->options['dateformat'], $updated['dateline'], true);
		$updated['posttime'] = vbdate($vbulletin->options['timeformat'], $updated['dateline']);
		$show['moderation'] = ($updated['state'] == 'moderation');
		$show['private'] = false;
		if ($updated['private'])
		{
			$show['private'] = true;
		}
		else if (can_moderate() AND $updated['userid'] != $vbulletin->userinfo['userid'])
		{
			$membercanview = $updated['options_member'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			$buddiescanview = $updated['options_buddy'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			if (!$membercanview AND (!$updated['buddyid'] OR !$buddiescanview))
			{
				$show['private'] = true;
			}
		}

		($hook = vBulletinHook::fetch_hook('blog_latest_blogs_entry')) ? eval($hook) : false;

		$templater = vB_Template::create('blog_home_list_entry');
			$templater->register('updated', $updated);
		$recentblogbits .= $templater->render();
	}

	return $recentblogbits;
}

/**
* Fetch the latest blog comments
*
* @param	string	Type to sort on, valid values is 'latest'
*
* @return	string	HTML for the latest blog comments
*/
function &fetch_latest_comments($type = 'latest')
{
	global $vbulletin, $show, $vbphrase;

	$sql_and = array();
	$sql_or = array();
	$sql_join = array();
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		$sql_and[] = "blog.userid = " . $vbulletin->userinfo['userid'];
	}
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND $vbulletin->userinfo['userid'])
	{
		$sql_and[] = "blog.userid <> " . $vbulletin->userinfo['userid'];
	}

	// get ignored users - just hide them on the latest list
	$ignore = array();
	if (trim($vbulletin->userinfo['ignorelist']))
	{
		$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
		if (!empty($ignorelist))
		{
			$sql_and[] = "blog.userid NOT IN (" . implode(", ", $ignorelist) . ")";
			$sql_and[] = "blog_text.userid NOT IN (". implode(", ", $ignorelist) . ")";
		}
	}

	if (trim($vbulletin->options['globalignore']) != '')
	{
		require_once(DIR . '/includes/functions_bigthree.php');
		if ($coventry = fetch_coventry('string') AND !can_moderate_blog())
		{
			$sql_and[] = "blog.userid NOT IN ($coventry)";
			$sql_and[] = "blog_text.userid NOT IN ($coventry)";
		}
	}

	$state = array('visible');
	if (can_moderate_blog('canmoderateentries'))
	{
		$state[] = 'moderation';
	}

	$sql_and[] = "blog.state IN('" . implode("', '", $state) . "')";
	$sql_and[] = "blog.dateline <= " . TIMENOW;
	$sql_and[] = "blog.pending = 0";
	$sql_and[] = "blog_text.state IN('" . implode("', '", $state) . "')";
	$sql_and[] = "blog.firstblogtextid <> blog_text.blogtextid";

	if ($type == 'latest' AND $vbulletin->options['vbblog_recentcommentcutoff'])
	{
		switch($vbulletin->options['vbblog_recentcommentcutoff'])
		{
			case '1d':
				$cutoff = mktime(date('H'), date('i'), date('s'), date('m'), date('d') - 1, date('y'));
				break;
			case '1m':
			case '3m':
			case '6m':
				$cutoff = mktime(date('H'), date('i'), date('s'), date('m') - intval($vbulletin->options['vbblog_recentcommentcutoff']), date('d'), date('y'));
				break;
			case '1y':
				$cutoff = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('y') - 1);
				break;
			case '1w':
			default:
				$cutoff = TIMENOW - 604800;
		}
			$sql_and[] = "blog_text.dateline >= $cutoff";
	}

	if (!can_moderate_blog())
	{
		if ($vbulletin->userinfo['userid'])
		{
			$sql_or[] = "blog.userid = " . $vbulletin->userinfo['userid'];
			$sql_or[] = "(options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND ignored.relationid IS NOT NULL)";
			$sql_or[] = "(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)";
			$sql_or[] = "(options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR buddy.relationid IS NULL) AND (options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL))";
			$sql_and[] = "(" . implode(" OR ", $sql_or) . ")";

			$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = blog.userid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')";
			$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = blog.userid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')";

			$sql_and[] = "
				(blog.userid = " . $vbulletin->userinfo['userid'] . "
					OR
				~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . "
					OR
				(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL))";
		}
		else
		{
			$sql_and[] = "options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			$sql_and[] = "~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'];
		}
	}

	if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']))
	{
		$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
		// The "OR blog.userid = $vb->userinfo[userid] is not here since it will cause duplicate rows AND don't wish to add DISTINCT
		// User does not need to see entries posted to categories that they no longer have canview access to anyway
		$sql_and[] = "cu.blogcategoryid IS NULL";
	}

	// Recently Updated
	$orderby = 'blog_text.dateline DESC';
	$limit = intval($vbulletin->options['vbblog_maxrecentcomment']);

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('blog_latest_comments_query')) ? eval($hook) : false;

	$recentupdates = $vbulletin->db->query_read_slave("
		SELECT blog.blogid, user.username, blogtextid, blog.title, blog_text.dateline, blog_text.pagetext, user.*, blog_user.title AS blogtitle, blog_text.title AS commenttitle,
			blog_text.state,
			IF(user.displaygroupid = 0, user.usergroupid, user.displaygroupid) AS displaygroupid, user.infractiongroupid, options_ignore, options_buddy, options_member, options_guest
			" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
		$hook_query_fields
		FROM " . TABLE_PREFIX . "blog_text AS blog_text
		LEFT JOIN " . TABLE_PREFIX . "blog AS blog ON (blog.blogid = blog_text.blogid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog_text.userid)
		INNER JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog.userid)
		" . (!empty($sql_join) ? implode("\r\n", $sql_join) : "") . "
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
		$hook_query_joins
		WHERE " . implode("\r\n\tAND ", $sql_and) . "
		$hook_query_where
		ORDER BY $orderby
		LIMIT $limit
	");

	while ($updated = $vbulletin->db->fetch_array($recentupdates))
	{
		$updated = array_merge($updated, convert_bits_to_array($updated['options'], $vbulletin->bf_misc_useroptions));
		$updated = array_merge($updated, convert_bits_to_array($updated['adminoptions'], $vbulletin->bf_misc_adminoptions));
		fetch_musername($updated);
		fetch_avatar_html($updated, true);
		$updated['postdate'] = vbdate($vbulletin->options['dateformat'], $updated['dateline'], true);
		$updated['posttime'] = vbdate($vbulletin->options['timeformat'], $updated['dateline']);
		$updated['title'] = fetch_word_wrapped_string($updated['title'], $vbulletin->options['blog_wordwrap']);
		if ($updated['commenttitle'])
		{
			$updated['excerpt'] = fetch_word_wrapped_string(fetch_trimmed_title($updated['commenttitle'], 50), $vbulletin->options['blog_wordwrap']);
		}
		else
		{
			$updated['excerpt'] = htmlspecialchars_uni(
				fetch_word_wrapped_string(
					fetch_trimmed_title(
						strip_bbcode(
							preg_replace(
								array('#\[img\].*\[/img\]#siU', '#\[url\].*\[/url\]#siU'),
								array($vbphrase['picture_replacement'], $vbphrase['link_replacement']),
								$updated['pagetext'])
							, true, true),
						50
					),
				$vbulletin->options['blog_wordwrap'])
			);
		}
		if (!$updated['blogtitle'])
		{
			$updated['blogtitle'] = $updated['username'];
		}
		$show['moderation'] = ($updated['state'] == 'moderation');
		$show['private'] = false;
		if (can_moderate() AND $updated['userid'] != $vbulletin->userinfo['userid'])
		{
			$membercanview = $updated['options_member'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			$buddiescanview = $updated['options_buddy'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			if (!$membercanview AND (!$updated['buddyid'] OR !$buddiescanview))
			{
				$show['private'] = true;
			}
		}

		($hook = vBulletinHook::fetch_hook('blog_latest_comments_entry')) ? eval($hook) : false;

		$templater = vB_Template::create('blog_home_list_comment');
			$templater->register('updated', $updated);
			$templater->register('pageinfo', array('bt' => $updated['blogtextid']));
		$recentcommentbits .= $templater->render();
	}

	return $recentcommentbits;
}

/**
* Fetch the blogs sorted by rating in descending order
*
* @return	string	HTML for the latest blogs
*/
function &fetch_rated_blogs()
{
	global $vbulletin, $show, $vbphrase;

	$sql_and = array();
	$recentblogbits = '';

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		$sql_and[] = "bu.bloguserid = " . $vbulletin->userinfo['userid'];
	}
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND $vbulletin->userinfo['userid'])
	{
		$sql_and[] = "bu.bloguserid <> " . $vbulletin->userinfo['userid'];
	}

	// get ignored users - just hide them on the latest list
	$ignore = array();
	if (trim($vbulletin->userinfo['ignorelist']))
	{
		$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
		if (!empty($ignorelist))
		{
			$sql_and[] = "bu.bloguserid NOT IN (" . implode(", ", $ignorelist) . ")";
		}
	}

	if (trim($vbulletin->options['globalignore']) != '')
	{
		require_once(DIR . '/includes/functions_bigthree.php');
		if ($coventry = fetch_coventry('string') AND !can_moderate_blog())
		{
			$sql_and[] = "bu.bloguserid NOT IN ($coventry)";
		}
	}

	$sql_and[] = "bu.ratingnum >= " . intval($vbulletin->options['vbblog_ratinguser']);

	$sql_or = array();
	$sql_join = array();
	if (!can_moderate_blog())
	{
		if ($vbulletin->userinfo['userid'])
		{
			$sql_or[] = "bu.bloguserid IN (" . $vbulletin->userinfo['memberblogids'] . ")";
			$sql_or[] = "(options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND ignored.relationid IS NOT NULL)";
			$sql_or[] = "(options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND buddy.relationid IS NOT NULL)";
			$sql_or[] = "(options_member & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " AND (options_buddy & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR buddy.relationid IS NULL) AND (options_ignore & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . " OR ignored.relationid IS NULL))";
			$sql_and[] = "(" . implode(" OR ", $sql_or) . ")";

			$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS buddy ON (buddy.userid = bu.bloguserid AND buddy.relationid = " . $vbulletin->userinfo['userid'] . " AND buddy.type = 'buddy')";
			$sql_join[] = "LEFT JOIN " . TABLE_PREFIX . "userlist AS ignored ON (ignored.userid = bu.bloguserid AND ignored.relationid = " . $vbulletin->userinfo['userid'] . " AND ignored.type = 'ignore')";
		}
		else
		{
			$sql_and[] = "options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
		}
	}

	// Highest Rated
	$orderby = 'bu.rating DESC';
	$limit = intval($vbulletin->options['vbblog_maxratedblog']);

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('blog_rated_blogs_query')) ? eval($hook) : false;

	$recentupdates = $vbulletin->db->query_read_slave("
		SELECT user.*, bu.ratingnum, bu.ratingtotal, bu.title,
			IF(user.displaygroupid = 0, user.usergroupid, user.displaygroupid) AS displaygroupid, infractiongroupid, options_ignore, options_buddy, options_member, options_guest
			" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
		$hook_query_fields
		FROM " . TABLE_PREFIX . "blog_user AS bu " . ($index ? "USE INDEX ($index)" : "") . "
		INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = bu.bloguserid)
		" . (!empty($sql_join) ? implode("\r\n", $sql_join) : "") . "
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
		$hook_query_joins
		WHERE " . implode("\r\n\tAND ", $sql_and) . "
		$hook_query_where
		ORDER BY $orderby
		LIMIT $limit
	");

	while ($updated = $vbulletin->db->fetch_array($recentupdates))
	{
		$updated = array_merge($updated, convert_bits_to_array($updated['options'], $vbulletin->bf_misc_useroptions));
		$updated = array_merge($updated, convert_bits_to_array($updated['adminoptions'], $vbulletin->bf_misc_adminoptions));
		fetch_musername($updated);
		fetch_avatar_html($updated, true);
		if ($updated['ratingnum'] > 0)
		{
			$updated['voteavg'] = vb_number_format($updated['ratingtotal'] / $updated['ratingnum'], 2);
			$updated['rating'] = intval(round($updated['ratingtotal'] / $updated['ratingnum']));
		}
		else
		{
			$updated['voteavg'] = 0;
			$updated['rating'] = 0;
		}
		$updated['title'] = $updated['title'] ? $updated['title'] : $updated['username'];

		$show['private'] = false;
		if (can_moderate() AND $vbulletin->userinfo['userid'] != $updated['userid'])
		{
			$membercanview = $updated['options_member'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			$buddiescanview = $updated['options_buddy'] & $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'];
			if (!$membercanview AND (!$updated['buddyid'] OR !$buddiescanview))
			{
				$show['private'] = true;
			}
		}

		($hook = vBulletinHook::fetch_hook('blog_rated_blogs_entry')) ? eval($hook) : false;

		$templater = vB_Template::create('blog_home_list_blog');
			$templater->register('updated', $updated);
		$recentblogbits .= $templater->render();
	}

	return $recentblogbits;
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 27303 $
|| ####################################################################
\*======================================================================*/
?>
