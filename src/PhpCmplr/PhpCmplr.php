<?php

namespace PhpCmplr;

use Psr\Log\LogLevel;
use React\EventLoop\LoopInterface;
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
use PhpCmplr\Completer\Indexer\IndexerInterface;
use PhpCmplr\Completer\Indexer\ReflectionIndexData;
use PhpCmplr\Completer\Indexer\IndexLocator;
use PhpCmplr\Completer\Indexer\IndexNamespaceReflection;
use PhpCmplr\Server\Server;
use PhpCmplr\Server\Action;
use PhpCmplr\Util\FileIO;
use PhpCmplr\Util\Logger;
use PhpCmplr\Completer\Completer\MemberCompleter;
use PhpCmplr\Completer\Completer\VariableCompleter;
use PhpCmplr\Completer\DocComment\DocCommentNameResolver;
use PhpCmplr\Completer\Parser\PositionsReconstructor;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Util\IOException;
use PhpCmplr\Util\JsonLoadException;
use PhpCmplr\Util\Json;
use Psr\Log\LoggerInterface;

class PhpCmplr extends Plugin implements ContainerFactoryInterface
{
    const GLOBAL_OPTIONS_FILE = '.config/phpcmplr.json';
    const PROJECT_OPTIONS_FILE = 'phpcmplr-project.json';
    const STDLIB_DATA_PATH = __DIR__ . '/../../data/stdlib.json';

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
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var FileIOInterface
     */
    private $io;

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
        $this->loop = EventLoopFactory::create();
        $this->io = new FileIO();

        $this->plugins = array_merge([$this], $plugins);

        $this->options = [];
        foreach ($this->plugins as $plugin) {
            $this->options = array_replace_recursive($this->options, $plugin->getDefaultOptions());
        }
        /** @var \Exception */
        $savedException = null;
        $globalOptions = [];
        try {
            $globalOptionsFile = getenv('HOME') . '/' . self::GLOBAL_OPTIONS_FILE;
            $globalOptions = Json::loadAsArray($this->io->read($globalOptionsFile));
        } catch (IOException $e) {
        } catch (JsonLoadException $e) {
            $savedException = $e;
        }
        $this->options = array_replace_recursive($this->options, $globalOptions);
        $this->options = array_replace_recursive($this->options, $options);

        $this->globalContainer = $this->createGlobalContainer();
        if ($savedException !== null) {
            /** @var LoggerInterface */
            $logger = $this->globalContainer->get('logger');
            $logger->error("Config error in " . $globalOptionsFile . ": " . $savedException->getMessage(),
                ['exception' => $savedException]);
        }
        $this->server = $this->createServer();
    }

    public function getDefaultOptions()
    {
        return [
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
                'max' => 30,
                'undefined' => true,
                'undefined_member' => true,
                'undefined_doc_comment_type' => true,
                'duplicate_member' => true,
            ],
        ];
    }

    /**
     * @return Server
     */
    private function createServer()
    {
        $server = new Server(
            $this,
            $this->globalContainer->get('logger'),
            $this->loop,
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
        $container->set('io', $this->io);
        $container->set('eventloop', $this->loop);
        $container->set('project_root_dir', new ProjectRootDirectoryGuesser($container->get('io')));
        $container->set('reflection.stdlib', new JsonReflection($container, self::STDLIB_DATA_PATH), ['reflection']);
    }

    public function createProject($rootPath)
    {
        $projectOptions = [];
        try {
            $projectOptionsFile = $rootPath . '/' . self::PROJECT_OPTIONS_FILE;
            $projectOptions = Json::loadAsArray($this->io->read($projectOptionsFile));
        } catch (IOException $e) {
        } catch (JsonLoadException $e) {
            /** @var LoggerInterface */
            $logger = $this->globalContainer->get('logger');
            $logger->error("Config error in " . $globalOptionsFile . ": " . $e->getMessage(),
                ['exception' => $e]);
        }
        $options = array_replace_recursive($this->options, $projectOptions);

        $container = new Container($this->globalContainer);
        $project = new Project($rootPath, $container, $options);
        $container->set('project', $project);

        foreach ($this->plugins as $plugin) {
            $plugin->addProjectComponents($container, $options);
        }

        /** @var IndexerInterface */
        $indexer = $container->get('indexer');
        if ($indexer !== null) {
            $indexer->run();
        }

        return $project;
    }

    public function addProjectComponents(Container $container, array $options)
    {
        $container->set('namespace_reflection', new NamespaceReflection($container));
        /** @var Project */
        $project = $container->get('project');
        $rootPath = $project->getRootPath();
        if ($options['indexer']['enabled'] && !empty($rootPath) && $rootPath !== '/') {
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
            $plugin->addFileComponents($container, $project->getOptions());
        }

        return $container;
    }

    public function addFileComponents(Container $container, array $options)
    {
        $container->set('diagnostics', new Diagnostics($container, $options['diagnostics']['max']));
        $container->set('fix_helper', new FixHelper($container));
        $container->set('parser', new Parser($container), [['diagnostics', 99]]);
        $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
        $container->set('name_resolver', new NameResolver($container));
        $container->set('doc_comment', new DocCommentParser($container));
        $container->set('name_resolver.doc_comment', new DocCommentNameResolver($container), ['name_resolver']);
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
        $container->set('completer.member', new MemberCompleter($container), ['completer']);
        $container->set('completer.variable', new VariableCompleter($container), ['completer']);

        if ($options['diagnostics']['undefined']) {
            $container->set('diagnostics.undefined',
                new Diagnostics\Undefined($container), [['diagnostics.visitor', 50]]);
        }
        if ($options['diagnostics']['undefined_member']) {
            $container->set('diagnostics.undefined_member',
                new Diagnostics\UndefinedMember($container), [['diagnostics.visitor', 20]]);
        }
        if ($options['diagnostics']['undefined_doc_comment_type']) {
            $container->set('diagnostics.undefined_doc_comment_type',
                new Diagnostics\UndefinedDocCommentType($container), [['diagnostics.visitor', 10]]);
        }
        if ($options['diagnostics']['duplicate_member']) {
            $container->set('diagnostics.duplicate_member',
                new Diagnostics\DuplicateMember($container), [['diagnostics.visitor', 50]]);
        }
    }

    public function createIndexerContainer(Project $project, $path, $contents = '')
    {
        $container = new Container($project->getProjectContainer());
        $container->set('file', new SourceFile($container, $path, $contents));

        foreach ($this->plugins as $plugin) {
            $plugin->addIndexerComponents($container, $project->getOptions());
        }

        return $container;
    }

    public function addIndexerComponents(Container $container, array $options)
    {
        if ($options['indexer']['enabled']) {
            $container->set('parser', new Parser($container), ['diagnostics']);
            $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
            $container->set('name_resolver', new NameResolver($container));
            $container->set('reflection', new FileReflection($container), ['reflection']);
            $container->set('index_data.reflection', new ReflectionIndexData($container), ['index_data']);
        }
    }

    /**
     * @param string $path
     *
     * @return Container
     */
    public function getFile($path)
    {
        /** @var FileStoreInterface */
        $fileStore =  $this->globalContainer->get('file_store');
        return $fileStore->getFile($path);
    }

    /**
     * @param string $path
     * @param string $contents
     *
     * @return Container
     */
    public function addFile($path, $contents)
    {
        /** @var FileStoreInterface */
        $fileStore =  $this->globalContainer->get('file_store');
        return $fileStore->addFile($path, $contents);
    }

    public function run()
    {
        $this->server->run();
        $this->loop->run();
    }

    public function quit()
    {
        $this->loop->nextTick(function (LoopInterface $loop) {
            $this->server->quit();
            $this->globalContainer->quit();
            $loop->stop();
        });
    }
}
