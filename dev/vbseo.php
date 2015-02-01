<?php

/************************************************************************************
* vBSEO 3.6.1 for vBulletin v3.x & v4.x by Crawlability, Inc.                       *
*                                                                                   *
* Copyright © 2011, Crawlability, Inc. All rights reserved.                         *
* You may not redistribute this file or its derivatives without written permission. *
*                                                                                   *
* Sales Email: sales@crawlability.com                                               *
*                                                                                   *
*----------------------------vBSEO IS NOT FREE SOFTWARE-----------------------------*
* http://www.crawlability.com/vbseo/license/                                        *
************************************************************************************/

error_reporting(0);
include_once(dirname(__FILE__).'/vbseo/includes/functions_vbseo.php');
include_once(vBSEO_Storage::path('vbinc') . '/' . VBSEO_VB_CONFIG);
vbseo_requested_url();
$vbseo_fullurl = VBSEO_TOPREL . $vbseo_url_;
$vbseo_url_suggest = '';
switch ($vbseo_url_)
{
case 'vbseo.php':
exit;
break;
case 'vbseo_sitemap.php':
case 'vbseocp.php':
case 'cron.php':
case 'cron.html':
include $vbseo_url_;
exit;
break;
}
if (VBSEO_IN_PINGBACK && ($vbseo_url_ == 'vbseo-xmlrpc/'))
{
if (!defined('THIS_SCRIPT'))
@define('THIS_SCRIPT', 'newreply');
include dirname(__FILE__) . '/global.' . VBSEO_VB_EXT;
vbseo_extra_inc('linkback');
vbseo_xmlrpc_proc();
exit;
}
if (VBSEO_REDIRECT_PRIV_EXTERNAL && ($vbseo_url_ == VBSEO_REDIRECT_URI))
{         
$uredir = $_GET['redirect'];
$uredir = preg_replace('#&(?![a-z0-9\#]+;)#si', '&amp;', $uredir);
$uredir = str_replace('"', '&quot;', $uredir);
if (preg_match('#^https?:\/\/#', $uredir) && !preg_match('#["<>]#', $uredir))
{
echo '<html><head><meta http-equiv="refresh" content="0;url=' . $uredir . '"></head><body></body></html>';
exit;
}
}
global $vbseo_gcache, $found_object_ids;
if (VBSEO_RELPATH)
vbseo_chdir($vbseo_relpath);
if (vbseo_security_check($vbseo_url_))
vbseo_404();
$vbseo_file_exists = (file_exists($vbseo_url_) 
|| (file_exists(basename($vbseo_url_)) && strstr($vbseo_url_, '.' . VBSEO_VB_EXT))) 
&& ($vbseo_url_[strlen($vbseo_url_)-1] != '/');
$vbseo_file_exists_deep = file_exists($vbseo_url_) && strstr($vbseo_url_, '/');
$vbseo_found = false;
define('VBSEO_PREPROC', 1);
$vbseo_proc = VBSEO_ENABLED && !defined('VBSEO_UNREG_EXPIRED');
if ($vbseo_proc)
{
preg_match('#^(.+?)(_(?:ltr|rtl)?)(\.gif)$#', $vbseo_url_, $ticom);
$vbseo_url_i = isset($ticom[1]) ? $ticom[1] . $ticom[3] : '';
if (!$vbseo_url_i) $vbseo_url_i = $vbseo_url_;
if (VBSEO_REWRITE_TREE_ICON &&
($gifpos = strpos($vbseo_url_, '.gif')) &&
(substr($vbseo_url_, 0, strlen(VBSEO_ICON_PREFIX)) == VBSEO_ICON_PREFIX) &&
((vbseo_check_url('VBSEO_URL_THREAD_TREE_ICON', substr($vbseo_url_i, strlen(VBSEO_ICON_PREFIX), $gifpos + 4), true)) ||
(vbseo_check_url('VBSEO_URL_FORUM_TREE_ICON', substr($vbseo_url_i, strlen(VBSEO_ICON_PREFIX), $gifpos + 4), true))
)
)
{
if ($vbseo_file_exists)
$vbseo_f = $vbseo_url_;
else
$vbseo_f = str_replace('.gif', $ticom[2] . '.gif', VBSEO_TREE_ICON);
$ifile = @fopen($vbseo_f, 'r');
$image_content = @fread($ifile, filesize($vbseo_f));
@fclose($ifile);
header ('Content-type: image/gif');
header ('Content-Length: ' . strlen($image_content));
echo $image_content;
exit();
}
else
if (VBSEO_CHECK_WWWDOMAIN && !strstr($_SERVER['HTTP_HOST'], 'www.') && !strstr($_SERVER['HTTP_HOST'], 'localhost'))
{
vbseo_get_options();
if (strstr($vboptions['bburl'], 'www.'))
{
vbseo_safe_redirect($vboptions['bburl'] . '/' . $vbseo_requrl);
}
}
$vbseo_is_arc = (
preg_match('#^(' . preg_quote(VBSEO_ARCHIVE_ROOT, '#') . '(?:index\.' . VBSEO_VB_EXT . '[/\?]?)?)((\w+\.'.VBSEO_VB_EXT.'.)?[^/]*)/?$#', '/' . $vbseo_requrl . '/', $arcm) || 
preg_match('#^(/archive/(?:index\.' . VBSEO_VB_EXT . '[/\?]?)?)(.*)#', '/' . $vbseo_requrl, $arcm));
$vbseo_move_tohp = ($hp_list = VBSEO_HOMEPAGE_ALIASES) &&
preg_match('#^(' . str_replace('\|', '|', preg_quote($hp_list, '#')) . ')$#', $vbseo_requrl);
if ($vbseo_move_tohp)
{
vbseo_get_options();
if (VBSEO_HOMEPAGE != $vbseo_requrl)
vbseo_safe_redirect(VBSEO_HOMEPAGE);
}
else
if ($vbseo_is_arc)
{
preg_match('#t-(\d+)(?:-p-(\d+))?#', $arcm[2], $tidm);
$thread_id = $tidm[1];
$page = $tidm[2] ? $tidm[2] : 1;
if ($thread_id && VBSEO_REDIRECT_ARCHIVE)
vbseo_get_options();
global $bbuserinfo;
if ($thread_id && VBSEO_REDIRECT_ARCHIVE && !$_COOKIE[vbseo_vb_cprefix() . 'pda'])
{
vbseo_prepare_seo_replace();
vbseo_get_forum_info();
$threadids = array($thread_id);
vbseo_get_thread_info($threadids);
$vbseo_url_ = (VBSEO_REWRITE_THREADS ? vbseo_thread_url($thread_id, $page) : 'showthread.' . VBSEO_VB_EXT . '?' . VBSEO_THREADID_URI . '=' . $thread_id);
vbseo_safe_redirect($vbseo_url_);
}
else
if (preg_match('#\.css$#', $vbseo_requrl))
{
$vbseo_url_ = $vbseo_found_fn = 'archive/' . $arcm[2];
$vbseo_found = $vbseo_file_exists = true;
}
else
if ($arcm[1] != VBSEO_ARCHIVE_ROOT || !strstr('/' . $vbseo_requrl, $arcm[1]))
{
vbseo_safe_redirect(VBSEO_ARCHIVE_ROOT . $arcm[2], array(), true);
}
else
{
$_fixp = function_exists('ini_get') ? @ini_get('cgi.fix_pathinfo') : @get_cfg_var('cgi.fix_pathinfo');
$sm = //(// VBSEO_VB35X && 0&& (substr(PHP_OS, 0, 3) == 'WIN' AND stristr($_SERVER['SERVER_SOFTWARE'], 'apache') === false) OR (strpos(@php_sapi_name(), 'cgi') !== false AND !$_fixp))
((strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' AND stristr($_SERVER['SERVER_SOFTWARE'], 'apache') === false) OR (strpos(SAPI_NAME, 'cgi') !== false AND @!ini_get('cgi.fix_pathinfo')))
? '?' : '/';
$arcscript = preg_match('#(\w+)\.'.VBSEO_VB_EXT.'#', $arcm[2], $asm) ? $asm[1] : 'index';
if(!file_exists('archive/'.$arcscript.'.'.VBSEO_VB_EXT)) $arcscript = 'index';
vbseo_chdir('archive/');
$vbseo_stop = VBSEO_TOPREL . 'archive'.($asm?'':'/'.$arcscript.'.' . VBSEO_VB_EXT ). $sm . $arcm[2];
vbseo_set_self($vbseo_stop);
if ($sm == '?')
$_SERVER['QUERY_STRING'] = $arcm[2];
define('VBSEO_BASE_URL', substr(VBSEO_ARCHIVE_ROOT, 0, strlen(VBSEO_ARCHIVE_ROOT)-1));
preg_match('#f-(\d+)#', $arcm[2], $tidm);
if ($tidm[1])
{
vbseo_get_options();
vbseo_get_forum_info();
if (isset($vbseo_gcache['forum']) && $vbseo_gcache['forum'] && !isset($vbseo_gcache['forum'][$tidm[1]]))
{
if (VBSEO_404_HANDLE == 2)
{
$vbseo_incf = VBSEO_404_CUSTOM;
if ($vbseo_incf[0] != '/')
$vbseo_incf = dirname(__FILE__) . '/' . $vbseo_incf;
include($vbseo_incf);
exit;
}
else
vbseo_404_routine($vbseo_url_);
}
}
ob_start();
include (dirname(__FILE__) . '/archive/'.$arcscript.'.' . VBSEO_VB_EXT);
$output = ob_get_contents();
ob_end_clean();
$GLOBALS['vbseo_notop_url'] = true;
$output = make_crawlable($output);
echo $output;
exit();
}
}
if (VBSEO_IS_ROBOT)
{
$vbseo_non_clean = array('pp', 'highlight', 'order', 'sort', 'daysprune', 'referrerid');
foreach($vbseo_non_clean as $vbseo_nn)
if (isset($_GET[$vbseo_nn]))
vbseo_safe_redirect($vbseo_url_, $vbseo_non_clean);
}
if (VBSEO_THREAD_301_REDIRECT && !$_POST && $vbseo_file_exists)
{
if(VBSEO_SITEMAP_MOD && VBSEO_IS_ROBOT)
{
vbseo_hit_log(VBSEO_BASEURL);
}
$vbseo_noproc = true;
if (!VBSEO_RELPATH && !$vbseo_file_exists_deep)
{
$vbseo_noproc = false;
if (VBSEO_REWRITE_BLOGS_ATT && (VBSEO_BASEURL == 'blog_attachment.' . VBSEO_VB_EXT))
{
if ($_REQUEST['attachmentid'])
{
vbseo_get_blogatt_info(array($_REQUEST['attachmentid']));
if($_batt = $vbseo_gcache['battach'][$_REQUEST['attachmentid']])
{
vbseo_get_user_info(array($_batt['userid']));
vbseo_get_blog_info(array($_batt['blogid']));
$vbse_rurl = vbseo_blog_url(VBSEO_URL_BLOG_ATT, $_GET);
$excpars = array('attachmentid', 'd', 'thumb', 'stc');
}
}
if($vbse_rurl)
vbseo_url_autoadjust($vbse_rurl, $excpars, false);
}else
if (VBSEO_REWRITE_BLOGS_TAGS_ENTRY && (VBSEO_BASEURL == 'blog_tag.' . VBSEO_VB_EXT))
{
if(count($_GET) == 0)
vbseo_safe_redirect(vbseo_blog_url(VBSEO_URL_BLOG_TAGS_HOME, $_GET));
}else
if (VBSEO_REWRITE_BLOGS && (VBSEO_BASEURL == 'blog.' . VBSEO_VB_EXT
|| (VBSEO_BASEURL == 'entry.' . VBSEO_VB_EXT && $_GET['b'])
) )
{                         
if(isset($_GET['userid']))
$_GET['u'] = $_GET['userid'];
if ($_GET['u'])
vbseo_get_user_info(array($_GET['u']));
$red_url_ = '';
if(count($_GET) == 0)
{
$red_url_ = vbseo_blog_url(VBSEO_URL_BLOG_HOME, $_GET);
}else
if ($_GET[VBSEO_BLOG_CATID_URI])
{
if(VBSEO_REWRITE_BLOGS_CAT)
{
vbseo_get_blog_cats($_GET[VBSEO_BLOG_CATID_URI]);
$red_url_ = vbseo_blog_url(
$_GET['u'] ? ($_GET['page'] ? VBSEO_URL_BLOG_CAT_PAGE : VBSEO_URL_BLOG_CAT)
: ($_GET['page'] ? VBSEO_URL_BLOG_GLOB_CAT_PAGE : VBSEO_URL_BLOG_GLOB_CAT), 
$_GET);
}
}
else
if($_GET['tag'])
{
if(VBSEO_REWRITE_BLOGS_TAGS_ENTRY && !$_GET['u'])
$red_url_ = vbseo_blog_url($_GET['page'] ? VBSEO_URL_BLOG_TAGS_ENTRY_PAGE : VBSEO_URL_BLOG_TAGS_ENTRY, $_GET);
}
else
if($_GET['cp'])
{
if(VBSEO_REWRITE_BLOGS_CUSTOM)
{
$found_object_ids['blogcp_ids'] = $_GET['cp'];
vbseo_get_object_info('blogcp_ids');
vbseo_get_user_info($vbseo_gcache['blogcp_ids'][$_GET['cp']]['userid']);
$red_url_ = vbseo_blog_url(VBSEO_URL_BLOG_CUSTOM, $_GET);
}
}else
if ($_GET['u'] && !$_GET['page'] && !$_GET['do'])
{
$red_url_ = vbseo_blog_url(VBSEO_URL_BLOG_USER, $_GET);
}
else
if (($_GET['b']||$_GET['blogid']) && count($_GET) == 1)
{
if(VBSEO_REWRITE_BLOGS_ENT)
{
vbseo_get_blog_info(array($_GET['b']?$_GET['b']:$_GET['blogid']));
$red_url_ = vbseo_blog_url(VBSEO_URL_BLOG_ENTRY, $_GET);
}
}
else
if (VBSEO_REWRITE_BLOGS_LIST && $_GET['do'] == 'comments' && !$_GET['type'] && !$_GET['u'])
$red_url_ = vbseo_blog_url($_GET['page'] ? VBSEO_URL_BLOG_CLIST_PAGE : VBSEO_URL_BLOG_CLIST, $_GET);
else
if (VBSEO_REWRITE_BLOGS_LIST && !$_GET['u'] && $_GET['do'] == 'list' && (!$_GET['blogtype'] || in_array($_GET['blogtype'], array('latest', 'recent'))))
{
if($_GET['span']=='24')
$newurl = vbseo_blog_url(
$_GET['page'] ? VBSEO_URL_BLOG_LAST_ENT_PAGE : VBSEO_URL_BLOG_LAST_ENT ,
$_GET);
else
if ($_GET['d'])
$red_url_ = vbseo_blog_url($_GET['page'] ? VBSEO_URL_BLOG_DAY_PAGE : VBSEO_URL_BLOG_DAY, $_GET);
else
if ($_GET['m'])
$red_url_ = vbseo_blog_url($_GET['page'] ? VBSEO_URL_BLOG_MONTH_PAGE : VBSEO_URL_BLOG_MONTH, $_GET);
else
$red_url_ = vbseo_blog_url($_GET['page'] ? VBSEO_URL_BLOG_LIST_PAGE : VBSEO_URL_BLOG_LIST, $_GET);
}
else
if (VBSEO_REWRITE_BLOGS_LIST && $_GET['do'] == 'bloglist')
$red_url_ = vbseo_blog_url($_GET['page'] ? VBSEO_URL_BLOG_BLIST_PAGE : VBSEO_URL_BLOG_BLIST, $_GET);
if ($red_url_)
vbseo_safe_redirect($red_url_, 
array(VBSEO_USERID_URI, 'userid', VBSEO_BLOG_CATID_URI, 'b', 'do', 'page', 'blogid', 'blogtype', 
'd', 'm', 'y', 'tag', 'cp'));
}
else
if (((VBSEO_REWRITE_THREADS && (VBSEO_BASEURL == 'showthread.' . VBSEO_VB_EXT)) ||
(VBSEO_REWRITE_PRINTTHREAD && ($print = 1) && (VBSEO_BASEURL == 'printthread.' . VBSEO_VB_EXT)))
)
{
$newurl = '';
if (isset($_GET['goto']))
{
if ($_GET['goto'] == 'nextnewest')
$vbseo_format = VBSEO_URL_THREAD_NEXT;
elseif ($_GET['goto'] == 'nextoldest')
$vbseo_format = VBSEO_URL_THREAD_PREV;
if ($vbseo_format)
{
define('THIS_SCRIPT', 'showthread');
vbseo_get_options();
vbseo_prepare_seo_replace();
vbseo_get_forum_info();
$threadid = $_GET[VBSEO_THREADID_URI];
vbseo_get_thread_info($threadid);
$newurl = vbseo_thread_url($threadid, '', $vbseo_format);
}
}
else
if (!isset($_REQUEST['do']))
{
define('THIS_SCRIPT', 'showthread');
$threadid = $_GET[VBSEO_THREADID_URI] ? $_GET[VBSEO_THREADID_URI] : $_GET['threadid'];
$r_post_id = $_GET[VBSEO_POSTID_URI] ? $_GET[VBSEO_POSTID_URI] : ($_GET['postid'] ? $_GET['postid'] : $_GET['post']);
$r_post_id = preg_replace('|#.*$|', '', $r_post_id);
$newurl = '';
if ($r_post_id)
{
define('VBSEO_PRIVATE_REDIRECT_POSTID', $r_post_id);
}
else
if ($threadid)
{
vbseo_get_options();
vbseo_prepare_seo_replace();
vbseo_get_forum_info();
vbseo_get_thread_info($threadid);
$newurl = vbseo_thread_url($threadid,
(VBSEO_ENABLE_GARS && $_GET[VBSEO_PAGENUM_URI_GARS]) ? $_GET[VBSEO_PAGENUM_URI_GARS] : $_GET['page'],
(VBSEO_ENABLE_GARS && $_GET[VBSEO_PAGENUM_URI_GARS]) ? VBSEO_URL_THREAD_GARS_PAGENUM :
($print ? (($_GET['page'] + 0 > 1) ? VBSEO_URL_THREAD_PRINT_PAGENUM : VBSEO_URL_THREAD_PRINT) : '')
);
}
}
if ($newurl)
{
$tinfo = $vbseo_gcache['thread'][$threadid];
$is_public = vbseo_forum_is_public($vbseo_gcache['forum'][$tinfo['forumid']]);
if ($is_public)
vbseo_safe_redirect($newurl,
array(VBSEO_ENABLE_GARS?VBSEO_PAGENUM_URI_GARS:'', VBSEO_THREADID_URI, 'threadid', 'postid', 'page',
'viewfull',
($_GET['pp'] == $vboptions['maxposts'])?'pp':''
));
else
{
define('VBSEO_PRIVATE_REDIRECT_URL', $newurl);
define('VBSEO_PRIVATE_REDIRECT_THREAD', $threadid);
}
}
}
else
if (VBSEO_REWRITE_SHOWPOST && (VBSEO_BASEURL == 'showpost.' . VBSEO_VB_EXT) && !$_POST)
{
define('THIS_SCRIPT', 'showpost');
vbseo_get_options();
vbseo_prepare_seo_replace();
$r_post_id = $_GET[VBSEO_POSTID_URI];
vbseo_get_forum_info();
if (VBSEO_POSTBIT_PINGBACK == 2)
$found_object_ids['prepostthread_ids'] = array($r_post_id);
vbseo_get_post_thread_info($r_post_id, true);
vbseo_get_thread_info($found_object_ids['postthreads']);
if (VBSEO_POSTBIT_PINGBACK == 2)
$vbseo_url_ = vbseo_thread_url_postid($r_post_id);
else
$vbseo_url_ = vbseo_post_url($r_post_id, $_GET['postcount']);
if($vbseo_url_)
vbseo_safe_redirect($vbseo_url_, array(VBSEO_POSTID_URI, 'postcount'));
else
{
$vbseo_found_fn = 'showpost.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
}
else
if (VBSEO_REWRITE_ATTACHMENTS && (VBSEO_BASEURL == 'attachment.' . VBSEO_VB_EXT))
{
if ($attid = $_REQUEST['attachmentid'])
{
vbseo_get_forum_info();
vbseo_get_attachments_info($attid);
$ainfo = $vbseo_gcache['attach'][$attid];
$is_public = false;
if(vbseo_content_type($ainfo) == 'forum')
{   
$found_object_ids['prepostthread_ids'] = array($ainfo['contentid']);
vbseo_get_post_thread_info($ainfo['contentid'], true);
vbseo_get_thread_info($found_object_ids['postthreads']);
}
$newurl = vbseo_attachment_url($attid, '', $_REQUEST['d'], $_REQUEST['thumb']);
$strip_params = array('attachmentid', 'd', 'thumb');
if($newurl) 
define('VBSEO_PRIVATE_REDIRECT_URL', $newurl);
else
{
$vbseo_found_fn = 'attachment.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
}
}
else
if (VBSEO_REWRITE_MEMBER_LIST && (VBSEO_BASEURL == 'memberlist.' . VBSEO_VB_EXT))
{
if (!in_array($_REQUEST['do'], array('search', 'process')))
{
$vbseo_url_ = vbseo_memberlist_url($_GET['ltr'], $_GET[VBSEO_PAGENUM_URI]);
$strip_params = array('ltr', 'do', VBSEO_PAGENUM_URI);
if ($_GET['sort'] == VBSEO_DEFAULT_MEMBERLIST_SORT) $strip_params[] = 'sort';
if ($_GET['order'] == VBSEO_DEFAULT_MEMBERLIST_ORDER) $strip_params[] = 'order';
vbseo_safe_redirect($vbseo_url_, $strip_params);
}
}
else
if (VBSEO_REWRITE_MEMBERS && (VBSEO_BASEURL == 'member.' . VBSEO_VB_EXT))
{
$newurl = '';
if (!$_GET['find'])
{
$userid = $_GET[VBSEO_USERID_URI] ? $_GET[VBSEO_USERID_URI] : $_GET['userid'];
if(!$userid && $_GET['username'])
{
$userid = vbseo_reverse_username($_GET['username']);
}
if ($userid)
{
vbseo_get_user_info(array($userid));
$uunset = array();
if($_GET['vmid'])
{
vbseo_get_options();
$_GET['page'] = vbseo_vmsg_pagenum($userid, $_GET['vmid']);
$_GET['tab']  = 'visitor_messaging';
}
if($_GET['tab'] == 'visitor_messaging' && $_GET['page']>1)
{
$newurl = vbseo_member_url($_GET['u'], '', 'VBSEO_URL_MEMBER_MSGPAGE', 
array('%page%'=>$_GET['page']));
}else
if($_GET['tab'] == 'friends' && $_GET['page']>1)
{
$newurl = vbseo_member_url($_GET['u'], '', 'VBSEO_URL_MEMBER_FRIENDSPAGE', 
array('%page%'=>$_GET['page']));
}else
if(!$_GET['action'] || ($_GET['action']=='getinfo'))
{
$newurl = vbseo_member_url($userid);
/*
if($_GET['tab'])
{
$uunset[] = 'tab';
$uunset[] = 'page';
}
*/
}
if($newurl)
{
if($_GET['tab'] && $_GET['tab'] != 'visitor_messaging')
$newurl .= '#'.$_GET['tab'];
vbseo_safe_redirect($newurl, array_diff(
array(VBSEO_USERID_URI, 'action', 'userid', 'username', 'tab', 'page', 'pp', 'vmid'),
$uunset));
}
}
}
else
if ($_GET['find'] == 'lastposter')
{
$found_object_ids['forum_last'] = array($_GET[VBSEO_FORUMID_URI]);
vbseo_get_options();
vbseo_get_forum_info();
if ($_GET[VBSEO_FORUMID_URI])
{
$userid = $vbseo_gcache['forum'][$_GET[VBSEO_FORUMID_URI]]['lastposter'];
}
else
{
vbseo_get_thread_info($_GET[VBSEO_THREADID_URI]);
$userid = $vbseo_gcache['thread'][$_GET[VBSEO_THREADID_URI]]['lastposter'];
}
vbseo_get_user_info(array(), array($userid));
$vbseo_url_ = vbseo_member_url(0, $userid);
vbseo_safe_redirect($vbseo_url_, array(VBSEO_FORUMID_URI, 'find', VBSEO_THREADID_URI));
}
}
else
if(VBSEO_REWRITE_MEMBERS && (VBSEO_BASEURL == 'converse.' . VBSEO_VB_EXT) )
{
vbseo_get_user_info(array($_GET['u'],$_GET['u2']));
$newurl = vbseo_member_url($_GET['u'], '', 
$_GET['page']?'VBSEO_URL_MEMBER_CONVPAGE':'VBSEO_URL_MEMBER_CONV', 
array(), $_GET
);
if($newurl)
vbseo_safe_redirect($newurl, array('u', 'u2', 'page', 'vmid'));
}
else
if(VBSEO_REWRITE_MALBUMS && (VBSEO_BASEURL == 'album.' . VBSEO_VB_EXT) )
{                 
if(($_GET['do'] == 'latest') && $_GET['page'])
{
$newurl = vbseo_generic_url(VBSEO_URL_MEMBER_ALBUM_HOME_PAGE, $_GET);
}else
if(!isset($_GET['do']))
{
if(isset($_GET['commentid']))
{
vbseo_get_options();
$_GET['page'] = vbseo_pic_pagenum($_GET[VBSEO_PICID_URI], $_GET['commentid']);
}
if(isset($_GET[VBSEO_PICID_URI]) && ($_vpid=$_GET[VBSEO_PICID_URI]))
{
$found_object_ids[VBSEO_PIC_STORAGE][] = $_vpid;
vbseo_get_object_info(VBSEO_PIC_STORAGE);
$_vaid = $_GET['albumid'] ? $_GET['albumid'] : $vbseo_gcache[VBSEO_PIC_STORAGE][$_vpid]['albumid'];
$found_object_ids['album'][] = $_vaid;
vbseo_get_object_info('album');
vbseo_get_user_info(array($vbseo_gcache['album'][$_vaid]['userid']));
$newurl = vbseo_album_url(
$_GET['page']>1?'VBSEO_URL_MEMBER_PICTURE_PAGE':'VBSEO_URL_MEMBER_PICTURE', $_GET);
}else
if(isset($_GET['albumid']) && (count($_GET)==1 || $_GET['page']))
{
$found_object_ids['album'][] = $_GET['albumid'];
vbseo_get_object_info('album');
vbseo_get_user_info(array($vbseo_gcache['album'][$_GET['albumid']]['userid']));
$newurl = vbseo_album_url($_GET['page'] ? 'VBSEO_URL_MEMBER_ALBUM_PAGE' : 'VBSEO_URL_MEMBER_ALBUM', $_GET);
}
else
if(isset($_GET['u']) && (count($_GET)==1||$_GET['page']))
{
vbseo_get_user_info(array($_GET['u']));
$newurl = vbseo_album_url($_GET['page'] ? 'VBSEO_URL_MEMBER_ALBUMS_PAGE' : 'VBSEO_URL_MEMBER_ALBUMS', $_GET);
}
else
if(count($_GET) == 0)
{
$newurl = vbseo_any_url(VBSEO_URL_MEMBER_ALBUM_HOME);
}
}
if($newurl)
vbseo_safe_redirect($newurl, array('u', 'do', 'albumid', VBSEO_PICID_URI, 'commentid','page'));
}
else
if((VBSEO_REWRITE_MALBUMS||VBSEO_REWRITE_GROUPS) && (VBSEO_BASEURL == 'picture.' . VBSEO_VB_EXT) 
&& !isset($_GET['do']) && isset($_GET[VBSEO_PICID_URI]))
{                 
if(VBSEO_REWRITE_MALBUMS && isset($_GET['albumid']))
{
$found_object_ids[VBSEO_PIC_STORAGE][] = $_GET[VBSEO_PICID_URI];
vbseo_get_object_info(VBSEO_PIC_STORAGE);
$found_object_ids['album'][] = $_GET['albumid'];
vbseo_get_object_info('album');
vbseo_get_user_info(array($vbseo_gcache['album'][$_GET['albumid']]['userid']));
$newurl = vbseo_album_url('VBSEO_URL_MEMBER_PICTURE_IMG', $_GET);
}
if(VBSEO_REWRITE_GROUPS && isset($_GET['groupid']))
{
$found_object_ids[VBSEO_PIC_STORAGE][] = $_GET[VBSEO_PICID_URI];
vbseo_get_object_info(VBSEO_PIC_STORAGE);
$found_object_ids['groups'][] = $_GET['groupid'];
vbseo_get_group_info($found_object_ids['groups']);
$newurl = vbseo_group_url(VBSEO_URL_GROUPS_PICTURE_IMG, $_GET);
}
if($newurl)
vbseo_safe_redirect($newurl, array(), true);
}else
if (VBSEO_REWRITE_ANNOUNCEMENT && (VBSEO_BASEURL == 'announcement.' . VBSEO_VB_EXT) && !isset($_GET['do']))
{
define('THIS_SCRIPT', 'announcement');
vbseo_get_options();
vbseo_prepare_seo_replace();
vbseo_get_forum_info();
$r_forum_id = $_GET['f'] ? $_GET['f'] : $_GET['forumid'];
$r_ann_id = $_GET['a'] ? $_GET['a'] : $_GET['announcementid'];
if (!$r_forum_id && $r_ann_id)
{
$fanna = vbseo_get_forum_announcement(0, $r_ann_id);
$anna = $fanna['announcement'];
$r_forum_id = $fanna['forumid'];
}
vbseo_get_forum_announcement($r_forum_id);
$newurl = vbseo_announcement_url($r_forum_id, $r_ann_id);
if ($newurl)
{
$is_public = vbseo_forum_is_public($vbseo_gcache['forum'][$r_forum_id], '', 1);
if ($is_public)
vbseo_safe_redirect($newurl, array(VBSEO_FORUMID_URI, 'forumid', 'a', 'announcementid'));
else
{
define('VBSEO_PRIVATE_REDIRECT_SUGGEST', $newurl);
}
}
}
else
if (VBSEO_REWRITE_FORUM && (VBSEO_BASEURL == 'forumdisplay.' . VBSEO_VB_EXT))
{
define('THIS_SCRIPT', 'forumdisplay');
$r_forum_id = $_GET['f'] ? $_GET['f'] : $_GET['forumid'];
$vbseo_newurl = '';
$vbseo_unset_arr = array(VBSEO_FORUMID_URI, 'forumid', 'page');
if ($r_forum_id == 'home')
{
$vbseo_newurl = '';
}elseif (preg_match('#^\d+$#', $r_forum_id))
{
vbseo_get_options();
vbseo_prepare_seo_replace();
vbseo_get_forum_info();
if ((($vbseo_gcache['forum'][$r_forum_id]['daysprune'] == $_GET['daysprune']) && !$_GET['order']
) || !$_GET['daysprune'])
$vbseo_unset_arr[] = 'daysprune';
if ((!$_GET['sort'] || $_GET['sort'] == VBSEO_DEFAULT_FORUMDISPLAY_SORT) && $_GET['order'] == VBSEO_DEFAULT_FORUMDISPLAY_ORDER)
{
$vbseo_unset_arr[] = 'sort';
$vbseo_unset_arr[] = 'order';
}
$vbseo_newurl = vbseo_forum_url($r_forum_id, $_GET['page']);
}
if(!$vbseo_newurl || strstr($vbseo_newurl, 'forumdisplay.'.VBSEO_VB_EXT))
{
vbseo_set_self ('forumdisplay.' . VBSEO_VB_EXT);
require ('forumdisplay.' . VBSEO_VB_EXT);
exit();
}
if ($vbseo_newurl)
{
$is_public = vbseo_forum_is_public($vbseo_gcache['forum'][$r_forum_id]);
if ($is_public)
vbseo_safe_redirect($vbseo_newurl, $vbseo_unset_arr);
else
{
$globaltemplates = $phrasegroups = $specialtemplates = array();
vbseo_vars_push('r_forum_id','vbseo_newurl','vbseo_unset_arr');
include 'global.' . VBSEO_VB_EXT;
vbseo_vars_pop();
vbseo_get_options();
vbseo_get_forum_info();
$is_public = vbseo_forum_is_public($vbseo_gcache['forum'][$r_forum_id], 0, true);
if ($is_public)
vbseo_safe_redirect($vbseo_newurl, $vbseo_unset_arr);
}
}
}
else
$vbseo_noproc = true;
}
if ($vbseo_noproc)
{
if (!isset($vbseo_crules))
{
$vbseo_crules = array();
foreach($GLOBALS['vbseo_custom_rules'] as $k => $v)
if ($k)
$vbseo_crules['#' . str_replace(array('#', '&'), array('\#', '&(?:amp;)?'), $k) . '#'] = str_replace('[NF]', '', $v);
}
if ($vbseo_crules)
{
$newurl = preg_replace(array_keys($vbseo_crules), $vbseo_crules, ($vbseo_relpath && ($sp = strpos($vbseo_requrl,$vbseo_url_))) ? substr($vbseo_requrl, $sp) : $vbseo_requrl);
if (($vbseo_requrl != $newurl )&& !strstr($newurl, $vbseo_url_))
{
if ($vbseo_relpath && !strstr($newurl, $vbseo_relpath))
$newurl = $vbseo_relpath . $newurl;
vbseo_safe_redirect($newurl, array(), 1);
}
}
}
} 
if (!$vbseo_file_exists && !$vbseo_file_exists_deep)
{     
$c301_fw = vbseo_fw_customurl('301');
$vbseo_url_2 = $c301_fw ? preg_replace(array_keys($c301_fw), $c301_fw, $vbseo_requrl) : $vbseo_requrl;
if ($vbseo_url_2 != $vbseo_requrl)
{
vbseo_safe_redirect($vbseo_url_2, array(), true );
}
else
if ($vbseo_url_2 = vbseo_back_customurl($vbseo_url_, 'rules'))
{
vbseo_set_self($vbseo_relpath . $vbseo_url_2);
$vbseo_purl = parse_url(preg_replace('#\?.*$#', '', $vbseo_url_2));
$vbseo_found_fn = $vbseo_purl['path'];
$vbseo_found = true;
}
if (!$vbseo_found && !VBSEO_RELPATH)
{
if (vbseo_checkurl_blog($vbseo_url_, 1))
{
}
else
if (vbseo_checkurl_cms($vbseo_url_, 1))
{
}
else
if (VBSEO_REWRITE_POLLS && $vbseo_arr = vbseo_check_url('VBSEO_URL_POLL', $vbseo_url_))
{
vbseo_set_self('poll.' . VBSEO_VB_EXT . '?' . VBSEO_ACTION_URI . '=showresults&' . VBSEO_POLLID_URI . '=' . $vbseo_arr['poll_id']);
$vbseo_found_fn = 'poll.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
else
if (VBSEO_REWRITE_ATTACHMENTS &&
(substr($vbseo_url_, 0, strlen(VBSEO_ATTACHMENTS_PREFIX)) == VBSEO_ATTACHMENTS_PREFIX) && 
$vbseo_arr = vbseo_check_url('VBSEO_URL_ATTACHMENT', substr($vbseo_url_, strlen(VBSEO_ATTACHMENTS_PREFIX)))
)
{
preg_match('#^(\d+)(d\d+)?(t)?#', $vbseo_arr['attachment_id'], $atm);
vbseo_set_self('attachment.' . VBSEO_VB_EXT . '?attachmentid=' . $atm[1] . (isset($atm[3])?'&thumb=1&stc=1':''));
$vbseo_found_fn = 'attachment.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
else
if (VBSEO_REWRITE_SHOWPOST && $vbseo_arr = vbseo_check_url('VBSEO_URL_POST_SHOW', $vbseo_url_))
{
if (VBSEO_POSTBIT_PINGBACK == 2)
{
vbseo_get_options();
vbseo_prepare_seo_replace();
vbseo_get_forum_info();
$r_post_id = $vbseo_arr['post_id'];
$found_object_ids['prepostthread_ids'] = array($r_post_id);
vbseo_get_post_thread_info($r_post_id, true);
vbseo_get_thread_info($found_object_ids['postthreads']);
$vbseo_url_ = vbseo_thread_url_postid($r_post_id);
vbseo_safe_redirect($vbseo_url_, array(VBSEO_POSTID_URI, 'postcount'));
}
vbseo_set_self('showpost.' . VBSEO_VB_EXT . '?' . VBSEO_POSTID_URI . '=' . $vbseo_arr['post_id'] . '&postcount=' . $vbseo_arr['post_count']);
$vbseo_found_fn = 'showpost.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
else
if (VBSEO_REWRITE_THREADS &&
($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_NEWPOST', $vbseo_url_)) ||
($vbseo_arr2 = $vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_LASTPOST', $vbseo_url_))
)
{
define('THIS_SCRIPT', 'showthread');
vbseo_set_self($q = 'showthread.' . VBSEO_VB_EXT . '?' . VBSEO_THREADID_URI . '=' . $vbseo_arr['thread_id'] . '&goto=' . (isset($vbseo_arr2) ? 'lastpost' : 'newpost'));
define('VBSEO_GT_POST', $q);
if (1 || defined('VBSEO_NEW_LAST_POST_COOKIE') && VBSEO_NEW_LAST_POST_COOKIE)
{
$postid = 0;
}
else
{
vbseo_vars_push('vbseo_arr','vbseo_arr2');
$globaltemplates = $phrasegroups = $specialtemplates = array();
include 'global.' . VBSEO_VB_EXT;
vbseo_vars_pop();
vbseo_get_options();
vbseo_prepare_seo_replace();
vbseo_get_forum_info();
$postid = $vbseo_arr2 ? vbseo_get_last_post($vbseo_arr['thread_id']) : vbseo_get_new_post($vbseo_arr['thread_id']);
}
if ($postid)
{
$tmode = $_COOKIE[vbseo_vb_cprefix() . "threadedmode"];
if ($tmode == 'threaded' || $tmode == 'hybrid')
{
$vbseo_found_fn='showthread.'.VBSEO_VB_EXT;
$vbseo_found = true;
}
else
{
$found_object_ids['prepostthread_ids'] = array($postid);
vbseo_get_post_thread_info($postid, true);
vbseo_get_thread_info($found_object_ids['postthreads']);
$vbseo_url_ = vbseo_thread_url_postid($postid);
$ti = $vbseo_gcache['thread'][$vbseo_arr['thread_id']];
if ($GAS_settings &&
preg_match('#\b' . $ti['forumid'] . '\b#', $GAS_settings['forums'])
)
{
$_SERVER['QUERY_STRING'] = 'conly=1';
}
vbseo_safe_redirect($vbseo_url_, array(VBSEO_THREADID_URI, 'goto'));
}
}
else
{                      
$vbseo_found_fn = 'showthread.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
}
else
if (VBSEO_REWRITE_THREADS &&
($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_GOTOPOST_PAGENUM', $vbseo_url_)) ||
($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_GOTOPOST', $vbseo_url_))
)
{                   
vbseo_set_self('showthread.' . VBSEO_VB_EXT . '?' . VBSEO_POSTID_URI . '=' . $vbseo_arr['post_id'] .
($vbseo_arr['thread_page'] > 1? '&' . VBSEO_PAGENUM_URI . '=' . $vbseo_arr['thread_page'] :''));
$vbseo_found_fn = 'showthread.' . VBSEO_VB_EXT;
$vbseo_found = true;
define('VBSEO_PRIVATE_REDIRECT_POSTID', $vbseo_arr['post_id']);
}
else
if (VBSEO_REWRITE_THREADS &&
($vbseo_arr2 = $vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_NEXT', $vbseo_url_)) ||
($vbseo_arr3 = $vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_PREV', $vbseo_url_))
)
{        
vbseo_get_forum_info();
vbseo_prepare_seo_replace();
vbseo_get_thread_info($vbseo_arr['thread_id']);
$nthread = vbseo_get_next_thread($vbseo_arr['thread_id'], $vbseo_arr3?true:false);
if ($nthread['threadid'])
{
vbseo_get_thread_info($nthread['threadid']);
$vbseo_url_ = vbseo_thread_url($nthread['threadid']);
vbseo_safe_redirect($vbseo_url_);
}
else
{                   
vbseo_set_self('showthread.' . VBSEO_VB_EXT . '?' . VBSEO_THREADID_URI . '=' . $vbseo_arr['thread_id'] . '&goto=' . ($vbseo_arr3?'nextoldest':'nextnewest'));
$vbseo_found_fn = 'showthread.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
}
else
if (VBSEO_REWRITE_MEMBER_LIST && $vbseo_arr = vbseo_check_url('VBSEO_URL_MEMBERLIST_PAGENUM', $vbseo_url_))
{
vbseo_set_self('memberlist.' . VBSEO_VB_EXT . '?' . ($vbseo_arr['page'] > 1?VBSEO_PAGENUM_URI . '=' . $vbseo_arr['page']:''));
$vbseo_found_fn = 'memberlist.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
else
if (VBSEO_REWRITE_MEMBER_LIST && $vbseo_arr = vbseo_check_url('VBSEO_URL_MEMBERLIST', $vbseo_url_))
{
vbseo_set_self('memberlist.' . VBSEO_VB_EXT);
$vbseo_found_fn = 'memberlist.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
else
if (VBSEO_REWRITE_MEMBER_LIST && $vbseo_arr = vbseo_check_url('VBSEO_URL_MEMBERLIST_LETTER', $vbseo_url_))
{
if ($vbseo_arr['letter'] == '0') $vbseo_arr['letter'] = '%23';
vbseo_set_self('memberlist.' . VBSEO_VB_EXT . '?ltr=' . strtoupper($vbseo_arr['letter']) . ($vbseo_arr['page'] > 1 ? '&' . VBSEO_PAGENUM_URI . '=' . $vbseo_arr['page']:''));
$vbseo_found_fn = 'memberlist.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
else
if (VBSEO_REWRITE_GROUPS && vbseo_check_multi_urls(
array(
'VBSEO_URL_GROUPS_ALL',
'VBSEO_URL_GROUPS_ALL_PAGE',
'VBSEO_URL_GROUPS_CATEGORY_LIST_PAGE',
'VBSEO_URL_GROUPS_CATEGORY_LIST',
'VBSEO_URL_GROUPS_DISCUSSION_PAGE',
'VBSEO_URL_GROUPS_DISCUSSION',
'VBSEO_URL_GROUPS_MEMBERS_PAGE',
'VBSEO_URL_GROUPS_MEMBERS',
'VBSEO_URL_GROUPS_PIC_PAGE',
'VBSEO_URL_GROUPS_PIC',
'VBSEO_URL_GROUPS_PICTURE_PAGE',
'VBSEO_URL_GROUPS_PICTURE',
'VBSEO_URL_GROUPS_PICTURE_IMG',
'VBSEO_URL_GROUPS_CATEGORY_PAGE',
'VBSEO_URL_GROUPS_CATEGORY',
'VBSEO_URL_GROUPS_PAGE', 
'VBSEO_URL_GROUPS',
'VBSEO_URL_GROUPS_HOME',
), 
$vbseo_url_) )
{
}
else
if (VBSEO_REWRITE_TAGS && vbseo_check_multi_urls(
array(
'VBSEO_URL_TAGS_ENTRYPAGE', 
'VBSEO_URL_TAGS_ENTRY', 
'VBSEO_URL_TAGS_HOME',
), 
$vbseo_url_) )
{
}
else
if (vbseo_checkurl_cms($vbseo_url_, 2))
{                 
}
else
if (VBSEO_REWRITE_FORUM_FEED && vbseo_check_multi_urls(
array(
'VBSEO_URL_FORUM_GENERAL_FEED', 
'VBSEO_URL_FORUM_TITLE_FEED', 
), 
$vbseo_url_) )
{
}
else
if (vbseo_checkurl_blog($vbseo_url_, 2))
{
}
else
if ((VBSEO_REWRITE_MALBUMS && 
(
($vbseo_arr9 = vbseo_check_url_strict('VBSEO_URL_MEMBER_PICTURE_PAGE', $vbseo_url_))
||
($vbseo_arr7 = vbseo_check_url_strict('VBSEO_URL_MEMBER_PICTURE', $vbseo_url_))
||
($vbseo_arr8 = vbseo_check_url_strict('VBSEO_URL_MEMBER_PICTURE_IMG', $vbseo_url_))
||
($vbseo_arr15 = vbseo_check_url_strict('VBSEO_URL_MEMBER_ALBUM_HOME_PAGE', $vbseo_url_))
||
($vbseo_arr14 = vbseo_check_url_strict('VBSEO_URL_MEMBER_ALBUM_HOME', $vbseo_url_))
||
($vbseo_arr5 = vbseo_check_url_strict('VBSEO_URL_MEMBER_ALBUMS', $vbseo_url_))
||
($vbseo_arr13 = vbseo_check_url_strict('VBSEO_URL_MEMBER_ALBUMS_PAGE', $vbseo_url_))
||
($vbseo_arr12 = vbseo_check_url_strict('VBSEO_URL_MEMBER_ALBUM_PAGE', $vbseo_url_))
||
($vbseo_arr6 = vbseo_check_url_strict('VBSEO_URL_MEMBER_ALBUM', $vbseo_url_))
)) ||
(VBSEO_REWRITE_MEMBERS && (
($vbseo_arr4 = vbseo_check_url_strict('VBSEO_URL_MEMBER_FRIENDSPAGE', $vbseo_url_))
||
($vbseo_arr10 = vbseo_check_url_strict('VBSEO_URL_MEMBER_CONVPAGE', $vbseo_url_))
||
($vbseo_arr11 = vbseo_check_url_strict('VBSEO_URL_MEMBER_CONV', $vbseo_url_))
||
($vbseo_arr2 = vbseo_check_url_strict('VBSEO_URL_MEMBER_MSGPAGE', $vbseo_url_))
||
($vbseo_arr = vbseo_check_url_strict('VBSEO_URL_MEMBER', $vbseo_url_))
)
) 
|| (VBSEO_REWRITE_AVATAR &&
(substr($vbseo_url_, 0, strlen(VBSEO_AVATAR_PREFIX)) == VBSEO_AVATAR_PREFIX) &&
($vbseo_arr3 = vbseo_check_url('VBSEO_URL_AVATAR', substr($vbseo_url_, strlen(VBSEO_AVATAR_PREFIX))))
)
)
{
vBSEO_Storage::set('url_vbseoed', true);
if($vbseo_arr2)$vbseo_arr=$vbseo_arr2;
if($vbseo_arr3)$vbseo_arr=$vbseo_arr3;
if($vbseo_arr4)$vbseo_arr=$vbseo_arr4;
if($vbseo_arr5)$vbseo_arr=$vbseo_arr5;
if($vbseo_arr6)$vbseo_arr=$vbseo_arr6;
if($vbseo_arr7)$vbseo_arr=$vbseo_arr7;
if($vbseo_arr8)$vbseo_arr=$vbseo_arr8;
if($vbseo_arr9)$vbseo_arr=$vbseo_arr9;
if($vbseo_arr10)$vbseo_arr=$vbseo_arr10;
if($vbseo_arr11)$vbseo_arr=$vbseo_arr11;
if($vbseo_arr12)$vbseo_arr=$vbseo_arr12;
if($vbseo_arr13)$vbseo_arr=$vbseo_arr13;
if($vbseo_arr15)$vbseo_arr=$vbseo_arr15;
if (empty($vbseo_arr['user_id']) && isset($vbseo_arr['user_name']))
$vbseo_arr['user_id'] = vbseo_reverse_username($vbseo_arr['user_name']);
if (empty($vbseo_arr['visitor_id']) && isset($vbseo_arr['visitor_name']))
$vbseo_arr['visitor_id'] = vbseo_reverse_username($vbseo_arr['visitor_name']);
if (empty($vbseo_arr['album_id']) && isset($vbseo_arr['album_title']))
$vbseo_arr['album_id'] = vbseo_reverse_object('album', $vbseo_arr['album_title'], $vbseo_arr['user_id']);
if ($vbseo_arr14)
{
vbseo_set_self('album.' . VBSEO_VB_EXT );
$vbseo_found_fn = 'album.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr15)
{
vbseo_set_self('album.' . VBSEO_VB_EXT . '?do=latest&page='.$vbseo_arr['page']);
$vbseo_found_fn = 'album.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr13)
{
vbseo_set_self('album.' . VBSEO_VB_EXT . '?' . VBSEO_USERID_URI . '=' . $vbseo_arr['user_id'] . '&page='.$vbseo_arr['page']);
$vbseo_found_fn = 'album.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr5)
{
vbseo_set_self('album.' . VBSEO_VB_EXT . '?' . VBSEO_USERID_URI . '=' . $vbseo_arr['user_id']);
$vbseo_found_fn = 'album.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr11)
{
vbseo_set_self('converse.' . VBSEO_VB_EXT . '?' . VBSEO_USERID_URI . '=' . $vbseo_arr['user_id'] . '&u2='.$vbseo_arr['visitor_id']);
$vbseo_found_fn = 'converse.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr10)
{
vbseo_set_self('converse.' . VBSEO_VB_EXT . '?' . VBSEO_USERID_URI . '=' . $vbseo_arr['user_id'] . '&u2='.$vbseo_arr['visitor_id'].'&page='.$vbseo_arr['page']);
$vbseo_found_fn = 'converse.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr6)
{
vbseo_set_self('album.' . VBSEO_VB_EXT . '?albumid=' . $vbseo_arr['album_id']);
$vbseo_found_fn = 'album.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr12)
{
vbseo_set_self('album.' . VBSEO_VB_EXT . '?albumid=' . $vbseo_arr['album_id'].'&page='.$vbseo_arr['page']);
$vbseo_found_fn = 'album.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr8)
{
preg_match('#^(\d+)(d\d+)?(t)?#', $vbseo_arr['picture_id'], $atm);
vbseo_set_self(VBSEO_PIC_SCRIPT . '.' . VBSEO_VB_EXT . '?albumid=' . $vbseo_arr['album_id'].'&'.VBSEO_PICID_URI.'='.$vbseo_arr['picture_id'].(isset($atm[3])?'&thumb=1&dl='.$atm[2]:''));
$vbseo_found_fn = VBSEO_PIC_SCRIPT . '.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr7)
{
vbseo_set_self('album.' . VBSEO_VB_EXT . '?albumid=' . $vbseo_arr['album_id'].'&'.VBSEO_PICID_URI.'='.$vbseo_arr['picture_id']);
$vbseo_found_fn = 'album.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr9)
{
vbseo_set_self('album.' . VBSEO_VB_EXT . '?albumid=' . $vbseo_arr['album_id'].'&'.VBSEO_PICID_URI.'='.$vbseo_arr['picture_id'].'&page='.$vbseo_arr['page']);
$vbseo_found_fn = 'album.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr4)
{
vbseo_set_self('member.' . VBSEO_VB_EXT . '?tab=friends&page='.$vbseo_arr['page'].'&' . VBSEO_USERID_URI . '=' . $vbseo_arr['user_id']);
$vbseo_found_fn = 'member.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr2)
{
vbseo_set_self('member.' . VBSEO_VB_EXT . '?tab=visitor_messaging&page='.$vbseo_arr['page'].'&' . VBSEO_USERID_URI . '=' . $vbseo_arr['user_id']);
$vbseo_found_fn = 'member.' . VBSEO_VB_EXT;
}
else
if($vbseo_arr3)
{
vbseo_set_self('image.' . VBSEO_VB_EXT . '?' . VBSEO_USERID_URI . '=' . $vbseo_arr['user_id'] . '&dateline=' . time());
$vbseo_found_fn = 'image.' . VBSEO_VB_EXT;
}
else
if ($vbseo_arr)
{
vbseo_set_self('member.' . VBSEO_VB_EXT . '?' . VBSEO_USERID_URI . '=' . $vbseo_arr['user_id']
.($_GET['tab'] ? '&tab='.$_GET['tab'] : ''));
$vbseo_found_fn = 'member.' . VBSEO_VB_EXT;
}
$vbseo_found = true;
}
else
if (VBSEO_REWRITE_PRINTTHREAD &&
($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_PRINT_PAGENUM', $vbseo_url_)) ||
($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_PRINT', $vbseo_url_)))
{
vbseo_set_self('printthread.' . VBSEO_VB_EXT . '?' . VBSEO_THREADID_URI . '=' . $vbseo_arr['thread_id'] . (isset($vbseo_arr['thread_page'])?'&' . VBSEO_PAGENUM_URI . '=' . $vbseo_arr['thread_page']:''));
$vbseo_found_fn = 'printthread.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
else
if (VBSEO_REWRITE_THREADS && !VBSEO_VB4 && VBSEO_ENABLE_GARS &&
($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_GARS_PAGENUM', $vbseo_url_))
)
{                   
vbseo_set_self('showthread.' . VBSEO_VB_EXT . '?' . VBSEO_THREADID_URI . '=' . $vbseo_arr['thread_id'] . (isset($vbseo_arr['thread_page'])?'&' . VBSEO_PAGENUM_URI_GARS . '=' . $vbseo_arr['thread_page']:''));
$vbseo_found_fn = 'showthread.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
else
if (VBSEO_REWRITE_THREADS &&
(($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_PAGENUM', $vbseo_url_)) ||
($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD', $vbseo_url_))
))
{
if($hlpar = vbseo_check_highlight(1))
{
vbseo_safe_redirect($_SERVER['REQUEST_URI'], array($hlpar));
}
vbseo_check_highlight(2);
if(!$vbseo_arr['thread_id'])
{
if(!$vbseo_arr['forum_id'])
$vbseo_arr['forum_id'] = vbseo_reverse_forumtitle($vbseo_arr);
$vbseo_arr['thread_id'] = 
vbseo_reverse_object('thread', $vbseo_arr['thread_title'], $vbseo_arr['forum_id']);
}
vbseo_set_self('showthread.' . VBSEO_VB_EXT . '?' . VBSEO_THREADID_URI . '=' . $vbseo_arr['thread_id'] . (isset($vbseo_arr['thread_page'])?'&' . VBSEO_PAGENUM_URI . '=' . $vbseo_arr['thread_page']:''));
$vbseo_found_fn = 'showthread.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
if (!$vbseo_found && !$vbseo_url_suggest)
{
if ((VBSEO_REWRITE_FORUM || VBSEO_REWRITE_ANNOUNCEMENT) &&
(
($vbseo_arr = $vbseo_arra = vbseo_check_url('VBSEO_URL_FORUM_ANNOUNCEMENT', $vbseo_url_)) ||
($vbseo_arr = $vbseo_arra2 = vbseo_check_url('VBSEO_URL_FORUM_ANNOUNCEMENT_ALL', $vbseo_url_)) ||
($vbseo_arr = vbseo_check_url('VBSEO_URL_FORUM_PAGENUM', $vbseo_url_)) ||
($vbseo_arr = vbseo_check_url('VBSEO_URL_FORUM', $vbseo_url_))
)
)
{
if (!isset($vbseo_arr['forum_page'])) $vbseo_arr['forum_page'] = 1;
if (!isset($vbseo_arr['forum_id']) &&
(
isset($vbseo_arr['forum_path']) ||
isset($vbseo_arr['forum_title'])
)
)
{
$vbseo_arr['forum_id'] = vbseo_reverse_forumtitle($vbseo_arr);
}
if ($vbseo_url_suggest)
{
if (!$vbseo_arr['forum_id'])
$vbseo_url_suggest = '';
}
else
if (isset($vbseo_arr['forum_id']))
{
vbseo_set_self('forumdisplay.' . VBSEO_VB_EXT . '?' . VBSEO_FORUMID_URI . '=' . $vbseo_arr['forum_id'] .
($vbseo_arr['forum_page'] > 1 ? '&' . VBSEO_PAGENUM_URI . '=' . $vbseo_arr['forum_page'] : ''));
$vbseo_found_fn = 'forumdisplay.' . VBSEO_VB_EXT;
$vbseo_found = true;
if (($vbseo_arra || $vbseo_arra2))
{
if ($vbseo_arra)
{
vbseo_prepare_seo_replace();
vbseo_get_forum_info();
$fa_ann = vbseo_get_forum_announcement($vbseo_arr['forum_id']);
$a_ann = $fa_ann['announcement'];
if (!$vbseo_arr['announcement_id'] && $a_ann)
while (list($aid, $announce) = each($a_ann))
{
if (preg_replace(array_keys($seo_replace_inurls),
$seo_replace_inurls,
vbseo_filter_text($announce)
) == $vbseo_arr['announcement_title'])
{
$vbseo_arr['announcement_id'] = $aid;
break;
}
}
}
$vbseo_url_ = 'announcement.' . VBSEO_VB_EXT . '?' . VBSEO_FORUMID_URI . '=' . $vbseo_arr['forum_id'] . '&announcementid=' . $vbseo_arr['announcement_id'];
vbseo_set_self($vbseo_url_);
$vbseo_found_fn = 'announcement.' . VBSEO_VB_EXT;
$vbseo_found = true;
}
}
}
}
}
}
if ((isset($_POST[($vbseo_postpar = 'mergethreadurl')]) && $murl_dyn = $_POST[$vbseo_postpar]) ||
(isset($_POST[($vbseo_postpar = 'dealurl')]) && $murl_dyn = $_POST[$vbseo_postpar])
)
{
$murl = $murl_dyn;
vbseo_get_options();
$purl = @parse_url($murl);
$murl = urldecode(substr($purl['path'], strlen(VBSEO_TOPREL)));
if ($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_GOTOPOST', $murl))
{
$murl_dyn = 'showthread.' . VBSEO_VB_EXT . '?p=' . $vbseo_arr['post_id'];
}
else
if (($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD_PAGENUM', $murl)) ||
($vbseo_arr = vbseo_check_url('VBSEO_URL_THREAD', $murl)))
{
$murl_dyn = 'showthread.' . VBSEO_VB_EXT . '?t=' . $vbseo_arr['thread_id'];
}
if (!strstr($murl_dyn, ':'))
$murl_dyn = $vboptions['bburl2'] . '/' . $murl_dyn;
$_POST[$vbseo_postpar] = $_REQUEST[$vbseo_postpar] = $murl_dyn;
}
if (isset($_POST['usercss']))
{
vbseo_get_options();
foreach($_POST['usercss'] as $cssind=>$csspart)
foreach($csspart as $name=>$imgurl)
if(strstr($name,'_image'))
{
$purl = @parse_url($imgurl);
$murl = urldecode(substr($purl['path'], strlen(VBSEO_TOPREL)));
$_am2 = '';
if ($murl &&
$vbseo_arr = vbseo_check_url('VBSEO_URL_MEMBER_PICTURE_IMG', $murl)
)
{
if (empty($vbseo_arr['user_id']) && isset($vbseo_arr['user_name']))
$vbseo_arr['user_id'] = vbseo_reverse_username($vbseo_arr['user_name']);
if (empty($vbseo_arr['album_id']) && isset($vbseo_arr['album_title']))
$vbseo_arr['album_id'] = vbseo_reverse_object('album', $vbseo_arr['album_title'], $vbseo_arr['user_id']);
$_POST['usercss'][$cssind][$name] = 
$vboptions['bburl2'] . '/' . VBSEO_PIC_SCRIPT . '.' . VBSEO_VB_EXT . '?albumid='.$vbseo_arr['album_id'].'&'.VBSEO_PICID_URI.'=' . $vbseo_arr['picture_id'];
}
}
}
if (isset($_POST[($vbseo_postpar = 'pictureurls')]) && $murl_dyn = $_POST[$vbseo_postpar])
{
$amurl = preg_split('#[\r\n]+#',$murl_dyn);
vbseo_get_options();
$_ischg = false;
$amurl2 = array();
foreach($amurl as $_am)
{
$purl = @parse_url($_am);
$murl = urldecode(substr($purl['path'], strlen(VBSEO_TOPREL)));
$_am2 = '';
if ($vbseo_arr = vbseo_check_url('VBSEO_URL_MEMBER_PICTURE', $murl))
{
$_am2 = 'album.' . VBSEO_VB_EXT . '?'.VBSEO_PICID_URI.'=' . $vbseo_arr['picture_id'];
}
else
if ($vbseo_arr = vbseo_check_url('VBSEO_URL_MEMBER_PICTURE_PAGE', $murl))
{
$_am2 = 'album.' . VBSEO_VB_EXT . '?'.VBSEO_PICID_URI.'=' . $vbseo_arr['picture_id'];
}
else
if ($vbseo_arr = vbseo_check_url('VBSEO_URL_MEMBER_PICTURE_IMG', $murl))
{
$_am2 = 'album.' . VBSEO_VB_EXT . '?'.VBSEO_PICID_URI.'=' . $vbseo_arr['picture_id'];
}
if ($_am2)
{
$_am = $vboptions['bburl2'] . '/' . $_am2;
$_ischg = true;
}
$amurl2[] = $_am;
}
if($_ischg)
$_POST[$vbseo_postpar] = $_REQUEST[$vbseo_postpar] = implode("\n", $amurl2);
}
}
$vbseo_found_1 = $vbseo_found;
if (!$vbseo_found && !$vbseo_file_exists && VBSEO_REDIRURL)
{
list($vbseo_url_, $vbseo_url_par) = explode('?', VBSEO_REDIRURL);
$vbseo_url_ = urldecode($vbseo_url_);
if(vbseo_security_check($vbseo_url_))
vbseo_404();
$vbseo_file_exists = file_exists($vbseo_url_) 
&& ($vbseo_url_[strlen($vbseo_url_)-1] != '/');
if($vbseo_file_exists)
{
$vbseo_found_fn = $vbseo_url_;
$vbseo_found = true;
}
}
if (!$vbseo_found)
{
$vbseo_found_fn = VBSEO_BASEURL;
if (@is_dir($vbseo_url_) || !$vbseo_url_)
{
$vbseo_url_ .= 'index.' . VBSEO_VB_EXT;
$vbseo_found_fn = 'index.' . VBSEO_VB_EXT;
}
$vbseo_root = dirname($vbseo_url_);
$vbseo_file = $vbseo_found_fn;
if ($vbseo_file == '')
$vbseo_file = 'index.' . VBSEO_VB_EXT;
if (@is_file($vbseo_url_) && (!$vbseo_root || ($vbseo_root == '.') || @is_dir($vbseo_root) || @is_dir($vbseo_root2)))
{
if ($vbseo_root && @is_dir($vbseo_root))
@vbseo_chdir($vbseo_root);
vbseo_set_self($_SERVER['REQUEST_URI']);
$vbseo_found = true;
}
else
{
$vbseo_root = dirname($vbseo_url_);
$vbseo_root2 = basename($vbseo_root);
if (file_exists($vbseo_found_fn) || file_exists($vbseo_root2 . '/' . $vbseo_found_fn))
{
if (!file_exists($vbseo_found_fn))
{
@vbseo_chdir($vbseo_root2);
$vbseo_found_fn = $vbseo_root2 . '/' . $vbseo_found_fn;
}
$vbseo_purl = @parse_url($_SERVER['REQUEST_URI']);
if ($_POST)
{
$vbseo_found = true;
}
else
{
vbseo_safe_redirect($vbseo_found_fn . ($vbseo_purl['query'] ? '?' . $vbseo_purl['query'] : ''), array(), true);
}
}
}
}
if ($vbseo_found_1 && !$vbseo_relpath)
define('VBSEO_PREPROCESSED', 1);
if ($vbseo_found_fn == 'external.' . VBSEO_VB_EXT || $vbseo_found_fn == 'blog_external.' . VBSEO_VB_EXT
|| ($vbseo_found_fn == 'ajax.' . VBSEO_VB_EXT && ($_REQUEST['do'] == 'rss'))
)
{                                                     
$GLOBALS['VBSEO_REWRITE_TEXTURLS'] = 1;
define('VBSEO_REWRITE_EXTERNAL', 1);
ob_start("vbseo_output_handler");
define('VBSEO_AJAX', 1);
require ($vbseo_found_fn);
if (!defined('VBSEO_PROCESS'))
{
$output = ob_get_contents();
ob_end_clean();
$output = make_crawlable($output);
echo $output;
}
exit();
}
if (isset($_GET['vbseoembedd']) && $vbseo_found_fn && preg_match('#\.php$#', $vbseo_found_fn))
{
ob_start("vbseo_output_handler");
require ($vbseo_found_fn);
ob_flush();
if (!defined('VBSEO_PROCESS'))
{
$output = ob_get_contents();
ob_end_clean();
$output = make_crawlable($output);
echo $output;
}
exit();
}
if(!defined('VBSEO_PROCESS'))
if (($vbseo_found_fn == 'ajax.' . VBSEO_VB_EXT) || 
(isset($_POST) && 
(isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'))) 
&& 
preg_match('#(newreply|profile|editpost|showpost|blog_post|blog_ajax|blog_tag|threadtag|group|attachment|visitormessage|picturecomment)\.php$#', $vbseo_found_fn))
)
{
define('VBSEO_AJAX', 1);
if(vbseo_vbversion() < '3.8')
ob_start("vbseo_output_handler");
require ($vbseo_found_fn);
if (!defined('VBSEO_OUTHANDLER'))
{
$output = ob_get_contents();
ob_end_clean();
vbseo_get_options();
$output = vbseo_output_handler($output, false);
echo $output;
}
exit();
}
if (!$vbseo_found)
{
if ($vbseo_url_suggest)
{
vbseo_safe_redirect($vbseo_relpath . $vbseo_url_suggest);
}
if (VBSEO_404_HANDLE == 2)
{
$vbseo_incf = VBSEO_404_CUSTOM;
if ($vbseo_incf[0] != '/')
$vbseo_incf = dirname(__FILE__) . '/' . $vbseo_incf;
include($vbseo_incf);
exit;
}
else
vbseo_404_routine($vbseo_url_);
}
else
{
if (preg_match('#\.(css|php\d?/?|p?html?|txt)$#', $vbseo_found_fn, $typematch) && !strstr($vbseo_found_fn, '://'))
{
if ($typematch[1] == 'css')
header ('Content-type: text/css');
if (preg_match('#^(.+)/([^/]+)$#', $vbseo_found_fn, $vbseo_m))
{
@vbseo_chdir($vbseo_m[1]);
$vbseo_found_fn = $vbseo_m[2];
}
if (($vbseo_found_fn == 'showthread.' . VBSEO_VB_EXT) && isset($_POST) && isset($_POST['excerpt']) && VBSEO_IN_TRACKBACK)
{
@define('THIS_SCRIPT', 'showthread');
include dirname(__FILE__) . '/global.' . VBSEO_VB_EXT;
vbseo_extra_inc('linkback');
vbseo_trackback_proc();
}
$_vfname = getcwd() . '/' . $vbseo_found_fn;
if(substr($typematch[1],0,2) == 'ph' || ($typematch[1] == VBSEO_VB_EXT))
{
require($_vfname);
}else
{
echo file_get_contents($_vfname);
}
exit();
}
else
{
vbseo_404();
}
}
exit();
?>