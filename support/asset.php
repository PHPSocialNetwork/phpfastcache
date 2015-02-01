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
@ini_set('zlib.output_compression', 'Off');
@set_time_limit(0);
if (@ini_get('output_handler') == 'ob_gzhandler' AND @ob_get_length() !== false)
{	// if output_handler = ob_gzhandler, turn it off and remove the header sent by PHP
	@ob_end_clean();
	header('Content-Encoding:');
}

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'asset');
define('CSRF_PROTECTION', true);
define('NOHEADER', 1);
define('NOZIP', 1);
define('NOCOOKIES', 1);
define('NOPMPOPUP', 1);
define('NONOTICES', 1);
define('NOSHUTDOWNFUNC', 1);
define('LOCATION_BYPASS', 1);

if (empty($_REQUEST['fid']))
{
	// return not found header
	$sapi_name = php_sapi_name();
	if ($sapi_name == 'cgi' OR $sapi_name == 'cgi-fcgi')
	{
		header('Status: 404 Not Found');
	}
	else
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
	}
	exit;
}

// Immediately send back the 304 Not Modified header if this image is cached, don't load global.php
if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) OR !empty($_SERVER['HTTP_IF_NONE_MATCH']))
{
	$sapi_name = php_sapi_name();
	if ($sapi_name == 'cgi' OR $sapi_name == 'cgi-fcgi')
	{
		header('Status: 304 Not Modified');
	}
	else
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
	}
	// remove the content-type and X-Powered headers to emulate a 304 Not Modified response as close as possible
	header('Content-Type:');
	header('X-Powered-By:');
	if (!empty($_REQUEST['fid']))
	{
		header('ETag: "' . intval($_REQUEST['fid']));
	}
	exit;
}

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array();

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

/*
The following headers are usually handled internally but we do our own thing
with filedata, the cache-control is to stop caches keeping private attachments
and the Vary header is to deal with the fact the filename encoding changes.
*/
header('Cache-Control: private');
header('Vary: User-Agent');

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vbulletin->input->clean_array_gpc('r', array(
	'fid' => TYPE_UINT,
));

if (
	!$vbulletin->GPC['fid']
		OR
	!($filedatainfo = $db->query_first_slave("
		SELECT
			fd.filedataid, fd.thumbnail_dateline AS dateline, fd.thumbnail_filesize AS filesize, fd.extension, fd.userid, fd.thumbnail AS filedata, fd.refcount,
			at.mimetype
		FROM " . TABLE_PREFIX . "attachmentcategoryuser AS acu
		INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (acu.filedataid = fd.filedataid)
		LEFT JOIN " . TABLE_PREFIX . "attachmenttype AS at ON (at.extension = fd.extension)
		WHERE
			acu.filedataid = {$vbulletin->GPC['fid']}
				AND
			acu.userid = {$vbulletin->userinfo['userid']}
		LIMIT 1
	"))
)
{
	eval(standard_error(fetch_error('invalidid', 'filedata', $vbulletin->options['contactuslink'])));
}

if ($filedatainfo['extension'])
{
	$extension = strtolower($filedatainfo['extension']);
}
else
{
	$extension = strtolower(file_extension($filedatainfo['filename']));
}

if ($vbulletin->options['attachfile'])
{
	require_once(DIR . '/includes/functions_file.php');
	$filepath = fetch_attachment_path($filedatainfo['userid'], $filedatainfo['filedataid'], true);

	if (!($fp = @fopen($filepath, 'rb')))
	{
		// replace this with a ? type image
		echo fetch_blank_image();
		exit;
	}
}
else if (!$filedatainfo['filedata'])
{
	// replace this with a ? type image
	echo fetch_blank_image();
	exit;
}

// send jpeg header for PDF, BMP, TIF, TIFF, and PSD thumbnails as they are jpegs
if (in_array($extension, array('bmp', 'tif', 'tiff', 'psd', 'pdf')))
{
	$filedatainfo['filename'] = preg_replace('#.(bmp|tiff?|psd|pdf)$#i', '.jpg', $filedatainfo['filename']);
	$mimetype = array('Content-type: image/jpeg');
}
else
{
	$mimetype = unserialize($filedatainfo['mimetype']);
}

header('Pragma:'); // VBIV-8269 
header('Cache-control: max-age=31536000, private');
header('Expires: ' . gmdate("D, d M Y H:i:s", TIMENOW + 31536000) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $filedatainfo['dateline']) . ' GMT');
header('ETag: "' . $filedatainfo['filedataid'] . '-' . $filedatainfo['userid'] . '"');

// look for entities in the file name, and if found try to convert
// the filename to UTF-8
$filename = $filedatainfo['filename'];
if (preg_match('~&#([0-9]+);~', $filename))
{
	if (function_exists('iconv'))
	{
		$filename_conv = @iconv(vB_Template_Runtime::fetchStyleVar('charset'), 'UTF-8//IGNORE', $filename);
		if ($filename_conv !== false)
		{
			$filename = $filename_conv;
		}
	}

	$filename = preg_replace(
		'~&#([0-9]+);~e',
		"convert_int_to_utf8('\\1')",
		$filename
	);
	$filename_charset = 'utf-8';
}
else
{
	$filename_charset = vB_Template_Runtime::fetchStyleVar('charset');
}

$filename = preg_replace('#[\r\n]#', '', $filename);

// Opera and IE have not a clue about this, mozilla puts on incorrect extensions.
if (is_browser('mozilla'))
{
	$filename = "filename*=" . $filename_charset . "''" . rawurlencode($filename);
}
else
{
	// other browsers seem to want names in UTF-8
	if ($filename_charset != 'utf-8' AND function_exists('iconv'))
	{
		$filename_conv = iconv($filename_charset, 'UTF-8//IGNORE', $filename);
		if ($filename_conv !== false)
		{
			$filename = $filename_conv;
		}
	}

	if (is_browser('opera') OR is_browser('konqueror') OR is_browser('safari'))
	{
		// Opera / Konqueror does not support encoded file names
		$filename = 'filename="' . str_replace('"', '', $filename) . '"';
	}
	else
	{
		// encode the filename to stay within spec
		$filename = 'filename="' . rawurlencode($filename) . '"';
	}
}

header("Content-disposition: inline; $filename");
header('Content-transfer-encoding: binary');
header('Content-Length: ' . $filedatainfo['filesize']);

if (is_array($mimetype))
{
	foreach ($mimetype AS $header)
	{
		if (!empty($header))
		{
			header($header);
		}
	}
}
else
{
	header('Content-type: unknown/unknown');
}

// This is new in IE8 and tells the browser not to try and guess
header('X-Content-Type-Options: nosniff');

($hook = vBulletinHook::fetch_hook('asset_filedata_display')) ? eval($hook) : false;

if (defined('NOSHUTDOWNFUNC'))
{
	if ($_GET['stc'] == 1)
	{
		$db->close();
	}
	else
	{
		exec_shut_down();
	}
}

if ($vbulletin->options['attachfile'])
{
	echo @fread($fp, $filedatainfo['filesize']);
	@fclose($fp);
}
else
{
	echo $filedatainfo['filedata'];
}
flush();

($hook = vBulletinHook::fetch_hook('asset_filedata_complete')) ? eval($hook) : false;

function fetch_blank_image()
{
	$filedata = vb_base64_decode('R0lGODlhAQABAIAAAMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
	$filesize = strlen($filedata);
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');             // Date in the past
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
	header('Cache-Control: no-cache, must-revalidate');           // HTTP/1.1
	header('Pragma: no-cache');                                   // HTTP/1.0
	header("Content-disposition: inline; filename=clear.gif");
	header('Content-transfer-encoding: binary');
	header("Content-Length: $filesize");
	header('Content-type: image/gif');
	return $filedata;
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 47204 $
|| ####################################################################
\*======================================================================*/
?>
