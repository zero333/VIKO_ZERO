<?php

/**
 * Contains the Mark class
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

require_once 'lib/DBInstance.php';
require_once 'lib/Lesson.php';
require_once 'lib/User.php';


/**
 * Mark class
 *
 */
class Mark
{
    // Private attributes
    /**
     * The value of mark
     *
     * @access private
     * @var string
     */
    var $_mark = null;

    /**
     * The student, the mark belongs to
     *
     * @access private
     * @var User
     */
    var $_student = null;

    /**
     * The lesson, the mark belongs to
     *
     * @access private
     * @var Lesson
     */
    var $_lesson = null;


    /**
     * Returns the lesson, this mark belongs to
     *
     * @access public
     * @return Lesson lesson
     */
    function getLesson()
    {
        return $this->_lesson;
    }


    /**
     * Sets lesson of the mark
     *
     * @access public
     * @param Lesson $lesson lesson
     */
    function setLesson( $lesson )
    {
        $this->_lesson = $lesson;
    }


    /**
     * Returns the student, this mark belongs to
     *
     * @access public
     * @return User student
     */
    function getStudent()
    {
        return $this->_student;
    }


    /**
     * Sets student who has the mark
     *
     * @access public
     * @param User $student student
     */
    function setStudent( $student )
    {
        $this->_student = $student;
    }


    /**
     * Retrieves the mark value from datavase
     *
     * Lesson and Student have to be specified for this to work
     *
     * @access private
     * @return boolean false if mark was not found
     */
    function _loadByLessonAndStudent()
    {
        // perform the query
        $select_mark = "
            SELECT
                mark
            FROM
                Marks
            WHERE
                lesson_id = ?
                AND
                user_id = ?
        ";
        $db = DBInstance::get();
        $mark = $db->getOne(
            $select_mark,
            array( $this->_lesson->getID(), $this->_student->getID() )
        );

        // If error is found here - just die
        if ( PEAR::isError($mark) ) {
            trigger_error( $mark->getMessage(), E_USER_ERROR );
        }


        if ( isset($mark) ) {
            // store results of query into active object
            $this->_mark = $mark;

            return true;
        }
        else {
            // the mark was not found
            return false;
        }
    }


    /**
     * Returns the value of private attribute
     *
     * If attribute is not set, then it is attempted to
     * retrieve from database. But if the lesson and student
     * are not set, the retrieval from database isn't possible,
     * so a fatal error is generated.
     *
     * @access private
     * @param string $attribute_name
     * @return mixed value of the requested attribute
     */
    function _getAttribute( $attribute_name )
    {
        if ( isset($this->$attribute_name) ) {
            return $this->$attribute_name;
        }
        elseif ( isset($this->_lesson) && isset($this->_student) ) {
            // the lesson and student are set, but the requested attribute is not,
            // so we load all the data associated with lesson and student from database,
            // and then return the requested attribute
            if ( $this->_loadByLessonAndStudent() ) {
                return $this->$attribute_name;
            }
            else {
                return false;
            }
        }
        else {
            trigger_error( "No lesson and student or $attribute_name specified.", E_USER_ERROR );
        }
    }


    /**
     * Returns value of mark
     *
     * @access public
     * @return string mark
     */
    function getMark()
    {
        return $this->_getAttribute("_mark");
    }


    /**
     * Sets mark
     *
     * @access public
     * @param string $mark mark
     */
    function setMark( $mark )
    {
        $this->_mark = $mark;
    }


    /**
     * Returns mark that is safe to insert into HTML
     *
     * @return string mark with special characters escaped
     */
    function getHTMLMark()
    {
        return htmlspecialchars( $this->getMark(), ENT_QUOTES );
    }


    /**
     * Saves Mark information into database
     *
     * If mark exists, it will be updated,
     * otherwise a new mark will be created.
     *
     * @access public
     * @return boolean  true on success
     *                  otherwise dies
     */
    function save()
    {
        if (
            isset($this->_mark) &&
            isset($this->_lesson) &&
            isset($this->_student) &&
            $this->_lesson->getID() > 0 &&
            $this->_student->getID() > 0
        ) {

            // if the insert fails, this means there already exist a record,
            // so we just update that record.
            if ( !$this->_insertMark() ) {
                $this->_updateMark();
            }
            return true;
        }
        else {
            // if some data is missing, give fatal error
            trigger_error( "All Mark data must be specified when saving.", E_USER_ERROR );
        }
    }


    /**
     * Updates the mark record in database
     *
     * @access private
     * @return boolean true on success, otherwise dies
     */
    function _updateMark()
    {
        $db = DBInstance::get();

        // update existing record
        $table_name = "Marks";
        $field_values = array(
            'mark' => $this->_mark
        );

        $where_clause =
            'lesson_id = ' . $db->quoteSmart( $this->_lesson->getID() ) . ' AND ' .
            'user_id = ' . $db->quoteSmart( $this->_student->getID() );

        $result = $db->autoExecute(
            $table_name,
            $field_values,
            DB_AUTOQUERY_UPDATE,
            $where_clause
        );

        // check for errors
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        return true;
    }


    /**
     * Inserts new Mark record into database
     *
     * @access private
     * @return boolean true on success,
     *                 false, when record already exists
     */
    function _insertMark()
    {
        $db = DBInstance::get();

        // create new record
        $table_name = "Marks";
        $field_values = array(
            'lesson_id' => $this->_lesson->getID(),
            'user_id' => $this->_student->getID(),
            'mark' => $this->_mark
        );

        $result = $db->autoExecute(
            $table_name,
            $field_values,
            DB_AUTOQUERY_INSERT
        );

        // check for errors
        if ( PEAR::isError($result) ) {
            if ( $result->getCode() == DB_ERROR_CONSTRAINT ) {
                return false;
            }
            else {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }
        }

        return true;
    }



    /**
     * Removes the mark from database
     *
     * The lesson and student must be specified for this to work.
     *
     * @access public
     * @return boolean true on successful delete.
     *                 false, if the mark did not exist
     */
    function delete()
    {
        // check, if ID is available
        if (
            isset($this->_lesson) &&
            isset($this->_student) &&
            $this->_lesson->getID() > 0 &&
            $this->_student->getID() > 0
        ) {
            // remove the course record from database
            $db = DBInstance::get();
            $result = $db->query(
                "DELETE FROM Marks WHERE lesson_id = ? AND user_id = ?",
                array( $this->_lesson->getID(), $this->_student->getID() )
            );

            // If we get error here, we die.
            if ( PEAR::isError($result) ) {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }

            if ( $db->affectedRows() == 1 ) {
                // we have successfully deleted one record from database
                return true;
            }
            else {
                // the mark was not found
                return false;
            }
        }
        else {
            // generate fatal error
            trigger_error( "No lesson and student specified when deleting.", E_USER_ERROR );
        }
    }


}



?>
