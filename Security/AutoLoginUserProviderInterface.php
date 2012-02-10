<?php

namespace Jmikola\AutoLoginBundle\Security;

interface AutoLoginUserProviderInterface
{
    /**
     * Loads the user for the given auto-login token.
     *
     * This method must throw AutoLoginTokenNotFoundException if the user is not
     * found.
     *
     * @param string $autoLoginToken
     * @return UserInterface
     * @throws AutoLoginTokenNotFoundException if the user is not found
     */
    function loadUserByAutoLoginToken($key);
}
