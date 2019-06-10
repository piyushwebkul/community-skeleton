<?php

namespace App\Resources\Routes;

use Webkul\UVDesk\CoreBundle\Routing\RouterInterface;

class CommunityWizard implements RouterInterface
{
    public static function getResourcePath()
    {
        return __DIR__ . "/_routes/wiz/community-installer.yaml";
    }

    public static function getResourceType()
    {
        return RouterInterface::YAML_RESOURCE;
    }
}
