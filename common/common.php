<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
timer_start();

// Common functions ////////////////////////////////////////////////////////////////////////////////////////////////////

function greatest_common_divisor($n, $m)
{
    $n = abs($n);
    $m = abs($m);
    if ($n == 0) return $m;
    if ($m == 0) return $n;
    return $n > $m
        ? greatest_common_divisor($m, $n % $m)
        : greatest_common_divisor($n, $m % $n);
}

function timer_start()
{
    global $__start;
    $__start = microtime(true);
}

function timer_lap(string $string = null)
{
    timer_end($string);
    timer_start();
}

function timer_end(string $string = null)
{
    global $__start;
    $end = microtime(true);
    $string = $string ?: "\nResult reached in %s seconds\n";
    echo sprintf($string, round($end - $__start, 3));
}