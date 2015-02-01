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
define('NOSHUTDOWNFUNC', 1);
define('SKIP_SESSIONCREATE', 1); // Always runs script as GUEST.
define('DIE_QUIETLY', 1);
define('THIS_SCRIPT', 'external');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('postbit');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
	'bbcode_code_printable',
	'bbcode_html_printable',
	'bbcode_php_printable',
	'bbcode_quote_printable',
	'postbit_attachment',
	'postbit_attachmentimage',
	'postbit_attachmentthumbnail',
	'postbit_external',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// take the first of "forumids" and make it "forumid" for style stuff
// see bug #22743
if ($_REQUEST['forumids'])
{
	// quick way of getting the first value
	$_REQUEST['forumid'] = intval($_REQUEST['forumids']);
}

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// We don't want no stinkin' sessionhash
$vbulletin->session->vars['sessionurl'] =
$vbulletin->session->vars['sessionurl_q'] =
$vbulletin->session->vars['sessionurl_js'] =
$vbulletin->session->vars['sessionhash'] = '';

$vbulletin->input->clean_array_gpc('r', array(
	'forumid'  => TYPE_UINT,
	'forumids' => TYPE_STR,
	'type'     => TYPE_STR,
	'lastpost' => TYPE_BOOL,
	'nohtml'   => TYPE_BOOL,
	'fulldesc' => TYPE_BOOL,
	'do'       => TYPE_STR,
	'count'    => TYPE_UINT,
	'id'       => TYPE_UINT,
	'grouped'  => TYPE_UINT,
	'days'     => TYPE_UINT,
	'detail'   => TYPE_STR,
	'name'     => TYPE_STR,
));

($hook = vBulletinHook::fetch_hook('external_start')) ? eval($hook) : false;


//If we have cms installed and this is a cms request, handle it first.

if ($vbulletin->products['vbcms'] AND $vbulletin->GPC_exists['do'] AND ($vbulletin->GPC['do'] == 'rss'))
{
	require_once(DIR . '/includes/class_xml.php');
	if (!defined('VB_ENTRY'))
	{
		define('VB_ENTRY', 'ajax.php');
	}

	// Get the entry time
	define('VB_ENTRY_TIME', microtime(true));

	// vB core path
	define('VB_PATH', DIR . '/vb/');

	// The package path
	define('VB_PKG_PATH', realpath(VB_PATH . '../packages') . '/');
	require_once(DIR . '/vb/vb.php');

	vB::init();
	vBCms_Rssfeed::makeRss();
	exit;
}

$vbulletin->GPC['type'] = strtoupper($vbulletin->GPC['type']);
$description = $vbulletin->options['description'];
$podcast = false;

// check to see if there is a forum preference
if ($vbulletin->GPC['forumid'])
{
	$vbulletin->GPC['forumids'] .= ',' . $vbulletin->GPC['forumid'];
}

if ($vbulletin->GPC['forumids'] != '')
{
	$forumchoice = array();
	$forumids = explode(',', $vbulletin->GPC['forumids']);
	foreach ($forumids AS $forumid)
	{
		$forumid = intval($forumid);
		$forumperms =& $vbulletin->userinfo['forumpermissions']["$forumid"];

		if (isset($vbulletin->forumcache["$forumid"])
			AND ($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
			AND ($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers'])
			AND verify_forum_password($forumid, $vbulletin->forumcache["$forumid"]['password'], false)
		)
		{
			$forumchoice[] = $forumid;
		}
	}

	// Sort forums for caching purposes -- ensure they are in numeric order for best potential cache hit
	sort($forumchoice, SORT_NUMERIC);
	$forumchoice = array_unique($forumchoice);

	$number_of_forums = sizeof($forumchoice);

	if ($number_of_forums == 1)
	{
		$title = unhtmlspecialchars($vbulletin->forumcache["$forumchoice[0]"]['title_clean']);
		$description = unhtmlspecialchars($vbulletin->forumcache["$forumchoice[0]"]['description_clean']);
	}
	else if ($number_of_forums > 1)
	{
		$title = implode(',', $forumchoice);
	}
	else
	{
		$title = '';
	}

	if (!empty($forumchoice))
	{
		$forumsql = "AND thread.forumid IN(" . implode(',', $forumchoice) . ")";
	}
	else
	{
		$forumsql = "";
	}
}
else
{
	foreach (array_keys($vbulletin->forumcache) AS $forumid)
	{
		$forumperms =& $vbulletin->userinfo['forumpermissions']["$forumid"];
		if ($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']
			AND ($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers'])
			AND verify_forum_password($forumid, $vbulletin->forumcache["$forumid"]['password'], false)
			)
		{
			$forumchoice[] = $forumid;
		}
	}
	if (!empty($forumchoice))
	{
		$forumsql = "AND thread.forumid IN(" . implode(',', $forumchoice) . ")";
	}
	else
	{
		$forumsql = "";
	}
}

if (empty($forumchoice))
{	// no access to view selected forums
	exit;
}

switch ($vbulletin->GPC['type'])
{
	case 'JS':
		if (!$vbulletin->options['externaljs'])
		{
			exit;
		}
		$vbulletin->GPC['nohtml'] = 0;
		break;
	case 'XML':
		if (!$vbulletin->options['externalxml'])
		{
			exit;
		}
		break;
	case 'RSS':
		$vbulletin->GPC['nohtml'] = 0;
	case 'RSS1':
	case 'RSS2':
		if (!$vbulletin->options['externalrss'])
		{
			exit;
		}
		break;
	default:
		$handled = false;
		($hook = vBulletinHook::fetch_hook('external_type')) ? eval($hook) : false;
		if (!$handled)
		{
			if (!$vbulletin->options['externalrss'])
			{
				exit;
			}
			$vbulletin->GPC['type'] = 'RSS2';
		}
}

if (!$vbulletin->options['externalcount'])
{
	$vbulletin->options['externalcount'] = 15;
}

if (!$vbulletin->GPC['count'] OR $vbulletin->GPC['count'] > $vbulletin->options['externalcount'])
{
	$count = $vbulletin->options['externalcount'];
}
else
{
	$count = $vbulletin->GPC['count'];
}

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
	$vbulletin->options['externalcutoff'] . '|' .
	$externalcache . '|' .
	$vbulletin->GPC['type'] . '|' .
	$vbulletin->GPC['lastpost'] . '|' .
	$vbulletin->GPC['nohtml'] . '|' .
	$vbulletin->GPC['fulldesc'] . '|' .
	$count . '|' .
	$forumsql
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

// remove threads from users on the global ignore list if user is not a moderator
$globalignore = '';
if (trim($vbulletin->options['globalignore']) != '')
{
	require_once(DIR . '/includes/functions_bigthree.php');
	if ($Coventry = fetch_coventry('string'))
	{
		$globalignore = "AND postuserid NOT IN ($Coventry) ";
	}
}

$hook_query_fields = $hook_query_joins = $hook_query_where = '';
($hook = vBulletinHook::fetch_hook('external_query')) ? eval($hook) : false;

$threadcache = array();
// query last threads from visible / chosen forums
$threads = $db->query_read_slave("
	SELECT thread.threadid, thread.title, thread.prefixid, post.attach,
		" . ($vbulletin->GPC['lastpost']
			? "thread.lastposter AS postusername, thread.lastpost AS dateline,"
			: "thread.postusername, thread.dateline, podcastitem.*,")
		. "
		forum.forumid,
		post.pagetext AS message, post.allowsmilie, post.postid
		$hook_query_fields
	FROM " . TABLE_PREFIX . "thread AS thread
	INNER JOIN " . TABLE_PREFIX . "forum AS forum ON(forum.forumid = thread.forumid)
	" . ($vbulletin->GPC['lastpost']
		? "LEFT JOIN " . TABLE_PREFIX . "post AS post ON (post.postid = thread.lastpostid)"
		: "LEFT JOIN " . TABLE_PREFIX . "post AS post ON (post.postid = thread.firstpostid)
			LEFT JOIN " . TABLE_PREFIX . "podcastitem AS podcastitem ON (podcastitem.postid = thread.firstpostid)")
	.	($vbulletin->products['vbcms']
		? "LEFT JOIN " . TABLE_PREFIX . "cms_nodeinfo AS cms_nodeinfo ON (cms_nodeinfo.associatedthreadid = thread.threadid)"
		: "" )
	. "
	$hook_query_joins
	WHERE 1=1
		$forumsql
	" . ($vbulletin->products['vbcms']
		? "AND cms_nodeinfo.nodeid IS null"
		: "" )
	. "	
		AND thread.visible = 1
		AND post.visible = 1
		AND open <> 10
		AND " . ($vbulletin->GPC['lastpost'] ? "thread.lastpost" : "thread.dateline") . " > $cutoff
		$globalignore
		$hook_query_where
	ORDER BY " . ($vbulletin->GPC['lastpost'] ? "thread.lastpost DESC" : "thread.dateline DESC") . "
	LIMIT $count
");

$postids = array();
while ($thread = $db->fetch_array($threads))
{ // fetch the threads
	// remove sessionhash from urls:
	$forumperms = fetch_permissions($thread['forumid']);
	if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
	{	// Don't show thread content and attachments
		$thread['message'] = '';
	}
	else
	{
		$postids["$thread[postid]"] = $thread['threadid'];
	}

	$thread['prefix_plain'] = ($thread['prefixid'] ? $vbphrase["prefix_$thread[prefixid]_title_plain"] . ' ' : '');
	$threadcache[] = $thread;
}
$lastmodified = (!empty($thread[0]['dateline']) ? $thread[0]['dateline'] : TIMENOW);
$expires = TIMENOW + $cachetime;

$attachmentcache = array();
if (!$vbulletin->GPC['nohtml'] AND !empty($postids) AND ($vbulletin->GPC['type'] == 'RSS1' OR $vbulletin->GPC['type'] == 'RSS2'))
{
	require_once(DIR . '/packages/vbattach/attach.php');
	$attach = new vB_Attach_Display_Content($vbulletin, 'vBForum_Post');
	$attachmentcache = $attach->fetch_postattach(0, array_keys($postids), null, true);
}

if ($number_of_forums == 1 AND $vbulletin->GPC['type'] == 'RSS2' AND $vbulletin->options['rsspodcast'])
{
	$podcastinfo = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "podcast
		WHERE forumid = $forumid AND enabled = 1
	");
	$podcastforumid = $forumchoice[0];
}
else
{
	$podcastforumid = 0;
}

$output = '';
$headers = array();
if ($vbulletin->GPC['type'] == 'JS')
{ // javascript output
	$output = "
	function thread(threadid, title, poster, threaddate, threadtime)
	{
		this.threadid = threadid;
		this.title = title;
		this.poster = poster;
		this.threaddate = threaddate;
		this.threadtime = threadtime;
	}
	";
	$output .= "var threads = new Array(" . sizeof ($threadcache) . ");\r\n";
	if (!empty($threadcache))
	{
		foreach ($threadcache AS $threadnum => $thread)
		{
			$thread['title'] = addslashes_js(htmlspecialchars_uni($thread['prefix_plain']) . $thread['title']);
			$thread['poster'] = addslashes_js($thread['postusername']);
			$output .= "\tthreads[$threadnum] = new thread($thread[threadid], '$thread[title]', '$thread[poster]', '" . addslashes_js(vbdate($vbulletin->options['dateformat'], $thread['dateline'])) . "', '" . addslashes_js(vbdate($vbulletin->options['timeformat'], $thread['dateline'])) . "');\r\n";
		}
	}
}
else if ($vbulletin->GPC['type'] == 'XML')
{ // XML output

	// set XML type and nocache headers
	$headers[] = 'Pragma:'; // VBIV-8269 
	$headers[] = 'Cache-control: max-age=' . $expires;
	$headers[] = 'Expires: ' . gmdate('D, d M Y H:i:s', $expires) . ' GMT';
	$headers[] = 'Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastmodified) . ' GMT';
	$headers[] = 'ETag: "' . $cachehash . '"';
	$headers[] = 'Content-Type: text/xml' . (vB_Template_Runtime::fetchStyleVar('charset') != '' ? '; charset=' .  vB_Template_Runtime::fetchStyleVar('charset') : '');

	// print out the page header
	$output = '<?xml version="1.0" encoding="' . vB_Template_Runtime::fetchStyleVar('charset') . '"?>' . "\r\n";
	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_XML_Builder($vbulletin);
	$xml->add_group('source');
		$xml->add_tag('url', $vbulletin->options['bburl'] . '/');

	// list returned threads
	if (!empty($threadcache))
	{
		foreach ($threadcache AS $thread)
		{
			$xml->add_group('thread', array('id' => $thread['threadid']));
				$xml->add_tag('title', $thread['prefix_plain'] . unhtmlspecialchars($thread['title']));
				$xml->add_tag('author', unhtmlspecialchars($thread['postusername']));
				$xml->add_tag('date', vbdate($vbulletin->options['dateformat'], $thread['dateline']));
				$xml->add_tag('time', vbdate($vbulletin->options['timeformat'], $thread['dateline']));
			$xml->close_group('thread');
		}
	}
	$xml->close_group('source');
	$output .= $xml->output();
	unset($xml);
}
else if (in_array($vbulletin->GPC['type'], array('RSS', 'RSS1', 'RSS2')))
{ // RSS output
	// setup the board title

	if (empty($title))
	{ // just show board title
		$rsstitle = $vbulletin->options['bbtitle'];
	}
	else
	{ // show board title plus selection
		$rsstitle = $vbulletin->options['bbtitle'] . " - $title";
	}
	$rssicon = create_full_url(vB_Template_Runtime::fetchStyleVar('imgdir_misc') . '/rss.png');

	$headers[] = 'Pragma:'; // VBIV-8269 
	$headers[] = 'Cache-control: max-age=' . $expires;
	$headers[] = 'Expires: ' . gmdate("D, d M Y H:i:s", $expires) . ' GMT';
	$headers[] = 'Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastmodified) . ' GMT';
	$headers[] = 'ETag: "' . $cachehash . '"';
	$headers[] = 'Content-Type: text/xml' . (vB_Template_Runtime::fetchStyleVar('charset') != '' ? '; charset=' .  vB_Template_Runtime::fetchStyleVar('charset') : '');

	$output = '<?xml version="1.0" encoding="' . vB_Template_Runtime::fetchStyleVar('charset') . '"?>' . "\r\n\r\n";

	# Each specs shared code is entered in full (duplicated) to make it easier to read
	switch($vbulletin->GPC['type'])
	{
		case 'RSS':
			require_once(DIR . '/includes/class_xml.php');
			$xml = new vB_XML_Builder($vbulletin);
			$xml->add_group('rss', array('version' => '0.91'));
				$xml->add_group('channel');
					$xml->add_tag('title', $rsstitle);
					$xml->add_tag('link', $vbulletin->options['bburl'] . '/', array(), false, true);
					$xml->add_tag('description', $description);
					$xml->add_tag('language', vB_Template_Runtime::fetchStyleVar('languagecode'));
					$xml->add_group('image');
						$xml->add_tag('url', $rssicon);
						$xml->add_tag('title', $rsstitle);
						$xml->add_tag('link', $vbulletin->options['bburl'] . '/', array(), false, true);
					$xml->close_group('image');
		break;
		case 'RSS1':
			if ($externalcache <= 60)
			{
				$updateperiod = 'hourly';
				$updatefrequency = round(60 / $externalcache);
			}
			else
			{
				$updateperiod = 'daily';
				$updatefrequency = round(1440 / $externalcache);
			}

			require_once(DIR . '/includes/class_xml.php');
			$xml = new vB_XML_Builder($vbulletin);
			$xml->add_group('rdf:RDF', array(
				'xmlns:rdf'     => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
				'xmlns:dc'      => 'http://purl.org/dc/elements/1.1/',
				'xmlns:syn'     => 'http://purl.org/rss/1.0/modules/syndication/',
				'xmlns:content' => 'http://purl.org/rss/1.0/modules/content/',
				'xmlns'         => 'http://purl.org/rss/1.0/',
			));

			$xml->add_group('channel', array(
				'rdf:about' => $vbulletin->options['bburl']
			));
				$xml->add_tag('title', $rsstitle);
				$xml->add_tag('link', $vbulletin->options['bburl'] . '/', array(), false, true);
				$xml->add_tag('description', $description);
				$xml->add_tag('syn:updatePeriod', $updateperiod);
				$xml->add_tag('syn:updateFrequency', $updatefrequency);
				$xml->add_tag('syn:updateBase', '1970-01-01T00:00Z');
				$xml->add_tag('dc:language', vB_Template_Runtime::fetchStyleVar('languagecode'));
				$xml->add_tag('dc:creator', 'vBulletin');
				$xml->add_tag('dc:date', gmdate('Y-m-d\TH:i:s') . 'Z');
				$xml->add_group('items');
					$xml->add_group('rdf:Seq');
						$xml->add_tag('rdf:li', '', array('rdf:resource' => $vbulletin->options['bburl'] . '/'));
					$xml->close_group('rdf:Seq');
				$xml->close_group('items');
				$xml->add_group('image');
					$xml->add_tag('url', $rssicon);
					$xml->add_tag('title', $rsstitle);
					$xml->add_tag('link', $vbulletin->options['bburl'] . '/', array(), false, true);
				$xml->close_group('image');
			$xml->close_group('channel');

			if (!$vbulletin->GPC['nohtml'])
			{
				require_once(DIR . '/includes/class_postbit.php');
				$postbit_factory = new vB_Postbit_Factory();
				$postbit_factory->registry =& $vbulletin;
				$postbit_factory->bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list($vbulletin->options['bburl'] . '/'));
				$postbit_factory->bbcode_parser->printable = true;
			}
			require_once(DIR . '/includes/class_bbcode_alt.php');

		break;
		case 'RSS2':
			require_once(DIR . '/includes/class_xml.php');
			$xml = new vB_XML_Builder($vbulletin);
			$rsstag = array(
				'version'       => '2.0',
				'xmlns:dc'      => 'http://purl.org/dc/elements/1.1/',
				'xmlns:content' => 'http://purl.org/rss/1.0/modules/content/'
			);
			if ($podcastinfo)
			{
				$rsstag['xmlns:itunes'] = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
			}
			$xml->add_group('rss', $rsstag);
				$xml->add_group('channel');
					$xml->add_tag('title', $rsstitle);
					$xml->add_tag('link', $vbulletin->options['bburl'] . '/', array(), false, true);
					$xml->add_tag('description', $description);
					$xml->add_tag('language', vB_Template_Runtime::fetchStyleVar('languagecode'));
					$xml->add_tag('lastBuildDate', gmdate('D, d M Y H:i:s') . ' GMT');
					#$xml->add_tag('pubDate', gmdate('D, d M Y H:i:s') . ' GMT');
					$xml->add_tag('generator', 'vBulletin');
					$xml->add_tag('ttl', $externalcache);
					$xml->add_group('image');
						$xml->add_tag('url', $rssicon);
						$xml->add_tag('title', $rsstitle);
						$xml->add_tag('link', $vbulletin->options['bburl'] . '/', array(), false, true);
					$xml->close_group('image');
					if ($podcastinfo['subtitle'])
					{
						$xml->add_tag('itunes:subtitle', $podcastinfo['subtitle']);
					}
					if ($podcastinfo['author'])
					{
						$xml->add_tag('itunes:author', $podcastinfo['author']);
					}
					if ($podcastinfo['summary'])
					{
						$xml->add_tag('itunes:summary', $podcastinfo['summary']);
					}
					if ($podcastinfo['owneremail'] OR $podcasinfo['ownername'])
					{
						$xml->add_group('itunes:owner');
							if ($podcastinfo['ownername'])
							{
								$xml->add_tag('itunes:name', $podcastinfo['ownername']);
							}
							if ($podcastinfo['owneremail'])
							{
								$xml->add_tag('itunes:email', $podcastinfo['owneremail']);
							}
						$xml->close_group('itunes:owner');
					}
					if ($podcastinfo['image'])
					{
						$xml->add_tag('itunes:image', '', array('href' => $podcastinfo['image']));
					}
					if ($podcastinfo['keywords'])
					{
						$xml->add_tag('itunes:keywords', $podcastinfo['keywords']);
					}
					if ($podcastinfo['category'])
					{
						if ($category = unserialize($podcastinfo['category']))
						{
							if (count($category) == 1)
							{
								$xml->add_tag('itunes:category', '', array('text' => $category[0]));
							}
							else
							{
								$xml->add_group('itunes:category', array('text' => array_shift($category)));
								foreach($category AS $cat)
								{
									$xml->add_tag('itunes:category', '', array('text' => $cat));
								}
								$xml->close_group('itunes:category');
							}
						}
					}
					if ($podcastinfo)
					{
						$xml->add_tag('itunes:explicit', $podcastinfo['explicit'] == 1 ? 'yes' : 'no');
					}

			if (!$vbulletin->GPC['nohtml'])
			{
				require_once(DIR . '/includes/class_postbit.php');
				$postbit_factory = new vB_Postbit_Factory();
				$postbit_factory->registry =& $vbulletin;
				$postbit_factory->bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list($vbulletin->options['bburl'] . '/'));
				$postbit_factory->bbcode_parser->printable = true;
			}
			require_once(DIR . '/includes/class_bbcode_alt.php');
		break;
	}

	$i = 0;
	$viewattachedimages = $vbulletin->options['viewattachedimages'];
	$attachthumbs = $vbulletin->options['attachthumbs'];

	// list returned threads
	if (!empty($threadcache))
	{
		foreach ($threadcache AS $thread)
		{
			switch($vbulletin->GPC['type'])
			{
				case 'RSS':
					$xml->add_group('item');
					$xml->add_tag('title', $thread['prefix_plain'] . unhtmlspecialchars($thread['title']));
					$xml->add_tag('link', fetch_seo_url('thread|fullurl|nosession', $thread, array('goto' => 'newpost')), array(), false, true);
					$xml->add_tag('description', "$vbphrase[forum]: " . unhtmlspecialchars($vbulletin->forumcache["$thread[forumid]"]['title_clean']) . "\r\n$vbphrase[posted_by]: " . unhtmlspecialchars($thread['postusername']) . "\r\n" .
					construct_phrase($vbphrase['post_time_x_at_y'], vbdate($vbulletin->options['dateformat'], $thread['dateline']), vbdate($vbulletin->options['timeformat'], $thread['dateline'])));
					$xml->close_group('item');
					break;

				case 'RSS1':
    				$xml->add_group('item', array('rdf:about' => fetch_seo_url('thread|fullurl|nosession', $thread)));
    				$xml->add_tag('title', $thread['prefix_plain'] . unhtmlspecialchars($thread['title']));
    				$xml->add_tag('link', fetch_seo_url('thread|nosession|fullurl|js', $thread, array('goto' => 'newpost')), array(), false, true);

					$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list($vbulletin->options['bburl'] . '/'));
					$plainmessage = $plaintext_parser->parse($thread['message'], $thread['forumid']);
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
						$thread['attachments'] = $attachmentcache['byattachment'];
						$thread['allattachments'] = $attachmentcache['bycontent'][$thread['postid']];
						$forumperms = fetch_permissions($thread['forumid']);
						$postbit_factory->thread =& $thread;
						$postbit_factory->cache = array();
						if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['cangetattachment']))
						{
							$vbulletin->options['viewattachedimages'] = 0;
							$vbulletin->options['attachthumbs'] = 0;
						}
						else
						{
							$vbulletin->options['viewattachedimages'] = $viewattachedimages;
							$vbulletin->options['attachthumbs'] = $attachthumbs;
						}
						$postbit_obj =& $postbit_factory->fetch_postbit('external');
						$message = $postbit_obj->construct_postbit($thread);
						$xml->add_tag('content:encoded', $thread['message'] ? $message : '');
					}

						$xml->add_tag('dc:date', gmdate('Y-m-d\TH:i:s', $thread['dateline']) . 'Z');
						$xml->add_tag('dc:creator', unhtmlspecialchars($thread['postusername']));
						$xml->add_tag('dc:subject', unhtmlspecialchars($vbulletin->forumcache["$thread[forumid]"]['title_clean']));
					$xml->close_group('item');
					break;

				case 'RSS2':
					$xml->add_group('item');
					$xml->add_tag('title', $thread['prefix_plain'] . unhtmlspecialchars($thread['title']));
					$xml->add_tag('link', fetch_seo_url('thread|nosession|fullurl|js', $thread, array('goto' => 'newpost')), array(), false, true);
					$xml->add_tag('pubDate', gmdate('D, d M Y H:i:s', $thread['dateline']) . ' GMT');

					$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list($vbulletin->options['bburl'] . '/'));
					$plainmessage = $plaintext_parser->parse($thread['message'], $thread['forumid']);
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
						$thread['attachments'] = $attachmentcache['byattachment'];
						$thread['allattachments'] = $attachmentcache['bycontent'][$thread['postid']];
						
						$forumperms = fetch_permissions($thread['forumid']);
						$postbit_factory->thread =& $thread;
						$postbit_factory->cache = array();
						if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['cangetattachment']))
						{
							$vbulletin->options['viewattachedimages'] = 0;
							$vbulletin->options['attachthumbs'] = 0;
						}
						else
						{
							$vbulletin->options['viewattachedimages'] = $viewattachedimages;
							$vbulletin->options['attachthumbs'] = $attachthumbs;
						}
						$postbit_obj =& $postbit_factory->fetch_postbit('external');
						$message = $postbit_obj->construct_postbit($thread);
						$xml->add_tag('content:encoded', $thread['message'] ? $message : '');
						unset($message);
					}

					$xml->add_tag('category', unhtmlspecialchars($vbulletin->forumcache["$thread[forumid]"]['title_clean']), array('domain' => fetch_seo_url('forum|fullurl|nosession', array('forumid' => $thread['forumid'], 'title' => $vbulletin->forumcache["$thread[forumid]"]['title']))));
					$xml->add_tag('dc:creator', unhtmlspecialchars($thread['postusername']));
					$xml->add_tag('guid', fetch_seo_url('thread|fullurl|nosession', $thread), array('isPermaLink' => 'true'));

					if ($vbulletin->options['rsspodcast'] AND $podcastinfo)
					{
						$xml->add_tag('itunes:explicit', $thread['explicit'] == 1 ? 'yes' : 'no');
						if ($thread['keywords'])
						{
							$xml->add_tag('itunes:keywords', $thread['keywords']);
						}
						if ($thread['subtitle'])
						{
							$xml->add_tag('itunes:subtitle', $thread['subtitle']);
						}
						if ($thread['author'])
						{
							$xml->add_tag('itunes:author', $thread['author']);
						}
						if ($thread['url'])
						{
							switch(file_extension($thread['url']))
							{
								case 'mp3':
									$type = 'audio/mpg';
									break;
								case 'm4a':
									$type = 'audio/x-m4a';
									break;
								case 'mp4':
									$type = 'video/mp4';
									break;
								case 'm4v':
									$type = 'video/x-m4v';
									break;
								case 'mov':
									$type = 'video/quicktime';
									break;
								case 'pdf':
									$type = 'application/pdf';
									break;
								default:
									$type = 'unknown/unknown';
							}

							$xml->add_tag('enclosure', '', array(
								'url'    => $thread['url'],
								'length' => $thread['length'],
								'type'   => $type
							));
						}
						else if ($attachmentcache['bycontent']["$thread[postid]"])
						{
							$type = 'unknown/unknown';
							$attach = array_shift($attachmentcache['bycontent']["$thread[postid]"]);
							$mimetype = unserialize($attach['mimetype']);
							foreach ($mimetype AS $header)
							{
								if (preg_match('#Content-type:(.*)$#si', $header, $matches))
								{
									$type = trim($matches[1]);
									break;
								}
							}
							if ((strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' AND stristr($_SERVER['SERVER_SOFTWARE'], 'apache') === false) OR (strpos(SAPI_NAME, 'cgi') !== false AND @!ini_get('cgi.fix_pathinfo')))
							{
								$filename = $vbulletin->options['bburl'] . "/attachment.php?attachmentid=$attach[attachmentid]&amp;dateline=$attach[dateline]&amp;filename=" . urlencode($attach['filename']);
							}
							else
							{
								$filename = $vbulletin->options['bburl'] . "/attachment.php/$attach[attachmentid]/" . urlencode($attach['filename']);
							}
							$xml->add_tag('enclosure', '', array(
								'url'    => $filename,
								'length' => $attach['filesize'],
								'type'   => $type
							));
						}
					}

					$xml->close_group('item');
					break;
			}
		}
	}

	switch($vbulletin->GPC['type'])
	{
		case 'XML':
		case 'JS':
			break;
		case 'RSS1':
			$xml->close_group('rdf:RDF');
			$output .= $xml->output();
			unset($xml);
			break;
		case 'RSS':
			$output .= '<!DOCTYPE rss PUBLIC "-//RSS Advisory Board//DTD RSS 0.91//EN" "http://www.rssboard.org/rss-0.91.dtd">' . "\r\n";
				$xml->close_group('channel');
			$xml->close_group('rss');
			$output .= $xml->output();
			unset($xml);
			break;
		case 'RSS2':
				$xml->close_group('channel');
			$xml->close_group('rss');
			$output .= $xml->output();
			unset($xml);
	}
}

$insert_cache = true;
($hook = vBulletinHook::fetch_hook('external_complete')) ? eval($hook) : false;

if ($insert_cache)
{
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "externalcache
			(cachehash, dateline, text, headers, forumid)
		VALUES
			(
				'" . $db->escape_string($cachehash) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($output) . "',
				'" . $db->escape_string(serialize($headers)) . "',
				" . intval($podcastforumid) . "
			)
	");
}
$db->close();

foreach ($headers AS $header)
{
	header($header);
}
echo $output;

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 58176 $
|| ####################################################################
\*======================================================================*/
?>