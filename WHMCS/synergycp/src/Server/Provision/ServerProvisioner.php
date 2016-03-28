<?php

namespace Scp\Whmcs\Server\Provision;

use Scp\Server\ServerProvisioner as OriginalServerProvisioner;
use Scp\Whmcs\Ticket\TicketManager;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Client\ClientService;
use Scp\Client\Client;

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

    public function __construct(
        Whmcs $whmcs,
        ClientService $client,
        TicketManager $tickets,
        OriginalServerProvisioner $provision
    ) {
        $this->whmcs = $whmcs;
        $this->client = $client;
        $this->tickets = $tickets;
        $this->provision = $provision;
    }

    /**
     * @param array $params
     *
     * @return Server|null
     */
    public function create(array $params)
    {
        $choices = $params['configoptions'];
        $osChoice = $choices['Operating System'];
        $ram = $choices['Memory'];
        $hdds = [];

        for ($i = 1; $i <= 8; ++$i) {
            $key = "SSD Bay $i";

            if (!empty($choices[$key]) && $choices[$key] != 'None') {
                $hdds[] = $choices[$key];
            }
        }

        $hdds = ';'.implode(';', $hdds).';';
        $portSpeed = $choices['Port Speed'];
        $ips = $choices['IPv4 Addresses'];
        $cpu = $params['configoption1'];

        $client = $this->client->getOrCreate();

        $server = $this->provision->server([
            'ips' => $ips,
            'ram' => $ram,
            'cpu' => $cpu,
            'hdds' => $hdds,
            'pxe_script' => $osChoice,
            'port_speed' => $portSpeed,
            'billing_id' => $params['serviceid'],
        ], $client);

        if (!$server) {
            $this->createTicket($params);

            return;
        }

        return $server;
    }

    private function createTicket(array $params)
    {
        $message = sprintf(
            "Your server has been queued for setup and will be processed shortly.\n\nBilling ID: %s\n",
            $params['serviceid']
        );

        $configOpts = $this->whmcs->configOptions();
        foreach ($params['configoptions'] as $optName => $billingVal) {
            $message .= "$optName: {$configOpts[$optName][$billingVal]}\n";
        }

        $this->tickets->createAndLogErrors([
            'clientid' => $params['userid'],
            'subject' => 'Server provisioning request',
            'message' => $message,
        ]);
    }
}
