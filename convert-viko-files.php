<?php
/**
 * Loads files in directory 'files' into database
 *
 * NOTE: This script has to be run <strong>after</strong> the VIKO database
 * has been updated. If run before - an error message is shown.
 *
 * The files will be loaded into table File_Contents.
 */

// let the files be read in 512KB chunks
define("FILE_CHUNK_SIZE", 512*1024);

// turn on all errors
// this has to be at the very beginning to catch all the errors beginning from here.
error_reporting(E_ALL);

// Modify the include path to fit the requirements of VIKO
// after we have adjusted the include path, we can start including required libraries.
initialize_php_include_path();

/**
 * HTML class is needed for generating HTML
 */
require_once "HTML.php";

/**
 * Main class for VIKO
 */
require_once 'VIKO.php';

/**
 * MimeType class is needed for determining MIME types
 */
require_once 'MimeType.php';

// load VIKO configuration file
Configuration::get( getcwd() . "/viko.conf");

// use ordinary database connection instead of DB object
// DB introduces too much overhead, and we need the fastest solution possible.
mysql_connect(
    Configuration::getDatabaseHost(),
    Configuration::getDatabaseUsername(),
    Configuration::getDatabasePassword()
);
mysql_select_db( Configuration::getDatabaseName() );



// first of all make sure that the database is updated before running this script.
// The old Files_Folder table should be dropped after the database update - so
// by checking the existance of that table we can find out, if the database is updated.
$files_folders_table = mysql_query("SHOW TABLES LIKE 'Files_Folders'");
if ( mysql_num_rows($files_folders_table) > 0 ) {
    // the table exists - therefore the database is not updated
    VIKO_page(
        "Error!",
        HTML::errorPage(
            "Before running this script, you have to update VIKO database. " .
            "Use the database update script in file <code>sql/changes_2.0.sql</code>."
        )
    );
}


// we ask a confirmation before running this script.
if ( !isset( $_POST['submit'] ) ) {
    // show a form with submit button.
    VIKO_page(
        "Import files to database",
        HTML::h2( "Import files to database" ) .
        '<form action="" method="post">' .
        '<p>Press the button to start importing files from VIKO 1.1 <code>files/</code> folder.</p>' .
        '<p><strong>NB!</strong> Make sure, that you have backed the contents of ' .
        '<code>files/</code> directory, before you continue.</p>' .
        '<p><input type="submit" name="submit" value="Convert" /></p>' .
        '</form>'
    );
}


$files_not_found = load_files_to_database();


// if there were more files in database than in files/ dir, then show error and list of those files
$not_found_error = "";
if ( count($files_not_found) > 0 ) {
    $not_found_error = HTML::error(
        "The script failed to find " . count($files_not_found) . " files mentioned in database. " .
        "Note, that this is a sign of error - your database and files folder are out of sync. " .
        "The listing of these files follows:"
    );
    $not_found_error .= "<pre>";
    foreach ( $files_not_found as $file ) {
        $not_found_error .= "$file\n";
    }
    $not_found_error .= "</pre>";
}



$nr_of_files_to_process = get_nr_of_files_to_process();

if ( $nr_of_files_to_process > 0 ) {
    VIKO_page(
        "Part of the files imported to database",
        HTML::h2("Part of the files imported to database") .
        HTML::p("Because the server has time limit for PHP scripts, " .
            "it's not possible to import all files in one run. ".
            "You have to keep clicking 'Continue' until all files are imported.") .
        HTML::p("There are $nr_of_files_to_process more files to process.") .
        '<form action="" method="post">' .
        '<p><input type="submit" name="submit" value="Continue" /></p>' .
        '</form>' .
        $not_found_error
    );
}
else {
    $files_left_over = look_for_remaining_files();

    // if there were more files in files/ dir than in database, then show error and list of those files
    $left_over_error = "";
    if ( count($files_left_over) > 0 ) {
        $left_over_error = HTML::error(
            "The script found " . count($files_left_over) . " files from <code>files/</code>, " .
            "that were not mentioned in database. " .
            "Note, that this is a sign of error - your database and files folder are out of sync."
        );
        $left_over_error .= "<pre>";
        foreach ( $files_left_over as $file ) {
            $left_over_error .= "$file\n";
        }
        $left_over_error .= "</pre>";
    }

    if ( $not_found_error == "" && $left_over_error == "" ) {
        // no errors were recorded - exit with success message
        VIKO_page(
            "Success! File hierarchy converted",
            HTML::h2("Success! File hierarchy converted") .
            HTML::notice("Structure of <code>files/</code> directory converted to VIKO 2.0.") .
            HTML::p("You should now remove the file 'convert-viko-files.php' from VIKO installation.")
        );
    }
    else {
        // the file
        VIKO_page(
            "Conversion finished with errors",
            HTML::h2("Conversion finished with errors") .
            HTML::p(
                "When converting structure of <code>files/</code> directory to VIKO 2.0, " .
                "the following errors accoured:"
            ) .
            $not_found_error .
            $left_over_error
        );
    }
}



/**
 * Loads files from 'files/file_id/filename.ext' to database
 *
 * Also empties database field file_uri.
 *
 * If the database contained references to some files, that were not found
 * in filesystem, the function returns a list (array) of those missing files.
 * If everything went successfully an empty array is returned.
 *
 * @return array list of errors
 */
function load_files_to_database()
{
    // to load large objects into database, we have to increase
    // the maximum allowed packet size. 100MB should be enough.
    mysql_query("SET max_allowed_packet=" . (100*1024*1024) );

    // get listing of all files from database
    $r = mysql_query(
        "SELECT
            material_id,
            material_uri
        FROM
            Materials
        WHERE
            material_type = 'FILE'
            AND
            material_uri != ''
        "
    );

    $errors = array();

    // we may not exceed the maximum execution time
    // the default is 30 seconds, but we may not be sure.
    // probably it isn't smaller than 30 seconds, so we can extract 5 seconds for extra safety
    $time_limit = ini_get("max_execution_time") - 5;
    $start_time = time();

    while ( $file = mysql_fetch_array($r) ) {

        // get the name of old ID directory by removing everything after the slash "/" (including slash itself)
        $old_file_dir = 'files/' . preg_replace( '#/.*$#', '', $file["material_uri"] );

        if ( file_exists( $old_file_dir ) ) {
            // because the old filename might contain some strange characters which may not
            // correlate with the filename characters in database, open the directory and
            // read the filename from there into variable $filename
            $filename = get_first_file_from_dir( $old_file_dir );

            // load the file into database
            load_file_into_database( $file["material_id"], "$old_file_dir/$filename" );

            // remove the file from 'files'
            unlink( "$old_file_dir/$filename" );

            // remove the old file directory from 'files'
            rmdir( $old_file_dir );
        }
        else {
            $errors[] = "Directory '$old_file_dir' is missing!";
        }

        // determine the MIME type of file
        $mime_type = MimeType::determineFromFilename( $file["material_uri"] );

        // empty field file_uri in database and set the mime type
        mysql_query(
            "UPDATE
                Materials
            SET
                material_uri='',
                mime_type='$mime_type'
            WHERE
                material_id=$file[material_id]
            "
        );

        // check whether we have reached or exceeded time limit
        if (  $time_limit > 0   &&   time() >= $start_time + $time_limit  ) {
            return $errors;
        }
    }

    return $errors;
}


/**
 * Retrieves the name of the first file in directory
 *
 * @param string $dir directory
 * @return string filename
 */
function get_first_file_from_dir( $dir )
{
    $dir_handle = opendir( $dir );
    while ( false !== ($filename = readdir($dir_handle)) ) {
        if ( ! ($filename == '.' || $filename == '..') ) {
            break;
        }
    }
    closedir( $dir_handle );

    return $filename;
}


/**
 * Inserts file contents into table File_Contents
 *
 * To avoid loading the whole file into memory,
 * which might exceed the PHP memory limit and stop the whole
 * process, this routine is optimized to read the file in
 * smaller chunks and append to the database field.s
 *
 * @param int $file_id
 * @param string $filename
 */
function load_file_into_database( $file_id, $filename )
{
    // first create an empty record
    mysql_query(
        "INSERT
        INTO Material_Contents
            (material_id, material_content)
        VALUES
            ( $file_id, '' )"
    );

    // open the file in binary mode
    $file = fopen($filename, "rb");

    // read the file in small chunks and append these to the record
    while (!feof($file)) {
        $file_chunk = fread($file, FILE_CHUNK_SIZE);

        $file_chunk = mysql_real_escape_string($file_chunk);

        mysql_query(
            "UPDATE
                Material_Contents
            SET
                material_content = CONCAT(material_content, '$file_chunk')
            WHERE
                material_id=$file_id"
        );
    }

    fclose($file);
}


/**
 * Checks, if there are any more files left in old 'files' directory
 *
 * If there are, returns array containing names of the files moved.
 *
 * @return array remaining files
 */
function look_for_remaining_files()
{
    $remaining_files = array();

    $dir_handle = opendir( 'files' );

    while ( false !== ($file = readdir($dir_handle)) ) {
        if ( $file == '.' || $file == '..' ) {
            // do nothing - these are system dirs
        }
        else {
            // count the files
            $remaining_files[] = "files/$file";
        }
    }

    closedir( $dir_handle );

    return $remaining_files;
}


/**
 * Returns the number of files in database that need loading to database
 *
 * @return int nr of files
 */
function get_nr_of_files_to_process()
{
    $r = mysql_query("SELECT COUNT(*) FROM Materials WHERE material_type = 'FILE' AND material_uri != '' ");
    $row = mysql_fetch_array( $r );
    return $row[0];
}


/**
 * Create VIKO error page
 *
 * @param string $title    the title of the page
 * @param string $content  the whole contents of the page
 */
function VIKO_page( $title, $content )
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

?>