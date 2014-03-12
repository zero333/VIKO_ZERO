<?php

/**
 * Contains the VIKO class, which handles the GUI at most general level
 *
 * PHP versions 4 and 5
 *
 * LICENSE: VIKO is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * VIKO is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * VIKO; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA  02111-1307  USA
 *
 * @package    VIKO
 * @author     Rene Saarsoo <nene@triin.net>
 * @copyright  2001-2007 VIKO team and contributors
 * @license    http://www.gnu.org/licenses/gpl.html  GPL 2.0
 */


/**
 * Uses the {@link Configuration} class to access configuration
 */
require_once 'Configuration.php';


/**
 * VIKO page
 *
 * Possible usage:
 *
 * <pre>
 * $page = new VIKO();
 * $page->setTitle("VIKO Hello");
 * $page->setContent("Hello, world of VIKO!");
 * echo $page->toHTML();
 * </pre>
 */
class VIKO {

    /**#@+
     * @access private
     * @var string
     */

    /**
     * Title following the VIKO logo
     */
    var $_environment_title="";

    /**
     * Title of the page
     */
    var $_title="--noname--";

    /**
     * Contents of the page (in HTML format)
     */
    var $_content="--empty--";

    /**
     * The Identifier of active VIKO module
     */
    var $_module_id="front-page";

    /**
     * Stylesheets associated with the page
     */
    var $_stylesheets = "";

    /**#@-*/

    /**
     * Navigation menu
     *
     * @access private
     * @var array
     */
    var $_nav = array();


    /**
     * Constructs new VIKO instance
     */
    function VIKO()
    {
        $this->_environment_title = "learning environment";
		$this->addStylesheet("layout.css"); 
    }

    /**
     * Sets the general VIKO environment, based on user group
     *
     * For example, if the user group is "STUDENT", the VIKO header will say:
     * "VIKO student environment".
     *
     * @access public
     * @param string $user_group the group of the user.
     *               only 4 values are allowed: STUDENT, TEACHER, SCHOOLADMIN and ADMIN.
     *               If the parameter is unspecified, no special title will be generated.
     */
    function setEnvironment( $user_group="" )
    {
        switch ($user_group) {
            case "": $this->_environment_title = _("learning environment");
            break;
            case "STUDENT": $this->_environment_title = _("student environment");
            break;
            case "TEACHER": $this->_environment_title = _("teacher environment");
            break;
            case "SCHOOLADMIN": $this->_environment_title = _("school administrator environment");
            break;
            case "ADMIN": $this->_environment_title = _("administrator environment");
            break;
            default: trigger_error("Unknown user group '$user_group'", E_USER_ERROR);
        }
    }


    /**
     * Sets the title of VIKO page
     *
     * @access public
     * @param string $title title of the page.
     */
    function setTitle( $title )
    {
        $this->_title = $title;
    }


    /**
     * Sets the Identifier of current VIKO module
     *
     * The {@link $module_id} will be used as a value for the 'id' attribute
     * of the 'body' element. For example:
     *
     * <pre>
     * $viko->setModuleID("about");
     * // will also output a body tag like: &lt;body id="about"&gt;
     * echo $viko->toHTML();
     * </pre>
     *
     * @access public
     * @param string $module_id ID of module
     */
    function setModuleID( $module_id )
    {
        $this->_module_id = $module_id;
    }


    /**
     * Adds a stylesheet to VIKO page
     *
     * @access public
     * @param string $uri address of the CSS file
     * @param string $media CSS media type (or list of types, separated by commas)
     * @param string $rel either "stylesheet" or "alternate stylesheet"
     */
    function addStylesheet( $uri, $media="all", $rel="stylesheet")
    {
		$css_path = Configuration::getInstallPath() . "css"; 
		
        $this->_stylesheets .= <<<EOHTML
<link rel="$rel" type="text/css" href="$css_path/$uri" media="$media" /> 
EOHTML;
    }


    /**
     * Adds a multiple stylesheets to VIKO page
     *
     * @access public
     * @param string $stylesheets array containing stylesheets
     */
    function addStylesheetList( $stylesheets )
    {
        foreach ( $stylesheets as $sheet ) {
            $uri = $sheet['uri'];
            $media = ( isset($sheet['media']) ) ? $sheet['media'] : 'all';
            $rel = ( isset($sheet['rel']) ) ? $sheet['rel'] : 'stylesheet';
            $this->addStylesheet( $uri, $media, $rel );
        }
    }


    /**
     * Sets the contents of VIKO page
     *
     * @access public
     * @param string $content content of the page.
     */
    function setContent( $content )
    {
        $this->_content = $content;
    }


    /**
     * Appends item (link) to VIKO menu
     *
     * @param string $uri address of the link
     * @param string $label label of the link
     * @param string $description description (displays as HTML title-text)
     */
    function addMenuItem( $uri, $label, $description )
    {
        $this->_nav[] = array(
            'uri'=>$uri,
            'label'=>$label,
            'description'=>$description,
            'state'=>'normal'
        );
    }


    /**
     * Marks menu item as selected
     *
     * @param string $uri URI of menu item to select
     */
    function selectMenuItem( $uri )
    {
        $this->_changeMenuItemState( $uri, 'selected' );
    }


    /**
     * Marks menu item as highlighted
     *
     * @param string $uri URI of menu item to highlight
     */
    function highlightMenuItem( $uri )
    {
        $this->_changeMenuItemState( $uri, 'highlighted' );
    }


    /**
     * Changes the state of menu item
     *
     * 'normal', 'selected' and 'highlighted' are allowed values for state.
     *
     * @access private
     * @param string $uri URI of menu item to change
     * @param string $state the value to assign to the menu item state
     */
    function _changeMenuItemState( $uri, $state )
    {
        // find the menu item with specified title, and change it's state
        for ( $i=0; $i < count($this->_nav); $i++ ) {
            if ( $this->_nav[$i]['uri'] == $uri ) {
                $this->_nav[$i]['state'] = $state;
                return;
            }
        }
    }


    /**
     * Converts $this->_nav array to unordered list.
     *
     * @access private
     * @return string generated HTML
     */
    function _menuToHTML()
    {
        $html='<ul id="nav">';
        foreach ( $this->_nav as $nr => $menu_item ) {
            // assign class="first-child" to the first menu item and
            // class="first-child" to the last menu item
            $class="";
            if ( $nr == 0 ) {
                $class.= "first-child ";
            }
            if ( $nr == count($this->_nav)-1 ) {
                $class.= "last-child ";
            }
            if ( strlen($class)>0 ) {
                $class = ' class="' . $class . '"';
            }

            // if menu item description is set,
            // use it as a value for HTML title attribute
            if ( strlen($menu_item['description']) > 0 ) {
                $description = ' title="' . $menu_item['description'] . '"';
            }

            // in normal state, output: <a>nav_element</a>
            // in selected state: <strong>nav_element</strong>
            // in highlighted state: <strong><a>nav_element</a></strong>
            if ( $menu_item['state']=='normal' ) {
            $html.=<<<EOHTML
<li$class><a href="$menu_item[uri]"$description>$menu_item[label]</a></li>
EOHTML;
            }
            elseif ( $menu_item['state']=='selected' ) {
            $html.=<<<EOHTML
<li$class><strong$description>$menu_item[label]</strong></li>
EOHTML;
            }
            elseif ( $menu_item['state']=='highlighted' ) {
            $html.=<<<EOHTML
<li$class><strong><a href="$menu_item[uri]"$description>$menu_item[label]</a></strong></li>
EOHTML;
            }
            else {
                trigger_error("Unexpected menu item state '$menu_item[state]'", E_USER_ERROR);
            }
        }
        $html.="</ul>";

        return $html;
    }


    /**
     * Creates langage menu, highlighting current locale
     *
     * @access private
     * @return string HTML ul
     */
    function _languageMenu()
    {
        $lang_menu = array(
            "et_EE" => "Eesti",
            "ru_RU" => "&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;",
            "en_US" => "English",
        );
		
		$lang_path = Configuration::getInstallPath() . "lang"; 

        $html = '<ul id="lang">';
        foreach ( $lang_menu as $locale => $language ) {
            if ( $locale == Configuration::getLocale() ) {
                $html .= "<li><strong>$language</strong></li>";
            }
            else {
                $html .= "<li><a href='$lang_path/$locale'>$language</a></li>"; 
            }
        }
        $html .= '</ul>';

        return $html;
    }


    /**
     * Outputs VIKO page in HTML
     *
     * @return string HTML document
     */
    function toHTML()
    {
        $lang = preg_replace('/^([a-z]{2})_([A-Z]+)$/', '\1', Configuration::getLocale());
        $copyright = _("Tallinn University %year1%-%year2%");
        $copyright = str_replace("%year1%", "2001", $copyright);
        $copyright = str_replace("%year2%", "2011", $copyright);

        $lang_menu = $this->_languageMenu();
        $menu = ( count($this->_nav) > 0 ) ? $this->_menuToHTML() : "";
		$js_uri = Configuration::getInstallPath() . "js/delete.js"; 
       	$logo_uri = Configuration::getInstallPath() . "img/viko-logo.png"; 
        
		
		return <<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$lang}" xml:lang="{$lang}">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>{$this->_title}</title>
{$this->_stylesheets}
<script type="text/javascript" src="$js_uri"></script> 
</head>
<body id="{$this->_module_id}">

{$lang_menu}

<div id="container">

<h1><img src="$logo_uri" alt="VIKO" /> {$this->_environment_title}</h1> 

{$menu}

<div id="content">

{$this->_content}

</div>

<hr />
<p id="copyright">{$copyright}</p>

</div>

</body>
</html>
EOHTML;
    }


}


?>
