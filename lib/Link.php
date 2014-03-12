<?php

/**
 * Contains the Link class
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
 * This is a child class of Material
 */
require_once 'Material.php';

/**
 * Link class
 *
 */
class Link extends Material
{
    /**
     * Extends the general constructor by setting material type to 'LINK'
     */
    function Link( $id )
    {
        $this->_type = "LINK";
        $this->_mime_type = "";
        $this->_size = 0;
        parent::Material( $id );
    }


    /**
     * Returns HTML link, that points to the location the link references
     *
     * @return string HTML a element
     */
    function getLink()
    {
        return HTML::element(
            "a",
            $this->getHTMLName(),
            array(
                "href" => $this->getHTMLURI(),
                "class" => "uri"
            )
        );
    }


    /**
     * Returns the path of the file or URI of the link
     *
     * @return string URI
     */
    function getURI()
    {
        return $this->getAttribute("_uri");
    }


    /**
     * Sets the path of the file or URI of the link
     *
     * @param string $uri URI
     */
    function setURI( $uri )
    {
        $this->_uri = $uri;
    }


    /**
     * Returns URI that is HTML-formatted
     *
     * @return string URI that is ready for inserting into HTML
     */
    function getHTMLURI()
    {
        return htmlspecialchars( $this->getURI(), ENT_QUOTES );
    }


    /**
     * Returns translated string describing the type of material
     *
     * @return string
     */
    function getTypeName()
    {
        return _("Link");
    }
}



?>
