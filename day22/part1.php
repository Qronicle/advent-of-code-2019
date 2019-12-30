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
//*/

$shuffler = new Shuffler(10007);
$shuffler->shuffle(explode("\n", $input));
echo $shuffler->getPosition(2019);

timer_end();

class Shuffler
{
    /** @var int[] */
    protected $cards;

    /** @var int */
    protected $numCards;

    public function __construct(int $numCards)
    {
        $this->cards = [];
        for ($i = 0; $i < $numCards; $i++) {
            $this->cards[] = $i;
        }
        $this->numCards = $numCards;
    }

    public function shuffle(array $instructions)
    {
        foreach ($instructions as $instruction) {
            $command = explode(' ', $instruction);
            $amount = array_pop($command);
            $command = implode(' ', $command);
            switch ($command) {
                case 'deal with increment':
                    $this->dealWithIncrement($amount);
                    break;
                case 'deal into new':
                    $this->dealIntoNewStack();
                    break;
                case 'cut':
                    $this->cut($amount);
                    break;
            }
        }
    }

    public function dealWithIncrement(int $increment)
    {
        $newCards = $this->cards;
        for ($i = 0; $i < $this->numCards; $i++) {
            $newCards[($i * $increment) % $this->numCards] = $this->cards[$i];
        }
        $this->cards = $newCards;
    }

    public function dealIntoNewStack()
    {
        $this->cards = array_reverse($this->cards);
    }

    public function cut($amount)
    {
        if ($amount > 0) {
            $out = array_splice($this->cards, 0, $amount);
            $this->cards = array_merge($this->cards, $out);
        } else {
            $out = array_splice($this->cards, $amount);
            $this->cards = array_merge($out, $this->cards);
        }
    }

    public function print($card)
    {
        echo implode(' ', $this->cards);
    }

    public function getPosition($card)
    {
        return array_search($card, $this->cards);
    }
}