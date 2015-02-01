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
define('THIS_SCRIPT', 'attachment_inlinemod');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('threadmanage', 'posting', 'inlinemod');

// get special data templates from the datastore
$specialtemplates = array();

$globaltemplates = array(
	'threadadmin_authenticate'
);

$actiontemplates = array(
	'attachmentdelete' => array('moderation_deleteattachments'),
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/modfunctions.php');
require_once(DIR . '/includes/functions_log_error.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (($current_memory_limit = ini_size_to_bytes(@ini_get('memory_limit'))) < 128 * 1024 * 1024 AND $current_memory_limit > 0)
{
	@ini_set('memory_limit', 128 * 1024 * 1024);
}
@set_time_limit(0);

$itemlimit = 200;

// This is a list of ids that were checked on the page we submitted from
$vbulletin->input->clean_array_gpc('p', array(
	'attachmentid'    => TYPE_UINT,
	'attachmentslist' => TYPE_ARRAY_KEYS_INT,
));

if (!$vbulletin->userinfo['userid'])
{
	print_no_permission();
}

switch ($_POST['do'])
{
	case 'doattachmentdelete':
	{
		$inline_mod_authenticate = true;
		break;
	}
	default:
	{
		$inline_mod_authenticate = false;
		($hook = vBulletinHook::fetch_hook('attachment_inlinemod_authenticate_switch')) ? eval($hook) : false;
	}
}

if ($inline_mod_authenticate AND !inlinemod_authenticated())
{
	show_inline_mod_login();
}

switch ($_POST['do'])
{
	case 'attachmentunapprove':
	case 'attachmentapprove':
	case 'attachmentdelete':

		if (empty($vbulletin->GPC['attachmentslist']))
		{
			standard_error(fetch_error('you_did_not_select_any_valid_attachments'));
		}

		if (count($vbulletin->GPC['attachmentslist']) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_attachments', $itemlimit));
		}

		$attachmentids = implode(', ', $vbulletin->GPC['attachmentslist']);
		break;

	case 'doattachmentdelete':

		$vbulletin->input->clean_array_gpc('p', array(
			'attachmentids' => TYPE_STR,
		));
		$attachmentids = explode(',', $vbulletin->GPC['attachmentids']);
		$attachmentids = $vbulletin->input->clean($attachmentids, TYPE_ARRAY_UINT);

		if (count($attachmentids) > $itemlimit)
		{
			standard_error(fetch_error('you_are_limited_to_working_with_x_attachments', $itemlimit));
		}
		break;
}

// set forceredirect for IIS
$forceredirect = (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);

$messagelist = $messagearray = $userlist = array();

($hook = vBulletinHook::fetch_hook('attachment_inlinemod_start')) ? eval($hook) : false;

if ($_POST['do'] == 'clearattachment')
{
	setcookie('vbulletin_inlineattachment', '', TIMENOW - 3600, '/');

	print_standard_redirect('redirect_inline_messagelist_cleared', true, $forceredirect);  
}

if ($_POST['do'] == 'attachmentapprove')
{
	// Permissions are verified within the attachment dm
	$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_STANDARD);
	$attachdata->condition = "attachmentid IN ($attachmentids)";
	$attachdata->approve();

	setcookie('vbulletin_inlineattachment', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('attachment_inlinemod_approve')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_approvedattachments', true, $forceredirect);  
}

if ($_POST['do'] == 'attachmentunapprove')
{
	// Permissions are verified within the attachment dm
	$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_STANDARD);
	$attachdata->condition = "attachmentid IN ($attachmentids)";
	$attachdata->unapprove();

	setcookie('vbulletin_inlineattachment', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('attachment_inlinemod_unapprove')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_approvedattachments', true, $forceredirect);  
}

if ($_POST['do'] == 'attachmentdelete')
{
	$attachmentcount = count($vbulletin->GPC['attachmentslist']);

	$url =& $vbulletin->url;

	$navbits = array('' => $vbphrase['delete_attachments']);
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('attachment_inlinemod_delete')) ? eval($hook) : false;

	$templater = vB_Template::create('moderation_deleteattachments');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('attachmentcount', $attachmentcount);
		$templater->register('attachmentids', $attachmentids);
		$templater->register('url', $url);
	print_output($templater->render());
}

if ($_POST['do'] == 'doattachmentdelete')
{
	// Permissions are verified within the attachment dm
	$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_STANDARD);
	$attachdata->condition = "attachmentid IN (" . implode(", ", $attachmentids) . ")";
	$attachdata->delete();

	// empty cookie
	setcookie('vbulletin_inlineattachment', '', TIMENOW - 3600, '/');

	($hook = vBulletinHook::fetch_hook('attachment_inlinemod_dodelete')) ? eval($hook) : false;

	print_standard_redirect('redirect_inline_deletedattachments', true, $forceredirect);  

}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 30287 $
|| ####################################################################
\*======================================================================*/