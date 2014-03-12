<?php

/**
 * Contains utility functions for database
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
 * Database utility functions
 */
class DBUtils
{

    /**
     * Generates SQL 'ORDER BY' clause from array
     *
     * This function takes array containing column names
     * and combines them into an ORDER BY clause. The first
     * column in array will be the first sort criteria, an so on.
     *
     * Also, to achieve locilized ordering, a COLLATE modifier
     * with appropriate collation for the current interface language
     * will be appended to each column name. For example:
     *
     * <pre>
     * DBUtils::createOrderBy( array("firstname", "lastname") );
     * </pre>
     *
     * Every column name may optionally by array index, instead of value,
     * pointing to keyword "ASC" or "DESC" to achieve either ascending or
     * descending sort order. As ascening order is the default, only
     * DESC is really needed in practice. For example, to create order by
     * statement for sorting usernames in descending order:
     *
     * <pre>
     * DBUtils::createOrderBy( array("username"=>"DESC") );
     * </pre>
     *
     * If the language is set to estonian, the above will return:
     *
     * <pre>
     * ORDER BY username COLLATE utf8_estonian_ci DESC
     * </pre>
     *
     * If you do not want to add collate-statements into the ORDER BY clause,
     * add second parameter "NO COLLATE":
     *
     * <pre>
     * DBUtils::createOrderBy( array("username"=>"DESC"), 'NO COLLATE' );
     * </pre>
     *
     * @access public
     *
     * @param array $order_by list of columns to sort by.
     *
     * @param string $do_collate weather to add collate clause or not.
     *
     * @return string ORDER BY clause
     * @static
     */
    static function createOrderBy( $order_by, $do_collate='COLLATE' )
    {
        // get the collation
        $collate = "COLLATE " . Configuration::getCollation();

        $count = 0;
        $sql = "";

        // only create ORDER BY, if there are more than zero elements in array
        if ( count($order_by) > 0 ) {
            $sql = "ORDER BY ";
        }
        else {
            return "";
        }

        // iterate over all columns to be sorted by
        foreach ( $order_by as $column => $order ) {

            // place commas between column names
            if ( $count > 0 ) {
                $sql.= ", ";
            }
            $count++;

            // check if the sorting order was specified
            if ( strtoupper($order) == "ASC" || strtoupper($order) == "DESC" ) {
                // if order is specified, convert it to uppercase
                $order = strtoupper($order);
            }
            else {
                // if no order is specified, use ascending order
                $column = $order;
                $order = "ASC";
            }

            // append column to composed SQL clause
            if ( $do_collate == 'COLLATE' ) {
                $sql.= "$column $collate $order";
            }
            else {
                $sql.= "$column $order";
            }
        }
        return $sql;
    }

}



?>
