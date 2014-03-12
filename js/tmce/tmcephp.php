<?php
 $allowedTags='<p><strong><em><u><h1><h2><h3><h4><h5><h6><img>';
 $allowedTags.='<li><ol><ul><span><div><br><ins><del>';  
?>

<script language="javascript" type="text/javascript" src="tiny_mce.js"></script>
<script language="javascript" type="text/javascript">
  tinyMCE.init({
    theme : "advanced",
    mode: "textareas",
	language : "et",
    elements : "elm1",
    theme_advanced_toolbar_location : "top",   
	theme_advanced_toolbar_align : "left",
    theme_advanced_buttons1 : "formatselect,separator"+",bold,italic,separator"+",link,unlink",
    
    height:"350px",
    width:"800px",
    file_browser_callback : 'myFileBrowser'
  });

  function myFileBrowser (field_name, url, type, win) {
    var fileBrowserWindow = new Array();
    fileBrowserWindow['title'] = 'File Browser';
    fileBrowserWindow['file'] = "untitled.php" + "?type=" + type;
    fileBrowserWindow['width'] = '800';
    fileBrowserWindow['height'] = '400';
    tinyMCE.openWindow(fileBrowserWindow, { window : win, resizable : 'yes', inline : 'yes' });
    return false;
  }
</script>

<?php echo 
	"<form method='post' action=''>
	<textarea id='elm1' name='elm1' rows='12' cols='45'>
	</textarea><br/>
	<input type='submit' name='save' value='Submit' />
	</form>";
 ?>
