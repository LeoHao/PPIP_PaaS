<?php
/**
 * @Filename         : SwooleServer.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2020-12-31 12:21
 * @Description      : this is base on Swoole tcp server
**/

class SwooleServer {

    /**
     * @var $server
     */
    public $server ;

    /**
     * @var $config array
     */
    public $config ;

    /**
     * @var $_worker
     */
    public static $_worker ;

    /**
     * pid path
     */
    public $pidFile ;

    /**
     * @var $worker_num
     */
    public $worker_num;

    /**
     * @var $worker_id
     */
    public $worker_id ;

    /**
     * @var $task_num
     */
    public $task_num ;

    /**
     * Server constructor.
     */
    public function __construct()
    {
        $this->getConfig();
    }

    /**
     * get config
     */
    public function getConfig() {

    }

    public function serverConfig()
    {
        $this->server->set($this->config['server']);
    }

    public function connect()
    {
        $this->server = new swoole_server($this->config ['host'] , $this->config ['port']);
        $this->serverConfig();
        $this->createTable();
        self::$_worker = & $this;
        self::main();
    }

    public function start()
    {
        foreach (ServerConfig::$swoole_server_function_map as $swoole_func => $local_func) {
            $this->server->on($swoole_func,[$this , $local_func]);
        }
        $this->server->start();
    }

    /**
     * @warning 进程隔离
     * 该步骤一般用于存储进程的 master_pid 和 manager_pid 到文件中
     * 本例子存储的位置是 __DIR__ . "/tmp/" 下面
     * 可以用 kill -15 master_pid 发送信号给进程关闭服务器，并且触发下面的onSwooleShutDown事件
     * @param $server
     */
    public function onSwooleStart($server)
    {
        $this->setProcessName('SwooleMaster');
        $debug = debug_backtrace();
        $this->pidFile =  __DIR__ . "/temp/" . str_replace("/" , "_" , $debug[count($debug) - 1] ["file"] . ".pid" );
        $pid = [$server->master_pid , $server->manager_pid];
        file_put_contents($this->pidFile , implode(",", $pid));
    }

    /**
     * @param $server
     * 已关闭所有Reactor线程、HeartbeatCheck线程、UdpRecv线程
     * 已关闭所有Worker进程、Task进程、User进程
     * 已close所有TCP/UDP/UnixSocket监听端口
     * 已关闭主Reactor
     * @warning
     * 强制kill进程不会回调onShutdown，如kill -9
     * 需要使用kill -15来发送SIGTREM信号到主进程才能按照正常的流程终止
     * 在命令行中使用Ctrl+C中断程序会立即停止，底层不会回调onShutdown
     */
    public function onSwooleShutDown($server)
    {
        echo "shutdown\n";
    }

    /**
     * @warning 进程隔离
     * 该函数具有进程隔离性 ,
     * {$this} 对象从 swoole_server->start() 开始前设置的属性全部继承
     * {$this} 对象在 onSwooleStart,onSwooleManagerStart中设置的对象属于不同的进程中.
     * 因此这里的pidFile虽然在onSwooleStart中设置了，但是是不同的进程，所以找不到该值.
     * @param \swoole_server $server
     * @param int            $worker_id
     */
    public function onSwooleWorkerStart(swoole_server $server, int $worker_id)
    {
        if($this->isTaskProcess($server))
        {
            $this->setProcessName('SwooleTask');
        }
        else{
            $this->setProcessName('SwooleWorker');
        }
        $debug = debug_backtrace();
        $this->pidFile = __DIR__ . "/temp/" . str_replace("/" , "_" , $debug[count($debug)-1] ["file"] . ".pid" );
        file_put_contents($this->pidFile , ",{$worker_id}" , FILE_APPEND);
    }

    public function onSwooleWorkerStop($server,$worker_id)
    {
        echo "#worker exited {$worker_id}\n";
    }

    /**
     * @warning 进程隔离 在task_worker进程内被调用
     * worker进程可以使用swoole_server_task函数向task_worker进程投递新的任务
     * $task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
     * 函数执行时遇到致命错误退出，或者被外部进程强制kill，当前的任务会被丢弃，但不会影响其他正在排队的Task
     * @param $server
     * @param $task_id 是任务ID 由swoole扩展内自动生成，用于区分不同的任务
     * @param $src_worker_id 来自于哪个worker进程
     * @param $data 是任务的内容
     * @return mixed $data
     */
    public function onSwooleTask($server , $task_id, $src_worker_id,$data)
    {
        return $data ;
    }

    public function onSwooleFinish()
    {

    }

    /**
     * 当工作进程收到由 sendMessage 发送的管道消息时会触发onPipeMessage事件。worker/task进程都可能会触发onPipeMessage事件。
     * @param $server
     * @param $src_worker_id 消息来自哪个Worker进程
     * @param $message 消息内容，可以是任意PHP类型
     */
    public function onSwoolePipeMessage($server , $src_worker_id,$message)
    {

    }

    /**
     * worker进程发送错误的错误处理回调 .
     * 记录日志等操作
     * 此函数主要用于报警和监控，一旦发现Worker进程异常退出，那么很有可能是遇到了致命错误或者进程CoreDump。通过记录日志或者发送报警的信息来提示开发者进行相应的处理。
     * @param $server
     * @param $worker_id 是异常进程的编号
     * @param $worker_pid  是异常进程的ID
     * @param $exit_code  退出的状态码，范围是 1 ～255
     * @param $signal 进程退出的信号
     */
    public function onSwooleWorkerError($server ,$worker_id,$worker_pid,$exit_code,$signal)
    {
        echo "#workerError:{$worker_id}\n";
    }

    /**
     *
     */
    public function onSwooleManagerStart()
    {
        $this->setProcessName('SwooleManager');
    }

    /**
     * @param $server
     */
    public function onSwooleManagerStop($server)
    {
        echo "#managerstop\n";
    }

    /**
     * 客户端连接
     * onConnect/onClose这2个回调发生在worker进程内，而不是主进程。
     * UDP协议下只有onReceive事件，没有onConnect/onClose事件
     * @param $server
     * @param $fd
     * @param $reactorId
     */
    public function onSwooleConnect($server ,$fd ,$reactorId)
    {
        $fd_info = $server->getClientInfo($fd);
        $client_ip = $fd_info['remote_ip'];
        $exist = $this->table->exist($fd);
        if (!$exist) {
            $redis_data = ['fd' => $fd, 'ip' => $client_ip];
            $this->table->set($client_ip, $redis_data);
            //Logger::trace("CPE connect fd:" . $fd . " | status:waiting | reactorid:" . $reactorId . " | request_ip:" . $data['ip'] . " | mac_address:" . $data['mac_address'], 'swoole');
            Logger::trace("newconnect fd:" . $fd . " | status:online | reactorid:" . $reactorId, 'swoole');
        }
        echo "#connected\n";
    }

    /**
     * @param $server server对象
     * @param $fd 文件描述符
     * @param $reactorId reactor线程id
     * @param $data 收到的数据内容，可能是文本或者二进制内容
     */
    public function onSwooleReceive($server,$fd,$reactorId,$data)
    {
        echo "#received\n";
    }

    /**
     * 连接断开，广播业务需要从redis | memcached | 内存 中删除该fd
     * @param $server
     * @param $fd
     * @param $reactorId
     */
    public function onSwooleClose($server, $fd ,$reactorId)
    {
        //$this->table->del($fd);
    }

    public function setProcessName($name)
    {
        if(function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($name);
        }
        else{
            @swoole_set_process_name($name);
        }
    }

    /**
     * 返回真说明该进程是task进程
     * @param $server
     * @return bool
     */
    public function isTaskProcess($server)
    {
        return $server->taskworker === true ;
    }

    /**
     * main 运行入口方法
     */
    public static function main()
    {
        self::$_worker->start();
    }

    /**
     * 创建swoole_table
     */
    public function createTable()
    {
        $this->table = new swoole_table(ServerConfig::$swoole_server_table['table_size']);
        foreach (ServerConfig::$swoole_server_table['table_column'] as $param_name => $param_value) {
            $this->table->column($param_name, $param_value['type'], $param_value['size']);
        }
        $this->table->create();
    }

    public function addTableColumn($fd, $data)
    {
        $this->table->set($fd, $data);
    }
}
