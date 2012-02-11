# JmikolaAutoLoginBundle

This bundle implements a Symfony2 security firewall listener to authenticate
users based on a single query parameter. This is useful for providing one-click
login functionality in email and newsletter links.

## Compatibility

This bundle's master branch maintains compatibility with Symfony2's master
branch. There is no alternate branch for 2.0.x compatilibity.

## Configuration

This bundle implements a firewall listener, which is configured via the
`jmikola_auto_login` key in your security component's firewall configuration.

### Listener Options

The AutoLoginFactory defines the following listener options:

 * `auto_login_user_provider`: AutoLoginUserProviderInterface service, which
    provides a method to load users by an auto-login token (i.e. query
    parameter). If this service is not defined, the listener's user provider
    will be used by default and an exception will be thrown if the provider does
    not implement the required interface (in addition to UserProviderInterface).
 * `provider`: User provider key. This is a standard option for most security
    listeners. If undefined, the default user provider for the firewall
    is used (see: [SecurityBundle docs][]).
 * `token_param`: The query parameter to be checked for an auto-login token.
    The presence of this query parameter will determine if the auto-login
    listener attempts authentication. In that respect, it is similar to the
    `check_path` option for the form-login listener. If undefined, the option
    defaults to `_al`.

### Security Configuration Examples

Consider the following example, which uses a stock EntityUserProvider:

```yml
services:
    acme.auto_login_user_provider:
        # Assume this class implements AutoLoginUserProviderInterface
        class: Acme\UserBundle\Security\AutoLoginUserProvider

security:
    providers:
        acme_user_provider:
            entity: { class: AcmeUserBundle:User, property: username }
    firewalls:
        main:
            # We need not specify a "provider" for our firewall or listeners,
            # since SecurityBundle will default to the first provider defined.
            jmikola_auto_login:
                auto_login_user_provider: acme.auto_login_user_provider
                token_param: al
```

In this example, we customized the token's query parameter. We also needed to
specify a custom service for `auto_login_user_provider`, since
EntityUserProvider does not implement AutoLoginUserProviderInterface. We could
simplify our configuration by using a custom service for our user provider,
which implements both interfaces:

```yml
services:
    acme.versatile_user_provider:
        # This class implements UserProviderInterface and
        # AutoLoginUserProviderInterface
        class: Acme\UserBundle\Security\VersatileUserProvider

security:
    providers:
        acme_user_provider:
            id: acme.versatile_user_provider
    firewalls:
        main:
            jmikola_auto_login:
                token_param: al
```

### FOSUserBundle Configuration Example

If you are using [FOSUserBundle][], defining a service ID for your user provider
will look familiar. You can easily integrate this bundle with FOSUserBundle by
defining a custom service for `fos_user.user_manager`:

```yml
services:
    acme.user_manager:
        # This class extends the appropriate UserManager from FOSUserBundle
        # and implements AutoLoginUserProviderInterface
        class: Acme\UserBundle\Model\UserManager
        # Note: the remaining service configuration is abridged

fos_user:
    service:
        user_manager: acme.user_manager
```

  [SecurityBundle docs]: http://symfony.com/doc/current/book/security.html#using-multiple-user-providers
  [FOSUserBundle]: https://github.com/FriendsOfSymfony/FOSUserBundle
