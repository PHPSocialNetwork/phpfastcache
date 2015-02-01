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
define('THIS_SCRIPT', 'usernote');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('posting', 'postbit', 'reputationlevel');

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'forumrules',
	'newpost_usernamecode'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'none' => array(
		'im_aim',
		'im_icq',
		'im_yahoo',
		'im_msn',
		'im_skype',
		'postbit',
		'postbit_wrapper',
		'postbit_onlinestatus',
		'usernote_nonotes',
		'bbcode_code',
		'bbcode_html',
		'bbcode_php',
		'bbcode_quote',
		'bbcode_video',
		'usernote',
	),
	'newnote' => array(
		'usernote_note'
	)
);

$actiontemplates['viewuser'] =& $actiontemplates['none'];
$actiontemplates['editnote'] =& $actiontemplates['newnote'];

// get the editor templates if required
if (in_array($_REQUEST['do'], array('newnote', 'editnote')))
{
	define('GET_EDIT_TEMPLATES', true);
}

// ####################### PRE-BACK-END ACTIONS ##########################
function parse_usernote_bbcode($bbcode, $smilies = true)
{
	global $vbulletin;

	require_once(DIR . '/includes/class_bbcode.php');
	$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
	return $bbcode_parser->parse($bbcode, 'usernote', $smilies);
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/functions_editor.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$vbulletin->input->clean_array_gpc('r', array(
	'usernoteid'	=> TYPE_UINT,
	'userid'		=> TYPE_UINT,
));

if ($vbulletin->GPC['usernoteid'])
{
	$noteinfo = verify_id('usernote', $vbulletin->GPC['usernoteid'], 1, 1);
	$userinfo = fetch_userinfo($noteinfo['userid']);
}
else
{
	$userinfo = verify_id('user', $vbulletin->GPC['userid'], 1, 1);
}

$userperms = cache_permissions($userinfo, false);

if (!($userperms['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canbeusernoted']))
{
	eval(standard_error(fetch_error('usernotenotallowed')));
}

$viewself = ($userinfo['userid'] == $vbulletin->userinfo['userid']) ? true : false;
$canviewown = ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewownusernotes']) ? true : false;
$canviewothers = ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewothersusernotes']) ? true : false;
$canpostown = ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canpostownusernotes']) ? true : false;
$canpostothers = ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canpostothersusernotes']) ? true : false;
$canview = (($viewself AND $canviewown) OR (!$viewself AND $canviewothers)) ? true : false;
$canpost = (($viewself AND $canpostown) OR (!$viewself AND $canpostothers)) ? true : false;

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'viewuser';
}

$navpopup = array(
	'id'    => 'postlist_navpopup',
	'title' => construct_phrase($vbphrase['user_notes_for_x'], $userinfo['username']),
	'link'  => 'usernote.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]",
);

construct_quick_nav($navpopup);

$bbcodeon = ($vbulletin->options['unallowvbcode'] ? $vbphrase['on'] : $vbphrase['off']);
$imgcodeon = ($vbulletin->options['unallowimg'] ? $vbphrase['on'] : $vbphrase['off']);
$videocodeon = ($vbulletin->options['unallowvideo'] ? $vbphrase['on'] : $vbphrase['off']);
$htmlcodeon = ($vbulletin->options['unallowhtml'] ? $vbphrase['on'] : $vbphrase['off']);
$smilieson = ($vbulletin->options['unallowsmilies'] ? $vbphrase['on'] : $vbphrase['off']);

// only show posting code allowances in forum rules template
$show['codeonly'] = true;

($hook = vBulletinHook::fetch_hook('usernote_start')) ? eval($hook) : false;

$templater = vB_Template::create('forumrules');
	$templater->register('bbcodeon', $bbcodeon);
	$templater->register('can', $can);
	$templater->register('htmlcodeon', $htmlcodeon);
	$templater->register('imgcodeon', $imgcodeon);
	$templater->register('videocodeon', $videocodeon);
	$templater->register('smilieson', $smilieson);
$forumrules = $templater->render();

$usernamecode = vB_Template::create('newpost_usernamecode')->render();

// ########################### Delete Note #######################################
if ($_POST['do'] == 'deletenote')
{
	if (!$canview)
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'deletenotechecked'		=> TYPE_BOOL,
	));

	if ($noteinfo['posterid'] == $vbulletin->userinfo['userid'] AND $permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['caneditownusernotes'])
	{
		// User has permissions to edit any notes that have posted no matter what the other manage permissions are set to..
	}
	else
	{
		if ($viewself AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canmanageownusernotes']))
		{
			print_no_permission();
		}
		else if (!$viewself AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canmanageothersusernotes']))
		{
			print_no_permission();
		}
	}

	($hook = vBulletinHook::fetch_hook('usernote_delete')) ? eval($hook) : false;

	if ($vbulletin->GPC['deletenotechecked'])
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "usernote
			WHERE usernoteid = $noteinfo[usernoteid]
		");
		$vbulletin->url = 'usernote.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]";
		print_standard_redirect(array('redirect_deleteusernote',$userinfo['username']));  
	}
	else
	{
		$vbulletin->url = 'usernote.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]";
		print_standard_redirect(array('redirect_nodeletenote',$userinfo['username']));  
	}
}

// ############################### Start Edit User Note ##########################
if ($_REQUEST['do'] == 'editnote')
{
	if (!$canview)
	{
		print_no_permission();
	}

	if ($noteinfo['posterid'] == $vbulletin->userinfo['userid'] AND $permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['caneditownusernotes'])
	{
		// User has permissions to edit any notes that have posted no matter what the other manage permissions are set to..
	}
	else
	{
		if ($viewself AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canmanageownusernotes']))
		{
			print_no_permission();
		}
		else if (!$viewself AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canmanageothersusernotes']))
		{
			print_no_permission();
		}
	}

	$checked = array();

	$checked['parseurl'] = 'checked="checked"';
	$checked['disablesmilies'] = iif($noteinfo['allowsmilies'], '', 'checked="checked"');
	if ($vbulletin->options['unallowsmilies'] == 1)
	{
		$templater = vB_Template::create('newpost_disablesmiliesoption');
			$templater->register('checked', $checked);
		$disablesmiliesoption = $templater->render();
	}

	// include useful functions
	require_once(DIR . '/includes/functions_newpost.php');
	$editorid = construct_edit_toolbar(
		htmlspecialchars_uni($noteinfo['message']),
		0,
		'usernote',
		true,
		true,
		false,
		'fe',
		'',
		array(),
		'content',
		'vBForum_UserNote',
		$noteinfo['usernoteid'],
		0,
		false,
		true,
		'titlefield'
	);

	$show['editnote'] = true;

	// generate navbar
	$navbits = array(
		fetch_seo_url('member', $userinfo) => $vbphrase['view_profile'],
		'usernote.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]" => construct_phrase($vbphrase['user_notes_for_x'], $userinfo['username']),
		$vbphrase['edit_user_note']
	);

	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	$show['parseurl'] = $vbulletin->options['unallowvbcode'];
	$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));

	($hook = vBulletinHook::fetch_hook('usernote_edit')) ? eval($hook) : false;

	$templater = vB_Template::create('usernote_note');
		$templater->register_page_templates();
		$templater->register('checked', $checked);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('editorid', $editorid);
		$templater->register('forumjump', $forumjump);
		$templater->register('forumrules', $forumrules);
		$templater->register('messagearea', $messagearea);
		$templater->register('navbar', $navbar);
		$templater->register('noteinfo', $noteinfo);
		$templater->register('onload', $onload);
		$templater->register('userinfo', $userinfo);
		$templater->register('usernamecode', $usernamecode);
	print_output($templater->render());
}

// ############################### Add/Update User Note ################################
if ($_POST['do'] == 'donote')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'disablesmilies' => TYPE_BOOL,
		'title'          => TYPE_NOHTML,
		'message'        => TYPE_STR,
		'preview'        => TYPE_STR,
		'parseurl'       => TYPE_BOOL,
		'wysiwyg'        => TYPE_BOOL,
	));

	if ($noteinfo['usernoteid']) // existing note => edit
	{
		if (!$canview)
		{
			print_no_permission();
		}

		if ($noteinfo['posterid'] == $vbulletin->userinfo['userid'] AND $permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['caneditownusernotes'])
		{
			// User has permissions to edit any notes that have posted no matter what the other manage permissions are set to..
		}
		else
		{
			if ($viewself AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canmanageownusernotes']))
			{
				print_no_permission();
			}
			else if (!$viewself AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canmanageothersusernotes']))
			{
				print_no_permission();
			}
		}
	}
	else // new note
	{
		if (!$canpost)
		{
			print_no_permission();
		}
	}

	$allowsmilies = iif($vbulletin->GPC['disablesmilies'], 0, 1);
	$preview = iif($vbulletin->GPC['preview'] != '', 1, 0);

	// include useful functions
	require_once(DIR . '/includes/functions_newpost.php');

	// unwysiwygify the incoming data
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$vbulletin->GPC['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $vbulletin->options['unallowhtml']);
	}

	if (empty($vbulletin->GPC['message']))
	{
		eval(standard_error(fetch_error('nosubject')));
	}

	$vbulletin->GPC['title'] = fetch_censored_text($vbulletin->GPC['title']);
	if ($vbulletin->options['wordwrap'] != 0)
	{
		$vbulletin->GPC['title'] = fetch_word_wrapped_string($vbulletin->GPC['title']);
	}

	// remove all caps subjects
	$vbulletin->GPC['title'] = fetch_no_shouting_text($vbulletin->GPC['title']);

	$vbulletin->GPC['message'] = fetch_censored_text($vbulletin->GPC['message']);
	if ($vbulletin->GPC['parseurl'] AND $vbulletin->options['unallowvbcode'])
	{
		$vbulletin->GPC['message'] = convert_url_to_bbcode($vbulletin->GPC['message']);
	}
	// remove sessionhash from urls:
	$vbulletin->GPC['message'] = preg_replace('/(s|sessionhash)=[a-z0-9]{32}&{0,1}/', '' , $vbulletin->GPC['message']);
	$vbulletin->GPC['message'] = fetch_no_shouting_text($vbulletin->GPC['message']);
	if (vbstrlen($vbulletin->GPC['message']) > $vbulletin->options['postmaxchars'] AND $vbulletin->options['postmaxchars'] != 0)
	{
		eval(standard_error(fetch_error('toolong', $postlength, $vbulletin->options['postmaxchars'])));
	}
	if (vbstrlen($vbulletin->GPC['message']) < $vbulletin->options['postminchars'] OR $vbulletin->GPC['message'] == '')
	{
		eval(standard_error(fetch_error('tooshort', $vbulletin->options['postminchars'])));
	}

	($hook = vBulletinHook::fetch_hook('usernote_donote')) ? eval($hook) : false;

	if ($vbulletin->GPC['usernoteid'])
	{
		// Edited note.
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "usernote
			SET message = '" . $db->escape_string($vbulletin->GPC['message']) . "',
				title = '" . $db->escape_string($vbulletin->GPC['title']) . "',
				allowsmilies = $allowsmilies
			WHERE usernoteid = " . $vbulletin->GPC['usernoteid'] . "
		");
		clear_autosave_text('vBForum_UserNote', $noteinfo['usernoteid'], 0, $vbulletin->userinfo['userid']);
	}
	else
	{
		/*insert query*/
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "usernote (message, dateline, userid, posterid, title, allowsmilies)
			VALUES ('" . $db->escape_string($vbulletin->GPC['message']) . "', " . TIMENOW . ", $userinfo[userid], " . $vbulletin->userinfo['userid'] . ", '" . $db->escape_string($vbulletin->GPC['title']) . "', $allowsmilies)
		");
		clear_autosave_text('vBForum_UserNote', 0, $userinfo['userid'], $vbulletin->userinfo['userid']);
	}

	if (!$canview)
	{
		$vbulletin->url = fetch_seo_url('member', $userinfo);
	}
	else
	{
		$vbulletin->url = 'usernote.php?' . $vbulletin->session->vars['sessionurl'] . "do=viewuser&amp;u=$userinfo[userid]";
	}
	print_standard_redirect(array('redirect_usernoteaddevent',$userinfo['username']));  

}

// ############################### Start Add User Note ##########################
if ($_REQUEST['do'] == 'newnote')
{
	if (!$canpost)
	{
		print_no_permission();
	}

	if (empty($checked['parseurl']))
	{
		$checked['parseurl'] = 'checked="checked"';
	}

	if ($vbulletin->options['unallowsmilies'] == 1)
	{
		$templater = vB_Template::create('newpost_disablesmiliesoption');
			$templater->register('checked', $checked);
		$disablesmiliesoption = $templater->render();
	}

	$show['editnote'] = false;

	// include useful functions
	require_once(DIR . '/includes/functions_newpost.php');
	$editorid = construct_edit_toolbar(
		'',
		0,
		'usernote',
		true,
		true,
		false,
		'fe',
		'',
		array(),
		'content',
		'vBForum_UserNote',
		0,
		$userinfo['userid'],
		false,
		true,
		'titlefield'
	);

	// generate navbar
	$navbits = array(
		fetch_seo_url('member', $userinfo) => $vbphrase['view_profile'],
		'usernote.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]" => construct_phrase($vbphrase['user_notes_for_x'], $userinfo['username']),
		$vbphrase['post_user_note']
	);

	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	$show['parseurl'] = $vbulletin->options['unallowvbcode'];
	$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));

	($hook = vBulletinHook::fetch_hook('usernote_newnote')) ? eval($hook) : false;

	$templater = vB_Template::create('usernote_note');
		$templater->register_page_templates();
		$templater->register('checked', $checked);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('editorid', $editorid);
		$templater->register('forumjump', $forumjump);
		$templater->register('forumrules', $forumrules);
		$templater->register('messagearea', $messagearea);
		$templater->register('navbar', $navbar);
		$templater->register('noteinfo', $noteinfo);
		$templater->register('onload', $onload);
		$templater->register('userinfo', $userinfo);
		$templater->register('usernamecode', $usernamecode);
	print_output($templater->render());
}

// ############################### Start Get User Notes##########################
if ($_REQUEST['do'] == 'viewuser')
{

	if (!$canview)
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'	=> TYPE_UINT,
		'pagenumber'=> TYPE_UINT
	));

	require_once(DIR . '/includes/class_postbit.php');

	($hook = vBulletinHook::fetch_hook('usernote_viewuser_start')) ? eval($hook) : false;

	// *********************************************************************************
	// get ignored users
	$ignore = array();
	if (trim($vbulletin->userinfo['ignorelist']))
	{
		$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
		foreach ($ignorelist AS $ignoreuserid)
		{
			$ignore["$ignoreuserid"] = 1;
		}
	}

	$notescount = $db->query_first_slave("
		SELECT COUNT(*) AS notes FROM " . TABLE_PREFIX . "usernote
		WHERE userid = $userinfo[userid]
	");
	$totalnotes = $notescount['notes'];

	$vbulletin->GPC['perpage'] = sanitize_maxposts($vbulletin->GPC['perpage']);

	// *********************************************************************************
	// set page number
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	else if ($vbulletin->GPC['pagenumber'] > ceil($totalnotes / $vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['pagenumber'] = ceil($totalnotes / $vbulletin->GPC['perpage']);
	}

	$limitlower = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'] + 1;
	$limitupper = ($vbulletin->GPC['pagenumber']) * $vbulletin->GPC['perpage'];

	if ($limitupper > $totalnotes)
	{
		$limitupper = $totalnotes;
		if ($limitlower > $totalnotes)
		{
			$limitlower = $totalnotes - $vbulletin->GPC['perpage'];
		}
	}
	if ($limitlower <= 0)
	{
		$limitlower = 1;
	}

	$counter = 0;
	$postcount = ($vbulletin->GPC['pagenumber'] - 1 ) * $vbulletin->GPC['perpage'];

	$hook_query_fields = $hook_query_joins = '';
	($hook = vBulletinHook::fetch_hook('usernote_viewuser_query')) ? eval($hook) : false;

	$notes = $db->query_read_slave("
		SELECT usernote.*, usernote.username as postusername, user.*, userfield.*,
		IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, infractiongroupid,
		IF(posterid=0, 0, user.userid) AS userid
		" . iif($vbulletin->options['avatarenabled'],",avatar.avatarpath,NOT ISNULL(customavatar.userid) AS hascustomavatar,customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight") . "
		$hook_query_fields
		FROM " . TABLE_PREFIX . "usernote AS usernote
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON(usernote.posterid=user.userid)
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid=user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON(user.usergroupid=usergroup.usergroupid)
		" . iif($vbulletin->options['avatarenabled'],"LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid=user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid=user.userid)") . "
		$hook_query_joins
		WHERE usernote.userid = $userinfo[userid]
		ORDER BY usernote.dateline LIMIT " . ($limitlower - 1) . ", " . $vbulletin->GPC['perpage'] . "
	");

	$postbit_factory = new vB_Postbit_Factory();
	$postbit_factory->registry =& $vbulletin;
	$postbit_factory->cache = array();
	$postbit_factory->bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	while ($post = $db->fetch_array($notes))
	{
		$postbit_obj =& $postbit_factory->fetch_postbit('usernote');
		$post['postcount'] = ++$postcount;
		$post['postid'] = $post['usernoteid'];
		$post['viewself'] = $viewself;
		$notebits .= $postbit_obj->construct_postbit($post);
	}

	$show['notes'] = ($notebits != '');

	$db->free_result($notes);
	unset($note);

	$pagenav = construct_page_nav($vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage'], $totalnotes, 'usernote.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]&amp;pp=" . $vbulletin->GPC['perpage']);

	// generate navbar
	$navbits = array(
		fetch_seo_url('member', $userinfo) => $vbphrase['view_profile'],
		construct_phrase($vbphrase['user_notes_for_x'], $userinfo['username'])
	);

	$show['addnote'] = $canpost;

	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('usernote_viewuser_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('usernote');
		$templater->register_page_templates();
		$templater->register('forumjump', $forumjump);
		$templater->register('forumrules', $forumrules);
		$templater->register('navbar', $navbar);
		$templater->register('notebits', $notebits);
		$templater->register('pagenav', $pagenav);
		$templater->register('spacer_close', $spacer_close);
		$templater->register('spacer_open', $spacer_open);
		$templater->register('userinfo', $userinfo);
		$templater->register('totalnotes', $totalnotes);
		$templater->register('limitlower', $limitlower);
		$templater->register('limitupper', $limitupper);
	print_output($templater->render());

}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 62098 $
|| ####################################################################
\*======================================================================*/
?>
