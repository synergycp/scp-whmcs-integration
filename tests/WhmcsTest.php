<?php

use Scp\Whmcs\Whmcs\Whmcs;

class WhmcsTest extends TestCase
{
    public function testParseConfigOptionRows()
    {
        $rows = [
            (object) ['name' => 'Memory', 'val' => 'mem-1|8 GB RAM'],
            (object) ['name' => 'Memory', 'val' => ' mem-2 | 16 GB RAM '],
            (object) ['name' => 'Datacenter Location', 'val' => 'la-1'],
            (object) ['name' => 'Add On', 'val' => 'add-1|Name|With|Pipes'],
        ];

        $this->assertSame([
            'Memory' => [
                'mem-1' => '8 GB RAM',
                'mem-2' => '16 GB RAM',
            ],
            'Datacenter Location' => [
                'la-1' => 'la-1',
            ],
            'Add On' => [
                'add-1' => 'Name|With|Pipes',
            ],
        ], Whmcs::parseConfigOptionRows($rows));
    }

    public function testParseConfigOptionRowsWithNoRows()
    {
        $this->assertSame([], Whmcs::parseConfigOptionRows([]));
    }
}
