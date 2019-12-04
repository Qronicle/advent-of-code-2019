<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require('Math1.php');

$input = require('input.php');

$lines = [];

foreach ($input as $wireIndex => $inputLine) {
    $path = explode(',', $inputLine);
    $x = 0;
    $y = 0;
    // Define starting point with offset one to prevent crossing at start
    $direction = substr($path[0], 0, 1);
    $prevPoint = new Point(
        $direction == 'L' ? -1 : ($direction == 'R' ? 1 : 0),
        $direction == 'U' ? 1 : ($direction == 'D' ? -1 : 0)
    );
    foreach ($path as $translation) {
        $direction = substr($translation, 0, 1);
        $distance = (int)substr($translation, 1);
        switch ($direction) {
            case 'U':
                $y += $distance;
                break;
            case 'D':
                $y -= $distance;
                break;
            case 'L':
                $x -= $distance;
                break;
            case 'R':
                $x += $distance;
                break;
        }
        $point = new Point($x, $y);
        $lines[$wireIndex][] = new Line($prevPoint, $point);
        $prevPoint = $point;
    }
}

$minDist = null;

foreach ($lines[0] as $line1) {
    foreach ($lines[1] as $line2) {
        if ($intersection = $line1->getIntersection($line2)) {
            $distance = abs($intersection->x) + abs($intersection->y);
            $minDist = is_null($minDist) ? $distance : min($minDist, $distance);
        }
    }
}

echo $minDist . "\n";
