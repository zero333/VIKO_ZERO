<?php

/**
 * Contains the School class
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
require_once 'lib/TableAbstraction.php';

/**
 * Form class is used by School::getForms() method
 */
require_once 'lib/Form.php';

/**
 * Manages school data
 *
 * An example of creating new school and storing it to database:
 *
 * <pre>
 * $school = new School();
 * $school->setName("University of California");
 * $school->save();
 * </pre>
 *
 * After a new school was saved, we can ask the database ID of it:
 *
 * <pre>
 * $id = $school->getID();
 * $name = $school->getHTMLName();
 * echo "School '$name' was added with ID $id.\n";
 * </pre>
 *
 * After we got an ID of some school, we may create a new School object,
 * and ask it's name:
 *
 * <pre>
 * $school_2 = new School();
 * $school_2->setID( $id );
 * echo "School name is " . $school_2->getHTMLName() . "\n";
 * </pre>
 *
 * And last we can delete the school completely from database
 *
 * <pre>
 * $school->delete();
 * </pre>
 */
class School extends TableAbstraction
{
    /**
     * Returns school name
     *
     * @return string name of the school
     */
    function getName()
    {
        return $this->getAttribute("_name");
    }


    /**
     * Sets school name
     *
     * @param string $name name of the school
     */
    function setName( $name )
    {
        $this->_name = $name;
    }


    /**
     * Returns school name that is safe to insert into HTML
     *
     * @return string name of the school with special characters escaped
     */
    function getHTMLName()
    {
        return htmlspecialchars( $this->getName(), ENT_QUOTES );
    }


    /**
     * Returns name of the table containing schools
     */
    function getTableName()
    {
        return "Schools";
    }


    /**
     * Returns the primary key field name of the schools table
     */
    function getIDFieldName()
    {
        return "school_id";
    }


    /**
     * Implementation of reading a database record
     */
    function readFromDatabaseRecord( $record )
    {
        $this->_name = $record['school_name'];
    }


    /**
     * Implementation of composing a database record
     */
    function writeToDatabaseRecord()
    {
        return array( "school_name" => $this->_name );
    }


    /**
     * Returns all the forms in this school
     *
     * Forms are ordered by form names.
     *
     * @return array forms
     */
    function getForms()
    {
        // ensure that School ID is specified
        if ( !isset($this->_id) ) {
            trigger_error( "No School ID specified when requesting associated Forms.", E_USER_ERROR );
        }

        // query all associated forms
        $db = DBInstance::get();
        $result = $db->query(
            "SELECT form_id, form_name FROM Forms WHERE school_id = ? ORDER BY form_name",
            $this->_id
        );

        // If we get error here, we die.
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        // convert each retrieved record into form object
        $forms = array();
        while ( $result->fetchInto($row) ) {
            $frm = new Form();
            $frm->setID( $row['form_id'] );
            $frm->setName( $row['form_name'] );
            $frm->setSchool( $this );
            $forms[] = $frm;
        }

        // return as array
        return $forms;
    }


    /**
     * Removes the school with all its contents from database
     *
     * The ID must be specified for this to work.
     *
     * @access public
     * @return boolean true on successful delete.
     *                 false, if the school with ID did not exist
     */
    function delete()
    {
        // first delete the school record itself
        if ( parent::delete() ) {

            $db = DBInstance::get();

            // After that remove all the users from that school
            // this should also delete all the courses and contents of those
            $result = $db->query("SELECT user_id FROM Users WHERE school_id=?", $this->_id );
            while ( $result->fetchInto( $row ) ) {
                $user = new User( $row['user_id'] );
                $user->delete();
            }

            // now only the empty forms should remain, so we delete those
            $db->query("DELETE FROM Forms WHERE school_id=?", $this->_id );

            return true;
        }
        else {
            return false;
        }
    }
}



?>
