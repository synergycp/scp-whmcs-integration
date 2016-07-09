<?php

use Scp\Api\ApiQuery;
use Scp\Api\Pagination\ApiPaginator;
use Scp\Server\Install;
use Scp\Support\Collection;
use Scp\Whmcs\Database\Database;
use Scp\Whmcs\Server\Provision\ServerProvisioner;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Ticket\TicketManager;
use Scp\Whmcs\Client\ClientService;
use Scp\Server\Server;
use Scp\Server\ServerProvisioner as OriginalServerProvisioner;
use Scp\Client\Client;
use Scp\Whmcs\Whmcs\WhmcsConfig;
use Illuminate\Database\Query\Builder;

class ServerProvisionerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->server = Mockery::mock(Server::class);
        $this->client = Mockery::mock(Client::class);
        $this->provision = new ServerProvisioner(
            $this->whmcs = Mockery::mock(Whmcs::class),
            $this->database = Mockery::mock(Database::class),
            $this->config = Mockery::mock(WhmcsConfig::class),
            $this->clients = Mockery::mock(ClientService::class),
            $this->tickets = Mockery::mock(TicketManager::class),
            $this->orig = Mockery::mock(OriginalServerProvisioner::class)
        );
    }

    /**
     * @param array $input
     * @param array $serverInfo
     *
     * @dataProvider dataProvision
     */
    public function testProvision(array $config, array $params, array $serverFilter, array $serverInfo, array $extraInstalls)
    {
        $this->setupConfig($config, $params);

        $this->orig->shouldReceive('server')
            ->with($serverFilter, $serverInfo, $this->client)
            ->andReturn($this->server);
        $this->clients->shouldReceive('getOrCreate')
            ->andReturn($this->client);

        $installQuery = Mockery::mock(ApiQuery::class);
        $installs = Mockery::mock(ApiPaginator::class);
        $collect = Mockery::mock(Collection::class);
        $installQuery->shouldReceive('get')->once()->andReturn($installs);
        foreach ($extraInstalls as $postData) {
            $this->server->shouldReceive('installs')
                ->andReturn($installQuery);
            $install = Mockery::mock(Install::class);
            $installs->shouldReceive('items')
                ->once()
                ->andReturn($collect);
            $collect->shouldReceive('last')
                ->once()
                ->andReturn($install);
            $installQuery->shouldReceive('model')
                ->andReturn($install);
            $install->shouldReceive('save')
                ->with($postData);
            $install->shouldReceive('getAttribute')
                ->with('id')
                ->andReturn($postData['parent']['id']);
        }

        $this->provision->create();
    }

    public function dataProvision()
    {
        $opt = 'configoption';

        return [
            [
                [
                    'Operating System' => $osChoice = 'windows-2008',
                    'Memory' => $ram = 'ram',
                    'Network Port Speed' => $portSpeed = 'speed-1000',
                    'Datacenter Location' => $loc = 'LOC-LA',
                    'IPv4 Addresses' => $ips = 'ip-28',
                    'Bandwidth' => $maxBandwidth = '10TB',
                ], [
                    $opt.WhmcsConfig::CPU_BILLING_ID => $cpu = 'cpu-',
                    $opt.WhmcsConfig::PXE_ACCESS => $accessPxe = true,
                    $opt.WhmcsConfig::IPMI_ACCESS => $accessIpmi = true,
                    $opt.WhmcsConfig::SWITCH_ACCESS => $accessSwitch = true,
                    $opt.WhmcsConfig::PRE_INSTALL => $preInstall = 'test',
                    'clientsdetails' => [
                        'email' => 'zanehoop@gmail.com',
                        'firstname' => 'Zane',
                        'lastname' => 'Hooper',
                    ],
                    'userid' => '10',
                    'pid' => 1,
                    'serviceid' => $billingId = '1',
                    'domain' => $nickname = '_domain_',
                    'password' => $password = '_password_',
                ], [
                    'mem_billing' => $ram,
                    'cpu_billing' => $cpu,
                    'disks_billing' => [],
                    'addons_billing' => [],
                    'ip_group_billing' => $loc,
                ], [
                    'ips_billing' => $ips,
                    'pxe_profile_billing' => $preInstall,
                    'port_speed_billing' => $portSpeed,
                    'nickname' => $nickname,
                    'password' => $password,
                    'billing' => [
                        'id' => $billingId,
                        'max_bandwidth' => $maxBandwidth,
                    ],
                    'access' => [
                        'pxe' => $accessPxe,
                        'ipmi' => $accessIpmi,
                        'switch' => $accessSwitch,
                    ]
                ], [[
                    'pxe_profile_billing' => $osChoice,
                    'password' => $password,
                    'parent' => [
                        'id' => 1,
                    ],
                ]],
            ],
        ];
    }

    /**
     * @param array $input
     * @param array $whmcsConfig
     *
     * @dataProvider dataProvisionTicket
     */
    public function testProvisionTicket($productName, array $config, array $params, array $whmcsConfig, array $ticketInfo)
    {
        $this->setupConfig($config, $params);

        $this->orig->shouldReceive('server')->andReturn(null);
        $this->whmcs->shouldReceive('configOptions')->andReturn($whmcsConfig);
        $this->tickets->shouldReceive('createAndLogErrors')->with($ticketInfo);
        $this->clients->shouldReceive('getOrCreate')->andReturn($this->client);
        $this->query = Mockery::mock(Builder::class);
        $this->database->shouldReceive('table')
            ->once()
            ->with('tblproducts')
            ->andReturn($this->query)
            ;
        $this->query->shouldReceive('select')
            ->once()
            ->with('name')
            ->andReturn($this->query)
            ;
        $this->query->shouldReceive('where')
            ->once()
            ->with('id', $params['pid'])
            ->andReturn($this->query)
            ;
        $this->query->shouldReceive('limit')
            ->once()
            ->with(1)
            ->andReturn($this->query)
            ;
        $result = new stdClass;
        $result->name = $productName;
        $this->query->shouldReceive('first')
            ->once()
            ->with()
            ->andReturn($result)
            ;

        $this->provision->create();
    }

    public function dataProvisionTicket()
    {
        $opt = 'configoption';
        return [
            [
                $productName = '_test name_',
                $configOpts = [
                    $osLabel = 'Operating System' => $osChoice = 'windows-2008',
                    $memLabel = 'Memory' => $ram = 'ram',
                    $portLabel = 'Network Port Speed' => $portSpeed = 'speed-1000',
                    $locLabel = 'Datacenter Location' => $loc = 'LOC-LA',
                    $ipLabel = 'IPv4 Addresses' => $ips = 'ip-28',
                    $bandwidthLabel = 'Bandwidth' => $maxBandwidth = '10TB',
                ], [
                    $opt.WhmcsConfig::CPU_BILLING_ID => 'cpu-',
                    $opt.WhmcsConfig::PXE_ACCESS => true,
                    $opt.WhmcsConfig::IPMI_ACCESS => true,
                    $opt.WhmcsConfig::SWITCH_ACCESS => true,
                    $opt.WhmcsConfig::PRE_INSTALL => '',
                    'configoptions' => $configOpts,
                    'clientsdetails' => [
                        'email' => 'zanehoop@gmail.com',
                        'firstname' => 'Zane',
                        'lastname' => 'Hooper',
                    ],
                    'userid' => $clientId = '10',
                    'serviceid' => $billingId = '1',
                    'domain' => $domain = '_domain_',
                    'password' => $password = '_password_',
                    'pid' => 1,
                ], [
                    $osLabel => [
                        $osChoice => $osName = 'Windows 2008',
                    ],
                    $memLabel => [
                        $ram => $memName = 'Ram Test',
                    ],
                    $portLabel => [
                        $portSpeed => $portName = 'Speed Test',
                    ],
                    $locLabel => [
                        $loc => $locName = 'Location Test',
                    ],
                    $ipLabel => [
                        $ips => $ipName = 'IP Test',
                    ],
                    $bandwidthLabel => [
                        $maxBandwidth => $bandwidthName = 'IP Test',
                    ],
                ], [
                    'clientid' => $clientId,
                    'subject' => 'Server provisioning request',
                    'message' => "Your server has been queued for setup and will be processed shortly.

Product Name: $productName
Billing ID: $billingId
Hostname: $domain
Root Password: $password
$osLabel: $osName
$memLabel: $memName
$portLabel: $portName
$locLabel: $locName
$ipLabel: $ipName
$bandwidthLabel: $bandwidthName
",
                ],
            ],
        ];
    }


    private function setupConfig(array $config, array $params)
    {
        $this->config->shouldReceive('options')
            ->andReturn($config);
        $this->config->shouldReceive('get')
            ->andReturnUsing(function ($key) use (&$params) {
                return $params[$key];
            });
        $this->config->shouldReceive('option')
            ->andReturnUsing(function ($key) use (&$params) {
                return $params['configoption'.$key];
            });
        $this->whmcs->shouldReceive('getParams')
            ->andReturn($params);
    }
}
