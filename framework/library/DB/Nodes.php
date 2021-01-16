<?php
/**
 * @Filename         : Nodes.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-13 10:11
 * @Description      : devices db dispose
 **/

class Nodes extends Table {

    /**
     * 要操作的表名
     *
     * @var string
     */
    static $table_name = 'paas.nodes';

    /**
     * 主键
     *
     * @var string
     */
    static $primary_key = 'node_id';

    /**
     * properties
     * 设置对象具有字段
     *
     * @var array
     * @access public
     */
    static $properties = array(
        'id' => array(
            'type' => 'int'
        ),
        'name' => array(
            'type' => 'varchar'
        ),
        'mac' => array(
            'type' => 'varchar'
        ),
        'sncode' => array(
            'type' => 'varchar'
        ),
        'ip' => array(
            'type' => 'varchar'
        ),
        'status' => array(
            'type' => 'int'
        )
    );

    /**
     * find_all_by_mac
     * @param $mac_address
     * @return mixed
     */
    public static function find_by_mac($mac_address) {

        $conditions = array(
            'mac = ?',
            $mac_address
        );

        $table_info = Table::load('ppip', self::$table_name, self::$properties);
        $devices = $table_info->find_all($conditions);
        return $devices[0];
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