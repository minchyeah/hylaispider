<?php
namespace Spider;

use Spider\Helper;
use Spider\Selector;
use Exception;
use GuzzleHttp\Client;
use Workerman\Lib\Timer;
use Workerman\Worker;
use Library\Db;

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
    public $interval = 1;
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
    public $mintid = 0;
    public $maxtid = 0;
    public $tid = 0;

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
        }else{
            $this->afterDownloadPageHooks[] = [$this, 'defaultAfterDownloadPage'];
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
        $this->initHooks();

        $this->setDownloader();
        $this->setLog();
        foreach ($this->startWorkerHooks as $hook) {
            call_user_func($hook, $this);
        }
        
        $this->queueArgs['name'] = $this->name;

        $this->gd()->mintid = 0;
        $this->gd()->maxtid = 0;
    }

    public function db()
    {
        return Db::instance(\Config\Database::$default);
    }

    protected function gd()
    {
        return \GlobalData\Client::getInstance(\Config\GlobalData::$address . ':' . \Config\GlobalData::$port);
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
            echo date('Y-m-d H:i:s ') . $msg . PHP_EOL;
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
        $options['verify'] = false;
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
        $this->tid = $this->getUrlTid($this->url);
    }

    public function defaultAfterDownloadPage()
    {
        if($this->urlType != 'content'){
            return;
        }
        preg_match('/tid=(\d+)/', $this->url, $matches);
        if(!isset($matches[1]) || !is_numeric($matches[1])){
            return;
        }
        $data = array();
        $data['tid'] = $matches[1];
        $start_time = 0;
        $end_time = 0;
        $set_rows = $this->db()->select('skey,svalue')
                ->from('pw_spider_settings')
                ->where('skey', 'start_time')
                ->orWhere('skey', 'end_time')
                ->query();
        if(!is_array($set_rows)){
            return;
        }
        foreach ($set_rows as $set) {
            if($set['skey'] == 'start_time'){
                $start_time = strtotime($set['svalue']);
            }
            if($set['skey'] == 'end_time'){
                $end_time = strtotime($set['svalue']);
            }
        }
        if($start_time ==0 || $end_time == 0){
            return;
        }
        $data['url'] = $this->url;
        $data['spide_time'] = time();
        $html = $this->page;
        $fields = \Config\Spider::$fields;
        foreach ($fields as $conf)
        {
            // 当前field抽取到的内容是否是有多项
            $repeated = isset($conf['repeated']) && $conf['repeated'] ? true : false;
            // 当前field抽取到的内容是否必须有值
            $required = isset($conf['required']) && $conf['required'] ? true : false;

            if (empty($conf['name'])) 
            {
                break;
            }
            $values = NULL;
            // 如果定义抽取规则
            if (!empty($conf['selector'])) 
            {
                // 没有设置抽取规则的类型 或者 设置为 xpath
                if (!isset($conf['selector_type']) || $conf['selector_type']=='xpath') 
                {
                    // 如果找不到，返回的是false
                    if($conf['name'] == 'content'){
                        //$html = $this->replaceQuoteTips($html);
                    }
                    $values = $this->get_fields_xpath($html, $conf['selector'], $conf['name']);
                }
                elseif ($conf['selector_type']=='css') 
                {
                    $values = $this->get_fields_css($html, $conf['selector'], $conf['name']);
                }
                elseif ($conf['selector_type']=='regex') 
                {
                    $values = $this->get_fields_regex($html, $conf['selector'], $conf['name']);
                }
            }
            if(isset($conf['filter']) && $conf['filter'] && $values){
                    $values = preg_replace($conf['filter'], '', $values);
            }
            //$this->log(print_r($values, true));
            switch ($conf['name']) {
                case 'author':
                    $author_row = $this->db()->select('*')
                            ->from('pw_spider_authors')
                            ->where('sp_author', $values)
                            ->row();
                    if(!isset($author_row['id'])){
                        return;
                    }
                    break;
                case 'post_time':
                    $values = strtotime($values);
                    if($values < $start_time){
                        $mintid = intval($this->gd()->mintid);
                        $this->gd()->mintid = max($mintid, $this->getUrlTid($this->url));
                        unset($data, $values);
                        return;
                    }
                    if($values > $end_time){
                        $maxtid = intval($this->gd()->maxtid);
                        if($maxtid == 0){
                            $this->gd()->maxtid = $this->getUrlTid($this->url);
                        }else{
                            $this->gd()->maxtid = min($maxtid, $this->getUrlTid($this->url));
                        }
                        unset($data, $values);
                        return;
                    }
                    break;
                case 'content':
                    $values = str_replace('&#13;', '<br >', $values);
                    $values = $this->replaceQuoteTips($values);
                    break;
                
                default:
                    # code...
                    break;
            }
            $data[$conf['name']] = $values;
        }

        $row = $this->db()
                    ->select('id,tid,subject,content')
                    ->from('pw_spider')
                    ->where('tid', $data['tid'])
                    ->row();
        if(isset($row['id']) && $row['id'] > 0){
            $worker_id = isset($this->id) ? $this->worker->id : '';
            $this->log("Spider worker {$worker_id} udpate {$this->url} content.");
            if($row['subject'] != $data['subject'] || $row['content'] != $data['content']){
                $this->db()
                    ->update('pw_spider')
                    ->set('subject', $data['subject'])
                    ->set('content', $data['content'])
                    ->set('spide_time', $data['spide_time'])
                    ->set('new_state', 1)
                    ->where('id', $row['id'])
                    ->where('tid', $data['tid'])
                    ->query();
            }
        }else{
            $this->db()
                ->insert('pw_spider')
                ->cols($data)
                ->query();
        }
    }

    public function defaultDiscoverUrl()
    {
        $urls = Helper::getUrlByHtml($this->page, $this->url);
        $sp_domain = $this->db()
                ->select('svalue')
                ->from('pw_spider_settings')
                ->where('skey', 'sp_domain')
                ->single();
        foreach ($urls as $key=>$url) {
            if(0 === strpos('http', $url) && false === strpos($sp_domain, $url)){
                unset($urls[$key]);
            }
        }
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
                    preg_match('/fid=(\d+)&page=(\d+)/', $url, $matches);
                    if(isset($matches[2]) && is_numeric($matches[2]) && $matches[2] > 50){
                        continue;
                    }
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
        $mintid = intval($this->gd()->mintid);
        $maxtid = intval($this->gd()->maxtid);
        foreach ($urls as $url) {
            foreach ($this->contentUrlFilter as $urlPattern) {
                if (preg_match($urlPattern, $url)) {
                    $this->queue()->add($url, ['url_type'=>'content']);
                }
            }
        }
    }

    protected function getUrlTid($url)
    {
        preg_match('/tid=(\d+)/', $url, $matches);
        if(!isset($matches[1]) || !is_numeric($matches[1])){
            return 0;
        }
        return $matches[1];
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

    public function replaceQuoteTips($html)
    {
        $selector = '//div[contains(@class,"quoteTips")]';
        $html = Selector::remove($html, $selector);

        $selector = '//div[contains(@class,"mb10")]';
        $html = Selector::remove($html, $selector);

        $selector = '//div[contains(@class,"f12")]';
        $html = Selector::remove($html, $selector);

        $filter = '/<div[\s]+class=".*"([^>]*)>((?:.(?!\<\/div\>))*.)<\/div>/';
        $html = preg_replace($filter, '', $html);

        return trim($html);
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
