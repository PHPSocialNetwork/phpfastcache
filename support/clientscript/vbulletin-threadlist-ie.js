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
function vB_Liquid_Cells(){this.cells=new Array();YAHOO.util.Event.on(window,"resize",this.resize,this,true)}vB_Liquid_Cells.prototype.add_cell=function(B){var A=this.calculate(B);this.cells.push(YAHOO.util.Dom.generateId(B));B.style.width=A.width;B.style.marginBottom=A.marginBottom};vB_Liquid_Cells.prototype.calculate=function(E){var D,C,B,F,A=0;D=YAHOO.util.Dom.getElementsByClassName("td","*",E.parentNode);for(C=0;C<D.length;C++){B=D[C];if(B!=E&&B.currentStyle.display!="none"){A+=parseFloat(B.offsetWidth)}}return{marginBottom:-9999+parseFloat(E.currentStyle.paddingTop),width:parseFloat(E.parentNode.offsetWidth)-A}};vB_Liquid_Cells.prototype.resize=function(D){var C,A;for(var B=0;B<this.cells.length;B++){C=YAHOO.util.Dom.get(this.cells[B]);A=this.calculate(C);C.style.width=A.width}};var LCF=new vB_Liquid_Cells();