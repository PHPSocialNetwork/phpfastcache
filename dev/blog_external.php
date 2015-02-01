<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Blog 4.2.1 - Licence Number VBF02D260D
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000–2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('VB_PRODUCT', 'vbblog');
define('NOSHUTDOWNFUNC', 1);
define('SKIP_SESSIONCREATE', 1);
define('DIE_QUIETLY', 1);
define('THIS_SCRIPT', 'blog_external');
define('VBBLOG_PERMS', true);
define('VBBLOG_STYLE', true);
define('VBBLOG_SKIP_PERMCHECK', true);
define('VBBLOG_SCRIPT', true);
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('vbblogglobal', 'postbit');

// get special data templates from the datastore
$specialtemplates = array('blogcategorycache');

// pre-cache templates used by all actions
$globaltemplates = array(
	'bbcode_code_printable',
	'bbcode_html_printable',
	'bbcode_php_printable',
	'bbcode_quote_printable',
	'blog_entry_category',
	'postbit_attachmentimage',
	'postbit_attachmentthumbnail',
	'blog_entry_external',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/blog_init.php');
require_once(DIR . '/includes/blog_functions.php');

verify_blog_url();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (!$vbulletin->options['externalrss'])
{
	exit;
}


// We don't want no stinkin' sessionhash
$vbulletin->session->vars['sessionurl'] =
$vbulletin->session->vars['sessionurl_q'] =
$vbulletin->session->vars['sessionurl_js'] =
$vbulletin->session->vars['sessionhash'] = '';

$vbulletin->input->clean_array_gpc('r', array(
	'bloguserid'  => TYPE_UINT,
	'lastcomment' => TYPE_BOOL,
	'nohtml'      => TYPE_BOOL
));

($hook = vBulletinHook::fetch_hook('blog_external_start')) ? eval($hook) : false;

$description = $vbulletin->options['description'];

if (!($vbulletin->userinfo['permissions']['vbblog_general_permissions'] & $vbulletin->bf_ugp_vbblog_general_permissions['blog_canviewothers']))
{	// no access to view blogs
	require_once(DIR . '/includes/class_xml.php');
	$rsstitle = construct_phrase($vbphrase['blog_rss_title'], $vbulletin->options['bbtitle']);
	$xml = new vB_XML_Builder($vbulletin);
	$rsstag = array(
		'version'       => '2.0',
		'xmlns:dc'      => 'http://purl.org/dc/elements/1.1/',
		'xmlns:content' => 'http://purl.org/rss/1.0/modules/content/'
	);
	$xml->add_group('rss', $rsstag);
		$xml->add_group('channel');
			$xml->add_tag('title', $rsstitle);
			$xml->add_tag('link', fetch_seo_url('bloghome|nosession|bburl', array()), array(), false, true);
			$xml->add_tag('description', $description);
			$xml->add_tag('language', vB_Template_Runtime::fetchStyleVar('languagecode'));
			$xml->add_tag('lastBuildDate', gmdate('D, d M Y H:i:s') . ' GMT');
			#$xml->add_tag('pubDate', gmdate('D, d M Y H:i:s') . ' GMT');
			$xml->add_tag('generator', 'vBulletin');
		$xml->close_group('channel');
	$xml->close_group('rss');
	header('Content-Type: text/xml' . (vB_Template_Runtime::fetchStyleVar('charset') != '' ? '; charset=' .  vB_Template_Runtime::fetchStyleVar('charset') : ''));
	echo '<?xml version="1.0" encoding="' . vB_Template_Runtime::fetchStyleVar('charset') . '"?>' . "\r\n\r\n";
	echo $xml->output();
	exit;
}

if (!$vbulletin->options['externalcount'])
{
	$vbulletin->options['externalcount'] = 15;
}
$count = $vbulletin->options['externalcount'];

if (!intval($vbulletin->options['externalcache']) OR $vbulletin->options['externalcache'] > 1440)
{
	$externalcache = 60;
}
else
{
	$externalcache = $vbulletin->options['externalcache'];
}

$cachetime = $externalcache * 60;
$cachehash = md5(
	'blog|' .
	$vbulletin->options['externalcutoff'] . '|' .
	$externalcache . '|' .
	$count . '|' .
	$vbulletin->GPC['bloguserid'] . '|' .
	$vbulletin->GPC['nohtml']
);

if ($_SERVER['HTTP_IF_NONE_MATCH'] == "\"$cachehash\"" AND !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
{
	$timediff = strtotime(gmdate('D, d M Y H:i:s') . ' GMT') - strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
	if ($timediff <= $cachetime)
	{
		$db->close();
		if (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi')
		{
			header('Status: 304 Not Modified');
		}
		else
		{
			header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
		}
		exit;
	}
}

if ($foundcache = $db->query_first_slave("
	SELECT text, headers, dateline
	FROM " . TABLE_PREFIX . "externalcache
	WHERE cachehash = '" . $db->escape_string($cachehash) . "' AND
		 dateline >= " . (TIMENOW - $cachetime) . "
"))
{
	$db->close();
	if (!empty($foundcache['headers']))
	{
		$headers = unserialize($foundcache['headers']);
		if (!empty($headers))
		{
			foreach($headers AS $header)
			{
				header($header);
			}
		}
	}
	echo $foundcache['text'];
	exit;
}

$cutoff = (!$vbulletin->options['externalcutoff']) ? 0 : TIMENOW - $vbulletin->options['externalcutoff'] * 86400;

// build the where clause
if ($vbulletin->GPC['bloguserid'])
{
	$userinfo = fetch_userinfo($vbulletin->GPC['bloguserid']);
	$condition = "blog.userid = " . $vbulletin->GPC['bloguserid'];
}
else
{
	$condition = '1=1';
}

$globalignore = '';
if (trim($vbulletin->options['globalignore']) != '')
{
	require_once(DIR . '/includes/functions_bigthree.php');
	if ($Coventry = fetch_coventry('string'))
	{
		$globalignore = "AND blog.userid NOT IN ($Coventry) AND blog_text.userid NOT IN ($Coventry)";
	}
}

if (!empty($vbulletin->userinfo['blogcategorypermissions']['cantview']))
{
	$joinsql = "LEFT JOIN " . TABLE_PREFIX . "blog_categoryuser AS cu ON (cu.blogid = blog.blogid AND cu.blogcategoryid IN (" . implode(", ", $vbulletin->userinfo['blogcategorypermissions']['cantview']) . "))";
	$wheresql = "AND cu.blogcategoryid IS NULL";
}

$hook_query_fields = $hook_query_joins = $hook_query_where = '';
($hook = vBulletinHook::fetch_hook('blog_external_query')) ? eval($hook) : false;
$blog_posts = $db->query_read_slave("
	SELECT blog.*, blog_text.*, user.*
	$hook_query_fields
	FROM " . TABLE_PREFIX . "blog AS blog
	INNER JOIN " . TABLE_PREFIX . "blog_text AS blog_text ON (blog.firstblogtextid = blog_text.blogtextid)
	INNER JOIN " . TABLE_PREFIX . "blog_user AS blog_user ON (blog_user.bloguserid = blog.userid)
	INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = blog_text.userid)
	$joinsql
	$hook_query_joins
	WHERE $condition
		AND blog.state = 'visible'
		AND blog.dateline <= " . TIMENOW . "
		AND blog.pending = 0
		AND blog_user.options_guest & " . $vbulletin->bf_misc_vbblogsocnetoptions['canviewmyblog'] . "
		AND ~blog.options & " . $vbulletin->bf_misc_vbblogoptions['private'] . "
		$wheresql
		$globalignore
		$hook_query_where
	ORDER BY blog.dateline DESC
	LIMIT $count
");

$expires = TIMENOW + $cachetime;

$output = '';
$headers = array();

// RSS output
// setup the board title
if ($vbulletin->GPC['bloguserid'])
{
	if ($userinfo['blog_title'] != $userinfo['username'])
	{
		$rsstitle = construct_phrase($vbphrase['blog_rss_title_with_blogtitle'], $vbulletin->options['bbtitle'], $userinfo['blog_title'], $userinfo['username']);
	}
	else
	{
		$rsstitle = construct_phrase($vbphrase['blog_rss_title_without_blogtitle'], $vbulletin->options['bbtitle'], $userinfo['username']);
	}
	$bloglink = fetch_seo_url('blog|nosession|bburl', $userinfo);
}
else
{
	$rsstitle = construct_phrase($vbphrase['blog_rss_title'], $vbulletin->options['bbtitle']);
	$bloglink = fetch_seo_url('bloghome|nosession|bburl', array());
}
$rssicon = create_full_url(vB_Template_Runtime::fetchStyleVar('imgdir_misc') . '/rss.jpg');

$headers[] = 'Pragma:'; // VBIV-8269
$headers[] = 'Cache-control: max-age=' . $expires;
$headers[] = 'Expires: ' . gmdate("D, d M Y H:i:s", $expires) . ' GMT';
$headers[] = 'ETag: "' . $cachehash . '"';
$headers[] = 'Content-Type: text/xml' . (vB_Template_Runtime::fetchStyleVar('charset') != '' ? '; charset=' .  vB_Template_Runtime::fetchStyleVar('charset') : '');

$output = '<?xml version="1.0" encoding="' . vB_Template_Runtime::fetchStyleVar('charset') . '"?>' . "\r\n\r\n";

require_once(DIR . '/includes/class_xml.php');
$xml = new vB_XML_Builder($vbulletin);
$rsstag = array(
	'version'       => '2.0',
	'xmlns:dc'      => 'http://purl.org/dc/elements/1.1/',
	'xmlns:content' => 'http://purl.org/rss/1.0/modules/content/'
);
$xml->add_group('rss', $rsstag);
	$xml->add_group('channel');
		$xml->add_tag('title', $rsstitle);
		$xml->add_tag('link', $bloglink, array(), false, true);
		$xml->add_tag('description', $description);
		$xml->add_tag('language', vB_Template_Runtime::fetchStyleVar('languagecode'));
		$xml->add_tag('lastBuildDate', gmdate('D, d M Y H:i:s') . ' GMT');
		#$xml->add_tag('pubDate', gmdate('D, d M Y H:i:s') . ' GMT');
		$xml->add_tag('generator', 'vBulletin');
		$xml->add_tag('ttl', $externalcache);
		$xml->add_group('image');
			$xml->add_tag('url', $rssicon);
			$xml->add_tag('title', $rsstitle);
			$xml->add_tag('link', $bloglink, array(), false, true);
		$xml->close_group('image');

require_once(DIR . '/includes/class_bbcode_alt.php');

$blogids = $blogcache = $postattach = array();
while($blog = $db->fetch_array($blog_posts))
{
	$blogcache[] = $blog;
	if ($blog['attach'])
	{
		$blogids["$blog[blogid]"] = $blog['blogid'];
	}
}

if (!$vbulletin->GPC['nohtml'] AND !empty($blogids))
{
	require_once(DIR . '/packages/vbattach/attach.php');
	$attach = new vB_Attach_Display_Content($vbulletin, 'vBBlog_BlogEntry');
	$postattach = $attach->fetch_postattach(0, $blogids);
}

require_once(DIR . '/includes/class_blog_entry.php');
require_once(DIR . '/includes/class_bbcode_blog.php');
$bbcode = new vB_BbCodeParser_Blog($vbulletin, fetch_tag_list());

$i = 0;
$viewattachedimages = $vbulletin->options['viewattachedimages'];
$attachthumbs = $vbulletin->options['attachthumbs'];

// list returned blog entries
$perm_cache = array();
foreach($blogcache AS $blog_post)
{
	$xml->add_group('item');
		$xml->add_tag('title', unhtmlspecialchars($blog_post['title']));
		$xml->add_tag('link', fetch_seo_url('entry|nosession|bburl', $blog_post), array(), false, true);
		$xml->add_tag('pubDate', gmdate('D, d M Y H:i:s', $blog_post['dateline']) . ' GMT');

	if (!isset($perm_cache["$blog_post[userid]"]))
	{
		$perm_cache["$blog_post[userid]"] = cache_permissions($blog_post, false);
	}
	$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());
	$plaintext_parser->set_parse_userinfo($blog_post, $perm_cache["$blog_post[userid]"]);

	$plainmessage = $plaintext_parser->parse($blog_post['pagetext'], 'blog_comment');
	unset($plaintext_parser);

	if ($vbulletin->GPC['fulldesc'])
	{
		$xml->add_tag('description', $plainmessage);
	}
	else
	{
		$xml->add_tag('description', fetch_trimmed_title($plainmessage, $vbulletin->options['threadpreview']));
	}

	if (!$vbulletin->GPC['nohtml'])
	{
		$entry_factory = new vB_Blog_EntryFactory($vbulletin, $bbcode, $entry_categories);
		$entry_handler =& $entry_factory->create($blog_post, 'external');
		$entry_handler->attachments = $postattach["$blog_post[blogid]"];
		$xml->add_tag('content:encoded', $entry_handler->construct());
	}

	$xml->add_tag('dc:creator', unhtmlspecialchars($blog_post['username']));
	$xml->add_tag('guid', fetch_seo_url('entry|nosession|bburl', $blog_post), array('isPermaLink' => 'true'));

	$xml->close_group('item');
}

	$xml->close_group('channel');
$xml->close_group('rss');
$output .= $xml->output();
unset($xml);

($hook = vBulletinHook::fetch_hook('blog_external_complete')) ? eval($hook) : false;

$db->query_write("
	REPLACE INTO " . TABLE_PREFIX . "externalcache
		(cachehash, dateline, text, headers, forumid)
	VALUES
		(
			'" . $db->escape_string($cachehash) . "',
			" . TIMENOW . ",
			'" . $db->escape_string($output) . "',
			'" . $db->escape_string(serialize($headers)) . "',
			0
		)
");
$db->close();

foreach ($headers AS $header)
{
	header($header);
}
echo $output;

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # SVN: $Revision: 63620 $
|| ####################################################################
\*======================================================================*/
?>
