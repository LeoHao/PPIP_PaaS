<?php
/**
* @Filename         : SocketServer.php
* @Author           : LeoHao
* @Email            : blueseamyheart@hotmail.com
* @Last modified    : 2020-12-29 16:39
* @Description      : 
**/

class SocketServer {

    const DLOG_TO_CONSOLE = 1;
    const DLOG_NOTICE = 2;
    const DLOG_WARNING = 4;
    const DLOG_ERROR = 8;
    const DLOG_CRITICAL = 16;

    const DAPC_PATH =  '/tmp/server_apc_keys';

    /**
     * User ID
     *
     * @var int
     */
    var $user_id = 65534; // nobody

    /**
     * Group ID
     *
     * @var integer
     */
    var $group_id = 65533; // nobody

    /**
     * Terminate daemon when set identity failure ?
     *
     * @var bool
     */
    var $require_set_identity = false;

    /**
     * Path to PID file
     *
     * @var string
     */
    var $pid_file_location = '/tmp/server.pid';

    /**
     * Home path
     *
     * @var string
     */
    var $home_path = '/';

    /**
     * Current process ID
     *
     * @var int
     * @since 1.0
     */
    var $_pid = 0;

    /**
     * Is this process a children
     *
     * @var boolean
     */
    var $_is_children = false;

    /**
     * _server_number
     * 启动监听进程个数
     *
     * @var integer
     */
    var $_server_number = 1;

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
     * _ignoreUserAbort
     * 是否忽略用户取消
     *
     * @var mixed
     */
    var $_ignore_user_abort = false;

    /**
     * _is_return_package
     * 是否回包
     *
     * @var mixed
     */
    var $_is_return_package = true;

    /**
     * _allowProtocol
     * server的协议类型udp/tcp
     *
     * @var array
     */
    private static $__allow_protocol = array('UDP', 'TCP');

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
            $msg = "config must be an array like:\r\narray(\r\n\t'number' => 1,\r\n\t'protocol' => 'UDP',\r\n\t'host' => '127.0.0.1',\r\n\t'port' => 12000,\r\n\t'callback_class' => 'Cloud_Server',\r\n);";

            throw new Exception($msg);
        }

        // 是否忽略用户中止,默认不忽略
        if ($options['ignore_user_abort']) {
            ignore_user_abort(true);
        }

        error_reporting(0);
        set_time_limit(0);
        ob_implicit_flush();

        register_shutdown_function(array(&$this, 'release_daemon'));

        // 是否有回包,默认有回包
        if (array_key_exists('is_return_package', $options)) {
            $this->_is_return_package = $options['is_return_package'];
        }

        // 启动监听子进程数目(1~1024)
        $options['number'] = intval($options['number']);
        if ($options['number'] < 1 || $options['number'] > 1024) {
            throw new Exception('"number" must between 1 and 1024');
        }
        $this->_server_number = $options['number'];

        // 协议类型(udp/tcp)
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

        if (!$this->__daemonize()) {
            $this->_log_message('Could not start server', self::DLOG_ERROR);

            return false;
        }

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
     * Daemonize
     *
     * Several rules or characteristics that most daemons possess:
     * 1) Check is daemon already running
     * 2) Fork child process
     * 3) Sets identity
     * 4) Make current process a session laeder
     * 5) Write process ID to file
     * 6) Change home path
     * 7) umask(0)
     *
     * @access private
     * @since 1.0
     * @return boolean
     */
    private function __daemonize() {

        ob_end_flush();

        @chdir($this->home_path);
        umask(0);

        declare(ticks = 1);

        pcntl_signal(SIGSTKFLT, array(&$this, 'sig_handler'));
        pcntl_signal(SIGVTALRM, array(&$this, 'sig_handler'));
        pcntl_signal(SIGPROF, array(&$this, 'sig_handler'));
        pcntl_signal(SIGPOLL, array(&$this, 'sig_handler'));
        pcntl_signal(SIGPIPE, array(&$this, 'sig_handler'));
        pcntl_signal(SIGQUIT, array(&$this, 'sig_handler'));
        pcntl_signal(SIGINT, array(&$this, 'sig_handler'));
        pcntl_signal(SIGHUP, array(&$this, 'sig_handler'));

        pcntl_signal(SIGCHLD, array(&$this, 'sig_handler'));
        pcntl_signal(SIGTERM, array(&$this, 'sig_handler'));
        pcntl_signal(SIGUSR1, array(&$this, 'sig_handler'));
        pcntl_signal(SIGUSR2, array(&$this, 'sig_handler'));

        // 创建socket连接句柄
        if (!$this->__create_socket()) {
            return false;
        }

        // fork监听子进程
        if (!$this->__fork()) {
            return false;
        }

        return true;
    }

    /**
     * _fork
     * fork监听子进程
     *
     * @return void
     */
    private function __fork() {

        $this->_log_message('Forking...');

        // 初始化子进程的个数
        for ($i = 0; $i < $this->_server_number; $i ++) {
            $pid = pcntl_fork();
            if ($pid == 0) {
                break;
            }
        }

        if ($pid == -1) { // error
            $this->_log_message('Could not fork', self::DLOG_ERROR);
            return false;

        } elseif ($pid) { // parent

            // 如果子进程异常终止,则重新启动一个
            while(($pid = pcntl_waitpid(-1, $status)) > 0) {

                $pid = pcntl_fork();

                if ($pid == 0) {
                    if (!posix_setsid()) {
                        exit;
                    }

                    // listen socket
                    $this->__listen_socket();
                    exit;
                }
            }

        } else { // children

            $this->_is_children = true;
            $this->_pid = posix_getpid();

            // listen socket
            $this->__listen_socket();
            return true;
        }
    }

    /**
     * _create_socket
     * 创建socket连接句柄
     *
     * @return resource
     */
    private function __create_socket() {

        switch ($this->_server_type) {
            case 'UDP':
                $res = $this->__create_udp_socket();
                break;
            case 'TCP':
                $res = $this->__create_tcp_socket();
                break;
        }

        return $res;
    }

    /**
     * _create_udp_socket
     * 创建UDP协议的连接句柄
     *
     * @return resource
     */
    private function __create_udp_socket() {

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socket, $this->_host, $this->_port);

        $this->_socket = $socket;

        return $socket;
    }

    /**
     * _create_tcp_socket
     * 创建TCP协议的连接句柄
     *
     * @return resource
     */
    private function __create_tcp_socket() {

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socket, $this->_host, $this->_port);
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
     * _listen_udp_socket
     * 监听UDP协议连接
     *
     * @return void
     */
    private function __listen_udp_socket() {

        while (true) {
            socket_recvfrom($this->_socket, $data, 1024, 0, $name, $port);

            if (!$data) {
                continue;
            }
            $data = trim($data);

            $res = $this->_callback_class->callback($data);

            // 是否回包
            if ($this->_is_return_package) {
                socket_sendto($this->_socket, $res, 1024, 0, $name, $port);
            }
        }

        socket_close($this->_socket);
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

    /**
     * Signals handler
     *
     * @access public
     * @since 1.0
     * @return void
     */
    public function sig_handler($sig_no) {

        $this->_log_message('Receive Signal: ' . $sig_no);

        switch ($sig_no) {
            case SIGTERM:   // Shutdown
                $this->_log_message('Shutdown signal');
                exit();
                break;
            case SIGCHLD:   // Halt
                $this->_log_message('Halt signal');
                while (pcntl_waitpid(-1, $status, WNOHANG) > 0);
                break;
            case SIGUSR1:   // User-defined
                $this->_log_message('User-defined signal 1');
                $this->_sig_handler_user1();
                break;
            case SIGUSR2:   // User-defined
                $this->_log_message('User-defined signal 2');
                $this->_sig_handler_user2();
                break;
        }
    }

    /**
     * Signals handler: USR1
     *  主要用于定时清理每个进程里被缓存的域名dns解析记录
     *
     * @return void
     */
    protected function _sig_handler_user1() {
        apc_clear_cache('user');
    }

    /**
     * Signals handler: USR2
     * 主要用于临时、手动清理某些域名的dns解析记录
     *
     * @return void
     */
    protected function _sig_handler_user2() {

        $handle = fopen(self::DAPC_PATH, 'r');
        if (!$handle) {
            return false;
        }

        $size = filesize(self::DAPC_PATH);
        $content = fread($handle, $size);
        fclose($handle);

        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $domain = trim($line);
            if (!$domain) {
                continue;
            }
            apc_store('host_' . $domain, null);
        }

        return true;
    }

}
