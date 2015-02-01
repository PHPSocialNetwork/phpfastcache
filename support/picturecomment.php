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
define('THIS_SCRIPT', 'picturecomment');
define('CSRF_PROTECTION', true);
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
	'messaging',
	'cprofilefield',
	'posting',
	'album',
	'socialgroups',
	'user'
);

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
		'picturecomment_editor',
	),
);

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_picturecomment.php');
require_once(DIR . '/includes/functions_album.php');
require_once(DIR . '/includes/functions_user.php');
require_once(DIR . '/includes/functions_socialgroup.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (!$vbulletin->options['pc_enabled'])
{
	print_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'albumid'      => TYPE_UINT,
	'groupid'      => TYPE_UINT,
	'attachmentid' => TYPE_UINT,
	'commentid'    => TYPE_UINT
));

($hook = vBulletinHook::fetch_hook('picture_comment_start')) ? eval($hook) : false;

if (!$vbulletin->userinfo['userid'])
{
	print_no_permission();
}

if (!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_albums'])
	OR !($permissions['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['canviewalbum'])
)
{
	print_no_permission();
}

$navbits = array();

// checks for specific types
if ($vbulletin->GPC['albumid'])
{
	if (!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers']))
	{
		print_no_permission();
	}

	$albuminfo = fetch_albuminfo($vbulletin->GPC['albumid']);
	if (!$albuminfo)
	{
		standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
	}

	if (!can_view_profile_section($albuminfo['userid'], 'albums'))
	{
		// private album that we can not see
		standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
	}

	if ($albuminfo['state'] == 'private' AND !can_view_private_albums($albuminfo['userid']))
	{
		// private album that we can not see
		standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
	}
	else if ($albuminfo['state'] == 'profile' AND !can_view_profile_albums($albuminfo['userid']))
	{
		// profile album that we can not see
		standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
	}

	$pictureinfo = fetch_pictureinfo($vbulletin->GPC['attachmentid'], $vbulletin->GPC['albumid']);

	$navbits = array(
		fetch_seo_url('member', $albuminfo) => construct_phrase($vbphrase['xs_profile'], $albuminfo['username']),
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$albuminfo[userid]" => $vbphrase['albums'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$albuminfo[albumid]" => $albuminfo['title_html']
	);
}
else if ($vbulletin->GPC['groupid'])
{
	$group = fetch_socialgroupinfo($vbulletin->GPC['groupid']);
	if (!$group)
	{
		standard_error(fetch_error('invalidid', $vbphrase['social_group'], $vbulletin->options['contactuslink']));
	}

	if (!($group['options'] & $vbulletin->bf_misc_socialgroupoptions['enable_group_albums']))
	{
		print_no_permission();
	}

	if ($group['membertype'] != 'member' AND !can_moderate(0, 'caneditalbumpicture'))
	{
		if ($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canjoingroups'])
		{
			standard_error(fetch_error('must_be_group_member_view_add_pictures_join_x', fetch_seo_url('group', $group)));
		}
		else
		{
			standard_error(fetch_error('must_be_group_member_view_add_pictures'));
		}
	}

	$pictureinfo = fetch_socialgroup_picture($vbulletin->GPC['attachmentid'], $vbulletin->GPC['groupid']);

	$navbits = array(
		fetch_seo_url('grouphome', array()) => $vbphrase['social_groups'],
		fetch_seo_url('group', $group) => $group['name'],
		fetch_seo_url('group', $group, array('do', 'grouppictures')) => $vbphrase['pictures']
	);
}
else
{
	$pictureinfo = array();
}

if (!$pictureinfo OR $pictureinfo['state'] == 'moderation')
{
	standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
}

if ($vbulletin->GPC['commentid'])
{
	$commentinfo = fetch_picturecommentinfo($pictureinfo['filedataid'], $pictureinfo['userid'], $vbulletin->GPC['commentid']);
	if (!$commentinfo)
	{
		standard_error(fetch_error('invalidid', $vbphrase['comment'], $vbulletin->options['contactuslink']));
	}
}
else
{
	$commentinfo = array();
}

$canpostmessage = ($vbulletin->userinfo['userid'] AND
	$vbulletin->userinfo['permissions']['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['canpiccomment']
);

($hook = vBulletinHook::fetch_hook('picture_comment_start2')) ? eval($hook) : false;

if ($_REQUEST['do'] == 'message')
{
	if ($commentinfo AND !fetch_user_picture_message_perm('caneditmessages', $pictureinfo, $commentinfo))
	{
		print_no_permission();
	}
	else if (!$commentinfo AND !$canpostmessage)
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
		));

		($hook = vBulletinHook::fetch_hook('picture_comment_post_start')) ? eval($hook) : false;

		// unwysiwygify the incoming data
		if ($vbulletin->GPC['wysiwyg'])
		{
			require_once(DIR . '/includes/class_wysiwygparser.php');
			$html_parser = new vB_WysiwygHtmlParser($vbulletin);
			$vbulletin->GPC['message'] = $html_parser->parse_wysiwyg_html_to_bbcode($vbulletin->GPC['message'], $vbulletin->options['allowhtml']);
		}

		// parse URLs in message text
		if ($vbulletin->options['allowbbcode'] AND $vbulletin->GPC['parseurl'])
		{
			require_once(DIR . '/includes/functions_newpost.php');
			$vbulletin->GPC['message'] = convert_url_to_bbcode($vbulletin->GPC['message']);
		}

		$message = array(
			'message'        => $vbulletin->GPC['message'],
			'attachmentid'   => $pictureinfo['attachmentid'],
			'userid'         => $vbulletin->userinfo['userid'],
			'postuserid'     => $vbulletin->userinfo['userid'],
			'disablesmilies' => $vbulletin->GPC['disablesmilies'],
			'parseurl'       => $vbulletin->GPC['parseurl'],
		);

		if ($vbulletin->GPC['ajax'])
		{
			$message['message'] = convert_urlencoded_unicode($message['message']);
		}

		$dataman =& datamanager_init('PictureComment', $vbulletin, ERRTYPE_ARRAY);
		if ($pictureuser = fetch_userinfo($pictureinfo['userid']))
		{
			$dataman->set_info('pictureuser', $pictureuser);
		}

		if ($commentinfo)
		{
			$show['edit'] = true;
			$dataman->set_existing($commentinfo);
		}
		else
		{
			if (($vbulletin->options['pc_moderation'] OR !($vbulletin->userinfo['permissions']['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['commentfollowforummoderation'])) AND !fetch_user_picture_message_perm('canmoderatemessages', $pictureinfo))
			{
				$dataman->set('state', 'moderation');
			}
			if ($vbulletin->userinfo['userid'] == 0)
			{
				$dataman->setr('username', $vbulletin->GPC['username']);
			}
			$dataman->set('filedataid', $pictureinfo['filedataid']);
			$dataman->set('userid', $pictureinfo['userid']);
			$dataman->set('postuserid', $vbulletin->userinfo['userid']);

			if ($vbulletin->GPC['albumid'])
			{
				$dataman->set('sourcecontentid', $vbulletin->GPC['albumid']);
				$dataman->set('sourcecontenttypeid', vB_Types::instance()->getContentTypeID('vBForum_Album'));
			}
			else
			{
				$dataman->set('sourcecontentid', $vbulletin->GPC['groupid']);
				$dataman->set('sourcecontenttypeid', vB_Types::instance()->getContentTypeID('vBForum_SocialGroup'));
			}
			$dataman->set('sourceattachmentid', $vbulletin->GPC['attachmentid']);
		}

		$dataman->set_info('pictureinfo', $pictureinfo);
		$dataman->set_info('preview', $vbulletin->GPC['preview']);
		$dataman->setr('pagetext', $message['message']);
		$dataman->set('allowsmilie', !$message['disablesmilies']);

		$dataman->pre_save();

		if ($vbulletin->GPC['fromquickcomment'] AND $vbulletin->GPC['preview'])
		{
			$dataman->errors = array();
		}

		require_once(DIR . '/includes/class_socialmessageparser.php');
		$pmparser = new vB_PictureCommentParser($vbulletin, fetch_tag_list());
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
				$preview = process_picture_comment_preview($message);
			}
			$_GET['do'] = 'message';
		}
		else
		{
			$commentid = $dataman->save();

			if ($commentinfo)
			{
				clear_autosave_text('vBForum_PictureComment', $commentinfo['commentid'], 0, $vbulletin->userinfo['userid']);
			}
			else
			{
				clear_autosave_text('vBForum_PictureComment', 0, $pictureinfo['attachmentid'], $vbulletin->userinfo['userid']);
			}

			if ($commentinfo AND $comentinfo['postuserid'] != $vbulletin->userinfo['userid'] AND can_moderate(0, 'caneditpicturecomments'))
			{
				require_once(DIR . '/includes/functions_log_error.php');
				log_moderator_action($pictureinfo, 'pc_by_x_on_y_edited',
					array($commentinfo['postusername'], fetch_trimmed_title($pictureinfo['caption'], 50))
				);
			}

			if ($vbulletin->GPC['ajax'])
			{
				require_once(DIR . '/includes/class_xml.php');
				$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
				$xml->add_group('commentbits');

				$state = array('visible');
				$state_or = array();
				if (fetch_user_picture_message_perm('canmoderatemessages', $pictureinfo))
				{
					$state[] = 'moderation';
				}
				else if ($vbulletin->userinfo['userid'])
				{
					$state_or[] = "(picturecomment.postuserid = " . $vbulletin->userinfo['userid'] . " AND state = 'moderation')";
				}

				if (can_moderate(0, 'canmoderatepicturecomments') OR ($vbulletin->userinfo['userid'] == $pictureinfo['userid'] AND $vbulletin->userinfo['permissions']['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['canmanagepiccomment']))
				{
					$state[] = 'deleted';
					$deljoinsql = "LEFT JOIN " . TABLE_PREFIX . "deletionlog AS deletionlog ON (picturecomment.commentid = deletionlog.primaryid AND deletionlog.type = 'picturecomment')";
				}
				else
				{
					$deljoinsql = '';
				}

				$state_or[] = "picturecomment.state IN ('" . implode("','", $state) . "')";

				require_once(DIR . '/includes/class_bbcode.php');
				require_once(DIR . '/includes/class_picturecomment.php');

				$bbcode = new vB_BbCodeParser($vbulletin, fetch_tag_list());
				$factory = new vB_Picture_CommentFactory($vbulletin, $bbcode, $pictureinfo);

				$hook_query_fields = $hook_query_joins = $hook_query_where = '';
				($hook = vBulletinHook::fetch_hook('picture_comment_post_ajax')) ? eval($hook) : false;
				$read_ids = array();

				if ($commentid === true) // Editing a comment
				{
					$commentid = $vbulletin->GPC['commentid'];
				}


				$messages = $db->query_read_slave("
					SELECT
						picturecomment.*, user.*, picturecomment.ipaddress AS messageipaddress
						" . ($deljoinsql ? ",deletionlog.userid AS del_userid, deletionlog.username AS del_username, deletionlog.reason AS del_reason" : "") . "
						" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight, customavatar.width_thumb AS avwidth_thumb, customavatar.height_thumb AS avheight_thumb, customavatar.filedata_thumb" : "") . "
						$hook_query_fields
					FROM " . TABLE_PREFIX . "picturecomment AS picturecomment
					LEFT JOIN " . TABLE_PREFIX . "user AS user ON (picturecomment.postuserid = user.userid)
					" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
					$deljoinsql
					$hook_query_joins
					WHERE
						picturecomment.filedataid = $pictureinfo[filedataid]
							AND
						picturecomment.userid = $pictureinfo[userid]
							AND (" . implode(" OR ", $state_or) . ")
							AND " . (($lastviewed = $vbulletin->GPC['lastcomment']) ?
							"(picturecomment.dateline > $lastviewed OR picturecomment.commentid = $commentid)" :
							"picturecomment.commentid = $commentid"
							) . "
						$hook_query_where
					ORDER BY picturecomment.dateline ASC
				");
				while ($message = $db->fetch_array($messages))
				{
					if ($message['state'] == 'visible' AND !$message['messageread'])
					{
						$read_ids[] = $message['commentid'];
					}

					$response_handler =& $factory->create($message);
					// Shall we pre parse these?
					$response_handler->cachable = false;

					$xml->add_tag('message', process_replacement_vars($response_handler->construct()), array(
						'commentid' => $message['commentid'],
						'visible'   => ($message['state'] == 'visible') ? 1 : 0,
						'bgclass'   => $bgclass,
						'quickedit' => 1
					));
				}

				// our profile and ids that need read
				if ($pictureinfo['userid'] == $vbulletin->userinfo['userid'] AND !empty($read_ids))
				{
					$db->query_write("UPDATE " . TABLE_PREFIX . "picturecomment SET messageread = 1 WHERE commentid IN (" . implode(',', $read_ids) . ")");

					build_picture_comment_counters($vbulletin->userinfo['userid']);
				}

				$xml->add_tag('time', TIMENOW);
				$xml->close_group();
				$xml->print_xml(true);
			}
			else
			{
				($hook = vBulletinHook::fetch_hook('picture_comment_post_complete')) ? eval($hook) : false;


				if ($commentinfo)
				{
					$url_commentid = $commentinfo['commentid'];
					$redirect_phrase = 'picturecomment_editthanks';
				}
				else
				{
					$url_commentid = $commentid;
					$redirect_phrase = 'picturecomment_thanks';
				}

				if ($pictureinfo['groupid'])
				{
					$pagevars = array('do' => 'picture', 'attachmentid' => $pictureinfo['attachmentid'], 'commentid' => $url_commentid);
					$vbulletin->url = fetch_seo_url('group', $pictureinfo, $pagevars) . "#picturecomment_$url_commentid";
				}
				else
				{
					$vbulletin->url = 'album.php?' . $vbulletin->session->vars['sessionurl'] .
						"albumid=$pictureinfo[albumid]&amp;attachmentid=$pictureinfo[attachmentid]&amp;commentid=$url_commentid#picturecomment_$url_commentid";
				}

				print_standard_redirect($redirect_phrase, true, true);
			}
		}
	}

	if ($_GET['do'] == 'message')
	{
		require_once(DIR . '/includes/functions_editor.php');

		($hook = vBulletinHook::fetch_hook('picture_comment_form_start')) ? eval($hook) : false;

		if (defined('MESSAGEPREVIEW'))
		{
			$postpreview =& $preview;
			$message['message'] = htmlspecialchars_uni($message['message']);

			require_once(DIR . '/includes/functions_newpost.php');
			construct_checkboxes($message);
		}
		else if ($commentinfo)
		{
			require_once(DIR . '/includes/functions_newpost.php');
			construct_checkboxes(
				array(
					'disablesmilies' => (!$commentinfo['allowsmilie']),
					'parseurl'       => 1,
				)
			);
			$message['message'] = htmlspecialchars_uni($commentinfo['pagetext']);
		}
		else
		{
			$message['message'] = '';
		}

		$editorid = construct_edit_toolbar(
			$message['message'],
			false,
			'picturecomment',
			$vbulletin->options['allowsmilies'],
			true,
			false,
			'fe',
			'',
			array(),
			'content',
			'vBForum_PictureComment',
			$commentinfo['commentid'] ? $commentinfo['commentid'] : 0,
			$commentinfo ? 0 : $pictureinfo['attachmentid'],
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
		$show['physicaldeleteoption'] = can_moderate(0, 'canremovepicturecomments');

		if ($commentinfo)
		{
			$show['edit'] = true;
			$show['delete'] = fetch_user_picture_message_perm('candeletemessages', $pictureinfo, $commentinfo);
			$navbits[] = $vbphrase['edit_picture_comment'];
		}
		else
		{
			$navbits[] = $vbphrase['post_new_picture_comment'];
		}

		$navbits = construct_navbits($navbits);
		$navbar = render_navbar_template($navbits);

		($hook = vBulletinHook::fetch_hook('picture_comment_form_complete')) ? eval($hook) : false;

		// complete
		$templater = vB_Template::create('picturecomment_editor');
			$templater->register_page_templates();
			$templater->register('albuminfo', $albuminfo);
			$templater->register('checked', $checked);
			$templater->register('commentinfo', $commentinfo);
			$templater->register('disablesmiliesoption', $disablesmiliesoption);
			$templater->register('editorid', $editorid);
			$templater->register('messagearea', $messagearea);
			$templater->register('navbar', $navbar);
			$templater->register('pagetitle', $pagetitle);
			$templater->register('pictureinfo', $pictureinfo);
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
		'delete' => TYPE_BOOL,
		'deletemessage' => TYPE_STR,
		'reason'        => TYPE_STR,
	));

	if (!fetch_user_picture_message_perm('candeletemessages', $pictureinfo, $commentinfo))
	{
		print_no_permission();
	}

	if ($pictureinfo['groupid'])
	{
		$vbulletin->url = fetch_seo_url('group', $pictureinfo, array('do' => 'picture', 'attachmentid' => $pictureinfo['attachmentid']));
	}
	else
	{
		$vbulletin->url = 'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$pictureinfo[albumid]&amp;attachmentid=$pictureinfo[attachmentid]";
	}

	if ($vbulletin->GPC['delete'])
	{
		if ($vbulletin->GPC['deltype'] == 'remove' AND can_moderate(0, 'canremovepicturecomments'))
		{
			$hard_delete = true;
		}
		else
		{
			$hard_delete = false;
		}

		$dataman =& datamanager_init('PictureComment', $vbulletin, ERRTYPE_STANDARD);
		$dataman->set_existing($commentinfo);
		if ($pictureuser = fetch_userinfo($pictureinfo['userid']))
		{
			$dataman->set_info('pictureuser', $pictureuser);
		}

		$dataman->set_info('pictureinfo', $pictureinfo);
		$dataman->set_info('hard_delete', $hard_delete);
		$dataman->set_info('reason', $vbulletin->GPC['reason']);

		$dataman->delete();
		unset($dataman);

		if ($comentinfo['postuserid'] != $vbulletin->userinfo['userid']
			AND (can_moderate(0, 'candeletepicturecomments') OR can_moderate(0, 'canremovepicturecomments'))
		)
		{
			require_once(DIR . '/includes/functions_log_error.php');
			log_moderator_action($pictureinfo,
				($hard_delete ? 'pc_by_x_on_y_removed' : 'pc_by_x_on_y_soft_deleted'),
				array($commentinfo['postusername'], fetch_trimmed_title($pictureinfo['caption'], 50))
			);
		}

		print_standard_redirect('picturecomment_deleted');
	}
	else
	{
		print_standard_redirect('picturecomment_nodelete');
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

	if (!$commentinfo['commentid'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['comment'], $vbulletin->options['contactuslink']));
	}

	$commentinfo['hostaddress'] = @gethostbyaddr(long2ip($commentinfo['ipaddress']));

	($hook = vBulletinHook::fetch_hook('picture_comment_getip')) ? eval($hook) : false;

	standard_error(fetch_error('thread_displayip', long2ip($commentinfo['ipaddress']), htmlspecialchars_uni($commentinfo['hostaddress'])), '', 0);
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
		standard_error(fetch_error('emaildisabled'));
	}

	$reportobj = new vB_ReportItem_PictureComment($vbulletin);
	$reportobj->set_extrainfo('picture', $pictureinfo);
	$reportobj->set_extrainfo('album', $albuminfo);
	$reportobj->set_extrainfo('group', $group);
	$perform_floodcheck = $reportobj->need_floodcheck();

	if ($perform_floodcheck)
	{
		$reportobj->perform_floodcheck_precommit();
	}

	if (!$commentinfo['commentid'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['comment'], $vbulletin->options['contactuslink']));
	}

	if (
		($commentinfo['state'] == 'moderation' AND !fetch_user_picture_message_perm('canmoderatemessages', $pictureinfo) AND $commentinfo['postuserid'] != $vbulletin->userinfo['userid'])
		OR ($commentinfo['state'] == 'deleted' AND !fetch_user_picture_message_perm('candeletemessages', $pictureinfo, $commentinfo)))
	{
		standard_error(fetch_error('invalidid', $vbphrase['comment'], $vbulletin->options['contactuslink']));
	}

	($hook = vBulletinHook::fetch_hook('report_start')) ? eval($hook) : false;

	if ($_REQUEST['do'] == 'report')
	{
		// draw nav bar
		$navbits[''] = $vbphrase['report_picture_comment'];
		$navbits = construct_navbits($navbits);

		$usernamecode = vB_Template::create('newpost_usernamecode')->render();

		$navbar = render_navbar_template($navbits);
		$url =& $vbulletin->url;

		($hook = vBulletinHook::fetch_hook('report_form_start')) ? eval($hook) : false;

		$forminfo = $reportobj->set_forminfo($commentinfo);
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
			standard_error(fetch_error('noreason'));
		}

		if ($perform_floodcheck)
		{
			$reportobj->perform_floodcheck_commit();
		}

		$reportobj->do_report($vbulletin->GPC['reason'], $commentinfo);

		$url =& $vbulletin->url;
		print_standard_redirect('redirect_reportthanks');
	}

}

if ($_POST['do'] == 'quickedit')
{
	if ($commentinfo AND !fetch_user_picture_message_perm('caneditmessages', $pictureinfo, $commentinfo))
	{
		print_no_permission();
	}
	else if (!$commentinfo AND !$canpostmessage)
	{
		print_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'editorid' => TYPE_NOHTML,
	));

	require_once(DIR . '/includes/class_xml.php');
	require_once(DIR . '/includes/functions_editor.php');

	$editorid = construct_edit_toolbar(
		htmlspecialchars_uni($commentinfo['pagetext']),
		false,
		'picturecomment',
		true,
		true,
		false,
		'qe',
		$vbulletin->GPC['editorid'],
		array(),
		'content',
		'vBForum_PictureComment',
		$commentinfo['commentid']
	);

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

	$xml->add_group('quickedit');
	$xml->add_tag('editor', process_replacement_vars($messagearea), array(
		'reason'       => '',
		'parsetype'    => 'picturecomment',
		'parsesmilies' => (true),
		'mode'         => $show['is_wysiwyg_editor']
	));
	$xml->add_tag('ckeconfig', vB_Ckeditor::getInstance($editorid)->getConfig());
	$xml->close_group();

	$xml->print_xml();
}

($hook = vBulletinHook::fetch_hook('picture_comment_complete')) ? eval($hook) : false;

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63231 $
|| ####################################################################
\*======================================================================*/
?>
