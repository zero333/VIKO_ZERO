<?php

/**
 * Contains the CourseList class
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

require_once 'Course.php';
require_once 'User.php';


/**
 * Routines for retrieving list of courses by various criteria
 *
 */
class CourseList
{

    /**
     * Returns all the courses, the student is in
     *
     * @access public
     *
     * @param User $student the student.
     *
     * @param array $order_by specifies the sorting order.
     *              Results are first sorted by first array element,
     *              then by second and so on - each element representing
     *              a column name. To achieve reverse sorting order write
     *              the column name as key and associate it with value "DESC".
     *              By default courses are sorted by their name.
     *              See {@link DBUtils}.
     *
     * @return array courses
     */
    static function getFromStudent( $student, $order_by = array("course_name") )
    {
        // ensure that student ID is specified
        if ( $student->getID() > 0 ) {

            $order_by_sql = DBUtils::createOrderBy( $order_by );

            // query all associated Courses
            $db = DBInstance::get();
            $result = $db->query(
                "
                SELECT
                    Courses.course_id AS course_id,
                    course_name,
                    course_subname,
                    teacher_id,
                    description,
                    assessment,
                    books,
                    scheduled_time,
                    classroom,
                    consultation
                FROM
                    Courses
                    JOIN
                    Courses_Users USING ( course_id )
                WHERE
                    user_id = ?
                $order_by_sql
                ",
                $student->getID()
            );

            // If we get error here, we die.
            if ( PEAR::isError($result) ) {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }

            // convert each retrieved record into course object
            $student_courses = array();
            while ( $result->fetchInto($row) ) {
                $course = new Course( $row['course_id'] );
                $course->readFromDatabaseRecord( $row );
                $student_courses[] = $course;
            }

            // return as array
            return $student_courses;
        }
        else {
            trigger_error( "No Student ID specified when requesting associated Courses.", E_USER_ERROR );
        }
    }

    /**
     * Returns all the courses associated with the teacher
     *
     * @access public
     *
     * @param User $teacher the student.
     *
     * @param array $order_by specifies the sorting order.
     *              Results are first sorted by first array element,
     *              then by second and so on - each element representing
     *              a column name. To achieve reverse sorting order write
     *              the column name as key and associate it with value "DESC".
     *              By default courses are sorted by their name.
     *              See {@link DBUtils}.
     *
     * @return array courses
     */
    static function getFromTeacher( $teacher, $order_by = array("course_name") )
    {
        // ensure that teacher ID is specified
        if ( $teacher->getID() > 0 ) {

            $order_by_sql = DBUtils::createOrderBy( $order_by );

            // query all associated Courses
            $db = DBInstance::get();
            $result = $db->query(
                "
                SELECT
                    *
                FROM
                    Courses
                WHERE
                    teacher_id = ?
                $order_by_sql
                ",
                $teacher->getID()
            );


            // If we get error here, we die.
            if ( PEAR::isError($result) ) {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }

            // convert each retrieved record into course object
            $teacher_courses = array();
            while ( $result->fetchInto($row) ) {
                $course = new Course( $row['course_id'] );
                $course->readFromDatabaseRecord( $row );
                $teacher_courses[] = $course;
            }

            // return as array
            return $teacher_courses;
        }
        else {
            trigger_error( "No Teacher ID specified when requesting associated Courses.", E_USER_ERROR );
        }
    }



}



?>
