<?php

/**
 * Contains the LessonList class
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

require_once 'Lesson.php';
require_once 'Course.php';


/**
 * Routines for retrieving list of lessons by various criteria
 *
 */
class LessonList
{

    /**
     * Returns all the lessons of specified course
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
     *              By default lessons are sorted by lessons_date and lesson_id.
     *              See {@link DBUtils}.
     *
     * @return array lessons
     */
    static function getFromCourse( $course, $order_by = array("lesson_date", "lesson_id") )
    {
        // ensure that Course ID is specified
        if ( $course->getID() > 0 ) {
            $order_by_sql = DBUtils::createOrderBy( $order_by, 'NO COLLATE' );

            // query all associated lessons
            $db = DBInstance::get();
            $result = $db->query(
                "
                SELECT
                    *
                FROM
                    Lessons
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

            // convert each retrieved record into lesson object
            $course_lessons = array();
            while ( $result->fetchInto($row) ) {
                $lesson = new Lesson( $row['lesson_id'] );
                $lesson->readFromDatabaseRecord( $row );
                $course_lessons[] = $lesson;
            }

            // return as array
            return $course_lessons;
        }
        else {
            trigger_error( "No Course ID specified when requesting associated Lessons.", E_USER_ERROR );
        }
    }

}



?>
