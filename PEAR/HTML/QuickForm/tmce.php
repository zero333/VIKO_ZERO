<?php 
require_once('tmcearea.php');  

/**
* HTML Quickform element for TinyMCE Editor  
*  
* TinyMCE is a WYSIWYG HTML editor which can be obtained from  
* http://tinymce.moxiecode.com/.  
* @access       public  
*/  
class HTML_QuickForm_tmce extends HTML_QuickForm_tmcearea  
{  
/**  
* The width of the editor in pixels or percent  
*  
* @var string  
* @access public  
*/  
var $Width = '';  
/**  
* The height of the editor in pixels or percent  
*  
* @var string  
* @access public  
*/  
var $Height = '';  

/**  
* The path where to find the editor files  
*  
* @var string  
* @access public  
*/ 

var $BasePath = '';
/**  
* Check for browser compatibility  
*  
* @var boolean  
* @access public  
*/  
var $CheckBrowser = true;  
/**  
* Configuration settings for the editor  
*  
* @var array  
* @access public  
*/  
var $Config = array();  
/**  
* Class constructor  
*  
* @param   string  TinyMCE instance name  
* @param   string  TinyMCE instance label  
* @param   array   Config settings for TinyMCE  
* @param   string  Attributes for the textarea  
* @access  public  
* @return  void  
	*/  
function HTML_QuickForm_TinyMCE($elementName=null, $elementLabel=null, $attributes=null, $options=array())  
{  
	HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes);  
	$this->_persistantFreeze = true;
		 $this->_type = 'tmce';  
	 if (is_array($options)) {  
	 $this->Config = $options;  
  }  
 }  
/**  
* Set config variable for TinyMCE  
*  
* @param mixed Key of config setting  
* @param mixed Value of config setting  
* @access public  
* @return void       
*/  
function SetConfig($key, $value = null)  
     {  
         if (is_array($key)) {  
              foreach ($key as $k => $v) {  
                $this->Config[$k] = $v;  
              }  
          } else {  
             $this->Config[$key] = $value;  
          }  
     }
	 
     /**  
* Check if the browser is compatible (IE 5.5+, Gecko > 20030210)  
*  
* @access public  
 * @return boolean  
 */  
     function IsCompatible()  
     {  
          if (isset($_SERVER['HTTP_USER_AGENT']))  
         {  
             $agent = strtolower($_SERVER['HTTP_USER_AGENT']);  
             if (($msie = strpos($agent, 'msie')) !== false &&  
                strpos($agent, 'opera') === false &&  
                 strpos($agent, 'mac') === false)  
           {                  
             return ((float) substr($agent, $msie + 5, 3) >= 5.5);  
            } elseif (($gecko = strpos($agent, 'gecko/')) !== false) {  
                 return ((int) substr($agent, $gecko + 6, 8 ) >= 20030210);  
          }               
           return true;  
        }     
        return false;  
   }  
  
       
     /**  
* Return the htmlarea in HTML  
*  
* @access public  
* @return string  
*/  
     function toHtml()  
    {  
        $html = null;  
// return frozen state  
		if ($this->_flagFrozen) {  
       return $this->getFrozenHtml();  
// return textarea if incompatible  
      } elseif (!$this->IsCompatible()) {  
        return parent::toHtml();  
      // return textarea  
          } else {  
	//FIX for multiple editors in a form, initialize once (CRM-3559)  
             if ( !defined('htmleditorloaded' ) ) {                  
                 // load tinyMCEeditor  
                //$config = CRM_Core_Config::singleton( );  
	// tinymce is wierd, it needs to be loaded initially along with jquery  
	// gets local language info from cookie
		$locale=$_COOKIE["locale"];
		$language=substr($locale,0,2);
		$html = null;  
		$html .= sprintf(
		'<script language="javascript" type="text/javascript" src="/js/tmce/tiny_mce.js"></script>
		<script language="javascript" type="text/javascript">
		tinyMCE.init({
		theme : "advanced",
		mode: "textareas",
		language : "'.$language.'",
		theme_advanced_toolbar_location : "top",   
		theme_advanced_toolbar_align : "left",
		theme_advanced_buttons1 : "formatselect,separator"+",bold,italic,separator"+",link,unlink",
		file_browser_callback : "myFileBrowser"
		});</script>');  
		define('htmleditorloaded', true);  
		}  
		// include textarea as well (TinyMCE transforms it)  
		$html .=  parent::toHTML();  
		return $html;  
			   }  
			 }  
	 /**  
	* Returns the htmlarea content in HTML  
	*   
	* @access public  
	* @return string  
	*/  
	function getFrozenHtml()  
	{  
	return $this->getValue();  
	}  
}
?>