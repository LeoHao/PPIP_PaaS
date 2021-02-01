<?php
/**
 * @Filename         : ServerSend.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2020-12-31 12:21
 * @Description      : this is base on Swoole tcp server
 **/

class ServerSend {

    /**
     * @param $server server对象
     * @param $fd 文件描述符
     * @param $reactorId reactor线程id
     * @param $data 收到的数据内容，可能是文本或者二进制内容
     */
    public function reconnect()
    {
    	//TODO 后期推送ip route 配置需要通过swoole一次对设备进行推送 暂定开启定时任务执行ipsend.php
		//获取ipsend.php需要后期在paas平台中编写定时在tmp目录中更新ip route地址
        $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        $client->connect('192.168.3.30', '7250', '0.5');
        $data = array(
            'Action'=>'plugins_network_webside_open',
            'ClientType'=>'SaaS',
            'CpeMac' => '00:F1:F3:18:86:42',
            'SecretKey' => crc32("plugins_network_webside_open" . "this is saas sncode"),
            'ActionExt' => array(
            	'node_id' => 1,
				'dest_id' => 7,
				'bw'      => 3,
				'domain'  => 'www.google.com'
			)
        );
        $client->send(json_encode($data));
    }
    
}
Swoole\Coroutine\run(function () {
    $send = new ServerSend();
    $send->reconnect();
});