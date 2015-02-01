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
function vB_AJAX_WolResolve_Init(B){if(AJAX_Compatible&&(typeof vb_disable_ajax=="undefined"||vb_disable_ajax<2)){var C=fetch_tags(fetch_object(B),"a");for(var A=0;A<C.length;A++){if(C[A].id&&C[A].id.substr(0,10)=="resolveip_"&&C[A].innerHTML.match(/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/)){C[A].onclick=resolve_ip_click}}}}function vB_AJAX_WolResolve(B,A){this.ip=B;this.objid=A}vB_AJAX_WolResolve.prototype.resolve=function(){YAHOO.util.Connect.asyncRequest("POST",fetch_ajax_url("online.php?do=resolveip&ipaddress="+PHP.urlencode(this.ip)),{success:this.handle_ajax_response,failure:this.handle_ajax_error,timeout:vB_Default_Timeout,scope:this},SESSIONURL+"securitytoken="+SECURITYTOKEN+"&do=resolveip&ajax=1&ipaddress="+PHP.urlencode(this.ip))};vB_AJAX_WolResolve.prototype.handle_ajax_error=function(A){vBulletin_AJAX_Error_Handler(A)};vB_AJAX_WolResolve.prototype.handle_ajax_response=function(A){if(A.responseXML){var B=fetch_object(this.objid);B.parentNode.insertBefore(document.createTextNode(A.responseXML.getElementsByTagName("ipaddress")[0].firstChild.nodeValue),B);B.parentNode.removeChild(B)}};function resolve_ip_click(A){var B=new vB_AJAX_WolResolve(this.innerHTML,this.id);B.resolve();return false};