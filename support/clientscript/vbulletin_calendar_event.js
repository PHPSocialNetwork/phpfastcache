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
vB_XHTML_Ready.subscribe(function(){YAHOO.util.Event.on("event_form","submit",set_form)});function set_form(C){if(YAHOO.util.Dom.hasClass("single_dd","selected")){var B="single"}else{if(YAHOO.util.Dom.hasClass("range_dd","selected")){var B="range"}else{var B="recur"}}var A=document.createElement("input");YAHOO.util.Dom.setAttribute(A,"type","hidden");YAHOO.util.Dom.setAttribute(A,"name","type");YAHOO.util.Dom.setAttribute(A,"value",B);YAHOO.util.Event.getTarget(C).appendChild(A)};