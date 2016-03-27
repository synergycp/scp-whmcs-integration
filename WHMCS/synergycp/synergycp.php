<?php

use Scp\Whmcs\Ticket\TicketManager;
use Scp\Whmcs\Ticket\TicketCreationFailed;
use Scp\Whmcs\App;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Server\Provision\ServerProvisioner;

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
function synergycp_ClientAreaCustomButtonArray() {
    return array(
        "Manage on SynergyCP" => "btn_manage",
    );
}

function synergycp_btn_manage($params) {
    // Get autologin URL
    $resp = get_response(array(
        'page' => 'client:login_url',
        'user_id' => $params['clientsdetails']['userid'],
        'server_id' => $params['serviceid'],
    ), $params);

    if (!is_object($resp)) {
        die($resp);
    }

    header("Location: $resp->result");
    die('Transfer to <a href="' . $resp->result . '">SynergyCP</a>.');
}

function synergycp_UsageUpdate($params) {
    $usage = _synergycp_app()->resolve(UsageUpdater::class);

    return $usage->runAndLogErrors();
}

?>
