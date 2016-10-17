<?php

namespace Scp\Whmcs\Whmcs;

use Scp\Api\ApiResponse;
use Scp\Server\Server;
use Scp\Whmcs\Server\Provision\ServerProvisioner;
use Scp\Whmcs\Server\ServerFieldsService;
use Scp\Whmcs\Client\ClientService;
use Scp\Whmcs\Api;
use Scp\Whmcs\Server\ServerService;
use Scp\Whmcs\Whmcs\WhmcsConfig;
use Scp\Entity\Entity;
use Scp\Api\ApiKey;
use Scp\Api\ApiSingleSignOn;

/**
 * Handle the buttons that appear on WHMCS.
 */
class WhmcsButtons
{
    /**
     * Internal Identifiers.
     */
    /**
     * @var string
     */
    const CLIENT_ACTIONS = 'ClientAreaCustomButtonArray';
    /**
     * @var string
     */
    const ADMIN_ACTIONS = 'AdminCustomButtonArray';
    /**
     * @var string
     */
    const CLIENT_FUNCTIONS = 'ClientAreaAllowedFunctions';
    /**
     * @var string
     */
    const ADMIN_LOGIN_LINK = 'LoginLink';

    /**
     * @var string
     */
    const MANAGE = 'btn_manage';
    /**
     * @var string
     */
    const PORT_POWER_ON = 'btn_port_power_on';
    /**
     * @var string
     */
    const PORT_POWER_OFF = 'btn_port_power_off';

    /**
     * @var string
     */
    const RESET_BMC = 'btn_reset_bmc';
    /**
     * @var string
     */
    const PXE_BOOT = 'btn_pxe_boot';
    /**
     * @var string
     */
    const POWER_ON = 'btn_power_on';
    /**
     * @var string
     */
    const POWER_OFF = 'btn_power_off';
    /**
     * @var string
     */
    const POWER_RESET = 'btn_power_reset';
    /**
     * @var string
     */
    const POWER_SHUTDOWN = 'btn_power_shutdown';

    /**
     * @var string
     */
    const IPMI_CLIENT_CREATE = 'btn_ipmi_client_create';
    /**
     * @var string
     */
    const IPMI_CLIENT_DELETE = 'btn_ipmi_client_delete';

    /**
     * @var string
     */
    const CREATE_CLIENT = 'btn_create_client';
    /**
     * @var string
     */
    const CHECK_INVENTORY = 'btn_check_inventory';
    /**
     * @var string
     */
    const FILL_DATA = 'btn_fill_data';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var WhmcsConfig
     */
    protected $config;

    /**
     * @var ServerService
     */
    protected $server;

    /**
     * @var ClientService
     */
    protected $clients;

    /**
     * @var ServerProvisioner
     */
    protected $provision;

    /**
     * @var ServerFieldsService
     */
    protected $fields;

    /**
     * @param Api                 $api
     * @param WhmcsConfig         $config
     * @param ClientService       $clients
     * @param ServerService       $server
     * @param ServerProvisioner   $provision
     * @param ServerFieldsService $fields
     */
    public function __construct(
        Api $api,
        WhmcsConfig $config,
        ServerService $server,
        ClientService $clients,
        ServerProvisioner $provision,
        ServerFieldsService $fields
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->fields = $fields;
        $this->server = $server;
        $this->clients = $clients;
        $this->provision = $provision;
    }

    /**
     * @return array
     */
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
     * @return array
     */
    public static function admin()
    {
        return [
            'Create Client' => static::CREATE_CLIENT,
            'Check Inventory' => static::CHECK_INVENTORY,
            'Fill Fields' => static::FILL_DATA,
        ];
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
            /*
            WhmcsEvents::USAGE,
            WhmcsEvents::TERMINATE,
            WhmcsEvents::SUSPEND,
            WhmcsEvents::UNSUSPEND,
            // */
        ];
    }

    /**
     * @return array
     */
    protected function otherActions()
    {
        return [
            'Manage on SynergyCP' => static::MANAGE,
        ];
    }

    /**
     * @param Server $server
     *
     * @return array
     */
    protected function switchActions(Server $server)
    {
        if (!$this->inAdmin && !$server->access()->now->switch) {
            return [];
        }

        return [
            'Port Power On' => static::PORT_POWER_ON,
            'Port Power Off' => static::PORT_POWER_OFF,
        ];
    }

    /**
     * @param Server $server
     *
     * @return array
     */
    protected function ipmiActions(Server $server)
    {
        if (!$this->inAdmin && !$server->access()->now->ipmi) {
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

    /**
     * @param Server $server
     *
     * @return array
     */
    protected function pxeActions(Server $server)
    {
        if (!$this->inAdmin && !$server->access()->now->pxe) {
            return [];
        }

        return [
        ];
    }

    /**
     * @return array
     */
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

            // Admin only.
            static::CREATE_CLIENT => 'createClient',
            static::CHECK_INVENTORY => 'checkInventory',
            static::FILL_DATA => 'fillData',
        ];
    }

    /**
     * @return string
     */
    public function fillData()
    {
        try {
            return $this->fields->fill(
                $this->config->get('serviceid'),
                $this->server->currentOrFail()
            ) ? 'success' : 'Unknown error';
        } catch (\Exception $exc) {
            return $exc->getMessage();
        }
    }

    /**
     * @return string
     */
    public function createClient()
    {
        $this->clients->getOrCreate();

        return 'success';
    }

    /**
     * @return string
     */
    public function checkInventory()
    {
        try {
            $this->provision->check();

            return 'success';
        } catch (\Exception $exc) {
            return $exc->getMessage();
        }
    }

    /**
     * @return string
     */
    public function ipmiClientCreate()
    {
        $this->ipmiControl([
            'add_user' => true,
        ]);

        return 'success';
    }

    /**
     * @return string
     */
    public function ipmiClientDelete()
    {
        $this->ipmiControl([
            'delete_user' => true,
        ]);

        return 'success';
    }

    /**
     * @return string "success" or error message
     */
    public function resetBmc()
    {
        $this->ipmiControl([
            'reset_bmc' => true,
        ]);

        return 'success';
    }

    /**
     * @return string "success" or error message
     */
    public function pxeBoot()
    {
        $this->ipmiControl([
            'pxe_boot' => true,
        ]);

        return 'success';
    }

    /**
     * @return string "success" or error message
     */
    public function powerOn()
    {
        $this->ipmiControl([
            'power' => 'on',
        ]);

        return 'success';
    }

    /**
     * @return string "success" or error message
     */
    public function powerOff()
    {
        $this->ipmiControl([
            'power' => 'off',
        ]);

        return 'success';
    }

    /**
     * @return string "success" or error message
     */
    public function powerReset()
    {
        $this->ipmiControl([
            'power' => 'reset',
        ]);

        return 'success';
    }

    /**
     * @return string "success" or error message
     */
    public function powerShutdown()
    {
        $this->ipmiControl([
            'power' => 'soft',
        ]);

        return 'success';
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

    /**
     * @return string
     */
    public function portPowerOn()
    {
        $this->switchControl([
            'power' => 'on',
        ]);

        return 'success';
    }

    /**
     * @return string
     */
    public function portPowerOff()
    {
        $this->switchControl([
            'power' => 'off',
        ]);

        return 'success';
    }

    /**
     * Manage Single Sign On.
     */
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

    /**
     * Manage as admin Single Sign On.
     */
    public function manageAsAdmin()
    {
        try {
            $server = $this->server->currentOrFail();
        } catch (\RuntimeException $exc) {
            $this->exitWithMessage($exc->getMessage());
        }

        $url = sprintf(
            '%s/admin#/hardware/server/%d',
            $this->api->siteUrl(),
            $server->id
        );

        $this->transferTo($url);
    }

    /**
     * @param string $url
     * @param string $linkText
     */
    protected function transferTo($url, $linkText = 'SynergyCP')
    {
        $this->exitWithMessage(sprintf(
            '<script type="text/javascript">window.location.href="%s"</script>'.
            'Transfer to <a href="%s">%s</a>.',
            $url,
            $url,
            $linkText
        ));
    }

    /**
     * @param string $message
     */
    protected function exitWithMessage($message)
    {
        // Clear output buffer so no other page contents show.
        ob_clean();

        die($message);
    }

    /**
     * @param array $data
     *
     * @return ApiResponse
     */
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

    /**
     * @param array $data
     *
     * @return ApiResponse
     */
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

    /**
     * @return array
     */
    public static function staticFunctions()
    {
        return [
            static::ADMIN_ACTIONS => 'admin',
        ];
    }
}
