<?php

namespace Scp\Whmcs\Server\Provision;

use Scp\Server\ServerProvisioner as OriginalServerProvisioner;
use Scp\Whmcs\Ticket\TicketManager;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Whmcs\WhmcsConfig;
use Scp\Whmcs\Client\ClientService;
use Scp\Client\Client;
use Scp\Support\Collection;

class ServerProvisioner
{
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

    public function __construct(
        Whmcs $whmcs,
        WhmcsConfig $config,
        ClientService $client,
        TicketManager $tickets,
        OriginalServerProvisioner $provision
    ) {
        $this->whmcs = $whmcs;
        $this->config = $config;
        $this->client = $client;
        $this->tickets = $tickets;
        $this->provision = $provision;
    }

    /**
     * @param array $params
     *
     * @return Server|null
     */
    public function create()
    {
        $choices = $this->config->options();
        $params = $this->whmcs->getParams();
        $osChoice = $choices['Operating System'];
        $ram = $choices['Memory'];
        $disks = $this->multiChoice($choices, "SSD Bay %d");
        $addons = $this->multiChoice($choices, "Add On %d");

        $cpu = $this->config->option(WhmcsConfig::CPU_BILLING_ID);

        $portSpeed = $choices['Network Port Speed'];
        $ips = $choices['IPv4 Addresses'];
        $ipGroup = $choices['Datacenter Location'];
        $nickname = $params['domain'];
        $password = $params['password'];

        $client = $this->client->getOrCreate();

        $server = $this->provision->server([
            'mem_billing' => $ram,
            'cpu_billing' => $cpu,
            'disks_billing' => $disks,
            'addons_billing' => $addons,
            'ip_group_billing' => $ipGroup,
        ], [
            'ips_billing' => $ips,
            'pxe_profile_billing' => $osChoice,
            'port_speed_billing' => $portSpeed,
            'billing_id' => $this->config->get('serviceid'),
            'nickname' => $nickname,
            'password' => $password,
            'pxe_access' => $this->config->option(WhmcsConfig::PXE_ACCESS),
            'ipmi_access' => $this->config->option(WhmcsConfig::IPMI_ACCESS),
            'switch_access' => $this->config->option(WhmcsConfig::SWITCH_ACCESS),
        ], $client);

        if (!$server) {
            $this->createTicket($params);

            return;
        }

        return $server;
    }

    /**
     * @param  array  $choices
     * @param  string $format  format string for the keys of the field.
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

    private function getProductName(array $params)
    {
        $productId = $params['pid'];

        $query = "SELECT name FROM tblproducts WHERE id = '$productId' LIMIT 1";
        $query = mysql_query($query);
        $result = mysql_fetch_object($query);

        if ($result) {
            return $result->name;
        }
    }
}
