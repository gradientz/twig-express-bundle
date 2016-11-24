<?php

namespace Gradientz\TwigExpressBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class GradientzTwigExpressExtension extends Extension
{
    public function getAlias()
    {
        return 'twig_express';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        // Make sure we have a twig_express.url_base parameter so we can use it in routes
        if ($container->hasParameter('twig_express.url_base') === false) {
            $container->setParameter('twig_express.url_base', 'static');
        }

        // Expose configured bundle information
        // (Is there a better way to get access to our config from controllers???)
        $bundles = [];
        $config = $this->processConfiguration(new Configuration(), $configs);
        foreach ($config['bundles'] as $item) {
            $name = $item['name'];
            $slug = $item['slug'];
            // Create slug if necessary, and clean it up
            if (!$slug) {
                $slug = strtolower($name);
                if (substr($slug, -6) === 'bundle') $slug = substr($slug, 0, -6);
            }
            $slug = str_replace('/', '', $slug);
            $root = trim($item['root'], '/');
            if (!array_key_exists($slug, $bundles)) {
                $bundles[$slug] = ['name' => $name, 'root' => $root];
            }
        }
        $container->setParameter('twig_express.bundles', $bundles);
    }
}
