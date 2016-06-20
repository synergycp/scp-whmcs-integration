<?php

use Scp\Client\Client;
use Scp\Api\Api;
use Scp\Api\ApiResponse;

class ClientModelTest extends TestCase
{
    public function setUp()
    {
        $this->api = Mockery::mock(Api::class);
        $this->response = Mockery::mock(ApiResponse::class);
    }

    /**
     * @param array $info
     *
     * @dataProvider dataCreate
     */
    public function testCreate(array $info, array $expectedInfo)
    {
        $this->response
            ->shouldReceive('data')
            ->andReturn((object) [
                'id' => $clientId = 1,
            ]);
        $this->api
            ->shouldReceive('post')
            ->with('client/', $expectedInfo)
            ->andReturn($this->response);
        $client = new Client($info, $this->api);
        $client->save();

        $this->assertEquals($client->id, $clientId);
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
