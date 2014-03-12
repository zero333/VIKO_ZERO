<?php

/**
 * Contains the Module_Register class
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
 * Manages registering new students
 *
 * <ul>
 * <li>/register - allows user to choose school, where to register.
 *     After school is selected, redirects to /register/#school_id.
 *     If VIKO database contains only one school, the user directly
 *     taken to /register/#school_id.</li>
 * <li>/register/#school_id - shows registration form for registering
 *     into school with ID #school_id.</li>
 * </ul>
 */
class Module_Register extends Module {

    /**
     * Constructs new Register Module
     *
     * @param array $parameters  parameters to the module
     */
    function Module_Register( $parameters )
    {
        if ( isset($parameters[0]) && (int)$parameters[0] > 0 ) {
            $this->_school = new School( (int)$parameters[0] );
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
        return "register";
    }


    /**
     * Returns Register page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // if school is chosen, then try to load it from database
        // if the school does not exist, show error message
        if ( isset( $this->_school ) ) {
            if ( !$this->_school->loadFromDatabaseByID() ) {
                return $this->errorPage( _("The school you have chosen does not exist.") );
            }

            // show registration page for selected school
            return $this->_registerUser();
        }
        else {
            // no school is chosen, so ask user to select one
            return $this->_selectSchool();
        }
    }


    /**
     * Returns page with form for selecting school
     *
     * When user selects a school, then redirects to the /register/#school_id.
     * Also performs an immediate redirect, when there exists only one school.
     *
     * @access private
     * @return string HTML fragment
     */
    function _selectSchool()
    {
        // check, that this module is enabled - if not, show error
        if ( !Configuration::getAllowRegistration() ) {
            return $this->accessDeniedPage(
                _("Registration of students has been disabled.")
            );
        }

        $school_count = $this->_getNrOfSchools();
        if ( $school_count == 0 ) {
            // show error, indicating that there are no schools in VIKO
            return $this->errorPage( _("There are no schools in VIKO to choose form.") );
        }
        elseif ( $school_count == 1 ) {
            // perform a redirect, if there is only one school to choose from
            $school_id = $this->_getFirstSchoolID();
            $this->redirect("register", $school_id ); 
            return false;
        }

        // create form for selecting school
        $school_select_form = $this->_createSchoolSelectForm();

        // if the user has selected a school, perform redirect
        if ( $school_select_form->validate() ) {
            $school_id = $school_select_form->exportValue("school_id");
            $this->redirect( "register", $school_id ); 
            return false;
        }

        // present the form to the user
        $html = $this->title( _("User registration: select school") );
        $html.= HTML_QuickForm_Renderer_VIKO::render( $school_select_form );
        $html.= HTML::backLink( $this->URI(), _("Return to the front page of VIKO"), STANDALONE ); 
        return $html;
    }


    /**
     * Returns HTML_QuickForm form for selecting school
     *
     * @access private
     * @return HTML_QuickForm
     */
    function _createSchoolSelectForm()
    {
        $form = new HTML_QuickForm();

        $school_list = $this->_getArrayOfSchools();

        $form->addElement(
            'select',
            'school_id',
            _("School") . ':',
            $school_list,
            array( 'id'=>'school_id' )
        );

        $form->addElement(
            'submit',
            'submit',
            _("Choose")
        );

        return $form;
    }

    /**
     * Returns associative array of school ID-s and names for all schools in VIKO
     *
     * Only these schools are included to the list, where forms exist.
     *
     * @access private
     * @return array all schools
     */
    function _getArrayOfSchools()
    {
        $db = DBInstance::get();
        $order_by = DBUtils::createOrderBy( array( "school_name" ) );
        $result = $db->query("
            SELECT
                Schools.school_id,
                school_name
            FROM
                Schools
                RIGHT JOIN
                Forms USING(school_id)
            $order_by
        ");

        if ( PEAR::isError( $result ) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        $school_list = array();
        while ( $result->fetchInto( $school ) ) {
            $school_list[ $school["school_id"] ]  =  $school["school_name"];
        }

        return $school_list;
    }


    /**
     * Returns ID of the only school in VIKO
     *
     * Actually this method returns the ID of first school (with forms) in Schools table.
     * This method is used after is checked, that there is only one school in VIKO.
     *
     * @access private
     * @return mixed
     */
    function _getFirstSchoolID()
    {
        // get the ID of school and return it
        $db = DBInstance::get();
        $school_id = $db->getOne("
            SELECT
                Schools.school_id
            FROM
                Schools
                RIGHT JOIN
                Forms USING(school_id)
            LIMIT 1
        ");

        if ( PEAR::isError( $school_id ) ) {
            trigger_error( $school_id->getMessage(), E_USER_ERROR );
        }

        return $school_id;
    }


    /**
     * Returns the number of schools that have at least one form in VIKO
     *
     * @access private
     * @return int  school count
     */
    function _getNrOfSchools()
    {
        $db = DBInstance::get();
        $count = $db->getOne("
            SELECT
                COUNT(*)
            FROM
                Schools
                RIGHT JOIN
                Forms USING(school_id)
        ");

        if ( PEAR::isError( $count ) ) {
            trigger_error( $count->getMessage(), E_USER_ERROR );
        }

        return $count;
    }


    /**
     * Shows form that allows user to register to school as student
     *
     * @access private
     * @return string HTML fragment
     */
    function _registerUser()
    {
        // create the HTML form
        $register_form = $this->_createRegistrationForm();

        // set default heading for the page
        $html = $this->title( _("User registration: student data") );

        // if the form is correctly filled in, add new user to database
        if ( $register_form->validate() ) {
            if ( $this->_registerNewStudent( $register_form ) ) {
                // on successful registration congratulate the user
                $html = $this->title( _("Registration successful!") );
                $html.= HTML::notice( _("You have successfully registered as new VIKO user.") );
                $html.= HTML::p( _("You may now go to the front page of VIKO and log in.") );
               $html.= HTML::backLink( $this->URI(), _("Return to the front page of VIKO"), STANDALONE ); 

                return $html;
            }
            else {
                // if registration somehow fails at this point, show error message
                // and ask the user to try again
                $html .= HTML::error(
                    _("Sorry, but registering your account failed. Please try again, maybe it succeeds this time.")
                );
            }
        }

        // render the form and other parts of the page to HTML
        $html.= HTML_QuickForm_Renderer_VIKO::render( $register_form );
        $html.= HTML::backLink( $this->URI(), _("Return to the front page of VIKO"), STANDALONE ); 

        return $html;
    }


    /**
     * Adds new user to database
     *
     * @access private
     * @param HTML_QuickForm $form
     * @return bool true on successful registration, false, when error accoured
     */
    function _registerNewStudent( &$form )
    {
        $user = new User();

        $user->setFirstName( $form->exportValue("firstname") );
        $user->setLastName( $form->exportValue("lastname") );
        $user->setUsername( $form->exportValue("username") );
        $user->setEmail( $form->exportValue("email") );
        $user->setSchool( $this->_school );
        $school_form = new Form( $form->exportValue("form") );
        $user->setForm( $school_form );
        $user->setGroup( "STUDENT" );
        $user->setPassword( $form->exportValue("password") );

        return $user->save();
    }


    /**
     * Returns HTML_QuickForm form for entering student data
     *
     * @access private
     * @return HTML_QuickForm
     */
    function _createRegistrationForm()
    {
        $form = new HTML_QuickForm();

        $this->_addFirstAndLastNameField( $form );
        $this->_addUsernameField( $form );
        $this->_addSchoolField( $form );
        $this->_addFormField( $form );
        $this->_addEmailField( $form );
        $this->_addPasswordField( $form );

        $form->addElement(
            'submit',
            'submit',
            _("Register")
        );

        return $form;
    }


    /**
     * Adds fields for first- and last name to form
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
    }


    /**
     * Adds field for username to form
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
        $form->registerRule('uniqueUsername', 'callback', '_checkUniquenessOfUsername', 'Module_Register');
        $form->addRule(
            'username',
            _("This username is already in use - choose another."),
            'uniqueUsername'
        );
    }


    /**
     * Adds field for e-mail to form
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
    }


    /**
     * Adds field for school to form
     *
     * This static field will have the value of selected school.
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _addSchoolField( &$form )
    {
        $form->addElement(
            'static',
            'school',
            _("School") . ':',
            $this->_school->getHTMLName()
        );
    }


    /**
     * Adds field for school form to HTML form
     *
     * @access private
     * @param HTML_QuickForm $form
     */
    function _addFormField( &$form )
    {
        $form_list = FormList::getFormsSelectBox( $this->_school );
        $form->addElement(
            'select',
            'form',
            _("Form") . ':',
            $form_list,
            array( 'id'=>'form' )
        );
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

        // both password fields are required
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
     * @return bool
     */
    function _checkUniquenessOfUsername( $username )
    {
        $db = DBInstance::get();
        $username_exists = (bool) $db->getOne(
            "SELECT COUNT(username) FROM Users WHERE username=?",
            array( $username )
        );

        return !$username_exists;
    }
}


?>