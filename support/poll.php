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
define('THIS_SCRIPT', 'poll');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('poll', 'posting');

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'editpoll',
	'forumrules',
	'newpoll',
	'newpost_usernamecode',
	'polleditbit',
	'pollnewbit',
	'pollpreview',
	'pollpreviewbit',
	'pollresult',
	'pollresults',
	'pollresults_table',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_bbcode_alt.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_forum_url();

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'newpoll';
}

// shortcut function to make the $navbits for the navbar...
function construct_poll_nav($foruminfo, $threadinfo)
{
	global $vbulletin, $vbphrase;

	$navbits = array();
	$navbits[fetch_seo_url('forumhome', array())] = $vbphrase['forum'];
	$parentlist = array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3)));

	foreach ($parentlist AS $forumID)
	{
		$forumTitle = $vbulletin->forumcache["$forumID"]['title'];
		$navbits[fetch_seo_url('forum', array('forumid' => $forumID, 'title' => $forumTitle))] = $forumTitle;
	}
	$navbits[fetch_seo_url('thread', $threadinfo)] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];

	switch ($_REQUEST['do'])
	{
		case 'newpoll':  $navbits[''] = $vbphrase['post_a_poll']; break;
		case 'polledit': $navbits[''] = $vbphrase['edit_poll']; break;
		case 'showresults': $navbits[''] = $vbphrase['view_poll_results']; break;
		// are there more?
	}

	return construct_navbits($navbits);
}

if ($threadinfo['isdeleted'] OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts') AND $vbulletin->userinfo['userid'] != $threadinfo['postuserid']))
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
}

if (!$foruminfo['forumid'])
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['forum'], $vbulletin->options['contactuslink'])));
}

// check permissions
$forumperms = fetch_permissions($foruminfo['forumid']);
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']))
{
	print_no_permission();
}

if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
{
	if (($_POST['do'] != 'postpoll' AND $_REQUEST['do'] != 'newpoll') OR $threadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR !$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}
}

// check if there is a forum password and if so, ensure the user has it set
verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

($hook = vBulletinHook::fetch_hook('poll_start')) ? eval($hook) : false;

// ############################### start post poll ###############################
if ($_POST['do'] == 'postpoll')
{
	// Reused in template
	$polloptions = $vbulletin->input->clean_gpc('p', 'polloptions', TYPE_UINT);
	$question = $vbulletin->input->clean_gpc('p', 'question', TYPE_NOHTML);
	$timeout = $vbulletin->input->clean_gpc('p', 'timeout', TYPE_UINT);

	$vbulletin->input->clean_array_gpc('p', array(
		'preview'        => TYPE_STR,
		'updatenumber'   => TYPE_STR,
		'public'         => TYPE_BOOL,
		'parseurl'       => TYPE_BOOL,
		'multiple'       => TYPE_BOOL,
		'options'        => TYPE_ARRAY_STR
	));

	($hook = vBulletinHook::fetch_hook('poll_post_start')) ? eval($hook) : false;

	if ($threadinfo['pollid'])
	{
		eval(standard_error(fetch_error('pollalready')));
	}

	if ($vbulletin->userinfo['userid'] != $threadinfo['postuserid'] AND !can_moderate($foruminfo['forumid'], 'caneditpoll'))
	{
		print_no_permission();
	}

	// check permissions
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostnew']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostpoll']))
	{
		print_no_permission();
	}

	if (!can_moderate($threadinfo['forumid'], 'caneditpoll') AND $vbulletin->options['addpolltimeout'] AND TIMENOW - ($vbulletin->options['addpolltimeout'] * 60) > $threadinfo['dateline'])
	{
		eval(standard_error(fetch_error('polltimeout', $vbulletin->options['addpolltimeout'])));
	}

	if (!$threadinfo['open'])
	{
		eval(standard_error(fetch_error('threadclosed')));
	}

	if ($vbulletin->options['maxpolloptions'] > 0 AND $polloptions > $vbulletin->options['maxpolloptions'])
	{
		$polloptions = $vbulletin->options['maxpolloptions'];
	}

	if ($vbulletin->GPC['parseurl'] AND $foruminfo['allowbbcode'])
	{
		require_once(DIR . '/includes/functions_newpost.php');

		$counter = 0;
		while ($counter++ < $polloptions)
		{ // 0..Pollnum-1 we want, as arrays start with 0
			$vbulletin->GPC['options']["$counter"] = convert_url_to_bbcode($vbulletin->GPC['options']["$counter"]);
		}
	}

	// check question and if 2 options or more were given
	$counter = 0;
	$optioncount = 0;
	$badoption = '';
	while ($counter++ < $polloptions)
	{ // 0..Pollnum-1 we want, as arrays start with 0
		if ($vbulletin->options['maxpolllength'] AND vbstrlen($vbulletin->GPC['options']["$counter"]) > $vbulletin->options['maxpolllength'])
		{
			$badoption .= ($badoption) ? $vbphrase['comma_space'] . $counter : $counter;
		}
		if (!empty($vbulletin->GPC['options']["$counter"]))
		{
			$optioncount++;
		}
	}

	if ($badoption)
	{
		eval(standard_error(fetch_error('polloptionlength', $vbulletin->options['maxpolllength'], $badoption)));
	}

	$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	if ($vbulletin->GPC['preview'] != '' OR $vbulletin->GPC['updatenumber'] != '')
	{
		if ($vbulletin->GPC['preview'] != '')
		{
			$previewpost = 1;

			$counter = 0;
			$pollpreview = '';
			$previewquestion = $bbcode_parser->parse(unhtmlspecialchars($question), $foruminfo['forumid'], $foruminfo['allowsmilies']);
			$pollpreviewbits = '';
			while ($counter++ < $polloptions)
			{
				$option = $bbcode_parser->parse($vbulletin->GPC['options']["$counter"], $foruminfo['forumid'], $foruminfo['allowsmilies']);
				$templater = vB_Template::create('pollpreviewbit');
					$templater->register('option', $option);
				$pollpreviewbits .= $templater->render();
			}

			$templater = vB_Template::create('pollpreview');
				$templater->register('pollpreviewbits', $pollpreviewbits);
				$templater->register('previewquestion', $previewquestion);
			$pollpreview = $templater->render();
		}

		$checked = array(
			'multiple'       => ($vbulletin->GPC['multiple'] ? 'checked="checked"' : ''),
			'public'         => ($vbulletin->GPC['public'] ? 'checked="checked"' : ''),
			'parseurl'       => ($vbulletin->GPC['parseurl'] ? 'checked="checked"' : ''),
		);

		$_REQUEST['do'] = 'newpoll';
	}
	else
	{
		if ($question == '' OR $optioncount < 2)
		{
			eval(standard_error(fetch_error('noquestionoption')));
		}

		if (TIMENOW + ($vbulletin->GPC['timeout'] * 86400) >= 2147483647)
		{ // maximuim size of a 32 bit integer
			eval(standard_error(fetch_error('maxpolltimeout')));
		}

		// check max images
		if ($vbulletin->options['maximages'] OR $vbulletin->options['maxvideos'])
		{
			$counter = 0;
			while ($counter++ < $polloptions)
			{ // 0..Pollnum-1 we want, as arrays start with 0
				$maximgtest .= $vbulletin->GPC['options']["$counter"];
			}

			$img_parser = new vB_BbCodeParser_ImgCheck($vbulletin, fetch_tag_list());
			$parsedmessage = $img_parser->parse($maximgtest . $question, $foruminfo['forumid'], $foruminfo['allowsmilies'], true);

			require_once(DIR . '/includes/functions_misc.php');

			if ($vbulletin->options['maximages'])
			{
				$imagecount = fetch_character_count($parsedmessage, '<img');
				if ($imagecount > $vbulletin->options['maximages'])
				{
					eval(standard_error(fetch_error('toomanyimages', $imagecount, $vbulletin->options['maximages'])));
				}
			}
			if ($vbulletin->options['maxvideos'])
			{
				$videocount = fetch_character_count($parsedmessage, '<video />');
				if ($videocount > $vbulletin->options['maxvideos'])
				{
					eval(standard_error(fetch_error('toomanyvideos', $videocount, $vbulletin->options['maxvideos'])));
				}
			}
		}

		$question = fetch_censored_text($question);
		$counter = 0;
		while ($counter++ < $polloptions)
		{ // 0..Pollnum-1 we want, as arrays start with 0
			$vbulletin->GPC['options']["$counter"] = fetch_censored_text($vbulletin->GPC['options']["$counter"]);
		}

		// Add the poll
		$poll =& datamanager_init('Poll', $vbulletin, ERRTYPE_STANDARD);

		$counter = 0;
		while ($counter++ < $polloptions)
		{
			if ($vbulletin->GPC['options']["$counter"] != '')
			{
				$poll->set_option($vbulletin->GPC['options']["$counter"]);
			}
		}

		$poll->set('question',	$question);
		$poll->set('dateline',	TIMENOW);
		$poll->set('active',	'1');
		$poll->set('timeout',	$vbulletin->GPC['timeout']);
		$poll->set('multiple',	$vbulletin->GPC['multiple']);
		$poll->set('public',	$vbulletin->GPC['public']);

		($hook = vBulletinHook::fetch_hook('poll_post_process')) ? eval($hook) : false;

		$pollid = $poll->save();
		//end create new poll

		// update thread
		$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_STANDARD, 'threadpost');
		$threadman->set_existing($threadinfo);
		$threadman->set('pollid', $pollid);
		$threadman->save();

		// update last post icon (if necessary)
		cache_ordered_forums(1);

		if ($vbulletin->forumcache["$threadinfo[forumid]"]['lastthreadid'] == $threadinfo['threadid'])
		{
			$forumdm =& datamanager_init('Forum', $vbulletin, ERRTYPE_SILENT);
			$forumdm->set_existing($vbulletin->forumcache["$threadinfo[forumid]"]);
			$forumdm->set('lasticonid', '-1');
			$forumdm->save();
			unset($forumdm);
		}

		// redirect
		if ($threadinfo['visible'] AND $forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
		{
			$vbulletin->url = fetch_seo_url('thread', $threadinfo);
		}
		else
		{
			$vbulletin->url = fetch_seo_url('forum', $foruminfo);
		}

		($hook = vBulletinHook::fetch_hook('poll_post_complete')) ? eval($hook) : false;

		if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
		{
			print_standard_redirect('redirect_postthanks_nopermission');  
		}
		else
		{
			print_standard_redirect('redirect_postthanks');  
		}
	}
}

// ############################### start new poll ###############################
if ($_REQUEST['do'] == 'newpoll')
{
	// Reused in template.
	$polloptions = $vbulletin->input->clean_gpc('r', 'polloptions', TYPE_UINT);

	($hook = vBulletinHook::fetch_hook('poll_newform_start')) ? eval($hook) : false;

	if ($threadinfo['pollid'])
	{
		eval(standard_error(fetch_error('pollalready')));
	}

	if ($vbulletin->userinfo['userid'] != $threadinfo['postuserid'] AND !can_moderate($foruminfo['forumid'], 'caneditpoll'))
	{
		print_no_permission();
	}

	// check permissions
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostnew']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canpostpoll']))
	{
		print_no_permission();
	}

	if (!can_moderate($threadinfo['forumid'], 'caneditpoll') AND $vbulletin->options['addpolltimeout'] AND TIMENOW - ($vbulletin->options['addpolltimeout'] * 60) > $threadinfo['dateline'])
	{
		eval(standard_error(fetch_error('polltimeout', $vbulletin->options['addpolltimeout'])));
	}

	if (!$threadinfo['open'])
	{
		eval(standard_error(fetch_error('threadclosed')));
	}

	// stop there being too many
	if ($vbulletin->options['maxpolloptions'] > 0 AND $polloptions > $vbulletin->options['maxpolloptions'])
	{
		$polloptions = $vbulletin->options['maxpolloptions'];
	}
	// stop there being too few
	if ($polloptions <= 1)
	{
		$polloptions = 2;
	}

	$polldate = vbdate($vbulletin->options['dateformat'], TIMENOW);
	$polltime = vbdate($vbulletin->options['timeformat'], TIMENOW);

	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	// draw nav bar
	$navbits = construct_poll_nav($foruminfo, $threadinfo);
	$navbar = render_navbar_template($navbits);

	require_once(DIR . '/includes/functions_bigthree.php');
	construct_forum_rules($foruminfo, $forumperms);

	$counter = 0;
	$option = array();
	while ($counter++ < $polloptions)
	{
		$option['number'] = $counter;
		if (is_array($vbulletin->GPC['options']))
		{
			$option['question'] = htmlspecialchars_uni($vbulletin->GPC['options']["$counter"]);
		}
		$templater = vB_Template::create('pollnewbit');
			$templater->register('option', $option);
		$pollnewbits .= $templater->render();
	}

	if (!isset($checked['parseurl']))
	{
		$checked['parseurl'] = 'checked="checked"';
	}

	$show['parseurl'] = $foruminfo['allowbbcode'];

	($hook = vBulletinHook::fetch_hook('poll_newform_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('newpoll');
		$templater->register_page_templates();
		$templater->register('checked', $checked);
		$templater->register('forumrules', $forumrules);
		$templater->register('navbar', $navbar);
		$templater->register('polldate', $polldate);
		$templater->register('pollnewbits', $pollnewbits);
		$templater->register('polloptions', $polloptions);
		$templater->register('pollpreview', $pollpreview);
		$templater->register('question', $question);
		$templater->register('threadid', $threadid);
		$templater->register('threadinfo', $threadinfo);
		$templater->register('timeout', $timeout);
		$templater->register('usernamecode', $usernamecode);
	print_output($templater->render());

}

// ############################### start poll edit ###############################
if ($_REQUEST['do'] == 'polledit')
{
	if (!$pollinfo['pollid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['poll'], $vbulletin->options['contactuslink'])));
	}

	($hook = vBulletinHook::fetch_hook('poll_editform_start')) ? eval($hook) : false;

	// check if user is allowed to do edit
	if (!can_moderate($threadinfo['forumid'], 'caneditpoll'))
	{
		print_no_permission();
	}

	if ($vbulletin->options['maxpolloptions'] > 0 AND $pollinfo['numberoptions'] > $vbulletin->options['maxpolloptions'])
	{
		$pollinfo['numberoptions'] = $vbulletin->options['maxpolloptions'];
	}

	if (!$pollinfo['active'])
	{
		 $pollinfo['closed'] = 'checked="checked"';
	}

	if($pollinfo['public'])
	{
		$show['makeprivate'] = true;
		$pollinfo['public'] = 'checked="checked"';
	}

	$pollinfo['postdate'] = vbdate($vbulletin->options['dateformat'], $pollinfo['dateline']);
	$pollinfo['posttime'] = vbdate($vbulletin->options['timeformat'], $pollinfo['dateline']);

	// draw nav bar
	$navbits = construct_poll_nav($foruminfo, $threadinfo);
	$navbar = render_navbar_template($navbits);

	require_once(DIR . '/includes/functions_bigthree.php');
	construct_forum_rules($foruminfo, $forumperms);

	//get options
	$splitoptions = explode('|||', $pollinfo['options']);
	$splitoptions = array_map('rtrim', $splitoptions);

	$splitvotes = explode('|||', $pollinfo['votes']);

	$counter = 0;
	while ($counter++ < $pollinfo['numberoptions'])
	{
		$pollinfo['numbervotes'] += $splitvotes[$counter - 1];
	}

	$counter = 0;
	$pollbits = '';

	$pollinfo['question'] = $pollinfo['question'];

	while ($counter++ < $pollinfo['numberoptions'])
	{
		$option['question'] = htmlspecialchars_uni($splitoptions[$counter - 1]);
		$option['votes'] = $splitvotes[$counter - 1];  //get the vote count for the option
		$option['number'] = $counter;  //number of the option

		$templater = vB_Template::create('polleditbit');
			$templater->register('option', $option);
		$pollbits .= $templater->render();
	}

	if ($vbulletin->options['maxpolloptions'] > 0)
	{
		$show['additional_option1'] = ($pollinfo['numberoptions'] < $vbulletin->options['maxpolloptions']);
		$show['additional_option2'] = ($pollinfo['numberoptions'] < ($vbulletin->options['maxpolloptions'] - 1));
	}
	else
	{
		$show['additional_option1'] = true;
		$show['additional_option2'] = true;
	}

	if (!isset($checked['parseurl']))
	{
		$checked['parseurl'] = 'checked="checked"';
	}

	$show['parseurl'] = $foruminfo['allowbbcode'];
	$usernamecode = vB_Template::create('newpost_usernamecode')->render();

	($hook = vBulletinHook::fetch_hook('poll_editform_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('editpoll');
		$templater->register_page_templates();
		$templater->register('checked', $checked);
		$templater->register('forumrules', $forumrules);
		$templater->register('navbar', $navbar);
		$templater->register('pollbits', $pollbits);
		$templater->register('pollid', $pollid);
		$templater->register('pollinfo', $pollinfo);
		$templater->register('threadinfo', $threadinfo);
		$templater->register('usernamecode', $usernamecode);
	print_output($templater->render());
}

// ############################### start adding the edit to the db ###############################
if ($_POST['do'] == 'updatepoll')
{
	if (!$pollinfo['pollid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['poll'], $vbulletin->options['contactuslink'])));
	}

	($hook = vBulletinHook::fetch_hook('poll_update_start')) ? eval($hook) : false;

	// check if user is allowed to do edit
	if (!can_moderate($threadinfo['forumid'], 'caneditpoll'))
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'closepoll'    => TYPE_BOOL,
		'pollquestion' => TYPE_NOHTML,
		'options'      => TYPE_ARRAY_STR,
		'pollvotes'    => TYPE_ARRAY_UINT,
		'timeout'      => TYPE_UINT,
		'public'       => TYPE_BOOL,
		'parseurl'       => TYPE_BOOL,
	));

	$poll =& datamanager_init('Poll', $vbulletin, ERRTYPE_STANDARD);
	$poll->set_existing($pollinfo);
	$maximgtest = '';

	// check max chars in option | prepare if needed $maximgtest for max img|video check
	$badoption = '';
	foreach ($vbulletin->GPC['options'] AS $counter => $optionvalue)
	{
		if ($vbulletin->options['maxpolllength'] AND vbstrlen($vbulletin->GPC['options']["$counter"]) > $vbulletin->options['maxpolllength'])
		{
			$badoption .= ($badoption) ? $vbphrase['comma_space'] . $counter : $counter;
		}
		if ($vbulletin->options['maximages'] OR $vbulletin->options['maxvideos']) 
		{
			$maximgtest .= $vbulletin->GPC['options']["$counter"];
		}
	}

	if ($badoption)
	{
		eval(standard_error(fetch_error('polloptionlength', $vbulletin->options['maxpolllength'], $badoption)));
	}

	$optioncount = 0;
	require_once(DIR . '/includes/functions_newpost.php');
	foreach ($vbulletin->GPC['options'] AS $counter => $optionvalue)
	{
		if ($optionvalue != '')
		{
			if ($vbulletin->GPC['parseurl'] AND $foruminfo['allowbbcode'])
			{
				$optionvalue = convert_url_to_bbcode($optionvalue);
			}
			$poll->set_option($optionvalue, $counter - 1, intval($vbulletin->GPC['pollvotes']["$counter"]));
			$optioncount++;
		}
		else
		{
			$poll->set_option('', $counter - 1);
		}
	}

	if ($vbulletin->GPC['pollquestion'] == '' OR $optioncount < 2)
	{
		eval(standard_error(fetch_error('noquestionoption')));
	}

	if (TIMENOW + ($vbulletin->GPC['timeout'] * 86400) >= 2147483647)
	{ // maximuim size of a 32 bit integer
		eval(standard_error(fetch_error('maxpolltimeout')));
	}

	// check max images|videos
	if ($vbulletin->options['maximages'] OR $vbulletin->options['maxvideos'])
	{
		$img_parser = new vB_BbCodeParser_ImgCheck($vbulletin, fetch_tag_list());
		$parsedmessage = $img_parser->parse($maximgtest . $vbulletin->GPC['pollquestion'], $foruminfo['forumid'], $foruminfo['allowsmilies'], true);

		require_once(DIR . '/includes/functions_misc.php');

		if ($vbulletin->options['maximages'])
		{
			$imagecount = fetch_character_count($parsedmessage, '<img');
			if ($imagecount > $vbulletin->options['maximages'])
			{
				eval(standard_error(fetch_error('toomanyimages', $imagecount, $vbulletin->options['maximages'])));
			}
		}
		if ($vbulletin->options['maxvideos'])
		{
			$videocount = fetch_character_count($parsedmessage, '<video />');
			if ($videocount > $vbulletin->options['maxvideos'])
			{
				eval(standard_error(fetch_error('toomanyvideos', $videocount, $vbulletin->options['maxvideos'])));
			}
		}
	}

	$poll->set('question', $vbulletin->GPC['pollquestion']);
	$poll->set('active', $vbulletin->GPC['closepoll'] ? 0 : 1);
	$poll->set('timeout', $vbulletin->GPC['timeout']);

	// only let a poll go from public to private, not the other way about
	if ($pollinfo['public'])
	{
		$poll->set('public', $vbulletin->GPC['public']);
	}

	($hook = vBulletinHook::fetch_hook('poll_update_process')) ? eval($hook) : false;

	$poll->save();

	$pollinfo['threadid'] = $threadinfo['threadid'];
	require_once(DIR . '/includes/functions_log_error.php');
	log_moderator_action($pollinfo, 'poll_edited');

	($hook = vBulletinHook::fetch_hook('poll_update_complete')) ? eval($hook) : false;

	$vbulletin->url = fetch_seo_url('thread', $threadinfo);
	print_standard_redirect('redirect_editthanks');  
}

// ############################### start show results without vote ###############################
if ($_REQUEST['do'] == 'showresults')
{
	if (!$pollinfo['pollid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['poll'], $vbulletin->options['contactuslink'])));
	}

	($hook = vBulletinHook::fetch_hook('poll_results_start')) ? eval($hook) : false;

	$counter = 1;
	$pollbits = '';

	$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	$pollinfo['question'] = $bbcode_parser->parse(unhtmlspecialchars($pollinfo['question']), $foruminfo['forumid'], 1);

	$splitoptions = explode('|||', $pollinfo['options']);
	$splitoptions = array_map('rtrim', $splitoptions);

	$splitvotes = explode('|||', $pollinfo['votes']);

	$pollinfo['numbervotes'] = array_sum($splitvotes);

	if ($vbulletin->userinfo['userid'] > 0)
	{
		$pollvotes = $db->query_read_slave("
			SELECT voteoption
			FROM " . TABLE_PREFIX . "pollvote
			WHERE userid = " . $vbulletin->userinfo['userid'] . " AND
				pollid = $pollid
		");
		$uservote = array();
		while ($pollvote = $db->fetch_array($pollvotes))
		{
			$uservote["$pollvote[voteoption]"] = 1;
		}
	}

	if ($pollinfo['public'])
	{
		$public = $db->query_read_slave("
			SELECT user.userid, user.usergroupid, user.displaygroupid, user.username, voteoption, user.infractiongroupid
			FROM " . TABLE_PREFIX . "pollvote AS pollvote
			INNER JOIN " . TABLE_PREFIX . "user AS user ON (pollvote.userid = user.userid)
			WHERE pollid = $pollinfo[pollid]
			ORDER BY username ASC
		");

		$clc = 0;
		$last = array();
		$allnames = array();
		while ($name = $db->fetch_array($public))
		{
			$clc++;
			fetch_musername($name);
			$last[$name['voteoption']] = $clc;
			$name['comma'] = $vbphrase['comma_space'];
			$allnames[$name['voteoption']][$clc] = $name;
		}
		
		// Last elements
		foreach ($last AS $voteoption => $value)
		{
			$allnames[$voteoption][$value]['comma'] = '';
		}		
	}

	foreach ($splitvotes AS $index => $value)
	{
		$option['uservote'] = ($uservote[$index + 1]) ? '*' : '';
		$option['question'] = $bbcode_parser->parse($splitoptions["$index"], $foruminfo['forumid'], true);
		$option['votes'] = $value;  //get the vote count for the option

		if ($option['votes'] <= 0)
		{
			$option['percentraw'] = 0;
		}
		else if ($pollinfo['multiple'])
		{
			$option['percentraw'] = ($option['votes'] < $pollinfo['voters']) ? $option['votes'] / $pollinfo['voters'] * 100 : 100;
		}
		else
		{
			$option['percentraw'] = ($options['votes'] < $pollinfo['numbervotes']) ? $option['votes'] / $pollinfo['numbervotes'] * 100 : 100;
		}
		$option['percent'] = vb_number_format($option['percentraw'], 2);

		$option['graphicnumber'] = $counter % 6 + 1;
		$option['barnumber'] = round($option['percent']) * 2;
		$option['remainder'] = 201 - $option['barnumber'];
		$option['votes'] = vb_number_format($option['votes']);

		$left = vB_Template_Runtime::fetchStyleVar('left');
		$right = vB_Template_Runtime::fetchStyleVar('right');
		$option['open'] = $left[0];
		$option['close'] = $right[0];

		$show['pollvoters'] = false;
		if ($pollinfo['public'] AND $value)
		{
			$names = $allnames[($index+1)];
			unset($allnames[($index+1)]);
			if (!empty($names))
			{
				$show['pollvoters'] = true;
			}
		}

		($hook = vBulletinHook::fetch_hook('poll_results_bit')) ? eval($hook) : false;

		$templater = vB_Template::create('pollresult');
			$templater->register('names', $names);
			$templater->register('option', $option);
		$pollbits .= $templater->render();
		$counter++;
	}

	if ($pollinfo['multiple'])
	{
		$pollinfo['numbervotes'] = $pollinfo['voters'];
		$show['multiple'] = true;
	}

	if (can_moderate($threadinfo['forumid'], 'caneditpoll'))
	{
		$show['editpoll'] = true;
	}
	else
	{
		$show['editpoll'] = false;
	}

	if ($pollinfo['timeout'])
	{
		$pollendtime = vbdate($vbulletin->options['timeformat'], $pollinfo['dateline'] + ($pollinfo['timeout'] * 86400));
		$pollenddate = vbdate($vbulletin->options['dateformat'], $pollinfo['dateline'] + ($pollinfo['timeout'] * 86400));
		$show['pollenddate'] = true;
	}
	else
	{
		$show['pollenddate'] = false;
	}

	// Phrase parts below
	if ($nopermission)
	{
		$pollstatus = $vbphrase['you_may_not_vote_on_this_poll'];
	}
	else if ($showresults)
	{
		$pollstatus = $vbphrase['this_poll_is_closed'];
	}
	else if ($uservoted)
	{
		$pollstatus = $vbphrase['you_have_already_voted_on_this_poll'];
	}

	// draw nav bar
	$navbits = construct_poll_nav($foruminfo, $threadinfo);
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('poll_results_complete')) ? eval($hook) : false;

	$templater = vB_Template::create('pollresults_table');
		$templater->register('pollbits', $pollbits);
		$templater->register('pollenddate', $pollenddate);
		$templater->register('pollendtime', $pollendtime);
		$templater->register('pollinfo', $pollinfo);
		$templater->register('pollstatus', $pollstatus);
	$pollresults = $templater->render();
	$templater = vB_Template::create('pollresults');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('pollresults', $pollresults);
		$templater->register('threadinfo', $threadinfo);
	print_output($templater->render());
}


// ############################### start vote on poll ###############################
if ($_POST['do'] == 'pollvote')
{
	if (!$pollinfo['pollid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['poll'], $vbulletin->options['contactuslink'])));
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'hkey' => TYPE_STR,
	));

	if ($pollinfo['multiple'])
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'optionnumber' => TYPE_ARRAY_BOOL,
		));
	}
	else
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'optionnumber' => TYPE_UINT
		));
	}

	($hook = vBulletinHook::fetch_hook('poll_vote_start')) ? eval($hook) : false;

	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canvote']))
	{
		print_no_permission();
	}

	//check if poll is closed
	if (!$pollinfo['active'] OR !$threadinfo['open'] OR ($pollinfo['dateline'] + ($pollinfo['timeout'] * 86400) < TIMENOW AND $pollinfo['timeout'] != 0))
	{ //poll closed
		 eval(standard_error(fetch_error('pollclosed')));
	}

	//check if an option was selected
	if (!empty($vbulletin->GPC['optionnumber']))
	{
		if (!$vbulletin->userinfo['userid'])
		{
			$voted = intval(fetch_bbarray_cookie('poll_voted', $pollid));
			if ($voted)
			{
				//the user has voted before
				eval(standard_error(fetch_error('useralreadyvote')));
			}
			else
			{
				set_bbarray_cookie('poll_voted', $pollid, 1, 1);
			}
		}
		// Query master to reduce the chance of multiple poll votes
		else if ($uservoteinfo = $db->query_first("
			SELECT userid
			FROM " . TABLE_PREFIX . "pollvote
			WHERE userid = " . $vbulletin->userinfo['userid'] . "
				AND pollid = $pollid
		"))
		{
			//the user has voted before
			eval(standard_error(fetch_error('useralreadyvote')));
		}

		$totaloptions = substr_count($pollinfo['options'], '|||') + 1;

		//Error checking complete, lets get the options
		if ($pollinfo['multiple'])
		{
			$insertsql = '';
			$skip_voters = false;
			foreach ($vbulletin->GPC['optionnumber'] AS $val => $vote)
			{
				$val = intval($val);
				if ($vote AND $val > 0 AND $val <= $totaloptions)
				{
					$pollvote =& datamanager_init('PollVote', $vbulletin, ERRTYPE_STANDARD);
					$pollvote->set_info('skip_voters', $skip_voters);
					$pollvote->set('pollid',     $pollid);
					$pollvote->set('votedate',   TIMENOW);
					$pollvote->set('voteoption', $val);
					if (!$vbulletin->userinfo['userid'])
					{
						$pollvote->set('userid', NULL, false);
					}
					else
					{
						$pollvote->set('userid', $vbulletin->userinfo['userid']);
					}
					$pollvote->set('votetype', $val);
					if (!$pollvote->save(true, false, false, false, true))
					{
						$vbulletin->url = fetch_seo_url('thread', $threadinfo);
						print_standard_redirect('redirect_pollvoteduplicate');  
					}

					$skip_voters = true;
				}
			}
		}
		else if ($vbulletin->GPC['optionnumber'] > 0 AND $vbulletin->GPC['optionnumber'] <= $totaloptions)
		{
				$pollvote =& datamanager_init('PollVote', $vbulletin, ERRTYPE_STANDARD);
				$pollvote->set('pollid',     $pollid);
				$pollvote->set('votedate',   TIMENOW);
				$pollvote->set('voteoption', $vbulletin->GPC['optionnumber']);
				if (!$vbulletin->userinfo['userid'])
				{
					$pollvote->set('userid', NULL, false);
				}
				else
				{
					$pollvote->set('userid', $vbulletin->userinfo['userid']);
				}
				$pollvote->set('votetype',   0);
				if (!$pollvote->save(true, false, false, false, true))
				{
					$vbulletin->url = fetch_seo_url('thread', $threadinfo);
					print_standard_redirect('redirect_pollvoteduplicate');  
				}
		}

		// make last reply date == last vote date
		if ($vbulletin->options['updatelastpost'])
		{
			// option selected in CP
			$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
			$threadman->set_existing($threadinfo);
			$threadman->set('lastpost', TIMENOW);
			$threadman->save();
		}

		($hook = vBulletinHook::fetch_hook('poll_vote_complete')) ? eval($hook) : false;

		if ($vbulletin->GPC['hkey'])
		{
			vB_Cache::instance()->expire($vbulletin->GPC['hkey']);
		}
		else
		{
			$vbulletin->url = fetch_seo_url('thread', $threadinfo);
		}
		// redirect
		print_standard_redirect('redirect_pollvotethanks');  
	}
	else
	{
		($hook = vBulletinHook::fetch_hook('poll_vote_complete')) ? eval($hook) : false;

		eval(standard_error(fetch_error('nopolloptionselected')));
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 62098 $
|| ####################################################################
\*======================================================================*/
?>
