<?php

require_once 'ForumPost.php';
require_once 'PHPUnit.php';

class ForumPostTest extends PHPUnit_TestCase
{
    var $post;

    // constructor of the test suite
    function ForumPostTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->post = new ForumPost();
        $db = DBInstance::get();
        $db->query(
            "
            INSERT INTO Posts
                (post_id, topic_id, user_id, addtime, post_content)
            VALUES
                (9999991, 7777771, 6666661, '2006-05-04 10:00:00', 'Hello world!'),
                (9999992, 7777771, 6666661, '2006-05-04 14:00:00', 'Thread closed.'),
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
        unset( $this->post );

        $db = DBInstance::get();
        $db->query("DELETE FROM Posts WHERE topic_id = 7777771 OR topic_id = 456456");
    }

    function testSetGetAttributes() {
        // set all attributes to arbitrary values
        $id = 123;
        $this->post->setID( $id );
        $topic = new ForumTopic( 456456465 );
        $this->post->setTopic( $topic );
        $author = new User( 789789789 );
        $this->post->setAuthor( $author );
        $date = new Date('2001-02-03 00:00:00');
        $this->post->setCreationDate( $date );
        $content = 'Foo';
        $this->post->setContent( $content );

        // check that getting these values gives the correct results
        $this->assertEquals( $id, $this->post->getID() );
        $this->assertEquals( $topic, $this->post->getTopic() );
        $this->assertEquals( $author, $this->post->getAuthor() );
        $this->assertEquals( $date, $this->post->getCreationDate() );
        $this->assertEquals( $content, $this->post->getContent() );
    }

    function testGetAttributes() {
        // set post ID to be equal to the one post ID in database
        $this->post->setID( 9999991 );

        // check that getting the values of Post attributes gives
        // exactly the values that are in the database
        $topic = $this->post->getTopic();
        $this->assertEquals( 7777771, $topic->getID() );
        $author = $this->post->getAuthor();
        $this->assertEquals( 6666661, $author->getID() );
        $date = $this->post->getCreationDate();
        $this->assertEquals( '2006-05-04 10:00:00', $date->getDate() );
        $this->assertEquals( 'Hello world!', $this->post->getContent() );
    }

    function testGetHTMLContent() {
        $this->post->setContent( "Micro & Soft" );
        $this->assertRegExp( '/<p>Micro &amp; Soft<\/p>/', $this->post->getHTMLContent() );
    }

    function testSaveUpdate() {
        // set post ID to be equal to the one post ID in database
        $id = 9999991;
        $this->post->setID( $id );
        // set other attributes to arbitrary values
        $topic = new ForumTopic( 456456 );
        $this->post->setTopic( $topic );
        $author = new User( 789789 );
        $this->post->setAuthor( $author );
        $date = new Date('2001-02-03 00:00:00');
        $this->post->setCreationDate( $date );
        $content = 'Foo';
        $this->post->setContent( $content );

        $this->post->save();

        // check that database entry was correctly changed
        $db = DBInstance::get();
        $count = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Posts
            WHERE
                post_id = ? AND
                topic_id = 456456 AND
                user_id = 789789 AND
                addtime = '2001-02-03 00:00:00' AND
                post_content = 'Foo'
            ",
            array( $id )
        );
        $this->assertEquals( 1, $count );
    }

    function testSaveInsert() {
        // set attributes (except ID) to arbitrary values
        $topic = new ForumTopic( 456456 );
        $this->post->setTopic( $topic );
        $author = new User( 789789 );
        $this->post->setAuthor( $author );
        $date = new Date('2001-02-03 00:00:00');
        $this->post->setCreationDate( $date );
        $content = 'Foo';
        $this->post->setContent( $content );

        $this->post->save();
        $id = $this->post->getID();

        // check that database entry was correctly changed
        $db = DBInstance::get();
        $count = $db->getOne(
            "SELECT
                COUNT(*)
            FROM
                Posts
            WHERE
                post_id = ? AND
                topic_id = 456456 AND
                user_id = 789789 AND
                addtime = '2001-02-03 00:00:00' AND
                post_content = 'Foo'
            ",
            array( $id )
        );
        $this->assertEquals( 1, $count );
    }

    function testDeleteExisting() {
        // set post ID to be equal to the one post ID in database
        $id = 9999991;
        $this->post->setID( $id );

        $success = $this->post->delete();
        $this->assertTrue( $success );

        if ( $success ) {
            // check that database entry was deleted
            $db = DBInstance::get();
            $count = $db->getOne(
                "SELECT
                    COUNT(*)
                FROM
                    Posts
                WHERE
                    post_id = ?
                ",
                array( $id )
            );
            $this->assertEquals( 0, $count );
        }
    }

    function testDeleteUnexisting() {
        // set post ID to be unexisting in database
        $id = 9999999;
        $this->post->setID( $id );

        $success = $this->post->delete();
        $this->assertFalse( $success );
    }



}

?>
