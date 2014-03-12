<?php

require_once 'VikoContentFormatter.php';
require_once 'PHPUnit.php';

class VikoContentFormatterTest extends PHPUnit_TestCase
{

    // constructor of the test suite
    function VikoContentFormatterTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    function testFormat() {
        // check that basic formatting is done and smileys are disabled.
        $this->assertRegExp(
            "/<p>&lt;&gt;&amp;'&quot; :D<\/p>/",
            VikoContentFormatter::format("<>&'\" :D")
        );
    }

}

?>
