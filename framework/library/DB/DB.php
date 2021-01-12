<?php
/**
 * @Filename         : DB.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-13 02:01
 * @Description      : db function
 **/

class DB {

    /**
     * _dbh
     * 当前实例的PDO对象
     *
     * @var object
     */
    protected $_dbh;

    /**
     * _dsn
     * 数据连接
     *
     * @var mixed
     */
    protected $_dsn;

    /**
     * $_user
     *
     * @var string
     */
    protected $_user;

    /**
     * $_password
     *
     * @var string
     */
    protected $_password;

    /**
     * $_charset
     *
     * @var string
     */
    protected $_charset;

    /**
     * $_failover
     *
     * @var string
     */
    protected $_failover;

    /**
     * $_persistent
     *
     * @var boolean
     */
    protected $_persistent;

    /**
     * $_timeout
     *
     * @var integer
     */
    protected $_timeout;

    /**
     * PHP本地模拟prepare参数
     * @var
     */
    protected $_emulate_prepares;

    /**
     * db 构造函数
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param string $charset
     * @param string $failover
     * @param boolean $persistent
     * @param integer $timeout
     * @param boolean $disable_transaction
     */
    public function __construct($dsn, $user, $password, $charset = 'utf8', $failover = '', $persistent = false, $timeout = 0) {

        $this->_dsn = $dsn;
        $this->_user = $user;
        $this->_password = $password;
        $this->_charset = $charset;
        $this->_failover = $failover;
        $this->_persistent = $persistent;
        $this->_timeout = $timeout;

        $this->_connect();
    }

    /**
     * _connect
     *
     * @return void
     */
    protected function _connect() {

        $dsn = $this->_dsn;
        $user = $this->_user;
        $password = $this->_password;
        $charset = $this->_charset;
        $timeout = $this->_timeout;
        $persistent = $this->_persistent;
        $failover = $this->_failover;

        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => $persistent
        );
        if ($timeout > 0) {
            $options[PDO::ATTR_TIMEOUT] = $timeout;
        }

        $time_start = microtime(true);
        try {
            $dbh = new PDO($dsn, $user, $password, $options);
            if ($charset) {
                $dbh->exec("SET NAMES '$charset'");
            }
        } catch (PDOException $e) {
            Event::emit('system.db.connect_error', array(
                'dsn' => $dsn,
                'user' => $user,
                'options' => $options,
                'timeout' => $timeout,
                'charset' => $charset,
                'persistent' => $persistent,
                'failover' => $failover,
                'exception' => $e
            ));

            if (!$failover) {
                throw new Exception('can\'t connect to the server because ' . $e->getMessage());
            }

            // 再次重连
            try {
                $dbh = new PDO($dsn, $user, $password, $options);
                if ($charset) {
                    $dbh->exec("SET NAMES '$charset'");
                }
            } catch (PDOException $e){
                throw new Exception('can\'t connect to the server because ' . $e->getMessage());
            }
        }
        $time_end = microtime(true);

        Event::emit('system.mysql.connect', array(
            'dsn' => $dsn,
            'time' => $time_end - $time_start,
        ));

        $this->_dbh = $dbh;
    }

    /**
     * fetch_row
     * 取得记录的第一行
     *
     * @param sql string $query
     * @param array $params
     */
    public function fetch_row($query, $params = array()) {

        return $this->query('fetch_row', $query, $params);
    }

    /**
     * fetch_all
     * 取得所有的记录
     *
     * @param sql string $query
     * @param array $params
     * @return array
     */
    public function fetch_all($query, $params = array()) {

        return $this->query('fetch_all', $query, $params);
    }

    /**
     * fetch_one
     * 获取记录的第一行第一列
     *
     * @param string sql $query
     * @param array $params
     */
    public function fetch_one($query, $params = array()) {

        return $this->query('fetch_one', $query, $params);
    }

    /**
     * exec
     * 执行sql 语句
     *
     * @param sqlstring $query
     * @param array $params
     * @return integer
     */
    public function exec($query, $params = array()) {

        return $this->query('exec', $query, $params);
    }

    /**
     * 开启nestloop
     * @return int
     */
    public function nestloop_on() {

        return $this->exec("set enable_nestloop to on");
    }

    /**
     * 关闭nestloop
     * @return int
     */
    public function nestloop_off() {

        return $this->exec("set enable_nestloop to off");
    }

    /**
     * 获取prepare配置
     * @return mixed
     */
    public function get_emulate_prepares() {

        return $this->_emulate_prepares;
    }

    /**
     * 启用PHP本地模拟prepare
     */
    public function set_emulate_prepares() {

        $this->_emulate_prepares = true;
    }

    /**
     * 禁止PHP本地模拟prepare
     */
    public function off_emulate_prepares() {

        $this->_emulate_prepares = false;
    }

    /**
     * query
     * 执行sql语句
     *
     * @param  mixed $type
     * @param  mixed $query
     * @param  array $params
     * @return void
     */
    public function query($type, $query, $params = array()) {

        if ($this->_need_reconnect()) {
            $this->_connect();
        }

        try {
            $start = microtime(true);
            if ($this->_emulate_prepares) {
                $stmt = $this->_dbh->prepare($query,array(
                    PDO::ATTR_EMULATE_PREPARES => true
                ));
            } else {
                $stmt = $this->_dbh->prepare($query);
            }

            $data = $stmt->execute($params);
        } catch (Exception $e) {
            Event::emit('system.db.query_error', array(
                'query' => $query,
                'params' => $params,
                'dsn' => $this->_dsn,
                'exception' => $e,
            ));
            throw $e;
        }

        switch ($type) {
            case 'fetch_row':
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'fetch_all':
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'fetch_one':
                if ($data) {
                    $data = $stmt->fetchColumn();
                }
                break;
            case 'exec':
                $data = $stmt->rowCount();
                break;
            default:
                break;
        }

        $end = microtime(true);
        Event::emit('system.db.query', array(
            'query' => $query,
            'params' => $params,
            'time' => $end - $start,
            'pdo' => $this->_dbh,
        ));

        return $data;
    }

    /**
     * run
     *
     * @param  string $sql
     * @return void
     */
    public function run($sql) {

        if ($this->_need_reconnect()) {
            $this->_connect();
        }

        try {
            return $this->_dbh->exec($sql);
        } catch (Exception $e) {
            Event::emit('system.db.query_error', array(
                'query' => $sql,
                'params' => [],
                'exception' => $e,
            ));
            throw $e;
        }
    }

    /**
     * last_insert_id
     * 获取最后一条记录的id
     *
     * @return string
     */
    public function last_insert_id() {

        return intval($this->_dbh->lastInsertId());
    }

    /**
     * close
     * 关闭数据库连接
     *
     * @return void
     */
    public function close() {

        $this->_dbh = NULL;
    }

    /**
     * _need_reconnect
     *
     * @return void
     */
    protected function _need_reconnect() {

        // 命令行模式自动重连MySQL
        if (PHP_SAPI == 'cli' || strtolower($_SERVER['RUN_MODE']) == 'development') {
            try {
                $info = @$this->_dbh->getAttribute(PDO::ATTR_SERVER_INFO);
                // PHP 5.2 获取属性值
                if ($info == 'MySQL server has gone away') {
                    return true;
                }
            } catch (PDOException $e) {
                // MySQL 5.4
                if ($e->getCode() == 'HY000' && $e->getMessage() == 'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away') {
                    return true;
                }
            }
        }

        return false;
    }
}

