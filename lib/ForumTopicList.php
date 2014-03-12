<?php

/**
 * Contains the ForumTopicList class
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
 * This class produces lists of ForumTopic instances
 */
require_once 'ForumTopic.php';


/**
 * Routines for retrieving list of forum topics for forums in viko
 *
 */
class ForumTopicList
{

    /**
     * Returns all the forum topics of specified course
     *
     * The topics are ordered so, that the topics with most recent posts
     * are at the beginning and topics with oldest posts at the end.
     *
     * @param Course $course the course
     *
     * @return array forum topics
     */
    static function getFromCourse( $course )
    {
        // ensure that Course ID is specified
        if ( !( $course->getID() > 0 ) ) {
            trigger_error( "No Course ID specified when requesting associated forum topics.", E_USER_ERROR );
        }

        // get the topics in correct order
        $db = DBInstance::get();
        $result = $db->query(
            "SELECT
                Posts.topic_id,
                MAX(addtime) as update_time,
                topic_title,
                course_id
            FROM
                Topics
                JOIN
                Posts USING (topic_id)
            WHERE
                course_id=?
            GROUP BY
                Posts.topic_id
            ORDER BY
                update_time DESC
            ",
            $course->getID()
        );

        // If we get error here, we die.
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        // convert retreved rows into list of ForumTopic objects
        $all_topics = array();
        while ( $result->fetchInto( $record ) ) {
            $topic = new ForumTopic( $record['topic_id'] );
            $topic->readFromDatabaseRecord( $record );
            $all_topics[] = $topic;
        }

        return $all_topics;
    }

}



?>
