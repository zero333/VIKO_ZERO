<?php

/**
 * Contains the ForumPost class
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
 * The property _topic of this class is of type ForumTopic
 */
require_once 'ForumTopic.php';

/**
 * The property _author of this class is of type User
 */
require_once 'User.php';

/**
 * The property _date of this class is of type Date
 */
require_once 'Pear/Date.php';

/**
 * The class transforms BBCode to HTML
 */
require_once "VikoContentFormatter.php";


/**
 * Manages forum post data
 *
 */
class ForumPost extends TableAbstraction
{
    /**
     * Returns the topic this post belongs to
     *
     * @return ForumTopic post topic
     */
    function getTopic()
    {
        return $this->getAttribute("_topic");
    }


    /**
     * Sets the topic this post belongs to
     *
     * @param ForumTopic $topic post topic
     */
    function setTopic( $topic )
    {
        $this->_topic = $topic;
    }


    /**
     * Returns the author of the post
     *
     * @return User post author
     */
    function getAuthor()
    {
        return $this->getAttribute("_author");
    }


    /**
     * Sets the author of the post
     *
     * @param User $author post author
     */
    function setAuthor( $author )
    {
        $this->_author = $author;
    }


    /**
     * Returns the date showing when post was created
     *
     * @return Date creation date of post
     */
    function getCreationDate()
    {
        return $this->getAttribute("_creation_date");
    }


    /**
     * Sets the date showing when post was created
     *
     * @param Date $date post creation date
     */
    function setCreationDate( $date )
    {
        $this->_creation_date = $date;
    }


    /**
     * Returns the textual content of the post
     *
     * @return string content of the post
     */
    function getContent()
    {
        return $this->getAttribute("_content");
    }


    /**
     * Sets the textual content of the post
     *
     * @param string $content post content
     */
    function setContent( $content )
    {
        $this->_content = $content;
    }


    /**
     * Returns post content that is HTML-formatted
     *
     * @return string content that is ready for inserting into HTML
     */
    function getHTMLContent()
    {
        return VikoContentFormatter::format( $this->getContent() );
    }


    /**
     * Returns name of the table containing forum posts
     */
    function getTableName()
    {
        return "Posts";
    }


    /**
     * Returns the primary key field name of the forum posts table
     */
    function getIDFieldName()
    {
        return "post_id";
    }


    /**
     * Implementation of reading a database record
     */
    function readFromDatabaseRecord( $record )
    {
        $this->_topic = new ForumTopic( $record['topic_id'] );
        $this->_author = new User( $record['user_id'] );
        $this->_creation_date = new Date( $record['addtime'] );
        $this->_content = $record['post_content'];
    }


    /**
     * Implementation of composing a database record
     */
    function writeToDatabaseRecord()
    {
        if ( !( isset($this->_topic) && $this->_topic->getID() > 0 ) ) {
            trigger_error("Topic must be specified when saving post.", E_USER_ERROR);
        }
        if ( !( isset($this->_author) && $this->_author->getID() > 0 ) ) {
            trigger_error("Author must be specified when saving post.", E_USER_ERROR);
        }
        if ( !isset($this->_creation_date) ) {
            trigger_error("Creation date must be specified when saving post.", E_USER_ERROR);
        }
        if ( !isset($this->_content) ) {
            trigger_error("Post content must be specified when saving post.", E_USER_ERROR);
        }

        return array(
            "topic_id" => $this->_topic->getID(),
            "user_id" => $this->_author->getID(),
            "addtime" => $this->_creation_date->getDate(),
            "post_content" => $this->_content
        );
    }


}



?>
