<?php

namespace Kaliop\TwigExpressBundle\Twig;

class TwigExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'twig_express.twig_extension';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('test', array($this, 'testFunction')),
        );
    }

    public function testFunction()
    {
        return 'THIS IS WORKING';
    }
}
