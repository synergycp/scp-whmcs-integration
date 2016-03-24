<?php

namespace Scp\Whmcs\Server\Provision;

use Scp\Server\ServerProvisioner as OriginalServerProvisioner;
use Scp\Whmcs\App;
use Scp\Whmcs\Ticket\TicketManager;
use Scp\Whmcs\Whmcs\Whmcs;

class ServerProvisioner
{
    /**
     * @var TicketManager
     */
    protected $tickets;

    /**
     * @var Whmcs
     */
    protected $whmcs;

    public function __construct()
    {
        $app = App::get();
        $this->tickets = $app->resolve(TicketManager::class);
        $this->whmcs = $app->resolve(Whmcs::class);
    }

    /**
     * @param  array  $params
     *
     * @return OriginalServerProvisioner
     */
    public function create(array $params)
    {
        $choices = $params['configoptions'];
        $osChoice = $choices['Operating System'];
        $ram = $choices['Memory'];
        $hdds = array();
        for ($i = 1; $i <= 8; $i++) {
            $key = "SSD Bay $i";
            if (!empty($choices[$key]) && $choices[$key] != 'None')
                $hdds[] = $choices[$key];
        }
        $hdds = ';' . implode(';', $hdds) . ';';
        $port_speed = $choices['Port Speed'];
        $ips = $choices['IPv4 Addresses'];
        $cpu = $params['configoption1'];

        $resp = get_response(array(
            'page' => 'server:create',

            // Client information
            'user_email' => $params['clientsdetails']['email'],
            'user_first' => $params['clientsdetails']['firstname'],
            'user_last' => $params['clientsdetails']['lastname'],
            'user_id' => $params['userid'],

            // Provisioning Information
            'ips' => $ips,
            'ram' => $ram,
            'cpu' => $cpu,
            'hdds' => $hdds,
            'pxe_script' => $osChoice,
            'port_speed' => $port_speed,
            'billing_id' => $params['serviceid'],
        ), $params);

        if (!is_object($resp)) {
            $this->createTicket($params);

            return false;
        }

        return $resp->result;
    }

    private function createTicket(array $params)
    {
        $message = sprintf(
            "Your server has been queued for setup and will be processed shortly.\n\nBilling ID: %s\n",
            $params['serviceid']
        );

        $config_opts = $this->whmcs->configOptions();
        foreach ($params['configoptions'] as $opt_name => $billing_val)
            $message .= "$opt_name: {$config_opts[$opt_name][$billing_val]}\n";

        $this->tickets->createAndLogErrors(array(
            'clientid' => $params['userid'],
            'subject' => 'Server provisioning request',
            'message' => $message,
        ));
    }
}
