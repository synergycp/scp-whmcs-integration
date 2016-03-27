<?php

namespace Scp\Server;

use Scp\Api\ApiRepository;

class ServerRepository extends ApiRepository
{
    /**
     * @var string
     */
    protected $class = Server::class;

    public function findByBillingId($billingId)
    {
        $result = $this->api->get($this->path(), [
            'billing_id' => $billingId,
        ]);
        print_r($result);
    }
}
