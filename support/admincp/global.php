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

// identify where we are
define('VB_AREA', 'AdminCP');
define('VB_ENTRY', 1);
define('IN_CONTROL_PANEL', true);

if (!isset($phrasegroups) OR !is_array($phrasegroups))
{
	$phrasegroups = array();
}
$phrasegroups[] = 'cpglobal';

if (!isset($specialtemplates) OR !is_array($specialtemplates))
{
	$specialtemplates = array();
}
$specialtemplates[] = 'mailqueue';
$specialtemplates[] = 'pluginlistadmin';

// ###################### Start functions #######################
chdir('./../');
define('CWD', (($getcwd = getcwd()) ? $getcwd : '.'));

require_once(CWD . '/includes/init.php');
require_once(DIR . '/includes/adminfunctions.php');

// ###################### Start headers (send no-cache) #######################
exec_nocache_headers();

if ($vbulletin->userinfo['cssprefs'] != '')
{
	$vbulletin->options['cpstylefolder'] = $vbulletin->userinfo['cssprefs'];
}

# cache full permissions so scheduled tasks will have access to them
$permissions = cache_permissions($vbulletin->userinfo);
$vbulletin->userinfo['permissions'] =& $permissions;

if (!($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
{
	$checkpwd = 1;
}

// ###################### Get date / time info #######################
// override date/time settings if specified
fetch_options_overrides($vbulletin->userinfo);
fetch_time_data();

// ############################################ LANGUAGE STUFF ####################################
// initialize $vbphrase and set language constants
$vbphrase = init_language();
if ($stylestuff = $vbulletin->db->query_first_slave("
	SELECT styleid, dateline, title
	FROM " . TABLE_PREFIX . "style
	WHERE styleid = " . $vbulletin->options['styleid'] . "
	ORDER BY styleid " . ($styleid > $vbulletin->options['styleid'] ? 'DESC' : 'ASC') . "
	LIMIT 1
"))
{
	fetch_stylevars($stylestuff, $vbulletin->userinfo);
}
else
{
	$_tmp = NULL;
	fetch_stylevars($_tmp, $vbulletin->userinfo);
}

// ############################################ Check for files existance ####################################
if (empty($vbulletin->debug) and !defined('BYPASS_FILE_CHECK'))
{
	// check for files existance. Potential security risks!
	if (file_exists(DIR . '/install/install.php') == true)
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			define('CP_CONTINUE', $vbulletin->scriptpath);
		}
		print_stop_message('security_alert_x_still_exists', 'install.php');
	}
	else if (file_exists(DIR . '/install/tools.php'))
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			define('CP_CONTINUE', $vbulletin->scriptpath);
		}
		print_stop_message('security_alert_tools_still_exists_in_x', 'install');
	}
	else if (file_exists(DIR . '/' . $vbulletin->config['Misc']['admincpdir'] . '/tools.php'))
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			define('CP_CONTINUE', $vbulletin->scriptpath);
		}
		print_stop_message('security_alert_tools_still_exists_in_x', $vbulletin->config['Misc']['admincpdir']);
	}
	else if (file_exists(DIR . '/' . $vbulletin->config['Misc']['modcpdir'] . '/tools.php'))
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			define('CP_CONTINUE', $vbulletin->scriptpath);
		}
		print_stop_message('security_alert_tools_still_exists_in_x', $vbulletin->config['Misc']['modcpdir']);
	}
}

// ############################################ Start Login Check ####################################
$vbulletin->input->clean_array_gpc('p', array(
	'adminhash' => TYPE_STR,
	'ajax'      => TYPE_BOOL,
));

assert_cp_sessionhash();

if (!CP_SESSIONHASH OR $checkpwd OR ($vbulletin->options['timeoutcontrolpanel'] AND !$vbulletin->session->vars['loggedin']))
{
	// #############################################################################
	// Put in some auto-repair ;)
	$check = array();

	$spectemps = $db->query_read("SELECT title FROM " . TABLE_PREFIX . "datastore");
	while ($spectemp = $db->fetch_array($spectemps))
	{
		$check["$spectemp[title]"] = true;
	}
	$db->free_result($spectemps);

	if (!$check['maxloggedin'])
	{
		build_datastore('maxloggedin', '', 1);
	}
	if (!$check['smiliecache'])
	{
		build_datastore('smiliecache', '', 1);
		build_image_cache('smilie');
	}
	if (!$check['iconcache'])
	{
		build_datastore('iconcache', '', 1);
		build_image_cache('icon');
	}
	if (!$check['bbcodecache'])
	{
		build_datastore('bbcodecache', '', 1);
		build_bbcode_cache();
	}
	if (!$check['ranks'])
	{
		require_once(DIR . '/includes/functions_ranks.php');
		build_ranks();
	}
	if (!$check['userstats'])
	{
		build_datastore('userstats', '', 1);
		require_once(DIR . '/includes/functions_databuild.php');
		build_user_statistics();
	}
	if (!$check['mailqueue'])
	{
		build_datastore('mailqueue');
	}
	if (!$check['cron'])
	{
		build_datastore('cron');
	}
	if (!$check['attachmentcache'])
	{
		build_datastore('attachmentcache', '', 1);
	}
	if (!$check['wol_spiders'])
	{
		build_datastore('wol_spiders', '', 1);
	}
	if (!$check['banemail'])
	{
		build_datastore('banemail');
	}
	if (!$check['stylecache'])
	{
		require_once(DIR . '/includes/adminfunctions_template.php');
		build_style_datastore();
	}
	if (!$check['usergroupcache'] OR !$check['forumcache'])
	{
		build_forum_permissions();
	}
	if (!$check['bookmarksitecache'])
	{
		require_once(DIR . '/includes/adminfunctions_bookmarksite.php');
		build_bookmarksite_datastore();
	}
	if (!$check['noticecache'])
	{
		build_datastore('noticecache', '', 1);
	}
	if (!$check['loadcache'])
	{
		update_loadavg();
	}
	if (!$check['prefixcache'])
	{
		require_once(DIR . '/includes/adminfunctions_prefix.php');
		build_prefix_datastore();
	}
	//making sure the product datastore is rebuilt (maybe after products datastore is deleted)
	if (!$check['products'])
	{
		build_product_datastore();
	}
	($hook = vBulletinHook::fetch_hook('admin_global_datastore_check')) ? eval($hook) : false;

	// end auto-repair
	// #############################################################################
	print_cp_login();
}
else if ($_POST['do'] AND ADMINHASH != $vbulletin->GPC['adminhash'])
{
	if ($_POST['login_redirect'])
	{
		unset($_REQUEST['do']);
		unset($_POST['do']);
		unset($_GET['do']);
	}
	else
	{
		print_cp_login(true);	
	}
}

if (file_exists(DIR . '/includes/version_vbulletin.php'))
{
	include_once(DIR . '/includes/version_vbulletin.php');
}
if (defined('FILE_VERSION_VBULLETIN') AND FILE_VERSION_VBULLETIN !== '')
{
	define('ADMIN_VERSION_VBULLETIN', FILE_VERSION_VBULLETIN);
}
else
{
	define('ADMIN_VERSION_VBULLETIN', $vbulletin->options['templateversion']);
}

($hook = vBulletinHook::fetch_hook('admin_global')) ? eval($hook) : false;

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 62099 $
|| ####################################################################
\*======================================================================*/
?>
