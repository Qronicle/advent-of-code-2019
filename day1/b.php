<?php

function getFuelForMass(int $mass)
{
    return max(0, floor($mass / 3) - 2);
}

function getFuelForFuelMass(int $mass)
{
    $extraFuel = 0;
    do {
        $mass = getFuelForMass($mass);
        $extraFuel += $mass;
    } while  ($mass > 0);
    return $extraFuel;
}

$inputFile = fopen('input.txt', 'r');

$fuelMass = 0;

while ($mass = fgets($inputFile)) {
    $mass = trim($mass);
    if (!is_numeric($mass)) {
        continue;
    }
    $moduleMass = getFuelForMass($mass);
    $fuelMass += $moduleMass + getFuelForFuelMass($moduleMass);
}

echo $fuelMass;
echo "\n";