<?php

require('../common/common.php');

// Parse asteroid field
$input = file_get_contents('input.txt');
$lines = explode("\n", $input);
$asteroids = [];
foreach ($lines as $y => $line) {
    $chars = str_split($line);
    foreach ($chars as $x => $char) {
        if ($char == '#') {
            $asteroids[] = [$x, $y];
        }
    }
}

// Calculate the best asteroid

$bestAsteroid = null;
$mostVisible = 0;

foreach ($asteroids as $a => $center) {
    $relAsteroids = [];
    foreach ($asteroids as $b => $asteroid) {
        if ($a == $b) {
            continue;
        }
        $rel = [
            $asteroid[0] - $center[0],
            $asteroid[1] - $center[1],
        ];
        $gcd = greatest_common_divisor($rel[0], $rel[1]);
        $angle = ($rel[0] / $gcd) . ',' . ($rel[1] / $gcd);
        $relAsteroids[$angle] = ($relAsteroids[$angle] ?? 0) + 1;
    }
    if (count($relAsteroids) > $mostVisible) {
        $bestAsteroid = $a;
        $mostVisible = count($relAsteroids);
    }
}

echo "Best asteroid is located at ({$asteroids[$bestAsteroid][0]}, {$asteroids[$bestAsteroid][1]}) and sees $mostVisible other asteroids";

timer_end();