<?php

require_once('../common/common.php');

$input = file_get_contents('input.txt');
/*/
$input = '                   A
                   A               
  #################.#############  
  #.#...#...................#.#.#  
  #.#.#.###.###.###.#########.#.#  
  #.#.#.......#...#.....#.#.#...#  
  #.#########.###.#####.#.#.###.#  
  #.............#.#.....#.......#  
  ###.###########.###.#####.#.#.#  
  #.....#        A   C    #.#.#.#  
  #######        S   P    #####.#  
  #.#...#                 #......VT
  #.#.#.#                 #.#####  
  #...#.#               YN....#.#  
  #.###.#                 #####.#  
DI....#.#                 #.....#  
  #####.#                 #.###.#  
ZZ......#               QG....#..AS
  ###.###                 #######  
JO..#.#.#                 #.....#  
  #.#.#.#                 ###.#.#  
  #...#..DI             BU....#..LF
  #####.#                 #.#####  
YN......#               VT..#....QG
  #.###.#                 #.###.#  
  #.#...#                 #.....#  
  ###.###    J L     J    #.#.###  
  #.....#    O F     P    #.#...#  
  #.###.#####.#.#####.#####.###.#  
  #...#.#.#...#.....#.....#.#...#  
  #.#####.###.###.#.#.#########.#  
  #...#.#.....#...#.#.#.#.....#.#  
  #.###.#####.###.###.#.#.#######  
  #.#.........#...#.............#  
  #########.###.###.#############  
           B   J   C               
           U   P   P               ';//*/

$maze = new Maze($input);
echo "Length: " . $maze->run();

timer_end();

class Maze
{
    const TILE_WALL   = '#';
    const TILE_FLOOR  = '.';
    const TILE_VOID   = ' ';
    const TILE_PORTAL = 'P';
    const TILE_END    = 'E';

    /** @var array */
    protected $tiles;

    /** @var int[] */
    protected $startPosition;

    /** @var stdClass[] */
    protected $portals;

    public function __construct(string $input)
    {
        $this->parseInput($input);
    }

    public function run()
    {
        $steps = 0;
        $weights = [];
        $points = [$this->startPosition];
        while (++$steps) {
            $endPoints = [];
            foreach ($points as $point) {
                // When the current point is a portal, go to it's exit (when the exit has no weight yet)
                if ($this->tiles[$point[1]][$point[0]] == self::TILE_PORTAL) {
                    $target = $this->portals[implode(',', $point)];
                    if (!isset($weights[$target[1]][$target[0]])) {
                        $weights[$target[1]][$target[0]] = $point;
                        $endPoints[] = $target;
                        continue;
                    }
                }
                $adjacentPoints = [
                    [$point[0] + 1, $point[1]],
                    [$point[0] - 1, $point[1]],
                    [$point[0], $point[1] + 1],
                    [$point[0], $point[1] - 1],
                ];
                foreach ($adjacentPoints as $target) {
                    $tile = $this->tiles[$target[1]][$target[0]] ?? self::TILE_WALL;
                    if ($tile == self::TILE_WALL || $tile == self::TILE_VOID || isset($weights[$target[1]][$target[0]])) {
                        continue;
                    }
                    $weights[$target[1]][$target[0]] = $point;
                    if ($tile == self::TILE_END) {
                        return $steps;
                    }
                    $endPoints[] = $target;
                }
            }
            if (!$endPoints) {
                break;
            }
            $points = $endPoints;
        }
        throw new Exception('No end found');
    }

    public function parseInput(string $input)
    {
        $test = [];
        $this->tiles = [];
        $this->portals = [];
        $portals = [];
        $lines = explode("\n", $input);
        $height = count($lines) - 4;
        $width = strlen(reset($lines)) - 4;
        for ($y = 0, $l = 2; $y < $height; $y++, $l++) {
            for ($x = 0, $c = 2; $x < $width; $x++, $c++) {
                $tile = $lines[$l][$c];
                if ($tile == self::TILE_FLOOR) {
                    if ($portal = $this->searchPortal($c, $l, $lines)) {
                        if ($portal == 'AA') {
                            $this->startPosition = [$x, $y];
                        } elseif ($portal == 'ZZ') {
                            $tile = self::TILE_END;
                        } else {
                            $tile = self::TILE_PORTAL;
                            $portals[$portal][] = [$x, $y];
                        }
                    }
                } elseif ($tile != self::TILE_WALL) {
                    $tile = self::TILE_VOID;
                }
                $this->tiles[$y][$x] = $tile;
                $test[$l][$c] = $tile;
            }
        }
        // Debug map
        foreach ($this->tiles as $y => $tiles) {
            foreach ($tiles as $x => $tile) {
                echo $tile;
            }
            echo "\n";
        }
        //*/
        // Create portals array
        foreach ($portals as $portal => $points) {
            if (count($points) != 2) {
                throw new Exception("Invalid portal $portal");
            }
            $this->portals[implode(',', $points[0])] = $points[1];
            $this->portals[implode(',', $points[1])] = $points[0];
        }
    }

    /**
     * @param int   $x
     * @param int   $y
     * @param array $lines
     * @return string|null
     */
    protected function searchPortal(int $x, int $y, array &$lines)
    {
        $d = false && $y == 30 && $x = 81;
        if ($d) echo "$x, $y -----\n";
        $directions = [[1, 0], [0, 1], [-1, 0], [0, -1]];
        foreach ($directions as $dir) {
            $tile = $lines[$y + $dir[1]][$x + $dir[0]];
            $ord = ord($tile);
            if ($d) echo "$dir[0] $dir[1] - $tile - $ord\n";
            if ($ord >= 65 && $ord <= 90) {
                $portal = $tile . $lines[$y + $dir[1] * 2][$x + $dir[0] * 2];
                if ($dir[0] == -1 || $dir[1] == -1) {
                    $portal = strrev($portal);
                }
                return $portal;
            }
        }
        return null;
    }
}