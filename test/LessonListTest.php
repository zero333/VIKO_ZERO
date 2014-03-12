<?php

require_once 'LessonList.php';
require_once 'PHPUnit.php';

class LessonListTest extends PHPUnit_TestCase
{
    var $lesson;

    // constructor of the test suite
    function LessonListTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
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
            INSERT INTO Courses
                (course_id, course_name)
            VALUES
                (7777771, 'Biology'),
                (7777772, 'Math')
            "
        );
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        $db = DBInstance::get();
        $db->query("DELETE FROM Lessons WHERE course_id = 7777771 OR course_id = 7777772");
        $db->query("DELETE FROM Courses WHERE course_id = 7777771 OR course_id = 7777772");
    }


    function testGetFromCourse() {
        // assign this ID to the course
        $course = new Course();
        $course->setID(7777771);

        // get the lessons
        $lessons = LessonList::getFromCourse( $course );

        // check, that we have retrieved the correct amount of lessons
        $this->AssertEquals( 4, count($lessons) );
        // and the topics of the lessons are correct
        if ( count($lessons) == 4 ) {
            $this->AssertEquals( 'First lesson', $lessons[0]->getTopic() );
            $this->AssertEquals( 'Second lesson', $lessons[1]->getTopic() );
            $this->AssertEquals( 'Third lesson', $lessons[2]->getTopic() );
            $this->AssertEquals( 'Fourth lesson', $lessons[3]->getTopic() );
        }
    }


}

?>
