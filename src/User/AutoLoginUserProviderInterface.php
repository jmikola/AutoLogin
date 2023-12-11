<?php

namespace Jmikola\AutoLogin\User;

use Jmikola\AutoLogin\Exception\AutoLoginTokenNotFoundException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

@trigger_deprecation('jmikola/autologin', '6.0', 'The "%s" class is deprecated, use Symfony Components with AccessTokenAuthenticator instead. (see README.md)', AutoLoginUserProviderInterface::class);

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
    public function loadUserByAutoLoginToken($key) : UserInterface;
}
