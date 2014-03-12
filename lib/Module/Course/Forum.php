<?php

/**
 * Contains the Module_Course_Forum class
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

require_once 'lib/Module/Course.php';
require_once 'lib/ForumTopicList.php';
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';
require_once 'lib/VikoTable.php';

/**
 * To specify that _createForm should create new topic
 */
define( 'NEW_TOPIC_FORM', 0 );

/**
 * To specify that _createForm should create new post
 */
define( 'NEW_POST_FORM', 1 );

/**
 * Manages the Forum of the course
 *
 * <ul>
 * <li>/#course_id/forum - shows table of forum topics, most recently updated ones at the beginning</li>
 * <li>/#course_id/forum/#topic_id - shows posts from topic with ID #topic_id</li>
 * <li>/#course_id/forum/#topic_id/delete - deletes topic with ID #topic_id and all posts inside that</li>
 * <li>/#course_id/forum/#topic_id/#post_id/delete - deletes post with ID #post_id</li>
 * </ul>
 */
class Module_Course_Forum extends Module_Course {

    /**
     * Action to perform with post or topic
     *
     * @access private
     * @var String
     */
    var $_action;

    /**
     * Selected forum topic
     *
     * @access private
     * @var ForumTopic
     */
    var $_topic;

    /**
     * Selected forum post
     *
     * @access private
     * @var ForumPost
     */
    var $_post;

    /**
     * Constructs new Course Forum Module
     */
    function Module_Course_Forum( $course, $parameters )
    {
        if ( isset( $parameters[0] ) ) {
            if ( (int)$parameters[0] > 0 ) {
                $this->_topic = new ForumTopic( $parameters[0] );
            }
        }

        if ( isset( $parameters[1] ) ) {
            if ( (int)$parameters[1] > 0 ) {
                $this->_post = new ForumPost( $parameters[1] );
            }
            elseif ( $parameters[1] == 'delete' ) {
                $this->_action = 'delete';
            }
        }

        if ( isset( $parameters[2] ) && $parameters[2] == 'delete' ) {
                $this->_action = 'delete';
        }

        parent::Module_Course( $course, $parameters );
    }


    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "forum";
    }


    /**
     * Returns the short name of the module, this is used in navigation menu.
     *
     * @access public
     * @static
     * @return string short name
     */
    function getName()
    {
        return _("Forum");
    }


    /**
     * Returns short description of module
     *
     * @access public
     * @static
     * @return string module description
     */
    function getDescription()
    {
        return _("Course forum");
    }


    /**
     * Returns special stylesheet for forum page
     *
     * @access public
     * @return array list of stylesheets
     */
    function getStylesheets()
    {
		return array( array( 'uri' => 'forum.css' ) );
    }


    /**
     * Returns Forum page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {

        // if action is "delete", then delete either topic or post
        if ( isset( $this->_action ) && $this->_action == 'delete' ) {

            // first ensure, that user is authorized to delete posts/topics
            if ( !$this->_userCanEditThisCourse() ) {
                return $this->accessDeniedPage(
                    _("Only teacher can delete forum posts and topics.")
                );
            }

            if ( isset( $this->_topic ) && isset( $this->_post ) ) {
                return $this->_deletePost( $this->_post );
            }
            elseif ( isset( $this->_topic ) ) {
                return $this->_deleteTopic( $this->_topic );
            }
        }

        // decide weather to show topics or posts
        if ( isset( $this->_topic ) ) {
            // if topic is chosen, show posts from that topic
            return $this->_postsPage();
        }
        else {
            // if topic is not specified, show all topics
            return $this->_topicsPage();
        }
    }


    /**
     * Returns Forum topics page in HTML
     *
     * @access private
     * @return string HTML fragment
     */
    function _topicsPage()
    {
        // manage the form
        $form = $this->_createForm( NEW_TOPIC_FORM );
        if ( $form->validate() ) {
            // if form is submitted correctly then add new topic
            $this->_addTopic(
                $form->exportValue( 'topic' ),
                $form->exportValue( 'message' )
            );
        }
        $form_html =
            HTML::h3( _("Add new topic") ) .
            HTML_QuickForm_Renderer_VIKO::render( $form );

        // create list of forum posts
        $topics_html =
            $this->title() .
            $this->_listOfTopics();

        return $topics_html . $form_html;
    }


    /**
     * Adds new topic into the forum of current course with specified title and content
     *
     * @access private
     * @param string $title the title of the topic
     * @param string $content the content of the post
     */
    function _addTopic( $title, $content )
    {
        // create new ForumTopic object
        $topic = new ForumTopic();
        $topic->setTitle( $title );
        $topic->setCourse( $this->_course );
        // save it into database
        $topic->save();

        // create new ForumPost object
        $post = new ForumPost();
        $post->setContent( $content );
        $post->setCreationDate( new Date() );
        $post->setAuthor( $_SESSION['user'] );
        $post->setTopic( $topic );
        // save it into database
        $post->save();

        // send user directly to newly created topic
 		$this->redirectInsideCourseModule( $topic->getID() );         
		exit();
    }


    /**
     * Deletes the specified topic and all posts in it
     *
     * @access private
     * @param ForumTopic $topic the topic to delete
     */
    function _deleteTopic( $topic )
    {
        // topic has to exist
        if ( !$topic->loadFromDatabaseByID() ) {
            return $this->errorPage(
                _("Selected forum topic does not exist.")
            );
        }

        // topic has to belong to this course
        $topic_course = $topic->getCourse();
        if ( $topic_course->getID() != $this->_course->getID() ) {
            return $this->accessDeniedPage(
                _("Selected forum topic does not belong to this course.")
            );
        }

        $topic->delete();
		$this->redirectInsideCourseModule();        
		return false;
    }


    /**
     * Deletes the specified post
     *
     * @access private
     * @param ForumPost $post the post to delete
     */
    function _deletePost( $post )
    {
        // post has to exist
        if ( !$post->loadFromDatabaseByID() ) {
            return $this->errorPage(
                _("Selected forum post does not exist.")
            );
        }

        // post has to belong to this course
        $topic = $post->getTopic();
        $topic_course = $topic->getCourse();
        if ( $topic_course->getID() != $this->_course->getID() ) {
            return $this->accessDeniedPage(
                _("Selected forum post does not belong to this course.")
            );
        }

        // if the post is first post in topic, then delete the whole topic
        $first_post = $this->_topic->getFirstPost();
        if ( $post->getID() == $first_post->getID() ) {
            $this->_topic->delete();
            // as this topic is no more, redirect to the main page of forum
			$this->redirectInsideCourseModule();             
			return false;
        }
        else {
            // if it's normal post, then just delete it
            $post->delete();
			$this->redirectInsideCourseModule( $this->_topic->getID() );             
			return false;
        }
    }


    /**
     * Returns table with forum topics
     *
     * @access private
     * @return string HTML fragment
     */
    function _listOfTopics()
    {
        $table = new VikoTable();

        // create table header
        $header = array(
            _("Topic"),
            _("Nr of answers"),
            _("First post"),
            _("Last post")
        );
        if ( $this->_userCanEditThisCourse() ) {
            $header[] = HTML::abbr( 'X', _("Delete topic") );
        }
        $table->addHeader( $header );

        // create table body
        $topic_list = ForumTopicList::getFromCourse( $this->_course );
        foreach ( $topic_list as $topic ) {
            $row = array();

            // link to topic itself
            $row[] = HTML::a( $this->relativeURI( $topic->getID() ), $topic->getHTMLTitle() );

            // nr of answers
            $row[] = $topic->getNrOfPosts() - 1;

            // first post
            $row[] = $this->_getPostAuthorAndDate( $topic->getFirstPost() );

            // last post
            $row[] = $this->_getPostAuthorAndDate( $topic->getLastPost() );

            // only add delete-cell when user has rights to delete posts
            if ( $this->_userCanEditThisCourse() ) {
                $row[] = $this->_getForumTopicDeleteCell( $topic );
            }

            $table->addRow( $row );
        }

        // apply classes to columns to enable styling
        $table->setColumnsToClass( "nr-of-posts", array(1) );
        $table->setColumnsToClass( "first-post", array(2) );
        $table->setColumnsToClass( "last-post", array(3) );
        if ( $this->_userCanEditThisCourse() ) {
            $table->setColumnToDelete( 4 );
        }

        if ( count($topic_list) > 0 ) {
            return $table->toHTML();
        }
        else {
            return HTML::notice( _("This forum is empty. You can add new topic by using the form below.") );
        }
    }


    /**
     * Takes post and returns string with date and author name
     *
     * @access private
     * @param ForumPost $post
     * @return string formatted author name and date
     */
    function _getPostAuthorAndDate( $post )
    {
        // create string with author name
        $author = $post->getAuthor();
        $author_formatted = $author->getFullName();

        // create string with post date
        $date = $post->getCreationDate();
        $date_short = $date->format( Configuration::getShortTime() );
        $date_long = $date->format( Configuration::getLongTime() );
        $date_formatted = HTML::abbr( $date_short, $date_long );

        return "$date_formatted<br />$author_formatted";
    }


    /**
     * Creates a link to delete specified topic
     *
     * @access private
     * @param ForumPost $post
     * @return string HTML fragment
     */
    function _getForumTopicDeleteCell( $topic )
    {
        // compose link URI
        $uri = $this->relativeURI( $topic->getID(), 'delete' );

        // format title of the link
        $title = sprintf( _("Delete %s"), $topic->getHTMLTitle() );

        // create the link
        return HTML::a( $uri, 'X', $title );
    }


    /**
     * Returns form for adding new topics
     *
     * @access private
     * @param int $type either NEW_TOPIC_FORM or NEW_POST_FORM
     * @return HTML_QuickForm
     */
    function _createForm( $type=NEW_TOPIC_FORM )
    {
        $form = new HTML_QuickForm();

        // static name of the current user
        $form->addElement(
            'static',
            'author',
            _("Author") . ':',
            $_SESSION['user']->getFullName()
        );

        if ( $type == NEW_TOPIC_FORM ) {
            // when creating new topic, user needs to submit the topic title
            $form->addElement(
                'text',
                'topic',
                _("Topic") . ':',
                array( 'class'=>'long-text', 'id' => 'topic' )
            );
            $form->addRule(
                'topic',
                _("You have to specify a title for the new topic."),
                'required'
            );
        }
        else {
            // when creating new post, user might like to know
            // the title of the topic, but she may not alter it
            $form->addElement(
                'static',
                'topic',
                _("Topic") . ':',
                $this->_topic->getHTMLTitle()
            );
        }

        // the large textarea for message body
        $form->addElement(
            'textarea',
            'message',
            _("Message") . ':',
            array( 'rows'=>'6', 'cols'=>'45', 'id'=>'message' )
        );
        $form->addRule(
            'message',
            _("You have to add some content to the message."),
            'required'
        );

        // submit button must have appropriate label
        // depending weather a new post of new topic is added
        if ( $type == NEW_TOPIC_FORM ) {
            $submit_label = _('Post new topic');
        }
        else {
            $submit_label = _("Add comment");
        }
        $form->addElement(
            'submit',
            'submit',
            $submit_label
        );

        return $form;
    }


    /**
     * Returns Forum posts page in HTML
     *
     * @access private
     * @return string HTML fragment
     */
    function _postsPage()
    {
        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        // manage the form
        $form = $this->_createForm( NEW_POST_FORM );
        if ( $form->validate() ) {
            // if form is submitted correctly then add new post
            $this->_addPost(
                $form->exportValue( 'message' )
            );
        }
        $form_html =
            HTML::h3( _("Add comment") ) .
            HTML_QuickForm_Renderer_VIKO::render( $form );

        // create list of forum posts
        $posts_html =
            $this->title( _("Forum topic") ) .
            $this->_listOfPosts( $this->_topic );

        // link back to the main page
        $backlink = HTML::backLink(
            $this->relativeURI(),
            _("Return to the list of forum topics"),
            STANDALONE
        );

        return $posts_html . $form_html . $backlink;
    }


    /**
     * Adds new post into currently active topic with specified content
     *
     * @access private
     * @param string $content the content of the post
     */
    function _addPost( $content )
    {
        // create new ForumPost object
        $post = new ForumPost();
        $post->setContent( $content );
        $post->setCreationDate( new Date() );
        $post->setAuthor( $_SESSION['user'] );
        $post->setTopic( $this->_topic );

        // save it into database
        $post->save();

        // refresh the page
        $this->redirectInsideCourseModule( $this->_topic->getID() ); 
		exit();
    }


    /**
     * Returns table with posts from forum topic
     *
     * @access private
     * @param FormTopic $topic
     * @return string HTML fragment
     */
    function _listOfPosts( $topic )
    {
        $table = new VikoTable();

        // create table header
        $header = array(
            _("Author"),
            _("Message")
        );
        if ( $this->_userCanEditThisCourse() ) {
            $header[] = HTML::abbr( 'X', _("Delete post") );
        }
        $table->addHeader( $header );

        // init vars needed when creating table body
        $first_post = $topic->getFirstPost();
        $first_post_author = $first_post->getAuthor();
        $post_list = $topic->getAllPosts( $this->_course );

        // create table body
        foreach ( $post_list as  $post ) {

            // create the row for appending to the table
            $row = array();
            $row[] = $this->_getForumPostAuthorCell( $post );
            $row[] = $this->_getForumPostContentCell( $topic, $post, $first_post );
            // only add delete-cell when user has rights to delete posts
            if ( $this->_userCanEditThisCourse() ) {
                $row[] = $this->_getForumPostDeleteCell( $post );
            }

            // Add the row to table:
            // if the post was created by active user assign class 'post-of-user',
            // if this is the post of topic author assign class 'post-of-topic-author'
            $post_author = $post->getAuthor();
            if ( $post_author->getID() == $_SESSION['user']->getID() ) {
                $table->addRow( $row, 'post-of-user' );
            }
            elseif ( $post_author->getID() == $first_post_author->getID() ) {
                $table->addRow( $row, 'post-of-topic-author' );
            }
            else {
                $table->addRow( $row );
            }
        }

        // If user has rights to delete posts,
        // we need to say that last column contains delete-links
        if ( $this->_userCanEditThisCourse() ) {
            $table->setColumnToDelete( 2 );
        }

        return $table->toHTML();
    }


    /**
     * Extracts author and date from post and returns as HTML string
     *
     * @access private
     * @param ForumPost $post
     * @return string HTML fragment
     */
    function _getForumPostAuthorCell( $post )
    {
        $author = $post->getAuthor();
        $author_link = HTML::p( $author->getUserLink() );

        // format the date as another paragraph
        $date = $post->getCreationDate();
        $date_formatted = HTML::p( HTML::abbr(
            $date->format( Configuration::getShortTime() ),
            $date->format( Configuration::getLongTime() )
        ) );

        return $author_link . $date_formatted;
    }


    /**
     * Extracts content from post and returns as HTML string
     *
     * @access private
     * @param ForumTopic $topic
     * @param ForumPost $post
     * @param ForumPost $firstPost
     * @return string HTML fragment
     */
    function _getForumPostContentCell( $topic, $post, $first_post )
    {
        // format the post content in XHTML
        $content = $post->getHTMLContent();

        // if we are dealing with the first post,
        // insert topic title at the beginning of the content as h3
        if ( $post->getID() == $first_post->getID() ) {
            $content = HTML::h3( $topic->getHTMLTitle() ) . $content;
        }

        return $content;
    }

    /**
     * Creates a link to delete specified post
     *
     * @access private
     * @param ForumPost $post
     * @return string HTML fragment
     */
    function _getForumPostDeleteCell( $post )
    {
        // compose link URI
        $uri = $this->relativeURI( $this->_topic->getID(), $post->getID(), 'delete' );

        // format title of the link
        $author = $post->getAuthor();
        $author_name = $author->getFullName();
        $title = _("Delete post of %personname%");
        $title = str_replace( '%personname%', $author_name, $title );

        // create the link and place it inside paragraph
        return HTML::p(
            HTML::a( $uri, 'X', $title )
        );
    }


}


?>