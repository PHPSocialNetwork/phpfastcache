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
function vB_AJAX_GroupReadMarker(A){this.groupid=A}vB_AJAX_GroupReadMarker.prototype.mark_read=function(){YAHOO.util.Connect.asyncRequest("POST",fetch_ajax_url("group.php?do=markread&groupid="+this.groupid),{success:this.handle_ajax_request,failure:this.handle_ajax_error,timeout:vB_Default_Timeout,scope:this},SESSIONURL+"securitytoken="+SECURITYTOKEN+"&do=markread&groupid="+this.groupid)};vB_AJAX_GroupReadMarker.prototype.handle_ajax_error=function(A){vBulletin_AJAX_Error_Handler(A)};vB_AJAX_GroupReadMarker.prototype.handle_ajax_request=function(F){if(F.responseXML&&F.responseXML.firstChild){var B=fetch_tags(F.responseXML,"error");if(B.length){alert(B[0].firstChild.nodeValue);return }}var E=document.getElementById("discussion_list");var A=YAHOO.util.Dom.getElementsByClassName("unread",false,E);for(var C=0;C<A.length;C++){YAHOO.util.Dom.removeClass(A[C],"unread");var D=YAHOO.util.Dom.getElementsByClassName("id_goto_unread","a",A[C]);for(var G=0;G<D.length;G++){YAHOO.util.Dom.addClass(D[G],"hidden")}}};function mark_group_read(A){if(AJAX_Compatible){vB_GroupReadMarker=new vB_AJAX_GroupReadMarker(A);vB_GroupReadMarker.mark_read()}else{window.location="group.php?"+SESSIONURL+"do=markread&groupid="+A}return false};