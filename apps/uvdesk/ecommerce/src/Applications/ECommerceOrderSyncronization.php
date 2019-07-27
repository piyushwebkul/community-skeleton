<?php

namespace UVDesk\CommunityPackages\UVDesk\ECommerce\Applications;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webkul\UVDesk\ExtensionFrameworkBundle\Application\Routine\ApiRoutine;
use UVDesk\CommunityPackages\UVDesk\ECommerce\Utils\ECommerceConfiguration;
use Webkul\UVDesk\ExtensionFrameworkBundle\Definition\Application\Application;
use Webkul\UVDesk\ExtensionFrameworkBundle\Application\Routine\RenderDashboardRoutine;
use Webkul\UVDesk\ExtensionFrameworkBundle\Definition\Application\ApplicationMetadata;
use Webkul\UVDesk\ExtensionFrameworkBundle\Definition\Application\ApplicationInterface;

class ECommerceOrderSyncronization extends Application implements ApplicationInterface, EventSubscriberInterface
{
    private $eCommerceConfiguration;

    public function __construct(ECommerceConfiguration $eCommerceConfiguration)
    {
        $this->eCommerceConfiguration = $eCommerceConfiguration;
    }

    public static function getMetadata() : ApplicationMetadata
    {
        return new ECommerceOrderSyncronizationMetadata();
    }

    public static function getSubscribedEvents()
    {
        return array(
            ApiRoutine::getName() => array(
                array('handleApiRequest'),
            ),
            RenderDashboardRoutine::getName() => array(
                array('prepareDashboard'),
            ),
        );
    }

    public function prepareDashboard(RenderDashboardRoutine $event)
    {
        $dashboard = $event->getDashboardTemplate();

        // Add loadable resources to templates
        $dashboard->appendStylesheet('bundles/extensionframework/extensions/uvdesk/ecommerce/css/csspin.css');
        $dashboard->appendStylesheet('bundles/extensionframework/extensions/uvdesk/ecommerce/css/main.css');
        $dashboard->appendJavascript('bundles/extensionframework/extensions/uvdesk/ecommerce/js/main.js');

        // Configure dashboard
        $event
            ->setTemplateReference('@_uvdesk_extension_uvdesk_ecommerce/apps/order-syncronization/dashboard.html.twig')
            ->addTemplateData('configuration', $this->eCommerceConfiguration);
    }

    public function handleApiRequest(ApiRoutine $event)
    {
        $request = $event->getRequest();
        
        switch ($request->query->get('endpoint')) {
            case 'get-stores':
                $response = ['platforms' => []];
                foreach ($this->eCommerceConfiguration->getECommercePlatforms() as $eCommercePlatform) {
                    $response['platforms'][$eCommercePlatform->getQualifiedName()] = [
                        'title' => $eCommercePlatform->getName(),
                        'description' => $eCommercePlatform->getDescription(),
                        'channels' => array_map(function ($eCommerceChannel) {
                            switch($eCommercePlatform->getQualifiedName()) {
                                case 'PrestashopECommercePlatform':
                                    return [
                                        'id' => $eCommerceChannel->getId(),
                                        'name' => $eCommerceChannel->getName(),
                                        'domain' => $eCommerceChannel->getDomain(),
                                        'apiKey' => $eCommerceChannel->getClient(),
                                        'enabled' => $eCommerceChannel->getIsEnabled(),
                                    ]; break;
                                default:
                                    return [
                                        'id' => $eCommerceChannel->getId(),
                                        'name' => $eCommerceChannel->getName(),
                                        'domain' => $eCommerceChannel->getDomain(),
                                        'apiKey' => $eCommerceChannel->getClient(),
                                        'apiPassword' => $eCommerceChannel->getPassword(),
                                        'enabled' => $eCommerceChannel->getIsEnabled(),
                                    ]; break;                    
                            } 
                        }, $eCommercePlatform->getECommerceChannelCollection()),
                    ];
                }
                $event->setResponseData($response);
                break;
            case 'save-store':
                // get request params
                $attributes = json_decode($request->getContent(), true);
                $attributes = !$attributes ? $request->request->all() : $attributes;
                // get platform id
                $platformId = array_keys($attributes)[0];
                $attributes = $attributes[$platformId];
                $eCommercePlatform = $this->eCommerceConfiguration->getECommercePlatformByQualifiedName($platformId);
                if (!empty($eCommercePlatform)) {
                    try {
                        if ('POST' == $request->getMethod()) {
                            $channel = $eCommercePlatform->createECommerceChannel($attributes);
                        } else if ('PUT' == $request->getMethod()) {
                            $channel = $eCommercePlatform->updateECommerceChannel($attributes); 
                        }
                    } catch (\Exception $exception) {}
                    $this->getPackage()->updatePackageConfiguration((string) $this->eCommerceConfiguration);
                }

                break;
            case 'remove-store':
                // get request params
                $attributes = json_decode($request->getContent(), true);
                $attributes = !$attributes ? $request->request->all() : $attributes;

                // get platform id
                $platformId = array_keys($attributes)[0];
                $attributes = $attributes[$platformId];

                $eCommercePlatform = $this->eCommerceConfiguration->getECommercePlatformByQualifiedName('shopify');

                $channel = $eCommercePlatform->removeECommerceChannel($attributes);
                $this->getPackage()->updatePackageConfiguration((string) $this->eCommerceConfiguration);

                break;
            default:
                break;
        }
    }
}
