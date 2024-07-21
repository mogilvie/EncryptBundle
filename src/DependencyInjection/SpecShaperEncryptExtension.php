<?php

namespace SpecShaper\EncryptBundle\DependencyInjection;

use SpecShaper\EncryptBundle\Event\EncryptEvents;
use SpecShaper\EncryptBundle\EventListener\DoctrineEncryptListener;
use SpecShaper\EncryptBundle\EventListener\EncryptEventListener;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class SpecShaperEncryptExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');

        if ($container->hasParameter('encrypt_key')) {
            trigger_deprecation('SpecShaperEncryptBundle', 'v3.0.2', 'storing Specshaper Encrypt Key in parameters is deprecated. Move to Config/Packages/spec_shaper_encrypt.yaml');
            $encryptKey = $container->getParameter('encrypt_key');
        } else {
            $encryptKey = $config['encrypt_key'];
        }

        $container->setParameter($this->getAlias().'.encrypt_key', $encryptKey);
        $container->setParameter($this->getAlias().'.default_associated_data', $config['default_associated_data']);
        $container->setParameter($this->getAlias().'.method', $config['method']);
        $container->setParameter($this->getAlias().'.listener_class', $config['listener_class']);
        $container->setParameter($this->getAlias().'.encryptor_class', $config['encryptor_class']);
        $container->setParameter($this->getAlias().'.annotation_classes', $config['annotation_classes']);
        $container->setParameter($this->getAlias().'.is_disabled', $config['is_disabled']);

        $doctrineListener = new Definition($config['listener_class']);
        $doctrineListener
            ->setAutowired(true)
            ->setArgument('$annotationArray', $config['annotation_classes'])
            ->setArgument('$isDisabled', $config['is_disabled'])
        ;

        $encryptEventListener = new Definition(EncryptEventListener::class);
        $encryptEventListener
            ->setAutowired(true)
            ->setArgument('$isDisabled', $config['is_disabled'])
        ;

        foreach ($config['connections'] as $connectionName) {
            $doctrineListener->addTag('doctrine.event_listener', [
                'event' => 'postLoad',
                'priority' => 500,
                'connection' => $connectionName,
            ]);

            $doctrineListener->addTag('doctrine.event_listener', [
                'event' => 'postUpdate',
                'priority' => 500,
                'connection' => $connectionName,
            ]);

            $doctrineListener->addTag('doctrine.event_listener', [
                'event' => 'onFlush',
                'priority' => 500,
                'connection' => $connectionName,
            ]);

            $encryptEventListener->addTag('kernel.event_listener', [
                'event' => EncryptEvents::ENCRYPT,
                'method' => 'encrypt',
                'connection' => $connectionName,
            ]);

            $encryptEventListener->addTag('kernel.event_listener', [
                'event' => EncryptEvents::DECRYPT,
                'method' => 'decrypt',
                'connection' => $connectionName,
            ]);
        }

        $container->addDefinitions([
            DoctrineEncryptListener::class => $doctrineListener,
            EncryptEventListener::class => $encryptEventListener
        ]);

        // Check if Twig is available
        if ($config['enable_twig'] && class_exists(\Twig\Environment::class)) {
            $loader->load('twig_services.yaml');
        }
    }
}
