<?php

/**
 * Contains the TableAbstraction class
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
 * This class abstracts database tables, so it needs database connection
 */
require_once 'DBInstance.php';


/**
 * Handles general tasks of interfacing a database table
 *
 * Subclasses need to implement the specific functionality.
 * Child classes defenetly need to override the following methods:
 *
 * <ul>
 * <li>readFromDatabaseRecord()</li>
 * <li>writeToDatabaseRecord()</li>
 * <li>getTableName()</li>
 * <li>getIDFieldName()</li>
 * </ul>
 *
 * @abstract
 */
class TableAbstraction
{
    /**
     * Value of the ID column
     *
     * TableAbstraction class requires that the table has one column,
     * that contains the unique primary key (possibly auto-generated).
     *
     * @access private
     * @var integer
     */
    var $_id = null;
    
    var $_count = NULL;


    /**
     * Creates new TableAbstraction instance
     *
     * The record ID may be optionally specified.
     *
     * @param int $id database record ID
     */
    function TableAbstraction( $id = null ) {
        $this->_id = $id;
    }


    /**
     * Returns the value of ID column, null if unset
     *
     * @access public
     * @return integer row ID
     */
    function getID()
    {
        return $this->_id;
    }


    /**
     * Sets value for the ID column
     *
     * @access public
     * @param integer $id row ID
     */
    function setID( $id )
    {
        $this->_id = $id;
    }


    /**
     * Returns the value of private attribute
     *
     * If attribute is not set, then it is attempted to retrieve from database.
     * If the retrieval from database failed, false is returned.
     *
     * @access protected
     * @param string $attribute_name
     * @return mixed value of the requested attribute
     */
    function getAttribute( $attribute_name )
    {
        if ( isset($this->$attribute_name) ) {
            // the requested value is readily available - just return it
            return $this->$attribute_name;
        }
        else {
            // so we request it from the database
            if ( $this->loadFromDatabaseByID() ) {
                return ( isset($this->$attribute_name) ) ? $this->$attribute_name : null;
            }
            else {
                return false;
            }
        }
    }


    /**
     * Loads object with info from table corresponding to specified record ID
     *
     * If a record corresponding to the ID was not found, false is returned.
     *
     * If record ID is unavailable,
     * an error is generated and the execution stops.
     *
     * @access public
     * @return bool true if corresponding record was found
     */
    function loadFromDatabaseByID()
    {
        // ensure that record ID is available
        if ( !isset($this->_id) ) {
            trigger_error( "No record ID specified when loading.", E_USER_ERROR );
        }

        // try to load a DB record corresponding to the ID
        $db = DBInstance::get();
        $table_name = $this->getTableName();
        $id_field_name = $this->getIDFieldName();
        $record = $db->getRow(
            "SELECT
                *
            FROM
                $table_name
            WHERE
                $id_field_name = ?
            LIMIT 1",
            array( $this->_id )
        );
        
        // Ensure that the query went without error.
        // If it didn't announce the error and stop execution.
        if ( PEAR::isError( $record ) ) {
            trigger_error( $record->getMessage(), E_USER_ERROR );
        }

        // if the ID existed in table apply the retrieved data to this object.
        // if not, then just return false.
        if ( isset($record) ) {
            $this->readFromDatabaseRecord( $record );
            return true;
        }
        else {
            return false;
        }
    }


    /**
     * Saves object information into database
     *
     * If record ID is set, an existing record will be updated,
     * without ID a new record will be inserted.
     *
     * @access public
     * @return boolean success
     */
    function save()
    {
        if ( isset( $this->_id ) ) {
            return $this->_updateRecord();
        }
        else {
            return $this->_insertRecord();
        }
    }

    public function SaveTemplate() {
        return $this->_insertRecord();
    }    

    /**
     * Updates the record in database
     *
     * @access private
     * @return boolean success
     */
    function _updateRecord()
    {
        $field_values = $this->writeToDatabaseRecord();

        $where_clause = $this->getIDFieldName() . '=' . $this->_id;

        $db = DBInstance::get();
        $result = $db->autoExecute(
            $this->getTableName(),
            $field_values,
            DB_AUTOQUERY_UPDATE,
            $where_clause
        );

        // check for errors
        if ( PEAR::isError($result) ) {
            // The update might fail because of a UNIQUE constraint
            // in that case return false,
            // for other errors just show the error message and stop.
            if ( $result->getCode() == DB_ERROR_CONSTRAINT ) {
                return false;
            }
            else {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }
        }

        // we reached here, so everything should have gone just fine
        return true;
    }


    /**
     * Inserts new record into database
     *
     * @access private
     * @return boolean success
     */
    function _insertRecord()
    {
        
        $field_values = $this->writeToDatabaseRecord();

        $db = DBInstance::get();
        $result = $db->autoExecute(
            $this->getTableName(),
            $field_values,
            DB_AUTOQUERY_INSERT
        );
        // check for errors
        if ( PEAR::isError($result) ) {
            // The insert might fail because of a UNIQUE constraint
            // in that case return false,
            // for other errors just show the error message and stop.
            if ( $result->getCode() == DB_ERROR_CONSTRAINT ) {
                return false;
            }
            else {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }
        }

        // Get the ID of last insert with a help of MySQL specific function
        // TODO: This has to be changed, if we want to port VIKO to other databases
        $this->_id = mysql_insert_id();

        // we reached here, so everything should have gone just fine
        return true;
    }


    /**
     * Removes record corresponding to the ID from database table
     *
     * The ID must be specified for this to work.
     *
     * @access public
     * @return boolean true on successful delete.
     */
    function delete()
    {
        // ensure that record ID is available
        if ( !isset($this->_id) ) {
            trigger_error( "No record ID specified when deleting.", E_USER_ERROR );
        }

        // remove the record from database
        $db = DBInstance::get();
        $table_name = $this->getTableName();
        print_r($table_name);
        $id_field_name = $this->getIDFieldName();
        echo $id_field_name;
        $result = $db->query(
            "DELETE FROM $table_name WHERE $id_field_name = ? LIMIT 1",
            $this->_id
        );

        // Check tha there were no errors in performing the query
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        // return true, if a record was deleted
        return ( $db->affectedRows() == 1 );
    }


    //
    // abstract methods that require implementing in child classes
    //

    /**
     * Returns the name of the database table which the class abstracts
     *
     * @access protected
     * @return string table name
     */
    function getTableName()
    {
        trigger_error( "TableAbstraction::getTableName() not implemented.", E_USER_ERROR );
    }


    //
    // abstract methods that require implementing in child classes
    //

    /**
     * Returns the name of the database table which the class abstracts
     *
     * @access protected
     * @return string table name
     */
    function getTemplateTableName()
    {
        trigger_error( "TableAbstraction::getTemplateTableName() not implemented.", E_USER_ERROR );
    }    
    
    /**
     * Returns the name of the id field in database table
     *
     * @access protected
     * @return string ID field name
     */
    function getIDFieldName()
    {
        trigger_error( "TableAbstraction::getIDFieldName() not implemented.", E_USER_ERROR );
    }


    /**
     * Takes values from database record and applies those to object attributes
     *
     * @access protected
     * @param array $record a row retrieved from database
     */
    function readFromDatabaseRecord( $record )
    {
        trigger_error( "TableAbstraction::readFromDatabaseRecord() not implemented.", E_USER_ERROR );
    }


    /**
     * Reads values from object attributes into array of fields and returns it
     *
     * @access protected
     * @return array a row to be written in database
     */
    function writeToDatabaseRecord()
    {
        trigger_error( "TableAbstraction::writeToDatabaseRecord() not implemented.", E_USER_ERROR );
    }


}



?>
