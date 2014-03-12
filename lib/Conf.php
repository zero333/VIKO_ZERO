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
 * Manages the loading and storage of VIKO configuration
 *
 * This class is only accessed through {@link Configuration} class,
 * which keeps a single instance of this class (the Singleton pattern).
 */
class Conf
{

    /**
     * Configuration data
     *
     * @access private
     * @var array
     */
    var $_data = array();

    /**
     * Constructs new Conf instance
     */
    function Conf()
    {
        $this->_data = array(
            'database_username' => false,  # we default to no username
            'database_password' => false,  # and no password
            'database_name' => 'viko',
            'database_host' => 'localhost',
            'database_type' => 'mysql',
            'locale' => 'en_US',
            'short_date' => '%d.%m',
            'install_path' => '/', 
            'long_date' => '%e. %B %Y',
            'long_date_with_weekday' => '%A, %e. %B %Y',
            'short_time' => '%d.%m, %H:%M',
            'long_time' => '%e. %B %Y, %H:%M',
            'course_modules' => array(
                1 => 'course-info',
                2 => 'students',
                3 => 'lessons',
	        4 => 'marks',
                5 => 'materials',
                6 => 'tasks',
                7 => 'forum',
                 
            ),
            'allow_registration' => 'yes',
            'max_file_size' => 100*1024*1024,  # 100MB
            'student_can_upload_files' => 'no',
            'student_can_add_links' => 'yes',
			'student_can_add_text' => 'no',
			'student_can_add_embed' => 'yes',
        );
    }

    /**
     * Returnes the value of specified configuration option
     *
     * @access public
     * @param string $option the option name, whos' value to return
     * @return mixed value of the configuration option
     */
    function get( $option )
    {
        if ( array_key_exists($option, $this->_data) ) {
            return $this->_data[$option];
        }
        else {
            trigger_error("Unknown configuration option '$option'", E_USER_ERROR);
        }
    }

    /**
     * Loads configuration from file
     *
     * @access public
     * @param string $conf_file path to configuration file to load
     */
    function load( $conf_file )
    {
        $file = @fopen( $conf_file, 'r' );
        if ( $file ) {

            // init the line count
            $line_nr=0;

            // read the file line-by-line (use buffer of 4KB)
            while ( !feof( $file ) ) {
                $line = fgets( $file, 4096 );

                // count line numbers in configuration file
                $line_nr++;

                // remove comments
                $line = preg_replace('/#.*$/', '', $line);

                // if the line contains configuration option,
                // extract the option name and value
                // The value may contain any characters,
                // but the spaces from beginning to end are removed
                if ( preg_match('/^ \s* ([a-zA-Z0-9_]+) \s* = \s* ([^\s]|[^\s].*[^\s]) \s* $/x', $line, $matches) ) {
                    $this->_load_single_option( $matches[1], $matches[2] );
                }
                elseif ( preg_match('/^ \s* $/x', $line) ) {
                    // the line contains only whitespace, ignore it
                }
                else {
                    // an error in configuration file
                    trigger_error(
                        "Syntax error in configuration file '$conf_file' on line $line_nr.",
                        E_USER_ERROR
                    );
                }
            }
            // if we managed to open file, close it after all lines are read
            fclose( $file );
        }
        else {
            // error when opening configuration file
            trigger_error(
                "Error when opening configuration file '$conf_file'.",
                E_USER_ERROR
            );
        }
    }

    /**
     * Loads single configuration option
     *
     * @access private
     * @param string $option name of the configuration option
     * @param string $value value of the configuration option
     */
    function _load_single_option( $option, $value )
    {
        // if the configuration option matches one of the keys listed in the
        // _data array, the assign value to that key.
        // Otherwise check if the option name begins with 'course_modules_',
        // if so, create an appropriate key-value combination into that _data key.
        // if all the above fails, generate error.
        if ( array_key_exists($option, $this->_data) ) {
            $this->_data[$option] = $value;
        }
        elseif ( preg_match( '/^course_modules_([0-9]+)$/', $option, $matches ) ) {
            $this->_data['course_modules'][ $matches[1] ] = $value;
        }
        else {
            trigger_error("Unknown configuration option '$option'", E_USER_ERROR);
        }
    }

}

?>