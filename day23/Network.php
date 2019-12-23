<?php

use IntCode\IntCode;

class Network
{
    /** @var IntCode[] */
    protected $computers;

    /** @var array[] */
    protected $inputQueue;

    /** @var int[] */
    protected $natInput = [];

    /** @var int[] */
    protected $idleMonitor = [];

    /** @var bool */
    protected $isIdle = false;

    public function __construct(string $networkCode, int $numComputers)
    {
        $this->inputQueue = [];
        for ($address = 0; $address < $numComputers; $address++){
            $this->computers[$address] = new IntCode($networkCode, [$address]);
        }
    }

    public function runWithNat()
    {
        $natY = null;
        while (true) {
            $this->tick();
            if (isset($this->inputQueue[255])) {
                $this->natInput = array_slice($this->inputQueue[255], -2);
                unset($this->inputQueue[255]);
            }
            if ($this->isIdle) {
                print_r($this->natInput);
                $this->inputQueue[0] = $this->natInput;
                $this->isIdle = false;
                $this->idleMonitor = [];
                if ($natY === $this->natInput[1]) {
                    break;
                }
                $natY = $this->natInput[1];
            }
        }
        echo "\n------\n";
        return $natY;
    }

    public function runUntilOutputOnAddress(int $address)
    {
        while (!isset($this->inputQueue[$address])) {
            $this->tick();
        }
        return $this->inputQueue[$address];
    }

    public function tick()
    {
        foreach ($this->computers as $address => $computer) {
            try {
                $computer->tick();
            } catch (\IntCode\InputNecessaryException $ex) {
                $computer->setInput([-1]);
                $computer->tick();
                $this->idleMonitor[$address] = ($this->idleMonitor[$address] ?? 0) + 1;
                if (count($this->idleMonitor) == count($this->computers) && min($this->idleMonitor) > 1) {
                    $this->isIdle = true;
                }
            }
            if (!$computer->isRunning()) {
                throw new Exception("Computer $address stopped running");
            }
            $output = $computer->getOutput(false);
            if (count($output) == 3) {
                $this->inputQueue[$output[0]][] = $output[1];
                $this->inputQueue[$output[0]][] = $output[2];
                $computer->resetOutput();
                $this->idleMonitor = [];
                $this->isIdle = false;
            }
        }
        foreach ($this->inputQueue as $address => $input) {
            if (isset($this->computers[$address])) {
                $this->computers[$address]->addInput($input);
                unset($this->inputQueue[$address]);
            }
        }
    }
}