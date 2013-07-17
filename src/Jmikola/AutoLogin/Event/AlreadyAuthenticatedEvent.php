<?php

namespace Jmikola\AutoLogin\Event;

use Symfony\Component\EventDispatcher\Event;

class AlreadyAuthenticatedEvent extends Event
{
    /**
     * @var string
     */
    protected $tokenParam;

    /**
     * @param string $tokenParam
     */
    public function __construct($tokenParam)
    {
        $this->tokenParam = $tokenParam;
    }

    /**
     * @return string
     */
    public function getTokenParam()
    {
        return $this->tokenParam;
    }
}
