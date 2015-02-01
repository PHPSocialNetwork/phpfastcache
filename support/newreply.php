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
define('GET_EDIT_TEMPLATES', true);
define('THIS_SCRIPT', 'newreply');
define('CSRF_PROTECTION', true);
if ($_POST['do'] == 'postreply')
{
	if (isset($_POST['ajax']))
	{
		define('NOPMPOPUP', 1);
		define('NOSHUTDOWNFUNC', 1);
	}
	if (isset($_POST['fromquickreply']))
	{	// Don't update Who's Online for Quick Replies since it will get stuck on that until the user goes somewhere else
		define('LOCATION_BYPASS', 1);
	}
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'threadmanage',
	'posting',
	'postbit',
	'reputationlevel',
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
	'ranks',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'newreply',
	'newpost_attachment',
	'newreply_reviewbit',
	'newreply_reviewbit_ignore',
	'newreply_reviewbit_ignore_global',
	'newpost_attachmentbit',
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
	'humanverify',
	'facebook_publishcheckbox'
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_newpost.php');
require_once(DIR . '/includes/functions_editor.php');
require_once(DIR . '/includes/functions_bigthree.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_forum_url();

// ### STANDARD INITIALIZATIONS ###
$checked = array();
$newpost = array();
$postattach = array();
$contenttype = 'vBForum_Post';

// sanity checks...
if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'newreply';
}

$vbulletin->input->clean_array_gpc('r', array(
	'noquote'	=>	TYPE_BOOL,
	'quoteall' 	=>	TYPE_BOOL
));

($hook = vBulletinHook::fetch_hook('newreply_start')) ? eval($hook) : false;

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
		$vbulletin->url = fetch_seo_url('thread', $threadinfo);
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
if (in_coventry($threadinfo['postuserid']) AND !can_moderate($threadinfo['forumid']))
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
}

// ### GET QUOTE FEATURES (WITH MQ SUPPORT) ###
// This section must exist before $_POST[do] == postreply because of the $newpost stuff
$newpost['message'] = '';
$unquoted_posts = 0;
$multiquote_empty = '';
$specifiedpost = 0;

if ($_REQUEST['do'] == 'newreply')
{
	$vbulletin->input->clean_array_gpc('c', array(
		'vbulletin_multiquote' => TYPE_STR
	));
	$vbulletin->input->clean_array_gpc('r', array(
		'return_node' => TYPE_UINT
	));

	if ($vbulletin->options['multiquote'] AND !empty($vbulletin->GPC['vbulletin_multiquote']))
	{
		$quote_postids = explode(',', $vbulletin->GPC['vbulletin_multiquote']);
	}
	else
	{
		$quote_postids = array();
	}

	// quote the last post only if: don't want to skip it, specified a post,
	// and post can be seen (visible or you're a mod)
	if (!$vbulletin->GPC['noquote'] AND $postid AND
		(
			($postinfo['visible'] == 1 AND $threadinfo['visible'] == 1) OR
			(
				($threadinfo['visible'] == 0 OR $postinfo['visible'] == 0) AND
				can_moderate($foruminfo['forumid'], 'canmoderateposts')
			)
		)
	)
	{
		$quote_postids[] = $postinfo['postid'];

		// fetch the quoted post title
		$newpost['title'] = htmlspecialchars_uni(vbchop(fetch_quote_title($postinfo['title'], $threadinfo['title']), $vbulletin->options['titlemaxchars']));

		$specifiedpost = 1; // the post we're replying to we explicitly picked
	}
	else
	{
		$newpost['title'] = htmlspecialchars_uni(vbchop(fetch_quote_title('', $threadinfo['title']), $vbulletin->options['titlemaxchars']));
	}

	if ($quote_postids)
	{
		$newpost['message'] = fetch_quotable_posts($quote_postids, $threadinfo['threadid'], $unquoted_post_count, $quoted_post_ids, 'only');

		$quote_count = count($quoted_post_ids);
		if ($quote_count > 1 OR ($quote_count == 1 AND $vbulletin->GPC['noquote']) OR ($quote_count == 1 AND $quoted_post_ids[0] != $postinfo['postid']))
		{
			// quoting more than one post, one post and noquote is set, or one post that isn't this post -- using MQ,
			// so when we post, remove the posts from the MQ cookie that are in this thread
			$multiquote_empty = 'only';
		}
	}
}

// ############################### start unquoted posts ###############################
if ($_POST['do'] == 'unquotedposts')
{
	$vbulletin->input->clean_array_gpc('c', array(
		'vbulletin_multiquote' => TYPE_STR
	));

	$vbulletin->input->clean_array_gpc('p', array(
		'wysiwyg' => TYPE_BOOL,
		'type' => TYPE_STR
	));

	$quote_postids = explode(',', $vbulletin->GPC['vbulletin_multiquote']);

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	$quote_text = fetch_quotable_posts($quote_postids, $threadinfo['threadid'], $unquoted_post_count, $quoted_post_ids, 'other', true);

	if ($vbulletin->GPC['type'] == 'deselect')
	{
		$remaining = array_diff($quote_postids, $quoted_post_ids);
		$xml->add_tag('mqpostids', implode(',', $remaining));
		//setcookie('vbulletin_multiquote', implode(',', $remaining), 0, '/');
	}
	else
	{
		if ($vbulletin->GPC['wysiwyg'])
		{
			require_once(DIR . '/includes/class_wysiwygparser.php');
			$html_parser = new vB_WysiwygHtmlParser($vbulletin);
			$quote_text = $html_parser->parse_wysiwyg_html(htmlspecialchars_uni($quote_text), false, $threadinfo['forumid'], ($foruminfo['allowsmilies'] ? 1 : 0));
		}

		$xml->add_tag('quotes', process_replacement_vars($quote_text));
	}

	$xml->print_xml();
}

// ############################### start post reply ###############################
if ($_POST['do'] == 'postreply')
{
	// Variables reused in templates
	$posthash =& $vbulletin->input->clean_gpc('p', 'posthash', TYPE_NOHTML);
	$poststarttime =& $vbulletin->input->clean_gpc('p', 'poststarttime', TYPE_UINT);

	$vbulletin->input->clean_array_gpc('p', array(
		'wysiwyg'        => TYPE_BOOL,
		'message'        => TYPE_STR,
		'quickreply'     => TYPE_BOOL,
		'fromquickreply' => TYPE_BOOL,
		'ajaxqrfailed'   => TYPE_BOOL,
		'folderid'       => TYPE_UINT,
		'emailupdate'    => TYPE_UINT,
		'htmlstate'      => TYPE_STR,
		'subscribe'      => TYPE_BOOL,
		'title'          => TYPE_STR,
		'iconid'         => TYPE_UINT,
		'parseurl'       => TYPE_BOOL,
		'signature'      => TYPE_BOOL,
		'preview'        => TYPE_STR,
		'disablesmilies' => TYPE_BOOL,
		'username'       => TYPE_STR,
		'rate'           => TYPE_BOOL,
		'rating'         => TYPE_UINT,
		'stickunstick'   => TYPE_BOOL,
		'openclose'      => TYPE_BOOL,
		'ajax'           => TYPE_BOOL,
		'ajax_lastpost'  => TYPE_INT,
		'loggedinuser'   => TYPE_INT,
		'humanverify'    => TYPE_ARRAY,
		'multiquoteempty'=> TYPE_NOHTML,
		'specifiedpost'  => TYPE_BOOL,
		'return_node'    => TYPE_UINT,
	));

	if ($vbulletin->GPC['loggedinuser'] != 0 AND $vbulletin->userinfo['userid'] == 0)
	{
		// User was logged in when writing post but isn't now. If we got this
		// far, guest posts are allowed, but they didn't enter a username so
		// they'll get an error. Force them to log back in.
		standard_error(fetch_error('session_timed_out_login'), '', false, 'STANDARD_ERROR_LOGIN');
	}

	($hook = vBulletinHook::fetch_hook('newreply_post_start')) ? eval($hook) : false;

	// ### PREP INPUT ###
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$newpost['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $foruminfo['allowhtml']);
	}
	else
	{
		$newpost['message'] = $vbulletin->GPC['message'];
	}

	if ($vbulletin->GPC['ajax'])
	{
		// posting via ajax so we need to handle those %u0000 entries
		$newpost['message'] = convert_urlencoded_unicode($newpost['message']);
	}

	if ($vbulletin->GPC['quickreply'])
	{
		$originalposter = fetch_quote_username($postinfo['username'] . ";$postinfo[postid]");
		$pagetext = trim(strip_quotes($postinfo['pagetext']));

		($hook = vBulletinHook::fetch_hook('newreply_post_quote')) ? eval($hook) : false;

		$templater = vB_Template::create('newpost_quote');
			$templater->register('originalposter', $originalposter);
			$templater->register('pagetext', $pagetext);
		$quotemessage = $templater->render(true);

		$newpost['message'] = trim($quotemessage) . "\n$newpost[message]";
	}

	if ($vbulletin->GPC['fromquickreply'])
	{
		// We only add notifications to threads that don't have one if the user defaults to it, do nothing else!
		if ($vbulletin->userinfo['autosubscribe'] != -1 AND !$threadinfo['issubscribed'])
		{
			$vbulletin->GPC['folderid'] = 0;
			$vbulletin->GPC['emailupdate'] = $vbulletin->userinfo['autosubscribe'];
		}
		else if ($threadinfo['issubscribed'])
		{ // Don't alter current settings
			$vbulletin->GPC['folderid'] = $threadinfo['folderid'];
			$vbulletin->GPC['emailupdate'] = $threadinfo['emailupdate'];
		}
		else
		{ // Don't don't add!
			$vbulletin->GPC['emailupdate'] = 9999;
		}

		// fetch the quoted post title
		$vbulletin->GPC['title'] = fetch_quote_title($postinfo['title'], $threadinfo['title']);
	}

	$newpost['title']          =& $vbulletin->GPC['title'];
	$newpost['iconid']         =& $vbulletin->GPC['iconid'];
	$newpost['parseurl']       = (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_URL) AND $foruminfo['allowbbcode'] AND $vbulletin->GPC['parseurl']);
	$newpost['signature']      =& $vbulletin->GPC['signature'];
	$newpost['preview']        =& $vbulletin->GPC['preview'];
	$newpost['disablesmilies'] =& $vbulletin->GPC['disablesmilies'];
	$newpost['rating']         =& $vbulletin->GPC['rating'];
	$newpost['rate']           =& $newpost['rating'];
	$newpost['username']       =& $vbulletin->GPC['username'];
	$newpost['folderid']       =& $vbulletin->GPC['folderid'];
	$newpost['quickreply']     =& $vbulletin->GPC['quickreply'];
	$newpost['poststarttime']  =& $poststarttime;
	$newpost['posthash']       =& $posthash;
	$newpost['humanverify']    =& $vbulletin->GPC['humanverify'];
	// moderation options
	$newpost['stickunstick']   =& $vbulletin->GPC['stickunstick'];
	$newpost['openclose']      =& $vbulletin->GPC['openclose'];
	$newpost['subscribe']      =& $vbulletin->GPC['subscribe'];
	$newpost['ajaxqrfailed']   = $vbulletin->GPC['ajaxqrfailed'];

	if ($vbulletin->GPC['ajax'] AND $newpost['username'])
	{
		if ($newpost['username'])
		{
			$newpost['username'] = convert_urlencoded_unicode($newpost['username']);
		}
	}

	if ($foruminfo['allowhtml'])
	{
		$htmlchecked = fetch_htmlchecked($vbulletin->GPC['htmlstate']);
		$newpost['htmlstate'] = array_pop($array = array_keys(fetch_htmlchecked($vbulletin->GPC['htmlstate'])));
	}
	else
	{
		$newpost['htmlstate'] = 'on_nl2br';
	}

	if ($vbulletin->GPC_exists['emailupdate'])
	{
		$newpost['emailupdate'] = $vbulletin->GPC['emailupdate'];
	}
	else
	{
		$newpost['emailupdate'] = array_pop($array = array_keys(fetch_emailchecked($threadinfo, $vbulletin->userinfo)));
	}

	if (!$vbulletin->GPC['subscribe'] AND !$vbulletin->GPC['fromquickreply'])
	{
		$newpost['emailupdate'] = 9999;
	}

	if ($vbulletin->GPC['specifiedpost'] AND $postinfo)
	{
		$postinfo['specifiedpost'] = true;
	}

	// Scan post for [attach] tags
	$attachid = array();
	if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostattachment'] AND $vbulletin->userinfo['userid'] AND !empty($vbulletin->userinfo['attachmentextensions']))
	{
		if (preg_match_all('#\[attach(?:=(right|left|config))?\](\d+)\[/attach\]#i', $newpost['message'], $matches) AND $matches[2])
		{
			foreach($matches[2] AS $key => $attachmentid)
			{
				$attachid[] = $attachmentid;
			}
		}
	}

	$cms_redirect = false;
	$cms_comment_thread = false;

	if ($vbulletin->GPC_exists['return_node'] AND $vbulletin->GPC['return_node'])
	{
		if (verify_threadNode($threadinfo['threadid'], $vbulletin->GPC['return_node']))
		{
			$cms_redirect = true;
			$cms_comment_thread = true;
		}
		else // Something fishy - the threadid and nodeid dont match.
		{
			print_no_permission();
		}
	}
	else if ($node = get_nodeFromThreadid($threadinfo['threadid']))
	{
		$cms_comment_thread = true;
		$vbulletin->GPC['return_node'] = $node;
	}	
	build_new_post('reply', $foruminfo, $threadinfo, $postinfo, $newpost, $errors);

	$multiquote_empty = $vbulletin->GPC['multiquoteempty']; // cleaned to nohtml above
	$specifiedpost = ($vbulletin->GPC['specifiedpost'] ? 1 : 0); // keep the sent value (for automoderation stuff)

	if (sizeof($errors) > 0)
	{
		// ### POST HAS ERRORS ###
		if ($vbulletin->GPC['ajax'])
		{
			require_once(DIR . '/includes/class_xml.php');
			$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
			$xml->add_group('errors');
			foreach ($errors AS $error)
			{
				$xml->add_tag('error', $error);
			}
			$xml->close_group();
			$xml->print_xml(true);
		}
		else
		{
			$postpreview = construct_errors($errors); // this will take the preview's place
			construct_checkboxes($newpost);
			$_REQUEST['do'] = 'newreply';
			$newpost['message'] = htmlspecialchars_uni($newpost['message']);
		}
	}
	else if ($newpost['preview'])
	{
		if ($vbulletin->options['multiquote'])
		{
			$vbulletin->input->clean_array_gpc('c', array(
				'vbulletin_multiquote' => TYPE_STR
			));
			$quote_postids = explode(',', $vbulletin->GPC['vbulletin_multiquote']);
		}
		else
		{
			$quote_postids = array();
		}

		if ($quote_postids)
		{
			fetch_quotable_posts($quote_postids, $threadinfo['threadid'], $unquoted_post_count, $quoted_post_ids);

			// handle MQ VBIV-388
			$multiquote_empty = 'only';
		}

		if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostattachment'] AND $vbulletin->userinfo['userid'] AND !empty($vbulletin->userinfo['attachmentextensions']))
		{
			require_once(DIR . '/packages/vbattach/attach.php');
			$attach = new vB_Attach_Display_Content($vbulletin, 'vBForum_Post');
			$postattach = $attach->fetch_postattach($posthash, 0, $postinfo['userid'], true, $attachid);
		}

		// ### PREVIEW POST ###
		$postpreview = process_post_preview($newpost, 0, $postattach['bycontent'][0], $postattach['byattachment']);
		$_REQUEST['do'] = 'newreply';
		$newpost['message'] = htmlspecialchars_uni($newpost['message']);
	}
	else
	{
		// ### NOT PREVIEW - ACTUAL POST ###
		if ($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid'])
		{
			$threadview = max($threadinfo['threadread'], $threadinfo['forumread'], TIMENOW - ($vbulletin->options['markinglimit'] * 86400));
		}
		else
		{
			$threadview = intval(fetch_bbarray_cookie('thread_lastview', $thread['threadid']));
			if (!$threadview)
			{
				$threadview = $vbulletin->userinfo['lastvisit'];
			}
		}

		$newpostid = $newpost['postid'];

		if ($cms_comment_thread)
		{
			// Expire any CMS comments cache entries.
			$expire_cache = array('cms_comments_change');
			$expire_cache[] = 'cms_comments_change_' . $threadinfo['threadid'];
			$expire_cache[] = 'cms_comments_add_' . $vbulletin->GPC['return_node'];

			vB_Cache::instance()->eventPurge($expire_cache);
			vB_Cache::instance()->cleanNow();

			$cms_url = vBCms_Route_Content::getURL(array('node' => $vbulletin->GPC['return_node']));
			$join = strpos($cms_url,'?') ? '&' : '?';
			$cms_url .= "{$join}postid={$newpostid}#comments_{$newpostid}";
			$cms_url = str_ireplace('&amp;', '&', $cms_url);
		}

		if ($vbulletin->GPC['ajax'])
		{
			// #############################################################################
			// #############################################################################
			// #############################################################################
			require_once(DIR . '/includes/class_postbit.php');
			require_once(DIR . '/includes/class_xml.php');

			$postcount = 0;
			$thread =& $threadinfo;
			$forum =& $foruminfo;

			if ($cms_redirect)
			{	/* This is a CMS comment. We got here by posting from an article etc. */
				$postbit_factory = new vB_Postbit_Factory();
				$postbit_factory->registry =& $vbulletin;
				$postbit_factory->forum =& $foruminfo;
				$postbit_factory->thread =& $thread;
				$postbit_factory->cache = array();
				$postbit_factory->bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
				$postbit_factory->bbcode_parser->set_quote_template('vbcms_bbcode_quote');

				$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
				$xml->add_group('postbits');

				$postbit_obj = $postbit_factory->fetch_postbit('post');
				$postbit_obj->set_template_prefix('vbcms_');

				($hook = vBulletinHook::fetch_hook('newreply_post_ajax')) ? eval($hook) : false;

				if ($newpost['doublepost'])
				{
					$vbulletin->GPC['ajax_lastpost'] = 0;
				}

				$posts = $db->query_read("
					SELECT
						post.*, post.username AS postusername, post.ipaddress AS ip, IF(post.visible = 2, 1, 0) AS isdeleted,
						user.*, userfield.*, usertextfield.*,
						" . iif($vbulletin->options['avatarenabled'], 'avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight,') . "
						" . iif($deljoin, 'deletionlog.userid AS del_userid, deletionlog.username AS del_username, deletionlog.reason AS del_reason,') . "
						IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, infractiongroupid
						" . iif(!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehiddencustomfields']), $vbulletin->profilefiled['hidden']) . "
					FROM " . TABLE_PREFIX . "post AS post
					LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = post.userid)
					LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
					LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
					" . iif($vbulletin->options['avatarenabled'], "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)") . "
					WHERE post.threadid = $threadinfo[threadid] AND post.parentid != 0 AND " . (
						($lastviewed = $vbulletin->GPC['ajax_lastpost']) ?
							"post.dateline > $lastviewed AND (post.visible = 1 OR post.postid = $newpost[postid])" :
							"post.postid = $newpost[postid]"
						) . "
					ORDER BY dateline
				");

				$cms_template = vB_Template::create('vbcms_comments_detail');

				while ($post = $db->fetch_array($posts))
				{
					if ($vbulletin->options['avatarenabled']
					AND $vbulletin->userinfo['showavatars']
					AND !$post['hascustomavatar'] AND !$post['avatarid'])
					{
						$post['hascustomavatar'] = 1;
						$post['avatarid'] = true;
						$post['avatarurl'] = $post['avatarpath'] = vB_Template_Runtime::fetchStyleVar('imgdir_misc') . '/unknown.gif';
						$post['avwidth'] = 60;
						$post['avheight'] = 60;
					}

					if ($tachyuser = in_coventry($post['userid'])
					AND !can_moderate($threadinfo['forumid']))
					{
						continue;
					}

					($hook = vBulletinHook::fetch_hook('showthread_postbit_create')) ? eval($hook) : false;

					if ($newpost['doublepost'])
					{
						$xml->add_tag('updatepost', $newpost['postid']);
					}
					
					$postcount++;
					$this_postbit = process_replacement_vars($postbit_obj->construct_postbit($post));

					$cms_template->register('postid', $post['postid'] );
					$cms_template->register('postbit', $this_postbit);

					$xml->add_tag('postbit', $cms_template->render(), array('postid' => $post['postid']));
				}
			}
			else
			{
				// work out if quickreply should be shown or not
				if (
					$vbulletin->options['quickreply']
					AND
					!$thread['isdeleted'] AND !is_browser('netscape') AND $vbulletin->userinfo['userid']
					AND (
						($vbulletin->userinfo['userid'] == $threadinfo['postuserid'] AND $forumperms & $vbulletin->bf_ugp_forumpermissions['canreplyown'])
						OR
						($vbulletin->userinfo['userid'] != $threadinfo['postuserid'] AND $forumperms & $vbulletin->bf_ugp_forumpermissions['canreplyothers'])
					) AND
					($thread['open'] OR can_moderate($threadinfo['forumid'], 'canopenclose'))
				)
				{
					$show['quickreply'] = true;
				}
				else
				{
					$show['quickreply'] = false;
					$show['wysiwyg'] = 0;
					$quickreply = '';
				}

				if (!$forum['allowposting'])
				{
					$show['quickreply'] = false;
				}

				$show['managepost'] = iif(can_moderate($threadinfo['forumid'], 'candeleteposts') OR can_moderate($threadinfo['forumid'], 'canremoveposts'), true, false);
				$show['approvepost'] = (can_moderate($threadinfo['forumid'], 'canmoderateposts')) ? true : false;
				$show['managethread'] = can_moderate($threadinfo['forumid'], 'canmanagethreads') ? true : false;
				$show['inlinemod'] = ($show['managethread'] OR $show['managepost'] OR $show['approvepost']) ? true : false;

				$show['multiquote_global'] = ($vbulletin->options['multiquote'] AND $vbulletin->userinfo['userid']);
				if ($show['multiquote_global'])
				{
					$vbulletin->input->clean_array_gpc('c', array(
						'vbulletin_multiquote' => TYPE_STR
					));

					// remove all posts from this thread from the cookie, but leave all the others
					$quote_postids = explode(',', $vbulletin->GPC['vbulletin_multiquote']);
					fetch_quotable_posts($quote_postids, $threadinfo['threadid'], $unquoted_post_count, $quoted_post_ids, 'only');

					$remaining = array_diff($quote_postids, $quoted_post_ids);
					setcookie('vbulletin_multiquote', implode(',', $remaining), 0, '/');
				}

				$hook_query_fields = $hook_query_joins = $hook_query_where = '';
				($hook = vBulletinHook::fetch_hook('newreply_post_ajax')) ? eval($hook) : false;

				if ($newpost['doublepost'])
				{
					$vbulletin->GPC['ajax_lastpost'] = 0;
				}

				$posts = $db->query_read("
					SELECT
						post.*, post.username AS postusername, post.ipaddress AS ip, IF(post.visible = 2, 1, 0) AS isdeleted,
						user.*, userfield.*, usertextfield.*,
						" . iif($forum['allowicons'], 'icon.title as icontitle, icon.iconpath,') . "
						" . iif($vbulletin->options['avatarenabled'], 'avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight,') . "
						" . iif($deljoin, 'deletionlog.userid AS del_userid, deletionlog.username AS del_username, deletionlog.reason AS del_reason,') . "
						editlog.userid AS edit_userid, editlog.username AS edit_username, editlog.dateline AS edit_dateline,
						editlog.reason AS edit_reason, editlog.hashistory,
						postparsed.pagetext_html, postparsed.hasimages,
						sigparsed.signatureparsed, sigparsed.hasimages AS sighasimages,
						sigpic.userid AS sigpic, sigpic.dateline AS sigpicdateline, sigpic.width AS sigpicwidth, sigpic.height AS sigpicheight,
						IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, infractiongroupid
						" . iif(!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehiddencustomfields']), $vbulletin->profilefiled['hidden']) . "
						$hook_query_fields
					FROM " . TABLE_PREFIX . "post AS post
					LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = post.userid)
					LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
					LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
					" . iif($forum['allowicons'], "LEFT JOIN " . TABLE_PREFIX . "icon AS icon ON(icon.iconid = post.iconid)") . "
					" . iif($vbulletin->options['avatarenabled'], "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)") . "
						$deljoin
					LEFT JOIN " . TABLE_PREFIX . "editlog AS editlog ON(editlog.postid = post.postid)
					LEFT JOIN " . TABLE_PREFIX . "postparsed AS postparsed ON(postparsed.postid = post.postid AND postparsed.styleid = " . intval(STYLEID) . " AND postparsed.languageid = " . intval(LANGUAGEID) . ")
					LEFT JOIN " . TABLE_PREFIX . "sigparsed AS sigparsed ON(sigparsed.userid = user.userid AND sigparsed.styleid = " . intval(STYLEID) . " AND sigparsed.languageid = " . intval(LANGUAGEID) . ")
					LEFT JOIN " . TABLE_PREFIX . "sigpic AS sigpic ON(sigpic.userid = post.userid)
					$hook_query_joins
					WHERE post.threadid = $threadinfo[threadid] AND " . (
						($lastviewed = $vbulletin->GPC['ajax_lastpost']) ?
							"post.dateline > $lastviewed AND (post.visible = 1 OR post.postid = $newpost[postid])" :
							"post.postid = $newpost[postid]"
						) . "
						$hook_query_where
					ORDER BY dateline
				");

				$postcount_query = $db->query_first("
					SELECT COUNT(*) AS count
					FROM " . TABLE_PREFIX . "post AS post
					WHERE threadid = $threadinfo[threadid]
						AND visible = 1
						AND dateline <= " . ($vbulletin->GPC['ajax_lastpost'] ? $vbulletin->GPC['ajax_lastpost'] : TIMENOW) . "
						AND postid <> $newpost[postid]
				");
				$postcount = $postcount_query['count'];

				// determine ignored users
				$ignore = array();
				if (trim($vbulletin->userinfo['ignorelist']))
				{
					$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
					foreach ($ignorelist AS $ignoreuserid)
					{
						$ignore["$ignoreuserid"] = 1;
					}
				}

				$see_deleted = ($forumperms & $vbulletin->bf_ugp_forumpermissions['canseedelnotice'] OR can_moderate($threadinfo['forumid']));

				$postbit_factory = new vB_Postbit_Factory();
				$postbit_factory->registry =& $vbulletin;
				$postbit_factory->forum =& $foruminfo;
				$postbit_factory->thread =& $thread;
				$postbit_factory->cache = array();
				$postbit_factory->bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

				$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
				$xml->add_group('postbits');

				while ($post = $db->fetch_array($posts))
				{
					if ($tachyuser = in_coventry($post['userid']) AND !can_moderate($thread['forumid']))
					{
						continue;
					}

					if ($tachyuser)
					{
						$fetchtype = 'post_global_ignore';
					}
					else if ($ignore["$post[userid]"])
					{
						$fetchtype = 'post_ignore';
					}
					else if ($post['visible'] == 2)
					{
						if (!$see_deleted)
						{
							continue;
						}
						$fetchtype = 'post_deleted';
					}
					else if ($post['visible'] == 0 AND !can_moderate($thread['forumid'], 'canmoderateposts'))
					{
						$fetchtype = 'auto_moderated';
					}
					else
					{
						$fetchtype = 'post';
					}

					if ($postorder)
					{
						$post['postcount'] = --$postcount;
					}
					else
					{
						$post['postcount'] = ++$postcount;
					}

					if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostattachment'] AND $vbulletin->userinfo['userid'] AND !empty($vbulletin->userinfo['attachmentextensions']))
					{
						require_once(DIR . '/packages/vbattach/attach.php');
						$attach = new vB_Attach_Display_Content($vbulletin, 'vBForum_Post');
						$attachments = $attach->fetch_postattach(0, $post['postid'], $postinfo['userid'], true, $attachid);
						$post['attachments'] = $attachments['byattachment'];
						$post['allattachments'] = $attachments['bycontent'][$post['postid']];
					}

					($hook = vBulletinHook::fetch_hook('showthread_postbit_create')) ? eval($hook) : false;

					if ($newpost['doublepost'])
					{
						$show['spacer'] = false; // Still needed ?
						$xml->add_tag('updatepost', $newpost['postid']);
					}

					$postbit_obj =& $postbit_factory->fetch_postbit($fetchtype);

					$xml->add_tag('postbit', process_replacement_vars($postbit_obj->construct_postbit($post)), array('postid' => $post['postid']));
				}
			}
			// ajax posts always mark the thread as read because any missed posts are retrieved as well
			mark_thread_read($threadinfo, $foruminfo, $vbulletin->userinfo['userid'], TIMENOW);

			// if post is not moderated, attempt to publish this new reply to user's Facebook feed
			if ($newpost['visible'] AND is_facebookenabled())
			{
				// If this is a cms comment post, and make the appropriate FB post if it is.
				if ($cms_comment_thread)
				{
					$url = vBCms_Route_Content::getURL(array('node' => $vbulletin->GPC['return_node']));
					$url = str_ireplace('&amp;', '&', $url);
					publishtofacebook_articlecomment($threadinfo['title'], $newpost['message'], create_full_url($url));
				}
				else // If not a cms comment, simply publish the new post to Facebook
				{
					if ($threadview < $threadinfo['lastpost'])
					{
						$fblink = fetch_seo_url('thread|js', $threadinfo, array('p' => $newpost['postid'], 'posted' => 1)) . "#post$newpost[postid]";
					}
					else
					{
						$fblink = fetch_seo_url('thread|js', $threadinfo, array('p' => $newpost['postid'])) . "#post$newpost[postid]";
					}
					publishtofacebook_newreply($threadinfo['title'], $newpost['message'], create_full_url($fblink));
				}
			}

			$xml->add_tag('time', TIMENOW);
			$xml->add_tag('newpostid', $newpostid);
			if ($cms_redirect)
			{
				$xml->add_tag('newrows',$postcount);
				$xml->add_tag('newposturl', $cms_url);
			}
			$xml->close_group();
			$xml->print_xml(true);

			// #############################################################################
			// #############################################################################
			// #############################################################################
		}
		else
		{
			// if this is a CMS article comment, perform the redirect back to the article
			if ($cms_comment_thread)
			{
				if (is_facebookenabled())
				{
					publishtofacebook_articlecomment($threadinfo['title'], $newpost['message'], create_full_url($vbulletin->url));
				}

				if ($cms_redirect)
				{
					exec_header_redirect($cms_url);
				}
			}

			if ($vbulletin->GPC['multiquoteempty'])
			{
				// setting cookies -- need to force a redirect on IIS because of
				// some issues with location-based redirects and set-cookie headers
				$forceredirect = (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);

				if ($vbulletin->GPC['multiquoteempty'] == 'only')
				{
					// remove all posts from this thread from the cookie, but leave all the others
					$vbulletin->input->clean_array_gpc('c', array(
						'vbulletin_multiquote' => TYPE_STR
					));
					$quote_postids = explode(',', $vbulletin->GPC['vbulletin_multiquote']);
					fetch_quotable_posts($quote_postids, $threadinfo['threadid'], $unquoted_post_count, $quoted_post_ids, 'only');

					$remaining = array_diff($quote_postids, $quoted_post_ids);
					setcookie('vbulletin_multiquote', implode(',', $remaining), 0, '/');
				}
				else if ($vbulletin->GPC['multiquoteempty'] == 'all')
				{
					// empty the cookie completely
					setcookie('vbulletin_multiquote', '', 0, '/');
				}
			}
			else
			{
				$forceredirect = false;
			}

			if ($newpost['visible'] OR can_moderate($foruminfo['forumid'], 'canmoderateposts'))
			{
				if ($threadview < $threadinfo['lastpost'])
				{
					$vbulletin->url = fetch_seo_url('thread', $threadinfo, array('p' => $newpost['postid'], 'posted' => 1)) . "#post$newpost[postid]";
				}
				else
				{
					$vbulletin->url = fetch_seo_url('thread', $threadinfo, array('p' => $newpost['postid'])) . "#post$newpost[postid]";
				}

				if (defined('VB_API') AND VB_API === true)
				{
					$show['threadid'] = $threadinfo['threadid'];
					$show['postid'] = $newpost['postid'];
				}

				// if post is not moderated, attempt to publish this new reply to user's Facebook feed
				if ($newpost['visible'] AND is_facebookenabled())
				{
					$fblink = str_ireplace('&amp;', '&', $vbulletin->url);
					publishtofacebook_newreply($threadinfo['title'], $newpost['message'], create_full_url($fblink));
				}

				($hook = vBulletinHook::fetch_hook('newreply_post_complete')) ? eval($hook) : false;
				print_standard_redirect('redirect_postthanks', true, $forceredirect);
			}
			else
			{
				$vbulletin->url = fetch_seo_url('forum', $foruminfo);
				($hook = vBulletinHook::fetch_hook('newreply_post_complete')) ? eval($hook) : false;
				print_standard_redirect('redirect_postthanks_moderate', true, true);
			}
		}

	} // end if
}

// ############################### start new reply ###############################
if ($_REQUEST['do'] == 'newreply')
{
	// falls down from preview post and has already been sent through htmlspecialchars() in build_new_post()
	$title = $newpost['title'];

	($hook = vBulletinHook::fetch_hook('newreply_form_start')) ? eval($hook) : false;

	// *********************************************************************
	// get options checks

	$posticons = construct_icons($newpost['iconid'], $foruminfo['allowicons']);

	if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostattachment'] AND $vbulletin->userinfo['userid'] AND !empty($vbulletin->userinfo['attachmentextensions']))
	{
		$values = "values[t]=$threadinfo[threadid]";
		require_once(DIR . '/packages/vbattach/attach.php');
		$attach = new vB_Attach_Display_Content($vbulletin, 'vBForum_Post');
		$attachmentoption = $attach->fetch_edit_attachments($posthash, $poststarttime, $postattach['bycontent'][0], 0, $values, $editorid, $attachcount);
		$contenttypeid = $attach->fetch_contenttypeid();
	}
	else
	{
		$attachmentoption = '';
		$contenttypeid = 0;
	}

	require_once(DIR . '/includes/functions_file.php');
	$attachinfo = fetch_attachmentinfo($posthash, $poststarttime, $contenttypeid, array('t' => $threadinfo['threadid']));

	$editorid = construct_edit_toolbar(
		$newpost['message'],
		0,
		$foruminfo['forumid'],
		iif($foruminfo['allowsmilies'], 1, 0),
		1,
		($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostattachment'] AND $vbulletin->userinfo['userid'] AND !empty($vbulletin->userinfo['attachmentextensions'])),
		'fe',
		'',
		$attachinfo,
		'forum',
		'vBForum_Post',
		0,
		$threadinfo['threadid'],
		$postpreview,
		true,
		'title'
	);

	// get rating options
	if ($foruminfo['allowratings'] AND ($forumperms & $vbulletin->bf_ugp_forumpermissions['canthreadrate']))
	{
		if ($rating = $db->query_first_slave("
			SELECT vote, threadrateid
			FROM " . TABLE_PREFIX . "threadrate
			WHERE userid = " . $vbulletin->userinfo['userid'] . "
				AND threadid = $threadinfo[threadid]
		"))
		{
			if ($vbulletin->options['votechange'])
			{
				$rate["$rating[vote]"] = ' ' . 'selected="selected"';
				$show['threadrating'] = true;
			}
			else
			{
				$show['threadrating'] = false;
			}
		}
		else
		{
			$show['threadrating'] = true;
		}
	}
	else
	{
		$show['threadrating'] = false;
	}

	// can this user open / close this thread?
	if (($threadinfo['postuserid'] AND $threadinfo['postuserid'] == $vbulletin->userinfo['userid'] AND $forumperms & $vbulletin->bf_ugp_forumpermissions['canopenclose']) OR can_moderate($threadinfo['forumid'], 'canopenclose'))
	{
		$show['openclose'] = true;
	}
	else
	{
		$show['openclose'] = false;
	}
	// can this user stick this thread?
	if (can_moderate($threadinfo['forumid'], 'canmanagethreads'))
	{
		$show['stickunstick'] = true;
	}
	else
	{
		$show['stickunstick'] = false;
	}
	if ($show['openclose'] OR $show['stickunstick'])
	{
		$show['closethread'] = iif($threadinfo['open'], true, false);
		$show['unstickthread'] = iif($threadinfo['sticky'], true, false);

		($hook = vBulletinHook::fetch_hook('newreply_form_threadmanage')) ? eval($hook) : false;

		$templater = vB_Template::create('newpost_threadmanage');
			$templater->register('checked', $checked);
		$threadmanagement = $templater->render();
	}
	else
	{
		$threadmanagement = '';
	}

	// Get subscribed thread folders
	// for now..
	if ($newpost['folderid'])
	{
		$folderid = $newpost['folderid'];
	}
	else
	{
		if ($threadinfo['issubscribed'])
		{
			$folderid = $threadinfo['folderid'];
		}
		else
		{
			$folderid = 0;
		}
	}
	$folders = unserialize($vbulletin->userinfo['subfolders']);

	// Don't show the folderjump if we only have one folder, would be redundant ;)
	if (sizeof($folders) > 1)
	{
		require_once(DIR . '/includes/functions_misc.php');
		$folderbits = construct_folder_jump(1, $folderid, false, $folders);
	}
	$show['subscribefolders'] = iif(!empty($folderbits), true, false);

	// get the checked option for auto subscription
	$emailchecked = fetch_emailchecked($threadinfo, $vbulletin->userinfo, $newpost);
	if ($emailchecked['9999'])
	{
		$emailchecked['0'] = $emailchecked['9999'];
		unset($emailchecked['9999']);
		$checked['subscribe'] = '';
	}
	else
	{
		$checked['subscribe'] = 'checked="checked"';
	}

	if ($foruminfo['allowhtml'])
	{
		if (!isset($htmlchecked))
		{
			$htmlchecked = array('on_nl2br' => 'selected="selected"');
		}
		$templater = vB_Template::create('newpost_html');
			$templater->register('htmlchecked', $htmlchecked);
		$htmloption = $templater->render();
	}

	// auto-parse URL
	if (!isset($checked['parseurl']))
	{
		$checked['parseurl'] = 'checked="checked"';
	}

	if ($vbulletin->userinfo['userid'] AND !$postpreview)
	{
		// signature
		if ($vbulletin->userinfo['signature'] != '')
		{
			$checked['signature'] = 'checked="checked"';
		}
		else
		{
			$checked['signature'] = '';
		}
	}

	// *********************************************************************
	// get thread review bits

	// get ignored users
	$ignore = array();
	$vbulletin->userinfo['ignorelist'] = trim($vbulletin->userinfo['ignorelist']);
	if ($vbulletin->userinfo['ignorelist'] != '')
	{
		$ignorelist = explode(' ', $vbulletin->userinfo['ignorelist']);
		foreach ($ignorelist AS $ignoreuserid)
		{
			$ignoreuserid = intval($ignoreuserid);
			if ($ignoreuserid)
			{
				$ignore["$ignoreuserid"] = 1;
			}
		}
	}
	if (!empty($ignore))
	{
		$ignoreduser = vB_Template::create('newreply_reviewbit_ignore')->render();
	}

	// get thread review
	$threadreviewbits = '';
	$postcounter = 0;

	if (($vbulletin->userinfo['maxposts'] != -1) AND ($vbulletin->userinfo['maxposts']))
	{
		$vbulletin->options['maxposts'] = $vbulletin->userinfo['maxposts'];
	}

	if ($Coventry = fetch_coventry('string'))
	{
		$globalignore = "AND post.userid NOT IN ($Coventry) ";
	}
	else
	{
		$globalignore = '';
	}

	require_once(DIR . '/includes/class_bbcode.php');
	$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());


	$posts = $db->query_read_slave("
		SELECT post.*, IF(post.userid = 0, post.username, user.username) AS username
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = post.userid)
		WHERE post.visible = 1
			$globalignore
			AND post.threadid = $threadinfo[threadid]
		ORDER BY dateline DESC
		LIMIT " . ($vbulletin->options['maxposts'] + 1)
	);
	while ($post = $db->fetch_array($posts))
	{
		if ($postcounter++ < $vbulletin->options['maxposts'])
		{
			exec_switch_bg();
			$posttime = vbdate($vbulletin->options['timeformat'], $post['dateline']);
			$postdate = vbdate($vbulletin->options['dateformat'], $post['dateline'], 1);
			$username = $post['username'];

			if ($ignore["$post[userid]"])
			{
				$reviewmessage = $ignoreduser;
			}
			else
			{
				$reviewmessage = $bbcode_parser->parse($post['pagetext'], $foruminfo['forumid'], $post['allowsmilie']);
			}

			// do word wrap
			$reviewtitle = ($vbulletin->options['wordwrap'] ? fetch_word_wrapped_string($post['title']) : '');
			$reviewtitle = fetch_censored_text($reviewtitle);

			($hook = vBulletinHook::fetch_hook('newreply_form_reviewbit')) ? eval($hook) : false;
			$templater = vB_Template::create('newreply_reviewbit');
				$templater->register('post', $post);
				$templater->register('postdate', $postdate);
				$templater->register('posttime', $posttime);
				$templater->register('reviewmessage', $reviewmessage);
				$templater->register('reviewtitle', $reviewtitle);
				$templater->register('username', $username);

			$threadreviewbits .= $templater->render();
		}
		else
		{
			break;
		}
	}
	if ($db->num_rows($posts) > $vbulletin->options['maxposts'])
	{
		$show['reviewmore'] = true;
	}
	else
	{
		$show['reviewmore'] = false;
	}

	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	if (fetch_require_hvcheck('post'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verification =& vB_HumanVerify::fetch_library($vbulletin);
		$human_verify = $verification->output_token();
	}
	else
	{
		$human_verify = '';
	}

	// *********************************************************************
	// finish the page

	construct_forum_rules($foruminfo, $forumperms);

	// draw nav bar
	$navbits = array();
	$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];
	$parentlist = array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3)));
	foreach ($parentlist AS $forumID)
	{
		$forumTitle = $vbulletin->forumcache["$forumID"]['title'];
		$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
	}
	if ($postid)
	{
		$navbits[fetch_seo_url('thread', $threadinfo, array('p' => $postid)) .  "#post$postid"] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];
	}
	else
	{
		$navbits[fetch_seo_url('thread', $threadinfo)] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];
	}
	$navbits[''] = $vbphrase['reply_to_thread'];

	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	$show['signaturecheckbox'] = ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature'] AND $vbulletin->userinfo['signature']);
	$show['parseurl'] = (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_URL) AND $foruminfo['allowbbcode']);
	$show['misc_options'] = ($show['signaturecheckbox'] OR $show['parseurl'] OR !empty($disablesmiliesoption));
	$show['additional_options'] = ($show['misc_options'] OR !empty($attachmentoption) OR $show['member'] OR $show['threadrating'] OR !empty($threadmanagement));
	$show['lightbox'] = ($vbulletin->options['lightboxenabled'] AND $vbulletin->options['usepopups']);

	// display publish to Facebook checkbox in quick editor?
	$guestuser = array(
		'userid'      => 0,
		'usergroupid' => 0,
	);
	cache_permissions($guestuser);

	if (
		$guestuser['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']
			AND
		$guestuser['forumpermissions']["$foruminfo[forumid]"] & $vbulletin->bf_ugp_forumpermissions['canview']
			AND
		$guestuser['forumpermissions']["$foruminfo[forumid]"] & $vbulletin->bf_ugp_forumpermissions['canviewthreads']
			AND
		($guestuser['forumpermissions']["$foruminfo[forumid]"] & $vbulletin->bf_ugp_forumpermissions['canviewothers'] OR $threadinfo['postuserid'] == 0)
			AND
		is_facebookenabled()
	)
	{
		$fbpublishcheckbox = construct_fbpublishcheckbox();
	}

	($hook = vBulletinHook::fetch_hook('newreply_form_complete')) ? eval($hook) : false;

	// complete
	$templater = vB_Template::create('newreply');
		$templater->register_page_templates();
		$templater->register('attachmentoption', $attachmentoption);
		$templater->register('checked', $checked);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('editorid', $editorid);
		$templater->register('emailchecked', $emailchecked);
		$templater->register('folderbits', $folderbits);
		$templater->register('forumrules', $forumrules);
		$templater->register('human_verify', $human_verify);
		$templater->register('messagearea', $messagearea);
		$templater->register('multiquote_empty', $multiquote_empty);
		$templater->register('navbar', $navbar);
		$templater->register('onload', $onload);
		$templater->register('posthash', $posthash);
		$templater->register('posticons', $posticons);
		$templater->register('postid', $postid);
		$templater->register('postpreview', $postpreview);
		$templater->register('poststarttime', $poststarttime);
		$templater->register('rate', $rate);
		$templater->register('selectedicon', $selectedicon);
		$templater->register('spacer_close', $spacer_close);
		$templater->register('spacer_open', $spacer_open);
		$templater->register('specifiedpost', $specifiedpost);
		$templater->register('threadid', $threadid);
		$templater->register('threadinfo', $threadinfo);
		$templater->register('threadmanagement', $threadmanagement);
		$templater->register('threadreviewbits', $threadreviewbits);
		$templater->register('title', $title);
		$templater->register('unquoted_post_count', $unquoted_post_count);
		$templater->register('usernamecode', $usernamecode);
		$templater->register('return_node', $vbulletin->GPC['return_node']);
		$templater->register('htmloption', $htmloption);
		$templater->register('fbpublishcheckbox', $fbpublishcheckbox);
	print_output($templater->render());

}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 62620 $
|| ####################################################################
\*======================================================================*/
