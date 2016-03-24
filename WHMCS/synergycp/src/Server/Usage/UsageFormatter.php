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
        $this->bitsToMB = 1.25 * pow(10, -7);
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
}
