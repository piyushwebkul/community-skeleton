<?php

namespace UVDesk\CommunityPackages\UVDesk\Commons\Widgets;

use Twig\Environment as TwigEnvironment;
use Symfony\Component\HttpFoundation\RequestStack;
use Webkul\UVDesk\CoreFrameworkBundle\Widgets\TicketWidgetInterface;

class CustomerNotes implements TicketWidgetInterface
{
    CONST SVG = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px">
    <path fill-rule="evenodd" fill="rgb(51, 51, 51)" id="icon-notes-on-customer" class="cls-1" d="M3,0H14l5,5V18a2,2,0,0,1-2,2H3a2,2,0,0,1-2-2V2A2,2,0,0,1,3,0Zm7,3A3,3,0,1,1,7,6,3,3,0,0,1,10,3Zm6,14H4s-0.462-5.1,4-6h4C16.462,11.9,16,17,16,17Z"/>
</svg>
SVG;

    public function __construct(RequestStack $requestStack, TwigEnvironment $twig)
    {
        $this->twig = $twig;
        $this->requestStack = $requestStack;
    }

    public static function getIcon()
    {
        return self::SVG;
    }

    public static function getTitle()
    {
        return "Customer Notes";
    }

    public static function getDataTarget()
    {
        return 'uv-customer-info-view';
    }

    public function getTemplate()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->twig->render('@_uvdesk_extension_uvdesk_commons/apps/customer-notes/widget.html.twig', [
            'id' => $request->get('ticketId')
        ]);
    }
}
