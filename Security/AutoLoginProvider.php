<?php

namespace Jmikola\AutoLoginBundle\Security;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AutoLoginProvider implements AuthenticationProviderInterface
{
    private $autoLoginUserProvider;
    private $providerKey;
    private $userChecker;
    private $userProvider;

    /**
     * Constructor.
     *
     * If $autoLoginUserProvider is null, $userProvider will be used if it
     * implements AutoLoginUserProviderInterface.
     *
     * @param UserProviderInterface          $userProvider
     * @param UserCheckerInterface           $userChecker
     * @param string                         $providerKey
     * @param AutoLoginUserProviderInterface $autoLoginUserProvider
     */
    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey, AutoLoginUserProviderInterface $autoLoginUserProvider = null)
    {
        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;

        if (null === $autoLoginUserProvider) {
            if ($userProvider instanceof AutoLoginUserProviderInterface) {
                $this->autoLoginUserProvider = $userProvider;
            } else {
                throw new \InvalidArgumentException('AutoLoginUserProviderInterface is required and $userProvider is not suitable.');
            }
        } else {
            $this->autoLoginUserProvider = $autoLoginUserProvider;
        }
    }

    /**
     * @see Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::authenticate()
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }

        $user = $this->autoLoginUserProvider->loadUserByAutoLoginToken($token->getKey());
        $this->userChecker->checkPostAuth($user);

        $authenticatedToken = new AutoLoginToken($this->providerKey, null, $user->getRoles());
        $authenticatedToken->setAttributes($token->getAttributes());
        $authenticatedToken->setUser($user);
        $authenticatedToken->setAuthenticated(true);

        return $authenticatedToken;
    }

    /**
     * @see Symfony\Component\Security\Core\Authentication\Provider\uthenticationProviderInterface::supports()
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof AutoLoginToken && $token->getProviderKey() === $this->providerKey;
    }
}
