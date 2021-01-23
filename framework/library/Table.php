<?php
/**
 * @Filename         : Table.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-13 10:11
 * @Description      : db model
 **/

class Table {

    /**
     * PostgreSQL 数据库
     */
    const TABLE_POSTGRESQL = 'pgsql';

    static $tables;
    /**
     * $primary_key
     * 主键名称, 必填
     *
     * @var string
     */
    static $primary_key = '';

    /**
     * $table_name
     * 数据表名称, 必填
     *
     * @var string
     */
    static $table_name = '';

    /**
     * db_name
     * 数据库名
     *
     * @var sting
     */
    static $db_name = 'ppip';

    /**
     * $properties
     * 数据表对应的字段配置信息
     *
     * array(
     *     'name' => array(
     *         'type' => 'varchar',
     *     ),
     * )
     *
     * 支持 int, varchar, array, datetime, bool
     *
     * @var array
     */
    static $properties = array();

    /**
     * $count_properties
     * 需要进行count运算而得到的属性
     *     class_name 表示需要进行count运算的model, 一般是其他model
     *     pk_condition 表示需要当前model的主键属性作为哪个条件
     *     const_conditions 表示需要常量作为条件的集合(每一项的值都是固定数字或者字符串, 暂时不支持数组)
     * @var array
     */
    static $count_properties = array();

    /**
     * $escape_char
     *
     * @var string
     */
    public $ec = '`';

    /**
     * $methods
     * 配置methods方法
     *
     * @var array
     */
    static $methods = array();

    /**
     * $cache
     * 配置查询缓存
     *
     * @var array
     */
    static $cache = array();

    /**
     * $__new_record
     *
     * @var boolean
     */
    private $__new_record = true;

    /**
     * __construct
     *
     * @param array $data Model对象数据
     * @param boolean $new_record 是否为未添加的新记录
     */
    public function __construct($db_name, $table_name, $pk, $data = array(), $new_record = true) {

        $this->table_name = self::$table_name = $table_name;
        $this->pk = $pk;
        $this->db = DB_Manager::connection($db_name);

        $this->__new_record = $new_record;

        $data = static::construct_data($data);
        foreach ($data as $field_name => $field_value) {
            $this->$field_name = $field_value;
        }
    }


    /**
     * 动态处理数据，不用初始化类
     * @param  array $data
     * @return void
     */
    public static function construct_data($data = array()) {

        $construct_data = array();
        foreach ($data as $field_name => $field_value) {
            // 支持从GROUP BY中查询出没有预定义的字段
            $property = static::$properties[$field_name];
            if (!$property) {
                $construct_data[$field_name] = $field_value;
                continue;
            }

            $field_type = $property['type'];
            switch ($field_type) {
                case 'bool':
                    $construct_data[$field_name] = (bool)$field_value;
                    break;
                case 'int':
                    $construct_data[$field_name] = (int)$field_value;
                    break;
                case 'nulldouble':
                    if ($field_value === NULL) {
                        $construct_data[$field_name] = NULL;
                    } else {
                        $construct_data[$field_name] = (double)$field_value;
                    }
                    break;
                case 'array':
                    if ($field_value && is_string($field_value)) {
                        $construct_data[$field_name] = json_decode($field_value, true);
                    } elseif ($field_value) {
                        $construct_data[$field_name] = $field_value;
                    } else {
                        $construct_data[$field_name] = array();
                    }
                    break;
                case 'intarray':
                    $construct_data[$field_name] = self::_to_int_array($field_value);
                    break;
                default:
                    $construct_data[$field_name] = $field_value;
                    break;
            }
        }

        return $construct_data;
    }

    /**
     * to int array
     * @param  string $int_array
     * @return array
     */
    protected static function _to_int_array($int_array = '') {

        if (!$int_array) {
            return array();
        }

        if (is_array($int_array)) {
            return $int_array;
        }

        $result = array();
        if ($int_array) {
            $int_array = substr($int_array, 1, -1);
            $result = explode(',', $int_array);
            if ($result) {
                foreach ($result as $k => $v) {
                    $result[$k] = intval($v);
                }
            }
        }

        return $result;
    }

    /**
     * get_db_name
     *
     * @return string
     */
    public static function get_db_name() {

        return static::$db_name;
    }

    /**
     * get_table_tag
     * 获取每张表唯一标识
     *
     * @return string
     */
    public static function get_table_tag($db_name, $table_name) {

        return "$db_name.$table_name";
    }

    /**
     * table
     * 获取/创建 Table 对象
     * @return DB
     * @throws Exception
     */
    public static function table() {

        $db_name = static::get_db_name();
        $db_config = Config::get('GLOBAL.DB.' . $db_name);
        if (!$db_config) {
            throw new Exception("Could not find database $db_name config");
        }

        switch ($db_config['protocol']) {
            case self::TABLE_POSTGRESQL:
                return $db = DB_Manager::connection($db_name);
            default:
                throw new Exception("Undefined database protocol ${db_config['protocol']}");
                break;
        }
    }

    /**
     * load
     * 获取 Table 对象
     *
     * @param  string $table_name 数据表名称
     * @param  string $pk 数据表主键
     * @return Table
     */
    public static function load($db_name, $table_name, $data) {

        // Table 缓存池标识需要合并分库的信息，保证同一个表不同分库不会连接到错误的库
        $table_tag = self::get_table_tag($db_name, $table_name);
        if (!self::$tables[$table_tag]) {
            $db_config = Config::get('GLOBAL.DB.' . $db_name);
            if (!$db_config) {
                throw new Exception("Could not find database $db_name config");
            }

            switch ($db_config['protocol']) {
                case self::TABLE_POSTGRESQL:
                    return $table = new Table($db_name, $table_name, $data);
                default:
                    throw new Exception("Undefined database protocol ${db_config['protocol']}");
                    break;
            }

            static::$tables[$table_tag] = $table;
        }

        return static::$tables[$table_tag];
    }
    /**
     * insert
     * 插入一条记录 或更新表记录
     * @param $data
     * @param array $shard_data
     * @return mixed
     * @throws Exception
     */
    public function insert($data) {

        $table_name = static::$table_name;
        $params = array();

        $keys = array_keys($data);
        $placeholder = '(' . join(', ', array_fill(0, count($keys), '?')) . ')';
        foreach ($data as $value) {
            $params[] = $value;
        }

        $sql = "INSERT INTO $table_name (" . join(", ", $keys) . ") VALUES $placeholder";

        try {
            return $this->db->exec($sql, $params);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * update
     * 更新一条记录
     * @param $data
     * @param $conditions
     * @return int
     * @throws Exception
     */
    public function update($data, $conditions) {

        $table_name = $this->table_name;

        $update_params = array();
        if (is_array($data)) {
            $update_placeholder = array();
            foreach ($data as $key => $value) {
                if (is_array($value) && $value['sql']) {
                    array_push($update_placeholder, "{$this->ec}{$key}{$this->ec} = " . $value['sql']);
                    if ($value['params']) {
                        $update_params = array_merge($update_params, $value['params']);
                    }
                } else {
                    array_push($update_placeholder, "{$key} = ?");
                    array_push($update_params, $value);
                }
            }
            $update_placeholder_string = join(',', $update_placeholder);
        } else {
            $update_placeholder_string = $data;
        }

        if ($conditions) {
            // 支持 IN (?) 查询写法
            if (strpos($conditions[0], '(?)') !== false) {
                $conditions = $this->rebuild_in_conditions($conditions);
            }

            $condition = array_shift($conditions);
            $params = $conditions;
        }

        $sql = "UPDATE $table_name SET " . $update_placeholder_string . " WHERE $condition";
        $params = array_merge($update_params, $params);

        try {
            return $this->db->exec($sql, $params);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * count
     * 计算行数
     * @param array $conditions
     * @throws Exception
     */
    public function count($conditions = array()) {

        $table_name = static::$table_name;
        $params = array();

        $sql = '';
        $sql .= "SELECT COUNT(*) FROM $table_name";

        if ($conditions) {
            // 支持 IN (?) 查询写法
            if (strpos($conditions[0], '(?)') !== false) {
                $conditions = $this->rebuild_in_conditions($conditions);
            }

            $sql .= ' WHERE ' . array_shift($conditions);
            $params = $conditions;
        }

        try {
            return $this->db->fetch_one($sql, $params);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * rebuild in conditions
     * @param $conditions
     * @return array $rebuilded_conditions
     */
    public function rebuild_in_conditions($conditions) {

        $rebuilded_conditions = array('');
        $condition = array_shift($conditions);

        $setup = 0;
        foreach ($conditions as $key => $value) {
            $offset = strpos($condition, '?', $setup);

            if ($offset !== false) {
                if ($value && is_array($value)) {
                    $tmp = trim(str_repeat('?,', count($value)), ',');
                    $rebuilded_conditions[0] .= substr($condition, $setup, $offset - $setup) . $tmp;

                    foreach ($value as $v) {
                        $rebuilded_conditions[] = $v;
                    }
                } else {
                    $rebuilded_conditions[0] .= substr($condition, $setup, $offset - $setup + 1);
                    if (is_array($value) && !$value) {
                        $value = null;
                    }
                    $rebuilded_conditions[] = $value;
                }

                $setup = $offset + 1;
            }
        }

        $rebuilded_conditions[0] .= substr($condition, $setup);
        return $rebuilded_conditions;
    }

    /**
     * find_all
     * 取得多条记录
     * @param array $conditions
     * @param string $select
     * @param int $limit
     * @param string $order
     * @param string $join
     * @param string $group_by
     * @param false $unlimit
     * @param array $withs
     * @return array
     * @throws Exception
     */
    public function find_all($conditions = array(), $select = '', $limit = 0, $order = '', $join = '', $group_by = '', $unlimit = false, $withs = array()) {

        $table_name = $this->table_name;
        $params = array();

        if (!$select) {
            $select = '*';
        }

        $sql = '';
        if ($withs) {
            $sql .= 'with ';
            list($table_ori_name, $table_alias_name) = explode(' as ', strtolower($table_name));
            foreach($withs['with_sqls'] as $with) {
                $with_sql = $with['sql'];
                $with_alias = $with['name'];
                $sql .= "$with_alias as ($with_sql),";
            }
            if ($table_alias_name) {
                $table_name = $withs['main_table'].' '.$table_alias_name;
            } else {
                $table_name = $withs['main_table'].' '.$table_ori_name;
            }

            $sql = rtrim($sql, ',');
        }


        $sql .= "SELECT $select FROM $table_name";
        if ($join) {
            $sql .= ' ' . $join;
        }

        if ($conditions) {
            // 支持 IN (?) 查询写法
            if (strpos($conditions[0], '(?)') !== false) {
                $conditions = $this->rebuild_in_conditions($conditions);
            }
            $sql .= ' WHERE ' . array_shift($conditions);
            $params = $conditions;
        }

        if ($group_by) {
            $sql .= " GROUP BY $group_by";
        }

        if ($order && is_array($order)) {
            $order_clause = '';
            foreach ($order as $field => $order_by) {
                $order_clause .= "{$this->ec}{$field}{$this->ec} $order_by,";
            }
            $sql .= ' ORDER BY ' . rtrim($order_clause, ',');
        } elseif ($order && is_string($order)) {
            $sql .= ' ORDER BY ' . $order;
        }

        if (!$limit) {
            $limit = 100;
        } elseif (!$unlimit && $limit > 100) {
            $limit = 100;
        }

        $sql .= $this->build_limit($limit);

        try {
            $rows = $this->db->fetch_all($sql, $params);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        return $rows;
    }

    /**
     * build_limit
     *
     * @param  integer $limit
     * @param  integer $start
     * @return string
     */
    public function build_limit($limit, $start = 0) {
            return " LIMIT $limit";
    }

    /**
     * build_in_condition
     *
     * @param  string $field
     * @param  array $params
     * @return string
     */
    public function build_in_condition($field, $params) {

        $sql = '';

        if ($params && is_array($params)) {
            $sql = "{$this->ec}{$field}{$this->ec} IN (";
            $sql .= join(',', array_pad(array(), count($params), '?'));
            $sql .= ')';
        }

        return $sql;
    }

    /**
     * exec
     * 执行sql语句
     * @param $sql
     * @param array $params
     * @return int
     * @throws Exception
     */
    public function exec($sql, $params = array()) {

        // 支持 IN (?) 查询写法
        if (strpos($sql, '(?)') !== false) {
            array_unshift($params, $sql);
            $conditions = $this->rebuild_in_conditions($params);

            $sql = array_shift($conditions);
            $params = $conditions;
        }

        try {
            $result = $this->db->exec($sql, $params);
        } catch (PDOException $e) {
            throw new Exception($e);
        }

        return $result;
    }
}