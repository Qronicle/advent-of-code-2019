<?php

require('../common/common.php');
require('FFT.php');

// Input
$input = file_get_contents('input.txt');
$input = str_repeat($input, 10000);

// Flawed Frequency Transmission
$fft = new FFT();
$offset = (int)substr($input, 0, 7);
$result = $fft->phase($input, 100, $offset);
echo substr($result, 0, 8);

timer_end();