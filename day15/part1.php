<?php

use IntCode\IntCode;

ini_set('display_errors', 1);
error_reporting(E_ALL);
$start = microtime(true);

require_once('../common/IntCode.php');
require_once('../common/image.php');

$code = file_get_contents('input.txt');

$droid = new RepairDroid(new IntCode($code));
$droid->run();

$droid->getRoom()->render('room.png');

$end = microtime(true);
echo "\nResult reached in " . round($end - $start, 2) . " seconds\n";

class RepairDroid
{
    const MOVE_NORTH = 1;
    const MOVE_SOUTH = 2;
    const MOVE_WEST  = 3;
    const MOVE_EAST  = 4;

    /** @var Room */
    protected $room;

    /** @var IntCode */
    protected $intCode;

    /** @var Point */
    protected $position;

    public function __construct(IntCode $intCode)
    {
        $this->intCode = $intCode;
        $this->intCode->setHaltOnOutput(true);
        $this->room = new Room();
        $this->position = new Point();
    }

    public function run()
    {
        $step = 0;
        while (true) {
            $startPosition = $this->position;
            foreach ($this->getAdjacentCoordinates() as $moveDirection => $targetPosition) {
                if ($this->room->isExplored($targetPosition)) {
                    continue;
                }
                // The adjacent position is unexplored, try to move to there
                $this->intCode->setInput([$moveDirection]);
                $this->intCode->run();
                $status = $this->intCode->getOutput();
                $this->room->setTile($targetPosition, $status);
                switch ($status) {
                    case Room::TILE_WALL:
                        continue 2;
                    case Room::TILE_VOID:
                        $this->position = $targetPosition;
                        break 2;
                    case Room::TILE_OXYGEN:
                        $this->position = $targetPosition;
                        $path = $this->getRouteTo($this->position);
                        echo count($path) + 1;
                        return;
                }
            }
            // When we haven't moved, go to a tile that is not completely explored
            if ($startPosition == $this->position) {
                $this->room->render('room-' . str_pad(++$step, 3, 0, STR_PAD_LEFT) . '.png');
                echo "Current position: $this->position\n";
                $path = $this->getRouteToClosestUnexplored();
                foreach ($path as $path) {
                    $this->intCode->setInput([$path->direction]);
                    $this->intCode->run();
                    $this->position = $path->position;
                }
            }
        }
    }

    protected function getRouteToClosestUnexplored(array $path = [])
    {
        if (!$path) {
            $this->room->weights = [];
        }
        $position = count($path) ? end($path) : $this->position;
        $paths = [];
        foreach ($this->getAdjacentCoordinates($position) as $moveDirection => $target) {
            if (isset($this->room->weights[$target->y][$target->x])) {
                continue;
            }
            if (!$this->room->isExplored($target)) {
                $moves = [];
                foreach ($path as $p) {
                    $moves[] = (object)[
                        'direction' => $this->room->weights[$p->y][$p->x],
                        'position'  => $p,
                    ];
                }
                return $moves; // moves to position
            }
            if ($this->room->canMoveOn($target)) {
                $newPath = $path;
                $newPath[] = $target;
                $paths[] = $newPath;
                $this->room->weights[$target->y][$target->x] = $moveDirection;
            }
        }
        if (!$paths) {
            return false;
        }
        $pathsByLength = [];
        foreach ($paths as $p) {
            if ($completePath = $this->getRouteToClosestUnexplored($p)) {
                $pathsByLength[count($completePath)] = $completePath;
            }
        }
        if (!$pathsByLength) {
            return false;
        }
        ksort($pathsByLength);
        return array_shift($pathsByLength);
    }


    protected function getRouteTo(Point $point, array $path = [])
    {
        if (!$path) {
            $this->room->weights = [];
        }
        $position = count($path) ? end($path) : new Point();
        $paths = [];
        foreach ($this->getAdjacentCoordinates($position) as $moveDirection => $target) {
            $weight = count($path) + 1;
            if (!(!isset($this->room->weights[$target->y][$target->x]) || $weight < $this->room->weights[$target->y][$target->x])) {
                continue;
            }
            if ($this->room->canMoveOn($target)) {
                $newPath = $path;
                $newPath[] = $target;
                $paths[] = $newPath;
                $this->room->weights[$target->y][$target->x] = $weight;

                if ($target->equals($point)) {
                    $moves = [];
                    foreach ($path as $p) {
                        $moves[] = (object)[
                            'direction' => $this->room->weights[$p->y][$p->x],
                            'position'  => $p,
                        ];
                    }
                    return $moves; // moves to position
                }
            }
        }
        if (!$paths) {
            return false;
        }
        $pathsByLength = [];
        foreach ($paths as $p) {
            if ($completePath = $this->getRouteTo($point, $p)) {
                $pathsByLength[count($completePath)] = $completePath;
            }
        }
        if (!$pathsByLength) {
            return false;
        }
        ksort($pathsByLength);
        return array_shift($pathsByLength);
    }

    protected function getAdjacentCoordinates(Point $point = null): array
    {
        $point = $point ?: $this->position;
        return [
            self::MOVE_NORTH => new Point($point->x, $point->y - 1),
            self::MOVE_SOUTH => new Point($point->x, $point->y + 1),
            self::MOVE_WEST  => new Point($point->x - 1, $point->y),
            self::MOVE_EAST  => new Point($point->x + 1, $point->y),
        ];
    }

    public function getRoom()
    {
        return $this->room;
    }
}

class Room
{
    const TILE_WALL   = 0;
    const TILE_VOID   = 1;
    const TILE_OXYGEN = 2;
    const TILE_FOG    = 3;

    /** @var array */
    public $tiles = [];

    /** @var array */
    public $weights = [];

    /** @var int[] */
    protected $bounds = [];

    public function __construct()
    {
        $this->tiles[0][0] = self::TILE_VOID;
    }

    public function isExplored(Point $point): bool
    {
        return isset($this->tiles[$point->y][$point->x]);
    }

    public function setTile(Point $point, int $tileType)
    {
        $this->tiles[$point->y][$point->x] = $tileType;
        $this->bounds[0] = min($this->bounds[0] ?? $point->y, $point->y);
        $this->bounds[1] = max($this->bounds[1] ?? $point->x, $point->x);
        $this->bounds[2] = max($this->bounds[2] ?? $point->y, $point->y);
        $this->bounds[3] = min($this->bounds[3] ?? $point->x, $point->x);
    }

    public function canMoveOn(Point $point): bool
    {
        $tile = $this->tiles[$point->y][$point->x] ?? self::TILE_FOG;
        return $tile == self::TILE_VOID || $tile == self::TILE_OXYGEN;
    }

    public function render(string $file)
    {
        $string = '';
        for ($y = $this->bounds[0]; $y <= $this->bounds[2]; $y++) {
            for ($x = $this->bounds[3]; $x <= $this->bounds[1]; $x++) {
                $string .= $this->tiles[$y][$x] ?? self::TILE_FOG;
            }
            $string .= "\n";
        }
        strtoimg($string, $file, 10, [
            self::TILE_FOG    => [0, 0, 0],
            self::TILE_VOID   => [200, 200, 255],
            self::TILE_WALL   => [255, 255, 255],
            self::TILE_OXYGEN => [255, 100, 100],
        ]);
    }
}

class Point
{
    public $x = 0;
    public $y = 0;

    public function __construct(int $x = 0, int $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function equals(Point $p)
    {
        return $this->x == $p->x && $this->y == $p->y;
    }

    public function __toString()
    {
        return sprintf('(%s, %s)', $this->x, $this->y);
    }
}