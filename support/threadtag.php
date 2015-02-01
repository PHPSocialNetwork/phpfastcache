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
define('THIS_SCRIPT', 'threadtag');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('posting', 'showthread');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
	'newpost_errormessage',
	'tag_edit',
	'tag_managebit',
	'tagbit_wrapper'
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/class_taggablecontent.php');

//do we still need this?  if so, why
require_once(DIR . '/includes/functions_newpost.php');

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'manage';
}

$vbulletin->input->clean_array_gpc('r', array(
	'contenttype' => TYPE_NOHTML,
	'contentid'   => TYPE_UINT,
	'threadid'    => TYPE_UINT,
	'ajax'      => TYPE_BOOL,
	'returnurl'   => TYPE_STR
));

//*******************************************************************
//Figure out the content type
if (!empty($vbulletin->GPC['contenttype']))
{
	$contenttypeid = $vbulletin->GPC['contenttype'];
}
else
{
	$contenttypeid = "vBForum_Thread";
}

//todo fix the old urls that use this method then elimate this code
if ($contenttypeid == 'thread')
{
	$contenttypeid = "vBForum_Thread";
}
else if ($contenttypeid == 'picture')
{
	$contenttypeid = "vBForum_Picture";
}

$contenttypeid = vB_Types::instance()->getContentTypeID($contenttypeid);

//*******************************************************************
//Figure out the content id
if ($vbulletin->GPC_exists['contentid'])
{
	$contentid = $vbulletin->GPC['contentid'];
}
else
{
	$contentid = $vbulletin->GPC['threadid'];
}

if (!$vbulletin->options['threadtagging'])
{
	print_no_permission();
}

if (!$contenttypeid)
{
	eval(standard_error(fetch_error('content_not_taggable')));
}

//this will terminate if there are permission errors
$content = vB_Taggable_Content_Item::create($vbulletin, $contenttypeid, $contentid);
if (!$content)
{
	//do we need a phrase?  This really shouldn't happen under normal operation.
	eval(standard_error(fetch_error('content_not_taggable')));
}
$content->verify_ui_permissions();

//$contentinfo = $content->fetch_content_info();
$show['add_option'] = $content->can_add_tag();
$show['manage_existing_option'] = $content->can_manage_tag();
($hook = vBulletinHook::fetch_hook('threadtag_start')) ? eval($hook) : false;

if (!$show['add_option'] AND !$show['manage_existing_option'])
{
	print_no_permission();
}

// ##############################################################################
if ($_POST['do'] == 'managetags')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'tagskept'  => TYPE_ARRAY_UINT,
		'tagsshown' => TYPE_ARRAY_UINT,
		'taglist'   => TYPE_NOHTML,
	));

	if ($vbulletin->GPC['ajax'])
	{
		$vbulletin->GPC['taglist'] = convert_urlencoded_unicode($vbulletin->GPC['taglist']);
	}

	//remove any tags shown and not kept.
	if ($vbulletin->GPC['tagsshown'] AND $show['manage_existing_option'])
	{
		$tags_sql = $db->query_read("
			SELECT tag.*, tagcontent.userid
			FROM " . TABLE_PREFIX . "tagcontent AS tagcontent
			INNER JOIN " . TABLE_PREFIX . "tag AS tag ON
				(tag.tagid = tagcontent.tagid AND tagcontent.contenttypeid = " . intval($contenttypeid) . ")
			WHERE tagcontent.contentid = $contentid
				AND tagcontent.tagid IN (" . implode(',', $vbulletin->GPC['tagsshown']) . ")
		");

		$delete = array();
		while ($tag = $db->fetch_array($tags_sql))
		{
			if ($content->can_delete_tag($tag['userid']))
			{
				if (!in_array($tag['tagid'], $vbulletin->GPC['tagskept']))
				{
					$delete[] = $tag['tagid'];
				}
			}
		}

		($hook = vBulletinHook::fetch_hook('threadtag_domanage_delete')) ? eval($hook) : false;

		if ($delete)
		{
			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "tagcontent
				WHERE contentid = $contentid AND
					contenttypeid = ". intval($contenttypeid) . " AND
					tagid IN (" . implode(',', $delete) . ")
			");

			$content->rebuild_content_tags();
		}
	}

	($hook = vBulletinHook::fetch_hook('threadtag_domanage_postdelete')) ? eval($hook) : false;

	if ($vbulletin->GPC['taglist'] AND $show['add_option'])
	{
		$limits = $content->fetch_tag_limits();
		$errors = $content->add_tags_to_content($vbulletin->GPC['taglist'], $limits);
	}
	else
	{
		$errors = array();
	}

	if ($vbulletin->GPC['ajax'])
	{
		require_once(DIR . '/includes/class_xml.php');

		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('tag');
			$xml->add_tag('taghtml', process_replacement_vars($content->fetch_rendered_tag_list()));
			if ($errors)
			{
				$errorlist = '';
				foreach ($errors AS $error)
				{
					$errorlist .= "\n   * $error";
				}
				$xml->add_tag('warning', fetch_error('tag_add_failed_plain', $errorlist));
			}
		$xml->close_group();
		$xml->print_xml();
	}
	else
	{
		$returnurl = $content->fetch_return_url();
		$errorlist = '';
		if (!empty($errors))
		{
			$show['errors'] = true;
			$templater = vB_Template::create('newpost_errormessage');
			$templater->register('errors', $errors);
			$errorlist .= $templater->render();

			$errorlist = fetch_error('tag_add_failed_html', $errorlist, $returnurl);

			$_REQUEST['do'] = 'manage';
			define('ADD_ERROR', true);
		}
		else
		{
			$vbulletin->url = $returnurl;
			print_standard_redirect(fetch_error('tags_edited_successfully'), false);  
		}
	}
}

// ##############################################################################
if ($_REQUEST['do'] == 'manage')
{

	$show['errors'] = defined('ADD_ERROR');
	if (!$show['errors'])
	{
		$valid_tag_html = '';
	}

	$tag_manage_options = '';
	$have_removal_tags = false;
	$mytags = 0;

	$tags_sql = $db->query_read("
		SELECT tag.*, tagcontent.userid, user.username
		FROM " . TABLE_PREFIX . "tagcontent AS tagcontent
		INNER JOIN " . TABLE_PREFIX . "tag AS tag ON
			(tag.tagid = tagcontent.tagid AND tagcontent.contenttypeid = " . intval($contenttypeid) . ")
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = tagcontent.userid)
		WHERE tagcontent.contentid = $contentid
		ORDER BY tag.tagtext
	");
	$total_tags = $db->num_rows($tags_sql);

	if ($total_tags == 0 AND !$show['add_option'])
	{
		print_no_permission();
	}

	while ($tag = $db->fetch_array($tags_sql))
	{
		$tag['ismine'] = ($tag['userid'] == $vbulletin->userinfo['userid']);
		$show['tag_checkbox'] = $content->can_delete_tag($tag['userid']);

		if ($show['tag_checkbox'])
		{
			$have_removal_tags = true;
		}

		if ($tag['ismine'])
		{
			$mytags++;
		}

		// only moderators can see who added a tag
		if (!$content->can_moderate_tag())
		{
			$tag['username'] = '';
		}

		($hook = vBulletinHook::fetch_hook('threadtag_managebit')) ? eval($hook) : false;
		$templater = vB_Template::create('tag_managebit');
			$templater->register('tag', $tag);
		$tag_manage_options .= $templater->render();
	}

	$limits = $content->fetch_tag_limits();
	if ($limits['content_limit'])
	{
		$content_tags_remain = max(0, $limits['content_limit'] - $total_tags);
	}
	else
	{
		$content_tags_remain = PHP_INT_MAX;
	}

	if ($limits['user_limit'])
	{
		$user_tags_remain = max(0, $limits['user_limit'] - $mytags);
	}
	else
	{
		$user_tags_remain = PHP_INT_MAX;
	}

	$tags_remain = min($content_tags_remain, $user_tags_remain);
	($hook = vBulletinHook::fetch_hook('threadtag_manage_tagsremain')) ? eval($hook) : false;

	$show['tag_limit_phrase'] = ($tags_remain !== PHP_INT_MAX);
	$tags_remain = vb_number_format($tags_remain);
	$tag_delimiters = addslashes_js($vbulletin->options['tagdelimiter']);

	if ($vbulletin->GPC['ajax'])
	{
		$popup = $vbulletin->input->clean_gpc('r', 'popup', TYPE_BOOL);

		if($popup)
		{
			$templater = vB_Template::create('tag_edit_ajax_popup');
		}
		else
		{
			$templater = vB_Template::create('tag_edit_ajax');
		}

		$templater->register('contentid', $contentid);
		$templater->register('contenttype', $contenttypeid);
		$templater->register('tags_remain', $tags_remain);
		$templater->register('tag_manage_options', $tag_manage_options);
		$templater->register('url', $url);
		$html = $templater->render();
		require_once(DIR . '/includes/class_xml.php');

		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('tag');
			$xml->add_tag($popup ? 'tagpopup' : 'html', process_replacement_vars($html));
			$xml->add_tag('delimiters', $vbulletin->options['tagdelimiter']);
		$xml->close_group();
		$xml->print_xml();
	}
	else
	{
		$returnurl = $content->fetch_return_url();

		$title = $content->get_title();
		if(!$title)
		{
			$title = $content->fetch_content_type_diplay();
		}

		$content_type_label = $content->fetch_content_type_diplay();
		// navbar and output
		$navbits = $content->fetch_page_nav();
		$navbits = construct_navbits($navbits);

		$navbar = render_navbar_template($navbits);
		$templater = vB_Template::create('tag_edit');
			$templater->register_page_templates();
			$templater->register('contentid', $contentid);
			$templater->register('title', $title);
			$templater->register('contenttype', $contenttypeid);
			$templater->register('content_type_label', $content_type_label);
			$templater->register('errorlist', $errorlist);
			$templater->register('navbar', $navbar);
			$templater->register('returnurl', $returnurl);
			$templater->register('tags_remain', $tags_remain);
			$templater->register('tag_delimiters', $tag_delimiters);
			$templater->register('tag_manage_options', $tag_manage_options);
		print_output($templater->render());
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 62098 $
|| ####################################################################
\*======================================================================*/
