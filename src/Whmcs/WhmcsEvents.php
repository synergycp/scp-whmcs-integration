<?php

namespace Scp\Whmcs\Whmcs;

use Scp\Server;
use Scp\Whmcs\Server\Provision\ServerProvisioner;
use Scp\Whmcs\Server\Usage\UsageUpdater;
use Scp\Whmcs\Server\ServerService;
use Scp\Whmcs\LogFactory;
use Scp\Whmcs\Ticket\TicketManager;
use Scp\Whmcs\Database\Database;

/**
 * Class Responsibilities:
 *  - Respond to internal WHMCS events by routing them into proper handlers.
 */
class WhmcsEvents
{
    // The internal WHMCS names of events.
    // TODO: move to interface

    /**
     * @var string
     */
    const PROVISION = 'CreateAccount';

    /**
     * @var string
     */
    const TERMINATE = 'TerminateAccount';

    /**
     * @var string
     */
    const SUSPEND = 'SuspendAccount';

    /**
     * @var string
     */
    const UNSUSPEND = 'UnsuspendAccount';

    /**
     * @var string
     */
    const USAGE = 'UsageUpdate';

    /**
     * @var string
     */
    const SUCCESS = 'success';

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

    /**
     * @var Database
     */
    protected $database;

    /**
     * @param Database          $database
     * @param LogFactory        $log
     * @param WhmcsConfig       $config
     * @param UsageUpdater      $usage
     * @param ServerService     $server
     * @param TicketManager     $ticket
     * @param ServerProvisioner $provision
     */
    public function __construct(
        Database $database,
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
        $this->database = $database;
        $this->provision = $provision;
    }

    /**
     * Triggered on Server Provisioning.
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

        return static::SUCCESS;
    }

    /**
     * Run the usage update function.
     *
     * @return string
     */
    public function usage()
    {
        return $this->usage->runAndLogErrors()
            ? static::SUCCESS
            : 'Error running usage update';
    }

    /**
     * Terminate an account, logging and returning any errors that occur.
     *
     * @return string
     */
    public function terminate()
    {
        try {
            return $this->doDeleteAction();
        } catch (\Exception $exc) {
            $this->logException($exc, __FUNCTION__);

            return $exc->getMessage();
        }
    }

    /**
     * Triggered on a Suspension event.
     *
     * @return string
     */
    public function suspend()
    {
        try {
            $server = $this->server->currentOrFail();

            try {
                $this->createSuspensionTicket(
                    // TODO: differentiate between auto and regular suspend.
                    // TODO: get suspension reason
                    $server->autoSuspend("See WHMCS")
                );

                return static::SUCCESS;
            } catch (Server\Exceptions\AutoSuspendIgnored $exc) {
                $this->createVipSuspensionTicket($server);

                return static::SUCCESS;
            }
        } catch (\Exception $exc) {
            $this->logException($exc, __FUNCTION__);

            return $exc->getMessage();
        }
    }

    /**
     * Triggered on an Unsuspension event.
     *
     * @return string
     */
    public function unsuspend()
    {
        try {
            $this->server
                ->currentOrFail()
                ->unsuspend()
                ;

            return static::SUCCESS;
        } catch (\Exception $exc) {
            $this->logException($exc, __FUNCTION__);

            return $exc->getMessage();
        }
    }

    /**
     * @param \Exception $exc
     * @param string     $action
     */
    private function logException(\Exception $exc, $action)
    {
        $this->log->activity(
            'SynergyCP: error during %s: %s',
            $action,
            $exc->getMessage()
        );
    }

    /**
     * @return array
     */
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

    /**
     * Delete the current server using the action chosen in settings.
     * @return string
     * @throws \Exception
     */
    protected function doDeleteAction()
    {
        switch ($act = $this->config->option(WhmcsConfig::DELETE_ACTION)) {
        case WhmcsConfig::DELETE_ACTION_WIPE:
            try {
                $server = $this->server->currentOrFail();

                try {
                    // TODO: differentiate between auto and regular suspend.
                    $server->autoWipe();
                    $this->wipeProductDetails();


                    return static::SUCCESS;
                } catch (Server\Exceptions\AutoWipeIgnored $exc) {
                    $this->createVipTerminationTicket($server);

                    return static::SUCCESS;
                }
            } catch (\Exception $exc) {
                $this->logException($exc, __FUNCTION__);

                return $exc->getMessage();
            }
            break;
        case WhmcsConfig::DELETE_ACTION_TICKET:
            $this->createCancellationTicket();
            break;
        default:
            $msg = sprintf(
                'Unhandled delete action: %s',
                $act
            );

            throw new \RuntimeException($msg);
        }

        return static::SUCCESS;
    }

    /**
     * Remove the current service's product details from the database.
     */
    protected function wipeProductDetails()
    {
        $serviceId = $this->config->get('serviceid');
        $entry = $this->database
            ->table('tblhosting')
            ->select('domain')
            ->where('id', $serviceId)
            ->first();
        $updated = false;
        if ($entry) {
          $updated = $this->database
            ->table('tblhosting')
            ->where('id', $serviceId)
            ->update([
              'dedicatedip' => '',
              'assignedips' => '',
              'domain' => static::domainForTerminatedServer($entry->domain),
            ]);
        }

        $this->log->activity(
            '%s service ID: %s during termination',
            $updated ? 'Successfully updated' : 'Failed to update',
            $serviceId
        );
    }

    public static function domainForTerminatedServer($domain) {
      return preg_replace('/ ((&lt;)|<).+((&gt;)|>)$/', '', $domain ?: '');
    }

    /**
     * Run the create cancellation ticket delete action.
     */
    protected function createCancellationTicket()
    {
        $message = sprintf(
            'Server with billing ID %d has been terminated.',
            $this->server->currentBillingId()
        );

        $this->ticket->createAndLogErrors([
            'clientid' => $this->config->get('userid'),
            'subject' => 'Server Termination',
            'message' => $message,
        ]);
    }

    /**
     * Run the create cancellation ticket delete action.
     */
    protected function createSuspensionTicket()
    {
        $message = sprintf(
            'Server with billing ID %d has been suspended.',
            $this->server->currentBillingId()
        );

        $this->ticket->createAndLogErrors([
            'clientid' => $this->config->get('userid'),
            'subject' => 'Server Suspension',
            'message' => $message,
        ]);
    }

    /**
     * Run the create cancellation ticket delete action.
     */
    protected function createVipSuspensionTicket()
    {
        $message = sprintf(
            'This is a notice that the server with billing ID %d is pending suspension. We will not suspend any services on your account automatically, so this ticket will be manually reviewed before processing.',
            $this->server->currentBillingId()
        );

        $this->ticket->createAndLogErrors([
            'clientid' => $this->config->get('userid'),
            'subject' => 'Pending Server Suspension',
            'message' => $message,
        ]);
    }

    /**
     * Run the create cancellation ticket delete action.
     */
    protected function createVipTerminationTicket()
    {
        $message = sprintf(
            'This is a notice that the server with billing ID %d is pending termination. We will not terminate any services on your account automatically, so this ticket will be manually reviewed before processing.',
            $this->server->currentBillingId()
        );

        $this->ticket->createAndLogErrors([
            'clientid' => $this->config->get('userid'),
            'subject' => 'Pending Server Termination',
            'message' => $message,
        ]);
    }
}
