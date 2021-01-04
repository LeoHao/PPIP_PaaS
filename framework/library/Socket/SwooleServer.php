<?php
/**
* @Filename         : SwooleServer.php
* @Author           : LeoHao
* @Email            : blueseamyheart@hotmail.com
* @Last modified    : 2020-12-29 16:39
* @Description      : 
**/

class SwooleServer {

    const DLOG_NOTICE = 2;
    const DLOG_ERROR = 8;

    /**
     * _server_type
     * 服务器类型
     *
     * @var string
     */
    var $_server_type = null;

    /**
     * _callback_class
     * 接收数据包后的回调类名称
     *
     * @var string
     */
    var $_callback_class = null;

    /**
     * _host
     * 监听的主机IP
     *
     * @var string
     */
    var $_host = 0;  // 0表示默认监听本机端口

    /**
     * _port
     * 端口号
     *
     * @var integer
     */
    var $_port = null;

    /**
     * _socket
     * 连接句柄
     *
     * @var source
     */
    var $_socket = null;

    /**
     * _is_return_package
     * 是否回包
     *
     * @var mixed
     */
    var $_is_return_package = true;

    /**
     * _allowProtocol
     * server的协议类型tcp
     *
     * @var array
     */
    private static $__allow_protocol = array('TCP');

    /**
     * __construct
     *
     * @param  array $options
     * @access protected
     * @return void
     */
    function __construct($options = array()) {

        // 配置数组校验
        if (!$options || !is_array($options)) {
            $msg = "config must be an array like:\r\narray(\r\n\t'host' => '127.0.0.1',\r\n\t'port' => 12000,\r\n\t'callback_class' => 'Cloud_Server',\r\n);";

            throw new Exception($msg);
        }

        error_reporting(0);
        set_time_limit(0);
        ob_implicit_flush();

        // 是否有回包,默认有回包
        if (array_key_exists('is_return_package', $options)) {
            $this->_is_return_package = $options['is_return_package'];
        }

        // 协议类型(tcp)
        $options['protocol'] = strtoupper($options['protocol']);
        if (!in_array($options['protocol'], self::$__allow_protocol)) {
            throw new Exception('"protocol" must be TCP or UDP');
        }
        $this->_server_type = $options['protocol'];

        // 监听的IP
        if ($options['host']) {
            if (!Validator::is_ip($options['host'])) {
                throw new Exception('"host" must be a valid IP like: 127.0.0.1');
            }
            $this->_host = $options['host'];
        }

        // 监听的端口号
        if (!Validator::is_in_range(intval($options['port']), 0, 65535)) {
            throw new Exception('"port" must between 1 and 65535');
        }
        $this->_port = $options['port'];

        // 回调的类名称
        if (!class_exists($options['callback_class'])) {
            throw new Exception('"callbackClass" ' . $options['callback_class'] . ' is not a valid class');
        }
        $this->_callback_class = new $options['callback_class'];

        if (!method_exists($this->_callback_class, 'callback')) {
            throw new Exception('"callbackClass" ' . $options['callback_class'] . ' must has "callback" method');
        }

        return true;
    }

    /**
     * run
     * 启动server进程
     *
     * @return boolean
     */
    public function run() {

        $this->_log_message('Starting server');
        $this->__create_socket();

        return true;
    }

    /**
     * _log_message
     * 记录日志
     *
     * @param string 消息
     * @param integer 级别
     * @return void
     */
    protected function _log_message($msg, $level = self::DLOG_NOTICE) {
        error_log($msg);
    }


    /**
     * _create_socket
     * 创建socket连接句柄
     *
     * @return resource
     */
    private function __create_socket() {

        switch ($this->_server_type) {
        case 'TCP':
            $res = $this->__create_tcp_socket();
            break;
        }

        return $res;
    }

    /**
     * _create_tcp_socket
     * 创建TCP协议的连接句柄
     *
     * @return resource
     */
    private function __create_tcp_socket() {

        $server = new Swoole\Server($this->_host, $this->_port);

        socket_listen($socket);

        $this->_socket = $socket;

        return $socket;
    }

    /**
     * _listen_socket
     * 监听端口并接收数据
     *
     * @return boolean
     */
    private function __listen_socket() {

        switch ($this->_server_type) {
        case 'UDP':
            $this->__listen_udp_socket();
            break;
        case 'TCP':
            $this->__listen_tcp_socket();
            break;
        }

        return true;
    }

    /**
     * _listen_tcp_socket
     * 监听TCP协议连接
     *
     * @return void
     */
    private function __listen_tcp_socket() {

        // 添加监听socket
        $clients = array($this->_socket);

        while (true) {

            $read = $clients;
            if (socket_select($read, $write = NULL, $except = NULL, 0) < 1) {
                continue;
            }

            if (in_array($this->_socket, $read)) {
                $clients[] = $new_socket = socket_accept($this->_socket);
                $key = array_search($this->_socket, $read);

                // 删除监听socket
                unset($read[$key]);
            }

            foreach ($read as $read_socket) {

                $data = socket_read($read_socket, 4096);

                if (!$data) {
                    $key = array_search($read_socket, $clients);

                    unset($clients[$key]);
                    continue;
                }

                $data = trim($data);

                // 接收到数据, 处理业务逻辑
                $res = $this->_callback_class->callback($data);

                // 是否回写数据
                if ($this->_is_return_package) {
                    socket_write($read_socket, $res);
                }
            }
        }

        socket_close($this->_socket);
    }
}
