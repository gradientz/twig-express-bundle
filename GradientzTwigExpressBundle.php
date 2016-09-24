<?php

namespace Gradientz\TwigExpressBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GradientzTwigExpressBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new DependencyInjection\GradientzTwigExpressExtension();
    }
}
