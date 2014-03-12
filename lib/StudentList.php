<?php

/**
 * Contains the StudentList class
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
 * This class uses a database connection
 */
require_once 'DBInstance.php';
require_once 'DBUtils.php';

require_once 'Form.php';
require_once 'School.php';
require_once 'Course.php';
require_once 'Pear/Date.php';


/**
 * Routines for retrieving list of students by various criteria
 *
 */
class StudentList
{

    /**
     * Returns all the students of specified form
     *
     * @access public
     *
     * @param Form $form the form.
     *
     * @param array $order_by specifies the sorting order.
     *              Results are first sorted by first array element,
     *              then by second and so on - each element representing
     *              a column name. To achieve reverse sorting order write
     *              the column name as key and associate it with value "DESC".
     *              By default users are sorted by firstname and lastname.
     *              See {@link DBUtils}.
     *
     * @return array students
     */
    static function getFromForm( $form, $order_by = array("lastname","firstname") )
    {
        // ensure that Form ID is specified
        if ( $form->getID() > 0 ) {
            $order_by_sql = DBUtils::createOrderBy( $order_by );

            // query all associated Students
            $db = DBInstance::get();
            $result = $db->query(
                "
                SELECT
                    user_id,
                    username,
                    password,
                    firstname,
                    lastname,
                    email,
                    user_group,
                    school_id,
                    form_id
                FROM
                    Users
                WHERE
                    form_id = ?
                $order_by_sql
                ",
                $form->getID()
            );

            // If we get error here, we die.
            if ( PEAR::isError($result) ) {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }

            // convert each retrieved record into student object
            $form_students = array();
            while ( $result->fetchInto($row) ) {
                $student = new User( $row['user_id'] );
                $student->readFromDatabaseRecord( $row );
                $form_students[] = $student;
            }

            // return as array
            return $form_students;
        }
        else {
            trigger_error( "No Form ID specified when requesting associated Students.", E_USER_ERROR );
        }
    }

    /**
     * Returns all the students of specified course
     *
     * @access public
     *
     * @param Course $course the course.
     *
     * @param array $order_by specifies the sorting order.
     *              Results are first sorted by first array element,
     *              then by second and so on - each element representing
     *              a column name. To achieve reverse sorting order write
     *              the column name as key and associate it with value "DESC".
     *              By default users are sorted by firstname and lastname.
     *              See {@link DBUtils}.
     *
     * @return array students
     */
    static function getFromCourse( $course, $order_by = array("lastname","firstname") )
    {
        // ensure that Course ID is specified
        if ( $course->getID() > 0 ) {
            $order_by_sql = DBUtils::createOrderBy( $order_by );

            // query all associated Students
            $db = DBInstance::get();
            $result = $db->query(
                "
                SELECT
                    Users.user_id AS user_id,
                    username,
                    password,
                    firstname,
                    lastname,
                    email,
                    user_group,
                    school_id,
                    form_id
                FROM
                    Users JOIN Courses_Users USING (user_id)
                WHERE
                    course_id = ?
                $order_by_sql
                ",
                $course->getID()
            );

            // If we get error here, we die.
            if ( PEAR::isError($result) ) {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }

            // convert each retrieved record into student object
            $course_students = array();
            while ( $result->fetchInto($row) ) {
                $student = new User( $row['user_id'] );
                $student->readFromDatabaseRecord( $row );
                $course_students[] = $student;
            }

            // return as array
            return $course_students;
        }
        else {
            trigger_error( "No Course ID specified when requesting associated Students.", E_USER_ERROR );
        }
    }


    /**
     * Returns the number of students on course
     *
     * Use this to determine the total number of students on one course.
     *
     * @param Course $course the course.
     *
     * @return int count of students on course
     */
    static function getCountFromCourse( $course )
    {
        // ensure that Course ID is specified
        if ( $course->getID() > 0 ) {
            $db = DBInstance::get();
            $count = $db->getOne(
                "SELECT COUNT(user_id) FROM Courses_Users WHERE course_id=?",
                $course->getID()
            );

            // If we get error here, we die.
            if ( PEAR::isError($count) ) {
                trigger_error( $count->getMessage(), E_USER_ERROR );
            }

            return $count;
        }
        else {
            trigger_error( "No Course ID specified when retrieving the number of Students.", E_USER_ERROR );
        }
    }


    /**
     * Returns all the students of specified school
     *
     * Because the schools usually contain a lot of students, parameters
     * are provided to make the paging of the users list easy.
     * The $page_size specifies how many students to display on one page,
     * and the exact page can be pointed out using the $page_nr parameter.
     *
     * Use the getCountFromSchool to determine the total number of students in school.
     *
     * @access public
     *
     * @param School $school the school.
     *
     * @param array $order_by specifies the sorting order.
     *              Results are first sorted by first array element,
     *              then by second and so on - each element representing
     *              a column name. To achieve reverse sorting order write
     *              the column name as key and associate it with value "DESC".
     *              By default users are sorted by firstname and lastname.
     *              See {@link DBUtils}.
     *
     * @param int $page_size the number of users per page.
     *            If zero (the default) is specified, then everything
     *            is placed on the same page.
     *
     * @param int $page_nr which page should be returned.
     *            Defaults to first page (zero).
     *
     * @return array students
     */
    function getFromSchool( $school, $order_by = array("lastname","firstname"), $page_size=0, $page_nr=0 )
    {
        // ensure that School ID is specified
        if ( $school->getID() > 0 ) {
            if ( $page_size > 0 ) {
                if ( $page_nr >= 0 ) {
                    $limit = "LIMIT " . ($page_nr*$page_size) . "," . $page_size;
                }
                else {
                    trigger_error( "Page number must be positive.", E_USER_ERROR);
                }
            }
            else {
                $limit = "";
            }

            $order_by_sql = DBUtils::createOrderBy( $order_by );

            // query all associated Students
            $db = DBInstance::get();
            $result = $db->query(
                "
                SELECT
                    user_id,
                    username,
                    password,
                    firstname,
                    lastname,
                    email,
                    user_group,
                    school_id,
                    form_id
                FROM
                    Users
                WHERE
                    school_id = ? AND
                    user_group = 'STUDENT'
                $order_by_sql
                $limit
                ",
                $school->getID()
            );


            // If we get error here, we die.
            if ( PEAR::isError($result) ) {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }

            // convert each retrieved record into student object
            $school_students = array();
            while ( $result->fetchInto($row) ) {
                $student = new User( $row['user_id'] );
                $student->readFromDatabaseRecord( $row );
                $school_students[] = $student;
            }

            // return as array
            return $school_students;
        }
        else {
            trigger_error( "No School ID specified when requesting associated Students.", E_USER_ERROR );
        }
    }


    /**
     * Returns the number of students in school
     *
     * Use this to determine the total number of students in one school.
     *
     * @param School $school the school.
     *
     * @return int count of students in school
     */
    function getCountFromSchool( $school )
    {
        // ensure that School ID is specified
        if ( $school->getID() > 0 ) {
            $db = DBInstance::get();
            $count = $db->getOne(
                "SELECT COUNT(user_id) FROM Users WHERE school_id=? AND user_group='STUDENT' ",
                $school->getID()
            );

            // If we get error here, we die.
            if ( PEAR::isError($count) ) {
                trigger_error( $count->getMessage(), E_USER_ERROR );
            }

            return $count;
        }
        else {
            trigger_error( "No School ID specified when retrieving the number of Students.", E_USER_ERROR );
        }
    }

}



?>
