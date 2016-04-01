<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

use Scp\Api\ApiKey;
use Scp\Api\ApiSingleSignOn;
use Scp\Whmcs\App;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Server\Provision\ServerProvisioner;
use Scp\Whmcs\Client\ClientService;
use Scp\Server\ServerRepository;

require __DIR__.'/bootstrap/autoload.php';

function _synergycp_app(array $params = [])
{
    return App::get($params);
}

function synergycp_ConfigOptions()
{
    return _synergycp_app()->resolve(Whmcs::class)->configForm();
}

function synergycp_CreateAccount($params)
{
    try {
        $server = _synergycp_app($params)
            ->resolve(ServerProvisioner::class)
            ->create($params);
    } catch (Exception $exc) {
        return $exc->getMessage();
    }

    return "success (fail for testing)";
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
function synergycp_MetaData()
{
    return [
        'DisplayName' => 'Synergy Control Panel',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
        //'DefaultNonSSLPort' => '1111', // Default Non-SSL Connection Port
        //'DefaultSSLPort' => '1112', // Default SSL Connection Port
        'ServiceSingleSignOnLabel' => 'Login to Synergy',
        'AdminSingleSignOnLabel' => 'Login to Synergy as Admin',
    ];
}


function synergycp_UsageUpdate($params)
{
    //$billingId = $params['serviceid'];
    return 'Error running usage update.';
    /*$usage = _synergycp_app($params)->resolve(UsageUpdater::class);

    return $usage->runAndLogErrors() ? 'success' : 'Error running usage update';*/
}

/**
 * TODO: usage?
 *
 * @param  array $params
 */
function synergycp_LoginLink($params)
{
    if (isset($_GET['login_service'])) {
        synergycp_btn_manage($params);
    }

    echo '<a href="?'.$_SERVER['QUERY_STRING'].'&login_service" '
        .'target="blank">Login as Client</a>';
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
function synergycp_ClientAreaCustomButtonArray($params)
{
    return [
        'Manage on SynergyCP' => 'btn_manage',
    ];
}

function synergycp_btn_manage($params)
{
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

    header("Location: $url");
    die(sprintf(
        'Transfer to <a href="%s">SynergyCP</a>.',
        $url
    ));
}
