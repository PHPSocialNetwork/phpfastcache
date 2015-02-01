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
define('THIS_SCRIPT', 'printthread');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('showthread', 'postbit');

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'printthread',
	'printthreadbit',
	'printthreadbit_ignore',
	'bbcode_code_printable',
	'bbcode_html_printable',
	'bbcode_php_printable',
	'bbcode_quote_printable',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_bbcode_alt.php');
require_once(DIR . '/includes/functions_bigthree.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

verify_forum_url();

$vbulletin->input->clean_array_gpc('r', array(
	'perpage'	=> TYPE_UINT,
	'pagenumber'=> TYPE_UINT
));

($hook = vBulletinHook::fetch_hook('printthread_start')) ? eval($hook) : false;

// oldest first or newest first
if ($vbulletin->userinfo['postorder'] == 0)
{
	$postorder = '';
}
else
{
	$postorder = 'DESC';
}

if ($vbulletin->options['wordwrap'])
{
	$threadinfo['title'] = fetch_word_wrapped_string($threadinfo['title']);
}

if (!$threadinfo['threadid'] OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')) OR $threadinfo['isdeleted'] OR (in_coventry($threadinfo['postuserid']) AND !can_moderate($threadinfo['forumid'])))
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
}

$forumperms = fetch_permissions($threadinfo['forumid']);
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
{
	print_no_permission();
}
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($threadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR $vbulletin->userinfo['userid'] == 0))
{
	print_no_permission();
}

if ($threadinfo['open'] == 10)
{
	//pollid is the real threadid for a redirect 
	exec_header_redirect(fetch_seo_url('threadprint', $threadinfo, array(), 'pollid'));
}

// check if there is a forum password and if so, ensure the user has it set
verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

// split thread over pages if necessary
$countposts = $db->query_first_slave("
	SELECT COUNT(*) AS total
	FROM " . TABLE_PREFIX . "post AS post
	WHERE threadid=$threadinfo[threadid] AND visible=1
");
$totalposts = $countposts['total'];

$vbulletin->GPC['perpage'] = sanitize_maxposts($vbulletin->GPC['perpage']);
$maxperpage = sanitize_maxposts(-1);

if ($vbulletin->GPC['pagenumber'] < 1)
{
	$vbulletin->GPC['pagenumber'] = 1;
}

$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

$pagenav = construct_page_nav(
	$vbulletin->GPC['pagenumber'],
	$vbulletin->GPC['perpage'],
	$totalposts,
	'',
	'',
	'',
	'threadprint',
	$threadinfo,
	array('pp' => $vbulletin->GPC['perpage'])
);
// end page splitter

$bbcode_parser = new vB_BbCodeParser_PrintableThread($vbulletin, fetch_tag_list());

$ignore = array();
if (trim($vbulletin->userinfo['ignorelist']))
{
	$ignorelist = preg_split('/( )+/', trim($vbulletin->userinfo['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);
	foreach ($ignorelist AS $ignoreuserid)
	{
		$ignore["$ignoreuserid"] = 1;
	}
}

$posts = $db->query_read_slave("
	SELECT post.*,post.username AS postusername,user.username
	FROM " . TABLE_PREFIX . "post AS post
	LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = post.userid)
	WHERE post.threadid=$threadid AND post.visible=1
	ORDER BY dateline $postorder
	LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
");

$postbits = '';
while ($post = $db->fetch_array($posts))
{
	// hide users in Coventry from non-staff members
	if ($tachyuser = in_coventry($post['userid']) AND !can_moderate($threadinfo['forumid']))
	{
		continue;
	}

	if ($tachyuser)
	{
		$show['adminignore'] = true;
		$bit_template = 'printthreadbit_ignore';
	}
	else if ($ignore["$post[userid]"])
	{
		$show['adminignore'] = false;
		$bit_template = 'printthreadbit_ignore';
	}
	else
	{
		$bit_template = 'printthreadbit';
	}

	$post['postdate'] = vbdate($vbulletin->options['dateformat'], $post['dateline']);
	$post['posttime'] = vbdate($vbulletin->options['timeformat'], $post['dateline']);

	if ($vbulletin->options['wordwrap'])
	{
		$post['title'] = fetch_word_wrapped_string($post['title']);
	}

	if (!$post['userid'])
	{
		$post['username'] = $post['postusername'];
	}

	$post['message'] = $bbcode_parser->parse($post['pagetext'], 'nonforum', false);

	($hook = vBulletinHook::fetch_hook('printthread_post')) ? eval($hook) : false;

	$templater = vB_Template::create($bit_template);
	$templater->register('post', $post);
	$templater->register('xhtml_id', ++$xhtml);
	$postbits .= $templater->render();
}

($hook = vBulletinHook::fetch_hook('printthread_complete')) ? eval($hook) : false;

$templater = vB_Template::create('printthread');
	$templater->register_page_templates();
	$templater->register('foruminfo', $foruminfo);
	$templater->register('maxperpage', $maxperpage);
	$templater->register('pagenav', $pagenav);
	$templater->register('postbits', $postbits);
	$templater->register('threadid', $threadid);
	$templater->register('threadinfo', $threadinfo);
	$templater->register('perpage', $vbulletin->GPC['perpage']);
print_output($templater->render());

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
?>
