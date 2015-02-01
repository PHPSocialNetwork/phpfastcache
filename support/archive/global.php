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
define('VB_AREA', 'Archive');

// ###################### Start initialisation #######################
chdir('./../');
define('CWD', (($getcwd = getcwd()) ? $getcwd : '.'));

require_once(CWD . '/includes/init.php');

// ###################### Start headers #######################
exec_headers();

// ############ Some stuff for the gmdate bug ####################
$vbulletin->options['hourdiff'] = (date('Z', TIMENOW) / 3600 - $vbulletin->userinfo['timezoneoffset']) * 3600;

// ###################### Get date / time info #######################
fetch_options_overrides($vbulletin->userinfo);
fetch_time_data();

// ############################################ LANGUAGE STUFF ####################################
// initialize $vbphrase and set language constants
$vbphrase = init_language();

// ###################### Start templates & styles #######################
// allow archive to use a non-english language
$styleid = intval($vbulletin->options['styleid']);

($hook = vBulletinHook::fetch_hook('style_fetch')) ? eval($hook) : false;

$style = $db->query_first_slave("
	SELECT * FROM " . TABLE_PREFIX . "style
	WHERE styleid = $styleid
");
fetch_stylevars($style, $vbulletin->userinfo);

if ((strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' AND stristr($_SERVER['SERVER_SOFTWARE'], 'apache') === false) OR (strpos(SAPI_NAME, 'cgi') !== false AND @!ini_get('cgi.fix_pathinfo')))
{
	define('SLASH_METHOD', false);
}
else
{
	define('SLASH_METHOD', true);
}

if (SLASH_METHOD)
{
	$archive_info = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
}
else
{
	$archive_info = $_SERVER['QUERY_STRING'];
}

if ($vbulletin->session->visible)
{
	if (SLASH_METHOD)
	{
		define('ARCHIVE_SESSION_URL', '?s=' . $vbulletin->session->vars['sessionhash']);
	}
	else
	{
		define('ARCHIVE_SESSION_URL', '&amp;s=' . $vbulletin->session->vars['sessionhash']);
	}
}
else
{
	define('ARCHIVE_SESSION_URL', '');
}

// check to see if server is too busy. this is checked at the end of session.php
if ((server_overloaded() AND $vbulletin->userinfo['usergroupid'] != 6) OR $vbulletin->options['archiveenabled'] == 0)
{
	exec_header_redirect(fetch_seo_url('forumhome|bburl', array()));
}

// #############################################################################
// ### CACHE PERMISSIONS AND GRAB $permissions
// get the combined permissions for the current user
// this also creates the $fpermscache containing the user's forum permissions
$permissions = cache_permissions($vbulletin->userinfo);
$vbulletin->userinfo['permissions'] =& $permissions;
// #############################################################################

// check that board is active - if not admin, then display error
if ((!$vbulletin->options['bbactive'] AND !($permissions['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])) OR !($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
{
	exec_header_redirect(fetch_seo_url('forumhome|bburl', array()));
}

// if password is expired, deny access
if ($vbulletin->userinfo['userid'] AND $permissions['passwordexpires'])
{
	$passworddaysold = floor((TIMENOW - $vbulletin->userinfo['passworddate']) / 86400);

	if ($passworddaysold >= $permissions['passwordexpires'])
	{
		exec_header_redirect(fetch_seo_url('forumhome|bburl', array()));
	}
}

verify_ip_ban();

$cache_templates = array('ad_archive_above_content1', 'ad_archive_above_content2', 'ad_archive_below_content');

($hook = vBulletinHook::fetch_hook('archive_global')) ? eval($hook) : false;

cache_templates($cache_templates, $style['templatelist']);
unset($cache_templates);

// #########################################################################################
// ###################### ARCHIVE FUNCTIONS ################################################

// function to list forums in their correct order and nesting
function print_archive_forum_list($parentid = -1, $indent = '')
{
	global $vbulletin;

	$output = '';

	if (empty($vbulletin->iforumcache))
	{
		$forums = $vbulletin->db->query_read_slave("
			SELECT forumid, title, link, parentid, displayorder, title_clean, description, description_clean,
			(options & " . $vbulletin->bf_misc_forumoptions['cancontainthreads'] . ") AS cancontainthreads
			FROM " . TABLE_PREFIX . "forum AS forum
			WHERE displayorder <> 0 AND
			password = '' AND
			(options & " . $vbulletin->bf_misc_forumoptions['active'] . ")
			ORDER BY displayorder
		");
		$vbulletin->iforumcache = array();
		while ($forum = $vbulletin->db->fetch_array($forums))
		{
			$vbulletin->iforumcache["$forum[parentid]"]["$forum[displayorder]"]["$forum[forumid]"] = $forum;
		}
		unset($forum);
		$vbulletin->db->free_result($forums);
	}

	if (is_array($vbulletin->iforumcache["$parentid"]))
	{
		foreach($vbulletin->iforumcache["$parentid"] AS $x)
		{
			foreach($x AS $forumid => $forum)
			{
				($hook = vBulletinHook::fetch_hook('archive_forum')) ? eval($hook) : false;

				if (!($vbulletin->userinfo['forumpermissions']["$forumid"] & $vbulletin->bf_ugp_forumpermissions['canview']) AND ($vbulletin->forumcache["$forumid"]['showprivate'] == 1 OR (!$vbulletin->forumcache["$forumid"]['showprivate'] AND !$vbulletin->options['showprivateforums'])))
				{
					continue;
				}
				else
				{
					if ($forum['cancontainthreads'] OR $forum['link'] !== '')
					{
						$link = ($forum['link'] !== '' ? $forum['link'] : $vbulletin->options['bburl'] . '/archive/index.php' .
							(SLASH_METHOD ? '/' : '?') . "f-$forumid.html" . ARCHIVE_SESSION_URL );
						$forum_link = '<a href="' . $link . '">';
					}
					else
					{
						$forum_link = "<a style=\"font-weight:bold\">";
					}
					$output .= "$indent\t<li>$forum_link$forum[title_clean]</a>" . print_archive_forum_list($forumid, "\t$indent") . "</li>\n";
				}
			}
		}
		// only add to $output if there were actual forums
		if (!empty($output))
		{
			$output = "\n$indent<ul>\n" . $output . "$indent</ul>\n$indent";
		}
	}

	return $output;
}

// function to draw the navbar for the archive pages
function print_archive_navigation($foruminfo, $threadinfo='')
{
	global $vbulletin, $vbphrase, $pda, $querystring;

	$navarray = array('<a href="' . $vbulletin->options['bburl'] . '/archive/index.php' .
		$vbulletin->session->vars['sessionurl_q'] . '">' . $vbulletin->options['bbtitle'] . '</a>');

	if (!empty($foruminfo))
	{
		foreach(array_reverse(explode(',', substr($foruminfo['parentlist'], 0, -3))) AS $forumid)
		{
			if ($threadinfo == '' AND $forumid == $foruminfo['forumid'])
			{
				$navarray[] = $vbulletin->forumcache["$forumid"]['title_clean'];
			}
			else
			{
				$navarray[] = "<a href=\"" . $vbulletin->options['bburl'] . '/archive/index.php' . (SLASH_METHOD ? '/' : '?')
					. "f-$forumid.html" . ARCHIVE_SESSION_URL . "\">" . $vbulletin->forumcache["$forumid"]['title_clean'] . "</a>";
			}
		}
		if (is_array($threadinfo))
		{
			$navarray[] = $threadinfo['prefix_plain_html'] . ' ' . $threadinfo['title'];
		}
	}

	if (SLASH_METHOD)
	{
		$loginlink = 'index.php' . (!empty($querystring) ? "/$querystring" : '') . '?' . $vbulletin->session->vars['sessionurl'] . 'login=1';
		$pdalink = 'index.php' . (!empty($querystring) ? "/$querystring" : '') . '?' . $vbulletin->session->vars['sessionurl'] . 'pda=1';
	}
	else
	{
		$loginlink = 'index.php?' . (!empty($querystring) ? "$querystring&amp;" : '') . $vbulletin->session->vars['sessionurl'] . 'login=1';
		$pdalink = 'index.php?' . (!empty($querystring) ? "$querystring&amp;" : '') . $vbulletin->session->vars['sessionurl'] . 'pda=1';
	}

	if ($pda)
	{
		if ($vbulletin->userinfo['userid'] == 0)
		{
			$extra = '<div class="pda"><a href="' . $vbulletin->options['bburl'] . "/archive/$loginlink" . '" rel="nofollow">' . $vbphrase['log_in'] . "</a></div>\n";
		}
	}
	else
	{
		$extra = '<div class="pda"><a href="' . $vbulletin->options['bburl'] . "/archive/$pdalink" . '" rel="nofollow">' . $vbphrase['pda'] . "</a></div>\n";
	}

	$return = '<div id="navbar">' . implode(' &gt; ', $navarray) . "</div>\n<hr />\n" . $extra;

	($hook = vBulletinHook::fetch_hook('archive_navigation')) ? eval($hook) : false;

	return $return;
}

// this function seems to only be used in project.php, and is thus probably no longer needed.
function print_archive_navbar($navbits = array())
{
	global $vbulletin, $vbphrase, $pda, $querystring;

	$navarray = array('<a href="' . $vbulletin->options['bburl'] . '/index.php' .
							$vbulletin->session->vars['sessionurl_q'] . '">' . $vbulletin->options['bbtitle'] . '</a>');

	foreach ($navbits AS $url => $navbit)
	{
		if ($url)
		{
			$navarray[] = "<a href=\"" . htmlspecialchars_uni($url) .
							$vbulletin->session->vars['sessionurl_q'] . "\">$navbit</a>";
		}
		else
		{
			$navarray[] = $navbit;
		}
	}

	if (SLASH_METHOD)
	{
		$loginlink = 'index.php' . (!empty($querystring) ? "/$querystring" : '') . '?' . $vbulletin->session->vars['sessionurl'] . 'login=1';
		$pdalink = 'index.php' . (!empty($querystring) ? "/$querystring" : '') . '?' . $vbulletin->session->vars['sessionurl'] . 'pda=1';
	}
	else
	{
		$loginlink = 'index.php?' . (!empty($querystring) ? "$querystring&amp;" : '') . $vbulletin->session->vars['sessionurl'] . 'login=1';
		$pdalink = 'index.php?' . (!empty($querystring) ? "$querystring&amp;" : '') . $vbulletin->session->vars['sessionurl'] . 'pda=1';
	}

	if ($pda)
	{
		if ($vbulletin->userinfo['userid'] == 0)
		{
			$extra = '<div class="pda"><a href="' . $vbulletin->options['bburl'] . "/archive/$loginlink" . '" rel="nofollow">' . $vbphrase['log_in'] . "</a></div>\n";
		}
	}
	else
	{
		$extra = '<div class="pda"><a href="' . $vbulletin->options['bburl'] . "/archive/$pdalink" . '" rel="nofollow">' . $vbphrase['pda'] . "</a></div>\n";
	}

	$return = '<div id="navbar">' . implode(' &gt; ', $navarray) . "</div>\n<hr />\n" . $extra;

	($hook = vBulletinHook::fetch_hook('archive_navigation')) ? eval($hook) : false;

	return $return;
}

// function to draw the page links for the archive pages
function print_archive_page_navigation($total, $perpage, $link)
{
	global $p, $vbphrase, $vbulletin;

	$output = '';
	$numpages = ceil($total / $perpage);

	if ($numpages > 1)
	{
		$output .= "<div id=\"pagenumbers\"><b>$vbphrase[pages] :</b>\n";

		for ($i=1; $i <= $numpages; $i++)
		{
			if ($i == $p)
			{
				$output .= "[<b>$i</b>]\n";
			}
			else if ($i == 1)
			{
				$output .= '<a href="' . $vbulletin->options['bburl'] . '/archive/index.php' .
					(SLASH_METHOD ? '/' : '?') . "$link.html" . ARCHIVE_SESSION_URL . "\">$i</a>\n";
			}
			else
			{
				$output .= '<a href="' . $vbulletin->options['bburl'] . '/archive/index.php' .
					(SLASH_METHOD ? '/' : '?') . "$link-p-$i.html" . ARCHIVE_SESSION_URL . "\">$i</a>\n";
			}
		}

		$output .= "</div>\n<hr />\n";
	}

	return $output;
}


// #############################################################################
/**
* Returns specified ad template output as an assoc array of title => parsed template
*
* @param	array	Name of templates to be fetched
*
* @return	array	Keyed by template name, value is HTML for template
*/
function fetch_ad_templates(array $templatenames)
{
	global $vbulletin;

	$ad_templates = array();
	foreach ($templatenames AS $template)
	{
		$ad_templates[$template] = vB_Template::create($template)->render();
	}

	return $ad_templates;
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 59007 $
|| ####################################################################
\*======================================================================*/
?>
