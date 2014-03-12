<?php

require_once 'School.php';
require_once 'PHPUnit.php';

class SchoolTest extends PHPUnit_TestCase
{
    var $school;

    // constructor of the test suite
    function SchoolTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->school = new School();
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->school );
    }

    function testSetGetID() {
        $expected = 128;
        $this->school->setID( $expected );
        $result = $this->school->getID();
        $this->assertTrue( $result == $expected );
    }

    function testSetGetName() {
        $expected = "University of California";
        $this->school->setName( $expected );
        $result = $this->school->getName();
        $this->assertTrue( $result == $expected );
    }

    function testGetName() {
        $db = DBInstance::get();
        $id = 9999999;
        $school_name = 'University of California';
        $db->query("INSERT INTO Schools (school_id, school_name) VALUES (?, ?)", array($id, $school_name) );

        $this->school->setID( $id );
        $this->assertTrue( $this->school->getName() == $school_name );

        $db->query("DELETE FROM Schools WHERE school_id = ? ", $id);
    }

    function testGetHTMLName() {
        $this->school->setName( '<i>Unique\'s</i> "School & Fool"' );
        $this->assertEquals( '&lt;i&gt;Unique&#039;s&lt;/i&gt; &quot;School &amp; Fool&quot;', $this->school->getHTMLName() );
    }

    function testGetUnexistingName() {
        $this->school->setID( 9999999 );
        $this->assertTrue( $this->school->getName() === false);
    }

    function testSaveAsNew() {
        $db = DBInstance::get();
        $school_name = 'University of California';
        $this->school->setName( $school_name );
        $this->school->save();
        $id = $this->school->getID();

        if ( $id > 0 ) {
            $result_name = $db->getOne("SELECT school_name FROM Schools WHERE school_id = ? ", $id);
            $this->assertTrue( $result_name == $school_name );

            $db->query("DELETE FROM Schools WHERE school_id = ? ", $id);
        }
        else {
            $this->assertTrue( false );
        }
    }

    function testSaveOverExisting() {
        $db = DBInstance::get();
        $id = 9999999;
        $school_name = 'University of California';
        $db->query("INSERT INTO Schools (school_id, school_name) VALUES (?, ?)", array($id, $school_name) );

        $this->school->setName( $school_name );
        $result = $this->school->save();

        $this->assertTrue( $result === false );

        $db->query("DELETE FROM Schools WHERE school_id = ? ", $id);
    }

    function testSaveWithRename() {
        $db = DBInstance::get();
        $id = 9999999;
        $school_name = 'University of California';
        $school_name_2 = 'MIT';
        $db->query("INSERT INTO Schools (school_id, school_name) VALUES (?, ?)", array($id, $school_name) );

        $this->school->setID( $id );
        $this->school->setName( $school_name_2 );
        $this->school->save();

        $result_name = $db->getOne("SELECT school_name FROM Schools WHERE school_id = ? ", $id);
        $this->assertTrue( $result_name == $school_name_2 );

        $db->query("DELETE FROM Schools WHERE school_id = ? ", $id);
    }

    function testSimpleDelete() {
        $db = DBInstance::get();
        $id = 9999999;
        $school_name = 'University of California';
        $db->query("INSERT INTO Schools (school_id, school_name) VALUES (?, ?)", array($id, $school_name) );

        $this->school->setID( $id );
        $this->school->delete();

        $res = $db->query("SELECT school_name FROM Schools WHERE school_id = ? ", $id);
        $this->assertTrue( $res->numRows() == 0 );

        if ( $res->numRows() != 0 ) {
            $db->query("DELETE FROM Schools WHERE school_id = ? ", $id);
        }

    }

    function testUnexistingDelete() {
        $db = DBInstance::get();
        $id = 9999999;

        $this->school->setID( $id );
        $result = $this->school->delete();

        $this->assertTrue( $result === false );
    }


    function testRecursiveDelete() {
        $db = DBInstance::get();

        // set up a school with users, courses etc
        $db->query("
            INSERT INTO Schools
                (school_id, school_name)
            VALUES
                (9999991, 'My School 123')
            ");
        $db->query("
            INSERT INTO Users
                (user_id, username, form_id, school_id, user_group)
            VALUES
                (7777771, 'usr771', 8888881, 9999991, 'STUDENT'),
                (7777772, 'usr772', 8888881, 9999991, 'STUDENT'),
                (7777773, 'usr773', 8888881, 9999991, 'STUDENT'),
                (7777774, 'usr774', 8888881, 9999991, 'STUDENT'),
                (7777775, 'usr775', NULL,    9999991, 'TEACHER')
            ");
        $db->query("
            INSERT INTO Forms
                (form_id, school_id, form_name)
            VALUES
                (8888881, 9999991, 'frm881'),
                (8888882, 9999991, 'frm882')
            ");
        $db->query("
            INSERT INTO Courses
                (course_id, teacher_id)
            VALUES
                (6666661, 7777775)
            ");
        $db->query("
            INSERT INTO Courses_Users
                (course_id, user_id)
            VALUES
                (6666661, 7777771),
                (6666661, 7777772),
                (6666661, 7777773)
            ");
        $db->query("
            INSERT INTO Topics
                (topic_id, course_id)
            VALUES
                (8888881, 6666661)
            ");
        $db->query("
            INSERT INTO Posts
                (post_id, topic_id, user_id, addtime)
            VALUES
                (9999991, 8888881, 7777771, '2006-05-04 13:23:27'),
                (9999992, 8888881, 7777772, '2006-05-04 13:23:28'),
                (9999993, 8888881, 7777773, '2006-05-04 13:23:29')
            ");

        // try deleting the school
        $this->school->setID( 9999991 );
        $result = $this->school->delete();

        // the successful delete must return true
        $this->assertTrue( $result );


        // check, that all the records needed, were deleted
        $cnt = $db->getOne( "SELECT COUNT(*) FROM Schools WHERE school_id=9999991" );
        $this->assertEquals( 0, $cnt );

        $cnt = $db->getOne( "SELECT COUNT(*) FROM Users WHERE school_id=9999991" );
        $this->assertEquals( 0, $cnt );

        $cnt = $db->getOne( "SELECT COUNT(*) FROM Forms WHERE school_id=9999991" );
        $this->assertEquals( 0, $cnt );

        $cnt = $db->getOne( "SELECT COUNT(*) FROM Courses WHERE teacher_id=7777775" );
        $this->assertEquals( 0, $cnt );

        $cnt = $db->getOne( "SELECT COUNT(*) FROM Courses_Users WHERE course_id=6666661" );
        $this->assertEquals( 0, $cnt );

        $cnt = $db->getOne( "SELECT COUNT(*) FROM Topics WHERE course_id=6666661" );
        $this->assertEquals( 0, $cnt );

        $cnt = $db->getOne( "SELECT COUNT(*) FROM Posts WHERE topic_id=8888881" );
        $this->assertEquals( 0, $cnt );

        // clean up in case the test failed
        $db->query( "DELETE FROM Schools WHERE school_id=9999991" );
        $db->query( "DELETE FROM Users WHERE school_id=9999991" );
        $db->query( "DELETE FROM Forms WHERE school_id=9999991" );
        $db->query( "DELETE FROM Courses WHERE teacher_id=7777775" );
        $db->query( "DELETE FROM Courses_Users WHERE course_id=6666661" );
        //$db->query( "DELETE FROM Topics WHERE course_id=6666661" );
        $db->query( "DELETE FROM Posts WHERE topic_id=8888881" );
    }


    function testGetForms() {
        $db = DBInstance::get();

        // we add 9 forms to one school
        $db->query("
            INSERT INTO Forms
                (form_id, form_name, school_id)
            VALUES
                (9999991, 'x9999991', 7777777),
                (9999992, 'x9999992', 7777777),
                (9999993, 'x9999993', 7777777),
                (9999994, 'x9999994', 7777777),
                (9999995, 'x9999995', 7777777),
                (9999996, 'x9999996', 7777777),
                (9999997, 'x9999997', 7777777),
                (9999998, 'x9999998', 7777777),
                (9999999, 'x9999999', 7777777)
            ");

        // assign this ID to the school
        $this->school->setID(7777777);

        // get the forms
        $forms = $this->school->getForms();

        // check, that we have retrieved the correct amount of forms,
        // and the ID-s of the forms are correct
        $this->AssertEquals(9, count($forms) );
        $this->AssertTrue(
            ( count($forms) == 9 ) AND
            ( $forms[0]->getID() == 9999991 ) AND
            ( $forms[1]->getID() == 9999992 ) AND
            ( $forms[2]->getID() == 9999993 ) AND
            ( $forms[7]->getID() == 9999998 ) AND
            ( $forms[8]->getID() == 9999999 )
        );

        // clean up the students
        $db->query("DELETE FROM Forms WHERE school_id=7777777");
    }

}

?>
