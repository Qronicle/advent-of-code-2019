<?php

use IntCode\IntCode;

ini_set('display_errors', 1);
error_reporting(E_ALL);
$start = microtime(true);

require_once('../common/IntCode.php');
require_once('../common/image.php');

$code = file_get_contents('input.txt');

$robot = new Robot(new IntCode($code));
$robot->run();

$bounds = $robot->getBounds();
$imgString = '';
for ($y = $bounds[0]; $y >= $bounds[2]; $y--) {
    for ($x = $bounds[3]; $x <= $bounds[1]; $x++) {
        $imgString .= $robot->getPanelColorAtCoordinates($x, $y);
    }
    $imgString .= "\n";
}

strtoimg($imgString, 'output.png');

$end = microtime(true);
echo "\nResult reached in " . round($end - $start, 2) . " seconds\n";

class Robot
{
    protected $hull = [];

    /**
     * @var IntCode
     */
    protected $intCode = null;

    /**
     * @var Point
     */
    protected $position;

    /**
     * @var int
     */
    protected $direction = Direction::UP;

    /**
     * Hull bounds defined by [top, right, bottom, left]
     *
     * @var int[]
     */
    protected $bounds = [0, 0, 0, 0];

    public function __construct(IntCode $intCode)
    {
        $this->intCode = $intCode;
        $this->position = new Point();
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        $panel = $this->getPanelAtPosition();
        $this->intCode->setInput([$panel->color]);
        try {
            $this->intCode->run();
        } catch (\IntCode\InputNecessaryException $ex) {
            unset($ex); // Apparently important
            list($color, $turn) = $this->intCode->getOutput(false);
            $this->intCode->resetOutput();
            //echo "Painting " . $this->position->__toString() . ': ' . $color . ' - total: ' . $this->getNumPanels() . "\n";
            $panel->paint($color);
            $this->turn($turn);
            $this->move();
            $this->run();
        }
    }

    /**
     * @param int $input
     * @throws Exception
     */
    public function turn(int $input)
    {
        switch ($input) {
            case 0: $this->turnLeft(); break;
            case 1: $this->turnRight(); break;
            default: throw new Exception('Invalid turn value');
        }
    }

    public function turnLeft()
    {
        $this->direction = ($this->direction + 3) % 4;
    }

    public function turnRight()
    {
        $this->direction = ($this->direction + 1) % 4;
    }

    public function move(int $distance = 1)
    {
        $this->position->x += Direction::$x[$this->direction] * $distance;
        $this->position->y += Direction::$y[$this->direction] * $distance;
        $this->bounds = [
            max($this->bounds[0], $this->position->y),
            max($this->bounds[1], $this->position->x),
            min($this->bounds[2], $this->position->y),
            min($this->bounds[3], $this->position->x),
        ];
    }

    public function getPosition(): Point
    {
        return $this->position;
    }

    public function getPanelAtPosition(): Panel
    {
        return $this->getPanelAt($this->position);
    }

    public function getPanelColorAtCoordinates(int $x, int $y): int
    {
        if (empty($this->hull[$y][$x])) {
            return Color::BLACK;
        }
        return $this->hull[$y][$x]->color;
    }

    public function getPanelAt(Point $point): Panel
    {
        if (empty($this->hull[$point->y][$point->x])) {
            $this->hull[$point->y][$point->x] = new Panel();
            if ($point->x == 0 && $point->y == 0) {
                $this->hull[$point->y][$point->x]->color = Color::WHITE;
            }
        }
        return $this->hull[$point->y][$point->x];
    }

    public function getNumPanels(bool $painted = false): int
    {
        $num = 0;
        foreach ($this->hull as $x => $row) {
            if ($painted) {
                foreach ($row as $y => $panel) {
                    $num += $panel->painted ? 1 : 0;
                }
            } else {
                $num += count($row);
            }
        }
        return $num;
    }

    public function getBounds(): array
    {
        return $this->bounds;
    }
}

class Direction
{
    const UP    = 0;
    const RIGHT = 1;
    const DOWN  = 2;
    const LEFT  = 3;

    public static $x = [0, 1, 0, -1];
    public static $y = [1, 0, -1, 0];
}

class Point
{
    public $x = 0;
    public $y = 0;

    public function __toString()
    {
        return sprintf('(%s,%s)', $this->x, $this->y);
    }

}

class Color
{
    const BLACK = 0;
    const WHITE = 1;
}

class Panel
{
    public $color = Color::BLACK;
    public $painted = false;

    public function paint(int $color)
    {
        $this->color = $color;
        $this->painted = true;
    }
}