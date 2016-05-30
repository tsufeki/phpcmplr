<?php

namespace PhpCmplr;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\ContainerFactoryInterface;
use PhpCmplr\Completer\Project;
use PhpCmplr\Completer\SourceFile;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Diagnostics\DiagnosticsComponent;
use PhpCmplr\Server\Server;
use PhpCmplr\Server\Action;
use PhpCmplr\Util\FileIO;

class PhpCmplr extends Plugin implements ContainerFactoryInterface
{
    private $logger;

    /**
     * @var Server
     */
    private $server;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var FileIO
     */
    private $io;

    /**
     * @var Plugin[]
     */
    private $plugins;

    /**
     * @param int    $port
     * @param string $host
     */
    public function __construct($port, $host = '127.0.0.1')
    {
        $this->logger = null;
        $this->io = new FileIO();
        $this->project = new Project($this);
        $this->server = new Server($this->project, $this->logger, $port, $host);

        $this->plugins = [];
        $this->addPlugin($this);
    }

    /**
     * @param Plugin $plugin
     *
     * @return $this
     */
    public function addPlugin(Plugin $plugin)
    {
        $plugin->addActions($this->server);
        $this->plugins[] = $plugin;

        return $this;
    }

    public function run()
    {
        $this->server->run();
    }

    public function createContainer($path, $contents, array $options = [])
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));

        foreach ($this->plugins as $plugin) {
            $plugin->addComponents($container, $options);
        }

        return $container;
    }

    public function addActions(Server $server)
    {
        $server->addAction(new Action\Ping());
        $server->addAction(new Action\Load());
        $server->addAction(new Action\Diagnostics());
        $server->addAction(new Action\Quit($server));
    }

    public function addComponents(Container $container, array $options)
    {
        $container->set('logger', $this->logger);
        $container->set('io', $this->io);
        $container->set('project', $this->project);
        $container->set('parser', new ParserComponent($container));
        $container->set('diagnostics', new DiagnosticsComponent($container));
    }
}
