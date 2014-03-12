<?php

/**
 * Contains the ContentTable class
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
 * This class simplifies the usage of PEAR class HTML_Table
 */
require_once 'HTML/Table.php';

/**
 * This class uses HTML class for creating abbrevation elements
 */
require_once 'HTML.php';

/**
 * Footer type EMPTY
 */
define('CONTENT_TABLE_EMPTY', 0);

/**
 * Footer type SUM
 */
define('CONTENT_TABLE_SUM', 1);

/**
 * Cell type NORMAL
 */
define('CONTENT_TABLE_NORMAL', 0);

/**
 * Cell type NUMERIC
 */
define('CONTENT_TABLE_NUMERIC', 1);

/**
 * Cell type DELETE
 */
define('CONTENT_TABLE_FILESIZE', 2);

/**
 * Cell type DELETE
 */
define('CONTENT_TABLE_DELETE', 3);

/**
 * Manages creation of HTML data tables for VIKO environment
 */
class ContentTable
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
    var $_thead;

    /**
     * Reference to the table body object
     *
     * @access private
     */
    var $_tbody;

    /**
     * Reference to the table footer object
     *
     * @access private
     */
    var $_tfoot;

    /**
     * The schema (columns description) of the table
     *
     * @access private
     */
    var $_schema;

    /**
     * The content of the table (list of rows)
     *
     * @access private
     */
    var $_data;

    /**
     * Constructs new ContentTable instance
     *
     * The $schema must be an array consisting of column name
     * and column description pairs. Column name is simply a unique
     * value identifying the column. Column description is an
     * array consisting of attributes, that describe that column.
     *
     * In each description-array, the "title" attribute is required,
     * all others are optional. Here are all the possible attributes:
     *
     * <ul>
     * <li>title - defines the text in the header of the column.</li>
     * <li>type - defines the visual style of cells in that column.
     *     Possible values:
     *     <ul>
     *     <li>CONTENT_TABLE_NORMAL - the default.
     *     <li>CONTENT_TABLE_NUMERIC - results in appropriate
     *         formatting for numeric values, e.g. aligned right.</li>
     *     <li>CONTENT_TABLE_FILESIZE - like previous, but in numbers
     *         are expected to represent filesize in bytes. These
     *         byte-filesizes are converted automatically to more human
     *         readable format e.g. 1024 becomes 1kB and so on.</li>
     *     <li>CONTENT_TABLE_DELETE - assuming the all the values in
     *         column are URI-s, appropriate delete-links are created,
     *         that consist of "X" in content and the value of from
     *         the first column of this table included to title string:
     *         "delete %s".</li>
     *     <li>Anything else is taken as a literal CSS class name.</li>
     *     </ul>
     * </li>
     * <li>footer - defines the contents of the footer of that column.
     *     Possible values:
     *     <ul>
     *     <li>CONTENT_TABLE_EMPTY - the default. If all columns
     *         have empty footer, then the footer is not shown at all.</li>
     *     <li>CONTENT_TABLE_SUM - the value in footer is the sum of all
     *         values in this column. Only applicable to numeric data.</li>
     *     <li>Anything else is taken as a literal string value and
     *         placed into the footer as is.</li>
     *     </ul>
     * </li>
     * </ul>
     *
     * <pre>
     * $schema = array(
     *     "name" => array(
     *         "title" => _("School name"),
     *         "footer" => _("Total"),
     *     ),
     *     "courses" => array(
     *         "title" => _("Nr of Courses"),
     *         "type" => CONTENT_TABLE_NUMERIC,
     *         "footer" => CONTENT_TABLE_SUM,
     *     ),
     *     "filesize" => array(
     *         "title" => _("Size of files"),
     *         "type" => CONTENT_TABLE_NUMERIC,
     *         "footer" => CONTENT_TABLE_FILESIZE,
     *     ),
     *     "delete" => array(
     *         "title" => _("Delete school"),
     *         "type" => CONTENT_TABLE_DELETE,
     *     ),
     * );
     * </pre>
     *
     * @access public
     * @param array $schema an array describing the columns in this table
     */
    function ContentTable( $schema )
    {
        $this->_schema = $schema;

        // initialize the encapsulated table object
        $this->_table = new HTML_Table();
        $this->_thead = $this->_table->getHeader();
        $this->_tbody = $this->_table->getBody();
        $this->_tfoot = $this->_table->getFooter();
    }

    /**
     * Loads the table with data
     *
     * $data must contain two-dimensional array with the following structure:
     *
     * <pre>
     * $data = array(
     *     array("first column content", "second column content", ...),  // first row
     *     array("first column content", "second column content", ...),  // second row
     *     array("first column content", "second column content", ...),  // third row
     *     ...
     * )
     * </pre>
     *
     * Alternatively the fields in a row might also be given explicit
     * association with columns by giving the column name as an index.
     * In that way, the ordering of fields doesn't matter and empty
     * fields may just be skipped, like in the following example:
     *
     * <pre>
     * $row = array(
     *     "first_column_name" => "first column content",
     *     "third_column_name" => "third column content",
     *     ...
     * )
     * </pre>
     *
     * @access public
     * @param array $data all the rows of a table
     */
    function PopulateWithData( $data )
    {
        $this->_data = $data;
    }

    /**
     * Converts the table into HTML
     *
     * @access public
     * @return string HTML table element
     */
    function toHTML()
    {
        $this->createTableHead();
        $this->createTableBody();
        $this->createTableFoot();

        $this->setTableAttributes();

        return $this->_table->toHTML();
    }

    function createTableHead()
    {
        // create table head
        $col_index=0;
        foreach ( $this->_schema as $col_name => $column ) {
            $this->_thead->setHeaderContents( 0, $col_index, $column['title'] );
            $col_index++;
        }
    }

    function createTableBody()
    {
        // fill table body with data
        for ( $row_index=0; $row_index < count($this->_data); $row_index++ ) {
            $row = $this->_data[$row_index];
            for ( $col_index=0; $col_index < count($row); $col_index++ ) {
                $cell = $this->createCellContents( $row[$col_index] );
                $this->_tbody->setCellContents( $row_index, $col_index, $cell );
            }
        }
    }

    function createCellContents($content)
    {
        if ( is_array($content) ) {
            return HTML::a( $content[0], $content[1] );
        }
        else {
            return $content;
        }
    }

    function createTextualCellContent($content)
    {
        if ( is_array($content) ) {
            return $content[1];
        }
        else {
            return $content;
        }
    }

    function createTableFoot()
    {
        $col_index=0;
        foreach ( $this->_schema as $col_name => $column ) {
            // when the footer type is defined, set the footer
            if ( isset($column['footer']) && $column['footer'] !== CONTENT_TABLE_EMPTY ) {
                $footer_cell = $this->createFooterCell( $column['footer'], $col_index );
                $this->_tfoot->setCellContents( 0, $col_index, $footer_cell );
            }

            $col_index++;
        }
    }

    /**
     * Creates a footer cell value for a particular type of footer
     */
    function createFooterCell($footer_type, $col_index)
    {
        if ( $footer_type === CONTENT_TABLE_SUM ) {
            return $this->columnSum($col_index);
        }
        else {
            // anything else is considered a literal text of footer
            return $footer_type;
        }
    }

    /**
     * Calculates the sum of values in a column with specified index.
     *
     * If the data doesn't consist entirely of numbers, the sum
     * will result in "not a number".
     */
    function columnSum($col_index)
    {
        $sum = 0;
        foreach ( $this->_data as $row ) {
            if ( !is_numeric($row[$col_index]) ) {
                return 'not a number';
            }
            $sum+= $row[$col_index];
        }
        return $sum;
    }

    /**
     * Sets all kinds of table attributes, like column types and
     * marking odd and even rows.
     */
    function setTableAttributes()
    {
        $this->createStripes($this->_tbody);

        // set attributes for columns
        $col_index=0;
        foreach ( $this->_schema as $column ) {
            if ( isset($column['type']) && $column['type'] !== CONTENT_TABLE_NORMAL ) {
                $this->setColumnType($column['type'], $col_index);
            }

            $col_index++;
        }
    }

    function setColumnType($column_type, $col_index)
    {
        if ( $column_type === CONTENT_TABLE_NUMERIC ) {
            // numeric type applies class "nr" only to the body and footer
            $attr = array('class' => 'nr');
            $this->_tfoot->setColAttributes($col_index, $attr);
            $this->_tbody->setColAttributes($col_index, $attr);
        }
        elseif ( $column_type === CONTENT_TABLE_FILESIZE ) {
            // filesize type applies class "nr" only to the body and footer
            $attr = array('class' => 'nr');
            $this->_tfoot->setColAttributes($col_index, $attr);
            $this->_tbody->setColAttributes($col_index, $attr);

            // the fields themselves also have to be converted
            for ( $i = 0; $i < count($this->_data); $i++ ) {
                $size = $this->_data[$i][$col_index];
                $formatted_size = $this->makeHumanReadableFilesize($size);
                $this->_tbody->setCellContents($i, $col_index, $formatted_size);
            }

            // and the footer too
            $size = $this->_tfoot->getCellContents(0, $col_index);
            $formatted_size = $this->makeHumanReadableFilesize($size);
            $this->_tfoot->setCellContents(0, $col_index, $formatted_size);
        }
        elseif ( $column_type === CONTENT_TABLE_DELETE ) {
            // delete type applies class 'delete' only to the body and header
            $attr = array('class' => 'delete');
            $this->_thead->setColAttributes($col_index, $attr);
            $this->_tbody->setColAttributes($col_index, $attr);

            // the header must be converted into abbrevation element
            $title = $this->_thead->getCellContents(0, $col_index);
            $abbr = HTML::abbr('X', $title);
            $this->_thead->setHeaderContents(0, $col_index, $abbr);

            // the data cells containing URI's have to be converted to links.
            for ( $i = 0; $i < count($this->_data); $i++ ) {
                // delete-cells contain URI's
                $uri = $this->_data[$i][$col_index];
                // contents of first column will be contained inside title
                $first_cell = $this->createTextualCellContent( $this->_data[$i][0] );
                $title = sprintf( _('delete %s'), $first_cell );
                $link = HTML::a($uri, 'X', $title);
                $this->_tbody->setCellContents($i, $col_index, $link);
            }
        }
        else {
            // anything else is considered a literal CSS class name
            $attr = array('class' => $column_type );
            // CSS class is applied to all parts of the table
            $this->_thead->setColAttributes($col_index, $attr);
            $this->_tfoot->setColAttributes($col_index, $attr);
            $this->_tbody->setColAttributes($col_index, $attr);
        }
    }

    function createStripes(&$table_section)
    {
        $odd_row = array('class' => 'odd');
        $even_row = array('class' => 'even');
        $table_section->altRowAttributes(0, $odd_row, $even_row, true);
    }

    /**
     * Returns the size of the file in human-readable format
     *
     * Mutch like the -h option in many UNIX utilities.
     * If the size is >= 1024 bytes, it is represented in KB,
     * if size is >= 1024 KB, it's given in MB.
     *
     * The unit B, KB, MB or GB is appended to the returned string.
     * If the value is a fraction, only one place after the comma is shown.
     *
     * @static
     * @param int $size file size in bytes
     * @return string file size
     */
    function makeHumanReadableFilesize( $size )
    {
        if ( $size < 1024 ) {
            return $size . " B";
        }
        elseif ( $size < 1024*1024 ) {
            return round($size/1024, 1) . " KB";
        }
        elseif ( $size < 1024*1024*1024 ) {
            return round($size/1024/1024, 1) . " MB";
        }
        else {
            return round($size/1024/1024/1024, 1) . " GB";
        }
    }
}



?>
