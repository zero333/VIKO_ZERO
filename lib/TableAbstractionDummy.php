<?php

/**
 * Contains the TableAbstractionDummy class
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
 * This class is a subclass of TableAbstraction
 */
require_once 'TableAbstraction.php';


/**
 * Dummy child class to enable testing of TableAbstraction class.
 *
 * This class may be used as an example or template when creating
 * real subclasses of TableAbstraction.
 */
class TableAbstractionDummy extends TableAbstraction
{
    /**
     * Sample of retrieving an attribute value
     */
    function getField()
    {
        return $this->getAttribute("_field");
    }

    /**
     * Sample of setting an attribute value
     */
    function setField( $field )
    {
        $this->_field = $field;
    }

    /**
     * Sample of reading from database record
     */
    function readFromDatabaseRecord( $record )
    {
        $this->_field = $record['field'];
    }

    /**
     * Sample of combining attributes into a database record
     */
    function writeToDatabaseRecord()
    {
        return array( "field" => $this->_field );
    }

    /**
     * Dummy table name
     */
    function getTableName()
    {
        return "DummyTable";
    }

    /**
     * Dummy ID field name
     */
    function getIDFieldName()
    {
        return "dummy_id";
    }
}



?>
