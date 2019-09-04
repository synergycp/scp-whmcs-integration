<?php

use Scp\Whmcs\App;

class TestCase extends \Scp\TestCase
{
    /**
     * @return App
     */
    protected function newApp()
    {
        return new App(['']);
    }

    /**
     * @return App
     */
    protected function app()
    {
        return App::get(['']);
    }
}
