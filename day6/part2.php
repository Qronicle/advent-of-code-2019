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

$youOrbits = getParentOrbits('YOU');
$sanOrbits = getParentOrbits('SAN');

print_r([$youOrbits, $sanOrbits]);

$sameLength = getIdenticalLeftLength($youOrbits, $sanOrbits);
$youOrbits = substr($youOrbits, $sameLength);
$sanOrbits = substr($sanOrbits, $sameLength);

print_r([$youOrbits, $sanOrbits]);

echo count(explode('.', $youOrbits)) + count(explode('.', $sanOrbits));
echo "\n";

// Functions ///////////////////////////////////////////////////////////////////////////////////////////////////////////

function getNumOrbits($object)
{
    global $orbits;
    if (isset($orbits[$object])) {
        return 1 + getNumOrbits($orbits[$object]);
    }
    return 0;
}

function getParentOrbits($object)
{
    global $orbits;
    if (isset($orbits[$object])) {
        return getParentOrbits($orbits[$object]) .  '.' . $orbits[$object];
    }
    return '';
}

function getIdenticalLeftLength(string $a, string $b)
{
    for ($i = 0; $i < strlen($a); $i++) {
        if ($a[$i] != $b[$i]) {
            return $i;
        }
    }
    return $i;
}