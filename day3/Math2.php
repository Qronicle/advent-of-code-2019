<?php

class Point
{
    public $x;
    public $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
}

class Line
{
    const VERTICAL   = 'vertical';
    const HORIZONTAL = 'horizontal';

    const UP    = 'U';
    const DOWN  = 'D';
    const LEFT  = 'L';
    const RIGHT = 'R';

    public $p1;
    public $p2;
    public $orientation;
    public $direction;
    public $startDistance;

    public function __construct(Point $p1, Point $p2, string $direction, int $startDistance)
    {
        $this->p1 = $p1;
        $this->p2 = $p2;
        $this->orientation = $p1->x == $p2->x ? self::VERTICAL : self::HORIZONTAL;
        $this->direction = $direction;
        $this->startDistance = $startDistance;
    }

    public function getIntersection(Line $line)
    {
        // Not really true, but can be assumed by given examples
        if ($line->orientation == $this->orientation) {
            return null;
        }
        if ($this->orientation == self::HORIZONTAL) {
            if ($this->p1->y >= $line->getMinY() && $this->p1->y <= $line->getMaxY()
                && $line->p1->x >= $this->getMinX() && $line->p1->x <= $this->getMaxX()) {
                return new Point($line->p1->x, $this->p1->y);
            }
        }
        if ($this->orientation == self::VERTICAL) {
            if ($this->p1->x >= $line->getMinX() && $this->p1->x <= $line->getMaxX()
                && $line->p1->y >= $this->getMinY() && $line->p1->y <= $this->getMaxY()) {
                return new Point($this->p1->x, $line->p1->y);
            }
        }
    }

    public function getTotalDistanceAt(Point $intersection)
    {
        $extraDistance = 0;
        switch ($this->direction) {
            case self::LEFT:
                $extraDistance = $this->p1->x - $intersection->x;
                break;
            case self::RIGHT:
                $extraDistance = $intersection->x - $this->p1->x;
                break;
            case self::UP:
                $extraDistance = $intersection->y - $this->p1->y;
                break;
            case self::DOWN:
                $extraDistance = $this->p1->y - $intersection->y;
                break;
        }
        return $this->startDistance + $extraDistance;
    }

    public function getMaxX() {return max($this->p1->x, $this->p2->x);}
    public function getMinX() {return min($this->p1->x, $this->p2->x);}
    public function getMaxY() {return max($this->p1->y, $this->p2->y);}
    public function getMinY() {return min($this->p1->y, $this->p2->y);}
}