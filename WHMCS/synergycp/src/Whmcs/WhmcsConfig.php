<?php

namespace Scp\Whmcs\Whmcs;

use Scp\Support\Collection;

class WhmcsConfig
{
    /**
     * Functions
     */
    const FORM = 'ConfigOptions';

    /**
     * Config Options.
     */
    const CPU_BILLING_ID = 1;
    const API_USER = 2;
    const TICKET_DEPT = 3;
    const PXE_ACCESS = 4;
    const IPMI_ACCESS = 5;
    const SWITCH_ACCESS = 6;

    /**
     * @var int
     */
    protected $countOptions = self::SWITCH_ACCESS;

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
        $value = $this->get('configoption'.$key);

        switch ($key) {
        case static::TICKET_DEPT:
            return (string) $this->getDepartmentIdByName($value);
        }

        return $value;
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
        case static::TICKET_DEPT:
            return $config['Ticket Department'] = [
                'Type' => 'dropdown',
                'Description' => 'When provisioning fails due to low inventory, a ticket will be filed on behalf of the client in this support department.',
                'Options' => $this->getDepartmentNames()->implode(','),
            ];
        case static::PXE_ACCESS:
            return $config['PXE Access'] = [
                'Type' => 'yesno',
            ];
        case static::IPMI_ACCESS:
            return $config['IPMI Access'] = [
                'Type' => 'yesno',
            ];
        case static::SWITCH_ACCESS:
            return $config['Switch Access'] = [
                'Type' => 'yesno',
            ];
        }
    }

    protected function getDepartmentNames()
    {
        $admin = $this->option(static::API_USER);
        $results = localAPI('getsupportdepartments', [], $admin);
        $departments = $this->getDepartmentsFromResults($results);
        $getName = function ($department) {
            return $department['name'];
        };

        return with(new Collection($departments))
            ->keyBy('id')
            ->map($getName);
    }

    /**
     * @param  array  $results
     *
     * @return array
     */
    protected function getDepartmentsFromResults(array $results)
    {
        if ($results['result'] != 'success') {
            return [[
                'name' => 'Error: ' . json_encode($results),
            ]];
        }

        return $results['departments']['department'];
    }

    /**
     * @param  string $value
     *
     * @return int
     */
    protected function getDepartmentIdByName($value)
    {
        $escaped = htmlspecialchars($value);

        return $this->getDepartmentNames()->search($escaped);
    }

    public static function functions()
    {
        return [
            static::FORM => 'form',
        ];
    }
}
