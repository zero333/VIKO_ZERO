<?php

/**
 * Contains the SchoolsStatistics class
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

require_once 'VikoTable.php';
require_once 'DBUtils.php';
require_once 'HTML.php';

/**
 * Produces statistics of all schools in VIKO
 */
class SchoolsStatistics
{
    /**
     * Database connection object
     */
    var $db;

    /**
     * Current module object
     */
    var $module;

    /**
     * Constructor
     *
     * @param object $database_connection
     * @param object $module  current module
     */
    function SchoolsStatistics( $database_connection, $module ) {
        $this->db = $database_connection;
        $this->module = $module;
    }

    /**
     * Returns HTML table with all schools in VIKO
     *
     * @access private
     * @return string HTML
     */
    function makeTablePage()
    {
        $schools_data = $this->getSchoolsData();

        if ( count($schools_data) == 0 ) {
            return HTML::notice( _("There are no schools in VIKO. Create new school using the link below.") );
        }

        $html = $this->makeTable( $schools_data );

        $html.= $this->makeFooterText($schools_data);

        return $html;
    }

    function getSchoolsData()
    {
        $orderby = DBUtils::createOrderBy( array( 'school_name' ) );
        $result = $this->db->query("SELECT school_id, school_name FROM Schools $orderby");

        // create array of all the schools as objects
        $all_schools = array();
        while ( $result->fetchInto( $school_r ) ) {
            $school = new School( $school_r['school_id'] );
            $school->setName( $school_r['school_name'] );

            $all_schools[] = $school;
        }

        return array_map( array($this, "calculateSchoolData"), $all_schools );
    }

    function makeTable( $schools_data )
    {
        $table = $this->startTable();

        foreach ($schools_data as $school) {
            $this->addSchoolToTable($school, $table);
        }

        $this->addFooterToTable($schools_data, $table);

        $table->setColumnsToNumeric( array(1,2,3,4), TABLE_BODY_AND_FOOTER );
        $table->setColumnToDelete( 5 );

        return $table->toHTML();
    }

    function makeFooterText($schools_data)
    {
        $nr_of_schools = count($schools_data);
        $nr_of_active_schools = array_reduce( $schools_data, array($this, "countActiveSchools"), 0 );

        $schools_text = _("Total %nr1% schools in VIKO. %nr2% of these having at least 10 students.");
        $schools_text = str_replace( '%nr1%', HTML::strong( $nr_of_schools ), $schools_text );
        $schools_text = str_replace( '%nr2%', HTML::strong( $nr_of_active_schools ), $schools_text );

        return HTML::p( $schools_text );
    }

    function calculateSchoolData($school)
    {
        $record = array();

        $record['link'] = $school->getLink();
        $record['delete_link'] = $school->getDeleteLink();
        $record['teacher_count'] = $school->getNrOfTeachers();
        $record['student_count'] = $school->getNrOfStudents();
        $record['course_count'] = $school->getNrOfCourses();
        $record['total_filesize'] = $school->getTotalSizeOfFiles();

        return $record;
    }


    function addSchoolToTable($school, $table)
    {
        $filesize_in_megabytes = round( $school['total_filesize'] / 1024 / 1024 ) . " MB";

        $table->addRow(
            $school['link'],
            $school['teacher_count'],
            $school['student_count'],
            $school['course_count'],
            $filesize_in_megabytes,
            $school['delete_link']
        );
    }

    function sumTeachers($total, $school)
    {
        return $total + $school['teacher_count'];
    }

    function sumStudents($total, $school)
    {
        return $total + $school['student_count'];
    }

    function sumCourses($total, $school)
    {
        return $total + $school['course_count'];
    }

    function sumFilesize($total, $school)
    {
        return $total + $school['total_filesize'];
    }

    /**
     * Count schools with at least 10 students
     */
    function countActiveSchools($active, $school)
    {
        if ( $school['student_count'] >= 10 ) {
            return $active + 1;
        }
        else {
            return $active;
        }
    }

    function addFooterToTable($schools_data, $table)
    {
        $total_teachers = array_reduce( $schools_data, array($this, "sumTeachers"), 0 );
        $total_students = array_reduce( $schools_data, array($this, "sumStudents"), 0 );
        $total_courses  = array_reduce( $schools_data, array($this, "sumCourses"),  0 );
        $total_files  = array_reduce( $schools_data, array($this, "sumFilesize"), 0 );
        $total_files  = round( $total_files / 1024 / 1024 ) . " MB";

        $table->addFooter(
            _("Total"),
            $total_teachers,
            $total_students,
            $total_courses,
            $total_files,
            ""
        );
    }

    function startTable()
    {
        $table = new VikoTable( _("List of all schools in VIKO server.") );
        $table->addHeader(
            _("School name"),
            _("Nr of teachers"),
            _("Nr of students"),
            _("Nr of courses"),
            _("Size of files"),
            HTML::abbr( "X", _("Delete school") )
        );

        return $table;
    }
}

?>