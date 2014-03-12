<?php

/**
 * Contains the UserFactory class
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

require_once 'User.php';

/**
 * Generates instances of User
 *
 */
class UserFactory
{

    /**
     * Creates a user by login information
     *
     * Prior to 1.1 version, VIKO used MySQL-s internal PASSWORD
     * function, which has changed between MySQL versions. Starting
     * from version 1.1, VIKO uses SHA1 instead, but to provide smooth
     * transition for users, who's password might be hashed in old way,
     * the following algorithm is used:
     *
     * First SHA1 is used to calculate has from password, if the hashes
     * do not match, the PASSWORD is used, and after that OLD_PASSWORD
     * function. If neither of those matches, then false is returned,
     * indicating, that the login information is incorrect. If the
     * match was not found with SHA1 function, that the password is
     * updated - raw password is hashed with SHA1 and stored to database.
     *
     * @access public
     * @param string $username
     * @param string $password
     * @return mixed on success generated instance of User class,
     *               on failure false.
     */
    static function createUserByLogin( $username, $password )
    {
        // try three different algorithms
        $algorithms = array("SHA1", "PASSWORD", "OLD_PASSWORD");

        foreach ($algorithms as $hash_algorithm ) {
            $user = UserFactory::_createUserByWhere(
                "username=? AND password=$hash_algorithm(?)",
                array($username, $password)
            );

            // if login succeeds with any of the algorithms
            if ($user) {
                // if another alorith than SHA1 succeeded,
                // then update the password of the user.
                // so that from now on SHA1 is used instead.
                if ($hash_algorithm != "SHA1") {
                    $user->changePassword( $password );
                    $user->save();
                }
                
                return $user;
            }
        }

        // only when none of the algorithms succeeds, return false
        return false;
    }


    /**
     * Creates a user by ID
     *
     * @access public
     * @param int $id
     * @return mixed on success generated instance of User class,
     *               on failure false.
     */
    function createUserByID( $id )
    {
        return UserFactory::_createUserByWhere(
            "user_id=?",
            array($id)
        );
    }

    /**
     * Creates a user by username
     *
     * @access public
     * @param string $username
     * @return User generated instance of User class
     */
    function createUserByUsername( $username )
    {
        return UserFactory::_createUserByWhere(
            "username=?",
            array($username)
        );
    }


    /**
     * Creates User subclass instance by using SQL WHERE-clause
     *
     * @access private
     * @param string $where_clause  the WHERE clause itself
     * @param array $where_parameters  parameters to the WHERE clause
     * @return mixed  on success generated instance of User class,
     *                on failure false.
     */
    static function _createUserByWhere( $where_clause, $where_parameters )
    {
        $db = DBInstance::get();
        $row = $db->getRow(
            "SELECT * FROM Users WHERE $where_clause",
            $where_parameters
        );

        // If error is found here - just die
        if ( PEAR::isError($row) ) {
            trigger_error( $row->getMessage(), E_USER_ERROR );
        }


        if ( isset($row) ) {
            $user = new User( $row['user_id'] );
            $user->setUsername( $row['username'] );
            $user->setFirstName( $row['firstname'] );
            $user->setLastName( $row['lastname'] );
            $user->setEmail( $row['email'] );
            $user->setGroup( $row['user_group'] );

            if ( isset($row['school_id']) ) {
                $school = new School( $row['school_id'] );
                $user->setSchool( $school );
            }

            if ( isset($row['form_id']) ) {
                $form = new Form();
                $form->setID( $row['form_id'] );
                $form->setSchool( $school );
                $user->setForm( $form );
            }

            return $user;
        }
        else {
            // the user with this ID was not found
            return false;
        }
    }


}



?>
