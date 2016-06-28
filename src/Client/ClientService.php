<?php

namespace Scp\Whmcs\Client;

use Scp\Client\Client;
use Scp\Client\ClientRepository;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Support\Arr;
use Scp\Api\ApiKey;

class ClientService
{
    const NOT_CHECKED = -1;

    /**
     * @var ClientRepository
     */
    protected $clients;

    /**
     * @var Whmcs
     */
    protected $whmcs;

    /**
     * @var Client|int
     */
    protected $client = self::NOT_CHECKED;

    /**
     * @var ApiKey|null
     */
    protected $apiKey;

    public function __construct(
        Whmcs $whmcs,
        ClientRepository $clients
    ) {
        $this->whmcs = $whmcs;
        $this->clients = $clients;
    }

    /**
     * Get Synergy information for current Client,
     * and create the client on Synergy if they do not exist yet.
     *
     * @return Client
     */
    public function getOrCreate()
    {
        if ($client = $this->get()) {
            return $client;
        }

        return $this->create();
    }

    /**
     * Get currently authed Client's info from Synergy,
     * using Client's Billing ID or email.
     *
     * @return Client|null
     */
    public function get()
    {
        if ($this->client !== static::NOT_CHECKED) {
            return $this->client;
        }

        $params = $this->whmcs->getParams();
        $billingId = $params['userid'];

        if ($this->client = $this->clients->findByBillingId($billingId)) {
            return $this->client;
        }

        $email = Arr::get($params, 'clientsdetails.email');
        if ($email && $this->client = $this->clients->findByEmail($email)) {
            $this->client->api_user = $billingId;
            $this->client->save();

            return $this->client;
        }
    }

    /**
     * Create a new Client on Synergy,
     * using the currently authed Client's information.
     *
     * @return Client
     */
    public function create()
    {
        $params = $this->whmcs->getParams();

        return $this->client = $this->clients->create([
            'email' => $params['clientsdetails']['email'],
            'first' => $params['clientsdetails']['firstname'],
            'last' => $params['clientsdetails']['lastname'],
            'billing_id' => $params['userid'],
        ]);
    }

    /**
     * Get the API Key for the currently authed client.
     *
     * @return ApiKey
     */
    public function apiKey()
    {
        if ($this->apiKey) {
            return $this->apiKey;
        }

        $client = $this->getOrCreate();

        $this->apiKey = new ApiKey();
        $this->apiKey->owner($client)->save();

        return $this->apiKey;
    }
}
