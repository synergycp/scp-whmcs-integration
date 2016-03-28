<?php

namespace Scp\Whmcs;

class LogFactory
{
    public function activity($msg)
    {
        $msg = $this->getMessage(func_get_args());

        logActivity($msg);
    }

    public function getMessage(array $args)
    {
        $args = func_get_args();
        if (count($args) == 1) {
            return $args[0];
        }

        return call_user_func_array('sprintf', $args);
    }
}
