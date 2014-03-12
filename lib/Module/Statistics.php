<?php

/**
 * Contains the Module_Statistics class
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
 * Shows the statictics of all schools or one school in VIKO
 *
 * <ul>
 * <li>/statistics  -  stats of all schools or stats of the school where user belongs.</li>
 * <li>/statistics/#school_id  -  stats of shool with ID #school_id.</li>
 * </ul>
 */
class Module_Statistics extends Module {

    /**
     * The school to show statistics about
     *
     * If null, then statistics of all schools is shown.
     *
     * @var School
     */
    var $_selected_school = null;

    /**
     * Constructs new Statistics Module
     *
     * @param array $parameters  parameters to the module
     */
    function Module_Statistics( $parameters )
    {
        if ( isset($parameters[0]) && (int)$parameters[0] > 0 ) {
            $this->_selected_school = new School( (int)$parameters[0] );
        }

        // schooladmins may only access the school of their own
        if ( !$_SESSION['user']->hasAccessToGroup("ADMIN") ) {
            $this->_selected_school = $_SESSION['user']->getSchool();
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
        return "statistics";
    }


    /**
     * Log out from VIKO
     *
     * @return string HTML content
     */
    function toHTML()
    {
        // access is only allowed for schooladmins and admins
        if ( !$_SESSION['user']->hasAccessToGroup("SCHOOLADMIN") ) {
            return $this->accessDeniedPage( _("You are not authorized to access school statistics.") );
        }

        // test that the school to be accessed really exists
        if ( isset($this->_selected_school) && !$this->_selected_school->loadFromDatabaseByID() ) {
            return $this->errorPage( _("The school you are trying to access does not exist.") );
        }

        if ( isset( $this->_selected_school ) ) {
            return $this->_schoolStatistics();
        }
        else {
            return $this->_globalStatistics();
        }
    }


    /**
     * Statistics about all the schools in VIKO
     *
     * @access private
     * @return string HTML
     */
    function _globalStatistics()
    {
        $html = $this->title( _("VIKO server statistics") );

        $html .= $this->_schoolsTable();

        $html .= HTML::actionList(
            HTML::backLink( $this->URI("my-viko"), _("Return to the main page") ),
            HTML::newLink( $this->URI("school", "add"), _("Add school") )
        );

        return $html;
    }


    /**
     * Returns HTML table with all schools in VIKO
     *
     * @access private
     * @return string HTML
     */
    function _schoolsTable()
    {
        $db = DBInstance::get();
        $orderby = DBUtils::createOrderBy( array( 'school_name' ) );
        $result = $db->query("SELECT school_id, school_name FROM Schools $orderby");

        if ( $result->numRows() == 0 ) {
            return HTML::notice( _("There are no schools in VIKO. Create new school using the link below.") );
        }

        $table = new VikoTable( _("List of all schools in VIKO server.") );
        $table->addHeader(
            _("School name"),
            _("Nr of teachers"),
            _("Nr of students"),
            _("Nr of courses"),
            _("Size of files"),
            HTML::abbr( "X", _("Delete school") )
        );

        $nr_of_schools = 0;
        $nr_of_active_schools = 0;
        $total_teachers = 0;
        $total_students = 0;
        $total_courses = 0;
        $total_files = 0;

        while ( $result->fetchInto( $school_r ) ) {
            $school = new School( $school_r['school_id'] );
            $school->setName( $school_r['school_name'] );

            $school_link = HTML::a( $this->URI("statistics", $school->getID()), $school->getHTMLName() );

            $delete_title = sprintf( _("Delete %s"), $school->getHTMLName() );
            $delete_link = HTML::a(
                $this->URI("school", "delete", $school->getID()),
                "X",
                $delete_title
            );

            $nr_of_schools++;

            $nr_of_teachers = $db->getOne(
                "SELECT COUNT(*) FROM Users WHERE user_group='TEACHER' AND school_id=?",
                array( $school->getID() )
            );
            $total_teachers += $nr_of_teachers;

            $nr_of_students = $db->getOne(
                "SELECT COUNT(*) FROM Users WHERE user_group='STUDENT' AND school_id=?",
                array( $school->getID() )
            );
            $total_students += $nr_of_students;
            // count active schools
            if ( $nr_of_students >= 10 ) {
                $nr_of_active_schools ++;
            }

            $nr_of_courses = $db->getOne(
                "SELECT COUNT(*) FROM
                    Courses JOIN
                    Users ON ( Courses.teacher_id = Users.user_id )
                WHERE
                    school_id=?",
                array( $school->getID() )
            );
            $total_courses += $nr_of_courses;

            $size_of_files = $db->getOne(
                "SELECT SUM(material_size)/1024/1024 FROM
                    Materials
                    JOIN
                    Courses ON ( Materials.course_id = Courses.course_id )
                    JOIN
                    Users ON ( Courses.teacher_id = Users.user_id )
                WHERE
                    school_id=?",
                array( $school->getID() )
            );
            $total_files += $size_of_files;
            $size_of_files = round($size_of_files) . " MB";

            $table->addRow(
                $school_link,
                $nr_of_teachers,
                $nr_of_students,
                $nr_of_courses,
                $size_of_files,
                $delete_link
            );

        }

        $table->addFooter(
            _("Total"),
            $total_teachers,
            $total_students,
            $total_courses,
            round($total_files) . " MB",
            ""
        );

        $table->setColumnsToNumeric( array(1,2,3,4), TABLE_BODY_AND_FOOTER );
        $table->setColumnToDelete( 5 );

        $html = $table->toHTML();

        $schools_text = _("Total %nr1% schools in VIKO. %nr2% of these having at least 10 students.");
        $schools_text = str_replace( '%nr1%', HTML::strong( $nr_of_schools ), $schools_text );
        $schools_text = str_replace( '%nr2%', HTML::strong( $nr_of_active_schools ), $schools_text );
        $html .= HTML::p( $schools_text );

        return $html;
    }


    /**
     * Statistics about one school
     *
     * @access private
     * @return string HTML
     */
    function _schoolStatistics()
    {
        $school = $this->_selected_school;
        $html = $this->title( _("School statistics") . ': ' . $school->getHTMLName() );

        // admins need a simple way to add-remove schooladmins a school
        if ( $_SESSION['user']->hasAccessToGroup("ADMIN") ) {
            $html .= "<h3>" . _("School administrators") . "</h3>";
            $html .= $this->_schooladminsManagement();
        }

        $html .= "<h3>" . _("Users") . "</h3>";
        $html .= $this->_usersStatistics();

        $html .= "<h3>" . _("General statistics") . "</h3>";
        $html .= $this->_generalSchoolStatistics();

        $html .= "<h3>" . _("Courses") . "</h3>";
        $html .= $this->_coursesStatistics();

        // backlink
        if ( $_SESSION['user']->hasAccessToGroup("ADMIN") ) {
            $html.= HTML::actionList(
                HTML::backLink( $this->URI("statistics"), _("Return to the list of all schools") ),
                HTML::actionLink( $this->URI("school", "edit", $this->_selected_school->getID()), _("Change school name"), "edit" )
            );
        }
        else {
            $html.= HTML::backLink( $this->URI("my-viko"), _("Return to the main page"), STANDALONE );
        }

        return $html;
    }


    /**
     * Controls for adding/deleting/managing schooladmins
     *
     * @access private
     * @return string HTML
     */
    function _schooladminsManagement()
    {
        $list = $this->_schooladminsList();

        $links = HTML::newLink(
            $this->URI("user", "add", "schooladmin", "0", $this->_selected_school->getID()),
            _("Add school administrator"),
            STANDALONE
        );

        return $list . $links;
    }


    /**
     * Table with all the schooladmins in the school
     *
     * @access private
     * @return string HTML
     */
    function _schooladminsList()
    {
        $db = DBInstance::get();
        $orderby = DBUtils::createOrderBy( array( 'lastname', 'firstname' ) );
        $result = $db->query(
            "SELECT
                user_id,
                lastname,
                firstname
            FROM
                Users
            WHERE
                school_id=?
                AND
                user_group='SCHOOLADMIN'",
            array( $this->_selected_school->getID() )
        );

        // If we get error here, we die.
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        if ( $result->numRows() == 0 ) {
            return HTML::notice( _("There are no school administrators in this school.") );
        }

        $table = new VikoTable( _("List of all schooladmins in this school") );
        $table->addHeader(
            _("Name"),
            HTML::abbr( "X", _("Delete user") )
        );

        while ( $result->fetchInto( $row ) ) {
            $user = new User();
            $user->setID( $row['user_id'] );
            $user->setFirstName( $row['firstname'] );
            $user->setLastName( $row['lastname'] );
            $user_link = $user->getUserLink( LASTNAME_FIRSTNAME );

            $user_name = $user->getFullName( LASTNAME_FIRSTNAME );
            $delete_title = sprintf( _("Delete %s"), $user_name );
            $delete_link = HTML::a(
                $this->URI('user', 'delete', $user->getID()),
                'X',
                $delete_title
            );

            $table->addRow(
                $user_link,
                $delete_link
            );
        }

        $table->setColumnToDelete( 1 );

        return $table->toHTML();
    }


    /**
     * General statistics about the school
     *
     * @access private
     * @return string HTML
     */
    function _generalSchoolStatistics()
    {
        $table = new VikoTable( _("Number of classes, courses and lessons in the school") );
        $table->addHeader(
            _("Entity"),
            _("Count")
        );

        $db = DBInstance::get();

        // Forms
        $nr_of_forms = $db->getOne(
            "SELECT COUNT(*) FROM Forms WHERE school_id=?",
            array( $this->_selected_school->getID() )
        );
        $table->addRow(
            _("Forms"),
            $nr_of_forms
        );

        // Courses
        $nr_of_courses = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Courses JOIN
                Users ON (Courses.teacher_id=Users.user_id)
            WHERE
                school_id=?
            ",
            array( $this->_selected_school->getID() )
        );
        $table->addRow(
            _("Courses"),
            $nr_of_courses
        );

        // Lessons
        $nr_of_lessons = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Lessons
                JOIN
                Courses ON (Courses.course_id=Lessons.course_id)
                JOIN
                Users ON (Courses.teacher_id=Users.user_id)
            WHERE
                school_id=?
            ",
            array( $this->_selected_school->getID() )
        );
        $table->addRow(
            _("Lessons"),
            $nr_of_lessons
        );

        $table->setColumnsToNumeric( array( 1 ) );

        return $table->toHTML();
    }


    /**
     * General statistics about user groups in school
     *
     * @access private
     * @return string HTML
     */
    function _usersStatistics()
    {
        $table = new VikoTable( _("Number of users from different groups in the school") );
        $table->addHeader(
            _("User group"),
            _("Nr of users")
        );

        // get information about all user groups of this school
        $db = DBInstance::get();
        $users = $db->query(
            "SELECT
                user_group,
                COUNT(*) as count
            FROM
                Users
            WHERE
                school_id=?
            GROUP BY
                user_group
            ORDER BY
                count DESC
            ",
            array( $this->_selected_school->getID() )
        );

        $total_users = 0;

        if ( $users->numRows() == 0 ) {
            return HTML::notice( _("There are no users in this school.") );
        }

        while ( $users->fetchInto( $user_group ) ) {
            switch ( $user_group['user_group'] ) {
                case 'ADMIN': $group_name = _("Administrators");
                break;
                case 'SCHOOLADMIN': $group_name = _("School administrators");
                break;
                case 'TEACHER': $group_name = _("Teachers");
                break;
                case 'STUDENT': $group_name = _("Students");
                break;
            }

            $table->addRow(
                $group_name,
                $user_group['count']
            );

            $total_users += $user_group['count'];
        }

        $table->addFooter(
            _("Total"),
            $total_users
        );

        $table->setColumnsToNumeric( array( 1 ), TABLE_BODY_AND_FOOTER );

        return $table->toHTML();
    }


    /**
     * Statistics about all the courses in school
     *
     * @access private
     * @return string HTML
     */
    function _coursesStatistics()
    {
        // table header
        $table = new VikoTable( _("List of all courses in this school.") );
        $table->addHeader(
            _("Course name"),
            _("Nr of students"),
            _("Nr of visits"),
            _("Nr of grades"),
            _("Nr of messages"),
            _("Nr of materials"),
            _("Size of files")
        );

        $db = DBInstance::get();
        $orderby = DBUtils::createOrderBy( array( 'course_name' ) );
        $result = $db->query(
            "SELECT
                course_id AS id,
                course_name AS name
            FROM
                Courses
                JOIN
                Users ON (Courses.teacher_id=Users.user_id)
            WHERE
                school_id=?
            $orderby",
            array( $this->_selected_school->getID() )
        );

        if ( $result->numRows() == 0 ) {
            return HTML::notice( _("There are no courses in this school.") );
        }

        $total_students = 0;
        $total_visits = 0;
        $total_grades = 0;
        $total_posts = 0;
        $total_materials = 0;
        $total_filesize = 0;

        while ( $result->fetchInto( $course_record ) ) {

            $course = new Course();
            $course->setID( $course_record['id'] );
            $course->setName( $course_record['name'] );
            $course_name = $course->getHTMLName();

            $nr_of_students = StudentList::getCountFromCourse( $course );
            $total_students += $nr_of_students;

            $nr_of_visits = $db->getOne(
                "SELECT SUM(total_views) FROM Courses_Users WHERE course_id=?",
                array( $course->getID() )
            );
            // if not a single user is on any of the courses,
            // the sum will result in empty string instead of zero.
            $nr_of_visits += 0;
            $total_visits += $nr_of_visits;

            $nr_of_grades = $db->getOne(
                "SELECT COUNT(*) FROM Marks JOIN Lessons USING (lesson_id) WHERE course_id=?",
                array( $course->getID() )
            );
            $total_grades += $nr_of_grades;

            $nr_of_posts = $db->getOne(
                "SELECT COUNT(*) FROM Posts JOIN Topics USING (topic_id) WHERE course_id=?",
                array( $course->getID() )
            );
            $total_posts += $nr_of_posts;

            $nr_of_materials = $db->getOne(
                "SELECT COUNT(*) FROM Materials WHERE course_id=?",
                array( $course->getID() )
            );
            $total_materials += $nr_of_materials;

            $size_of_files = $db->getOne(
                "SELECT SUM(material_size) FROM Materials WHERE course_id=?",
                array( $course->getID() )
            );
            $total_filesize += $size_of_files;
            $size_of_files = round( $size_of_files / 1024 ) . ' KB';

            $table->addRow(
                $course_name,
                $nr_of_students,
                $nr_of_visits,
                $nr_of_grades,
                $nr_of_posts,
                $nr_of_materials,
                $size_of_files
            );
        }

        $table->addFooter(
            _("Total"),
            $total_students,
            $total_visits,
            $total_grades,
            $total_posts,
            $total_materials,
            round( $total_filesize / 1024 / 1024 ) . ' MB'
        );

        $table->setColumnsToNumeric( array( 1,2,3,4,5,6 ), TABLE_BODY_AND_FOOTER );

        return $table->toHTML();
    }
}


?>