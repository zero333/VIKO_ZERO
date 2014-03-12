<?php

/**
 * Contains the Module_Forms class
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

/**
 * Shows the forms of a school
 *
 * <ul>
 * <li>/forms  -  shows list of all forms in the school of user</li>
 * <li>/forms/delete/#id  -  shows confirmation dialog about deleting form with ID #id</li>
 * <li>/forms/delete/#id/yes-please  -  actually deletes form with ID #id</li>
 * </ul>
 */
class Module_Forms extends Module {

    var $_form_to_delete=null;
    var $_please_delete_the_selected_form=false;

    /**
     * Constructs new Forms Module
     *
     * @param array $parameters  parameters to the module
     */
    function Module_Forms( $parameters )
    {
        if ( isset($parameters[0]) && $parameters[0]=='delete' ) {
            if ( isset($parameters[1]) ) {
                $this->_form_to_delete = new Form();
                $this->_form_to_delete->setID( (int)$parameters[1] );
            }
            else {
                trigger_error("No form ID specified when deleting.", E_USER_ERROR);
            }
        }

        if ( isset($parameters[2]) && $parameters[2]=='yes-please' ) {
            $this->_please_delete_the_selected_form = true;
        }
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
        return "forms";
    }


    /**
     * Creates HTML content of the page
     *
     * @return string HTML content
     */
    function toHTML()
    {
        // only Schooladmins are allowed to add/edit forms
        if ( !$_SESSION['user']->hasAccessToGroup("SCHOOLADMIN") ) {
            return $this->accessDeniedPage( _("You are not authorized to add or edit forms.") );
        }

        if ( isset($this->_form_to_delete) ) {
            // form must exist
            if ( !$this->_form_to_delete->loadFromDatabaseByID() ) {
                return $this->errorPage( _("The selected form does not exist.") );
            }

            // form must belong to the school where the user is
            $form_school = $this->_form_to_delete->getSchool();
            $user_school = $_SESSION['user']->getSchool();
            if ( $form_school->getID() != $user_school->getID() ) {
                return $this->accessDeniedPage( _("You are not authorized to delete this form.") );
            }

            // a form may be easily deleted, when there are no students,
            // otherwise the user must say please to delete this form.
            $nr_of_students = count( StudentList::getFromForm( $this->_form_to_delete ) );
            if ( $this->_please_delete_the_selected_form || $nr_of_students == 0 ) {
                return
                    $this->title( _("Forms") ) .
                    $this->_deleteForm( $this->_form_to_delete );
            }
            else {
                return $this->_confirmDeleteForm( $this->_form_to_delete, $nr_of_students );
            }
        }
        else {
            return
                $this->title( _("Forms") ) .
                $this->_formsList() .
		        HTML::actionList(
				HTML::backLink( $this->URI("my-viko"), _("Return to the main page") ), 
 	            HTML::newLink( $this->URI('form', 'add'), _("Add form") )                 );
        }
    }


    /**
     * Deletes the form and shows a list of remaining forms
     *
     * Also shows a notice about which form was deleted.
     *
     * @access private
     * @param Form $form the form to delete
     * @return string HTML fragment
     */
    function _deleteForm( $form )
    {
        $message = sprintf( _("Form %s was successfully deleted."), $form->getHTMLName() );
        $html = HTML::notice( $message );

        // the real deletion of the form
        $form->delete();

        $html .= $this->_formsList();
        return $html;
    }


    /**
     * Returns a confirmation dialog for deleting the form
     *
     * Also shows the number of students in the form that will be along with the form.
     *
     * @access private
     * @param Form $form the form to delete
     * @param int $nr_of_students the number of students the supplied form
     * @return string HTML fragment
     */
    function _confirmDeleteForm( $form, $nr_of_students )
    {
        $title = sprintf( _("Are you sure you want to delete form %s?"), HTML::strong( $form->getHTMLName() ) );
        $html = $this->title( $title );

        // notice about how many students there are on the form
        $nr_of_students = count( StudentList::getFromForm( $form ) );
        $message = ngettext(
            "There is %d student in this form. This student will be also deleted.",
            "There are %d students in this form. All these students will be also deleted.",
            $nr_of_students
        );
        $message = sprintf( $message, $nr_of_students );
        $html .= HTML::p( $message );

        // confirmation link to really delete the form
        $html .= HTML::p( HTML::a(
            '/forms/delete/' . $form->getID() . '/yes-please',
 			$this->URI('forms','delete', $form->getID(), 'yes-please') 
		));

        return $html;
    }


    /**
     * Table with all the forms in the school
     *
     * @access private
     * @return string HTML
     */
    function _formsList()
    {
        $table = new VikoTable( _("List of all forms in this school") );
        $table->addHeader(
            _("Form"),
            _("Change form name"),
            _("Nr of students"),
            HTML::abbr( "X", _("Delete form") )
        );

        $total_students = 0;

        $form_result = $this->_retrieveFormRecords( $_SESSION['user']->getSchool() );

        if ( $form_result->numRows() == 0 ) {
            return HTML::notice( _("There are no forms in this school.") );
        }

        while ( $form_result->fetchInto( $form ) ) {


            // link which takes you to the list of students in that form
            $form_link = HTML::a(
                $this->URI('students', $form["form_id"]),
                $form["form_name"]
            );

            // link that deletes the form
            $delete_title = sprintf( _("Delete %s"), $form["form_name"] );
            $form_delete_link = HTML::a(
                $this->URI('forms', 'delete', $form["form_id"]),
                "X",
                $delete_title
            );
            $form_edit_link = HTML::a(
                $this->URI('form', 'edit', $form["form_id"]), 
                _("Edit")
            );

            $table->addRow(
                $form_link,
                $form_edit_link,
                $form["nr_of_students"],
                $form_delete_link
            );

            $total_students += $form["nr_of_students"];
        }

        $table->addFooter(
            _("Total"),
            "",
            $total_students,
            ""
        );

        $table->setColumnsToNumeric( array(2), TABLE_BODY_AND_FOOTER );
        $table->setColumnToDelete( 3 );

        return $table->toHTML();
    }


    /**
     * Retrieves form names, ID-s and student counts from database
     *
     * @param School $school
     * @return DB_result
     */
    function _retrieveFormRecords( $school )
    {
        $order_by = DBUtils::createOrderBy( array( 'form_name' ) );
        $db = DBInstance::get();
        $result = $db->query(
            "SELECT
                Forms.form_id,
                form_name,
                COUNT(user_id) AS nr_of_students
            FROM
                Forms
                LEFT JOIN
                Users USING ( form_id )
            WHERE
                Forms.school_id = ?
            GROUP BY
                Forms.form_id
            $order_by",
            $school->getID()
        );

        // If we get error here, we die.
        if ( PEAR::isError($result) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }

        return $result;
    }
}


?>