<?php

/**
 * Contains the User class
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

/**
 * This is a child class of TableAbstraction
 */
require_once 'TableAbstraction.php';

/**
 * The class contains a school object
 */
require_once 'School.php';

/**
 * The class contains a form object
 */
require_once 'Form.php';

/**
 * CourseList::getFromTeacher() is used in User::delete method to delete all the courses of teacher
 */
require_once 'CourseList.php';

/**
 * When deleting user, all associated materials also have to get deleted
 */
require_once 'MaterialFactory.php';

/**
 * The class needs to generate some HTML links
 */
require_once 'HTML.php';

/** 
* The class needs an URI service, provided by Module class 
*/ 
require_once 'Module.php'; 

/**
 * Person name in normal format
 */
define('FIRSTNAME_LASTNAME', 0);

/**
 * Person name in format suitable when in list of sorted names
 */
define('LASTNAME_FIRSTNAME', 1);


/**
 * Class for all different VIKO users
 *
 */
class User extends TableAbstraction
{
    /**
     * Returns username
     *
     * @return string the username
     */
    function getUsername()
    {
        return $this->getAttribute("_username");
    }


    /**
     * Sets username
     *
     * @param string $username username
     */
    function setUsername( $username )
    {
        $this->_username = $username;
    }


    /**
     * Sets password of user
     *
     * @param string $password plain text password
     */
    function setPassword( $password )
    {
        $this->_password_hash = sha1( $password );
    }


    /**
     * Returns first name
     *
     * @return string first name
     */
    function getFirstName()
    {
        return $this->getAttribute("_first_name");
    }


    /**
     * Sets first name
     *
     * @param string $first_name first name
     */
    function setFirstName( $first_name )
    {
        $this->_first_name = $first_name;
    }


    /**
     * Returns last name
     *
     * @return string last name
     */
    function getLastName()
    {
        return $this->getAttribute("_last_name");
    }


    /**
     * Sets last name
     *
     * @param string $last_name last name
     */
    function setLastName( $last_name )
    {
        $this->_last_name = $last_name;
    }


    /**
     * Returns e-mail address
     *
     * @return string e-mail address
     */
    function getEmail()
    {
        return $this->getAttribute("_email");
    }


    /**
     * Sets e-mail address
     *
     * @param string $email e-mail address
     */
    function setEmail( $email )
    {
        $this->_email = $email;
    }


    /**
     * Returns groupname
     *
     * @return string groupname
     */
    function getGroup()
    {
        return $this->getAttribute("_group");
    }


    /**
     * Sets groupname
     *
     * @param string $group groupname
     */
    function setGroup( $group )
    {
        if (
            $group == "STUDENT" ||
            $group == "TEACHER" ||
            $group == "SCHOOLADMIN" ||
            $group == "ADMIN"
        ) {
            $this->_group = $group;
        }
        else {
            trigger_error( "Unknown group $group.", E_USER_ERROR );
        }
    }


    /**
     * Returns form
     *
     * @return Form form object
     */
    function getForm()
    {
        return $this->getAttribute("_form");
    }


    /**
     * Sets form
     *
     * @param Form $form form object
     */
    function setForm( $form )
    {
        $this->_form = $form;
    }


    /**
     * Returns school
     *
     * @return School school object
     */
    function getSchool()
    {
        return $this->getAttribute("_school");
    }


    /**
     * Sets school
     *
     * @param School $school school object
     */
    function setSchool( $school )
    {
        $this->_school = $school;
    }


    /**
     * Returns both first and last name of user in single string
     *
     * @param int $order  should firstname be first or lastname
     * @return string     HTML-safe full name of the user
     */
    function getFullName( $order=FIRSTNAME_LASTNAME )
    {
        $firstname = $this->getFirstName();
        $lastname = $this->getLastName();

        if ( $order == FIRSTNAME_LASTNAME ) {
             $name = "$firstname $lastname";
        }
        else {
             $name = "$lastname $firstname";
        }

        // escape all five special characters: <, >, &, ", '
        return htmlspecialchars( $name, ENT_QUOTES );
    }


    /**
     * Returns link to user info or e-mail with user name as label
     *
     * If active user (saved in $_SESSION['user']) is student,
     * then e-mail link is returned,
     * otherwise link to /user/#id# page is created.
     *
     * @param int $order  should firstname be first or lastname
     * @return string     HTML anchor element
     */
    function getUserLink( $order=FIRSTNAME_LASTNAME )
    {
        $name = $this->getFullName( $order );

        if ( $_SESSION["user"]->hasAccessToGroup( "SCHOOLADMIN" ) ) {
            $id = $this->getID();
	        return HTML::a( Module::URI('user', 'edit', $id), $name ); 
        }
        elseif ( $_SESSION["user"]->hasAccessToGroup( "TEACHER" ) ) {
            $id = $this->getID();
            return HTML::a( Module::URI("user", "view", $id), $name ); 
        }
        else {
            $email = $this->getEmail();
            // only create e-mail-link, if user has e-mail address specified
            if ( $email == "" ) {
                return $name;
            }
            else {
                return HTML::a( "mailto:$email", $name );
            }
        }
    }


    /**
     * Returns link to user e-mail address
     *
     * The label of the link will also be the e-mail address.
     * If e-mail address is missing, an empty string is returned.
     *
     * @return string     HTML anchor element
     */
    function getEmailLink()
    {
        $email = $this->getEmail();
        if ( $email == "" ) {
            return "";
        }
        else {
            return HTML::a( "mailto:$email", $email );
        }
    }


    /**
     * Returns name of the table containing users
     */
    function getTableName()
    {
        return "Users";
    }


    /**
     * Returns the primary key field name of the users table
     */
    function getIDFieldName()
    {
        return "user_id";
    }


    /**
     * Implementation of reading a database record
     */
    function readFromDatabaseRecord( $record )
    {
        $this->_username = $record['username'];
        $this->_password_hash = $record['password'];
        $this->_first_name = $record['firstname'];
        $this->_last_name = $record['lastname'];
        $this->_email = $record['email'];
        $this->_group = $record['user_group'];
        $this->_school = new School( $record['school_id'] );
        if ( isset($record['form_id']) ) {
            $this->_form = new Form( $record['form_id'] );
            $this->_form->setSchool( $this->_school );
        }
    }


    /**
     * Implementation of composing a database record
     */
    function writeToDatabaseRecord()
    {
        // the following properties may not be NULL
        if ( !isset($this->_username) ) {
            trigger_error("Username must be specified when saving user.", E_USER_ERROR);
        }
        if ( !isset($this->_password_hash) ) {
            trigger_error("Password must be specified when saving user.", E_USER_ERROR);
        }
        if ( !isset($this->_first_name) ) {
            trigger_error("First name must be specified when saving user.", E_USER_ERROR);
        }
        if ( !isset($this->_last_name) ) {
            trigger_error("Last name must be specified when saving user.", E_USER_ERROR);
        }
        if ( !isset($this->_email) ) {
            trigger_error("E-mail address must be specified when saving user.", E_USER_ERROR);
        }
        if ( !isset($this->_group) ) {
            trigger_error("Group must be specified when saving user.", E_USER_ERROR);
        }

        $record = array(
            "username" => $this->_username,
            "password" => $this->_password_hash,
            "firstname" => $this->_first_name,
            "lastname" => $this->_last_name,
            "email" => $this->_email,
            "user_group" => $this->_group
        );

        // user does not need to belong to school - the field may be NULL
        if ( isset($this->_school) && $this->_school->getID() > 0 ) {
            $record['school_id'] = $this->_school->getID();
        }
        else {
            $record['school_id'] = null;
        }

        // user does not need to belong to form - the field may be NULL
        if ( isset($this->_form) && $this->_form->getID() > 0 ) {
            $record['form_id'] = $this->_form->getID();
        }
        else {
            $record['form_id'] = null;
        }

        return $record;
    }


    /**
     * Returns true, if user is of that group
     *
     * @access public
     * @param string $group groupname
     * @return boolean true, if user is in group $group
     */
    function belongsToGroup( $group )
    {
        return ($this->_group == $group);
    }


    /**
     * Returns true, if user is of higher or equal group than specified group
     *
     * This method should be used to detect if user can view certain material.
     * For example if only teachers should be able to edit list of courses,
     * then actually all the higher levels should also be able to do so.
     *
     * <pre>
     * if ( $_SESSION['user'].hasAccessToGroup('TEACHER') ) {
     *     // this code is executed if user is TEACHER, SCHOOLADMIN or ADMIN.
     * }
     * </pre>
     *
     * STUDENT rights: STUDENT, TEACHER, SCHOOLADMIN, ADMIN.
     *
     * TEACHER rights: TEACHER, SCHOOLADMIN, ADMIN.
     *
     * SCHOOLADMIN rights: SCHOOLADMIN, ADMIN.
     *
     * ADMIN rights: ADMIN.
     *
     * @access public
     * @param string $group groupname
     * @return boolean true, if user is in group $group
     */
    function hasAccessToGroup( $group )
    {
        if (
            ( $this->_group == $group ) ||
            ( $group == "STUDENT" ) ||
            ( $group == "TEACHER" && $this->_group != "STUDENT" ) ||
            ( $group == "SCHOOLADMIN" && $this->_group == "ADMIN" )
        ) {
            return true;
        }
        else {
            return false;
        }
    }


    /**
     * Changes password
     *
     * This method changes the password of user,
     * but it does so only in memory. To make change
     * permanent use the User::save method.
     *
     * An optional parameter $old_password may be specified,
     * to perform first a check if the old password is correct.
     * An example with that kind of checking follows:
     *
     * <pre>
     * if ( $user->changePassword( $newpass, $oldpass ) ) {
     *     $user->save();
     *     echo "Password successfully changed.";
     * }
     * else {
     *     echo "Wrong old password specified!";
     * }
     * </pre>
     *
     * @access public
     * @param string $new_password plain text password
     * @param string $old_password old plain text password,
     *               this attribute may be left unspecified, e.g. when
     *               administrator changes the password of regular user.
     * @return boolean true on success,
     *                 false when old password is incorrect
     */
    function changePassword( $new_password, $old_password = null )
    {
        if ( isset($old_password) ) {
            if ( $this->verifyPassword( $old_password ) ) {
                $this->setPassword( $new_password );
                return true;
            }
            else {
                return false;
            }
        }
        else {
            $this->setPassword( $new_password );
            return true;
        }
    }


    /**
     * Checks if the supplied argument is users' password
     *
     * @access public
     * @param string $password password to check
     * @return boolean true when password matches.
     */
    function verifyPassword( $password )
    {
        if ( isset($this->_username) ) {
            return User::authenticate( $this->_username, $password );
        }
        elseif ( isset($this->_id) ) {
            $this->loadFromDatabaseByID();
            return User::authenticate( $this->_username, $password );
        }
        else {
            trigger_error("No user ID specified when verifying password.", E_USER_ERROR);
        }
    }


    /**
     * Checks if username and supplied password match
     *
     * This function may be called statically. An example follows:
     *
     * <pre>
     * if ( User::authenticate( $username, $pass ) ) {
     *     echo "Password correct.";
     * }
     * else {
     *     echo "Password and username do not match!";
     * }
     * </pre>
     *
     * <strong>Note!</strong> If the password is stored in database
     * by using PASSWORD of OLD_PASSWORD hash, then the method
     * automagically replaces it with SHA1 hash.
     *
     * @access public
     * @param string $username username
     * @param string $password password to check
     * @return boolean true when password matches.
     */
    function authenticate( $username, $password )
    {
        // Try authenticating the user with applying
        // SHA1 function to the password
        if ( User::authenticateWithHashFunction($username, $password, "SHA1") )
        {
            return true;
        }
        // If this fails, then the password might be stored
        // with either PASSWORD() or OLD_PASSWORD() function.
        // First try with PASSWORD and then with OLD_PASSWORD.
        elseif (
            User::authenticateWithHashFunction($username, $password, "PASSWORD") ||
            User::authenticateWithHashFunction($username, $password, "OLD_PASSWORD")
        ) {
            // re-save the password
            // using the current password encryption technology of VIKO
            return User::savePassword($username, $password);
        }
        // Finally give up and return error
        else
        {
            return false;
        }
    }


    /**
     * Authenticates the user by applying
     * custom hashfunction to the $password.
     * On success returns true.
     *
     * @return boolean
     */
    function authenticateWithHashFunction($username, $password, $function)
    {
        $db = DBInstance::get();

        $result = $db->getOne(
            "
            SELECT
                user_id
            FROM
                Users
            WHERE
                username = ? AND
                password = $function( ? )
            ",
            array( $username, $password )
        );

        // check for errors
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        // if the result is set, the query succeeded
        return isset($result);
    }


    /**
     * Saves password into database
     *
     * Statically callable.
     *
     * @access public
     * @return boolean false when username was not found
     */
    function savePassword( $username, $password )
    {
        $db = DBInstance::get();
        $result = $db->query(
            "
            UPDATE Users SET
                password = SHA1( ? )
            WHERE
                username = ?
            ",
            array( $password, $username )
        );

        // check for errors
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        return ( $db->affectedRows() == 1 );
    }


    /**
     * Removes the user from database
     *
     * The ID must be specified for this to work.
     *
     * @access public
     * @return boolean true on successful delete.
     *                 false, if the user with ID did not exist
     */
    function delete()
    {
        // first delete the user record itself
        if ( parent::delete() ) {

            // After that remove all the courses the user is teaching
            $course_list = CourseList::getFromTeacher( $this );
            foreach ( $course_list as $course ) {
                $course->delete();
            }

            $db = DBInstance::get();

            // remove user from each course where he is registered as student
            $db->query("DELETE FROM Courses_Users WHERE user_id=?", $this->_id );
            // remove all the marks he has received
            $db->query("DELETE FROM Marks WHERE user_id=?", $this->_id );
            // and remove all the forum posts he has created
            $db->query("DELETE FROM Posts WHERE user_id=?", $this->_id );

            // get list of materials and delete these
            $r = $db->query("SELECT * FROM Materials WHERE user_id=?", $this->_id );
            while ( $r->fetchInto( $row ) ) {
                $material = MaterialFactory::material( $row['material_type'], $row['material_id'] );
                $material->readFromDatabaseRecord( $row );
                $material->delete();
            }

            // NOTE: Maybe we should just mark the user as deleted, not actually delete him?

            return true;
        }
        else {
            return false;
        }
    }



}



?>
