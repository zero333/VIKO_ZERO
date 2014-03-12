<?php

/**
 * Contains the Course class
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
 * The class contains a user object (the teacher)
 */
require_once 'lib/User.php';

/**
 * The class contains a school object
 */
require_once 'lib/School.php';

/**
 * The class transforms BBCode to HTML
 */
require_once "lib/VikoContentFormatter.php";

/**
 * When deleting course, all associated materials also have to get deleted
 */
require_once 'lib/MaterialFactory.php';

/**
 * Course class
 *
 */
class Course extends TableAbstraction
{
    /**
     * Returns teacher
     *
     * @return User teacher object
     */
    function getTeacher()
    {
        return $this->getAttribute("_teacher");
    }


    /**
     * Sets teacher
     *
     * @param User $teacher teacher object
     */
    function setTeacher( $teacher )
    {
        $this->_teacher = $teacher;
    }



    /**
     * Returns the course name
     *
     * @return string the name
     */
    function getName()
    {
        return $this->getAttribute("_name");
    }


    /**
     * Sets course name
     *
     * @param string $name name
     */
    function setName( $name )
    {
        $this->_name = $name;
    }


    /**
     * Returns course name that is safe to insert into HTML
     *
     * @return string name of the course with special characters escaped
     */
    function getHTMLName()
    {
        return htmlspecialchars( $this->getName(), ENT_QUOTES );
    }


    /**
     * Returns the course subtitle
     *
     * @return string the subname
     */
    function getSubName()
    {
        return $this->getAttribute("_subname");
    }


    /**
     * Sets course subtitle
     *
     * @param string $subname subname
     */
    function setSubName( $subname )
    {
        $this->_subname = $subname;
    }


    /**
     * Returns course subname that is safe to insert into HTML
     *
     * @return string subname of the course with special characters escaped
     */
    function getHTMLSubName()
    {
        return htmlspecialchars( $this->getSubName(), ENT_QUOTES );
    }


    /**
     * Returns the course description
     *
     * @return string the description
     */
    function getDescription()
    {
        return $this->getAttribute("_description");
    }


    /**
     * Sets course description
     *
     * @param string $description description
     */
    function setDescription( $description )
    {
        $this->_description = $description;
    }


    /**
     * Returns course description that is safe to insert into HTML
     *
     * @return string description of the course with special characters escaped
     */
    function getHTMLDescription()
    {
        return VikoContentFormatter::format( $this->getDescription() );
    }


    /**
     * Returns the course assessment
     *
     * @return string the assessment
     */
    function getAssessment()
    {
        return $this->getAttribute("_assessment");
    }


    /**
     * Sets course assessment
     *
     * @param string $assessment assessment
     */
    function setAssessment( $assessment )
    {
        $this->_assessment = $assessment;
    }


    /**
     * Returns course assessment that is safe to insert into HTML
     *
     * @return string assessment of the course with special characters escaped
     */
    function getHTMLAssessment()
    {
        return VikoContentFormatter::format( $this->getAssessment() );
    }


    /**
     * Returns the course books
     *
     * @return string the books
     */
    function getBooks()
    {
        return $this->getAttribute("_books");
    }


    /**
     * Sets course books
     *
     * @param string $books books
     */
    function setBooks( $books )
    {
        $this->_books = $books;
    }



    /**
     * Returns course books that is safe to insert into HTML
     *
     * @return string books of the course with special characters escaped
     */
    function getHTMLBooks()
    {
        return VikoContentFormatter::format( $this->getBooks() );
    }


    /**
     * Returns the course classroom
     *
     * @return string the classroom
     */
    function getClassRoom()
    {
        return $this->getAttribute("_classroom");
    }


    /**
     * Sets course classroom
     *
     * @param string $classroom classroom
     */
    function setClassRoom( $classroom )
    {
        $this->_classroom = $classroom;
    }


    /**
     * Returns course classroom that is safe to insert into HTML
     *
     * @return string classroom of the course with special characters escaped
     */
    function getHTMLClassRoom()
    {
        return VikoContentFormatter::format( $this->getClassRoom() );
    }


    /**
     * Returns the course scheduled_time
     *
     * @return string the scheduled_time
     */
    function getScheduledTime()
    {
        return $this->getAttribute("_scheduled_time");
    }


    /**
     * Sets course scheduled_time
     *
     * @param string $scheduled_time scheduled_time
     */
    function setScheduledTime( $scheduled_time )
    {
        $this->_scheduled_time = $scheduled_time;
    }


    /**
     * Returns course scheduled_time that is safe to insert into HTML
     *
     * @return string scheduled_time of the course with special characters escaped
     */
    function getHTMLScheduledTime()
    {
        return VikoContentFormatter::format( $this->getScheduledTime() );
    }


    /**
     * Returns the course consultation
     *
     * @return string the consultation
     */
    function getConsultation()
    {
        return $this->getAttribute("_consultation");
    }


    /**
     * Sets course consultation
     *
     * @param string $consultation consultation
     */
    function setConsultation( $consultation )
    {
        $this->_consultation = $consultation;
    }


    /**
     * Returns course consultation that is safe to insert into HTML
     *
     * @return string consultation of the course with special characters escaped
     */
    function getHTMLConsultation()
    {
        return VikoContentFormatter::format( $this->getConsultation() );
    }


    /**
     * Returns the school, where the course belongs to
     *
     * @access public
     * @return School school of the course
     */
    function getSchool()
    {
        $teacher = $this->getTeacher();
        return $teacher->getSchool();
    }


    /**
     * Returns name of the table containing courses
     */
    function getTableName()
    {
        return "Courses";
    }


    /**
     * Returns the primary key field name of the courses table
     */
    function getIDFieldName()
    {
        return "course_id";
    }


    /**
     * Implementation of reading a database record
     */
    function readFromDatabaseRecord( $record )
    {
        $this->_name = $record['course_name'];
        $this->_subname = $record['course_subname'];
        $this->_description = $record['description'];
        $this->_assessment = $record['assessment'];
        $this->_books = $record['books'];
        $this->_scheduled_time = $record['scheduled_time'];
        $this->_classroom = $record['classroom'];
        $this->_consultation = $record['consultation'];
        $this->_teacher = new User( $record['teacher_id'] );
    }


    /**
     * Implementation of composing a database record
     */
    function writeToDatabaseRecord()
    {
        // the following properties may not be NULL
        if ( !isset($this->_name) ) {
            trigger_error("Course name must be specified when saving course.", E_USER_ERROR);
        }
        if ( !isset($this->_subname) ) {
            trigger_error("Course subname must be specified when saving course.", E_USER_ERROR);
        }
        if ( !isset($this->_description) ) {
            trigger_error("Description must be specified when saving course.", E_USER_ERROR);
        }
        if ( !isset($this->_assessment) ) {
            trigger_error("Assessment must be specified when saving course.", E_USER_ERROR);
        }
        if ( !isset($this->_books) ) {
            trigger_error("Books must be specified when saving course.", E_USER_ERROR);
        }
        if ( !isset($this->_scheduled_time) ) {
            trigger_error("Scheduled time must be specified when saving course.", E_USER_ERROR);
        }
        if ( !isset($this->_classroom) ) {
            trigger_error("Classroom must be specified when saving course.", E_USER_ERROR);
        }
        if ( !isset($this->_consultation) ) {
            trigger_error("Consultation must be specified when saving course.", E_USER_ERROR);
        }
        if ( !isset($this->_teacher) && $this->_teacher->getID() > 0 ) {
            trigger_error("Teacher must be specified when saving course.", E_USER_ERROR);
        }

        return array(
            'course_name' => $this->_name,
            'course_subname' => $this->_subname,
            'description' => $this->_description,
            'assessment' => $this->_assessment,
            'books' => $this->_books,
            'scheduled_time' => $this->_scheduled_time,
            'classroom' => $this->_classroom,
            'consultation' => $this->_consultation,
            'teacher_id' => $this->_teacher->getID()
        );
    }


    /**
     * Removes the course from database
     *
     * The ID must be specified for this to work.
     *
     * @access public
     * @return boolean true on successful delete.
     *                 false, if the course with ID did not exist
     */
    function delete()
    {
        // first delete the course record itself
        if ( parent::delete() ) {

            $db = DBInstance::get();

            // remove all the associated students
            $db->query( "DELETE FROM Courses_Users WHERE course_id = ?", $this->_id );

            // remove all the marks and lessons of this course
            $db->query(
                "DELETE
                    Marks, Lessons
                FROM
                    Marks LEFT JOIN Lessons USING ( lesson_id )
                WHERE
                    course_id = ?
                ",
                $this->_id
            );

            // remove all the forum topics and posts
            $db->query(
                "DELETE
                    Topics, Posts
                FROM
                    Topics LEFT JOIN Posts USING ( topic_id )
                WHERE
                    course_id = ?
                ",
                $this->_id
            );

            // remove all course materials
            $r = $db->query("SELECT * FROM Materials WHERE course_id = ?", $this->_id );
            while ( $r->fetchInto( $row ) ) {
                $material = MaterialFactory::material( $row['material_type'], $row['material_id'] );
                $material->readFromDatabaseRecord( $row );
                $material->delete();
            }

            return true;
        }
        else {
            return false;
        }
    }


    /**
     * Returns true if student is member of this course
     *
     * @access public
     * @param User $student student object
     * @return bool true, when student is on course, false otherwise
     */
    function studentExists( $student )
    {
        // check, if IDs are available
        if ( isset($this->_id) && $student->getID()>0 ) {
            // check if there is a record that associates this user and course
            $db = DBInstance::get();
            $student_count = $db->getOne(
                "SELECT count(*) FROM Courses_Users WHERE course_id=? AND user_id=?",
                array( $this->_id, $student->getID() )
            );

            // If we get error here, we die.
            if ( PEAR::isError($student_count) ) {
                trigger_error( $student_count->getMessage(), E_USER_ERROR );
            }

            // return true, if exactly one user was found
            return ( $student_count == 1 );
        }
        else {
            // generate fatal error
            trigger_error( "No Course ID and/or student ID specified.", E_USER_ERROR );
        }
    }


    /**
     * Updates the statistics of student on a course
     *
     * Increments the count of times the user has loaded a course page.
     * Updates the last time he did.
     *
     * This method should be called when ever a student accesses a course.
     *
     * @access public
     * @param User $student student object
     */
    function accessByStudent( $student )
    {
        // check, if IDs are available
        if ( isset($this->_id) && $student->getID()>0 ) {
            $db = DBInstance::get();

            // increment the number of total_views by the student on this course
            $result = $db->query(
                "UPDATE Courses_Users SET total_views=total_views+1 WHERE course_id=? AND user_id=?",
                array( $this->_id, $student->getID() )
            );

            // If we get error here, we die.
            if ( PEAR::isError($result) ) {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }

            // if more than one row was updated, then something is wrong with database.
            // if no rows were updated, the user is no more part of the course.
            // Anyway, this is quite rare to happen - I guess we can just give a somewhat
            // friendly error message in here - that's better than just crashing afterwards.
            if ( $db->affectedRows() != 1 ) {
                trigger_error( "Unable to find connection between user and course.", E_USER_ERROR );
            }
        }
        else {
            // generate fatal error
            trigger_error( "No Course ID and/or student ID specified.", E_USER_ERROR );
        }
    }
}



?>
