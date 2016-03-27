<?php

namespace Scp\Server;

use Scp\Api\Api;
use Scp\Client\Client;

class ServerProvisioner
{
    /**
     * @var Api
     */
    protected $api;

    public function __construct()
    {
        $this->api = Api::instance();
    }

    /**
     * Provision a Server according to the given filters and return it.
     * Returns null if no server matching the given filters is found.
     *
     * @param  array  $info
     * @param  Client [$client]
     *
     * @return Server|null
     */
    public function server(array $info, Client $client = null)
    {

    }
}
