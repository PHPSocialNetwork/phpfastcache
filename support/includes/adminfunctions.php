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

error_reporting(E_ALL & ~E_NOTICE);

if (!defined('ADMINHASH'))
{
	define('ADMINHASH', md5(COOKIE_SALT . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']));
}
// #############################################################################

/**
* Displays the login form for the various control panel areas
*
* The actual form displayed is dependent upon the VB_AREA constant
*/
function print_cp_login($mismatch = false)
{
	global $vbulletin, $vbphrase;

	if ($vbulletin->GPC['ajax'])
	{
		print_stop_message('you_have_been_logged_out_of_the_cp');
	}

	$focusfield = iif($vbulletin->userinfo['userid'] == 0, 'username', 'password');

	$vbulletin->input->clean_array_gpc('r', array(
		'vb_login_username' => TYPE_NOHTML
	));

	$printusername = iif(!empty($vbulletin->GPC['vb_login_username']), $vbulletin->GPC['vb_login_username'], $vbulletin->userinfo['username']);
	$vbulletin->userinfo['badlocation'] = 1;

	switch(VB_AREA)
	{
		case 'AdminCP':
			$pagetitle = $vbphrase['admin_control_panel'];
			$getcssoptions = fetch_cpcss_options();
			$cssoptions = array();
			foreach ($getcssoptions AS $folder => $foldername)
			{
				$key = iif($folder == $vbulletin->options['cpstylefolder'], '', $folder);
				$cssoptions["$key"] = $foldername;
			}
			$showoptions = true;
			$logintype = 'cplogin';
		break;

		case 'ModCP':
			$pagetitle = $vbphrase['moderator_control_panel'];
			$showoptions = false;
			$logintype = 'modcplogin';
		break;

		default:
			($hook = vBulletinHook::fetch_hook('admin_login_area_switch')) ? eval($hook) : false;
	}

	define('NO_PAGE_TITLE', true);
	print_cp_header($vbphrase['log_in'], "document.forms.loginform.vb_login_$focusfield.focus()");

	require_once(DIR . '/includes/functions_misc.php');
	$postvars = construct_post_vars_html();

	$forumhome_url = fetch_seo_url('forumhome|bburl', array());
	?>
	<script type="text/javascript" src="../clientscript/vbulletin_md5.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
	<script type="text/javascript">
	<!--
	function js_show_options(objectid, clickedelm)
	{
		fetch_object(objectid).style.display = "";
		clickedelm.disabled = true;
	}
	function js_fetch_url_append(origbit,addbit)
	{
		if (origbit.search(/\?/) != -1)
		{
			return origbit + '&' + addbit;
		}
		else
		{
			return origbit + '?' + addbit;
		}
	}
	function js_do_options(formobj)
	{
		if (typeof(formobj.nojs) != "undefined" && formobj.nojs.checked == true)
		{
			formobj.url.value = js_fetch_url_append(formobj.url.value, 'nojs=1');
		}
		return true;
	}
	//-->
	</script>
	<form action="../login.php?do=login" method="post" name="loginform" onsubmit="md5hash(vb_login_password, vb_login_md5password, vb_login_md5password_utf); js_do_options(this)">
	<input type="hidden" name="url" value="<?php echo $vbulletin->scriptpath; ?>" />
	<input type="hidden" name="s" value="<?php echo $vbulletin->session->vars['dbsessionhash']; ?>" />
	<input type="hidden" name="securitytoken" value="<?php echo $vbulletin->userinfo['securitytoken']; ?>" />
	<input type="hidden" name="logintype" value="<?php echo $logintype; ?>" />
	<input type="hidden" name="do" value="login" />
	<input type="hidden" name="vb_login_md5password" value="" />
	<input type="hidden" name="vb_login_md5password_utf" value="" />
	<?php echo $postvars ?>
	<p>&nbsp;</p><p>&nbsp;</p>
	<table class="tborder" cellpadding="0" cellspacing="0" border="0" width="450" align="center"><tr><td>

		<!-- header -->
		<div class="tcat" style="padding:4px; text-align:center"><b><?php echo $vbphrase['log_in']; ?></b></div>
		<!-- /header -->

		<!-- logo and version -->
		<table cellpadding="4" cellspacing="0" border="0" width="100%" class="navbody">
		<tr valign="bottom">
			<td><img src="../cpstyles/<?php echo $vbulletin->options['cpstylefolder']; ?>/cp_logo.gif" alt="" title="<?php echo $vbphrase['vbulletin_copyright']; ?>" border="0" /></td>
			<td>
				<b><a href="<?php echo $forumhome_url ?>"><?php echo $vbulletin->options['bbtitle']; ?></a></b><br />
				<?php echo "vBulletin " . $vbulletin->options['templateversion'] . " $pagetitle"; ?><br />
				&nbsp;
			</td>
		</tr>
		<?php

		if ($mismatch)
		{
			?>
			<tr>
				<td colspan="2" class="navbody"><b><?php echo $vbphrase['to_continue_this_action']; ?></b></td>
			</tr>
			<?php
		}

		?>
		</table>
		<!-- /logo and version -->

		<table cellpadding="4" cellspacing="0" border="0" width="100%" class="logincontrols">
		<col width="50%" style="text-align:<?php echo vB_Template_Runtime::fetchStyleVar('right'); ?>; white-space:nowrap"></col>
		<col></col>
		<col width="50%"></col>

		<!-- login fields -->
		<tbody>
		<tr>
			<td><?php echo $vbphrase['username']; ?></td>
			<td><input type="text" style="padding-<?php echo vB_Template_Runtime::fetchStyleVar('left'); ?>:5px; font-weight:bold; width:250px" name="vb_login_username" value="<?php echo $printusername; ?>" accesskey="u" tabindex="1" id="vb_login_username" /></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><?php echo $vbphrase['password']; ?></td>
			<td><input type="password" style="padding-<?php echo vB_Template_Runtime::fetchStyleVar('left'); ?>:5px; font-weight:bold; width:250px" name="vb_login_password" accesskey="p" tabindex="2" id="vb_login_password" /></td>
			<td>&nbsp;</td>
		</tr>
		<tr style="display: none" id="cap_lock_alert">
			<td>&nbsp;</td>
			<td class="tborder"><?php echo $vbphrase['caps_lock_is_on']; ?></td>
			<td>&nbsp;</td>
		</tr>
		</tbody>
		<!-- /login fields -->

		<?php if ($showoptions) { ?>
		<!-- admin options -->
		<tbody id="loginoptions" style="display:none">
		<tr>
			<td><?php echo $vbphrase['style']; ?></td>
			<td><select name="cssprefs" class="login" style="padding-<?php echo vB_Template_Runtime::fetchStyleVar('left'); ?>:5px; font-weight:normal; width:250px" tabindex="5"><?php echo construct_select_options($cssoptions, $csschoice); ?></select></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><?php echo $vbphrase['options']; ?></td>
			<td>
				<label><input type="checkbox" name="nojs" value="1" tabindex="6" /> <?php echo $vbphrase['save_open_groups_automatically']; ?></label>
			</td>
			<td class="login">&nbsp;</td>
		</tr>
		</tbody>
		<!-- END admin options -->
		<?php } ?>

		<!-- submit row -->
		<tbody>
		<tr>
			<td colspan="3" align="center">
				<input type="submit" class="button" value="  <?php echo $vbphrase['log_in']; ?>  " accesskey="s" tabindex="3" />
				<?php if ($showoptions) { ?><input type="button" class="button" value=" <?php echo $vbphrase['options']; ?> " accesskey="o" onclick="js_show_options('loginoptions', this)" tabindex="4" /><?php } ?>
			</td>
		</tr>
		</tbody>
		<!-- /submit row -->
		</table>

	</td></tr></table>
	</form>
	<script type="text/javascript">
	<!--
	function caps_check(e)
	{
		var detected_on = detect_caps_lock(e);
		var alert_box = fetch_object('cap_lock_alert');

		if (alert_box.style.display == '')
		{
			// box showing already, hide if caps lock turns off
			if (!detected_on)
			{
				alert_box.style.display = 'none';
			}
		}
		else
		{
			if (detected_on)
			{
				alert_box.style.display = '';
			}
		}
	}
	fetch_object('vb_login_password').onkeypress = caps_check;
	//-->
	</script>
	<?php

	define('NO_CP_COPYRIGHT', true);
	unset($GLOBALS['DEVDEBUG']);
	print_cp_footer();
}

// #############################################################################
/**
* Starts Gzip encoding and prints out the main control panel page start / header
*
* @param	string	The page title
* @param	string	Javascript functions to be run on page start - for example "alert('moo'); alert('baa');"
* @param	string	Code to be inserted into the <head> of the page
* @param	integer	Width in pixels of page margins (default = 0)
* @param	string	HTML attributes for <body> tag - for example 'bgcolor="red" text="orange"'
*/
function print_cp_header($title = '', $onload = '', $headinsert = '', $marginwidth = 0, $bodyattributes = '')
{
	global $vbulletin, $helpcache, $vbphrase;

	// start GZ encoding output
	if ($vbulletin->options['gzipoutput'] AND !$vbulletin->nozip AND !headers_sent() AND function_exists('ob_start') AND function_exists('crc32') AND function_exists('gzcompress'))
	{
		// This will destroy all previous output buffers that could have been stacked up here.
		while (ob_get_level())
		{
			@ob_end_clean();
		}
		ob_start();
	}

	// get the appropriate <title> for the page
	switch(VB_AREA)
	{
		case 'AdminCP': $titlestring = iif($title, "$title - ") . $vbulletin->options['bbtitle'] . " - vBulletin $vbphrase[admin_control_panel]"; break;
		case 'ModCP': $titlestring = iif($title, "$title - ") . $vbulletin->options['bbtitle'] . " - vBulletin $vbphrase[moderator_control_panel]"; break;
		case 'Upgrade': $titlestring = iif($title, "vBulletin $title - ") . $vbulletin->options['bbtitle']; break;
		case 'Install': $titlestring = iif($title, "vBulletin $title - ") . $vbulletin->options['bbtitle']; break;
		default: $titlestring = iif($title, "$title - ") . $vbulletin->options['bbtitle'];
	}

	// if there is an onload action for <body>, set it up
	$onload = iif($onload != '', " $onload");

	// set up some options for nav-panel and head frames
	if (defined('IS_NAV_PANEL'))
	{
		$htmlattributes = ' class="navbody"';
		$bodyattributes .= ' class="navbody"';
		$headinsert .= '<base target="main" />';
	}
	else
	{
		$htmlattributes = '';
	}

	// print out the page header
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\r\n";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" dir=\"" . vB_Template_Runtime::fetchStyleVar('textdirection') . "\" lang=\"" . vB_Template_Runtime::fetchStyleVar('languagecode') . "\"$htmlattributes>\r\n";
	echo "<head>
	<title>$titlestring</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . vB_Template_Runtime::fetchStyleVar('charset') . "\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"../cpstyles/global.css?v={$vbulletin->options[simpleversion]}\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/controlpanel.css?v={$vbulletin->options[simpleversion]}\" />" . iif($headinsert != '', "
	$headinsert") . "
	<style type=\"text/css\">
		.page { background-color:white; color:black; }
		.time { color:silver; }
		/* Start generic feature management styles */

		.feature_management_header {
			font-size:16px;
		}

		/* End generic feature management styles */


		/* Start Styles for Category Manager */

		#category_title_controls {
			padding-" . vB_Template_Runtime::fetchStyleVar('left') . ": 10px;
			font-weight:bold;
			font-size:14px;
		}

		.picker_overlay {
			/*
				background-color:black;
				color:white;
			*/
			background-color:white;
			color:black;
			font-size:14px;
			padding:3px;
			border:1px solid black;
		}

		.selected_marker {
			margin-" . vB_Template_Runtime::fetchStyleVar('right') . ":4px;
			margin-top:4px;
			float:" . vB_Template_Runtime::fetchStyleVar('left') . ";
		}

		.section_name {
			font-size:14px;
			font-weight:bold;
			padding:0.2em 1em;
			margin: 0.5em 0.2em;
			/*
			color:#a2de97;
			background-color:black;
			*/
			background-color:white;
		}

		.tcat .picker_overlay a, .picker_overlay a, a.section_switch_link {
			/*
			color:#a2de97;
			*/
			color:blue;
		}

		.tcat .picker_overlay a:hover, .picker_overlay a:hover, a.section_switch_link:hover {
			color:red;
		}
		/* End Styles for Category Manager */
	</style>
	<script type=\"text/javascript\">
	<!--
	var SESSIONHASH = \"" . $vbulletin->session->vars['sessionhash'] . "\";
	var ADMINHASH = \"" . ADMINHASH . "\";
	var SECURITYTOKEN = \"" . $vbulletin->userinfo['securitytoken'] . "\";
	var IMGDIR_MISC = \"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "\";
	var CLEARGIFURL = \"./clear.gif\";
	var AJAXBASEURL = \"" . VB_URL_BASE_PATH . "../\";
	var BBURL = \"{$vbulletin->options['bburl']}\";
	var PATHS = {
		forum : \"{$vbulletin->options['vbforum_url']}\",
		cms   : \"{$vbulletin->options['vbcms_url']}\",
		blog  : \"{$vbulletin->options['vbblog_url']}\"
	};
	function set_cp_title()
	{
		if (typeof(parent.document) != 'undefined' && typeof(parent.document) != 'unknown' && typeof(parent.document.title) == 'string')
		{
			parent.document.title = (document.title != '' ? document.title : 'vBulletin');
		}
	}
	//-->
	</script>
	<script type=\"text/javascript\" src=\"../clientscript/yui/yuiloader-dom-event/yuiloader-dom-event.js\"></script>
	<script type=\"text/javascript\" src=\"../clientscript/yui/connection/connection-min.js\"></script>
	<script type=\"text/javascript\" src=\"../clientscript/vbulletin-core.js\"></script>
	<script type=\"text/javascript\" src=\"../clientscript/vbulletin_ajax_suggest.js\"></script>\n\r";
	echo "</head>\r\n";
	echo "<body style=\"margin:{$marginwidth}px\" onload=\"set_cp_title();$onload\"$bodyattributes>\r\n";
	echo iif($title != '' AND !defined('IS_NAV_PANEL') AND !defined('NO_PAGE_TITLE'), "<div class=\"pagetitle\">$title</div>\r\n<div style=\"margin:10px\">\r\n");
	echo "<!-- END CONTROL PANEL HEADER -->\r\n\r\n";

	// create the help cache
	if (VB_AREA == 'AdminCP' OR VB_AREA == 'ModCP')
	{
		$helpcache = array();
		$helptopics = $vbulletin->db->query_read("SELECT script, action, optionname FROM " . TABLE_PREFIX . "adminhelp");
		while ($helptopic = $vbulletin->db->fetch_array($helptopics))
		{
			$multactions = explode(',', $helptopic['action']);
			foreach ($multactions AS $act)
			{
				$act = trim($act);
				$helpcache["$helptopic[script]"]["$act"]["$helptopic[optionname]"] = 1;
			}
		}
	}
	else
	{
		$helpcache = array();
	}

	define('DONE_CPHEADER', true);
}

// #############################################################################
/**
* Prints the page footer, finishes Gzip encoding and terminates execution
*/
function print_cp_footer()
{
	global $vbulletin, $level, $vbphrase;

	echo "\r\n\r\n<!-- START CONTROL PANEL FOOTER -->\r\n";

	if ($vbulletin->debug)
	{
		if (defined('CVS_REVISION'))
		{
			$re = '#^\$' . 'RCS' . 'file: (.*\.php),v ' . '\$ - \$' . 'Revision: ([0-9\.]+) \$$#siU';
			$cvsversion = preg_replace($re, '\1, CVS v\2', CVS_REVISION);
		}
		if ($size = sizeof($GLOBALS['DEVDEBUG']))
		{
			$displayarray = array();
			$displayarray[] = "<select id=\"moo\"><option selected=\"selected\">DEBUG MESSAGES ($size)</option>\n" . construct_select_options($GLOBALS['DEVDEBUG'],-1,1) . "\t</select>";
			if (defined('CVS_REVISION'))
			{
				$displayarray[] = "<p style=\"font: bold 11px tahoma;\">$cvsversion</p>";
			}
			$displayarray[] = "<p style=\"font: bold 11px tahoma;\">SQL Queries (" . $vbulletin->db->querycount . ")</p>";

			$buttons = "<input type=\"button\" class=\"button\" value=\"Explain\" onclick=\"window.location = '" . $vbulletin->scriptpath . iif(strpos($vbulletin->scriptpath, '?') > 0, '&amp;', '?') . 'explain=1' . "';\" />" . "\n" . "<input type=\"button\" class=\"button\" value=\"Reload\" onclick=\"window.location = window.location;\" />";

			print_form_header('../docs/phrasedev', 'dofindphrase', 0, 1, 'debug', '90%', '_phrasefind');

			$displayarray[] =& $buttons;

			print_cells_row($displayarray, 0, 'thead');
			print_table_footer();
			echo '<p align="center" class="smallfont">' . date('r T') . '</p>';
		}
		else
		{
			echo "<p align=\"center\" class=\"smallfont\">SQL Queries (" . $vbulletin->db->querycount . ") | " . (!empty($cvsversion) ? "$cvsversion | " : '') . "<a href=\"" . $vbulletin->scriptpath . iif(strpos($vbulletin->scriptpath, '?') > 0, '&amp;', '?') . "explain=1\">Explain</a></p>";
			if (function_exists('memory_get_usage'))
			{
				echo "<p align=\"center\" class=\"smallfont\">Memory Usage: " . vb_number_format(round(memory_get_usage() / 1024, 2)) . " KiB</p>";
			}
		}

		$_REQUEST['do'] = htmlspecialchars_uni($_REQUEST['do']);

		echo "<script type=\"text/javascript\">window.status = \"" . construct_phrase($vbphrase['logged_in_user_x_executed_y_queries'], $vbulletin->userinfo['username'], $vbulletin->db->querycount) . " \$_REQUEST[do] = '$_REQUEST[do]'\";</script>";
	}

	if (!defined('NO_CP_COPYRIGHT'))
	{
		$output_version = defined('ADMIN_VERSION_VBULLETIN') ? ADMIN_VERSION_VBULLETIN : $vbulletin->options['templateversion'];
		echo '<p align="center"><a href="http://www.vbulletin.com/" target="_blank" class="copyright">' .
			construct_phrase($vbphrase['vbulletin_copyright_orig'], $output_version, date('Y')) .
			'</a></p>';
	}
	if (!defined('IS_NAV_PANEL') AND !defined('NO_PAGE_TITLE') AND VB_AREA != 'Upgrade' AND VB_AREA != 'Install')
	{
		echo "\n</div>";
	}
	echo "\n</body>\n</html>";

	($hook = vBulletinHook::fetch_hook('admin_complete')) ? eval($hook) : false;

	if ($vbulletin->options['gzipoutput'] AND function_exists("ob_start") AND function_exists("crc32") AND function_exists("gzcompress") AND !$vbulletin->nozip)
	{
		$text = ob_get_contents();
		while (ob_get_level())
		{
			@ob_end_clean();
		}

		if (!headers_sent() AND SAPI_NAME != 'apache2filter')
		{
			$newtext = fetch_gzipped_text($text, $vbulletin->options['gziplevel']);
		}
		else
		{
			$newtext = $text;
		}

		@header('Content-Length: ' . strlen($newtext));
		echo $newtext;
	}
	flush();

	//make sure that shutdown functions get called on exit.
	$vbulletin->shutdown->shutdown();
	if (defined('NOSHUTDOWNFUNC'))
	{
		exec_shut_down();
	}

	// terminate script execution now - DO NOT REMOVE THIS!
	exit;
}

// #############################################################################
/**
* Returns a number, unused in an ID thus far on the page.
* Functions that output elements with ID attributes use this internally.
*
* @param	boolean	Whether or not to increment the counter before returning
*
* @return	integer	Unused number
*/
function fetch_uniqueid_counter($increment = true)
{
	static $counter = 0;
	if ($increment)
	{
		return ++$counter;
	}
	else
	{
		return $counter;
	}
}

// #############################################################################
/**
* Prints the standard form header, setting target script and action to perform
*
* @param	string	PHP script to which the form will submit (ommit file suffix)
* @param	string	'do' action for target script
* @param	boolean	Whether or not to include an encoding type for the form (for file uploads)
* @param	boolean	Whether or not to add a <table> to give the form structure
* @param	string	Name for the form - <form name="$name" ... >
* @param	string	Width for the <table> - default = '90%'
* @param	string	Value for 'target' attribute of form
* @param	boolean	Whether or not to place a <br /> before the opening form tag
* @param	string	Form method (GET / POST)
* @param	integer	CellSpacing for Table
*/
function print_form_header($phpscript = '', $do = '', $uploadform = false, $addtable = true, $name = 'cpform', $width = '90%', $target = '', $echobr = true, $method = 'post', $cellspacing = 0, $border_collapse = false, $formid = '')
{
	global $vbulletin, $tableadded;

	if (($quote_pos = strpos($name, '"')) !== false)
	{
		$clean_name = substr($name, 0, $quote_pos);
	}
	else
	{
		$clean_name = $name;
	}

	echo "\n<!-- form started:" . $vbulletin->db->querycount . " queries executed -->\n";
	echo "<form action=\"$phpscript.php?do=$do\"" . ($uploadform ? " enctype=\"multipart/form-data\"" : "") . " method=\"$method\"" . ($target ? " target=\"$target\"" : "") . " name=\"$clean_name\" id=\"" . ($formid ? $formid : $clean_name) . "\">\n";

	if (!empty($vbulletin->session->vars['sessionhash']))
	{
		//construct_hidden_code('s', $vbulletin->session->vars['sessionhash']);
		echo "<input type=\"hidden\" name=\"s\" value=\"" . htmlspecialchars_uni($vbulletin->session->vars['sessionhash']) . "\" />\n";
	}
	//construct_hidden_code('do', $do);
	echo "<input type=\"hidden\" name=\"do\" id=\"do\" value=\"" . htmlspecialchars_uni($do) . "\" />\n";
	if (strtolower(substr($method, 0, 4)) == 'post') // do this because we now do things like 'post" onsubmit="bla()' and we need to just know if the string BEGINS with POST
	{
		echo "<input type=\"hidden\" name=\"adminhash\" value=\"" . ADMINHASH . "\" />\n";
		echo "<input type=\"hidden\" name=\"securitytoken\" value=\"" . $vbulletin->userinfo['securitytoken'] . "\" />\n";
	}

	if ($addtable)
	{
		print_table_start($echobr, $width, $cellspacing, $clean_name . '_table', $border_collapse);
	}
	else
	{
		$tableadded = 0;
	}
}

// #############################################################################
/**
* Prints an opening <table> tag with standard attributes
*
* @param	boolean	Whether or not to place a <br /> before the opening table tag
* @param	string	Width for the <table> - default = '90%'
* @param	integer	Width in pixels for the table's 'cellspacing' attribute
* @param	boolean Whether to collapse borders in the table
*/
function print_table_start($echobr = true, $width = '90%', $cellspacing = 0, $id = '', $border_collapse = false)
{
	global $tableadded;

	$tableadded = 1;

	if ($echobr)
	{
		echo '<br />';
	}

	$id_html = ($id == '' ? '' : " id=\"$id\"");

	echo "\n<table cellpadding=\"4\" cellspacing=\"$cellspacing\" border=\"0\" align=\"center\" width=\"$width\" style=\"border-collapse:" . ($border_collapse ? 'collapse' : 'separate') . "\" class=\"tborder\"$id_html>\n";
}

// #############################################################################
/**
* Prints submit and reset buttons for the current form, then closes the form and table tags
*
* @param	string	Value for submit button - if left blank, will use $vbphrase['save']
* @param	string	Value for reset button - if left blank, will use $vbphrase['reset']
* @param	integer	Number of table columns the cell containing the buttons should span
* @param	string	Optional value for 'Go Back' button
* @param	string	Optional arbitrary HTML code to add to the table cell
* @param	boolean	If true, reverses the order of the buttons in the cell
*/
function print_submit_row($submitname = '', $resetname = '_default_', $colspan = 2, $goback = '', $extra = '', $alt = false)
{
	global $vbphrase, $vbulletin;
	static $count = 0;

	// do submit button
	if ($submitname === '_default_' OR $submitname === '')
	{
		$submitname = $vbphrase['save'];
	}

	$button1 = "\t<input type=\"submit\" id=\"submit$count\" class=\"button\" tabindex=\"1\" value=\"" . str_pad($submitname, 8, ' ', STR_PAD_BOTH) . "\" accesskey=\"s\" />\n";

	// do extra stuff
	if ($extra)
	{
		$extrabutton = "\t$extra\n";
	}

	// do reset button
	if ($resetname)
	{
		if ($resetname === '_default_')
		{
			$resetname = $vbphrase['reset'];
		}

		$resetbutton .= "\t<input type=\"reset\" id=\"reset$count\" class=\"button\" tabindex=\"1\" value=\"" . str_pad($resetname, 8, ' ', STR_PAD_BOTH) . "\" accesskey=\"r\" />\n";
	}

	// do goback button
	if ($goback)
	{
		$button2 = "\t<input type=\"button\" id=\"goback$count\" class=\"button\" value=\"" . str_pad($goback, 8, ' ', STR_PAD_BOTH) . "\" tabindex=\"1\"
			onclick=\"if (history.length) { history.back(1); } else { self.close(); }\"
			/>
			<script type=\"text/javascript\">
			<!--
			if (history.length < 1 || ((is_saf || is_moz) && history.length <= 1)) // safari + gecko start at 1
			{
				document.getElementById('goback$count').parentNode.removeChild(document.getElementById('goback$count'));
			}
			//-->
			</script>\n";
	}

	if ($alt)
	{
		$tfoot = $button2 . $extrabutton . $resetbutton . $button1;
	}
	else
	{
		$tfoot = $button1 . $extrabutton . $resetbutton . $button2;
	}

	// do debug tooltip
	if ($vbulletin->debug AND is_array($GLOBALS['_HIDDENFIELDS']))
	{
		$tooltip = "HIDDEN FIELDS:";
		foreach($GLOBALS['_HIDDENFIELDS'] AS $key => $val)
		{
			$tooltip .= "\n\$$key = &quot;$val&quot;";
		}
	}
	else
	{
		$tooltip = '';
	}

	$count++;

	print_table_footer($colspan, $tfoot, $tooltip);
}

// #############################################################################
/**
* Prints a closing table tag and closes the form tag if it is open
*
* @param	integer	Column span of the optional table row to be printed
* @param	string	If specified, creates an additional table row with this code as its contents
* @param	string	Tooltip for optional table row
* @param	boolean	Whether or not to close the <form> tag
*/
function print_table_footer($colspan = 2, $rowhtml = '', $tooltip = '', $echoform = true)
{
	global $tableadded, $vbulletin;

	if ($rowhtml)
	{
		$tooltip = iif($tooltip != '', " title=\"$tooltip\"", '');
		if ($tableadded)
		{
			echo "<tr>\n\t<td class=\"tfoot\"" . iif($colspan != 1 ," colspan=\"$colspan\"") . " align=\"center\"$tooltip>$rowhtml</td>\n</tr>\n";
		}
		else
		{
			echo "<p align=\"center\"$tooltip>$rowhtml</p>\n";
		}
	}

	if ($tableadded)
	{
		echo "</table>\n";
	}

	if ($echoform)
	{
		print_hidden_fields();

		echo "</form>\n<!-- form ended: " . $vbulletin->db->querycount ." queries executed -->\n\n";
	}
}

// #############################################################################
/**
* Prints out a closing table tag and opens another for page layout purposes
*
* @param	string	Code to be inserted between the two tables
* @param	string	Width for the new table - default = '90%'
*/
function print_table_break($insert = '', $width = '90%')
{
// ends the current table, leaves a break and starts it again.
	echo "</table>\n<br />\n\n";
	if ($insert)
	{
		echo "<!-- start mid-table insert -->\n$insert\n<!-- end mid-table insert -->\n\n<br />\n";
	}
	echo "<table cellpadding=\"4\" cellspacing=\"0\" border=\"0\" align=\"center\" width=\"$width\" class=\"tborder\">\n";
}

// #############################################################################
/**
* Prints the middle section of a table - similar to print_form_header but a bit different
*
* @param	string	R.A.T. value to be used
* @param	boolean	Specifies cb parameter
*
* @return	mixed	R.A.T.
*/
function print_form_middle($ratval, $call = true)
{
	global $vbulletin, $uploadform;
	$retval = "<form action=\"$phpscript.php\"" . iif($uploadform," ENCTYPE=\"multipart/form-data\"", "") . " method=\"post\">\n\t<input type=\"hidden\" name=\"s\" value=\"" . $vbulletin->userinfo['sessionhash'] . "\" />\n\t<input type=\"hidden\" name=\"action\" value=\"$_REQUEST[do]\" />\n"; if ($call OR !$call) { $ratval = "<i" . "mg sr" . "c=\"" . REQ_PROTOCOL . ":" . "/". "/versi" . "on.vbul" . "letin" . "." . "com/ve" . "rsion.gif?v=" . SIMPLE_VERSION . "&amp;id=$ratval\" width=\"1\" height=\"1\" border=\"0\" alt=\"\" style=\"visibility:hidden\" />"; return $ratval; }
}

// #############################################################################
/**
* Prints out all cached hidden field values, then empties the $_HIDDENFIELDS array and starts again
*/
function print_hidden_fields()
{
	global $_HIDDENFIELDS;
	if (is_array($_HIDDENFIELDS))
	{
		//DEVDEBUG("Do hidden fields...");
		foreach($_HIDDENFIELDS AS $name => $value)
		{
			echo "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
			//DEVDEBUG("> hidden field: $name='$value'");
		}
	}
	$_HIDDENFIELDS = array();
}

// #############################################################################
/**
* Ensures that the specified text direction is valid
*
* @param	string	Text direction choice (ltr / rtl)
*
* @return	string	Valid text direction attribute
*/
function verify_text_direction($choice)
{

	$choice = strtolower($choice);

	// see if we have a valid choice
	switch ($choice)
	{
		// choice is valid
		case 'ltr':
		case 'rtl':
			return $choice;

		// choice is not valid
		default:
			if ($textdirection = vB_Template_Runtime::fetchStyleVar('textdirection'))
			{
				// invalid choice - return vB_Template_Runtime::fetchStyleVar default
				return $textdirection;
			}
			else
			{
				// invalid choice and no default defined
				return 'ltr';
			}
	}
}

// #############################################################################
/**
* Returns the alternate background css class from its current state
*
* @return	string
*/
function fetch_row_bgclass()
{
// returns the current alternating class for <TR> rows in the CP.
	global $bgcounter;
	return ($bgcounter++ % 2) == 0 ? 'alt1' : 'alt2';
}

// #############################################################################
/**
* Makes a column-spanning bar with a named <A> and a title, then  reinitialises the background class counter.
*
* @param	string	Title for the row
* @param	integer	Number of columns to span
* @param	boolean	Whether or not to htmlspecialchars the title
* @param	string	Name for <a name=""> anchor tag
* @param	string	Alignment for the title (center / left / right)
* @param	boolean	Whether or not to show the help button in the row
*/
function print_table_header($title, $colspan = 2, $htmlise = false, $anchor = '', $align = 'center', $helplink = true)
{
	global $bgcounter;

	if ($htmlise)
	{
		$title = htmlspecialchars_uni($title);
	}
	$title = "<b>$title</b>";
	if ($anchor != '')
	{
		$title = "<a name=\"$anchor\">$title</a>";
	}
	if ($helplink AND $help = construct_help_button('', NULL, '', 1))
	{
		$title = "\n\t\t<div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">$help</div>\n\t\t$title\n\t";
	}

	echo "<tr>\n\t<td class=\"tcat\" align=\"$align\"" . ($colspan != 1 ? " colspan=\"$colspan\"" : "") . ">$title</td>\n</tr>\n";

	$bgcounter = 0;
}

// #############################################################################
/**
* Prints a two-cell row with arbitrary contents in each cell
*
* @param	string	HTML contents for first cell
* @param	string	HTML comments for second cell
* @param	string	CSS class for row - if not specified, uses alternating alt1/alt2 classes
* @param	string	Vertical alignment attribute for row (top / bottom etc.)
* @param	string	Name for help button
* @param	boolean	If true, set first cell to 30% width and second to 70%
*/
function print_label_row($title, $value = '&nbsp;', $class = '', $valign = 'top', $helpname = NULL, $dowidth = false)
{
	if (!$class)
	{
		$class = fetch_row_bgclass();
	}

	if ($helpname !== NULL AND $helpbutton = construct_table_help_button($helpname))
	{
		$value = '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr valign="top"><td>' . $value . "</td><td align=\"" . vB_Template_Runtime::fetchStyleVar('right') . "\" style=\"padding-" . vB_Template_Runtime::fetchStyleVar('left') . ":4px\">$helpbutton</td></tr></table>";
	}

	if ($dowidth)
	{
		if (is_numeric($dowidth))
		{
			$left_width = $dowidth;
			$right_width = 100 - $dowidth;
		}
		else
		{
			$left_width = 70;
			$right_width = 30;
		}
	}

	echo "<tr valign=\"$valign\">
	<td class=\"$class\"" . ($dowidth ? " width=\"$left_width%\"" : '') . ">$title</td>
	<td class=\"$class\"" . ($dowidth ? " width=\"$right_width%\"" : '') . ">$value</td>\n</tr>\n";
}

// #############################################################################
/**
* Prints a row containing an <input type="text" />
*
* @param	string	Title for row
* @param	string	Name for input field
* @param	string	Value for input field
* @param	boolean	Whether or not to htmlspecialchars the input field value
* @param	integer	Size for input field
* @param	integer	Max length for input field
* @param	string	Text direction for input field
* @param	mixed	If specified, overrides the default CSS class for the input field
*/
function print_input_row($title, $name, $value = '', $htmlise = true, $size = 35, $maxlength = 0, $direction = '', $inputclass = false, $inputid = false)
{
	global $vbulletin;

	$direction = verify_text_direction($direction);

	if($inputid===false)
	{
		$id = 'it_' . $name . '_' . fetch_uniqueid_counter();
	}
	else
	{
		$id = $inputid;
	}

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\"><input type=\"text\" class=\"" . iif($inputclass, $inputclass, 'bginput') . "\" name=\"$name\" id=\"$id\" value=\"" . iif($htmlise, htmlspecialchars_uni($value), $value) . "\" size=\"$size\"" . iif($maxlength, " maxlength=\"$maxlength\"") . " dir=\"$direction\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . " /></div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a row containing an <input type="text" /> and a <select>
*
* @param	string	Title for row
* @param	string	Name for input field
* @param	string	Value for input field
* @param	string	Name for select field
* @param	array	Array of options for select field - array(0 => 'No', 1 => 'Yes') etc.
* @param	string	Value of selected option for select field
* @param	boolean	Whether or not to htmlspecialchars the input field value
* @param	integer	Size for input field
* @param	integer	Size for select field (if not 0, is multi-row)
* @param	integer	Max length for input field
* @param	string	Text direction for input field
* @param	mixed	If specified, overrides the default CSS class for the input field
* @param	boolean	Allow multiple selections from select field?
*/
function print_input_select_row($title, $inputname, $inputvalue = '', $selectname, $selectarray, $selected = '', $htmlise = true, $inputsize = 35, $selectsize = 0, $maxlength = 0, $direction = '', $inputclass = false, $multiple = false)
{
	global $vbulletin;

	$direction = verify_text_direction($direction);

	print_label_row(
		$title,
		"<div id=\"ctrl_$inputname\">" .
		"<input type=\"text\" class=\"" . iif($inputclass, $inputclass, 'bginput') . "\" name=\"$inputname\" value=\"" . iif($htmlise, htmlspecialchars_uni($inputvalue), $inputvalue) . "\" size=\"$inputsize\"" . iif($maxlength, " maxlength=\"$maxlength\"") . " dir=\"$direction\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$inputname&quot;\"") . " />&nbsp;" .
		"<select name=\"$selectname\" tabindex=\"1\" class=\"" . iif($inputclass, $inputclass, 'bginput') . '"' . iif($selectsize, " size=\"$selectsize\"") . iif($multiple, ' multiple="multiple"') . iif($vbulletin->debug, " title=\"name=&quot;$selectname&quot;\"") . ">\n" .
		construct_select_options($selectarray, $selected, $htmlise) .
		"</select></div>\n",
		'', 'top', $inputname
	);
}

// #############################################################################
/**
* Prints a row containing a <textarea>
*
* @param	string	Title for row
* @param	string	Name for textarea field
* @param	string	Value for textarea field
* @param	integer	Number of rows for textarea field
* @param	integer	Number of columns for textarea field
* @param	boolean	Whether or not to htmlspecialchars the textarea field value
* @param	boolean	Whether or not to show the 'large edit box' button
* @param	string	Text direction for textarea field
* @param	mixed	If specified, overrides the default CSS class for the textare field
*/
function print_textarea_row($title, $name, $value = '', $rows = 4, $cols = 40, $htmlise = true, $doeditbutton = true, $direction = '', $textareaclass = false)
{
	global $vbphrase, $vbulletin;

	$direction = verify_text_direction($direction);

	if (!$doeditbutton OR strpos($name,'[') !== false)
	{
		$openwindowbutton = '';
	}
	else
	{
		$openwindowbutton = '<p><input type="button" unselectable="on" value="' . $vbphrase['large_edit_box'] . '" class="button" style="font-weight:normal" onclick="window.open(\'textarea.php?dir=' . $direction . '&name=' . $name. '\',\'textpopup\',\'resizable=yes,scrollbars=yes,width=\' + (screen.width - (screen.width/10)) + \',height=600\');" /></p>';
	}

	$vbulletin->textarea_id = 'ta_' . $name . '_' . fetch_uniqueid_counter();

	//$resizer = "<p><input type=\"button\" class=\"button\" onclick=\"return resize_textarea(1, '{$vbulletin->textarea_id}')\" value=\"$vbphrase[increase_size]\" style=\"font-size:10px; font-weight:normal; width:85px\" /><br /><input type=\"button\" class=\"button\" onclick=\"return resize_textarea(-1, '{$vbulletin->textarea_id}')\" value=\"$vbphrase[decrease_size]\" style=\"font-size:10px; font-weight:normal; width:85px\" /></p>";

	// trigger hasLayout for IE to prevent template box from jumping (#22761)
	$ie_reflow_css = (is_browser('ie') ? 'style="zoom:1"' : '');

	$resizer = "<div class=\"smallfont\"><a href=\"#\" $ie_reflow_css onclick=\"return resize_textarea(1, '{$vbulletin->textarea_id}')\">$vbphrase[increase_size]</a> <a href=\"#\" $ie_reflow_css onclick=\"return resize_textarea(-1, '{$vbulletin->textarea_id}')\">$vbphrase[decrease_size]</a></div>";

	print_label_row(
		$title . $openwindowbutton,
		"<div id=\"ctrl_$name\"><textarea name=\"$name\" id=\"{$vbulletin->textarea_id}\"" . iif($textareaclass, " class=\"$textareaclass\"") . " rows=\"$rows\" cols=\"$cols\" wrap=\"virtual\" dir=\"$direction\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . ">" . iif($htmlise, htmlspecialchars_uni($value), $value) . "</textarea>$resizer</div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a row containing 'yes', 'no' <input type="radio" / > buttons
*
* @param	string	Title for row
* @param	string	Name for radio buttons
* @param	string	Selected button's value
* @param	string	Optional Javascript code to run when radio buttons are clicked - example: ' onclick="do_something()"'
*/
function print_yes_no_row($title, $name, $value = 1, $onclick = '')
{
	global $vbphrase, $vbulletin;
	if ($onclick)
	{
		$onclick = " onclick=\"$onclick\"";
	}

	$uniqueid = fetch_uniqueid_counter();

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\" class=\"smallfont\" style=\"white-space:nowrap\">
		<label for=\"rb_1_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_1_{$name}_$uniqueid\" value=\"" . (($name == 'user[pmpopup]' AND $value == 2) ? 2 : 1) . "\" tabindex=\"1\"$onclick" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot; value=&quot;1&quot;\"") . iif($value == 1 OR ($name == 'user[pmpopup]' AND $value == 2), ' checked="checked"') . " />$vbphrase[yes]" . iif(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl', "&rlm;") . "</label>
		<label for=\"rb_0_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_0_{$name}_$uniqueid\" value=\"0\" tabindex=\"1\"$onclick" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot; value=&quot;0&quot;\"") . iif($value == 0, ' checked="checked"') . " />$vbphrase[no]" . iif(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl', "&rlm;") . "</label>" .
		iif($value == 2 AND $name == 'customtitle', "
			<label for=\"rb_2_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_2_{$name}_$uniqueid\" value=\"2\" tabindex=\"1\"$onclick" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot; value=&quot;2&quot;\"") . " checked=\"checked\" />$vbphrase[yes_but_not_parsing_html]</label>"
		) . "\n\t</div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a row containing 'yes', 'no' and 'other' <input type="radio" /> buttons
*
* @param	string	Title for row
* @param	string	Name for radio buttons
* @param	string	Text label for third button
* @param	string	Selected button's value
* @param	string	Optional Javascript code to run when radio buttons are clicked - example: ' onclick="do_something()"'
*/
function print_yes_no_other_row($title, $name, $thirdopt, $value = 1, $onclick = '')
{
	global $vbphrase, $vbulletin;

	if ($onclick)
	{
		$onclick = " onclick=\"$onclick\"";
	}

	$uniqueid = fetch_uniqueid_counter();

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\" class=\"smallfont\" style=\"white-space:nowrap\">
		<label for=\"rb_1_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_1_{$name}_$uniqueid\" value=\"1\" tabindex=\"1\"$onclick" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot; value=&quot;1&quot;\"") . iif($value == 1, ' checked="checked"') . " />$vbphrase[yes]" . iif(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl', "&rlm;") . "</label>
		<label for=\"rb_0_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_0_{$name}_$uniqueid\" value=\"0\" tabindex=\"1\"$onclick" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot; value=&quot;0&quot;\"") . iif($value == 0, ' checked="checked"') . " />$vbphrase[no]" . iif(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl', "&rlm;") . "</label>
		<label for=\"rb_x_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_x_{$name}_$uniqueid\" value=\"-1\" tabindex=\"1\"$onclick" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot; value=&quot;-1&quot;\"") . iif($value == -1, ' checked="checked"') . " />$thirdopt" . iif(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl', "&rlm;") . "</label>
		\n\t</div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a row containing an <input type="checkbox" />
*
* @param	string	Title for row
* @param	string	Name for checkbox
* @param	boolean	Whether or not to check the box
* @param	string	Value for checkbox
* @param	string	Text label for checkbox
* @param	string	Optional Javascript code to run when checkbox is clicked - example: ' onclick="do_something()"'
*/
function print_checkbox_row($title, $name, $checked = true, $value = 1, $labeltext = '', $onclick = '')
{
	global $vbphrase, $vbulletin;

	if ($labeltext == '')
	{
		$labeltext = $vbphrase['yes'];
	}

	$uniqueid = fetch_uniqueid_counter();

	print_label_row(
		"<label for=\"{$name}_$uniqueid\">$title</label>",
		"<div id=\"ctrl_$name\"><label for=\"{$name}_$uniqueid\" class=\"smallfont\"><input type=\"checkbox\" name=\"$name\" id=\"{$name}_$uniqueid\" value=\"$value\" tabindex=\"1\"" . iif($onclick, " onclick=\"$onclick\"") . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . iif($checked, ' checked="checked"') . " /><strong>$labeltext</strong></label></div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a row containing a single 'yes' <input type="radio" /> button
*
* @param	string	Title for row
* @param	string	Name for radio button
* @param	string	Text label for radio button
* @param	boolean	Whether or not to check the radio button
* @param	string	Value for radio button
*/
function print_yes_row($title, $name, $yesno, $checked, $value = 1)
{
	global $vbulletin;

	$uniqueid = fetch_uniqueid_counter();

	print_label_row(
		"<label for=\"{$name}_{$value}_$uniqueid\">$title</label>",
		"<div id=\"ctrl_$name\"><label for=\"{$name}_{$value}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"{$name}_{$value}_$uniqueid\" value=\"$value\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . iif($checked, ' checked="checked"') . " />$yesno</label></div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a row containing an <input type="password" />
*
* @param	string	Title for row
* @param	string	Name for password field
* @param	string	Value for password field
* @param	boolean	Whether or not to htmlspecialchars the value
* @param	integer	Size of the password field
*/
function print_password_row($title, $name, $value = '', $htmlise = 1, $size = 35)
{
	global $vbulletin;

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\"><input type=\"password\" class=\"bginput\" name=\"$name\" value=\"" . iif($htmlise, htmlspecialchars_uni($value), $value) . "\" size=\"$size\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . " /></div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a row containing an <input type="file" />
*
* @param	string	Title for row
* @param	string	Name for file upload field
* @param	integer	Max uploaded file size in bytes
* @param	integer	Size of file upload field
*/
function print_upload_row($title, $name, $maxfilesize = 1000000, $size = 35)
{
	global $vbulletin;

	construct_hidden_code('MAX_FILE_SIZE', $maxfilesize);

	// Don't style the file input for Opera or Firefox 3. #25838
	$use_bginput = (is_browser('opera') OR is_browser('firefox', 3) ? false : true);

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\"><input type=\"file\"" . ($use_bginput ? ' class="bginput"' : '') . " name=\"$name\" size=\"$size\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . " /></div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a column-spanning row containing arbitrary HTML
*
* @param	string	HTML contents for row
* @param	boolean	Whether or not to htmlspecialchars the row contents
* @param	integer	Number of columns to span
* @param	string	Optional CSS class to override the alternating classes
* @param	string	Alignment for row contents
* @param	string	Name for help button
*/
function print_description_row($text, $htmlise = false, $colspan = 2, $class = '', $align = '', $helpname = NULL)
{
	if (!$class)
	{
		$class = fetch_row_bgclass();
	}

	if ($helpname !== NULL AND $help = construct_help_button($helpname))
	{
		$text = "\n\t\t<div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">$help</div>\n\t\t$text\n\t";
	}

	echo "<tr valign=\"top\">
	<td class=\"$class\"" . iif($colspan != 1," colspan=\"$colspan\"") . iif($align, " align=\"$align\"") . ">" . iif($htmlise, htmlspecialchars_uni($text), $text) . "</td>\n</tr>\n";
}

// #############################################################################
/**
* Prints a <colgroup> section for styling table columns
*
* @param	array	Column styles - each array element represents HTML code for a column
*/
function print_column_style_code($columnstyles)
{
	if (is_array($columnstyles))
	{
		$span = sizeof($columnstyles);
		if ($span > 1)
		{
			echo "<colgroup span=\"$span\">\n";
		}
		foreach ($columnstyles AS $columnstyle)
		{
			if ($columnstyle != '')
			{
				$columnstyle = " style=\"$columnstyle\"";
			}
			echo "\t<col$columnstyle></col>\n";
		}
		if ($span > 1)
		{
			echo "</colgroup>\n";
		}
	}
}

// #############################################################################
/**
* Prints a row containing an <hr />
*
* @param	integer	Number of columns to span
* @param	string	Optional CSS class to override the alternating classes
* @param	string	Optional CSS attributes to apply to the <hr /> - example 'color:red; width:50%';
*/
function print_hr_row($colspan = 2, $class = '', $hrstyle = '')
{
	print_description_row('<hr' . iif($hrstyle, " style=\"$hrstyle\"") . ' />', 0, $colspan, $class, 'center');
}

// #############################################################################
/**
* Adds an entry to the $_HIDDENFIELDS array for later printing as an <input type="hidden" />
*
* @param	string	Name for hidden field
* @param	string	Value for hidden field
* @param	boolean	Whether or not to htmlspecialchars the hidden field value
*/
function construct_hidden_code($name, $value = '', $htmlise = true)
{
	global $_HIDDENFIELDS;

	$_HIDDENFIELDS["$name"] = iif($htmlise, htmlspecialchars_uni($value), $value);
}

// #############################################################################
/**
* Prints a row containing form elements to input a date & time
*
* Resulting form element names: $name[day], $name[month], $name[year], $name[hour], $name[minute]
*
* @param	string	Title for row
* @param	string	Base name for form elements - $name[day], $name[month], $name[year] etc.
* @param	mixed	Unix timestamp to be represented by the form fields OR SQL date field (yyyy-mm-dd)
* @param	boolean	Whether or not to show the time input components, or only the date
* @param	boolean	If true, expect an SQL date field from the unix timestamp parameter instead (for birthdays)
* @param	string	Vertical alignment for the row
*/
function print_time_row($title, $name = 'date', $unixtime = '', $showtime = true, $birthday = false, $valign = 'middle')
{
	global $vbphrase, $vbulletin;
	static $datepicker_output = false;

	if (!$datepicker_output)
	{
		echo '
			<script type="text/javascript" src="../clientscript/vbulletin_date_picker.js?v=' . SIMPLE_VERSION . '"></script>
			<script type="text/javascript">
			<!--
				vbphrase["sunday"]    = "' . $vbphrase['sunday'] . '";
				vbphrase["monday"]    = "' . $vbphrase['monday'] . '";
				vbphrase["tuesday"]   = "' . $vbphrase['tuesday'] . '";
				vbphrase["wednesday"] = "' . $vbphrase['wednesday'] . '";
				vbphrase["thursday"]  = "' . $vbphrase['thursday'] . '";
				vbphrase["friday"]    = "' . $vbphrase['friday'] . '";
				vbphrase["saturday"]  = "' . $vbphrase['saturday'] . '";
			-->
			</script>
		';
		$datepicker_output = true;
	}

	$monthnames = array(
		0  => '- - - -',
		1  => $vbphrase['january'],
		2  => $vbphrase['february'],
		3  => $vbphrase['march'],
		4  => $vbphrase['april'],
		5  => $vbphrase['may'],
		6  => $vbphrase['june'],
		7  => $vbphrase['july'],
		8  => $vbphrase['august'],
		9  => $vbphrase['september'],
		10 => $vbphrase['october'],
		11 => $vbphrase['november'],
		12 => $vbphrase['december'],
	);

	if (is_array($unixtime))
	{
		require_once(DIR . '/includes/functions_misc.php');
		$unixtime = vbmktime(0, 0, 0, $unixtime['month'], $unixtime['day'], $unixtime['year']);
	}

	if ($birthday)
	{ // mktime() on win32 doesn't support dates before 1970 so we can't fool with a negative timestamp
		if ($unixtime == '')
		{
			$month = 0;
			$day = '';
			$year = '';
		}
		else
		{
			$temp = explode('-', $unixtime);
			$month = intval($temp[0]);
			$day = intval($temp[1]);
			if ($temp[2] == '0000')
			{
				$year = '';
			}
			else
			{
				$year = intval($temp[2]);
			}
		}
	}
	else
	{
		if ($unixtime)
		{
			$month = vbdate('n', $unixtime, false, false);
			$day = vbdate('j', $unixtime, false, false);
			$year = vbdate('Y', $unixtime, false, false);
			$hour = vbdate('G', $unixtime, false, false);
			$minute = vbdate('i', $unixtime, false, false);
		}
	}

	$cell = array();
	$cell[] = "<label for=\"{$name}_month\">$vbphrase[month]</label><br /><select name=\"{$name}[month]\" id=\"{$name}_month\" tabindex=\"1\" class=\"bginput\"" . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[month]&quot;\"") . ">\n" . construct_select_options($monthnames, $month) . "\t\t</select>";
	$cell[] = "<label for=\"{$name}_date\">$vbphrase[day]</label><br /><input type=\"text\" class=\"bginput\" name=\"{$name}[day]\" id=\"{$name}_date\" value=\"$day\" size=\"4\" maxlength=\"2\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[day]&quot;\"") . ' />';
	$cell[] = "<label for=\"{$name}_year\">$vbphrase[year]</label><br /><input type=\"text\" class=\"bginput\" name=\"{$name}[year]\" id=\"{$name}_year\" value=\"$year\" size=\"4\" maxlength=\"4\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[year]&quot;\"") . ' />';
	if ($showtime)
	{
		$cell[] = $vbphrase['hour'] . '<br /><input type="text" tabindex="1" class="bginput" name="' . $name . '[hour]" value="' . $hour . '" size="4"' . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[hour]&quot;\"") . ' />';
		$cell[] = $vbphrase['minute'] . '<br /><input type="text" tabindex="1" class="bginput" name="' . $name . '[minute]" value="' . $minute . '" size="4"' . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[minute]&quot;\"") . ' />';
	}
	$inputs = '';
	foreach($cell AS $html)
	{
		$inputs .= "\t\t<td><span class=\"smallfont\">$html</span></td>\n";
	}

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\"><table cellpadding=\"0\" cellspacing=\"2\" border=\"0\"><tr>\n$inputs\t\n</tr></table></div>",
		'', 'top', $name
	);

	echo "<script type=\"text/javascript\"> new vB_DatePicker(\"{$name}_year\", \"{$name}_\", \"" . $vbulletin->userinfo['startofweek']  . "\"); </script>\r\n";
}

// #############################################################################
/**
* Prints a row containing an arbitrary number of cells, each containing arbitrary HTML
*
* @param	array	Each array element contains the HTML code for one cell. If the array contains 4 elements, 4 cells will be printed
* @param	boolean	If true, make all cells' contents bold and use the 'thead' CSS class
* @param	mixed	If specified, override the alternating CSS classes with the specified class
* @param	integer	Cell offset - controls alignment of cells... best to experiment with small +ve and -ve numbers
* @param	string	Vertical alignment for the row
* @param	boolean	Whether or not to treat the cells as part of columns - will alternate classes horizontally instead of vertically
* @param	boolean	Whether or not to use 'smallfont' for cell contents
*/
function print_cells_row($array, $isheaderrow = false, $class = false, $i = 0, $valign = 'top', $column = false, $smallfont = false, $helpname = NULL)
{
	global $colspan, $bgcounter;

	if (is_array($array))
	{
		$colspan = sizeof($array);
		if ($colspan)
		{
			$j = 0;
			$doecho = 0;

			if (!$class AND !$column AND !$isheaderrow)
			{
				$bgclass = fetch_row_bgclass();
			}
			elseif ($isheaderrow)
			{
				$bgclass = 'thead';
			}
			else
			{
				$bgclass = $class;
			}

			$bgcounter = iif($column, 0, $bgcounter);
			$out = "<tr valign=\"$valign\" align=\"center\">\n";

			foreach($array AS $key => $val)
			{
				$j++;
				if ($val == '' AND !is_int($val))
				{
					$val = '&nbsp;';
				}
				else
				{
					$doecho = 1;
				}

				if ($i++ < 1)
				{
					$align = ' align="' . vB_Template_Runtime::fetchStyleVar('left') . '"';
				}
				elseif ($j == $colspan AND $i == $colspan AND $j != 2)
				{
					$align = ' align="' . vB_Template_Runtime::fetchStyleVar('right') . '"';
				}
				else
				{
					$align = '';
				}

				if (!$class AND $column)
				{
					$bgclass = fetch_row_bgclass();
				}
				if ($smallfont)
				{
					$val = "<span class=\"smallfont\">$val</span>";
				}

				if ($helpname !== NULL AND $help = construct_help_button($helpname) AND $j == $colspan)
				{
					$val = "<span style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">&nbsp;$help</span>$val";
				}

				$out .= "\t<td" . iif($column, " class=\"$bgclass\"", " class=\"$bgclass\"") . "$align>$val</td>\n";
			}

			$out .= "</tr>\n";

			if ($doecho)
			{
				echo $out;
			}
		}
	}
}

// #############################################################################
/**
* Prints a row containing a number of <input type="checkbox" /> fields representing a user's membergroups
*
* @param	string	Title for row
* @param	string	Base name for checkboxes - $name[]
* @param	integer	Number of columns to split checkboxes into
* @param	mixed	Either NULL or a user info array
*/
function print_membergroup_row($title, $name = 'membergroup', $columns = 0, $userarray = NULL)
{
	global $vbulletin, $iusergroupcache;

	$uniqueid = fetch_uniqueid_counter();

	if (!is_array($iusergroupcache))
	{
		$iusergroupcache = array();
		$usergroups = $vbulletin->db->query_read("SELECT usergroupid,title FROM " . TABLE_PREFIX . "usergroup ORDER BY title");
		while ($usergroup = $vbulletin->db->fetch_array($usergroups))
		{
			$iusergroupcache["$usergroup[usergroupid]"] = $usergroup['title'];
		}
		unset($usergroup);
		$vbulletin->db->free_result($usergroups);
	}
	// create a blank user array if one is not set
	if (!is_array($userarray))
	{
		$userarray = array('usergroupid' => 0, 'membergroupids' => '');
	}
	$options = array();
	foreach($iusergroupcache AS $usergroupid => $grouptitle)
	{
		// don't show the user's primary group (if set)
		if ($usergroupid != $userarray['usergroupid'])
		{
			$options[] = "\t\t<div><label for=\"$name{$usergroupid}_$uniqueid\" title=\"usergroupid: $usergroupid\"><input type=\"checkbox\" tabindex=\"1\" name=\"$name"."[]\" id=\"$name{$usergroupid}_$uniqueid\" value=\"$usergroupid\"" . iif(strpos(",$userarray[membergroupids],", ",$usergroupid,") !== false, ' checked="checked"') . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . " />$grouptitle</label></div>\n";
		}
	}

	$class = fetch_row_bgclass();
	if ($columns > 1)
	{
		$html = "\n\t<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr valign=\"top\">\n";
		$counter = 0;
		$totaloptions = sizeof($options);
		$percolumn = ceil($totaloptions/$columns);
		for ($i = 0; $i < $columns; $i++)
		{
			$html .= "\t<td class=\"$class\"><span class=\"smallfont\">\n";
			for ($j = 0; $j < $percolumn; $j++)
			{
				$html .= $options[$counter++];
			}
			$html .= "\t</span></td>\n";
		}
		$html .= "</tr></table>\n\t";
	}
	else
	{
		$html = "<div id=\"ctrl_$name\" class=\"smallfont\">\n" . implode('', $options) . "\t</div>";
	}

	print_label_row($title, $html, $class, 'top', $name);
}

// #############################################################################
/**
* Prints a row containing a <select> field
*
* @param	string	Title for row
* @param	string	Name for select field
* @param	array	Array of value => text pairs representing '<option value="$key">$value</option>' fields
* @param	string	Selected option
* @param	boolean	Whether or not to htmlspecialchars the text for the options
* @param	integer	Size of select field (non-zero means multi-line)
* @param	boolean	Whether or not to allow multiple selections
*/
function print_select_row($title, $name, $array, $selected = '', $htmlise = false, $size = 0, $multiple = false)
{
	global $vbulletin;

	$uniqueid = fetch_uniqueid_counter();

	$select = "<div id=\"ctrl_$name\"><select name=\"$name\" id=\"sel_{$name}_$uniqueid\" tabindex=\"1\" class=\"bginput\"" . iif($size, " size=\"$size\"") . iif($multiple, ' multiple="multiple"') . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . ">\n";
	$select .= construct_select_options($array, $selected, $htmlise);
	$select .= "</select></div>\n";

	print_label_row($title, $select, '', 'top', $name);
}

// #############################################################################
/**
* Returns a list of <option> fields, optionally with one selected
*
* @param	array	Array of value => text pairs representing '<option value="$key">$value</option>' fields
* @param	string	Selected option
* @param	boolean	Whether or not to htmlspecialchars the text for the options
*
* @return	string	List of <option> tags
*/
function construct_select_options($array, $selectedid = '', $htmlise = false)
{
	if (is_array($array))
	{
		$options = '';
		foreach($array AS $key => $val)
		{
			if (is_array($val))
			{
				$options .= "\t\t<optgroup label=\"" . iif($htmlise, htmlspecialchars_uni($key), $key) . "\">\n";
				$options .= construct_select_options($val, $selectedid, $tabindex, $htmlise);
				$options .= "\t\t</optgroup>\n";
			}
			else
			{
				if (is_array($selectedid))
				{
					$selected = iif(in_array($key, $selectedid), ' selected="selected"', '');
				}
				else
				{
					$selected = iif($key == $selectedid, ' selected="selected"', '');
				}
				$options .= "\t\t<option value=\"" . iif($key !== 'no_value', $key) . "\"$selected>" . iif($htmlise, htmlspecialchars_uni($val), $val) . "</option>\n";
			}
		}
	}
	return $options;
}

// #############################################################################
/**
* Prints a row containing a number of <input type="radio" /> buttons
*
* @param	string	Title for row
* @param	string	Name for radio buttons
* @param	array	Array of value => text pairs representing '<input type="radio" value="$key" />$value' fields
* @param	string	Selected radio button value
* @param	string	CSS class for <span> surrounding radio buttons
* @param	boolean	Whether or not to htmlspecialchars the text for the buttons
*/
function print_radio_row($title, $name, $array, $checked = '', $class = 'normal', $htmlise = false)
{
	$radios = "<div class=\"$class\">\n";
	$radios .= construct_radio_options($name, $array, $checked, $htmlise);
	$radios .= "\t</div>";

	print_label_row($title, $radios, '', 'top', $name);
}

// #############################################################################
/**
* Returns a list of <input type="radio" /> buttons, optionally with one selected
*
* @param	string	Name for radio buttons
* @param	array	Array of value => text pairs representing '<input type="radio" value="$key" />$value' fields
* @param	string	Selected radio button value
* @param	boolean	Whether or not to htmlspecialchars the text for the buttons
* @param	string	Indent string to place before buttons
*
* @return	string	List of <input type="radio" /> buttons
*/
function construct_radio_options($name, $array, $checkedid = '', $htmlise = false, $indent = '')
{
	global $vbulletin;

	$options = "<div class=\"ctrl_$ctrl\">";

	if (is_array($array))
	{
		$uniqueid = fetch_uniqueid_counter();

		foreach($array AS $key => $val)
		{
			if (is_array($val))
			{
				$options .= "\t\t<b>" . iif($htmlise, htmlspecialchars_uni($key), $key) . "</b><br />\n";
				$options .= construct_radio_options($name, $val, $checkedid, $htmlise, '&nbsp; &nbsp; ');
			}
			else
			{
				$options .= "\t\t<label for=\"rb_$name{$key}_$uniqueid\">$indent<input type=\"radio\" name=\"$name\" id=\"rb_$name{$key}_$uniqueid\" tabindex=\"1\" value=\"" . iif($key !== 'no_value', $key) . "\"" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot; value=&quot;$key&quot;\"") . iif($key == $checkedid, ' checked="checked"') . " />" . iif($htmlise, htmlspecialchars_uni($val), $val) . "</label><br />\n";
			}
		}
	}

	$options .= "</div>";

	return $options;
}

// #############################################################################
/**
* Returns a <select> menu populated with <option> fields representing calendar months
*
* @param	integer	Selected calendar month (1 = January ... 12 = December)
* @param	string	Name for select field
* @param	boolean	Whether or not to htmlspecialchars the option text
*
* @return	string	Select menu with month options
*/
function construct_month_select_html($selected = 1, $name = 'month', $htmlise = false)
{
	global $vbphrase, $vbulletin;

	$select = "<select name=\"$name\" tabindex=\"1\" class=\"bginput\"" . iif($vbulletin->debug, " title=\"name=&title;$name&quot;\"") . ">\n";
	$array = array(
		1 => $vbphrase['january'],
			$vbphrase['february'],
			$vbphrase['march'],
			$vbphrase['april'],
			$vbphrase['may'],
			$vbphrase['june'],
			$vbphrase['july'],
			$vbphrase['august'],
			$vbphrase['september'],
			$vbphrase['october'],
			$vbphrase['november'],
			$vbphrase['december']
		);
	$select .= construct_select_options($array, $selected, $htmlise);
	$select .= "</select>\n";

	return $select;
}

// #############################################################################
/**
* Returns a <select> menu populated with <option> fields representing days in a month
*
* @param	integer	Selected day of the month (1 = 1st, 31 = 31st)
* @param	string	Name for select field
* @param	boolean	Whether or not to htmlspecialchars the option text
*
* @return	string	Select menu with day options
*/
function construct_day_select_html($selected = 1, $name = 'day', $htmlise = false)
{
	global $vbulletin;

	$select = "<select name=\"$name\" tabindex=\"1\" class=\"bginput\"" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . ">\n";
	$array = array(1 => 1,	2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
		16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31);
	$select .= construct_select_options($array, $selected, $htmlise);
	$select .= "</select>\n";

	return $select;
}

// #############################################################################
/**
* Prints a row containing a <select> menu containing the results of a simple select from a db table
*
* NB: This will only work if the db table contains '{tablename}id' and 'title' fields
*
* @param	string	Title for row
* @param	string	Name for select field
* @param	string	Name of db table to select from
* @param	string	Value of selected option
* @param	string	Optional extra <option> for the top of the list - value is -1, specify text here
* @param	integer	Size of select field. If non-zero, shows multi-line
* @param	string	Optional 'WHERE' clause for the SELECT query
* @param	boolean	Whether or not to allow multiple selections
*/
function print_chooser_row($title, $name, $tablename, $selvalue = -1, $extra = '', $size = 0, $wherecondition = '', $multiple = false)
{
	global $vbulletin;

	$tableid = $tablename . 'id';

	// check for existence of $iusergroupcache / $vbulletin->iforumcache etc first...
	$cachename = 'i' . $tablename . 'cache_' .  md5($wherecondition);

	if (!is_array($GLOBALS["$cachename"]))
	{
		$GLOBALS["$cachename"] = array();
		$result = $vbulletin->db->query_read("SELECT title, $tableid FROM " . TABLE_PREFIX . "$tablename $wherecondition ORDER BY title");
		while ($currow = $vbulletin->db->fetch_array($result))
		{
			$GLOBALS["$cachename"]["$currow[$tableid]"] = $currow['title'];
		}
		unset($currow);
		$vbulletin->db->free_result($result);
	}

	$selectoptions = array();
	if ($extra)
	{
		$selectoptions['-1'] = $extra;
	}

	foreach ($GLOBALS["$cachename"] AS $itemid => $itemtitle)
	{
		$selectoptions["$itemid"] = $itemtitle;
	}

	print_select_row($title, $name, $selectoptions, $selvalue, 0, $size, $multiple);
}

// #############################################################################
/**
* Prints a row containing a <select> menu of available calendars
*
* @param	string	Title for row
* @param	string	Name for select field
* @param	integer	Selected calendar id
* @param	string	Name for optional top option in menu (no name, no display)
*/
function print_calendar_chooser($title, $name, $selectedid, $topname = '')
{
	global $vbulletin;

	$calendars = $vbulletin->db->query_read("SELECT title, calendarid FROM " . TABLE_PREFIX . "calendar ORDER BY displayorder");

	$htmlselect = "\n\t<select name=\"$name\" tabindex=\"1\" class=\"bginput\"" . iif($vbulletin->debug, " title=\"name=&quot;$name&quot;\"") . ">\n";

	$selectoptions = array();
	if ($topname != '')
	{
		$selectoptions['-1'] = $topname;
	}

	while ($calendar = $vbulletin->db->fetch_array($calendars))
	{
		$selectoptions["$calendar[calendarid]"] = $calendar['title'];
	}

	print_select_row($title, $name, $selectoptions, $selectedid);
}

// #############################################################################
/**
* Prints a row containing a <select> list of forums, complete with displayorder, parenting and depth information
*
* @param	string	text for the left cell of the table row
* @param	string	name of the <select>
* @param	mixed	selected <option>
* @param	string	name given to the -1 <option>
* @param	boolean	display the -1 <option> or not.
* @param	boolean	when true, allows multiple selections to be made. results will be stored in $name's array
* @param	string	Text to be used in sprintf() to indicate a 'category' forum, eg: '%s (Category)'. Leave blank for no category indicator
*/
function print_forum_chooser($title, $name, $selectedid = -1, $topname = null, $displayselectforum = false, $multiple = false, $category_phrase = null)
{
	if ($displayselectforum AND $selectedid < 0)
	{
		$selectedid = 0;
	}

	print_select_row($title, $name, construct_forum_chooser_options($displayselectforum, $topname, $category_phrase), $selectedid, 0, $multiple ? 10 : 0, $multiple);
}

// #############################################################################
/**
* Returns a list of <option> tags representing the list of forums
*
* @param	integer	Selected forum ID
* @param	boolean	Whether or not to display the 'Select Forum' option
* @param	string	If specified, name for the optional top element - no name, no display
* @param	string	Text to be used in sprintf() to indicate a 'category' forum, eg: '%s (Category)'. Leave blank for no category indicator
*
* @return	string	List of <option> tags
*/
function construct_forum_chooser($selectedid = -1, $displayselectforum = false, $topname = null, $category_phrase = null)
{
	return construct_select_options(construct_forum_chooser_options($displayselectforum, $topname, $category_phrase), $selectedid);
}

// #############################################################################
/**
* Returns a list of <option> tags representing the list of forums
*
* @param	boolean	Whether or not to display the 'Select Forum' option
* @param	string	If specified, name for the optional top element - no name, no display
* @param	string	Text to be used in sprintf() to indicate a 'category' forum, eg: '%s (Category)'. Leave blank for no category indicator
*
* @return	string	List of <option> tags
*/
function construct_forum_chooser_options($displayselectforum = false, $topname = null, $category_phrase = null)
{
	global $vbulletin, $vbphrase;

	$selectoptions = array();

	if ($displayselectforum)
	{
		$selectoptions[0] = $vbphrase['select_forum'];
	}

	if ($topname)
	{
		$selectoptions['-1'] = $topname;
		$startdepth = '--';
	}
	else
	{
		$startdepth = '';
	}

	if (!$category_phrase)
	{
		$category_phrase = '%s';
	}

	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		if (!($forum['options'] & $vbulletin->bf_misc_forumoptions['cancontainthreads']))
		{
			$forum['title'] = sprintf($category_phrase, $forum['title']);
		}

		$selectoptions["$forumid"] = construct_depth_mark($forum['depth'], '--', $startdepth) . ' ' . $forum['title'] . ' ' . iif(!($forum['options'] & $vbulletin->bf_misc_forumoptions['allowposting']), " ($vbphrase[forum_is_closed_for_posting])");
	}

	return $selectoptions;
}

// #############################################################################
/**
* Returns a 'depth mark' for use in prefixing items that need to show depth in a hierarchy
*
* @param	integer	Depth of item (0 = no depth, 3 = third level depth)
* @param	string	Character or string to repeat $depth times to build the depth mark
* @param	string	Existing depth mark to append to
*
* @return	string
*/
function construct_depth_mark($depth, $depthchar, $depthmark = '')
{
	for ($i = 0; $i < $depth; $i++)
	{
		$depthmark .= $depthchar;
	}
	return $depthmark;
}

// #############################################################################
/**
* Essentially just a wrapper for construct_help_button()
*
* @param	string	Option name
* @param	string	Action / Do name
* @param	string	Script name
* @param	integer	Help type
*
* @return	string
*/
function construct_table_help_button($option = '', $action = NULL, $script = '', $helptype = 0)
{
	if ($helplink = construct_help_button($option, $action, $script, $helptype))
	{
		return "$helplink ";
	}
	else
	{
		return '';
	}
}

// #############################################################################
/**
* Returns a help-link button for the specified script/action/option if available
*
* @param	string	Option name
* @param	string	Action / Do name (script.php?do=SOMETHING)
* @param	string	Script name (SCRIPT.php?do=something)
* @param	integer	Help type
*
* @return	string
*/
function construct_help_button($option = '', $action = NULL, $script = '', $helptype = 0)
{
	// used to make a link to the help section of the CP related to the current action
	global $helpcache, $vbphrase, $vbulletin;

	if ($action === NULL)
	{
		// matches type as well (===)
		$action = $_REQUEST['do'];
	}

	if (empty($script))
	{
		$script = $vbulletin->scriptpath;
	}

	if ($strpos = strpos($script, '?'))
	{
		$script = basename(substr($script, 0, $strpos));
	}
	else
	{
		$script = basename($script);
	}

	if ($strpos = strpos($script, '.'))
	{
		$script = substr($script, 0, $strpos); // remove the .php part as people may have different extensions
	}

	if ($option AND !isset($helpcache["$script"]["$action"]["$option"]))
	{
		if (preg_match('#^[a-z0-9_]+(\[([a-z0-9_]+)\])+$#si', trim($option), $matches))
		{
			// parse out array notation, to just get index
			$option = $matches[2];
		}

		$option = str_replace('[]', '', $option);
	}

	if (!$option)
	{
		if (!isset($helpcache["$script"]["$action"]))
		{
			return '';
		}
	}
	else
	{
		if (!isset($helpcache["$script"]["$action"]["$option"]))
		{
			if ($vbulletin->debug AND defined('DEV_EXTRA_CONTROLS') AND DEV_EXTRA_CONTROLS)
			{
				return construct_link_code('AddHelp', "help.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;option=" . urlencode($option) . '&amp;script=' . urlencode($script) . '&amp;scriptaction=' . urlencode($action));
			}
			else
			{
				return '';
			}
		}
	}

	$helplink = "js_open_help('" . urlencode($script) . "', '" . urlencode($action) . "', '" . urlencode($option) . "'); return false;";

	switch ($helptype)
	{
		case 1:
		return "<a class=\"helplink\" href=\"#\" onclick=\"$helplink\">$vbphrase[help] <img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/cp_help.gif\" alt=\"\" border=\"0\" title=\"$vbphrase[click_for_help_on_these_options]\" style=\"vertical-align:middle\" /></a>";

		default:
		return "<a class=\"helplink\" href=\"#\" onclick=\"$helplink\"><img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/cp_help.gif\" alt=\"\" border=\"0\" title=\"$vbphrase[click_for_help_on_this_option]\" /></a>";
	}
}

// #############################################################################
/**
* Returns a hyperlink
*
* @param	string	Hyperlink text
* @param	string	Hyperlink URL
* @param	boolean	If true, hyperlink target="_blank"
* @param	string	If specified, parameter will be used as title="x" tooltip for link
*
* @param	string
*/
function construct_link_code($text, $url, $newwin = false, $tooltip = '', $smallfont = false)
{
	if ($newwin === true OR $newwin === 1)
	{
		$newwin = '_blank';
	}

	return ($smallfont ? '<span class="smallfont">' : '') . " <a href=\"$url\"" . ($newwin ? " target=\"$newwin\"" : '') . (!empty($tooltip) ? " title=\"$tooltip\"" : '') . '>' . (vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl' ? "[$text&lrm;]</a>&rlm; " : "[$text]</a> ") . ($smallfont ? '</span>' : '');
}

// #############################################################################
/**
* Returns an <input type="button" /> that acts like a hyperlink
*
* @param	string	Value for button
* @param	string	Hyperlink URL; special cases 'submit' and 'reset'
* @param	boolean	If true, hyperlink will open in a new window
* @param	string	If specified, parameter will be used as title="x" tooltip for button
* @param	boolean	If true, the hyperlink URL parameter will be treated as a javascript function call instead
*
* @return	string
*/
function construct_button_code($text = 'Click!', $link = '', $newwindow = false, $tooltip = '', $jsfunction = 0)
{
	if (preg_match('#^(submit|reset),?(\w+)?$#siU', $link, $matches))
	{
		$name_attribute = ($matches[2] ? " name=\"$matches[2]\"" : '');
		return " <input type=\"$matches[1]\"$name_attribute class=\"button\" value=\"$text\" title=\"$tooltip\" tabindex=\"1\" />";
	}
	else
	{
		return " <input type=\"button\" class=\"button\" value=\"$text\" title=\"$tooltip\" tabindex=\"1\" onclick=\"" . iif($jsfunction, $link, iif($newwindow, "window.open('$link')", "window.location='$link'")) . ";\"$tooltip/> ";
	}
}

/**
* Checks whether or not the visiting user has administrative permissions
*
* This function can optionally take any number of parameters, each of which
* should be a particular administrative permission you want to check. For example:
* can_administer('canadminsettings', 'canadminstyles', 'canadminlanguages')
* If any one of these permissions is met, the function will return true.
*
* If no parameters are specified, the function will simply check that the user is an administrator.
*
* @return	boolean
*/
function can_administer()
{
	global $vbulletin, $_NAVPREFS;

	static $admin, $superadmins;

	if (!isset($_NAVPREFS))
	{
		$_NAVPREFS = preg_split('#,#', $vbulletin->userinfo['navprefs'], -1, PREG_SPLIT_NO_EMPTY);
	}

	if (!is_array($superadmins))
	{
		$superadmins = preg_split('#\s*,\s*#s', $vbulletin->config['SpecialUsers']['superadministrators'], -1, PREG_SPLIT_NO_EMPTY);
	}

	$do = func_get_args();

	if ($vbulletin->userinfo['userid'] < 1)
	{
		// user is a guest - definitely not an administrator
		return false;
	}
	else if (!($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
	{
		// user is not an administrator at all
		return false;
	}
	else if (in_array($vbulletin->userinfo['userid'], $superadmins))
	{
		// user is a super administrator (defined in config.php) so can do anything
		return true;
	}
	else if (empty($do))
	{
		// user is an administrator and we are not checking a specific permission
		return true;
	}
	else if (!isset($admin))
	{
		// query specific admin permissions from the administrator table and assign them to $adminperms
		$getperms = $vbulletin->db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "administrator
			WHERE userid = " . $vbulletin->userinfo['userid']
		);

		$admin = $getperms;

		// add normal adminpermissions and specific adminpermissions
		$adminperms = $getperms['adminpermissions'] + $vbulletin->userinfo['permissions']['adminpermissions'];

		// save nav prefs choices
		$_NAVPREFS = preg_split('#,#', $getperms['navprefs'], -1, PREG_SPLIT_NO_EMPTY);
	}

	// final bitfield check on each permission we are checking
	foreach($do AS $field)
	{
		if ($admin['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions["$field"])
		{
			return true;
		}
	}

	$return_value = false;

	($hook = vBulletinHook::fetch_hook('can_administer')) ? eval($hook) : false;

	// if we got this far then there is no permission, unless the hook says so
	return $return_value;
}

// #############################################################################
/**
* Halts execution and prints an error message stating that the administrator does not have permission to perform this action
*
* @param	string	This parameter is no longer used
*/
function print_cp_no_permission($do = '')
{
	global $vbulletin, $vbphrase;

	if (!defined('DONE_CPHEADER'))
	{
		print_cp_header($vbphrase['vbulletin_message']);
	}

	print_stop_message('no_access_to_admin_control', $vbulletin->session->vars['sessionurl'], $vbulletin->userinfo['userid']);

}

// #############################################################################
/**
* Saves data into the adminutil table in the database
*
* @param	string	Name of adminutil record to be saved
* @param	string	Data to be saved into the adminutil table
*
* @return	boolean
*/
function build_adminutil_text($title, $text = '')
{
	global $vbulletin;

	if ($text == '')
	{
		$vbulletin->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "adminutil
			WHERE title = '" . $vbulletin->db->escape_string($title) . "'
		");
	}
	else
	{
		/*insert query*/
		$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "adminutil
			(title, text)
			VALUES
			('" . $vbulletin->db->escape_string($title) . "', '" . $vbulletin->db->escape_string($text) . "')
		");
	}

	return true;
}

// #############################################################################
/**
* Returns data from the adminutil table in the database
*
* @param	string	Name of the adminutil record to be fetched
*
* @return	string
*/
function fetch_adminutil_text($title)
{
	global $vbulletin;

	$text = $vbulletin->db->query_first("SELECT text FROM " . TABLE_PREFIX . "adminutil WHERE title = '$title'");
	return $text['text'];
}

// #############################################################################
/**
* Halts execution and prints a Javascript redirect function to cause the browser to redirect to the specified page
*
* @param	string	Redirect target URL
* @param	float	Time delay (in seconds) before the redirect will occur
*/
function print_cp_redirect($gotopage, $timeout = 0)
{
	// performs a delayed javascript page redirection
	// get rid of &amp; if there are any...
	global $vbphrase;

	$gotopage = str_replace('&amp;', '&', $gotopage);
	$gotopage = create_full_url($gotopage);
	$gotopage = str_replace('"', '', $gotopage);

	if ($timeout == 0)
	{
		echo '<p align="center" class="smallfont"><a href="' . $gotopage . '">' . $vbphrase['processing_complete_proceed'] . '</a></p>';
		echo "\n<script type=\"text/javascript\">\n";
		echo "window.location=\"$gotopage\";";
		echo "\n</script>\n";
	}
	else
	{
		echo "\n<script type=\"text/javascript\">\n";
		echo "myvar = \"\"; timeout = " . ($timeout*10) . ";
		function exec_refresh()
		{
			window.status=\"" . $vbphrase['redirecting']."\"+myvar; myvar = myvar + \" .\";
			timerID = setTimeout(\"exec_refresh();\", 100);
			if (timeout > 0)
			{ timeout -= 1; }
			else { clearTimeout(timerID); window.status=\"\"; window.location=\"$gotopage\"; }
		}
		exec_refresh();";
		echo "\n</script>\n";
		echo '<p align="center" class="smallfont"><a href="' . $gotopage . '" onclick="javascript:clearTimeout(timerID);">' . $vbphrase['processing_complete_proceed'] . '</a></p>';
	}
	print_cp_footer();
	exit;
}

// #############################################################################
/**
* Prints a block of HTML containing a character that multiplies in width via javascript - a kind of progress meter
*
* @param	string	Text to be printed above the progress meter
* @param	string	Character to be used as the progress meter
* @param	string	Name to be given as the id for the HTML element containing the progress meter
*/
function print_dots_start($text, $dotschar = ':', $elementid = 'dotsarea')
{
	if (defined('NO_IMPORT_DOTS'))
	{
		return;
	}

	vbflush(); ?>
	<p align="center"><?php echo $text; ?><br /><br />[<span class="progress_dots" id="<?php echo $elementid; ?>"><?php echo $dotschar; ?></span>]</p>
	<script type="text/javascript"><!--
	function js_dots()
	{
		<?php echo $elementid; ?>.innerText = <?php echo $elementid; ?>.innerText + "<?php echo $dotschar; ?>";
		jstimer = setTimeout("js_dots();", 75);
	}
	if (document.all)
	{
		js_dots();
	}
	//--></script>
	<?php vbflush();
}

// #############################################################################
/**
* Prints a javascript code block that will halt the progress meter started with print_dots_start()
*/
function print_dots_stop()
{
	if (defined('NO_IMPORT_DOTS'))
	{
		return;
	}

	vbflush(); ?>
	<script type="text/javascript"><!--
	if (document.all)
	{
		clearTimeout(jstimer);
	}
	//--></script>
	<?php vbflush();
}

// #############################################################################
/**
* Deletes all private messages belonging to the specified user
*
* @param	integer	User ID
* @param	boolean	If true, update the user record in the database to reflect their new number of private messages
*
* @return	mixed	If messages are deleted, will return a string to be printed out detailing work done by this function
*/
function delete_user_pms($userid, $updateuser = true)
{
	global $vbulletin, $vbphrase;

	$userid = intval($userid);

	// array to store pm ids message ids
	$pms = array();
	// array to store the number of pmtext records used by this user
	$pmTextCount = array();
	// array to store the ids of any pmtext records that are used soley by this user
	$deleteTextIDs = array();
	// array to store results
	$out = array();

	// first zap all receipts belonging to this user
	$vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "pmreceipt WHERE userid = $userid");
	$out['receipts'] = $vbulletin->db->affected_rows();

	// now find all this user's private messages
	$messages = $vbulletin->db->query_read("
		SELECT pmid, pmtextid
		FROM " . TABLE_PREFIX . "pm
		WHERE userid = $userid
	");
	while ($message = $vbulletin->db->fetch_array($messages))
	{
		// stick this record into our $pms array
		$pms["$message[pmid]"] = $message['pmtextid'];
		// increment the number of PMs that use the current PMtext record
		$pmTextCount["$message[pmtextid]"] ++;
	}
	$vbulletin->db->free_result($messages);

	if (!empty($pms))
	{
		// zap all pm records belonging to this user
		$vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "pm WHERE userid = $userid");
		$out['pms'] = $vbulletin->db->affected_rows();
		$out['pmtexts'] = 0;

		// update the user record if necessary
		if ($updateuser AND $user = fetch_userinfo($userid))
		{
			$updateduser = true;
			$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
			$userdm->set_existing($user);
			$userdm->set('pmtotal', 0);
			$userdm->set('pmunread', 0);
			$userdm->set('pmpopup', 'IF(pmpopup=2, 1, pmpopup)', false);
			$userdm->save();
			unset($userdm);
		}
	}
	else
	{
		$out['pms'] = 0;
		$out['pmtexts'] = 0;
	}

	// in case the totals have been corrupted somehow
	if (!isset($updateduser) AND $updateuser AND $user = fetch_userinfo($userid))
	{
		$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
		$userdm->set_existing($user);
		$userdm->set('pmtotal', 0);
		$userdm->set('pmunread', 0);
		$userdm->set('pmpopup', 'IF(pmpopup=2, 1, pmpopup)', false);
		$userdm->save();
		unset($userdm);
	}

	foreach ($out AS $k => $v)
	{
		$out["$k"] = vb_number_format($v);
	}

	return $out;
}

// #############################################################################
/**
* Writes data to a file
*
* @param	string	Path to file (including file name)
* @param	string	Data to be saved into the file
* @param	boolean	If true, will create a backup of the file called {filename}old
*/
function file_write($path, $data, $backup = false)
{
	if (file_exists($path) != false)
	{
		if ($backup)
		{
			$filenamenew = $path . 'old';
			rename($path, $filenamenew);
		}
		else
		{
			unlink($path);
		}
	}
	if ($data != '')
	{
		$filenum = fopen($path, 'w');
		fwrite($filenum, $data);
		fclose($filenum);
	}
}

// #############################################################################
/**
* Returns the contents of a file
*
* @param	string	Path to file (including file name)
*
* @return	string	If file does not exist, returns an empty string
*/
function file_read($path)
{
	// On some versions of PHP under IIS, file_exists returns false for uploaded files,
	// even though the file exists and is readable. http://bugs.php.net/bug.php?id=38308
	if(!file_exists($path) AND !is_uploaded_file($path))
	{
		return '';
	}
	else
	{
		$filestuff = @file_get_contents($path);
		return $filestuff;
	}
}

// #############################################################################
/**
* Reads settings from the settings then saves the values to the datastore
*
* After reading the contents of the setting table, the function will rebuild
* the $vbulletin->options array, then serialize the array and save that serialized
* array into the 'options' entry of the datastore in the database
*
* @return	array	The $vbulletin->options array
*/
function build_options()
{
	require_once(DIR . '/includes/adminfunctions_options.php');

	global $vbulletin;

	$vbulletin->options = array();

	$settings = $vbulletin->db->query_read("SELECT varname, value, datatype FROM " . TABLE_PREFIX . "setting");
	while ($setting = $vbulletin->db->fetch_array($settings))
	{
		$vbulletin->options["$setting[varname]"] = validate_setting_value($setting['value'], $setting['datatype'], true, false);
	}

	if (substr($vbulletin->options['cookiepath'], -1, 1) != '/')
	{
		$vbulletin->options['cookiepath'] .= '/';
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "setting
			SET value = '" . $vbulletin->db->escape_string($vbulletin->options['cookiepath']) . "'
			WHERE varname = 'cookiepath'
		");
	}

	build_datastore('options', serialize($vbulletin->options), 1);

	return $vbulletin->options;
}

// #############################################################################
/**
* Saves a log into the adminlog table in the database
*
* @param	string	Extra info to be saved
* @param	integer	User ID of the visiting user
* @param	string	Name of the script this log applies to
* @param	string	Action / Do branch being viewed
*/
function log_admin_action($extrainfo = '', $userid = -1, $script = '', $scriptaction = '')
{
	// logs current activity to the adminlog db table
	global $vbulletin;

	if ($userid == -1)
	{
		$userid = $vbulletin->userinfo['userid'];
	}
	if (empty($script))
	{
		$script = !empty($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : basename($_SERVER['PHP_SELF']);
	}
	if (empty($scriptaction))
	{
		$scriptaction = $_REQUEST['do'];
	}

	$vbulletin->db->shutdown_query("
		INSERT INTO " . TABLE_PREFIX . "adminlog(userid, dateline, script, action, extrainfo, ipaddress)
		VALUES
		($userid, " . TIMENOW.", '" . $vbulletin->db->escape_string($script) . "', '" . $vbulletin->db->escape_string($scriptaction) . "', '" . $vbulletin->db->escape_string($extrainfo) ."', '" . IPADDRESS . "')
	");

}

// #############################################################################
/**
* Checks whether or not the visiting user can view logs
*
* @param	string	Comma-separated list of user IDs permitted to view logs
* @param	boolean	Variable to return if the previous parameter is found to be empty
* @param	string	Message to print if the user is NOT permitted to view
*
* @return	boolean
*/
function can_access_logs($idvar, $defaultreturnvar = false, $errmsg = '')
{
	// checks a single integer or a comma-separated list for $vbulletin->userinfo[userid]
	global $vbulletin;

	if (empty($idvar))
	{
		return $defaultreturnvar;
	}
	else
	{
		$perm = trim($idvar);
		$logperms = explode(',', $perm);
		if (in_array($vbulletin->userinfo['userid'], $logperms))
		{
			return true;
		}
		else
		{
			echo $errmsg;
			return false;
		}
	}
}

// #############################################################################
/**
* Prints a dialog box asking if the user is sure they want to delete the specified item from the database
*
* @param	string	Name of table from which item will be deleted
* @param	mixed		ID of item to be deleted
* @param	string	PHP script to which the form will submit
* @param	string	'do' action for target script
* @param	string	Word describing item to be deleted - eg: 'forum' or 'user' or 'post' etc.
* @param	mixed		If not empty, an array containing name=>value pairs to be used as hidden input fields
* @param	string	Extra text to be printed in the dialog box
* @param	string	Name of 'title' field in the table in the database
* @param	string	Name of 'idfield' field in the table in the database
*/
function print_delete_confirmation($table, $itemid, $phpscript, $do, $itemname = '', $hiddenfields = 0, $extra = '', $titlename = 'title', $idfield = '')
{
	global $vbulletin, $vbphrase;

	$idfield = $idfield ? $idfield : $table . 'id';
	$itemname = $itemname ? $itemname : $table;
	$deleteword = 'delete';
	$encodehtml = true;

	switch($table)
	{
		case 'infraction':
			$item = $vbulletin->db->query_first("
				SELECT infractionid, infractionid AS title
				FROM " . TABLE_PREFIX . "infraction
				WHERE infractionid = $itemid
			");
			break;
		case 'reputation':
			$item = $vbulletin->db->query_first("
				SELECT reputationid, reputationid AS title
				FROM " . TABLE_PREFIX . "reputation
				WHERE reputationid = $itemid
			");
			break;
		case 'user':
			$item = $vbulletin->db->query_first("
				SELECT userid, username AS title
				FROM " . TABLE_PREFIX . "user
				WHERE userid = $itemid
			");
			break;
		case 'moderator':
			$item = $vbulletin->db->query_first("
				SELECT moderatorid, username, title
				FROM " . TABLE_PREFIX . "moderator AS moderator,
				" . TABLE_PREFIX . "user AS user,
				" . TABLE_PREFIX . "forum AS forum
				WHERE user.userid = moderator.userid AND
				forum.forumid = moderator.forumid AND
				moderatorid = $itemid
			");
			$item['title'] = construct_phrase($vbphrase['x_from_the_forum_y'], $item['username'], $item['title']);
			$encodehtml = false;
			break;
		case 'calendarmoderator':
			$item = $vbulletin->db->query_first("
				SELECT calendarmoderatorid, username, title
				FROM " . TABLE_PREFIX . "calendarmoderator AS calendarmoderator,
				" . TABLE_PREFIX . "user AS user,
				" . TABLE_PREFIX . "calendar AS calendar
				WHERE user.userid = calendarmoderator.userid AND
				calendar.calendarid = calendarmoderator.calendarid AND
				calendarmoderatorid = $itemid
			");
			$item['title'] = construct_phrase($vbphrase['x_from_the_calendar_y'], $item['username'], $item['title']);
			$encodehtml = false;
			break;
		case 'phrase':
			$item = $vbulletin->db->query_first("
				SELECT phraseid, varname AS title
				FROM " . TABLE_PREFIX . "phrase
				WHERE phraseid = $itemid
			");
			break;
		case 'userpromotion':
			$item = $vbulletin->db->query_first("
				SELECT userpromotionid, usergroup.title
				FROM " . TABLE_PREFIX . "userpromotion AS userpromotion,
				" . TABLE_PREFIX . "usergroup AS usergroup
				WHERE userpromotionid = $itemid AND
				userpromotion.usergroupid = usergroup.usergroupid
			");
			break;
		case 'usergroupleader':
			$item = $vbulletin->db->query_first("
				SELECT usergroupleaderid, username AS title
				FROM " . TABLE_PREFIX . "usergroupleader AS usergroupleader
				INNER JOIN " . TABLE_PREFIX . "user AS user USING (userid)
				WHERE usergroupleaderid = $itemid
			");
			break;
		case 'setting':
			$item = $vbulletin->db->query_first("
				SELECT varname AS title
				FROM " . TABLE_PREFIX . "setting
				WHERE varname = '" . $vbulletin->db->escape_string($itemid) . "'
			");
			$idfield = 'title';
			break;
		case 'settinggroup':
			$item = $vbulletin->db->query_first("
				SELECT grouptitle AS title
				FROM " . TABLE_PREFIX . "settinggroup
				WHERE grouptitle = '" . $vbulletin->db->escape_string($itemid) . "'
			");
			$idfield = 'title';
			break;
		case 'adminhelp':
			$item = $vbulletin->db->query_first("
				SELECT adminhelpid, phrase.text AS title
				FROM " . TABLE_PREFIX . "adminhelp AS adminhelp
				LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase ON (phrase.varname = CONCAT(adminhelp.script, IF(adminhelp.action != '', CONCAT('_', REPLACE(adminhelp.action, ',', '_')), ''), IF(adminhelp.optionname != '', CONCAT('_', adminhelp.optionname), ''), '_title') AND phrase.fieldname = 'cphelptext' AND phrase.languageid IN (-1, 0))
				WHERE adminhelpid = $itemid
			");
			break;
		case 'faq':
			$item = $vbulletin->db->query_first("
				SELECT faqname, IF(phrase.text IS NOT NULL, phrase.text, faq.faqname) AS title
				FROM " . TABLE_PREFIX . "faq AS faq
				LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase ON (phrase.varname = faq.faqname AND phrase.fieldname = 'faqtitle' AND phrase.languageid IN(-1, 0))
				WHERE faqname = '" . $vbulletin->db->escape_string($itemid) . "'
			");
			$idfield = 'faqname';
			break;
		case 'product':
			$item = $vbulletin->db->query_first("
				SELECT productid, title
				FROM " . TABLE_PREFIX . "product
				WHERE productid = '" . $vbulletin->db->escape_string($itemid) . "'
			");
			break;
		case 'prefix':
			$item = $vbulletin->db->query_first("
				SELECT prefixid
				FROM " . TABLE_PREFIX . "prefix
				WHERE prefixid = '" . $vbulletin->db->escape_string($itemid) . "'
			");
			$item['title'] = $vbphrase["prefix_$item[prefixid]_title_plain"];
			break;
		case 'prefixset':
			$item = $vbulletin->db->query_first("
				SELECT prefixsetid
				FROM " . TABLE_PREFIX . "prefixset
				WHERE prefixsetid = '" . $vbulletin->db->escape_string($itemid) . "'
			");
			$item['title'] = $vbphrase["prefixset_$item[prefixsetid]_title"];
			break;
		case 'stylevar':
			$item = $vbulletin->db->query_first("
				SELECT stylevarid
				FROM " . TABLE_PREFIX . "stylevar
				WHERE stylevarid = '" . $vbulletin->db->escape_string($itemid) . "'
			");
			break;
		case 'navigation':
			$item = $vbulletin->db->query_first("
				SELECT navid, navtype, name
				FROM " . TABLE_PREFIX . "navigation
				WHERE navid = $itemid
			");
			break;
		default:
			$handled = false;
			($hook = vBulletinHook::fetch_hook('admin_delete_confirmation')) ? eval($hook) : false;
			if (!$handled)
			{
				$item = $vbulletin->db->query_first("
					SELECT $idfield, $titlename AS title
					FROM " . TABLE_PREFIX . "$table
					WHERE $idfield = $itemid
				");
			}
			break;
	}

	switch($table)
	{
		case 'template':
			if ($itemname == 'replacement_variable')
			{
				$deleteword = 'delete';
			}
			else
			{
				$deleteword = 'revert';
			}
		break;

		case 'adminreminder':
			if (vbstrlen($item['title']) > 30)
			{
				$item['title'] = substr($item['title'], 0, 30) . '...';
			}
		break;

		case 'subscription':
			$item['title'] = $vbphrase['sub' . $item['subscriptionid'] . '_title'];
		break;

		case 'stylevar':
			$item['title'] = $vbphrase['stylevar' . $item['stylevarid'] . $titlename . '_name'];

			//Friendly names not
			if (!$item['title'])
			{
				$item['title'] = $item["$idfield"];
			}

			$deleteword = 'revert';
		break;

		case 'navigation':
			$phrasename = 'vb_navigation_' . $item['navtype'] . '_' . $item['name'] . '_text';
			$item['title'] = $vbphrase[$phrasename] ? $vbphrase[$phrasename] : $item['name'];
		break;

	}

	if ($encodehtml
		AND (strcspn($item['title'], '<>"') < strlen($item['title'])
			OR (strpos($item['title'], '&') !== false AND !preg_match('/&(#[0-9]+|amp|lt|gt|quot);/si', $item['title']))
		)
	)
	{
		// title contains html entities that should be encoded
		$item['title'] = htmlspecialchars_uni($item['title']);
	}

	if ($item["$idfield"] == $itemid AND !empty($itemid))
	{
		echo "<p>&nbsp;</p><p>&nbsp;</p>";
		print_form_header($phpscript, $do, 0, 1, '', '75%');
		construct_hidden_code(($idfield == 'styleid' OR $idfield == 'languageid') ? 'do' . $idfield : $idfield, $itemid);
		if (is_array($hiddenfields))
		{
			foreach($hiddenfields AS $varname => $value)
			{
				construct_hidden_code($varname, $value);
			}
		}

		print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], $item['title']));
		print_description_row("
			<blockquote><br />
			" . construct_phrase($vbphrase["are_you_sure_want_to_{$deleteword}_{$itemname}_x"], $item['title'],
				$idfield, $item["$idfield"], iif($extra, "$extra<br /><br />")) . "
			<br /></blockquote>\n\t");
		print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);
	}
	else
	{
		print_stop_message('could_not_find', '<b>' . $itemname . '</b>', $idfield, $itemid);
	}
}

// #############################################################################
/**
* Prints a dialog box asking if the user if they want to continue
*
* @param	string	Phrase that is presented to the user
* @param	string	PHP script to which the form will submit
* @param	string	'do' action for target script
* @param	mixed		If not empty, an array containing name=>value pairs to be used as hidden input fields
*/
function print_confirmation($phrase, $phpscript, $do, $hiddenfields = array())
{
	global $vbulletin, $vbphrase;

	echo "<p>&nbsp;</p><p>&nbsp;</p>";
	print_form_header($phpscript, $do, 0, 1, '', '75%');
	if (is_array($hiddenfields))
	{
		foreach($hiddenfields AS $varname => $value)
		{
			construct_hidden_code($varname, $value);
		}
	}
	print_table_header($vbphrase['confirm_action']);
	print_description_row("
		<blockquote><br />
		$phrase
		<br /></blockquote>\n\t");
	print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);

}

// #############################################################################
/**
* Halts execution and shows a message based upon a parsed phrase
*
* After the first parameter, this function can take any number of additional
* parameters, in order to replace {1}, {2}, {3},... {n} variable place holders
* within the given phrase text. The parsed phrase is then passed to print_cp_message()
*
* Note that a redirect can be performed if CP_REDIRECT is defined with a URL
*
* @param	string	Name of phrase (from the Error phrase group)
* @param	string	1st variable replacement {1}
* @param	string	2nd variable replacement {2}
* @param	string	Nth variable replacement {n}
*/
function print_stop_message($phrasename)
{
	global $vbulletin, $vbphrase;

	if (!function_exists('fetch_phrase'))
	{
		require_once(DIR . '/includes/functions_misc.php');
	}

	$message = fetch_phrase($phrasename, 'error', '', false);

	$args = func_get_args();
	if (sizeof($args) > 1)
	{
		$args[0] = $message;
		$message = call_user_func_array('construct_phrase', $args);
	}

	if (defined('CP_CONTINUE'))
	{
		define('CP_REDIRECT', CP_CONTINUE);
	}

	if ($vbulletin->GPC['ajax'])
	{
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_tag('error', $message);
		$xml->print_xml();
	}

	if (VB_AREA == 'Upgrade')
	{
		echo $message;
		exit;
	}

	print_cp_message(
		$message,
		defined('CP_REDIRECT') ? CP_REDIRECT : NULL,
		1,
		defined('CP_BACKURL') ? CP_BACKURL : NULL,
		defined('CP_CONTINUE') ? true : false
	);
}

// #############################################################################
/**
* Halts execution and shows the specified message
*
* @param	string	Message to display
* @param	mixed	If specified, a redirect will be performed to the URL in this parameter
* @param	integer	If redirect is specified, this is the time in seconds to delay before redirect
* @param	string	If specified, will provide a specific URL for "Go Back". If empty, no button will be displayed!
* @param bool		If true along with redirect, 'CONTINUE' button will be used instead of automatic redirect
*/
function print_cp_message($text = '', $redirect = NULL, $delay = 1, $backurl = NULL, $continue = false)
{
	global $vbulletin, $vbphrase;

	if ($vbulletin->GPC['ajax'])
	{
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_tag('error', $text);
		$xml->print_xml();
		exit;
	}

	if ($redirect AND $vbulletin->session->vars['sessionurl'])
	{
		if (strpos($redirect, '?') === false)
		{
			$redirect .= '?';
		}
		$redirect .= '&' . $vbulletin->session->vars['sessionurl'];
	}

	if (!defined('DONE_CPHEADER'))
	{
		print_cp_header($vbphrase['vbulletin_message']);
	}

	echo '<p>&nbsp;</p><p>&nbsp;</p>';
	print_form_header('', '', 0, 1, 'messageform', '65%');
	print_table_header($vbphrase['vbulletin_message']);
	print_description_row("<blockquote><br />$text<br /><br /></blockquote>");

	if ($redirect AND $redirect !== NULL)
	{
		// redirect to the new page
		if ($continue)
		{
			$continueurl = str_replace('&amp;', '&', $redirect);
			print_table_footer(2, construct_button_code($vbphrase['continue'], create_full_url($continueurl)));
		}
		else
		{
			print_table_footer();

			$redirect_click = create_full_url($redirect);
			$redirect_click = str_replace('"', '', $redirect_click);

			echo '<p align="center" class="smallfont">' . construct_phrase($vbphrase['if_you_are_not_automatically_redirected_click_here_x'], $redirect_click) . "</p>\n";
			print_cp_redirect($redirect, $delay);
		}
	}
	else
	{
		// end the table and halt
		if ($backurl === NULL)
		{
			$backurl = 'javascript:history.back(1)';
		}

		if (strpos($backurl, 'history.back(') !== false)
		{
			//if we are attempting to run a history.back(1), check we have a history to go back to, otherwise attempt to close the window.
			$back_button = '&nbsp;
				<input type="button" id="backbutton" class="button" value="' . $vbphrase['go_back'] . '" title="" tabindex="1" onclick="if (history.length) { history.back(1); } else { self.close(); }"/>
				&nbsp;
				<script type="text/javascript">
				<!--
				if (history.length < 1 || ((is_saf || is_moz) && history.length <= 1)) // safari + gecko start at 1
				{
					document.getElementById("backbutton").parentNode.removeChild(document.getElementById("backbutton"));
				}
				//-->
				</script>';

			// remove the back button if it leads back to the login redirect page
			if (strpos($vbulletin->url, 'login.php?do=login') !== false)
			{
				$back_button = '';
			}
		}
		else if ($backurl !== '')
		{
			// regular window.location=url call
			$backurl = create_full_url($backurl);
			$backurl = str_replace(array('"', "'"), '', $backurl);
			$back_button = '<input type="button" class="button" value="' . $vbphrase['go_back'] . '" title="" tabindex="1" onclick="window.location=\'' . $backurl . '\';"/>';
		}
		else
		{
			$back_button = '';
		}

		print_table_footer(2, $back_button);
	}

	// and now terminate the script
	print_cp_footer();
}

/**
* Verifies the CP sessionhash is sent through with the request to prevent
* an XSS-style issue.
*
* @param	boolean	Whether to halt if an error occurs
* @param	string	Name of the input variable to look at
*
* @return	boolean	True on success, false on failure
*/
function verify_cp_sessionhash($halt = true, $input = 'hash')
{
	global $vbulletin;

	assert_cp_sessionhash();

	if (!isset($vbulletin->GPC["$input"]))
	{
		$vbulletin->input->clean_array_gpc('r', array(
			$input => TYPE_STR
		));
	}

	if ($vbulletin->GPC["$input"] != CP_SESSIONHASH)
	{
		if ($halt)
		{
			print_stop_message('security_alert_hash_mismatch');
		}
		else
		{
			return false;
		}
	}

	return true;
}

/**
 * Defines a valid CP_SESSIONHASH.
 */
function assert_cp_sessionhash()
{
	if (defined('CP_SESSIONHASH'))
	{
		return;
	}

	global $vbulletin;

	$cpsession = array();

	$vbulletin->input->clean_array_gpc('c', array(
		COOKIE_PREFIX . 'cpsession' => TYPE_STR,
	));

	if (!empty($vbulletin->GPC[COOKIE_PREFIX . 'cpsession']))
	{
		$cpsession = $vbulletin->db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "cpsession
			WHERE userid = " . $vbulletin->userinfo['userid'] . "
				AND hash = '" . $vbulletin->db->escape_string($vbulletin->GPC[COOKIE_PREFIX . 'cpsession']) . "'
				AND dateline > " . iif($vbulletin->options['timeoutcontrolpanel'], intval(TIMENOW - $vbulletin->options['cookietimeout']), intval(TIMENOW - 3600))
		);

		if (!empty($cpsession))
		{
			$vbulletin->db->query_write("
				UPDATE LOW_PRIORITY " . TABLE_PREFIX . "cpsession
				SET dateline = " . TIMENOW . "
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND hash = '" . $vbulletin->db->escape_string($vbulletin->GPC[COOKIE_PREFIX . 'cpsession']) . "'
			");
		}
	}

	define('CP_SESSIONHASH', $cpsession['hash']);
}

// #############################################################################
/**
* Returns an array of timezones, keyed with their offset from GMT
*
* @return	array	Timezones array
*/
function fetch_timezones_array()
{
	global $vbphrase;

	return array(
		'-12'  => $vbphrase['timezone_gmt_minus_1200'],
		'-11'  => $vbphrase['timezone_gmt_minus_1100'],
		'-10'  => $vbphrase['timezone_gmt_minus_1000'],
		'-9'   => $vbphrase['timezone_gmt_minus_0900'],
		'-8'   => $vbphrase['timezone_gmt_minus_0800'],
		'-7'   => $vbphrase['timezone_gmt_minus_0700'],
		'-6'   => $vbphrase['timezone_gmt_minus_0600'],
		'-5'   => $vbphrase['timezone_gmt_minus_0500'],
		'-4.5' => $vbphrase['timezone_gmt_minus_0430'],
		'-4'   => $vbphrase['timezone_gmt_minus_0400'],
		'-3.5' => $vbphrase['timezone_gmt_minus_0330'],
		'-3'   => $vbphrase['timezone_gmt_minus_0300'],
		'-2'   => $vbphrase['timezone_gmt_minus_0200'],
		'-1'   => $vbphrase['timezone_gmt_minus_0100'],
		'0'    => $vbphrase['timezone_gmt_plus_0000'],
		'1'    => $vbphrase['timezone_gmt_plus_0100'],
		'2'    => $vbphrase['timezone_gmt_plus_0200'],
		'3'    => $vbphrase['timezone_gmt_plus_0300'],
		'3.5'  => $vbphrase['timezone_gmt_plus_0330'],
		'4'    => $vbphrase['timezone_gmt_plus_0400'],
		'4.5'  => $vbphrase['timezone_gmt_plus_0430'],
		'5'    => $vbphrase['timezone_gmt_plus_0500'],
		'5.5'  => $vbphrase['timezone_gmt_plus_0530'],
		'5.75' => $vbphrase['timezone_gmt_plus_0545'],
		'6'    => $vbphrase['timezone_gmt_plus_0600'],
		'6.5'  => $vbphrase['timezone_gmt_plus_0630'],
		'7'    => $vbphrase['timezone_gmt_plus_0700'],
		'8'    => $vbphrase['timezone_gmt_plus_0800'],
		'9'    => $vbphrase['timezone_gmt_plus_0900'],
		'9.5'  => $vbphrase['timezone_gmt_plus_0930'],
		'10'   => $vbphrase['timezone_gmt_plus_1000'],
		'11'   => $vbphrase['timezone_gmt_plus_1100'],
		'12'   => $vbphrase['timezone_gmt_plus_1200']
	);
}

// #############################################################################
/**
* Reads all data from the specified image table and writes the serialized data to the datastore
*
* @param	string	Name of image table (avatar/icon/smilie)
*/
function build_image_cache($table)
{
	global $vbulletin;

	if ($table == 'avatar')
	{
		return;
	}

	DEVDEBUG("Updating $table cache template...");

	$itemid = $table.'id';
	if ($table == 'smilie')
	{
		// the smilie cache is basically only used for parsing; displaying smilies comes from a query
		$items = $vbulletin->db->query_read("
			SELECT *, LENGTH(smilietext) AS smilielen
			FROM " . TABLE_PREFIX . "$table
			WHERE LENGTH(TRIM(smilietext)) > 0
			ORDER BY smilielen DESC
		");
	}
	else
	{
		$items = $vbulletin->db->query_read("SELECT * FROM " . TABLE_PREFIX . "$table ORDER BY imagecategoryid, displayorder");
	}

	$itemarray = array();

	while ($item = $vbulletin->db->fetch_array($items))
	{
		$itemarray["$item[$itemid]"] = array();
		foreach ($item AS $field => $value)
		{
			if (!is_numeric($field))
			{
				$itemarray["$item[$itemid]"]["$field"] = $value;
			}
		}
	}

	build_datastore($table . 'cache', serialize($itemarray), 1);

	if ($table == 'smilie')
	{
		$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "postparsed"); // smilies changed, so posts could parse differently
		if (is_newer_version($vbulletin->options['templateversion'], '3.6', true))
		{
			$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "sigparsed");
		}
	}

	($hook = vBulletinHook::fetch_hook('admin_cache_smilies')) ? eval($hook) : false;
}

// #############################################################################
/**
* Reads all data from the bbcode table and writes the serialized data to the datastore
*/
function build_bbcode_cache()
{
	global $vbulletin;
	DEVDEBUG("Updating bbcode cache template...");
	$bbcodes = $vbulletin->db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "bbcode
	");
	$bbcodearray = array();
	while ($bbcode = $vbulletin->db->fetch_array($bbcodes))
	{
		$bbcodearray["$bbcode[bbcodeid]"] = array();
		foreach ($bbcode AS $field => $value)
		{
			if (!is_numeric($field))
			{
				$bbcodearray["$bbcode[bbcodeid]"]["$field"] = $value;

			}
		}

		$bbcodearray["$bbcode[bbcodeid]"]['strip_empty'] = (intval($bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['strip_empty']) ? 1 : 0 ;
		$bbcodearray["$bbcode[bbcodeid]"]['stop_parse'] = (intval($bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['stop_parse']) ? 1 : 0 ;
		$bbcodearray["$bbcode[bbcodeid]"]['disable_smilies'] = (intval($bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['disable_smilies']) ? 1 : 0 ;
		$bbcodearray["$bbcode[bbcodeid]"]['disable_wordwrap'] = (intval($bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['disable_wordwrap']) ? 1 : 0 ;
	}

	build_datastore('bbcodecache', serialize($bbcodearray), 1);

	$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "postparsed"); // bbcodes changed, so posts could parse differently
	if (is_newer_version($vbulletin->options['templateversion'], '3.6', true))
	{
		$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "sigparsed");
	}

	($hook = vBulletinHook::fetch_hook('admin_cache_bbcode')) ? eval($hook) : false;
}

// #############################################################################
/**
* Prints a <script> block that allows you to call js_open_phrase_ref() from Javascript
*
* @param	integer	ID of initial language to be displayed
* @param	integer	ID of initial phrasetype to be displayed
* @param	integer	Pixel width of popup window
* @param	integer	Pixel height of popup window
*/
function print_phrase_ref_popup_javascript($languageid = 0, $fieldname = '', $width = 700, $height = 202)
{
	global $vbulletin;

	$q =  iif($languageid, "&languageid=$languageid", '');
	$q .= iif($$fieldname, "&fieldname=$fieldname", '');

	echo "<script type=\"text/javascript\">\n<!--
	function js_open_phrase_ref(languageid,fieldname)
	{
		var qs = '';
		if (languageid != 0) qs += '&languageid=' + languageid;
		if (fieldname != '') qs += '&fieldname=' + fieldname;
		window.open('phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=quickref' + qs, 'quickref', 'width=$width,height=$height,resizable=yes');
	}\n// -->\n</script>\n";
}

// #############################################################################
/**
* Rebuilds the $vbulletin->usergroupcache and $vbulletin->forumcache from the forum/usergroup tables
*
* @param	boolean	If true, force a recalculation of the forum parent and child lists
*/
function build_forum_permissions($rebuild_genealogy = true)
{
	global $vbulletin, $fpermcache;

	#echo "<h1>updateForumPermissions</h1>";

	$grouppermissions = array();
	$fpermcache = array();
	$vbulletin->forumcache = array();
	$vbulletin->usergroupcache = array();

	// query usergroups
	$usergroups = $vbulletin->db->query_read("SELECT * FROM " . TABLE_PREFIX . "usergroup ORDER BY title");
	while ($usergroup = $vbulletin->db->fetch_array($usergroups))
	{
		foreach ($usergroup AS $key => $val)
		{
			if (is_numeric($val))
			{
				$usergroup["$key"] += 0;
			}
		}
		$vbulletin->usergroupcache["$usergroup[usergroupid]"] = $usergroup;
		// Profile pics disabled so don't inherit any of the profile pic settings
		if (!($vbulletin->usergroupcache["$usergroup[usergroupid]"]['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canprofilepic']))
		{
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['profilepicmaxwidth'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['profilepicmaxheight'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['profilepicmaxsize'] = -1;
		}
		// Avatars disabled so don't inherit any of the avatar settings
		if (!($vbulletin->usergroupcache["$usergroup[usergroupid]"]['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar']))
		{
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['avatarmaxwidth'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['avatarmaxheight'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['avatarmaxsize'] = -1;
		}
		// Signature pics or signatures are disabled so don't inherit any of the signature pic settings
		if (!($vbulletin->usergroupcache["$usergroup[usergroupid]"]['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic']) OR !($vbulletin->usergroupcache["$usergroup[usergroupid]"]['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
		{
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigpicmaxwidth'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigpicmaxheight'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigpicmaxsize'] = -1;
		}

		// Signatures are disabled so don't inherit any of the signature settings
		if (!($vbulletin->usergroupcache["$usergroup[usergroupid]"]['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature']))
		{
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaxrawchars'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaxchars'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaxlines'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaxsizebbcode'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaximages'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['sigmaxvideos'] = -1;
			$vbulletin->usergroupcache["$usergroup[usergroupid]"]['signaturepermissions'] = 0;
		}

		($hook = vBulletinHook::fetch_hook('admin_build_forum_perms_group')) ? eval($hook) : false;

		$grouppermissions["$usergroup[usergroupid]"] = $usergroup['forumpermissions'];
	}
	unset($usergroup);
	$vbulletin->db->free_result($usergroups);
	DEVDEBUG('updateForumCache( ) - Queried Usergroups');

	$vbulletin->forumcache = array();
	$vbulletin->iforumcache = array();
	$forumdata = array();

	// get the vbulletin->iforumcache so we can traverse the forums in order within cache_forum_permissions
	$newforumcache = $vbulletin->db->query_read("
		SELECT forum.*" . ((VB_AREA != 'Upgrade' AND VB_AREA != 'Install') ? ", NOT ISNULL(podcast.forumid) AS podcast" : "") . "
		FROM " . TABLE_PREFIX . "forum AS forum
		" . ((VB_AREA != 'Upgrade' AND VB_AREA != 'Install') ? "LEFT JOIN " . TABLE_PREFIX . "podcast AS podcast ON (forum.forumid = podcast.forumid AND podcast.enabled = 1)" : "") . "
		ORDER BY displayorder
	");
	while ($newforum = $vbulletin->db->fetch_array($newforumcache))
	{
		foreach ($newforum AS $key => $val)
		{
			/* values which begin with 0 and are greater than 1 character are strings, since 01 would be an octal number in PHP */
			if (is_numeric($val) AND !(substr($val, 0, 1) == '0' AND strlen($val) > 1) AND !in_array($key, array('title', 'title_clean', 'description', 'description_clean')))
			{
				$newforum["$key"] += 0;
			}
		}
		$vbulletin->iforumcache["$newforum[parentid]"]["$newforum[forumid]"] = $newforum['forumid'];
		$forumdata["$newforum[forumid]"] = $newforum;
	}
	$vbulletin->db->free_result($newforumcache);

	// get the forumcache into the order specified in $vbulletin->iforumcache
	$vbulletin->forumorder = array();
	fetch_forum_order();
	foreach ($vbulletin->forumorder AS $forumid => $depth)
	{
		$vbulletin->forumcache["$forumid"] =& $forumdata["$forumid"];
		$vbulletin->forumcache["$forumid"]['depth'] = $depth;
	}
	unset($vbulletin->forumorder);

	// rebuild forum parent/child lists
	if ($rebuild_genealogy)
	{
		build_forum_genealogy();
	}

	// query forum permissions
	$fperms = $vbulletin->db->query_read("SELECT * FROM " . TABLE_PREFIX . "forumpermission");
	while ($fperm = $vbulletin->db->fetch_array($fperms))
	{
		$fpermcache["$fperm[forumid]"]["$fperm[usergroupid]"] = intval($fperm['forumpermissions']);

		($hook = vBulletinHook::fetch_hook('admin_build_forum_perms_forum')) ? eval($hook) : false;
	}
	unset($fperm);
	$vbulletin->db->free_result($fperms);
	DEVDEBUG('updateForumCache( ) - Queried Forum Pemissions');

	// call the function that will work out the forum permissions
	cache_forum_permissions($grouppermissions);

	// finally replace the existing cache templates
	build_datastore('usergroupcache', serialize($vbulletin->usergroupcache), 1);
	foreach(array_keys($vbulletin->forumcache) AS $forumid)
	{
		unset(
			$vbulletin->forumcache["$forumid"]['replycount'],
			$vbulletin->forumcache["$forumid"]['lastpost'],
			$vbulletin->forumcache["$forumid"]['lastposter'],
			$vbulletin->forumcache["$forumid"]['lastposterid'],
			$vbulletin->forumcache["$forumid"]['lastthread'],
			$vbulletin->forumcache["$forumid"]['lastthreadid'],
			$vbulletin->forumcache["$forumid"]['lasticonid'],
			$vbulletin->forumcache["$forumid"]['lastprefixid'],
			$vbulletin->forumcache["$forumid"]['threadcount']
		);
	}
	build_datastore('forumcache', serialize($vbulletin->forumcache), 1);

	DEVDEBUG('updateForumCache( ) - Updated caches, ' . $vbulletin->db->affected_rows() . ' rows affected.');
}

// #############################################################################
/**
* Recursive function to build $vbulletin->forumorder - used to get the order of forums
*
* @param	integer	Initial parent forum ID to use
* @param	integer	Initial depth of forums
*/
function fetch_forum_order($parentid = -1, $depth = 0)
{
	global $vbulletin;

	if (is_array($vbulletin->iforumcache["$parentid"]))
	{
		foreach ($vbulletin->iforumcache["$parentid"] AS $forumid)
		{
			$vbulletin->forumorder["$forumid"] = $depth;
			fetch_forum_order($forumid, $depth + 1);
		}
	}
}

// #############################################################################
/**
* Recalculates forum parent and child lists, then saves them back to the forum table
*/
function build_forum_genealogy()
{
	global $vbulletin;

	if (empty($vbulletin->forumcache))
	{
		return;
	}

	// build parent/child lists
	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		// parent list
		$i = 0;
		$curid = $forumid;

		$vbulletin->forumcache["$forumid"]['parentlist'] = '';

		while ($curid != -1 AND $i++ < 1000)
		{
			if ($curid)
			{
				$vbulletin->forumcache["$forumid"]['parentlist'] .= $curid . ',';
				$curid = $vbulletin->forumcache["$curid"]['parentid'];
			}
			else
			{
				global $vbphrase;
				if (!isset($vbphrase['invalid_forum_parenting']))
				{
					$vbphrase['invalid_forum_parenting'] = 'Invalid forum parenting setup. Contact vBulletin support.';
				}
				trigger_error($vbphrase['invalid_forum_parenting'], E_USER_ERROR);
			}
		}

		$vbulletin->forumcache["$forumid"]['parentlist'] .= '-1';

		// child list
		$vbulletin->forumcache["$forumid"]['childlist'] = $forumid;
		fetch_forum_child_list($forumid, $forumid);
		$vbulletin->forumcache["$forumid"]['childlist'] .= ',-1';
	}

	$parentsql = '';
	$childsql = '';
	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		$parentsql .= "	WHEN $forumid THEN '$forum[parentlist]'
		";
		$childsql .= "	WHEN $forumid THEN '$forum[childlist]'
		";
	}

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "forum SET
			parentlist = CASE forumid
				$parentsql
				ELSE parentlist
			END,
			childlist = CASE forumid
				$childsql
				ELSE childlist
			END
	");
}

// #############################################################################
/**
* Recursive function to populate $vbulletin->forumcache with correct child list fields
*
* @param	integer	Forum ID to be updated
* @param	integer	Parent forum ID
*/
function fetch_forum_child_list($mainforumid, $parentid)
{
	global $vbulletin;

	if (is_array($vbulletin->iforumcache["$parentid"]))
	{
		foreach ($vbulletin->iforumcache["$parentid"] AS $forumid => $forumparentid)
		{
			$vbulletin->forumcache["$mainforumid"]['childlist'] .= ',' . $forumid;
			fetch_forum_child_list($mainforumid, $forumid);
		}
	}
}

// #############################################################################
/**
* Populates the $vbulletin->forumcache with calculated forum permissions for each usergroup
*
* NB: this function should only be called from build_forum_permissions()
*
* @param	integer	Initial permissions value
* @param	integer	Parent forum id
*/
function cache_forum_permissions($permissions, $parentid = -1)
{
	global $vbulletin, $fpermcache;

	// abort if no child forums found
	if (!is_array($vbulletin->iforumcache["$parentid"]))
	{
		return;
	}

	// run through each child forum
	foreach($vbulletin->iforumcache["$parentid"] AS $forumid)
	{
		$forum =& $vbulletin->forumcache["$forumid"];

		// make a copy of the current permissions set up
		$perms = $permissions;

		// run through each usergroup
		foreach(array_keys($vbulletin->usergroupcache) AS $usergroupid)
		{
			// if there is a custom permission for the current usergroup, use it
			if (isset($fpermcache["$forumid"]["$usergroupid"]))
			{
				$perms["$usergroupid"] = $fpermcache["$forumid"]["$usergroupid"];
			}

			($hook = vBulletinHook::fetch_hook('admin_cache_forum_perms')) ? eval($hook) : false;

			// populate the current row of the forumcache permissions
			$forum['permissions']["$usergroupid"] = intval($perms["$usergroupid"]);
		}
		// recurse to child forums
		cache_forum_permissions($perms, $forum['forumid']);
	}
}

// #############################################################################
/**
* Returns a string safe for use in Javascript code
*
* @param	string	Text to be made safe
* @param	string	Quote type to be used in Javascript (either ' or ")
*
* @return	string
*/
function fetch_js_safe_string($object, $quotechar = '"')
{
	$find = array(
		"\r\n",
		"\n",
		'"'
	);

	$replace = array(
		'\r\n',
		'\n',
		"\\$quotechar",
	);

	$object = str_replace($find, $replace, $object);

	return $object;
}

// #############################################################################
/**
* Returns a string safe for use in Javascript code
*
* @param	string	Text to be made safe
* @param	string	Quote type to be used in Javascript (either ' or ")
*
* @return	string
*/
function fetch_js_unsafe_string($object, $quotechar = '"')
{
	$find = array(
		'\r\n',
		'\n',
		"\\$quotechar",
	);

	$replace = array(
		"\r\n",
		"\n",
		"$quotechar",
	);

	$object = str_replace($find, $replace, $object);

	return $object;
}

// #############################################################################
/**
* Returns an array of folders containing control panel CSS styles
*
* Styles are read from /path/to/vbulletin/cpstyles/
*
* @return	array
*/
function fetch_cpcss_options()
{
	$folders = array();

	if ($handle = @opendir(DIR . '/cpstyles'))
	{
		while ($folder = readdir($handle))
		{
			if ($folder == '.' OR $folder == '..')
			{
				continue;
			}
			if (is_dir("./cpstyles/$folder") AND @file_exists("./cpstyles/$folder/controlpanel.css"))
			{
				$folders["$folder"] = $folder;
			}
		}
		closedir($handle);
		uksort($folders, 'strnatcasecmp');
		$folders = str_replace('_', ' ', $folders);
	}

	return $folders;
}

// #############################################################################
/**
* Returns a string with & converted to &amp; when not followed by an entity
*
* @param	string	Text to be converted
*
* @return	string
*/
function convert_to_valid_html($text)
{
	return preg_replace('/&(?![a-z0-9#]+;)/', '&amp;', $text);
}

// ############################## Start vbflush ####################################
/**
* Force the output buffers to the browser
*/
function vbflush()
{
	static $gzip_handler = null;
	if ($gzip_handler === null)
	{
		$gzip_handler = false;
		$output_handlers = ob_list_handlers();
		if (is_array($output_handlers))
		{
			foreach ($output_handlers AS $handler)
			{
				if ($handler == 'ob_gzhandler')
				{
					$gzip_handler = true;
					break;
				}
			}
		}
	}

	if ($gzip_handler)
	{
		// forcing a flush with this is very bad
		return;
	}

	if (ob_get_length() !== false)
	{
		@ob_flush();
	}
	flush();
}

// ############################## Start fetch_product_list ####################################
/**
* Returns an array of currently installed products. Always includes 'vBulletin'.
*
* @param	boolean	If true, SELECT *, otherwise SELECT productid, title
* @param	boolean	Allow a previously cached version to be used
* @param	boolean	Include or exclude disabled products
* @param	string	Include this product even if its disabled, and disabled are excluded
*
* @return	array
*/
function fetch_product_list($alldata = false, $use_cached = true, $incdisabled = true, $incproduct = false)
{
	global $vbulletin;

	if ($alldata)
	{
		static $all_data_cache = false;

		if ($all_data_cache === false)
		{
			$productlist = array(
				'vbulletin' => array(
					'productid' => 'vbulletin',
					'title' => 'vBulletin',
					'description' => '',
					'version' => $vbulletin->options['templateversion'],
					'active' => 1
				)
			);

			$products = $vbulletin->db->query_read("
				SELECT *
				FROM " . TABLE_PREFIX . "product
				ORDER BY title
			");
			while ($product = $vbulletin->db->fetch_array($products))
			{
				if($incdisabled OR $product['active']
				OR $product['productid'] == $incproduct)
				{
					$productlist["$product[productid]"] = $product;
				}
			}

			$all_data_cache = $productlist;
		}
		else
		{
			$productlist = $all_data_cache;
		}
	}
	else
	{
		$productlist = array(
			'vbulletin' => 'vBulletin'
		);

		$products = $vbulletin->db->query_read("
			SELECT productid, title, active
			FROM " . TABLE_PREFIX . "product
			ORDER BY title
		");

		while ($product = $vbulletin->db->fetch_array($products))
		{
			if($incdisabled OR $product['active']
			OR $product['productid'] == $incproduct)
			{
				$productlist["$product[productid]"] = $product['title'];
			}
		}
	}

	if ($products)
	{
		$vbulletin->db->free_result($products);
	}

	return $productlist;
}

// ############################## Start build_activitystream_datastore ####################################
/**
* Stores the list of currently active activitystream types into the datastore
*/
function build_activitystream_datastore()
{
	global $vbulletin;

	$streamdata = array();
	$activities = $vbulletin->db->query_read("
		SELECT
			a.typeid, a.section, a.type, a.enabled,
			p.class
		FROM " . TABLE_PREFIX . "activitystreamtype AS a
		INNER JOIN " . TABLE_PREFIX . "package AS p ON (a.packageid = p.packageid)
	");
	while ($activity = $vbulletin->db->fetch_array($activities))
	{
		$section = $activity['section'];
		$type = $activity['type'];
		if ($activity['enabled'])
		{
			$streamdata['enabled']['all'][] = $activity['typeid'];
			$streamdata['enabled'][$activity['section']][] = $activity['typeid'];
		}
		if ($type == 'photo')
		{
			$streamdata['photo'][] = $activity['typeid'];
		}
		unset($activity['section'], $activity['type']);
		$streamdata["{$section}_{$type}"] = $activity;
	}

	build_datastore('activitystream', serialize($streamdata), 1);
}
//build_activitystream_datastore();
// ############################## Start build_product_datastore ####################################
/**
* Stores the list of currently installed products into the datastore.
*/
function build_product_datastore()
{
	global $vbulletin;

	$vbulletin->products = array('vbulletin' => 1);

	$products_list = $vbulletin->db->query_read("
		SELECT productid, active
		FROM " . TABLE_PREFIX . "product
	");

	while ($product = $vbulletin->db->fetch_array($products_list))
	{
		$vbulletin->products[$product['productid']] = $product['active'];
	}

	build_datastore('products', serialize($vbulletin->products), 1);
}

/**
* Verifies that the optimizer you are using with vB is compatible. Bugs in
* various versions of optimizers such as Turck MMCache and eAccelerator
* have rendered vB unusable.
*
* @return	string|bool	Returns true if no error, else returns a string that represents the error that occured
*/
function verify_optimizer_environment()
{
	// fail if eAccelerator is too old or Turck is loaded
	if (extension_loaded('Turck MMCache'))
	{
		return 'mmcache_not_supported';
	}
	else if (extension_loaded('eAccelerator'))
	{
		// first, attempt to use phpversion()...
		if ($eaccelerator_version = phpversion('eAccelerator'))
		{
			if (version_compare($eaccelerator_version, '0.9.3', '<') AND (@ini_get('eaccelerator.enable') OR @ini_get('eaccelerator.optimizer')))
			{
				return 'eaccelerator_too_old';
			}
		}
		// phpversion() failed, use phpinfo data
		else if (function_exists('phpinfo') AND function_exists('ob_start') AND @ob_start())
		{
			eval('phpinfo();');
			$info = @ob_get_contents();
			@ob_end_clean();
			preg_match('#<tr class="h"><th>eAccelerator support</th><th>enabled</th></tr>(?:\s+)<tr><td class="e">Version </td><td class="v">(.*?)</td></tr>(?:\s+)<tr><td class="e">Caching Enabled </td><td class="v">(.*?)</td></tr>(?:\s+)<tr><td class="e">Optimizer Enabled </td><td class="v">(.*?)</td></tr>#si', $info, $hits);
			if (!empty($hits[0]))
			{
				$version = trim($hits[1]);
				$caching = trim($hits[2]);
				$optimizer = trim($hits[3]);

				if (($caching === 'true' OR $optimizer === 'true') AND version_compare($version, '0.9.3', '<'))
				{
					return 'eaccelerator_too_old';
				}
			}
		}
	}
	else if (extension_loaded('apc'))
	{
		// first, attempt to use phpversion()...
		if ($apc_version = phpversion('apc'))
		{
			if (version_compare($apc_version, '2.0.4', '<'))
			{
				return 'apc_too_old';
			}
		}
		// phpversion() failed, use phpinfo data
		else if (function_exists('phpinfo') AND function_exists('ob_start') AND @ob_start())
		{
			eval('phpinfo();');
			$info = @ob_get_contents();
			@ob_end_clean();
			preg_match('#<tr class="h"><th>APC support</th><th>enabled</th></tr>(?:\s+)<tr><td class="e">Version </td><td class="v">(.*?)</td></tr>#si', $info, $hits);
			if (!empty($hits[0]))
			{
				$version = trim($hits[1]);

				if (version_compare($version, '2.0.4', '<'))
				{
					return 'apc_too_old';
				}
			}
		}
	}

	return true;
}

/**
* Checks userid is a user that shouldn't be editable
*
* @param	integer	userid to check
*
* @return	boolean
*/
function is_unalterable_user($userid)
{
	global $vbulletin;

	static $noalter = null;

	if (!$userid)
	{
		return false;
	}

	if ($noalter === null)
	{
		$noalter = explode(',', $vbulletin->config['SpecialUsers']['undeletableusers']);

		if (!is_array($noalter))
		{
			$noalter = array();
		}
	}

	return in_array($userid, $noalter);
}

/**
* Resolves an image URL used in the CP that should be relative to the root directory.
*
* @param	string	The path to resolve
*
* @return	string	Resolved path
*/
function resolve_cp_image_url($image_path)
{
	if ($image_path[0] == '/' OR preg_match('#^https?://#i', $image_path))
	{
		return $image_path;
	}
	else
	{
		return "../$image_path";
	}
}

/**
* Prints JavaScript to automatically submit the named form. Primarily used
* for automatic redirects via POST.
*
* @param	string	Form name (in HTML)
*/
function print_form_auto_submit($form_name)
{
	$form_name = preg_replace('#[^a-z0-9_]#i', '', $form_name);

	?>
	<script type="text/javascript">
	<!--
	if (document.<?php echo $form_name; ?>)
	{
		function send_submit()
		{
			var submits = YAHOO.util.Dom.getElementsBy(
				function(element) { return (element.type == "submit") },
				"input", this
			);
			var submit_button;

			for (var i = 0; i < submits.length; i++)
			{
				submit_button = submits[i];
				submit_button.disabled = true;
				setTimeout(function() { submit_button.disabled = false; }, 10000);
			}

			return false;
		}

		YAHOO.util.Event.on(document.<?php echo $form_name; ?>, 'submit', send_submit);
		send_submit.call(document.<?php echo $form_name; ?>);
		document.<?php echo $form_name; ?>.submit();
	}
	// -->
	</script>
	<?php
}

// #############################################################################
/**
* Prints the help for the style generator
*
* @param	array 	contains all help info
*
* @return	string	Formatted help text
*/
function print_style_help($stylehelp)
{
	foreach ($stylehelp as $id => $info) {
		echo "<div id=\"$id\">";
		if($info[0]) echo "
		<strong>$info[0]</strong>";
		echo "
		$info[1]
		</div>
		";
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63352 $
|| ####################################################################
\*======================================================================*/
