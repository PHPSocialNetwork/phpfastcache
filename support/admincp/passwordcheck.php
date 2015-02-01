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
define('CVS_REVISION', '$RCSfile$ - $Revision: 25957 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('maintenance');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_file.php');
require_once(DIR . '/includes/functions_login.php');
require_once(DIR . '/includes/functions_misc.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['check_vulnerable_passwords']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = ($_POST['doreset'] ? 'reset' : 'check');
}

// checkable periods
$periods = array(
	'0' => $vbphrase['over_any_period'],
	'259200' => construct_phrase($vbphrase['over_x_days_ago'], 3),
	'604800' => $vbphrase['over_1_week_ago'],
	'1209600' => construct_phrase($vbphrase['over_x_weeks_ago'], 2),
	'1814400' => construct_phrase($vbphrase['over_x_weeks_ago'], 3),
	'2592000' => $vbphrase['over_1_month_ago'],
	'5270400' => construct_phrase($vbphrase['over_x_months_ago'], 2),
	'7862400' => construct_phrase($vbphrase['over_x_months_ago'], 3),
	'15724800' => construct_phrase($vbphrase['over_x_months_ago'], 6)
);

// get language information
$languages = array(0 => '');
$languages = array_merge($languages, fetch_language_titles_array('', 0));

// input
$vbulletin->input->clean_array_gpc('p', array(
		'period'        => TYPE_UINT,
		'quantity'      => TYPE_UINT,
		'email'         => TYPE_NOHTML,
		'email_subject' => TYPE_NOHTML,
		'email_from'    => TYPE_NOHTML,
		'languageid'	=> TYPE_UINT
));

// selected period
$period = $vbulletin->GPC['period'];

// count affected accounts
$total_affected = $vbulletin->db->query_first("
	SELECT COUNT(userid) AS total_affected
	FROM " . TABLE_PREFIX . "user
	WHERE password = MD5(CONCAT(MD5(username),salt)) " .
	($period ? 'AND lastvisit < ' . (TIMENOW - $period) : '') . "
");
$total_affected = (!empty($total_affected) ? $total_affected['total_affected'] : 0);


// ########################################################################
if ($_POST['do'] == 'reset')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'lastuser'      => TYPE_UINT,
		'reset'         => TYPE_UINT,
		'processed'     => TYPE_UINT,
		'email_errors'  => TYPE_BOOL,
		'reset_errors'  => TYPE_BOOL,
		'reset_on_error'=> TYPE_BOOL,
		'do_banned'     => TYPE_UINT
	));

	$lastuser = $vbulletin->GPC['lastuser'];
	$reset = $vbulletin->GPC['reset'];
	$processed = $vbulletin->GPC['processed'];
	$reset_errors = $vbulletin->GPC['reset_errors'];
	$email_errors = $vbulletin->GPC['email_errors'];
	$reset_on_error = $vbulletin->GPC['reset_on_error'];
	$do_banned = $vbulletin->GPC['do_banned'];
	$languageid = $vbulletin->GPC['languageid'];

	if (empty($vbulletin->GPC['email_subject']) OR empty($vbulletin->GPC['email']) OR empty($vbulletin->GPC['email_from']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if (false === strpos($vbulletin->GPC['email'], '{password}'))
	{
		print_stop_message('you_must_enter_the_password_token_into_the_message');
	}

	// select affected users
	$result = $vbulletin->db->query("
		SELECT user.userid, userban.liftdate
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userban AS userban
			ON user.userid = userban.userid
		WHERE user.password = MD5(CONCAT(MD5(user.username),user.salt)) " .
		($period ? 'AND user.lastvisit < ' . (TIMENOW - $period) : '') . "
		AND user.userid > $lastuser " .
		($languageid ? "AND user.languageid = " . $languageid : '') . "
		LIMIT 0, " . $vbulletin->GPC['quantity'] . "
	");

	if ($total = $vbulletin->db->num_rows($result))
	{
		while ($user = $vbulletin->db->fetch_array($result))
		{
			// set last user processed
			$lastuser = $user['userid'];

			// copy the ban liftdate before it is removed by fetch_userinfo
			$liftdate = $user['liftdate'];

			// fetch their info
			$user = fetch_userinfo($user['userid']);

			// make random password
			$newpassword = fetch_random_password(8);

			// send mail to user
			if ($do_banned OR ($liftdate !== "0"))
			{
				$message = str_replace('{username}', $user['username'], $vbulletin->GPC['email']);
				$message = str_replace('{password}', $newpassword, $message);
				if (!vbmail($user['email'], $vbulletin->GPC['email_subject'], $message, true, $vbulletin->GPC['from']))
				{
					$email_errors = true;
					if (!$reset_on_error)
					{
						continue;
					}
				}
			}

			// reset the password
			$userdata = datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
			$userdata->set_existing($user);
			$userdata->set('password', $newpassword);
			$userdata->save();

			// check reset for errors
			if (sizeof($userdata->errors))
			{
				$reset_errors = true;
				continue;
			}

			$reset++;
		}
		$vbulletin->db->free_result($result);
		unset($userdata);

		$processed = $processed + $total;
		$_POST['do'] = 'resetnext';
	}
	else
	{
		// display results
		print_table_start();
		print_table_header($vbphrase['passwords_reset']);
		print_description_row(construct_phrase($vbphrase['x_of_y_passwords_were_reset'], $reset, $processed), false, 2, '', 'center');

		if ($reset_errors)
		{
			print_description_row($vbphrase['some_errors_occured_while_resetting_passwords']);
		}

		if ($email_errors)
		{
			print_description_row($vbphrase['some_errors_occured_while_sending_emails']);
		}

		if ($languageid)
		{
			print_description_row(construct_phrase($vbphrase['only_accounts_using_language_x_were_processed'], $languages[$languageid]));
		}

		print_table_footer();

		// display check form for resubmit
		$_REQUEST['do'] = 'check';
	}
}

// ########################################################################
if ($_POST['do'] == 'resetnext')
{
	print_form_header('passwordcheck', 'reset', false, true, 'cpform_reset');
	print_description_row(construct_phrase($vbphrase['x_accounts_processed'], $processed), false, 2, '', 'center');
	construct_hidden_code('email_errors', $email_errors);
	construct_hidden_code('reset_errors', $reset_errors);
	construct_hidden_code('reset_on_error', $reset_on_error);
	construct_hidden_code('email', $vbulletin->GPC['email'], false);
	construct_hidden_code('email_subject', $vbulletin->GPC['email_subject'], false);
	construct_hidden_code('email_from', $vbulletin->GPC['email_from'], false);
	construct_hidden_code('quantity', $vbulletin->GPC['quantity']);
	construct_hidden_code('reset', $reset);
	construct_hidden_code('processed', $processed);
	construct_hidden_code('do_banned', $do_banned);
	construct_hidden_code('lastuser', $lastuser);
	construct_hidden_code('languageid', $languageid);
	print_submit_row($vbphrase['continue'], 0);
	print_table_footer();

	?>
	<script type="text/javascript">
	<!--
	if (document.cpform_reset)
	{
		function send_submit()
		{
			var submits = YAHOO.util.Dom.getElementsBy(
				function(element) { return (element.type == "submit") },
				"input", this
			);
			var submit_button;

			for (var i = 0; i < submits.length; i++)
			{
				submit_button = submits[i];
				submit_button.disabled = true;
				setTimeout(function() { submit_button.disabled = false; }, 10000);
			}

			return false;
		}

		YAHOO.util.Event.on(document.cpform_reset, 'submit', send_submit);
		send_submit.call(document.cpform_reset);
		document.cpform_reset.submit();
	}
	// -->
	</script>
	<?php
	vbflush();
}
// ########################################################################
if ($_REQUEST['do'] == 'check')
{
	// postback or default values
	$email = construct_phrase(($vbulletin->GPC['email'] ? $vbulletin->GPC['email'] : $vbphrase['vulnerable_password_reset_email']), $vbulletin->options['bbtitle'], $vbulletin->options['bburl']);
	$email_subject = construct_phrase(($vbulletin->GPC['email_subject'] ? $vbulletin->GPC['email_subject'] : $vbphrase['vulnerable_password_reset_email_subject']), $vbulletin->options['bbtitle']);
	$email_from = ($vbulletin->GPC['email_from'] ? $vbulletin->GPC['email_from'] : $vbulletin->options['webmasteremail']);
	$quantity = ($vbulletin->GPC['quantity'] ? $vbulletin->GPC['quantity'] : min($total_affected, 100));


	// display notice and check options
	print_form_header('passwordcheck', 'check');
	print_table_header($vbphrase['check_vulnerable_passwords']);
	print_description_row($vbphrase['password_check_notice']);
	print_select_row($vbphrase['check_accounts_with_last_activity'], 'period', $periods, $period);
	print_description_row('<strong>' . ($period ? construct_phrase($vbphrase['affected_accounts_that_were_last_active_x_y'], strtolower($periods[$period]), $total_affected) : construct_phrase($vbphrase['affected_accounts_x'], $total_affected)) . '</strong>');
	construct_hidden_code('email', $email, false);
	construct_hidden_code('email_subject', $email_subject, false);
	construct_hidden_code('email_from', $email_from, false);
	construct_hidden_code('quantity', $quantity);
	print_submit_row($vbphrase['check_affected_accounts'], false);
	print_table_footer();

	// display reset options
	print_form_header('passwordcheck', 'reset');
	print_table_header($vbphrase['reset_vulnerable_passwords']);
	print_column_style_code(array('width: 40%','width: 60%'));
	print_select_row($vbphrase['reset_accounts_with_last_activity'], 'period', $periods, $period);
	print_input_row($vbphrase['email_to_send_at_once'], 'quantity', $quantity);
	print_input_row($vbphrase['email_subject'], 'email_subject', $email_subject, false, 70);
	print_input_row($vbphrase['email_from'], 'email_from', $email_from, false, 70);
	print_textarea_row($vbphrase['password_vulnerability_email_message_label'], 'email', $email, 30, 70);
	print_select_row($vbphrase['reset_passwords_for_users_with_language'], 'languageid', $languages, $languageid);
	print_yes_no_row($vbphrase['email_permanently_banned_users'], 'do_banned', 0);
	print_yes_no_row($vbphrase['reset_password_if_email_failed'], 'reset_on_error', 0);
	print_submit_row($vbphrase['reset_vulnerable_passwords'], false);
	print_table_footer();
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 25957 $
|| ####################################################################
\*======================================================================*/
