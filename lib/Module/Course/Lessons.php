<?php

/**
 * Contains the Module_Course_Lessons class
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
require_once 'lib/VikoTable.php';
require_once 'lib/LessonList.php';
require_once 'lib/HTML.php';

/**
 * Manages the lessons of the course
 *
 * <ul>
 * <li>/#course_id/lessons - show list of lessons of the course</li>
 * <li>/#course_id/lessons/add - show form for adding a lesson to course</li>
 * <li>/#course_id/lessons/edit/#lesson_id - show form for editing lesson with ID #lesson_id</li>
 * <li>/#course_id/lessons/delete/#lesson_id - delete lesson with ID #lesson_id</li>
 * </ul>
 */
class Module_Course_Lessons extends Module_Course {

    /**
     * Selected lesson
     *
     * @access private
     * @var Lesson
     */
    var $_lesson = null;

    /**
     * Action to be performed with the lesson
     *
     * @access private
     * @var string
     */
    var $_action = null;


    /**
     * Constructs new Course Lessons Module
     */
    function Module_Course_Lessons( $course, $parameters )
    {
        $actions = array( "add", "edit", "delete" );
        if ( isset($parameters[0]) && in_array( $parameters[0], $actions ) ) {
            $this->_action = $parameters[0];

            if ( isset($parameters[1]) && (int)$parameters[1] > 0 ) {
                $this->_lesson = new Lesson( (int)$parameters[1] );
            }
            elseif ( $this->_action != "add" ) {
                trigger_error( "No lesson ID specified to edit/delete.", E_USER_ERROR );
            }
        }
        elseif ( isset($parameters[0]) ) {
            trigger_error( "Unknown action '$parameters[0]' specified.", E_USER_ERROR );
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
        return "lessons";
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
        return _("Lessons");
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
        return _("Lessons of course");
    }


    /**
     * Returns Lessons page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        if ( isset($this->_action) ) {
            // only the teacher can add/delete/edit lessons
            if ( !$this->_userCanEditThisCourse() ) {
                return $this->accessDeniedPage(
                    _("Only teacher of the course can add or remove lessons.")
                );
            }

            // if lesson is to be edited or deleted, it has to exist and belong to this course
            if ( isset( $this->_lesson ) ) {

                // does it exist?
                if ( !$this->_lesson->loadFromDatabaseByID() ) {
                    return $this->errorPage(
                        _("Selected lesson does not exist.")
                    );
                }

                // does it belong to this course?
                $lesson_course = $this->_lesson->getCourse();
                if ( $lesson_course->getID() != $this->_course->getID() ) {
                    return $this->errorPage(
                        _("Selected lesson does not belong to this course.")
                    );
                }

            }

            switch ( $this->_action ) {
                case "add":
                    return $this->_createEditLesson( null );
                    break;
                case "edit":
                    return $this->_createEditLesson( $this->_lesson );
                    break;
                case "delete":
                    return $this->_deleteLesson( $this->_lesson );
                    break;
                default:
                    trigger_error( "Unknown action '{$this->_action}' specified.", E_USER_ERROR );
            }
        }

        return $this->_createLessonListPage();
    }


    /**
     * Creates HTML fragment containing table of lessons
     *
     * @return string HTML
     */
    function _createLessonListPage()
    {
        $html_title = $this->title();

        $table = new VikoTable();

        // Create table headers
        $header_list = array(
            _("Date"),
            _("Lesson topics"),
            _("Hometask"),
            _("Term")
        );
        // if page is editable, add the "X" to the end of headers
        if ( $this->_userCanEditThisCourse() ) {
            $header_list[] = _("Edit");
            $header_list[] = HTML::abbr( "X", _("Remove lesson") );
        }
        else {
            $header_list[] = _("Mark");
        }
        $table->addHeader( $header_list );


        // get list of lessons
        $lesson_list = LessonList::getFromCourse( $this->_course );

        // Fill table body with list of lessons
        foreach ( $lesson_list as $lesson ) {
            $table->addRow( $this->_createLessonsTableRow( $lesson ) );
        }

        if ( $this->_userCanEditThisCourse() ) {
            // the last column is for deletion
            $table->setColumnToDelete( 5 );

            // create link to new lesson creation
            $add_lesson_html = HTML::newLink(
                $this->relativeURI( "add" ),
                _("Add lesson"),
                STANDALONE
            );

            $note = "";

        }
        else {
            $add_lesson_html = "";
            $note = HTML::p( _("Assessment marks are in bold face.") );
        }

        // If there are lessons, show them in table; or show message telling that there are no lessons
        if ( count($lesson_list) > 0 ) {
            return $html_title . $table->toHTML() . $add_lesson_html . $note;
        }
        else {
            return HTML::notice( _("There are no lessons on this course.") ) . $add_lesson_html;
        }
    }


    /**
     * Returns table row contents as an array
     *
     * @param Lesson $lesson
     * @return array table row
     */
    function _createLessonsTableRow( $lesson )
    {
        // Lesson date
        $date = $lesson->getDate();
        $date_short = $date->format( Configuration::getShortDate() );
        $date_long = $date->format( Configuration::getLongDateWithWeekday() );
        $lesson_date = HTML::p( HTML::abbr( $date_short, $date_long ) );

        // Lesson topic
        $lesson_topic = $lesson->getHTMLTopic();

        // Hometask
        $hometask = $lesson->getHTMLHometask();

        // Hometask date  (only show, when hometask is specified)
        if ( strlen($hometask) > 0 ) {
            $ht_date = $lesson->getHometaskDate();
            $ht_date_short = $ht_date->format( Configuration::getShortDate() );
            $ht_date_long = $ht_date->format( Configuration::getLongDateWithWeekday() );
            $hometask_date = HTML::p( HTML::abbr( $ht_date_short, $ht_date_long ) );
        }
        else {
            $hometask_date = "";
        }

        // Edit link
        $lesson_uri = $this->relativeURI( "edit", $lesson->getID() );
        $edit_link = HTML::p( HTML::a( $lesson_uri, _("Edit") ) );

        // Delete link
        $delete_uri = $this->relativeURI( "delete", $lesson->getID() );
        $delete_label = "X";
        $delete_title = _("Delete lesson");
        $delete_link = HTML::p( HTML::a( $delete_uri, $delete_label, $delete_title ) );

        // Mark
        if ( !$this->_userCanEditThisCourse() ) {
            $mark = new Mark();
            $mark->setStudent( $_SESSION['user'] );
            $mark->setLesson( $lesson );
            $mark_value = $mark->getHTMLMark();
            // do not display zero or empty marks
            if (trim($mark_value) > "" && trim($mark_value) != "0") {
                if ( $lesson->getMarkType() == 'PRELIM' ) {
                    $mark_value = HTML::strong( $mark_value );
                }
            }
            else {
                $mark_value="";
            }
            $mark_value = HTML::p( $mark_value );
        }

        // combine all this data into table row
        if ( $this->_userCanEditThisCourse() ) {
            $row = Array(
                $lesson_date,
                $lesson_topic,
                $hometask,
                $hometask_date,
                $edit_link,
                $delete_link
            );
        }
        else {
            $row = Array(
                $lesson_date,
                $lesson_topic,
                $hometask,
                $hometask_date,
                $mark_value
            );
        }

        return $row;
    }


    /**
     * Creates HTML fragment containing form to edit lesson
     *
     * @param Lesson $lesson  null, when adding new lesson
     * @return string HTML
     */
    function _createEditLesson( $lesson=null )
    {
        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        // create HTML form
        $form = $this->_createForm( $lesson );
        // check form for user input
        if ( $form->validate() ) {
            // if data is entered correctly, save it
            // and redirect to the lessons list page
            $this->_saveLesson( $form, $lesson );
            $this->redirectInsideCourseModule();
            return false;
        }

        $html = $this->title( $this->_editPageTitle($lesson) );

        // convert form to HTML
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );

        $html .= HTML::backLink(
            $this->relativeURI(),
            _("Return to the list of lessons"),
            STANDALONE
        );

        // return HTML containing page title and form
        return $html;
    }


    /**
     * Returnes appropriate title, depending weather the lesson is defined or not
     *
     * @access private
     * @param Lesson $lesson
     * @return string
     */
    function _editPageTitle( $lesson=null )
    {
        if ( isset($lesson) ) {
            return _("Change lesson");
        }
        else {
            return _("Add lesson");
        }
    }

    /**
     * Creates an HTML form for editing lesson info
     *
     * @access private
     * @param Lesson $lesson  null, when adding new lesson
     * @return Form new form object
     */
    function _createForm( $lesson=null )
    {
        $form = new HTML_QuickForm();

        // get the language code from locale (first two letters)
        $lang = substr( Configuration::getLocale(), 0, 2 );

        $form->addElement(
            'date',
            'lesson_date',
            _('Date') . ':',
            array( 'language'=>$lang, 'format'=>'dFY' ),
            array( 'id'=>'date' )
        );
        $form->addElement(
            'textarea',
            'lesson_topic',
            _('Lesson topics') . ':',
            array( 'rows'=>'12', 'cols'=>'45', 'id'=>'lesson_topic' )
        );
        $form->addElement(
            'textarea',
            'hometask',
            _('Hometask') . ':',
            array( 'rows'=>'12', 'cols'=>'45', 'id'=>'hometask' )
        );
        $form->addElement(
            'date',
            'hometask_date',
            _('Term') . ':',
            array( 'language'=>$lang, 'format'=>'dFY' ),
            array( 'id'=>'hometask_date' )
        );
        $form->addElement(
            'submit',
            'submit',
            $this->_editPageTitle($lesson)
        );

        $form->addRule(
            'lesson_topic',
            _("Lesson topic field must not be left blank."),
            'required'
        );

        if ( isset($lesson) ) {
            $date = $lesson->getDate();
            $ht_date = $lesson->getHometaskDate();

            $form->setDefaults( array(
                'lesson_date' => $date->getDate(),
                'lesson_topic' => $lesson->getTopic(),
                'hometask' => $lesson->getHometask(),
                'hometask_date' => $ht_date->getDate()
            ) );
        }
        else {
            $date = new Date();
            $form->setDefaults( array(
                'lesson_date' => $date->getDate(),
                'hometask_date' => $date->getDate()
            ) );
        }

        return $form;
    }


    /**
     * Saves the lesson info in submitted form
     *
     * If the lesson is specified, updates that lesson record,
     * otherwise creates new lesson.
     *
     * @access private
     * @param HTML_QuickForm $form    submitted HTML form
     * @param Lesson $lesson          null, when adding new lesson
     */
    function _saveLesson( $form, $lesson=null )
    {
        // if lesson is not specified, create new
        if ( !isset($lesson) ) {
            $lesson = new Lesson();
            $lesson->setMarkType('LESSON');
        }

            $date_arr = $form->exportValue('lesson_date');
            $date = new Date();
            $date->setDay( $date_arr['d'] );
            $date->setMonth( $date_arr['F'] );
            $date->setYear( $date_arr['Y'] );
        $lesson->setDate( $date );
        $lesson->setTopic( $form->exportValue('lesson_topic') );
        $lesson->setHometask( $form->exportValue('hometask') );
            $ht_date_arr = $form->exportValue('hometask_date');
            $ht_date = new Date();
            $ht_date->setDay( $ht_date_arr['d'] );
            $ht_date->setMonth( $ht_date_arr['F'] );
            $ht_date->setYear( $ht_date_arr['Y'] );
        $lesson->setHometaskDate( $ht_date );

        $lesson->setCourse( $this->_course );

        $lesson->save();
    }


    /**
     * Deletes the lesson and send user back to the lessons main page
     *
     * @access private
     * @param Lesson $lesson  the lesson to be deleted
     * @return mixed  false on success; error page on failure
     */
    function _deleteLesson( $lesson )
    {
        if ( $lesson->delete() ) {
            $this->redirectInsideCourseModule();
            return false;
        }
        else {
            return $this->errorPage(
                _("Deleting lesson failed.")
            );
        }
    }
}


?>
