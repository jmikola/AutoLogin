<?php

namespace Jmikola\AutoLogin\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Is thrown when a AutoLoginUserProvider cannot find a User based
 * on the url key.
 *
 * @author Jeremy Mikola <jmikola@gmail.com>
 */
class AutoLoginTokenNotFoundException extends AuthenticationException
{
}
