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

    public $p1;
    public $p2;
    public $dir;

    public function __construct(Point $p1, Point $p2)
    {
        $this->p1 = $p1;
        $this->p2 = $p2;
        $this->dir = $p1->x == $p2->x ? self::VERTICAL : self::HORIZONTAL;
    }

    public function getIntersection(Line $line)
    {
        // Not really true, but can be assumed by given examples
        if ($line->dir == $this->dir) {
            return null;
        }
        if ($this->dir == self::HORIZONTAL) {
            if ($this->p1->y >= $line->getMinY() && $this->p1->y <= $line->getMaxY()
                && $line->p1->x >= $this->getMinX() && $line->p1->x <= $this->getMaxX()) {
                return new Point($line->p1->x, $this->p1->y);
            }
        }
        if ($this->dir == self::VERTICAL) {
            if ($this->p1->x >= $line->getMinX() && $this->p1->x <= $line->getMaxX()
                && $line->p1->y >= $this->getMinY() && $line->p1->y <= $this->getMaxY()) {
                return new Point($this->p1->x, $line->p1->y);
            }
        }
    }

    public function getMaxX() {return max($this->p1->x, $this->p2->x);}
    public function getMinX() {return min($this->p1->x, $this->p2->x);}
    public function getMaxY() {return max($this->p1->y, $this->p2->y);}
    public function getMinY() {return min($this->p1->y, $this->p2->y);}
}