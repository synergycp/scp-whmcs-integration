<?php
use Scp\Client\Client;
use Scp\Client\ClientRepository;

class ApiClientTest extends ApiTestCase
{
    public function testList()
    {
        $repository = new ClientRepository();
        $repository->query()->get();
    }

    public function testCreate()
    {
        $client = new Client([
            'email' => $email = 'zanehoop@gmail.com',
            'first' => 'Zane',
            'last' => 'Hooper',
            'billing_id' => '1',
        ]);
        $client->save();

        $this->assertNotNull($client->id);
        $this->assertEquals($client->email, $email);

        $client->delete();
    }

    public function testDelete()
    {
        $client = new Client([
            'email' => 'zanehoop@gmail.com',
            'first' => 'Zane',
            'last' => 'Hooper',
            'billing_id' => $billingId = '1',
        ]);
        $client->save()->delete();

        $repository = new ClientRepository();
        $client = $repository->findByBillingId($billingId);
        $this->assertNull($client);
    }
}
