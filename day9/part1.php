<?php

use IntCode\IntCode;

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('../common/IntCode.php');

$code = file_get_contents('input.txt');
//$code = '109,1,204,-1,1001,100,1,100,1008,100,16,101,1006,101,0,99';
//$code = '1102,34915192,34915192,7,4,7,99,0';
//$code = '104,1125899906842624,99';

$intCode = new IntCode($code, 1);
$intCode->run();

print_r($intCode->getOutput(false));
echo $intCode->getOutput() . "\n";