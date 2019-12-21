<?php

use IntCode\IntCode;

class ASCII
{
    const TILE_SPACE          = '.';
    const TILE_SCAFFOLDING    = '#';
    const TILE_ROBOT_UP       = '^';
    const TILE_ROBOT_RIGHT    = '>';
    const TILE_ROBOT_DOWN     = 'v';
    const TILE_ROBOT_LEFT     = '<';
    const TILE_ROBOT_TUMBLING = 'X';

    /** @var Room */
    protected $view;

    /** @var IntCode */
    protected $intCode;

    /** @var int[] */
    protected $position;
    /** @var int[] */
    protected $direction;

    public function __construct(IntCode $intCode)
    {
        $this->intCode = $intCode;
    }

    public function reportDamage($mode = 'WALK')
    {
        $this->runAscii();
        switch ($mode) {
            case 'WALK':
                $spring = [
                    'NOT A T',
                    'OR T J',
                    'NOT B T',
                    'OR T J',
                    'NOT C T',
                    'OR T J',
                    'AND D J',
                ];
            case 'RUN':
                $spring = [
                    'NOT E J',
                    'AND B J',
                    'AND C J',
                    'NOT J J',
                    'AND D J',
                    'OR E T',
                    'OR H T',
                    'AND T J',
                ];
        }
        $this->intCode->setInput($this->linesToAsciiInput($spring));
        $this->runAscii();
        $this->intCode->setInput($this->linesToAsciiInput([$mode]));
        $this->runAscii();
    }

    protected function runAscii()
    {
        try {
            $this->intCode->run();
        } catch (\IntCode\InputNecessaryException $ex) {
            $this->printOutput();
            return;
        }
        $this->printOutput();
    }

    protected function printOutput()
    {
        $output = $this->intCode->getOutput(false);
        if (count($output) > 1) {
            foreach ($output as $ord) {
                if ($ord < 256) {
                    echo chr($ord);
                } else {
                    echo $ord;
                }
            }
            $this->intCode->resetOutput();
        }
    }

    protected function linesToAsciiInput(array $lines):array
    {
        $input = [];
        foreach ($lines as $line) {
            $line = str_split(trim($line));
            foreach ($line as $c => $char) {
                $input[] = ord($char);
            }
            $input[] = 10;
        }
        return $input;
    }
}