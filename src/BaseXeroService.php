<?php

namespace Dcodegroup\LaravelXeroOauth;

use XeroPHP\Application;

class BaseXeroService
{
    public Application $xeroClient;

    public function __construct(Application $xeroClient)
    {
        $this->xeroClient = $xeroClient;
    }
}
