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
        $config = $this->processConfiguration(new Configuration(), $configs);

        // Make sure we have a base_url parameter so we can use it in routes
        $baseUrl = $container->getParameter('twig_express.base_url');
        if (is_string($baseUrl)) $baseUrl = trim($baseUrl, '/');
        if (!$baseUrl) $container->setParameter('twig_express.base_url', 'static');

        // Expose configured bundle information
        // (Is there a better way to get access to our config from controllers???)
        $bundles = [];
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
