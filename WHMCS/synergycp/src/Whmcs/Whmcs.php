<?php

namespace Scp\Whmcs\Whmcs;

class Whmcs
{
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function configForm()
    {
        $configarray = [
            'CPU Billing ID' => [
                'Type' => 'text',
                'Size' => '50',
                'Description' => '',
            ],
        ];

        return $configarray;
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
            JOIN tblproducts p ON (p.id = '$params[pid]' AND p.gid = opt.gid)";
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

    public function getParams()
    {
        return $this->params;
    }
}
