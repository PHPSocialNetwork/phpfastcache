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
vBulletin.events.systemInit.subscribe(function(){if(AJAX_Compatible){vB_QuickLoader_Factory=new vB_QuickLoader_Factory_Blog_Entry()}});function vB_QuickLoader_Factory_Blog_Entry(){vB_QuickLoader_Factory_Blog_Entry.baseConstructor.call(this);this.id=this}vBulletin.extend(vB_QuickLoader_Factory_Blog_Entry,vB_QuickLoader_Factory);vB_QuickLoader_Factory_Blog_Entry.prototype.init=function(){this.objecttype="b";this.containertype="entry";this.ajaxtarget="blog_ajax.php";this.ajaxaction="loadentry";this.returnbit="entrybit";if(vBulletin.elements.vB_QuickLoad_Blog_Entry){for(var B=0;B<vBulletin.elements.vB_QuickLoad_Blog_Entry.length;B++){var C=vBulletin.elements.vB_QuickLoad_Blog_Entry[B];var A=YAHOO.util.Dom.get("view_"+this.containertype+C);if(A){this.controls[C]=new vB_QuickLoader(C,this)}}vBulletin.elements.vB_QuickLoad_Blog_Entry=null}};vB_QuickLoader_Factory.prototype.redirect=function(A){window.location="blog.php?"+SESSIONURL+"b="+A};