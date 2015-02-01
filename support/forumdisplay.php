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
define('THIS_SCRIPT', 'forumdisplay');
define('CSRF_PROTECTION', true);
define('FRIENDLY_URL_LINK', 'forum');

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('forumdisplay', 'inlinemod', 'prefix');

// get special data templates from the datastore
$specialtemplates = array(
	'iconcache',
	'mailqueue',
	'prefixcache'
);

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'none' => array(
		'ad_forum_below_threadlist',
		'FORUMDISPLAY',
		'threadbit',
		'threadbit_deleted',
		'threadbit_announcement',
		'forumhome_lastpostby',
		'forumhome_subforums',
		'forumhome_forumbit_level1_post',
		'forumhome_forumbit_level2_post',
		'forumhome_forumbit_level1_nopost',
		'forumhome_forumbit_level2_nopost',
		'forumdisplay_sortarrow',
		'forumrules',
		'optgroup',
		'threadadmin_imod_menu_thread',
	)
);

// ####################### PRE-BACK-END ACTIONS ##########################
function exec_postvar_call_back()
{
	global $vbulletin;

	$vbulletin->input->clean_array_gpc('r', array(
		'forumid'	=> TYPE_STR,
	));

	$goto = '';
	$url = '';
	// jump from forumjump
	switch ($vbulletin->GPC['forumid'])
	{
		case 'search': $goto = 'search'; break;
		case 'pm':     $goto = 'private'; break;
		case 'wol':    $goto = 'online'; break;
		case 'cp':     $goto = 'usercp'; break;
		case 'subs':   $goto = 'subscription'; break;
		case 'home':
		case '-1':     $url = fetch_seo_url('forumhome|js', array()); break;
	}

	// intval() forumid since having text in it is not expected anywhere else and it can't be "cleaned" a second time
	$vbulletin->GPC['forumid'] = intval($vbulletin->GPC['forumid']);

	if ($goto != '')
	{
		$url = "$goto.php?";
		if (!empty($vbulletin->session->vars['sessionurl_js']))
		{
			$url .= $vbulletin->session->vars['sessionurl_js'];
		}
	}

	if ($url != '')
	{
			exec_header_redirect($url);
	}
	// end forumjump redirects
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_forumlist.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/functions_forumdisplay.php');
require_once(DIR . '/includes/functions_prefix.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_forum_url();

($hook = vBulletinHook::fetch_hook('forumdisplay_start')) ? eval($hook) : false;

// ############################### start mark forums read ###############################
if ($_REQUEST['do'] == 'markread')
{
	// Prevent CSRF. See #32785
	$vbulletin->input->clean_array_gpc('r', array(
		'markreadhash' => TYPE_STR,
	));

	if (!VB_API AND !verify_security_token($vbulletin->GPC['markreadhash'], $vbulletin->userinfo['securitytoken_raw']))
	{
		eval(standard_error(fetch_error('security_token_invalid', $vbulletin->options['contactuslink'])));
	}

	require_once(DIR . '/includes/functions_misc.php');
	$mark_read_result = mark_forums_read($foruminfo['forumid']);

	$vbulletin->url = $mark_read_result['url'];
	print_standard_redirect($mark_read_result['phrase']);
}

// Don't allow access to anything below if an invalid $forumid was specified
cache_moderators();
if (!$foruminfo['forumid'])
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['forum'], $vbulletin->options['contactuslink'])));
}

// ############################### start enter password ###############################
if ($_REQUEST['do'] == 'doenterpwd')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'newforumpwd' => TYPE_STR,
		'url' => TYPE_STR,
		'postvars' => TYPE_BINARY,
	));

	if ($foruminfo['password'] == $vbulletin->GPC['newforumpwd'])
	{
		// set a temp cookie for guests
		if (!$vbulletin->userinfo['userid'])
		{
			set_bbarray_cookie('forumpwd', $foruminfo['forumid'], md5($vbulletin->userinfo['userid'] . $vbulletin->GPC['newforumpwd']));
		}
		else
		{
			set_bbarray_cookie('forumpwd', $foruminfo['forumid'], md5($vbulletin->userinfo['userid'] . $vbulletin->GPC['newforumpwd']), 1);
		}

		if ($vbulletin->GPC['url'] == fetch_seo_url('forumhome|nosession', array()))
		{
			$vbulletin->GPC['url'] = fetch_seo_url('forum', $foruminfo);
		}
		//removed AND $vbulletin->GPC['url'] != 'forumdisplay.php'.  This hasn't worked for some time
		//($vbulletin->GPC['url'] is fully qualified now) and this case should to the right thing
		//if a forumdisplay link is provided.  Trying to catch all of the potential SEO link cases
		//is going to prove annoying.
		else if ($vbulletin->GPC['url'] != '' )
		{
			$vbulletin->GPC['url'] = str_replace('"', '', $vbulletin->GPC['url']);
		}
		else
		{
			$vbulletin->GPC['url'] = fetch_seo_url('forum', $foruminfo);
		}

		// Allow POST based redirection...
		if ($vbulletin->GPC['postvars'] != '')
		{
			if (($check = verify_client_string($vbulletin->GPC['postvars'])) !== false)
			{
				$temp = unserialize($check);
				if ($temp['do'] == 'doenterpwd')
				{
					$vbulletin->GPC['postvars'] = '';
				}
			}
			else
			{
				$vbulletin->GPC['postvars'] = '';
			}
		}

		// workaround IIS cookie+location header bug
		$forceredirect = (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);
		print_standard_redirect('forumpasswordcorrect', true, $forceredirect);
	}
	else
	{
		require_once(DIR . '/includes/functions_misc.php');

		$vbulletin->GPC['url'] = str_replace('&amp;', '&', $vbulletin->GPC['url']);
		$postvars = construct_post_vars_html()
			. '<input type="hidden" name="securitytoken" value="' . $vbulletin->userinfo['securitytoken'] . '" />';

		//use the basic link here.  I'm not sure how the advanced link will play with the postvars in the form.
 		require_once(DIR . '/includes/class_friendly_url.php');
		$forumlink = vB_Friendly_Url::fetchLibrary($vbulletin, 'forum|nosession',
			$foruminfo, array('do' => 'doenterpwd'));
		$forumlink = $forumlink->get_url(FRIENDLY_URL_OFF);

		// TODO; Convert 'forumpasswordincorrect' to vB4 style
		eval(standard_error(fetch_error('forumpasswordincorrect',
			$vbulletin->session->vars['sessionhash'],
			htmlspecialchars_uni($vbulletin->GPC['url']),
			$foruminfo['forumid'],
			$postvars,
			10,
			1,
			$forumlink
		)));
	}
}

// ###### END SPECIAL PATHS

// These $_REQUEST values will get used in the sort template so they are assigned to normal variables
$perpage =  $vbulletin->input->clean_gpc('r', 'perpage', TYPE_UINT);
$pagenumber = $vbulletin->input->clean_gpc('r', 'pagenumber', TYPE_UINT);
$daysprune = $vbulletin->input->clean_gpc('r', 'daysprune', TYPE_INT);
$sortfield = $vbulletin->input->clean_gpc('r', 'sortfield', TYPE_STR);
$sortorder = $vbulletin->input->clean_gpc('r', 'sortorder', TYPE_STR);

// get permission to view forum
$_permsgetter_ = 'forumdisplay';
$forumperms = fetch_permissions($foruminfo['forumid']);
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
{
	print_no_permission();
}

// disable thread preview if we can't view threads
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
{
	$vbulletin->options['threadpreview'] = 0;
}

// check if there is a forum password and if so, ensure the user has it set
verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

// verify that we are at the canonical SEO url
// and redirect to this if not
verify_seo_url('forum', $foruminfo, array('pagenumber' => $_REQUEST['pagenumber']));

// get vbulletin->iforumcache - for use by makeforumjump and forums list
// fetch the forum even if they are invisible since its needed
// for the title but we'll unset that further down
// also fetch subscription info for $show['subscribed'] variable
cache_ordered_forums(1, 1, $vbulletin->userinfo['userid']);

$show['newthreadlink'] = iif(!$show['search_engine'] AND $foruminfo['allowposting'] AND $foruminfo['cancontainthreads'], true, false);
$show['newthreadlink'] = ($show['newthreadlink'] AND ($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostnew']));
$show['threadicons'] = iif ($foruminfo['allowicons'], true, false);
$show['threadratings'] = iif ($foruminfo['allowratings'], true, false);
$show['subscribed_to_forum'] = ($vbulletin->forumcache["$foruminfo[forumid]"]['subscribeforumid'] != '' ? true : false);

if (!$daysprune)
{
	if ($vbulletin->userinfo['daysprune'])
	{
		$daysprune = $vbulletin->userinfo['daysprune'];
	}
	else
	{
		$daysprune = iif($foruminfo['daysprune'], $foruminfo['daysprune'], 30);
	}
}

// ### GET FORUMS, PERMISSIONS, MODERATOR iCACHES ########################

// draw nav bar
$navbits = array();
$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];
$parentlist = array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3)));
foreach ($parentlist AS $forumID)
{
	$forumTitle = $vbulletin->forumcache["$forumID"]['title'];
	$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
}

// pop the last element off the end of the $nav array so that we can show it without a link
array_pop($navbits);

$navbits[''] = $foruminfo['title'];
$navbits = construct_navbits($navbits);
$navbar = render_navbar_template($navbits);

$moderatorslist = array();
$listexploded = explode(',', $foruminfo['parentlist']);
$showmods = array();
$show['moderators'] = false;
$totalmods = 0;
foreach ($listexploded AS $parentforumid)
{
	if (!$imodcache["$parentforumid"] OR $parentforumid == -1)
	{
		continue;
	}

	foreach ($imodcache["$parentforumid"] AS $moderator)
	{
		if ($showmods["$moderator[userid]"] === true)
		{
			continue;
		}

		($hook = vBulletinHook::fetch_hook('forumdisplay_moderator')) ? eval($hook) : false;

		$showmods["$moderator[userid]"] = true;
		$moderator['comma'] = $vbphrase['comma_space'];

		$totalmods++;
		$show['moderators'] = true;
		$moderatorslist[$totalmods] = $moderator;
	}
}

// Last element
if ($totalmods)
{
	$moderatorslist[$totalmods]['comma'] = '';
}

// ### BUILD FORUMS LIST #################################################

// get an array of child forum ids for this forum
$foruminfo['childlist'] = explode(',', $foruminfo['childlist']);

// define max depth for forums display based on $vbulletin->options[forumhomedepth]
define('MAXFORUMDEPTH', $vbulletin->options['forumdisplaydepth']);

if (($vbulletin->options['showforumusers'] == 1 OR $vbulletin->options['showforumusers'] == 2 OR ($vbulletin->options['showforumusers'] > 2 AND $vbulletin->userinfo['userid'])) AND !$show['search_engine'])
{
	$datecut = TIMENOW - $vbulletin->options['cookietimeout'];
	$forumusers = $db->query_read_slave("
		SELECT user.username, (user.options & " . $vbulletin->bf_misc_useroptions['invisible'] . ") AS invisible, user.usergroupid,
			session.userid, session.inforum, session.lastactivity, session.badlocation,
			IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, infractiongroupid
		FROM " . TABLE_PREFIX . "session AS session
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = session.userid)
		WHERE session.lastactivity > $datecut
		ORDER BY" . iif($vbulletin->options['showforumusers'] == 1 OR $vbulletin->options['showforumusers'] == 3, " username ASC,") . " lastactivity DESC
	");

	$numberregistered = 0;
	$numberguest = 0;
	$numbervisible = 0;
	$doneuser = array();
	$activeusers = array();

	if ($vbulletin->userinfo['userid'])
	{
		// fakes the user being in this forum
		$loggedin = array(
			'userid'        => $vbulletin->userinfo['userid'],
			'username'      => $vbulletin->userinfo['username'],
			'invisible'     => $vbulletin->userinfo['invisible'],
			'invisiblemark' => $vbulletin->userinfo['invisiblemark'],
			'inforum'       => $foruminfo['forumid'],
			'lastactivity'  => TIMENOW,
			'musername'     => $vbulletin->userinfo['musername'],
		);
		$numberregistered = 1;
		$numbervisible = 1;
		fetch_online_status($loggedin);

		($hook = vBulletinHook::fetch_hook('forumdisplay_loggedinuser')) ? eval($hook) : false;

		$loggedin['comma'] = $vbphrase['comma_space'];
		$activeusers[$numberregistered] = $loggedin;
		$doneuser["{$vbulletin->userinfo['userid']}"] = 1;
	}

	$inforum = array();

	// this require the query to have lastactivity ordered by DESC so that the latest location will be the first encountered.
	while ($loggedin = $db->fetch_array($forumusers))
	{
		if ($loggedin['badlocation'])
		{
			continue;
		}

		if (empty($doneuser["$loggedin[userid]"]))
		{
			if (in_array($loggedin['inforum'], $foruminfo['childlist']) AND $loggedin['inforum'] != -1)
			{
				if (!$loggedin['userid'])
				{
					// this is a guest
					$numberguest++;
					$inforum["$loggedin[inforum]"]++;
				}
				else
				{
					$numberregistered++;
					$inforum["$loggedin[inforum]"]++;

					($hook = vBulletinHook::fetch_hook('forumdisplay_loggedinuser')) ? eval($hook) : false;

					if (fetch_online_status($loggedin))
					{
						$numbervisible++;
						fetch_musername($loggedin);
						$loggedin['comma'] = $vbphrase['comma_space'];
						$activeusers[$numbervisible] = $loggedin;
					}
				}
			}
			if ($loggedin['userid'])
			{
				$doneuser["$loggedin[userid]"] = 1;
			}
		}
	}

	// Last element
	if ($numbervisible)
	{
		$activeusers[$numbervisible]['comma'] = '';
	}

	if (!$vbulletin->userinfo['userid'])
	{
		$numberguest = ($numberguest == 0) ? 1 : $numberguest;
	}
	$totalonline = $numberregistered + $numberguest;
	unset($joingroupid, $key, $datecut, $invisibleuser, $userinfo, $userid, $loggedin, $index, $value, $forumusers, $parentarray );

	$show['activeusers'] = true;
}
else
{
	$show['activeusers'] = false;
}

// #############################################################################
// get read status for this forum and children
$unreadchildforums = 0;
foreach ($foruminfo['childlist'] AS $val)
{
	if ($val == -1 OR $val == $foruminfo['forumid'])
	{
		continue;
	}

	if ($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid'])
	{
		$lastread_child = max($vbulletin->forumcache["$val"]['forumread'], TIMENOW - ($vbulletin->options['markinglimit'] * 86400));
	}
	else
	{
		$lastread_child = max(intval(fetch_bbarray_cookie('forum_view', $val)), $vbulletin->userinfo['lastvisit']);
	}

	if ($vbulletin->forumcache["$val"]['lastpost'] > $lastread_child)
	{
		$unreadchildforums = 1;
		break;
	}
}

$forumbits = construct_forum_bit($foruminfo['forumid']);

// admin tools

$show['post_queue'] = can_moderate($foruminfo['forumid'], 'canmoderateposts');
$show['attachment_queue'] = can_moderate($foruminfo['forumid'], 'canmoderateattachments');
$show['mass_move'] = can_moderate($foruminfo['forumid'], 'canmassmove');
$show['mass_prune'] = can_moderate($foruminfo['forumid'], 'canmassprune');

$show['post_new_announcement'] = can_moderate($foruminfo['forumid'], 'canannounce');
$show['addmoderator'] = ($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']);

$show['adminoptions'] = ($show['post_queue'] OR $show['attachment_queue'] OR $show['mass_move'] OR $show['mass_prune'] OR $show['addmoderator'] OR $show['post_new_announcement']);

$navpopup = array(
	'id'    => 'forumdisplay_navpopup',
	'title' => $foruminfo['title_clean'],
	'link'  => fetch_seo_url('forum', $foruminfo)
);
construct_quick_nav($navpopup);


/////////////////////////////////
if ($foruminfo['cancontainthreads'])
{
	/////////////////////////////////
	if ($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid'])
	{
		$foruminfo['forumread'] = $vbulletin->forumcache["$foruminfo[forumid]"]['forumread'];
		$lastread = max($foruminfo['forumread'], TIMENOW - ($vbulletin->options['markinglimit'] * 86400));
	}
	else
	{
		$bbforumview = intval(fetch_bbarray_cookie('forum_view', $foruminfo['forumid']));
		$lastread = max($bbforumview, $vbulletin->userinfo['lastvisit']);
	}

	// Inline Moderation
	$show['movethread'] = (can_moderate($forumid, 'canmanagethreads')) ? true : false;
	$show['deletethread'] = (can_moderate($forumid, 'candeleteposts') OR can_moderate($forumid, 'canremoveposts')) ? true : false;
	$show['approvethread'] = (can_moderate($forumid, 'canmoderateposts')) ? true : false;
	$show['openthread'] = (can_moderate($forumid, 'canopenclose')) ? true : false;
	$show['inlinemod'] = ($show['movethread'] OR $show['deletethread'] OR $show['approvethread'] OR $show['openthread']) ? true : false;
	$show['spamctrls'] = ($show['inlinemod'] AND $show['deletethread']);
	$url = $show['inlinemod'] ? SCRIPTPATH : '';

	// fetch popup menu
	if ($show['popups'] AND $show['inlinemod'])
	{
		$threadadmin_imod_menu_thread = vB_Template::create('threadadmin_imod_menu_thread')->render();
	}
	else
	{
		$threadadmin_imod_menu_thread = '';
	}

	// get announcements

	$announcebits = '';
	if ($show['threadicons'] AND $show['inlinemod'])
	{
		$announcecolspan = 6;
	}
	else if (!$show['threadicons'] AND !$show['inlinemod'])
	{
		$announcecolspan = 4;
	}
	else
	{
		$announcecolspan = 5;
	}

	$mindate = TIMENOW - 2592000; // 30 days

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('forumdisplay_announcement_query')) ? eval($hook) : false;

	$announcements = $db->query_read_slave("
		SELECT
			announcement.announcementid, startdate, title, announcement.views,
			user.username, user.userid, user.usertitle, user.customtitle, user.usergroupid,
			IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, infractiongroupid
			" . (($vbulletin->userinfo['userid']) ? ", NOT ISNULL(announcementread.announcementid) AS readannounce" : "") . "
			$hook_query_fields
		FROM " . TABLE_PREFIX . "announcement AS announcement
		" . (($vbulletin->userinfo['userid']) ? "LEFT JOIN " . TABLE_PREFIX . "announcementread AS announcementread ON (announcementread.announcementid = announcement.announcementid AND announcementread.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = announcement.userid)
		$hook_query_joins
		WHERE startdate <= " . TIMENOW . "
			AND enddate >= " . TIMENOW . "
			AND " . fetch_forum_clause_sql($foruminfo['forumid'], 'forumid') . "
			$hook_query_where
		ORDER BY startdate DESC, announcement.announcementid DESC
		" . iif($vbulletin->options['oneannounce'], "LIMIT 1")
	);

	while ($announcement = $db->fetch_array($announcements))
	{
		fetch_musername($announcement);
		$announcement['title'] = fetch_censored_text($announcement['title']);
		$announcement['postdate'] = vbdate($vbulletin->options['dateformat'], $announcement['startdate']);
		if ($announcement['readannounce'] OR $announcement['startdate'] <= $mindate)
		{
			$announcement['statusicon'] = 'old';
		}
		else
		{
			$announcement['statusicon'] = 'new';
		}
		$announcement['views'] = vb_number_format($announcement['views']);
		$announcementidlink = iif(!$vbulletin->options['oneannounce'] , "&amp;a=$announcement[announcementid]");

		($hook = vBulletinHook::fetch_hook('forumdisplay_announcement')) ? eval($hook) : false;

		$templater = vB_Template::create('threadbit_announcement');
			$templater->register('announcecolspan', $announcecolspan);
			$templater->register('announcement', $announcement);
			$templater->register('announcementidlink', $announcementidlink);
			$templater->register('foruminfo', $foruminfo);
		$announcebits .= $templater->render();
	}

	// display threads
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']))
	{
		$limitothers = "AND thread.postuserid = " . $vbulletin->userinfo['userid'] . " AND " . $vbulletin->userinfo['userid'] . " <> 0";
	}
	else
	{
		$limitothers = '';
	}

	if (can_moderate($foruminfo['forumid']))
	{
		$redirectjoin = "LEFT JOIN " . TABLE_PREFIX . "threadredirect AS threadredirect ON(thread.open = 10 AND thread.threadid = threadredirect.threadid)";
	}
	else
	{
		$redirectjoin = '';
	}

	// filter out deletion notices if can't be seen
	if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canseedelnotice'] OR can_moderate($foruminfo['forumid']))
	{
		$canseedelnotice = true;
		$deljoin = "LEFT JOIN " . TABLE_PREFIX . "deletionlog AS deletionlog ON(thread.threadid = deletionlog.primaryid AND deletionlog.type = 'thread')";
	}
	else
	{
		$canseedelnotice = false;
		$deljoin = '';
	}

	// remove threads from users on the global ignore list if user is not a moderator
	if ($Coventry = fetch_coventry('string') AND !can_moderate($foruminfo['forumid']))
	{
		$globalignore = "AND thread.postuserid NOT IN ($Coventry) ";
	}
	else
	{
		$globalignore = '';
	}

	// look at thread limiting options
	$stickyids = '';
	$stickycount = 0;
	if ($daysprune != -1)
	{
		if ($vbulletin->userinfo['userid'] AND in_coventry($vbulletin->userinfo['userid'], true))
		{
			$tachyjoin = "LEFT JOIN " . TABLE_PREFIX . "tachythreadpost AS tachythreadpost ON " .
				"(tachythreadpost.threadid = thread.threadid AND tachythreadpost.userid = " . $vbulletin->userinfo['userid'] . ")";
			$datecut = " AND (thread.lastpost >= " . (TIMENOW - ($daysprune * 86400)) . " OR tachythreadpost.lastpost >= " . (TIMENOW - ($daysprune * 86400)) . ")";
		}
		else
		{
			$datecut = "AND lastpost >= " . (TIMENOW - ($daysprune * 86400));
			$tachyjoin = "";
		}
		$show['noposts'] = false;
	}
	else
	{
		$tachyjoin = "";
		$datecut = "";
		$show['noposts'] = true;
	}

	// complete form fields on page
	$daysprunesel = iif($daysprune == -1, 'all', $daysprune);
	$daysprunesel = array($daysprunesel => 'selected="selected"');

	$vbulletin->input->clean_array_gpc('r', array(
		'sortorder' => TYPE_NOHTML,
		'prefixid'  => TYPE_NOHTML,
	));

	// prefix options
	$prefix_options = fetch_prefix_html($foruminfo['forumid'], $vbulletin->GPC['prefixid']);
	$prefix_selected = array('anythread', 'anythread' => '', 'none' => '');
	if ($vbulletin->GPC['prefixid'])
	{
		//no prefix id
		if ($vbulletin->GPC['prefixid'] == '-1')
		{
			$prefix_filter = "AND thread.prefixid = ''";
			$prefix_selected['none'] = ' selected="selected"';
		}

		//any prefix id
		else if ($vbulletin->GPC['prefixid'] == '-2')
		{
			$prefix_filter = "AND thread.prefixid <> ''";
			$prefix_selected['anyprefix'] = ' selected="selected"';
		}

		//specific prefix id
		else
		{
			$prefix_filter = "AND thread.prefixid = '" . $db->escape_string($vbulletin->GPC['prefixid']) . "'";
		}
	}
	else
	{
		$prefix_filter = '';
		$prefix_selected['anythread'] = ' selected="selected"';
	}

	// default sorting methods
	if (empty($sortfield))
	{
		$sortfield = $foruminfo['defaultsortfield'];
	}
	if (empty($sortorder))
	{
		$sortorder = $foruminfo['defaultsortorder'];
	}

	// look at sorting options:
	if ('asc' != $sortorder)
	{
		$sqlsortorder = 'DESC';
		$order = array('desc' => 'checked="checked"');
	}
	else
	{
		$sqlsortorder = '';
		$order = array('asc' => 'checked="checked"');
	}

	$sqlsortfield2 = '';

	switch ($sortfield)
	{
		case 'lastpost':
		case 'replycount':
		case 'views':
			$sqlsortfield = $sortfield;
			break;
		case 'title':
		case 'dateline':
		case 'postusername':
			$sqlsortfield = 'thread.'.$sortfield;
			break;
		case 'voteavg':
			if ($foruminfo['allowratings'])
			{
				$sqlsortfield = 'voteavg';
				$sqlsortfield2 = 'votenum';
				break;
			}
		// else, use last post
		default:
			$handled = false;
			($hook = vBulletinHook::fetch_hook('forumdisplay_sort')) ? eval($hook) : false;
			if (!$handled)
			{
				$sqlsortfield = 'lastpost';
				$sortfield = 'lastpost';
			}
	}
	$sort = array($sortfield => 'selected="selected"');

	if (!can_moderate($forumid, 'canmoderateposts'))
	{
		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canseedelnotice']))
		{
			$visiblethreads = " AND visible = 1 ";
		}
		else
		{
			$visiblethreads = " AND visible IN (1,2)";
		}
	}
	else
	{
		$visiblethreads = " AND visible IN (0,1,2)";
	}

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('forumdisplay_query_threadscount')) ? eval($hook) : false;

	# Include visible IN (0,1,2) in order to hit upon the 4 column index
	$threadscount = $db->query_first_slave("
	SELECT COUNT(*) AS threads
	$hook_query_fields
	FROM " . TABLE_PREFIX . "thread AS thread
	$tachyjoin
	$hook_query_joins
	WHERE thread.forumid = $foruminfo[forumid]
		AND sticky = 0
		$prefix_filter
		$visiblethreads
		$globalignore
		$limitothers
		$datecut
		$hook_query_where
	");
	$totalthreads = $threadscount['threads'];

	$threadscount = $db->query_first_slave("
	SELECT COUNT(*) AS newthread
	FROM " . TABLE_PREFIX . "thread AS thread
	$tachyjoin
	$hook_query_joins
	WHERE thread.forumid = $foruminfo[forumid]
		AND thread.lastpost > $lastread
		AND open <> 10
		AND sticky = 0
		$prefix_filter
		$visiblethreads
		$globalignore
		$limitothers
		$datecut
		$hook_query_where
	");
	$newthreads = $threadscount['newthread'];

	// set defaults
	sanitize_pageresults($totalthreads, $pagenumber, $perpage, 200, $vbulletin->options['maxthreads']);

	// get number of sticky threads for the first page
	// on the first page there will be the sticky threads PLUS the $perpage other normal threads
	// not quite a bug, but a deliberate feature!
	if ($pagenumber == 1 OR $vbulletin->options['showstickies'])
	{
		$stickies = $db->query_read_slave("
			SELECT thread.threadid, lastpost, open
			FROM " . TABLE_PREFIX . "thread AS thread
			WHERE forumid = $foruminfo[forumid]
				AND sticky = 1
				$prefix_filter
				$visiblethreads
				$limitothers
				$globalignore
		");
		while ($thissticky = $db->fetch_array($stickies))
		{
			$stickycount++;
			if ($thissticky['lastpost'] >= $lastread AND $thissticky['open'] <> 10)
			{
				$newthreads++;
			}
			$stickyids .= ",$thissticky[threadid]";
		}
		$db->free_result($stickies);
		unset($thissticky, $stickies);
	}


	$limitlower = ($pagenumber - 1) * $perpage;
	$limitupper = ($pagenumber) * $perpage;

	if ($limitupper > $totalthreads)
	{
		$limitupper = $totalthreads;
		if ($limitlower > $totalthreads)
		{
			$limitlower = ($totalthreads - $perpage) - 1;
		}
	}
	if ($limitlower < 0)
	{
		$limitlower = 0;
	}

	if ($foruminfo['allowratings'])
	{
		$vbulletin->options['showvotes'] = intval($vbulletin->options['showvotes']);
		$votequery = "
			IF(votenum >= " . $vbulletin->options['showvotes'] . ", votenum, 0) AS votenum,
			IF(votenum >= " . $vbulletin->options['showvotes'] . " AND votenum > 0, votetotal / votenum, 0) AS voteavg,
		";
	}
	else
	{
		$votequery = '';
	}

	if ($vbulletin->options['threadpreview'] > 0)
	{
		$previewfield = "post.pagetext AS preview,";
		$previewjoin = "LEFT JOIN " . TABLE_PREFIX . "post AS post ON(post.postid = thread.firstpostid)";
	}
	else
	{
		$previewfield = '';
		$previewjoin = '';
	}

	if ($vbulletin->userinfo['userid'] AND in_coventry($vbulletin->userinfo['userid'], true))
	{
		$tachyjoin = "
			LEFT JOIN " . TABLE_PREFIX . "tachythreadpost AS tachythreadpost ON
				(tachythreadpost.threadid = thread.threadid AND tachythreadpost.userid = " . $vbulletin->userinfo['userid'] . ")
			LEFT JOIN " . TABLE_PREFIX . "tachythreadcounter AS tachythreadcounter ON
				(tachythreadcounter.threadid = thread.threadid AND tachythreadcounter.userid = " . $vbulletin->userinfo['userid'] . ")
		";
		$tachy_columns = "
			IF(tachythreadpost.userid IS NULL, thread.lastpost, tachythreadpost.lastpost) AS lastpost,
			IF(tachythreadpost.userid IS NULL, thread.lastposter, tachythreadpost.lastposter) AS lastposter,
			IF(tachythreadpost.userid IS NULL, thread.lastposterid, tachythreadpost.lastposterid) AS lastposterid,
			IF(tachythreadpost.userid IS NULL, thread.lastpostid, tachythreadpost.lastpostid) AS lastpostid,
			IF(tachythreadcounter.userid IS NULL, thread.replycount, thread.replycount + tachythreadcounter.replycount) AS replycount,
			IF(thread.views<=IF(tachythreadcounter.userid IS NULL, thread.replycount, thread.replycount + tachythreadcounter.replycount), IF(tachythreadcounter.userid IS NULL, thread.replycount, thread.replycount + tachythreadcounter.replycount)+1, thread.views) AS views
		";

	}
	else
	{
		$tachyjoin = '';
		$tachy_columns = 'thread.lastpost, thread.lastposter, thread.lastposterid, thread.lastpostid, thread.replycount, IF(thread.views<=thread.replycount, thread.replycount+1, thread.views) AS views';
	}

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('forumdisplay_query_threadid')) ? eval($hook) : false;

	$getthreadids = $db->query_read_slave("
		SELECT " . iif($sortfield == 'voteavg', $votequery) . " thread.threadid,
			$tachy_columns
			$hook_query_fields
		FROM " . TABLE_PREFIX . "thread AS thread
		$tachyjoin
		$hook_query_joins
		WHERE forumid = $foruminfo[forumid]
			AND sticky = 0
			$prefix_filter
			$visiblethreads
			$globalignore
			$limitothers
			$datecut
			$hook_query_where
		ORDER BY sticky DESC, $sqlsortfield $sqlsortorder" . (!empty($sqlsortfield2) ? ", $sqlsortfield2 $sqlsortorder" : '') . "
		LIMIT $limitlower, $perpage
	");

	$ids = '';
	while ($thread = $db->fetch_array($getthreadids))
	{
		$ids .= ',' . $thread['threadid'];
	}

	$ids .= $stickyids;

	$db->free_result($getthreadids);
	unset ($thread, $getthreadids);

	$fetchavatar = ($vbulletin->options['avatarenabled'] AND defined('VB_API') AND VB_API === true);

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('forumdisplay_query')) ? eval($hook) : false;

	$threads = $db->query_read_slave("
		SELECT $votequery $previewfield
			thread.threadid, thread.title AS threadtitle, thread.forumid, thread.pollid, thread.open, thread.postusername, thread.postuserid, thread.iconid AS threadiconid,
			thread.dateline, thread.notes, thread.visible, thread.sticky, thread.votetotal, thread.attach, $tachy_columns,
			thread.prefixid, thread.taglist, thread.hiddencount, thread.deletedcount, user.userid,
			user.membergroupids, user.infractiongroupids, user.usergroupid, user.homepage, user.options AS useroptions, IF(userlist.friend = 'yes', 1, 0) AS isfriend,
			user.lastactivity, user.lastvisit, IF(user.options & " . $vbulletin->bf_misc_useroptions['invisible'] . ", 1, 0) AS invisible
			" . ($fetchavatar ? "
				,avatar2.avatarpath AS api_avatarpath, NOT ISNULL(customavatar2.userid) AS api_hascustomavatar, customavatar2.dateline AS api_avatardateline, customavatar2.width AS api_avwidth, customavatar2.height AS api_avheight,
				user2.adminoptions AS api_adminoptions, user2.userid AS api_userid, user2.usergroupid AS api_usergroupid, user2.membergroupids AS api_membergroupids, user2.infractiongroupids AS api_infractiongroupids, user2.avatarrevision AS api_avatarrevision
			" : "") . "
			" . (($vbulletin->options['threadsubscribed'] AND $vbulletin->userinfo['userid']) ? ", NOT ISNULL(subscribethread.subscribethreadid) AS issubscribed" : "") . "
			" . ($deljoin ? ", deletionlog.userid AS del_userid, deletionlog.username AS del_username, deletionlog.reason AS del_reason" : "") . "
			" . (($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid']) ? ", threadread.readtime AS threadread" : "") . "
			" . ($redirectjoin ? ", threadredirect.expires" : "") . "
			$hook_query_fields
		FROM " . TABLE_PREFIX . "thread AS thread
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = thread.lastposterid)
			LEFT JOIN " . TABLE_PREFIX . "userlist AS userlist ON (userlist.relationid = user.userid AND userlist.type = 'buddy' AND userlist.userid = " . $vbulletin->userinfo['userid'] . ")
			" . ($fetchavatar ? "
				LEFT JOIN " . TABLE_PREFIX . "user AS user2 ON (thread.postuserid = user2.userid)
				LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar2 ON(avatar2.avatarid = user2.avatarid)
				LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar2 ON(customavatar2.userid = user2.userid)
			" : "") . "
			$deljoin
			" . (($vbulletin->options['threadsubscribed'] AND $vbulletin->userinfo['userid']) ?  " LEFT JOIN " . TABLE_PREFIX . "subscribethread AS subscribethread ON(subscribethread.threadid = thread.threadid AND subscribethread.userid = " . $vbulletin->userinfo['userid'] . " AND canview = 1)" : "") . "
			" . (($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid']) ? " LEFT JOIN " . TABLE_PREFIX . "threadread AS threadread ON (threadread.threadid = thread.threadid AND threadread.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
			$previewjoin
			$tachyjoin
			$redirectjoin
			$hook_query_joins
		WHERE thread.threadid IN (0$ids) $hook_query_where
		ORDER BY thread.sticky DESC, $sqlsortfield $sqlsortorder" . (!empty($sqlsortfield2) ? ", $sqlsortfield2 $sqlsortorder" : '') . "
	");
	unset($limitothers, $delthreadlimit, $deljoin, $datecut, $votequery, $sqlsortfield, $sqlsortorder, $threadids, $sqlsortfield2);

	// Get Dot Threads
	$dotthreads = fetch_dot_threads_array($ids);
	if ($vbulletin->options['showdots'] AND $vbulletin->userinfo['userid'])
	{
		$show['dotthreads'] = true;
	}
	else
	{
		$show['dotthreads'] = false;
	}

	unset($ids);

	$pageinfo = array();
	if ($vbulletin->GPC['prefixid'])
	{
		$pageinfo['prefixid'] = $vbulletin->GPC['prefixid'];
	}
	if ($vbulletin->GPC['daysprune'])
	{
		$pageinfo['daysprune'] = $daysprune;
	}

	$show['fetchseo'] = true;
	$oppositesort = $sortorder == 'asc' ? 'desc' : 'asc';

	$pageinfo_voteavg = $pageinfo + array('sort' => 'voteavg', 'order' => ('voteavg' == $sortfield) ? $oppositesort : 'desc');
	$pageinfo_title = $pageinfo + array('sort' => 'title', 'order' => ('title' == $sortfield) ? $oppositesort : 'asc');
	$pageinfo_postusername = $pageinfo + array('sort' => 'postusername', 'order' => ('postusername' == $sortfield) ? $oppositesort : 'asc');
	$pageinfo_flastpost = $pageinfo + array('sort' => 'lastpost', 'order' => ('lastpost' == $sortfield) ? $oppositesort : 'asc');
	$pageinfo_replycount = $pageinfo + array('sort' => 'replycount', 'order' => ('replycount' == $sortfield) ? $oppositesort : 'desc');
	$pageinfo_views = $pageinfo + array('sort' => 'views', 'order' => ('views' == $sortfield) ? $oppositesort : 'desc');

	$pageinfo_sort = $pageinfo + array(sort => $sortfield, 'order' => $oppositesort, 'pp' => $perpage, 'page' => $pagenumber);

	if ($totalthreads > 0 OR $stickyids)
	{
		if ($totalthreads > 0)
		{
			$limitlower++;
		}
		// check to see if there are any threads to display. If there are, do so, otherwise, show message

		if ($vbulletin->options['threadpreview'] > 0)
		{
			// Get Buddy List
			$buddy = array();
			if (trim($vbulletin->userinfo['buddylist']))
			{
				$buddylist = preg_split('/( )+/', trim($vbulletin->userinfo['buddylist']), -1, PREG_SPLIT_NO_EMPTY);
				foreach ($buddylist AS $buddyuserid)
				{
					$buddy["$buddyuserid"] = 1;
				}
			}
			DEVDEBUG('buddies: ' . implode(', ', array_keys($buddy)));
			// Get Ignore Users
			$ignore = array();
			if (trim($vbulletin->userinfo['ignorelist']))
			{
				$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
				foreach ($ignorelist AS $ignoreuserid)
				{
					if (!$buddy["$ignoreuserid"])
					{
						$ignore["$ignoreuserid"] = 1;
					}
				}
			}
			DEVDEBUG('ignored users: ' . implode(', ', array_keys($ignore)));
		}

		$show['threads'] = true;
		$threadbits = '';
		$threadbits_sticky = '';

		$counter = 0;
		$toread = 0;

		while ($thread = $db->fetch_array($threads))
		{ // AND $counter++ < $perpage)

			// build thread data
			$thread = process_thread_array($thread, $lastread, $foruminfo['allowicons'], $fetchavatar);
			$realthreadid = $thread['realthreadid'];

			if ($thread['sticky'])
			{
				$threadbit =& $threadbits_sticky;
			}
			else
			{
				$threadbit =& $threadbits;
			}

			($hook = vBulletinHook::fetch_hook('threadbit_display')) ? eval($hook) : false;

			// Soft Deleted Thread
			if ($thread['visible'] == 2)
			{
				$thread['deletedcount']++;
				$show['threadtitle'] = (can_moderate($forumid) OR ($vbulletin->userinfo['userid'] != 0 AND $vbulletin->userinfo['userid'] == $thread['postuserid'])) ? true : false;
				$show['deletereason'] = (!empty($thread['del_reason'])) ?  true : false;
				$show['viewthread'] = (can_moderate($forumid)) ? true : false;
				$show['managethread'] = (can_moderate($forumid, 'candeleteposts') OR can_moderate($forumid, 'canremoveposts')) ? true : false;
				$show['moderated'] = ($thread['hiddencount'] > 0 AND can_moderate($forumid, 'canmoderateposts')) ? true : false;
				$show['deletedthread'] = $canseedelnotice;
				$templater = vB_Template::create('threadbit_deleted');
					$templater->register('thread', $thread);
				$threadbit .= $templater->render();
			}
			else
			{
				if (!$thread['visible'])
				{
					$thread['hiddencount']++;
				}
				$show['moderated'] = ($thread['hiddencount'] > 0 AND can_moderate($forumid, 'canmoderateposts')) ? true : false;
				$show['deletedthread'] = ($thread['deletedcount'] > 0 AND $canseedelnotice) ? true : false;

				$pageinfo_lastpage = array();
				if ($show['pagenavmore'])
				{
					$pageinfo_lastpage['page'] = $thread['totalpages'];
				}
				$pageinfo_newpost = array('goto' => 'newpost');
				$pageinfo_lastpost = array('p' => $thread['lastpostid']);

				// prepare the member action drop-down menu
				$memberaction_dropdown = construct_memberaction_dropdown(fetch_lastposter_userinfo($thread));

				$templater = vB_Template::create('threadbit');
					$templater->register('pageinfo', array());
					$templater->register('pageinfo_lastpage', $pageinfo_lastpage);
					$templater->register('pageinfo_lastpost', $pageinfo_lastpost);
					$templater->register('pageinfo_newpost', $pageinfo_newpost);
					$templater->register('subscribethread', $subscribethread);
					$templater->register('memberaction_dropdown', $memberaction_dropdown);
					$templater->register('thread', $thread);
					$templater->register('threadid', $threadid);
				$threadbit .= $templater->render();
			}
		}
		$db->free_result($threads);
		unset($thread, $counter);

		$pageinfo_pagenav = array();
		if (!empty($vbulletin->GPC['perpage']))
		{
			$pageinfo_pagenav['pp'] = $perpage;
		}
		if (!empty($vbulletin->GPC['prefixid']))
		{
			$pageinfo_pagenav['prefixid'] = $vbulletin->GPC['prefixid'];
		}
		if (!empty($vbulletin->GPC['sortfield']))
		{
			$pageinfo_pagenav['sort'] = $sortfield;
		}
		if (!empty($vbulletin->GPC['sortorder']))
		{
			$pageinfo_pagenav['order'] = $vbulletin->GPC['sortorder'];
		}
		if (!empty($vbulletin->GPC['daysprune']))
		{
			$pageinfo_pagenav['daysprune'] = $daysprune;
		}

		$pagenav = construct_page_nav(
			$pagenumber,
			$perpage,
			$totalthreads,
			'',
			'',
			'',
			'forum',
			$foruminfo,
			$pageinfo_pagenav
		);

		$templater = vB_Template::create('forumdisplay_sortarrow');
			$templater->register('oppositesort', $oppositesort);
		$sortarrow[$sortfield] = $templater->render();
	}
	unset($threads, $dotthreads);

	// get colspan for bottom bar
	$foruminfo['bottomcolspan'] = 5;
	if ($foruminfo['allowicons'])
	{
		$foruminfo['bottomcolspan']++;
	}
	if ($show['inlinemod'])
	{
		$foruminfo['bottomcolspan']++;
	}

	$show['threadslist'] = true;

	/////////////////////////////////
} // end forum can contain threads
else
{
	$show['threadslist'] = false;
}
/////////////////////////////////

if (!$vbulletin->GPC['prefixid'] AND $newthreads < 1 AND $unreadchildforums < 1)
{
	mark_forum_read($foruminfo, $vbulletin->userinfo['userid'], TIMENOW);
}

construct_forum_rules($foruminfo, $forumperms);

// Revisit this at a later date as it can be made to work with each SEO option
$show['displayoptions'] = (!$vbulletin->options['friendlyurl']);

$show['forumsearch'] = (!$show['search_engine'] AND $forumperms & $vbulletin->bf_ugp_forumpermissions['cansearch'] AND $vbulletin->options['enablesearches']);
$show['forumslist'] = $forumshown ? true : false;
$show['stickies'] = ($threadbits_sticky != '');
$show['optionbox'] = ($show['moderators'] OR $show['activeusers'] OR $show['displayoptions']);

$ad_location['forum_below_threadlist'] = vB_Template::create('ad_forum_below_threadlist')->render();

$foruminfo['parenttitle'] = $vbulletin->forumcache["$foruminfo[parentid]"]['title'];

($hook = vBulletinHook::fetch_hook('forumdisplay_complete')) ? eval($hook) : false;

$templater = vB_Template::create('FORUMDISPLAY');
	$templater->register_page_templates();
	$templater->register('activeusers', $activeusers);
	$templater->register('ad_location', $ad_location);
	$templater->register('announcebits', $announcebits);
	$templater->register('daysprune', $daysprune);
	$templater->register('daysprunesel', $daysprunesel);
	$templater->register('forumbits', $forumbits);
	$templater->register('forumid', $forumid);
	$templater->register('foruminfo', $foruminfo);
	$templater->register('forumjump', $forumjump);
	$templater->register('forumrules', $forumrules);
	$templater->register('gobutton', $gobutton);
	$templater->register('limitlower', $limitlower);
	$templater->register('limitupper', $limitupper);
	$templater->register('moderatorslist', $moderatorslist);
	$templater->register('navbar', $navbar);
	$templater->register('numberguest', $numberguest);
	$templater->register('numberregistered', $numberregistered);
	$templater->register('order', $order);
	$templater->register('pageinfo_flastpost', $pageinfo_flastpost);
	$templater->register('pageinfo_postusername', $pageinfo_postusername);
	$templater->register('pageinfo_replycount', $pageinfo_replycount);
	$templater->register('pageinfo_title', $pageinfo_title);
	$templater->register('pageinfo_views', $pageinfo_views);
	$templater->register('pageinfo_voteavg', $pageinfo_voteavg);
	$templater->register('pagenav', $pagenav);
	$templater->register('pagenumber', $pagenumber);
	$templater->register('perpage', $perpage);
	$templater->register('prefix_options', $prefix_options);
	$templater->register('prefix_selected', $prefix_selected);
	$templater->register('sort', $sort);
	$templater->register('sortarrow', $sortarrow);
	$templater->register('template_hook', $template_hook);
	$templater->register('threadadmin_imod_menu_thread', $threadadmin_imod_menu_thread);
	$templater->register('threadbits', $threadbits);
	$templater->register('threadbits_sticky', $threadbits_sticky);
	$templater->register('totalmods', $totalmods);
	$templater->register('totalonline', $totalonline);
	$templater->register('totalthreads', $totalthreads);
	$templater->register('url', $url);

print_output($templater->render());


/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 74000 $
|| ####################################################################
\*======================================================================*/
