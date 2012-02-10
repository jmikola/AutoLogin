<?php

namespace Jmikola\AutoLoginBundle\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AutoLoginTokenNotFoundException extends AuthenticationException
{
}
