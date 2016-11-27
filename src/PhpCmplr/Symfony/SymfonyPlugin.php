<?php

namespace PhpCmplr\Symfony;

use PhpCmplr\Core\Container;
use PhpCmplr\Plugin;

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
