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

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

/**
* Returns list of URLs from text
*
* @param	string	Message text
*
* @return	array
*/
function fetch_urls($messagetext)
{
	global $vbulletin;

	preg_match_all('#\[url=("|\'|)?(.*)\\1\](?:.*)\[/url\]|\[url\](.*)\[/url\]#siU', $messagetext, $matches);

	if (!empty($matches))
	{
		$matches = array_merge($matches[2], $matches[3]);
	}

	$urls = array();
	foreach($matches AS $url)
	{
		if (!empty($url))
		{
			if ($temp = $vbulletin->input->parse_url($url))
			{
				if ($temp['port'] == 80)
				{
					unset($temp['port']);
				}
				if (!$temp['scheme'])
				{
					$temp['scheme'] = 'http';
				}
				$urls[] = "$temp[scheme]://$temp[host]" . ($temp['port']	? ":$temp[port]" : '') . "$temp[path]" . ($temp['query'] ? "?$temp[query]" : '');
			}
		}
	}

	return array_unique($urls);
}

/**
* Function for writing to the trackback log
*
* @param string		Pingback, Trackback or None (none is failure before system is established)
* @param string		'in' or 'out' (incoming or outgoing)
* @param integer	Error Code
* @param string		Message from remote server
* @param array		bloginfo
* @param string		URL
*
* @return	mixed	error string on failure, true on success or apparent success
*/
function write_trackback_log($system = 'pingback', $type = 'in', $status = 0, $message = '', $bloginfo = array(), $url = '')
{
	global $vbulletin;

	$vbulletin->db->query_write("
		INSERT INTO " . TABLE_PREFIX . "blog_trackbacklog
		(
			system,
			type,
			status,
			message,
			blogid,
			userid,
			dateline,
			url,
			ipaddress
			)
		VALUES
		(
			'" . (!in_array($system, array('trackback', 'pingback')) ? 'none' : $system) . "',
			'" . ($type == 'in' ? 'in' : 'out') . "',
			" . intval($status) . ",
			'" . $vbulletin->db->escape_string(serialize($message)) . "',
			" . intval($bloginfo['blogid']) . ",
			" . intval($bloginfo['userid']) . ",
			" . TIMENOW . ",
			'" . $vbulletin->db->escape_string(htmlspecialchars_uni($url)) . "',
			" . intval(sprintf('%u', ip2long(IPADDRESS))) . "
		)
	");
}

/**
* Send a pingback / trackback request
*
* @param	array	Bloginfo
* @param	string	Destination URL
* @param	string	Title of the blog
*
* @return	mixed	error string on failure, true on success or apparent success
*/
function send_ping_notification(&$bloginfo, $desturl, $blogtitle)
{
	global $vbulletin;

	if (!intval($bloginfo['blogid']))
	{
		return false;
	}

	//we've had some problems with the SEO friendly titles and outside integration -- particularly with
	//non latin characters in the urls.  Since the push here is around allowing path flexibility I'm not
	//going to borrow trouble and force this to be a basic url (as it was previously).
 	require_once(DIR . '/includes/class_friendly_url.php');
	$ourblogurl = vB_Friendly_Url::fetchLibrary($vbulletin, 'blog|nosession|bburl', $bloginfo);
	$ourblogurl = $ourblogurl->get_url(FRIENDLY_URL_OFF);

	$pingback_dest = '';
	$trackback_dest = $desturl;

	require_once(DIR . '/includes/functions_file.php');
	if ($headresult = fetch_head_request($desturl))
	{
		if (!empty($headresult['x-pingback']))
		{
			$pingback_dest = $headresult['x-pingback'];
		}
		else if ($headresult['http-response']['statuscode'] == 200 AND preg_match('#text\/html#si', $headresult['content-type']))
		{
			// Limit to 5KB
			// Consider adding the ability to Kill the transfer on </head>\s+*<body to class_vurl.php
			if ($bodyresult = fetch_body_request($desturl, 5120))
			{
				// search head for <link rel="pingback" href="pingback server">
				if (preg_match('<link rel="pingback" href="([^"]+)" ?/?>', $bodyresult, $matches))
				{
					$pingback_dest = $matches[1];
				}
				else	if (preg_match('#<rdf:Description((?!<\/rdf:RDF>).)*dc:identifier="' . preg_quote($desturl, '#') . '".*<\/rdf:RDF>#siU', $bodyresult))
				{
					if (preg_match('#<rdf:Description(?:(?!<\/rdf:RDF>).)*trackback:ping="([^"]+)".*<\/rdf:RDF>#siU', $bodyresult, $matches))
					{
						$trackback_dest = trim($matches[1]);
					}
				}
			}
		}

		if (!empty($pingback_dest))
		{
			// Client
			require_once(DIR . '/includes/class_xmlrpc.php');
			$xmlrpc = new vB_XMLRPC_Client($vbulletin);
			$xmlrpc->build_xml_call('pingback.ping', $ourblogurl, $desturl);
			if ($pingresult = $xmlrpc->send_xml_call($pingback_dest))
			{
				require_once(DIR . '/includes/class_xmlrpc.php');
				$xmlrpc_server = new vB_XMLRPC_Server($vbulletin);
				$xmlrpc_server->parse_xml($pingresult['body']);
				$xmlrpc_server->parse_xmlrpc();
			}

			// NOT FINSIHED
			write_trackback_log('pingback', 'out', 0, $pingresult, $bloginfo, $desturl);
			// Not always a success but we can't know for sure
			return true;
		}
		else
		{
			// Client
			require_once(DIR . '/includes/class_trackback.php');
			$tb = new vB_Trackback_Client($vbulletin);
			$excerpt = fetch_censored_text(fetch_trimmed_title(strip_bbcode(strip_quotes($bloginfo['pagetext']), false, true), 255));
			if ($result = $tb->send_ping($trackback_dest, $ourblogurl, $bloginfo['title'], $excerpt, $blogtitle))
			{
				require_once(DIR . '/includes/class_xml.php');
				$xml_object = new vB_XML_Parser($result['body']);
				$xml_object->include_first_tag = true;
				if ($xml_object->parse_xml() AND $xml_object->parseddata['response']['error'] === '0')
				{
					write_trackback_log('trackback', 'out', 0, $result, $bloginfo, $desturl);
					return true;
				}
			}

			write_trackback_log('trackback', 'out', 3, $result, $bloginfo, $desturl);
			// Not always a success but we can't know for sure
			return true;
		}
	}

	write_trackback_log('none', 'out', 1, '', $bloginfo, $desturl);

	return false;
}

/**
* Parse message content for preview
*
* @param	array		Message and disablesmilies options
* @param	string	Parse Type (user, post or comment)
*/
function process_blog_preview($blog, $type, $attachments = NULL)
{
	global $vbulletin, $vbphrase, $show;

	require_once(DIR . '/includes/class_bbcode_blog.php');
	$bbcode_parser = new vB_BbCodeParser_Blog($vbulletin, fetch_tag_list());
	$bbcode_parser->set_parse_userinfo($vbulletin->userinfo, $vbulletin->userinfo['permissions']);
	$bbcode_parser->containerid = $blog['blogid'];
	$bbcode_parser->attachments = $attachments;

	$postpreview = '';
	if ($previewmessage = $bbcode_parser->parse(
		$blog['message'],
		'blog_' . $type,
		$blog['disablesmilies'] ? 0 : 1,
		false,
		'',
		3,
		false,
		$blog['htmlstate']
	))
	{
		switch ($type)
		{
			case 'user':
				$templater = vB_Template::create('blog_cp_modify_profile_preview');
					$templater->register('errorlist', $errorlist);
					$templater->register('newpost', $newpost);
					$templater->register('previewmessage', $previewmessage);
				$postpreview = $templater->render();
				break;
			case 'entry':
			case 'comment':
			case 'usercomment':
				$templater = vB_Template::create('blog_entry_editor_preview');
					$templater->register('blog', $blog);
					$templater->register('previewmessage', $previewmessage);
				$postpreview = $templater->render();
				break;
		}
	}

	return $postpreview;
}

/**
* Construct the 'publish on' select menu
*
* @param	array			Bloginfo array for the entry
* @param	interger|null	Unixtime stamp to use for the date, if null it will use the current time
*
* @return	void
*/
function construct_publish_select($blog, $dateline = NULL)
{
	global $publish_selected;
	$publish_selected = array();

	if ($dateline == NULL)
	{
		$dateline = TIMENOW;
	}
	$date = getdate($dateline);

	$publish_selected = array(
		'hour'		=> vbdate('H', $dateline, false, false),
		'minute'	=> vbdate('i', $dateline, false, false),
		'month'		=> vbdate('n', $dateline, false, false),
		'date'		=> vbdate('d', $dateline, false, false),
		'year'		=> vbdate('Y', $dateline, false, false),
	);

	$publish_selected["$date[mon]"] = ' selected="selected"';

	// check blog status in case we're already processing a preview
	if ($blog['state'] == 'draft' OR $blog['status'] == 'draft')
	{
		$publish_selected['draft'] = ' selected="selected"';
	}
	else if ($dateline > TIMENOW OR $blog['status'] == 'publish_on')
	{
		$publish_selected['publish_on'] = ' selected="selected"';
	}
	else
	{
		$publish_selected['publish_now'] = ' selected="selected"';
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 27303 $
|| ####################################################################
\*======================================================================*/
?>
