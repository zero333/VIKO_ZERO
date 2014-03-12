<?php

/**
 * Contains the Module_SchoolDelete class
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
require_once 'lib/School.php';

/**
 * Deleting of schools
 *
 * <ul>
 * <li>/school-delete/#school_id - delete the school with ID #school_id</li>
 * <li>/school-delete/#school_id/yes-please - confirm the deleting of school</li>
 * </ul>
 */
class Module_SchoolDelete extends Module {

    /**
     * The school to be deleted
     */
    var $_school = null;

    /**
     * Has the user confirmed the deleting of school?
     */
    var $_yes_please_delete = false;

    /**
     * Constructs new FormAdd Module
     *
     * @param array $parameters  parameters to the module
     */
    function Module_SchoolDelete( $parameters )
    {
        if ( isset($parameters[0]) && (int)$parameters[0] > 0 ) {
            $this->_school = new School( (int)$parameters[0] );
        }
        else {
            // fatal error: no school_id specified
            trigger_error("No school ID specified when deleting school.", E_USER_ERROR);
        }

        if ( isset($parameters[1]) && $parameters[1] == "yes-please" ) {
            $this->_yes_please_delete = true;
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
        return "school-delete";
    }


    /**
     * Returns page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // only admins are allowed to tinker with schools
        if ( !$_SESSION['user']->hasAccessToGroup("ADMIN") ) {
            return $this->accessDeniedPage( _("You are not authorized to add, edit or delete schools.") );
        }

        // when deleting, the school must exist
        if ( !$this->_school->loadFromDatabaseByID() ) {
            return $this->errorPage( _("The school you are trying to access does not exist.") );
        }

        return $this->_deleteSchool();
    }


    /**
     * Deletes the school if user confirms the delete
     *
     * @access private
     */
    function _deleteSchool()
    {
        $html = $this->title( _("Delete school") );

        // user has to say "please" before he is allowed to delete the school
        if ( $this->_yes_please_delete ) {
            $this->_school->delete();

            $html.= HTML::p( HTML::notice( _("School successfully deleted.") ) );
        }
        else {
            $question = sprintf( _("Are you sure you want to delete school %s?"), $this->_school->getHTMLName() );
            $html.= HTML::p( $question );
            $id = $this->_school->getID();
            $html.= HTML::p( HTML::a( $this->URI("school-delete", $id, "yes-please"), _("Yes, please delete this school.") ) );
        }

        // backlink
        $html .= HTML::backLink( $this->URI("statistics"), _("Return to the list of all schools"), STANDALONE );

        return $html;
    }


}

?>