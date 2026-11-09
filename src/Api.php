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
        $hostname = (string) Arr::get($params, 'serverhostname', '');
        $url = $hostname === '' ? '' : static::urlFromHostname(
            $hostname,
            Arr::get($params, 'serversecure', false) ? 'https' : 'http'
        );

        parent::__construct($url, $apiKey);

        $this->setTransport($transport);
    }

    /**
     * Build the API base URL from the WHMCS server hostname field, which may
     * be a bare host, a host with a path, or a full URL.
     *
     * @param string $hostname
     * @param string $defaultScheme used when the hostname has no scheme
     *
     * @return string
     */
    public static function urlFromHostname($hostname, $defaultScheme)
    {
        // A bare hostname parses as a path, so give it a scheme first.
        if (strpos($hostname, '://') === false) {
            $hostname = $defaultScheme . '://' . $hostname;
        }

        $parsed = parse_url($hostname) ?: [];
        $path = Arr::get($parsed, 'path', '');
        $host = Arr::get($parsed, 'host', '');
        $scheme = Arr::get($parsed, 'scheme', $defaultScheme);

        $path = trim($path, '/');
        if ($path !== '') {
            $path .= '/';
        }

        if ($host) {
            $host .= '/';
        }

        return sprintf('%s://%s%s', $scheme, $host, $path);
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
