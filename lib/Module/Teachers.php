<?php

/**
 * Contains the Module_Teachers class
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
require_once 'lib/VikoTable.php';

/**
 * Shows the schooladmins and teachers of the school
 *
 * (This module has no parameters.)
 */
class Module_Teachers extends Module {

    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "teachers";
    }


    /**
     * Creates HTML content of the page
     *
     * @return string HTML content
     */
    function toHTML()
    {
        // only schooladmins can access the list of teachers and schooladmins
        if ( !$_SESSION['user']->hasAccessToGroup("SCHOOLADMIN") ) {
            return $this->accessDeniedPage(
                _("You are not authorized to add or edit teachers and school administrators.")
            );
        }


        $title = $this->title( _("Teachers and schooladmins") );

        $teachers_list = $this->_teachersList();

        $links = HTML::actionList(
            HTML::backLink( $this->URI("my-viko"), _("Return to the main page") ),
            HTML::newLink( $this->URI('user', 'add', 'teacher'), _("Add teacher") ),
            HTML::newLink( $this->URI('user', 'add', 'schooladmin'), _("Add school administrator") )
        );

        return $title . $teachers_list . $links;
    }


    /**
     * Table with all the schooladmins and teachers in the school
     *
     * @access private
     * @return string HTML
     */
    function _teachersList()
    {
        $db = DBInstance::get();
        $orderby = DBUtils::createOrderBy( array( 'user_group', 'lastname', 'firstname' ) );
        $school = $_SESSION['user']->getSchool();
        $result = $db->query(
            "SELECT
                user_id,
                lastname,
                firstname,
                user_group
            FROM
                Users
            WHERE
                school_id = ?
                AND
                ( user_group = 'TEACHER' OR user_group = 'SCHOOLADMIN' )
            $orderby
            ",
            array( $school->getID() )
        );

        // If we get error here, we die.
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        $table = new VikoTable( _("List of all teachers and schooladmins in this school") );
        $table->addHeader(
            _("Name"),
            _("User group"),
            _("Nr of courses"),
            _("Nr of lessons"),
            HTML::abbr( "X", _("Delete user") )
        );

        $total_courses = 0;
        $total_lessons = 0;

        while ( $result->fetchInto( $row ) ) {
            $user = new User();
            $user->setID( $row['user_id'] );
            $user->setFirstName( $row['firstname'] );
            $user->setLastName( $row['lastname'] );
            $user->setGroup( $row['user_group'] );
            $user_name = $user->getFullName( LASTNAME_FIRSTNAME );
            $user_link = $user->getUserLink( LASTNAME_FIRSTNAME );

            // create link to delete the teacher/schooladmin
            // but do not allow schooladmin to delete himself
            if ( $user->getID() == $_SESSION['user']->getID() ) {
                $delete_link = HTML::abbr('X', _("You can not delete youself!") );
            }
            else {
                $delete_title = sprintf( _("Delete %s"), $user_name );
                $delete_link = HTML::a(
                    $this->URI('user', 'delete', $user->getID()),
                    'X',
                    $delete_title
                );
            }

            // user group
            if ( $user->getGroup() == 'TEACHER' ) {
                $user_group = _("Teacher");
            }
            else {
                $user_group = _("School administrator");
            }

            // nr of courses
            $nr_of_courses = $db->getOne(
                "SELECT
                    COUNT(*)
                FROM
                    Courses
                WHERE
                    teacher_id = ?
                ",
                array( $user->getID() )
            );
            $total_courses += $nr_of_courses;

            // nr of lessons
            $nr_of_lessons = $db->getOne(
                "SELECT
                    COUNT(*)
                FROM
                    Courses
                    JOIN
                    Lessons USING (course_id)
                WHERE
                    teacher_id = ?
                ",
                array( $user->getID() )
            );
            $total_lessons += $nr_of_lessons;

            $table->addRow(
                $user_link,
                $user_group,
                $nr_of_courses,
                $nr_of_lessons,
                $delete_link
            );

        }

        $table->addFooter(
            _("Total"),
            "",
            $total_courses,
            $total_lessons,
            ""
        );

        $table->setColumnsToNumeric( array(2,3), TABLE_BODY_AND_FOOTER );
        $table->setColumnToDelete( 4 );

        return $table->toHTML();
    }


}


?>