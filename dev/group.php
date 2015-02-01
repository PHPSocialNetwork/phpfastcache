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
define('THIS_SCRIPT', 'group');
define('CSRF_PROTECTION', true);
define('GET_EDIT_TEMPLATES', 'message,picture');

if ($_POST['do'] == 'message')
{
	if (isset($_POST['ajax']))
	{
		define('NOPMPOPUP', 1);
		define('NOSHUTDOWNFUNC', 1);
	}
	if (isset($_POST['fromquickcomment']))
	{	// Don't update Who's Online for Quick Comments since it will get stuck on that until the user goes somewhere else
		define('LOCATION_BYPASS', 1);
	}
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array (
	'socialgroups',
	'search',
	'user',
	'posting',
	'album',
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
);

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'overview' => array(
		'socialgroups_overview',
		'socialgroups_category_cloud_bit',
		'socialgroups_mygroups_bit',
		'socialgroups_newgroup_bit',
		'socialgroups_updatedgroups_bit'
	),
	'grouplist' => array(
		'SOCIALGROUPS',
		'socialgroups_grouplist',
		'socialgroups_grouplist_bit',
		'socialgroups_mygroups',
		'socialgroups_mygroups_bit',
		'socialgroups_overview',
		'forumdisplay_sortarrow',
		'socialgroups_search'
	),
	'invitations' => array(
		'SOCIALGROUPS',
		'socialgroups_grouplist',
		'socialgroups_grouplist_bit',
		'forumdisplay_sortarrow',
		'socialgroups_search'
	),
	'requests' => array(
		'SOCIALGROUPS',
		'socialgroups_grouplist',
		'socialgroups_grouplist_bit',
		'forumdisplay_sortarrow',
		'socialgroups_search'
	),
	'moderatedgms' => array(
		'SOCIALGROUPS',
		'socialgroups_grouplist',
		'socialgroups_grouplist_bit',
		'forumdisplay_sortarrow',
		'socialgroups_search'
	),
	'transfer' => array(
		'socialgroups_transfer'
	),
	'dotransfer' => array(
		'socialgroups_transfer'
	),
	'accepttransfer' => array(
		'socialgroups_accepttransfer'
	),
	'canceltransfer' => array(
		'socialgroups_transfer'
	),
	'leave' => array(
		'socialgroups_confirm'
	),
	'delete' => array(
		'socialgroups_confirm'
	),
	'join' => array(
		'socialgroups_confirm'
	),
	'edit' => array(
		'socialgroups_form'
	),
	'create' => array(
		'socialgroups_form'
	),
	'docreate' => array(
		'socialgroups_form'
	),
	'view' => array(
		'socialgroups_memberbit',
		'socialgroups_group',
		'editor_clientscript',
		'editor_smilie_category',
		'editor_smilie_row',
		'newpost_disablesmiliesoption',
		'editor_ckeditor',
		'editor_jsoptions_font',
		'editor_jsoptions_size',
		'forumdisplay_sortarrow',
		'socialgroups_picturebit',
		'socialgroups_discussion',
		'socialgroups_discussion_ignored',
		'socialgroups_discussion_deleted'
	),
	'viewmembers' => array(
		'im_aim',
		'im_icq',
		'im_msn',
		'im_skype',
		'im_yahoo',
		'memberinfo_small',
		'postbit_onlinestatus',
		'socialgroups_memberlist'
	),
	'subscribe' => array (
		'socialgroups_subscribe',
		'socialgroups_subscribe_group',
	),
	'message' => array(
		'socialgroups_editor',
		'visitormessage_preview',
		'socialgroups_message',
	),
	'report' => array(
		'newpost_usernamecode',
		'reportitem',
	),
	'reportpicture' => array(
		'newpost_usernamecode',
		'reportitem',
	),
	'grouppictures' => array(
		'socialgroups_pictures',
		'socialgroups_picturebit'
	),
	'picture' => array(
		'socialgroups_picture',
		'picturecomment_commentarea',
		'picturecomment_form',
		'picturecomment_message',
		'picturecomment_message_deleted',
		'picturecomment_message_ignored',
		'picturecomment_message_global_ignored',
	),
	'editpictures' => array(
		'socialgroups_picture_edit',
		'socialgroups_picture_editbit',
	),
	'manage' => array(
		'socialgroups_manage',
		'socialgroups_managebit'
	),
	'managemembers' => array(
		'socialgroups_managebit',
		'socialgroups_managemembers',
	),
	'sendinvite' => array(
		'socialgroups_manage',
		'socialgroups_managebit'
	),
	'removepicture' => array(
		'socialgroups_confirm'
	),
	'discuss' => array(
		'socialgroups_message',
		'socialgroups_message_deleted',
		'socialgroups_message_ignored',
		'socialgroups_discussionview',
		'editor_smilie_category',
		'editor_smilie_row',
		'newpost_disablesmiliesoption',
		'editor_ckeditor',
		'editor_clientscript',
		'editor_jsoptions_font',
		'editor_jsoptions_size',
		'showthread_bookmarksite',
		'bbcode_code',
		'bbcode_html',
		'bbcode_php',
		'bbcode_quote',
		'bbcode_video',
	),
	'owngroup' => array(
		'socialgroups_owngroup_bit'
	),
	'categorylist' => array(
		'forumdisplay_sortarrow',
		'socialgroups_categorylist',
		'socialgroups_categorylist_bit'
	)
);


$action_needs_groupid = array(
	'accepttransfer',
	'cancelinvites',
	'delete',
	'deletemessage',
	'discuss',
	'doaccepttransfer',
	'dodelete',
	'doedit',
	'dojoin',
	'doleave',
	'doremovepicture',
	'edit',
	'editicon',
	'grouppictures',
	'join',
	'kickmembers',
	'leave',
	'manage',
	'managemembers',
	'markread',
	'message',
	'pendingmembers',
	'picture',
	'removepicture',
	'report',
	'reportpicture',
	'sendemail',
	'sendinvite',
	'sendpictureemail',
	'transfer',
	'updateicon',
	'view',
	'viewmembers'
);

// set no action template to default action template
if (!$_REQUEST['do'] AND ($_REQUEST['gmid'] OR $_REQUEST['discussionid']))
{
	$actiontemplates['none'] = $actiontemplates['discuss'];
}
else if (!$_REQUEST['do'] AND ($_REQUEST['groupid']))
{
	$actiontemplates['none'] = $actiontemplates['view'];
}
else if ($_REQUEST['cat'])
{
	$actiontemplates['none'] = $actiontemplates['grouplist'];
}
else
{
	$actiontemplates['none'] = $actiontemplates['overview'];
}

if ($_REQUEST['do'] == 'discuss' OR (!$_REQUEST['do'] AND $_REQUEST['discussionid']))
{
	$specialtemplates[] = 'bookmarksitecache';
}

if (!$_REQUEST['do'] OR $_REQUEST['do'] == 'overview')
{
	$specialtemplates[] = 'sg_category_cloud';
	$specialtemplates[] = 'sg_newest_groups';
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_socialgroup.php');
require_once(DIR . '/includes/functions_album.php');
require_once(DIR . '/includes/functions_user.php');
require_once(DIR . '/vb/search/core.php');
require_once(DIR . '/includes/class_postbit.php'); // for construct_im_icons

$vbulletin->options['sg_enablesocialgroupicons'] = true;

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_forum_url();

$vbulletin->input->clean_array_gpc('r', array(
	'groupid'      => TYPE_UINT,
	'gmid'         => TYPE_UINT,
	'discussionid' => TYPE_UINT,
	'cat'          => TYPE_UINT
));

$contenttypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');

($hook = vBulletinHook::fetch_hook('group_start_precheck')) ? eval($hook) : false;

if (empty($_REQUEST['do']))
{
	if ($vbulletin->GPC['discussionid'] OR $vbulletin->GPC['gmid'])
	{
		$_REQUEST['do'] = 'discuss';
	}
	else if ($vbulletin->GPC['groupid'])
	{
		$_REQUEST['do'] = 'view';
	}
	else if ($vbulletin->GPC['cat'])
	{
		$_REQUEST['do'] = 'grouplist';
	}
	else
	{
		$_REQUEST['do'] = 'overview';
	}
}

if (
	!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_groups'])
	OR !($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview'])
	OR !($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups'])
	)
{
	print_no_permission();
}

// check if specific message was requested
if ($vbulletin->GPC['gmid'])
{
	$messageinfo = verify_groupmessage($vbulletin->GPC['gmid'], false, false);
	if (!empty($messageinfo['discussionid']))
	{
		$vbulletin->GPC['discussionid'] = $messageinfo['discussionid'];
	}
}

// If a discussionid is specified, verify it is valid and the user has access
if ($vbulletin->GPC['discussionid'])
{
	// Verify will error out if it fails
	$discussion = verify_socialdiscussion($vbulletin->GPC['discussionid']);

	$vbulletin->GPC['groupid'] = $discussion['groupid'];

	if (!empty($messageinfo))
	{
		$messageinfo['groupid'] = $discussion['groupid'];
	}
}

// If a group id is specified, but the group doesn't exist, error out
if ($vbulletin->GPC['groupid'])
{
	$group = fetch_socialgroupinfo($vbulletin->GPC['groupid'], true);

	if (empty($group))
	{
		standard_error(fetch_error('invalidid', $vbphrase['social_group'], $vbulletin->options['contactuslink']));
	}
}

// Error out if no group specified, but a group is needed for the actions
if (in_array($_REQUEST['do'], $action_needs_groupid) AND empty($group))
{
	standard_error(fetch_error('invalidid', $vbphrase['social_group'], $vbulletin->options['contactuslink']));
}

// Check a specified message is viewable
if ($vbulletin->GPC['gmid'])
{
	if ($messageinfo['state'] == 'deleted' AND !fetch_socialgroup_modperm('canviewdeleted', $group))
	{
		standard_error(fetch_error('invalidid', $vbphrase['message'], $vbulletin->options['contactuslink']));
	}
	else if ($messageinfo['state'] == 'moderation' AND !fetch_socialgroup_modperm('canmoderategroupmessages', $group) AND $messageinfo['postuserid'] != $vbulletin->userinfo['userid'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['message'], $vbulletin->options['contactuslink']));
	}
}

($hook = vBulletinHook::fetch_hook('group_start_postcheck')) ? eval($hook) : false;

// #######################################################################
if ($_REQUEST['do'] == 'grouplist' OR $_REQUEST['do'] == 'invitations' OR $_REQUEST['do'] == 'requests' OR $_REQUEST['do'] == 'moderatedgms')
{
	require_once(DIR . '/includes/class_socialgroup_search.php');
	$socialgroupsearch = new vB_SGSearch($vbulletin);

	switch ($_REQUEST['do'])
	{
		case 'invitations':
		{
			if (!$vbulletin->userinfo['userid'])
			{
				print_no_permission();
			}

			$socialgroupsearch->add('member', $vbulletin->userinfo['userid']);
			$socialgroupsearch->add('membertype', 'invited');
			$grouplisttitle = $navphrase = $vbphrase['your_invites'];
			$doaction = 'invitations';
		}
		break;

		case 'requests':
		{
			$socialgroupsearch->add('creator', $vbulletin->userinfo['userid']);
			$socialgroupsearch->add('pending', true);
			$grouplisttitle = $navphrase = $vbphrase['your_groups_in_need_of_attention'];
			$doaction = 'requests';
		}
		break;

		case 'moderatedgms':
		{
			if (!$vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canmanageowngroups'])
			{
				print_no_permission();
			}

			$socialgroupsearch->add('creator', $vbulletin->userinfo['userid']);
			$socialgroupsearch->add('moderatedgms', true);
			$grouplisttitle = $navphrase = $vbphrase['your_groups_with_moderated_messages'];
			$doaction = 'moderatedgms';
		}
		break;
	}

	if (!$vbulletin->options['threadmarking'])
	{
		$socialgroupsearch->check_read(false);
	}

	$doaction = $doaction ? $doaction : 'grouplist';

	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'      => TYPE_UINT,
		'pagenumber'   => TYPE_UINT,
		'sortfield'    => TYPE_NOHTML,
		'sortorder'    => TYPE_NOHTML,
		'cat'          => TYPE_UINT,
		'owngrouppage' => TYPE_UINT
	));

	$vbulletin->input->clean_array_gpc('r', array(
		'dofilter'   => TYPE_BOOL
	));

	if ($vbulletin->GPC['cat'])
	{
		$vbulletin->GPC['dofilter'] = true;
	}

	if (empty($grouplisttitle))
	{
		if (empty($mygroup_bits))
		{
			$grouplisttitle = $vbphrase['social_groups'];
		}
		else
		{
			$grouplisttitle = $vbphrase['available_groups'];
		}
	}

	// start processing group list
	$sortfield  = $vbulletin->GPC['sortfield'];
	$perpage    = $vbulletin->GPC['perpage'];
	$pagenumber = $vbulletin->GPC['pagenumber'];
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$pagenumber = $vbulletin->GPC['pagenumber'];

	if (empty($sortfield))
	{
		$sortfield = 'lastpost';
	}

	$perpage = (!$vbulletin->GPC['perpage'] OR $vbulletin->GPC['perpage'] > $vbulletin->options['sg_maxperpage']) ? $vbulletin->options['sg_perpage'] : $vbulletin->GPC['perpage'];

	$socialgroupsearch->set_sort($sortfield, $vbulletin->GPC['sortorder']);

	if ($vbulletin->GPC['dofilter'])
	{
		$vbulletin->input->clean_array_gpc('r', array(
			'filtertext'       => TYPE_NOHTML,
			'cat'		       => TYPE_UINT,
			'memberlimit'      => TYPE_UINT,
			'memberless'       => TYPE_UINT,
			'discussionlimit'  => TYPE_UINT,
			'discussionless'   => TYPE_BOOL,
			'messagelimit'     => TYPE_UINT,
			'messageless'      => TYPE_BOOL,
			'picturelimit'     => TYPE_UINT,
			'pictureless'      => TYPE_BOOL,
			'filter_date_gteq' => TYPE_UNIXTIME,
			'filter_date_lteq' => TYPE_UNIXTIME,
		));

		$filters = array();

		if (!empty($vbulletin->GPC['filtertext']))
		{
			$filters['text'] = $vbulletin->GPC['filtertext'];
 		}

 		if (!empty($vbulletin->GPC['cat']))
 		{
 			$filters['category'] = $vbulletin->GPC['cat'];
 		}

 		if (!empty($vbulletin->GPC['filter_date_lteq']))
		{
			$filters['date_lteq'] = $vbulletin->GPC['filter_date_lteq'];
 		}

 		if (!empty($vbulletin->GPC['filter_date_gteq']))
		{
			$filters['date_gteq'] = $vbulletin->GPC['filter_date_gteq'];
 		}

 		if (!empty($vbulletin->GPC['memberlimit']))
		{
			if ($vbulletin->GPC['memberless'])
			{
				$filters['members_lteq'] = $vbulletin->GPC['memberlimit'];
			}
			else
			{
				$filters['members_gteq'] = $vbulletin->GPC['memberlimit'];
			}
			$memberlessselected[$vbulletin->GPC['memberless']] = 'selected="selected"';
			$memberlimit = $vbulletin->GPC['memberlimit'];
 		}

		if (!empty($vbulletin->GPC['discussionlimit']))
		{
			if ($vbulletin->GPC['discussionless'])
			{
				$filters['discussion_lteq'] = $vbulletin->GPC['discussionlimit'];
			}
			else
			{
				$filters['discussion_gteq'] = $vbulletin->GPC['discussionlimit'];
			}
			$discussionlessselected[$vbulletin->GPC['discussionless']] = 'selected="selected"';
			$discussionlimit = $vbulletin->GPC['discussionlimit'];
 		}

 		if (!empty($vbulletin->GPC['messagelimit']))
		{
			if ($vbulletin->GPC['messageless'])
			{
				$filters['message_lteq'] = $vbulletin->GPC['messagelimit'];
			}
			else
			{
				$filters['message_gteq'] = $vbulletin->GPC['messagelimit'];
			}
			$messagelessselected[$vbulletin->GPC['messageless']] = 'selected="selected"';
			$messagelimit = $vbulletin->GPC['messagelimit'];
 		}

 		if (!empty($vbulletin->GPC['picturelimit']))
		{
			if ($vbulletin->GPC['pictureless'])
			{
				$filters['picture_lteq'] = $vbulletin->GPC['picturelimit'];
			}
			else
			{
				$filters['picture_gteq'] = $vbulletin->GPC['picturelimit'];
			}
			$picturelessselected[$vbulletin->GPC['pictureless']] = 'selected="selected"';
			$picturelimit = $vbulletin->GPC['picturelimit'];
 		}

 		foreach ($filters AS $key => $value)
 		{
 			$socialgroupsearch->add($key, $value);
 		}
 	}

 	($hook = vBulletinHook::fetch_hook('group_list_filter')) ? eval($hook) : false;

	$totalgroups = $socialgroupsearch->execute(true);
	$grouplist = '';
	if ($socialgroupsearch->has_errors())
	{
		$errorlist = '';

		if ($_REQUEST['do'] == 'invitations' OR $_REQUEST['do'] == 'requests' OR $_REQUEST['do'] == 'moderatedgms')
		{
			$errorlist = "<li>$vbphrase[no_results]</li>";
			$show['noresults'] = $show['errors'] = true;
		}
		else
		{
			foreach ($socialgroupsearch->generator->errors AS $error)
			{
				$errorlist .= "<li>$error</li>";
			}

			// don't show the error box if we didn't actually do a search
			$show['errors'] = $vbulletin->GPC['dofilter'];
		}

		$_REQUEST['do'] = 'overview';
	}
	else
	{
		sanitize_pageresults($totalgroups, $pagenumber, $perpage);

		$socialgroupsearch->limit(($pagenumber - 1) * $perpage, $perpage);

		$groups = $socialgroupsearch->fetch_results();

		$show['gminfo'] = $vbulletin->options['socnet_groups_msg_enabled'];
		$show['pictureinfo'] = ($vbulletin->options['socnet_groups_pictures_enabled']) ? true : false;

		$lastpostalt = ($show['pictureinfo'] ? 'alt2' : 'alt1');

		$category_name = false;
		$show['category_names'] = true;

		if (is_array($groups))
		{
			foreach ($groups AS $group)
			{
				// get category for navbits
				if ($vbulletin->GPC['cat'] AND !$category_name)
				{
					$category_name = $group['categoryname'];
					$show['category_names'] = false;
				}

				$group = prepare_socialgroup($group);

				$group['canjoin'] = can_join_group($group);
				$group['canleave'] = can_leave_group($group);

				$show['pending_link'] = (fetch_socialgroup_modperm('caninvitemoderatemembers', $group) AND $group['moderatedmembers'] > 0);
				$show['lastpostinfo'] = ($group['lastposterid']);

				($hook = vBulletinHook::fetch_hook('group_list_groupbit')) ? eval($hook) : false;

				$templater = vB_Template::create('socialgroups_grouplist_bit');
					$templater->register('group', $group);
					$templater->register('lastpostalt', $lastpostalt);
				$grouplist .= $templater->render();

			}
		}

		if ($category_name AND empty($filters) OR ((sizeof($filters) == 1 AND isset($filters['category']))))
		{
			$navphrase = $category_name;
		}

		$pagevars = array('do' => $doaction);
		if ($perpage != $vbulletin->options['sg_perpage'])
		{
			$pagevars['pp'] = $perpage;
		}

		if ($vbulletin->GPC['dofilter'])
		{
			$pagevars['dofilter'] = 1;
			$filters = array('filtertext', 'memberlimit', 'memberless', 'messagelimit', 'messageless',
				'picturelimit', 'pictureless', 'filter_date_gteq', 'filter_date_lteq', 'cat');

			foreach ($filters AS $filter)
			{
				if ($vbulletin->GPC[$filter])
				{
					$pagevars[$filter] = $vbulletin->GPC[$filter];
				}
			}
			unset($filters);
		}

		$pagevars['sort'] = $sortfield;
		if ($vbulletin->GPC['sortorder'])
		{
			$pagevars['order'] = $vbulletin->GPC['sortorder'];
		}

		$pagenav = construct_page_nav($pagenumber, $perpage, $totalgroups,
			'', '', '', 'grouphome', array(), $pagevars
		);

		$oppositesort = ($vbulletin->GPC['sortorder'] == 'asc' ? 'desc' : 'asc');

		$orderlinks = array();
		$sorts = array('name', 'category', 'members', 'discussions', 'messages', 'pictures', 'lastpost');
		foreach($sorts AS $sort)
		{
			unset($pagevars['order']);
			$pagevars['sort'] = $sort;
			if ($sortfield == $sort)
			{
				$pagevars['order'] = $oppositesort;
			}
			$orderlinks[$sort] = fetch_seo_url('grouphome', array(), $pagevars);
		}
		unset($sorts);

		$templater = vB_Template::create('forumdisplay_sortarrow');
			$templater->register('oppositesort', $oppositesort);
		$sortarrow["$sortfield"] = $templater->render();

		$show['creategroup'] = ($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['cancreategroups']);

		$navbits = array();
		$navbits[fetch_seo_url('grouphome', array())] = $vbphrase['social_groups'];

		if (!$navphrase)
		{
			$navphrase = (empty($filters) ? $vbphrase['all_groups'] : $vbphrase['search_results']);
		}

		$navbits[''] = $navphrase;
		$page_templater = vB_Template::create('SOCIALGROUPS');
		$page_templater->register('contenttypeid', $contenttypeid);
		$page_templater->register('category_name', $category_name);
		$page_templater->register('filters', $filters);
		$page_templater->register('grouplist', $grouplist);
		$page_templater->register('grouplisttitle', $grouplisttitle);
		$page_templater->register('orderlinks', $orderlinks);
		$page_templater->register('pagenav', $pagenav);
		$page_templater->register('sortarrow', $sortarrow);
		$page_templater->register('start', vb_number_format(($pagenumber - 1) * $perpage + 1));
		$page_templater->register('end', vb_number_format(($pagenumber * $perpage) > $totalgroups ? $totalgroups : ($pagenumber * $perpage)));
		$page_templater->register('total', vb_number_format($totalgroups));
		if ($vbulletin->GPC_exists['cat'])
		{
			$page_templater->register('messagegroupid', $vbulletin->GPC['cat']);
		}
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'overview')
{
	$show['sgicons'] = $vbulletin->options['sg_enablesocialgroupicons'];

	($hook = vBulletinHook::fetch_hook('group_prepareinfo')) ? eval($hook) : false;

	$vbulletin->input->clean_array_gpc('r', array(
		'owngrouppage'     => TYPE_UINT
	));

	$show['messageinfo'] = ($vbulletin->options['socnet_groups_msg_enabled'] ? true : false);
	$show['pictureinfo'] = ($vbulletin->options['socnet_groups_pictures_enabled']);
	$show['creategrouplink'] = ($permissions['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['cancreategroups']);

	// Get categories
	$categories = fetch_socialgroup_category_cloud();

	$categorybits = '';
	foreach ($categories AS $category)
	{
		$templater = vB_Template::create('socialgroups_category_cloud_bit');
			$templater->register('category', $category);
		$categorybits .= $templater->render();
	}

	// Get newest groups
	$newgroups = fetch_socialgroup_newest_groups(true, true, false);
	$newgroupbits = '';
	foreach ($newgroups AS $group)
	{
		$group = prepare_socialgroup($group);
		$group['canjoin'] = can_join_group($group);
		$group['canleave'] = can_leave_group($group);
		($hook = vBulletinHook::fetch_hook('group_newgroup_bit')) ? eval($hook) : false;

		$templater = vB_Template::create('socialgroups_newgroup_bit');

		$templater->register('group', $group);
		$templater->register('template_hook', $template_hook);
		$newgroupbits .= $templater->render();
	}
	unset($newgroups);

	// Get recently updated groups
	$updatedgroups = fetch_socialgroups_updatedgroups();

	if ($vbulletin->userinfo['userid'])
	{
		// Display groups the current user is in
		$mygroups = fetch_socialgroups_mygroups(true);
	}
	else
	{
		// Random groups
		$mygroups = fetch_socialgroup_random_groups();
	}

	// Get groupids to be displayed
	$groupids = array();
	if (!empty($updatedgroups))
	{
		foreach ((array)$updatedgroups as $group)
		{
			$groupids[] = $group['groupid'];
		}
	}
	if (!empty($mygroups))
	{
		foreach ((array)$mygroups as $group)
		{
			$groupids[] = $group['groupid'];
		}
	}

	// Cache group members info
	cache_group_members(array_unique($groupids));
	unset($groupids);

	$updatedgroupbits = '';
	foreach ($updatedgroups AS $group)
	{
		$group = prepare_socialgroup($group, true);
		$group['canjoin'] = can_join_group($group);
		$group['canleave'] = can_leave_group($group);

		$templater = vB_Template::create('socialgroups_updatedgroups_bit');

		$templater->register('group', $group);
		$templater->register('template_hook', $template_hook);
		$updatedgroupbits .= $templater->render();
	}
	unset($updatedgroups);

	// Render bits
	$mygroup_bits = '';
	foreach ($mygroups AS $mygroup)
	{
		$mygroup = prepare_socialgroup($mygroup, true);
		$mygroup['canleave'] = can_leave_group($mygroup);

		($hook = vBulletinHook::fetch_hook('group_list_mygroupsbit')) ? eval($hook) : false;

		$templater = vB_Template::create('socialgroups_mygroups_bit');

		$templater->register('mygroup', $mygroup);
		$templater->register('template_hook', $template_hook);
		$mygroup_bits .= $templater->render();
	}
	unset($newgroups);

	($hook = vBulletinHook::fetch_hook('group_overview')) ? eval($hook) : false;

	$navbits[''] = $vbphrase['social_groups'];

	$show['creategroup'] = ($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['cancreategroups']);

	$page_templater = vB_Template::create('socialgroups_overview');
	$page_templater->register('contenttypeid', $contenttypeid);
	$page_templater->register('categorybits', $categorybits);
	$page_templater->register('filters', $filters);
	$page_templater->register('mygroup_bits', $mygroup_bits);
	$page_templater->register('newgroupbits', $newgroupbits);
	$page_templater->register('updatedgroupbits', $updatedgroupbits);
	$page_templater->register('owngroup', $owngroup);
	$page_templater->register('randomgroup', $randomgroup);
	$page_templater->register('template_hook', $template_hook);
}

// #######################################################################
if ($_REQUEST['do'] == 'categorylist')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'       => TYPE_UINT,
		'pagenumber'    => TYPE_UINT,
		'sort'          => TYPE_NOHTML,
		'order'         => TYPE_NOHTML
	));

	$sortfield = $vbulletin->GPC['sort'];
	$pagenumber = $vbulletin->GPC['pagenumber'];

	if ('groups' == $sortfield)
	{
		$desc = ('asc' == $vbulletin->GPC['order'] ? false : true);
	}
	else
	{
		$desc = ('desc' == $vbulletin->GPC['order'] ? true : false);
	}

	require_once(DIR . '/includes/class_groupmessage.php');

	// Navbits for breadcrumb
	$navbits = array(
			fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
			'' => $vbphrase['categories']
	);

	($hook = vBulletinHook::fetch_hook('group_view_categories_start')) ? eval($hook) : false;

	// Items to display per page
	$perpage = (!$vbulletin->GPC['perpage'] OR $vbulletin->GPC['perpage'] > $vbulletin->options['sg_maxperpage']) ? $vbulletin->options['sg_perpage'] : $vbulletin->GPC['perpage'];

	// Create category collection
	$collection_factory = new vB_Collection_Factory($vbulletin);
	$collection = $collection_factory->create('groupcategory', false, $vbulletin->GPC['pagenumber'], $perpage, $desc);
	$collection->filter_sort_field($sortfield);

	// Set counts for view
	list($start, $end, $shown, $total) = array_values($collection->fetch_counts());

	// Get actual resolved page number in case input was normalised
	$pagenumber = $show['pagenumber'] = $collection->fetch_pagenumber();
	$quantity = $collection->fetch_quantity();

	// Create bit factory
	$bitfactory = new vB_Bit_Factory($vbulletin, 'groupcategory');

	// Build bits for all categories
	$categorybits = '';
	while ($category = $collection->fetch_item())
	{
		$bit =& $bitfactory->create_instance($category);
		$categorybits .= $bit->construct();
	}
	unset($bitfactory, $bit, $collection_factory, $collection);

	$sortorder = ($desc ? 'desc' : 'asc');
	$oppositesort = ($vbulletin->GPC['order'] == 'asc' ? 'desc' : 'asc');

	$pagevars = array('do' => 'categorylist');
	if($perpage != $vbulletin->options['sg_perpage'])
	{
		$pagevars['pp'] = $perpage;
	}

	$pagevars['sort'] = 'title';
	$pagevars['order'] = ('title' == $sortfield ? $oppositesort : 'asc');
	$titlesort = fetch_seo_url('grouphome', array(), $pagevars);

	$pagevars['sort'] = 'groups';
	$pagevars['order'] = ('groups' == $sortfield ? $oppositesort : 'desc');
	$groupsort = fetch_seo_url('grouphome', array(), $pagevars);


	$templater = vB_Template::create('forumdisplay_sortarrow');
		$templater->register('oppositesort', $oppositesort);
	$sortarrow["$sortfield"] = $templater->render();

	// Construct page navigation
	$pagevars['sort'] = $sortfield;
	unset($pagevars['order']);
	if ($vbulletin->GPC['order'])
	{
		$pagevars['order'] = $sortorder;
	}
	$pagenav = construct_page_nav($pagenumber, $perpage, $total, '', '', '', 'grouphome', array(), $pagevars);
	unset($pagevars);


	// Set page template
	$page_templater = vB_Template::create('socialgroups_categorylist');
	$page_templater->register('contenttypeid', $contenttypeid);
	$page_templater->register('categorybits', $categorybits);
	$page_templater->register('groupsort', $groupsort);
	$page_templater->register('pagenav', $pagenav);
	$page_templater->register('sortarrow', $sortarrow);
	$page_templater->register('template_hook', $template_hook);
	$page_templater->register('titlesort', $titlesort);
	$page_templater->register('start', $start);
	$page_templater->register('end', $end);
	$page_templater->register('total', $total);
}

// #######################################################################
if ($_REQUEST['do'] == 'canceltransfer')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'confirm'     => TYPE_STR
	));

	if ($vbulletin->GPC['confirm'])
	{
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "socialgroup
			SET transferowner = 0
			WHERE groupid = $group[groupid]
		");

		$group['transferowner'] = 0;
		$_REQUEST['do'] = 'transfer';
	}
	else
	{
		$_REQUEST['do'] = 'view';
	}
}

// #######################################################################
if ($_POST['do'] == 'dotransfer' OR $_REQUEST['do'] == 'transfer')
{
	if (!fetch_socialgroup_modperm('cantransfergroup', $group))
	{
		print_no_permission();
	}
}

// #######################################################################
if ($_POST['do'] == 'dotransfer')
{
	$error = false;

	$vbulletin->input->clean_array_gpc('p', array(
		'targetusername'     => TYPE_STR
	));

	if ($vbulletin->GPC['targetusername'])
	{
		$targetuser = $vbulletin->db->query_first_slave("
			SELECT user.userid, COUNT(groupid) AS targetgroups
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "socialgroup AS socialgroup ON (socialgroup.creatoruserid = user.userid)
			WHERE username = '" . $vbulletin->db->escape_string($vbulletin->GPC['targetusername']) . "'
			GROUP BY user.userid
		");

		if (!$targetuser['userid'])
		{
			$error = $vbphrase['user_does_not_exist'];
		}
		else if ($targetuser['userid'] == $group['creatoruserid'])
		{
			$error = $vbphrase['user_already_owns_group'];
		}
		else
		{
			$targetgroups = $targetuser['targetgroups'];
			$targetuser = $targetuser['userid'];

			// check the target user has permission to run social groups
			$targetuserinfo = fetch_userinfo($targetuser);
			$targetperms = cache_permissions($targetuserinfo);

			if (!($targetperms['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['cancreategroups'])
				OR !($targetperms['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups']))
			{
				$error = $vbphrase['user_does_not_have_permission_to_own_social_groups'];
			}

			if ($targetperms['maximumsocialgroups'] AND ($targetgroups >= $targetperms['maximumsocialgroups']))
			{
				$error = $vbphrase['user_cannot_own_any_more_social_groups'];
			}
			unset($targetperms);
		}
	}
	else
	{
		$error = $vbphrase['user_does_not_exist'];
	}

	if ($error)
	{
		$_REQUEST['do'] = 'transfer';
		$targetuser = false;
	}
	else
	{
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "socialgroup
			SET transferowner = $targetuser
			WHERE groupid = $group[groupid]
		");

		// Send pm
		$pmdm = datamanager_init('PM', $vbulletin, ERRTYPE_ARRAY);
		$pmdm->set_info('is_automated', true); // implies overridequota
		$pmdm->set('fromuserid', $vbulletin->userinfo['userid']);
		$pmdm->set('fromusername', $vbulletin->userinfo['username']);
		$pmdm->setr('title', construct_phrase($vbphrase['pm_request_to_take_ownership_of_social_group_title'], fetch_word_wrapped_string(fetch_censored_text($group['name']))));
		$pmdm->set_recipients($targetuserinfo['username'], $vbulletin->userinfo['permissions'], 'cc');
		$pmdm->setr('message', construct_phrase($vbphrase['pm_request_to_take_ownership_of_social_group_message'],
			fetch_seo_url('group|bburl|js|nosession', $group),
			fetch_seo_url('group|bburl|js|nosession', $group, array('do' => 'accepttransfer')),
			fetch_word_wrapped_string(fetch_censored_text($group['name']))));
		$pmdm->set('dateline', TIMENOW);
		$pmdm->set('allowsmilie', 0);
		$pmdm->pre_save();
		$pmdm->save();

		$group['transferowner'] = $targetuser;
		exec_header_redirect(fetch_seo_url('group', $group));
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'transfer')
{
	if ($group['transferowner'])
	{
		$userinfo = fetch_userinfo($group['transferowner']);
		$targetuser = $userinfo['userid'];
	}

	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		'' => $vbphrase['transfer_group_ownership']
	);

	$page_templater = vB_Template::create('socialgroups_transfer');
	$page_templater->register('error', $error);
	$page_templater->register('group', $group);
	$page_templater->register('target_username', $target_username);
	$page_templater->register('url', $url);
	$page_templater->register('userinfo', $userinfo);
}

// #######################################################################
if ($_REQUEST['do'] == 'accepttransfer' OR $_POST['do'] == 'doaccepttransfer')
{
	if (!($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['cancreategroups']))
	{
		print_no_permission();
	}

	if($vbulletin->userinfo['userid'] != $group['transferowner'])
	{
		standard_error(fetch_error('invalid_transfer_request'));
	}
}

// #######################################################################
if ($_POST['do'] == 'doaccepttransfer')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'confirm' => TYPE_STR
	));

	if ($vbulletin->GPC['confirm'])
	{
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "socialgroup
			SET transferowner = 0,
				creatoruserid = " . $vbulletin->userinfo['userid'] . "
			WHERE groupid = $group[groupid]
		");

		$typeid = $vbulletin->activitystream['socialgroup_group']['typeid'];
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "activitystream
			SET userid = {$vbulletin->userinfo['userid']}
			WHERE
				typeid = {$typeid}
					AND
				contentid = {$group['groupid']}
		");


		$socialgroupmemberdm = datamanager_init('SocialGroupMember', $vbulletin);

		if (!empty($group['membertype']))
		{
			$socialgroupmemberdm->set_existing($vbulletin->db->query_first("
				SELECT * FROM " . TABLE_PREFIX . "socialgroupmember WHERE userid = " . $vbulletin->userinfo['userid'] . "  AND groupid = " . $group['groupid']
			));
		}

		$socialgroupmemberdm->set('userid', $vbulletin->userinfo['userid']);
		$socialgroupmemberdm->set('groupid', $group['groupid']);
		$socialgroupmemberdm->set('dateline', TIMENOW);
		$socialgroupmemberdm->set('type', 'member');

		$socialgroupmemberdm->save();
		unset($socialgroupmemberdm);

		// Send pm
		$pmdm =& datamanager_init('PM', $vbulletin, ERRTYPE_ARRAY);
		$pmdm->set_info('is_automated', true); // implies overridequota
		$pmdm->set('fromuserid', $vbulletin->userinfo['userid']);
		$pmdm->set('fromusername', $vbulletin->userinfo['username']);
		$pmdm->setr('title', construct_phrase($vbphrase['pm_request_to_take_ownership_of_social_group_accepted_title'], fetch_word_wrapped_string(fetch_censored_text($group['name']))));
		$pmdm->set_recipients($group['creatorusername'], $vbulletin->userinfo['permissions'], 'cc');
		$pmdm->setr('message', construct_phrase($vbphrase['pm_request_to_take_ownership_of_social_group_accepted_message'],
			$vbulletin->userinfo['username'], fetch_seo_url('group|bburl|nosession|js', $group),
			fetch_word_wrapped_string(fetch_censored_text($group['name']))));
		$pmdm->set('dateline', TIMENOW);
		$pmdm->set('allowsmilie', 0);
		$pmdm->pre_save();
		$pmdm->save();

		update_owner_pending_gm_count($group['creatoruserid']);
		update_owner_pending_gm_count($vbulletin->userinfo['userid']);

		$group['creatoruserid'] = $vbulletin->userinfo['userid'];
		$group['creatorusername'] = $vbulletin->userinfo['username'];

		exec_header_redirect(fetch_seo_url('group', $group));
	}
	else
	{
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "socialgroup
			SET transferowner = 0
			WHERE groupid = $group[groupid]
		");

		print_standard_redirect('group_ownership_request_rejected');
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'accepttransfer')
{
	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		'' => $vbphrase['transfer_group_ownership']
	);

	$url = $vbulletin->url;
	$page_templater = vB_Template::create('socialgroups_accepttransfer');
	$page_templater->register('group', $group);
	$page_templater->register('url', $url);
}

// #######################################################################
if ($_REQUEST['do'] == 'owngroup')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'owngrouppage'     => TYPE_STR,
		'ajax'             => TYPE_BOOL
	));

	if (!$vbulletin->GPC['ajax'])
	{
		$vbulletin->url = fetch_seo_url('grouphome', '');
		print_standard_redirect();
	}

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('response');

	$show['sgicons'] = $vbulletin->options['sg_enablesocialgroupicons'];

	if (!($owngroup = fetch_owner_socialgroup($vbulletin->userinfo['userid'], $vbulletin->GPC['owngrouppage'])))
	{
		// leave block unchanged
		$xml->add_tag('error', 1);
	}
	else
	{
		$owngroup = prepare_socialgroup($owngroup);
		$templater = vB_Template::create('socialgroups_owngroup_bit');
			$templater->register('owngroup', $owngroup);
			$templater->register('template_hook', $template_hook);
		$owngroup = $templater->render();
		$xml->add_tag('html', process_replacement_vars($owngroup));
	}

	$xml->close_group();
	$xml->print_xml(true);
}

// #######################################################################
if ($_REQUEST['do'] == 'leave' OR $_REQUEST['do'] == 'delete' OR $_REQUEST['do'] == 'join')
{
	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'leave')
{
	if (!can_leave_group($group))
	{
		if ($group['is_owner'])
		{
			standard_error(fetch_error('cannot_leave_group_if_owner'));
		}
		else
		{
			standard_error(fetch_error('invalidid', $vbphrase['social_group'], $vbulletin->options['contactuslink']));
		}
	}

	$confirmdo = 'doleave';
	$confirmaction = vB_Friendly_Url::fetchLibrary($vbulletin, 'group|nosession', $group, array('do' => $confirmdo));
	$confirmaction = $confirmaction->get_url(FRIENDLY_URL_OFF);

	if ($group['membertype'] == 'moderated')
	{
		$question_phrase = construct_phrase($vbphrase['confirm_cancel_join_group_x'], $group['name']);
		$title_phrase = $vbphrase['cancel_join_request_question'];
		$navphrase = $vbphrase['cancel_join_request'];
	}
	else if ($group['membertype'] == 'invited')
	{
		$question_phrase = construct_phrase($vbphrase['confirm_decline_join_group_x'], $group['name']);
		$title_phrase = $vbphrase['decline_join_invitation_question'];
		$navphrase = $vbphrase['decline_join_invitation'];
	}
	else
	{
		$question_phrase = construct_phrase($vbphrase['confirm_leave_group_x'], $group['name']);
		$title_phrase = $vbphrase['leave_group_question'];
		$navphrase = $vbphrase['leave_social_group'];
	}

	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		'' => $navphrase
	);

	$url = $vbulletin->url;

	$page_templater = vB_Template::create('socialgroups_confirm');
	$page_templater->register('confirmaction', $confirmaction);
	$page_templater->register('confirmdo', $confirmdo);
	$page_templater->register('extratext', $extratext);
	$page_templater->register('group', $group);
	$page_templater->register('pictureinfo', $pictureinfo);
	$page_templater->register('question_phrase', $question_phrase);
	$page_templater->register('title_phrase', $title_phrase);
	$page_templater->register('url', $url);
}

// #######################################################################
if ($_REQUEST['do'] == 'delete')
{
	if (!can_delete_group($group))
	{
		print_no_permission();
	}

	$question_phrase = construct_phrase($vbphrase['confirm_delete_group_x'], $group['name']);
	$title_phrase = $vbphrase['delete_group_question'];

	$confirmdo = 'dodelete';
	$confirmaction = vB_Friendly_Url::fetchLibrary($vbulletin, 'group|nosession', $group, array('do' => 'dodelete'));
	$confirmaction = $confirmaction->get_url(FRIENDLY_URL_OFF);

	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		'' => $vbphrase['delete_group']
	);

	$url = $vbulletin->url;
	$page_templater = vB_Template::create('socialgroups_confirm');
	$page_templater->register('confirmaction', $confirmaction);
	$page_templater->register('confirmdo', $confirmdo);
	$page_templater->register('extratext', $extratext);
	$page_templater->register('group', $group);
	$page_templater->register('pictureinfo', $pictureinfo);
	$page_templater->register('question_phrase', $question_phrase);
	$page_templater->register('title_phrase', $title_phrase);
	$page_templater->register('url', $url);
}

// #######################################################################
if ($_REQUEST['do'] == 'join')
{
	if (!can_join_group($group))
	{
		print_no_permission();
	}

	$confirmdo = 'dojoin';
	$question_phrase = construct_phrase($vbphrase['confirm_join_group_x'], $group['name']);
	$title_phrase = $vbphrase['join_group_question'];
	$confirmaction = vB_Friendly_Url::fetchLibrary($vbulletin, 'group|nosession', $group, array('do' => 'dojoin'));
	$confirmaction = $confirmaction->get_url(FRIENDLY_URL_OFF);

	$extratext = empty($vbphrase['join_' . $group['type'] . '_extratext']) ? '' : $vbphrase['join_' . $group['type'] . '_extratext'];

	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		'' => $vbphrase['join_group']
	);

	$url = $vbulletin->url;

	$page_templater = vB_Template::create('socialgroups_confirm');
	$page_templater->register('confirmaction', $confirmaction);
	$page_templater->register('confirmdo', $confirmdo);
	$page_templater->register('extratext', $extratext);
	$page_templater->register('group', $group);
	$page_templater->register('pictureinfo', $pictureinfo);
	$page_templater->register('question_phrase', $question_phrase);
	$page_templater->register('title_phrase', $title_phrase);
	$page_templater->register('url', $url);
}

// #######################################################################
if ($_POST['do'] == 'doleave' OR $_POST['do'] == 'dodelete' OR $_POST['do'] == 'dojoin')
{
	$vbulletin->input->clean_array_gpc('p', array(
 		'deny'  => TYPE_NOHTML
	));

	// You either clicked no or you're a guest
	if ($vbulletin->GPC['deny'])
	{
		print_standard_redirect('action_cancelled');
	}

	if ($vbulletin->userinfo['userid'] == 0)
	{
		print_no_permission();
	}

	if ($vbulletin->url == fetch_seo_url('forumhome|nosession', array()))
	{
		$vbulletin->url = fetch_seo_url('group', $group);
	}
}

// #######################################################################
if ($_POST['do'] == 'doleave')
{
	if (!can_leave_group($group))
	{
		if ($group['is_owner'])
		{
			standard_error(fetch_error('cannot_leave_group_if_owner'));
		}
		else
		{
			standard_error(fetch_error('invalidid', $vbphrase['social_group'], $vbulletin->options['contactuslink']));
		}
	}

	if (!empty($group['membertype']))
	{
		$currentmemberentry = $vbulletin->db->query_first("SELECT * FROM " . TABLE_PREFIX . "socialgroupmember WHERE userid = " . $vbulletin->userinfo['userid'] . " AND groupid = " . $vbulletin->GPC['groupid']);

		// remove us from the group if we're still in it
		if ($currentmemberentry)
		{
			$socialgroupmemberdm = datamanager_init('SocialGroupMember', $vbulletin);
			$socialgroupmemberdm->set_existing($currentmemberentry);
			$socialgroupmemberdm->delete();
			unset($socialgroupmemberdm);
		}
	}

	print_standard_redirect('successfully_left_group');
}

// #######################################################################
if ($_POST['do'] == 'dodelete')
{
	if (!can_delete_group($group))
	{
		print_no_permission();
	}

	$socialgroupdm = datamanager_init('SocialGroup', $vbulletin);
	$socialgroupdm->set_existing($group);
 	$socialgroupdm->delete();
 	unset($socialgroupdm);

 	if (!$group['is_owner'] AND can_moderate(0, 'candeletesocialgroups'))
	{
		require_once(DIR . '/includes/functions_log_error.php');
		log_moderator_action($group, 'social_group_x_deleted',
			array($group['name'])
		);
	}

	$vbulletin->url = fetch_seo_url('grouphome', array());
	print_standard_redirect('successfully_deleted_group');
}

// #######################################################################
if ($_POST['do'] == 'dojoin')
{
	if (!can_join_group($group))
	{
		print_no_permission();
	}

	$jointype = array(
		'public'     => 'member',
		'moderated'  => 'moderated',
		'inviteonly' => 'member'
	);

	$socialgroupmemberdm = datamanager_init('SocialGroupMember', $vbulletin);

	if (!empty($group['membertype']))
	{
		$socialgroupmemberdm->set_existing($vbulletin->db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "socialgroupmember WHERE userid=" . $vbulletin->userinfo['userid'] . "  AND groupid = " . $group['groupid']
		));
	}

	$socialgroupmemberdm->set('userid', $vbulletin->userinfo['userid']);
	$socialgroupmemberdm->set('groupid', $vbulletin->GPC['groupid']);
	$socialgroupmemberdm->set('dateline', TIMENOW);
	$socialgroupmemberdm->set('type', $jointype["$group[type]"]);

	($hook = vBulletinHook::fetch_hook('group_dojoin')) ? eval($hook) : false;

	$socialgroupmemberdm->save();
	unset($socialgroupmemberdm);

	print_standard_redirect('successfully_joined_group');
}

// ############# Do we need group owner info? ############################
if ($_REQUEST['do'] == 'edit' OR $_POST['do'] == 'doedit' OR $_REQUEST['do'] == 'create' OR $_POST['do'] == 'docreate')
{
	if (!empty($group))
	{
		$groupowner = fetch_userinfo($group['creatoruserid']);
		cache_permissions($groupowner);
	}
	else
	{
		$groupowner = $vbulletin->userinfo;
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'create' OR $_REQUEST['do'] == 'docreate')
{
	if (!($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['cancreategroups']))
	{
		print_no_permission();
	}

	if ($vbulletin->userinfo['permissions']['maximumsocialgroups'])
	{
		// fetch the number of groups the user has already created
		$result = $vbulletin->db->query_first(
			"SELECT COUNT(groupid) AS total
			 FROM " . TABLE_PREFIX . "socialgroup
			 WHERE creatoruserid = " . $vbulletin->userinfo['userid']
		);

		if ($result['total'] >= $vbulletin->userinfo['permissions']['maximumsocialgroups'])
		{
			standard_error(fetch_error((fetch_socialgroup_perm('candeleteowngroups') ? 'you_can_only_create_x_groups_delete' : 'you_can_only_create_x_groups'), $vbulletin->userinfo['permissions']['maximumsocialgroups']));
		}
	}
}

// #######################################################################
if ($_POST['do'] == 'docreate')
{
	if (!($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['cancreategroups']))
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
 		'groupname'        => TYPE_NOHTML,
		'socialgroupcategoryid' => TYPE_UINT,
 		'groupdescription' => TYPE_NOHTML,
 		'grouptype'        => TYPE_STR,
 		'options'          => TYPE_ARRAY_BOOL,
	));

	$groupdm = datamanager_init('SocialGroup', $vbulletin, ERRTYPE_STANDARD);

	$groupdm->set('name', $vbulletin->GPC['groupname']);
	$groupdm->set('description', $vbulletin->GPC['groupdescription']);
	$groupdm->set('creatoruserid', $vbulletin->userinfo['userid']);
	$groupdm->set('type', $vbulletin->GPC['grouptype']);

	if (!$vbulletin->GPC['socialgroupcategoryid'])
	{
		$groupdm->error('must_select_a_category');
	}

	$groupdm->set('socialgroupcategoryid', $vbulletin->GPC['socialgroupcategoryid']);

	foreach (array_keys($vbulletin->bf_misc_socialgroupoptions) AS $key)
	{
		switch ($key)
		{
			case 'owner_mod_queue':
			{
				$permcheck = (
					$vbulletin->options['sg_allow_owner_mod_queue']
					AND !$vbulletin->options['social_moderation']
					AND $groupowner['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canmanageowngroups']
					AND $vbulletin->options['socnet_groups_msg_enabled']
				) ? true : false;
			}
			break;

			case 'join_to_view':
			{
				$permcheck = (
					$vbulletin->options['sg_allow_join_to_view']
					AND (
						$vbulletin->options['socnet_groups_msg_enabled']
						OR $vbulletin->options['socnet_groups_pictures_enabled']
					)
				) ? true : false;
			}
			break;

			case 'enable_group_messages':
			{
				$permcheck = $vbulletin->options['socnet_groups_msg_enabled'] ? true : false;
			}
			break;

			case 'enable_group_albums':
			{
				$permcheck = $vbulletin->options['socnet_groups_pictures_enabled'] ? true : false;
			}
			break;

			case 'only_owner_discussions':
			{
				$permcheck = (
					$vbulletin->options['sg_enable_owner_only_discussions']
					AND fetch_socialgroup_perm('canlimitdiscussion')
					) ? true: false;
			}
			break;

			default:
			{
				$permcheck = false;
			}
		}

		$value = $permcheck ? isset($vbulletin->GPC['options']["$key"]) : false;

		$groupdm->set_bitfield('options', $key, $value);
	}

	($hook = vBulletinHook::fetch_hook('group_docreate')) ? eval($hook) : false;

	$group = array('groupid' => $groupdm->save());
	unset($groupdm);

	if ($vbulletin->options['sg_enablesocialgroupicons'] AND fetch_socialgroup_perm('canuploadgroupicon'))
	{
		$_REQUEST['do'] = 'edit';
		$icononly = true;
	}
	else
	{
		$vbulletin->url = fetch_seo_url('group', $group);
		print_standard_redirect('successfully_created_group');
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'create' OR $_REQUEST['do'] == 'edit')
{
	if (!$icononly)
	{
		switch($_REQUEST['do'])
		{
			case 'create':
			{
				$phrase =  $vbphrase['create_group'];
				$action = 'docreate';

				$checked['enable_group_messages'] = ' checked="checked"';
				$checked['enable_group_albums'] = ' checked="checked"';
			}
			break;
			case 'edit':
			{
				if (!can_edit_group($group))
				{
					print_no_permission();
				}

				$typeselected["$group[type]"] = ' selected="selected"';

				$phrase =  $vbphrase['edit_group'];
				$action = 'doedit';

				$checked['enable_group_messages'] = ($group['options'] & $vbulletin->bf_misc_socialgroupoptions['enable_group_messages']) ? ' checked="checked"' : '';
				$checked['enable_group_albums'] = ($group['options'] & $vbulletin->bf_misc_socialgroupoptions['enable_group_albums']) ? ' checked="checked"' : '';
				$checked['mod_queue'] = ($group['options'] & $vbulletin->bf_misc_socialgroupoptions['owner_mod_queue']) ? ' checked="checked"' : '';
				$checked['join_to_view'] = ($group['options'] & $vbulletin->bf_misc_socialgroupoptions['join_to_view']) ? ' checked="checked"' : '';
				$checked['only_owner_discussions'] = ($group['options'] & $vbulletin->bf_misc_socialgroupoptions['only_owner_discussions']) ? ' checked="checked"' : '';
			}
			break;
		}

		// get category options
		$categories = fetch_socialgroup_category_options();
		$categoryoptions = '';

		foreach ($categories as $categoryid => $category)
		{
			$optiontitle = $category['title'];
			$optionvalue = $categoryid;
			$optionselected = ($categoryid == $group['socialgroupcategoryid'] ? ' selected="selected"' : '');

			$categoryoptions .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
		}
		unset($categories);

		if ($_REQUEST['do'] == 'edit')
		{
			$show['title'] = (can_moderate(0, 'caneditsocialgroups') OR $group['members'] <= 1);
		}
		else
		{
			$show['title'] = true;
		}

		$show['mod_queue'] = (
			$vbulletin->options['sg_allow_owner_mod_queue']
			AND !$vbulletin->options['social_moderation']
			AND $groupowner['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canmanageowngroups']
			AND $vbulletin->options['socnet_groups_msg_enabled']
		);

		$show['join_to_view'] = (
			$vbulletin->options['sg_allow_join_to_view']
			AND (
				$vbulletin->options['socnet_groups_msg_enabled']
				OR $vbulletin->options['socnet_groups_pictures_enabled']
			)
		);

		$show['enable_group_messages'] = $vbulletin->options['socnet_groups_msg_enabled'];
		$show['enable_group_albums'] = $vbulletin->options['socnet_groups_pictures_enabled'];
		$show['only_owner_discussions'] = (
			$vbulletin->options['sg_enable_owner_only_discussions']
			AND $groupowner['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canlimitdiscussion']
			);

		if (!$show['only_owner_discussions'])
		{
			$checked['only_owner_discussions'] = (bool)($group['options'] & $vbulletin->bf_misc_socialgroupoptions['only_owner_discussions']);
		}

		$show['options'] = ($show['mod_queue'] OR $show['join_to_view'] OR $show['enable_group_albums'] OR $show['enable_group_messages'] OR $show['only_owner_discussions']);
	}
	else
	{
		$phrase = $vbphrase['create_group'];
	}

	// edit icon
	if (($_REQUEST['do'] == 'edit') AND $vbulletin->options['sg_enablesocialgroupicons'] AND fetch_socialgroup_perm('canuploadgroupicon'))
	{
		$show['editicon'] = true;
		$show['deleteicon'] = (bool)$group['icondateline'];

		($hook = vBulletinHook::fetch_hook('group_edit_groupicon')) ? eval($hook) : false;

		$groupiconurl = fetch_socialgroupicon_url($group, true);

		if ($permissions['groupiconmaxsize'])
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x_y_or_z'], FIXED_SIZE_GROUP_ICON_WIDTH, FIXED_SIZE_GROUP_ICON_HEIGHT, (($permissions['groupiconmaxsize'] / 1000) . $vbphrase['kilobytes']));
		}
		else
		{
			$maxnote = construct_phrase($vbphrase['note_maximum_size_x_y_pixels'], FIXED_SIZE_GROUP_ICON_WIDTH, FIXED_SIZE_GROUP_ICON_HEIGHT);
		}

		$show['url_option'] = (ini_get('allow_url_fopen') != 0 OR function_exists('curl_init'));
	}

	// navbits
	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
	);

	if ($_REQUEST['do'] == 'edit')
	{
		$navbits[fetch_seo_url('groupcategory', $group)] = $group['categoryname'];
		$navbits[fetch_seo_url('group', $group)] = $group['name'];
	}

 	$navbits[''] = $phrase;
	$url = $vbulletin->url;

	($hook = vBulletinHook::fetch_hook('group_create_edit')) ? eval($hook) : false;

	$page_templater = vB_Template::create('socialgroups_form');
	$page_templater->register('action', $action);
	$page_templater->register('categoryoptions', $categoryoptions);
	$page_templater->register('checked', $checked);
	$page_templater->register('group', $group);
	$page_templater->register('groupiconurl', $groupiconurl);
	$page_templater->register('icononly', $icononly);
	$page_templater->register('inimaxattach', $inimaxattach);
	$page_templater->register('maxnote', $maxnote);
	$page_templater->register('phrase', $phrase);
	$page_templater->register('typeselected', $typeselected);
	$page_templater->register('url', $url);
}

// #######################################################################
if ($_POST['do'] == 'doedit')
{
	$vbulletin->input->clean_array_gpc('p', array(
 		'groupname'        => TYPE_NOHTML,
 		'groupdescription' => TYPE_NOHTML,
 		'grouptype'        => TYPE_STR,
		'socialgroupcategoryid' => TYPE_UINT,
 		'options'          => TYPE_ARRAY_BOOL,
	));

	if (!can_edit_group($group))
	{
		print_no_permission();
	}

	$groupdm = datamanager_init('SocialGroup', $vbulletin, ERRTYPE_STANDARD);

	$groupdm->set_existing($group);
	if (can_moderate(0, 'caneditsocialgroups') OR $group['members'] == 1)
	{
		$groupdm->set('name', $vbulletin->GPC['groupname']);
	}
	$groupdm->set('description', $vbulletin->GPC['groupdescription']);
	$groupdm->set('type', $vbulletin->GPC['grouptype']);

	if (!$vbulletin->GPC['socialgroupcategoryid'])
	{
		$groupdm->error('must_select_a_category');
	}

	$groupdm->set('socialgroupcategoryid', $vbulletin->GPC['socialgroupcategoryid']);

	foreach (array_keys($vbulletin->bf_misc_socialgroupoptions) AS $key)
	{
		switch ($key)
		{
			case 'owner_mod_queue':
			{
				$permcheck = (
					$vbulletin->options['sg_allow_owner_mod_queue']
					AND !$vbulletin->options['social_moderation']
					AND $groupowner['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canmanageowngroups']
					AND $vbulletin->options['socnet_groups_msg_enabled']
				) ? true : false;
			}
			break;

			case 'join_to_view':
			{
				$permcheck = (
					$vbulletin->options['sg_allow_join_to_view']
					AND (
						$vbulletin->options['socnet_groups_msg_enabled']
						OR $vbulletin->options['socnet_groups_pictures_enabled']
					)
				) ? true : false;
			}
			break;

			case 'enable_group_messages':
			{
				$permcheck = $vbulletin->options['socnet_groups_msg_enabled'] ? true : false;
			}
			break;

			case 'enable_group_albums':
			{
				$permcheck = $vbulletin->options['socnet_groups_pictures_enabled'] ? true : false;
			}
			break;

			case 'only_owner_discussions':
			{
				$permcheck = (
					fetch_socialgroup_perm('canlimitdiscussion')
					) ? true: false;
			}
			break;

			default:
			{
				$permcheck = false;
			}
		}

		$value = $permcheck ? isset($vbulletin->GPC['options']["$key"]) : false;

		$groupdm->set_bitfield('options', $key, $value);
	}

	($hook = vBulletinHook::fetch_hook('group_doedit')) ? eval($hook) : false;

	$groupdm->save();
	unset($groupdm);

	if (!$group['is_owner'] AND can_moderate(0, 'caneditsocialgroups'))
	{
		require_once(DIR . '/includes/functions_log_error.php');
		log_moderator_action($group, 'social_group_x_edited',
			array($group['name'])
		);
	}

	print_standard_redirect('successfully_edited_group');
}

// #######################################################################
if ($_REQUEST['do'] == 'markread')
{
	$vbulletin->input->clean_array_gpc('p', array(
			'ajax'             => TYPE_BOOL
	));

	if ($discussion['discussionid'])
	{
		exec_sg_mark_as_read('discussion', $discussion['discussionid']);
	}
	else if ($group['groupid'])
	{
		exec_sg_mark_as_read('group', $group['groupid']);
	}

	if ($vbulletin->GPC['ajax'])
	{
		//print some xml
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_tag('success', '1');
		$xml->print_xml();
	}
	else
	{
		$_REQUEST['do'] = 'view';
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'subscribe')
{
	if (!$group['groupid'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['social_group'], $vbulletin->options['contactuslink']));
	}

	if (!$vbulletin->userinfo['userid'] OR !$group['canviewcontent'])
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
			'emailupdate'			  => TYPE_STR
	));

	if (empty($_POST['do']))
	{
		if ($discussion['discussionid'])
		{
			$navbits = array(
				fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
				fetch_seo_url('groupcategory', $group) => $group['categoryname'],
				fetch_seo_url('group', $group) => $group['name'],
				fetch_seo_url('groupdiscussion', $discussion) => $discussion['title'],
				'' => $vbphrase['subscribe_to_discussion']
			);

			$templatename = 'socialgroups_subscribe';
		}
		else
		{
			$navbits = array(
				fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
				fetch_seo_url('groupcategory', $group) => $group['categoryname'],
				fetch_seo_url('group', $group) => $group['name'],
				'' => $vbphrase['subscriptions']
			);

			$templatename = 'socialgroups_subscribe_group';
		}


		$page_templater = vB_Template::create($templatename);
		$page_templater->register('discussion', $discussion);
		$page_templater->register('emailselected', $emailselected);
		$page_templater->register('group', $group);
	}
	else
	{
		if ($discussion['discussionid'])
		{
			$emailupdate = ($vbulletin->GPC['emailupdate'] ? 1 : 0);

			/*insert query*/
			$vbulletin->db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "subscribediscussion (userid, discussionid, emailupdate)
				VALUES (" . $vbulletin->userinfo['userid'] . ", " . $discussion['discussionid'] . ", $emailupdate)
			");

			// mark discussion as having subscribers
			$vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "discussion AS discussion
				SET subscribers = '1'
				WHERE discussion.discussionid = " . $discussion['discussionid']
			);

			$vbulletin->url = fetch_seo_url('groupdiscussion', $discussion);
			print_standard_redirect('redirect_subsadd_discussion', true, true);
		}
		else
		{
			switch ($vbulletin->GPC['emailupdate'])
			{
				case 'weekly':
				case 'daily':
					$emailupdate = $vbulletin->GPC['emailupdate'];
					break;

				case 'none':
				default:
					$emailupdate = 'none';
			}

			/*insert query*/
			$db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "subscribegroup (userid, groupid, emailupdate)
				VALUES (" . $vbulletin->userinfo['userid'] . ", " . $group['groupid'] . ", '" . $db->escape_string($emailupdate) . "')
			");

			$vbulletin->url = fetch_seo_url('group', $group);
			print_standard_redirect('redirect_subsadd_group', true, true);
		}
	}
}


// #######################################################################
if ($_REQUEST['do'] == 'unsubscribe')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'auth'           => TYPE_STR,
		'subscriptionid' => TYPE_UINT
	));

	// check link in email
	if ($vbulletin->GPC['subscriptionid'])
	{
		$table = TABLE_PREFIX . 'subscribediscussion';

		if ($subscription = $db->query_first_slave("
				SELECT subscribediscussionid, discussionid
				FROM $table
				INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = $table.userid)
				WHERE $table.subscribediscussionid = " . $vbulletin->GPC['subscriptionid'] . "
					AND MD5(CONCAT(user.userid, $table.subscribediscussionid, user.salt, '" . COOKIE_SALT . "')) = '" . $db->escape_string($vbulletin->GPC['auth']) . "'
			"))
		{
			$db->query_write("
				DELETE FROM $table
				WHERE $table.subscribediscussionid = " . $vbulletin->GPC['subscriptionid'] . "
			");

			$vbulletin->url = fetch_seo_url('groupdiscussion', $discussion);
			print_standard_redirect('redirect_subsremove_discussion', true, true);
		}
	}

	// check group
	if (!$group['groupid'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['social_group'], $vbulletin->options['contactuslink']));
	}

	if ($discussion['discussionid'])
	{
		$table = TABLE_PREFIX . 'subscribediscussion';
		$vbulletin->db->query_write("
			DELETE FROM $table
			WHERE $table.userid = ". $vbulletin->userinfo['userid'] . "
			AND $table.discussionid = " . $discussion['discussionid']
		);

		$vbulletin->url = fetch_seo_url('groupdiscussion', $discussion);
		print_standard_redirect('redirect_subsremove_discussion', true, true);
	}
	else
	{
		$table = TABLE_PREFIX . 'subscribegroup';
		$vbulletin->db->query_write("
			DELETE FROM $table
			WHERE $table.userid = ". $vbulletin->userinfo['userid'] . "
			AND $table.groupid = " . $group['groupid']
		);

		$vbulletin->url = fetch_seo_url('group', $group);
		print_standard_redirect('redirect_subsremove_group', true, true);
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'view' OR $_REQUEST['do'] == 'discuss' OR $_REQUEST['do'] == 'message')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'     => TYPE_UINT,
		'pagenumber'  => TYPE_UINT,
		'showignored' => TYPE_BOOL,
		'searchtext'  => TYPE_NOHTML
	));

	$grouptypephrase = $vbphrase['group_desc_' . $group['type']];

	// Get group options
	$show['groupoptions'] = false;
	$groupoptions = fetch_groupoptions($group, $show['groupoptions']);
}

// #######################################################################
if ($_REQUEST['do'] == 'view')
{
	$show['groupinfo'] = (!$vbulletin->GPC['pagenumber']
						  AND !$vbulletin->GPC['perpage']
						  AND !$vbulletin->GPC['searchtext']);

	if ($show['groupinfo'])
	{
		// first page -- main group page
		$navbits = array(
			fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
			fetch_seo_url('groupcategory', $group) => $group['categoryname'],
			'' => $group['name']
		);
	}
	else
	{
		$navbits = array(
			fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
			fetch_seo_url('groupcategory', $group) => $group['categoryname'],
			fetch_seo_url('group', $group) => $group['name'],
			'' => $vbphrase['group_discussions']
		);
	}

	if ($show['groupinfo'])
	{
		// Show group icon
		$groupiconurl = fetch_socialgroupicon_url($group);

		// Show members
		$groupmemberids = $vbulletin->db->query_read_slave("
			SELECT userfield.*, usertextfield.*, user.*, UNIX_TIMESTAMP(passworddate) AS passworddate,
				IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid
				" . ($vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width_thumb AS avwidth_thumb, customavatar.height_thumb AS avheight_thumb, customavatar.width as avwidth, customavatar.height as avheight, customavatar.filedata_thumb' : '') .
				', customprofilepic.userid AS profilepic, customprofilepic.dateline AS profilepicdateline, customprofilepic.width AS ppwidth, customprofilepic.height AS ppheight' .
				", user.icq AS icq, user.aim AS aim, user.yahoo AS yahoo, user.msn AS msn, user.skype AS skype
			FROM " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = socialgroupmember.userid)
			LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (user.userid = userfield.userid)
			LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON (usertextfield.userid = user.userid)
			" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid) " : '') .
			"LEFT JOIN " . TABLE_PREFIX . "customprofilepic AS customprofilepic ON (user.userid = customprofilepic.userid) " .
			"WHERE socialgroupmember.groupid = " . $group['groupid'] . " AND socialgroupmember.type = 'member'
			ORDER BY user.lastactivity DESC
			LIMIT 10
		");
		$members_shown = $vbulletin->db->num_rows($groupmemberids);

		while ($groupmember = $vbulletin->db->fetch_array($groupmemberids))
		{
			$width = 0;
			$height = 0;

			fetch_avatar_from_userinfo($groupmember, true);
			fetch_musername($groupmember);
			$user = $groupmember;

			($hook = vBulletinHook::fetch_hook('group_memberbit')) ? eval($hook) : false;

			$templater = vB_Template::create('socialgroups_memberbit');
				$templater->register('user', $user);
			$short_member_list_bits .= $templater->render();
		}
		$vbulletin->db->free_result($groupmemberids);

		// find recent pictures
		$show['pictures_block'] = (
			$group['canviewcontent']
			AND $group['options'] & $vbulletin->bf_misc_socialgroupoptions['enable_group_albums']
			AND $vbulletin->options['socnet_groups_pictures_enabled']
		);

		($hook = vBulletinHook::fetch_hook('group_view_pictures_start')) ? eval($hook) : false;

		if ($show['pictures_block'])
		{
			$hook_query_fields = $hook_query_joins = $hook_query_where = '';
			($hook = vBulletinHook::fetch_hook('group_pictures_query')) ? eval($hook) : false;

			$pictures_sql = $db->query_read_slave("
				SELECT
					a.attachmentid, a.userid, a.caption, a.dateline,
					fd.filesize, fd.thumbnail_filesize, fd.thumbnail_dateline, fd.thumbnail_width, fd.thumbnail_height, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail,
					user.username
					$hook_query_fields
				FROM " . TABLE_PREFIX . "attachment AS a
				INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
				INNER JOIN " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember ON
					(socialgroupmember.userid = a.userid AND socialgroupmember.groupid = $group[groupid] AND socialgroupmember.type = 'member')
				LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = a.userid)
				$hook_query_joins
				WHERE
					a.contentid = $group[groupid]
						AND
					a.contenttypeid = $contenttypeid
					$hook_query_where
				ORDER BY a.dateline DESC
				LIMIT 5
			");

			$pictures_shown = vb_number_format($db->num_rows($pictures_sql));
			$picturebits = '';
			$index = 1;
			while ($picture = $db->fetch_array($pictures_sql))
			{
				$picture = prepare_pictureinfo_thumb($picture, $group);
				$picture['index'] = $index++;

				($hook = vBulletinHook::fetch_hook('group_picturebit')) ? eval($hook) : false;

				$templater = vB_Template::create('socialgroups_picturebit');
					$templater->register('group', $group);
					$templater->register('picture', $picture);
					$templater->register('usercss', $usercss);
				$picturebits .= $templater->render();
			}
			$db->free_result($pictures_sql);
			$show['add_pictures_link'] = ($group['membertype'] == 'member' AND $vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canupload']);
			$show['pictures_block'] = ($show['add_pictures_link'] OR $picturebits OR $group['picturecount']);
		}
	}

	// Display discussions
	$show['groupdiscussions'] = $show['quickcomment'] = ($vbulletin->options['socnet_groups_msg_enabled'] AND $group['canviewcontent'] AND ($group['options'] & $vbulletin->bf_misc_socialgroupoptions['enable_group_messages']));

	($hook = vBulletinHook::fetch_hook('group_view_discussion_start')) ? eval($hook) : false;

	if ($show['groupdiscussions'])
	{
		// get sorting
		$vbulletin->input->clean_array_gpc('r', array(
			'sort'        => TYPE_NOHTML,
			'order'       => TYPE_NOHTML
		));
		$desc = ('asc' == $vbulletin->GPC['order']) ? false : true;
		$sortfield = $vbulletin->GPC['sort'];
		$searchtext = $vbulletin->GPC['searchtext'];

		// show auto moderation message
		$show['auto_moderation'] = fetch_group_auto_moderation($group);

		// show subscribe link
		$show['subscribe'] = (bool)$vbulletin->userinfo['userid'];

		// Items to display per page
		$perpage = (!$vbulletin->GPC['perpage'] OR $vbulletin->GPC['perpage'] > $vbulletin->options['sgd_maxperpage']) ? $vbulletin->options['sgd_perpage'] : $vbulletin->GPC['perpage'];

		// Create discussion collection
		require_once(DIR . '/includes/class_groupmessage.php');
		$collection_factory = new vB_Group_Collection_Factory($vbulletin, $group);
		$collection = $collection_factory->create('discussion', $group['groupid'], $vbulletin->GPC['pagenumber'], $perpage, $desc);

		$collection->filter_sort_field($sortfield);
		$collection->filter_searchtext($searchtext);

		if (!$vbulletin->options['threadmarking'] OR !$vbulletin->userinfo['userid'])
		{
			$collection->check_read(false);
		}

		// Set counts for view
		list($messagestart, $messageend, $messageshown, $messagetotal) = array_values($collection->fetch_counts());

		// Get actual resolved page number in case input was normalised
		$pagenumber = $collection->fetch_pagenumber();

		// Create bit factory
		$factory = new vB_Group_Bit_Factory($vbulletin, 'discussion');

		// Build message bits for all items
		$messagebits = '';
		while ($item = $collection->fetch_item())
		{
			$bit =& $factory->create($item, $group);
			$messagebits .= $bit->construct();
		}
		unset($factory, $bit);

		// Get last item's dateline
		$lastcomment = $collection->fetch_lastitem('dateline');
		unset($collection_factory, $collection);

		// Construct page navigation
		//		$pagenavbits = array("groupid={$group['groupid']}");
		$pagenavbits = array();
		if ($sortfield)
		{
			$pagenavbits['sort'] = urlencode($sortfield);
		}
		if ($perpage)
		{
			$pagenavbits['pp'] = $perpage;
		}
		if ($vbulletin->GPC['showignored'])
		{
			$pagenavbits['showignored'] = '1';
		}
		if (!$desc)
		{
			$pagenavbits['order'] = 'asc';
		}
		if ($searchtext)
		{
			$pagenavbits['searchtext'] = urlencode($searchtext);
		}
		$pagenav = construct_page_nav($pagenumber, $perpage, $messagetotal, '', '', '', 'group', $group, $pagenavbits);

		// Sort helpers -- doesn't look like this is used in the current template.  We updated it anyway.
		$oppositesort = ($desc ? 'asc' : 'desc');

		$orderlinks = array();
		$sorts = array('title', 'author', 'replies', 'dateline', 'lastpost');
		foreach ($sorts as $sort)
		{
			//the orginal code did not respect all of the pagenavbits particularly the
			//perpage (pp) and showignored parameters.  We add them because it makes sense
			//and is more consistant with practice elsewhere.
			$pagenavbits['sort'] = $sort;
		 	unset($pagenavbits['order']);
			if($sortfield == $sort)
			{
				$pagenavbits['order'] = $oppositesort;
			}
			$orderlinks[$sort] = fetch_seo_url('group', $group, $pagenavbits);
		}
		unset ($sorts);

		$templater = vB_Template::create('forumdisplay_sortarrow');
			$templater->register('oppositesort', $oppositesort);
		$sortarrow["$sortfield"] = $templater->render();

		// Inline moderation options
		show_group_inlinemoderation($group, $show, true);

		$messagecontenttypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupMessage');
	}

	$ownerlink = fetch_seo_url('member', $group, null, 'creatoruserid', 'creatorusername');

	$show['postlink'] = (can_post_new_discussion($group));

	$poststarttime = TIMENOW;
	$posthash = md5($poststarttime . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']);

	// Set page template
	$page_templater = vB_Template::create('socialgroups_group');
	$page_templater->register('group', $group);
	$page_templater->register('groupiconurl', $groupiconurl);
	$page_templater->register('groupoptions', $groupoptions);
	$page_templater->register('members_shown', $members_shown);
	$page_templater->register('messagebits', $messagebits);
	$page_templater->register('messageend', $messageend);
	$page_templater->register('messageshown', $messageshown);
	$page_templater->register('messagestart', $messagestart);
	$page_templater->register('messagetotal', $messagetotal);
	$page_templater->register('messagecontenttypeid', $messagecontenttypeid);
	$page_templater->register('orderlinks', $orderlinks);
	$page_templater->register('ownerlink', $ownerlink);
	$page_templater->register('pagenav', $pagenav);
	$page_templater->register('pagenumber', $pagenumber);
	$page_templater->register('perpage', $perpage);
	$page_templater->register('picturebits', $picturebits);
	$page_templater->register('pictures_shown', $pictures_shown);
	$page_templater->register('searchtext', $searchtext);
	$page_templater->register('short_member_list_bits', $short_member_list_bits);
	$page_templater->register('sortarrow', $sortarrow);
	$page_templater->register('template_hook', $template_hook);
	$page_templater->register('contenttypeid', $contenttypeid);
	$page_templater->register('posthash', $posthash);
	$page_templater->register('poststarttime', $poststarttime);
	$page_templater->register('values', "values[groupid]=$group[groupid]");
}

// #######################################################################
// View messages in a discussion
if ($_REQUEST['do'] == 'discuss')
{
	require_once(DIR . '/includes/class_groupmessage.php');

	// Check if user can view messages in this discussion
	if (empty($discussion) OR !$vbulletin->options['socnet_groups_msg_enabled'] OR !$group['canviewcontent'] OR
		!($group['options'] & $vbulletin->bf_misc_socialgroupoptions['enable_group_messages']))
	{
		standard_error(fetch_error('invalidid', $vbphrase['social_group_discussion'], $vbulletin->options['contactuslink']));
	}

	// Navbits for breadcrumb
	$navbits = array(
			fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
			fetch_seo_url('groupcategory', $group) => $group['categoryname'],
			fetch_seo_url('group', $group) => $group['name'],
			'' => $discussion['title']
	);

	($hook = vBulletinHook::fetch_hook('group_discuss_start')) ? eval($hook) : false;

	// Show auto moderation message
	$show['auto_moderation'] = fetch_group_auto_moderation($group);

	// Items to display per page
	$perpage = (!$vbulletin->GPC['perpage'] OR $vbulletin->GPC['perpage'] > $vbulletin->options['gm_maxperpage']) ? $vbulletin->options['gm_perpage'] : $vbulletin->GPC['perpage'];

	// Create message collection
	$collection_factory = new vB_Group_Collection_Factory($vbulletin, $group);
	$collection = $collection_factory->create('message', $discussion['discussionid'], $vbulletin->GPC['pagenumber'], $perpage, false);

	// If a message is specified, seek to the results page with the message
	if ($messageinfo['gmid'])
	{
		$collection->seek_item($goto = $messageinfo['dateline']);
	}
	else if ($goto = $vbulletin->input->clean_gpc('r', 'goto', TYPE_UINT))
	{
		$collection->seek_item($goto);
	}

	// Set counts for view
	list($messagestart, $messageend, $messageshown, $messagetotal) = array_values($collection->fetch_counts());

	// Get actual resolved page number in case input was normalised
	$pagenumber = $show['pagenumber'] = $collection->fetch_pagenumber();
	$quantity = $collection->fetch_quantity();

	// Create bit factory
	$bitfactory = new vB_Group_Bit_Factory($vbulletin, 'message');

	// Get first item
	$firstrecord =& $collection->fetch_firstitem();

	// Build message bits for all items
	$messagebits = '';
	while ($item = $collection->fetch_item())
	{
		if ($goto)
		{
			// mark first item after requested dateline
			if ($item['dateline'] >= $goto)
			{
				$item['goto'] = true;
				$goto = false;
			}
		}

		$bit =& $bitfactory->create($item, $group);
		$messagebits .= $bit->construct();
	}
	unset($bitfactory, $bit);

	// Get last item's dateline
	$lastcomment = $collection->fetch_lastitem('dateline');

	unset($collection_factory, $collection);

	// Only allow AJAX QC on the last page
	$show['quickcomment'] = (can_post_new_message($group));

	if ($messageend >= $messagetotal)
	{
		$show['allow_ajax_qc'] = 1;

		// also mark discussion as read
		exec_sg_mark_as_read('discussion', $discussion['discussionid']);
	}

	if ($show['quickcomment'])
	{
		require_once(DIR . '/includes/functions_editor.php');

		$editorid = construct_edit_toolbar(
			'',
			false,
			'groupmessage',
			$vbulletin->options['allowsmilies'],
			true,
			false,
			'qr_small',
			'',
			array(),
			'content',
			'vBForum_SocialGroupMessage',
			0,
			$discussion['discussionid']
		);
	}

	// Construct page navigation
	$pagenavbits = array();
	if ($perpage)
	{
		$pagenavbits['pp'] = $perpage;
	}
	if ($vbulletin->GPC['showignored'])
	{
		$pagenavbits['showignored'] = '1';
	}

	$pagenav = construct_page_nav($pagenumber, $perpage, $messagetotal, '', '', '',
		'groupdiscussion', $discussion, $pagenavbits);

	// Display inline moderation
	if ($show['inlinemod'])
	{
		show_group_inlinemoderation($group, $show, false);
	}

	$show['postlink'] = (can_post_new_message($group));
	$show['subscribe'] = (bool)$vbulletin->userinfo['userid'];

	// Social bookmarking
	if ($vbulletin->options['socialbookmarks'])
	{
		$guestuser = array(
			'userid'      => 0,
			'usergroupid' => 0,
		);
		cache_permissions($guestuser);

		$bookmarksites = '';
		if (
			is_array($vbulletin->bookmarksitecache) AND !empty($vbulletin->bookmarksitecache)
				AND
			$guestuser['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']
				AND
			$guestuser['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups']
				AND
			!(($group['options'] & $vbulletin->bf_misc_socialgroupoptions['join_to_view']) OR !$vbulletin->options['sg_allow_join_to_view'])
		)
		{
			$raw_title = html_entity_decode($discussion['title'], ENT_QUOTES);
			foreach ($vbulletin->bookmarksitecache AS $bookmarksite)
			{
				$bookmarksite['link'] = str_replace(
					array('{URL}', '{TITLE}'),
					array(urlencode(fetch_seo_url('groupdiscussion|bburl|js|nosession', $discussion)),
					urlencode($bookmarksite['utf8encode'] ? utf8_encode($raw_title) : $raw_title)),
					$bookmarksite['url']
				);

				($hook = vBulletinHook::fetch_hook('showthread_bookmarkbit')) ? eval($hook) : false;

				$templater = vB_Template::create('showthread_bookmarksite');
					$templater->register('bookmarksite', $bookmarksite);
				$bookmarksites .= $templater->render();
			}
		}
	}

	($hook = vBulletinHook::fetch_hook('group_discuss_end')) ? eval($hook) : false;

	// Set page template
	$page_templater = vB_Template::create('socialgroups_discussionview');
	$page_templater->register('allowed_bbcode', $allowed_bbcode);
	$page_templater->register('bookmarksites', $bookmarksites);
	$page_templater->register('discussion', $discussion);
	$page_templater->register('editorid', $editorid);
	$page_templater->register('group', $group);
	$page_templater->register('groupoptions', $groupoptions);
	$page_templater->register('lastcomment', $lastcomment);
	$page_templater->register('messagearea', $messagearea);
	$page_templater->register('messagebits', $messagebits);
	$page_templater->register('pagenav', $pagenav);
	$page_templater->register('first',$messagestart);
	$page_templater->register('last',$messageend);
	$page_templater->register('total', $messagetotal);
	$page_templater->register('template_hook', $template_hook);
	$page_templater->register('vBeditTemplate', $vBeditTemplate);
}

// #######################################################################
if ($_REQUEST['do'] == 'view' OR $_REQUEST['do'] == 'discuss' OR $_REQUEST['do'] == 'grouplist' OR $_REQUEST['do'] == 'subscribe' OR $_REQUEST['do'] == 'overview' OR $_REQUEST['do'] == 'moderatedgms' OR $_REQUEST['do'] == 'requests' OR $_REQUEST['do'] == 'invitations')
{
	$ownerlink = fetch_seo_url('member', $group, null, 'creatoruserid', 'creatorusername');
}

// #######################################################################
if ($_REQUEST['do'] == 'viewmembers')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
	));

	$perpage = $vbulletin->GPC['perpage'];
	$pagenumber = $vbulletin->GPC['pagenumber'];
	$totalmembers = $group['members_number'];

	sanitize_pageresults($totalmembers, $pagenumber, $perpage);

	$groupmembers = $vbulletin->db->query_read_slave("
		SELECT userfield.*, usertextfield.*, user.*, UNIX_TIMESTAMP(passworddate) AS passworddate,
			IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, (user.options & " . $vbulletin->bf_misc_useroptions['invisible'] . ") AS invisible,
			" . ($vbulletin->options['avatarenabled'] ? 'avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight,' : '') . "
			customprofilepic.userid AS profilepic, customprofilepic.dateline AS profilepicdateline, customprofilepic.width AS ppwidth, customprofilepic.height AS ppheight,
			user.icq AS icq, user.aim AS aim, user.yahoo AS yahoo, user.msn AS msn, user.skype AS skype
		FROM " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember
		INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = socialgroupmember.userid)
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (user.userid = userfield.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON (usertextfield.userid = user.userid)
		" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid) " : '') . "
		LEFT JOIN " . TABLE_PREFIX . "customprofilepic AS customprofilepic ON (user.userid = customprofilepic.userid)
		WHERE socialgroupmember.groupid = " . $vbulletin->GPC['groupid'] . " AND socialgroupmember.type = 'member'
		ORDER BY user.username
		LIMIT " . (($pagenumber - 1) * $perpage) . ", $perpage
	");

	require_once(DIR . '/includes/functions_bigthree.php');

	while ($groupmember = $vbulletin->db->fetch_array($groupmembers))
	{
		$width = 0;
		$height = 0;

		$alt = exec_switch_bg();

		fetch_avatar_from_userinfo($groupmember, true);
		fetch_musername($groupmember);
		$user = $groupmember;

		fetch_online_status($user, true);
		construct_im_icons($user, true);

		($hook = vBulletinHook::fetch_hook('group_memberbit')) ? eval($hook) : false;

		$templater = vB_Template::create('memberinfo_small');
			$templater->register('remove', $remove);
			$templater->register('user', $user);
		$member_list .= $templater->render();
	}
	$vbulletin->db->free_result($groupmembers);

	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		'' => $vbphrase['member_list']
	);

	$pagevars = array('do' => 'viewmembers');
	if ($perpage)
	{
		$pagevars['pp'] = $perpage;
	}

	$pagenav = construct_page_nav($pagenumber, $perpage, $totalmembers, '', '', '',
		'group', $group, $pagevars);

	$page_templater = vB_Template::create('socialgroups_memberlist');
	$page_templater->register('group', $group);
	$page_templater->register('member_list', $member_list);
	$page_templater->register('pagenav', $pagenav);
}

// #######################################################################
if ($_REQUEST['do'] == 'message')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
	));

	$perpage = $vbulletin->GPC['perpage'];
	$pagenumber = $vbulletin->GPC['pagenumber'];

	if (!$vbulletin->options['socnet_groups_msg_enabled'])
	{
		print_no_permission();
	}

	if (empty($group))
	{
		standard_error(fetch_error('invalidid', $vbphrase['social_group'], $vbulletin->options['contactuslink']));
	}

	// Check if we're posting or editing a discussion
	$edit_discussion = (empty($discussion) OR (!empty($messageinfo) AND $discussion['firstpostid'] == $messageinfo['gmid']));

	if ($edit_discussion)
	{
		// editing or posting a new discussion
		if (!empty($messageinfo))
		{
			// check if we are allowed to edit title
			$edit_discussion = can_edit_group_discussion($discussion, $group);

			if (!$edit_discussion AND !can_edit_group_message($messageinfo, $group))
			{
				print_no_permission();
			}
		}
		else
		{
			// check if we can post a new discussion in this group
			if (!can_post_new_discussion($group))
			{
				if (!$group['membertype'] AND fetch_socialgroup_perm('cancreatediscussion'))
				{
					standard_error(fetch_error('must_be_group_member'));
				}

				standard_error(fetch_error('invalidid', $vbphrase['social_group_discussion'], $vbulletin->options['contactuslink']));
			}
		}
	}
	else
	{
		// editing or posting a new message
		if (empty($discussion))
		{
			standard_error(fetch_error('invalidid', $vbphrase['social_group_discussion'], $vbulletin->options['contactuslink']));
		}

		// editing a message
		if ($messageinfo)
		{
			// Can we edit?
			if (!can_edit_group_message($messageinfo, $group))
			{
				print_no_permission();
			}
		}
		else
		{
			// posting a new message
			if (!can_post_new_message($group))
			{
				print_no_permission();
			}
		}
	}

	if ($_POST['do'] == 'message')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'subject'          => TYPE_STR,
			'message'          => TYPE_STR,
			'wysiwyg'          => TYPE_BOOL,
			'disablesmilies'   => TYPE_BOOL,
			'parseurl'         => TYPE_BOOL,
			'username'         => TYPE_STR,
			'ajax'             => TYPE_BOOL,
			'lastcomment'      => TYPE_UINT,
			'humanverify'      => TYPE_ARRAY,
			'loggedinuser'     => TYPE_UINT,
			'fromquickcomment' => TYPE_BOOL,
			'preview'          => TYPE_STR,
			'advanced'         => TYPE_BOOL,
			'hideinlinemod'    => TYPE_BOOL
		));

		($hook = vBulletinHook::fetch_hook('group_message_post_start')) ? eval($hook) : false;

		// unwysiwygify the incoming data
		if ($vbulletin->GPC['wysiwyg'])
		{
			require_once(DIR . '/includes/class_wysiwygparser.php');
			$html_parser = new vB_WysiwygHtmlParser($vbulletin);
			$vbulletin->GPC['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'],  $vbulletin->options['allowhtml']);
		}

		// parse URLs in message text
		if ($vbulletin->options['allowbbcode'] AND $vbulletin->GPC['parseurl'])
		{
			require_once(DIR . '/includes/functions_newpost.php');
			$vbulletin->GPC['message'] = convert_url_to_bbcode($vbulletin->GPC['message']);
		}

		$message = array(
			'message'        =>& $vbulletin->GPC['message'],
			'postuserid'     =>& $vbulletin->userinfo['userid'],
			'disablesmilies' =>& $vbulletin->GPC['disablesmilies'],
			'parseurl'       =>& $vbulletin->GPC['parseurl'],
		);

		if ($discussion['discussionid'])
		{
			$message['discussionid']   = $discussion['discussionid'];
		}

		if ($edit_discussion AND !$vbulletin->GPC['ajax'])
		{
			if ($messageinfo AND $vbulletin->GPC['advanced'])
			{
				// Initial visit to advanced edit screen
				$message['title'] = $messageinfo['title'];
			}
			else
			{
				$message['title'] = $vbulletin->GPC['subject'];
			}
		}

		if ($vbulletin->GPC['ajax'])
		{
			$message['message'] = convert_urlencoded_unicode($message['message']);
		}

		$dataman =& datamanager_init('GroupMessage', $vbulletin, ERRTYPE_ARRAY);
		$dataman->set_info('group', $group);
		$dataman->set_info('discussion', $discussion);

		if ($messageinfo)
		{	// existing message
			$show['edit'] = true;

			$dataman->set_existing($messageinfo);
		}
		else
		{
			// New message
			if (fetch_group_auto_moderation($group))
			{
				$dataman->set('state', 'moderation');
			}

			if ($vbulletin->userinfo['userid'] == 0)
			{
				$dataman->setr('username', $vbulletin->GPC['username']);
			}

			$dataman->setr('discussionid', $message['discussionid']);
			$dataman->setr('postuserid', $message['postuserid']);
		}

		if ($edit_discussion AND (!$messageinfo OR !$vbulletin->GPC['ajax']))
		{
			$dataman->setr('title', $message['title']);
		}

		$message['pagetext'] = $message['message'];
		$message['postusername'] = $vbulletin->userinfo['username'];

		$dataman->set_info('preview', $vbulletin->GPC['preview']);
		$dataman->setr('pagetext', $message['pagetext']);
		$dataman->set('allowsmilie', !$message['disablesmilies']);

		$dataman->pre_save();

		if ($vbulletin->GPC['fromquickcomment'] AND $vbulletin->GPC['preview'])
		{
			$dataman->errors = array();
		}

		// Visitor Messages and Group Messages share the same restrictive bbcode set because of this...
		require_once(DIR . '/includes/class_socialmessageparser.php');
		$pmparser = new vB_GroupMessageParser($vbulletin, fetch_tag_list());
		$pmparser->parse($message['message']);
		if ($error_num = count($pmparser->errors))
		{
			foreach ($pmparser->errors AS $tag => $error_phrase)
			{
				$dataman->errors[] = fetch_error($error_phrase, $tag);
			}
		}

		if (!empty($dataman->errors))
		{
			if ($vbulletin->GPC['ajax'])
			{
				require_once(DIR . '/includes/class_xml.php');
				$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
				$xml->add_group('errors');
				foreach ($dataman->errors AS $error)
				{
					$xml->add_tag('error', $error);
				}
				$xml->close_group();
				$xml->print_xml();
			}
			else
			{
				define('MESSAGEPREVIEW', true);
				require_once(DIR . '/includes/functions_newpost.php');
				$preview = construct_errors($dataman->errors);
				$_GET['do'] = 'message';
			}
		}
		else if ($vbulletin->GPC['preview'] OR $vbulletin->GPC['advanced'])
		{
			define('MESSAGEPREVIEW', true);
			$preview = process_group_message_preview($message);
			$_GET['do'] = 'message';
		}
		else
		{
			$gmid = $dataman->save();

			if ($discussion)
			{
				if ($messageinfo)
				{
					clear_autosave_text('vBForum_SocialGroupMessage', $messageinfo['gmid'], 0, $vbulletin->userinfo['userid']);
				}
				else
				{
					clear_autosave_text('vBForum_SocialGroupMessage', 0, $discussion['discussionid'], $vbulletin->userinfo['userid']);
				}
			}
			else
			{
				clear_autosave_text('vBForum_SocialGroupDiscussion', 0, 0, $vbulletin->userinfo['userid']);
			}

			if ($messageinfo)
			{
				$gmid = $messageinfo['gmid'];
			}

			if ($messageinfo AND !$group['is_owner'] AND can_moderate(0, 'caneditgroupmessages'))
			{
				require_once(DIR . '/includes/functions_log_error.php');
				log_moderator_action($messageinfo, 'gm_by_x_in_y_for_z_edited',
					array($messageinfo['postusername'], $discussion['title'], $group['name'])
				);
			}

			if ($vbulletin->GPC['ajax'] AND (!$edit_discussion OR $messageinfo['gmid']))
			{
				require_once(DIR . '/includes/class_xml.php');
				$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
				$xml->add_group('commentbits');

				require_once(DIR . '/includes/class_groupmessage.php');

				// create message collection
				$collection_factory = new vB_Group_Collection_Factory($vbulletin, $group);
				$collection = $collection_factory->create(
					($messageinfo['gmid'] ? 'message' : 'recentmessage'),
					$discussion['discussionid'], false, false, false, true
				);

				if ($messageinfo['gmid'])
				{
					$collection->filter_id($messageinfo['gmid']);
				}
				else
				{
					$collection->set_dateline($vbulletin->GPC['lastcomment'], $gmid);
				}

				// add hook for manipulating query
				$collection->set_query_hook('group_message_post_ajax');

				// create bit factory for rendering messages
				$bitfactory = new vB_Group_Bit_Factory($vbulletin, 'message');

				// build response for each message
				while ($message = $collection->fetch_item())
				{
					$bit = $bitfactory->create($message, $group);

					if ($vbulletin->GPC['hideinlinemod'])
					{
						$bit->show_moderation_tools(false);
					}

					$xml->add_tag('message', process_replacement_vars($bit->construct()), array(
						'gmid'              => $message['gmid'],
						'visible'           => ($message['state'] == 'visible') ? 1 : 0,
						'bgclass'           => $bgclass,
						'quickedit'			=> 1
					));
				}
				unset($bitfactory, $bit, $collection_factory, $collection);

				exec_sg_mark_as_read('discussion', $discussion['discussionid'], $vbulletin->userinfo['username']);

				// send notifications
				if (!$messageinfo AND $discussion['subscribers'] AND !fetch_group_auto_moderation($group))
				{
					exec_send_sg_notification($discussion['discussionid'], $gmid, $postusername=false);
				}

				$xml->add_tag('time', TIMENOW);
				$xml->close_group();
				$xml->print_xml(true);
			}
			else
			{
				// check if we're posting a new discussion
				if (!$messageinfo AND $edit_discussion)
				{
					// create a new discussion using the new message as the first and last posts
					$discussion_dm =& datamanager_init('Discussion', $vbulletin, ERRTYPE_ARRAY);
					$discussion_dm->set('groupid', $group['groupid']);
					$discussion_dm->set('firstpostid', $gmid);
					$discussion_dm->set('lastpost', TIMENOW);
					$discussion_dm->set('lastpostid', $gmid);
					$discussion_dm->set('lastposter', $vbulletin->userinfo['username']);
					$discussion_dm->set('lastposterid', $vbulletin->userinfo['userid']);
					$discussion_dm->set_info('lastposttitle', $message['title']);

					// set group info so discussion dm can update counters on group
					$discussion_dm->setr_info('group', $group);

					// set the relevant counter to 1 based on new messages state
					$discussion_dm->set($dataman->fetch_field('state'), 1);
					$discussionid = $discussion_dm->save();
					unset($discussion_dm);

					// Search index maintenance
					require_once(DIR . '/vb/search/core.php');
					$indexer = vB_Search_Core::get_instance()->get_index_controller('vBForum', 'SocialGroupMessage');
					$indexer->index($gmid);

					if (!fetch_group_auto_moderation($group))
					{
						exec_sg_mark_as_read('discussion', $discussionid);
					}

					// IIS may have issues setting cookies with header redirects
					$forceredirect = (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);

					($hook = vBulletinHook::fetch_hook('group_message_post_complete')) ? eval($hook) : false;
					$vbulletin->url = fetch_seo_url('groupmessage', array('gmid' => $gmid)) . "#gmessage$gmid";
					print_standard_redirect('visitormessagethanks', true, $forceredirect);
				}
				else
				{
					// mark discussion as read
					exec_sg_mark_as_read('discussion', $discussion['discussionid']);

					// IIS may have issues setting cookies with header redirects
					$forceredirect = (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);

					($hook = vBulletinHook::fetch_hook('group_message_post_complete')) ? eval($hook) : false;
					$vbulletin->url = fetch_seo_url('groupmessage', array('gmid' => $gmid)) . "#gmessage$gmid";

					// send notifications
					if (!$messageinfo AND !$edit_discussion AND $discussion['subscribers'])
					{
						exec_send_sg_notification($discussion['discussionid'], $gmid, $postusername=false);
					}

					print_standard_redirect('visitormessagethanks', true, $forceredirect);
				}
			}
		}

		unset($dataman);
	}

	if ($_GET['do'] == 'message')
	{
		require_once(DIR . '/includes/functions_editor.php');

		($hook = vBulletinHook::fetch_hook('group_message_form_start')) ? eval($hook) : false;

		if (defined('MESSAGEPREVIEW'))
		{
			$postpreview = $preview;
			$message['message'] = htmlspecialchars_uni($message['message']);
			require_once(DIR . '/includes/functions_newpost.php');
			construct_checkboxes($message);
		}
		else if ($messageinfo)
		{
			require_once(DIR . '/includes/functions_newpost.php');
			construct_checkboxes(
				array(
					'disablesmilies' => (!$messageinfo['allowsmilie']),
					'parseurl'       => 1,
				)
			);
			$message['message'] = htmlspecialchars_uni($messageinfo['pagetext']);

			if ($edit_discussion)
			{
				$message['title'] = htmlspecialchars_uni($messageinfo['title']);
			}
		}
		else
		{
			$message['message'] = '';
		}

		$navbits[fetch_seo_url('grouphome', array())] = $vbphrase['social_groups'];
		$navbits[fetch_seo_url('groupcategory', $group)] = $group['categoryname'];
		$navbits[fetch_seo_url('group', $group)] = $group['name'];
		$navbits[fetch_seo_url('groupdiscussion', $discussion)] = $discussion['title'];

		if ($messageinfo)
		{
			$show['edit'] = true;

			if ($messageinfo['gmid'] == $discussion['firstpostid'])
			{
				$show['delete'] = (
					fetch_socialgroup_modperm('candeletediscussions', $group)
					OR (
						$messageinfo['postuserid'] == $vbulletin->userinfo['userid']
						AND ((
							fetch_socialgroup_perm('canmanagediscussions')
							)
						OR (
							fetch_socialgroup_perm('canmanagemessages')
							AND can_edit_group_discussion($discussion)
						))
					)
				);

				$show['physicaldeleteoption'] = fetch_socialgroup_modperm('canremovediscussions', $group);
				$delete_discussion = true;
			}
			else
			{
				$show['delete'] = (
					fetch_socialgroup_modperm('candeletegroupmessages', $group)
					OR (
						$messageinfo['postuserid'] == $vbulletin->userinfo['userid']
						AND $messageinfo['gmid'] != $discussion['firstpostid']
						AND fetch_socialgroup_perm('canmanagemessages')
					)
				);

				$show['physicaldeleteoption'] = fetch_socialgroup_modperm('canremovegroupmessages', $group);
			}

			$messageinfo['deleted'] = 'deleted' == $messageinfo['state'];
			$show['undeleteoption'] = $messageinfo['deleted'] AND ($show['delete'] OR $show['physicaldeleteoption']);
			$navbits[] = ($edit_discussion) ? $vbphrase['edit_discussion'] : $vbphrase['edit_message'];
		}
		else
		{

			$navbits[] = ($edit_discussion) ? $vbphrase['post_new_discussion'] : $vbphrase['reply_to_discussion'];
		}

		if ($discussion)
		{
			$contenttype = 'vBForum_SocialGroupMessage';
			if (!$messageinfo)
			{
				$contentid = 0;
				$parentcontentid = $discussion['discussionid'];
			}
			else
			{
				$contentid = $messageinfo['gmid'];
				$parentcontentid = 0;
			}
		}
		else
		{
			$contenttype = 'vBForum_SocialGroupDiscussion';
			$contentid = 0;
			$parentcontentid = 0;
		}

		$editorid = construct_edit_toolbar(
			$message['message'],
			false,
			'groupmessage',
			$vbulletin->options['allowsmilies'],
			true,
			false,
			'fe',
			'',
			array(),
			'content',
			$contenttype,
			$contentid,
			$parentcontentid,
			defined('MESSAGEPREVIEW'),
			true,
			'titlefield'
		);

		$usernamecode = vB_Template::create('newpost_usernamecode')->render();

		// carry page number in case we do a hard delete
		$pagenumber = $vbulletin->input->clean_gpc('g', 'pagenumber', TYPE_UINT);

		// auto-parse URL
		if (!isset($checked['parseurl']))
		{
			$checked['parseurl'] = 'checked="checked"';
		}

		$show['parseurl'] = $vbulletin->options['allowbbcode'];
		$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));
		$show['additional_options'] = ($show['misc_options'] OR !empty($attachmentoption));
		$show['auto_moderation'] = fetch_group_auto_moderation($group);

		$navbits = construct_navbits($navbits);
		$navbar = render_navbar_template($navbits);

		($hook = vBulletinHook::fetch_hook('group_message_form_complete')) ? eval($hook) : false;

		// complete
		$templater = vB_Template::create('socialgroups_editor');
			$templater->register_page_templates();
			$templater->register('checked', $checked);
			$templater->register('delete_discussion', $delete_discussion);
			$templater->register('disablesmiliesoption', $disablesmiliesoption);
			$templater->register('discussion', $discussion);
			$templater->register('editorid', $editorid);
			$templater->register('edit_discussion', $edit_discussion);
			$templater->register('group', $group);
			$templater->register('human_verify', $human_verify);
			$templater->register('message', $message);
			$templater->register('messagearea', $messagearea);
			$templater->register('messagebits', $messagebits);
			$templater->register('messageinfo', $messageinfo);
			$templater->register('navbar', $navbar);
			$templater->register('pagenumber', $pagenumber);
			$templater->register('pagetitle', $pagetitle);
			$templater->register('posthash', $posthash);
			$templater->register('postpreview', $postpreview);
			$templater->register('usernamecode', $usernamecode);
		print_output($templater->render());
	}
}

// #######################################################################
if ($_POST['do'] == 'deletemessage')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deletemessage' => TYPE_STR,
		'reason'        => TYPE_STR,
	));

	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
	));

	// check if cancelled / no delete
	if ($vbulletin->GPC['deletemessage'] == '')
	{
		$vbulletin->url = fetch_seo_url('groupdiscussion', $discussion);
		print_standard_redirect('redirect_groupmessage_nodelete');
	}

	$is_discussion = ($discussion['firstpostid'] == $messageinfo['gmid']);
	$hard_delete = ($vbulletin->GPC['deletemessage'] == 'remove');
	$undelete = ($vbulletin->GPC['deletemessage'] == 'undelete');

	// Check permissions
	if ($is_discussion)
	{
		if ($messageinfo['state'] == 'deleted' AND !fetch_socialgroup_modperm('canundeletediscussions', $group))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_discussions'));
		}
		else if ($messageinfo['state'] == 'moderation'
				 AND !fetch_socialgroup_modperm('canmoderatediscussions', $group)
				 AND (($messageinfo['postuserid'] != $vbulletin->userinfo['userid'])
				 		OR (!fetch_socialgroup_perm('canmanagediscussions'))))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_discussions'));
		}

		if ($hard_delete AND !fetch_socialgroup_modperm('canremovediscussions', $group))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_hard_delete_discussions'));
		}
		else if (!$hard_delete AND !fetch_socialgroup_modperm('candeletediscussions', $group)
				 AND (($messageinfo['postuserid'] != $vbulletin->userinfo['userid'])
				 	  OR (!fetch_socialgroup_perm('canmanagediscussions')
				 	  AND (!fetch_socialgroup_perm('canmanagemessages')
				 	  		OR ($discussion['visible'] + $discussion['moderation'] + $discussion['deleted']) > 1))))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_soft_delete_discussions'));
		}
	}
	else
	{
		if ($messageinfo['state'] == 'deleted' AND !fetch_socialgroup_modperm('canundeletegroupmessages', $group))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_deleted_messages'));
		}
		else if ($messageinfo['state'] == 'moderation' AND !fetch_socialgroup_modperm('canmoderategroupmessages', $group))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_manage_moderated_messages'));
		}

		if ($hard_delete AND !fetch_socialgroup_modperm('canremovegroupmessages', $group))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_hard_delete_messages'));
		}
		else if (!$hard_delete AND !fetch_socialgroup_modperm('candeletegroupmessages', $group)
				 AND (($messageinfo['postuserid'] != $vbulletin->userinfo['userid']) OR !fetch_socialgroup_perm('canmanagemessages')))
		{
			standard_error(fetch_error('you_do_not_have_permission_to_soft_delete_messages'));
		}
	}

	// Only specifically delete discussion on hard delete
	$delete_discussion = ($is_discussion AND $hard_delete AND !$undelete);

	if ($undelete)
	{
		require_once(DIR . '/vb/search/indexcontroller/queue.php');

		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "deletionlog
			WHERE type = 'groupmessage' AND
				primaryid = " . intval($messageinfo['gmid']) . "
		");

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "groupmessage
			SET state = 'visible'
			WHERE gmid = " . intval($messageinfo['gmid']) . "
		");

		vB_Search_Indexcontroller_Queue::indexQueue('vBForum', 'SocialGroupMessage', 'index', $gmid, null, null);

		build_discussion_counters($messageinfo['discussionid']);
		build_group_counters($messageinfo['groupid']);

		if (!$messageinfo['is_group_owner'])
		{
			require_once(DIR . '/includes/functions_log_error.php');

			if ($messageinfo['firstpost'])
			{
				log_moderator_action($messageinfo, 'discussion_by_x_for_y_undeleted',
					array($messageinfo['postusername'], $messageinfo['group_name'])
				);
			}
			else
			{
				log_moderator_action($messageinfo, 'gm_by_x_in_y_for_z_undeleted',
						array($messageinfo['postusername'], $messageinfo['discussion_name'], $messageinfo['group_name'])
				);
			}

			if ($is_discussion)
			{
				$vbulletin->url = fetch_seo_url('groupdiscussion', $discussion);
			}
			else
			{
				$vbulletin->url = fetch_seo_url('groupmessage', $messageinfo);
			}
		}

		if ($is_discussion)
		{
			print_standard_redirect('redirect_groupdiscussionrestored');
		}
		else
		{
			print_standard_redirect('redirect_groupmessagerestored');
		}
	}
	else
	{
		if ($delete_discussion)
		{
			// hard delete discussion, all messages, deletionlogs and moderation
			$dataman =& datamanager_init('Discussion', $vbulletin, ERRTYPE_STANDARD);
			$dataman->set_existing($discussion);
			$dataman->set_info('group', $group);
		}

		else
		{
			// soft delete message
			$dataman =& datamanager_init('GroupMessage', $vbulletin, ERRTYPE_STANDARD);
			$dataman->set_existing($messageinfo);
			$dataman->set_info('hard_delete', $hard_delete);
			$dataman->set_info('reason', $vbulletin->GPC['reason']);
			$dataman->set_info('group', $group);
		}

		($hook = vBulletinHook::fetch_hook('group_message_delete')) ? eval($hook) : false;
		$dataman->delete();
		unset($dataman);

		// Only log if not owner and not normally managing own messages
		if (!$group['is_owner'] AND (($messageinfo['postuserid'] != $vbulletin->userinfo['userid']) OR ($is_discussion AND $discussion['visible'] > 1)))
		{
			require_once(DIR . '/includes/functions_log_error.php');
			log_moderator_action($messageinfo,
				($hard_delete ? ($delete_discussion ? 'discussion_by_x_for_y_removed' : 'gm_by_x_for_y_removed') : 'gm_by_x_for_y_soft_deleted'),
				array($messageinfo['postusername'], $group['name'])
			);
		}

		// Take user back to post after soft delete
		if (!$hard_delete AND fetch_socialgroup_modperm('canviewdeleted', $group))
		{
			$vbulletin->url = fetch_seo_url('groupmessage', $messageinfo);
		}
		else
		{
			if ($is_discussion)
			{
				// discussion will be gone, take back to group
				$vbulletin->url = fetch_seo_url('group', $group);
			}
			else
			{
				// try to take user back to the correct results page
				$vbulletin->url = fetch_seo_url('groupdiscussion', $discussion, array('page' => $pagenumber));
			}
		}

		if ($is_discussion AND ($hard_delete OR !fetch_socialgroup_modperm('canviewdeleted', $group)))
		{
			print_standard_redirect('redirect_groupdiscussiondelete');
		}
		else
		{
			print_standard_redirect('redirect_groupmessagedelete');
		}
	}
}

// ############################### start retrieve ip ###############################
if ($_REQUEST['do'] == 'viewip')
{
	// check moderator permissions for getting ip
	if (!can_moderate(0, 'canviewips'))
	{
		print_no_permission();
	}

	if (!$messageinfo['gmid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['message'], $vbulletin->options['contactuslink'])));
	}

	$messageinfo['hostaddress'] = @gethostbyaddr(long2ip($messageinfo['ipaddress']));

	($hook = vBulletinHook::fetch_hook('group_message_getip')) ? eval($hook) : false;

	eval(standard_error(fetch_error('thread_displayip', long2ip($messageinfo['ipaddress']), htmlspecialchars_uni($messageinfo['hostaddress'])), '', 0));
}

// ############################### start report ###############################
if ($_REQUEST['do'] == 'report' OR $_POST['do'] == 'sendemail')
{
	require_once(DIR . '/includes/class_reportitem.php');

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	$reportthread = ($rpforumid = $vbulletin->options['rpforumid'] AND $rpforuminfo = fetch_foruminfo($rpforumid));
	$reportemail = ($vbulletin->options['enableemail'] AND $vbulletin->options['rpemail']);

	if (!$reportthread AND !$reportemail)
	{
		eval(standard_error(fetch_error('emaildisabled')));
	}

	$reportobj = new vB_ReportItem_GroupMessage($vbulletin);
	$reportobj->set_extrainfo('group', $group);
	$reportobj->set_extrainfo('discussion', $discussion);
	$perform_floodcheck = $reportobj->need_floodcheck();

	if ($perform_floodcheck)
	{
		$reportobj->perform_floodcheck_precommit();
	}

	if (!$messageinfo['gmid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['message'], $vbulletin->options['contactuslink'])));
	}

	($hook = vBulletinHook::fetch_hook('report_start')) ? eval($hook) : false;

	if ($_REQUEST['do'] == 'report')
	{
		// draw nav bar
		$navbits = array();
		$navbits[fetch_seo_url('grouphome', array())] = $vbphrase['social_groups'];
		$navbits[fetch_seo_url('groupcategory', $group)] = $group['categoryname'];
		$navbits[fetch_seo_url('group', $group)] = $group['name'];
		$navbits[fetch_seo_url('groupdiscussion', $discussion)] = $discussion['title'];

		$navbits[''] = $vbphrase['report_group_message'];
		$navbits = construct_navbits($navbits);

		$usernamecode = vB_Template::create('newpost_usernamecode')->render();

		$navbar = render_navbar_template($navbits);
		$url = $vbulletin->url;

		($hook = vBulletinHook::fetch_hook('report_form_start')) ? eval($hook) : false;

		$forminfo = $reportobj->set_forminfo($messageinfo);
		$templater = vB_Template::create('reportitem');
			$templater->register_page_templates();
			$templater->register('forminfo', $forminfo);
			$templater->register('navbar', $navbar);
			$templater->register('url', $url);
			$templater->register('usernamecode', $usernamecode);
		print_output($templater->render());
	}

	if ($_POST['do'] == 'sendemail')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'reason' => TYPE_STR,
		));

		if ($vbulletin->GPC['reason'] == '')
		{
			eval(standard_error(fetch_error('noreason')));
		}

		$reportobj->do_report($vbulletin->GPC['reason'], $messageinfo);

		$url = $vbulletin->url;
		print_standard_redirect('redirect_reportthanks');
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'reportpicture' OR $_POST['do'] == 'sendpictureemail')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'attachmentid' => TYPE_UINT
	));

	require_once(DIR . '/includes/class_reportitem.php');

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	$reportthread = ($rpforumid = $vbulletin->options['rpforumid'] AND $rpforuminfo = fetch_foruminfo($rpforumid));
	$reportemail = ($vbulletin->options['enableemail'] AND $vbulletin->options['rpemail']);

	if (!$reportthread AND !$reportemail)
	{
		eval(standard_error(fetch_error('emaildisabled')));
	}

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	if (!$vbulletin->options['socnet_groups_pictures_enabled'])
	{
		print_no_permission();
	}

	if ($group['membertype'] != 'member' AND !can_moderate(0, 'caneditgrouppicture'))
	{
		print_no_permission();
	}

	$pictureinfo = fetch_socialgroup_picture($vbulletin->GPC['attachmentid'], $group['groupid']);
	if (!$pictureinfo)
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	$userinfo = fetch_userinfo($pictureinfo['userid']);

	$reportobj = new vB_ReportItem_GroupPicture($vbulletin);
	$reportobj->set_extrainfo('user', $userinfo ? $userinfo : array());
	$reportobj->set_extrainfo('group', $group);

	$perform_floodcheck = $reportobj->need_floodcheck();

	if ($perform_floodcheck)
	{
		$reportobj->perform_floodcheck_precommit();
	}

	($hook = vBulletinHook::fetch_hook('report_start')) ? eval($hook) : false;

	if ($_REQUEST['do'] == 'reportpicture')
	{
		// draw nav bar
		$navbits = construct_navbits(array(
			fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
			fetch_seo_url('groupcategory', $group) => $group['categoryname'],
			fetch_seo_url('group', $group) => $group['name'],
			'' => $vbphrase['report_picture']
		));

		$usernamecode = vB_Template::create('newpost_usernamecode')->render();

		$navbar = render_navbar_template($navbits);
		$url = $vbulletin->url;

		($hook = vBulletinHook::fetch_hook('report_form_start')) ? eval($hook) : false;

		$forminfo = $reportobj->set_forminfo($pictureinfo);
		$templater = vB_Template::create('reportitem');
			$templater->register_page_templates();
			$templater->register('forminfo', $forminfo);
			$templater->register('navbar', $navbar);
			$templater->register('url', $url);
			$templater->register('usernamecode', $usernamecode);
		print_output($templater->render());
	}

	if ($_POST['do'] == 'sendpictureemail')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'reason' => TYPE_STR,
		));

		if ($vbulletin->GPC['reason'] == '')
		{
			eval(standard_error(fetch_error('noreason')));
		}

		$reportobj->do_report($vbulletin->GPC['reason'], $pictureinfo);

		$url = $vbulletin->url;
		print_standard_redirect('redirect_reportthanks');
	}
}

// #######################################################################
if ($_POST['do'] == 'updatepictures')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'pictures'          => TYPE_ARRAY,
		'frompicture'       => TYPE_BOOL,
		'posthash'          => TYPE_NOHTML,
		'poststarttime'     => TYPE_UINT,
	));

	if (empty($group))
	{
		standard_error(fetch_error('invalidid', $vbphrase['group'], $vbulletin->options['contactuslink']));
	}

	if ($group['membertype'] != 'member'
		 AND
		(
			$vbulletin->GPC['posthash']
				OR
			!can_moderate(0, 'caneditgrouppicture')
		)
	)
	{
		print_no_permission();
	}

	$can_delete = ($vbulletin->userinfo['userid'] == $group['userid'] OR can_moderate(0, 'candeletegrouppicture'));

	$attachmentids = array_map('intval', array_keys($vbulletin->GPC['pictures']));

	if (!$attachmentids)
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	if ($vbulletin->GPC['posthash'])
	{
		$attachmentids = array();
		if (md5($vbulletin->GPC['poststarttime'] . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']) != $vbulletin->GPC['posthash'])
		{
			standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
		}
		else
		{
			$pictures = $db->query_read("
				SELECT
					attachmentid
				FROM " . TABLE_PREFIX . "attachment
				WHERE
					posthash = '" . $db->escape_string($vbulletin->GPC['posthash']) . "'
						AND
					contenttypeid = $contenttypeid
						AND
					userid = {$vbulletin->userinfo['userid']}
			");
			while ($picture = $db->fetch_array($pictures))
			{
				$attachmentids[] = $picture['attachmentid'];
			}
			if (empty($attachmentids))
			{
				standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
			}
		}
	}

	$deleted_picture = false;

	$picture_sql = $db->query_read("
		SELECT
			attachment.contentid, attachment.userid, attachment.caption, attachment.state, attachment.dateline, attachment.attachmentid,
			filedata.extension, filedata.filesize, filedata.thumbnail_filesize, filedata.filedataid
		FROM " . TABLE_PREFIX . "attachment AS attachment
		INNER JOIN " . TABLE_PREFIX . "filedata AS filedata ON (attachment.filedataid = filedata.filedataid)
		WHERE
			attachment.contentid = " . ($vbulletin->GPC['posthash'] ? 0 : $group['groupid']) . "
				AND
			attachment.attachmentid IN (" . implode(',', $attachmentids) . ")
	");

	while ($picture = $db->fetch_array($picture_sql))
	{
		$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_ARRAY, 'attachment');
		$attachdata->set_existing($picture);
		$attachdata->set_info('group', $group);

		($hook = vBulletinHook::fetch_hook('group_picture_update')) ? eval($hook) : false;

		if ($vbulletin->GPC['pictures']["$picture[attachmentid]"]['delete'])
		{
			// if we can't delete, then we're not going to do the update either
			if ($can_delete)
			{
				$attachdata->delete(true, true, 'socialgroup', 'photo');
				$deleted_picture = true;
			}
		}
		else
		{
			if ($picture['state'] == 'moderation' AND can_moderate(0, 'canmoderategrouppicture') AND $vbulletin->GPC['pictures']["$picture[attachmentid]"]['approve'])
			{
				// need to increase picture counter
				$attachdata->set('state', 'visible');
				$updatecounter++;
			}

			if ($vbulletin->GPC['posthash'])
			{
				$attachdata->set('contentid', $group['groupid']);
				$attachdata->set('posthash', '');
			}

			if (!$picture['contentid'])
			{
				$activity = new vB_ActivityStream_Manage('socialgroup', 'photo');
				$activity->set('contentid', $picture['attachmentid']);
				$activity->set('userid', $picture['userid']);
				$activity->set('dateline', $picture['dateline']);
				$activity->set('action', 'create');
				$activity->save();
			}

			$attachdata->set('caption', $vbulletin->GPC['pictures']["$picture[attachmentid]"]['caption']);
			$attachdata->save();

			if (
				$picture['userid'] != $vbulletin->userinfo['userid']
					AND
				$vbulletin->GPC['pictures']["$picture[attachmentid]"]['caption'] != $picture['caption']
					AND
				can_moderate(0, 'caneditgrouppicture')
			)
			{
				require_once(DIR . '/includes/functions_log_error.php');
				log_moderator_action($picture, 'picture_x_in_y_by_z_edited',
					array(fetch_trimmed_title($picture['caption'], 50), $group['name'], $userinfo['username'])
				);
			}
		}
	}

	($hook = vBulletinHook::fetch_hook('group_picture_update_complete')) ? eval($hook) : false;

	$groupdm = datamanager_init('SocialGroup', $vbulletin);
	$groupdm->set_existing($group);
	$groupdm->rebuild_picturecount();
	$groupdm->save();

	if ($vbulletin->GPC['frompicture'] AND sizeof($attachmentids) == 1 AND !$deleted_picture)
	{
		$attachmentid = reset($attachmentids);
		$vbulletin->url = fetch_seo_url('group', $group, array('attachmentid' => $attachmentid));
	}
	else
	{
		$vbulletin->url = fetch_seo_url('group', $group);
	}

	print_standard_redirect('pictures_updated');
}

// #######################################################################
if ($_REQUEST['do'] == 'editpictures')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber'     => TYPE_UINT,
		'attachmentid'   => TYPE_UINT,
		'attachmentids'  => TYPE_ARRAY_UINT,
		'errors'         => TYPE_ARRAY_NOHTML,
		'frompicture'    => TYPE_BOOL
	));

	if (empty($group))
	{
		standard_error(fetch_error('invalidid', $vbphrase['group'], $vbulletin->options['contactuslink']));
	}

	if ($group['membertype'] != 'member' AND !can_moderate(0, 'caneditgrouppicture'))
	{
		print_no_permission();
	}

	if ($vbulletin->GPC['attachmentid'])
	{
		$vbulletin->GPC['attachmentids'][] = $vbulletin->GPC['attachmentid'];
	}

	$show['delete_option'] = ($vbulletin->userinfo['userid'] == $group['userid'] OR can_moderate(0, 'candeletegrouppicture'));

	$display = $db->query_first("
		SELECT
			COUNT(*) AS picturecount
		FROM " . TABLE_PREFIX . "attachment AS a
		WHERE
			a.contentid = $group[groupid]
				AND
			a.contenttypeid = " . intval($contenttypeid) . "
			" . ($vbulletin->GPC['attachmentids'] ? "AND a.attachmentid IN (" . implode(',', $vbulletin->GPC['attachmentids']) . ")" : '') . "
			" . (!can_moderate(0, 'canmoderategrouppicture') ? "AND (a.state = 'visible' OR a.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
	");

	if (!$display['picturecount'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	// pagination setup
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}

	//$perpage = $vbulletin->options['group_pictures_perpage'];
	$perpage = 999999; // disable page nav
	$total_pages = max(ceil($display['picturecount'] / $perpage), 1); // 0 pictures still needs an empty page
	$pagenumber = ($vbulletin->GPC['pagenumber'] > $total_pages ? $total_pages : $vbulletin->GPC['pagenumber']);
	$start = ($pagenumber - 1) * $perpage;

	$picture_sql = $db->query_read("
		SELECT
			a.attachmentid, a.userid, a.caption, a.state, a.dateline,
			fd.filesize, fd.thumbnail_filesize, fd.thumbnail_dateline, fd.thumbnail_width, fd.thumbnail_height, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
		WHERE
			a.contentid = $group[groupid]
				AND
			a.contenttypeid = " . intval($contenttypeid) . "
			" . ($vbulletin->GPC['attachmentids'] ? "AND a.attachmentid IN (" . implode(',', $vbulletin->GPC['attachmentids']) . ")" : '') . "
			" . (!can_moderate(0, 'canmoderatepictures') ? "AND (a.state = 'visible' OR a.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
		ORDER BY
			a.dateline DESC
		LIMIT $start, $perpage
	");

	$picturebits = '';
	while ($picture = $db->fetch_array($picture_sql))
	{
		$picture['caption_preview'] = fetch_censored_text(fetch_trimmed_title(
			$picture['caption'],
			$vbulletin->options['album_captionpreviewlen']
		));

		$picture['thumburl'] = ($picture['thumbnail_filesize'] ? true : false);
		$picture['dimensions'] = ($picture['thumbnail_width'] ? "width=\"$picture[thumbnail_width]\" height=\"$picture[thumbnail_height]\"" : '');

		($hook = vBulletinHook::fetch_hook('group_picture_editbit')) ? eval($hook) : false;

		$show['approve_option'] = ($picture['state'] == 'moderation' AND can_moderate(0, 'canmoderatepictures'));

		$templater = vB_Template::create('socialgroups_picture_editbit');
			$templater->register('group', $group);
			$templater->register('picture', $picture);
		$picturebits .= $templater->render();
	}


 /*
	 //pagenav is currently disabled (and this entirely action isn't currently linked to in the app). There is not indication as to
	 //why, but it it probably breaks something.  If its ever enabled we'll need to update this to use the seo urls so that it
	 //works with the forum subdirect option.  This is fairly mechanical (other calls to construct_page_nav provide an example), but I'm
	 //not clear how the address2 parameter affects the changes I made to the construct_page_nav to handle the jump link for the seo
	 //url approach.  This is too much work to fix functionality that already doesn't work.
	$pagenav = construct_page_nav($pagenumber, $perpage, $display['picturecount'],
		'group.php?' . $vbulletin->session->vars['sessionurl'] . "do=editpictures&amp;groupid=$group[groupid]",
		($vbulletin->GPC['attachmentids'] ? "&amp;attachmentids[]=" . implode('&amp;attachmentids[]=', $vbulletin->GPC['attachmentids']) : '')
	);
 */
	$pagenav = '';

	$frompicture = $vbulletin->GPC['frompicture'];

	($hook = vBulletinHook::fetch_hook('group_picture_edit_complete')) ? eval($hook) : false;

	// navbar and final output
	$navbits = construct_navbits(array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		'' => $vbphrase['edit_pictures']
	));
	$navbar = render_navbar_template($navbits);

	$templater = vB_Template::create('socialgroups_picture_edit');
		$templater->register_page_templates();
		$templater->register('group', $group);
		$templater->register('error_message', $error_message);
		$templater->register('frompicture', $frompicture);
		$templater->register('navbar', $navbar);
		$templater->register('pagenav', $pagenav);
		$templater->register('picturebits', $picturebits);
	print_output($templater->render());
}

// #######################################################################
if ($_POST['do'] == 'doremovepicture' OR $_REQUEST['do'] == 'removepicture')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'attachmentid' => TYPE_UINT
	));

	if (!$vbulletin->options['socnet_groups_pictures_enabled'])
	{
		print_no_permission();
	}

	$pictureinfo = fetch_socialgroup_picture($vbulletin->GPC['attachmentid'], $group['groupid']);
	if (!$pictureinfo)
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	if ($pictureinfo['userid'] != $vbulletin->userinfo['userid'] AND !fetch_socialgroup_modperm('canremovepicture', $group))
	{
		print_no_permission();
	}
}

// #######################################################################
if ($_POST['do'] == 'doremovepicture')
{
	$vbulletin->input->clean_array_gpc('p', array(
 		'deny' => TYPE_NOHTML
	));

	// You either clicked no or you're a guest
	if (!empty($vbulletin->GPC['deny']))
	{
		//there doesn't seem to be a way to get here.  The "no" button isn't a submit button and
		//the JS it activates points elsewhere.  Without JS it doesn't do anything.  Not sure
		//about guests.
		$vbulletin->url = fetch_seo_url('group', $group, array('do' => 'grouppictures'));
		print_standard_redirect('action_cancelled');
	}

	$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_ARRAY, 'attachment');
	$attachdata->set_existing($pictureinfo);
	$attachdata->set_info('group', $group);
	$attachdata->delete(true, true, 'socialgroup', 'photo');

	$groupdm = datamanager_init('SocialGroup', $vbulletin);
	$groupdm->set_existing($group);
	$groupdm->rebuild_picturecount();
	$groupdm->save();

	if (!$group['is_owner'] AND $pictureinfo['userid'] != $vbulletin->userinfo['userid'] AND can_moderate(0, 'caneditgrouppicture'))
	{
		require_once(DIR . '/includes/functions_log_error.php');
		log_moderator_action($pictureinfo, 'social_group_picture_x_in_y_removed',
			array(fetch_trimmed_title($pictureinfo['caption'], 50), $group['name'])
		);
	}

	($hook = vBulletinHook::fetch_hook('group_picture_delete')) ? eval($hook) : false;

	if ($groupdm->fetch_field('picturecount'))
	{
		$vbulletin->url = fetch_seo_url('group', $group, array('do' => 'grouppictures'));
	}
	else
	{
		$vbulletin->url = fetch_seo_url('group', $group);
	}

	unset($groupdm);

	print_standard_redirect('picture_removed_from_group');
}

// #######################################################################
if ($_REQUEST['do'] == 'removepicture')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'attachmentid' => TYPE_UINT
	));

	$confirmdo = 'doremovepicture';
	$confirmaction = vB_Friendly_Url::fetchLibrary($vbulletin, 'group|nosession', $group, array('do' => $confirmdo));
	$confirmaction = $confirmaction->get_url(FRIENDLY_URL_OFF);

	$title_phrase = $vbphrase['remove_picture_from_group'];
	$question_phrase = construct_phrase($vbphrase['confirm_remove_picture_group_x'], $group['name']);

	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		'' => $vbphrase['remove_picture_from_group']
	);

	$page_templater = vB_Template::create('socialgroups_confirm');
	$page_templater->register('confirmaction', $confirmaction);
	$page_templater->register('confirmdo', $confirmdo);
	$page_templater->register('extratext', $extratext);
	$page_templater->register('group', $group);
	$page_templater->register('pictureinfo', $pictureinfo);
	$page_templater->register('question_phrase', $question_phrase);
	$page_templater->register('title_phrase', $title_phrase);
	$page_templater->register('url', $url);
}

// #######################################################################
if ($_REQUEST['do'] == 'grouppictures')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT
	));

	if (!$vbulletin->options['socnet_groups_pictures_enabled'])
	{
		print_no_permission();
	}

	if (
		!($group['options'] & $vbulletin->bf_misc_socialgroupoptions['enable_group_albums'])
			OR
		!$vbulletin->options['socnet_groups_pictures_enabled']
	)
	{
		print_no_permission();
	}

	if ($group['membertype'] != 'member' AND !can_moderate(0, 'caneditgrouppicture'))
	{
		if ($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canjoingroups'] AND can_join_group($group))
		{
			standard_error(fetch_error('must_be_group_member_view_add_pictures_join_x', fetch_seo_url('group', $group, array('do' => 'join'))));
		}
		else
		{
			standard_error(fetch_error('must_be_group_member_view_add_pictures'));
		}
	}

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}

	$perpage = $vbulletin->options['album_pictures_perpage'];
	$total_pages = max(ceil($group['rawpicturecount'] / $perpage), 1); // 0 pictures still needs an empty page
	$pagenumber = ($vbulletin->GPC['pagenumber'] > $total_pages ? $total_pages : $vbulletin->GPC['pagenumber']);
	$start = ($pagenumber - 1) * $perpage;

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
		($hook = vBulletinHook::fetch_hook('group_pictures_query')) ? eval($hook) : false;

	$pictures_sql = $db->query_read_slave("
		SELECT
			a.attachmentid, a.userid, a.caption, a.dateline,
			fd.filesize, fd.thumbnail_filesize, fd.thumbnail_dateline, fd.thumbnail_width, fd.thumbnail_height, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail,
			user.username
			$hook_query_fields
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
		INNER JOIN " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember ON
			(socialgroupmember.userid = a.userid AND socialgroupmember.groupid = $group[groupid] AND socialgroupmember.type = 'member')
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = a.userid)
		$hook_query_joins
		WHERE
			a.contentid = $group[groupid]
				AND
			a.contenttypeid = $contenttypeid
			$hook_query_where
		ORDER BY a.dateline DESC
		LIMIT $start, $perpage
	");

	$picturebits = '';
	$pictures_shown = 0;
	while ($picture = $db->fetch_array($pictures_sql))
	{
		$picture = prepare_pictureinfo_thumb($picture, $group);

		($hook = vBulletinHook::fetch_hook('group_picturebit')) ? eval($hook) : false;

		$templater = vB_Template::create('socialgroups_picturebit');
			$templater->register('group', $group);
			$templater->register('picture', $picture);
			$templater->register('usercss', $usercss);
		$picturebits .= $templater->render();

		$pictures_shown++;
	}
	$db->free_result($pictures_sql);

	$pagenav = construct_page_nav($pagenumber, $perpage, $group['rawpicturecount'], '', '', '',
		'group', $group,  array('do' => 'grouppictures'));

	$show['add_pictures_link'] = ($group['membertype'] == 'member' AND
		$vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canupload']);

	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		'' => $vbphrase['pictures']
	);

	$poststarttime = TIMENOW;
	$posthash = md5($poststarttime . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']);

	// Get group options
	$show['groupoptions'] = false;
	$groupoptions = fetch_groupoptions($group, $show['groupoptions']);

	$page_templater = vB_Template::create('socialgroups_pictures');
	$page_templater->register('group', $group);
	$page_templater->register('pagenav', $pagenav);
	$page_templater->register('picturebits', $picturebits);
	$page_templater->register('pictures_shown', $pictures_shown);
	$page_templater->register('contenttypeid', $contenttypeid);
	$page_templater->register('posthash', $posthash);
	$page_templater->register('poststarttime', $poststarttime);
	$page_templater->register('groupoptions', $groupoptions);
	$page_templater->register('values', "values[groupid]=$group[groupid]");
}

// #######################################################################
if ($_REQUEST['do'] == 'picture')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'attachmentid' => TYPE_UINT,
		'pagenumber'   => TYPE_UINT,
		'perpage'      => TYPE_UINT,
		'commentid'    => TYPE_UINT,
		'showignored'  => TYPE_BOOL,
	));

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	if (!$vbulletin->options['socnet_groups_pictures_enabled'])
	{
		print_no_permission();
	}

	if (!($group['options'] & $vbulletin->bf_misc_socialgroupoptions['enable_group_albums']))
	{
		print_no_permission();
	}

	if ($group['membertype'] != 'member' AND !can_moderate(0, 'caneditgrouppicture'))
	{
		if ($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canjoingroups'] AND can_join_group($group))
		{
			standard_error(fetch_error('must_be_group_member_view_add_pictures_join_x', fetch_seo_url('group', $group, array('do' => 'join'))));
		}
		else
		{
			standard_error(fetch_error('must_be_group_member_view_add_pictures'));
		}
	}

	$pictureinfo = fetch_socialgroup_picture($vbulletin->GPC['attachmentid'], $group['groupid']);
	if (!$pictureinfo)
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	$pictureinfo['adddate'] = vbdate($vbulletin->options['dateformat'], $pictureinfo['dateline'], true);
	$pictureinfo['addtime'] = vbdate($vbulletin->options['timeformat'], $pictureinfo['dateline']);
	$pictureinfo['caption_html'] = nl2br(fetch_word_wrapped_string(fetch_censored_text($pictureinfo['caption'])));

	$pictureurl = create_full_url("attachment.php?attachmentid=$pictureinfo[attachmentid]");
	if (!preg_match('#^[a-z]+://#i', $pictureurl))
	{
		$pictureurl = $vbulletin->options['bburl'] . "/attachment.php?attachmentid=$pictureinfo[attachmentid]";

	}
	$pictureinfo['pictureurl'] = $pictureurl;

	$navpictures_sql = $db->query_read_slave("
		SELECT
			a.attachmentid
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember ON
			(socialgroupmember.userid = a.userid AND socialgroupmember.groupid = $group[groupid] AND socialgroupmember.type = 'member')
		WHERE
			a.contentid = $group[groupid]
				AND
			a.contenttypeid = $contenttypeid
		ORDER BY a.dateline DESC
	");
	$pic_location = fetch_picture_location_info($navpictures_sql, $pictureinfo['attachmentid']);
	$db->free_result($navpictures_sql);

	($hook = vBulletinHook::fetch_hook('group_picture')) ? eval($hook) : false;

	$show['edit_picture_option'] = ($pictureinfo['userid'] == $vbulletin->userinfo['userid'] OR can_moderate(0, 'caneditgrouppicture'));
	$show['remove_picture_option'] = ($pictureinfo['userid'] == $vbulletin->userinfo['userid'] OR fetch_socialgroup_modperm('canremovepicture', $group));

	$show['reportlink'] = (
		$vbulletin->userinfo['userid']
		AND ($vbulletin->options['rpforumid'] OR
			($vbulletin->options['enableemail'] AND $vbulletin->options['rpemail']))
	);

	if ($vbulletin->options['pc_enabled'])
	{
		require_once(DIR . '/includes/functions_picturecomment.php');

		$pagenumber = $vbulletin->GPC['pagenumber'];
		$perpage = $vbulletin->GPC['perpage'];
		$picturecommentbits = fetch_picturecommentbits($pictureinfo, $messagestats, $pagenumber, $perpage, $vbulletin->GPC['commentid'], $vbulletin->GPC['showignored']);

		$pagenavbits = array(
			'do' => 'picture',
			'attachmentid' => $pictureinfo['attachmentid']
		);
		if ($perpage != $vbulletin->options['pc_perpage'])
		{
			$pagenavbits['pp'] = $perpage;
		}
		if ($vbulletin->GPC['showignored'])
		{
			$pagenavbits['showignored'] = '1';
		}

		$pagenav = construct_page_nav($pagenumber, $perpage, $messagestats['total'], '', '', '',
			'group', $group, $pagenavbits);

		$editorid = fetch_picturecomment_editor($pictureinfo, $pagenumber, $messagestats);
		if ($editorid)
		{
			$templater = vB_Template::create('picturecomment_form');
				$templater->register('group', $group);
				$templater->register('allowed_bbcode', $allowed_bbcode);
				$templater->register('editorid', $editorid);
				$templater->register('group', $group);
				$templater->register('messagearea', $messagearea);
				$templater->register('messagestats', $messagestats);
				$templater->register('pictureinfo', $pictureinfo);
				$templater->register('vBeditTemplate', $vBeditTemplate);
			$picturecomment_form = $templater->render();
		}
		else
		{
			$picturecomment_form = '';
		}

		$show['picturecomment_options'] = ($picturecomment_form OR $picturecommentbits);

		$templater = vB_Template::create('picturecomment_commentarea');
			$templater->register('messagestats', $messagestats);
			$templater->register('pagenav', $pagenav);
			$templater->register('picturecommentbits', $picturecommentbits);
			$templater->register('picturecomment_form', $picturecomment_form);
			$templater->register('pictureinfo', $pictureinfo);
		$picturecomment_commentarea = $templater->render();
	}
	else
	{
		$picturecomment_commentarea = '';
	}

	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		fetch_seo_url('group', $group, array('do' => 'grouppictures')) => $vbphrase['pictures'],
		'' => construct_phrase($vbphrase['picture_x_of_y_from_group_z'], $pic_location['pic_position'], $group['picturecount'], $group['name'])
	);

	$page_templater = vB_Template::create('socialgroups_picture');
	$page_templater->register('group', $group);
	$page_templater->register('picturecomment_commentarea', $picturecomment_commentarea);
	$page_templater->register('pictureinfo', $pictureinfo);
	$page_templater->register('pic_location', $pic_location);
}

// #######################################################################
if ($_POST['do'] == 'sendinvite')
{
	if (!fetch_socialgroup_modperm('caninvitemoderatemembers', $group))
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'username'	=> TYPE_NOHTML
	));

	if ($user = $vbulletin->db->query_first("
		SELECT * FROM " . TABLE_PREFIX . "user
		WHERE username = '" . $vbulletin->db->escape_string($vbulletin->GPC['username']) . "'"
	))
	{
		cache_permissions($user);

		if ($currentmembership = $vbulletin->db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "socialgroupmember
			WHERE userid = " . $user['userid'] . " AND groupid = " . $group['groupid']
		))
		{
			if ($currentmembership['type'] == 'member')
			{
				$errormsg = $vbphrase['this_person_is_already_a_member_of_the_group'];
				$invite_username = $vbulletin->GPC['username'];
				$_REQUEST['do'] = 'manage';
			}
			else if (!($user['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canjoingroups']) OR
			!($user['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups']))
			{
				$errormsg = $vbphrase['this_user_is_not_allowed_to_join_groups'];
				$invite_username = $vbulletin->GPC['username'];
				$_REQUEST['do'] = 'manage';
			}
			else
			{
				$socialgroupmemberdm = datamanager_init('SocialGroupMember', $vbulletin);
				$socialgroupmemberdm->set_existing($currentmembership);
				$socialgroupmemberdm->set('type', 'invited');
				$socialgroupmemberdm->set('dateline', TIMENOW);
				$socialgroupmemberdm->save();
				unset($socialgroupmemberdm);

				if (!$group['is_owner'] AND can_moderate(0, 'candeletesocialgroups'))
				{
					require_once(DIR . '/includes/functions_log_error.php');
					log_moderator_action($group, 'social_group_x_members_managed',
						array($group['name'])
					);
				}

				$vbulletin->url = fetch_seo_url('group', $group, array('do' => 'manage'));
				print_standard_redirect('successfully_invited_user');
			}
		}
		else
		{
			if (!($user['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canjoingroups']) OR
			!($user['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups']))
			{
				$errormsg = $vbphrase['this_user_is_not_allowed_to_join_groups'];
				$invite_username = $vbulletin->GPC['username'];
				$_REQUEST['do'] = 'manage';
			}
			else
			{
				$socialgroupmemberdm = datamanager_init('SocialGroupMember', $vbulletin);
				$socialgroupmemberdm->set('userid', $user['userid']);
				$socialgroupmemberdm->set('groupid', $group['groupid']);
				$socialgroupmemberdm->set('type', 'invited');
				$socialgroupmemberdm->set('dateline', TIMENOW);
				$socialgroupmemberdm->save();
				unset($socialgroupmemberdm);

				if (!$group['is_owner'] AND can_moderate(0, 'candeletesocialgroups'))
				{
					require_once(DIR . '/includes/functions_log_error.php');
					log_moderator_action($group, 'social_group_x_members_managed',
						array($group['name'])
					);
				}

				$vbulletin->url = fetch_seo_url('group', $group, array('do' => 'manage'));
				print_standard_redirect('successfully_invited_user');
			}
		}
	}
	else
	{
		$errormsg = $vbphrase['user_does_not_exist'];
		$invite_username = $vbulletin->GPC['username'];
		$_REQUEST['do'] = 'manage';
	}
}

// ############# Permission checks for group management ##################

if ($_REQUEST['do'] == 'manage' OR $_POST['do'] == 'cancelinvites')
{
	if (!fetch_socialgroup_modperm('caninvitemoderatemembers', $group))
	{
		print_no_permission();
	}
}

if ($_REQUEST['do'] == 'managemembers' OR $_POST['do'] == 'kickmembers')
{
	if (!fetch_socialgroup_modperm('canmanagemembers', $group))
	{
		print_no_permission();
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'manage' OR $_REQUEST['do'] == 'managemembers')
{
	$members = $vbulletin->db->query_read("
		SELECT socialgroupmember.*, user.username
		FROM " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = socialgroupmember.userid)
		WHERE groupid = $group[groupid]
			" . ($_REQUEST['do'] == 'managemembers' ? "AND type = 'member'" : "AND type <> 'member'") . "
		ORDER BY user.username
	");

	$invitebits = '';
	$moderatebits = '';
	$memberbits = '';
	$i = 0;

	while ($user = $vbulletin->db->fetch_array($members))
	{
		($hook = vBulletinHook::fetch_hook('group_manage_memberbit')) ? eval($hook) : false;

		if ($user['type'] == 'invited')
		{
			$container = 'invitedlist';
			$templater = vB_Template::create('socialgroups_managebit');
				$templater->register('container', $container);
				$templater->register('user', $user);
			$invitebits .= $templater->render();
		}
		else if ($user['type'] == 'moderated')
		{
			$container = 'moderatedlist';
			$templater = vB_Template::create('socialgroups_managebit');
				$templater->register('container', $container);
				$templater->register('user', $user);
			$moderatebits .= $templater->render();
		}
		else if ($user['userid'] != $group['creatoruserid'])
		{
			$templater = vB_Template::create('socialgroups_managebit');
				$templater->register('container', $container);
				$templater->register('user', $user);
			$memberbits .= $templater->render();
		}
	}
	$vbulletin->db->free_result($members);

	$show['manage_members'] = ($_REQUEST['do'] == 'managemembers');

	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('groupcategory', $group) => $group['categoryname'],
		fetch_seo_url('group', $group) => $group['name'],
		'' => $show['manage_members'] ? $vbphrase['manage_members'] : $vbphrase['pending_and_invited_members']
	);

	if ($_REQUEST['do'] == 'managemembers')
	{
		$page_templater = vB_Template::create('socialgroups_managemembers');
		$page_templater->register('group', $group);
		$page_templater->register('memberbits', $memberbits);
	}
	else
	{
		$page_templater = vB_Template::create('socialgroups_manage');
		$page_templater->register('errormsg', $errormsg);
		$page_templater->register('group', $group);
		$page_templater->register('invitebits', $invitebits);
		$page_templater->register('invite_username', $invite_username);
		$page_templater->register('moderatebits', $moderatebits);
	}
}

// #######################################################################
if ($_POST['do'] == 'cancelinvites' OR $_POST['do'] == 'kickmembers')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'ids'	=> TYPE_ARRAY_KEYS_INT
	));

	if (sizeof($vbulletin->GPC['ids']) > 0)
	{
		$socialgroupmemberdm = datamanager_init('SocialGroupMember', $vbulletin);

		$vbulletin->GPC['ids'][] = 0;
		$ids = implode(', ', $vbulletin->GPC['ids']);

		$invites = $vbulletin->db->query_read("
			SELECT * FROM " . TABLE_PREFIX . "socialgroupmember
			WHERE groupid = " . $group['groupid'] . " AND userid IN($ids)" .
			($_POST['do'] == 'cancelinvites' ? " AND type = 'invited'" : '')
		);

		while ($invite = $vbulletin->db->fetch_array($invites))
		{
			($hook = vBulletinHook::fetch_hook('group_kickmember')) ? eval($hook) : false;

			if ($invite['userid'] != $group['creatoruserid'])
			{
				$socialgroupmemberdm->set_existing($invite);
				$socialgroupmemberdm->delete();
			}
		}
		$vbulletin->db->free_result($invites);
		unset ($socialgroupmemberdm);
	}

	if (!$group['is_owner'] AND can_moderate(0, 'candeletesocialgroups'))
	{
		require_once(DIR . '/includes/functions_log_error.php');
		log_moderator_action($group, 'social_group_x_members_managed',
			array($group['name'])
		);
	}

	if (($group['members'] - sizeof($ids) <= 1) AND $_REQUEST['do'] == 'kickmembers')
	{
		$vbulletin->url = fetch_seo_url('group', $group);
	}
	else
	{

		if ($_REQUEST['do'] == 'kickmembers')
		{
			$pagevars = array('do' => 'managemembers');
		}
		else
		{
			$pagevars = array('do' => 'manage');
		}

		$vbulletin->url = fetch_seo_url('group', $group, $pagevars);
	}

	($hook = vBulletinHook::fetch_hook('group_kickmember_complete')) ? eval($hook) : false;

	$phrase = $_POST['do'] == 'cancelinvites' ? 'successfully_removed_invites' : 'successfully_kicked_members';
	print_standard_redirect($phrase);
}

// #######################################################################
if ($_POST['do'] == 'pendingmembers')
{
	if (!fetch_socialgroup_modperm('caninvitemoderatemembers', $group))
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'ids'	  => TYPE_ARRAY_KEYS_INT,
		'action'  => TYPE_STR
	));

	$vbulletin->GPC['ids'][] = 0;

	$ids = implode(', ', $vbulletin->GPC['ids']);

	$members = $vbulletin->db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "socialgroupmember
		WHERE groupid = " . $group['groupid'] . " AND type = 'moderated' AND userid IN ($ids)
	");

	while ($member = $vbulletin->db->fetch_array($members))
	{
		$socialgroupmemberdm = datamanager_init('SocialGroupMember', $vbulletin);
		$socialgroupmemberdm->set_existing($member);

		($hook = vBulletinHook::fetch_hook('group_pending_members')) ? eval($hook) : false;

		if ($vbulletin->GPC['action'] == 'deny')
		{
			$socialgroupmemberdm->delete();
		}
		else if ($vbulletin->GPC['action'] == 'accept')
		{
			$socialgroupmemberdm->set('type', 'member');
			$socialgroupmemberdm->save();
		}
		unset($socialgroupmemberdm);
	}
	$vbulletin->db->free_result($members);

	$vbulletin->url = fetch_seo_url('group', $group, array('do' => 'manage'));

	($hook = vBulletinHook::fetch_hook('group_pending_members_complete')) ? eval($hook) : false;

	print_standard_redirect('successfully_managed_members');
}

// #######################################################################
if ($_REQUEST['do'] == 'quickedit')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'editorid' => TYPE_NOHTML,
	));

	require_once(DIR . '/includes/class_xml.php');
	require_once(DIR . '/includes/functions_editor.php');

	$editorid = construct_edit_toolbar(
		htmlspecialchars_uni($messageinfo['pagetext']),
		false,
		'groupmessage',
		true,
		true,
		false,
		'qe',
		$vbulletin->GPC['editorid'],
		array(),
		'content',
		'vBForum_SocialGroupMessage',
		$messageinfo['gmid']
	);

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	$xml->add_group('quickedit');
	$xml->add_tag('editor', process_replacement_vars($messagearea), array(
		'reason'       => '',
		'parsetype'    => 'groupmessage',
		'parsesmilies' => true,
		'mode'         => $show['is_wysiwyg_editor']
	));
	$xml->add_tag('ckeconfig', vB_Ckeditor::getInstance($editorid)->getConfig());
	$xml->close_group();

	$xml->print_xml();
}

// #######################################################################
if ($_POST['do'] == 'updateicon')
{
	if (!$vbulletin->options['sg_enablesocialgroupicons'])
	{
		eval(standard_error(fetch_error('socialgroupiconsdisabled')));
	}

	if (!fetch_socialgroup_perm('canuploadgroupicon'))
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'deletegroupicon' => TYPE_BOOL,
		'iconurl'      => TYPE_STR,
		'icononly'		=> TYPE_BOOL,
		'skip'			=> TYPE_STR
	));

	if (!$vbulletin->GPC['skip'])
	{
		($hook = vBulletinHook::fetch_hook('group_update_groupicon_start')) ? eval($hook) : false;

		if ($vbulletin->GPC['deletegroupicon'])
		{
			$groupicon =& datamanager_init('SocialGroupIcon', $vbulletin, ERRTYPE_STANDARD);
			$groupicon->condition = "groupid = " . $group['groupid'];
			$groupicon->delete();
			unset($groupicon);
		}
		else
		{
			$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);

			require_once(DIR . '/includes/class_upload.php');
			require_once(DIR . '/includes/class_image.php');

			$upload = new vB_Upload_SocialGroupIcon($vbulletin);

			$upload->data =& datamanager_init('SocialGroupIcon', $vbulletin, ERRTYPE_STANDARD);
			$upload->image =& vB_Image::fetch_library($vbulletin);
			$upload->set_group_info($group);
			$upload->maxwidth = FIXED_SIZE_GROUP_ICON_WIDTH;
			$upload->maxheight = FIXED_SIZE_GROUP_ICON_HEIGHT;
			$upload->maxuploadsize = $vbulletin->userinfo['permissions']['groupiconmaxsize'];
			$upload->allowanimation = fetch_socialgroup_perm('cananimategroupicon');

			if (!$upload->process_upload($vbulletin->GPC['iconurl']))
			{
				eval(standard_error($upload->fetch_error()));
			}

			unset($upload);
		}

		($hook = vBulletinHook::fetch_hook('group_update_groupicon_complete')) ? eval($hook) : false;
	}

	if ($vbulletin->GPC['icononly'])
	{
		$vbulletin->url = fetch_seo_url('group', $group);
		print_standard_redirect('successfully_created_group');
	}

	print_standard_redirect(array('redirect_updatethanks',$vbulletin->userinfo['username']));
}

// #######################################################################
if (!empty($page_templater))
{
	($hook = vBulletinHook::fetch_hook('group_complete')) ? eval($hook) : false;

	// make navbar
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);
	$custompagetitle = empty($custompagetitle) ? $pagetitle : $custompagetitle;

	$page_templater->register_page_templates();
	$page_templater->register('custompagetitle', $custompagetitle);
	$page_templater->register('navbar', $navbar);

	print_output($page_templater->render());
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 64711 $
|| ####################################################################
\*======================================================================*/
