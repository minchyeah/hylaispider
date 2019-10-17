<?php
namespace Spider;

use Spider\Helper;
use Spider\Selector;
use Exception;
use GuzzleHttp\Client;
use Workerman\Lib\Timer;
use Workerman\Worker;

class Spider
{
    const VERSION = '1.0.4';

    public $id = null;
    public $name = null;
    public $max = 0;
    public $seed = [];
    public $urlFilter = [];
    public $listUrlFilter = [];
    public $contentUrlFilter = [];
    public $interval = 0.1;
    public $timeout = 5;
    public $userAgent = 'pc';
    public $logFile = '';
    public $commands = [];

    public $queue = '';
    public $url = '';
    public $urlType = '';
    public $method = '';
    public $options = [];
    public $page = '';

    public $startWorker = '';
    public $beforeDownloadPage = '';
    public $downloadPage = '';
    public $afterDownloadPage = '';
    public $discoverUrl = '';
    public $afterDiscover = '';
    public $stopWorker = '';
    public $exceptionHandler = '';

    public $hooks = [
        'startWorkerHooks',
        'beforeDownloadPageHooks',
        'downloadPageHooks',
        'afterDownloadPageHooks',
        'discoverUrlHooks',
        'afterDiscoverHooks',
        'stopWorkerHooks',
    ];
    public $startWorkerHooks = [];
    public $beforeDownloadPageHooks = [];
    public $downloadPageHooks = [];
    public $afterDownloadPageHooks = [];
    public $discoverUrlHooks = [];
    public $afterDiscoverHooks = [];
    public $stopWorkerHooks = [];

    protected $queues = null;
    protected $downloader = null;
    protected $worker = null;
    protected $timer_id = null;
    protected $queueFactory = null;
    protected $queueArgs = [];
    protected $downloaderFactory = null;
    protected $downloaderArgs = [];
    protected $logFactory = null;

    public static function timer($interval, $callback, $args = [], $persistent = true)
    {
        return Timer::add($interval, $callback, $args, $persistent);
    }

    public static function timerDel($time_id)
    {
        Timer::del($time_id);
    }

    public function __construct($config = [])
    {
        $this->name = isset($config['name']) ? $config['name'] : 'hylaispider';
        $this->logFile = isset($config['logFile']) ? $config['logFile'] : __DIR__ . '/' . $this->name . '_access.log';
    }

    // 执行爬虫
    public function start()
    {
        $worker = new Worker;
        $worker->count = $this->count;
        $worker->name = $this->name;
        $worker->onWorkerStart = [$this, 'onWorkerStart'];
        $worker->onWorkerStop = [$this, 'onWorkerStop'];
        $this->worker = $worker;

        Worker::$stdoutFile = $this->logFile;

    }

    public function initHooks()
    {
        $this->startWorkerHooks[] = function ($spider) {
            $spider->id = $spider->worker->id;
            $spider->log("Spider worker {$spider->id} is starting ...");
        };

        if ($this->startWorker) {
            $this->startWorkerHooks[] = $this->startWorker;
        }

        $this->startWorkerHooks[] = function ($spider) {
            $spider->queue()->maxQueueSize = $spider->max;
            $spider->timer_id = Spider::timer($spider->interval, [$spider, 'crawler']);
        };

        $this->beforeDownloadPageHooks[] = [$this, 'defaultBeforeDownloadPage'];

        if ($this->beforeDownloadPage) {
            $this->beforeDownloadPageHooks[] = $this->beforeDownloadPage;
        }

        if ($this->downloadPage) {
            $this->downloadPageHooks[] = $this->downloadPage;
        } else {
            $this->downloadPageHooks[] = [$this, 'defaultDownloadPage'];
        }

        if ($this->afterDownloadPage) {
            $this->afterDownloadPageHooks[] = $this->afterDownloadPage;
        }

        if ($this->discoverUrl) {
            $this->discoverUrlHooks[] = $this->discoverUrl;
        } else {
            $this->discoverUrlHooks[] = [$this, 'defaultDiscoverUrl'];
        }

        if ($this->afterDiscover) {
            $this->afterDiscoverHooks[] = $this->afterDiscover;
        }

        $this->afterDiscoverHooks[] = function ($spider) {
            if ($spider->options['reserve'] == false) {
                $spider->queue()->queued($spider->queue);
            }
        };

        if ($this->stopWorker) {
            $this->stopWorkerHooks[] = $this->stopWorker;
        }

        if (!$this->exceptionHandler) {
            $this->exceptionHandler = [$this, 'defaultExceptionHandler'];
        }
    }

    // 爬虫进程
    public function onWorkerStart($worker)
    {
        $this->setQueue([
            'name' => $this->name,
            'host' => \Config\Queue::$address,
            'port' => \Config\Queue::$port
        ]);
        foreach ((array) $this->seed as $url) {
            if (is_string($url)) {
                $this->queue()->add($url, ['url_type'=>'list']);
            } elseif (is_array($url)) {
                $this->queue()->add($url[0], $url[1]);
            }
        }
        $this->initHooks();

        $this->setDownloader();
        $this->setLog();
        foreach ($this->startWorkerHooks as $hook) {
            call_user_func($hook, $this);
        }
        
        $this->queueArgs['name'] = $this->name;
    }

    public function queue()
    {
        if ($this->queues == null) {
            $this->queues = call_user_func($this->queueFactory, $this->queueArgs);
        }
        return $this->queues;
    }

    public function setQueue($args = [
        'host' => '127.0.0.1',
        'port' => '2207',
    ]) {
        $this->queueFactory = function ($args) {
            return new \Queue\Queue($args);
        };

        $this->queueArgs = $args;
    }

    public function downloader()
    {
        if ($this->downloader === null) {
            $this->downloader = call_user_func($this->downloaderFactory, $this->downloaderArgs);
        }
        return $this->downloader;
    }

    public function setDownloader($callback = null, $args = [])
    {
        if ($callback === null) {
            $this->downloaderFactory = function ($args) {
                return new Client($args);
            };
        } else {
            $this->downloaderFactory = $callback;
        }
        $this->downloaderArgs = $args;
    }

    public function log($msg)
    {
        call_user_func($this->logFactory, $msg, $this);
    }

    public function setLog($callback = null)
    {
        $this->logFactory = $callback === null
        ? function ($msg, $spider) {
            echo date('Y-m-d H:i:s') . " {$spider->name} : $msg\n";
        }
        : $callback;
    }

    public function error($msg = null)
    {
        throw new Exception($msg);
    }

    public function crawler()
    {
        try {
            $allHooks = $this->hooks;
            array_shift($allHooks);
            array_pop($allHooks);

            foreach ($allHooks as $hooks) {
                foreach ($this->$hooks as $hook) {
                    call_user_func($hook, $this);
                }
            }
        } catch (Exception $e) {
            call_user_func($this->exceptionHandler, $e);
        }

        $this->queue = '';
        $this->url = '';
        $this->urlType = '';
        $this->method = '';
        $this->page = '';
        $this->options = [];
    }

    public function onWorkerStop($worker)
    {
        sleep(1);
        foreach ($this->stopWorkerHooks as $hook) {
            call_user_func($hook, $this);
        }
    }

    public function defaultExceptionHandler(Exception $e)
    {
        if ($e instanceof Exception) {
            if ($e->getMessage()) {
                $this->log($e->getMessage());
            }
        } elseif ($e instanceof Exception) {
            $this->log($e->getMessage());
            $this->queue()->add($this->queue['url'], $this->queue['options']);
        }
    }

    public function defaultBeforeDownloadPage()
    {
        if ($this->max > 0 && $this->queue()->queuedCount() >= $this->max) {
            $this->log("Download to the upper limit, Spider worker {$this->id} stop downloading.");
            self::timerDel($this->timer_id);
            $this->error();
        }

        $this->queue = $queue = $this->queue()->next();

        if (is_null($queue) || !$queue) {
            sleep(1);
            $this->error();
        }

        if (!is_array($queue)) {
            $this->queue = $queue = [
                'url' => $queue,
                'options' => [],
            ];
        } else{
            $this->queue = $queue;
        }
        if(isset($queue['options']['url_type']) && 
            in_array($queue['options']['url_type'], ['list','content'])){
            $this->urlType = $queue['options']['url_type'];
        }else{
            $this->urlType = 'list';
        }

        $options = array_merge([
            'headers' => isset($this->options['headers']) ?: [],
            'reserve' => false,
            'timeout' => $this->timeout,
        ], (array) $queue['options']);

        if (!$options['reserve'] && $this->queue()->isQueued($queue)) {
            $this->error();
        }

        $this->url = $queue['url'];
        $this->method = isset($options['method']) ? $options['method'] : 'GET';
        $this->options = $options;
        if (!isset($this->options['headers']['User-Agent'])) {
            $this->options['headers']['User-Agent'] = Helper::randUserAgent($this->userAgent);
        }
    }

    public function defaultDownloadPage()
    {
        $response = $this->downloader()->request($this->method, $this->url, $this->options);
        $this->page = $response->getBody();
        if ($this->page) {
            $worker_id = isset($this->id) ? $this->worker->id : '';
            $this->log("Spider worker {$worker_id} download {$this->url} success.");
        } else {
            $this->error();
        }
    }

    public function defaultDiscoverUrl()
    {
        $urls = Helper::getUrlByHtml($this->page, $this->url);
        $this->discoverListUrl($urls);
        $this->discoverContentUrl($urls);
    }

    public function discoverListUrl($urls)
    {
        $count = count($this->listUrlFilter);
        if ($count === 1 && !$this->listUrlFilter[0]) {
            $this->error();
        }
        foreach ($urls as $url) {
            foreach ($this->listUrlFilter as $urlPattern) {
                if (preg_match($urlPattern, $url)) {
                    //$this->log("get list url from {$this->url} ". print_r($url, true).PHP_EOL);
                    $this->queue()->add($url, ['url_type'=>'list']);
                }
            }
        }
    }

    public function discoverContentUrl($urls)
    {
        $count = count($this->contentUrlFilter);
        if ($count === 1 && !$this->contentUrlFilter[0]) {
            $this->error();
        }
        foreach ($urls as $url) {
            foreach ($this->contentUrlFilter as $urlPattern) {
                if (preg_match($urlPattern, $url)) {
                    //$this->log("get content url from {$this->url} ". print_r($url, true).PHP_EOL);
                    $this->queue()->add($url, ['url_type'=>'content']);
                }
            }
        }
    }

    public function middleware($middleware, $action = 'handle')
    {
        if (is_object($middleware)) {
            $middleware->$action($this);
        } else {
            call_user_func($middleware, $this);
        }
    }

    /**
     * 采用xpath分析提取字段
     * 
     * @param mixed $html
     * @param mixed $selector
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function get_fields_xpath($html, $selector, $fieldname) 
    {
        $result = Selector::select($html, $selector);
        if (Selector::$error)
        {
            $this->log("Field(\"{$fieldname}\") ".Selector::$error."\n");
        }
        return $result;
    }

    /**
     * 采用正则分析提取字段
     * 
     * @param mixed $html
     * @param mixed $selector
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function get_fields_regex($html, $selector, $fieldname) 
    {
        $result = Selector::select($html, $selector, 'regex');
        if (Selector::$error) 
        {
            $this->log("Field(\"{$fieldname}\") ".Selector::$error."\n");
        }
        return $result;
    }

    /**
     * 采用CSS选择器提取字段
     * 
     * @param mixed $html
     * @param mixed $selector
     * @param mixed $fieldname
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function get_fields_css($html, $selector, $fieldname) 
    {
        $result = Selector::select($html, $selector, 'css');
        if (Selector::$error) 
        {
            $this->log("Field(\"{$fieldname}\") ".Selector::$error."\n");
        }
        return $result;
    }
}