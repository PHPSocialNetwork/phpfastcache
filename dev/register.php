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
define('THIS_SCRIPT', 'register');
define('CSRF_PROTECTION', true);
define('CONTENT_PAGE', false);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('timezone', 'user', 'register', 'cprofilefield');

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
	'banemail',
	'ranks',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'humanverify',
	'register',
	'register_rules',
	'register_verify_age',
	'register_coppaform',
	'userfield_textbox',
	'userfield_checkbox_option',
	'userfield_optional_input',
	'userfield_radio',
	'userfield_radio_option',
	'userfield_select',
	'userfield_select_option',
	'userfield_select_multiple',
	'userfield_textarea',
	'userfield_wrapper',
	'modifyoptions_timezone',
	'modifyprofile_birthday',
	'facebook_associate',
	'facebook_disassociate',
	'facebook_importregister',
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'requestemail' => array(
		'activate_requestemail'
	),
	'none' => array(
		'activateform'
	)
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_user.php');
require_once(DIR . '/includes/functions_misc.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$vbulletin->input->clean_gpc('r', 'a', TYPE_NOHTML);
$vbulletin->input->clean_gpc('r', 'u', TYPE_NOHTML);
$coppaage = $vbulletin->input->clean_gpc('c', COOKIE_PREFIX . 'coppaage', TYPE_STR);

if (empty($_REQUEST['do']) AND $vbulletin->GPC['a'] == '')
{
	$_REQUEST['do'] = 'register';
}

($hook = vBulletinHook::fetch_hook('register_start')) ? eval($hook) : false;

// ############################### start checkdate ###############################
if ($_POST['do'] == 'checkdate')
{
	// check their birthdate
	$vbulletin->input->clean_array_gpc('r', array(
		'month' => TYPE_UINT,
		'year'  => TYPE_UINT,
		'day'   => TYPE_UINT,
	));

	$current['year'] = date('Y');
	$current['month'] = date('m');
	$current['day'] = date('d');

	if ($vbulletin->GPC['month'] == 0 OR $vbulletin->GPC['day'] == 0 OR !preg_match('#^\d{4}$#', $vbulletin->GPC['year']) OR $vbulletin->GPC['year'] < 1901 OR $vbulletin->GPC['year'] > $current['year'])
	{
		eval(standard_error(fetch_error('select_valid_dob', $current['year'])));
	}

	($hook = vBulletinHook::fetch_hook('register_checkdate')) ? eval($hook) : false;

	if ($vbulletin->options['usecoppa'] AND $vbulletin->options['checkcoppa'] AND $coppaage)
	{
		$dob = explode('-', $coppaage);
		$month = $dob[0];
		$day = $dob[1];
		$year = $dob[2];
	}

	if ($vbulletin->GPC['year'] < 1970 OR (mktime(0, 0, 0, $vbulletin->GPC['month'], $vbulletin->GPC['day'], $vbulletin->GPC['year']) <= mktime(0, 0, 0, $current['month'], $current['day'], $current['year'] - 13)))
	{
		$_REQUEST['do'] = 'register';
	}
	else
	{
		if ($vbulletin->options['checkcoppa'] AND $vbulletin->options['usecoppa'])
		{
			vbsetcookie('coppaage', $vbulletin->GPC['month'] . '-' . $vbulletin->GPC['day'] . '-' . $vbulletin->GPC['year'], 1);
		}

		if ($vbulletin->options['usecoppa'] == 2)
		{
			// turn away as they're under 13
			eval(standard_error(fetch_error('under_thirteen_registration_denied')));
		}
		else
		{
			$_REQUEST['do'] = 'register';
		}
	}
}
// if the page was refreshed after birthday has been checked and cookied,
// then simply perform the register action, #37319
else if ($_REQUEST['do'] == 'checkdate')
{
	$_REQUEST['do'] = 'register';
}

// ############################### start add member ###############################
if ($_POST['do'] == 'addmember')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'agree'               => TYPE_BOOL,
		'options'             => TYPE_ARRAY_BOOL,
		'username'            => TYPE_STR,
		'email'               => TYPE_STR,
		'emailconfirm'        => TYPE_STR,
		'parentemail'         => TYPE_STR,
		'password'            => TYPE_STR,
		'password_md5'        => TYPE_STR,
		'passwordconfirm'     => TYPE_STR,
		'passwordconfirm_md5' => TYPE_STR,
		'referrername'        => TYPE_NOHTML,
		'coppauser'           => TYPE_BOOL,
		'day'                 => TYPE_UINT,
		'month'               => TYPE_UINT,
		'year'                => TYPE_UINT,
		'timezoneoffset'      => TYPE_NUM,
		'dst'                 => TYPE_UINT,
		'userfield'           => TYPE_ARRAY,
		'showbirthday'        => TYPE_UINT,
		'humanverify'         => TYPE_ARRAY,
		'fbaccesstoken'       => TYPE_STR,
		'fbuserid'            => TYPE_STR,
	));

	if (!$vbulletin->GPC['agree'])
	{
		eval(standard_error(fetch_error('register_not_agreed', fetch_seo_url('forumhome', array()))));
	}

	if (!$vbulletin->options['allowregistration'])
	{
		eval(standard_error(fetch_error('noregister')));
	}

	// check for multireg
	if ($vbulletin->userinfo['userid'] AND !$vbulletin->options['allowmultiregs'])
	{
		eval(standard_error(fetch_error('alreadyregistered', $vbulletin->userinfo['username'], $vbulletin->session->vars['sessionurl'])));
	}

	// init user datamanager class
	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_ARRAY);

	// coppa option
	if ($vbulletin->options['usecoppa'])
	{
		$current['year'] = date('Y');
		$current['month'] = date('m');
		$current['day'] = date('d');

		$month = $vbulletin->GPC['month'];
		$year = $vbulletin->GPC['year'];
		$day = $vbulletin->GPC['day'];

		if ($year > 1970 AND mktime(0, 0, 0, $month, $day, $year) > mktime(0, 0, 0, $current['month'], $current['day'], $current['year'] - 13))
		{
			if ($vbulletin->options['checkcoppa'])
			{
				vbsetcookie('coppaage', $month . '-' . $day . '-' . $year, 1);
			}

			if ($vbulletin->options['usecoppa'] == 2)
			{
				standard_error(fetch_error('under_thirteen_registration_denied'));
			}

			$vbulletin->GPC['coppauser'] = true;

		}
		else
		{
			$vbulletin->GPC['coppauser'] = false;
		}
	}
	else
	{
		$vbulletin->GPC['coppauser'] = false;
	}

	$userdata->set_info('coppauser', $vbulletin->GPC['coppauser']);
	$userdata->set_info('coppapassword', $vbulletin->GPC['password']);
	$userdata->set_bitfield('options', 'coppauser', $vbulletin->GPC['coppauser']);
	$userdata->set('parentemail', $vbulletin->GPC['parentemail']);

	// check for missing fields
	if (empty($vbulletin->GPC['username'])
		OR empty($vbulletin->GPC['email'])
		OR empty($vbulletin->GPC['emailconfirm'])
		OR ($vbulletin->GPC['coppauser'] AND empty($vbulletin->GPC['parentemail']))
		OR (empty($vbulletin->GPC['password']) AND empty($vbulletin->GPC['password_md5']))
		OR (empty($vbulletin->GPC['passwordconfirm']) AND empty($vbulletin->GPC['passwordconfirm_md5']))
	)
	{
		$userdata->error('fieldmissing');
	}

	// check for matching passwords
	if ($vbulletin->GPC['password'] != $vbulletin->GPC['passwordconfirm'] OR (strlen($vbulletin->GPC['password_md5']) == 32 AND $vbulletin->GPC['password_md5'] != $vbulletin->GPC['passwordconfirm_md5']))
	{
		$userdata->error('passwordmismatch');
	}

	// check for matching email addresses
	if ($vbulletin->GPC['email'] != $vbulletin->GPC['emailconfirm'])
	{
		$userdata->error('emailmismatch');
	}
	$userdata->set('email', $vbulletin->GPC['email']);

	$userdata->set('username', $vbulletin->GPC['username']);

	// set password
	$userdata->set('password', ($vbulletin->GPC['password_md5'] ? $vbulletin->GPC['password_md5'] : $vbulletin->GPC['password']));

	// check referrer
	if ($vbulletin->GPC['referrername'] AND !$vbulletin->userinfo['userid'])
	{
		$userdata->set('referrerid', $vbulletin->GPC['referrername']);
	}

	// Human Verification, not neccessary if user is logged into facebook
	if (fetch_require_hvcheck('register') AND (!is_facebookenabled() OR (is_facebookenabled() AND !vB_Facebook::instance()->userIsLoggedIn())))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verify =& vB_HumanVerify::fetch_library($vbulletin);
		if (!$verify->verify_token($vbulletin->GPC['humanverify']))
		{
			$userdata->error($verify->fetch_error());
		}
	}

	// Set specified options
	if (!empty($vbulletin->GPC['options']))
	{
		foreach ($vbulletin->GPC['options'] AS $optionname => $onoff)
		{
			$userdata->set_bitfield('options', $optionname, $onoff);
		}
	}

	$forcelist = array(
		'adminemail',
		'showemail',
	);
	foreach ($forcelist AS $option)
	{
		if (!$vbulletin->GPC['options'][$option])
		{
			$userdata->set_bitfield('options', $option, 0);
		}
	}

	// assign user to usergroup 3 if email needs verification
	if ($vbulletin->options['verifyemail'])
	{
		$newusergroupid = 3;
	}
	else if ($vbulletin->options['moderatenewmembers'] OR $vbulletin->GPC['coppauser'])
	{
		$newusergroupid = 4;
	}
	else
	{
		$newusergroupid = 2;
	}
	// set usergroupid
	$userdata->set('usergroupid', $newusergroupid);

	// set languageid
	$userdata->set('languageid', $vbulletin->userinfo['languageid']);

	// set user title
	$userdata->set_usertitle('', false, $vbulletin->usergroupcache["$newusergroupid"], false, false);

	// set profile fields
	$customfields = $userdata->set_userfields($vbulletin->GPC['userfield'], true, 'register');

	// set birthday
	$userdata->set('showbirthday', $vbulletin->GPC['showbirthday']);
	$userdata->set('birthday', array(
		'day'   => $vbulletin->GPC['day'],
		'month' => $vbulletin->GPC['month'],
		'year'  => $vbulletin->GPC['year']
	));

	// set time options
	$userdata->set_dst($vbulletin->GPC['dst']);
	$userdata->set('timezoneoffset', $vbulletin->GPC['timezoneoffset']);

	// register IP address
	$userdata->set('ipaddress', IPADDRESS);

	// check if we are associating the new user with a facebook account
	if (is_facebookenabled() AND (vB_Facebook::instance()->userIsLoggedIn() OR (defined('VB_API') AND VB_API === true AND $vbulletin->GPC['fbuserid'] AND $vbulletin->GPC['fbaccesstoken'])))
	{
		save_fbdata($userdata);
	}

	($hook = vBulletinHook::fetch_hook('register_addmember_process')) ? eval($hook) : false;

	$userdata->pre_save();

	// check for errors
	if (!empty($userdata->errors))
	{
		$_REQUEST['do'] = 'register';

		$errorlist = '';
		if (!VB_API)
		{
			foreach ($userdata->errors AS $index => $error)
			{
				$errorlist .= "<li>$error</li>";
			}
		}
		else
		{
			$errorlist = $userdata->errors;
		}

		$username = htmlspecialchars_uni($vbulletin->GPC['username']);
		$email = htmlspecialchars_uni($vbulletin->GPC['email']);
		$emailconfirm = htmlspecialchars_uni($vbulletin->GPC['emailconfirm']);
		$parentemail = htmlspecialchars_uni($vbulletin->GPC['parentemail']);
		$selectdst = array($vbulletin->GPC['dst'] => 'selected="selected"');
		$sbselected = array($vbulletin->GPC['showbirthday'] => 'selected="selected"');
		$show['errors'] = true;
	}
	else
	{
		$show['errors'] = false;

		// save the data
		$vbulletin->userinfo['userid']
			= $userid
			= $userdata->save();

		if ($userid)
		{
			$userinfo = fetch_userinfo($userid,0,0,0,true); // Read Master
			$userdata_rank =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
			$userdata_rank->set_existing($userinfo);
			$userdata_rank->set('posts', 0);
			$userdata_rank->save();

			// force a new session to prevent potential issues with guests from the same IP, see bug #2459
			require_once(DIR . '/includes/functions_login.php');
			$vbulletin->session->created = false;
			process_new_login('', false, '');

			// send new user email
			if ($vbulletin->options['newuseremail'] != '')
			{
				$username = $vbulletin->GPC['username'];
				$email = $vbulletin->GPC['email'];

				if ($birthday = $userdata->fetch_field('birthday'))
				{
					$bday = explode('-', $birthday);
					$year = vbdate('Y', TIMENOW, false, false);
					$month = vbdate('n', TIMENOW, false, false);
					$day = vbdate('j', TIMENOW, false, false);
					if ($year > $bday[2] AND $bday[2] > 1901 AND $bday[2] != '0000')
					{
						require_once(DIR . '/includes/functions_misc.php');
						$vbulletin->options['calformat1'] = mktimefix($vbulletin->options['calformat1'], $bday[2]);
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
						$birthday = vbdate($vbulletin->options['calformat1'], mktime(0, 0, 0, $bday[0], $bday[1], $yearpass), false, true, false);
					}
					else
					{
						// lets send a valid year as some PHP3 don't like year to be 0
						$birthday = vbdate($vbulletin->options['calformat2'], mktime(0, 0, 0, $bday[0], $bday[1], 1992), false, true, false);
					}

					if ($birthday == '')
					{
						// Should not happen; fallback for win32 bug regarding mktime and dates < 1970
						if ($bday[2] == '0000')
						{
							$birthday = "$bday[0]-$bday[1]";
						}
						else
						{
							$birthday = "$bday[0]-$bday[1]-$bday[2]";
						}
					}
				}

				if ($userdata->fetch_field('referrerid') AND $vbulletin->GPC['referrername'])
				{
					$referrer = unhtmlspecialchars($vbulletin->GPC['referrername']);
				}
				else
				{
					$referrer = $vbphrase['n_a'];
				}
				$ipaddress = IPADDRESS;
				$memberlink = fetch_seo_url('member|nosession|bburl', array('userid' => $userid, 'username' => htmlspecialchars_uni($vbulletin->GPC['username'])));

				eval(fetch_email_phrases('newuser', 0));

				$newemails = explode(' ', $vbulletin->options['newuseremail']);
				foreach ($newemails AS $toemail)
				{
					if (trim($toemail))
					{
						vbmail($toemail, $subject, $message);
					}
				}
			}

			$username = htmlspecialchars_uni($vbulletin->GPC['username']);
			$email = htmlspecialchars_uni($vbulletin->GPC['email']);

			// sort out emails and usergroups
			if ($vbulletin->options['verifyemail'])
			{
				$activateid = build_user_activation_id($userid, (($vbulletin->options['moderatenewmembers'] OR $vbulletin->GPC['coppauser']) ? 4 : 2), 0);

				eval(fetch_email_phrases('activateaccount'));

				vbmail($email, $subject, $message, true);

			}
			else if ($newusergroupid == 2)
			{
				if ($vbulletin->options['welcomemail'])
				{
					eval(fetch_email_phrases('welcomemail'));
					vbmail($email, $subject, $message);
				}
			}

			($hook = vBulletinHook::fetch_hook('register_addmember_complete')) ? eval($hook) : false;

			if ($vbulletin->GPC['coppauser'])
			{
				$_REQUEST['do'] = 'coppaform';
			}
			else
			{
				if ($vbulletin->options['verifyemail'])
				{
					eval(standard_error(fetch_error('registeremail', $username, $email, create_full_url($vbulletin->url . $vbulletin->session->vars['sessionurl_q'])), '', false));
				}
				else
				{
					$vbulletin->url = str_replace('"', '', $vbulletin->url);
					if (!$vbulletin->url)
					{
						$vbulletin->url = fetch_seo_url('forumhome', array());
					}
					else
					{
						$vbulletin->url = (strpos($vbulletin->url, 'register.php') !== false ?
							fetch_seo_url('forumhome', array()) : $vbulletin->url);
					}

					if ($vbulletin->options['moderatenewmembers'])
					{
						eval(standard_error(fetch_error('moderateuser', $username, fetch_seo_url('forumhome', array())), '', false));
					}
					else
					{
						eval(standard_error(fetch_error('registration_complete', $username,
							$vbulletin->session->vars['sessionurl'], fetch_seo_url('forumhome', array())), '', false));
					}
				}
			}
		}
	}
}
else if ($_GET['do'] == 'addmember')
{
	// hmm, this probably happened because of a template edit that put the login box in the header.
	exec_header_redirect(fetch_seo_url('forumhome|nosession', array()));
}

// ############################### start facebook dis-associate ###############################
// process facebook dis-association
if ($_REQUEST['do'] == 'fbdisconnect')
{
	// only disconnect registered vb users, (not facebook only users, because they will not be able to login anymore)
	if (is_facebookenabled() AND !empty($vbulletin->userinfo['userid']) AND $vbulletin->userinfo['logintype'] == 'vb')
	{

		$vbulletin->input->clean_array_gpc('p', array(
			'confirm' => TYPE_NOHTML,
			'deny'    => TYPE_NOHTML,
		));

		// user has confirmed dis-association, so modify the data and logout of fb
		if ($vbulletin->GPC['confirm'])
		{
			// instantiate the data manager class
			$userdata =& datamanager_init('user', $vbulletin, ERRTYPE_STANDARD);
			$userdata->set_existing($vbulletin->userinfo);

			// uset the fbuserid association and save
			$userdata->set('fbuserid', null);
			$userdata->save();

			// logout of facebook connect
			do_facebooklogout();

			// redirect to the forum home
			exec_header_redirect(fetch_seo_url('forumhome|nosession', array()));
		}

		// user clicked 'No' for dis-association, so direct them back to the forums page
		else if ($vbulletin->GPC['deny'])
		{
			$vbulletin->url = fetch_seo_url('forumhome', array());
			print_standard_redirect('action_cancelled');
		}

		// otherwise, make sure current FB account is associated with vb account
		// and display the confirmation form if so
		else if (vB_Facebook::instance()->userIsLoggedIn() AND $vbulletin->userinfo['userid'] == vB_Facebook::instance()->getVbUseridFromFbUserid())
		{
			$navbits = construct_navbits(array(
				'register.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['register']
			));
			$navbar = render_navbar_template($navbits);

			$fb_userinfo = vB_Facebook::instance()->getFbUserInfo();
			$fbname = $fb_userinfo['name'];
			$fbprofileurl = get_fbprofileurl();
			$fbprofilepicurl = !empty($fb_userinfo['pic']) ? $fb_userinfo['pic'] : get_fbprofilepicurl();;

			$templater = vB_Template::create('facebook_disassociate');
			$templater->register_page_templates();
			$templater->register('navbar', $navbar);
			$templater->register('userinfo', $vbulletin->userinfo);
			$templater->register('fbuserid', vB_Facebook::instance()->getLoggedInFbUserId());
			$templater->register('fbname', $fbname);
			$templater->register('fbprofileurl', $fbprofileurl);
			$templater->register('fbprofilepicurl', $fbprofilepicurl);
			print_output($templater->render());
		}
	}

	// if we dont meet any of the above action criteria, display regular registration form
	$_REQUEST['do'] = 'register';
}

// ############################### start facebook associate ###############################
// process facebook association
if ($_POST['do'] == 'fbconnect')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'link'   => TYPE_NOHTML,
		'nolink' => TYPE_NOHTML,
	));

	// if facebook is not enabled, we we somehow lost either the fb or vb session,
	// display regular registration form
	if (!is_facebookenabled()
		OR empty($vbulletin->userinfo['userid'])
		OR !vB_Facebook::instance()->userIsLoggedIn())
	{
		if (defined('VB_API') AND VB_API === true)
		{
			if (!is_facebookenabled())
			{
				eval(standard_error(fetch_error('facebook_disabled')));
			}
			else if (empty($vbulletin->userinfo['userid']))
			{
				eval(standard_error(fetch_error('usernotloggedin')));
			}
			else
			{
				eval(standard_error(fetch_error('facebook_usernotloggedin')));
			}
		}
		else
		{
			$_REQUEST['do'] = 'register';
		}
	}
	else if ($vbulletin->GPC['link'])
	{
		// instantiate the data manager class
		$userdata =& datamanager_init('user', $vbulletin, ERRTYPE_ARRAY);
		$userdata->set_existing($vbulletin->userinfo);

		// save the fb data
		save_fbdata($userdata, false);

		// if there were errors in the association code
		// go back to the association form and display errors
		$userdata->pre_save();
		if (!empty($userdata->errors))
		{
			$_REQUEST['do'] = 'register';
			$errorlist = '';
			if (!VB_API)
			{
				foreach ($userdata->errors AS $index => $error)
				{
					$errorlist .= "<li>$error</li>";
				}
			}
			else
			{
				$error = array_pop($userdata->errors);
				$output = is_array($error) ? $error[0] : $error;
				eval(standard_error(fetch_error($output)));
			}

			$show['errors'] = true;
		}

		// otherwise, we can save the association and redirect someplace nice
		else
		{
			$userdata->save();
			$vbulletin->url = $vbulletin->options['forumhome'] . '.php' . $vbulletin->session->vars['sessionurl_q'];
			print_standard_redirect(array('redirect_updatethanks', $vbulletin->userinfo['username']));
		}
	}
	// user does not want to link accounts, redirect to forum page
	else if ($vbulletin->GPC['nolink'])
	{
		$vbulletin->url = $vbulletin->options['forumhome'] . '.php' . $vbulletin->session->vars['sessionurl_q'];
		print_standard_redirect(array('redirect_updatethanks',$vbulletin->userinfo['username']));
	}
}

// ############################### start register ###############################
if ($_REQUEST['do'] == 'register')
{
	// check the conditions are right for auto-register, logged in fb user/
	// not logged into vB, no associated account, and email permissions available
	if (is_facebookenabled()
		AND vB_Facebook::instance()->userIsLoggedIn()
		AND empty($vbulletin->userinfo['userid'])
		AND !vB_Facebook::instance()->getVbUseridFromFbUserid()
		AND $vbulletin->options['facebookautoregister']
		AND check_emailpermissions()
	)
	{
		// instantiate the data manager class
		$userdata =& datamanager_init('user', $vbulletin, ERRTYPE_ARRAY);

		// populate the datamanager with auto reg data
		save_fbautoregister($userdata);

		($hook = vBulletinHook::fetch_hook('register_addmember_process')) ? eval($hook) : false;

		// if there were errors in the association code
		// go back to the association form and display errors
		$userdata->pre_save();
		if (!empty($userdata->errors))
		{
			$_REQUEST['do'] = 'register';
			$errorlist = '';
			foreach ($userdata->errors AS $index => $error)
			{
				$errorlist .= "<li>$error</li>";
			}
			$show['errors'] = true;
		}

		// if no errors, auto-register user
		else
		{
			$show['errors'] = false;

			// save the data
			$vbulletin->userinfo['userid']
				= $userid
				= $userdata->save();

			if ($userid)
			{
				$username = $userdata->fetch_field('username');
				$email = $userdata->fetch_field('email');

				$userinfo = fetch_userinfo($userid);
				$userdata_rank =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
				$userdata_rank->set_existing($userinfo);
				$userdata_rank->set('posts', 0);
				$userdata_rank->save();

				// force a new session to prevent potential issues with guests from the same IP, see bug #2459
				require_once(DIR . '/includes/functions_login.php');
				$vbulletin->session->created = false;
				process_new_login('', false, '');

				// send new user email
				if ($vbulletin->options['newuseremail'] != '')
				{
					$referrer = 'Facebook Connect';
					$ipaddress = IPADDRESS;
					$memberlink = fetch_seo_url('member|nosession|bburl', array('userid' => $userid, 'username' => htmlspecialchars_uni($vbulletin->GPC['username'])));

					eval(fetch_email_phrases('newuser', 0));

					$newemails = explode(' ', $vbulletin->options['newuseremail']);
					foreach ($newemails AS $toemail)
					{
						if (trim($toemail))
						{
							vbmail($toemail, $subject, $message);
						}
					}
				}

				if ($newusergroupid == 2 AND $vbulletin->options['welcomemail'])
				{
					eval(fetch_email_phrases('welcomemail'));
					vbmail($email, $subject, $message);
				}

				($hook = vBulletinHook::fetch_hook('register_addmember_complete')) ? eval($hook) : false;

				// now redirect the user to the home page
				$vbulletin->url = str_replace('"', '', $vbulletin->url);
				if (!$vbulletin->url)
				{
					$vbulletin->url = fetch_seo_url('forumhome', array());
				}
				else
				{
					$vbulletin->url = iif(strpos($vbulletin->url, 'register.php') !== false, fetch_seo_url('forumhome', array()), $vbulletin->url);
				}

				if ($vbulletin->options['moderatenewmembers'])
				{
					eval(standard_error(fetch_error('moderateuser', $username, fetch_seo_url('forumhome', array())), '', false));
				}
				else
				{
					eval(standard_error(fetch_error('registration_complete', $username,
						$vbulletin->session->vars['sessionurl'], fetch_seo_url('forumhome', array())), '', false));
				}
			}
		}
	}
	// if facebook connect is enabled and user is logged into both vb and facebook
	// but accounts arent associated, display the association form instead of register form
	else if (is_facebookenabled()
		AND	!empty($vbulletin->userinfo['userid']) /* logged into vb */
		AND	vB_Facebook::instance()->userIsLoggedIn() /* logged into facebook */
		AND	$vbulletin->userinfo['fbuserid'] != vB_Facebook::instance()->getLoggedInFbUserId() /* not already associated */
	)
	{
		// generate the form for importing facebook data
		$fbimportform = construct_fbimportform();

		$fb_userinfo = vB_Facebook::instance()->getFbUserInfo();
		$fbname = $fb_userinfo['name'];
		$fbprofileurl = get_fbprofileurl();
		$fbprofilepicurl = !empty($fb_userinfo['pic']) ? $fb_userinfo['pic'] : get_fbprofilepicurl();;

		// check if user already has a different fb account, and inform them if so
		$show['fb_alreadyassociated'] = !empty($vbulletin->userinfo['fbuserid']);

		$navbits = construct_navbits(array(
			'register.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['link_accounts']
		));
		$navbar = render_navbar_template($navbits);

		$templater = vB_Template::create('facebook_associate');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('userinfo', $vbulletin->userinfo);
		$templater->register('fbuserid', vB_Facebook::instance()->getLoggedInFbUserId());
		$templater->register('fbname', $fbname);
		$templater->register('fbprofileurl', $fbprofileurl);
		$templater->register('fbprofilepicurl', $fbprofilepicurl);
		$templater->register('fbimportform', $fbimportform);
		$templater->register('currentfbuserid', $vbulletin->userinfo['fbuserid']);
		$templater->register('currentfbname', $vbulletin->userinfo['fbname']);
		$templater->register('errorlist', $errorlist);
		print_output($templater->render());
	}
	else if (is_facebookenabled() AND vB_Facebook::instance()->userIsLoggedIn())
	{
		if (!vB_Facebook::instance()->verifyLoginFromServer())
		{
			vB_Facebook::instance()->doLogoutFbUser();
			$show['facebookuser'] = false;
		}
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'year'    => TYPE_UINT,
		'month'   => TYPE_UINT,
		'day'     => TYPE_UINT,
		'options' => TYPE_ARRAY_BOOL,
	));

	$url = $vbulletin->url;

	$navbits['register.php' . $vbulletin->session->vars['sessionurl_q']] = $vbphrase['register'];
	$navbits = construct_navbits($navbits);

	if (!$vbulletin->options['allowregistration'])
	{
		eval(standard_error(fetch_error('noregister')));
	}

	if ($vbulletin->userinfo['userid'] AND !$vbulletin->options['allowmultiregs'])
	{
		eval(standard_error(fetch_error('alreadyregistered', $vbulletin->userinfo['username'], $vbulletin->session->vars['sessionurl'])));
	}

	// if neccessary validate COPPA info
	if ($vbulletin->options['usecoppa'])
	{
		if ($vbulletin->options['checkcoppa'] AND $coppaage)
		{
			$dob = explode('-', $coppaage);
			$month = $dob[0];
			$day = $dob[1];
			$year = $dob[2];
		}
		else
		{
			$month = $vbulletin->input->clean_gpc('r', 'month', TYPE_UINT);
			$year = $vbulletin->input->clean_gpc('r', 'year', TYPE_UINT);
			$day = $vbulletin->input->clean_gpc('r', 'day', TYPE_UINT);
		}

		if (!$month OR !$day OR !$year)
		{
			$navbar = render_navbar_template($navbits);
			// Show age controls
			$templater = vB_Template::create('register_verify_age');

			$templater->register_page_templates();
			$templater->register('url', $url);
			$templater->register('navbar', $navbar);
			print_output($templater->render());
		}
		else	// verify age
		{
			$current['year'] = date('Y');
			$current['month'] = date('m');
			$current['day'] = date('d');

			if ($year < 1970 OR (mktime(0, 0, 0, $month, $day, $year) <= mktime(0, 0, 0, $current['month'], $current['day'], $current['year'] - 13)))
			{	// this user is >13
				$show['coppa'] = false;
			}
			else if ($vbulletin->options['usecoppa'] == 2)
			{
				if ($vbulletin->options['checkcoppa'])
				{
					vbsetcookie('coppaage', $month . '-' . $day . '-' . $year, 1);
				}
				eval(standard_error(fetch_error('under_thirteen_registration_denied')));
			}
			else
			{
				if ($vbulletin->options['checkcoppa'])
				{
					vbsetcookie('coppaage', $month . '-' . $day . '-' . $year, 1);
				}
				$show['coppa'] = true;
			}
		}
	}
	else
	{
		$show['coppa'] = false;
	}

	($hook = vBulletinHook::fetch_hook('register_form_start')) ? eval($hook) : false;

	if ($errorlist)
	{
		$checkedoff['adminemail'] = iif($vbulletin->GPC['options']['adminemail'], 'checked="checked"');
		$checkedoff['showemail'] = iif($vbulletin->GPC['options']['showemail'], 'checked="checked"');
	}
	else
	{
		$checkedoff['adminemail'] = iif(bitwise($vbulletin->bf_misc_regoptions['adminemail'], $vbulletin->options['defaultregoptions']), 'checked="checked"');
		$checkedoff['showemail'] = iif(bitwise($vbulletin->bf_misc_regoptions['receiveemail'], $vbulletin->options['defaultregoptions']), 'checked="checked"');
	}

	$htmlonoff = ($vbulletin->options['allowhtml'] ? $vbphrase['on'] : $vbphrase['off']);
	$bbcodeonoff = ($vbulletin->options['allowbbcode'] ? $vbphrase['on'] : $vbphrase['off']);
	$imgcodeonoff = ($vbulletin->options['allowbbimagecode'] ? $vbphrase['on'] : $vbphrase['off']);
	$videocodeonoff = ($vbulletin->options['allowbbvideocode'] ? $vbphrase['on'] : $vbphrase['off']);
	$smiliesonoff = ($vbulletin->options['allowsmilies'] ? $vbphrase['on'] : $vbphrase['off']);

	// human verification, which we can bypass if user has been verified on facebook
	if (fetch_require_hvcheck('register') AND (!is_facebookenabled() OR (is_facebookenabled() AND !vB_Facebook::instance()->userIsLoggedIn())))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verify =& vB_HumanVerify::fetch_library($vbulletin);
		$human_verify = $verify->output_token();
	}

	// Referrer
	if ($vbulletin->options['usereferrer'] AND !$vbulletin->userinfo['userid'])
	{
		exec_switch_bg();
		if ($errorlist)
		{
			$referrername = $vbulletin->GPC['referrername'];
		}
		else if ($vbulletin->GPC[COOKIE_PREFIX . 'referrerid'])
		{
			if ($referrername = $db->query_first_slave("SELECT username FROM " . TABLE_PREFIX . "user WHERE userid = " . $vbulletin->GPC[COOKIE_PREFIX . 'referrerid']))
			{
				$referrername = $referrername['username'];
			}
		}
		$show['referrer'] = true;
	}
	else
	{
		$show['referrer'] = false;
	}

	// get extra profile fields
	if ($show['coppa'])
	{
		$bgclass1 = 'alt1';
	}

	// get facebook profile data to pre-populate custom profile fields
	$fb_importform_skip_fields = array();
	$fb_profilefield_info = array();
	if (is_facebookenabled() AND vB_Facebook::instance()->userIsLoggedIn())
	{
		$fb_profilefield_info = get_vbprofileinfo();
	}

	if ($vbulletin->options['reqbirthday'] AND !$vbulletin->options['usecoppa'])
	{
		$fb_importform_skip_fields[] = 'birthday';

		if ($vbulletin->options['fb_userfield_birthday'] AND !empty($fb_profilefield_info['birthday']) AND !$vbulletin->GPC['day'] AND !$vbulletin->GPC['month'] AND !$vbulletin->GPC['year'])
		{
			list($bd_month, $bd_day, $bd_year) = explode('/', $fb_profilefield_info['birthday']);
			$vbulletin->GPC['day'] = intval($bd_day);
			$vbulletin->GPC['month'] = intval($bd_month);
			$vbulletin->GPC['year'] = intval($bd_year);
		}

		$show['birthday'] = true;
		$monthselected[str_pad($vbulletin->GPC['month'], 2, '0', STR_PAD_LEFT)] = 'selected="selected"';
		$dayselected[str_pad($vbulletin->GPC['day'], 2, '0', STR_PAD_LEFT)] = 'selected="selected"';
		$year = !$vbulletin->GPC['year'] ? '' : $vbulletin->GPC['year'];

		// Default Birthday Privacy option to show all
		if (empty($errorlist))
		{
			$sbselected = array(2 => 'selected="selected"');
		}
		$templater = vB_Template::create('modifyprofile_birthday');
			$templater->register('birthdate', $birthdate);
			$templater->register('dayselected', $dayselected);
			$templater->register('monthselected', $monthselected);
			$templater->register('sbselected', $sbselected);
			$templater->register('year', $year);
		$birthdayfields = $templater->render();
	}
	else
	{
		$show['birthday'] = false;

		$birthdayfields = '';
	}

	$customfields_other = '';
	$customfields_profile = '';
	$customfields_option = '';

	$profilefields = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "profilefield
		WHERE editable > 0 AND required <> 0
		ORDER BY displayorder
	");
	while ($profilefield = $db->fetch_array($profilefields))
	{
		$profilefieldname = "field$profilefield[profilefieldid]";
		$optionalname = $profilefieldname . '_opt';
		$optionalfield = '';
		$optional = '';
		$profilefield['title'] = $vbphrase[$profilefieldname . '_title'];
		$profilefield['description'] = $vbphrase[$profilefieldname . '_desc'];
		$profilefield['currentvalue'] = '';

		if ($errorlist AND isset($vbulletin->GPC['userfield']["$profilefieldname"]))
		{
			$profilefield['currentvalue'] = $vbulletin->GPC['userfield']["$profilefieldname"];
		}

		// add profile data from facebook as a default if available
		if ($profilefield['type'] == 'input' OR $profilefield['type'] == 'textarea')
		{
			switch($profilefieldname)
			{
				case $vbulletin->options['fb_userfield_biography']:
					$profilefield['data'] = $fb_profilefield_info['biography'];
					$fb_importform_skip_fields[] = 'biography';
					break;

				case $vbulletin->options['fb_userfield_location']:
					$profilefield['data'] = $fb_profilefield_info['location'];
					$fb_importform_skip_fields[] = 'location';
					break;

				case $vbulletin->options['fb_userfield_interests']:
					$profilefield['data'] = $fb_profilefield_info['interests'];
					$fb_importform_skip_fields[] = 'interests';
					break;

				case $vbulletin->options['fb_userfield_occupation']:
					$profilefield['data'] = $fb_profilefield_info['occupation'];
					$fb_importform_skip_fields[] = 'occupation';
					break;
			}
		}

		$custom_field_holder = '';

		if ($profilefield['type'] == 'input')
		{
			if (empty($profilefield['currentvalue']) AND !empty($profilefield['data']))
			{
				$profilefield['currentvalue'] = $profilefield['data'];
			}
			else
			{
				$profilefield['currentvalue'] = htmlspecialchars_uni($profilefield['currentvalue']);
			}
			$templater = vB_Template::create('userfield_textbox');
				$templater->register('profilefield', $profilefield);
				$templater->register('profilefieldname', $profilefieldname);
			$custom_field_holder = $templater->render();
		}
		else if ($profilefield['type'] == 'textarea')
		{
			if (empty($profilefield['currentvalue']) AND !empty($profilefield['data']))
			{
				$profilefield['currentvalue'] = $profilefield['data'];
			}
			else
			{
				$profilefield['currentvalue'] = htmlspecialchars_uni($profilefield['currentvalue']);
			}
			$templater = vB_Template::create('userfield_textarea');
				$templater->register('profilefield', $profilefield);
				$templater->register('profilefieldname', $profilefieldname);
			$custom_field_holder = $templater->render();
		}
		else if ($profilefield['type'] == 'select')
		{
			$data = unserialize($profilefield['data']);
			$selectbits = '';

			if ($profilefield['optional'])
			{
				$optional = htmlspecialchars_uni($vbulletin->GPC['userfield']["$optionalname"]);

				$templater = vB_Template::create('userfield_optional_input');
					$templater->register('optional', $optional);
					$templater->register('optionalname', $optionalname);
					$templater->register('profilefield', $profilefield);
					$templater->register('tabindex', $tabindex);
				$optionalfield = $templater->render();
			}

			$foundselect = 0;
			foreach ($data AS $key => $val)
			{
				$key++;
				$selected = '';
				if (isset($profilefield['currentvalue']))
				{
					if ($key == $profilefield['currentvalue'])
					{
						$selected = 'selected="selected"';
						$foundselect = 1;
					}
				}
				else if ($profilefield['def'] AND $key == 1)
				{
					$selected = 'selected="selected"';
					$foundselect = 1;
				}

				$templater = vB_Template::create('userfield_select_option');
					$templater->register('key', $key);
					$templater->register('selected', $selected);
					$templater->register('val', $val);
				$selectbits .= $templater->render();
			}

			$show['noemptyoption'] = iif($profilefield['def'] != 2, true, false);

			if (!$foundselect AND $show['noemptyoption'])
			{
				$selected = 'selected="selected"';
			}
			else
			{
				$selected = '';
			}

			$templater = vB_Template::create('userfield_select');
				$templater->register('optionalfield', $optionalfield);
				$templater->register('profilefield', $profilefield);
				$templater->register('profilefieldname', $profilefieldname);
				$templater->register('selectbits', $selectbits);
				$templater->register('selected', $selected);
			$custom_field_holder = $templater->render();
		}
		else if ($profilefield['type'] == 'radio')
		{
			$data = unserialize($profilefield['data']);
			$radiobits = '';
			$foundfield = 0;

			if ($profilefield['optional'])
			{
				$optional = htmlspecialchars_uni($vbulletin->GPC['userfield']["$optionalname"]);
				if ($optional)
				{
					$foundfield = 1;
				}

				$templater = vB_Template::create('userfield_optional_input');
					$templater->register('optional', $optional);
					$templater->register('optionalname', $optionalname);
					$templater->register('profilefield', $profilefield);
					$templater->register('tabindex', $tabindex);
				$optionalfield = $templater->render();
			}

			foreach ($data AS $key => $val)
			{
				$key++;
				$checked = '';
				if (!$foundfield)
				{
					if (!$profilefield['currentvalue'] AND $key == 1 AND $profilefield['def'] == 1)
					{
						$checked = 'checked="checked"';
					}
					else if ($key == $profilefield['currentvalue'])
					{
						$checked = 'checked="checked"';
					}
				}

				$templater = vB_Template::create('userfield_radio_option');
					$templater->register('checked', $checked);
					$templater->register('key', $key);
					$templater->register('profilefieldname', $profilefieldname);
					$templater->register('val', $val);
				$radiobits .= $templater->render();
			}

			$templater = vB_Template::create('userfield_radio');
				$templater->register('optionalfield', $optionalfield);
				$templater->register('profilefield', $profilefield);
				$templater->register('profilefieldname', $profilefieldname);
				$templater->register('radiobits', $radiobits);
			$custom_field_holder = $templater->render();
		}
		else if ($profilefield['type'] == 'checkbox')
		{
			$data = unserialize($profilefield['data']);
			$radiobits = '';
			foreach ($data AS $key => $val)
			{
				$key++;
				if (is_array($profilefield['currentvalue']) AND in_array($key, $profilefield['currentvalue']))
				{
					$checked = 'checked="checked"';
				}
				else
				{
					$checked = '';
				}
				$templater = vB_Template::create('userfield_checkbox_option');
					$templater->register('checked', $checked);
					$templater->register('key', $key);
					$templater->register('profilefieldname', $profilefieldname);
					$templater->register('val', $val);
				$radiobits .= $templater->render();
			}
			$templater = vB_Template::create('userfield_radio');
				$templater->register('optionalfield', $optionalfield);
				$templater->register('profilefield', $profilefield);
				$templater->register('profilefieldname', $profilefieldname);
				$templater->register('radiobits', $radiobits);
			$custom_field_holder = $templater->render();
		}
		else if ($profilefield['type'] == 'select_multiple')
		{
			$data = unserialize($profilefield['data']);
			$selectbits = '';
			$selected = '';

			if ($profilefield['height'] == 0)
			{
				$profilefield['height'] = count($data);
			}

			foreach ($data AS $key => $val)
			{
				$key++;
				if (is_array($profilefield['currentvalue']) AND in_array($key, $profilefield['currentvalue']))
				{
					$selected = 'selected="selected"';
				}
				else
				{
					$selected = '';
				}
				$templater = vB_Template::create('userfield_select_option');
					$templater->register('key', $key);
					$templater->register('selected', $selected);
					$templater->register('val', $val);
				$selectbits .= $templater->render();
			}
			$templater = vB_Template::create('userfield_select_multiple');
				$templater->register('profilefield', $profilefield);
				$templater->register('profilefieldname', $profilefieldname);
				$templater->register('selectbits', $selectbits);
			$custom_field_holder = $templater->render();
		}

		if ($profilefield['required'] == 2)
		{
			// not required to be filled in but still show
			$profile_variable =& $customfields_other;
		}
		else // required to be filled in
		{
			if ($profilefield['form'])
			{
				$profile_variable =& $customfields_option;
			}
			else
			{
				$profile_variable =& $customfields_profile;
			}
		}

		$templater = vB_Template::create('userfield_wrapper');
			$templater->register('custom_field_holder', $custom_field_holder);
			$templater->register('profilefield', $profilefield);
		$profile_variable .= $templater->render();
	}

	$usecoppa = $show['coppa'];
	$show['customfields_profile'] = ($customfields_profile OR $show['birthday']) ? true : false;
	$show['customfields_option'] = ($customfields_option) ? true : false;
	$show['customfields_other'] = ($customfields_other) ? true : false;
	$show['email'] = ($vbulletin->options['enableemail'] AND $vbulletin->options['displayemails']) ? true : false;

	$vbulletin->input->clean_array_gpc('p', array(
		'timezoneoffset' => TYPE_NUM
	));

	// where do we send in timezoneoffset?
	if ($vbulletin->GPC['timezoneoffset'])
	{
		$timezonesel = $vbulletin->GPC['timezoneoffset'];
	}
	else
	{
		$timezonesel = $vbulletin->options['timeoffset'];
	}

	// if applicable, set up some facebook data
	if (is_facebookenabled())
	{
		// make sure current user is logged in
		if (vB_Facebook::instance()->userIsLoggedIn())
		{
			// if users are allowed to import info from facebook, generate the form
			$fbimportform = construct_fbimportform('register', $fb_importform_skip_fields);

			// populate form fields with information from facebook if its available
			$fb_userinfo = vB_Facebook::instance()->getFbUserInfo();
			if (!empty($fb_userinfo))
			{
				$show['fb_email'] = (!empty($fb_userinfo['email']) ? true : false);
				$username = (!empty($fb_userinfo['name']) ? htmlspecialchars_uni($fb_userinfo['name']) : $username);
				$email = (!empty($fb_userinfo['email'])?$fb_userinfo['email']:$email);
				$emailconfirm = (!empty($fb_userinfo['email'])?$fb_userinfo['email']:$emailconfirm);
				$timezonesel = (!empty($fb_userinfo['timezone'])?$fb_userinfo['timezone']:$timezonesel);
				$fbname = $fb_userinfo['name'];
				$fbprofileurl = get_fbprofileurl();
				$fbprofilepicurl = !empty($fb_userinfo['pic']) ? $fb_userinfo['pic'] : get_fbprofilepicurl();;
			}
		}
	}

	require_once(DIR . '/includes/functions_misc.php');
	$timezoneoptions = '';
	foreach (fetch_timezone() AS $optionvalue => $timezonephrase)
	{
		$optiontitle = $vbphrase["$timezonephrase"];
		$optionselected = iif($optionvalue == $timezonesel, 'selected="selected"', '');
		$timezoneoptions .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
	}
	$templater = vB_Template::create('modifyoptions_timezone');
		$templater->register('selectdst', $selectdst);
		$templater->register('timezoneoptions', $timezoneoptions);
	$timezoneoptions = $templater->render();

	$navbits['register.php' . $vbulletin->session->vars['sessionurl_q']] = $vbphrase['register'];
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('register_form_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('register');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('birthdayfields', $birthdayfields);
		$templater->register('checkedoff', $checkedoff);
		$templater->register('customfields_option', $customfields_option);
		$templater->register('customfields_other', $customfields_other);
		$templater->register('customfields_profile', $customfields_profile);
		$templater->register('day', $day);
		$templater->register('email', $email);
		$templater->register('emailconfirm', $emailconfirm);
		$templater->register('errorlist', $errorlist);
		$templater->register('human_verify', $human_verify);
		$templater->register('month', $month);
		$templater->register('parentemail', $parentemail);
		$templater->register('password', $password);
		$templater->register('passwordconfirm', $passwordconfirm);
		$templater->register('referrername', $referrername);
		$templater->register('timezoneoptions', $timezoneoptions);
		$templater->register('url', $url);
		$templater->register('username', $username);
		$templater->register('year', $year);
		$templater->register('fbname', $fbname);
		$templater->register('fbprofileurl', $fbprofileurl);
		$templater->register('fbprofilepicurl', $fbprofilepicurl);
		$templater->register('fbimportform', $fbimportform);
	print_output($templater->render());
}

// ############################### start activate form ###############################
if ($vbulletin->GPC['a'] == 'ver')
{
	// get username and password
	if (!$vbulletin->userinfo['userid'])
	{
		$vbulletin->userinfo['username'] = '';
	}

	$navbits = construct_navbits(array('' => $vbphrase['activate_your_account']));
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('register_activateform')) ? eval($hook) : false;

	$templater = vB_Template::create('activateform');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
	print_output($templater->render());
}

// ############################### start activate ###############################
if ($_REQUEST['do'] == 'activate')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'username'		=> TYPE_NOHTML,
		'activateid'	=> TYPE_STR,

		// These three are cleaned so that they will exist and not be overwritten in the next step

		'u'				=> TYPE_UINT,
		'a'				=> TYPE_NOHTML,
		'i'				=> TYPE_STR,
	));

	if ($userinfo = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username='" . $db->escape_string($vbulletin->GPC['username']) . "'"))
	{
		$vbulletin->GPC['u'] = $userinfo['userid'];
		$vbulletin->GPC['a'] = 'act';
		$vbulletin->GPC['i'] = $vbulletin->GPC['activateid'];
	}
	else
	{
		eval(standard_error(fetch_error('badlogin', $vbulletin->options['bburl'], $vbulletin->session->vars['sessionurl'], $strikes)));
	}
}

if ($vbulletin->GPC['a'] == 'act')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'u'		=> TYPE_UINT,
		'i'		=> TYPE_STR,
	));

	$userinfo = verify_id('user', $vbulletin->GPC['u'], 1, 1);

	($hook = vBulletinHook::fetch_hook('register_activate_start')) ? eval($hook) : false;

	if ($userinfo['usergroupid'] == 3)
	{
		// check valid activation id
		$user = $db->query_first("
			SELECT activationid, usergroupid, emailchange
			FROM " . TABLE_PREFIX . "useractivation
			WHERE activationid = '" . $db->escape_string($vbulletin->GPC['i']) . "'
				AND userid = $userinfo[userid]
				AND type = 0
		");
		if (!$user OR $vbulletin->GPC['i'] != $user['activationid'])
		{
			// send email again
			eval(standard_error(fetch_error('invalidactivateid', $vbulletin->session->vars['sessionurl'], $vbulletin->options['contactuslink'])));
		}

		// delete activationid
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "useractivation WHERE userid=$userinfo[userid] AND type=0");

		/*
		This shouldn't be needed any more since we handle this during registration
		if ($userinfo['coppauser'] OR ($vbulletin->options['moderatenewmembers'] AND !$userinfo['posts']))
		{
			// put user in moderated group
			$user['usergroupid'] = 4;
		}*/

		if (empty($user['usergroupid']))
		{
			$user['usergroupid'] = 2; // sanity check
		}

		// ### DO THE UG/TITLE UPDATE ###

		$getusergroupid = iif($userinfo['displaygroupid'] != $userinfo['usergroupid'], $userinfo['displaygroupid'], $user['usergroupid']);

		$user_usergroup =& $vbulletin->usergroupcache["$user[usergroupid]"];
		$display_usergroup =& $vbulletin->usergroupcache["$getusergroupid"];

		// init user data manager
		$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
		$userdata->set_existing($userinfo);
		$userdata->set('usergroupid', $user['usergroupid']);
		$userdata->set_usertitle(
			$user['customtitle'] ? $user['usertitle'] : '',
			false,
			$display_usergroup,
			($user_usergroup['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusecustomtitle']) ? true : false,
			($user_usergroup['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cancontrolpanel']) ? true : false
		);

		require_once(DIR . '/includes/functions_ranks.php');
		if ($user['userid'] == $vbulletin->userinfo['userid'])
		{
			$vbulletin->userinfo['usergroupid'] = $user['usergroupid'];
			$vbulletin->userinfo['displaygroupid'] = $user['usergroupid'];
		}

		// see 3.6.x bug #176
		//$userinfo['usergroupid'] = $user['usergroupid'];

		($hook = vBulletinHook::fetch_hook('register_activate_process')) ? eval($hook) : false;

		if ($userinfo['coppauser'] OR ($vbulletin->options['moderatenewmembers'] AND !$userinfo['posts']))
		{
			// put user in moderated group
			$userdata->save();
			eval(standard_error(fetch_error('moderateuser', $userinfo['username'], fetch_seo_url('forumhome', array())), '', false));
		}
		else
		{
			// activate account
			$userdata->save();

			// rebuild stats so new user displays on forum home
			require_once(DIR . '/includes/functions_databuild.php');
			build_user_statistics();

			$username = unhtmlspecialchars($userinfo['username']);
			if (!$user['emailchange'])
			{
				if ($vbulletin->options['welcomemail'])
				{
					eval(fetch_email_phrases('welcomemail'));
					vbmail($userinfo['email'], $subject, $message);
				}

				$userdata->send_welcomepm();
			}

			if ($user['emailchange'])
			{
				eval(standard_error(fetch_error('emailchanged', htmlspecialchars_uni($userinfo['email'])), '', false));
			}
			else
			{

				eval(standard_error(fetch_error('registration_complete', $userinfo['username'],
					$vbulletin->session->vars['sessionurl'], fetch_seo_url('forumhome', array())), '', false));
			}
		}
	}
	else
	{
		if ($userinfo['usergroupid'] == 4)
		{
			// In Moderation Queue
			eval(standard_error(fetch_error('activate_moderation'), '', false));
		}
		else
		{
			// Already activated
			eval(standard_error(fetch_error('activate_wrongusergroup')));
		}
	}

}

// ############################### start request activation email ###############################
if ($_REQUEST['do'] == 'requestemail')
{
	$email = $vbulletin->input->clean_gpc('r', 'email', TYPE_NOHTML);

	if ($vbulletin->userinfo['userid'] AND $vbulletin->GPC['email'] === '')
	{
		$email = $vbulletin->userinfo['email'];
	}
	else
	{
		$email = $vbulletin->GPC['email'];
	}

	$navbits = construct_navbits(array(
		'register.php?' . $vbulletin->session->vars['sessionurl'] . 'a=ver' => $vbphrase['activate_your_account'],
		'' => $vbphrase['email_activation_codes']
	));

	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('register_requestemail')) ? eval($hook) : false;

	$url =& $vbulletin->url;
	$templater = vB_Template::create('activate_requestemail');
		$templater->register_page_templates();
		$templater->register('email', $email);
		$templater->register('navbar', $navbar);
		$templater->register('url', $url);
	print_output($templater->render());
}

// ############################### process request activation email #############################
if ($_POST['do'] == 'emailcode')
{
	$vbulletin->input->clean_gpc('r', 'email', TYPE_NOHTML);

	$users = $db->query_read_slave("
		SELECT user.userid, user.usergroupid, username, email, activationid, languageid
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "useractivation AS useractivation ON(user.userid = useractivation.userid AND type = 0)
		WHERE email = '" . $db->escape_string($vbulletin->GPC['email']) . "'"
	);

	if ($db->num_rows($users))
	{
		while ($user = $db->fetch_array($users))
		{
			if ($user['usergroupid'] == 3)
			{ // only do it if the user is in the correct usergroup
				// make random number
				if (empty($user['activationid']))
				{ //none exists so create one
					$user['activationid'] = build_user_activation_id($user['userid'], 2, 0);
				}
				else
				{
					$user['activationid'] = fetch_random_string(40);
					$db->query_write("
						UPDATE " . TABLE_PREFIX . "useractivation SET
							dateline = " . TIMENOW . ",
							activationid = '$user[activationid]'
						WHERE userid = $user[userid]
							AND type = 0
					");
				}

				$userid = $user['userid'];
				$username = $user['username'];
				$activateid = $user['activationid'];

				($hook = vBulletinHook::fetch_hook('register_emailcode_user')) ? eval($hook) : false;

				eval(fetch_email_phrases('activateaccount', $user['languageid']));

				vbmail($user['email'], $subject, $message, true);
			}
		}

		print_standard_redirect('redirect_lostactivatecode', true, true);
	}
	else
	{
		eval(standard_error(fetch_error('invalidemail', $vbulletin->options['contactuslink'])));
	}

}

// ############################### start coppa form ###############################
if ($_REQUEST['do'] == 'coppaform')
{
	if ($vbulletin->userinfo['userid'])
	{
		$vbulletin->userinfo['signature'] = nl2br($vbulletin->userinfo['signature']);

		if ($vbulletin->userinfo['showemail'])
		{
			$vbulletin->userinfo['showemail'] = $vbphrase['no'];
		}
		else
		{
			$vbulletin->userinfo['showemail'] = $vbphrase['yes'];
		}
	}
	else
	{
		$vbulletin->userinfo['username'] = '';
		$vbulletin->userinfo['homepage'] = 'http://';
	}

	($hook = vBulletinHook::fetch_hook('register_coppaform')) ? eval($hook) : false;

	$templater = vB_Template::create('register_coppaform');
	print_output($templater->render());
}

// ############################### start delete activation request ###############################
if ($_REQUEST['do'] == 'deleteactivation')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'u'		=> TYPE_UINT,
		'i'		=> TYPE_STR,
	));

	$userinfo = verify_id('user', $vbulletin->GPC['u'], 1, 1);

	if ($userinfo['usergroupid'] == 3)
	{
		// check valid activation id
		$user = $db->query_first("
			SELECT userid, activationid, usergroupid
			FROM " . TABLE_PREFIX . "useractivation
			WHERE activationid = '" . $db->escape_string($vbulletin->GPC['i']) . "'
				AND userid = $userinfo[userid]
				AND type = 0
		");

		if (!$user OR $vbulletin->GPC['i'] != $user['activationid'])
		{
			eval(standard_error(fetch_error('invalidactivateid', $vbulletin->session->vars['sessionurl'], $vbulletin->options['contactuslink'])));
		}

		eval(standard_error(fetch_error('activate_deleterequest', $user['activationid'], $user['userid'])));
	}
	else
	{
		eval(standard_error(fetch_error('activate_wrongusergroup')));
	}
}

// ############################### start kill activation request ###############################
if ($_REQUEST['do'] == 'killactivation')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'u'		=> TYPE_UINT,
		'i'		=> TYPE_STR,
	));

	$userinfo = verify_id('user', $vbulletin->GPC['u'], 1, 1);

	if ($userinfo['usergroupid'] == 3)
	{
		// check valid activation id
		$user = $db->query_first("
			SELECT activationid, usergroupid
			FROM " . TABLE_PREFIX . "useractivation
			WHERE activationid = '" . $db->escape_string($vbulletin->GPC['i']) . "'
				AND userid = $userinfo[userid]
				AND type = 0
		");

		if (!$user OR $vbulletin->GPC['i'] != $user['activationid'])
		{
			eval(standard_error(fetch_error('invalidactivateid', $vbulletin->session->vars['sessionurl'], $vbulletin->options['contactuslink'])));
		}

		$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
		$userdata->set_existing($userinfo);
		$userdata->set_bitfield('options', 'receiveemail', 0);
		$userdata->set_bitfield('options', 'noactivationmails', 1);
		$userdata->save();

		eval(standard_error(fetch_error('activate_requestdeleted')));
	}
	else
	{
		eval(standard_error(fetch_error('activate_wrongusergroup')));
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 73770 $
|| ####################################################################
\*======================================================================*/
