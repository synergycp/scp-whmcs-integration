<?php

use Scp\Client\Client;
use Scp\Api\Api;

class ClientModelTest extends TestCase
{
    /**
     * @param  array  $info
     *
     * @dataProvider dataSave
     */
    public function testSave(array $info)
    {
        $api = Mockery::mock(Api::class);
        $client = new Client($info, $api);
        $client->save();
    }

    public function dataSave()
    {
        return [
            [
                [
                    $testKey = 'test' => $testValue = 'Test',
                ],
            ],
        ];
    }
}
