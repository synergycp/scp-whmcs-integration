<?php

namespace Scp\Whmcs\Server\Provision;

use Scp\Server\ServerProvisioner as OriginalServerProvisioner;
use Scp\Whmcs\LogFactory;
use Scp\Whmcs\Database\Database;
use Scp\Whmcs\Ticket\TicketManager;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Whmcs\WhmcsConfig;
use Scp\Whmcs\Client\ClientService;
use Scp\Whmcs\Server\ServerFieldsService;
use Scp\Support\Collection;
use Scp\Server\Server;
use Scp\Entity\Entity;

/**
 * Provision a Server for WHMCS.
 */
class ServerProvisioner
{
    /**
     * @var string
     */
    protected $sep = ',';

    /**
     * @var Whmcs
     */
    protected $whmcs;

    /**
     * @var ClientService
     */
    protected $client;

    /**
     * @var TicketManager
     */
    protected $tickets;

    /**
     * @var OriginalServerProvisioner
     */
    protected $provision;

    /**
     * @var WhmcsConfig
     */
    protected $config;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var LogFactory
     */
    protected $log;

    /**
     * @var ServerFieldsService
     */
    protected $fields;

    /**
     * @param Whmcs                     $whmcs
     * @param Database                  $database
     * @param LogFactory                $log
     * @param WhmcsConfig               $config
     * @param ClientService             $client
     * @param TicketManager             $tickets
     * @param ServerFieldsService       $fields
     * @param OriginalServerProvisioner $provision
     */
    public function __construct(
        Whmcs $whmcs,
        Database $database,
        LogFactory $log,
        WhmcsConfig $config,
        ClientService $client,
        TicketManager $tickets,
        ServerFieldsService $fields,
        OriginalServerProvisioner $provision
    ) {
        $this->log = $log;
        $this->whmcs = $whmcs;
        $this->config = $config;
        $this->client = $client;
        $this->fields = $fields;
        $this->tickets = $tickets;
        $this->database = $database;
        $this->provision = $provision;
    }

    /**
     * @return Server|null
     */
    public function create()
    {
        $choices = $this->config->options();
        $params = $this->whmcs->getParams();
        $osChoicesString = sprintf(
            '%s%s%s',
            $this->config->option(WhmcsConfig::PRE_INSTALL),
            $this->sep,
            $choices['Operating System']
        );
        $osChoices = array_filter(explode($this->sep, $osChoicesString));
        $osChoice = array_shift($osChoices);

        $password = $params['password'];

        $server = $this->provision->server(
            $this->getFilters(),
            $this->getServerSettings($osChoice, $password),
            $this->client->getOrCreate()
        );

        if (!$server) {
            $this->createTicket($params);

            return;
        }

        $this->queueInstalls($server, $osChoices, $password);
        $this->updateServerInDatabase($server);

        return $server;
    }

    /**
     * @throws \Exception
     */
    public function check()
    {
        if (!$this->getServer()) {
            throw new \Exception('No matching Server found in inventory');
        }

        if (!$this->getEntities()) {
            throw new \Exception('No mathcing Entity found in inventory');
        }
    }

    /**
     * @return Entity|void
     */
    public function getEntities()
    {
        return Entity::query()
            ->where('group', [
                'billing' => $this->ipGroupChoice(),
            ])
            ->where('server', 'none')
            ->first()
            ;
    }

    /**
     * @return Server|void
     */
    public function getServer()
    {
        return $this->provision->getServer(
            $this->getFilters()
        );
    }

    /**
     * @param Server $server
     */
    private function updateServerInDatabase(Server $server)
    {
        $serviceId = $this->config->get('serviceid');
        $updated = $this->fields->fill($serviceId, $server);

        $this->log->activity(
            '%s service ID: %s during create',
            $updated ? 'Successfully updated' : 'Failed to update',
            $serviceId
        );
    }

    /**
     * @return string
     */
    private function ipGroupChoice()
    {
        return $this->config->getOption('Datacenter Location');
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        $choices = $this->config->options();

        return [
            'mem_billing' => $this->config->getOption('Memory'),
            'cpu_billing' => $this->config->option(WhmcsConfig::CPU_BILLING_ID),
            'disks_billing' =>  $this->multiChoice($choices, 'SSD Bay %d'),
            'addons_billing' => $this->multiChoice($choices, 'Add On %d'),
            'ip_group_billing' => $this->ipGroupChoice(),
        ];
    }

    /**
     * @param string $osChoice
     * @param string $password
     *
     * @return array
     */
    private function getServerSettings($osChoice, $password)
    {
        $choices = $this->config->options();
        $params = $this->whmcs->getParams();

        $portSpeed = $choices['Network Port Speed'];
        $ips = $choices['IPv4 Addresses'];
        $nickname = $params['domain'];
        $rateLimit = array_get($choices, 'DDoS Protection');

        return [
            'ips_billing' => $ips,
            'pxe_profile_billing' => $osChoice,
            'port_speed_billing' => $portSpeed,
            'nickname' => $nickname,
            'password' => $password,
            'ddos' => array_filter([
                'rate-limit' => $rateLimit ?: null,
            ]),
            'billing' => [
                'id' => $this->config->get('serviceid'),
                'max_bandwidth' => $choices['Bandwidth'],
            ],
            'access' => [
                'pxe' => $this->config->option(WhmcsConfig::PXE_ACCESS),
                'ipmi' => $this->config->option(WhmcsConfig::IPMI_ACCESS),
                'switch' => $this->config->option(WhmcsConfig::SWITCH_ACCESS),
            ],
        ];
    }

    /**
     * @param Server $server
     * @param array  $osChoices
     * @param string $password
     */
    private function queueInstalls(Server $server, array $osChoices, $password)
    {
        $choices = new Collection($osChoices);

        $choices->reduce(function ($install, $choice) use ($password, $server) {
            $install = $install ?: $server->installs()->get()->items()->last();
            $install = $server->installs()->model()->save([
                'pxe_profile_billing' => trim($choice),
                'parent' => [
                    'id' => $install->id,
                ],
                'password' => $password,
            ]);

            return $install;
        });
    }

    /**
     * @param array  $choices
     * @param string $format  format string for the keys of the field.
     *
     * @return array
     */
    private function multiChoice(array $choices, $format)
    {
        $result = [];

        for ($i = 1; $i <= 8; ++$i) {
            $key = sprintf($format, $i);

            if (!empty($choices[$key]) && $choices[$key] != 'None') {
                $result[] = $choices[$key];
            }
        }

        return $result;
    }

    /**
     * @param array $params
     */
    private function createTicket(array $params)
    {
        $message = sprintf(
            "Your server has been queued for setup and will be processed shortly.\n\nProduct Name: %s\nBilling ID: %s\n",
            $this->getProductName($params),
            $params['serviceid']
        );

        $message .= sprintf(
            "Hostname: %s\nRoot Password: %s\n",
            $params['domain'],
            $params['password']
        );

        $configOpts = $this->whmcs->configOptions();
        foreach ($params['configoptions'] as $optName => $billingVal) {
            $message .= sprintf(
                "%s: %s\n",
                $optName,
                $configOpts[$optName][$billingVal]
            );
        }

        $this->tickets->createAndLogErrors([
            'clientid' => $params['userid'],
            'subject' => 'Server provisioning request',
            'message' => $message,
        ]);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    private function getProductName(array $params)
    {
        return $this->database
            ->table('tblproducts')
            ->select('name')
            ->where('id', $params['pid'])
            ->limit(1)
            ->first()
            ->name
            ;
    }
}
