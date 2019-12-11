<?php

use IntCode\IntCode;

ini_set('display_errors', 1);
error_reporting(E_ALL);
$start = microtime(true);

require_once('../common/IntCode.php');

$code = file_get_contents('input.txt');

$robot = new Robot(new IntCode($code));
$robot->run();

var_dump($robot->getNumPanels(true));

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
    }

    public function getPosition(): Point
    {
        return $this->position;
    }

    public function getPanelAtPosition(): Panel
    {
        return $this->getPanelAt($this->position);
    }

    public function getPanelAt(Point $point): Panel
    {
        if (empty($this->hull[$point->x][$point->y])) {
            $this->hull[$point->x][$point->y] = new Panel();
        }
        return $this->hull[$point->x][$point->y];
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