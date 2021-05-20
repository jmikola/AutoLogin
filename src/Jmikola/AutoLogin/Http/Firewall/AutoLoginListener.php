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
use Symfony\Component\Security\Http\Firewall\AbstractListener;

class AutoLoginListener extends AbstractListener
{
    private TokenStorageInterface $tokenStorage;
    private AuthenticationManagerInterface $authenticationManager;
    private string $providerKey;
    private string $tokenParam;
    private ?LoggerInterface $logger = null;
    private ?EventDispatcherInterface $dispatcher = null;
    private array $options;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        string $providerKey,
        string $tokenParam,
        LoggerInterface $logger = null,
        EventDispatcherInterface $dispatcher = null,
        array $options = []
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->tokenParam = $tokenParam;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;

        $this->options = $options = array_merge([
            'override_already_authenticated' => false,
        ], $options);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Request $request): ?bool
    {
        return $request->query->has($this->tokenParam) ||
            $request->attributes->has($this->tokenParam) ||
            $request->request->has($this->tokenParam);
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(RequestEvent $event)
    {
        $request = $event->getRequest();
        $tokenParam = $request->get($this->tokenParam);

        /* If the security context has a token, a user is already authenticated.
         * We will dispatch an event with the token parameter so that a listener
         * may track its usage.
         */
        if (null !== $this->tokenStorage->getToken()) {
            if (null !== $this->dispatcher) {
                $this->dispatcher->dispatch(
                    new AlreadyAuthenticatedEvent($tokenParam),
                    AutoLoginEvents::ALREADY_AUTHENTICATED
                );
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
                $this->tokenStorage->setToken($authenticatedToken);

                if (null !== $this->dispatcher) {
                    $this->dispatcher->dispatch(
                        new InteractiveLoginEvent($request, $authenticatedToken),
                        SecurityEvents::INTERACTIVE_LOGIN
                    );
                }
            }
        } catch (AuthenticationException $e) {
            if (null !== $this->logger) {
                $this->logger->warning(
                    'TokenStorage not populated with auto-login token as the '.
                    'AuthenticationManager rejected the auto-login token created '.
                    'by AutoLoginListener: '.$e->getMessage()
                );
            }
        }
    }
}
