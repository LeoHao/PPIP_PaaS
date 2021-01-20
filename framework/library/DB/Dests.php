<?php
/**
 * @Filename         : Dests.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-19 16:31
 * @Description      : devices db dispose
 **/

class Dests extends Table {

    /**
     * 要操作的表名
     *
     * @var string
     */
    static $table_name = 'paas.dests';

    /**
     * 主键
     *
     * @var string
     */
    static $primary_key = 'id';

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
        'node_id' => array(
            'type' => 'varchar'
        ),
        'dest_country' => array(
            'type' => 'varchar'
        ),
        'dest_city' => array(
            'type' => 'varchar'
        )
    );

    /**
     * find_all_by_node_id
     * @param $id
     * @return mixed
     */
    public static function find_by_id($id) {

        $conditions = array(
            'id = ?',
            $id
        );

        $table_info = Table::load('ppip', self::$table_name, self::$properties);
        $devices = $table_info->find_all($conditions);
        return $devices[0];
    }

    /**
     * update_by_id
     * @param $data
     * @param $id
     * @return bool
     */
    public static function update_by_id($data, $id) {

        $conditions = array(
            'id = ?',
            $id
        );

        $table_info = Table::load('ppip', self::$table_name, self::$properties);
        return $table_info->update($data, $conditions);
    }
}