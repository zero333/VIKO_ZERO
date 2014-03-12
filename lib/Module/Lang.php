<?php

/**
 * Contains the Module_Lang class
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
require_once 'lib/Configuration.php';

/**
 * Changes language/locale, that is used by VIKO interface
 *
 * The module takes one parameter - the locale code.
 * For example "et_EE" or "ru_RU". And sets that to be the active locale.
 *
 * After locale set, sends user back to where he came from.
 */
class Module_Lang extends Module {

    /**
     * Set the locale, if given, or give error
     */
    function Module_Lang( $parameters )
    {
        if ( !isset($parameters[0]) ) {
            trigger_error("Locale not defined.", E_USER_ERROR);
        }

        if ( in_array( $parameters[0], array("et_EE", "en_US", "ru_RU") ) ) {
            Configuration::setLocale( $parameters[0] );
        }
        else {
            trigger_error("Unknown locale '$parameters[0]'.", E_USER_ERROR);
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
        return "lang";
    }


    /**
     * Send user back to were he came from
     *
     * If no HTTP_REFERER is available, send to front page.
     *
     * @return bool false
     */
    function toHTML()
    {
        if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
            header("Location: " . $_SERVER['HTTP_REFERER'] );
        }
        else {
            $this->redirect(); // go to login page 
        }

        return false;
    }

}


?>