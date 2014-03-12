<?php

/**
 * Contains the Form class
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
 * StudentList::getFromForm() is used in Form::delete method to delete all the students in form
 */
require_once 'StudentList.php';


/**
 * Manages form data
 *
 */
class Form extends TableAbstraction
{
    /**
     * Returns form name
     *
     * @return string name of the form
     */
    function getName()
    {
        return $this->getAttribute("_name");
    }


    /**
     * Sets form name
     *
     * @param string $name name of the form
     */
    function setName( $name )
    {
        $this->_name = $name;
    }


    /**
     * Returns form name that is safe to insert into HTML
     *
     * @return string name of the form with special characters escaped
     */
    function getHTMLName()
    {
        return htmlspecialchars( $this->getName(), ENT_QUOTES );
    }


    /**
     * Returns the school of the form
     *
     * @return School school where the form belongs to
     */
    function getSchool()
    {
        return $this->getAttribute("_school");
    }


    /**
     * Sets school of the form
     *
     * @param School $school the school where the form belongs
     */
    function setSchool( $school )
    {
        $this->_school = $school;
    }


    /**
     * Returns name of the table containing forms
     */
    function getTableName()
    {
        return "Forms";
    }


    /**
     * Returns the primary key field name of the forms table
     */
    function getIDFieldName()
    {
        return "form_id";
    }


    /**
     * Implementation of reading a database record
     */
    function readFromDatabaseRecord( $record )
    {
        $this->_name = $record['form_name'];
        $this->_school = new School( $record['school_id'] );
    }


    /**
     * Implementation of composing a database record
     */
    function writeToDatabaseRecord()
    {
        if ( !isset($this->_name) ) {
            trigger_error("Form name must be specified when saving form.", E_USER_ERROR);
        }
        if ( !( isset($this->_school) && $this->_school->getID() > 0 ) ) {
            trigger_error("School id must be specified when saving form.", E_USER_ERROR);
        }

        return array(
            "form_name" => $this->_name,
            "school_id" => $this->_school->getID()
        );
    }


    /**
     * Removes the form together with all the students in it from database
     *
     * @return boolean true on successful delete.
     */
    function delete()
    {
        // first delete the form record itself
        if ( parent::delete() ) {

            // After that remove all the students in this form
            $student_list = StudentList::getFromForm( $this );
            foreach ( $student_list as $student ) {
                $student->delete();
            }

            return true;
        }
        else {
            return false;
        }
    }


}



?>
