<?php

namespace SpecShaper\EncryptBundle\DependencyInjection;

use SpecShaper\EncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use SpecShaper\EncryptBundle\Subscribers\EncryptEventSubscriber;
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
    public function load(array $configs, ContainerBuilder $container)
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
//
//        dump($encryptKey);

        if (array_key_exists('subscriber_class', $config)) {
            trigger_deprecation('SpecShaperEncryptBundle', 'v4.0.0', 'DoctrineSubscribers will be deprecated in version 4. If you
            have a custom subscriber in the encrypt bundle then this will need to be changed to a DoctrineListener.');
        }

        $container->setParameter($this->getAlias().'.encrypt_key', $encryptKey);
        $container->setParameter($this->getAlias().'.default_associated_data', $config['default_associated_data']);
        $container->setParameter($this->getAlias().'.method', $config['method']);
        $container->setParameter($this->getAlias().'.subscriber_class', $config['subscriber_class']);
        $container->setParameter($this->getAlias().'.encryptor_class', $config['encryptor_class']);
        $container->setParameter($this->getAlias().'.annotation_classes', $config['annotation_classes']);
        $container->setParameter($this->getAlias().'.is_disabled', $config['is_disabled']);

        $doctrineSubscriber = new Definition($config['subscriber_class']);
        $doctrineSubscriber
            ->setAutowired(true)
            ->setArgument(3, $config['annotation_classes'])
            ->setArgument(4, $config['is_disabled'])
        ;

        $encryptEventSubscriber = new Definition(EncryptEventSubscriber::class);
        $encryptEventSubscriber
            ->setAutowired(true)
            ->setArgument(1, $config['is_disabled'])
        ;

        foreach ($config['connections'] as $connectionName) {
            $doctrineSubscriber->addTag('doctrine.event_subscriber', [
                'priority' => 500,
                'connection' => $connectionName,
            ]);

            $encryptEventSubscriber->addTag('kernal.event_subscriber', [
                'connection' => $connectionName,
            ]);
        }

        $container->addDefinitions([
            DoctrineEncryptSubscriber::class => $doctrineSubscriber,
            EncryptEventSubscriber::class => $encryptEventSubscriber
        ]);

        // Check if Twig is available
        if ($config['enable_twig'] && class_exists(\Twig\Environment::class)) {
            $loader->load('twig_services.yaml');
        }
    }
}
