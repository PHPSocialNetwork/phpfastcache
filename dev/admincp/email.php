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
define('CVS_REVISION', '$RCSfile$ - $Revision: 62096 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('user', 'cpuser', 'messaging', 'cprofilefield', 'profilefield');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_profilefield.php');
require_once(DIR . '/includes/adminfunctions_user.php');

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

print_cp_header($vbphrase['email_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'start';
}

// *************************** Send a page of emails **********************
if ($_POST['do'] == 'dosendmail' OR $_POST['do'] == 'makelist')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'user'              => TYPE_ARRAY,
		'profile'           => TYPE_ARRAY,
		'serializeduser'    => TYPE_STR,
		'serializedprofile' => TYPE_STR,
		'septext'           => TYPE_NOTRIM,
		'perpage'           => TYPE_UINT,
		'startat'           => TYPE_UINT,
		'test'              => TYPE_BOOL,
		'from'              => TYPE_STR,
		'subject'           => TYPE_STR,
		'message'           => TYPE_STR,
	));

	$vbulletin->GPC['septext'] = nl2br(htmlspecialchars_uni($vbulletin->GPC['septext']));

	// ensure that we don't send blank emails by mistake
	if ($_POST['do'] == 'dosendmail')
	{
		if ($vbulletin->GPC['subject'] == '' OR $vbulletin->GPC['message'] == '' OR !is_valid_email($vbulletin->GPC['from']))
		{
			print_stop_message('please_complete_required_fields');
		}
	}

	if (!empty($vbulletin->GPC['serializeduser']))
	{
		$vbulletin->GPC['user'] = @unserialize(verify_client_string($vbulletin->GPC['serializeduser']));
		$vbulletin->GPC['profile'] = @unserialize(verify_client_string($vbulletin->GPC['serializedprofile']));
	}

	$condition = fetch_user_search_sql($vbulletin->GPC['user'], $vbulletin->GPC['profile']);
	if (!$condition)
	{
		$condition = ' 1=1 ';
	}

	$finalcondition = "
		$condition
		AND user.email <> ''
		" . iif(!$vbulletin->GPC['user']['adminemail'], " AND (options & " . $vbulletin->bf_misc_useroptions['adminemail'] . ")");

	if ($_POST['do'] == 'makelist')
	{
		$users = $db->query_read("
			SELECT DISTINCT user.email
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (userfield.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON (usertextfield.userid = user.userid)
			WHERE $finalcondition
		");
		if ($db->num_rows($users) > 0)
		{
			while ($user = $db->fetch_array($users))
			{
				echo $user['email'] . $vbulletin->GPC['septext'];
				vbflush();
			}
		}
		else
		{
			print_stop_message('no_users_matched_your_query');
		}
	}
	else
	{
		if (empty($vbulletin->GPC['perpage']))
		{
			$vbulletin->GPC['perpage'] = 500;
		}

		@set_time_limit(0);

		$counter = $db->query_first("
			SELECT COUNT(*) AS total
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (userfield.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON (usertextfield.userid = user.userid)
			WHERE $finalcondition
		");
		if ($counter['total'] == 0)
		{
			print_stop_message('no_users_matched_your_query');
		}
		else
		{
			$users = $db->query_read("
				SELECT user.userid, user.usergroupid, user.username, user.email, user.joindate,
					useractivation.activationid
				FROM " . TABLE_PREFIX . "user AS user
				LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (userfield.userid = user.userid)
				LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON (usertextfield.userid = user.userid)
				LEFT JOIN " . TABLE_PREFIX . "useractivation AS useractivation ON (useractivation.userid = user.userid AND useractivation.type = 0)
				WHERE $finalcondition
				ORDER BY userid
				LIMIT " . $vbulletin->GPC['startat'] . ", " . $vbulletin->GPC['perpage'] . "
			");
			if ($db->num_rows($users))
			{
				$page = $vbulletin->GPC['startat'] / $vbulletin->GPC['perpage'] + 1;
				$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

				if (strpos($vbulletin->GPC['message'], '$activateid') !== false OR strpos($vbulletin->GPC['message'], '$activatelink') !== false)
				{
					$hasactivateid = 1;
				}
				else
				{
					$hasactivateid = 0;
				}

				echo '<p><b>' . $vbphrase['emailing'] . '<br />' . construct_phrase($vbphrase['showing_users_x_to_y_of_z'], vb_number_format($vbulletin->GPC['startat'] + 1), iif ($vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'] > $counter['total'], vb_number_format($counter['total']), vb_number_format($vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'])), vb_number_format($counter['total'])) . '</b></p>';

				while ($user = $db->fetch_array($users))
				{
					echo "$user[userid] - $user[username] .... \n";
					vbflush();

					$userid = $user['userid'];
					$sendmessage = $vbulletin->GPC['message'];
					$sendmessage = str_replace(
						array('$email', '$username', '$userid'),
						array($user['email'], $user['username'], $user['userid']),
						$vbulletin->GPC['message']
					);
					if ($hasactivateid)
					{
						if ($user['usergroupid'] == 3)
						{ // if in correct usergroup
							if (empty($user['activationid']))
							{ //none exists so create one
								$activate['activationid'] = fetch_random_string(40);
								/*insert query*/
								$db->query_write("
									REPLACE INTO " . TABLE_PREFIX . "useractivation
										(userid, dateline, activationid, type, usergroupid)
									VALUES
										($user[userid], " . TIMENOW . ", '$activate[activationid]', 0, 2)
								");
							}
							else
							{
								$activate['activationid'] = fetch_random_string(40);
								$db->query_write("
									UPDATE " . TABLE_PREFIX . "useractivation SET
										dateline = " . TIMENOW . ",
										activationid = '$activate[activationid]'
									WHERE userid = $user[userid] AND
										type = 0
								");
							}
							$activate['link'] = $vbulletin->options['bburl'] . "/register.php?a=act&u=$userid&i=$activate[activationid]";
						}
						else
						{
							$activate = array();
						}

						$sendmessage = str_replace(
							array('$activateid', '$activatelink'),
							array($activate['activationid'], $activate['link']),
							$sendmessage
						);

					}
					$sendmessage = str_replace(
						array('$bburl', '$bbtitle'),
						array($vbulletin->options['bburl'], $vbulletin->options['bbtitle']),
						$sendmessage
					);

					if (!$vbulletin->GPC['test'])
					{
						echo $vbphrase['emailing'] . " \n";
						vbmail($user['email'], $vbulletin->GPC['subject'], $sendmessage, false, $vbulletin->GPC['from']);
					}
					else
					{
						echo $vbphrase['test'] . " ... \n";
					}

					echo $vbphrase['okay'] . "<br />\n";
					vbflush();

				}
				$_REQUEST['do'] = 'donext';
			}
			else
			{
				define('CP_REDIRECT', 'email.php?' . $vbulletin->session->vars['sessionurl']);
				print_stop_message('emails_sent_successfully');
			}
		}
	}
}

// *************************** Link to next page of emails to send **********************
if ($_REQUEST['do'] == 'donext')
{

	$vbulletin->GPC['startat'] += $vbulletin->GPC['perpage'];

	print_form_header('email', 'dosendmail', false, true, 'cpform_dosendmail');
	construct_hidden_code('test', $vbulletin->GPC['test']);
	construct_hidden_code('serializeduser', sign_client_string(serialize($vbulletin->GPC['user'])));
	construct_hidden_code('serializedprofile', sign_client_string(serialize($vbulletin->GPC['profile'])));
	construct_hidden_code('from', $vbulletin->GPC['from']);
	construct_hidden_code('subject', $vbulletin->GPC['subject']);
	construct_hidden_code('message', $vbulletin->GPC['message']);
	construct_hidden_code('startat', $vbulletin->GPC['startat']);
	construct_hidden_code('perpage', $vbulletin->GPC['perpage']);

	print_submit_row($vbphrase['next_page'], 0);

	?>
	<script type="text/javascript">
	<!--
	if (document.cpform_dosendmail)
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

		YAHOO.util.Event.on(document.cpform_dosendmail, 'submit', send_submit);
		send_submit.call(document.cpform_dosendmail);
		document.cpform_dosendmail.submit();
	}
	// -->
	</script>
	<?php
	vbflush();
}

// *************************** Main email form **********************
if ($_REQUEST['do'] == 'start' OR $_REQUEST['do'] == 'genlist')
{
?>
<script type="text/javascript">
function check_all_usergroups(formobj, toggle_status)
{
	for (var i = 0; i < formobj.elements.length; i++)
	{
		var elm = formobj.elements[i];
		if (elm.type == "checkbox" && elm.name == 'user[usergroupid][]')
		{
			elm.checked = toggle_status;
		}
	}
}
</script>
<?php
	if ($_REQUEST['do'] == 'start')
	{
		print_form_header('email', 'dosendmail');
		print_table_header($vbphrase['email_manager']);
		print_yes_no_row($vbphrase['test_email_only'], 'test', 0);
		print_input_row($vbphrase['email_to_send_at_once'], 'perpage', 500);
		print_input_row($vbphrase['from'], 'from', $vbulletin->options['webmasteremail']);
		print_input_row($vbphrase['subject'], 'subject');
		print_textarea_row($vbphrase['message_email'], 'message', '', 10, 50);
		$text = $vbphrase['send'];

	}
	else
	{
		print_form_header('email', 'makelist');
		print_table_header($vbphrase['generate_mailing_list']);
		print_textarea_row($vbphrase['text_to_separate_addresses_by'], 'septext', ' ');
		$text = $vbphrase['go'];
	}

	print_table_break();
	print_table_header($vbphrase['search_criteria']);
	print_user_search_rows(true);

	print_table_break();
	print_submit_row($text);
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 62096 $
|| ####################################################################
\*======================================================================*/
?>
