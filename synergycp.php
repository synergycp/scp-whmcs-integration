<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

require __DIR__.'/bootstrap/autoload.php';

use Scp\Whmcs\App;
use Scp\Whmcs\Whmcs;

/**
 * Define WHMCS global functions
 *
 * @param string $class
 */
function _synergycp_map_class($class)
{
    foreach ($class::functions() as $name => $method) {
        $fullName = 'synergycp_'.$name;
        eval('function '.$fullName.' (array $params)
        {
            return '.App::class.'::get($params)
                ->make("'.$class.'")
                ->'.$method.'();
        }');
    }
}

function _synergycp_map_static_class($class)
{
   foreach ($class::staticFunctions() as $name => $method) {
       $fullName = 'synergycp_'.$name;
       eval('function '.$fullName.' (array $params)
       {
           return '.$class.'::'.$method.'();
       }');
   }
}

_synergycp_map_class(Whmcs\WhmcsConfig::class);
_synergycp_map_class(Whmcs\WhmcsEvents::class);
_synergycp_map_class(Whmcs\WhmcsButtons::class);
_synergycp_map_class(Whmcs\WhmcsTemplates::class);
_synergycp_map_static_class(Whmcs\Whmcs::class);
