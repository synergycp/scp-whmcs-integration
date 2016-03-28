<?php

use Scp\Client\Client;
use Scp\Client\ClientRepository;
use Scp\Api\Api;

class ClientModelTest extends TestCase
{
    public function setUp()
    {
        $this->api = Mockery::mock(Api::class);
    }

    /**
     * @param  array  $info
     *
     * @dataProvider dataCreate
     */
    public function testCreate(array $info, array $expectedInfo)
    {
        $this->api
            ->shouldReceive('post')
            ->with('client/', $expectedInfo);
        $client = new Client($info, $this->api);
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
