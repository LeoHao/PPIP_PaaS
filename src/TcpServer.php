<?php
/**
 * @Filename         : TcpServer.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2020-12-31 12:21
 * @Description      : this is base on Swoole tcp server
 **/


require_once(dirname(dirname(__FILE__)) . "/bin/Skel.php");
class TcpServer extends SwooleServer{

    /**
     * 获取配置文件
     *
     * @return array $config
     */
    public function getConfig() {
        $this->config = array();
        $this->config['server'] = array();
        $this->config['host'] = $this->getLocalIp();
        $this->config['port'] = ServerConfig::SERVER_SWOOLE_PORT;
        foreach (ServerConfig::$swoole_server_tcp as $name => $value) {
            $this->config['server'][$name] = $value;
        }
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
		if(Validator::checkRequestData($data)) {
			if($this->validateSecretKey($data)) {
				if ($data['ClientType'] == ServerConfig::CLIENT_FOR_SAAS) {
					$send_data = $this->disposeSaasRequestData($data);
					$fd_info = $this->table->get($send_data['SendIp']);
					$this->serverSendData($server, $fd_info['fd'], $send_data);
					Logger::trace("SaaS connect fd:" . $fd . " | status:online | reactorid:" . $reactorId . " | request_ip:" . $data['ClientIP'] . " | response_ip:" . $data['CpeIP'] . " | action:" . $data['Action'], 'swoole');
				}

				if ($data['ClientType'] == ServerConfig::CLIENT_FOR_CPE) {
					$send_data = $this->disposeCpeRequestData($data, $fd);
					if (!empty($send_data)) {
						$fd_info = $this->table->get($send_data['SendIp']);
						$this->serverSendData($server, $fd_info['fd'], $send_data);
						Logger::trace("CPE connect fd:" . $fd . " | status:exec_done | reactorid:" . $reactorId . " | request_ip:" . $data['ip'] . " exec_result:" . $data['exec_result'], 'swoole');
					}
				}
			}
		}
	}

	/**
	 * dispose saas request data
	 * @param $data
	 * @return array $send_data
	 */
	public function disposeSaasRequestData($data)
    {
        $send_data = array();
        /*
        if ($this->checkRequestType($data['Action'])) {
             * $cpe_exec_info = $redis->get($data['CpeIp']);
             * $cpe_exec_result = $cpe_exec_info['ExecStatus'];
             * $result = array();
             * $result['ExecStatus'] = $cpe_exec_result;
             * $result['send_ip'] = $data['ClientIp'];
            $send_data['ExecStatus'] = 'Success';
            $send_data['SendIp'] = $data['ClientIp'];
        }
        */
		$function_name = $this->getActionFunctionName($data['Action']);
		$cpe_info = Devices::find_by_mac($data['CpeMac']);
		if (!$cpe_info['status']) {
			$send_data = ServerAction::$function_name($data, $cpe_info);
		} else {
			Logger::trace("CPE status offline for mac:" .$data['CpeMac'] , 'swoole');
		}
        return $send_data;
    }

    /**
     * dispose cpe request data
     * @param $data
	 * @param $fd
	 * @return array send_data
     */
    public function disposeCpeRequestData($data, $fd)
    {
        $send_data = array();
		$this->client_mac_map[$data['CpeMac']] = $fd;
		$this->client_fd_map[$fd] = $data['CpeMac'];
		$this->client_mac_data_map[$data['CpeMac']]['fd'] = $fd;
		$function_name = $this->getActionFunctionName($data['Action']);
		$data = CpeAction::$function_name($data);
		if (!empty($data)) {
			$send_data['SendIp'] = $data['ClientIP'];
			$send_data['ResultData'] = $data;
		}
        return $send_data;
    }

    /**
     * validate secret key
     * @param $data
     * @return bool
     */
	public function validateSecretKey($data) {
		if ($data['ClientType'] == ServerConfig::CLIENT_FOR_SAAS) {
			$validate_str = $data['Action'] . ServerConfig::SNCODE_FOR_SAAS;
			$validate_key = crc32($validate_str);
			if ($validate_key != $data['SecretKey']) {
				Logger::error("SaaS invalid Secret key | data:" . json_encode($data), 'swoole');
				return false;
			}
		} elseif ($data['ClientType'] == ServerConfig::CLIENT_FOR_CPE) {
			$cpe_info = Devices::find_by_mac($data['CpeMac']);
			$cpe_sncode = $cpe_info['sncode'];
			$validate_str = $data['Action'] . $cpe_sncode;
			$validate_key = crc32($validate_str);
			if ($validate_key != $data['SecretKey']) {
				Logger::error("CPE invalid Secret key | server_cpe_sncode: " . $cpe_sncode. " | data:" . json_encode($data), 'swoole');
				return false;
			}
		}
		return true;
	}

    /**
     * check request type
     * @param $action_name
     * @return bool
     */
    public function checkRequestType($action_name)
    {
       $action_params = explode("_", $action_name);
       if (in_array("response", $action_params)) {
           return true;
       }
       return false;
    }

    /**
     * get action function name
     * @param $action_name
     * @return string function_name
     */
    public function getActionFunctionName($action_name)
    {
        $action_array = explode("_", $action_name);
        $first_word = array_shift($action_array);
        $function_name = array();
        array_push($function_name, $first_word);
        foreach ($action_array as $word){
            array_push($function_name, ucfirst(strtolower($word)));
        }
        $function_name = implode("", $function_name);

        return $function_name;
    }

}
$swoole_tcp_server = new TcpServer();
$swoole_tcp_server->connect();