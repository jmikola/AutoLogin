<?php

namespace Jmikola\AutoLogin;

final class AutoLoginEvents
{
    /**
     * The ALREADY_AUTHENTICATED event occurs when the token parameter is found
     * in the request and the security context token is not null (i.e. the user
     * is already authenticated).
     *
     * The event listener method receives a
     * Jmikola\AutoLogin\Event\AlreadyAuthenticatedEvent instance.
     *
     * @var string
     */
    const ALREADY_AUTHENTICATED = 'autologin.already_authenticated';
}
