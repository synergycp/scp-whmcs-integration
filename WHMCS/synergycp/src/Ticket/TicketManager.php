<?php

namespace Scp\Whmcs\Ticket;

class TicketManager
{
    /**
     * @var array
     */
    protected $defaults = array(
        'deptid' => "1",
        'priority' => "Low",
    //  'clientid' => $client,
    //  'subject' => "Testing Tickets",
    //  'message' => "This is a sample ticket opened by the API as a client",
    //  'customfields' => base64_encode(serialize(array("8"=>"mydomain.com"))),
    );

    /**
     * @param  array $values
     * @return array the localAPI resulting call.
     *
     * @throws TicketCreationFailed
     */
    public function create(array $values)
    {
        $values = array_merge($this->defaults, $values);

        $results = localAPI("openticket", $values, "admin");
        if ($results['result'] != 'success') {
            throw new TicketCreationFailed();
        }

        return $results;
    }

    /**
     * @param  array  $values
     *
     * @return bool
     */
    public function createAndLogErrors(array $values)
    {
        try {
            $this->create($values);
            return true;
        } catch (TicketCreationFailed $exc) {
            logActivity('SynergyCP: Ticket creation failed ' . $exc->getMessage());
            return false;
        }
    }
}
