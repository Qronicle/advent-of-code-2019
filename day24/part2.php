<?php

require_once('../common/common.php');

$input = '##.#.
.##..
##.#.
.####
###..';

$life = new GameOfLife($input);
echo $life->run(200);

timer_end();

class GameOfLife
{
    const WIDTH  = 5;
    const HEIGHT = 5;
    const SPECIAL = 12;

    /** @var array[] */
    protected $tiles = [];

    protected $states = [];

    public function __construct(string $input)
    {
        $lines = explode("\n", $input);
        foreach ($lines as $line) {
            $line = str_split($line);
            foreach ($line as $char) {
                $this->tiles[0][] = $char == '#' ? 1 : 0;
            }
        }
    }

    public function run(int $iterations): int
    {
        for ($i = 0; $i < $iterations; $i++) {

            $levels = array_keys($this->tiles);
            $minLevel = min($levels);
            $maxLevel = max($levels);

            $newTiles = [];

            for ($level = $minLevel - 1; $level <= $maxLevel + 1; $level++) {
                for ($y = 0; $y < self::HEIGHT; $y++) {
                    for ($x = 0; $x < self::WIDTH; $x++) {
                        $currIndex = $y * self::WIDTH + $x;
                        if ($currIndex == self::SPECIAL) continue;

                        // Calculate number of bugs on adjacent tiles
                        $numBugs = 0;
                        $adj = [[$x, $y - 1], [$x + 1, $y], [$x, $y + 1], [$x - 1, $y]];
                        foreach ($adj as $point) {
                            if ($point[0] < 0) {
                                // outer left of special
                                $numBugs += $this->tiles[$level - 1][self::SPECIAL - 1] ?? 0;
                            } elseif ($point[0] == self::WIDTH) {
                                // outer right of special
                                $numBugs += $this->tiles[$level - 1][self::SPECIAL + 1] ?? 0;
                            } elseif ($point[1] < 0) {
                                // outer top of special
                                $numBugs += $this->tiles[$level - 1][self::SPECIAL - self::WIDTH] ?? 0;
                            } elseif ($point[1] == self::HEIGHT) {
                                // outer bottom of special
                                $numBugs += $this->tiles[$level - 1][self::SPECIAL + self::WIDTH] ?? 0;
                            } else {
                                $adjIndex = $point[1] * self::WIDTH + $point[0];
                                if ($adjIndex == self::SPECIAL) {
                                    // We need the inner ones
                                    if ($x > $point[0]) {
                                        // right side
                                        for ($innerY = 0; $innerY < self::HEIGHT; $innerY++) {
                                            $numBugs += $this->tiles[$level + 1][($innerY + 1) * self::WIDTH - 1] ?? 0;
                                        }
                                    } elseif ($x < $point[0]) {
                                        // left side
                                        for ($innerY = 0; $innerY < self::HEIGHT; $innerY++) {
                                            $numBugs += $this->tiles[$level + 1][$innerY * self::WIDTH] ?? 0;
                                        }
                                    } elseif ($y < $point[1]) {
                                        // top side
                                        for ($innerX = 0; $innerX < self::WIDTH; $innerX++) {
                                            $numBugs += $this->tiles[$level + 1][$innerX] ?? 0;
                                        }
                                    } elseif ($y > $point[1]) {
                                        // top side
                                        $t = self::WIDTH * (self::HEIGHT - 1);
                                        for ($innerX = 0; $innerX < self::WIDTH; $innerX++) {
                                            $numBugs += $this->tiles[$level + 1][$t + $innerX] ?? 0;
                                        }
                                    } else {
                                        throw new Exception('This cannot happen');
                                    }
                                } else {
                                    $numBugs += $this->tiles[$level][$adjIndex] ?? 0;
                                }
                            }

                            // There are no special cases when the number of bugs gt 2
                            if ($numBugs > 2) {
                                break;
                            }
                        }

                        // Calculate whether bug lives there nao
                        if (empty($this->tiles[$level][$currIndex])) {
                            if ($numBugs == 1 || $numBugs == 2) {
                                $newTiles[$level][$currIndex] = 1;
                            } else {
                                $newTiles[$level][$currIndex] = $this->tiles[$level][$currIndex] ?? 0;
                            }
                        } else {
                            if ($numBugs == 1) {
                                $newTiles[$level][$currIndex] = 1;
                            } else {
                                $newTiles[$level][$currIndex] = 0;
                            }
                        }
                    }
                }
                // Check whether a bug is living in the outer worlds, otherwise unset
                if ($level == $minLevel - 1 || $level == $maxLevel + 1) {
                    $hasBugs = false;
                    foreach ($newTiles[$level] as $tile) {
                        if ($tile) {
                            $hasBugs = true;
                            break;
                        }
                    }
                    if (!$hasBugs) {
                        unset($newTiles[$level]);
                    }
                }
            }

            $this->tiles = $newTiles;
        }

        // Calculate amount of bugs
        $numBugs = 0;
        foreach ($this->tiles as $level => $tiles) {
            foreach ($tiles as $tile) {
                if ($tile) {
                    $numBugs++;
                }
            }
        }
        //$this->print();

        return $numBugs;
    }

    public function print()
    {
        ksort($this->tiles);
        foreach ($this->tiles as $level => $tiles) {
            echo "Level " . $level . "\n--------\n";
            for ($y = 0; $y < self::HEIGHT; $y++) {
                for ($x = 0; $x < self::WIDTH; $x++) {
                    $currIndex = $y * self::WIDTH + $x;
                    if (!isset($tiles[$currIndex])) {
                        echo '?';
                    } else {
                        echo $tiles[$currIndex] ? '#' : '.';
                    }
                }
                echo "\n";
            }
            echo "\n";
        }
    }
}