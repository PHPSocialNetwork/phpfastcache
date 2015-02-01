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
define('THIS_SCRIPT', 'groupsubscription');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'user',
	'forumdisplay',
	'socialgroups'
);

// get special data templates from the datastore
$specialtemplates = array(
	'iconcache',
	'noavatarperms'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'USERCP_SHELL'
);

$actiontemplates = array(
	'viewsubscription' => array(
		'forumdisplay_sortarrow',
		'socialgroups_discussion',
		'socialgroups_groupsub_bit',
		'socialgroups_subscriptions',
		'usercp_nav_folderbit'
	)
);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'viewsubscription';
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_user.php');
require_once(DIR . '/includes/functions_socialgroup.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_forum_url();

$vbulletin->input->clean_array_gpc('r', array(
	'groupid'		=> TYPE_UINT,
	'gmid'    		=> TYPE_UINT,
	'discussionid'	=> TYPE_UINT
));

if ((!$vbulletin->userinfo['userid'] AND $_REQUEST['do'] != 'unsubscribe')
	OR ($vbulletin->userinfo['userid'] AND !($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
	OR $vbulletin->userinfo['usergroupid'] == 4
	OR !($permissions['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
{
	print_no_permission();
}

// start the navbits breadcrumb
$navbits = array('usercp.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['user_control_panel']);

// ############################### start view threads ###############################
if ($_REQUEST['do'] == 'viewsubscription')
{
	if (!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_groups'])
		OR !($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups'])
		OR !$vbulletin->options['socnet_groups_msg_enabled']
	)
	{
		print_no_permission();
	}

	// Get pagenav
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'sort'       => TYPE_NOHTML,
	    'order'      => TYPE_NOHTML
	));
	$perpage = (!$vbulletin->GPC['perpage'] OR $vbulletin->GPC['perpage'] > $vbulletin->options['sg_maxperpage']) ? $vbulletin->options['sg_perpage'] : $vbulletin->GPC['perpage'];

	// get sorting
	$desc = ('asc' == $vbulletin->GPC['order']) ? false : true;
	$sortfield = $vbulletin->GPC['sort'];

	// Create message collection
	require_once(DIR . '/includes/class_groupmessage.php');
	$collection_factory = new vB_Group_Collection_Factory($vbulletin);
	$collection = $collection_factory->create('discussion', false, $vbulletin->GPC['pagenumber'], $perpage, $desc);

	$collection->set_ignore_marking(false);
	$collection->filter_show_unsubscribed(false);
	$collection->filter_sort_field($sortfield);

	// Get counts
	list($start, $end, $shown, $totaldiscussions) = array_values($collection->fetch_counts());
	$pagenumber = $collection->fetch_pagenumber();

	// Check if the user is subscribed to any discussions
	if ($totaldiscussions)
	{
		// Create bit factory
		$bit_factory = new vB_Group_Bit_Factory($vbulletin, $itemtype);

		// Build message bits for all items
		$messagebits = '';
		while ($item = $collection->fetch_item())
		{
			$group = fetch_socialgroupinfo($item['groupid']);

			// add group name to message
			$group['name'] = fetch_word_wrapped_string(fetch_censored_text($group['name']));

			// force everything to be visible
			if ('deleted' == $item['state'])
			{
				$item['state'] = 'visible';
			}

			// add bit
			$bit =& $bit_factory->create($item, $group);
			$bit->show_moderation_tools(false);
			$bit->show_subscription(true);

			// always show inline selection
			$bit->force_inline_selection(true);

			$discussionbits .= $bit->construct();
		}
	}
	unset($bit, $bit_factory, $collection_factory, $collection);


	$pagevars = array();
	if ($perpage)
	{
		$pagevars['pp'] = $perpage;
	}
	if ($sortfield)
	{
		$pagevars['sort'] = $sortfield;
	}
	if (!$desc)
	{
		$pagevars['order'] = 'asc';
	}
	


	// Construct pagenav
	$pagenav = construct_page_nav($pagenumber, $perpage, $totaldiscussions, '', '', '', 'groupsub', array(), $pagevars);

	// Sort helpers
	$oppositesort = $desc ? 'asc' : 'desc';
	$orderlinks = array();
	$sorts = array('replies', 'dateline', 'lastpost', 'subscription');
	foreach ($sorts as $sort)
	{
		$pagevars['sort'] = $sort;
		unset($pagevars['order']);
		if ($sortfield == $sort)
		{
			$pagevars['order'] = $oppositesort;
		}

		$orderlinks[$sort] = fetch_seo_url('groupsub', array(), $pagevars);
	}

	$templater = vB_Template::create('forumdisplay_sortarrow');
		$templater->register('oppositesort', $oppositesort);
	$sortarrow["$sortfield"] = $templater->render();

	$group_subscribe_list = '';

	if ($pagenumber <= 1)
	{
		// show group subscriptions on page one
		require_once(DIR . '/includes/class_socialgroup_search.php');

		$socialgroupsearch = new vB_SGSearch($vbulletin);
		$socialgroupsearch->add('subscribed', $vbulletin->userinfo['userid']);
		$socialgroupsearch->set_sort('lastpost', 'ASC');

		if ($numsocialgroups = $socialgroupsearch->execute(true))
		{
			foreach ($socialgroupsearch->fetch_results() AS $group)
			{
				$group = prepare_socialgroup($group);
				$show['lastpostinfo'] = ($group['lastpost'] ? true : false);

				switch ($group['emailupdate'])
				{
					case 'daily': $group['notification'] = $vbphrase['daily']; break;
					case 'weekly': $group['notification'] = $vbphrase['weekly']; break;

					case 'none':
					default:
						$group['notification'] = $vbphrase['none'];
				}

				$templater = vB_Template::create('socialgroups_groupsub_bit');
					$templater->register('group', $group);
				$group_subscribe_list .= $templater->render();
			}
		}
	}

	// Construct navbits
	$navbits = array(
		'usercp.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['user_control_panel'], 
		'' => $vbphrase['group_subscriptions']
	);
	$navbits = construct_navbits($navbits);

	// Construct cp nav
	construct_usercp_nav('socialgroups');

	//this creates the "$forumjump" global parameter, but this doesn't appear to be used anywhere
	//on the page.
	$navpopup = array(
		'id'    => 'groupsub_navpopup',
		'title' => $vbphrase['group_subscriptions'],
		'link'  => fetch_seo_url('groupsub', array()),
	);
	construct_quick_nav($navpopup);

	$navbar = render_navbar_template($navbits);
	$templater = vB_Template::create('socialgroups_subscriptions');
		$templater->register('discussionbits', $discussionbits);
		$templater->register('forumjump', $forumjump);
		$templater->register('gobutton', $gobutton);
		$templater->register('group_subscribe_list', $group_subscribe_list);
		$templater->register('orderlinks', $orderlinks);
		$templater->register('pagenav', $pagenav);
		$templater->register('pagenumber', $pagenumber);
		$templater->register('perpage', $perpage);
		$templater->register('sortarrow', $sortarrow);
		$templater->register('totaldiscussions', $totaldiscussions);
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

// ########################## Bulk Subscription Updates ##############################
if ($_POST['do'] == 'noemail' OR $_POST['do'] == 'instantemail' OR $_POST['do'] == 'delete')
{
	if (!($discussionlist = $vbulletin->input->clean_gpc('r', 'gdiscussionlist', TYPE_ARRAY_KEYS_INT)))
	{
		standard_error(fetch_error('you_did_not_select_any_valid_discussions'));
	}

	$discussionlist = implode(',', $vbulletin->GPC['gdiscussionlist']);
}

// ########################## Delete Subscriptions ###################################
if ($_POST['do'] == 'delete')
{
	// Set message state
	$vbulletin->db->query_write("
		DELETE FROM " . TABLE_PREFIX . "subscribediscussion
		WHERE discussionid IN ($discussionlist)
		AND userid = " . $vbulletin->userinfo['userid']
	);

	print_standard_redirect('subupdate');  
}

// ########################## Update Subscription ###################################
if ($_POST['do'] == 'noemail' OR $_POST['do'] == 'instantemail')
{
	// Set message state
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "subscribediscussion
		SET emailupdate = '" . ($_POST['do'] == 'noemail' ? 0 : 1) . "'
		WHERE discussionid IN ($discussionlist)
		AND userid = " . $vbulletin->userinfo['userid']
	);

	print_standard_redirect('subupdate');  
}

// ###############################################################
if ($_POST['do'] == 'updategroup')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'grouplist' => TYPE_ARRAY_UINT,
		'act' => TYPE_STR
	));

	if ($vbulletin->GPC['grouplist'])
	{
		$grouplist = implode(',', $vbulletin->GPC['grouplist']);

		$update_type = '';

		switch ($vbulletin->GPC['act'])
		{
			case 'delete':
				$vbulletin->db->query_write("
					DELETE FROM " . TABLE_PREFIX . "subscribegroup
					WHERE groupid IN ($grouplist)
						AND userid = " . $vbulletin->userinfo['userid']
				);
				break;

			case 'daily':
			case 'weekly':
			case 'none':
				$update_type = $vbulletin->GPC['act'];
		}

		if ($update_type)
		{
			$vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "subscribegroup
				SET emailupdate = '" . $db->escape_string($update_type) . "'
				WHERE groupid IN ($grouplist)
					AND userid = " . $vbulletin->userinfo['userid']
			);
		}
	}

	print_standard_redirect('subupdate');  
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 26399 $
|| ####################################################################
\*======================================================================*/
?>
