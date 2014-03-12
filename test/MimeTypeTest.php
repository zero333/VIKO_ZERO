<?php

require_once 'MimeType.php';
require_once 'PHPUnit.php';

class MimeTypeTest extends PHPUnit_TestCase
{
    // constructor of the test suite
    function MimeTypeTest($name) {
       $this->PHPUnit_TestCase($name);
    }


    function testDetermineFromFilename() {
        $this->assertEquals(
            "application/pdf",
            MimeType::determineFromFilename("application.pdf")
        );

        $this->assertEquals(
            "text/html",
            MimeType::determineFromFilename("index.html")
        );

        $this->assertEquals(
            "application/x-gzip",
            MimeType::determineFromFilename("viko.tar.gz")
        );

        $this->assertEquals(
            "application/msword",
            MimeType::determineFromFilename("writing.dot")
        );

        $this->assertEquals(
            "application/vnd.ms-excel",
            MimeType::determineFromFilename("spreadsheet.xls")
        );

        $this->assertEquals(
            "text/plain",
            MimeType::determineFromFilename("quine/MimeTypeTest.php")
        );

        $this->assertEquals(
            "image/jpeg",
            MimeType::determineFromFilename("/home/nene/viko/flower.jpe")
        );

        $this->assertEquals(
            "application/octet-stream",
            MimeType::determineFromFilename("http://viko.example.com/flower")
        );

        $this->assertEquals(
            "application/octet-stream",
            MimeType::determineFromFilename("foo.bar")
        );

        $this->assertEquals(
            "application/octet-stream",
            MimeType::determineFromFilename("filename.ext")
        );

        $this->assertEquals(
            "application/vnd.sun.xml.writer",
            MimeType::determineFromFilename("doc.sxw")
        );

        $this->assertEquals(
            "application/vnd.ms-access",
            MimeType::determineFromFilename("database.mdb")
        );
}


    function testCssClass() {
        // test that given a MIME type an appropriate CSS class name is returned

        // test common image formats
        $this->assertEquals( "image-file", MimeType::cssClass( "image/png" ) );
        $this->assertEquals( "image-file", MimeType::cssClass( "image/gif" ) );
        $this->assertEquals( "image-file", MimeType::cssClass( "image/jpeg" ) );
        $this->assertEquals( "image-file", MimeType::cssClass( "image/tiff" ) );
        $this->assertEquals( "image-file", MimeType::cssClass( "image/bmp" ) );
        $this->assertEquals( "image-file", MimeType::cssClass( "image/svg+xml" ) );
        $this->assertEquals( "image-file", MimeType::cssClass( "image/jp2" ) );
        $this->assertEquals( "image-file", MimeType::cssClass( "image/vnd.microsoft.icon" ) );

        // test common audio formats
        $this->assertEquals( "audio-file", MimeType::cssClass( "audio/mpeg" ) );
        $this->assertEquals( "audio-file", MimeType::cssClass( "audio/x-wav" ) );
        $this->assertEquals( "audio-file", MimeType::cssClass( "audio/x-aiff" ) );
        $this->assertEquals( "audio-file", MimeType::cssClass( "audio/x-ms-wma" ) );

        // test common video formats
        $this->assertEquals( "video-file", MimeType::cssClass( "video/x-ms-wmv" ) );
        $this->assertEquals( "video-file", MimeType::cssClass( "video/mpeg" ) );
        $this->assertEquals( "video-file", MimeType::cssClass( "video/quicktime" ) );
        $this->assertEquals( "video-file", MimeType::cssClass( "video/x-msvideo" ) );

        // test common plain text formats
        $this->assertEquals( "text-file", MimeType::cssClass( "text/plain" ) );
        $this->assertEquals( "text-file", MimeType::cssClass( "text/xml" ) );
        $this->assertEquals( "text-file", MimeType::cssClass( "text/css" ) );

        // test PDF format
        $this->assertEquals( "pdf-document-file", MimeType::cssClass( "application/pdf" ) );

        // test HTML format
        $this->assertEquals( "webpage-file", MimeType::cssClass( "text/html" ) );

        // test other text document formats
        $this->assertEquals( "document-file", MimeType::cssClass( "application/msword" ) );
        $this->assertEquals( "document-file", MimeType::cssClass( "application/vnd.oasis.opendocument.text" ) );
        $this->assertEquals( "document-file", MimeType::cssClass( "text/richtext" ) );
        $this->assertEquals( "document-file", MimeType::cssClass( "application/vnd.ms-htmlhelp" ) );
        $this->assertEquals( "document-file", MimeType::cssClass( "application/postscript" ) );

        // test spreadsheet formats
        $this->assertEquals( "spreadsheet-file", MimeType::cssClass( "application/vnd.ms-excel" ) );
        $this->assertEquals( "spreadsheet-file", MimeType::cssClass( "application/vnd.oasis.opendocument.spreadsheet" ) );
        $this->assertEquals( "spreadsheet-file", MimeType::cssClass( "text/csv" ) );

        // test presentation formats
        $this->assertEquals( "presentation-file", MimeType::cssClass( "application/vnd.ms-powerpoint" ) );
        $this->assertEquals( "presentation-file", MimeType::cssClass( "application/vnd.oasis.opendocument.presentation" ) );

        // test archive formats
        $this->assertEquals( "archive-file", MimeType::cssClass( "application/zip" ) );
        $this->assertEquals( "archive-file", MimeType::cssClass( "application/x-gzip" ) );
        $this->assertEquals( "archive-file", MimeType::cssClass( "application/x-bzip" ) );
        $this->assertEquals( "archive-file", MimeType::cssClass( "application/x-rar-compressed" ) );
        $this->assertEquals( "archive-file", MimeType::cssClass( "application/x-tar" ) );

        // test unknown formats (the first is unknown by definition)
        $this->assertEquals( "unknown-file", MimeType::cssClass( "application/octet-stream" ) );
        $this->assertEquals( "unknown-file", MimeType::cssClass( "application/vnd.oasis.opendocument.formula" ) );
        $this->assertEquals( "unknown-file", MimeType::cssClass( "application/vnd.ms-works" ) );
        $this->assertEquals( "unknown-file", MimeType::cssClass( "application/xhtml+xml" ) );

        // I think we can assume, that most of the time OGG refers to audio,
        // as the popularity of Theora video codec is quite low.
        $this->assertEquals( "audio-file", MimeType::cssClass( "application/ogg" ) );
    }

    function testHumanReadableName() {
        // test common image formats
        $this->assertEquals( "Image file", MimeType::humanReadableName( "image/png" ) );
        $this->assertEquals( "Image file", MimeType::humanReadableName( "image/gif" ) );
        $this->assertEquals( "Image file", MimeType::humanReadableName( "image/jpeg" ) );
        $this->assertEquals( "Image file", MimeType::humanReadableName( "image/tiff" ) );
        $this->assertEquals( "Image file", MimeType::humanReadableName( "image/bmp" ) );
        $this->assertEquals( "Image file", MimeType::humanReadableName( "image/svg+xml" ) );
        $this->assertEquals( "Image file", MimeType::humanReadableName( "image/jp2" ) );
        $this->assertEquals( "Image file", MimeType::humanReadableName( "image/vnd.microsoft.icon" ) );

        // test common audio formats
        $this->assertEquals( "Audio file", MimeType::humanReadableName( "audio/mpeg" ) );
        $this->assertEquals( "Audio file", MimeType::humanReadableName( "audio/x-wav" ) );
        $this->assertEquals( "Audio file", MimeType::humanReadableName( "audio/x-aiff" ) );
        $this->assertEquals( "Audio file", MimeType::humanReadableName( "audio/x-ms-wma" ) );

        // test common video formats
        $this->assertEquals( "Video file", MimeType::humanReadableName( "video/x-ms-wmv" ) );
        $this->assertEquals( "Video file", MimeType::humanReadableName( "video/mpeg" ) );
        $this->assertEquals( "Video file", MimeType::humanReadableName( "video/quicktime" ) );
        $this->assertEquals( "Video file", MimeType::humanReadableName( "video/x-msvideo" ) );

        // test common plain text formats
        $this->assertEquals( "Text file", MimeType::humanReadableName( "text/plain" ) );
        $this->assertEquals( "Text file", MimeType::humanReadableName( "text/xml" ) );
        $this->assertEquals( "Text file", MimeType::humanReadableName( "text/css" ) );

        // test PDF format
        $this->assertEquals( "PDF document", MimeType::humanReadableName( "application/pdf" ) );

        // test HTML format
        $this->assertEquals( "Web page", MimeType::humanReadableName( "text/html" ) );

        // test other text document formats
        $this->assertEquals( "Text document", MimeType::humanReadableName( "application/msword" ) );
        $this->assertEquals( "Text document", MimeType::humanReadableName( "application/vnd.oasis.opendocument.text" ) );
        $this->assertEquals( "Text document", MimeType::humanReadableName( "text/richtext" ) );
        $this->assertEquals( "Text document", MimeType::humanReadableName( "application/vnd.ms-htmlhelp" ) );
        $this->assertEquals( "Text document", MimeType::humanReadableName( "application/postscript" ) );

        // test spreadsheet formats
        $this->assertEquals( "Spreadsheet", MimeType::humanReadableName( "application/vnd.ms-excel" ) );
        $this->assertEquals( "Spreadsheet", MimeType::humanReadableName( "application/vnd.oasis.opendocument.spreadsheet" ) );
        $this->assertEquals( "Spreadsheet", MimeType::humanReadableName( "text/csv" ) );

        // test presentation formats
        $this->assertEquals( "Presentation", MimeType::humanReadableName( "application/vnd.ms-powerpoint" ) );
        $this->assertEquals( "Presentation", MimeType::humanReadableName( "application/vnd.oasis.opendocument.presentation" ) );

        // test archive formats
        $this->assertEquals( "Archive", MimeType::humanReadableName( "application/zip" ) );
        $this->assertEquals( "Archive", MimeType::humanReadableName( "application/x-gzip" ) );
        $this->assertEquals( "Archive", MimeType::humanReadableName( "application/x-bzip" ) );
        $this->assertEquals( "Archive", MimeType::humanReadableName( "application/x-rar-compressed" ) );
        $this->assertEquals( "Archive", MimeType::humanReadableName( "application/x-tar" ) );

        // test unknown formats (the first is unknown by definition)
        $this->assertEquals( "", MimeType::humanReadableName( "application/octet-stream" ) );
        $this->assertEquals( "", MimeType::humanReadableName( "application/vnd.oasis.opendocument.formula" ) );
        $this->assertEquals( "", MimeType::humanReadableName( "application/vnd.ms-works" ) );
        $this->assertEquals( "", MimeType::humanReadableName( "application/xhtml+xml" ) );

        // I think we can assume, that most of the time OGG refers to audio,
        // as the popularity of Theora video codec is quite low.
        $this->assertEquals( "Audio file", MimeType::humanReadableName( "application/ogg" ) );
    }

    function testGetExtension() {
        // test common image formats
        $this->assertEquals( "png", MimeType::getExtension( "image/png" ) );
        $this->assertEquals( "gif", MimeType::getExtension( "image/gif" ) );
        $this->assertEquals( "jpg", MimeType::getExtension( "image/jpeg" ) );
        $this->assertEquals( "tif", MimeType::getExtension( "image/tiff" ) );
        $this->assertEquals( "bmp", MimeType::getExtension( "image/bmp" ) );
        $this->assertEquals( "svg", MimeType::getExtension( "image/svg+xml" ) );
        $this->assertEquals( "jp2", MimeType::getExtension( "image/jp2" ) );
        $this->assertEquals( "ico", MimeType::getExtension( "image/vnd.microsoft.icon" ) );

        // test common audio formats
        $this->assertEquals( "mp3", MimeType::getExtension( "audio/mpeg" ) );
        $this->assertEquals( "wav", MimeType::getExtension( "audio/x-wav" ) );
        $this->assertEquals( "aif", MimeType::getExtension( "audio/x-aiff" ) );
        $this->assertEquals( "wma", MimeType::getExtension( "audio/x-ms-wma" ) );

        // test common video formats
        $this->assertEquals( "wmv", MimeType::getExtension( "video/x-ms-wmv" ) );
        $this->assertEquals( "mpg", MimeType::getExtension( "video/mpeg" ) );
        $this->assertEquals( "mov", MimeType::getExtension( "video/quicktime" ) );
        $this->assertEquals( "avi", MimeType::getExtension( "video/x-msvideo" ) );

        // test common plain text formats
        $this->assertEquals( "txt", MimeType::getExtension( "text/plain" ) );
        $this->assertEquals( "xml", MimeType::getExtension( "text/xml" ) );
        $this->assertEquals( "css", MimeType::getExtension( "text/css" ) );

        // test PDF format
        $this->assertEquals( "pdf", MimeType::getExtension( "application/pdf" ) );

        // test HTML format
        $this->assertEquals( "html", MimeType::getExtension( "text/html" ) );

        // test other text document formats
        $this->assertEquals( "doc", MimeType::getExtension( "application/msword" ) );
        $this->assertEquals( "odt", MimeType::getExtension( "application/vnd.oasis.opendocument.text" ) );
        $this->assertEquals( "rtf", MimeType::getExtension( "text/richtext" ) );
        $this->assertEquals( "chm", MimeType::getExtension( "application/vnd.ms-htmlhelp" ) );
        $this->assertEquals( "ps", MimeType::getExtension( "application/postscript" ) );

        // test spreadsheet formats
        $this->assertEquals( "xls", MimeType::getExtension( "application/vnd.ms-excel" ) );
        $this->assertEquals( "ods", MimeType::getExtension( "application/vnd.oasis.opendocument.spreadsheet" ) );
        $this->assertEquals( "csv", MimeType::getExtension( "text/csv" ) );

        // test presentation formats
        $this->assertEquals( "ppt", MimeType::getExtension( "application/vnd.ms-powerpoint" ) );
        $this->assertEquals( "odp", MimeType::getExtension( "application/vnd.oasis.opendocument.presentation" ) );

        // test archive formats
        $this->assertEquals( "zip", MimeType::getExtension( "application/zip" ) );
        $this->assertEquals( "gz", MimeType::getExtension( "application/x-gzip" ) );
        $this->assertEquals( "bz", MimeType::getExtension( "application/x-bzip" ) );
        $this->assertEquals( "rar", MimeType::getExtension( "application/x-rar-compressed" ) );
        $this->assertEquals( "tar", MimeType::getExtension( "application/x-tar" ) );

        // some OpenDocument stuff
        $this->assertEquals( "odf", MimeType::getExtension( "application/vnd.oasis.opendocument.formula" ) );
        $this->assertEquals( "odg", MimeType::getExtension( "application/vnd.oasis.opendocument.graphics" ) );
        $this->assertEquals( "odi", MimeType::getExtension( "application/vnd.oasis.opendocument.image" ) );
        $this->assertEquals( "odc", MimeType::getExtension( "application/vnd.oasis.opendocument.chart" ) );

        // test unknown formats (the first is unknown by definition)
        $this->assertEquals( "", MimeType::getExtension( "application/octet-stream" ) );
        $this->assertEquals( "", MimeType::getExtension( "application/xhtml+xml" ) );

        // I think we can assume, that most of the time OGG refers to audio,
        // as the popularity of Theora video codec is quite low.
        $this->assertEquals( "ogg", MimeType::getExtension( "application/ogg" ) );
    }

}

?>
