<?php

namespace Kaliop\TwigExpressBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class KaliopTwigExpressBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new DependencyInjection\KaliopTwigExpressExtension();
    }
}
