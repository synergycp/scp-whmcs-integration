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
        // TODO: pass filters as server_filters instead of server_id
        $server = $this->servers->query()
            ->where($filters)
            ->first();
        if (!$server) {
            return;
        }

        $provisionData = [
            'client_id' => $client->id,
            'server_id' => $server->id,
        ] + $set;
        $result = $this->api->post('server/provision', $provisionData);
        $data = $result->data();

        if (!$data) {
            return;
        }

        $server = new Server((array) $data->server, $this->api);
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
