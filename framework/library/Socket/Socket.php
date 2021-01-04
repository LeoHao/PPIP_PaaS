<?php
/**
* @Filename         : Socket.php
* @Author           : LeoHao
* @Email            : blueseamyheart@hotmail.com
* @Last modified    : 2020-12-29 16:36
* @Description      : socket client
**/

class Socket {

    const TAIL_LEN = 1;

    const TIMEOUT = 1;

    const READ_BUFF = 4096;

    /**
     * $_instance
     */
    protected static $_instance;

    /**
     * get_instance
     *
     * @return self
     */
    public static function get_instance() {

        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * TCP
     *
     * @param  string $host
     * @param  integer $port
     * @param  mixed $package
     * @param  boolean $is_recv
     * @return void
     */
    public function TCP($host, $port, $package, $is_recv = true, $timeout = 0.3) {

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (!$socket) {
            $message = sprintf('Unable to create socket. Host: %s, Port: %s', $host, $port);
            throw new Socket_Exception($message, 621);
        }

        if ($timeout < 1) {
            $u_timeout = $timeout * 1000000;
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 0, 'usec' => $u_timeout));
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => $u_timeout));
        } else {
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeout, 'usec' => 0));
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
        }

        // connect
        if (socket_connect($socket, $host, $port) === false) {

            $error_code = socket_last_error($socket);
            $error_message = socket_strerror($error_code);

            socket_close($socket);

            $message = sprintf('Create socket error: %s, Host: %s, Port: %s', $error_message, $host, $port);

            throw new Socket_Exception($message, $error_code);
        }

        // send
        if (socket_write($socket, $package) === false) {

            $error_code = socket_last_error($socket);
            $error_message = socket_strerror($error_code);

            socket_close($socket);

            $message = sprintf('Write socket error: %s, Host: %s, Port: %s', $error_message, $host, $port);

            throw new Socket_Exception($message, $error_code);
        }

        // revice
        $rsp_buffer = '';
        if ($is_recv) {
            $rsp_buffer = socket_read($socket, self::READ_BUFF);

            // 回包为空
            if (!$rsp_buffer) {
                $error_code = socket_last_error($socket);
                $error_message = socket_strerror($error_code);

                socket_close($socket);

                $message = sprintf('Read socket error: %s, Host: %s, Port: %s', $error_message, $host, $port);
                throw new Socket_Exception($message, $error_code);
            }
        }

        socket_close($socket);

        return $rsp_buffer;
    }

    /**
     * request
     *
     * @param  string $protocol
     * @param  string $host
     * @param  integer $port
     * @param  mixed $package
     * @param  boolean $is_recv
     * @return void
     */
    public function request($protocol, $host, $port, $package, $is_recv = true, $timeout = 0.3) {

        $protocol = strtolower($protocol);

        if ($protocol == 'tcp') {
            return $this->TCP($host, $port, $package, $is_recv, $timeout);
        } 
    }

    /**
     * request_servers
     * 带权重重试的请求
     *
     * @param  mixed $protocol 协议类型
     * @param  mixed $servers 服务器IP/端口/权重二维数组
     *  array(
     *        array('host' => '', 'port' => '', 'weight' => 1),
     *        array('host' => '', 'port' => '', 'weight' => 2),
     *        array('host' => '', 'port' => '', 'weight' => 2),
     *       )
     *  weight需要为正整数，建议在 1 ~ 10之间，没有weight默认为1。weight为负数，该server临时不可用
     *
     * @param  mixed $package socket内容
     * @param  mixed $is_recv 是否接收回包
     * @param  integer $retries 重试次数
     * @param  integer $timeout 超时时间默认1秒
     * @return void
     */
    public function request_servers($protocol, $servers, $package, $is_recv = true, $retries = 2, $timeout = 0.3, $persistent = false) {

        if (!$servers) {
            throw new Socket_Exception('Socket server error.', 643);
        }

        for ($retry = 1; $retry <= $retries; $retry ++) {

            $exception = false;

            // 每次重试都需要重新组合weightServers数组
            $server = $this->_get_server($servers);
            if (!$server) {
                break;
            }

            try {

                if ($persistent) {
                    $res = $this->persistent_request($protocol, $server['host'], $server['port'], $package, $is_recv, $timeout);
                } else {
                    $res = $this->request($protocol, $server['host'], $server['port'], $package, $is_recv, $timeout);
                }

                // 请求没有异常，返回请求结果
                return $res;

            } catch (Socket_Exception $exception) {

                // 出现异常，将对应的servers权重值降低到负数
                $servers[$server['config_key']]['weight'] = -1;

                // 出现异常时，减慢请求速度
                sleep($retry);
            }

        }

        if ($exception instanceof Exception) {
            throw new Socket_Exception($exception);
        } else {
            throw new Socket_Exception('Socket request error', 651);
        }

    }

    /**
     * _get_server
     * 从配置文件servers数组中随机获取一个
     *
     * @param  array $servers
     * @return array
     */
    protected function _get_server($servers) {

        $weight_servers = $this->_build_weight_servers($servers);
        if (!$weight_servers) {
            return false;
        }

        $indexes = count($weight_servers);
        $index = mt_rand(0, $indexes - 1);
        $server = $weight_servers[$index];

        return $server;
    }

    /**
     * _build_weight_servers
     * 根据servers参数重组数组，权重值在数组中体现
     *
     * @param  mixed $servers
     * @return void
     */
    protected function _build_weight_servers($servers) {

        $weight_servers = array();
        foreach ($servers as $config_key => $server) {

            $weight = intval($server['weight']);

            if ($weight < 0) {
                // 权重降到负数，认为该server不可用
                continue;
            }

            // weight 取值在1~10之间
            $weight = MIN(MAX($weight, 1), 10);

            // 将原始servers中的KEY记录下来，以便排除时追溯
            $server['config_key'] = $config_key;

            for ($i = 0; $i < $weight; $i ++) {
                $weight_servers[] = $server;
            }

        }

        if (!$weight_servers) {
            // 所有server权重都降低到负数时，取第一个
            $server = reset($servers);
            $server['config_key'] = 0;
            $weight_servers = array($server);
        }

        return $weight_servers;
    }

    /**
     * persistent_request
     * 持久连接的请求
     *
     * @param  mixed $host
     * @param  mixed $port
     * @param  mixed $package
     * @param  mixed $is_recv
     * @param  integer $timeout
     * @return void
     */
    public function persistent_request($protocol, $host, $port, $package, $is_recv = true, $timeout = 0.3) {

        $protocol = strtolower($protocol);

        $host_name = $protocol . '://' . $host;
        $fp = pfsockopen($host_name, $port, $error_code, $error_message, $timeout);

        if (!$fp || $error_code || $error_message) {
            $message = sprintf('Unable to create persistent socket. [%s] %s. Host: %s, Port: %s', $error_code, $error_message, $host, $port);
            throw new Socket_Exception($message, 721);
        }

        if ($timeout < 1) {
            $u_timeout = $timeout * 1000000;
            stream_set_timeout($fp, 0, $u_timeout);
        } else {
            stream_set_timeout($fp, $timeout);
        }

        if (fwrite($fp, $package) === false) {

            fclose($fp);

            $message = sprintf('Write persistent socket error. Host: %s, Port: %s', $host, $port);

            throw new Socket_Exception($message, 8011);
        }

        $rsp_buffer = '';

        if ($is_recv) {

            // 长连接不能使用 feof 判断
            $rsp_buffer = fread($fp, self::READ_BUFF);

            // 回包为空
            if (!$rsp_buffer) {

                fclose($fp);

                $message = sprintf('Read persistent socket error. Host: %s, Port: %s', $host, $port);
                throw new Socket_Exception($message, 8012);
            }
        }

        return $rsp_buffer;
    }

}
