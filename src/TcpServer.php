<?php
/**
 * @Filename         : TcpServer.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2020-12-31 12:21
 * @Description      : this is base on Swoole tcp server
 **/

require_once(dirname(dirname(__FILE__)) . "/bin/Skel.php");
require_once(dirname(dirname(__FILE__)) . "/include/SwooleServer.php");

class TcpServer extends SwooleServer{

    /**
     * 获取配置文件
     * 
     * @return array $config
     */
    public function getConfig() {
        $this->server_config = new ServerConfig();
        $this->config = array();
        $this->config['server'] = array();
        foreach ($this->server_config->swoole_server_tcp as $name => $value) {
            $this->config['server'][$name] = $value;
        }
        $this->config['host'] = $this->getLocalIp();
        $this->config['port'] = $this->server_config->swoole_base['port'];
    }

    /**
     * 获取本机IP地址
     *
     * @return string $local_ip
     */
    public function getLocalIp() {
        $shell_exec = "ifconfig -a|grep inet|grep -v 127.0.0.1|grep -v inet6|awk '{print $2}'|tr -d 'addr:'";
        $local_ip = exec($shell_exec);
        if (!Validator::is_ip($local_ip)) {
            throw new Exception('"host" must be a valid IP like: 127.0.0.1');
        }

        return $local_ip;
    }

    /**
     * @param $server server对象
     * @param $fd 文件描述符
     * @param $reactorId reactor线程id
     * @param $data 收到的数据内容，可能是文本或者二进制内容
     */
    public function onSwooleReceive($server,$fd,$reactorId, $data)
    {
        $data = json_decode($data, true);
        if (isset($data['auth_name']) &&$data['auth_name'] == ServerConfig::SERVER_AUTH_NAME && $data['ip'] == ServerConfig::SAAS_SERVER_IP) {
            if ($data['key'] && $data['key'] == '1234567890') {
                $cpe_ip = $data['cpeip'];
                $cpe_info = $this->table->get($cpe_ip);
                $server->send($cpe_info['fd'] , json_encode(array('action' => $data['action'])));

                Logger::trace("SaaS connect fd:" . $fd . " | status:online | reactorid:" . $reactorId . " | request_ip:" . $data['ip'] . " | response_ip:" . $data['cpeip'] . " | action:" . $data['action'], 'swoole');
            }
        } elseif (isset($data['exec_result'])){
            echo "# " . $data['exec_result'] . "\n";
            Logger::trace("CPE connect fd:" . $fd . " | status:exec_done | reactorid:" . $reactorId . " | request_ip:" . $data['ip'] . " exec_result:" . $data['exec_result'], 'swoole');
        } else {
            $ip = $data['ip'];
            $exist = $this->table->exist($fd);
            if (!$exist) {
                $data = ['fd'=>$fd, 'ip'=>$ip];
                $this->table->set($ip, $data);
                Logger::trace("CPE connect fd:" . $fd . " | status:waiting | reactorid:" . $reactorId . " | request_ip:" . $data['ip'], 'swoole');
            }
        }
    }
    
    /**
     * 创建swoole_table
     */
    public function createTable()
    {
        $this->table = new swoole_table($this->server_config->swoole_server_table['table_size']);
        foreach ($this->server_config->swoole_server_table['table_column'] as $name => $value) {
            $type = $value['type'];
            $size = $value['size'];
            $this->table->column($name, $type, $size);
        }
        $this->table->create();
    }
}
$swoole_tcp_server = new TcpServer();
$swoole_tcp_server->connect();
?>
