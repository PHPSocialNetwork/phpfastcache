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
define('THIS_SCRIPT', 'blog_post');
define('CSRF_PROTECTION', true);
define('VBBLOG_PERMS', true);
define('VBBLOG_STYLE', true);
define('VBBLOG_SCRIPT', true);
define('GET_EDIT_TEMPLATES', 'newblog,editblog,updateblog,comment,editcomment,postcomment');

if ($_POST['do'] == 'postcomment')
{
	if (isset($_POST['ajax']))
	{
		define('NOPMPOPUP', 1);
		define('NOSHUTDOWNFUNC', 1);
	}
	if (isset($_POST['fromquickcomment']))
	{	// Don't update Who's Online for Quick Comments since it will get stuck on that until the user goes somewhere else
		define('LOCATION_BYPASS', 1);
	}
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'posting',
	'vbblogglobal',
	'vbblogcat',
	'postbit',
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
	'blogcategorycache',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'BLOG',
	'blog_css',
	'blog_usercss',
	'blog_header_custompage_link',
	'blog_sidebar_category_link',
	'blog_sidebar_comment_link',
	'blog_sidebar_custompage_link',
	'blog_sidebar_entry_link',
	'blog_sidebar_user_block_archive',
	'blog_sidebar_user_block_category',
	'blog_sidebar_user_block_comments',
	'blog_sidebar_user_block_entries',
	'blog_sidebar_user_block_search',
	'blog_sidebar_user_block_tagcloud',
	'blog_sidebar_user_block_visitors',
	'blog_sidebar_user_block_custom',
	'blog_sidebar_user',
	'blog_sidebar_calendar',
	'blog_sidebar_calendar_day',
	'blog_tag_cloud_link',
	'ad_blogsidebar_start',
	'ad_blogsidebar_middle',
	'ad_blogsidebar_end',
	'fbpublishcheckbox',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'comment'			=>	array(
		'blog_blogpost_quote',
		'blog_comment_editor',
		'blog_entry_editor_preview',
		'blog_rules',
		'humanverify',
	),
	'editcomment'     => array(
		'blog_comment_editor',
		'blog_rules',
	),
	'newblog'			=> array(
		'blog_blogpost_quote',
		'blog_entry_editor_category',
		'newpost_attachment',
		'newpost_attachmentbit',
		'blog_entry_editor',
		'blog_entry_editor_preview',
		'blog_entry_editor_draft',
		'blog_rules',
	),
	'editblog'        => array(
		'blog_entry_editor_category',
		'blog_entry_editor',
		'newpost_attachment',
		'newpost_attachmentbit',
		'blog_entry_editor_preview',
		'blog_entry_editor_draft',
		'blog_rules',
	),
	'notify'				=> array(
		'blog_notify_urls',
		'blog_notify_urls_url',
		'newpost_preview',
		'newpost_errormessage',
	),
	'edittrackback'	=> array(
		'blog_edit_trackback',
	),
);
$actiontemplates['postcomment'] =& $actiontemplates['comment'];
$actiontemplates['updateblog'] =& $actiontemplates['editblog'];
$actiontemplates['donotify'] =& $actiontemplates['notify'];
$actiontemplates['updatetrackback'] =& $actiontemplates['edittrackback'];

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'newblog';
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/blog_functions_post.php');

verify_blog_url();


// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// ### STANDARD INITIALIZATIONS ###
$checked = array();
$blog = array();
$postattach = array();
$show['moderatecomments'] = (!$vbulletin->options['blog_commentmoderation'] AND $vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_followcommentmoderation'] ? true : false);
$show['pingback'] = ($vbulletin->options['vbblog_pingback'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canreceivepingback'] ? true : false);
$show['trackback'] = ($vbulletin->options['vbblog_trackback'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canreceivepingback'] ? true : false);
$show['notify'] = ($vbulletin->options['vbblog_notifylinks'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cansendpingback'] AND $vbulletin->userinfo['guest_canviewmyblog'] ? true : false);
$show['tag_option'] = ($vbulletin->options['vbblog_tagging'] AND $vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_cantagown']);

/* Check they can view a blog, any blog */
if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']) AND !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
{
	print_no_permission();
}

($hook = vBulletinHook::fetch_hook('blog_post_start')) ? eval($hook) : false;

// #######################################################################
if ($_POST['do'] == 'donotify')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blogid'    => TYPE_UINT,
		'notifyurl' => TYPE_ARRAY_BOOL,
	));

	// can we edit this blog? We need answers!
	$bloginfo = fetch_bloginfo($vbulletin->GPC['blogid']);

	if ($bloginfo['state'] !== 'visible' OR $vbulletin->userinfo['userid'] != $bloginfo['userid'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
	}

	if (!empty($vbulletin->GPC['notifyurl']) AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cansendpingback'] AND $vbulletin->options['vbblog_notifylinks'])
	{
		if (count($vbulletin->GPC['notifyurl']) > $vbulletin->options['vbblog_notifylinks'])
		{
			$_REQUEST['do'] = 'notify';
			require_once(DIR . '/includes/functions_newpost.php');
			$errors = construct_errors(array(fetch_error('blog_too_many_links'))); // this will take the preview's place
		}
		else
		{
			if ($urls = fetch_urls($bloginfo['pagetext']))
			{
				$counter = 0;
				foreach($urls AS $url)
				{
					if (isset($vbulletin->GPC['notifyurl']["$counter"]))
					{
						send_ping_notification($bloginfo, $url, $vbulletin->userinfo['blog_title'] ? $vbulletin->userinfo['blog_title'] : $vbulletin->userinfo['userid']);
					}
					$counter++;
				}
			}
		}
	}

	$vbulletin->url = fetch_seo_url('entry', $bloginfo);
	print_standard_redirect('redirect_blog_entrythanks');  
}

// #######################################################################
if ($_REQUEST['do'] == 'notify')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blogid'    => TYPE_UINT
	));

	$bloginfo = fetch_bloginfo($vbulletin->GPC['blogid']);
	if ($bloginfo['state'] !== 'visible' OR !fetch_entry_perm('edit', $bloginfo))
	{
		standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
	}

	if ($vbulletin->options['vbblog_notifylinks'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cansendpingback'])
	{
		if ($urls = fetch_urls($bloginfo['pagetext']))
		{
			$urlbits = '';
			if (count($urls) > $vbulletin->options['vbblog_notifylinks'])
			{
				$show['urllimit'] = true;
			}
			$counter = 0;
			foreach($urls AS $url)
			{
				$url = htmlspecialchars($url);
				$checked = (isset($vbulletin->GPC['notifyurl']["$counter"]) ? 'checked="checked"' : '');
				$templater = vB_Template::create('blog_notify_urls_url');
					$templater->register('checked', $checked);
					$templater->register('counter', $counter);
					$templater->register('url', $url);
				$urlbits .= $templater->render();
				$counter++;
			}

			$sidebar =& build_user_sidebar($bloginfo);

			// navbar and output
			$navbits = array(
				 fetch_seo_url('blog', $bloginfo) => $bloginfo['blog_title'],
				 fetch_seo_url('entry', $bloginfo) => $bloginfo['title']
			);

			$templater = vB_Template::create('blog_notify_urls');
				$templater->register('bloginfo', $bloginfo);
				$templater->register('errors', $errors);
				$templater->register('urlbits', $urlbits);
			$content = $templater->render();
		}
	}
}

// #######################################################################
if ($_POST['do'] == 'updateblog')
{
	// Variables reused in templates
	$posthash = $vbulletin->input->clean_gpc('p', 'posthash', TYPE_NOHTML);
	$poststarttime = $vbulletin->input->clean_gpc('p', 'poststarttime', TYPE_UINT);

	$vbulletin->input->clean_array_gpc('p', array(
		'blogid'           => TYPE_UINT,
		'title'            => TYPE_NOHTML,
		'message'          => TYPE_STR,
		'wysiwyg'          => TYPE_BOOL,
		'preview'          => TYPE_STR,
		'draft'            => TYPE_STR,
		'disablesmilies'   => TYPE_BOOL,
		'parseurl'         => TYPE_BOOL,
		'status'           => TYPE_STR,
		'categories'       => TYPE_ARRAY_UINT,
		'reason'           => TYPE_NOHTML,
		'allowcomments'    => TYPE_BOOL,
		'moderatecomments' => TYPE_BOOL,
		'allowpingback'    => TYPE_BOOL,
		'notify'           => TYPE_BOOL,
		'private'          => TYPE_BOOL,
		'publish'          => TYPE_ARRAY_UINT,
		'emailupdate'      => TYPE_STR,
		'advanced'         => TYPE_BOOL,
		'ajax'             => TYPE_BOOL,
		'taglist'          => TYPE_NOHTML,
		'userid'           => TYPE_UINT,
		'htmlstate'        => TYPE_STR,
	));

	if (!$vbulletin->userinfo['userid']) // Guests can not make entries
	{
		print_no_permission();
	}

	($hook = vBulletinHook::fetch_hook('blog_post_updateentry_start')) ? eval($hook) : false;

	// unwysiwygify the incoming data
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$vbulletin->GPC['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowhtml']);
	}

	// parse URLs in message text
	if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode'] AND $vbulletin->GPC['parseurl'])
	{
		require_once(DIR . '/includes/functions_newpost.php');
		$vbulletin->GPC['message'] = convert_url_to_bbcode($vbulletin->GPC['message']);
	}

	// handle clicks on the 'save draft' button
	if (!empty($vbulletin->GPC['draft']))
	{
		$vbulletin->GPC['status'] = 'draft';
	}

	if ($vbulletin->GPC['status'] == 'publish_on')
	{
		require_once(DIR . '/includes/functions_misc.php');
		$blog['dateline'] = vbmktime($vbulletin->GPC['publish']['hour'], $vbulletin->GPC['publish']['minute'], 0, $vbulletin->GPC['publish']['month'], $vbulletin->GPC['publish']['day'], $vbulletin->GPC['publish']['year']);
	}

	$blogman =& datamanager_init('Blog_Firstpost', $vbulletin, ERRTYPE_ARRAY, 'blog');

	if ($vbulletin->GPC['blogid'])
	{	// Editing
		$bloginfo = verify_blog($blogid);
		/* Check edit blog */
		if (!fetch_entry_perm('edit', $bloginfo))
		{
			print_no_permission();
		}
		$show['edit'] = true;
		$userinfo = fetch_userinfo($bloginfo['userid']);
		$blogman->set_existing($bloginfo);
		$blogman->set_info('userinfo', $userinfo);
		$bloguserid = $bloginfo['userid'];

		$show['tag_option'] = false;
	}
	else
	{
		if ($vbulletin->GPC['userid'])
		{
			if (!($userinfo = fetch_userinfo($vbulletin->GPC['userid'])) OR !is_member_of_blog($vbulletin->userinfo, $userinfo))
			{
				standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink']));
			}
		}
		else
		{
			$userinfo =& $vbulletin->userinfo;
		}

		if (	// VBIV-13291, Check blog posting permission.
				!($userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpost'])
					OR
				!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']	)
		)
		{
			print_no_permission();
		}

		$bloguserid = $userinfo['userid'];
		$blogman->set('userid', $userinfo['userid']);
		$blogman->set('bloguserid', $userinfo['userid']);
		$blogman->set('postedby_userid', $vbulletin->userinfo['userid']);
		$blogman->set_info('userinfo', $userinfo);
		if ($show['tag_option'] AND $vbulletin->GPC['taglist'])
		{
			$blog['taglist'] =& $vbulletin->GPC['taglist'];
			$blog['postedby_userid'] = $vbulletin->userinfo['userid'];

			require_once(DIR . '/includes/class_taggablecontent.php');
			$content = vB_Taggable_Content_Item::create($vbulletin, 'vBBlog_BlogEntry', $blog['blogid'], $blog);
			$limits = $content->fetch_tag_limits();
			$content->filter_tag_list_content_limits($blog['taglist'], $limits, $tag_errors, true, false);

			if ($tag_errors)
			{
				foreach ($tag_errors AS $error)
				{
					$blogman->error($error);
				}
			}
		}
	}

	if ($vbulletin->GPC['ajax'])
	{
		$blog = $bloginfo;
		$blog['title'] = unhtmlspecialchars($blog['title']);
		$blog['message'] = convert_urlencoded_unicode($vbulletin->GPC['message']);
		$blog['reason']  = fetch_censored_text(convert_urlencoded_unicode($vbulletin->GPC['reason']));
		$blog['disablesmilies'] = !($bloginfo['allowsmilie']);
		$blog['parseurl'] = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode']);
	}
	else
	{
		if ($vbulletin->GPC['advanced'])
		{
			$blog = $bloginfo;
			$blog['title']    = unhtmlspecialchars($bloginfo['title']);
			$blog['parseurl'] = false;
			$blog['message']  = convert_urlencoded_unicode($vbulletin->GPC['message']);
			$blog['reason']   = fetch_censored_text(convert_urlencoded_unicode($vbulletin->GPC['reason']));
		}
		else
		{
			$blog['moderatecomments'] = $vbulletin->GPC['moderatecomments'];
			$blog['allowcomments']    = $vbulletin->GPC['allowcomments'];
			$blog['categories_array'] = $vbulletin->GPC['categories'];
			$blog['notify']           = $vbulletin->GPC['notify'];
			$blog['parseurl']         = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode'] AND $vbulletin->GPC['parseurl']);
			$blog['status']           = $vbulletin->GPC['status'];
			$blog['allowpingback']    = $vbulletin->GPC['allowpingback'];
			$blog['title']            = $vbulletin->GPC['title'];
			$blog['disablesmilies']   = $vbulletin->GPC['disablesmilies'];
			$blog['private']          = $vbulletin->GPC['private'];
			$blog['reason']           = $vbulletin->GPC['reason'];
			$blog['message']          = $vbulletin->GPC['message'];

			if ($blog['status'] == 'publish_now' AND ($bloginfo['dateline'] > TIMENOW OR $bloginfo['state'] == 'draft'))
			{
				$blog['dateline'] = TIMENOW;
			}
			// if we have a dateline then set it
			if ($blog['dateline'])
			{
				$blogman->set('dateline', $blog['dateline']);
			}
			$blogman->set_info('notify', $blog['notify']);

			/* Drafts are exempt from initial moderation */
			if ($blog['status'] == 'draft')
			{
				$blogman->set('state', 'draft');
			}
			/* moderation is on, usergroup permissions are following the scheme and its 
			   not a moderator who can simply moderate */
			else if (
				(
					$vbulletin->options['vbblog_postmoderation']
						OR
					!($userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_followpostmoderation'])
				)
					AND
				!can_moderate_blog('canmoderateentries', $userinfo)
					AND
				(!$bloginfo['blogid'] OR ($bloginfo['state'] == 'draft' AND $blog['status'] != 'draft'))
			)
			{
				$blogman->set('state', 'moderation');
			}
			else if (
				$userinfo['userid'] != $vbulletin->userinfo['userid']
					AND
				$userinfo['grouppermissions'] & $vbulletin->bf_misc_vbbloggrouppermissions['moderateentries']
					AND
				!can_moderate_blog('canmoderateentries')
					AND
				!$bloginfo['blogid']
			)
			{
				$blogman->set('state', 'moderation');
				$blogman->set_bitfield('options', 'membermoderate', true);
			}
			else if ($bloginfo['state'] == 'draft' AND $blog['status'] != 'draft')
			{
				$blogman->set('state', 'visible');
			}

			if ($show['moderatecomments'])
			{
				$blogman->set_bitfield('options', 'moderatecomments', $blog['moderatecomments']);
			}
			if ($show['pingback'] OR $show['trackback'])
			{
				$blogman->set_bitfield('options', 'allowpingback', $blog['allowpingback']);
			}
			$blogman->set_bitfield('options', 'private', $blog['private']);
			$blogman->set_bitfield('options', 'allowcomments', $blog['allowcomments']);

			$blogman->set_info('categories', $blog['categories_array']);
			$blogman->set_info('emailupdate', $vbulletin->GPC['emailupdate']);
		}
	}

	if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowhtml'] AND $vbulletin->GPC_exists['htmlstate'])
	{
		$blog['htmlstate'] = array_pop($array = array_keys(fetch_htmlchecked($vbulletin->GPC['htmlstate'])));
		$blogman->set('htmlstate', $blog['htmlstate']);
	}

	$blogman->set('title', $blog['title']);
	$blogman->set('pagetext', $blog['message']);
	$blogman->set('allowsmilie', !$blog['disablesmilies']);
	$blogman->set_info('posthash', $posthash);
	if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowhtml'])
	{
		$htmlchecked = fetch_htmlchecked( $vbulletin->GPC_exists['htmlstate'] ? $vbulletin->GPC['htmlstate'] : $blog['htmlstate']);
		$show['htmloption'] = true;
	}

	$blogman->pre_save();

	$errors = $blogman->errors;

	if (!empty($errors))
	{
		if ($vbulletin->GPC['ajax'])
		{
			require_once(DIR . '/includes/class_xml.php');
			$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
			$xml->add_group('errors');
			foreach ($blogman->errors AS $error)
			{
				$xml->add_tag('error', $error);
			}
			$xml->close_group();
			$xml->print_xml();
		}
		else
		{
			define('POSTPREVIEW', true);
			$preview = construct_errors($errors); // this will take the preview's place
			$previewpost = true;
			$_REQUEST['do'] = $bloginfo ? 'editblog' : 'newblog';
		}
	}
	else if ($vbulletin->GPC['preview'])
	{
		define('POSTPREVIEW', true);

		if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpostattach'])
		{
			require_once(DIR . '/packages/vbattach/attach.php');
			$attach = new vB_Attach_Display_Content($vbulletin, 'vBBlog_BlogEntry');
			$postattach = $attach->fetch_postattach($posthash, $bloginfo['blogid'], explode(',', $userinfo['memberids']));
		}

		$blog['blogid'] = ($blogid ? $blogid : 0);
		$preview = process_blog_preview($blog, 'entry', $postattach);

		$_REQUEST['do'] = $bloginfo ? 'editblog' : 'newblog';
	}
	else if ($vbulletin->GPC['advanced'])
	{
		$_REQUEST['do'] = 'editblog';
	}
	else
	{
		if ($bloginfo)
		{
			$blogman->save();
			clear_autosave_text('vBBlog_BlogEntry', $bloginfo['blogid'], 0, $vbulletin->userinfo['userid']);

			$update_edit_log = true;

			($hook = vBulletinHook::fetch_hook('blog_post_updateentry_edit')) ? eval($hook) : false;

			if ($bloginfo['state'] == 'draft' OR $bloginfo['pending'] == 1 OR (!($permissions['genericoptions'] & $vbulletin->bf_ugp_genericoptions['showeditedby']) AND ($blog['reason'] === '' OR $blog['reason'] == $bloginfo['edit_reason'])))
			{
				$update_edit_log = false;
			}

			if ($update_edit_log)
			{
				if ($bloginfo['dateline'] < (TIMENOW - ($vbulletin->options['noeditedbytime'] * 60)) OR !empty($blog['reason']))
				{
					/*insert query*/
					$db->query_write("
						REPLACE INTO " . TABLE_PREFIX . "blog_editlog (blogtextid, userid, username, dateline, reason)
						VALUES ($bloginfo[firstblogtextid], " . $vbulletin->userinfo['userid'] . ", '" . $db->escape_string($vbulletin->userinfo['username']) . "', " . TIMENOW . ", '" . $db->escape_string($blog['reason']) . "')
					");
				}
			}

			// if this is a mod edit, then log it
			if (!is_member_of_blog($vbulletin->userinfo, $bloginfo) AND can_moderate('caneditentries'))
			{
				require_once(DIR . '/includes/blog_functions_log_error.php');
				blog_moderator_action($bloginfo, 'blogentry_x_edited', array($bloginfo['title']));
			}

			build_blog_user_counters($bloginfo['userid']);

			if ($vbulletin->GPC['ajax'])
			{
				$cats = $db->query_read_slave("
					SELECT blogid, title, blog_category.blogcategoryid, blog_categoryuser.userid, blog_category.userid AS creatorid
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
					$post['attachments'] = $attach->fetch_postattach(0, $bloginfo['blogid']);
				}

				require_once(DIR . '/includes/class_blog_entry.php');
				require_once(DIR . '/includes/class_bbcode_blog.php');
				require_once(DIR . '/includes/class_xml.php');
				$bbcode = new vB_BbCodeParser_Blog_Snippet($vbulletin, fetch_tag_list());
				$factory = new vB_Blog_EntryFactory($vbulletin, $bbcode, $categories);

				$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
				$xml->add_group('entrybits');

				$bloginfo = fetch_bloginfo($bloginfo['blogid'], false);

				// TODO - We need to know from AJAX whether $userinfo is set, e.g. do=list&u=9 OR do=list
				$entry_handler =& $factory->create($bloginfo);
				if ($vbulletin->userinfo['userid'] == $bloginfo['userid'])
				{
					$entry_handler->userinfo = $vbulletin->userinfo;
				}
				// no attachment support for lists at this time
				$entry_handler->attachments = $post['attachments'];
				$rentry = process_replacement_vars($entry_handler->construct());
				$xml->add_tag('message', process_replacement_vars($rentry));
				$xml->close_group();
				$xml->print_xml();
			}
			else
			{
				if ($show['notify'] AND $blog['notify'])
				{
					if ($urls = fetch_urls($vbulletin->GPC['message']))
					{
						$vbulletin->url = fetch_seo_url('blogpost', array(), array('do' => 'notify', 'b' => $bloginfo['blogid']));
						print_standard_redirect('blog_editthanks_notify');  
					}
				}

				$vbulletin->url = fetch_seo_url('entry', $bloginfo);
				print_standard_redirect('redirect_blog_editthanks');  
			}
		}
		else
		{
			// ### DUPE CHECK ###
			$dupehash = md5($blog['categoryid'] . $blog['title'] . $blog['message'] . $vbulletin->userinfo['userid'] . 'blog');

			($hook = vBulletinHook::fetch_hook('blog_post_updateentry_new')) ? eval($hook) : false;

			if ($prevcomment = $vbulletin->db->query_first("
				SELECT blogid
				FROM " . TABLE_PREFIX . "blog_hash
				WHERE userid = " . $vbulletin->userinfo['userid'] . " AND
					dupehash = '" . $vbulletin->db->escape_string($dupehash) . "' AND
					dateline > " . (TIMENOW - 300) . "
			"))
			{
				// won't have the title set, shouldn't matter
				$vbulletin->url = fetch_seo_url('entry', $prevcomment);				
				print_standard_redirect('blog_duplicate_comment', true, true);  
			}
			else
			{
				if ($show['tag_option'] AND $blog['taglist'])
				{
					$blogman->set_info('addtags', true);
				}
				if ($blogcomment = $blogman->save())
				{	// Parse Notify Links
					clear_autosave_text('vBBlog_BlogEntry', 0, $userinfo['userid'], $vbulletin->userinfo['userid']);
					build_blog_user_counters($userinfo['userid']);
					if ($show['tag_option'] AND $blog['taglist'])
					{
						require_once(DIR . '/includes/class_taggablecontent.php');
   						$content = vB_Taggable_Content_Item::create($vbulletin, "vBBlog_BlogEntry",	$blogman->blog['blogid'], $blogman->blog);

						$limits = $content->fetch_tag_limits();
						$content->add_tags_to_content($blog['taglist'], $limits);
					}

					($hook = vBulletinHook::fetch_hook('blog_post_updateentry_complete')) ? eval($hook) : false;

					if ($show['notify'] AND $blog['notify'])
					{
						if ($urls = fetch_urls($vbulletin->GPC['message']))
						{
							$vbulletin->url = fetch_seo_url('blogpost', array(), array('do' => 'notify', 'b' => $blogcomment));
							print_standard_redirect('blog_entrythanks_notify');  
						}
					}
				}
				if (defined('VB_API') AND VB_API === true)
				{
					$show['blogid'] = $blogman->blog['blogid'];
				}

				$vbulletin->url = fetch_seo_url('entry', $blogman->blog, array(), 'blogid', 'title');

				$pending = ($vbulletin->GPC['status'] == 'publish_on' AND $blog['dateline'] > TIMENOW) ? true : false;
				// attempt to publish blog entry to user's Facebook feed (if this is not a draft)
				if (is_facebookenabled() AND $vbulletin->GPC['status'] != 'draft' AND !$pending)
				{
					publishtofacebook_blogentry($blog['title'], $blog['message'], create_full_url(fetch_seo_url('entry|js', $blogman->blog)));
				}

				print_standard_redirect('redirect_blog_entrythanks');  
			}
		}
	}

	unset($blogman);
}

// #######################################################################
if ($_REQUEST['do'] == 'newblog')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT,
	));

	// verify the userid exists, don't want useless entries in our table.
	if ($vbulletin->GPC['userid'] AND $vbulletin->GPC['userid'] != $vbulletin->userinfo['userid'])
	{
		if (!($userinfo = fetch_userinfo($vbulletin->GPC['userid'])))
		{
			standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink']));
		}

		// are we a member of this user's blog?
		if (!is_member_of_blog($vbulletin->userinfo, $userinfo))
		{
			print_no_permission();
		}

		$userid = $userinfo['userid'];

		/* Blog posting check */
		if (
				!($userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpost'])
					OR
				!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']	)
		)
		{
			print_no_permission();
		}
	}
	else
	{
		$userinfo =& $vbulletin->userinfo;
		$userid = '';
		/* Blog posting check, no guests! */
		if (
				!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
					OR
				!($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpost'])
					OR
				!$vbulletin->userinfo['userid']
		)
		{
			print_no_permission();
		}
	}

	// falls down from preview post and has already been sent through htmlspecialchars() in build_new_post()
	$title = $blog['title'];
	$taglist = $blog['taglist'];

	require_once(DIR . '/includes/functions_editor.php');
	require_once(DIR . '/includes/functions_newpost.php');

	($hook = vBulletinHook::fetch_hook('blog_post_newentry_start')) ? eval($hook) : false;

	$draft_options = '';
	$blog_drafts = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "blog WHERE userid = $userinfo[userid] AND state = 'draft'");
	while ($blog_draft = $db->fetch_array($blog_drafts))
	{
		$blog_draft['date_string'] = vbdate($vbulletin->options['dateformat'], $blog_draft['dateline']);
		$blog_draft['time_string'] = vbdate($vbulletin->options['timeformat'], $blog_draft['dateline']);
		$radiochecked = (!isset($radiochecked) ? ' checked="checked"' : '');
		$templater = vB_Template::create('blog_entry_editor_draft');
			$templater->register('blog_draft', $blog_draft);
			$templater->register('radiochecked', $radiochecked);
			$templater->register('_blog_draft', $_blog_draft);
		$draft_options .= $templater->render();
	}
	$show['drafts'] = !empty($draft_options);

	if (defined('POSTPREVIEW'))
	{
		$postpreview =& $preview;
		$blog['message'] = htmlspecialchars_uni($blog['message']);
		construct_checkboxes($blog, array('allowcomments', 'allowpingback', 'moderatecomments', 'notify', 'private'));
		construct_publish_select($blog, $blog['dateline']);

		$notification = array($vbulletin->GPC['emailupdate'] => 'selected="selected"');

		if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowhtml'])
		{
			$htmlchecked = fetch_htmlchecked($vbulletin->GPC['htmlstate']);
			$show['htmloption'] = true;
		}
	}
	else
	{ // defaults in here if we're doing a quote etc
		construct_checkboxes(
			array(
				'allowcomments'    => $userinfo['blog_allowcomments'],
				'allowpingback'    => $userinfo['blog_allowpingback'],
				'moderatecomments' => $userinfo['blog_moderatecomments'],
				'parseurl'         => true,
			),
			array('allowcomments', 'allowpingback', 'moderatecomments'
		));
		construct_publish_select($blog);

		if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowhtml'])
		{
			if (!isset($htmlchecked))
			{
				$htmlchecked = array('on_nl2br' => 'selected="selected"');
			}
			$show['htmloption'] = true;
		}

		$notification = array($vbulletin->userinfo['blog_subscribeown'] => 'selected="selected"');

		//we will have post and thread info in the case of the "blog this post" option...
		//this is not a mistake.
		if ($postinfo)
		{
			// ### CHECK IF ALLOWED TO POST ###
			if ($threadinfo['isdeleted'] OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
			{
				eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
			}

			if (!$foruminfo['allowposting'] OR $foruminfo['link'] OR !$foruminfo['cancontainthreads'])
			{
				eval(standard_error(fetch_error('forumclosed')));
			}

			if (!$threadinfo['open'])
			{
				if (!can_moderate($threadinfo['forumid'], 'canopenclose'))
				{
					//I don't think that setting vbulletin->url actually does anything here.
					$vbulletin->url = fetch_seo_url('thread|js', $threadinfo);
					eval(standard_error(fetch_error('threadclosed')));
				}
			}

			$forumperms = fetch_permissions($foruminfo['forumid']);
			if (($vbulletin->userinfo['userid'] != $threadinfo['postuserid'] OR !$vbulletin->userinfo['userid']) AND (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canreplyothers'])))
			{
				print_no_permission();
			}
			if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']) OR (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canreplyown']) AND $vbulletin->userinfo['userid'] == $threadinfo['postuserid']))
			{
				print_no_permission();
			}

			// check if there is a forum password and if so, ensure the user has it set
			verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

			// *********************************************************************************
			// Tachy goes to coventry
			if (in_coventry($thread['postuserid']) AND !can_moderate($thread['forumid']))
			{
				eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
			}

			if (!$postinfo['visible'] AND !can_moderate($foruminfo['forumid'], 'canmoderateposts'))
			{
				eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
			}
			$title = unhtmlspecialchars($postinfo['title'] ? $postinfo['title'] : $threadinfo['title']);
			$title = preg_replace('#^(' . preg_quote($vbphrase['reply_prefix'], '#') . '\s*)+#i', '', $title);
			$title = htmlspecialchars_uni(vbchop($title, $vbulletin->options['titlemaxchars']));

			$originalposter = fetch_quote_username($postinfo['username'] . ";$postinfo[postid]");
			$postdate = vbdate($vbulletin->options['dateformat'], $postinfo['dateline']);
			$posttime = vbdate($vbulletin->options['timeformat'], $postinfo['dateline']);
			$pagetext = trim(htmlspecialchars_uni($postinfo['pagetext']));

			$templater = vB_Template::create('blog_blogpost_quote');
				$templater->register('originalposter', $originalposter);
				$templater->register('pagetext', $pagetext);
			$blog['message'] = $templater->render(true);
		}
	}

	// VBIV-9941
	if ($postinfo['attach']
		AND	($forumperms & $vbulletin->bf_ugp_forumpermissions['cangetattachment'])
		AND ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpostattach']))
	{
		// Copy attachments from forum post.
		$attachments = $db->query_read_slave("
			SELECT *
			FROM " . TABLE_PREFIX . "attachment
			WHERE contentid = $postinfo[postid]
			AND contenttypeid = " . vB_Types::instance()->getContentTypeID('vBForum_Post') . "
		");

		$poststarttime = TIMENOW;
		$posthash = md5($poststarttime . $userinfo['userid'] . $userinfo['salt']);

		while ($attachment = $db->fetch_array($attachments))
		{
			$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_ARRAY, 'attachment');
			$attachdata->set('userid', $userinfo['userid']);
			$attachdata->set('dateline', $poststarttime);
			$attachdata->set('contentid', 0);
			$attachdata->set('posthash', $posthash);
			$attachdata->set('state', $attachment['state']);
			$attachdata->set('contenttypeid', vB_Types::instance()->getContentTypeID('vBBlog_BlogEntry'));
			$attachdata->set('filedataid', $attachment['filedataid']);
			$attachdata->set('filename', $attachment['filename']);
			$attachdata->set('settings', $attachment['settings']);

			if ($newattachmentid = $attachdata->save())
			{ // Adjust inline attachment id.
				$blog['message'] = preg_replace('#\[ATTACH(?:(=right|=left|=config))?\]' . $attachment['attachmentid'] . '\[/ATTACH\]#i', '[ATTACH$1]' . $newattachmentid . '[/ATTACH]', $blog['message']);
			}

			unset($attachdata);
		}
	}

	if ($show['tag_option'])
	{
		$tags_remain = null;
		if ($vbulletin->options['vbblog_maxtag'])
		{
			$tags_remain = $vbulletin->options['vbblog_maxtag'];
		}
		if ($vbulletin->options['vbblog_maxtagstarter'] AND !can_moderate_blog('caneditentries'))
		{
			$tags_remain = ($tags_remain === null ? $vbulletin->options['vbblog_maxtagstarter'] : min($tags_remain, $vbulletin->options['vbblog_maxtagstarter']));
		}

		$show['tags_remain'] = ($tags_remain !== null);
		$tags_remain = vb_number_format($tags_remain);
		$tag_delimiters = addslashes_js($vbulletin->options['tagdelimiter']);
	}

	// VBIV-9941
	if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpostattach'])
	{
		$values = !$userid ? "" : "values[u]=$userid";
		require_once(DIR . '/packages/vbattach/attach.php');
		$attach = new vB_Attach_Display_Content($vbulletin, 'vBBlog_BlogEntry');
		$attachmentoption = $attach->fetch_edit_attachments($posthash, $poststarttime, $postattach, 0, $values, $editorid, $attachcount, explode(',', $userinfo['memberids']));
		$contenttypeid = $attach->fetch_contenttypeid();
	}
	else
	{
		$attachmentoption = '';
		$contenttypeid = 0;
	}

	$vbulletin->options['ignorequotechars'] = 0;
	require_once(DIR . '/includes/functions_file.php');
	$attachinfo = fetch_attachmentinfo($posthash, $poststarttime, $contenttypeid);

	$editorid = construct_edit_toolbar(
		$blog['message'],
		false,
		'blog_entry',
		$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowsmilies'],
		true,
		$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpostattach'] AND !empty($vbulletin->userinfo['attachmentextensions']),
		'fe',
		'',
		$attachinfo,
		'content',
		'vBBlog_BlogEntry',
		0,
		$userinfo['userid'],
		defined('POSTPREVIEW'),
		true,
		'titlefield'
	);

	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	$sidebar =& build_user_sidebar($userinfo, 0, 0, 'entry');

	// draw nav bar
	// navbar and output
	$navbits = array(
		fetch_seo_url('blog', $userinfo) => $userinfo['blog_title'],
		'' => $show['postgroupblog'] ? $vbphrase['post_to_this_blog'] : $vbphrase['post_to_your_blog']
	);

	require_once(DIR . '/includes/blog_functions_category.php');
	$localcategorybits = construct_category_checkbox($blog['categories_array'], $userinfo, 'local');
	$globalcategorybits = construct_category_checkbox($blog['categories_array'], $userinfo, 'global');
	$show['category'] = ($localcategorybits OR $globalcategorybits);
	$show['parseurl'] = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode']);
	$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));
	$show['additional_options'] = ($show['misc_options'] OR !empty($attachmentoption));
	$show['post_options'] = true;
	$show['datepicker'] = true;
	$show['draftpublish'] = true;
	$show['lightbox'] = ($vbulletin->options['lightboxenabled'] AND $vbulletin->options['usepopups']);
	$show['blogattach'] = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpostattach']) ? 1 : 0;

	$guestuser = array(
		'userid'      => 0,
		'usergroupid' => 0,
	);
	cache_permissions($guestuser, false);
	if (
		$guestuser['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']
			AND
		$guestuser['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']
			AND
		$vbulletin->userinfo['guest_canviewmyblog']
			AND
		is_facebookenabled()
	)
	{
		$fbpublishcheckbox = construct_fbpublishcheckbox();
	}

	($hook = vBulletinHook::fetch_hook('blog_post_newentry_complete')) ? eval($hook) : false;

	// complete
	$templater = vB_Template::create('blog_entry_editor');
		$templater->register('attachmentoption', $attachmentoption);
		$templater->register('bloginfo', $bloginfo);
		$templater->register('checked', $checked);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('draft_options', $draft_options);
		$templater->register('editorid', $editorid);
		$templater->register('globalcategorybits', $globalcategorybits);
		$templater->register('localcategorybits', $localcategorybits);
		$templater->register('messagearea', $messagearea);
		$templater->register('notification', $notification);
		$templater->register('posthash', $posthash);
		$templater->register('postpreview', $postpreview);
		$templater->register('poststarttime', $poststarttime);
		$templater->register('publish_selected', $publish_selected);
		$templater->register('reason', $reason);
		$templater->register('taglist', $taglist);
		$templater->register('tags_remain', $tags_remain);
		$templater->register('tag_delimiters', $tag_delimiters);
		$templater->register('title', $title);
		$templater->register('url', $url);
		$templater->register('userid', $userid);
		$templater->register('htmlchecked', $htmlchecked);
		$templater->register('fbpublishcheckbox', $fbpublishcheckbox);
	$content = $templater->render();
}

// ############################### start delete entry ###############################
if ($_POST['do'] == 'deleteblog')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deleteblog'      => TYPE_STR,
		'deletepost'      => TYPE_STR,
		'reason'          => TYPE_NOHTML,
		'keepattachments' => TYPE_BOOL,
		'blogid'          => TYPE_UINT,
		'blogtextid'      => TYPE_UINT,
	));

	if (!can_moderate_blog('candeleteentries'))
	{	// Keep attachments for non moderator deletes (blog owner)
		$vbulletin->GPC['keepattachments'] = true;
	}

	$bloginfo = verify_blog($blogid);

	if (!$vbulletin->userinfo['userid']) // Guests can not make entries
	{
		print_no_permission();
	}

	if (empty($vbulletin->GPC['deleteblog']) AND !empty($vbulletin->GPC['deletepost']))
	{
		$vbulletin->GPC['deleteblog'] = $vbulletin->GPC['deletepost'];
	}

	if ($vbulletin->GPC['deleteblog'] != '')
	{
		if ($vbulletin->GPC['blogtextid'] == $bloginfo['blogtextid'] OR empty($vbulletin->GPC['blogtextid']))
		{
			if ($vbulletin->GPC['deleteblog'] == 'remove' AND fetch_entry_perm('remove', $bloginfo))
			{
				$hard_delete = true;
			}
			else if ($vbulletin->GPC['deleteblog'] == 'soft' AND fetch_entry_perm('delete', $bloginfo))
			{
				$hard_delete = false;
			}
			else
			{
				print_no_permission();
			}

			$blogman =& datamanager_init('Blog', $vbulletin, ERRTYPE_ARRAY, 'blog');
			$blogman->set_existing($bloginfo);
			$blogman->set_info('hard_delete', $hard_delete);
			$blogman->set_info('keep_attachments', $vbulletin->GPC['keepattachments']);
			$blogman->set_info('reason', $vbulletin->GPC['reason']);
			$blogman->delete();
			unset($blogman);

			build_blog_user_counters($bloginfo['userid']);

			$url = unhtmlspecialchars($vbulletin->url);
			if (preg_match('/\?([^#]*)(#.*)?$/s', $url, $match))
			{
				parse_str($match[1], $parts);

				if (
					$parts['blogid'] == $bloginfo['blogid']
						OR
					$parts['b'] == $bloginfo['blogid']
						OR
					(
						$parts['bt']
							AND
						!$parts['do']
							AND
						!$parts['b']
							AND
						!$parts['blogid'])
					)
				{
					// we've deleted the entry that we came into this blog from
					// blank the redirect as it will be set below
					$vbulletin->url = '';
				}
			}
			if (!stristr($vbulletin->url, 'blog.php')) // no referring url?
			{
				$vbulletin->url = fetch_seo_url('blog', $bloginfo);
			}
			print_standard_redirect('redirect_blog_delete');  
		}
		else
		{ // just deleting a comment
			$blogtextinfo = fetch_blog_textinfo($vbulletin->GPC['blogtextid']);
			if ($blogtextinfo === false)
			{
				standard_error(fetch_error('invalidid', $vbphrase['comment'], $vbulletin->options['contactuslink']));
			}

			if (fetch_comment_perm('candeletecomments', $bloginfo, $blogtextinfo) OR fetch_comment_perm('canremovecomments', $bloginfo, $blogtextinfo))
			{
				if ($vbulletin->GPC['deleteblog'] == 'remove' AND can_moderate_blog('canremovecomments'))
				{
					$hard_delete = true;
				}
				else
				{
					$hard_delete = false;
				}

				$blogman =& datamanager_init('BlogText', $vbulletin, ERRTYPE_ARRAY, 'blog');
				$blogman->set_existing($blogtextinfo);
				$blogman->set_info('hard_delete', $hard_delete);
				$blogman->set_info('reason', $vbulletin->GPC['reason']);

				$blogman->delete();
				unset($blogman);

				if (!stristr($vbulletin->url, 'blog.php')) // no referring url?
				{
					$vbulletin->url = fetch_seo_url('entry', $bloginfo);
				}
				print_standard_redirect('redirect_blog_deletecomment');  
			}
			else
			{
				print_no_permission();
			}
		}
	}
	else
	{
		$vbulletin->url = fetch_seo_url('entry', $bloginfo);
		print_standard_redirect('redirect_blog_entry_nodelete');  
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'editblog')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blogid'	=> TYPE_UINT
	));

	$bloginfo = verify_blog($blogid);

	if (!$vbulletin->userinfo['userid']) // Guests can not make entries
	{
		print_no_permission();
	}

	if (!fetch_entry_perm('edit', $bloginfo))
	{
		print_no_permission();
	}

	require_once(DIR . '/includes/functions_editor.php');
	require_once(DIR . '/includes/functions_newpost.php');

	($hook = vBulletinHook::fetch_hook('blog_post_editentry_start')) ? eval($hook) : false;

	// Use our permission to attach or the person who owns the post? check what vB does in this situation
	if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpostattach'])
	{
		$values = "values[blogid]=$bloginfo[blogid]";
		require_once(DIR . '/packages/vbattach/attach.php');
		$attach = new vB_Attach_Display_Content($vbulletin, 'vBBlog_BlogEntry');
		$attachmentoption = $attach->fetch_edit_attachments($posthash, $poststarttime, $postattach, $bloginfo['blogid'], $values, $editorid, $attachcount, explode(',', $bloginfo['memberids']));
		$contenttypeid = $attach->fetch_contenttypeid();
	}
	else
	{
		$attachmentoption = '';
		$contenttypeid = 0;
	}

	if ($vbulletin->GPC['advanced'] OR !defined('POSTPREVIEW'))
	{
		$title = $bloginfo['title'];
		$reason = $blog['reason'];
		$blog['allowsmilie'] = $bloginfo['allowsmilie'];

		construct_checkboxes(
			array(
				'allowcomments'    => $bloginfo['allowcomments'],
				'allowpingback'    => $bloginfo['allowpingback'],
				'moderatecomments' => $bloginfo['moderatecomments'],
				'private'          => $bloginfo['private'],
				'draft'            => ($bloginfo['state'] == 'draft'),
				'disablesmilies'   => (!$bloginfo['allowsmilie']),
				'parseurl'         => 1,
			),
			array('allowcomments', 'allowpingback', 'moderatecomments', 'private')
		);
		construct_publish_select($bloginfo, $bloginfo['dateline']);

		if ($vbulletin->userinfo['userid'] == $bloginfo['userid'])
		{
			if ($bloginfo['entrysubscribed'])
			{
				$notification = array($bloginfo['emailupdate'] => 'selected="selected"');
			}
		}
		else if ($subscribed = $db->query_first("SELECT type AS emailupdate FROM " . TABLE_PREFIX . "blog_subscribeentry WHERE blogid = $bloginfo[blogid] AND userid = $bloginfo[userid]"))
		{
			$notification = array($subscribed['emailupdate'] => 'selected="selected"');
		}

		$cats = $db->query_read_slave("
			SELECT blogcategoryid
			FROM " . TABLE_PREFIX . "blog_categoryuser
			WHERE userid = $bloginfo[userid]
				AND blogid = $bloginfo[blogid]
		");
		while ($cat = $db->fetch_array($cats))
		{
			$blog['categories_array'][] = $cat['blogcategoryid'];
		}

		$blog['message'] = htmlspecialchars_uni($blog['message']);
	}

	if (defined('POSTPREVIEW'))
	{
		$postpreview =& $preview;
		$blog['message'] = htmlspecialchars_uni($blog['message']);
		$blog['allowsmilie'] = !($blog['disablesmilies']);
		// falls down from preview blog entry and has already been sent through htmlspecialchars()
		$title = $blog['title'];
		$reason = $blog['reason'];
		construct_checkboxes($blog, array('allowcomments', 'allowpingback', 'moderatecomments', 'private'));
		construct_publish_select($blog, $blog['dateline']);
		$notification = array($vbulletin->GPC['emailupdate'] => 'selected="selected"');
	}
	else if (!$vbulletin->GPC['advanced'])
	{ // defaults in here if we're doing a quote etc
		$blog['message'] = htmlspecialchars_uni($bloginfo['pagetext']);
		if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowhtml'])
		{
			$htmlchecked = fetch_htmlchecked($bloginfo['htmlstate']);
			$show['htmloption'] = true;
		}
	}

	require_once(DIR . '/includes/functions_file.php');
	$attachinfo = fetch_attachmentinfo($posthash, $poststarttime, $contenttypeid, array('blogid' => $bloginfo['blogid']));

	$vbulletin->options['ignorequotechars'] = 0;

	$editorid = construct_edit_toolbar(
		$blog['message'],
		false,
		'blog_entry',
		$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowsmilies'],
		$blog['allowsmilie'],
		$bloginfo['userid'] AND $vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_canpostattach'] AND !empty($vbulletin->userinfo['attachmentextensions']),
		'fe',
		'',
		$attachinfo,
		'content',
		'vBBlog_BlogEntry',
		$bloginfo['blogid'],
		0,
		defined('POSTPREVIEW'),
		true,
		'titlefield'
	);

	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	$sidebar =& build_user_sidebar($bloginfo, 0, 0, 'entry');

	// draw nav bar

	$navbits = array(
		fetch_seo_url('blog', $bloginfo)  => $bloginfo['blog_title'],
		fetch_seo_url('entry', $bloginfo) =>  $bloginfo['title'],
		'' => $vbphrase['edit_blog_entry'],
	);

	require_once(DIR . '/includes/blog_functions_category.php');
	$localcategorybits = construct_category_checkbox($blog['categories_array'], $bloginfo, 'local');
	$globalcategorybits = construct_category_checkbox($blog['categories_array'], $bloginfo, 'global');
	$show['category'] = ($localcategorybits OR $globalcategorybits);
	$show['parseurl'] = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode']);
	$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));
	$show['additional_options'] = ($show['misc_options'] OR !empty($attachmentoption));
	$show['edit'] = true;
	$show['physicaldeleteoption'] = (fetch_entry_perm('remove', $bloginfo) OR $bloginfo['state'] == 'draft' OR $bloginfo['pending']);
	$show['softdeleteoption'] = (fetch_entry_perm('delete', $bloginfo) AND $bloginfo['state'] != 'draft' AND !$bloginfo['pending']);
	$show['keepattachmentsoption'] = $attachcount ? true : false;
	$show['datepicker'] = true;
	$show['draftpublish'] = ($bloginfo['state'] == 'draft');
	$show['tag_option'] = false;
	$show['lightbox'] = ($vbulletin->options['lightboxenabled'] AND $vbulletin->options['usepopups']);

	$bloginfo['entrydate'] = vbdate($vbulletin->options['dateformat'], $bloginfo['dateline']);
	$bloginfo['entrytime'] = vbdate($vbulletin->options['timeformat'], $bloginfo['dateline']);
	$bloginfo['title_trimmed'] = fetch_trimmed_title($bloginfo['title']);

	$show['delete'] = ($show['softdeleteoption'] OR $show['physicaldeleteoption']);

	($hook = vBulletinHook::fetch_hook('blog_post_editentry_complete')) ? eval($hook) : false;

	$url =& $vbulletin->url;
	// complete
	$templater = vB_Template::create('blog_entry_editor');
		$templater->register('attachmentoption', $attachmentoption);
		$templater->register('bloginfo', $bloginfo);
		$templater->register('checked', $checked);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('draft_options', $draft_options);
		$templater->register('editorid', $editorid);
		$templater->register('globalcategorybits', $globalcategorybits);
		$templater->register('localcategorybits', $localcategorybits);
		$templater->register('messagearea', $messagearea);
		$templater->register('notification', $notification);
		$templater->register('posthash', $posthash);
		$templater->register('postpreview', $postpreview);
		$templater->register('poststarttime', $poststarttime);
		$templater->register('publish_selected', $publish_selected);
		$templater->register('reason', $reason);
		$templater->register('taglist', $taglist);
		$templater->register('tags_remain', $tags_remain);
		$templater->register('tag_delimiters', $tag_delimiters);
		$templater->register('title', $title);
		$templater->register('url', $url);
		$templater->register('userid', $userid);
		$templater->register('htmlchecked', $htmlchecked);
	$content = $templater->render();
}

if ($_REQUEST['do'] == 'editcomment' OR ($_POST['do'] == 'postcomment' AND $vbulletin->GPC['blogtextid']))
{
	$bloginfo = verify_blog($blogtextinfo['blogid'], 1, 'modifychild');

	if (!$blogtextinfo)
	{
		standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
	}

	if ($bloginfo['firstblogtextid'] == $blogtextinfo['blogtextid'] OR !fetch_comment_perm('caneditcomments', $bloginfo, $blogtextinfo))
	{
		standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
	}
}

// #######################################################################
if ($_POST['do'] == 'postcomment')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'blogid'           => TYPE_UINT,
		'title'            => TYPE_NOHTML,
		'message'          => TYPE_STR,
		'wysiwyg'          => TYPE_BOOL,
		'preview'          => TYPE_STR,
		'disablesmilies'   => TYPE_BOOL,
		'parseurl'         => TYPE_BOOL,
		'username'         => TYPE_STR,
		'fromquickcomment' => TYPE_BOOL,
		'ajax'             => TYPE_BOOL,
		'lastcomment'      => TYPE_UINT,
		'imagestamp'       => TYPE_STR,
		'imagehash'        => TYPE_STR,
		'loggedinuser'     => TYPE_UINT,
		'emailupdate'      => TYPE_STR,
		'reason'           => TYPE_NOHTML,
		'humanverify'      => TYPE_ARRAY,
		'advanced'         => TYPE_BOOL,
		'ajax'             => TYPE_BOOL,
		'linkblog'         => TYPE_BOOL,
	));

	$bloginfo = verify_blog($blogid);

	/* Checks if they can post comments to their blogs or other peoples blogs */
	if (!$blogtextid AND !($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_cancommentown']) AND $bloginfo['userid'] == $vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	if (!($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_cancommentothers']) AND $bloginfo['userid'] != $vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	/* Moderators can only edit comments if they are deleted or add comments to moderated posts */
	if (($bloginfo['state'] == 'deleted' AND (!can_moderate_blog('candeleteentries') OR !$blogtextid)) OR ($bloginfo['state'] == 'moderation' AND !can_moderate_blog('canmoderateentries')) OR $bloginfo['state'] == 'draft')
	{
		standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
	}

	if ((!$bloginfo['allowcomments'] AND $vbulletin->userinfo['userid'] != $bloginfo['userid'] AND !can_moderate_blog()) OR !$bloginfo['cancommentmyblog'])
	{
		print_no_permission();
	}

	($hook = vBulletinHook::fetch_hook('blog_post_updatecomment_start')) ? eval($hook) : false;

	// unwysiwygify the incoming data
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$vbulletin->GPC['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowhtml']);
	}

	if ($vbulletin->GPC['fromquickcomment'])
	{
		if ($vbulletin->userinfo['blog_subscribeothers'] AND !$bloginfo['entrysubscribed'])
		{
			$vbulletin->GPC['emailupdate'] = $vbulletin->userinfo['blog_subscribeothers'];
		}
		else if ($bloginfo['entrysubscribed'])
		{
			$vbulletin->GPC['emailupdate'] = $bloginfo['emailupdate'];
		}
		else
		{
			$vbulletin->GPC['emailupdate'] = 'none';
		}
	}

	if ($vbulletin->GPC['ajax'])
	{
		// posting via ajax so we need to handle those %u0000 entries
		$blog['message'] = convert_urlencoded_unicode($vbulletin->GPC['message']);
		$blog['title'] = unhtmlspecialchars($blogtextinfo['title']);
		$blog['parseurl'] = ($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowbbcode']);
		$blog['reason'] = fetch_censored_text(convert_urlencoded_unicode($vbulletin->GPC['reason']));
		if ($blogtextinfo)
		{
			$blog['disablesmilies'] = !($blogtextinfo['allowsmilie']);
		}
		else
		{
			$blog['disablesmilies'] = $vbulletin->GPC['disablesmilies'];
		}
	}
	else
	{
		$blog['message']        = $vbulletin->GPC['message'];
		$blog['title']          = $vbulletin->GPC['title'];
		$blog['reason']         = $vbulletin->GPC['reason'];
		if ($vbulletin->GPC['advanced'])
		{
			$blog['parseurl'] = false;
		}
		else
		{
			$blog['parseurl'] = ($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowbbcode'] AND $vbulletin->GPC['parseurl']);
		}
		$blog['disablesmilies'] = $vbulletin->GPC['disablesmilies'];
	}

	$blog['blogid']         = $vbulletin->GPC['blogid'];
	$blog['username']       = $vbulletin->GPC['username'];

	// parse URLs in message text
	if ($blog['parseurl'])
	{
		require_once(DIR . '/includes/functions_newpost.php');
		$blog['message'] = convert_url_to_bbcode($blog['message']);
	}


	$blogman =& datamanager_init('BlogText', $vbulletin, ERRTYPE_ARRAY, 'blog');

	if ($blogtextid)
	{
		$show['edit'] = true;
		$blogman->set_existing($blogtextinfo);
	}
	else
	{
		// if the blog owner is forcing a comment OR board has comment enforcement on and we are following that policy
		if (($bloginfo['moderatecomments'] OR $vbulletin->options['blog_commentmoderation'] OR !($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_followcommentmoderation'])) AND !can_moderate_blog('canmoderatecomments') AND $bloginfo['userid'] != $vbulletin->userinfo['userid'])
		{
			$blogman->set('state', 'moderation');
		}
		$blogman->set('userid', $vbulletin->userinfo['userid']);
		$blogman->set('bloguserid', $bloginfo['userid']);
		if ($vbulletin->userinfo['userid'] == 0)
		{
			$blogman->setr('username', $blog['username']);
		}
		else
		{
			$blogman->do_set('username', $vbulletin->userinfo['username']);
		}
	}

	$blogman->set_info('blog', $bloginfo);
	$blogman->set_info('preview', $vbulletin->GPC['preview']);
	$blogman->set_info('emailupdate', $vbulletin->GPC['emailupdate']);
	$blogman->set_info('akismet_key', $bloginfo['akismet_key']);
	$blogman->setr('title', $blog['title']);
	$blogman->setr('pagetext', $blog['message']);
	$blogman->setr('blogid', $blog['blogid']);
	$blogman->set('allowsmilie', !$blog['disablesmilies']);

	if (fetch_require_hvcheck('post'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verify =& vB_HumanVerify::fetch_library($vbulletin);
		if (!$verify->verify_token($vbulletin->GPC['humanverify']))
		{
			$blogman->errors[] = fetch_error($verify->fetch_error());
		}
	}

	$blogman->pre_save();

	if ($vbulletin->GPC['fromquickcomment'] AND $vbulletin->GPC['preview'])
	{
		$blogman->errors = array();
	}

	if (!empty($blogman->errors))
	{
		if ($vbulletin->GPC['ajax'])
		{
			require_once(DIR . '/includes/class_xml.php');
			$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
			$xml->add_group('errors');
			foreach ($blogman->errors AS $error)
			{
				$xml->add_tag('error', $error);
			}
			$xml->close_group();
			$xml->print_xml();
		}
		else
		{
			define('COMMENTPREVIEW', true);
			$preview = construct_errors($blogman->errors); // this will take the preview's place
			$previewpost = true;
			$_REQUEST['do'] = 'comment';
		}
	}
	else if ($vbulletin->GPC['preview'])
	{
		define('COMMENTPREVIEW', true);
		$preview = process_blog_preview($blog, 'comment');
		$previewpost = true;
		$_REQUEST['do'] = 'comment';
	}
	else if ($vbulletin->GPC['advanced'])
	{
		$_REQUEST['do'] = 'editcomment';
	}
	else
	{
		$blogcommentid = $blogman->save();
		if ($blogtextid)
		{
			clear_autosave_text('vBBlog_BlogComment', $blogtextid, 0, $vbulletin->userinfo['userid']);
			$blogcommentid =& $blogtextid;

			$update_edit_log = true;

			if (!($permissions['genericoptions'] & $vbulletin->bf_ugp_genericoptions['showeditedby']) AND ($blog['reason'] === '' OR $blog['reason'] == $blogtextinfo['edit_reason']))
			{
				$update_edit_log = false;
			}

			if ($update_edit_log)
			{
				if ($blogtextinfo['dateline'] < (TIMENOW - ($vbulletin->options['noeditedbytime'] * 60)) OR !empty($blog['reason']))
				{
					/*insert query*/
					$db->query_write("
						REPLACE INTO " . TABLE_PREFIX . "blog_editlog (blogtextid, userid, username, dateline, reason)
						VALUES ($blogtextid, " . $vbulletin->userinfo['userid'] . ", '" . $db->escape_string($vbulletin->userinfo['username']) . "', " . TIMENOW . ", '" . $db->escape_string($blog['reason']) . "')
					");
				}
			}
		}
		else
		{
			clear_autosave_text('vBBlog_BlogComment', 0, $bloginfo['blogid'], $vbulletin->userinfo['userid']);
		}

		if ($vbulletin->GPC['ajax'])
		{
			if (!$blogtextid)
			{
				$state = array('visible');

				// Owner/Admin/Super Mod of blog should see all states
				// Moderator, depending on their moderation permissions
				$showmoderation = false;
				if ($bloginfo['userid'] == $vbulletin->userinfo['userid'] OR can_moderate_blog('canmoderatecomments'))
				{
					$showmoderation = true;
					$state[] = 'moderation';
				}

				$deljoinsql = '';
				if ($bloginfo['userid'] == $vbulletin->userinfo['userid'] OR can_moderate_blog())
				{
					$deljoinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_deletionlog AS blog_deletionlog ON (blog_text.blogtextid = blog_deletionlog.primaryid AND blog_deletionlog.type = 'blogtextid')";
					$state[] = 'deleted';
				}


				$state_or = array(
					"blog_text.state IN ('" . implode("','", $state) . "')"
				);

				// Get the viewing user's moderated entries
				if ($vbulletin->userinfo['userid'] AND ($bloginfo['comments_moderation'] > 0 OR $blogman->fetch_field('state') == 'moderation') AND !can_moderate_blog('canmoderatecomments') AND $vbulletin->userinfo['userid'] != $bloginfo['userid'])
				{
					$state_or[] = "(blog_text.userid = " . $vbulletin->userinfo['userid'] . " AND blog_text.state = 'moderation')";
				}

				$wheresql = "
					blog_text.blogid = " . $vbulletin->GPC['blogid'] . "
					AND blog_text.blogtextid <> " . $bloginfo['firstblogtextid'] . "
					AND (" . implode(" OR ", $state_or) . ")
					AND " . (($lastviewed = $vbulletin->GPC['lastcomment']) ?
						"(blog_text.dateline > $lastviewed OR blog_text.blogtextid = $blogcommentid)" :
						"blog_text.blogtextid = $blogcommentid"
						) . "
				";
			}
			else
			{
				if ($bloginfo['userid'] == $vbulletin->userinfo['userid'] OR can_moderate_blog())
				{
					$deljoinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_deletionlog AS blog_deletionlog ON (blog_text.blogtextid = blog_deletionlog.primaryid AND blog_deletionlog.type = 'blogtextid')";
				}
				$wheresql = "blog_text.blogtextid = $blogtextid";
			}

			require_once(DIR . '/includes/class_xml.php');
			require_once(DIR . '/includes/class_bbcode.php');
			require_once(DIR . '/includes/class_blog_response.php');

			$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
			$xml->add_group('commentbits');

			$bbcode = new vB_BbCodeParser($vbulletin, fetch_tag_list());
			$factory = new vB_Blog_ResponseFactory($vbulletin, $bbcode, $bloginfo);
			$responsebits = '';

			($hook = vBulletinHook::fetch_hook('blog_post_updatecomment_complete')) ? eval($hook) : false;

			$comments = $db->query_read("
				SELECT blog_text.*, blog_text.ipaddress AS blogipaddress,
					blog_textparsed.pagetexthtml, blog_textparsed.hasimages,
					blog.title AS entrytitle,
					user.*, userfield.*,
					blog_editlog.userid AS edit_userid, blog_editlog.dateline AS edit_dateline, blog_editlog.reason AS edit_reason, blog_editlog.username AS edit_username
					" . ($deljoinsql ? ",blog_deletionlog.moddelete AS del_moddelete, blog_deletionlog.userid AS del_userid, blog_deletionlog.username AS del_username, blog_deletionlog.reason AS del_reason" : "") . "
					" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
				FROM " . TABLE_PREFIX . "blog_text AS blog_text
				LEFT JOIN " . TABLE_PREFIX . "blog AS blog ON (blog.blogid = blog_text.blogid)
				LEFT JOIN " . TABLE_PREFIX . "blog_textparsed AS blog_textparsed ON (blog_textparsed.blogtextid = blog_text.blogtextid AND blog_textparsed.styleid = " . intval(STYLEID) . " AND blog_textparsed.languageid = " . intval(LANGUAGEID) . ")
				LEFT JOIN " . TABLE_PREFIX . "user AS user ON (blog_text.userid = user.userid)
				LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (userfield.userid = blog_text.userid)
				LEFT JOIN " . TABLE_PREFIX . "blog_editlog AS blog_editlog ON (blog_editlog.blogtextid = blog_text.blogtextid)
				" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
				$deljoinsql
				WHERE
					$wheresql
				ORDER BY blog_text.dateline ASC
			");
			while ($comment = $db->fetch_array($comments))
			{
				$response_handler =& $factory->create($comment);
				$response_handler->userinfo = $bloginfo;

				if ($vbulletin->GPC['linkblog'])
				{
					$response_handler->linkblog = true;
				}
				$xml->add_tag('message', process_replacement_vars($response_handler->construct()), array(
					'blogtextid'        => $comment['blogtextid'],
					'visible'           => ($comment['state'] == 'visible') ? 1 : 0,
					'bgclass'           => $bgclass,
				));
			}

			$xml->add_tag('time', TIMENOW);
			$xml->close_group();
			$xml->print_xml();
		}
		else
		{
			($hook = vBulletinHook::fetch_hook('blog_post_updatecomment_complete')) ? eval($hook) : false;
			$vbulletin->url = fetch_seo_url('entry', $bloginfo, array('bt' => $blogcommentid));
			if ($blogman->fetch_field('state') == 'moderation')
			{
				print_standard_redirect(array('redirect_blog_commentthanks_moderate',$bloginfo['username']), true, true);  
			}
			else if ($blogtextid)
			{
				print_standard_redirect('redirect_blog_edit_commentthanks');  
			}
			else
			{
				// attempt to publish this new thread to user's Facebook feed
				if (is_facebookenabled())
				{
					$fblink = str_ireplace('&amp;', '&', $vbulletin->url);
					publishtofacebook_blogcomment($bloginfo['title'], $blog['message'], create_full_url($fblink));
				}

				print_standard_redirect('redirect_blog_commentthanks');  
			}
		}
	}

	unset($blogman);
}

//We'll need this in a bit. This is the info to mark as escalate to Article
$promote_link = '';
if ($vbulletin->products['vbcms'])
{

	if (! isset(vB::$vbulletin->userinfo['permissions']['cms']))
	{
		require_once DIR . '/packages/vbcms/permissions.php';
		vBCMS_Permissions::getUserPerms();
	}

	if (! isset($vbulletin->userinfo['cms_new_articleid']))
	{
		$vbulletin->userinfo['cms_new_articleid'] = false;
		if (count(vB::$vbulletin->userinfo['permissions']['cms']['canpublish']))
		{
			$record = $vbulletin->db->query_first("
				SELECT nodeid
				FROM " . TABLE_PREFIX . "cms_node
				WHERE parentnode = " . vB::$vbulletin->userinfo['permissions']['cms']['canpublish'][0]
			);

			if (!$record)
			{
				$record = $vbulletin->db->query_first("
					SELECT nodeid
					FROM " . TABLE_PREFIX . "cms_node
					WHERE parentnode IN (" . implode(', ', vB::$vbulletin->userinfo['permissions']['cms']['canpublish']) . ")
				");
			}

			if (count($record))
			{
				$vbulletin->userinfo['cms_new_articleid'] = $record['nodeid'];
			}
		}
	}

	if ($vbulletin->userinfo['cms_new_articleid'])
	{
		$promote_link = vB_Route::create('vBCms_Route_Content', $vbulletin->userinfo['cms_new_articleid'] . '/addcontent') .
			'&contenttypeid=' .  vB_Types::instance()->getContentTypeID('vBCms_Article');
	}
}
else
{
	$vbulletin->userinfo['cms_new_articleid'] = false;
}



// #######################################################################
if ($_REQUEST['do'] == 'comment')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blogid'		=> TYPE_UINT,
	));

	$bloginfo = verify_blog($blogid);

	/* Checks if they can post comments to their blogs or other peoples blogs */
	// Don't check this permission if we are editing
	if (!($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_cancommentown']) AND $bloginfo['userid'] == $vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	if (!($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_cancommentothers']) AND $bloginfo['userid'] != $vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	if (($bloginfo['state'] == 'moderation' AND !can_moderate_blog('canmoderateentries')) OR $bloginfo['state'] == 'deleted' OR $bloginfo['state'] == 'draft' OR $bloginfo['pending'] == 1)
	{
		standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
	}

	if ((!$bloginfo['allowcomments'] AND $vbulletin->userinfo['userid'] != $bloginfo['userid'] AND !can_moderate_blog()) OR !$bloginfo['cancommentmyblog'])
	{
		print_no_permission();
	}

	require_once(DIR . '/includes/functions_editor.php');

	// get attachment options
	require_once(DIR . '/includes/functions_file.php');
	$inimaxattach = fetch_max_upload_size();
	$maxattachsize = vb_number_format($inimaxattach, 1, true);
	$attachcount = 0;
	$attachment_js = '';

	$attachmentoption = '';

	if (defined('COMMENTPREVIEW'))
	{
		$postpreview =& $preview;
		$blog['message'] = htmlspecialchars_uni($blog['message']);
		$title = $blog['title'];
		$reason = $blog['reason'];
		construct_checkboxes($blog);
		$notification = array($vbulletin->GPC['emailupdate'] => 'selected="selected"');
		$previewpost = true;
	}
	else
	{ // defaults in here if we're doing a quote etc
		if ($bloginfo['issubscribed'])
		{
			$notification = array($bloginfo['emailupdate'] => 'selected="selected"');
		}
		else
		{
			$notification = array($vbulletin->userinfo['blog_subscribeothers'] => 'selected="selected"');
		}

		// Handle Quote
		if ($blogtextinfo)
		{
			$title = unhtmlspecialchars($blogtextinfo['title']);
			$title = preg_replace('#^(' . preg_quote($vbphrase['reply_prefix'], '#') . '\s*)+#i', '', $title);
			$title = htmlspecialchars_uni(vbchop($title, $vbulletin->options['titlemaxchars']));

			require_once(DIR . '/includes/functions_newpost.php');
			$originalposter = fetch_quote_username($blogtextinfo['username'] . ";bt$blogtextinfo[blogtextid]");
			$pagetext = trim(strip_quotes(htmlspecialchars_uni($blogtextinfo['pagetext'])));

			$templater = vB_Template::create('blog_blogpost_quote');
				$templater->register('originalposter', $originalposter);
				$templater->register('pagetext', $pagetext);
			$blog['message'] = $templater->render(true);
		}
		unset($blogtextinfo);
	}

	($hook = vBulletinHook::fetch_hook('blog_post_comment_start')) ? eval($hook) : false;

	$bloginfo['title_trimmed'] = fetch_trimmed_title($bloginfo['title']);

	$editorid = construct_edit_toolbar(
		$blog['message'],
		false,
		'blog_comment',
		$vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowsmilies'],
		true,
		false,
		'fe',
		'',
		array(),
		'content',
		'vBBlog_BlogComment',
		0,
		$bloginfo['blogid'],
		defined('COMMENTPREVIEW'),
		true,
		'title'
	);

	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	// image verification
	$human_verify = '';
	if (fetch_require_hvcheck('post'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verification =& vB_HumanVerify::fetch_library($vbulletin);
		$human_verify = $verification->output_token();
	}

	$sidebar =& build_user_sidebar($bloginfo, 0, 0, 'comment');

	// draw nav bar
	$navbits = array(
		fetch_seo_url('blog', $bloginfo)  => $bloginfo['blog_title'],
		fetch_seo_url('entry', $bloginfo) =>  $bloginfo['title'],
		'' => $vbphrase['post_a_comment'],
	);

	// auto-parse URL
	if (!isset($checked['parseurl']))
	{
		$checked['parseurl'] = 'checked="checked"';
	}

	$show['parseurl'] = ($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowbbcode']);
	$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));
	$show['additional_options'] = ($show['misc_options'] OR !empty($attachmentoption));
	
	$guestuser = array(
		'userid'      => 0,
		'usergroupid' => 0,
	);
	cache_permissions($guestuser, false);
	if (
		$guestuser['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']
			AND
		$guestuser['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']
			AND
		$bloginfo['state'] == 'visible'
			AND
		$bloginfo['guest_canviewmyblog']
			AND
		!$bloginfo['pending']
			AND
		is_facebookenabled()
	)	
	{
		$fbpublishcheckbox = construct_fbpublishcheckbox();
	}

	($hook = vBulletinHook::fetch_hook('blog_post_comment_complete')) ? eval($hook) : false;

	// complete
	$templater = vB_Template::create('blog_comment_editor');
		$templater->register('attachmentoption', $attachmentoption);
		$templater->register('bloginfo', $bloginfo);
		$templater->register('blogtextinfo', $blogtextinfo);
		$templater->register('checked', $checked);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('editorid', $editorid);
		$templater->register('human_verify', $human_verify);
		$templater->register('imagereg', $imagereg);
		$templater->register('messagearea', $messagearea);
		$templater->register('notification', $notification);
		$templater->register('posthash', $posthash);
		$templater->register('postpreview', $postpreview);
		$templater->register('reason', $reason);
		$templater->register('title', $title);
		$templater->register('url', $url);
		$templater->register('usernamecode', $usernamecode);
		$templater->register('vbulletin', $vbulletin);
		$templater->register('fbpublishcheckbox', $fbpublishcheckbox);
	$content = $templater->render();
}

// #######################################################################
if ($_REQUEST['do'] == 'editcomment')
{
	require_once(DIR . '/includes/functions_editor.php');

	// get attachment options
	require_once(DIR . '/includes/functions_file.php');
	$inimaxattach = fetch_max_upload_size();
	$maxattachsize = vb_number_format($inimaxattach, 1, true);
	$attachcount = 0;
	$attachment_js = '';

	$attachmentoption = '';

	$title = $blogtextinfo['title'];
	if (!empty($blog['message']))
	{
		$blog['message'] = htmlspecialchars_uni($blog['message']);
	}
	else
	{
		$blog['message'] = htmlspecialchars_uni($blogtextinfo['pagetext']);
	}
	if ($previewpost OR $vbulletin->GPC['advanced'])
	{
		$reason = $blog['reason'];
	}
	else if ($vbulletin->userinfo['userid'] == $blogtextinfo['edit_userid'])
	{
		// Only carry the reason over if the editing user owns the previous edit
		$reason = $blogtextinfo['edit_reason'];
	}

	if ($vbulletin->userinfo['userid'] == $blogtextinfo['userid'])
	{
		if ($bloginfo['issubscribed'])
		{
			$notification = array($bloginfo['emailupdate'] => 'selected="selected"');
		}
	}
	else if ($subscribed = $db->query_first("SELECT type AS emailupdate FROM " . TABLE_PREFIX . "blog_subscribeentry WHERE blogid = $bloginfo[blogid] AND userid = $blogtextinfo[userid]"))
	{
		$notification = array($subscribed['emailupdate'] => 'selected="selected"');
	}

	($hook = vBulletinHook::fetch_hook('blog_post_editcomment_start')) ? eval($hook) : false;

	$bloginfo['title_trimmed'] = fetch_trimmed_title($bloginfo['title']);

	if ($previewpost)
	{
		$checked['parseurl'] = ($blog['parseurl']) ? 'checked="checked"' : '';
		$allowsmilie = (!$blog['disablesmilies']);
	}
	else
	{
		if (!isset($checked['parseurl']))
		{
			$checked['parseurl'] = 'checked="checked"';
		}
		$allowsmilie = $blogtextinfo['allowsmilie'];
	}
	$checked['disablesmilies'] = (!$allowsmilie) ? 'checked="checked"' : '';

	$editorid = construct_edit_toolbar(
		$blog['message'],
		false,
		'blog_comment',
		$vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowsmilies'],
		$allowsmilie,
		false,
		'fe',
		'',
		array(),
		'content',
		'vBBlog_BlogComment',
		$blogtextinfo['blogtextid'],
		0,
		$previewpost,
		true,
		'title'
	);

	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	// draw nav bar
	$navbits = array(
		fetch_seo_url('blog', $bloginfo)  => $bloginfo['blog_title'],
		fetch_seo_url('entry', $bloginfo) =>  $bloginfo['title'],
		'' => $vbphrase['edit_comment'],
	);

	$show['parseurl'] = ($vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_allowbbcode']);
	$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));
	$show['additional_options'] = ($show['misc_options'] OR !empty($attachmentoption));
	$show['edit'] = true;
	$show['delete'] = (fetch_comment_perm('candeletecomments', $bloginfo, $blogtextinfo) OR fetch_comment_perm('canremovecomments', $bloginfo, $blogtextinfo));
	$show['physicaldeleteoption'] = can_moderate_blog('canremovecomments');

	$sidebar =& build_user_sidebar($bloginfo, 0, 0, 'comment');

	($hook = vBulletinHook::fetch_hook('blog_post_editcomment_complete')) ? eval($hook) : false;

	$url =& $vbulletin->url;
	// complete
	$templater = vB_Template::create('blog_comment_editor');
		$templater->register('attachmentoption', $attachmentoption);
		$templater->register('bloginfo', $bloginfo);
		$templater->register('blogtextinfo', $blogtextinfo);
		$templater->register('checked', $checked);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('editorid', $editorid);
		$templater->register('human_verify', $human_verify);
		$templater->register('imagereg', $imagereg);
		$templater->register('messagearea', $messagearea);
		$templater->register('notification', $notification);
		$templater->register('posthash', $posthash);
		$templater->register('postpreview', $postpreview);
		$templater->register('reason', $reason);
		$templater->register('title', $title);
		$templater->register('url', $url);
		$templater->register('usernamecode', $usernamecode);
		$templater->register('vbulletin', $vbulletin);
	$content = $templater->render();
}

// #######################################################################
if ($_POST['do'] == 'updatetrackback')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'blogtrackbackid'	=> TYPE_UINT,
		'title'           => TYPE_NOHTML,
		'snippet'         => TYPE_NOHTML
	));

	if (!($trackbackinfo = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "blog_trackback WHERE blogtrackbackid = " . $vbulletin->GPC['blogtrackbackid'])))
	{
		standard_error(fetch_error('invalidid', $vbphrase['trackback'], $vbulletin->options['contactuslink']));
	}

	$bloginfo = verify_blog($trackbackinfo['blogid']);

	if ($trackbackinfo['state'] == 'moderation' AND !can_moderate_blog('canmoderatecomments') AND ($vbulletin->userinfo['userid'] != $bloginfo['userid'] OR !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments'])))
	{
		standard_error(fetch_error('invalidid', $vbphrase['trackback'], $vbulletin->options['contactuslink']));
	}

	if (($bloginfo['state'] == 'deleted' AND !can_moderate_blog('candeleteentries')) OR ($bloginfo['state'] == 'moderation' AND !can_moderate_blog('canmoderateentries')))
	{
		print_no_permission();
	}

	$dataman =& datamanager_init('Blog_Trackback', $vbulletin, ERRTYPE_ARRAY);
	$dataman->set_existing($trackbackinfo);
	$dataman->set_info('skip_build_blog_entry_counters', true);
	$dataman->set('title', $vbulletin->GPC['title']);
	$dataman->set('snippet', $vbulletin->GPC['snippet']);

	$dataman->pre_save();

	// check for errors
	if (!empty($dataman->errors))
	{
		$_REQUEST['do'] = 'edittrackback';

		$errorlist = '';
		foreach ($dataman->errors AS $index => $error)
		{
			$errorlist .= "<li>$error</li>";
		}

		$title = htmlspecialchars_uni($vbulletin->GPC['title']);
		$snippet = htmlspecialchars_uni($vbulletin->GPC['snippet']);

		$show['errors'] = true;
	}
	else
	{
		$show['errors'] = false;

		$dataman->save();

		// if this is a mod edit, then log it
		if ($vbulletin->userinfo['userid'] != $bloginfo['userid'] AND can_moderate('caneditcomments'))
		{
			require_once(DIR . '/includes/blog_functions_log_error.php');
			blog_moderator_action($trackbackinfo, 'trackback_x_edited', array($trackbackinfo['title']));
		}

		print_standard_redirect('redirect_blog_edittrackback');  
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'edittrackback')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blogtrackbackid'	=> TYPE_UINT
	));

	if (!($trackbackinfo = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "blog_trackback WHERE blogtrackbackid = " . $vbulletin->GPC['blogtrackbackid'])))
	{
		standard_error(fetch_error('invalidid', $vbphrase['trackback'], $vbulletin->options['contactuslink']));
	}

	$bloginfo = verify_blog($trackbackinfo['blogid']);

	if ($trackbackinfo['state'] == 'moderation' AND !can_moderate_blog('canmoderatecomments') AND ($vbulletin->userinfo['userid'] != $bloginfo['userid'] OR !($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canmanageblogcomments'])))
	{
		standard_error(fetch_error('invalidid', $vbphrase['trackback'], $vbulletin->options['contactuslink']));
	}

	if (($bloginfo['state'] == 'deleted' AND !can_moderate_blog('candeleteentries')) OR ($bloginfo['state'] == 'moderation' AND !can_moderate_blog('canmoderateentries')))
	{
		print_no_permission();
	}

	if ($show['errors'])
	{
		$trackbackinfo['title'] = $title;
		$trackbackinfo['snippet'] = $snippet;
	}

	$sidebar =& build_user_sidebar($bloginfo);

	// draw nav bar
	$navbits = array(
		fetch_seo_url('blog', $bloginfo)  => $bloginfo['blog_title'],
		fetch_seo_url('entry', $bloginfo) =>  $bloginfo['title'],
		'' => $vbphrase['edit_trackback'],
	);

	($hook = vBulletinHook::fetch_hook('blog_post_edittrackback_complete')) ? eval($hook) : false;

	// complete
	$url = $vbulletin->url;
	$templater = vB_Template::create('blog_edit_trackback');
		$templater->register('bloginfo', $bloginfo);
		$templater->register('errorlist', $errorlist);
		$templater->register('trackbackinfo', $trackbackinfo);
		$templater->register('url', $url);
	$content = $templater->render();
}

// build navbar
if (empty($navbits))
{
	$navbits[] = $vbphrase['blogs'];
}
else
{
	$navbits = array_merge(array(fetch_seo_url('bloghome', array()) => $vbphrase['blogs']), $navbits);
}
$navbits = construct_navbits($navbits);

($hook = vBulletinHook::fetch_hook('blog_post_complete')) ? eval($hook) : false;

$navbar = render_navbar_template($navbits);
$headinclude .= vB_Template::create('blog_css')->render();
$templater = vB_Template::create('BLOG');
	$templater->register_page_templates();
	$templater->register('abouturl', $abouturl);
	$templater->register('blogheader', $blogheader);
	$templater->register('bloginfo', $bloginfo);
	$templater->register('blogrssinfo', $blogrssinfo);
	$templater->register('bloguserid', $bloguserid);
	$templater->register('content', $content);
	$templater->register('navbar', $navbar);
	$templater->register('onload', $onload);
	$templater->register('pagetitle', $pagetitle);
	$templater->register('pingbackurl', $pingbackurl);
	$templater->register('sidebar', $sidebar);
	$templater->register('trackbackurl', $trackbackurl);
	$templater->register('usercss_profile_preview', $usercss_profile_preview);
print_output($templater->render());

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # SVN: $Revision: 62098 $
|| ####################################################################
\*======================================================================*/
?>
