<?php

use IntCode\InputNecessaryException;
use IntCode\IntCode;

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('../common/IntCode.php');

$code = file_get_contents('input.txt');
//$code = '3,52,1001,52,-5,52,3,53,1,52,56,54,1007,54,5,55,1005,55,26,1001,54,-5,54,1105,1,12,1,53,54,53,1008,54,0,55,1001,55,1,55,2,53,55,53,4,53,1001,56,-1,56,1005,56,6,99,0,0,0,0,10';
//$code = '3,26,1001,26,-4,26,3,27,1002,27,2,27,1,27,26,27,4,27,1001,28,-1,28,1005,28,6,99,0,0,5';

$combinations = getCombinations([5,6,7,8,9]);
//$combinations = ['98765'];
$maxOutput = 0;

foreach ($combinations as $phases) {
    $phases = str_split($phases);
    $output = 0;
    $phaseIntCodes = [];
    $finalOutput = 0;
    // Initialize intcode apps
    foreach ($phases as $phase) {
        $intCode = new IntCode($code);
        $intCode->setHaltOnOutput();
        $phaseIntCodes[$phase] = $intCode;
    }
    $loop = 0;
    do {
        echo "LOOP " . ++$loop . "\n";
        foreach ($phases as $p => $phase) {
            $intCode = $phaseIntCodes[$phase];
            $input = $intCode->isRunning() ? [$output] : [$phase, $output];
            $intCode->setInput($input);
            echo "Engine $p - phase $phase\n> Input: " . implode(', ', $input) . "\n";
            $intCode->run();
            if (!is_null($intCode->getOutput())) {
                $output = $intCode->getOutput();
            }
            if (!$intCode->isRunning()) {
                echo "> Process stopped\n";
                break 2;
            }
        }
        echo "> Output: $output\n";
        $finalOutput = $output;
    } while (true);
    $maxOutput = max($maxOutput, $finalOutput);
}
echo 'Max: ' . $maxOutput . "\n";

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