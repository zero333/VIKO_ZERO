<?php

/**
 * Contains the Hometasks class
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

require_once 'lib/Module.php';
require_once 'lib/VikoTable.php';
require_once 'lib/Lesson.php';

/**
 * Displays list of hometask for students
 *
 */
class Module_Hometasks extends Module {


    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "hometasks";
    }


    /**
     * Display info about hometasks
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // create table containing all the homework assignments,
        // that are either scheduled into future or are not older
        // than 2 weeks.

        $html = $this->title( _("Hometasks") );

        $past_tasks = $this->_recentHometasksTable();
        $future_tasks = $this->_futureHometasksTable();

        if ( $past_tasks || $future_tasks ) {
            if ( $past_tasks ) {
                $html .= HTML::h3( _("Past hometasks (not older than two weeks):") );
                $html .= $past_tasks;
            }
            if ( $future_tasks ) {
                $html .= HTML::h3( _("Future hometasks:") );
                $html .= $future_tasks;
            }
        }
        else {
            $html .= HTML::notice( _("There are no hometasks in near past or in future.") );
        }

        $html.= HTML::backLink( $this->URI("my-viko"), _("Return to the main page"), STANDALONE );

        return $html;
    }


    /**
     * Returns table, containing past hometasks, that are not older than two weeks
     *
     * Hometasks of current day are considered past (because usually the student
     * does her homework at least a day before).
     *
     * @access private
     * @return string HTML table
     */
    function _recentHometasksTable()
    {
        return $this->_hometasksTable(
            "hometask_date  BETWEEN  ( CURRENT_DATE() - INTERVAL 14 DAY )  AND  CURRENT_DATE()"
        );
    }


    /**
     * Returns table, containing all future hometasks, excluding todays hometasks
     *
     * @access private
     * @return string HTML table
     */
    function _futureHometasksTable()
    {
        return $this->_hometasksTable( "hometask_date > CURRENT_DATE()" );
    }


    /**
     * Returns table, containing hometasks
     *
     * @access private
     * @param string $filter  SQL condition, that filters out hometasks
     * @return mixed HTML table; false if no hometasks found
     */
    function _hometasksTable( $filter )
    {
        $table = new VikoTable();

        $table->addHeader(
            _("Term"),
            _("Course name"),
            _("Hometask")
        );

        $db = DBInstance::get();
        $result = $db->query(
            "SELECT
                Courses.course_id AS course_id,
                course_name,
                hometask,
                hometask_date
            FROM
                Lessons
                JOIN
                Courses USING (course_id)
                JOIN
                Courses_Users USING (course_id)
            WHERE
                Courses_Users.user_id=?
                AND
                LENGTH(hometask) > 0
                AND
                ( $filter )
            ORDER BY
                hometask_date
            ",
            array( $_SESSION['user']->getID() )
        );

        // If we get error here, we die.
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        // when not a one homework was found, return false instead of table
        if ( $result->numRows() == 0 ) {
            return false;
        }

        while ( $result->fetchInto( $task ) ) {
            $course = new Course();
            $course->setID( $task['course_id'] );
            $course->setName( $task['course_name'] );

            $lesson = new Lesson();
            $lesson->setHometask( $task['hometask'] );

            $hometask_date = new Date( $task['hometask_date'] );
            $ht_date_short = $hometask_date->format( Configuration::getShortDate() );
            $ht_date_long = $hometask_date->format( Configuration::getLongDate() );

            $table->addRow(
                HTML::p( HTML::abbr( $ht_date_short, $ht_date_long ) ),
                HTML::p( HTML::a( $this->URI($course->getID()), $course->getHTMLName() ) ),
                $lesson->getHTMLHometask()
            );
        }

        return $table->toHTML();
    }


}


?>