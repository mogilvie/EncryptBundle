<?php

declare(strict_types=1);

namespace SpecShaper\EncryptBundle;

use SpecShaper\EncryptBundle\Annotations\Encrypted;
use SpecShaper\EncryptBundle\Encryptors\AecCbcEncryptor;
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
                ->scalarNode('subscriber_class')->defaultValue(DoctrineEncryptSubscriber::class)->end()
                ->scalarNode('encryptor_class')->defaultValue(AecCbcEncryptor::class)->end()
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
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        if ($builder->hasParameter('encrypt_key')) {
            trigger_deprecation('SpecShaperEncryptBundle', 'v3.0.2', 'storing Specshaper Encrypt Key in parameters is deprecated. Move to Config/Packages/spec_shaper_encrypt.yaml');
            $encryptKey =  $builder->getParameter('encrypt_key');
        } else {
            $encryptKey =  $config['encrypt_key'];
        }

        if(array_key_exists('subscriber_class', $config)){
            trigger_deprecation('SpecShaperEncryptBundle', 'v4.0.0', 'DoctrineSubscribers will be deprecated in version 4. If you
            have a custom subscriber in the encrypt bundle then this will need to be changed to a DoctrineListener.');
        }

        $container->parameters()->set($this->extensionAlias.'.encrypt_key', $encryptKey);
        $container->parameters()->set($this->extensionAlias.'.method', $config['method']);
        $container->parameters()->set($this->extensionAlias.'.subscriber_class', $config['subscriber_class']);
        $container->parameters()->set($this->extensionAlias.'.encryptor_class', $config['encryptor_class']);
        $container->parameters()->set($this->extensionAlias.'.annotation_classes', $config['annotation_classes']);
        $container->parameters()->set($this->extensionAlias.'.is_disabled', $config['is_disabled']);

        $services = $container->services();

        $doctrineSubscriber = $services->set($config['subscriber_class'])
            ->autowire(true)
            ->arg('$annotationArray', $config['annotation_classes'])
            ->arg('$isDisabled', $config['is_disabled'])
        ;

        $encryptEventSubscriber = $services->set(EncryptEventSubscriber::class)
            ->autowire(true)
            ->arg('$isDisabled', $config['is_disabled'])
        ;

        foreach($config['connections'] as $connectionName){
            $doctrineSubscriber->tag('doctrine.event_subscriber', [
                'priority' => 500,
                'connection' => $connectionName,
            ]);

            $encryptEventSubscriber->tag('kernal.event_subscriber', [
                'connection' => $connectionName,
            ]);
        }

        // Check if Twig is available
        if ($config['enable_twig'] && class_exists(\Twig\Environment::class)) {
            $container->import('../config/twig_services.yaml');
        }
    }
}
