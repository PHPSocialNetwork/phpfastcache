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
define('THIS_SCRIPT', 'tags');
define('CSRF_PROTECTION', true);
define('ALTSEARCH', true);


// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('inlinemod', 'search', 'prefix');

// get special data templates from the datastore
$specialtemplates = array(
	'tagcloud',
	'iconcache'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'humanverify',
	'optgroup',
	'search_common',
	'search_results',
	'search_results_postbit', // result from search posts
	'search_results_postbit_lastvisit',
	'threadbit', // result from search threads
	'threadbit_deleted', // result from deleted search threads
	'threadbit_announcement',
	'newreply_reviewbit_ignore',
	'threadadmin_imod_menu_thread',
	'threadadmin_imod_menu_post',
	'tag_cloud_link'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'cloud' => array(
		'tag_cloud_box',
		'tag_cloud_link',
		'tag_cloud_page'
	),
	'tag' => array(
		'tag_search',
		'threadadmin_imod_menu_thread',
		'threadbit',
		'search_resultlist',
		'search_threadbit'
	)
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

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/functions_forumdisplay.php');
require_once(DIR . '/includes/functions_search.php');
require_once(DIR . '/includes/functions_forumlist.php');
require_once(DIR . '/includes/functions_misc.php');
//new search stuff.
require_once(DIR . '/vb/search/core.php');
require_once(DIR . '/vb/legacy/currentuser.php');
require_once(DIR . '/vb/search/resultsview.php');


if (!$vbulletin->options['threadtagging'])
{
	print_no_permission();
}

($hook = vBulletinHook::fetch_hook('tags_start')) ? eval($hook) : false;

// #######################################################################
if ($_REQUEST['do'] == 'cloud')
{
	require_once(DIR . '/includes/functions_search.php');

	$tag_cloud = fetch_tagcloud('usage');

	$navbits = construct_navbits(array(
		'' => $vbphrase['tags'],
	));
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('tags_cloud_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('tag_cloud_page');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('tag_cloud', $tag_cloud);
	print_output($templater->render());
}

// #######################################################################
if ($_REQUEST['do'] == 'tag')
{

	$vbulletin->input->clean_array_gpc('r', array(
		'tag' => TYPE_NOHTML,
		'pagenumber' => TYPE_UINT,
		'perpage' => TYPE_UINT
	));

	if (!$vbulletin->GPC['tag'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['tag'], $vbulletin->options['contactuslink']));
	}

	$search_core = vB_Search_Core::get_instance();
	$current_user = new vB_Legacy_CurrentUser();

	$criteria = $search_core->create_criteria(vB_Search_Core::SEARCH_TAG);
	$criteria->add_tag_filter($vbulletin->GPC['tag']);

	$errors = $criteria->get_errors();
	if ($errors)
	{
		standard_error(fetch_error($errors[0]));
	}
	$results = null;
	$searchstart = microtime();
	if (!($vbulletin->GPC_exists['nocache'] AND $vbulletin->GPC['nocache']))
	{
		$results = vB_Search_Results::create_from_cache($current_user, $criteria);
	}

 	if (!$results)
	{
		$results = vB_Search_Results::create_from_criteria($current_user, $criteria, 
			$search_core->get_tag_search_controller());
	}

	$base =  'tags.php?' . $vbulletin->session->vars['sessionurl'] .
		'tag=' . $vbulletin->GPC['tag'] . '&amp;pp=' . $perpage;

	$navbits = array('search.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['search_forums']);
	$view = new vb_Search_Resultsview($results);
	$view->showpage($vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage'], $base, $navbits);
	exit;
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 54782 $
|| ####################################################################
\*======================================================================*/
