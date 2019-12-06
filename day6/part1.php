<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$input = file_get_contents('input.txt');
$orbitData = explode("\n", $input);

$orbits = [];

foreach ($orbitData as $orbit) {
    list($center, $object) = explode(')', $orbit);
    $orbits[$object] = $center;
}

$totalOrbits = 0;
foreach ($orbits as $object => $center) {
    $totalOrbits += getNumOrbits($object);
}
echo $totalOrbits . "\n";

function getNumOrbits($object)
{
    global $orbits;
    if (isset($orbits[$object])) {
        return 1 + getNumOrbits($orbits[$object]);
    }
    return 0;
}