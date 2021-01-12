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
                'Action'=>'plugins_network_special_add',
                'ClientType'=>'SaaS',
                'ClientIP'=>'192.168.3.87',
                'CpeIP' => '192.168.3.113',
                'CpeMac' => '00:F1:F3:18:86:42',
                'SecretKey' => crc32("plugins_network_special_add" . "this is saas sncode"),
                'ActionExt' => "{'node_id':1}"
            );
            $this->client->send(json_encode($data));
        });
    }
    
}
$send = new ServerSend();
$send->reconnect();
?>