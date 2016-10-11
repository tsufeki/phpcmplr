<?php

namespace PhpCmplr\Completer\Indexer;

use React\EventLoop\LoopInterface;
use ReactFilesystemMonitor\FilesystemMonitorInterface;
use ReactFilesystemMonitor\INotifyProcessMonitor;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\ContainerFactoryInterface;
use PhpCmplr\Completer\Project;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Util\IOException;

class Indexer extends Component implements IndexerInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var FileIOInterface
     */
    private $io;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var ContainerFactoryInterface
     */
    private $factory;

    /**
     * @var mixed
     */
    private $logger;

    /**
     * @var FilesystemMonitorInterface
     */
    private $monitor;

    /**
     * @var \SplQueue
     */
    private $updateQueue;

    /**
     * @var string[]|null
     */
    private $extensions = ['php'];

    /**
     * @var int|null
     */
    private $maxSize = 1024*1024;

    /**
     * @var bool
     */
    private $updating = false;

    private function loadCache()
    {
        try {
            $data = json_decode($this->io->read($this->cachePath), true);
            if (!is_array($data) || !isset($data['files']) || !isset($data['data'])) {
                throw new IOException("Corrupted indexer cache");
            }
            $this->data = $data;
        } catch (IOException $e) {
            $this->logger->notice("Can't load indexed data: " . $e->getMessage(), ['exception' => $e]);
        }
    }

    private function saveCache()
    {
        try {
            $this->io->write($this->cachePath, json_encode($this->data));
        } catch (IOException $e) {
            $this->logger->error("Can't save indexed data: " . $e->getMessage(), ['exception' => $e]);
        }
    }

    private function scan()
    {
        $freshFiles = $this->io->listFileMTimesRecursive(
            $this->project->getRootPath(),
            $this->extensions,
            $this->maxSize
        );
        $curFiles =& $this->data['files'];

        foreach ($curFiles as $path => $mtime) {
            if (!array_key_exists($path, $freshFiles)) {
                $this->updateQueue->enqueue([$path, true]);
            }
        }

        foreach ($freshFiles as $path => $mtime) {
            if (!array_key_exists($path, $curFiles) || $curFiles[$path] !== $mtime) {
                $this->updateQueue->enqueue([$path, false]);
            }
        }
    }

    /**
     * @internal
     */
    public function update()
    {
        if ($this->updateQueue->isEmpty()) {
            return;
        }

        list($path, $deleted) = $this->updateQueue->dequeue();

        try {
            $contents = $deleted ? '' : $this->io->read($path);
            /** @var Container */
            $cont = $this->factory->createIndexerContainer($this->project, $path, $contents);

            $components = $cont->getByTag('index_data');
            /** @var IndexDataInterface $indexData */
            foreach ($components as $indexData) {
                $key = $indexData->getKey();
                if (!isset($this->data['data'][$key])) {
                    $this->data['data'][$key] = [];
                }
                $indexData->update($this->data['data'][$key]);
            }
            $this->logger->info("Indexed " . $path);

        } catch (IOException $e) {
            $this->logger->notice("Can't index " . $path . ": " . $e->getMessage(), ['exception' => $e]);
        } catch (\Exception $e) {
            $this->logger->notice("Can't index " . $path . ": " . $e->getMessage(), ['exception' => $e]);
        } catch (\Error $e) { // PHP7
            $this->logger->notice("Can't index " . $path . ": " . $e->getMessage(), ['exception' => $e]);
        }

        if (!$this->updateQueue->isEmpty()) {
            $this->loop->futureTick([$this, 'update']);
        } else {
            $this->updating = false;
            $this->saveCache();
        }
    }

    private function startUpdates()
    {
        if (!$this->updateQueue->isEmpty()) {
            $this->updating = true;
            $this->loop->futureTick([$this, 'update']);
        }
    }

    private function setupFsEvents()
    {
        $this->monitor->on('start', function () {
            $this->scan();
            $this->startUpdates();
        });

        $this->monitor->on('modify', function ($path) {
            $this->updateQueue->enqueue([$path, false]);
            $this->startUpdates();
        });
        $this->monitor->on('move_to', function ($path) {
            $this->updateQueue->enqueue([$path, false]);
            $this->startUpdates();
        });
        $this->monitor->on('move_from', function ($path) {
            $this->updateQueue->enqueue([$path, true]);
            $this->startUpdates();
        });
        $this->monitor->on('delete', function ($path) {
            $this->updateQueue->enqueue([$path, true]);
            $this->startUpdates();
        });
    }

    private function watch()
    {
        $this->monitor->start($this->loop);
    }

    public function quit()
    {
        $this->monitor->close();
    }

    public function getData($key) {
        if (!array_key_exists($key, $this->data['data'][$key])) {
            return [];
        }

        return $this->data['data'][$key];
    }

    protected function doRun()
    {
        $this->data = [
            'files' => [],
            'data' => [],
        ];
        $this->logger = $this->container->get('logger');
        $this->io = $this->container->get('io');
        $this->loop = $this->container->get('eventloop');
        $this->factory = $this->container->get('factory');
        $this->project = $this->container->get('project');
        $this->cachePath = $this->io->getCacheDir('indexer') . '/' . sha1($this->project->getRootPath()) . '.json';

        $this->updateQueue = new \SplQueue();
        $this->loadCache();
        $fsEvents =  ['modify', 'delete', 'move_to', 'move_from'];
        $this->monitor = new INotifyProcessMonitor($this->project->getRootPath(), $fsEvents);
        $this->setupFsEvents();
        $this->watch();
    }
}