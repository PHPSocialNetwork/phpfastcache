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
define('THIS_SCRIPT', 'album');
define('CSRF_PROTECTION', true);
define('GET_EDIT_TEMPLATES', 'picture');

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('album', 'user', 'posting');

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'memberinfo_usercss'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'addalbum' => array(
		'album_edit'
	),
	'editpictures' => array(
		'album_picture_edit',
		'album_picture_editbit',
	),
	'addgroup' => array(
		'album_addgroup',
		'album_addgroup_groupbit'
	),
	'report' => array(
		'newpost_usernamecode',
		'reportitem',
	),
	'picture' => array(
		'album_pictureview',
		'picturecomment_commentarea',
		'picturecomment_form',
		'picturecomment_message',
		'picturecomment_message_deleted',
		'picturecomment_message_ignored',
		'picturecomment_message_global_ignored',
	),
	'album' => array(
		'album_picturelist',
		'album_picturebit',
		'album_picturebit_checkbox'
	),
	'user' => array(
		'album_list',
		'albumbit'
	),
	'moderated' => array(
		'album_moderatedcomments',
		'picturecomment_message_moderatedview'
	),
	'unread' => array(
		'album_picturebit_unread',
		'album_unreadcomments'
	),
	'latest' => array(
		'album_latestbit',
		'album_list'
	),
	'overview' => array(
		'album_latestbit',
		'album_list',
		'albumbit'
	)
);
$actiontemplates['updatealbum'] = $actiontemplates['editalbum'] = $actiontemplates['addalbum'];

if (empty($_REQUEST['do']))
{
	if (!empty($_REQUEST['albumid']))
	{
		if (!empty($_REQUEST['attachmentid']))
		{
			$_REQUEST['do'] = 'picture';
		}
		else
		{
			$_REQUEST['do'] = 'album';
		}
	}
	else if ($_REQUEST['u'] OR $_REQUEST['userid'])
	{
		$_REQUEST['do'] = 'user';
	}
	else if($_REQUEST['do'] != 'latest')
	{
		$_REQUEST['do'] = 'overview';
	}
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_album.php');
require_once(DIR . '/includes/functions_user.php');

$contenttypeid = vB_Types::instance()->getContentTypeID('vBForum_Album');

$vbulletin->input->clean_array_gpc('r', array(
	'albumid'      => TYPE_UINT,
	'attachmentid' => TYPE_UINT,
	'userid'       => TYPE_UINT,
));

$canviewalbums = (
	$vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_albums']
		AND
	$permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers']
		AND
	$permissions['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['canviewalbum']
);
$canviewgroups = (
	$vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_groups']
		AND
	$vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups']
);

if (!$canviewalbums)
{
	// check for do=='unread', allow if user can view groups since the picture comment may be from there
	if (
		$_REQUEST['do'] != 'unread'
			OR
		!$canviewgroups
	)
	{
		print_no_permission();
	}
}

$moderatedpictures = (
	(
		$vbulletin->options['albums_pictures_moderation']
			OR
		!($vbulletin->userinfo['permissions']['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['picturefollowforummoderation'])
	)
		AND
	!can_moderate(0, 'canmoderatepictures')
);

($hook = vBulletinHook::fetch_hook('album_start_precheck')) ? eval($hook) : false;

// if we specify an album, make sure our user context is sane
if ($vbulletin->GPC['albumid'])
{
	$albuminfo = fetch_albuminfo($vbulletin->GPC['albumid']);
	if (!$albuminfo)
	{
		standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
	}

	$vbulletin->GPC['userid'] = $albuminfo['userid'];
}

if ($vbulletin->GPC['attachmentid'])
{
	// todo
	$pictureinfo = fetch_pictureinfo($vbulletin->GPC['attachmentid'], $albuminfo['albumid']);
	if (!$pictureinfo)
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}
}

if ($_REQUEST['do'] == 'overview')
{
	if ((!$vbulletin->GPC['userid'] AND !$vbulletin->userinfo['userid']) OR !($vbulletin->userinfo['permissions']['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['canalbum']))
	{
		$_REQUEST['do'] = 'latest';
	}
}

// don't need userinfo if we're only viewing latest
if ($_REQUEST['do'] != 'latest')
{
	if (!$vbulletin->GPC['userid'])
	{
		if (!($vbulletin->GPC['userid'] = $vbulletin->userinfo['userid']))
		{
			print_no_permission();
		}
	}

	$userinfo = verify_id('user', $vbulletin->GPC['userid'], 1, 1, FETCH_USERINFO_USERCSS);

	cache_permissions($userinfo, false);
	if (!can_moderate(0, 'caneditalbumpicture') AND !($userinfo['permissions']['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['canalbum']))
	{
		print_no_permission();
	}

	if (!can_view_profile_section($userinfo['userid'], 'albums'))
	{
		// private album that we can not see
		standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
	}

	// determine if we can see this user's private albums and run the correct permission checks
	if (!empty($albuminfo))
	{
		if ($albuminfo['state'] == 'private' AND !can_view_private_albums($userinfo['userid']))
		{
			// private album that we can not see
			standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
		}
		else if ($albuminfo['state'] == 'profile' AND !can_view_profile_albums($userinfo['userid']))
		{
			// profile album that we can not see
			standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
		}
	}

	$usercss = construct_usercss($userinfo, $show['usercss_switch']);
	$show['usercss_switch'] = ($show['usercss_switch'] AND $vbulletin->userinfo['userid'] != $userinfo['userid']);
	construct_usercss_switch($show['usercss_switch'], $usercss_switch_phrase);
}

($hook = vBulletinHook::fetch_hook('album_start_postcheck')) ? eval($hook) : false;

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if ($_POST['do'] == 'killalbum')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'delete' => TYPE_BOOL
	));

	if ($vbulletin->userinfo['userid'] != $albuminfo['userid'] AND !can_moderate(0, 'candeletealbumpicture'))
	{
		print_no_permission();
	}

	if (!$vbulletin->GPC['delete'])
	{
		standard_error(fetch_error('no_checkbox_item_not_deleted'));
	}

	$albumdata =& datamanager_init('Album', $vbulletin, ERRTYPE_STANDARD);
	$albumdata->set_existing($albuminfo);
	$albumdata->delete();

	if ($albuminfo['userid'] != $vbulletin->userinfo['userid'] AND can_moderate(0, 'caneditalbumpicture'))
	{
		require_once(DIR . '/includes/functions_log_error.php');
		log_moderator_action($albuminfo, 'album_x_by_y_deleted',
			array($albuminfo['title'], $userinfo['username'])
		);
	}
	unset($albumdata);

	$vbulletin->url = 'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$albuminfo[userid]";
	print_standard_redirect('album_deleted');
}

// #######################################################################
if ($_POST['do'] == 'updatealbum' OR $_REQUEST['do'] == 'addalbum' OR $_REQUEST['do'] == 'editalbum')
{
	if (empty($albuminfo['albumid']))
	{
		// adding new, can only add in your own
		if ($userinfo['userid'] != $vbulletin->userinfo['userid'])
		{
			print_no_permission();
		}
	}
	else
	{
		// editing: only in your own or moderators
		if ($userinfo['userid'] != $vbulletin->userinfo['userid'] AND !can_moderate(0, 'caneditalbumpicture'))
		{
			print_no_permission();
		}
	}
}

// #######################################################################
if ($_POST['do'] == 'updatealbum')
{
	$vbulletin->input->clean_array_gpc('p', array(
		// albumid cleaned at the beginning
		'title'       => TYPE_NOHTML,
		'description' => TYPE_NOHTML,
		'albumtype'   => TYPE_STR
	));

	$albumdata =& datamanager_init('Album', $vbulletin, ERRTYPE_ARRAY);
	if (!empty($albuminfo['albumid']))
	{
		$albumdata->set_existing($albuminfo);
		$albumdata->rebuild_counts();
	}
	else
	{
		$albumdata->set('userid', $vbulletin->userinfo['userid']);
	}

	$albumdata->set('title', $vbulletin->GPC['title']);
	$albumdata->set('description', $vbulletin->GPC['description']);

	// if changing an album to a profile album, be sure we actually have perm to change it
	if ($vbulletin->GPC['albumtype'] == 'profile' AND $albuminfo['state'] != 'profile')
	{
		$creator = fetch_userinfo($albumdata->fetch_field('userid'));
		cache_permissions($creator);

		$can_profile_album = (
			$vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_profile_styling']
			AND $creator['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditbgimage']
		);

		if (!$can_profile_album)
		{
			$vbulletin->GPC['albumtype'] = 'public';
		}
	}
	$albumdata->set('state', $vbulletin->GPC['albumtype']);

	$albumdata->pre_save();

	($hook = vBulletinHook::fetch_hook('album_album_update')) ? eval($hook) : false;

	if ($albumdata->errors)
	{
		$formdata = array_merge($albumdata->existing, $albumdata->album);

		require_once(DIR . '/includes/functions_newpost.php');
		$errortable = construct_errors($albumdata->errors);

		$_REQUEST['do'] = 'addalbum';
		define('PREVIEW_ERRORS', true);
	}
	else
	{
		$albumdata->save();

		if (!empty($albuminfo['albumid']) AND $albuminfo['userid'] != $vbulletin->userinfo['userid'] AND can_moderate(0, 'caneditalbumpicture'))
		{
			require_once(DIR . '/includes/functions_log_error.php');
			log_moderator_action($albuminfo, 'album_x_by_y_edited',
				array($albuminfo['title'], $userinfo['username'])
			);
		}

		$vbulletin->url = 'album.php?' . $vbulletin->session->vars['sessionurl'] . 'albumid=' . $albumdata->fetch_field('albumid');
		print_standard_redirect('album_added_edited');
	}

	unset($albumdata);
}

// #######################################################################
if ($_REQUEST['do'] == 'addalbum' OR $_REQUEST['do'] == 'editalbum')
{
	// $formdata will fall through on preview
	if (empty($formdata))
	{
		if (!empty($albuminfo))
		{
			$formdata = $albuminfo;
		}
		else
		{
			$formdata = array(
				'albumid'     => 0,
				'title'       => '',
				'description' => '',
				'state'       => 'public',
				'userid'      => $vbulletin->userinfo['userid']
			);
		}
	}

	$formdata['albumtype_' . $formdata['state']] = 'checked="checked"';

	$show['delete_option'] = (!defined('PREVIEW_ERRORS') AND !empty($albuminfo['albumid']) AND
		($vbulletin->userinfo['userid'] == $albuminfo['userid'] OR can_moderate(0, 'candeletealbumpicture'))
	);

	$show['album_used_in_css'] = false;

	if (!empty($albuminfo['albumid']))
	{
		if ($db->query_first("
			SELECT selector
			FROM " . TABLE_PREFIX . "usercss
			WHERE userid = $albuminfo[userid]
				AND property = 'background_image'
				AND value LIKE '$albuminfo[albumid],%'
			LIMIT 1
		"))
		{
			$show['album_used_in_css'] = true;
		}
	}

	// if permitted to customize profile, or album is already a profile-type, show the profile-type option
	$creator = fetch_userinfo($formdata['userid']);
	cache_permissions($creator);

	$show['albumtype_profile'] = (
		$albuminfo['state'] == 'profile'
		OR (
			$vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_profile_styling']
			AND $creator['permissions']['usercsspermissions'] & $vbulletin->bf_ugp_usercsspermissions['caneditbgimage']
		)
	);

	($hook = vBulletinHook::fetch_hook('album_album_edit')) ? eval($hook) : false;

	// navbar and final output
	$navbits = construct_navbits(array(
		'memberlist.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['members_list'],
		fetch_seo_url('member', $userinfo) => $userinfo['username'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]" => $vbphrase['albums'],
		'' => (!empty($albuminfo['albumid']) ? $vbphrase['edit_album'] : $vbphrase['add_album'])
	));
	$navbar = render_navbar_template($navbits);

	$templater = vB_Template::create('album_edit');
		$templater->register_page_templates();
		$templater->register('albuminfo', $albuminfo);
		$templater->register('errortable', $errortable);
		$templater->register('formdata', $formdata);
		$templater->register('navbar', $navbar);
		$templater->register('usercss', $usercss);
		$templater->register('userinfo', $userinfo);
		$templater->register('albumcount', $albumcount);
	print_output($templater->render());
}

// #######################################################################
if ($_POST['do'] == 'updatepictures')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'pictures'          => TYPE_ARRAY,
		'coverattachmentid' => TYPE_UINT,
		'frompicture'       => TYPE_BOOL,
		'posthash'          => TYPE_NOHTML,
		'poststarttime'     => TYPE_UINT,
	));

	if (empty($albuminfo))
	{
		standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
	}

	if (
		$userinfo['userid'] != $vbulletin->userinfo['userid']
			AND
		(
			$vbulletin->GPC['posthash']
				OR
			!can_moderate(0, 'caneditalbumpicture')
		)
	)
	{
		print_no_permission();
	}

	$can_delete = ($vbulletin->userinfo['userid'] == $albuminfo['userid'] OR can_moderate(0, 'candeletealbumpicture'));

	$attachmentids = array_map('intval', array_keys($vbulletin->GPC['pictures']));

	if (!$attachmentids)
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	if ($vbulletin->GPC['posthash'])
	{
		$attachmentids = array();
		if (md5($vbulletin->GPC['poststarttime'] . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']) != $vbulletin->GPC['posthash'])
		{
			standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
		}
		else
		{
			$pictures = $db->query_read("
				SELECT
					attachmentid
				FROM " . TABLE_PREFIX . "attachment
				WHERE
					posthash = '" . $db->escape_string($vbulletin->GPC['posthash']) . "'
						AND
					contenttypeid = $contenttypeid
						AND
					userid = {$vbulletin->userinfo['userid']}
			");
			while ($picture = $db->fetch_array($pictures))
			{
				$attachmentids[] = $picture['attachmentid'];
			}
			if (empty($attachmentids))
			{
				standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
			}
		}
	}

	$new_coverid = 0;
	$cover_moved = false;
	$destinations = array();
	$need_css_rebuild = false;
	$updatecounter = 0;
	$deleted_picture = false;
	$delete_usercss = array();
	$update_usercss = array();

	// Fetch possible destination albums
	$destination_result = $db->query_read("
		SELECT
			albumid, userid, title, coverattachmentid, state
		FROM " . TABLE_PREFIX . "album
		WHERE
			userid = $userinfo[userid]
	");

	$destinations = array();

	if ($db->num_rows($destination_result))
	{
		while ($album = $db->fetch_array($destination_result))
		{
			$destinations[$album['albumid']] = $album;
		}
	}
	$db->free_result($destination_result);

	$picture_sql = $db->query_read("
		SELECT
			a.contentid, a.userid, a.caption, a.state, a.dateline, a.attachmentid, a.contenttypeid,
			filedata.extension, filedata.filesize, filedata.thumbnail_filesize, filedata.filedataid
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS filedata ON (a.filedataid = filedata.filedataid)
		WHERE
			a.contentid = " . ($vbulletin->GPC['posthash'] ? 0 : $albuminfo['albumid']) . "
				AND
			a.attachmentid IN (" . implode(',', $attachmentids) . ")
	");

	while ($picture = $db->fetch_array($picture_sql))
	{
		$album = $vbulletin->GPC['pictures']["$picture[attachmentid]"]['album'];
		$attachdata =& datamanager_init('Attachment', $vbulletin, ERRTYPE_ARRAY, 'attachment');
		$attachdata->set_existing($picture);
		$attachdata->set_info('albuminfo', $albuminfo);
		$attachdata->set_info('destination', $destinations["$album"]);

		($hook = vBulletinHook::fetch_hook('album_picture_update')) ? eval($hook) : false;

		if ($vbulletin->GPC['pictures']["$picture[attachmentid]"]['delete'])
		{
			// if we can't delete, then we're not going to do the update either
			if ($can_delete)
			{
				$attachdata->delete(true, true, 'album', 'photo');
				if ($attachdata->info['have_updated_usercss'])
				{
					$need_css_rebuild = true;
				}

				$deleted_picture = true;

				if (
					$albuminfo['userid'] != $vbulletin->userinfo['userid']
						AND
					can_moderate(0, 'caneditalbumpicture')
				)
				{
					require_once(DIR . '/includes/functions_log_error.php');
					log_moderator_action($picture, 'picture_x_in_y_by_z_deleted',
						array(fetch_trimmed_title($picture['caption'], 50), $albuminfo['title'], $userinfo['username'])
					);
				}
			}
		}
		else
		{
			if ($picture['state'] == 'moderation' AND can_moderate(0, 'canmoderatepictures') AND $vbulletin->GPC['pictures']["$picture[attachmentid]"]['approve'])
			{
				// need to increase picture counter
				$attachdata->set('state', 'visible');
				$updatecounter++;

				// album has been recently updated
				exec_album_updated($vbulletin->userinfo, $albuminfo);
			}


			if (!$vbulletin->GPC['posthash'])
			{
				// only album owner can move pictures
				if ($vbulletin->userinfo['userid'] == $albuminfo['userid'])
				{
					$picture_moved = false;

					if (isset($destinations["$album"]) AND ($album != $albuminfo['albumid']))
					{
						$attachdata->set('contentid', $album);
						$updatecounter = true;
						$picture_moved = $album;
						$destinations["$album"]['moved_pictures'][] = $picture['attachmentid'];
						if ($picture['attachmentid'] == $albuminfo['coverattachmentid'] AND (!$new_coverid))
						{
							$cover_moved = true;
						}
					}
				}
			}
			else
			{
				if (isset($destinations["$album"]) AND ($album != $albuminfo['albumid']))
				{
					$attachdata->set('contentid', $album);
				}
				else
				{
					$attachdata->set('contentid', $albuminfo['albumid']);
				}
				$attachdata->set('posthash', '');
			}

			$attachdata->set('caption', $vbulletin->GPC['pictures']["$picture[attachmentid]"]['caption']);
			$attachdata->save();

			if (!$picture['contentid'])
			{
				$activity = new vB_ActivityStream_Manage('album', 'photo');
				$activity->set('contentid', $picture['attachmentid']);
				$activity->set('userid', $picture['userid']);
				$activity->set('dateline', $picture['dateline']);
				$activity->set('action', 'create');
				$activity->save();
			}

			if (
				$albuminfo['userid'] != $vbulletin->userinfo['userid']
					AND
				$vbulletin->GPC['pictures']["$picture[attachmentid]"]['caption'] != $picture['caption']
					AND
				can_moderate(0, 'caneditalbumpicture')
			)
			{
				require_once(DIR . '/includes/functions_log_error.php');
				log_moderator_action($picture, 'picture_x_in_y_by_z_edited',
					array(fetch_trimmed_title($picture['caption'], 50), $albuminfo['title'], $userinfo['username'])
				);
			}

			if (!$picture_moved)
			{
				if ($picture['attachmentid'] == $vbulletin->GPC['coverattachmentid'] AND $attachdata->fetch_field('state') == 'visible')
				{
					$new_coverid = $picture['attachmentid'];
					$cover_moved = false;
				}
				else if (
					!$vbulletin->GPC['coverattachmentid']
						AND
					!$new_coverid
						AND
					(
						!$albuminfo['coverattachmentid']
							OR
						$cover_moved
					)
						AND
					$attachdata->fetch_field('state') == 'visible'
				)
				{
					// not setting a cover and there's no existing cover -> set to this pic
					$new_coverid = $picture['attachmentid'];
					$cover_moved = false;
				}
			}
		}
	}

	($hook = vBulletinHook::fetch_hook('album_picture_update_complete')) ? eval($hook) : false;

	if ($cover_moved)
	{
		// try and find a new cover
		$new_coverid = $db->query_first("
			SELECT
				attachment.attachmentid
			FROM " . TABLE_PREFIX . "attachment AS attachment
			WHERE
				attachment.contentid = $albuminfo[albumid]
					AND
				attachment.state = 'visible'
					AND
				attachment.contenttypeid = $contenttypeid
			ORDER BY attachment.dateline ASC
			LIMIT 1
		");

		$new_coverid = $new_coverid['attachmentid'] ? $new_coverid['attachmentid'] : 0;
	}

	// update all albums that pictures were moved to
	foreach ($destinations as $albumid => $album)
	{
		if (sizeof($album['moved_pictures']))
		{
			$albumdata =& datamanager_init('Album', $vbulletin, ERRTYPE_SILENT);
			$albumdata->set_existing($album);

			if (!$album['coverattachmentid'])
			{
				$albumdata->set('coverattachmentid', array_shift($album['moved_pictures']));
			}

			$albumdata->rebuild_counts();
			$albumdata->save();
			unset($albumdata);
		}
	}

	$albumdata =& datamanager_init('Album', $vbulletin, ERRTYPE_SILENT);
	$albumdata->set_existing($albuminfo);
	$albumdata->rebuild_counts();
	if ($new_coverid OR $updatecounter)
	{
		if ($new_coverid OR $cover_moved)
		{
			$albumdata->set('coverattachmentid', $new_coverid);
		}
	}
	$albumdata->save();
	unset($albumdata);

	if ($need_css_rebuild)
	{
		require_once(DIR . '/includes/class_usercss.php');
		$usercss = new vB_UserCSS($vbulletin, $albuminfo['userid'], false);
		$usercss->update_css_cache();
	}

	// add to updated list
	if (can_moderate(0, 'canmoderatepictures')
		OR
		(!$vbulletin->options['albums_pictures_moderation']
		 AND
		 ($vbulletin->userinfo['permissions']['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['picturefollowforummoderation']))
		)
	{
		exec_album_updated($vbulletin->userinfo, $albuminfo);
	}


	if ($vbulletin->GPC['frompicture'] AND sizeof($attachmentids) == 1 AND !$deleted_picture)
	{
		$attachmentid = reset($attachmentids);
		$vbulletin->url = 'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=" . ($picture_moved ? $picture_moved : $albuminfo['albumid']) . "&amp;attachmentid=$attachmentid";
	}
	else
	{
		$vbulletin->url = 'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$albuminfo[albumid]";
	}

	print_standard_redirect('pictures_updated');
}

// #######################################################################
if ($_REQUEST['do'] == 'editpictures')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber'     => TYPE_UINT,
		'attachmentids'  => TYPE_ARRAY_UINT,
		'errors'         => TYPE_ARRAY_NOHTML,
		'frompicture'    => TYPE_BOOL
	));

	if (empty($albuminfo))
	{
		standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
	}

	if ($userinfo['userid'] != $vbulletin->userinfo['userid'] AND !can_moderate(0, 'caneditalbumpicture'))
	{
		print_no_permission();
	}

	if ($vbulletin->GPC['attachmentid'])
	{
		$vbulletin->GPC['attachmentids'][] = $vbulletin->GPC['attachmentid'];
	}

	$show['delete_option'] = ($vbulletin->userinfo['userid'] == $albuminfo['userid'] OR can_moderate(0, 'candeletealbumpicture'));

	$display = $db->query_first("
		SELECT
			COUNT(*) AS picturecount
		FROM " . TABLE_PREFIX . "attachment AS a
		WHERE
			a.contentid = $albuminfo[albumid]
				AND
			a.contenttypeid = " . intval($contenttypeid) . "
			" . ($vbulletin->GPC['attachmentids'] ? "AND a.attachmentid IN (" . implode(',', $vbulletin->GPC['attachmentids']) . ")" : '') . "
			" . (!can_moderate(0, 'canmoderatepictures') ? "AND (a.state = 'visible' OR a.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
	");

	if (!$display['picturecount'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	// pagination setup
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}

	//$perpage = $vbulletin->options['album_pictures_perpage'];
	$perpage = 999999; // disable page nav
	$total_pages = max(ceil($display['picturecount'] / $perpage), 1); // 0 pictures still needs an empty page
	$pagenumber = ($vbulletin->GPC['pagenumber'] > $total_pages ? $total_pages : $vbulletin->GPC['pagenumber']);
	$start = ($pagenumber - 1) * $perpage;

	$background_pictures = array();
	$background_picture_sql = $db->query_read("
		SELECT value
		FROM " . TABLE_PREFIX . "usercss
		WHERE userid = $albuminfo[userid]
			AND property = 'background_image'
			AND value LIKE '$albuminfo[albumid],%'
	");
	while ($background_picture = $db->fetch_array($background_picture_sql))
	{
		preg_match('#^(\d+),(\d+)$#', $background_picture['value'], $match);
		$match[2] = intval($match[2]);
		$background_pictures["$match[2]"] = $match[2];
	}

	if ($vbulletin->userinfo['userid'] == $albuminfo['userid'])
	{
		$album_options = '';
		$album_result = $db->query_read("
			SELECT albumid, title
			FROM " . TABLE_PREFIX . "album
			WHERE userid = $userinfo[userid]
		");

		if ($db->num_rows($album_result) > 1)
		{
			while ($album = $db->fetch_array($album_result))
			{
				$optiontitle = $album['title'];
				$optionvalue = $album['albumid'];
				$optionselected = ($album['albumid'] == $albuminfo['albumid']) ? 'selected="selected"' : '';

				$album_options .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
			}

			$show['move_to_album'] = true;
		}
		$db->free_result($album_result);
	}

	$limit = "$start, $perpage";
	$orderby = 'a.dateline DESC';

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('album_picturelist_query')) ? eval($hook) : false;

	$picture_sql = $db->query_read("
		SELECT a.attachmentid, a.userid, a.caption, a.state, a.dateline, fd.filesize, fd.thumbnail_filesize,
		fd.thumbnail_dateline, fd.thumbnail_width, fd.thumbnail_height, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail
		$hook_query_fields
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
		$hook_query_joins
		WHERE a.contentid = $albuminfo[albumid] AND a.contenttypeid = " . intval($contenttypeid) . "
		" . ($vbulletin->GPC['attachmentids'] ? "AND a.attachmentid IN (" . implode(',', $vbulletin->GPC['attachmentids']) . ")" : '') . "
		" . (!can_moderate(0, 'canmoderatepictures') ? "AND (a.state = 'visible' OR a.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
		$hook_query_where
		ORDER BY $orderby
		LIMIT $limit
	");

	$picturebits = '';
	$show['leave_cover'] = true;
	while ($picture = $db->fetch_array($picture_sql))
	{
		if ($picture['attachmentid'] == $albuminfo['coverattachmentid'])
		{
			$show['leave_cover'] = false;
			$cover_checked = ' checked="checked"';
		}
		else
		{
			$cover_checked = '';
		}

		$picture['usedincss'] = isset($background_pictures["$picture[attachmentid]"]);

		$picture['caption_preview'] = fetch_censored_text(fetch_trimmed_title(
			$picture['caption'],
			$vbulletin->options['album_captionpreviewlen']
		));

		$picture['thumburl'] = ($picture['thumbnail_filesize'] ? true : false);
		$picture['dimensions'] = ($picture['thumbnail_width'] ? "width=\"$picture[thumbnail_width]\" height=\"$picture[thumbnail_height]\"" : '');

		($hook = vBulletinHook::fetch_hook('album_picture_editbit')) ? eval($hook) : false;

		$show['album_cover'] = ($picture['state'] == 'visible' OR can_moderate(0, 'canmoderatepictures'));
		$show['approve_option'] = ($picture['state'] == 'moderation' AND can_moderate(0, 'canmoderatepictures'));

		$templater = vB_Template::create('album_picture_editbit');
			$templater->register('albuminfo', $albuminfo);
			$templater->register('album_options', $album_options);
			$templater->register('cover_checked', $cover_checked);
			$templater->register('picture', $picture);
		$picturebits .= $templater->render();
	}

	$pagenav = construct_page_nav($pagenumber, $perpage, $display['picturecount'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "do=editpictures&amp;albumid=$albuminfo[albumid]",
		($vbulletin->GPC['attachmentids'] ? "&amp;attachmentids[]=" . implode('&amp;attachmentids[]=', $vbulletin->GPC['attachmentids']) : '')
	);

	$frompicture = $vbulletin->GPC['frompicture'];

	// error handling
	if ($vbulletin->GPC['errors'])
	{
		$error_file = '';
		foreach ($vbulletin->GPC['errors'] AS $error_name)
		{
			$error_files .= "<li>$error_name</li>";
		}

		$error_message = fetch_error('multiple_pictures_uploaded_errors_file_x', $error_files);
	}
	else
	{
		$error_message = '';
	}

	($hook = vBulletinHook::fetch_hook('album_picture_edit_complete')) ? eval($hook) : false;

	// navbar and final output
	$navbits = construct_navbits(array(
		'memberlist.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['members_list'],
		fetch_seo_url('member', $userinfo) => $userinfo['username'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]" => $vbphrase['albums'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$albuminfo[albumid]" => $albuminfo['title_html'],
		'' => $vbphrase['edit_pictures']
	));
	$navbar = render_navbar_template($navbits);

	$templater = vB_Template::create('album_picture_edit');
		$templater->register_page_templates();
		$templater->register('albuminfo', $albuminfo);
		$templater->register('error_message', $error_message);
		$templater->register('frompicture', $frompicture);
		$templater->register('navbar', $navbar);
		$templater->register('pagenav', $pagenav);
		$templater->register('picturebits', $picturebits);
		$templater->register('usercss', $usercss);
		$templater->register('usercss_switch_phrase', $usercss_switch_phrase);
		$templater->register('userinfo', $userinfo);
	print_output($templater->render());
}

// #######################################################################
if ($_POST['do'] == 'doaddgroupmult')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'groupid'       => TYPE_UINT,
		'attachmentids' => TYPE_ARRAY_UINT,
		'cancel'        => TYPE_STR,
		'pagenumber'    => TYPE_UINT
	));

	if ($vbulletin->GPC['cancel'])
	{
		exec_header_redirect(
			'album.php?' . $vbulletin->session->vars['sessionurl'] . 'albumid=' . $albuminfo['albumid'] .
			($vbulletin->GPC['pagenumber'] > 1 ? '&amp;page=' . $vbulletin->GPC['pagenumber'] : '')
		);
	}

	if (empty($albuminfo))
	{
		standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
	}

	if (
		$userinfo['userid'] != $vbulletin->userinfo['userid']
			OR
		!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_groups'])
			OR
		!($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups'])
	)
	{
		print_no_permission();
	}

	if (!$vbulletin->GPC['groupid'])
	{
		standard_error(fetch_error('must_select_valid_group_add_pictures'));
	}

	if (empty($vbulletin->GPC['attachmentids']))
	{
		standard_error(fetch_error('must_select_valid_pictures_add_group'));
	}

	require_once(DIR . '/includes/functions_socialgroup.php');
	$group = fetch_socialgroupinfo($vbulletin->GPC['groupid']);

	if (!$group OR $group['membertype'] != 'member' OR !($group['options'] & $vbulletin->bf_misc_socialgroupoptions['enable_group_albums']))
	{
		print_no_permission();
	}

	if ($vbulletin->GPC['attachmentids'])
	{
		$picture_sql = $db->query_read("
			SELECT
				attachment.filedataid, attachment.filename, attachment.caption
			FROM " . TABLE_PREFIX . "attachment AS attachment
			WHERE
				attachment.contentid = $albuminfo[albumid]
					AND
				attachment.attachmentid IN (" . implode(',', $vbulletin->GPC['attachmentids']) . ")
					AND
				attachment.state = 'visible'
					AND
				attachment.contenttypeid = " . intval(vB_Types::instance()->getContentTypeID('vBForum_Album')) . "
					AND
				attachment.userid = $userinfo[userid]
		");

		$pictures = false;
		while ($picture = $db->fetch_array($picture_sql))
		{
			// Yes this does single inserts on purpose
			// An atomic check since we can't put an unique key on the attachment table but we don't want the same attachment getting into the group more than once.
			if (!($db->query_first("
				SELECT
					attachmentid
				FROM " . TABLE_PREFIX . "attachment
				WHERE
					contenttypeid = " . intval(vB_Types::instance()->getContentTypeID('vBForum_SocialGroup')) . "
						AND
					userid = $userinfo[userid]
						AND
					contentid = $group[groupid]
						AND
					filedataid = $picture[filedataid]
			")))
			{
				$db->query_write("
					INSERT IGNORE INTO " . TABLE_PREFIX . "attachment
						(contenttypeid, contentid, userid, dateline, filedataid, filename, caption)
					VALUES
						(" . intval(vB_Types::instance()->getContentTypeID('vBForum_SocialGroup')) . ", $group[groupid], $userinfo[userid], " . TIMENOW . ", $picture[filedataid], '" . $db->escape_string($picture['filename']) . "', '" . $db->escape_string($picture['caption']) . "')
				");
				$pictures = true;
				$attachmentid = $db->insert_id();

				// Activity Stream insert
				$activity = new vB_ActivityStream_Manage('socialgroup', 'photo');
				$activity->set('contentid', $attachmentid);
				$activity->set('userid', $userinfo['userid']);
				$activity->set('dateline', TIMENOW);
				$activity->set('action', 'create');
				$activity->save();
			}
		}

		($hook = vBulletinHook::fetch_hook('album_picture_doaddgroups_multiple')) ? eval($hook) : false;

		if ($pictures)
		{
			$groupdm =& datamanager_init('SocialGroup', $vbulletin, ERRTYPE_STANDARD);
			$groupdm->set_existing($group);
			$groupdm->rebuild_picturecount();
			$groupdm->save();
		}
	}

	$vbulletin->url = fetch_seo_url('group', $group, array('do' => 'grouppictures'));
	print_standard_redirect('pictures_added');
}

// #######################################################################
if ($_POST['do'] == 'doaddgroup')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'groupids'    => TYPE_ARRAY_UINT,
		'groupsshown' => TYPE_ARRAY_UINT
	));

	if (empty($pictureinfo) OR $pictureinfo['state'] == 'moderation')
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	if (
		$userinfo['userid'] != $vbulletin->userinfo['userid']
			OR
		!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_groups'])
			OR
		!($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups'])
			OR
		!($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canjoingroups'])
	)
	{
		print_no_permission();
	}

	if ($vbulletin->GPC['groupsshown'])
	{
		$delete = array();
		$insert = array();
		$changed_groups = array();

		$groups_sql = $db->query_read("
			SELECT
				socialgroup.*, IF(attachment.filedataid IS NULL, 0, 1) AS picingroup,
				attachment.filedataid, attachment.filename
			FROM " . TABLE_PREFIX . "socialgroup AS socialgroup
			INNER JOIN " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember ON
				(
					socialgroupmember.groupid = socialgroup.groupid
						AND
					socialgroupmember.userid = $userinfo[userid]
				)
			LEFT JOIN ". TABLE_PREFIX . "attachment AS attachment ON
				(
					attachment.contenttypeid = " . intval(vB_Types::instance()->getContentTypeID('vBForum_SocialGroup')) . "
						AND
					socialgroup.groupid = attachment.contentid
						AND
					attachment.filedataid = $pictureinfo[filedataid]
						AND
					attachment.userid = $userinfo[userid]
				)
			WHERE
				socialgroup.groupid IN (" . implode(',', $vbulletin->GPC['groupsshown']) . ")
					AND
				socialgroupmember.type = 'member'
					AND
				socialgroup.options & " . $vbulletin->bf_misc_socialgroupoptions['enable_group_albums'] . "
		");

		while ($group = $db->fetch_array($groups_sql))
		{
			if (!empty($vbulletin->GPC['groupids']["$group[groupid]"]) AND !$group['picingroup'])
			{
				// Yes this does single inserts on purpose
				// An atomic check since we can't put an unique key on the attachment table but we don't want the same attachment getting into the group more than once.
				if (!($db->query_first("
					SELECT attachmentid
					FROM " . TABLE_PREFIX . "attachment
					WHERE
						contenttypeid = " . intval(vB_Types::instance()->getContentTypeID('vBForum_SocialGroup')) . "
							AND
						userid = $userinfo[userid]
							AND
						contentid = $group[groupid]
							AND
						filedataid = $pictureinfo[filedataid]
				")))
				{
					$db->query_write("
						INSERT IGNORE INTO " . TABLE_PREFIX . "attachment
							(contenttypeid, contentid, userid, dateline, filedataid, filename, caption)
						VALUES
							(" . intval(vB_Types::instance()->getContentTypeID('vBForum_SocialGroup')) . ", $group[groupid], $userinfo[userid], " . TIMENOW . ", $pictureinfo[filedataid], '" . $db->escape_string($pictureinfo['filename']) . "', '" . $db->escape_string($pictureinfo['caption']) . "')
					");
					$changed_groups["$group[groupid]"] = $group;
					$attachmentid = $db->insert_id();

					$activity = new vB_ActivityStream_Manage('socialgroup', 'photo');
					$activity->set('contentid', $attachmentid);
					$activity->set('userid', $userinfo[userid]);
					$activity->set('dateline', TIMENOW);
					$activity->set('action', 'create');
					$activity->save();
				}
			}
			else if (empty($vbulletin->GPC['groupids']["$group[groupid]"]) AND $group['picingroup'])
			{
				$delete[] = $group['groupid'];
				$changed_groups["$group[groupid]"] = $group;
			}
		}

		($hook = vBulletinHook::fetch_hook('album_picture_doaddgroups')) ? eval($hook) : false;

		if ($delete)
		{
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "attachment
				WHERE
					filedataid = $pictureinfo[filedataid]
						AND
					contentid IN (" . implode(',', $delete) . ")
						AND
					contenttypeid = " . intval(vB_Types::instance()->getContentTypeID('vBForum_SocialGroup')) . "
						AND
					userid = $userinfo[userid]
			");
		}

		foreach ($changed_groups AS $group)
		{
			$groupdm =& datamanager_init('SocialGroup', $vbulletin, ERRTYPE_STANDARD);
			$groupdm->set_existing($group);
			$groupdm->rebuild_picturecount();
			$groupdm->save();
		}
	}

	$vbulletin->url = 'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$albuminfo[albumid]&amp;attachmentid=$pictureinfo[attachmentid]";
	print_standard_redirect('groups_picture_changed');
}

// #######################################################################
if ($_REQUEST['do'] == 'addgroup')
{
	if (empty($pictureinfo) OR $pictureinfo['state'] == 'moderation')
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	if (
		$userinfo['userid'] != $vbulletin->userinfo['userid']
			OR
		!($vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_groups'])
			OR
		!($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups'])
			OR
		!($vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canjoingroups'])
	)
	{
		print_no_permission();
	}

	$groups_sql = $db->query_read_slave("
		SELECT
			socialgroup.*, IF(attachment.filedataid IS NULL, 0, 1) AS picingroup
		FROM " . TABLE_PREFIX . "socialgroup AS socialgroup
		INNER JOIN " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember ON
			(
				socialgroupmember.groupid = socialgroup.groupid
					AND
				socialgroupmember.userid = $userinfo[userid]
			)
		LEFT JOIN ". TABLE_PREFIX . "attachment AS attachment ON
			(
				attachment.contenttypeid = " . intval(vB_Types::instance()->getContentTypeID('vBForum_SocialGroup')) . "
					AND
				socialgroup.groupid = attachment.contentid
					AND
				attachment.filedataid = $pictureinfo[filedataid]
					AND
				attachment.userid = $userinfo[userid]
			)
		WHERE
			socialgroupmember.type = 'member'
				AND
			socialgroup.options & " . $vbulletin->bf_misc_socialgroupoptions['enable_group_albums'] . "
		ORDER BY
			socialgroup.name
	");
	if ($db->num_rows($groups_sql) == 0)
	{
		standard_error(fetch_error('not_member_groups_find_some', fetch_seo_url('grouphome', array())));
	}

	require_once(DIR . '/includes/functions_socialgroup.php');

	$groupbits = '';
	while ($group = $db->fetch_array($groups_sql))
	{
		$group = prepare_socialgroup($group);
		$group_checked = ($group['picingroup'] ? ' checked="checked"' : '');

		$templater = vB_Template::create('album_addgroup_groupbit');
			$templater->register('group', $group);
			$templater->register('group_checked', $group_checked);
		$groupbits .= $templater->render();
	}

	$pictureinfo = prepare_pictureinfo_thumb($pictureinfo, $albuminfo);

	($hook = vBulletinHook::fetch_hook('album_picture_addgroups')) ? eval($hook) : false;

	// navbar and final output
	$navbits = construct_navbits(array(
		fetch_seo_url('member', $userinfo) => construct_phrase($vbphrase['xs_profile'], $userinfo['username']),
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]" => $vbphrase['albums'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$albuminfo[albumid]" => $albuminfo['title_html'],
		'' => $vbphrase['add_picture_to_groups']
	));
	$navbar = render_navbar_template($navbits);

	$templater = vB_Template::create('album_addgroup');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('albuminfo', $albuminfo);
		$templater->register('pictureinfo', $pictureinfo);
		$templater->register('groupbits', $groupbits);
	print_output($templater->render());
}

// #######################################################################
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

	$reportobj = new vB_ReportItem_AlbumPicture($vbulletin);
	$reportobj->set_extrainfo('album', $albuminfo);
	$reportobj->set_extrainfo('user', $userinfo);
	$reportobj->set_extrainfo('picture', $pictureinfo);
	$perform_floodcheck = $reportobj->need_floodcheck();

	if ($perform_floodcheck)
	{
		$reportobj->perform_floodcheck_precommit();
	}

	if (empty($pictureinfo))
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	if ($pictureinfo['state'] == 'moderation' AND !can_moderate(0, 'canmoderatepictures') AND $vbulletin->userinfo['userid'] != $pictureinfo['userid'])
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	($hook = vBulletinHook::fetch_hook('report_start')) ? eval($hook) : false;

	if ($_REQUEST['do'] == 'report')
	{
		// draw nav bar
		$navbits = construct_navbits(array(
			'memberlist.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['members_list'],
			fetch_seo_url('member', $userinfo) => $userinfo['username'],
			'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]" => $vbphrase['albums'],
			'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$albuminfo[albumid]" => $albuminfo['title_html'],
			'' => $vbphrase['report_picture']
		));

		$usernamecode = vB_Template::create('newpost_usernamecode')->render();

		$navbar = render_navbar_template($navbits);
		$url =& $vbulletin->url;

		($hook = vBulletinHook::fetch_hook('report_form_start')) ? eval($hook) : false;

		$forminfo = $reportobj->set_forminfo($pictureinfo);
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

		$reportobj->do_report($vbulletin->GPC['reason'], $pictureinfo);

		$url =& $vbulletin->url;
		print_standard_redirect('redirect_reportthanks');
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'picture')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber'  => TYPE_UINT,
		'perpage'     => TYPE_UINT,
		'commentid'   => TYPE_UINT,
		'showignored' => TYPE_BOOL,
	));

	if (empty($pictureinfo) OR ($pictureinfo['state'] == 'moderation' AND !can_moderate(0, 'canmoderatepictures') AND $pictureinfo['userid'] != $vbulletin->userinfo['userid']) AND !can_moderate(0, 'caneditalbumpicture'))
	{
		standard_error(fetch_error('invalidid', $vbphrase['picture'], $vbulletin->options['contactuslink']));
	}

	$pictureurl = create_full_url("attachment.php?" . $vbulletin->session->vars['sessionurl'] . "attachmentid=$pictureinfo[attachmentid]&d=$pictureinfo[dateline]");
	if (!preg_match('#^[a-z]+://#i', $pictureurl))
	{
		$pictureurl = $vbulletin->options['bburl'] . "/attachment.php?attachmentid=$pictureinfo[attachmentid]";

	}
	$pictureinfo['pictureurl'] = $pictureurl;

	$pictureinfo['adddate'] = vbdate($vbulletin->options['dateformat'], $pictureinfo['dateline'], true);
	$pictureinfo['addtime'] = vbdate($vbulletin->options['timeformat'], $pictureinfo['dateline']);
	$pictureinfo['caption_censored'] = fetch_censored_text($pictureinfo['caption']);

	$show['picture_owner'] = ($userinfo['userid'] == $vbulletin->userinfo['userid']);

	$show['edit_picture_option'] = ($userinfo['userid'] == $vbulletin->userinfo['userid'] OR can_moderate(0, 'caneditalbumpicture'));

	$show['add_group_link'] = ($userinfo['userid'] == $vbulletin->userinfo['userid']
		AND $vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_groups']
		AND $vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups']
		AND $vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canjoingroups']
		AND $pictureinfo['state'] != 'moderation'
	);

	$show['reportlink'] = (
		$vbulletin->userinfo['userid']
		AND ($vbulletin->options['rpforumid'] OR
			($vbulletin->options['enableemail'] AND $vbulletin->options['rpemail']))
	);

	$orderby = 'a.dateline DESC';
	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('album_picture_query')) ? eval($hook) : false;

	$navpictures_sql = $db->query_read_slave("
		SELECT a.attachmentid
		$hook_query_fields
		FROM " . TABLE_PREFIX . "attachment AS a
		$hook_query_joins
		WHERE a.contentid = $albuminfo[albumid] AND a.contenttypeid = " . intval($contenttypeid) . "
		" . ((!can_moderate(0, 'canmoderatepictures') AND $pictureinfo['userid'] != $vbulletin->userinfo['userid']) ? "AND a.state = 'visible'" : "") . "
		$hook_query_where
		ORDER BY $orderby
	");

	$pic_location = fetch_picture_location_info($navpictures_sql, $pictureinfo['attachmentid']);

	($hook = vBulletinHook::fetch_hook('album_picture')) ? eval($hook) : false;

	if ($vbulletin->options['pc_enabled'] AND $pictureinfo['state'] == 'visible')
	{
		require_once(DIR . '/includes/functions_picturecomment.php');

		$pagenumber = $vbulletin->GPC['pagenumber'];
		$perpage = $vbulletin->GPC['perpage'];
		$picturecommentbits = fetch_picturecommentbits($pictureinfo, $messagestats, $pagenumber, $perpage, $vbulletin->GPC['commentid'], $vbulletin->GPC['showignored']);

		$pagenavbits = array(
			"albumid=$albuminfo[albumid]",
			"attachmentid=$pictureinfo[attachmentid]"
		);
		if ($perpage != $vbulletin->options['pc_perpage'])
		{
			$pagenavbits[] = "pp=$perpage";
		}
		if ($vbulletin->GPC['showignored'])
		{
			$pagenavbits[] = 'showignored=1';
		}

		$pagenav = construct_page_nav($pagenumber, $perpage, $messagestats['total'],
			'album.php?' . $vbulletin->session->vars['sessionurl'] . implode('&amp;', $pagenavbits),
			''
		);

		$editorid = fetch_picturecomment_editor($pictureinfo, $pagenumber, $messagestats);
		if ($editorid)
		{
			$templater = vB_Template::create('picturecomment_form');
				$templater->register('albuminfo', $albuminfo);
				$templater->register('allowed_bbcode', $allowed_bbcode);
				$templater->register('editorid', $editorid);
				$templater->register('group', $group);
				$templater->register('messagearea', $messagearea);
				$templater->register('messagestats', $messagestats);
				$templater->register('pictureinfo', $pictureinfo);
				$templater->register('vBeditTemplate', $vBeditTemplate);
			$picturecomment_form = $templater->render();
		}
		else
		{
			$picturecomment_form = '';
		}

		$show['picturecomment_options'] = ($picturecomment_form OR $picturecommentbits);

		$templater = vB_Template::create('picturecomment_commentarea');
			$templater->register('messagestats', $messagestats);
			$templater->register('pagenav', $pagenav);
			$templater->register('picturecommentbits', $picturecommentbits);
			$templater->register('picturecomment_form', $picturecomment_form);
			$templater->register('pictureinfo', $pictureinfo);
		$picturecomment_commentarea = $templater->render();
	}
	else
	{
		$picturecomment_commentarea = '';
	}

	$show['moderation'] = ($pictureinfo['state'] == 'moderation');

	($hook = vBulletinHook::fetch_hook('album_picture_complete')) ? eval($hook) : false;

	// navbar and final output
	$navbits = construct_navbits(array(
		'memberlist.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['members_list'],
		fetch_seo_url('member', $userinfo) => $userinfo['username'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]" => $vbphrase['albums'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$albuminfo[albumid]" => $albuminfo['title_html'],
		'' => construct_phrase($vbphrase['picture_x_of_y_from_album_z'], $pic_location['pic_position'], $albuminfo['picturecount'], $albuminfo['title_html'])
	));


	// *********************************************************************************
	// prepare tags
	/*
	//not ready for primetime, but leave in for future
	$show['tag_box'] = false;

	if ($vbulletin->options['threadtagging'])
	{
		require_once(DIR . "/includes/functions_bigthree.php");
		require_once(DIR . "/includes/class_taggablecontent.php");

		$content = vB_Taggable_Content_Item::create($vbulletin, vB_Taggable_Content_Item::PICTURE,
			$pictureinfo['attachmentid'], $pictureinfo);

		$taglist = implode(", ", $content->fetch_existing_tag_list());
		$tag_list = fetch_tagbits($taglist);

		$show['manage_tag'] = ($content->can_add_tag() OR ($taglist AND $content->can_manage_tag()));
		$show['tag_box'] = ($show['manage_tag'] OR $taglist);
	}
	*/

	$navbar = render_navbar_template($navbits);
	$templater = vB_Template::create('album_pictureview');
		$templater->register_page_templates();
		$templater->register('albuminfo', $albuminfo);
		$templater->register('navbar', $navbar);
		$templater->register('picturecomment_commentarea', $picturecomment_commentarea);
		$templater->register('pictureinfo', $pictureinfo);
		$templater->register('pic_location', $pic_location);
		$templater->register('usercss', $usercss);
		$templater->register('usercss_switch_phrase', $usercss_switch_phrase);
		$templater->register('userinfo', $userinfo);
	print_output($templater->render());
}

// #######################################################################
if ($_REQUEST['do'] == 'album')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT,
		'addgroup'   => TYPE_BOOL
	));

	if (empty($albuminfo))
	{
		standard_error(fetch_error('invalidid', $vbphrase['album'], $vbulletin->options['contactuslink']));
	}

	if ($vbulletin->GPC['addgroup'] AND $albuminfo['userid'] != $vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	$show['add_group_row'] = ($userinfo['userid'] == $vbulletin->userinfo['userid']
		AND $vbulletin->options['socnet'] & $vbulletin->bf_misc_socnet['enable_groups']
		AND $vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups']
		AND $vbulletin->userinfo['permissions']['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canjoingroups']
	);

	($hook = vBulletinHook::fetch_hook('album_album')) ? eval($hook) : false;

	if ($vbulletin->GPC['addgroup'] AND $show['add_group_row'])
	{
		// need a list of groups this user is in
		$groups_sql = $db->query_read_slave("
			SELECT
				socialgroup.groupid, socialgroup.name
			FROM " . TABLE_PREFIX . "socialgroup AS socialgroup
			INNER JOIN " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember ON
				(
					socialgroupmember.groupid = socialgroup.groupid
						AND
					socialgroupmember.userid = " . $vbulletin->userinfo['userid'] . ")
			WHERE
				socialgroupmember.type = 'member'
					AND
				socialgroup.options & " . $vbulletin->bf_misc_socialgroupoptions['enable_group_albums'] . "
			ORDER BY
				socialgroup.name
		");
		if ($db->num_rows($groups_sql) == 0)
		{
			standard_error(fetch_error('not_member_groups_find_some', fetch_seo_url('grouphome', array())));
		}

		$group_options = '';
		while ($group = $db->fetch_array($groups_sql))
		{
			$optiontitle = fetch_censored_text($group['name']);
			$optionvalue = $group['groupid'];

			$group_options .= render_option_template($optiontitle, $optionvalue);

		}

		$show['add_group_form'] = true;
		$show['add_group_row'] = false;
		$show['private_notice'] = ($albuminfo['state'] == 'private');

		$perpage = 999999; // disable pagination
	}
	else
	{
		$show['add_group_form'] = false;
		$perpage = $vbulletin->options['album_pictures_perpage'];
	}

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}

	$input_pagenumber = $vbulletin->GPC['pagenumber'];

	if (can_moderate(0, 'canmoderatepictures') OR $albuminfo['userid'] == $vbulletin->userinfo['userid'])
	{
		$totalpictures = $albuminfo['visible'] + $albuminfo['moderation'];
	}
	else
	{
		$totalpictures = $albuminfo['visible'];
	}

	if (!$totalpictures)
	{
		$show['add_group_row'] = false;
	}

	$total_pages = max(ceil($totalpictures / $perpage), 1); // 0 pictures still needs an empty page
	$pagenumber = ($vbulletin->GPC['pagenumber'] > $total_pages ? $total_pages : $vbulletin->GPC['pagenumber']);
	$start = ($pagenumber - 1) * $perpage;

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('album_album_query')) ? eval($hook) : false;

	$pictures = $db->query_read("
		SELECT
			a.attachmentid, a.userid, a.caption, a.dateline, a.state,
			fd.filesize, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail, fd.thumbnail_dateline, fd.thumbnail_width, fd.thumbnail_height
			$hook_query_fields
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (fd.filedataid = a.filedataid)
		$hook_query_joins
		WHERE
			a.contentid = $albuminfo[albumid]
				AND
			a.contenttypeid = " . intval($contenttypeid) . "
			" . ((!can_moderate(0, 'canmoderatepictures') AND $albuminfo['userid'] != $vbulletin->userinfo['userid']) ? "AND a.state = 'visible'" : "") . "
			$hook_query_where
		ORDER BY a.dateline DESC
		LIMIT $start, $perpage
	");

	$picturebits = '';
	$picnum = 0;
	while ($picture = $db->fetch_array($pictures))
	{
		$picture = prepare_pictureinfo_thumb($picture, $albuminfo);

		if ($picnum % $vbulletin->options['album_pictures_perpage'] == 0)
		{
			$show['page_anchor'] = true;
			$page_anchor = ($picnum / $vbulletin->options['album_pictures_perpage']) + 1;
		}
		else
		{
			$show['page_anchor'] = false;
		}

		$picnum++;

		if ($picture['state'] == 'moderation')
		{
			$show['moderation'] = true;
		}
		else
		{
			$show['moderation'] = false;
			$have_visible = true;
		}

		($hook = vBulletinHook::fetch_hook('album_album_picturebit')) ? eval($hook) : false;

		if (defined('VB_API') AND VB_API === true)
		{
			if ($picture['hasthumbnail'])
			{
				$picture['pictureurl'] = create_full_url('attachment.php?' . $vbulletin->session->vars['sessionurl'] . "attachmentid=$picture[attachmentid]&thumb=1&stc=1&d=$picture[thumbnail_dateline]");
			}
			else
			{
				$picture['pictureurl'] = '';
			}

			$picture['picturefullurl'] = create_full_url('attachment.php?' . $vbulletin->session->vars['sessionurl'] . "attachmentid=$picture[attachmentid]&d=$picture[dateline]");
		}

		if ($show['add_group_form'] AND $picture['state'] == 'visible')
		{
			$templater = vB_Template::create('album_picturebit_checkbox');
		}
		else
		{
			$templater = vB_Template::create('album_picturebit');
		}

			$templater->register('albuminfo', $albuminfo);
			$templater->register('picture', $picture);
			$templater->register('usercss', $usercss);
		$picturebits .= $templater->render();
	}

	$pagenav = construct_page_nav($pagenumber, $perpage, $totalpictures, 'album.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$albuminfo[albumid]", '');

	$show['edit_album_option'] = ($userinfo['userid'] == $vbulletin->userinfo['userid'] OR can_moderate(0, 'caneditalbumpicture'));
	$show['add_picture_option'] = (
		$userinfo['userid'] == $vbulletin->userinfo['userid']
			AND
		fetch_count_overage($userinfo['userid'], $albuminfo[albumid], $vbulletin->userinfo['permissions']['albummaxpics']) <= 0
			AND
		(
			!$vbulletin->options['album_maxpicsperalbum']
				OR
			$totalpictures - $vbulletin->options['album_maxpicsperalbum'] < 0
		)
	);

	if ($albuminfo['state'] == 'private')
	{
		$show['personalalbum'] = true;
		$albumtype = $vbphrase['private_album_paren'];
	}
	else if ($albuminfo['state'] == 'profile')
	{
		$show['personalalbum'] = true;
		$albumtype = $vbphrase['profile_album_paren'];
	}

	($hook = vBulletinHook::fetch_hook('album_album_complete')) ? eval($hook) : false;

	// navbar and final output
	$navbits = construct_navbits(array(
		'memberlist.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['members_list'],
		fetch_seo_url('member', $userinfo) => $userinfo['username'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]" => $vbphrase['albums'],
		'' => $albuminfo['title_html']
	));
	$navbar = render_navbar_template($navbits);

	$poststarttime = TIMENOW;
	$posthash = md5($poststarttime . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']);

	$templater = vB_Template::create('album_picturelist');
		$templater->register_page_templates();
		$templater->register('albuminfo', $albuminfo);
		$templater->register('albumtype', $albumtype);
		$templater->register('group_options', $group_options);
		$templater->register('input_pagenumber', $input_pagenumber);
		$templater->register('navbar', $navbar);
		$templater->register('pagenav', $pagenav);
		$templater->register('pagenumber', $pagenumber);
		$templater->register('picturebits', $picturebits);
		$templater->register('usercss', $usercss);
		$templater->register('usercss_switch_phrase', $usercss_switch_phrase);
		$templater->register('userinfo', $userinfo);
		$templater->register('posthash', $posthash);
		$templater->register('contenttypeid', $contenttypeid);
		$templater->register('poststarttime', $poststarttime);
		$templater->register('values', "values[albumid]=$albuminfo[albumid]");
		$templater->register('totalpages', $total_pages);
		$templater->register('start', vb_number_format($start + 1));
		$templater->register('end', vb_number_format(($pagenumber * $perpage) > $totalpictures ? $totalpictures : ($pagenumber * $perpage)));
		$templater->register('total', vb_number_format($totalpictures));
	print_output($templater->render());
}

// #######################################################################
if ($_REQUEST['do'] == 'latest' OR $_REQUEST['do'] == 'overview')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT
	));

	$perpage = $vbulletin->options['albums_perpage'];

	// only show latest if we're not showing more specific user albums
	if ((!$userinfo OR !$vbulletin->GPC['pagenumber']) AND $vbulletin->options['album_recentalbumdays'])
	{
		// Create collection
		require_once(DIR . '/includes/class_groupmessage.php');
		$collection_factory = new vB_Collection_Factory($vbulletin);
		$collection = $collection_factory->create('album', false, $vbulletin->GPC['pagenumber'], $perpage);

		// Set counts for view
		list($pagestart, $pageend, $pageshown, $pagetotal) = array_values($collection->fetch_counts());

		// Get actual resolved page number in case input was normalised
		if ($collection->fetch_count())
		{
			$pagenumber = $collection->fetch_pagenumber();

			// Create bit factory
			$bitfactory = new vB_Bit_Factory($vbulletin, 'album');

			// Build message bits for all items
			$latestbits = '';
			while ($item = $collection->fetch_item())
			{
				$bit =& $bitfactory->create_instance($item);
				$bit->set_template('album_latestbit');
				$latestbits .= $bit->construct();
			}

			// Construct page navigation
			$latest_pagenav = construct_page_nav($pagenumber, $perpage, $pagetotal, 'album.php?' . $vbulletin->session->vars['sessionurl'] . "do=latest");

			$show['latestalbums'] = true;
		}
		unset($collection_factory, $collection);

		if (!$userinfo)
		{
			// navbar and final output
			$navbits = construct_navbits(array(
				'album.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['albums'],
				'' => $vbphrase['recently_updated_albums']
			));
			$navbar = render_navbar_template($navbits);

			$custompagetitle = $vbphrase['recently_updated_albums'];
		}
		else
		{
			// navbar and final output
			$navbits = construct_navbits(array(
				'album.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['albums'],
			));
			$navbar = render_navbar_template($navbits);

			$custompagetitle = $vbphrase['albums'];
		}

		($hook = vBulletinHook::fetch_hook('album_latest_complete')) ? eval($hook) : false;
	}

	// also show user albums
	if ($userinfo)
	{
		$_REQUEST['do'] = 'user';
	}
	else
	{
		if (!$latestbits)
		{
			standard_error(fetch_error('no_recently_updated_albums'));
		}

		$templater = vB_Template::create('album_list');
			$templater->register_page_templates();
			$templater->register('albumbits', $albumbits);
			$templater->register('custompagetitle', $custompagetitle);
			$templater->register('latestbits', $latestbits);
			$templater->register('latest_pagenav', $latest_pagenav);
			$templater->register('navbar', $navbar);
			$templater->register('pagenav', $pagenav);
			$templater->register('template_hook', $template_hook);
			$templater->register('usercss', $usercss);
			$templater->register('userinfo', $userinfo);
		print_output($templater->render());
	}
}

// #######################################################################
if ($_REQUEST['do'] == 'user')
{
	// was profile privacy condition - moved up to top of file
	if (true)
	{
		$show['user_albums'] = true;
		$vbulletin->input->clean_array_gpc('r', array(
			'pagenumber' => TYPE_UINT
		));

		$state = array('public');
		if (can_view_private_albums($userinfo['userid']))
		{
			$state[] = 'private';
		}
		if (can_view_profile_albums($userinfo['userid']))
		{
			$state[] = 'profile';
		}

		$albumcount = $db->query_first("
			SELECT COUNT(*) AS total
			FROM " . TABLE_PREFIX . "album
			WHERE userid = $userinfo[userid]
				AND state IN ('" . implode("', '", $state) . "')
		");

		if ($vbulletin->GPC['pagenumber'] < 1)
		{
			$vbulletin->GPC['pagenumber'] = 1;
		}

		$perpage = $vbulletin->options['albums_perpage'];
		$total_pages = max(ceil($albumcount['total'] / $perpage), 1); // handle the case of 0 albums
		$pagenumber = ($vbulletin->GPC['pagenumber'] > $total_pages ? $total_pages : $vbulletin->GPC['pagenumber']);
		$start = ($pagenumber - 1) * $perpage;

		$hook_query_fields = $hook_query_joins = $hook_query_where = '';
		($hook = vBulletinHook::fetch_hook('album_user_query')) ? eval($hook) : false;

		// fetch data and prepare data
		$albums = $db->query_read("
			SELECT album.*,
				attachment.attachmentid,
				IF(filedata.thumbnail_filesize > 0, 1, 0) AS hasthumbnail, filedata.thumbnail_dateline, filedata.thumbnail_width, filedata.thumbnail_height
				$hook_query_fields
			FROM " . TABLE_PREFIX . "album AS album
			LEFT JOIN " . TABLE_PREFIX . "attachment AS attachment ON (album.coverattachmentid = attachment.attachmentid)
			LEFT JOIN " . TABLE_PREFIX . "filedata AS filedata ON (attachment.filedataid = filedata.filedataid)
			$hook_query_joins
			WHERE
				album.userid = $userinfo[userid]
					AND
				album.state IN ('" . implode("', '", $state) . "')
				$hook_query_where
			ORDER BY album.lastpicturedate DESC
			LIMIT $start, $perpage
		");

		$albumbits = '';
		while ($album = $db->fetch_array($albums))
		{
			$album['picturecount'] = vb_number_format($album['visible']);
			$album['picturedate'] = vbdate($vbulletin->options['dateformat'], $album['lastpicturedate'], true);
			$album['picturetime'] = vbdate($vbulletin->options['timeformat'], $album['lastpicturedate']);

			$album['description_html'] = nl2br(fetch_word_wrapped_string(fetch_censored_text($album['description'])));
			$album['title_html'] = fetch_word_wrapped_string(fetch_censored_text($album['title']));
			$album['coverdimensions'] = ($album['thumbnail_width'] ? "width=\"$album[thumbnail_width]\" height=\"$album[thumbnail_height]\"" : '');

			if ($album['state'] == 'private')
			{
				$show['personalalbum'] = true;
				$albumtype = $vbphrase['private_album_paren'];
			}
			else if ($album['state'] == 'profile')
			{
				$show['personalalbum'] = true;
				$albumtype = $vbphrase['profile_album_paren'];
			}
			else
			{
				$show['personalalbum'] = false;
			}

			if ($album['moderation'] AND (can_moderate(0, 'canmoderatepictures') OR $vbulletin->userinfo['userid'] == $album['userid']))
			{
				$show['moderated'] = true;
				$album['moderatedcount'] = vb_number_format($album['moderation']);
			}
			else
			{
				$show['moderated'] = false;
			}

			if (defined('VB_API') AND VB_API === true)
			{
				if ($album['attachmentid'])
				{
					$album['pictureurl'] = create_full_url('attachment.php?' . $vbulletin->session->vars['sessionurl'] . "albumid=$album[albumid]&attachmentid=$album[attachmentid]&thumb=1&d=$album[thumbnail_dateline]");
				}
				else
				{
					$album['pictureurl'] = '';
				}
			}

			($hook = vBulletinHook::fetch_hook('album_user_albumbit')) ? eval($hook) : false;

			$templater = vB_Template::create('albumbit');
				$templater->register('album', $album);
				$templater->register('albumtype', $albumtype);
				$templater->register('template_hook', $template_hook);
			$albumbits .= $templater->render();
		}

		$pagenav = construct_page_nav($pagenumber, $perpage, $albumcount['total'],
			'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]", ''
		);

		$show['add_album_option'] = ($userinfo['userid'] == $vbulletin->userinfo['userid']);

		($hook = vBulletinHook::fetch_hook('album_user_complete')) ? eval($hook) : false;
	}

	if (!$navbits)
	{
		// navbar and final output
		$navbits = construct_navbits(array(
			'memberlist.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['members_list'],
			fetch_seo_url('member', $userinfo) => $userinfo['username'],
			'' => $vbphrase['albums']
		));

		$navbar = render_navbar_template($navbits);

		$custompagetitle = ($custompagetitle ? $custompagetitle : construct_phrase($vbphrase['xs_albums'], $userinfo['username']));
	}

	$templater = vB_Template::create('album_list');
		$templater->register_page_templates();
		$templater->register('albumbits', $albumbits);
		$templater->register('custompagetitle', $custompagetitle);
		$templater->register('latestbits', $latestbits);
		$templater->register('latest_pagenav', $latest_pagenav);
		$templater->register('navbar', $navbar);
		$templater->register('pagenav', $pagenav);
		$templater->register('template_hook', $template_hook);
		$templater->register('usercss', $usercss);
		$templater->register('userinfo', $userinfo);
		$templater->register('albumcount', $albumcount);
	print_output($templater->render());
}


// #######################################################################
if ($_REQUEST['do'] == 'moderated')
{
	if (!$vbulletin->options['pc_enabled'])
	{
		print_no_permission();
	}

	if ($userinfo['userid'] != $vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	if (!($vbulletin->userinfo['permissions']['albumpermissions'] & $vbulletin->bf_ugp_albumpermissions['canmanagepiccomment']))
	{
		print_no_permission();
	}

	require_once(DIR . '/includes/functions_picturecomment.php');
	require_once(DIR . '/includes/class_bbcode.php');
	require_once(DIR . '/includes/class_picturecomment.php');
	require_once(DIR . '/includes/functions_bigthree.php');

	$coventry = fetch_coventry('string');

	$bbcode = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	// note: this code assumes that albumpicture and picture are 1:1 because they are in 3.7
	$comments = $db->query_read_slave("
		SELECT picturecomment.*, user.*, picturecomment.ipaddress AS messageipaddress,
			a.caption, fd.filesize, fd.thumbnail_filesize, fd.thumbnail_dateline, fd.thumbnail_width, fd.thumbnail_height, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail,
			a.contentid AS albumid
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
		INNER JOIN " . TABLE_PREFIX . "picturecomment AS picturecomment ON
			(picturecomment.filedataid = fd.filedataid AND picturecomment.userid = a.userid AND picturecomment.state = 'moderation')
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (picturecomment.postuserid = user.userid)
		WHERE
			a.userid = " . $vbulletin->userinfo['userid'] . "
				AND
			a.contenttypeid = " . intval($contenttypeid) . "
			" . ($coventry ? "AND picturecomment.postuserid NOT IN ($coventry)" : '') . "
		ORDER BY picturecomment.dateline ASC
	");

	$picturecommentbits = '';
	$moderated_count = 0;

	while ($comment = $db->fetch_array($comments))
	{
		// $comment contains comment, picture, and album info
		$pictureinfo = array(
			'filedataid' => $comment['filedataid'],
			'albumid' => $comment['albumid'],
			'userid' => $vbulletin->userinfo['userid'],
			'caption' => $comment['caption'],
			'extension' => $comment['extension'],
			'filesize' => $comment['filesize'],
			'idhash' => $comment['idhash'],
			'thumbnail_filesize' => $comment['thumbnail_filesize'],
			'thumbnail_dateline' => $comment['thumbnail_dateline'],
			'thumbnail_width' => $comment['thumbnail_width'],
			'thumbnail_height' => $comment['thumbnail_height'],
		);

		$albuminfo = array(
			'albumid' => $comment['albumid'],
			'userid' => $vbulletin->userinfo['userid']
		);

		$pictureinfo = prepare_pictureinfo_thumb($pictureinfo, $albuminfo);

		$factory = new vB_Picture_CommentFactory($vbulletin, $bbcode, $pictureinfo);

		$response_handler = new vB_Picture_Comment_ModeratedView($vbulletin, $factory, $bbcode, $pictureinfo, $comment);
		$response_handler->cachable = false;

		$picturecommentbits .= $response_handler->construct();
		$moderated_count++;
	}

	if ($moderated_count != $vbulletin->userinfo['pcmoderatedcount'])
	{
		// back counter -- likely tachy based, rebuild all counters
		build_picture_comment_counters($vbulletin->userinfo['userid']);
	}

	if (!$picturecommentbits)
	{
		standard_error(
			fetch_error('no_picture_comments_awaiting_approval', 'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]"),
			'',
			false
		);
	}

	// this is a small kludge to let me use fetch_user_picture_message_perm
	// all pictures will be from this user and userid is the only value used
	$pictureinfo = array(
		'userid' => $userinfo['userid']
	);
	$show['delete'] = fetch_user_picture_message_perm('candeletemessages', $pictureinfo);
	$show['undelete'] = fetch_user_picture_message_perm('canundeletemessages', $pictureinfo);
	$show['approve'] = fetch_user_picture_message_perm('canmoderatemessages', $pictureinfo);
	$show['inlinemod'] = ($show['delete'] OR $show['undelete'] OR $show['approve']);

	($hook = vBulletinHook::fetch_hook('album_moderated_complete')) ? eval($hook) : false;

	// navbar and final output
	$navbits = construct_navbits(array(
		'memberlist.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['members_list'],
		fetch_seo_url('member', $userinfo) => $userinfo['username'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]" => construct_phrase($vbphrase['xs_albums'], $userinfo['username']),
		'' => $vbphrase['picture_comments_awaiting_approval']
	));
	$navbar = render_navbar_template($navbits);

	$templater = vB_Template::create('album_moderatedcomments');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('picturecommentbits', $picturecommentbits);
		$templater->register('pictureinfo', $pictureinfo);
		$templater->register('usercss', $usercss);
	print_output($templater->render());
}

// #######################################################################
if ($_REQUEST['do'] == 'unread')
{
	if (!$vbulletin->options['pc_enabled'])
	{
		print_no_permission();
	}

	if ($userinfo['userid'] != $vbulletin->userinfo['userid'])
	{
		print_no_permission();
	}

	require_once(DIR . '/includes/functions_bigthree.php');
	$coventry = fetch_coventry('string');

	$contenttypes = array();
	if ($canviewalbums)
	{
		$contenttypes[] = intval(vB_Types::instance()->getContentTypeID('vBForum_Album'));
	}
	if ($canviewgroups)
	{
		$contenttypes[] = intval(vB_Types::instance()->getContentTypeID('vBForum_SocialGroup'));
	}

	$pictures = $db->query_read_slave("
		SELECT
			a.attachmentid, a.caption,
			fd.filesize, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail, fd.thumbnail_filesize, fd.thumbnail_dateline, fd.thumbnail_width, fd.thumbnail_height,
			a.contentid AS albumid, MIN(picturecomment.commentid) AS unreadcommentid, COUNT(*) AS unreadcomments
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
		INNER JOIN " . TABLE_PREFIX . "picturecomment AS picturecomment ON
			(picturecomment.filedataid = fd.filedataid AND picturecomment.userid = a.userid AND picturecomment.state = 'visible' AND picturecomment.messageread = 0)
		WHERE
			a.userid = " . $vbulletin->userinfo['userid'] . "
				AND
			a.contenttypeid IN (" . implode(",", $contenttypes) . ")
			" . ($coventry ? "AND picturecomment.postuserid NOT IN ($coventry)" : '') . "
		GROUP BY a.attachmentid
		ORDER BY unreadcommentid ASC
	");

	$picturebits = '';
	$unread_count = 0;

	while ($picture = $db->fetch_array($pictures))
	{
		// $comment contains picture and album info
		$picture = prepare_pictureinfo_thumb($picture, $picture);

		$picture['unreadcomments'] = vb_number_format($picture['unreadcomments']);

		($hook = vBulletinHook::fetch_hook('album_unread_picturebit')) ? eval($hook) : false;

		$templater = vB_Template::create('album_picturebit_unread');
			$templater->register('picture', $picture);
			$templater->register('usercss', $usercss);
		$picturebits .= $templater->render();
	}

	if ($moderated_count != $vbulletin->userinfo['pcunreadcount'])
	{
		// back counter -- likely tachy based, rebuild all counters
		require_once(DIR . '/includes/functions_picturecomment.php');
		build_picture_comment_counters($vbulletin->userinfo['userid']);
	}

	if (!$picturebits)
	{
		standard_error(
			fetch_error('no_unread_picture_comments', 'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]"),
			'',
			false
		);
	}

	($hook = vBulletinHook::fetch_hook('album_unread_complete')) ? eval($hook) : false;

	// navbar and final output
	$navbits = construct_navbits(array(
		'memberlist.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['members_list'],
		fetch_seo_url('member', $userinfo) => $userinfo['username'],
		'album.php?' . $vbulletin->session->vars['sessionurl'] . "u=$userinfo[userid]" => construct_phrase($vbphrase['xs_albums'], $userinfo['username']),
		'' => $vbphrase['unread_picture_comments']
	));
	$navbar = render_navbar_template($navbits);

	$templater = vB_Template::create('album_unreadcomments');
		$templater->register_page_templates();
		$templater->register('navbar', $navbar);
		$templater->register('picturebits', $picturebits);
		$templater->register('usercss', $usercss);
	print_output($templater->render());
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 62621 $
|| ####################################################################
\*======================================================================*/
?>
