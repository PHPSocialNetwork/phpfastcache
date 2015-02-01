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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'calendar');
define('CSRF_PROTECTION', true);
define('GET_EDIT_TEMPLATES', 'edit,add,manage');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'calendar',
	'holiday',
	'timezone',
	'posting',
	'user'
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
	'noavatarperms',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'calendarjump',
	'calendarjumpbit',
	'bbcode_code',
	'bbcode_html',
	'bbcode_php',
	'bbcode_quote',
	'bbcode_video',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'displayweek' => array(
		'calendar_yearly',
		'calendar_monthly',
		'calendar_monthly_week',
		'calendar_monthly_day',
		'calendar_monthly_day_other',
		'calendar_monthly_birthday',
		'calendar_monthly_event',
		'calendar_monthly_header',
		'calendar_smallmonth_header',
		'calendar_smallmonth_week',
		'calendar_smallmonth_day',
		'calendar_smallmonth_day_other',
		'calendar_weekly_day',
		'calendar_weekly_event',
		'calendar_weekly',
		'calendar_showbirthdays',
		'CALENDAR'
	),
	'displayyear' => array(
		'calendar_smallmonth_day_other',
		'calendar_smallmonth_header',
		'calendar_smallmonth_week',
		'calendar_monthly_event',
		'calendar_smallmonth_day',
		'calendar_monthly_week',
		'calendar_showbirthdays',
		'calendar_weekly_day',
		'calendar_yearly',
		'CALENDAR'
	),
	'getinfo' => array(
		'calendar_showevents',
		'calendar_showbirthdays',
		'calendar_showeventsbit',
		'calendar_showeventsbit_customfield',
		'CALENDAR'
	),
	'edit' => array(
		'calendar_edit',
		'calendar_edit_customfield',
		'calendar_edit_recurrence',
		'userfield_select_option'
	),
	'manage' => array(
		'calendar_edit',
		'calendar_edit_customfield',
		'calendar_edit_recurrence',
		'calendar_manage',
		'userfield_select_option'
	),
	'viewreminder' => array(
		'CALENDAR_REMINDER',
		'calendar_reminder_eventbit',
		'USERCP_SHELL',
		'forumdisplay_sortarrow',
		'usercp_nav_folderbit',
	),
	'addreminder' => array(
		'USERCP_SHELL',
		'calendar_reminder_choosetype',
		'usercp_nav_folderbit',
	),
);

$actiontemplates['getday'] =& $actiontemplates['getinfo'];
$actiontemplates['add'] =& $actiontemplates['edit'];
$actiontemplates['displaymonth'] =& $actiontemplates['displayweek'];
$actiontemplates['none'] =& $actiontemplates['displayweek'];

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_calendar.php');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$serveroffset = date('Z', TIMENOW) / 3600;

$idname = $vbphrase['event'];

$vbulletin->input->clean_array_gpc('r', array(
	'calendarid' => TYPE_UINT,
	'eventid'    => TYPE_UINT,
	'holidayid'  => TYPE_UINT,
	'week'       => TYPE_UINT,
	'month'      => TYPE_UINT,
	'year'       => TYPE_UINT,
	'sb'         => TYPE_UINT,
));

($hook = vBulletinHook::fetch_hook('calendar_start')) ? eval($hook) : false;

if ($vbulletin->GPC['week'])
{
	$_REQUEST['do'] = 'displayweek';
}

if (!$vbulletin->GPC['calendarid'])
{ // Determine the first calendar we have canview access to for the default calendar
	if ($vbulletin->GPC['eventid'])
	{ // get calendarid for this event
		if ($eventinfo = $db->query_first_slave("
			SELECT event.*, IF(dateline_to = 0, 1, 0) AS singleday,
			user.*, user.username, IF(user.displaygroupid = 0, user.usergroupid, user.displaygroupid) AS displaygroupid,
			user.adminoptions, user.usergroupid, user.membergroupids, user.infractiongroupids, IF(options & " . $vbulletin->bf_misc_useroptions['hasaccessmask'] . ", 1, 0) AS hasaccessmask
			" . ($vbulletin->userinfo['userid'] ? ", subscribeevent.eventid AS subscribed" : "") . "
			" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
			FROM " . TABLE_PREFIX . "event AS event
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = event.userid)
			" . ($vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "subscribeevent AS subscribeevent ON(subscribeevent.eventid = " . $vbulletin->GPC['eventid'] . " AND subscribeevent.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
			" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
			WHERE event.eventid = " . $vbulletin->GPC['eventid']))
		{
			$vbulletin->GPC['calendarid'] =& $eventinfo['calendarid'];
			if (!$vbulletin->GPC['calendarid'])
			{
				foreach ($calendarcache AS $index => $value)
				{
					if ($vbulletin->userinfo['calendarpermissions']["$index"] & $vbulletin->bf_ugp_calendarpermissions['canviewcalendar'])
					{
						$vbulletin->GPC['calendarid'] = $index;
						$addcalendarid = $index;
						break;
					}
				}
			}
			if (!($vbulletin->userinfo['calendarpermissions']["{$vbulletin->GPC['calendarid']}"] & $vbulletin->bf_ugp_calendarpermissions['canviewcalendar']))
			{
				print_no_permission();
			}
			if (!$eventinfo['visible'])
			{
				eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
			}

			$offset = $eventinfo['dst'] ? $vbulletin->userinfo['timezoneoffset'] : $vbulletin->userinfo['tzoffset'];

			require_once(DIR . '/includes/functions_user.php');

			$eventinfo = array_merge($eventinfo, convert_bits_to_array($eventinfo['options'], $vbulletin->bf_misc_useroptions));
			$eventinfo  = array_merge($eventinfo, convert_bits_to_array($eventinfo['adminoptions'], $vbulletin->bf_misc_adminoptions));
			cache_permissions($eventinfo, false);
			fetch_avatar_from_userinfo($eventinfo, true);

			$eventinfo['dateline_from_user'] = $eventinfo['dateline_from'] + $offset * 3600;
			$eventinfo['dateline_to_user'] = $eventinfo['dateline_to'] + $offset * 3600;
			fetch_musername($eventinfo);
		}
		else
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}
	}
	else
	{
		foreach ($calendarcache AS $index => $value)
		{
			if ($vbulletin->userinfo['calendarpermissions']["$index"] & $vbulletin->bf_ugp_calendarpermissions['canviewcalendar'])
			{
				$vbulletin->GPC['calendarid'] = $index;
				$addcalendarid = $index;
				break;
			}
		}
		if (!$vbulletin->GPC['calendarid'])
		{
			if (sizeof($calendarcache) == 0)
			{
				eval(standard_error(fetch_error('nocalendars')));
			}
			else
			{
				print_no_permission();
			}
		}
	}
}
else if (!($vbulletin->userinfo['calendarpermissions']["{$vbulletin->GPC['calendarid']}"] & $vbulletin->bf_ugp_calendarpermissions['canviewcalendar']))
{
	print_no_permission();
}
else if ($vbulletin->GPC['eventid'])
{
	if ($eventinfo = $db->query_first_slave("
		SELECT event.*, user.*, user.username, IF(dateline_to = 0, 1, 0) AS singleday,
		IF(user.displaygroupid = 0, user.usergroupid, user.displaygroupid) AS displaygroupid, infractiongroupid
		" . ($vbulletin->userinfo['userid'] ? ", subscribeevent.eventid AS subscribed" : "") . "
		" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
		FROM " . TABLE_PREFIX . "event AS event
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = event.userid)
		" . ($vbulletin->userinfo['userid'] ? "LEFT JOIN " . TABLE_PREFIX . "subscribeevent AS subscribeevent ON(subscribeevent.eventid = " . $vbulletin->GPC['eventid'] . " AND subscribeevent.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
		WHERE event.eventid = " . $vbulletin->GPC['eventid']))
	{
		if (!$eventinfo['visible'])
		{
			eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
		}

		$offset = $eventinfo['dst'] ? $vbulletin->userinfo['timezoneoffset'] : $vbulletin->userinfo['tzoffset'];
		$eventinfo['dateline_from_user'] = $eventinfo['dateline_from'] + $offset * 3600;
		$eventinfo['dateline_to_user'] = $eventinfo['dateline_to'] + $offset * 3600;
		fetch_musername($eventinfo);
	}
	else
	{
		eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
	}
}

if ($vbulletin->GPC['holidayid']) // $holidayid > 0 ?
{
	if ($eventinfo = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "holiday AS holiday
		WHERE holidayid = " . $vbulletin->GPC['holidayid'])
	)
	{
		$eventinfo['visible'] = 1;
		$eventinfo['holiday'] = 1;
		$eventinfo['title'] = $vbphrase['holiday' . $eventinfo['holidayid'] . '_title'];
		$eventinfo['event'] = $vbphrase['holiday' . $eventinfo['holidayid'] . '_desc'];
	}
	else
	{
		eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
	}
}

if ($eventinfo['eventid'] AND $eventinfo['userid'] != $vbulletin->userinfo['userid'] AND !($vbulletin->userinfo['calendarpermissions']["$eventinfo[calendarid]"] & $vbulletin->bf_ugp_calendarpermissions['canviewothersevent']))
{
	print_no_permission();
}

$calendarinfo = verify_id('calendar', $vbulletin->GPC['calendarid'], 1, 1);
$getoptions = convert_bits_to_array($calendarinfo['options'], $_CALENDAROPTIONS);
$calendarinfo = array_merge($calendarinfo, $getoptions);
$geteaster = convert_bits_to_array($calendarinfo['holidays'], $_CALENDARHOLIDAYS);
$calendarinfo = array_merge($calendarinfo, $geteaster);
$calendarid =& $calendarinfo['calendarid'];

$calview = htmlspecialchars_uni(fetch_bbarray_cookie('calendar', 'calview' . $calendarinfo['calendarid']));
$calmonth = intval(fetch_bbarray_cookie('calendar', 'calmonth'));
$calyear = intval(fetch_bbarray_cookie('calendar', 'calyear'));

$show['neweventlink'] = ($vbulletin->userinfo['calendarpermissions'][$calendarid] & $vbulletin->bf_ugp_calendarpermissions['canpostevent']) ? true : false;

if (empty($_REQUEST['do']))
{
	$defaultview = ((!empty($calendarinfo['weekly'])) ? 'displayweek' : ((!empty($calendarinfo['yearly'])) ? 'displayyear' : 'displaymonth'));
	$_REQUEST['do'] = !empty($calview) ? $calview : $defaultview;
}

if ($vbulletin->GPC['sb'])
{
	// Allow showbirthdays to be turned on if they are off -- mainly for the birthday link from the front page
	$calendarinfo['showbirthdays'] = true;
}

// chande the start of week for invalid values or guests (which are currently forced to 1, Sunday)
if ($vbulletin->userinfo['startofweek'] > 7 OR $vbulletin->userinfo['startofweek'] < 1 OR $vbulletin->userinfo['userid'] == 0)
{
	$vbulletin->userinfo['startofweek'] = $calendarinfo['startofweek'];
}

// Make first part of Calendar Nav Bar
$navbits = array('calendar.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['calendar']);

// Make second part of calendar nav... link if needed
if (in_array($_REQUEST['do'], array('displayweek', 'displaymonth', 'displayyear')))
{
	$navbits[''] = $calendarinfo['title'];
}
else
{
	$navbits['calendar.php?' . $vbulletin->session->vars['sessionurl'] . "c={$calendarinfo[calendarid]}"] = $calendarinfo['title'];
}

$today = getdate(TIMENOW - $vbulletin->options['hourdiff']);
$today['month'] = $vbphrase[strtolower($today['month'])];

if (!$vbulletin->GPC['year'])
{
	if (!empty($calyear))
	{
		$vbulletin->GPC['year'] = $calyear;
	}
	else
	{
		$vbulletin->GPC['year'] = $today['year'];
		set_bbarray_cookie('calendar', 'calyear', $today['year']);

	}
}
else
{
	if ($vbulletin->GPC['year'] < 1970 OR $vbulletin->GPC['year'] > 2037)
	{
		$vbulletin->GPC['year'] = $today['year'];
	}
	set_bbarray_cookie('calendar', 'calyear', $vbulletin->GPC['year']);
}

if (!$vbulletin->GPC['month'])
{
	if (!empty($calmonth))
	{
		$vbulletin->GPC['month'] = $calmonth;
	}
	else
	{
		$vbulletin->GPC['month'] = $today['mon'];
		set_bbarray_cookie('calendar', 'calmonth', $today['mon']);
	}
}
else
{
	if ($vbulletin->GPC['month'] < 1 OR $vbulletin->GPC['month'] > 12)
	{
		$vbulletin->GPC['month'] = $today['mon'];
	}
	set_bbarray_cookie('calendar', 'calmonth', $vbulletin->GPC['month']);
}

if ($calendarinfo['startyear'])
{
	if ($vbulletin->GPC['year'] < $calendarinfo['startyear'] OR $vbulletin->GPC['year'] > $calendarinfo['endyear'])
	{
		if ($calendarinfo['startyear'] > $today['year'])
		{
			$vbulletin->GPC['year'] = $calendarinfo['startyear'];
			$vbulletin->GPC['month'] = 1;
		}
		else
		{
			$vbulletin->GPC['year'] = $calendarinfo['endyear'];
			$vbulletin->GPC['month'] = 12;
		}
		set_bbarray_cookie('calendar', 'calyear', $vbulletin->GPC['year']);
		set_bbarray_cookie('calendar', 'calmonth', $vbulletin->GPC['month']);
	}
}

if ($vbulletin->GPC['month'] >= 1 AND $vbulletin->GPC['month'] <= 9)
{
	$doublemonth = "0{$vbulletin->GPC['month']}";
}
else
{
	$doublemonth = $vbulletin->GPC['month'];
}

// For calendarjump
$monthselected["{$vbulletin->GPC['month']}"] = 'selected="selected"';

($hook = vBulletinHook::fetch_hook('calendar_start2')) ? eval($hook) : false;

// ############################################################################
// ############################### MONTHLY VIEW ###############################

if ($_REQUEST['do'] == 'displaymonth')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'day' => TYPE_UINT
	));

	($hook = vBulletinHook::fetch_hook('calendar_displaymonth_start')) ? eval($hook) : false;

	$show['weeklyview'] = false;
	$show['monthlyview'] = true;
	$show['yearlyview'] = false;

	$usertoday = array(
		'firstday' => gmdate('w', gmmktime(0, 0, 0, $month, 1, $year)),
		'day' => $vbulletin->GPC['day'],
		'month' => $vbulletin->GPC['month'],
		'year' => $vbulletin->GPC['year'],
	);

	// Make Nav Bar #####################################################################
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	$usertodayprev = $usertoday;
	$usertodaynext = $usertoday;
	$eventrange = array();

	if ($vbulletin->GPC['month'] == 1)
	{
		$usertodayprev['month'] = 12;
		$usertodayprev['year'] = $vbulletin->GPC['year'] - 1;
		$usertodayprev['firstday'] = gmdate('w', gmmktime(0, 0, 0, 12, 1, $vbulletin->GPC['year'] - 1));
		$eventrange['frommonth'] = 12;
		$eventrange['fromyear'] = $vbulletin->GPC['year'] - 1;
	}
	else
	{
		$usertodayprev['month'] = $vbulletin->GPC['month'] - 1;
		$usertodayprev['year'] = $vbulletin->GPC['year'];
		$usertodayprev['firstday'] = gmdate('w', gmmktime(0, 0, 0, $vbulletin->GPC['month'] - 1, 1, $vbulletin->GPC['year']));
		$eventrange['frommonth'] = $vbulletin->GPC['month'] - 1;
		$eventrange['fromyear']= $vbulletin->GPC['year'];
	}

	if ($vbulletin->GPC['month'] == 12)
	{
		$usertodaynext['month'] = 1;
		$usertodaynext['year'] = $vbulletin->GPC['year'] + 1;
		$usertodaynext['firstday'] = gmdate('w', gmmktime(0, 0, 0, 1, 1, $vbulletin->GPC['year'] + 1));
		$eventrange['nextmonth'] = 1;
		$eventrange['nextyear'] = $vbulletin->GPC['year'] + 1;
	}
	else
	{
		$usertodaynext['month'] = $vbulletin->GPC['month'] + 1;
		$usertodaynext['year'] = $vbulletin->GPC['year'];
		$usertodaynext['firstday'] = gmdate('w', gmmktime(0, 0, 0, $vbulletin->GPC['month'] + 1, 1, $vbulletin->GPC['year']));
		$eventrange['nextmonth'] = $vbulletin->GPC['month'] + 1;
		$eventrange['nextyear'] = $vbulletin->GPC['year'];
	}

	$birthdaycache = cache_birthdays();
	$eventcache = cache_events($eventrange);

	if ($vbulletin->GPC['month'] == 1 AND $vbulletin->GPC['year'] == 1970)
	{
		$prevmonth = '';
	}
	else
	{
		$prevmonth = construct_calendar_output($today, $usertodayprev, $calendarinfo);
	}
	$calendarbits = construct_calendar_output($today, $usertoday, $calendarinfo, 1);
	if ($vbulletin->GPC['month'] == 12 AND $vbulletin->GPC['year'] == 2037)
	{
		$nextmonth = '';
	}
	else
	{
		$nextmonth = construct_calendar_output($today, $usertodaynext, $calendarinfo);
	}

	$monthname = $vbphrase[strtolower(gmdate('F', gmmktime(0, 0, 0, $vbulletin->GPC['month'], 1, $vbulletin->GPC['year'])))];

	$calendarjump = construct_calendar_jump($calendarinfo['calendarid'], $vbulletin->GPC['month'], $vbulletin->GPC['year']);

	if ($calview != 'displaymonth')
	{
		set_bbarray_cookie('calendar', 'calview' . $calendarinfo['calendarid'], 'displaymonth');
	}

	$yearbits = '';
	for ($gyear = $calendarinfo['startyear']; $gyear <= $calendarinfo['endyear']; $gyear++)
	{
		$yearbits .= render_option_template($gyear, $gyear, ($gyear == $vbulletin->GPC['year']) ? 'selected="selected"' : '');
	}

	($hook = vBulletinHook::fetch_hook('calendar_displaymonth_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('calendar_monthly');
		$templater->register('calendarbits', $calendarbits);
		$templater->register('calendarinfo', $calendarinfo);
		$templater->register('monthselected', $monthselected);
		$templater->register('yearbits', $yearbits);
		$templater->register('today', $today);
		$templater->register('year', $vbulletin->GPC['year']);
		$templater->register('monthname', $monthname);
		$templater->register('pmonth', $vbulletin->GPC['month'] == 1 ? 12 : $vbulletin->GPC['month'] - 1);
		$templater->register('nmonth', $vbulletin->GPC['month'] == 12 ? 1 : $vbulletin->GPC['month'] + 1);
		$templater->register('nyear', $vbulletin->GPC['month'] == 12 ? $vbulletin->GPC['year'] + 1 : $vbulletin->GPC['year']);
		$templater->register('pyear', $vbulletin->GPC['month'] == 1 ? $vbulletin->GPC['year'] - 1 : $vbulletin->GPC['year']);
	$HTML = $templater->render();
	$templater = vB_Template::create('CALENDAR');
		$templater->register_page_templates();
		$templater->register('calendarjump', $calendarjump);
		$templater->register('calendarid', $calendarid);
		$templater->register('calendarinfo', $calendarinfo);
		$templater->register('HTML', $HTML);
		$templater->register('navbar', $navbar);
		$templater->register('today', $today);
		$templater->register('year', $vbulletin->GPC['year']);
		$templater->register('nextmonth', $nextmonth);
		$templater->register('prevmonth', $prevmonth);
		$templater->register('mode', 'monthly');
		$templater->register('pagetitle', $calendarinfo['title']);
	print_output($templater->render());

}

// ############################################################################
// ############################### WEEKLY VIEW ################################
// ############################################################################

if ($_REQUEST['do'] == 'displayweek')
{
	($hook = vBulletinHook::fetch_hook('calendar_displayweek_start')) ? eval($hook) : false;

	$show['weeklyview'] = true;
	$show['monthlyview'] = false;
	$show['yearlyview'] = false;

	if ($vbulletin->GPC['week'])
	{
		if ($vbulletin->GPC['week'] < 259200)
		{
			$vbulletin->GPC['week'] = 259200;
		}
		else if ($vbulletin->GPC['week'] > 2145484800)
		{
			$vbulletin->GPC['week'] = 2145484800;
		}
		$prevweek = $vbulletin->GPC['week'] - 604800;
		$nextweek = $vbulletin->GPC['week'] + 604800;
	}
	else
	{
		$firstday = gmdate('w', gmmktime(0, 0, 0, 1, 1, $vbulletin->GPC['year'])) + 1;
		if ($vbulletin->userinfo['startofweek'] <= $firstday)
		{
			$offset = -1 * ($firstday - $vbulletin->userinfo['startofweek'] - 1);
		}
		else
		{ // $firstday < Start Of Week
			$offset = ($firstday + 6) * -1 + $vbulletin->userinfo['startofweek'];
		}
		if ($vbulletin->GPC['month'] == $today['mon'] AND $vbulletin->GPC['year'] == $today['year'])
		{
			$todaystamp = gmmktime(0, 0, 0, $vbulletin->GPC['month'], $today['mday'], $vbulletin->GPC['year']);
		}
		else
		{
			$todaystamp = gmmktime(0, 0, 0, $vbulletin->GPC['month'], 1, $vbulletin->GPC['year']);
		}

		while (true)
		{
			$prevweek = gmmktime(0, 0, 0, 1, $offset - 7, $vbulletin->GPC['year']);
			$vbulletin->GPC['week'] = gmmktime(0, 0, 0, 1, $offset, $vbulletin->GPC['year']);
			$nextweek = gmmktime(0, 0, 0, 1, $offset + 7, $vbulletin->GPC['year']);
			if ($nextweek > $todaystamp)
			{ // current week was last week so show that week!!
				break;
			}
			else
			{
				$offset += 7;
			}
		}

	}

	$day1 = gmdate('n-j-Y', $vbulletin->GPC['week']);
	$day1 = explode('-', $day1);
	$day7 = gmdate('n-j-Y', gmmktime(0, 0, 0, $day1[0], $day1[1] + 6, $day1[2]));
	$day7 = explode('-', $day7);

	$usertoday1 = array(
		'firstday' => gmdate('w', gmmktime(0, 0, 0, $day1[0], 1, $day1[2])),
		'month' => $day1[0],
		'year' => $day1[2]
	);
	$eventrange = array();
	$usertoday1 = array();
	$eventrange['frommonth'] = $day1[0];
	$eventrange['fromyear'] = $day1[2];
	$usertoday1['month'] = $day1[0];
	$usertoday1['year'] = $day1[2];
	$usertoday1['firstday'] = gmdate('w', gmmktime(0, 0, 0, $day1[0], 1, $day1[2]));
	if ($day1[0] != $day7[0])
	{
		$eventrange['nextmonth'] = $day7[0];
		$eventrange['nextyear'] = $day7[2];
		$usertoday2 = array();
		$usertoday2['month'] = $day7[0];
		$usertoday2['year'] = $day7[2];
		$usertoday2['firstday'] = gmdate('w', gmmktime(0, 0, 0, $day7[0], 1, $day7[2]));
	}
	else
	{
		$eventrange['nextmonth'] = $eventrange['frommonth'];
		$eventrange['nextyear'] = $eventrange['fromyear'];
	}

	$doublemonth1 = $day1[0] < 10 ? '0' . $day1[0] : $day1[0];
	$doublemonth2 = $day7[0] < 10 ? '0' . $day7[0] : $day7[0];
	$birthdaycache = cache_birthdays(1);
	$eventcache = cache_events($eventrange);

	$weekrange = array();
	$weekrange['start'] = gmmktime(0, 0, 0, $day1[0], $day1[1], $day1[2]);
	$weekrange['end'] = gmmktime(0, 0, 0, $day7[0], $day7[1], $day7[2]);
	$month1 = construct_calendar_output($today, $usertoday1, $calendarinfo, 0, $weekrange);
	if (is_array($usertoday2) AND $vbulletin->GPC['week'] != 2145484800)
	{
		$month2 = construct_calendar_output($today, $usertoday2, $calendarinfo, 0, $weekrange);
		$show['secondmonth'] = true;
	}

	$daystamp = $weekrange['start'];
	$eastercache = fetch_easter_array($day1['2']);

	$lastmonth = '';

	while ($daystamp <= $weekrange['end'])
	{
		$weekmonth = $vbphrase[strtolower(gmdate('F', $daystamp))];
		$weekdayname = $vbphrase[ strtolower(gmdate('l', $daystamp)) ];
		$weekday = gmdate('j', $daystamp);
		$weekyear = gmdate('Y', $daystamp);
		$month = gmdate('n', $daystamp);
		$monthnum = gmdate('m', $daystamp);
		if ($lastmonth != $weekmonth)
		{
			$show['monthname'] = true;
		}
		else
		{
			$show['monthname'] = false;
		}
		if (!$calendarinfo['showweekends'] AND (gmdate('w', $daystamp) == 6 OR gmdate('w', $daystamp) == 0))
		{
			// do nothing..
		}
		else
		{
			// Process birthdays / Events / templates
			unset($userbdays);
			$show['birthdays'] = false;
			if ($calendarinfo['showbirthdays'] AND is_array($birthdaycache["$month"]["$weekday"]))
			{
				unset($userday);
				unset($age);
				unset($comma);
				$bdaycount = 0;
				foreach ($birthdaycache["$month"]["$weekday"] AS $index => $userinfo)
				{
					$userday = explode('-', $userinfo['birthday']);
					$bdaycount++;
					if ($weekyear > $userday[2] AND $userday[2] != '0000' AND $userinfo['showbirthday'] == 2)
					{
						$age = '(' . ($weekyear - $userday[2]) . ')';
						$show['age'] = true;
					}
					else
					{
						unset($age);
						$show['age'] = false;
					}

					$templater = vB_Template::create('calendar_showbirthdays');
						$templater->register('age', $age);
						$templater->register('userinfo', $userinfo);
					$userbdays .= $templater->render();

					$show['birthdays'] = true;
				}
			}

			require_once(DIR . '/includes/functions_misc.php');

			unset($userevents);
			$show['events'] = false;
			if (is_array($eventcache))
			{
				$eventarray = cache_events_day($month, $weekday, $weekyear);

				foreach ($eventarray AS $index => $value)
				{
					$show['holiday'] = !empty($value['holidayid']) ? true : false;
					$eventid = $value['eventid'];
					$holidayid = $value['holidayid'];

					$allday = false;
					$eventtitle =  $value['title'];
					$year = gmdate('Y', $daystamp);
					$month = gmdate('n', $daystamp);
					$day = gmdate('j', $daystamp);
					if (!$value['singleday'])
					{
						$fromtime = vbgmdate($vbulletin->options['timeformat'], $value['dateline_from_user']);
						$totime = vbgmdate($vbulletin->options['timeformat'], $value['dateline_to_user']);
						$eventfirstday = gmmktime(0, 0, 0, gmdate('n', $value['dateline_from_user']), gmdate('j', $value['dateline_from_user']), gmdate('Y', $value['dateline_from_user']));
						$eventlastday = gmmktime(0, 0, 0, gmdate('n', $value['dateline_to_user']), gmdate('j', $value['dateline_to_user']), gmdate('Y', $value['dateline_to_user']));

						if (!$value['recurring'])
						{
							if ($eventfirstday == $daystamp)
							{
								if ($eventfirstday != $eventlastday)
								{
									if (gmdate('g:ia', $value['dateline_from_user']) == '12:00am')
									{
										$allday = true;
									}
									else
									{
										$totime = vbgmdate($vbulletin->options['timeformat'], 946771200);
									}
								}
							}
							else if ($eventlastday == $daystamp)
							{
								$fromtime = vbgmdate($vbulletin->options['timeformat'], 946771200);
							}
							else // A day in the middle of a multi-day event so event covers 24 hours
							{
								$allday = true; // Used in conditional
							}
						}
						$show['time'] = true;
					}
					else
					{
						$show['time'] = false;
					}
					$issubscribed = !empty($value['subscribed']) ? true : false;
					$show['events'] = true;

					($hook = vBulletinHook::fetch_hook('calendar_displayweek_event')) ? eval($hook) : false;

					$templater = vB_Template::create('calendar_weekly_event');
						$templater->register('allday', $allday);
						$templater->register('calendarid', $calendarid);
						$templater->register('day', $day);
						$templater->register('eventid', $eventid);
						$templater->register('eventtitle', $eventtitle);
						$templater->register('fromtime', $fromtime);
						$templater->register('issubscribed', $issubscribed);
						$templater->register('month', $month);
						$templater->register('totime', $totime);
						$templater->register('value', $value);
						$templater->register('weekday', $weekday);
						$templater->register('weekyear', $weekyear);
						$templater->register('year', $year);
					$userevents .= $templater->render();
				}
			}

			$month = gmdate('n', $daystamp);

			if (!empty($eastercache["$month-$weekday-$weekyear"]))
			{
				$show['events'] = true;
				$show['holiday'] = true;
				$eventtotal++;
				$eventtitle =& $eastercache["$month-$weekday-$weekyear"]['title'];
				$templater = vB_Template::create('calendar_weekly_event');
					$templater->register('allday', $allday);
					$templater->register('calendarid', $calendarid);
					$templater->register('day', $day);
					$templater->register('eventid', $eventid);
					$templater->register('eventtitle', $eventtitle);
					$templater->register('fromtime', $fromtime);
					$templater->register('issubscribed', $issubscribed);
					$templater->register('month', $month);
					$templater->register('totime', $totime);
					$templater->register('value', $value);
					$templater->register('weekday', $weekday);
					$templater->register('weekyear', $weekyear);
					$templater->register('year', $year);
				$userevents .= $templater->render();
				unset($holidayid);
				$show['holiday'] = false;
			}

			$show['highlighttoday'] = ("$today[year]-$today[mon]-$today[mday]" == "$weekyear-$month-$weekday");
			$templater = vB_Template::create('calendar_weekly_day');
				$templater->register('calendarid', $calendarid);
				$templater->register('calendarinfo', $calendarinfo);
				$templater->register('month', $month);
				$templater->register('nextweek', $nextweek);
				$templater->register('prevweek', $prevweek);
				$templater->register('userbdays', $userbdays);
				$templater->register('userevents', $userevents);
				$templater->register('weekday', $weekday);
				$templater->register('weekdayname', $weekdayname);
				$templater->register('weekmonth', $weekmonth);
				$templater->register('weekyear', $weekyear);
			$weekbits .= $templater->render();
			$lastmonth = $weekmonth;
		}
		$daystamp = gmmktime(0, 0, 0, $day1['0'], ++$day1['1'], $day1['2']);
	}

	// Make Nav Bar #####################################################################
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	if ($calview != 'displayweek')
	{
		set_bbarray_cookie('calendar', 'calview' . $calendarinfo['calendarid'], 'displayweek');
	}

	$calendarjump = construct_calendar_jump($calendarinfo['calendarid'], $vbulletin->GPC['month'], $vbulletin->GPC['year']);

	($hook = vBulletinHook::fetch_hook('calendar_displayweek_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('calendar_weekly');
		$templater->register('calendarinfo', $calendarinfo);
		$templater->register('weekbits', $weekbits);
	$HTML = $templater->render();
	$templater = vB_Template::create('CALENDAR');
		$templater->register_page_templates();
		$templater->register('calendarid', $calendarid);
		$templater->register('calendarinfo', $calendarinfo);
		$templater->register('HTML', $HTML);
		$templater->register('navbar', $navbar);
		$templater->register('spacer_close', $spacer_close);
		$templater->register('spacer_open', $spacer_open);
		$templater->register('today', $today);
		$templater->register('year', $year);
		$templater->register('calendarjump', $calendarjump);
		$templater->register('prevmonth', $month1);
		$templater->register('nextmonth', $month2);
		$templater->register('mode', 'weekly');
		$templater->register('pagetitle', $calendarinfo['title']);
	print_output($templater->render());

}

// ############################################################################
// ############################### YEARLY VIEW ################################
// ############################################################################

if ($_REQUEST['do'] == 'displayyear')
{
	($hook = vBulletinHook::fetch_hook('calendar_displayyear_start')) ? eval($hook) : false;

	$show['weeklyview'] = false;
	$show['monthlyview'] = false;
	$show['yearlyview'] = true;

	$eventrange = array('frommonth' => 1, 'fromyear' => $vbulletin->GPC['year'], 'nextmonth' => 12, 'nextyear' => $vbulletin->GPC['year']);
	$eventcache = cache_events($eventrange);

	$usertoday = array();
	$usertoday['year'] = $vbulletin->GPC['year'];

	for ($x = 1; $x <= 12; $x++)
	{
		$usertoday['month'] = $x;
		$usertoday['firstday'] = date('w', mktime(12, 0, 0, $x, 1, $vbulletin->GPC['year']));
		// build small calendar.
		$calname = 'month' . $x;
		$$calname = construct_calendar_output($today, $usertoday, $calendarinfo);
	}

	// Make Nav Bar #####################################################################
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	$calendarjump = construct_calendar_jump($calendarinfo['calendarid'], $vbulletin->GPC['month'], $vbulletin->GPC['year']);

	($hook = vBulletinHook::fetch_hook('calendar_displayyear_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('calendar_yearly');
		$templater->register('calendarinfo', $calendarinfo);
		$templater->register('month1', $month1);
		$templater->register('month2', $month2);
		$templater->register('month3', $month3);
		$templater->register('month4', $month4);
		$templater->register('month5', $month5);
		$templater->register('month6', $month6);
		$templater->register('month7', $month7);
		$templater->register('month8', $month8);
		$templater->register('month9', $month9);
		$templater->register('month10', $month10);
		$templater->register('month11', $month11);
		$templater->register('month12', $month12);
		$templater->register('year', $vbulletin->GPC['year']);
		$templater->register('nextyear', $vbulletin->GPC['year'] + 1);
		$templater->register('prevyear', $vbulletin->GPC['year'] - 1);
	$HTML = $templater->render();
	$templater = vB_Template::create('CALENDAR');
		$templater->register_page_templates();
		$templater->register('calendarid', $calendarid);
		$templater->register('calendarinfo', $calendarinfo);
		$templater->register('HTML', $HTML);
		$templater->register('navbar', $navbar);
		$templater->register('spacer_close', $spacer_close);
		$templater->register('spacer_open', $spacer_open);
		$templater->register('today', $today);
		$templater->register('year', $year);
		$templater->register('calendarjump', $calendarjump);
		$templater->register('mode', 'yearly');
		$templater->register('pagetitle', $calendarinfo['title']);
	print_output($templater->render());
}

// ############################################################################
// ############################### MANAGE EVENT ###############################
// ############################################################################

if ($_REQUEST['do'] == 'manage')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'what'          => TYPE_STR,
	));

	$vbulletin->input->clean_array_gpc('p', array(
		'newcalendarid' => TYPE_UINT,
		'dodelete'      => TYPE_BOOL,
		'day'           => TYPE_STR,
	));

	($hook = vBulletinHook::fetch_hook('calendar_manage_start')) ? eval($hook) : false;

	$getdate = explode('-', $vbulletin->GPC['day']);
	$year = intval($getdate[0]);
	$month = intval($getdate[1]);
	$day = intval($getdate[2]);

	$validdate = checkdate($month, $day, $year);

	if (!$eventinfo['eventid'])
	{
		eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
	}

	if ($vbulletin->GPC['what'] == 'dodelete' AND !$vbulletin->GPC['dodelete'])
	{
		// tried to delete but didn't click the checkbox... try again.
		$vbulletin->GPC['what'] = 'delete';
	}

	$print_output = false;

	switch ($vbulletin->GPC['what'])
	{
		// do delete
		case 'dodelete':
		{
			if (!can_moderate_calendar($calendarinfo['calendarid'], 'candeleteevents'))
			{
				print_no_permission();
			}
			else
			{
				// init event datamanager class
				$eventdata =& datamanager_init('Event', $vbulletin, ERRTYPE_STANDARD);
				$eventdata->set_existing($eventinfo);
				$eventdata->delete();

				$vbulletin->url = 'calendar.php?' . $vbulletin->session->vars['sessionurl'] . "c=$calendarinfo[calendarid]";
				print_standard_redirect('redirect_calendardeleteevent');
			}
		}
		break;

		// delete
		case 'delete':
		{
			if (!can_moderate_calendar($calendarinfo['calendarid'], 'candeleteevents'))
			{
				print_no_permission();
			}
			else
			{
				$print_output = true;
				$show['delete'] = true;
				if ($validdate)
				{
					$navbits['calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;c=$calendarinfo[calendarid]&amp;day=$year-$month-$day"] = vbgmdate($vbulletin->options['dateformat'], gmmktime(0, 0, 0, $month, $day, $year));
				}
				$navbits['calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;e=$eventinfo[eventid]"] = $eventinfo['title'];
				$navbits[''] = $vbphrase['delete_event'];
			}
		}
		break;

		// do move
		case 'domove':
		{
			if (!can_moderate_calendar($calendarinfo['calendarid'], 'canmoveevents'))
			{
				print_no_permission();
			}
			else
			{
				if (!($vbulletin->userinfo['calendarpermissions']["{$vbulletin->GPC['newcalendarid']}"] & $vbulletin->bf_ugp_calendarpermissions['canviewcalendar']))
				{
					print_no_permission();
				}

				// unsubscribe users who can't view the calendar that the event is now in
				$users = $db->query_read("
					SELECT user.userid, usergroupid, membergroupids, infractiongroupids, IF(options & " . $vbulletin->bf_misc_useroptions['hasaccessmask'] . ", 1, 0) AS hasaccessmask
					FROM " . TABLE_PREFIX . "subscribeevent AS subscribeevent
					INNER JOIN " . TABLE_PREFIX . "user AS user USING (userid)
					WHERE eventid = $eventinfo[eventid]
				");
				$deleteuser = '0';
				while ($thisuser = $db->fetch_array($users))
				{
					cache_permissions($thisuser);
					$userperms =& $thisuser['calendarpermissions']["{$vbulletin->GPC['newcalendarid']}"];
					if (($userperms & $vbulletin->bf_ugp_calendarpermissions['canviewcalendar']) AND ($eventinfo['userid'] == $thisuser['userid'] OR ($userperms & $vbulletin->bf_ugp_calendarpermissions['canviewothersevent'])))
					{
						// don't delete
						continue;
					}
					else

					{
						$deleteuser .=  ',' . $thisuser['userid'];
					}
				}

				if ($deleteuser)
				{
					$query = "DELETE FROM " . TABLE_PREFIX . "subscribeevent WHERE eventid = $eventinfo[eventid] AND userid IN ($deleteuser)";
					$db->query_write($query);
				}

				// init event datamanager class
				$eventdata =& datamanager_init('Event', $vbulletin, ERRTYPE_STANDARD);
				$eventdata->verify_datetime = false;
				$eventdata->set_existing($eventinfo);
				$eventdata->set('calendarid', $vbulletin->GPC['newcalendarid']);
				$eventdata->save();

				$vbulletin->url = 'calendar.php?' . $vbulletin->session->vars['sessionurl'] . 'c=' . $vbulletin->GPC['newcalendarid'];
				print_standard_redirect('redirect_calendarmoveevent');
			}
		}
		break;

		// move
		case 'move':
		{
			if (!can_moderate_calendar($calendarinfo['calendarid'], 'canmoveevents'))
			{
				print_no_permission();
			}
			else
			{
				$calendarbits = '';
				foreach ($calendarcache AS $lcalendarid => $title)
				{
					if (!($vbulletin->userinfo['calendarpermissions']["$lcalendarid"] & $vbulletin->bf_ugp_calendarpermissions['canviewcalendar']) OR ($lcalendarid == $eventinfo['calendarid']))
					{
						continue;
					}
					else
					{
						$optionvalue = $lcalendarid;
						$optiontitle = $title;
						$calendarbits .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
					}
				}
				if ($calendarbits == '')
				{
					eval(standard_error(fetch_error('calendarmove')));
				}
				else
				{
					$print_output = true;
					$show['delete'] = false;
					if ($validdate)
					{
						$navbits['calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;c=$calendarinfo[calendarid]&amp;day=$year-$month-$day"] = vbgmdate($vbulletin->options['dateformat'], gmmktime(0, 0, 0, $month, $day, $year));
					}
					$navbits['calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;e=$eventinfo[eventid]"] = $eventinfo['title'];
					$navbits[''] = $vbphrase['move_event'];
				}
			}
		}
		break;

		// edit - skip through to do=edit
		case 'edit':
		default:
		{
			$_POST['do'] = 'edit';
		}
		break;

	}

	($hook = vBulletinHook::fetch_hook('calendar_manage_complete')) ? eval($hook) : false;

	if ($print_output)
	{
		$navbits = construct_navbits($navbits);
		$navbar = render_navbar_template($navbits);
		$templater = vB_Template::create('calendar_manage');
			$templater->register_page_templates();
			$templater->register('calendarbits', $calendarbits);
			$templater->register('eventinfo', $eventinfo);
			$templater->register('navbar', $navbar);
			$templater->register('pagetitle', $pagetitle);
			$templater->register('_logincode', $_logincode);
		print_output($templater->render());
	}
}

// ############################################################################
// ############################### GET EVENTS #################################
// ############################################################################

if ($_REQUEST['do'] == 'getday' OR $_REQUEST['do'] == 'getinfo')
{

	$vbulletin->input->clean_array_gpc('r', array(
		'day' => TYPE_STR
	));

	($hook = vBulletinHook::fetch_hook('calendar_getday_start')) ? eval($hook) : false;

	$getdate = explode('-', $vbulletin->GPC['day']);
	$year = intval($getdate[0]);
	$month = intval($getdate[1]);
	$day = intval($getdate[2]);
	$eventarray = array();

	$validdate = checkdate($month, $day, $year);

	if ($eventinfo['eventid'])
	{
		$eventarray = array($eventinfo);
	}
	else if ($validdate)
	{
		$doublemonth = $month < 10 ? '0' . $month : $month;
		$doubleday = $day < 10 ? '0' . $day : $day;

		$todaystamp = gmmktime(0, 0, 0, $month, $day, $year);

		// set date range for events to cache.
		$eventrange = array('frommonth' => $month, 'fromyear' => $year, 'nextmonth' => $month, 'nextyear' => $year);

		// cache events for this month only.
		$eventcache = cache_events($eventrange);

		if ($calendarinfo['showbirthdays'])
		{  // Load the birthdays for today

			foreach($vbulletin->usergroupcache AS $usergroupid => $usergroup)
			{
				if ($usergroup['genericoptions'] & $vbulletin->bf_ugp_genericoptions['showbirthday'])
				{
					$ids .= ",$usergroupid";
				}
			}

			$comma = '';
			$birthday = $db->query_read_slave("
				SELECT birthday, username, userid, showbirthday
				FROM " . TABLE_PREFIX . "user
				WHERE birthday LIKE '$doublemonth-$doubleday-%' AND
					usergroupid IN (0$ids) AND
					showbirthday IN (2,3)
			");

			while ($birthdays = $db->fetch_array($birthday))
			{
				$userinfo = $birthdays;
				$userday = explode('-', $userinfo['birthday']);
				if ($year > $userday[2] AND $userday[2] != '0000' AND $userinfo['showbirthday'] == 2)
				{
					$age = '(' . ($year - $userday[2]) . ')';
					$show['age'] = true;
				}
				else
				{
					unset($age);
					$show['age'] = false;
				}

				$templater = vB_Template::create('calendar_showbirthdays');
					$templater->register('age', $age);
					$templater->register('userinfo', $userinfo);
				$userbdays .= $templater->render();

				$show['birthdays'] = true;
			}
		}

		$eventarray = cache_events_day($month, $day, $year);
	}

	if (!empty($eventarray))
	{
		$customcalfields = $db->query_read_slave("
			SELECT calendarcustomfieldid, title, options, allowentry, description
			FROM " . TABLE_PREFIX . "calendarcustomfield AS calendarcustomfield
			WHERE calendarid = $calendarinfo[calendarid]
			ORDER BY calendarcustomfieldid
		");
		$customfieldssql = array();
		while ($custom = $db->fetch_array($customcalfields))
		{
			$customfieldssql[] = $custom;
		}
	}

	$show['canmoveevent'] = can_moderate_calendar($calendarinfo['calendarid'], 'canmoveevents');
	$show['candeleteevent'] = can_moderate_calendar($calendarinfo['calendarid'], 'candeleteevents');

	require_once(DIR . '/includes/functions_misc.php'); // mainly for fetch_timezone

	require_once(DIR . '/includes/functions_user.php'); // to fetch user avatar
	foreach ($eventarray AS $index => $eventinfo)
	{
		$eventinfo = fetch_event_date_time($eventinfo);
		$holidayid = $eventinfo['holidayid'];
		$customfields = '';

		fetch_musername($eventinfo);

		if (!$holidayid)
		{
			unset($holidayid);
			$eventfields = unserialize($eventinfo['customfields']);

			$bgclass = 'alt2';
			$show['customfields'] = false;

			foreach ($customfieldssql AS $index => $value)
			{
				$description = $value['description'];
				$value['options'] = unserialize($value['options']);
				exec_switch_bg();
				$selectbits = '';
				$customoption = '';
				$customtitle = $value['title'];
				if (is_array($value['options']))
				{
					foreach ($value['options'] AS $key => $val)
					{
						if ($val == $eventfields["{$value['calendarcustomfieldid']}"])
						{
							$customoption = $val;
							break;
						}
					}
				}

				// Skip this value if a user entered entry exists but no longer allowed
				if (!$value['allowentry'] AND $customoption == '')
				{
					continue;
				}

				require_once(DIR . '/includes/functions_newpost.php');
				$customoption = parse_calendar_bbcode(convert_url_to_bbcode(unhtmlspecialchars($eventfields["{$value['calendarcustomfieldid']}"])));

				$show['customoption'] = ($customoption == '') ? false : true;
				if ($show['customoption'])
				{
					$show['customfields'] = true;
				}
				$templater = vB_Template::create('calendar_showeventsbit_customfield');
					$templater->register('customoption', $customoption);
					$templater->register('customtitle', $customtitle);
				$customfields .= $templater->render();
			}

			$show['holiday'] = false;
			// check for calendar moderator here.
			$show['caneditevent'] = true;
			if (!can_moderate_calendar($calendarinfo['calendarid'], 'caneditevents'))
			{
				if ($eventinfo['userid'] != $vbulletin->userinfo['userid'])
				{
					$show['caneditevent'] = false;
				}
				else if (!($vbulletin->userinfo['calendarpermissions']["{$calendarinfo['calendarid']}"] & $vbulletin->bf_ugp_calendarpermissions['caneditevent']))
				{
					$show['caneditevent'] = false;
				}
			}
			$show['subscribed'] = !empty($eventinfo['subscribed']) ? true : false;
			if ($eventinfo['subscribed'])
			{
				$show['subscribelink'] = true;
			}
			else if ($vbulletin->userinfo['userid'] AND $eventinfo['dateline_to'] AND TIMENOW <= $eventinfo['dateline_to'])
			{
				$show['subscribelink'] = true;
			}
			else if ($vbulletin->userinfo['userid'] AND $eventinfo['singleday'] AND TIMENOW <= $eventinfo['dateline_from'])
			{
				$show['subscribelink'] = true;
			}
			else
			{
				$show['subscribelink'] = false;
			}
		}
		else
		{
			$show['holiday'] = true;
			$show['caneditevent'] = false;
			$show['subscribelink'] = false;
		}

		exec_switch_bg();
		if (!$eventinfo['singleday'] AND gmdate('w', $eventinfo['dateline_from_user']) != gmdate('w', $eventinfo['dateline_from'] + ($eventinfo['utc'] * 3600)))
		{
			$show['adjustedday'] = true;
			$eventinfo['timezone'] = str_replace('&nbsp;', ' ', $vbphrase[fetch_timezone($eventinfo['utc'])]);
		}
		else
		{
			$show['adjustedday'] = false;
		}

		$show['ignoredst'] = ($eventinfo['dst'] AND !$eventinfo['singleday']) ? true : false;
		$show['postedby'] = !empty($eventinfo['userid']) ? true : false;
		$show['singleday'] = !empty($eventinfo['singleday']) ? true : false;
		if (($show['candeleteevent'] OR $show['canmoveevent'] OR $show['caneditevent']) AND !$show['holiday'])
		{
			$show['eventoptions'] = true;
		}

		//we already have the avatar info, no need to refetch.
		fetch_avatar_from_userinfo($eventinfo);

		// prepare the member action drop-down menu
		$memberaction_dropdown = construct_memberaction_dropdown($eventinfo);

//		$avatar = fetch_avatar_url($eventinfo['userid']);
//		$eventinfo['avatarurl'] = $avatar[0];
		($hook = vBulletinHook::fetch_hook('calendar_getday_event')) ? eval($hook) : false;

		$templater = vB_Template::create('calendar_showeventsbit');
			$templater->register('calendarinfo', $calendarinfo);
			$templater->register('customfields', $customfields);
			$templater->register('date1', $date1);
			$templater->register('date2', $date2);
			$templater->register('eventdate', $eventdate);
			$templater->register('eventinfo', $eventinfo);
			$templater->register('gobutton', $gobutton);
			$templater->register('memberaction_dropdown', $memberaction_dropdown);
			$templater->register('recurcriteria', $recurcriteria);
			$templater->register('spacer_close', $spacer_close);
			$templater->register('spacer_open', $spacer_open);
			$templater->register('time1', $time1);
			$templater->register('time2', $time2);
		$caldaybits .= $templater->render();
	}
	unset($date2, $recurcriteria, $customfields);
	$show['subscribelink'] = false;
	$show['adjustedday'] = false;
	$show['ignoredst'] = true;
	$show['singleday'] = false;
	$show['holiday'] = false;
	$show['eventoptions'] = false;
	$show['postedby'] = false;
	$show['recuroption'] = false;

	if (!$vbulletin->GPC['eventid'])
	{
		$eventinfo = array();
		$eastercache = fetch_easter_array($year);

		if (!empty($eastercache["$month-$day-$year"]))
		{
			$eventinfo['title'] =& $eastercache["$month-$day-$year"]['title'];
			$eventinfo['event'] =& $eastercache["$month-$day-$year"]['event'];
			$show['holiday'] = true;
		}

		if ($eventinfo['title'] != '')
		{
			require_once(DIR . '/includes/functions_misc.php');
			$eventdate = vbgmdate($vbulletin->options['dateformat'], gmmktime(0, 0, 0, $month, $day, $year));
			$titlecolor = 'alt2';
			$bgclass = 'alt1';

			fetch_avatar_from_userinfo($eventinfo);
//			$avatar = fetch_avatar_url($eventinfo['userid']);
//			$eventinfo['avatarurl'] = $avatar[0];
			($hook = vBulletinHook::fetch_hook('calendar_getday_event')) ? eval($hook) : false;

			$templater = vB_Template::create('calendar_showeventsbit');
			$templater->register('calendarinfo', $calendarinfo);
			$templater->register('customfields', $customfields);
			$templater->register('date1', $date1);
			$templater->register('date2', $date2);
			$templater->register('eventdate', $eventdate);
			$templater->register('eventinfo', $eventinfo);
			$templater->register('gobutton', $gobutton);
			$templater->register('recurcriteria', $recurcriteria);
			$templater->register('spacer_close', $spacer_close);
			$templater->register('spacer_open', $spacer_open);
			$templater->register('time1', $time1);
			$templater->register('time2', $time2);
		$caldaybits .= $templater->render();
		}
	}

	if (empty($eventarray) AND !$show['birthdays'] AND !$show['holiday'])
	{
		eval(standard_error(fetch_error('noevents')));
	}

	$monthselected = array($month => 'selected="selected"');
	$calendarjump = construct_calendar_jump($calendarinfo['calendarid'], $month, $year);

	// Make Rest of Nav Bar
	require_once(DIR . '/includes/functions_misc.php');
	if ($vbulletin->GPC['eventid'])
	{
		if ($validdate)
		{
			$navbits['calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;c=$calendarinfo[calendarid]&amp;day=$year-$month-$day"] = vbgmdate($vbulletin->options['dateformat'], gmmktime(0, 0, 0, $month, $day, $year));
		}
		$navbits[''] = $eventinfo['title'];
	}
	else
	{
		$navbits[''] = vbgmdate($vbulletin->options['dateformat'], gmmktime(0, 0, 0, $month, $day, $year));
	}

	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('calendar_getday_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('calendar_showevents');
		$templater->register_page_templates();
		$templater->register('caldaybits', $caldaybits);
		$templater->register('userbdays', $userbdays);
		$templater->register('pagetitle', $pagetitle);
	$HTML = $templater->render();
	$templater = vB_Template::create('CALENDAR');
		$templater->register_page_templates();
		$templater->register('calendarjump', $calendarjump);
		$templater->register('calendarid', $calendarid);
		$templater->register('calendarinfo', $calendarinfo);
		$templater->register('HTML', $HTML);
		$templater->register('navbar', $navbar);
		$templater->register('today', $today);
		$templater->register('year', $vbulletin->GPC['year']);
		$templater->register('nextmonth', $nextmonth);
		$templater->register('prevmonth', $prevmonth);
		$templater->register('mode', 'dayly');
		$templater->register('pagetitle', $pagetitle);
	print_output($templater->render());
}

// ############################################################################
// ################################# EDIT EVENT ###############################
// ############################################################################

if ($_POST['do'] == 'edit')
{
	($hook = vBulletinHook::fetch_hook('calendar_edit_start')) ? eval($hook) : false;

	if (!$eventinfo['eventid'])
	{
		eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
	}

	// check for calendar moderator here.
	if (!can_moderate_calendar($calendarinfo['calendarid'], 'caneditevents'))
	{
		if ($eventinfo['userid'] != $vbulletin->userinfo['userid'])
		{
			print_no_permission();
		}
		else if (!($vbulletin->userinfo['calendarpermissions']["{$calendarinfo['calendarid']}"] & $vbulletin->bf_ugp_calendarpermissions['caneditevent']))
		{
			print_no_permission();
		}

	}

	$checked = array('disablesmilies' => ($eventinfo['allowsmilies'] == 1 ? '' : 'checked="checked"'));

	if ($calendarinfo['allowsmilies'])
	{
		$templater = vB_Template::create('newpost_disablesmiliesoption');
			$templater->register('checked', $checked);
		$disablesmiliesoption = $templater->render();
	}

	$calrules['allowbbcode'] = $calendarinfo['allowbbcode'];
	$calrules['allowimages'] = $calendarinfo['allowimgcode'];
	$calrules['allowvideos'] = $calendarinfo['allowvideocode'];
	$calrules['allowhtml'] = $calendarinfo['allowhtml'];
	$calrules['allowsmilies'] = $calendarinfo['allowsmilies'];

	$bbcodeon = !empty($calrules['allowbbcode']) ? $vbphrase['on'] : $vbphrase['off'];
	$imgcodeon = !empty($calrules['allowimages']) ? $vbphrase['on'] : $vbphrase['off'];
	$videocodeon = !empty($calrules['allowvideos']) ? $vbphrase['on'] : $vbphrase['off'];
	$htmlcodeon = !empty($calrules['allowhtml']) ? $vbphrase['on'] : $vbphrase['off'];
	$smilieson = !empty($calrules['allowsmilies']) ? $vbphrase['on'] : $vbphrase['off'];

	// only show posting code allowances in forum rules template
	$show['codeonly'] = true;

	require_once(DIR . '/includes/functions_bigthree.php');
	construct_forum_rules($calrules, $permissions);

	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	$title = $eventinfo['title'];
	$message = htmlspecialchars_uni($eventinfo['event']);

	$fromdate = explode('-', gmdate('n-j-Y', $eventinfo['dateline_from'] + $eventinfo['utc'] * 3600));
	$fromtime = gmdate('g_i_A_H', $eventinfo['dateline_from'] + $eventinfo['utc'] * 3600);

	$todate = explode('-', gmdate('n-j-Y', $eventinfo['dateline_to'] + $eventinfo['utc'] * 3600));
	$totime = gmdate('g_i_A_H', $eventinfo['dateline_to'] + $eventinfo['utc'] * 3600);

	$fromtime = explode('_', $fromtime);
	$totime = explode('_', $totime);

	if (strpos($vbulletin->options['timeformat'], 'H') !== false)
	{
		$show['24hour'] = true;
	}
	else
	{
		$show['24hour'] = false;
	}

	$user_from_time = fetch_time_options($fromtime, $show['24hour']);
	$user_to_time = fetch_time_options($totime, $show['24hour']);

	if ($eventinfo['utc'] < 0)
	{
		$timezonesel['n' . (-$eventinfo['utc'] * 10)] = 'selected="selected"';
	}
	else
	{
		$index = $eventinfo['utc'] * 10;
		$timezonesel["$index"] = 'selected="selected"';
	}

	// select correct timezone and build timezone options
	require_once(DIR . '/includes/functions_misc.php');
	$timezoneoptions = '';
	foreach (fetch_timezone() AS $optionvalue => $timezonephrase)
	{
		$optiontitle = $vbphrase["$timezonephrase"];
		$optionselected = ($optionvalue == $eventinfo['utc'] ? 'selected="selected"' : '');
		$timezoneoptions .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
	}

	if (($pos = strpos($vbulletin->options['timeformat'], 'H')) !== false)
	{
		$show['24hour'] = true;
		$fromtime[3] = intval($fromtime[3]);
		$totime[3] = intval($totime[3]);
		$from_hourselected["$fromtime[3]"] = 'selected="selected"';
		$from_minuteselected["$fromtime[1]"] = 'selected="selected"';
		$to_hourselected["$totime[3]"] = 'selected="selected"';
		$to_minuteselected["$totime[1]"] = 'selected="selected"';
	}
	else
	{
		$show['24hour'] = false;
		$from_hourselected["$fromtime[0]"] = 'selected="selected"';
		$from_minuteselected["$fromtime[1]"] = 'selected="selected"';
		$from_ampmselected["$fromtime[2]"] = 'selected="selected"';

		$to_hourselected["$totime[0]"] = 'selected="selected"';
		$to_minuteselected["$totime[1]"] = 'selected="selected"';
		$to_ampmselected["$totime[2]"] = 'selected="selected"';
	}

	$from_day = $fromdate[1];
	$from_monthselected["$fromdate[0]"] = 'selected="selected"';
	$from_yearselected["$fromdate[2]"] = 'selected="selected"';

	$to_day = $todate[1];
	$to_monthselected["$todate[0]"] = 'selected="selected"';
	$to_yearselected["$todate[2]"] = 'selected="selected"';

	$from_yearbits = '';
	$to_yearbits = '';
	for ($gyear = $calendarinfo['startyear']; $gyear <= $calendarinfo['endyear']; $gyear++)
	{
		$from_yearbits .= "\t\t<option value=\"$gyear\" $from_yearselected[$gyear]>$gyear</option>";
		$to_yearbits .= "\t\t<option value=\"$gyear\" $to_yearselected[$gyear]>$gyear</option>";
	}

	// Do custom fields

	$eventcustomfields = unserialize($eventinfo['customfields']);

	$customfields_required = '';
	$show['custom_required'] = false;
	$customfields_optional = '';
	$show['custom_optional'] = false;

	$customcalfields = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "calendarcustomfield
		WHERE calendarid = $calendarinfo[calendarid]
		ORDER BY calendarcustomfieldid
	");
	$bgclass = 'alt1';
	while ($custom = $db->fetch_array($customcalfields))
	{
		$custom['options'] = unserialize($custom['options']);
		$customfieldname = "userfield[f{$custom['calendarcustomfieldid']}]";
		$customfieldname_opt = "userfield[o{$custom['calendarcustomfieldid']}]";
		exec_switch_bg();
		$selectbits = '';
		$found = false;
		if (is_array($custom['options']))
		{
			$optioncount = sizeof($custom['options']);
			foreach ($custom['options'] AS $key => $val)
			{
				if ($eventcustomfields["{$custom['calendarcustomfieldid']}"] == $val)
				{
					$selected = 'selected="selected"';
					$found = true;
				}
				else

				{
					$selected = '';
				}
				$templater = vB_Template::create('userfield_select_option');
					$templater->register('key', $key);
					$templater->register('selected', $selected);
					$templater->register('val', $val);
				$selectbits .= $templater->render();
			}
			$show['customoptions'] = true;
		}
		else
		{
			$optioncount = 0;
			$show['customoptions'] = false;
		}
		if ($custom['allowentry'] AND !$found)
		{
			$custom['optional'] = $eventcustomfields["{$custom['calendarcustomfieldid']}"];
			$custom['length'] = $custom['length'] ? $custom['length'] : 255;
		}
		$show['customdescription'] = !empty($custom['description']) ? true : false;
		$show['customoptionalinput'] = !empty($custom['allowentry']) ? true : false;

		if ($custom['required'])
		{
			$show['custom_required'] = true;
			$templater = vB_Template::create('calendar_edit_customfield');
				$templater->register('custom', $custom);
				$templater->register('customfieldname', $customfieldname);
				$templater->register('customfieldname_opt', $customfieldname_opt);
				$templater->register('selectbits', $selectbits);
				$templater->register('selected', $selected);
			$customfields_required .= $templater->render();
		}
		else
		{
			$show['custom_optional'] = true;
			$templater = vB_Template::create('calendar_edit_customfield');
				$templater->register('custom', $custom);
				$templater->register('customfieldname', $customfieldname);
				$templater->register('customfieldname_opt', $customfieldname_opt);
				$templater->register('selectbits', $selectbits);
				$templater->register('selected', $selected);
			$customfields_optional .= $templater->render();
		}
	}

	$recur = $eventinfo['recurring'];
	$dailybox = 1;
	$weeklybox = 2;
	$monthlybox1 = 2;
	$monthlybox2 = 2;
	$monthlycombo1 = 1;
	$yearlycombo2 = 1;
	$patterncheck = array($eventinfo['recurring'] => 'checked="checked"');
	$eventtypecheck = array();

	if ($eventinfo['recurring'] == 1)
	{
		$dailybox = $eventinfo['recuroption'];
		$thistype = 'daily';
		$eventtypecheck[1] = 'checked="checked"';
	}
	else if ($eventinfo['recurring'] == 2)
	{
		// Nothing to do for this one..
		$thistype = 'daily';
		$eventtypecheck[1] = 'checked="checked"';
	}
	else if ($eventinfo['recurring'] == 3)
	{
		$monthbit = explode('|', $eventinfo['recuroption']);
		$weeklybox = $monthbit[0];
		if ($monthbit[1] & 1)
		{
			$sunboxchecked = 'checked="checked"';
		}
		if ($monthbit[1] & 2)
		{
			$monboxchecked = 'checked="checked"';
		}
		if ($monthbit[1] & 4)
		{
			$tueboxchecked = 'checked="checked"';
		}
		if ($monthbit[1] & 8)
		{
			$wedboxchecked = 'checked="checked"';
		}
		if ($monthbit[1] & 16)
		{
			$thuboxchecked = 'checked="checked"';
		}
		if ($monthbit[1] & 32)
		{
			$friboxchecked = 'checked="checked"';
		}
		if ($monthbit[1] & 64)
		{
			$satboxchecked = 'checked="checked"';
		}
		$thistype = 'weekly';
		$eventtypecheck[2] = 'checked="checked"';
	}
	else if ($eventinfo['recurring'] == 4)
	{
		$monthbit = explode('|', $eventinfo['recuroption']);
		$monthlycombo1 = $monthbit[0];

		$monthlybox1 = $monthbit[1];
		$thistype = 'monthly';
		$eventtypecheck[3] = 'checked="checked"';
	}
	else if ($eventinfo['recurring'] == 5)
	{
		$monthbit = explode('|', $eventinfo['recuroption']);
		$monthlycombo2["$monthbit[0]"] = 'selected="selected"';
		$monthlycombo3["$monthbit[1]"] = 'selected="selected"';
		$monthlybox2 = $monthbit[2];
		$thistype = 'monthly';
		$eventtypecheck[3] = 'checked="checked"';
	}
	else if ($eventinfo['recurring'] == 6)
	{
		$monthbit = explode('|', $eventinfo['recuroption']);
		$yearlycombo1["$monthbit[0]"] = 'selected="selected"';
		$yearlycombo2 = $monthbit[1];
		$thistype = 'yearly';
		$eventtypecheck[4] = 'checked="checked"';
	}
	else if ($eventinfo['recurring'] == 7)
	{
		$monthbit = explode('|', $eventinfo['recuroption']);
		$yearlycombo3["$monthbit[0]"] = 'selected="selected"';
		$yearlycombo4["$monthbit[1]"] = 'selected="selected"';
		$yearlycombo5["$monthbit[2]"] = 'selected="selected"';
		$thistype = 'yearly';
		$eventtypecheck[4] = 'checked="checked"';
	}
	$templater = vB_Template::create('calendar_edit_recurrence');
		$templater->register('dailybox', $dailybox);
		$templater->register('eventtypecheck', $eventtypecheck);
		$templater->register('friboxchecked', $friboxchecked);
		$templater->register('monboxchecked', $monboxchecked);
		$templater->register('monthlybox1', $monthlybox1);
		$templater->register('monthlybox2', $monthlybox2);
		$templater->register('monthlycombo1', $monthlycombo1);
		$templater->register('monthlycombo2', $monthlycombo2);
		$templater->register('monthlycombo3', $monthlycombo3);
		$templater->register('patterncheck', $patterncheck);
		$templater->register('satboxchecked', $satboxchecked);
		$templater->register('sunboxchecked', $sunboxchecked);
		$templater->register('recurtype', $thistype);
		$templater->register('thuboxchecked', $thuboxchecked);
		$templater->register('tueboxchecked', $tueboxchecked);
		$templater->register('wedboxchecked', $wedboxchecked);
		$templater->register('weeklybox', $weeklybox);
		$templater->register('yearlycombo1', $yearlycombo1);
		$templater->register('yearlycombo2', $yearlycombo2);
		$templater->register('yearlycombo3', $yearlycombo3);
		$templater->register('yearlycombo4', $yearlycombo4);
		$templater->register('yearlycombo5', $yearlycombo5);
	$recurrence = $templater->render();
	if ($recur)
	{
		$type = 'recur';
	}
	else if ($eventinfo['dateline_to'] == 0)
	{
		$type = 'single';
	}
	else
	{
		$type = 'range';
	}

	$show['todate'] = ($type == 'single' ? false : true);
	$show['deleteoption'] = true;
	$dstchecked = $eventinfo['dst'] ? 'checked="checked"' : '';

	$class = array();
	exec_switch_bg();
	$class['event'] = $bgclass;
	exec_switch_bg();
	$class['options'] = $bgclass;

	$navbits[''] = $eventinfo['title'];
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	require_once(DIR . '/includes/functions_editor.php');
	$editorid = construct_edit_toolbar(
		htmlspecialchars_uni($eventinfo['event']),
		0,
		'calendar',
		$calendarinfo['allowsmilies'],
		true,
		false,
		'fe',
		'',
		array(),
		'content',
		'vBForum_Calendar',
		$eventinfo['eventid'],
		0,
		false,
		true,
		'titlefield'
	);

	$show['parseurl'] = $calendarinfo['allowbbcode'];
	$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));
	$show['additional_options'] = ($show['misc_options'] OR $show['custom_optional']);

	($hook = vBulletinHook::fetch_hook('calendar_edit_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('calendar_edit');
		$templater->register_page_templates();
		$templater->register('calendarinfo', $calendarinfo);
		$templater->register('customfields_optional', $customfields_optional);
		$templater->register('customfields_required', $customfields_required);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('dstchecked', $dstchecked);
		$templater->register('editorid', $editorid);
		$templater->register('eventinfo', $eventinfo);
		$templater->register('forumrules', $forumrules);
		$templater->register('from_day', $from_day);
		$templater->register('from_monthselected', $from_monthselected);
		$templater->register('from_yearbits', $from_yearbits);
		$templater->register('messagearea', $messagearea);
		$templater->register('navbar', $navbar);
		$templater->register('onload', $onload);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('recurrence', $recurrence);
		$templater->register('timezoneoptions', $timezoneoptions);
		$templater->register('title', $title);
		$templater->register('to_day', $to_day);
		$templater->register('to_monthselected', $to_monthselected);
		$templater->register('to_yearbits', $to_yearbits);
		$templater->register('type', $type);
		$templater->register('usernamecode', $usernamecode);
		$templater->register('fromtime', $user_from_time);
		$templater->register('totime', $user_to_time);
	print_output($templater->render());
}

// ############################################################################
// ################################# ADD EVENT ################################
// ############################################################################

if ($_REQUEST['do'] == 'add')
{
	if (!($vbulletin->userinfo['calendarpermissions']["{$calendarinfo['calendarid']}"] & $vbulletin->bf_ugp_calendarpermissions['canpostevent']))
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'day'  => TYPE_STR,
		'type' => TYPE_NOHTML,
	));

	($hook = vBulletinHook::fetch_hook('calendar_add_start')) ? eval($hook) : false;

	$vbulletin->GPC['eventid'] = 0;

	if ($calendarinfo['allowsmilies'] == 1)
	{
		$templater = vB_Template::create('newpost_disablesmiliesoption');
			$templater->register('checked', $checked);
		$disablesmiliesoption = $templater->render();
	}

	$customfields_required = '';
	$show['custom_required'] = false;
	$customfields_optional = '';
	$show['custom_optional'] = false;

	$customcalfields = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "calendarcustomfield
		WHERE calendarid = $calendarinfo[calendarid]
		ORDER BY calendarcustomfieldid
	");
	$bgclass = 'alt1';
	while ($custom = $db->fetch_array($customcalfields))
	{
		$custom['options'] = unserialize($custom['options']);
		$customfieldname = "userfield[f{$custom['calendarcustomfieldid']}]";
		$customfieldname_opt = "userfield[o{$custom['calendarcustomfieldid']}]";
		exec_switch_bg();
		$selectbits = '';
		if (is_array($custom['options']))
		{
			$optioncount = sizeof($custom['options']);
			foreach ($custom['options'] AS $key => $val)
			{
				$templater = vB_Template::create('userfield_select_option');
					$templater->register('key', $key);
					$templater->register('selected', $selected);
					$templater->register('val', $val);
				$selectbits .= $templater->render();
			}
		}
		else
		{
			$optioncount = 0;
		}
		$show['customdescription'] = !empty($custom['description']) ? true : false;
		$show['customoptions'] = is_array($custom['options']) ? true : false;
		if ($custom['allowentry'])
		{
			$show['customoptionalinput'] = true;
			$custom['length'] = $custom['length'] ? $custom['length'] : 255;
		}
		else
		{
			$show['customoptionalinput'] = false;
		}

		if ($custom['required'])
		{
			$show['custom_required'] = true;
			$templater = vB_Template::create('calendar_edit_customfield');
				$templater->register('custom', $custom);
				$templater->register('customfieldname', $customfieldname);
				$templater->register('customfieldname_opt', $customfieldname_opt);
				$templater->register('selectbits', $selectbits);
				$templater->register('selected', $selected);
			$customfields_required .= $templater->render();
		}
		else
		{
			$show['custom_optional'] = true;
			$templater = vB_Template::create('calendar_edit_customfield');
				$templater->register('custom', $custom);
				$templater->register('customfieldname', $customfieldname);
				$templater->register('customfieldname_opt', $customfieldname_opt);
				$templater->register('selectbits', $selectbits);
				$templater->register('selected', $selected);
			$customfields_optional .= $templater->render();
		}
	}

	$calrules['allowbbcode'] = $calendarinfo['allowbbcode'];
	$calrules['allowimages'] = $calendarinfo['allowimgcode'];
	$calrules['allowvideos'] = $calendarinfo['allowvideocode'];
	$calrules['allowhtml'] = $calendarinfo['allowhtml'];
	$calrules['allowsmilies'] = $calendarinfo['allowsmilies'];

	$bbcodeon = !empty($calrules['allowbbcode']) ? $vbphrase['on'] : $vbphrase['off'];
	$imgcodeon = !empty($calrules['allowimages']) ? $vbphrase['on'] : $vbphrase['off'];
	$videocodeon = !empty($calrules['allowvideos']) ? $vbphrase['on'] : $vbphrase['off'];
	$htmlcodeon = !empty($calrules['allowhtml']) ? $vbphrase['on'] : $vbphrase['off'];
	$smilieson = !empty($calrules['allowsmilies']) ? $vbphrase['on'] : $vbphrase['off'];

	// only show posting code allowances in forum rules template
	$show['codeonly'] = true;

	require_once(DIR . '/includes/functions_bigthree.php');
	construct_forum_rules($calrules, $permissions);

	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	if (($pos = strpos($vbulletin->options['timeformat'], 'H')) !== false)
	{
		$show['24hour'] = true;
	}

	$user_from_time = fetch_time_options('', $show['24hour']);
	$user_to_time = fetch_time_options('', $show['24hour']);

	$passedday = false;
	// did a day value get passed in?
	if ($vbulletin->GPC['day'] != '')
	{
		$daybits = explode('-', $vbulletin->GPC['day']);
		foreach ($daybits AS $key => $val)
		{
			$daybits["$key"] = intval($val);
		}
		if (checkdate($daybits[1], $daybits[2], $daybits[0]))
		{
			$to_day = $from_day = $daybits[2];
			$to_monthselected["$daybits[1]"] = $from_monthselected["$daybits[1]"] = 'selected="selected"';
			$to_yearselected["$daybits[0]"] = $from_yearselected["$daybits[0]"] = 'selected="selected"';
			$passedday = true;
		}
	}

	if (!$passedday)
	{
		$from_day = $today['mday'];
		$from_monthselected["$today[mon]"] = 'selected="selected"';
		$from_yearselected["$today[year]"] = 'selected="selected"';

		$to_day = $today['mday'];
		$to_monthselected["$today[mon]"] = 'selected="selected"';
		$to_yearselected["$today[year]"] = 'selected="selected"';
	}

	$from_yearbits = '';
	$to_yearbits = '';
	for ($gyear = $calendarinfo['startyear']; $gyear <= $calendarinfo['endyear']; $gyear++)
	{
		$from_yearbits .= "\t\t<option value=\"$gyear\" $from_yearselected[$gyear]>$gyear</option>";
		$to_yearbits .= "\t\t<option value=\"$gyear\" $to_yearselected[$gyear]>$gyear</option>";
	}

	// select correct timezone and build timezone options
	require_once(DIR . '/includes/functions_misc.php'); // mainly for fetch_timezone
	$timezoneoptions = '';
	foreach (fetch_timezone() AS $optionvalue => $timezonephrase)
	{
		$optiontitle = $vbphrase["$timezonephrase"];
		$optionselected = ($optionvalue == $vbulletin->userinfo['timezoneoffset'] ? 'selected="selected"' : '');
		$timezoneoptions .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
	}

	$patterncheck = array(1 => 'checked="checked"');
	$eventtypecheck = array(1 => 'checked="checked"');
	$dailybox = '1';
	$weeklybox = '1';
	$monthlybox1 = '2';
	$monthlybox2 = '1';
	$monthlycombo1 = 1;
	$monthlycombo2 = array(1 => 'selected="selected"');
	$monthlycombo3 = array(1 => 'selected="selected"');
	$yearlycombo1 = array(1 => 'selected="selected"');
	$yearlycombo2 = 1;
	$yearlycombo3 = array(1 => 'selected="selected"');
	$yearlycombo4 = array(1 => 'selected="selected"');
	$yearlycombo5 = array(1 => 'selected="selected"');
	$thistype = 'daily';
	$templater = vB_Template::create('calendar_edit_recurrence');
		$templater->register('dailybox', $dailybox);
		$templater->register('eventtypecheck', $eventtypecheck);
		$templater->register('friboxchecked', $friboxchecked);
		$templater->register('monboxchecked', $monboxchecked);
		$templater->register('monthlybox1', $monthlybox1);
		$templater->register('monthlybox2', $monthlybox2);
		$templater->register('monthlycombo1', $monthlycombo1);
		$templater->register('monthlycombo2', $monthlycombo2);
		$templater->register('monthlycombo3', $monthlycombo3);
		$templater->register('patterncheck', $patterncheck);
		$templater->register('satboxchecked', $satboxchecked);
		$templater->register('sunboxchecked', $sunboxchecked);
		$templater->register('recurtype', $thistype);
		$templater->register('thuboxchecked', $thuboxchecked);
		$templater->register('tueboxchecked', $tueboxchecked);
		$templater->register('wedboxchecked', $wedboxchecked);
		$templater->register('weeklybox', $weeklybox);
		$templater->register('yearlycombo1', $yearlycombo1);
		$templater->register('yearlycombo2', $yearlycombo2);
		$templater->register('yearlycombo3', $yearlycombo3);
		$templater->register('yearlycombo4', $yearlycombo4);
		$templater->register('yearlycombo5', $yearlycombo5);
	$recurrence .= $templater->render();

	$show['deleteoption'] = false;

	// Make Rest of Nav Bar
	$navbits[''] = $vbphrase['add_new_event'];

	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	require_once(DIR . '/includes/functions_editor.php');
	$editorid = construct_edit_toolbar(
		'',
		0,
		'calendar',
		$calendarinfo['allowsmilies'],
		true,
		false,
		'fe',
		'',
		array(),
		'content',
		'vBForum_Calendar',
		0,
		0,
		false,
		true,
		'titlefield'
	);

	$dstchecked = 'checked="checked"';
	$show['parseurl'] = $calendarinfo['allowbbcode'];
	$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));
	$show['additional_options'] = ($show['misc_options'] OR $show['custom_optional']);
	($hook = vBulletinHook::fetch_hook('calendar_add_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('calendar_edit');
		$templater->register_page_templates();
		$templater->register('calendarinfo', $calendarinfo);
		$templater->register('customfields_optional', $customfields_optional);
		$templater->register('customfields_required', $customfields_required);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('dstchecked', $dstchecked);
		$templater->register('editorid', $editorid);
		$templater->register('eventinfo', $eventinfo);
		$templater->register('forumrules', $forumrules);
		$templater->register('from_day', $from_day);
		$templater->register('from_monthselected', $from_monthselected);
		$templater->register('from_yearbits', $from_yearbits);
		$templater->register('messagearea', $messagearea);
		$templater->register('navbar', $navbar);
		$templater->register('onload', $onload);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('recurrence', $recurrence);
		$templater->register('timezoneoptions', $timezoneoptions);
		$templater->register('title', $title);
		$templater->register('to_day', $to_day);
		$templater->register('to_monthselected', $to_monthselected);
		$templater->register('to_yearbits', $to_yearbits);
		$templater->register('type', $vbulletin->GPC['type'] ? $vbulletin->GPC['type'] : 'single');
		$templater->register('usernamecode', $usernamecode);
		$templater->register('fromtime', $user_from_time);
		$templater->register('totime', $user_to_time);
	print_output($templater->render());
}

// ############################################################################
// ############################### UPDATE EVENT ###############################
// ############################################################################

if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'title'          => TYPE_STR,
		'message'        => TYPE_STR,
		'parseurl'       => TYPE_BOOL,
		'disablesmilies' => TYPE_BOOL,
		'deletepost'     => TYPE_BOOL,
		'deletebutton'   => TYPE_STR,
		'wysiwyg'	       => TYPE_BOOL,
		'timezoneoffset' => TYPE_ARRAY_NUM,
		'userfield'      => TYPE_ARRAY_STR,
		'dst'            => TYPE_ARRAY_UINT,
		'fromdate'       => TYPE_ARRAY_ARRAY,
		'todate'         => TYPE_ARRAY_ARRAY,
		'totime'         => TYPE_ARRAY_NOHTML,
		'fromtime'       => TYPE_ARRAY_NOHTML,
		'recur'          => TYPE_ARRAY_UINT,
		'type'           => TYPE_NOHTML,
		'loggedinuser'   => TYPE_INT
	));

	$type = $vbulletin->GPC['type'];

	$fromtime = $vbulletin->GPC['fromtime']["$type"];
	$totime = $vbulletin->GPC['totime']["$type"];
	$fromdate = $vbulletin->input->clean($vbulletin->GPC['fromdate']["$type"], TYPE_ARRAY_UINT);
	$todate = $vbulletin->input->clean($vbulletin->GPC['todate']["$type"], TYPE_ARRAY_UINT);
	$timezoneoffset = $vbulletin->GPC['timezoneoffset']["$type"];
	$dst = $vbulletin->GPC['dst']["$type"];

	if ($vbulletin->GPC['loggedinuser'] != 0 AND $vbulletin->userinfo['userid'] == 0)
	{
		// User was logged in when writing post but isn't now. If we got this
		// far, guest posts are allowed, but they didn't enter a username so
		// they'll get an error. Force them to log back in.
		standard_error(fetch_error('session_timed_out_login'), '', false, 'STANDARD_ERROR_LOGIN');
	}

	($hook = vBulletinHook::fetch_hook('calendar_update_start')) ? eval($hook) : false;

	if ($eventinfo['eventid'])
	{
		if ($vbulletin->GPC['deletebutton'])
		{
			if (!$vbulletin->GPC['deletepost'])
			{
				eval(standard_error(fetch_error('please_confirm_delete')));
			}

			if (!can_moderate_calendar($calendarinfo['calendarid'], 'candeleteevents'))
			{
				if ($eventinfo['userid'] != $vbulletin->userinfo['userid'])
				{
					print_no_permission();
				}
				else if (!($vbulletin->userinfo['calendarpermissions']["{$calendarinfo['calendarid']}"] & $vbulletin->bf_ugp_calendarpermissions['candeleteevent']))
				{
					print_no_permission();
				}
			}

			// init event datamanager class
			$eventdata =& datamanager_init('Event', $vbulletin, ERRTYPE_STANDARD);
			$eventdata->set_existing($eventinfo);
			$eventdata->delete();

			$vbulletin->url = 'calendar.php?' . $vbulletin->session->vars['sessionurl_q'] . "c=$calendarinfo[calendarid]";
			print_standard_redirect('redirect_calendardeleteevent');
		}
		else
		{
			if (!can_moderate_calendar($calendarinfo['calendarid'], 'caneditevents'))
			{
				if ($eventinfo['userid'] != $vbulletin->userinfo['userid'])
				{
					print_no_permission();
				}
				else if (!($vbulletin->userinfo['calendarpermissions']["{$calendarinfo['calendarid']}"] & $vbulletin->bf_ugp_calendarpermissions['caneditevent']))
				{
					print_no_permission();
				}
			}
		}
	}
	else
	{
		if (!($vbulletin->userinfo['calendarpermissions']["{$calendarinfo['calendarid']}"] & $vbulletin->bf_ugp_calendarpermissions['canpostevent']))
		{
			print_no_permission();
		}
	}

	// unwysiwygify the incoming data
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$message = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $calendarinfo['allowhtml']);
	}
	else
	{
		$message = $vbulletin->GPC['message'];
	}

	// init event datamanager class
	$eventdata =& datamanager_init('Event', $vbulletin, ERRTYPE_STANDARD);

	($hook = vBulletinHook::fetch_hook('calendar_update_process')) ? eval($hook) : false;

	$eventdata->set_info('parseurl', ($vbulletin->GPC['parseurl'] AND $calendarinfo['allowbbcode']));
	$eventdata->setr_info('fromtime', $fromtime);
	$eventdata->setr_info('totime', $totime);
	$eventdata->setr_info('fromdate', $fromdate);
	$eventdata->setr_info('todate', $todate);
	$eventdata->setr_info('type', $vbulletin->GPC['type']);
	$eventdata->setr_info('recur', $vbulletin->GPC['recur']);

	$eventdata->set('title', $vbulletin->GPC['title']);
	$eventdata->set('event', $message);
	$eventdata->set('allowsmilies', empty($vbulletin->GPC['disablesmilies']) ? true : false);
	$eventdata->set('utc', $timezoneoffset);
	$eventdata->set('recurring', $type == 'recur' ? $vbulletin->GPC['recur']['pattern'] : 0);
	$eventdata->set('calendarid', $calendarinfo['calendarid']);
	$eventdata->set('dst', $dst);
	$eventdata->set_userfields($vbulletin->GPC['userfield']);


	if (!$eventinfo['eventid'])
	{ // No Eventid == Insert Event

		if (can_moderate_calendar($calendarinfo['calendarid'], 'canmoderateevents'))
		{
			$eventdata->set('visible', 1);
			$visible = 1;
		}
		else if (!($vbulletin->userinfo['calendarpermissions']["{$calendarinfo['calendarid']}"] & $vbulletin->bf_ugp_calendarpermissions['isnotmoderated']) OR $calendarinfo['moderatenew'])
		{
			$eventdata->set('visible', 0);
			$visible = 0;
		}
		else
		{
			$eventdata->set('visible', 1);
			$visible = 1;
		}

		$eventdata->set('userid', $vbulletin->userinfo['userid']);
		$eventdata->set('calendarid', $calendarinfo['calendarid']);

		$eventid = $eventdata->save();
		clear_autosave_text('vBForum_Calendar', 0, 0, $vbulletin->userinfo['userid']);

		if ($calendarinfo['neweventemail'])
		{
			$calemails = unserialize($calendarinfo['neweventemail']);
			$calendarinfo['title'] = unhtmlspecialchars($calendarinfo['title']);
			$title =& $vbulletin->GPC['title'];
			$vbulletin->userinfo['username'] = unhtmlspecialchars($vbulletin->userinfo['username']); //for emails

			require_once(DIR . '/includes/class_bbcode_alt.php');
			$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());
			$plaintext_parser->set_parsing_language(0); // email addresses don't have a language ID
			$eventmessage = $plaintext_parser->parse($message, 'calendar');

			foreach ($calemails AS $index => $toemail)
			{
				if (trim($toemail))
				{
					eval(fetch_email_phrases('newevent', 0));
					vbmail($toemail, $subject, $message, true);
				}
			}
		}

		($hook = vBulletinHook::fetch_hook('calendar_update_complete')) ? eval($hook) : false;

		if ($visible)
		{
			$vbulletin->url = 'calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;e=$eventid&amp;day=" . $eventdata->info['occurdate'];
			print_standard_redirect('redirect_calendaraddevent');
		}
		else
		{
			$vbulletin->url = 'calendar.php?' . $vbulletin->session->vars['sessionurl'] . "c=$calendarinfo[calendarid]";
			print_standard_redirect('redirect_calendarmoderated', true, true);
		}
	}
	else
	{ // Update event

		$eventdata->set_existing($eventinfo);
		$eventdata->save();

		clear_autosave_text('vBForum_Calendar', $eventinfo['eventid'], 0, $vbulletin->userinfo['userid']);

		($hook = vBulletinHook::fetch_hook('calendar_update_complete')) ? eval($hook) : false;

		$vbulletin->url = 'calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;e=$eventinfo[eventid]&amp;day=" . $eventdata->info['occurdate'];
		print_standard_redirect('redirect_calendarupdateevent');
	}

}

// ############################################################################
// ######################## DELETE EVENT REMINDER #############################
// ############################################################################

if ($_REQUEST['do'] == 'deletereminder')
{

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	if (!$eventinfo['eventid'])
	{
		eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
	}

	($hook = vBulletinHook::fetch_hook('calendar_deletereminder')) ? eval($hook) : false;

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "subscribeevent
		WHERE userid = " . $vbulletin->userinfo['userid'] . "
			AND eventid = $eventinfo[eventid]
	");

	$vbulletin->url = 'calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;e=$eventinfo[eventid]";
	print_standard_redirect('redirect_subsremove_event', true, true);

}

// ############################################################################
// ######################## DELETE EVENT REMINDERS ############################
// ############################################################################

if ($_POST['do'] == 'dostuff')
{

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'deletebox'	 => TYPE_ARRAY_BOOL,
		'what'       => TYPE_STR,
		'calendarid' => TYPE_UINT,
	));

	if (empty($vbulletin->GPC['deletebox']))
	{
		eval(standard_error(fetch_error('eventsnoselected')));
	}

	$vbulletin->url = 'calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=viewreminder"
		. (!empty($vbulletin->GPC['calendarid']) ? '&amp;c=' . $vbulletin->GPC['calendarid'] : '');

	$ids = '';
	foreach ($vbulletin->GPC['deletebox'] AS $id => $value)
	{
		if ($id = intval($id))
		{
			$ids .= ",$id";
		}
	}

	($hook = vBulletinHook::fetch_hook('calendar_dostuff')) ? eval($hook) : false;

	if (!empty($ids))
	{
		if ($vbulletin->GPC['what'] == 'delete')
		{
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "subscribeevent
				WHERE subscribeeventid IN (-1$ids)
					AND userid = " . $vbulletin->userinfo['userid']
			);
			print_standard_redirect('redirect_reminderdeleted');
		}
		else
		{
			if (!empty($reminders["{$vbulletin->GPC['what']}"]))
			{ # make sure the supplied integer is a valid one
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "subscribeevent
					SET reminder = " . intval($vbulletin->GPC['what']) . "
					WHERE subscribeeventid IN (-1$ids)
						AND userid = " . $vbulletin->userinfo['userid']
				);
			}
			print_standard_redirect('redirect_reminderupdated');
		}
	}
}

// ############################################################################
// ######################## MANAGE EVENT REMINDERS ############################
// ############################################################################

if ($_REQUEST['do'] == 'viewreminder')
{
	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'sortfield'  => TYPE_NOHTML,
		'sortorder'  => TYPE_NOHTML,
	));

	require_once(DIR . '/includes/functions_user.php');

	($hook = vBulletinHook::fetch_hook('calendar_viewreminder_start')) ? eval($hook) : false;

	// These $_REQUEST values will get used in the sort template so they are assigned to normal variables
	$perpage =&  $vbulletin->GPC['perpage'];
	$pagenumber =& $vbulletin->GPC['pagenumber'];
	$sortfield =& $vbulletin->GPC['sortfield'];
	$sortorder =& $vbulletin->GPC['sortorder'];
	$calendarid =& $vbulletin->GPC['calendarid'];

	// look at sorting options:
	if ($sortorder != 'asc')
	{
		$sortorder = 'desc';
		$orderphrase = 'descending';
	}
	else
	{
		$orderphrase = 'ascending';
	}

	switch ($sortfield)
	{
		case 'username':
			$sqlsortfield = 'user.username';
			$sortphrase = 'event_poster';
			break;
		case 'reminder':
			$sqlsortfield = 'subscribeevent.reminder';
			$sortphrase = 'reminder';
			break;
		case 'title':
			$sqlsortfield = 'event.' . $sortfield;
			$sortphrase = 'event';
			break;
		default:
			$sqlsortfield = 'event.dateline_from';
			$sortfield = 'fromdate';
			$sortphrase = 'date';
	}

	$eventcount = $db->query_first_slave("
		SELECT COUNT(*) AS events
		FROM " . TABLE_PREFIX . "subscribeevent AS subscribeevent
		LEFT JOIN " . TABLE_PREFIX . "event AS event ON (subscribeevent.eventid = event.eventid)
		WHERE subscribeevent.userid = " . $vbulletin->userinfo['userid'] . "
			AND event.visible = 1
	");

	$totalevents = intval($eventcount['events']); // really stupid mysql bug

	sanitize_pageresults($totalevents, $pagenumber, $perpage, 200, $vbulletin->options['maxthreads']);

	$limitlower = ($pagenumber - 1) * $perpage + 1;
	$limitupper = ($pagenumber) * $perpage;

	if ($limitupper > $totalevents)
	{
		$limitupper = $totalevents;
		if ($limitlower > $totalevents)
		{
			$limitlower = $totalevents - $perpage;
		}
	}
	if ($limitlower <= 0)
	{
		$limitlower = 1;
	}

	$getevents = $db->query_read_slave("
		SELECT event.*, IF(dateline_to = 0, 1, 0) AS singleday, user.username, user.options, user.adminoptions, user.usergroupid, user.membergroupids, user.infractiongroupids, IF(options & " . $vbulletin->bf_misc_useroptions['hasaccessmask'] . ", 1, 0) AS hasaccessmask,
			subscribeevent.reminder, subscribeevent.subscribeeventid
			" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight, customavatar.width_thumb AS avwidth_thumb, customavatar.height_thumb AS avheight_thumb, filedata_thumb, NOT ISNULL(customavatar.userid) AS hascustom" : "") . "
		FROM " . TABLE_PREFIX . "subscribeevent AS subscribeevent
		LEFT JOIN " . TABLE_PREFIX . "event AS event ON (subscribeevent.eventid = event.eventid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (event.userid = user.userid)
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
		WHERE
			subscribeevent.userid = " . $vbulletin->userinfo['userid'] . "
				AND
			event.visible = 1
		ORDER BY
			$sqlsortfield $sortorder
		LIMIT " . ($limitlower - 1) . ", $perpage
	");

	$itemcount = ($pagenumber - 1) * $perpage;
	$first = $itemcount + 1;

	if ($db->num_rows($getevents))
	{
		$show['haveevents'] = true;

		while ($event = $db->fetch_array($getevents))
		{
			if (empty($reminders["{$event['reminder']}"]))
			{
				$event['reminder'] = 3600;
			}
			$event['reminder'] = $vbphrase[$reminders[$event['reminder']]];
			$offset = $event['dst'] ? $vbulletin->userinfo['timezoneoffset'] : $vbulletin->userinfo['tzoffset'];


			$event = array_merge($event, convert_bits_to_array($event['options'], $vbulletin->bf_misc_useroptions));
			$event  = array_merge($event, convert_bits_to_array($event['adminoptions'], $vbulletin->bf_misc_adminoptions));
			cache_permissions($event, false);
			fetch_avatar_from_userinfo($event, true);

			$event['dateline_from_user'] = $event['dateline_from'] + $offset * 3600;
			$event['dateline_to_user'] = $event['dateline_to'] + $offset * 3600;
			$event['preview'] = htmlspecialchars_uni(strip_bbcode(fetch_trimmed_title(strip_quotes($event['event']), 300), false, true));
			$event = fetch_event_date_time($event);
			$event['calendar'] = $calendarcache["$event[calendarid]"];
			$show['singleday'] = !empty($event['singleday']) ? true : false;

			($hook = vBulletinHook::fetch_hook('calendar_viewreminder_event')) ? eval($hook) : false;

			$oppositesort = ($sortorder == 'asc' ? 'desc' : 'asc');
			$templater = vB_Template::create('calendar_reminder_eventbit');
				$templater->register('date1', $date1);
				$templater->register('date2', $date2);
				$templater->register('daterange', $daterange);
				$templater->register('event', $event);
				$templater->register('eventdate', $eventdate);
				$templater->register('recurcriteria', $recurcriteria);
				$templater->register('time1', $time1);
				$templater->register('time2', $time2);
			$eventbits .= $templater->render();
			$itemcount++;
		}

		$last = $itemcount;

		$db->free_result($getevents);
		$sorturl = 'calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=viewreminder&amp;pp=$perpage";
		$pagenav = construct_page_nav($pagenumber, $perpage, $totalevents, $sorturl . "&amp;sort=$sortfield" . (!empty($sortorder) ? "&amp;order=$sortorder" : ""));
	}
	else
	{
		$show['haveevents'] = false;
	}

	array_pop($navbits);
	$navbits[''] = $vbphrase['event_reminders'];
	$navbits = construct_navbits($navbits);

	// build the cp nav
	require_once(DIR . '/includes/functions_user.php');
	construct_usercp_nav('event_reminders');

	($hook = vBulletinHook::fetch_hook('calendar_viewreminder_complete')) ? eval($hook) : false;

	$navbar = render_navbar_template($navbits);
	$templater = vB_Template::create('CALENDAR_REMINDER');
		$templater->register('calendarid', $calendarid);
		$templater->register('eventbits', $eventbits);
		$templater->register('gobutton', $gobutton);
		$templater->register('pagenav', $pagenav);
		$templater->register('sorturl', $sorturl);
		$templater->register('first', $first);
		$templater->register('last', $last);
		$templater->register('totalevents', $totalevents);
		$templater->register('sortfield', $sortfield);
		$templater->register('perpage', $perpage);
		$templater->register('oppositesort', $oppositesort);
		$templater->register('sortphrase', $sortphrase);
		$templater->register('orderphrase', $orderphrase);
		$templater->register('sortorder', $sortorder);
	$HTML = $templater->render();
	$templater = vB_Template::create('USERCP_SHELL');
		$templater->register_page_templates();
		$templater->register('cpnav', $cpnav);
		$templater->register('HTML', $HTML);
		$templater->register('navbar', $navbar);
		$templater->register('navclass', $navclass);
		$templater->register('onload', $onload);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('template_hook', $template_hook);
		$templater->register('includecss', 'reminders.css');
		$templater->register('includeiecss', 'reminders-ie.css');
	print_output($templater->render());

}

// ############################################################################
// ######################### ADD EVENT REMINDER ###############################
// ############################################################################

if ($_POST['do'] == 'doaddreminder')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'reminder' => TYPE_UINT
	));

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	if (!$eventinfo['eventid'])
	{
		eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
	}

	($hook = vBulletinHook::fetch_hook('calendar_doaddreminder')) ? eval($hook) : false;

	/*insert query*/
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "subscribeevent (userid, eventid, reminder)
		VALUES (" . $vbulletin->userinfo['userid'] . ", $eventinfo[eventid], " . (!empty($reminders["{$vbulletin->GPC['reminder']}"]) ? $vbulletin->GPC['reminder'] : 3600) . ")
	");

	$vbulletin->url = 'calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;e=$eventinfo[eventid]";
	print_standard_redirect('redirect_subsadd_event');
}


// ############################### start add subscription ###############################
if ($_REQUEST['do'] == 'addreminder')
{
	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	if (!$eventinfo['eventid'])
	{
		eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));
	}

	$navbits['calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=viewreminder"] = $vbphrase['event_reminders'];

	$navbits[''] = $vbphrase['add_reminder'];
	$navbits = construct_navbits($navbits);

	require_once(DIR . '/includes/functions_user.php');
	construct_usercp_nav('event_reminders');
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('calendar_addreminder')) ? eval($hook) : false;

	$url =& $vbulletin->url;
	$templater = vB_Template::create('calendar_reminder_choosetype');
		$templater->register('eventinfo', $eventinfo);
		$templater->register('url', $url);
	$HTML = $templater->render();
	$templater = vB_Template::create('USERCP_SHELL');
		$templater->register_page_templates();
		$templater->register('cpnav', $cpnav);
		$templater->register('HTML', $HTML);
		$templater->register('navbar', $navbar);
		$templater->register('navclass', $navclass);
		$templater->register('onload', $onload);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('template_hook', $template_hook);
	print_output($templater->render());
}

eval(standard_error(fetch_error('invalidid', $idname, $vbulletin->options['contactuslink'])));

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63836 $
|| ####################################################################
\*======================================================================*/
?>
