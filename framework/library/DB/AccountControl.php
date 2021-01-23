<?php
/**
 * @Filename         : AccountControl.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-24 1:23
 * @Description      : account for control
 **/

class AccountControl extends Table {

    /**
     * 要操作的表名
     *
     * @var string
     */
    static $table_name = 'paas.control_account';

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
        'control_ip' => array(
            'type' => 'varchar'
        ),
        'account_type' => array(
            'type' => 'varchar'
        ),
        'account_name' => array(
            'type' => 'varchar'
        ),
        'account_pwd' => array(
            'type' => 'varchar'
        ),
        'company_id' => array(
            'type' => 'int'
        ),
        'cpe_id' => array(
            'type' => 'int'
        ),
        'create_time' => array(
            'type' => 'timestamp'
        )
    );
    
    public static function addAccount($data)
    {
        $table_info = Table::load('ppip', self::$table_name, self::$properties);
        $table_info->insert($data);
    }
}