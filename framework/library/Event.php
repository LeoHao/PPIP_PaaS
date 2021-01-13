<?php
/**
 * @Filename         : Event.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-13 02:31
 * @Description      : event dispose
 **/

class Event {

    /**
     * $_events
     *
     * @var array
     */
    protected static $_events = array();

    /**
     * on
     * 监听事件
     *
     * @param  string $name     事件名称
     * @param  mixed  $callback 事件回调
     * @return void
     */
    public static function on($name, $callback) {

        if (!self::is_exist($name)) {
            self::$_events[$name]['callbacks'] = array();
        }

        self::$_events[$name]['callbacks'][] = $callback;
    }

    /**
     * remove
     * 移除事件
     *
     * @param  string $name     事件名称
     * @param  mixed  $callback 事件回调
     * @return void
     */
    public static function remove($name, $callback) {

        if (!self::is_exist($name)) {
            return;
        }

        $callbacks = array();
        foreach (self::$_events[$name]['callbacks'] as $key => $registered_callback) {
            if ($callback !== $registered_callback) {
                $callbacks[] = $registered_callback;
            }
        }

        self::$_events[$name]['callbacks'] = $callbacks;
    }

    /**
     * emit
     * 触发事件
     *
     * @param  string $name     事件名称
     * @param  mixed  $payload  事件触发参数
     * @return array
     */
    public static function emit($name, $payload = array()) {

        // 记录事件触发的返回值
        $responses = array();

        if (!self::is_exist($name)) {
            return $responses;
        }

        // 依次触发事件的所有回调
        foreach (self::$_events[$name]['callbacks'] as $callback) {
            if (is_callable($callback)) {
                $responses[] = call_user_func($callback, $payload);
            } elseif ($callback instanceof Closure) {
                $responses[] = $callback($payload);
            }
        }

        return $responses;
    }

    /**
     * clear
     *
     * @return void
     */
    public static function clear() {

        self::$_events = array();
    }

    /**
     * is_exist
     * 检查事件是否存在
     *
     * @param  string $name     事件名称
     * @return boolean
     */
    public static function is_exist($name) {

        return array_key_exists($name, self::$_events);
    }
}