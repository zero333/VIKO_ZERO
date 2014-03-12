<?php

/**
 * Contains the Module_SchoolEdit class
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
require_once 'lib/SchoolNameForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';

/**
 * Editing of school names
 *
 * <ul>
 * <li>/school-edit/#school_id - change the name of school with ID #school_id</li>
 * </ul>
 */
class Module_SchoolEdit extends Module {

    /**
     * The school to be modified
     */
    var $_school = null;

    /**
     * Constructs new SchoolEdit Module
     *
     * @param array $parameters  parameters to the module
     */
    function Module_SchoolEdit( $parameters )
    {
        if ( isset($parameters[0]) && (int)$parameters[0] > 0 ) {
            $this->_school = new School( (int)$parameters[0] );
        }
        else {
            // fatal error: no school_id specified
            trigger_error("No school ID specified when editing school.", E_USER_ERROR);
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
        return "school-edit";
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

        // when editing, the school must exist
        if ( !$this->_school->loadFromDatabaseByID() ) {
            return $this->errorPage( _("The school you are trying to access does not exist.") );
        }

        return $this->_editSchool();
    }


    /**
     * Page for changing school name
     *
     * @access private
     * @return string HTML fragment
     */
    function _editSchool()
    {
        $html_form = SchoolNameForm::createForm( $this->_school );

        // translate the title
        $html = $this->title( _("Change school name") );

        if ( $html_form->validate() ) {
            $this->_saveSchool(
                $html_form->exportValue('school-name')
            );

            $html.= HTML::p( HTML::notice( _("School name successfully changed.") ) );
        }
        else {
            // convert form to HTML
            $html .= HTML_QuickForm_Renderer_VIKO::render( $html_form );
        }

        // backlink
        $html .= HTML::backLink( $this->URI("statistics"), _("Return to the list of all schools"), STANDALONE );

        return $html;
    }


    /**
     * Saves the school
     *
     * @access private
     * @param string $school_name school name to use
     */
    function _saveSchool( $school_name )
    {
        $this->_school->setName( $school_name );
        $this->_school->save();
    }

}

?>