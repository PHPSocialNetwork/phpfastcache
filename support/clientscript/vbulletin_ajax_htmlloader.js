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
function load_html(E,A,D,C,B){if(AJAX_Compatible){vB_HtmlLoader=new vB_AJAX_HtmlLoader(E,A,D,C,B);vB_HtmlLoader.load()}return false}var vB_HtmlLoader=false;function vB_AJAX_HtmlLoader(E,A,D,C,B){this.getrequest=A;this.container=fetch_object(E);this.postrequest=D;this.progresselement=fetch_object(C);this.triggerevent=B}vB_AJAX_HtmlLoader.prototype.load=function(){if(this.progresselement){this.progresselement.style.display=""}if(this.container){YAHOO.util.Connect.asyncRequest("POST",fetch_ajax_url(this.getrequest),{success:this.display,failure:this.handle_ajax_error,timeout:vB_Default_Timeout,scope:this},this.postrequest+"&sessionurl="+SESSIONURL+"&securitytoken="+SECURITYTOKEN+"&ajax=1")}return false};vB_AJAX_HtmlLoader.prototype.handle_ajax_error=function(A){if(this.progresselement){this.progresselement.style.display="none"}vBulletin_AJAX_Error_Handler(A)};vB_AJAX_HtmlLoader.prototype.display=function(C){if(this.progresselement){this.progresselement.style.display="none"}if(C.responseXML){var B=C.responseXML.getElementsByTagName("html");var A=C.responseXML.getElementsByTagName("error");if(B.length&&B[0].hasChildNodes()){this.container.innerHTML=B[0].firstChild.nodeValue}}if(this.triggerevent){this.triggerevent()}};