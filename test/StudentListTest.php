<?php

require_once 'StudentList.php';
require_once 'PHPUnit.php';

class StudentListTest extends PHPUnit_TestCase
{
    var $list;

    // constructor of the test suite
    function StudentListTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->list = new StudentList();

        // we add 9 students to one class and 3 to another class in the same school,
        // plus a test user in another school
        $db = DBInstance::get();
        $db->query("
            INSERT INTO Users
                (user_id, username, user_group, form_id, firstname, lastname, school_id)
            VALUES
                (9999991, 'x9999991', 'STUDENT', 7777777, 'John2', 'Smith9', 6666666),
                (9999992, 'x9999992', 'STUDENT', 7777777, 'John2', 'Smith8', 6666666),
                (9999993, 'x9999993', 'STUDENT', 7777777, 'John2', 'Smith7', 6666666),
                (9999994, 'x9999994', 'STUDENT', 7777777, 'John3', 'Smith6', 6666666),
                (9999995, 'x9999995', 'STUDENT', 7777777, 'John3', 'Smith5', 6666666),
                (9999996, 'x9999996', 'STUDENT', 7777777, 'John3', 'Smith4', 6666666),
                (9999997, 'x9999997', 'STUDENT', 7777777, 'John1', 'Smith3', 6666666),
                (9999998, 'x9999998', 'STUDENT', 7777777, 'John1', 'Smith2', 6666666),
                (9999999, 'x9999999', 'STUDENT', 7777777, 'John1', 'Smith1', 6666666),
                (9111000, 'x9111000', 'STUDENT', 8888888,   'Bob', 'Silent', 6666666),
                (9111001, 'x9111001', 'STUDENT', 8888888,   'Jey', 'noname', 6666666),
                (9111222, 'x9111222', 'STUDENT', 8888888, 'Carol', 'Coward', 6666666),
                (9000000, 'x9000000', 'STUDENT', 8888888,  'test',   'test', 7777777)
            ");

        $db->query("
            INSERT INTO Courses_Users
                (course_id, user_id, last_view, total_views)
            VALUES
                (7777777, 9999991, '2001-01-01', '1'),
                (7777777, 9999992, '2002-01-01', '1'),
                (7777777, 9999993, '2003-01-01', '1'),
                (7777777, 9999994, '2004-01-01', '2'),
                (7777777, 9999995, '2005-01-01', '2'),
                (7777777, 9111222, '2006-01-01', '2')
            ");
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->list );

        // clean up the students
        $db = DBInstance::get();
        $db->query("DELETE FROM Users WHERE form_id=7777777 OR form_id=8888888");
        $db->query("DELETE FROM Courses_Users WHERE course_id=7777777");
    }


    function testGetFromForm() {
        // assign this ID to the form
        $form = new Form();
        $form->setID(7777777);

        // get the students - ordered by lastname and firstname
        $students = $this->list->getFromForm( $form );

        // check, that we have retrieved the correct amount of students,
        // and the ID-s of the students are correct
        $this->AssertTrue(
            ( count($students) == 9 ) AND
            ( $students[0]->getID() == 9999999 ) AND
            ( $students[1]->getID() == 9999998 ) AND
            ( $students[2]->getID() == 9999997 ) AND
            ( $students[7]->getID() == 9999992 ) AND
            ( $students[8]->getID() == 9999991 )
        );

        // get the students - ordered by username
        $students = $this->list->getFromForm( $form, array("username") );

        // check, that we have retrieved the correct amount of students,
        // and the ID-s of the students are correct
        $this->AssertTrue(
            ( count($students) == 9 ) AND
            ( $students[0]->getID() == 9999991 ) AND
            ( $students[1]->getID() == 9999992 ) AND
            ( $students[2]->getID() == 9999993 ) AND
            ( $students[7]->getID() == 9999998 ) AND
            ( $students[8]->getID() == 9999999 )
        );
    }

    function testGetFromCourse() {
        // assign this ID to the course
        $course = new Course();
        $course->setID(7777777);

        // get the students - ordered by lastname and firstname
        $students = $this->list->getFromCourse( $course );

        // check, that we have retrieved the correct amount of students,
        $this->AssertEquals( 6, count($students) );
        // and the ID-s of the students are correct
        if ( count($students) == 6 ) {
            $this->AssertEquals( 'Coward', $students[0]->getLastName() );
            $this->AssertEquals( 'Smith5', $students[1]->getLastName() );
            $this->AssertEquals( 'Smith6', $students[2]->getLastName() );
            $this->AssertEquals( 'Smith7', $students[3]->getLastName() );
            $this->AssertEquals( 'Smith8', $students[4]->getLastName() );
            $this->AssertEquals( 'Smith9', $students[5]->getLastName() );
        }
    }

    function testGetCountFromCourse() {
        // assign this ID to the course
        $course = new Course();
        $course->setID(7777777);

        // get the number of students
        $nr = $this->list->getCountFromCourse( $course );

        // check, that we have retrieved the correct amount of students
        $this->AssertEquals( 6, $nr );

    }

}

?>
