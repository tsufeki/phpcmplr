<?php

namespace PhpCmplr\Symfony;

use PhpCmplr\Core\Container;
use PhpCmplr\Plugin;
use PhpCmplr\Symfony\Config\ConfigLoader;
use PhpCmplr\Symfony\Config\YamlLoader;

class SymfonyPlugin extends Plugin
{
    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return [
            'symfony' => [
                'enabled' => true,
            ],
        ];
    }

    /**
     * @param Container $container
     * @param array     $options
     */
    public function addProjectComponents(Container $container, array $options)
    {
        if ($options['symfony']['enabled']) {
            $container->set('symfony.paths', new Paths($container));
            $container->set('symfony.config', new ConfigLoader($container));
            $container->set('symfony.config_loader.yaml', new YamlLoader(), ['symfony.config_loader']);
        }
    }

    /**
     * @param Container $container
     * @param array     $options
     */
    public function addFileComponents(Container $container, array $options)
    {
        if ($options['symfony']['enabled']) {
        }
    }
}
