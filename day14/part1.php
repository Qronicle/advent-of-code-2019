<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
$start = microtime(true);

// Parse input to reaction formulas array
$input = file_get_contents('input.txt');
$formulas = stringToFormulas($input);

// Let a NanoFactory create some fuel!
$nanoFactory = new NanoFactory($formulas);
$nanoFactory->createChemical('FUEL');

echo 'Ore needed: ' . $nanoFactory->getNecessaryOre();

$end = microtime(true);
echo "\nResult reached in " . round($end - $start, 2) . " seconds\n";

########################################################################################################################

class NanoFactory
{
    /** @var array [chemical => amount] */
    protected $availableChemicals = [];

    /** @var array */
    protected $reactionFormulas;

    /** @var int */
    protected $ore = 0;

    public function __construct(array $reactionFormulas)
    {
        $this->reactionFormulas = $reactionFormulas;
    }

    public function createChemical($chemical, $amount = 1)
    {
        $this->availableChemicals[$chemical] = $this->availableChemicals[$chemical] ?? 0;
        if ($this->availableChemicals[$chemical] >= $amount) {
            return true;
        }
        $amountNeeded = $amount - $this->availableChemicals[$chemical];
        $reaction = $this->reactionFormulas[$chemical];
        $numReactionsNeeded = ceil($amountNeeded/$reaction['amount']);
        foreach ($reaction['chemicals'] as $inputChemical => $inputAmount) {
            $inputAmount *= $numReactionsNeeded;
            if ($inputChemical == 'ORE') {
                $this->ore += $inputAmount;
                continue;
            }
            $this->createChemical($inputChemical, $inputAmount);
            $this->availableChemicals[$inputChemical] -= $inputAmount;
        }
        $this->availableChemicals[$chemical] += $reaction['amount'] * $numReactionsNeeded;
        return true;
    }

    public function getNecessaryOre()
    {
        return $this->ore;
    }
}

function stringToFormulas(string $input): array
{
    $formulas = [];
    $lines = explode("\n", $input);
    foreach ($lines as $line) {
        $parts = explode(' => ', $line);
        $inputChemicals = arrayToChemicals(array_map('trim', explode(',', $parts[0])));
        $outputChemical = stringToChemical($parts[1]);
        if (isset($formulas[$outputChemical['chemical']])) {
            throw new Exception('Oh shit');
        }
        $formulas[$outputChemical['chemical']] = [
            'amount'    => $outputChemical['amount'],
            'chemicals' => $inputChemicals,
        ];
    }
    return $formulas;
}

function arrayToChemicals(array $input): array
{
    $chemicals = [];
    foreach ($input as $string) {
        $parts = explode(' ', $string);
        $chemicals[$parts[1]] = (int)$parts[0];
    }
    return $chemicals;
}

function stringToChemical(string $input): array
{
    $parts = explode(' ', $input);
    return [
        'chemical' => $parts[1],
        'amount'   => (int)$parts[0],
    ];
}