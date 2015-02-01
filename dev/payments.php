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
define('THIS_SCRIPT', 'payments');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('subscription', 'user');

// get special data templates from the datastore
$specialtemplates = array('noavatarperms');

// pre-cache templates used by all actions
$globaltemplates = array('USERCP_SHELL','usercp_nav_folderbit');

// pre-cache templates used by specific actions
$actiontemplates = array(
	'none' => array(
		'subscription',
		'subscription_activebit',
		'subscription_availablebit'
	),
	'order' => array(
		'subscription_payment',
		'subscription_paymentbit',
		'subscription_payment_2checkout',
		'subscription_payment_paypal',
		'subscription_payment_nochex',
		'subscription_payment_worldpay',
		'subscription_payment_authorizenet',
	)
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_paid_subscription.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if ($vbulletin->userinfo['userid'] == 0)
{
	print_no_permission();
}

// start the navbar
$navbits = array('usercp.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['user_control_panel']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'list';
}

$includecss = array('payments' => 'payments.css');

$subobj = new vB_PaidSubscription($vbulletin);

$subscribed = array();
// fetch all active subscriptions the user is subscribed too
$susers = $db->query_read_slave("
	SELECT *
	FROM " . TABLE_PREFIX . "subscriptionlog
	WHERE status = 1
	AND userid = " . $vbulletin->userinfo['userid']
);
while ($suser = $db->fetch_array($susers))
{
	$subscribed["$suser[subscriptionid]"] = $suser;
}

// cache all the subscriptions
$subobj->cache_user_subscriptions();

$apicache = array();
$paymentapi = array();
// get the settings for all the API stuff
$paymentapis = $db->query_read_slave("
	SELECT *
	FROM " . TABLE_PREFIX . "paymentapi
	WHERE active = 1
");
while ($paymentapi = $db->fetch_array($paymentapis))
{
	$apicache["$paymentapi[classname]"] = $paymentapi;
}

if (empty($subobj->subscriptioncache) OR empty($apicache) OR !$vbulletin->options['subscriptionmethods'])
{
	eval(standard_error(fetch_error('nosubscriptions', $vbulletin->options['bbtitle'])));
}

($hook = vBulletinHook::fetch_hook('paidsub_start')) ? eval($hook) : false;
$lengths = array(
	'D' => $vbphrase['day'],
	'W' => $vbphrase['week'],
	'M' => $vbphrase['month'],
	'Y' => $vbphrase['year'],
	// plural stuff below
	'Ds' => $vbphrase['days'],
	'Ws' => $vbphrase['weeks'],
	'Ms' => $vbphrase['months'],
	'Ys' => $vbphrase['years']
);

// #############################################################################

if ($_REQUEST['do'] == 'list')
{

	$subscribedbits = '';
	$subscriptionbits = '';

	$membergroupids = fetch_membergroupids_array($vbulletin->userinfo);
	$allow_secondary_groups = $vbulletin->bf_ugp_genericoptions['allowmembergroups'] & 
	$vbulletin->usergroupcache[$vbulletin->userinfo['usergroupid']]['genericoptions'];

	($hook = vBulletinHook::fetch_hook('paidsub_list_start')) ? eval($hook) : false;

	foreach ($subobj->subscriptioncache AS $subscription)
	{
		$subscriptionid =& $subscription['subscriptionid'];

		if (isset($subscribed["$subscription[subscriptionid]"]))
		{
			$joindate = vbdate($vbulletin->options['dateformat'], $subscribed["$subscription[subscriptionid]"]['regdate'], false);
			$enddate = vbdate($vbulletin->options['dateformat'], $subscribed["$subscription[subscriptionid]"]['expirydate'], false);

			$gotsubscriptions = true;
			$subscription['title'] = $vbphrase['sub' . $subscriptionid . '_title'];

			($hook = vBulletinHook::fetch_hook('paidsub_list_activebit')) ? eval($hook) : false;

			$templater = vB_Template::create('subscription_activebit');
				$templater->register('enddate', $enddate);
				$templater->register('joindate', $joindate);
				$templater->register('subscription', $subscription);
			$subscribedbits .= $templater->render();

		}

		if ($subscription['active'])
		{
			if (isset($subscribed["$subscription[subscriptionid]"]))
			{
				if ($subobj->fetch_proper_expirydate($subscribed["$subscription[subscriptionid]"]['expirydate'], $subscription['length'], $subscription['units']) == -1)
				{
					continue;
				}
			}

			if (
				!empty($subscription['deniedgroups'])
				AND
				(
					($allow_secondary_groups AND !count(array_diff($membergroupids, $subscription['deniedgroups'])))
					OR
					(!$allow_secondary_groups AND in_array($vbulletin->userinfo['usergroupid'], $subscription['deniedgroups']))
				)
			)
			{
					continue;
			}

			$subscription['cost'] = unserialize($subscription['cost']);
			$string = '<option value="">--------</option>';
			foreach ($subscription['cost'] AS $key => $currentsub)
			{
				if ($currentsub['length'] == 1)
				{
					$currentsub['units'] = $lengths["{$currentsub['units']}"];
				}
				else
				{
					$currentsub['units'] = $lengths[$currentsub['units'] . 's'];
				}
				$string .= "<optgroup label=\"" . construct_phrase($vbphrase['length_x_units_y_recurring_z'], $currentsub['length'], $currentsub['units'], ($currentsub['recurring'] ? ' *' : '')) . "\">\n";
				foreach ($currentsub['cost'] AS $currency => $value)
				{
					if ($value > 0)
					{
						$string .= "<option value=\"{$key}_{$currency}\" >" . $subobj->_CURRENCYSYMBOLS["$currency"] . vb_number_format($value, 2) . "</option>\n";
					}
				}
				$string .= "</optgroup>\n";
			}

			$subscription['cost'] = $string;
			$subscription['title'] = $vbphrase['sub' . $subscription['subscriptionid'] . '_title'];
			$subscription['description'] = $vbphrase['sub' . $subscription['subscriptionid'] . '_desc'];

			($hook = vBulletinHook::fetch_hook('paidsub_list_availablebit')) ? eval($hook) : false;

			$templater = vB_Template::create('subscription_availablebit');
				$templater->register('subscription', $subscription);
				$templater->register('subscriptionid', $subscriptionid);
			$subscriptionbits .= $templater->render();
		}
	}

	if ($subscribedbits == '')
	{
		$show['activesubscriptions'] = false;
	}
	else
	{
		$show['activesubscriptions'] = true;
	}

	if ($subscriptionbits == '')
	{
		$show['subscriptions'] = false;
	}
	else
	{
		$show['subscriptions'] = true;
	}

	if (!empty($apicache))
	{
		$paymentlink = true;
	}
	else
	{
		$paymentlink = false;
	}

	if (!$subscribedbits AND !$subscriptionbits)
	{
		standard_error(fetch_error('nosubscriptions', $vbulletin->options['bbtitle']));
	}

	$navbits[''] = $vbphrase['paid_subscriptions'];

	$page_templater = vB_Template::create('subscription');
	$page_templater->register('subscribedbits', $subscribedbits);
	$page_templater->register('subscriptionbits', $subscriptionbits);
}

// #############################################################################

if ($_POST['do'] == 'order')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'subscriptionids'	=> TYPE_ARRAY_NOHTML,
		'currency'			=> TYPE_ARRAY_NOHTML,
	));

	if (empty($vbulletin->GPC['subscriptionids']))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['subscription'], $vbulletin->options['contactuslink'])));
	}
	else
	{
		$subscriptionid = array_keys($vbulletin->GPC['subscriptionids']);
		$subscriptionid = intval($subscriptionid[0]);
	}

	$sub = $subobj->subscriptioncache["$subscriptionid"];

	// first check this is active if not die
	if (!$sub['active'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['subscription'], $vbulletin->options['contactuslink'])));
	}

	$membergroupids = fetch_membergroupids_array($vbulletin->userinfo);
	$allow_secondary_groups = $vbulletin->bf_ugp_genericoptions['allowmembergroups'] & 
	$vbulletin->usergroupcache[$vbulletin->userinfo['usergroupid']]['genericoptions'];

	if (
		!empty($sub['deniedgroups'])
		AND
		(
			($allow_secondary_groups AND !count(array_diff($membergroupids, $sub['deniedgroups'])))
			OR
			(!$allow_secondary_groups AND in_array($vbulletin->userinfo['usergroupid'], $sub['deniedgroups']))
		)
	)
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['subscription'], $vbulletin->options['contactuslink'])));
	}

	$sub['title'] = $vbphrase['sub' . $sub['subscriptionid'] . '_title'];
	$sub['description'] = $vbphrase['sub' . $sub['subscriptionid'] . '_desc'];
	$currency = $vbulletin->GPC['currency']["$subscriptionid"];
	$tmp = explode('_', $currency);
	$currency = $tmp[1];
	$subscriptionsubid = intval($tmp[0]);
	unset($tmp);

	$costs = unserialize($sub['cost']);

	if ($costs["$subscriptionsubid"]['length'] == 1)
	{
		$subscription_units = $lengths[$costs["$subscriptionsubid"]['units']];
	}
	else
	{
		$subscription_units = $lengths[$costs["$subscriptionsubid"]['units'] . 's'];
	}

	$subscription_length = construct_phrase($vbphrase['length_x_units_y_recurring_z'], $costs["$subscriptionsubid"]['length'], $subscription_units, ($costs["$subscriptionsubid"]['recurring'] ? ' *' : ''));
	$subscription_title = $sub['title'];
	$subscription_cost = $subobj->_CURRENCYSYMBOLS["$currency"] . vb_number_format($costs["$subscriptionsubid"]['cost']["$currency"], 2);
	$orderbits = '';

	if (empty($costs["$subscriptionsubid"]['cost']["$currency"]))
	{
		eval(standard_error(fetch_error('invalid_currency')));
	}

	// These phrases are constant since they are the name of a service
	$tmp = array(
		'paypal'       => 'PayPal',
		'nochex'       => 'NOCHEX',
		'worldpay'     => 'WorldPay',
		'2checkout'    => '2Checkout',
		'moneybookers' => 'MoneyBookers',
		'authorizenet' => 'Authorize.Net',
		'ccbill'       => 'CCBill',
	);

	$vbphrase += $tmp;

	($hook = vBulletinHook::fetch_hook('paidsub_order_start')) ? eval($hook) : false;

	$hash = md5($vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt'] . $subscriptionid . uniqid(microtime(),1));
	/* insert query */
	$db->query_write("
		INSERT INTO " . TABLE_PREFIX . "paymentinfo
			(hash, completed, subscriptionid, subscriptionsubid, userid)
		VALUES
			('" . $db->escape_string($hash) . "', 0, $subscriptionid, $subscriptionsubid, " . $vbulletin->userinfo['userid'] . ")
	");

	$methods = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "paymentapi WHERE active = 1 AND FIND_IN_SET('" . $db->escape_string($currency) . "', currency)");

	while ($method = $db->fetch_array($methods))
	{
		if (empty($costs["$subscriptionsubid"]['ccbillsubid']) AND $method['classname'] == 'ccbill')
		{
			continue;
		}

		if ($costs["$subscriptionsubid"]['cost']["$currency"] > 0)
		{
			$form = $subobj->construct_payment($hash, $method, $costs["$subscriptionsubid"], $currency, $sub, $vbulletin->userinfo);
			if (!empty($form))
			{
				$typetext = $method['classname'] . '_order_instructions';

				($hook = vBulletinHook::fetch_hook('paidsub_order_paymentbit')) ? eval($hook) : false;

				$templater = vB_Template::create('subscription_paymentbit');
					$templater->register('form', $form);
					$templater->register('method', $method);
					$templater->register('typetext', $typetext);
				$orderbits .= $templater->render();
			}
		}
	}

	$navbits['payments.php' . $vbulletin->session->vars['sessionurl_q']] = $vbphrase['paid_subscriptions'];
	$navbits[''] = $vbphrase['subscription_payment_method'];

	$page_templater = vB_Template::create('subscription_payment');
	$page_templater->register('orderbits', $orderbits);
	$page_templater->register('subscription_cost', $subscription_cost);
	$page_templater->register('subscription_length', $subscription_length);
	$page_templater->register('subscription_title', $subscription_title);
}

// #############################################################################

if (!empty($page_templater))
{
	// build the cp nav
	require_once(DIR . '/includes/functions_user.php');
	construct_usercp_nav('paid_subscriptions');

	($hook = vBulletinHook::fetch_hook('paidsub_complete')) ? eval($hook) : false;

	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	if (!$vbulletin->options['storecssasfile'])
	{
		$includecss = implode(',', $includecss);
	}

	$templater = vB_Template::create('USERCP_SHELL');
		$templater->register_page_templates();
		$templater->register('cpnav', $cpnav);
		$templater->register('HTML', $page_templater->render());
		$templater->register('navbar', $navbar);
		$templater->register('navclass', $navclass);
		$templater->register('onload', $onload);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('template_hook', $template_hook);
		$templater->register('includecss', $includecss);
	print_output($templater->render());

}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 58240 $
|| ####################################################################
\*======================================================================*/
?>
