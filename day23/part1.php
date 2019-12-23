<?php

require_once('../common/common.php');
require_once('../common/IntCode.php');
require_once('Network.php');

use IntCode\IntCode;

$code = file_get_contents('input.txt');

$network = new Network($code, 50);
$result = $network->runUntilOutputOnAddress(255);

timer_end();