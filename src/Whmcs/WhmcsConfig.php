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
     * Config Options (make sure to update count below when adding).
     */
    const CPU_BILLING_ID = 1;
    const API_USER = 2;
    const TICKET_DEPT = 3;
    const PXE_ACCESS = 4;
    const IPMI_ACCESS = 5;
    const SWITCH_ACCESS = 6;
    const DELETE_ACTION = 7;
    const PRE_INSTALL = 8;
    const MEM_BILLING_ID = 9;
    const DISK_BILLING_IDS = 10;
    const ADDON_BILLING_IDS = 11;

    /**
     * The 1-based index of the last Config Option.
     *
     * @var int
     */
    protected $countOptions = self::ADDON_BILLING_IDS;

    const API_USER_DESC = 'This must be an administrator user with API access enabled.';
    const TICKET_DEPT_DESC = 'When provisioning fails due to low inventory, a ticket will be filed on behalf of the client in this support department.';
    const DELETE_ACTION_DESC = 'When a product is terminated, this action will occur.';
    const PRE_INSTALL_DESC = 'Billing ID of an OS Reload that will be run before each install, e.g. format-quick. Multiple can be separated by a comma.';
    const MEM_BILLING_DESC = 'Optional preset Billing ID of the RAM. This field is overrided when the \'Memory\' Configurable Option is present. ex: mem-1|8 GB RAM';
    const DISK_BILLING_DESC = 'Optional preset Billing ID of the Hard Disks. Multiple can be separated by commas. This field is overrided when the \'Drive Bay\' Configurable Options are present. ex: disk-1, disk-2|1 TB HDD, 2 TB HDD';
    const ADDON_BILLING_DESC = 'Optional preset Billing ID of the Addons. Multiple can be separated by commas. This field is overrided when the \'Add On\' Configurable Options are present. ex: add-1, add-2|Addon 1, Addon 2';

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

    /**
     * @return mixed
     */
    public function getOption($option)
    {
        return $this->options()[$option];
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
                'Description' => static::API_USER_DESC,
            ];
        case static::TICKET_DEPT:
            return $config['Ticket Department'] = [
                'Type' => 'dropdown',
                'Description' => static::TICKET_DEPT_DESC,
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
            return $config['Switch Port Power Access'] = [
                'Type' => 'yesno',
            ];
        case static::DELETE_ACTION:
            return $config['Termination Action'] = [
                'Type' => 'dropdown',
                'Description' => static::DELETE_ACTION_DESC,
                'Options' => implode(',', [
                    static::DELETE_DESCR_WIPE,
                    static::DELETE_DESCR_TICKET,
                ]),
            ];
        case static::PRE_INSTALL:
            return $config['Pre-OS install'] = [
                'Type' => 'text',
                'Size' => '50',
                'Description' => self::PRE_INSTALL_DESC,
            ];
        case static::MEM_BILLING_ID:
            return $config['MEM Billing ID'] = [
                'Type' => 'text',
                'Size' => '50',
                'Description' => self::MEM_BILLING_DESC,
            ];
        case static::DISK_BILLING_IDS:
            return $config['Disk Billing IDs'] = [
                'Type' => 'text',
                'Size' => '50',
                'Description' => self::DISK_BILLING_DESC,
            ];
        case static::ADDON_BILLING_IDS:
            return $config['Addon Billing IDs'] = [
                'Type' => 'text',
                'Size' => '100',
                'Description' => self::ADDON_BILLING_DESC,
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
