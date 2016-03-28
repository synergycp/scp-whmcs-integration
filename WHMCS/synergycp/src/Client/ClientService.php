<?php

namespace Scp\Whmcs\Client;

use Scp\Client\ClientRepository;
use Scp\Whmcs\Whmcs\Whmcs;

class ClientService
{
    /**
     * @var ClientRepository
     */
    protected $clients;

    /**
     * @var Whmcs
     */
    protected $whmcs;

    public function __construct(
        Whmcs $whmcs,
        ClientRepository $clients
    ) {
        $this->whmcs = $whmcs;
        $this->clients = $clients;
    }

    public function get()
    {
        $params = $this->whmcs->getParams();
        $billingId = $params['userid'];

        return $this->clients->findByBillingId($billingId);
    }

    public function getOrCreate()
    {
        if ($client = $this->get()) {
            return $client;
        }

        return $this->create();
    }

    public function create()
    {
        $params = $this->whmcs->getParams();

        return $this->clients->create([
            'email' => $params['clientsdetails']['email'],
            'first' => $params['clientsdetails']['firstname'],
            'last' => $params['clientsdetails']['lastname'],
            'billing_id' => $params['userid'],
        ]);
    }
}
