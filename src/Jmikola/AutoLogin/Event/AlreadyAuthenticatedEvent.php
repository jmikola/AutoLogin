<?php

namespace Jmikola\AutoLogin\Event;

use Symfony\Contracts\EventDispatcher\Event;

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
    public function __construct(string $tokenParam)
    {
        $this->tokenParam = $tokenParam;
    }

    /**
     * Return the token parameter.
     *
     * @return string
     */
    public function getTokenParam() : string
    {
        return $this->tokenParam;
    }
}
