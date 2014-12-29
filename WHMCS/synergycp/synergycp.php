<?php

define('BITS_TO_MB', 1.25 * pow(10, -7));

// http://php.net/manual/en/function.round.php#24379
function round_sig_digits($number, $sigdigs) {
    $multiplier = 1;
    while ($number < 0.1) {
        $number *= 10;
        $multiplier /= 10;
    }
    while ($number >= 1) {
        $number /= 10;
        $multiplier *= 10;
    }
    return round($number, $sigdigs) * $multiplier;
}

function bits_to_MB($bits, $sigdigs = false) {
    if (!$bits) return 0;
    $MB = $bits * BITS_TO_MB;
    return $sigdigs ? round_sig_digits($MB, $sigdigs) : $MB;
}

if (!function_exists('json_last_error_msg')) {
    function json_last_error_msg() {
        static $errors = array(
            JSON_ERROR_NONE             => null,
            JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
            JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        );
        $error = json_last_error();
        return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
    }
}

function synergycp_ConfigOptions() {
    $configarray = array(
        "CPU Billing ID" => array("Type"=>"text", "Size"=>"50", "Description"=>""),
    );
    return $configarray;
}


function array_get($array, $key, $default = false) {
    return empty($array[$key]) ? $default : $array[$key];
}

function array_pick($array, $keys, $default = false) {
    foreach ($keys as $key)
        if (!empty($array[$key]))
            return $array[$key];

    return $default;
}

function get_url($params) {
    // TODO: write tests for this function.

    $hostname = array_get($params, 'serverhostname');
    $api_key = array_get($params, 'serveraccesshash');

    $parsed = parse_url($hostname);
    $path = trim(array_get($parsed, 'path', ''), '/');
    if ($path) $path .= '/';

    $host = array_get($parsed, 'host', '');
    if ($host) $host .= '/';

    return array_get($parsed, 'scheme', 'http') . "://$host$path"
        . "integration.php?api_key=$api_key";
}

function get_response($post, $params) {
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => get_url($params),
        CURLOPT_POST => count($post),
        CURLOPT_POSTFIELDS => http_build_query($post),
        CURLOPT_RETURNTRANSFER => 1,
    ));

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        print "Synergy CP API Request Error: " . curl_error($ch);
        return "";
    }
    curl_close($ch);

    $resp = json_decode($result);
    if (!$resp)
        return "JSON Parse Error: " . json_last_error_msg();

    if (!empty($resp->msgs))
        foreach ($resp->msgs as $msg)
            if ($msg->cat == 'danger')
                return $msg->text;

    if (empty($resp->result))
        $resp->result = "success";
    return $resp;
}

function synergycp_CreateAccount($params) {
    $choices = $params['configoptions'];
    $os = $choices['Operating System'];
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
        'pxe_script' => $os,
        'port_speed' => $port_speed,
        'billing_id' => $params['serviceid'],
    ), $params);

    if (!is_object($resp)) {
        $message = "Your server has been queued for setup and will be processed shortly.\n\n"
            . "Billing ID: $params[serviceid]\n";

        $config_opts = _synergycp_GetConfigOptions($params);
        foreach ($params['configoptions'] as $opt_name => $billing_val)
            $message .= "$opt_name: {$config_opts[$opt_name][$billing_val]}\n";

        _synergycp_OpenTicket(array(
            'clientid' => $params['userid'],
            'subject' => 'Server provisioning request',
            'message' => $message,
        ));

        return $resp;
    }

    return $resp->result;
}

function _synergycp_GetConfigOptions($params) {
    $results = array();
    $q="SELECT optval.optionname AS val, opt.optionname AS name
        FROM tblproductconfigoptionssub optval
        JOIN tblproductconfigoptions opt ON opt.id = optval.configid
        JOIN tblproducts p ON (p.id = '$params[pid]' AND p.gid = opt.gid)";
    $query = mysql_query($q);
    while ($result = mysql_fetch_array($query)) {
        $name = $result['name'];
        list($billing_id, $value) = explode('|', $result['val']);
        if (!is_array($results[$name]))
            $results[$name] = array();
        $results[$name][$billing_id] = $value;
    }

    return $results;
}

function _synergycp_OpenTicket($values) {
    $values = array_merge(array(
        'deptid' => "1",
        'priority' => "Low",
    //  'clientid' => $client,
    //  'subject' => "Testing Tickets",
    //  'message' => "This is a sample ticket opened by the API as a client",
    //  'customfields' => base64_encode(serialize(array("8"=>"mydomain.com"))),
    ), $values);

    $results = localAPI("openticket", $values, "admin");
    if ($results['result'] == 'success')
        return true;

    logActivity('SynergyCP: Ticket creation failed');
    return false;
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
    //print_r($params);
    //die('');

    // Get autologin URL
    $resp = get_response(array(
        'page' => 'client:login_url',
        'user_id' => $params['clientsdetails']['userid'],
        'server_id' => $params['serviceid'],
    ), $params);

    if (!is_object($resp))
        die($resp);

    header("Location: $resp->result");
    die('Transfer to <a href="' . $resp->result . '">SynergyCP</a>.');
}

function synergycp_UsageUpdate($params) {
    $serverip = $params['serverip'];
    $access_key = $params['serveraccesshash'];

    // Get bandwidth from SynergyCP
    $resp = get_response(array(
        'page' => 'server:usage',
    ), $params);

    if (is_object($resp) && empty($resp->result->products))
        $resp = "No products found.";

    if (!is_object($resp)) {
        logActivity($msg = 'SynergyCP: Error running usage update: ' . $resp);
        echo $msg;
        return;
    }

    // Update WHMCS DB
    foreach ($resp->result->products as $product) {
        if (empty($product->billing_id))
            continue;

        logActivity('SynergyCP: Updating billing ID ' . $product->billing_id);
        update_query("tblhosting", array(
            //"diskused" => $values['diskusage'],
            //"dislimit" => $values['disklimit'],
            "bwusage" => bits_to_MB($product->bandwidth_used),
            "bwlimit" => bits_to_MB($product->bandwidth_limit, 3),
            "lastupdate" => "now()",
        ), array(
            "id" => $product->billing_id,
        ));
    }

    logActivity('SynergyCP: Completed usage update');

    return "success";
}

?>
