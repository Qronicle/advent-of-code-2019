<?php

require_once('../common/common.php');

$input = file_get_contents('input.txt');
/*/
$input = 'deal into new stack
cut -2
deal with increment 7
cut 8
cut -4
deal with increment 7
cut 3
deal with increment 9
deal with increment 3
cut -1';
//*//*

           0123456789
new stack: 9876543210
cut 2:     8901234567

*/
$shuffler = new Shuffler(10, 2);
echo $shuffler->shuffle(explode("\n", $input));

timer_end();

class Shuffler
{
    /** @var int */
    protected $numCards;

    protected $followIndex;

    protected $reverseInstructions;

    public function __construct(int $numCards, int $followIndex)
    {
        $this->cards = [0 => ['length' => $numCards, 'final' => $numCards - 1, 'order' => 'inc']];
        $this->numCards = $numCards;
        $this->followIndex = $followIndex;
    }

    public function shuffle(array $instructions, int $repetitions = 1): int
    {
        $this->reverseInstructions = array_reverse($instructions);

        /*
        X = 2020
        Y = f(X)
        Z = f(Y)
        A = (Y-Z) * modinv(X-Y+D, D) % D
        B = (Y-A*X) % D
        print(A, B)
        */

        $n = $repetitions;
        $d = $this->numCards;
        $x = $this->followIndex;
        $y = $this->leShuffle($x);
        $z = $this->leShuffle($y);
        $a = ($y - $z) * modinv($x - $y + $d, $d) % $d;
        $b = ($y - $a * $x) % $d;

        $result = (
                ((($a ** $n) % $d) * $x) +
                ((($a ** $n) % $d) - 1) * modinv($a - 1, $d) * $b
            ) % $d;

        return $result;
    }

    public function leShuffle($index): int
    {
        foreach ($this->reverseInstructions as $instruction) {
            $command = explode(' ', $instruction);
            $amount = array_pop($command);
            $command = implode(' ', $command);
            switch ($command) {
                case 'deal with increment':
                    $index = $this->dealWithIncrement($index, $amount);
                    break;
                case 'deal into new':
                    $index = $this->dealIntoNewStack($index);
                    break;
                case 'cut':
                    $index = $this->cut($index, $amount);
                    break;
            }
        }
        return $index;
    }

    public function dealWithIncrement(int $index, int $increment)
    {
        return modinv($increment, $index) * $index % $this->numCards;
    }

    public function dealIntoNewStack(int $index)
    {
        return ($this->numCards - 1) - $index;
    }

    public function cut(int $index, int $amount)
    {
        return ($index + $amount + $this->numCards) % $this->numCards;
    }

    public function getFollowedCardValue()
    {
        return $this->followIndex;
    }
}

function modinv($a, $n)
{
    if ($n < 0) $n = -$n;
    if ($a < 0) $a = $n - (-$a % $n);
    $t = 0;
    $nt = 1;
    $r = $n;
    $nr = $a % $n;
    while ($nr != 0) {
        $quot = intval($r / $nr);
        $tmp = $nt;
        $nt = $t - $quot * $nt;
        $t = $tmp;
        $tmp = $nr;
        $nr = $r - $quot * $nr;
        $r = $tmp;
    }
    if ($r > 1) return -1;
    if ($t < 0) $t += $n;
    return $t;
}