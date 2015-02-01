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
#define('GET_EDIT_TEMPLATES', true);
define('THIS_SCRIPT', 'assetmanage');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('posting');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'loadassets' => array(
		'assetmanager_thumbview',
		'assetmanager_listview',
		'assetmanager_detailedview',
	),
	'help' => array(
		'assetmanager_help',
	),
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_newpost.php');
require_once(DIR . '/includes/functions_file.php');
require_once(DIR . '/packages/vbattach/attach.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$vbulletin->input->clean_array_gpc('r', array(
	'poststarttime' => TYPE_UINT,
	'posthash'      => TYPE_NOHTML,
	'userid'        => TYPE_UINT,
));

if (
	!$vbulletin->userinfo['userid'] // Guests can not post attachments
		OR
	empty($vbulletin->userinfo['attachmentextensions'])
		OR
	($vbulletin->GPC['posthash'] != md5($vbulletin->GPC['poststarttime'] . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']))
)
{
	print_no_permission();
}

if ($_POST['ajax'])
{
	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	// Still undecided about this
	// $userinfo = verify_id('user', $vbulletin->GPC['userid'], 1, 1);
	$userinfo = $vbulletin->userinfo;

	if ($_POST['do'] == 'loadnode')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'parentid'  => TYPE_UINT,
		));

		$xml->add_group('categories');

		$categories = $db->query_read_slave("
			SELECT categoryid, title
			FROM " . TABLE_PREFIX . "attachmentcategory
			WHERE
				userid = {$userinfo['userid']}
					AND
				parentid = {$vbulletin->GPC['parentid']}
			ORDER BY displayorder
		");
		while ($category = $db->fetch_array($categories))
		{
			$xml->add_tag('category', $category['title'], array('categoryid' => $category['categoryid']));
		}

		// Update posthash if this is the root node.
		if ($vbulletin->GPC['parentid'] == 0)
		{
			$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
			$userdm->set_existing($userinfo);
			$userdm->set('assetposthash', $vbulletin->GPC['posthash']);
			$userdm->save();
			$userinfo['assetposthash'] = $vbulletin->GPC['posthash'];
		}

		$xml->close_group();
		$xml->print_xml();
	}

	if ($_POST['do'] == 'loadassets')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'categoryid'    => TYPE_UINT,
			'view'          => TYPE_STR,
			'orderby'       => TYPE_STR,
			'sortorder'     => TYPE_STR,
			'pagenumber'    => TYPE_UINT,
			'init'          => TYPE_BOOL,
			'contenttypeid' => TYPE_UINT,
		));

		if ($vbulletin->GPC['categoryid'] AND !($db->query_first("
			SELECT categoryid
			FROM " . TABLE_PREFIX . "attachmentcategory
			WHERE
				userid = {$userinfo['userid']}
					AND
				categoryid = {$vbulletin->GPC['categoryid']}
			")))
		{
			eval(standard_error(fetch_error('invalidid', 'categoryid', $vbulletin->options['contactuslink'])));
		}

		switch($vbulletin->GPC['orderby'])
		{
			case 'filename':
				$orderby = 'acu.filename';
				break;
			default:
				$handled = false;
				($hook = vBulletinHook::fetch_hook('assetmanager_orderby')) ? eval($hook) : false;
				if (!$handled)
				{
					$orderby = 'acu.dateline';
				}
		}

		switch($vbulletin->GPC['sortorder'])
		{
			case 'asc':
				$sortorder = 'ASC';
				break;
			default:
				$sortorder = 'DESC';
		}

		switch($vbulletin->GPC['view'])
		{
			case 'list':
				$template = 'assetmanager_listview';
				break;
			case 'detailed':
				$template = 'assetmanager_detailedview';
				break;
			default:
				$handled = false;
				($hook = vBulletinHook::fetch_hook('assetmanager_viewtype')) ? eval($hook) : false;
				if (!$handled)
				{
					$template = 'assetmanager_thumbview';
				}
		}

		$extensions = array();
		$contenttypeid = $vbulletin->GPC['contenttypeid'];
		foreach($userinfo['attachmentpermissions'] AS $filetype => $extension)
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
				$extensions["$filetype"] = $extension['size'];
			}
		}

		// if we are in the Forum Post context, use maximum post attach limit, otherwise disable js attachlimit check (0)
		// TODO: add an attachlimit for each of the attachment types (like albums, articles, blog entries ect.)
		/* Disabling this for now, because its too much overhead for added js validation for posts only
		$attachlimit = ($contenttypeid == vB_Types::instance()->getContentTypeID('vBForum_Post')) ? $vbulletin->options['attachlimit'] : 0;
		*/
		$attachlimit = 0; // disable js attachlimit validation for now

		$perpage = ($vbulletin->options['vbasset_perpage'] > 0 ? $vbulletin->options['vbasset_perpage'] : 50);
		$pagenumber = !$vbulletin->GPC['pagenumber'] ? 1 : $vbulletin->GPC['pagenumber'];
		do
		{
			$start = ($pagenumber - 1) * $perpage;

			$assets = $db->query("
				SELECT SQL_CALC_FOUND_ROWS
					acu.*, fd.thumbnail_dateline AS dateline, IF (thumbnail_filesize > 0, 1, 0) AS hasthumbnail, fd.extension, fd.filesize
				FROM " . TABLE_PREFIX . "attachmentcategoryuser AS acu
				INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (acu.filedataid = fd.filedataid)
				WHERE
					acu.userid = {$userinfo['userid']}
						AND
					acu.categoryid = {$vbulletin->GPC['categoryid']}
						AND
					fd.extension IN ('" . implode("' , '", array_keys($extensions)) . "')
				ORDER BY $orderby $sortorder
				LIMIT $start, $perpage
			");
			$totalassets = $db->found_rows();

			if ($start >= $totalassets)
			{
				$pagenumber = ceil($totalassets / $perpage);
			}
		}
		while($start >= $totalassets AND $totalassets);

		$xml->add_group('results');
		$xml->add_group('assets');

		// vB_Template::create is resetting $pagenumber
		$_pagenumber = $pagenumber;
		while ($assetinfo = $db->fetch_array($assets))
		{
			$assetinfo['date_string'] = vbdate($vbulletin->options['dateformat'], $assetinfo['dateline']);
			$assetinfo['time_string'] = vbdate($vbulletin->options['timeformat'], $assetinfo['dateline']);
			$assetinfo['filesize_formatted'] = vb_number_format($assetinfo['filesize'], 1, true);
			$assetinfo['filename'] = fetch_censored_text(htmlspecialchars_uni($assetinfo['filename'], false));
			$templater = vB_Template::create($template);
				$templater->register('assetinfo', $assetinfo);
			$xml->add_tag('asset', $templater->render());
		}
		$xml->close_group();

		$startasset = $totalassets ? $start + 1 : 0;
		$endasset = ($start + $perpage > $totalassets ? $totalassets : $start + $perpage);
		$totalpages = ceil($totalassets / $perpage);
		$xml->add_tag('totalassets', $totalassets);
		$xml->add_tag('startasset', $startasset);
		$xml->add_tag('endasset', $endasset);
		$xml->add_tag('pagenumber', $_pagenumber);
		$xml->add_tag('totalpages', $totalpages);
		$xml->add_tag('currentpage', construct_phrase($vbphrase['page_x_of_y'], $_pagenumber, $totalpages));
		$xml->add_tag('pagestats', construct_phrase($vbphrase['assets_x_to_y_of_z'], $startasset, $endasset, $totalassets));

		// Defaults used by program init
		if ($vbulletin->GPC['categoryid'] == 0 AND $vbulletin->GPC['init'])
		{
			$xml->add_tag('attachboxcount', $vbulletin->options['attachboxcount']);
			$xml->add_tag('attachurlcount', $vbulletin->options['attachurlcount']);
			$xml->add_tag('attachlimit', $attachlimit);
			$xml->add_tag('max_file_size',  fetch_max_upload_size());
			$xml->add_group('phrases');
				$xml->add_tag('rename', $vbphrase['rename']);
				$xml->add_tag('delete', $vbphrase['delete']);
				$xml->add_tag('add_folder_to_x', $vbphrase['add_folder_to_x']);
				$xml->add_tag('are_you_sure_delete_asset', $vbphrase['are_you_sure_delete_asset']);
				$xml->add_tag('are_you_sure_delete_assets', $vbphrase['are_you_sure_delete_assets']);
				$xml->add_tag('upload_failed', $vbphrase['upload_failed']);
				$xml->add_tag('asset_already_attached', $vbphrase['asset_already_attached']);
				$xml->add_tag('are_you_sure_delete_folder_x', $vbphrase['are_you_sure_delete_folder_x']);
				$xml->add_tag('enter_title', $vbphrase['enter_title']);
				$xml->add_tag('add_folder_to_home', $vbphrase['add_folder_to_home']);
				$xml->add_tag('the_following_errors_occurred', $vbphrase['the_following_errors_occurred']);
				$xml->add_tag('file_is_too_large', $vbphrase['file_is_too_large']);
				$xml->add_tag('invalid_file', $vbphrase['invalid_file']);
				$xml->add_tag('all_files', $vbphrase['all_files']);
				$xml->add_tag('maximum_number_of_attachments_reached', $vbphrase['maximum_number_of_attachments_reached']);
				$xml->add_tag('assets_x_to_y_of_z', $vbphrase['assets_x_to_y_of_z']);
				$xml->add_tag('please_drag_and_drop', $vbphrase['please_drag_and_drop']);
				$xml->add_tag('please_select_attachment', $vbphrase['please_select_attachment']);
				$xml->add_tag('insert_inline_x', $vbphrase['insert_inline_x']);
			$xml->close_group('phrases');
			$xml->add_group('extensions');

			foreach($extensions AS $extension => $maxsize)
			{
				$xml->add_tag('extension', $maxsize, array('name' => $extension));
			}
			$xml->close_group('extensions');
		}

		$xml->close_group();
		$xml->print_xml();
	}

	if ($_POST['do'] == 'help')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'filedataid' => TYPE_UINT,
			'type'       => TYPE_STR,
		));

		switch($vbulletin->GPC['type'])
		{
			case 'assetusage':
				$attachmultiple = new vB_Attachment_Display_Multiple($vbulletin);
				$attachments = $attachmultiple->fetch_results("a.userid = $userinfo[userid] AND a.contentid <> 0 AND a.filedataid = " . $vbulletin->GPC['filedataid']);

				$title = $vbphrase['asset_usage'];
				$content = '';

				if (empty($attachments))
				{
					eval(standard_error(fetch_error('asset_not_used')));
				}

				$count = 0;
				foreach($attachments AS $attachment)
				{
					$count++;
					$result = $attachmultiple->process_attachment($attachment);
					$templater = vB_Template::create('assetmanager_usage_' . $result['template']);
					unset($result['template']);
					foreach ($result AS $key => $value)
					{
						$templater->register($key, $value);
					}
					$templater->register('usagerow', ($count % 2) == 1 ? 'usagerow1' : 'usagerow2');
					$content .= $templater->render();
				}
				break;
			default:	// help
				$title = $vbphrase['help'];
				$templater = vB_Template::create('assetmanager_help');
				$content = $templater->render();
		}

		$xml->add_group('help');
		$xml->add_tag('title', $title);
		$xml->add_tag('content', $content);
		$xml->close_group();
		$xml->print_xml();
	}

	if ($_POST['do'] == 'orderattachments')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'attachment' => TYPE_ARRAY_UINT
		));

		$casesql = array();
		foreach ($vbulletin->GPC['attachment'] AS $attachmentid => $displayorder)
		{
			$casesql[] = " WHEN attachmentid = " . intval($attachmentid) . " THEN " . intval($displayorder) . " ";
		}

		if (!$casesql)
		{
			exit;
		}

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "attachment
			SET displayorder =
				CASE
					" . implode($casesql, "\r\n") . "
					ELSE displayorder
				END
			WHERE userid = $userinfo[userid]
		");
	}

	if ($vbulletin->GPC['posthash'] AND $userinfo['assetposthash'] != $vbulletin->GPC['posthash'])
	{
		eval(standard_error(fetch_error('folder_structure_altered')));
	}

	// Update posthash for all actions beyond node loading (except if this is the first node, see "loadnode")
	$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
	$userdm->set_existing($userinfo);
	$userdm->set('assetposthash', $vbulletin->GPC['posthash']);
	$userdm->save();

	if ($_POST['do'] == 'updatelabel')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'categoryid' => TYPE_UINT,
			'title'      => TYPE_NOHTML,
		));

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "attachmentcategory
			SET title = '" . $db->escape_string(convert_urlencoded_unicode($vbulletin->GPC['title'])) . "'
			WHERE
				userid = {$userinfo['userid']}
					AND
				categoryid = {$vbulletin->GPC['categoryid']}
		");

		exit;
	}

	if ($_POST['do'] == 'insertnode')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'parentid'  => TYPE_UINT,
			'title'     => TYPE_NOHTML,
			'returnall' => TYPE_BOOL,
		));

		if (!($maxdo = $db->query_first("
			SELECT MAX(displayorder) AS maxdo
			FROM " . TABLE_PREFIX . "attachmentcategory
			WHERE
				userid = {$userinfo['userid']}
					AND
				parentid = {$vbulletin->GPC['parentid']}
			")))
		{
			eval(standard_error(fetch_error('invalidid', 'categoryid', $vbulletin->options['contactuslink'])));
		}

		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "attachmentcategory
				(parentid, userid, title, displayorder)
			VALUES
				(
					{$vbulletin->GPC['parentid']},
					{$userinfo['userid']},
					'" . $db->escape_string(convert_urlencoded_unicode($vbulletin->GPC['title'])) . "',
					" . ($maxdo['maxdo'] + 1) . "
				)
		");

		$categoryid = $db->insert_id();

		$xml->add_group('categories');
		if ($vbulletin->GPC['returnall'])
		{
			$categories = $db->query_read_slave("
				SELECT categoryid, title
				FROM " . TABLE_PREFIX . "attachmentcategory
				WHERE
					userid = {$userinfo['userid']}
						AND
					parentid = {$vbulletin->GPC['parentid']}
				ORDER BY displayorder
			");
			while ($category = $db->fetch_array($categories))
			{
				$xml->add_tag('category', $category['title'], array('categoryid' => $category['categoryid']));
			}
		}
		else
		{
			$xml->add_tag('categoryid', $categoryid);
		}
		$xml->close_group();
		$xml->print_xml();
	}

	if ($_POST['do'] == 'removenode')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'categoryid' => TYPE_UINT,
		));

		if (!($catinfo = $db->query_first("
			SELECT categoryid, parentid
			FROM " . TABLE_PREFIX . "attachmentcategory
			WHERE
				 userid = {$userinfo['userid']}
				 	AND
				 categoryid = {$vbulletin->GPC['categoryid']}
			")))
		{
			eval(standard_error(fetch_error('invalidid', 'categoryid', $vbulletin->options['contactuslink'])));
		}

		$ids = array($vbulletin->GPC['categoryid']);
		$ids = array_merge($ids, fetch_children($userinfo['userid'], $vbulletin->GPC['categoryid']));

		if (!empty($ids))
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "attachmentcategoryuser
				SET categoryid = $catinfo[parentid]
				WHERE
					userid = {$userinfo['userid']}
						AND
					categoryid IN ( " . implode(', ', $ids) . ")
			");

			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "attachmentcategory
				WHERE
					userid = {$userinfo['userid']}
						AND
					categoryid IN (" . implode(', ', $ids) . ")
			");

			$xml->add_tag('response', $db->affected_rows());
			$xml->print_xml();
		}
		exit;
	}

	if ($_POST['do'] == 'movenode')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'categoryid' => TYPE_UINT,
			'parentid'   => TYPE_UINT,
			'siblingids' => TYPE_STR,
		));

		if (!($db->query_first("
			SELECT categoryid
			FROM " . TABLE_PREFIX . "attachmentcategory
			WHERE
				 userid = {$userinfo['userid']}
				 	AND
				 categoryid = {$vbulletin->GPC['categoryid']}
			")))
		{
			eval(standard_error(fetch_error('invalidid', 'categoryid', $vbulletin->options['contactuslink'])));
		}

		if ($vbulletin->GPC['parentid'] > 0 AND !($db->query_first("
			SELECT categoryid
			FROM " . TABLE_PREFIX . "attachmentcategory
			WHERE
				 userid = {$userinfo['userid']}
				 	AND
				 categoryid = {$vbulletin->GPC['parentid']}
			")))
		{
			eval(standard_error(fetch_error('invalidid', 'parentid', $vbulletin->options['contactuslink'])));
		}

		if (!($maxdo = $db->query_first("
			SELECT MAX(displayorder) AS maxdo
			FROM " . TABLE_PREFIX . "attachmentcategory
			WHERE
				 userid = {$userinfo['userid']}
				 	AND
				 parentid = {$vbulletin->GPC['parentid']}
			")))
		{
			eval(standard_error(fetch_error('invalidid', 'categoryid', $vbulletin->options['contactuslink'])));
		}

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "attachmentcategory
			SET
				parentid = {$vbulletin->GPC['parentid']},
				displayorder = 0
			WHERE
				categoryid = {$vbulletin->GPC['categoryid']}
					AND
				userid = {$userinfo['userid']}
		");

		$siblingids = explode(',', $vbulletin->GPC['siblingids']);
		$siblingids = array_map('intval', $siblingids);

		$child_positions = array_flip($siblingids);
		$casesql = array();
		foreach ($child_positions AS $categoryid => $displayorder)
		{
			$casesql[] = " WHEN categoryid = $categoryid THEN $displayorder ";
		}

		if (!empty($casesql))
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "attachmentcategory
				SET displayorder =
				CASE
					" . implode($casesql, "\r\n") . "
					ELSE displayorder
				END
				WHERE
					userid = {$userinfo['userid']}
						AND
					parentid = {$vbulletin->GPC['parentid']}
			");
		}

		exit;
	}

	if ($_POST['do'] == 'moveasset')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'categoryid' => TYPE_UINT,
			'filedata'   => TYPE_ARRAY_UINT,
		));

		if ($vbulletin->GPC['categoryid'] AND !($db->query_first("
			SELECT categoryid
			FROM " . TABLE_PREFIX . "attachmentcategory
			WHERE
				 userid = {$userinfo['userid']}
				 	AND
				 categoryid = {$vbulletin->GPC['categoryid']}
			")))
		{
			eval(standard_error(fetch_error('invalidid', 'categoryid', $vbulletin->options['contactuslink'])));
		}

		$count = $db->query_first("
			SELECT COUNT(*) AS count
			FROM " . TABLE_PREFIX . "attachmentcategoryuser
			WHERE
				 userid = {$userinfo['userid']}
				 	AND
				 filedataid IN(0," . implode(", ", array_values($vbulletin->GPC['filedata'])) . ")
		");
		if ($count['count'] != count($vbulletin->GPC['filedata']))
		{
			eval(standard_error(fetch_error('invalidid', 'filedataid', $vbulletin->options['contactuslink'])));
		}

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "attachmentcategoryuser
			SET categoryid = {$vbulletin->GPC['categoryid']}
			WHERE
				userid = $userinfo[userid]
					AND
				filedataid IN(0," . implode(", ", array_values($vbulletin->GPC['filedata'])) . ")
		");

		$xml->add_tag('results', $db->affected_rows());
		$xml->print_xml();

	}
}

function fetch_children($userid, $categoryid)
{
	global $vbulletin;

	$ids = array();
	$children = $vbulletin->db->query_read("
		SELECT categoryid
		FROM " . TABLE_PREFIX . "attachmentcategory
		WHERE
			userid = $userid
				AND
			parentid = $categoryid
	");
	while ($child = $vbulletin->db->fetch_array($children))
	{
		$ids[] = $child['categoryid'];
		$ids = array_merge($ids, fetch_children($userid, $child['categoryid']));
	}

	return $ids;
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 62098 $
|| ####################################################################
\*======================================================================*/
?>
