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
define('THIS_SCRIPT', 'blog_report');
define('CSRF_PROTECTION', true);
define('VBBLOG_PERMS', true);
define('VBBLOG_STYLE', true);
define('VBBLOG_SCRIPT', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'vbblogglobal',
	'vbblogcat',
	'messaging',
	'postbit',
);

// get special data templates from the datastore
$specialtemplates = array('blogcategorycache');

// pre-cache templates used by all actions
$globaltemplates = array(
	'BLOG',
	'blog_css',
	'blog_usercss',
	'blog_header_custompage_link',
	'blog_sidebar_calendar',
	'blog_sidebar_calendar_day',
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
	'blog_reportitem',
	'blog_sidebar_user',
	'blog_tag_cloud_link',
	'newpost_usernamecode',
	'ad_blogsidebar_start',
	'ad_blogsidebar_middle',
	'ad_blogsidebar_end',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/class_reportitem.php');
require_once(DIR . '/includes/class_reportitem_blog.php');
require_once(DIR . '/includes/blog_functions_post.php');

verify_blog_url();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

//check usergroup of user to see if they can use this
if (!$vbulletin->userinfo['userid'])
{
	print_no_permission();
}

$reportthread = ($rpforumid = $vbulletin->options['rpforumid'] AND $rpforuminfo = fetch_foruminfo($rpforumid));
$reportemail = ($vbulletin->options['enableemail'] AND $vbulletin->options['rpemail']);

if (!$reportthread AND !$reportemail)
{
	standard_error(fetch_error('emaildisabled'));
}

$navbits = array();
if ($blogid)
{
	$bloginfo = verify_blog($blogid);

	if ($blogtextinfo AND $blogtextinfo['blogtextid'] != $bloginfo['firstblogtextid'])
	{
		if (!fetch_comment_perm('canviewcomments', $bloginfo, $blogtextinfo))
		{
			print_no_permission();
		}

		$reportobj = new vB_ReportItem_Blog_Comment($vbulletin);
		$reportobj->set_extrainfo('blog', $bloginfo);
		$forminfo = $reportobj->set_forminfo($blogtextinfo);
	}
	else
	{
		$blogtextinfo = array();
		$bloginfo['blogtextid'] = $bloginfo['firstblogtextid'];
		$reportobj = new vB_ReportItem_Blog_Entry($vbulletin);
		$forminfo = $reportobj->set_forminfo($bloginfo);
	}

	if ($bloginfo['state'] == 'draft' OR $bloginfo['pending'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['blog'], $vbulletin->options['contactuslink']));
	}

	$bloginfo['title_trimmed'] = fetch_trimmed_title($bloginfo['title']);

	// draw nav bar
	if ($blogtextinfo)
	{
		$navbits[fetch_seo_url('entry', $bloginfo, array('bt' => $blogtextinfo['blogtextid']))] = $bloginfo['title'];
	}
	else
	{
		$navbits[fetch_seo_url('entry', $bloginfo)] = $bloginfo['title'];
	}
	$navbits[''] = $vbphrase['report_blog_entry'];
}
else
{
	$vbulletin->input->clean_array_gpc('r', array(
		'cp' => TYPE_UINT,
	));

	require_once(DIR . '/includes/blog_functions_usercp.php');
	$blockinfo = verify_blog_customblock($vbulletin->GPC['cp'], 'page');

	if (
			(
				$blockinfo['type'] == 'block'
					AND
				!$blockinfo['userinfo']['permissions']['vbblog_customblocks']
			)
				OR
			(
				$blockinfo['type'] == 'page'
					AND
				!$blockinfo['userinfo']['permissions']['vbblog_custompages']
			)
	)
	{
		print_no_permission();
	}

	$blockinfo['title_trimmed'] = fetch_trimmed_title($blockinfo['title']);

	$navbits[fetch_seo_url('blog', $blockinfo['userinfo'])] = $blockinfo['userinfo']['blog_title'];
	$navbits[fetch_seo_url('blogcustompage', $blockinfo)] = $blockinfo['title'];
	
	$navbits[] = $vbphrase['report_custom_page'];

	$reportobj = new vB_ReportItem_Blog_Custom_Page($vbulletin);
	$reportobj->set_extrainfo('user', $blockinfo['userinfo']);
	$forminfo = $reportobj->set_forminfo($blockinfo);
}

($hook = vBulletinHook::fetch_hook('blog_report_start')) ? eval($hook) : false;

$perform_floodcheck = $reportobj->need_floodcheck();

if ($perform_floodcheck)
{
	$reportobj->perform_floodcheck_precommit();
}

if (empty($_POST['do']))
{
	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	($hook = vBulletinHook::fetch_hook('blog_report_form_start')) ? eval($hook) : false;

	$url = $vbulletin->url;
	$templater = vB_Template::create('blog_reportitem');
		$templater->register('forminfo', $forminfo);
		$templater->register('url', $url);
		$templater->register('usernamecode', $usernamecode);
	$content = $templater->render();
}

if ($_POST['do'] == 'sendemail')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'reason'	=> TYPE_STR,
	));

	if ($vbulletin->GPC['reason'] == '')
	{
		standard_error(fetch_error('noreason'));
	}

	// trim the reason so it's not too long
	if ($vbulletin->options['postmaxchars'] > 0)
	{
		$trimmed_reason = substr($vbulletin->GPC['reason'], 0, $vbulletin->options['postmaxchars']);
	}
	else
	{
		$trimmed_reason = $vbulletin->GPC['reason'];
	}

	if ($perform_floodcheck)
	{
		$reportobj->perform_floodcheck_commit();
	}

	if ($blogid)
	{
		if ($blogtextinfo AND $blogtextinfo['blogtextid'] != $bloginfo['firstblogtextid'])
		{
			$reportobj->do_report($trimmed_reason, $blogtextinfo);
		}
		else
		{
			$reportobj->do_report($trimmed_reason, $bloginfo);
		}
	}
	else
	{
		$reportobj->do_report($trimmed_reason, $blockinfo);
	}

	print_standard_redirect('redirect_reportthanks');  
}

($hook = vBulletinHook::fetch_hook('blog_report_complete')) ? eval($hook) : false;

// build navbar
if (empty($navbits))
{
	$navbits[] = $vbphrase['blogs'];
}
else
{
	$navbits = array_merge(array(fetch_seo_url('bloghome', array()) => $vbphrase['blogs']), $navbits);
}

if ($blockinfo)
{
	$sidebar =& build_user_sidebar($blockinfo['userinfo']);
}
else
{
	$sidebar =& build_user_sidebar($bloginfo);
}

$navbits = construct_navbits($navbits);

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
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 53471 $
|| ####################################################################
\*======================================================================*/
?>
