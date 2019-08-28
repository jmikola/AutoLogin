<?php

namespace Jmikola\AutoLogin\Http\Firewall;

use Jmikola\AutoLogin\AutoLoginEvents;
use Jmikola\AutoLogin\Authentication\Token\AutoLoginToken;
use Jmikola\AutoLogin\Event\AlreadyAuthenticatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AutoLoginListener
{
    private $authenticationManager;
    private $dispatcher;
    private $logger;
    private $providerKey;
    private $securityContext;
    private $tokenParam;
    private $options;

    /**
     * Constructor.
     *
     * @param TokenStorageInterface                          $securityContext
     * @param AuthenticationManagerInterface                 $authenticationManager
     * @param string                                         $providerKey
     * @param string                                         $tokenParam
     * @param LoggerInterface                                $logger
     * @param EventDispatcherInterface                       $dispatcher
     * @param array                                          $options
     */
    public function __construct($securityContext, AuthenticationManagerInterface $authenticationManager, $providerKey, $tokenParam, LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null, array $options = array())
    {
        if (!$securityContext instanceof TokenStorageInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Argument 1 passed to %s() must be an instance of Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface, %s given',
                __METHOD__,
                is_object($securityContext) ? get_class($securityContext) : gettype($securityContext)
            ));
        }

        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->tokenParam = $tokenParam;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;

        $this->options = $options = array_merge(array(
            'override_already_authenticated' => false,
        ), $options);
    }

    /**
     * @param RequestEvent $event
     */
    public function __invoke(RequestEvent $event)
    {
        $request = $event->getRequest();

        if ( ! ($this->isTokenInRequest($request))) {
            return;
        }

        $tokenParam = $request->get($this->tokenParam);

        /* If the security context has a token, a user is already authenticated.
         * We will dispatch an event with the token parameter so that a listener
         * may track its usage.
         */
        if (null !== $this->securityContext->getToken()) {
            if (null !== $this->dispatcher) {
                $event = new AlreadyAuthenticatedEvent($tokenParam);
                $this->dispatcher->dispatch(AutoLoginEvents::ALREADY_AUTHENTICATED, $event);
            }

            /* By default, ignore the token and return; however, in some cases
             * it may be useful to override the existing token and allow the
             * AutoLogin token to be used to switch users (without requiring
             * the user to first log out).
             */
            if ( ! $this->options['override_already_authenticated']) {
                return;
            }
        }

        try {
            $token = new AutoLoginToken($this->providerKey, $tokenParam);

            /* TODO: This authentication method should be considered the same as
             * remember-me according to AuthenticationTrustResolver. That will
             * entail either refactoring to extend RememberMeToken or adding a
             * service to compose the existing AuthenticationTrustResolver and
             * implement custom logic to respect our own token class.
             */
            if ($authenticatedToken = $this->authenticationManager->authenticate($token)) {
                $this->securityContext->setToken($authenticatedToken);

                if (null !== $this->dispatcher) {
                    $event = new InteractiveLoginEvent($request, $authenticatedToken);
                    $this->dispatcher->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $event);
                }
            }
        } catch (AuthenticationException $e) {
            if (null !== $this->logger) {
                $this->logger->warning(
                    'SecurityContext not populated with auto-login token as the '.
                    'AuthenticationManager rejected the auto-login token created '.
                    'by AutoLoginListener: '.$e->getMessage()
                );
            }
        }
    }

    /**
     * Check the ParameterBags consulted by Request::get() for the token.
     *
     * @param Request $request
     * @return boolean
     */
    private function isTokenInRequest(Request $request)
    {
        return $request->query->has($this->tokenParam) ||
            $request->attributes->has($this->tokenParam) ||
            $request->request->has($this->tokenParam);
    }
}
