<?php
/**
 * @Filename         : Devices.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-13 10:11
 * @Description      : devices db dispose
 **/

class Devices extends Table {

    /**
     * 要操作的表名
     *
     * @var string
     */
    static $table_name = 'cpe.devices';

    /**
     * 主键
     *
     * @var string
     */
    static $primary_key = 'sncode';

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
    public function find_by_mac($mac_address) {

        $options = array(
            'mac = ?',
            $mac_address
        );

        $devices = $this->find_all($options);
        return $devices[0];
    }
}