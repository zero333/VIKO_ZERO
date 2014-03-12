<?php

require_once 'ForumTopicList.php';
require_once 'PHPUnit.php';

class ForumTopicListTest extends PHPUnit_TestCase
{
    var $topic;

    // constructor of the test suite
    function ForumTopicTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $db = DBInstance::get();
        $db->query(
            "
            INSERT INTO Topics
                (topic_id, course_id, topic_title)
            VALUES
                (7777771, 8888881, 'Middle topic'),
                (7777772, 8888881, 'First topic'),
                (7777773, 8888881, 'Last topic')
            "
        );
        $db->query(
            "
            INSERT INTO Posts
                (post_id, topic_id, user_id, addtime, post_content)
            VALUES
                (9999991, 7777771, 6666661, '2006-05-04 10:00:00', 'Hello world!'),
                (9999992, 7777771, 6666661, '2006-05-04 11:00:00', 'Yeah, hello.'),
                (9999993, 7777771, 6666661, '2006-05-04 12:00:00', 'Wazzap!'),
                (9999994, 7777771, 6666661, '2006-05-04 13:00:00', 'LOL'),
                (9999995, 7777771, 6666661, '2006-05-04 14:00:00', 'Thread closed.'),

                (9999996, 7777772, 6666661, '2006-05-04 12:00:00', 'Hello world! 2'),
                (9999997, 7777772, 6666661, '2006-05-04 13:00:00', 'Yeah, hello. 2'),
                (9999998, 7777772, 6666661, '2006-05-04 14:00:00', 'Wazzap! 2'),
                (9999999, 7777772, 6666661, '2006-05-04 15:00:00', 'Thread closed. 2'),

                (9999989, 7777773, 6666661, '2006-05-01 16:00:00', 'Hello world! 3'),
                (9999990, 7777773, 6666661, '2006-05-01 17:00:00', 'Thread closed. 3')
            "
        );
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        $db = DBInstance::get();
        $db->query("DELETE FROM Posts WHERE user_id = 6666661");
        $db->query("DELETE FROM Topics WHERE course_id = 8888881");
    }

    function testGetFromCourse() {
        $course = new Course( 8888881 );

        $list = ForumTopicList::getFromCourse( $course );

        // there are 3 topics on that course
        $this->assertEquals( 3, count($list) );
        if ( 3 == count($list) ) {
            // check that the topics are in correct order
            // that means most recently updated ones at the beginning
            $this->assertEquals( 'First topic', $list[0]->getTitle() );
            $this->assertEquals( 'Middle topic', $list[1]->getTitle() );
            $this->assertEquals( 'Last topic', $list[2]->getTitle() );
        }
    }


}

?>
