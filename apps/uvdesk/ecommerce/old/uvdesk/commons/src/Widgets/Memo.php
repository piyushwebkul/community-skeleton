<?php

namespace UVDesk\CommunityPackages\UVDesk\Commons\Widgets;

use Twig\Environment as TwigEnvironment;
use Symfony\Component\HttpFoundation\RequestStack;
use Webkul\UVDesk\CoreFrameworkBundle\Widgets\TicketWidgetInterface;

class Memo implements TicketWidgetInterface
{
    CONST SVG = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px">
    <path fill-rule="evenodd" fill="rgb(51, 51, 51)" d="M17.000,20.000 L3.000,20.000 C1.343,20.000 0.000,18.657 0.000,17.000 L0.000,3.000 C0.000,1.343 1.343,0.000 3.000,0.000 L17.000,0.000 C18.657,0.000 20.000,1.343 20.000,3.000 L20.000,17.000 C20.000,18.657 18.657,20.000 17.000,20.000 ZM13.333,4.000 L8.333,8.800 L6.667,7.200 L5.000,8.800 L8.333,12.000 L15.000,5.600 L13.333,4.000 ZM16.000,14.000 L4.000,14.000 L4.000,16.000 L16.000,16.000 L16.000,14.000 Z"/>
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
        return "To do";
    }

    public static function getDataTarget()
    {
        return 'uv-todo-view';
    }

    public function getTemplate()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->twig->render('@_uvdesk_extension_uvdesk_commons/apps/memo/widget.html.twig', [
            'id' => $request->get('ticketId')
        ]);
    }
}
