<?php

require_once 'Lesson.php';
require_once 'PHPUnit.php';

class LessonTest extends PHPUnit_TestCase
{
    var $lesson;

    // constructor of the test suite
    function LessonTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->lesson = new Lesson();
        $db = DBInstance::get();
        $db->query(
            "
            INSERT INTO Lessons
                (lesson_id, course_id, lesson_topic, lesson_date, hometask, hometask_date, marktype)
            VALUES
                (9999991, 7777771, 'First lesson',  '2006-07-08', 'Nothing', '2007-08-09', 'LESSON'),
                (9999992, 7777771, 'Second lesson', '2006-07-09', 'Nope',    '2007-08-10', 'LESSON'),
                (9999993, 7777771, 'Third lesson',  '2006-07-10', 'Nil',     '2007-08-11', 'LESSON'),
                (9999994, 7777771, 'Fourth lesson', '2006-07-11', 'Exam',    '2007-08-12', 'PRELIM'),
                (9999995, 7777772, 'First lesson',  '2006-07-12', 'Nothing', '2007-08-09', 'LESSON')
            "
        );
        $db->query(
            "
            INSERT INTO Marks
                (lesson_id, user_id, mark)
            VALUES
                (9999991, 7777771, '1'),
                (9999991, 7777772, '2'),
                (9999991, 7777773, '3'),
                (9999991, 7777774, '4'),
                (9999991, 7777775, '5')
            "
        );
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->lesson );

        $db = DBInstance::get();
        $db->query("DELETE FROM Lessons WHERE course_id = 7777771 OR course_id = 7777772");
        $db->query("DELETE FROM Marks WHERE lesson_id = 9999991");
    }

    function testGetSetID() {
        $expected = 9999991;
        $this->lesson->setID( $expected );
        $this->assertEquals( $expected, $this->lesson->getID() );
    }

    function testGetSetCourse() {
        $expected = new Course();
        $expected->setID(10);
        $this->lesson->setCourse( $expected );
        $result = $this->lesson->getCourse();
        $this->assertEquals( get_class($expected), get_class($result) );
        if ( get_class($expected) == get_class($result) ) {
            $this->assertEquals( 10, $result->getID() );
        }
    }

    function testGetCourse() {
        $expected = 7777771;
        $this->lesson->setID( 9999991 );
        $result = $this->lesson->getCourse();
        $this->assertEquals( 'Course', get_class($result) );
        if ( 'Course' == get_class($result) ) {
            $this->assertEquals( $expected, $result->getID() );
        }
    }

    function testGetSetTopic() {
        $expected = 'Biology';
        $this->lesson->setTopic( $expected );
        $this->assertEquals( $expected, $this->lesson->getTopic() );
    }

    function testGetTopic() {
        $expected = 'First lesson';
        $this->lesson->setID( 9999991 );
        $this->assertEquals( $expected, $this->lesson->getTopic() );
    }

    function testGetHTMLTopic() {
        $this->lesson->setTopic( '&<>"' );
        $this->assertRegExp( '/<p>&amp;&lt;&gt;&quot;<\/p>/', $this->lesson->getHTMLTopic() );
    }

    function testGetSetDate() {
        $expected = new Date('2001-06-08 00:00:00');
        $this->lesson->setDate( $expected );
        $result = $this->lesson->getDate();
        $this->assertEquals( get_class($expected), get_class($result) );
        if ( get_class($expected) == get_class($result) ) {
            $this->assertEquals( '2001-06-08 00:00:00', $result->getDate() );
        }
    }

    function testGetDate() {
        $expected = '2006-07-10 00:00:00';
        $this->lesson->setID( 9999993 );
        $result = $this->lesson->getDate();
        $this->assertEquals( 'Date', get_class($result) );
        if ( 'Date' == get_class($result) ) {
            $this->assertEquals( $expected, $result->getDate() );
        }
    }

    function testGetSetHometask() {
        $expected = 'Algae';
        $this->lesson->setHometask( $expected );
        $this->assertEquals( $expected, $this->lesson->getHometask() );
    }

    function testGetHometask() {
        $expected = 'Nothing';
        $this->lesson->setID( 9999995 );
        $this->assertEquals( $expected, $this->lesson->getHometask() );
    }

    function testGetHTMLHometask() {
        $this->lesson->setHometask( '&<>"' );
        $this->assertRegExp( '/<p>&amp;&lt;&gt;&quot;<\/p>/', $this->lesson->getHTMLHometask() );
    }

    function testGetSetHometaskDate() {
        $expected = new Date('2001-06-08 00:00:00');
        $this->lesson->setHometaskDate( $expected );
        $result = $this->lesson->getHometaskDate();
        $this->assertEquals( get_class($expected), get_class($result) );
        if ( get_class($expected) == get_class($result) ) {
            $this->assertEquals( '2001-06-08 00:00:00', $result->getDate() );
        }
    }

    function testGetHometaskDate() {
        $expected = '2007-08-11 00:00:00';
        $this->lesson->setID( 9999993 );
        $result = $this->lesson->getHometaskDate();
        $this->assertEquals( 'Date', get_class($result) );
        if ( 'Date' == get_class($result) ) {
            $this->assertEquals( $expected, $result->getDate() );
        }
    }

    function testGetSetMarktype() {
        $expected = 'PRELIM';
        $this->lesson->setMarktype( $expected );
        $this->assertEquals( $expected, $this->lesson->getMarktype() );
    }

    function testGetMarktype() {
        $expected = 'PRELIM';
        $this->lesson->setID( 9999994 );
        $this->assertEquals( $expected, $this->lesson->getMarktype() );
    }


    function testSaveInsert() {
        $course = new Course();
        $course->setID(7777778);
        $this->lesson->setCourse($course);
        $this->lesson->setTopic('Biology');
        $date = new Date('1981-07-10 00:00:00');
        $this->lesson->setDate( $date );
        $this->lesson->setHometask('Animals in zoo');
        $ht_date = new Date('1981-07-11 00:00:00');
        $this->lesson->setHometaskDate( $ht_date );
        $this->lesson->setMarkType('PRELIM');

        $this->lesson->save();

        $id = $this->lesson->getID();

        $this->assertTrue( isset($id) );
        if ( isset($id) ) {
            $db = DBInstance::get();
            $row = $db->getRow("SELECT * FROM Lessons WHERE lesson_id=?", array($id) );

            $this->assertEquals(7777778, $row['course_id']);
            $this->assertEquals('Biology', $row['lesson_topic']);
            $this->assertEquals('1981-07-10', $row['lesson_date']);
            $this->assertEquals('Animals in zoo', $row['hometask']);
            $this->assertEquals('1981-07-11', $row['hometask_date']);
            $this->assertEquals('PRELIM', $row['marktype']);

            $db->query("DELETE FROM Lessons WHERE lesson_id=?", array($id));
        }
    }


    function testSaveUpdate() {
        $id = 9999991;
        $this->lesson->setID($id);
        $course = new Course();
        $course->setID(7777778);
        $this->lesson->setCourse($course);
        $this->lesson->setTopic('Biology');
        $date = new Date('1981-07-10 00:00:00');
        $this->lesson->setDate( $date );
        $this->lesson->setHometask('Animals in zoo');
        $ht_date = new Date('1981-07-11 00:00:00');
        $this->lesson->setHometaskDate( $ht_date );
        $this->lesson->setMarkType('PRELIM');

        $this->lesson->save();

        $db = DBInstance::get();
        $row = $db->getRow("SELECT * FROM Lessons WHERE lesson_id=?", array($id) );

        $this->assertEquals(7777778, $row['course_id']);
        $this->assertEquals('Biology', $row['lesson_topic']);
        $this->assertEquals('1981-07-10', $row['lesson_date']);
        $this->assertEquals('Animals in zoo', $row['hometask']);
        $this->assertEquals('1981-07-11', $row['hometask_date']);
        $this->assertEquals('PRELIM', $row['marktype']);
    }


    function testSimpleDelete() {
        $id = 9999991;
        $this->lesson->setID($id);

        $result = $this->lesson->delete();
        $this->assertTrue( $result === true);

        $db = DBInstance::get();

        $row = $db->getRow("SELECT * FROM Lessons WHERE lesson_id=?", array($id) );

        $this->assertFalse( isset($row) );
    }


    function testUnexistingDelete() {
        $id = 9999999;
        $this->lesson->setID($id);

        $result = $this->lesson->delete();

        $this->assertTrue( $result === false );
    }


    function testRecursiveDelete() {
        $id = 9999991;
        $this->lesson->setID($id);

        $result = $this->lesson->delete();
        $this->assertTrue( $result === true);

        $db = DBInstance::get();

        $cnt = $db->getOne("SELECT COUNT(*) FROM Marks WHERE lesson_id=?", array($id) );

        $this->assertEquals( 0, $cnt );
    }



}

?>
