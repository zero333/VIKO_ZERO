<?php

/**
 * Contains the Module_ForgotPassword class
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
require_once 'lib/UserFactory.php';
require_once 'Pear/Text/Password.php';
require_once 'lib/HTML.php';
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';

/**
 * Manages the process of creating new password for user who has forgotten his password
 *
 * This module takes no parameters and is accessed through URI /password-restore.
 *
 * A form is shown to the user to enter his username. If the username exists,
 * a new password is generated and sent to the e-mail address of the user.
 * If the user has no e-mail address specified, an e-mail is sent to schooladmin,
 * telling her that particular user has lost password. If even the schooladmin
 * has no e-mail address specified, a message is shown to the user, telling he
 * should contact the school admin by himself.
 *
 * Only teachers and students can use this form for regenerating password.
 * Schooladmins must directly contact VIKO administrator. And the only option
 * for VIKO admin on password lost is change it directly in VIKO database.
 */
class Module_ForgotPassword extends Module {

    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "forgot-password";
    }


    /**
     * Returns ForgotPassword page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        $form = $this->_createAskNewPasswordForm();
        if ( $form->validate() ) {
            $html = $this->_sendNewPasswordToUser( $form->exportValue( 'username' ) );
        }
        else {
            $html = $this->title( _("Forgot your password?") );

            $html.= HTML::p( _(
"Enter your username and we send a new password to your e-mail address
(specified in your profile). If your profile does not contain e-mail
address, the school administrator of VIKO is notified about this lost
password."
            ) );

            $html.= HTML_QuickForm_Renderer_VIKO::render( $form );
        }

       $html.= HTML::backLink( $this->URI(), _("Return to the front page of VIKO"), STANDALONE ); 

        return $html;
    }


    /**
     * Creates HTML form where user can enter her username
     *
     * @access private
     * @return HTML_QuickForm
     */
    function _createAskNewPasswordForm()
    {
        $form = new HTML_QuickForm();

        $form->addElement(
            'text',
            'username',
            _("Username") . ':',
            array( 'id'=>'username' )
        );

        // username must exist
        $form->registerRule('existingUsername', 'callback', '_checkExistanceOfUsername', 'Module_ForgotPassword');
        $form->addRule(
            'username',
            _("No user exists with specified username."),
            'existingUsername'
        );

        // username must belong to either student or teacher, not admin or schooladmin
        $form->registerRule('allowedGroup', 'callback', '_checkGroupOfUsername', 'Module_ForgotPassword');
        $form->addRule(
            'username',
            _("This username belongs to administrator or school administrator. Only students and teachers can use this form for retrieving new password."),
            'allowedGroup'
        );

        $form->addElement(
            'submit',
            'submit',
            _("Ask for new password")
        );

        return $form;
    }


    /**
     * Checks if username exists in VIKO database
     *
     * @access private
     * @param string  $username  username to validate
     * @return bool true if username exists
     */
    function _checkExistanceOfUsername( $username )
    {
        $db = DBInstance::get();
        $username_exists = (bool) $db->getOne(
            "SELECT COUNT(username) FROM Users WHERE username=?",
            array( $username )
        );

        return $username_exists;
    }


    /**
     * Checks if username belongs to either student or teacher
     *
     * @access private
     * @param string  $username  username to validate
     * @return bool true if username belongs to student or teacher
     */
    function _checkGroupOfUsername( $username )
    {
        $db = DBInstance::get();
        $student_or_teacher = (bool) $db->getOne(
            "SELECT
                COUNT(username)
            FROM
                Users
            WHERE
                username=?
                AND
                ( user_group='STUDENT' OR user_group='TEACHER')",
            array( $username )
        );

        return $student_or_teacher;
    }


    /**
     * Tries to create new password and send it to user with e-mail
     *
     * @access private
     * @param string $username
     * @return string HTML fragment
     */
    function _sendNewPasswordToUser( $username )
    {
        // get the user object, that matches given username
        $user = UserFactory::createUserByUsername( $username );

        if ( $user === false ) {
            // UserFactory was unable to load user with specified username for database.
            // This might happen, when user is deleted in the middle of this transaction
            // quite unlikely, but still.
            return $this->errorPage( _("No user exists with specified username.") );
        }


        if ( $user->getEmail() != "" ) {
            // if user has e-mail address specified,
            // generate new password and send it to the user
            return $this->_generatePasswordAndSendToUser( $user );
        }
        else {
            // in case of no e-mail address for user
            // try to send e-mail to schooladmin telling the user has lost her password
            return $this->_noticeSchoolAdminAboutLostPassword( $user );
        }
    }


    /**
     * Changes the password of user and sends e-mail to user containing the new password
     *
     * @access private
     * @param User $user
     * @return string HTML fragment
     */
    function _generatePasswordAndSendToUser( $user )
    {
        // generate new password consisting of 8 alphanumeric characters
        $new_password = Text_Password::create(8, 'unpronounceable', 'alphanumeric');

        // change the current password of user
        $user->setPassword( $new_password );

        // try saving the password
        if ( !$user->save() ) {
            // it failed? alright then - show error:
            return $this->errorPage( _("Unable to change password of specified user.") );
        }

        // send the password to user with e-mail
        if ( $this->_sendPasswordToUser( $user, $new_password ) ) {
            // success!
            return
                $this->title( _("Password successfully changed!") ) .
                HTML::notice( _(
                    "New password has been generated and sent to your e-mail address. Check your mailbox for your new VIKO password."
                ) );
        }
        else {
            // something wrong with e-mail system...
            return $this->errorPage( _("Failed sending password with e-mail.") );
        }
    }


    /**
     * Sends e-mail to schooladmin about user having lost her password
     *
     * @access private
     * @param User $user
     * @return string HTML fragment
     */
    function _noticeSchoolAdminAboutLostPassword( $user )
    {
        // first, try retrieving the schooladmin of this school
        $schooladmin = $this->_getSchoolAdminOfUser( $user );
        if ( $schooladmin === false ) {
            // that's odd - no schooladmins in this school
            return $this->errorPage(
                _("There are no school administrators in your school.")
            );
        }

        // ensure, that the admin has e-mail address
        if ( $schooladmin->getEmail() == "" ) {
            // probably lazy schooladmins...
            return $this->errorPage(
                _("The school administrator of your school has no e-mail address specified.")
            );
        }

        // now everything should be OK, so attempt sending e-mail to schooladmin
        if ( $this->_sendNoticeToSchoolAdmin( $schooladmin, $user ) ) {
            // success!
            return
                $this->title( _("School administrator has been notified") ) .
                HTML::notice( _(
                    "A message has been sent to school administrator, telling that you have lost your password."
                ) );
        }
        else {
            // sending the mail failed
            return $this->errorPage( _("Failed sending e-mail to school administrator.") );
        }
    }


    /**
     * Returns schooladmin object associated with the school of user
     *
     * If there is more than one schooladmin in school,
     * the first one is returned.
     *
     * @access private
     * @param User $user
     * @return mixed     on success a User object, on failure false
     */
    function _getSchoolAdminOfUser( $user )
    {
        $school = $user->getSchool();

        $db = DBInstance::get();
        $record = $db->getRow(
            "SELECT
                *
            FROM
                Users
            WHERE
                school_id=?
                AND
                user_group='SCHOOLADMIN'
            ",
            array( $school->getID() )
        );

        // If we get error here, we die.
        if ( PEAR::isError($record) ) {
            trigger_error( $record->getMessage(), E_USER_ERROR );
        }

        if ( isset($record) ) {
            // load new user object with data from schooladmin record
            $schooladmin = new User( $record['user_id'] );
            $schooladmin->readFromDatabaseRecord( $record );

            return $schooladmin;
        }
        else {
            return false;
        }
    }

    /**
     * Detects the installation URI
     *
     */


	function curPageURL() {
      	 $pageURL = $_SERVER["HTTP_HOST"];
		 return $pageURL;
	}

    /**
     * Sends password for username to e-mail address
     *
     * @access private
     * @param User $user       user who gets this new password
     * @param string $password   new password (in plain text)
     * @return bool success
     */
	

    function _sendPasswordToUser( $user, $password )
    {
        // To:
        $to_name = $user->getFirstName() . " " . $user->getLastName();
        $to_email = $user->getEmail();

        // From:
		$schooladmin = $this->_getSchoolAdminOfUser( $user );
        $from_name = _("VIKO");
        $from_email = $schooladmin->getEmail();

        // Subject:
        $subject = _("VIKO: Password change");

        // Message body:
        $message = I18n::getFileContents("forgot-password-user.txt");
        $username = $user->getUsername();
        $message = str_replace( '%username%', $username, $message );
        $message = str_replace( '%password%', $password, $message );
        $message = str_replace( '%url%', $this->curPageURL(), $message );

        return I18n::email(
            $to_name,
            $to_email,
            $subject,
            $message,
            $from_name,
            $from_email
        );
    }


    /**
     * Sends notice to schooladmin, that one user has lost her password
     *
     * TODO: The message should also contain URI where the admin can edit this user.
     *
     * @access private
     * @param User $schooladmin  school administrator who gets the message
     * @param User $user         user who lost her password
     * @return bool success
     */
    function _sendNoticeToSchoolAdmin( $schooladmin, $user )
    {
        // switch to default locale
        $current_locale = Configuration::getLocale();
        $default_locale = Configuration::getDefaultLocale();
        Configuration::setLocale( $default_locale );
        setlocale( LC_ALL, $default_locale. ".UTF-8" );
        setlocale( LC_TIME, $default_locale. ".UTF-8" );

        // To:
        $to_name = $schooladmin->getFirstName() . " " . $schooladmin->getLastName();
        $to_email = $schooladmin->getEmail();

        // From:
        $from_name = _("VIKO");
        $from_email = $schooladmin->getEmail();

        // Subject:
        $username = $user->getUsername();
        $subject = sprintf( _("VIKO: User %s forgot password"), $username );

        // Message body:
        $message = I18n::getFileContents("forgot-password-admin.txt");
        $person_name = $user->getFirstName() . " " . $user->getLastName();
        $message = str_replace( '%person_name%', $person_name, $message );
        $message = str_replace( '%username%', $username, $message );


        // switch back to current interface locale
        Configuration::setLocale( $current_locale );
        setlocale( LC_ALL, $current_locale. ".UTF-8" );
        setlocale( LC_TIME, $current_locale. ".UTF-8" );

        return I18n::email(
            $to_name,
            $to_email,
            $subject,
            $message,
            $from_name,
            $from_email
        );
    }
}


?>