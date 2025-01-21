<?php

namespace SpecShaper\EncryptBundle\DependencyInjection;

use SpecShaper\EncryptBundle\Annotations\Encrypted;
use SpecShaper\EncryptBundle\Encryptors\AesCbcEncryptor;
use SpecShaper\EncryptBundle\EventListener\DoctrineEncryptListener;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('spec_shaper_encrypt');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('encrypt_key')->end()
                ->scalarNode('default_associated_data')->defaultValue(null)->end()
                ->scalarNode('method')->defaultValue('OpenSSL')->end()
                ->scalarNode('listener_class')->defaultValue(DoctrineEncryptListener::class)->end()
                ->scalarNode('encryptor_class')->defaultValue(AesCbcEncryptor::class)->end()
                ->scalarNode('is_disabled')->defaultValue(false)->end()
                ->arrayNode('connections')
                ->treatNullLike([])
                ->prototype('scalar')->end()
                ->defaultValue([
                    'default',
                ])
                ->end()
                ->arrayNode('annotation_classes')
                ->treatNullLike([])
                ->prototype('scalar')->end()
                ->defaultValue([
                    Encrypted::class,
                ])
                ->end()
                ->booleanNode('enable_twig')
                ->defaultTrue()
                ->info('Enable or disable Twig functionality')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}