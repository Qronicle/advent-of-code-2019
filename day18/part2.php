<?php

require_once('../common/common.php');
require_once('../common/image.php');

$input = file_get_contents('input2.txt');

$maze = new Maze($input);
echo "\nLength: " . $maze->run();

timer_end();

class Maze
{
    const TILE_WALL  = '#';
    const TILE_FLOOR = '.';

    /** @var array */
    protected $tiles;

    /** @var array[] */
    protected $startPositions;

    /** @var bool[] */
    protected $unlocked;

    /** @var array[] */
    protected $keys;

    /** @var string[] */
    protected $keyPoints;

    /** @var int */
    protected $numKeys;

    /** @var array[] */
    protected $doors;

    /** @var string[] */
    protected $doorPoints;

    protected $cache;

    protected $completeRoute;
    protected $completeLength;

    protected $keyTree;

    public function __construct(string $input)
    {
        $this->parseInput($input);
        $this->createTree();
    }

    public function run()
    {
        $minSteps = 0;
        $weights = [];
        $nodes = [['positions' => ['@0', '@1', '@2', '@3'], 'steps' => 0, 'keys' => [], 'tmp' => []]];
        $finishedNode = null;
        while (true) {
            $endNodes = [];
            $newMinSteps = $finishedNode ? $finishedNode['steps'] : null;
            foreach ($nodes as $node) {
                foreach ($node['positions'] as $robot => $position) {
                    if ($node['steps'] > $minSteps) {
                        if (!$finishedNode) {
                            $endNodes[implode('', $node['tmp'])] = $node;
                            $newMinSteps = is_null($newMinSteps) ? $node['steps'] : min($node['steps'], $newMinSteps);
                        }
                        continue;
                    }
                    foreach ($this->keyTree[$position] as $targetKey => $targetData) {
                        // Check whether we can access this key
                        if (array_diff($targetData->doors, $node['keys'])) continue;
                        // Check whether we want this key
                        if (in_array(strtoupper($targetKey), $node['keys'])) continue;
                        // Try and run to this key maybe!
                        $newSteps = $node['steps'] + $targetData->steps;
                        $newKeys = array_unique(array_merge($node['keys'],array_map('strtoupper', $targetData->keys)));
                        sort($newKeys);
                        $newPositions = $node['positions'];
                        $newPositions[$robot] = $targetKey;
                        $weightKey = implode('', $newPositions) . '_' . implode('', $newKeys);
                        if (!isset($weights[$weightKey]) || $newSteps < $weights[$weightKey]) {
                            $weights[$weightKey] = $newSteps;
                            $newNode = ['positions' => $newPositions, 'steps' => $newSteps, 'keys' => $newKeys, 'tmp' => $node['tmp']];
                            foreach ($targetData->keys as $key) {
                                if (!in_array($key, $newNode['tmp'])) {
                                    $newNode['tmp'][] = $key;
                                }
                            }
                            $newMinSteps = is_null($newMinSteps) ? $newSteps : min($newSteps, $newMinSteps);
                            if (count($newKeys) == $this->numKeys) {
                                if (!$finishedNode || $newSteps < $finishedNode['steps']) {
                                    echo "Found end at $newSteps\n";
                                    $finishedNode = $newNode;
                                }
                            } else {
                                $endNodes[implode('', $newNode['tmp'])] = $newNode;
                            }
                        }
                    }
                }
            }
            if (!$endNodes) {
                break;
            }
            $minSteps = $finishedNode ? $finishedNode['steps'] : $newMinSteps;
            $nodes = $endNodes;
        }
        print_r(implode('', $finishedNode['tmp']));
        return $finishedNode['steps'];
    }

    public function getPathLength(string $path): int
    {
        $currentKey = '*';
        $path = str_split($path);
        $length = 0;
        foreach ($path as $key) {
            $length += $this->keyTree[$currentKey][$key]->steps;
            $currentKey = $key;
        }
        return $length;
    }

    protected function createTree()
    {
        $this->keyTree = [];

        // First we create nodes for the start positions
        foreach ($this->startPositions as $i => $startPosition) {
            $key = "@$i";
            $targets = $this->getAllPathsFromPoint($startPosition);
            foreach ($targets as $targetKey => $target) {
                $this->keyTree[$key][$targetKey] = (object)[
                    'keys'  => $target->keys,
                    'doors' => $target->doors,
                    'steps' => $target->length,
                ];
            }
        }

        // Then we calculate all viable routes from all keys to all other keys
        foreach ($this->keys as $key => $point) {
            $targets = $this->getAllPathsFromPoint($point);
            $node = [];
            foreach ($targets as $targetKey => $target) {
                if ($targetKey == $key) continue; // Don't go to yourself
                if (in_array(strtoupper($targetKey), $target->doors)) continue; // Don't go to keys we need to pass their door for
                $node[$targetKey] = (object)[
                    'keys'  => $target->keys,
                    'doors' => $target->doors,
                    'steps' => $target->length,
                ];
            }
            $this->keyTree[$key] = $node;
        }
    }

    protected function getAllPathsFromPoint(array $start, $stopAtDoor = false)
    {
        $cacheKey = implode(',', $start);
        if (!$stopAtDoor && isset($this->cache[$cacheKey])) {
            //echo "Load $cacheKey from cache\n";
            return $this->cache[$cacheKey];
        }
        $steps = 0;
        $points = [$start];
        $paths = [];
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
                    if ($tile != self::TILE_WALL && !isset($weights[$target[1]][$target[0]])) {
                        $weights[$target[1]][$target[0]] = $point;
                        if ($tile != self::TILE_FLOOR) {
                            // Check whether we can pass the door
                            if (ord($tile) < 91) {
                                if ($stopAtDoor && !$this->unlocked[$tile]) {
                                    continue;
                                }
                            } else {
                                $paths[$tile] = (object)[
                                    'key'   => $tile,
                                    'steps' => $steps,
                                    'point' => [$target[0], $target[1]],
                                ];
                            }
                        }
                        $endPoints[] = $target;
                    }
                }
            }
            if (!$endPoints) {
                break;
            }
            $points = $endPoints;
        };
        // Reconstruct complete paths from start to key
        $keyPaths = [];
        foreach ($paths as $keyEndPoint) {
            $pathInfo = (object)[
                'doors'  => [],
                'keys'   => [$keyEndPoint->key],
                'length' => $keyEndPoint->steps,
            ];
            $point = $keyEndPoint->point;
            for ($step = 0; $step < $keyEndPoint->steps - 1; $step++) {
                $point = $weights[$point[1]][$point[0]];
                $pString = $point[0] . ',' . $point[1];
                if (isset($this->doorPoints[$pString])) {
                    array_unshift($pathInfo->doors, $this->doorPoints[$pString]);
                } elseif (isset($this->keyPoints[$pString])) {
                    array_unshift($pathInfo->keys, $this->keyPoints[$pString]);
                }
            }
            $keyPaths[$keyEndPoint->key] = $pathInfo;
        }
        ksort($keyPaths);
        if (!$stopAtDoor) {
            $this->cache[$cacheKey] = $keyPaths;
        }
        return $keyPaths;
    }

    public function parseInput(string $input)
    {
        $this->tiles = [];
        $this->unlocked = [];
        $this->doors = [];
        $this->doorPoints = [];
        $this->keys = [];
        $this->keyPoints = [];
        $this->cache = [];
        $img = '';
        $lines = explode("\n", $input);
        foreach ($lines as $y => $line) {
            foreach (str_split($line) as $x => $char) {
                if ($char == '@') {
                    $this->startPositions[] = [$x, $y];
                    $char = self::TILE_FLOOR;
                }
                if (ord($char) > 64 && ord($char) < 91) {
                    $this->unlocked[$char] = false;
                    $this->doors[$char] = [$x, $y];
                    $this->doorPoints[$x . "," . $y] = $char;
                }
                if (ord($char) > 96 && ord($char) < 123) {
                    $this->keys[$char] = [$x, $y];
                    $this->keyPoints[$x . "," . $y] = $char;
                }
                $img .= $char == self::TILE_WALL ? 0 : 6;
                $this->tiles[$y][$x] = $char;
            }
            $img .= "\n";
        }
        $this->numKeys = count($this->keys);
        strtoimg($img, 'maze.png');
    }
}

// rbgkfxeulaqczpsmotwdnvhjiy
// rbgkfxeulacqzpsmotdwnvhjiy