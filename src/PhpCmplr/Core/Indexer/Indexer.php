<?php

namespace PhpCmplr\Core\Indexer;

use React\EventLoop\LoopInterface;
use ReactFilesystemMonitor\FilesystemMonitorInterface;
use ReactFilesystemMonitor\INotifyProcessMonitor;
use Psr\Log\LoggerInterface;

use PhpCmplr\Core\Component;
use PhpCmplr\Core\Container;
use PhpCmplr\Core\ContainerFactoryInterface;
use PhpCmplr\Core\Project;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Util\IOException;
use PhpCmplr\Util\BasicFileFilter;
use PhpCmplr\Util\FileFilterInterface;
use React\EventLoop\Timer\TimerInterface;
use PhpCmplr\Util\StopWatch;
use PhpCmplr\Util\Json;
use PhpCmplr\Util\JsonDumpException;
use PhpCmplr\Util\JsonLoadException;

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
     * @var LoggerInterface
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
     * @var array
     */
    private $updateQueueHash;

    /**
     * @var bool
     */
    private $updating;

    /**
     * @var int|float Seconds.
     */
    private $updateDelay;

    /**
     * @var TimerInterface
     */
    private $timer;

    /**
     * @var FileFilterInterface
     */
    private $fileFilter;

    /**
     * @var int
     */
    private $updateCounter;

    /**
     * @var int
     */
    private $saveCounter;

    private function loadCache()
    {
        try {
            if (!empty($this->cachePath) && $this->io->exists($this->cachePath)) {
                $data = Json::loadAsArray($this->io->read($this->cachePath));
                if (!is_array($data) || !isset($data['files']) || !isset($data['data'])) {
                    throw new JsonLoadException("Bad format");
                }
                $this->data = $data;
            }
        } catch (JsonLoadException $e) {
            $this->logger->notice("Indexer: can't load indexed data, corrupted: " . $e->getMessage(), ['exception' => $e]);
        } catch (IOException $e) {
            $this->logger->notice("Indexer: can't load indexed data: " . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * @internal
     */
    public function saveCache()
    {
        try {
            if (!empty($this->cachePath) && $this->updateCounter > $this->saveCounter) {
                $this->logger->debug('Indexer: save');
                $this->io->write($this->cachePath, Json::dump($this->data));
                $this->saveCounter = $this->updateCounter;
            }
        } catch (JsonDumpException $e) {
            $this->logger->notice("Indexer: can't save indexed data: " . $e->getMessage(), ['exception' => $e]);
        } catch (IOException $e) {
            $this->logger->notice("Indexer: can't save indexed data: " . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * @param string $path
     * @param bool   $deleted
     */
    private function enqueue($path, $deleted)
    {
        if (!array_key_exists($path, $this->updateQueueHash)) {
            $this->updateQueue->enqueue($path);
        }
        $this->updateQueueHash[$path] = $deleted;
    }

    /**
     * @return array [string path, bool deleted]
     */
    private function dequeue()
    {
        $path = $this->updateQueue->dequeue();
        $deleted = $this->updateQueueHash[$path];
        unset($this->updateQueueHash[$path]);

        return [$path, $deleted];
    }

    /**
     * @param bool $immediately
     */
    private function startUpdates($immediately = false)
    {
        if ($this->updating) {
            return;
        }

        if ($this->timer !== null && $this->timer->isActive()) {
            $this->timer->cancel();
        }

        if ($immediately) {
            $this->loop->futureTick([$this, 'update']);
        } else {
            $this->timer = $this->loop->addTimer($this->updateDelay, [$this, 'update']);
        }
    }

    /**
     * @internal
     */
    public function update()
    {
        if ($this->updateQueue->isEmpty()) {
            $this->updating = false;
            $this->loop->futureTick([$this, 'saveCache']);
            return;
        }

        $this->updating = true;
        list($path, $deleted) = $this->dequeue();

        if ($deleted || $this->io->match($path, $this->fileFilter)) {
            try {
                $this->logger->info("Indexer: index " . $path);
                $this->updateCounter++;

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

                if ($deleted) {
                    unset($this->data['files'][$path]);
                } else {
                    $this->data['files'][$path] = $this->io->getMTime($path);;
                }

            } catch (IOException $e) {
                $this->logger->notice("Indexer: can't index " . $path . ": " . $e->getMessage(), ['exception' => $e]);
            } catch (\Exception $e) {
                $this->logger->notice("Indexer: can't index " . $path . ": " . $e->getMessage(), ['exception' => $e]);
            } catch (\Error $e) { // PHP7
                $this->logger->notice("Indexer: can't index " . $path . ": " . $e->getMessage(), ['exception' => $e]);
            }
        }

        $this->loop->futureTick([$this, 'update']);
    }

    private function scan($path)
    {
        $time = new StopWatch();

        $freshFiles = $this->io->listFileMTimesRecursive(
            $path,
            $this->fileFilter
        );
        $curFiles =& $this->data['files'];

        foreach ($curFiles as $path => $mtime) {
            if (!array_key_exists($path, $freshFiles)) {
                $this->enqueue($path, true);
            }
        }

        foreach ($freshFiles as $path => $mtime) {
            if (!array_key_exists($path, $curFiles) || $curFiles[$path] !== $mtime) {
                $this->enqueue($path, false);
            }
        }

        $this->logger->debug(sprintf("Indexer: scan done [%s]", $time));
    }

    private function setupFsEvents()
    {
        $this->monitor->on('start', function () {
            $this->scan($this->project->getRootPath());
            $this->startUpdates(true);
        });
        $this->monitor->on('error', function ($e) {
            $this->logger->error('Indexer: Filesystem monitor: ' . $e, ['exception' => $e]);
        });
        $this->monitor->on('all', function ($path, $isDir, $event) {
            if ($isDir) {
                $this->scan($path);
            } else {
                $isDelete = in_array($event, ['delete', 'move_from']);
                $this->enqueue($path, $isDelete);
            }
            $this->startUpdates();
        });
    }

    private function watch()
    {
        $this->monitor->start($this->loop);
    }

    public function quit()
    {
        $this->logger->debug('Indexer: quit');
        $this->monitor->close();
        $this->updateQueue = new \SplQueue();
        $this->updateQueueHash = [];
        $this->updating = false;
        $this->saveCache();
    }

    public function getData($key) {
        if (!array_key_exists($key, $this->data['data'])) {
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
        $this->fileFilter = new BasicFileFilter(['php'], 1024*1024, ['file']);
        $this->updateDelay = 0.5;

        $this->updateQueue = new \SplQueue();
        $this->updateQueueHash = [];
        $this->updating = false;
        $this->updateCounter = 0;
        $this->saveCounter = 0;
        $this->loadCache();
        $fsEvents =  ['modify', 'delete', 'move_to', 'move_from'];
        $this->monitor = new INotifyProcessMonitor($this->project->getRootPath(), $fsEvents);
        $this->setupFsEvents();
        $this->watch();
    }
}
