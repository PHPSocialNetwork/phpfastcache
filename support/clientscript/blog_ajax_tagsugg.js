/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 4.2.1
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
function vB_Blog_AJAX_TagSuggest(B,A,C){vB_Blog_AJAX_TagSuggest.baseConstructor.call(this,B,A,C);this.tag_search=function(){if(this.active){this.tags=new Array();this.ajax_req=YAHOO.util.Connect.asyncRequest("POST",fetch_ajax_url("blog_tag.php?do=tagsearch"),{success:this.handle_ajax_response,failure:vBulletin_AJAX_Error_Handler,timeout:vB_Default_Timeout,scope:this},SESSIONURL+"securitytoken="+SECURITYTOKEN+"&do=tagsearch&fragment="+PHP.urlencode(this.fragment))}}}vBulletin.extend(vB_Blog_AJAX_TagSuggest,vB_AJAX_TagSuggest);