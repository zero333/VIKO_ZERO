<?php

error_reporting(E_ALL);

$pathinfo=pathinfo(__FILE__);
$path = $pathinfo['dirname'];
$path = preg_replace('{/[^/]+$}', '', $path);
ini_set(
    'include_path',
    ini_get('include_path') . ":$path/lib" . ":$path/PEAR"
);


require_once 'PHPUnit.php';

require_once 'Configuration.php';

Configuration::get("$path/test/viko.conf");

for ($i = 1; $i < count($argv); $i++ ) {
    require_once $argv[$i] . "Test.php";

    $suite  = new PHPUnit_TestSuite( $argv[$i] . "Test" );
    $result = PHPUnit::run($suite);
    echo $result -> toString();
}


?>
