<?php

require_once 'DBUtils.php';
require_once 'PHPUnit.php';

class DBUtilsTest extends PHPUnit_TestCase
{

    // constructor of the test suite
    function DBUtilsTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
    }

    // test the toString function
    function testCreateOrderBySimple() {
        $expected = "ORDER BY username COLLATE utf8_general_ci ASC";
        $result = DBUtils::createOrderBy( array("username") );
        $this->assertEquals( $expected, $result );
    }

    function testCreateOrderByAscLc() {
        $expected = "ORDER BY username COLLATE utf8_general_ci ASC";
        $result = DBUtils::createOrderBy( array("username"=>"asc") );
        $this->assertEquals( $expected, $result );
    }

    function testCreateOrderByDesc() {
        $expected = "ORDER BY first COLLATE utf8_general_ci DESC";
        $result = DBUtils::createOrderBy( array("first"=>"DESC") );
        $this->assertEquals( $expected, $result );
    }

    function testCreateOrderByTwoColumns() {
        $expected = "ORDER BY first COLLATE utf8_general_ci DESC, last COLLATE utf8_general_ci ASC";
        $result = DBUtils::createOrderBy( array("first"=>"DESC", "last") );
        $this->assertEquals( $expected, $result );
    }

}

?>
