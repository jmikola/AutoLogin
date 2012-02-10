<?php

namespace Jmikola\AutoLoginBundle;

use Jmikola\AutoLoginBundle\Security\AutoLoginFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JmikolaAutoLoginBundle extends Bundle
{
    /**
     * @see Symfony\Component\HttpKernel\Bundle\Bundle::build()
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new AutoLoginFactory());
    }
}
