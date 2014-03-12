<?php

/**
 * Contains the Module_CourseDelete class
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
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';
require_once 'lib/StudentList.php';
require_once 'lib/HTML.php';

/**
 * Manages the deleting of courses
 *
 * <ul>
 * <li>/course-delete/#id  -  shows confirmation dialog about deleting course with ID #id</li>
 * <li>/course-delete/#id/yes-please  -  actually deletes course with ID #id</li>
 * </ul>
 *
 * Only the teacher of the course is allowed to remove it.
 */
class Module_CourseDelete extends Module {

    /**
     * Course, that is to be removed
     *
     * @var Course
     */
    var $_course_to_delete = null;

    /**
     * Has the user sayd "please"?
     *
     * @var Course
     */
    var $_yes_please_delete_it = false;


    /**
     * Constructs new CourseAdd Module
     *
     * @param array $parameters  parameters to the module
     */
    function Module_CourseDelete( $parameters )
    {
        // this module needes the ID of the course to be removed
        if ( isset($parameters[0]) && (int)$parameters[0] > 0 ) {
            $this->_course_to_delete = new Course();
            $this->_course_to_delete->setID( (int)$parameters[0] );
        }
        else {
            trigger_error("No course ID specified.", E_USER_ERROR);
        }

        // the second parameter is for confirmation
        if ( isset($parameters[1]) && $parameters[1]=='yes-please' ) {
            $this->_yes_please_delete_it = true;
        }
    }


    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "course-delete";
    }


    /**
     * Returns CourseDelete page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // only teachers can delete courses
        if ( !$_SESSION['user']->hasAccessToGroup("TEACHER") ) {
            return $this->accessDeniedPage( _("You are not authorized to delete courses.") );
        }

        // the course has to exist -- try loading it from database
        if ( !$this->_course_to_delete->loadFromDatabaseByID() ) {
            return $this->errorPage( _("The selected course does not exist.") );
        }

        // only the teacher who owns the course can delete it
        $course_teacher = $this->_course_to_delete->getTeacher();
        if ( $_SESSION['user']->getID() != $course_teacher->getID() ) {
            return $this->accessDeniedPage( _("You are not authorized to delete this course.") );
        }

        // The user has to say "please" to confirm he truly wants to delete it
        // If he hasn't sayd that, show him a link he can click to say "please".

        if ( $this->_yes_please_delete_it ) {

            // remove the course (The students will probably say "Hurray!" now)
            $this->_course_to_delete->delete();

            // send back to the MyVIKO page
            $this->redirect("my-viko"); 
            return false;

        }
        else {

            $name = $this->_course_to_delete->getHTMLName();
            $title = sprintf( _("Are you really sure you want to delete the course %s?"), HTML::strong( $name ) );
            $html = $this->title( $title );

            $nr_of_students = StudentList::getCountFromCourse( $this->_course_to_delete );
            if ( $nr_of_students > 0 ) {
                $translated_text = ngettext(
                    "There is %d student on this course.",
                    "There are %d students on this course.",
                    $nr_of_students
                );
                $html.= HTML::p( sprintf( $translated_text, $nr_of_students ) );
            }

            $id = $this->_course_to_delete->getID();
            $html.= HTML::p( HTML::a(
            $this->URI("course-delete", $id, "yes-please"), 
                _("Yes, please remove this course.")
            ) );

            return $html;
        }
    }
}


?>