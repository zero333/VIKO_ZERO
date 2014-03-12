function getElementsByClass(searchClass,node,tag){
var classElements=new Array()
if(node==null){
node=document}
if(tag==null){
tag='*'}
var els=node.getElementsByTagName(tag)
var elsLen=els.length
var pattern=new RegExp('(^|\\s)'+searchClass+'(\\s|$)')
for(i=0,j=0;i<elsLen;i++){
if(pattern.test(els[i].className)){
classElements[j]=els[i]
j++}}
return classElements}
function deleteLinkClick(event){
if(!event)var event=window.event
var target=(event.target)? event.target : event.srcElement
if(target.nodeType==3){
var activeLink=target.parentNode}
else{
var activeLink=target}
var selectedRow=activeLink
while(selectedRow.tagName !="TR"){
selectedRow=selectedRow.parentNode}
var oldClassName=selectedRow.className
selectedRow.className="selected"
var result=confirm(activeLink.title+"?")
selectedRow.className=oldClassName
return result}
function checkAllCheckboxes(){
if(!document.getElementsByTagName){
return false}
var controls=document.getElementsByTagName('input')
for(var i=0;i<controls.length;i++){
if(controls[i].type=="checkbox"){
controls[i].checked=true}}
return false}
window.onload=function(){
if(document.getElementsByTagName){
var deleteCells=getElementsByClass("delete",document,"td")
for(var i=0;i<deleteCells.length;i++){
var linkList=deleteCells[i].getElementsByTagName("a")
if(linkList.length>0){
var deleteLink=linkList[0]
deleteLink.onclick=function(event){return deleteLinkClick(event);}}}}}
