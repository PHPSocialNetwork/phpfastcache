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
define('THIS_SCRIPT', 'memberlist');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'user',
	'search',
	'cprofilefield',
	'reputationlevel',
);

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'none' => array(
		'memberlist',
		'memberlist_letter',
		'memberlist_results_header',
		'memberlist_resultsbit_field',

		'im_aim',
		'im_icq',
		'im_msn',
		'im_yahoo',
		'im_skype',

		'forumdisplay_sortarrow',
	),
	'search' => array(
		'memberlist_search',
		'memberlist_search_radio',
		'memberlist_search_select',
		'memberlist_search_select_multiple',
		'memberlist_search_select',
		'memberlist_search_textbox',
		'memberlist_search_optional_input',

		'userfield_select_option',
		'userfield_radio_option',
		'userfield_checkbox_option',
	)
);

$actiontemplates['getall'] =& $actiontemplates['none'];

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_misc.php');
require_once(DIR . '/includes/functions_user.php');
require_once(DIR . '/includes/class_postbit.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// enabled check
if (!$vbulletin->options['enablememberlist'])
{
	eval(standard_error(fetch_error('nomemberlist')));
}

// permissions check
if (!($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers']))
{
	print_no_permission();
}

// default action
if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'getall';
}

($hook = vBulletinHook::fetch_hook('memberlist_start')) ? eval($hook) : false;

$perpage = $vbulletin->input->clean_gpc('r', 'perpage', TYPE_UINT);
$sortfield = $vbulletin->input->clean_gpc('r', 'sortfield', TYPE_STR);
$sortorder = $vbulletin->input->clean_gpc('r', 'sortorder', TYPE_STR);
$usergroupid = $vbulletin->input->clean_gpc('r', 'usergroupid', TYPE_UINT);
$ltr = $vbulletin->input->clean_gpc('r', 'ltr', TYPE_NOHTML);
$pagenumber = $vbulletin->input->clean_gpc('r', 'pagenumber', TYPE_UINT);

$vbulletin->input->clean_array_gpc('r', array(
	'ausername'      => TYPE_STR,
	'homepage'       => TYPE_STR,
	'email'          => TYPE_STR,
	'icq'            => TYPE_NOHTML,
	'aim'            => TYPE_STR,
	'yahoo'          => TYPE_STR,
	'msn'            => TYPE_STR,
	'skype'          => TYPE_STR,
	'joindateafter'  => TYPE_STR,
	'joindatebefore' => TYPE_STR,
	'lastpostafter'  => TYPE_STR,
	'lastpostbefore' => TYPE_STR,
	'postslower'     => TYPE_UINT,
	'postsupper'     => TYPE_UINT,
	'userfield'      => TYPE_NOCLEAN,
));

// set defaults and sensible values

if ($sortfield == '')
{
	$sortfield = 'username';
}
if ($sortorder == '')
{
	$sortorder = 'asc';
}

// which fields to display?
$show['homepagecol'] = bitwise($vbulletin->options['memberlistfields'], 1);
$show['searchcol'] = bitwise($vbulletin->options['memberlistfields'], 2);
$show['datejoinedcol'] = bitwise($vbulletin->options['memberlistfields'], 4);
$show['postscol'] = bitwise($vbulletin->options['memberlistfields'], 8);
$show['usertitlecol'] = bitwise($vbulletin->options['memberlistfields'], 16);
$show['lastvisitcol'] = bitwise($vbulletin->options['memberlistfields'], 32);
$show['reputationcol'] = iif(bitwise($vbulletin->options['memberlistfields'], 64) AND $vbulletin->options['reputationenable'], 1, 0);
$show['avatarcol'] = iif(bitwise($vbulletin->options['memberlistfields'], 128) AND $vbulletin->options['avatarenabled'], 1, 0);
$show['birthdaycol'] = bitwise($vbulletin->options['memberlistfields'], 256);
$show['agecol'] = bitwise($vbulletin->options['memberlistfields'], 512);
$show['emailcol'] = (bitwise($vbulletin->options['memberlistfields'], 1024) AND ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canemailmember'] OR ($vbulletin->options['enablepms'] AND $vbulletin->userinfo['permissions']['pmquota'])));
$show['customfields'] = bitwise($vbulletin->options['memberlistfields'], 2048);
$show['imicons'] = bitwise($vbulletin->options['memberlistfields'], 4096);
$show['profilepiccol'] = iif(bitwise($vbulletin->options['memberlistfields'], 8192) AND $permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseeprofilepic'], 1, 0);
$show['advancedlink'] = false;

// work out total columns
$totalcols = $show['emailcol'] + $show['homepagecol'] + $show['searchcol'] + $show['datejoinedcol'] + $show['postscol'] + $show['lastvisitcol'] + $show['reputationcol'] + $show['avatarcol'] + $show['birthdaycol'] + $show['agecol'] + $show['profilepiccol'] + $show['imicons'];

$navpopup = array(
	'id'    => 'memberlist_navpopup',
	'title' => $vbphrase['members_list'],
	'link'  => 'memberlist.php' . $vbulletin->session->vars['sessionurl_q'],
);
construct_quick_nav($navpopup);


// #############################################################################
// show results
if ($_REQUEST['do'] == 'getall')
{

	// start search timer
	$searchstart = microtime();

	$show['advancedlink'] = iif (!$usergroupid AND $vbulletin->options['usememberlistadvsearch'], true, false);

	// get conditions
	$condition = '1=1';
	if ($vbulletin->GPC['ausername'])
	{
		$condition  .=  " AND username LIKE '%" . $db->escape_string_like(htmlspecialchars_uni($vbulletin->GPC['ausername'])) . "%' ";
	}

	if ($vbulletin->options['usememberlistadvsearch'])
	{
		if ($vbulletin->GPC['email'])
		{
			if (can_moderate())
			{
				$condition .= " AND email LIKE '%" . $db->escape_string_like(htmlspecialchars_uni($vbulletin->GPC['email'])) . "%' ";
			}
			else
			{
				print_no_permission();
			}
		}
		if ($vbulletin->GPC['homepage'])
		{
			$condition .= " AND homepage LIKE '%" . $db->escape_string_like(htmlspecialchars_uni($vbulletin->GPC['homepage'])) . "%' ";
		}
		if ($vbulletin->GPC['icq'])
		{
			$condition .= " AND icq LIKE '%" . $db->escape_string_like($vbulletin->GPC['icq']) . "%' ";
		}
		if ($vbulletin->GPC['aim'])
		{
			$condition .= " AND REPLACE(aim, ' ', '') LIKE '%" . $db->escape_string_like(htmlspecialchars_uni(str_replace(' ', '', $vbulletin->GPC['aim']))) . "%' ";
		}
		if ($vbulletin->GPC['yahoo'])
		{
			$condition .= " AND yahoo LIKE '%" . $db->escape_string_like(htmlspecialchars_uni($vbulletin->GPC['yahoo'])) . "%' ";
		}
		if ($vbulletin->GPC['msn'])
		{
			$condition .= " AND msn LIKE '%" . $db->escape_string_like(htmlspecialchars_uni($vbulletin->GPC['msn'])) . "%' ";
		}
		if ($vbulletin->GPC['skype'])
		{
			$condition .= " AND skype LIKE '%" . $db->escape_string_like(htmlspecialchars_uni($vbulletin->GPC['skype'])) . "%' ";
		}
		if ($vbulletin->GPC['joindateafter'])
		{
			$condition .= " AND joindate > UNIX_TIMESTAMP('" . $db->escape_string(strtolower($vbulletin->GPC['joindateafter'])) . "')";
		}
		if ($vbulletin->GPC['joindatebefore'])
		{
			$condition .= " AND joindate < UNIX_TIMESTAMP('" . $db->escape_string(strtolower($vbulletin->GPC['joindatebefore'])) . "')";
		}
		if ($vbulletin->GPC['lastpostafter'])
		{
			$condition .= " AND lastpost > UNIX_TIMESTAMP('" . $db->escape_string(strtolower($vbulletin->GPC['lastpostafter'])) . "')";
		}
		if ($vbulletin->GPC['lastpostbefore'])
		{
			$condition .= " AND lastpost < UNIX_TIMESTAMP('" . $db->escape_string(strtolower($vbulletin->GPC['lastpostbefore'])) . "')";
		}
		if ($vbulletin->GPC['postslower'])
		{
			$condition .= " AND posts >= " . $vbulletin->GPC['postslower'];
		}
		if ($vbulletin->GPC['postsupper'])
		{
			$condition .= " AND posts < " . $vbulletin->GPC['postsupper'];
		}
	}

	// Process Custom Fields..
	$userfields = '';
	$profilefields = $db->query_read_slave("
		SELECT profilefieldid, type, data, optional, memberlist, searchable
		FROM " . TABLE_PREFIX . "profilefield
		WHERE form = 0 "
			. iif(!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehiddencustomfields']), "	AND hidden = 0") . "
		ORDER BY displayorder
	");

	$include_userfield_join = false;

	$urladd = '';
	$profileinfo = array();
	while ($profilefield = $db->fetch_array($profilefields))
	{
		$varname = "field$profilefield[profilefieldid]";
		$optionalvar = $varname . '_opt';
		$profilefield['title'] = $vbphrase[$varname . '_title'];

		if ($profilefield['memberlist'])
		{
			$profilefield['varname'] = $varname;
			if ($profilefield['type'] == 'checkbox' OR $profilefield['type'] == 'select_multiple')
			{
					$profilefield['data'] = unserialize($profilefield['data']);
			}
			$profileinfo[] = $profilefield;
		}

		// Break if this field is not searchable or if the advanced search is disabled
		if (!$profilefield['searchable'] OR !$vbulletin->options['usememberlistadvsearch'])
		{
			continue;
		}

		$value =& $vbulletin->input->clean_gpc('r', $varname, TYPE_NOCLEAN);
		if ($value === null)
		{
			$value = $vbulletin->GPC['userfield']["$varname"];
		}
		$optvalue =& $vbulletin->input->clean_gpc('r', $optionalvar, TYPE_STR);

		$bitwise = 0;
		$sql = '';
		$url = '';

		if (($profilefield['type'] == 'input' OR $profilefield['type'] == 'textarea') AND $value != '')
		{
			$condition .= " AND $varname LIKE '%" . $db->escape_string_like(htmlspecialchars_uni(trim($value))) . "%' ";
			$urladd .= "&amp;$varname=" . urlencode($value);
			$include_userfield_join = true;
		}
		else if ($profilefield['type'] == 'radio' OR $profilefield['type'] == 'select')
		{
			if ($optvalue != '' AND $profilefield['optional'])
			{
				$sql = " AND $varname LIKE '%" . $db->escape_string_like(htmlspecialchars_uni($optvalue)) . "%' ";
				$url = "&amp;$varname=" . urlencode($optvalue);
				$include_userfield_join = true;
			}
			else if ($value !== '')
			{
				$data = unserialize($profilefield['data']);

				foreach ($data AS $key => $val)
				{
					$key++;
					if ($key == $value)
					{
						$val = trim($val);
						$sql = " AND $varname LIKE '" . $db->escape_string_like($val) . '\' ';
						$url = "&amp;$varname=" . intval($value);
						$include_userfield_join = true;
						break;
					}
				}
			}
			else
			{
				continue;
			}

			$condition .= $sql;
			$urladd .= $url;
		}
		else if (($profilefield['type'] == 'checkbox' OR $profilefield['type'] == 'select_multiple') AND is_array($value) AND !empty($value))
		{
			foreach ($value AS $key => $val)
			{
				$condition .= " AND $varname & ". pow(2, $val - 1) . ' ';
				$urladd .= "&amp;$varname" . '[' . urlencode($key) . ']=' . urlencode($val);
				$include_userfield_join = true;
			}
		}
	}

	if ($ltr != '')
	{
		if ($ltr == '#')
		{
			$condition .= " AND username NOT REGEXP(\"^[a-zA-Z]\")";
		}
		else
		{
			$ltr = chr(intval(ord($ltr)));
			$condition .= " AND username LIKE(\"" . $db->escape_string_like($ltr) . "%\")";
		}
	}

	$show['usergroup'] = iif($usergroupid , true, false);

	// Limit to a specific group for usergroup leaders
	if ($usergroupid)
	{
		// check permission to do authorizations in this group
		if (!$leadergroup = $db->query_first_slave("
			SELECT usergroupleader.usergroupleaderid, usergroup.title
			FROM " . TABLE_PREFIX . "usergroupleader AS usergroupleader
			LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroupleader.usergroupid = usergroup.usergroupid)
			WHERE usergroupleader.userid = " . $vbulletin->userinfo['userid'] . "
				AND usergroupleader.usergroupid = $usergroupid
		"))
		{
			print_no_permission();
		}
		$leadergroup['mtitle'] = $vbulletin->usergroupcache["$usergroupid"]['opentag'] . $leadergroup['title'] . $vbulletin->usergroupcache["$usergroupid"]['closetag'];
		$condition .= " AND (FIND_IN_SET('$usergroupid', membergroupids) OR user.usergroupid = $usergroupid)";
		$usergrouplink = "&amp;usergroupid=$usergroupid";
	}
	else if ($vbulletin->options['memberlistposts'])
	{
		$condition .= ' AND posts >= ' . $vbulletin->options['memberlistposts'];
	}

	$sortorder = strtolower($sortorder);

	// specify this if the primary sort will have a lot of tie values (ie, reputation)
	$secondarysortsql = '';
	switch ($sortfield)
	{
		case 'username':
			$sqlsort = 'user.username';
			break;
		case 'joindate':
			$sqlsort = 'user.joindate';
			break;
		case 'posts':
			$sqlsort = 'user.posts';
			break;
		case 'lastvisit':
			$sqlsort = 'lastvisittime';
			break;
		case 'reputation':
			$sqlsort = iif($show['reputationcol'], 'reputationscore', 'user.username');
			$secondarysortsql = ', user.username';
			break;
		case 'age':
			if ($show['agecol'])
			{
				$sqlsort = 'agesort';
				$secondarysortsql = ', user.username';
			}
			else
			{
				$sqlsort = 'user.username';
			}
			break;
		default:
			$sqlsort = 'user.username';
			$sortfield = 'username';
	}

	if ($sortorder != 'asc')
	{
		$sortorder = 'desc';
		$oppositesort = 'asc';
	}
	else
	{ // $sortorder = 'ASC'
		$oppositesort = 'desc';
	}

	// Seems quicker to grab the ids rather than doing a JOIN
	$ids = -1;
	$idarray = array(-1);
	$hiderepids = -1;
	$hidereparray = array();

	foreach ($vbulletin->usergroupcache AS $ugroupid => $usergroup)
	{
		if ($usergroup['genericoptions'] & $vbulletin->bf_ugp_genericoptions['showmemberlist'])
		{
			$ids .= ",$ugroupid";
			$idarray[] = $ugroupid;
		}
		else if ($usergroupid)
		{
			$ids .= ",$ugroupid";
			$idarray[] = $ugroupid;
		}

		if ($usergroup['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canhiderep'])
		{
			$hiderepids .= ",$ugroupid";
			$hidereparray[] = $ugroupid;
		}
	}
	$hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('memberlist_query_userscount')) ? eval($hook) : false;

	$userscount = $db->query_first_slave("
		SELECT COUNT(*) AS users
		FROM " . TABLE_PREFIX . "user AS user
		" . ($include_userfield_join ? "LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield USING (userid)" : '') . "
		$hook_query_joins
		WHERE $condition
			AND (user.usergroupid IN ($ids)" . (defined('MEMBERLIST_INCLUDE_SECONDARY') ? (" OR FIND_IN_SET(" . implode(', user.membergroupids) OR FIND_IN_SET(', $idarray) . ", user.membergroupids)") : '') . ")
			$hook_query_where
	");
	$totalusers = $userscount['users'];

	if (!$totalusers)
	{
		eval(standard_error(fetch_error('searchnoresults', $displayCommon)));
	}

	// set defaults
	sanitize_pageresults($totalusers, $pagenumber, $perpage, 100, $vbulletin->options['memberlistperpage']);

	$sortaddon = ($vbulletin->GPC['postslower']) ? 'postslower=' . $vbulletin->GPC['postslower'] . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['postsupper']) ? 'postsupper=' . $vbulletin->GPC['postsupper'] . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['ausername'] != '') ? 'ausername=' . urlencode($vbulletin->GPC['ausername']) . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['homepage'] != '') ? 'homepage=' . urlencode($vbulletin->GPC['homepage']) . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['icq'] != '') ? 'icq=' . urlencode($vbulletin->GPC['icq']) . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['aim'] != '') ? 'aim=' . urlencode($vbulletin->GPC['aim']) . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['yahoo'] != '') ? 'yahoo=' . urlencode($vbulletin->GPC['yahoo']) . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['msn'] != '') ? 'msn=' . urlencode($vbulletin->GPC['msn']) . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['skype'] != '') ? 'skype=' . urlencode($vbulletin->GPC['skype']) . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['joindateafter'] != '') ? 'joindateafter=' . urlencode($vbulletin->GPC['joindateafter']) . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['joindatebefore'] != '') ? 'joindatebefore=' . urlencode($vbulletin->GPC['joindatebefore']) . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['lastpostafter'] != '') ? 'lastpostafter=' . urlencode($vbulletin->GPC['lastpostafter']) . '&amp;' : '';
	$sortaddon .= ($vbulletin->GPC['lastpostbefore'] != '') ? 'lastpostbefore=' . urlencode($vbulletin->GPC['lastpostbefore']) . '&amp;' : '';
	$sortaddon .= ($usergroupid) ? 'usergroupid=' . $usergroupid . '&amp;' : '';
	$sortaddon .= ($urladd != '') ? $urladd : '';

	$ltraddon = $sortaddon; // Add to letters

	$sortaddon .= ($ltr != '') ? 'ltr=' . urlencode($ltr) . '&amp;' : '';

	$sortaddon = preg_replace('#&amp;$#s', '', $sortaddon);
	$ltraddon = preg_replace('#&amp;$#s', '', $ltraddon);

	$ltrurl = (!empty($ltraddon) ? '&amp;' . $ltraddon : '');

	$sorturl = 'memberlist.php?' . $vbulletin->session->vars['sessionurl'] . $sortaddon;

	$show['sorturlnoargs'] = ($sorturl == 'memberlist.php?' . $vbulletin->session->vars['sessionurl']);

	$selectedletter =& $ltr;

	// build letter selector
	// start with non-alpha characters
	$currentletter = '#';
	$linkletter = urlencode('#');
	$show['selectedletter'] = $selectedletter == '#' ? true : false;
	$templater = vB_Template::create('memberlist_letter');
		$templater->register('currentletter', $currentletter);
		$templater->register('linkletter', $linkletter);
		$templater->register('ltrurl', $ltrurl);
		$templater->register('perpage', $perpage);
		$templater->register('sortfield', $sortfield);
		$templater->register('sortorder', $sortorder);
		$templater->register('usergrouplink', $usergrouplink);
	$letterbits = $templater->render();
	// now do alpha-characters
	for ($i=65; $i < 91; $i++)
	{
		$currentletter = chr($i);
		$linkletter =& $currentletter;
		$show['selectedletter'] = $selectedletter == $currentletter ? true : false;
		$templater = vB_Template::create('memberlist_letter');
			$templater->register('currentletter', $currentletter);
			$templater->register('linkletter', $linkletter);
			$templater->register('ltrurl', $ltrurl);
			$templater->register('perpage', $perpage);
			$templater->register('sortfield', $sortfield);
			$templater->register('sortorder', $sortorder);
			$templater->register('usergrouplink', $usergrouplink);
		$letterbits .= $templater->render();
	}

	$templater = vB_Template::create('forumdisplay_sortarrow');
		$templater->register('oppositesort', $oppositesort);
	$sortarrow["$sortfield"] = $templater->render();

	$limitlower = ($pagenumber - 1) * $perpage + 1;
	$limitupper = ($pagenumber) * $perpage;
	$counter = 0;

	if ($limitupper > $totalusers)
	{
		$limitupper = $totalusers;
		if ($limitlower > $totalusers)
		{
			$limitlower = $totalusers - $perpage;
		}
	}
	if ($limitlower <= 0)
	{
		$limitlower = 1;
	}

	if ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehidden'])
	{
		$lastvisitcond = " , lastactivity AS lastvisittime ";
	}
	else
	{
		$lastvisitcond = " , IF((options & " . $vbulletin->bf_misc_useroptions['invisible'] . " AND user.userid <> " . $vbulletin->userinfo['userid'] . "), 0, lastactivity) AS lastvisittime ";
	}

	if ($show['reputationcol'])
	{
		$repcondition = ",IF((NOT(options & " . $vbulletin->bf_misc_useroptions['showreputation']. ") AND (user.usergroupid IN ($hiderepids)";

		if (!empty($hidereparray))
		{
			foreach($hidereparray AS $value)
			{
				$repcondition .= " OR FIND_IN_SET('$value', membergroupids)";
			}
		}
		$repcondition .= ")), 0, reputation) AS reputationscore";
	}

	if ($show['agecol'])
	{
		$agecondition = ', IF(YEAR(user.birthday_search) > 0 AND user.showbirthday IN (1,2) AND YEAR(user.birthday_search) < YEAR(CURDATE()), user.birthday_search, \'0000-00-00\') AS agesort';
	}
	else
	{
		$agecondition = '';
	}

	// we're not actually checking the age, but the birth date
	// so this is makes asc/desc do what you think for age
	if ($sqlsort == 'agesort')
	{
		$sortorder = ($sortorder == 'desc' ? 'asc' : 'desc');
	}

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('memberlist_fetch')) ? eval($hook) : false;

	$users = $db->query_read_slave("
		SELECT user.*,usertextfield.*,userfield.*, user.userid, options,
			IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, infractiongroupid
		$repcondition
		" . iif($show['avatarcol'], ',avatar.avatarpath,NOT ISNULL(customavatar.userid) AS hascustomavatar,customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight') ."
		" . iif($show['profilepiccol'], ', pp_profilepic.requirement AS profilepicrequirement, customprofilepic.userid AS profilepic, customprofilepic.dateline AS profilepicdateline, customprofilepic.width AS ppwidth, customprofilepic.height AS ppheight') . "
		$lastvisitcond
		$agecondition
		" . iif($usergroupid, ", NOT ISNULL(usergroupleader.usergroupid) AS isleader") . "
		$hook_query_fields
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid=user.userid)
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid=user.userid)
		" . iif($show['reputationcol'], "LEFT JOIN " . TABLE_PREFIX . "reputationlevel AS reputationlevel ON(user.reputationlevelid=reputationlevel.reputationlevelid) ") . "
		" . iif($show['avatarcol'], "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)") . "
		" . iif($show['profilepiccol'], "
			LEFT JOIN " . TABLE_PREFIX . "customprofilepic AS customprofilepic ON (user.userid = customprofilepic.userid)
			LEFT JOIN " . TABLE_PREFIX . "profileblockprivacy AS pp_profilepic ON
				(pp_profilepic.userid = user.userid AND pp_profilepic.blockid = 'profile_picture')") . "
		" . iif($usergroupid, "LEFT JOIN " . TABLE_PREFIX . "usergroupleader AS usergroupleader ON (user.userid = usergroupleader.userid AND usergroupleader.usergroupid=$usergroupid) ") . "
		$hook_query_joins
		WHERE $condition
			AND (user.usergroupid IN ($ids)" . (defined('MEMBERLIST_INCLUDE_SECONDARY') ? (" OR FIND_IN_SET(" . implode(', user.membergroupids) OR FIND_IN_SET(', $idarray) . ", user.membergroupids)") : '') . ")
			$hook_query_where
		ORDER BY $sqlsort $sortorder $secondarysortsql
		LIMIT " . ($limitlower - 1) . ", $perpage
	");

	$counter = 0;
	$memberlistbits = array();
	$today_year = vbdate('Y', TIMENOW, false, false);
	$today_month = vbdate('n', TIMENOW, false, false);
	$today_day = vbdate('j', TIMENOW, false, false);

	// initialize counters
	$itemcount = ($pagenumber - 1) * $perpage;
	$first = $itemcount + 1;

	while ($userinfo = $db->fetch_array($users) AND $counter++ < $perpage)
	{
		$memberlist = array();
		$userinfo = array_merge($userinfo , convert_bits_to_array($userinfo['options'] , $vbulletin->bf_misc_useroptions));
		$userinfo = array_merge($userinfo , convert_bits_to_array($userinfo['adminoptions'] , $vbulletin->bf_misc_adminoptions));
		cache_permissions($userinfo, false);

		// format posts number
		$userinfo['posts'] = vb_number_format($userinfo['posts']);
		if ($userinfo['usertitle'] == '')
		{
			$userinfo['usertitle'] = '&nbsp;';
		}

		fetch_musername($userinfo);
		$userinfo['datejoined'] = vbdate($vbulletin->options['dateformat'], $userinfo['joindate'], true);

		if ($userinfo['lastpost'])
		{
			$memberlist['searchlink'] = true;
		}
		else
		{
			$memberlist['searchlink'] = false;
		}
		if ($userinfo['showemail'] AND $vbulletin->options['displayemails'] AND (!$vbulletin->options['secureemail'] OR ($vbulletin->options['secureemail'] AND $vbulletin->options['enableemail'])) AND $vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canemailmember'] AND $vbulletin->userinfo['userid'])
		{
			$memberlist['emaillink'] = true;
		}
		else
		{
			$memberlist['emaillink'] = false;
		}

		construct_im_icons($userinfo, true);

		if ($userinfo['homepage'] != '' AND $userinfo['homepage'] != 'http://')
		{
			$memberlist['homepagelink'] = true;
		}
		else
		{
			$memberlist['homepagelink'] = false;
		}
		if ($vbulletin->options['enablepms'] AND $vbulletin->userinfo['permissions']['pmquota'] AND ($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']
	 					OR ($userinfo['receivepm'] AND $userinfo['permissions']['pmquota']
	 						AND (!$userinfo['receivepmbuddies'] OR can_moderate() OR strpos(" $userinfo[buddylist] ", ' ' . $vbulletin->userinfo['userid'] . ' ') !== false))
	 				))
	 	{
			$memberlist['pmlink'] = true;
		}
		else
		{
			$memberlist['pmlink'] = false;
		}
		if ($show['birthdaycol'] OR $show['agecol'])
		{
			if (empty($userinfo['birthday']) OR !$userinfo['showbirthday'])
			{
				$userinfo['birthday'] = '&nbsp;';
			}
			else
			{
				$bday = explode('-', $userinfo['birthday']);
				if (date('Y') > $bday[2] AND $bday[2] > 1901 AND $bday[2] != '0000' AND ($userinfo['showbirthday'] == 1 OR $userinfo['showbirthday'] == 2))
				{
					$birthdayformat = mktimefix($vbulletin->options['calformat1'], $bday[2]);
					if ($bday[2] >= 1970)
					{
						$yearpass = $bday[2];
					}
					else
					{
						// day of the week patterns repeat every 28 years, so
						// find the first year >= 1970 that has this pattern
						$yearpass = $bday[2] + 28 * ceil((1970 - $bday[2]) / 28);
					}

					if ($userinfo['showbirthday'] == 2)
					{
						$userinfo['birthday'] = vbdate($birthdayformat, mktime(0, 0, 0, $bday[0], $bday[1], $yearpass), false, true, false);
					}
					else
					{
						$userinfo['birthday'] = '&nbsp;';
					}

					if ($today_year > $bday[2] AND $bday[2] != '0000')
					{
						$userinfo['age'] = $today_year - $bday[2];
						if ($bday[0] > $today_month)
						{
							$userinfo['age']--;
						}
						else if ($bday[0] == $today_month AND $today_day < $bday[1])
						{
							$userinfo['age']--;
						}
					}
					else
					{
						$userinfo['age'] = '&nbsp;';
					}
				}
				else if ($userinfo['showbirthday'] >= 2)
				{
					// lets send a valid year as some PHP3 don't like year to be 0
					$userinfo['birthday'] = vbdate($vbulletin->options['calformat2'], mktime(0, 0, 0, intval($bday[0]), intval($bday[1]), 1992), false, true, false);
				}

				if ($userinfo['birthday'] == '' AND $userinfo['showbirthday'] == 2)
				{ // This should not be blank but win32 has a bug in regards to mktime and dates < 1970
					if ($bday[2] == '0000')
					{
						$userinfo['birthday'] = "$bday[0]-$bday[1]";
					}
					else
					{
						$userinfo['birthday'] = "$bday[0]-$bday[1]-$bday[2]";
					}
				}
			}
		}

		if ($show['reputationcol'])
		{
			$checkperms = cache_permissions($userinfo, false);
			fetch_reputation_image($userinfo, $checkperms);
		}

		$can_view_profile_pic = (
			$show['profilepiccol']
			AND $userinfo['profilepic']
			AND ($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canprofilepic'] OR $userinfo['adminprofilepic'])
		);
		if ($userinfo['profilepicrequirement'] AND !can_view_profile_section($userinfo['userid'], 'profile_picture', $userinfo['profilepicrequirement'], $userinfo))
		{
			$can_view_profile_pic = false;
		}

		if ($can_view_profile_pic)
		{
			if ($vbulletin->options['usefileavatar'])
			{
				$userinfo['profilepicurl'] = $vbulletin->options['profilepicurl'] . '/profilepic' . $userinfo['userid'] . '_' . $userinfo['profilepicrevision'] . '.gif';
			}
			else
			{
				$userinfo['profilepicurl'] = 'image.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userinfo['userid'] . "&amp;dateline=$userinfo[profilepicdateline]&amp;type=profile";
			}
			$userinfo['profilepic'] = "<img src=\"" . $userinfo['profilepicurl'] . "\" alt=\"\" title=\"" . construct_phrase($vbphrase['xs_picture'], $userinfo['username']) . "\" border=\"0\"";
			$userinfo['profilepic'] .= ($userinfo['ppwidth'] AND $userinfo['ppheight']) ? " width=\"$userinfo[ppwidth]\" height=\"$userinfo[ppheight]\" " : '';
			$userinfo['profilepic'] .= "/>";
		}
		else
		{
			$userinfo['profilepic'] = '&nbsp;';
		}

		if ($show['avatarcol'])
		{
			$memberlist['avwidth'] = '';
			$memberlist['avheight'] = '';
			if ($userinfo['avatarid'])
			{
				$memberlist['avatarurl'] = $userinfo['avatarpath'];
			}
			else
			{
				if ($userinfo['hascustomavatar'] AND $vbulletin->options['avatarenabled'] AND ($userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar'] OR $userinfo['adminavatar']))
				{
					if ($vbulletin->options['usefileavatar'])
					{
						$memberlist['avatarurl'] = $vbulletin->options['avatarurl'] . "/thumbs/avatar$userinfo[userid]_$userinfo[avatarrevision].gif";
					}
					else
					{
						$memberlist['avatarurl'] = 'image.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]&amp;dateline=$userinfo[avatardateline]" . '&amp;type=thumb';
					}
					if ($userinfo['avheight'] AND $userinfo['avwidth'])
					{
						$memberlist['avheight'] = "height=\"$userinfo[avheight]\"";
						$memberlist['avwidth'] = "width=\"$userinfo[avwidth]\"";
					}
				}
				else
				{
					$memberlist['avatarurl'] = '';
				}
			}
			if ($memberlist['avatarurl'] == '')
			{
				$memberlist['avatar'] = false;
			}
			else
			{
				$memberlist['avatar'] = true;
			}
		}

		$memberlist['customfields'] = '';

		if ($show['customfields'] AND !empty($profileinfo))
		{
			foreach ($profileinfo AS $index => $value)
			{
				if ($userinfo["$value[varname]"] != '')
				{
					if ($value['type'] == 'checkbox' OR $value['type'] == 'select_multiple')
					{
						unset($customfield);
						foreach ($value['data'] AS $key => $val)
						{
							if ($userinfo["$value[varname]"] & pow(2, $key))
							{
								$customfield .= iif($customfield, ', ') . $val;
							}
						}
					}
					else
					{
						$customfield = $userinfo["$value[varname]"];
					}
				}
				else
				{
					$customfield = '&nbsp;';
				}

				$templater = vB_Template::create('memberlist_resultsbit_field');
					$templater->register('customfield', $customfield);
				$memberlist['customfields'] .= $templater->render();
			}
		}

		$memberlist['hideleader'] = iif ($userinfo['isleader'] OR $userinfo['usergroupid'] == $usergroupid, true, false);

		$itemcount++;
		$memberlist += $userinfo;

		($hook = vBulletinHook::fetch_hook('memberlist_bit')) ? eval($hook) : false;
		
		$memberlistbits[] = $memberlist;
	}

	$last = $itemcount;

	if ($sqlsort == 'agesort')
	{
		$sortorder = ($sortorder == 'desc' ? 'asc' : 'desc');
	}

	$pagenav = construct_page_nav($pagenumber, $perpage, $totalusers, 'memberlist.php?' . $vbulletin->session->vars['sessionurl'], ''
		. (!empty($vbulletin->GPC['perpage']) ? "&amp;pp=$perpage" : "")
		. (!empty($sortorder) ? "&amp;order=$sortorder" : "")
		. (!empty($sortfield) ? "&amp;sort=$sortfield" : "")
		. (!empty($sortaddon) ? "&amp;$sortaddon" : "")
	);

	unset($customfieldsheader);
	if ($show['customfields'] AND is_array($profileinfo))
	{
		foreach ($profileinfo AS $index => $customfield)
		{
			$totalcols++;
			$customfield = $customfield['title'];
			$templater = vB_Template::create('memberlist_results_header');
				$templater->register('customfield', $customfield);
			$customfieldsheader .= $templater->render();
		}
	}
	// build navbar
	$navbits = array('' => $vbphrase['members_list']);

	$searchtime = vb_number_format(fetch_microtime_difference($searchstart), 2);
	$totalcols += !empty($usergroupid) ? 2 : 1;

	$page_templater = vB_Template::create('memberlist');
	$page_templater->register('customfieldsheader', $customfieldsheader);
	$page_templater->register('first', $first);
	$page_templater->register('forumjump', $forumjump);
	$page_templater->register('gobutton', $gobutton);
	$page_templater->register('last', $last);
	$page_templater->register('leadergroup', $leadergroup);
	$page_templater->register('letterbits', $letterbits);
	$page_templater->register('ltr', $ltr);
	$page_templater->register('memberlistbits', $memberlistbits);
	$page_templater->register('pagenav', $pagenav);
	$page_templater->register('perpage', $perpage);
	$page_templater->register('searchtime', $searchtime);
	$page_templater->register('sortarrow', $sortarrow);
	$page_templater->register('sorturl', $sorturl);
	$page_templater->register('spacer_close', $spacer_close);
	$page_templater->register('spacer_open', $spacer_open);
	$page_templater->register('totalcols', $totalcols);
	$page_templater->register('totalusers', $totalusers);
	$page_templater->register('usergroupid', $usergroupid);
	$page_templater->register('usergrouplink', $usergrouplink);
	$page_templater->register('oppositesort', $oppositesort);
}

// #############################################################################
// advanced search
if ($_REQUEST['do'] == 'search')
{
	if (!$vbulletin->options['usememberlistadvsearch'])
	{
		eval(standard_error(fetch_error('nomemberlistsearch')));
	}

	$bgclass = 'alt1';
	// get extra profile fields
	$profilefields = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "profilefield
		WHERE searchable = 1
			AND form = 0
			" . iif(!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehiddencustomfields']), " AND hidden = 0") . "
		ORDER BY displayorder
	");

	$customfields = '';
	while ($profilefield = $db->fetch_array($profilefields))
	{
		$profilefieldname = "field$profilefield[profilefieldid]";
		$optionalname = $profilefieldname . '_opt';
		exec_switch_bg();
		$optional = '';
		$optionalfield = '';
		$profilefield['title'] = $vbphrase[$profilefieldname . '_title'];

		if ($profilefield['type'] == 'input' OR $profilefield['type'] == 'textarea')
		{
			$vbulletin->userinfo["$profilefieldname"] = '';
			$templater = vB_Template::create('memberlist_search_textbox');
				$templater->register('profilefield', $profilefield);
				$templater->register('profilefieldname', $profilefieldname);
			$customfields .= $templater->render();
		}
		else if ($profilefield['type'] == 'select')
		{
			$profilefield['def'] = 0;
			$data = unserialize($profilefield['data']);
			$selectbits = '';
			$selected = '';
			foreach ($data AS $key => $val)
			{
				$key++;
				$templater = vB_Template::create('userfield_select_option');
					$templater->register('key', $key);
					$templater->register('selected', $selected);
					$templater->register('val', $val);
				$selectbits .= $templater->render();
			}
			if ($profilefield['optional'])
			{
				$templater = vB_Template::create('memberlist_search_optional_input');
					$templater->register('optional', $optional);
					$templater->register('optionalname', $optionalname);
					$templater->register('profilefield', $profilefield);
					$templater->register('tabindex', $tabindex);
				$optionalfield = $templater->render();
			}
			$selected = 'selected="selected"';
			$templater = vB_Template::create('memberlist_search_select');
				$templater->register('optionalfield', $optionalfield);
				$templater->register('profilefield', $profilefield);
				$templater->register('profilefieldname', $profilefieldname);
				$templater->register('selectbits', $selectbits);
				$templater->register('selected', $selected);
			$customfields .= $templater->render();
		}
		else if ($profilefield['type'] == 'radio')
		{
			$data = unserialize($profilefield['data']);
			$radiobits = '';
			$checked = '';
			foreach ($data AS $key => $val)
			{
				$key++;
				$templater = vB_Template::create('userfield_radio_option');
					$templater->register('checked', $checked);
					$templater->register('key', $key);
					$templater->register('profilefieldname', $profilefieldname);
					$templater->register('val', $val);
				$radiobits .= $templater->render();
			}
			if ($profilefield['optional'])
			{
				$templater = vB_Template::create('memberlist_search_optional_input');
					$templater->register('optional', $optional);
					$templater->register('optionalname', $optionalname);
					$templater->register('profilefield', $profilefield);
					$templater->register('tabindex', $tabindex);
				$optionalfield = $templater->render();
			}
			$templater = vB_Template::create('memberlist_search_radio');
				$templater->register('optionalfield', $optionalfield);
				$templater->register('profilefield', $profilefield);
				$templater->register('radiobits', $radiobits);
			$customfields .= $templater->render();
		}
		else if ($profilefield['type'] == 'checkbox')
		{
			$data = unserialize($profilefield['data']);
			$radiobits = '';
			$checked = '';
			foreach ($data AS $key => $val)
			{
				$key++;
				$templater = vB_Template::create('userfield_checkbox_option');
					$templater->register('checked', $checked);
					$templater->register('key', $key);
					$templater->register('profilefieldname', $profilefieldname);
					$templater->register('val', $val);
				$radiobits .= $templater->render();
			}
			$templater = vB_Template::create('memberlist_search_radio');
				$templater->register('optionalfield', $optionalfield);
				$templater->register('profilefield', $profilefield);
				$templater->register('radiobits', $radiobits);
			$customfields .= $templater->render();
		}
		else if ($profilefield['type'] == 'select_multiple')
		{
			$data = unserialize($profilefield['data']);
			$selected = '';
			$selectbits = '';
			foreach ($data AS $key => $val)
			{
				$key++;
				$templater = vB_Template::create('userfield_select_option');
					$templater->register('key', $key);
					$templater->register('selected', $selected);
					$templater->register('val', $val);
				$selectbits .= $templater->render();
			}
			$templater = vB_Template::create('memberlist_search_select_multiple');
				$templater->register('profilefield', $profilefield);
				$templater->register('profilefieldname', $profilefieldname);
				$templater->register('selectbits', $selectbits);
			$customfields .= $templater->render();
		}
	}

	// build navbar
	$navbits = array(
		'memberlist.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['members_list'],
		'' => $vbphrase['search']
	);

	$page_templater = vB_Template::create('memberlist_search');
	$page_templater->register('customfields', $customfields);
	$page_templater->register('forumjump', $forumjump);
}

// now spit out the HTML, assuming we got this far with no errors or redirects.

($hook = vBulletinHook::fetch_hook('memberlist_complete')) ? eval($hook) : false;

if (!empty($page_templater))
{
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	$page_templater->register_page_templates();
	$page_templater->register('navbar', $navbar);
	print_output($page_templater->render());
}


/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 61069 $
|| ####################################################################
\*======================================================================*/
?>
