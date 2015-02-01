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
define('THIS_SCRIPT', 'member');
define('CSRF_PROTECTION', true);
define('BYPASS_STYLE_OVERRIDE', 1);
define('FRIENDLY_URL_LINK', 'member');

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'wol',
	'user',
	'messaging',
	'cprofilefield',
	'reputationlevel',
	'infractionlevel',
	'posting',
	'profilefield',
	'activitystream',
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'MEMBERINFO',
	'memberinfo_membergroupbit',
	'im_aim',
	'im_icq',
	'im_msn',
	'im_yahoo',
	'im_skype',
	'activitystream_album_album',
	'activitystream_album_comment',
	'activitystream_album_photo',
	'activitystream_blog_comment',	// Use a hook to add this
	'activitystream_blog_entry',	// Use a hook to add this
	'activitystream_calendar_event',
	'activitystream_cms_article',
	'activitystream_cms_comment',
	'activitystream_date_group',
	'activitystream_photo_date_bit',
	'activitystream_forum_post',
	'activitystream_forum_thread',
	'activitystream_forum_visitormessage',
	'activitystream_socialgroup_discussion',
	'activitystream_socialgroup_group',
	'activitystream_socialgroup_groupmessage',
	'activitystream_socialgroup_photo',
	'activitystream_socialgroup_photocomment',
	'bbcode_code',
	'bbcode_html',
	'bbcode_php',
	'bbcode_quote',
	'bbcode_video',
	'blog_taglist',
	'editor_clientscript',
	'editor_ckeditor',
	'editor_jsoptions_font',
	'editor_jsoptions_size',
	'editor_smilie_category',
	'editor_smilie_row',
	'postbit_onlinestatus',
	'userfield_checkbox_option',
	'userfield_select_option',
	'memberinfo_block',
	'memberinfo_assetpicker',
	'memberinfo_block_aboutme',
	'memberinfo_block_albums',
	'memberinfo_block_activity',
	'memberinfo_block_contactinfo',
	'memberinfo_block_friends',
	'memberinfo_block_friends_mini',
	'memberinfo_block_groups',
	'memberinfo_block_infractions',
	'memberinfo_block_ministats',
	'memberinfo_block_profilefield',
	'memberinfo_block_visitormessaging',
	'memberinfo_block_recentvisitors',
	'memberinfo_block_statistics',
	'memberinfo_block_profilepicture',
	'memberinfo_block_reputation',
	'memberinfo_infractionbit',
	'memberinfo_profilefield',
	'memberinfo_profilefield_category',
	'memberinfo_visitormessage',
	'memberinfo_customize',
	'memberinfo_small',
	'memberinfo_socialgroupbit',
	'memberinfo_socialgroupbit_text',
	'memberinfo_tab',
	'memberinfo_tiny',
	'memberinfo_albumbit',
	'memberinfo_imbit',
	'memberinfo_publicgroupbit',
	'memberinfo_visitormessage_deleted',
	'memberinfo_themerow',
	'memberinfo_visitormessage_ignored',
	'memberinfo_visitormessage_global_ignored',
	'memberinfo_usercss',
	'newpost_disablesmiliesoption',
);


// pre-cache templates used by specific actions
$actiontemplates = array();

if ($_REQUEST['do'] == 'vcard') // don't alter this $_REQUEST
{
	define('NOHEADER', 1);
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_postbit.php');
require_once(DIR . '/includes/functions_user.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
verify_forum_url();

if (!($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers']))
{
	print_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'find'        => TYPE_STR,
	'moderatorid' => TYPE_UINT,
	'userid'      => TYPE_UINT,
	'username'    => TYPE_NOHTML,
	'token'        => TYPE_STR,
));

($hook = vBulletinHook::fetch_hook('member_start')) ? eval($hook) : false;

if ($vbulletin->GPC['find'] == 'firstposter' AND $threadinfo['threadid'])
{
	if ((!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')) OR ($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'])))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
	}
	if (in_coventry($threadinfo['postuserid']) AND !can_moderate($threadinfo['forumid']))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
	}

	$forumperms = fetch_permissions($threadinfo['forumid']);
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
	{
		print_no_permission();
	}
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($threadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR $vbulletin->userinfo['userid'] == 0))
	{
		print_no_permission();
	}

	exec_header_redirect(fetch_seo_url('member|js', $threadinfo, null, 'postuserid', 'postusername'));
}
else if ($vbulletin->GPC['find'] == 'lastposter' AND $threadinfo['threadid'])
{
	if ((!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')) OR ($threadinfo['isdeleted'] AND !can_moderate($threadinfo['forumid'])))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
	}
	if (in_coventry($threadinfo['postuserid']) AND !can_moderate($threadinfo['forumid']))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
	}

	$forumperms = fetch_permissions($threadinfo['forumid']);
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
	{
		print_no_permission();
	}
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($threadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR $vbulletin->userinfo['userid'] == 0))
	{
		print_no_permission();
	}

	require_once(DIR . '/includes/functions_bigthree.php');
	$coventry = fetch_coventry('string');

	$getuserid = $db->query_first_slave("
		SELECT post.userid, post.username
		FROM " . TABLE_PREFIX . "post AS post
		WHERE post.threadid = $threadinfo[threadid]
			AND post.visible = 1
			". ($coventry ? "AND post.userid NOT IN ($coventry)" : '') . "
		ORDER BY dateline DESC
		LIMIT 1
	");

	exec_header_redirect(fetch_seo_url('member|js', $getuserid));
}
else if ($vbulletin->GPC['find'] == 'lastposter' AND $foruminfo['forumid'])
{
	$_permsgetter_ = 'lastposter fperms';
	$forumperms = fetch_permissions($foruminfo['forumid']);
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
	{
		print_no_permission();
	}

	if ($vbulletin->userinfo['userid'] AND in_coventry($vbulletin->userinfo['userid'], true))
	{
		$tachyjoin = "LEFT JOIN " . TABLE_PREFIX . "tachythreadpost AS tachythreadpost ON " .
			"(tachythreadpost.threadid = thread.threadid AND tachythreadpost.userid = " . $vbulletin->userinfo['userid'] . ')';
	}
	else
	{
		$tachyjoin = '';
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

	$forumslist = $forumid;

	require_once(DIR . '/includes/functions_bigthree.php');
	// this isn't including moderator checks, because the last post checks don't either
	if ($coventry = fetch_coventry('string')) // takes self into account
	{
		$globalignore_post = "AND post.userid NOT IN ($coventry)";
		$globalignore_thread = "AND thread.postuserid NOT IN ($coventry)";
	}
	else
	{
		$globalignore_post = '';
		$globalignore_thread = '';
	}

	cache_ordered_forums(1);

	$datecutoff = $vbulletin->forumcache["$foruminfo[forumid]"]['lastpost'] - 30;

	$thread = $db->query_first_slave("
		SELECT thread.threadid
			" . ($tachyjoin ? ', IF(tachythreadpost.lastpost > thread.lastpost, tachythreadpost.lastpost, thread.lastpost) AS lastpost' : '') . "
		FROM " . TABLE_PREFIX . "thread AS thread
		$tachyjoin
		WHERE thread.forumid = $forumid
			AND thread.visible = 1
			AND thread.sticky IN (0,1)
			AND thread.open <> 10
			" . (!$tachyjoin ? "AND lastpost > $datecutoff" : '') . "
			$globalignore_thread
		ORDER BY lastpost DESC
		LIMIT 1
	");

	if (!$thread)
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
	}

	$getuserid = $db->query_first_slave("
		SELECT post.userid, post.username
		FROM " . TABLE_PREFIX . "post AS post
		WHERE threadid = $thread[threadid]
			AND visible = 1
			$globalignore_post
		ORDER BY dateline DESC
		LIMIT 1
	");

	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($getuserid['userid'] != $vbulletin->userinfo['userid'] OR $vbulletin->userinfo['userid'] == 0))
	{
		print_no_permission();
	}

	exec_header_redirect(fetch_seo_url('member|js', $getuserid));
}
else if ($vbulletin->GPC['username'] != '' AND !$vbulletin->GPC['userid'])
{
	$user = $db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = '" . $db->escape_string($vbulletin->GPC['username']) . "'");
	$vbulletin->GPC['userid'] = $user['userid'];
}

if (!$vbulletin->GPC['userid'])
{
	eval(standard_error(fetch_error('unregistereduser')));
}

$fetch_userinfo_options = (
	FETCH_USERINFO_AVATAR | FETCH_USERINFO_LOCATION |
	FETCH_USERINFO_PROFILEPIC | FETCH_USERINFO_SIGPIC |
	FETCH_USERINFO_USERCSS | FETCH_USERINFO_ISFRIEND
);

($hook = vBulletinHook::fetch_hook('member_start_fetch_user')) ? eval($hook) : false;

$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true, $fetch_userinfo_options);

if ($userinfo['usergroupid'] == 4 AND !($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
{
	print_no_permission();
}

// verify that we are at the canonical SEO url
// and redirect to this if not
verify_seo_url('member|js', $userinfo);

/*
Swap the show user css option before loading the profile.
*/
if ($_REQUEST['do'] == 'swapcss')
{
	if (verify_security_token($vbulletin->GPC['token'], $vbulletin->userinfo['securitytoken_raw']))
	{
		if ($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_profile_styling'])
		{
			$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
			$userdata->set_existing($vbulletin->userinfo);
			$userdata->set_bitfield('options', 'showusercss', ($vbulletin->userinfo['options'] & $vbulletin->bf_misc_useroptions['showusercss'] ? 0 : 1));
			$userdata->save();

			$vbulletin->url = fetch_seo_url('member', $userinfo);
			print_standard_redirect('redirect_usercss_toggled');
		}
	}
	else
	{ // Invalid token.
		print_no_permission();
	}
}

$show['vcard'] = ($vbulletin->userinfo['userid'] AND $userinfo['showvcard']);

if ($_REQUEST['do'] == 'vcard' AND $show['vcard'])
{
	// source: http://www.ietf.org/rfc/rfc2426.txt
	$text = "BEGIN:VCARD\r\n";
	$text .= "VERSION:2.1\r\n";
	$text .= "N:;$userinfo[username]\r\n";
	$text .= "FN:$userinfo[username]\r\n";
	$text .= "EMAIL;PREF;INTERNET:$userinfo[email]\r\n";
	if (!empty($userinfo['birthday'][7]) AND $userinfo['showbirthday'] == 2)
	{
		$birthday = explode('-', $userinfo['birthday']);
		$text .= "BDAY:$birthday[2]-$birthday[0]-$birthday[1]\r\n";
	}
	if (!empty($userinfo['homepage']))
	{
		$text .= "URL:$userinfo[homepage]\r\n";
	}
	$text .= 'REV:' . date('Y-m-d') . 'T' . date('H:i:s') . "Z\r\n";
	$text .= "END:VCARD\r\n";

	$filename = $userinfo['userid'] . '.vcf';

	header("Content-Disposition: attachment; filename=$filename");
	header('Content-Length: ' . strlen($text));
	header('Connection: close');
	header("Content-Type: text/x-vCard; name=$filename");
	echo $text;
	exit;
}

// display user info
$userperms = cache_permissions($userinfo, false);

$show['edit_profile'] = (($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) OR can_moderate(0, 'canviewprofile'));

// Check if blog is installed, and show link if so
$show['viewblog'] = $vbulletin->products['vbblog'];

// Check if CMS is installed, and show link if so
$show['viewarticles'] = $vbulletin->products['vbcms'];

($hook = vBulletinHook::fetch_hook('member_execute_start')) ? eval($hook) : false;

require_once(DIR . '/includes/class_userprofile.php');
require_once(DIR . '/includes/class_profileblock.php');

$vbulletin->input->clean_array_gpc('r', array(
	'pagenumber'  => TYPE_UINT,
	'tab'         => TYPE_NOHTML,
	'perpage'     => TYPE_UINT,
	'vmid'        => TYPE_UINT,
	'showignored' => TYPE_BOOL,
	'simple'      => TYPE_BOOL,
	'type'        => TYPE_NOHTML,
));

if ($vbulletin->GPC['vmid'] AND !$vbulletin->GPC['tab'])
{
	$vbulletin->GPC['tab'] = 'visitor_messaging';
}

$profileobj = new vB_UserProfile($vbulletin, $userinfo);
$profileobj->prepare_blogurl();
$blockfactory = new vB_ProfileBlockFactory($vbulletin, $profileobj);

$prepared =& $profileobj->prepared;
$blocks = array();
$tabs = array();
$tablinks = array();

$blocklist = array(
	'stats_mini' => array(
		'class' => 'MiniStats',
		'title' => $vbphrase['mini_statistics'],
	),
	'friends_mini' => array(
		'class' => 'Friends',
		'title' => $vbphrase['friends'],
	),
	'albums' => array(
		'class' => 'Albums',
		'title' => $vbphrase['albums'],
	),
	'visitors' => array(
		'class' => 'RecentVisitors',
		'title' => $vbphrase['recent_visitors'],
		'options' => array(
			'profilemaxvisitors' => $vbulletin->options['profilemaxvisitors']
		)
	),
	'groups' => array(
		'class' => 'Groups',
		'title' => $vbphrase['group_memberships'],
	),
	// VMs must come before Stats to save a query
	'visitor_messaging' => array(
		'class'   => 'VisitorMessaging',
		'title'   => $vbphrase['visitor_messages_tab'],
		'options' => array(
			'pagenumber'  => $vbulletin->GPC['pagenumber'],
			'tab'         => $vbulletin->GPC['tab'],
			'vmid'        => $vbulletin->GPC['vmid'],
			'showignored' => $vbulletin->GPC['showignored'],
		)
	),
	// stats must come before about me to display stats in the about me tab
	'stats' => array(
		'class' => 'Statistics',
		'title' => $vbphrase['statistics'],
	),
	'aboutme' => array(
		'class' => 'AboutMe',
		'title' => $vbphrase['about_me'],
		'options' => array(
			'simple' => $vbulletin->GPC['simple'],
		),
	),
	'contactinfo' => array(
		'class' => 'ContactInfo',
		'title' => $vbphrase['contact_info'],
	),
	'friends' => array(
		'class'   => 'Friends',
		'title'   => $vbphrase['friends'],
		'type'    => 'tab',
		'options' => array(
			'fetchamount'       => $vbulletin->options['friends_per_page'],
			'membertemplate'    => 'memberinfo_small',
			'template_override'	=> 'memberinfo_block_friends',
			'pagenumber'        => $vbulletin->GPC['pagenumber'],
			'tab'               => $vbulletin->GPC['tab'],
			'fetchorder'        => 'asc',
		),
	),
	'infractions' => array(
		'class'   => 'Infractions',
		'title'   => $vbphrase['infractions'],
		'options' => array(
			'pagenumber' => $vbulletin->GPC['pagenumber'],
			'tab'        => $vbulletin->GPC['tab'],
		),
	),
	'profile_picture' => array(
		'class'  => 'ProfilePicture'
	),
    'reputation' => array(
	    'wrap' => false,
	    'class' => 'Reputation',
	    'title' => $vbphrase['reputation'],
		'options' => array(
			'tab'        => $vbulletin->GPC['tab'],
			'comments' => $vbulletin->options['member_rep_comments'],
			'showraters' => $permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseeownrep'], // Odd name, but correct
	    ),
    ),
	'activitystream' => array(
		'class'   => 'ActivityStream',
		'title'   => $userinfo['userid'] == $vbulletin->userinfo['userid'] ? $vbphrase['my_activity'] : construct_phrase($vbphrase['x_activity'], $userinfo['username']),
		'options' => array(
			'tab'        => $vbulletin->GPC['tab'],
			'type'       => $vbulletin->GPC['type'],
			'pagenumber' => $vbulletin->GPC['pagenumber'],
		)
	)
);

if (!empty($vbulletin->GPC['tab']) AND !empty($vbulletin->GPC['perpage']) AND isset($blocklist["{$vbulletin->GPC['tab']}"]))
{
	$blocklist["{$vbulletin->GPC['tab']}"]['options']['perpage'] = $vbulletin->GPC['perpage'];
}

$vbulletin->GPC['simple'] = ($prepared['myprofile'] ? $vbulletin->GPC['simple'] : false);

$profileblock =& $blockfactory->fetch('ProfileFields');
$profileblock->build_field_data($vbulletin->GPC['simple']);

foreach ($profileblock->locations AS $profilecategoryid => $location)
{
	if ($location)
	{
		if (strpos($location, 'profile_tabs') !== false)
		{
			$wrap = false;
		}
		else
		{
			$wrap = true;
		}
		$blocklist["profile_cat$profilecategoryid"] = array(
			'class'         => 'ProfileFields',
			'title'         => $vbphrase["category{$profilecategoryid}_title"],
			'options'       => array(
				'category' => $profilecategoryid,
				'simple'   => $vbulletin->GPC['simple'],
			),
			'hook_location' => $location,
			'wrap' => $wrap,
		);
	}
}

if ($vbulletin->userinfo['options'] & $vbulletin->bf_misc_useroptions['showusercss'])
{
	$show['showusercss'] = 1;
	$usercss_switch_phrase = $vbphrase['hide_user_customizations'];
}
else
{
	$show['showusercss'] = 0;
	$usercss_switch_phrase = $vbphrase['show_user_customizations'];
}

if (!isset($vbulletin->bf_ugp_usercsspermissions['canusetheme']))
{
	$canusetheme = false;
}
else
{
	$canusetheme = $vbulletin->userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['canusetheme'] ? true : false;
}

$cancustomize = $vbulletin->userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['cancustomize'] ? true : false;

//Fairly complex permissions check. Does the admin allow customization?
$show_customize_profile = (bool)($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_profile_styling']) ;

//Even if so, there are a number of reasons not to show the link.
if (!intval($vbulletin->userinfo['userid']) OR
	(($vbulletin->userinfo['userid']) != intval($userinfo['userid'])) OR
	(! $canusetheme AND !$cancustomize))
{
	$show_customize_profile = false;
}

vB_dB_Assertor::init($vbulletin->db, $vbulletin->userinfo);
$usertheme = vB_ProfileCustomize::getUserTheme($vbulletin->GPC['userid']);
$show['userhastheme'] = (vB_ProfileCustomize::getUserThemeType($vbulletin->GPC['userid']) == 1) ? 1 : 0;

if ($show['userhastheme'] AND $show['showusercss'])
{
	define('AS_PROFILE', true);
}

($hook = vBulletinHook::fetch_hook('member_build_blocks_start')) ? eval($hook) : false;

if (!empty($vbulletin->GPC['tab']) AND isset($blocklist["{$vbulletin->GPC['tab']}"]))
{
	$selected_tab = $vbulletin->GPC['tab'];
}
else
{
	$selected_tab = '';
}

foreach ($blocklist AS $blockid => $blockinfo)
{
	$blockobj = $blockfactory->fetch($blockinfo['class']);
	// added a new param for $blocklist var 'wrap'. if it's set to true,
	// the block html will be wrapped by memberinfo_block template.
	// but if this may not be what you want, then set it to false.
	// if you don't set it, it will be determined by 'nowrap' var of the instance of $blocklist['class']
	if (isset($blockinfo['wrap']))
	{
		if ($blockinfo['wrap'] == true)
		{
			$blockobj->nowrap = false;
		}
		else
		{
			$blockobj->nowrap = true;
		}
	}
	$block_html = $blockobj->fetch($blockinfo['title'], $blockid, $blockinfo['options'], $vbulletin->userinfo);

	if (!empty($blockinfo['hook_location']))
	{
		if (!empty($block_html) && strpos($blockinfo['hook_location'], 'profile_tabs') !== false)
		{
			$templater = vB_Template::create('memberinfo_tab');
				$templater->register('selected_tab', $selected_tab);
				$templater->register('blockid', $blockid);
				$templater->register('blockinfo', $blockinfo);
				$templater->register('taburl', fetch_seo_url('member', $userinfo, array('tab' => $blockid)) . "#$blockid");
			$tab_html = $templater->render();
			$template_hook["$blockinfo[hook_location]"] .= $tab_html;
			$template_hook["profile_tabs"] .= $block_html;
		}
		else
		{
			$template_hook["$blockinfo[hook_location]"] .= $block_html;
		}
	}
	else
	{
		$blocks["$blockid"] = $block_html;
	}
}

// check to see if we can see a 'Members List' link in the breadcrumb
if ($vbulletin->options['enablememberlist'] AND $permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers'])
{
	$navbits = construct_navbits(array(
		'memberlist.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['members_list'],
		'' => $userinfo['username']
	));
}
else // no, we can't, so miss off that part of the breadcrumb
{
	$navbits = construct_navbits(array(
		'' => $userinfo['username']
	));
}

if ($vbulletin->products['vbcms'])
{
	$segments = array('type' =>'author', 'value' => $userinfo['userid'] . '-' . $userinfo['username']);
	$author_list_url = vBCms_Route_List::getURL($segments);
}
else
{
	$author_list_url = '';
}

$navbar = render_navbar_template($navbits);

$templatename = 'MEMBERINFO';

$show['pmlink'] =& $show['pm']; // VBIV-12742 Lets be consistant with the name.

($hook = vBulletinHook::fetch_hook('member_complete')) ? eval($hook) : false;

//Now we need to get the css theme information if applicable

if ($show_customize_profile)
{
	$themes = vB_ProfileCustomize::getThemes();
	if (empty($themes))
	{
		$canusetheme = false;
		if (!$cancustomize)
		{
			$show_customize_profile = false;
		}
	}
}
$themes[-1] = vB_ProfileCustomize::getDefaultTheme();
$themes[-1]['title'] = $vbphrase['site_default_theme'];
$themes[-1]['thumbnail'] = 'default_theme.png';
//We need to get the themes in rows of 4, and we also need to generate the
//json version of the theme array we'll use for setting the events;
$i = 0;
$themelist = '';
$themeblock = array();
if ($show_customize_profile)
{
	$themerow = array();
	foreach ($themes as $themeid => $theme)
	{
		$theme['themeid'] = $themeid;
		$themerow[] = $theme;
		$i++;

		$themeblock[] = "\"$themeid\":\"profiletheme_$themeid\"";
		if ($i > 3)
		{
			$template = vB_Template::create('memberinfo_themerow');
			$template->register('themes', $themerow);
			$themelist .= $template->render();
			$i = 0;
			$themerow = array();
		}
	}
	if ($i > 0)
	{
		$template = vB_Template::create('memberinfo_themerow');
		$template->register('themes', $themerow);
		$themelist .= $template->render();
		$i = 0;
		$themerow = array();
	}
}
$jsblock = "settings_string = '{\\\n" . implode($themeblock, ",\\\n" ) . "}';\n" .
	"current_theme = '" . $initial_theme . "';\n";

$page_templater = vB_Template::create($templatename);
	$page_templater->register('styleid', $vbulletin->options['styleid']);
	$page_templater->register('timenow', TIMENOW);
	$page_templater->register('textdirection', $vbulletin->options['styleid']);
	$page_templater->register_page_templates();
	$page_templater->register('blocks', $blocks);
	$page_templater->register('navbar', $navbar);
	$page_templater->register('prepared', $prepared);
	$page_templater->register('selected_tab', $selected_tab);
	$page_templater->register('template_hook', $template_hook);
	$page_templater->register('author_list_url', $author_list_url);
	$page_templater->register('usercss_switch_phrase', $usercss_switch_phrase);
	$page_templater->register('userinfo', $userinfo);
	$page_templater->register('show_customize_profile', $show_customize_profile);
	$page_templater->register('author_list_url', $author_list_url);
	$page_templater->register('show_userid', $vbulletin->GPC['userid']);
	$page_templater->register('reputationdisplay', $prepared['reputationdisplay']);
	$page_templater->register('activity_phrase', $userinfo['userid'] == $vbulletin->userinfo['userid'] ? $vbphrase['my_activity'] : construct_phrase($vbphrase['x_activity'], $userinfo['username']));

	if ($usertheme)
	{
		$jsblock .= "var userTheme = new Array();\n" ;
		foreach ($usertheme as $varname => $value)
		{
			if (stripos($varname, 'font') > -1)
			{
				continue;
			}
			if (preg_match('#<\s*script.*>#i', $value) > 0 )
			{
				unset($usertheme[$varname]);
				continue;
			}

			$value = strtr($value, array('\\' => '\\\\', "'" => "\\'", '"' => '\\"', "\r" => '\\r', "\n" => '\\n', '</' => '<\/', '<' => '&lt;', '>' => '&gt;'));

			$jsblock .=  "userTheme['$varname'] = '" . $value . "';\n";
		}

		$jsblock .= "var select_color_information = '" . vB_Template_Runtime::escapeJS($vbphrase['select_color_information']) . "';\n";
		$jsblock .= "var no_server_response = '" . vB_Template_Runtime::escapeJS($vbphrase['no_server_response']) . "';\n";
		$jsblock .= "var not_a_valid_color = '" . vB_Template_Runtime::escapeJS($vbphrase['not_a_valid_color']) . "';\n";
		$jsblock .= "var str_OK = '" . vB_Template_Runtime::escapeJS($vbphrase['okay']) . "';\n";
		$jsblock .= "var str_exit = '" . vB_Template_Runtime::escapeJS($vbphrase['exit']) . "';\n";
		$jsblock .= "var str_dont_exit = '" . vB_Template_Runtime::escapeJS($vbphrase['dont_exit']) . "';\n";
		$jsblock .= "var profile_reverted_message = '" . vB_Template_Runtime::escapeJS($vbphrase['profile_reverted_message']) . "';\n";
		$jsblock .= "var nothing_to_revert = '" . vB_Template_Runtime::escapeJS($vbphrase['nothing_to_revert']) . "';\n";
		$jsblock .= "var confirm_sitedefault_save = '" . vB_Template_Runtime::escapeJS($vbphrase['confirm_sitedefault_msg']) . "';\n";
	}

	//create the user profile customization interface
	if ($show_customize_profile)
	{
		require_once(DIR . '/includes/class_usercss.php');
		$usercss = new vB_UserCSS($vbulletin, $vbulletin->userinfo['userid']);
		$allowedfonts = $usercss->build_select_option($vbulletin->options['usercss_allowed_fonts']);

		$jsblock .= "var default_font = '" . vB_Template_Runtime::escapeJS(vB::$vbulletin->stylevars['font']['family']) . "';\n";

		$fontnames = "<option class=\"grey_select_item\" style=\"font-family:" . vB::$vbulletin->stylevars['font']['family']. "\" value=\"default\">" .
			$vbphrase['default'] . "</option>\n";
		foreach ($allowedfonts AS $key => $font)
		{
			$selected = ($font == $usertheme['font_family'] ? 'selected="selected"' : '');
			$fontnames .= "<option style=\"font-family:$font;\"  class=\"grey_select_item\" $selected  value=\"$font\">".
				$vbphrase['usercss_font_' . $key] . "</option>\n";
		}

		$fontsizes = '';
		$allowedsizes = $usercss->build_select_option($vbulletin->options['usercss_allowed_font_sizes']);
		$jsblock .= "var fontsizes = new Array();\n";
		foreach ($allowedsizes as $key => $fontsize)
		{
			$selected = ($fontsize == $usertheme['fontsize'] ? 'selected="selected"' : '');
			$phrasekey = str_replace('-', '', $fontsize);
			$fontsizes .= "<option $selected  class=\"grey_select_item\" value=\"$fontsize\">" .
				$vbphrase[$phrasekey] . "</option>\n";
			$jsblock .= "fontsizes['$fontsize'] = '" . vB_Template_Runtime::escapeJS($vbphrase[$phrasekey]) . "';\n";
		}

		require_once DIR . '/includes/adminfunctions.php';

		$template = vB_Template::create('memberinfo_customize');
		$template->register('fontnames', $fontnames);
		$template->register('fontsizes', $fontsizes);
		$template->register('themelist', $themelist);
		$template->register('canusetheme', $canusetheme);
		$template->register('cancustomize', $cancustomize);
		$template->register('is_superadmin', can_administer('cansetdefaultprofile') ? 1 : 0);
		$template->register('caneditfontfamily', $userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditfontfamily']);
		$template->register('caneditfontsize', $userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditfontsize']);
		$template->register('caneditbgimage', $userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditbgimage']);
		$template->register('caneditcolors', $userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditcolors']);
		$template->register('caneditborders', $userinfo['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditborders']);
		$template->register('contenttypeid',  vB_Types::instance()->getContentTypeID('vBForum_Album'));
		$template->register('poststarttime', TIMENOW);
		$template->register('posthash',
			vB_Template_Runtime::escapeJS( md5(TIMENOW . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt'])));

		//see if this user is using the asset manager.
		$show_albums = 'false';

		//see if this user has an album we can use for background images.
		$albums = vB_dB_Assertor::getInstance()->assertQuery('firstPublicAlbum',
			array('userid' => $vbulletin->userinfo['userid']));

		if ($albums->valid())
		{
			$album = $albums->current();
			if (!empty($album))
			{
				//this user has at least one public album
				$show_albums = 'true';
			}
		}
		$jsblock .= "var show_albums = $show_albums;\n";
		$template->register('show_assetmanager', $show_albums == 'true');

		//Now the initial variables.
		$template->register('title_text_color', $usertheme['title_text_color']);
		$template->register('module_text_color', $usertheme['module_text_color']);
		$template->register('module_link_color', $usertheme['module_link_color']);
		$template->register('module_border', $usertheme['module_border']);
		$template->register('content_text_color', $usertheme['content_text_color']);
		$template->register('content_link_color', $usertheme['content_link_color']);
		$template->register('content_border', $usertheme['content_border']);
		$template->register('button_text_color', $usertheme['button_text_color']);
		$template->register('button_border', $usertheme['button_border']);
		$template->register('moduleinactive_text_color', $usertheme['moduleinactive_text_color']);
		$template->register('moduleinactive_link_color', $usertheme['moduleinactive_link_color']);
		$template->register('moduleinactive_border', $usertheme['moduleinactive_border']);
		$template->register('headers_text_color', $usertheme['headers_text_color']);
		$template->register('headers_link_color', $usertheme['headers_link_color']);
		$template->register('headers_border', $usertheme['headers_border']);
		$template->register('page_link_color', $usertheme['page_link_color']);
		$template->register('page_background',
				(empty($usertheme['page_background_image']) OR $usertheme['page_background_image']=='none') ?
				$usertheme['page_background_color'] :  $usertheme['page_background_image'] );
		$template->register('module_background',
			(empty($usertheme['module_background_image']) OR $usertheme['module_background_image']=='none') ?
				$usertheme['module_background_color'] :  $usertheme['module_background_image'] );
		$template->register('content_background',
			(empty($usertheme['content_background_image']) OR $usertheme['content_background_image']=='none') ?
				$usertheme['content_background_color'] :  $usertheme['content_background_image'] );
		$template->register('button_background',
			(empty($usertheme['button_background_image']) OR $usertheme['button_background_image']=='none') ?
				$usertheme['button_background_color'] :  $usertheme['button_background_image'] );
		$template->register('moduleinactive_background',
			(empty($usertheme['moduleinactive_background_image']) OR $usertheme['moduleinactive_background_image']=='none') ?
				$usertheme['moduleinactive_background_color'] :  $usertheme['moduleinactive_background_image'] );
		$template->register('headers_background',
			(empty($usertheme['headers_background_image']) OR $usertheme['headers_background_image']=='none') ?
				$usertheme['headers_background_color'] :  $usertheme['headers_background_image'] );
		$memberinfo_customize = $template->render();
		$page_templater->register('themes_js', $jsblock);
		$page_templater->register('memberinfo_customize', $memberinfo_customize);
		$page_templater->register('timenow', TIMENOW);
		$page_templater->register('posthash',
			vB_Template_Runtime::escapeJS( md5(TIMENOW . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt'])));
	}

print_output($page_templater->render(false));

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 74160 $
|| ####################################################################
\*======================================================================*/
