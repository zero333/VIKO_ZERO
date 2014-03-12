<?php

require_once 'UserFactory.php';
require_once 'PHPUnit.php';

class UserFactoryTest extends PHPUnit_TestCase
{
    // constructor of the test suite
    function UserFactoryTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $db = DBInstance::get();
        $db->query(
            "
            INSERT INTO Users
                (user_id, username, password, firstname, lastname, email, user_group, school_id, form_id)
            VALUES
                (9999991, 'john9999', SHA1('secret'), 'John', 'Smith', 'j@example.com', 'STUDENT', 7777777, 8888888),
                (9999992, 'bill9999', SHA1('MSMSMS'), 'Bill', 'Gates', 'bill@ms.com', 'TEACHER', 7777777, NULL),
                (9999993, 'linus999', SHA1('xxxxxx'), 'Linus', 'Torv', 'linus@linux.org', 'SCHOOLADMIN', 7777777, NULL),
                (9999994, 'god9999', SHA1('blessed'), 'The', 'One', 'god@heaven.org', 'ADMIN', 7777777, NULL)
            "
        );
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        $db = DBInstance::get();
        $db->query("DELETE FROM Users WHERE school_id = 7777777");
    }

    function testStudentLogin() {
        $user = UserFactory::createUserByLogin("john9999", 'secret');

        $this->assertEquals( "STUDENT", $user->getGroup() );
        if ( "STUDENT" == $user->getGroup() ) {
            $this->assertEquals( "John", $user->getFirstName() );
        }
    }

    function testTeacherLogin() {
        $user = UserFactory::createUserByLogin("bill9999", 'MSMSMS');

        $this->assertEquals( "TEACHER", $user->getGroup() );
        if ( "TEACHER" == $user->getGroup() ) {
            $this->assertEquals( "Bill", $user->getFirstName() );
        }
    }

    function testSchoolAdminLogin() {
        $user = UserFactory::createUserByLogin("linus999", 'xxxxxx');

        $this->assertEquals( "SCHOOLADMIN", $user->getGroup() );
        if ( "SCHOOLADMIN" == $user->getGroup() ) {
            $this->assertEquals( "Linus", $user->getFirstName() );
        }
    }

    function testAdminLogin() {
        $user = UserFactory::createUserByLogin("god9999", 'blessed');

        $this->assertEquals( "ADMIN", $user->getGroup() );
        if ( "ADMIN" == $user->getGroup() ) {
            $this->assertEquals( "The", $user->getFirstName() );
        }
    }

    function testFailedLogin() {
        $user = UserFactory::createUserByLogin("god9999", 'wrong');

        $this->assertTrue( $user === false );
    }

    function testCreateUserByID() {
        $user = UserFactory::createUserByID(9999994);

        $this->assertEquals( "ADMIN", $user->getGroup() );
        if ( "ADMIN" == $user->getGroup() ) {
            $this->assertEquals( "The", $user->getFirstName() );
        }
    }

    function testCreateUserByID_WrongID() {
        $user = UserFactory::createUserByID(9999995);

        $this->assertTrue( $user === false );
    }

}

?>
