<?php

/**
 * Contains the Module_Students class
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
 * Shows the students of a specified form
 *
 * <ul>
 * <li>/students/#form_id  -  list students from form with ID #form_id.</li>
 * </ul>
 */
class Module_Students extends Module {

    /**
     * The form from where students are shown
     */
    var $_form = null;

    /**
     * Constructs new Students Module
     *
     * @param array $parameters  parameters to the module
     */
    function Module_Students( $parameters )
    {
        if ( isset($parameters[0]) && (int)$parameters[0] > 0 ) {
            $this->_form = new Form();
            $this->_form->setID( (int)$parameters[0] );
        }
        else {
            trigger_error( "Form ID not specified.", E_USER_ERROR );
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
        return "students";
    }


    /**
     * Creates HTML content of the page
     *
     * @return string HTML content
     */
    function toHTML()
    {
        // only schooladmins can access the list of students
        if ( !$_SESSION['user']->hasAccessToGroup("SCHOOLADMIN") ) {
            return $this->accessDeniedPage( _("You are not authorized to add or edit students.") );
        }

        // form must exist
        if ( !$this->_form->loadFromDatabaseByID() ) {
            return $this->errorPage( _("The selected form does not exist.") );
        }

        // form must belong to the school where the user is
        $form_school = $this->_form->getSchool();
        $user_school = $_SESSION['user']->getSchool();
        if ( $form_school->getID() != $user_school->getID() ) {
            return $this->accessDeniedPage( _("You are not authorized to access this form.") );
        }

        $title_text = _("Students of %form_name%");
        $title_text = str_replace('%form_name%', $this->_form->getHTMLName(), $title_text );
        $title = $this->title( $title_text );

        $student_list = $this->_studentList();

        $links = HTML::actionList(
            HTML::backLink( "/forms", _("Return to the list of forms") ),
            HTML::newLink( '/user/add/student/' . $this->_form->getID(), _("Add student") )
        );

        return $title . $student_list . $links;
    }


    /**
     * Table with all the students in one form
     *
     * @access private
     * @return string HTML
     */
    function _studentList()
    {
        $table = new VikoTable( _("List of all students in this form") );
        $table->addHeader(
            _("Student name"),
            _("Nr of courses"),
            _("Nr of loads"),
            _("Nr of messages"),
            _("Nr of materials"),
            HTML::abbr( "X", _("Delete student") )
        );

        $total_courses = 0;
        $total_loads = 0;
        $total_posts = 0;
        $total_materials = 0;

        $student_result = $this->_retrieveStudentRecords( $this->_form );

        if ( $student_result->numRows() == 0 ) {
            return HTML::notice( _("There are no students in this form.") );
        }

        while ( $student_result->fetchInto( $student ) ) {

            $user = new User();
            $user->setID( $student['user_id'] );
            $user->setFirstName( $student['firstname'] );
            $user->setLastName( $student['lastname'] );

            // link which takes you to the student info
            $student_link = $user->getUserLink( LASTNAME_FIRSTNAME );

            // link that deletes the student
            $name = $user->getFullName();
            $delete_title = sprintf( _("Delete %s"), $name );
            $student_delete_link = HTML::a(
                '/user/delete/' . $user->getID(),
                "X",
                $delete_title
            );

            $table->addRow(
                $student_link,
                $student["nr_of_courses"],
                $student["nr_of_loads"],
                $student["nr_of_posts"],
                $student["nr_of_materials"],
                $student_delete_link
            );

            $total_courses += $student["nr_of_courses"];
            $total_loads += $student["nr_of_loads"];
            $total_posts += $student["nr_of_posts"];
            $total_materials += $student["nr_of_materials"];
        }

        $table->addFooter(
            _("Total"),
            $total_courses,
            $total_loads,
            $total_posts,
            $total_materials,
            ""
        );

        $table->setColumnsToNumeric( array(1,2,3,4), TABLE_BODY_AND_FOOTER );
        $table->setColumnToDelete( 5 );

        return $table->toHTML();
    }


    /**
     * Retrieves stats of all students in the specified form
     *
     * @param Form $form
     * @return DB_result
     */
    function _retrieveStudentRecords( $form )
    {
        $order_by = DBUtils::createOrderBy( array( 'lastname', 'firstname' ) );
        $db = DBInstance::get();
        $result = $db->query(
            "SELECT
                Users.user_id,
                firstname,
                lastname,
                COALESCE( nr_of_courses, 0 ) AS nr_of_courses,
                COALESCE( nr_of_loads, 0 ) AS nr_of_loads,
                COALESCE( nr_of_posts, 0 ) AS nr_of_posts,
                COALESCE( nr_of_materials, 0 ) AS nr_of_materials
            FROM
                Users
                LEFT JOIN
                (
                    SELECT
                        user_id,
                        count(*) AS nr_of_courses,
                        sum(total_views) AS nr_of_loads
                    FROM
                        Courses_Users
                    GROUP BY
                        user_id
                ) AS Courses_Users ON ( Users.user_id = Courses_Users.user_id )
                LEFT JOIN
                (
                    SELECT
                        user_id,
                        count(*) AS nr_of_posts
                    FROM
                        Posts
                    GROUP BY
                        user_id
                ) AS Posts ON ( Users.user_id = Posts.user_id )
                LEFT JOIN
                (
                    SELECT
                        user_id,
                        count(*) AS nr_of_materials
                    FROM
                        Materials
                    GROUP BY
                        user_id
                ) AS Materials ON ( Users.user_id = Materials.user_id )
            WHERE
                Users.form_id = ?
            $order_by",
            $form->getID()
        );

        // If we get error here, we die.
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getDebugInfo(), E_USER_ERROR );
        }

        return $result;
    }
}


?>