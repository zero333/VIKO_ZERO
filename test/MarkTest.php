<?php

require_once 'Mark.php';
require_once 'PHPUnit.php';

class MarkTest extends PHPUnit_TestCase
{
    var $mark;

    // constructor of the test suite
    function MarkTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->mark = new Mark();
        $db = DBInstance::get();
        $db->query(
            "
            INSERT INTO Marks
                (lesson_id, user_id, mark)
            VALUES
                (9999991, 7777771, '2'),
                (9999992, 7777771, '2'),
                (9999993, 7777771, '4'),
                (9999991, 7777772, '4')
            "
        );
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->lesson );

        $db = DBInstance::get();
        $db->query("DELETE FROM Marks WHERE user_id = 7777771 OR user_id = 7777772");
    }

    function testGetSetLesson() {
        $expected = new Lesson();
        $expected->setID(10);
        $this->mark->setLesson( $expected );
        $result = $this->mark->getLesson();
        $this->assertEquals( get_class($expected), get_class($result) );
        if ( get_class($expected) == get_class($result) ) {
            $this->assertEquals( 10, $result->getID() );
        }
    }

    function testGetSetStudent() {
        $expected = new User();
        $expected->setID(12);
        $this->mark->setStudent( $expected );
        $result = $this->mark->getStudent();
        $this->assertEquals( get_class($expected), get_class($result) );
        if ( get_class($expected) == get_class($result) ) {
            $this->assertEquals( 12, $result->getID() );
        }
    }

    function testGetSetMark() {
        $expected = 'Bad';
        $this->mark->setMark( $expected );
        $this->assertEquals( $expected, $this->mark->getMark() );

        $expected = '5';
        $this->mark->setMark( $expected );
        $this->assertEquals( $expected, $this->mark->getMark() );
    }

    function testGetMark() {
        $expected = '2';
        $lesson = new Lesson();
        $lesson->setID( 9999991 );
        $this->mark->setLesson( $lesson );
        $student = new User();
        $student->setID( 7777771 );
        $this->mark->setStudent( $student );
        $this->assertEquals( $expected, $this->mark->getMark() );
    }

    function testGetHTMLMark() {
        $this->mark->setMark( '<i>Unique\'s</i> "Form & Storm"' );
        $this->assertEquals( '&lt;i&gt;Unique&#039;s&lt;/i&gt; &quot;Form &amp; Storm&quot;', $this->mark->getHTMLMark() );
    }

    function testSaveInsert() {
        $lesson = new Lesson();
        $lesson->setID( 9999999 );
        $this->mark->setLesson( $lesson );
        $student = new User();
        $student->setID( 7777777 );
        $this->mark->setStudent( $student );
        $this->mark->setMark( 'Not bad' );

        $this->mark->save();

        $db = DBInstance::get();
        $mark = $db->getOne(
            "SELECT mark FROM Marks WHERE lesson_id=? AND user_id=?",
            array(9999999, 7777777)
        );

        $this->assertEquals('Not bad', $mark);

        $db->query(
            "DELETE FROM Marks WHERE lesson_id=? AND user_id=?",
            array(9999999, 7777777)
        );
    }


    function testSaveUpdate() {
        $lesson = new Lesson();
        $lesson->setID( 9999991 );
        $this->mark->setLesson( $lesson );
        $student = new User();
        $student->setID( 7777771 );
        $this->mark->setStudent( $student );
        $this->mark->setMark( 'Brilliant!' );

        $this->mark->save();

        $db = DBInstance::get();
        $mark = $db->getOne(
            "SELECT mark FROM Marks WHERE lesson_id=? AND user_id=?",
            array(9999991, 7777771)
        );

        $this->assertEquals('Brilliant!', $mark);

        $db->query(
            "DELETE FROM Marks WHERE lesson_id=? AND user_id=?",
            array(9999991, 7777771)
        );
    }


    function testSimpleDelete() {
        $lesson = new Lesson();
        $lesson->setID( 9999991 );
        $this->mark->setLesson( $lesson );
        $student = new User();
        $student->setID( 7777771 );
        $this->mark->setStudent( $student );

        $result = $this->mark->delete();
        $this->assertTrue( $result === true);

        $db = DBInstance::get();

        $row = $db->getRow(
            "SELECT * FROM Marks WHERE lesson_id=? AND user_id=?",
            array(9999991, 7777771)
        );

        $this->assertFalse( isset($row) );
    }


    function testUnexistingDelete() {
        $lesson = new Lesson();
        $lesson->setID( 9999991 );
        $this->mark->setLesson( $lesson );
        $student = new User();
        $student->setID( 7777777 );
        $this->mark->setStudent( $student );

        $result = $this->mark->delete();

        $this->assertTrue( $result === false );
    }

}

?>
