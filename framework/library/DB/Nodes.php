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
    static $primary_key = 'ip';

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
        'ip' => array(
            'type' => 'varchar'
        ),
        'country' => array(
            'type' => 'varchar'
        ),
        'city' => array(
            'type' => 'varchar'
        ),
        'bw' => array(
            'type' => 'int'
        )
    );

    /**
     * find_all_by_id
     * @param $id
     * @return mixed
     */
    public static function find_by_id($id) {

        $conditions = array(
            'id = ?',
            $id
        );

        $table_info = Table::load('ppip', self::$table_name, self::$properties);
        $node_info = $table_info->find_all($conditions);
        return $node_info[0];
    }

    /**
     * update_by_ip
     * @param $data
     * @param $ip
     * @return bool
     */
    public static function update_by_ip($data, $ip) {

        $conditions = array(
            'ip = ?',
            $ip
        );

        $table_info = Table::load('ppip', self::$table_name, self::$properties);
        return $table_info->update($data, $conditions);
    }
}