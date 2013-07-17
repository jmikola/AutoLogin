<?php

namespace Jmikola\AutoLogin\Event;

use Symfony\Component\EventDispatcher\Event;

class AlreadyAuthenticatedEvent extends Event
{
    /**
     * The token parameter from the request.
     *
     * @var string
     */
    private $tokenParam;

    /**
     * Constructor.
     *
     * @param string $tokenParam
     */
    public function __construct($tokenParam)
    {
        $this->tokenParam = $tokenParam;
    }

    /**
     * Return the token parameter.
     *
     * @return string
     */
    public function getTokenParam()
    {
        return $this->tokenParam;
    }
}
