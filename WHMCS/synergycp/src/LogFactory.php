<?php

namespace Scp\Whmcs;

class LogFactory
{
    public function activity($msg)
    {
        $msg = $this->getMessage(func_get_args());

        logActivity($msg);
    }

    public function call($module, $action, $data, $raw, $respData, array $replace = [])
    {
        logModuleCall($module, $action, $data, $raw, $respData, $replace);
    }

    public function getMessage(array $args)
    {
        if (count($args) == 1) {
            return $args[0];
        }

        return call_user_func_array('sprintf', $args);
    }
}
