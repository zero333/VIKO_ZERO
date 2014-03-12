<?php

/**
 * Contains the Module_CourseAdd class
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

require_once 'lib/Module.php';
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';

/**
 * Shows form for adding new course
 *
 * Only teachers are allowed to add new courses.
 */
class Module_CourseAdd extends Module {


    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "course-add";
    }


    /**
     * Returns CourseAdd page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // only teachers can add new courses
        if ( !$_SESSION['user']->hasAccessToGroup("TEACHER") ) {
            return $this->accessDeniedPage( _("You are not authorized to add courses.") );
        }

        $form = $this->_createForm();

        if ( $form->validate() ) {
            $this->_add_course(
                $form->exportValue('course-name')
            );

            return false;
        }
        else {
            // translate the title
            $html = $this->title( _("Add new course") );

            // convert form to HTML
            $html .= HTML_QuickForm_Renderer_VIKO::render( $form );

            // backlink
            $html.= HTML::backLink( $this->URI("my-viko"), _("Return to the main page"), STANDALONE );

            return $html;
        }
    }


    /**
     * Adds new course and sends user to course-info page
     *
     * @access private
     * @param string $course_name course name to use
     */
    function _add_course( $course_name )
    {
        $course = new Course();
        $course->setName( $course_name );
        $course->setAssessment( '' );
        $course->setBooks( '' );
        $course->setClassRoom( '' );
        $course->setConsultation( '' );
        $course->setDescription( '' );
        $course->setScheduledTime( '' );
        $course->setSubName( '' );
        $course->setTeacher( $_SESSION['user'] );
        $course->save();

        $this->redirect( $course->getID() );
    }


    /**
     * Creates an HTML form for adding the course
     *
     * @access private
     * @return Form new form object
     */
    function _createForm()
    {
        $form = new HTML_QuickForm();

        $form->addElement('text', 'course-name', _('Course name') . ':' );
        $form->addElement('submit', 'submit', _('Add course') );

        $form->addRule(
            'course-name',
            _("This field is required"),
            'required'
        );
        $form->addRule(
            'course-name',
            _("Course name may not only consist of spaces."),
            'regex',
            '/ [^\s] /x'
        );

        return $form;
    }


}


?>