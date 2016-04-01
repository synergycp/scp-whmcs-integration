<?php

namespace Scp\Whmcs\Client;

use Scp\Client\ClientRepository;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Support\Arr;

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

        if ($client = $this->clients->findByBillingId($billingId)) {
            return $client;
        }

        $email = Arr::get($params, 'clientsdetails.email');
        if ($email && $client = $this->clients->findByEmail($email)) {
            $client->api_user = $billingId;
            $client->save();

            return $client;
        }
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
