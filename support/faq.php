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
define('THIS_SCRIPT', 'faq');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('fronthelp');

// get special data templates from the datastore
$specialtemplates = array('products');

// pre-cache templates used by all actions
$globaltemplates = array(
	'FAQ',
	'faqbit',
	'faqbit_link'
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_faq.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// #############################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'main';
}

// initialize some important arrays
$displayorder = array();
$ifaqcache = array();
$faqcache = array();

// initialize some template bits
$faqbits = '';
$faqlinks = '';
$navbits[''] = $vbphrase['faq'];

($hook = vBulletinHook::fetch_hook('faq_start')) ? eval($hook) : false;

// #############################################################################

if ($_REQUEST['do'] == 'search')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'q'            => TYPE_STR,
		'match'        => TYPE_STR,
		'titleandtext' => TYPE_BOOL
	));

	if ($vbulletin->GPC['q'] == '')
	{
		eval(standard_error(fetch_error('searchspecifyterms')));
	}

	$phraseIds = array();		// array to store phraseids of phrases to search
	$whereText = array();		// array to store 'text LIKE(something)' entries
	$faqnames = array();		// array to store FAQ shortnames that match the search query
	$find = array();			// array to store all find words

	$phrasetypeSql = (!$vbulletin->GPC['titleandtext']) ? "= 'faqtitle'" : "IN('faqtitle', 'faqtext')";

	// get a list of phrase ids to search in
	$query = "
		SELECT phraseid, fieldname, varname
		FROM " . TABLE_PREFIX . "phrase
		WHERE languageid IN(-1, 0, " . LANGUAGEID . ")
			AND fieldname $phrasetypeSql
		ORDER BY languageid
	";

	$phrases = $db->query_read_slave($query);
	while ($phrase = $db->fetch_array($phrases))
	{
		$phraseIds["{$phrase['varname']}_{$phrase['fieldname']}"] = $phrase['phraseid'];
	}
	unset($phrase);
	$db->free_result($phrases);

	switch($vbulletin->GPC['match'])
	{
		case 'all':
			$match = 'all';
			$search = preg_split('#[ \r\n\t]+#', $vbulletin->GPC['q']);
			$matchSql = ' AND ';
			break;
		case 'phr':
			$match = 'phr';
			$search = array($vbulletin->GPC['q']);
			$matchSql = ' ';
			break;
		default: // any
			$match = 'any';
			$search = preg_split('#[ \r\n\t]+#',$vbulletin->GPC['q']);
			$matchSql = ' OR ';
			break;
	}

	foreach ($search AS $word)
	{
		if (strlen($word) == 1)
		{
			// searches happen anywhere within a word, so 1 letter searches are useless
			continue;
		}

		$find[] = preg_quote($word, '#'); // -> '#(?<=[^\w=]|^)(\w*($word)\w*)(?=[^\w=]|$)#siU'

		$whereText[] = "text LIKE('%" . $db->escape_string_like($word) . "%')";
	}

	$activeproducts = array(
		'', 'vbulletin'
	);
	foreach ($vbulletin->products AS $product => $active)
	{
		if ($active)
		{
			$activeproducts[] = $product;
		}
	}

	if (!empty($whereText))
	{
		$phrases = $db->query_read_slave("
			SELECT varname AS faqname, fieldname
			FROM " . TABLE_PREFIX . "phrase AS phrase
			WHERE phraseid IN(" . implode(', ', $phraseIds) . ")
				AND product IN ('" . implode('\', \'', $activeproducts) . "')
				AND (" . implode($matchSql, $whereText) . ")
		");
		if (!$db->num_rows($phrases))
		{
			eval(standard_error(fetch_error('searchnoresults', $displayCommon)));
		}
	}
	else
	{
		eval(standard_error(fetch_error('searchnoresults', $displayCommon)));
	}

	while ($phrase = $db->fetch_array($phrases))
	{
		$faqcache["$phrase[faqname]"] = $phrase;
		$ifaqcache['faqroot']["$phrase[faqname]"] =& $faqcache["$phrase[faqname]"];
	}

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('faq_search_query')) ? eval($hook) : false;

	$faqs = $db->query_read_slave("
		SELECT faqname, faqparent, phrase.text AS title
			$hook_query_fields
		FROM " . TABLE_PREFIX . "faq AS faq
		INNER JOIN " . TABLE_PREFIX . "phrase AS phrase ON(phrase.fieldname = 'faqtitle' AND phrase.varname = faq.faqname)
		$hook_query_joins
		WHERE phrase.languageid IN(-1, 0, " . LANGUAGEID . ")
			AND (faqparent IN('" . implode("', '", array_keys($faqcache)) . "')
				OR faqname IN('" . implode("', '", array_keys($faqcache)) . "'))
			$hook_query_where
	");
	if (!$db->num_rows($faqs))
	{
		eval(standard_error(fetch_error('searchnoresults', $displayCommon)));
	}
	while ($faq = $db->fetch_array($faqs))
	{
		$faqcache["$faq[faqname]"] = $faq;
		if ($ifaqcache['faqroot']["$faq[faqname]"] != '')
		{
			$ifaqcache['faqroot']["$faq[faqname]"] =& $faqcache["$faq[faqname]"];
		}
		else
		{
			$ifaqcache["$faq[faqparent]"]["$faq[faqname]"] =& $faqcache["$faq[faqname]"];
		}
	}
	unset($faq);
	$db->free_result($faqs);

	fetch_faq_text_array($ifaqcache['faqroot']);

	require_once(DIR . '/includes/functions_misc.php');

	$faqparent = 'faqroot';
	foreach ($ifaqcache['faqroot'] AS $faqname => $faq)
	{
		$text = str_replace(array("\\'", '\\\\$'), array("'", '\\$'), addslashes($faq['text']));

		eval('$faq[\'text\'] = "' . replace_template_variables($text) . '";');
		construct_faq_item($faq, $find);
	}

	$q = htmlspecialchars_uni($vbulletin->GPC['q']);

	// construct navbits
	$navbits = array(
		'faq.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['faq'],
		'' => $vbphrase['search_results']
	);
}

// #############################################################################

if ($_REQUEST['do'] == 'main')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'faq'	=> TYPE_STR
	));

	// get parent variable
	if ($vbulletin->GPC['faq'] == '')
	{
		$faqparent = 'faqroot';
	}
	else
	{
		$faqparent = preg_replace('#\W#', '', $vbulletin->GPC['faq']);
	}

	// set initial navbar entry
	if ($faqparent == 'faqroot')
	{
		$navbits[''] = $vbphrase['faq'];
	}
	else
	{
		$navbits['faq.php' . $vbulletin->session->vars['sessionurl_q']] = $vbphrase['faq'];
	}

	cache_ordered_faq(false, true);

	// get bits for faq text cache
	$faqtext = array();
	if (is_array($ifaqcache["$faqparent"]))
	{
		fetch_faq_text_array($ifaqcache["$faqparent"]);
	}
	else
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['faq_item'], $vbulletin->options['contactuslink'])));
	}

	// $censorchars is used in the vb_censor_explain phrase
	$censorchars = $vbulletin->options['censorchar'] . $vbulletin->options['censorchar'] . $vbulletin->options['censorchar'] . $vbulletin->options['censorchar'] . $vbulletin->options['censorchar'];

	require_once(DIR . '/includes/functions_misc.php');

	// display FAQs
	$faq = array();
	foreach ($ifaqcache["$faqparent"] AS $faq)
	{
		if ($faq['displayorder'] > 0)
		{
			$text = str_replace(array("\\'", '\\\\$'), array("'", '\\$'), addslashes($faq['text']));
			eval('$faq[\'text\'] = "' . replace_template_variables($text) . '";');
			construct_faq_item($faq, $find, $replace, $replace);
		}
	}

	$faqtitle = $faqcache["$faqparent"]['title'];
	$show['faqtitle'] = iif ($faqtitle, true, false);

	// get navbar stuff
	$parents = array();
	fetch_faq_parents($faqcache["$faqparent"]['faqname']);
	foreach (array_reverse($parents) AS $key => $val)
	{ // fix for bug #1660
		if (isset($navbits["$key"]))
		{
			unset($navbits["$key"]);
		}
		$navbits["$key"] = $val;
	}

}

// #############################################################################

// parse search <select> options
$checked = array();
if ($_REQUEST['do'] == 'search')
{
	if ($vbulletin->GPC['titleandtext'])
	{
		$checked['titleandtext'] = 'checked="checked"';
	}
	$checked["$match"] = 'checked="checked"';
}
else
{
	$checked['titleandtext'] = 'checked="checked"';
	$checked['all'] = 'checked="checked"';
}

if ($_REQUEST['do'] != 'search' AND $_REQUEST['do'] != 'main')
{
	die();
}

($hook = vBulletinHook::fetch_hook('faq_complete')) ? eval($hook) : false;

$navbits = construct_navbits($navbits);
$navbar = render_navbar_template($navbits);
$templater = vB_Template::create('FAQ');
	$templater->register_page_templates();
	$templater->register('faqbits', $faqbits);
	$templater->register('faqtitle', $faqtitle);
	$templater->register('navbar', $navbar);
	$templater->register('q', $q);
	$templater->register('checked', $checked);
print_output($templater->render());

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>