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
define('VB_PRODUCT', 'vbblog');
define('THIS_SCRIPT', 'blog_tag');
define('VBBLOG_PERMS', true);
define('VBBLOG_STYLE', true);
define('CSRF_PROTECTION', true);
define('VBBLOG_SCRIPT', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'vbblogglobal',
	'vbblogcat',
);

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'cloud' => array(
		'BLOG',
		'blog_css',
		'blog_usercss',
		'blog_sidebar_calendar',
		'blog_sidebar_calendar_day',
		'blog_sidebar_category_link',
		'blog_sidebar_comment_link',
		'blog_sidebar_custompage_link',
		'blog_sidebar_entry_link',
		'blog_sidebar_generic',
		'blog_sidebar_user',
		'blog_sidebar_user_block_archive',
		'blog_sidebar_user_block_category',
		'blog_sidebar_user_block_comments',
		'blog_sidebar_user_block_entries',
		'blog_sidebar_user_block_search',
		'blog_sidebar_user_block_tagcloud',
		'blog_sidebar_user_block_visitors',
		'blog_sidebar_user_block_custom',
		'blog_tag_cloud_box',
		'blog_tag_cloud_link',
		'blog_tag_cloud',
		'ad_blogsidebar_start',
		'ad_blogsidebar_middle',
		'ad_blogsidebar_end',
	),
);

if (empty($_REQUEST['do']))
{
	if (empty($_REQUEST['tag']))
	{
		$_REQUEST['do'] = 'cloud';
	}
	else
	{
		$_REQUEST['do'] = 'tag';
	}
}

if ($_REQUEST['do'] == 'cloud')
{
	$specialtemplates[] = 'blogtagcloud';
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/blog_functions_tag.php');
require_once(DIR . '/includes/blog_functions_post.php');

verify_blog_url();

if (!$vbulletin->options['vbblog_tagging'])
{
	print_no_permission();
}

($hook = vBulletinHook::fetch_hook('blog_tags_start')) ? eval($hook) : false;

// #######################################################################
if ($_REQUEST['do'] == 'cloud')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT,
	));

	if ($vbulletin->GPC['userid'])
	{
		$userinfo = fetch_userinfo($vbulletin->GPC['userid']);

		if (!$userinfo['canviewmyblog'])
		{
			print_no_permission();
		}

		if ($vbulletin->userinfo['userid'] == $userinfo['userid'] AND 
			!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewown']))
		{
			print_no_permission();
		}

		if ($vbulletin->userinfo['userid'] != $userinfo['userid'] AND 
			!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
		{
			// Can't view other's entries so off you go to your own blog.
			exec_header_redirect(fetch_seo_url('blog', $vbulletin->userinfo));
		}

		$show['usercloud'] = true;
		$tag_cloud = fetch_blog_tagcloud('usage', false, $userinfo['userid']);
	}
	else
	{
		$tag_cloud = fetch_blog_tagcloud('usage');
	}

	$navbits = construct_navbits(array(
		fetch_seo_url('bloghome', array()) => $vbphrase['blogs'],
		'' => $vbphrase['tags'],
	));
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('blog_tags_cloud_complete')) ? eval($hook) : false;

	if ($userinfo)
	{
		$sidebar =& build_user_sidebar($userinfo);
	}
	else
	{
		$sidebar =& build_overview_sidebar();
	}

	$templater = vB_Template::create('blog_tag_cloud');
		$templater->register('tag_cloud', $tag_cloud);
		$templater->register('tag_delimiters', $tag_delimiters);
		$templater->register('userinfo', $userinfo);
	$content = $templater->render();
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
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 26544 $
|| ####################################################################
\*======================================================================*/
?>
