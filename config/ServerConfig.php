<?php
/**
 * @Filename         : ServerConfig.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2020-12-31 12:21
 * @Description      : this is base on Swoole tcp server
 **/

class ServerConfig {

    const SERVER_AUTH_NAME = 'SaaS';

    const SAAS_SERVER_IP = '192.168.3.87';

    const SERVER_SWOOLE_PORT = '6001';

    public static $swoole_server_table = array(
        'table_size' => 65536,
        'table_column' => array(
            'fd' => array(
                'type' => swoole_table::TYPE_INT,
                'size' => 8
            ),
            'ip' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 15
            ),
            'name' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 255
            ),
            'action' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 50
            ),
            'auth_name' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 4
            ),
            'mac_address' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 40
            ),
            'exec_result' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 10
            ),
        )
    );

    public static $swoole_server_tcp = array(
        'worker_num' => 10,
        'task_worker_num' => 5,
        'dispatch_mode' => 2,
        'heartbeat_check_interval' => 10,
        'heartbeat_idle_time'      => 20,
        'max_connection' => 500,
        'log_file' => ROOT_PATH . '/logs/swoole/swoole.log'
    );

    public static $swoole_server_function_map = array(
        "start" => "onSwooleStart", //Server启动在主进程的主线程回调此函数
        "shutDown" => "onSwooleShutDown", //在Server正常结束时发生
        "workerStart" => "onSwooleWorkerStart", //在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用。
        "workerStop" => "onSwooleWorkerStop", //在worker进程终止时发生。在此函数中可以回收worker进程申请的各类资源。
        "task" => "onSwooleTask", //worker向task_worker进程投递任务触发
        "finish" => "onSwooleFinish", //task_worker 返回值传给worker进程时触发
        "pipeMessage" => "onSwoolePipeMessage", //当工作进程收到由 sendMessage 发送的管道消息时会触发onPipeMessage事件
        "workerError" => "onSwooleWorkerError", //当worker/task_worker进程发生异常后会在Manager进程内回调此函数
        "managerStart" => "onSwooleManagerStart", //当管理进程启动时调用它，函数原型：
        "managerStop" => "onSwooleManagerStop", //onManagerStop
        "connect" => "onSwooleConnect", //有新的连接进入时，在worker进程中回调。
        "receive" => "onSwooleReceive", //接收到数据时回调此函数，发生在worker进程中
        "close" => "onSwooleClose" //CP客户端连接关闭后，在worker进程中回调此函数
    );
}
?>