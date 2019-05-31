<?php

namespace Webkul\UVDesk\Wizard\Routes;

use Webkul\UVDesk\CoreBundle\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Wizard implements RouterInterface
{
    public static function getResourcePath()
    {
        return __DIR__ . "/../Resources/routes/wizard.yaml";
    }

    public static function getResourceType()
    {
        return RouterInterface::YAML_RESOURCE;
    }
}
