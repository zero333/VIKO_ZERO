<?php

/**
 * Contains a class for creating HTML elements in VIKO
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
 * Indicates that the actionLink should create only the li element
 */
define( "NOT_STANDALONE", 0 );

/**
 * Indicates that the actionLink should wrap li inside actionList
 */
define( "STANDALONE", 1 );


/**
 * Provides methods for generating HTML strings
 *
 * All the methods in this class, that craete some specific
 * HTML element have a name of that HTML element. For example
 * HTML::a creates a (anchor) element. If you want to create
 * a element for which this class provides no special method
 * you can use the general HTML::element method.
 *
 * The methods in this class only cover the creation of simple
 * HTML elements. For something as complex as tables and forms
 * you should use some other class instead.
 *
 * Note! None of the methods escapes the special HTML characters
 * &, <, >, " and '. This should be done before passing text to
 * these methods. This might sound like a shortcoming, but it is
 * actually very practical, because it's impossible to determine
 * when the special characters need to be escaped and when you
 * want to place one HTML element inside another. This allows you
 * to nest calls for methods of this class, e.g.:
 *
 * <pre>
 * HTML::p( HTML:a( "http://www.example.com", "A link inside a paragraph." ) );
 * </pre>
 *
 * Besides general-purpose HTML methods, this class also contains
 * VIKO-specific methods e.g. error and notice.
 */
class HTML
{
    /**
     * Creates HTML element with specified name and attributes
     *
     * The element is created using XHTML syntax: if it has no content,
     * then shorthand tag <.../> is created. Although when content
     * is empty string "" then both start and end tags are created.
     *
     * This method is used by all other methods of this class to
     * create specific HTML elements.
     *
     * @static
     * @param string $name       name of the element
     * @param string $content    content of the element
     * @param array $attributes  attributes for the element
     * @return string            HTML element
     */
    static function element( $name, $content=null, $attributes=null )
    {
        // begin the start tag
        $element = "<$name";

        // if there are attributes defined, loop through all of them
        // and append to the start tag
        if ( isset($attributes) ) {
            foreach ( $attributes as $attr => $value ) {
                $element .= " $attr=\"$value\"";
            }
        }

        if ( isset($content) ) {
            // the element has content:
            // close the start tag, add content and append end tag
            $element .= ">";
            $element .= $content;
            $element .= "</$name>";
        }
        else {
            // no content exists - use shorthand ending
            $element .= " />";
        }

        return $element;
    }


    /**
     * Creates anchor element (link)
     *
     * @static
     * @param string $uri      address of the link
     * @param string $content  text to display as the link
     * @param string $title    text for the title of the link
     * @return string          HTML a element
     */
    static function a( $uri, $content, $title=null )
    {
        // all links must have href attribute
        $attributes = array( "href" => $uri );

        // title attribute is optional
        if ( isset($title) ) {
            $attributes["title"] = $title;
        }

        return HTML::element( "a", $content, $attributes );
    }


    /**
     * Creates abbrevation
     *
     * Currently this method creates acronym element not abbr.
     * This is because IE6 does not support the latter.
     *
     * @static
     * @param string $content  the abbreviated word
     * @param string $title    explanation for the abbreviation
     * @return string          HTML acronym element
     */
    static function abbr( $content, $title=null )
    {
        // title attribute is optional
        if ( isset($title) ) {
            $attributes = array( "title" => $title );
        }
        else {
            $attributes = null;
        }

        return HTML::element( "acronym", $content, $attributes );
    }


    /**
     * Creates strong emphasis
     *
     * @static
     * @param string $content     the content of the element
     * @param array $attributes   list of attributes
     * @return string             HTML strong element
     */
    static function strong( $content, $attributes=null )
    {
        return HTML::element( "strong", $content, $attributes );
    }


    /**
     * Creates element strong with class "error"
     *
     * @static
     * @param string $content     the content of the element
     * @param bool $in_paragraph  should the strong element be inside p
     * @return string             HTML strong element
     */
    static function error( $content, $in_paragraph=true )
    {
        $strong = HTML::strong( $content, array( "class" => "error" ) );

        if ( $in_paragraph ) {
            return HTML::p( $strong );
        }
        else {
            return $strong;
        }
    }


    /**
     * Creates element strong with class "notice"
     *
     * @static
     * @param string $content     the content of the element
     * @param bool $in_paragraph  should the strong element be inside p
     * @return string             HTML strong element
     */
    static function notice( $content, $in_paragraph=true )
    {
        $strong = HTML::strong( $content, array( "class" => "notice" ) );

        if ( $in_paragraph ) {
            return HTML::p( $strong );
        }
        else {
            return $strong;
        }
    }


    /**
     * Creates heading and body of general error message page
     *
     * The returned HTML will contain a h2 heading with text "Error!".
     * The message will be wrapped inside strong with class="error" and
     * inside p (paragraph).
     *
     * @static
     * @param string $error_message  descriptive text about the error
     * @return string
     */
    static function errorPage( $error_message )
    {
        return
            HTML::h2( _( "Error!" ) ) .
            HTML::error( $error_message );
    }


    /**
     * Creates heading and body of access denied type of error page
     *
     * The returned HTML will contain a h2 heading with text "Access denied!".
     * The message will be wrapped inside strong with class="error" and
     * inside p (paragraph).
     *
     * @static
     * @param string $error_message  descriptive text about the error
     * @return string
     */
    static function accessDeniedPage( $error_message )
    {
        return
            HTML::h2( _( "Access denied!" ) ) .
            HTML::error( $error_message );
    }


    /**
     * Creates paragraph
     *
     * @static
     * @param string $content     the content of the element
     * @param array $attributes   list of attributes
     * @return string             HTML p element
     */
    static function p( $content, $attributes=null )
    {	
		$content = preg_replace('/<p[^>]*>/', '', $content); // Removes the start <p> or <p attr=""> added by tinyMCE
		$content = preg_replace('/<\/p>/', '<br />', $content); // Replaces the end tag

        return HTML::element( "p", $content, array( "class" => "p" ) );
    }


    /**
     * Creates h1 heading
     *
     * @static
     * @param string $content     the content of the element
     * @param array $attributes   list of attributes
     * @return string             HTML h1 element
     */
    static function h1( $content, $attributes=null )
    {
        return HTML::element( "h1", $content, $attributes );
    }


    /**
     * Creates h2 heading
     *
     * @static
     * @param string $content     the content of the element
     * @param array $attributes   list of attributes
     * @return string             HTML h2 element
     */
    static function h2( $content, $attributes=null )
    {
        return HTML::element( "h2", $content, $attributes );
    }


    /**
     * Creates h3 heading
     *
     * @static
     * @param string $content     the content of the element
     * @param array $attributes   list of attributes
     * @return string             HTML h3 element
     */
    static function h3( $content, $attributes=null )
    {
        return HTML::element( "h3", $content, $attributes );
    }


    /**
     * Creates list item
     *
     * @static
     * @param string $content     the content of the element
     * @param array $attributes   list of attributes
     * @return string             HTML li element
     */
    static function li( $content, $attributes=null )
    {
        return HTML::element( "li", $content, $attributes );
    }


    /**
     * Creates multiple elements of the same type
     *
     * @static
     * @param string $name          name of the element type
     * @param array $content_items  content for each element
     * @return string               HTML elements
     */
    static function multiElement( $name, $content_items )
    {
        $html = "";
        foreach ( $content_items as $content ) {
            $html .= HTML::element( $name, $content );
        }
        return $html;
    }


    /**
     * Creates unordered list
     *
     * This method takes variable number of arguments,
     * each argument will be rendered as a list item.
     *
     * @static
     * @return string  HTML ul element
     */
    static function ul()
    {
        $items = func_get_args();
        return HTML::element( "ul", HTML::multiElement( "li", $items ) );
    }


    /**
     * Creates ordered list
     *
     * This method takes variable number of arguments,
     * each argument will be rendered as a list item.
     *
     * @static
     * @return string  HTML ol element
     */
    static function ol()
    {
        $items = func_get_args();
        return HTML::element( "ol", HTML::multiElement( "li", $items ) );
    }


    /**
     * Creates definition list
     *
     * This method takes variable number of arguments,
     * all the arguments are concatenated together into
     * a string of element content.
     *
     * @static
     * @return string  HTML dl element
     */
    static function dl()
    {
        $items = func_get_args();
        $content = implode( "", $items );
        return HTML::element( "dl", $content );
    }


    /**
     * Creates definition term
     *
     * @static
     * @param string $content     the content of the element
     * @param array $attributes   list of attributes
     * @return string             HTML dt element
     */
    static function dt( $content, $attributes=null )
    {
        return HTML::element( "dt", $content, $attributes );
    }


    /**
     * Creates definition data / description
     *
     * @static
     * @param string $content     the content of the element
     * @param array $attributes   list of attributes
     * @return string             HTML dd element
     */
    static function dd( $content, $attributes=null )
    {
        return HTML::element( "dd", $content, $attributes );
    }


    /**
     * Creates ul with class="actions"
     *
     * This method takes variable number of arguments,
     * all the arguments are concatenated together into
     * a string of element content.
     *
     * (There may be several actionlists per page - that is why class is used,
     * instead of ID.)
     *
     * @static
     * @return string             HTML ul element
     */
    static function actionList( $content )
    {
        $items = func_get_args();
        $content = implode( "", $items );
        return HTML::element( "ul", $content, array( "class" => "actions" ) );
    }


    /**
     * Creates list item with class="action-foo" containing one link
     *
     * "foo" in the class name is the value of $action parameters.
     *
     * If $type is set to STANDALONE, the list item is wrapped inside
     * actionList.
     *
     * @static
     * @param string $uri     address of the link
     * @param string $label   text on the link
     * @param string $action  name of the action
     * @param string $type    shall we craete li or full ul
     * @return string         HTML li element with class="action-back"
     */
    static function actionLink( $uri, $label, $action, $type=NOT_STANDALONE )
    {
        $li = HTML::li( HTML::a( $uri, $label ), array( "class" => "action-$action" ) );

        if ( $type == STANDALONE ) {
            return HTML::actionList( $li );
        }
        else {
            return $li;
        }
    }


    /**
     * Creates list item with class="action-back" containing one link
     *
     * @static
     * @param string $uri     address of the link
     * @param string $label   text on the link
     * @param string $type    shall we craete li or full ul
     * @return string         HTML li element with class="action-back"
     */
    static function backLink( $uri, $label, $type=NOT_STANDALONE )
    {
        return HTML::actionLink( $uri, $label, "back", $type );
    }


    /**
     * Creates list item with class="action-new" containing one link
     *
     * @static
     * @param string $uri     address of the link
     * @param string $label   text on the link
     * @param string $type    shall we craete li or full ul
     * @return string         HTML li element with class="action-new"
     */
    static function newLink( $uri, $label, $type=NOT_STANDALONE )
    {
        return HTML::actionLink( $uri, $label, "new", $type );
    }


    /**
     * Creates list item with class="action-new-folder" containing one link
     *
     * @static
     * @param string $uri     address of the link
     * @param string $label   text on the link
     * @param string $type    shall we craete li or full ul
     * @return string         HTML li element with class="action-new-folder"
     */
    static function newFolderLink( $uri, $label, $type=NOT_STANDALONE )
    {
        return HTML::actionLink( $uri, $label, "new-folder", $type );
    }
}



?>
