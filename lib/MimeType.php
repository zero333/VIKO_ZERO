<?php

/**
 * Contains the MimeType class
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
 * Handles determining MIME types of files
 *
 * The functionality of this class is used to determine file type when uploading
 * files to VIKO. Although browsers send us a MIME type of uploaded file (stored
 * in $_FILES[myfile][type] ), this information can not be relied on. First of all
 * different browsers send different MIME types for the same file type. For example
 * with .rtf file one might send either 'application/rtf' or 'text/ritchtext'.
 * Some browsers might just send 'application/octet-stream' for all or most of the
 * file types.
 */
class MimeType
{

    /**
     * Determines the MIME type of given filename
     *
     * File name extension is used to determine the type.
     * If no MIME type (known to this class) matches the extension,
     * the type 'application/octet-stream' is returned.
     *
     * @static
     * @parem string $filename name of the file (path is not required, but may be given)
     * @return string MIME type
     */
    function determineFromFilename( $filename )
    {
        $default = "application/octet-stream";

        // get the extension of the file
        if ( preg_match( '/\.([^.]+)$/', $filename, $matches ) ) {
            $extension = $matches[1];
        }
        else {
            // no file type found, return default
            return $default;
        }

        // if extension matches one of the known MIME types, return the matching MIME type
        $known_types = MimeType::arrayOfKnownMIMETypes();
        if ( isset( $known_types[ $extension ] ) ) {
            return $known_types[ $extension ];
        }
        else {
            // extension is unknown, return default MIME type
            return $default;
        }
    }


    /**
     * Returns CSS class name, that should be assigned to links to files of specified type
     *
     * All the CSS class names returned end with string "-file".
     * For example image files will have class "image-file" and pdf documents
     * "pdf-document-file".
     *
     * @static
     * @param string $mime_type MIME type
     * @return string CSS class name
     */
    static function cssClass( $mime_type )
    {
        // every MIME type beginning with "image/" is definitely an image
        if ( preg_match( '/^image/', $mime_type ) ) {
            return "image-file";
        }

        // every MIME type beginning with "audio/" is definitely an audio
        // application/ogg is usually also an audio file (just a wild guess)
        if ( preg_match( '/^audio/', $mime_type ) || $mime_type == "application/ogg" ) {
            return "audio-file";
        }

        // every MIME type beginning with "video/" is definitely an video
        if ( preg_match( '/^video/', $mime_type ) ) {
            return "video-file";
        }

        // matching of other file types is not that simple,
        // so we just create an array of all the mime-type => css-class associations.
        $css_classes = array(
            "text/plain" => "text-file",
            "text/xml" => "text-file",
            "text/css" => "text-file",

            "application/pdf" => "pdf-document-file",

            "text/html" => "webpage-file",

            "application/msword" => "document-file",
            "application/vnd.oasis.opendocument.text" => "document-file",
            "application/vnd.sun.xml.writer" => "document-file",
            "application/vnd.ms-htmlhelp" => "document-file",
            "application/postscript" => "document-file",
            "text/richtext" => "document-file",

            "application/vnd.ms-excel" => "spreadsheet-file",
            "application/vnd.oasis.opendocument.spreadsheet" => "spreadsheet-file",
            "application/vnd.sun.xml.calc" => "spreadsheet-file",
            "text/csv" => "spreadsheet-file",

            "application/vnd.ms-powerpoint" => "presentation-file",
            "application/vnd.oasis.opendocument.presentation" => "presentation-file",
            "application/vnd.sun.xml.impress" => "presentation-file",

            "application/zip" => "archive-file",
            "application/x-gzip" => "archive-file",
            "application/x-bzip" => "archive-file",
            "application/x-tar" => "archive-file",
            "application/x-rar-compressed" => "archive-file",
        );

        if ( isset( $css_classes[ $mime_type ] ) ) {
            return $css_classes[ $mime_type ];
        }

        // if nothing else remains...
        return "unknown-file";
    }

    /**
     * Returns a name for the MIME type in natural language
     *
     * This method just returns general names like "Image file" or "Presentation"
     * (not format specific like "GIF image" or "Powerpoint presentation").
     *
     * @static
     * @param string $mime_type MIME type
     * @return string  translated name of the file type
     */
    static function humanReadableName( $mime_type )
    {
        switch ( MimeType::cssClass( $mime_type ) ) {
            case "image-file":
                return _("Image file");
                break;
            case "audio-file":
                return _("Audio file");
                break;
            case "video-file":
                return _("Video file");
                break;
            case "document-file":
                return _("Text document");
                break;
            case "pdf-document-file":
                return _("PDF document");
                break;
            case "presentation-file":
                return _("Presentation");
                break;
            case "spreadsheet-file":
                return _("Spreadsheet");
                break;
            case "archive-file":
                return _("Archive");
                break;
            case "text-file":
                return _("Text file");
                break;
            case "webpage-file":
                return _("Web page");
                break;
            default:
                return '';
        }
    }

    /**
     * Given a mime-type, returns corresponding extension.
     */
    function getExtension($mime_type) {
        $extensions = array_flip(MimeType::arrayOfKnownMIMETypes());

        if ( isset($extensions[$mime_type]) ) {
            return $extensions[$mime_type];
        }
        else {
            return "";
        }
    }


    /**
     * Returns associative array that maps extensions to MIME types
     *
     * Several extensions may reference the same MIME type, but one extension
     * can only be associated with one MIME type.
     *
     * The extensions are ordered in a way, that more important
     * extensions are at the end, so that when the keys-values are
     * flipped, only the less-important extensions will be removed.
     *
     * @static
     * @return array MIME types
     */
    function arrayOfKnownMIMETypes()
    {
        return array(
            "pdf" => "application/pdf",
            "ps" => "application/postscript",
            "ogg" => "application/ogg",

            "zip" => "application/zip",
            "tgz" => "application/x-gzip",
            "gz" => "application/x-gzip",
            "tbz" => "application/x-bzip",
            "bz" => "application/x-bzip",
            "rar" => "application/x-rar-compressed",
            "tar" => "application/x-tar",

            "dot" => "application/msword",
            "doc" => "application/msword",
            "xls" => "application/vnd.ms-excel",
            "pps" => "application/vnd.ms-powerpoint",
            "ppt" => "application/vnd.ms-powerpoint",
            "chm" => "application/vnd.ms-htmlhelp",
            "wps" => "application/vnd.ms-works",
            "mdb" => "application/vnd.ms-access",
            "pub" => "application/x-mspublisher",

            "odc" => "application/vnd.oasis.opendocument.chart",
            "odf" => "application/vnd.oasis.opendocument.formula",
            "odg" => "application/vnd.oasis.opendocument.graphics",
            "odi" => "application/vnd.oasis.opendocument.image",
            "odp" => "application/vnd.oasis.opendocument.presentation",
            "ods" => "application/vnd.oasis.opendocument.spreadsheet",
            "odt" => "application/vnd.oasis.opendocument.text",

            "sxc" => "application/vnd.sun.xml.calc",
            "sxd" => "application/vnd.sun.xml.draw",
            "sxi" => "application/vnd.sun.xml.impress",
            "sxm" => "application/vnd.sun.xml.math",
            "sxw" => "application/vnd.sun.xml.writer",

            "xml" => "text/xml",
            "rtf" => "text/richtext",
            "htm" => "text/html",
            "xhtml" => "text/html",
            "html" => "text/html",
            "css" => "text/css",
            "csv" => "text/csv",

            "asm" => "text/plain",
            "bas" => "text/plain",
            "bat" => "text/plain",
            "c" => "text/plain",
            "cc" => "text/plain",
            "cpp" => "text/plain",
            "cxx" => "text/plain",
            "h" => "text/plain",
            "inc" => "text/plain",
            "java" => "text/plain",
            "js" => "text/plain",
            "pas" => "text/plain",
            "php" => "text/plain",
            "pl" => "text/plain",
            "pm" => "text/plain",
            "py" => "text/plain",
            "rb" => "text/plain",
            "sh" => "text/plain",
            "sql" => "text/plain",
            "tcl" => "text/plain",
            "vbs" => "text/plain",
            "txt" => "text/plain",

            "bmp" => "image/bmp",
            "gif" => "image/gif",
            "jpe" => "image/jpeg",
            "jpeg" => "image/jpeg",
            "jpg" => "image/jpeg",
            "png" => "image/png",
            "j2c" => "image/jp2",
            "jp2" => "image/jp2",
            "ico" => "image/vnd.microsoft.icon",
            "tiff" => "image/tiff",
            "tif" => "image/tiff",
            "svg" => "image/svg+xml",

            "wav" => "audio/x-wav",
            "mp3" => "audio/mpeg",
            "aifc" => "audio/x-aiff",
            "aiff" => "audio/x-aiff",
            "aif" => "audio/x-aiff",
            "wma" => "audio/x-ms-wma",

            "wmv" => "video/x-ms-wmv",
            "mp2" => "video/mpeg",
            "mpa" => "video/mpeg",
            "mpv2" => "video/mpeg",
            "mpeg" => "video/mpeg",
            "mpg" => "video/mpeg",
            "qt" => "video/quicktime",
            "mov" => "video/quicktime",
            "avi" => "video/x-msvideo"
        );
    }
}

?>