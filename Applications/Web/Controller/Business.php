<?php

namespace Web\Controller;

class Business extends Base
{
    /**
     * 打印共享数据
     * @param string $key 键名
     */
    public function dump($key)
    {
        echo '<pre>';
        var_dump($this->gdata($key));
    }

    /**
     * 打印共享数据
     * @param string $key 键名
     */
    public function export($key)
    {
        echo '<pre>';
        var_export($this->gdata($key));
    }
}