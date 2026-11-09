<?php

namespace Scp\Whmcs\Whmcs;

use Illuminate\Database\Capsule\Manager as Capsule;
use Scp\Support\Arr;

class Whmcs
{
    const META = 'MetaData';

    /**
     * @var array
     */
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function configOptions() {
      return $this->configOptionsForProductID($this->getParams()['pid']);
    }

    public function configOptionsForProductID($pid) {
      $rows = Capsule::table('tblproductconfigoptionssub as optval')
          ->join('tblproductconfigoptions as opt', 'opt.id', '=', 'optval.configid')
          ->join('tblproductconfiglinks as link', 'opt.gid', '=', 'link.gid')
          ->where('link.pid', (int) $pid)
          ->select('optval.optionname as val', 'opt.optionname as name')
          ->get();

      return static::parseConfigOptionRows($rows);
    }

    /**
     * Group configurable option rows by option name, keyed by billing ID.
     *
     * Each row has a `name` (the option name) and a `val` in the
     * "billing-id|Display Name" format. Rows without a display name use the
     * billing ID for both.
     *
     * @param iterable $rows objects with `name` and `val` properties
     *
     * @return array
     */
    public static function parseConfigOptionRows($rows) {
      $results = [];

      foreach ($rows as $row) {
          $name = $row->name;
          $parts = explode('|', $row->val, 2);
          $billingId = trim($parts[0]);
          $value = isset($parts[1]) ? trim($parts[1]) : $billingId;

          if (!isset($results[$name])) {
              $results[$name] = [];
          }

          $results[$name][$billingId] = $value;
      }

      return $results;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    public function getParam($param, $default = null)
    {
        return Arr::get($this->getParams(), $param, $default);
    }

    /**
     * Define module related meta data.
     *
     * Values returned here are used to determine module related abilities and
     * settings.
     *
     * @see http://docs.whmcs.com/Provisioning_Module_Meta_Data_Parameters
     *
     * @return array
     */
    public static function meta()
    {
        return [
            'DisplayName' => 'Synergy Control Panel',

            // Use WHMCS API Version 1.1
            'APIVersion' => '1.1',

            // Set true if module requires a server to work
            'RequiresServer' => true,

            //'DefaultNonSSLPort' => '1111',
            //'DefaultSSLPort' => '1112',

            // Single Sign On (Where does this show up?)
            //'ServiceSingleSignOnLabel' => 'Login to Synergy',
            //'AdminSingleSignOnLabel' => 'Login to Synergy as Admin',
        ];
    }

    public static function staticFunctions()
    {
        return [
            static::META => 'meta',
        ];
    }
}
