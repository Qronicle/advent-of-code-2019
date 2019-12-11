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
        foreach ($line as $x => $color) {
            if (isset($colors[$color])) {
                $color = $colors[$color];
            } else {
                $color = imagecolorallocate($img, round($color * 25), round($color * 25), round($color * 25));
            }
            imagefilledrectangle($img, $x * $pixelSize, $y * $pixelSize, $x * $pixelSize + $pixelSize, $y * $pixelSize + $pixelSize, $color);
        }
    }
    imagepng($img, $filename);
}