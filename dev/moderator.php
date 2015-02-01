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
define('THIS_SCRIPT', 'moderator');
define('CSRF_PROTECTION', true);
define('CONTENT_PAGE', false);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array();

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

// ####################### PRE-BACK-END ACTIONS ##########################
function exec_postvar_call_back()
{
	global $vbulletin;

	$vbulletin->input->clean_array_gpc('r', array(
		'move'	=> TYPE_STR,
	));

	if ($vbulletin->GPC['do'] == 'move' OR $_REQUEST['do'] == 'prune' OR $_REQUEST['do'] == 'useroptions')
	{
		$vbulletin->noheader = true;
	}
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions.php'); // required for can_administer

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if ($_REQUEST['do'] == 'addmoderator')
{
	if (can_administer('canadminforums'))
	{
		if (!$foruminfo['forumid'])
		{
			eval(standard_error(fetch_error('invalidid', $vbphrase['forum'], $vbulletin->options['contactuslink'])));
		}
		else
		{
			exec_header_redirect($vbulletin->config['Misc']['admincpdir'] . '/index.php?' . $vbulletin->session->vars['sessionurl_js'] . 'loc=' . urlencode('moderator.php?' . $vbulletin->session->vars['sessionurl_js'] . "do=add&f=$foruminfo[forumid]"));
		}
	}
	else
	{
		print_no_permission();
	}
}

// #############################################################################
if ($_REQUEST['do'] == 'postannouncement')
{
	$vbulletin->input->clean_gpc('r', 'formumid', TYPE_INT);
	
	exec_header_redirect('announcement.php?' . $vbulletin->session->vars['sessionurl_js'] . 'do=edit&f=' . $vbulletin->GPC['forumid']);
}

if ($_REQUEST['do'] == 'useroptions')
{
	$vbulletin->input->clean_gpc('r', 'userid', TYPE_UINT);

	$userid = verify_id('user', $vbulletin->GPC['userid']);

	if (can_administer('canadminusers'))
	{
		exec_header_redirect($vbulletin->config['Misc']['admincpdir'] . '/index.php?' . $vbulletin->session->vars['sessionurl_js'] . 'loc=' . urlencode('user.php?' . $vbulletin->session->vars['sessionurl_js'] . "do=edit&u=$userid"));
	}
	else if (can_moderate(0, 'canviewprofile'))
	{
		exec_header_redirect($vbulletin->config['Misc']['modcpdir'] . '/index.php?' . $vbulletin->session->vars['sessionurl_js'] . 'loc=' . urlencode('user.php?' . $vbulletin->session->vars['sessionurl_js'] . "do=viewuser&u=$userid"));
	}
	else
	{
		print_no_permission();
	}

}

// #############################################################################
if ($_REQUEST['do'] == 'move')
{
	if (!$foruminfo['forumid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['forum'], $vbulletin->options['contactuslink'])));
	}

	if (can_administer('canadminthreads'))
	{
		exec_header_redirect($vbulletin->config['Misc']['admincpdir'] . '/index.php?' . $vbulletin->session->vars['sessionurl_js'] . 'loc=' . urlencode('thread.php?' . $vbulletin->session->vars['sessionurl_js']. 'do=move'));
	}
	else if (can_moderate($foruminfo['forumid'], 'canmassmove'))
	{
		exec_header_redirect($vbulletin->config['Misc']['modcpdir'] . '/index.php?' . $vbulletin->session->vars['sessionurl_js'] . 'loc=' . urlencode('thread.php?' . $vbulletin->session->vars['sessionurl_js'] . 'do=move'));
	}
	else
	{
		print_no_permission();
	}
}

// #############################################################################
if ($_REQUEST['do'] == 'prune')
{
	if (!$foruminfo['forumid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['forum'], $vbulletin->options['contactuslink'])));
	}

	if (can_administer('canadminthreads'))
	{
		exec_header_redirect($vbulletin->config['Misc']['admincpdir'] . '/index.php?' . $vbulletin->session->vars['sessionurl_js'] . 'loc=' . urlencode('thread.php?' . $vbulletin->session->vars['sessionurl_js'] . 'do=prune'));
	}
	else if (can_moderate($forumid, 'canmassprune'))
	{
		exec_header_redirect($vbulletin->config['Misc']['modcpdir'] . '/index.php?' . $vbulletin->session->vars['sessionurl_js'] . 'loc=' . urlencode('thread.php?' . $vbulletin->session->vars['sessionurl_js'] . 'do=prune'));
	}
	else
	{
		print_no_permission();
	}
}

// #############################################################################
if ($_REQUEST['do'] == 'modposts')
{
	if (can_moderate(0, 'canmoderateposts'))
	{
		exec_header_redirect($vbulletin->config['Misc']['modcpdir'] . '/index.php?' . $vbulletin->session->vars['sessionurl_js'] . 'loc=' . urlencode('moderate.php?' . $vbulletin->session->vars['sessionurl_js'] . 'do=posts'));
	}
	else
	{
		print_no_permission();
	}
}

// #############################################################################
if ($_REQUEST['do'] == 'modattach')
{
	if (can_moderate(0, 'canmoderateattachments'))
	{
		exec_header_redirect($vbulletin->config['Misc']['modcpdir'] . '/index.php?' . $vbulletin->session->vars['sessionurl_js'] . 'loc=' . urlencode('moderate.php?' . $vbulletin->session->vars['sessionurl_js'] . 'do=attachments'));
	}
	else
	{
		print_no_permission();
	}

}

print_no_permission();

//setup redirects for other options in moderators cp

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 34227 $
|| ####################################################################
\*======================================================================*/
?>