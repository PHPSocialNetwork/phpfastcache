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
define('GET_EDIT_TEMPLATES', 'newpm,insertpm,showpm');
define('THIS_SCRIPT', 'private');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'messaging',
	'posting',
	'postbit',
	'pm',
	'reputationlevel',
	'user'
);

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache',
	'banemail',
	'noavatarperms',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'USERCP_SHELL',
	'usercp_nav_folderbit'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'editfolders' => array(
		'pm_editfolders',
		'pm_editfolderbit',
	),
	'emptyfolder' => array(
		'pm_emptyfolder',
	),
	'showpm' => array(
		'pm_showpm',
		'postbit',
		'postbit_wrapper',
		'postbit_onlinestatus',
		'bbcode_code',
		'bbcode_html',
		'bbcode_php',
		'bbcode_quote',
		'bbcode_video',
		'im_aim',
		'im_icq',
		'im_msn',
		'im_yahoo',
		'im_skype',
	),
	'newpm' => array(
		'pm_newpm',
	),
	'managepm' => array(
		'pm_movepm',
	),
	'trackpm' => array(
		'pm_trackpm',
		'pm_receipts',
		'pm_receiptsbit',
	),
	'messagelist' => array(
		'pm_messagelist',
		'pm_messagelist_periodgroup',
		'pm_messagelistbit',
		'pm_messagelistbit_ignore',
		'pm_filter',
		'forumdisplay_sortarrow'
	),
	'report' => array(
		'newpost_usernamecode',
		'reportitem'
	),
	'showhistory' => array(
		'postbit',
		'postbit_wrapper',
		'postbit_onlinestatus',
		'bbcode_code',
		'bbcode_html',
		'bbcode_php',
		'bbcode_quote',
		'bbcode_video',
		'im_aim',
		'im_icq',
		'im_msn',
		'im_yahoo',
		'im_skype',
		'pm_nomessagehistory'
	)
);
$actiontemplates['insertpm'] =& $actiontemplates['newpm'];

//Limit the number of folders and the length of title text
$char_limit = 200;
$folder_limit = 1000;

// ################## SETUP PROPER NO DO TEMPLATES #######################
if (empty($_REQUEST['do']))
{
	$temppmid = ($temppmid = intval($_REQUEST['pmid'])) < 0 ? 0 : $temppmid;

	if ($temppmid > 0)
	{
		$actiontemplates['none'] =& $actiontemplates['showpm'];
	}
	else
	{
		$actiontemplates['none'] =& $actiontemplates['messagelist'];
	}
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_user.php');
require_once(DIR . '/includes/functions_misc.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// ###################### Start pm code parse #######################
function parse_pm_bbcode($bbcode, $smilies = true)
{
	global $vbulletin;

	require_once(DIR . '/includes/class_bbcode.php');
	$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
	return $bbcode_parser->parse($bbcode, 'privatemessage', $smilies);
}

// ###################### Start pm update counters #######################
// update the pm counters for $vbulletin->userinfo
function build_pm_counters()
{
	global $vbulletin;

	$pmcount = $vbulletin->db->query_first("
		SELECT
			COUNT(pmid) AS pmtotal,
			SUM(IF(messageread = 0 AND folderid >= 0, 1, 0)) AS pmunread
		FROM " . TABLE_PREFIX . "pm AS pm
		WHERE pm.userid = " . $vbulletin->userinfo['userid'] . "
	");

	$pmcount['pmtotal'] = intval($pmcount['pmtotal']);
	$pmcount['pmunread'] = intval($pmcount['pmunread']);

	if ($vbulletin->userinfo['pmtotal'] != $pmcount['pmtotal'] OR $vbulletin->userinfo['pmunread'] != $pmcount['pmunread'])
	{
		// init user data manager
		$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
		$userdata->set_existing($vbulletin->userinfo);
		$userdata->set('pmtotal', $pmcount['pmtotal']);
		$userdata->set('pmunread', $pmcount['pmunread']);
		$userdata->save();
	}
}

// ############################### initialisation ###############################

if (!$vbulletin->options['enablepms'])
{
	eval(standard_error(fetch_error('pm_adminoff')));
}

// the following is the check for actions which allow creation of new pms
if ($permissions['pmquota'] < 1 OR !$vbulletin->userinfo['receivepm'])
{
	$show['createpms'] = false;
}

// check permission to use private messaging
if (($permissions['pmquota'] < 1 AND (!$vbulletin->userinfo['pmtotal'] OR in_array($_REQUEST['do'], array('insertpm', 'newpm')))) OR !$vbulletin->userinfo['userid'])
{
	print_no_permission();
}

if (!$vbulletin->userinfo['receivepm'] AND in_array($_REQUEST['do'], array('insertpm', 'newpm')))
{
	eval(standard_error(fetch_error('pm_turnedoff')));
}

// start navbar
$navbits = array(
	'usercp.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['user_control_panel'],
	'private.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['private_messages']
);

// select correct part of forumjump
$navpopup = array(
	'id'    => 'pm_navpopup',
	'title' => $vbphrase['private_messages'],
	'link'  => 'private.php' . $vbulletin->session->vars['sessionurl_q'],
);
construct_quick_nav($navpopup);


$onload = '';
$show['trackpm'] = $cantrackpm = $permissions['pmpermissions'] & $vbulletin->bf_ugp_pmpermissions['cantrackpm'];
$includecss = array();
$includeiecss = array();
$vbulletin->input->clean_gpc('r', 'pmid', TYPE_UINT);


// ############################### default do value ###############################
if (empty($_REQUEST['do']))
{
	if (!$vbulletin->GPC['pmid'])
	{
		$_REQUEST['do'] = 'messagelist';
	}
	else
	{
		$_REQUEST['do'] = 'showpm';
	}
}

($hook = vBulletinHook::fetch_hook('private_start')) ? eval($hook) : false;

// ############################### start update folders ###############################
// update the user's custom pm folders
if ($_POST['do'] == 'updatefolders')
{
	$vbulletin->input->clean_gpc('p', 'folder', TYPE_ARRAY_NOHTML);

	if (!empty($vbulletin->GPC['folder']))
	{
		$oldpmfolders = unserialize($vbulletin->userinfo['pmfolders']);

		$pmfolders = array();
		$updatefolders = array();
		$old_count = count($oldpmfolders);
		foreach ($vbulletin->GPC['folder'] AS $folderid => $foldername)
		{
			$folderid = intval($folderid);

			if (($foldername != ''))
			{
				//limit the title to something sane.
				$pmfolders["$folderid"] = vbchop($foldername, $char_limit);
			}
			else if (isset($oldpmfolders["$folderid"]))
			{
				$updatefolders[] = $folderid;
			}
		}

		$new_count = count($pmfolders);
		//its possible, though unlikely, that there is a legitimate user out there
		//with too many folders.  Rather than preventing them from saving anything,
		//we'll just prevent them from adding any folders if they are over the limit
		//if they just change some titles or delete some but not enough folders (or
		//even delete some and add no more than they deleted) we'll let it slide.
		if ($new_count > $folder_limit and $new_count > $old_count)
		{
			eval(standard_error(fetch_error('folder_limit_exceeded', $folder_limit)));
		}

		if (!empty($updatefolders))
		{
			$db->query_write("UPDATE " . TABLE_PREFIX . "pm SET folderid=0 WHERE userid=" . $vbulletin->userinfo['userid'] . " AND folderid IN(" . implode(', ', $updatefolders) . ")");
		}

		require_once(DIR . '/includes/functions_databuild.php');
		if (!empty($pmfolders))
		{
			natcasesort($pmfolders);
		}
		build_usertextfields('pmfolders', iif(empty($pmfolders), '', serialize($pmfolders)), $vbulletin->userinfo['userid']);
	}
	($hook = vBulletinHook::fetch_hook('private_updatefolders')) ? eval($hook) : false;

	$itemtype = $vbphrase['private_message'];
	$itemtypes = $vbphrase['private_messages'];
	print_standard_redirect(array('foldersedited',$itemtype,$itemtypes));
}

// ############################### start empty folders ###############################
if ($_REQUEST['do'] == 'emptyfolder')
{
	$vbulletin->input->clean_gpc('r', 'folderid', TYPE_INT);

	$folderid = $vbulletin->GPC['folderid'];

	// generate navbar
	$navbits[''] = $vbphrase['confirm_deletion'];
	$pmfolders = array('0' => $vbphrase['inbox'], '-1' => $vbphrase['sent_items']);
	if (!empty($vbulletin->userinfo['pmfolders']))
	{
		$pmfolders = $pmfolders + unserialize($vbulletin->userinfo['pmfolders']);
	}
	if (!isset($pmfolders["{$vbulletin->GPC['folderid']}"]))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['folder'], $vbulletin->options['contactuslink'])));
	}
	$folder = $pmfolders["{$vbulletin->GPC['folderid']}"];
	$dateline = TIMENOW;

	($hook = vBulletinHook::fetch_hook('private_emptyfolder')) ? eval($hook) : false;

	$page_templater = vB_Template::create('pm_emptyfolder');
	$page_templater->register('dateline', $dateline);
	$page_templater->register('folder', $folder);
	$page_templater->register('folderid', $folderid);
}

// ############################### start confirm empty folders ###############################
if ($_POST['do'] == 'confirmemptyfolder')
{ // confirmation page

	$vbulletin->input->clean_array_gpc('p', array(
		'folderid' => TYPE_INT,
		'dateline' => TYPE_UNIXTIME,
	));

	$deletepms = array();
	// get pms
	$pms = $db->query_read_slave("
		SELECT pmid
		FROM " . TABLE_PREFIX . "pm AS pm
		LEFT JOIN " . TABLE_PREFIX . "pmtext AS pmtext USING (pmtextid)
		WHERE folderid = " . $vbulletin->GPC['folderid'] . "
			AND userid = " . $vbulletin->userinfo['userid'] . "
			AND dateline < " . $vbulletin->GPC['dateline']
	);
	while ($pm = $db->fetch_array($pms))
	{
		$deletepms[] = $pm['pmid'];
	}

	if (!empty($deletepms))
	{
		// remove pms and receipts!
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "pm WHERE pmid IN (" . implode(',', $deletepms) . ")");
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "pmreceipt WHERE pmid IN (" . implode(',', $deletepms) . ")");
		build_pm_counters();
	}

	($hook = vBulletinHook::fetch_hook('private_confirmemptyfolder')) ? eval($hook) : false;

	$vbulletin->url = 'private.php?' . $vbulletin->session->vars['sessionurl'];
	print_standard_redirect('pm_messagesdeleted');
}

// ############################### start edit folders ###############################
// edit the user's custom pm folders
if ($_REQUEST['do'] == 'editfolders')
{
	if (!isset($pmfolders))
	{
		$pmfolders = unserialize($vbulletin->userinfo['pmfolders']);
	}

	$folderjump = construct_folder_jump();

	($hook = vBulletinHook::fetch_hook('private_editfolders_start')) ? eval($hook) : false;

	$usedids = array();

	$editfolderbits = '';
	$show['messagecount'] = true;
	if (!empty($pmfolders))
	{
		$show['customfolders'] = true;
		foreach ($pmfolders AS $folderid => $foldername)
		{
			$usedids[] = $folderid;
			$foldertotal = intval($messagecounters["$folderid"]);
			($hook = vBulletinHook::fetch_hook('private_editfolders_bit')) ? eval($hook) : false;
			$templater = vB_Template::create('pm_editfolderbit');
				$templater->register('folderid', $folderid);
				$templater->register('foldername', $foldername);
				$templater->register('foldertotal', $foldertotal);
			$editfolderbits .= $templater->render();
		}
	}
	else
	{
		$show['customfolders'] = false;
	}
	$show['messagecount'] = false;

	// build the inputs for new folders
	//Only if they are allowed to have more folders
	if (count($pmfolders) < $folder_limit)
	{
		$addfolderbits = '';
		$donefolders = 0;
		$folderid = 0;
		$foldername = '';
		$foldertotal = 0;
		$done = 0;
		$max_adds = min(3, ($folder_limit - count($pmfolders)));
		while ($done < $max_adds)
		{
			$folderid ++;
			if (in_array($folderid, $usedids))
			{
				continue;
			}
			else
			{
				($hook = vBulletinHook::fetch_hook('private_editfolders_bit')) ? eval($hook) : false;
				$done++;
				$templater = vB_Template::create('pm_editfolderbit');
				$templater->register('folderid', $folderid);
				$templater->register('foldername', $foldername);
				$templater->register('foldertotal', $foldertotal);
				$addfolderbits .= $templater->render();
			}
		}
	}

	$inboxtotal = intval($messagecounters[0]);
	$sentitemstotal = intval($messagecounters['-1']);

	// generate navbar
	$navbits[''] = $vbphrase['edit_folders'];

	$page_templater = vB_Template::create('pm_editfolders');

	//if they have all the allowed folders they don't get an 'add';
	if (count($pmfolders) < $folder_limit)
	{
		$page_templater->register('addfolderbits', $addfolderbits);
		$show['ok_to_add'] = 1;
	}
	else
	{
		$show['ok_to_add'] = 0;
	}
	$page_templater->register('editfolderbits', $editfolderbits);
	$page_templater->register('inboxtotal', $inboxtotal);
	$page_templater->register('sentitemstotal', $sentitemstotal);
}

// ############################### delete pm receipt ###############################
// delete one or more pm receipts
if ($_POST['do'] == 'deletepmreceipt')
{
	$vbulletin->input->clean_gpc('p', 'receipt', TYPE_ARRAY_UINT);


	if (empty($vbulletin->GPC['receipt']))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['private_message_receipt'], $vbulletin->options['contactuslink'])));
	}

	($hook = vBulletinHook::fetch_hook('private_deletepmreceipt')) ? eval($hook) : false;

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "pmreceipt WHERE userid=" . $vbulletin->userinfo['userid'] . " AND pmid IN(". implode(', ', $vbulletin->GPC['receipt']) . ")");

	if ($db->affected_rows() == 0)
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['private_message_receipt'], $vbulletin->options['contactuslink'])));
	}
	else
	{
		print_standard_redirect('pm_receiptsdeleted');
	}
}

// ############################### start deny receipt ###############################
// set a receipt as denied
if ($_REQUEST['do'] == 'dopmreceipt')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pmid'    => TYPE_UINT,
		'confirm' => TYPE_BOOL,
		'type'    => TYPE_NOHTML,
	));

	if (!$vbulletin->GPC['confirm'] AND ($permissions['pmpermissions'] & $vbulletin->bf_ugp_pmpermissions['candenypmreceipts']))
	{
		$receiptSql = "UPDATE " . TABLE_PREFIX . "pmreceipt SET readtime = 0, denied = 1 WHERE touserid = " . $vbulletin->userinfo['userid'] . " AND pmid = " . $vbulletin->GPC['pmid'];
	}
	else
	{
		$receiptSql = "UPDATE " . TABLE_PREFIX . "pmreceipt SET readtime = " . TIMENOW . ", denied = 0 WHERE touserid = " . $vbulletin->userinfo['userid'] . " AND pmid = " . $vbulletin->GPC['pmid'];
	}

	($hook = vBulletinHook::fetch_hook('private_dopmreceipt')) ? eval($hook) : false;

	$db->query_write($receiptSql);

	if ($vbulletin->GPC['type'] == 'img')
	{
		header('Content-type: image/gif');
		readfile(DIR . '/' . $vbulletin->options['cleargifurl']);
	}
	else
	{
	?>
<html xmlns="http://www.w3.org/1999/xhtml"><head><title><?php echo $vbulletin->options['bbtitle']; ?></title><style type="text/css"><?php echo $style['css']; ?></style></head><body>
<script type="text/javascript">
self.close();
</script>
</body></html>
	<?php
	}
	flush();
	exit;
}

// ############################### start pm receipt tracking ###############################
// message receipt tracking
if ($_REQUEST['do'] == 'trackpm')
{
	if (!$cantrackpm)
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT,
		'type'       => TYPE_NOHTML
	));

	switch ($vbulletin->GPC['type'])
	{
		case 'confirmed':
		case 'unconfirmed':
			break;

		default:
			$vbulletin->GPC['type'] = '';
			$vbulletin->GPC['pagenumber'] = 1;
	}

	$perpage = $vbulletin->options['pmperpage'];
	if (!$vbulletin->GPC['pagenumber'])
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}

	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;

	($hook = vBulletinHook::fetch_hook('private_trackpm_start')) ? eval($hook) : false;

	$confirmedreceipts = '';

	if (!$vbulletin->GPC['type'] OR $vbulletin->GPC['type'] == 'confirmed')
	{
		$pmreceipts = $db->query_read_slave("
			SELECT SQL_CALC_FOUND_ROWS
				pmreceipt.*, pmreceipt.pmid AS receiptid
			FROM " . TABLE_PREFIX . "pmreceipt AS pmreceipt
			WHERE pmreceipt.userid = " . $vbulletin->userinfo['userid'] . "
				AND pmreceipt.readtime <> 0
			ORDER BY pmreceipt.sendtime DESC
			LIMIT $startat, $perpage
		");
		list($readtotal) = $db->query_first_slave("SELECT FOUND_ROWS()", DBARRAY_NUM);

		$counter = 1;
		if ($readtotal)
		{
			$show['readpm'] = true;
			$tabletitle = $vbphrase['confirmed_private_message_receipts'];
			$tableid = 'pmreceipts_read';
			$collapseobj_tableid =& $vbcollapse["collapseobj_$tableid"];
			$collapseimg_tableid =& $vbcollapse["collapseimg_$tableid"];
			$receiptbits = '';

			while ($receipt = $db->fetch_array($pmreceipts))
			{
				$receipt['send_date'] = vbdate($vbulletin->options['dateformat'], $receipt['sendtime'], true);
				$receipt['send_time'] = vbdate($vbulletin->options['timeformat'], $receipt['sendtime']);
				$receipt['read_date'] = vbdate($vbulletin->options['dateformat'], $receipt['readtime'], true);
				$receipt['read_time'] = vbdate($vbulletin->options['timeformat'], $receipt['readtime']);

				$receiptinfo = array(
					'userid'   => $receipt['touserid'],
					'username' => $receipt['tousername'],
				);

				($hook = vBulletinHook::fetch_hook('private_trackpm_receiptbit')) ? eval($hook) : false;
				$templater = vB_Template::create('pm_receiptsbit');
					$templater->register('receipt', $receipt);
				$receiptbits .= $templater->render();
			}

			$confirmed_pagenav = construct_page_nav($vbulletin->GPC['pagenumber'], $perpage, $readtotal,
				"private.php?" . $vbulletin->session->vars['sessionurl'] . "do=trackpm&amp;type=confirmed"
			);

			$templater = vB_Template::create('pm_receipts');
				$templater->register('collapseimg_tableid', $collapseimg_tableid);
				$templater->register('collapseobj_tableid', $collapseobj_tableid);
				$templater->register('startreceipt', vb_number_format($startat + 1));
				$templater->register('endreceipt', vb_number_format(($vbulletin->GPC['pagenumber'] * $perpage) > $readtotal ? $readtotal : ($vbulletin->GPC['pagenumber'] * $perpage)));
				$templater->register('numreceipts', vb_number_format($readtotal));
				$templater->register('receiptbits', $receiptbits);
				$templater->register('tableid', $tableid);
				$templater->register('tabletitle', $tabletitle);
				$templater->register('counter', $counter++);
			$confirmedreceipts = $templater->render();
		}
	}

	$unconfirmedreceipts = '';

	if (!$vbulletin->GPC['type'] OR $vbulletin->GPC['type'] == 'unconfirmed')
	{
		$pmreceipts = $db->query_read_slave("
			SELECT SQL_CALC_FOUND_ROWS
				pmreceipt.*, pmreceipt.pmid AS receiptid
			FROM " . TABLE_PREFIX . "pmreceipt AS pmreceipt
			WHERE pmreceipt.userid = " . $vbulletin->userinfo['userid'] . "
				AND pmreceipt.readtime = 0
			ORDER BY pmreceipt.sendtime DESC
			LIMIT $startat, $perpage
		");
		list($unreadtotal) = $db->query_first_slave("SELECT FOUND_ROWS()", DBARRAY_NUM);

		if ($unreadtotal)
		{
			$show['readpm'] = false;
			$tabletitle = $vbphrase['unconfirmed_private_message_receipts'];
			$tableid = 'pmreceipts_unread';
			$collapseobj_tableid =& $vbcollapse["collapseobj_$tableid"];
			$collapseimg_tableid =& $vbcollapse["collapseimg_$tableid"];
			$receiptbits = '';

			while ($receipt = $db->fetch_array($pmreceipts))
			{
				$receipt['send_date'] = vbdate($vbulletin->options['dateformat'], $receipt['sendtime'], true);
				$receipt['send_time'] = vbdate($vbulletin->options['timeformat'], $receipt['sendtime']);
				$receipt['read_date'] = vbdate($vbulletin->options['dateformat'], $receipt['readtime'], true);
				$receipt['read_time'] = vbdate($vbulletin->options['timeformat'], $receipt['readtime']);

				$receiptinfo = array(
					'userid'   => $receipt['touserid'],
					'username' => $receipt['tousername'],
				);

				($hook = vBulletinHook::fetch_hook('private_trackpm_receiptbit')) ? eval($hook) : false;
				$templater = vB_Template::create('pm_receiptsbit');
					$templater->register('receipt', $receipt);
				$receiptbits .= $templater->render();
			}

			$unconfirmed_pagenav = construct_page_nav($vbulletin->GPC['pagenumber'], $perpage, $unreadtotal,
				"private.php?" . $vbulletin->session->vars['sessionurl'] . "do=trackpm&amp;type=unconfirmed"
			);

			$templater = vB_Template::create('pm_receipts');
				$templater->register('collapseimg_tableid', $collapseimg_tableid);
				$templater->register('collapseobj_tableid', $collapseobj_tableid);
				$templater->register('startreceipt', vb_number_format($startat + 1));
				$templater->register('endreceipt', vb_number_format(($vbulletin->GPC['pagenumber'] * $perpage) > $unreadtotal ? $unreadtotal : ($vbulletin->GPC['pagenumber'] * $perpage)));
				$templater->register('numreceipts', vb_number_format($unreadtotal));
				$templater->register('receiptbits', $receiptbits);
				$templater->register('tableid', $tableid);
				$templater->register('tabletitle', $tabletitle);
				$templater->register('counter', $counter);
			$unconfirmedreceipts = $templater->render();
		}
	}

	$folderjump = construct_folder_jump();

	// generate navbar
	$navbits[''] = $vbphrase['message_tracking'];

	$show['receipts'] = ($confirmedreceipts != '' OR $unconfirmedreceipts != '');

	$page_templater = vB_Template::create('pm_trackpm');
	$page_templater->register('confirmedreceipts', $confirmedreceipts);
	$page_templater->register('confirmed_pagenav', $confirmed_pagenav);
	$page_templater->register('unconfirmedreceipts', $unconfirmedreceipts);
	$page_templater->register('unconfirmed_pagenav', $unconfirmed_pagenav);
}

// ############################### start move pms ###############################
if ($_POST['do'] == 'movepm')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'folderid'   => TYPE_INT,
		'messageids' => TYPE_STR,
	));

	$vbulletin->GPC['messageids'] = @unserialize(verify_client_string($vbulletin->GPC['messageids']));

	if (!is_array($vbulletin->GPC['messageids']) OR empty($vbulletin->GPC['messageids']))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['private_message'], $vbulletin->options['contactuslink'])));
	}

	$pmids = array();
	foreach ($vbulletin->GPC['messageids'] AS $pmid)
	{
		$id = intval($pmid);
		$pmids["$id"] = $id;
	}

	($hook = vBulletinHook::fetch_hook('private_movepm')) ? eval($hook) : false;

	$db->query_write("UPDATE " . TABLE_PREFIX . "pm SET folderid=" . $vbulletin->GPC['folderid'] . " WHERE userid=" . $vbulletin->userinfo['userid'] . " AND folderid<>-1 AND pmid IN(" . implode(', ', $pmids) . ")");
	$vbulletin->url = 'private.php?' . $vbulletin->session->vars['sessionurl'] . 'folderid=' . $vbulletin->GPC['folderid'];

	// deselect messages
	setcookie('vbulletin_inlinepm', '', TIMENOW - 3600, '/');

	print_standard_redirect('pm_messagesmoved');
}

// ############################### start pm manager ###############################
// actions for moving pms between folders, and deleting pms
if ($_POST['do'] == 'managepm')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'folderid' => TYPE_INT,
		'dowhat'   => TYPE_NOHTML,
		'pm'       => TYPE_ARRAY_UINT,
	));

	// get selected via post
	$messageids = array();
	foreach (array_keys($vbulletin->GPC['pm']) AS $pmid)
	{
		$pmid = intval($pmid);
		$messageids["$pmid"] = $pmid;
	}
	unset($pmid);

	// get cookie
	$vbulletin->input->clean_array_gpc('c', array(
		'vbulletin_inlinepm' => TYPE_STR,
	));

	if ($vbulletin->GPC['dowhat'] != 'deleteonepm')
	{
		// get selected via cookie
		if (!empty($vbulletin->GPC['vbulletin_inlinepm']))
		{
			$cookielist = explode('-', $vbulletin->GPC['vbulletin_inlinepm']);
			$cookielist = $vbulletin->input->clean($cookielist, TYPE_ARRAY_UINT);

			$messageids = array_unique(array_merge($messageids, $cookielist));
		}
		$clearcookie = true;
	}
	else
	{
		$vbulletin->GPC['dowhat'] = 'delete';
		$singlepmid = intval(array_pop(array_keys($vbulletin->GPC['pm'])));
		$clearcookie = false;
	}

	// check that we have an array to work with
	if (empty($messageids))
	{
		eval(standard_error(fetch_error('no_private_messages_selected')));
	}

	($hook = vBulletinHook::fetch_hook('private_managepm_start')) ? eval($hook) : false;

	// now switch the $dowhat...
	switch($vbulletin->GPC['dowhat'])
	{
		// *****************************
		// deselect all messages
		case 'clear':
			setcookie('vbulletin_inlinepm', '', TIMENOW - 3600, '/');
			print_standard_redirect('pm_allmessagesdeselected');
		break;

		// *****************************
		// move messages to a new folder
		case 'move':
			$totalmessages = sizeof($messageids);
			$messageids = sign_client_string(serialize($messageids));
			$folderoptions = construct_folder_jump(0, 0, array($vbulletin->GPC['folderid'], -1));

			switch ($vbulletin->GPC['folderid'])
			{
				case -1:
					$fromfolder = $vbphrase['sent_items'];
					break;
				case 0:
					$fromfolder = $vbphrase['inbox'];
					break;
				default:
				{
					$folders = unserialize($vbulletin->userinfo['pmfolders']);
					$fromfolder = $folders["{$vbulletin->GPC['folderid']}"];
				}
			}

			($hook = vBulletinHook::fetch_hook('private_managepm_move')) ? eval($hook) : false;

			if ($folderoptions)
			{
				$page_templater = vB_Template::create('pm_movepm');
				$page_templater->register('folderoptions', $folderoptions);
				$page_templater->register('fromfolder', $fromfolder);
				$page_templater->register('messageids', $messageids);
				$page_templater->register('totalmessages', $totalmessages);
			}
			else
			{
				eval(standard_error(fetch_error('pm_nofolders', $vbulletin->options['bburl'], $vbulletin->session->vars['sessionurl'])));
			}
		break;

		// *****************************
		// mark messages as unread
		case 'unread':
			$db->query_write("UPDATE " . TABLE_PREFIX . "pm SET messageread=0 WHERE userid=" . $vbulletin->userinfo['userid'] . " AND pmid IN (" . implode(', ', $messageids) . ")");
			build_pm_counters();
			$readunread = $vbphrase['unread_date'];

			($hook = vBulletinHook::fetch_hook('private_managepm_unread')) ? eval($hook) : false;

			// deselect messages
			setcookie('vbulletin_inlinepm', '', TIMENOW - 3600, '/');

			print_standard_redirect(array('pm_messagesmarkedas',$readunread));
		break;

		// *****************************
		// mark messages as read
		case 'read':
			$db->query_write("UPDATE " . TABLE_PREFIX . "pm SET messageread=1 WHERE messageread=0 AND userid=" . $vbulletin->userinfo['userid'] . " AND pmid IN (" . implode(', ', $messageids) . ")");
			build_pm_counters();
			$readunread = $vbphrase['read'];

			($hook = vBulletinHook::fetch_hook('private_managepm_read')) ? eval($hook) : false;

			// deselect messages
			setcookie('vbulletin_inlinepm', '', TIMENOW - 3600, '/');

			print_standard_redirect(array('pm_messagesmarkedas',$readunread));
		break;

		// *****************************
		// download as XML
		case 'xml':
			$_REQUEST['do'] = 'downloadpm';
		break;

		// *****************************
		// download as CSV
		case 'csv':
			$_REQUEST['do'] = 'downloadpm';
		break;

		// *****************************
		// download as TEXT
		case 'txt':
			$_REQUEST['do'] = 'downloadpm';
		break;

		// *****************************
		// delete messages completely
		case 'delete':
			$pmids = array();
			$textids = array();

			// get the pmid and pmtext id of messages to be deleted
			$pms = $db->query_read_slave("
				SELECT pmid
				FROM " . TABLE_PREFIX . "pm
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND pmid IN(" . implode(', ', $messageids) . ")
			");

			// check to see that we still have some ids to work with
			if ($db->num_rows($pms) == 0)
			{
				eval(standard_error(fetch_error('invalidid', $vbphrase['private_message'], $vbulletin->options['contactuslink'])));
			}

			// build the final array of pmids to work with
			while ($pm = $db->fetch_array($pms))
			{
				$pmids[] = $pm['pmid'];
			}

			// delete from the pm table using the results from above
			$deletePmSql = "DELETE FROM " . TABLE_PREFIX . "pm WHERE pmid IN(" . implode(', ', $pmids) . ")";
			$db->query_write($deletePmSql);

			// deselect messages
			if ($clearcookie)
			{
				setcookie('vbulletin_inlinepm', '', TIMENOW - 3600, '/');
			}
			else
			{
				$cookielist = explode('-', $vbulletin->GPC['vbulletin_inlinepm']);
				$cookielist = $vbulletin->input->clean($cookielist, TYPE_ARRAY_UINT);
				$pmids = array();

				foreach ($cookielist AS $pmid)
				{
					if ($pmid == $singlepmid)
					{
						continue;
					}
					$pmids[] = $pmid;
				}
				if ($pmids)
				{
					setcookie('vbulletin_inlinepm', implode('-', $pmids), TIMENOW + 3600, '/');
				}
				else
				{
					setcookie('vbulletin_inlinepm', '', TIMENOW - 3600, '/');
				}
			}

			build_pm_counters();

			($hook = vBulletinHook::fetch_hook('private_managepm_delete')) ? eval($hook) : false;

			// all done, redirect...
			$vbulletin->url = 'private.php?' . $vbulletin->session->vars['sessionurl'] . 'folderid=' . $vbulletin->GPC['folderid'];
			print_standard_redirect('pm_messagesdeleted');
		break;

		// *****************************
		// unknown action specified
		default:
			$handled_do = false;
			($hook = vBulletinHook::fetch_hook('private_managepm_action_switch')) ? eval($hook) : false;
			if (!$handled_do)
			{
				eval(standard_error(fetch_error('invalidid', $vbphrase['action'], $vbulletin->options['contactuslink'])));
			}
		break;
	}
}

// ############################### start download pm ###############################
// downloads selected private messages to a file type of user's choice
if ($_REQUEST['do'] == 'downloadpm')
{
	if (($current_memory_limit = ini_size_to_bytes(@ini_get('memory_limit'))) < 128 * 1024 * 1024 AND $current_memory_limit > 0)
	{
		@ini_set('memory_limit', 128 * 1024 * 1024);
	}

	$vbulletin->input->clean_gpc('r', 'dowhat', TYPE_NOHTML);

	require_once(DIR . '/includes/functions_file.php');

	function fetch_touser_string($pm)
	{
		global $vbulletin;

		$cclist = array();
		$bcclist = array();
		$ccrecipients = '';
		$touser = unserialize($pm['touser']);

		foreach($touser AS $key => $item)
		{
			if (is_array($item))
			{
				foreach($item AS $subkey => $subitem)
				{
					$username = $subitem;
					$userid = $subkey;
					if ($key == 'bcc')
					{
						$bcclist[] = $username;
					}
					else
					{
						$cclist[] = $username;
					}
				}
			}
			else
			{
				$username = $item;
				$userid = $key;
				$cclist[] = $username;
			}
		}

		if (!empty($cclist))
		{
			$ccrecipients = implode("\r\n", $cclist);
		}

		if ($pm['folder'] == -1)
		{
			if (!empty($bcclist))
			{
				$ccrecipients = implode("\r\n", array_unique(array_merge($cclist, $bcclist)));
			}
		}
		else
		{
			$ccrecipients = implode("\r\n", array_unique(array_merge($cclist, array("{$vbulletin->userinfo['username']}"))));
		}

		return $ccrecipients;
	}

	// set sql condition for selected messages
	if (is_array($messageids))
	{
		$sql = 'AND pm.pmid IN(' . implode(', ', $messageids) . ')';
	}
	// set blank sql condition (get all user's messages)
	else
	{
		$sql = '';
	}

	// query the specified messages
	$pms = $db->query_read_slave("
		SELECT dateline AS datestamp, folderid AS folder, title, fromusername AS fromuser, fromuserid, touserarray AS touser, message
		FROM " . TABLE_PREFIX . "pm AS pm
		LEFT JOIN " . TABLE_PREFIX . "pmtext AS pmtext ON(pmtext.pmtextid = pm.pmtextid)
		WHERE pm.userid = " . $vbulletin->userinfo['userid'] . " $sql
		ORDER BY folderid, dateline
	");

	// check to see that we have some messages to work with
	if (!$db->num_rows($pms))
	{
		eval(standard_error(fetch_error('no_pm_to_download')));
	}

	// get folder names the easy way...
	construct_folder_jump();

	($hook = vBulletinHook::fetch_hook('private_downloadpm_start')) ? eval($hook) : false;

	// do the business...
	switch ($vbulletin->GPC['dowhat'])
	{
		// *****************************
		// download as XML
		case 'xml':
			$pmfolders = array();

			while ($pm = $db->fetch_array($pms))
			{
				$pmfolders["$pm[folder]"][] = $pm;
			}
			unset($pm);
			$db->free_result($pms);

			require_once(DIR . '/includes/class_xml.php');
			$xml = new vB_XML_Builder($vbulletin);

			$xml->add_group('privatemessages');

			foreach ($pmfolders AS $folder => $messages)
			{
				$foldername =& $foldernames["$folder"];
				$xml->add_group('folder', array('name' => $foldername));
				foreach ($messages AS $pm)
				{
					$pm['datestamp'] = vbdate('Y-m-d H:i', $pm['datestamp'], false, false);
					$pm['touser'] = fetch_touser_string($pm);
					$pm['folder'] = $foldernames["$pm[folder]"];
					$pm['message'] = preg_replace("/(\r\n|\r|\n)/s", "\r\n", $pm['message']);
					$pm['message'] = fetch_censored_text($pm['message']);
					unset($pm['folder']);

					($hook = vBulletinHook::fetch_hook('private_downloadpm_bit')) ? eval($hook) : false;

					$xml->add_group('privatemessage');
					foreach ($pm AS $key => $val)
					{
						$xml->add_tag($key, $val);
					}
					$xml->close_group();
				}
				$xml->close_group();
			}

			$xml->close_group();

			$doc = "<?xml version=\"1.0\" encoding=\"" . vB_Template_Runtime::fetchStyleVar('charset') . "\"?>\r\n\r\n";
			$doc .= "<!-- " . $vbulletin->options['bbtitle'] . ';' . $vbulletin->options['bburl'] . " -->\r\n";
			// replace --/---/... with underscores for valid XML comments
			$doc .= '<!-- ' . construct_phrase($vbphrase['private_message_dump_for_user_x_y'], preg_replace('#(-(?=-)|(?<=-)-)#', '_', $vbulletin->userinfo['username']), vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'], TIMENOW)) . " -->\r\n\r\n";

			$doc .= $xml->output();
			$xml = null;

			// download the file
			file_download($doc, str_replace(array('\\', '/'), '-', "$vbphrase[dump_privatemessages]-" . $vbulletin->userinfo['username'] . "-" . vbdate($vbulletin->options['dateformat'], TIMENOW) . '.xml'), 'text/xml');
		break;

		// *****************************
		// download as CSV
		case 'csv':
			// column headers
			$csv = "$vbphrase[date],$vbphrase[folder],$vbphrase[title],$vbphrase[dump_from],$vbphrase[dump_to],$vbphrase[message]\r\n";

			while ($pm = $db->fetch_array($pms))
			{
				$csvpm = array();
				$csvpm['datestamp'] = vbdate('Y-m-d H:i', $pm['datestamp'], false, false);
				$csvpm['folder'] = $foldernames["$pm[folder]"];
				$csvpm['title'] = unhtmlspecialchars($pm['title']);
				$csvpm['fromuser'] = $pm['fromuser'];
				$csvpm['touser'] = fetch_touser_string($pm);
				$csvpm['message'] = preg_replace("/(\r\n|\r|\n)/s", "\r\n", $pm['message']);
				$csvpm['message'] = fetch_censored_text($pm['message']);

				($hook = vBulletinHook::fetch_hook('private_downloadpm_bit')) ? eval($hook) : false;

				// make values safe
				foreach ($csvpm AS $key => $val)
				{
					$csvpm["$key"] = '"' . str_replace('"', '""', $val) . '"';
				}
				// output the message row
				$csv .= implode(',', $csvpm) . "\r\n";
			}
			unset($pm, $csvpm);
			$db->free_result($pms);

			// download the file
			file_download($csv, str_replace(array('\\', '/'), '-', "$vbphrase[dump_privatemessages]-" . $vbulletin->userinfo['username'] . "-" . vbdate($vbulletin->options['dateformat'], TIMENOW) . '.csv'), 'text/x-csv');
		break;

		// *****************************
		// download as TEXT
		case 'txt':
			$pmfolders = array();

			while ($pm = $db->fetch_array($pms))
			{
				$pmfolders["$pm[folder]"][] = $pm;
			}
			unset($pm);
			$db->free_result($pms);

			$txt = $vbulletin->options['bbtitle'] . ';' . $vbulletin->options['bburl'] . "\r\n";
			$txt .= construct_phrase($vbphrase['private_message_dump_for_user_x_y'], $vbulletin->userinfo['username'], vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'], TIMENOW)) . " -->\r\n\r\n";

			foreach ($pmfolders AS $folder => $messages)
			{
				$foldername =& $foldernames["$folder"];
				$txt .= "################################################################################\r\n";
				$txt .= "$vbphrase[folder] :\t$foldername\r\n";
				$txt .= "################################################################################\r\n\r\n";

				foreach ($messages AS $pm)
				{
					// turn all single \n into \r\n
					$pm['message'] = preg_replace("/(\r\n|\r|\n)/s", "\r\n", $pm['message']);
					$pm['message'] = fetch_censored_text($pm['message']);

					($hook = vBulletinHook::fetch_hook('private_downloadpm_bit')) ? eval($hook) : false;

					$txt .= "================================================================================\r\n";
					$txt .= "$vbphrase[dump_from] :\t$pm[fromuser]\r\n";
					$txt .= "$vbphrase[dump_to] :\t" . fetch_touser_string($pm) . "\r\n";
					$txt .= "$vbphrase[date] :\t" . vbdate('Y-m-d H:i', $pm['datestamp'], false, false) . "\r\n";
					$txt .= "$vbphrase[title] :\t" . unhtmlspecialchars($pm['title']) . "\r\n";
					$txt .= "--------------------------------------------------------------------------------\r\n";
					$txt .= "$pm[message]\r\n\r\n";
				}
			}

			// download the file
			file_download($txt, str_replace(array('\\', '/'), '-', "$vbphrase[dump_privatemessages]-" . $vbulletin->userinfo['username'] . "-" . vbdate($vbulletin->options['dateformat'], TIMENOW) . '.txt'), 'text/plain');
		break;

		// *****************************
		// unknown download format
		default:
			eval(standard_error(fetch_error('invalidid', $vbphrase['file_type'], $vbulletin->options['contactuslink'])));
		break;
	}
}

// ############################### start insert pm ###############################
// either insert a pm into the database, or process the preview and fall back to newpm
if ($_POST['do'] == 'insertpm')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'wysiwyg'        => TYPE_BOOL,
		'title'          => TYPE_NOHTML,
		'message'        => TYPE_STR,
		'parseurl'       => TYPE_BOOL,
		'savecopy'       => TYPE_BOOL,
		'signature'      => TYPE_BOOL,
		'disablesmilies' => TYPE_BOOL,
		'receipt'        => TYPE_BOOL,
		'preview'        => TYPE_STR,
		'recipients'     => TYPE_STR,
		'bccrecipients'  => TYPE_STR,
		'iconid'         => TYPE_UINT,
		'forward'        => TYPE_BOOL,
		'folderid'       => TYPE_INT,
		'sendanyway'     => TYPE_BOOL,
	));

	if ($permissions['pmquota'] < 1)
	{
		print_no_permission();
	}
	else if (!$vbulletin->userinfo['receivepm'])
	{
		eval(standard_error(fetch_error('pm_turnedoff')));
	}

	if (fetch_privatemessage_throttle_reached($vbulletin->userinfo['userid']))
	{
		eval(standard_error(fetch_error('pm_throttle_reached', $vbulletin->userinfo['permissions']['pmthrottlequantity'], $vbulletin->options['pmthrottleperiod'])));
	}

	// include useful functions
	require_once(DIR . '/includes/functions_newpost.php');

	// unwysiwygify the incoming data
	if ($vbulletin->GPC['wysiwyg'])
	{
		require_once(DIR . '/includes/class_wysiwygparser.php');
		$html_parser = new vB_WysiwygHtmlParser($vbulletin);
		$vbulletin->GPC['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $vbulletin->options['privallowhtml']);
	}

	// parse URLs in message text
	if ($vbulletin->options['privallowbbcode'] AND $vbulletin->GPC['parseurl'])
	{
		$vbulletin->GPC['message'] = convert_url_to_bbcode($vbulletin->GPC['message']);
	}

	$pm['message'] =& $vbulletin->GPC['message'];
	$pm['title'] =& $vbulletin->GPC['title'];
	$pm['parseurl'] =& $vbulletin->GPC['parseurl'];
	$pm['savecopy'] =& $vbulletin->GPC['savecopy'];
	$pm['signature'] =& $vbulletin->GPC['signature'];
	$pm['disablesmilies'] =& $vbulletin->GPC['disablesmilies'];
	$pm['sendanyway'] =& $vbulletin->GPC['sendanyway'];
	$pm['receipt'] =& $vbulletin->GPC['receipt'];
	$pm['recipients'] =& $vbulletin->GPC['recipients'];
	$pm['bccrecipients'] =& $vbulletin->GPC['bccrecipients'];
	$pm['pmid'] =& $vbulletin->GPC['pmid'];
	$pm['iconid'] =& $vbulletin->GPC['iconid'];
	$pm['forward'] =& $vbulletin->GPC['forward'];
	$pm['folderid'] =& $vbulletin->GPC['folderid'];

	// *************************************************************
	// PROCESS THE MESSAGE AND INSERT IT INTO THE DATABASE

	$errors = array(); // catches errors

	if ($vbulletin->userinfo['pmtotal'] > $permissions['pmquota'] OR ($vbulletin->userinfo['pmtotal'] == $permissions['pmquota'] AND $pm['savecopy']))
	{
		$errors[] = fetch_error('yourpmquotaexceeded');
	}

	// create the DM to do error checking and insert the new PM
	$pmdm =& datamanager_init('PM', $vbulletin, ERRTYPE_ARRAY);

	$pmdm->set_info('savecopy',      $pm['savecopy']);
	$pmdm->set_info('receipt',       $pm['receipt']);
	$pmdm->set_info('cantrackpm',    $cantrackpm);
	$pmdm->set_info('forward',       $pm['forward']);
	$pmdm->set_info('bccrecipients', $pm['bccrecipients']);
	if ($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
	{
		$pmdm->overridequota = true;
	}

	$pmdm->set('fromuserid', $vbulletin->userinfo['userid']);
	$pmdm->set('fromusername', $vbulletin->userinfo['username']);
	$pmdm->setr('title', $pm['title']);
	$pmdm->set_recipients($pm['recipients'], $permissions, 'cc');
	$pmdm->set_recipients($pm['bccrecipients'], $permissions, 'bcc');
	$pmdm->setr('message', $pm['message']);
	$pmdm->setr('iconid', $pm['iconid']);
	$pmdm->set('dateline', TIMENOW);
	$pmdm->setr('showsignature', $pm['signature']);
	$pmdm->set('allowsmilie', $pm['disablesmilies'] ? 0 : 1);
	if (!$pm['forward'])
	{
		$pmdm->set_info('parentpmid', $pm['pmid']);
	}
	$pmdm->set_info('replypmid', $pm['pmid']);

	($hook = vBulletinHook::fetch_hook('private_insertpm_process')) ? eval($hook) : false;

	$pmdm->pre_save();

	// deal with user using receivepmbuddies sending to non-buddies
	if ($vbulletin->userinfo['receivepmbuddies'] AND is_array($pmdm->info['recipients']))
	{
		$users_not_on_list = array();

		// get a list of super mod groups
		$smod_groups = array();
		foreach ($vbulletin->usergroupcache AS $ugid => $groupinfo)
		{
			if ($groupinfo['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['ismoderator'])
			{
				// super mod group
				$smod_groups[] = $ugid;
			}
		}

		// now filter out all moderators (and super mods) from the list of recipients
		// to check against the buddy list
		$check_recipients = $pmdm->info['recipients'];
		$mods = $db->query_read_slave("
			SELECT user.userid
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "moderator AS moderator ON (moderator.userid = user.userid)
			WHERE user.userid IN (" . implode(',', array_keys($check_recipients)) . ")
				AND ((moderator.userid IS NOT NULL AND moderator.forumid <> -1)
				" . (!empty($smod_groups) ? "OR user.usergroupid IN (" . implode(',', $smod_groups) . ")" : '') . "
				)
		");
		while ($mod = $db->fetch_array($mods))
		{
			unset($check_recipients["$mod[userid]"]);
		}

		if (!empty($check_recipients))
		{
			// filter those on our buddy list out
			$users = $db->query_read_slave("
				SELECT userlist.relationid
				FROM " . TABLE_PREFIX . "userlist AS userlist
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND userlist.relationid IN(" . implode(array_keys($check_recipients), ',') . ")
					AND type = 'buddy'
			");
			while ($user = $db->fetch_array($users))
			{
				unset($check_recipients["$user[relationid]"]);
			}
		}

		// what's left must be those who are neither mods or on our buddy list
		foreach ($check_recipients AS $userid => $user)
		{
				$users_not_on_list["$userid"] = $user['username'];
		}

		if (!empty($users_not_on_list) AND (!$vbulletin->GPC['sendanyway'] OR !empty($errors)))
		{
			$users = '';
			foreach ($users_not_on_list AS $userid => $username)
			{
				$users .= "<li><a href=\"" . fetch_seo_url('member', array('userid' => $userid, 'username' => $username)) . "\" target=\"profile\">$username</a></li>";
			}
			$pmdm->error('pm_non_contacts_cant_reply', $users, $vbulletin->input->fetch_relpath());
		}
	}

	// check for message flooding
	if ($vbulletin->options['pmfloodtime'] > 0 AND !$vbulletin->GPC['preview'])
	{
		if (!($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) AND !can_moderate())
		{
			$floodcheck = $db->query_first("
				SELECT MAX(dateline) AS dateline
				FROM " . TABLE_PREFIX . "pmtext AS pmtext
				WHERE fromuserid = " . $vbulletin->userinfo['userid'] . "
			");

			if (($timepassed = TIMENOW - $floodcheck['dateline']) < $vbulletin->options['pmfloodtime'])
			{
				$errors[] = fetch_error('pmfloodcheck', $vbulletin->options['pmfloodtime'], ($vbulletin->options['pmfloodtime'] - $timepassed));
			}
		}
	}

	// process errors if there are any
	$errors = array_merge($errors, $pmdm->errors);

	if (!empty($errors))
	{
		define('PMPREVIEW', 1);
		$preview = construct_errors($errors); // this will take the preview's place
		$_REQUEST['do'] = 'newpm';
	}
	else if ($vbulletin->GPC['preview'] != '')
	{
		define('PMPREVIEW', 1);
		$foruminfo = array(
			'forumid' => 'privatemessage',
			'allowicons' => $vbulletin->options['privallowicons']
		);
		$preview = process_post_preview($pm);
		$_REQUEST['do'] = 'newpm';
	}
	else
	{
		// everything's good!
		$pmdm->save();

		clear_autosave_text('vBForum_PrivateMessage', 0, $pm['pmid'], $vbulletin->userinfo['userid']);

		// force pm counters to be rebuilt
		$vbulletin->userinfo['pmunread'] = -1;
		build_pm_counters();

		($hook = vBulletinHook::fetch_hook('private_insertpm_complete')) ? eval($hook) : false;

		$vbulletin->url = 'private.php' . $vbulletin->session->vars['sessionurl_q'];
		print_standard_redirect('pm_messagesent');
	}
}

// ############################### start new pm ###############################
// form for creating a new private message
if ($_REQUEST['do'] == 'newpm')
{
	if ($permissions['pmquota'] < 1)
	{
		print_no_permission();
	}
	else if (!$vbulletin->userinfo['receivepm'])
	{
		eval(standard_error(fetch_error('pm_turnedoff')));
	}

	if (fetch_privatemessage_throttle_reached($vbulletin->userinfo['userid']))
	{
		eval(standard_error(fetch_error('pm_throttle_reached', $vbulletin->userinfo['permissions']['pmthrottlequantity'], $vbulletin->options['pmthrottleperiod'])));
	}

	require_once(DIR . '/includes/functions_newpost.php');

	($hook = vBulletinHook::fetch_hook('private_newpm_start')) ? eval($hook) : false;

	// do initial checkboxes
	$checked = array();
	$signaturechecked = iif($vbulletin->userinfo['signature'] != '', 'checked="checked"');
	$checked['savecopy'] = $vbulletin->userinfo['pmdefaultsavecopy'];
	$show['receivepmbuddies'] = $vbulletin->userinfo['receivepmbuddies'];

	// setup for preview display
	if (defined('PMPREVIEW'))
	{
		$postpreview =& $preview;
		$pm['recipients'] =& htmlspecialchars_uni($pm['recipients']);
		if (!empty($pm['bccrecipients']))
		{
			$pm['bccrecipients'] =& htmlspecialchars_uni($pm['bccrecipients']);
		}
		else
		{
			$show['bcclink'] = true;
		}
		$pm['message'] = htmlspecialchars_uni($pm['message']);
		construct_checkboxes($pm);
	}
	else
	{
		$vbulletin->input->clean_array_gpc('r', array(
			'stripquote' => TYPE_BOOL,
			'forward'    => TYPE_BOOL,
			'userid'     => TYPE_NOCLEAN,
		));

		// set up for PM reply / forward
		if ($vbulletin->GPC['pmid'])
		{
			if($pm = $vbulletin->db->query_first_slave("
				SELECT pm.*, pmtext.*
				FROM " . TABLE_PREFIX . "pm AS pm
				LEFT JOIN " . TABLE_PREFIX . "pmtext AS pmtext ON(pmtext.pmtextid = pm.pmtextid)
				WHERE pm.userid=" . $vbulletin->userinfo['userid'] . " AND pm.pmid=" . $vbulletin->GPC['pmid'] . "
			"))
			{
				$pm = fetch_privatemessage_reply($pm);
			}
		}
		else
		{
			//set up for standard new PM
			// insert username(s) of specified recipients
			if ($vbulletin->GPC['userid'])
			{
				$recipients = array();
				if (is_array($vbulletin->GPC['userid']))
				{
					foreach ($vbulletin->GPC['userid'] AS $recipient)
					{
						$recipients[] = intval($recipient);
					}
				}
				else
				{
					$recipients[] = intval($vbulletin->GPC['userid']);
				}
				$users = $db->query_read_slave("
					SELECT usertextfield.*, user.*, userlist.type
					FROM " . TABLE_PREFIX . "user AS user
					LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid=user.userid)
					LEFT JOIN " . TABLE_PREFIX . "userlist AS userlist ON(user.userid = userlist.userid AND userlist.relationid = " . $vbulletin->userinfo['userid'] . " AND userlist.type = 'buddy')
					WHERE user.userid IN(" . implode(', ', $recipients) . ")
				");
				$recipients = array();
				while ($user = $db->fetch_array($users))
				{
					$user = array_merge($user , convert_bits_to_array($user['options'] , $vbulletin->bf_misc_useroptions));
					cache_permissions($user, false);
					if (!($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) AND (!$user['receivepm'] OR !$user['permissions']['pmquota']
	 							OR ($user['receivepmbuddies'] AND !can_moderate() AND $user['type'] != 'buddy')
	 				))
	 				{
						eval(standard_error(fetch_error('pmrecipturnedoff', $user['username'])));
					}

					$recipients[] = $user['username'];
				}
				if (empty($recipients))
				{
					$pm['recipients'] = '';
				}
				else
				{
					$pm['recipients'] = implode(' ; ', $recipients);
				}
			}

			($hook = vBulletinHook::fetch_hook('private_newpm_blank')) ? eval($hook) : false;
		}

		construct_checkboxes(array(
			'savecopy' => $vbulletin->userinfo['pmdefaultsavecopy'],
			'parseurl' => true,
			'signature' => iif($vbulletin->userinfo['signature'] !== '', true)
		));

		$show['bcclink'] = true;
	}

	$folderjump = construct_folder_jump(0, $pm['folderid']);

	$posticons = construct_icons($pm['iconid'], $vbulletin->options['privallowicons']);

	require_once(DIR . '/includes/functions_editor.php');

	$editorid = construct_edit_toolbar(
		$pm['message'],
		0,
		'privatemessage',
		$vbulletin->options['privallowsmilies'] ?  1 : 0,
		true,
		false,
		'fe',
		'',
		array(),
		'forum',
		'vBForum_PrivateMessage',
		0,
		0,
		defined('PMPREVIEW'),
		true,
		'title'
	);

	// generate navbar
	if ($pm['pmid'])
	{
		$navbits['private.php?' . $vbulletin->session->vars['sessionurl'] . "folderid=$pm[folderid]"] = $foldernames["$pm[folderid]"];
		$navbits['private.php?' . $vbulletin->session->vars['sessionurl'] . "do=showpm&amp;pmid=$pm[pmid]"] = $pm['title'];
		$navbits[''] = iif($pm['forward'], $vbphrase['forward_message'], $vbphrase['reply_to_private_message']);
	}
	else
	{
		$navbits[''] = $vbphrase['post_new_private_message'];
	}

	$show['sendmax'] = iif($permissions['pmsendmax'], true, false);
	$show['sendmultiple'] = ($permissions['pmsendmax'] != 1);
	$show['parseurl'] = $vbulletin->options['privallowbbcode'];
	$show['signaturecheckbox'] = ($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature'] AND $vbulletin->userinfo['signature']);

	// build forum rules
	$bbcodeon = ($vbulletin->options['privallowbbcode'] ? $vbphrase['on'] : $vbphrase['off']);
	$imgcodeon = ($vbulletin->options['privallowbbimagecode'] ? $vbphrase['on'] : $vbphrase['off']);
	$videocodeon = ($vbulletin->options['privallowbbvideocode'] ? $vbphrase['on'] : $vbphrase['off']);
	$htmlcodeon = ($vbulletin->options['privallowhtml'] ? $vbphrase['on'] : $vbphrase['off']);
	$smilieson = ($vbulletin->options['privallowsmilies'] ? $vbphrase['on'] : $vbphrase['off']);

	// only show posting code allowances in forum rules template
	$show['codeonly'] = true;

	$templater = vB_Template::create('forumrules');
		$templater->register('bbcodeon', $bbcodeon);
		$templater->register('can', $can);
		$templater->register('htmlcodeon', $htmlcodeon);
		$templater->register('imgcodeon', $imgcodeon);
		$templater->register('videocodeon', $videocodeon);
		$templater->register('smilieson', $smilieson);
	$forumrules = $templater->render();

	$page_templater = vB_Template::create('pm_newpm');
	$page_templater->register('anywaychecked', $anywaychecked);
	$page_templater->register('checked', $checked);
	$page_templater->register('disablesmiliesoption', $disablesmiliesoption);
	$page_templater->register('editorid', $editorid);
	$page_templater->register('forumrules', $forumrules);
	$page_templater->register('messagearea', $messagearea);
	$page_templater->register('permissions', $permissions);
	$page_templater->register('pm', $pm);
	$page_templater->register('posticons', $posticons);
	$page_templater->register('postpreview', $postpreview);
	$page_templater->register('selectedicon', $selectedicon);
}

// ############################### start show pm ###############################
// show a private message
if ($_REQUEST['do'] == 'showpm')
{
	require_once(DIR . '/includes/class_postbit.php');
	require_once(DIR . '/includes/functions_bigthree.php');

	$vbulletin->input->clean_array_gpc('r', array(
		'pmid'        => TYPE_UINT,
		'showhistory' => TYPE_BOOL
	));

	($hook = vBulletinHook::fetch_hook('private_showpm_start')) ? eval($hook) : false;

	$pm = $db->query_first_slave("
		SELECT
			pm.*, pmtext.*,
			" . iif($vbulletin->options['privallowicons'], "icon.title AS icontitle, icon.iconpath,") . "
			IF(ISNULL(pmreceipt.pmid), 0, 1) AS receipt, pmreceipt.readtime, pmreceipt.denied,
			sigpic.userid AS sigpic, sigpic.dateline AS sigpicdateline, sigpic.width AS sigpicwidth, sigpic.height AS sigpicheight
		FROM " . TABLE_PREFIX . "pm AS pm
		LEFT JOIN " . TABLE_PREFIX . "pmtext AS pmtext ON(pmtext.pmtextid = pm.pmtextid)
		" . iif($vbulletin->options['privallowicons'], "LEFT JOIN " . TABLE_PREFIX . "icon AS icon ON(icon.iconid = pmtext.iconid)") . "
		LEFT JOIN " . TABLE_PREFIX . "pmreceipt AS pmreceipt ON(pmreceipt.pmid = pm.pmid)
		LEFT JOIN " . TABLE_PREFIX . "sigpic AS sigpic ON(sigpic.userid = pmtext.fromuserid)
		WHERE pm.userid=" . $vbulletin->userinfo['userid'] . " AND pm.pmid=" . $vbulletin->GPC['pmid'] . "
	");

	if (!$pm)
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['private_message'], $vbulletin->options['contactuslink'])));
	}

	$folderjump = construct_folder_jump(0, $pm['folderid']);

	// do read receipt
	$show['receiptprompt'] = $show['receiptpopup'] = false;
	if ($pm['receipt'] == 1 AND $pm['readtime'] == 0 AND $pm['denied'] == 0)
	{
		if ($permissions['pmpermissions'] & $vbulletin->bf_ugp_pmpermissions['candenypmreceipts'])
		{
			echo 1;
			// set it to denied just now as some people might have ad blocking that stops the popup appearing
			$show['receiptprompt'] = $show['receiptpopup'] = true;
			$receipt_question_js = addslashes_js(construct_phrase($vbphrase['x_has_requested_a_read_receipt'], unhtmlspecialchars($pm['fromusername'])), '"');
			$db->shutdown_query("UPDATE " . TABLE_PREFIX . "pmreceipt SET denied = 1 WHERE pmid = $pm[pmid]");
		}
		else
		{
			// they can't deny pm receipts so do not show a popup or prompt
			$db->shutdown_query("UPDATE " . TABLE_PREFIX . "pmreceipt SET readtime = " . TIMENOW . " WHERE pmid = $pm[pmid]");
		}
	}
	else if ($pm['receipt'] == 1 AND $pm['denied'] == 1)
	{
		echo 2;
		$show['receiptprompt'] = true;
	}

	$postbit_factory = new vB_Postbit_Factory();
	$postbit_factory->registry =& $vbulletin;
	$postbit_factory->cache = array();
	$postbit_factory->bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	$postbit_obj =& $postbit_factory->fetch_postbit('pm');
	$pm_postbit = $pm;
	$postbit = $postbit_obj->construct_postbit($pm_postbit);

	// update message to show read
	if ($pm['messageread'] == 0)
	{
		$db->shutdown_query("UPDATE " . TABLE_PREFIX . "pm SET messageread=1 WHERE userid=" . $vbulletin->userinfo['userid'] . " AND pmid=$pm[pmid]");

		if ($pm['folderid'] >= 0)
		{
			$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
			$userdm->set_existing($vbulletin->userinfo);
			$userdm->set('pmunread', 'IF(pmunread >= 1, pmunread - 1, 0)', false);
			$userdm->save(true, true);
			unset($userdm);
		}
	}

	$cclist = array();
	$bcclist = array();
	$ccrecipients = array();
	$bccrecipients = array();
	$touser = unserialize($pm['touserarray']);

	if (!is_array($touser))
	{
		$touser = array();
	}

	foreach($touser AS $key => $item)
	{
		if (is_array($item))
		{
			foreach($item AS $subkey => $subitem)
			{
				$userinfo = array(
					'userid'   => $subkey,
					'username' => $subitem,
				);

				$userinfo['comma'] = $vbphrase['comma_space'];
				${$key . 'list'}[] = $userinfo;
			}
		}
		else
		{
			$userinfo = array(
				'username' => $item,
				'userid'   => $key,
			);

			$userinfo['comma'] = $vbphrase['comma_space'];
			$bcclist[] = $userinfo;
		}
	}

	// Last elements
	$countcc = sizeof($cclist);
	$countbcc = sizeof($bcclist);

	if ($countcc)
	{
		$cclist[$countcc-1]['comma'] = '';
	}

	if ($countbcc)
	{
		$bcclist[$countbcc-1]['comma'] = '';
	}

	if ($countcc > 1 OR (is_array($touser['cc']) AND !in_array($vbulletin->userinfo['username'], $touser['cc'])) OR ($vbulletin->userinfo['userid'] == $pm['fromuserid'] AND $pm['folderid'] == -1))
	{
		if ($countcc)
		{
			$ccrecipients = $cclist;
		}

		if ($countbcc AND $vbulletin->userinfo['userid'] == $pm['fromuserid'] AND $pm['folderid'] == -1)
		{
			if ($countcc)
			{
				$bccrecipients = $bcclist;
			}
			else
			{
				$ccrecipients = $bcclist;
			}
		}

		$show['recipients'] = true;
	}

	$show['quickreply'] = ($permissions['pmquota'] AND $vbulletin->userinfo['receivepm'] AND !fetch_privatemessage_throttle_reached($vbulletin->userinfo['userid']));

	if ($pm['fromuserid'])
 	{
		$recipient = $db->query_first("
			SELECT usertextfield.*, user.*, userlist.type
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid=user.userid)
			LEFT JOIN " . TABLE_PREFIX . "userlist AS userlist ON(user.userid = userlist.userid AND userlist.relationid = " . $vbulletin->userinfo['userid'] . " AND userlist.type = 'buddy')
			WHERE user.userid = " . intval($pm['fromuserid'])
		);
		if (!empty($recipient))
		{
			$recipient = array_merge($recipient , convert_bits_to_array($recipient['options'] , $vbulletin->bf_misc_useroptions));
			cache_permissions($recipient, false);
			if (!($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) AND (!$recipient['receivepm'] OR !$recipient['permissions']['pmquota']
						OR ($recipient['receivepmbuddies'] AND !can_moderate() AND $recipient['type'] != 'buddy')
			))
			{
				$show['quickreply'] = false;
			}
		}
		else
 		{
 			$show['quickreply'] = false;
 		}
	}
	else
	{
		$show['quickreply'] = false;
 	}

	if ($vbulletin->GPC['showhistory'] AND $pm['parentpmid'])
	{
		$threadresult = $vbulletin->db->query_read_slave("
			SELECT pm.*, pmtext.*
			FROM " . TABLE_PREFIX . "pm AS pm
			INNER JOIN " . TABLE_PREFIX . "pmtext AS pmtext ON(pmtext.pmtextid = pm.pmtextid)
			WHERE (pm.parentpmid=" . $pm['parentpmid'] . "
					OR pm.pmid = " . $pm['parentpmid'] . ")
			AND pm.pmid != " . $pm['pmid'] . "
			AND pm.userid=" . $vbulletin->userinfo['userid'] . "
			AND pmtext.dateline < " . $pm['dateline'] . "
			ORDER BY pmtext.dateline DESC
		");

		if ($vbulletin->db->num_rows($threadresult))
		{
			$threadpms = '';

			while ($threadpm = $vbulletin->db->fetch_array($threadresult))
			{
				$postbit_factory = new vB_Postbit_Factory();
				$postbit_factory->registry =& $vbulletin;
				$postbit_factory->cache = array();
				$postbit_factory->bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

				$postbit_obj =& $postbit_factory->fetch_postbit('pm');
				$threadpms .= $postbit_obj->construct_postbit($threadpm);
			}
		}
	}

	// generate navbar
	$navbits['private.php?' . $vbulletin->session->vars['sessionurl'] . "folderid=$pm[folderid]"] = $foldernames["{$pm['folderid']}"];
	$navbits[''] = $pm['title'];

	$pm['original_title'] = $pm['title'];

	if ($show['quickreply'])
	{
		// get pm info
		require_once(DIR . '/includes/functions_newpost.php');
		$pm = fetch_privatemessage_reply($pm);

		// create quick reply editor
		require_once(DIR . '/includes/functions_editor.php');

		$editorid = construct_edit_toolbar(
			$pm['message'],
			false,
			'privatemessage',
			$vbulletin->options['privallowsmilies'],
			true,
			false,
			'qr_pm',
			'',
			array(),
			'content',
			'vBForum_PrivateMessage',
			0,
			$pm['pmid']
		);

		$pm['savecopy'] = $vbulletin->userinfo['pmdefaultsavecopy'];
	}

	$includecss['postbit'] = 'postbit.css';
	$includeiecss['postbit'] = 'postbit-ie.css';

	$page_templater = vB_Template::create('pm_showpm');
	$page_templater->register('allowed_bbcode', $allowed_bbcode);
	$page_templater->register('bccrecipients', $bccrecipients);
	$page_templater->register('ccrecipients', $ccrecipients);
	$page_templater->register('editorid', $editorid);
	$page_templater->register('messagearea', $messagearea);
	$page_templater->register('pm', $pm);
	$page_templater->register('postbit', $postbit);
	$page_templater->register('receipt_question_js', $receipt_question_js);
	$page_templater->register('threadpms', $threadpms);
	$page_templater->register('vBeditTemplate', $vBeditTemplate);
}

// ############################# start pm message history #############################
if ($_REQUEST['do'] == 'showhistory')
{
	require_once(DIR . '/includes/class_postbit.php');
	require_once(DIR . '/includes/functions_bigthree.php');

	$vbulletin->input->clean_gpc('r', array(
		'pmid'        => TYPE_UINT
	));

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('response');

	if ($vbulletin->userinfo['userid'] AND $vbulletin->GPC['pmid'])
	{
		$pm = $db->query_first_slave("
			SELECT pm.parentpmid, pmtext.dateline
			FROM " . TABLE_PREFIX . "pm AS pm
			INNER JOIN " . TABLE_PREFIX . "pmtext AS pmtext ON(pmtext.pmtextid = pm.pmtextid)
			WHERE pm.userid=" . $vbulletin->userinfo['userid'] . " AND pm.pmid=" . $vbulletin->GPC['pmid'] . "
		");
	}

	if (empty($pm))
	{
		$xml->add_tag('error', 1);
	}
	else
	{
		$threadresult = $vbulletin->db->query_read_slave("
			SELECT pm.*, pmtext.*
			FROM " . TABLE_PREFIX . "pm AS pm
			INNER JOIN " . TABLE_PREFIX . "pmtext AS pmtext ON(pmtext.pmtextid = pm.pmtextid)
			WHERE (pm.parentpmid=" . $pm['parentpmid'] . "
					OR pm.pmid = " . $pm['parentpmid'] . ")
			AND pm.pmid != " . $vbulletin->GPC['pmid'] . "
			AND pm.userid=" . $vbulletin->userinfo['userid'] . "
			AND pmtext.dateline < " . intval($pm['dateline']) . "
			ORDER BY pmtext.dateline DESC
		");

		if ($vbulletin->db->num_rows($threadresult))
		{
			$threadpms = '';

			while ($threadpm = $vbulletin->db->fetch_array($threadresult))
			{
				$postbit_factory = new vB_Postbit_Factory();
				$postbit_factory->registry =& $vbulletin;
				$postbit_factory->cache = array();
				$postbit_factory->bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

				$postbit_obj =& $postbit_factory->fetch_postbit('pm');
				$threadpms .= $postbit_obj->construct_postbit($threadpm);
			}
		}
		else
		{
			$threadpms = vB_Template::create('pm_nomessagehistory')->render();
		}

		$xml->add_tag('html', process_replacement_vars($threadpms));
	}

	$xml->close_group();
	$xml->print_xml(true);
}

// ############################### start pm folder view ###############################
if ($_REQUEST['do'] == 'messagelist')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'folderid'    => TYPE_INT,
		'perpage'     => TYPE_UINT,
		'pagenumber'  => TYPE_UINT,
	));

	($hook = vBulletinHook::fetch_hook('private_messagelist_start')) ? eval($hook) : false;

	$folderid = $vbulletin->GPC['folderid'];

	$folderjump = construct_folder_jump(0, $vbulletin->GPC['folderid'], false, '', true);
	$foldername = $foldernames["{$vbulletin->GPC['folderid']}"]['name'];

	// count receipts
	$receipts = $db->query_first_slave("
		SELECT
			SUM(IF(readtime <> 0, 1, 0)) AS confirmed,
			SUM(IF(readtime = 0, 1, 0)) AS unconfirmed
		FROM " . TABLE_PREFIX . "pmreceipt
		WHERE userid = " . $vbulletin->userinfo['userid']
	);

	// get ignored users
	$ignoreusers = preg_split('#\s+#s', $vbulletin->userinfo['ignorelist'], -1, PREG_SPLIT_NO_EMPTY);

	$totalmessages = intval($messagecounters["{$vbulletin->GPC['folderid']}"]);

	// build pm counters bar, folder is 100 if we have no quota so red shows on the main bar
	$tdwidth = array();
	$tdwidth['folder'] = ($permissions['pmquota'] ? ceil($totalmessages / $permissions['pmquota'] * 100) : 100);
	$tdwidth['folder'] = min($tdwidth['folder'], 100);

	$totalWidth = (($permissions['pmquota'] && ($vbulletin->userinfo['pmtotal'] / $permissions['pmquota']) < 1) ?
	$vbulletin->userinfo['pmtotal'] / $permissions['pmquota'] : 1);

	$tdwidth['total'] = ($permissions['pmquota'] ? ceil($totalWidth * 100) - $tdwidth['folder'] : 0);
	$tdwidth['quota'] = 100 - $tdwidth['folder'] - $tdwidth['total'];

	$show['thisfoldertotal'] = iif($tdwidth['folder'], true, false);
	$show['allfolderstotal'] = iif($tdwidth['total'], true, false);
	$show['pmicons'] = iif($vbulletin->options['privallowicons'], true, false);

	// build navbar
	$navbits[''] = $foldernames["{$vbulletin->GPC['folderid']}"]['name'];

	if ($totalmessages == 0)
	{
		$show['messagelist'] = false;
	}
	else
	{
		$show['messagelist'] = true;

		$vbulletin->input->clean_array_gpc('r', array(
			'sort'        => TYPE_NOHTML,
		    'order'       => TYPE_NOHTML,
		    'searchtitle' => TYPE_NOHTML,
		    'searchuser'  => TYPE_NOHTML,
		    'startdate'   => TYPE_UNIXTIME,
			'enddate'     => TYPE_UNIXTIME,
			'searchread'  => TYPE_UINT
		));

		$search = array(
			'sort'       => (('sender' == $vbulletin->GPC['sort']) ? 'sender'
							 : (('title' == $vbulletin->GPC['sort']) ? 'title' : 'date')),
		    'order'      => (($vbulletin->GPC['order'] == 'asc') ? 'asc' : 'desc'),
		    'searchtitle'=> $vbulletin->GPC['searchtitle'],
		    'searchuser' => $vbulletin->GPC['searchuser'],
		    'startdate'  => $vbulletin->GPC['startdate'],
			'enddate'    => $vbulletin->GPC['enddate'],
			'read'       => $vbulletin->GPC['searchread']
		);

		// make enddate inclusive
		$search['enddate'] = ($search['enddate'] ? ($search['enddate'] + 86400) : 0);

		$show['openfilter'] = ($search['searchtitle'] OR $search['searchuser'] OR $search['startdate'] OR $search['enddate']);

		$sortfield = (('sender' == $search['sort']) ? 'pmtext.fromusername'
					  : (('title' == $search['sort'] ? 'pmtext.title' : 'pmtext.dateline')));
		$desc = ($search['order'] == 'desc');

		$hook_query_fields = $hook_query_joins = $hook_query_where = '';
		($hook = vBulletinHook::fetch_hook('private_messagelist_filter')) ? eval($hook) : false;

		// get a sensible value for $perpage
		sanitize_pageresults($totalmessages, $vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage'], $vbulletin->options['pmmaxperpage'], $vbulletin->options['pmperpage']);

		// work out the $startat value
		$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
		$perpage = $vbulletin->GPC['perpage'];
		$pagenumber = $vbulletin->GPC['pagenumber'];

		// array to store private messages in period groups
		$pm_period_groups = array();

		$need_sql_calc_rows = ($search['searchtitle'] OR $search['searchuser'] OR $search['startdate'] OR $search['enddate'] OR $search['read']);

		$readstatus = array(0 => '', 1 => '= 0', 2 => '> 0', 3 => '< 2', 4 => '= 2');
		$readstatus = ($search['read'] == 0 ? '' : 'AND pm.messageread ' . $readstatus[$search['read']]);

		// query private messages
		$pms = $db->query_read_slave("
			SELECT " . ($need_sql_calc_rows ? 'SQL_CALC_FOUND_ROWS' : '') . " pm.*, pmtext.*
				" . iif($vbulletin->options['privallowicons'], ", icon.title AS icontitle, icon.iconpath") . "
			$hook_query_fields
			FROM " . TABLE_PREFIX . "pm AS pm
			LEFT JOIN " . TABLE_PREFIX . "pmtext AS pmtext ON(pmtext.pmtextid = pm.pmtextid)
			" . iif($vbulletin->options['privallowicons'], "LEFT JOIN " . TABLE_PREFIX . "icon AS icon ON(icon.iconid = pmtext.iconid)") . "
			$hook_query_joins
			WHERE pm.userid=" . $vbulletin->userinfo['userid'] . " AND pm.folderid=" . $vbulletin->GPC['folderid'] .
			($search['searchtitle'] ? " AND pmtext.title LIKE '%" . $vbulletin->db->escape_string($search['searchtitle']) . "%'" : '') .
			($search['searchuser'] ? " AND pmtext.fromusername LIKE '%" . $vbulletin->db->escape_string($search['searchuser']) . "%'" : '') .
			($search['startdate'] ? " AND pmtext.dateline >= $search[startdate]" : '') .
			($search['enddate'] ? " AND pmtext.dateline <= $search[enddate]" : '') . "
			$readstatus
			$hook_query_where
			ORDER BY $sortfield " . ($desc ? 'DESC' : 'ASC') . "
			LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
		");

		if ($need_sql_calc_rows)
		{
			list($totalmessages) = $vbulletin->db->query_first_slave("SELECT FOUND_ROWS()", DBARRAY_NUM);
		}

		while ($pm = $db->fetch_array($pms))
		{
			if ('title' == $search['sort'])
			{
				$pm_period_groups[ fetch_char_group($pm['title']) ]["$pm[pmid]"] = $pm;
			}
			else if ('sender' == $search['sort'])
			{
				$pm_period_groups["$pm[fromusername]"]["$pm[pmid]"] = $pm;
			}
			else
			{
				$pm_period_groups[ fetch_period_group($pm['dateline']) ]["$pm[pmid]"] = $pm;
			}
		}
		$db->free_result($pms);

		// ensure other group is last
		if (isset($pm_period_groups['other']))
		{
			$pm_period_groups = ($desc)  ? array_merge($pm_period_groups, array('other' => $pm_period_groups['other']))
										 : array_merge(array('other' => $pm_period_groups['other']), $pm_period_groups);
		}

		// display returned messages
		$show['pmcheckbox'] = true;

		require_once(DIR . '/includes/functions_bigthree.php');

		foreach ($pm_period_groups AS $groupid => $pms)
		{
			if (('date' == $search['sort']) AND preg_match('#^(\d+)_([a-z]+)_ago$#i', $groupid, $matches))
			{
				$groupname = construct_phrase($vbphrase["x_$matches[2]_ago"], $matches[1]);
			}
			else if ('title' == $search['sort'] OR 'date' == $search['sort'])
			{
				if (('older' == $groupid) AND (sizeof($pm_period_groups) == 1))
				{
					$groupid = 'old_messages';
				}

				$groupname = $vbphrase["$groupid"];
			}
			else
			{
				$groupname = $groupid;
			}

			$groupid = $vbulletin->GPC['folderid'] . '_' . $groupid;
			$collapseobj_groupid =& $vbcollapse["collapseobj_pmf$groupid"];
			$collapseimg_groupid =& $vbcollapse["collapseimg_pmf$groupid"];

			$messagesingroup = sizeof($pms);
			$messagelistbits = '';

			foreach ($pms AS $pmid => $pm)
			{
				if (in_array($pm['fromuserid'], $ignoreusers))
				{
					// from user is on Ignore List
					$templater = vB_Template::create('pm_messagelistbit_ignore');
						$templater->register('groupid', $groupid);
						$templater->register('pm', $pm);
						$templater->register('pmid', $pmid);
					$messagelistbits .= $templater->render();
				}
				else
				{
					switch($pm['messageread'])
					{
						case 0: // unread
							$pm['statusicon'] = 'new';
						break;

						case 1: // read
							$pm['statusicon'] = 'old';
						break;

						case 2: // replied to
							$pm['statusicon'] = 'replied';
						break;

						case 3: // forwarded
							$pm['statusicon'] = 'forwarded';
						break;
					}

					$pm['senddate'] = vbdate($vbulletin->options['dateformat'], $pm['dateline']);
					$pm['sendtime'] = vbdate($vbulletin->options['timeformat'], $pm['dateline']);

					$clc = 0;
					$userbit = array();
					if ($vbulletin->GPC['folderid'] == -1)
					{
						$users = unserialize($pm['touserarray']);
						$touser = array();
						$tousers = array();
						if (!empty($users))
						{
							foreach ($users AS $key => $item)
							{
								if (is_array($item))
								{
									foreach($item AS $subkey => $subitem)
									{
										$touser["$subkey"] = $subitem;
									}
								}
								else
								{
									$touser["$key"] = $item;
								}
							}
							uasort($touser, 'strnatcasecmp');
						}

						foreach ($touser AS $userid => $username)
						{
							$userinfo = array(
								'userid'   => $userid,
								'username' => $username,
							);

							$clc++;
							if (!VB_API)
							{
								$userinfo['comma'] = $vbphrase['comma_space'];
								$userbit[$clc] = $userinfo;
							}
							else
							{	// VBIV-14029
								$userbit[]['userinfo'] = $userinfo;
							}
						}
					}
					else
					{
						$userinfo = array(
							'userid'   => $pm['fromuserid'],
							'username' => $pm['fromusername'],
						);

						$clc++;
						if (!VB_API)
						{
							$userbit[$clc] = $userinfo;
						}
						else
						{	// VBIV-13915
							$userbit[]['userinfo'] = $userinfo;
						}
					}

					if (!VB_API)
					{
						if ($clc)
						{
							$userbit[$clc]['comma'] = '';
						}
					}
					else
					{
						if ($clc == 1)
						{	// Only one username ? we only send userinfo
							$userbit['userinfo'] = $userbit[0]['userinfo'];
						}
					}

					$show['pmicon'] = iif($pm['iconpath'], true, false);
					$show['unread'] = iif(!$pm['messageread'], true, false);

					($hook = vBulletinHook::fetch_hook('private_messagelist_messagebit')) ? eval($hook) : false;

					$templater = vB_Template::create('pm_messagelistbit');
						$templater->register('groupid', $groupid);
						$templater->register('pm', $pm);
						$templater->register('pmid', $pmid);
						$templater->register('userbit', $userbit);
					$messagelistbits .= $templater->render();
				}
			}

			// free up memory not required any more
			unset($pm_period_groups["$groupid"]);

			($hook = vBulletinHook::fetch_hook('private_messagelist_period')) ? eval($hook) : false;

			// build group template
			$templater = vB_Template::create('pm_messagelist_periodgroup');
				$templater->register('collapseimg_groupid', $collapseimg_groupid);
				$templater->register('collapseobj_groupid', $collapseobj_groupid);
				$templater->register('groupid', $groupid);
				$templater->register('groupname', $groupname);
				$templater->register('messagelistbits', $messagelistbits);
				$templater->register('messagesingroup', $messagesingroup);
			$messagelist_periodgroups .= $templater->render();
		}

		if ($desc)
		{
			unset($search['order']);
		}
		$sorturl = urlimplode($search);

		// build pagenav
		$pagenav = construct_page_nav($pagenumber, $perpage, $totalmessages, 'private.php?' . $vbulletin->session->vars['sessionurl'] . 'folderid=' . $vbulletin->GPC['folderid'] . '&amp;pp=' . $vbulletin->GPC['perpage'] . '&amp;' . $sorturl);

		$sortfield = $search['sort'];
		unset($search['sort']);

		$sorturl = 'private.php?' . $vbulletin->session->vars['sessionurl'] . 'folderid=' . $vbulletin->GPC['folderid'] . ($searchurl = urlimplode($search) ? '&amp;' . $searchurl : '');
		$oppositesort = $desc ? 'asc' : 'desc';

		$orderlinks = array(
			'date' => $sorturl . '&amp;sort=date' . ($sortfield == 'date' ? '&amp;order=' . $oppositesort : ''),
			'title' => $sorturl . '&amp;sort=title' . ($sortfield == 'title' ? '&amp;order=' . $oppositesort : '&amp;order=asc'),
			'sender' => $sorturl . '&amp;sort=sender' . ($sortfield == 'sender' ? '&amp;order=' . $oppositesort : '&amp;order=asc')
		);

		$templater = vB_Template::create('forumdisplay_sortarrow');
			$templater->register('oppositesort', $oppositesort);
		$sortarrow["$sortfield"] = $templater->render();

		// values for filters
		$startdate = fetch_datearray_from_timestamp(($search['startdate'] ? $search['startdate'] : strtotime('last month', TIMENOW)));
		$enddate = fetch_datearray_from_timestamp(($search['enddate'] ? $search['enddate'] : TIMENOW));
		$startmonth[$startdate[month]] = 'selected="selected"';
		$endmonth[$enddate[month]] = 'selected="selected"';
		$readselection[$search['read']] = 'selected="selected"';

		$templater = vB_Template::create('pm_filter');
			$templater->register('enddate', $enddate);
			$templater->register('endmonth', $endmonth);
			$templater->register('order', $order);
			$templater->register('pagenumber', $pagenumber);
			$templater->register('perpage', $perpage);
			$templater->register('readselection', $readselection);
			$templater->register('search', $search);
			$templater->register('sortfield', $sortfield);
			$templater->register('startdate', $startdate);
			$templater->register('startmonth', $startmonth);
		$sortfilter = $templater->render();
	}

	if ($vbulletin->GPC['folderid'] == -1)
	{
		$show['sentto'] = true;
		$show['movetofolder'] = false;
	}
	else
	{
		$show['sentto'] = false;
		$show['movetofolder'] = true;
	}

	$startmessage = vb_number_format($startat + 1);
	$endmessage = vb_number_format(($pagenumber * $perpage) > $totalmessages ? $totalmessages : ($pagenumber * $perpage));
	$totalmessages = vb_number_format($totalmessages);

	$pmtotal = vb_number_format($vbulletin->userinfo['pmtotal']);
	$pmquota = vb_number_format($vbulletin->userinfo['permissions']['pmquota']);
	$includecss['datepicker'] = 'datepicker.css';
	$includeiecss['datepicker'] = 'datepicker-ie.css';

	$page_templater = vB_Template::create('pm_messagelist');
	$page_templater->register('folderid', $folderid);
	$page_templater->register('folderjump', $folderjump);
	$page_templater->register('foldername', $foldername);
	$page_templater->register('forumjump', $forumjump);
	$page_templater->register('gobutton', $gobutton);
	$page_templater->register('messagelist_periodgroups', $messagelist_periodgroups);
	$page_templater->register('orderlinks', $orderlinks);
	$page_templater->register('pagenav', $pagenav);
	$page_templater->register('pagenumber', $pagenumber);
	$page_templater->register('permissions', $permissions);
	$page_templater->register('perpage', $perpage);
	$page_templater->register('pmquota', $pmquota);
	$page_templater->register('pmtotal', $pmtotal);
	$page_templater->register('receipts', $receipts);
	$page_templater->register('sortarrow', $sortarrow);
	$page_templater->register('sortfilter', $sortfilter);
	$page_templater->register('tdwidth', $tdwidth);
	$page_templater->register('totalmessages', $totalmessages);
	$page_templater->register('startmessage', $startmessage);
	$page_templater->register('endmessage', $endmessage);
}

// ############################### start pm reporting ###############################
if ($_REQUEST['do'] == 'report' OR $_POST['do'] == 'sendemail')
{
	$reportthread = ($rpforumid = $vbulletin->options['rpforumid'] AND $rpforuminfo = fetch_foruminfo($rpforumid));
	$reportemail = ($vbulletin->options['enableemail'] AND $vbulletin->options['rpemail']);

	if (!$reportthread AND !$reportemail)
	{
		eval(standard_error(fetch_error('emaildisabled')));
	}

	$vbulletin->input->clean_gpc('r', 'pmid', TYPE_UINT);

	$pminfo = $db->query_first_slave("
		SELECT
			pm.*, pmtext.*
		FROM " . TABLE_PREFIX . "pm AS pm
		LEFT JOIN " . TABLE_PREFIX . "pmtext AS pmtext ON(pmtext.pmtextid = pm.pmtextid)
		WHERE pm.userid=" . $vbulletin->userinfo['userid'] . " AND pm.pmid=" . $vbulletin->GPC['pmid'] . "
	");

	if (!$pminfo)
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['private_message'], $vbulletin->options['contactuslink'])));
	}

	require_once(DIR . '/includes/class_reportitem.php');
	$reportobj = new vB_ReportItem_PrivateMessage($vbulletin);
	$reportobj->set_extrainfo('pm', $pminfo);
	$perform_floodcheck = $reportobj->need_floodcheck();

	if ($perform_floodcheck)
	{
		$reportobj->perform_floodcheck_precommit();
	}

	($hook = vBulletinHook::fetch_hook('report_start')) ? eval($hook) : false;

	if ($_REQUEST['do'] == 'report')
	{
		// draw nav bar
		$navbits = array(
			'usercp.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['user_control_panel'],
			'private.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['private_messages'],
			'' => $vbphrase['report_bad_private_message']
		);

		$usernamecode = vB_Template::create('newpost_usernamecode')->render();

		$navbits = construct_navbits($navbits);
		$navbar = render_navbar_template($navbits);
		$url =& $vbulletin->url;

		$pminfo['itemlink'] = 'private.php?' . $vbulletin->session->vars['sessionurl_q'] . "do=showpm&amp;pmid=" . $pminfo['pmid'];

		($hook = vBulletinHook::fetch_hook('report_form_start')) ? eval($hook) : false;

		$forminfo = $reportobj->set_forminfo($pminfo);
		$templater = vB_Template::create('reportitem');
			$templater->register_page_templates();
			$templater->register('forminfo', $forminfo);
			$templater->register('navbar', $navbar);
			$templater->register('url', $url);
			$templater->register('usernamecode', $usernamecode);
		print_output($templater->render());
	}

	if ($_POST['do'] == 'sendemail')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'reason' => TYPE_STR,
		));

		if ($vbulletin->GPC['reason'] == '')
		{
			eval(standard_error(fetch_error('noreason')));
		}

		$reportobj->do_report($vbulletin->GPC['reason'], $pminfo);

		$url =& $vbulletin->url;
		print_standard_redirect('redirect_reportthanks');
	}
}

// #############################################################################

if (!empty($page_templater))
{
	// draw cp nav bar
	if ($_REQUEST['do'] == 'messagelist')
	{
		construct_usercp_nav('pm_folder' . $vbulletin->GPC['folderid']);
	}
	else if ($_REQUEST['do'] == 'showpm')
	{
		construct_usercp_nav('pm_folder' . $pm['folderid']);
	}
	else
	{
		construct_usercp_nav($page_templater->get_template_name());
	}

	// build navbar
	$navbits = construct_navbits($navbits);
	$navbar = render_navbar_template($navbits);

	($hook = vBulletinHook::fetch_hook('private_complete')) ? eval($hook) : false;

	$includecss['private'] = 'private.css';
	$includeiecss['private'] = 'private-ie.css';
	if (!$vbulletin->options['storecssasfile'])
	{
		$includecss = implode(',', $includecss);
		$includeiecss = implode(',', $includeiecss);
	}

	// print page
	$templater = vB_Template::create('USERCP_SHELL');
		$templater->register_page_templates();
		$templater->register('includecss', $includecss);
		$templater->register('includeiecss', $includeiecss);
		$templater->register('cpnav', $cpnav);
		$templater->register('HTML', $page_templater->render());
		$templater->register('navbar', $navbar);
		$templater->register('navclass', $navclass);
		$templater->register('onload', $onload);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('template_hook', $template_hook);
	print_output($templater->render());
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 64477 $
|| ####################################################################
\*======================================================================*/
?>
