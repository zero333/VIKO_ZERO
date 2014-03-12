<?php

/**
 * Contains the Module_MyVIKO class
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
require_once 'lib/CourseList.php';
require_once 'lib/VikoTable.php';
require_once 'lib/HTML.php';

/**
 * Manages the main page of VIKO
 *
 */
class Module_MyVIKO extends Module {

    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "my-viko";
    }


    /**
     * Returns My VIKO page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        $html = "";
        $html.= $this->_createPageTitle();
        $html.= $this->_createCoursesMenuForTeacher();
        $html.= $this->_createCoursesMenuForStudent();
        $html.= $this->_createAdminMenu();
        $html.= $this->_createSettingsMenu();
        //$html.= $this->_createHelpMenu();

        return $html;
    }


    /**
     * Generates H2 heading and title for the page
     *
     * The heading consist of "User:" followed with the name of the user.
     * Also sets the contents of title to "My VIKO".
     *
     * @access private
     * @return string HTML H2 title
     */
    function _createPageTitle()
    {
        $this->title( _("My VIKO") );

        $user = $_SESSION['user'];
        $title = _("User") . ": " . $user->getFullName();
        return HTML::h2( $title );
    }


    /**
     * Generates section containing the courses the user is teacher in
     *
     * If the user doesn't have teacher privileges or is administrator,
     * an empty string will be returned.
     *
     * @access private
     * @return string HTML content
     */
    function _createCoursesMenuForTeacher()
    {
        if (
            $_SESSION['user']->hasAccessToGroup('TEACHER') &&
            !$_SESSION['user']->hasAccessToGroup('ADMIN')
        ) {
            // start with section heading
            $html = HTML::h3( _("Courses") );

            // retrieve all the associated courses
            $course_list = CourseList::getFromTeacher( $_SESSION['user'] );

            // Only generate table with courses,
            // if the user is a teacher on at least one course.
            if ( count($course_list) > 0 ) {
                $table = new VikoTable();
                $table->addHeader(
                    _("Course name"),
                    _("Form name"),
                    _("Nr of students"),
                    HTML::abbr( "X", _("Delete course") )
                );

                foreach ( $course_list as $course ) {
                    // link to course
                    
                    $course_link = HTML::a(
						$this->URI( $course->getID() ), 
						$course->getHTMLName()
                    );

                    $nr_of_students = StudentList::getCountFromCourse( $course );

                    // link to delete the course
                    $title = sprintf( _("Delete %s"), $course->getHTMLName() );
                    $course_delete_link = HTML::a(
                      $this->URI('course-delete', $course->getID()), 
                        "X",
                        $title
                    );

                    $table->addRow(
                        $course_link,
                        $course->getHTMLSubName(),
                        $nr_of_students,
                        $course_delete_link
                    );
                }
                $table->setColumnsToNumeric( array(2) );
                $table->setColumnToDelete( 3 );
                $html .= $table->toHTML();
            }

            // create the link for adding courses
          $html .= HTML::newLink( $this->URI('course-add'), _("Add course"), STANDALONE ); 
          $html .= HTML::newLink( $this->URI('1/tasks/add'), _("Lisa ülesanne"), STANDALONE ); 

            return $html;
        }
        else {
            return "";
        }
    }


    /**
     * Generates section containing courses the user is in (not as teacher)
     *
     * If the user isn't registered to any course
     * an empty string will be returned.
     *
     * @access private
     * @return string HTML content
     */
    function _createCoursesMenuForStudent()
    {
        // retrieve all the associated courses
        $course_list = CourseList::getFromStudent( $_SESSION['user'] );

        // Only generate this section,
        // if the user is registered on at least one course
        if ( count($course_list) > 0 ) {
            // start with section heading
            $title = HTML::h3( _("Courses") );

            // begin the table
            $table = new VikoTable();
            $table->addHeader(
                _("Course name"),
                _("Teacher"),
                _("Marks")
            );

            // for each course, retrieve the marks from the database and
            // combine with all the other data into one row
            foreach ( $course_list as $course ) {
                $teacher = $course->getTeacher();

                $marks = $this->_getUserMarksFromCourse( $course );

                $table->addRow(
                   HTML::a( $this->URI($course->getID()), $course->getHTMLName() ), 
                    $teacher->getFullName(),
                    $marks
                );
            }

           $hometasks_link = HTML::ul( HTML::a( $this->URI('hometasks'), _("Hometasks") ) ); 

            return $title . $table->toHTML() . $hometasks_link;
        }
        else {
            return "";
        }
    }


    /**
     * Returns grades belonging to current user and specified course
     *
     * @access private
     * @param Course $course course object
     * @return string marks
     */
    function _getUserMarksFromCourse( $course )
    {
        // execute SQL statement
        $db = DBInstance::get();
        $result = $db->query(
            "
            SELECT
                mark,
                marktype
            FROM
                Lessons
                JOIN
                Marks ON ( Lessons.lesson_id = Marks.lesson_id )
            WHERE
                Lessons.course_id = ?
                AND
                Marks.user_id = ?
            ORDER BY
                lesson_date
            ",
            array( $course->getID(), $_SESSION['user']->getID() )
        );

        // If error is found here - just die
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        // return comma-separated list of grades
        return $this->_composeGradesList( $result );
    }


    /**
     * Returns comma-separated list of grades
     *
     * @access private
     * @param DB_result $result
     * @return string
     */
    function _composeGradesList( $result )
    {
        $marks = array();
        while ( $result->fetchInto($row) ) {
            // only include actual marks (exclude empty strings and zero)
            if ( trim($row['mark']) > "" && trim($row['mark']) != "0" ) {
                // emphasize perliminary marks
                if ( $row['marktype'] == 'PRELIM' ) {
                    $marks[] = HTML::strong( $row['mark'] );
                }
                else {
                    $marks[] = $row['mark'];
                }
            }
        }

        // add commas between marks
        return implode(", ", $marks);
    }


    /**
     * Generates section with admin-only links
     *
     * Only visible for admins.
     *
     * @access private
     * @return string HTML
     */
    function _createAdminMenu()
    {
        if ( $_SESSION['user']->hasAccessToGroup('ADMIN') ) {
            return
                $html = HTML::h3( _("VIKO administration") ) .
               HTML::ul( HTML::a( $this->URI('statistics'), _("VIKO server statistics") ) ); 
        }
        elseif ( $_SESSION['user']->hasAccessToGroup('SCHOOLADMIN') ) {
            $html = HTML::h3( _("School administration") );
            $school = $_SESSION['user']->getSchool();
            $html .= HTML::ul(
				HTML::a( $this->URI('statistics/' . $school->getID()), _("School statistics") ), 
				HTML::a( $this->URI('teachers'), _("Teachers and school administrators") ), 
 		        HTML::a( $this->URI('forms'), _("Forms and students") )             );

            return $html;
        }
        else {
            return "";
        }
    }


    /**
     * Generates section with links to different settings
     *
     * This is available for all users
     *
     * @access private
     * @return string HTML
     */
    function _createSettingsMenu()
    {
        return
            HTML::h3( _("Settings") ) .
            HTML::ul(
    		HTML::a( $this->URI('change-userinfo'), _("Change userinfo") ), 
			HTML::a( $this->URI('change-password'), _("Change password") ) 
			);
    }

}


?>
