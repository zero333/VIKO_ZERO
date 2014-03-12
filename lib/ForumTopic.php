<?php

/**
 * Contains the ForumTopic class
 *
 * PHP versions 4 and 5
 *
 * LICENSE: VIKO is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * VIKO is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * VIKO; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA  02111-1307  USA
 *
 * @package    VIKO
 * @author     Rene Saarsoo <nene@triin.net>
 * @copyright  2001-2007 VIKO team and contributors
 * @license    http://www.gnu.org/licenses/gpl.html  GPL 2.0
 */


/**
 * This is a child class of TableAbstraction
 */
require_once 'TableAbstraction.php';

/**
 * This class needs to create some ForumPost objects
 */
require_once 'ForumPost.php';


/**
 * Represents one topic in forum
 *
 * The topic itself consists of 1..n posts
 */
class ForumTopic extends TableAbstraction
{
    /**
     * Returns forum topic title
     *
     * @return string title of the topic
     */
    function getTitle()
    {
        return $this->getAttribute("_title");
    }


    /**
     * Sets forum topic title
     *
     * @param string $title title of the topic
     */
    function setTitle( $title )
    {
        $this->_title = $title;
    }


    /**
     * Returns title of the topic that is safe to insert into HTML
     *
     * @return string title with special characters escaped
     */
    function getHTMLTitle()
    {
        return htmlspecialchars( $this->getTitle(), ENT_QUOTES );
    }


    /**
     * Returns course where the topic belongs
     *
     * @return string course of the topic
     */
    function getCourse()
    {
        return $this->getAttribute("_course");
    }


    /**
     * Sets the course where the forum topic belongs
     *
     * @param string $course course of the topic
     */
    function setCourse( $course )
    {
        $this->_course = $course;
    }


    /**
     * Returns the first post under the forum topic
     *
     * @return ForumPost the first post
     */
    function getFirstPost()
    {
        return $this->_getPostUsingSQLFunction( "MIN" );
    }


    /**
     * Returns the last post under the forum topic
     *
     * @return ForumPost the last post
     */
    function getLastPost()
    {
        return $this->_getPostUsingSQLFunction( "MAX" );
    }


    /**
     * Returns either first or last post depending on the specified function
     *
     * @access private
     * @param string $function either "MAX" or "MIN"
     * @return ForumPost the last or first post
     */
    function _getPostUsingSQLFunction( $function )
    {
        $db = DBInstance::get();
        $record = $db->getRow(
            "SELECT
                *
            FROM
                Posts
            WHERE
                topic_id=? AND
                addtime = ( SELECT $function(addtime) FROM Posts WHERE topic_id=? )
            ",
            array( $this->_id, $this->_id )
        );

        $post = new ForumPost( $record['post_id'] );
        $post->readFromDatabaseRecord( $record );

        return $post;
    }


    /**
     * Returns the number of posts in this topic
     *
     * All posts including the initial post are counted meaning,
     * the minimum number of posts in topic is one.
     *
     * @return int number of posts
     */
    function getNrOfPosts()
    {
        $db = DBInstance::get();
        return $db->getOne(
            "SELECT COUNT(*) FROM Posts WHERE topic_id=?",
            array( $this->_id )
        );
    }


    /**
     * Returns all the posts in this topic
     *
     * The returned list of posts is ordered chronologically.
     *
     * @return array list of ForumPost objects
     */
    function getAllPosts()
    {
        $db = DBInstance::get();
        $result = $db->query(
            "SELECT * FROM Posts WHERE topic_id=? ORDER BY addtime ASC",
            array( $this->_id )
        );

        $all_posts = array();
        while ( $result->fetchInto( $record ) ) {
            $post = new ForumPost( $record['post_id'] );
            $post->readFromDatabaseRecord( $record );
            $all_posts[] = $post;
        }

        return $all_posts;
    }


    /**
     * Returns name of the table containing forum topics
     */
    function getTableName()
    {
        return "Topics";
    }


    /**
     * Returns the primary key field name of the forum topics table
     */
    function getIDFieldName()
    {
        return "topic_id";
    }


    /**
     * Implementation of reading a database record
     */
    function readFromDatabaseRecord( $record )
    {
        $this->_title = $record['topic_title'];
        $this->_course = new Course( $record['course_id'] );
    }


    /**
     * Implementation of composing a database record
     */
    function writeToDatabaseRecord()
    {
        if ( !isset($this->_title) ) {
            trigger_error("Topic title must be specified when saving topic.", E_USER_ERROR);
        }
        if ( !( isset($this->_course) && $this->_course->getID() > 0 ) ) {
            trigger_error("Course must be specified when saving topic.", E_USER_ERROR);
        }

        return array(
            "topic_title" => $this->_title,
            "course_id" => $this->_course->getID()
        );
    }


    /**
     * Removes the forum topic together with all posts in it from database
     *
     * @return boolean true on successful delete.
     */
    function delete()
    {
        // first delete the forum topic record itself
        if ( parent::delete() ) {

            // After that remove all the posts of this topic
            $db = DBInstance::get();
            $db->query(
                "DELETE FROM Posts WHERE topic_id=?",
                array( $this->_id )
            );

            return true;
        }
        else {
            return false;
        }
    }


}



?>
