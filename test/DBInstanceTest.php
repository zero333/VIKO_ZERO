<?php

require_once 'DBInstance.php';
require_once 'PHPUnit.php';

class DBInstanceTest extends PHPUnit_TestCase
{

    // constructor of the test suite
    function DBInstanceTest($name) {
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
    function testGet() {
        $result = DBInstance::get();
        $this->assertTrue( get_class($result) == "DB_mysql" );
    }

}

?>
