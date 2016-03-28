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
        return $this->query()->where('billing_id', $billingId)->first();
    }
}
