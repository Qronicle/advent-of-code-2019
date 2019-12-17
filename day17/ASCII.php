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

    public function run(): int
    {
        // Create the view
        $this->calibrate();

        // Calculate the necessary commands (path from start to end)
        $commands = [];
        do {
            $newDirection = null;
            foreach ($this->getOtherDirections() as $turn => $direction) {
                $x = $this->position[0] + $direction[0];
                $y = $this->position[1] + $direction[1];
                $tile = $this->view[$y][$x] ?? self::TILE_SPACE;
                if ($tile == self::TILE_SCAFFOLDING) {
                    $newDirection = $direction;
                    $commands[] = $turn;
                    break;
                }
            }
            if (!$newDirection) {
                break;
            }
            $this->direction = $newDirection;
            $newPosition = $this->position;
            $steps = -1;
            do {
                $steps++;
                $this->position = $newPosition;
                $newPosition[0] += $newDirection[0];
                $newPosition[1] += $newDirection[1];
            } while (($this->view[$newPosition[1]][$newPosition[0]] ?? self::TILE_SPACE) == self::TILE_SCAFFOLDING);
            $commands[] = $steps;
        } while (true);

        // Calculate the largest recurring command lists
        $recurringCommands = [];
        $commandString = ',' . implode(',', $commands) . ',';
        for ($i = 0; $i < count($commands) - 1; $i++) {
            for ($j = $i + 1; $j < count($commands); $j++) {
                $searchRepetition = ',' . implode(',', array_slice($commands, $i, $j - $i + 1)) . ',';
                if (strlen($searchRepetition) > 22) {
                    break;
                }
                if (strpos($commandString, $searchRepetition, $j + 2) !== false) {
                    $recurringCommands[$searchRepetition] = strlen($searchRepetition);
                }
            }
        }
        arsort($recurringCommands);

        // Make a set to describe the whole thing
        $functionCommands = [];
        foreach ($recurringCommands as $recurringCommand1 => $l1) {
            $commandPieces = explode('#', str_replace([$recurringCommand1, $recurringCommand1], ',#,', $commandString));
            foreach ($recurringCommands as $recurringCommand2 => $l2) {
                if ($recurringCommand1 == $recurringCommand2) continue;
                $commandPieces2 = [];
                $hasDivided = false;
                foreach ($commandPieces as $commandPiece) {
                    $commandPiecesIn2 = explode('#', str_replace([$recurringCommand2, $recurringCommand2], ',#,', $commandPiece));
                    if (count($commandPiecesIn2) > 1) {
                        $hasDivided = true;
                    }
                    $commandPieces2 = array_merge($commandPieces2, $commandPiecesIn2);
                }
                if ($hasDivided) {
                    foreach ($recurringCommands as $recurringCommand3 => $l3) {
                        if ($recurringCommand3 == $recurringCommand1) continue;
                        if ($recurringCommand3 == $recurringCommand2) continue;
                        $commandPieces3 = [];
                        $hasDivided = false;
                        foreach ($commandPieces2 as $commandPiece2) {
                            $commandPiecesIn3 = explode('#', str_replace([$recurringCommand3, $recurringCommand3], ',#,', $commandPiece2));
                            if (count($commandPiecesIn3) > 1) {
                                $hasDivided = true;
                            }
                            $commandPieces3 = array_merge($commandPieces3, $commandPiecesIn3);
                        }
                        if ($hasDivided) {
                            if (count(array_unique($commandPieces3)) < 2 && reset($commandPieces3) == ',') {
                                $functionCommands = [
                                    'A' => trim($recurringCommand1, ','),
                                    'B' => trim($recurringCommand2, ','),
                                    'C' => trim($recurringCommand3, ','),
                                ];
                                break 3;
                            }
                        }
                    }
                }
            }
        }
        if (!$functionCommands) {
            throw new Exception("It can't be done");
        }

        // Create the real command strings
        $input = [];
        $routeString = implode(',', $commands) . ',';
        do {
            foreach ($functionCommands as $functionName => $route) {
                if (strpos($routeString, $route . ',') === 0) {
                    $input[] = ord($functionName);
                    $routeString = substr($routeString, strlen($route) + 1);
                    if ($routeString) {
                        $input[] = ord(',');
                    }
                    break;
                }
            }
        } while ($routeString);
        foreach ($functionCommands as $route) {
            $input[] = 10;
            for ($r = 0; $r < strlen($route); $r++) {
                $input[] = ord($route[$r]);
            }
        }
        $input[] = 10;
        $input[] = ord('n');
        $input[] = 10;

        // Run the intcode in mode 2 with the input
        $this->intCode->reset($input);
        $this->intCode->setMemoryValueAt(2, 0);
        $this->intCode->run();
        return $this->intCode->getOutput();
    }

    protected function getOtherDirections()
    {
        if ($this->direction[0] == 0) {
            return [
                $this->direction[1] > 0 ? 'L' : 'R' => [1, 0],
                $this->direction[1] < 0 ? 'L' : 'R' => [-1, 0],
            ];
        } else {
            return [
                $this->direction[0] < 0 ? 'L' : 'R' => [0, 1],
                $this->direction[0] > 0 ? 'L' : 'R' => [0, -1],
            ];
        }
    }

    public function calibrate()
    {
        $this->intCode->run();
        $output = $this->intCode->getOutput(false);
        $this->view = [];
        $y = 0;
        $x = 0;
        $render = '';
        foreach ($output as $charCode) {
            $char = chr($charCode);
            if ($charCode == 10) {
                $y++;
                $x = 0;
                $render .= "\n";
                continue;
            }
            switch ($char) {
                case self::TILE_ROBOT_TUMBLING :
                    $this->position = [$x, $y];
                case self::TILE_SPACE:
                    $this->view[$y][$x] = self::TILE_SPACE;
                    $render .= 0;
                    break;
                case self::TILE_ROBOT_UP:
                case self::TILE_ROBOT_RIGHT:
                case self::TILE_ROBOT_DOWN:
                case self::TILE_ROBOT_LEFT:
                    $this->position = [$x, $y];
                    switch ($char) {
                        case self::TILE_ROBOT_UP:
                            $this->direction = [0, -1];
                            break;
                        case self::TILE_ROBOT_RIGHT:
                            $this->direction = [1, 0];
                            break;
                        case self::TILE_ROBOT_DOWN:
                            $this->direction = [0, 1];
                            break;
                        case self::TILE_ROBOT_LEFT:
                            $this->direction = [-1, 0];
                            break;
                    }
                case self::TILE_SCAFFOLDING:
                    $this->view[$y][$x] = self::TILE_SCAFFOLDING;
                    $render .= 9;
                    break;
            }
            $x++;
        }
        //strtoimg($render, 'scaffolding.png', 1);
        $alignmentSum = 0;
        for ($y = 1; $y < count($this->view) - 1; $y++) {
            for ($x = 1; $x < count($this->view[$y]) - 1; $x++) {
                if (
                    $this->view[$y][$x] == self::TILE_SCAFFOLDING &&
                    $this->view[$y - 1][$x] == self::TILE_SCAFFOLDING &&
                    $this->view[$y + 1][$x] == self::TILE_SCAFFOLDING &&
                    $this->view[$y][$x + 1] == self::TILE_SCAFFOLDING &&
                    $this->view[$y][$x - 1] == self::TILE_SCAFFOLDING
                ) {
                    $alignmentSum += $x * $y;
                }
            }
        }
        return $alignmentSum;
    }
}