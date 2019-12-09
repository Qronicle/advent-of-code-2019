<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$input = file_get_contents('input.txt');

$w = 25;
$h = 6;
$l = $w * $h;
$numLayers = strlen($input) / ($w * $h);

echo "Layers: $numLayers\n";

$output = '';

for ($i = 0; $i < $l; $i++) {
    for ($layer = 0; $layer < $numLayers; $layer++) {
        $color = substr($input, $layer * $w * $h + $i, 1);
        if ($color != 2) {
            $output .= $color;
            break;
        } elseif ($layer + 1 == $numLayers) {
            $output .= $color;
        }
    }
}

echo strlen($output) . " = $l\n";
echo implode("\n", str_split(str_replace('0', ' ', $output), $w)) . "\n";