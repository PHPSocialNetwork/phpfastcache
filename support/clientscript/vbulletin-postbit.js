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
vB_XHTML_Ready.subscribe(init_PostBits);function init_PostBits(){var B=YAHOO.util.Dom.getElementsByClassName("postbit","li","posts");for(var A=0;A<B.length;A++){new PostBit(B[A],inlinemod_collection)}}function PostBit(B,A){this.postbit=YAHOO.util.Dom.get(B);this.postid=B.id.substr("post_".length);this.inlinemod=new InlineModControl(this.postbit,this.postid,A)};