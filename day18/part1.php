<?php

//ini_set('memory_limit', '4092M');
require_once('../common/common.php');

$input = file_get_contents('input.txt');

$maze = new Maze($input);
echo "Length: " . $maze->run();

timer_end();

class Maze
{
    const TILE_WALL  = '#';
    const TILE_FLOOR = '.';

    /** @var array */
    protected $tiles;

    /** @var int[] */
    protected $position;

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

    public function __construct(string $input)
    {
        $this->parseInput($input);
    }

    protected function runRecursive(stdClass $route)
    {
        if ($route->length >= $this->completeLength) {
            return;
        }
        $keyPaths = $this->getAllPathsFromPoint($route->position, !$route->keys);
        foreach ($keyPaths as $key => $keyPath) {
            // Don't check keys already in the path
            if (isset($route->keys[$key])) {
                continue;
            }
            // Check whether we have door clearance
            if (array_diff($keyPath->doors, $route->doors)) {
                //echo "Cannot continue to $key because of doors: " . implode(', ', array_diff($keyPath->doors, $route->doors)) . "\n";
                continue;
            }
            $newRoute = clone $route;
            // Merge new keys in the route
            foreach ($keyPath->keys as $newKey) {
                $door = strtoupper($newKey);
                if (!isset($newRoute->keys[$newKey])) {
                    $newRoute->keys[$newKey] = $newKey;
                    $newRoute->doors[$door] = $door;
                }
            }
            $newRoute->position = $this->keys[$key];
            $newRoute->length += $keyPath->length;
            if (count($newRoute->keys) == $this->numKeys) {
                if ($newRoute->length < $this->completeLength) {
                    $this->completeRoute = $newRoute;
                    $this->completeLength = $newRoute->length;
                    echo $newRoute->length;
                    timer_lap();
                }
                unset($newRoute);
                return;
            } else {
                $this->runRecursive($newRoute);
                unset($newRoute);
            }
        }
    }

    public function run()
    {
        $route = (object)[
            'position' => $this->position,
            'keys'     => [],
            'doors'    => [],
            'length'   => 0,
        ];

        $this->completeRoute = null;
        $this->completeLength = PHP_INT_MAX;

        $this->runRecursive($route);

        return $this->completeLength;
    }

    public function run2()
    {
        $numKeys = count($this->keys);
        $routes = [(object)[
            'position' => $this->position,
            'keys'     => [],
            'doors'    => [],
            'length'   => 0,
        ]];
        $completeRoutes = [];

        $phase = 0;
        while ($routes) {
            $phase++;
            $newRoutes = [];
            foreach ($routes as $route) {
                //echo "----\n";
                //echo "$phase. Route " . implode(', ', $route->keys) . "\n";
                $keyPaths = $this->getAllPathsFromPoint($route->position, !$route->keys);
                foreach ($keyPaths as $key => $keyPath) {
                    // Don't check keys already in the path
                    if (isset($route->keys[$key])) {
                        continue;
                    }
                    // Check whether we have door clearance
                    if (array_diff($keyPath->doors, array_merge($route->doors, array_map('strtoupper', $keyPath->keys)))) {
                        //echo "Cannot continue to $key because of doors: " . implode(', ', array_diff($keyPath->doors, $route->doors)) . "\n";
                        continue;
                    }
                    $newRoute = clone $route;
                    // Merge new keys in the route
                    foreach ($keyPath->keys as $newKey) {
                        $door = strtoupper($newKey);
                        if (!isset($newRoute->keys[$newKey])) {
                            $newRoute->keys[$newKey] = $newKey;
                            $newRoute->doors[$door] = $door;
                        }
                    }
                    $newRoute->position = $this->keys[$key];
                    $newRoute->length += $keyPath->length;
                    if (count($newRoute->keys) == $numKeys) {
                        $completeRoutes[] = $newRoute;
                    } else {
                        $newRoutes[] = $newRoute;
                    }
                }
                unset($route);
            }
            unset($routes);
            $routes = $newRoutes;
            echo "Phase $phase: " . count($newRoutes) . "\n";
        }

        $shortestRoute = null;
        foreach ($completeRoutes as $completeRoute) {
            if (is_null($shortestRoute) || $completeRoute->length < $shortestRoute->length) {
                $shortestRoute = $completeRoute;
            }
        }

        print_r($shortestRoute);
        die;
    }

    protected function resetDoors()
    {
        foreach ($this->unlocked as $door => $unlocked) {
            $this->unlocked[$door] = false;
        }
    }

    public function run1()
    {
        // Lock all doors again
        foreach ($this->unlocked as $door => $unlocked) {
            $this->unlocked[$door] = false;
        }
        $totalKeys = count($this->keys);
        $foundKeys = [];
        $stepsPerKey = [];
        $shortestSteps = [];

        do {
            $paths = $this->getNextAvailablePaths();
            if (!$paths) {
                die(':(');
            }

            // Add the key!
            $key = reset($paths);
            // Check whether there previously was a shorter route to this key
            if (isset($shortestSteps[$key->key])) {
                $shortestStep = null;
                foreach ($shortestSteps[$key->key] as $step => $otherKey) {
                    if ($otherKey->steps < $key->steps && (is_null($shortestStep) || $otherKey->steps <= $shortestSteps[$key->key][$shortestStep]->steps)) {
                        $shortestStep = $step;
                    }
                }
                // Go back to state before we should have gone to this key
                if (!is_null($shortestStep)) {
                    $key = $shortestSteps[$key->key][$shortestStep];
                    $unfindKeys = array_splice($foundKeys, $shortestStep);
                    array_splice($stepsPerKey, $shortestStep);
                    foreach ($shortestSteps as $keyName => $shortKeys) {
                        // Splicing doesn't work on assoc arrays, so unset them manually
                        foreach ($shortKeys as $s => $tmp) {
                            if ($s >= $shortestStep) {
                                unset($shortestSteps[$keyName][$s]);
                            }
                        }
                    }
                    foreach ($unfindKeys as $unfindKey) {
                        $this->tiles[$this->keys[$unfindKey][1]][$this->keys[$unfindKey][0]] = $unfindKey;
                        $this->unlocked[strtoupper($unfindKey)] = false;
                    }
                    echo "-----\nFound shorter route to $key->key\nBack to step $shortestStep:\n";
                    print_r($shortestSteps);
                    print_r($foundKeys);
                    print_r($this->unlocked);
                }
            }
            $this->tiles[$key->y][$key->x] = self::TILE_FLOOR;
            unset($paths[$key->key]);
            $this->unlocked[strtoupper($key->key)] = true;
            $this->position[0] = $key->x;
            $this->position[1] = $key->y;
            $foundKeys[] = $key->key;
            $stepsPerKey[] = $key->steps;
            echo count($foundKeys) . ": Move $key->steps steps to key $key->key\n";
            echo implode(',', $foundKeys) . "\n";

            // Save the shortest path to the other available keys
            foreach ($paths as $key) {
                $shortestSteps[$key->key][count($foundKeys) - 1] = $key;
            }
        } while (count($foundKeys) < $totalKeys);

        echo "Found keys: " . implode(',', $foundKeys) . "\nTotal steps: " . array_sum($stepsPerKey);
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
            for ($step = 0; $step < $keyEndPoint->steps; $step++) {
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

    protected function getNextAvailablePaths()
    {
        $steps = 0;
        $points = [$this->position];
        $weights = [];
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
                        $weights[$target[1]][$target[0]] = $steps;
                        if ($tile != self::TILE_FLOOR) {
                            // Check whether we can pass the door
                            if (ord($tile) < 91) {
                                if (!$this->unlocked[$tile]) {
                                    continue;
                                }
                            } else {
                                $paths[$tile] = (object)[
                                    'key'   => $tile,
                                    'steps' => $steps,
                                    'x'     => $target[0],
                                    'y'     => $target[1],
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
        }
        ksort($paths);
        return $paths;
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
        $lines = explode("\n", $input);
        foreach ($lines as $y => $line) {
            foreach (str_split($line) as $x => $char) {
                if ($char == '@') {
                    $this->position = [$x, $y];
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
                $this->tiles[$y][$x] = $char;
            }
        }
        $this->numKeys = count($this->keys);
    }
}