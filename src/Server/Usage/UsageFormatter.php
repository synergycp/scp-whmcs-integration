<?php

namespace Scp\Whmcs\Server\Usage;

class UsageFormatter
{
    /**
     * @var int
     */
    protected $bitsToMB;

    public function __construct()
    {
        $this->bitsToMB = 1/8 * pow(10, -6);
    }

    /**
     * @see http://php.net/manual/en/function.round.php#24379
     *
     * @param float $number
     * @param int   $sigdigs
     *
     * @return float
     */
    public function roundSigDigs($number, $sigdigs)
    {
        $multiplier = 1;

        while ($number < 0.1) {
            $number *= 10;
            $multiplier /= 10;
        }

        while ($number >= 1) {
            $number /= 10;
            $multiplier *= 10;
        }

        return round($number, $sigdigs) * $multiplier;
    }

    public function bitsToMB($bits, $sigdigs = null)
    {
        if (!$bits) {
            return 0;
        }

        $bitsMB = $bits * $this->bitsToMB;

        return $sigdigs ? $this->roundSigDigs($bitsMB, $sigdigs) : $bitsMB;
    }

    public function megaBytesToBits($megaBytes, $sigdigs = null)
    {
        if (!$megaBytes) {
            return 0;
        }

        $bits = $megaBytes / $this->bitsToMB;

        return $sigdigs ? $this->roundSigDigs($bits, $sigdigs) : $bits;
    }

    function humanToBits($size)
    {
        if (!$size) {
            return 0;
        }

        $byteUnits = ['','K','M','G','T','P','E','Z','Y'];
        $units = array_flip($byteUnits);
        $size = strtoupper(trim($size));

        if (ends_with($size, 'PS')) {
            $size = substr($size, 0, -2);
        }

        $unit = substr($size, -2, 1);
        $unit = !ends_with($size, 'B') || !isset($units[$unit]) ? '' : $unit;

        $count = floatval($size);
        $bits = round($count * pow(1000, $units[$unit]) * 8);

        return $bits;
    }

    function megaBytesToHuman($megaBytes, $precision = 2, $default = '0B')
    {
        $bits = $this->megaBytesToBits($megaBytes);

        return $this->bitsToHuman($bits, $precision, $default);
    }

    function bitsToHuman($bits, $precision = 2, $default = '0B')
    {
        $bytes = max(intval($bits), 0) / 8;
        if (!$bytes) {
            return $default;
        }

        $byteUnits = ['','K','M','G','T','P','E','Z','Y'];

        $base = log($bytes) / log(1000);
        $pow = max(min(floor($base), count($byteUnits) - 1), 0);

        return round(pow(1000, $base - $pow), $precision).$byteUnits[$pow].'B';
    }
}
