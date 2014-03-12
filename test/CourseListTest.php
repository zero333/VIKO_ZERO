<?php

require_once 'CourseList.php';
require_once 'PHPUnit.php';

class CourseListTest extends PHPUnit_TestCase
{
    // constructor of the test suite
    function CourseListTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        // we add 9 courses, associated with one teacher
        $db = DBInstance::get();
        $db->query("
            INSERT INTO Courses
                (course_id, course_name, course_subname, teacher_id, description, assessment, books, scheduled_time, classroom, consultation)
            VALUES
                ('9999991', 'Course 1', 'class A', '7777771', '', '', '', '', '', ''),
                ('9999992', 'Course 2', 'class A', '7777771', '', '', '', '', '', ''),
                ('9999993', 'Course 3', 'class B', '7777771', '', '', '', '', '', ''),
                ('9999994', 'Course 4', 'class B', '7777771', '', '', '', '', '', ''),
                ('9999995', 'Course 5', 'class B', '7777771', '', '', '', '', '', ''),
                ('9999996', 'Course 6', 'class C', '7777771', '', '', '', '', '', ''),
                ('9999997', 'Course 7', 'class C', '7777771', '', '', '', '', '', ''),
                ('9999998', 'Course 8', 'class C', '7777771', '', '', '', '', '', ''),
                ('9999999', 'Course 9', 'class A', '7777771', '', '', '', '', '', '')
            ");

        // now add 1 student and 1 teacher
        $db->query("
            INSERT INTO Users
                (user_id, username, user_group, form_id, firstname, lastname, school_id)
            VALUES
                ('8888881', 'x8888881', 'STUDENT', 7777777, 'John',  'Smith',  6666666),
                ('7777771', 'x7777771', 'TEACHER', NULL, 'Michael', 'Jackson',  6666666)
            ");

        // and associate it with 5 courses
        $db->query("
            INSERT INTO Courses_Users
                (course_id, user_id, last_view, total_views)
            VALUES
                ('9999991', '8888881', '', '0'),
                ('9999993', '8888881', '', '0'),
                ('9999995', '8888881', '', '0'),
                ('9999997', '8888881', '', '0'),
                ('9999999', '8888881', '', '0')
            ");

    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        // clean up the courses
        $db = DBInstance::get();
        $db->query("DELETE FROM Courses WHERE teacher_id=7777771");
        $db->query("DELETE FROM Users WHERE school_id=6666666");
        $db->query("DELETE FROM Courses_Users WHERE user_id=8888881");
    }


    function testGetFromStudent() {
        // assign this ID to the student
        $student = new User();
        $student->setID(8888881);

        $courses = CourseList::getFromStudent( $student );

        // check, that we have retrieved the correct amount of courses,
        // and the names of the courses are correct
        $this->AssertEquals( 5, count($courses) );
        if ( count($courses) == 5 ) {
            $this->AssertEquals( 'Course 1', $courses[0]->getName() );
            $this->AssertEquals( 'Course 3', $courses[1]->getName() );
            $this->AssertEquals( 'Course 5', $courses[2]->getName() );
            $this->AssertEquals( 'Course 7', $courses[3]->getName() );
            $this->AssertEquals( 'Course 9', $courses[4]->getName() );
        }
    }

    function testGetFromTeacher() {
        // assign this ID to the teacher
        $teacher = new User();
        $teacher->setID(7777771);

        $courses = CourseList::getFromTeacher( $teacher );

        // check, that we have retrieved the correct amount of courses,
        // and the names of the courses are correct
        $this->AssertEquals( 9, count($courses) );
        if ( count($courses) == 9 ) {
            $this->AssertEquals( 'Course 1', $courses[0]->getName() );
            $this->AssertEquals( 'Course 2', $courses[1]->getName() );
            $this->AssertEquals( 'Course 5', $courses[4]->getName() );
            $this->AssertEquals( 'Course 8', $courses[7]->getName() );
            $this->AssertEquals( 'Course 9', $courses[8]->getName() );
        }
    }

}

?>
