<?php

namespace PhpCmplr\Symfony\Config;

use DOMXPath;
use DOMNode;
use DOMElement;
use DOMNodeList;
use PhpCmplr\Util\Xml;
use PhpCmplr\Util\CommonTokens;

class XmlLoader implements FileLoaderInterface
{
    private $namespaceUri = 'http://symfony.com/schema/dic/services';

    public function getSupportedExtensions()
    {
        return ['xml'];
    }

    public function load($fileContents, Config $config)
    {
        $xml = Xml::load($fileContents);
        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('c', $this->namespaceUri);

        $parameters = $this->parseParameters($xpath->query('/c:container/c:parameters/c:parameter'), $xpath);
        foreach ($parameters as $key => $value) {
            $config->addParameter($key, $value);
        }

        $this->parseServices($config, $xpath->query('/c:container/c:services/c:service'));

        return true;
    }

    private function parseParameters(DOMNodeList $nodes, DOMXPath $xpath)
    {
        $parameters = [];

        /** @var DOMElement */
        foreach ($nodes as $node) {
            $key = strtolower($node->getAttribute('key') ?: $node->getAttribute('name'));
            if (!$key) {
                $key = empty($parameters) ? 0 : max(array_keys($parameters)) + 1;
            }

            switch ($node->getAttribute('type')) {
                case 'string':
                    $value = $node->nodeValue;
                    break;
                case 'collection':
                    $value = $this->parseParameters($xpath->query('c:parameter', $node), $xpath);
                    break;
                default:
                    $value = CommonTokens::getValue($node->nodeValue);
            }

            $parameters[$key] = $value;
        }

        return $parameters;
    }

    private function parseServices(Config $config, DOMNodeList $nodes)
    {
        /** @var DOMElement */
        foreach ($nodes as $node) {
            $id = (string)$node->getAttribute('id');
            $class = $node->getAttribute('class') ?: null;
            $alias = $node->getAttribute('alias') ?: null;
            $public = true;
            if ($publicValue = $node->getAttribute('public')) {
                $public = (bool)CommonTokens::getValue($publicValue);
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
}
