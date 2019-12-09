<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$input = file_get_contents('input.txt');

$w = 25;
$h = 6;
$layerIndex = 0;

$theLayer = null;
$minZeros = $w * $h;
$numLayers = strlen($input) / ($w * $h);

echo "Layers: $numLayers\n";

for ($layerIndex = 0; $layerIndex < $numLayers; $layerIndex++) {
    $numZeros = numDigitsInLayer($layerIndex, 0);
    if ($numZeros < $minZeros) {
        $theLayer = $layerIndex;
        $minZeros = $numZeros;
    }
};

echo "Chosen layer: $theLayer ($minZeros zeros)\n";
echo substr($input, $theLayer * $w * $h, $w * $h) . "\n";

$numOnes = numDigitsInLayer($theLayer, 1);
$numTwos = numDigitsInLayer($theLayer, 2);

echo "$numOnes x $numTwos = " . ($numOnes * $numTwos) . "\n";

function numDigitsInLayer(int $layer, int $digit)
{
    global $input, $w, $h;
    return substr_count($input, (string)$digit, $layer * $w * $h, $w * $h);
}

// 00000000000000000000000000000000000000000000000000000000
// 1111111111111111111111111111111111111111111111111111
// 222222222222222222222222222222222222222222