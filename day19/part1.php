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

    public function run()
    {
        $num = 0;
        for ($y = 0; $y < 50; $y++) {
            for ($x = 0; $x < 50; $x++) {
                $this->intCode->reset([$x, $y]);
                $this->intCode->run();
                $out = $this->intCode->getOutput();
                echo $out ? '#' : '.';
                $num += $out;
            }
            echo "\n";
        }
        return $num;
    }
}