<?php

use IntCode\IntCode;

ini_set('display_errors', 1);
error_reporting(E_ALL);
$start = microtime(true);

require_once('../common/IntCode.php');
require_once('../common/image.php');

$code = file_get_contents('input.txt');

$game = new Game(new IntCode($code));
$game->run();
$game->renderScreen('output.png');

echo "Score: " . $game->getScore();

$end = microtime(true);
echo "\nResult reached in " . round($end - $start, 2) . " seconds\n";

class Game
{
    const TILE_VOID   = 0;
    const TILE_WALL   = 1;
    const TILE_BLOCK  = 2;
    const TILE_PADDLE = 3;
    const TILE_BALL   = 4;

    /** @var IntCode */
    protected $intCode;

    /** @var array[] */
    protected $screen;

    /** @var int */
    protected $step;

    /** @var int */
    protected $score;

    public function __construct(IntCode $intCode)
    {
        $this->intCode = $intCode;
        $this->intCode->setHaltOnOutput(true);
        $this->intCode->setMemoryValueAt(2, 0);
        $this->step = 0;
        $this->score = 0;
    }

    public function run()
    {
        do {
            for ($i = 0; $i < 3; $i++) {
                try {
                    $this->intCode->run();
                } catch (\IntCode\InputNecessaryException $ex) {
                    $this->renderScreen('test' . str_pad($this->step, 5, 0, STR_PAD_LEFT) . '.png');
                    $ballX = $this->getBallX();
                    $paddleX = $this->getPaddleX();
                    $input = $ballX > $paddleX ? 1 : ($ballX < $paddleX ? -1 : 0);
                    $this->intCode->setInput([$input]);
                    $this->intCode->run();
                }
                if (!$this->intCode->isRunning()) {
                    break;
                }
            }
            $output = $this->intCode->getOutput(false);
            $this->intCode->resetOutput();
            if (count($output) == 3) {
                $this->processOutput($output[0], $output[1], $output[2]);
            }
            $this->step++;
        } while ($this->intCode->isRunning());
    }

    public function processOutput(int $x, int $y, int $tile)
    {
        if ($x == -1 && $y == 0) {
            $this->score = $tile;
            return;
        }
        $this->screen[$y][$x] = $tile;
    }

    public function renderScreen(string $filename)
    {
        $pixels = '';
        foreach ($this->screen as $line) {
            $pixels .= implode('', $line) . "\n";
        }
        strtoimg($pixels, $filename, 1, [
            self::TILE_WALL   => [255, 255, 0], // Wall = yellow
            self::TILE_BLOCK  => [0, 0, 255], // Block = blue
            self::TILE_PADDLE => [255, 0, 0], // Paddle = red
            self::TILE_BALL   => [255, 255, 255], // Ball = white
        ]);
    }

    public function getNumTiles(int $tile): int
    {
        $amount = 0;
        foreach ($this->screen as $line) {
            foreach ($line as $t) {
                $amount += $t == $tile ? 1 : 0;
            }
        }
        return $amount;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getBallX(): int
    {
        foreach ($this->screen as $y => $line) {
            foreach ($line as $x => $tile) {
                if ($tile == self::TILE_BALL) {
                    return $x;
                }
            }
        }
        return 0;
    }

    public function getPaddleX(): int
    {
        foreach ($this->screen as $y => $line) {
            foreach ($line as $x => $tile) {
                if ($tile == self::TILE_PADDLE) {
                    return $x;
                }
            }
        }
        return 0;
    }
}