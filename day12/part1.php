<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
$start = microtime(true);

$moons = [
    'io'       => new SpaceObject(17, -12, 13),
    'europa'   => new SpaceObject(2, 1, 1),
    'ganymede' => new SpaceObject(-1, -17, 7),
    'callisto' => new SpaceObject(12, -14, 18),
];

$simulation = new SpaceSimulation();
foreach ($moons as $moon) {
    $simulation->addSpaceObject($moon);
}

for ($step = 0; $step < 1000; $step++) {
    $simulation->step();
}
echo $simulation;


$end = microtime(true);
echo "\nResult reached in " . round($end - $start, 2) . " seconds\n";

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
        return sprintf('V3(%s, %s, %s)', $this->x, $this->y, $this->z);
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
        return 'P: ' . (string)$this->position . ', V: ' . (string)$this->velocity;
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

    public function __toString()
    {
        $result = '';
        foreach ($this->objects as $object) {
            $result .= (string)$object . "\n";
        }
        $result .= 'Energy: ' . $this->getTotalEnergy() . "\n";
        return $result;
    }
}