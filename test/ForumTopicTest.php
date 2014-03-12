<?php

require_once 'ForumTopic.php';
require_once 'PHPUnit.php';

class ForumTopicTest extends PHPUnit_TestCase
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
        $this->topic = new ForumTopic();
        $db = DBInstance::get();
        $db->query(
            "
            INSERT INTO Topics
                (topic_id, course_id, topic_title)
            VALUES
                (7777771, 8888881, 'Superb topic'),
                (7777772, 8888881, 'Boring topic')
            "
        );
        $db->query(
            "
            INSERT INTO Posts
                (post_id, topic_id, user_id, addtime, post_content)
            VALUES
                (9999991, 7777771, 6666661, '2006-05-04 14:00:00', 'Thread closed.'),
                (9999992, 7777771, 6666661, '2006-05-04 10:00:00', 'Hello world!'),
                (9999993, 7777771, 6666661, '2006-05-04 13:00:00', 'Yeah, hello.'),
                (9999994, 7777771, 6666661, '2006-05-04 12:00:00', 'Wazzap!'),
                (9999995, 7777771, 6666661, '2006-05-04 11:00:00', 'LOL')
            "
        );
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->topic );

        $db = DBInstance::get();
        $db->query("DELETE FROM Posts WHERE topic_id = 7777771");
        $db->query("DELETE FROM Topics WHERE course_id = 8888881");
    }

    function testSetGetAttributes() {
        // set all attributes to arbitrary values
        $id = 123;
        $this->topic->setID( $id );
        $course = new Course( 456456465 );
        $this->topic->setCourse( $course );
        $title = 'Blaah';
        $this->topic->setTitle( $title );

        // check that getting these values gives the correct results
        $this->assertEquals( $id, $this->topic->getID() );
        $this->assertEquals( $course, $this->topic->getCourse() );
        $this->assertEquals( $title, $this->topic->getTitle() );
    }

    function testGetAttributes() {
        // set topic ID to be equal to the one topic ID in database
        $this->topic->setID( 7777771 );

        // check that getting the values of Topic attributes gives
        // exactly the values that are in the database
        $course = $this->topic->getCourse();
        $this->assertEquals( 8888881, $course->getID() );
        $this->assertEquals( 'Superb topic', $this->topic->getTitle() );
    }

    function testGetHTMLTitle() {
        $this->topic->setTitle( '<i>Unique\'s</i> "Form & Storm"' );
        $this->assertEquals( '&lt;i&gt;Unique&#039;s&lt;/i&gt; &quot;Form &amp; Storm&quot;', $this->topic->getHTMLTitle() );
    }

    function testGetNrOfPosts() {
        $this->topic->setID( 7777771 );
        $this->assertEquals( 5, $this->topic->getNrOfPosts() );
    }

    function testGetFirstPost() {
        $this->topic->setID( 7777771 );
        $post = $this->topic->getFirstPost();
        $this->assertEquals( 'ForumPost', get_class( $post ) );
        if ( 'ForumPost' == get_class( $post ) ) {
            $this->assertEquals( 'Hello world!', $post->getContent() );
        }
    }

    function testGetLastPost() {
        $this->topic->setID( 7777771 );
        $post = $this->topic->getLastPost();
        $this->assertEquals( 'ForumPost', get_class( $post ) );
        if ( 'ForumPost' == get_class( $post ) ) {
            $this->assertEquals( 'Thread closed.', $post->getContent() );
        }
    }

    function testGetAllPosts() {
        $this->topic->setID( 7777771 );
        $posts = $this->topic->getAllPosts();
        $this->assertEquals( 5, count( $posts ) );
        if ( 5 == count( $posts ) ) {
            $this->assertEquals( 'Hello world!', $posts[0]->getContent() );
            $this->assertEquals( 'LOL', $posts[1]->getContent() );
            $this->assertEquals( 'Wazzap!', $posts[2]->getContent() );
            $this->assertEquals( 'Yeah, hello.', $posts[3]->getContent() );
            $this->assertEquals( 'Thread closed.', $posts[4]->getContent() );
        }
    }

    function testSaveUpdate() {
        // set topic ID to be equal to the one topic ID in database
        $id = 7777771;
        $this->topic->setID( $id );
        // set other attributes to arbitrary values
        $course = new Course( 8888881 );
        $this->topic->setCourse( $course );
        $title = 'Foo';
        $this->topic->setTitle( $title );

        $this->topic->save();

        // check that database entry was correctly changed
        $db = DBInstance::get();
        $count = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Topics
            WHERE
                topic_id = ? AND
                course_id = 8888881 AND
                topic_title = 'Foo'
            ",
            array( $id )
        );
        $this->assertEquals( 1, $count );
    }

    function testSaveInsert() {
        // set attributes (except ID) to arbitrary values
        $course = new Course( 8888881 );
        $this->topic->setCourse( $course );
        $title = 'Foo';
        $this->topic->setTitle( $title );

        $this->topic->save();
        $id = $this->topic->getID();

        // check that database entry was correctly changed
        $db = DBInstance::get();
        $count = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Topics
            WHERE
                topic_id = ? AND
                course_id = 8888881 AND
                topic_title = 'Foo'
            ",
            array( $id )
        );
        $this->assertEquals( 1, $count );
    }

    function testDeleteExisting() {
        // set topic ID to be equal to the one topic ID in database
        $id = 7777771;
        $this->topic->setID( $id );

        $success = $this->topic->delete();
        $this->assertTrue( $success );

        if ( $success ) {
            // check that database entry was deleted
            $db = DBInstance::get();
            $count = $db->getOne(
                "SELECT
                    COUNT(*)
                FROM
                    Topics
                WHERE
                    topic_id = ?
                ",
                array( $id )
            );
            $this->assertEquals( 0, $count );
        }
    }

    function testDeleteUnexisting() {
        // set topic ID to be unexisting in database
        $id = 9999999;
        $this->topic->setID( $id );

        $success = $this->topic->delete();
        $this->assertFalse( $success );
    }

    function testRecursiveDelete() {
        // set topic ID to be equal to the one topic ID in database
        $id = 7777771;
        $this->topic->setID( $id );

        $success = $this->topic->delete();
        $this->assertTrue( $success );

        if ( $success ) {
            // check that all associated posts were deleted
            $db = DBInstance::get();
            $count = $db->getOne(
                "SELECT
                    COUNT(*)
                FROM
                    Posts
                WHERE
                    topic_id = ?
                ",
                array( $id )
            );
            $this->assertEquals( 0, $count );
        }
    }


}

?>
