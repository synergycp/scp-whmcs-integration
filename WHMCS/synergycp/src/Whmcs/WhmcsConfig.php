<?php

namespace Scp\Whmcs\Whmcs;

class WhmcsConfig
{
    /**
     * Config Options.
     */
    const CPU_BILLING_ID = 1;
    const API_USER = 2;

    /**
     * @var int
     */
    protected $countOptions = 2;

    /**
     * @var Whmcs
     */
    protected $whmcs;

    public function __construct(
        Whmcs $whmcs
    ) {
        $this->whmcs = $whmcs;
    }

    public function get($key)
    {
        $params = $this->whmcs->getParams();

        return $params[$key];
    }

    public function option($key)
    {
        return $this->get('configoption'.$key);
    }

    public function options()
    {
        return $this->get('configoptions');
    }

    public function form()
    {
        $config = [];

        for ($i = 1; $i <= $this->countOptions; ++$i) {
            $this->addFormOption($config, $i);
        }

        return $config;
    }

    protected function addFormOption(array &$config, $key)
    {
        switch ($key) {
        case static::CPU_BILLING_ID:
            return $config['CPU Billing ID'] = [
                'Type' => 'text',
                'Size' => '50',
                'Description' => '',
            ];
        case static::API_USER:
            return $config['API User'] = [
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'This must be an administrator user with API access enabled.',
            ];
        }
    }
}
