<?php

/**
 * Contains the Module class, parent of all VIKO modules
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
 * This class needs to create some HTML
 */
require_once "HTML.php";

/** 
* This class needs to access some configuration options 
*/ 
require_once "Configuration.php"; 

/**
 * Parent class of all VIKO modules
 *
 * @abstract
 */
class Module {

    /**
     * Page title
     *
     * @access private
     * @var string
     */
    var $_title = null;


    /**
     * Default constructor for the conveniance of subclasses
     *
     * @param array $parameters  parameters to the module
     */
    function Module_ChangePassword( $parameters )
    {
        // nothing should be done in here
    }


    /**
     * Returns the module identifier string
     *
     * Must be implemented inside subclass
     *
     * @access public
     * @static
     * @abstract
     * @return string Identificator of module
     */
    function getID()
    {
        trigger_error("getID not implemeneted", E_USER_ERROR);
    }


    /**
     * Returns text to be displayd on browser title bar
     *
     * @access public
     * @return string title
     */
    function getTitle()
    {
        // To ensure, that every page has a title set, crash VIKO if it isn't
        if ( !isset( $this->_title ) ) {
            trigger_error( "Page title not defined.", E_USER_ERROR );
        }

        return $this->_title;
    }


    /**
     * Sets contents of page title and returns h2 heading
     *
     * Usually the title and h2 heading on a page are exactly the same.
     * This method should be used to create both of them simultaneously.
     *
     * This method sets the string $title to be returned by Module::getTitle()
     * and also returns h2 heading element containing the value of $title.
     *
     * Sometimes you need to include some HTML inside the h2 heading, but
     * the title element may not contain other elements. This is easy to
     * overcome by using strip_tags() on the contents of title element,
     * and that's exactly what this method does. So you should use this
     * method even if the heading has to contain other HTML elements.
     *
     * @access protected
     * @param string $title  contents of heading and title
     * @return string        h2 heading
     */
    function title( $title )
    {
        $this->_title = strip_tags( $title );
        return HTML::h2( $title );
    }


    /**
     * Creates heading and body for general error message page
     *
     * It's almost the same as HTML::errorPage(), only difference, that
     * this method uses $this->title() instead of HTML::h2() to create
     * the heading, meaning, that this method also sets the title of
     * the page.
     *
     * This method should be used instead of HTML::errorPage() in all
     * subclasses of Module.
     *
     * @param string $error_message  descriptive text about the error
     * @return string
     */
    function errorPage( $error_message )
    {
        return
            $this->title( _( "Error!" ) ) .
            HTML::error( $error_message );
    }


    /**
     * Creates heading and body of access denied type of error page
     *
     * It's almost the same as HTML::accessDeniedPage(), only difference,
     * that this method uses $this->title() instead of HTML::h2() to
     * create the heading, meaning, that this method also sets the title
     * of the page.
     *
     * This method should be used instead of HTML::accessDeniedPage() in
     * all subclasses of Module.
     *
     * @param string $error_message  descriptive text about the error
     * @return string
     */
    function accessDeniedPage( $error_message )
    {
        return
            $this->title( _( "Access denied!" ) ) .
            HTML::error( $error_message );
    }


    /**
     * Returns array containing module-specific stylesheets
     *
     * This method may be implemented in modules, that need to have
     * their special stylesheet in addition to default VIKO stylesheet.
     *
     * Each stylesheet is represented as an associative array
     * with a following structure:
     *
     * <pre>
     * array(
     *     'uri'   => '/css/my-styles.css',
     *     'media' => 'all',       # the default
     *     'rel'   => 'stylesheet' # the default
     * )
     * </pre>
     *
     * Only the uri is required, the others are optional.
     *
     * @access public
     * @return array list of stylesheets
     */
    function getStylesheets()
    {
        return array();
    }


    /**
     * Returns Module in HTML
     *
     * Requires implementation in subclasses.
     *
	 * @access public 
     * @return string HTML fragment
     */
    function toHTML()
    {
        trigger_error("Unimplemented", E_USER_ERROR);
    }
	/** 
	* Redirects to another module 
	* 
 	* Takes either any number of arguments or one argument as array. 
 	* If no parameter is given, redirects to the front page. 
 	* 
 	* e.g. when VIKO is located at /dir-name, 
 	* then passing in parameters "bar" and "baz", the result will be: 
 	* $module->redirect("bar","baz") ==> redirect to: "/dir-name/bar/baz" 
 	* 
 	* @access public 
 	*/ 
 	function redirect() 
 	{ 
 	// get a list of all parameters passed in 
 	$args = func_get_args(); 
 		 
 	// if the first argument was array, 
 	// use that as the argument list instead 
 	if (isset($args[0]) && is_array($args[0])) { 
 		$args = $args[0]; 
 	} 
 		 
 	// create URI relative to install path 
 	$uri = $this->URI($args); 
 	 	 
 	// perform the redirect 
 	header("Location: " . $uri); 
 	} 
 	
	
	/** 
 	* Create path relative to the current module 
 	* 
 	* All arguments to this function are added as parameters 
 	* for this module. Takes either one argument as array or 
 	* any number of string or numeric arguments. 
 	* 
 	* If no arguments are given, returns just the path to current module. 
 	* 
 	* e.g. when VIKO is installed to /dir-name and the module name is "mod", 
 	* then: 
 	* $module->relativeURI("foo","bar") ==> "/dir-name/mod/foo/bar" 
 	* 
 	* @access public 
 	* @return string path 
 	*/ 
 	function relativeURI() 
 	{ 
 	// get all arguments 
 	$args = func_get_args(); 
 	
	// if the first argument was array, 
 	// use that as the argument list instead 
 	if (isset($args[0]) && is_array($args[0])) { 
 		$args = $args[0]; 
 	} 
 	
	// put the module name at the beginning of argument list 
 	$args = array_merge( 
 	array( $this->getID() ), 
 	$args 
 	); 
 	
	// use URI method to create final path 
 	return $this->URI( $args ); 
 	} 
 	
	
	/** 
 	* Create path relative to the VIKO installation location 
 	* 
 	* Takes either any number of parameters - all the parameters are 
 	* separated with slashes, and appended to the end of the path - 
 	* or it can take only one argument as an array. 
 	* 
 	* e.g. when VIKO is installed to /dir-name, then 
 	* Module::URI("foo","bar") ==> "/dir-name/foo/bar" 
 	* 
 	* @access public 
 	* @return string path 
 	*/ 
 	static function URI() 
 	{ 
 	// get the path where VIKO is installed 
 	$uri = Configuration::getInstallPath(); 
 	
	// get list of all the arguments 
 	$args = func_get_args(); 
	// if the first argument was array, 
 	// use that as the argument list instead 
 	if (isset($args[0]) && is_array($args[0])) { 
 		$args = $args[0]; 
 	} 
 	
	// separate arguments with slashes and 
 	// append to the installation path 
 	$uri.= implode("/", $args); 
	return $uri; 
 	} 
}


?>