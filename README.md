# AutoLogin

This library implements a Symfony2 security firewall listener to authenticate
users based on a single query parameter. This is useful for providing one-click
login functionality in email and newsletter links.

## Installation

The library is published as a [package][] and is installable via [Composer][]:

```
$ composer require jmikola/auto-login=~1.0
```

  [package]: https://packagist.org/packages/jmikola/auto-login
  [Composer]: http://getcomposer.org/

### Compatibility

This library requires Symfony 2.1 or above. There is no support for Symfony 2.0.

## Usage

This library implements authentication provider and firewall listener classes,
which may be plugged into Symfony2's security component to intercept requests
and automatically authenticate users based on a single request parameter.

To utilize this library in a full-stack Symfony2 application, you may want to
use [JmikolaAutoLoginBundle][]. An example of registering an authentication
provider and firewall listener manually may be found in the
[Silex documentation][] and [Security component documentation][].

  [JmikolaAutoLoginBundle]: http://symfony.com/doc/current/book/security.html#using-multiple-user-providers
  [Silex documentation]: http://silex.sensiolabs.org/doc/providers/security.html#defining-a-custom-authentication-provider
  [security component documentation]: http://symfony.com/doc/current/components/security/firewall.html

### Token

When a user is automatically logged in by a token parameter in the request, they
will be authenticated with an `AutoLoginToken` instance. In the context of
authorization, this token satisfies `IS_AUTHENTICATED_FULLY`. Ideally, it would
be possible to restrict the token to `IS_AUTHENTICATED_REMEMBERED`, but that is
not yet supported. Additional information on these authorization levels may be
found in Symfony2's [authorization documentation][].

  [authorization documentation]: http://symfony.com/doc/current/components/security/authorization.html

### Events

The firewall listener may dispatch events if constructed with an
[event dispatcher][] instance.

  [event dispatcher]: http://symfony.com/doc/current/components/event_dispatcher/introduction.html

#### Interactive Login

Upon successful authentication by a token parameter in the request, an
interactive login core event will be dispatched with the authenticated
`AutoLoginToken` instance.

#### Already Authenticated

*This event was contributed by [Antonio Trapani][] in [PR #9][].*

If a token parameter is present in the request, but the user is already
authenticated, a custom event will be dispatched, which includes the token's
value. After dispatching this event, the listener will return immediately, since
there is no work to be done.

A practical use for this event would be to mark user's email addresses as
confirmed, assuming the auto-login link with the token was only delivered via
email. As a business requirement, the confirmation service might also listen to
the interactive login core event and operate when the authenticated token was an
`AutoLoginToken` instance.

**Note:** Unlike the interactive login event, the token parameter in this event
will not have been validated. It will be the responsibility of the listener to
check whether it matches the currently authenticated user. For this reason, it
may be helpful to inject this library's provider class.

  [Antonio Trapani]: https://github.com/TwistedLogic
  [PR #9]: https://github.com/jmikola/AutoLogin/pull/9
