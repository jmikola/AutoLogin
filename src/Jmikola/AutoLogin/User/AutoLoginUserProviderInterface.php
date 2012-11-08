<?php

namespace Jmikola\AutoLogin\User;

interface AutoLoginUserProviderInterface
{
    /**
     * Loads the user for the given auto-login token.
     *
     * This method must throw AutoLoginTokenNotFoundException if the user is not
     * found.
     *
     * @param string $key
     * @return UserInterface
     * @throws AutoLoginTokenNotFoundException if the user is not found
     */
    public function loadUserByAutoLoginToken($key);
}
