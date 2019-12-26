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

    public function run()
    {
        $this->runAscii();
        while ($this->intCode->isRunning()) {
            $input = readline();
            $this->processInput($input);
            $this->runAscii();
        }
    }

    public function resolvePressurePlate($direction, $items)
    {
        // Drop all items
        foreach ($items as $item) {
            $this->fakeInput('drop ' . $item);
        }
        $this->resolvePressurePlateRecursive($direction, $items);
    }

    protected function resolvePressurePlateRecursive(string $direction, array $items)
    {
        foreach ($items as $i => $item) {
            $this->fakeInput("take $item");
            $output = $this->fakeInput($direction);
            unset($items[$i]);

            // When we're too heavy
            if (strpos($output, 'lighter than') !== false) {
                $this->fakeInput("drop $item");
                continue;
            } elseif (strpos($output, 'heavier than') !== false) {
                // That's okay
            } else {
                die;
            }


            $this->resolvePressurePlateRecursive($direction, $items);
            $this->fakeInput("drop $item");
        }
    }

    public function loadAndRun(string $filename)
    {
        $this->loadState($filename);
        $this->run();
    }

    protected function fakeInput(string $input, int $sleep = 0): string
    {
        echo "$input\n";
        $this->processInput($input);
        $output = $this->runAscii();
        sleep($sleep);
        return $output;
    }

    protected function processInput(string $input)
    {
        switch ($input) {
            case 'save':
                $this->saveState();
                break;
            case 'load':
                $this->loadState();
                break;
            default:
                $asciiInput = $this->linesToAsciiInput([$input]);
                $this->intCode->setInput($asciiInput);
        }
    }

    public function saveState()
    {
        $state = $this->intCode->serialize();
        file_put_contents('save_states/input.txt', $state);
    }

    public function loadState($filename = null)
    {
        $filename = $filename ?: 'input.txt';
        $state = file_get_contents('save_states/' . $filename);
        $this->intCode->unserialize($state);
    }

    protected function runAscii(): string
    {
        try {
            $this->intCode->run();
        } catch (\IntCode\InputNecessaryException $ex) {
            return $this->printOutput();
        }
        return $this->printOutput();
    }

    protected function printOutput(): string
    {
        $output = $this->intCode->getOutput(false);
        $string = '';
        if (count($output) > 1) {
            foreach ($output as $ord) {
                if ($ord < 256) {
                    $string .= chr($ord);
                    echo chr($ord);
                } else {
                    echo $ord;
                }
            }
            $this->intCode->resetOutput();
        }
        return $string;
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