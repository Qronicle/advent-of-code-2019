<?php

require_once('../common/common.php');

$input = '##.#.
.##..
##.#.
.####
###..';

$life = new GameOfLife($input);
echo $life->run();

timer_end();

class GameOfLife
{
    const WIDTH  = 5;
    const HEIGHT = 5;

    /** @var bool[] */
    protected $tiles = [];

    protected $states = [];

    public function __construct(string $input)
    {
        $lines = explode("\n", $input);
        foreach ($lines as $line) {
            $line = str_split($line);
            foreach ($line as $char) {
                $this->tiles[] = $char == '#' ? 1 : 0;
            }
        }
    }

    public function run(): int
    {
        while (true) {

            // Save current state
            $state = bindec(implode($this->tiles));
            if (isset($this->states[$state])) {
                break;
            }
            $this->states[$state] = true;

            // Calculate next tiles state
            $newTiles = [];
            for ($y = 0; $y < self::HEIGHT; $y++) {
                for ($x = 0; $x < self::WIDTH; $x++) {
                    $adj = [];
                    $currIndex = $y * self::WIDTH + $x;
                    if ($x > 0) $adj[] = $currIndex - 1;
                    if ($x + 1 < self::WIDTH) $adj[] = $currIndex + 1;
                    if ($y > 0) $adj[] = $currIndex - self::WIDTH;
                    if ($y + 1 < self::HEIGHT) $adj[] = $currIndex + self::WIDTH;
                    $numBugs = 0;
                    foreach ($adj as $index) {
                        $numBugs += $this->tiles[$index];
                    }
                    if (!$this->tiles[$currIndex]) {
                        if ($numBugs == 1 || $numBugs == 2) {
                            $newTiles[$currIndex] = 1;
                        } else {
                            $newTiles[$currIndex] = $this->tiles[$currIndex];
                        }
                    } else {
                        if ($numBugs == 1) {
                            $newTiles[$currIndex] = 1;
                        } else {
                            $newTiles[$currIndex] = 0;
                        }
                    }
                }
            }
            $this->tiles = $newTiles;
        }

        // Calculate biodiversity
        $biodiversity = 0;
        foreach ($this->tiles as $pow => $tile) {
            if ($tile) {
                $biodiversity += pow(2, $pow);
            }
        }

        return $biodiversity;
    }

    public function print()
    {
        for ($y = 0; $y < self::HEIGHT; $y++) {
            for ($x = 0; $x < self::WIDTH; $x++) {
                $currIndex = $y * self::WIDTH + $x;
                echo $this->tiles[$currIndex] ? '#' : '.';
            }
            echo "\n";
        }
    }
}