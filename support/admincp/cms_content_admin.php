<?php
/*======================================================================*\
   || #################################################################### ||
   || # vBulletin Blog 4.2.1 - Licence Number VBF02D260D
   || # ---------------------------------------------------------------- # ||
   || # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
   || # This file may not be redistributed in whole or significant part. # ||
   || # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
   || # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
   || #################################################################### ||
   \*======================================================================*/

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 27874 $');
define('NOZIP', 1);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
if (!count($phrasegroups))
{
	$phrasegroups = array('vbcms', 'global', 'cpcms', 'cphome');
	$globaltemplates = array(
		'pagenav_curpage', 'pagenav_pagelinkrel', 'pagenav_pagelink','pagenav'
	);
}

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_cms.php');
require_once(DIR . '/includes/functions_cms_layout.php');
require_once(DIR . '/packages/vbcms/contentmanager.php');

if (!isset($vbulletin->userinfo['permissions']['cms']))
{
	vBCMS_Permissions::getUserPerms();
}

define('CMS_ADMIN', true);

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!($vbulletin->userinfo['permissions']['cms']['admin']))
{
	print_cp_no_permission();
}

//Make sure the system knows where we are:
// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
$sect_js_varname = 'filter_section';
$current_user = new vB_Legacy_CurrentUser();
$per_page = vBCms_ContentManager::getPerPage($current_user);

$styles = false;
$layouts = false;

//It's possible we could have a post "do" and a get "do"that don't match. Let's make sure
// we get the right one.
if (isset($_POST['do']))
{
	$vbulletin->input->clean_array_gpc('p', array(
		'do'   => TYPE_STR,
		'mydo' => TYPE_STR,
	));
}
else
{
	$vbulletin->input->clean_array_gpc('r', array(
		'do'   => TYPE_STR,
	));
}


switch($vbulletin->GPC['do'])
{
	case 'list_nodes': //This is called from ajax.php. It returns a list of
		// sections and leaves for a display panel. Because
		//we're just returning xml for ajax we don't want any headers, etc.
		require_once DIR . '/includes/functions_misc.php';
		$vbulletin->input->clean_array_gpc('r', array(
			'nodeid' => TYPE_UINT,
			'level'  => TYPE_UINT,
		));

		if ($vbulletin->GPC_exists['nodeid'])
		{
			$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
			$xml->add_group('root');

			$xml->add_tag('html',  vBCms_ContentManager::getLeafPanel($vbulletin->GPC['nodeid'],
				   'sel_node_' . $vbulletin->GPC['nodeid'] , $vbulletin->GPC['level']));

			$xml->close_group();
			$xml->print_xml();
		}

		break;

	case 'find_leaves':  //This is also called from ajax.php. It returns a list of
		// sections and leaves for a display panel, with only leaves clickable. Because
		//we're just returning xml for ajax we don't want any headers, etc.
		require_once DIR . '/includes/functions_misc.php';

		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('root');

		$xml->add_tag('html',  vBCms_ContentManager::getNodeSearchResults());

		$xml->close_group();
		$xml->print_xml();

		break;


	case 'list_sections': //This is called from ajax.php. It returns a list of
		// sub-categories of the current node. Because
		//we're just returning xml for ajax we don't want any headers, etc.
		require_once DIR . '/includes/functions_misc.php';
		$vbulletin->input->clean_array_gpc('r', array(
			'sectionid' => TYPE_UINT,
			'level'     => TYPE_UINT,
		));

		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('root');

		$xml->add_tag('html',  vBCms_ContentManager::showSections($per_page));
		$xml->close_group();
		$xml->print_xml();

		break;

	case 'list_allsection': //This is called from ajax.php. It returns a list of
		// sub-categories of the current node. Because
		//we're just returning xml for ajax we don't want any headers, etc.
		require_once DIR . '/includes/functions_misc.php';
		$vbulletin->input->clean_array_gpc('r', array(
			'order' => TYPE_UINT,
		));

		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('root');
		$xml->add_tag('html',  vBCms_ContentManager::getSectionList($vbulletin->GPC['order']));
		$xml->close_group();
		$xml->print_xml();
		break;


	case 'list_categories' : //This is called from ajax with a sectionid to
		// list the categories in that section
		require_once DIR . '/includes/functions_misc.php';
		$vbulletin->input->clean_array_gpc('r', array(
			'sectionid' => TYPE_UINT,
		));

		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('root');

		$xml->add_tag('html',  vBCms_ContentManager::getCategoryList($vbulletin->GPC['sectionid']));
		$xml->close_group();
		$xml->print_xml();
		break;

	case 'find_categories': //This is called to list categories for the
		//article edit page. It creates a scrolling list. You can give it a string,
		// or a category, or a section.
		//we're just returning xml for ajax we don't want any headers, etc.
		require_once DIR . '/includes/functions_misc.php';
		$vbulletin->input->clean_array_gpc('r', array(
			'order' => TYPE_UINT,
		));

		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('root');
		$html = vBCms_ContentManager::getCategorySelector();

		if (!empty($html))
		{
			$xml->add_tag('html',  $html);
			$xml->close_group();
			$xml->print_xml();
		}
		break;

	case 'clear_cache':
		/* This is now located in misc.php and called from the maintenance 
		section, because the cache is now used by functions outside of the CMS.
		Nothing should actually call this, but just in case, display a message. */
		print_cp_header($vbphrase['vbcms']);
		print_cp_message($vbphrase['no_cache_clean']);
		print_cp_footer();
		break;

	case 'perpage': //Here are are updating the number of rows per page
		// This needs to be saved as a user preference. Then we will display
		// results again, which we will do because we don't have a "break"
		$current_user = new vB_Legacy_CurrentUser();
		$vbulletin->input->clean_array_gpc('r', array(
			'perpage' => TYPE_UINT,
			'sentfrom' => TYPE_STR,
		));

		// print_cp_header needs to come before calling vBCms_ContentManager::savePerPage
		// which starts printing a table, creating whitespace before the CP header
		print_cp_header($vbphrase['vbcms']);
		$per_page = vBCms_ContentManager::savePerPage($current_user);

		switch($vbulletin->GPC['sentfrom'])
		{
			case 'section':
				$redirect_do = 'section';
				break;
			case 'category':
				$redirect_do = 'category';
				break;
			case 'nodes':
			default:
				$redirect_do = 'list';
		}
		print_cp_message($vbphrase['saved_perpage'], 'cms_content_admin.php?' . $vbulletin->session->vars['sessionurl'] . "do=$redirect_do");
		print_cp_footer();
		break;

	case 'filter_category':
	case 'category': //Here we are viewing the category browser.
		$vbulletin->input->clean_array_gpc('r', array(
			'nodeid'       => TYPE_INT,
			'title_filter' => TYPE_STR,
			'categoryid'   => TYPE_INT,
			'sectionid'    => TYPE_INT,
			'page'         => TYPE_INT,
			'level'        => TYPE_UINT,
			));
		print_cp_header($vbphrase['category_manager']);

		if ($vbulletin->GPC_exists['sectionid'])
		{
			echo vBCms_ContentManager::showCategories($vbulletin->GPC['sectionid'], $per_page,
				$vbulletin->GPC['page']);
		} else
		{
			echo vBCms_ContentManager::showCategories(false, $per_page,
				$vbulletin->GPC['page']);
		}
		print_cp_footer();
		break;

	case 'delete_category' :
	case 'save_category':
	case 'new_category' :
	case 'save_categories':
	case 'move_category' :
	case 'saveonecategorystate':
		$vbulletin->input->clean_array_gpc('r', array(
			'categoryid'        => TYPE_INT,
			'sectionid'         => TYPE_INT,
			'target_categoryid' => TYPE_INT,
			'title'             => TYPE_STR,
			'page'              => TYPE_INT,
		));

		if ($vbulletin->GPC_exists['sectionid'] OR $vbulletin->GPC_exists['categoryid'])
		{
			echo vBCms_ContentManager:: updateCategories();
		}
		print_cp_header($vbphrase['category_manager']);
		echo vBCms_ContentManager::showCategories($vbulletin->GPC['sectionid'], $per_page,
				$vbulletin->GPC['page']);
		print_cp_footer();
		break;

	case 'move_node' :
	case 'publish_nodes' :
	case 'unpublish_nodes' :
	case 'save_nodes':
	case 'delete_nodes' :
	case 'set_order':
	case 'new':
		echo vBCms_ContentManager:: updateSections();

		print_cp_header($vbphrase['content_manager']);
		echo vBCms_ContentManager::showNodes($per_page);
		print_cp_footer();
		break;


	case 'delete_section' :
	case 'move_section' :
	case 'publish_section' :
	case 'unpublish_section' :
	case 'saveonetitle':
	case 'saveonelayout':
	case 'saveonestyle':
	case 'save_section' :
	case 'saveonecl':
	case 'sectionpriority':
	case 'sectionpp':
	case 'saveonesectionstate':
	case 'new_section':
	case 'set_order':
	case 'swap_sections':
		echo vBCms_ContentManager::updateSections();

		print_cp_header($vbphrase['section_manager']);
		echo vBCms_ContentManager::showSections($per_page);
		print_cp_footer();
		break;

	case 'saveonenodestate':
		vBCms_ContentManager::updateSections();

		print_cp_header($vbphrase['content_manager']);
		echo vBCms_ContentManager::showNodes($per_page);
		print_cp_footer();
		break;

	case 'section': //Here we are viewing the category browser.
		$vbulletin->input->clean_array_gpc('r', array(
			'nodeid'    => TYPE_INT,
			'sectionid' => TYPE_INT,
			'level'     => TYPE_UINT,
		));
		print_cp_header($vbphrase['section_manager']);

		if (! $vbulletin->GPC_exists['level'])
		{
			$vbulletin->GPC['level'] = '-1';
		}

		echo vBCms_ContentManager::showSections($per_page);
		print_cp_footer();

		break;

	case 'nodecontent': //Here we are view a specific node contents
		$vbulletin->input->clean_array_gpc('r', array(
			'nodegroup' => TYPE_INT,
		));
		print_cp_header($vbphrase['content_manager']);
		echo vBCms_ContentManager::showNodes($per_page);
		print_cp_footer();
		break;

	case 'sort':
	case 'filter':
		//figure out what type we're doing. It could be category or section.
		$vbulletin->input->clean_array_gpc('r', array(
			'sortby'         => TYPE_STR,
			'sortdir'        => TYPE_STR,
			'title_filter'   => TYPE_STR,
			'submit'         => TYPE_STR,
			'state_filter'   => TYPE_INT,
			'author_filter'  => TYPE_UINT,
			'filter_section' => TYPE_UINT,
			'sentfrom'       => TYPE_STR,
			'contenttypeid'  => TYPE_INT,
		));

		if ($vbulletin->GPC_exists['sentfrom'] and $vbulletin->GPC['sentfrom'] == 'nodes')
		{
			print_cp_header($vbphrase['content_manager']);
			echo vBCms_ContentManager::showNodes($per_page);
			print_cp_footer();
		}

		if (($vbulletin->GPC_exists['sentfrom'] and $vbulletin->GPC['sentfrom'] == 'section')
			or ($vbulletin->GPC_exists['contenttypeid']
			and $vbulletin->GPC['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBCms_Section')))
		{
			print_cp_header($vbphrase['section_manager']);
			echo vBCms_ContentManager::showSections($per_page);
			print_cp_footer();
		}
		else if (($vbulletin->GPC_exists['sentfrom'] and $vbulletin->GPC['sentfrom'] == 'category') or
			($vbulletin->GPC_exists['contenttypeid']
			and $vbulletin->GPC['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBCms_Category')))
		{
			print_cp_header($vbphrase['category_manager']);
			echo vBCms_ContentManager::showNodes($per_page, 'category');
			print_cp_footer();
		}
		 else
		{
			print_cp_header($vbphrase['content_manager']);
			echo vBCms_ContentManager::showNodes($per_page);
			print_cp_footer();
		}
	break;

	case 'filter_section':
		//figure out what type we're doing. It could be category or section.
		$vbulletin->input->clean_array_gpc('r', array(
			'sectionid' => TYPE_INT));
		print_cp_header($vbphrase['section_manager']);
		echo vBCms_ContentManager::showSections($per_page);
		print_cp_footer();
	break;

	case 'filter_nodesection':
		//figure out what type we're doing. It could be category or section.
		$vbulletin->input->clean_array_gpc('r', array(
			'sectionid' => TYPE_INT,
		));
		print_cp_header($vbphrase['content_manager']);
		echo vBCms_ContentManager::showNodes($per_page);
		print_cp_footer();
		break;

	case 'save':
		//figure out what type we're doing. It could be category or section.
		$vbulletin->input->clean_array_gpc('r', array(
			'sortby'         => TYPE_STR,
			'title_filter'   => TYPE_STR,
			'submit'         => TYPE_STR,
			'state_filter'   => TYPE_INT,
			'author_filter'  => TYPE_UINT,
			'filter_section' => TYPE_UINT,
			'sentfrom'       => TYPE_STR,
			'contenttypeid'  => TYPE_INT,
		));

		if ($vbulletin->GPC_exists['sentfrom'] and $vbulletin->GPC['sentfrom'] == 'section')
		{
			print_cp_header($vbphrase['section_manager']);
			vBCms_ContentManager::updateSections();
			echo vBCms_ContentManager::showSections($per_page);
			print_cp_footer();
		}
		else if (($vbulletin->GPC_exists['sentfrom'] and $vbulletin->GPC['sentfrom'] == 'category') or
			($vbulletin->GPC_exists['contenttypeid']
			and $vbulletin->GPC['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBCms_Category')))
		{
			print_cp_header($vbphrase['category_manager']);
			vBCms_ContentManager::updateCategories();
			echo vBCms_ContentManager::showNodes($per_page, 'category');
			print_cp_footer();
		}
		else if ($vbulletin->GPC_exists['sentfrom'] and $vbulletin->GPC['sentfrom'] == 'nodes')
		{
			print_cp_header($vbphrase['content_manager']);
			vBCms_ContentManager::updateSections();
			echo vBCms_ContentManager::showNodes($per_page);
			print_cp_footer();
		}

	case 'fix_nodes':
		print_cp_header($vbphrase['vbcms']);
		echo vBCms_ContentManager::fixNodeLR();
		print_cp_message($vbphrase['nodetable_repaired']);
		print_cp_footer();
		break;

	case 'list': //This is our default action. We need to display a list of items.

	default:
		$vbulletin->input->clean_array_gpc('r', array(
			'sortby'         => TYPE_STR,
			'sortdir'        => TYPE_STR,
			'title_filter'   => TYPE_STR,
			'submit'         => TYPE_STR,
			'state_filter'   => TYPE_INT,
			'author_filter'  => TYPE_UINT,
			'filter_section' => TYPE_UINT,
			'contenttypeid'  => TYPE_INT,
		));
		print_cp_header($vbphrase['content_manager']);
		echo vBCms_ContentManager::showNodes($per_page);
		print_cp_footer();

	break;
}


/*======================================================================*\
   || ####################################################################
   || # Downloaded: 16:55, Fri Jul 19th 2013
   || # SVN: $Revision: 27874 $
   || ####################################################################
   \*======================================================================*/
