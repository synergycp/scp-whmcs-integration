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
    public function __construct(
        Whmcs $whmcs,
        ApiTransport $transport
    ) {
        $this->whmcs = $whmcs;

        $params = $whmcs->getParams();
        $apiKey = Arr::get($params, 'serveraccesshash');
        $hostname = Arr::get($params, 'serverhostname');

        $parsed = parse_url($hostname);
        $path = trim(Arr::get($parsed, 'path', ''), '/');
        if ($path) {
            $path .= '/';
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
        $client = $clients->getOrCreate();

        $apiKey = with(new ApiKey)->owner($client)->save();

        $api->setApiKey($apiKey->key);

        return $api;
    }
}
