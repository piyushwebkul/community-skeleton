<?php

namespace Wizard\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Wizard extends Controller
{
    const HELPDESK_VERSION = '0.1.0 DEV';

    public function load()
    {
        return $this->render('installation-wizard/index.html.twig', [
            'version' => self::HELPDESK_VERSION,
        ]);
    }
}
