<?php

namespace IntCode;

use Exception;
use IntCode\Operation\Operation;

class InputNecessaryException extends Exception {}

class IntCode
{
    /**
     * Used for reset
     * @var string
     */
    protected $code;

    /**
     * @var int[]
     */
    protected $memory;

    /**
     * @var int[]
     */
    protected $input;

    /**
     * @var int[]
     */
    protected $output;

    /**
     * @var bool
     */
    protected $hasHaltOutput = false;

    /**
     * @var bool
     */
    protected $haltOnOutput = false;

    /**
     * @var int
     */
    protected $relativeBase = 0;

    protected $operations = [
        1  => 'Addition',
        2  => 'Multiplication',
        3  => 'Input',
        4  => 'Output',
        5  => 'JumpWhenTrue',
        6  => 'JumpWhenFalse',
        7  => 'LessThan',
        8  => 'Equals',
        9  => 'AdjustRelativeBase',
        99 => 'End',
    ];

    /**
     * The current address
     *
     * @var int
     */
    protected $address;

    /**
     * Whether the application is running
     *
     * @var bool
     */
    protected $running = false;

    public function __construct(string $code, $input = null)
    {
        $this->init($code, $input);
    }

    public function init(string $code, $input = null)
    {
        $this->code = $code;
        $this->memory = explode(',', $code);
        $this->input = (array)$input;
        $this->output = null;
        $this->address = 0;
        $this->running = false;
        $this->relativeBase = 0;
    }

    public function reset($input = null): IntCode
    {
        $this->init($this->code, $input);
        return $this;
    }

    /**
     * Run the int-code instructions!
     *
     * @throws Exception
     * @throws InputNecessaryException
     */
    public function run(int $ticks = -1): IntCode
    {
        $this->running = true;
        $hasOutput = false;
        $tick = 0;

        // Do as long as the operation doesn't halt the programme
        do {
            $tick++;

            // Prepare the operation & arguments
            $opCode = $this->getMemoryValueAt($this->address);
            $operation = $this->getOperation($opCode);
            $arguments = $this->getArguments($operation, $opCode, $this->address);

            // Run the operation
            $operation->execute($arguments);
            //echo $this->address . ': ' . get_class($operation) . "\n";

            // Move the address pointer
            $this->address = $operation->getNextAddress($this->address);

            // Add output
            if (!is_null($operation->getOutput())) {
                $this->output[] = $operation->getOutput();
                //echo "output: " . $operation->getOutput() . "\n";
                $hasOutput = true;
                if ($this->haltOnOutput) {
                    $this->hasHaltOutput = true;
                    return $this;
                }
            } elseif (!$operation->halt()) {
                $hasOutput = false;
            }
        } while (!$operation->halt() && ($ticks < 0 || $tick < $ticks));

        if ($operation->halt()) {
            if ($hasOutput) {
                $this->hasHaltOutput = true;
            }
            $this->running = false;
        }

        return $this;
    }

    public function tick()
    {
        return $this->run(1);
    }

    public function serialize(): string
    {
        return json_encode([
            'relativeBase' => $this->relativeBase,
            'address'      => $this->address,
            'input'        => $this->input,
            'output'       => $this->output,
            'memory'       => $this->memory,
        ]);
    }

    public function unserialize(string $string)
    {
        $data = json_decode($string);
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function setInput(array $input)
    {
        $this->input = $input;
    }

    public function addInput(array $input)
    {
        foreach ($input as $val) {
            $this->input[] = $val;
        }
    }

    public function setHaltOnOutput(bool $halt = true)
    {
        $this->haltOnOutput = $halt;
    }

    public function getHaltOnOutput(): bool
    {
        return $this->haltOnOutput;
    }

    public function getRelativeBase(): int
    {
        return $this->relativeBase;
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * @param int $opCode
     * @return Operation
     * @throws Exception
     */
    protected function getOperation(int $opCode): Operation
    {
        $opCode = (int)substr($opCode, -2);
        if (!isset($this->operations[$opCode])) {
            throw new Exception('Unknown OP code encountered: ' . $opCode);
        }
        if (!$this->operations[$opCode] instanceof Operation) {
            $operationClass = '\\IntCode\\Operation\\' . $this->operations[$opCode];
            $operation = new $operationClass($this);
            $this->operations[$opCode] = $operation;
        }
        return $this->operations[$opCode];
    }

    /**
     * @param Operation $operation
     * @param int       $opCode
     * @param int       $opCodeAddress
     * @return Argument[]
     * @throws Exception
     */
    protected function getArguments(Operation $operation, int $opCode, int $opCodeAddress): array
    {
        $argumentTypes = str_split(str_pad(substr(strrev((string)$opCode), 2), $operation->getNumArguments(), '0'));
        $arguments = [];
        for ($i = 0; $i < $operation->getNumArguments(); $i++) {
            $argument = new Argument();
            $argument->value = $this->getMemoryValueAt($opCodeAddress + 1 + $i);
            $argument->mode = $argumentTypes[$i];
            $arguments[] = $argument;
        }
        return $arguments;
    }

    /**
     * @param int $value
     * @param int $address
     * @throws Exception
     */
    public function setMemoryValueAt(int $value, int $address)
    {
        if ($address < 0) {
            throw new Exception('Requested address out of bounds (for write)');
        }
        if (!isset($this->memory[$address])) {
            $this->fillMemoryToAddress($address);
        }
        $this->memory[$address] = $value;
    }

    /**
     * @param int $address
     * @return int
     * @throws Exception
     */
    public function getMemoryValueAt(int $address): int
    {
        if ($address < 0) {
            throw new Exception('Requested address out of bounds');
        }
        if (!isset($this->memory[$address])) {
            $this->fillMemoryToAddress($address);
        }
        return $this->memory[$address];
    }

    protected function fillMemoryToAddress(int $address)
    {
        for ($i = count($this->memory); $i <= $address; $i++) {
            $this->memory[$i] = 0;
        }
    }

    public function increaseRelativeBase(int $increment)
    {
        $this->relativeBase += $increment;
    }

    /**
     * Get next input value
     *
     * @return int
     * @throws Exception
     */
    public function getInput(): int
    {
        if (!count($this->input)) {
            throw new InputNecessaryException('No input left');
        }
        return array_shift($this->input);
    }

    public function getMemory($asString = false)
    {
        return $asString ? implode(',', $this->memory) : $this->memory;
    }

    public function getOutput($onHalt = true)
    {
        if ($onHalt) {
            if ($this->hasHaltOutput) {
                return end($this->output);
            } else {
                //echo "no output";
                return null;
            }
        }
        return $this->output;
    }

    public function resetOutput()
    {
        $this->output = [];
    }
}

class Argument
{
    const MODE_POSITION  = 0;
    const MODE_IMMEDIATE = 1;
    const MODE_RELATIVE  = 2;

    public $value;
    public $mode;
}

namespace IntCode\Operation;

use Exception;
use IntCode\Argument;
use IntCode\IntCode;

abstract class Operation
{
    protected $intCode;

    protected $numArguments = 0;

    protected $output = null;

    public function __construct(IntCode $intCode)
    {
        $this->intCode = $intCode;
    }

    /**
     * The amount of arguments required by this operation
     *
     * @return int
     */
    public function getNumArguments()
    {
        return $this->numArguments;
    }

    /**
     * Get the integer value for an argument
     *
     * Uses the argument mode to determine which value to select
     *
     * @param Argument $argument
     * @return int
     * @throws Exception
     */
    public function getArgumentValue(Argument $argument): int
    {
        switch ($argument->mode) {
            case Argument::MODE_IMMEDIATE:
                return $argument->value;
            case Argument::MODE_POSITION:
                return $this->intCode->getMemoryValueAt($argument->value);
            case Argument::MODE_RELATIVE:
                return $this->intCode->getMemoryValueAt($this->intCode->getRelativeBase() + $argument->value);
            default:
                throw new Exception('Invalid Argument Mode set: ' . $argument->mode);
        }
    }

    /**
     * Set a memory value based on the address given by an argument
     *
     * Will check whether the argument has the correct mode (position)
     *
     * @param int      $value
     * @param Argument $argument
     * @throws Exception
     */
    public function setMemoryValueForArgument(int $value, Argument $argument)
    {
        switch ($argument->mode) {
            case Argument::MODE_IMMEDIATE:
                throw new Exception('Parameters that an instruction writes to can never be in immediate mode');
            case Argument::MODE_POSITION:
                $this->intCode->setMemoryValueAt($value, $argument->value);
                break;
            case Argument::MODE_RELATIVE:
                $this->intCode->setMemoryValueAt($value, $this->intCode->getRelativeBase() + $argument->value);
                break;
            default:
                throw new Exception('Invalid Argument Mode set: ' . $argument->mode);
        }
    }

    /**
     * The address to go to after this instruction
     *
     * @param int $currentAddress
     * @return int
     */
    public function getNextAddress(int $currentAddress): int
    {
        return $currentAddress + $this->getNumArguments() + 1;
    }

    /**
     * The output of this operation
     *
     * @return int|null
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Whether the program should be halted after this operation
     *
     * @return bool
     */
    public function halt()
    {
        return false;
    }

    /**
     * The actual operation logic will go here
     *
     * @param Argument[] $args
     * @return void
     */
    public abstract function execute(array $args = []);
}

class Addition extends Operation
{
    protected $numArguments = 3;

    /**
     * @param Argument[] $args
     * @throws Exception
     */
    public function execute(array $args = [])
    {
        $sum = $this->getArgumentValue($args[0]) + $this->getArgumentValue($args[1]);
        $this->setMemoryValueForArgument($sum, $args[2]);
    }

}

class Multiplication extends Operation
{
    protected $numArguments = 3;

    /**
     * @param Argument[] $args
     * @throws Exception
     */
    public function execute(array $args = [])
    {
        $result = $this->getArgumentValue($args[0]) * $this->getArgumentValue($args[1]);
        $this->setMemoryValueForArgument($result, $args[2]);
    }
}

class Input extends Operation
{
    protected $numArguments = 1;

    /**
     * @param Argument[] $args
     * @throws Exception
     */
    public function execute(array $args = [])
    {
        $this->setMemoryValueForArgument($this->intCode->getInput(), $args[0]);
    }
}

class Output extends Operation
{
    protected $numArguments = 1;

    /**
     * @param Argument[] $args
     * @throws Exception
     */
    public function execute(array $args = [])
    {
        $this->output = $this->getArgumentValue($args[0]);
    }
}

class JumpWhenTrue extends Operation
{
    protected $numArguments = 2;

    protected $jump = false;

    /**
     * @param Argument[] $args
     * @throws Exception
     */
    public function execute(array $args = [])
    {
        if ($this->getArgumentValue($args[0]) != 0) {
            $this->jump = $this->getArgumentValue($args[1]);
        } else {
            $this->jump = false;
        }
    }

    public function getNextAddress(int $currentAddress): int
    {
        if ($this->jump !== false) {
            return $this->jump;
        }
        return parent::getNextAddress($currentAddress);
    }
}

class JumpWhenFalse extends JumpWhenTrue
{
    /**
     * @param Argument[] $args
     * @throws Exception
     */
    public function execute(array $args = [])
    {
        if ($this->getArgumentValue($args[0]) == 0) {
            $this->jump = $this->getArgumentValue($args[1]);
        } else {
            $this->jump = false;
        }
    }
}

class LessThan extends Operation
{
    protected $numArguments = 3;

    /**
     * @param Argument[] $args
     * @throws Exception
     */
    public function execute(array $args = [])
    {
        $value = $this->getArgumentValue($args[0]) < $this->getArgumentValue($args[1]) ? 1 : 0;
        $this->setMemoryValueForArgument($value, $args[2]);
    }
}

class Equals extends Operation
{
    protected $numArguments = 3;

    /**
     * @param Argument[] $args
     * @throws Exception
     */
    public function execute(array $args = [])
    {
        $value = $this->getArgumentValue($args[0]) == $this->getArgumentValue($args[1]) ? 1 : 0;
        $this->setMemoryValueForArgument($value, $args[2]);
    }
}

class AdjustRelativeBase extends Operation
{
    protected $numArguments = 1;

    /**
     * @param Argument[] $args
     * @throws Exception
     */
    public function execute(array $args = [])
    {
        $value = $this->getArgumentValue($args[0]);
        $this->intCode->increaseRelativeBase($value);
    }
}

class End extends Operation
{
    public function execute(array $args = [])
    {
        // Do nothing
    }

    public function halt()
    {
        return true;
    }
}