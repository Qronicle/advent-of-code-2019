<?php

function strtoimg(string $string, string $filename, int $pixelSize = 5, array $colorMap = [])
{
    $string = trim($string);
    $lines = explode("\n", $string);
    if (!$lines) {
        throw new Exception('strtoimg: no input given');
    }
    $img = imagecreate(strlen($lines[0]) * $pixelSize, count($lines) * $pixelSize);
    $colors = [];
    foreach ($lines as $y => $line) {
        $line = str_split($line);
        foreach ($line as $x => $colorIndex) {
            if (isset($colors[$colorIndex])) {
                $color = $colors[$colorIndex];
            } else {
                if (isset($colorMap[$colorIndex])) {
                    $color = imagecolorallocate($img, $colorMap[$colorIndex][0], $colorMap[$colorIndex][1], $colorMap[$colorIndex][2]);
                } else {
                    $color = imagecolorallocate($img, round($colorIndex * 25), round($colorIndex * 25), round($colorIndex * 25));
                }
                $colors[$colorIndex] = $color;
            }
            imagefilledrectangle($img, $x * $pixelSize, $y * $pixelSize, $x * $pixelSize + $pixelSize, $y * $pixelSize + $pixelSize, $color);
        }
    }
    imagepng($img, $filename);
}