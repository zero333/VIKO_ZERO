<?php

/**
 * Contains the Module_Course_Marks class
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
require_once 'lib/VikoTable.php';
require_once 'lib/LessonList.php';
require_once 'lib/StudentList.php';
require_once 'lib/Mark.php';

/**
 * Manages the Marks of the course
 *
 * <ul>
 * <li>/#course_id/marks - shows marks for first 10 lessons ( this is the same as /#course_id/marks/0 )</li>
 * <li>/#course_id/marks/#page_nr - shows marks page nr #page_nr, 10 marks on a page</li>
 * <li>/#course_id/marks/all - shows all marks in one column and calculared average mark in another</li>
 * <li>/#course_id/marks/edit/#lesson_id - show form for editing marks of lesson with ID #lesson_id</li>
 * </ul>
 */
class Module_Course_Marks extends Module_Course {

    var $_page_nr = 0;
    var $_page_size = 10;
    var $_show_all = false;
    var $_selected_lesson = null;

    /**
     * Constructs new Course Marks Module
     */
    function Module_Course_Marks( $course, $parameters )
    {
        if ( isset($parameters[0]) ) {
            if ( $parameters[0] == 'all' ) {
                $this->_page_nr = 0;
                $this->_show_all = true;
            }
            elseif ( $parameters[0] == 'edit' ) {
                $this->_selected_lesson = new Lesson();
                $this->_selected_lesson->setID( $parameters[1] );
            }
            else {
                $this->_page_nr = $parameters[0];
            }
        }
        else {
            $this->_page_nr = 0;
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
        return "marks";
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
        return _("Marks");
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
        return _("Course assessment");
    }


    /**
     * Returns false, because only teacher can view all the marks.
     *
     * The Marks module is only viewable by teachers, so this
     * time this method returns false.
     *
     * @return bool false
     */
    static function studentIsAllowedToView()
    {
        return false;
    }


    /**
     * Returns CourseMarks page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // only teacher of this course can access the marks page
        if ( !$this->_userCanEditThisCourse() ) {
            return $this->accessDeniedPage( _("You are not authorized to edit marks of this course.") );
        }

        if ( isset($this->_selected_lesson) ) {
            return $this->_editMarks();
        }
        else {
            return $this->_showMarks();
        }
    }


    /**
     * Returns page with form allowing to submit marks
     *
     * @access private
     * @return string HTML fragment
     */
    function _editMarks()
    {
        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        $html_title = $this->title( _("Edit marks") );

        // add subtitle
        $date = $this->_selected_lesson->getDate();
        $date_formatted = $date->format( Configuration::getLongDateWithWeekday() );
        $subtitle = _("Marks for lesson at %date%");
        $subtitle = str_replace( '%date%', $date_formatted, $subtitle );
        $subtitle = HTML::h3( $subtitle );

        $form = $this->_createForm();

        if ( $form->validate() ) {
            // save the form and go back to the previous page
            $this->_saveForm( $form );

            // determine the page number, where the user came from
            $lesson_nr = $this->_getSelectedLessonIndex();
            $page_nr = (int)( $lesson_nr / $this->_page_size );

            // send back to that page
            $this->redirectInsideCourseModule( $page_nr );
            return false;
        }

        // convert form to HTML
        $html_form = HTML_QuickForm_Renderer_VIKO::render( $form );

        $backlink = HTML::backLink(
            $this->relativeURI(),
            _("Return to marks page"),
            STANDALONE
        );

        return $html_title . $subtitle . $html_form . $backlink;
    }


    /**
     * Get the index of current lesson in the ordered list of course lessons
     *
     * @access private
     * @return int lesson index
     */
    function _getSelectedLessonIndex() {
        // loop through all course lessons
        $lesson_list = LessonList::getFromCourse( $this->_course );
        foreach ( $lesson_list as $index => $lesson ) {
            // look for matching ID-s
            if ( $lesson->getID() == $this->_selected_lesson->getID() ) {
                // return the index
                return $index;
            }
        }
    }


    /**
     * Returns form with fields for marks for each student of selected lesson
     *
     * @access private
     * @return HTML_QuickForm
     */
    function _createForm()
    {
        $form = new HTML_QuickForm();

        $all_students = StudentList::getFromCourse( $this->_course );

        foreach ( $all_students as $student ) {
            $form->addElement(
                'text',
                'mark-for-student-' . $student->getID(),
                $student->getFullName( LASTNAME_FIRSTNAME ),
                array( 'id' => 'mark-for-student-' . $student->getID() )
            );
        }

        $form->addElement(
            'checkbox',
            'is-preliminary',
            _("Preliminary marks"),
            '',
            array( 'id' => 'is-preliminary' )
        );

        $form->addElement(
            'submit',
            'submit',
            _('Save')
        );

        // load the form with existing values from database
        $defaults = array();
        foreach ( $all_students as $student ) {

            $mark = new Mark();
            $mark->setLesson( $this->_selected_lesson );
            $mark->setStudent( $student );

            $defaults[ 'mark-for-student-' . $student->getID() ] = $mark->getMark();
        }
        if ( $this->_selected_lesson->getMarktype() == 'PRELIM' ) {
            $defaults['is-preliminary'] = true;
        }
        $form->setDefaults( $defaults );

        return $form;
    }


    /**
     * Saves the marks in submitted form
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _saveForm( $form )
    {
        $all_students = StudentList::getFromCourse( $this->_course );

        // save all the marks
        foreach ( $all_students as $student ) {
            $mark = new Mark();
            $mark->setLesson( $this->_selected_lesson );
            $mark->setStudent( $student );
            $mark->setMark(
                $form->exportValue( 'mark-for-student-' . $student->getID() )
            );
            $mark->save();
        }

        // load all the properties of this lesson
        $this->_selected_lesson->getDate();
        // change the marktype and save the lesson
        if ( $form->exportValue('is-preliminary') ) {
            $this->_selected_lesson->setMarktype( 'PRELIM' );
        }
        else {
            $this->_selected_lesson->setMarktype( 'LESSON' );
        }
        $this->_selected_lesson->save();
    }


    /**
     * Returns page listing marks of the course
     *
     * @access private
     * @return string HTML fragment
     */
    function _showMarks()
    {
        $html_title = $this->title();

        // retrieve all the lessons
        $all_lessons = LessonList::getFromCourse( $this->_course );

        // retrieve all the students
        $all_students = StudentList::getFromCourse( $this->_course );

        // ensure, that at least one or more lessons and students exist
        // if not, return the corresponding error message
        if ( count($all_lessons) == 0 || count($all_students) == 0 ) {
            return $html_title . HTML::notice(
                _("To assign marks, you need to add lessons and students to this course.")
            );
        }

        // create list of pages
        $pages = $this->_calculatePaging( $all_lessons );

        // get lessons visible on the current page
        $visible_lessons = $this->_createVisibleLessonsArray( $all_lessons, $pages );

        // create table header, footer and body
        $table = new VikoTable();
        $this->_createHeader( $table, $visible_lessons );
        $this->_createFooter( $table, $visible_lessons );
        $this->_createBody( $table, $visible_lessons, $all_lessons, $all_students );

        // create the pager
        $pager = $this->_createPagerMenu( $pages );

        // additional informative paragraph
        $note = HTML::p( _("Assessment marks are in bold face.") );

        return $html_title . $table->toHTML() . $pager . $note;
    }


    /**
     * Creates array containing only the lessons of current page
     *
     * Creates subset of all lessons, containing only those lessons,
     * that are visible on the current page.
     *
     * @access private
     * @param array $all_lessons lessons array
     * @param array $pages paging array
     */
    function _createVisibleLessonsArray( $all_lessons, $pages )
    {
        // get the first and last lesson index visible on current page
        $first_lesson = $pages[$this->_page_nr]['start']-1;
        $last_lesson = $pages[$this->_page_nr]['end']-1;

        // select lessons between first and last lesson index
        // into array that is returned
        $visible_lessons = array();
        for ( $i = $first_lesson; $i <= $last_lesson; $i++ ) {
            $visible_lessons[] = $all_lessons[$i];
        }

        return $visible_lessons;
    }


    /**
     * Creates the header for the table
     *
     * @access private
     * @param Table $table where the header will belong to
     * @param array $visible_lessons array of lessons visible on current page
     */
    function _createHeader( &$table, $visible_lessons )
    {
        // first cell is the header of student names columns
        $header_list = array( _("Student name") );

        if ( $this->_show_all ) {
            // if all marks are shown,
            // only one column containing all marks needs a header
            $header_list[] = _("Marks");
            $header_list[] = _("Average mark");
        }
        else {
            // normally all other columns besides the first
            // contain lesson dates as their headers
            foreach ( $visible_lessons as $lesson ) {
                $date = $lesson->getDate();
                // all dates are displayd as abbrevations,
                // showing full date when hovered with mouse
                $header_list[] = HTML::abbr(
                    $date->format( Configuration::getShortDate() ),
                    $date->format( Configuration::getLongDateWithWeekday() )
                );
            }
        }

        $table->addHeader( $header_list );
    }


    /**
     * Creates the footer for the table
     *
     * @access private
     * @param Table $table where the footer will belong to
     * @param array $visible_lessons array of lessons visible on current page
     */
    function _createFooter( &$table, $visible_lessons )
    {
        // when all marks are shown, there's no point to create footer
        if ( !$this->_show_all ) {

            // the first cell contains heading
            $footer_list = array( _("Editing") );

            // other cells contain links
            // which make the marks of the corresponding lesson editable
            foreach ( $visible_lessons as $lesson ) {
                $footer_list[] = HTML::element(
                    "a",
                    _("Edit"),
                    array( 
                        "href" => $this->relativeURI( 'edit', $lesson->getID() ),
                        "style"=>"font-size:smaller"
                    )
                );
            }

            $table->addFooter( $footer_list );
        }
    }


    /**
     * Creates the body for the table
     *
     * The first column will contain the students and
     * the others will consist of corresponding grades for
     * each lesson in the $visible_lessons array
     *
     * @access private
     * @param Table $table where the body will belong to
     * @param array $visible_lessons array of lessons visible on current page
     * @param array $all_lessons array of all the lessons of this course
     * @param array $all_students array of all the students of this course
     */
    function _createBody( &$table, $visible_lessons, $all_lessons, $all_students )
    {
        // Fill table body with list of marks
        foreach ( $all_students as $student ) {

            // add student name to the beginning of the row
            $row = array(
                $student->getUserLink( LASTNAME_FIRSTNAME )
            );

            if ( $this->_show_all ) {
                // add one cell with all grades and another which shows the average
                $row = array_merge( $row, $this->_createMarksSummary( $all_lessons, $student ) );
            }
            else {
                // add the grades to the cells
                foreach ( $visible_lessons as $lesson ) {
                    $row[] = $this->_createFormattedMark( $lesson, $student );
                }
            }

            // combine everything into a row
            $table->addRow( $row );
        }

        if ( !$this->_show_all ) {
            // mark all cells with grades as numeric
            $indexes = array();
            for ( $i = 1; $i <= count($visible_lessons); $i++ ) {
                $indexes[] = $i;
            }
            $table->setColumnsToNumeric( $indexes );
        }
    }


    /**
     * Creates two-element array: first element has all grades and another the average grade
     *
     * @access private
     * @param array $all_lessons array of Lesson objects
     * @param User $student a student object
     * @return array
     */
    function _createMarksSummary( $all_lessons, $student )
    {
        // preparations for calculating average grade
        $sum_of_marks=0;
        $nr_of_marks=0;
        $all_marks_are_numeric=true;

        // combine all grades to array
        $mark_list = array();
        foreach ( $all_lessons as $lesson ) {
            // get the value of mark
            $mark = $this->_getMark( $lesson, $student );

            if ( is_numeric($mark) ) {
                // add up the numeric
                $sum_of_marks += $mark;
                $nr_of_marks++;
            }
            elseif ( $mark == "" ) {
                // skip the missing marks
            }
            else {
                // at least one mark is not numeric,
                // so it will be impossible to tell the average
                $all_marks_are_numeric = false;
            }

            // append mark to the list
            if ( $mark != "" ) {
                $mark_list[] = $this->_formatMark( $mark, $lesson );
            }
        }

        // separate marks with commas
        $marks_string = implode(", ", $mark_list);

        // if we can calculate the average mark, then append it.
        // otherwise just add an empty string.
        // the nr_of_marks must be greater than zero to avoid division by zero.
        if ( $all_marks_are_numeric && $nr_of_marks > 0 ) {
            // rounding with one place after the comma should be enough
            $average_mark = round( $sum_of_marks / $nr_of_marks, 1 );
            return array( $marks_string, HTML::strong( $average_mark ) );
        }
        else {
            return array( $marks_string, "" );
        }
    }


    /**
     * Returns array containing pages
     *
     * Each element in the returned array is itself an array,
     * consisting of two elements:
     * 'start' - marks the first lesson nr on page,
     * 'end' - marks the last lesson nr on page.
     *
     * @access private
     * @param array $lesson_list
     * @return array pages
     */
    function _calculatePaging( $lesson_list )
    {
        $lesson_count = count($lesson_list);
        $pages = array();
        $i=0;
        while ( $i < $lesson_count ) {
            if (  ( $i + $this->_page_size ) < $lesson_count  ) {
                $pages[] = array(
                    'start' => $i + 1,
                    'end' => $i + $this->_page_size
                );
            }
            else {
                $pages[] = array(
                    'start' => $i+1,
                    'end' => $lesson_count
                );
            }
            $i += $this->_page_size;
        }
        return $pages;
    }


    /**
     * Returns the pager menu in HTML
     *
     * Uses the $pages array, returned by the _calculatePaging method.
     * It marks the current page as selected ( using the value of
     * $this->_page_nr ).
     *
     * @access private
     * @param array $pages
     * @return string the menu itself
     */
    function _createPagerMenu( $pages )
    {
        // the pager will be structured as a paragraph of text,
        // going like this: 1-10, 11-20, 21-27, show all.

        // add information about all the pages
        $menu = "";
        foreach ( $pages as $page_nr => $p ) {
            $label = $p['start'] . '-' . $p['end'];
            if ( $page_nr == $this->_page_nr  &&  !$this->_show_all ) {
                $menu .= HTML::strong( $label );
            }
            else {
                $menu .= HTML::a(
                    $this->relativeURI( $page_nr ),
                    $label
                );
            }
            $menu .= ", ";
        }

        if ( $this->_show_all ) {
            $menu .= HTML::strong( _("all lessons") );
        }
        else {
            $menu .= HTML::a(
                $this->relativeURI( 'all' ),
                _("all lessons")
            );
        }

        return HTML::p( _("Lessons") . " " . $menu . "." );
    }


    /**
     * Returnes a HTML formatted string containing mark
     *
     * @param Lesson $lesson
     * @param User $student
     * @return string HTML formatted mark/grade
     */
    function _createFormattedMark( $lesson, $student )
    {
        $mark = $this->_getMark( $lesson, $student );

        return $this->_formatMark( $mark, $lesson );
    }


    /**
     * Formats the mark as strong in HTML if lesson marktype is preliminary
     *
     * @param mixed $mark
     * @param Lesson $lesson
     * @return string HTML formatted mark/grade
     */
    function _formatMark( $mark, $lesson )
    {
        if ( $mark != ''  && $lesson->getMarktype() == 'PRELIM' ) {
            $mark = HTML::strong( $mark );
        }

        return $mark;
    }


    /**
     * Returns the grade for the student in specified lesson
     *
     * @access private
     * @param Lesson $lesson
     * @param User $student
     * @return mixed mark
     */
    function _getMark( $lesson, $student )
    {
        $mark = new Mark();
        $mark->setLesson( $lesson );
        $mark->setStudent( $student );

        $grade = $mark->getHTMLMark();
        return ( $grade ) ? $grade : '' ;
    }
}


?>
