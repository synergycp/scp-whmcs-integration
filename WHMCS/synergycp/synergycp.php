<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

use Scp\Api\ApiKey;
use Scp\Api\ApiSingleSignOn;
use Scp\Whmcs\App;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Whmcs\WhmcsConfig;
use Scp\Whmcs\Whmcs\WhmcsButtons;
use Scp\Whmcs\Server\Provision\ServerProvisioner;
use Scp\Whmcs\Client\ClientService;
use Scp\Server\ServerRepository;

require __DIR__.'/bootstrap/autoload.php';

function _synergycp_app(array $params = [])
{
    return App::get($params);
}

/**
 * Server configuration options.
 *
 * @param  array  $params
 *
 * @return array
 */
function synergycp_ConfigOptions(array $params)
{
    return _synergycp_app($params)
        ->resolve(WhmcsConfig::class)
        ->form();
}

/**
 * Triggered on Server Provisioning.
 *
 * @param  array $params
 *
 * @return string
 */
function synergycp_CreateAccount(array $params)
{
    try {
        $server = _synergycp_app($params)
            ->resolve(ServerProvisioner::class)
            ->create();
    } catch (Exception $exc) {
        return $exc->getMessage();
    }

    return "success";
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see http://docs.whmcs.com/Provisioning_Module_Meta_Data_Parameters
 *
 * @return array
 */
function synergycp_MetaData(array $params)
{
    return Whmcs::meta();
}

// TODO: single signon

function synergycp_UsageUpdate(array $params)
{
    //$billingId = $params['serviceid'];
    return 'success';//'Error running usage update.';
    /*$usage = _synergycp_app($params)->resolve(UsageUpdater::class);

    return $usage->runAndLogErrors() ? 'success' : 'Error running usage update';*/
}

/**
 * Displayed on the view product page of WHMCS Admin.
 *
 * @param  array $params
 */
function synergycp_LoginLink(array $params)
{
    if (isset($_GET['login_service'])) {
        synergycp_btn_manage($params);
    }

    echo '<a href="?'.$_SERVER['QUERY_STRING'].'&login_service" '
        .'target="blank">Login as Client on SynergyCP</a>';
}

/**
 * Custom button array that allows users
 * to interact with specified buttons below in client area.
 *
 * TODO: can we route btn_manage etc. into static methods of a class?
 *
 * @param  array $params
 *
 * @return array
 */
function synergycp_ClientAreaCustomButtonArray(array $params)
{
    return _synergycp_app($params)
        ->make(WhmcsButtons::class)
        ->client();
}

function synergycp_btn_manage(array $params)
{
    // Clear output buffer
    ob_clean();

    $client = _synergycp_app($params)
        ->resolve(ClientService::class)
        ->getOrCreate();
    $server = _synergycp_app()
        ->resolve(ServerRepository::class)
        ->findByBillingId($params['serviceid']);

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
