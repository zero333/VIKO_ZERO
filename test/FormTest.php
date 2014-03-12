<?php

require_once 'Form.php';
require_once 'PHPUnit.php';

class FormTest extends PHPUnit_TestCase
{
    var $form;

    // constructor of the test suite
    function FormTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->form = new Form();
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->form );
    }

    function testSetGetID() {
        $expected = 128;
        $this->form->setID( $expected );
        $result = $this->form->getID();
        $this->assertEquals( $expected, $result );
    }

    function testSetGetName() {
        $expected = "10th grade";
        $this->form->setName( $expected );
        $result = $this->form->getName();
        $this->assertEquals( $expected, $result );
    }

    function testSetGetSchool() {
        $expected = new School();
        $expected->setID(0xCAFEBABE);
        $expected->setName("University of California");

        $this->form->setSchool( $expected );
        $result = $this->form->getSchool();
        $this->assertTrue( $expected === $result );
    }

    function testGetName() {
        $db = DBInstance::get();
        $id = 9999999;
        $school_id = 7777777;
        $form_name = '10th grade';
        $db->query(
            "INSERT INTO Forms (form_id, form_name, school_id) VALUES (?, ?, ?)",
            array($id, $form_name, $school_id)
        );

        $this->form->setID( $id );
        $this->assertEquals( $form_name, $this->form->getName() );

        $db->query("DELETE FROM Forms WHERE form_id = ? ", $id);
    }

    function testGetUnexistingName() {
        $this->form->setID( 9999999 );
        $this->assertTrue( $this->form->getName() === false);
    }

    function testGetHTMLName() {
        $this->form->setName( '<i>Unique\'s</i> "Form & Storm"' );
        $this->assertEquals( '&lt;i&gt;Unique&#039;s&lt;/i&gt; &quot;Form &amp; Storm&quot;', $this->form->getHTMLName() );
    }

    function testGetSchool() {
        $db = DBInstance::get();
        $id = 9999999;
        $school_id = 7777777;
        $form_name = '10th grade';
        $db->query(
            "INSERT INTO Forms (form_id, form_name, school_id) VALUES (?, ?, ?)",
            array($id, $form_name, $school_id)
        );

        $this->form->setID( $id );
        $resulting_school = $this->form->getSchool();
        if ( get_class($resulting_school) == "School" ) {
            $this->assertEquals( $school_id, $resulting_school->getID() );
        }
        else {
            $this->assertTrue( false );
        }

        $db->query("DELETE FROM Forms WHERE form_id = ? ", $id);
    }

    function testGetUnexistingSchool() {
        $this->form->setID( 9999999 );
        $this->assertTrue( $this->form->getSchool() === false );
    }

    function testSaveAsNew() {
        $db = DBInstance::get();
        $form_name = '3rd A Grade';
        $school = new School();
        $school->setID(0xCAFEBABE);
        $school->setName("University of California");

        $this->form->setName( $form_name );
        $this->form->setSchool( $school );
        $this->form->save();
        $id = $this->form->getID();

        if ( $id > 0 ) {
            $result_name = $db->getOne("SELECT form_name FROM Forms WHERE form_id = ? ", $id);
            $this->assertEquals( $form_name, $result_name );

            $db->query("DELETE FROM Forms WHERE form_id = ? ", $id);
        }
        else {
            $this->assertTrue( false );
        }
    }

    function testSaveOverExisting() {
        $db = DBInstance::get();
        $id = 9999999;
        $form_name = '3rd B Grade';
        $school = new School();
        $school->setID(0xCAFEBABE);
        $school->setName("University of California");
        $db->query("INSERT INTO Forms (form_id, form_name, school_id) VALUES (?, ?, ?)",
            array( $id, $form_name, $school->getID() )
        );

        $this->form->setName( $form_name );
        $this->form->setSchool( $school );
        $result = $this->form->save();

        $this->assertTrue( $result === false );

        $db->query("DELETE FROM Forms WHERE form_id = ? ", $id);
    }

    function testSaveWithRename() {
        $db = DBInstance::get();
        $id = 9999999;
        $form_name = '3rd B Grade';
        $form_name_2 = 'Class 3B';
        $school = new School();
        $school->setID(0xCAFEBABE);
        $school->setName("University of California");
        $db->query("INSERT INTO Forms (form_id, form_name, school_id) VALUES (?, ?, ?)",
            array( $id, $form_name, $school->getID() )
        );

        $this->form->setID( $id );
        $this->form->setName( $form_name_2 );
        $this->form->setSchool( $school );
        $this->form->save();

        $result_name = $db->getOne("SELECT form_name FROM Forms WHERE form_id = ? ", $id);
        $this->assertEquals( $form_name_2, $result_name );

        $db->query("DELETE FROM Forms WHERE form_id = ? ", $id);
    }

    function testSimpleDelete() {
        $db = DBInstance::get();
        $id = 9999999;
        $form_name = '3rd B Grade';
        $db->query("INSERT INTO Forms (form_id, form_name, school_id) VALUES (?, ?, ?)",
            array( $id, $form_name, 0xCAFEBABE )
        );

        $this->form->setID( $id );
        $this->form->delete();

        $res = $db->query("SELECT form_name FROM Forms WHERE form_id = ? ", $id);
        $this->assertEquals( 0, $res->numRows() );

        if ( $res->numRows() != 0 ) {
            $db->query("DELETE FROM Forms WHERE form_id = ? ", $id);
        }

    }

    function testUnexistingDelete() {
        $db = DBInstance::get();
        $id = 9999999;

        $this->form->setID( $id );
        $result = $this->form->delete();

        $this->assertTrue( $result === false );
    }

    function testRecursiveDelete() {
        $db = DBInstance::get();
        $id = 9999999;
        $form_name = '3rd B Grade';
        $db->query("INSERT INTO Forms (form_id, form_name, school_id) VALUES (?, ?, ?)",
            array( $id, $form_name, 0xCAFEBABE )
        );
        $db->query("INSERT INTO Users (user_id, username, form_id, school_id) VALUES (?, ?, ?, ?)",
            array( 7777771, 'user7777771', $id, 0xCAFEBABE )
        );
        $db->query("INSERT INTO Users (user_id, username, form_id, school_id) VALUES (?, ?, ?, ?)",
            array( 7777772, 'user7777772', $id, 0xCAFEBABE )
        );
        $db->query("INSERT INTO Users (user_id, username, form_id, school_id) VALUES (?, ?, ?, ?)",
            array( 7777773, 'user7777773', $id, 0xCAFEBABE )
        );

        $this->form->setID( $id );
        $this->form->delete();

        $res = $db->query("SELECT form_name FROM Forms WHERE form_id = ? ", $id);
        $this->assertEquals( 0, $res->numRows() );

        $res = $db->query("SELECT username FROM Users WHERE form_id = ? ", $id);
        $this->assertEquals( 0, $res->numRows() );

        // clean up
        $db->query("DELETE FROM Forms WHERE form_id = ? ", $id);
    }


}

?>
