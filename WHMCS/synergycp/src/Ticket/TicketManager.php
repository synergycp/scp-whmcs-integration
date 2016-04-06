<?php

namespace Scp\Whmcs\Ticket;
use Scp\Whmcs\LogFactory;
use Scp\Whmcs\Whmcs\WhmcsConfig;

class TicketManager
{
    /**
     * @var array
     */
    protected $defaults = [
        'priority' => 'Low',
    //  'clientid' => $client,
    //  'subject' => "Testing Tickets",
    //  'message' => "This is a sample ticket opened by the API as a client",
    //  'customfields' => base64_encode(serialize(array("8"=>"mydomain.com"))),
    ];

    /**
     * @var LogFactory
     */
    protected $log;

    /**
     * @var WhmcsConfig
     */
    protected $config;

    /**
     * @var int|null
     */
    protected $deptId;

    public function __construct(
        LogFactory $log,
        WhmcsConfig $config
    ) {
        $this->log = $log;
        $this->config = $config;
    }

    /**
     * @param array $values
     *
     * @return array the localAPI resulting call.
     *
     * @throws TicketCreationFailed
     */
    public function create(array $values)
    {
        $this->deptId = $this->deptId ?: $this->config->option(WhmcsConfig::TICKET_DEPT);
        $defaults = [
            'deptid' => $this->deptId,
        ];
        $values = array_merge($this->defaults, $defaults, $values);
        $admin = $this->config->option(WhmcsConfig::API_USER);

        $results = localAPI('openticket', $values, $admin);
        if ($results['result'] != 'success') {
            throw new TicketCreationFailed(json_encode($results));
        }

        return $results;
    }

    /**
     * @param array $values
     *
     * @return bool
     */
    public function createAndLogErrors(array $values)
    {
        try {
            $this->create($values);

            return true;
        } catch (TicketCreationFailed $exc) {
            $this->log->activity(
                'SynergyCP: Ticket creation failed %s',
                $exc->getMessage()
            );

            return false;
        }
    }
}
