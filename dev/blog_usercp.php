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
define('THIS_SCRIPT', 'blog_usercp');
define('CSRF_PROTECTION', true);
define('VBBLOG_PERMS', true);
define('VBBLOG_STYLE', true);
define('VBBLOG_SCRIPT', true);
define('GET_EDIT_TEMPLATES', 'editprofile,updateprofile,updatesidebar,modifyblock');

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'vbblogglobal',
	'vbblogcat',
	'user',
	'posting',
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
	'blog_cp_css',
	'blog_usercss',
	'blog_header_custompage_link',
	'blog_sidebar_category_link',
	'blog_sidebar_comment_link',
	'blog_sidebar_custompage_link',
	'blog_sidebar_entry_link',
	'blog_sidebar_user',
	'blog_sidebar_user_block_archive',
	'blog_sidebar_user_block_category',
	'blog_sidebar_user_block_comments',
	'blog_sidebar_user_block_entries',
	'blog_sidebar_user_block_search',
	'blog_sidebar_user_block_tagcloud',
	'blog_sidebar_user_block_visitors',
	'blog_sidebar_user_block_custom',
	'blog_sidebar_calendar',
	'blog_sidebar_calendar_day',
	'blog_tag_cloud_link',
	'ad_blogsidebar_start',
	'ad_blogsidebar_middle',
	'ad_blogsidebar_end',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'editoptions' => array(
		'blog_cp_modify_options',
	),
	'editprofile' => array(
		'blog_cp_modify_profile',
		'blog_cp_modify_profile_preview',
		'blog_rules',
	),
	'editcat' => array(
		'blog_cp_manage_categories',
		'blog_cp_manage_categories_category',
	),
	'modifycat' => array(
		'blog_cp_new_category',
	),
	'updatecat' => array(
		'blog_cp_new_category'
	),
	'managetrackback' => array(
		'blog_cp_manage_trackbacks',
		'blog_cp_manage_trackbacks_trackback',
	),
	'stats' => array(
		'blog_cp_statbit',
		'blog_cp_stats',
		'forumdisplay_sortarrow',
	),
	'sidebar' => array(
		'blog_cp_manage_sidebar',
		'blog_cp_manage_sidebar_bit',
		'blog_cp_manage_custom_editor',
		'blog_rules',
	),
	'custompage' => array(
		'blog_cp_manage_custompage',
		'blog_cp_manage_custompage_bit',
		'blog_cp_manage_custom_editor',
		'blog_rules',
	),
	'modifyblock' => array(
		'blog_cp_manage_custom_editor',
		'blog_rules',
	),
	'groups' => array(
		'blog_cp_manage_group',
		'blog_cp_manage_group_blogbit',
		'blog_cp_manage_group_userbit',
	),
	'customize' => array(
		'blog_usercss',
		'blog_cp_manage_usercss',
		'modifyusercss_backgroundbit',
		'modifyusercss_backgroundrow',
		'modifyusercss_bit',
		'modifyusercss_error',
		'modifyusercss_error_link',
		'modifyusercss_headinclude',
	),
	'userperm' => array(
		'blog_cp_manage_group_perm',
		'blog_cp_manage_group_permbit',
	),
);

$actiontemplates['updateprofile'] =& $actiontemplates['editprofile'];
$actiontemplates['updatesidebar'] =& $actiontemplates['modifyblock'];
$actiontemplates['docustomize'] =& $actiontemplates['customize'];
$actiontemplates['none'] =& $actiontemplates['editoptions'];

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/blog_functions_usercp.php');
require_once(DIR . '/includes/blog_functions_post.php');

verify_blog_url();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$bloguserinfo = array();
$checked = array();

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'editoptions';
}

if (!($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']) OR !$vbulletin->userinfo['userid'])
{
	print_no_permission();
}

$show['pingback'] = ($vbulletin->options['vbblog_pingback'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canreceivepingback'] ? true : false);
$show['trackback'] = ($vbulletin->options['vbblog_trackback'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canreceivepingback'] ? true : false);
$show['hidesidebar'] = true;

switch($_REQUEST['do'])
{
	case 'editprofile':
		$rules = 'user';
		break;
	case 'modifyblock':
	case 'updateblock':
	case 'updatesidebar':
		$rules = 'customblock';
		break;
	default:
		$rules = '';
}

($hook = vBulletinHook::fetch_hook('blog_usercp_start')) ? eval($hook) : false;

// ############################################################################
// ############################### UPDATED PROFILE ############################
// ############################################################################
if ($_POST['do'] == 'updateprofile')
{
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'wysiwyg'        => TYPE_BOOL,
		'title'          => TYPE_STR,
		'message'        => TYPE_STR,
		'parseurl'       => TYPE_BOOL,
		'disablesmilies' => TYPE_BOOL,
		'preview'        => TYPE_STR,
	));

	$errors = array();
	// unwysiwygify the incoming data
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$vbulletin->GPC['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowhtml']);
	}

	require_once(DIR . '/includes/functions_newpost.php');
	// parse URLs in message text
	if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode'] AND $vbulletin->GPC['parseurl'])
	{
		$vbulletin->GPC['message'] = convert_url_to_bbcode($vbulletin->GPC['message']);
	}

	$dataman =& datamanager_init('Blog_User', $vbulletin, ERRTYPE_ARRAY);
	if ($vbulletin->userinfo['bloguserid'])
	{
		$foo = array('bloguserid' => $vbulletin->userinfo['bloguserid']);
		$dataman->set_existing($foo);
	}
	else
	{
		$dataman->set('bloguserid', $vbulletin->userinfo['userid']);
	}

	$dataman->set('description', $vbulletin->GPC['message']);
	$dataman->set('title', $vbulletin->GPC['title']);
	$dataman->set('allowsmilie', $vbulletin->GPC['disablesmilies'] ? 0 : 1);

	$dataman->pre_save();

	$bloguserinfo = array();
	if (!empty($dataman->errors))
	{	### DESCRIPTION HAS ERRORS ###
		define('PREVIEW', 1);
		$show['errors'] = true;
		$postpreview = construct_errors($dataman->errors);
		$_REQUEST['do'] = 'editprofile';
	}
	else if ($vbulletin->GPC['preview'])
	{
		define('PREVIEW', 1);
		$_REQUEST['do'] = 'editprofile';

		$vbulletin->userinfo['blog_description'] = $vbulletin->GPC['message'];
		$vbulletin->userinfo['blog_allowsmilie'] = !($vbulletin->GPC['disablesmilies']);
		$blogheader = parse_blog_description($vbulletin->userinfo);
	}
	else
	{
		$dataman->save();

		clear_autosave_text('vBBlog_BlogDescription', 0, 0, $vbulletin->userinfo['userid']);
		$vbulletin->url = fetch_seo_url('blogusercp', array(), array('do' => 'editprofile'));
		print_standard_redirect(array('redirect_blog_profileupdate',$vbulletin->userinfo['username']), true, true);
	}
}

// ############################################################################
// ############################### EDIT PROFILE ###############################
// ############################################################################
if ($_REQUEST['do'] == 'editprofile')
{
	($hook = vBulletinHook::fetch_hook('blog_editprofile_start')) ? eval($hook) : false;

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}

	if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode'])
	{
		$show['parseurl'] = true;
		$show['miscoptions'] = true;
	}

	if (defined('PREVIEW'))
	{
		$bloguserinfo['message'] = htmlspecialchars_uni($vbulletin->GPC['message']);
		$bloguserinfo['title'] = htmlspecialchars_uni($vbulletin->GPC['title']);
		$checked['disablesmilies'] = $vbulletin->GPC['disablesmilies'] ? 'checked="checked"' : '';
		$checked['parseurl'] = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode'] AND $vbulletin->GPC['parseurl']) ? 'checked="checked"' : '';
		$blogheader = parse_blog_description($vbulletin->userinfo);
		$postpreview = ($postpreview) ? $postpreview : $blogheader['description'];
	}
	else
	{
		$bloguserinfo['message'] = $vbulletin->userinfo['blog_description'];
		$bloguserinfo['title'] = ($vbulletin->userinfo['blog_title'] == $vbulletin->userinfo['username']) ? '' : $vbulletin->userinfo['blog_title'];
		$checked['parseurl'] = 'checked="checked"';
		if (!$vbulletin->userinfo['blog_allowsmilie'] AND $vbulletin->userinfo['blog_description'])
		{
			$checked['disablesmilies'] = 'checked="checked"';
		}

		$postpreview = '';
		$blogheader = parse_blog_description($vbulletin->userinfo);
	}

	// get decent textarea size for user's browser
	require_once(DIR . '/includes/functions_editor.php');

	$editorid = construct_edit_toolbar(
		$bloguserinfo['message'],
		false,
		'blog_user',
		$vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowsmilies'],
		true,
		false,
		'fe',
		'',
		array(),
		'content',
		'vBBlog_BlogDescription',
		0,
		0,
		defined('PREVIEW'),
		true,
		'text_blog_title'
	);

	// build forum rules
	$bbcodeon = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode']) ? $vbphrase['on'] : $vbphrase['off'];
	$imgcodeon = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowimages']) ? $vbphrase['on'] : $vbphrase['off'];
	$videocodeon = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowvideos']) ? $vbphrase['on'] : $vbphrase['off'];
	$htmlcodeon = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowhtml']) ? $vbphrase['on'] : $vbphrase['off'];
	$smilieson = ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowsmilies']) ? $vbphrase['on'] : $vbphrase['off'];

	if ($vbulletin->userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowsmilies'])
	{
		$templater = vB_Template::create('newpost_disablesmiliesoption');
			$templater->register('checked', $checked);
		$disablesmiliesoption = $templater->render();
		$show['miscoptions'] = true;
	}

	// only show posting code allowances in forum rules template
	$show['codeonly'] = true;

	$templater = vB_Template::create('forumrules');
		$templater->register('bbcodeon', $bbcodeon);
		$templater->register('can', $can);
		$templater->register('htmlcodeon', $htmlcodeon);
		$templater->register('imgcodeon', $imgcodeon);
		$templater->register('videocodeon', $videocodeon);
		$templater->register('smilieson', $smilieson);
	$forumrules = $templater->render();

	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

	$navbits = array('' => $vbphrase['blog_title_and_description']);

	($hook = vBulletinHook::fetch_hook('blog_editprofile_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('blog_cp_modify_profile');
		$templater->register('bloguserinfo', $bloguserinfo);
		$templater->register('checked', $checked);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('editorid', $editorid);
		$templater->register('messagearea', $messagearea);
		$templater->register('postpreview', $postpreview);
	$content = $templater->render();
}

// ############################################################################
// ############################### EDIT OPTIONS ###############################
// ############################################################################
if ($_REQUEST['do'] == 'editoptions')
{
	($hook = vBulletinHook::fetch_hook('blog_editoptions_start')) ? eval($hook) : false;

	foreach($vbulletin->bf_misc_vbbloguseroptions AS $optionname => $optionval)
	{
		$checked["$optionname"] = $vbulletin->userinfo['blog_' . $optionname] ? 'checked="checked"' : '';
	}

	foreach ($vbulletin->bf_misc_vbblogsocnetoptions AS $optionname => $optionvalue)
	{
		$checked["member_$optionname"] = $vbulletin->userinfo["member_$optionname"] ? 'checked="checked"' : '';
		$checked["guest_$optionname"] = $vbulletin->userinfo["guest_$optionname"] ? 'checked="checked"' : '';
		$checked["buddy_$optionname"] = $vbulletin->userinfo["buddy_$optionname"] ? 'checked="checked"' : '';
		$checked["ignore_$optionname"] = $vbulletin->userinfo["ignore_$optionname"] ? 'checked="checked"' : '';
	}

	$subscribeownchecked = array($vbulletin->userinfo['blog_subscribeown'] => 'selected="selected"');
	$subscribeotherschecked = array($vbulletin->userinfo['blog_subscribeothers'] => 'selected="selected"');
	$blog_akismet_key = htmlspecialchars_uni($vbulletin->userinfo['blog_akismet_key']);

	$show['moderatecomments'] = (!$vbulletin->options['blog_commentmoderation'] AND $vbulletin->userinfo['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_followcommentmoderation'] ? true : false);
	$show['akismet_key'] = (empty($vbulletin->options['vb_antispam_key']) AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']);
	$show['allowcomments'] = ($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']);
	$show['privacy'] = ($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']);

	$guestuser = array(
		'userid'      => 0,
		'usergroupid' => 0,
	);
	cache_permissions($guestuser, false);

	$show['guestview'] = ($guestuser['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']) ? true : false;
	$show['guestcomment'] = (
		$show['guestview']
			AND
		$guestuser['permissions']['vbblog_comment_permissions'] & $vbulletin->bf_ugp_vbblog_comment_permissions['blog_cancommentothers']
	) ? true : false;

	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

	$navbits = array('' => $vbphrase['permissions_and_privacy']);

	($hook = vBulletinHook::fetch_hook('blog_editoptions_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('blog_cp_modify_options');
		$templater->register('blog_akismet_key', $blog_akismet_key);
		$templater->register('checked', $checked);
		$templater->register('subscribeotherschecked', $subscribeotherschecked);
		$templater->register('subscribeownchecked', $subscribeownchecked);
	$content = $templater->render();
}

// ############################################################################
// ############################### UPDATED OPTIONS ############################
// ############################################################################
if ($_POST['do'] == 'updateoptions')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'options'          => TYPE_ARRAY_BOOL,
		'set_options'      => TYPE_ARRAY_BOOL,
		'options_member'   => TYPE_ARRAY_BOOL,
		'options_buddy'    => TYPE_ARRAY_BOOL,
		'options_ignore'   => TYPE_ARRAY_BOOL,
		'options_guest'    => TYPE_ARRAY_BOOL,
		'title'            => TYPE_STR,
		'description'      => TYPE_STR,
		'subscribeown'     => TYPE_STR,
		'subscribeothers'  => TYPE_STR,
		'akismet_key'      => TYPE_STR
	));

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

	// options bitfield
	foreach ($vbulletin->bf_misc_vbbloguseroptions AS $key => $val)
	{
		if (isset($vbulletin->GPC['options']["$key"]) OR isset($vbulletin->GPC['set_options']["$key"]))
		{
			$value = intval($vbulletin->GPC['options']["$key"]);
			$dataman->set_bitfield('options', $key, $value);
		}
	}

	// options bitfield
	foreach ($vbulletin->bf_misc_vbblogsocnetoptions AS $key => $val)
	{
		if (isset($vbulletin->GPC['set_options']["options_member_$key"]) OR isset($vbulletin->GPC['options_member']["$key"]))
		{
			$dataman->set_bitfield('options_member', $key, intval($vbulletin->GPC['options_member']["$key"]));
		}
		if (isset($vbulletin->GPC['set_options']["options_guest_$key"]) OR isset($vbulletin->GPC['options_guest']["$key"]))
		{
			$dataman->set_bitfield('options_guest', $key, intval($vbulletin->GPC['options_guest']["$key"]));
		}
		if (isset($vbulletin->GPC['set_options']["options_buddy_$key"]) OR isset($vbulletin->GPC['options_buddy']["$key"]))
		{
			$dataman->set_bitfield('options_buddy', $key, intval($vbulletin->GPC['options_buddy']["$key"]));
		}
		if (isset($vbulletin->GPC['set_options']["options_ignore_$key"]) OR isset($vbulletin->GPC['options_ignore']["$key"]))
		{
			$dataman->set_bitfield('options_ignore', $key, intval($vbulletin->GPC['options_ignore']["$key"]));
		}
	}

	if (isset($vbulletin->GPC['set_options']['subscribeown']) OR $vbulletin->GPC_exists['subscribeown'])
	{
		$dataman->set('subscribeown', $vbulletin->GPC['subscribeown']);
	}
	if (isset($vbulletin->GPC['set_options']['subscribeothers']) OR $vbulletin->GPC_exists['subscribeothers'])
	{
		$dataman->set('subscribeothers', $vbulletin->GPC['subscribeothers']);
	}

	if ((isset($vbulletin->GPC['set_options']['akismet_key']) OR $vbulletin->GPC_exists['akismet_key']) AND empty($vbulletin->options['vb_antispam_key']))
	{
		$dataman->set('akismet_key', $vbulletin->GPC['akismet_key']);
	}

	$dataman->save();

	// Update latest entry in the event the user changed who can view their blog
	build_blog_stats();

	$vbulletin->url = fetch_seo_url('blogusercp', array());
	print_standard_redirect(array('redirect_blog_profileupdate',$vbulletin->userinfo['username']));
}

// ############################################################################
// ############################### EDIT CATEGORIES ############################
// ############################################################################
if ($_REQUEST['do'] == 'editcat')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT,
	));

	if ($vbulletin->GPC['userid'] AND $vbulletin->GPC['userid'] != $vbulletin->userinfo['userid'] AND can_moderate_blog('caneditcategories'))
	{
		$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true);
		cache_permissions($userinfo, false);
		$show['modedit'] = true;
	}
	else
	{
		$userinfo =& $vbulletin->userinfo;
		if (
			!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
				OR
			!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancreatecategory'])
		)
		{
			print_no_permission();
		}
		$show['blogcp'] = true;
	}

	require_once(DIR . '/includes/blog_functions_category.php');
	fetch_ordered_categories($userinfo['userid']);

	$catbits = '';
	foreach ($vbulletin->vbblog['categorycache']["{$userinfo['userid']}"] AS $blogcategoryid => $category)
	{
		if ($category['userid'] == 0)
		{	// admin category
			continue;
		}
		$depthmarkclass = 'd' . $category['depth'];

		$templater = vB_Template::create('blog_cp_manage_categories_category');
			$templater->register('blogcategoryid', $blogcategoryid);
			$templater->register('category', $category);
			$templater->register('depthmarkclass', $depthmarkclass);
			$templater->register('pageinfo', array('blogcategoryid' => $category['blogcategoryid']));
			$templater->register('userinfo', $userinfo);
		$catbits .= $templater->render();
	}

	$categorycount = $vbulletin->vbblog['categorycount'][$userinfo['userid']];

	// Sidebar
	$sidebar =& build_user_sidebar($userinfo);

	if ($userinfo['userid'] == $vbulletin->userinfo['userid'])
	{
		$navbits = array('' => $vbphrase['blog_categories']);
	}
	else
	{
		$navbitsdone = true;
		$navbits = array(
			fetch_seo_url('bloghome', array())  => $vbphrase['blogs'],
			fetch_seo_url('blog', $userinfo)  => $userinfo['blog_title'],
			'' => $vbphrase['blog_categories'],
		);
	}

	$templater = vB_Template::create('blog_cp_manage_categories');
		$templater->register('catbits', $catbits);
		$templater->register('categorycount', $categorycount);
		$templater->register('pagenav', $pagenav);
		$templater->register('userinfo', $userinfo);
	$content = $templater->render();
}

// ############################################################################
// ###############################   ADD CATEGORIES  ##########################
// ############################################################################
if ($_POST['do'] == 'addcat')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'title'          => TYPE_STR,
		'description'    => TYPE_STR,
		'parentid'       => TYPE_UINT,
		'displayorder'   => TYPE_UINT,
		'blogcategoryid' => TYPE_UINT,
		'dbutton'        => TYPE_STR,
		'delete'         => TYPE_BOOL,
		'userid'         => TYPE_UINT,
	));

	if ($vbulletin->GPC['userid'] AND $vbulletin->GPC['userid'] != $vbulletin->userinfo['userid'] AND can_moderate_blog('caneditcategories'))
	{
		$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
		cache_permissions($userinfo, false);
		$show['modedit'] = true;
	}
	else
	{
		$userinfo =& $vbulletin->userinfo;
		if (
			!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
				OR
			!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancreatecategory'])
		)
		{
			print_no_permission();
		}
		$show['blogcp'] = true;
	}

	require_once(DIR . '/includes/blog_functions_category.php');

	$errors = array();

	fetch_ordered_categories($userinfo['userid']);

	$dataman =& datamanager_init('Blog_Category', $vbulletin, ERRTYPE_ARRAY);

	if ($vbulletin->GPC['blogcategoryid'])
	{
		if ($categoryinfo = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "blog_category
			WHERE blogcategoryid = " . $vbulletin->GPC['blogcategoryid'] . "
				AND userid = $userinfo[userid]
		"))
		{
			$dataman->set_existing($categoryinfo);
			if ($vbulletin->GPC['dbutton'])
			{
				if ($vbulletin->GPC['delete'])
				{
					$dataman->set_condition("FIND_IN_SET('" . $vbulletin->GPC['blogcategoryid'] . "', parentlist)");
					$dataman->delete();

					$page_vars = array('do' => 'editcat');
					if ($vbulletin->GPC['userid'])
					{
						$page_vars['userid'] = $userinfo['userid'];
					}

					$vbulletin->url = fetch_seo_url('blogusercp', array(), $page_vars);
					print_standard_redirect(array('redirect_blog_profileupdate',$vbulletin->userinfo['username']));
				}
				else
				{
					define('PREVIEW', 1);
					$_REQUEST['do'] = 'modifycat';
				}
			}
		}
		else
		{
			standard_error(fetch_error('invalidid', 'blogcategoryid', $vbulletin->options['contactuslink']));
		}
	}
	else
	{
		$count = 0;
		foreach($vbulletin->vbblog['categorycache'][$userinfo['userid']] AS $categorycheck)
		{
			if ($categorycheck['userid'] == $userinfo['userid'])
			{
				$count++;
			}
		}
		if ($count >= $vbulletin->options['blog_catusertotal'])
		{
			standard_error(fetch_error('blog_category_limit', $vbulletin->options['blog_catusertotal']));
		}
		$dataman->set('userid', $userinfo['userid']);
	}

	if (empty($errors) AND !defined('PREVIEW'))
	{
		$dataman->set('description', $vbulletin->GPC['description']);
		$dataman->set('title', $vbulletin->GPC['title']);
		$dataman->set('parentid', $vbulletin->GPC['parentid']);
		$dataman->set('displayorder', $vbulletin->GPC['displayorder']);

		$dataman->pre_save();

		if (!empty($dataman->errors))
		{
			define('PREVIEW', 1);
			$_REQUEST['do'] = 'modifycat';
			require_once(DIR . '/includes/functions_newpost.php');
			$errorlist = construct_errors($dataman->errors);
		}
		else
		{
			$dataman->save();

			$page_vars = array('do' => 'editcat');
			if ($show['modedit'])
			{
				$page_vars['userid'] = $userinfo['userid'];
			}

			$vbulletin->url = fetch_seo_url('blogusercp', array(), $page_vars);
			print_standard_redirect(array('redirect_blog_profileupdate',$vbulletin->userinfo['username']));
		}
	}
}

// ############################################################################
// ############################### UPDATE CATEGORIES ##########################
// ############################################################################
if ($_POST['do'] == 'updatecat')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'addcat'       => TYPE_STR,
		'displayorder' => TYPE_ARRAY_UINT,
		'userid'       => TYPE_UINT,
	));

	if ($vbulletin->GPC['userid'] AND $vbulletin->GPC['userid'] != $vbulletin->userinfo['userid'] AND can_moderate_blog('caneditcategories'))
	{
		$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
		cache_permissions($userinfo, false);
		$show['modedit'] = true;
	}
	else
	{
		$userinfo =& $vbulletin->userinfo;
		if (
			!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
				OR
			!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancreatecategory'])
		)
		{
			print_no_permission();
		}
		$show['blogcp'] = true;
	}

	if ($vbulletin->GPC['addcat'])
	{	// Add New Category
		$_REQUEST['do'] = 'modifycat';
		define('CLICKED_ADD_BUTTON', true);
	}
	else
	{	// Update Display Order and Rebuild Category Cache
		$casesql = array();
		foreach ($vbulletin->GPC['displayorder'] AS $blogcategoryid => $displayorder)
		{
			$casesql[] = " WHEN blogcategoryid = " . intval($blogcategoryid) . " THEN $displayorder";
		}

		if (!empty($casesql))
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "blog_category
				SET displayorder =
				CASE
					" . implode("\r\n", $casesql) . "
					ELSE displayorder
				END
				WHERE userid = $userinfo[userid]
			");
		}

		$page_vars = array('do' => 'editcat');
		if ($show['modedit'])
		{
			$page_vars['userid'] = $userinfo['userid'];
		}
		$vbulletin->url = fetch_seo_url('blogusercp', array(), $page_vars);
		print_standard_redirect(array('redirect_blog_profileupdate',$vbulletin->userinfo['username']));
	}
}

// ############################################################################
// ############################### MANAGE CATEGORIES ##########################
// ############################################################################
if ($_REQUEST['do'] == 'modifycat')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'blogcategoryid' => TYPE_UINT
	));

	$categoryinfo = array('displayorder' => 1);

	if ($vbulletin->GPC['blogcategoryid'])
	{
		if (!($categoryinfo = $db->query_first("
			SELECT *, title AS realtitle
			FROM " . TABLE_PREFIX . "blog_category
			WHERE blogcategoryid = " . $vbulletin->GPC['blogcategoryid'] . "
		")))
		{
			standard_error(fetch_error('invalidid', 'blogcategoryid', $vbulletin->options['contactuslink']));
		}

		if ($categoryinfo['userid'] != $vbulletin->userinfo['userid'])
		{
			if (!can_moderate_blog('caneditcategories'))
			{
				standard_error(fetch_error('invalidid', 'blogcategoryid', $vbulletin->options['contactuslink']));
			}
			$userinfo = fetch_userinfo($categoryinfo['userid']);
			cache_permissions($userinfo, false);
		}
		else
		{
			$userinfo =& $vbulletin->userinfo;
			$show['blogcp'] = true;
		}
	}
	else if (!defined('CLICKED_ADD_BUTTON') AND !defined('PREVIEW'))
	{
		$userinfo =& $vbulletin->userinfo;
		if (
			!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
				OR
			!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancreatecategory'])
		)
		{
			print_no_permission();
		}
		$show['blogcp'] = true;
	}

	require_once(DIR . '/includes/blog_functions_category.php');

	if (!$vbulletin->GPC['blogcategoryid'])
	{ // make sure they have less than the limit
		if (!isset($vbulletin->vbblog['categorycache'][$userinfo['userid']]))
		{
			fetch_ordered_categories($userinfo['userid']);
		}

		$count = 0;
		foreach($vbulletin->vbblog['categorycache'][$userinfo['userid']] AS $categorycheck)
		{
			if ($categorycheck['userid'] == $userinfo['userid'])
			{
				$count++;
			}
		}
		if ($count >= $vbulletin->options['blog_catusertotal'])
		{
			standard_error(fetch_error('blog_category_limit', $vbulletin->options['blog_catusertotal']));
		}
	}

	if (defined('PREVIEW'))
	{
		$categoryinfo = array(
			'realtitle'      => $categoryinfo['title'],
			'title'          => htmlspecialchars_uni($vbulletin->GPC['title']),
			'description'    => htmlspecialchars_uni($vbulletin->GPC['description']),
			'parentid'       => $vbulletin->GPC['parentid'],
			'displayorder'   => $vbulletin->GPC['displayorder'],
			'blogcategoryid' => $vbulletin->GPC['blogcategoryid'],
		);
	}

	$selectbits = construct_category_select($categoryinfo['parentid'], $userinfo['userid']);

	// Sidebar
	$sidebar =& build_user_sidebar($userinfo);

	if ($userinfo['userid'] == $vbulletin->userinfo['userid'])
	{
		$navbits = array(
			fetch_seo_url('blogusercp', array(), array('do' => 'editcat')) => $vbphrase['blog_categories'],
			'' => ($categoryinfo['blogcategoryid'] ? $vbphrase['edit_blog_category'] : $vbphrase['add_new_blog_category'])
		);
	}
	else
	{
		$navbitsdone = true;
		$navbits = array(
			fetch_seo_url('bloghome', array())  => $vbphrase['blogs'],
			fetch_seo_url('blog', $userinfo)  => $userinfo['blog_title'],
		);

		if ($vbulletin->GPC['blogcategoryid'])
		{
			$navbits[fetch_seo_url('blogusercp', array(), array('do' => 'editcat', 'u' => $userinfo['userid']))] = $vbphrase['blog_categories'];
			$navbits[] = $categoryinfo['title'];
		}
		else
		{
			$navbits[] = $vbphrase['blog_categories'];
		}
	}

	$templater = vB_Template::create('blog_cp_new_category');
		$templater->register('categoryinfo', $categoryinfo);
		$templater->register('errorlist', $errorlist);
		$templater->register('selectbits', $selectbits);
		$templater->register('userinfo', $userinfo);
	$content = $templater->render();
}

// ############################################################################
// ############################### MANAGE TRACKBACKS ##########################
// ############################################################################
if ($_REQUEST['do'] == 'managetrackback')
{
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}

	if ($show['pingback'] OR $show['trackback'])
	{
		$vbulletin->input->clean_array_gpc('r', array(
			'type'       => TYPE_STR,
			'pagenumber' => TYPE_UINT,
			'perpage'    => TYPE_UINT,
		));

		if (!$vbulletin->userinfo['userid'])
		{
			print_no_permission();
		}

		$canmoderateall = (can_moderate_blog('canmoderatecomments'));

		switch ($vbulletin->GPC['type'])
		{
			case 'fa';
			case 'fm';
					if (!$canmoderateall)
					{
						$type = 'oa';
					}
					else
					{
						$type = $vbulletin->GPC['type'];
					}
				break;
			case 'oa':
			case 'om':
				$type = $vbulletin->GPC['type'];
				break;
			default:
				$type = 'oa';
		}

		$selected = array(
			$type => 'selected="selected"'
		);

		$wheresql = array();
		if ($type == 'oa' OR $type == 'om')
		{
			if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
			{
				print_no_permission();
			}
			$wheresql[] = "bt.userid = " . $vbulletin->userinfo['userid'];
		}
		else
		{	// Moderator View
			$wheresql[] = "bt.userid <> " . $vbulletin->userinfo['userid'];
			$wheresql[] = "blog.dateline <= " . TIMENOW;
			$wheresql[] = "blog.pending = 0";

			$state = array('visible', 'deleted');

			if (can_moderate_blog('canmoderateentries'))
			{
				$state[] = 	'moderation';
			}

			$wheresql[] = "blog.state IN('" . implode("', '", $state) . "')";
		}
		if ($type == 'fm' OR $type == 'om')
		{
			$wheresql[] = "bt.state = 'moderation'";
		}

		// Set Perpage .. this limits it to 50. Any reason for more?
		if ($vbulletin->GPC['perpage'] == 0)
		{
			$perpage = 20;
		}
		else if ($vbulletin->GPC['perpage'] > 50)
		{
			$perpage = 50;
		}
		else
		{
			$perpage = $vbulletin->GPC['perpage'];
		}

		do
		{
			if ($vbulletin->GPC['pagenumber'] < 1)
			{
				$pagenumber = 1;
			}
			else if ($vbulletin->GPC['pagenumber'] > 10)
			{
				$pagenumber = 10;
			}
			else
			{
				$pagenumber = $vbulletin->GPC['pagenumber'];
			}
			$start = ($pagenumber - 1) * $perpage;

			$trackbacks = $db->query_read_slave("
				SELECT SQL_CALC_FOUND_ROWS bt.*,
					blog.title AS blogtitle, blog.state AS blog_state, blog.userid AS blog_userid, blog.categories, blog.pending, blog.postedby_userid, blog.postedby_username, blog.options AS blogoptions,
					bu.memberids, bu.memberblogids,
					user.membergroupids, user.usergroupid, user.infractiongroupids,
					user2.membergroupids AS blog_membergroupids, user2.usergroupid AS blog_usergroupid, user2.infractiongroupids AS blog_infractiongroupids,
					gm.permissions AS grouppermissions
				FROM " . TABLE_PREFIX . "blog_trackback AS bt
				LEFT JOIN " . TABLE_PREFIX . "blog AS blog ON (blog.blogid = bt.blogid)
				LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = blog.userid)
				LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = bt.userid)
				INNER JOIN " . TABLE_PREFIX . "user AS user2 ON (user2.userid = blog.userid)
				LEFT JOIN " . TABLE_PREFIX . "blog_groupmembership AS gm ON (blog.userid = gm.bloguserid AND gm.userid = " . $vbulletin->userinfo['userid'] . ")
				WHERE " . implode(" AND ", $wheresql) . "
				ORDER BY bt.dateline DESC
				LIMIT $start, $perpage
			");
			list($count) = $db->query_first("SELECT FOUND_ROWS()", DBARRAY_NUM);

			if ($start >= $post_count)
			{
				$vbulletin->GPC['pagenumber'] = ceil($count / $perpage);
			}
		}
		while ($start >= $count AND $count);

		$pagenavurl = array('do' => 'managetrackback');
		if ($type != 'oa')
		{
			$pagenavurl['type'] = $type;
		}
		if ($perpage != 20)
		{
			$pagenavurl['pp'] = $perpage;
		}

		$pagenav = construct_page_nav(
			$pagenumber,
			$perpage,
			$count,
			//			'blog_usercp.php?' . $vbulletin->session->vars['sessionurl'] . implode('&amp;', $pagenavurl)
			'',
			'',
			'',
			'blogusercp',
			array(),
			$pagenavurl
		);

		$colspan = 3;
		while ($trackback = $db->fetch_array($trackbacks))
		{
			$trackback = array_merge($trackback, convert_bits_to_array($trackback['blogoptions'], $vbulletin->bf_misc_vbblogoptions));
			$entryinfo = array(
				'blogid'             => $comment['blogid'],
				'userid'             => $comment['blog_userid'],
				'usergroupid'        => $comment['blog_usergroupid'],
				'infractiongroupids' => $comment['blog_infractiongroupids'],
				'membergroupids'     => $comment['blog_membergroupids'],
				'memberids'          => $comment['memberids'],
				'memberblogids'      => $comment['memberblogids'],
				'postedby_userid'    => $comment['postedby_userid'],
				'postedby_username'  => $comment['postedby_username'],
				'grouppermissions'   => $comment['grouppermissions'],
				'membermoderate'     => $comment['membermoderate'],
			);

			cache_permissions($trackback, false);
			cache_permissions($entryinfo, false);

			$show['edit_trackback'] = fetch_comment_perm('caneditcomments', $entryinfo, $trackback);
			$show['inlinemod_approve'] = fetch_comment_perm('canmoderatecomments', $entryinfo, $trackback);
			$show['inlinemod_delete'] = (fetch_comment_perm('candeletecomments', $entryinfo, $trackback) OR fetch_comment_perm('canremovecomments', $entryinfo, $trackback));
			if ($show['inlinemod_delete'] OR $show['inlinemod_approve'])
			{
				$show['inlinemod_trackback'] = true;
			}

			$show['moderation'] = ($trackback['state'] == 'moderation');

			$trackback['date'] = vbdate($vbulletin->options['dateformat'], $trackback['dateline'], true);
			$trackback['time'] = vbdate($vbulletin->options['timeformat'], $trackback['dateline'], true);
			$templater = vB_Template::create('blog_cp_manage_trackbacks_trackback');
				$templater->register('trackback', $trackback);
			$trackbackbits .= $templater->render();
		}
		if ($show['inlinemod_trackback'])
		{
			$colspan++;
		}

		// Sidebar
		$show['blogcp'] = true;
		$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

		$navbits = array($vbphrase['manage_trackbacks']);
		$templater = vB_Template::create('blog_cp_manage_trackbacks');
			$templater->register('canmoderateall', $canmoderateall);
			$templater->register('colspan', $colspan);
			$templater->register('count', $count);
			$templater->register('pagenav', $pagenav);
			$templater->register('selected', $selected);
			$templater->register('trackbackbits', $trackbackbits);
		$content = $templater->render();
	}
	else
	{	// Shouldn't be here
		print_no_permission();
	}
}

// ############################################################################
// ###############################     VIEW STATS    ##########################
// ############################################################################
if ($_REQUEST['do'] == 'stats')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'sortfield'  => TYPE_NOHTML,
		'sortorder'  => TYPE_NOHTML,
		'scope'      => TYPE_NOHTML,
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
	));

	($hook = vBulletinHook::fetch_hook('blog_stats_start')) ? eval($hook) : false;

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}

	$sortfield = $vbulletin->GPC['sortfield'];
	$perpage = $vbulletin->GPC['perpage'];
	$pagenumber = $vbulletin->GPC['pagenumber'];
	$sorturlbits = array();

	// look at sorting options:
	if ($vbulletin->GPC['sortorder'] != 'asc')
	{
		$vbulletin->GPC['sortorder'] = 'desc';
		$sqlsortorder = 'DESC';
		$order = array('desc' => 'selected="selected"');
	}
	else
	{
		$sqlsortorder = '';
		$order = array('asc' => 'selected="selected"');
		$sorturlbits[] = 'sortorder=asc';
	}

	switch ($sortfield)
	{
		case 'date':
			$sqlsortfield = 'dateline';
			break;
		case 'entry':
			$sqlsortfield = 'entrytotal';
			$sorturlbits[] = 'sortfield=entry';
			break;
		case 'comment':
			$sqlsortfield = 'commenttotal';
			$sorturlbits[] = 'comment';
			break;
		case 'visit':
			$sqlsortfield = 'usertotal';
			$sorturlbits[] = 'visit';
			break;
		default:
			$handled = false;
			($hook = vBulletinHook::fetch_hook('blog_stat_view_sort')) ? eval($hook) : false;
			if (!$handled)
			{
				$sqlsortfield = 'dateline';
				$sortfield = 'date';
			}
	}
	$sort = array($sortfield => 'selected="selected"');

	switch ($vbulletin->GPC['scope'])
	{
		case 'weekly':
			$sqlformat = '%U %Y';
			$phpformat = '# (! Y)';
			$scopeselected = array('weekly' => 'selected="selected"');
			$sorturlbits[] = 'scope=weekly';
			break;
		case 'monthly':
			$sqlformat = '%m %Y';
			$phpformat = '! Y';
			$scopeselected = array('monthly' => 'selected="selected"');
			$sorturlbits[] = 'scope=monthly';
			break;
		default:
			$sqlformat = '%w %U %m %Y';
			$phpformat = '! d, Y';
			$scopeselected = array('daily' => 'selected="selected"');
			break;
	}

	if ($perpage > 25 OR !$perpage)
	{
		$perpage = 25;
	}
	else
	{
		$sorturlbits[] = "pp=$perpage";
	}

	do
	{
		if (!$pagenumber)
		{
			$pagenumber = 1;
		}
		$start = ($pagenumber - 1) * $perpage;

		$statbits = '';

		$statistics = $db->query_read_slave("
			SELECT SQL_CALC_FOUND_ROWS SUM(users) AS usertotal, SUM(comments) AS commenttotal, SUM(entries) AS entrytotal,
			DATE_FORMAT(from_unixtime(dateline), '$sqlformat') AS formatted_date,
			MAX(dateline) AS dateline
			FROM " . TABLE_PREFIX . "blog_userstats
			WHERE userid = " . $vbulletin->userinfo['userid'] . "
			GROUP BY formatted_date
			###" . (empty($vbulletin->GPC['nullvalue']) ? " HAVING total > 0 " : "") . "###
			ORDER BY $sqlsortfield $sqlsortorder
			LIMIT $start, $perpage
		");
		$count = $db->found_rows();

		if ($start >= $count)
		{
			$pagenumber = ceil($count / $perpage);
		}
	}
	while ($start >= $count AND $count);

	while ($stat = $db->fetch_array($statistics))
	{
		exec_switch_bg();
		$month = strtolower(date('F', $stat['dateline']));
		$stat['date'] = str_replace(' ', '&nbsp;', str_replace('#', $vbphrase['week'] . '&nbsp;' . strftime('%U', $stat['dateline']), str_replace('!', $vbphrase["$month"], date($phpformat, $stat['dateline']))));

		$templater = vB_Template::create('blog_cp_statbit');
			$templater->register('bgclass', $bgclass);
			$templater->register('stat', $stat);
		$statbits .= $templater->render();
	}

	//blogusercp is a "generation only" url class, which currently means that it
	//will be locked to FRIENDLY_URL_OFF anyway.  However the current use very much depends
	//on the classic format since we add params to the query string somewhere in the templates
	//(using sorturl as a base in multiple locations).  No sense borrowing trouble now, but
	//if we change the behavior of the bloghome url generation we'll need to fix that before
	//we change so if we hard code the FRIENDLY_URL_OFF here it won't mysteriously break
	//if changes are made to the class.
	require_once(DIR . '/includes/class_friendly_url.php');
	$sorturl = vB_Friendly_Url::fetchLibrary($vbulletin, 'blogusercp', array(), array('do' => 'stats'))->get_url(FRIENDLY_URL_OFF);

	$oppositesort = ($vbulletin->GPC['sortorder'] == 'asc' ? 'desc' : 'asc');
		$templater = vB_Template::create('forumdisplay_sortarrow');
			$templater->register('oppositesort', $oppositesort);
		$sortarrow["$sortfield"] = $templater->render();
		$sortarrow['oppositesort'] = $oppositesort;

	$pagenav = construct_page_nav(
		$pagenumber,
		$perpage,
		$count,
		$sorturl . (!empty($sorturlbits) ? '&amp;' . implode('&amp;', $sorturlbits) : '')
	);

	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

	$navbits = array(
		'' => $vbphrase['blog_stats']
	);
	$templater = vB_Template::create('blog_cp_stats');
		$templater->register('gobutton', $gobutton);
		$templater->register('pagenav', $pagenav);
		$templater->register('pagenumber', $pagenumber);
		$templater->register('perpage', $perpage);
		$templater->register('scopeselected', $scopeselected);
		$templater->register('sortarrow', $sortarrow);
		$templater->register('sorturl', $sorturl);
		$templater->register('statbits', $statbits);
	$content = $templater->render();
}

// ############################################################################
// ###############################   MANAGE SIDEBAR  ##########################
// ############################################################################
if ($_REQUEST['do'] == 'sidebar')
{
	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

	if (!$show['editsidebar'])
	{
		print_no_permission();
	}

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}

	$blockorder = $vbulletin->userinfo['sidebar'];

	if ($vbulletin->userinfo['permissions']['vbblog_customblocks'] > 0)
	{
		$customblock = array();
		$customblocks = $db->query_read_slave("
			SELECT customblockid, title, type
			FROM " . TABLE_PREFIX . "blog_custom_block
			WHERE
				userid = " . $vbulletin->userinfo['userid'] . "
					AND
				type = 'block'
		");
		while ($blockholder = $db->fetch_array($customblocks))
		{
			$customblock["custom$blockholder[customblockid]"] = $blockholder;
			if (!isset($blockorder["custom$blockholder[customblockid]"]))
			{
				$blockorder["custom$blockholder[customblockid]"] = 1;
				$updateblockcache = true;
			}
		}
	}

	if ($updateblockcache)
	{	// housekeeping
		$dataman =& datamanager_init('Blog_User', $vbulletin, ERRTYPE_SILENT);
		if ($vbulletin->userinfo['bloguserid'])
		{
			$dataman->set_existing($vbulletin->userinfo);
		}
		else
		{
			$dataman->set('bloguserid', $vbulletin->userinfo['userid']);
		}
		$dataman->set('customblocks', count($customblock));
		$dataman->set('sidebar', $blockorder);
		$dataman->save();
	}

	$freeblocks = array();
	$sidebarblockbits = '';

	foreach ($vbulletin->bf_misc_vbblogblockoptions AS $key => $value)
	{
		if ($vbulletin->options['vbblog_blocks'] & $value)
		{
			if (!isset($blockorder["$key"]))
			{
				$freeblocks["$key"] = 1;
			}
		}
		else
		{
			unset($blockorder["$key"]);
		}
	}

	if (!empty($freeblocks))
	{
		$blockorder = array_merge($blockorder, $freeblocks);
	}

	if (!$vbulletin->options['vbblog_tagging'] AND $blockorder['block_tagcloud'])
	{
		unset($blockorder['block_tagcloud']);
	}
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cansearch']))
	{
		unset($blockorder['block_search']);
	}

	$blockchecked = array();
	$customblockcount = 0;
	$loop = 1;
	$position = 1;
	foreach ($blockorder AS $block => $status)
	{
		if (preg_match('#^custom#', $block))
		{
				if (!$customblock["$block"])
			 	{
			 		unset($blockorder["$block"]);
					continue;
				}
				else
				{
					$blockname = $customblock["$block"]['title'];
					$customblockid = $customblock["$block"]['customblockid'];
					$blockcount++;
				}
		}
		else
		{
			if (!$vbulletin->bf_misc_vbblogblockoptions["$block"])
			{
				continue;
			}
			else
			{
				$blockname = $vbphrase["$block"];
			}
		}

		$show['moveup'] = $show['movedown'] = false;
		if (count($blockorder) > 1)
		{
			if ($loop != count($blockorder))
			{
				$show['movedown'] = true;
			}
			if ($loop != 1)
			{
				$show['moveup'] = true;
			}
		}
		$blockchecked["$block"] = $status ? 'checked="checked"' : '';
		$loop++;
		$show['disableblock'] = (!$status);
		$show['customblock'] = (!empty($customblock["$block"]));
		$show['alignright'] = (!$show['moveup']);
		$templater = vB_Template::create('blog_cp_manage_sidebar_bit');
			$templater->register('block', $block);
			$templater->register('blockchecked', $blockchecked);
			$templater->register('blockname', $blockname);
			$templater->register('customblockid', $customblockid);
			$templater->register('position', $position);
		$sidebarblockbits .= $templater->render();
		$position++;
	}

	$show['addblock'] = ($blockcount < $vbulletin->userinfo['permissions']['vbblog_customblocks']);

	$navbits = array(
		'' => $vbphrase['blog_custom_blocks']
	);

	$templater = vB_Template::create('blog_cp_manage_sidebar');
		$templater->register('sidebarblockbits', $sidebarblockbits);
	$content = $templater->render();
}

// ############################################################################
// ################################   MANAGE PAGE  ############################
// ############################################################################
if ($_REQUEST['do'] == 'custompage')
{
	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

	if (!$show['editcustompage'])
	{
		print_no_permission();
	}

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}

	$pagecount = 0;
	$pagebits = '';
	$custompages = $db->query_read_slave("
		SELECT customblockid, title, type, location, displayorder
		FROM " . TABLE_PREFIX . "blog_custom_block
		WHERE
			userid = " . $vbulletin->userinfo['userid'] . "
				AND
			type = 'page'
		ORDER BY displayorder
	");
	while ($page = $db->fetch_array($custompages))
	{
		$pagecount++;
		$page['location_phrase'] = $vbphrase["blog_$page[location]"];
		$templater = vB_Template::create('blog_cp_manage_custompage_bit');
			$templater->register('page', $page);
		$pagebits .= $templater->render();
	}

	$show['addblock'] = ($pagecount < $vbulletin->userinfo['permissions']['vbblog_custompages']);
	$show['updateorder'] = ($pagecount > 0);

	$navbits = array(
		'' => $vbphrase['blog_custom_pages']
	);

	$templater = vB_Template::create('blog_cp_manage_custompage');
		$templater->register('pagebits', $pagebits);
	$content = $templater->render();
}

// ############################################################################
// ###############################   MANAGE SIDEBAR  ##########################
// ############################################################################
if ($_REQUEST['do'] == 'moveblock')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'block'    => TYPE_NOHTML,
		'hash'     => TYPE_STR,
		'dir'      => TYPE_NOHTML,
	));

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}

	if (!verify_security_token($vbulletin->GPC['hash'], $vbulletin->userinfo['securitytoken_raw']))
	{
		print_no_permission();
	}

	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

	$blockorder = $vbulletin->userinfo['sidebar'];

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
			if (!isset($blockorder["$key"]))
			{
				$freeblocks["$key"] = 1;
			}
		}
		else
		{
			unset($blockorder["$key"]);
		}
	}

	if (!empty($freeblocks))
	{
		$blockorder = array_merge($blockorder, $freeblocks);
	}

	if (count($blockorder) <= 1)
	{	// invalid choice
		$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'sidebar'));
		print_standard_redirect(array('redirect_blog_profileupdate',$vbulletin->userinfo['username']));
	}

	$output = array();
	$loop = 1;
	foreach($blockorder AS $block => $value)
	{
		if (preg_match('#^custom#', $block))
		{
				if (!$customblock["$block"])
			 	{
			 		unset($blockorder["$block"]);
					continue;
				}
		}
		else
		{
			if (!$vbulletin->bf_misc_vbblogblockoptions["$block"])
			{
				continue;
			}
		}

		if ($block == $vbulletin->GPC['block'])
		{
			if (
				($loop == 1 AND $vbulletin->GPC['dir'] == 'up')
					OR
				($loop == count($blockorder) AND $vbulletin->GPC['dir'] == 'down')
			)
			{ // invalid choice
				$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'sidebar'));
				print_standard_redirect(array('redirect_blog_profileupdate',$vbulletin->userinfo['username']));
			}
			if ($vbulletin->GPC['dir'] == 'up')
			{
				$tempvalue = array_pop($output);
				$output[] = $block;
				$output[] = $tempvalue;
			}
			else	// down
			{
				$holder = $vbulletin->GPC['block'];
				continue;
			}
		}
		else
		{
			$output[] = $block;
		}
		if ($holder)
		{
			$output[] = $holder;
			unset($holder);
		}
		$loop++;
	}
	if ($holder)
	{
		$output[] = $holder;
	}

	$finalblockorder = array();
	ksort($output, SORT_NUMERIC);
	foreach($output AS $value)
	{
		$finalblockorder["$value"] = $blockorder["$value"];
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

	$dataman->set('sidebar', $finalblockorder);
	$dataman->save();

	$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'sidebar'));
	print_standard_redirect(array('redirect_blog_profileupdate',$vbulletin->userinfo['username']));
}

// ############################################################################
// ###############################   UPDATE SIDEBAR  ##########################
// ############################################################################
if ($_POST['do'] == 'updatesidebar')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'block'    => TYPE_ARRAY_BOOL,
		'addblock' => TYPE_STR,
		'position' => TYPE_ARRAY_UINT,
		'type'     => TYPE_STR,
	));

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}

	if (!$vbulletin->GPC['addblock'])
	{
		if ($vbulletin->GPC['type'] == 'page')
		{
			if (!empty($vbulletin->GPC['position']))
			{
				$dataman =& datamanager_init('Blog_Custom_Block', $vbulletin, ERRTYPE_STANDARD);
				$dataman->set_info('user', $vbulletin->userinfo);
				$pages = $db->query_read_slave("
					SELECT displayorder, customblockid, type
					FROM " . TABLE_PREFIX . "blog_custom_block
					WHERE
						userid = " . $vbulletin->userinfo['userid'] . "
							AND
						type = 'page'
				");
				while ($page = $db->fetch_array($pages))
				{
					if ($page['displayorder'] != $vbulletin->GPC['position']["{$page['customblockid']}"])
					{
						$dataman->set_existing($page);
						$dataman->set('displayorder', $vbulletin->GPC['position']["{$page['customblockid']}"]);
						$dataman->save();
					}
				}

				$links = array();
				// Build datastore
				$pages = $db->query_read("
					SELECT customblockid, location, title
					FROM " . TABLE_PREFIX . "blog_custom_block
					WHERE
						userid = " . $vbulletin->userinfo['userid'] . "
							AND
						type = 'page'
							AND
						location <> 'none'
					ORDER BY displayorder
				");
				while ($page = $db->fetch_array($pages))
				{
					$links["$page[location]"][] = array(
						'i' => $page['customblockid'],
						't' => $page['title'],
					);
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

				$dataman->set('custompages', $links);
				$dataman->save();

			}

			$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'custompage'));
			print_standard_redirect(array('redirect_blog_profileupdate',$vbulletin->userinfo['username']));
		}
		else
		{
			$blockorder = $vbulletin->userinfo['sidebar'];

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
					if (!isset($blockorder["$key"]))
					{
						$freeblocks["$key"] = 1;
					}
				}
				else
				{
					unset($blockorder["$key"]);
				}
			}

			if (!empty($freeblocks))
			{
				$blockorder = array_merge($blockorder, $freeblocks);
			}

			$finalblockorder = array();
			foreach ($blockorder AS $block => $status)
			{
				if (preg_match('#^custom#', $block))
				{
						if (!$customblock["$block"])
					 	{
					 		unset($blockorder["$block"]);
							continue;
						}
				}
				else
				{
					if (!$vbulletin->bf_misc_vbblogblockoptions["$block"])
					{
						continue;
					}
				}

				$finalblockorder["$block"] = $vbulletin->GPC['block']["$block"] ? 1 : 0;
			}

			asort($vbulletin->GPC['position'], SORT_NUMERIC);
			foreach ($vbulletin->GPC['position'] AS $block => $value)
			{
				if (isset($finalblockorder["$block"]))
				{
					$vbulletin->GPC['position']["$block"] = $finalblockorder["$block"];
				}
				else
				{
					unset($vbulletin->GPC['position']);
				}
			}
			if (count($finalblockorder) == count($vbulletin->GPC['position']))
			{
				$finalblockorder = $vbulletin->GPC['position'];
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

			$dataman->set('sidebar', $finalblockorder);
			$dataman->save();

			$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'sidebar'));
			print_standard_redirect(array('redirect_blog_profileupdate',$vbulletin->userinfo['username']));
		}
	}
	else
	{
		$_REQUEST['do'] = 'modifyblock';
	}
}

// ############################################################################
// ###############################   UPDATE SIDEBAR  ##########################
// ############################################################################
if ($_POST['do'] == 'updateblock')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'customblockid'  => TYPE_UINT,
		'title'          => TYPE_NOHTML,
		'message'        => TYPE_STR,
		'wysiwyg'        => TYPE_BOOL,
		'preview'        => TYPE_STR,
		'disablesmilies' => TYPE_BOOL,
		'parseurl'       => TYPE_BOOL,
		'advanced'       => TYPE_BOOL,
		'type'           => TYPE_NOHTML,
		'location'       => TYPE_NOHTML,
	));

	$errors = array();
	if ($vbulletin->GPC['customblockid'])
	{
		$sidebarinfo = verify_blog_customblock($vbulletin->GPC['customblockid']);
		cache_permissions($sidebarinfo['userinfo'], false);
		$userinfo =& $sidebarinfo['userinfo'];

		if ($sidebarinfo['userid'] != $vbulletin->userinfo['userid'] AND !can_moderate_blog('caneditcustomblocks'))
		{
			print_no_permission();
		}
		if ($userinfo['userid'] == $vbulletin->userinfo['userid'])
		{
			if ($sidebarinfo['type'] == 'block' AND $userinfo['customblocks'] > $userinfo['permissions']['vbblog_customblocks'])
			{
				$errors[] = fetch_error('you_are_limited_to_x_blocks_delete_y_blocks', $userinfo['permissions']['vbblog_customblocks'], $userinfo['customblocks'] - $userinfo['permissions']['vbblog_customblocks']);
			}
			else if ($sidebarinfo['type'] == 'page')
			{
				$blocks = $db->query_first("
					SELECT COUNT(*) AS count
					FROM " . TABLE_PREFIX . "blog_custom_block
					WHERE
						userid = $userinfo[userid]
							AND
						type = 'page'
				");
				if ($blocks['count'] > $userinfo['permissions']['vbblog_custompages'])
				{
					$errors[] = fetch_error('you_are_limited_to_x_pages_delete_y_pages', $userinfo['permissions']['vbblog_custompages'], $blocks['count'] - $userinfo['permissions']['vbblog_custompages']);
				}
			}
		}
	}
	else
	{
		$userinfo =& $vbulletin->userinfo;
		if ($vbulletin->GPC['type'] == 'block' AND $userinfo['customblocks'] >= $userinfo['permissions']['vbblog_customblocks'])
		{
			print_no_permission();
		}
		else if ($vbulletin->GPC['type'] == 'page')
		{
			$blocks = $db->query_first("
				SELECT COUNT(*) AS count
				FROM " . TABLE_PREFIX . "blog_custom_block
				WHERE
					userid = $userinfo[userid]
						AND
					type = 'page'
			");
			if ($blocks['count'] >= $userinfo['permissions']['vbblog_custompages'])
			{
				print_no_permission();
			}
		}
	}

	// Sidebar
	$sidebar =& build_user_sidebar($userinfo, 0, 0, $rules);

	// unwysiwygify the incoming data
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$vbulletin->GPC['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowhtml']);
	}

	// parse URLs in message text
	if ($userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode'] AND $vbulletin->GPC['parseurl'])
	{
		require_once(DIR . '/includes/functions_newpost.php');
		$vbulletin->GPC['message'] = convert_url_to_bbcode($vbulletin->GPC['message']);
	}

	$customblock = $sidebarinfo;
	$customblock['title']          = $vbulletin->GPC['title'];
	$customblock['disablesmilies'] = $vbulletin->GPC['disablesmilies'];
	$customblock['parseurl']       = ($userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode'] AND $vbulletin->GPC['parseurl']);
	$customblock['message']        = $vbulletin->GPC['message'];
	$customblock['type']           = $vbulletin->GPC['type'];
	$customblock['location']       = $vbulletin->GPC['location'];

	$dataman =& datamanager_init('Blog_Custom_Block', $vbulletin, ERRTYPE_ARRAY);

	if ($sidebarinfo['customblockid'])
	{
		$dataman->set_existing($sidebarinfo);
		if (
			(
				(
					$sidebarinfo['type'] == 'block'
						AND
					!$userinfo['permissions']['vbblog_customblocks']
				)
					OR
				(
					$sidebarinfo['type'] == 'page'
						AND
					!$userinfo['permissions']['vbblog_custompages']
				)
			)
				AND
			!can_moderate_blog('caneditcustomblocks')
		)
		{
			print_no_permission();
		}
	}
	else
	{
		if (!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
		{
			print_no_permission();
		}
		$dataman->set('userid', $userinfo['userid']);
		$dataman->set('type', $customblock['type']);
	}

	$dataman->set('title', $customblock['title']);
	$dataman->set('pagetext', $customblock['message']);
	$dataman->set('allowsmilie', !$customblock['disablesmilies']);
	$dataman->set('location', $customblock['location']);
	$dataman->pre_save();

	$errors = array_merge($errors, $dataman->errors);

	if (!empty($errors))
	{
		define('POSTPREVIEW', true);
		$preview = construct_errors($errors);
		$previewpost = true;
		$_REQUEST['do'] = 'modifyblock';
	}
	else if ($vbulletin->GPC['preview'])
	{
		define('POSTPREVIEW', true);
		require_once(DIR . '/includes/blog_functions_post.php');
		$preview = process_blog_preview($customblock, 'entry');
		$_REQUEST['do'] = 'modifyblock';
	}
	else
	{
		$dataman->save();
		clear_autosave_text('vBBlog_BlogCustomBlock', $sidebarinfo['customblockid'] ? $sidebarinfo['customblockid'] : 0, $sidebarinfo['customblockid'] ? 0 : $userinfo['userid'], $vbulletin->userinfo['userid']);
		if ($dataman->fetch_field('type') == 'block')
		{
			if ($vbulletin->url == fetch_seo_url('forumhome|nosession', array()))
			{
				$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'sidebar'));
			}
			print_standard_redirect('redirect_blog_blockthanks');
		}
		else
		{
			if ($vbulletin->url == fetch_seo_url('forumhome|nosession', array()))
			{
				$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'custompage'));
			}
			print_standard_redirect('redirect_blog_pagethanks');
		}
	}
}

// ############################################################################
// ###############################   DELETE BLOCK    ##########################
// ############################################################################
if ($_POST['do'] == 'deleteblock')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'customblockid' => TYPE_UINT,
		'delete'        => TYPE_BOOL,
	));

	$sidebarinfo = verify_blog_customblock($vbulletin->GPC['customblockid']);
	cache_permissions($sidebarinfo['userinfo'], false);
	$userinfo =& $sidebarinfo['userinfo'];

	if ($sidebarinfo['userid'] != $vbulletin->userinfo['userid'] AND !can_moderate_blog('caneditcustomblocks'))
	{
		print_no_permission();
	}

	if ($sidebarinfo['type'] == 'block')
	{
		if (!$userinfo['permissions']['vbblog_customblocks'])
		{
			print_no_permission();
		}
		$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'sidebar'));
	}
	else
	{
		if (!$userinfo['permissions']['vbblog_custompages'])
		{
			print_no_permission();
		}
		if ($sidebarinfo['userid'] != $vbulletin->userinfo['userid'])
		{
			$vbulletin->url = fetch_seo_url('blog',  $sidebarinfo['userinfo']);
		}
		else
		{
			$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'custompage'));
		}
	}

	if ($vbulletin->GPC['delete'])
	{
		$dataman =& datamanager_init('Blog_Custom_Block', $vbulletin, ERRTYPE_STANDARD);
		$dataman->set_existing($sidebarinfo);
		$dataman->set_info('user', $userinfo);
		$dataman->delete();
		print_standard_redirect('redirect_custom_block_delete');
	}
	else
	{
		print_standard_redirect('redirect_custom_block_nodelete');
	}
}

// ############################################################################
// ###############################   MANAGE BLOCK    ##########################
// ############################################################################
if ($_REQUEST['do'] == 'modifyblock')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'customblockid' => TYPE_UINT,
		'type'          => TYPE_NOHTML,
	));

	if ($vbulletin->GPC['customblockid'])
	{
		$sidebarinfo = verify_blog_customblock($vbulletin->GPC['customblockid']);
		cache_permissions($sidebarinfo['userinfo'], false);
		$userinfo =& $sidebarinfo['userinfo'];

		if ($sidebarinfo['userid'] != $vbulletin->userinfo['userid'] AND !can_moderate_blog('caneditcustomblocks'))
		{
			print_no_permission();
		}

		if (
			(
				($sidebarinfo['type'] == 'block' AND !$userinfo['permissions']['vbblog_customblocks'])
					OR
				($sidebarinfo['type'] == 'page' AND !$userinfo['permissions']['vbblog_custompages'])
			)
				AND
			!can_moderate_blog('caneditcustomblocks')
		)
		{
			print_no_permission();
		}
		$type = $sidebarinfo['type'];
		if ($type == 'page')
		{
			$blocks = $db->query_first("
				SELECT COUNT(*) AS count
				FROM " . TABLE_PREFIX . "blog_custom_block
				WHERE
					userid = " . $userinfo['userid'] . "
						AND
					type = 'page'
			");
			$show['display_location'] = true;
		}
		if ($userinfo['userid'] == $vbulletin->userinfo['userid'])
		{
			$show['blogcp'] = true;
		}
	}
	else
	{
		if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
		{
			print_no_permission();
		}
		$type = $vbulletin->GPC['type'];
		if ($type == 'block' AND $vbulletin->userinfo['customblocks'] >= $vbulletin->userinfo['permissions']['vbblog_customblocks'])
		{
			print_no_permission();
		}
		else if ($type == 'page')
		{
			$blocks = $db->query_first("
				SELECT COUNT(*) AS count
				FROM " . TABLE_PREFIX . "blog_custom_block
				WHERE
					userid = " . $vbulletin->userinfo['userid'] . "
						AND
					type = 'page'
			");
			if ($blocks['count'] >= $vbulletin->userinfo['permissions']['vbblog_custompages'])
			{
				print_no_permission();
			}
			$show['display_location'] = true;
		}
		$userinfo =& $vbulletin->userinfo;
		$show['blogcp'] = true;
	}

	// Sidebar
	$sidebar =& build_user_sidebar($userinfo, 0, 0, $rules);

	#($hook = vBulletinHook::fetch_hook('blog_post_newentry_start')) ? eval($hook) : false;
	require_once(DIR . '/includes/functions_editor.php');
	require_once(DIR . '/includes/functions_newpost.php');

	if (defined('POSTPREVIEW'))
	{
		$title = $customblock['title'];
		$postpreview =& $preview;
		$customblock['message'] = htmlspecialchars_uni($customblock['message']);
		construct_checkboxes($customblock);
		$location["$customblock[location]"] = 'selected="selected"';
	}
	else
	{
		if ($sidebarinfo)
		{
			$customblock['message'] = htmlspecialchars_uni($sidebarinfo['pagetext']);
			$title = $sidebarinfo['title'];
			$location["$sidebarinfo[location]"] = 'selected="selected"';
		}
		else
		{
			$title = '';
			$customblock['message'] = '';
			$location['side'] = 'selected="selected"';
		}

		construct_checkboxes(
			array(
				'parseurl'       => true,
				'disablesmilies' => ($sidebarinfo AND !$sidebarinfo['allowsmilie'])
		));
	}

	if ($sidebarinfo)
	{
		$show['delete'] = true;
		$show['edit'] = true;
	}

	$editorid = construct_edit_toolbar(
		$customblock['message'],
		false,
		'blog_entry',
		$userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowsmilies'],
		true,
		false,
		'fe',
		'',
		array(),
		'content',
		'vBBlog_BlogCustomBlock',
		$sidebarinfo['customblockid'] ? $sidebarinfo['customblockid'] : 0,
		$sidebarinfo['customblockid'] ? 0 : $userinfo['userid'],
		defined('POSTPREVIEW'),
		true,
		'title'
	);
	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	if ($type == 'block')
	{
		$blocks['count'] = $userinfo['customblocks'];
		$blocklimit = $userinfo['permissions']['vbblog_customblocks'];
		$show['block'] = true;
	}
	else
	{
		$blocklimit = $userinfo['permissions']['vbblog_custompages'];
	}

	if ($userinfo['userid'] == $vbulletin->userinfo['userid'])
	{
		$navbits = ($type == 'block') ? array('' => $vbphrase['blog_custom_blocks']) : array('' => $vbphrase['blog_custom_pages']);
	}
	else
	{
		$navbitsdone = true;
		$navbits = array(
			fetch_seo_url('bloghome', array())  => $vbphrase['blogs'],
			fetch_seo_url('blog', $userinfo)  => $userinfo['blog_title'],
		);

		$navbits[] = ($type == 'block') ? $vbphrase['blog_custom_blocks'] : $vbphrase['blog_custom_pages'];
	}

	$show['parseurl'] = ($userinfo['permissions']['vbblog_entry_permissions'] & $vbulletin->bf_ugp_vbblog_entry_permissions['blog_allowbbcode']);
	$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));
	$show['additional_options'] = ($show['misc_options'] OR $show['display_location']);
	$url =& $vbulletin->url;

	$templater = vB_Template::create('blog_cp_manage_custom_editor');
		$templater->register('blocklimit', $blocklimit);
		$templater->register('blocks', $blocks);
		$templater->register('checked', $checked);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('editorid', $editorid);
		$templater->register('location', $location);
		$templater->register('messagearea', $messagearea);
		$templater->register('postpreview', $postpreview);
		$templater->register('sidebarinfo', $sidebarinfo);
		$templater->register('title', $title);
		$templater->register('type', $type);
		$templater->register('url', $url);
	$content = $templater->render();
}

// ############################################################################
// ###############################   UPDATE GROUP    ##########################
// ############################################################################
if ($_POST['do'] == 'adduser')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'username' => TYPE_NOHTML,
	));

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canhavegroupblog']))
	{
		print_no_permission();
	}

	if (!($userinfo = $db->query_first_slave("
		SELECT userid, username FROM " . TABLE_PREFIX . "user WHERE username = '" . $db->escape_string($vbulletin->GPC['username']) . "'
	")))
	{
		standard_error(fetch_error('invalid_user_specified'));
	}

	if ($vbulletin->userinfo['userid'] == $userinfo['userid'] OR $db->query_first_slave("
		SELECT userid FROM " . TABLE_PREFIX . "blog_groupmembership WHERE userid = $userinfo[userid] AND bloguserid = " . $vbulletin->userinfo['userid'] . "
	"))
	{
		standard_error(fetch_error('user_x_already_member_of_blog', $userinfo['username']));
	}

	$userinfo = fetch_userinfo($userinfo['userid']);
	cache_permissions($userinfo, false);
	if (!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canjoingroupblog']))
	{
		standard_error(fetch_error('user_x_can_not_join_blog', $userinfo['username']));
	}

	$ignorelist = array();
	if (trim($userinfo['ignorelist']))
	{
		$ignorelist = preg_split('/( )+/', trim($userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
	}

	if (!in_array($vbulletin->userinfo['userid'], $ignorelist))
	{
		// Send pm/email
		$cansendemail = (($userinfo['adminemail'] OR $userinfo['showemail']) AND $vbulletin->options['enableemail'] AND $vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canemailmember']);
		if ($cansendemail)
		{
			$touserinfo =& $userinfo;
			$fromuserinfo =& $vbulletin->userinfo;

			$blog_group_link = fetch_seo_url('blogusercp|js|bburl|nosession',  array(), array('do' => 'groups'));

			eval(fetch_email_phrases('blog_group_request_email', $touserinfo['languageid']));
			require_once(DIR . '/includes/class_bbcode_alt.php');
			$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());
			$plaintext_parser->set_parsing_language($touserinfo['languageid']);
			$message = $plaintext_parser->parse($message, 'privatemessage');
			vbmail($touserinfo['email'], $subject, $message);
		}

		$pending = 'pending';
	}
	else
	{
		$pending = 'ignored';
	}

	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "blog_groupmembership
			(bloguserid, userid, state, dateline)
		VALUES
			(" . $vbulletin->userinfo['userid'] . ", $userinfo[userid], '$pending', " . TIMENOW . ")
	");

	build_blog_pending_count($userinfo['userid']);
	build_blog_memberblogids($userinfo['userid']);
	build_blog_memberids($vbulletin->userinfo['userid']);

	$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'groups')) . '#member' . $userinfo['userid'];
	print_standard_redirect('redirect_blog_groupupdate');
}

// ############################################################################
// ###############################   MANAGE GROUPS   ##########################
// ############################################################################
if ($_POST['do'] == 'deleteuser')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'listbits' => TYPE_ARRAY_ARRAY,
	));

	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
	{
		print_no_permission();
	}
	if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canhavegroupblog']))
	{
		print_no_permission();
	}

	$remove = $clean_list = array();

	foreach ($vbulletin->GPC['listbits'] AS $type => $val)
	{
		$clean_list["$type"] = array_map('intval', array_keys($vbulletin->GPC['listbits']["$type"]));
	}

	foreach ($clean_list AS $type => $val)
	{
		if (sizeof($clean_list['user_original']) != sizeof($clean_list['user']))
		{
			$remove = array_merge($remove, array_diff($clean_list['user_original'], (is_array($clean_list['user']) ? $clean_list['user'] : array())));
		}
	}

	if (!empty($remove))
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_groupmembership
			WHERE bloguserid = " . $vbulletin->userinfo['userid'] . "
			AND userid IN (" . implode($remove, ', ') . ")
		");

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "blog
			SET
				postedby_userid = " . $vbulletin->userinfo['userid'] . ",
				postedby_username = '" . $db->escape_string($vbulletin->userinfo['username']) . "'
			WHERE
				userid = " . $vbulletin->userinfo['userid'] . "
					AND
				postedby_userid IN (" . implode($remove, ', ') . ")
		");

		build_blog_memberids($vbulletin->userinfo['userid']);

		foreach ($remove AS $userid)
		{
			build_blog_memberblogids($userid);
			build_blog_pending_count($userid);
		}
	}

	$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'groups')) . '#member' . $userinfo['userid'];
	print_standard_redirect('redirect_blog_groupupdate');
}

// ############################################################################
// ######################   VERIFY LEAVE/JOIN BLOG   ##########################
// ############################################################################
if ($_REQUEST['do'] == 'leaveblog' OR $_REQUEST['do'] == 'joinblog' OR $_POST['do'] == 'doleaveblog')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'bloguserid' => TYPE_UINT
	));

	if (
		!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers'])
			OR
		!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canjoingroupblog'])
	)
	{
		print_no_permission();
	}

	$inviteinfo = $db->query_first_slave("
		SELECT user.*, gm.state, bu.bloguserid,
			IF (bu.title <> '', bu.title, user.username) AS title
		FROM " . TABLE_PREFIX . "blog_groupmembership AS gm
		LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = gm.bloguserid)
		INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = gm.bloguserid)
		WHERE
			gm.bloguserid = " . $vbulletin->GPC['bloguserid'] . "
				AND
			gm.userid = " . $vbulletin->userinfo['userid'] . "
				AND
			gm.state <> 'ignored'
	");

	cache_permissions($inviteinfo, false);
	if (!$inviteinfo OR (!($inviteinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canhavegroupblog'])))
	{
		standard_error(fetch_error('invalidid', 'bloguserid', $vbulletin->options['contactuslink']));
	}
}

// ############################################################################
// ##############################   JOIN BLOG   ###############################
// ############################################################################

if ($_REQUEST['do'] == 'joinblog')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'hash' => TYPE_STR,
	));

	if ($inviteinfo['state'] != 'pending')
	{
		standard_error(fetch_error('already_member_of_blog_x', $inviteinfo['title']));
	}

	if (!VB_API AND !verify_security_token($vbulletin->GPC['hash'], $vbulletin->userinfo['securitytoken_raw']))
	{
		print_no_permission();
	}

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "blog_groupmembership
		SET state = 'active', dateline = " . TIMENOW . "
		WHERE
			userid = " . $vbulletin->userinfo['userid'] . "
				AND
			bloguserid = $inviteinfo[bloguserid]
	");

	build_blog_pending_count($vbulletin->userinfo['userid']);
	build_blog_memberblogids($vbulletin->userinfo['userid']);
	build_blog_memberids($inviteinfo['bloguserid']);

	$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'groups'));
	print_standard_redirect('redirect_blog_joined_blog');
}

// ############################################################################
// #########################  CONFIRM LEAVE BLOG   ############################
// ############################################################################

if ($_REQUEST['do'] == 'leaveblog')
{
	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

	$show['invite'] = ($inviteinfo['state'] == 'pending');
	$templater = vB_Template::create('blog_cp_manage_group_join');
		$templater->register('inviteinfo', $inviteinfo);
	$content = $templater->render();
}

// ############################################################################
// #############################   LEAVE BLOG   ###############################
// ############################################################################

if ($_POST['do'] == 'doleaveblog')
{
	if ($inviteinfo['state'] == 'ignored')
	{
		standard_error(fetch_error('invalidid', 'bloguserid', $vbulletin->options['contactuslink']));
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'bloguserid' => TYPE_UINT,
		'deny'       => TYPE_STR,
		'confirm'    => TYPE_STR,
	));

	if (!$_POST['confirm'])
	{
		$_REQUEST['do'] = 'groups';
	}
	else
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "blog_groupmembership
			WHERE
				bloguserid = $inviteinfo[bloguserid]
					AND
				userid = " . $vbulletin->userinfo['userid'] . "
		");

		build_blog_pending_count($vbulletin->userinfo['userid']);
		build_blog_memberblogids($vbulletin->userinfo['userid']);
		build_blog_memberids($inviteinfo['bloguserid']);

		$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'groups'));
		if ($inviteinfo['state'] == 'pending')
		{
			print_standard_redirect('redirect_blog_declined_group_blog', true, true);
		}
		else
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "blog
				SET
					postedby_userid = " . $vbulletin->userinfo['userid'] . ",
					postedby_username = '" . $db->escape_string($vbulletin->userinfo['username']) . "'
				WHERE
					userid = " . $vbulletin->userinfo['userid'] . "
						AND
					postedby_userid = $inviteinfo[userid]
			");

			print_standard_redirect('redirect_blog_left_group_blog', true, true);
		}
	}
}

// ############################################################################
// ###############################   MANAGE GROUPS   ##########################
// ############################################################################
if ($_REQUEST['do'] == 'groups')
{
	if (
		$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']
			AND
		$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canhavegroupblog']
	)
	{
		require_once(DIR . '/includes/functions_user.php');

		$members = $db->query_read_slave("
			SELECT user.*, gm.state
			" . ($vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width_thumb AS avwidth_thumb, customavatar.height_thumb AS avheight_thumb, customavatar.width as avwidth, customavatar.height as avheight, customavatar.filedata_thumb' : '') . "
			FROM " . TABLE_PREFIX . "blog_groupmembership AS gm
			INNER JOIN " . TABLE_PREFIX . "user AS user on (user.userid = gm.userid)
			" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid) " : '') . "
			WHERE bloguserid = " . $vbulletin->userinfo['userid'] . "
			ORDER BY gm.state, user.username
		");
		while ($member = $db->fetch_array($members))
		{
			fetch_avatar_from_userinfo($member, true);
			$show['pending'] = ($member['state'] != 'active');
			$templater = vB_Template::create('blog_cp_manage_group_userbit');
				$templater->register('member', $member);
			$memberlist .= $templater->render();
		}

		$membercount = $db->num_rows($members);
		$show['havemembers'] = ($membercount > 0);

		$showavatarchecked = ($vbulletin->userinfo['showavatars'] ? ' checked="checked"' : '');
		$show['avatars'] = $vbulletin->userinfo['showavatars'];
		$show['mymembers'] = true;
	}

	if (
		$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']
			AND
		$vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canjoingroupblog']
	)
	{
		$blogs = $db->query_read_slave("
			SELECT user.*, gm.bloguserid, gm.dateline, gm.state, IF (bu.title <> '', bu.title, user.username) AS title
			FROM " . TABLE_PREFIX . "blog_groupmembership AS gm
			LEFT JOIN " . TABLE_PREFIX . "blog_user AS bu ON (bu.bloguserid = gm.bloguserid)
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = gm.bloguserid)
			WHERE gm.userid = " . $vbulletin->userinfo['userid'] . " AND state IN ('active', 'pending')
			ORDER BY state
		");
		while ($blog = $db->fetch_array($blogs))
		{
			cache_permissions($blog, false);
			if (!($blog['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canhavegroupblog']))
			{
				continue;
			}

			$blog['joindate'] = vbdate($vbulletin->options['dateformat'], $blog['dateline']);
			$blog['jointime'] = vbdate($vbulletin->options['timeformat'], $blog['dateline']);
			$show['username'] = ($blog['title'] != $blog['username']);
			$show['pending'] = ($blog['state'] == 'pending');
			$templater = vB_Template::create('blog_cp_manage_group_blogbit');
				$templater->register('blog', $blog);
			$blogbits .= $templater->render();
		}

		$blogcount = $db->num_rows($blogs);

		$show['haveblogs'] = ($blogcount > 0);
		$show['myblogs'] = true;
	}

	if (!$show['mymembers'] AND !$show['myblogs'])
	{
		print_no_permission();
	}

	$navbits = array(
		'' => $vbphrase['blog_groups']
	);

	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

	$templater = vB_Template::create('blog_cp_manage_group');
		$templater->register('blogbits', $blogbits);
		$templater->register('memberlist', $memberlist);
		$templater->register('showavatarchecked', $showavatarchecked);
	$content = $templater->render();
}

// ############################################################################
// #############################   GROUP PERMISSION   #########################
// ############################################################################

if ($_REQUEST['do'] == 'userperm')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT,
	));

	$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true, 'flipgmperms');
	cache_permissions($userinfo, false);

	if (!($db->query_first_slave("
		SELECT userid
		FROM " . TABLE_PREFIX . "blog_groupmembership
		WHERE
			bloguserid = " . $vbulletin->userinfo['userid'] . "
				AND
			userid = $userinfo[userid]
	")))
	{
		print_no_permission();
	}

	if (
		!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
			OR
		!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canhavegroupblog'])
			OR
		!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canjoingroupblog'])
	)
	{
		print_no_permission();
	}

	$permbits = '';
	foreach($vbulletin->bf_misc_vbbloggrouppermissions AS $name => $value)
	{
		exec_switch_bg();
		$perm = array(
			'title'    => $vbphrase["blog_$name"],
			'varname'  => $name,
			'checkyes' => ($userinfo['grouppermissions'] & $value) ? 'checked="checked"' : '',
			'checkno'  => ($userinfo['grouppermissions'] & $value) ? '' : 'checked="checked"',
		);

		$templater = vB_Template::create('blog_cp_manage_group_permbit');
			$templater->register('bgclass', $bgclass);
			$templater->register('perm', $perm);
		$permbits .= $templater->render();
	}

	$navbits = array(
		fetch_seo_url('blogusercp', array(), array('do' => 'groups')) => $vbphrase['blog_groups'],
		'' => construct_phrase($vbphrase['group_permissions_for_x'], $userinfo['username']),
	);

	// add "Modify Permissions" to navbar

	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

	$templater = vB_Template::create('blog_cp_manage_group_perm');
		$templater->register('permbits', $permbits);
		$templater->register('userinfo', $userinfo);
	$content = $templater->render();
}

// ############################################################################
// #############################   GROUP PERMISSION   #########################
// ############################################################################

if ($_POST['do'] == 'updateuserperm')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'   => TYPE_UINT,
		'userperm' => TYPE_ARRAY_UINT,
	));

	$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true);
	cache_permissions($userinfo, false);

	if (!($db->query_first_slave("
		SELECT userid
		FROM " . TABLE_PREFIX . "blog_groupmembership
		WHERE
			bloguserid = " . $vbulletin->userinfo['userid'] . "
				AND
			userid = $userinfo[userid]
	")))
	{
		print_no_permission();
	}

	if (
		!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
			OR
		!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canhavegroupblog'])
			OR
		!($userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canjoingroupblog'])
	)
	{
		print_no_permission();
	}

	require_once(DIR . '/includes/functions_misc.php');
	$permissions = convert_array_to_bits($vbulletin->GPC['userperm'], $vbulletin->bf_misc_vbbloggrouppermissions);
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "blog_groupmembership
		SET permissions = $permissions
		WHERE userid = $userinfo[userid] AND bloguserid = " . $vbulletin->userinfo['userid'] . "
	");

	$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'groups'));
	print_standard_redirect('redirect_group_permissions_updated_successfully');
}

if ($_REQUEST['do'] == 'customize' OR $_POST['do'] == 'docustomize')
{
	if (
		!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
			OR
		!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_cancustomizeblog'])
	)
	{
		print_no_permission();
	}

	require_once(DIR . '/includes/class_usercss.php');
	require_once(DIR . '/includes/class_usercss_blog.php');

	$selector_base = array(
		'font_family'       => '',
		'font_size'         => '',
		'color'             => '',
		'background_color'  => '',
 		'background_image'  => '',
		'border_style'      => '',
		'border_color'      => '',
		'border_width'      => '',
		'linkcolor'         => '',
		'shadecolor'        => '',
		'padding'           => '',
		'background_repeat' => '',
	);

	$usercsspermissions = array(
		'caneditfontfamily' => $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditfontfamily'] ? true  : false,
		'caneditfontsize'   => $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditfontsize'] ? true : false,
		'caneditcolors'     => $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditcolors'] ? true : false,
		'caneditbgimage'    => ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_albums'] AND $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditbgimage']) ? true : false,
		'caneditborders'    => $vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_caneditborders'] ? true : false
	);

	$usercss = new vB_UserCSS_Blog($vbulletin, $vbulletin->userinfo['userid']);

	$allowedfonts = $usercss->build_select_option($vbulletin->options['vbblog_usercss_allowed_fonts']);
	$allowedfontsizes = $usercss->build_select_option($vbulletin->options['vbblog_usercss_allowed_font_sizes']);
	$allowedborderwidths = $usercss->build_select_option($vbulletin->options['vbblog_usercss_allowed_border_widths']);
	$allowedpaddings = $usercss->build_select_option($vbulletin->options['vbblog_usercss_allowed_padding']);

	$vbulletin->input->clean_array_gpc('p', array(
		'copyprofilecss' => TYPE_STR
	));

	if ($vbulletin->GPC['copyprofilecss'])
	{
		$_REQUEST['do'] = 'customize';
		$_POST['do'] = '';
	}
}

// #######################################################################
if ($_POST['do'] == 'docustomize')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usercss' => TYPE_ARRAY,
		'ajax'    => TYPE_BOOL // means preview
	));

	($hook = vBulletinHook::fetch_hook('blog_docustomize_start')) ? eval($hook) : false;

	foreach ($vbulletin->GPC['usercss'] AS $selectorname => $selector)
	{
		if (!isset($usercss->cssedit["$selectorname"]) OR !empty($usercss->cssedit["$selectorname"]['noinputset']))
		{
			$usercss->error[] = fetch_error('invalid_selector_name_x', $selectorname);
			continue;
		}

		if (!is_array($selector))
		{
			continue;
		}

		foreach ($selector AS $property => $value)
		{
			$prop_perms = $usercss->properties["$property"]['permission'];

			if (empty($usercsspermissions["$prop_perms"]) OR !in_array($property, $usercss->cssedit["$selectorname"]['properties']))
			{
				$usercss->error[] = fetch_error('no_permission_edit_selector_x_property_y', $selectorname, $property);
				continue;
			}

			unset($allowedlist);
			switch ($property)
			{
				case 'font_size':    $allowedlist = $allowedfontsizes; break;
				case 'font_family':  $allowedlist = $allowedfonts; break;
				case 'border_width': $allowedlist = $allowedborderwidths; break;
				case 'padding':      $allowedlist = $allowedpaddings; break;
			}

			if (isset($allowedlist))
			{
				if (!in_array($value, $allowedlist) AND $value != '')
				{
					$usercss->invalid["$selectorname"]["$property"] = ' usercsserror ';
					continue;
				}
			}

			$usercss->parse($selectorname, $property, $value);
		}
	}

	($hook = vBulletinHook::fetch_hook('blog_docustomize_process')) ? eval($hook) : false;

	if ($vbulletin->GPC['ajax'])
	{
		// AJAX means get the preview
		$effective_css = $usercss->build_css($usercss->fetch_effective());
		$effective_css = str_replace('/*sessionurl*/', $vbulletin->session->vars['sessionurl_js'], $effective_css);

		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('preview');
			$xml->add_tag('css', process_replacement_vars($effective_css));
		$xml->close_group();
		$xml->print_xml();
	}

	if (empty($usercss->error) AND empty($usercss->invalid))
	{
		$usercss->save();
		$vbulletin->url = fetch_seo_url('blogusercp',  array(), array('do' => 'customize'));
		print_standard_redirect('usercss_saved');
	}
	else if (!empty($usercss->error))
	{
		standard_error(implode("<br />", $usercss->error));
	}
	else
	{
		// have invalid, no errors
		$_REQUEST['do'] = 'customize';
		define('HAVE_ERRORS', true);
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'customize')
{
	$cssdisplayinfo = $usercss->build_display_array();
	$errors = '';

	// if we don't have errors, the displayed values are the existing ones
	// otherwise, use the form submission
	if (!defined('HAVE_ERRORS'))
	{
		if ($vbulletin->GPC['copyprofilecss'] AND ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_profile_styling']))
		{
			$profileusercss = new vB_UserCSS($vbulletin, $vbulletin->userinfo['userid']);
			$selectors_saved = $profileusercss->existing;
			$usercss_profile_preview = $profileusercss->build_css($profileusercss->fetch_effective());
			$usercss_profile_preview = str_replace('/*sessionurl*/', $vbulletin->session->vars['sessionurl_js'], $usercss_profile_preview);
			$usercss_profile_preview = process_replacement_vars($usercss_profile_preview);
			unset($profileusercss);
			define('VBBLOG_NOUSERCSS', true);
		}
		else
		{
			$selectors_saved = $usercss->existing;
		}
	}

	($hook = vBulletinHook::fetch_hook('blog_customize_start')) ? eval($hook) : false;

	$usercssbits = '';
	foreach ($cssdisplayinfo AS $selectorname => $selectorinfo)
	{
		$selector = $selector_base;

		$selectorinvalid = array();
		$field_names = array();

		$invalidpropertyphrases = array();

		if (empty($selectorinfo['properties']))
		{
			$selectorinfo['properties'] = $usercss->cssedit["$selectorname"]['properties'];
		}

		if (!is_array($selectorinfo['properties']))
		{
			continue;
		}

		$selector['phrase'] = $vbphrase["$selectorinfo[phrasename]"];

		foreach ($selectorinfo['properties'] AS $key => $value)
		{
			if (is_numeric($key))
			{
				$this_property = $value;
				$this_selector = $selectorname;
			}
			else
			{
				$this_property = $key;
				$this_selector = $value;
			}

			if (!$usercsspermissions[$usercss->properties["$this_property"]['permission']])
			{
				continue;
			}

			$field_names["$this_property"] = "usercss[$this_selector][$this_property]";

			if (defined('HAVE_ERRORS'))
			{
				if (isset($vbulletin->GPC['usercss']["$this_selector"]["$this_property"]))
				{
					$selector["$this_property"] = $vbulletin->GPC['usercss']["$this_selector"]["$this_property"];
				}

				if (isset($usercss->invalid["$this_selector"]["$this_property"]))
				{
					$selectorinvalid["$this_property"] = $usercss->invalid["$this_selector"]["$this_property"];

					$error_link_phrase = $vbphrase["usercss_$this_property"];
					$templater = vB_Template::create('modifyusercss_error_link');
						$templater->register('error_link_phrase', $error_link_phrase);
						$templater->register('selectorname', $selectorname);
						$templater->register('this_property', $this_property);
					$invalidpropertyphrases[] = $templater->render();
				}
			}
			else if (isset($selectors_saved["$this_selector"]["$this_property"]))
			{
				$selector["$this_property"] = $selectors_saved["$this_selector"]["$this_property"];
			}
		}

		if ($invalidpropertyphrases)
		{
			$invalid_properties_string = implode(", ", $invalidpropertyphrases);
			$templater = vB_Template::create('modifyusercss_error');
				$templater->register('invalid_properties_string', $invalid_properties_string);
				$templater->register('selector', $selector);
				$templater->register('selectorname', $selectorname);
			$errors .= $templater->render();
		}

		$show['textcolor'] = ($field_names['color'] OR $field_names['linkcolor'] OR $field_names['shadecolor']);
		$show['background'] = ($field_names['background_color'] OR $field_names['background_image']);
		$show['font'] = ($field_names['font_family'] OR $field_names['font_size']);
		$show['border'] = ($field_names['border_style'] OR $field_names['border_color'] OR $field_names['border_width'] OR $field_names['padding']);

		if ($field_names['font_family'])
		{
			$fontselect = '';
			foreach ($allowedfonts AS $key => $font)
			{
				$optionvalue = htmlspecialchars_uni($font);
				$optionclass = '';
				$optionselected = ($font == $selector['font_family'] ? ' selected="selected"' : '');
				$optiontitle = !empty($vbphrase["usercss_font_$key"]) ? $vbphrase["usercss_font_$key"] : $key;

				$fontselect .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}
		}

		if ($field_names['font_size'])
		{
			$fontsizeselect = '';
			foreach ($allowedfontsizes AS $key => $fontsize)
			{
				$optionvalue = htmlspecialchars_uni($fontsize);
				$optionclass = '';
				$optionselected = ($fontsize == $selector['font_size'] ? ' selected="selected"' : '');
				$optiontitle = !empty($vbphrase["usercss_fontsize_$key"]) ? $vbphrase["usercss_fontsize_$key"] : $key;

				$fontsizeselect .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}
		}

		if ($field_names['border_width'])
		{
			$borderwidthselect = '';
			foreach ($allowedborderwidths AS $key => $borderwidth)
			{
				$optionvalue = htmlspecialchars_uni($borderwidth);
				$optionclass = '';
				$optionselected = ($borderwidth == $selector["border_width"] ? ' selected="selected"' : '');
				$optiontitle = !empty($vbphrase["usercss_borderwidth_$key"]) ? $vbphrase["usercss_borderwidth_$key"] : $key;

				$borderwidthselect .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}
		}

		if ($field_names['background_image'])
		{
			if (!empty($selector['background_image']))
			{
				if (preg_match("/^([0-9]+),([0-9]+)$/", $selector['background_image'], $picture))
				{
					$selector['background_image'] = create_full_url("attachment.php?albumid=" . $picture[1] . "&attachmentid=" . $picture[2]);
				}
			}
		}

		if ($field_names['padding'])
		{
			$paddingselect = '';

			foreach ($allowedpaddings AS $key => $padding)
			{
				$optionvalue = htmlspecialchars_uni($padding);
				$optionclass = '';
				$optionselected = ($padding == $selector['padding'] ? ' selected="selected"' : '');
				$optiontitle = !empty($vbphrase["usercss_padding_$key"]) ? $vbphrase["usercss_padding_$key"] : $key;

				$paddingselect .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}
		}

		if ($field_names)
		{
			$border_style_selected = array(
				$selector['border_style'] => ' selected="selected"'
			);

			$repeat_selected = array(
				str_replace('-', '_', $selector['background_repeat']) => ' selected="selected"'
			);

			// make safe for display
			foreach ($selector AS $property => $selvalue)
			{
				$selector["$property"] = htmlspecialchars_uni($selvalue);
			}

			$selector['phrase'] = $vbphrase["$selectorinfo[phrasename]"];
			$selector['description'] = (isset($vbphrase["$selectorinfo[phrasename]_desc"]) ? $vbphrase["$selectorinfo[phrasename]_desc"] : '');

			($hook = vBulletinHook::fetch_hook('blog_customize_bit')) ? eval($hook) : false;

			$templater = vB_Template::create('modifyusercss_bit');
				$templater->register('borderwidthselect', $borderwidthselect);
				$templater->register('border_style_selected', $border_style_selected);
				$templater->register('field_names', $field_names);
				$templater->register('fontselect', $fontselect);
				$templater->register('fontsizeselect', $fontsizeselect);
				$templater->register('paddingselect', $paddingselect);
				$templater->register('repeat_selected', $repeat_selected);
				$templater->register('selector', $selector);
				$templater->register('selectorinvalid', $selectorinvalid);
				$templater->register('selectorname', $selectorname);
			$usercssbits .= $templater->render();
		}
	}

	if (!$usercssbits)
	{
		print_no_permission();
	}

	$types = vB_Types::instance();
	$contenttypeid = $types->getContentTypeID('vBForum_Album');

	$albumbits = '';
	$picturerowbits = '';
	$count = 0;
	$albums = array();
		$profilealbums = $db->query_read("
			SELECT
				album.title, album.albumid,
				a.dateline, a.attachmentid, a.caption,
				fd.filesize, fd.thumbnail_filesize, fd.thumbnail_dateline, fd.thumbnail_width, fd.thumbnail_height, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail
			FROM " . TABLE_PREFIX . "album AS album
			INNER JOIN " . TABLE_PREFIX . "attachment AS a ON (a.contentid = album.albumid)
			INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (fd.filedataid = a.filedataid)
			WHERE
				album.state IN ('profile', 'public')
					AND
				album.userid = " . $vbulletin->userinfo['userid'] . "
					AND
				a.state = 'visible'
					AND
				a.contenttypeid = $contenttypeid
			ORDER BY
				album.albumid, a.attachmentid
		");
	while ($album = $db->fetch_array($profilealbums))
	{
		$albums[$album['albumid']]['title'] = $album['title'];
		$albums[$album['albumid']]['pictures'][] = $album;
	}

	require_once(DIR . '/includes/functions_album.php');
	foreach ($albums AS $albumid => $info)
	{
		$picturebits = '';
		$show['backgroundpicker'] = true;
		$optionvalue = $albumid;

		// Need to shorten album titles here
		$optiontitle = "{$info['title']} (" . count($info['pictures']) . ")";
		$optionselected = empty($albumbits) ? 'selected="selected"' : '';
		$albumbits .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
		$show['hidediv'] = empty($picturerowbits) ? false : true;
		foreach($info['pictures'] AS $picture)
		{
			$picture['caption_preview'] = fetch_censored_text(fetch_trimmed_title(
				$picture['caption'],
				$vbulletin->options['album_captionpreviewlen']
			));
			//$picture['thumburl'] = ($picture['thumbnail_filesize'] ? fetch_picture_url($picture, $picture, true) : '');
			$picture['dimensions'] = ($picture['thumbnail_width'] ? "width=\"$picture[thumbnail_width]\" height=\"$picture[thumbnail_height]\"" : '');
			$templater = vB_Template::create('modifyusercss_backgroundbit');
				$templater->register('picture', $picture);
			$picturebits .= $templater->render();
		}

		$templater = vB_Template::create('modifyusercss_backgroundrow');
			$templater->register('albumid', $albumid);
			$templater->register('picturebits', $picturebits);
		$picturerowbits .= $templater->render();
	}

	$show['albumselect'] = (count($albums) == 1) ? false : true;

	$vbulletin->userinfo['blog_cachedcss'] = $usercss->build_css($usercss->fetch_effective());
	$vbulletin->userinfo['blog_cachedcss'] = str_replace('/*sessionurl*/', $vbulletin->session->vars['sessionurl_js'], $vbulletin->userinfo['blog_cachedcss']);

	$show['copyprofilecss'] = (!$vbulletin->GPC['copyprofilecss'] AND $vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_profile_styling']);

	$templater = vB_Template::create('modifyusercss_headinclude');
		$templater->register('usercss_string', $usercss_string);
	$headinclude .= $templater->render();

	$navbits[''] = $vbphrase['customize_profile'];

	// Sidebar
	$show['blogcp'] = true;
	$sidebar =& build_user_sidebar($vbulletin->userinfo, 0, 0, $rules);

	$templater = vB_Template::create('blog_cp_manage_usercss');
		$templater->register('albumbits', $albumbits);
		$templater->register('errors', $errors);
		$templater->register('picturerowbits', $picturerowbits);
		$templater->register('usercssbits', $usercssbits);
		$templater->register('usercsspermissions', $usercsspermissions);
	$content = $templater->render();
}

// #############################################################################
// spit out final HTML if we have got this far

// build navbar
if (empty($navbits))
{
	$navbits = array(
		fetch_seo_url('bloghome', array())  => $vbphrase['blogs']
	);
	if ($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
	{
		$navbits[fetch_seo_url('blog', $vbulletin->userinfo)] = $vbulletin->userinfo['blog_title'];
	}
	$navbits[''] = $vbphrase['blog_control_panel'];

}
else if (!$navbitsdone)
{
	$prenavbits = array(
		fetch_seo_url('bloghome', array())  => $vbphrase['blogs'],
	);
	if ($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown'])
	{
		$prenavbits[fetch_seo_url('blog', $vbulletin->userinfo)] = $vbulletin->userinfo['blog_title'];
	}
	$prenavbits[fetch_seo_url('blogusercp', array())] = $vbphrase['blog_control_panel'];
	$navbits = array_merge($prenavbits, $navbits);
}
$navbits = construct_navbits($navbits);

$navbar = render_navbar_template($navbits);

($hook = vBulletinHook::fetch_hook('blog_usercp_complete')) ? eval($hook) : false;

// CSS
$headinclude .= vB_Template::create('blog_css')->render();
$headinclude .= vB_Template::create('blog_cp_css')->render();

if ($_POST['do'] == 'docustomize' OR $_REQUEST['do'] == 'customize')
{
	$show['nousercss'] = true;
}

// shell template
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
|| # SVN: $Revision: 64580 $
|| ####################################################################
\*======================================================================*/
?>
