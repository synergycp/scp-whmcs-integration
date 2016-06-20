<?php

namespace Scp\Whmcs\Whmcs;
use Scp\Whmcs\Server\Provision\ServerProvisioner;
use Scp\Whmcs\Server\Usage\UsageUpdater;
use Scp\Whmcs\Server\ServerService;
use Scp\Whmcs\LogFactory;
use Scp\Whmcs\Whmcs\WhmcsConfig;
use Scp\Whmcs\Ticket\TicketManager;

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
    const TERMINATE = 'TerminateAccount';
    const SUSPEND = 'SuspendAccount';
    const UNSUSPEND = 'UnsuspendAccount';
    const USAGE = 'UsageUpdate';

    /**
     * @var LogFactory`
     */
    protected $log;

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

    /**
     * @var WhmcsConfig
     */
    protected $config;

    /**
     * @var TicketManager
     */
    protected $ticket;

    public function __construct(
        LogFactory $log,
        WhmcsConfig $config,
        UsageUpdater $usage,
        ServerService $server,
        TicketManager $ticket,
        ServerProvisioner $provision
    ) {
        $this->log = $log;
        $this->usage = $usage;
        $this->config = $config;
        $this->server = $server;
        $this->ticket = $ticket;
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


    public function terminate()
    {
        try {
            switch ($act = $this->config->option(WhmcsConfig::DELETE_ACTION)) {
            case WhmcsConfig::DELETE_ACTION_WIPE:
                $this->server->current()->wipe();

                return 'success';
            case WhmcsConfig::DELETE_ACTION_TICKET:
                $this->createCancellationTicket();

                return 'success';
            }

            throw new \RuntimeException(sprintf(
                'Unhandled delete action: %s',
                $act
            ));
        } catch (\Exception $exc) {
            $this->logException($exc, __FUNCTION__);

            return $exc->getMessage();
        }
    }

    protected function createCancellationTicket()
    {
        $message = sprintf(
            'Server with billing ID %d has been terminated.',
            $this->server->currentBillingId()
        );

        $this->ticket->create([
            'clientid' => $this->config->get('userid'),
            'subject' => 'Server Cancellation',
            'message' => $message,
        ]);
    }

    public function suspend()
    {
        try {
            $this->server->current()->suspend();
        } catch (\Exception $exc) {
            $this->logException($exc, __FUNCTION__);

            return $exc->getMessage();
        }

        return 'success';
    }

    public function unsuspend()
    {
        try {
            $this->server->current()->unsuspend();
        } catch (\Exception $exc) {
            $this->logException($exc, __FUNCTION__);

            return $exc->getMessage();
        }

        return 'success';
    }

    private function logException(\Exception $exc, $action)
    {
        $this->log->activity(
            'SynergyCP: error during %s: %s',
            $action,
            $exc->getMessage()
        );
    }

    public static function functions()
    {
        return [
            static::PROVISION => 'provision',
            static::USAGE => 'usage',
            static::TERMINATE => 'terminate',
            static::SUSPEND => 'suspend',
            static::UNSUSPEND => 'unsuspend',
        ];
    }
}
