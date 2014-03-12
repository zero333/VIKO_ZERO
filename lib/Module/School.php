<?php

/**
 * Contains the Module_School class
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
 * Adding, deleting and editing of schools
 *
 * <ul>
 * <li>/school/add - add new school</li>
 * <li>/school/edit/#school_id - change the name of school with ID #school_id</li>
 * <li>/school/delete/#school_id - delete the school with ID #school_id</li>
 * <li>/school/delete/#school_id/yes-please - confirm the deleting of school</li>
 * </ul>
 */
class Module_School extends Module {

    /**
     * The school to be modified
     */
    var $_school = null;

    /**
     * Which action should be performed?
     */
    var $_action = "edit";

    /**
     * Has the user confirmed the deleting of school?
     */
    var $_yes_please_delete = false;

    /**
     * Constructs new FormAdd Module
     *
     * @param array $parameters  parameters to the module
     */
    function Module_School( $parameters )
    {
        if ( isset($parameters[0]) ) {
            if ( $parameters[0] == "add" ) {
                $this->_action = "add";
            }
            elseif ( $parameters[0] == "edit" || $parameters[0] == "delete" ) {
                $this->_action = $parameters[0];
                if ( isset($parameters[1]) && (int)$parameters[1] > 0 ) {
                    $this->_school = new School( (int)$parameters[1] );
                }
                else {
                    // fatal error: no school_id specified
                    trigger_error("No school ID specified when editing/deleting school.", E_USER_ERROR);
                }

                if ( isset($parameters[2]) && $parameters[2] == "yes-please" ) {
                    $this->_yes_please_delete = true;
                }
            }
            else {
                // fatal error: unknown action specified
                trigger_error("Unknown action specified.", E_USER_ERROR);
            }
        }
        else {
            // fatal error: no action specified
            trigger_error("No action specified.", E_USER_ERROR);
        }
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
        return "school";
    }


    /**
     * Returns page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // only admins are allowed to tinker with schools
        if ( !$_SESSION['user']->hasAccessToGroup("ADMIN") ) {
            return $this->accessDeniedPage( _("You are not authorized to add, edit or delete schools.") );
        }

        // when editing or deleting, the school must exist
        if ( $this->_action == "edit" || $this->_action == "delete" ) {
            if ( !$this->_school->loadFromDatabaseByID() ) {
                return $this->errorPage( _("The school you are trying to access does not exist.") );
            }
        }

        switch ( $this->_action ) {
            case "add": return $this->_addSchool();
            break;
            case "edit": return $this->_editSchool();
            break;
            case "delete": return $this->_deleteSchool();
            break;
        }
    }


    /**
     * Page for adding new school
     *
     * @access private
     * @return string HTML fragment
     */
    function _addSchool()
    {
        $html_form = $this->_createForm();

        $this->_school = new School();

        // translate the title
        $html = $this->title( _("Add school") );

        if ( $html_form->validate() ) {
            $this->_saveSchool(
                $html_form->exportValue('school-name')
            );

            $html.= HTML::p( HTML::notice( _("School successfully added.") ) );
        }
        else {
            // convert form to HTML
            $html .= HTML_QuickForm_Renderer_VIKO::render( $html_form );
        }

        // backlink
        $html .= HTML::backLink( $this->URI("statistics"), _("Return to the list of all schools"), STANDALONE ); 

        return $html;
    }


    /**
     * Page for changing school name
     *
     * @access private
     * @return string HTML fragment
     */
    function _editSchool()
    {
        $html_form = $this->_createForm();

        // translate the title
        $html = $this->title( _("Change school name") );

        if ( $html_form->validate() ) {
            $this->_saveSchool(
                $html_form->exportValue('school-name')
            );

            $html.= HTML::p( HTML::notice( _("School name successfully changed.") ) );
        }
        else {
            // convert form to HTML
            $html .= HTML_QuickForm_Renderer_VIKO::render( $html_form );
        }

        // backlink
       $html .= HTML::backLink( $this->URI("statistics"), _("Return to the list of all schools"), STANDALONE ); 

        return $html;
    }


    /**
     * Saves the school
     *
     * @access private
     * @param string $school_name school name to use
     */
    function _saveSchool( $school_name )
    {
        $this->_school->setName( $school_name );
        $this->_school->save();
    }


    /**
     * Deletes the school if user confirms the delete
     *
     * @access private
     */
    function _deleteSchool()
    {
        $html = $this->title( _("Delete school") );

        // user has to say "please" before he is allowed to delete the school
        if ( $this->_yes_please_delete ) {
            $this->_school->delete();

            $html.= HTML::p( HTML::notice( _("School successfully deleted.") ) );
        }
        else {
            $question = sprintf( _("Are you sure you want to delete school %s?"), $this->_school->getHTMLName() );
            $html.= HTML::p( $question );
            $id = $this->_school->getID();
            $html.= HTML::p( HTML::a( $this->URI("school", "delete", $id, "yes-please"), _("Yes, please delete this school.") ) ); 
        }

        // backlink
        $html .= HTML::backLink( $this->URI("statistics"), _("Return to the list of all schools"), STANDALONE ); 

        return $html;
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

        $form->addElement(
            'text',
            'school-name',
            _('School name') . ':',
            array( 'class'=>'long-text', 'id'=>'school-name' )
        );

        if ( $this->_action == "edit" ) {
            $form->addElement('submit', 'submit', _('Save') );
        }
        else {
            $form->addElement('submit', 'submit', _('Add school') );
        }

        $form->addRule(
            'school-name',
            _("This field is required"),
            'required'
        );
        $form->addRule(
            'school-name',
            _("School name may not only consist of spaces."),
            'regex',
            '/ [^\s] /x'
        );
        $form->registerRule('uniqueSchoolName', 'callback', '_checkUniquenessOfSchoolName', 'Module_School');
        $form->addRule(
            'school-name',
            _("This name is already in use - please choose another."),
            'uniqueSchoolName'
        );

        if ( $this->_action == "edit" ) {
            $form->setDefaults( array( "school-name" => $this->_school->getName() ) );
        }

        return $form;
    }


    /**
     * Checks if school name is unique
     *
     * If the school name already exists, returns false.
     * Else returns true.
     *
     * @access private
     * @param string  $school_name  school name to validate
     * @return bool
     */
    function _checkUniquenessOfSchoolName( $school_name )
    {
        $db = DBInstance::get();
        $school_name_exists = (bool) $db->getOne(
            "SELECT COUNT(school_id) FROM Schools WHERE school_name=?",
            array( $school_name )
        );

        return !$school_name_exists;
    }


}


?>
