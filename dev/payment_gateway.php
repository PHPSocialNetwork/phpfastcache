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
define('THIS_SCRIPT', 'payment_gateway');
define('CSRF_PROTECTION', false);
define('SKIP_SESSIONCREATE', 1);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('subscription');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
define('VB_AREA', 'Subscriptions');
define('CWD', (($getcwd = getcwd()) ? $getcwd : '.'));
require_once(CWD . '/includes/init.php');
require_once(DIR . '/includes/adminfunctions.php');
require_once(DIR . '/includes/class_paid_subscription.php');

$vbulletin->input->clean_array_gpc('r', array(
	'method' => TYPE_STR
));

$vbulletin->nozip = true;

$api = $db->query_first_slave("
	SELECT *
	FROM " . TABLE_PREFIX . "paymentapi
	WHERE classname = '" . $db->escape_string($vbulletin->GPC['method']) . "'
");
if (!empty($api) AND $api['active'])
{
	$subobj = new vB_PaidSubscription($vbulletin);
	if (file_exists(DIR . '/includes/paymentapi/class_' . $api['classname'] . '.php'))
	{
		require_once(DIR . '/includes/paymentapi/class_' . $api['classname'] . '.php');
		$api_class = 'vB_PaidSubscriptionMethod_' . $api['classname'];
		$apiobj = new $api_class($vbulletin);

		// at this point we know if the system is going to want to display feedback, so do the appropriate queries here
		if ($apiobj->display_feedback)
		{
			// initialize $vbphrase and set language constants
			$vbphrase = init_language();

			$vbulletin->userinfo['styleid'] = $vbulletin->options['styleid'];
			$style = $db->query_first_slave("
				SELECT *
				FROM " . TABLE_PREFIX . "style
				WHERE styleid = " . intval($vbulletin->options['styleid'])
			);
			define('STYLEID', $style['styleid']);

			cache_templates(array('STANDARD_REDIRECT', 'STANDARD_ERROR', 'STANDARD_ERROR_LITE', 'headinclude'), $style['templatelist']);
			fetch_stylevars($style, $vbulletin->userinfo);
			$headinclude = '<base href="' . $vbulletin->options['bburl'] . '/" />';
			$templater = vB_Template::create('headinclude');
				$templater->register('foruminfo', $foruminfo);
				$templater->register('pagenumber', $pagenumber);
				$templater->register('style', $style);
				$templater->register('basepath', $vbulletin->input->fetch_basepath());
			$headinclude .= $templater->render();
		}

		if (!empty($api['settings']))
		{ // need to convert this from a serialized array with types to a single value
			$apiobj->settings = $subobj->construct_payment_settings($api['settings']);
		}

		if ($apiobj->verify_payment())
		{
			// its a valid payment now lets check transactionid
			$transaction = $db->query_first("
				SELECT *
				FROM " . TABLE_PREFIX . "paymenttransaction
				WHERE
					transactionid = '" . $db->escape_string($apiobj->transaction_id) . "'
						AND
					paymentapiid = $api[paymentapiid]
			");

			if (($apiobj->type == 2 OR $apiobj->type == 3 OR (empty($transaction) AND $apiobj->type == 1)) AND $vbulletin->options['paymentemail'])
			{
				if (!$vbphrase)
				{
					// initialize $vbphrase and set language constants
					$vbphrase = init_language();
				}

				$emailphrase = ($apiobj->type == 2) ? 'payment_reversed' : 'payment_received';
				$emails = explode(' ', $vbulletin->options['paymentemail']);

				$username = unhtmlspecialchars($apiobj->paymentinfo['username']);
				$userid = $apiobj->paymentinfo['userid'];
				$subscription = $vbphrase['sub' . $apiobj->paymentinfo['subscriptionid'] . '_title'];
				$amount = vb_number_format($apiobj->paymentinfo['amount'], 2) . ' ' . strtoupper($apiobj->paymentinfo['currency']);
				$processor = $api['title'];
				$transactionid = $apiobj->transaction_id;

				$memberlink = fetch_seo_url('member|nosession|bburl', array('userid' => $userid, 'username' => $apiobj->paymentinfo['username']));
				eval(fetch_email_phrases($emailphrase, 0));
				foreach($emails AS $toemail)
				{
					if (trim($toemail))
					{
						vbmail($toemail, $subject, $message, true);
					}
				}
			}

			if (empty($transaction))
			{ // transaction hasn't been processed before
				/*insert query*/
				$trans = array(
					'transactionid' => $apiobj->transaction_id,
					'paymentinfoid' => $apiobj->paymentinfo['paymentinfoid'],
					'amount'        => $apiobj->paymentinfo['amount'],
					'currency'      => $apiobj->paymentinfo['currency'],
					'state'         => $apiobj->type,
					'dateline'      => TIMENOW,
					'paymentapiid'  => $api['paymentapiid'],
				);

				if (!$apiobj->type)
				{
					$trans['request'] = serialize(array(
						'vb_error_code' => $apiobj->error_code,
						'GET'           => serialize($_GET),
						'POST'          => serialize($_POST)
					));
				}

				$db->query_write(fetch_query_sql($trans, 'paymenttransaction'));

				if ($apiobj->type == 1)
				{
					$subobj->build_user_subscription($apiobj->paymentinfo['subscriptionid'], $apiobj->paymentinfo['subscriptionsubid'], $apiobj->paymentinfo['userid']);
					if ($apiobj->display_feedback)
					{
						$vbulletin->url = $vbulletin->options['bburl'] . '/payments.php';

						print_standard_redirect('payment_complete', true, true);  
					}
				}
				else if ($apiobj->type == 2)
				{
					$subobj->delete_user_subscription($apiobj->paymentinfo['subscriptionid'], $apiobj->paymentinfo['userid'], $apiobj->paymentinfo['subscriptionsubid']);
				}
			}
			else if ($apiobj->type == 2)
			{ // transaction is a reversal / refund
				$subobj->delete_user_subscription($apiobj->paymentinfo['subscriptionid'], $apiobj->paymentinfo['userid'], $apiobj->paymentinfo['subscriptionsubid']);
			}
			else if ($apiobj->type == 3)
			{// transaction is a canceled_reversal
				$subobj->build_user_subscription($apiobj->paymentinfo['subscriptionid'], $apiobj->paymentinfo['subscriptionsubid'], $apiobj->paymentinfo['userid']);
				if ($apiobj->display_feedback)
				{
					$vbulletin->url = $vbulletin->options['bburl'] . '/payments.php';
					print_standard_redirect('payment_complete', true, true);  
				}
			}
			else
			{ // its most likely a re-post of a payment, if we've already dealt with it serve up a redirect
				if ($apiobj->display_feedback)
				{
					$vbulletin->url = $vbulletin->options['bburl'] . '/payments.php';
					print_standard_redirect('payment_complete', true, true);  
				}
			}
		}
		else
		{ // something went horribly wrong, get $apiobj->error
			$trans = array(
				'state'         => 0,
				'dateline'      => TIMENOW,
				'paymentapiid'  => $api['paymentapiid'],
				'request'       => serialize(array(
					'vb_error_code' => $apiobj->error_code,
					'GET' => serialize($_GET),
					'POST' => serialize($_POST)
				)),
			);
			$db->query_write(fetch_query_sql($trans, 'paymenttransaction'));
			if ($apiobj->display_feedback AND !empty($apiobj->error))
			{
				//die("<p>{$apiobj->error}</p>");
				// we dont load header / footer, so just show the LITE template
				define('VB_ERROR_LITE', true);
				standard_error($apiobj->error);
			}
		}
	}
}
else
{
	exec_header_redirect(fetch_seo_url('forumhome|nosession', array()));
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 50189 $
|| ####################################################################
\*======================================================================*/
?>
