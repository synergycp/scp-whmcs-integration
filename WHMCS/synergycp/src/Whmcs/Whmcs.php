<?php

namespace Scp\Whmcs\Whmcs;

class Whmcs
{
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
    public function meta()
    {
        return [
            'DisplayName' => 'Synergy Control Panel',
            'APIVersion' => '1.1', // Use API Version 1.1
            'RequiresServer' => true, // Set true if module requires a server to work
            //'DefaultNonSSLPort' => '1111', // Default Non-SSL Connection Port
            //'DefaultSSLPort' => '1112', // Default SSL Connection Port
            'ServiceSingleSignOnLabel' => 'Login to Synergy',
            'AdminSingleSignOnLabel' => 'Login to Synergy as Admin',
        ];
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
}
