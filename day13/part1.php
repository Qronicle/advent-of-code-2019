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

echo "Number block tiles: " . $game->getNumTiles(Game::TILE_BLOCK);

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

    public function __construct(IntCode $intCode)
    {
        $this->intCode = $intCode;
        $this->intCode->setHaltOnOutput(true);
        $this->step = 0;
    }

    public function run()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->intCode->run();
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
        /*if ($this->step % 100 == 0) {
            $this->renderScreen('out' . $this->step . '.png');
        }*/
        if ($this->intCode->isRunning()) {
            $this->run();
        }
    }

    public function processOutput(int $x, int $y, int $tile)
    {
        $this->screen[$y][$x] = $tile;
    }

    public function renderScreen(string $filename)
    {
        $pixels = '';
        foreach ($this->screen as $line) {
            $pixels .= implode('', $line) . "\n";
        }
        strtoimg($pixels, $filename, 10, [
            1 => [255, 255, 0], // Wall = yellow
            2 => [0, 0, 255], // Block = blue
            3 => [255, 0, 0], // Paddle = red
            4 => [255, 255, 255], // Ball = white
        ]);
    }

    public function getNumTiles(int $tile)
    {
        $amount = 0;
        foreach ($this->screen as $line) {
            foreach ($line as $t) {
                $amount += $t == $tile ? 1 : 0;
            }
        }
        return $amount;
    }
}