<?php

namespace SpecShaper\EncryptBundle\DependencyInjection;

use SpecShaper\EncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('spec_shaper_encrypt');

            $rootNode
                ->children()
                    ->scalarNode('method')->defaultValue('OpenSSL')->end()
                    ->scalarNode('subscriber_class')->defaultValue(DoctrineEncryptSubscriber::class)->end()
                    ->arrayNode('annotation_classes')
                        ->treatNullLike(array())
                        ->prototype('scalar')->end()
                        ->defaultValue(array(
                            'SpecShaper\EncryptBundle\Annotations\Encrypted'
                        ))
                    ->end()
                ->end()
            ;


        return $treeBuilder;
    }
}

