<?php

/**
 * Contains the Module_Course class, parent of all course-specific modules
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

require_once 'lib/Module.php';
require_once 'lib/Course.php';

/**
 * Abstract parent class for all course modules
 *
 * Each subclass of this module represents the whole functionality
 * under one element in the course menu. (The course menu is the menu,
 * which appears at the top of the screen, when student or teacher
 * selects a course.)
 *
 * For example if you want to add a image gallery to VIKO, you would
 * create a subclass Module_Course_Gallery and save it into file
 * lib/Module/Course/Gallery.php
 *
 * The file itself should contain at least the following:
 *
 * <pre>
 * require_once('Module/Course.php');
 *
 * class Module_Course_Gallery extends Module_Course
 * {
 * }
 * </pre>
 *
 * Well, that's barely enough - so, let's add some implementation too.
 *
 * First we need to create the constructor, that overrides the constructor
 * in the parent class.
 *
 * <pre>
 * function Module_Course_Gallery( $course, $parameters )
 * {
 *     // get image number from parameters array,
 *     // but only when it is specified.
 *     if isset( $parameters[1] ) {
 *         $this->_image_nr = $parameters[1];
 *     }
 *
 *     // pass information on to parent constructor
 *     parent::Module_Course( $course, $parameters )
 * }
 *
 * </pre>
 *
 * As seen from above, the constructor takes two arguments.
 * The first is an instance of Course, which we'll pass on to
 * parent class.
 *
 * The other argument contains parameters specific for this
 * module. All the parameters come from the URI, which was used
 * to access this course module. URI-s are in the following form:
 *
 * <pre>
 * /course-nr/module-name/parameter-1/parameter-2/...
 * </pre>
 *
 * This has to be rewritten... but I don't bother to do it now.
 *
 * <pre>
 * /**
 *  * Some special variables for the gallery
 *  * /
 *  var $_gallery_nr = 1;
 *  var $_image_nr = null;
 *
 * /**
 *  * Create HTML for the gallery when needed
 *  * /
 * function toHTML()
 * {
 * }
 * </pre>
 *
 */
class Module_Course extends Module {

    /**
     * Stores the course object
     *
     * @access private
     * @var Course
     */
    var $_course = null;

    /**
     * Determines weather the menu item is selected or just highlighted.
     *
     * @access private
     * @var string
     */
    var $_selected_menu_item_status = 'SELECTED';


    /**
     * Creates new Course Module
     *
     * The value of first parameter is stored into _course attribute.
     * Nothing is done with the other parameter - the implementation
     * is left for child classes.
     *
     * @param Course $course  object representing the currently selected course
     * @param array $parameters  parameters to the module
     */
    function Module_Course( $course, $parameters )
    {
        $this->_course = $course;
    }


    /**
     * Returns the module identifier string
     *
     * This method must be implemeneted in derived classes.
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "course";
    }


    /**
     * Returns the short name of the module, this is used in navigation menu.
     *
     * Must be implemented inside subclass
     *
     * @access public
     * @static
     * @abstract
     * @return string short name
     */
    function getName()
    {
        trigger_error("getName not implemeneted", E_USER_ERROR);
    }


    /**
     * Returns short description of module
     *
     * This might be used as a title attribute value in navigation.
     *
     * Must be implemented inside subclass
     *
     * @access public
     * @static
     * @abstract
     * @return string module description
     */
    function getDescription()
    {
        trigger_error("getDescription not implemeneted", E_USER_ERROR);
    }


    /**
     * Returns the state of currently selected menu item
     *
     * This can be either 'SELECTED' or 'HIGHLIGHTED'.
     * If the menu link is selected, then user can not click it,
     * but highlighted link is clickable.
     *
     * By default this function returnes 'SELECTED', but derived
     * classes are probably changing this behavior to suit their needs.
     *
     * @return string either 'SELECTED' or 'HIGHLIGHTED'
     */
    function getSelectedMenuItemStatus()
    {
        return $this->_selected_menu_item_status;
    }


    /**
     * Returns true if this course module is viewable by student
     *
     * By default each course module should be listed in the
     * course menu of student also. So this method returns true
     * by default. But some modules only need to be viewed by
     * teachers - those modules can override this method.
     *
     * @return bool true if is allowed
     */
    static function studentIsAllowedToView()
    {
        return true;
    }


    /**
     * Creates URI relative to the current domain
     *
     * This method should be used when creating relative links inside
     * a course module. The method will create URI relative to the
     * VIKO install path and consisting of the following parts:
     *
     * <pre>
     * VIKO_path / course_id / course_module_id [ / parameter ]*
     * </pre>
     *
     * The parameters are the parameters supplied to this method (the method
     * takes multiple parameters; preferably strings). Alternatively the 
     * method may take only one parameter as array. 
     *
     * For example, when using the code below inside Module_Course_Lessons:
     *
     * <pre>
     * $this->relativeURI( 'edit', $lesson->getID() );
     * </pre>
     *
     * If the selected course has ID 1043 , $lesson->getID() returns 128 and 
	 * VIKO is installed to "/",  and $lesson->getID() returns 128,
     * then the created URI will be the following:
     *
     * <pre>
     * /1043/lessons/edit/128
     * </pre>
     *
     *
     * @return string absolute URI
     */
    function relativeURI()
    {

		// get all arguments
        $args = func_get_args();
        
		// if the first argument was array
		// use that as the argument list instead 
		if (isset($args[0]) && is_array($args[0]) ) { 
			$args = $args[0]; 
        } 

		// put the course ID and module name at the beginning of argument list 
		$args = array_merge( 
		array( $this->_course->getID() , $this->getID() ), 
		$args 
		); 
		// use URI method to create final path 
		return $this->URI( $args ); 
		} 


	/** 
 	* Redirects to another page inside the current course module 
 	* 
 	* Like Module::redirect() takes any number of arguments or 
 	* one argument as array. But only the parameters to the module 
 	* need to be specified, course_id and module name are added 
 	* automatically. 
 	*/ 
 	function redirectInsideCourseModule() 
 	{ 
 		$args = func_get_args(); 

		// if the first argument was array, 
		// use that as the argument list instead 
		if (isset($args[0]) && is_array($args[0])) { 
			$args = $args[0]; 
			} 

 		// merge module name at the beginning of argument list 
 		$args = array_merge( 
        array( $this->getID() ), 
		$args 
		); 

		// go to the resulting address 
		$this->redirectInsideCourse( $args ); 
	} 


	/** 
 	* Redirects to another page inside the current course 
 	* 
 	* Like Module::redirect() takes any number of arguments 
 	* or one argument as array. But only the name of the module 
 	* and parameters to the module need to be specified, 
 	* course_id is added automatically. 
 	*/ 
 	function redirectInsideCourse() 
 	{ 
 	$args = func_get_args(); 

	// if the first argument was array, 
 	// use that as the argument list instead 
 		if (isset($args[0]) && is_array($args[0])) { 
			$args = $args[0]; 
		} 
    // merge course_id at the beginning of argument list 
 		$args = array_merge( 
 		array( $this->_course->getID() ),$args ); 
	// go to the resulting address 
 		$this->redirect( $args ); 

    }


    /**
     * Checks if user can edit this course or not
     *
     * e.g. weather user is teacher of this course.
     *
     * @access protected
     * @return bool true if user can edit the course.
     */
    function _userCanEditThisCourse()
    {
		$user =  $_SESSION['user'];
		$course_teacher = $this->_course->getTeacher();
		
		$course_teacher->getID() == $user->getID();
		 if ( $user->getGroup() == 'TEACHER' || $course_teacher->getID() == $user->getID()){
        return true;
		}
    }


    /**
     * Creates H2 title for the course page
     *
     * If page name parameter is not specified, the name of the current
     * course module is used instead.
     *
     * If the user has TEACHER rights for this course, then the course subname
     * is also displayd (if, of course, that name exists).
     *
     * @access protected
     * @param string $page_name  first part of the H2 title
     * @return string H2 title
     */
    function title( $page_name=null )
    {
        // get course name and subname
        $course_name = $this->_course->getHTMLName();
        $course_subname = $this->_course->getHTMLSubName();
        // if page name is not specified, use module name
        if ( !isset($page_name) ) {
            $page_name = htmlspecialchars( $this->getName() );
        }

        // title consists of page name and course name
        $title = "$page_name: $course_name";

        // If the user teacher of this course and course subname is specified,
        // then append the course subname to the title (separated with en-dash).
        if ( $this->_userCanEditThisCourse() && strlen( $course_subname ) > 0 ) {
            $title .= " &#8211; $course_subname";
        }

        return parent::title( $title );
    }


}


?>