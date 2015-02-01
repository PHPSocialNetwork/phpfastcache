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
define('THIS_SCRIPT', 'joinrequests');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('user');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
	'usercpmenu',
	'usercpnav'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'viewjoinrequests' => array(
		'JOINREQUESTS',
		'joinrequestsbit',
		'usercp_nav_folderbit',
		'USERCP_SHELL',
	),
);

$actiontemplates['none'] = &$actiontemplates['viewjoinrequests'];

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// must be a logged in user to use this page
if (!$vbulletin->userinfo['userid'])
{
	print_no_permission();
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'viewjoinrequests';
}

$includecss = array();

($hook = vBulletinHook::fetch_hook('joinrequest_start')) ? eval($hook) : false;

// #############################################################################
// process join requests
if ($_POST['do'] == 'processjoinrequests')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usergroupid' => TYPE_UINT,
		'request' => TYPE_ARRAY_INT,
	));

	($hook = vBulletinHook::fetch_hook('joinrequest_process_start')) ? eval($hook) : false;

	// check we have a valid usergroup
	if (!$vbulletin->GPC['usergroupid'] OR !isset($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['usergroup'], $vbulletin->options['contactuslink'])));
	}

	// check we have some requests to work with
	if (empty($vbulletin->GPC['request']))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['join_request'], $vbulletin->options['contactuslink'])));
	}

	// check permission to do authorizations in this group
	if (!($check = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "usergroupleader WHERE userid = " . $vbulletin->userinfo['userid'] . " AND usergroupid = " . $vbulletin->GPC['usergroupid'])))
	{
		print_no_permission();
	}

	// initialize an array to store requests that will be authorized
	$auth = array();
	$deny = array();
	$delete = array();

	// sort the requests according to the action specified
	foreach ($vbulletin->GPC['request'] AS $requestid => $action)
	{
		$requestid = intval($requestid);
		switch($action)
		{
			case -1:	// this request will be ignored
				unset($vbulletin->GPC['request']["$requestid"]);
				break;

			case  1:	// this request will be authorized and then removed
				$auth[] = $requestid;
				$delete[] = $requestid;
				break;

			case  0:	// this request will be denied
				$deny[] = $requestid;
				$delete[] = $requestid;
				break;
		}
	}

	// if we have any accepted requests, make sure they are valid
	if (!empty($delete))
	{
		$users = $db->query_read_slave("
			SELECT req.userid, user.username, user.usergroupid, user.membergroupids, req.usergrouprequestid
			FROM " . TABLE_PREFIX . "usergrouprequest AS req
			INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
			WHERE usergrouprequestid IN(" . implode(', ', $delete) . ")
			ORDER BY user.username
		");
		$authusers = array();
		$denyusers = array();
		while ($user = $db->fetch_array($users))
		{
			if (!in_array($vbulletin->GPC['usergroupid'], fetch_membergroupids_array($user)) and
				in_array($user['usergrouprequestid'],$auth))
			{
				$authusers[$user['userid']] = $user['usergrouprequestid'];
			}
			elseif (in_array($user['usergrouprequestid'], $deny))
			{
				$denyusers[$user['userid']] = $user['usergrouprequestid'];
			}
		}
	}

	// check that we STILL have some valid requests
	if (!empty($authusers))
	{
		$updateQuery = "
			UPDATE " . TABLE_PREFIX . "user SET
			membergroupids = IF(membergroupids = '', " . $vbulletin->GPC['usergroupid'] . ", CONCAT(membergroupids, '," . $vbulletin->GPC['usergroupid'] . "'))
			WHERE userid IN(" . implode(', ', array_keys($authusers)) . ")
		";
		$db->query_write($updateQuery);
	}

	($hook = vBulletinHook::fetch_hook('joinrequest_process_complete')) ? eval($hook) : false;

	// delete processed join requests
	if (!empty($delete))
	{
		$deleteQuery = "
			DELETE FROM " . TABLE_PREFIX . "usergrouprequest
			WHERE usergrouprequestid IN(" . implode(', ', $delete) . ")
		";
		$db->query_write($deleteQuery);
	}

	print_standard_redirect('join_requests_processed', true, true);  
}

// #############################################################################
// view join requests
if ($_REQUEST['do'] == 'viewjoinrequests')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'usergroupid' => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'perpage' => TYPE_UINT
	));

	$usergroupid = $vbulletin->GPC['usergroupid'];

	($hook = vBulletinHook::fetch_hook('joinrequest_view_start')) ? eval($hook) : false;

	if (!$vbulletin->GPC['usergroupid'] OR !isset($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['usergroup'], $vbulletin->options['contactuslink'])));
	}

	$usergroups = array();

	// query usergroups of which bbuser is a leader
	$joinrequests = $db->query_read_slave("
		SELECT usergroupleader.usergroupid, COUNT(usergrouprequestid) AS requests
		FROM " . TABLE_PREFIX . "usergroupleader AS usergroupleader
		LEFT JOIN " . TABLE_PREFIX . "usergrouprequest AS usergrouprequest USING(usergroupid)
		WHERE usergroupleader.userid = " . $vbulletin->userinfo['userid'] . "
		GROUP BY usergroupleader.usergroupid
	");
	while ($joinrequest = $db->fetch_array($joinrequests))
	{
		$usergroups["{$joinrequest['usergroupid']}"] = intval($joinrequest['requests']);
	}
	unset($joinrequest);
	$db->free_result($joinrequests);

	// if we got no results, or if the specified usergroupid was not returned, show no permission
	if (empty($usergroups))
	{
		print_no_permission();
	}

	$usergroupbits = '';
	foreach ($vbulletin->usergroupcache AS $optionvalue => $usergroup)
	{
		if (isset($usergroups["$optionvalue"]))
		{
			$optiontitle = construct_phrase($vbphrase['x_y_requests'], $vbulletin->usergroupcache["$optionvalue"]['title'], vb_number_format($usergroups["$optionvalue"]));
			$optionselected = iif($optionvalue == $vbulletin->GPC['usergroupid'], 'selected="selected"', '');
			$optionclass = '';
			$usergroupbits .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
		}
	}

	// set a shortcut to the vbulletin->usergroupcache entry for this group
	$usergroup =& $vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"];

	// initialize $joinrequestbits
	$joinrequestbits = '';

	$numrequests =& $usergroups["{$vbulletin->GPC['usergroupid']}"];

	// if there are some requests for this usergroup, display them
	if ($numrequests > 0)
	{
		// set defaults
		sanitize_pageresults($numrequests, $vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage'], 100, 20);
		$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

		$pagenav = construct_page_nav($vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage'], $numrequests, 'joinrequests.php?' . $vbulletin->session->vars['sessionurl'] . "usergroupid={$vbulletin->GPC['usergroupid']}&amp;pp=" . $vbulletin->GPC['perpage']);

		$requests = $db->query_read_slave("
			SELECT req.*, user.username, IF(user.displaygroupid = 0, user.usergroupid, user.displaygroupid) AS displaygroupid, infractiongroupid
			FROM " . TABLE_PREFIX . "usergrouprequest AS req
			INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
			WHERE req.usergroupid = " . $vbulletin->GPC['usergroupid'] . "
			LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
		");
		while ($request = $db->fetch_array($requests))
		{
			fetch_musername($request);
			$request['date'] = vbdate($vbulletin->options['dateformat'], $request['dateline'], 1);
			$request['time'] = vbdate($vbulletin->options['timeformat'], $request['dateline']);

			exec_switch_bg();

			($hook = vBulletinHook::fetch_hook('joinrequest_view_bit')) ? eval($hook) : false;

			$templater = vB_Template::create('joinrequestsbit');
				$templater->register('bgclass', $bgclass);
				$templater->register('request', $request);
			$joinrequestbits .= $templater->render();
		}
	} // end if ($numrequests > 0)

	$show['joinrequests'] = iif($joinrequestbits != '', true, false);

	// make the navbar elements
	$navbits = construct_navbits(array(
		'usercp.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['user_control_panel'],
		'profile.php?' . $vbulletin->session->vars['sessionurl'] . "do=editusergroups" => $vbphrase['group_memberships'],
		'' => "$vbphrase[join_requests]: '$usergroup[title]'" // <phrase> ?
	));

	$includecss['joinrequests'] = 'joinrequests.css';
}

// #############################################################################
// spit out final HTML if we have got this far

// build the cp nav
require_once(DIR . '/includes/functions_user.php');
construct_usercp_nav('usergroups');

($hook = vBulletinHook::fetch_hook('joinrequest_complete')) ? eval($hook) : false;

$navbar = render_navbar_template($navbits);
$templater = vB_Template::create('JOINREQUESTS');
	$templater->register('gobutton', $gobutton);
	$templater->register('joinrequestbits', $joinrequestbits);
	$templater->register('pagenav', $pagenav);
	$templater->register('perpage', $perpage);
	$templater->register('usergroup', $usergroup);
	$templater->register('usergroupbits', $usergroupbits);
	$templater->register('usergroupid', $usergroupid);
$HTML = $templater->render();

if (!$vbulletin->options['storecssasfile'])
{
	$includecss = implode(',', $includecss);
}

// shell template
$templater = vB_Template::create('USERCP_SHELL');
	$templater->register_page_templates();
	$templater->register('includecss', $includecss);
	$templater->register('cpnav', $cpnav);
	$templater->register('HTML', $HTML);
	$templater->register('navbar', $navbar);
	$templater->register('navclass', $navclass);
	$templater->register('onload', $onload);
	$templater->register('pagetitle', $pagetitle);
	$templater->register('template_hook', $template_hook);
print_output($templater->render());

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 53346 $
|| ####################################################################
\*======================================================================*/
?>
