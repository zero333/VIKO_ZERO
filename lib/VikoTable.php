<?php

/**
 * Contains the VikoTable class
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
 * This class encapsulates PEAR class HTML_Table
 */
require_once 'Pear/HTML/Table.php';

/**
 * Only table body is affected
 */
define("TABLE_BODY_ONLY", 0);

/**
 * Only table footer is affected
 */
define("TABLE_FOOTER_ONLY", 1);

/**
 * Both table body and footer are affected
 */
define("TABLE_BODY_AND_FOOTER", 2);


/**
 * Manages creation of HTML data tables for VIKO environment
 *
 * The class encapsulates an HTML_Table object to which it adds
 * some viko-specific attributes. For example all the even rows
 * in table will have class="even" and odd rows class="odd".
 *
 * For example the creation of a table to display scores for
 * athlets might be done light this:
 *
 * <pre>
 * $table = new VikoTable(
 *     "100 finalists ordered by score starting with the highest."
 * );
 *
 * $table->addHeader( "Name", "Score" );
 *
 * $total_score=0;
 * foreach( $spotsmen as $man ) {
 *     $table->addRow( $man['name'], $man['score'] );
 *     $total_score += $man['score'];
 * }
 *
 * $table->addFooter( "Total", $total_score );
 *
 * echo $table->toHTML();
 * </pre>
 *
 */
class VikoTable
{
    /**
     * Reference to the table object
     *
     * @access private
     */
    var $_table;

    /**
     * Reference to the table header object
     *
     * @access private
     */
    var $_header;

    /**
     * Reference to the table body object
     *
     * @access private
     */
    var $_body;

    /**
     * Reference to the table footer object
     *
     * @access private
     */
    var $_footer;


    /**
     * Constructs new VikoTable instance
     *
     * You are not required to specify summary for the table, but for more
     * complex tables with many columns and rows this may prove helpful.
     * The summary should describe the organization of the table and
     * how the table should be used. See the class description for an example.
     *
     * @access public
     * @param string $summary the value for the summary attribute of table
     */
    function VikoTable( $summary = "" )
    {
        // initialize the encapsulated table object
        $this->_table = new HTML_Table( array("summary" => $summary) );
        $this->_header = $this->_table->getHeader();
        $this->_body = $this->_table->getBody();
        $this->_footer = $this->_table->getFooter();
    }


    /**
     * Adds new row to the body of the table
     *
     * The function takes variable number of arguments,
     * each representing the contents of one cell.
     *
     * You may also pass the cell contents as an array -
     * then the function takes only one argument - this array.
     * When passing an array, you may after the array pass also a string,
     * that will be used as a classname for the added row.
     *
     * @access public
     */
    function addRow()
    {
        if ( func_num_args() == 1 && is_array( func_get_arg(0) ) ) {
            $this->_body->addRow( func_get_arg(0) );
        }
        elseif ( func_num_args() == 2 && is_array( func_get_arg(0) ) ) {
            $classname = func_get_arg(1);
            $this->_body->addRow(
                func_get_arg(0),
                array( 'class' => $classname ),
                'td',
                true
            );
        }
        else {
            $this->_body->addRow( func_get_args() );
        }
    }


    /**
     * Adds new row to the header of the table
     *
     * The function takes variable number of arguments,
     * each representing the contents of one cell.
     *
     * You may also pass the cell contents as an array -
     * then the function takes only one argument - this array.
     *
     * @access public
     */
    function addHeader()
    {
        if ( func_num_args() == 1 && is_array( func_get_arg(0) ) ) {
            $this->_header->addRow( func_get_arg(0), null, 'th' );
        }
        else {
            $this->_header->addRow( func_get_args(), null, 'th' );
        }
    }


    /**
     * Adds new row to the footer of the table
     *
     * The function takes variable number of arguments,
     * each representing the contents of one cell.
     *
     * You may also pass the cell contents as an array -
     * then the function takes only one argument - this array.
     *
     * @access public
     */
    function addFooter()
    {
        if ( func_num_args() == 1 && is_array( func_get_arg(0) ) ) {
            $this->_footer->addRow( func_get_arg(0) );
        }
        else {
            $this->_footer->addRow( func_get_args() );
        }
    }




    /**
     * Defines the columns to be have specified value of class attribute
     *
     * To the table cells that belong to the columns with indexes listed
     * in $column_indexes will be assigned class="$class". But that only
     * affects the cells in table body and/or footer sections.
     *
     * $table_part attribute specifies the affected part of the table:
     *
     * <ul>
     * <li>TABLE_BODY_ONLY -
     * This is the default, only the cells in tbody section are modified.</li>
     * <li>TABLE_FOOTER_ONLY -
     * Only the cells in tfoot section are modified.</li>
     * <li>TABLE_BODY_AND_FOOTER -
     * Both the cells of tbody and tfoot are modified.</li>
     * </ul>
     *
     * @access public
     * @param string $class classname to assign
     * @param array $column_indexes list of integer column indexes
     * @param int $table_part which parts of the table should be affected
     */
    function setColumnsToClass( $class, $column_indexes, $table_part=TABLE_BODY_ONLY )
    {
        foreach ( $column_indexes as $index ) {
            if ( $table_part == TABLE_BODY_AND_FOOTER ) {
                $this->_body->setColAttributes( $index, array("class"=>$class) );
                $this->_footer->setColAttributes( $index, array("class"=>$class) );
            }
            elseif ( $table_part == TABLE_BODY_ONLY ) {
                $this->_body->setColAttributes( $index, array("class"=>$class) );
            }
            elseif ( $table_part == TABLE_FOOTER_ONLY ) {
                $this->_footer->setColAttributes( $index, array("class"=>$class) );
            }
            else {
                trigger_error("Invalid value '$table_part' for table_part.", E_USER_ERROR);
            }
        }
    }


    /**
     * Defines the columns to be numeric by assigning class="nr" to them
     *
     * This is a shorthand for setColumnsToClass() where the value of $class
     * parameter is "nr".
     *
     * @access public
     * @param array $column_indexes list of integer column indexes
     * @param int $table_part which parts of the table should be affected
     */
    function setColumnsToNumeric( $column_indexes, $table_part=TABLE_BODY_ONLY )
    {
        $this->setColumnsToClass( "nr", $column_indexes, $table_part );
    }


    /**
     * Defines the column to be for deleting the row
     *
     * Assigns class="delete" to all the cells in specified column.
     * The classes are only assigned to table body.
     *
     * @access public
     * @param int $column_index
     */
    function setColumnToDelete( $column_index )
    {
        $this->_body->setColAttributes( $column_index, array("class"=>"delete") );
    }


    /**
     * Returns the table in HTML format
     *
     * @access public
     * @return string HTML table element
     */
    function toHTML()
    {
        // add odd and even classes to the rows in table body
        // this is used in the stylesheet to make stripes
        for ( $i = 0; $i < $this->_body->getRowCount(); $i++ ) {

            // determine weather the row is odd or even
            $odd_or_even = ( ($i+1) % 2 == 0 ) ? 'even' : 'odd';

            // get the current attributes
            $attributes = $this->_body->getRowAttributes( $i );

            // add class 'odd' or 'even' to attribute 'class' if that exists
            // or just create attribute class with appropriate value
            if ( isset( $attributes ) && isset( $attributes['class'] ) ) {
                $attributes['class'] .= ' ' . $odd_or_even;
            }
            else {
                $attributes['class'] = $odd_or_even;
            }

            // save the attribute values
            $this->_body->setRowAttributes( $i, $attributes, true );
        }

        return $this->_table->toHTML();
    }
}



?>
