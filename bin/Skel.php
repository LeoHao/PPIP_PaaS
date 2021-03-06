<?php
/**
 * Config
 * @Filename         : Skel.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-3 22:25
 * @Description      : autoloader
 */

define('ROOT_PATH' , dirname(dirname(__FILE__)));
define('LOG_SWOOLE_PATH' , dirname(dirname(__FILE__)) . '/logs/swoole/');
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
date_default_timezone_set('Asia/Shanghai');

class Skel {

    /**
     * _instance
     *
     * @object
     */
    public static $_instance;

    /**
     * _config
     *
     * @array
     */
    public static $_config;

    /**
     * getInstance
     *
     * @return object
     */
    public static function getInstance() {

        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * main
     *
     * @return void
     */
    public static function main() {
        try {
            $skel = self::getInstance();
            $skel->_registAutoload();
        } catch(Exception $e) {
            printf("\033[1;37;41mAn Error Has Occurred\033[0m" . PHP_EOL);
            printf($e->getMessage() . PHP_EOL);
        }
    }

    /**
     * _registAutoload
     *
     * @return void
     */
    protected function _registAutoload() {
        $require_path = array('/framework/library/',
            '/framework/library/DB/',
            '/framework/library/PEAR2/',
            '/config/',
            '/logs/',
            '/include/'
        );
        $params = $_SERVER['argv'];
        $filename = array_shift($params);
        $this->mapFilePath($require_path, $filename);
    }

    public function mapFilePath($paths, $exist_file) {
        foreach ($paths as $path) {
            $libraryPath = dirname(dirname(__FILE__)) . $path;
            $allFiles = scandir($libraryPath);
            foreach ($allFiles as $singleFileName) {
                if (strtolower(pathinfo($singleFileName, PATHINFO_EXTENSION)) != 'php') {
                    if($singleFileName === '.' || $singleFileName === '..'){
                        continue;
                    } else {
                           /* $secondPath = $libraryPath . $singleFileName . '/';
                            $secondFiles = scandir($secondPath);
                            foreach ($secondFiles as $fileName) {
                                if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) == 'php') {
                                    include_once ($secondPath . $fileName);
                                }
                            }*/
                    }
                    continue;
                }
                if (($libraryPath . $singleFileName) != $exist_file) {
                    include_once ($libraryPath . $singleFileName);
                }
            }
        }
    }
}

Skel::main();
