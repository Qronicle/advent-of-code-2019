<?php

require('../common/common.php');
require('FFT.php');

$input = file_get_contents('input.txt');
$fft = new FFT();
$result = $fft->phase($input, 100);
echo substr($result, 0, 8);

timer_end();
