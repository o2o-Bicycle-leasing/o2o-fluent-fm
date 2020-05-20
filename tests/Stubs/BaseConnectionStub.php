<?php

namespace Illuminate\Support\Facades;

use o2o\FluentFM\Connection\BaseConnection;

class BaseConnectionStub extends BaseConnection
{
    public function __construct()
    {
        try {
            parent::__construct(['host' => 'host', 'file' => 'file'], null);
        } catch (\Exception $e) {
        }
    }
}
