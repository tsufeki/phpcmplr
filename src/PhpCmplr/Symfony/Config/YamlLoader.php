<?php

namespace PhpCmplr\Symfony\Config;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlLoader implements FileLoaderInterface
{
    const PARAMETERS = 'parameters';
    const SERVICES = 'services';
    const CLASS_ = 'class';
    const ALIAS = 'alias';
    const PUBLIC_ = 'public';

    public function getSupportedExtensions()
    {
        return ['yml', 'yaml'];
    }

    public function load($fileContents, Config $config)
    {
        try {
            $data = Yaml::parse($fileContents);
        } catch (ParseException $e) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        if (array_key_exists(self::PARAMETERS, $data) && is_array($data[self::PARAMETERS])) {
            foreach ($data[self::PARAMETERS] as $key => $value) {
                $config->addParameter($key, $value);
            }
        }

        if (array_key_exists(self::SERVICES, $data) && is_array($data[self::SERVICES])) {
            foreach ($data[self::SERVICES] as $id => $srv) {
                $class = null;
                $alias = null;
                $public = true;

                if (is_string($srv) && $srv && $srv[0] === '@') {
                    $alias = substr($srv, 1);
                }

                if (is_array($srv)) {
                    if (array_key_exists(self::CLASS_, $srv) &&
                        is_string($srv[self::CLASS_]) &&
                        $srv[self::CLASS_] !== ''
                    ) {
                        $class = $srv[self::CLASS_];
                    }

                    if (array_key_exists(self::ALIAS, $srv) &&
                        is_string($srv[self::ALIAS]) &&
                        $srv[self::ALIAS] !== ''
                    ) {
                        $alias = $srv[self::ALIAS];
                    }

                    if (array_key_exists(self::PUBLIC_, $srv)) {
                        $public = (bool)$srv[self::PUBLIC_];
                    }
                }

                if ($class !== null || $alias !== null) {
                    $service = new Service($id);
                    $service->setClass($class);
                    $service->setAlias($alias);
                    $service->setPublic($public);
                    $config->addService($service);
                }
            }
        }

        return true;
    }
}
