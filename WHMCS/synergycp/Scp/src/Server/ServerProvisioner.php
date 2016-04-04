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

    /**
     * @var ServerRepository
     */
    protected $servers;

    public function __construct(Api $api = null)
    {
        $this->api = $api ?: Api::instance();
        $this->servers = new ServerRepository($this->api);
    }

    /**
     * Provision a Server according to the given filters and return it.
     * Returns null if no server matching the given filters is found.
     *
     * @param  array  $filters
     * @param  array  $set
     * @param  Client [$client]
     *
     * @return Server|null
     */
    public function server(array $filters, array $set, Client $client)
    {
        $filters = $this->addDefaultFilters($filters);
        // TODO: if provision fails, try next server.
        $server = $this->servers->query()
            ->where($filters)
            ->first();
        if (!$server) {
            return;
        }

        $set['client_id'] = $client->id;
        $result = $this->api->post('server/provision', $filters);
        $data = $result->data();

        if (!$data) {
            return;
        }

        $server = new Server($data, $this->api);
        $server->setExists(true);

        return $server;
    }

    private function addDefaultFilters(array $filters)
    {
        return array_merge(
            $filters,
            [
                'available' => true,
            ]
        );
    }
}
