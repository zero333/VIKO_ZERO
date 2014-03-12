<?php

require_once 'MaterialFactory.php';
require_once 'File.php';
require_once 'PHPUnit.php';

class FileTest extends PHPUnit_TestCase
{
    var $file;

    // constructor of the test suite
    function FormTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->file = new File(0);
    }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset( $this->file );
    }

    function testGetDownloadName() {
        // simple case
        $this->file->setName("MyFile.pdf");
        $this->assertEquals("MyFile.pdf", $this->file->getDownloadName());

        // file has no extension,
        // but the mimetype doesn't give a lot of information too
        $this->file->setName("aFile");
        $this->file->setMimeType("application/octet-stream");
        $this->assertEquals("aFile", $this->file->getDownloadName());

        // file has no extension,
        // but the mimetype refers to PDF
        $this->file->setName("SomeFile");
        $this->file->setMimeType("application/pdf");
        $this->assertEquals("SomeFile.pdf", $this->file->getDownloadName());

        // file has no extension,
        // but the mimetype refers to RTF
        $this->file->setName("SomeFile");
        $this->file->setMimeType("text/richtext");
        $this->assertEquals("SomeFile.rtf", $this->file->getDownloadName());
    }

    function testGetDownloadNameWithSpaces() {
        // the spaces should be replaced with underscores,
        // because some browsers like Mozilla don't handle spaces well
        $this->file->setName("My File.pdf");
        $this->assertEquals("My_File.pdf", $this->file->getDownloadName());

        // multiple spaces
        $this->file->setName("a File it really   is.html");
        $this->assertEquals("a_File_it_really___is.html", $this->file->getDownloadName());

        // tabs and no extension
        $this->file->setName("Some\tFile");
        $this->file->setMimeType("application/pdf");
        $this->assertEquals("Some_File.pdf", $this->file->getDownloadName());
    }
}

?>
