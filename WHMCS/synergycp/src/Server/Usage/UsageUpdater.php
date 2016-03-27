<?php

namespace Scp\Whmcs\Server\Usage;
use Scp\Api\ApiError;
use Scp\Server\ServerQuery;
use Scp\Whmcs\Server\Usage\UsageFormatter;

class UsageUpdater
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var UsageFormatter
     */
    protected $format;

    public function __construct()
    {
        $this->app = App::get();
        $this->format = $this->app->make(UsageFormatter::class);
    }

    /**
     * @return bool
     */
    public function runAndLogErrors()
    {
        try {
            $this->run();
            return true;
        } catch (ApiError $exc) {
            logActivity('SynergyCP: Error running usage update: ' . $exc->getMessage());
            return false;
        }
    }

    public function run()
    {
        // Get bandwidth from SynergyCP
        $servers = new ServerQuery;

        // Update WHMCS DB
        foreach ($resp->result->products as $product) {
            if (empty($product->billing_id))
                continue;

            logActivity('SynergyCP: Updating billing ID ' . $product->billing_id);
            update_query("tblhosting", array(
                //"diskused" => $values['diskusage'],
                //"dislimit" => $values['disklimit'],
                "bwusage" => bits_to_MB($product->bandwidth_used),
                "bwlimit" => bits_to_MB($product->bandwidth_limit, 3),
                "lastupdate" => "now()",
            ), array(
                "id" => $product->billing_id,
            ));
        }

        logActivity('SynergyCP: Completed usage update');

        return "success";
    }
}
