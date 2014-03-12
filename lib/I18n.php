<?php

/**
 * Contains the I18n class
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
 * Configuration is used to get the current locale
 */
require_once "Configuration.php";

/**
 * Methods related to internationalization
 */
class I18n
{
    /**
     * Returns contents of a file from localization directory
     *
     * This is used when a large body of text needs to be localized.
     * Just issuing _() to translate all the paragraphs is usually not
     * the best way - a localized version might not just have translated
     * text, but a totally different structure and message.
     *
     * This also gives opportunity of customization to VIKO administrators.
     * For example they can easily customize the main content area of VIKO
     * front page, as this is just a bit of HTML inside one file in locale
     * directory.
     *
     * The given filename is searched for from the following directory:
     * locale/$current_locale/LC_FILES/$filename, where $current_locale is
     * the locale specified in viko.conf and $filename, is the passed in
     * parameter to this method.
     *
     * If the file was not found, a HTML paragraph containing the error
     * message is returned.
     *
     * @access public
     * @param string $filename the name of the file with text
     * @return string contents of file or error message
     */
    static function getFileContents( $filename )
    {
        // create full filename
        $root = getcwd();
        $locale = Configuration::getLocale();
        $full_filename = "$root/locale/$locale/LC_FILES/$filename";

        $content = @file_get_contents( $full_filename );
        if ( !$content ) {
            return HTML::error( sprintf(_("Unable to load file %s."), $full_filename ) );
        }
        else {
            return $content;
        }
    }


    /**
     * Sends e-mail using UTF-8 encoding
     *
     * Taken from "Building Scalable Web Sites" of Cal Henderson.
     *
     * @access public
     * @param string $to_name     person/organisation name, who receives the e-mail
     * @param string $to_email    e-mail address of the receiver
     * @param string $subject     title of the letter
     * @param string $message     mail contents
     * @param string $from name   person/organisation name, who sent the mail
     * @param string $from_email  e-mail of the sender (where to reply)
     * @return bool success
     */
    function email( $to_name, $to_email, $subject, $message, $from_name, $from_email )
    {
        $from_name = I18n::emailEscape($from_name);
        $to_name   = I18n::emailEscape($to_name);
        $headers = "From: \"$from_name\" <$from_email>\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8";
        $subject = I18n::emailEscape($subject);

        return mail( "\"$to_name\" <$to_email>", $subject, $message, $headers );
    }


    /**
     * Encodes non-ASCII string, so that it's safe to use it in e-mail headers
     *
     * Encodes the string based on RFC 1342 (Representation of Non-ASCII Text in Internet Message Headers).
     * Function is taken from "Building Scalable Web Sites" of Cal Henderson.
     *
     * @access public
     * @param string $text  text, that possibly contains characters out of ASCII range
     * @return string encoded text
     */
    function emailEscape( $text )
    {
        if ( preg_match('/[^a-z ]/i', $text) ) {
            $text = preg_replace('/([^a-z ])/ie', 'sprintf("=%02x", ord(StripSlashes("\\1")))', $text);
            $text = str_replace(' ', '_', $text);
            return "=?utf-8?Q?$text?=";
        }
        else {
            return $text;
        }
    }

}

?>