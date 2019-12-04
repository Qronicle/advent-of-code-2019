<?php

function getFuelForMass(int $mass)
{
    return max(0, floor($mass / 3) - 2);
}

$inputFile = fopen('input.txt', 'r');

$totalFuel = 0;

while ($mass = fgets($inputFile)) {
    $mass = trim($mass);
    if (!is_numeric($mass)) {
        continue;
    }
    $totalFuel += getFuelForMass($mass);
}

echo $totalFuel;