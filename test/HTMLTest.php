<?php

require_once 'HTML.php';
require_once 'PHPUnit.php';

class HTMLTest extends PHPUnit_TestCase
{
    /**
     * Simple standard constructor for the testcase
     */
    function HTMLTest( $name )
    {
        $this->PHPUnit_TestCase( $name );
    }

    // all the methods of HTML class are static,
    // so we don't need any setUp() and tearDown() methods here

    /**
     * Test the general HTML::element method
     */
    function testElement()
    {
        $this->assertEquals(
            "<foo />",
            HTML::element( "foo" )
        );

        $this->assertEquals(
            "<Foo></Foo>",
            HTML::element( "Foo", "" )
        );

        $this->assertEquals(
            "<foo>bar</foo>",
            HTML::element( "foo", "bar" )
        );

        $this->assertEquals(
            "<foo>bar</foo>",
            HTML::element( "foo", "bar", array() )
        );

        $this->assertEquals(
            '<foo attr="value">bar</foo>',
            HTML::element( "foo", "bar", array( "attr" => "value" ) )
        );

        $this->assertEquals(
            '<foo attr1="value1" attr2="value2" />',
            HTML::element( "foo", null, array( "attr1" => "value1", "attr2" => "value2" ) )
        );

        // test element inside element
        $this->assertEquals(
            '<foo><bar><baz /></bar></foo>',
            HTML::element( "foo", HTML::element( "bar", HTML::element( "baz" ) ) )
        );
    }


    /**
     * Test method for creating links
     */
    function testA()
    {
        // simple link
        $this->assertEquals(
            '<a href="http://www.example.com">example</a>',
            HTML::a( "http://www.example.com", "example" )
        );

        // link with title
        $this->assertEquals(
            '<a href="mailto:john@example.com" title="send e-mail to John">John</a>',
            HTML::a( "mailto:john@example.com", "John", "send e-mail to John" )
        );
    }


    /**
     * Test method for creating abbreviations
     */
    function testAbbr()
    {
        // simple abbr
        $this->assertEquals(
            '<acronym>PHP</acronym>',
            HTML::abbr( "PHP" )
        );

        // abbr with title
        $this->assertEquals(
            '<acronym title="PHP Hypertext Preprocessor">PHP</acronym>',
            HTML::abbr( "PHP", "PHP Hypertext Preprocessor" )
        );
    }


    /**
     * Test method for creating strong emphasis
     */
    function testStrong()
    {
        $this->assertEquals(
            '<strong>Hei!</strong>',
            HTML::strong( "Hei!" )
        );
    }


    /**
     * Test method for creating error messages
     */
    function testError()
    {
        $this->assertEquals(
            '<p><strong class="error">Error on line #128</strong></p>',
            HTML::error( "Error on line #128" )
        );
        $this->assertEquals(
            '<strong class="error">Error on line #128</strong>',
            HTML::error( "Error on line #128", false )
        );
    }


    /**
     * Test method for creating notice messages
     */
    function testNotice()
    {
        $this->assertEquals(
            '<p><strong class="notice">Operation completed with success.</strong></p>',
            HTML::notice( "Operation completed with success." )
        );
        $this->assertEquals(
            '<strong class="notice">Operation completed with success.</strong>',
            HTML::notice( "Operation completed with success.", false )
        );
    }


    /**
     * Test method for creating paragraphs
     */
    function testP()
    {
        $this->assertEquals(
            '<p>Hello, world!</p>',
            HTML::p( "Hello, world!" )
        );
    }


    /**
     * Test methods for creating headings
     */
    function testH()
    {
        $this->assertEquals(
            '<h1>Heading 1</h1>',
            HTML::h1( "Heading 1" )
        );
        $this->assertEquals(
            '<h2>Heading 2</h2>',
            HTML::h2( "Heading 2" )
        );
        $this->assertEquals(
            '<h3>Heading 3</h3>',
            HTML::h3( "Heading 3" )
        );
    }


    /**
     * Test method for list items
     */
    function testLi()
    {
        $this->assertEquals(
            '<li class="first-child">Hello, world!</li>',
            HTML::li( "Hello, world!", array("class" => "first-child") )
        );
    }


    /**
     * Test method for creating multiple elements
     */
    function testMultiElement()
    {
        $this->assertEquals(
            '<p>line 1</p><p>line 2</p>',
            HTML::multiElement( "p", array("line 1", "line 2") )
        );
    }


    /**
     * Test method for unordered list
     */
    function testUl()
    {
        $this->assertEquals(
            '<ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul>',
            HTML::ul( "Item 1", "Item 2", "Item 3" )
        );
    }


    /**
     * Test method for ordered list
     */
    function testOl()
    {
        $this->assertEquals(
            '<ol><li>Item 1</li><li>Item 2</li><li>Item 3</li></ol>',
            HTML::ol( "Item 1", "Item 2", "Item 3" )
        );
    }


    /**
     * Test methods for definition list
     */
    function testDl()
    {
        $this->assertEquals(
            '<dl><dt>Hello, world!</dt><dd>A canonical first program.</dd></dl>',
            HTML::dl(
                HTML::dt("Hello, world!"),
                HTML::dd("A canonical first program.")
            )
        );
    }


    /**
     * Test methods for actionList
     */
    function testActionList()
    {
        $this->assertEquals(
            '<ul class="actions"><li class="action-back"><a href="foo">bar</a></li></ul>',
            HTML::backLink( "foo", "bar", STANDALONE )
        );
        $this->assertEquals(
            '<li class="action-back"><a href="foo">bar</a></li>',
            HTML::backLink( "foo", "bar" )
        );

        $this->assertEquals(
            '<ul class="actions"><li class="action-new"><a href="foo">bar</a></li></ul>',
            HTML::newLink( "foo", "bar", STANDALONE )
        );
        $this->assertEquals(
            '<li class="action-new"><a href="foo">bar</a></li>',
            HTML::newLink( "foo", "bar" )
        );

        $this->assertEquals(
            '<ul class="actions"><li class="action-new-folder"><a href="foo">bar</a></li></ul>',
            HTML::newFolderLink( "foo", "bar", STANDALONE )
        );
        $this->assertEquals(
            '<li class="action-new-folder"><a href="foo">bar</a></li>',
            HTML::newFolderLink( "foo", "bar" )
        );

        $this->assertEquals(
            HTML::actionList( HTML::actionLink("foo", "bar", "new") ),
            HTML::newLink( "foo", "bar", STANDALONE )
        );
    }


    /**
     * Test methods for error pages
     */
    function testErrorPages()
    {
        $this->assertEquals(
            '<h2>Error!</h2><p><strong class="error">Foo is bar. Sorry.</strong></p>',
            HTML::errorPage( "Foo is bar. Sorry." )
        );
        $this->assertEquals(
            '<h2>Access denied!</h2><p><strong class="error">No more beer for you.</strong></p>',
            HTML::accessDeniedPage( "No more beer for you." )
        );
    }


}

?>
