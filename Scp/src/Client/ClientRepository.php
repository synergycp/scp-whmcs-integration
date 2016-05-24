<?php

namespace Scp\Client;

use Scp\Api\ApiRepository;

class ClientRepository extends ApiRepository
{
    /**
     * @var string
     */
    protected $class = Client::class;

    public function findByBillingId($billingId)
    {
        return $this->query()->where('billing_id', $billingId)->first();
    }

    public function findByEmail($email)
    {
        return $this->query()->where('email', $email)->first();
    }
}
