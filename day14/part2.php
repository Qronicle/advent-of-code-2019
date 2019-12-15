<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
$start = microtime(true);

// Parse input to reaction formulas array
$input = file_get_contents('input.txt');
$formulas = stringToFormulas($input);
$oreAmount = 1000000000000;

// Let a NanoFactory create some fuel!
$nanoFactory = new NanoFactory($formulas);
/*$nanoFactory->createChemical('FUEL');die;

$usedChemicals = $nanoFactory->getUsedChemicals();
$restChemicals = $nanoFactory->getRemainingChemicals();

$maxFuel = floor($oreAmount / $usedChemicals['ORE']);
$oreAmount = $oreAmount % $usedChemicals['ORE'];
echo "Max fuel without rests: $maxFuel (leaving $oreAmount)\n";

foreach ($restChemicals as $chemical => $amount) {
    $restChemicals[$chemical] *= $maxFuel;
}
$restChemicals['ORE'] = $oreAmount;

$nanoFactory->setAvailableChemicals($restChemicals);
print_r($nanoFactory->getBaseChemicals());
print_r($restChemicals);
print_r($usedChemicals);*/

$maxFuel = 0;
while (true) {
    try {
        $nanoFactory->createChemical('FUEL');
        $maxFuel++;
        $noRest = true;
        foreach ($nanoFactory->getRemainingChemicals() as $chemical => $amount) {
            if ($chemical == 'ORE') continue;
            if ($amount > 0) {
                $noRest = false; break;
            }
        }
        if ($noRest) {
            echo "Fuel: $maxFuel\n";
            $usedOre = $oreAmount - $nanoFactory->getRemainingChemicals()['ORE'];
            echo "Used ore: $usedOre\n";
            $maxFuel = floor($oreAmount / $usedOre) * $maxFuel;
            $oreAmount = $oreAmount % $usedOre;
            echo "Fuel 2: $maxFuel\n";
            echo "Available ore: $oreAmount\n";
            $nanoFactory->setAvailableChemicals(['ORE' => $oreAmount]);
            while (true) {
                try {
                    $nanoFactory->createChemical('FUEL');
                    $maxFuel++;
                } catch (Exception $ex) {
                    break 2;
                }
            }
        }
    } catch (Exception $ex) {
        echo 'Too soon\n';
        break; // Out of ore
    }
}

echo "MAX FUEL: $maxFuel";

$end = microtime(true);
echo "\nResult reached in " . round($end - $start, 2) . " seconds\n";

########################################################################################################################

class NanoFactory
{
    protected $usedChemicals = [];

    /** @var array [chemical => amount] */
    protected $availableChemicals = ['ORE' => 1000000000000];

    /** @var array */
    protected $reactionFormulas;

    public function __construct(array $reactionFormulas)
    {
        $this->reactionFormulas = $reactionFormulas;
    }

    public function createChemical($chemical, $amount = 1)
    {
        //echo "> Creating $amount $chemical\n";
        $this->availableChemicals[$chemical] = $this->availableChemicals[$chemical] ?? 0;
        $this->usedChemicals[$chemical] = ($this->usedChemicals[$chemical] ?? 0) + $amount;
        if ($this->availableChemicals[$chemical] >= $amount) {
            //echo "We have enough $chemical\n";
            //print_r($this->availableChemicals);
            return true;
        } elseif ($chemical == 'ORE') {
            throw new Exception('Out of ore');
        }
        $amountNeeded = $amount - $this->availableChemicals[$chemical];
        $reaction = $this->reactionFormulas[$chemical];
        $numReactionsNeeded = ceil($amountNeeded/$reaction['amount']);
        foreach ($reaction['chemicals'] as $inputChemical => $inputAmount) {
            $inputAmount *= $numReactionsNeeded;
            //echo ">> We need $inputAmount $inputChemical\n";
            try {
                $this->createChemical($inputChemical, $inputAmount);
            } catch (Exception $ex) {
                //echo "Could not create $inputAmount $inputChemical\n";
                throw $ex;
            }
            $this->availableChemicals[$inputChemical] -= $inputAmount;
            if ($inputChemical == 'HKGWZ') {
                //echo "Making $chemical with $inputAmount HKGWZ\n";
            }
        }
        if ($chemical != 'FUEL') {
            $this->availableChemicals[$chemical] += $reaction['amount'] * $numReactionsNeeded;
            if ($chemical == 'HKGWZ') {
                $t = $reaction['amount'] * $numReactionsNeeded;
                //echo "Created $t HKGWZ for a total of {$this->availableChemicals[$chemical]}\n";
            }
        }
        //print_r($this->availableChemicals);
        return true;
    }

    public function getUsedChemicals()
    {
        return $this->usedChemicals;
    }

    public function getRemainingChemicals()
    {
        return $this->availableChemicals;
    }

    public function getBaseChemicals()
    {
        $baseChemicals = [];
        foreach ($this->reactionFormulas as $chemical => $reactionFormula) {
            if (count($reactionFormula['chemicals']) == 1 && key($reactionFormula['chemicals']) == 'ORE') {
                $baseChemicals[] = $chemical;
            }
        }
        return $baseChemicals;
    }

    public function setAvailableChemicals(array $chemicals)
    {
        $this->availableChemicals = $chemicals;
        $this->usedChemicals = [];
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