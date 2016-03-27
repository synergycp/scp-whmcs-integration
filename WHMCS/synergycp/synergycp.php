<?php

use Scp\Api\ApiKey;
use Scp\Api\ApiSingleSignOn;
use Scp\Whmcs\Ticket\TicketManager;
use Scp\Whmcs\Ticket\TicketCreationFailed;
use Scp\Whmcs\App;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Server\Provision\ServerProvisioner;
use Scp\Whmcs\Client\ClientService;
use Scp\Server\ServerRepository;

if (!defined('WHMCS')) {
    die("This file cannot be accessed directly.");
}

function _synergycp_app(array $params = []) {
    return App::get($params);
}

function synergycp_ConfigOptions() {
    return _synergycp_app()->resolve(Whmcs::class)->configForm();
}

function synergycp_CreateAccount($params) {
    return _synergycp_app()
        ->resolve(ServerProvisioner::class)
        ->create($params)
        ->get();
}

function synergycp_LoginLink($params) {
    if (isset($_GET['login_service']))
        synergycp_btn_manage($params);
    echo '<a href="?' . $_SERVER['QUERY_STRING'] . '&login_service" '
        . 'target="blank">Login as Client</a>';
}

//Custom button array that allows users to interact with specified buttons below in client area.
function synergycp_ClientAreaCustomButtonArray($params) {
    return [
        "Manage on SynergyCP" => "btn_manage",
    ];
}

function synergycp_btn_manage($params) {
    $client = _synergycp_app($params)
        ->resolve(ClientService::class)
        ->getOrCreate();
    $server = _synergycp_app()
        ->resolve(ServerRepository::class)
        ->findByBillingId($params['serviceid']);

    $apiKey = with(new ApiKey)->owner($client)->save();

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

function synergycp_UsageUpdate($params) {
    $usage = _synergycp_app()->resolve(UsageUpdater::class);

    return $usage->runAndLogErrors() ? "success" : "Error running usage update";
}

?>
