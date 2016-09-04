<?php

namespace PhpCmplr;

use Psr\Log\LogLevel;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\ContainerFactoryInterface;
use PhpCmplr\Completer\Project;
use PhpCmplr\Completer\SourceFile\SourceFile;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\NameResolver\NameResolver;
use PhpCmplr\Completer\DocComment\DocCommentParser;
use PhpCmplr\Completer\Diagnostics\Diagnostics;
use PhpCmplr\Completer\Reflection\Reflection;
use PhpCmplr\Completer\Reflection\FileReflection;
use PhpCmplr\Completer\Reflection\LocatorReflection;
use PhpCmplr\Completer\Reflection\JsonReflection;
use PhpCmplr\Completer\Composer\ComposerLocator;
use PhpCmplr\Completer\TypeInferrer\TypeInferrer;
use PhpCmplr\Completer\TypeInferrer\BasicInferrer;
use PhpCmplr\Completer\TypeInferrer\ReflectionInferrer;
use PhpCmplr\Completer\GoTo_\GoTo_;
use PhpCmplr\Completer\GoTo_\GoToMemberDefinition;
use PhpCmplr\Completer\GoTo_\GoToClassDefinition;
use PhpCmplr\Completer\Completer\Completer;
use PhpCmplr\Server\Server;
use PhpCmplr\Server\Action;
use PhpCmplr\Util\FileIO;
use PhpCmplr\Util\Logger;

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
     * @param string $logLevel
     * @param string $logDir
     */
    public function __construct($port, $host = '127.0.0.1', $logLevel = LogLevel::DEBUG, $logDir = 'php://stderr')
    {
        $this->logger = new Logger($logDir, $logLevel, [
            'logFormat' => "[{date}] [{level}] [pid:{pid}] {message}\n{exception}",
            'appendContext' => false,
        ]);
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
        $server->addAction(new Action\Type());
        $server->addAction(new Action\GoTo_());
        $server->addAction(new Action\Complete());
        $server->addAction(new Action\Quit($server));
    }

    public function addComponents(Container $container, array $options)
    {
        $container->set('logger', $this->logger);
        $container->set('io', $this->io);
        $container->set('project', $this->project);
        $container->set('diagnostics', new Diagnostics($container));
        $container->set('parser', new Parser($container), ['diagnostics']);
        $container->set('name_resolver', new NameResolver($container));
        $container->set('doc_comment', new DocCommentParser($container));
        $container->set('reflection', new Reflection($container));
        $container->set('reflection.file', new FileReflection($container), ['reflection']);
        $container->set('reflection.locator', new LocatorReflection($container), ['reflection']);
        $stdlibPath = __DIR__ . '/../../data/stdlib.json';
        $container->set('reflection.stdlib', new JsonReflection($container, $stdlibPath), ['reflection']);
        $container->set('composer.locator', new ComposerLocator($container), ['reflection.locator']);
        $container->set('typeinfer', new TypeInferrer($container));
        $container->set('typeinfer.basic', new BasicInferrer($container), ['typeinfer.visitor']);
        $container->set('typeinfer.reflection', new ReflectionInferrer($container), ['typeinfer.visitor']);
        $container->set('goto', new GoTo_($container));
        $container->set('goto.member_definition', new GoToMemberDefinition($container), ['goto']);
        $container->set('goto.class_definition', new GoToClassDefinition($container), ['goto']);
        $container->set('completer', new Completer($container));
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }
}
