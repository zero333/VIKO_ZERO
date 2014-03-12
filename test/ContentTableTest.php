<?php

require_once 'ContentTable.php';
require_once 'PHPUnit.php';

class ContentTableTest extends PHPUnit_TestCase
{
    var $simple_schema = array(
            "person" => array("title" => "Person name"),
            "age" => array("title" => "Age"),
        );

    var $simple_data = array(
            array("John",  10),
            array("Mary",  32),
            array("Alice", 18),
            array("Bob",   40),
            array("Romeo", 11),
        );

    var $simple_sum;

    function setUp() {
        $this->simple_sum = 10+32+18+40+11;
    }

    function testBasicTable() {
        $table = new ContentTable( $this->simple_schema );

        $table->populateWithData( $this->simple_data );

        $expected_layout = array(
            "thead" => array( array("Person name", "Age") ),
            "tbody" => $this->simple_data,
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    function testEmptyTable() {
        $table = new ContentTable( $this->simple_schema );

        $table->populateWithData( array() );

        $expected_layout = array(
            "thead" => array( array("Person name", "Age") ),
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    function testOneColumnTable() {
        $schema = array(
            "foo" => array("title" => "Foo"),
        );

        // if cell contents is an array, it means a link
        $data = array(
            array( "foo" ),
            array( "bar" ),
            array( "baz" ),
        );

        $table = new ContentTable( $schema );
        $table->populateWithData( $data );

        $expected_layout = array(
            "thead" => array(
                array("Foo")
            ),
            "tbody" => $data
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    var $simple_schema_with_sum = array(
            "person" => array("title" => "Person name"),
            "age" => array("title" => "Age", "footer" => CONTENT_TABLE_SUM),
        );

    function testSumFooter() {
        $table = new ContentTable( $this->simple_schema_with_sum );

        $table->populateWithData( $this->simple_data );

        $expected_layout = array(
            "thead" => array( array("Person name", "Age") ),
            "tfoot" => array( array("&nbsp;", $this->simple_sum) ),
            "tbody" => $this->simple_data,
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    function testSumFooterWithInvalidData() {
        $table = new ContentTable( $this->simple_schema_with_sum );

        $data = array(
            array("Juliet", 10),
            array("Romeo",  "foo"),
        );

        $table->populateWithData( $data );

        $expected_layout = array(
            "thead" => array( array("Person name", "Age") ),
            "tfoot" => array( array("&nbsp;", "not a number") ),
            "tbody" => $data,
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    var $simple_schema_with_text_footer = array(
            "person" => array("title" => "Person name", "footer" => "Total"),
            "age" => array("title" => "Age", "footer" => "1"),
        );

    function testTextFooter() {
        $table = new ContentTable( $this->simple_schema_with_text_footer );

        $table->populateWithData( $this->simple_data );

        $expected_layout = array(
            "thead" => array( array("Person name", "Age") ),
            "tfoot" => array( array("Total", "1") ),
            "tbody" => $this->simple_data,
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    var $simple_schema_with_empty = array(
            "person" => array("title" => "Person name", "footer" => CONTENT_TABLE_EMPTY),
            "age" => array("title" => "Age", "footer" => CONTENT_TABLE_EMPTY),
        );

    function testEmptyFooter() {
        $table = new ContentTable( $this->simple_schema_with_empty );

        $table->populateWithData( $this->simple_data );

        $expected_layout = array(
            "thead" => array( array("Person name", "Age") ),
            "tbody" => $this->simple_data,
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    var $simple_schema_with_numeric_sum = array(
            "person" => array("title" => "Person name"),
            "age" => array("title" => "Age",
                           "footer" => CONTENT_TABLE_SUM,
                           "type" => CONTENT_TABLE_NUMERIC,
                           ),
        );

    function testNumericCells() {
        $table = new ContentTable( $this->simple_schema_with_numeric_sum );

        $table->populateWithData( $this->simple_data );

        $expected_layout = array(
            "thead" => array( array("Person name", "Age") ),
            "tfoot" => array( array("&nbsp;", array($this->simple_sum, "nr") ) ),
            "tbody" => array(
                array("John",  array(10, "nr") ),
                array("Mary",  array(32, "nr") ),
                array("Alice", array(18, "nr") ),
                array("Bob",   array(40, "nr") ),
                array("Romeo", array(11, "nr") ),
            ),
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    function testDeleteCells() {
        $schema = array(
            "person" => array("title" => "Person name"),
            "delete" => array("title" => "Delete person", "type" => CONTENT_TABLE_DELETE),
        );

        $data = array(
            array("John",  "/delete-user/001" ),
            array("Mary",  "/delete-user/002" ),
            array("Alice", "/delete-user/003" ),
            array("Bob",   "/delete-user/004" ),
        );

        $table = new ContentTable( $schema );
        $table->populateWithData( $data );

        $expected_layout = array(
            "thead" => array(
                array("Person name",
                    array('<acronym title="Delete person">X</acronym>', 'delete')
                )
            ),
            "tbody" => array(
                array('John',  array('<a href="/delete-user/001" title="delete John">X</a>', 'delete') ),
                array('Mary',  array('<a href="/delete-user/002" title="delete Mary">X</a>', 'delete') ),
                array('Alice', array('<a href="/delete-user/003" title="delete Alice">X</a>', 'delete') ),
                array('Bob',   array('<a href="/delete-user/004" title="delete Bob">X</a>', 'delete') ),
            ),
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    function testLinkCells() {
        $schema = array(
            "person" => array("title" => "Person name"),
        );

        // if cell contents is an array, it means a link
        $data = array(
            array( array("/user/001", "John") ),
            array( array("/user/002", "Mary") ),
            array( array("/user/003", "Alice") ),
            array( array("/user/004", "Bob") ),
        );

        $table = new ContentTable( $schema );
        $table->populateWithData( $data );

        $expected_layout = array(
            "thead" => array(
                array("Person name")
            ),
            "tbody" => array(
                array( '<a href="/user/001">John</a>' ),
                array( '<a href="/user/002">Mary</a>' ),
                array( '<a href="/user/003">Alice</a>' ),
                array( '<a href="/user/004">Bob</a>' ),
            ),
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    function testLinkAndDeleteCells() {
        $schema = array(
            "person" => array("title" => "Person name"),
            "delete" => array("title" => "Delete person", "type" => CONTENT_TABLE_DELETE),
        );

        // if cell contents is an array, it means a link
        $data = array(
            array( array("/user/001", "John"), "/delete-user/001" ),
        );

        $table = new ContentTable( $schema );
        $table->populateWithData( $data );

        $expected_layout = array(
            "thead" => array(
                array("Person name", array('<acronym title="Delete person">X</acronym>', 'delete') )
            ),
            "tbody" => array(
                array( '<a href="/user/001">John</a>', array('<a href="/delete-user/001" title="delete John">X</a>', 'delete') ),
            ),
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }

    function testFilesizeType() {
        $schema = array(
            "person" => array("title" => "Person name"),
            "size" => array("title" => "Filesize",
                           "footer" => CONTENT_TABLE_SUM,
                           "type" => CONTENT_TABLE_FILESIZE,
                           ),
        );

        $data = array(
            array( "John",    0 ),
            array( "Alice",   1 ),
            array( "Bob",     8 ),
            array( "Fred",  247 ),
            array( "Sam",  1024 ),
            array( "Al",   2000 ),
            array( "Joe",  1024*1024*3+18 ),
            array( "Mc",   1024*1024*1024*125+1024*1024*900 ),
        );

        $table = new ContentTable( $schema );
        $table->populateWithData( $data );

        $expected_layout = array(
            "thead" => array( array("Person name", "Filesize") ),
            "tfoot" => array( array("&nbsp;", array("125.9 GB", "nr") ) ),
            "tbody" => array(
                array( "John",    array("0 B", "nr") ),
                array( "Alice",   array("1 B", "nr") ),
                array( "Bob",     array("8 B", "nr") ),
                array( "Fred",    array("247 B", "nr") ),
                array( "Sam",     array("1 KB", "nr") ),
                array( "Al",      array("2 KB", "nr") ),
                array( "Joe",     array("3 MB", "nr") ),
                array( "Mc",      array("125.9 GB", "nr") ),
            ),
        );

        $html = $table->toHTML();

        $this->assertTableEquals( $expected_layout, $html );
    }




    ///////////////////////////////////////////////////////////////////////////
    //
    //             Utility methods for comparing tables
    //
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Compares the expected table layout description
     * with actual string of HTML.
     */
    function assertTableEquals($expected_layout, $actual_html) {
        $expected_lines = $this->createExpectedHtmlLines( $expected_layout );
        $actual_lines = $this->createActualHtmlLines( $actual_html );
//print_r($expected_lines);print_r($actual_lines);
        // check, that the line counts match
        $this->assertEquals( count($expected_lines), count($actual_lines), "Different number of lines" );

        // check all the lines one-by-one
        for ( $line=0; $line<count($expected_lines); $line++) {
            $this->assertEquals( $expected_lines[$line], $actual_lines[$line] );
        }
    }

    function createActualHtmlLines($actual_html) {
        // remove newline from the end of table HTML
        $actual_html = preg_replace('/\n$/', '', $actual_html);

        // remove all tabs from the HTML (they are only needed for nicer formatting
        $actual_html = str_replace( "\t", "", $actual_html);

        // convert actual HTML into array of lines
        return explode("\n", $actual_html);
    }

    /**
     * Creates the expected HTML output from an abstract table description
     *
     * The output is an array of lines of HTML
     *
     * <pre>
     * $table = array(
     *     "thead" => array(
     *         array("foo", "bar", "baz"),
     *     ),
     *     "tbody" => array(
     *         array("cell1", "cell2", "cell3"),
     *         array("cell1", "cell2", "cell3"),
     *         array("cell1", "cell2", "cell3"),
     *         ...
     *     ),
     * );
     * </pre>
     */
    function createExpectedHtmlLines($table) {
        $start = array("<table>");
        $thead = $this->createExpectedHtmlRows( $table, "thead", "th", false );
        $tfoot = $this->createExpectedHtmlRows( $table, "tfoot", "td", false );
        $tbody = $this->createExpectedHtmlRows( $table, "tbody", "td", true );
        $end = array("</table>");

        return array_merge($start, $thead, $tfoot, $tbody, $end);
    }

    function createExpectedHtmlRows( $table, $section, $td="td", $odd_even=true) {
        // ignore empty sections, e.g. missing footer
        if ( !isset($table[$section]) ) {
            return array();
        }

        $rows = array();
        $rows[] = "<$section>";
        $odd_even_counter=0;
        foreach ( $table[$section] as $row ) {
            $odd_even_counter++;
            $class = $this->createOddEvenClass($odd_even, $odd_even_counter);

            $rows[] = "<tr$class>";
            $rows = array_merge( $rows, $this->createExpectedHtmlCells($row, $td) );
            $rows[] = "</tr>";
        }
        $rows[] = "</$section>";

        return $rows;
    }

    function createExpectedHtmlCells( $row, $td="td" ) {
        $rows = array();
        foreach ( $row as $cell ) {
            // there can be two types of cells:
            // simple - only the text of cell content
            // complex - array( cell_content, class_names )
            if ( is_array($cell) ) {
                $rows[] = "<$td class=\"$cell[1]\">$cell[0]</$td>";
            }
            else {
                $rows[] = "<$td>$cell</$td>";
            }
        }
        return $rows;
    }

    function createOddEvenClass( $odd_even, $odd_even_counter ) {
        // do nothing, when odd and even rows need no distinction
        if (!$odd_even) {
            return "";
        }

        $odd_even_class = ($odd_even_counter % 2 == 0) ? "even" : "odd";
        return " class=\"$odd_even_class\"";
    }
}

?>
