<?php

namespace GlobalData;

class Queue
{
    public $globalData = null;
    public $maxQueueSize = 10000;
    public $maxQueuedCount = 0;
    public $bloomFilter = true;

    protected $name = '';
    protected $key = '';
    protected $queuedKey = '';
    protected $algorithm = 'depth';

    public function __construct($config)
    {
        $this->globalData = new Client($config['host'] . ':' . $config['port']);

        $this->name = $config['name'];
        $this->key = $config['name'] . 'Queue';
        $this->queuedKey = $config['name'] . 'Queued';
        if (isset($config['algorithm'])) {
            $this->algorithm = $config['algorithm'] != 'breadth' ? 'depth' : 'breadth';
        }
        if (isset($config['bloomFilter']) && !$config['bloomFilter']) {
            $this->bloomFilter = false;
        }

        $this->globalData->add($this->key, []);
        if ($this->bloomFilter) {
            $this->globalData->bfNew($this->queuedKey, [400000, 14]);
        } else {
            $this->globalData->add($this->queuedKey, []);
        }

        $this->globalData->add('beanbun', []);

        if (!isset($this->globalData->beanbun[$this->name])) {
            $name = $this->name;
            $this->globalData->up('beanbun', function ($value) use ($name) {
                if (!in_array($name, $value)) {
                    $value[] = $name;
                }
                return $value;
            });
        }
    }

    public function add($url, $options = [])
    {
        if (!$url && ($this->maxQueueSize != 0 && $this->count() >= $this->maxQueueSize)) {
            return;
        }

        $queue = serialize([
            'url' => $url,
            'options' => $options,
        ]);

        if ($this->isQueued($queue)) {
            return;
        }

        if (!isset($options['reserve']) || $options['reserve'] == false) {
            $this->globalData->pushIfNotExist($this->key, $queue);
        } else {
            $this->globalData->pushToLeftIfNotExist($this->key, $queue);
        }
    }

    public function next()
    {
        if ($this->algorithm == 'depth') {
            $queue = $this->globalData->shift($this->key);
        } else {
            $queue = $this->globalData->pop($this->key);
        }

        if ($this->isQueued($queue)) {
            return $this->next();
        } else {
            return unserialize($queue);
        }
    }

    public function count()
    {
        return $this->globalData->count($this->key);
    }

    public function queued($queue)
    {
        if ($this->bloomFilter) {
            $this->globalData->bfAdd($this->queuedKey, md5(serialize($queue)));
        } else {
            $this->globalData->push($this->queuedKey, serialize($queue));
        }
    }

    public function isQueued($queue)
    {
        if ($this->bloomFilter) {
            return $this->globalData->bfIn($this->queuedKey, md5(serialize($queue)));
        } else {
            return in_array($queue, $this->globalData->{$this->queuedKey});
        }
    }

    public function queuedCount()
    {
        if ($this->bloomFilter) {
            return 0;
        } else {
            return $this->globalData->count($this->queuedKey);
        }
    }

    public function clean()
    {
        unset($this->globalData->{$this->key});
        unset($this->globalData->{$this->queuedKey});
        $name = $this->name;
        $this->globalData->update('beanbun', function ($value) use ($name) {
            $key = array_search($name, $value);
            if ($key !== false) {
                unset($value[$key]);
            }
            return $value;
        });
    }
}
