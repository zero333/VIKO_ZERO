<?php
/**

 * Contains the Embed class
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
 * @author     Argo Ilves <argoi@tlu.ee>
 * @copyright  2001-2009 VIKO team and contributors
 * @license    http://www.gnu.org/licenses/gpl.html  GPL 2.0
 */

/**
 * This is a child class of Material
 */
require_once 'Material.php';
/**
 * Folder class
 *
 */
class Embed extends Material
{
    /**
     * Extends the general constructor by setting material type to 'FOLDER'
     */
    function Embed( $id )
    {
        $this->_type = "EMBED";
        $this->_mime_type ="";
        $this->_uri = "";
        $this->_size = 0;

        parent::Material( $id );
    }
    function relativeURI()
    {
        // start the path with Course ID and module ID
        $uri = '/' . $this->_course->getID() . '/' . $this->getID();

        // append all the supplied arguments to the path,
        // separated with slashes
        $args = func_get_args();
        foreach ( $args as $token ) {
            $uri .= '/' . $token;
        }

        return $uri;
    }

    /**
     * Returns HTML link, that points to the embed material form
     *
     * @return string HTML a element
     */
	 
    function getLink()
    {
        $course = $this->getCourse();
        return HTML::element(
            "a",
            $this->getHTMLName(),
            array(
                "href" => "/" . $course->getID() . "/materials/show-material/" . $this->getID(),
                "class" => "webpage-file"
            )
        );

    }

    /**
     * Returns translated string describing the type of material
     *
     * @return string
     */
    function getTypeName()
    {
        return _("Embed");
    }
}


?>
