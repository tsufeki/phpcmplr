<?php

namespace PhpCmplr;

use Psr\Log\LogLevel;
use React\EventLoop\Factory as EventLoopFactory;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\FileStoreInterface;
use PhpCmplr\Completer\Project;
use PhpCmplr\Completer\ProjectRootDirectoryGuesser;
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

class PhpCmplr extends Plugin implements FileStoreInterface
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
     * @var string[] file path => project root dir
     */
    private $projectRootDirCache;

    /**
     * @var Project[]
     */
    private $projects;

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
                'level' => LogLevel::DEBUG,
                'dir' => 'php://stderr',
            ],
        ], $options);

        $this->options = $options;
        $this->projects = [];
        $this->projectRootDirCache = [];
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

    public function addActions(Server $server, array $options)
    {
        $server->addAction(new Action\Ping());
        $server->addAction(new Action\Load());
        $server->addAction(new Action\Diagnostics());
        $server->addAction(new Action\Type());
        $server->addAction(new Action\GoTo_());
        $server->addAction(new Action\Complete());
        $server->addAction(new Action\Quit($server));
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

    public function addGlobalComponents(Container $container, array $options)
    {
        $container->set('file_store', $this);
        $container->set('logger', new Logger($options['log']['dir'], $options['log']['level'], [
            'logFormat' => "[{date}] [{level}] [pid:{pid}] {message}\n{exception}",
            'appendContext' => false,
        ]));
        $container->set('io', new FileIO());
        $container->set('eventloop', EventLoopFactory::create());
        $container->set('project_root_dir', new ProjectRootDirectoryGuesser($container->get('io')));
        $stdlibPath = __DIR__ . '/../../data/stdlib.json';
        $container->set('reflection.stdlib', new JsonReflection($container, $stdlibPath), ['reflection']);
    }

    private function createProject($rootPath)
    {
        $container = new Container($this->globalContainer);
        $project = new Project($rootPath, $container);
        //$container->set('project', $project);

        foreach ($this->plugins as $plugin) {
            $plugin->addProjectComponents($container, $this->options);
        }

        return $project;
    }

    public function addProjectComponents(Container $container, array $options)
    {
    }

    private function createFileContainer(Project $project, $path, $contents)
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
        $container->set('parser', new Parser($container), ['diagnostics']);
        $container->set('name_resolver', new NameResolver($container));
        $container->set('doc_comment', new DocCommentParser($container));
        $container->set('reflection', new Reflection($container));
        $container->set('reflection.file', new FileReflection($container), ['reflection']);
        $container->set('reflection.locator', new LocatorReflection($container), ['reflection']);
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
     * @param string $path
     *
     * @return string
     */
    private function getProjectRootPath($path)
    {
        if (array_key_exists($path, $this->projectRootDirCache)) {
            return $this->projectRootDirCache[$path];
        }

        $rootPath = $this->globalContainer->get('project_root_dir')->getProjectRootDir($path);
        if (empty($rootPath)) {
            $rootPath = '/';
        }
        $this->projectRootDirCache[$path] = $rootPath;

        return $rootPath;
    }

    public function getFile($path)
    {
        $path = $this->globalContainer->get('io')->canonicalPath($path);
        $projectRootPath = $this->getProjectRootPath($path);

        if (array_key_exists($projectRootPath, $this->projects)) {
            return $this->projects[$projectRootPath]->getFile($path);
        }

        return null;
    }

    public function addFile($path, $contents)
    {
        $path = $this->globalContainer->get('io')->canonicalPath($path);
        $projectRootPath = $this->getProjectRootPath($path);

        if (!array_key_exists($projectRootPath, $this->projects)) {
            $this->projects[$projectRootPath] = $this->createProject($projectRootPath);
        }
        $project = $this->projects[$projectRootPath];
        $fileContainer = $this->createFileContainer($project, $path, $contents);
        $project->addFile($path, $fileContainer);

        return $fileContainer;
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    public function run()
    {
        $this->server->run();
    }
}
