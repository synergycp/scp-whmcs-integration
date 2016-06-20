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
    const DELETE_ACTION = 7;

    const DELETE_ACTION_WIPE = 0;
    const DELETE_ACTION_TICKET = 1;
    const DELETE_DESCR_WIPE = 'Wipe Server on Synergy';
    const DELETE_DESCR_TICKET = 'Open Cancellation Ticket';

    /**
     * @var array
     */
    protected $deleteActionMap = [
        self::DELETE_DESCR_WIPE => self::DELETE_ACTION_WIPE,
        self::DELETE_DESCR_TICKET => self::DELETE_ACTION_TICKET,
    ];

    /**
     * @var int
     */
    protected $countOptions = self::DELETE_ACTION;

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
        case static::DELETE_ACTION:
            $mapped = array_get($this->deleteActionMap, $value);

            if ($mapped !== null) {
                return $mapped;
            }

            throw new \Exception(sprintf(
                'Invalid value for Delete Action: %s',
                $value
            ));
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
        case static::DELETE_ACTION:
            return $config['Termination Action'] = [
                'Type' => 'dropdown',
                'Description' => 'When a product is terminated, this action will occur.',
                'Options' => implode(',', [
                    static::DELETE_DESCR_WIPE,
                    static::DELETE_DESCR_TICKET,
                ]),
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
