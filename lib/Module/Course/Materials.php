<?php

/**
 * Contains the Module_Course_Materials class
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
 * @author     Argo Ilves <argoi@tlu.ee>
 * @copyright  2001-2009 VIKO team and contributors
 * @license    http://www.gnu.org/licenses/gpl.html  GPL 2.0
 */

require_once 'lib/Module/Course.php';
require_once 'Pear/HTML/QuickForm.php';
require_once 'lib/HTML/QuickForm/Renderer/VIKO.php';

/**
 * HTML form elements for materials
 */
require_once 'lib/MaterialForm.php';

/**
 * Files, folders and links
 */
require_once 'lib/MaterialFactory.php';


/**
 * Showing, editing, adding, deleting of course materials: files, folders and links
 *
 * <ul>
 * <li>/#course_id/materials - view contents of main folder</li>
 * <li>/#course_id/materials/view/#folder_id - view contents of folder with ID #folder_id</li>
 * <li>/#course_id/materials/download/#file_id - download file</li>
 * <li>/#course_id/materials/edit/#material_id - edit material</li>
 * <li>/#course_id/materials/delete/#material_id - delete material</li>
 * <li>/#course_id/materials/add-file/#folder_id - upload file to folder with ID #folder_id</li>
 * <li>/#course_id/materials/add-folder/#folder_id - add folder to folder with ID #folder_id</li>
 * <li>/#course_id/materials/add-link/#folder_id - add link to folder with ID #folder_id</li>
 * </ul>
 *
 * The #folder_id parameter is optional - when this is missing,
 * then the action refers to the root directory.
 * For example /materials and /materials/view both trigger the showing of root folder contents.
 */
class Module_Course_Materials extends Module_Course {

    /**
     * The action to perform with the material
     *
     * @access private
     * @var string
     */
    var $_action = "view";

    /**
     * The material to view/edit/delete/...
     *
     * @access private
     * @var Material
     */
    var $_material = null;

    /**
     * Constructs new Materials Module
     */
    function Module_Course_Materials( $course, $parameters )
    {
        $possible_actions = array(
            "view" => true,
            "download" => true,
            "edit" => true,
            "delete" => true,
            "add-file" => true,
			"add-text" => true,
			"add-embed" => true,
            "add-folder" => true,
            "show-material" => true,
            "add-link" => true
        );

        // the first parameter is the name of action
        if ( isset( $parameters[0] ) ) {
            $this->_action = $parameters[0];
        }
        else {
            $this->_action = "view";  // default
        }

        // if the action is one of allowed ones
        if ( isset( $possible_actions[ $this->_action ] ) ) {
            // take the [optional] second parameter and turn it into Material object
            if ( isset( $parameters[1] ) && (int)$parameters[1] > 0 ) {
                $this->_material = MaterialFactory::materialByID( (int)$parameters[1] );
                // if the loading failid, _material will be false - that is checked in toHTML()
            }
        }
        else {
            trigger_error( "Unknown action '{$this->_action}'.", E_USER_ERROR );
        }

        parent::Module_Course( $course, $parameters );
    }


    /**
     * Returns the module identifier string
     *
     * @access public
     * @static
     * @return string Identificator of module
     */
    function getID()
    {
        return "materials";
    }

    /**
     * Returns the short name of the module, this is used in navigation menu.
     *
     * @access public
     * @static
     * @return string short name
     */
    function getName()
    {
        return _("Materials");
    }


    /**
     * Returns short description of module
     *
     * @access public
     * @static
     * @return string module description
     */
    function getDescription()
    {
        return _("Manage materials");
    }

    /**
     * Returns material text
     *
     * @access public
     * @static
     * @return string module description
     */
    function getMaterialText()
    {
        return _("Manage materials");
    }


    /**
     * Returns Materials page in HTML
     *
     * @return string HTML fragment
     */
    function toHTML()
    {
        if ( isset( $this->_material ) ) {
            // ensure, that the material exists
            if ( $this->_material === false ) {
                return $this->errorPage( _("The selected material does not exist.") );
            }

            // ensure, that material belongs to current course
            $material_course = $this->_material->getCourse();
            if ( $material_course->getID() != $this->_course->getID() ) {
                return $this->errorPage( _("The selected material does not belong to this course.") );
            }
        }

        switch ( $this->_action ) {
            case "view":
                return $this->_showFolder( $this->_material );
                break;
            case "download":
                return $this->_downloadFile( $this->_material );
                break;
            case "delete":
                return $this->_deleteMaterial( $this->_material );
                break;
            case "add-folder":
                return $this->_addFolder( $this->_material );
                break;
            case "add-link":
                return $this->_addLink( $this->_material );
                break;
			case "add-text":
                return $this->_addText( $this->_material );
                break;
            case "add-embed":
                return $this->_addEmbed( $this->_material );
                break;
			case "add-file":
                return $this->_addFile( $this->_material );
                break;
            case "edit":
                return $this->_editMaterial( $this->_material );
                break;
			case "show-material":
                return $this->_showMaterial( $this->_material );
                break;
            default:
                trigger_error( "Unknown action '{$this->_action}'.", E_USER_ERROR );
        }
    }


    /**
     * Returns page with table listing all files in folder
     *
     * When the $folder parameter is not specified, the contents of main folder is shown.
     *
     * @access private
     * @param File $folder
     * @return string HTML fragment
     */
    function _showFolder( $folder = null )
    {
        // page title
        $html = $this->title();

        // table with files
        $html.= $this->_getMaterialsTable( $folder );

        // links to add files/folders/links // text
        if ( isset($folder) ) {
            $add_file_uri = $this->relativeURI('add-file', $folder->getID() );
            $add_link_uri = $this->relativeURI('add-link', $folder->getID() );
            $add_text_uri = $this->relativeURI('add-text', $folder->getID() );
            $add_embed_uri = $this->relativeURI('add-embed', $folder->getID() );
	        $add_folder_uri = $this->relativeURI('add-folder', $folder->getID() );
             

		}
        else {
            $add_file_uri = $this->relativeURI('add-file' );
            $add_link_uri = $this->relativeURI('add-link' );
            $add_text_uri = $this->relativeURI('add-text' );
            $add_embed_uri = $this->relativeURI('add-embed' );
            $add_folder_uri = $this->relativeURI('add-folder' );


        }
        $add_file_link = HTML::newLink( $add_file_uri, _("Add file") );
        $add_link_link = HTML::newLink( $add_link_uri, _("Add link") );
		$add_text_link = HTML::newLink( $add_text_uri, _("Add text") );
		$add_embed_link = HTML::newLink( $add_embed_uri, _("Add embed") );
        $add_folder_link = HTML::newFolderLink( $add_folder_uri, _("Add folder") );


        // only teachers can add folders
        if ( $this->_userCanEditThisCourse() ) {
            $html.= HTML::actionList($add_file_link, $add_link_link, $add_text_link, $add_embed_link, $add_folder_link); 
        }
        else {
            // both the adding of links and uploading files are allowed/disallowed in configuration
			
            if(Configuration::getStudentCanUploadFiles()){
				$add_file=$add_file_link;
			}else{
				$add_file=null;
			}
			if(Configuration::getStudentCanAddLinks()){
				$add_link=$add_link_link;
			}else{
				$add_link=null;
				}
			if(Configuration::getStudentCanAddText()){
				$add_text=$add_text_link;
			}else{
				$add_text=null;
				}
			if(Configuration::getStudentCanAddEmbed()){
				$add_embed=$add_embed_link;
			}else{
				$add_embed=null;
				}
			$html.= HTML::actionList($add_file,$add_link,$add_text,$add_embed);
        }

        return $html;
    }


    /**
     * Returns table listing all files in folder
     *
     * When the $folder parameter is not specified, the contents of main folder are listed.
     *
     * @access private
     * @param File $folder
     * @return string HTML table
     */
    function _getMaterialsTable( $folder = null )
    {
        $table = new VikoTable();

        $header = array(
            _("File name"),
            _("File type"),
            _("Size")
        );
		$course_teacher = $this->_course->getTeacher();
		$user =  $_SESSION['user'];
        if ( $this->_userCanEditThisCourse() && $course_teacher->getID() == $user->getID()) {
            $header[] = _("Edit");
            $header[] = HTML::abbr('X', _("Delete material") );
			//$header[] = HTML::abbr('SHOW',_("Show / Hide material") );
		// for visible function
        }elseif($this->_userCanEditThisCourse()){$header[] = _("Edit");}
		
		
        $table->addHeader( $header );

        if ( isset( $folder ) ) {
            // get list of materials inside folder
            $material_records = $this->_getFolderRecords( $folder );

            // because we display a subfolder, we need to provide a backlink to parent folder
            $table->addRow(
                $this->_linkToParentFolder( $folder ),
                _("Parent folder")
            );
        }
        else {
            // get materials inside root folder
            $material_records = $this->_getRootFolderRecords();
        }

        $total_filesize = 0;

        // If course has no materials, show notice about it
        if ( count($material_records) == 0 && !isset($folder) ) {
            return HTML::notice( _("There are no materials on this course. To add new materials use the links below.") );
        }

        foreach ( $material_records as $record ) {
            $material = MaterialFactory::material( $record['material_type'], $record['material_id'] );
            $material->readFromDatabaseRecord( $record );

            $delete_title = sprintf( _("Delete %s"), $material->getHTMLName() );

            if ( $material->getType() == 'FILE' ) {
                $filesize = $material->getHumanReadableSize();
                $total_filesize += $material->getSize();
            }
            else {
                $filesize = "";
            }

            // only create delete- and edit-links when user is allowed to edit the file
            if ( $this->_userCanEditThisCourse()  ) {
                $edit_link = HTML::a( $this->relativeURI( 'edit', $material->getID() ), _("Edit") );
                $delete_link = HTML::a( $this->relativeURI( 'delete', $material->getID() ), "X", $delete_title );
            }

            // only add description, when it exists
            if ( $material->getDescription() == "" ) {
                $description = "";
            }
            else {
                // place description inside <div class="material-description">
                $description = HTML::element(
                    'div',
                    $material->getHTMLDescription(),
                    array( "class" => "material-description" )
                );
            }

            // if the the file was added by someone else than the teacher,
            // display the name of the adder in description.
            $material_owner = $material->getOwner();

            if ( $material_owner->getID() != $course_teacher->getID() ) {
                $description .= HTML::p( $material_owner->getFullName() );
            }

            $row = array(
                $material->getLink() . $description,
                $material->getTypeName(),
                $filesize
            );
            if ( $this->_userCanEditThisCourse() && $course_teacher->getID() == $user->getID() ) {
                $row[] = $edit_link;
                $row[] = $delete_link;
				//$row[] = $visible;	// to add checkbox for visible status if user can edit course.
            	}elseif($this->_userCanEditThisCourse()){
                $row[] = $edit_link;
				}
			$table->addRow( $row );
        }

        $table->addFooter(
            _("Total"),
            "",
            File::getHumanReadableSize( $total_filesize )
        );

        $table->setColumnsToNumeric( array(2), TABLE_BODY_AND_FOOTER );
        if ( $this->_userCanEditThisCourse() &&   $course_teacher->getID() == $user->getID()) {
            $table->setColumnToDelete( 4 );
        }

        return $table->toHTML();
    }


    /**
     * Returns array with files and folders inside root folder
     *
     * @access private
     * @return array file records
     */
    function _getRootFolderRecords()
    {
        $db = DBInstance::get();

        $order_by = DBUtils::createOrderBy( array('material_name') );

        // get list of folders
        $folder_records = $db->getAll(
            "SELECT * FROM Materials WHERE course_id=? AND parent_id is NULL AND material_type='FOLDER' $order_by",
            array( $this->_course->getID() )
        );

        if ( PEAR::isError( $folder_records ) ) {
            trigger_error( $folder_records->getMessage(), E_USER_ERROR );
        }

        // get list of other files/links
        $file_records = $db->getAll(
            "SELECT * FROM Materials WHERE course_id=? AND parent_id is NULL AND material_type<>'FOLDER' $order_by",
            array( $this->_course->getID() )
        );

        if ( PEAR::isError( $file_records ) ) {
            trigger_error( $file_records->getMessage(), E_USER_ERROR );
        }

        return array_merge( $folder_records, $file_records );
    }


    /**
     * Returns array with files and folders inside specified folder
     *
     * @access private
     * @param Material $folder
     * @return array file records
     */
    function _getFolderRecords( $folder )
    {
        $db = DBInstance::get();

        $order_by = DBUtils::createOrderBy( array('material_name') );

        // get list of folders
        $folder_records = $db->getAll(
            "SELECT * FROM Materials WHERE parent_id = ? AND material_type='FOLDER' $order_by",
            array( $folder->getID() )
        );

        if ( PEAR::isError( $folder_records ) ) {
            trigger_error( $folder_records->getMessage(), E_USER_ERROR );
        }

        // get list of other files/links
        $file_records = $db->getAll(
            "SELECT * FROM Materials WHERE parent_id = ? AND material_type<>'FOLDER' $order_by",
            array( $folder->getID() )
        );

        if ( PEAR::isError( $file_records ) ) {
            trigger_error( $file_records->getMessage(), E_USER_ERROR );
        }

        return array_merge( $folder_records, $file_records );
    }


    /**
     * Returns link to parent folder of given folder
     *
     * @access private
     * @param Material $current_folder
     * @return string HTML a element
     */
    function _linkToParentFolder( $current_folder )
    {
        $parent = $current_folder->getParent();
        if ( isset( $parent ) && $parent->getID() > 0 ) {
            // if parent folder is not root folder, link to the parent folder id
            $parent_uri = $this->relativeURI( 'view', $parent->getID() );
        }
        else {
            // if parent folder is root folder, link to the root dir of course module
            $parent_uri = $this->relativeURI();
        }

        return HTML::element( "a", "..", array( "href" => $parent_uri, "class" => "folder" ) );
    }


    /**
     * Retrieves the file contents from database and sends to client
     *
     * @access private
     * @param Material $file  the file to download
     * @return bool
     */
    function _downloadFile( $file )
    {
        // $file must not be null
        if ( !isset($file) ) {
            trigger_error( "The file_id to download must be specified.", E_USER_ERROR );
        }

        // $file must be of correct type
        if ( $file->getType() != 'FILE' ) {
            trigger_error( "Only files can be downloaded.", E_USER_ERROR );
        }

        // set headers
        header("Content-Type: " . $file->getMimeType() );
        header("Content-Length: " . $file->getSize() );
        header("Content-Disposition: attachment; filename=" . $file->getDownloadName() );

        // output data
        $file->outputContent();

        // exit
        return false;
    }


    /**
     * Deletes the material
     *
     * On successful delete sends user back to previous page
     *
     * @access private
     * @param Material $material  the material to delete
     * @return mixed
     */
    function _deleteMaterial( $material )
    {
        // must not be null
        if ( !isset($material) ) {
            trigger_error( "The material to delete must be specified.", E_USER_ERROR );
        }

        // ensure, that user is teacher of the course
        if ( !$this->_userCanEditThisCourse() && $course_teacher->getID() != $user->getID() ) {
            return $this->accessDeniedPage( _("You are not allowed to delete this material.") );
        }

        // delete the file
        if ( !$material->delete() ) {
            return $this->errorPage( _("The deleting of material did not succeed.") );
        }

		if ( isset($_SERVER['HTTP_REFERER']) ) { 
 		      header("Location: $_SERVER[HTTP_REFERER]"); 
        } 
		else { 
            $this->redirectInsideCourseModule(); 
		} 

        // exit
        return false;
    }    

	/**
     * Shows the material
     *
     * @access private
     * @param Material $material  the material to delete
     * @return mixed
     */
    function _showMaterial( $material )
    {
        // must not be null
        if ( !isset($material) ) {
            trigger_error( "The material to show must be specified.", E_USER_ERROR );
        }

		// shows material
		if ( $material->getType() == 'TEXT' ) {
			$showName=	HTML::h3( $material->getName(),array( "class" => "material-showtext" ) );
			$showText=	HTML::p( $material->getMaterialText());
			$showDescription='';

		}elseif ( $material->getType() == 'EMBED' ) {
			$showName=	HTML::h3( $material->getName(),array( "class" => "material-showtext" ));
			$showText=	HTML::p( $material->getMaterialText());
			$showDescription=	HTML::p( $material->getDescription());

        }

        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        // display the form
        $html = $this->title( _("Show material") );
        $html .= $showName;
        $html .= $showText;
        $html .= $showDescription;
        $html .= HTML::backLink( $this->relativeURI(), _("Return to the list of materials"), STANDALONE );
        return $html;
    }


    /**
     * Shows form for adding new folder, when form is submitted, adds the folder
     *
     * Only the teacher of the course is allowed to add folders, this is mostly
     * to prevent situation where student creates a folder, other users add files
     * to that folder and then the user who created the folder deletes it, deleting
     * also all the files inside that folder.
     *
     * @access private
     * @return mixed
     */
    function _addFolder( $parent=null )
    {
        // only teacher can add folders
        if ( !$this->_userCanEditThisCourse() ) {
            return $this->accessDeniedPage( _("Only teacher can add folders.") );
        }

        // if the folder is specified, check that it is a correct one
        if ( isset($parent)  &&  $parent->getType() != 'FOLDER' ) {
            return $this->errorPage( _("You can only add folders inside folders.") );
        }

        // construct HTML form for adding new folder
        $form = new HTML_QuickForm();
        // folder name
        MaterialForm::addFolderNameField( $form );
        // folder description
        MaterialForm::addDescriptionField( $form );
        // submit button
        $form->addElement(
            'submit',
            'submit',
            _("Add folder")
        );

        // validate the form
        if ( $form->validate() ) {
            // if validation succeeded, create new Folder object and save it to database
            $this->_saveNewFolder( $form, $parent );

            // Send user back to the parent folder
            if ( isset($parent) ) {
                $this->redirectInsideCourseModule( 'view', $parent->getID() );
            }
            else {
                $this->redirectInsideCourseModule();
            }
            return false;
        }

        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        // display the form
        $html = $this->title( _("Add folder") );
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );
        $html .= HTML::backLink( $this->relativeURI(), _("Return to the list of materials"), STANDALONE );
        return $html;
    }


    /**
     * Saves the info about new folder from HTML form to database
     *
     * @access private
     * @param HTML_QuickForm $form
     * @param File $parent
     */
    function _saveNewFolder( $form, $parent=null ) {
        $folder = MaterialFactory::folder();
        $folder->setCourse( $this->_course );
        $folder->setParent( $parent );
        $folder->setOwner( $_SESSION['user'] );
        $folder->setName( $form->exportValue("material-name") );
        $folder->setDescription( $form->exportValue("material-description") );
        $folder->setMaterialText( "" );
        $folder->setAddTime( new Date() );
        $folder->save();
    }


    /**
     * Shows form for adding new link, when form is submitted, adds the link
     *
     * @access private
     * @return mixed
     */
    function _addLink( $parent=null )
    {
        // if adding links is disabled for students - error
        if ( !( $this->_userCanEditThisCourse() || Configuration::getStudentCanAddLinks() ) ) {
            return $this->accessDeniedPage( _("Only teacher can add links.") );
        }

        // if the parent folder is specified, check that it is a correct one
        if ( isset($parent)  &&  $parent->getType() != 'FOLDER' ) {
            return $this->errorPage( _("You can only add links inside folders.") );
        }

        // construct HTML form for adding new URI
        $form = new HTML_QuickForm();
        // link URI
        MaterialForm::addURIField( $form );
        // link name
        MaterialForm::addLinkNameField( $form );
        // link description
        MaterialForm::addDescriptionField( $form );
        // submit button
        $form->addElement(
            'submit',
            'submit',
            _("Add link")
        );

        // validate the form
        if ( $form->validate() ) {
            // if validation succeeded, create new Folder object and save it to database
            $this->_saveNewLink( $form, $parent );

            // Send user back to the parent folder
            if ( isset($parent) ) {
                $this->redirectInsideCourseModule( 'view', $parent->getID() );
            }
            else {
                $this->redirectInsideCourseModule();
            }
            return false;
        }

        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        // display the form
        $html = $this->title( _("Add link") );
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );
        $html .= HTML::backLink( $this->relativeURI(), _("Return to the list of materials"), STANDALONE );
        return $html;
    }


    /** 
     * Saves the info about new link from HTML form to database
     *
     * @access private
     * @param HTML_QuickForm $form
     * @param File $parent
     */
    function _saveNewLink( $form, $parent=null ) {
        $link = MaterialFactory::link();
        $link->setCourse( $this->_course );
        $link->setParent( $parent );
        $link->setOwner( $_SESSION['user'] );
        $link->setDescription( $form->exportValue("material-description") );
        $link->setMaterialText("");
        $link->setURI( MaterialForm::normalizeURI( $form->exportValue("link-uri") ) );
        $link->setAddTime( new Date() );

        // if no link name is given, use the URI as a name
        if ( trim( $form->exportValue("material-name") ) == "" ) {
            $link->setName( $form->exportValue("link-uri") );
        }
        else {
            $link->setName( $form->exportValue("material-name") );
        }

        $link->save();
    }
	
    /**
     * Shows form for adding new text, when form is submitted, adds the text
     *
     * If parent folder is specified, the text is added inside that.
     *
     * @access private
     * @param Material $parent  parent folder
     * @return mixed
     */
    function _addText( $parent=null )
    {
        // if uploading is disabled for students - error NEEDS TO BE ADDED CONF file
        if ( !( $this->_userCanEditThisCourse() || Configuration::getStudentCanAddText() ) ) {
            return $this->accessDeniedPage( _("Only teacher can upload text.") );
        }

        // if the parent folder is specified, check that it is a correct one
        if ( isset($parent)  &&  $parent->getType() != 'FOLDER' ) {
            return $this->errorPage( _("You can only add text inside folders.") );
        }

        // construct HTML form for adding new text
        $form = new HTML_QuickForm();
        // text title form
        MaterialForm::addTextTitleField( $form );
        // text Editor form
        MaterialForm::addEditorField( $form );
        // submit button
        $form->addElement(
            'submit',
            'submit',
            _("Add text")
        );

        // validate the form
        if ( $form->validate() ) {
            // if validation succeeded, create new Text object and save it to database
            $this->_saveNewText( $form, $parent );

            // Send user back to the parent folder
            if ( isset($parent) ) {
                $this->redirectInsideCourseModule( 'view', $parent->getID() );
            }
            else {
                $this->redirectInsideCourseModule();
            }
            return false;
        }

        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        // display the form
        $html = $this->title( _("Add text") );
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );
        $html .= HTML::backLink( $this->relativeURI(), _("Return to the list of materials"), STANDALONE );
        return $html;
    }
	
/**	
     * Saves the info about new text from HTML form to database
     *
     * @access private
     * @param HTML_QuickForm $form
     * @param File $parent
     */
    function _saveNewText( $form, $parent=null ) {
        $text = MaterialFactory::text();
        $text->setCourse( $this->_course );
        $text->setParent( $parent );
        $text->setOwner( $_SESSION['user'] );
		$text->setMaterialText( $form->exportValue("material-text") );
		$text->setDescription( "" );
        $text->setAddTime( new Date() );
        $text->setName( $form->exportValue("material-name") );
        $text->save();
    }
	
    /**
     * Shows form for adding new embed material, when form is submitted, adds the embed material
     *
     * If parent folder is specified, the text is added inside that.
     *
     * @access private
     * @param Material $parent  parent folder
     * @return mixed
     */
    function _addEmbed( $parent=null )
    {
        // if uploading is disabled for students - error 
		if ( !( $this->_userCanEditThisCourse() || Configuration::getStudentCanAddEmbed() ) ) 		       {
            return $this->accessDeniedPage( _("Only teacher can add embed content.") );
        }

        // if the parent folder is specified, check that it is a correct one
        if ( isset($parent)  &&  $parent->getType() != 'FOLDER' ) {
            return $this->errorPage( _("You can only add embed content inside folders.") );
        }

        // construct HTML form for adding new text
        $form = new HTML_QuickForm();
        // embed title form
        MaterialForm::addEmbedNameField( $form );
        // embed code form
        MaterialForm::addEmbedCodeField( $form );
        // embed description form
        MaterialForm::addDescriptionField( $form );
        // submit button
        $form->addElement(
            'submit',
            'submit',
            _("Add embed")
        );

        // validate the form
        if ( $form->validate() ) {
            // if validation succeeded, create new Embed object and save it to database
            $this->_saveNewEmbed( $form, $parent );

            // Send user back to the parent folder
            if ( isset($parent) ) {
                $this->redirectInsideCourseModule( 'view', $parent->getID() );
            }
            else {
                $this->redirectInsideCourseModule();
            }
            return false;
        }

        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        // display the form
        $html = $this->title( _("Add embed") );
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );
        $html .= HTML::backLink( $this->relativeURI(), _("Return to the list of materials"), STANDALONE );
        return $html;
    }
/**
     * Saves the info about new embed content from HTML form to database
     *
     * @access private
     * @param HTML_QuickForm $form
     * @param File $parent
     */
	function _saveNewEmbed( $form, $parent ) {
        $embed = MaterialFactory::embed();
        $embed->setCourse( $this->_course );
        $embed->setParent( $parent );
        $embed->setOwner( $_SESSION['user'] );
		$embed->setName( $form->exportValue("material-name") );
		$embed->setMaterialText( $form->exportValue("material-text") );
        $embed->setDescription( $form->exportValue("material-description") );
        $embed->setAddTime( new Date() );
        $embed->save();
    }


    /**
     * Shows form for adding new file, when form is submitted, adds the file
     *
     * If parent folder is specified, the file is added inside that.
     *
     * @access private
     * @param Material $parent  parent folder
     * @return mixed
     */
    function _addFile( $parent=null )
    {
        // if uploading is disabled for students - error
        if ( !( $this->_userCanEditThisCourse() || Configuration::getStudentCanUploadFiles() ) ) {
            return $this->accessDeniedPage( _("Only teacher can upload files.") );
        }

        // if the parent folder is specified, check that it is a correct one
        if ( isset($parent)  &&  $parent->getType() != 'FOLDER' ) {
            return $this->errorPage( _("You can only add files inside folders.") );
        }

        // construct HTML form for adding new file
        $form = new HTML_QuickForm();
        // file upload (true marks, that the file is required)
        MaterialForm::addFileUploadField( $form, true );
        // file name
        MaterialForm::addFileNameField( $form );
        // file description
        MaterialForm::addDescriptionField( $form );
        // submit button
        $form->addElement(
            'submit',
            'submit',
            _("Add file")
        );

        // validate the form
        if ( $form->validate() ) {
            // if validation succeeded, create new Folder object and save it to database
            $this->_saveNewFile( $form, $parent );

            // Send user back to the parent folder
            if ( isset($parent) ) {
                $this->redirectInsideCourseModule( 'view', $parent->getID() );
            }
            else {
                $this->redirectInsideCourseModule();
            }
            return false;
        }

        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        // display the form
        $html = $this->title( _("Add file") );
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );
        $html .= HTML::backLink( $this->relativeURI(), _("Return to the list of materials"), STANDALONE );
        return $html;
    }


    /**
     * Saves the info about new file from HTML form to database
     *
     * @access private
     * @param HTML_QuickForm $form
     * @param File $parent
     */
    function _saveNewFile( $form, $parent=null ) {
        $file = MaterialFactory::file();
        $file->setCourse( $this->_course );
        $file->setParent( $parent );
        $file->setOwner( $_SESSION['user'] );
        $file->setDescription( $form->exportValue("material-description") );
        $file->setAddTime( new Date() );

        // get array with uploaded file info
        $file_element = $form->getElement("file");
        $uploaded_file = $file_element->getValue();

        // get the size of the file
        $file->setSize( $uploaded_file['size'] );

        // determine the MIME type of the file from the name of uploaded file
        $mime_type = MimeType::determineFromFilename( $uploaded_file['name'] );
        $file->setMimeType( $mime_type );
        $file->setMaterialText( "" );

        // if no file name is given, use the original file name
        if ( trim( $form->exportValue("material-name") ) == "" ) {
            $file->setName( $uploaded_file['name'] );
        }
        else {
            $file->setName( $form->exportValue("material-name") );
        }

        $file->save();

        // now the file contents also have to be saved
        $file->loadContentFromFile( $uploaded_file['tmp_name'] );
        // remove the uploaded file
        unlink( $uploaded_file['tmp_name'] );
    }


    /**
     * Shows form with material info and allows user to modify it
     *
     * @access private
     * @param Material $material  the material to be edited
     * @return mixed  HTML fragment with form
     */
    function _editMaterial( $material )
    {
        // must not be null
        if ( !isset($material) ) {
            trigger_error( "The material to edit must be specified.", E_USER_ERROR );
        }

        // ensure, that user is teacher of the course
        if ( !$this->_userCanEditThisCourse() ) {
            return $this->accessDeniedPage( _("You are not allowed to edit this material.") );
        }


        // construct HTML form for editing material
        $form = $this->_editMaterialForm( $material );


        // validate the form
        if ( $form->validate() ) {
            // if validation succeeded, apply the new values from form to material, and save it
            $this->_changeMaterial( $form, $material );

            // Send user back to the parent folder
            $parent = $material->getParent();
            // Send user back to the parent folder
            if ( isset($parent) ) {
                $this->redirectInsideCourseModule( 'view', $parent->getID() );
            }
            else {
                $this->redirectInsideCourseModule();
            }
            return false;
        }


        // change the menu item to be clickable link
        $this->_selected_menu_item_status='HIGHLIGHTED';

        // display the form
        $html = $this->title( $this->_editFormSubmitTitle( $material->getType() ) );
        $html .= HTML_QuickForm_Renderer_VIKO::render( $form );
        $html .= HTML::backLink( $this->relativeURI(), _("Return to the list of materials"), STANDALONE );
        return $html;
    }


    /**
     * Applies new values from submitted form to material and saves the material
     *
     * @access private
     * @param HTML_QuickForm $form  form with new values
     * @param Material $material  the material to change
     * @return mixed
     */
    function _changeMaterial( $form, $material )
    {
        // almost all materials have name and description fields
        $material->setName( $form->exportValue("material-name") );
        $material->setDescription( $form->exportValue("material-description") );
        $material->setMaterialText( $form->exportValue("material-text") );
		

        if ( $material->getType() == 'LINK' ) {
            // link has URI field
            $material->setURI( MaterialForm::normalizeURI( $form->exportValue("link-uri") ) );

            // if no link name is given, use the URI as link name
            if ( trim( $form->exportValue("material-name") ) == "" ) {
                $material->setName( $form->exportValue("link-uri") );
            }
        }
        elseif ( $material->getType() == 'FILE' ) {
            // if new file was uploaded, replace the current file in database with that
            $file_element = $form->getElement("file");
            if ( $file_element->isUploadedFile() ) {
                $uploaded_file = $file_element->getValue();

                // get the size of the file
                $material->setSize( $uploaded_file['size'] );

                // determine the MIME type of the file from the name of uploaded file
                $mime_type = MimeType::determineFromFilename( $uploaded_file['name'] );
                $material->setMimeType( $mime_type );

                // if no file name is given, use the original file name
                if ( trim( $form->exportValue("material-name") ) == "" ) {
                    $material->setName( $uploaded_file['name'] );
                }

                // change file contents
                $material->loadContentFromFile( $uploaded_file['tmp_name'] );

                // remove the uploaded file
                unlink( $uploaded_file['tmp_name'] );
            }

        }

        $material->save();
    }


    /**
     * Creates HTML form object for editing material
     *
     * @access private
     * @param Material $material  the material to edit
     * @return HTML_QuickForm
     */
    function _editMaterialForm( $material )
    {
        // construct HTML form for editing
        $form = new HTML_QuickForm();

        if ( $material->getType() == 'FILE' ) {
            // file upload (false marks, that the file is NOT required)
            MaterialForm::addFileUploadField( $form, false );
            // file name
            MaterialForm::addFileNameField( $form, $material->getName() );
			// text descrition form
			MaterialForm::addDescriptionField( $form, $material->getDescription() );

        }
        elseif ( $material->getType() == 'FOLDER' ) {
            // folder name
            MaterialForm::addFolderNameField( $form, $material->getName() );
			// text description form
			MaterialForm::addDescriptionField( $form, $material->getDescription() );

        }
        elseif ( $material->getType() == 'LINK' ) {
            // link URI
            MaterialForm::addURIField( $form, $material->getURI() );
            // link name
            MaterialForm::addLinkNameField( $form, $material->getName() );
			// text descrition form
			MaterialForm::addDescriptionField( $form, $material->getDescription() );
			
        }elseif ( $material->getType() == 'TEXT' ) {
			// text title form
	        MaterialForm::addTextTitleField( $form, $material->getName() );
        	// text Editor form
    	    MaterialForm::addEditorField( $form, $material->getMaterialText());
  
		}elseif ( $material->getType() == 'EMBED' ) {
			// text title form
			MaterialForm::addEmbedNameField( $form, $material->getName());
			        // embed code form
	        MaterialForm::addEmbedCodeField( $form, $material->getMaterialText() );
			// text descrition form
			MaterialForm::addDescriptionField( $form, $material->getDescription() );
        }
        $form->addElement(
            'submit',
            'submit',
            $this->_editFormSubmitTitle( $material->getType() )
        );
        return $form;
    }
    /**
     * Returns appropriate title for the form, based on material type
     *
     * @access private
     * @param string $material_type  either FILE, FOLDER or LINK
     * @return string
     */
    function _editFormSubmitTitle( $material_type )
    {
        switch ( $material_type ) {
            case 'FILE': return _("Change file"); break;
            case 'FOLDER': return _("Change folder"); break;
            case 'LINK': return _("Change link"); break;
			case 'TEXT': return _("Change text"); break;
			case 'EMBED': return _("Change embed"); break;
            default: trigger_error("Unknown material type.", E_USER_ERROR);
        }
    }
}
?>