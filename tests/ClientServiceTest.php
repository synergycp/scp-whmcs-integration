<?php

use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Client\ClientService;
use Scp\Client\Client;
use Scp\Client\ClientRepository;

class ClientServiceTest extends TestCase
{
    public function testGetOrCreateGetBilling()
    {
        $whmcs = Mockery::mock(Whmcs::class);
        $client = Mockery::mock(Client::class);
        $clients = Mockery::mock(ClientRepository::class);
        $service = new ClientService($whmcs, $clients);

        $whmcs->shouldReceive('getParams')
            ->andReturn([
                'userid' => $billingId = 10,
            ]);
        $clients->shouldReceive('findByBillingId')
            ->with($billingId)
            ->andReturn($client);

        $this->assertEquals($service->getOrCreate(), $client);
    }

    public function testGetOrCreateGetEmail()
    {
        $whmcs = Mockery::mock(Whmcs::class);
        $client = Mockery::mock(Client::class);
        $clients = Mockery::mock(ClientRepository::class);
        $service = new ClientService($whmcs, $clients);

        $whmcs->shouldReceive('getParams')
            ->andReturn([
                'userid' => $billingId = 10,
                'clientsdetails' => [
                    'email' => $email = 'zanehoop@gmail.com',
                ],
            ]);
        $clients->shouldReceive('findByBillingId')
            ->andReturn(null);
        $clients->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($client);
        $client->shouldReceive('setAttribute')
            ->with('api_user', $billingId);
        $client->shouldReceive('save');

        $this->assertEquals($service->getOrCreate(), $client);
    }

    public function testGetOrCreateCreate()
    {
        $whmcs = Mockery::mock(Whmcs::class);
        $client = Mockery::mock(Client::class);
        $clients = Mockery::mock(ClientRepository::class);
        $service = new ClientService($whmcs, $clients);

        $whmcs->shouldReceive('getParams')
            ->andReturn([
                'userid' => $billingId = 10,
                'clientsdetails' => [
                    'email' => $email = 'zanehoop@gmail.com',
                    'firstname' => $firstName = 'Zane',
                    'lastname' => $lastName = 'Hooper',
                ],
            ]);
        $clients->shouldReceive('findByBillingId')
            ->andReturn(null);
        $clients->shouldReceive('findByEmail')
            ->andReturn(null);

        $clients->shouldReceive('create')
            ->with([
                'email' => $email,
                'first' => $firstName,
                'last' => $lastName,
                'billing_id' => $billingId,
                'password' => ''
            ])
            ->andReturn($client);

        $this->assertEquals($service->getOrCreate(), $client);
    }
}
