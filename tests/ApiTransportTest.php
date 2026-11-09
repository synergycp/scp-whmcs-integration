<?php

use Scp\Api\ApiRequest;
use Scp\Api\ApiResponse;
use Scp\Whmcs\ApiTransport;
use Scp\Whmcs\LogFactory;

class ApiTransportTest extends TestCase
{

    public function testSensitiveValuesCollectsKeyAndPasswords()
    {
        $transport = new ApiTransport(Mockery::mock(LogFactory::class));

        $values = $transport->sensitiveValues(
            'https://scp.example/api/server/provision?key=admin-key-123&page=1',
            json_encode([
                'password' => 'root-pw',
                'nickname' => 'host.example',
                'server' => ['fields' => ['password' => 'nested-pw']],
            ])
        );
        sort($values);

        $this->assertSame(['admin-key-123', 'nested-pw', 'root-pw'], $values);
    }

    public function testSensitiveValuesIgnoresEmptyAndNonSensitiveData()
    {
        $transport = new ApiTransport(Mockery::mock(LogFactory::class));

        $this->assertSame([], $transport->sensitiveValues('https://scp.example/api/server', ''));
        $this->assertSame([], $transport->sensitiveValues('https://scp.example/api/server?key=', json_encode(['nickname' => 'x'])));
        $this->assertSame(['k'], $transport->sensitiveValues('https://scp.example/api/server?key=k', 'not json'));
    }

    public function testLogPassesCredentialsToWhmcsForMasking()
    {
        $log = Mockery::mock(LogFactory::class);
        $log->shouldReceive('call')
            ->once()
            ->with(
                'synergycp',
                Mockery::type('string'),
                Mockery::type('string'),
                Mockery::type('string'),
                Mockery::type('array'),
                Mockery::on(function ($replace) {
                    sort($replace);

                    return $replace === ['root-pw', 'secret-key'];
                })
            );

        $transport = new ExposedApiTransport($log);
        $response = new ApiResponse(
            new ApiRequest('POST', 'https://scp.example/api/server/provision?key=secret-key'),
            '{"error":false,"data":{"id":1}}',
            200
        );

        $transport->exposeLog(
            'POST',
            'https://scp.example/api/server/provision?key=secret-key',
            json_encode(['password' => 'root-pw', 'nickname' => 'host']),
            ['Content-Type: application/json'],
            $response
        );

        $this->assertTrue(true);
    }
}

class ExposedApiTransport extends ApiTransport
{
    public function exposeLog($method, $url, $postData, array $headers, ApiResponse $response)
    {
        return $this->log($method, $url, $postData, $headers, $response);
    }
}
