<?php

/**
 * Contains the Module_ChangeUserinfo class
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
require_once 'lib/FormList.php';
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';

/**
 * Manages the user password changing in VIKO
 *
 */
class Module_ChangeUserinfo extends Module {

    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "change-userinfo";
    }


    /**
     * Returns ChangeUserinfo page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        $html = $this->title( _("Change userinfo") );

        $form = $this->_createForm();

        if ( $form->validate() ) {
            $this->_change_email( $form->exportValue('email') );

            // admins can't belong to form
            if ( $_SESSION['user']->getGroup() != 'ADMIN' ) {
                $this->_change_form( $form->exportValue('form') );
            }

            $html.= HTML::p( HTML::notice( _("Userinfo updated successfully.") ) );
        }

        // convert form to HTML
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );

        // backlink
        $html.= HTML::backLink( $this->URI("my-viko"), _("Return to the main page"), STANDALONE );

        return $html;
    }


    /**
     * Performs the changing of users e-mail address
     *
     * @access private
     * @param string $email the new e-mail address to use
     */
    function _change_email( $email )
    {
        $_SESSION['user']->loadFromDatabaseByID();

        $_SESSION['user']->setEmail( $email );
        $_SESSION['user']->save();
    }


    /**
     * Performs the changing of users form
     *
     * @access private
     * @param string $form_id ID of the form record, zero if missing
     */
    function _change_form( $form_id )
    {
        $_SESSION['user']->loadFromDatabaseByID();

        if ( $form_id > 0 ) {
            $form = new Form();
            $form->setID( $form_id );
            $_SESSION['user']->setForm( $form );
        }
        else {
            $_SESSION['user']->setForm( null );
        }
        $_SESSION['user']->save();
    }


    /**
     * Creates an HTML form for changing the user info
     *
     * @access private
     * @return HTML_QuickForm new form object
     */
    function _createForm()
    {
        $form = new HTML_QuickForm();

        $form->addElement('static', 'fullname', _('Name') . ':', $_SESSION['user']->getFullName() );
        $form->addElement('static', 'username', _('Username') . ':', $_SESSION['user']->getUsername() );

        // VIKO admins can't belong to school
        if ( $_SESSION['user']->getGroup() != 'ADMIN' ) {
            $school = $_SESSION['user']->getSchool();
            $form->addElement('static', 'school', _('School') . ':', $school->getHTMLName() );

            // create select-box for selecting form
            $form->addElement(
                'select',
                'form',
                _('Form') . ':',
                FormList::getFormsSelectBox( $school, true ),
                array( 'id'=>'form' )
            );

            $school_form = $_SESSION['user']->getForm();
            if ( isset($school_form) ) {
                $school_form_id = $school_form->getID();
            }
            else {
                $school_form_id = 0;
            }

            $form->setDefaults( array( 'form' => $school_form_id ) );
        }

        $form->addElement('text', 'email', _('e-mail') . ':', array("id" => "email") );
        $form->addElement('submit', 'submit', _('Save') );

        $form->addRule(
            'email',
            _("This is not a correct e-mail address."),
            'email'
        );

        $form->setDefaults( array( 'email' => $_SESSION['user']->getEmail() ) );

        return $form;
    }

}


?>