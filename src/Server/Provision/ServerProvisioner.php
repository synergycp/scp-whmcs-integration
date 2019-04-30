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
     * @var int|null
     */
    private $softRaid;

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
     * @return void|Server
     * @throws \Exception
     */
    public function create()
    {
        $params = $this->whmcs->getParams();
        // TODO: clean this logic up with specific exceptions

        if (!$this->getEntities()) {
            $this->createTicket($params);

            return;
        }

        $choices = $this->config->options();
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

        $this->checkEntities();
    }

    /**
     * @return Entity|void
     *
     * @throws \Exception
     */
    private function checkEntities()
    {
        if ($entities = $this->getEntities()) {
            return $entities;
        }

        throw new \Exception('No matching Entity found in inventory');
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
            ->where('billing_id', $this->getIps())
            ->where('server', 'none')
            ->first()
            ;
    }

    /**
     * @return Server|void
     * @throws \Exception
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
     * @return string
     */
    private function getIps()
    {
        return $this->config->getOption('IPv4 Addresses');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getFilters()
    {
        $choices = $this->config->options();
        $memory = $this->config->getOption('Memory');
        $disks = $this->multiChoice($choices, '/Drive Bay ([0-9]+)(.*)/');
        $addons = $this->addons($this->multiChoice($choices, '/Add On ([0-9]+)/'));

        return [
            'mem_billing' => $memory ?: $this->getConfigValue(WhmcsConfig::MEM_BILLING_ID),
            'cpu_billing' => $this->config->option(WhmcsConfig::CPU_BILLING_ID),
            'disks_billing' => $disks ?: $this->getConfigValues(WhmcsConfig::DISK_BILLING_IDS),
            'addons_billing' => $addons ?: $this->addons($this->getConfigValues(WhmcsConfig::ADDON_BILLING_IDS)),
            'ip_group_billing' => $this->ipGroupChoice(),
        ];
    }

    /**
     * @param string $osChoice
     * @param string $password
     *
     * @return array
     * @throws \Exception
     */
    private function getServerSettings($osChoice, $password)
    {
        $choices = $this->config->options();
        $params = $this->whmcs->getParams();

        $portSpeed = $choices['Network Port Speed'];
        $nickname = $params['domain'];

        return [
            'ips_billing' => $this->getIps(),
            'pxe_profile_billing' => $osChoice,
            'port_speed_billing' => $portSpeed,
            'nickname' => $nickname,
            'password' => $password,
            'server' => [
                'fields' => array_filter([
                    'pkg.ddos.rate-limit' => array_get($choices, 'DDoS Protection'),
                ]),
            ],
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
        $raidLevel = $this->getRaidLevel();
        $choices = new Collection($osChoices);

        $choices->reduce(function ($install, $choice) use ($password, $server, $raidLevel) {
            $install = $install ?: $server->installs()->get()->items()->last();
            $install = $server->installs()->model()->save([
                'pxe_profile_billing' => trim($choice),
                'parent' => [
                    'id' => $install->id,
                ],
                'password' => $password,
                'disk' => [
                    'raid' => $raidLevel,
                ],
            ]);

            return $install;
        });
    }

    /**
     * @param array  $choices
     * @param string $format  regex string for the keys of the field.
     *
     * @return array
     */
    private function multiChoice(array $choices, $format)
    {
        $result = [];

        foreach ($choices as $key => $value) {
            if ($value !== 'None' && preg_match($format, $key)) {
                $result[] = $value;
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

        $presetConfigOptions = array_merge(
            $this->getConfigName(WhmcsConfig::MEM_BILLING_ID, 'Memory') ?: [],
            $this->getConfigNames(WhmcsConfig::DISK_BILLING_IDS, 'Drive Bay') ?: [],
            $this->getConfigNames(WhmcsConfig::ADDON_BILLING_IDS, 'Add On') ?: []
        );

        $configOpts = $this->whmcs->configOptions();
        foreach ($presetConfigOptions as $optionName => $billingVal) {
            if (!array_key_exists($optionName, $configOpts)) {
                $message .= sprintf(
                    "%s: %s\n",
                    $optionName,
                    $presetConfigOptions[$optionName]
                );
            }
        }

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

    /**
     * @return void|int
     */
    private function getRaidLevel()
    {
        return $this->softRaid;
    }

    /**
     * @param array $addons
     *
     * @return array
     */
    private function addons(array $addons)
    {
        return array_filter($addons, function ($addOn) {
            switch ($addOn) {
            case 'ADD-RAID1':
                $this->softRaid = 1;
                break;
            case 'ADD-RAID0':
                $this->softRaid = 0;
                break;
            default:
                return true;
            }

            return false;
        });
    }

    /**
     * @param string $configID
     *
     * @return array
     */
    private function getConfigValues(string $configID)
    {
        if (!$configNamesAndValues = $this->parseConfigValuesAndNames($configID)) {
            return null;
        }

        return $this->parseConfigValues($configNamesAndValues[0]);
    }

    /**
     * @param string $configID
     *
     * @return string
     */
    private function getConfigValue(string $configID)
    {
        if (!$configNamesAndValues = $this->parseConfigValuesAndNames($configID)) {
            return null;
        }

        return trim($configNamesAndValues[0]);
    }

    /**
     * @param string $configID
     * @param string $newKey
     *
     * @return array
     */
    private function getConfigNames(string $configID, string $newKey)
    {
        if (!$configNamesAndValues = $this->parseConfigValuesAndNames($configID)) {
            return null;
        }

        $configNamesOrValues = $configNamesAndValues[1] ?: $configNamesAndValues[0];

        return $this->parseConfigNames($configNamesOrValues, $newKey);
    }

    /**
     * @param string $configID
     * @param string $newKey
     *
     * @return array
     */
    private function getConfigName(string $configID, string $newKey)
    {
        if (!$configNamesAndValues = $this->parseConfigValuesAndNames($configID)) {
            return null;
        }

        $untrimmedConfigName = $configNamesAndValues[1] ?: $configNamesAndValues[0];

        $configName = trim($untrimmedConfigName);

        return [
            $newKey => $configName
        ];
    }

    /**
     * @param string $configValue
     *
     * @return array
     */
    private function parseConfigValues(string $configValue)
    {
        $configValues = array_map('trim', explode(',', $configValue));

        foreach($configValues as $value) {
            $result[] = $value;
        }

        return $result;
    }

    /**
     * @param string $configValue
     * @param string $newKey
     *
     * @return array
     */
    private function parseConfigNames(string $configValue, string $newKey)
    {
        $configValues = array_map('trim', explode(',', $configValue));

        foreach($configValues as $index => $configValue) {
            $key = $newKey . ' ' . ($index + 1);
            $result[$key] = $configValue;
        }

        return $result;
    }

    /**
     * @param string $configID
     *
     * @return array
     */
    private function parseConfigValuesAndNames(string $configID)
    {
        if (!$configValues = $this->config->option($configID)) {
            return null;
        }

        return explode('|', $configValues);
    }
}
