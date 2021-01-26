<?php
/**
 * @Filename         : ServerAction.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-12 01:21
 * @Description      : auth action function
 **/

class ServerAction {

	/**
	 * plugins network special open
	 * @param $data
	 * @param $cpe
	 * @return array
	 */
    public static function pluginsNetworkSpecialOpen($data, $cpe)
    {
    	$send_data = array();
		if (!self::checkDeviceExistAction($data['Action'], $cpe)) {
			$send_data['Action'] = $data['Action'];
			$send_data['ClientType'] = ServerConfig::CLIENT_FOR_PAAS;
			$send_data['SecretKey'] = crc32($data['Action'] . $cpe['sncode']);
			$send_data['SendIp'] = $cpe['ip'];
			$send_data['ActionExt'] = SwooleServer::createUserForControl($data, $cpe);
			if (!empty($send_data['ActionExt'])) {
				return $send_data;
			}
		}
		return array();
	}

	/**
	 * checkDeviceExistAction
	 * @param $action
	 * @param $cpe
	 * @return bool
	 */
	public static function checkDeviceExistAction($action, $cpe)
	{
		$exist_action = array();
		$actions = DeviceAction::find_all_by_device_id($cpe['id']);
		if ($actions) {
			foreach ($actions as $action_item) {
				$exist_action[] = $action_item['action_name'];
			}
		}

		if (in_array($action, $exist_action)) {
			return true;
		}
		return false;
	}

}
