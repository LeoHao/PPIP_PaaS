<?php
/**
 * @Filename         : Router.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-01-20 15:08
 * @Description      : this is base on sock tcp telnet
 **/

use PEAR2\Net\RouterOS;
class Router {

	/**
	 * connect router os
	 * @param $server
	 * @param $username
	 * @param $password
	 * @return RouterOS\Client $client
	 */
	public static function connect($server, $username, $password) {
		try {
			$client = new RouterOS\Client($server, $username, $password);
		} catch (Exception $e) {
			Logger::error('Unable to connect to the router. | ip:' . $server . '|username:' . $username . '|password:' . $password . '|exception' . json_encode($e) . '|', 'router');
		}

		return $client;
	}

	/**
	 * get new router remote address
	 * @param $client
	 * @return array
	 */
	public static function getNewRemoteAddress($client) {
		$remote_address = $remote_part = array();
		$responses = $client->sendSync(new RouterOS\Request('/ppp/secret/print'));
		foreach ($responses as $response) {
			if ($response->getType() === RouterOS\Response::TYPE_DATA) {
				$remote_address[] = $response->getProperty('remote-address');
			}
		}
		if ($remote_address = array_filter($remote_address)) {
			foreach ($remote_address as $ip_address) {
				if (strstr($ip_address, ServerConfig::ROUTER_REMOTE_ADDRESS_PART)) {
					$ip_address_part = explode(".", $ip_address);
					$ip_address_part_third = $ip_address_part[count($ip_address_part)-2];
					$ip_address_part_last = $ip_address_part[count($ip_address_part)-1];
					$remote_part[$ip_address_part_third][] = $ip_address_part_last;
				}
			}
		}

		krsort($remote_part);
		$ip_address_third_keys = array_keys($remote_part);
		$current_max_third_part = array_shift($ip_address_third_keys);
		$remote_part = $remote_part[$current_max_third_part];
		sort($remote_part);
		$current_sub_ip = (count($remote_part) < 2) ? $remote_part[0] : array_pop($remote_part);
		$new_sub_ip = $current_sub_ip + 1;

		if ($new_sub_ip > ServerConfig::ROUTER_REMOTE_ADDRESS_PART_END) {
			$new_ip_address_third_part = $current_max_third_part + 1;
			$new_remote_address = ServerConfig::ROUTER_REMOTE_ADDRESS_PART . '.' . $new_ip_address_third_part . '.' . ServerConfig::ROUTER_REMOTE_ADDRESS_PART_START;
			$new_remote_address_gateway = ServerConfig::ROUTER_REMOTE_ADDRESS_PART . '.' . $new_ip_address_third_part . '.1';
			self::setPoolsRule($client, $new_ip_address_third_part);
		} else {
			$new_remote_address = ServerConfig::ROUTER_REMOTE_ADDRESS_PART . '.' . $current_max_third_part . '.' . $new_sub_ip;
			$new_remote_address_gateway = ServerConfig::ROUTER_REMOTE_ADDRESS_PART . '.' . $current_max_third_part . '.1';
		}
		return array('ip' => $new_remote_address, 'gateway' => $new_remote_address_gateway);
	}

	/**
	 * create router account
	 * @param $data
	 * @return array
	 */
    public static function createRouterAccount($data) {
        $router_account = $db_account = array();
        $client = self::connect($data['ConnectNode'], $data['ConnectAccount']['username'], $data['ConnectAccount']['password']);
        $account_name = $data['ConnectCompanyName'] . '-' . $data['ConnectNodeCity'] . '-' . crc32($data['ConnectCpeMac']);
        $account_password = crc32($data['ConnectCpeMac']);
        $router_account['username'] = $account_name;
        $router_account['password'] = $account_password;
        $router_account['server'] = $data['ConnectNode'];

        if (!self::checkRouterAccount($client, $account_name)) {
            $new_remote_address = self::getNewRemoteAddress($client);
            $remote_address  = $new_remote_address['ip'];
            $remote_gateway = $new_remote_address['gateway'];
            $account_type = $data['ConnectType'];
            $account_profile = self::setProfilesRule($client, $data['ConnectDW'], $new_remote_address);
            $account_comment = $data['ConnectCpeMac'];

            $addRequest = new RouterOS\Request('/ppp/secret/add');

            $addRequest->setArgument('name', $account_name);
            $addRequest->setArgument('password', $account_password);
            $addRequest->setArgument('local-address', $remote_gateway);
            $addRequest->setArgument('remote-address', $remote_address);
            $addRequest->setArgument('profile', $account_profile);
            $addRequest->setArgument('comment', $account_comment);
            if ($client->sendSync($addRequest)->getType() !== RouterOS\Response::TYPE_FINAL) {
                return array();
            } else {
                $db_account['control_ip'] = $data['ConnectNode'];
                $db_account['account_type'] = $account_type;
                $db_account['account_name'] = $account_name;
                $db_account['account_pwd'] = $account_password;
                $db_account['company_id'] = $data['ConnectCompanyID'];
                $db_account['cpe_id'] = $data['ConnectCpeID'];
                AccountControl::addAccount($db_account);
            }
        }

        return $router_account;
    }

	/**
	 * set profile rule
	 * @param $client
	 * @param $dw
	 * @param $remote_address
	 * @return array
	 */
	public static function setProfilesRule($client, $dw, $remote_address)
	{
		$rxtx = intval($dw) . 'M';
		$responses = $client->sendSync(new RouterOS\Request('/ip/pool/print'));
		if ($responses) {
			foreach ($responses as $response) {
				if ($response->getType() === RouterOS\Response::TYPE_DATA) {
					$pool_ranges = $response->getProperty('ranges');
					$pool_ranges = explode("-", $pool_ranges);
					$pool_shard_ip = array_shift($pool_ranges);
					$ip_compare_part = substr_compare($pool_shard_ip, $remote_address['ip'], 0, 10);
					if ($ip_compare_part === 0) {
						$pool_name = $response->getProperty('name');
					}
				}
			}
		}

		if (!self::checkProfileRule($client, $rxtx, $pool_name)) {
			$addRequest = new RouterOS\Request('/ppp/profile/add');
			$addRequest->setArgument('name', $rxtx);
			$addRequest->setArgument('local-address', $remote_address['gateway']);
			$addRequest->setArgument('remote-address', $pool_name);
			$addRequest->setArgument('rate-limit', $rxtx . '/' . $rxtx);
			$addRequest->setArgument('only-one', 'yes');
			if ($client->sendSync($addRequest)->getType() !== RouterOS\Response::TYPE_FINAL) {
				return $rxtx;
			}
		}
		return $rxtx;
	}

	/**
	 * check profile rule
	 * @param $client
	 * @param $dw
	 * @param $pool_name
	 * @return bool
	 */
    public static function checkProfileRule($client, $dw, $pool_name) 
    {
        $profiles = array();
		$responses = $client->sendSync(new RouterOS\Request('/ppp/profile/print'));
		foreach ($responses as $response) {
			if ($response->getType() === RouterOS\Response::TYPE_DATA) {
				$profile_name = $response->getProperty('name');
				$remote_address = $response->getProperty('remote-address');
                $profiles[$remote_address][] = $profile_name;
			}
		}
        $exist_profile = array_search($dw, $profiles[$pool_name]);
        if ($exist_profile) {
            return true;
        }
        return false;
    }

	/**
	 * set pool rule
	 * @param $client
	 * @param $third_part
	 * @return bool
	 */
	public static function setPoolsRule($client, $third_part)
	{
		$router_pools = $new_ranges_arr = array();
		$responses = $client->sendSync(new RouterOS\Request('/ip/pool/print'));
		if ($responses) {
			foreach ($responses as $response) {
				if ($response->getType() === RouterOS\Response::TYPE_DATA) {
					$pool_name = $response->getProperty('name');
					$pool_name_num = explode(ServerConfig::ROUTER_PREFIX_NAME, $pool_name);
					array_push($router_pools, array_pop($pool_name_num));
				}
			}
		}

		sort($router_pools);
		$new_pool_num = array_pop($router_pools);
		$new_pool_num++;
		array_push($new_ranges_arr, ServerConfig::ROUTER_REMOTE_ADDRESS_PART . '.' . $third_part . '.' . ServerConfig::ROUTER_REMOTE_ADDRESS_PART_START);
		array_push($new_ranges_arr, ServerConfig::ROUTER_REMOTE_ADDRESS_PART . '.' . $third_part . '.' . ServerConfig::ROUTER_REMOTE_ADDRESS_PART_END);

		$addRequest = new RouterOS\Request('/ip/pool/add');
		$addRequest->setArgument('name', ServerConfig::ROUTER_PREFIX_NAME . $new_pool_num);
		$addRequest->setArgument('ranges', implode("-", $new_ranges_arr));
		if ($client->sendSync($addRequest)->getType() !== RouterOS\Response::TYPE_FINAL) {
			return false;
		}
		return true;
	}

	/**
	 * check router account
	 * @param $client
	 * @param $account_name
	 * @return bool
	 */
    public static function checkRouterAccount($client, $account_name) 
    {
		$account_names = array();
		$responses = $client->sendSync(new RouterOS\Request('/ppp/secret/print'));
		foreach ($responses as $response) {
			if ($response->getType() === RouterOS\Response::TYPE_DATA) {
				$account_names[] = $response->getProperty('name');
			}
		}
        $exist_name = array_search($account_name, $account_names);
        if ($exist_name) {
            return true;
        }
        return false;
    }
}
