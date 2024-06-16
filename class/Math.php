<?php

namespace Sv\Photo\ExifStats;

class Math
{
    public function convertDecimalToFraction(int|float|string $input): float|string
    {
        if (is_string($input) && str_contains($input, '/')) {
            list($numerator, $denominator) = explode('/', $input, 2);
            if (is_numeric($numerator) && is_numeric($denominator) && $denominator != 0) {
                return self::simplifyFraction((int)$numerator, (int)$denominator);  // Simplify and check if already in fractional form
            } else {
                return "Invalid input"; // Handle division by zero or non-numeric values
            }
        }

        // Convert the input to a float if it's not a fraction
        $decimal = (float)$input;
        $tolerance = 1.0e-6;
        $denominator = 1;
        $numerator = $decimal;

        while (abs(round($numerator) - $numerator) > $tolerance) {
            $denominator *= 10;
            $numerator = $decimal * $denominator;
        }

        $numerator = round($numerator);  // Round to the nearest integer

        // Adjust the fraction if numerator exceeds denominator
        if ($numerator >= $denominator) {
            $numerator = $numerator % $denominator;
            if ($numerator == 0) { // In case the numerator is a perfect multiple of the denominator
                return "1/1"; // Represents 100%
            }
        }

        // Simplify the fraction only if the denominator remains a power of 10
        return self::simplifyFraction($numerator, $denominator);
    }

    private function simplifyFraction($numerator, $denominator): string
    {
        $gcd = self::gcd($numerator, $denominator);  // Call to a gcd function

        // Apply GCD only if the denominator after reduction remains a power of 10
        if (self::isPowerOfTen($denominator / $gcd)) {
            $numerator /= $gcd;
            $denominator /= $gcd;
        }

        return "$numerator/$denominator";
    }

    private function gcd($a, $b): int
    {
        while ($b != 0) {
            $t = $b;
            $b = $a % $b;
            $a = $t;
        }
        return $a;
    }

    function isPowerOfTen($x): bool
    {
        while ($x > 9 && $x % 10 === 0) {
            $x /= 10;
        }
        return $x === 1;
    }

    public function calculateMegapixels(int|float $width, int|float $height): float
    {
        $totalPixels = (float)$width * (float)$height;
        $megapixels = $totalPixels / 1000000;
        return round($megapixels, 2);
    }

    public function calculateAverage(float|array|string $numbers, int $precision = 2): float
    {
        if (is_string($numbers)) {
            if (str_contains($numbers, '/')) {
                list($numerator, $denominator) = explode('/', $numbers, 2);
                if (is_numeric($numerator) && is_numeric($denominator) && (float)$denominator != 0) {
                    $numbers = (float)$numerator / (float)$denominator;
                } else {
                    return -1; // Invalid input or division by zero
                }
            } elseif (is_numeric($numbers)) {
                $numbers = (float)$numbers;
            } else {
                return -1;
            }
        }

        if (!is_array($numbers)) {
            $numbers = array($numbers);
        }

        $numbers = array_filter($numbers, function ($value) {
            return (is_numeric($value) && ($value != -1) && ($value != 0));
        });

        $count = count($numbers);

        if ($count > 0) {
            $sum = array_sum($numbers);
            $average = $sum / $count;
            return round($average, $precision);
        } else {
            return -1;
        }
    }

    public function calculatePercentage(int|float $part, array $wholeArray, int $precision = 2): string
    {
        $whole = array_sum($wholeArray);

        if ($whole == 0) {
            return "xx %";
        }
        $percentage = ($part / $whole) * 100;
        return round($percentage, $precision) . ' %';
    }


    public function formatBytes(int|float $bytes, int $precision = 2): string
    {
        if ($bytes == -1) {
            return '-1';
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        $tmpResult = round($bytes, $precision);

        while (strrpos($tmpResult, '.') >= strlen($tmpResult) - $precision) {
            $tmpResult .= '0';
        }

        return $tmpResult . ' ' . $units[$pow];
    }

}