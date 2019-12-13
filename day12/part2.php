<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
$start = microtime(true);

/* @var SpaceObject[] */
$moons = [
    new SpaceObject(17, -12, 13),
    new SpaceObject(2, 1, 1),
    new SpaceObject(-1, -17, 7),
    new SpaceObject(12, -14, 18),
];

/*/ Test Data
$moons = [
    new SpaceObject(-1, 0, 2),
    new SpaceObject(2, -10, -7),
    new SpaceObject(4, -8, 8),
    new SpaceObject(3, 5, -1),
];//*/

/*/ Test Data
$moons = [
    new SpaceObject(-8, -10, 0),
    new SpaceObject(5, 5, 10),
    new SpaceObject(2, -7, 3),
    new SpaceObject(9, -8, -3),
];//*/


// Find the steps where the individual moons repeat
$simulation = new SpaceSimulation();
$startPoints = [];
$axis = ['x', 'y', 'z'];
foreach ($moons as $m => $moon) {
    $simulation->addSpaceObject($moon);
    $startPoints[] = clone $moon->position;
}
$steps = [];
$step = 0;
while (++$step) {
    $simulation->step();
    foreach ($axis as $a => $axel) {
        $isOriginal = true;
        foreach ($moons as $m => $moon) {
            if ($startPoints[$m]->$axel != $moon->position->$axel || $moon->velocity->$axel != 0) {
                $isOriginal = false;
                break;
            }
        }
        if ($isOriginal) {
            $steps[] = $step;
            unset($axis[$a]);
        }
    }
    if (!$axis) {
        echo implode(',', $steps) . "\n";
        break;
    }
}

echo 'Steps: ' . lowestCommonMultiple($steps);

// 10674186835800000000000
// 16502928646265000000000

$end = microtime(true);
echo "\nResult reached in " . round($end - $start, 2) . " seconds\n";


function lowestCommonMultiple(array $values)
{
    $values = array_unique($values);
    while (count($values) > 1) {
        $small = array_shift($values);
        $big = array_shift($values);
        if ($small > $big) {
            $tmp = $small;
            $small = $big;
            $big = $tmp;
        }
        $common = $big;
        $i = 1;
        while ($common % $small != 0) {
            $common = $big * ++$i;
        }
        array_unshift($values, $common);
        $values = array_unique($values);
    }
    return array_shift($values);
}

class Vector3
{
    /** @var int */
    public $x;

    /** @var int */
    public $y;

    /** @var int */
    public $z;

    public function __construct(int $x = 0, int $y = 0, int $z = 0)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    public function __toString()
    {
        return sprintf('%s,%s,%s', $this->x, $this->y, $this->z);
    }

}

class SpaceObject
{
    /** @var Vector3 */
    public $position;

    /** @var Vector3 */
    public $velocity;

    public function __construct(int $x, int $y, int $z)
    {
        $this->position = new Vector3($x, $y, $z);
        $this->velocity = new Vector3();
    }

    public function __toString()
    {
        return (string)$this->position . ':' . (string)$this->velocity;
    }

}

class SpaceSimulation
{
    /** @var SpaceObject[] */
    public $objects;

    public function __construct()
    {
        $this->objects = [];
    }

    public function addSpaceObject(SpaceObject $object)
    {
        $this->objects[] = $object;
    }

    public function step()
    {
        $this->applyGravity();
        $this->applyVelocity();
    }

    public function getTotalEnergy(): int
    {
        $energy = 0;
        foreach ($this->objects as $object) {
            $potential = abs($object->position->x) + abs($object->position->y) + abs($object->position->z);
            $kinetic = abs($object->velocity->x) + abs($object->velocity->y) + abs($object->velocity->z);
            $energy += $potential * $kinetic;
        }
        return $energy;
    }

    protected function applyGravity()
    {
        foreach ($this->objects as $object) {
            foreach ($this->objects as $object2) {
                if ($object == $object2) {
                    continue;
                }
                $object->velocity->x += $object->position->x > $object2->position->x ? -1 : ($object->position->x < $object2->position->x ? 1 : 0);
                $object->velocity->y += $object->position->y > $object2->position->y ? -1 : ($object->position->y < $object2->position->y ? 1 : 0);
                $object->velocity->z += $object->position->z > $object2->position->z ? -1 : ($object->position->z < $object2->position->z ? 1 : 0);
            }
        }
    }

    protected function applyVelocity()
    {
        foreach ($this->objects as $object) {
            $object->position->x += $object->velocity->x;
            $object->position->y += $object->velocity->y;
            $object->position->z += $object->velocity->z;
        }
    }

    public function __toString(): string
    {
        $result = '';
        foreach ($this->objects as $object) {
            $result .= (string)$object . '|';
        }
        return $result;
    }
}