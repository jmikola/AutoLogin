<?php

namespace Jmikola\AutoLoginBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class AutoLoginToken extends AbstractToken
{
    private $key;
    private $providerKey;

    /**
     * Constructor.
     *
     * @param string $providerKey
     * @param string $autoLoginToken
     * @param array  $roles
     */
    public function __construct($providerKey, $key = null, array $roles = array())
    {
        parent::__construct($roles);

        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->providerKey = $providerKey;
        $this->key = $key;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getProviderKey()
    {
        return $this->providerKey;
    }

    /**
     * @see Symfony\Component\Security\Core\Authentication\Token\TokenInterface::getCredentials()
     */
    public function getCredentials()
    {
        return '';
    }

    /**
     * @see Symfony\Component\Security\Core\Authentication\Token\AbstractToken::serialize()
     */
    public function serialize()
    {
        return serialize(array(
            $this->key,
            $this->providerKey,
            parent::serialize(),
        ));
    }

    /**
     * @see Symfony\Component\Security\Core\Authentication\Token\AbstractToken::unserialize()
     */
    public function unserialize($str)
    {
        list($this->key, $this->providerKey, $parentStr) = unserialize($str);
        parent::unserialize($parentStr);
    }
}
