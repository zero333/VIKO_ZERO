<?php

/**
 * The main file of VIKO, all the pages are accessed through it
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

// turn on all errors
// this has to be at the very beginning to catch all the errors beginning from here.
error_reporting(E_ALL);

// Modify the include path to fit the requirements of VIKO
// after we have adjusted the include path, we can start including required libraries.
initialize_php_include_path();

/**
 * Main class for VIKO
 */
require_once 'lib/VIKO.php';

/**
 * To handle User object stored in session
 */
require_once 'lib/User.php';

/**
 * To handle course when loading course module
 */
require_once 'lib/Course.php';

/**
 * Module_Login is the default module (the front page)
 */
require_once 'lib/Module/Login.php';

/**
 * Module_Logout is another module, that doesn't require authentication
 */
require_once 'lib/Module/Logout.php';

/**
 * Module_Register is third module, that doesn't require authentication
 */
require_once 'lib/Module/Register.php';

/**
 * Module_ForgotPassword is fourth module, that doesn't require authentication
 */
require_once 'lib/Module/ForgotPassword.php';

/**
 * Module_Lang is fifth module, that doesn't require authentication
 */
require_once 'lib/Module/Lang.php';

/**
 * HTML class is needed for generating HTML
 */
require_once 'lib/HTML.php';

/**
 * Constants to be returned by user_can_view_course() function
 */
define("CAN_NOT_ACCESS_COURSE", 0 );
define("CAN_ACCESS_BECAUSE_IS_ADMIN", 1 );
define("CAN_ACCESS_BECAUSE_IS_SCHOOLADMIN", 2 );
define("CAN_ACCESS_BECAUSE_IS_TEACHER", 3 );
define("CAN_ACCESS_BECAUSE_IS_STUDENT", 4 );

// load VIKO configuration file
Configuration::get( getcwd() . "/viko.conf");

// we use sessions to handle logins
session_start();

// now, that configuration is loaded, we can set locales etc
// because the locale is defined in configuration file
initialize_localization();

// enter the main routine
viko_main();

exit();




function viko_main()
{
    // default module is login
    if ( isset($_GET['module']) ) {
        $module_name = $_GET['module'];
    }
    else {
        $module_name = "login";
    }

    // module parameters
    if ( isset($_GET['par']) ) {
        $module_parameters = $_GET['par'];
    }
    else {
        $module_parameters = array();        
    }

    // choose weather to load general module or course module
    if ( isset($_GET['course']) ) {
        load_course_module( $module_name, $_GET['course'], $module_parameters );
    }
    else {
        load_module( $module_name, $module_parameters );
    }
    
}


function load_module( $module_name, $module_parameters )
{
     
    $viko = new VIKO();

    $module = null;

    // five special modules, that dont require user to be logged in for accessing
    $modules_not_reeding_login = array(
        'login' => 'Module_Login',
        'logout' => 'Module_Logout',
        'register' => 'Module_Register',
        'forgot-password' => 'Module_ForgotPassword',
        'lang' => 'Module_Lang'
    );

    if ( isset( $modules_not_reeding_login[ $module_name ] ) ) {
        $module = new $modules_not_reeding_login[$module_name]( $module_parameters );
    }
    elseif ( isset( $_SESSION['user'] ) ) {

        // load all subclasses of Module
        $modules = load_submodules( "Module" );

        $classname = $modules[ $module_name ];

        $module = new $classname( $module_parameters );

        $viko->addMenuItem( Module::URI("logout"), _("Log out"), _("Exit VIKO") );
        $viko->addMenuItem( Module::URI("my-viko"), _("My VIKO"), _("Main page of VIKO") );
        if ( $module_name == 'my-viko' ) {
            $viko->selectMenuItem( Module::URI("my-viko") );
        }
        else {
            $viko->highlightMenuItem( Module::URI("my-viko") );
        }
        $viko->setEnvironment( $_SESSION['user']->getGroup() );
    }
    else {
        VIKO_unauthorized_access();
    }

    $content = $module->toHTML();
    if ( $content ) {
        $viko->setModuleID( $module->getID() );
        $viko->setTitle( $module->getTitle() );
        $viko->setContent( $content );
        $viko->addStylesheetList( $module->getStylesheets() );
    }
    else {
        // probably a redirect to another page was specified,
        // so we output nothing, except cookie for language
        update_locale_cookie();
        exit();
    }
    header("Content-type: text/html; charset=utf-8");
    update_locale_cookie();
    echo $viko->toHTML();
}


function load_course_module( $module_name, $course_id, $module_parameters )
{
    $viko = new VIKO();
    $course = new Course();
    $course->setID( $course_id );
     
    // check, that user is logged in
    if ( !isset( $_SESSION['user'] ) ) {
        VIKO_unauthorized_access();
    }

    // check, that a course with supplied ID exists
    if ( !$course->loadFromDatabaseByID() ) {
        VIKO_error(
            _("Error!"),
            HTML::errorPage( _("The course you are trying to access does not exist.") )
        );
    }

    // check that user is authorised to access the course
    $can_access_course = user_can_view_course( $_SESSION['user'], $course );
    if ( $can_access_course == CAN_NOT_ACCESS_COURSE ) {
        VIKO_error(
            _("Unauthorized access!"),
            HTML::accessDeniedPage( _("You are not a member of the course you are trying to access.") )
        );
    }
    // if the user can access the course, because he is student on that course,
    // the we have to notify the course that this user is accessing it.
    // ( One result of the notification will be that total_views in Courses_Users incremented. )
    if ( $can_access_course == CAN_ACCESS_BECAUSE_IS_STUDENT ) {
        $course->accessByStudent( $_SESSION['user'] );
    }

    $viko->addMenuItem( Module::URI("logout"), _("Log out"), _("Exit VIKO") );
    $viko->addMenuItem( Module::URI("my-viko"), _("My VIKO"), _("Main page of VIKO") );

    // load all subclasses of Module_Course
    $course_modules = load_submodules( "Module_Course" );
    
   
    $course_modules_order = Configuration::getCourseModules();

    // use the order defined in configuration to
    // read names, descriptions and ID-s from modules
    // and add VIKO course menu entries based on that information
    foreach ($course_modules_order as $ident ) {
        $classname = $course_modules[$ident];
        if ( user_can_view_course_module( $_SESSION['user'], $classname ) ) {
           
            $viko->addMenuItem(                 
                Module::URI( $course->getID(), $ident),
                @call_user_func( array($classname, 'getName') ),
                @call_user_func( array($classname, 'getDescription') )
            );
        }
        $modules_in_menu[$ident] = true;
    }

    // read the names, descriptions and ID-s from other remaining modules
    // and add VIKO course menu entries based on that information
    foreach ($course_modules as $ident => $classname ) {
        if ( @!$modules_in_menu[$ident] ) {
             
             if ( user_can_view_course_module( $_SESSION['user'], $classname ) ) {
                $viko->addMenuItem(
                    "/" . $course->getID() . "/" . $ident,
                    @call_user_func( array($classname, 'getName') ),
                    @call_user_func( array($classname, 'getDescription') )
                        
                );
            }
        }
    }

    // create an instance of the appropriate course module
    $classname = $course_modules[ $module_name ];
    $module = new $classname( $course, $module_parameters );

    $viko->setContent( $module->toHTML() ); // toHTML should be called before getTitle
    $viko->setModuleID( $module->getID() );
    $viko->setTitle( $module->getTitle() );
    $viko->addStylesheetList( $module->getStylesheets() );
    // select the corresponding menu item
    $active_link = Module::URI( $course->getID(), $module->getID());
    if ( $module->getSelectedMenuItemStatus() == 'SELECTED' ) {
        $viko->selectMenuItem( $active_link );
    }
    else {
        $viko->highlightMenuItem( $active_link );
    }
    $viko->setEnvironment( $_SESSION['user']->getGroup() );

    header("Content-type: text/html; charset=utf-8");
    update_locale_cookie();
    echo $viko->toHTML();
}


/**
 * Returns associative array of pairs module_id => module_classname
 *
 * @param string $parent_module   name of the parent module class
 * @return array                  module ID-s and classnames
 */
function load_submodules( $parent_module )
{
    // if the parent module is Module_Course,
    // then the path is Module/Course
    $module_path = str_replace( '_', '/', $parent_module );
   
    $modules = array();
    // open directory of class Module subclasses
    if ( $handle = opendir( getcwd() . "/lib/" . $module_path) ) {
        // read all files from the direcory
        while ( false !== ($file = readdir($handle)) ) {
            // only act on PHP files
            if ( preg_match('/^(.+)\.php$/', $file, $matches) ) {
                
                // include class definition
                require_once "lib/" . $module_path . "/" . $file;
                // get the class name
                $classname = $parent_module . "_" . $matches[1];
             // get the identifier of module
                @$ident = call_user_func( array($classname, 'getID') );
                // associate module identifier and class name
                $modules[$ident] = $classname;

            }
        }
        // close dir after being opened
        closedir($handle);
    }
    return $modules;
}


/**
 * Determines weather user is authorized to access course
 *
 * User can access the course only if:
 *
 * <ul>
 * <li>CAN_ACCESS_BECAUSE_IS_ADMIN - user is global admin</li>
 * <li>CAN_ACCESS_BECAUSE_IS_SCHOOLADMIN - user is admin of the same school the course is in</li>
 * <li>CAN_ACCESS_BECAUSE_IS_TEACHER - user is the teacher of this course</li>
 * <li>CAN_ACCESS_BECAUSE_IS_STUDENT - user is a student of this course</li>
 * </ul>
 *
 * If user can access the course, then a constant from the above list is
 * returned to indicate reason. On failure CAN_NOT_ACCESS_COURSE is returned.
 *
 * @param User $user the one who want's to access course
 * @param Course $course the course he tries to access
 * @return mixed success or error code
 */
function user_can_view_course( $user, $course )
{
    // test, if user is teacher of this course
    $course_teacher = $course->getTeacher();
    if ( $course_teacher->getID() == $user->getID() ) {
        return CAN_ACCESS_BECAUSE_IS_TEACHER;
    }

    // test, if user is a student of this course
    if ( $course->studentExists( $user ) ) {
        return CAN_ACCESS_BECAUSE_IS_STUDENT;
    }

    // test, if user is admin of the same school the course is in
    $user_is_schooladmin = $user->hasAccessToGroup('SCHOOLADMIN');
    $school_of_course = $course->getSchool();
    $school_of_user = $user->getSchool();
    $user_from_of_same_school = ( $school_of_course->getID() == $school_of_user->getID() );
    if ( $user_is_schooladmin && $user_from_of_same_school ) {
        return CAN_ACCESS_BECAUSE_IS_SCHOOLADMIN;
    }

    // test, if user is global admin
    if ( $user->hasAccessToGroup('ADMIN') ) {
        return CAN_ACCESS_BECAUSE_IS_ADMIN;
    }

    // if anything else, the user can't access the course
    return CAN_NOT_ACCESS_COURSE;
}


/**
 * Determines weather user can view specified course module
 *
 * Returns true if it is allowed.
 *
 * @param User $user
 * @param string $classname
 * @return bool
 */
function user_can_view_course_module( $user, $classname )
{
    if ( $_SESSION['user']->getGroup() == 'STUDENT' ) {
        return call_user_func( array($classname, 'studentIsAllowedToView') );
    }
    else {
        return true;
    }
}


/**
 * Create VIKO error page
 *
 * @param string $title    the title of the page
 * @param string $content  the whole contents of the page
 */
function VIKO_error( $title, $content )
{
    $viko = new VIKO();
    $viko->setTitle( $title );
    $viko->setContent( $content );

    // output page
    header("Content-type: text/html; charset=utf-8");
    echo $viko->toHTML();
    exit();
}


/**
 * Creates error page with title "Unauthorized access!"
 */
function VIKO_unauthorized_access()
{
    VIKO_error(
        _("Unauthorized access!"),
        HTML::accessDeniedPage(
            _("Either your session has expired or you are trying to access this page without authentication.")
        ) .
        HTML::p( HTML::a( Module::URI(), _("Please log in to VIKO and then try again.") ) )
    );
}


/**
 * Modifies PHP include path to contain paths to VIKO and PEAR code libraries
 *
 * VIKO code resides in two subdirectories of VIKO installation:
 *
 * <ul>
 * <li>lib/ - contains all VIKO-specific classes</li>
 * <li>PEAR/ - contains third-party packages from PEAR ( pear.php.net )</li>
 * </ul>
 *
 * We want that when we say: include("Foo.php"); the PHP to find the file when
 * there exists either lib/Foo.php or PEAR/Foo.php.
 *
 * This is why we have to modify PHP include path to include the paths to
 * both of these important directories.
 */
function initialize_php_include_path()
{
    // get current working directory
    $cwd = getcwd();

    // create path to VIKO code library
    $lib_path = "$cwd/lib";
    // create path to PEAR code library
    $PEAR_path = "$cwd/PEAR";

    // get the current PHP include path
    $old_path = ini_get("include_path");

    // insert the VIKO and PEAR library paths
    // at the beginning of PHP default include path
    ini_set( 'include_path', "$lib_path:$PEAR_path:$old_path" );
}


/**
 * Retrieves the locale from conf file and establishes it
 */
function initialize_localization()
{
    if ( !function_exists("bindtextdomain") ) {
        echo "ERROR: GetText functions are not available!";
    }

    putenv("LANG=" . Configuration::getLocale() );
    setlocale( LC_ALL, Configuration::getLocale(). ".UTF-8" );
    setlocale( LC_TIME, Configuration::getLocale(). ".UTF-8" );
    bindtextdomain( "VIKO", getcwd() . "/locale" );
    textdomain("VIKO");    
}


/**
 * Sends cookie with selected locale information
 */
function update_locale_cookie()
{
    $locale = Configuration::getLocale();
    // The cookie should last at least a month.
    $expires = time() + 60*60*24*30; // 30 days
    setcookie( "locale", $locale, $expires, Configuration::getInstallPath() );
}

?>
