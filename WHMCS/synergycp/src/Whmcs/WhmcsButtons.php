<?php

namespace Scp\Whmcs\Whmcs;

use Scp\Server\Server;
use Scp\Server\ServerRepository;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Client\ClientService;
use Scp\Whmcs\Api;
use Scp\Api\ApiKey;
use Scp\Api\ApiSingleSignOn;

class WhmcsButtons
{
    /**
     * Internal Identifiers
     */
    const CLIENT_ACTIONS = 'ClientAreaCustomButtonArray';
    const MANAGE = 'btn_manage';
    const ADMIN_LOGIN_LINK = 'LoginLink';
    const PORT_POWER_ON = 'btn_port_power_on';
    const PORT_POWER_OFF = 'btn_port_power_off';

    const RESET_BMC = 'btn_reset_bmc';
    const PXE_BOOT = 'btn_pxe_boot';
    const POWER_ON = 'btn_power_on';
    const POWER_OFF = 'btn_power_off';
    const POWER_RESET = 'btn_power_reset';
    const POWER_SHUTDOWN = 'btn_power_shutdown';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Whmcs
     */
    protected $whmcs;

    /**
     * @var ClientService
     */
    protected $clients;

    /**
     * @var ServerRepository
     */
    protected $servers;

    public function __construct(
        Api $api,
        Whmcs $whmcs,
        ClientService $clients,
        ServerRepository $servers
    ) {
        $this->api = $api;
        $this->whmcs = $whmcs;
        $this->clients = $clients;
        $this->servers = $servers;
    }

    public function client()
    {
        $billingId = $this->whmcs->getParams()['serviceid'];
        $server = $this->servers->findByBillingId($billingId);

        $actions = $this->otherActions()
          + $this->switchActions($server)
          + $this->ipmiActions($server)
          + $this->pxeActions($server)
          ;

        return $actions;
    }

    protected function otherActions()
    {
        return [
            'Manage on SynergyCP' => static::MANAGE,
        ];
    }

    protected function switchActions(Server $server)
    {
        if (!$server->switch_access) {
            return [];
        }

        return [
            'Port Power On' => static::PORT_POWER_ON,
            'Port Power Off' => static::PORT_POWER_OFF,
        ];
    }

    protected function ipmiActions(Server $server)
    {
        if (!$server->ipmi_access) {
            return [];
        }

        return [
            'Reset BMC' => static::RESET_BMC,
            'PXE Boot' => static::PXE_BOOT,
            'Power On' => static::POWER_ON,
            'Power Off' => static::POWER_OFF,
            'Power Reset' => static::POWER_RESET,
            'Soft Shutdown' => static::POWER_SHUTDOWN,
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

    public static function functions()
    {
        return [
            static::MANAGE => 'manage',
            static::CLIENT_ACTIONS => 'client',
            static::PORT_POWER_ON => 'portPowerOn',
            static::PORT_POWER_OFF => 'portPowerOff',
            static::ADMIN_LOGIN_LINK => 'loginLink',
            static::RESET_BMC => 'resetBmc',
            static::PXE_BOOT => 'pxeBoot',
            static::POWER_ON => 'powerOn',
            static::POWER_OFF => 'powerOff',
            static::POWER_RESET => 'powerReset',
            static::POWER_SHUTDOWN => 'powerShutdown',
        ];
    }

    /**
     * @return string "success" or error message
     */
    public function resetBmc()
    {
        $this->ipmiControl([
            'reset_bmc' => true,
        ]);

        return "success";
    }

    /**
     * @return string "success" or error message
     */
    public function pxeBoot()
    {
        $this->ipmiControl([
            'pxe_boot' => true,
        ]);

        return "success";
    }

    /**
     * @return string "success" or error message
     */
    public function powerOn()
    {
        $this->ipmiControl([
            'power' => 'on',
        ]);

        return "success";
    }

    /**
     * @return string "success" or error message
     */
    public function powerOff()
    {
        $this->ipmiControl([
            'power' => 'off',
        ]);

        return "success";
    }

    /**
     * @return string "success" or error message
     */
    public function powerReset()
    {
        $this->ipmiControl([
            'power' => 'reset',
        ]);

        return "success";
    }

    /**
     * @return string "success" or error message
     */
    public function powerShutdown()
    {
        $this->ipmiControl([
            'power' => 'soft',
        ]);

        return "success";
    }

    /**
    * Displayed on the view product page of WHMCS Admin.
    */
    public function loginLink()
    {
        if (isset($_GET['login_service'])) {
            $this->manage();
        }

        echo '<a href="?'.$_SERVER['QUERY_STRING'].'&login_service" '
            .'target="blank">Login as Client on SynergyCP</a>';
    }

    public function portPowerOn()
    {
        $this->switchControl([
            'power' => 'on',
        ]);

        return "success";
    }

    public function portPowerOff()
    {
        $this->switchControl([
            'power' => 'off',
        ]);

        return "success";
    }

    public function manage()
    {
        // Clear output buffer so no other page contents show.
        ob_clean();

        $client = $this->clients->getOrCreate();
        $server = $this->getServer();

        // Generate single sign on for client
        $apiKey = with(new ApiKey())->owner($client)->save();
        $sso = new ApiSingleSignOn($apiKey);

        if ($server) {
            $sso->view($server);
        }

        $url = $sso->url();

        die(sprintf(
            '<script type="text/javascript">window.location.href="%s"</script>'.
            'Transfer to <a href="%s">%s</a>.',
            $url,
            $url,
            'SynergyCP'
        ));
    }

    protected function switchControl(array $data)
    {
        $server = $this->getServer();
        $url = sprintf(
            'server/%d/switch/%d',
            $server->id,
            $server->switch_id
        );

        return $this->api->asClient()->patch($url, $data);
    }

    protected function ipmiControl(array $data)
    {
        $server = $this->getServer();
        $url = sprintf(
            'server/%d/ipmi',
            $server->id
        );

        return $this->api->asClient()->patch($url, $data);
    }

    /**
     * @return Server
     *
     * @throws \RuntimeException
     */
    protected function getServer()
    {
        $billingId = $this->whmcs->getParam('serviceid');
        $server = $this->servers->findByBillingId($billingId);

        if (!$server) {
            throw new \RuntimeException(sprintf(
                'Server with billing ID %s does not exist on SynergyCP.',
                $billingId
            ));
        }

        return $server;
    }
}
