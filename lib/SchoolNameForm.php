<?php

/**
 * Contains the SchoolNameForm class
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

require_once 'lib/School.php';
require_once 'lib/DBInstance.php';
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';

/**
 * Creates a form for editing school name
 */
class SchoolNameForm {

    /**
     * Creates an HTML form for adding or editing a school
     *
     * If parameter $school is provided, then a for for editing
     * the name of an existing school is created. Otherwise the
     * form for adding new school is returned.
     *
     * @access public
     * @static
     * @param School $school the school to be modified (optional)
     * @return Form new form object
     */
    function createForm( $school=null )
    {
        $form = new HTML_QuickForm();

        $form->addElement(
            'text',
            'school-name',
            _('School name') . ':',
            array( 'class'=>'long-text', 'id'=>'school-name' )
        );

        if ( isset($school) ) {
            $form->addElement('submit', 'submit', _('Save') );
        }
        else {
            $form->addElement('submit', 'submit', _('Add school') );
        }

        $form->addRule(
            'school-name',
            _("This field is required"),
            'required'
        );
        $form->addRule(
            'school-name',
            _("School name may not only consist of spaces."),
            'regex',
            '/ [^\s] /x'
        );
        $form->registerRule('uniqueSchoolName', 'callback', '_checkUniquenessOfSchoolName', 'SchoolNameForm');
        $form->addRule(
            'school-name',
            _("This name is already in use - please choose another."),
            'uniqueSchoolName'
        );

        if ( isset($school) ) {
            $form->setDefaults( array( "school-name" => $school->getName() ) );
        }

        return $form;
    }


    /**
     * Checks if school name is unique
     *
     * If the school name already exists, returns false.
     * Else returns true.
     *
     * @access private
     * @param string  $school_name  school name to validate
     * @return bool
     */
    function _checkUniquenessOfSchoolName( $school_name )
    {
        $db = DBInstance::get();
        $school_name_exists = (bool) $db->getOne(
            "SELECT COUNT(school_id) FROM Schools WHERE school_name=?",
            array( $school_name )
        );

        return !$school_name_exists;
    }
}


?>