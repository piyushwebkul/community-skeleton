<?php

namespace UVDesk\CommunityPackages\UVDesk\Commons\Applications;

use Webkul\UVDesk\ExtensionFrameworkBundle\Definition\ApplicationMetadata;

class MemoMetadata extends ApplicationMetadata
{
    public function getName() : string
    {
        return "Memo";
    }

    public function getSummary() : string
    {
        return "Add important memos to support tickets accessible to all agents";
    }

    public function getDescription() : string
    {
        return "Write memos pertaining to customers which will be visible to all agents across your helpdesk.";
    }

    public function getQualifiedName() : string
    {
        return "memo";
    }

    public function getDashboardTemplate() : string
    {
        return '@_uvdesk_extension_uvdesk_commons/apps/memo/dashboard.html.twig';
    }
}
