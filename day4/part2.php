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
    $amount = 1;
    $prevDigit = $digits[0];
    for ($i = 1; $i < 6; $i++) {
        if ($prevDigit == $digits[$i]) {
            $amount++;
        } else {
            if ($amount == 2) {
                return true;
            }
            $prevDigit= $digits[$i];
            $amount = 1;
        }
    }
    return $amount == 2;
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