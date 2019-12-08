<?php

use IntCode\IntCode;

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('../common/IntCode.php');

$code = file_get_contents('input.txt');

$combinations = getCombinations([0,1,2,3,4]);
$maxOutput = 0;

foreach ($combinations as $phases) {
    $phases = str_split($phases);
    $output = 0;
    foreach ($phases as $phase) {
        $input = [$phase, $output];
        $intCode = new IntCode($code, $input);
        $intCode->run();
        $output = $intCode->getOutput();
    }
    echo $output . "\n";
    $maxOutput = max($maxOutput, $output);
}

echo 'Maxi: ' . $maxOutput . "\n";

function getCombinations(array $input)
{
    if (count($input) == 1) {
        return reset($input);
    }
    $combinations = [];
    foreach ($input as $i => $int) {
        $nextInput = $input;
        unset($nextInput[$i]);
        $result = getCombinations($nextInput);
        if (is_scalar($result)) {
            $combinations[] = $int . $result;
        } else {
            foreach ($result as $r) {
                $combinations[] = $int . $r;
            }
        }
    }
    return $combinations;
}