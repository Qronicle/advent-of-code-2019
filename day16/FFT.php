<?php

class FFT
{
    protected $basePattern = [0, 1, 0, -1];

    public function phase(string $pattern, int $phases = 1, int $offset = 0): string
    {
        $numDigits = strlen($pattern);
        $halfDigits = (int)ceil($numDigits * 0.5);

        // Apply offset - works only when offset > half digits I assume
        if ($offset) {
            $numDigits -= $offset;
            $pattern = substr($pattern, $offset);
            $halfDigits = max($halfDigits, $offset) - $offset;
        }

        for ($phase = 0; $phase < $phases; $phase++) {
            $newPattern = '';
            $sum = 0;
            // Work from back to front
            // Final half
            for ($position = $numDigits - 1; $position >= $halfDigits; $position--) {
                $sum += $pattern[$position];
                $newPattern = substr((string)$sum, -1) . $newPattern;
            }
            // First half
            for ($position = $halfDigits - 1; $position >= 0; $position--) {
                $sum = 0;
                for ($elementIndex = $position; $elementIndex < $numDigits; $elementIndex++) {
                    // Calculate offset
                    $patternIndex = floor(($elementIndex + 1) / ($position + 1)) % 4;
                    // When not zero, do calculations
                    if ($patternIndex % 2 != 0) {
                        $digits = substr($pattern, $elementIndex, $position + 1);
                        $sum += array_sum(str_split($digits)) * ($patternIndex == 1 ? 1 : -1);
                    }
                    // Skip until next operator
                    $elementIndex += $position;
                }
                $newPattern = substr((string)$sum, -1) . $newPattern;
            }
            $pattern = $newPattern;
        }
        return $pattern;
    }

    /**
     * The original phase method
     *
     * @param string $pattern
     * @param int    $phases
     * @return string
     */
    public function phaseUnoptimized(string $pattern, int $phases = 1): string
    {
        $numDigits = strlen($pattern);
        for ($phase = 0; $phase < $phases; $phase++) {
            $newPattern = '';
            for ($position = 0; $position < $numDigits; $position++) {
                $sum = 0;
                for ($elementIndex = 0; $elementIndex < $numDigits; $elementIndex++) {
                    $sum += $pattern[$elementIndex] * $this->getPatternMultiplier($position, $elementIndex);
                }
                $newPattern .= substr((string)$sum, -1);
            }
            //echo "$newPattern\n";
            $pattern = $newPattern;
        }
        return $pattern;
    }

    public function getPatternMultiplier(int $position, int $elementIndex): int
    {
        $offset = floor(($elementIndex + 1) / ($position + 1)) % 4;
        $offset = floor($offset);
        $offset %= count($this->basePattern);
        return $this->basePattern[$offset];
    }
}