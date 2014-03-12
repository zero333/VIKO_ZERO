<?php

/**
 * Contains the FormList class
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
 * This class uses a database connection
 */
require_once 'DBInstance.php';
require_once 'DBUtils.php';

require_once 'Form.php';


/**
 * Routines for retrieving list of lessons by various criteria
 *
 */
class FormList
{

    /**
     * Returns all the forms in a specified school
     *
     * @access public
     *
     * @param School $school the school
     *
     * @param array $order_by specifies the sorting order.
     *              Results are first sorted by first array element,
     *              then by second and so on - each element representing
     *              a column name. To achieve reverse sorting order write
     *              the column name as key and associate it with value "DESC".
     *              By default forms are sorted by name.
     *              See {@link DBUtils}.
     *
     * @return array lessons
     */
    static function getFromSchool( $school, $order_by = array("form_name") )
    {
        // ensure that School ID is specified
        if ( $school->getID() > 0 ) {
            $order_by_sql = DBUtils::createOrderBy( $order_by );

            // query all associated Forms
            $db = DBInstance::get();
            $result = $db->query(
                "
                SELECT
                    *
                FROM
                    Forms
                WHERE
                    school_id = ?
                $order_by_sql
                ",
                $school->getID()
            );

            // If we get error here, we die.
            if ( PEAR::isError($result) ) {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }

            // convert each retrieved record into form object
            $school_forms = array();
            while ( $result->fetchInto($row) ) {
                $form = new Form( $row['form_id'] );
                $form->readFromDatabaseRecord( $row );
                $school_forms[] = $form;
            }

            // return as array
            return $school_forms;
        }
        else {
            trigger_error( "No School ID specified when requesting associated forms.", E_USER_ERROR );
        }
    }


    /**
     * Returns an array of forms that can be used in an HTML select box
     *
     * @param School $school the school to retrieve forms from
     * @param bool $first_option_is_empty
     *             specifies weather to include empty option at the beginning of array
     * @param bool $last_options_are_pseudo_forms
     *             specifies weather to include teacher and schooladmin pseudo-forms
     * @return array
     */
    static function getFormsSelectBox(
        $school,
        $first_option_is_empty=false,
        $last_options_are_pseudo_forms=false )
    {
        $form_list = FormList::getFromSchool( $school );

        if ( $first_option_is_empty ) {
            $options = array( "0" => "" );
        }
        else {
            $options = array();
        }

        foreach ( $form_list as $form ) {
            $options[ $form->getID() ] = $form->getName();
        }

        if ( $last_options_are_pseudo_forms ) {
            $options["teacher"] = _("Teachers");
            $options["schooladmin"] = _("School administrators");
        }

        return $options;
    }
}



?>
