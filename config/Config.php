<?php
/**
 * Config
 * @Filename         : Config.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-3 22:25
 * @Description      : this is global config
 */

class Config {

    /**
     * _values
     *
     * @var array
     */
    private $__values = array();

    /**
     * _loaded
     *
     * @var array
     */
    protected static $_loaded = array();

    /**
     * _loadedCache
     * 缓存是否已经加载过
     *
     */
    protected static $_loaded_cache = false;

    /**
     * _instance
     *
     * @var object
     */
    protected static $_instance = null;

    /**
     * _run_mode;
     * 运行环境
     */
    private $__run_mode;

    /**
     * get_instance
     * 获取一个Config类的实例
     *
     * @return object
     */
    public static function get_instance() {

        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * __construct
     * 私有的构造方法
     *
     * @return void
     */
    private function __construct() {

        $this->__run_mode = strtolower($_SERVER['RUN_MODE']);

        if (!$this->__run_mode) {
            $this->__run_mode = 'production';
        }

    }

    /**
     * get
     * 获取一个配置的值
     *
     * @param string $key 配置的key
     * @param mixed $default_value key不存在时返回默认值
     * @return mixed
     */
    public static function get($key, $default_value = null) {

        /**
         * Config::get优化查询说明
         * 1、在正式环境利用ARS发布系统的后置脚本，对所有配置文件打包成一个，避免重复的 is_file、Config::set
         * 2、利用APC的特性，关闭 apc.stat，直接 include 文件效率高
         * 3、以下特性由于使用量过小，不再支持
         *    app/src/config/%s/%s/%s.php 配置文件不再支持
         *    app/src/config/%s/global_%env.php 配置文件不再支持
         *    不再自动载入 framework 和 global 目录的配置，如需要读取框架和全局的配置，必须写 framework 或 global 前缀
         * 4、测试环境为开发方便，不会读入编译后的Config，还是使用原来的is_file方式判断文件是否存在
         */

        $config = Config::get_instance();
        $value = $config->_get($key);
        if (!isset($value)) {
            return $default_value;
        }

        return $value;
    }

    /**
     * exists
     * 检查一个配置是否存在
     *
     * @param  string $key 配置的key
     * @return boolean
     */
    public static function exists($key) {

        $value = self::get($key);

        return $value !== null;
    }

    /**
     * set
     * 设置值
     *
     * @param  string $key 键
     * @param  mixed $value 值
     * @return void
     */
    public static function set($key, $value) {

        $config = Config::get_instance();
        $config->__values[$key] = $value;
    }

    /**
     * _get
     * 获取一个配置的值
     *
     * @param string $key 配置的key
     * @return mixed
     */
    protected function _get($key) {

        if (isset($this->__values[$key])) {
            return $this->__values[$key];
        }

        $this->_load_key($key);
        $value = $this->_match($key);
        Config::set($key, $value);

        return $value;
    }

    /**
     * _load_cache
     * 读取正式环境下打包后的缓存
     *
     * @return void
     */
    protected function _load_cache() {

        /*
        if (!self::$_loaded_cache) {
            require ROOT_PATH . '/app/data/config_cache.php';
            self::$_loaded_cache = true;
        }
        */

    }

    /**
     * _match
     * 匹配
     *
     * @param  string $key
     * @return mixed
     */
    protected function _match($key) {

        if (isset($this->__values[$key])) {
            return $this->__values[$key];
        }

        $parts = explode('.', $key);
        if (!$parts) {
            return false;
        }

        $leave = array();
        for ($i = 0; $i < count($parts); $i ++) {
            $part = array_pop($parts);
            array_unshift($leave, $part);

            $pattern = join('.', $parts);
            $array = $this->__values[$pattern];
            if ($array) {
                break;
            }
        }

        if (!$array || !$leave) {
            return false;
        }

        if (!is_array($array)) {
            return null;
        }

        $value = $array;
        foreach ($leave as $part) {

            if ($value && is_array($value)) {
                $value = $value[$part];
            } else {
                $value = null;
                break;
            }
        }

        return $value;
    }

    /**
     * _load_key
     * 根据key加载配置
     *
     * @param string $key
     * @return void
     */
    protected function _load_key($key) {
        $path = explode('.', $key);
        $path1 = $path[0]; // 第一级路径
        $path2 = $path[1]; // 第二级路径

        if ($path1 == 'GLOBAL') {
            $root_key = $path1 . '.' . $path2;
            $file = ROOT_PATH . '/config/' . $path2 . '.php';
        }

        $this->_load($root_key, false, $file);
    }

    /**
     * _load
     * 载入配置
     *
     * @param  string $root_key 根key
     * @param  mixed $conf 配置
     * @param  string $file 定义配置文件
     * throw new Config_Exception
     * @return void
     */
    protected function _load($root_key, $conf = false, $file = null) {

        if ($conf === false) {
            if (in_array($file, self::$_loaded)) {
                return ;
            } else {
                array_push(self::$_loaded, $file);
            }

            include($file);
        }

        if (is_array($conf)) {
            $config = Config::get_instance();
            foreach ($conf as $key => $value) {
                $config->__values[$root_key . '.' . $key] = $value;
            }
        }
    }

    /**
     * compile_cache
     * 将所有 config 文件编译成一个文件，提高效率
     *
     * @return true
     */
    public static function compile_cache() {

        $config = Config::get_instance();
        // 重置已经载入的缓存
        self::$_loaded = array();
        $config->__values = array();

        // 重新编译缓存
        $config->_compile_cache();

        $cache_data = '$this->__values = ' . var_export($config->__values, true) . ";\n";

        $file_data = "<?php\n/**\n * 配置文件缓存，此文件由ARS发布系统后置脚本自动生成，请勿修改\n *\n * Identify: " . time() . "\n */\n\n" . $cache_data;
        file_put_contents(ROOT_PATH . '/app/data/config_cache.php', $file_data);

        return true;
    }

    /**
     * _compile_cache
     *
     * @param  string $dir
     * @param  integer $depth
     * @return void
     */
    protected function _compile_cache($dir = '', $depth = 0) {

        ++ $depth;

        if ($depth == 1) {
            $config_dir = dir(ROOT_PATH . '/app/src/config/');
        } elseif ($depth == 2) {
            $config_dir = dir(ROOT_PATH . '/app/src/config/' . $dir . '/');
        } else {
            return false;
        }


        if ($config_dir) {
            while (($file = $config_dir->read()) !== false) {
                if ($file == '.' || $file == '..' || strpos($file, '.swp') !== false) {
                    continue;
                }

                if ($depth < 2 && strpos($file, '.') === false) {
                    // 是目录且需要递归
                    $this->_compile_cache($file, $depth);

                } elseif (($pos = strpos($file, '.php')) && ($pos + 4) == strlen($file)) {
                    // 是 .php 结束的配置文件

                    if ($depth == 1 && strpos($file, 'global') !== 0) {
                        // 由于历史原因，第一级排除 global*
                        $root_key = substr($file, 0, -4);
                        $load_key = sprintf('%s.%s', $root_key, 'default');
                        $this->_load_key($load_key);

                    } elseif ($depth == 2 && strpos($file, 'ttc') !== 0) {
                        // 由于历史原因，第二级排除 ttc.php

                        $root_key = str_replace(array('_production', '_development', '_test'), '', $dir);
                        $file_key = substr($file, 0, -4);
                        $load_key = sprintf('%s.%s', $root_key, $file_key);
                        $this->_load_key($load_key);
                    }
                }
            }
        }

        return true;
    }

}
