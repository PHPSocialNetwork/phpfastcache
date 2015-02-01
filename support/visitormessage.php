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
define('CSRF_PROTECTION', true);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'visitormessage');
define('BYPASS_STYLE_OVERRIDE', 1);
define('GET_EDIT_TEMPLATES', 'message');

if ($_POST['do'] == 'message')
{
	if (isset($_POST['ajax']))
	{
		define('NOPMPOPUP', 1);
		define('NOSHUTDOWNFUNC', 1);
	}
	if (isset($_POST['fromquickcomment']))
	{	// Don't update Who's Online for Quick Comments since it will get stuck on that until the user goes somewhere else
		define('LOCATION_BYPASS', 1);
	}
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'user',
	'messaging',
	'cprofilefield',
	'reputationlevel',
	'posting'
);

if ($_REQUEST['do'] == 'message')
{
	$phrasegroups[] = 'inlinemod';
}

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache'
);

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'report' => array(
		'newpost_usernamecode',
		'reportitem',
	),
	'message' => array(
		'visitormessage_editor',
		'visitormessage_preview',
		'visitormessage_simpleview',
		'visitormessage_simpleview_deleted',
	),
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_visitormessage.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (!($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers']))
{
	print_no_permission();
}

if (!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_visitor_messaging']))
{
	print_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'userid' => TYPE_UINT,
	'vmid'   => TYPE_UINT,
));

($hook = vBulletinHook::fetch_hook('visitor_message_start')) ? eval($hook) : false;

if ($vbulletin->GPC['vmid'])
{
	$messageinfo = verify_visitormessage($vbulletin->GPC['vmid']);

	if (!fetch_visitor_message_perm('caneditvisitormessages', $messageinfo, $messageinfo) AND !($_REQUEST['do'] == 'report' OR $_REQUEST['do'] == 'sendemail'))
	{		// yes, two instances of $messageinfo in the previous line
		print_no_permission();
	}
	$vbulletin->GPC['userid'] = $messageinfo['userid'];
}

$userinfo = verify_id('user', $vbulletin->GPC['userid'], true, true, FETCH_USERINFO_ISFRIEND);

if (!$userinfo['vm_enable'])
{
	if (!can_moderate(0, 'canmoderatevisitormessages') OR $userinfo['userid'] == $vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}
}
else if (
	$userinfo['vm_contactonly']
		AND
	!can_moderate(0, 'canmoderatevisitormessages')
		AND
	$userinfo['userid'] != $vbulletin->userinfo['userid']
		AND
	!$userinfo['bbuser_iscontact_of_user']
)
{
	// are you a contact?
	print_no_permission();
}

require_once(DIR . '/includes/functions_user.php');
if (!can_view_profile_section($userinfo['userid'], 'visitor_messaging'))
{
	print_no_permission();
}

cache_permissions($userinfo, false);

if ($userinfo['usergroupid'] == 4 AND !($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
{
	print_no_permission();
}

$canpostmessage = (
	$userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers']
	AND $vbulletin->userinfo['userid']
	AND (
		(
			$vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['canmessageownprofile']
			AND $vbulletin->userinfo['userid'] == $userinfo['userid']
		)
		OR (
			$vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['canmessageothersprofile']
			AND $vbulletin->userinfo['userid'] != $userinfo['userid']
		)
	)
);

if ($_REQUEST['do'] == 'message')
{
	if ($messageinfo AND !fetch_visitor_message_perm('caneditvisitormessages', $userinfo, $messageinfo))
	{
		print_no_permission();
	}
	else if (!$messageinfo AND !$canpostmessage)
	{
		print_no_permission();
	}

	if ($_POST['do'] == 'message')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'message'          => TYPE_STR,
			'wysiwyg'          => TYPE_BOOL,
			'disablesmilies'   => TYPE_BOOL,
			'parseurl'         => TYPE_BOOL,
			'username'         => TYPE_STR,
			'ajax'             => TYPE_BOOL,
			'lastcomment'      => TYPE_UINT,
			'humanverify'      => TYPE_ARRAY,
			'loggedinuser'     => TYPE_UINT,
			'fromquickcomment' => TYPE_BOOL,
			'preview'          => TYPE_STR,
			'advanced'         => TYPE_BOOL,
			'fromconverse'     => TYPE_BOOL,
			'u2'               => TYPE_UINT,
		));

		($hook = vBulletinHook::fetch_hook('visitor_message_post_start')) ? eval($hook) : false;

		// unwysiwygify the incoming data
		if ($vbulletin->GPC['wysiwyg'])
		{
			require_once(DIR . '/includes/class_wysiwygparser.php');
			$html_parser = new vB_WysiwygHtmlParser($vbulletin);
			$vbulletin->GPC['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'],  $vbulletin->options['allowhtml']);
		}

		// parse URLs in message text
		if ($vbulletin->options['allowbbcode'] AND $vbulletin->GPC['parseurl'])
		{
			require_once(DIR . '/includes/functions_newpost.php');
			$vbulletin->GPC['message'] = convert_url_to_bbcode($vbulletin->GPC['message']);
		}

		$message = array(
			'message'        =>& $vbulletin->GPC['message'],
			'userid'         =>& $userinfo['userid'],
			'postuserid'     =>& $vbulletin->userinfo['userid'],
			'disablesmilies' =>& $vbulletin->GPC['disablesmilies'],
			'parseurl'       =>& $vbulletin->GPC['parseurl'],
		);

		if ($vbulletin->GPC['ajax'])
		{
			$message['message'] = convert_urlencoded_unicode($message['message']);
		}

		$dataman =& datamanager_init('VisitorMessage', $vbulletin, ERRTYPE_ARRAY);

		if ($messageinfo)
		{
			$show['edit'] = true;
			$dataman->set_existing($messageinfo);
		}
		else
		{
			// Don't allow mods to create new messages
			if (!$userinfo['vm_enable'])
			{
				print_no_permission();
			}

			if (($vbulletin->options['vm_moderation'] OR !($vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['followforummoderation'])) AND !fetch_visitor_message_perm('canmoderatevisitormessages', $userinfo))
			{
				$dataman->set('state', 'moderation');
			}
			if ($vbulletin->userinfo['userid'] == 0)
			{
				$dataman->setr('username', $vbulletin->GPC['username']);
			}
			$dataman->setr('userid', $userinfo['userid']);
			$dataman->setr('postuserid', $vbulletin->userinfo['userid']);
		}

		$dataman->set_info('preview', $vbulletin->GPC['preview']);
		$dataman->setr('pagetext', $message['message']);
		$dataman->set('allowsmilie', !$message['disablesmilies']);

		//todo: What about a profile option for the owner to set their comments as moderated by default?
		//todo: Option to receive email notification to the owner of this profile about a new comment.

		$dataman->pre_save();

		if ($vbulletin->GPC['fromquickcomment'] AND $vbulletin->GPC['preview'])
		{
			$dataman->errors = array();
		}

		require_once(DIR . '/includes/class_socialmessageparser.php');
		$pmparser = new vB_VisitorMessageParser($vbulletin, fetch_tag_list());
		$pmparser->parse($message['message']);
		if ($error_num = count($pmparser->errors))
		{
			foreach ($pmparser->errors AS $tag => $error_phrase)
			{
				$dataman->errors[] = fetch_error($error_phrase, $tag);
			}
		}

		if (!empty($dataman->errors))
		{
			if ($vbulletin->GPC['ajax'])
			{
				require_once(DIR . '/includes/class_xml.php');
				$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
				$xml->add_group('errors');
				foreach ($dataman->errors AS $error)
				{
					$xml->add_tag('error', $error);
				}
				$xml->close_group();
				$xml->print_xml();
			}
			else
			{
				define('MESSAGEPREVIEW', true);
				require_once(DIR . '/includes/functions_newpost.php');
				$preview = construct_errors($dataman->errors);
				$_GET['do'] = 'message';
			}
		}
		else if ($vbulletin->GPC['preview'] OR $vbulletin->GPC['advanced'])
		{
			define('MESSAGEPREVIEW', true);

			if ($vbulletin->GPC['preview'])
			{
				$preview = process_visitor_message_preview($message);
			}

			$_GET['do'] = 'message';
		}
		else
		{
			$vmid = $dataman->save();
			clear_autosave_text('vBForum_VisitorMessage', $messageinfo ? $messageinfo['vmid'] : 0, $messageinfo ? 0 : $userinfo['userid'], $vbulletin->userinfo['userid']);

			if ($messageinfo AND $messageinfo['postuserid'] != $vbulletin->userinfo['userid'] AND can_moderate(0, 'caneditvisitormessages'))
			{
				require_once(DIR . '/includes/functions_log_error.php');
				log_moderator_action($messageinfo, 'vm_by_x_for_y_edited',
					array($messageinfo['postusername'], $userinfo['username'])
				);
			}

			if ($vbulletin->GPC['fromconverse'])
			{
				$userinfo2 = verify_id('user', $vbulletin->GPC['u2'], 1, 1);
			}

			if ($vbulletin->GPC['ajax'])
			{
				require_once(DIR . '/includes/class_xml.php');
				$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
				$xml->add_group('commentbits');
				$read_ids = array();

				require_once(DIR . '/includes/class_bbcode.php');
				require_once(DIR . '/includes/class_visitormessage.php');

				$bbcode = new vB_BbCodeParser($vbulletin, fetch_tag_list());
				$factory = new vB_Visitor_MessageFactory($vbulletin, $bbcode, $userinfo);

				if ($vbulletin->GPC['vmid'])
				{
					$sql = fetch_vm_ajax_query($userinfo, $vbulletin->GPC['vmid'], 'edit');
				}
				else if ($vbulletin->GPC['fromconverse'])
				{
					$sql = fetch_vm_ajax_query($userinfo, $vmid, 'wall', $userinfo2);
				}
				else
				{
					$sql = fetch_vm_ajax_query($userinfo, $vmid, 'user');
				}

				$messages = $db->query_read_slave($sql);
				while ($message = $db->fetch_array($messages))
				{
					// Process user.options
					$message = array_merge($message, convert_bits_to_array($message['options'], $vbulletin->bf_misc_useroptions));

					if ($message['profileuserid'] == $vbulletin->userinfo['userid'] AND $message['state'] == 'visible' AND !$message['messageread'])
					{
						$read_ids[] = $message['vmid'];
					}

					$response_handler =& $factory->create($message);
					$response_handler->cachable = false;
					if ($vbulletin->GPC['fromconverse'])
					{
						$response_handler->converse = false;
					}
					else
					{
						$response_handler->converse = true;
						if (
							(
								!$message['vm_enable']
									AND
								(
									!can_moderate(0, 'canmoderatevisitormessages')
										OR
									$vbulletin->userinfo['userid'] == $message['postuserid']
								)
							)
							OR
							(
								$message['vm_contactonly']
									AND
								!can_moderate(0, 'canmoderatevisitormessages')
									AND
								$message['postuserid'] != $vbulletin->userinfo['userid']
									AND
								!$message['bbuser_iscontact_of_user']
							)
						)
						{
							$response_handler->converse = false;
						}
					}

					$xml->add_tag('message', process_replacement_vars($response_handler->construct()), array(
						'vmid'      => $message['vmid'],
						'visible'   => ($message['state'] == 'visible') ? 1 : 0,
						'bgclass'   => $bgclass,
						'quickedit' => 1
					));
				}

				// our profile and ids that need read
				if (!empty($read_ids))
				{
					$db->query_write("UPDATE " . TABLE_PREFIX . "visitormessage SET messageread = 1 WHERE vmid IN (" . implode(',', $read_ids) . ")");

					build_visitor_message_counters($vbulletin->userinfo['userid']);
				}

				$xml->add_tag('time', TIMENOW);
				$xml->close_group();
				$xml->print_xml(true);
			}
			else
			{
				($hook = vBulletinHook::fetch_hook('visitor_message_post_complete')) ? eval($hook) : false;

				if ($messageinfo)
				{
					$vbulletin->url = fetch_seo_url('member', $userinfo, array('vmid' => $messageinfo['vmid'])) . "#vmessage$messageinfo[vmid]";
					print_standard_redirect('visitormessageeditthanks');
				}
				else
				{
					if ($vbulletin->GPC['fromconverse'])
					{
						$vbulletin->url = 'converse.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]&amp;u2=$userinfo2[userid]&amp;vmid=$vmid#vmessage$vmid";
					}
					else
					{
						$vbulletin->url = fetch_seo_url('member', $userinfo, array('vmid' => $vmid)) . "#vmessage$vmid";
					}
					print_standard_redirect('visitormessagethanks');
				}
			}
		}
	}

	if ($_GET['do'] == 'message')
	{
		require_once(DIR . '/includes/functions_editor.php');

		($hook = vBulletinHook::fetch_hook('visitor_message_form_start')) ? eval($hook) : false;

		if (defined('MESSAGEPREVIEW'))
		{
			$postpreview =& $preview;
			$message['message'] = htmlspecialchars_uni($message['message']);

			require_once(DIR . '/includes/functions_newpost.php');
			construct_checkboxes($message);
		}
		else if ($messageinfo)
		{
			require_once(DIR . '/includes/functions_newpost.php');
			construct_checkboxes(
				array(
					'disablesmilies' => (!$messageinfo['allowsmilie']),
					'parseurl'       => 1,
				)
			);
			$message['message'] = htmlspecialchars_uni($messageinfo['pagetext']);
		}
		else
		{
			$message['message'] = '';
		}

		$editorid = construct_edit_toolbar(
			$message['message'],
			false,
			'visitormessage',
			$vbulletin->options['allowsmilies'],
			true,
			false,
			'fe',
			'',
			array(),
			'content',
			'vBForum_VisitorMessage',
			$messageinfo['vmid'] ? $messageinfo['vmid'] : 0,
			$messageinfo ? 0 : $userinfo['userid'],
			defined('MESSAGEPREVIEW')
		);

		$usernamecode = vB_Template::create('newpost_usernamecode')->render();

		// auto-parse URL
		if (!isset($checked['parseurl']))
		{
			$checked['parseurl'] = 'checked="checked"';
		}
		$show['parseurl'] = $vbulletin->options['allowbbcode'];
		$show['misc_options'] = ($show['parseurl'] OR !empty($disablesmiliesoption));
		$show['additional_options'] = ($show['misc_options'] OR !empty($attachmentoption));
		$show['physicaldeleteoption'] = can_moderate(0, 'canremovevisitormessages');

		$messagebits = '';
		$read_ids = array();
		// If not an edit then fetch post history between vbulletin->userinfo and this user
		if (!$messageinfo AND $vbulletin->userinfo['userid'] AND $userinfo['userid'] != $vbulletin->userinfo['userid'])
		{
			$sql1 = $sql2 = array();

			$state2 = array('visible');
			if (fetch_visitor_message_perm('canmoderatevisitormessages', $userinfo))
			{
				$state2[] = 'moderation';
			}

			if (can_moderate(0, 'canmoderatevisitormessages') OR $vbulletin->userinfo['permissions']['visitormessagepermissions'] & $vbulletin->bf_ugp_visitormessagepermissions['canmanageownprofile'])
			{
				$state2[] = 'deleted';
				$deljoinsql2 = "LEFT JOIN " . TABLE_PREFIX . "deletionlog AS deletionlog ON (visitormessage.vmid = deletionlog.primaryid AND deletionlog.type = 'visitormessage')";
			}
			else
			{
				$deljoinsql2 = '';
			}

			$state1 = array('visible', 'moderation');

			if (can_moderate(0, 'canmoderatevisitormessages'))
			{
				$state1[] = 'deleted';
				$delsql1 = ",deletionlog.userid AS del_userid, deletionlog.username AS del_username, deletionlog.reason AS del_reason";
				$deljoinsql1 = "LEFT JOIN " . TABLE_PREFIX . "deletionlog AS deletionlog ON (visitormessage.vmid = deletionlog.primaryid AND deletionlog.type = 'visitormessage')";
			}
			else if ($deljoinsql2)
			{
				$delsql1 = ",0 AS del_userid, '' AS del_username, '' AS del_reason";
			}

			// Messages left to this user's profile by $vbulletin->userinfo
			$sql1[] = "visitormessage.userid = $userinfo[userid]";
			$sql1[] = "visitormessage.postuserid = " . $vbulletin->userinfo['userid'];
			$sql1[] = "visitormessage.state IN ('" . implode("','", $state1) . "')";

			// Messages left to vbulletin->userinfo's profile by this user
			$sql2[] = "visitormessage.userid = " . $vbulletin->userinfo['userid'];
			$sql2[] = "visitormessage.postuserid = $userinfo[userid]";
			$sql2[] = "visitormessage.state IN ('" . implode("','", $state2) . "')";

			require_once(DIR . '/includes/class_bbcode.php');
			require_once(DIR . '/includes/class_visitormessage.php');

			$bbcode = new vB_BbCodeParser($vbulletin, fetch_tag_list());
			$factory = new vB_Visitor_MessageFactory($vbulletin, $bbcode, $userinfo);

			$hook_query_fields1 = $hook_query_fields2 = $hook_query_joins1 = $hook_query_joins2 = $hook_query_where1 = $hook_query_where2 = '';
			($hook = vBulletinHook::fetch_hook('visitor_message_form_query')) ? eval($hook) : false;

			// We will need to limit this to a certain amount
			$messages = $db->query_read_slave("
			(
				SELECT
					visitormessage.*, visitormessage.dateline AS pmdateline, user.*, visitormessage.ipaddress AS messageipaddress
					$delsql1
					" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
					$hook_query_fields1
				FROM " . TABLE_PREFIX . "visitormessage AS visitormessage
				LEFT JOIN " . TABLE_PREFIX . "user AS user ON (visitormessage.postuserid = user.userid)
				" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
				$deljoinsql1
				$hook_query_joins1
				WHERE " . implode(" AND ", $sql1) . "
				$hook_query_where1
			)
			UNION
			(
				SELECT
					visitormessage.*, visitormessage.dateline AS pmdateline, user.*, visitormessage.ipaddress AS messageipaddress
					" . ($deljoinsql2 ? ",deletionlog.userid AS del_userid, deletionlog.username AS del_username, deletionlog.reason AS del_reason" : "") . "
					" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
					$hook_query_fields2
				FROM " . TABLE_PREFIX . "visitormessage AS visitormessage
				LEFT JOIN " . TABLE_PREFIX . "user AS user ON (visitormessage.postuserid = user.userid)
				" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
				$deljoinsql2
				$hook_query_joins2
				WHERE " . implode(" AND ", $sql2) . "
				$hook_query_where2
			)
			ORDER BY pmdateline DESC
			");

			while ($message = $db->fetch_array($messages))
			{
				if ($message['state'] == 'visible' AND !$message['messageread'])
				{
					$read_ids[] = $message['vmid'];
				}
				$response_handler =& $factory->create($message, 'Simple');
				$response_handler->cachable = false;
				$messagebits .= $response_handler->construct();
			}

			// our profile and ids that need read
			if ($userinfo['userid'] == $vbulletin->userinfo['userid'] AND !empty($read_ids))
			{
				$db->query_write("UPDATE " . TABLE_PREFIX . "visitormessage SET messageread = 1 WHERE vmid IN (" . implode(',', $read_ids) . ")");

				build_visitor_message_counters($vbulletin->userinfo['userid']);
			}
		}

		if ($messageinfo)
		{
			$show['edit'] = true;
			$show['delete'] = fetch_visitor_message_perm('candeletevisitormessages', $userinfo, $messageinfo);
			$navbits[fetch_seo_url('member', $userinfo)] = $userinfo['username'];
			$navbits[] = $vbphrase['edit_visitor_message'];
		}
		else
		{
			$show['edit'] = false;
			// Don't allow mods to create new messages
			if (!$userinfo['vm_enable'])
			{
				print_no_permission();
			}
			$navbits[fetch_seo_url('member', $userinfo)] = $userinfo['username'];
			$navbits[] = $vbphrase['post_new_visitor_message'];
		}

		$navbits = construct_navbits(array(
			fetch_seo_url('member', $userinfo) => $userinfo['username'],
			'' => ($messageinfo ? $vbphrase['edit_visitor_message'] : $vbphrase['post_new_visitor_message'])
		));
		$navbar = render_navbar_template($navbits);

		($hook = vBulletinHook::fetch_hook('visitor_message_form_complete')) ? eval($hook) : false;

		// complete
		$templater = vB_Template::create('visitormessage_editor');
			$templater->register_page_templates();
			$templater->register('checked', $checked);
			$templater->register('disablesmiliesoption', $disablesmiliesoption);
			$templater->register('editorid', $editorid);
			$templater->register('human_verify', $human_verify);
			$templater->register('messagearea', $messagearea);
			$templater->register('messagebits', $messagebits);
			$templater->register('messageinfo', $messageinfo);
			$templater->register('navbar', $navbar);
			$templater->register('pagetitle', $pagetitle);
			$templater->register('posthash', $posthash);
			$templater->register('postpreview', $postpreview);
			$templater->register('userinfo', $userinfo);
			$templater->register('usernamecode', $usernamecode);
		print_output($templater->render());
	}
}

if ($_POST['do'] == 'deletemessage')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'deletemessage' => TYPE_STR,
		'reason'        => TYPE_STR,
	));

	if (!fetch_visitor_message_perm('candeletevisitormessages', $userinfo, $messageinfo))
	{
		print_no_permission();
	}

	if ($vbulletin->GPC['deletemessage'] != '')
	{
		if ($vbulletin->GPC['deletemessage'] == 'remove' AND can_moderate(0, 'canremovevisitormessages'))
		{
			$hard_delete = true;
		}
		else
		{
			$hard_delete = false;
		}

		$dataman =& datamanager_init('VisitorMessage', $vbulletin, ERRTYPE_STANDARD);
		$dataman->set_existing($messageinfo);
		$dataman->set_info('hard_delete', $hard_delete);
		$dataman->set_info('reason', $vbulletin->GPC['reason']);

		$dataman->delete();
		unset($dataman);

		if ($messageinfo['postuserid'] != $vbulletin->userinfo['userid'] AND can_moderate(0, 'candeletevisitormessages'))
		{
			require_once(DIR . '/includes/functions_log_error.php');
			log_moderator_action($messageinfo,
				($hard_delete ? 'vm_by_x_for_y_removed' : 'vm_by_x_for_y_soft_deleted'),
				array($messageinfo['postusername'], $userinfo['username'])
			);
		}

		$vbulletin->url = fetch_seo_url('member', $userinfo, array('tab' => 'visitor_messaging'));
		print_standard_redirect('visitormessagedelete');
	}
	else
	{
		$vbulletin->url = fetch_seo_url('member', $userinfo);
		print_standard_redirect('visitormessage_nodelete');
	}
}

// ############################### start retrieve ip ###############################
if ($_REQUEST['do'] == 'viewip')
{
	// check moderator permissions for getting ip
	if (!can_moderate(0, 'canviewips'))
	{
		print_no_permission();
	}

	if (!$messageinfo['vmid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['message'], $vbulletin->options['contactuslink'])));
	}

	$messageinfo['hostaddress'] = @gethostbyaddr(long2ip($messageinfo['ipaddress']));

	($hook = vBulletinHook::fetch_hook('visitor_message_getip')) ? eval($hook) : false;

	eval(standard_error(fetch_error('thread_displayip', long2ip($messageinfo['ipaddress']), htmlspecialchars_uni($messageinfo['hostaddress'])), '', 0));
}

// ############################### start report ###############################
if ($_REQUEST['do'] == 'report' OR $_POST['do'] == 'sendemail')
{
	require_once(DIR . '/includes/class_reportitem.php');

	if (!$vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	$reportthread = ($rpforumid = $vbulletin->options['rpforumid'] AND $rpforuminfo = fetch_foruminfo($rpforumid));
	$reportemail = ($vbulletin->options['enableemail'] AND $vbulletin->options['rpemail']);

	if (!$reportthread AND !$reportemail)
	{
		eval(standard_error(fetch_error('emaildisabled')));
	}

	$reportobj = new vB_ReportItem_VisitorMessage($vbulletin);
	$reportobj->set_extrainfo('user', $userinfo);
	$perform_floodcheck = $reportobj->need_floodcheck();

	if ($perform_floodcheck)
	{
		$reportobj->perform_floodcheck_precommit();
	}

	if (!$messageinfo['vmid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['message'], $vbulletin->options['contactuslink'])));
	}

	if (
		($messageinfo['state'] == 'moderation' AND !fetch_visitor_message_perm('canmoderatevisitormessages', $userinfo, $messageinfo) AND $messageinfo['postuserid'] != $vbulletin->userinfo['userid'])
		OR ($messageinfo['state'] == 'deleted' AND !fetch_visitor_message_perm('candeletevisitormessages', $userinfo, $messageinfo)))
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['message'], $vbulletin->options['contactuslink'])));
	}

	($hook = vBulletinHook::fetch_hook('report_start')) ? eval($hook) : false;

	if ($_REQUEST['do'] == 'report')
	{
		// draw nav bar
		$navbits = array();
		$navbits[fetch_seo_url('member', $userinfo)] = $userinfo['username'];
		$navbits[''] = $vbphrase['report_bad_visitor_message'];
		$navbits = construct_navbits($navbits);

		$usernamecode = vB_Template::create('newpost_usernamecode')->render();

		$navbar = render_navbar_template($navbits);
		$url =& $vbulletin->url;

		($hook = vBulletinHook::fetch_hook('report_form_start')) ? eval($hook) : false;

		$forminfo = $reportobj->set_forminfo($messageinfo);
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

		if ($perform_floodcheck)
		{
			$reportobj->perform_floodcheck_commit();
		}

		$reportobj->do_report($vbulletin->GPC['reason'], $messageinfo);

		$url =& $vbulletin->url;
		print_standard_redirect('redirect_reportthanks');
	}

}

if ($_REQUEST['do'] == 'quickedit')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'editorid' => TYPE_NOHTML,
	));

	require_once(DIR . '/includes/class_xml.php');
	require_once(DIR . '/includes/functions_editor.php');

	$vminfo = verify_visitormessage($vbulletin->GPC['vmid']);

	$editorid = construct_edit_toolbar(
		htmlspecialchars_uni($vminfo['pagetext']),
		false,
		'visitormessage',
		true,
		true,
		false,
		'qe',
		$vbulletin->GPC['editorid'],
		array(),
		'content',
		'vBForum_VisitorMessage',
		$vminfo['vmid']
	);

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	$xml->add_group('quickedit');
	$xml->add_tag('editor', process_replacement_vars($messagearea), array(
		'reason'       => '',
		'parsetype'    => 'visitormessage',
		'parsesmilies' => true,
		'mode'         => $show['is_wysiwyg_editor']
	));
	$xml->add_tag('ckeconfig', vB_Ckeditor::getInstance($editorid)->getConfig());
	$xml->close_group();

	$xml->print_xml();
}

($hook = vBulletinHook::fetch_hook('visitor_message_complete')) ? eval($hook) : false;

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 64477 $
|| ####################################################################
\*======================================================================*/
?>
