<?php

require_once('../common/common.php');
require_once('../common/IntCode.php');
require_once('ASCII.php');

use IntCode\IntCode;

$code = file_get_contents('input.txt');

$ascii = new ASCII(new IntCode($code));

// Part 1: play until you get to the pressure plate with all items and save the state
// $ascii->run();

// Part 2: start from room before pressure plate
$ascii->loadState('part1-before-pressure.txt');
$items = ['mutex', 'whirled peas', 'space law space brochure', 'loom', 'hologram', 'manifold', 'cake', 'easter egg'];
$ascii->resolvePressurePlate('south', $items);


timer_end();

// Test for trying all combinations

$arr = [0, 1, 2, 3, 4];
function all(array $items, array $currentItems = []) {
    foreach ($items as $i => $item) {
        $currentItems[] = $item;
        echo implode(',', $currentItems) . "\n";
        unset($items[$i]);
        all($items, $currentItems);
        array_pop($currentItems);
    }
}

//all($arr);