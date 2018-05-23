<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;

use think\cache\CacheItemPool;
use think\cache\Driver;

class Cache extends CacheItemPool
{
    /**
     * 缓存实例
     * @var array
     */
    protected $instance = [];

    /**
     * 应用对象
     * @var App
     */
    protected $app;

    /**
     * 操作句柄
     * @var object
     */
    protected $handler;

    public function __construct()
    {
        $this->app = Container::get('app');
    }

    public static function __make(Config $config)
    {
        return (new static())->init($config->pull('cache'));
    }

    /**
     * 连接缓存
     * @access public
     * @param  array         $options  配置数组
     * @param  bool|string   $name 缓存连接标识 true 强制重新连接
     * @return Driver
     */
    public function connect(array $options = [], $name = false)
    {

        if (false === $name) {
            $name = md5(serialize($options));
        }

        if (true === $name || !isset($this->instance[$name])) {
            $type = !empty($options['type']) ? $options['type'] : 'File';

            if (true === $name) {
                $name = md5(serialize($options));
            }

            $this->instance[$name] = Loader::factory($type, '\\think\\cache\\driver\\', $options);
        }

        return $this->instance[$name];
    }

    /**
     * 自动初始化缓存
     * @access public
     * @param  array         $options  配置数组
     * @param  bool          $force    强制更新
     * @return Driver
     */
    public function init(array $options = [], $force = false)
    {
        if (is_null($this->handler) || $force) {
            if ('complex' == $options['type']) {
                $default = $options['default'];
                $options = isset($options[$default['type']]) ? $options[$default['type']] : $default;
            }

            $this->handler = $this->connect($options);
        }

        return $this->handler;
    }

    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 切换缓存类型 需要配置 cache.type 为 complex
     * @access public
     * @param  string $name 缓存标识
     * @return Driver
     */
    public function store($name = '')
    {
        if ('' !== $name && 'complex' == $this->config['type']) {
            return $this->connect($this->config[$name], strtolower($name));
        }

        return $this->init();
    }

    public function get(string $key)
    {
        return $this->init()->get($key);
    }

    public function set(string $name, $value, $expire = null)
    {
        return $this->init()->set($name, $value, $expire);
    }

    public function delete(string $key)
    {
        return $this->init()->rm($key);
    }

    public function has(string $key)
    {
        return $this->init()->has($key);
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->init(), $method], $args);
    }

}
