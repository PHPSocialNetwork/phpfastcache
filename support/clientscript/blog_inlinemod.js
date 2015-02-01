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
function vB_Inline_Mod_Blog(A,D,E,C,B){vB_Inline_Mod_Blog.baseConstructor.call(this,A,D,E,C,B);this.id=this}vBulletin.extend(vB_Inline_Mod_Blog,vB_Inline_Mod);vB_Inline_Mod_Blog.prototype.highlight_comment=function(A){this.highlight_table(A)};vB_Inline_Mod_Blog.prototype.highlight_trackback=function(A){this.highlight_table(A)};vB_Inline_Mod_Blog.prototype.highlight_blog=function(A){this.highlight_table(A)};vB_Inline_Mod_Blog.prototype.highlight_pcomment=function(A){this.highlight_table(A)};vB_Inline_Mod_Blog.prototype.highlight_table=function(A){var B=YAHOO.util.Dom.get("td_"+this.type+"_"+A.id.substr(this.type.length+5));if(B){this.toggle_highlight(B,A,true)}};vB_Inline_Mod_Blog.prototype.toggle_highlight_alt1=function(A,B){if(A.tagName){if(B.checked){YAHOO.util.Dom.addClass(A,"inlinemod");YAHOO.util.Dom.removeClass(A,"alt1")}else{YAHOO.util.Dom.addClass(A,"alt1");YAHOO.util.Dom.removeClass(A,"inlinemod")}}};