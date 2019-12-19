<?php

require_once('../common/common.php');
require_once('../common/image.php');
require_once('../common/IntCode.php');

use IntCode\IntCode;

$code = file_get_contents('input.txt');

$tractorBeam = new TractorBeam(new IntCode($code));
echo $tractorBeam->run();

timer_end();

class TractorBeam
{
    /** @var IntCode */
    protected $intCode;

    public function __construct(IntCode $intCode)
    {
        $this->intCode = $intCode;
    }

    public function run(): int
    {
        $size = 100;

        // Calculate the lower and upper Y values to fit in the equation y = [lowerY/upperY] * x;
        $lowerY = 0;
        $upperY = 1;
        for ($x = 10; $x < 1000000; $x *= 10) {
            extract($this->getLimits($x, $lowerY, $upperY, true));
        }

        // Calculate most left x
        for ($x = 10; $x < PHP_INT_MAX; $x++) {
            $diff = floor(($upperY * $x)) - ceil(($lowerY * $x));
            if ($diff >= $size) {
                $top = ceil($upperY * $x);
                $bottom = $top - ($size + 1);
                $rightBottom = floor($lowerY * ($x + $size - 1));
                if ($rightBottom <= $bottom) {
                    break;
                }
            }
        }

        // Check whether we were right
        $limitsLeft = $this->getLimits($x, $lowerY, $upperY);
        $limitsRight = $this->getLimits($x + $size - 1, $lowerY, $upperY);
        if ($limitsRight['lowerY'] != $limitsLeft['upperY'] - $size + 1) {
            throw new Exception('Noooooo');
        }
        return $x * 10000 + $limitsRight['lowerY'];
    }

    public function getLimits(int $x, float $lowerY, float $upperY, $normalize = false)
    {
        // calculate lowerY
        $y = round($lowerY * $x);
        $inBeam = $this->intCode->reset([$x, $y])->run()->getOutput();
        $searchOffset = $inBeam ? -1 : 1;
        do {
            $y += $searchOffset;
        } while ($inBeam == $this->intCode->reset([$x, $y])->run()->getOutput());
        $lowerY = $inBeam ? $y + 1 : $y;

        // Calculate higherY
        $y = round($upperY * $x);
        $inBeam = $this->intCode->reset([$x, $y])->run()->getOutput();
        $searchOffset = $inBeam ? 1 : -1;
        do {
            $y += $searchOffset;
        } while ($inBeam == $this->intCode->reset([$x, $y])->run()->getOutput());
        $upperY = $inBeam ? $y - 1 : $y;
        return $normalize
            ? ['lowerY' => $lowerY / $x, 'upperY' => $upperY / $x]
            : ['lowerY' => $lowerY, 'upperY' => $upperY];
    }
}