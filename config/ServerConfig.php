<?php
/**
 * @Filename         : ServerConfig.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2020-12-31 12:21
 * @Description      : this is base on Swoole tcp server
 **/

class ServerConfig {

    const CLIENT_FOR_PAAS = 'PaaS';

    const CLIENT_FOR_SAAS = 'SaaS';

    const CLIENT_FOR_CPE = 'Cpe';

    const SNCODE_FOR_SAAS = 'this is saas sncode';

    const SAAS_SERVER_IP = '192.168.3.87';

    const SERVER_SWOOLE_PORT = '7250';

    const SERVER_TELNET_PORT = '23';

    const CONTROL_SERVER_PORT = '3388';

    const SPECIAL_CONNETCT_L2TP = 'L2TP';

    const SPECIAL_CONNETCT_GRE = 'GRE';

    const ROUTER_REMOTE_ADDRESS_PART = '10.10';

	const ROUTER_REMOTE_ADDRESS_PART_START = '10';

	const ROUTER_REMOTE_ADDRESS_PART_END = '254';

	const ROUTER_PREFIX_NAME = 'pool';


	public static $swoole_server_table = array(
        'table_size' => 65536,
        'table_column' => array(
            'fd' => array(
                'type' => swoole_table::TYPE_INT,
                'size' => 8
            ),
            'ClientType' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 10
            ),
            'Action' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 100
            ),
            'SecretKey' => array(
                'type' => swoole_table::TYPE_INT,
                'size' => 255
            ),
            'Sncode' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 100
            ),
            'CpeIP' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 15
            ),
            'ClientMac' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 20
            ),
            'CpeMac' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 20
            ),
            'CpeName' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 255
            ),
            'CpeStatus' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 255
            ),
            'ExecStatus' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 255
            ),
            'ExecExt' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 255
            ),
            'ActionExt' => array(
                'type' => swoole_table::TYPE_STRING,
                'size' => 255
            )
        )
    );

    public static $swoole_server_tcp = array(
        'worker_num' => 10,
        //'daemonize' => false,
        'task_worker_num' => 5,
        'dispatch_mode' => 2,
        'heartbeat_check_interval' => 360,
        'heartbeat_idle_time'      => 600,
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

    public static $server_own_action = array(
        'plugins_network_special_open',
        'plugins_network_special_change',
        'plugins_network_webside_open',
        'plugins_network_webside_change',
        'plugins_network_group_open',
        'plugins_network_group_change'
    );

    public static $cpe_own_action  = array(
        'client_init',
        'client_get_own_plugins',
        'client_get_own_webside',
        'client_get_own_node'
    );

    public static $os_own_action  = array(
        'cpe_opkg_update_theme',
        'cpe_opkg_update_plugins'
    );

    public static $control_server_auth_0  = array(
        'username' => 'haojianping',
        'password' => 'haojianping@778899'
    );

    public static $control_server_auth_1  = array(
        'username' => 'haojianping',
        'password' => 'Ha@jian!@#ping@!!!'
    );

    public static $request_data = array(
		self::CLIENT_FOR_SAAS => array(
			'plugins_network_special_open' => array('Action', 'ClientType', 'CpeMac', 'SecretKey', 'ActionExt' => array('node_id', 'dest_id', 'bw'))
		),
		self::CLIENT_FOR_CPE => array(
			'client_init' => array('ClientType', 'Action', 'Sncode', 'SecretKey', 'CpeName', 'CpeIp', 'CpeMac', 'CpeStatus'),
			'plugins_network_special_open' => array(
				'ClientType', 'Action', 'Sncode', 'SecretKey', 'CpeName', 'CpeIp', 'CpeMac', 'CpeStatus', 'ExecStatus')
		)
	);
}