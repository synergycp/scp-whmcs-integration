<?php

use Scp\Client\Client;
use Scp\Api\Api;

class ClientModelTest extends TestCase
{
    /**
     * @param  array  $info
     *
     * @dataProvider dataCreate
     */
    public function testCreate(array $info, array $expectedInfo)
    {
        $api = Mockery::mock(Api::class);
        $api->shouldReceive('post')
            ->with('client/', $expectedInfo);
        $client = new Client($info, $api);
        $client->save();
    }

    public function dataCreate()
    {
        return [
            [
                $data = [
                    'test' => 'Test',
                ], $data,
            ],
        ];
    }
}
