<?php

/**
 * Contains the Lesson class
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
 * This is a child class of TableAbstraction
 */
require_once 'lib/TableAbstraction.php';

/**
 * The class contains a course object
 */
require_once 'lib/Course.php';

/**
 * The class contains a date object
 */
require_once 'Pear/Date.php';

/**
 * The class transforms BBCode to HTML
 */
require_once "lib/VikoContentFormatter.php";


/**
 * Lesson class
 *
 */
class Lesson extends TableAbstraction
{
    /**
     * Returns course
     *
     * @return Course course object
     */
    function getCourse()
    {
        return $this->getAttribute("_course");
    }


    /**
     * Sets course
     *
     * @param Course $course course object
     */
    function setCourse( $course )
    {
        $this->_course = $course;
    }


    /**
     * Returns the lesson topic description
     *
     * @return string the topic
     */
    function getTopic()
    {
        return $this->getAttribute("_lesson_topic");
    }


    /**
     * Sets topic
     *
     * @param string $topic topic
     */
    function setTopic( $topic )
    {
        $this->_lesson_topic = $topic;
    }


    /**
     * Returns HTML-formatted lesson topic
     *
     * @return string topic that is safe to insert in HTML
     */
    function getHTMLTopic()
    {
        return VikoContentFormatter::format( $this->getTopic() );
    }


    /**
     * Returns the date when the lesson is cheduled
     *
     * @return Date the date of the lesson
     */
    function getDate()
    {
        return $this->getAttribute("_lesson_date");
    }


    /**
     * Sets lesson date
     *
     * @param Date $date date
     */
    function setDate( $date )
    {
        $this->_lesson_date = $date;
    }


    /**
     * Returns the hometask description
     *
     * @return string the hometask
     */
    function getHometask()
    {
        return $this->getAttribute("_hometask");
    }


    /**
     * Sets hometask description
     *
     * @param string $hometask hometask
     */
    function setHometask( $hometask )
    {
        $this->_hometask = $hometask;
    }


    /**
     * Returns HTML-formatted hometask description
     *
     * @return string hometask description that is safe to insert in HTML
     */
    function getHTMLHometask()
    {
        return VikoContentFormatter::format( $this->getHometask() );
    }


    /**
     * Returns the hometask date
     *
     * @return Date the hometask date
     */
    function getHometaskDate()
    {
        return $this->getAttribute("_hometask_date");
    }


    /**
     * Sets hometask date
     *
     * @param Date $hometask_date hometask date
     */
    function setHometaskDate( $hometask_date )
    {
        $this->_hometask_date = $hometask_date;
    }


    /**
     * Returns the type of marks for this lesson
     *
     * @return string the mark type
     */
    function getMarktype()
    {
        return $this->getAttribute("_marktype");
    }


    /**
     * Sets the type of marks for this lesson
     *
     * The parameter can be either 'LESSON' or 'PRELIM'.
     *
     * @param string $marktype type of mark
     */
    function setMarktype( $marktype )
    {
        $this->_marktype = $marktype;
    }


    /**
     * Returns name of the table containing lessons
     */
    function getTableName()
    {
        return "Lessons";
    }


    /**
     * Returns the primary key field name of the lessons table
     */
    function getIDFieldName()
    {
        return "lesson_id";
    }


    /**
     * Implementation of reading a database record
     */
    function readFromDatabaseRecord( $record )
    {
        $this->_lesson_topic = $record['lesson_topic'];
        $this->_lesson_date = new Date( $record['lesson_date'] );
        $this->_hometask = $record['hometask'];
        $this->_hometask_date = new Date( $record['hometask_date'] );
        $this->_marktype = $record['marktype'];
        $this->_course = new Course( $record['course_id'] );
    }


    /**
     * Implementation of composing a database record
     */
    function writeToDatabaseRecord()
    {
        // the following properties may not be NULL
        if ( !isset($this->_lesson_topic) ) {
            trigger_error("Topic must be specified when saving lesson.", E_USER_ERROR);
        }
        if ( !isset($this->_lesson_date) ) {
            trigger_error("Date must be specified when saving lesson.", E_USER_ERROR);
        }
        if ( !isset($this->_hometask) ) {
            trigger_error("Hometask must be specified when saving lesson.", E_USER_ERROR);
        }
        if ( !isset($this->_hometask_date) ) {
            trigger_error("Hometask date must be specified when saving lesson.", E_USER_ERROR);
        }
        if ( !isset($this->_marktype) ) {
            trigger_error("Marktype must be specified when saving lesson.", E_USER_ERROR);
        }
        if ( !isset($this->_course) && $this->_course->getID() > 0 ) {
            trigger_error("Course must be specified when saving lesson.", E_USER_ERROR);
        }

        return array(
            'lesson_topic' => $this->_lesson_topic,
            'lesson_date' => $this->_lesson_date->getDate(),
            'hometask' => $this->_hometask,
            'hometask_date' => $this->_hometask_date->getDate(),
            'marktype' => $this->_marktype,
            'course_id' => $this->_course->getID()
        );
    }


    /**
     * Removes the lesson from database
     *
     * The ID must be specified for this to work.
     * By deleting the lesson, all associated marks are also deleted.
     *
     * @access public
     * @return boolean true on successful delete.
     *                 false, if the lesson with ID did not exist
     */
    function delete()
    {
        // first delete the lesson record itself
        if ( parent::delete() ) {

            // After that remove all the marks given in this lesson
            $db = DBInstance::get();
            echo $this->_id;
            $db->query("DELETE FROM lesson WHERE task_id = ?", $this->_id );

            return true;
        }
        else {
            return false;
        }
    }
}



?>
