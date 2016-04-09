<?php

namespace Scp\Whmcs\Whmcs;
use Scp\Whmcs\Server\ServerService;
use Scp\Whmcs\Client\ClientService;
use Scp\Whmcs\Api;

class WhmcsTemplates
{
    const CLIENT_AREA = 'ClientArea';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var ClientService
     */
    protected $client;

    /**
     * @var ServerService
     */
    protected $server;

    public function __construct(
        Api $api,
        ClientService $client,
        ServerService $server
    ) {
        $this->api = $api;
        $this->client = $client;
        $this->server = $server;
    }

    public function clientArea()
    {
        $server = $this->server->current();
        $urlAction = sprintf(
            'clientarea.php?action=productdetails&id=%d&modop=custom&a=',
            $this->server->currentBillingId()
        );

        $apiKey = $this->client->apiKey();
        $urlApi = $this->api->baseUrl();
        $password = $this->generatePassword(10);

        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'password' => $password,
                'url_action' => $urlAction,
                'server' => $server,
                'ips' => $server->entities(),
                'MODULE_FOLDER' => '/modules/servers/synergycp',
                'apiKey' => $apiKey->key,
                'apiUrl' => $urlApi,
            ],
        ];
    }

    private function generatePassword($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public static function functions()
    {
        return [
            static::CLIENT_AREA => 'clientArea',
        ];
    }
}
