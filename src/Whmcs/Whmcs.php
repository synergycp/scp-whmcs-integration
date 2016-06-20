<?php

namespace Scp\Whmcs\Whmcs;
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
    public function configOptions()
    {
        $params = $this->getParams();
        $results = [];

        $query = "SELECT optval.optionname AS val, opt.optionname AS name
            FROM tblproductconfigoptionssub optval
            JOIN tblproductconfigoptions opt ON opt.id = optval.configid
            JOIN tblproductconfiglinks link ON opt.gid = link.gid
            WHERE link.pid = '$params[pid]'";
        $query = mysql_query($query);

        while ($result = mysql_fetch_array($query)) {
            $name = $result['name'];
            list($billingId, $value) = explode('|', $result['val']);

            if (!is_array($results[$name])) {
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
