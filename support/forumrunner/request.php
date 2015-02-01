<?php
/*
 * Forum Runner
 *
 * Copyright (c) 2010-2011 to End of Time Studios, LLC
 *
 * This file may not be redistributed in whole or significant part.
 *
 * http://www.forumrunner.com
 */

define('MCWD', (($getcwd = getcwd()) ? $getcwd : '.'));
define('IN_FRNR', true);
define('VBSEO_UNREG_EXPIRED', true);
define('BYPASS_AEO', true);

if (isset($_REQUEST['d'])) {
    error_reporting(E_ALL);
} else {
    header('Content-type: application/json');
    error_reporting(0);
}

require_once(MCWD . '/version.php');
require_once(MCWD . '/support/utils.php');
require_once(MCWD . '/support/JSON.php');
require_once(MCWD . '/include/general_vb.php');

if (file_exists(MCWD . '/branded.php')) {
    require_once(MCWD .'/branded.php');
}

$phrasegroups = array();
$specialtemplates = array();
$globaltemplates = array();
$actiontemplates = array();

$processed = process_input(array('cmd' => STRING, 'frv' => STRING, 'frp' => STRING));
if (!$processed['cmd']) {
    return;
}

$frcl_version = '1.3.3';
$frcl_platform = 'ip';
if (isset($processed['frv'])) {
    $frcl_version = $processed['frv'];
}
if (isset($processed['frp'])) {
    $frcl_platform = $processed['frp'];
}

require_once(MCWD . '/support/common_methods.php');
require_once(MCWD . '/support/vbulletin_methods.php');
if (file_exists(MCWD . '/support/other_methods.php')) {
    require_once(MCWD . '/support/other_methods.php');
}

if (!isset($methods[$processed['cmd']])) {
    json_error(ERR_NO_PERMISSION);
}

if ($methods[$processed['cmd']]['include']) {
    require_once(MCWD . '/include/' . $methods[$processed['cmd']]['include']);
}

if (isset($_REQUEST['d'])) {
    error_reporting(E_ALL);
}

$out = call_user_func($methods[$processed['cmd']]['function']);

fr_exec_shut_down(false);

$json_out = array(
    'success' => true,
    'data' => $out,
    'ads' => fr_show_ad(),
);

// Return Unread PM/Subscribed Threads count
if ($vbulletin->userinfo['userid'] > 0 &&
    $processed['cmd'] != 'get_new_updates' &&
    $processed['cmd'] != 'login')
{
    if ($vbulletin->userinfo['userid'] > 0) {
	$json_out['pm_notices'] = get_pm_unread();
	$json_out['sub_notices'] = get_sub_thread_updates();
    }
}

fr_exec_shut_down(true);

$json = new Services_JSON();
print $json->encode($json_out);

?>