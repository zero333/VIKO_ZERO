<?php

/**
 * Contains the MaterialFactory class
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
 * This abstract factory produces instances of File
 */
require_once 'File.php';

/**
 * This abstract factory produces instances of Folder
 */
require_once 'Folder.php';

/**
 * This abstract factory produces instances of Link
 */
require_once 'Link.php';
/**
 * This abstract factory produces instances of Text
 */
require_once 'Text.php';
/**
 * This abstract factory produces instances of Embed
 */
require_once 'Embed.php'; 

/**
 * Generates course materials: files, folders and links and text and embed
 *
 */
class MaterialFactory
{
    /**
     * Constructs a material object based on passed in parameters
     *
     * Allowed values for $type are: 'FILE', 'FOLDER' and 'LINK'.
     * AND TEXT AND EMBED
     * @static
     * @param string $type the type of the material to create
     * @param int $material_id the database ID of the material
     * @return Material instance
     */
    static function material( $type, $material_id=null )
    {
        switch ( $type ) {
            case "FILE":
                return MaterialFactory::file( $material_id );
                break;
            case "FOLDER":
                return MaterialFactory::folder( $material_id );
                break;
            case "LINK":
                return MaterialFactory::link( $material_id );
                break;
			case "TEXT":
                return MaterialFactory::text( $material_id );
                break;
			case "EMBED":
                return MaterialFactory::embed( $material_id );
                break;
            default:
                trigger_error( "Unknown type '$type'.", E_USER_ERROR );
        }
    }


    /**
     * Constructs a material object based on material_id
     *
     * @static
     * @param int $material_id  the database ID of the material
     * @return mixed  on success Material instance (if loading fails, false is returned)
     */
    function materialByID( $material_id )
    {
        $db = DBInstance::get();
        $record = $db->getRow(
            "SELECT * FROM Materials WHERE material_id=?",
            array( $material_id )
        );

        if ( PEAR::isError( $record ) ) {
            trigger_error( $record->getMessage(), E_USER_ERROR );
        }

        if ( isset($record) ) {
            $material = MaterialFactory::material( $record['material_type'], $record['material_id'] );
            $material->readFromDatabaseRecord( $record );
            return $material;
        }
        else {
            return false;
        }
    }


    /**
     * Returns file object
     *
     * @static
     * @return Material instance
     */
    static function file( $material_id=null )
    {
        $file = new File( $material_id );
        return $file;
    }


    /**
     * Returns folder object
     *
     * @return Material instance
     */
    static function folder( $material_id=null )
    {
        $folder = new Folder( $material_id );
        return $folder;
    }


    /**
     * Returns Link object
     *
     * @return Material instance
     */
    static function link( $material_id=null )
    {
        $link = new Link( $material_id );
        return $link;
    }
	/**
     * Returns Text object
     *
     * @return Material instance
     */
    static function text( $material_id=null )
    {
        $text = new Text( $material_id );
        return $text;
    }
	/** 
     * Returns Embed object
     *
     * @return Material instance
     */
    static function embed( $material_id=null )
    {
        $embed = new Embed( $material_id );
        return $embed;
    }
}
?>
