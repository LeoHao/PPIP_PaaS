<?php
/**
 * @Filename         : Action.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-13 10:11
 * @Description      : action db dispose
 **/

class Action extends Table {

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
        ),
        'create_time' => array(
            'type' => 'timestamp'
        )
    );

    /**
     * find by device id
     * @param $device_id
     * @return mixed
     */
    public static function find_by_device_id($device_id) {

        $conditions = array(
            'device_id = ?',
            $device_id
        );

        $table_info = Table::load('ppip', self::$table_name, self::$properties);
        $action = $table_info->find_all($conditions);
        return $action;
    }

    /**
     * update_by_mac
     * @param $data
     * @param $mac_address
     * @return bool
     */
    public static function update_by_device_id($data, $device_id) {

        $conditions = array(
            'device_id = ?',
            $device_id
        );

        $table_info = Table::load('ppip', self::$table_name, self::$properties);
        return $table_info->update($data, $conditions);
    }
}