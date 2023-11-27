<?php

namespace guayaquil;

use Exception;

class UnauthorisedException extends Exception
{
    /* @var string */
    public $service;

    public function __construct(string $service)
    {
        parent::__construct();
        $this->service = $service;
    }


}