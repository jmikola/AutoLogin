<?php

namespace Jmikola\AutoLogin\Http\Firewall;

use Jmikola\AutoLogin\AutoLoginEvents;
use Jmikola\AutoLogin\Authentication\Token\AutoLoginToken;
use Jmikola\AutoLogin\Event\AlreadyAuthenticatedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class AutoLoginListener implements ListenerInterface
{
    private $authenticationManager;
    private $dispatcher;
    private $logger;
    private $providerKey;
    private $securityContext;
    private $tokenParam;
    private $options;

    /**
     * Constructor
     *
     * @param SecurityContextInterface       $securityContext
     * @param AuthenticationManagerInterface $authenticationManager
     * @param string                         $providerKey
     * @param string                         $tokenParam
     * @param LoggerInterface                $logger
     * @param EventDispatcherInterface       $dispatcher
     * @param array                          $options
     */
    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, $providerKey, $tokenParam, LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null, array $options = array())
    {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->tokenParam = $tokenParam;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->options = $options;
    }

    /**
     * @see Symfony\Component\Security\Http\Firewall\ListenerInterface::handle()
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ( ! ($this->isTokenInRequest($request))) {
            return;
        }

        $tokenParam = $request->get($this->tokenParam);

        /* If the security context has a token, a user is already authenticated
         * so we dispatch an event with the token parameter so that a listener may 
         * track its usage.
         */
        if (null !== $this->securityContext->getToken()) {
            if (null !== $this->dispatcher) {
                $event = new AlreadyAuthenticatedEvent($tokenParam);
                $this->dispatcher->dispatch(AutoLoginEvents::ALREADY_AUTHENTICATED, $event);
            }
            
            if ( ! array_key_exists('on_already_authenticated', $this->options) or 
                 $this->options['on_already_authenticated'] !== 'override' ) {
                /* The default behavior is ignore the token by exiting the function.
                 * But in some cases, it can be useful to overide the authentication
                 * without forcing the user to logout.
                 */
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
                $this->logger->warn(
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
