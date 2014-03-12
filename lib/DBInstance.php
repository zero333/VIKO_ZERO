<?php

/**
 * Contains the DBInstance class
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
 * Database Abstraction Layer: {@link http://pear.php.net/package/DB PEAR DB}
 */
require_once 'PEAR/DB.php';

require_once 'Configuration.php';


/**
 * Provides simple access to DB class instance from any part of the program
 *
 * This class implements the 'singleton' design pattern.
 * From whatever part of the program you need access to the database,
 * simply call the static method get() of this class:
 *
 * <pre>
 * $db = DBInstance::get();
 *
 * // use as a normal DB instance
 * $db->query("INSERT INTO foo (bar, baz) VALUES ( 5, 'foobar' )");
 * </pre>
 */
class DBInstance
{
    /**
     * Returns the database instance
     *
     * @access public
     * @static
     * @return DB reference to database instance
     */
    static function get()
    {
        // our static variable, which won't lose he's value
        // after the function exits
        static $db_instance = null;

        if ( isset($db_instance) ) {
            // return already existing instance
            return $db_instance;
        }
        else {
            // instance does not exist, so create it
            $db_instance = DBInstance::_createInstance();
            return $db_instance;
        }
    }


    /**
     * Creates new instance of database abstraction layer
     *
     * @access private
     * @static
     */
    static function _createInstance()
    {
        // compose the Data Source Name (DSN)
        // read values from configuration
        $dsn = array(
            'phptype'  => Configuration::getDatabaseType(),
            'hostspec' => Configuration::getDatabaseHost(),
            'username' => Configuration::getDatabaseUsername(),
            'password' => Configuration::getDatabasePassword(),
            'database' => Configuration::getDatabaseName()
        );


        // compose DB interface options
        $options = array(
            // Expose errors
            // This might need to be set into zero in production release
            'debug' => 2,

            // The results should be freed automatically
            // when there are no more rows
            'autofree' => true,

            // DB_PORTABILITY_null_TO_EMPTY and DB_PORTABILITY_NUMROWS
            // have been left out, because those are Oracle-specific
            // and the first one (converting null's to empty strings)
            // is IMHO rather odd behaviour also.
            // I guess VIKO will never run on Oracle - so the support
            // for that database is admittedly left out.
            //
            // DB_PORTABILITY_RTRIM is not used because that would also
            // trim BLOB fields that might contain binary data - which
            // isn't good.
            'portability' =>
                DB_PORTABILITY_DELETE_COUNT |
                DB_PORTABILITY_ERRORS |
                DB_PORTABILITY_LOWERCASE
        );


        // connect to the database. If this fails: die with error message.
        $db = DB::connect($dsn, $options);
        if ( PEAR::isError($db) ) {
            die( $db->getMessage() );
        }

        // The results should be returned as associative list
        // instead of ordinary numbered array, because normally we want to
        // access fields by their names, not numbers.
        $db->setFetchMode(DB_FETCHMODE_ASSOC);

        // also, the results should be returned in UTF-8 encoding
        $enc_res = $db->query("SET NAMES 'utf8' COLLATE 'utf8_general_ci'");
        if ( PEAR::isError($enc_res) ) {
            die( $enc_res->getMessage() );
        }

        return $db;
    }


}


?>