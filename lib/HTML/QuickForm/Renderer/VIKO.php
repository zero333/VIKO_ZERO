<?php

/**
 * Contains the custom VIKO QuickForm Renderer
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

require_once 'Pear/HTML/QuickForm/Renderer.php';

require_once 'lib/HTML.php';

/**
 * VIKO-specific customized QuickForm Renderer
 */
class HTML_QuickForm_Renderer_VIKO extends HTML_QuickForm_Renderer
{
    var $_html="";
    var $_row_class="odd";
    var $_end_elements=array();

    function startForm( &$form )
    {
        // set all forms to be sent as multipart/from-data
        // this enctype is really only needed for file uploads, but it doesn't hurt other forms.
        // It might introduce a small overhead, but that shouldn't matter much.
        $this->_html.='<form action="" method="post" enctype="multipart/form-data"><table>';
    }


    function finishForm( &$form )
    {
        $this->_html .= "</table><p>";
        foreach ( $this->_end_elements as $element ) {
            $this->_html .= $element->toHTML() . " ";
        }
        $this->_html .= "</p></form>";
    }


    function startGroup( &$group, $required, $error )
    {
    }


    function finishGroup( &$group )
    {
    }


    function renderElement( $element, $required, $error  )
    {
        $type = $element->getType();

        // if element is a button, store it for placing at the end of form and exit
        if ( in_array( $type, array("submit", "reset", "button") ) ) {
            $this->_end_elements[] = $element;
            return;
        }

        $form_field = $element->toHTML();
        $label = $element->getLabel();
        $name = $element->getName();

        // if field is required, set up required variable
        if ( $required ) {
            $required_text = "(" . _("required") . ")";
            $required = HTML::element(
                "span",
                $required_text,
                array( "class" => "required" )
            );
            $required = " $required";
        }
        else {
            $required = '';
        }

        // on error, set up error variables
        if ( strlen($error) > 0 ) {
            $error_message = HTML::p( HTML::strong( $error ) );
            $error_class = "error";
        }
        else {
            $error_message = "";
            $error_class = "";
        }


        if ( in_array( $type, array( "static", "checkbox" ) ) ) {
            // for static and checkbox fields, do simple rendering
            $row_content  = HTML::element("th", $label);
            $row_content .= HTML::element("td", $form_field);
        }
        else {
            // other fields need more complicated handling
            $label = HTML::element("label", $label . $required, array( "for" => $name ) );
            $row_content  = HTML::element("th", $label);
            $row_content .= HTML::element("td", $error_message . $form_field);
        }

        // alternate between even and odd rows
        $this->_row_class = ($this->_row_class == "odd") ? "even" : "odd";

        // combine table row
        $row_attributes = array( "class" => $this->_row_class . " " . $error_class );
        $row = HTML::element( "tr", $row_content, $row_attributes );

        // append row to form
        $this->_html .= $row;
    }


    function renderHeader( &$header )
    {
    }


    function renderHidden( &$element )
    {
        $this->_end_elements[] = $element;
    }

    function toHTML()
    {
        return $this->_html;
    }


    /**
     * Uses this class to render the form and output HTML
     *
     * @static
     * @param HTML_QuickForm $form the form object to render
     * @return string form in HTML format
     */
    static function render( $form )
    {
        // create new renderer
        $renderer = new HTML_QuickForm_Renderer_VIKO();

        // make the form use this renderer
        $form->accept( $renderer );

        // render form into HTML and return the HTML
        return $renderer->toHTML();
    }
}

?>