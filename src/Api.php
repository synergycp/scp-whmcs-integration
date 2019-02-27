<?php

namespace Scp\Whmcs;

use Scp\Api\Api as OriginalApi;
use Scp\Support\Arr;
use Scp\Whmcs\Client\ClientService;
use Scp\Whmcs\Whmcs\Whmcs;

class Api
    extends OriginalApi
{
    /**
     * @var Whmcs
     */
    protected $whmcs;

    /**
     * Api constructor.
     *
     * @param Whmcs        $whmcs
     * @param ApiTransport $transport
     */
    public function __construct(
        Whmcs $whmcs,
        ApiTransport $transport
    ) {
        $this->whmcs = $whmcs;

        $params = $whmcs->getParams();
        $apiKey = Arr::get($params, 'serveraccesshash');
        $hostname = Arr::get($params, 'serverhostname');
        $parsed = parse_url($hostname);
        $path = Arr::get($parsed, 'path', '');
        $host = Arr::get($parsed, 'host', '');

        // $default_scheme = Arr::get($params, 'serversecure', false) ? 'https' : 'http';
        // $scheme = Arr::get($parsed, 'scheme', $default_scheme);
        $scheme = Arr::get($parsed, 'scheme', 'http');

        if ($path) {
            $path = trim($path, '/') . '/';
        }

        if ($host) {
            $host .= '/';
        }

        $url = sprintf('%s://%s%s', $scheme, $host, $path);

        parent::__construct($url, $apiKey);

        $this->setTransport($transport);
    }

    public function call($method, $path, array $data = [])
    {
        if (!$this->url || !$this->apiKey) {
            throw new \RuntimeException('This host is not linked to SynergyCP (server = 0)');
        }

        return parent::call($method, $path, $data);
    }

    /**
     * Get an API Instance on behalf of the current authed Client.
     *
     * @return static
     */
    public function asClient()
    {
        $api = new static($this->whmcs, $this->getTransport());

        // Make sure client API is not now the default one.
        static::instance($this);

        $clients = App::get()
                      ->make(ClientService::class)
        ;
        $apiKey = $clients->apiKey();

        $api->setApiKey($apiKey->key);

        return $api;
    }
}
