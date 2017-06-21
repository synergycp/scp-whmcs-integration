<?php

namespace Scp\Whmcs;

use Scp\Api\Api as OriginalApi;
use Scp\Api\ApiKey;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Client\ClientService;
use Scp\Whmcs\App;
use Scp\Support\Arr;

class Api extends OriginalApi
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

        if (!$apiKey) {
            // This is now processed later because it breaks the product config page.
            //throw new \RuntimeException('This host is not linked to SynergyCP (server = 0)');
        }

        $parsed = parse_url($hostname);
        $path = Arr::get($parsed, 'path', '');
        if ($path) {
            $path = trim($path, '/') . '/';
        }

        $host = Arr::get($parsed, 'host', '');
        if ($host) {
            $host .= '/';
        }

        $scheme = Arr::get($parsed, 'scheme', 'http');
        $url = sprintf('%s://%s%s', $scheme, $host, $path);

        parent::__construct($url, $apiKey);

        $this->setTransport($transport);
    }

    public function call()
    {
        if (!$this->url || !$this->apiKey) {
            throw new \RuntimeException('This host is not linked to SynergyCP (server = 0)');
        }

        return call_user_func_array(['parent', 'call'], func_get_args());
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

        $clients = App::get()->make(ClientService::class);
        $apiKey = $clients->apiKey();

        $api->setApiKey($apiKey->key);

        return $api;
    }
}
