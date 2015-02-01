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

// ######################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'sendmessage');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('messaging');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
	'mailform',
	'sendtofriend',
	'contactus',
	'contactus_option',
	'newpost_errormessage',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'im' => array(
		'im_send_aim',
		'im_send_yahoo',
		'im_send_msn',
		'im_send_skype',
		'im_message'
	),
	'sendtofriend' => array(
		'newpost_usernamecode',
		'humanverify'
	),
	'contactus' => array(
		'humanverify',
	),
);

$actiontemplates['none'] =& $actiontemplates['contactus'];
$actiontemplates['docontactus'] =& $actiontemplates['contactus'];

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'contactus';
}

($hook = vBulletinHook::fetch_hook('sendmessage_start')) ? eval($hook) : false;

// ############################### start im message ###############################
if ($_REQUEST['do'] == 'im')
{
	if (!($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers']))
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'type'		=> TYPE_NOHTML,
		'userid'	=> TYPE_UINT
	));

	// verify userid
	$userinfo = verify_id('user', $vbulletin->GPC['userid'], 1, 1, 15);

	require_once(DIR . '/includes/functions_user.php');
	if (!can_view_profile_section($userinfo['userid'], 'contactinfo'))
	{
		define('VB_ERROR_LITE', true);
		standard_error(fetch_error('user_chosen_privacy_prevents_viewing'));
	}

	$type = $vbulletin->GPC['type'];

	switch ($type)
	{
		case 'aim':
		case 'yahoo':
		case 'skype':
			$userinfo["{$type}_link"] = urlencode($userinfo["$type"]);
			break;
		case 'icq':
			$userinfo['icq'] = trim(htmlspecialchars_uni($userinfo['icq']));
			break;
		default:
			$type = 'msn';
			break;
	}

	($hook = vBulletinHook::fetch_hook('sendmessage_im_start')) ? eval($hook) : false;

	if (empty($userinfo["$type"]))
	{
		// user does not have this messaging medium defined
		eval(standard_error(fetch_error('immethodnotdefined', $userinfo['username'])));
	}

	if ($type == 'icq')
	{
		$vbulletin->url = 'http://www.icq.com/people/' . urlencode($userinfo['icq']);
		exec_header_redirect($vbulletin->url);
		exit;
	}

	// shouldn't be a problem hard-coding this text, as they are all commercial names
	$typetext = array(
		'msn'   => 'MSN',
		'aim'   => 'AIM',
		'yahoo' => 'Yahoo!',
		'skype' => 'Skype'
	);

	// add language suffix to SkypeWeb graphic if possible
	$userinfo['skype_suffix'] = '';
	if ($vbulletin->options['skypeweb_gfx'] == 2 AND $type == 'skype')
	{
		// list of available language codes from the SkypeWeb Partner Whitepaper
		$skype_language_codes = array(
			'en',
			'de',
			'fr',
			'it',
			'pl',
			'ja',
			'pt',
			'pt-br',
			'se',
			'zh',
			'cn',
			'zh-cn',
			'hk',
			'tw',
			'zh-tw',
		);

		// is the visiting user's language code available?
		$search_result = array_search(strtolower(str_replace('/', '-', $vbulletin->userinfo['lang_code'])), $skype_language_codes);

		if ($search_result > 0) // ignore 'en' as that's the default
		{
			$userinfo['skype_suffix'] = '.' . $skype_language_codes["$search_result"];
		}
	}

	($hook = vBulletinHook::fetch_hook('sendmessage_im_complete')) ? eval($hook) : false;

	$typetext = $typetext["$type"];

	$templater = vB_Template::create("im_send_$type");
		$templater->register('userinfo', $userinfo);
	$imtext = $templater->render();

	$templater = vB_Template::create('im_message');
		$templater->register('headinclude', $headinclude);
		$templater->register('imtext', $imtext);
		$templater->register('typetext', $typetext);
		$templater->register('userinfo', $userinfo);
	print_output($templater->render());

}

// ##################################################################################
// ALL other actions from here onward require email permissions, so check that now...
// *** email permissions ***
if (!$vbulletin->options['enableemail'])
{
	eval(standard_error(fetch_error('emaildisabled')));
}

$perform_floodcheck = (
	!($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
	AND $vbulletin->options['emailfloodtime']
	AND $vbulletin->userinfo['userid']
);

if ($perform_floodcheck AND ($timepassed = TIMENOW - $vbulletin->userinfo['emailstamp']) < $vbulletin->options['emailfloodtime'])
{
	eval(standard_error(fetch_error('emailfloodcheck', $vbulletin->options['emailfloodtime'], ($vbulletin->options['emailfloodtime'] - $timepassed))));
}

// initialize errors array
$errors = array();

// ############################### do contact webmaster ###############################
if ($_POST['do'] == 'docontactus')
{
	if (!$vbulletin->userinfo['userid'] AND !$vbulletin->options['contactustype'])
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'name'          => TYPE_STR,
		'email'         => TYPE_STR,
		'subject'       => TYPE_STR,
		'message'       => TYPE_STR,
		'other_subject' => TYPE_STR,
		'humanverify'   => TYPE_ARRAY,
	));

	($hook = vBulletinHook::fetch_hook('sendmessage_docontactus_start')) ? eval($hook) : false;

	// Used in phrase(s)
	$subject =& $vbulletin->GPC['subject'];
	$name =& $vbulletin->GPC['name'];
	$message =& $vbulletin->GPC['message'];
	$email =& $vbulletin->GPC['email'];

	// check we have a message and a subject
	if ($message == '' OR $subject == ''
			OR (
				$vbulletin->options['contactusoptions']
				AND $subject == 'other'
				AND ($vbulletin->GPC['other_subject'] == '' OR !$vbulletin->options['contactusother'])
			)
		)
	{
		$errors[] = fetch_error('nosubject');
	}

	// check for valid email address
	if (!is_valid_email($vbulletin->GPC['email']))
	{
		$errors[] = fetch_error('bademail');
	}

	if (fetch_require_hvcheck('contactus'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verify =& vB_HumanVerify::fetch_library($vbulletin);
		if (!$verify->verify_token($vbulletin->GPC['humanverify']))
		{
	  		$errors[] = fetch_error($verify->fetch_error());
	  	}
	}

	($hook = vBulletinHook::fetch_hook('sendmessage_docontactus_process')) ? eval($hook) : false;

	// if it's all good... send the email
	if (empty($errors))
	{
		$languageid = -1;
		if ($vbulletin->options['contactusoptions'])
		{
			if ($subject == 'other')
			{
				$subject = $vbulletin->GPC['other_subject'];
			}
			else
			{
				$options = explode("\n", trim($vbulletin->options['contactusoptions']));
				foreach($options AS $index => $title)
				{
					if ($index == $subject)
					{
						if (preg_match('#^{(.*)} (.*)$#siU', $title, $matches))
						{
							$title =& $matches[2];
							if (is_numeric($matches[1]) AND intval($matches[1]) !== 0)
							{
								$userinfo = fetch_userinfo($matches[1]);
								$alt_email =& $userinfo['email'];
								$languageid =& $userinfo['languageid'];
							}
							else
							{
								$alt_email = $matches[1];
							}
						}
						$subject = $title;
						break;
					}
				}
			}
		}

		if (!empty($alt_email))
		{
			if ($alt_email == $vbulletin->options['webmasteremail'] OR $alt_email == $vbulletin->options['contactusemail'])
			{
				$ip = IPADDRESS;
			}
			else
			{
				$ip =& $vbphrase['n_a'];
			}
			$destemail =& $alt_email;
		}
		else
		{
			$ip = IPADDRESS;
			if ($vbulletin->options['contactusemail'])
			{
				$destemail =& $vbulletin->options['contactusemail'];
			}
			else
			{
				$destemail =& $vbulletin->options['webmasteremail'];
			}
		}

		($hook = vBulletinHook::fetch_hook('sendmessage_docontactus_complete')) ? eval($hook) : false;

		$url =& $vBulletin->url;
		eval(fetch_email_phrases('contactus', $languageid));
		vbmail($destemail, $subject, $message, false, $vbulletin->GPC['email'], '', $name);

		print_standard_redirect('redirect_sentfeedback', true, true);  
	}
	// there are errors!
	else
	{
		$errormessages = '';
		if (!empty($errors))
		{
			$show['errors'] = true;
			$templater = vB_Template::create('newpost_errormessage');
			$templater->register('errors', $errors);
			$errormessages .= $templater->render();
		}

		$_REQUEST['do'] = 'contactus';
	}

}

// ############################### start contact webmaster ###############################
if ($_REQUEST['do'] == 'contactus')
{
	if (!$vbulletin->userinfo['userid'] AND !$vbulletin->options['contactustype'])
	{
		print_no_permission();
	}

	// These values may have already been cleaned in the previous action so we can not clean them again here (TYPE_NOHTML)
	$vbulletin->input->clean_array_gpc('r', array(
		'name'		=> TYPE_STR,
		'email'		=> TYPE_STR,
		'subject'	=> TYPE_STR,
		'other_subject' => TYPE_STR,
		'message'	=> TYPE_STR,
	));

	($hook = vBulletinHook::fetch_hook('sendmessage_contactus_start')) ? eval($hook) : false;

	$name = htmlspecialchars_uni($vbulletin->GPC['name']);
	$email = htmlspecialchars_uni($vbulletin->GPC['email']);
	$subject = htmlspecialchars_uni($vbulletin->GPC['subject']);
	$other_subject = htmlspecialchars_uni($vbulletin->GPC['other_subject']);
	$message = htmlspecialchars_uni($vbulletin->GPC['message']);

	// enter $vbulletin->userinfo's name and email if necessary
	if ($name == '' AND $vbulletin->userinfo['userid'] > 0)
	{
		$name = $vbulletin->userinfo['username'];
	}
	if ($email == '' AND $vbulletin->userinfo['userid'] > 0)
	{
		$email = $vbulletin->userinfo['email'];
	}

	if ($vbulletin->options['contactusoptions'])
	{
		$options = explode("\n", trim($vbulletin->options['contactusoptions']));
		foreach($options AS $index => $title)
		{
			// Look for the {(int)} or {(email)} identifier at the start and strip it out
			if (preg_match('#^({.*}) (.*)$#siU', $title, $matches))
			{
				$title =& $matches[2];
			}

			if ($subject == strval($index))
			{
				$checked = 'checked="checked"';
			}

			($hook = vBulletinHook::fetch_hook('sendmessage_contactus_option')) ? eval($hook) : false;

			$templater = vB_Template::create('contactus_option');
				$templater->register('checked', $checked);
				$templater->register('index', $index);
				$templater->register('title', $title);
			$contactusoptions .= $templater->render();
			unset($checked);
		}
	}

	$other_subject_checked = ($subject == 'other' ? 'checked="checked"' : '');

	if (fetch_require_hvcheck('contactus'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verification =& vB_HumanVerify::fetch_library($vbulletin);
		$human_verify = $verification->output_token();
	}
	else
	{
		$human_verify = '';
	}

	// generate navbar
	$navbits = construct_navbits(array('' => $vbphrase['contact_us']));
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('sendmessage_contactus_complete')) ? eval($hook) : false;

	$url =& $vbulletin->url;
	$templater = vB_Template::create('contactus');
		$templater->register_page_templates();
		$templater->register('contactusoptions', $contactusoptions);
		$templater->register('email', $email);
		$templater->register('errormessages', $errormessages);
		$templater->register('human_verify', $human_verify);
		$templater->register('message', $message);
		$templater->register('name', $name);
		$templater->register('navbar', $navbar);
		$templater->register('subject', $subject);
		$templater->register('url', $url);
		$templater->register('other_subject', $other_subject);
		$templater->register('other_subject_checked', $other_subject_checked);
	print_output($templater->render());
}

// ############################### start send to friend permissions ###############################
if ($_REQUEST['do'] == 'sendtofriend' OR $_POST['do'] == 'dosendtofriend')
{
	$forumperms = fetch_permissions($threadinfo['forumid']);

	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canemail']) OR (($threadinfo['postuserid'] != $vbulletin->userinfo['userid']) AND !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers'])))
	{
		print_no_permission();
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

}

// ############################### start send to friend ###############################
if ($_REQUEST['do'] == 'sendtofriend')
{
	($hook = vBulletinHook::fetch_hook('sendmessage_sendtofriend_start')) ? eval($hook) : false;

	if ($vbulletin->options['wordwrap'] != 0)
	{
		$threadinfo['title'] = fetch_word_wrapped_string($threadinfo['title']);
	}

	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	// human verification
	if (fetch_require_hvcheck('contactus'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verification =& vB_HumanVerify::fetch_library($vbulletin);
		$human_verify = $verification->output_token();
	}
	else
	{
		$human_verify = '';
	}

	// draw nav bar
	$navbits = array();
	$parentlist = array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3)));
	foreach ($parentlist AS $forumID)
	{
		$forumTitle =& $vbulletin->forumcache["$forumID"]['title'];
		$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
	}
	$navbits[fetch_seo_url('thread', $threadinfo)] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];
	$navbits[''] = $vbphrase['email_to_friend'];

	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	$pageinfo = array('referrerid' => $vbulletin->userinfo['userid']);

	($hook = vBulletinHook::fetch_hook('sendmessage_sendtofriend_complete')) ? eval($hook) : false;

	$url =& $vbulletin->url;
	$templater = vB_Template::create('sendtofriend');
		$templater->register_page_templates();
		$templater->register('human_verify', $human_verify);
		$templater->register('navbar', $navbar);
		$templater->register('pageinfo', $pageinfo);
		$templater->register('threadid', $threadid);
		$templater->register('threadinfo', $threadinfo);
		$templater->register('url', $url);
		$templater->register('usernamecode', $usernamecode);
	print_output($templater->render());

}

// ############################### start do send to friend ###############################
if ($_POST['do'] == 'dosendtofriend')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'sendtoname'   => TYPE_STR,
		'sendtoemail'  => TYPE_STR,
		'emailsubject' => TYPE_STR,
		'emailmessage' => TYPE_STR,
		'username'     => TYPE_STR,
		'humanverify'  => TYPE_ARRAY
	));

	// Values that are used in phrases or error messages
	$sendtoname =& $vbulletin->GPC['sendtoname'];
	$emailmessage =& $vbulletin->GPC['emailmessage'];

	if ($sendtoname == '' OR !is_valid_email($vbulletin->GPC['sendtoemail']) OR $vbulletin->GPC['emailsubject'] == '' OR $emailmessage == '')
	{
		eval(standard_error(fetch_error('requiredfields')));
	}

	if ($perform_floodcheck)
	{
		require_once(DIR . '/includes/class_floodcheck.php');
		$floodcheck = new vB_FloodCheck($vbulletin, 'user', 'emailstamp');
		$floodcheck->commit_key($vbulletin->userinfo['userid'], TIMENOW, TIMENOW - $vbulletin->options['emailfloodtime']);
		if ($floodcheck->is_flooding())
		{
			eval(standard_error(fetch_error('emailfloodcheck', $vbulletin->options['emailfloodtime'], $floodcheck->flood_wait())));
		}
	}

	if (fetch_require_hvcheck('contactus'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verify =& vB_HumanVerify::fetch_library($vbulletin);
		if (!$verify->verify_token($vbulletin->GPC['humanverify']))
		{
	  		standard_error(fetch_error($verify->fetch_error()));
	  	}
	}

	($hook = vBulletinHook::fetch_hook('sendmessage_dosendtofriend_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['username'] != '')
	{
		if ($userinfo = $db->query_first_slave("
			SELECT user.*, userfield.*
			FROM " . TABLE_PREFIX . "user AS user," . TABLE_PREFIX . "userfield AS userfield
			WHERE username='" . $db->escape_string(htmlspecialchars_uni($vbulletin->GPC['username'])) . "'
				AND user.userid = userfield.userid"
		))
		{
			eval(standard_error(fetch_error('usernametaken', $vbulletin->GPC['username'], $vbulletin->session->vars['sessionurl'])));
		}
		else
		{
			$postusername = htmlspecialchars_uni($vbulletin->GPC['username']);
		}
	}
	else
	{
		$postusername = $vbulletin->userinfo['username'];
	}

	eval(fetch_email_phrases('sendtofriend'));

	vbmail($vbulletin->GPC['sendtoemail'], $vbulletin->GPC['emailsubject'], $message);

	($hook = vBulletinHook::fetch_hook('sendmessage_dosendtofriend_complete')) ? eval($hook) : false;

	$sendtoname = htmlspecialchars_uni($sendtoname);
	print_standard_redirect(array('redirect_sentemail',$sendtoname));  

}

// ############################### start mail member permissions ###############################
if ($_REQUEST['do'] == 'mailmember' OR $_POST['do'] == 'domailmember')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'	=> TYPE_UINT
	));

	if (!$vbulletin->userinfo['userid'] OR !($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canemailmember']))
	{
		print_no_permission();
	}

	$userinfo = verify_id('user', $vbulletin->GPC['userid'], 1, 1);

	if ($userinfo['usergroupid'] == 3 OR $userinfo['usergroupid'] == 4)
	{ // user hasn't confirmed email address yet or is COPPA
		eval(standard_error(fetch_error('usernoemail', $vbulletin->options['contactuslink'])));
	}

}

// ############################### start mail member ###############################
if ($_REQUEST['do'] == 'mailmember')
{

	if (!$vbulletin->options['displayemails'])
	{
		eval(standard_error(fetch_error('emaildisabled')));
	}
	else if (!$userinfo['showemail'])
	{
		eval(standard_error(fetch_error('usernoemail', $vbulletin->options['contactuslink'])));
	}
	else
	{
		($hook = vBulletinHook::fetch_hook('sendmessage_mailmember')) ? eval($hook) : false;

		if ($vbulletin->options['secureemail']) // use secure email form or not?
		{
			// generate navbar
			$navbits = construct_navbits(array('' => $vbphrase['email']));
			$navbar = render_navbar_template($navbits);

			$url =& $vbulletin->url;
			$templater = vB_Template::create('mailform');
				$templater->register_page_templates();
				$templater->register('message', $message);
				$templater->register('navbar', $navbar);
				$templater->register('subject', $subject);
				$templater->register('url', $url);
				$templater->register('userinfo', $userinfo);
			print_output($templater->render());
		}
		else
		{
			require_once(DIR . '/includes/functions_user.php');
			if (!can_view_profile_section($userinfo['userid'], 'contactinfo'))
			{
				standard_error(fetch_error('user_chosen_privacy_prevents_viewing'));
			}

			// show the user's email address
			$destusername = $userinfo['username']; 
			eval(standard_error(fetch_error('showemail', $destusername, htmlspecialchars_uni($userinfo['email']))));
		}
	}
}

// ############################### start do mail member ###############################
if ($_POST['do'] == 'domailmember')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'message'		=> TYPE_STR,
		'emailsubject'	=> TYPE_STR,
	));

	$destuserid = $userinfo['userid'];

	if (!$vbulletin->options['displayemails'])
	{
		eval(standard_error(fetch_error('emaildisabled')));
	}
	else if (!$userinfo['showemail'])
	{
		eval(standard_error(fetch_error('usernoemail', $vbulletin->options['contactuslink'])));
	}
	else
	{
		if ($vbulletin->GPC['message'] == '')
		{
			eval(standard_error(fetch_error('nomessage')));
		}

		if ($perform_floodcheck)
		{
			require_once(DIR . '/includes/class_floodcheck.php');
			$floodcheck = new vB_FloodCheck($vbulletin, 'user', 'emailstamp');
			$floodcheck->commit_key($vbulletin->userinfo['userid'], TIMENOW, TIMENOW - $vbulletin->options['emailfloodtime']);
			if ($floodcheck->is_flooding())
			{
				eval(standard_error(fetch_error('emailfloodcheck', $vbulletin->options['emailfloodtime'], $floodcheck->flood_wait())));
			}
		}

		($hook = vBulletinHook::fetch_hook('sendmessage_domailmember')) ? eval($hook) : false;

		//magic variables for for phrase eval
		$message = fetch_censored_text($vbulletin->GPC['message']);
		$forumhomelink = create_full_url(fetch_seo_url('forumhome|nosession', array()), true);

		eval(fetch_email_phrases('usermessage', $userinfo['languageid']));

		//note that $message is set via the run via eval from fetch_email_phrases.
		vbmail($userinfo['email'], fetch_censored_text($vbulletin->GPC['emailsubject']), $message , 
			false, $vbulletin->userinfo['email'], '', $vbulletin->userinfo['username']);

		// parse this next line with eval:
		$sendtoname = $userinfo['username'];

		print_standard_redirect(array('redirect_sentemail',$sendtoname));
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 58373 $
|| ####################################################################
\*======================================================================*/
?>
