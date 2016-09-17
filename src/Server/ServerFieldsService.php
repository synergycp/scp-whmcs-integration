<?php

namespace Scp\Whmcs\Server;

use Scp\Server\Server;
use Scp\Support\Collection;
use Scp\Whmcs\Database\Database;
use Scp\Whmcs\Whmcs\WhmcsConfig;

class ServerFieldsService
{
    /**
     * @var WhmcsConfig
     */
    protected $config;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @param Database    $database
     * @param WhmcsConfig $config
     */
    public function __construct(
        Database $database,
        WhmcsConfig $config
    ) {
        $this->config = $config;
        $this->database = $database;
    }

    /**
     * @param int    $serviceId
     * @param Server $server
     *
     * @return bool
     */
    public function fill($serviceId, Server $server)
    {
        $domain = sprintf(
            '%s &lt;%s&gt;',
            $server->nickname,
            $server->srv_id
        );
        $fields = [
            'domain' => $domain,
            'dedicatedip' => $this->primaryAddr($server) ?: '',
            'assignedips' => $this->assignedIps($server),
        ];

        return $this->database
            ->table('tblhosting')
            ->where('id', $serviceId)
            ->update($fields)
            ;
    }

    /**
     * @param Server $server
     *
     * @return string|void
     */
    private function primaryAddr(Server $server)
    {
        if (!$entity = $server->entities()->first()) {
            return;
        }

        return $entity->address;
    }

    /**
     * @param Server $server
     *
     * @return string
     */
    private function assignedIps(Server $server)
    {
        $entities = $server->entities();
        $addAllocation = function (&$result, $entity) {
            $result .= sprintf("IP Allocation\t%s\n", $entity->name);

            if ($entity->gateway) {
                $result .= sprintf("- Usable IP(s)\t%s\n", $entity->full_ip);
                $result .= sprintf("- Gateway IP\t%s\n", $entity->gateway);
                $result .= sprintf("- Subnet Mask\t%s\n", $entity->subnet_mask);
            }

            if ($entity->v6_gateway) {
                $result .= sprintf("- IPv6 Address\t%s\n", $entity->v6_address);
                $result .= sprintf("- IPv6 Gateway\t%s\n", $entity->v6_gateway);
            }

            return $result .= "\n";
        };

        return $entities->reduce($addAllocation, '');
    }
}
