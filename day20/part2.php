<?php

require_once('../common/common.php');

$input = file_get_contents('input.txt');

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

    /** @var int */
    protected $width;

    /** @var int */
    protected $height;

    /** @var int[] */
    protected $startPosition;

    /** @var array[] */
    protected $portals;

    /** @var array */
    protected $portalTree;

    public function __construct(string $input)
    {
        $this->parseInput($input);
    }

    public function run()
    {
        $minSteps = 0;
        $weights = [];
        $nodes = [['portal' => 'AA', 'steps' => 0, 'level' => 0]];
        $finishedNode = null;
        while (true) {
            $endNodes = [];
            $newMinSteps = $finishedNode ? $finishedNode['steps'] : null;
            foreach ($nodes as $node) {
                if ($node['steps'] > $minSteps) {
                    if (!$finishedNode) {
                        $endNodes[] = $node;
                        $newMinSteps = is_null($newMinSteps) ? $node['steps'] : min($node['steps'], $newMinSteps);
                    }
                    continue;
                }
                $level = $node['level'];
                foreach ($this->portalTree[$node['portal']] as $targetPortal => $targetData) {
                    if ($targetPortal == 'ZZ' && $level != 0) continue;
                    $newSteps = $node['steps'] + $targetData['steps'];
                    $newLevel = $level + $targetData['level'];
                    if ($newLevel < 0) continue;
                    if (!isset($weights[$level][$targetPortal]) || $newSteps < $weights[$level][$targetPortal]) {
                        $weights[$level][$targetPortal] = $newSteps;
                        $newNode = ['portal' => $targetPortal, 'steps' => $newSteps, 'level' => $newLevel];
                        $newMinSteps = is_null($newMinSteps) ? $newSteps : min($newSteps, $newMinSteps);
                        if ($targetPortal == 'ZZ') {
                            if (!$finishedNode || $newSteps < $finishedNode['steps']) {
                                echo "Found end at $newSteps\n";
                                $finishedNode = $newNode;
                            }
                        } else {
                            $endNodes[] = $newNode;
                        }
                    }
                }
            }
            if (!$endNodes) {
                break;
            }
            $minSteps = $newMinSteps;
            $nodes = $endNodes;
        }
        return $finishedNode['steps'];
    }

    public function parseInput(string $input)
    {
        $this->tiles = [];
        $this->portals = [];
        $this->portalTree = [];
        $portals = [];
        $lines = explode("\n", $input);
        $this->height = $height = count($lines) - 4;
        $this->width = $width = strlen(reset($lines)) - 4;
        for ($y = 0, $l = 2; $y < $height; $y++, $l++) {
            for ($x = 0, $c = 2; $x < $width; $x++, $c++) {
                $tile = $lines[$l][$c];
                if ($tile == self::TILE_FLOOR) {
                    if ($portal = $this->searchPortalName($c, $l, $lines)) {
                        if ($portal == 'AA') {
                            $this->startPosition = [$x, $y];
                        } elseif ($portal == 'ZZ') {
                            $tile = self::TILE_END;
                        } else {
                            $tile = self::TILE_PORTAL;
                            $pos = $this->isOuterRing($x, $y) ? 'O' : 'I';
                            $portals[$portal][$pos] = [$x, $y];
                        }
                    }
                } elseif ($tile != self::TILE_WALL) {
                    $tile = self::TILE_VOID;
                }
                $this->tiles[$y][$x] = $tile;
            }
        }
        /*/ Debug map
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
            foreach ($points as $pos => $point) {
                $target = $points[$pos == 'O' ? 'I' : 'O'];
                $this->portals[implode(',', $points[$pos])] = [
                    'name'       => $portal . $pos,
                    'point'      => $point,
                    'target'     => $target,
                    'targetName' => $portal . ($pos == 'O' ? 'I' : 'O'),
                    'level'      => $pos == 'I' ? 1 : -1,
                ];
            }
        }
        // Create portals tree
        $startPortals = $this->searchLinkedPortals($this->startPosition);
        foreach ($startPortals as $linkedPortal) {
            $this->portalTree['AA'][$linkedPortal['targetName']] = [
                'steps' => $linkedPortal['steps'],
                'level' => $linkedPortal['level'],
            ];
        }
        foreach ($portals as $portal => $points) {
            foreach ($points as $pos => $point) {
                $linkedTo[$pos] = $this->searchLinkedPortals($point);
                // Don't include dead ends
                if (!$linkedTo) {
                    continue 2;
                }
            }
            foreach ($points as $pos => $point) {
                $portalName = $portal . $pos;
                $portalBranch = [];
                foreach ($linkedTo[$pos] as $linkedPortal) {
                    if ($linkedPortal['targetName'] == $portalName) {
                        continue; // Don't loop with ourselves
                    }
                    $portalBranch[$linkedPortal['targetName']] = [
                        'steps' => $linkedPortal['steps'],
                        'level' => $linkedPortal['level'],
                    ];
                }
                $this->portalTree[$portalName] = $portalBranch;
            }
        }
        // @todo You can do a few loops here and keep deleting empty tree nodes
    }

    protected function searchLinkedPortals($point): array
    {
        $steps = 0;
        $weights = [$point[1] => [$point[0] => 0]];
        $points = [$point];
        $portals = [];
        while (++$steps) {
            $endPoints = [];
            foreach ($points as $point) {
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
                    $weights[$target[1]][$target[0]] = $steps;
                    if ($tile == self::TILE_PORTAL) {
                        $portals[] = array_merge($this->portals[implode(',', $target)], [
                            'steps' => $steps + 1,
                        ]);
                        continue;
                    } elseif ($tile == self::TILE_END) {
                        $portals[] = [
                            'targetName' => 'ZZ',
                            'steps'      => $steps,
                            'level'      => 0,
                        ];
                        continue;
                    }
                    $endPoints[] = $target;
                }
            }
            if (!$endPoints) {
                break;
            }
            $points = $endPoints;
        };
        return $portals;
    }

    protected function isOuterRing($x, $y = null)
    {
        if (is_array($x)) {
            $y = $x[1];
            $x = $x[0];
        }
        return $x == 0 || $x == $this->width - 1 || $y == 0 || $y == $this->height - 1;
    }

    /**
     * @param int   $x
     * @param int   $y
     * @param array $lines
     * @return string|null
     */
    protected function searchPortalName(int $x, int $y, array &$lines)
    {
        $directions = [[1, 0], [0, 1], [-1, 0], [0, -1]];
        foreach ($directions as $dir) {
            $tile = $lines[$y + $dir[1]][$x + $dir[0]];
            $ord = ord($tile);
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