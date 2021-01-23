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
     * get router remote address
     * @param $client
     * @return string
     */
    public static function getRemoteAddress($client) {
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
                    $temp_arr = explode(".", $ip_address);
                    $remote_part[] = array_pop($temp_arr);
                } 
            }
        }
        sort($remote_part);
        $current_sub_ip = array_pop($remote_part);
        $new_sub_ip = $current_sub_ip + 1;
        /**
        if ($new_sub_ip < 255) {
            $current_part = explode(".", ServerConfig::ROUTER_REMOTE_ADDRESS_PART);
            $third_part = array_pop($current_part);
            $third_part = $third_part + 1;
            array_push($current_part , $third_part);
            $new_remote_address_part = implode(".", $current_part);
            $new_remote_address = $new_remote_address_part . '.' . $new_sub_ip;
            var_dump($new_remote_address);die;
        }**/
        $remote_ip = ServerConfig::ROUTER_REMOTE_ADDRESS_PART . '.' . $new_sub_ip;
        return $remote_ip;
    }

    /**
     * create router account 
     * @param $data
     * @return array
     */
    public static function createRouterAccount($data) {
        $client = self::connect($data['ConnectNode'], $data['ConnectAccount']['username'], $data['ConnectAccount']['password']);
        $account_name = $data['ConnectCompanyName'] . '-' . $data['ConnectNodeCity'] . '-' . crc32($data['ConnectCpeMac']);
        $account_password = crc32($data['ConnectCpeMac']);
        $remote_gateway = ServerConfig::ROUTER_REMOTE_ADDRESS_PART . '.1';
        $remote_address = self::getRemoteAddress($client);
        $account_type = $data['ConnectType'];
        $account_profile = intval($data['ConnectDW']) . 'M';
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
        }
        $router_account = $db_account = array(); 
        $router_account['username'] = $account_name;
        $router_account['password'] = $account_password;
        $router_account['server'] = $data['ConnectNode'];
        
        $db_account['control_ip'] = $data['ConnectNode'];
        $db_account['account_type'] = $account_type;
        $db_account['account_name'] = $account_name;
        $db_account['account_pwd'] = $account_password;
        $db_account['company_id'] = $data['ConnectCompanyID'];
        $db_account['cpe_id'] = $data['ConnectCpeID'];
        AccountControl::addAccount($db_account);die;
        return $router_account;
    }
}