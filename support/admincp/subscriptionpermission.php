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
define('CVS_REVISION', '$RCSfile$ - $Revision: 32878 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('subscription');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_paid_subscription.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'subscriptionpermissionid' => TYPE_INT,
	'subscriptionid'           => TYPE_INT,
	'usergroupid'              => TYPE_INT
));
log_admin_action(iif($vbulletin->GPC['subscriptionpermissionid'] != 0, "subscriptionpermission id = " . $vbulletin->GPC['subscriptionpermissionid'],
					iif($vbulletin->GPC['subscriptionid'] != 0, "subscription id = ". $vbulletin->GPC['subscriptionid'] .
						iif($vbulletin->GPC['usergroupid'] != 0, " / usergroup id = " . $vbulletin->GPC['usergroupid']))));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['subscription_permissions_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

$subobj = new vB_PaidSubscription($vbulletin);
$subobj->cache_user_subscriptions();

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit')
{
	print_form_header('subscriptionpermission', 'doupdate');

	if (empty($subobj->subscriptioncache[$vbulletin->GPC['subscriptionid']]))
	{
		print_stop_message('invalid_x_specified', $vbphrase['subscription']);
	}

	if (empty($vbulletin->usergroupcache[$vbulletin->GPC['usergroupid']]))
	{
		print_stop_message('invalid_x_specified', $vbphrase['usergroup']);
	}

	$getperms = $db->query_first("
		SELECT subscriptionpermission.*
		FROM " . TABLE_PREFIX . "subscriptionpermission AS subscriptionpermission
		INNER JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroup.usergroupid = subscriptionpermission.usergroupid)
		WHERE subscriptionid = " . $vbulletin->GPC['subscriptionid'] . " AND subscriptionpermission.usergroupid = " . $vbulletin->GPC['usergroupid']
	);
	$usergroup = $vbulletin->usergroupcache[$vbulletin->GPC['usergroupid']];

	$subtitle = $vbphrase['sub' . $vbulletin->GPC['subscriptionid'] . '_title'];
	construct_hidden_code('subscriptionid', $vbulletin->GPC['subscriptionid']);
	construct_hidden_code('usergroupid', $vbulletin->GPC['usergroupid']);
	print_table_header(construct_phrase($vbphrase['edit_usergroup_permissions_for_usergroup_x_in_subscription_y'], $usergroup['title'], $subtitle));
	print_yes_no_row($vbphrase['can_use_subscription'], 'usesub', !$getperms);

	print_submit_row($vbphrase['save']);
}

// ###################### Start do update #######################
if ($_POST['do'] == 'doupdate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usesub'                   => TYPE_BOOL,
		'subscriptionpermissionid' => TYPE_INT,
		'subscriptionid'           => TYPE_INT,
		'usergroupid'              => TYPE_INT

	));

	if (empty($subobj->subscriptioncache[$vbulletin->GPC['subscriptionid']]))
	{
		print_stop_message('invalid_x_specified', $vbphrase['subscription']);
	}

	if (empty($vbulletin->usergroupcache[$vbulletin->GPC['usergroupid']]))
	{
		print_stop_message('invalid_x_specified', $vbphrase['usergroup']);
	}

	define('CP_REDIRECT', "subscriptionpermission.php?do=modify#subscription" . $vbulletin->GPC['subscriptionid']);

	if ($vbulletin->GPC['usesub'])
	{
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "subscriptionpermission
			WHERE subscriptionid = " . $vbulletin->GPC['subscriptionid'] . " AND usergroupid = " . $vbulletin->GPC['usergroupid']
		);
		if ($db->affected_rows())
		{
			print_stop_message('deleted_subscription_permissions_successfully');
		}
		else
		{
			print_stop_message('updated_subscription_permissions_successfully');
		}
	}
	else
	{
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "subscriptionpermission
			(usergroupid, subscriptionid)
			VALUES
			(" . $vbulletin->GPC['usergroupid'] . ", " . $vbulletin->GPC['subscriptionid'] . ")
		");

		print_stop_message('saved_usergroup_permissions_successfully');
	}
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{

	print_form_header('', '');
	print_table_header($vbphrase['subscription_permissions']);
	print_description_row('
		<div class="darkbg" style="border: 2px inset">	<ul class="darkbg">
		<li><b>' . $vbphrase['color_key'] . '</b></li>
		<li class="col-g">' . $vbphrase['allowed_can_access_subscription'] . '</li>
		<li class="col-c">' . $vbphrase['denied_can_not_access_subscription'] . '</li>
		</ul></div>
	');

	print_table_footer();

	if (empty($subobj->subscriptioncache))
	{
		print_stop_message('nosubscriptions', $vbulletin->options['bbtitle']);
	}

	// query subscription permissions
	$subscriptionpermissions = $db->query_read("
		SELECT usergroupid, subscriptionid
		FROM " . TABLE_PREFIX . "subscriptionpermission AS subscriptionpermission
	");

	$permscache = array();
	while ($sperm = $db->fetch_array($subscriptionpermissions))
	{
		$permscache["{$sperm['subscriptionid']}"]["{$sperm['usergroupid']}"] = true;
	}

	echo '<center><div class="tborder" style="width: 89%">';
	echo '<div class="alt1" style="padding: 8px">';
	echo '<div class="darkbg" style="padding: 4px; border: 2px inset; text-align: ' . vB_Template_Runtime::fetchStyleVar('left') . '">';

	$ident = '   ';
	echo "$indent<ul class=\"lsq\">\n";
	foreach ($subobj->subscriptioncache AS $subscriptionid => $subscription)
	{
		$title = $vbphrase['sub' . $subscriptionid . '_title'];
		// forum title and links
		echo "$indent<li><b><a name=\"subscription$subscriptionid\" href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;subscriptionid=$subscriptionid\">$title</a></b>";
		echo "$indent\t<ul class=\"usergroups\">\n";
		foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
		{
			if (!empty($permscache["$subscriptionid"]["$usergroupid"]))
			{
				$class = ' class="col-c"';
			}
			else
			{
				$class = '';
			}
			$link = "subscriptionid=$subscriptionid&amp;usergroupid=$usergroupid";

			echo "$indent\t<li$class>" . construct_link_code($vbphrase['edit'], "subscriptionpermission.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;$link") . $usergroup['title'] . "</li>\n";

			unset($permscache["$subscriptionid"]["$usergroupid"]);
		}
		echo "$indent\t</ul><br />\n";
		echo "$indent</li>\n";
	}
	echo "$indent</ul>\n";

	echo "</div></div></div></center>";
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>
