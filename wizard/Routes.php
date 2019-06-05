<?php

namespace Wizard;

use Webkul\UVDesk\CoreBundle\Routing\RouterInterface;

class Routes implements RouterInterface
{
    public static function getResourcePath()
    {
        return __DIR__ . "/Resources/config/routes/wizard.yaml";
    }

    public static function getResourceType()
    {
        return RouterInterface::YAML_RESOURCE;
    }
}
