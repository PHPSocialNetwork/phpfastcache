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
define('THIS_SCRIPT', 'search');
define('CSRF_PROTECTION', true);
define('ALTSEARCH', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('search', 'inlinemod', 'prefix', 'socialgroups', 'prefix', 'user');

// get special data templates from the datastore
$specialtemplates = array(
	'iconcache',
	'searchcloud',
	'routes'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'humanverify',
	'optgroup',
	'search_common',
	'search_resultlist',
	'search_results_postbit', // result from search posts
	'search_results_postbit_lastvisit',
	'search_results_forum',
	'search_results_visitormessage',
	'search_results_socialgroup_discussion',
	'search_results_socialgroup_message',
	'search_threadbit',
	'pagenav_curpage_window',
	'pagenav_pagelink_window',
	'pagenav_window',
	'threadbit', // result from search threads
	'threadbit_deleted', // result from deleted search threads
	'threadbit_announcement',
	'newreply_reviewbit_ignore',
	'threadadmin_imod_menu_thread',
	'threadadmin_imod_menu_post',
	'tag_cloud_link',
	'tag_cloud_box_search'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'intro' => array(
		'search_common_select_type',
		'search_input_post',
		'search_input_searchtypes',
		'tag_cloud_box',
	),
	'showresults' => array(
		'bbcode_video',
	),
);

if (empty($_REQUEST['do']))
{
	if (intval($_REQUEST['searchid']))
	{
		$_REQUEST['do'] = 'showresults';
	}
	else
	{
		$_REQUEST['do'] = 'intro';
	}
}

// ######################### REQUIRE BACK-END ############################
//error_reporting(E_ALL & ~E_NOTICE);
require_once('./global.php');
//old stuff,  we should start getting rid of this
require_once(DIR . '/includes/functions_forumlist.php');
require_once(DIR . '/includes/functions_misc.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/functions_forumdisplay.php');
require_once(DIR . '/includes/functions_search.php');
//error_reporting(E_ALL);

//new search stuff.
require_once(DIR . "/vb/search/core.php");
require_once(DIR . "/vb/legacy/currentuser.php");
require_once(DIR . "/vb/search/resultsview.php");
require_once(DIR . "/vb/search/searchtools.php");

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$search_core = vB_Search_Core::get_instance();
$current_user = new vB_Legacy_CurrentUser();

if (!$current_user->hasPermission('forumpermissions', 'cansearch'))
{
	print_no_permission();
}

if (!$vbulletin->options['enablesearches'])
{
	eval(standard_error(fetch_error('searchdisabled')));
}

// #############################################################################
$vbulletin->input->clean_array_gpc('r', array(
	'contenttypeid'  => TYPE_UINT,
	'contenttype'  => TYPE_STR,
	'type' 		  => TYPE_ARRAY
	));

if ($vbulletin->GPC_exists['type'] and !($vbulletin->GPC_exists['contenttypeid']))
{
	$vbulletin->GPC_exists['contenttypeid'] = true;
	$vbulletin->GPC['contenttypeid'] = $vbulletin->GPC['type'];
}
else if ($vbulletin->GPC_exists['contenttype'] and !($vbulletin->GPC_exists['contenttypeid']))
{
	if ($this_type = vB_Types::instance()->getContentTypeID($vbulletin->GPC['contenttype']))
	{
		$vbulletin->GPC_exists['contenttypeid'] = true;
		$vbulletin->GPC['contenttypeid'] = $this_type;
	}
}
if ($vbulletin->GPC_exists['contenttypeid'] and (count($vbulletin->GPC['contenttypeid'])
	 == 1) and is_array($vbulletin->GPC['contenttypeid']))
{
	$vbulletin->GPC['contenttypeid'] = $vbulletin->GPC['contenttypeid'][0];
}

//We may be passed a deactivated contenttypeid. We want to make sure we don't return
// any results. So let's check each type.
if ($vbulletin->GPC_exists['contenttypeid'])
{
	if (is_array($vbulletin->GPC['contenttypeid']))
	{
		foreach ($vbulletin->GPC['contenttypeid'] as $key => $contenttype)
		{
			try
			{
				vB_Types::instance()->assertContentType($contenttype);
			}
			catch(Exception $e)
			{
				//we don't need to pass anything back, we just unset this content type.
				unset($vbulletin->GPC['contenttypeid'][$key]);
			}
		}

		if (count($vbulletin->GPC['contenttypeid']) == 0)
		{
			unset($vbulletin->GPC['contenttypeid']);
			$vbulletin->GPC_exists['contenttypeid'] = false;
		}
	}
	else
	{
		try
		{
			vB_Types::instance()->assertContentType($vbulletin->GPC['contenttypeid']);
		}
		catch(Exception $e)
		{
			//we don't need to pass anything back, we just unset this content type.
			unset($vbulletin->GPC['contenttypeid']);
			$vbulletin->GPC_exists['contenttypeid'] = false;
		}
	}
}

if ($vbulletin->GPC_exists['contenttypeid'] and !(is_array($vbulletin->GPC['contenttypeid'])))
{
	//handle string forms of content types
	$vbulletin->GPC['contenttypeid'] = vB_Types::instance()->getContentTypeID($vbulletin->GPC['contenttypeid']);
	$search_type = $search_core->get_search_type_from_id($vbulletin->GPC['contenttypeid']);
}
else
{
	$search_type =  $search_core->get_search_type('vBForum', 'Common');
}

$globals = $search_type->listSearchGlobals();
$vbulletin->input->clean_array_gpc('r', $globals);

$vbulletin->input->clean_array_gpc('r', array(
	'doprefs'     => TYPE_NOHTML,
//	'searchtype'  => TYPE_BOOL,
	'searchid'    => TYPE_UINT,
	'quicksearch' => TYPE_UINT,
	'childforums' => TYPE_UINT
));

if ($vbulletin->GPC_exists['contenttypeid'])
{
	$prefs = vB_Search_Searchtools::searchIntroFetchPrefs($current_user, $vbulletin->GPC['contenttypeid']);
}
else
{
	$prefs = vB_Search_Searchtools::searchIntroFetchPrefs($current_user, vB_Search_Core::TYPE_COMMON);
}

// #############################################################################


// check for extra variables from the advanced search form
if (isset($_REQUEST['do']) AND $_REQUEST['do'] == 'process')
{
	// don't go to do=process, go to do=doprefs
	if ($vbulletin->GPC['doprefs'] != '')
	{
		$_POST['do'] = 'doprefs';
		$_REQUEST['do'] = 'doprefs';
	}
}


// workaround for 3.6 bug 1229 - 'find all threads started by x' + captcha
if ($_REQUEST['do'] == 'process' AND fetch_require_hvcheck('search') AND !isset($_POST['humanverify']))
{
	// guest user has come from a do=process link that does not include human verification
	$_REQUEST['do'] = 'intro';
}

// make first part of navbar
$navbits = array('search.php' . $vbulletin->session->vars['sessionrl_q'] => $vbphrase['search']);

$errors = array();

// #############################################################################
// floodcheck
if (in_array($_REQUEST['do'], array('intro', 'showresults', 'doprefs', 'getnew')) == false)
{
	$flood_result = $search_core->flood_check($current_user, IPADDRESS);

	if ($flood_result !== true)
	{
		if ($_REQUEST['do'] == 'process')
		{
			$errors[] = $flood_result;
		}
		else
		{
			eval(standard_error(fetch_error($flood_result)));
		}
	}
}

// #############################################################################
// allows an alternative processing branch to be executed
($hook = vBulletinHook::fetch_hook('search_before_process')) ? eval($hook) : false;

// #############################################################################
if ($_REQUEST['do'] == 'process')
{
	$vbulletin->input->clean_array_gpc('r', $globals);
	//Now let's do some input cleanup before we start preparing the query.

	($hook = vBulletinHook::fetch_hook('search_process_start')) ? eval($hook) : false;

	if (!$vbulletin->options['threadtagging'])
	{
		//  tagging disabled, don't let them search on it
		$vbulletin->GPC['tag'] = '';
	}

	if ($vbulletin->GPC['userid'] AND $userinfo = fetch_userinfo($vbulletin->GPC['userid']))
	{
		$vbulletin->GPC_exists['searchuser'] = true;
		$vbulletin->GPC['searchuser'] = unhtmlspecialchars($userinfo['username']);
	}

	if (fetch_require_hvcheck('search'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verify =& vB_HumanVerify::fetch_library($vbulletin);

		if (!$verify->verify_token($vbulletin->GPC['humanverify']))
		{
			$errors[] = $verify->fetch_error();
		}
	}

	if ($vbulletin->GPC['searchthreadid'])
	{
		$vbulletin->GPC['sortby'] = 'dateline';
		$vbulletin->GPC['sortorder'] = 'ASC';
		$vbulletin->GPC['showposts'] = true;

		$vbulletin->GPC['starteronly'] = false;
		$vbulletin->GPC['titleonly'] = false;

	}

	// if searching for only a tag, we must show results as threads
	if ($vbulletin->GPC['tag'] AND empty($vbulletin->GPC['query']) AND empty($vbulletin->GPC['searchuser']))
	{
		$vbulletin->GPC['showposts'] = false;
	}

	//do this even if the hv check fails to make sure that the user sees any errors
	//nothing worse then typing in a capcha five times only to get a message saying
	//fix something and type it in again.

	if ($vbulletin->GPC['contenttypeid'] and !is_array($vbulletin->GPC['contenttypeid']))
	{
		$criteria = $search_core->create_criteria(vB_Search_Core::SEARCH_ADVANCED);
		$criteria->set_advanced_typeid($vbulletin->GPC['contenttypeid']);
	}
	else
	{
		$criteria = $search_core->create_criteria(vB_Search_Core::SEARCH_COMMON);
	}
	set_criteria_from_vbform($current_user, $criteria);
	$search_type->add_advanced_search_filters($criteria, $vbulletin);
	//allow parameters include and exclude to includeor exclude specific forums in a search query.
	$vbulletin->input->clean_array_gpc('r', array(
		'exclude'    => TYPE_NOHTML,
		'include'    => TYPE_NOHTML
	));
	set_newitem_forums($criteria);

	//caputure the search form values for backreferencing
	$searchterms = array();
	foreach ($globals AS $varname => $value)
	{
		if (
			!$vbulletin->GPC_exists[$varname] OR
			(in_array($value, array(TYPE_ARRAY, TYPE_ARRAY_NOHTML)) AND
			!is_array($vbulletin->GPC[$varname]))
		)
		{
			continue;
		}
		$searchterms[$varname] = $vbulletin->GPC[$varname];
	}
	$criteria->set_search_terms($searchterms);

	$errors = array_merge($errors, $criteria->get_errors());
	if ($errors)
	{
		do_intro($current_user, $globals, $navbits, $search_type, $errors);
		exit;
	}
	$results = null;

	if (!($vbulletin->debug OR ($vbulletin->GPC_exists['nocache'] AND $vbulletin->GPC['nocache'])))
	{
		$results = vB_Search_Results::create_from_cache($current_user, $criteria);
	}

	if (!$results)
	{
		$results = vB_Search_Results::create_from_criteria($current_user, $criteria);
	}
	if (!VB_API)
	{
		$vbulletin->url = 'search.php?' . $vbulletin->session->vars['sessionurl'] .
			"searchid=" . $results->get_searchid();
	}
	else
	{
		$show['searchid'] = $results->get_searchid();
	}
	print_standard_redirect('search');  
	exit;
}


($hook = vBulletinHook::fetch_hook('search_start')) ? eval($hook) : false;

// #############################################################################
if ($_REQUEST['do'] == 'intro')
{
	do_intro($current_user, $globals, $navbits, $search_type);
	exit;
}

// #############################################################################

if ($_REQUEST['do'] == 'showresults')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT,
		'perpage'    => TYPE_UINT
	));
	
	$results = vB_Search_Results::create_from_searchid($current_user, $vbulletin->GPC['searchid']);

	$base = 'search.php?' . $vbulletin->session->vars['sessionurl'] .
		'searchid=' . $vbulletin->GPC['searchid'] . '&amp;pp=' . $perpage;

	//note that show page will handle blank results just fine
	if ($results AND ($criteria = $results->get_criteria()) AND $criteria->get_searchtype() == vB_Search_Core::SEARCH_NEW)
	{
		$navbits = array(
			'search.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['search'],
			'' => $vbphrase['new_posts_nav']
		);
	}
	else
	{
		$navbits = array(
			'search.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['search'],
			'' => $vbphrase['search_results']
		);
	}
	$show['popups'] = true;
	$view = new vb_Search_Resultsview($results);
	$view->showpage($vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage'], $base, $navbits);
}

// #############################################################################
if ($_REQUEST['do'] == 'getnew' OR $_REQUEST['do'] == 'getdaily')
{
	//f is an auto variable that is already registered.  We include it here for
	//clarity and to guard against the day that we don't automatically process
	//the forum/thread/post variables on init
	$vbulletin->input->clean_array_gpc('r', array(
		'f'					 => TYPE_UINT,
		'days'       => TYPE_UINT,
		'exclude'    => TYPE_NOHTML,
		'include'    => TYPE_NOHTML,
		'showposts'  => TYPE_BOOL,
		'oldmethod'  => TYPE_BOOL,
		'sortby'     => TYPE_NOHTML,
		'noannounce' => TYPE_BOOL,
		'contenttype' => TYPE_NOHTML,
		'type' => TYPE_STR
	));

	$criteria = $search_core->create_criteria(vB_Search_Core::SEARCH_NEW);

	if ($vbulletin->GPC_exists['contenttypeid'])
	{
		$type = $vbulletin->GPC['contenttypeid'];
	}
	else if ($vbulletin->GPC_exists['contenttype'])
	{
		$type = $vbulletin->GPC['contenttype'];
	}
	else
	{
		//default to post to preserve existing links
		$type = 'vBForum_Post';
	}

	($hook = vBulletinHook::fetch_hook('search_getnew_start')) ? eval($hook) : false;

	$type = vB_Types::instance()->getContentTypeID($type);
	if (!$type)
	{
		//todo, do we need a seperate error for this?
		eval(standard_error(fetch_error('searchnoresults', ''), '', false));
	}

	//hack, we have a getnew controller for events, but they are not actually
	//indexed.  For now we need to skip the search backlink for events because
	//there isn't anywhere for them to go.
	if ($type <> vB_Types::instance()->getContentTypeID('vBForum_Event'))
	{
		$searchterms['searchdate'] = $_REQUEST['do'] == 'getnew' ? 'lastvisit' : 1;
		$searchterms['contenttypeid'] = $type;
		$searchterms['search_type'] = 1;
		$searchterms['showposts'] = $vbulletin->GPC['showposts'];

		$criteria->set_search_terms($searchterms);
	}

	$criteria->add_contenttype_filter($type);
	$criteria->set_grouped($vbulletin->GPC['showposts'] ?
		vB_Search_Core::GROUP_NO : vB_Search_Core::GROUP_YES);

	//set critieria and sort
	set_newitem_forums($criteria);
	set_newitem_date($criteria, $current_user, $_REQUEST['do']);
	set_getnew_sort($criteria, $vbulletin->GPC['sortby']);


	($hook = vBulletinHook::fetch_hook('search_getnew_process')) ? eval($hook) : false;

	//check for any errors
	$errors = $criteria->get_errors();

	if ($errors)
	{
		standard_error(fetch_error($errors[0]));
	}

	try
	{
		$search_controller = $search_core->get_newitem_search_controller_by_id($type);
	}
	catch (Exception $e)
	{
		eval(standard_error(fetch_error('searchnoresults', ''), '', false));
	}

	$results = vB_Search_Results::create_from_criteria($current_user, $criteria, $search_controller);

	//get a page with one result to check if we actually have any.
	$first_result = $results->get_page(1,1,1);

	if (!$first_result)
	{
		if ($_REQUEST['do'] == 'getnew')
		{
			// Pulling the entire $contenttype in consideration of future development
			// ex. searchnoresults_getnew_vBForum_SocialGroupMessage instead of searchnoresults_getnew_SocialGroupMessage
			// Incase a distinction needs to be made between products.
			$contenttypename = $vbulletin->GPC['contenttype'];
			$contenttypephrase = 'searchnoresults_getnew_'.$contenttypename;
			$phraseexists = strpos(fetch_phrase($contenttypephrase, 'error', '', false), 'Could not find phrase');
			// Phrase exists
			if ($phraseexists === false)
			{
   				eval(standard_error(fetch_error($contenttypephrase, $vbulletin->session->vars['sessionurl'], '&amp;contenttype='.$contenttypename), '', false));
			}
			// Phrase doesn't exist, so display generic 'items' phrase
			else
			{
    			eval(standard_error(fetch_error('searchnoresults_getnew_Items', $vbulletin->session->vars['sessionurl']), '', false));
			}
		}
		else
		{
			eval(standard_error(fetch_error('searchnoresults', ''), '', false));
		}
	}

	if (!VB_API)
	{
		$vbulletin->url = 'search.php?' . $vbulletin->session->vars['sessionurl'] .
			"searchid=" . $results->get_searchid();
	}
	else
	{
		$show['searchid'] = $results->get_searchid();
	}

	($hook = vBulletinHook::fetch_hook('search_getnew_complete')) ? eval($hook) : false;

	print_standard_redirect('search');  
	exit;
}

// #############################################################################
if ($_REQUEST['do'] == 'finduser')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'type'			  => TYPE_ARRAY_NOHTML,
		'userid'	      => TYPE_UINT,
		'starteronly'    => TYPE_BOOL,
		'forumchoice'    => TYPE_ARRAY,
		'childforums'    => TYPE_BOOL,
		'postuserid' 	  => TYPE_UINT,
		'searchthreadid' => TYPE_UINT,
	));

	// valid user id?
	if (!$vbulletin->GPC_exists['userid'] and !$vbulletin->GPC_exists['postuserid'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink']));
	}

	($hook = vBulletinHook::fetch_hook('search_finduser_start')) ? eval($hook) : false;

	//default to posts
	if (! $vbulletin->GPC_exists['contenttypeid'])
	{
		$vbulletin->GPC['contenttypeid'] = vB_Types::instance()->getContentTypeID('vBForum_Post');
	}

	if ($vbulletin->GPC['contenttypeid'] and !is_array($vbulletin->GPC['contenttypeid']))
	{
		$criteria = $search_core->create_criteria(vB_Search_Core::SEARCH_ADVANCED);
		$criteria->set_advanced_typeid($vbulletin->GPC['contenttypeid']);
	}
	else
	{
		$criteria = $search_core->create_criteria(vB_Search_Core::SEARCH_COMMON);
	}

	set_criteria_from_vbform($current_user, $criteria);
	$search_type->add_advanced_search_filters($criteria, $vbulletin);
	//allow parameters include and exclude to includeor exclude specific forums in a search query.
	$vbulletin->input->clean_array_gpc('r', array(
		'exclude'    => TYPE_NOHTML,
		'include'    => TYPE_NOHTML
	));
	set_newitem_forums($criteria);

	$criteria->set_sort($criteria->switch_field('dateline'), 'desc');

	$errors = $criteria->get_errors();

	if ($errors)
	{
		standard_error(fetch_error($errors[0]));
	}
	$results = null;

	if (!($vbulletin->debug OR ($vbulletin->GPC_exists['nocache'] AND $vbulletin->GPC['nocache'])))
	{
		$results = vB_Search_Results::create_from_cache($current_user, $criteria);
	}

	if (!$results)
	{
		$results = vB_Search_Results::create_from_criteria($current_user, $criteria);
	}

	($hook = vBulletinHook::fetch_hook('search_finduser_complete')) ? eval($hook) : false;

	if (!VB_API)
	{
		$vbulletin->url = 'search.php?' . $vbulletin->session->vars['sessionurl'] .
			"searchid=" . $results->get_searchid();
	}
	else
	{
		$show['searchid'] = $results->get_searchid();
	}
	print_standard_redirect('search');  
}

// #############################################################################
if ($_POST['do'] == 'doprefs')
{
	$vbulletin->input->clean_array_gpc('r',
		$globals
	);

	if (!$current_user->isGuest())
	{
		if (isset($vbulletin->GPC['contenttypeid']))
		{
			$typeid = $vbulletin->GPC['contenttypeid'];
		}
		else
		{
			$typeid = vB_Search_Core::TYPE_COMMON;
		}


		check_save_prefs($current_user, $vbulletin->GPC_exists['contenttypeid'] ?
			$vbulletin->GPC['contenttypeid'] : vB_Search_Core::TYPE_COMMON);

		// save preferences
//		check_save_prefs($current_user, $typeid);
	}

	//this only gets used for the non ajax redirect.  Do we really need it here?
	//the only reason not to move it is that that would put it after the hook call...
	$vbulletin->url = 'search.php?' . $vbulletin->session->vars['sessionurl'];

	if (!empty($globals))
	{
		$vbulletin->url .= make_query_string($vbulletin->GPC, array_keys($globals));
	}

	($hook = vBulletinHook::fetch_hook('search_doprefs_complete')) ? eval($hook) : false;


	//The prior code only set this to true when we actuallly clear prefs.  However that will
	//give the save prefs message when we attempt to clear prefs that don't exist.
	//Unless we attempt to provide details on what actions did or did not take place (and its not that important),
	//we should should respond to the user with the message for the action requested
	//The only real weirdness is that we respond like we did something for guests, which cannot
	//save prefs.  However, they should never be given the option, so its not a problem.
	$clearprefs = !$vbulletin->GPC['saveprefs'];
	if (!$vbulletin->GPC['ajax'])
	{
		print_standard_redirect($clearprefs ? 'search_preferencescleared' : 'search_preferencessaved', true, true);  
	}
	else
	{
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_tag('message', fetch_phrase($clearprefs ? 'redirect_search_preferencescleared' :
			'redirect_search_preferencessaved', 'frontredirect', 'redirect_'));
		$xml->print_xml();
	}

	//try to enforce some kind of modularity to actions.
	exit;
}

// #############################################################################
// finish off the page

if ($templatename != '')
{
	throw new Exception ("we shouldn't be here.  If we are fix it");
}
exit;

// #############################################################################
// Misc support functions functions


/**
*	Collapse an array into a query string
*
*	values are urlencode, fields names aren't.  Resulting string should be considered
*
*	@param array $fields map of $varname => value to encode to query string
* @param array $keys if provided, an array of key names to enocode from $fields.  By
*		default all values in $fields are used
*/
function make_query_string($fields, $keys = false)
{
	if (!$keys)
	{
		$keys = array_keys($fields);
	}

	$chunks = array();
	foreach ($keys AS $varname)
	{
		if (isset($fields[$key]))
		{
			if (is_array($vbulletin->GPC["$varname"]))
			{
				foreach ($vbulletin->GPC["$varname"] AS $_cleanme)
				{
					$chunks[] = $varname . '[]=' . urlencode($_cleanme);
				}
			}
			else
			{
				$chunks[] = $varname . '=' . urlencode($vbulletin->GPC["$varname"]);
			}
		}
	}
	return implode('&amp;', $chunks);
}

/**
 * Display the main search form.
 *
 * @param vB_Legacy_Current_User $user  The current user for the board.
 * @param array $globals The array of "global" GPC items and their type defaults
 * @param array $navbits The navbit array
 * @param array $errors A list of errors to display, used for redisplaying the form on error
 * @param array $searchterms A map of form fields posted by the user.  Used to propogate the
 *		the form when processing fails.
 */
function do_intro($user, $globals, $navbits, $search_type, $errors = array(), $searchterms = array())
{
	global $vbulletin, $vbphrase, $prefs, $searchforumids, $show;

	$search_core = vB_Search_Core::get_instance();

	if ($vbulletin->GPC['search_type']
		OR intval($vbulletin->GPC['searchthreadid'])
		OR (isset($vbulletin->GPC['searchfromtype'])
			and strlen($vbulletin->GPC['searchfromtype']) > 6))
	{
		if($vbulletin->options['hv_type'] == 'Recaptcha')
		{
			$show['init_recaptcha'] = true;
		}
		
		$template = vB_Template::create('search_common_select_type');
	}
	else
	{
		$template = vB_Template::create('search_common');

		// we only need the list of content types if we're doing the generic
		$template->register('type_options', vB_Search_Searchtools::get_type_options());
		if (!$prefs['type']) $prefs['type'] = array();
		$template->register('selectedtypes', $prefs['type']);
	}

	search_intro_register_prefs($template, $prefs);
	vB_Search_Searchtools::searchIntroRegisterHumanVerify($template);

	//actually render any errors.
	search_intro_register_errors($template, $errors);
	$show['errors'] = !empty($errors);
	$show['tag_option'] = $vbulletin->options['threadtagging'];

	//check to see if we have a preferred type
	$defaulttype = null;

	if ($prefs['type'])
	{
		if (is_array($prefs['type']))
		{
			$defaulttype = $prefs['type'][0];
		}
		else
		{
			$defaulttype = $prefs['type'];
		}
	}

	if ($vbulletin->GPC['contenttypeid'])
	{
		$defaulttype = $vbulletin->GPC['contenttypeid'];
	}

	//If we have nothing else, let's show Posts
	if ($defaulttype == null)
	{
		$defaulttype = $search_core->get_contenttypeid('vBForum', 'Post');
	}

	//If we have the common type, set to the default
	if ($search_type instanceof vBForum_Search_Type_Common)
	{
		unset($search_type);
		$search_type = $search_core->get_search_type_from_id($defaulttype);
		$prefs = vB_Search_Searchtools::searchIntroFetchPrefs($user, $defaulttype);
	}

	if ($vbulletin->GPC['search_type'] )
	{
		$template->register('input_search_types', vB_Search_Searchtools::listSearchable('vb_search_params',
			 'search.php', $prefs, $defaulttype));

		if (intval($vbulletin->GPC['contenttypeid']) OR $defaulttype)
		{
			$template->register('search_ui', $search_type->listUi($prefs,
				intval($vbulletin->GPC['contenttypeid']) ?
				$vbulletin->GPC['contenttypeid'] : $defaulttype));
		}
	}
	else if (isset($vbulletin->GPC['searchfromtype'])
		AND strlen($vbulletin->GPC['searchfromtype']) > 6)
	{
		$template->register('input_search_types', vB_Search_Searchtools::listSearchable('vb_search_params',
			 'search.php', $prefs, $defaulttype));
		$search_type = explode(':', $vbulletin->GPC['searchfromtype'], 2);

		if (count($search_type) == 2)
		{
			$search_type = $search_core->get_search_type($search_type[0], $search_type[1]);
		}
		else if (intval($vbulletin->GPC['contenttypeid']))
		{
			$search_type = $search_core->get_search_type_from_id($vbulletin->GPC['contenttypeid']);
		}
		if (isset($search_type))
		{
			$template->register('search_ui', $search_type->listUi($prefs,
				$search_type->get_contenttypeid()));
		}
	}

	$template->register('sessionhash', $sessionhash);
	search_intro_register_tagcloud($template);

	if ($vbulletin->debug)
	{
		$show['nocache'] = true;
	}

	// unlink the 'search' part of the navbits
	array_pop($navbits);
	$navbits[''] = $vbphrase['advanced_search'];
	($hook = vBulletinHook::fetch_hook('search_intro')) ? eval($hook) : false;

	//finish off search
	($hook = vBulletinHook::fetch_hook('search_complete')) ? eval($hook) : false;

	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);
	$template->register('show', $show);
	$template->register('navbar', $navbar);
	$template->register_page_templates();

	print_output($template->render());
}

function set_criteria_from_vbform($user, $criteria)
{
	global $vbulletin;

	if ($vbulletin->GPC_exists['contenttypeid'])
	{
		$criteria->add_contenttype_filter( $vbulletin->GPC['contenttypeid']);
	}
	else if ($vbulletin->GPC_exists['type'])
	{
		$criteria->add_contenttype_filter( $vbulletin->GPC['type']);
	}

	$grouped =  vB_Search_Core::GROUP_DEFAULT;
 	if ($vbulletin->GPC_exists['showposts'])
	{
		$grouped = $vbulletin->GPC['showposts'] ? vB_Search_Core::GROUP_NO : vB_Search_Core::GROUP_YES;
		$criteria->set_grouped($grouped);
	}

	if ($vbulletin->GPC_exists['starteronly'])
	{
		$groupuser = $vbulletin->GPC['starteronly'] ? vB_Search_Core::GROUP_YES : vB_Search_Core::GROUP_NO;
	}
	else
	{
		//if not specified assume that we want the starter when showing groups and the item user for items
		$groupuser = $grouped;
	}

	if ($vbulletin->GPC_exists['query'])
	{
		$criteria->add_keyword_filter($vbulletin->GPC['query'], $vbulletin->GPC['titleonly']);
	}

	if ($vbulletin->GPC['searchuser'] )
	{
		$criteria->add_user_filter($vbulletin->GPC['searchuser'],
			$vbulletin->GPC['exactname'], $groupuser);
	}

	if ($vbulletin->GPC['userid'] )
	{
		$criteria->add_userid_filter(array($vbulletin->GPC['userid']), $groupuser);
	}

	if ($vbulletin->GPC['tag'])
	{
		$criteria->add_tag_filter(htmlspecialchars($vbulletin->GPC['tag']));
	}

	if ($vbulletin->GPC['searchdate'])
	{
		if (is_numeric($vbulletin->GPC['searchdate']))
		{
			$dateline = TIMENOW - ($vbulletin->GPC['searchdate'] * 86400);
		}
		else
		{
			$dateline = $user->get_field('lastvisit');
		}

		$criteria->add_date_filter($vbulletin->GPC['beforeafter'] == 'after' ? vB_Search_Core::OP_GT : vB_Search_Core::OP_LT,
		 	$dateline);
	}

	// allow both sortby rank or relevance to denote natural search
	if ($vbulletin->GPC_exists['sortby'] AND ($vbulletin->GPC['sortby'] == 'relevance' OR $vbulletin->GPC['sortby'] == 'rank') AND $vbulletin->GPC_exists['query'])
	{
		$vbulletin->GPC_exists['sortorder'] = true;
		$vbulletin->GPC['sortorder'] = 'desc';
	}
	else if (!$vbulletin->GPC_exists['sortby'] OR $vbulletin->GPC['sortby'] == 'relevance' OR $vbulletin->GPC['sortby'] == 'rank')
	{
		$vbulletin->GPC['sortby'] = 'dateline';
	}

	if (!$vbulletin->GPC_exists['sortorder'])
	{
		$vbulletin->GPC['sortorder'] = 'desc';
	}

	//natural mode search defaults to false. Only set if we are passed
	// true or 1
	if ($vbulletin->GPC_exists['natural'] AND $vbulletin->GPC['natural'])
	{
		$vbulletin->GPC['sortorder'] = 'desc';
	}


	$field = $vbulletin->GPC['sortby'];
	//fix user or dateline fields.
	$field = $criteria->switch_field($field);

	$criteria->set_sort($field, $vbulletin->GPC['sortorder']);
}


/**
 * check_save_prefs()
 * This function checks to see if we should save the search preferences,
 *  and takes appropriate action
 * @param integer $typeid
 * @return : no return
 */
function check_save_prefs($current_user, $typeid = vB_Search_Core::TYPE_COMMON)
{

	global $vbulletin, $prefs;

	if (is_array($typeid))
	{
		$typeid = vB_Search_Core::TYPE_COMMON;
	}

	if ($vbulletin->GPC_exists['saveprefs'] AND $vbulletin->GPC['saveprefs'])
	{
		$stored_prefs = $current_user->getSearchPrefs();

		foreach ($prefs AS $key => $value)
		{
			if (isset($vbulletin->GPC[$key]))
			{
				$prefs[$key] = convert_urlencoded_unicode($vbulletin->GPC[$key]);
			}
		}
		$stored_prefs[$typeid] = $prefs;

	}
	// clear preferences (only if prefs are set for that type)
	else if (isset($stored_prefs[$typeid]))
	{
		unset($stored_prefs[$typeid]);
	}

	$current_user->saveSearchPrefs($stored_prefs);

}


// #############################################################################
//Support function for intro action

/**
*	Register any errors to display to the main template
* @param vB_Template $template The main search display template
* @param array $errors
*/
function search_intro_register_errors($template, $errors)
{
	if (!VB_API)
	{
		$errorlist = '';
		foreach (array_map('fetch_error', $errors) AS $error)
		{
			$errorlist .= "<li>$error</li>";
		}
	}
	else
	{
		$errorlist =& $errors;
	}
	$template->register('errorlist', $errorlist);
}

/**
*	Handle registration of search prefs
*
* Handles registration of default values for most form elements based
* on the prefs array (a combination of defaults, saved user prefs, and
* any posted form values we might have).
*
* The elements that are handled are singleton elements and any
* static option lists in the html.  Lists generated from a DB query are
* handled when the list html is created.
*
* @param vB_Template $template The main search display template
* @param array $prefs The array of prefs to process.
*/
function search_intro_register_prefs($template, $prefs)
{
	// now check appropriate boxes, select menus etc...
	$formdata = array();
	if ($prefs)
	{
		foreach ($prefs AS $varname => $value)
		{
			//skip array types.  Assume they are handled when the picklist is generated.
			if (is_array($value))
			{
				continue;
			}

			$formdata["$varname"] = htmlspecialchars_uni($value);
			//checkbox needs string not array
			$formdata[$varname . 'checked'] = 'checked="checked"';
			$formdata[$varname . 'selected'] = array($value => 'selected="selected"');
		}
		//we should clean up the template so we don't have to register the individual names
		foreach ($formdata as $varname => $value)
		{
			$template->register($varname, $value);
		}
		$template->register('formdata', $formdata);

	}
}

/**
*	Handle registration of the search cloud
*
* @param vB_Template The main search display template
*/
function search_intro_register_tagcloud($template)
{
	global $vbulletin;

	$template->register('tag_cloud', '');

	// tag cloud display
	if ($vbulletin->options['threadtagging'] == 1 AND $vbulletin->options['tagcloud_searchcloud'] == 1)
	{

		$tag_cloud = fetch_tagcloud('search');

		if ($tag_cloud)
		{
			$template->register('tag_cloud', $tag_cloud);
		}
	}
}

function set_newitem_forums($criteria)
{
	global $vbulletin;

	//figure out forums
	//This follows the logic of the original search.  If a forum is specified then use it and its
	//children.  If an include list is specified, then use it without its children.
	//Do not honor the exclude list if we are using the provided forumid
	if ($vbulletin->GPC['f'])
	{
		$criteria->add_forumid_filter($vbulletin->GPC['f'], true);
	}
	else
	{
		if ($vbulletin->GPC['include'])
		{
			$list = explode(',', $vbulletin->GPC['include']);

			if (is_array($list))
			{
				$list = array_map('intval', $list);
				$criteria->add_forumid_filter($list, false);
			}
		}

		if ($vbulletin->GPC['exclude'])
		{
			$list = explode(',', $vbulletin->GPC['exclude']);

			if (is_array($list))
			{
				$list = array_map('intval', $list);
				$criteria->add_excludeforumid_filter($list);
			}
		}
	}
}

function set_newitem_date($criteria, $user, $action)
{
	global $vbulletin;

	//if we don't have a last visit date, then can't do getnew
	if (!$user->get_field('lastvisit'))
	{
		$action = 'getdaily';
	}


	$markinglimit = false;

	if ($action == 'getnew')
	{
		//if we are using marking logic, then get
		if (!$user->isGuest() AND $vbulletin->options['threadmarking'] AND !$vbulletin->GPC['oldmethod'])
		{
			$markinglimit = TIMENOW - ($vbulletin->options['markinglimit'] * 86400);
		}
		$datecut = $vbulletin->userinfo['lastvisit'];
	}
	//get daily
	else
	{
		if ($vbulletin->GPC['days'] < 1)
		{
			$vbulletin->GPC['days'] = 1;
		}
		$datecut = TIMENOW - (24 * 60 * 60 * $vbulletin->GPC['days']);
	}

	$criteria->add_newitem_filter($datecut, $markinglimit, $action);
}

function set_getnew_sort($criteria, $sort)
{

	if (!$sort)
	{
		$sort = 'dateline';
	}

	//handle rename to standard sort fields.
	$sort_map = array (
		'postusername' => 'user',
		'lastpost' => 'dateline'
	);

	$descending_sorts = array (
		'dateline', 'threadstart'
	);

	$sortorder = in_array($sort, $descending_sorts) ? 'desc' : 'asc';

	if ($sort == 'dateline' OR $sort == 'user')
	{
		//todo -- figure this out, because its spreading
		$sort = $criteria->switch_field($sort);
	}

	$criteria->set_sort($sort, $sortorder);
}


/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63144 $
|| ####################################################################
\*======================================================================*/
