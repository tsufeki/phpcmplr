<?php

namespace PhpCmplr;

use Psr\Log\LogLevel;
use React\EventLoop\Factory as EventLoopFactory;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\ContainerFactoryInterface;
use PhpCmplr\Completer\FileStoreInterface;
use PhpCmplr\Completer\FileStore;
use PhpCmplr\Completer\Project;
use PhpCmplr\Completer\ProjectRootDirectoryGuesser;
use PhpCmplr\Completer\SourceFile\SourceFile;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\NameResolver\NameResolver;
use PhpCmplr\Completer\DocComment\DocCommentParser;
use PhpCmplr\Completer\Diagnostics\Diagnostics;
use PhpCmplr\Completer\Diagnostics\FixHelper;
use PhpCmplr\Completer\Reflection\Reflection;
use PhpCmplr\Completer\Reflection\FileReflection;
use PhpCmplr\Completer\Reflection\LocatorReflection;
use PhpCmplr\Completer\Reflection\JsonReflection;
//use PhpCmplr\Completer\Composer\ComposerLocator;
use PhpCmplr\Completer\TypeInferrer\TypeInferrer;
use PhpCmplr\Completer\TypeInferrer\BasicInferrer;
use PhpCmplr\Completer\TypeInferrer\ReflectionInferrer;
use PhpCmplr\Completer\GoTo_\GoTo_;
use PhpCmplr\Completer\GoTo_\GoToMemberDefinition;
use PhpCmplr\Completer\GoTo_\GoToClassDefinition;
use PhpCmplr\Completer\Completer\Completer;
use PhpCmplr\Completer\Reflection\NamespaceReflection;
use PhpCmplr\Completer\Indexer\Indexer;
use PhpCmplr\Completer\Indexer\ReflectionIndexData;
use PhpCmplr\Completer\Indexer\IndexLocator;
use PhpCmplr\Completer\Indexer\IndexNamespaceReflection;
use PhpCmplr\Server\Server;
use PhpCmplr\Server\Action;
use PhpCmplr\Util\FileIO;
use PhpCmplr\Util\Logger;

class PhpCmplr extends Plugin implements ContainerFactoryInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var Container
     */
    private $globalContainer;

    /**
     * @var Server
     */
    private $server;

    /**
     * @var Plugin[]
     */
    private $plugins;

    /**
     * @param array    $options
     * @param Plugin[] $plugins
     */
    public function __construct(array $options = [], array $plugins = [])
    {
        $options = array_replace_recursive([
            'server' => [
                'port' => 41749,
                'host' => '127.0.0.1',
            ],
            'log' => [
                'level' => 'warning',
                'dir' => 'php://stderr',
            ],
            'indexer' => [
                'enabled' => true,
            ],
            'diagnostics' => [
                'undefined' => true,
            ],
        ], $options);

        $this->options = $options;
        $this->plugins = array_merge([$this], $plugins);
        $this->globalContainer = $this->createGlobalContainer();
        $this->server = $this->createServer();
    }

    /**
     * @return Server
     */
    private function createServer()
    {
        $server = new Server(
            $this,
            $this->globalContainer->get('logger'),
            $this->globalContainer->get('eventloop'),
            $this->options['server']
        );

        foreach ($this->plugins as $plugin) {
            $plugin->addActions($server, $this->options);
        }

        return $server;
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    public function addActions(Server $server, array $options)
    {
        $server->addAction(new Action\Ping());
        $server->addAction(new Action\Load());
        $server->addAction(new Action\Diagnostics());
        $server->addAction(new Action\Fix());
        $server->addAction(new Action\Type());
        $server->addAction(new Action\GoTo_());
        $server->addAction(new Action\Complete());
        $server->addAction(new Action\Quit());
    }

    /**
     * @return Container
     */
    private function createGlobalContainer()
    {
        $container = new Container();

        foreach ($this->plugins as $plugin) {
            $plugin->addGlobalComponents($container, $this->options);
        }

        return $container;
    }

    public function getGlobalContainer()
    {
        return $this->globalContainer;
    }

    public function addGlobalComponents(Container $container, array $options)
    {
        $container->set('logger', new Logger(
            $options['log']['dir'],
            constant(LogLevel::class . '::' . strtoupper($options['log']['level'])),
            [
                'logFormat' => "[{date}] [{level}] [pid:{pid}] {message}\n{exception}",
                'appendContext' => false,
            ]
        ));
        $container->set('factory', $this);
        $container->set('file_store', new FileStore($container));
        $container->set('io', new FileIO());
        $container->set('eventloop', EventLoopFactory::create());
        $container->set('project_root_dir', new ProjectRootDirectoryGuesser($container->get('io')));
        $stdlibPath = __DIR__ . '/../../data/stdlib.json';
        $container->set('reflection.stdlib', new JsonReflection($container, $stdlibPath), ['reflection']);
    }

    public function createProject($rootPath)
    {
        $container = new Container($this->globalContainer);
        $project = new Project($rootPath, $container);
        $container->set('project', $project);

        foreach ($this->plugins as $plugin) {
            $plugin->addProjectComponents($container, $this->options);
        }

        $indexer = $container->get('indexer');
        if ($indexer !== null) {
            $indexer->run();
        }

        return $project;
    }

    public function addProjectComponents(Container $container, array $options)
    {
        $container->set('namespace_reflection', new NamespaceReflection($container));
        if ($options['indexer']['enabled']) {
            $container->set('indexer', new Indexer($container));
            $container->set('reflection.locator.index', new IndexLocator($container), ['reflection.locator']);
            $container->set('namespace_reflection.index', new IndexNamespaceReflection($container), ['namespace_reflection']);
        }
    }

    public function createFileContainer(Project $project, $path, $contents)
    {
        $container = new Container($project->getProjectContainer());
        $container->set('file', new SourceFile($container, $path, $contents));

        foreach ($this->plugins as $plugin) {
            $plugin->addFileComponents($container, $this->options);
        }

        return $container;
    }

    public function addFileComponents(Container $container, array $options)
    {
        $container->set('diagnostics', new Diagnostics($container));
        $container->set('fix_helper', new FixHelper($container));
        $container->set('parser', new Parser($container), ['diagnostics']);
        $container->set('name_resolver', new NameResolver($container));
        $container->set('doc_comment', new DocCommentParser($container));
        $container->set('reflection', new Reflection($container));
        $container->set('reflection.file', new FileReflection($container), ['reflection']);
        $container->set('reflection.locator', new LocatorReflection($container), ['reflection']);
        //$container->set('reflection.locator.composer', new ComposerLocator($container), ['reflection.locator']);
        $container->set('typeinfer', new TypeInferrer($container));
        $container->set('typeinfer.basic', new BasicInferrer($container), ['typeinfer.visitor']);
        $container->set('typeinfer.reflection', new ReflectionInferrer($container), ['typeinfer.visitor']);
        $container->set('goto', new GoTo_($container));
        $container->set('goto.member_definition', new GoToMemberDefinition($container), ['goto']);
        $container->set('goto.class_definition', new GoToClassDefinition($container), ['goto']);
        $container->set('completer', new Completer($container));

        if ($options['diagnostics']['undefined']) {
            $container->set('diagnostics.undefined', new Diagnostics\Undefined($container), ['diagnostics.visitor']);
        }
    }

    public function createIndexerContainer(Project $project, $path, $contents = '')
    {
        $container = new Container($project->getProjectContainer());
        $container->set('file', new SourceFile($container, $path, $contents));

        foreach ($this->plugins as $plugin) {
            $plugin->addIndexerComponents($container, $this->options);
        }

        return $container;
    }

    public function addIndexerComponents(Container $container, array $options)
    {
        if ($options['indexer']['enabled']) {
            $container->set('parser', new Parser($container), ['diagnostics']);
            $container->set('name_resolver', new NameResolver($container));
            $container->set('reflection', new FileReflection($container), ['reflection']);
            $container->set('index_data.reflection', new ReflectionIndexData($container), ['index_data']);
        }
    }

    public function getFile($path)
    {
        return $this->globalContainer->get('file_store')->getFile($path);
    }

    public function addFile($path, $contents)
    {
        return $this->globalContainer->get('file_store')->addFile($path, $contents);
    }

    public function run()
    {
        $this->server->run();
    }

    public function quit()
    {
        $this->globalContainer->get('eventloop')->nextTick(function ($loop) {
            $this->server->quit();
            $this->globalContainer->quit();
            $loop->stop();
        });
    }
}
