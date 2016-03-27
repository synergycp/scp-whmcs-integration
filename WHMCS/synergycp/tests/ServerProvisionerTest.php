<?php
use Scp\Whmcs\App;
use Scp\Whmcs\Server\Provision\ServerProvisioner;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Ticket\TicketManager;
use Scp\Server\Server;
use Scp\Server\ServerProvisioner as OriginalServerProvisioner;
use Scp\Client\Client;
use Scp\Client\ClientRepository;

class ServerProvisionerTest extends TestCase
{
    /**
     * @param  array  $input
     * @param  array  $serverInfo
     *
     * @dataProvider dataProvision
     */
    public function testProvision(array $input, array $clientInfo, array $serverInfo)
    {
        $orig = Mockery::mock(OriginalServerProvisioner::class);
        $whmcs = Mockery::mock(Whmcs::class);
        $server = Mockery::mock(Server::class);
        $client = Mockery::mock(Client::class);
        $tickets = Mockery::mock(TicketManager::class);
        $clients = Mockery::mock(ClientRepository::class);
        $provision = new ServerProvisioner($whmcs, $tickets, $clients, $orig);
        $orig->shouldReceive('server')
            ->with($serverInfo, $client)
            ->andReturn($server);
        $clients->shouldReceive('create')
            ->with($clientInfo)
            ->andReturn($client);

        $provision->create($input);
    }

    public function dataProvision()
    {
        return [
            [
                [
                    'configoptions' => [
                        'Operating System' => $osChoice = 'windows-2008',
                        'Memory' => $ram = 'ram',
                        'Port Speed' => $portSpeed = 'speed-1000',
                        'IPv4 Addresses' => $ips = 'ip-28',
                    ],
                    'configoption1' => $cpu = 'cpu-',
                    'clientsdetails' => [
                        'email' => $clientEmail = 'zanehoop@gmail.com',
                        'firstname' => $clientFirstName = 'Zane',
                        'lastname' => $clientLastName = 'Hooper',
                    ],
                    'userid' => $clientBillingId = '10',
                    'serviceid' => $billingId = '1',
                ], [
                    'email' => $clientEmail,
                    'first' => $clientFirstName,
                    'last' => $clientLastName,
                    'billing_id' => $clientBillingId,
                ], [
                    'ips' => $ips,
                    'ram' => $ram,
                    'cpu' => $cpu,
                    'hdds' => ';;',
                    'pxe_script' => $osChoice,
                    'port_speed' => $portSpeed,
                    'billing_id' => $billingId,
                ]
            ],
        ];
    }

    /**
     * @param  array  $input
     * @param  array  $whmcsConfig
     *
     * @dataProvider dataProvisionTicket
     */
    public function testProvisionTicket(array $input, array $whmcsConfig, array $ticketInfo)
    {
        $orig = Mockery::mock(OriginalServerProvisioner::class);
        $whmcs = Mockery::mock(Whmcs::class);
        $client = Mockery::mock(Client::class);
        $tickets = Mockery::mock(TicketManager::class);
        $clients = Mockery::mock(ClientRepository::class);
        $provision = new ServerProvisioner($whmcs, $tickets, $clients, $orig);
        $orig->shouldReceive('server')->andReturn(null);
        $whmcs->shouldReceive('configOptions')->andReturn($whmcsConfig);
        $tickets->shouldReceive('createAndLogErrors')->with($ticketInfo);
        $clients->shouldReceive('create')->andReturn($client);

        $provision->create($input);
    }

    public function dataProvisionTicket()
    {
        return [
            [
                [
                    'configoptions' => [
                        'Operating System' => 'windows-2008',
                        'Memory' => 'ram',
                        'Port Speed' => 'speed-1000',
                        'IPv4 Addresses' => 'ip-28',
                    ],
                    'configoption1' => 'cpu-',
                    'clientsdetails' => [
                        'email' => 'zanehoop@gmail.com',
                        'firstname' => 'Zane',
                        'lastname' => 'Hooper',
                    ],
                    'userid' => $clientId = '10',
                    'serviceid' => $billingId = '1',
                ], [
                    $osLabel = 'Operating System' => [
                        'windows-2008' => $osName = 'Windows 2008',
                    ],
                    $memLabel = 'Memory' => [
                        'ram' => $memName = 'Ram Test',
                    ],
                    $portLabel = 'Port Speed' => [
                        'speed-1000' => $portName = 'Speed Test',
                    ],
                    $ipLabel = 'IPv4 Addresses' => [
                        'ip-28' => $ipName = 'IP Test',
                    ],
                ], [
                    'clientid' => $clientId,
                    'subject' => 'Server provisioning request',
                    'message' => "Your server has been queued for setup and will be processed shortly.

Billing ID: $billingId
$osLabel: $osName
$memLabel: $memName
$portLabel: $portName
$ipLabel: $ipName
",
                ]
            ],
        ];
    }
}
