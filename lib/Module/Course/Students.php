<?php

/**
 * Contains the Module_Course_Students class
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

require_once 'lib/Module/Course.php';
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';
require_once 'lib/HTML.php';
require_once 'lib/StudentList.php';
require_once 'lib/VikoTable.php';
require_once 'lib/FormList.php';

/**
 * Manages the students of the course
 *
 * <ul>
 * <li>/#course_id/students - show list of students on course</li>
 * <li>/#course_id/students/add - show form for adding students to course</li>
 * <li>/#course_id/students/remove/#student_id - remove student with ID #student_id from course</li>
 * </ul>
 */
class Module_Course_Students extends Module_Course {

    /**
     * Selected student
     *
     * @access private
     * @var User
     */
    var $_selected_student = null;

    /**
     * Action to be performed with the student
     *
     * @access private
     * @var string
     */
    var $_student_action = null;


    /**
     * Constructs new Course Students Module
     */
    function Module_Course_Students( $course, $parameters )
    {
        if ( isset($parameters[0]) && $parameters[0]=='add' ) {
            $this->_student_action = 'add';
        }
        elseif ( isset($parameters[0]) && $parameters[0]=='remove' ) {
            $this->_student_action = 'remove';
            if ( isset($parameters[1]) && (int)$parameters[1] > 0 ) {
                $this->_selected_student = new User();
                $this->_selected_student->setID( (int)($parameters[1]) );
            }
            else {
                trigger_error( "Student ID not specified when deleting.", E_USER_ERROR );
            }
        }
        elseif ( isset($parameters[0]) && $parameters[0] > "" ) {
            trigger_error( "Unknown action '$parameter[0]' specified.", E_USER_ERROR );
        }

        parent::Module_Course( $course, $parameters );
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
     * Returns the short name of the module, this is used in navigation menu.
     *
     * @access public
     * @static
     * @return string short name
     */
    function getName()
    {
        return _("Students");
    }


    /**
     * Returns short description of module
     *
     * @access public
     * @static
     * @return string module description
     */
    function getDescription()
    {
        return _("Students of course");
    }


    /**
     * Returns Students page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        if ( isset( $this->_student_action ) ) {
            // only teacher can add/remove course students
            if ( !$this->_userCanEditThisCourse() ) {
                return $this->accessDeniedPage(
                    _("Only teacher of the course can add or remove students.")
                );
            }

            switch ( $this->_student_action ) {
                case "add":
                    return $this->_createAddStudentsPage();
                    break;
                case "remove":
                    return $this->_removeStudent( $this->_selected_student );
                    break;
                default:
                    trigger_error( "Unknown action {$this->_student_action}", E_USER_ERROR );
            }
        }
        else {
            return $this->_createStudentListPage();
        }
    }


    /**
     * Creates page for adding new students to course
     *
     * @return string HTML
     */
    function _createAddStudentsPage()
    {
        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        $class_form = $this->_createClassSelectForm();

        if ( isset( $_POST['submit-form'] ) ) {
            // the select-box was submitted

            if ( $class_form->validate() ) {
                // check, that selected form exists and belongs to this school
                if ( ($error_page = $this->_validFormID( $_POST['form'] )) !== true ) {
                    return $error_page;
                }

                // in addition to select-box,
                // the list of students from that form is displayd
                $students_form = $this->_createStudentsSelectForm( $_POST['form'] );
                $html_students_form = HTML_QuickForm_Renderer_VIKO::render( $students_form );
            }
        }
        elseif ( isset( $_POST['submit-studs'] ) ) {
            // the student-list was submitted

            // get the form id from hidden field
            if ( isset ( $_POST['form'] ) ) {
                // check, that selected form exists and belongs to this school
                if ( ($error_page = $this->_validFormID( $_POST['form'] )) !== true ) {
                    return $error_page;
                }

                $students_form = $this->_createStudentsSelectForm( $_POST['form'] );

                if ( $students_form->validate() ) {
                    return $this->_addStudentsToCourse( $students_form );
                }
            }
            trigger_error( "Error in form parameters", E_USER_ERROR );
        }
        else {
            // nothing was submitted

            $html_students_form = "";
        }

        $html_class_form = HTML_QuickForm_Renderer_VIKO::render( $class_form );

        $html_title = $this->title( _("Add students") );

        $list = HTML::backLink( $this->relativeURI(), _("Return to the list of students"), STANDALONE );

        // return HTML containing page title and form
        return $html_title . $html_class_form . $html_students_form . $list;
    }


    /**
     * Returns true, if form_id is valid
     *
     * On a bad ID returns error page.
     *
     * @access private
     * @param mixed $form_id  the form ID submitted by user
     * @return mixed  true or string with error message
     */
    function _validFormID( $form_id )
    {
        if ( $form_id == 'teacher' || $form_id == 'schooladmin' ) {
            return true;
        }

        $form = new Form( $form_id );

        // check existance
        if ( !$form->loadFromDatabaseByID() ) {
            return $this->errorPage(
                _("The selected form does not exist.")
            );
        }

        // check, that form belongs to active school
        $form_school = $form->getSchool();
        $user_school = $_SESSION['user']->getSchool();
        if ( $form_school->getID() != $user_school->getID() ) {
            return $this->accessDeniedPage(
                _("The selected form does not belong to this school.")
            );
        }

        return true;
    }


    /**
     * Creates an HTML form for selecting classes
     *
     * @access private
     * @return Form new form object
     */
    function _createClassSelectForm()
    {
        $form = new HTML_QuickForm();

        $school = $this->_course->getSchool();
        $form->addElement(
            'select',
            'form',
            _('Form') . ':',
            FormList::getFormsSelectBox( $school, false, true ),
            array( 'id'=>'form' )
        );

        $form->addElement(
            'submit',
            'submit-form',
            _('Show students')
        );

        return $form;
    }


    /**
     * Creates an HTML form for selecting classes
     *
     * @access private
     * @param mixed form id
     * @return HTML_QuickForm new HTML form
     */
    function _createStudentsSelectForm( $form_id )
    {
        $html_form = new HTML_QuickForm();

        $student_list = $this->_getUserListByFormId( $form_id );

        foreach ( $student_list as $index => $student ) {
            $html_form->addElement(
                'checkbox',
                "student-$index",
                null,
                $student->getFullName( LASTNAME_FIRSTNAME ),
                array( 'id' => "student-$index" )
            );
        }

        $html_form->addElement(
            'hidden',
            'form',
            $form_id
        );

        $html_form->addElement(
            'submit',
            'submit-studs',
            _('Add students to course')
        );

        $html_form->addElement(
            'button',
            'select-all',
            _('Select all students'),
            array("onclick" => "return checkAllCheckboxes();" )
        );

        return $html_form;
    }


    /**
     * Returns list of users
     */
    function _getUserListByFormId( $form_id )
    {
        if ( $form_id == 'teacher' || $form_id == 'schooladmin' ) {
            return $this->_getUsersFromGroup( $form_id );
        }
        else {
            $form = new Form();
            $form->setID( $form_id );
            return StudentList::getFromForm( $form );
        }
    }


    /**
     * Returns list of all users in specified group in the school of user
     */
    function _getUsersFromGroup( $group )
    {
        $school = $_SESSION['user']->getSchool();
        $db = DBInstance::get();
        $result = $db->query(
            "SELECT * FROM Users WHERE user_group=? AND school_id=?",
            array( $group, $school->getID() )
        );

        $list = array();
        while( $result->fetchInto( $row ) ) {
            $user = new User();
            $user->setID( $row['user_id'] );
            $user->setUsername( $row['username'] );
            $user->setFirstName( $row['firstname'] );
            $user->setLastName( $row['lastname'] );
            $list[] = $user;
        }
        return $list;
    }


    /**
     * Associates all the selected students in form with the course
     *
     * @access private
     * @param HTML_QuickForm $students_form
     * @return string HTML
     */
    function _addStudentsToCourse( $students_form )
    {
        $student_list = $this->_getUserListByFormId(
            $students_form->exportValue("form")
        );

        foreach ( $student_list as $index => $student ) {
            if ( $students_form->exportValue( "student-$index" ) ) {
                // if student was selected, try to add him to course
                $this->_associateStudentWithCourse( $student );
            }
        }

        // direct back to students page
        $this->redirectInsideCourseModule();

        return false;
    }


    /**
     * Associates one student with the course
     *
     * @access private
     * @param User $student
     * @return string HTML
     */
    function _associateStudentWithCourse( $student )
    {
        // give SQL command to make connection between student and course
        // if the connection already exists, it wont be duplicated,
        // because the database has a unique constraint set for
        // student<=>course combinations

        $db = DBInstance::get();
        $db->query(
            "INSERT INTO Courses_Users (course_id, user_id) VALUES (?,?)",
            array( $this->_course->getID(), $student->getID() )
        );
    }


    /**
     * Removes the selected student from course
     *
     * @access private
     * @param User $student   the student to remove from course
     * @return string HTML
     */
    function _removeStudent( $student )
    {
        // first of all, the student must exist
        if ( !$student->loadFromDatabaseByID() ) {
            return $this->errorPage(
                _("Selected student does not exist.")
            );
        }

        // then it has to belong to this course
        if ( !$this->_course->studentExists( $student ) ) {
            return $this->errorPage(
                _("Selected student does not belong to this course.")
            );
        }


        // perform the removal of student from course
        $db = DBInstance::get();
        $db->query(
            "DELETE FROM Courses_Users WHERE course_id=? AND user_id=?",
            array( $this->_course->getID(), $this->_selected_student->getID() )
        );

        // remove all the grades of the student too
        $lessons = LessonList::getFromCourse( $this->_course );
        foreach ( $lessons as $lesson ) {
            $db->query(
                "DELETE FROM Marks WHERE lesson_id=? AND user_id=?",
                array( $lesson->getID(), $this->_selected_student->getID() )
            );
        }

        // direct back to students page
        $this->redirectInsideCourseModule();

        return false;
    }


    /**
     * Creates HTML fragment containing table of students
     *
     * @return string HTML
     */
    function _createStudentListPage()
    {
        $html = $this->title();

        $html .= $this->_createStudentsTable();

        // only teacher of the course can add new students
        if ( $this->_userCanEditThisCourse() ) {
            $html .= HTML::newLink(
                $this->relativeURI( 'add' ),
                _("Add students"),
                STANDALONE
            );
        }

        return $html;
    }


    /**
     * Creates HTML table of students
     *
     * @return string HTML table or message, thet there are no students
     */
    function _createStudentsTable()
    {
        // create table containing students
        $table = new VikoTable();

        // if page is editable, add some extra columns
        if ( $this->_userCanEditThisCourse() ) {
            $table->addHeader(
                _("Student name"),
                _("Form name"),
                _("Last login"),
                _("Nr of loads"),
                _("Nr of messages"),
                _("Nr of materials"),
                HTML::abbr( "X", _("Remove student") )
            );
        }
        else {
            $table->addHeader(
                _("Student name"),
                _("Form name"),
                _("Last login")
            );
        }

        // get list of students
        $result = $this->_queryCourseStudents();

        // when the student list is empty, return message.
        if ( $result->numRows() == 0 ) {
            return HTML::notice( _("There are no students on this course.") );
        }

        // Fill table body with list of students
        while ( $result->fetchInto( $student_record ) ) {
            $table->addRow( $this->_createStudentsTableRow( $student_record ) );
        }

        // some extra formatting for extra columns :)
        if ( $this->_userCanEditThisCourse() ) {
            // Loads, posts and links columns are numberic
            $table->setColumnsToNumeric( array(2,3,4,5) );
            // the last column is for deletion
            $table->setColumnToDelete( 6 );
        }
        else {
            $table->setColumnsToNumeric( array(2) );
        }

        return $table->toHTML();
    }

    /**
     * Retrieves information about students of the course
     *
     * @return DB_result
     */
    function _queryCourseStudents()
    {
        $order_by = array("lastname","firstname");
        $order_by_sql = DBUtils::createOrderBy( $order_by );

        // query all associated Students
        $db = DBInstance::get();
        $result = $db->query(
            "
            SELECT
                Users.user_id AS user_id,
                username,
                firstname,
                lastname,
                email,
                user_group,
                Users.school_id AS school_id,

                Users.form_id AS form_id,
                COALESCE(form_name, '') AS form_name,

                last_view,
                total_views,
                COALESCE(Posts.total,0) AS total_posts,
                COALESCE(Materials.total, 0) AS total_materials
            FROM
                Users
                JOIN
                Courses_Users USING (user_id)
                LEFT JOIN
                Forms ON (Users.form_id=Forms.form_id)
                LEFT JOIN
                (
                    SELECT
                        user_id,
                        count(*) AS total
                    FROM
                        Materials
		    WHERE
		        course_id=?
                    GROUP BY
                        user_id
                ) AS Materials ON (Materials.user_id=Users.user_id)
                LEFT JOIN
                (
                    SELECT
                        Posts.user_id AS user_id,
                        count(*) AS total
                    FROM
                        Posts
			JOIN
			Topics USING (topic_id)
                    WHERE
                        Topics.course_id=?
                    GROUP BY
                        user_id
                ) AS Posts ON (Posts.user_id=Users.user_id)
            WHERE
                Courses_Users.course_id = ?
            $order_by_sql
            ",
            array( $this->_course->getID(), $this->_course->getID(), $this->_course->getID() )
        );

        // If we get error here, we die.
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        return $result;
    }


    /**
     * Returns table row contents as an array
     *
     * @param array $student_record from _queryCourseStudents
     * @return array table row
     */
    function _createStudentsTableRow( $student_record )
    {
        // create student object
        $student = new User();
        $student->setID( $student_record['user_id'] );
        $student->setUsername( $student_record['username'] );
        $student->setFirstName( $student_record['firstname'] );
        $student->setLastName( $student_record['lastname'] );
        $student->setEmail( $student_record['email'] );
        $student->setGroup( $student_record['user_group'] );
        $school = new School( $student_record['school_id'] );
        $student->setSchool( $school );

        // Link to student info or e-mail
        $student_link = $student->getUserLink( LASTNAME_FIRSTNAME );

        // Form name
        $form_name = htmlspecialchars( $student_record['form_name'] );

        // Link for deleting student
        $student_remove_uri = $this->relativeURI(
            'remove',
            $student->getID()
        );
        $student_remove_title = sprintf( _("Remove %s"), $student->getFullName() );
        $student_remove_link = HTML::a(
            $student_remove_uri,
            "X",
            $student_remove_title
        );

        // if the user hasn't accessed the course at least once,
        // then the last access date is incorrect.
        // Only show it, when course is accessed at least once.
        if ( $student_record['total_views'] > 0 ) {
            // Format the time when user previously viewed this course
            $date = new Date($student_record['last_view']);
            $last_view = $date->format(
                Configuration::getLongTime()
            );
        }
        else {
            $last_view = "";
        }

        // combine all this data into table row
        $row = Array(
            $student_link,
            $form_name,
            $last_view
        );
        if ( $this->_userCanEditThisCourse() ) {
            $row[] = $student_record['total_views'];
            $row[] = $student_record['total_posts'];
            $row[] = $student_record['total_materials'];
            $row[] = $student_remove_link;
        }

        return $row;
    }

}





?>
