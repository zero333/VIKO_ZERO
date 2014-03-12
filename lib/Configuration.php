<?php

/**
 * Contains the Conf class
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
 * Uses the {@link Conf} class to store and load the configuration
 */
require_once 'Conf.php';


/**
 * Provides access to configuration settings from any part of the program
 *
 * This class implements the 'singleton' design pattern.
 * Use the getFoo methods to access different configuration settings.
 * For example to perform some DBMS specific queries:
 *
 * <pre>
 * if ( Configuration::getDatabaseType() == "mysql" ) {
 *     // make MySQL specific query
 * }
 * elseif ( Configuration::getLanguage() == "pgsql" ) {
 *     // make PostgreSQL specific query
 * }
 * </pre>
 */
class Configuration {

    /**
     * Returns the {@link Conf} instance
     *
     * If configuration is already loaded, returnes the existing instance,
     * otherwise loads the configuration and returnes newly created instance.
     *
     * @access public
     * @static
     * @return Conf reference to Conf instance
     */
    static function get( $conf_file="conf.php" )
    {
        static $conf = null;
        if ( $conf != null ) {
            $conf_ref = $conf;
            return $conf_ref;
        }
        else {
            $conf = new Conf();
            $conf->load( $conf_file );
            $conf_ref = $conf;
            return $conf_ref;
        }
    }


    /**
     * Returns the type of DBMS
     *
     * @access public
     * @static
     * @return string database backend identifier
     */
    static function getDatabaseType()
    {
        $conf = Configuration::get();
        return $conf->get('database_type');
    }


    /**
     * Returns the host where the database server runs
     *
     * @access public
     * @static
     * @return string database host
     */
    static function getDatabaseHost()
    {
        $conf = Configuration::get();
        return $conf->get('database_host');
    }


    /**
     * Returns the name of the database
     *
     * @access public
     * @static
     * @return string database name
     */
    static function getDatabaseName()
    {
        $conf = Configuration::get();
        return $conf->get('database_name');
    }


    /**
     * Returns the username to access the database
     *
     * @access public
     * @static
     * @return string database username
     */
    static function getDatabaseUsername()
    {
        $conf = Configuration::get();
        return $conf->get('database_username');
    }


    /**
     * Returns the password to access the database
     *
     * @access public
     * @static
     * @return string database password
     */
    static function getDatabasePassword()
    {
        $conf = Configuration::get();
        return $conf->get('database_password');
    }


    /**
	* Returns path, where the VIKO is installed on a site. 
	* 
 	* @access public 
 	* @static 
 	* @return string path 
 	*/ 
     static function getInstallPath() 
     { 
     $conf = Configuration::get(); 
     $path = $conf->get('install_path'); 

     // The path must begin end with a slash "/" 
     // if it doesn't, add the required characters 
     $path = ( substr($path, 0, 1) == '/' ) ? $path : "/$path"; 
     $path = ( substr($path, -1, 1) == '/' ) ? $path : "$path/"; 

     return $path; 
     } 
 		 
 		 
 	/** 
    * Returns the default locale code for the user interface
    *
     * e.g. "en_US" for english and "ru_RU" for russian.
     *
     * @access public
     * @static
     * @return string locale code
     */
    static function getDefaultLocale()
    {
        $conf = Configuration::get();
        return $conf->get('locale');
    }

    /**
     * Returns the locale code for the user interface
     *
     * First the program tries to retrieve locale from a cookie.
     * If that fails, the default locale from configuration file is used.
     * Also the cookie is set to that locale.
     *
     * @access public
     * @static
     * @return string locale code
     */
    static function getLocale()
    {
        // if cookie is set, get the locale from cookie
        if ( isset($_COOKIE["locale"]) ) {
            // ensure, that the cookie contains a supported locale, not some crap
            if ( in_array( $_COOKIE["locale"], array("et_EE", "en_US", "ru_RU") ) ) {
                return $_COOKIE["locale"];
            }
        }

        // get default locale from configuration and save it
        $locale = Configuration::getDefaultLocale();
        Configuration::setLocale( $locale );

        return $locale;
    }


    /**
     * Sets the locale code for the user interface
     *
     * Sets the value to be returned by getLocale() method.
     * At the background it sets cookie 'locale' to contain
     * specified locale.
     *
     * The cookie expires after 30 days. This method is also
     * used to update the expiration time of the cookie.
     *
     * @access public
     * @static
     * @param string $locale code
     */
    static function setLocale( $locale )
    {
        $_COOKIE["locale"] = $locale;
    }


    /**
     * Returns the MySQL collation name
     *
     * The collation is determined by locale.
     *
     * @access public
     * @static
     * @return string collation
     */
    static function getCollation()
    {
        $collations = array(
            "en_US" => "utf8_general_ci",
            "et_EE" => "utf8_estonian_ci",
            "ru_RU" => "utf8_general_ci",
        );

        $locale = Configuration::getLocale();
        if ( isset($collations[$locale]) ) {
            return $collations[$locale];
        }
        else {
            trigger_error("Unknown locale '$locale'");
        }
    }

    /**
     * Returns formatting string for long dates
     *
     * @access public
     * @static
     * @return string date format
     */
    static function getLongDate()
    {
        $conf = Configuration::get();
        return $conf->get('long_date');
    }

    /**
     * Returns formatting string for long dates with weekday name
     *
     * @access public
     * @static
     * @return string date format
     */
    static function getLongDateWithWeekday()
    {
        $conf = Configuration::get();
        return $conf->get('long_date_with_weekday');
    }

    /**
     * Returns formatting string for short dates
     *
     * @access public
     * @static
     * @return string date format
     */
    static function getShortDate()
    {
        $conf = Configuration::get();
        return $conf->get('short_date');
    }

    /**
     * Returns formatting string for full date with time
     *
     * @access public
     * @static
     * @return string date format
     */
    static function getLongTime()
    {
        $conf = Configuration::get();
        return $conf->get('long_time');
    }

    /**
     * Returns formatting string for short date with time
     *
     * @access public
     * @static
     * @return string date format
     */
    static function getShortTime()
    {
        $conf = Configuration::get();
        return $conf->get('short_time');
    }

    /**
     * Returns ordred list of course module identifiers
     *
     * This list is used to determine the order of modules in the course menu.
     *
     * @access public
     * @static
     * @return string list of course module identifiers
     */
    static function getCourseModules()
    {
        $conf = Configuration::get();
        return $conf->get('course_modules');
    }


    /**
     * Returns true, if students are allowed to register themselves
     *
     * @access public
     * @static
     * @return bool true, when allowed
     */
    static function getAllowRegistration()
    {
        $conf = Configuration::get();
        return ( $conf->get('allow_registration') == "yes" );
    }


    /**
     * Returns the maximum file size in bytes
     *
     * This is used when limiting the size of uploaded files.
     *
     * @access public
     * @static
     * @return bool true, when allowed
     */
    static function getMaxFileSize()
    {
        $conf = Configuration::get();
        return ( $conf->get('max_file_size') );
    }


    /**
     * Returns true, if students are allowed to upload files into course materials
     *
     * @access public
     * @static
     * @return bool true, when allowed
     */
    static function getStudentCanUploadFiles()
    {
        $conf = Configuration::get();
        return ( $conf->get('student_can_upload_files') == "yes" );
    }    
	
	/**
     * Returns true, if students are allowed to add text into course materials
     *
     * @access public
     * @static
     * @return bool true, when allowed
     */
    static function getStudentCanAddText()
    {
        $conf = Configuration::get();
        return ( $conf->get('student_can_add_text') == "yes" );
    }


	/**
     * Returns true, if students are allowed to add embed contents into course materials
     *
     * @access public
     * @static
     * @return bool true, when allowed
     */
    static function getStudentCanAddEmbed()
    {
        $conf = Configuration::get();
        return ( $conf->get('student_can_add_embed') == "yes" );
    }
	
	
	
	
    /**
     * Returns true, if students are allowed to add links into course materials
     *
     * @access public
     * @static
     * @return bool true, when allowed
     */
    static function getStudentCanAddLinks()
    {
        $conf = Configuration::get();
        return ( $conf->get('student_can_add_links') == "yes" );
    }


}


?>