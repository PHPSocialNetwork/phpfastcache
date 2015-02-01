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
define('THIS_SCRIPT', 'threadrate');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('showthread');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$vbulletin->input->clean_array_gpc('p', array(
	'vote'       => TYPE_UINT,
	'pagenumber' => TYPE_UINT,
	'perpage'    => TYPE_UINT,
	'ajax'       => TYPE_BOOL,
));

if ($vbulletin->GPC['vote'] < 1 OR $vbulletin->GPC['vote'] > 5)
{
	eval(standard_error(fetch_error('invalidvote')));
}

if (!$threadinfo['threadid'] OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')) OR (!$threadinfo['open'] AND !can_moderate($threadinfo['forumid'], 'canopenclose')) OR ($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'], 'candeleteposts')))
{
	eval(standard_error(fetch_error('threadrateclosed')));
}

$forumperms = fetch_permissions($threadinfo['forumid']);
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canthreadrate']) OR (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($threadinfo['postuserid'] != $vbulletin->userinfo['userid'])))
{
	print_no_permission();
}

// check if there is a forum password and if so, ensure the user has it set
verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

$rated = intval(fetch_bbarray_cookie('thread_rate', $threadinfo['threadid']));

($hook = vBulletinHook::fetch_hook('threadrate_start')) ? eval($hook) : false;

$update = false;
if ($vbulletin->userinfo['userid'])
{
	if ($rating = $db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "threadrate
		WHERE userid = " . $vbulletin->userinfo['userid'] . "
			AND threadid = $threadinfo[threadid]
	"))
	{
		if ($vbulletin->options['votechange'])
		{
			if ($vbulletin->GPC['vote'] != $rating['vote'])
			{
				$threadrate =& datamanager_init('ThreadRate', $vbulletin, ERRTYPE_STANDARD);
				$threadrate->set_info('thread', $threadinfo);
				$threadrate->set_existing($rating);
				$threadrate->set('vote', $vbulletin->GPC['vote']);

				($hook = vBulletinHook::fetch_hook('threadrate_update')) ? eval($hook) : false;

				$threadrate->save();
			}
			$update = true;
			if (!$vbulletin->GPC['ajax'])
			{
				$vbulletin->url = fetch_seo_url('thread', $threadinfo, array('page' => $vbulletin->GPC['pagenumber'], 'pp' => $vbulletin->GPC['perpage']));
				print_standard_redirect('redirect_threadrate_update');  
			}
		}
		else if (!$vbulletin->GPC['ajax'])
		{
			eval(standard_error(fetch_error('threadratevoted')));
		}
	}
	else
	{
		$threadrate =& datamanager_init('ThreadRate', $vbulletin, ERRTYPE_STANDARD);
		$threadrate->set_info('thread', $threadinfo);
		$threadrate->set('threadid', $threadinfo['threadid']);
		$threadrate->set('userid', $vbulletin->userinfo['userid']);
		$threadrate->set('vote', $vbulletin->GPC['vote']);

		($hook = vBulletinHook::fetch_hook('threadrate_add')) ? eval($hook) : false;

		$threadrate->save();
		$update = true;

		if (!$vbulletin->GPC['ajax'])
		{
			$vbulletin->url = fetch_seo_url('thread', $threadinfo, array('page' => $vbulletin->GPC['pagenumber'], 'pp' => $vbulletin->GPC['perpage']));
			print_standard_redirect('redirect_threadrate_add');  
		}
	}
}
else
{
	// Check for cookie on user's computer for this threadid
	if ($rated AND !$vbulletin->options['votechange'])
	{
		if (!$vbulletin->GPC['ajax'])
		{
			eval(standard_error(fetch_error('threadratevoted')));
		}
	}
	else
	{
		// Check for entry in Database for this Ip Addr/Threadid
		if ($rating = $db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "threadrate
			WHERE ipaddress = '" . $db->escape_string(IPADDRESS) . "'
				AND threadid = $threadinfo[threadid]
		"))
		{
			if ($vbulletin->options['votechange'])
			{
				if ($vbulletin->GPC['vote'] != $rating['vote'])
				{
					$threadrate =& datamanager_init('ThreadRate', $vbulletin, ERRTYPE_STANDARD);
					$threadrate->set_info('thread', $threadinfo);
					$threadrate->set_existing($rating);
					$threadrate->set('vote', $vbulletin->GPC['vote']);

					($hook = vBulletinHook::fetch_hook('threadrate_update')) ? eval($hook) : false;

					$threadrate->save();
				}
				$update = true;

				if (!$vbulletin->GPC['ajax'])
				{
					$vbulletin->url = fetch_seo_url('thread', $threadinfo, array('page' => $vbulletin->GPC['pagenumber'], 'pp' => $vbulletin->GPC['perpage']));
					print_standard_redirect('redirect_threadrate_update');  
				}
			}
			else if (!$vbulletin->GPC['ajax'])
			{
				eval(standard_error(fetch_error('threadratevoted')));
			}
		}
		else
		{
			$threadrate =& datamanager_init('ThreadRate', $vbulletin, ERRTYPE_STANDARD);
			$threadrate->set_info('thread', $threadinfo);
			$threadrate->set('threadid', $threadinfo['threadid']);
			$threadrate->set('userid', 0);
			$threadrate->set('vote', $vbulletin->GPC['vote']);
			$threadrate->set('ipaddress', IPADDRESS);

			($hook = vBulletinHook::fetch_hook('threadrate_add')) ? eval($hook) : false;

			$threadrate->save();
			$update = true;

			if (!$vbulletin->GPC['ajax'])
			{
				$vbulletin->url = fetch_seo_url('thread', $threadinfo, array('page' => $vbulletin->GPC['pagenumber'], 'pp' => $vbulletin->GPC['perpage']));
				print_standard_redirect('redirect_threadrate_add');  
			}
		}
	}
}

require_once(DIR . '/includes/class_xml.php');
$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
$xml->add_group('threadrating');
if ($update)
{
	$thread = $db->query_first_slave("
		SELECT votetotal, votenum
		FROM " . TABLE_PREFIX . "thread
		WHERE threadid = $threadinfo[threadid]
	");

	$average = $thread['votetotal'] / $thread['votenum'];
	$rating = round($average);
	
	$xml->add_tag('rating_full', vb_number_format($average, 2));
	$xml->add_tag('rating', $rating);
	$xml->add_tag('vote_threshold_met', intval($thread['votenum'] >= $vbulletin->options['showvotes']));

/*
//I don't think we need this any longer.
	if ($thread['votenum'] >= $vbulletin->options['showvotes'])
	{	// Show Voteavg
		$thread['voteavg'] = vb_number_format($average, 2);
//		$thread['rating'] = round($thread['votetotal'] / $thread['votenum']);

		$html = "$vbphrase[rating]: <img class=\"inlineimg\" src=\"$stylevar[imgdir_rating]/rating_$rating.gif\" alt=\"" . 
			construct_phrase($vbphrase['thread_rating_x_votes_y_average'], $thread['votenum'], 
			$thread['voteavg']) . "\" border=\"0\" />";

		$xml->add_tag('voteavg', process_replacement_vars($html));
	}
	else
	{
		$xml->add_tag('voteavg', '');
	}
*/

	if (!function_exists('fetch_phrase'))
	{
		require_once(DIR . '/includes/functions_misc.php');
	}
	$xml->add_tag('message', fetch_phrase('redirect_threadrate_add', 'frontredirect', 'redirect_'));
}
else	// Already voted error...
{
	$xml->add_tag('error', fetch_error('threadratevoted'));
}
$xml->close_group();
$xml->print_xml();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 50189 $
|| ####################################################################
\*======================================================================*/
?>
