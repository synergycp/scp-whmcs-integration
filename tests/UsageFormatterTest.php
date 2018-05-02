<?php

use Scp\Whmcs\Server\Usage\UsageFormatter;

class UsageFormatterTest extends TestCase
{
    /**
     * @var DataUsageFormatter
     */
    protected $format;

    public function setUp()
    {
        $this->format = new UsageFormatter();
    }

    public function testBitConverter()
    {
        $this->assertEquals(
            $this->format->bitsToMB(8),
            1
        );

        $this->assertEquals(
            $this->format->bitsToMB(8000),
            1000
        );
    }

    public function testRoundSigDigs()
    {
        $this->assertEquals(
            $this->format->roundSigDigs(123456789, 4),
            123500000
        );

        $this->assertEquals(
            $this->format->roundSigDigs(123456789, 3),
            123000000
        );
    }
}
