<?php

/**
 * Contains the Material class
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
 * This is a child class of TableAbstraction
 */
require_once 'TableAbstraction.php';

/**
 * The class contains a course object
 */
require_once 'Course.php';

/**
 * The class contains a date object
 */
require_once 'Pear/Date.php';

/**
 * The class contains a user object
 */
require_once 'User.php';

/**
 * The class transforms BBCode to HTML
 */
require_once "VikoContentFormatter.php";

/**
 * The class creates instances of (subclasses of) itself
 */
require_once "MaterialFactory.php";


/**
 * Material class
 *
 * This is abstract parent class of all different course materials.
 */
class Material extends TableAbstraction
{
    /**
     * Returns the user who owns the material
     *
     * @return User the user
     */
    function getOwner()
    {
        return $this->getAttribute("_owner");
    }


    /**
     * Sets owner of the material
     *
     * @param User $user user
     */
    function setOwner( $owner )
    {
        $this->_owner = $owner;
    }


    /**
     * Returns course
     *
     * @return Course course object
     */
    function getCourse()
    {
        return $this->getAttribute("_course");
    }


    /**
     * Sets course
     *
     * @param Course $course course object
     */
    function setCourse( $course )
    {
        $this->_course = $course;
    }


    /**
     * Returns the name of the material
     *
     * @return string the name
     */
    function getName()
    {
        return $this->getAttribute("_name");
    }


    /**
     * Sets name of the material
     *
     * @param string $name name
     */
    function setName( $name )
    {
        $this->_name = $name;
    }


    /**
     * Returns name that is HTML-formatted
     *
     * @return string name that is ready for inserting into HTML
     */
    function getHTMLName()
    {
        return htmlspecialchars( $this->getName(), ENT_QUOTES );
    }


    /**
     * Returns HTML link
     *
     * Each subclasses has to implement its own version of this.
     *
     * @abstract
     * @return string HTML a element
     */
    function getLink()
    {
        trigger_error("Method getLink not implemented.", E_USER_ERROR);
    }

/**
     * Sets text of the material
     *
     * @param string $name name
     */
    function setMaterialText( $materialtext )
    {
        $this->_materialtext = $materialtext;
    }

    /**
     * Returns the text of the material
     *
     * @return string the name
     */
    function getMaterialText()
    {
        return $this->getAttribute("_materialtext");
    }


    /**
     * Returns the description of the material
     *
     * @return string the name
     */
    function getDescription()
    {
        return $this->getAttribute("_description");
    }

     /**
     * Sets description of the material
     *
     * @param string $name name
     */
    function setDescription( $description )
    {
        $this->_description = $description;
    }


    /**
     * Returns description that is HTML-formatted
     *
     * @return string description that is ready for inserting into HTML
     */
    function getHTMLDescription()
    {
        return VikoContentFormatter::format( $this->getDescription() );
    }


    /**
     * Returns the date when the material was added
     *
     * @return Date the date of the material
     */
    function getAddTime()
    {
        return $this->getAttribute("_addtime");
    }


    /**
     * Sets material adding time
     *
     * @param Date $date date
     */
    function setAddTime( $date )
    {
        $this->_addtime = $date;
    }


    /**
     * Returns the parent of the material (another material or null)
     *
     * @return Material parent
     */
    function getParent()
    {
        return $this->getAttribute("_parent");
    }


    /**
     * Sets the parent of the material
     *
     * @param Material $parent another Material object or null
     */
    function setParent( $parent )
    {
        $this->_parent = $parent;
    }


    /**
     * Returns the type of the material
     *
     * Possible values are: 'FILE', 'FOLDER' and 'LINK'.
     *
     * @return string material type
     */
    function getType()
    {
        return $this->getAttribute("_type");
    }


    /**
     * Returns name of the table containing materials
     */
    function getTableName()
    {
        return "Materials";
    }


    /**
     * Returns the primary key field name of the materials table
     */
    function getIDFieldName()
    {
        return "material_id";
    }


    /**
     * Implementation of reading a database record
     */
    function readFromDatabaseRecord( $record )
    {
        $this->_owner = new User( $record['user_id'] );
        $this->_course = new Course( $record['course_id'] );
        $this->_type = $record['material_type'];
        $this->_mime_type = $record['mime_type'];
        $this->_name = $record['material_name'];
        $this->_description = $record['material_description'];
        $this->_materialtext = $record['material_text'];
        $this->_uri = $record['material_uri'];
        $this->_addtime = new Date( $record['material_addtime'] );
        $this->_size = $record['material_size'];

        // only some materials are inside a parent folder
        if ( isset($record['parent_id']) ) {
            $this->_parent = MaterialFactory::folder( $record['parent_id'] );
        }
        else {
            $this->_parent = null;
        }
    }


    /**
     * Implementation of composing a database record
     */
    function writeToDatabaseRecord()
    {
        // the following properties may not be NULL
        if ( !isset($this->_owner) && $this->_owner->getID() > 0  ) {
            trigger_error("Owner must be specified when saving material.", E_USER_ERROR);
        }
        if ( !isset($this->_course) && $this->_course->getID() > 0 ) {
            trigger_error("Course must be specified when saving material.", E_USER_ERROR);
        }
        if ( !isset($this->_type) ) {
            trigger_error("Type must be specified when saving material.", E_USER_ERROR);
        }
        if ( !isset($this->_mime_type) ) {
            trigger_error("MIME type must be specified when saving material.", E_USER_ERROR);
        }
        if ( !isset($this->_name) ) {
            trigger_error("Name must be specified when saving material.", E_USER_ERROR);
        }
	if ( !isset($this->_description) ) {
            trigger_error("Description must be specified when saving material.", E_USER_ERROR);
        }
	if ( !isset($this->_materialtext) ) {
            trigger_error("Text must be specified when saving material.", E_USER_ERROR);
        }
        if ( !isset($this->_uri) ) {
            trigger_error("URI must be specified when saving material.", E_USER_ERROR);
        }
        if ( !isset($this->_addtime) ) {
            trigger_error("Addtime must be specified when saving material.", E_USER_ERROR);
        }
        if ( !isset($this->_size) ) {
            trigger_error("Size must be specified when saving material.", E_USER_ERROR);
        }

        $record = array(
            'user_id' => $this->_owner->getID(),
            'course_id' => $this->_course->getID(),
            'material_type' => $this->_type,
            'mime_type' => $this->_mime_type,
            'material_name' => $this->_name,
            'material_description' => $this->_description,
            'material_text' => $this->_materialtext,
            'material_uri' => $this->_uri,
            'material_addtime' => $this->_addtime->getDate(),
            'material_size' => $this->_size
        );

        if ( isset($this->_parent) && $this->_parent->getID() > 0  ) {
            $record['parent_id'] = $this->_parent->getID();
        }
        else {
            $record['parent_id'] = null;
        }

        return $record;
    }


    /**
     * Removes the material from database
     *
     * The ID must be specified for this to work.
     * If deleted material is a container, then all materials inside that are also
     * recursively deleted.
     *
     * @access public
     * @return boolean true on successful delete.
     *                 false, if the material with ID did not exist
     */
    function delete()
    {
        // first delete the material record itself
        if ( parent::delete() ) {

            // If the material contains other materials, then delete recursively these too.

            // get array of file_id-s inside this folder
            $db = DBInstance::get();
            $materials_in_dir = $db->query(
                "
                SELECT
                    material_id,
                    material_type
                FROM
                    Materials
                WHERE
                    parent_id=?
                ",
                array( $this->_id )
            );

            if ( PEAR::isError( $materials_in_dir ) ) {
                trigger_error( $materials_in_dir->getMessage(), E_USER_ERROR );
            }

            // create material object for each found ID and execute delete() command on it
            while ( $materials_in_dir->fetchInto( $material_record ) ) {

                $material = MaterialFactory::material(
                    $material_record['material_type'],
                    $material_record['material_id']
                );

                if ( !$material->delete() ) {
                    // that's pretty bad when the delete failed...
                    // we should roll back the transaction, but there are no transactions...
                    // Even better would be, if there would be database-level relational integrity.
                    // But at this time it isn't there.
                    // It really needs to get done in some future version - the sooner, the better.
                    // But now we just continue and try to delete at least all the remaining files.
                }
            }

            return true;
        }
        else {
            return false;
        }
    }
}



?>
