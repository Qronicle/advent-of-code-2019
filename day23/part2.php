<?php

require_once('../common/common.php');
require_once('../common/IntCode.php');
require_once('Network.php');

$code = file_get_contents('input.txt');

$network = new Network($code, 50);
echo $network->runWithNat();

timer_end();