<?php

require('part1.php');

timer_start();

// Sort visible asteroids by angle and distance

$center = $asteroids[$bestAsteroid];
$relAsteroids = [];
foreach ($asteroids as $a => $asteroid) {
    if ($bestAsteroid == $a) {
        continue;
    }
    $rel = [
        $asteroid[0] - $center[0],
        $asteroid[1] - $center[1],
    ];
    $gcd = greatest_common_divisor($rel[0], $rel[1]);
    $angle = (string)atan2($rel[1] / $gcd, $rel[0] / $gcd);
    $distance = abs($rel[0]) + abs($rel[1]);
    $relAsteroids[$angle][$distance] = $asteroid;
}
ksort($relAsteroids);
foreach ($relAsteroids as $i => $angledAsteroids) {
    ksort($angledAsteroids);
    $relAsteroids[$i] = $angledAsteroids;
}

// Start shooting

$shooting = false;
$numShot = 0;
$startAngle = (string)atan2(-1, 0);
while (true) {
    foreach ($relAsteroids as $angle => $angledAsteroids) {
        if (!$shooting) {
            if ($angle < $startAngle) {
                continue;
            } else {
                $shooting = true;
            }
        }
        $numShot++;
        $asteroid = array_shift($angledAsteroids);
        if ($numShot == 200) {
            echo "We shot number 200 at $asteroid[0], $asteroid[1] (" . ($asteroid[0] * 100 + $asteroid[1]) . ")";
            break 2;
        }
        if (!$angledAsteroids == 0) {
            unset($relAsteroids[$angle]);
        }
    }
}

timer_end();