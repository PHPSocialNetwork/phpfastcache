<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 4.2.1 - Licence Number VBF02D260D
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'showpost');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'showthread',
	'postbit',
	'reputationlevel',
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'im_aim',
	'im_icq',
	'im_msn',
	'im_yahoo',
	'im_skype',
	'postbit',
	'postbit_wrapper',
	'postbit_attachment',
	'postbit_attachmentimage',
	'postbit_attachmentthumbnail',
	'postbit_attachmentmoderated',
	'postbit_ip',
	'postbit_onlinestatus',
	'bbcode_code',
	'bbcode_html',
	'bbcode_php',
	'bbcode_quote',
	'bbcode_video',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/class_postbit.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_forum_url();

$vbulletin->input->clean_array_gpc('r', array(
	'highlight'	=> TYPE_STR,
	'postcount'	=> TYPE_UINT,
	'prefix'	=> TYPE_UINT,
));

// words to highlight from the search engine
if (!empty($vbulletin->GPC['highlight']))
{
	$highlight = str_replace('\*', '[a-z]*', preg_quote(strtolower($vbulletin->GPC['highlight']), '/'));
	$highlightwords = explode(' ', $highlight);
	foreach ($highlightwords AS $val)
	{
		if ($val == 'or' OR $val == 'and' OR $val == 'not')
		{
			continue;
		}
		$replacewords[] = $val;
	}
}

// #######################################################################
// ############################# SHOW POST ###############################
// #######################################################################

if (!$postinfo['postid'])
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
}

if ((!$postinfo['visible'] OR $postinfo['isdeleted']) AND !can_moderate($threadinfo['forumid']))
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
}

if ((!$threadinfo['visible'] OR $threadinfo['isdeleted']) AND !can_moderate($threadinfo['forumid']))
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
}

$forumperms = fetch_permissions($threadinfo['forumid']);
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
{
	print_no_permission();
}
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($threadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR $vbulletin->userinfo['userid'] == 0))
{
	print_no_permission();
}

// check if there is a forum password and if so, ensure the user has it set
verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

if ($_SERVER['REQUEST_METHOD'] != 'POST' OR !$vbulletin->GPC['ajax'])
{
	// redirect to showthread with a 301
	exec_header_redirect(fetch_seo_url('thread|js', $threadinfo, array('p' => $postinfo['postid'])). "#post$postinfo[postid]", 301);
}

$hook_query_fields = $hook_query_joins = '';
($hook = vBulletinHook::fetch_hook('showpost_start')) ? eval($hook) : false;

$post = $db->query_first_slave("
	SELECT
		post.*, post.username AS postusername, post.ipaddress AS ip, IF(post.visible = 2, 1, 0) AS isdeleted,
		user.*, userfield.*, usertextfield.*,
		" . iif($foruminfo['allowicons'], 'icon.title as icontitle, icon.iconpath,') . "
		IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, infractiongroupid,
		" . iif($vbulletin->options['avatarenabled'], 'avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight,') . "
		" . ((can_moderate($threadinfo['forumid'], 'canmoderateposts') OR can_moderate($threadinfo['forumid'], 'candeleteposts')) ? 'spamlog.postid AS spamlog_postid,' : '') . "
		editlog.userid AS edit_userid, editlog.username AS edit_username, editlog.dateline AS edit_dateline, editlog.reason AS edit_reason, editlog.hashistory,
		postparsed.pagetext_html, postparsed.hasimages,
		sigparsed.signatureparsed, sigparsed.hasimages AS sighasimages,
		sigpic.userid AS sigpic, sigpic.dateline AS sigpicdateline, sigpic.width AS sigpicwidth, sigpic.height AS sigpicheight
		" . iif(!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehiddencustomfields']), $vbulletin->profilefield['hidden']) . "
		$hook_query_fields
	FROM " . TABLE_PREFIX . "post AS post
	LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = post.userid)
	LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
	LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
	" . iif($foruminfo['allowicons'], "LEFT JOIN " . TABLE_PREFIX . "icon AS icon ON(icon.iconid = post.iconid)") . "
	" . iif($vbulletin->options['avatarenabled'], "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)") . "
	" . ((can_moderate($threadinfo['forumid'], 'canmoderateposts') OR can_moderate($threadinfo['forumid'], 'candeleteposts')) ? "LEFT JOIN " . TABLE_PREFIX . "spamlog AS spamlog ON(spamlog.postid = post.postid)" : '') . "
	LEFT JOIN " . TABLE_PREFIX . "editlog AS editlog ON(editlog.postid = post.postid)
	LEFT JOIN " . TABLE_PREFIX . "postparsed AS postparsed ON(postparsed.postid = post.postid AND postparsed.styleid = " . intval(STYLEID) . " AND postparsed.languageid = " . intval(LANGUAGEID) . ")
	LEFT JOIN " . TABLE_PREFIX . "sigparsed AS sigparsed ON(sigparsed.userid = user.userid AND sigparsed.styleid = " . intval(STYLEID) . " AND sigparsed.languageid = " . intval(LANGUAGEID) . ")
	LEFT JOIN " . TABLE_PREFIX . "sigpic AS sigpic ON(sigpic.userid = post.userid)
	$hook_query_joins
	WHERE post.postid = $postid
");

// Tachy goes to coventry
if (in_coventry($threadinfo['postuserid']) AND !can_moderate($threadinfo['forumid']))
{
	// do not show post if part of a thread from a user in Coventry and bbuser is not mod
	eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
}
if (in_coventry($post['userid']) AND !can_moderate($threadinfo['forumid']))
{
	// do not show post if posted by a user in Coventry and bbuser is not mod
	eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
}

// check for attachments
if ($post['attach'])
{
	$types = vB_Types::instance();
	$contenttypeid = $types->getContentTypeID('vBForum_Post');

	$attachments = $db->query_read_slave("
		SELECT
			fd.thumbnail_dateline, fd.filesize, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail, fd.thumbnail_filesize,
			a.dateline, a.state, a.attachmentid, a.counter, a.contentid AS postid, a.filename,
			type.contenttypes
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
		LEFT JOIN " . TABLE_PREFIX . "attachmenttype AS type ON (fd.extension = type.extension)
		WHERE
			a.contentid = $postid
				AND
			a.contenttypeid = $contenttypeid
		ORDER BY a.displayorder
	");
	while ($attachment = $db->fetch_array($attachments))
	{
		$content = @unserialize($attachment['contenttypes']);
		$attachment['newwindow'] = $content["$contenttypeid"]['n'];
		$post['attachments']["$attachment[attachmentid]"] = $attachment;
	}
}

if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canseethumbnails']))
{
	$vbulletin->options['attachthumbs'] = 0;
}
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['cangetattachment']))
{
	$vbulletin->options['viewattachedimages'] = (($vbulletin->options['viewattachedimages'] AND $vbulletin->options['attachthumbs']) ? 1 : 0);
}

// needed for deleted post management
$show['managepost'] = (can_moderate($threadinfo['forumid'], 'candeleteposts') OR can_moderate($threadinfo['forumid'], 'canremoveposts')) ? true : false;
$show['approvepost'] = (can_moderate($threadinfo['forumid'], 'canmoderateposts')) ? true : false;
$show['managethread'] = (can_moderate($threadinfo['forumid'], 'canmanagethreads')) ? true : false;
$show['inlinemod'] = ($show['managethread'] OR $show['managepost'] OR $show['approvepost']) ? true : false;
$show['multiquote_global'] = ($vbulletin->options['multiquote'] AND $vbulletin->userinfo['userid']);
if ($show['multiquote_global'])
{
	$vbulletin->input->clean_array_gpc('c', array(
		'vbulletin_multiquote' => TYPE_STR
	));
	$vbulletin->GPC['vbulletin_multiquote'] = explode(',', $vbulletin->GPC['vbulletin_multiquote']);
}
// work out if quickreply should be shown or not
if (
	$vbulletin->options['quickreply']
	AND
	!$threadinfo['isdeleted'] AND !is_browser('netscape') AND $vbulletin->userinfo['userid']
	AND (
		($vbulletin->userinfo['userid'] == $threadinfo['postuserid'] AND $forumperms & $vbulletin->bf_ugp_forumpermissions['canreplyown'])
		OR
		($vbulletin->userinfo['userid'] != $threadinfo['postuserid'] AND $forumperms & $vbulletin->bf_ugp_forumpermissions['canreplyothers'])
	)
	AND ($threadinfo['open'] OR can_moderate($threadinfo['forumid'], 'canopenclose'))
	AND (!fetch_require_hvcheck('post'))
)
{
	$show['quickreply'] = true;
}
else
{
	$show['quickreply'] = false;
}
$show['lightbox'] = ($vbulletin->options['lightboxenabled'] AND $vbulletin->options['usepopups']);
$show['spacer'] = false;

$saveparsed = ''; // inialise

$post['postcount'] =& $vbulletin->GPC['postcount'];

$postbit_factory = new vB_Postbit_Factory();
$postbit_factory->registry =& $vbulletin;
$postbit_factory->forum =& $foruminfo;
$postbit_factory->thread =& $threadinfo;
$postbit_factory->cache = array();
$postbit_factory->bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

$postbit_obj =& $postbit_factory->fetch_postbit('post');
if ($vbulletin->GPC['prefix'])
{
	$postbit_obj->set_template_prefix('vbcms_');
	if ($vbulletin->options['avatarenabled'] AND $vbulletin->userinfo['showavatars'] AND !$post['hascustomavatar'] AND !$post['avatarid'])
	{
		$post['hascustomavatar'] = 1;
		$post['avatarid'] = true;
		// explicity setting avatarurl to allow guests comments to show unknown avatar
		$post['avatarurl'] = $post['avatarpath'] = vB_Template_Runtime::fetchStyleVar('imgdir_misc') . '/unknown.gif';
		$post['avwidth'] = 60;
		$post['avheight'] = 60;	
	}
}
$postbit_obj->highlight =& $replacewords;
$postbit_obj->cachable = (!$post['pagetext_html'] AND $vbulletin->options['cachemaxage'] > 0 AND (TIMENOW - ($vbulletin->options['cachemaxage'] * 60 * 60 * 24)) <= $threadinfo['lastpost']);

($hook = vBulletinHook::fetch_hook('showpost_post')) ? eval($hook) : false;

$postbits = $postbit_obj->construct_postbit($post);

// save post to cache if relevant
if ($postbit_obj->cachable)
{
	/*insert query*/
	$db->shutdown_query("
		REPLACE INTO " . TABLE_PREFIX . "postparsed (postid, dateline, hasimages, pagetext_html, styleid, languageid)
		VALUES (
			$post[postid], " .
			intval($threadinfo['lastpost']) . ", " .
			intval($postbit_obj->post_cache['has_images']) . ", '" .
			$db->escape_string($postbit_obj->post_cache['text']) . "', " .
			intval(STYLEID) . ", " .
			intval(LANGUAGEID) . "
			)
	");
}

($hook = vBulletinHook::fetch_hook('showpost_complete')) ? eval($hook) : false;

require_once(DIR . '/includes/class_xml.php');
$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
$xml->add_tag('postbit', process_replacement_vars($postbits));
$xml->print_xml();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 63221 $
|| ####################################################################
\*======================================================================*/
?>
