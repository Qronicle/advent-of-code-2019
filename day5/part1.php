<?php

use IntCode\IntCode;

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('../common/IntCode.php');

$code = file_get_contents('input.txt');
$input = 1;

$intCode = new IntCode($code, $input);
$intCode->run();