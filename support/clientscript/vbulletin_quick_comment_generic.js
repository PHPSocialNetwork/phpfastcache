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
function vB_QuickComment_GenericMessage(C,A,B){vB_QuickComment_GenericMessage.baseConstructor.call(this,C,A,B);this.id=this}vBulletin.extend(vB_QuickComment_GenericMessage,vB_QuickComment);vB_QuickComment_GenericMessage.prototype.post_save=function(F){YAHOO.util.Dom.setStyle(document.body,"cursor","auto");YAHOO.util.Dom.setStyle("qc_posting_msg","display","none");this.posting=false;var E=F.responseXML.getElementsByTagName("message");if(E.length){this.write_editor_contents("");this.form.lastcomment.value=F.responseXML.getElementsByTagName("time")[0].firstChild.nodeValue;this.hide_errors();var D=0;var H=YAHOO.util.Dom.get("message_list");for(var C=0;C<E.length;C++){if(this.returnorder=="ASC"){Comment_Init(H.insertBefore(string_to_node(E[C].firstChild.nodeValue),H.firstChild))}else{Comment_Init(H.appendChild(string_to_node(E[C].firstChild.nodeValue)))}D+=parseInt(E[C].getAttribute("visible"))}if(D>0){var G=YAHOO.util.Dom.get("page_message_count");if(G){G.innerHTML=parseInt(G.innerHTML)+D}var A=YAHOO.util.Dom.get("total_message_count");if(A){A.innerHTML=parseInt(A.innerHTML)+D}}var B=YAHOO.util.Dom.get("qr_submit");if(B){B.blur()}}else{if(!is_saf){this.show_errors(F);return false}this.repost=true;this.form.submit()}};