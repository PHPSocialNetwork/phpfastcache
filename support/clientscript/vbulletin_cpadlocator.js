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
vBulletin.events.systemInit.subscribe(function(){var A=document.getElementsByTagName("select").item(0);YAHOO.util.Event.on(A,"change",change_group,A)});function change_group(D,A){var C=YAHOO.util.Dom.getElementsBy(is_group_element,"div");console.info(C.length);for(var B=0;B<C.length;B++){C[B].style.display=(C[B].id=="group_"+A.value)?"":"none"}}function is_group_element(A){return(A.id.substring(0,5)=="group")}function toggle_all_active(B){for(var A=0;A<this.form.elements.length;A++){if(this.form.elements[A].type=="checkbox"&&this.form.elements[A].name.substr(0,6)=="active"){this.form.elements[A].checked=this.checked}}};