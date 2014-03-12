<?php

require_once 'Course.php';
require_once 'PHPUnit.php';

class CourseTest extends PHPUnit_TestCase
{
    var $course;

    // constructor of the test suite
    function CourseTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->course = new Course();
        $db = DBInstance::get();
        $db->query(
            "
            INSERT INTO Courses
                (course_id, course_name, course_subname, teacher_id, description, assessment, books, scheduled_time, classroom, consultation)
            VALUES
                (9999991, 'Math', '12 A', 7777771, 'Simple course in math', 'Exam', 'Algebra for dummies', 'All weekdays 10:00', '302', 'Tuesdays at 11:00' )
            "
        );
        $db->query(
            "
            INSERT INTO Users
                (user_id, username, user_group, school_id)
            VALUES
                (7777771, 'x7777771', 'TEACHER', '8888881')
            "
        );
        $db->query(
            "
            INSERT INTO Schools
                (school_id, school_name)
            VALUES
                (8888881, 'school-8888881')
            "
        );
        $db->query(
            "
            INSERT INTO Courses_Users
                (course_id, user_id, last_view, total_views)
            VALUES
                (9999991, 5555551, '2005-05-05 05:05:05', 5),
                (9999991, 5555552, '2006-06-06 06:06:06', 0)
            "
        );

        $db->query(
            "
            INSERT INTO Lessons
                (lesson_id, course_id)
            VALUES
                (8888881, 9999991),
                (8888882, 9999991)
            "
        );

        $db->query(
            "
            INSERT INTO Marks
                (lesson_id, user_id)
            VALUES
                (8888881, 7777771),
                (8888882, 7777771)
            "
        );

        $db->query(
            "
            INSERT INTO Materials
                (material_id, course_id, user_id)
            VALUES
                (1, 9999991, 7777771),
                (2, 9999991, 7777771)
            "
        );

        $db->query(
            "
            INSERT INTO Material_Contents
                (material_id, material_content)
            VALUES
                (1, 'Foo'),
                (2, 'Bar')
            "
        );
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->course );

        $db = DBInstance::get();
        $db->query("DELETE FROM Courses WHERE course_id = 9999991");
        $db->query("DELETE FROM Users WHERE user_id = 7777771");
        $db->query("DELETE FROM Schools WHERE school_id = 8888881");
        $db->query("DELETE FROM Courses_Users WHERE course_id = 9999991");
        $db->query("DELETE FROM Lessons WHERE course_id = 9999991");
        $db->query("DELETE FROM Marks WHERE user_id = 7777771");
    }

    function testGetSetID() {
        $expected = 9999991;
        $this->course->setID( $expected );
        $this->assertEquals( $expected, $this->course->getID() );
    }

    function testGetSetName() {
        $expected = 'Biology';
        $this->course->setName( $expected );
        $this->assertEquals( $expected, $this->course->getName() );
    }

    function testGetName() {
        $expected = 'Math';
        $this->course->setID( 9999991 );
        $this->assertEquals( $expected, $this->course->getName() );
    }

    function testGetHTMLName() {
        $this->course->setName( '<i>Unique\'s</i> "Course & Source"' );
        $this->assertEquals( '&lt;i&gt;Unique&#039;s&lt;/i&gt; &quot;Course &amp; Source&quot;', $this->course->getHTMLName() );
    }

    function testGetSetSubName() {
        $expected = '12th grade';
        $this->course->setSubName( $expected );
        $this->assertEquals( $expected, $this->course->getSubName() );
    }

    function testGetSubName() {
        $expected = '12 A';
        $this->course->setID( 9999991 );
        $this->assertEquals( $expected, $this->course->getSubName() );
    }

    function testGetHTMLSubName() {
        $this->course->setSubName( '<>"\'&' );
        $this->assertEquals( '&lt;&gt;&quot;&#039;&amp;', $this->course->getHTMLSubName() );
    }

    function testGetSetTeacher() {
        $expected = new User();
        $expected->setID(10);
        $this->course->setTeacher( $expected );
        $result = $this->course->getTeacher();
        $this->assertEquals( get_class($expected), get_class($result) );
        if ( get_class($expected) == get_class($result) ) {
            $this->assertEquals( 10, $result->getID() );
        }
    }

    function testGetTeacher() {
        $expected = 7777771;
        $this->course->setID( 9999991 );
        $result = $this->course->getTeacher();
        $this->assertEquals( 'User', get_class($result) );
        if ( 'User' == get_class($result) ) {
            $this->assertEquals( $expected, $result->getID() );
        }
    }

    function testGetSetDescription() {
        $expected = 'Algae course';
        $this->course->setDescription( $expected );
        $this->assertEquals( $expected, $this->course->getDescription() );
    }

    function testGetDescription() {
        $expected = 'Simple course in math';
        $this->course->setID( 9999991 );
        $this->assertEquals( $expected, $this->course->getDescription() );
    }

    function testGetHTMLDescription() {
        $this->course->setDescription( "Hello\n\nWorld" );
        $this->assertRegExp( '/<p>Hello<\/p>/', $this->course->getHTMLDescription() );
        $this->assertRegExp( '/<p>World<\/p>/', $this->course->getHTMLDescription() );
    }

    function testGetSetAssessment() {
        $expected = 'Exam';
        $this->course->setAssessment( $expected );
        $this->assertEquals( $expected, $this->course->getAssessment() );
    }

    function testGetAssessment() {
        $expected = 'Exam';
        $this->course->setID( 9999991 );
        $this->assertEquals( $expected, $this->course->getAssessment() );
    }

    function testGetHTMLAssessment() {
        $this->course->setAssessment( "Micro & Soft" );
        $this->assertRegExp( '/<p>Micro &amp; Soft<\/p>/', $this->course->getHTMLAssessment() );
    }

    function testGetSetBooks() {
        $expected = '12 books at least';
        $this->course->setBooks( $expected );
        $this->assertEquals( $expected, $this->course->getBooks() );
    }

    function testGetBooks() {
        $expected = 'Algebra for dummies';
        $this->course->setID( 9999991 );
        $this->assertEquals( $expected, $this->course->getBooks() );
    }

    function testGetHTMLBooks() {
        $this->course->setBooks( "K & R" );
        $this->assertRegExp( '/<p>K &amp; R<\/p>/', $this->course->getHTMLBooks() );
    }

    function testGetSetScheduledTime() {
        $expected = '12:00 afternoon';
        $this->course->setScheduledTime( $expected );
        $this->assertEquals( $expected, $this->course->getScheduledTime() );
    }

    function testGetScheduledTime() {
        $expected = 'All weekdays 10:00';
        $this->course->setID( 9999991 );
        $this->assertEquals( $expected, $this->course->getScheduledTime() );
    }

    function testGetHTMLScheduledTime() {
        $this->course->setScheduledTime( "K & R" );
        $this->assertRegExp( '/<p>K &amp; R<\/p>/', $this->course->getHTMLScheduledTime() );
    }

    function testGetSetClassRoom() {
        $expected = 'room 302';
        $this->course->setClassRoom( $expected );
        $this->assertEquals( $expected, $this->course->getClassRoom() );
    }

    function testGetClassRoom() {
        $expected = '302';
        $this->course->setID( 9999991 );
        $this->assertEquals( $expected, $this->course->getClassRoom() );
    }

    function testGetHTMLClassRoom() {
        $this->course->setClassRoom( "K & R" );
        $this->assertRegExp( '/<p>K &amp; R<\/p>/', $this->course->getHTMLClassRoom() );
    }

    function testGetSetConsultation() {
        $expected = 'no consultations, just the exam';
        $this->course->setConsultation( $expected );
        $this->assertEquals( $expected, $this->course->getConsultation() );
    }

    function testGetConsultation() {
        $expected = 'Tuesdays at 11:00';
        $this->course->setID( 9999991 );
        $this->assertEquals( $expected, $this->course->getConsultation() );
    }

    function testGetHTMLConsultation() {
        $this->course->setConsultation( "K & R" );
        $this->assertRegExp( '/<p>K &amp; R<\/p>/', $this->course->getHTMLConsultation() );
    }

    function testGetSchool() {
        $expected = 8888881;
        $this->course->setID( 9999991 );
        $school = $this->course->getSchool();
        $this->assertEquals( 'School', get_class($school) );
        if ( 'School' == get_class($school) ) {
            $this->assertEquals( $expected, $school->getID() );
        }
    }

    function testStudentExists() {
        $this->course->setID( 9999991 );
        $student = new User();
        $student->setID(5555552);
        $this->assertTrue( $this->course->studentExists( $student ) );
        $student->setID(5555553);
        $this->assertFalse( $this->course->studentExists( $student ) );
    }


    function testSaveInsert() {
        $this->course->setName('Biology');
        $this->course->setSubName('11th grade');
        $this->course->setDescription('Animals in zoo');
        $this->course->setAssessment('No-one passes');
        $this->course->setBooks('No books at all');
        $this->course->setScheduledTime('24/7');
        $this->course->setClassRoom('my home');
        $this->course->setConsultation('in the future somewhere');
        $teacher = new User();
        $teacher->setID(7777772);
        $this->course->setTeacher($teacher);

        $this->course->save();

        $id = $this->course->getID();

        $this->assertTrue( isset($id) );
        if ( isset($id) ) {
            $db = DBInstance::get();
            $row = $db->getRow("SELECT * FROM Courses WHERE course_id=?", array($id) );

            $this->assertEquals('Biology', $row['course_name']);
            $this->assertEquals('11th grade', $row['course_subname']);
            $this->assertEquals('Animals in zoo', $row['description']);
            $this->assertEquals('No-one passes', $row['assessment']);
            $this->assertEquals('No books at all', $row['books']);
            $this->assertEquals('24/7', $row['scheduled_time']);
            $this->assertEquals('my home', $row['classroom']);
            $this->assertEquals('in the future somewhere', $row['consultation']);
            $this->assertEquals(7777772, $row['teacher_id']);

            $db->query("DELETE FROM Courses WHERE course_id=?", array($id));
        }
    }


    function testSaveUpdate() {
        $id = 9999991;
        $this->course->setID($id);
        $this->course->setName('Biology');
        $this->course->setSubName('11th grade');
        $this->course->setDescription('Animals in zoo');
        $this->course->setAssessment('No-one passes');
        $this->course->setBooks('No books at all');
        $this->course->setScheduledTime('24/7');
        $this->course->setClassRoom('my home');
        $this->course->setConsultation('in the future somewhere');
        $teacher = new User();
        $teacher->setID(7777772);
        $this->course->setTeacher($teacher);

        $this->course->save();

        $db = DBInstance::get();
        $row = $db->getRow("SELECT * FROM Courses WHERE course_id=?", array($id) );

        $this->assertEquals('Biology', $row['course_name']);
        $this->assertEquals('11th grade', $row['course_subname']);
        $this->assertEquals('Animals in zoo', $row['description']);
        $this->assertEquals('No-one passes', $row['assessment']);
        $this->assertEquals('No books at all', $row['books']);
        $this->assertEquals('24/7', $row['scheduled_time']);
        $this->assertEquals('my home', $row['classroom']);
        $this->assertEquals('in the future somewhere', $row['consultation']);
        $this->assertEquals(7777772, $row['teacher_id']);
    }


    function testSimpleDelete() {
        $id = 9999991;
        $this->course->setID($id);

        $result = $this->course->delete();
        $this->assertTrue( $result === true);

        $db = DBInstance::get();

        $row = $db->getRow("SELECT * FROM Courses WHERE course_id=?", array($id) );

        $this->assertFalse( isset($row) );
    }


    function testUnexistingDelete() {
        $id = 9999993;
        $this->course->setID($id);

        $result = $this->course->delete();

        $this->assertTrue( $result === false );
    }


    function testRecursiveDelete() {
        $id = 9999991;
        $this->course->setID($id);

        $result = $this->course->delete();
        $this->assertTrue( $result === true);

        $db = DBInstance::get();

        $row = $db->getRow("SELECT * FROM Courses WHERE course_id=?", array($id) );
        $this->assertFalse( isset($row) );

        $row = $db->getRow("SELECT * FROM Courses_Users WHERE course_id=?", array($id) );
        $this->assertFalse( isset($row) );

        $row = $db->getRow("SELECT * FROM Lessons WHERE course_id=?", array($id) );
        $this->assertFalse( isset($row) );

        $row = $db->getRow("SELECT * FROM Marks WHERE user_id=?", array(7777771) );
        $this->assertFalse( isset($row) );

        $row = $db->getRow("SELECT * FROM Materials WHERE user_id=?", array(7777771) );
        $this->assertFalse( isset($row) );

        $row = $db->getRow("SELECT * FROM Material_Contents WHERE material_id=1 OR material_id=2" );
        $this->assertFalse( isset($row) );
    }


    function testAccessByStudent() {
        $course_id = 9999991;
        $this->course->setID( $course_id );

        $user_id = 5555551;
        $user = new User( $user_id );

        $this->course->accessByStudent( $user );

        $db = DBInstance::get();

        // test that total views has been incremented
        $total_views = $db->getOne(
            "SELECT total_views FROM Courses_Users WHERE course_id=? AND user_id=?",
            array( $course_id, $user_id )
        );
        $this->assertEquals( 6, $total_views );

        // test that the last view date has changed
        $total_views = $db->getOne(
            "SELECT last_view FROM Courses_Users WHERE course_id=? AND user_id=?",
            array( $course_id, $user_id )
        );
        $this->assertFalse( '2005-05-05 05:05:05' == $total_views );
    }

}

?>
