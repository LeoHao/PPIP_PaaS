<?php
/**
 * @Filename         : Manager.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-13 02:21
 * @Description      : db manager
 **/

class DB_Manager {

    /**
     * $_instances
     * 数据库实例集合
     *
     * @var array
     */
    protected static $_instances = array();

    /**
     * group
     * @var array
     */
    private static $_shard_groups = array();

    /**
     * connection
     * 获取数据库连接对象
     *
     * @param  string $group 数据库连接分组名称
     * @return DB
     */
    public static function connection($group) {

        $config = Config::get('GLOBAL.DB.' . $group);
        if (!$config) {
            throw new DB_Exception('Can\'t get DB config for ' . $group);
        }

        return self::_connection($config);
    }

    /**
     * connection_by_config
     *
     * @param  array $config
     * @return DB
     */
    public static function connection_by_config($config) {

        return self::_connection($config);
    }

    /**
     * get_dsn
     * 根据config解析dsn
     *
     * @param  array $config 数据库连接配置
     * @return string
     */
    public static function get_dsn($config) {

        return $config['protocol'] . ':host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['db_name'];
    }

    /**
     * 内部连接
     * @param  string $config
     * @return DB
     */
    private static function _connection($config) {

        $dsn = static::get_dsn($config);
        if (!static::$_instances[$dsn]) {
            static::$_instances[$dsn] = new DB(
                $dsn,
                $config['user'],
                $config['password']
            );
        }

        return static::$_instances[$dsn];
    }

    /**
     * 关闭指定库表连接
     * @param $group
     */
    public static function close($group) {

        $config = Config::get('GLOBAL.DB.' . $group);
        $dsn = static::get_dsn($config);
        if (static::$_instances[$dsn]) {
            static::$_instances[$dsn]->close();
            static::$_instances[$dsn] = null;
        }
    }

    /**
     * close_connections
     *
     * @return void
     */
    public static function close_connections() {

        foreach (static::$_instances as $dsn => $instance) {
            $instance->close();
        }

        static::$_instances = array();
    }
}
