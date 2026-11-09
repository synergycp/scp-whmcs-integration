<?php

use Scp\Whmcs\Api;
use Scp\Whmcs\ApiTransport;
use Scp\Whmcs\LogFactory;
use Scp\Whmcs\Whmcs\Whmcs;
use PHPUnit\Framework\Attributes\DataProvider;

class ApiTest extends TestCase
{
    #[DataProvider('dataHostnames')]
    public function testUrlFromHostname($hostname, $secure, $expected)
    {
        $this->assertSame($expected, Api::urlFromHostname($hostname, $secure ? 'https' : 'http'));
    }

    public static function dataHostnames()
    {
        return [
            'bare host, http' => ['api.example.com', false, 'http://api.example.com/'],
            'bare host, https' => ['api.example.com', true, 'https://api.example.com/'],
            'host with path' => ['api.example.com/whmcs', true, 'https://api.example.com/whmcs/'],
            'full url keeps its scheme' => ['http://api.example.com/', true, 'http://api.example.com/'],
            'full url with path' => ['https://api.example.com/v1/', false, 'https://api.example.com/v1/'],
        ];
    }

    public function testServerWithoutHostnameIsNotLinked()
    {
        $api = $this->makeApi(['serveraccesshash' => 'key']);

        $this->assertSame('', $api->baseUrl());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not linked');
        $api->get('server');
    }

    public function testServerWithoutKeyIsNotLinked()
    {
        $api = $this->makeApi(['serverhostname' => 'api.example.com', 'serversecure' => true]);

        $this->assertSame('https://api.example.com', $api->baseUrl());

        $this->expectException(\RuntimeException::class);
        $api->get('server');
    }

    private function makeApi(array $params)
    {
        return new Api(new Whmcs($params), new ApiTransport(new LogFactory()));
    }
}
