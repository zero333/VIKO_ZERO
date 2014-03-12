<?php

/**
 * Contains the Module_Form class
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
 * Manages the form adding and editing in VIKO
 *
 * <ul>
 * <li>/form/add - add new form</li>
 * <li>/form/edit/#form_id - change the name of form
 * with ID #form_id</li>
 * </ul>
 */
class Module_Form extends Module {

    /**
     * The form to be modified
     */
    var $_form = null;

    /**
     * Which action should be performed?
     */
    var $_action = "edit";

    /**
     * Constructs new FormAdd Module
     *
     * @param array $parameters  parameters to the module
     */
    function Module_Form( $parameters )
    {
        if ( isset($parameters[0]) ) {
            if ( $parameters[0] == "add" ) {
                $this->_action = "add";
            }
            elseif ( $parameters[0] == "edit" ) {
                $this->_action = "edit";
                if ( isset($parameters[1]) && (int)$parameters[1] > 0 ) {
                    $this->_form = new Form( (int)$parameters[1] );
                }
                else {
                    // fatal error: no form_id specified
                    trigger_error("No form ID specified when editing form.", E_USER_ERROR);
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
        return "form";
    }


    /**
     * Returns page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // only Schooladmins are allowed to add/edit forms
        if ( !$_SESSION['user']->hasAccessToGroup("SCHOOLADMIN") ) {
            return $this->accessDeniedPage( _("You are not authorized to add or edit forms.") );
        }

        // when editing form, the form has to exist
        // so we attempt loading it and if we fail, return an error message
        if ( $this->_action == "edit" ) {
            if ( !$this->_form->loadFromDatabaseByID() ) {
                return $this->errorPage( _("The form you are trying to edit does not exist.") );
            }
        }

        switch ( $this->_action ) {
            case "add": return $this->_addForm();
            break;
            case "edit": return $this->_editForm();
            break;
        }
    }


    /**
     * Page for adding new form
     *
     * @access private
     * @return string HTML fragment
     */
    function _addForm()
    {
        $html_form = $this->_createForm();

        $this->_form = new Form();
        $this->_form->setSchool( $_SESSION['user']->getSchool() );

        if ( $html_form->validate() ) {
            $this->_saveForm(
                $html_form->exportValue('form-name')
            );

            return false;
        }
        else {
            // translate the title
            $html = $this->title( _("Add new form") );

            // convert form to HTML
            $html .= HTML_QuickForm_Renderer_VIKO::render( $html_form );

            // backlink
            $html .= HTML::backLink( $this->URI("forms"), _("Return to the list of forms"), STANDALONE );

            return $html;
        }
    }


    /**
     * Page for changing form name
     *
     * @access private
     * @return string HTML fragment
     */
    function _editForm()
    {
        $html_form = $this->_createForm();

        if ( $html_form->validate() ) {
            $this->_saveForm(
                $html_form->exportValue('form-name')
            );

            return false;
        }
        else {
            // translate the title
            $html = $this->title( _("Change form name") );

            // convert form to HTML
            $html .= HTML_QuickForm_Renderer_VIKO::render( $html_form );

            // backlink
            $html .= HTML::backLink( $this->URI("forms"), _("Return to the list of forms"), STANDALONE );

            return $html;
        }
    }


    /**
     * Saves the form and sends user to forms page
     *
     * @access private
     * @param string $form_name form name to use
     */
    function _saveForm( $form_name )
    {
        $this->_form->setName( $form_name );
        $this->_form->save();

        $this->redirect("forms" );
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
            'form-name',
            _('Form name') . ':',
            array( "id" => "form-name" )
        );

        if ( $this->_action == "edit" ) {
            $form->addElement('submit', 'submit', _('Save') );
        }
        else {
            $form->addElement('submit', 'submit', _('Add form') );
        }

        $form->addRule(
            'form-name',
            _("This field is required"),
            'required'
        );
        $form->addRule(
            'form-name',
            _("Form name may not only consist of spaces."),
            'regex',
            '/ [^\s] /x'
        );
        $form->registerRule('uniqueFormName', 'callback', '_checkUniquenessOfFormName', 'Module_Form');
        $form->addRule(
            'form-name',
            _("This name is already in use - please choose another."),
            'uniqueFormName'
        );

        if ( $this->_action == "edit" ) {
            $form->setDefaults( array( "form-name" => $this->_form->getName() ) );
        }

        return $form;
    }


    /**
     * Checks if form name is unique
     *
     * If the form name already exists, returns false.
     * Else returns true.
     *
     * @access private
     * @param string  $form_name  form name to validate
     * @return bool
     */
    function _checkUniquenessOfFormName( $form_name )
    {
        $db = DBInstance::get();
        $school = $_SESSION['user']->getSchool();
        $form_name_exists = (bool) $db->getOne(
            "SELECT COUNT(form_id) FROM Forms WHERE form_name=? AND school_id=?",
            array( $form_name, $school->getID() )
        );

        return !$form_name_exists;
    }


}


?>