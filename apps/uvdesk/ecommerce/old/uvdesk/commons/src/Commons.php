<?php

namespace UVDesk\CommunityPackages\UVDesk\Commons;

use Webkul\UVDesk\ExtensionFrameworkBundle\Definition\Module;

final class Commons extends Module
{
    public function getServices()
    {
        return __DIR__ . "/Resources/config/services.yaml";
    }

    public function getPackageReference() : string
    {
        return CommonsPackage::class;
    }

    public function getApplicationReferences() : array
    {
        return array(Applications\CustomerNotes::class, Applications\Memo::class);
    }
}
