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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 63817 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cphome');
$specialtemplates = array('maxloggedin', 'acpstats');

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// #############################################################################
// ########################### START MAIN SCRIPT ###############################
// #############################################################################

// ############################## Start build_acpstats_datastore ####################################
/**
* Stores a cache of various data for ACP Home Quick Stats into the datastore.
*/
function build_acpstats_datastore()
{
	global $vbulletin, $starttime, $mysqlversion;

	$data = $vbulletin->db->query_first("SELECT SUM(filesize) AS size FROM " . TABLE_PREFIX . "filedata");
	$vbulletin->acpstats['attachsize'] = $data['size'];
	$data = $vbulletin->db->query_first("SELECT SUM(filesize) AS size FROM " . TABLE_PREFIX . "customavatar");
	$vbulletin->acpstats['avatarsize'] = $data['size'];
	$data = $vbulletin->db->query_first("SELECT SUM(filesize) AS size FROM " . TABLE_PREFIX . "customprofilepic");
	$vbulletin->acpstats['profilepicsize'] = $data['size'];

	$data = $vbulletin->db->query_first("SELECT COUNT(*) AS count FROM " . TABLE_PREFIX . "user WHERE joindate >= $starttime");
	$vbulletin->acpstats['newusers'] = $data['count'];
	$data = $vbulletin->db->query_first("SELECT COUNT(*) AS count FROM " . TABLE_PREFIX . "user WHERE lastactivity >= $starttime");
	$vbulletin->acpstats['userstoday'] = $data['count'];

	$data = $vbulletin->db->query_first("SELECT COUNT(*) AS count FROM " . TABLE_PREFIX . "post WHERE dateline >= $starttime");
	$vbulletin->acpstats['newposts'] = $data['count'];

	$vbulletin->acpstats['indexsize'] = 0;
	$vbulletin->acpstats['datasize'] = 0;
	if ($mysqlversion['version'] >= '3.23')
	{
		$vbulletin->db->hide_errors();
		$tables = $vbulletin->db->query_write("SHOW TABLE STATUS");
		$errno = $vbulletin->db->errno;
		$vbulletin->db->show_errors();
		if (!$errno)
		{
			while ($table = $vbulletin->db->fetch_array($tables))
			{
				$vbulletin->acpstats['datasize'] += $table['Data_length'];
				$vbulletin->acpstats['indexsize'] += $table['Index_length'];
			}
		}
	}
	if (!$vbulletin->acpstats['indexsize'])
	{
		$vbulletin->acpstats['indexsize'] = -1;
	}
	if (!$vbulletin->acpstats['datasize'])
	{
		$vbulletin->acpstats['datasize'] = -1;
	}
	$vbulletin->acpstats['lastupdate'] = TIMENOW;
	build_datastore('acpstats', serialize($vbulletin->acpstats), 1);
}

if (empty($_REQUEST['do']))
{
	log_admin_action();
}

// #############################################################################

$vbulletin->input->clean_array_gpc('r', array(
	'redirect' => TYPE_STR,
	'nojs' 		=> TYPE_BOOL,
));

// #############################################################################
// ################################## REDIRECTOR ###############################
// #############################################################################

if (!empty($vbulletin->GPC['redirect']))
{
	require_once(DIR . '/includes/functions_login.php');
	$redirect = htmlspecialchars_uni(fetch_replaced_session_url($vbulletin->GPC['redirect']));
	$redirect = create_full_url($redirect);
	$redirect = preg_replace(
		array('/&#0*59;?/', '/&#x0*3B;?/i', '#;#'),
		'%3B',
		$redirect
	);
	$redirect = preg_replace('#&amp%3B#i', '&amp;', $redirect);

	print_cp_header($vbphrase['redirecting_please_wait'], '', "<meta http-equiv=\"Refresh\" content=\"0; URL=$redirect\" />");
	echo "<p>&nbsp;</p><blockquote><p>$vbphrase[redirecting_please_wait]</p></blockquote>";
	print_cp_footer();
	exit;
}

// #############################################################################
// ############################### LOG OUT OF CP ###############################
// #############################################################################

if ($_REQUEST['do'] == 'cplogout')
{
	vbsetcookie('cpsession', '', false, true, true);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "cpsession WHERE userid = " . $vbulletin->userinfo['userid'] . " AND hash = '" . $db->escape_string($vbulletin->GPC[COOKIE_PREFIX . 'cpsession']) . "'");
	vbsetcookie('customerid', '', 0);
	if (!empty($vbulletin->session->vars['sessionurl_js']))
	{
		exec_header_redirect('index.php?' . $vbulletin->session->vars['sessionurl_js']);
	}
	else
	{
		exec_header_redirect('index.php');
	}
}

// #############################################################################
// ################################# SAVE NOTES ################################
// #############################################################################

if ($_POST['do'] == 'notes')
{
	$vbulletin->input->clean_array_gpc('p', array('notes' => TYPE_STR));

	$admindm =& datamanager_init('Admin', $vbulletin, ERRTYPE_CP);
	$admindm->set_existing($vbulletin->userinfo);
	$admindm->set('notes', $vbulletin->GPC['notes']);
	$admindm->save();
	unset($admindm);

	$vbulletin->userinfo['notes'] = htmlspecialchars_uni($vbulletin->GPC['notes']);
	$_REQUEST['do'] = 'home';
}

// #############################################################################
// ################################# HEADER FRAME ##############################
// #############################################################################

$versionhost = REQ_PROTOCOL . '://version.vbulletin.com';

if ($_REQUEST['do'] == 'head')
{
	ignore_user_abort(true);

	define('IS_NAV_PANEL', true);
	print_cp_header('', '');

	$forumhomelink = create_full_url(fetch_seo_url('forumhome', array()), true);

	?>
	<table border="0" width="100%" height="100%">
	<tr align="center" valign="top">
		<td style="text-align:<?php echo vB_Template_Runtime::fetchStyleVar('left'); ?>"><a href="http://www.vbulletin.com/" target="_blank"><b><?php echo $vbphrase['admin_control_panel']; ?></b> (vBulletin <?php echo ADMIN_VERSION_VBULLETIN . print_form_middle('VBF02D260D'); ?>)<?php echo iif(is_demo_mode(), ' <b>DEMO MODE</b>'); ?></a></td>
		<td><a href="http://members.vbulletin.com/" id="head_version_link" target="_blank">&nbsp;</a></td>
		<td style="white-space:nowrap; text-align:<?php echo vB_Template_Runtime::fetchStyleVar('right'); ?>; font-weight:bold">
			<a href="<?php echo $forumhomelink; ?>" target="_blank"><?php echo $vbphrase['forum_home_page']; ?></a>
			|
			<a href="index.php?<?php echo $vbulletin->session->vars['sessionurl']; ?>do=cplogout" onclick="return confirm('<?php echo $vbphrase['sure_you_want_to_log_out_of_cp']; ?>');"  target="_top"><?php echo $vbphrase['log_out']; ?></a>
		</td>
	</tr>
	</table>
	<script type="text/javascript" src="<?php echo $versionhost; ?>/version.js?v=<?php echo SIMPLE_VERSION; ?>&amp;id=VBF02D260D&amp;pid=vbulletinsuite"></script>
	<script type="text/javascript">
	<!--
	fetch_object('head_version_link').innerHTML = construct_phrase('<?php echo $vbphrase['latest_version_available_x']; ?>', ((typeof(vb_version) == 'undefined' || vb_version == '') ? '<?php echo $vbphrase['n_a']; ?>' : vb_version));
	//-->
	</script>
	<?php

	define('NO_CP_COPYRIGHT', true);
	unset($DEVDEBUG);
	print_cp_footer();

}

$vbulletin->input->clean_array_gpc('r', array('navprefs' => TYPE_STR));
$vbulletin->GPC['navprefs'] = preg_replace('#[^a-z0-9_,]#i', '', $vbulletin->GPC['navprefs']);

// #############################################################################
// ############################### SAVE NAV PREFS ##############################
// #############################################################################

if ($_REQUEST['do'] == 'navprefs')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'groups'	=> TYPE_STR,
		'expand'	=> TYPE_BOOL,
		'navprefs'	=> TYPE_STR
	));

	$vbulletin->GPC['groups'] = preg_replace('#[^a-z0-9_,]#i', '', $vbulletin->GPC['groups']);

	if ($vbulletin->GPC['expand'])
	{
		$groups = explode(',', $vbulletin->GPC['groups']);

		foreach ($groups AS $group)
		{
			if (empty($group))
			{
				continue;
			}

			$vbulletin->input->clean_gpc('r', "num$group", TYPE_UINT);

			for ($i = 0; $i < $vbulletin->GPC["num$group"]; $i++)
			{
				$vbulletin->GPC['navprefs'][] = $group . "_$i";
			}
		}

		$vbulletin->GPC['navprefs'] = implode(',', $vbulletin->GPC['navprefs']);
	}
	else
	{
		$vbulletin->GPC['navprefs'] = '';
	}

	$vbulletin->GPC['navprefs'] = preg_replace('#[^a-z0-9_,]#i', '', $vbulletin->GPC['navprefs']);

	$_REQUEST['do'] = 'savenavprefs';
}

if ($_REQUEST['do'] == 'buildbitfields')
{
	require_once(DIR . '/includes/class_bitfield_builder.php');
	vB_Bitfield_Builder::save($db);
	build_forum_permissions();

	define('CP_REDIRECT', 'index.php');
	print_stop_message('rebuilt_bitfields_successfully');

}

if ($_REQUEST['do'] == 'buildvideo')
{
	require_once(DIR . '/includes/functions_databuild.php');
	build_bbcode_video();

	print_cp_header();
	require_once(DIR . '/includes/adminfunctions_template.php');
	build_all_styles(0, 0, '', false, 'standard');
	build_all_styles(0, 0, '', false, 'mobile');

	define('CP_REDIRECT', 'index.php');
	print_stop_message('rebuilt_video_bbcodes_successfully');
}

if ($_REQUEST['do'] == 'buildnavprefs')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'prefs' 	=> TYPE_STR,
		'dowhat'	=> TYPE_STR,
		'id'		=> TYPE_INT
	));

	$vbulletin->GPC['prefs'] = preg_replace('#[^a-z0-9_,]#i', '', $vbulletin->GPC['prefs']);
	$_tmp = preg_split('#,#', $vbulletin->GPC['prefs'], -1, PREG_SPLIT_NO_EMPTY);
	$_navprefs = array();

	foreach ($_tmp AS $_val)
	{
		$_navprefs["$_val"] = $_val;
	}
	unset($_tmp);

	if ($vbulletin->GPC['dowhat'] == 'collapse')
	{
		// remove an item from the list
		unset($_navprefs[$vbulletin->GPC['id']]);
	}
	else
	{
		// add an item to the list
		$_navprefs[$vbulletin->GPC['id']] = $vbulletin->GPC['id'];
		ksort($_navprefs);
	}

	$vbulletin->GPC['navprefs'] = implode(',', $_navprefs);
	$_REQUEST['do'] = 'savenavprefs';
}

if ($_REQUEST['do'] == 'savenavprefs')
{
	$admindm =& datamanager_init('Admin', $vbulletin, ERRTYPE_CP);
	$admindm->set_existing($vbulletin->userinfo);
	$admindm->set('navprefs', $vbulletin->GPC['navprefs']);
	$admindm->save();
	unset($admindm);

	$_NAVPREFS = preg_split('#,#', $vbulletin->GPC['navprefs'], -1, PREG_SPLIT_NO_EMPTY);
	$_REQUEST['do'] = 'nav';
}

// ################################ NAVIGATION FRAME #############################

if ($_REQUEST['do'] == 'nav')
{
	require_once(DIR . '/includes/adminfunctions_navpanel.php');
	print_cp_header();

	echo "\n<div>";
	?><img src="../cpstyles/<?php echo $vbulletin->options['cpstylefolder']; ?>/cp_logo.gif" title="<?php echo $vbphrase['admin_control_panel']; ?>" alt="" border="0" hspace="4" <?php $df = print_form_middle("VBF02D260D"); ?> vspace="4" /><?php
	echo "</div>\n\n" . iif(is_demo_mode(), "<div align=\"center\"><b>DEMO MODE</b></div>\n\n") . "<div style=\"width:168px; padding: 4px\">\n";

	// cache nav prefs
	can_administer();
	construct_nav_spacer();

	$navigation = array(); // [displayorder][phrase/text] = array([group], [options][disporder][])

	require_once(DIR . '/includes/class_xml.php');

	$navfiles = array();
	if ($handle = @opendir(DIR . '/includes/xml/'))
	{
		while (($file = readdir($handle)) !== false)
		{
			if (!preg_match('#^cpnav_(.*).xml$#i', $file, $matches))
			{
				continue;
			}
			$nav_key = preg_replace('#[^a-z0-9]#i', '', $matches[1]);
			$navfiles["$nav_key"] = $file;
		}
		closedir($handle);
	}

	if (empty($navfiles['vbulletin']))	// opendir failed or cpnav_vbulletin.xml is missing
	{
		if (is_readable(DIR . '/includes/xml/cpnav_vbulletin.xml'))
		{
			$navfiles['vbulletin'] = 'cpnav_vbulletin.xml';
		}
		else
		{
			echo construct_phrase($vbphrase['could_not_open_x'], DIR . '/includes/xml/cpnav_vbulletin.xml');
			exit;
		}
	}

	foreach ($navfiles AS $nav_file => $file)
	{
		$xmlobj = new vB_XML_Parser(false, DIR . "/includes/xml/$file");
		$xml =& $xmlobj->parse();

		if ($xml['product'] AND empty($vbulletin->products["$xml[product]"]))
		{
			// attached to a specific product and that product isn't enabled
			continue;
		}

		if (!is_array($xml['navgroup'][0]))
		{
			$xml['navgroup'] = array($xml['navgroup']);
		}

		foreach ($xml['navgroup'] AS $navgroup)
		{
			if (!empty($navgroup['debug']) AND $vbulletin->debug != 1)
			{
				continue;
			}

			// do we have access to this group
			if (empty($navgroup['permissions']) OR can_administer($navgroup['permissions']))
			{
				$group_displayorder = intval($navgroup['displayorder']);
				$group_key = fetch_nav_text($navgroup);

				if (!isset($navigation["$group_displayorder"]["$group_key"]))
				{
					$navigation["$group_displayorder"]["$group_key"] = array('options' => array());
				}
				$local_options =& $navigation["$group_displayorder"]["$group_key"]['options'];

				if (!is_array($navgroup['navoption'][0]))
				{
					$navgroup['navoption'] = array($navgroup['navoption']);
				}
				foreach ($navgroup['navoption'] AS $navoption)
				{
					if (
						(!empty($navoption['debug']) AND $vbulletin->debug != 1)
							OR
						(!empty($navoption['permissions']) AND !can_administer($navoption['permissions']))
					)
					{
						continue;
					}

					$navoption['link'] = str_replace(
						array(
							'{$vbulletin->config[Misc][modcpdir]}',
							'{$vbulletin->config[Misc][admincpdir]}'
						),
						array($vbulletin->config['Misc']['modcpdir'], $vbulletin->config['Misc']['admincpdir']),
						$navoption['link']
					);

					$navoption['text'] = fetch_nav_text($navoption);

					$local_options[intval($navoption['displayorder'])]["$navoption[text]"] = $navoption;
				}

				if (!isset($navigation["$group_displayorder"]["$group_key"]['group']) OR $xml['master'])
				{
					unset($navgroup['navoption']);
					$navgroup['nav_file'] = $nav_file;
					$navgroup['text'] = $group_key;

					$navigation["$group_displayorder"]["$group_key"]['group'] = $navgroup;
				}
			}
		}

		$xmlobj = null;
		unset($xml);
	}

	($hook = vBulletinHook::fetch_hook('admin_index_navigation')) ? eval($hook) : false;

	// sort groups by display order
	ksort($navigation);
	foreach ($navigation AS $group_keys)
	{
		foreach ($group_keys AS $group_key => $navgroup_holder)
		{
			// sort options by display order
			ksort($navgroup_holder['options']);

			foreach ($navgroup_holder['options'] AS $navoption_holder)
			{
				foreach ($navoption_holder AS $navoption)
				{
					construct_nav_option($navoption['text'], $navoption['link']);
				}
			}

			// have all the options, so do the group
			construct_nav_group($navgroup_holder['group']['text'], $navgroup_holder['group']['nav_file']);

			if ($navgroup_holder['group']['hr'] == 'true')
			{
				construct_nav_spacer();
			}
		}
	}

	print_nav_panel();

	unset($navigation);

	echo "</div>\n";
	// *************************************************

	define('NO_CP_COPYRIGHT', true);
	unset($DEVDEBUG);
	print_cp_footer();

}

// #############################################################################
// ################################ BUILD FRAMESET #############################
// #############################################################################

if ($_REQUEST['do'] == 'frames' OR empty($_REQUEST['do']))
{
	$vbulletin->input->clean_array_gpc('r', array(
		'loc' 		=> TYPE_NOHTML
	));

	$navframe = "<frame src=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=nav" . iif($vbulletin->GPC['nojs'], '&amp;nojs=1') . "\" name=\"nav\" scrolling=\"yes\" frameborder=\"0\" marginwidth=\"0\" marginheight=\"0\" border=\"no\" />\n";
	$headframe = "<frame src=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=head\" name=\"head\" scrolling=\"no\" noresize=\"noresize\" frameborder=\"0\" marginwidth=\"10\" marginheight=\"0\" border=\"no\" />\n";
	$mainframe = "<frame src=\"" . iif(!empty($vbulletin->GPC['loc']) AND !preg_match('#^[a-z]+:#i', $vbulletin->GPC['loc']), create_full_url($vbulletin->GPC['loc']), "index.php?" . $vbulletin->session->vars['sessionurl'] . "do=home") . "\" name=\"main\" scrolling=\"yes\" frameborder=\"0\" marginwidth=\"10\" marginheight=\"10\" border=\"no\" />\n";

	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo vB_Template_Runtime::fetchStyleVar('textdirection'); ?>" lang="<?php echo vB_Template_Runtime::fetchStyleVar('languagecode'); ?>">
	<head>
	<script type="text/javascript">
	<!--
	// get out of any containing frameset
	if (self.parent.frames.length != 0)
	{
		self.parent.location.replace(document.location.href);
	}
	// -->
	</script>
	<title><?php echo $vbulletin->options['bbtitle'] . ' ' . $vbphrase['admin_control_panel']; ?></title>
	</head>

	<?php

	if (vB_Template_Runtime::fetchStyleVar('textdirection') == 'ltr')
	{
	// left-to-right frameset
	?>
	<frameset cols="195,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
		<?php echo $navframe; ?>
		<frameset rows="20,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
			<?php echo $headframe; ?>
			<?php echo $mainframe; ?>
		</frameset>
	</frameset>
	<?php
	}
	else
	{
	// right-to-left frameset
	?>
	<frameset cols="*,195"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
		<frameset rows="20,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
			<?php echo $headframe; ?>
			<?php echo $mainframe; ?>
		</frameset>
		<?php echo $navframe; ?>
	</frameset>
	<?php
	}

	?>

	<noframes>
		<body>
			<p><?php echo $vbphrase['no_frames_support']; ?></p>
		</body>
	</noframes>
	</html>
	<?php
}

// ################################ MAIN FRAME #############################

if ($_REQUEST['do'] == 'home')
{

$vbulletin->input->clean_array_gpc('r', array('showallnews' => TYPE_BOOL));

print_cp_header($vbphrase['welcome_to_the_vbulletin_admin_control_panel']);

// Warn admin if admincpdir setting doesn't match the admincp directory
if (!empty($_SERVER['SCRIPT_NAME']))
{
	$admincppath = dirname($_SERVER['SCRIPT_NAME']);
	if (strpos($admincppath, '/') !== false)
	{
		$admincppath = strrchr($admincppath, '/');
	}
	$admincppath = trim($admincppath, '/');
	if ($admincppath != $vbulletin->config['Misc']['admincpdir'])
	{
		print_table_start();
		print_description_row(construct_phrase($vbphrase['admincpdir_mismatch'], htmlspecialchars_uni($admincppath), htmlspecialchars_uni($vbulletin->config['Misc']['admincpdir'])));
		print_table_footer(2, '', '', false);
	}
	unset($admincppath);
}

$news_rows = array();

// look to see if MySQL is running in strict mode
if (empty($vbulletin->config['Database']['force_sql_mode']))
{
	// check to see if MySQL is running strict mode and recommend disabling it
	$db->hide_errors();
	$strict_mode_check = $db->query_first("SHOW VARIABLES LIKE 'sql\\_mode'");
	if (strpos(strtolower($strict_mode_check['Value']), 'strict_') !== false)
	{
		ob_start();
		print_table_header($vbphrase['mysql_strict_mode_warning']);
		print_description_row('<div class="smallfont">' . $vbphrase['mysql_running_strict_mode'] . '</div>');
		$news_rows['sql_strict'] = ob_get_clean();
	}
	$db->show_errors();

}

// check if a PHP optimizer with known issues is installed
if (($err = verify_optimizer_environment()) !== true)
{
	ob_start();
	print_description_row($vbphrase['problematic_php_optimizer_found'], false, 2, 'thead');
	print_description_row('<div class="smallfont">' . $vbphrase["$err"] . '</div>');
	$news_rows['php_optimizer'] = ob_get_clean();
}

// look for incomplete admin messages that may have actually been independently completed
// and say they're done
$donemessages_result = $db->query_read("
	SELECT adminmessage.adminmessageid
	FROM " . TABLE_PREFIX . "adminmessage AS adminmessage
	INNER JOIN " . TABLE_PREFIX . "adminlog AS adminlog ON (adminlog.script = adminmessage.script AND adminlog.action = adminmessage.action)
	WHERE adminmessage.status = 'undone'
		AND adminmessage.script <> ''
		AND adminlog.dateline > adminmessage.dateline
	GROUP BY adminmessage.adminmessageid
");
while ($donemessage = $db->fetch_array($donemessages_result))
{
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "adminmessage
		SET status = 'done'
		WHERE adminmessageid = " . intval($donemessage['adminmessageid']) . "
	");
}

// let's look for any messages that we need to display to the admin
$adminmessages_result = $db->query_read("
	SELECT *
	FROM " . TABLE_PREFIX . "adminmessage
	WHERE status = 'undone'
	ORDER BY dateline
");
if ($db->num_rows($adminmessages_result))
{
	ob_start();
	while ($adminmessage = $db->fetch_array($adminmessages_result))
	{
		$buttons = '';
		if ($adminmessage['execurl'])
		{
			$buttons .= '<input type="submit" name="address[' . $adminmessage['adminmessageid'] .']" value="' . $vbphrase['address'] . '" class="button" />';
		}
		if ($adminmessage['dismissable'] OR !$adminmessage['execurl'])
		{
			$buttons .= ' <input type="submit" name="dismiss[' . $adminmessage['adminmessageid'] .']" value="' . $vbphrase['dismiss'] . '" class="button" />';
		}

		$args = @unserialize($adminmessage['args']);
		print_description_row("<div style=\"float: right\">$buttons</div><div>" . $vbphrase['admin_attention_required'] . "</div>", false, 2, 'thead');
		print_description_row(
			'<div class="smallfont">' . fetch_error($adminmessage['varname'], $args) . "</div>"
		);
	}
	$news_rows['admin_messages'] = ob_get_clean();
}

if (can_administer('canadminstyles'))
{
	// before the quick stats, display the number of templates that need updating
	require_once(DIR . '/includes/adminfunctions_template.php');

	$need_updates = fetch_changed_templates_count();
	if ($need_updates)
	{
		ob_start();
		print_description_row($vbphrase['out_of_date_custom_templates_found'], false, 2, 'thead');
		print_description_row(construct_phrase(
			'<div class="smallfont">' .  $vbphrase['currently_x_customized_templates_updated'] . '</div>',
			$need_updates,
			$vbulletin->session->vars['sessionurl']
		));
		$news_rows['new_version'] = ob_get_clean();
	}
}

echo '<div id="admin_news"' . (empty($news_rows) ? ' style="display: none;"' : '') . '>';
if (!empty($news_rows))
{
	print_form_header('index', 'handlemessage', false, true, 'news');

	print_table_header($vbphrase['news_header_string']);
	echo $news_rows['new_version'];
	echo $news_rows['php_optimizer'];
	echo $news_rows['sql_strict'];
	echo $news_rows['admin_messages'];

	print_table_footer();
}
else
{
	print_form_header('index', 'handlemessage', false, true, 'news');

	print_table_footer();
}
echo '</div>'; // end of <div id="admin_news">

// *******************************
// Admin Quick Stats -- Toggable via the CP
$starttime = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

$mysqlversion = $db->query_first("SELECT VERSION() AS version");

if ($vbulletin->options['adminquickstats'])
{
	if ($vbulletin->acpstats['lastupdate'] < (TIMENOW - 3600))
	{
		build_acpstats_datastore();
	}

	// An index exists on dateline for thread marking so we can run this on each page load.
	$newthreads = $db->query_first("
		SELECT COUNT(*) AS count
		FROM " . TABLE_PREFIX . "thread
		WHERE visible IN (0,1,2)
			AND sticky IN (0,1)
			AND open <> 10
			AND dateline >= $starttime
	");

	if ($vbulletin->acpstats['datasize'] == -1)
	{
		$vbulletin->acpstats['datasize'] = $vbphrase['n_a'];
	}
	if ($vbulletin->acpstats['indexsize'] == -1)
	{
		$vbulletin->acpstats['indexsize'] = $vbphrase['n_a'];
	}
}

$db->hide_errors();
if ($variables = $db->query_first("SHOW VARIABLES LIKE 'max_allowed_packet'"))
{
	$maxpacket = $variables['Value'];
}
else
{
	$maxpacket = $vbphrase['n_a'];
}
$db->show_errors();

if (preg_match('#(Apache)/([0-9\.]+)\s#siU', $_SERVER['SERVER_SOFTWARE'], $wsregs))
{
	$webserver = "$wsregs[1] v$wsregs[2]";
	if (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi')
	{
		$addsapi = true;
	}
}
else if (preg_match('#Microsoft-IIS/([0-9\.]+)#siU', $_SERVER['SERVER_SOFTWARE'], $wsregs))
{
	$webserver = "IIS v$wsregs[1]";
	$addsapi = true;
}
else if (preg_match('#Zeus/([0-9\.]+)#siU', $_SERVER['SERVER_SOFTWARE'], $wsregs))
{
	$webserver = "Zeus v$wsregs[1]";
	$addsapi = true;
}
else if (strtoupper($_SERVER['SERVER_SOFTWARE']) == 'APACHE')
{
	$webserver = 'Apache';
	if (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi')
	{
		$addsapi = true;
	}
}
else
{
	$webserver = SAPI_NAME;
}

if ($addsapi)
{
	$webserver .= ' (' . SAPI_NAME . ')';
}

$serverinfo = SAFEMODE ? "<br />$vbphrase[safe_mode]" : '';
$serverinfo .= (ini_get('file_uploads') == 0 OR strtolower(ini_get('file_uploads')) == 'off') ? "<br />$vbphrase[file_uploads_disabled]" : '';

$memorylimit = ini_get('memory_limit');

// ###### Users to Moderate
$waiting = $db->query_first("SELECT COUNT(*) AS users FROM " . TABLE_PREFIX . "user WHERE usergroupid = 4");

// ##### Attachments to Moderate
$attachcount = $db->query_first("
	SELECT COUNT(*) AS count
	FROM " . TABLE_PREFIX . "attachment AS attachment
	###INNER JOIN " . TABLE_PREFIX . "post AS post USING (postid)###
	WHERE attachment.state = 'moderation' AND contentid <> 0
");

// ##### Events to Moderate
$eventcount = $db->query_first("
	SELECT COUNT(*) AS count
	FROM " . TABLE_PREFIX . "event AS event
	INNER JOIN " . TABLE_PREFIX ."calendar AS calendar USING (calendarid)
	WHERE event.visible = 0
");

// ##### Posts to Moderate
$postcount = $db->query_first("
	SELECT COUNT(*) AS count
	FROM " . TABLE_PREFIX . "moderation AS moderation
	INNER JOIN " . TABLE_PREFIX . "post AS post ON (post.postid = moderation.primaryid)
	WHERE moderation.type = 'reply'
");

// ##### Threads to Moderate
$threadcount = $db->query_first("
	SELECT COUNT(*) AS count
	FROM " . TABLE_PREFIX . "moderation AS moderation
	INNER JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = moderation.primaryid)
	WHERE moderation.type = 'thread'
");

// ##### Messages to Moderate
$messagecount = $db->query_first("
	SELECT COUNT(*) AS count
	FROM " . TABLE_PREFIX . "moderation AS moderation
	INNER JOIN " . TABLE_PREFIX . "visitormessage AS visitormessage ON (visitormessage.vmid = moderation.primaryid)
	WHERE moderation.type = 'visitormessage'
");

$mailqueue = $vbulletin->db->query_first("
	SELECT COUNT(mailqueueid) AS queued FROM " . TABLE_PREFIX . "mailqueue
");

print_form_header('index', 'home');
if ($vbulletin->options['adminquickstats'])
{
	print_table_header($vbphrase['welcome_to_the_vbulletin_admin_control_panel'], 6);
	print_cells_row(array(
		$vbphrase['server_type'], PHP_OS . $serverinfo,
		$vbphrase['database_data_usage'], vb_number_format($vbulletin->acpstats['datasize'], 2, true),
		$vbphrase['users_awaiting_moderation'], vb_number_format($waiting['users']) . ' ' . construct_link_code($vbphrase['view'], "user.php?" . $vbulletin->session->vars['sessionurl'] . "do=moderate"),
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['web_server'], $webserver,
		$vbphrase['database_index_usage'], vb_number_format($vbulletin->acpstats['indexsize'], 2, true),
		$vbphrase['threads_awaiting_moderation'], vb_number_format($threadcount['count']) . ' ' . construct_link_code($vbphrase['view'], '../' . $vbulletin->config['Misc']['modcpdir'] . '/moderate.php?' . $vbulletin->session->vars['sessionurl'] . "do=posts"),
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		'PHP', PHP_VERSION,
		$vbphrase['attachment_usage'], vb_number_format($vbulletin->acpstats['attachsize'], 2, true),
		$vbphrase['posts_awaiting_moderation'], vb_number_format($postcount['count']) . ' ' . construct_link_code($vbphrase['view'],'../' . $vbulletin->config['Misc']['modcpdir'] . '/moderate.php?' . $vbulletin->session->vars['sessionurl'] . "do=posts#postlist"),
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['php_max_post_size'], ($postmaxsize = ini_get('post_max_size')) ? vb_number_format($postmaxsize, 2, true) : $vbphrase['n_a'],
		$vbphrase['custom_avatar_usage'], vb_number_format($vbulletin->acpstats['avatarsize'], 2, true),
		$vbphrase['attachments_awaiting_moderation'], vb_number_format($attachcount['count']) . ' ' . construct_link_code($vbphrase['view'], '../' . $vbulletin->config['Misc']['modcpdir'] . '/moderate.php?' . $vbulletin->session->vars['sessionurl'] . "do=attachments"),
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['php_max_upload_size'], ($postmaxuploadsize = ini_get('upload_max_filesize')) ? vb_number_format($postmaxuploadsize, 2, true) : $vbphrase['n_a'],
		$vbphrase['custom_profile_picture_usage'], vb_number_format($vbulletin->acpstats['profilepicsize'], 2, true),
		$vbphrase['events_awaiting_moderation'], vb_number_format($eventcount['count']) . ' ' . construct_link_code($vbphrase['view'], '../' . $vbulletin->config['Misc']['modcpdir'] . '/moderate.php?' . $vbulletin->session->vars['sessionurl'] . "do=events"),
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['php_memory_limit'], ($memorylimit AND $memorylimit != '-1') ? vb_number_format($memorylimit, 2, true) : $vbphrase['none'],
		$vbphrase['unique_registered_visitors_today'], vb_number_format($vbulletin->acpstats['userstoday']),
		$vbphrase['messages_awaiting_moderation'], vb_number_format($messagecount['count']) . ' ' . construct_link_code($vbphrase['view'], '../' . $vbulletin->config['Misc']['modcpdir'] . '/moderate.php?' . $vbulletin->session->vars['sessionurl'] . "do=messages"),
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['mysql_version'], $mysqlversion['version'],
		$vbphrase['new_users_today'], vb_number_format($vbulletin->acpstats['newusers']),
		$vbphrase['new_threads_today'], vb_number_format($newthreads['count']),
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['mysql_max_packet_size'], vb_number_format($maxpacket, 2, 1),
		$vbphrase['new_posts_today'], vb_number_format($vbulletin->acpstats['newposts']),
	$vbphrase['queued_emails'], vb_number_format($mailqueue['queued'])
	), 0, 0, -5, 'top', 1, 1);
}
else
{
	print_table_header($vbphrase['welcome_to_the_vbulletin_admin_control_panel'], 4);
	print_cells_row(array(
		$vbphrase['server_type'], PHP_OS . $serverinfo,
		$vbphrase['users_awaiting_moderation'], vb_number_format($waiting['users']) . ' ' . construct_link_code($vbphrase['view'], "user.php?" . $vbulletin->session->vars['sessionurl'] . "do=moderate")
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['web_server'], $webserver,
		$vbphrase['threads_awaiting_moderation'], vb_number_format($threadcount['count']) . ' ' . construct_link_code($vbphrase['view'], '../' . $vbulletin->config['Misc']['modcpdir'] . '/moderate.php?' . $vbulletin->session->vars['sessionurl'] . "do=posts")
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		'PHP', PHP_VERSION,
		$vbphrase['posts_awaiting_moderation'], vb_number_format($postcount['count']) . ' ' . construct_link_code($vbphrase['view'],'../' . $vbulletin->config['Misc']['modcpdir'] . '/moderate.php?' . $vbulletin->session->vars['sessionurl'] . "do=posts#postlist")
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['php_max_post_size'], ($postmaxsize = ini_get('post_max_size')) ? vb_number_format($postmaxsize, 2, true) : $vbphrase['n_a'],
		$vbphrase['attachments_awaiting_moderation'], vb_number_format($attachcount['count']) . ' ' . construct_link_code($vbphrase['view'], '../' . $vbulletin->config['Misc']['modcpdir'] . '/moderate.php?' . $vbulletin->session->vars['sessionurl'] . "do=attachments")
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['php_max_upload_size'], ($postmaxuploadsize = ini_get('upload_max_filesize')) ? vb_number_format($postmaxuploadsize, 2, true) : $vbphrase['n_a'],
		$vbphrase['events_awaiting_moderation'], vb_number_format($eventcount['count']) . ' ' . construct_link_code($vbphrase['view'], '../' . $vbulletin->config['Misc']['modcpdir'] . '/moderate.php?' . $vbulletin->session->vars['sessionurl'] . "do=events")
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['php_memory_limit'], ($memorylimit AND $memorylimit != '-1') ? vb_number_format($memorylimit, 2, true) : $vbphrase['none'],
	$vbphrase['queued_emails'], vb_number_format($mailqueue['queued'])
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['mysql_version'], $mysqlversion['version'],
		'&nbsp;', '&nbsp;'
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array($vbphrase['mysql_max_packet_size'], vb_number_format($maxpacket, 2, 1),
		'&nbsp;', '&nbsp;'
	), 0, 0, -5, 'top', 1, 1);
}

print_table_footer();
($hook = vBulletinHook::fetch_hook('admin_index_main1')) ? eval($hook) : false;

// *************************************
// Administrator Notes

print_form_header('index', 'notes');
print_table_header($vbphrase['administrator_notes'], 1);
print_description_row("<textarea name=\"notes\" style=\"width: 90%\" rows=\"9\" tabindex=\"1\">" . $vbulletin->userinfo['notes'] . "</textarea>", false, 1, '', 'center');
print_submit_row($vbphrase['save'], 0, 1);

($hook = vBulletinHook::fetch_hook('admin_index_main2')) ? eval($hook) : false;

// *************************************
// QUICK ADMIN LINKS

print_table_start();
print_table_header($vbphrase['quick_administrator_links']);

$datecut = TIMENOW - $vbulletin->options['cookietimeout'];
$guestsarry = $db->query_first("SELECT COUNT(host) AS sessions FROM " . TABLE_PREFIX . "session WHERE userid = 0 AND lastactivity > $datecut");
$membersarry = $db->query_read("SELECT DISTINCT userid FROM " . TABLE_PREFIX . "session WHERE userid <> 0 AND lastactivity > $datecut");
$guests = intval($guestsarry['sessions']);
$members = intval($db->num_rows($membersarry));

// ### MAX LOGGEDIN USERS ################################
if (intval($vbulletin->maxloggedin['maxonline']) <= ($guests + $members))
{
	$vbulletin->maxloggedin['maxonline'] = $guests + $members;
	$vbulletin->maxloggedin['maxonlinedate'] = TIMENOW;
	build_datastore('maxloggedin', serialize($vbulletin->maxloggedin), 1);
}

$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
$loadavg = array();

if (!$is_windows AND function_exists('exec') AND $stats = @exec('uptime 2>&1') AND trim($stats) != '' AND preg_match('#: ([\d.,]+),?\s+([\d.,]+),?\s+([\d.,]+)$#', $stats, $regs))
{
	$loadavg[0] = vb_number_format($regs[1], 2);
	$loadavg[1] = vb_number_format($regs[2], 2);
	$loadavg[2] = vb_number_format($regs[3], 2);
}
else if (!$is_windows AND @file_exists('/proc/loadavg') AND $stats = @file_get_contents('/proc/loadavg') AND trim($stats) != '')
{
	$loadavg = explode(' ', $stats);
	$loadavg[0] = vb_number_format($loadavg[0], 2);
	$loadavg[1] = vb_number_format($loadavg[1], 2);
	$loadavg[2] = vb_number_format($loadavg[2], 2);
}

if (!empty($loadavg))
{
	print_label_row($vbphrase['server_load_averages'], "$loadavg[0]&nbsp;&nbsp;$loadavg[1]&nbsp;&nbsp;$loadavg[2] | " . construct_phrase($vbphrase['users_online_x_members_y_guests'], vb_number_format($guests + $members), vb_number_format($members), vb_number_format($guests)), '', 'top', NULL, false);
}
else
{
	print_label_row($vbphrase['users_online'], construct_phrase($vbphrase['x_y_members_z_guests'], vb_number_format($guests + $members), vb_number_format($members), vb_number_format($guests)), '', 'top', NULL, false);
}

if (can_administer('canadminusers'))
{
	print_label_row($vbphrase['quick_user_finder'], '
		<form action="user.php?do=find" method="post" style="display:inline">
		<input type="hidden" name="s" value="' . $vbulletin->session->vars['sessionhash'] . '" />
		<input type="hidden" name="adminhash" value="' . ADMINHASH . '" />
		<input type="hidden" name="do" value="find" />
		<input type="text" class="bginput" name="user[username]" size="30" tabindex="1" />
		<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
		<input type="submit" class="button" value="' . $vbphrase['exact_match'] . '" tabindex="1" name="user[exact]" />
		</form>
		', '', 'top', NULL, false
	);
}

print_label_row($vbphrase['quick_phrase_finder'], '
	<form action="phrase.php?do=dosearch" method="post" style="display:inline">
	<input type="text" class="bginput" name="searchstring" size="30" tabindex="1" />
	<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
	<input type="hidden" name="do" value="dosearch" />
	<input type="hidden" name="languageid" value="-10" />
	<input type="hidden" name="searchwhere" value="10" />
	<input type="hidden" name="adminhash" value="' . ADMINHASH . '" />
	</form>
	', '', 'top', NULL, false
);

print_label_row($vbphrase['php_function_lookup'], '
	<form action="http://www.ph' . 'p.net/manual-lookup.ph' . 'p" method="get" style="display:inline">
	<input type="text" class="bginput" name="function" size="30" tabindex="1" />
	<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
	</form>
	', '', 'top', NULL, false
);
print_label_row($vbphrase['mysql_language_lookup'], '
	<form action="http://www.mysql.com/search/" method="get" style="display:inline">
	<input type="hidden" name="doc" value="1" />
	<input type="hidden" name="m" value="o" />
	<input type="text" class="bginput" name="q" size="30" tabindex="1" />
	<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
	</form>
	', '', 'top', NULL, false
);
print_label_row($vbphrase['useful_links'], '
	<form style="display:inline">
	<select onchange="if (this.options[this.selectedIndex].value != \'\') { window.open(this.options[this.selectedIndex].value); } return false;" tabindex="1" class="bginput">
		<option value="">-- ' . $vbphrase['useful_links'] . ' --</option>' . construct_select_options(array(
			'vBulletin' => array(
				'http://www.vbulletin.com/' => $vbphrase['home_page'] . ' (vBulletin.com)',
				'http://members.vbulletin.com/' => $vbphrase['members_area'],
				'http://www.vbulletin.com/forum/' => $vbphrase['community_forums'],
				'http://www.vbulletin.com/docs/html/' => $vbphrase['reference_manual']
			),
			'PHP' => array(
				'http://www.ph' . 'p.net/' => $vbphrase['home_page'] . ' (PHP.net)',
				'http://www.ph' . 'p.net/manual/' => $vbphrase['reference_manual'],
				'http://www.ph' . 'p.net/downloads.ph' . 'p' => $vbphrase['download_latest_version']
			),
			'MySQL' => array(
				'http://www.mysql.com/' => $vbphrase['home_page'] . ' (MySQL.com)',
				'http://www.mysql.com/documentation/' => $vbphrase['reference_manual'],
				'http://www.mysql.com/downloads/' => $vbphrase['download_latest_version'],
			),
			'Apache' => array(
				'http://httpd.apache.org/' => $vbphrase['home_page'] . ' (Apache.org)',
				'http://httpd.apache.org/docs/' => $vbphrase['reference_manual'],
				'http://httpd.apache.org/download.cgi' => $vbphrase['download_latest_version'],
			),
	)) . '</select>
	</form>
	', '', 'top', NULL, false
);
print_table_footer(2, '', '', false);

($hook = vBulletinHook::fetch_hook('admin_index_main3')) ? eval($hook) : false;

// *************************************
// vBULLETIN CREDITS
require_once(DIR . '/includes/vbulletin_credits.php');

?>

<p class="smallfont" align="center">
<!--<?php echo construct_phrase($vbphrase['vbulletin_copyright'], $vbulletin->options['templateversion'], date('Y')); ?><br />-->
<script type="text/javascript">
<!--
if (typeof(vb_version) != "undefined")
{
	var this_vb_version = "<?php echo ADMIN_VERSION_VBULLETIN; ?>";
	if (isNewerVersion(this_vb_version, vb_version))
	{
		document.writeln('<a href="http://www.vbulletin.com/forum/showthread.ph' + 'p?p=' + vb_announcementid + '" target="_blank">' + construct_phrase(latest_string, vb_version) + '</a><br />' + construct_phrase(current_string, this_vb_version.bold()));
	}
	else
	{
		document.write(construct_phrase('<?php echo $vbphrase['your_version_of_vbulletin_is_up_to_date']; ?>', this_vb_version));
	}
}
// -->
</script>
</p>

<?php

unset($DEVDEBUG);

?>
<script type="text/javascript">
<!--
var current_version = "<?php echo ADMIN_VERSION_VBULLETIN; ?>";
var latest_string = "<?php echo $vbphrase['latest_version_available_x']; ?>";
var current_string = "<?php echo $vbphrase['you_are_running_vbulletin_version_x']; ?>";
var download_string = "<?php echo $vbphrase['download_vbulletin_x_from_members_area']; ?>";
var newer_version_string = "<?php echo $vbphrase['there_is_a_newer_vbulletin_version']; ?>";
var dismissed_news = "<?php echo ($vbulletin->GPC['showallnews'] ? '' : $vbulletin->userinfo['dismissednews']); ?>";
var dismiss_string = "<?php echo $vbphrase['dismiss']; ?>";
var vbulletin_news_string = "<?php echo $vbphrase['vbulletin_news_x']; ?>";
var news_header_string = "<?php echo $vbphrase['news_header_string']; ?>";
var show_all_news_link = "index.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=home&showallnews=1";
var show_all_news_string = "<?php echo $vbphrase['show_all_news']; ?>";
var view_string = "<?php echo $vbphrase['view']; ?>...";
var stylevar_left = "<?php echo vB_Template_Runtime::fetchStyleVar('left'); ?>";
var stylevar_right = "<?php echo vB_Template_Runtime::fetchStyleVar('right'); ?>";
var done_table = <?php echo (empty($news_rows) ? 'false' : 'true'); ?>;
var local_extension = '.php';
//-->
</script>
<script type="text/javascript" src="<?php echo $versionhost; ?>/versioncheck.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo $versionhost; ?>/version.js?v=<?php echo SIMPLE_VERSION; ?>&amp;id=VBF02D260D&amp;pid=vbulletinsuite"></script>
<script type="text/javascript" src="../clientscript/vbulletin_cphome_scripts.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
<?php

print_cp_footer();

}

// ################################ SHOW PHP INFO #############################

if ($_REQUEST['do'] == 'phpinfo')
{
	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}
	else
	{
		phpinfo();
		exit;
	}
}

// ################################ HANDLE ADMIN MESSAGES #############################
if ($_POST['do'] == 'handlemessage')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'address' => TYPE_ARRAY_KEYS_INT,
		'dismiss' => TYPE_ARRAY_KEYS_INT,
		'acpnews' => TYPE_ARRAY_KEYS_INT
	));

	print_cp_header($vbphrase['welcome_to_the_vbulletin_admin_control_panel']);

	if ($vbulletin->GPC['address'])
	{
		// chosen to address the issue -- redirect to the appropriate page
		$adminmessageid = intval($vbulletin->GPC['address'][0]);
		$adminmessage = $db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "adminmessage
			WHERE adminmessageid = $adminmessageid
		");

		if (!empty($adminmessage))
		{
			// set the issue as addressed
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "adminmessage
				SET status = 'done', statususerid = " . $vbulletin->userinfo['userid'] . "
				WHERE adminmessageid = $adminmessageid
			");
		}

		if (!empty($adminmessage) AND !empty($adminmessage['execurl']))
		{
			if ($adminmessage['method'] == 'get')
			{
				// get redirect -- can use the url basically as is
				if (!strpos($adminmessage['execurl'], '?'))
				{
					$adminmessage['execurl'] .= '?';
				}
				print_cp_redirect($adminmessage['execurl'] . $vbulletin->session->vars['sessionurl_js']);
			}
			else
			{
				// post redirect -- need to seperate into <file>?<querystring> first
				if (preg_match('#^(.+)\?(.*)$#siU', $adminmessage['execurl'], $match))
				{
					$script = $match[1];
					$arguments = explode('&', $match[2]);
				}
				else
				{
					$script = $adminmessage['execurl'];
					$arguments = array();
				}

				echo '
					<form action="' . htmlspecialchars($script) . '" method="post" id="postform">
				';

				foreach ($arguments AS $argument)
				{
					// now take each element in the query string into <name>=<value>
					// and stuff it into hidden form elements
					if (preg_match('#^(.*)=(.*)$#siU', $argument, $match))
					{
						$name = $match[1];
						$value = $match[2];
					}
					else
					{
						$name = $argument;
						$value = '';
					}
					echo '
						<input type="hidden" name="' . htmlspecialchars(urldecode($name)) . '" value="' . htmlspecialchars(urldecode($value)) . '" />
					';
				}

				// and submit the form automatically
				echo '
					</form>
					<script type="text/javascript">
					<!--
					fetch_object(\'postform\').submit();
					// -->
					</script>
				';
			}

			print_cp_footer();
		}
	}
	else if ($vbulletin->GPC['dismiss'])
	{
		// choosing to forget about the issue
		$adminmessageid = intval($vbulletin->GPC['dismiss'][0]);

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "adminmessage
			SET status = 'dismissed', statususerid = " . $vbulletin->userinfo['userid'] . "
			WHERE adminmessageid = $adminmessageid
		");
	}
	else if ($vbulletin->GPC['acpnews'])
	{
		$items = preg_split('#\s*,\s*#s', $vbulletin->userinfo['dismissednews'], -1, PREG_SPLIT_NO_EMPTY);
		$items[] = intval($vbulletin->GPC['acpnews'][0]);
		$vbulletin->userinfo['dismissednews'] = implode(',', array_unique($items));

		$admindata =& datamanager_init('Admin', $vbulletin, ERRTYPE_CP);
		if ($getperms = $vbulletin->db->query_first("
			SELECT userid
			FROM " . TABLE_PREFIX . "administrator
			WHERE userid = " . $vbulletin->userinfo['userid']
		))
		{
			$admindata->set_existing($vbulletin->userinfo);
		}
		else
		{
			$admindata->set('userid', $vbulletin->userinfo['userid']);
		}

		$admindata->set('dismissednews', $vbulletin->userinfo['dismissednews']);
		$admindata->save();
	}
	print_cp_redirect('index.php?do=home' . $vbulletin->session->vars['sessionurl_js']);
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63817 $
|| ####################################################################
\*======================================================================*/
?>
