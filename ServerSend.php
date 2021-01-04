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
        go(function() {
            $this->client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
            $this->client->connect('192.168.3.30', '6001', '0.5');
            $data = array(
                'action'=>'plugins_network_special_open',
                'auth_name'=>'SaaS',
                'ip'=>'192.168.3.87',
                'key' => '1234567890',
                'cpeip' => '192.168.3.113'
            );
            $this->client->send(json_encode($data));
        });
    }
    
}
$send = new ServerSend();
$send->reconnect();
?>