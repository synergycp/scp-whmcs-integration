<?php

namespace Scp\Whmcs\Server;

use Scp\Server\Server;
use Scp\Server\ServerRepository;
use Scp\Whmcs\Whmcs\Whmcs;

class ServerService
{
    /**
     * @var Server|null
     */
    protected $current;

    /**
     * @var Whmcs
     */
    protected $whmcs;

    /**
     * @var ServerRepository
     */
    protected $servers;

    public function __construct(
        Whmcs $whmcs,
        ServerRepository $servers
    ) {
        $this->whmcs = $whmcs;
        $this->servers = $servers;
    }

    public function currentBillingId()
    {
        return $this->whmcs->getParam('serviceid');
    }

    public function current()
    {
        $billingId = $this->currentBillingId();

        return $this->servers->findByBillingId($billingId);
    }

    /**
     * @return Server
     *
     * @throws \RuntimeException
     */
    public function currentOrFail()
    {
        if (!$server = $this->current()) {
            throw new \RuntimeException(sprintf(
                'Server with billing ID %s does not exist on SynergyCP.',
                $this->server->currentBillingId()
            ));
        }

        return $server;
    }
}
