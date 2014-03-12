<?php

/**
 * Contains the File class
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
 * Let the files be read and written in 128KB chunks
 */
define("FILE_CHUNK_SIZE", 128*1024);

/**
 * This is a child class of TableAbstraction
 */
require_once 'Material.php';

/**
 * Each file has a MIME type associated with
 */
require_once 'MimeType.php';
 
/** 
* This class makes use of URI function provided by Module class 
*/ 
require_once 'Module.php'; 

/**
 * File class
 *
 */
class File extends Material
{
    /**
     * Extends the general constructor by setting material type to 'FILE'
     */
    function File( $id )
    {
        $this->_type = "FILE";

        $this->_uri = "";

        parent::Material( $id );
    }


    /**
     * Returns filename with extension if possible
     *
     * If the file has no extension, then the extension
     * is to be added based on the MIME-type of the file.
     *
     * Also the spaces and tabs inside filenames are replaced
     * with underscores, because some browsers can't handle spaces.
     */
    function getDownloadName() {
        $filename = $this->getName();

        // first of all, replace spaces and tabs in filename
        $disallowed_chars = array(" ", "\t");
        $filename = str_replace( $disallowed_chars, "_", $filename);

        // if the filename already contains on extension, return it as-is.
        if ( preg_match( '!\.[a-zA-Z0-9]{1,4}$!', $filename ) ) {
            return $filename;
        }
        else {
            // determine the extension from mime-type.
            $extension = MimeType::getExtension($this->getMimeType());
            if ( $extension == "" ) {
                return $filename;
            }
            else {
                return "$filename.$extension";
            }
        }
    }


    /**
     * Returns the MIME type of file
     *
     * @return string MIME type
     */
    function getMimeType()
    {
        return $this->getAttribute("_mime_type");
    }


    /**
     * Sets MIME type of file
     *
     * @param string $type MIME type
     */
    function setMimeType( $type )
    {
        $this->_mime_type = $type;
    }


    /**
     * Returns translated string describing the type of material
     *
     * @return string
     */
    function getTypeName()
    {
        return $this->humanReadableName( $this->getMimeType() );
    }


    /**
     * Returns HTML link, that points to download location of the file
     *
     * @return string HTML a element
     */
    function getLink()
    {
        $class = MimeType::cssClass( $this->getMimeType() );

        $course = $this->getCourse();
        return HTML::element(
            "a",
            $this->getHTMLName(),
            array(
                "href" => "/" . $course->getID() . "/materials/download/" . $this->getID(),
                "class" => $class
            )
        );
    }


    /**
     * Returns the size of the file in bytes
     *
     * @return int size
     */
    function getSize()
    {
        return $this->getAttribute("_size");
    }


    /**
     * Sets the size of the file in bytes
     *
     * @param int $size filesize
     */
    function setSize( $size )
    {
        $this->_size = $size;
    }


    /**
     * Returns the size of the file in human-readable format
     *
     * Mutch like the -h option in many UNIX utilities.
     * If the size is >= 1024 bytes, it is represented in KB,
     * if size is >= 1024 KB, it's given in MB.
     *
     * The unit B, KB, MB or GB is appended to the returned string.
     * If the value is a fraction, only one place after the comma is shown.
     *
     * The method can also be called statically be specifying the optional
     * argument.
     *
     * @static
     * @param int $size file size in bytes
     * @return string file size
     */
    function getHumanReadableSize( $size = null )
    {
        if ( !isset($size) ) {
            $size = $this->getSize();
        }

        if ( $size < 1024 ) {
            return $size . " B";
        }
        elseif ( $size < 1024*1024 ) {
            return round($size/1024, 1) . " KB";
        }
        elseif ( $size < 1024*1024*1024 ) {
            return round($size/1024/1024, 1) . " MB";
        }
        else {
            return round($size/1024/1024/1024, 1) . " GB";
        }
    }


    /**
     * Outputs the contents of file
     *
     * @return string content or false on error
     */
    function outputContent()
    {
        // ID must be specified
        if ( !isset($this->_id) ) {
            trigger_error("File id must be specified when retrieving file contents.", E_USER_ERROR);
        }

        $db = DBInstance::get();
        $filesize = $this->getSize();
        $chunk_length = FILE_CHUNK_SIZE;
        $chunk_count = 0;
        $chunk_start = 1; // substring uses 1 as the first position in string

        // read the contents of file in chunks
        do {
            $chunk = $db->getOne(
                "SELECT SUBSTRING(material_content, $chunk_start, $chunk_length)
                FROM Material_Contents
                WHERE material_id=?",
                array( $this->_id )
            );

            // check for errors in query
            if ( PEAR::isError( $chunk ) ) {
                trigger_error( $chunk->getMessage(), E_USER_ERROR );
            }

            // output data
            echo $chunk;

            // move on to next chunk
            $chunk_count++;
            $chunk_start = $chunk_count * $chunk_length + 1;

        } while ( $chunk_start <= $filesize );
    }


    /**
     * Loads content from the specified file to database
     *
     * @param string content
     */
    function loadContentFromFile( $filename )
    {
        // ID must be specified
        if ( !isset($this->_id) ) {
            trigger_error("File id must be specified when setting file contents.", E_USER_ERROR);
        }

        // create empty content-record, truncate all previous content
        $this->_clearContent();

        $db = DBInstance::get();

        // increase the maximum allowed packet size to allow large files
        $db->query("SET max_allowed_packet=" . Configuration::getMaxFileSize() );

        // open the file in binary mode
        $file = fopen($filename, "rb");

        // read the file in small chunks and append these to the record
        while (!feof($file)) {
            $file_chunk = fread($file, FILE_CHUNK_SIZE);

            $file_chunk = mysql_real_escape_string($file_chunk);

            $result = $db->query(
                "UPDATE
                    Material_Contents
                SET
                    material_content = CONCAT(material_content, '$file_chunk')
                WHERE
                    material_id=" . $this->_id
            );

            // check for errors in query
            if ( PEAR::isError( $result ) ) {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }
        }

        fclose($file);
    }


    /**
     * Creates empty record to Material_Contents table.
     * If the record already exists, truncates its content.
     *
     * @access private
     */
    function _clearContent()
    {
        $db = DBInstance::get();

        // check, if the file already has content
        $count = $db->getOne(
            "SELECT COUNT(*) FROM Material_Contents WHERE material_id=?",
            array( $this->_id )
        );

        // check for errors in query
        if ( PEAR::isError( $count ) ) {
            trigger_error( $count->getMessage(), E_USER_ERROR );
        }

        // if the file already has previous content, truncate that content
        if ( $count == 1 ) {
            $result = $db->autoExecute(
                'Material_Contents',
                array( 'material_content' => '' ),
                DB_AUTOQUERY_UPDATE,
                'material_id = ' . $this->_id
            );
        }
        else {
            // if no previous content exists, create new empty content record
            $result = $db->autoExecute(
                'Material_Contents',
                array( 'material_id' => $this->_id, 'material_content' => '' ),
                DB_AUTOQUERY_INSERT
            );
        }

        // check for errors in query
        if ( PEAR::isError( $result ) ) {
            trigger_error( $result->getMessage(), E_USER_ERROR );
        }
    }


    /**
     * Removes the file from database
     *
     * Also removes the contents of the file from database.
     *
     * @access public
     * @return boolean true on successful delete.
     *                 false, if the file with ID did not exist
     */
    function delete()
    {
        // first delete the file record itself
        if ( parent::delete() ) {

            // delete the contents of file
            $db = DBInstance::get();
            $result = $db->query(
                "DELETE FROM Material_Contents WHERE material_id=?",
                array( $this->_id )
            );

            if ( PEAR::isError( $result ) ) {
                trigger_error( $result->getMessage(), E_USER_ERROR );
            }

            return true;
        }
        else {
            return false;
        }
    }
}



?>