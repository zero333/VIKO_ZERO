<?php

/**
 * Contains the MaterialForm class
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

/**
 * Most of the class methods need $form parameter which must be of type HTML_QuickForm
 */
require_once 'Pear/HTML/QuickForm.php';

/**
 * The class accesses configuration to retrieve the max file size limit
 */
require_once 'lib/Configuration.php';

/**
 * The class uses File::getHumanReadableSize() before printing out the file size limit
 */
require_once 'lib/File.php';

/**
 * Contains methods for creating file upload forms
 *
 */
class MaterialForm {
    
	/**
     * Adds field for embed name
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param bool $required  if true, then uploading the file is required
     */
	function addEmbedNameField( &$form, $default=null )
    	{
        $form->addElement(
            'text',
            'material-name',
            _("Embed title").':',
            array( 'class'=>'long-text', 'id'=>'material-name' )
        );
	
        // The field is required - no point to add folder without name
        $form->addRule(
            'material-name',
			_("Each Embed content must have Title."),
            'required'
        );

        // no more than 255 characters are allowed for text_name field in Files table
        $form->addRule(
            'material-name',
            sprintf( _("Maximum %s characters allowed in title name."), 255 ),
            'maxlength',
            255
        );

        // if default is given, use it
        if ( isset($default) ) {
            $form->setDefaults( array( "material-name" => $default ) );
        }
    }
	

	
	/**
     * Adds field for showing embed name
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param bool $required  if true, then uploading the file is required
     */
	function addEmbedShowNameField( &$form, $default=null )
		{
        $form->addElement(
            'static',
            'material-name'
        );
	
        // if default is given, use it
        if ( isset($default) ) {
            $form->setDefaults( array( "material-name" => $default ) );
        }
    }
	
	
	 /**
     * Adds field for embed code
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param string default  the default/existing URI
     */
	function addEmbedCodeField( &$form, $default=null )
		{
        $form->addElement(
            'textarea',
            'material-text',
            _("Embed code") . ':',
			array( 'rows'=>'8', 'cols'=>'45', 'id'=>'material-text' )        
			);

        // The field is required
        $form->addRule(
            'material-text',
			_("You have to specify the address of the embed content you want to add."),
            'required'
        );

        // no more than 65535 characters are allowed for embed_code field in Files table
        $form->addRule(
            'embed-code',
            sprintf( _("Maximum %s characters allowed in embed content address."), 65535 ),
            'maxlength',
            65535
        );

        // if default is given, use it
        if ( isset($default) ) {
            $form->setDefaults( array( "material-text" => $default ) );
        }
    }


   /**
     * Adds field for file upload
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param bool $required  if true, then uploading the file is required
     */
    function addFileUploadField( &$form, $required=false )
    {
        $form->addElement(
            'file',
            'file',
            _("File") . ':',
            array( 'id'=>'file' )
        );


        // if the required option is specified, then make the field a required one
        if ( $required ) {
            $form->addRule(
                'file',
                _("You have to select the file, you want to upload."),
                'uploadedfile'
            );
        }


        // there should be a reasonable upload limit for file size
        // that limit is specified in configuration file
        $max_size = Configuration::getMaxFileSize();
        $readable_max_size = File::getHumanReadableSize( $max_size );
        $form->addRule(
            'file',
            sprintf( _("Files larger than %s are not allowed."), $readable_max_size ),
            'maxfilesize',
            $max_size
        );
    }


    /**
     * Adds field for file name
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param string default  the default/existing file name
     */
    function addFileNameField( &$form, $default=null )
    {
        $form->addElement(
            'text',
            'material-name',
            _("New Filename") . ':',
            array( 'class'=>'long-text', 'id'=>'material-name' )
        );

        // no more than 255 characters are allowed for file_name field in Files table
        $form->addRule(
            'material-name',
            sprintf( _("Maximum %s characters allowed in file name."), 255 ),
            'maxlength',
            255
        );

        // if default is given, use it
        if ( isset($default) ) {
            $form->setDefaults( array( "material-name" => $default ) );
        }
    }


    /**
     * Adds field for folder name
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param string default  the default/existing folder name
     */
    function addFolderNameField( &$form, $default=null )
    {
        $form->addElement(
            'text',
            'material-name',
            _("Folder name") . ':',
            array( 'class'=>'long-text', 'id'=>'material-name' )
        );

        // The field is required - no point to add folder without name
        $form->addRule(
            'material-name',
            _("Each folder must have name."),
            'required'
        );

        // no more than 255 characters are allowed for file_name field in Files table
        $form->addRule(
            'material-name',
            sprintf( _("Maximum %s characters allowed in folder name."), 255 ),
            'maxlength',
            255
        );

        // if default is given, use it
        if ( isset($default) ) {
            $form->setDefaults( array( "material-name" => $default ) );
        }
    }



 	/**
     * Adds field for text heading name
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param string default  the default/existing folder name
     */
    function addTextTitleField( &$form, $default=null )
    {
        $form->addElement(
            'text',
            'material-name',
			_("Title").':',
			array( 'class'=>'long-text', 'id'=>'material-name' )
        );

        // The field is required - no point to add folder without name
        $form->addRule(
            'material-name',
			_("Each text must have title."),
            'required'
        );

        // no more than 255 characters are allowed for text_name field in Files table
        $form->addRule(
            'material-name',
            sprintf( _("Maximum %s characters allowed in title name."), 255 ),
            'maxlength',
            255
        );

        // if default is given, use it
        if ( isset($default) ) {
            $form->setDefaults( array( "material-name" => $default ) );
        }
    }
	
	
	
    /**
     * Adds field for link name
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param string default  the default/existing link title
     */
    function addLinkNameField( &$form, $default=null )
    {
        $form->addElement(
            'text',
            'material-name',
            _("Link name") . ':',
            array( 'class'=>'long-text', 'id'=>'material-name' )
        );

        // no more than 255 characters are allowed for file_name field in Files table
        $form->addRule(
            'material-name',
            sprintf( _("Maximum %s characters allowed in link name."), 255 ),
            'maxlength',
            255
        );

        // if default is given, use it
        if ( isset($default) ) {
            $form->setDefaults( array( "material-name" => $default ) );
        }
    }


    /**
     * Adds field for material description
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param string default  the default/existing description
     */
    function addDescriptionField( &$form, $default=null )
    {
        $form->addElement(
            'textarea',
            'material-description',
            _("Description") . ':',
            array( 'rows'=>'12', 'cols'=>'45', 'id'=>'material-description' )
        );

        // no more than 65535 characters are allowed for file_description field in Files table
        $form->addRule(
            'material-description',
            sprintf( _("Maximum %s characters allowed in description."), 65535 ),
            'maxlength',
            65535
        );

        // if default is given, use it
        if ( isset($default) ) {
            $form->setDefaults( array( "material-description" => $default ) );
        }
    }
	
	
	/**
     * Adds field for editor
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param string default  the default/existing description
     */
    function addEditorField( &$form, $default=null )
    {
        $form->addElement(
            'tmce',
            'material-text','',array( "class" => "tmcearea")

			);

        // if default is given, use it
        if ( isset($default) ) {
            $form->setDefaults( array( "material-text" => $default ) );
        }
    }
	
	
	  /**
     * Adds field for link URI
     *
     * @static
     * @param HTML_QuickForm $form  the form object to decorate
     * @param string default  the default/existing URI
     */
    function addURIField( &$form, $default=null )
    {
        $form->addElement(
            'text',
            'link-uri',
            _("Web address") . ':',
            array( 'class'=>'long-text', 'id'=>'link-uri' )
        );

        // The field is required
        $form->addRule(
            'link-uri',
            _("You have to specify the address of the link you want to add."),
            'required'
        );

        // no more than 65535 characters are allowed for file_uri field in Files table
        $form->addRule(
            'link-uri',
            sprintf( _("Maximum %s characters allowed in address."), 65535 ),
            'maxlength',
            65535
        );

        // if default is given, use it
        if ( isset($default) ) {
            $form->setDefaults( array( "link-uri" => $default ) );
        }
    }


    /**
     * Adds "http://" to the given URI if it has no protocol specified
     *
     * For example, given string "www.example.com" normalizeURI returns "http://www.example.com".
     * Except when the parameter is empty string.
     *
     * @static
     * @param string $uri  a string possibly containing URI
     * @return string corrected URI
     */
    function normalizeURI( $uri )
    {
        if ( strlen($uri) > 0 && !preg_match( '!^[a-zA-z0-9]+://!', $uri ) ) {
            return "http://$uri";
        }
        else {
            return $uri;
        }
    }

}


?>