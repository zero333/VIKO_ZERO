<?php

/**
 * Contains the Task class
 *
 * PHP version 5
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
 * Task class
 *
 */
class Task extends TableAbstraction
{
    /**
     * Returns course
     *
     * @return Course course object
     */
    function getTask()
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
     * Returns the task topic description
     *
     * @return string the topic
     */
    function getTitle()
    {
        return $this->getAttribute("_task_title");
    }


    /**
     * Sets topic
     *
     * @param string $topic topic
     */
    function setTitle( $title )
    {
        $this->_task_title = $title;
    }

    function getTaskContent()
    {
        return $this->getAttribute("_task_content");
    }
    
    
    /**
     * Returns HTML-formatted task topic
     *
     * @return string topic that is safe to insert in HTML
     */
    function getHTMLTopic()
    {
        return VikoContentFormatter::format( $this->getTitle() );
    }


    /**
     * Returns the date when the task is cheduled
     *
     * @return Date the date of the task
     */
    function getDate()
    {
        return $this->getAttribute("_task_date");
    }


    /**
     * Sets task date
     *
     * @param Date $date date
     */
    function setDate( $date )
    {
        $this->_task_date = $date;
    }


    /**
     * Returns the task description
     *
     * @return string the hometask
     */
    function getTaskDesc()
    {
        return $this->getAttribute("_task_desc");
    }

    
    
    
    function getHTMLDesc()
    {
        return VikoContentFormatter::format( $this->getTaskDesc() );
    }    
    
    

    /**
     * Sets task description
     *
     * @param string $hometask hometask
     */
    function setTaskContent( $desc )
    {
        $this->_taskContent = $desc;
    }


    /**
     * Returns HTML-formatted hometask description
     *
     * @return string hometask description that is safe to insert in HTML
     */
    function getHTMLTaskContent()
    {
        return VikoContentFormatter::format( $this->getTaskContent() );
    }


    /**
     * Returns the hometask date
     *
     * @return Date the hometask date
     */
    function getHometaskDate()
    {
        return $this->getAttribute("_date");
    }


    /**
     * Sets task expire date
     *
     * @param Date $task_exp_date hometask date
     */
    function setTaskExpDate( $task_exp_date )
    {
        $this->_task_exp_date = $task_exp_date;
    }


    /**
     * Returns name of the table containing tasks templates
     */
    function getTemplateTableName()
    {
        return "Tasks";
    }

    /**
     * Returns name of the table containing users tasks
     */
    function getTableName()
    {
        return "Tasks_users";
    }
    
    
    /**
     * Returns the primary key field name of the tasks table
     */
    function getIDFieldName()
    {
        return "id";
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
     * Implementation of reading a database record
     */
    function readFromDatabaseRecord( $record )
    {
        $this->_task_title = $record['title'];
        $this->_task_desc = $record['description'];
        $this->_task_content = $record['content'];
        $this->_task_date = !empty($record['task_date']) ? new Date($record['task_date']) : NULL;
    }


    /**
     * Implementation of composing a database record
     */
    function writeToDatabaseRecord()
    {
        
        // the following properties may not be NULL
        if ( !isset($this->_task_title) ) {
            trigger_error("Topic must be specified when saving task.", E_USER_ERROR);
        }
//        if ( !isset($this->_task_date) ) {
//            trigger_error("Date must be specified when saving task.", E_USER_ERROR);
//        }
        if ( !isset($this->_taskContent) ) {
            trigger_error("Task content must be specified when saving task.", E_USER_ERROR);
        }
//        if ( !isset($this->_task_date) ) {
//            trigger_error("Hometask date must be specified when saving task.", E_USER_ERROR);
//        }
//        if ( !isset($this->_marktype) ) {
//            trigger_error("Marktype must be specified when saving task.", E_USER_ERROR);
//        }
        if ( !isset($this->_course) && $this->_course->getID() > 0 ) {
            trigger_error("Course must be specified when saving task.", E_USER_ERROR);
        }

        return array(
            'title'         => $this->_task_title,
            'task_date'     => $this->_task_date->getDate(),
            'content'       => $this->_taskContent,
            'task_exp_date' => $this->_task_exp_date->getDate(),
        );
    }


    /**
     * Removes the task from database
     *
     * The ID must be specified for this to work.
     * By deleting the task, all associated marks are also deleted.
     *
     * @access public
     * @return boolean true on successful delete.
     *                 false, if the task with ID did not exist
     */
    function delete()
    {
        // first delete the task record itself
        if ( parent::delete() ) {

            // After that remove all the marks given in this task
            $db = DBInstance::get();
            $db->query("DELETE FROM tasks_users WHERE task_id = ?", $this->_id );

            return true;
        }
        else {
            return false;
        }
    }
    
    function update()
    {
        // update the task record itself
        if ( parent::_updateRecord() ) {

            // update query
            $db = DBInstance::get();
            $db->query("UPDATE Tasks_users SETS title, description, content, task_date, task_exp_date "
                     . "VALUES(?, ?, ?, ?, ?) WHERE task_id = ?", $this->_title, $this->_description, 
                        $this->_content, $this->_task_date, $this->_task_exp_date, $this->_id );

            return true;
        }
        else {
            return false;
        }
    }
    
    function insert()
    {
        // first delete the task record itself
        if ( parent::_insertRecord() ) {

            // After that remove all the marks given in this task
            $db = DBInstance::get();
            $db->query("INSERT INTO Tasks_users WHERE user_id = ?", $this->_id );

            return true;
        }
        else {
            return false;
        }
    }    
    
    
}



?>
