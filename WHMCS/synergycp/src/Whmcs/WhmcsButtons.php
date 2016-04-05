<?php

namespace Scp\Whmcs\Whmcs;

use Scp\Server\Server;
use Scp\Server\ServerRepository;
use Scp\Whmcs\Whmcs\Whmcs;

class WhmcsButtons
{
    /**
     * @var Whmcs
     */
    protected $whmcs;

    /**
     * @var ServerRepository
     */
    protected $servers;

    public function __construct(
        Whmcs $whmcs,
        ServerRepository $servers
    ) {
        $this->whmcs = $whmcs;
        $this->servers = $servers;
    }

    public function client()
    {
        $billingId = $this->whmcs->getParams()['serviceid'];
        $server = $this->servers->findByBillingId($billingId);

        $actions = [
            'Manage on SynergyCP' => 'btn_manage',
        ] + $this->switchActions($server)
          + $this->ipmiActions($server)
          + $this->pxeActions($server)
          ;

        return $actions;
    }

    protected function switchActions(Server $server)
    {
        if (!$server->switch_access) {
            return [];
        }

        return [
            'Port Power On' => 'btn_port_power_on',
            'Port Power Off' => 'btn_port_power_off',
        ];
    }

    protected function ipmiActions(Server $server)
    {
        if (!$server->ipmi_access) {
            return [];
        }

        return [
            'Reset BMC' => 'btn_reset_bmc',
            'PXE Boot' => 'btn_pxe_boot',
            'Power On' => 'btn_power_on',
            'Power Off' => 'btn_power_off',
            'Power Reset' => 'btn_power_reset',
            'Soft Shutdown' => 'btn_power_shutdown',
        ];
    }

    protected function pxeActions(Server $server)
    {
        if (!$server->pxe_access) {
            return [];
        }

        return [
        ];
    }
}
