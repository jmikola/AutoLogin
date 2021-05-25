<?php declare(strict_types=1);

namespace Tests\AutoLogin\Http\Firewall;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Jmikola\AutoLogin\Http\Firewall\AutoLoginListener;
use Jmikola\AutoLogin\Authentication\Token\AutoLoginToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AutoLoginListenerTest extends TestCase
{
    private const PROVIDER_KEY = 'test-provider-key';
    private const TOKEN_KEY = 'test-token-param';
    private const TOKEN = 'test-token';

    /**
     * @var MockObject|TokenStorageInterface
     */
    private $tokenStorageMock;

    /**
     * @var MockObject|AuthenticationManagerInterface
     */
    private $authenticationManagerMock;

    protected function setUp(): void
    {
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->authenticationManagerMock = $this->createMock(AuthenticationManagerInterface::class);
    }

    public function testShouldDoNothingIfTokenIsNotInRequest(): void
    {
        $listener = $this->createListener([]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->expectTokenStorageGetTokenNotCalled();
        $this->expectAuthenticationManagerAuthenticateNotCalled();

        $listener->__invoke($event);
    }

    public function testShouldNotOverrideAlreadyAuthenticatedUser(): void
    {
        $listener = $this->createListener([]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request([self::TOKEN_KEY => self::TOKEN]),
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->expectTokenStorageGetToken();
        $this->expectAuthenticationManagerAuthenticateNotCalled();

        $listener->__invoke($event);
    }

    public function testShouldOverrideAlreadyAuthenticatedUser(): void
    {
        $listener = $this->createListener([
            'override_already_authenticated' => true,
        ]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request([self::TOKEN_KEY => self::TOKEN]),
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->expectTokenStorageGetToken();
        $this->expectShouldAuthenticateUser();

        $listener->__invoke($event);
    }

    private function createListener(array $options): AutoLoginListener
    {
        return new AutoLoginListener(
            $this->tokenStorageMock,
            $this->authenticationManagerMock,
            self::PROVIDER_KEY,
            self::TOKEN_KEY,
            null,
            null,
            $options
        );
    }

    private function expectTokenStorageGetTokenNotCalled(): void
    {
        $this->tokenStorageMock
            ->expects(self::never())
            ->method('getToken')
        ;
    }

    private function expectTokenStorageGetToken(): void
    {
        $this->tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class))
        ;
    }

    private function expectAuthenticationManagerAuthenticateNotCalled(): void
    {
        $this->authenticationManagerMock
            ->expects(self::never())
            ->method('authenticate')
        ;
    }

    private function expectShouldAuthenticateUser(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->authenticationManagerMock
            ->expects(self::once())
            ->method('authenticate')
            ->with(self::isInstanceOf(AutoLoginToken::class))
            ->willReturn($token)
        ;

        $this->tokenStorageMock
            ->expects(self::once())
            ->method('setToken')
            ->with($token)
        ;
    }
}
