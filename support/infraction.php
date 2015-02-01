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
define('THIS_SCRIPT', 'infraction');
define('CSRF_PROTECTION', true);
define('GET_EDIT_TEMPLATES', 'report,update');

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('infraction', 'infractionlevel', 'pm', 'posting', 'banning', 'user');

// get special data templates from the datastore
$specialtemplates = array('smiliecache', 'bbcodecache');

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'report' => array(
		'userinfraction',
		'userinfractionbit',
		'userinfraction_banbit',
		'userinfraction_groupbit',
		'newpost_preview',
	),
	'view'   => array(
		'userinfraction_view'
	),
);

$actiontemplates['none'] =& $actiontemplates['report'];
$actiontemplates['reverse'] =& $actiontemplates['view'];
$actiontemplates['update'] =& $actiontemplates['report'];

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_banning.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'report';
}

$vbulletin->input->clean_array_gpc('r', array(
	'userid'       => TYPE_UINT,
	'infractionid' => TYPE_UINT,
));

($hook = vBulletinHook::fetch_hook('infraction_start')) ? eval($hook) : false;

// ######################### VERIFY POST OR USER ########################

if ($postinfo['postid'])
{
	$infractioninfo = $db->query_first_slave("
		SELECT inf.*, user.username, user2.username AS actionusername
		FROM " . TABLE_PREFIX . "infraction AS inf
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (inf.whoadded = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (inf.actionuserid = user2.userid)
		WHERE postid = $postinfo[postid]
		ORDER BY inf.dateline DESC
		LIMIT 1
	");
	$userinfo = fetch_userinfo($postinfo['userid']);
}
else if ($vbulletin->GPC['infractionid'])
{
	if (!$infractioninfo = $db->query_first_slave("
		SELECT inf.*, user.username, user2.username AS actionusername
		FROM " . TABLE_PREFIX . "infraction AS inf
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (inf.whoadded = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (inf.actionuserid = user2.userid)
		WHERE infractionid = " . $vbulletin->GPC['infractionid'] . "
	"))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['infraction'], $vbulletin->options['contactuslink'])));
	}

	if ($infractioninfo['postid'])
	{	// this infraction belongs to a post
		$postinfo = $threadinfo = $foruminfo = array();
		if ($postinfo = fetch_postinfo($infractioninfo['postid']))
		{
			if ($threadinfo = fetch_threadinfo($postinfo['threadid']))
			{
				$foruminfo = fetch_foruminfo($threadinfo['forumid']);
			}
		}
	}
	$userinfo = fetch_userinfo($infractioninfo['userid']);
}
else if ($vbulletin->GPC['userid'])
{
	$userinfo = verify_id('user', $vbulletin->GPC['userid'], 0, 1, 15);
	if (!$userinfo['userid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
	}
}
else
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
}

if (!$userinfo['userid'])
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
}

// ######################### VERIFY POST PERMISSIONS ###################
// At this point $userinfo has to exist and $postinfo *might* exist
if ($postinfo['postid'])
{
	if ((!$postinfo['visible'] OR $postinfo['isdeleted']) AND !can_moderate($threadinfo['forumid']))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
	}
	if ((!$threadinfo['visible'] OR $threadinfo['isdeleted']) AND !can_moderate($threadinfo['forumid']))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
	}

	$forumperms = fetch_permissions($foruminfo['forumid']);
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
	{
		print_no_permission();
	}
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($threadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR $vbulletin->userinfo['userid'] == 0))
	{
		print_no_permission();
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);
}

// ######################### VERIFY PERMISSIONS ##########################
if ($_REQUEST['do'] == 'report' OR $_POST['do'] == 'update')
{
	cache_permissions($userinfo);
	if (
			// Must have 'cangiveinfraction' permission. Branch dies right here majority of the time
		!($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cangiveinfraction'])
			// Can not give yourself an infraction
		OR $userinfo['userid'] == $vbulletin->userinfo['userid']
			// Can not give an admin an infraction
		OR $userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']
			// Only Admins can give a supermod an infraction
		OR (
			$userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['ismoderator']
			AND !($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
		)
	)
	{
		print_no_permission();
	}

	($hook = vBulletinHook::fetch_hook('infraction_verify_permissions')) ? eval($hook) : false;

	// moderators will have the infraction icon on their posts due to the overhead of checking moderator status on showthread
	// Only Admins & Supermods may give infractions to moderators
	// really could use a bit in user that is set when an user is a moderator of any forum to avoid this

	$uglist = $userinfo['usergroupid'] . iif(trim($userinfo['membergroupids']), ",$userinfo[membergroupids]");
	if (can_moderate(0, '', $userinfo['userid'], $uglist)
		AND !($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
		AND !($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['ismoderator'])
	)
	{
		eval(standard_error(fetch_error('you_are_not_allowed_to_warn_moderators')));
	}

	// Check if infraction exists and if so was it reversed?
	if ($infractioninfo AND $infractioninfo['action'] != 2)
	{
		eval(standard_error(fetch_error('postalreadywarned')));
	}

	$customreason = $expires = $points = $postmessage = $note = '';
	$periodselected = array();

	$infractionban = $pointsban = false;
	$totalinfractions = 0;
	$infcache = array();
	$userinfractions = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "infraction
		WHERE userid = " . $userinfo['userid'] . "
			AND action IN (0,1)
			AND points > 0
		ORDER BY expires DESC
	");
	while ($userinfraction = $db->fetch_array($userinfractions))
	{
		if ($userinfraction['action'] == 0 AND $userinfraction['infractionlevelid'])
		{
			if (!$infcache["$userinfraction[infractionlevelid]"]['expires'] OR $userinfraction['expires'] == 0)
			{
				$infcache["$userinfraction[infractionlevelid]"]['expires'] = $userinfraction['expires'];
			}
			$infcache["$userinfraction[infractionlevelid]"]['count']++;
			$show['count'] = true;
		}
		$totalinfractions++;
	}

	$banlist = array();
	$banpointlist = array();
	$bans = $db->query_read_slave("
		SELECT banusergroupid, amount, period, method
		FROM " . TABLE_PREFIX . "infractionban
		WHERE usergroupid IN (-1, " . $userinfo['usergroupid'] . ")
			AND ((method = 'infractions' AND amount > $totalinfractions) OR
				(method = 'points' AND amount > $userinfo[ipoints]))
		ORDER BY method, amount ASC
	");
	while ($ban = $db->fetch_array($bans))
	{
		if ($ban['method'] == 'infractions' AND $totalinfractions + 1 == $ban['amount'])
		{
			$infractionban = true;
		}
		else if ($ban['method'] == 'points' AND !$minimumpointsban)
		{
			$minimumpointsban = $ban['amount'];
		}
		$bangroup = $vbulletin->usergroupcache["$ban[banusergroupid]"]['title'];
		$points = $ban['method'] == 'infractions' ? '&nbsp;' : $ban['amount'];
		$infractions = $ban['method'] == 'points' ? '&nbsp;' : $ban['amount'];

		switch($ban['period'])
		{
			case 'D_1':   $period = construct_phrase($vbphrase['x_days'], 1); break;
			case 'D_2':   $period = construct_phrase($vbphrase['x_days'], 2); break;
			case 'D_3':   $period = construct_phrase($vbphrase['x_days'], 3); break;
			case 'D_4':   $period = construct_phrase($vbphrase['x_days'], 4); break;
			case 'D_5':   $period = construct_phrase($vbphrase['x_days'], 5); break;
			case 'D_6':   $period = construct_phrase($vbphrase['x_days'], 6); break;
			case 'D_7':   $period = construct_phrase($vbphrase['x_days'], 7); break;
			case 'D_10':  $period = construct_phrase($vbphrase['x_days'], 10); break;
			case 'D_14':  $period = construct_phrase($vbphrase['x_weeks'], 2); break;
			case 'D_21':  $period = construct_phrase($vbphrase['x_weeks'], 3); break;
			case 'M_1':   $period = construct_phrase($vbphrase['x_months'], 1); break;
			case 'M_2':   $period = construct_phrase($vbphrase['x_months'], 2); break;
			case 'M_3':   $period = construct_phrase($vbphrase['x_months'], 3); break;
			case 'M_4':   $period = construct_phrase($vbphrase['x_months'], 4); break;
			case 'M_5':   $period = construct_phrase($vbphrase['x_months'], 5); break;
			case 'M_6':   $period = construct_phrase($vbphrase['x_months'], 6); break;
			case 'Y_1':   $period = construct_phrase($vbphrase['x_years'], 1); break;
			case 'Y_2':   $period = construct_phrase($vbphrase['x_years'], 2); break;
			case 'PERMA': $period = $vbphrase['forever']; break;
			default: $period = '';
		}
		$ban['liftdate'] = convert_date_to_timestamp($ban['period']);
		$templater = vB_Template::create('userinfraction_banbit');
			$templater->register('bangroup', $bangroup);
			$templater->register('infractions', $infractions);
			$templater->register('period', $period);
			$templater->register('points', $points);
		$banbits .= $templater->render();

		$banlist[] = $ban;
	}

	if (!($vbulletin->usergroupcache["$userinfo[usergroupid]"]['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
	{
		$bancheck = $db->query_first("SELECT userid, liftdate FROM " . TABLE_PREFIX . "userban WHERE userid = $userinfo[userid]");
		if ($bancheck AND !$bancheck['liftdate'])
		{
			$nocontact = true;
		}
	}

	$show['pm'] = ($vbulletin->options['enablepms'] AND $vbulletin->userinfo['permissions']['pmquota'] AND !$nocontact);
	$show['trackpm'] = $cantrackpm = $vbulletin->userinfo['permissions']['pmpermissions'] & $vbulletin->bf_ugp_pmpermissions['cantrackpm'];
	$showemail = (($userinfo['adminemail'] OR $userinfo['showemail']) AND $vbulletin->options['enableemail'] AND $vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canemailmember'] AND !$nocontact);
}

// ######################### REVERSE INFRACTION ##########################
if ($_POST['do'] == 'reverse')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'reason'            => TYPE_STR,
		'reverseinfraction' => TYPE_BOOL,
	));

	if (!($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canreverseinfraction']))
	{
		print_no_permission();
	}

	if (!$infractioninfo OR $infractioninfo['action'] == 2)
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['infractionid'], $vbulletin->options['contactuslink'])));
	}

	// Infraction has been changed
	if ($infractioninfo['infractionid'] != $vbulletin->GPC['infractionid'])
	{
		eval(standard_error(fetch_error('infractionupdated')));
	}

	if ($vbulletin->GPC['reverseinfraction'])
	{
		($hook = vBulletinHook::fetch_hook('infraction_reverse_start')) ? eval($hook) : false;

		// Can not reverse infractions that have already been reversed
		if ($infractioninfo['action'] != 2)
		{
			$infdata =& datamanager_init('Infraction', $vbulletin, ERRTYPE_STANDARD);
			$infdata->set_existing($infractioninfo);
			$infdata->setr_info('postinfo', $postinfo);
			$infdata->setr_info('userinfo', $userinfo);
			$infdata->set('action', 2);
			$infdata->set('actionuserid', $vbulletin->userinfo['userid']);
			$infdata->set('actiondateline', TIMENOW);
			$infdata->set('actionreason', $vbulletin->GPC['reason']);

			($hook = vBulletinHook::fetch_hook('infraction_reverse_process')) ? eval($hook) : false;

			$infdata->save();

			($hook = vBulletinHook::fetch_hook('infraction_reverse_complete')) ? eval($hook) : false;

			print_standard_redirect('redirect_infraction_reversed');  
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
		}
	}
	else
	{
		// Return to view
		$_REQUEST['do'] = 'view';
	}
}

// ######################### VIEW INFRACTION ##########################
if ($_REQUEST['do'] == 'view')
{
	if (!($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canreverseinfraction'])
		AND !($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseeinfraction'])
		AND !($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cangiveinfraction'])
		AND ($userinfo['userid'] != $vbulletin->userinfo['userid'] /*OR !$vbulletin->options['canseeown']*/)
	)
	{
		print_no_permission();
	}

	($hook = vBulletinHook::fetch_hook('infraction_view_start')) ? eval($hook) : false;

	if (!($infractioninfo))
	{
		if ($postinfo['infraction'] > 0)
		{
			$dataman =& datamanager_init('Post', $vbulletin, ERRTYPE_SILENT, 'threadpost');
			$dataman->set_existing($postinfo);
			$dataman->set('infraction', 0);
			$dataman->save();
			unset($dataman);
		}
		eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
	}

	$show['expires'] = false;
	$infractioninfo['title'] = !empty($vbphrase['infractionlevel' . $infractioninfo['infractionlevelid'] . '_title']) ? $vbphrase['infractionlevel' . $infractioninfo['infractionlevelid'] . '_title'] : ($infractioninfo['customreason'] ? $infractioninfo['customreason'] : $vbphrase['n_a']);
	$infractioninfo['date'] = vbdate($vbulletin->options['dateformat'], $infractioninfo['dateline']);
	$infractioninfo['time'] = vbdate($vbulletin->options['timeformat'], $infractioninfo['dateline']);
	if ($infractioninfo['points'])
	{
		switch ($infractioninfo['action'])
		{
			case 2:
				$infractioninfo['status'] = construct_phrase($vbphrase['reversed_infraction_x_points'], $infractioninfo['points']);
				break;
			case 1:
				$infractioninfo['status'] = construct_phrase($vbphrase['expired_infraction_x_points'], $infractioninfo['points']);
				break;
			case 0:
				$infractioninfo['status'] = construct_phrase($vbphrase['active_infraction_x_points'], $infractioninfo['points']);
				break;
		}
	}
	else
	{
		switch ($infractioninfo['action'])
		{
			case 2:
				$infractioninfo['status'] = $vbphrase['reversed_warning'];
				break;
			case 1:
				$infractioninfo['status'] = $vbphrase['expired_warning'];
				break;
			case 0:
				$infractioninfo['status'] = $vbphrase['active_warning'];
				break;
		}
	}

	if ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canreverseinfraction'] AND $infractioninfo['action'] != 2)
	{
		$show['reverseoption'] = true;
	}
	else
	{
		$show['reverseoption'] = false;
	}

	$show['warning'] = $infractioninfo['points'] ? false : true;
	if ($infractioninfo['action'] == 0)
	{
		$show['expires'] = true;
		if ($infractioninfo['expires'] > 0)
		{
			$infractioninfo['expires_time'] = vbdate($vbulletin->options['timeformat'], $infractioninfo['expires']);
			$infractioninfo['expires_date'] = vbdate($vbulletin->options['dateformat'], $infractioninfo['expires']);
		}
		else // Never Expires
		{
			$show['never'] = true;
		}
	}
	else if ($infractioninfo['action'] == 1)
	{	// Expired
		$show['expired'] = true;
		$infractioninfo['expired_time'] = vbdate($vbulletin->options['timeformat'], $infractioninfo['actiondateline']);
		$infractioninfo['expired_date'] = vbdate($vbulletin->options['dateformat'], $infractioninfo['actiondateline']);
	}
	else if ($infractioninfo['action'] == 2)
	{
		$show['reversed'] = true;
		$infractioninfo['reversed_time'] = vbdate($vbulletin->options['timeformat'], $infractioninfo['actiondateline']);
		$infractioninfo['reversed_date'] = vbdate($vbulletin->options['dateformat'], $infractioninfo['actiondateline']);
	}

	if ($infractioninfo['note'] AND $userinfo['userid'] != $vbulletin->userinfo['userid'] AND ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canreverseinfraction'] OR $vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cangiveinfraction']))
	{
		$show['note'] = true;
	}
	$show['postinfo'] = ($infractioninfo['postid'] AND $threadinfo);

	if ($infractioninfo['threadid'] AND $disthreadinfo = fetch_threadinfo($infractioninfo['threadid']))
	{
		$show['disthread'] = true;
		if ((!$disthreadinfo['visible'] OR $disthreadinfo['isdeleted']) AND !can_moderate($disthreadinfo['forumid']))
		{
			$show['disthread'] = false;
		}

		$forumperms = fetch_permissions($disthreadinfo['forumid']);
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
		{
			$show['disthread'] = false;
		}
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($disthreadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR $vbulletin->userinfo['userid'] == 0))
		{
			$show['disthread'] = false;
		}

		// check if there is a forum password and if so, ensure the user has it set
		if (!verify_forum_password($foruminfo['forumid'], $foruminfo['password'], false))
		{
			$show['disthread'] = false;
		}
	}
	else
	{
		$show['disthread'] = false;
	}

	$pageinfo = array('p' => $postinfo['postid']);

	// draw nav bar
	$navbits = array();
	$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];
	$parentlist = array_reverse(explode(',', $foruminfo['parentlist']));
	foreach ($parentlist AS $forumID)
	{
		$forumTitle = $vbulletin->forumcache["$forumID"]['title'];
		$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
	}
	if ($postinfo['postid'])
	{
		$navbits[fetch_seo_url('thread', $threadinfo, $pageinfo) . "#post$postinfo[postid]"] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];
	}
	else
	{
		$navbits[fetch_seo_url('member', $userinfo)] = construct_phrase($vbphrase['xs_profile'], $userinfo['username']);
	}

	$navbits[''] = construct_phrase($vbphrase['user_infraction_for_x'], $userinfo['username']);
	$navbits = construct_navbits($navbits);

	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('infraction_view_complete')) ? eval($hook) : false;

	$url =& $vbulletin->url;
	$templater = vB_Template::create('userinfraction_view');
		$templater->register_page_templates();
		$templater->register('disthreadinfo', $disthreadinfo);
		$templater->register('forumrules', $forumrules);
		$templater->register('infractioninfo', $infractioninfo);
		$templater->register('navbar', $navbar);
		$templater->register('onload', $onload);
		$templater->register('pageinfo', $pageinfo);
		$templater->register('postid', $postid);
		$templater->register('postinfo', $postinfo);
		$templater->register('threadinfo', $threadinfo);
		$templater->register('url', $url);
		$templater->register('userinfo', $userinfo);
	print_output($templater->render());
}

// ######################### UPDATE INFRACTION ############################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'infractionlevelid' => TYPE_UINT,
		'warning'           => TYPE_ARRAY_BOOL,
		'note'              => TYPE_STR,
		'message'           => TYPE_STR,
		'iconid'            => TYPE_UINT,
		'wysiwyg'           => TYPE_BOOL,
		'parseurl'          => TYPE_BOOL,
		'signature'         => TYPE_BOOL,
		'disablesmilies'    => TYPE_BOOL,
		'receipt'           => TYPE_BOOL,
		'preview'           => TYPE_STR,
		'savecopy'          => TYPE_BOOL,
		'expires'           => TYPE_UINT,
		'points'            => TYPE_STR, // leave as STR
		'period'            => TYPE_NOHTML,
		'customreason'      => TYPE_STR,
		'banreason'         => TYPE_NOHTML,
	));

	($hook = vBulletinHook::fetch_hook('infraction_update_start')) ? eval($hook) : false;

	$errors = array();
	$infdata =& datamanager_init('Infraction', $vbulletin, ERRTYPE_STANDARD);
	$infdata->setr_info('warning', $vbulletin->GPC['warning']["{$vbulletin->GPC[infractionlevelid]}"]);
	$infdata->setr_info('postinfo', $postinfo);
	$infdata->setr_info('userinfo', $userinfo);
	$infdata->setr_info('threadinfo', $threadinfo);
	$infdata->set_info('banreason', $vbulletin->GPC['banreason']);

	if ($vbulletin->GPC['points'] !== '')
	{
		$vbulletin->GPC['points'] = intval($vbulletin->GPC['points']);
	}

	if (!$vbulletin->GPC['infractionlevelid'] AND $vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cangivearbinfraction'])
	{	// custom infraction
		if (empty($vbulletin->GPC['customreason']) OR (!$vbulletin->GPC['expires'] AND $vbulletin->GPC['period'] != 'N'))
		{
			if (empty($vbulletin->GPC['customreason']))
			{
				$errors[] = 'invalid_custom_infraction_description';
			}
			if (!$vbulletin->GPC['expires'] AND $vbulletin->GPC['period'] != 'N')
			{
				$errors[] = 'invalid_timeframe';
			}
		}
		else
		{
			switch($vbulletin->GPC['period'])
			{
				case 'D': $expires = mktime(date('H'), date('i'), date('s'), date('m'), date('d') + $vbulletin->GPC['expires'], date('y')); break;
				case 'M': $expires = mktime(date('H'), date('i'), date('s'), date('m') + $vbulletin->GPC['expires'], date('d'), date('y')); break;
				case 'N': $expires = 0; break;
				case 'H':
				default:
					$expires = mktime(date('H') + $vbulletin->GPC['expires'], date('i'), date('s'), date('m'), date('d'), date('y')); break;
			}
			$infdata->set('expires', $expires);
			$infdata->set('points', $vbulletin->GPC['points']);
			$infdata->set('customreason', $vbulletin->GPC['customreason']);
		}

		if ($vbulletin->GPC['points'] AND empty($vbulletin->GPC['banreason']) AND ($infractionban OR ($minimumpointsban AND $vbulletin->GPC['points'] + $userinfo['ipoints'] >= $minimumpointsban)))
		{
			$errors[] = 'invalid_banreason';
		}
	}
	else
	{
		$infractionlevel = verify_id('infractionlevel', $vbulletin->GPC['infractionlevelid'], 1, 1);
		if ($infractionlevel['extend'])
		{
			if (isset($infcache["$infractionlevel[infractionlevelid]"]['expires']))
			{
				if ($infcache["$infractionlevel[infractionlevelid]"]['expires'] == 0)
				{
					$infdata->set('expires', 0);
				}
				else if (($expiretime = $infcache["$infractionlevel[infractionlevelid]"]['expires'] - TIMENOW) > 0)
				{
					switch($infractionlevel['period'])
					{
						case 'D': $expires = $expiretime + mktime(date('H'), date('i'), date('s'), date('m'), date('d') + $infractionlevel['expires'], date('y')); break;
						case 'M': $expires = $expiretime + mktime(date('H'), date('i'), date('s'), date('m') + $infractionlevel['expires'], date('d'), date('y')); break;
						case 'N': $expires = 0; break;
						case 'H':
						default:
							$expires = $expiretime + mktime(date('H') + $infractionlevel['expires'], date('i'), date('s'), date('m'), date('d'), date('y')); break;
					}

					$infdata->set('expires', $expires);
				}
			}
		}

		if (!$vbulletin->GPC['warning']["{$vbulletin->GPC[infractionlevelid]}"] AND empty($vbulletin->GPC['banreason']) AND ($infractionban OR ($minimumpointsban AND $infractionlevel['points'] + $userinfo['ipoints'] >= $minimumpointsban)))
		{
			$errors[] = 'invalid_banreason';
		}

		$infdata->setr_info('infractionlevel', $infractionlevel);
		$infdata->set('infractionlevelid', $vbulletin->GPC['infractionlevelid']);
	}

	$banusergroupid = 0;
	$liftdate = 0;
	if (!empty($banlist) AND $points = $infdata->fetch_field('points'))
	{
		// Look for the longest ban that applies
		foreach ($banlist AS $ban)
		{
			if (($ban['method'] == 'infractions' AND $ban['amount'] == $totalinfractions + 1) OR ($ban['method'] == 'points' AND $ban['amount'] <= $userinfo['ipoints'] + $points))
			{
				if ($ban['liftdate'] == 0)
				{
					$liftdate = 0;
					$banusergroupid = $ban['banusergroupid'];
					break;
				}
				else if ($liftdate <= $ban['liftdate'])
				{
					$liftdate = $ban['liftdate'];
					$banusergroupid = $ban['banusergroupid'];
				}
			}
		}
		if ($banusergroupid AND !$liftdate)
		{
			$nocontact = true;
		}
	}

	$infdata->set('whoadded', $vbulletin->userinfo['userid']);
	$infdata->set('postid', $postinfo['postid']);
	$infdata->set('note', fetch_censored_text($vbulletin->GPC['note']));

	// include useful functions
	require_once(DIR . '/includes/functions_newpost.php');
	require_once(DIR . '/includes/functions_misc.php');

	// unwysiwygify the incoming data
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$vbulletin->GPC['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $vbulletin->options['privallowhtml']);
	}

	// parse URLs in message text
	if ($vbulletin->options['privallowbbcode'] AND $vbulletin->GPC['parseurl'])
	{
		$vbulletin->GPC['message'] = convert_url_to_bbcode($vbulletin->GPC['message']);
	}

	if ($show['pm'])
	{
		if (empty($vbulletin->GPC['message']) AND $vbulletin->options['uimessage'] AND !$nocontact)
		{
			$errors[] = 'nomessagetouser';
		}

		$pm['message'] =& $vbulletin->GPC['message'];
		$pm['parseurl'] =& $vbulletin->GPC['parseurl'];
		$pm['savecopy'] =& $vbulletin->GPC['savecopy'];
		$pm['signature'] =& $vbulletin->GPC['signature'];
		$pm['disablesmilies'] =& $vbulletin->GPC['disablesmilies'];
		$pm['receipt'] =& $vbulletin->GPC['receipt'];
		$pm['iconid'] =& $vbulletin->GPC['iconid'];

		// *************************************************************
		// PROCESS THE MESSAGE AND INSERT IT INTO THE DATABASE

		if ($vbulletin->userinfo['pmtotal'] >= $permissions['pmquota'])
		{
			$pm['savecopy'] = false;
		}

		$infraction = array(
			'username' => unhtmlspecialchars($userinfo['username']),
			'reason'   => ($infractionlevel['infractionlevelid']) ? fetch_phrase('infractionlevel' . $infractionlevel['infractionlevelid'] . '_title', 'infractionlevel', '', true, true, $userinfo['languageid']) : $vbulletin->GPC['customreason'],
			'message'  => fetch_censored_text($pm['message']),
			'points'   => $infdata->fetch_field('points')
		);

		$emailsubphrase = ($infraction['points'] > 0) ? 'infraction_received' : 'warning_received';

		// if we have a specific post we can link to, link to it in the PM
		if (!empty($postinfo))
		{
			if ($vbulletin->options['privallowbbcode'])
			{
				$infraction['post'] = '[post]' . $postinfo['postid'] . '[/post]';
			}
			else
			{
				$infraction['post'] = $vbulletin->options['bburl'] . '/' . fetch_seo_url('thread', $threadinfo, array('p' => $postinfo['postid'])) . "#post$postinfo[postid]";
			}
			$emailphrase = $emailsubphrase . '_post';
			$infraction['pagetext'] =& $postinfo['pagetext'];
		}
		else
		{
			$infraction['post'] = '';
			$emailphrase = $emailsubphrase . '_profile';
		}

		eval(fetch_email_phrases($emailphrase, $userinfo['languageid'], $emailsubphrase));

		if (empty($message) OR empty($subject))
		{
			$errors[] = array('problem_with_x_phrase', $emailphrase);
		}

		// create the DM to do error checking and insert the new PM
		$pmdm =& datamanager_init('PM', $vbulletin, ERRTYPE_ARRAY);

		if (!empty($errors))
		{
			foreach ($errors AS $error)
			{
				$pmdm->error($error);
			}
		}

		$pmdm->set_info('savecopy',   $pm['savecopy']);
		$pmdm->set_info('receipt',    $pm['receipt']);
		$pmdm->set_info('cantrackpm', $cantrackpm);
		$pmdm->set_info('is_automated', true); // implies overridequota
		$pmdm->set('fromuserid', $vbulletin->userinfo['userid']);
		$pmdm->set('fromusername', $vbulletin->userinfo['username']);
		$pmdm->setr('title', $subject);
		$pmdm->set_recipients(unhtmlspecialchars($userinfo['username']), $permissions);
		$pmdm->setr('message', $message);
		$pmdm->setr('iconid', $pm['iconid']);
		$pmdm->set('dateline', TIMENOW);
		$pmdm->setr('showsignature', $pm['signature']);
		$pmdm->set('allowsmilie', $pm['disablesmilies'] ? 0 : 1);

		($hook = vBulletinHook::fetch_hook('private_insertpm_process')) ? eval($hook) : false;

		$pmdm->pre_save();

		if (!empty($pmdm->errors))
		{
			define('PMPREVIEW', 1);
			$preview = construct_errors($pmdm->errors); // this will take the preview's place
		}
		else if ($vbulletin->GPC['preview'] != '')
		{
			define('PMPREVIEW', 1);
			$old_finfo = $foruminfo;
			$foruminfo = array('forumid' => 'privatemessage');
			$preview = process_post_preview($pm);
			$foruminfo = $old_finfo;
		}
		else
		{
			// everything's good!
			$pmdm->save();

			clear_autosave_text('vBForum_Infraction', 0, $userinfo['userid'], $vbulletin->userinfo['userid']);
			($hook = vBulletinHook::fetch_hook('private_insertpm_complete')) ? eval($hook) : false;

			$postmessage =& $vbulletin->GPC['message'];
		}
		unset($pmdm);
	}
	else if ($showemail)
	{
		if (empty($vbulletin->GPC['message']) AND $vbulletin->options['uimessage'] AND !$nocontact)
		{
			$errors[] = 'nomessagetouser';
		}

		if (!empty($errors))
		{
			// include useful functions
			require_once(DIR . '/includes/functions_newpost.php');

			$postpreview = construct_errors(array_map('fetch_error', $errors));
			define('PMPREVIEW', 1);

			$postmessage = htmlspecialchars_uni($vbulletin->GPC['message']);
		}
		else
		{	// Email User
			require_once(DIR . '/includes/class_bbcode_alt.php');
			$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());
			$plaintext_parser->set_parsing_language($touserinfo['languageid']);

			$infraction = array(
				'username' => unhtmlspecialchars($userinfo['username']),
				'reason'   => ($infractionlevel['infractionlevelid']) ? fetch_phrase('infractionlevel' . $infractionlevel['infractionlevelid'] . '_title', 'infractionlevel', '', true, true, $userinfo['languageid']) : $vbulletin->GPC['customreason'],
				'message'  =>& $vbulletin->GPC['message'],
				'points'   => $infdata->fetch_field('points'),
			);

			$emailsubphrase = ($infraction['points'] > 0) ? 'infraction_received' : 'warning_received';

			// if we have a specific post we can link to, link to it
			if (!empty($postinfo))
			{
				$infraction['post'] = $vbulletin->options['bburl'] . '/' . fetch_seo_url('thread', $threadinfo, array('p' => $postinfo['postid'])) . "#post$postinfo[postid]";
				$infraction['pagetext'] =& $postinfo['pagetext'];
				$emailphrase = $emailsubphrase . '_post';
			}
			else
			{
				$infraction['post'] = '';
				$emailphrase = $emailsubphrase . '_profile';
			}

			eval(fetch_email_phrases($emailphrase, $userinfo['languageid'], $emailsubphrase));
			$message = $plaintext_parser->parse($message, 'privatemessage');

			vbmail($userinfo['email'], $subject, $message);
		}
	}
	else if (!empty($errors))
	{
		// include useful functions
		require_once(DIR . '/includes/functions_newpost.php');

		$postpreview = construct_errors(array_map('fetch_error', $errors));
		define('PMPREVIEW', 1);
	}

	if (!defined('PMPREVIEW'))
	{
		// trim the message so it's not too long
		if ($vbulletin->options['postmaxchars'] > 0)
		{
			$trimmed_postmessage = substr($vbulletin->GPC['message'], 0, $vbulletin->options['postmaxchars']);
		}
		else
		{
			$trimmed_postmessage =& $vbulletin->GPC['message'];
		}
		$infdata->set_info('message', $trimmed_postmessage);

		($hook = vBulletinHook::fetch_hook('infraction_update_process')) ? eval($hook) : false;

		$infdata->save();

		// Ban
		require_once(DIR . '/includes/adminfunctions.php');
		if (!empty($banlist) AND $points = $infdata->fetch_field('points') AND !is_unalterable_user($userinfo['userid']))
		{
			if ($banusergroupid)
			{
				// check to see if there is already a ban record for this user in the userban table
				if ($bancheck)
				{
					if (($liftdate == 0 OR $bancheck['liftdate'] < $liftdate) AND $bancheck['liftdate'] != 0)
					{
						// there is already a record - just update this record
						$db->query_write("
							UPDATE " . TABLE_PREFIX . "userban SET
								bandate = " . TIMENOW . ",
								liftdate = $liftdate,
								adminid = " . $vbulletin->userinfo['userid'] . ",
								reason = '" . $db->escape_string($vbulletin->GPC['banreason']) . "'
							WHERE userid = $userinfo[userid]
						");
					}
				}
				else
				{
					// insert a record into the userban table
					/*insert query*/
					$db->query_write("
						REPLACE INTO " . TABLE_PREFIX . "userban
							(userid, usergroupid, displaygroupid, customtitle, usertitle, adminid, bandate, liftdate, reason)
						VALUES
							($userinfo[userid], $userinfo[usergroupid], $userinfo[displaygroupid], $userinfo[customtitle], '" . $db->escape_string($userinfo['usertitle']) . "', " . $vbulletin->userinfo['userid'] . ", " . TIMENOW . ", $liftdate, '" . $db->escape_string($vbulletin->GPC['banreason']) . "')
					");
				}

				unset($usercache["$userinfo[userid]"]);
				$userinfo = fetch_userinfo($userinfo['userid']);

				// update the user record
				$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
				$userdm->set_existing($userinfo);
				$userdm->set('usergroupid', $banusergroupid);
				$userdm->set('displaygroupid', 0);

				// update the user's title if they've specified a special user title for the banned group
				if ($vbulletin->usergroupcache["$banusergroupid"]['usertitle'] != '')
				{
					$userdm->set('usertitle', $vbulletin->usergroupcache["$banusergroupid"]['usertitle']);
					$userdm->set('customtitle', 0);
				}
				$userdm->save();
				unset($userdm);
			}
		}

		($hook = vBulletinHook::fetch_hook('infraction_update_complete')) ? eval($hook) : false;

		if ($postinfo['postid'])
		{
			$vbulletin->url = fetch_seo_url('thread', $threadinfo, array('p' => $postinfo['postid'])) . "#post$postinfo[postid]";
			print_standard_redirect('redirect_infraction_added');  
		}
		else
		{
			print_standard_redirect('redirect_infraction_added');  
		}
	}
	else
	{
		($hook = vBulletinHook::fetch_hook('infraction_update_complete')) ? eval($hook) : false;

		unset($infdata);
		$note = htmlspecialchars_uni($vbulletin->GPC['note']);
		$customexpires = $vbulletin->GPC['expires'] ? $vbulletin->GPC['expires'] : '';
		$custompoints = $vbulletin->GPC['points'];
		$periodselected = array($vbulletin->GPC['period'] => 'selected="selected"');
		$customreason = htmlspecialchars_uni($vbulletin->GPC['customreason']);
		$banreason = $vbulletin->GPC['banreason'];
		$_REQUEST['do'] = 'report';
	}
}

// ######################### REPORT INFRACTION ############################
if ($_REQUEST['do'] == 'report')
{
	($hook = vBulletinHook::fetch_hook('infraction_report_start')) ? eval($hook) : false;

	$infraction_ordering = array();
	$infractions = array();
	$infractionbits = '';

	$infraction_query = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "infractionlevel
	");
	while ($infraction = $db->fetch_array($infraction_query))
	{
		$infractions["$infraction[infractionlevelid]"] = $infraction;
		$infraction_ordering["$infraction[points]"]["$infraction[infractionlevelid]"] = htmlspecialchars_uni($vbphrase['infractionlevel' . $infraction['infractionlevelid'] . '_title']);
	}

	// sort by: points and then infraction title
	ksort($infraction_ordering);
	foreach ($infraction_ordering AS $infraction_groups_points)
	{
		natcasesort($infraction_groups_points); // value is infraction title
		foreach ($infraction_groups_points AS $id => $null)
		{
			$infraction = $infractions["$id"];
			$expires = 0;
			$title = htmlspecialchars_uni($vbphrase['infractionlevel' . $infraction['infractionlevelid'] . '_title']);
			if (!($count = $infcache["$infraction[infractionlevelid]"]['count']))
			{
				$count = 0;
			}
			if ($infraction['extend'] AND isset($infcache["$infraction[infractionlevelid]"]['expires']))
			{
				if ($infcache["$infraction[infractionlevelid]"]['expires'] == 0)
				{
					$expires = construct_phrase($vbphrase['never']);  // make sure phrase exists
				}
				else if (($expiretime = $infcache["$infraction[infractionlevelid]"]['expires'] - TIMENOW) > 0)
				{
					switch($infraction['period'])
					{
						case 'D': $expiretime += mktime(date('H'), date('i'), date('s'), date('m'), date('d') + $infraction['expires'], date('y')); break;
						case 'M': $expiretime += mktime(date('H'), date('i'), date('s'), date('m') + $infraction['expires'], date('d'), date('y')); break;
						case 'N': $expiretime= 0; break;
						case 'H':
						default:
							$expiretime += mktime(date('H') + $infraction['expires'], date('i'), date('s'), date('m'), date('d'), date('y')); break;
					}
					$timeleft = $expiretime - TIMENOW;
					$decimal = $vbulletin->userinfo['lang_decimalsep'];
					if ($timeleft < 86400)
					{
						$expires = construct_phrase($vbphrase['x_hours'], preg_replace('#^(\d+)' . $decimal . '0#', '\\1', vb_number_format($timeleft / 3600, 1)));
					}
					else if ($timeleft < 2592000)
					{
						$expires = construct_phrase($vbphrase['x_days'], preg_replace('#^(\d+)' . $decimal . '0#', '\\1', vb_number_format($timeleft / 86400, 1)));
					}
					else
					{
						$expires = construct_phrase($vbphrase['x_months'], preg_replace('#^(\d+)' . $decimal . '0#', '\\1', vb_number_format($timeleft / 2592000, 1)));
					}
				}
			}

			if (!$expires)
			{
				switch($infraction['period'])
				{
					case 'H': $period = 'x_hours'; break;
					case 'D': $period = 'x_days'; break;
					case 'M': $period = 'x_months'; break;
					case 'N': $period = 'never'; break;
					default:
				}
				$expires = construct_phrase($vbphrase["$period"], $infraction['expires']);
			}

			$show['warning'] = $infraction['warning'] ? true : false;

			exec_switch_bg();

			$checked_warn = ($vbulletin->GPC['warning']["$infraction[infractionlevelid]"]) ? 'checked="checked"' : '';
			$checked_inf = (
				$vbulletin->GPC['infractionlevelid'] == $infraction['infractionlevelid'] OR
				(empty($infractionbits) AND empty($vbulletin->GPC['period']))

			) ? 'checked="checked"' : '';

			if ($infractionban)
			{
				$show['ban'] = true;
			}
			else if ($minimumpointsban AND $infraction['points'] + $userinfo['ipoints'] >= $minimumpointsban)
			{
				$pointsban = true;
				$show['ban'] = true;
			}
			else
			{
				$show['ban'] = false;
			}

			$templater = vB_Template::create('userinfractionbit');
				$templater->register('checked_inf', $checked_inf);
				$templater->register('checked_warn', $checked_warn);
				$templater->register('count', $count);
				$templater->register('expires', $expires);
				$templater->register('infraction', $infraction);
				$templater->register('title', $title);
			$infractionbits .= $templater->render();
		}
	}

	if ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cangivearbinfraction'])
	{
		$checked_inf = ((!$vbulletin->GPC['infractionlevelid'] AND !empty($vbulletin->GPC['period']) OR empty($infractionbits))) ? 'checked="checked"' : '';
		$show['custominfraction'] = true;
	}

	if (!empty($banlist) AND ($show['custominfraction'] OR $infractionban OR $pointsban))
	{
		$show['banreason'] = true;
	}
	else
	{
		$show['banreason'] = false;
	}

	if (empty($infractionbits) AND !($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cangivearbinfraction']))
	{
		eval(standard_error(fetch_error('there_are_no_infraction_levels')));
	}

	// draw nav bar
	$navbits = array();
	if ($postinfo['postid'])
	{
		$parentlist = array_reverse(explode(',', $foruminfo['parentlist']));
		foreach ($parentlist AS $forumID)
		{
			$forumTitle = $vbulletin->forumcache["$forumID"]['title'];
			$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
		}
		$navbits[fetch_seo_url('thread', $threadinfo, array('p' => $postinfo['postid'])) . "#post$postinfo[postid]"] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];
	}
	else
	{
		$navbits[fetch_seo_url('member', $userinfo)] = construct_phrase($vbphrase['xs_profile'], $userinfo['username']);
	}

	$navbits[''] = construct_phrase($vbphrase['user_infraction_for_x'], $userinfo['username']);
	$navbits = construct_navbits($navbits);

	if ($show['pm'])
	{
		require_once(DIR . '/includes/functions_newpost.php');

		// do initial checkboxes
		$checked = array();
		$signaturechecked = iif($vbulletin->userinfo['signature'] != '', 'checked="checked"');

		// setup for preview display
		if (defined('PMPREVIEW'))
		{
			$postpreview =& $preview;
			$pm['message'] = htmlspecialchars_uni($pm['message']);
			construct_checkboxes($pm);
		}
		else
		{
			construct_checkboxes(array(
				'savecopy' => true,
				'parseurl' => true,
				'signature' => iif($vbulletin->userinfo['signature'] !== '', true)
			));
		}

		$posticons = construct_icons($pm['iconid'], $vbulletin->options['privallowicons']);

		require_once(DIR . '/includes/functions_editor.php');
		$editorid = construct_edit_toolbar(
			$pm['message'],
			0,
			'privatemessage',
			$vbulletin->options['privallowsmilies'] ? 1 : 0,
			true,
			false,
			'fe',
			'',
			array(),
			'content',
			'vBForum_Infraction',
			0,
			$userinfo['userid'],
			defined('PMPREVIEW')
		);

		$show['parseurl'] = $vbulletin->options['privallowbbcode'];

		// build forum rules
		$bbcodeon = ($vbulletin->options['privallowbbcode'] ? $vbphrase['on'] : $vbphrase['off']);
		$imgcodeon = ($vbulletin->options['privallowbbimagecode'] ? $vbphrase['on'] : $vbphrase['off']);
		$videocodeon = ($vbulletin->options['privallowbbvideocode'] ? $vbphrase['on'] : $vbphrase['off']);
		$htmlcodeon = ($vbulletin->options['privallowhtml'] ? $vbphrase['on'] : $vbphrase['off']);
		$smilieson = ($vbulletin->options['privallowsmilies'] ? $vbphrase['on'] : $vbphrase['off']);

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

	}
	else if ($showemail)
	{	// Send email to user
		$show['email'] = true;
	}

	$infractiongroups = '';
	if ($userinfo['infractiongroupids'])
	{
		$groups = explode(',', $userinfo['infractiongroupids']);
		foreach($groups AS $groupid)
		{
			$infractiongroups .= (!empty($infractiongroups) ? ', ' : '') . $vbulletin->usergroupcache["$groupid"]['title'];
		}
		$show['groups'] = true;
	}

	$firstgroup = $firstpoints = $moregroups = '';
	$pgroups = $db->query_read_slave("
		SELECT orusergroupid, pointlevel
		FROM " . TABLE_PREFIX . "infractiongroup
		WHERE usergroupid IN (-1, $userinfo[usergroupid])
		ORDER BY pointlevel
	");
	while ($pgroup = $db->fetch_array($pgroups))
	{
		if ($vbulletin->usergroupcache["$pgroup[orusergroupid]"])
		{
			if ($firstgroup)
			{
				$show['moregroups'] = true;
				$grouptitle = $vbulletin->usergroupcache["$pgroup[orusergroupid]"]['title'];
				$points = $pgroup['pointlevel'];
				$templater = vB_Template::create('userinfraction_groupbit');
					$templater->register('grouptitle', $grouptitle);
					$templater->register('points', $points);
				$moregroups .= $templater->render();
			}
			else
			{
				$firstgroup = $vbulletin->usergroupcache["$pgroup[orusergroupid]"]['title'];
				$firstpoints = $pgroup['pointlevel'];
			}
			$show['possiblegroups'] = true;
		}
	}

	$show['signaturecheckbox'] = ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature'] AND $vbulletin->userinfo['signature']);

	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('infraction_report_complete')) ? eval($hook) : false;

	$url =& $vbulletin->url;
	$templater = vB_Template::create('userinfraction');
		$templater->register_page_templates();
		$templater->register('banbits', $banbits);
		$templater->register('banreason', $banreason);
		$templater->register('checked', $checked);
		$templater->register('checked_inf', $checked_inf);
		$templater->register('customexpires', $customexpires);
		$templater->register('custompoints', $custompoints);
		$templater->register('customreason', $customreason);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('editorid', $editorid);
		$templater->register('firstgroup', $firstgroup);
		$templater->register('firstpoints', $firstpoints);
		$templater->register('foruminfo', $foruminfo);
		$templater->register('forumrules', $forumrules);
		$templater->register('infractionbits', $infractionbits);
		$templater->register('infractiongroups', $infractiongroups);
		$templater->register('messagearea', $messagearea);
		$templater->register('moregroups', $moregroups);
		$templater->register('navbar', $navbar);
		$templater->register('note', $note);
		$templater->register('periodselected', $periodselected);
		$templater->register('posticons', $posticons);
		$templater->register('postinfo', $postinfo);
		$templater->register('postmessage', $postmessage);
		$templater->register('postpreview', $postpreview);
		$templater->register('selectedicon', $selectedicon);
		$templater->register('totalinfractions', $totalinfractions);
		$templater->register('url', $url);
		$templater->register('userinfo', $userinfo);
	print_output($templater->render());
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 62098 $
|| ####################################################################
\*======================================================================*/
?>
