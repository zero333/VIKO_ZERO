<?php

/**
 * Contains the Module_ChangePassword class
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
 * Manages the user password changing in VIKO
 *
 */
class Module_ChangePassword extends Module {


    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    public function getID()
    {
        return "change-password";
    }


    /**
     * Returns ChangePassword page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // translate the title
        $html = $this->title( _("Change password") );

        $form = $this->_createForm();

        // perform validation and on success change the password
        if ( $form->validate() ) {

            $this->_change_password(
                $form->exportValue('new-password')
            );

            $html.= HTML::p( HTML::notice( _("Password updated successfully.") ) );
        }
        else {
            // convert form to HTML
            $html .= HTML_QuickForm_Renderer_VIKO::render( $form );
        }

        // backlink
        $html.= HTML::backLink( $this->URI("my-viko"), _("Return to the main page"), STANDALONE );

        return $html;
    }


    /**
     * Performs the password change
     *
     * Returns message to be displayd on the page about successfull change
     *
     * @access private
     * @param string $new_password the new passphrase to use
     */
    static function _change_password( $new_password )
    {
        User::savePassword( $_SESSION['user']->getUsername(), $new_password );
    }


    /**
     * Creates an HTML form for changing the password
     *
     * @access private
     * @return Form new form object
     */
    function _createForm()
    {
        $form = new HTML_QuickForm();

        $form->addElement(
            'static',
            'username',
            _('Username') . ':',
            $_SESSION['user']->getUsername()
        );
        $form->addElement(
            'password',
            'old-password',
            _('Old password') . ':',
            array('id'=>'old-password')
        );
        $form->addElement(
            'password',
            'new-password',
            _('New password') . ':',
            array('id'=>'new-password')
        );
        $form->addElement(
            'password',
            'new-password-repeat',
            _('Repeat password') . ':',
            array('id'=>'new-password-repeat')
        );
        $form->addElement(
            'submit',
            'submit',
            _('Save')
        );

        $form->addRule(
            'old-password',
            _("This field is required"),
            'required'
        );
        $form->addRule(
            'new-password',
            _("This field is required"),
            'required'
        );
        $form->addRule(
            'new-password-repeat',
            _("This field is required"),
            'required'
        );


        // check if the old password is correct
        $form->registerRule('verifypassword', 'callback', '_checkCurrentPassword', 'Module_ChangePassword');
        $form->addRule(
            'old-password',
            _("The old password is incorrect"),
            'verifypassword'
        );

        // the new password must match with repeated new password
        $form->addRule(
            array('new-password', 'new-password-repeat'),
            _("The passwords do not match."),
            'compare'
        );

        // password must contain at least 5 characters
        $form->addRule(
            'new-password',
            _("Password must be at least 5 characters long."),
            'minlength',
            5
        );

        // only ASCII characters are allowed
        $form->addRule(
            'new-password',
            _("Password may only contain characters from the ASCII range."),
            'regex',
            '/^[\x20-\x7E]+$/'
        );

        return $form;
    }


    /**
     * Performs the validation of old password field
     *
     * @access private
     * @param string $password the password to check
     * @return bool true if password is correct
     */
    function _checkCurrentPassword( $password )
    {
        return $_SESSION['user']->verifyPassword( $password );
    }

}


?>