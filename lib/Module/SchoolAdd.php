<?php

/**
 * Contains the Module_School class
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
 * Adding new schools
 *
 * <ul>
 * <li>/school-add - add new school</li>
 * </ul>
 */
class Module_SchoolAdd extends Module {

    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "school-add";
    }


    /**
     * Returns page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        // only admins are allowed to add new schools
        if ( !$_SESSION['user']->hasAccessToGroup("ADMIN") ) {
            return $this->accessDeniedPage( _("You are not authorized to add, edit or delete schools.") );
        }

        return $this->_addSchool();
    }


    /**
     * Page for adding new school
     *
     * @access private
     * @return string HTML fragment
     */
    function _addSchool()
    {
        $html_form = SchoolNameForm::createForm();

        $school = new School();

        // translate the title
        $html = $this->title( _("Add school") );

        if ( $html_form->validate() ) {
            $school->setName( $html_form->exportValue('school-name') );
            $school->save();

            $html.= HTML::p( HTML::notice( _("School successfully added.") ) );
        }
        else {
            // convert form to HTML
            $html .= HTML_QuickForm_Renderer_VIKO::render( $html_form );
        }

        // backlink
        $html .= HTML::backLink( $this->URI("statistics"), _("Return to the list of all schools"), STANDALONE );

        return $html;
    }
}


?>