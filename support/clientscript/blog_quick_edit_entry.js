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
vBulletin.events.systemInit.subscribe(function(){if(AJAX_Compatible){vB_QuickEditor_Factory=new vB_QuickEditor_Factory_Blog_Entry()}});function vB_QuickEditor_Factory_Blog_Entry(){vB_QuickEditor_Factory_Blog_Entry.baseConstructor.call(this);this.id=this}vBulletin.extend(vB_QuickEditor_Factory_Blog_Entry,vB_QuickEditor_Factory);vB_QuickEditor_Factory_Blog_Entry.prototype.init=function(){this.target="blog_post.php";if(PATHS.blog){this.target=PATHS.blog+"/"+this.target}this.postaction="updateblog";this.objecttype="b";this.getaction="editblog";this.ajaxtarget="blog_ajax.php";this.ajaxaction="quickeditentry";this.deleteaction="deleteblog";this.messagetype="entry_text_";this.containertype="entry";this.responsecontainer="entrybits";if(vBulletin.elements.vB_QuickEdit_Blog_Entry){for(var A=0;A<vBulletin.elements.vB_QuickEdit_Blog_Entry.length;A++){var B=vBulletin.elements.vB_QuickEdit_Blog_Entry[A];var C=YAHOO.util.Dom.get(this.containertype+"_edit_"+B);if(C){this.controls[B]=new vB_QuickEditor(B,this)}}vBulletin.elements.vB_QuickEdit_Blog_Entry=null}};