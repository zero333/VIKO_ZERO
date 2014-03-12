<?php

/**
 * Contains the VikoContentFormatter class
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
 *
 */
require_once "PEAR/Text/Wiki/BBCode.php";


/**
 * Provides methods for transforming content in BBCode into HTML
 *
 * This class implements the 'singleton' design pattern.
 * At the moment only one method is provided: format().
 * It takes the string in BBCode and returns HTML.
 */
class VikoContentFormatter {

    /**
     * Transforms the supplied string into HTML
     *
     * @static
     * @param string $content  initial BBCode text to transform
     * @return string          resulting HTML fragment
     */
    static function format( $content )
    {
        $wiki = VikoContentFormatter::_getWiki();
        return $wiki->transform( $content, 'Xhtml' );
    }


    /**
     * Returns Wiki object, set up especially for VIKO
     *
     * @static
     * @access private
     * @return Text_Wiki_BBCode  wiki object
     */
    static function _getWiki()
    {
        static $wiki = null;
        if ( $wiki == null ) {
            $wiki = new Text_Wiki_BBCode();
            $wiki->setFormatConf('Xhtml', 'charset', 'UTF-8');
            $wiki->setFormatConf('Xhtml', 'translate', HTML_SPECIALCHARS);
            $wiki->disableRule('Smiley');
        }
        $wiki_ref = $wiki;
        return $wiki_ref;
    }
}


?>