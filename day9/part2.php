<?php

use IntCode\IntCode;

ini_set('display_errors', 1);
error_reporting(E_ALL);
$start = microtime(true);

require_once('../common/IntCode.php');

$code = file_get_contents('input.txt');

$intCode = new IntCode($code, 2);
$intCode->run();

print_r($intCode->getOutput(false));
echo $intCode->getOutput();

$end = microtime(true);
echo "\nResult reached in " . round($end-$start, 2) . " seconds\n";