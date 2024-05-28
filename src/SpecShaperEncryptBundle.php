<?php

declare(strict_types=1);

namespace SpecShaper\EncryptBundle;

use SpecShaper\EncryptBundle\Annotations\Encrypted;
use SpecShaper\EncryptBundle\Encryptors\OpenSslEncryptor;
use SpecShaper\EncryptBundle\EventListener\DoctrineEncryptListener;
use SpecShaper\EncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use SpecShaper\EncryptBundle\Subscribers\EncryptEventSubscriber;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SpecShaperEncryptBundle extends AbstractBundle
{
    protected string $extensionAlias = 'spec_shaper_encrypt';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('encrypt_key')->end()
                ->scalarNode('method')->defaultValue('OpenSSL')->end()
//                ->scalarNode('subscriber_class')->defaultValue(DoctrineEncryptSubscriber::class)->end()
                ->scalarNode('listener_class')->defaultValue(DoctrineEncryptListener::class)->end()
                ->scalarNode('encryptor_class')->defaultValue(OpenSslEncryptor::class)->end()
                ->scalarNode('is_disabled')->defaultValue(false)->end()
                ->arrayNode('connections')
                    ->treatNullLike([])
                    ->prototype('scalar')->end()
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
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        if ($builder->hasParameter('encrypt_key')) {
            trigger_deprecation('SpecShaperEncryptBundle', 'v3.0.2', 'storing Specshaper Encrypt Key in parameters is deprecated. Move to Config/Packages/spec_shaper_encrypt.yaml');
        }

        $encryptKey =  $config['encrypt_key'];

        $container->parameters()->set($this->extensionAlias.'.encrypt_key', $encryptKey);
        $container->parameters()->set($this->extensionAlias.'.method', $config['method']);
//        $container->parameters()->set($this->extensionAlias.'.subscriber_class', $config['subscriber_class']);
        $container->parameters()->set($this->extensionAlias.'.listener_class', $config['listener_class']);
        $container->parameters()->set($this->extensionAlias.'.encryptor_class', $config['encryptor_class']);
        $container->parameters()->set($this->extensionAlias.'.annotation_classes', $config['annotation_classes']);
        $container->parameters()->set($this->extensionAlias.'.is_disabled', $config['is_disabled']);

        $services = $container->services();

        $encryptEventSubscriber = $services->set(EncryptEventSubscriber::class)
            ->autowire(true)
            ->arg('$isDisabled', $config['is_disabled'])
        ;

//        $doctrineSubscriber = $services->set($config['subscriber_class'])
//            ->autowire(true)
//            ->arg('$annotationArray', $config['annotation_classes'])
//            ->arg('$isDisabled', $config['is_disabled'])
//        ;

        $doctrineListener = $services->set($config['listener_class'])
            ->autowire(true)
            ->arg('$annotationArray', $config['annotation_classes'])
            ->arg('$isDisabled', $config['is_disabled'])
        ;

        foreach($config['connections'] as $connectionName){
            $doctrineListener->tag('doctrine.event_listener', [
                'priority' => 500,
                'connection' => $connectionName,
                'event' => 'onFlush'
            ]);

            $doctrineListener->tag('doctrine.event_listener', [
                'priority' => 500,
                'connection' => $connectionName,
                'event' => 'postUpdate'
            ]);

            $doctrineListener->tag('doctrine.event_listener', [
                'priority' => 500,
                'connection' => $connectionName,
                'event' => 'postLoad'
            ]);
        }

        foreach($config['connections'] as $connectionName){
//            $doctrineSubscriber->tag('doctrine.event_subscriber', [
//                'priority' => 500,
//                'connection' => $connectionName,
//            ]);

            $encryptEventSubscriber->tag('kernal.event_subscriber', [
                'connection' => $connectionName,
            ]);
        }

        // Check if Twig is available
        if (class_exists(\Twig\Environment::class)) {
            $container->import('../config/twig_services.yaml');
        }
    }
}
