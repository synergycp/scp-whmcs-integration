<?php

namespace Scp\Whmcs\Whmcs;
use Scp\Whmcs\Server\Provision\ServerProvisioner;
use Scp\Whmcs\Server\Usage\UsageUpdater;
use Scp\Whmcs\Server\ServerService;

/**
 * Class Responsibilities:
 *  - Respond to internal WHMCS events by routing them into proper handlers.
 */
class WhmcsEvents
{
    /**
     * The internal WHMCS names of events.
     */
    const PROVISION = 'CreateAccount';
    const USAGE = 'UsageUpdate';

    /**
     * @var UsageUpdater
     */
    protected $usage;

    /**
     * @var ServerService
     */
    protected $server;

    /**
     * @var ServerProvisioner
     */
    protected $provision;

    public function __construct(
        UsageUpdater $usage,
        ServerService $server,
        ServerProvisioner $provision
    ) {
        $this->usage = $usage;
        $this->server = $server;
        $this->provision = $provision;
    }

    /**
     * Triggered on Server Provisioning.
     *
     * @param  array $params
     *
     * @return string
     */
    public function provision()
    {
        try {
            $server = $this->provision->create();

            if (!$server) {
                throw new \Exception(
                    'No Server found in inventory. '.
                    'Provisioning Ticket Created.'
                );
            }
        } catch (\Exception $exc) {
            return $exc->getMessage();
        }

        return 'success';
    }

    public function usage()
    {
        $billingId = $this->server->currentBillingId();

        return $this->usage->runAndLogErrors($billingId)
            ? 'success'
            : 'Error running usage update';
    }

    public static function functions()
    {
        return [
            static::PROVISION => 'provision',
            static::USAGE => 'usage',
        ];
    }
}
