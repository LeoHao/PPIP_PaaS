<?php
/**
 * @Filename         : CpeAction.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-27 01:10
 * @Description      : device allow action function
 **/

class CpeAction {

	/**
	 * client init
	 * @param $data
	 * @param $db
	 */
	public static function clientInit($data)
	{
		$device_info = Devices::find_by_mac($data['CpeMac']);
		if (!empty($device_info)) {
			$update_data = array();
			$update_data['ip'] = $data['CpeIp'];
			$update_data['status'] = ($data['CpeStatus'] == 'online') ? 1 : 0;
			$update_data['connect_time'] = date("Y-m-d H:i:s");
			if (Devices::update_by_mac($update_data, $data['CpeMac'])) {
				Logger::trace("CPE update info data:" . json_encode($data), 'swoole');
			}
		}
	}

	public static function clientGetOwnPlugins()
	{

	}

	public static function clientGetOwnWebside()
	{

	}

	public static function clientGetOwnNode()
	{

	}

}
