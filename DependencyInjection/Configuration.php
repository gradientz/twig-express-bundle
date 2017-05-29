<?php

namespace Kaliop\TwigExpressBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('twig_express')
            ->children()
                ->arrayNode('bundles')
                    ->info('List of enabled bundles, with optional per-bundle config')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifString()->then(function($v) { return ['name' => $v]; })
                        ->end()
                        ->children()
                            ->scalarNode('name')
                                ->info('Bundle name')
                                ->isRequired()
                            ->end()
                            ->scalarNode('slug')
                                ->info('Short name to use in the URL')
                                ->defaultValue(null)
                            ->end()
                            ->scalarNode('root')
                                ->info('Document root folder from the bundle')
                                ->defaultValue('Resources/views/static')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $treeBuilder;
    }
}
