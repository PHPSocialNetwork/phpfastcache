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
define('GET_EDIT_TEMPLATES', true);
define('THIS_SCRIPT', 'newthread');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'threadmanage',
	'postbit',
	'posting',
	'prefix'
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
	'ranks',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'newpost_attachment',
	'newpost_attachmentbit',
	'newthread',
	'humanverify',
	'optgroup',
	'postbit_attachment',
	'postbit_attachmentimage',
	'postbit_attachmentthumbnail',
	'facebook_publishcheckbox',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_newpost.php');
require_once(DIR . '/includes/functions_editor.php');
require_once(DIR . '/includes/functions_bigthree.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_forum_url();

// ### STANDARD INITIALIZATIONS ###
$checked = array();
$newpost = array();
$postattach = array();
$contenttype = 'vBForum_Post';

// sanity checks...
if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'newthread';
}

($hook = vBulletinHook::fetch_hook('newthread_start')) ? eval($hook) : false;

if (!$foruminfo['forumid'])
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['forum'], $vbulletin->options['contactuslink'])));
}

if (!$foruminfo['allowposting'] OR $foruminfo['link'] OR !$foruminfo['cancontainthreads'])
{
	eval(standard_error(fetch_error('forumclosed')));
}

$forumperms = fetch_permissions($forumid);
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostnew']))
{
	print_no_permission();
}

// check if there is a forum password and if so, ensure the user has it set
verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

$show['tag_option'] = ($vbulletin->options['threadtagging'] AND ($forumperms & $vbulletin->bf_ugp_forumpermissions['cantagown']));

// ############################### start post thread ###############################
if ($_POST['do'] == 'postthread')
{
	// Variables reused in templates
	$posthash = $vbulletin->input->clean_gpc('p', 'posthash', TYPE_NOHTML);
	$poststarttime = $vbulletin->input->clean_gpc('p', 'poststarttime', TYPE_UINT);

	$vbulletin->input->clean_array_gpc('p', array(
		'wysiwyg'         => TYPE_BOOL,
		'preview'         => TYPE_STR,
		'message'         => TYPE_STR,
		'subject'         => TYPE_STR,
		'iconid'          => TYPE_UINT,
		'rating'          => TYPE_UINT,
		'prefixid'        => TYPE_NOHTML,
		'taglist'         => TYPE_NOHTML,

		'postpoll'        => TYPE_BOOL,
		'polloptions'     => TYPE_UINT,

		'signature'       => TYPE_BOOL,
		'disablesmilies'  => TYPE_BOOL,
		'parseurl'        => TYPE_BOOL,
		'folderid'        => TYPE_UINT,
		'subscribe'       => TYPE_BOOL,
		'emailupdate'     => TYPE_UINT,
		'stickunstick'    => TYPE_BOOL,
		'openclose'       => TYPE_BOOL,
		'htmlstate'       => TYPE_STR,

		'username'        => TYPE_STR,
		'loggedinuser'    => TYPE_INT,

		'humanverify'     => TYPE_ARRAY,

		'podcasturl'      => TYPE_STR,
		'podcastsize'     => TYPE_UINT,
		'podcastexplicit' => TYPE_BOOL,
		'podcastkeywords' => TYPE_STR,
		'podcastsubtitle' => TYPE_STR,
		'podcastauthor'   => TYPE_STR,
	));

	if ($vbulletin->GPC['loggedinuser'] != 0 AND $vbulletin->userinfo['userid'] == 0)
	{
		// User was logged in when writing post but isn't now. If we got this
		// far, guest posts are allowed, but they didn't enter a username so
		// they'll get an error. Force them to log back in.
		standard_error(fetch_error('session_timed_out_login'), '', false, 'STANDARD_ERROR_LOGIN');
	}

	($hook = vBulletinHook::fetch_hook('newthread_post_start')) ? eval($hook) : false;

	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$newpost['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $foruminfo['allowhtml']);
	}
	else
	{
		$newpost['message'] =& $vbulletin->GPC['message'];
	}

	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostpoll']))
	{
		$vbulletin->GPC['postpoll'] = false;
	}

	$newpost['title'] =& $vbulletin->GPC['subject'];
	$newpost['iconid'] =& $vbulletin->GPC['iconid'];

	require_once(DIR . '/includes/functions_prefix.php');

	if (can_use_prefix($vbulletin->GPC['prefixid']))
	{
		$newpost['prefixid'] =& $vbulletin->GPC['prefixid'];
	}

	if ($show['tag_option'])
	{
		$newpost['taglist'] =& $vbulletin->GPC['taglist'];
	}
	$newpost['parseurl']        = (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_URL) AND $foruminfo['allowbbcode'] AND $vbulletin->GPC['parseurl']);
	$newpost['signature']       =& $vbulletin->GPC['signature'];
	$newpost['preview']         =& $vbulletin->GPC['preview'];
	$newpost['disablesmilies']  =& $vbulletin->GPC['disablesmilies'];
	$newpost['rating']          =& $vbulletin->GPC['rating'];
	$newpost['username']        =& $vbulletin->GPC['username'];
	$newpost['postpoll']        =& $vbulletin->GPC['postpoll'];
	$newpost['polloptions']     =& $vbulletin->GPC['polloptions'];
	$newpost['folderid']        =& $vbulletin->GPC['folderid'];
	$newpost['humanverify']     =& $vbulletin->GPC['humanverify'];
	$newpost['poststarttime']   = $poststarttime;
	$newpost['posthash']        = $posthash;
	// moderation options
	$newpost['stickunstick']    =& $vbulletin->GPC['stickunstick'];
	$newpost['openclose']       =& $vbulletin->GPC['openclose'];
	$newpost['podcasturl']      =& $vbulletin->GPC['podcasturl'];
	$newpost['podcastsize']     =& $vbulletin->GPC['podcastsize'];
	$newpost['podcastexplicit'] =& $vbulletin->GPC['podcastexplicit'];
	$newpost['podcastkeywords'] =& $vbulletin->GPC['podcastkeywords'];
	$newpost['podcastsubtitle'] =& $vbulletin->GPC['podcastsubtitle'];
	$newpost['podcastauthor']   =& $vbulletin->GPC['podcastauthor'];
	$newpost['subscribe']       =& $vbulletin->GPC['subscribe'];

	if ($foruminfo['allowhtml'])
	{
		$htmlchecked = fetch_htmlchecked($vbulletin->GPC['htmlstate']);
		$newpost['htmlstate'] = array_pop($array = array_keys(fetch_htmlchecked($vbulletin->GPC['htmlstate'])));
	}
	else
	{
		$newpost['htmlstate'] = 'on_nl2br';
	}

	if ($vbulletin->GPC_exists['emailupdate'])
	{
		$newpost['emailupdate'] =& $vbulletin->GPC['emailupdate'];
	}
	else
	{
		$newpost['emailupdate'] = array_pop($array = array_keys(fetch_emailchecked(array(), $vbulletin->userinfo)));
	}

	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
	{
		$newpost['emailupdate'] = 0;
	}

	if (!$vbulletin->GPC['subscribe'])
	{
		$newpost['emailupdate'] = 9999;
	}

	build_new_post('thread', $foruminfo, array(), array(), $newpost, $errors);

	if (sizeof($errors) > 0)
	{
		// ### POST HAS ERRORS ###
		$postpreview = construct_errors($errors); // this will take the preview's place
		construct_checkboxes($newpost);
		$_REQUEST['do'] = 'newthread';
		$newpost['message'] = htmlspecialchars_uni($newpost['message']);
		$podcasturl = htmlspecialchars_uni($newpost['podcasturl']);
		$podcastsize = ($newpost['podcastsize']) ? $newpost['podcastsize'] : '';
		$podcastkeywords = htmlspecialchars_uni($newpost['podcastkeywords']);
		$podcastsubtitle = htmlspecialchars_uni($newpost['podcastsubtitle']);
		$podcastauthor = htmlspecialchars_uni($newpost['podcastauthor']);
		$explicitchecked = $newpost['podcastexplicit'] ? 'checked="checked"' : '';
	}
	else if ($newpost['preview'])
	{
		if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostattachment'] AND $vbulletin->userinfo['userid'] AND !empty($vbulletin->userinfo['attachmentextensions']))
		{
			require_once(DIR . '/packages/vbattach/attach.php');
			$attach = new vB_Attach_Display_Content($vbulletin, 'vBForum_Post');
			$postattach = $attach->fetch_postattach($posthash);
		}
		// ### PREVIEW POST ###
		// yes .. double postattach here
		$postpreview = process_post_preview($newpost, 0, $postattach, $postattach);
		$_REQUEST['do'] = 'newthread';
		$newpost['message'] = htmlspecialchars_uni($newpost['message']);
		$podcasturl = htmlspecialchars_uni($newpost['podcasturl']);
		$podcastsize = ($newpost['podcastsize']) ? $newpost['podcastsize'] : '';
		$podcastkeywords = htmlspecialchars_uni($newpost['podcastkeywords']);
		$podcastsubtitle = htmlspecialchars_uni($newpost['podcastsubtitle']);
		$podcastauthor = htmlspecialchars_uni($newpost['podcastauthor']);
		$explicitchecked = $newpost['podcastexplicit'] ? 'checked="checked"' : '';
	}
	else
	{
		// ### NOT PREVIEW - ACTUAL POST ###
		$threadinfo = fetch_threadinfo($newpost['threadid']); // need the forumread variable from this
		mark_thread_read($threadinfo, $foruminfo, $vbulletin->userinfo['userid'], TIMENOW);

		($hook = vBulletinHook::fetch_hook('newthread_post_complete')) ? eval($hook) : false;
		if ($newpost['postpoll'])
		{
			$vbulletin->url = 'poll.php?' . $vbulletin->session->vars['sessionurl'] . 
				"t=$newpost[threadid]&polloptions=$newpost[polloptions]";
			if ($vbulletin->options['vbforum_url'])
			{
				$vbulletin->url = $vbulletin->options['vbforum_url'] . '/' . $vbulletin->url;
			}
			if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
			{
				print_standard_redirect('redirect_postthanks', true, true);  
			}
			else
			{
				print_standard_redirect('redirect_postthanks_nopermission', true, true);  
			}
		}
		else if ($newpost['visible'])
		{
			if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
			{
				$vbulletin->url = fetch_seo_url('thread', $threadinfo, array('p' => $newpost['postid'] . "#post$newpost[postid]"));

				if (defined('VB_API') AND VB_API === true)
				{
					$show['threadid'] = $threadinfo['threadid'];
					$show['postid'] = $newpost['postid'];
				}

				// attempt to publish this new thread to user's Facebook feed
				if (is_facebookenabled())
				{
					$fblink = str_ireplace('&amp;', '&', $vbulletin->url);
					publishtofacebook_newthread($newpost['title'], $newpost['message'], create_full_url($fblink));
				}

				print_standard_redirect('redirect_postthanks');  
			}
			else
			{
				$vbulletin->url = fetch_seo_url('forum', $foruminfo);
				print_standard_redirect('redirect_postthanks_nopermission', true, true);  
			}
		}
		else
		{
			$vbulletin->url = fetch_seo_url('forum', $foruminfo);
			print_standard_redirect('redirect_postthanks_moderate', true, true);  
		}
	} // end if
}

// ############################### start new thread ###############################
if ($_REQUEST['do'] == 'newthread')
{
	($hook = vBulletinHook::fetch_hook('newthread_form_start')) ? eval($hook) : false;

	$posticons = construct_icons($newpost['iconid'], $foruminfo['allowicons']);

	if (!isset($checked['parseurl']))
	{
		$checked['parseurl'] = 'checked="checked"';
	}

	if (!isset($checked['postpoll']))
	{
		$checked['postpoll'] = '';
	}

	if (!isset($newpost['polloptions']))
	{
		$polloptions = 4;
	}
	else
	{
		$polloptions = $newpost['polloptions'];
	}

	// Get subscribed thread folders
	$newpost['folderid'] = iif($newpost['folderid'], $newpost['folderid'], 0);
	$folders = unserialize($vbulletin->userinfo['subfolders']);
	// Don't show the folderjump if we only have one folder, would be redundant ;)
	if (sizeof($folders) > 1)
	{
		require_once(DIR . '/includes/functions_misc.php');
		$folderbits = construct_folder_jump(1, $newpost['folderid'], false, $folders);
	}
	$show['subscribefolders'] = iif(!empty($folderbits), true, false);

	// get the checked option for auto subscription
	$emailchecked = fetch_emailchecked($threadinfo, $vbulletin->userinfo, $newpost);
	if ($emailchecked['9999'])
	{
		$emailchecked['0'] = $emailchecked['9999'];
		unset($emailchecked['9999']);
		$checked['subscribe'] = '';
	}
	else
	{
		$checked['subscribe'] = 'checked="checked"';
	}

	if ($foruminfo['allowhtml'])
	{
		if (!isset($htmlchecked))
		{
			$htmlchecked = array('on_nl2br' => 'selected="selected"');
		}
		$templater = vB_Template::create('newpost_html');
			$templater->register('htmlchecked', $htmlchecked);
		$htmloption = $templater->render();
	}

	// check to see if signature required
	if ($vbulletin->userinfo['userid'] AND !$postpreview)
	{
		if ($vbulletin->userinfo['signature'] != '')
		{
			$checked['signature'] = 'checked="checked"';
		}
		else
		{
			$checked['signature'] = '';
		}
	}

	if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostpoll'])
	{
		$show['poll'] = true;
	}
	else
	{
		$show['poll'] = false;
	}

	$attachcount = 0;

	require_once(DIR . '/includes/functions_file.php');
	if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostattachment'] AND $vbulletin->userinfo['userid'] AND !empty($vbulletin->userinfo['attachmentextensions']))
	{
		$values = "values[f]=$foruminfo[forumid]";
		require_once(DIR . '/packages/vbattach/attach.php');
		$attach = new vB_Attach_Display_Content($vbulletin, 'vBForum_Post');
		$attachmentoption = $attach->fetch_edit_attachments($posthash, $poststarttime, $postattach, 0, $values, $editorid, $attachcount);
		$contenttypeid = $attach->fetch_contenttypeid();
		$attachinfo = fetch_attachmentinfo($posthash, $poststarttime, $contenttypeid, array('f' => $foruminfo['forumid']));
	}
	else
	{
		$attachmentoption = '';
		$attachinfo = fetch_attachmentinfo($posthash, $poststarttime, 0, array('f' => $foruminfo['forumid']));
	}

	$editorid = construct_edit_toolbar(
		$newpost['message'],
		0,
		$foruminfo['forumid'],
		$foruminfo['allowsmilies'],
		1,
		($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostattachment'] AND $vbulletin->userinfo['userid'] AND !empty($vbulletin->userinfo['attachmentextensions'])),
		'fe',
		'',
		$attachinfo,
		'forum',
		'vBForum_Thread',
		0,
		0,
		$postpreview,
		true,
		'subject'
	);

	$subject = $newpost['title'];

	// display prefixes
	require_once(DIR . '/includes/functions_prefix.php');
	$prefix_options = fetch_prefix_html($foruminfo['forumid'], $newpost['prefixid'], true);

	// get username code
	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	$show['podcasturl'] = ($foruminfo['podcast']);

	// can this user open / close this thread?
	if (($vbulletin->userinfo['userid'] AND $forumperms & $vbulletin->bf_ugp_forumpermissions['canopenclose']) OR can_moderate($foruminfo['forumid'], 'canopenclose'))
	{
		$threadinfo['open'] = 1;
		$show['openclose'] = true;
		$show['closethread'] = true;
	}
	else
	{
		$show['openclose'] = false;
	}
	// can this user stick this thread?
	if (can_moderate($foruminfo['forumid'], 'canmanagethreads'))
	{
		$threadinfo['sticky'] = 0;
		$show['stickunstick'] = true;
		$show['unstickthread'] = false;
	}
	else
	{
		$show['stickunstick'] = false;
	}
	if ($show['openclose'] OR $show['stickunstick'])
	{
		($hook = vBulletinHook::fetch_hook('newthread_form_threadmanage')) ? eval($hook) : false;
		$templater = vB_Template::create('newpost_threadmanage');
			$templater->register('checked', $checked);
		$threadmanagement = $templater->render();
	}
	else
	{
		$threadmanagement = '';
	}

	if (fetch_require_hvcheck('post'))
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verification =& vB_HumanVerify::fetch_library($vbulletin);
		$human_verify = $verification->output_token();
	}
	else
	{
		$human_verify = '';
	}

	if ($show['tag_option'])
	{
		$tags_remain = null;
		if ($vbulletin->options['tagmaxthread'])
		{
			$tags_remain = $vbulletin->options['tagmaxthread'];
		}
		if ($vbulletin->options['tagmaxstarter'] AND !can_moderate($threadinfo['forumid'], 'caneditthreads'))
		{
			$tags_remain = ($tags_remain === null ? $vbulletin->options['tagmaxstarter'] : min($tags_remain, $vbulletin->options['tagmaxstarter']));
		}

		$show['tags_remain'] = ($tags_remain !== null);
		$tags_remain = vb_number_format($tags_remain);
		$tag_delimiters = addslashes_js($vbulletin->options['tagdelimiter']);
	}

	// draw nav bar
	$navbits = array();
	$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];
	$parentlist = array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3)));
	foreach ($parentlist AS $forumID)
	{
		$forumTitle = $vbulletin->forumcache["$forumID"]['title'];
		$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
	}
	$navbits[''] = $vbphrase['post_new_thread'];
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	construct_forum_rules($foruminfo, $forumperms);

	$show['signaturecheckbox'] = ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature'] AND $vbulletin->userinfo['signature']);
	$show['parseurl'] = (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_URL) AND $foruminfo['allowbbcode']);
	$show['misc_options'] = ($show['signaturecheckbox'] OR $show['parseurl'] OR !empty($disablesmiliesoption));
	$show['additional_options'] = ($show['misc_options'] OR !empty($attachmentoption) OR $show['member'] OR $show['poll'] OR !empty($threadmanagement));
	$show['lightbox'] = ($vbulletin->options['lightboxenabled'] AND $vbulletin->options['usepopups']);

	$guestuser = array(
		'userid'      => 0,
		'usergroupid' => 0,
	);
	cache_permissions($guestuser);

	if (
		$guestuser['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']
			AND
		$guestuser['forumpermissions']["$foruminfo[forumid]"] & $vbulletin->bf_ugp_forumpermissions['canview']
			AND
		$guestuser['forumpermissions']["$foruminfo[forumid]"] & $vbulletin->bf_ugp_forumpermissions['canviewthreads']
			AND
		($guestuser['forumpermissions']["$foruminfo[forumid]"] & $vbulletin->bf_ugp_forumpermissions['canviewothers'])
			AND
		is_facebookenabled()
	)
	{
		$fbpublishcheckbox = construct_fbpublishcheckbox();
	}

	($hook = vBulletinHook::fetch_hook('newthread_form_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('newthread');
		$templater->register_page_templates();
		$templater->register('attachmentoption', $attachmentoption);
		$templater->register('checked', $checked);
		$templater->register('disablesmiliesoption', $disablesmiliesoption);
		$templater->register('editorid', $editorid);
		$templater->register('emailchecked', $emailchecked);
		$templater->register('explicitchecked', $explicitchecked);
		$templater->register('folderbits', $folderbits);
		$templater->register('forumid', $forumid);
		$templater->register('foruminfo', $foruminfo);
		$templater->register('forumrules', $forumrules);
		$templater->register('human_verify', $human_verify);
		$templater->register('messagearea', $messagearea);
		$templater->register('navbar', $navbar);
		$templater->register('newpost', $newpost);
		$templater->register('onload', $onload);
		$templater->register('podcastauthor', $podcastauthor);
		$templater->register('podcastkeywords', $podcastkeywords);
		$templater->register('podcastsize', $podcastsize);
		$templater->register('podcastsubtitle', $podcastsubtitle);
		$templater->register('podcasturl', $podcasturl);
		$templater->register('polloptions', $polloptions);
		$templater->register('posthash', $posthash);
		$templater->register('posticons', $posticons);
		$templater->register('postpreview', $postpreview);
		$templater->register('poststarttime', $poststarttime);
		$templater->register('prefix_options', $prefix_options);
		$templater->register('selectedicon', $selectedicon);
		$templater->register('subject', $subject);
		$templater->register('tags_remain', $tags_remain);
		$templater->register('tag_delimiters', $tag_delimiters);
		$templater->register('threadmanagement', $threadmanagement);
		$templater->register('usernamecode', $usernamecode);
		$templater->register('htmloption', $htmloption);
		$templater->register('fbpublishcheckbox', $fbpublishcheckbox);
	print_output($templater->render());

}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 57655 $
|| ####################################################################
\*======================================================================*/
