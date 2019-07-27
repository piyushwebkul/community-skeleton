<?php

namespace UVDesk\CommunityPackages\UVDesk\Commons\Applications;

use Webkul\UVDesk\ExtensionFrameworkBundle\Definition\ApplicationMetadata;

class CustomerNotesMetadata extends ApplicationMetadata
{
    public function getName() : string
    {
        return "Customer Notes";
    }

    public function getSummary() : string
    {
        return "Add important notes to support tickets accessible to all agents";
    }

    public function getDescription() : string
    {
        return "Write notes pertaining to customers which will be visible to all agents across your helpdesk. This provides an effective way of storing important details which may be vital in providing a better support to your customers.";
    }

    public function getQualifiedName() : string
    {
        return "customer-notes";
    }

    public function getDashboardTemplate() : string
    {
        return '@_uvdesk_extension_uvdesk_commons/apps/customer-notes/dashboard.html.twig';
    }
}
