<?php

namespace UVDesk\CommunityPackages\UVDesk\Commons\Applications;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webkul\UVDesk\ExtensionFrameworkBundle\Definition\Application;
use Webkul\UVDesk\ExtensionFrameworkBundle\Definition\ApplicationMetadata;

class CustomerNotes extends Application implements EventSubscriberInterface
{
    public static function getMetadata() : ApplicationMetadata
    {
        return new CustomerNotesMetadata();
    }

    public static function getSubscribedEvents()
    {
        return array();
    }
}
