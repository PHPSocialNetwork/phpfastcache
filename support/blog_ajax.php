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

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('VB_PRODUCT', 'vbblog');
define('THIS_SCRIPT', 'blog_ajax');
define('CSRF_PROTECTION', true);
define('LOCATION_BYPASS', 1);
define('NOPMPOPUP', 1);
define('VBBLOG_PERMS', true);
define('VBBLOG_STYLE', true);
define('VBBLOG_SCRIPT', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'cprofilefield',
	'user',
	'vbblogglobal',
);
if (in_array($_POST['do'], array('quickeditcomment', 'quickeditentry')))
{
	$phrasegroups[] = 'posting';
}
else if ($_POST['do'] == 'loadentry')
{
	$phrasegroups[] = 'postbit';
}

// get special data templates from the datastore
$specialtemplates = array(
	'bbcodecache',
	'blogcategorycache',
);

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'calendar'       => array(
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
	),
	'loadupdated' => array(
		'blog_overview_recentblogbit',
		'blog_overview_recentcommentbit',
		'blog_overview_ratedblogbit',
	),
	'quickeditcomment' => array(
	),
	'quickeditblog' => array(
	),
	'loadcomment' => array(
		'blog_comment',
	),
	'locadentry'  => array(
		'blog_entry',
	),
);

$_POST['ajax'] = 1;

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/class_xml.php');
require_once(DIR . '/includes/functions_user.php');
require_once(DIR . '/includes/blog_functions.php');
require_once(DIR . '/includes/blog_functions_main.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_blog_url();

if (empty($_POST['do']))
{
	$_POST['do'] = 'fetchuserfield';
}

($hook = vBulletinHook::fetch_hook('blog_ajax_start')) ? eval($hook) : false;

// #############################################################################
// return a deleted comment

if ($_POST['do'] == 'loadcomment')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'blogtextid' => TYPE_UINT,
	));

	$bloginfo = verify_blog($blogtextinfo['blogid']);

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	if (
		!$blogtextinfo
			OR
		$bloginfo['firstblogtextid'] == $blogtextinfo['blogtextid']
			OR
		!fetch_comment_perm('canviewcomments', $bloginfo, $blogtextinfo)
	)
	{
		$xml->add_tag('error', 'nopermission');
		$xml->print_xml();
	}

	require_once(DIR . '/includes/class_bbcode.php');
	require_once(DIR . '/includes/class_blog_response.php');

	$bbcode = new vB_BbCodeParser($vbulletin, fetch_tag_list());
	$factory = new vB_Blog_ResponseFactory($vbulletin, $bbcode, $bloginfo);
	$responsebits = '';

	$comment = $db->query_first_slave("
		SELECT blog_text.*, blog_text.ipaddress AS blogipaddress,
			blog_textparsed.pagetexthtml, blog_textparsed.hasimages,
			blog.title AS entrytitle,
			user.*, userfield.*,
			blog_editlog.userid AS edit_userid, blog_editlog.dateline AS edit_dateline, blog_editlog.reason AS edit_reason, blog_editlog.username AS edit_username
			" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
		FROM " . TABLE_PREFIX . "blog_text AS blog_text
		LEFT JOIN " . TABLE_PREFIX . "blog AS blog ON (blog.blogid = blog_text.blogid)
		LEFT JOIN " . TABLE_PREFIX . "blog_textparsed AS blog_textparsed ON (blog_textparsed.blogtextid = blog_text.blogtextid AND blog_textparsed.styleid = " . intval(STYLEID) . " AND blog_textparsed.languageid = " . intval(LANGUAGEID) . ")
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (blog_text.userid = user.userid)
		INNER JOIN " . TABLE_PREFIX . "user AS user2 ON (blog.userid = user2.userid)
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (userfield.userid = blog_text.userid)
		LEFT JOIN " . TABLE_PREFIX . "blog_editlog AS blog_editlog ON (blog_editlog.blogtextid = blog_text.blogtextid)
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
		WHERE
			blog_text.blogtextid = $blogtextinfo[blogtextid]
	");

	$comment['state'] = 'visible';
	$response_handler =& $factory->create($comment);
	$response_handler->userinfo = $bloginfo;

	if ($vbulletin->GPC['linkblog'])
	{
		$response_handler->linkblog = true;
	}

	$xml->add_tag('commentbit', process_replacement_vars($response_handler->construct()));
	$xml->print_xml();
}

// #############################################################################
// return a deleted entry

if ($_POST['do'] == 'loadentry')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'blogid' => TYPE_UINT,
	));

	$bloginfo = verify_blog($blogid);

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	$cats = $db->query_read_slave("
		SELECT blogid, title, blog_category.blogcategoryid, blog_categoryuser.userid
		FROM " . TABLE_PREFIX . "blog_categoryuser AS blog_categoryuser
		LEFT JOIN " . TABLE_PREFIX . "blog_category AS blog_category ON (blog_category.blogcategoryid = blog_categoryuser.blogcategoryid)
		WHERE blogid = $bloginfo[blogid]
		ORDER BY displayorder
	");
	while ($cat = $db->fetch_array($cats))
	{
		$categories["$cat[blogid]"][] = $cat;
	}

	if ($bloginfo['attach'])
	{
		require_once(DIR . '/packages/vbattach/attach.php');
		$attach = new vB_Attach_Display_Content($vbulletin, 'vBBlog_BlogEntry');
		$postattach = $attach->fetch_postattach(0, $bloginfo['blogid']);
	}

	require_once(DIR . '/includes/class_blog_entry.php');
	require_once(DIR . '/includes/class_bbcode_blog.php');
	require_once(DIR . '/includes/class_xml.php');
	$bbcode = new vB_BbCodeParser_Blog_Snippet($vbulletin, fetch_tag_list());
	$factory = new vB_Blog_EntryFactory($vbulletin, $bbcode, $categories);

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	$bloginfo['state'] = 'visible';
	$entry_handler =& $factory->create($bloginfo);
	if ($vbulletin->userinfo['userid'] == $bloginfo['userid'])
	{
		$entry_handler->userinfo = $vbulletin->userinfo;
	}
	$entry_handler->attachments = $postattach;

	$xml->add_tag('entrybit', process_replacement_vars($entry_handler->construct()));
	$xml->print_xml();
}

// #############################################################################
// return a comment in an editor

if ($_POST['do'] == 'quickeditcomment')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'blogtextid' => TYPE_UINT,
		'editorid'   => TYPE_STR
	));

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	if (!$vbulletin->options['quickedit'])
	{
		// if quick edit has been disabled after showthread is loaded, return a string to indicate such
		$xml->add_tag('disabled', 'true');
		$xml->print_xml();
	}
	else
	{
		$vbulletin->GPC['editorid'] = preg_replace('/\W/s', '', $vbulletin->GPC['editorid']);

		/* Check they can view a blog, any blog */
		if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
		{
			$xml->add_tag('error', 'nopermission');
			$xml->print_xml();
		}

		$bloginfo = verify_blog($blogtextinfo['blogid'], 0, 'modifychild');
		if (!$bloginfo)
		{
			$xml->add_tag('error', 'nopermission');
			$xml->print_xml();
		}

		if (!$blogtextinfo)
		{
			$xml->add_tag('error', 'nopermission');
			$xml->print_xml();
		}

		if ($bloginfo['firstblogtextid'] == $blogtextinfo['blogtextid'] OR !fetch_comment_perm('caneditcomments', $bloginfo, $blogtextinfo))
		{
			$xml->add_tag('error', 'nopermission');
			$xml->print_xml();
		}

		$show['quick_edit_form_tag'] = false; //$show['deletepostoption'] = (fetch_comment_perm('candeletecomments', $bloginfo, $blogtextinfo) OR fetch_comment_perm('canremovecomments', $bloginfo, $blogtextinfo));
		$show['softdeleteoption'] = true;
		$show['physicaldeleteoption'] = can_moderate_blog('canremovecomments');

		require_once(DIR . '/includes/functions_editor.php');

		$editorid = construct_edit_toolbar(
			htmlspecialchars_uni($blogtextinfo['pagetext']),
			false,
			'blog_comment',
			$vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowsmilies'],
			$blogtextinfo['allowsmilie'],
			false,
			'qe',
			$vbulletin->GPC['editorid'],
			array(),
			'content',
			'vBBlog_BlogComment',
			$blogtextinfo['blogtextid']
			// We don't need blogid here as this is an edit
		);

		$xml->add_group('quickedit');
		$xml->add_tag('editor', $messagearea, array(
			'reason'       => $blogtextinfo['edit_reason'],
			'parsetype'    => 'blog_comment',
			'parsesmilies' => ($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowsmilies']),
			'mode'         => $show['is_wysiwyg_editor']
		));
		$xml->add_tag('ckeconfig', vB_Ckeditor::getInstance($editorid)->getConfig());
		$xml->close_group();
		$xml->print_xml();
	}
}

// #############################################################################
// return an entry in an editor

if ($_POST['do'] == 'quickeditentry')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'blogid'   => TYPE_UINT,
		'editorid' => TYPE_STR
	));

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	if (!$vbulletin->options['quickedit'])
	{
		// if quick edit has been disabled after showthread is loaded, return a string to indicate such
		$xml->add_tag('disabled', 'true');
		$xml->print_xml();
	}
	else
	{
		$vbulletin->GPC['editorid'] = preg_replace('/\W/s', '', $vbulletin->GPC['editorid']);

		$bloginfo = verify_blog($blogid);;

		if (!fetch_entry_perm('edit', $bloginfo))
		{
			$xml->add_tag('error', 'nopermission');
			$xml->print_xml();
		}

		$show['quick_edit_form_tag'] = false;
		$show['physicaldeleteoption'] = (fetch_entry_perm('remove', $bloginfo) OR $bloginfo['state'] == 'draft' OR $bloginfo['pending']);
		$show['softdeleteoption'] = (fetch_entry_perm('delete', $bloginfo) AND $bloginfo['state'] != 'draft' AND !$bloginfo['pending']);
		$show['deletepostoption'] = ($show['softdeleteoption'] OR $show['physicaldeleteoption']);

		require_once(DIR . '/includes/functions_editor.php');
		require_once(DIR . '/includes/functions_attach.php');

		$posthash = md5(TIMENOW . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']);
		$poststarttime = TIMENOW;

		// Use our permission to attach or the person who owns the post? check what vB does in this situation
		if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpostattach'])
		{
			$values = "values[blogid]=$bloginfo[blogid]";
			require_once(DIR . '/packages/vbattach/attach.php');
			$attach = new vB_Attach_Display_Content($vbulletin, 'vBBlog_BlogEntry');
			$attachmentoption = $attach->fetch_edit_attachments($posthash, $poststarttime, $postattach, $bloginfo['blogid'], $values, $vbulletin->GPC['editorid'], $attachcount);
			$contenttypeid = $attach->fetch_contenttypeid();
		}
		else
		{
			$attachmentoption = '';
			$contenttypeid = 0;
		}

		$editorid = construct_edit_toolbar(
			htmlspecialchars_uni($bloginfo['pagetext']),
			false,
			'blog_entry',
			$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowsmilies'],
			$bloginfo['allowsmilie'],
			false,
			'qe',
			$vbulletin->GPC['editorid'],
			array(),
			'content',
			'vBBlog_BlogEntry',
			$bloginfo['blogid']
		);

		require_once(DIR . '/includes/functions_file.php');
		$xml->add_group('quickedit');
			$xml->add_tag('editor', $messagearea, array(
				'reason'       => $bloginfo['edit_reason'],
				'parsetype'    => 'blog_entry',
				'parsesmilies' => ($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowsmilies']),
				'mode'         => $show['is_wysiwyg_editor']
			));
			add_ajax_attachment_xml($xml, $contenttypeid, $posthash, $poststarttime, array('b' => $bloginfo['blogid']));
			$xml->add_tag('ckeconfig', vB_Ckeditor::getInstance($editorid)->getConfig());
		$xml->close_group();
		$xml->print_xml();
	}
}

// #############################################################################
// retrieve a calendar
if ($_POST['do'] == 'calendar')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'month'  => TYPE_UINT,
		'year'   => TYPE_UINT,
		'userid' => TYPE_UINT,
	));

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	// can't view any blogs, no need for a calendar
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		$xml->add_tag('error', 'nopermission');
		$xml->print_xml();
		exit;
	}

	if (!($month = $vbulletin->GPC['month']) OR $month < 1 OR $month > 12)
	{
		$month = vbdate('n', TIMENOW, false, false);
	}
	if (!($year = $vbulletin->GPC['year']) OR $year > 2037 OR $year < 1970)
	{
		$year = vbdate('Y', TIMENOW, false, false);
	}

	if ($vbulletin->GPC['userid'])
	{
		$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
	}

	$calendar = construct_calendar($month, $year, $userinfo);

	$xml->add_tag('calendar', $calendar);
	$xml->print_xml();
}

// #############################################################################
// fetch latest blogs
if ($_POST['do'] == 'loadupdated')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'type'  => TYPE_NOHTML,
		'which' => TYPE_NOHTML,
	));

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	// can't view any blogs
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
	{
		$xml->add_tag('error', 'nopermission');
		$xml->print_xml();
		exit;
	}

	$noresults = 0;
	if (!($data =& fetch_latest_blogs($vbulletin->GPC['which'])))
	{
		if ($vbulletin->GPC['which'] == 'rating' OR $vbulletin->GPC['which'] == 'blograting')
		{
			$data = fetch_error('blog_no_rated_entries', vB_Template_Runtime::fetchStyleVar('left'), vB_Template_Runtime::fetchStyleVar('imgdir_rating'));
		}
		else
		{
			$data = fetch_error('blog_no_entries');
		}
		$data = htmlspecialchars_uni($data);
		$noresults = 1;
	}

	$xml->add_tag('updated', '', array(
		'which'       => $vbulletin->GPC['which'],
		'type'        => $vbulletin->GPC['type'],
		'data'        => $data,
		'noresults'   => $noresults,
	));
	$xml->print_xml();
}

if ($_REQUEST['do'] == 'moveblock')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'block' => TYPE_ARRAY_UINT,
	));

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		exit;
	}

	$validblocks = $vbulletin->userinfo['sidebar'];

	if ($vbulletin->userinfo['permissions']['vbblog_customblocks'] > 0)
	{
		$customblock = array();
		$customblocks = $db->query_read_slave("
			SELECT customblockid, title
			FROM " . TABLE_PREFIX . "blog_custom_block
			WHERE
				userid = " . $vbulletin->userinfo['userid'] . "
					AND
				type = 'block'
		");
		while ($blockholder = $db->fetch_array($customblocks))
		{
				$customblock["custom$blockholder[customblockid]"] = $blockholder['title'];
		}
	}

	$freeblocks = array();
	$blockbits = '';

	foreach ($vbulletin->bf_misc_vbblogblockoptions AS $key => $value)
	{
		if ($vbulletin->options['vbblog_blocks'] & $value)
		{
			if (!isset($validblocks["$key"]))
			{
				$freeblocks["$key"] = 1;
			}
		}
		else
		{
			unset($validblocks["$key"]);
		}
	}

	if (!empty($freeblocks))
	{
		$validblocks = array_merge($validblocks, $freeblocks);
	}

	if (count($validblocks) <= 1)
	{
		exit;
	}

	$hiddenblocks = array();
	$count = 1;
	foreach($validblocks AS $block => $value)
	{
		if (preg_match('#^custom#', $block))
		{
				if (!$customblock["$block"])
			 	{
			 		unset($validblocks["$block"]);
					continue;
				}
		}
		else
		{
			if (!$vbulletin->bf_misc_vbblogblockoptions["$block"])
			{
				unset($validblocks["$block"]);
				continue;
			}
		}

		// track hiddenblocks and give them a temporary position so that they don't jump to the top when we sort.
		//  A valud of 0 indicates that a block is disabled by the user.
		if (!$value)
		{
			$hiddenblocks["$block"] = true;
			$validblocks["$block"] = $count;
		}
		$count++;
	}

	foreach ($vbulletin->GPC['block'] AS $block => $pos)
	{
		if (preg_match('#^block_custom_(custom\d+)$#', $block, $match))
		{
			if ($validblocks["$match[1]"])
			{
				$validblocks["$match[1]"] = $pos;
			}
		}
		else if ($validblocks["$block"])
		{
			$validblocks["$block"] = $pos;
		}
	}

	asort($validblocks, SORT_NUMERIC);

	// rehide hidden blocks
	foreach($hiddenblocks AS $block => $value)
	{
		$validblocks["$block"] = 0;
	}

	$dataman =& datamanager_init('Blog_User', $vbulletin, ERRTYPE_STANDARD);
	if ($vbulletin->userinfo['bloguserid'])
	{
		$foo = array('bloguserid' => $vbulletin->userinfo['bloguserid']);
		$dataman->set_existing($foo);
	}
	else
	{
		$dataman->set('bloguserid', $vbulletin->userinfo['userid']);
	}

	$dataman->set('sidebar', $validblocks);
	$dataman->save();
}

($hook = vBulletinHook::fetch_hook('blog_ajax_complete')) ? eval($hook) : false;

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 63620 $
|| ####################################################################
\*======================================================================*/
?>
