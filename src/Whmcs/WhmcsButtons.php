<?php

namespace Scp\Whmcs\Whmcs;

use Scp\Server\Server;
use Scp\Server\ServerRepository;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Client\ClientService;
use Scp\Whmcs\Api;
use Scp\Whmcs\Server\ServerService;
use Scp\Api\ApiKey;
use Scp\Api\ApiSingleSignOn;

class WhmcsButtons
{
    /**
     * Internal Identifiers
     */
    const CLIENT_ACTIONS = 'ClientAreaCustomButtonArray';
    const CLIENT_FUNCTIONS = 'ClientAreaAllowedFunctions';
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

    const IPMI_CLIENT_CREATE = 'btn_ipmi_client_create';
    const IPMI_CLIENT_DELETE = 'btn_ipmi_client_delete';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var ClientService
     */
    protected $clients;

    /**
     * @var ServerService
     */
    protected $server;

    public function __construct(
        Api $api,
        ClientService $clients,
        ServerService $server
    ) {
        $this->api = $api;
        $this->server = $server;
        $this->clients = $clients;
    }

    public function client()
    {
        if (!$server = $this->server->current()) {
            return [];
        }

        return $this->otherActions()
          + $this->switchActions($server)
          + $this->ipmiActions($server)
          + $this->pxeActions($server)
          ;
    }

    /**
     * Client actions that are not visible as buttons.
     *
     * @return array
     */
    public function clientOtherActions()
    {
        return [
            static::IPMI_CLIENT_DELETE,
            static::IPMI_CLIENT_CREATE,

            // Debug WHMCS Events:
            // /*
            WhmcsEvents::USAGE,
            WhmcsEvents::TERMINATE,
            WhmcsEvents::SUSPEND,
            WhmcsEvents::UNSUSPEND,
            // */
        ];
    }

    protected function otherActions()
    {
        return [
            'Manage on SynergyCP' => static::MANAGE,
        ];
    }

    protected function switchActions(Server $server)
    {
        if (!$server->switch_access_now) {
            return [];
        }

        return [
            'Port Power On' => static::PORT_POWER_ON,
            'Port Power Off' => static::PORT_POWER_OFF,
        ];
    }

    protected function ipmiActions(Server $server)
    {
        if (!$server->ipmi_access_now) {
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
        if (!$server->pxe_access_now) {
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
            static::CLIENT_FUNCTIONS => 'clientOtherActions',
            static::PORT_POWER_ON => 'portPowerOn',
            static::PORT_POWER_OFF => 'portPowerOff',
            static::ADMIN_LOGIN_LINK => 'loginLink',
            static::RESET_BMC => 'resetBmc',
            static::PXE_BOOT => 'pxeBoot',
            static::POWER_ON => 'powerOn',
            static::POWER_OFF => 'powerOff',
            static::POWER_RESET => 'powerReset',
            static::POWER_SHUTDOWN => 'powerShutdown',
            static::IPMI_CLIENT_CREATE => 'ipmiClientCreate',
            static::IPMI_CLIENT_DELETE => 'ipmiClientDelete',
        ];
    }

    public function ipmiClientCreate()
    {
        $this->ipmiControl([
            'add_user' => true,
        ]);

        return "success";
    }

    public function ipmiClientDelete()
    {
        $this->ipmiControl([
            'delete_user' => true,
        ]);

        return "success";
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
        if (isset($_GET['login_client'])) {
            $this->manage();
        }

        if (isset($_GET['login_admin'])) {
            $this->manageAsAdmin();
        }
        ?>

        <div class="btn-group" style="margin-bottom: 10px">
            <div class="btn-dropdown">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Manage on SynergyCP
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="?<?php echo $_SERVER['QUERY_STRING'] ?>&login_client" target="_blank">As Client</a></li>
                    <li><a href="?<?php echo $_SERVER['QUERY_STRING'] ?>&login_admin" target="_blank">As Administrator</a></li>
                </ul>
            </div>
        </div>

        <?php
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
        $client = $this->clients->getOrCreate();
        $server = $this->getServer();

        // Generate single sign on for client
        $apiKey = with(new ApiKey())->owner($client)->save();
        $sso = new ApiSingleSignOn($apiKey);

        if ($server) {
            $sso->view($server);
        }

        $url = $sso->url();

        $this->transferTo($url);
    }

    public function manageAsAdmin()
    {
        try {
            $server = $this->server->currentOrFail();
        } catch (\RuntimeException $exc) {
            $this->exitWithMessage($exc->getMessage());
        }

        $url = sprintf(
            '%s/admin/servers/manage/%d',
            $this->api->siteUrl(),
            $server->id
        );

        $this->transferTo($url);
    }

    protected function transferTo($url, $linkText = 'SynergyCP')
    {
        $this->exitWithMessage(sprintf(
            '<script type="text/javascript">window.location.href="%s"</script>'.
            'Transfer to <a href="%s">%s</a>.',
            $url,
            $url,
            'SynergyCP'
        ));
    }

    protected function exitWithMessage($message)
    {
        // Clear output buffer so no other page contents show.
        ob_clean();

        die($message);
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
        return $this->server->currentOrFail();
    }
}
