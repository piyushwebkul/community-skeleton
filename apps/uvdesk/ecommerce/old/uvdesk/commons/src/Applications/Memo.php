<?php

namespace UVDesk\CommunityPackages\UVDesk\Commons\Applications;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webkul\UVDesk\ExtensionFrameworkBundle\Definition\Application;
use Webkul\UVDesk\ExtensionFrameworkBundle\Definition\ApplicationMetadata;

class Memo extends Application implements EventSubscriberInterface
{
    public static function getMetadata() : ApplicationMetadata
    {
        return new MemoMetadata();
    }

    public static function getSubscribedEvents()
    {
        return array();
    }
}
