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
@set_time_limit(0);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('GET_EDIT_TEMPLATES', true);
define('THIS_SCRIPT', 'newattachment');
define('CSRF_PROTECTION', true);

$flashstrings = array(
	'^shockwave flash$',
	'^adobe flash player \d+$'
);

if (preg_match('/(' . implode('|', $flashstrings) . ')/si', $_SERVER['HTTP_USER_AGENT']) AND $_SERVER['REQUEST_METHOD'] == 'POST' AND $_POST['ajax'] == 1 AND $_POST['do'] == 'manageattach')
{

	define('NOCHECKSTATE', 1);
	define('SKIP_SESSIONCREATE', true);
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('posting');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
	'assetmanager',
	'assetmanager_thumbview',
	'assetmanager_uploadcontrol',
	'newattachment',
	'newattachmentbit',
	'newpost_attachmentbit',
	'newattachment_errormessage',
	'newattachment_keybit',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_newpost.php');
require_once(DIR . '/includes/functions_file.php');
require_once(DIR . '/packages/vbattach/attach.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$vbulletin->input->clean_array_gpc('r', array(
	'values'        => TYPE_ARRAY,
	'categoryid'    => TYPE_UINT,
	'userid'        => TYPE_UINT,
));

// Variables that are reused in templates
$poststarttime = $vbulletin->GPC['values']['poststarttime'] = $vbulletin->input->clean_gpc('r', 'poststarttime', TYPE_UINT);
$posthash      = $vbulletin->GPC['values']['posthash']      = $vbulletin->input->clean_gpc('r', 'posthash',      TYPE_NOHTML);
$contenttypeid = $vbulletin->input->clean_gpc('r', 'contenttypeid', TYPE_NOHTML);
$insertinline  = $vbulletin->input->clean_gpc('r', 'insertinline', TYPE_UINT);

if (
	!$vbulletin->userinfo['userid'] // Guests can not post attachments
		OR
	empty($vbulletin->userinfo['attachmentextensions'])
		OR
	($vbulletin->GPC['posthash'] != md5($vbulletin->GPC['poststarttime'] . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']))
)
{
	if (!$vbulletin->userinfo['userid'] AND $vbulletin->GPC['userid'])
	{
		$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
		if ($vbulletin->GPC['posthash'] == md5($vbulletin->GPC['poststarttime'] . $userinfo['userid'] . $userinfo['salt']))
		{
			$vbulletin->userinfo = $userinfo;
			cache_permissions($vbulletin->userinfo, true);
		}
		else
		{
			print_no_permission();
		}
	}
	else
	{
		print_no_permission();
	}
}

if (
	!($attachlib =& vB_Attachment_Store_Library::fetch_library($vbulletin, $contenttypeid, $vbulletin->GPC['categoryid'], $vbulletin->GPC['values']))
		OR
	!$attachlib->verify_permissions()
)
{
	print_no_permission();
}

$new_attachlist_js = '';

($hook = vBulletinHook::fetch_hook('newattachment_start')) ? eval($hook) : false;

$show['errors'] = false;

if (!$attachlib->fetch_attachcount())
{
	print_no_permission();
}

$show['ajaxform'] = ($_REQUEST['do'] == 'assetmanager');
$show['ajaxupload'] = ($_POST['ajax'] AND $_POST['do'] == 'manageattach');

$currentattachment = array(
	'attachmentid' => 0,
	'hasthumbnail' => false,
);

// ##################### Add Attachment to Content ####################
if ($_POST['do'] == 'manageattach')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'upload'     => TYPE_STR,
		'delete'     => TYPE_ARRAY_STR,
		'filedata'   => TYPE_ARRAY_UINT,
		'flash'      => TYPE_UINT,
		'imageonly'  => TYPE_BOOL,
	));

	$uploads = array();
	if (!$vbulletin->GPC['upload'])
	{
		$attachlib->delete($vbulletin->GPC['delete']);
	}
	else
	{	// Attach file...
		$vbulletin->input->clean_gpc('f', 'attachment',    TYPE_FILE);
		$vbulletin->input->clean_gpc('p', 'attachmenturl', TYPE_ARRAY_STR);

		if ($vbulletin->GPC['flash'] AND is_array($vbulletin->GPC['attachment']))
		{
			$vbulletin->GPC['attachment']['utf8_names'] = true;
		}

		$uploadids = $attachlib->upload($vbulletin->GPC['attachment'], $vbulletin->GPC['attachmenturl'], $vbulletin->GPC['filedata'], $vbulletin->GPC['imageonly']);
		$uploads = explode(',', $uploadids);

		// if $uploads > 1 then we are in a case where $currentattachment isn't used
		$currentattachment['attachmentid'] = $uploads[0];

		($hook = vBulletinHook::fetch_hook('newattachment_attach')) ? eval($hook) : false;

		if (!empty($attachlib->errors))
		{
			$errorlist = '';
			foreach ($attachlib->errors AS $error)
			{
				$filename = fetch_censored_text(htmlspecialchars_uni($error['filename'], false));
				$errormessage = $error['error'] ? $error['error'] : $vbphrase["$error[errorphrase]"];
				$templater = vB_Template::create('newattachment_errormessage');
					$templater->register('errormessage', $errormessage);
					$templater->register('filename', $filename);
				$errorlist .= $templater->render();
			}
			$show['errors'] = true;
		}
	}
}

$currentattaches = $attachlib->fetch_attachments();

require_once(DIR . '/includes/functions_editor.php');
$wysiwyg = is_wysiwyg_compatible();

$attachcount = 0;
$totalsize = 0;
$attachments = '';
$attachmentsarray = array();
$updatearray = array();
$attachdisplaylib =& vB_Attachment_Upload_Displaybit_Library::fetch_library($vbulletin, $contenttypeid);

while ($attach = $db->fetch_array($currentattaches))
{
	$attach['extension'] = strtolower(file_extension($attach['filename']));
	$attach['filename'] = fetch_censored_text(htmlspecialchars_uni($attach['filename'], false));
	$attachcount++;
	$totalsize += intval($attach['filesize']);
    $attach['filesize_formatted'] = vb_number_format($attach['filesize'], 1, true);
    $show['thumbnail'] = $attach['hasthumbnail'] ? true : false;
    if ($attach['attachmentid'] == $currentattachment['attachmentid'])
    {
        $currentattachment['hasthumbnail'] = $attach['hasthumbnail'];
    }

    $assetinfo = $attach;

	if ($show['ajaxform'] OR $show['ajaxupload'])
	{
		$show['uploadasset'] = true;
		$show['smallthumb'] = true;

		$templater = vB_Template::create('assetmanager_thumbview');
		$templater->register('attach', $attach);
		$templater->register('assetinfo', $assetinfo);

		$assetinfo['html'] = $attachdisplaylib->process_display_template($assetinfo, $vbulletin->GPC['values']);
		if ($show['ajaxform'])
		{
			$attachments .= $templater->render();
			$new_attachlist_js .= $attachdisplaylib->construct_attachment_add_js($assetinfo, true);
		}
		else
		{
			$attachmentsarray[] = $templater->render();
			$updatearray[] = $assetinfo;
		}
	}
	else
	{
		$templater = vB_Template::create('newattachmentbit');
			$templater->register('attach', $attach);
		$attachments .= $templater->render();

		$attach['html'] = $attachdisplaylib->process_display_template($attach, $vbulletin->GPC['values']);
		$new_attachlist_js .= $attachdisplaylib->construct_attachment_add_js($attach, true);
	}

	if ($wysiwyg == 1)
	{
		$attach['filename'] = fetch_trimmed_title($attach['filename'], 12);
	}
}

$totallimit = vb_number_format($totalsize, 1, true);

if ($attachlimit = $attachlib->userinfo['permissions']['attachlimit'])
{
	$attachdata = $vbulletin->db->query_first("
	SELECT SUM(filesize) AS sum
	FROM
	(
		SELECT DISTINCT fd.filedataid, fd.filesize
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (fd.filedataid = a.filedataid)
		WHERE
			a.userid = " . $attachlib->userinfo['userid'] . "
	) AS x
	");

	$attach_username = $attachlib->userinfo['username'];
	$attachsum = intval($attachdata['sum']);

	($hook = vBulletinHook::fetch_hook('newattachment_attachsum')) ? eval($hook) : false;

	if ($attachsum >= $attachlimit)
	{
		$totalsize = 0;
		$attachsize = 100;
	}
	else
	{
		$attachsize = ceil($attachsum / $attachlimit * 100);
		$totalsize = 100 - $attachsize;
	}

	$attachsum = vb_number_format($attachsum, 1, true);
	$attachlimit = vb_number_format($attachlimit, 1, true);
	$show['attachmentlimits'] = true;
	$show['currentsize'] = $attachsize ? true : false;
	$show['totalsize'] = $totalsize ? true : false;
}
else
{
	$show['attachmentlimits'] = false;
	$show['currentsize'] = false;
	$show['totalsize'] = false;
	$attachsum = $attachlimit = $attachsize = 0;
}

// $show['forumclosed'] is a generic switch for this content isn't accepting uploads
if ($show['forumclosed'])// OR ($attachcount >= $vbulletin->options['attachlimit'] AND $vbulletin->options['attachlimit']))
{
	$show['attachoption'] = false;
}
else
{
	// If we have unlimited attachments, set filesleft to box count
	if ($vbulletin->options['attachboxcount'])
	{
		$show['attachoption'] = true;
		$show['attachfile'] = true;
		$filesleft = $vbulletin->options['attachlimit'] ? $vbulletin->options['attachlimit'] - $attachcount : $vbulletin->options['attachboxcount'];
		$filesleft = $filesleft < $vbulletin->options['attachboxcount'] ? $filesleft : $vbulletin->options['attachboxcount'];

		$boxcount = 1;
		$attachinput = '';
		$attachboxes = array();
		while ($boxcount <= $filesleft)
		{
			$attachboxes[] = '';
			$boxcount++;
		}
	}

	if ($vbulletin->options['attachurlcount'] AND (ini_get('allow_url_fopen') != 0 OR function_exists('curl_init')))
	{
		$show['attachoption'] = true;
		$show['attachurl'] = true;
		$filesleft = $vbulletin->options['attachlimit'] ? $vbulletin->options['attachlimit'] - $attachcount : $vbulletin->options['attachurlcount'];
		$filesleft = $filesleft < $vbulletin->options['attachurlcount'] ? $filesleft : $vbulletin->options['attachurlcount'];

		$boxcount = 1;
		$attachurlinput = '';
		$urlboxes = array();
		while ($boxcount <= $filesleft)
		{
			$urlboxes[] = '';
			$attachurlinput .= "<input type=\"text\" class=\"bginput\" name=\"attachmenturl[]\" size=\"30\" dir=\"ltr\" /><br />\n";
			$boxcount++;
		}
	}

	$vbphrase['upload_word'] = is_browser('safari') ? $vbphrase['choose_file'] : $vbphrase['browse'];
}

$show['attachmentlist'] = $attachments ? true : false;

$inimaxattach = fetch_max_upload_size();

($hook = vBulletinHook::fetch_hook('newattachment_complete')) ? eval($hook) : false;

foreach($attachlib->userinfo['attachmentpermissions'] AS $filetype => $extension)
{
	if (
		!empty($extension['permissions'])
			AND
		(
			!$extension['contenttypes']["$contenttypeid"]
				OR
			!isset($extension['contenttypes']["$contenttypeid"]['e'])
				OR
			$extension['contenttypes']["$contenttypeid"]['e']
		)
	)
	{
		exec_switch_bg();
		$extension['size'] = $extension['size'] > 0 ? vb_number_format($extension['size'], 1, true) : '-';
		$extension['width'] = $extension['width'] > 0 ? $extension['width'] : '-';
		$extension['height'] = $extension['height'] > 0 ? $extension['height'] : '-';
		$extension['extension'] = $filetype;
		$templater = vB_Template::create('newattachment_keybit');
			$templater->register('bgclass', $bgclass);
			$templater->register('extension', $extension);
		$attachkeybits .= $templater->render();
	}
}
$show['updateparent'] = true;
$hiddenvalues = implode("\r\n", array_map('fetch_hidden_value', array_keys($vbulletin->GPC['values']), $vbulletin->GPC['values']));
// complete

if ($show['ajaxupload'])
{
	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('container');
	if (!empty($attachlib->errors))
	{
		$xml->add_group('uploaderrors');
		foreach ($attachlib->errors AS $error)
		{
			$filename = fetch_censored_text(htmlspecialchars_uni($error['filename'], false));
			$errormessage = $error['error'] ? $error['error'] : $vbphrase["$error[errorphrase]"];
			$xml->add_tag('uploaderror', "$filename: $errormessage");
			if ($vbulletin->GPC['flash'])
			{
				echo "error: $errormessage";
			}
		}
		$xml->close_group();
		if ($vbulletin->GPC['flash'])
		{
			exit;
		}
	}

	if ($vbulletin->GPC['flash'])
	{
		echo "ok - " . intval($currentattachment['attachmentid']) . " - " . $currentattachment['hasthumbnail'];
		exit;
	}

	$xml->add_group('attachments');
		foreach($attachmentsarray AS $key => $attachment )
		{
			$xml->add_tag('attachment',   $attachment);
			$xml->add_tag('displaybit',   $updatearray["$key"]['html']);
			$xml->add_tag('filename',     $updatearray["$key"]['filename']);
			$xml->add_tag('filesize',     $updatearray["$key"]['filesize']);
			$xml->add_tag('attachmentid', $updatearray["$key"]['attachmentid']);
			$xml->add_tag('hasthumbnail', $updatearray["$key"]['hasthumbnail']);
			$xml->add_tag('icon',         vB_Template_Runtime::fetchStyleVar('imgdir_attach') . '/' . $updatearray["$key"]['extension'] . '.gif');
			$xml->add_tag('new',          in_array($updatearray["$key"]['attachmentid'], $uploads) ? 1 : 0);
		}
	$xml->close_group('attachments');

	$xml->add_tag('stats', $attachcount ? construct_phrase($vbphrase['attachments_x_y'], $attachcount, $totallimit) : $vbphrase['attachments']);
	$xml->add_tag('attachsize', $attachsize . '%');
	$xml->add_tag('totalsize', $totalsize . '%');
	$xml->add_tag('attachtotal', construct_phrase($vbphrase['current_attachment_total_x'], $attachsum));
	$xml->add_tag('attachstorage', construct_phrase($vbphrase['maximum_attachment_storage_x'], $attachlimit));
	$xml->add_tag('attachsum', $attachsum);
	$xml->close_group();
	$xml->print_xml();
}

if ($show['ajaxform'])
{
	$templater = vB_Template::create('assetmanager');
		$templater->register_page_templates();
		$templater->register('poststarttime', $vbulletin->GPC['poststarttime']);
		$templater->register('posthash', $vbulletin->GPC['posthash']);
		$templater->register('contenttypeid', $vbulletin->GPC['contenttypeid']);
		$templater->register('insertinline', $vbulletin->GPC['insertinline']);
		$templater->register('inimaxattach', $inimaxattach);
		$templater->register('hiddenvalues', $hiddenvalues);
		$templater->register('attachments', $attachments);
		$templater->register('attachinput', $attachinput);
		$templater->register('attachkeybits', $attachkeybits);
		$templater->register('totallimit', $totallimit);
		$templater->register('attachcount', $attachcount);
		$templater->register('attachsum', $attachsum);
		$templater->register('attachlimit', $attachlimit);
		$templater->register('attachsize', $attachsize);
		$templater->register('totalsize', $totalsize);
		$templater->register('attach_username', $attach_username);
		$templater->register('auth_type', (
												empty($_SERVER['AUTH_USER'])
													AND
												empty($_SERVER['REMOTE_USER'])
											) ? 0 : 1);

		$templater->register('asset_enable', $vbulletin->userinfo['vbasset_enable'] ? $vbulletin->options['vbasset_enable'] : 0);
		$templater->register('new_attachlist_js', $new_attachlist_js);
	print_output($templater->render());
}

$templater = vB_Template::create('newattachment');
	$templater->register_page_templates();
	$templater->register('ajaxbaseurl', VB_URL_BASE_PATH);
	$templater->register('attachinput', $attachinput);
	$templater->register('attachkeybits', $attachkeybits);
	$templater->register('attachlimit', $attachlimit);
	$templater->register('attachments', $attachments);
	$templater->register('attachsize', $attachsize);
	$templater->register('attachsum', $attachsum);
	$templater->register('attachurlinput', $attachurlinput);
	$templater->register('attach_username', $attach_username);
	$templater->register('contenttypeid', $vbulletin->GPC['contenttypeid']);
	$templater->register('editpost', $editpost);
	$templater->register('errorlist', $errorlist);
	$templater->register('headinclude', $headinclude);
	$templater->register('hiddenvalues', $hiddenvalues);
	$templater->register('inimaxattach', $inimaxattach);
	$templater->register('new_attachlist_js', $new_attachlist_js);
	$templater->register('posthash', $posthash);
	$templater->register('poststarttime', $poststarttime);
	$templater->register('totallimit', $totallimit);
	$templater->register('totalsize', $totalsize);
	$templater->register('urlboxes', $urlboxes);
	$templater->register('attachboxes', $attachboxes);
	$templater->register('values', $vbulletin->GPC['values']);
print_output($templater->render());


function fetch_hidden_value($key, $value)
{
	return '<input type="hidden" name="values[' . htmlspecialchars_uni($key) . ']" value="' . htmlspecialchars_uni($value) . '" />';
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 59308 $
|| ####################################################################
\*======================================================================*/