<?php

namespace SpecShaper\EncryptBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class SpecShaperEncryptExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter($this->getAlias() . '.method', $config['method']);
        $container->setParameter($this->getAlias() . '.subscriber_class', $config['subscriber_class']);
        $container->setParameter($this->getAlias() . '.annotation_classes', $config['annotation_classes']);
        $container->setParameter($this->getAlias() . '.is_disabled', $config['is_disabled']);

    }
}
