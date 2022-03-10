<?php

namespace SpecShaper\EncryptBundle\DependencyInjection;

use SpecShaper\EncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('spec_shaper_encrypt');
        
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('method')->defaultValue('OpenSSL')->end()
                ->scalarNode('subscriber_class')->defaultValue(DoctrineEncryptSubscriber::class)->end()
                ->scalarNode('is_disabled')->defaultValue(false)->end()
                ->arrayNode('annotation_classes')
                    ->treatNullLike(array())
                    ->prototype('scalar')->end()
                    ->defaultValue(array(
                        Encrypted::class
                    ))
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
