<?php

/************************************************************************************
* vBSEO 3.6.1 for vBulletin v3.x & v4.x by Crawlability, Inc.                       *
*                                                                                   *
* Copyright © 2011, Crawlability, Inc. All rights reserved.                         *
* You may not redistribute this file or its derivatives without written permission. *
*                                                                                   *
* Sales Email: sales@crawlability.com                                               *
*                                                                                   *
*----------------------------vBSEO IS NOT FREE SOFTWARE-----------------------------*
* http://www.crawlability.com/vbseo/license/                                        *
************************************************************************************/

error_reporting(E_ALL & ~(E_NOTICE|E_STRICT|E_DEPRECATED));
define('THIS_SCRIPT', 'admin_vbseo');
$phrasegroups = array('cphome', 'plugins');
$specialtemplates = array();
$globaltemplates = array();
$actiontemplates = array();
include_once dirname(__FILE__).'/../vbseo/includes/functions_vbseo.php';
require_once('./global.'.VBSEO_VB_EXT);
if (!can_administer('canadminplugins')) {
print_cp_no_permission();
}
print_cp_header('vBSEO Installation');
if ($_REQUEST['keepdata']) {
print_form_header();
print_table_header('vBSEO data has been left in database unchanged for further usage', 2, 0, '', 'center', 0);
print_table_footer(2, construct_button_code($vbphrase['click_here_to_continue_processing'], 'index.'.VBSEO_VB_EXT.'?' . $vbulletin->session->vars['sessionurl'] . 'do=buildbitfields'));
}else
if ($_REQUEST['do'] == 'kill') {
$db->hide_errors();
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('forum') . " DROP COLUMN vbseo_moderatepingbacks");
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('forum') . " DROP COLUMN vbseo_moderatetrackbacks");
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('forum') . " DROP COLUMN vbseo_moderaterefbacks");
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('forum') . " DROP COLUMN vbseo_enable_likes");
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('thread') . " DROP COLUMN vbseo_linkbacks_no");
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('thread') . " DROP COLUMN vbseo_likes");
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('blog') . " DROP COLUMN vbseo_likes");
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('cms_nodeinfo') . " DROP COLUMN vbseo_likes");
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('user') . " DROP COLUMN vbseo_likes_in");
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('user') . " DROP COLUMN vbseo_likes_out");
$db->query_write("ALTER TABLE " . vbseo_tbl_prefix('user') . " DROP COLUMN vbseo_likes_unread");
$db->query_write("DROP TABLE IF EXISTS " . vbseo_tbl_prefix('vbseo_likes'). "");
$db->query_write("DROP TABLE IF EXISTS " . vbseo_tbl_prefix('trackback'). "");
$db->query_write("DROP TABLE IF EXISTS " . vbseo_tbl_prefix('vbseo_linkback') . "");
$db->query_write("DROP TABLE IF EXISTS " . vbseo_tbl_prefix('vbseo_blacklist') . "");
$db->query_write("DROP TABLE IF EXISTS " . vbseo_tbl_prefix('vbseo_serviceupdate') . "");
$db->query_write("DROP TABLE IF EXISTS " . vbseo_tbl_prefix('vbseo_likes') . "");
require_once(DIR . '/includes/class_bitfield_builder.'.VBSEO_VB_EXT);
vB_Bitfield_Builder::save($db);
build_forum_permissions();
$db->show_errors();
print_form_header();
print_table_header('vBSEO data has been cleaned up successfully', 2, 0, '', 'center', 0);
print_table_footer(2, construct_button_code($vbphrase['click_here_to_continue_processing'], 'index.'.VBSEO_VB_EXT.'?' . $vbulletin->session->vars['sessionurl'] . 'do=buildbitfields'));
}
print_cp_footer();
?>