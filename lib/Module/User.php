<?php

/**
 * Contains the Module_User class
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
require_once 'lib/HTML.php';
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';

/**
 * Manages the viewing / modifing users data
 *
 * <ul>
 * <li>/user/#id - view/edit user with id #id</li>
 * <li>/user/#id/delete - delete user with id #id</li>
 * <li>/user/add/#group/#form_id/#school_id - add user of group #group
 *     into form with id #form_id; group can be "STUDENT", "TEACHER" or
 *     "SCHOOLADMIN"; #form_id is optional; #school_id is required when
 *     admin is adding new user (probably schooladmin).</li>
 * </ul>
 *
 * For example address /user/add/TEACHER will give you a HTML form
 * for creating new teacher (the user group select box will have
 * "Teacher" selected).
 *
 * Address /user/add/STUDENT/128 will return HTML form for creating
 * student with a form with ID 128 selected as the form of student.
 */
class Module_User extends Module {

    /**
     * The user to be viewed and/or modified
     */
    var $_user = null;

    /**
     * Which action should be performed?
     */
    var $_action = "view";

    /**
     * Does the user belong to a form and which
     */
    var $_form = null;


    /**
     * Constructs new User Module
     *
     * @param array $parameters  parameters to the module
     */
    function Module_User( $parameters )
    {
        $actions = array( "view", "edit", "add", "delete" );
        // NOTE: ADMIN is not allowed, because ADMIN is THE root user.
        $user_groups = array( "STUDENT", "TEACHER", "SCHOOLADMIN" );

        $this->_user = new User();

        // select action (view, edit, add or delete)
        if ( isset($parameters[0]) && in_array( $parameters[0], $actions ) ) {
            $this->_action = $parameters[0];
        }
        else {
            // fatal error: no action defined
            trigger_error( "No action specified for this module.", E_USER_ERROR );
        }

        if ( $this->_action == "add" ) {
            // select group
            if ( isset( $parameters[1] ) && in_array( strtoupper($parameters[1]), $user_groups ) ) {
                $group = strtoupper($parameters[1]);
            }
            else {
                // fatal error: no user group specified
                trigger_error( "No user group specified when adding new user.", E_USER_ERROR );
            }
            $this->_user->setGroup( $group );

            // select form (if applicable)
            if ( isset( $parameters[2] ) && (int)$parameters[2] > 0 ) {
                $this->_form = new Form( (int)$parameters[2] );
                $this->_user->setForm( $this->_form );
            }

            // select school
            if ( $_SESSION['user']->hasAccessToGroup("ADMIN") ) {
                if ( isset( $parameters[3] ) && (int)$parameters[3] > 0 ) {
                    $school = new School( (int)$parameters[3] );
                    $this->_user->setSchool( $school );
                }
                else {
                    trigger_error( "When administrator adds users, school has to be specified.", E_USER_ERROR );
                }
            }
            else {
                $this->_user->setSchool( $_SESSION['user']->getSchool() );
            }
        }
        else {
            // select user
            if ( isset( $parameters[1] ) && (int)$parameters[1] > 0 ) {
                $this->_user->setID( (int)$parameters[1] );
            }
            else {
                // fatal error: no user selected
                trigger_error( "No user specified.", E_USER_ERROR );
            }
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
        return "user";
    }


    /**
     * Returns User page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // in case of viewing, editing or deleting a user,
        // we have to ensure that this user exists.
        // if the user doesn't exist - return error message.
        if ( $this->_action != "add" ) {
            if ( !$this->_user->loadFromDatabaseByID() ) {
                return $this->errorPage( _("The user you are trying to access does not exist.") );
            }
        }

        // everyone except admin is limited to view edit and delete
        // only the users of her own school.
        // if the selected user does not belong to the same school as current user of VIKO,
        // then raise an error message.
        if ( !$_SESSION['user']->hasAccessToGroup('ADMIN') && $this->_action != "add" ) {
            $user_school = $this->_user->getSchool();
            $active_school = $_SESSION['user']->getSchool();
            if ( $user_school->getID() != $active_school->getID() ) {
                return $this->accessDeniedPage( _("You can not access user of another school.") );
            }
        }

        // choose which action to perform
        switch ( $this->_action ) {
            case "view":
                return $this->_viewUser();
                break;
            case "edit":
                return $this->_editUser();
                break;
            case "add":
                return $this->_addUser();
                break;
            case "delete":
                return $this->_deleteUser();
                break;
        }
    }


    /**
     * Shows static information about the specified user
     *
     * @access private
     * @return string HTML fragment
     */
    function _viewUser()
    {
        // only teachers, schooladmins and admin
        // can view information about other users
        if ( !$_SESSION['user']->hasAccessToGroup('TEACHER') ) {
            return $this->accessDeniedPage( _("You are not authorized to view information about other users.") );
        }

        $user = $this->_user;

        // format user name
        $user_name = $user->getFullName();

        // format form name
        $form = $user->getForm();
        if ( isset($form) ) {
            $form_name = $form->getHTMLName();
        }
        else {
            $form_name = _("missing");
        }

        // format school name
        $school = $user->getSchool();
        $school_name = $school->getHTMLName();

        // format e-mail address
        if ( $user->getEmail() > "" ) {
            $email = $user->getEmailLink();
        }
        else {
            $email = _("missing");
        }


        // create all HTML for page content
        $html = $this->title( _("User") . ': ' . $user_name );
        $html.= HTML::dl(
            HTML::dt( _("Name") ),
            HTML::dd( $user_name ),

            HTML::dt( _("Form") ),
            HTML::dd( $form_name ),

            HTML::dt( _("School") ),
            HTML::dd( $school_name ),

            HTML::dt( _("e-mail") ),
            HTML::dd( $email )
        );

        $html .= $this->_backLinks();

        return $html;
    }


    /**
     * Allow modifications to a user
     *
     * @access private
     * @return string HTML fragment
     */
    function _editUser()
    {
        // only chooladmins and admins can edit user info
        if ( !$_SESSION['user']->hasAccessToGroup('SCHOOLADMIN') ) {
            return $this->accessDeniedPage( _("You are not authorized to edit users.") );
        }


        $html = $this->title( _("Change user") );

        $form = $this->_createForm();

        if ( $form->validate() ) {
            $this->_saveForm( $form );
            $html.= HTML::p( HTML::notice( _("User successfully modified.") ) );
        }

        // convert form to HTML
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );

        $html .= $this->_backLinks();

        return $html;
    }


    /**
     * Creates new user
     *
     * @access private
     * @return string HTML fragment
     */
    function _addUser()
    {
        // only chooladmins and admins can edit user info
        if ( !$_SESSION['user']->hasAccessToGroup('SCHOOLADMIN') ) {
            return $this->accessDeniedPage( _("You are not authorized to add users.") );
        }

        $html = $this->title( _("Add user") );

        $form = $this->_createForm();

        if ( $form->validate() ) {
            $this->_saveForm( $form );
            $html.= HTML::p( HTML::notice( _("User successfully added.") ) );
        }

        // convert form to HTML
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );

        $html .= $this->_backLinks();

        return $html;
    }


    /**
     * Deletes the user
     *
     * @access private
     * @return string HTML fragment
     */
    function _deleteUser()
    {
        // only chooladmins and admins can delete users
        if ( !$_SESSION['user']->hasAccessToGroup('SCHOOLADMIN') ) {
            return $this->accessDeniedPage( _("You are not authorized to delete users.") );
        }


        $name = $this->_user->getFullName();
        $html = $this->title( _("Deleting of user") . ': ' . $name );

        $this->_user->delete();

        $html.= HTML::p( HTML::notice( _("User was successfully deleted.") ) );

        $html .= $this->_backLinks();

        return $html;
    }


    /**
     * Creates list of links for returning to previous pages
     *
     * @return string HTML ul element
     */
    function _backLinks() {

        if ( $_SESSION['user']->hasAccessToGroup("ADMIN") ) {
            // for admins:

            // link back to the school page
            $school = $this->_user->getSchool();
			$uri = $this->URI('statistics', $school->getID()); 
            $label = sprintf( _("Return to the school %s"), $school->getHTMLName() );
        }
        elseif ( $_SESSION['user']->hasAccessToGroup("SCHOOLADMIN") ) {
            // for schooladmins:

            if ( $this->_user->getGroup() == 'STUDENT' ) {
                if ( isset($this->_form) ) {
                    $form = $this->_form;
                }
                else {
                    $form = $this->_user->getForm();
                }

                // link to the form the user is in
                $uri = $this->URI('students', $form->getID()); 
                $label = sprintf( _("Return to the form %s"), $form->getHTMLName() );
            }
            else {
                // link to teachers/schooladmins list
               $uri = $this->URI('teachers'); 
                $label = _("Return to the list of teachers and schooladmins");
            }
        }
        else {
            // for teachers:

            // if REFERER header is set, create link to previous page
            if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
                $uri = $_SERVER['HTTP_REFERER'];
                if ( preg_match('/students/', $uri ) ) {
                    $label = _("Return to the list of studens");
                }
                elseif ( preg_match('/marks/', $uri ) ) {
                    $label = _("Return to the list of marks");
                }
                elseif ( preg_match('/forum/', $uri ) ) {
                    $label = _("Return to the forum");
                }
                else {
                    $label = _("Return to the previous page");
                }
            }
            else {
                // without the referer, produce no link at all.
                return "";
            }
        }

        return HTML::backLink( $uri, $label, STANDALONE );
    }


    /**
     * Creates form object for changing information about other users
     *
     * @access private
     * @return HTML_QuickForm
     */
    function _createForm() {
        $form = new HTML_QuickForm();

        $this->_addFirstAndLastNameField( $form );
        $this->_addUsernameField( $form );
        $this->_addSchoolField( $form );
        $this->_addFormField( $form );
        $this->_addEmailField( $form );
        $this->_addGroupField( $form );
        $this->_addPasswordField( $form );

        // submit button
        $form->addElement(
            'submit',
            'submit',
            _("Save")
        );

        return $form;
    }


    /**
     * Adds fields for first- and last name to form
     *
     * The fields will have default values of selected user
     * only if the form is used for editing user, not when adding.
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _addFirstAndLastNameField( &$form )
    {
        $form->addElement(
            'text',
            'firstname',
            _("Firstname") . ':',
            array( 'id'=>'firstname' )
        );
        $form->addElement(
            'text',
            'lastname',
            _("Lastname") . ':',
            array( 'id'=>'lastname' )
        );

        // all these fields are required
        $form->addRule(
            'firstname',
            _("Every person must have a first name."),
            'required'
        );
        $form->addRule(
            'lastname',
            _("Every person must have a last name."),
            'required'
        );


        if ( $this->_action == "edit" ) {
            $form->setDefaults( array( "firstname" => $this->_user->getFirstName() ) );
            $form->setDefaults( array( "lastname" => $this->_user->getLastName() ) );
        }
    }


    /**
     * Adds field for username to form
     *
     * The field will have default value of selected user
     * only if the form is used for editing user, not when adding.
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _addUsernameField( &$form )
    {
        $form->addElement(
            'text',
            'username',
            _("Username") . ':',
            array( 'id'=>'username' )
        );

        // username is certainly required, has to be unique and
        // contain only limited set of characters
        $form->addRule(
            'username',
            _("All users need a username."),
            'required'
        );
        $form->addRule(
            'username',
            _("Username may only consist of characters a-z, A-Z, 0-9, _ and -."),
            'regex',
            '/^[a-zA-Z0-9_-]+$/'
        );
        $form->registerRule('uniqueUsername', 'callback', '_checkUniquenessOfUsername', 'Module_User');
        $form->addRule(
            'username',
            _("This username is already in use - choose another."),
            'uniqueUsername',
            array( "user" => $this->_user, "action" => $this->_action )
        );


        if ( $this->_action == "edit" ) {
            $form->setDefaults( array( "username" => $this->_user->getUsername() ) );
        }
    }


    /**
     * Adds field for e-mail to form
     *
     * The field will have default value of selected user
     * only if the form is used for editing user, not when adding.
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _addEmailField( &$form )
    {
        $form->addElement(
            'text',
            'email',
            _("E-mail") . ':',
            array( 'id'=>'email' )
        );

        // e-mail is not required, but has to be in correct form
        $form->addRule(
            'email',
            _("This is not a correct e-mail address."),
            'email'
        );

        if ( $this->_action == "edit" ) {
            $form->setDefaults( array( "email" => $this->_user->getEmail() ) );
        }
    }


    /**
     * Adds field for user group to form
     *
     * The field will have default value of selected user
     * or the group specified as a parameter for 'add' command.
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _addGroupField( &$form )
    {
        // create array of group select box items
        // not even admin will have the right to raise a user to admin status
        // admin is sort of like UNIX root user.
        $groups = array(
            'SCHOOLADMIN' => _("School administrator"),
            'TEACHER' => _("Teacher"),
            'STUDENT' => _("Student"),
        );

        $form->addElement(
            'select',
            'group',
            _("User group") . ':',
            $groups,
            array( 'id'=>'group' )
        );

        // it doesn't matter, if we are editing the user or adding,
        // because the user will have its group loaded from database
        // or specified as a parameter for 'add' command.
        $form->setDefaults( array( "group" => $this->_user->getGroup() ) );
    }


    /**
     * Adds field for school to form
     *
     * This static field will have the value of selected user
     * or the school specified as a parameter for 'add' command
     * or the school of current VIKO user.
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _addSchoolField( &$form )
    {
        $school = $this->_user->getSchool();

        $form->addElement(
            'static',
            'school',
            _("School") . ':',
            $school->getHTMLName()
        );
    }


    /**
     * Adds field for school form to HTML form
     *
     * This static field will have the value of selected user
     * or the school specified as a parameter for 'add' command
     * or the school of current VIKO user.
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _addFormField( &$form )
    {
        $form_list = FormList::getFormsSelectBox( $this->_user->getSchool(), true );
        $form->addElement(
            'select',
            'form',
            _("Form") . ':',
            $form_list,
            array( 'id'=>'form' )
        );

        if ( isset($this->_form) ) {
            $form->setDefaults( array( "form" => $this->_form->getID() ) );
        }
        elseif ( $this->_action != "add" ) {
            $school_form = $this->_user->getForm();
            if ( isset($school_form) ) {
                $form->setDefaults( array( "form" => $school_form->getID() ) );
            }
            else {
                $form->setDefaults( array( "form" => '0' ) );
            }
        }
        else {
            $form->setDefaults( array( "form" => '0' ) );
        }
    }


    /**
     * Adds fields for password to form
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _addPasswordField( &$form )
    {
        $form->addElement(
            'password',
            'password',
            _("Password") . ':',
            array( 'id'=>'password' )
        );
        $form->addElement(
            'password',
            'password-repeat',
            _("Repeat password") . ':',
            array( 'id'=>'password-repeat' )
        );

        // when adding new user, password field is required
        if ( $this->_action == "add" ) {
            $form->addRule(
                'password',
                _("This field is required"),
                'required'
            );
            $form->addRule(
                'password-repeat',
                _("This field is required"),
                'required'
            );
        }

        // the password must match with repeated new password
        $form->addRule(
            array('password', 'password-repeat'),
            _("The passwords do not match."),
            'compare'
        );

        // password must contain at least 5 characters
        $form->addRule(
            'password',
            _("Password must be at least 5 characters long."),
            'minlength',
            5
        );

        // only ASCII characters are allowed
        $form->addRule(
            'password',
            _("Password may only contain characters from the ASCII range."),
            'regex',
            '/^[\x20-\x7E]+$/'
        );
    }


    /**
     * Checks if username is unique (if changed)
     *
     * If username was not changed in the form, returns true.
     * If the change was made, but the username already exists, returns false.
     * Else returns true.
     *
     * @access private
     * @param string  $username  username to validate
     * @param User    $p         parameters ("user" and "action")
     * @return bool
     */
    function _checkUniquenessOfUsername( $username, $p )
    {
        if ( $p["action"] == "edit" && $p["user"]->getUsername() == $username ) {
            // username was not changed
            return true;
        }
        else {
            // if username exists return false, else true

            $db = DBInstance::get();
            $username_exists = (bool) $db->getOne(
                "SELECT COUNT(username) FROM Users WHERE username=?",
                array( $username )
            );

            return !$username_exists;
        }
    }


    /**
     * Saves the data in form
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _saveForm( &$form )
    {
        $user = $this->_user;

        if ( $this->_action == "edit" ) {
            // load the user object with old data
            $user->loadFromDatabaseByID();
        }

        // modify our selected user object and save
        $user->setFirstName( $form->exportValue("firstname") );
        $user->setLastName( $form->exportValue("lastname") );
        $user->setUsername( $form->exportValue("username") );
        $user->setEmail( $form->exportValue("email") );
        $user->setGroup( $form->exportValue("group") );

        // form is optional
        $form_id = $form->exportValue("form");
        if ( $form_id == 0 ) {
            $user->setForm( null );
        }
        else {
            $school_form = new Form( $form_id );
            $user->setForm( $school_form );
        }

        // password is optional when editing user / required when adding
        $password = $form->exportValue("password");
        if ( $password != "" ) {
            $user->setPassword( $password );
        }

        if ( !$user->save() ) {
            die("Error when saving user.");
        }
    }



}


?>