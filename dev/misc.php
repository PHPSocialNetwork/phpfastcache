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
define('THIS_SCRIPT', 'misc');
define('CSRF_PROTECTION', true);
if (in_array($_GET['do'], array('whoposted', 'buddylist', 'getsmilies')))
{
	define('NOPMPOPUP', 1);
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('fronthelp', 'register');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'buddylist' => array(
		'BUDDYLIST',
		'buddylistbit'
	),
	'whoposted' => array(
		'WHOPOSTED',
		'whopostedbit'
	),
	'showattachments' => array(
		'ATTACHMENTS',
		'attachmentbit',
	),
	'bbcode' => array(
		'help_bbcodes',
		'help_bbcodes_bbcode',
		'help_bbcodes_link',
		'bbcode_code',
		'bbcode_html',
		'bbcode_php',
		'bbcode_quote',
		'bbcode_video',
	),
	'getsmilies' => array(
		'smiliepopup',
		'smiliepopup_category',
		'smiliepopup_row',
		'smiliepopup_smilie',
		'smiliepopup_straggler'
	),
	'showsmilies' => array(
		'help_smilies',
		'help_smilies_smilie',
		'help_smilies_category',
	),
	'showrules' => array(
		'help_rules',
	)
);
$actiontemplates['none'] =& $actiontemplates['showsmilies'];

// allows proper template caching for the default action (showsmilies) if no valid action is specified
if (!empty($_REQUEST['do']) AND !isset($actiontemplates["$_REQUEST[do]"]))
{
	$actiontemplates["$_REQUEST[do]"] =& $actiontemplates['showsmilies'];
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

($hook = vBulletinHook::fetch_hook('misc_start')) ? eval($hook) : false;

// ############################### start buddylist ###############################
if ($_REQUEST['do'] == 'buddylist')
{
	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	($hook = vBulletinHook::fetch_hook('misc_buddylist_start')) ? eval($hook) : false;

	$buddies =& $vbulletin->input->clean_gpc('r', 'buddies', TYPE_STR);

	$datecut = TIMENOW - $vbulletin->options['cookietimeout'];

	$buddys = $db->query_read_slave("
		SELECT
		user.username, (user.options & " . $vbulletin->bf_misc_useroptions['invisible'] . ") AS invisible, user.userid, session.lastactivity
		FROM " . TABLE_PREFIX . "userlist AS userlist
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = userlist.relationid)
		LEFT JOIN " . TABLE_PREFIX . "session AS session ON(session.userid = user.userid)
		WHERE userlist.userid = {$vbulletin->userinfo['userid']} AND userlist.relationid = user.userid AND type = 'buddy'
		ORDER BY username ASC, session.lastactivity DESC
	");

	$onlineusers = '';
	$offlineusers = '';
	$newonlineusers = '';
	$newusersound = '';
	$lastonline = array();

	if (isset($buddies))
	{
		$buddies = urldecode($buddies);
		$lastonline = explode(' ', $buddies);
	}
	$buddies = '0 ';
	$show['playsound'] = false;

	require_once(DIR . '/includes/functions_bigthree.php');
	while ($buddy = $db->fetch_array($buddys))
	{
		if ($doneuser["$buddy[userid]"])
		{
			continue;
		}

		$doneuser["$buddy[userid]"] = true;

		if ($onlineresult = fetch_online_status($buddy))
		{
			if ($onlineresult == 1)
			{
				$buddy['statusicon'] = 'online';
			}
			else
			{
				$buddy['statusicon'] = 'invisible';
			}
			$buddies .= $buddy['userid'] . ' ';
		}
		else
		{
			$buddy['statusicon'] = 'offline';
		}

		$show['highlightuser'] = false;

		($hook = vBulletinHook::fetch_hook('misc_buddylist_bit')) ? eval($hook) : false;

		if ($buddy['statusicon'] != 'offline')
		{
			if (!in_array($buddy['userid'], $lastonline) AND !empty($lastonline))
			{
				$show['playsound'] = true;
				$show['highlightuser'] = true;
				// add name to top of list
				$templater = vB_Template::create('buddylistbit');
					$templater->register('buddy', $buddy);
				$newonlineusers .= $templater->render();
			}
			else
			{
				$templater = vB_Template::create('buddylistbit');
					$templater->register('buddy', $buddy);
				$onlineusers .= $templater->render();
			}
		}
		else
		{
			$templater = vB_Template::create('buddylistbit');
				$templater->register('buddy', $buddy);
			$offlineusers .= $templater->render();
		}
	}

	$onlineusers = $newonlineusers . $onlineusers;

	$buddies = urlencode(trim($buddies));

	($hook = vBulletinHook::fetch_hook('misc_buddylist_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('BUDDYLIST');
		$templater->register_page_templates();
		$templater->register('buddies', $buddies);
		$templater->register('offlineusers', $offlineusers);
		$templater->register('onlineusers', $onlineusers);
	print_output($templater->render());
}

// ############################### start who posted ###############################
if ($_REQUEST['do'] == 'whoposted')
{
	if (!$threadinfo['threadid'] OR $threadinfo['isdeleted'] OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
	}

	($hook = vBulletinHook::fetch_hook('misc_whoposted_start')) ? eval($hook) : false;

	$forumperms = fetch_permissions($threadinfo['forumid']);
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
	{
		print_no_permission();
	}
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($threadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR !$vbulletin->userinfo['userid']))
	{
		print_no_permission();
	}

	$posts = $db->query_read_slave("
		SELECT COUNT(postid) AS posts, IF(post.userid = 0, post.username, user.userid) as memberid, user.userid,
			post.username AS postuser, user.username
		FROM " . TABLE_PREFIX . "post AS post
		LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid)
		WHERE threadid = $threadinfo[threadid]
			AND visible = 1
		GROUP BY memberid
		ORDER BY posts DESC
	");

	$totalposts = 0;
	$posters = '';
	if ($db->num_rows($posts))
	{
		require_once(DIR . '/includes/functions_bigthree.php');
		while ($post = $db->fetch_array($posts))
		{
			// hide users in Coventry
			$ast = '';
			if (in_coventry($post['userid']) AND !can_moderate($threadinfo['forumid']))
			{
				continue;
			}

			exec_switch_bg();
			if ($post['username'] == '')
			{
				$post['username'] = $post['postuser'];
			}
			$post['username'] .=  $ast;
			$totalposts += $post['posts'];
			$post['posts'] = vb_number_format($post['posts']);
			$show['memberlink'] = iif ($post['userid'], true, false);
			$templater = vB_Template::create('whopostedbit');
				$templater->register('bgclass', $bgclass);
				$templater->register('post', $post);
				$templater->register('threadinfo', $threadinfo);
			$posters .= $templater->render();
		}
		$totalposts = vb_number_format($totalposts);

		($hook = vBulletinHook::fetch_hook('misc_whoposted_complete')) ? eval($hook) : false;

		$templater = vB_Template::create('WHOPOSTED');
			$templater->register_page_templates();
			$templater->register('posters', $posters);
			$templater->register('threadinfo', $threadinfo);
			$templater->register('totalposts', $totalposts);
		print_output($templater->render());
	}
	else
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
	}
}

// ############################### start show attachments ###############################
if ($_REQUEST['do'] == 'showattachments')
{
	if (!$threadinfo['threadid'] OR $threadinfo['isdeleted'] OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
	}

	($hook = vBulletinHook::fetch_hook('misc_showattachments_start')) ? eval($hook) : false;

	$forumperms = fetch_permissions($threadinfo['forumid']);
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
	{
		print_no_permission();
	}
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($threadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR !$vbulletin->userinfo['userid']))
	{
		print_no_permission();
	}

	$types = vB_Types::instance();
	$contenttypeid = $types->getContentTypeID('vBForum_Post');

	$attachs = $db->query_read_slave("
		SELECT a.*, fd.filesize
		FROM " . TABLE_PREFIX . "post AS post
		INNER JOIN " . TABLE_PREFIX . "attachment AS a ON (a.contentid = post.postid AND a.state = 'visible')
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
		WHERE
			threadid = $threadinfo[threadid]
				AND
			post.visible = 1
				AND
			a.contenttypeid = $contenttypeid
		ORDER BY a.filename DESC
	");

	if ($db->num_rows($attachs))
	{
		require_once(DIR . '/includes/functions_bigthree.php');
		while ($attachment = $db->fetch_array($attachs))
		{
			// hide users in Coventry
			$ast = '';
			if (in_coventry($attachment['userid']) AND !can_moderate($threadinfo['forumid']))
			{
				continue;
			}

			$attachment['filename'] = fetch_censored_text(htmlspecialchars_uni($attachment['filename']));
			$attachment['attachmentextension'] = strtolower(file_extension($attachment['filename']));
			$attachment['filesize'] = vb_number_format($attachment['filesize'], 1, true);

			exec_switch_bg();

			$templater = vB_Template::create('attachmentbit');
				$templater->register('attachment', $attachment);
				$templater->register('bgclass', $bgclass);
			$attachments .= $templater->render();
		}

		($hook = vBulletinHook::fetch_hook('misc_showattachments_complete')) ? eval($hook) : false;

		$templater = vB_Template::create('ATTACHMENTS');
			$templater->register_page_templates();
			$templater->register('attachments', $attachments);
			$templater->register('threadinfo', $threadinfo);
			$templater->register('totalattachments', $db->num_rows($attachs));
		print_output($templater->render());
	}
	else
	{
		eval(standard_error(fetch_error('noattachments')));
	}
}

// ############################### start bbcode ###############################
if ($_REQUEST['do'] == 'bbcode')
{

	($hook = vBulletinHook::fetch_hook('misc_bbcode_start')) ? eval($hook) : false;
	require_once(DIR . '/includes/class_bbcode.php');

	$show['bbcodebasic'] = ($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_BASIC) ? true : false;
	$show['bbcodecolor'] = ($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_COLOR) ? true : false;
	$show['bbcodesize'] = ($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_SIZE) ? true : false;
	$show['bbcodefont'] = ($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_FONT) ? true : false;
	$show['bbcodealign'] = ($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_ALIGN) ? true : false;
	$show['bbcodelist'] = ($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_LIST) ? true : false;
	$show['bbcodeurl'] = ($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_URL) ? true : false;
	$show['bbcodecode'] = ($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_CODE) ? true : false;
	$show['bbcodephp'] = ($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_PHP) ? true : false;
	$show['bbcodehtml'] = ($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_HTML) ? true : false;
	$show['bbcodesigpic'] = ($permissions['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic']) ? true : false;

	$template['bbcodebits'] = '';

	$specialbbcode[] = array();

	$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	$bbcodes = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "bbcode ORDER BY bbcodetag, twoparams");
	while ($bbcode = $db->fetch_array($bbcodes))
	{
		$bbcode['output'] = $bbcode_parser->do_parse($bbcode['bbcodeexample'], false, false, true, false, true);

		$bbcode['bbcodeexample'] = htmlspecialchars_uni($bbcode['bbcodeexample']);
		if ($bbcode['twoparams'])
		{
			$bbcode['tag'] = '[' . $bbcode['bbcodetag'] . '=<span class="highlight">' . $vbphrase['option'] . '</span>]<span class="highlight">' . $vbphrase['value'] . '</span>[/' . $bbcode['bbcodetag'] . ']';
		}
		else
		{
			$bbcode['tag'] = '[' . $bbcode['bbcodetag'] . ']<span class="highlight">' . $vbphrase['value'] . '</span>[/' . $bbcode['bbcodetag'] . ']';
		}

		($hook = vBulletinHook::fetch_hook('misc_bbcode_bit')) ? eval($hook) : false;

		$templater = vB_Template::create('help_bbcodes_bbcode');
			$templater->register('bbcode', $bbcode);
		$template['bbcodebits'] .= $templater->render();
		$templater = vB_Template::create('help_bbcodes_link');
			$templater->register('bbcode', $bbcode);
		$template['bbcodelinks'] .= $templater->render();
	}

	$navbits = construct_navbits(array(
		'faq.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['faq'],
		'' => $vbphrase['bbcode_list']
	));

	$show['iewidthfix'] = (is_browser('ie') AND !(is_browser('ie', 6)));

	$vbulletin->options['allowhtml'] = false;
	$vbulletin->options['allowbbcode'] = true;

	// ### CODE tag
	$specialbbcode['code'] = $bbcode_parser->parse("[code]<script type=\"text/javascript\">\n<!--\n\talert(\"Hello world!\");\n//-->\n</script>[/code]", 0, false);

	// ### HTML Tag
	$specialbbcode['html'] = $bbcode_parser->parse("[html]<img src=\"image.gif\" alt=\"image\" />\n<a href=\"testing.html\" target=\"_blank\">Testing</a>[/html]", 0, false);

	// ### PHP Tag
	$specialbbcode['php'] = $bbcode_parser->parse("[php]\$myvar = 'Hello World!';\nfor (\$i = 0; \$i < 10; \$i++)\n{\n\techo \$myvar . \"\\n\";\n}[/php]", 0, false);

	// ### Quote Tag
	$specialbbcode['quote1'] = $bbcode_parser->parse("[quote]Lorem ipsum dolor sit amet[/quote]", 0, false);
	$specialbbcode['quote2'] = $bbcode_parser->parse("[quote=John Doe]Lorem ipsum dolor sit amet[/quote]", 0, false);

	$max_post = $db->query_first_slave("SELECT MAX(postid) AS maxpostid FROM " . TABLE_PREFIX . "post");
	$max_post['maxpostid'] = intval($max_post['maxpostid']);
	$specialbbcode['quote3'] = $bbcode_parser->parse("[quote=John Doe;$max_post[maxpostid]]Lorem ipsum dolor sit amet[/quote]", 0, false);

	// ### Special URL for Image
	if (preg_match('#^[a-z0-9]+://#si', vB_Template_Runtime::fetchStyleVar('imgdir_statusicon')))
	{
		$statusicon_dir = vB_Template_Runtime::fetchStyleVar('imgdir_statusicon');
	}
	else
	{
		$statusicon_dir = $vbulletin->options['bburl'] . '/' . vB_Template_Runtime::fetchStyleVar('imgdir_statusicon');
	}

	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('misc_bbcode_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('help_bbcodes');
		$templater->register_page_templates();
		$templater->register('i', $i);
		$templater->register('max_post', $max_post);
		$templater->register('myvar', $myvar);
		$templater->register('navbar', $navbar);
		$templater->register('specialbbcode', $specialbbcode);
		$templater->register('statusicon_dir', $statusicon_dir);
		$templater->register('template', $template);
	print_output($templater->render());
}

// ############################### Popup Smilies for vbCode ################
if ($_REQUEST['do'] == 'getsmilies')
{
	$editorid = $vbulletin->input->clean_gpc('r', 'editorid', TYPE_NOHTML);
	$editorid = preg_replace('#[^a-z0-9_]#i', '', $editorid);

	($hook = vBulletinHook::fetch_hook('misc_smiliespopup_start')) ? eval($hook) : false;

	$result = $db->query_read_slave("
		SELECT smilietext AS text, smiliepath AS path, smilie.title, smilieid,
			imagecategory.title AS category
		FROM " . TABLE_PREFIX . "smilie AS smilie
		LEFT JOIN " . TABLE_PREFIX . "imagecategory AS imagecategory USING(imagecategoryid)
		ORDER BY imagecategory.displayorder, imagecategory.title, smilie.displayorder
	");

	$categories = array();
	while ($smilie = $db->fetch_array($result))
	{
		$categories[$smilie['category']][] = $smilie;
	}

	$categorybits = '';
	foreach ($categories AS $category => $smilies)
	{
		($hook = vBulletinHook::fetch_hook('misc_smiliespopup_category')) ? eval($hook) : false;

		$smiliebits = '';
		foreach ($smilies AS $smilie)
		{
			($hook = vBulletinHook::fetch_hook('misc_smiliespopup_smilie')) ? eval($hook) : false;

			$smilie['js'] = addslashes_js($smilie['text']);

			$templater = vB_Template::create('smiliepopup_smilie');
				$templater->quickRegister($smilie);
			$smiliebits .= $templater->render();
		}

		$templater = vB_Template::create('smiliepopup_category');
			$templater->register('title', $category);
			$templater->register('smiliebits', $smiliebits);
		$categorybits .= $templater->render();
	}

	($hook = vBulletinHook::fetch_hook('misc_smiliespopup_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('smiliepopup');
		$templater->register_page_templates();
		$templater->register('editorid', $editorid);
		$templater->register('categorybits', $categorybits);
		$templater->register('wysiwyg', $wysiwyg);
	print_output($templater->render());

}

$vbulletin->input->clean_gpc('r', 'template', TYPE_NOHTML);

// ############################### start any page ###############################
if ($_REQUEST['do'] == 'debug_page' AND $vbulletin->GPC['template'] != '')
{
	if (!$vbulletin->debug)
	{
		print_no_permission();
	}

	$template_name = preg_replace('#[^a-z0-9_]#i', '', $vbulletin->GPC['template']);

	$navbits = construct_navbits(array('' => $template_name));
	$navbar = render_navbar_template($navbits);

	$templater = vB_Template::create($template_name);
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
	print_output($templater->render());
}

if ($_REQUEST['do'] == 'page' AND $vbulletin->GPC['template'] != '')
{
	$template_name = preg_replace('#[^a-z0-9_]#i', '', $vbulletin->GPC['template']);

	$navbits = construct_navbits(array('' => $template_name));
	$navbar = render_navbar_template($navbits);

	$templater = vB_Template::create('custom_' . $template_name);
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('pagetitle', $pagetitle);
	print_output($templater->render());
}

if ($_REQUEST['do'] == 'generic')
{

	$navbits = construct_navbits(array('' => 'Generic Shell'));
	$navbar = render_navbar_template($navbits);

	$templater = vB_Template::create('GENERIC_SHELL');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('pagetitle', 'Generic Shell');
	print_output($templater->render());
}

// ############################### start show rules ###############################
if ($_REQUEST['do'] == 'showrules')
{
	$navbits = construct_navbits(array(
		'' => $vbphrase['forum_rules']
	));

	$navbar = render_navbar_template($navbits);
	$templater = vB_Template::create('help_rules');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
	print_output($templater->render());
}

$_REQUEST['do'] = 'showsmilies';

// ############################### start show smilies ###############################
if ($_REQUEST['do'] == 'showsmilies')
{
	$smiliebits = '';

	($hook = vBulletinHook::fetch_hook('misc_smilieslist_start')) ? eval($hook) : false;

	$smilies = $db->query_read_slave("
		SELECT smilietext,smiliepath,smilie.title,imagecategory.title AS category
		FROM " . TABLE_PREFIX . "smilie AS smilie
		LEFT JOIN " . TABLE_PREFIX . "imagecategory AS imagecategory USING(imagecategoryid)
		ORDER BY imagecategory.displayorder, imagecategory.title, smilie.displayorder
	");

	while ($smilie = $db->fetch_array($smilies))
	{
		$smilie['title'] = htmlspecialchars_uni($smilie['title']);

		if ($smilie['category'] != $lastcat)
		{
			($hook = vBulletinHook::fetch_hook('misc_smilieslist_category')) ? eval($hook) : false;

			$templater = vB_Template::create('help_smilies_category');
				$templater->register('smilie', $smilie);
			$smiliebits .= $templater->render();
		}
		exec_switch_bg();

		($hook = vBulletinHook::fetch_hook('misc_smilieslist_smilie')) ? eval($hook) : false;

		$templater = vB_Template::create('help_smilies_smilie');
			$templater->register('bgclass', $bgclass);
			$templater->register('smilie', $smilie);
		$smiliebits .= $templater->render();
		$lastcat = $smilie['category'];
	}

	$navbits = construct_navbits(array(
		'faq.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['faq'],
		'' => $vbphrase['smilie_list']
	));

	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('misc_smilieslist_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('help_smilies');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('smiliebits', $smiliebits);
	print_output($templater->render());
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 63184 $
|| ####################################################################
\*======================================================================*/
