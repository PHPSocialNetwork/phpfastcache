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
vBulletin.events.systemInit.subscribe(function(){if(is_ie){var B=YAHOO.util.Dom.get("picturebits");if(B){var F=B.getElementsByTagName("label");var A,D,C,E;for(C=0;C<F.length;C++){E=YAHOO.util.Dom.generateId(F[C]);A=F[C].getElementsByTagName("img");for(D=0;D<A.length;D++){A[D].labelid=E;YAHOO.util.Event.on(A[D],"click",image_label_click,A[D])}}}}});function image_label_click(B,A){YAHOO.util.Dom.get(A.labelid).click()};