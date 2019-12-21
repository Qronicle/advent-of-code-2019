<?php

require_once('../common/common.php');
require_once('../common/IntCode.php');
require_once('ASCII.php');

use IntCode\IntCode;

$code = file_get_contents('input.txt');

$ascii = new ASCII(new IntCode($code));
$ascii->reportDamage();

timer_end();