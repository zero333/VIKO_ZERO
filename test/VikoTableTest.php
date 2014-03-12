<?php

require_once 'VikoTable.php';
require_once 'PHPUnit.php';

class VikoTableTest extends PHPUnit_TestCase
{
    var $table;

    // constructor of the test suite
    function VikoTableTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->table = new VikoTable( "This is a small description" );
        $this->table->addRow( "a", "b" );
        $this->table->addRow( "a", "b" );
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->table );
    }

    function testOddAndEvenRows() {
        $html = $this->table->toHTML();

        $this->assertTrue( preg_match( '/odd/', $html ) );
        $this->assertTrue( preg_match( '/even/', $html ) );
    }

    function testDeleteCells() {
        $this->table->setColumnToDelete( 1 );
        $html = $this->table->toHTML();

        $this->assertTrue( preg_match( '/delete/', $html ) );
    }

    function testNumericCells() {
        $this->table->setColumnsToNumeric( array( 0, 1 ) );
        $html = $this->table->toHTML();

        $this->assertTrue( preg_match( '/nr/', $html ) );
    }

    function testSummary() {
        $html = $this->table->toHTML();

        $this->assertTrue( preg_match( '/This is a small description/', $html ) );
    }
}

?>
