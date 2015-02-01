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
define('THIS_SCRIPT', 'ajax');
define('CSRF_PROTECTION', true);
define('LOCATION_BYPASS', 1);
define('NOPMPOPUP', 1);
define('NONOTICES', 1);
define('VB_ENTRY', 'ajax.php');
define('VB_ENTRY_TIME', microtime(true));

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('posting', 'search', 'socialgroups');
switch ($_POST['do'])
{
	case 'fetchuserfield':
	case 'saveuserfield':
		$phrasegroups[] = 'cprofilefield';
		$phrasegroups[] = 'user';
		break;
	case 'verifyusername':
		$phrasegroups[] = 'register';
		break;
	case 'list':
		$phrasegroups[] = 'user';
		break;
	case 'getconfirmclosebox':
	case 'getprofiledialog':
		$phrasegroups[] = 'profilefield';
		break;
	case 'checkurl':
		$phrasegroups = array('vbcms', 'global', 'cpcms', 'cphome');
		break;
}

// get special data templates from the datastore
$specialtemplates = array('bbcodecache');

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'fetchuserfield' => array(
		'memberinfo_customfield_edit',
		'userfield_checkbox_option',
		'userfield_optional_input',
		'userfield_radio',
		'userfield_radio_option',
		'userfield_select',
		'userfield_select_option',
		'userfield_select_multiple',
		'userfield_textarea',
		'userfield_textbox',
	),
	'quickedit' => array(
		'editor_clientscript',
		'editor_jsoptions_font',
		'editor_jsoptions_size',
		'editor_smilie',
		'editor_smiliebox',
		'newpost_disablesmiliesoption',
		'postbit_quickedit',
	),
	'overlay' => array(
		'overlay',
	),
	'checkurl' => array(
		'pagenav',
		'pagenav_curpage',
		'pagenav_pagelink',
		'pagenav_pagelinkrel',
	),
);

if (!VB_API)
{
	$_POST['ajax'] = 1;
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once DIR . '/vb/search/searchtools.php';
($hook = vBulletinHook::fetch_hook('ajax_start')) ? eval($hook) : false;

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################


// #############################################################################
// user name search

if ($_POST['do'] == 'usersearch')
{
	$vbulletin->input->clean_array_gpc('p', array('fragment' => TYPE_STR));

	$vbulletin->GPC['fragment'] = convert_urlencoded_unicode($vbulletin->GPC['fragment']);

	if ($vbulletin->GPC['fragment'] != '' AND strlen($vbulletin->GPC['fragment']) >= 3)
	{
		$fragment = htmlspecialchars_uni($vbulletin->GPC['fragment']);
	}
	else
	{
		$fragment = '';
	}

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('users');

	if ($fragment != '')
	{
		$users = $db->query_read_slave("
			SELECT user.userid, user.username FROM " . TABLE_PREFIX . "user
			AS user WHERE username LIKE('" . $db->escape_string_like($fragment) . "%')
			ORDER BY username
			LIMIT 15
		");
		while ($user = $db->fetch_array($users))
		{
			$xml->add_tag('user', $user['username'], array('userid' => $user['userid']));
		}
	}

	$xml->close_group();
	$xml->print_xml();
}

// #############################################################################
// tag search

if ($_POST['do'] == 'tagsearch')
{
	$vbulletin->input->clean_array_gpc('p', array('fragment' => TYPE_STR));

	$vbulletin->GPC['fragment'] = convert_urlencoded_unicode($vbulletin->GPC['fragment']);

	if ($vbulletin->GPC['fragment'] != '' AND strlen($vbulletin->GPC['fragment']) >= 3)
	{
		$fragment = htmlspecialchars_uni($vbulletin->GPC['fragment']);
	}
	else
	{
		$fragment = '';
	}

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('tags');

	if ($fragment != '')
	{
		/*
			This is a little complicated.
			What we want is to return 15 tags such that they match the prefix and
			we do not include a synonyms if its canonical version is also present (and only
			one of a group of synonyms if the canonical verison is present).

			This could be costly to compute if we match a lot of data so we'll fudge it.
			We'll check the first 20 tags that match the prefix and return the first
			15 that match.  We can incorrectly return less than 15 items if there are
			more than 20 that match the prefix and we throw out more than five.  This
			should be exceedingly rare and the consequences are minor.
		*/

		$set = $db->query_read_slave("
			SELECT tag.tagid, tag.tagtext, tag.canonicaltagid
			FROM " . TABLE_PREFIX . "tag AS tag
			WHERE tagtext LIKE '" . $db->escape_string_like($fragment) . "%'
			ORDER BY tagtext
			LIMIT 20
		");

		$tags = array();
		$canonicalmap = array();
		while ($tag = $db->fetch_array($set))
		{
			$tags[] = $tag;
			if ($tag['canonicaltagid'] == 0)
			{
				$canonicalmap[$tag['tagid']] = 1;
			}
		}

		$added = 0;
		foreach ($tags as $tag)
		{
			if ($tag['canonicaltagid'] == 0 OR !array_key_exists($tag['canonicaltagid'], $canonicalmap))
			{
				//prevent further synonyms for a given tag from being added.
				//canonical tags will add id 0 to the map, but this doesn't cause problems
				$canonicalmap[$tag['canonicaltagid']] = 1;
				$xml->add_tag('tag', $tag['tagtext']);
				$added++;
				if ($added >= 15)
				{
					break;
				}
			}
		}
	}

	$xml->close_group();
	$xml->print_xml();
}
if ($_POST['do'] == 'socialgroupsearch')
{
	$vbulletin->input->clean_array_gpc('p', array('fragment' => TYPE_STR));

	$vbulletin->GPC['fragment'] = convert_urlencoded_unicode($vbulletin->GPC['fragment']);

	if ($vbulletin->GPC['fragment'] != '' AND strlen($vbulletin->GPC['fragment']) >= 3)
	{
		$fragment = htmlspecialchars_uni($vbulletin->GPC['fragment']);
	}
	else
	{
		$fragment = '';
	}

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('socialgroups');

	if ($fragment != '')
	{
		$groups = $db->query_read_slave("
			SELECT socialgroup.groupid, socialgroup.name FROM " . TABLE_PREFIX . "socialgroup
			AS socialgroup WHERE name LIKE('" . $db->escape_string_like($fragment) . "%')
			ORDER BY name
			LIMIT 15");
		while ($group = $db->fetch_array($groups))
		{
			$xml->add_tag('socialgroup', $group['name'], array('socialgroupid' => $group['groupid']));
		}
	}

	$xml->close_group();
	$xml->print_xml();
}
// #############################################################################
// update thread title

if ($_POST['do'] == 'updatethreadtitle')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'threadid' => TYPE_UINT,
		'title'    => TYPE_STR
	));

	// allow edit if...
	if (
		$threadinfo
		AND
		can_moderate($threadinfo['forumid'], 'caneditthreads') // ...user is moderator
		OR
		(
			$threadinfo['open']
			AND
			$threadinfo['postuserid'] == $vbulletin->userinfo['userid'] // ...user is thread first poster
			AND
			($forumperms = fetch_permissions($threadinfo['forumid'])) AND ($forumperms & $vbulletin->bf_ugp_forumpermissions['caneditpost']) // ...user has edit own posts permissions
			AND
			($threadinfo['dateline'] + $vbulletin->options['editthreadtitlelimit'] * 60) > TIMENOW // ...thread was posted within editthreadtimelimit
		)
	)
	{
		$threadtitle = convert_urlencoded_unicode($vbulletin->GPC['title']);
		$threaddata =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$threaddata->set_existing($threadinfo);
		if (!can_moderate($threadinfo['forumid']))
		{
			$threaddata->set_info('skip_moderator_log', true);
		}

		$threaddata->set('title', $threadtitle);

		if ($vbulletin->options['similarthreadsearch'])
		{
			require_once(DIR . '/vb/search/core.php');
			$searchcontroller = vB_Search_Core::get_instance()->get_search_controller();
			$similarthreads = $searchcontroller->get_similar_threads($threadtitle, $threadinfo['threadid']);
			$threaddata->set('similar', implode(',', $similarthreads));
		}

		$getfirstpost = $db->query_first("
			SELECT post.*
			FROM " . TABLE_PREFIX . "post AS post
			WHERE threadid = $threadinfo[threadid]
			ORDER BY dateline
			LIMIT 1
		");

		if ($threaddata->save())
		{
			$getfirstpost['threadtitle'] = $threaddata->fetch_field('title');
			$getfirstpost['title'] =& $getfirstpost['threadtitle'];

			cache_ordered_forums(1);

			if ($vbulletin->forumcache["$threadinfo[forumid]"]['lastthreadid'] == $threadinfo['threadid'])
			{
				require_once(DIR . '/includes/functions_databuild.php');
				build_forum_counters($threadinfo['forumid']);
			}

			// we do not appear to log thread title updates
			$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
			$xml->add_group('foo');
				$xml->add_tag('linkhtml', $threaddata->thread['title']);
				$threadinfo['title'] = $threaddata->fetch_field('title');
				$xml->add_tag('linkhref', fetch_seo_url('thread', $threadinfo));
			$xml->close_group('foo');
			$xml->print_xml();
			exit;
		}
	}

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('foo');
	$xml->add_tag('linkhtml', $threadinfo['title']);
	$xml->add_tag('linkhref', fetch_seo_url('thread', $threadinfo));
	$xml->close_group('foo');
	$xml->print_xml();
}

// #############################################################################
// toggle thread open/close

if ($_POST['do'] == 'updatethreadopen')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'threadid' => TYPE_UINT,
		'open'     => TYPE_BOOL,
	));
	if ($threadinfo['open'] == 10)
	{	// thread redirect
		exit;
	}
	//load the existing open status to return if no permission
	$open = $threadinfo['open'];
	// allow edit if...
	if (
		can_moderate($threadinfo['forumid'], 'canopenclose') // user is moderator
		OR
		(
			$threadinfo['postuserid'] == $vbulletin->userinfo['userid'] // user is thread first poster
			AND
			($forumperms = fetch_permissions($threadinfo['forumid'])) AND ($forumperms & $vbulletin->bf_ugp_forumpermissions['canopenclose']) // user has permission to open / close own threads
		)
	)
	{
		$open = $vbulletin->GPC['open'];

		$threaddata =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$threaddata->set_existing($threadinfo);
		$threaddata->set('open', $open); // note: mod logging will occur automatically
		if (!$threaddata->save())
		{
			// didn't change anything
			$open = !$open;
		}
	}

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_tag('status', $open ? 'open' : 'closed');
	$xml->print_xml();
}

// #############################################################################
// return a post in an editor

if ($_POST['do'] == 'quickedit')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'postid'      => TYPE_UINT,
		'editorid'    => TYPE_STR,
		'return_node' => TYPE_UINT,
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

		if (!$postinfo['postid'])
		{
			$xml->add_tag('error', fetch_error('invalidid'));
			$xml->print_xml();
		}

		if ((!$postinfo['visible'] OR $postinfo['isdeleted']) AND !can_moderate($threadinfo['forumid']))
		{
			$xml->add_tag('error', fetch_error('nopermission'));
			$xml->print_xml();
		}

		if ((!$threadinfo['visible'] OR $threadinfo['isdeleted']) AND !can_moderate($threadinfo['forumid']))
		{
			$xml->add_tag('error', fetch_error('nopermission'));
			$xml->print_xml();
		}

		$forumperms = fetch_permissions($threadinfo['forumid']);
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
		{
			$xml->add_tag('error', fetch_error('nopermission'));
			$xml->print_xml();
		}
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($threadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR $vbulletin->userinfo['userid'] == 0))
		{
			$xml->add_tag('error', fetch_error('nopermission'));
			$xml->print_xml();
		}

		// check if there is a forum password and if so, ensure the user has it set
		verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

		// Tachy goes to coventry
		if (in_coventry($threadinfo['postuserid']) AND !can_moderate($threadinfo['forumid']))
		{
			// do not show post if part of a thread from a user in Coventry and bbuser is not mod
			$xml->add_tag('error', fetch_error('nopermission'));
			$xml->print_xml();
		}
		if (in_coventry($postinfo['userid']) AND !can_moderate($threadinfo['forumid']))
		{
			// do not show post if posted by a user in Coventry and bbuser is not mod
			$xml->add_tag('error', fetch_error('nopermission'));
			$xml->print_xml();
		}

		if (!can_moderate($threadinfo['forumid'], 'caneditposts'))
		{ // check for moderator
			if (!$threadinfo['open'])
			{
				$xml->add_tag('error', fetch_error('threadclosed'));
				$xml->print_xml();
			}
			if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['caneditpost']))
			{
				$xml->add_tag('error', fetch_error('nopermission_loggedout'));
				$xml->print_xml();
			}
			else
			{
				if ($vbulletin->userinfo['userid'] != $postinfo['userid'])
				{
					// check user owns this post
					$xml->add_tag('error', fetch_error('nopermission_loggedout'));
					$xml->print_xml();
				}
				else
				{
					// check for time limits
					if ($postinfo['dateline'] < (TIMENOW - ($vbulletin->options['edittimelimit'] * 60)) AND $vbulletin->options['edittimelimit'] != 0)
					{
						$xml->add_tag('error', fetch_error('edittimelimit', $vbulletin->options['edittimelimit'], $vbulletin->options['contactuslink']));
						$xml->print_xml();
					}
				}
			}
		}

		$show['managepost'] = iif (can_moderate($threadinfo['forumid'], 'candeleteposts') OR can_moderate($threadinfo['forumid'], 'canremoveposts'), true, false);
		$show['approvepost'] = (can_moderate($threadinfo['forumid'], 'canmoderateposts')) ? true : false;
		$show['managethread'] = (can_moderate($threadinfo['forumid'], 'canmanagethreads')) ? true : false;
		$show['quick_edit_form_tag'] = ($show['managethread'] OR $show['managepost'] OR $show['approvepost']) ? false : true;

		// Is this the first post in the thread?
		$isfirstpost = $postinfo['postid'] == $threadinfo['firstpostid'] ? true : false;

		if ($vbulletin->GPC['return_node'])
		{
			$show['deletepostoption'] = false;
		}
		else if ($isfirstpost AND can_moderate($threadinfo['forumid'], 'canmanagethreads'))
		{
			$show['deletepostoption'] = true;
		}
		else if (!$isfirstpost AND can_moderate($threadinfo['forumid'], 'candeleteposts'))
		{
			$show['deletepostoption'] = true;
		}
		else if (((($forumperms & $vbulletin->bf_ugp_forumpermissions['candeletepost']) AND !$isfirstpost) OR (($forumperms & $vbulletin->bf_ugp_forumpermissions['candeletethread']) AND $isfirstpost)) AND $vbulletin->userinfo['userid'] == $postinfo['userid'])
		{
			$show['deletepostoption'] = true;
		}
		else
		{
			$show['deletepostoption'] = false;
		}

		$show['softdeleteoption'] = true;
		$show['physicaldeleteoption'] = iif (can_moderate($threadinfo['forumid'], 'canremoveposts'), true, false);
		$show['keepattachmentsoption'] = iif ($postinfo['attach'], true, false);
		$show['firstpostnote'] = $isfirstpost;

		require_once(DIR . '/includes/functions_editor.php');
		require_once(DIR . '/includes/functions_attach.php');

		$forum_allowsmilies = ($foruminfo['allowsmilies'] ? 1 : 0);
		$editor_parsesmilies = ($forum_allowsmilies AND $postinfo['allowsmilie'] ? 1 : 0);

		$post =& $postinfo;

		$posthash = md5(TIMENOW . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']);
		$poststarttime = TIMENOW;

		if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostattachment'] AND $vbulletin->userinfo['userid'] AND !empty($vbulletin->userinfo['attachmentextensions']) AND !$vbulletin->GPC['return_node'])
		{
			$values = "values[t]=$threadinfo[threadid]";
			require_once(DIR . '/packages/vbattach/attach.php');
			$attach = new vB_Attach_Display_Content($vbulletin, 'vBForum_Post');
			$attachmentoption = $attach->fetch_edit_attachments($posthash, $poststarttime, $postattach, 0, $values, $vbulletin->GPC['editorid'], $attachcount);
			$contenttypeid = $attach->fetch_contenttypeid();
		}
		else
		{
			$attachmentoption = '';
			$contenttypeid = 0;
		}

		require_once(DIR . '/includes/functions_file.php');
		$attachinfo = fetch_attachmentinfo($posthash, $poststarttime, $contenttypeid, array('p' => $postinfo['postid']));

		// This function creates the global var $messagearea, that is used below
		$editorid = construct_edit_toolbar(
			htmlspecialchars_uni($postinfo['pagetext']),
			0,
			$foruminfo['forumid'],
			$forum_allowsmilies,
			$postinfo['allowsmilie'],
			false,
			'qe',
			$vbulletin->GPC['editorid'],
			$attachinfo,
			'forum',
			'vBForum_Post',
			$postinfo['postid']
		);

		$xml->add_group('quickedit');
			$xml->add_tag('editor', process_replacement_vars($messagearea), array(
				'reason'       => $postinfo['edit_reason'],
				'parsetype'    => $foruminfo['forumid'],
				'parsesmilies' => $editor_parsesmilies,
				'mode'         => $show['is_wysiwyg_editor'],
				'content'      => 'forum'
			));
			if ($contenttypeid)
			{
				add_ajax_attachment_xml($xml, $contenttypeid, $posthash, $poststarttime, array('p' => $postinfo['postid']));
			}
			$xml->add_tag('ckeconfig', vB_Ckeditor::getInstance($editorid)->getConfig());
		$xml->close_group();
		$xml->print_xml();
	}
}

// #############################################################################
// handle editor mode switching

if ($_POST['do'] == 'editorswitch')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'towysiwyg'    => TYPE_BOOL,
		'message'      => TYPE_NOTRIM,
		'parsetype'    => TYPE_STR, // string to support non-forum options
		'allowsmilie'  => TYPE_BOOL,
		'allowbbcode'  => TYPE_BOOL, // run time editor option for announcements
	));

	$vbulletin->GPC['message'] = convert_urlencoded_unicode($vbulletin->GPC['message']);

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	require_once(DIR . '/includes/class_wysiwygparser.php');

	if ($vbulletin->GPC['parsetype'] == 'calendar')
	{
		require_once(DIR . '/includes/functions_calendar.php');
		$vbulletin->input->clean_gpc('p', 'calendarid', TYPE_UINT);
		$calendarinfo = verify_id('calendar', $vbulletin->GPC['calendarid'], 0, 1);
		if ($calendarinfo)
		{
			$getoptions = convert_bits_to_array($calendarinfo['options'], $_CALENDAROPTIONS);
			$geteaster = convert_bits_to_array($calendarinfo['holidays'], $_CALENDARHOLIDAYS);
			$calendarinfo = array_merge($calendarinfo, $getoptions, $geteaster);
		}
	}
	if ($vbulletin->GPC['parsetype'] == 'announcement')
	{	// oh this is a kludge but there is no simple way to changing the bbcode parser from using global $post with announcements without changing function arguments
		$post = array(
			'announcementoptions' => $vbulletin->GPC['allowbbcode'] ? $vbulletin->bf_misc_announcementoptions['allowbbcode'] : 0
		);
	}

	if ($vbulletin->GPC['towysiwyg'])
	{
		// from standard to wysiwyg
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$message = process_replacement_vars($html_parser->parse_wysiwyg_html(htmlspecialchars_uni($vbulletin->GPC['message']), false, $vbulletin->GPC['parsetype'], $vbulletin->GPC['allowsmilie']));
		if (is_browser('mozilla'))
		{
			// Going with a list of items to check at the end of the container, one for now but other tags might need to be added
			// Otherwise we are going to have instances where a break appears when we don't want it
			$find = array(
				'#(<hr[^>]*>)$#si',
			);
			$replace = array('\\1<br type="_moz" />');
			$message = preg_replace($find, $replace, $message);
		}
		$xml->add_tag('message', $message);
	}
	else
	{
		// from wysiwyg to standard
		switch ($vbulletin->GPC['parsetype'])
		{
			case 'calendar':
				$dohtml = $calendarinfo['allowhtml']; break;

			case 'privatemessage':
				$dohtml = $vbulletin->options['privallowhtml']; break;

			case 'usernote':
				$dohtml = $vbulletin->options['unallowhtml']; break;

			case 'nonforum':
				$dohtml = $vbulletin->options['allowhtml']; break;

			case 'signature':
				$dohtml = ($vbulletin->userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['allowhtml']); break;

			default:
				if (intval($vbulletin->GPC['parsetype']))
				{
					$parsetype = intval($vbulletin->GPC['parsetype']);
					$foruminfo = fetch_foruminfo($parsetype);
					$dohtml = $foruminfo['allowhtml']; break;
				}
				else
				{
					$dohtml = false;
				}

				($hook = vBulletinHook::fetch_hook('editor_switch_wysiwyg_to_standard')) ? eval($hook) : false;
		}

		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$xml->add_tag('message', process_replacement_vars($html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $dohtml)));
	}

	$xml->print_xml();
}

// #############################################################################
// mark forums read

if ($_POST['do'] == 'markread')
{
	$vbulletin->input->clean_gpc('p', 'forumid', TYPE_UINT);

	require_once(DIR . '/includes/functions_misc.php');
	$mark_read_result = mark_forums_read($foruminfo['forumid']);

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('readmarker');

	$xml->add_tag('phrase', $mark_read_result['phrase']);
	$xml->add_tag('url', $mark_read_result['url']);

	$xml->add_group('forums');
	if (is_array($mark_read_result['forumids']))
	{
		foreach ($mark_read_result['forumids'] AS $forumid)
		{
			$xml->add_tag('forum', $forumid);
		}
	}
	$xml->close_group();

	$xml->close_group();
	$xml->print_xml();
}

// ###########################################################################
// Image Verification

if ($_POST['do'] == 'imagereg')
{
	$vbulletin->input->clean_gpc('p', 'hash', TYPE_STR);

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	if ($vbulletin->options['hv_type'] == 'Image')
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verify =& vB_HumanVerify::fetch_library($vbulletin);
		$verify->delete_token($vbulletin->GPC['hash']);
		$output = $verify->generate_token();
		$xml->add_tag('hash', $output['hash']);
	}
	else
	{
		$xml->add_tag('error', fetch_error('humanverify_image_wronganswer'));
	}
	$xml->print_xml();
}

// ###########################################################################
// New Securitytoken

if ($_POST['do'] == 'securitytoken')
{
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	$xml->add_tag('securitytoken', $vbulletin->userinfo['securitytoken']);

	$xml->print_xml();
}

// #############################################################################
// fetch a profile field editor
if ($_POST['do'] == 'fetchuserfield')
{
	require_once(DIR . '/includes/functions_user.php');

	$vbulletin->input->clean_array_gpc('p', array(
		'fieldid' => TYPE_UINT
	));

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('response');

	if ($profilefield = $db->query_first("SELECT profilefield.* FROM
		" . TABLE_PREFIX . "profilefield AS profilefield
		WHERE profilefieldid = " . $vbulletin->GPC['fieldid']))
	{
		if ($profilefield['editable'] == 1 OR ($profilefield['editable'] == 2 AND empty($vbulletin->userinfo["field$profilefield[profilefieldid]"])))
		{
			$profilefield_template = fetch_profilefield($profilefield, 'memberinfo_customfield_edit');
			$xml->add_tag('template', process_replacement_vars($profilefield_template));
		}
		else
		{
			$xml->add_tag('error', fetch_error('profile_field_uneditable'));
			$xml->add_tag('uneditable', '1');
		}
	}
	else
	{
		// we want this person to refresh the page, so just throw a no perm error
		print_no_permission();
	}

	$xml->close_group();
	$xml->print_xml();
}

// #############################################################################
// dismisses a dismissible notice
if ($_POST['do'] == 'dismissnotice')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'noticeid'	=> TYPE_UINT
	));

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	$update_record = $db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "noticedismissed
			(noticeid, userid)
		VALUES
			(" . $vbulletin->GPC['noticeid'] . ", " . $vbulletin->userinfo['userid'] .")
	");

	// output XML
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('response');
		$xml->add_tag('dismissed', $vbulletin->GPC['noticeid']);
	$xml->close_group();
	$xml->print_xml();
}

// #############################################################################
// save a profile field
if ($_POST['do'] == 'saveuserfield')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'fieldid'   => TYPE_UINT,
		'userfield' => TYPE_ARRAY
	));

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	if (!($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canmodifyprofile']))
	{
		print_no_permission();
	}

	// handle AJAX posting of %u00000 entries
	$vbulletin->GPC['userfield'] = convert_urlencoded_unicode($vbulletin->GPC['userfield']);

	// init user datamanager
	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($vbulletin->userinfo);
	$userdata->set_userfields($vbulletin->GPC['userfield']);
	$userdata->save();

	// fetch profilefield data
	$profilefield = $db->query_first("
		SELECT profilefield.* FROM " . TABLE_PREFIX . "profilefield AS profilefield
		WHERE profilefieldid = " . $vbulletin->GPC['fieldid']
	);

	// get displayable profilefield value
	$new_value = (isset($userdata->userfield['field' . $vbulletin->GPC['fieldid']]) ?
		$userdata->userfield['field' . $vbulletin->GPC['fieldid']] :
		$vbulletin->userinfo['field' . $vbulletin->GPC['fieldid']]
	);
	fetch_profilefield_display($profilefield, $new_value);

	// output XML
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('response');

	$returnvalue = $profilefield['value'] == '' ? $vbphrase['n_a'] : $profilefield['value'];
	$xml->add_tag('value', process_replacement_vars($returnvalue));
	if ($profilefield['editable'] == 2 AND !empty($new_value))
	{
		// this field is no longer editable
		$xml->add_tag('uneditable', '1');
	}

	$xml->close_group();
	$xml->print_xml();
}

// #############################################################################
// verify username during registration

if ($_POST['do'] == 'verifyusername')
{
	/**
	* Checks username status, and return status for registration
	* Values for the XML output includes:
	* username: a direct copy of the original Username, for references needs
	* status: valid / invalid username?
	* response: string of error message from the datamanager
	*/

	$vbulletin->input->clean_gpc('p', 'username', TYPE_STR);
	$vbulletin->GPC['username'] = convert_urlencoded_unicode($vbulletin->GPC['username']);

	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_ARRAY);
	$userdata->set('username', $vbulletin->GPC['username']);
	if (!empty($userdata->errors))
	{
		$status = "invalid";
		$message = "";
		$image = vB_Template_Runtime::fetchStyleVar('imgdir_misc') . "/cross.png";
		foreach ($userdata->errors AS $index => $error)
		{
			$message .= "$error";
		}
	}
	else
	{
		$status = "valid";
		$image = vB_Template_Runtime::fetchStyleVar('imgdir_misc') . "/tick.png";
		$message = $vbphrase['username_is_valid'];
	}

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('response');
		$xml->add_tag('status', $status);
		$xml->add_tag('image', $image);
		$xml->add_tag('message', $message);
	$xml->close_group();
	$xml->print_xml();
}

// grabbing bb codes for quoting on quick reply
if ($_POST['do'] == 'getquotes')
{
	$vbulletin->input->clean_array_gpc('c', array(
		'vbulletin_multiquote' => TYPE_STR
	));

	$vbulletin->input->clean_array_gpc('p', array(
		'p'        => TYPE_STR
	));

	// add multiquotes stored in cookies
	if ($vbulletin->options['multiquote'] AND !empty($vbulletin->GPC['vbulletin_multiquote']))
	{
		$quote_postids = explode(',', $vbulletin->GPC['vbulletin_multiquote']);
	}
	else
	{
		$quote_postids = array();
	}
	// add quote from the post we are replying to
	$quote_postids[] = $vbulletin->GPC['p'];

	if ($quote_postids)
	{
		require_once(DIR . '/includes/functions_newpost.php');
		$quotes = fetch_quotable_posts($quote_postids, $threadinfo['threadid'], $unquoted_post_count, $quoted_post_ids, 'only', true);
	}

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_tag('quotes', $quotes);
	$xml->print_xml();
}

if ($_POST['do'] == 'getvideoproviders')
{
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('providers');

	$bbcodes = $db->query_read_slave("
		SELECT
			provider, url
		FROM " . TABLE_PREFIX . "bbcode_video
		ORDER BY priority
	");
	while ($bbcode = $db->fetch_array($bbcodes))
	{
		$xml->add_tag('provider', '', $bbcode);
	}

	$xml->close_group('providers');
	$xml->print_xml();
}

if ($_POST['do'] == 'overlay')
{
	$_POST['do'] = 'fetchhtml';
	$_POST['template'] = 'overlay';
}

if ($_POST['do'] == 'fetchhtml')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'template' => TYPE_NOHTML,
	));

	$posthash = md5(TIMENOW . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']);
	$poststarttime = TIMENOW;

	switch ($vbulletin->GPC['template'])
	{
		case 'overlay':
		case 'editor_upload_overlay':
			break;
		default:
			exit;
	}

	$templater = vB_Template::create($vbulletin->GPC['template']);

	// Images in the overlay template need a relative path when in the Admin CP.
	if ($vbulletin->GPC['template'] == 'overlay')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'adminhash' => TYPE_NOHTML,
		));
		$imgrelpath = $vbulletin->GPC['adminhash'] == '' ? '' : '../';
		$templater->register('imgrelpath', $imgrelpath);
	}

	$templater->register('posthash', $posthash);
	$templater->register('poststarttime', $poststarttime);
	$html = $templater->render(false);

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_tag('html', $html);
	$xml->print_xml();
}

if ($_REQUEST['do'] == 'list')
{
	$current_user = new vB_Legacy_CurrentUser();
	$vbulletin->input->clean_array_gpc('p', array(
		'search_type' => TYPE_UINT));

	if ($vbulletin->GPC_exists['search_type'])
	{
		vB_Search_Searchtools::getUiXml($vbulletin->GPC['search_type'],
			vB_Search_Searchtools::searchIntroFetchPrefs($current_user, $vbulletin->GPC['search_type']));
	}
	else
	{
		vB_Search_Searchtools::getUiXml(vB_Search_Core::TYPE_COMMON,
			vB_Search_Searchtools::searchIntroFetchPrefs($current_user, vB_Search_Core::TYPE_COMMON));
	}
}

if ($_POST['do'] == 'loadimageconfig')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'attachmentid'  => TYPE_UINT,
		'posthash'      => TYPE_NOHTML,
		'poststarttime' => TYPE_NOHTML,
		'contentid'     => TYPE_UINT,
	));

	if ($vbulletin->GPC['posthash'] != md5($vbulletin->GPC['poststarttime'] . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']))
	{
		exit;
	}

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('settings');

		if ($attachment = $db->query_first("
			SELECT attachmentid, settings, posthash, contenttypeid, contentid, filename
			FROM " . TABLE_PREFIX . "attachment
			WHERE attachmentid = " . $vbulletin->GPC['attachmentid'] . "
		"))
		{
			if ($settings = unserialize($attachment['settings']))
			{
				foreach ($settings AS $key => $value)
				{
					$xml->add_tag($key, $value);
				}
			}
		}

		if (!$attachment OR ($attachment['posthash'] AND $attachment['posthash'] != $vbulletin->GPC['posthash']))
		{
			exit;
		}

		if (!$attachment['posthash'])
		{
			require_once(DIR . '/packages/vbattach/attach.php');
			// Verify that the user can modify this EXISTING attachment..
			// We also verify that the contentid matches -- even is the user plays around with the javascript to send in different contentid
			// They can still only modify attachments that they have access to modify
			if (
				$vbulletin->GPC['contentid'] != $attachment['contentid']
					OR
				!($attachlib =& vB_Attachment_Store_Library::fetch_library($vbulletin, $attachment['contenttypeid']))
					OR
				!$attachlib->verify_permissions_attachmentid($attachment['attachmentid'])
			)
			{
				exit;
			}
		}

	$xml->add_tag('extension', file_extension($attachment['filename']));
	$xml->add_tag('canstyle', ($vbulletin->userinfo['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canattachmentcss']) ? 1 : 0);
	$xml->close_group('settings');
	$xml->print_xml();
}

if ($_POST['do'] == 'saveimageconfig')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'alignment'       => TYPE_NOHTML,
		'size'            => TYPE_NOHTML,
		'title'           => TYPE_NOHTML,
		'caption'         => TYPE_NOHTML,
		'link'            => TYPE_UINT,
		'linkurl'         => TYPE_NOHTML,
		'linktarget'      => TYPE_BOOL,
		'styles'          => TYPE_NOHTML,
		'description'     => TYPE_NOHTML,
		'attachmentid'    => TYPE_UINT,
		'posthash'        => TYPE_NOHTML,
		'poststarttime'   => TYPE_UINT,
		'contentid'       => TYPE_UINT,
	));

	if ($vbulletin->GPC['posthash'] != md5($vbulletin->GPC['poststarttime'] . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']))
	{
		exit;
	}

	$vbulletin->GPC['title'] = convert_urlencoded_unicode($vbulletin->GPC['title']);
	$vbulletin->GPC['caption'] = convert_urlencoded_unicode($vbulletin->GPC['caption']);
	$vbulletin->GPC['description'] = convert_urlencoded_unicode($vbulletin->GPC['description']);

	$settings = array(
		'alignment'   => $vbulletin->GPC['alignment'],
		'size'        => $vbulletin->GPC['size'],
		'caption'     => $vbulletin->GPC['caption'],
		'link'        => $vbulletin->GPC['link'],
		'linkurl'     => $vbulletin->GPC['linkurl'],
		'linktarget'  => $vbulletin->GPC['linktarget'],
		'styles'      => ($vbulletin->userinfo['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canattachmentcss']) ? $vbulletin->GPC['styles'] : '',
		'description' => $vbulletin->GPC['description'],
		'title'       => $vbulletin->GPC['title'],
	);

	$attachment = $db->query_first("
		SELECT attachmentid, settings, posthash, contenttypeid, contentid
		FROM " . TABLE_PREFIX . "attachment
		WHERE attachmentid = " . $vbulletin->GPC['attachmentid'] . "
	");

	if (!$attachment OR ($attachment['posthash'] AND $attachment['posthash'] != $vbulletin->GPC['posthash']))
	{
		exit;
	}

	if (!$attachment['posthash'])
	{
		require_once(DIR . '/packages/vbattach/attach.php');
		// Verify that the user can modify this EXISTING attachment..
		if (
			$vbulletin->GPC['contentid'] != $attachment['contentid']
				OR
			!($attachlib =& vB_Attachment_Store_Library::fetch_library($vbulletin, $attachment['contenttypeid']))
				OR
			!$attachlib->verify_permissions_attachmentid($attachment['attachmentid'])
		)
		{
			exit;
		}
	}

	$db->query_write("
		INSERT INTO " . TABLE_PREFIX . "attachment
			(attachmentid, settings)
		VALUES (" . $vbulletin->GPC['attachmentid'] . ", '" . $db->escape_string(serialize($settings)) . "')
		ON DUPLICATE KEY UPDATE settings = '" . $db->escape_string(serialize($settings)) . "'

	");

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_tag('ok', 1);
	$xml->print_xml();
}

if ($_REQUEST['do'] == 'rss')
{
	//we just replace "ajax.php" with "external.php"
	$redirect_url = 'external.php?' . $_SERVER['QUERY_STRING'];
	exec_header_redirect($redirect_url , 301);
}

if ($_REQUEST['do'] == 'get_comment_reply')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'postid' => TYPE_UINT));

	if (!$vbulletin->GPC_exists['postid'])
	{
		exit();
	}

	$current_user = new vB_Legacy_CurrentUser();
	vBCms_Widget_Comments::GetCommentUIXml($vbulletin->GPC['postid']);
}

if ($_REQUEST['do'] == 'list_sections' or $_REQUEST['do'] == 'list_categories'
	or $_REQUEST['do'] == 'list_nodes' or $_REQUEST['do'] == 'list_allsection'
	or $_REQUEST['do'] == 'list_allcategory' or $_REQUEST['do'] == 'find_leaves'
	or $_REQUEST['do'] == 'find_categories')
{
	require_once('./includes/adminfunctions.php');
	require_once(DIR . '/' . $vbulletin->config['Misc']['admincpdir'] . '/cms_content_admin.php');
	//and that page will do all the work.
}

if ($_REQUEST['do'] == 'checkurl')
{
	vBCms_ContentManager::checkUrlAvailable();
}

if ($_REQUEST['do'] == 'perms_section' or $_REQUEST['do'] == 'del_perms')
{
	require_once DIR . '/' . $vbulletin->config['Misc']['admincpdir'] . '/cms_permissions.php';
	//and that page will do all the work.
}

//This is for the calendar widget paging;
if ($_REQUEST['do'] == 'calwidget' AND $vbulletin->products['vbcms'])
{
	$vbulletin->input->clean_array_gpc('r', array(
		'year' => TYPE_UINT,
		'month' => TYPE_UINT));

	if ( $vbulletin->GPC_exists['year'] AND $vbulletin->GPC_exists['month'])
	{
		$view = vBCms_Widget_Calendar::getCalendar($vbulletin->GPC['year'], $vbulletin->GPC['month']);
		$view->registerTemplater(vB_View::OT_XHTML, new vB_Templater_vB());
		$html = $view->render();
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_tag('html', $html);
		$xml->print_xml();
	}


}

// #############################################################################
// get a Theme for user profile customization
//
if ($_REQUEST['do'] == 'gettheme' )
{
	//class db_Assertor needs to be initialized.
	vB_dB_Assertor::init(vB::$vbulletin->db, vB::$vbulletin->userinfo);
	$vbulletin->input->clean_array_gpc('r', array(
		'themeid' => TYPE_STR));

	if ($vbulletin->GPC_exists['themeid'] )
	{
		echo vB_ProfileCustomize::getTheme($vbulletin->GPC['themeid'], 'j');
	}

}
// #############################################################################
// get a Theme for user profile customization
//
if ($_REQUEST['do'] == 'saveusertheme' )
{
	//class db_Assertor needs to be initialized.
	vB_dB_Assertor::init(vB::$vbulletin->db, vB::$vbulletin->userinfo);

	echo vB_ProfileCustomize::saveUserTheme($vbulletin->GPC['usertheme'], $vbulletin->userinfo);

}
// #############################################################################
// get appropriate attachmentid for a passed filedataid, for a custom profile page
// background.
//
if ($_REQUEST['do'] == 'getalbum' )
{

	if (intval($vbulletin->userinfo['userid']) )
	{
		$vbulletin->input->clean_array_gpc('r', array(
			'albumid' => TYPE_UINT));

		if ($vbulletin->GPC_exists['albumid'])
		{
			//class db_Assertor needs to be initialized.
			vB_dB_Assertor::init(vB::$vbulletin->db, vB::$vbulletin->userinfo);
			echo vB_ProfileCustomize::getAlbumContents($vbulletin->GPC['albumid'], $vbulletin->userinfo);
		}
	}

}
// #############################################################################
// get the asset picker
//
if ($_REQUEST['do'] == 'getassetpicker' )
{

	if (intval($vbulletin->userinfo['userid']) )
	{
		//class db_Assertor needs to be initialized.
		vB_dB_Assertor::init(vB::$vbulletin->db, vB::$vbulletin->userinfo);
		vB_ProfileCustomize::getAssetPicker($vbulletin->userinfo, $vbulletin);
	}

}

// #############################################################################
// get the confirm close dialog box
//
if ($_REQUEST['do'] == 'getconfirmclosebox' )
{

	if (intval($vbulletin->userinfo['userid']) )
	{
		echo vB_ProfileCustomize::getConfirmCloseBox();
	}

}

// #############################################################################
// get the confirm close dialog box
//
if ($_REQUEST['do'] == 'getprofiledialog' )
{
	$vbulletin->input->clean_array_gpc('r', array(
		'phrase' => TYPE_STR));
	if ($vbulletin->GPC_exists['phrase'])
	{
		echo vB_ProfileCustomize::getProfileDialog($vbulletin->GPC['phrase']);
	}

}

// #############################################################################
// Autosave editor content

if ($_POST['do'] == 'autosave')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'contenttypeid'   => TYPE_NOHTML,
		'contentid'       => TYPE_UINT,
		'parentcontentid' => TYPE_UINT,
		'pagetext'        => TYPE_STR,
		'title'           => TYPE_NOHTML,
		'posthash'        => TYPE_NOHTML,
		'poststarttime'   => TYPE_UINT,
		'wysiwyg'         => TYPE_BOOL,
		'parsetype'       => TYPE_STR, // string to support non-forum options
	));

	if (!$vbulletin->userinfo['userid'])
	{
		echo 'NO USERID';
		exit;
	}

	if (!vB_Types::instance()->getContentTypeID($vbulletin->GPC['contenttypeid']))
	{
		echo 'INVALID CONTENTTYPEID';
		exit;
	}

	if (!$vbulletin->GPC['pagetext'])
	{
		echo 'NO PAGETEXT';
		exit;
	}

	$vbulletin->GPC['pagetext'] = convert_urlencoded_unicode($vbulletin->GPC['pagetext']);
	$vbulletin->GPC['title'] = convert_urlencoded_unicode($vbulletin->GPC['title']);

	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');

		if ($vbulletin->GPC['parsetype'] == 'calendar')
		{
			require_once(DIR . '/includes/functions_calendar.php');
			$vbulletin->input->clean_gpc('p', 'calendarid', TYPE_UINT);
			$calendarinfo = verify_id('calendar', $vbulletin->GPC['calendarid'], 0, 1);
			if ($calendarinfo)
			{
				$getoptions = convert_bits_to_array($calendarinfo['options'], $_CALENDAROPTIONS);
				$geteaster = convert_bits_to_array($calendarinfo['holidays'], $_CALENDARHOLIDAYS);
				$calendarinfo = array_merge($calendarinfo, $getoptions, $geteaster);
			}
		}
		if ($vbulletin->GPC['parsetype'] == 'announcement')
		{	// oh this is a kludge but there is no simple way to changing the bbcode parser from using global $post with announcements without changing function arguments
			$post = array(
				'announcementoptions' => $vbulletin->GPC['allowbbcode'] ? $vbulletin->bf_misc_announcementoptions['allowbbcode'] : 0
			);
		}

		// from wysiwyg to standard
		switch ($vbulletin->GPC['parsetype'])
		{
			case 'calendar':
				$dohtml = $calendarinfo['allowhtml']; break;

			case 'privatemessage':
				$dohtml = $vbulletin->options['privallowhtml']; break;

			case 'usernote':
				$dohtml = $vbulletin->options['unallowhtml']; break;

			case 'nonforum':
				$dohtml = $vbulletin->options['allowhtml']; break;

			case 'signature':
				$dohtml = ($vbulletin->userinfo['permissions']['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['allowhtml']); break;

			default:
				if (intval($vbulletin->GPC['parsetype']))
				{
					$parsetype = intval($vbulletin->GPC['parsetype']);
					$foruminfo = fetch_foruminfo($parsetype);
					$dohtml = $foruminfo['allowhtml']; break;
				}
				else
				{
					$dohtml = false;
				}

				//($hook = vBulletinHook::fetch_hook('editor_switch_wysiwyg_to_standard')) ? eval($hook) : false;
		}

		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$vbulletin->GPC['pagetext'] = process_replacement_vars($html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['pagetext'], $dohtml));
	}

	// If we have a posthash then only save it if it is valid
	// this can be used to grab attachments that are attached to this draft
	if ($vbulletin->GPC['posthash']
			AND
		($vbulletin->GPC['posthash'] != md5($vbulletin->GPC['poststarttime'] . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']))
	)
	{
		$vbulletin->GPC['posthash'] = '';
	}

	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "autosave
			(contenttypeid, contentid, parentcontentid, userid, pagetext, title, posthash, dateline)
		VALUES
		(
			'" . $db->escape_string($vbulletin->GPC['contenttypeid']) . "',
			{$vbulletin->GPC['contentid']},
			{$vbulletin->GPC['parentcontentid']},
			{$vbulletin->userinfo['userid']},
			'" . $db->escape_string($vbulletin->GPC['pagetext']) . "',
			'" . $db->escape_string($vbulletin->GPC['title']) . "',
			'" . $db->escape_string($vbulletin->GPC['posthash']) . "',
			" . TIMENOW . "
		)
	");

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_tag('ok', 1);
	$xml->print_xml();
}

($hook = vBulletinHook::fetch_hook('ajax_complete')) ? eval($hook) : false;

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 63231 $
|| ####################################################################
\*======================================================================*/
