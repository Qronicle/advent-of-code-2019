<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$start = 273025;
$end = 767253;

function normalize(int $number): int
{
    $digits = str_split((string)$number);
    $highest = $digits[0];
    for ($i = 1; $i < 6; $i++) {
        if ($digits[$i] < $highest) {
            for ($j = $i; $j < 6; $j++) {
                $digits[$j] = $highest;
            }
            break;
        }
        $highest = $digits[$i];
    }
    return implode('', $digits);
}

function nextNumber(int $number): int
{
    return normalize($number + 1);
}

function isValid(int $number): bool
{
    $digits = str_split((string)$number);
    for ($i = 0; $i < 5; $i++) {
        if ($digits[$i] == $digits[$i+1]) {
            return true;
        }
    }
    return false;
}

$number = normalize($start) - 1;
$nrValid = 0;
while (true) {
    $number = nextNumber($number);
    if ($number > $end) {
        break;
    }
    $nrValid += isValid($number) ? 1 : 0;
    //echo $number . (isValid($number) ? ' Y' : ' N') . "\n";
}
echo $nrValid . "\n";