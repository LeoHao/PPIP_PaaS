<?php
/**
 * Logger 日志类
 * @Filename         : Logger.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-3 22:25
 * @Description      : this is logger
 *
 * <code>
 *     Logger::set_level(LOGGER_LEVEL_ERROR);
 *     function test() {
 *         Logger::debug('test2');
 *     }
 *     Logger::fatal('test1');
 *     test();
 * </code>
 */

class Logger {

    /**
     * 错误级别
     */
    const LOGGER_LEVEL_TRACE = 1;
    const LOGGER_LEVEL_DEBUG = 2;
    const LOGGER_LEVEL_WARNING = 3;
    const LOGGER_LEVEL_ERROR = 4;
    const LOGGER_LEVEL_FATAL = 5;

    /**
     * 监控类型
     */
    const MONITOR_TYPE_ERROR = 'error';
    const MONITOR_TYPE_STATS = 'stats';

    /**
     * crlf
     * 换行符
     *
     * @var string
     */
    public static $crlf = PHP_EOL;

    /**
     * $delimiter
     *
     * @var string
     */
    public static $delimiter = '|';

    /**
     * write
     *
     * @param  mixed $fpath
     * @param  mixed $message
     * @access public
     * @return void
     */
    static function write($fpath, $message, $dir) {

        $dir = rtrim($dir, '/') . '/' . ltrim($fpath, '/');

        $message = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        // 将所有日志写入syslog
        self::fs_write($dir, $message);
    }

    /**
     * fs_write
     *
     * @param  string $path    日志存储路径
     * @param  string $message 日志内容
     * @return void
     */
    public static function fs_write($path, $message, $with_hostname = true) {

        if ($with_hostname) {
            $message .= ' [' . gethostname() . ']';
        }

        // 日志统一去除换行
        $message = str_replace(self::$crlf, "\t", $message) . self::$crlf;
        self::x_write($path, $message);
    }

    /**
     * x_write
     *
     * @param  string $path    日志存储路径
     * @param  string $message 日志内容
     * @return void
     */
    public static function x_write($path, $message) {

        // 自动创建不存在的目录
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $fd = fopen($path, 'a+');
        if (!$fd) {
            error_log('Logger: Cannot open file (' . $path . ')');
            return false;
        }

        fwrite($fd, $message);
        fclose($fd);
    }

    /**
     * debug
     *
     * @param  mixed $message
     * @access public
     * @return void
     */
    static function debug($message, $dir) {
        return self::log($message, self::LOGGER_LEVEL_DEBUG, $dir);
    }

    /**
     * warning
     *
     * @param  mixed $message
     * @access public
     * @return void
     */
    static function warning($message, $dir) {
        return self::log($message, self::LOGGER_LEVEL_WARNING, $dir);
    }

    /**
     * trace
     *
     * @param  mixed $message
     * @access public
     * @return void
     */
    static function trace($message, $dir) {
        return self::log($message, self::LOGGER_LEVEL_TRACE, $dir);
    }

    /**
     * fatal
     *
     * @param  mixed $message
     * @access public
     * @return void
     */
    static function fatal($message, $dir) {
        return self::log($message, self::LOGGER_LEVEL_FATAL, $dir);
    }

    /**
     * error
     *
     * @param  string $message
     * @access public
     * @return void
     */
    static function error($message, $dir) {
        return self::log($message, self::LOGGER_LEVEL_ERROR, $dir);
    }

    /**
     * log
     *
     * @param  mixed $message
     * @param  mixed $level
     * @access public
     * @return void
     */
    static function log($message, $level, $dir) {

        if ($message instanceof Exception) {
            // 异常记录Trace日志
            $log_trace = '';
            $exception_trace = reset($message->getTrace());
            if ($exception_trace) {
                $log_trace = sprintf("[%s%s%s] [code: %s] ", $exception_trace['class'], $exception_trace['type'], $exception_trace['function'], $message->getCode());
            }
            $message = $log_trace . $message->getMessage();
        } elseif (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        $backtrace = debug_backtrace();
        array_shift($backtrace); // myself
        $traceinfo = array_shift($backtrace);
        $filePath = basename($traceinfo['file']);
        $fileName = str_replace(strrchr($filePath, '.'), '', $filePath);
        $traceinfo['class_name'] = $fileName;

        $conf['level']  = $level;
        $conf['output'] = 'file';
        $conf['output_dir'] = ROOT_PATH . '/logs/' . $dir . '/';

        switch ($level) {
            case self::LOGGER_LEVEL_TRACE:
                $my_level = 'TRACE';
                break;
            case self::LOGGER_LEVEL_DEBUG:
                $my_level = 'DEBUG';
                break;
            case self::LOGGER_LEVEL_WARNING:
                $my_level = 'WARNING';
                break;
            case self::LOGGER_LEVEL_ERROR:
                $my_level = 'ERROR';
                break;
            case self::LOGGER_LEVEL_FATAL:
                $my_level = 'FATAL';
                break;
            default:
                $my_level = 'N/A';
        }

        if ($conf['level'] <= $level) {
            if ($traceinfo['class_name']) {
                $out = sprintf ("[%s::%s] [%s] %s" . self::$crlf, $traceinfo['class_name'], $traceinfo['function'], $my_level, $message);
                $out_file = $traceinfo['class_name'];
            } elseif ($traceinfo['function']) {
                $out = sprintf("[%s] %s" . self::$crlf, $traceinfo['function'], $message);
                $out_file = $traceinfo['function'];
            } else {
                $out = sprintf("[main] %s" . self::$crlf, $message);
                $out_file = 'main';
            }

            if ($conf['output'] == 'stdout') {
                echo $out;
            } elseif ($conf['output'] == 'file') {
                $out_file .= '.' . date('Y-m-d') . '.log';
                self::write($out_file, $out, $conf['output_dir']);
            } else {
                error_log('Logger: unrecognized output type: ' . $conf['output']);
            }
        }

        return $out_file;
    }

    /**
     * monitor
     * 记录监控日志
     *
     * @param  string  $name    监控项名称
     * @param  string  $type    监控项类型，不同的类型写入不同的文件
     * @param  string  $message 监控信息
     * @param  integer $level   监控信息级别
     * @param  array   $meta    监控meta数据
     * @return void
     */
    public static function monitor($name, $type, $message, $level = self::LOGGER_LEVEL_WARNING, $meta = []) {

        $types = [self::MONITOR_TYPE_ERROR, self::MONITOR_TYPE_STATS];
        if (!in_array($type, $types)) {
            $type = self::MONITOR_TYPE_ERROR;
        }
        $datetime = date('Y-m-d H:i:s');

        $log_data = [
            'name' => $name,
            'hostname' => gethostname(),
            'level' => $level,
            'message' => $message,
            'meta' => $meta,
            'datetime' => $datetime,
        ];
        $message = json_encode($log_data, JSON_UNESCAPED_UNICODE);

        $dir = Config::get('logger.output_dir');
        $date = substr($datetime, 0, 10);
        $dir = rtrim($dir, '/') . "/monitor/$type.$date.log";

        return self::fs_write($dir, $message, false);
    }
}
