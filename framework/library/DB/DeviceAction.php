<?php
/**
 * @Filename         : DeviceAction.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-27 01:41
 * @Description      : device exist action
 **/

class DeviceAction extends Table {

	/**
	 * 要操作的表名
	 *
	 * @var string
	 */
	static $table_name = 'paas.devices_action';

	/**
	 * 主键
	 *
	 * @var string
	 */
	static $primary_key = 'device_id';

	/**
	 * properties
	 * 设置对象具有字段
	 *
	 * @var array
	 * @access public
	 */
	static $properties = array(
		'device_id' => array(
			'type' => 'int'
		),
		'action_name' => array(
			'type' => 'varchar'
		)
	);

	/**
	 * find_all_by_device_id
	 * @param $device_id
	 * @return array $devices
	 */
	public static function find_all_by_device_id($device_id) {

		$conditions = array(
			'device_id = ?',
			$device_id
		);

		$table_info = Table::load('ppip', self::$table_name, self::$properties);
		$devices = $table_info->find_all($conditions);
		return $devices;
	}

	/**
	 * update_by_mac
	 * @param $data
	 * @param $mac_address
	 * @return bool
	 */
	public static function update_by_mac($data, $mac_address) {

		$conditions = array(
			'mac = ?',
			$mac_address
		);

		$table_info = Table::load('ppip', self::$table_name, self::$properties);
		return $table_info->update($data, $conditions);
	}
}