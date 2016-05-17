<?php

namespace PhpCmplr\Completer;

class ContainerFactory implements  ContainerFactoryInterface
{
    public function create($path, $contents, array $options = [])
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', new Parser\ParserComponent($container));
        $container->set('diagnostics', new Diagnostics\DiagnosticsComponent($container));

        return $container;
    }
}
