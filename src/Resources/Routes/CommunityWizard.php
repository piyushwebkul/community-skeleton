<?php

namespace App\Resources\Routes;

use Webkul\UVDesk\CoreBundle\Routing\RouterInterface;

class InstallationWizardRoutes implements RouterInterface
{
    public static function getResourcePath()
    {
        return __DIR__ . "/_config/wiz/community-installer.yaml";
    }

    public static function getResourceType()
    {
        return RouterInterface::YAML_RESOURCE;
    }
}
